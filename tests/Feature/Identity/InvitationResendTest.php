<?php

namespace Tests\Feature\Identity;

use App\Domain\Identity\Models\Permission;
use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Models\UserInvitation;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use App\Notifications\UserInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class InvitationResendTest extends TestCase
{
    use RefreshDatabase;

    public function test_tenant_admin_can_resend_and_replace_the_token_hash(): void
    {
        Notification::fake();
        $role = Role::create(['name' => 'tenant_admin', 'label' => 'Admin']);
        $permission = Permission::create(['name' => 'users.create', 'label' => 'Criar']);
        $role->permissions()->attach($permission);
        $tenant = Tenant::create(['name' => 'Tenant', 'slug' => 'tenant']);
        $admin = User::factory()->create();
        $admin->forceFill(['status' => 'active', 'is_platform_admin' => true])->save();
        $admin->tenants()->attach($tenant, ['role_id' => $role->id, 'status' => 'active']);
        $invited = User::factory()->create(['status' => 'invited']);
        $invited->tenants()->attach($tenant, ['role_id' => $role->id, 'status' => 'inactive']);
        $invitation = UserInvitation::create(['tenant_id' => $tenant->id, 'user_id' => $invited->id, 'role_id' => $role->id, 'invited_by' => $admin->id, 'email' => $invited->email, 'name' => $invited->name, 'token_hash' => hash('sha256', 'old'), 'expires_at' => now()->subMinute()]);
        $old = $invitation->token_hash;
        $this->actingAs($admin)->withSession(['active_tenant' => $tenant->public_id])->post(route('invitations.resend', ['invitation' => $invitation->public_id]))->assertRedirect();
        $this->assertNotSame($old, $invitation->fresh()->token_hash);
        Notification::assertSentOnDemand(UserInvitationNotification::class);
    }

    public function test_accepted_invitation_cannot_be_resent(): void
    {
        $role = Role::create(['name' => 'tenant_admin', 'label' => 'Admin']);
        $permission = Permission::create(['name' => 'users.create', 'label' => 'Criar']);
        $role->permissions()->attach($permission);
        $tenant = Tenant::create(['name' => 'Tenant', 'slug' => 'tenant']);
        $admin = User::factory()->create();
        $admin->forceFill(['status' => 'active', 'is_platform_admin' => true])->save();
        $admin->tenants()->attach($tenant, ['role_id' => $role->id, 'status' => 'active']);
        $invitee = User::factory()->create();
        $invitation = UserInvitation::create(['tenant_id' => $tenant->id, 'user_id' => $invitee->id, 'role_id' => $role->id, 'invited_by' => $admin->id, 'email' => $invitee->email, 'name' => $invitee->name, 'token_hash' => hash('sha256', 'old'), 'expires_at' => now()->addDay(), 'accepted_at' => now()]);
        $this->actingAs($admin)->withSession(['active_tenant' => $tenant->public_id])->post(route('invitations.resend', ['invitation' => $invitation->public_id]))->assertStatus(422);
    }

    public function test_invitation_resend_limit_blocks_the_fourth_attempt_without_mutating_state(): void
    {
        Notification::fake();
        $role = Role::factory()->create(['name' => 'tenant_admin']);
        $permission = Permission::factory()->create(['name' => 'users.create']);
        $role->permissions()->attach($permission);
        $tenant = Tenant::factory()->create();
        $admin = User::factory()->active()->create();
        $admin->tenants()->attach($tenant, ['role_id' => $role->id, 'status' => 'active']);
        $invitee = User::factory()->invited()->create();
        $invitee->tenants()->attach($tenant, ['role_id' => $role->id, 'status' => 'inactive']);
        $invitation = UserInvitation::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $invitee->id, 'role_id' => $role->id]);
        $key = "taggo:invitation-resend:{$tenant->id}:invitation:{$invitation->id}";
        $userKey = "taggo:invitation-resend:{$tenant->id}:user:{$admin->id}";
        RateLimiter::clear($key);
        RateLimiter::clear($userKey);

        for ($attempt = 0; $attempt < 3; $attempt++) {
            $this->actingAs($admin)->withSession(['active_tenant' => $tenant->public_id])->post(route('invitations.resend', $invitation))->assertRedirect();
        }
        $before = $invitation->fresh();
        $this->actingAs($admin)->withSession(['active_tenant' => $tenant->public_id])->post(route('invitations.resend', $invitation))->assertSessionHasErrors('invitation');

        $this->assertSame($before->token_hash, $invitation->fresh()->token_hash);
        $this->assertSame($before->expires_at->toIso8601String(), $invitation->fresh()->expires_at->toIso8601String());
        Notification::assertSentOnDemand(UserInvitationNotification::class, 3);
    }
}
