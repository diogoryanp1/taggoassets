<?php

namespace Tests\Feature\Assets;

use App\Domain\Assets\Models\AssetCategory;
use App\Domain\Assets\Models\AssetType;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class AssetTypeTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_type_validates_category_tenant_and_depreciation_rule(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->tenantContext(['asset_types.view', 'asset_types.create', 'asset_types.update', 'asset_types.deactivate'], 'manager');
        $category = AssetCategory::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->post(route('catalog.types.store'), ['asset_category_id' => $category->public_id, 'name' => 'Notebook', 'default_useful_life_months' => 60])->assertStatus(422);
        $this->post(route('catalog.types.store'), ['asset_category_id' => $category->public_id, 'name' => 'Notebook', 'is_depreciable' => '1', 'default_useful_life_months' => 60])->assertRedirect();
        $this->assertDatabaseHas('asset_types', ['tenant_id' => $tenant->id, 'name_normalized' => 'notebook', 'is_depreciable' => true]);
        $this->patch(route('catalog.types.deactivate', AssetType::firstOrFail()))->assertRedirect();
    }
}
