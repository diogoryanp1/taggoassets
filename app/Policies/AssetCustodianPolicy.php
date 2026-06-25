<?php

namespace App\Policies;

class AssetCustodianPolicy extends TenantCatalogPolicy
{
    protected function permissionPrefix(): string
    {
        return 'asset_custodians';
    }
}
