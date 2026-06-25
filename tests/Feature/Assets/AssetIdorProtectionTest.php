<?php

namespace Tests\Feature\Assets;

use App\Domain\Assets\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class AssetIdorProtectionTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_internal_numeric_id_is_not_a_public_asset_route_key(): void
    {
        ['tenant' => $tenant, 'user' => $user, 'unit' => $unit] = $this->tenantContext(['assets.view'], 'viewer');
        $asset = Asset::factory()->create(['tenant_id' => $tenant->id, 'organizational_unit_id' => $unit->id]);

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get('/assets/'.$asset->id)->assertNotFound();
    }
}
