<?php

namespace Tests\Feature\Identity;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class AuthenticationFlowTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_active_tenant_admin_authenticates_and_receives_active_tenant_session(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->tenantContext(['dashboard.view'], 'tenant_admin');
        $user->forceFill(['password' => 'KnownPassword!123'])->save();

        $this->post(route('login.store'), ['email' => $user->email, 'password' => 'KnownPassword!123'])->assertRedirect(route('dashboard'))->assertSessionHas('active_tenant', $tenant->public_id);
        $this->assertAuthenticatedAs($user);
    }

    public function test_login_ignores_stale_intended_url_and_redirects_to_dashboard(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->tenantContext(['dashboard.view'], 'tenant_admin_login_redirect');
        $user->forceFill(['password' => 'KnownPassword!123'])->save();

        $this
            ->withSession(['url.intended' => url('/movements/01KVZENQGE26DPMWR609JHFAYJ')])
            ->post(route('login.store'), ['email' => $user->email, 'password' => 'KnownPassword!123'])
            ->assertRedirect(route('dashboard'))
            ->assertSessionMissing('url.intended')
            ->assertSessionHas('active_tenant', $tenant->public_id);

        $this->assertAuthenticatedAs($user);
    }

    public function test_wrong_password_invited_and_blocked_users_do_not_authenticate(): void
    {
        ['user' => $active] = $this->tenantContext([], 'auth_active');
        $active->forceFill(['password' => 'KnownPassword!123'])->save();
        $invited = User::factory()->invited()->create(['password' => 'KnownPassword!123']);
        $blocked = User::factory()->blocked()->create(['password' => 'KnownPassword!123']);

        $this->post(route('login.store'), ['email' => $active->email, 'password' => 'wrong'])->assertSessionHasErrors('email');
        $this->post(route('login.store'), ['email' => $invited->email, 'password' => 'KnownPassword!123'])->assertSessionHasErrors('email');
        $this->post(route('login.store'), ['email' => $blocked->email, 'password' => 'KnownPassword!123'])->assertSessionHasErrors('email');
        $this->assertGuest();
    }
}
