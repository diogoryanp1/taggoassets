<?php

namespace Tests\Feature\Security;

use App\Domain\Audit\Models\AuditLog;
use App\Domain\Organizations\Models\Location;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class PublicRouteBindingTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_bound_resources_reject_numeric_ids_and_unknown_ulids(): void
    {
        ['tenant' => $tenant, 'user' => $user, 'unit' => $unit] = $this->tenantContext(['organizations.update', 'audit.view']);
        $location = Location::factory()->create(['tenant_id' => $tenant->id, 'organizational_unit_id' => $unit->id]);
        $audit = AuditLog::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $user->id]);

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get('/locations/'.$location->id.'/edit')->assertNotFound();
        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get('/audit/'.$audit->id)->assertNotFound();
        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get('/audit/01J00000000000000000000000')->assertNotFound();
    }
}
