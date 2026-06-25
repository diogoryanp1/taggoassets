<?php

namespace Tests\Feature\Assets;

use App\Domain\Assets\Enums\AssetMovementDocumentType;
use App\Domain\Assets\Enums\AssetMovementType;
use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\AssetCustodian;
use App\Domain\Assets\Models\AssetMovement;
use App\Domain\Assets\Models\AssetMovementDocument;
use App\Domain\Assets\Services\AssetMovementWorkflowService;
use App\Domain\Assets\Services\AssetReturnAlertService;
use App\Domain\Assets\Services\AssetTermGenerator;
use App\Domain\Organizations\Models\Location;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use App\Notifications\AssetMovementNotification;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class AssetMovementDocumentsAndAlertsTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_asset_term_pdf_test_generates_real_private_pdf_with_hash(): void
    {
        Storage::fake('private');
        [$tenant, $user, $movement] = $this->completedMovement(['asset_terms.generate', 'asset_terms.download', 'asset_movement_documents.view', 'asset_movement_documents.download']);

        $document = app(AssetTermGenerator::class)->generate($movement, $user);
        $bytes = Storage::disk('private')->get($document->stored_name);

        $this->assertStringStartsWith('%PDF-', $bytes);
        $this->assertSame('application/pdf', $document->mime_type);
        $this->assertSame(hash('sha256', $bytes), $document->sha256);
        $this->assertSame($tenant->id, $document->tenant_id);
        $this->assertSame($user->id, $document->uploaded_by);
        $this->assertDatabaseHas('asset_movement_documents', ['asset_movement_id' => $movement->id, 'private_document_id' => $document->id, 'document_type' => AssetMovementDocumentType::GeneratedTerm->value]);

        $again = app(AssetTermGenerator::class)->generate($movement->refresh(), $user);
        $this->assertSame($document->id, $again->id);
    }

    public function test_asset_term_authorization_test_download_is_protected_by_tenant_and_permission(): void
    {
        Storage::fake('private');
        [$tenant, $user, $movement] = $this->completedMovement(['asset_terms.generate', 'asset_terms.download', 'asset_movement_documents.view', 'asset_movement_documents.download']);
        $document = app(AssetTermGenerator::class)->generate($movement, $user);

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get(route('documents.download', $document))->assertOk()->assertHeader('content-type', 'application/pdf');

        ['tenant' => $otherTenant, 'user' => $otherUser] = $this->tenantContext(['asset_terms.download', 'asset_movement_documents.download'], 'other_reader');
        $this->actingAs($otherUser)->withSession(['active_tenant' => $otherTenant->public_id])->get(route('documents.download', $document))->assertNotFound();

        ['tenant' => $blockedTenant, 'user' => $blocked] = $this->tenantContext(['asset_terms.view'], 'blocked_reader');
        $blocked->tenants()->sync([$tenant->id => ['role_id' => $blocked->tenants()->first()->pivot->role_id, 'status' => 'active']]);
        $this->actingAs($blocked)->withSession(['active_tenant' => $tenant->public_id])->get(route('documents.download', $document))->assertForbidden();
    }

    public function test_asset_movement_document_upload_test_stores_private_document_and_lists_without_preload(): void
    {
        Storage::fake('private');
        [$tenant, $user, $movement] = $this->completedMovement(['asset_movements.view', 'asset_movement_documents.view', 'asset_movement_documents.upload', 'asset_movement_documents.download']);

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->post(route('movements.documents.store', $movement), [
            'document_type' => AssetMovementDocumentType::Receipt->value,
            'file' => UploadedFile::fake()->create('comprovante.pdf', 12, 'application/pdf'),
        ])->assertRedirect();

        $link = AssetMovementDocument::with('document')->firstOrFail();
        Storage::disk('private')->assertExists($link->document->stored_name);
        $this->assertSame(hash_file('sha256', Storage::disk('private')->path($link->document->stored_name)), $link->document->sha256);

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get(route('movements.show', $movement))->assertOk()->assertSee('Comprovante')->assertSee('Visualizar')->assertSee('Baixar')->assertDontSee($link->document->stored_name);
    }

    public function test_asset_movement_document_authorization_and_deactivation_test(): void
    {
        Storage::fake('private');
        [$tenant, $user, $movement] = $this->completedMovement(['asset_movements.view', 'asset_movement_documents.view', 'asset_movement_documents.upload', 'asset_movement_documents.download', 'asset_movement_documents.deactivate']);
        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->post(route('movements.documents.store', $movement), [
            'document_type' => AssetMovementDocumentType::SignedTerm->value,
            'file' => UploadedFile::fake()->image('termo.jpg'),
        ]);
        $link = AssetMovementDocument::with('document')->firstOrFail();

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->patch(route('movements.documents.deactivate', $link))->assertRedirect();
        $this->assertNotNull($link->refresh()->deactivated_at);
        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get(route('documents.download', $link->document))->assertForbidden();
    }

    public function test_asset_movement_document_tenant_isolation_test(): void
    {
        Storage::fake('private');
        [$tenant, $user, $movement] = $this->completedMovement(['asset_movements.view', 'asset_movement_documents.upload']);
        ['tenant' => $otherTenant, 'user' => $otherUser] = $this->tenantContext(['asset_movements.view', 'asset_movement_documents.upload'], 'other_upload');

        $this->actingAs($otherUser)->withSession(['active_tenant' => $otherTenant->public_id])->post(route('movements.documents.store', $movement), [
            'document_type' => AssetMovementDocumentType::Receipt->value,
            'file' => UploadedFile::fake()->create('comprovante.pdf', 12, 'application/pdf'),
        ])->assertNotFound();
    }

    public function test_asset_return_reminder_test_sends_upcoming_and_is_idempotent(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-06-25 08:00:00');
        [, $user, $movement] = $this->openReturn(['asset_movements.view'], now()->addDays(2));

        $first = app(AssetReturnAlertService::class)->sendUpcoming();
        $second = app(AssetReturnAlertService::class)->sendUpcoming();

        $this->assertGreaterThan(0, $first);
        $this->assertSame(0, $second);
        Notification::assertSentTo($user, AssetMovementNotification::class);
        $this->assertNotNull(data_get($movement->refresh()->metadata, 'return_alerts.upcoming_return_2026-06-25'));
        Carbon::setTestNow();
    }

    public function test_asset_overdue_return_test_sends_only_overdue_open_movements(): void
    {
        Notification::fake();
        Carbon::setTestNow('2026-06-25 08:00:00');
        [, $user, $movement] = $this->openReturn(['asset_movements.view'], now()->subDay());

        $sent = app(AssetReturnAlertService::class)->sendOverdue();

        $this->assertGreaterThan(0, $sent);
        Notification::assertSentTo($user, AssetMovementNotification::class);
        $this->assertNotNull(data_get($movement->refresh()->metadata, 'return_alerts.overdue_2026-06-25'));
        Carbon::setTestNow();
    }

    public function test_asset_return_scheduler_test_registers_daily_commands(): void
    {
        $commands = collect(app(Schedule::class)->events())->map(fn ($event) => $event->command)->implode("\n");

        $this->assertStringContainsString('assets:returns:upcoming', $commands);
        $this->assertStringContainsString('assets:returns:overdue', $commands);
    }

    /** @return array{0: Tenant, 1: User, 2: AssetMovement} */
    private function completedMovement(array $permissions): array
    {
        ['tenant' => $tenant, 'user' => $user, 'unit' => $unit] = $this->tenantContext($permissions, 'sprint2_'.Str::random(6));
        $asset = Asset::factory()->active()->create(['tenant_id' => $tenant->id, 'organizational_unit_id' => $unit->id]);
        $custodian = AssetCustodian::factory()->create(['tenant_id' => $tenant->id, 'organizational_unit_id' => $unit->id]);
        $movement = app(AssetMovementWorkflowService::class)->create($tenant, $user, [
            'asset_id' => $asset->id,
            'movement_type' => AssetMovementType::CustodianChange->value,
            'destination_custodian_id' => $custodian->id,
            'reason' => 'Documento de teste.',
        ]);

        return [$tenant, $user, $movement];
    }

    /** @return array{0: Tenant, 1: User, 2: AssetMovement} */
    private function openReturn(array $permissions, Carbon $expectedReturn): array
    {
        ['tenant' => $tenant, 'user' => $user, 'unit' => $unit] = $this->tenantContext($permissions, 'alerts_'.Str::random(6));
        $asset = Asset::factory()->active()->create(['tenant_id' => $tenant->id, 'organizational_unit_id' => $unit->id]);
        $custodian = AssetCustodian::factory()->create(['tenant_id' => $tenant->id, 'organizational_unit_id' => $unit->id, 'user_id' => $user->id]);
        Location::factory()->create(['tenant_id' => $tenant->id, 'organizational_unit_id' => $unit->id]);
        $movement = app(AssetMovementWorkflowService::class)->create($tenant, $user, [
            'asset_id' => $asset->id,
            'movement_type' => AssetMovementType::TemporaryCheckout->value,
            'destination_custodian_id' => $custodian->id,
            'expected_return_at' => $expectedReturn,
            'reason' => 'Retorno monitorado.',
        ]);

        return [$tenant, $user, $movement];
    }
}
