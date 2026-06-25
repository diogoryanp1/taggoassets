<?php

namespace Tests\Feature\Security;

use App\Domain\Assets\Models\AssetBrand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class CatalogIdorProtectionTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_catalog_numeric_id_is_not_public_route_key(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->tenantContext(['asset_brands.view'], 'viewer');
        $brand = AssetBrand::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get('/catalog/brands/'.$brand->id)->assertNotFound();
    }
}
