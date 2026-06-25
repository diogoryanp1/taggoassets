<?php

namespace Tests\Feature\Identity;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class InvitedUserAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_invited_user_cannot_authenticate_or_request_password_reset(): void
    {
        $user = User::factory()->create(['status' => 'invited', 'password' => 'KnownPassword!123']);
        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'KnownPassword!123'])->assertSessionHasErrors('email');
        Password::shouldReceive('sendResetLink')->never();
        $this->post(route('password.email'), ['email' => $user->email])->assertSessionHas('status');
    }
}
