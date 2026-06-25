<?php

namespace Database\Factories;

use App\Domain\Assets\Models\AssetCondition;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AssetCondition> */
class AssetConditionFactory extends Factory
{
    protected $model = AssetCondition::class;

    public function definition(): array
    {
        $name = fake()->unique()->word();

        return ['tenant_id' => Tenant::factory(), 'name' => Str::title($name), 'code' => Str::slug($name, '_'), 'description' => fake()->optional()->sentence(), 'sort_order' => fake()->numberBetween(0, 100), 'is_system' => false, 'is_active' => true];
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
