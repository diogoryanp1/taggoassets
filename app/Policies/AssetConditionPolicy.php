<?php

namespace App\Policies;

use App\Domain\Assets\Models\AssetCondition;
use App\Domain\Tenancy\CurrentTenant;
use App\Models\User;

class AssetConditionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(app(CurrentTenant::class)->require(), 'asset_conditions.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(app(CurrentTenant::class)->require(), 'asset_conditions.manage');
    }

    public function update(User $user, AssetCondition $condition): bool
    {
        return ! $condition->is_system && $condition->tenant_id === app(CurrentTenant::class)->id() && $this->create($user);
    }

    public function deactivate(User $user, AssetCondition $condition): bool
    {
        return $this->update($user, $condition);
    }
}
