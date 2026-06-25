<?php

namespace Tests\Feature\Identity;

use App\Domain\Identity\Models\UserSession;
use App\Domain\Identity\SessionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class SessionManagementTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_user_can_only_list_and_revoke_own_sessions_by_public_id(): void
    {
        ['user' => $user] = $this->tenantContext();
        $own = UserSession::factory()->create(['user_id' => $user->id]);
        $other = UserSession::factory()->create();

        $this->actingAs($user)->get(route('sessions.index'))->assertOk()->assertSee('127.0.0.***')->assertDontSee($own->session_fingerprint);
        $this->actingAs($user)->delete(route('sessions.destroy', $other))->assertNotFound();
        $this->actingAs($user)->delete(route('sessions.destroy', $own))->assertRedirect();
        $this->assertNotNull($own->fresh()->revoked_at);
        $this->actingAs($user)->delete('/sessions/'.$own->id)->assertNotFound();
    }

    public function test_destroy_other_sessions_requires_current_password(): void
    {
        ['user' => $user] = $this->tenantContext();
        UserSession::factory()->create(['user_id' => $user->id]);

        $this->actingAs($user)->delete(route('sessions.destroy-others'), ['password' => 'wrong'])->assertSessionHasErrors('password');
    }

    public function test_redis_session_revocation_destroys_the_underlying_session(): void
    {
        ['user' => $user] = $this->tenantContext();
        $id = 'session-for-revocation-test';
        $handler = app('session')->getHandler();
        $handler->write($id, 'authenticated-session');
        $session = UserSession::factory()->create(['user_id' => $user->id, 'session_id_encrypted' => $id, 'session_fingerprint' => hash_hmac('sha256', $id, config('app.key'))]);

        app(SessionManager::class)->revoke($session);

        $this->assertSame('', $handler->read($id));
        $this->assertNotNull($session->fresh()->revoked_at);
    }
}
