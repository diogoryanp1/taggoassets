<?php

namespace Tests\Feature\Assets;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class AssetAuthorizationTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_asset_routes_require_permission(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->tenantContext([], 'viewer_without_assets');

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get(route('assets.index'))->assertForbidden();
    }
}
