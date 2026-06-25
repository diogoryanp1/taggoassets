<?php

namespace Tests\Feature\Assets;

use App\Domain\Assets\Models\AssetBrand;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class AssetBrandTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_brand_crud_uses_current_tenant_and_blocks_duplicates(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->tenantContext(['asset_brands.view', 'asset_brands.create', 'asset_brands.update', 'asset_brands.deactivate'], 'manager');

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->post(route('catalog.brands.store'), ['name' => ' Dell '])->assertRedirect();
        $this->assertDatabaseHas('asset_brands', ['tenant_id' => $tenant->id, 'name_normalized' => 'dell']);
        $brand = AssetBrand::firstOrFail();
        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->post(route('catalog.brands.store'), ['name' => 'dell'])->assertStatus(422);
        $this->patch(route('catalog.brands.deactivate', $brand))->assertRedirect();
        $this->assertFalse($brand->refresh()->is_active);
    }
}
