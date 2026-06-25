<?php

namespace Database\Factories;

use App\Domain\Assets\Models\UnitOfMeasure;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<UnitOfMeasure> */
class UnitOfMeasureFactory extends Factory
{
    protected $model = UnitOfMeasure::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();
        $symbol = Str::upper(fake()->unique()->lexify('??'));

        return ['tenant_id' => Tenant::factory(), 'name' => Str::title($name), 'name_normalized' => Str::lower($name), 'symbol' => $symbol, 'symbol_normalized' => Str::lower($symbol), 'type' => 'unit', 'decimal_places' => 0, 'is_system' => false, 'is_active' => true];
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function system(): static
    {
        return $this->state(['tenant_id' => null, 'is_system' => true, 'is_active' => true]);
    }

    public function tenantOwned(): static
    {
        return $this->state(['is_system' => false]);
    }
}
