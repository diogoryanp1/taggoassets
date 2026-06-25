<?php

namespace Database\Factories;

use App\Domain\Organizations\Models\Location;
use App\Domain\Organizations\Models\OrganizationalUnit;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Location> */
class LocationFactory extends Factory
{
    protected $model = Location::class;

    public function configure(): static
    {
        return $this->afterCreating(function (Location $location): void {
            $location->unit()->update(['tenant_id' => $location->tenant_id]);
        });
    }

    public function definition(): array
    {
        return ['tenant_id' => Tenant::factory(), 'organizational_unit_id' => OrganizationalUnit::factory(), 'type' => 'room', 'code' => fake()->unique()->bothify('LOC-####'), 'name' => fake()->streetName(), 'status' => 'active'];
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }
}
