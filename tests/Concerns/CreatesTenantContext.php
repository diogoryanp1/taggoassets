<?php

namespace Tests\Concerns;

use App\Domain\Identity\Models\Permission;
use App\Domain\Identity\Models\Role;
use App\Domain\Organizations\Models\OrganizationalUnit;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;

trait CreatesTenantContext
{
    /** @return array{tenant: Tenant, user: User, unit: OrganizationalUnit, role: Role} */
    protected function tenantContext(array $permissions = [], string $roleName = 'test_role'): array
    {
        $role = Role::factory()->create(['name' => $roleName]);
        foreach ($permissions as $permission) {
            $role->permissions()->attach(Permission::firstOrCreate(['name' => $permission], ['label' => $permission]));
        }
        $tenant = Tenant::factory()->create();
        $user = User::factory()->active()->create();
        $user->tenants()->attach($tenant, ['role_id' => $role->id, 'status' => 'active']);
        $unit = OrganizationalUnit::factory()->create(['tenant_id' => $tenant->id]);
        $user->organizationalUnits()->attach($unit);

        return compact('tenant', 'user', 'unit', 'role');
    }
}
