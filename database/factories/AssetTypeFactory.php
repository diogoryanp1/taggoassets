<?php

namespace Database\Factories;

use App\Domain\Assets\Models\AssetCategory;
use App\Domain\Assets\Models\AssetType;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AssetType> */
class AssetTypeFactory extends Factory
{
    protected $model = AssetType::class;

    public function configure(): static
    {
        return $this->afterCreating(function (AssetType $type): void {
            $type->category()->update(['tenant_id' => $type->tenant_id]);
        });
    }

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return ['tenant_id' => Tenant::factory(), 'asset_category_id' => AssetCategory::factory(), 'name' => Str::title($name), 'name_normalized' => Str::lower($name), 'code' => fake()->unique()->bothify('TYP-###'), 'description' => fake()->optional()->sentence(), 'is_active' => true, 'requires_serial_number' => false, 'requires_brand' => false, 'requires_model' => false, 'is_depreciable' => false, 'default_useful_life_months' => null];
    }

    public function active(): static
    {
        return $this->state(['is_active' => true]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function depreciable(): static
    {
        return $this->state(['is_depreciable' => true, 'default_useful_life_months' => 60]);
    }

    public function requiresSerialNumber(): static
    {
        return $this->state(['requires_serial_number' => true]);
    }

    public function requiresBrand(): static
    {
        return $this->state(['requires_brand' => true]);
    }

    public function requiresModel(): static
    {
        return $this->state(['requires_model' => true]);
    }
}
