<?php

namespace Database\Factories;

use App\Domain\Assets\Models\AssetCategory;
use App\Domain\Assets\Models\AssetCustomFieldDefinition;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AssetCustomFieldDefinition> */
class AssetCustomFieldDefinitionFactory extends Factory
{
    protected $model = AssetCustomFieldDefinition::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return ['tenant_id' => Tenant::factory(), 'asset_category_id' => AssetCategory::factory(), 'name' => Str::title($name), 'key' => Str::slug($name, '_'), 'field_type' => 'text', 'is_required' => false, 'options' => null, 'validation_rules' => null, 'sort_order' => fake()->numberBetween(0, 100), 'is_active' => true];
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
