<?php

namespace Tests\Feature\Assets;

use App\Domain\Assets\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class AssetAuditTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_asset_detail_shows_audit_summary_without_documents_preload(): void
    {
        ['tenant' => $tenant, 'user' => $user, 'unit' => $unit] = $this->tenantContext(['assets.view'], 'viewer');
        $asset = Asset::factory()->create(['tenant_id' => $tenant->id, 'organizational_unit_id' => $unit->id]);

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get(route('assets.show', $asset))->assertOk()->assertSee('Auditoria resumida')->assertSee('Visualizar documentos');
    }
}
