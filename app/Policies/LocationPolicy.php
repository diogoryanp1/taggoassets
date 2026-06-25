<?php

namespace App\Policies;

use App\Domain\Organizations\Models\Location;
use App\Domain\Tenancy\CurrentTenant;
use App\Models\User;

class LocationPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(app(CurrentTenant::class)->require(), 'organizations.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(app(CurrentTenant::class)->require(), 'organizations.create');
    }

    public function view(User $user, Location $location): bool
    {
        return $location->tenant_id === app(CurrentTenant::class)->id() && ($user->hasPermission(app(CurrentTenant::class)->require(), 'organizations.update') || $user->organizationalUnits()->whereKey($location->organizational_unit_id)->exists());
    }

    public function update(User $user, Location $location): bool
    {
        return $location->tenant_id === app(CurrentTenant::class)->id() && $user->hasPermission(app(CurrentTenant::class)->require(), 'organizations.update');
    }
}
