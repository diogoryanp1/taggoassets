<?php

namespace Tests\Feature\Identity;

use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Models\UserInvitation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class InvitationCreationTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_tenant_admin_creates_hashed_invitation_with_backend_owned_fields(): void
    {
        Notification::fake();
        ['tenant' => $tenant, 'user' => $admin] = $this->tenantContext(['users.create'], 'tenant_admin');
        $role = Role::factory()->create(['name' => 'viewer']);
        $otherRole = Role::factory()->create(['name' => 'manager']);
        $previous = UserInvitation::factory()->create(['tenant_id' => $tenant->id, 'email' => 'invitee@example.test', 'role_id' => $otherRole->id, 'user_id' => User::factory()->invited()->create()->id]);

        $this->actingAs($admin)->withSession(['active_tenant' => $tenant->public_id])->post(route('invitations.store'), ['name' => 'Invitee', 'email' => 'INVITEE@EXAMPLE.TEST', 'role' => $role->name, 'tenant_id' => 999, 'invited_by' => 999])->assertRedirect(route('invitations.index'));

        $invitation = UserInvitation::query()->where('email', 'invitee@example.test')->where('role_id', $role->id)->firstOrFail();
        $this->assertSame($tenant->id, $invitation->tenant_id);
        $this->assertSame($admin->id, $invitation->invited_by);
        $this->assertSame($role->id, $invitation->role_id);
        $this->assertSame('invited', $invitation->user->status);
        $this->assertSame(64, strlen($invitation->token_hash));
        $this->assertTrue($invitation->expires_at->between(now()->addDays(3)->subMinute(), now()->addDays(3)->addMinute()));
        $this->assertNotNull($previous->fresh()->revoked_at);
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $tenant->id, 'action' => 'invitation.created']);
    }

    public function test_invitation_creation_rejects_missing_permission_blocked_accounts_and_super_admin_role(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->tenantContext([], 'member');
        $viewer = Role::factory()->create(['name' => 'viewer']);
        $superAdmin = Role::factory()->create(['name' => 'super_admin']);

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->post(route('invitations.store'), ['name' => 'No permission', 'email' => 'no-permission@example.test', 'role' => $viewer->name])->assertForbidden();
        $user->update(['status' => 'blocked']);
        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->post(route('invitations.store'), ['name' => 'Blocked', 'email' => 'blocked@example.test', 'role' => $viewer->name])->assertForbidden();
        ['tenant' => $adminTenant, 'user' => $admin] = $this->tenantContext(['users.create'], 'tenant_admin');
        $this->actingAs($admin)->withSession(['active_tenant' => $adminTenant->public_id])->post(route('invitations.store'), ['name' => 'Forbidden role', 'email' => 'role@example.test', 'role' => $superAdmin->name])->assertSessionHasErrors('role');
    }
}
