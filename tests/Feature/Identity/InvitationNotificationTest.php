<?php

namespace Tests\Feature\Identity;

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Models\UserInvitation;
use App\Notifications\UserInvitationNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class InvitationNotificationTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_invitation_notification_is_queued_after_commit_and_database_keeps_only_hash(): void
    {
        Notification::fake();
        ['tenant' => $tenant, 'user' => $admin] = $this->tenantContext(['users.create'], 'tenant_admin');
        $role = Role::factory()->create(['name' => 'viewer']);

        $this->actingAs($admin)->withSession(['active_tenant' => $tenant->public_id])->post(route('invitations.store'), ['name' => 'Notification recipient', 'email' => 'notification@example.test', 'role' => $role->name])->assertRedirect();

        $invitation = UserInvitation::query()->where('email', 'notification@example.test')->firstOrFail();
        $this->assertSame(64, strlen($invitation->token_hash));
        $this->assertDatabaseMissing('user_invitations', ['token_hash' => 'notification@example.test']);
        $this->assertArrayNotHasKey('token', AuditLog::query()->latest()->firstOrFail()->new_values);
        Notification::assertSentOnDemand(UserInvitationNotification::class, function (UserInvitationNotification $notification, array $channels, object $notifiable): bool {
            $this->assertInstanceOf(ShouldQueue::class, $notification);
            $this->assertTrue($notification->afterCommit);

            return $channels === ['mail'] && $notifiable->routeNotificationFor('mail') === 'notification@example.test';
        });
    }
}
