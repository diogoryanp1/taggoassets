<?php

namespace App\Domain\Assets\Services;

use App\Domain\Assets\Models\AssetCategory;

final class AssetCategoryHierarchy
{
    public function acceptsParent(AssetCategory $category, ?AssetCategory $parent): bool
    {
        if ($parent === null) {
            return true;
        }
        if ($parent->tenant_id !== $category->tenant_id || $parent->id === $category->id) {
            return false;
        }
        while ($parent->parent_id !== null) {
            if ($parent->parent_id === $category->id) {
                return false;
            }
            $parent = $parent->parent()->first();
            if ($parent === null) {
                return false;
            }
        }

        return true;
    }
}
