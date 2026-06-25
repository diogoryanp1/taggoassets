<?php

namespace Tests\Feature\Assets;

use App\Domain\Assets\Models\AssetCategory;
use App\Domain\Assets\Models\AssetCustomFieldDefinition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class AssetCustomFieldTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_select_field_requires_options_and_key_is_unique_per_category(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->tenantContext(['asset_custom_fields.view', 'asset_custom_fields.manage'], 'tenant_admin');
        $category = AssetCategory::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->post(route('catalog.custom-fields.store'), ['asset_category_id' => $category->public_id, 'name' => 'Voltagem', 'field_type' => 'select'])->assertStatus(422);
        $this->post(route('catalog.custom-fields.store'), ['asset_category_id' => $category->public_id, 'name' => 'Voltagem', 'key' => 'voltagem', 'field_type' => 'select', 'options' => ['110', '220']])->assertRedirect();
        $this->assertDatabaseHas('asset_custom_field_definitions', ['tenant_id' => $tenant->id, 'key' => 'voltagem']);
        $this->patch(route('catalog.custom-fields.deactivate', AssetCustomFieldDefinition::firstOrFail()))->assertRedirect();
    }
}
