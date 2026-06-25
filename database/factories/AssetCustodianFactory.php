<?php

namespace Database\Factories;

use App\Domain\Assets\Models\AssetCustodian;
use App\Domain\Organizations\Models\OrganizationalUnit;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AssetCustodian> */
class AssetCustodianFactory extends Factory
{
    protected $model = AssetCustodian::class;

    public function configure(): static
    {
        return $this->afterCreating(function (AssetCustodian $custodian): void {
            $custodian->organizationalUnit()->update(['tenant_id' => $custodian->tenant_id]);
        });
    }

    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'organizational_unit_id' => OrganizationalUnit::factory(),
            'name' => fake()->name(),
            'registration_number' => fake()->unique()->numerify('MAT-#####'),
            'document_identifier' => fake()->optional()->numerify('###.###.###-##'),
            'email' => fake()->safeEmail(),
            'phone' => fake()->phoneNumber(),
            'position' => fake()->jobTitle(),
            'is_active' => true,
        ];
    }
}
