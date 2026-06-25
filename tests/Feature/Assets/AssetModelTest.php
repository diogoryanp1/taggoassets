<?php

namespace Tests\Feature\Assets;

use App\Domain\Assets\Models\AssetBrand;
use App\Domain\Assets\Models\AssetCategory;
use App\Domain\Assets\Models\AssetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class AssetModelTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_model_requires_brand_and_type_from_current_tenant(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->tenantContext(['asset_models.view', 'asset_models.create'], 'manager');
        $category = AssetCategory::factory()->create(['tenant_id' => $tenant->id]);
        $type = AssetType::factory()->create(['tenant_id' => $tenant->id, 'asset_category_id' => $category->id]);
        $brand = AssetBrand::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->post(route('catalog.models.store'), ['asset_brand_id' => $brand->public_id, 'asset_type_id' => $type->public_id, 'name' => 'Latitude'])->assertRedirect();
        $this->assertDatabaseHas('asset_models', ['tenant_id' => $tenant->id, 'name_normalized' => 'latitude']);
    }
}
