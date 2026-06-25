<?php

namespace Tests\Feature\Tenancy;

use App\Domain\Assets\Models\AssetBrand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class CatalogTenantIsolationTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_catalog_resource_from_other_tenant_returns_404(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->tenantContext(['asset_brands.view'], 'viewer');
        ['tenant' => $otherTenant] = $this->tenantContext([], 'other_catalog_tenant');
        $brand = AssetBrand::factory()->create(['tenant_id' => $otherTenant->id]);

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get(route('catalog.brands.show', $brand))->assertNotFound();
    }
}
