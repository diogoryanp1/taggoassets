<?php

namespace Database\Factories;

use App\Domain\Assets\Enums\AssetMovementStatus;
use App\Domain\Assets\Enums\AssetMovementType;
use App\Domain\Assets\Models\Asset;
use App\Domain\Assets\Models\AssetMovement;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AssetMovement> */
class AssetMovementFactory extends Factory
{
    protected $model = AssetMovement::class;

    public function definition(): array
    {
        $asset = Asset::factory()->active()->create();
        $user = User::factory()->active()->create();

        return [
            'tenant_id' => $asset->tenant_id,
            'asset_id' => $asset->id,
            'movement_type' => AssetMovementType::CustodianChange->value,
            'status' => AssetMovementStatus::Completed->value,
            'origin_organizational_unit_id' => $asset->organizational_unit_id,
            'origin_location_id' => $asset->location_id,
            'origin_custodian_id' => $asset->custodian_id,
            'requested_by' => $user->id,
            'effective_at' => now(),
            'reason' => fake()->sentence(),
        ];
    }
}
