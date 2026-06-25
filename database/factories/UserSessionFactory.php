<?php

namespace Database\Factories;

use App\Domain\Identity\Models\UserSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<UserSession> */
class UserSessionFactory extends Factory
{
    protected $model = UserSession::class;

    public function definition(): array
    {
        $sessionId = Str::random(40);

        return ['user_id' => User::factory(), 'session_id_encrypted' => $sessionId, 'session_fingerprint' => hash_hmac('sha256', $sessionId, config('app.key')), 'ip_address' => '127.0.0.1', 'user_agent' => 'Mozilla/5.0', 'last_activity_at' => now()];
    }
}
