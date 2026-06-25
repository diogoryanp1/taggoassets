<?php

namespace Tests\Feature\Assets;

use App\Domain\Assets\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class AssetDeactivationTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_asset_can_be_deactivated_with_audit(): void
    {
        ['tenant' => $tenant, 'user' => $user, 'unit' => $unit] = $this->tenantContext(['assets.deactivate'], 'manager');
        $asset = Asset::factory()->active()->create(['tenant_id' => $tenant->id, 'organizational_unit_id' => $unit->id]);

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->patch(route('assets.deactivate', $asset))->assertRedirect();
        $this->assertFalse($asset->refresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $tenant->id, 'action' => 'asset.deactivated']);
    }
}
