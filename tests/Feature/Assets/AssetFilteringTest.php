<?php

namespace Tests\Feature\Assets;

use App\Domain\Assets\Models\Asset;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class AssetFilteringTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_asset_listing_requires_filter_and_preserves_query_string(): void
    {
        ['tenant' => $tenant, 'user' => $user, 'unit' => $unit] = $this->tenantContext(['assets.view'], 'viewer');
        Asset::factory()->create(['tenant_id' => $tenant->id, 'organizational_unit_id' => $unit->id, 'asset_number' => 'PAT-2026-000777']);

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get(route('assets.index'))->assertSee('Informe um termo');
        $this->get(route('assets.index', ['asset_number' => '000777']))->assertOk()->assertSee('PAT-2026-000777');
    }
}
