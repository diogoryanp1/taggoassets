<?php

namespace Database\Factories;

use App\Domain\Assets\Models\AssetBrand;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AssetBrand> */
class AssetBrandFactory extends Factory
{
    protected $model = AssetBrand::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return ['tenant_id' => Tenant::factory(), 'name' => $name, 'name_normalized' => Str::lower(trim($name)), 'is_active' => true];
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }
}
