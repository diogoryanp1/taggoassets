<?php

namespace App\Policies;

class AssetCustomFieldDefinitionPolicy extends TenantCatalogPolicy
{
    protected function permissionPrefix(): string
    {
        return 'asset_custom_fields';
    }
}
