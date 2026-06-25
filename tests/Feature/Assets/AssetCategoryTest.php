<?php

namespace Tests\Feature\Assets;

use App\Domain\Assets\Models\AssetCategory;
use App\Domain\Assets\Services\AssetCategoryHierarchy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Concerns\CreatesTenantContext;
use Tests\TestCase;

class AssetCategoryTest extends TestCase
{
    use CreatesTenantContext;
    use RefreshDatabase;

    public function test_category_creation_uses_current_tenant_and_ignores_spoofed_tenant_id(): void
    {
        ['tenant' => $tenant, 'user' => $user] = $this->tenantContext(['asset_categories.create'], 'tenant_admin');
        ['tenant' => $otherTenant] = $this->tenantContext([], 'other_category_role');

        $this->actingAs($user)->withSession(['active_tenant' => $tenant->public_id])->post(route('catalog.categories.store'), ['name' => 'Tecnologia', 'code' => 'TI', 'tenant_id' => $otherTenant->id])->assertRedirect(route('catalog.categories.index'));
        $this->assertDatabaseHas('asset_categories', ['tenant_id' => $tenant->id, 'name' => 'Tecnologia', 'code' => 'TI']);
        $this->assertDatabaseHas('audit_logs', ['tenant_id' => $tenant->id, 'action' => 'asset_category.created']);
    }

    public function test_category_hierarchy_rejects_self_descendant_and_other_tenant_parent(): void
    {
        ['tenant' => $tenant] = $this->tenantContext();
        ['tenant' => $otherTenant] = $this->tenantContext([], 'other_category_role');
        $root = AssetCategory::forceCreate(['tenant_id' => $tenant->id, 'name' => 'Root', 'name_normalized' => 'root', 'is_active' => true]);
        $child = AssetCategory::forceCreate(['tenant_id' => $tenant->id, 'parent_id' => $root->id, 'name' => 'Child', 'name_normalized' => 'child', 'is_active' => true]);
        $other = AssetCategory::forceCreate(['tenant_id' => $otherTenant->id, 'name' => 'Other', 'name_normalized' => 'other', 'is_active' => true]);
        $hierarchy = app(AssetCategoryHierarchy::class);

        $this->assertFalse($hierarchy->acceptsParent($root, $root));
        $this->assertFalse($hierarchy->acceptsParent($root, $child));
        $this->assertFalse($hierarchy->acceptsParent($root, $other));
    }
}
