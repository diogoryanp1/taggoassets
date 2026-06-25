<?php

namespace Tests\Feature\Assets;

use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\AssetCategory;
use App\Domain\Assets\Models\AssetCondition;
use App\Domain\Assets\Models\AssetType;
use App\Domain\Assets\Models\UnitOfMeasure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class AssetCreationTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_asset_is_created_with_backend_number_and_tenant_scoped_relations(): void
    {
        ['tenant' => $tenant, 'user' => $user, 'unit' => $organizationalUnit] = $this->tenantContext(['assets.create'], 'manager');
        $category = AssetCategory::forceCreate(['tenant_id' => $tenant->id, 'name' => 'Computadores', 'name_normalized' => 'computadores', 'is_active' => true]);
        $type = AssetType::forceCreate(['tenant_id' => $tenant->id, 'asset_category_id' => $category->id, 'name' => 'Notebook', 'name_normalized' => 'notebook', 'is_active' => true]);
        $unit = UnitOfMeasure::forceCreate(['name' => 'Unidade', 'name_normalized' => 'unidade', 'symbol' => 'UN', 'symbol_normalized' => 'un', 'type' => 'unit', 'is_system' => true, 'is_active' => true]);
        $condition = AssetCondition::forceCreate(['name' => 'Novo', 'code' => 'new', 'is_system' => true, 'is_active' => true]);

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->post(route('assets.store'), ['asset_number' => 'SPOOFED-001', 'description' => 'Notebook de teste', 'asset_category_id' => $category->public_id, 'asset_type_id' => $type->public_id, 'unit_of_measure_id' => $unit->public_id, 'condition_id' => $condition->public_id, 'organizational_unit_id' => $organizationalUnit->public_id, 'status' => 'draft', 'tenant_id' => 999])->assertRedirect();

        $asset = Asset::firstOrFail();
        $this->assertSame($tenant->id, $asset->tenant_id);
        $this->assertSame('PAT-'.now()->format('Y').'-000001', $asset->asset_number);
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $tenant->id, 'action' => 'asset.created']);
    }

    public function test_asset_listing_requires_a_filter_before_querying_records(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->tenantContext(['assets.view'], 'viewer');

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get(route('assets.index'))->assertOk()->assertSee('Informe um termo ou selecione ao menos um filtro');
    }
}
