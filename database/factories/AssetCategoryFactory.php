<?php

namespace Database\Factories;

use App\Domain\Assets\Models\AssetCategory;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AssetCategory> */
class AssetCategoryFactory extends Factory
{
    protected $model = AssetCategory::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return ['tenant_id' => Tenant::factory(), 'name' => Str::title($name), 'name_normalized' => Str::lower($name), 'code' => fake()->unique()->bothify('CAT-###'), 'description' => fake()->optional()->sentence(), 'is_active' => true, 'sort_order' => fake()->numberBetween(0, 100)];
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
