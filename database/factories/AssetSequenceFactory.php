<?php

namespace Database\Factories;

use App\Domain\Assets\Models\AssetSequence;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AssetSequence> */
class AssetSequenceFactory extends Factory
{
    protected $model = AssetSequence::class;

    public function definition(): array
    {
        return ['tenant_id' => Tenant::factory(), 'year' => (int) now()->format('Y'), 'next_value' => 1];
    }
}
