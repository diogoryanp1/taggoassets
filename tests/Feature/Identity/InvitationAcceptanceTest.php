<?php

namespace Tests\Feature\Identity;

use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Models\UserInvitation;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class InvitationAcceptanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_valid_invitation_activates_user_membership_and_is_single_use(): void
    {
        $tenant = Tenant::factory()->create();
        $role = Role::factory()->create(['name' => 'viewer']);
        $user = User::factory()->invited()->create(['email_verified_at' => null]);
        $user->tenants()->attach($tenant, ['role_id' => $role->id, 'status' => 'inactive']);
        $token = 'valid-invitation-token';
        $invitation = UserInvitation::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'role_id' => $role->id, 'email' => $user->email, 'token_hash' => hash('sha256', $token)]);

        $this->post(route('invitations.complete', $invitation), ['token' => $token, 'password' => 'NewSecurePassword!123', 'password_confirmation' => 'NewSecurePassword!123', 'tenant_id' => 999, 'role_id' => 999])->assertRedirect(route('login'));

        $user->refresh();
        $this->assertSame('active', $user->status);
        $this->assertNotNull($user->email_verified_at);
        $this->assertTrue(Hash::check('NewSecurePassword!123', $user->password));
        $this->assertSame('active', $user->tenants()->whereKey($tenant->id)->firstOrFail()->pivot->status);
        $this->assertNotNull($invitation->fresh()->accepted_at);
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $tenant->id, 'action' => 'invitation.accepted']);
        $this->post(route('invitations.complete', $invitation), ['token' => $token, 'password' => 'AnotherSecurePassword!123', 'password_confirmation' => 'AnotherSecurePassword!123'])->assertNotFound();
    }

    public function test_invalid_expired_revoked_and_blocked_invites_are_rejected(): void
    {
        $role = Role::factory()->create(['name' => 'viewer']);
        $tenant = Tenant::factory()->create();
        $user = User::factory()->invited()->create();
        $user->tenants()->attach($tenant, ['role_id' => $role->id, 'status' => 'inactive']);
        foreach ([['expires_at' => now()->subSecond()], ['revoked_at' => now()], []] as $index => $attributes) {
            $invitation = UserInvitation::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'role_id' => $role->id, 'token_hash' => hash('sha256', 'known-token-'.$index), ...$attributes]);
            $this->post(route('invitations.complete', $invitation), ['token' => 'wrong-token', 'password' => 'NewSecurePassword!123', 'password_confirmation' => 'NewSecurePassword!123'])->assertNotFound();
        }
        $blocked = UserInvitation::factory()->create(['tenant_id' => $tenant->id, 'user_id' => User::factory()->blocked()->create()->id, 'role_id' => $role->id, 'token_hash' => hash('sha256', 'blocked-token')]);
        $this->post(route('invitations.complete', $blocked), ['token' => 'blocked-token', 'password' => 'NewSecurePassword!123', 'password_confirmation' => 'NewSecurePassword!123'])->assertNotFound();
    }
}
