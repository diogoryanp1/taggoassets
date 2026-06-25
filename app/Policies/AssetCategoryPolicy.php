<?php

namespace App\Policies;

class AssetCategoryPolicy extends TenantCatalogPolicy
{
    protected function permissionPrefix(): string
    {
        return 'asset_categories';
    }
}
