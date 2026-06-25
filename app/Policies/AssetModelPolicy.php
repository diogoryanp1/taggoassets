<?php

namespace App\Policies;

class AssetModelPolicy extends TenantCatalogPolicy
{
    protected function permissionPrefix(): string
    {
        return 'asset_models';
    }
}
