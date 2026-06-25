<?php

namespace Tests\Feature\Organizations;

use App\Domain\Identity\Models\Permission;
use App\Domain\Identity\Models\Role;
use App\Domain\Organizations\Models\Location;
use App\Domain\Organizations\Models\OrganizationalUnit;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class LocationHierarchyTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_children_endpoint_returns_only_immediate_children_from_current_tenant(): void
    {
        $role = Role::create(['name' => 'manager', 'label' => 'Manager']);
        $permission = Permission::create(['name' => 'organizations.update', 'label' => 'Atualizar']);
        $role->permissions()->attach($permission);
        $tenant = Tenant::create(['name' => 'T', 'slug' => 't']);
        $user = User::factory()->create();
        $user->tenants()->attach($tenant, ['role_id' => $role->id, 'status' => 'active']);
        $unit = $tenant->units()->create(['name' => 'U', 'type' => 'setor', 'status' => 'active']);
        $user->organizationalUnits()->attach($unit);
        $root = Location::forceCreate(['tenant_id' => $tenant->id, 'organizational_unit_id' => $unit->id, 'name' => 'A', 'type' => 'building', 'status' => 'active']);
        $child = Location::forceCreate(['tenant_id' => $tenant->id, 'organizational_unit_id' => $unit->id, 'parent_id' => $root->id, 'name' => 'B', 'type' => 'floor', 'status' => 'active']);
        Location::forceCreate(['tenant_id' => $tenant->id, 'organizational_unit_id' => $unit->id, 'parent_id' => $child->id, 'name' => 'C', 'type' => 'room', 'status' => 'active']);
        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->getJson(route('locations.children', $root))->assertOk()->assertJsonCount(1)->assertJsonPath('0.name', 'B');
    }

    public function test_location_routes_create_hierarchy_and_reject_self_descendant_or_cross_unit_parent(): void
    {
        ['tenant' => $tenant, 'user' => $user, 'unit' => $unit] = $this->tenantContext(['organizations.create', 'organizations.update']);
        $payload = ['organizational_unit_id' => $unit->public_id, 'type' => 'building', 'name' => 'Root', 'status' => 'active'];
        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->post(route('locations.store'), $payload)->assertRedirect();
        $root = Location::query()->where('tenant_id', $tenant->id)->where('name', 'Root')->firstOrFail();
        $child = Location::factory()->create(['tenant_id' => $tenant->id, 'organizational_unit_id' => $unit->id, 'parent_id' => $root->id]);
        $otherUnit = OrganizationalUnit::factory()->create(['tenant_id' => $tenant->id]);
        $otherParent = Location::factory()->create(['tenant_id' => $tenant->id, 'organizational_unit_id' => $otherUnit->id]);

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->put(route('locations.update', $root), ['organizational_unit_id' => $unit->public_id, 'parent_id' => $root->public_id, 'type' => 'building', 'name' => 'Root', 'status' => 'active'])->assertStatus(422);
        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->put(route('locations.update', $root), ['organizational_unit_id' => $unit->public_id, 'parent_id' => $child->public_id, 'type' => 'building', 'name' => 'Root', 'status' => 'active'])->assertStatus(422);
        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->post(route('locations.store'), ['organizational_unit_id' => $unit->public_id, 'parent_id' => $otherParent->public_id, 'type' => 'floor', 'name' => 'Invalid child', 'status' => 'active'])->assertStatus(422);
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $tenant->id, 'action' => 'location.created']);
    }
}
