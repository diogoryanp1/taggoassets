<?php

namespace Tests\Feature\Assets;

use App\Domain\Assets\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class AssetUpdateTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_asset_update_ignores_spoofed_internal_fields(): void
    {
        ['tenant' => $tenant, 'user' => $user, 'unit' => $unit] = $this->tenantContext(['assets.update', 'assets.set_manual_number'], 'manager');
        $asset = Asset::factory()->create(['tenant_id' => $tenant->id, 'organizational_unit_id' => $unit->id]);
        $payload = ['asset_number' => 'MAN-001', 'description' => 'Atualizado', 'asset_category_id' => $asset->category->public_id, 'asset_type_id' => $asset->type->public_id, 'unit_of_measure_id' => $asset->unitOfMeasure->public_id, 'condition_id' => $asset->condition->public_id, 'organizational_unit_id' => $unit->public_id, 'status' => 'active', 'tenant_id' => 999, 'created_by' => 999];

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->put(route('assets.update', $asset), $payload)->assertRedirect();
        $this->assertSame($tenant->id, $asset->refresh()->tenant_id);
        $this->assertSame('Atualizado', $asset->description);
    }
}
