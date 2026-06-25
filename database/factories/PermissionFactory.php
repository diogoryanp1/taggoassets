<?php

namespace Database\Factories;

use App\Domain\Identity\Models\Permission;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Permission> */
class PermissionFactory extends Factory
{
    protected $model = Permission::class;

    public function definition(): array
    {
        $name = fake()->unique()->lexify('permission.??????');

        return ['name' => $name, 'label' => ucfirst($name)];
    }
}
