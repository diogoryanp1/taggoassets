<?php

namespace Tests\Feature\Tenancy;

use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class TenantSpoofingTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_tenant_selection_rejects_a_tenant_the_user_does_not_belong_to(): void
    {
        ['user' => $user] = $this->tenantContext();
        ['tenant' => $otherTenant] = $this->tenantContext([], 'second_role');

        $this->actingAs($user)->put(route('tenant.update'), ['tenant' => $otherTenant->public_id])->assertNotFound();
    }

    public function test_inactive_or_missing_active_tenant_cannot_access_tenant_routes(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->tenantContext(['organizations.view']);
        $tenant->update(['status' => 'inactive']);

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get(route('locations.index'))->assertForbidden();
        $this->actingAs($user)->get(route('locations.index'))->assertForbidden();
    }

    public function test_removed_or_blocked_member_cannot_spoof_an_active_tenant(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->tenantContext(['organizations.view']);
        $user->tenants()->detach($tenant);
        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get(route('locations.index'))->assertForbidden();

        ['tenant' => $blockedTenant, 'user' => $blockedUser] = $this->tenantContext(['organizations.view'], 'blocked_member');
        $blockedUser->forceFill(['status' => 'blocked'])->save();
        $blockedUser->refresh();
        $this->actingAs($blockedUser)->withSession(['active_tenant' => $blockedTenant->public_id])->get(route('locations.index', ['tenant_id' => $tenant->id]))->assertForbidden();
    }

    public function test_legitimate_tenant_switch_uses_only_the_public_id_from_backend_validation(): void
    {
        ['tenant' => $first, 'user' => $user, 'role' => $role] = $this->tenantContext();
        $second = Tenant::factory()->create();
        $user->tenants()->attach($second, ['role_id' => $role->id, 'status' => 'active']);

        $this->actingAs($user)->withSession(['active_tenant' => $first->public_id])->put(route('tenant.update'), ['tenant' => $second->public_id, 'tenant_id' => $first->id])->assertRedirect()->assertSessionHas('active_tenant', $second->public_id);
    }
}
