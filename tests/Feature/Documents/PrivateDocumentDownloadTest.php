<?php

namespace Tests\Feature\Documents;

use App\Domain\Documents\Models\PrivateDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class PrivateDocumentDownloadTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_download_uses_attachment_and_never_uses_client_storage_path(): void
    {
        Storage::fake('private');
        ['tenant' => $tenant, 'user' => $user, 'unit' => $unit] = $this->tenantContext(['assets.view']);
        $document = PrivateDocument::factory()->create(['tenant_id' => $tenant->id, 'organizational_unit_id' => $unit->id, 'original_name' => 'arquivo seguro.pdf']);
        Storage::disk('private')->put($document->stored_name, 'pdf');

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get(route('documents.download', $document))->assertOk()->assertHeader('x-content-type-options', 'nosniff')->assertHeader('content-disposition');
        $this->assertDatabaseHas('audit_logs', ['action' => 'document.downloaded']);
    }
}
