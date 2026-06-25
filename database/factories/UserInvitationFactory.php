<?php

namespace Database\Factories;

use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Models\UserInvitation;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<UserInvitation> */
class UserInvitationFactory extends Factory
{
    protected $model = UserInvitation::class;

    public function definition(): array
    {
        $token = Str::random(64);

        return ['tenant_id' => Tenant::factory(), 'user_id' => User::factory(), 'role_id' => Role::factory(), 'email' => fake()->unique()->safeEmail(), 'name' => fake()->name(), 'token_hash' => hash('sha256', $token), 'expires_at' => now()->addDays(3)];
    }

    public function expired(): static
    {
        return $this->state(['expires_at' => now()->subSecond()]);
    }

    public function accepted(): static
    {
        return $this->state(['accepted_at' => now()]);
    }

    public function revoked(): static
    {
        return $this->state(['revoked_at' => now()]);
    }
}
