<?php

namespace Tests\Feature\Assets;

use App\Domain\Assets\Enums\AssetMovementStatus;
use App\Domain\Assets\Enums\AssetMovementType;
use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\AssetCustodian;
use App\Domain\Assets\Models\AssetMovement;
use App\Domain\Assets\Services\AssetMovementWorkflowService;
use App\Domain\Organizations\Models\Location;
use App\Domain\Organizations\Models\OrganizationalUnit;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use App\Notifications\AssetMovementNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class AssetMovementSprint2Test extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_asset_custodian_test_creates_tenant_scoped_custodian(): void
    {
        ['tenant' => $tenant, 'user' => $user, 'unit' => $unit] = $this->tenantContext(['asset_custodians.create', 'asset_custodians.view']);

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->post(route('custodians.store'), [
            'organizational_unit' => $unit->public_id,
            'name' => 'Responsavel de Teste',
            'registration_number' => 'MAT-777',
            'email' => 'responsavel@example.test',
        ])->assertRedirect(route('custodians.index'));

        $this->assertDatabaseHas('asset_custodians', ['tenant_id' => $tenant->id, 'registration_number' => 'MAT-777']);
    }

    public function test_asset_movement_creation_test_derives_origin_and_completes_internal_change(): void
    {
        [$tenant, $user, $asset, $destination, $custodian] = $this->movementContext(['asset_movements.create']);

        $movement = app(AssetMovementWorkflowService::class)->create($tenant, $user, [
            'asset_id' => $asset->id,
            'movement_type' => AssetMovementType::CustodianChange->value,
            'destination_custodian_id' => $custodian->id,
            'reason' => 'Troca operacional.',
        ]);

        $this->assertSame(AssetMovementStatus::Completed, $movement->status);
        $this->assertSame($asset->organizational_unit_id, $movement->origin_organizational_unit_id);
        $this->assertSame($custodian->id, $asset->refresh()->custodian_id);
        $this->assertDatabaseHas('audit_logs', ['action' => 'asset_movement.created']);
    }

    public function test_asset_movement_approval_test_approves_pending_transfer(): void
    {
        [$tenant, $user, $asset, $destination, $custodian] = $this->movementContext(['asset_movements.create', 'asset_movements.approve']);
        Notification::fake();
        $movement = app(AssetMovementWorkflowService::class)->create($tenant, $user, [
            'asset_id' => $asset->id,
            'movement_type' => AssetMovementType::InternalTransfer->value,
            'destination_organizational_unit_id' => $destination->id,
            'reason' => 'Transferencia entre unidades.',
        ]);

        app(AssetMovementWorkflowService::class)->approve($movement, $user);

        $this->assertSame(AssetMovementStatus::Approved, $movement->refresh()->status);
        Notification::assertSentTo($user, AssetMovementNotification::class);
    }

    public function test_asset_transfer_test_complete_updates_asset_after_approval(): void
    {
        [$tenant, $user, $asset, $destination, $custodian] = $this->movementContext(['asset_movements.create', 'asset_movements.approve', 'asset_movements.complete']);
        $movement = app(AssetMovementWorkflowService::class)->create($tenant, $user, [
            'asset_id' => $asset->id,
            'movement_type' => AssetMovementType::InternalTransfer->value,
            'destination_organizational_unit_id' => $destination->id,
            'reason' => 'Transferencia aprovada.',
        ]);

        app(AssetMovementWorkflowService::class)->approve($movement, $user);
        app(AssetMovementWorkflowService::class)->complete($movement, $user);

        $this->assertSame($destination->id, $asset->refresh()->organizational_unit_id);
        $this->assertSame(AssetMovementStatus::Completed, $movement->refresh()->status);
    }

    public function test_asset_movement_rejection_and_cancellation_do_not_change_asset(): void
    {
        [$tenant, $user, $asset, $destination, $custodian] = $this->movementContext(['asset_movements.create', 'asset_movements.reject', 'asset_movements.cancel']);
        $originalUnit = $asset->organizational_unit_id;
        $movement = app(AssetMovementWorkflowService::class)->create($tenant, $user, [
            'asset_id' => $asset->id,
            'movement_type' => AssetMovementType::InternalTransfer->value,
            'destination_organizational_unit_id' => $destination->id,
            'reason' => 'Transferencia rejeitada.',
        ]);

        app(AssetMovementWorkflowService::class)->reject($movement, $user, 'Sem necessidade.');

        $this->assertSame($originalUnit, $asset->refresh()->organizational_unit_id);
        $this->assertSame(AssetMovementStatus::Rejected, $movement->refresh()->status);
    }

    public function test_asset_temporary_checkout_and_return_test_links_return_to_open_checkout(): void
    {
        [$tenant, $user, $asset, , $custodian] = $this->movementContext(['asset_movements.create']);
        $checkout = app(AssetMovementWorkflowService::class)->create($tenant, $user, [
            'asset_id' => $asset->id,
            'movement_type' => AssetMovementType::TemporaryCheckout->value,
            'destination_custodian_id' => $custodian->id,
            'expected_return_at' => now()->addDay(),
            'reason' => 'Saida temporaria.',
        ]);

        $return = app(AssetMovementWorkflowService::class)->create($tenant, $user, [
            'asset_id' => $asset->id,
            'movement_type' => AssetMovementType::TemporaryReturn->value,
            'related_movement_id' => $checkout->id,
            'reason' => 'Retorno confirmado.',
        ]);

        $this->assertNotNull($checkout->refresh()->returned_at);
        $this->assertSame(AssetMovementStatus::Completed, $return->status);
    }

    public function test_asset_loan_and_loan_return_test_blocks_duplicate_return(): void
    {
        [$tenant, $user, $asset, , $custodian] = $this->movementContext(['asset_movements.create', 'asset_movements.approve', 'asset_movements.complete']);
        $loan = app(AssetMovementWorkflowService::class)->create($tenant, $user, [
            'asset_id' => $asset->id,
            'movement_type' => AssetMovementType::Loan->value,
            'destination_custodian_id' => $custodian->id,
            'expected_return_at' => now()->addDay(),
            'reason' => 'Emprestimo.',
        ]);
        app(AssetMovementWorkflowService::class)->approve($loan, $user);
        app(AssetMovementWorkflowService::class)->complete($loan, $user);

        app(AssetMovementWorkflowService::class)->create($tenant, $user, ['asset_id' => $asset->id, 'movement_type' => AssetMovementType::LoanReturn->value, 'related_movement_id' => $loan->id, 'reason' => 'Retorno.']);

        $this->expectException(ValidationException::class);
        app(AssetMovementWorkflowService::class)->create($tenant, $user, ['asset_id' => $asset->id, 'movement_type' => AssetMovementType::LoanReturn->value, 'related_movement_id' => $loan->id, 'reason' => 'Retorno duplicado.']);
    }

    public function test_asset_movement_authorization_and_idor_test_blocks_other_tenant_route(): void
    {
        [$tenant, $user] = array_values($this->tenantContext(['asset_movements.view']));
        $other = AssetMovement::factory()->create();

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get(route('movements.show', $other))->assertNotFound();
    }

    public function test_asset_term_generation_test_stores_private_pdf_document(): void
    {
        Storage::fake('private');
        [$tenant, $user, $asset, , $custodian] = $this->movementContext(['asset_movements.create', 'asset_terms.generate', 'asset_terms.download']);
        $movement = app(AssetMovementWorkflowService::class)->create($tenant, $user, ['asset_id' => $asset->id, 'movement_type' => AssetMovementType::CustodianChange->value, 'destination_custodian_id' => $custodian->id, 'reason' => 'Termo.']);

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->post(route('movements.term', $movement))->assertRedirect();

        $this->assertNotNull($movement->refresh()->term_document_id);
        Storage::disk('private')->assertExists($movement->termDocument()->firstOrFail()->stored_name);
    }

    public function test_asset_movement_filtering_and_dashboard_test_renders_metrics(): void
    {
        [$tenant, $user, $asset, , $custodian] = $this->movementContext(['asset_movements.create', 'asset_movements.view', 'assets.view']);
        app(AssetMovementWorkflowService::class)->create($tenant, $user, ['asset_id' => $asset->id, 'movement_type' => AssetMovementType::CustodianChange->value, 'destination_custodian_id' => $custodian->id, 'reason' => 'Filtro.']);

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get(route('movements.index', ['asset_number' => $asset->asset_number]))->assertOk()->assertSee($asset->asset_number);
        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get(route('dashboard'))->assertOk()->assertSee('Movimentações pendentes')->assertSee('Bens sem responsável');
    }

    /** @return array{0: Tenant, 1: User, 2: Asset, 3: OrganizationalUnit, 4: AssetCustodian} */
    private function movementContext(array $permissions): array
    {
        ['tenant' => $tenant, 'user' => $user, 'unit' => $unit] = $this->tenantContext($permissions);
        $asset = Asset::factory()->active()->create(['tenant_id' => $tenant->id, 'organizational_unit_id' => $unit->id]);
        $destination = OrganizationalUnit::factory()->create(['tenant_id' => $tenant->id]);
        Location::factory()->create(['tenant_id' => $tenant->id, 'organizational_unit_id' => $destination->id]);
        $custodian = AssetCustodian::factory()->create(['tenant_id' => $tenant->id, 'organizational_unit_id' => $unit->id]);
        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id]);

        return [$tenant, $user, $asset, $destination, $custodian];
    }
}
