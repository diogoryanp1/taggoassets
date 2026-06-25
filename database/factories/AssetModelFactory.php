<?php

namespace Database\Factories;

use App\Domain\Assets\Models\AssetBrand;
use App\Domain\Assets\Models\AssetModel;
use App\Domain\Assets\Models\AssetType;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<AssetModel> */
class AssetModelFactory extends Factory
{
    protected $model = AssetModel::class;

    public function configure(): static
    {
        return $this->afterCreating(function (AssetModel $model): void {
            $model->brand()->update(['tenant_id' => $model->tenant_id]);
            $model->type?->update(['tenant_id' => $model->tenant_id]);
        });
    }

    public function definition(): array
    {
        $name = fake()->unique()->bothify('Model ###');

        return ['tenant_id' => Tenant::factory(), 'asset_brand_id' => AssetBrand::factory(), 'asset_type_id' => AssetType::factory(), 'name' => $name, 'name_normalized' => Str::lower(trim($name)), 'manufacturer_code' => fake()->optional()->bothify('MFG-###'), 'description' => fake()->optional()->sentence(), 'is_active' => true];
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
