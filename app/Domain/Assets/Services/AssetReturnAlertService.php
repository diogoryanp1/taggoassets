<?php

namespace App\Domain\Assets\Services;

use App\Domain\Assets\Enums\AssetMovementStatus;
use App\Domain\Assets\Enums\AssetMovementType;
use App\Domain\Assets\Models\AssetMovement;
use App\Domain\Audit\Models\AuditLog;
use App\Domain\Audit\Services\AuditPayloadSanitizer;
use App\Domain\Identity\Models\Role;
use App\Models\User;
use App\Notifications\AssetMovementNotification;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class AssetReturnAlertService
{
    public function sendUpcoming(): int
    {
        $days = max(1, (int) config('taggo.asset_return_reminder_days', 3));
        $today = now()->startOfDay();
        $limit = now()->addDays($days)->endOfDay();

        return $this->process('upcoming_return', 'asset_return_reminder.sent', fn (Builder $query): Builder => $query
            ->whereBetween('expected_return_at', [$today, $limit]));
    }

    public function sendOverdue(): int
    {
        return $this->process('overdue', 'asset_return_overdue_notification.sent', fn (Builder $query): Builder => $query
            ->where('expected_return_at', '<', now()->startOfDay()));
    }

    /** @param callable(Builder<AssetMovement>): Builder<AssetMovement> $scope */
    private function process(string $event, string $auditAction, callable $scope): int
    {
        $sent = 0;
        $marker = $event.'_'.now()->toDateString();
        $base = AssetMovement::query()
            ->whereIn('movement_type', [AssetMovementType::TemporaryCheckout->value, AssetMovementType::Loan->value])
            ->where('status', AssetMovementStatus::Completed->value)
            ->whereNull('returned_at')
            ->whereNotNull('expected_return_at')
            ->with(['tenant', 'asset:id,asset_number,description', 'requester:id,name,email', 'destinationCustodian.user:id,name,email']);
        $scope($base)->orderBy('id')->chunkById(100, function (Collection $movements) use ($event, $auditAction, $marker, &$sent): void {
            foreach ($movements as $movement) {
                if ($this->alreadyMarked($movement, $marker)) {
                    continue;
                }
                foreach ($this->recipients($movement) as $recipient) {
                    $recipient->notify(new AssetMovementNotification($movement, $event));
                    $sent++;
                }
                $this->mark($movement, $marker);
                $this->audit($movement, $auditAction, ['marker' => $marker]);
            }
        });

        return $sent;
    }

    /** @return Collection<int, User> */
    private function recipients(AssetMovement $movement): Collection
    {
        $tenant = $movement->tenant()->firstOrFail();
        $roleIds = Role::query()
            ->whereHas('permissions', fn ($query) => $query->whereIn('name', ['asset_movements.approve', 'asset_movements.view']))
            ->pluck('id');
        $managers = User::query()
            ->whereHas('tenants', fn ($query) => $query->whereKey($tenant->id)->where('tenant_user.status', 'active')->whereIn('tenant_user.role_id', $roleIds))
            ->whereNotNull('email')
            ->get(['id', 'name', 'email']);
        $users = collect([$movement->requester()->first(), $movement->destinationCustodian()->first()?->user()->first()])
            ->filter(fn (?User $user): bool => $user instanceof User && filled($user->email))
            ->merge($managers)
            ->unique('id')
            ->values();

        return $users;
    }

    private function alreadyMarked(AssetMovement $movement, string $marker): bool
    {
        return (bool) data_get($movement->metadata ?? [], 'return_alerts.'.$marker, false);
    }

    private function mark(AssetMovement $movement, string $marker): void
    {
        $metadata = $movement->metadata ?? [];
        data_set($metadata, 'return_alerts.'.$marker, Carbon::now()->toIso8601String());
        $movement->forceFill(['metadata' => $metadata])->save();
    }

    private function audit(AssetMovement $movement, string $action, array $payload): void
    {
        AuditLog::create([
            'tenant_id' => $movement->tenant_id,
            'user_id' => null,
            'action' => $action,
            'entity_type' => AssetMovement::class,
            'entity_id' => $movement->id,
            'old_values' => null,
            'new_values' => app(AuditPayloadSanitizer::class)->sanitize($payload),
            'ip_address' => null,
            'user_agent' => null,
            'request_id' => null,
            'reason' => null,
        ]);
    }
}
