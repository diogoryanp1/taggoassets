<?php

namespace Database\Factories;

use App\Domain\Identity\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Role> */
class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        $name = fake()->unique()->lexify('role_??????');

        return ['name' => $name, 'label' => ucfirst($name)];
    }

    public function tenantAdmin(): static
    {
        return $this->state(['name' => 'tenant_admin', 'label' => 'Tenant admin']);
    }

    public function manager(): static
    {
        return $this->state(['name' => 'manager', 'label' => 'Manager']);
    }

    public function member(): static
    {
        return $this->state(['name' => 'member', 'label' => 'Member']);
    }

    public function auditor(): static
    {
        return $this->state(['name' => 'auditor', 'label' => 'Auditor']);
    }

    public function viewer(): static
    {
        return $this->state(['name' => 'viewer', 'label' => 'Viewer']);
    }
}
