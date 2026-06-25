<?php

namespace App\Policies;

use App\Domain\Organizations\Models\OrganizationalUnit;
use App\Domain\Tenancy\CurrentTenant;
use App\Models\User;

class OrganizationalUnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(app(CurrentTenant::class)->require(), 'organizations.view');
    }

    public function view(User $user, OrganizationalUnit $unit): bool
    {
        return $unit->tenant_id === app(CurrentTenant::class)->id() && ($user->hasPermission(app(CurrentTenant::class)->require(), 'organizations.update') || $user->organizationalUnits()->whereKey($unit->id)->exists());
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(app(CurrentTenant::class)->require(), 'organizations.create');
    }

    public function update(User $user, OrganizationalUnit $unit): bool
    {
        return $unit->tenant_id === app(CurrentTenant::class)->id() && $user->hasPermission(app(CurrentTenant::class)->require(), 'organizations.update');
    }
}
