<?php

namespace App\Policies;

class AssetTypePolicy extends TenantCatalogPolicy
{
    protected function permissionPrefix(): string
    {
        return 'asset_types';
    }
}
