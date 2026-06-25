<?php

namespace Database\Factories;

use App\Domain\Organizations\Models\OrganizationalUnit;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<OrganizationalUnit> */
class OrganizationalUnitFactory extends Factory
{
    protected $model = OrganizationalUnit::class;

    public function definition(): array
    {
        return ['tenant_id' => Tenant::factory(), 'type' => 'department', 'code' => fake()->unique()->bothify('UNIT-####'), 'name' => fake()->company(), 'status' => 'active'];
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }
}
