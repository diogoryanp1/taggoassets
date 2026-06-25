<?php

namespace Tests\Feature\Security;

use App\Domain\Documents\Models\PrivateDocument;
use App\Domain\Identity\Models\UserInvitation;
use App\Domain\Identity\Models\UserSession;
use App\Domain\Organizations\Models\Location;
use App\Domain\Organizations\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class IdorProtectionTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_cross_tenant_resources_return_safe_not_found_on_direct_routes(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->tenantContext(['organizations.update', 'assets.view', 'users.block', 'users.create']);
        ['tenant' => $otherTenant, 'user' => $otherUser, 'unit' => $otherUnit, 'role' => $role] = $this->tenantContext(['organizations.update', 'assets.view', 'users.block', 'users.create'], 'other_tenant_role');
        $location = Location::factory()->create(['tenant_id' => $otherTenant->id, 'organizational_unit_id' => $otherUnit->id]);
        $document = PrivateDocument::factory()->create(['tenant_id' => $otherTenant->id, 'organizational_unit_id' => $otherUnit->id]);
        $session = UserSession::factory()->create(['user_id' => $otherUser->id]);
        $invitation = UserInvitation::factory()->create(['tenant_id' => $otherTenant->id, 'user_id' => $otherUser->id, 'role_id' => $role->id]);

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get(route('locations.edit', $location))->assertNotFound();
        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get(route('documents.view', $document))->assertNotFound();
        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->delete(route('sessions.destroy', $session))->assertNotFound();
        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->post(route('invitations.resend', $invitation))->assertNotFound();
        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->patch(route('users.block', $otherUser))->assertNotFound();
    }

    public function test_numeric_ids_do_not_bind_for_models_with_public_routes(): void
    {
        ['tenant' => $tenant, 'user' => $user, 'unit' => $unit] = $this->tenantContext(['organizations.update', 'assets.view']);
        $location = Location::factory()->create(['tenant_id' => $tenant->id, 'organizational_unit_id' => $unit->id]);
        $unit = OrganizationalUnit::factory()->create(['tenant_id' => $tenant->id]);
        $document = PrivateDocument::factory()->create(['tenant_id' => $tenant->id, 'organizational_unit_id' => $unit->id]);

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get('/locations/'.$location->id.'/edit')->assertNotFound();
        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get('/units/'.$unit->id.'/edit')->assertNotFound();
        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get('/documents/'.$document->id.'/view')->assertNotFound();
    }
}
