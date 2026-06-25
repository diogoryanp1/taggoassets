<?php

namespace Tests\Feature\Assets;

use App\Domain\Assets\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class AssetTenantIsolationTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_asset_from_other_tenant_returns_404(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->tenantContext(['assets.view'], 'viewer');
        ['tenant' => $otherTenant] = $this->tenantContext([], 'other_asset_tenant');
        $asset = Asset::factory()->create(['tenant_id' => $otherTenant->id]);

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get(route('assets.show', $asset))->assertNotFound();
    }
}
