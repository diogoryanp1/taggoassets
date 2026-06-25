<?php

namespace Tests\Feature\Assets;

use App\Domain\Assets\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class AssetReactivationTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_asset_can_be_reactivated_with_existing_route(): void
    {
        ['tenant' => $tenant, 'user' => $user, 'unit' => $unit] = $this->tenantContext(['assets.deactivate'], 'manager');
        $asset = Asset::factory()->inactive()->create(['tenant_id' => $tenant->id, 'organizational_unit_id' => $unit->id]);

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->patch(route('assets.reactivate', $asset))->assertRedirect();
        $this->assertTrue($asset->refresh()->is_active);
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $tenant->id, 'action' => 'asset.reactivated']);
    }
}
