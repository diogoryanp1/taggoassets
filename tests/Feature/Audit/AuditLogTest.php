<?php

namespace Tests\Feature\Audit;

use App\Domain\Audit\AuditLogger;
use App\Domain\Audit\Models\AuditLog;
use App\Domain\Tenancy\CurrentTenant;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_logger_recursively_sanitizes_sensitive_fields(): void
    {
        $tenant = Tenant::create(['name' => 'T', 'slug' => 't']);
        app(CurrentTenant::class)->set($tenant);
        app(AuditLogger::class)->record('test', null, [], ['nested' => ['token' => 'secret', 'safe' => true], 'password' => 'secret']);
        $this->assertSame(['nested' => ['safe' => true]], AuditLog::firstOrFail()->new_values);
    }

    public function test_audit_http_filters_are_scoped_and_keep_payloads_out_of_the_listing(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->tenantContext(['audit.view']);
        ['tenant' => $otherTenant] = $this->tenantContext([], 'other_audit_role');
        $matching = AuditLog::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'action' => 'document.uploaded', 'request_id' => '01J00000000000000000000001', 'old_values' => ['token' => 'secret'], 'new_values' => ['password' => 'secret'], 'created_at' => now()->subMinute()]);
        AuditLog::factory()->create(['tenant_id' => $tenant->id, 'user_id' => $user->id, 'action' => 'user.created', 'created_at' => now()]);
        $other = AuditLog::factory()->create(['tenant_id' => $otherTenant->id, 'action' => 'document.uploaded']);

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get(route('audit.index'))->assertOk()->assertDontSee($matching->action);
        $response = $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get(route('audit.index', ['action' => 'document.uploaded', 'request_id' => $matching->request_id, 'per_page' => 1000]));
        $response->assertOk()->assertSee($matching->action)->assertDontSee($other->public_id)->assertDontSee('secret')->assertViewHas('logs', fn ($logs) => $logs->perPage() === 100);
        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get(route('audit.show', $matching))->assertOk()->assertSee('secret');
        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get('/audit/'.$matching->id)->assertNotFound();
        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get(route('audit.show', $other))->assertNotFound();
    }

    public function test_audit_routes_require_permission_and_do_not_offer_mutations(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->tenantContext();
        $log = AuditLog::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get(route('audit.index', ['action' => 'any']))->assertForbidden();
        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->delete('/audit/'.$log->public_id)->assertMethodNotAllowed();
        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->put('/audit/'.$log->public_id, ['action' => 'changed'])->assertMethodNotAllowed();
    }
}
