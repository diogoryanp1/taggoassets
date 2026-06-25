<?php

namespace App\Policies;

use App\Domain\Assets\Models\AssetMovement;
use App\Domain\Tenancy\CurrentTenant;
use App\Models\User;

class AssetMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(app(CurrentTenant::class)->require(), 'asset_movements.view');
    }

    public function view(User $user, AssetMovement $movement): bool
    {
        return $movement->tenant_id === app(CurrentTenant::class)->id() && $this->viewAny($user);
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(app(CurrentTenant::class)->require(), 'asset_movements.create');
    }

    public function approve(User $user, AssetMovement $movement): bool
    {
        return $movement->tenant_id === app(CurrentTenant::class)->id() && $user->hasPermission(app(CurrentTenant::class)->require(), 'asset_movements.approve');
    }

    public function reject(User $user, AssetMovement $movement): bool
    {
        return $movement->tenant_id === app(CurrentTenant::class)->id() && $user->hasPermission(app(CurrentTenant::class)->require(), 'asset_movements.reject');
    }

    public function cancel(User $user, AssetMovement $movement): bool
    {
        return $movement->tenant_id === app(CurrentTenant::class)->id() && $user->hasPermission(app(CurrentTenant::class)->require(), 'asset_movements.cancel');
    }

    public function complete(User $user, AssetMovement $movement): bool
    {
        return $movement->tenant_id === app(CurrentTenant::class)->id() && $user->hasPermission(app(CurrentTenant::class)->require(), 'asset_movements.complete');
    }

    public function generateTerm(User $user, AssetMovement $movement): bool
    {
        return $movement->tenant_id === app(CurrentTenant::class)->id() && $user->hasPermission(app(CurrentTenant::class)->require(), 'asset_terms.generate');
    }
}
