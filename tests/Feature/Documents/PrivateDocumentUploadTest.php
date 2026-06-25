<?php

namespace Tests\Feature\Documents;

use App\Domain\Documents\Models\PrivateDocument;
use App\Domain\Organizations\Models\OrganizationalUnit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class PrivateDocumentUploadTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_authorized_user_uploads_a_private_pdf_with_server_derived_ownership(): void
    {
        Storage::fake('private');
        ['tenant' => $tenant, 'user' => $user, 'unit' => $unit] = $this->tenantContext(['assets.create']);
        $file = UploadedFile::fake()->create('relatorio.pdf', 20, 'application/pdf');

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->post(route('documents.store'), ['file' => $file, 'organizational_unit' => $unit->public_id, 'tenant_id' => 999])->assertRedirect(route('documents.index'));

        $document = PrivateDocument::firstOrFail();
        $this->assertSame($tenant->id, $document->tenant_id);
        $this->assertSame($user->id, $document->uploaded_by);
        $this->assertSame(hash_file('sha256', Storage::disk('private')->path($document->stored_name)), $document->sha256);
        $this->assertStringStartsWith('tenants/'.$tenant->public_id.'/documents/', $document->stored_name);
        Storage::disk('private')->assertExists($document->stored_name);
    }

    public function test_upload_rejects_unapproved_mime_type_and_unassigned_unit(): void
    {
        Storage::fake('private');
        ['tenant' => $tenant, 'user' => $user] = $this->tenantContext(['assets.create']);
        $otherUnit = OrganizationalUnit::factory()->create(['tenant_id' => $tenant->id]);

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->post(route('documents.store'), ['file' => UploadedFile::fake()->create('script.php', 4, 'application/x-php')])->assertSessionHasErrors('file');
        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->post(route('documents.store'), ['file' => UploadedFile::fake()->create('safe.pdf', 4, 'application/pdf'), 'organizational_unit' => $otherUnit->public_id])->assertForbidden();
        $this->assertDatabaseCount('private_documents', 0);
    }
}
