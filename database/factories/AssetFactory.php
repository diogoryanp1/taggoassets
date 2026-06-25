<?php

namespace Database\Factories;

use App\Domain\Assets\Enums\AssetStatus;
use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\AssetCategory;
use App\Domain\Assets\Models\AssetCondition;
use App\Domain\Assets\Models\AssetType;
use App\Domain\Assets\Models\UnitOfMeasure;
use App\Domain\Organizations\Models\OrganizationalUnit;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Asset> */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    public function configure(): static
    {
        return $this->afterCreating(function (Asset $asset): void {
            $asset->category()->update(['tenant_id' => $asset->tenant_id]);
            $asset->type()->update(['tenant_id' => $asset->tenant_id, 'asset_category_id' => $asset->asset_category_id]);
            $asset->organizationalUnit()->update(['tenant_id' => $asset->tenant_id]);
            $asset->location?->update(['tenant_id' => $asset->tenant_id, 'organizational_unit_id' => $asset->organizational_unit_id]);
            $asset->brand?->update(['tenant_id' => $asset->tenant_id]);
            $asset->model?->update(['tenant_id' => $asset->tenant_id, 'asset_brand_id' => $asset->brand_id, 'asset_type_id' => $asset->asset_type_id]);
        });
    }

    public function definition(): array
    {
        return ['tenant_id' => Tenant::factory(), 'asset_number' => fake()->unique()->bothify('PAT-2026-######'), 'legacy_number' => fake()->optional()->bothify('LEG-####'), 'description' => fake()->sentence(3), 'asset_category_id' => AssetCategory::factory(), 'asset_type_id' => AssetType::factory(), 'unit_of_measure_id' => UnitOfMeasure::factory()->system(), 'condition_id' => AssetCondition::factory()->system(), 'status' => AssetStatus::Draft->value, 'organizational_unit_id' => OrganizationalUnit::factory(), 'acquisition_date' => fake()->optional()->date(), 'acquisition_value_cents' => fake()->optional()->numberBetween(1000, 1000000), 'serial_number' => fake()->optional()->bothify('SN-######'), 'notes' => fake()->optional()->sentence(), 'custom_values' => [], 'is_active' => true];
    }

    public function active(): static
    {
        return $this->state(['is_active' => true, 'status' => AssetStatus::Active->value]);
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false, 'status' => AssetStatus::Inactive->value]);
    }

    public function draft(): static
    {
        return $this->state(['is_active' => true, 'status' => AssetStatus::Draft->value]);
    }
}
