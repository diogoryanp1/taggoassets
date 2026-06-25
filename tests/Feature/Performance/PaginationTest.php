<?php

namespace Tests\Feature\Performance;

use App\Domain\Documents\Models\PrivateDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_document_listing_normalizes_pagination_bounds(): void
    {
        ['tenant' => $tenant, 'user' => $user, 'unit' => $unit] = $this->tenantContext(['assets.view']);
        PrivateDocument::factory()->count(21)->create(['tenant_id' => $tenant->id, 'organizational_unit_id' => $unit->id]);

        foreach ([[[], 20], [['per_page' => '1'], 1], [['per_page' => '100'], 100], [['per_page' => '101'], 100], [['per_page' => '0'], 1], [['per_page' => '-3'], 1], [['per_page' => 'invalid'], 20]] as [$query, $expected]) {
            $response = $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get(route('documents.index', $query));
            $response->assertOk()->assertViewHas('documents', fn ($documents) => $documents->perPage() === $expected);
        }
    }
}
