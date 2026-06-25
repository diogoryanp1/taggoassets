<?php

namespace Tests\Feature;

use App\Domain\Identity\Models\Permission;
use App\Domain\Identity\Models\Role;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TenancyIsolationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_access_a_unit_from_another_tenant(): void
    {
        $role = Role::create(['name' => 'tenant_admin', 'label' => 'Admin']);
        $first = Tenant::create(['name' => 'Primeiro', 'slug' => 'primeiro']);
        $second = Tenant::create(['name' => 'Segundo', 'slug' => 'segundo']);
        $user = User::factory()->create();
        $user->tenants()->attach($first, ['role_id' => $role->id, 'status' => 'active']);
        $unit = $second->units()->create(['type' => 'escola', 'name' => 'Privada', 'status' => 'active']);
        $this->actingAs($user)->withSession(['active_tenant' => $first->public_id])->get(route('units.edit', $unit->public_id))->assertNotFound();
    }

    public function test_tenant_id_from_frontend_is_not_used_when_creating_a_unit(): void
    {
        $role = Role::create(['name' => 'tenant_admin', 'label' => 'Admin']);
        $permission = Permission::create(['name' => 'organizations.create', 'label' => 'Criar']);
        $role->permissions()->attach($permission);
        $first = Tenant::create(['name' => 'Primeiro', 'slug' => 'primeiro']);
        $second = Tenant::create(['name' => 'Segundo', 'slug' => 'segundo']);
        $user = User::factory()->create();
        $user->tenants()->attach($first, ['role_id' => $role->id, 'status' => 'active']);
        $this->actingAs($user)->withSession(['active_tenant' => $first->public_id])->post(route('units.store'), ['tenant_id' => $second->id, 'name' => 'Nova', 'type' => 'setor', 'status' => 'active'])->assertRedirect();
        $this->assertDatabaseHas('organizational_units', ['tenant_id' => $first->id, 'name' => 'Nova']);
    }
}
