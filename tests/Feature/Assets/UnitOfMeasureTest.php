<?php

namespace Tests\Feature\Assets;

use App\Domain\Assets\Models\UnitOfMeasure;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class UnitOfMeasureTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_system_unit_is_readonly_and_tenant_unit_can_be_managed(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->tenantContext(['units_of_measure.view', 'units_of_measure.manage'], 'tenant_admin');
        $system = UnitOfMeasure::factory()->system()->create();

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get(route('catalog.units.index'))->assertOk();
        $this->get(route('catalog.units.edit', $system))->assertForbidden();
        $this->post(route('catalog.units.store'), ['name' => 'Pacote', 'symbol' => 'PCT', 'type' => 'unit', 'decimal_places' => 0])->assertRedirect();
        $this->assertDatabaseHas('unit_of_measures', ['tenant_id' => $tenant->id, 'symbol_normalized' => 'pct']);
    }
}
