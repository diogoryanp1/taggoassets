<?php

namespace App\Domain\Assets\Services;

use App\Domain\Assets\Enums\AssetMovementStatus;
use App\Domain\Assets\Enums\AssetMovementType;
use App\Domain\Assets\Enums\AssetStatus;
use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\AssetCustodian;
use App\Domain\Assets\Models\AssetMovement;
use App\Domain\Audit\AuditLogger;
use App\Domain\Organizations\Models\Location;
use App\Domain\Organizations\Models\OrganizationalUnit;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use App\Notifications\AssetMovementNotification;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

final class AssetMovementWorkflowService
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function create(Tenant $tenant, User $user, array $data): AssetMovement
    {
        $type = AssetMovementType::from((string) $data['movement_type']);
        if (! $type->implemented()) {
            throw ValidationException::withMessages(['movement_type' => 'Tipo de movimentação ainda não operacional nesta sprint.']);
        }

        return DB::transaction(function () use ($tenant, $user, $data, $type): AssetMovement {
            $asset = Asset::query()->whereKey((int) $data['asset_id'])->where('tenant_id', $tenant->id)->lockForUpdate()->firstOrFail();
            $this->ensureNoBlockingMovement($asset);
            $destinationUnit = $this->unit($tenant, $data['destination_organizational_unit_id'] ?? null);
            $destinationLocation = $this->location($tenant, $data['destination_location_id'] ?? null);
            $destinationCustodian = $this->custodian($tenant, $data['destination_custodian_id'] ?? null);
            $asset->loadMissing(['organizationalUnit:id,name', 'location:id,name', 'custodian:id,name']);
            $related = isset($data['related_movement_id']) ? AssetMovement::query()->forTenant($tenant->id)->whereKey((int) $data['related_movement_id'])->lockForUpdate()->firstOrFail() : null;
            $this->validateByType($type, $asset, $destinationUnit, $destinationLocation, $destinationCustodian, $related, $data);

            $status = $this->requiresApproval($type, $asset, $destinationUnit)
                ? AssetMovementStatus::PendingApproval
                : AssetMovementStatus::Completed;
            $movement = AssetMovement::forceCreate([
                'tenant_id' => $tenant->id,
                'asset_id' => $asset->id,
                'movement_type' => $type->value,
                'status' => $status->value,
                'origin_organizational_unit_id' => $asset->organizational_unit_id,
                'destination_organizational_unit_id' => $destinationUnit?->id,
                'origin_location_id' => $asset->location_id,
                'destination_location_id' => $destinationLocation?->id,
                'origin_custodian_id' => $asset->custodian_id,
                'destination_custodian_id' => $destinationCustodian?->id,
                'requested_by' => $user->id,
                'effective_at' => $status === AssetMovementStatus::Completed ? now() : null,
                'expected_return_at' => $data['expected_return_at'] ?? null,
                'returned_at' => in_array($type, [AssetMovementType::TemporaryReturn, AssetMovementType::LoanReturn], true) ? now() : null,
                'related_movement_id' => $related?->id,
                'reason' => $data['reason'],
                'notes' => $data['notes'] ?? null,
                'metadata' => $this->metadataSnapshot($asset, $destinationUnit, $destinationLocation, $destinationCustodian),
            ]);

            if ($status === AssetMovementStatus::Completed) {
                $this->applyMovementToAsset($asset, $movement);
                $this->markRelatedReturned($related, $movement);
            }
            $this->audit->record('asset_movement.created', $movement, [], ['type' => $type->value, 'status' => $status->value]);
            if ($status === AssetMovementStatus::PendingApproval) {
                $user->notify(new AssetMovementNotification($movement, 'pending'));
            }
            $this->forgetDashboard($tenant);

            return $movement;
        });
    }

    public function approve(AssetMovement $movement, User $user): AssetMovement
    {
        return DB::transaction(function () use ($movement, $user): AssetMovement {
            $movement = AssetMovement::query()->whereKey($movement->id)->lockForUpdate()->firstOrFail();
            if ($movement->movementStatus() !== AssetMovementStatus::PendingApproval) {
                throw ValidationException::withMessages(['status' => 'Somente movimentações pendentes podem ser aprovadas.']);
            }
            $movement->forceFill(['status' => AssetMovementStatus::Approved->value, 'approved_by' => $user->id, 'approved_at' => now()])->save();
            $this->audit->record('asset_movement.approved', $movement, [], ['approved_by' => $user->id]);
            $movement->requester()->first()?->notify(new AssetMovementNotification($movement, 'approved'));

            return $movement;
        });
    }

    public function reject(AssetMovement $movement, User $user, ?string $reason = null): AssetMovement
    {
        return $this->transitionWithoutAssetChange($movement, $user, AssetMovementStatus::Rejected, 'asset_movement.rejected', $reason);
    }

    public function cancel(AssetMovement $movement, User $user, ?string $reason = null): AssetMovement
    {
        return $this->transitionWithoutAssetChange($movement, $user, AssetMovementStatus::Cancelled, 'asset_movement.cancelled', $reason);
    }

    public function complete(AssetMovement $movement, User $user): AssetMovement
    {
        return DB::transaction(function () use ($movement, $user): AssetMovement {
            $movement = AssetMovement::query()->with('asset')->whereKey($movement->id)->lockForUpdate()->firstOrFail();
            if (! in_array($movement->movementStatus(), [AssetMovementStatus::Approved], true)) {
                throw ValidationException::withMessages(['status' => 'Somente movimentações aprovadas podem ser concluídas manualmente.']);
            }
            $asset = Asset::query()->whereKey($movement->asset_id)->lockForUpdate()->firstOrFail();
            $this->applyMovementToAsset($asset, $movement);
            $this->markRelatedReturned($movement->related_movement_id ? AssetMovement::query()->whereKey($movement->related_movement_id)->lockForUpdate()->first() : null, $movement);
            $movement->forceFill(['status' => AssetMovementStatus::Completed->value, 'effective_at' => now()])->save();
            $this->audit->record('asset_movement.completed', $movement, [], ['completed_by' => $user->id]);
            $this->forgetDashboard($movement->tenant()->firstOrFail());

            return $movement;
        });
    }

    private function transitionWithoutAssetChange(AssetMovement $movement, User $user, AssetMovementStatus $status, string $action, ?string $reason): AssetMovement
    {
        return DB::transaction(function () use ($movement, $user, $status, $action, $reason): AssetMovement {
            $movement = AssetMovement::query()->whereKey($movement->id)->lockForUpdate()->firstOrFail();
            if (! in_array($movement->movementStatus(), [AssetMovementStatus::PendingApproval, AssetMovementStatus::Approved], true)) {
                throw ValidationException::withMessages(['status' => 'A transição solicitada não é permitida.']);
            }
            $movement->forceFill(['status' => $status->value, 'approved_by' => $status === AssetMovementStatus::Rejected ? $user->id : $movement->approved_by, 'approved_at' => $status === AssetMovementStatus::Rejected ? now() : $movement->approved_at])->save();
            $this->audit->record($action, $movement, [], ['status' => $status->value], $reason);
            if ($status === AssetMovementStatus::Rejected) {
                $movement->requester()->first()?->notify(new AssetMovementNotification($movement, 'rejected'));
            }

            return $movement;
        });
    }

    private function validateByType(AssetMovementType $type, Asset $asset, ?OrganizationalUnit $unit, ?Location $location, ?AssetCustodian $custodian, ?AssetMovement $related, array $data): void
    {
        if (in_array($type, [AssetMovementType::InternalTransfer, AssetMovementType::InitialAssignment], true) && ! $unit) {
            throw ValidationException::withMessages(['destination_organizational_unit' => 'Informe a unidade administrativa de destino.']);
        }
        if ($type === AssetMovementType::LocationChange && ! $location) {
            throw ValidationException::withMessages(['destination_location' => 'Informe a localização de destino.']);
        }
        if ($type === AssetMovementType::LocationChange && $location->organizational_unit_id !== $asset->organizational_unit_id) {
            throw ValidationException::withMessages(['destination_location' => 'A localização deve pertencer à unidade atual do ativo.']);
        }
        if ($type === AssetMovementType::CustodianChange && ! $custodian) {
            throw ValidationException::withMessages(['destination_custodian' => 'Informe o responsável de destino.']);
        }
        if ($custodian && $unit && $custodian->organizational_unit_id !== $unit->id) {
            throw ValidationException::withMessages(['destination_custodian' => 'O responsável deve pertencer à unidade de destino.']);
        }
        if ($custodian && ! $unit && $custodian->organizational_unit_id !== $asset->organizational_unit_id) {
            throw ValidationException::withMessages(['destination_custodian' => 'O responsável deve pertencer à unidade atual.']);
        }
        if (in_array($type, [AssetMovementType::TemporaryCheckout, AssetMovementType::Loan], true) && (empty($data['expected_return_at']) || ! $custodian)) {
            throw ValidationException::withMessages(['expected_return_at' => 'Informe responsável e data prevista de retorno.']);
        }
        if (in_array($type, [AssetMovementType::TemporaryReturn, AssetMovementType::LoanReturn], true) && (! $related || $related->returned_at !== null || $related->asset_id !== $asset->id)) {
            throw ValidationException::withMessages(['related_movement' => 'Informe uma saída ou empréstimo aberto do mesmo ativo.']);
        }
        $sameUnit = ! $unit || $unit->id === $asset->organizational_unit_id;
        $sameLocation = ! $location || $location->id === $asset->location_id;
        $sameCustodian = ! $custodian || $custodian->id === $asset->custodian_id;
        if ($sameUnit && $sameLocation && $sameCustodian && ! in_array($type, [AssetMovementType::TemporaryReturn, AssetMovementType::LoanReturn], true)) {
            throw ValidationException::withMessages(['movement_type' => 'A movimentação não altera unidade, localização ou responsável.']);
        }
    }

    private function applyMovementToAsset(Asset $asset, AssetMovement $movement): void
    {
        $type = $movement->movementType();
        if ($movement->destination_organizational_unit_id) {
            $asset->organizational_unit_id = $movement->destination_organizational_unit_id;
        }
        if ($movement->destination_location_id || in_array($type, [AssetMovementType::InternalTransfer, AssetMovementType::InitialAssignment], true)) {
            $asset->location_id = $movement->destination_location_id;
        }
        if ($movement->destination_custodian_id || in_array($type, [AssetMovementType::InternalTransfer, AssetMovementType::CustodianChange, AssetMovementType::InitialAssignment], true)) {
            $asset->custodian_id = $movement->destination_custodian_id;
        }
        $asset->status = match ($type) {
            AssetMovementType::TemporaryCheckout => AssetStatus::InTransfer,
            AssetMovementType::Loan => AssetStatus::Loaned,
            AssetMovementType::TemporaryReturn, AssetMovementType::LoanReturn => AssetStatus::Active,
            default => $asset->status,
        };
        $asset->save();
    }

    private function ensureNoBlockingMovement(Asset $asset): void
    {
        $exists = AssetMovement::query()->where('asset_id', $asset->id)->whereIn('status', [AssetMovementStatus::PendingApproval->value, AssetMovementStatus::Approved->value])->exists();
        if ($exists) {
            throw ValidationException::withMessages(['asset' => 'Este ativo possui movimentação em andamento.']);
        }
    }

    private function requiresApproval(AssetMovementType $type, Asset $asset, ?OrganizationalUnit $destinationUnit): bool
    {
        return $type === AssetMovementType::Loan || ($type === AssetMovementType::InternalTransfer && $destinationUnit?->id !== $asset->organizational_unit_id);
    }

    private function markRelatedReturned(?AssetMovement $related, AssetMovement $returnMovement): void
    {
        if ($related) {
            $related->forceFill(['returned_at' => $returnMovement->returned_at ?? now()])->save();
        }
    }

    private function unit(Tenant $tenant, mixed $id): ?OrganizationalUnit
    {
        return $id ? OrganizationalUnit::query()->forTenant($tenant->id)->whereKey((int) $id)->firstOrFail() : null;
    }

    private function location(Tenant $tenant, mixed $id): ?Location
    {
        return $id ? Location::query()->forTenant($tenant->id)->whereKey((int) $id)->firstOrFail() : null;
    }

    private function custodian(Tenant $tenant, mixed $id): ?AssetCustodian
    {
        return $id ? AssetCustodian::query()->forTenant($tenant->id)->where('is_active', true)->whereKey((int) $id)->firstOrFail() : null;
    }

    private function metadataSnapshot(Asset $asset, ?OrganizationalUnit $unit, ?Location $location, ?AssetCustodian $custodian): array
    {
        return [
            'origin' => ['unit' => $asset->organizationalUnit?->name, 'location' => $asset->location?->name, 'custodian' => $asset->custodian?->name],
            'destination' => ['unit' => $unit?->name, 'location' => $location?->name, 'custodian' => $custodian?->name],
        ];
    }

    private function forgetDashboard(Tenant $tenant): void
    {
        Cache::forget("tenant:{$tenant->id}:dashboard:summary");
    }
}
