<?php

namespace Tests\Feature\Authorization;

use App\Domain\Identity\Models\Role;
use App\Domain\Identity\Models\UserSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class RolePermissionTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_explicit_permissions_control_direct_read_routes(): void
    {
        $routes = [
            ['users.view', 'users.index', []],
            ['organizations.view', 'units.index', []],
            ['organizations.view', 'locations.index', []],
            ['audit.view', 'audit.index', ['action' => 'audit.check']],
            ['users.view', 'invitations.index', []],
            ['assets.view', 'documents.index', []],
        ];

        foreach ($routes as [$permission, $route, $parameters]) {
            ['tenant' => $tenant, 'user' => $allowed] = $this->tenantContext([$permission], 'allowed_'.str_replace('.', '_', $permission).'_'.$route);
            ['tenant' => $deniedTenant, 'user' => $denied] = $this->tenantContext([], 'denied_'.str_replace('.', '_', $permission).'_'.$route);

            $this->actingAs($allowed)->withSession(['active_tenant' => $tenant->public_id])->get(route($route, $parameters))->assertOk();
            $this->actingAs($denied)->withSession(['active_tenant' => $deniedTenant->public_id])->get(route($route, $parameters))->assertForbidden();
        }
    }

    public function test_user_creation_requires_users_create_permission_even_when_route_is_called_directly(): void
    {
        ['tenant' => $tenant, 'user' => $allowed] = $this->tenantContext(['users.create'], 'tenant_admin');
        ['tenant' => $deniedTenant, 'user' => $denied] = $this->tenantContext([], 'viewer');
        Role::factory()->create(['name' => 'member']);

        $payload = ['name' => 'New user', 'email' => 'new-user@example.test', 'password' => 'NewSecurePassword!123', 'password_confirmation' => 'NewSecurePassword!123', 'role' => 'member'];
        $this->actingAs($denied)->withSession(['active_tenant' => $deniedTenant->public_id])->post(route('users.store'), $payload)->assertForbidden();
        $this->actingAs($allowed)->withSession(['active_tenant' => $tenant->public_id])->post(route('users.store'), $payload)->assertRedirect(route('users.index'));
    }

    public function test_authorized_block_persists_status_and_revokes_the_subject_sessions(): void
    {
        ['tenant' => $tenant, 'user' => $admin, 'role' => $role] = $this->tenantContext(['users.block'], 'tenant_admin');
        $subject = User::factory()->active()->create();
        $subject->tenants()->attach($tenant, ['role_id' => $role->id, 'status' => 'active']);
        $session = UserSession::factory()->create(['user_id' => $subject->id]);

        $this->actingAs($admin)->withSession(['active_tenant' => $tenant->public_id])->patch(route('users.block', $subject))->assertRedirect();
        $this->assertSame('blocked', $subject->fresh()->status);
        $this->assertNotNull($session->fresh()->revoked_at);
    }
}
