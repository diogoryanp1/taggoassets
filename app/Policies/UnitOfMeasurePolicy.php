<?php

namespace App\Policies;

use App\Domain\Assets\Models\UnitOfMeasure;
use App\Domain\Tenancy\CurrentTenant;
use App\Models\User;

class UnitOfMeasurePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(app(CurrentTenant::class)->require(), 'units_of_measure.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(app(CurrentTenant::class)->require(), 'units_of_measure.manage');
    }

    public function update(User $user, UnitOfMeasure $unit): bool
    {
        return ! $unit->is_system && $unit->tenant_id === app(CurrentTenant::class)->id() && $this->create($user);
    }

    public function deactivate(User $user, UnitOfMeasure $unit): bool
    {
        return $this->update($user, $unit);
    }
}
