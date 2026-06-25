<?php

namespace Tests\Feature\Documents;

use App\Domain\Documents\Models\PrivateDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class PrivateDocumentAuthorizationTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_document_from_another_tenant_is_not_exposed_by_public_id_or_numeric_id(): void
    {
        Storage::fake('private');
        ['tenant' => $tenant, 'user' => $user] = $this->tenantContext(['assets.view']);
        ['tenant' => $otherTenant, 'unit' => $otherUnit] = $this->tenantContext(['assets.view'], 'other_role');
        $document = PrivateDocument::factory()->create(['tenant_id' => $otherTenant->id, 'organizational_unit_id' => $otherUnit->id, 'disk' => 'private']);
        Storage::disk('private')->put($document->stored_name, 'document');

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get(route('documents.view', $document->public_id))->assertNotFound();
        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get('/documents/'.$document->id.'/view')->assertNotFound();
    }
}
