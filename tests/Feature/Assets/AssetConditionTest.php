<?php

namespace Tests\Feature\Assets;

use App\Domain\Assets\Models\AssetCondition;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class AssetConditionTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_system_condition_is_readonly_and_tenant_condition_can_be_reactivated(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->tenantContext(['asset_conditions.view', 'asset_conditions.manage'], 'tenant_admin');
        $system = AssetCondition::factory()->system()->create();
        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get(route('catalog.conditions.edit', $system))->assertForbidden();
        $this->post(route('catalog.conditions.store'), ['name' => 'Revisado', 'sort_order' => 10])->assertRedirect();
        $condition = AssetCondition::where('tenant_id', $tenant->id)->firstOrFail();
        $this->patch(route('catalog.conditions.deactivate', $condition))->assertRedirect();
        $this->patch(route('catalog.conditions.reactivate', $condition))->assertRedirect();
    }
}
