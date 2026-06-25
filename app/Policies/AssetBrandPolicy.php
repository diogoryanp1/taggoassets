<?php

namespace App\Policies;

class AssetBrandPolicy extends TenantCatalogPolicy
{
    protected function permissionPrefix(): string
    {
        return 'asset_brands';
    }
}
