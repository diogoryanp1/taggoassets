<?php

namespace App\Policies;

class AssetPolicy extends TenantCatalogPolicy
{
    protected function permissionPrefix(): string
    {
        return 'assets';
    }
}
