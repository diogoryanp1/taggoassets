<?php

namespace Tests\Feature\Assets;

use App\Domain\Assets\Models\AssetBrand;
use App\Domain\Assets\Models\AssetCategory;
use App\Domain\Assets\Models\AssetModel;
use App\Domain\Assets\Models\AssetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class AssetProgressiveSelectionTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_progressive_selection_endpoints_are_tenant_scoped(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->tenantContext(['asset_types.view', 'asset_models.view', 'asset_custom_fields.view'], 'manager');
        $category = AssetCategory::factory()->create(['tenant_id' => $tenant->id]);
        $type = AssetType::factory()->create(['tenant_id' => $tenant->id, 'asset_category_id' => $category->id, 'name' => 'Notebook']);
        $brand = AssetBrand::factory()->create(['tenant_id' => $tenant->id]);
        AssetModel::factory()->create(['tenant_id' => $tenant->id, 'asset_brand_id' => $brand->id, 'asset_type_id' => $type->id, 'name' => 'Latitude']);

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->getJson(route('asset-selections.types', ['category' => $category->public_id]))->assertOk()->assertJsonFragment(['name' => 'Notebook']);
        $this->getJson(route('asset-selections.models', ['brand' => $brand->public_id, 'type' => $type->public_id]))->assertOk()->assertJsonFragment(['name' => 'Latitude']);
    }
}
