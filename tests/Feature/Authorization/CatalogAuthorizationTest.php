<?php

namespace Tests\Feature\Authorization;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class CatalogAuthorizationTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_catalog_routes_require_catalog_permissions(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->tenantContext([], 'no_catalog_access');

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->get(route('catalog.types.index'))->assertForbidden();
    }
}
