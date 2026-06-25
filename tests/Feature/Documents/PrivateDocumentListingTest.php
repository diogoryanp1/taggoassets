<?php

namespace Tests\Feature\Documents;

use App\Domain\Documents\Models\PrivateDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class PrivateDocumentListingTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_listing_is_tenant_scoped_paginated_and_hides_storage_metadata(): void
    {
        ['tenant' => $tenant, 'user' => $user, 'unit' => $unit] = $this->tenantContext(['assets.view']);
        PrivateDocument::factory()->count(25)->create(['tenant_id' => $tenant->id, 'organizational_unit_id' => $unit->id]);
        $response = $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get(route('documents.index', ['per_page' => 1000]));

        $response->assertOk()->assertViewHas('documents', fn ($documents) => $documents->perPage() === 100 && $documents->count() === 25)->assertDontSee('stored_name')->assertDontSee('sha256');
    }
}
