<?php

namespace Tests\Feature\Documents;

use App\Domain\Documents\Models\PrivateDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class PrivateDocumentViewTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_authorized_pdf_view_uses_safe_inline_headers_and_is_audited(): void
    {
        Storage::fake('private');
        ['tenant' => $tenant, 'user' => $user, 'unit' => $unit] = $this->tenantContext(['assets.view']);
        $document = PrivateDocument::factory()->create(['tenant_id' => $tenant->id, 'organizational_unit_id' => $unit->id, 'mime_type' => 'application/pdf']);
        Storage::disk('private')->put($document->stored_name, 'pdf');

        $response = $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get(route('documents.view', $document));
        $response->assertOk()->assertHeader('content-type', 'application/pdf')->assertHeader('x-content-type-options', 'nosniff');
        $this->assertStringContainsString('private', (string) $response->headers->get('cache-control'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('cache-control'));
        $this->assertDatabaseHas('audit_logs', ['action' => 'document.viewed', 'tenant_id' => $tenant->id]);
    }
}
