<?php

namespace Database\Factories;

use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Tenant> */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        $slug = Str::lower(fake()->unique()->lexify('tenant-??????'));

        return ['name' => fake()->company(), 'slug' => $slug, 'status' => 'active', 'timezone' => 'America/Sao_Paulo', 'locale' => 'pt_BR'];
    }

    public function inactive(): static
    {
        return $this->state(['status' => 'inactive']);
    }
}
