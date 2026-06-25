<?php

namespace App\Policies;

use App\Domain\Tenancy\CurrentTenant;
use App\Models\User;

class UserPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(app(CurrentTenant::class)->require(), 'users.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(app(CurrentTenant::class)->require(), 'users.create');
    }

    public function update(User $user, User $subject): bool
    {
        return $user->id !== $subject->id && $user->hasPermission(app(CurrentTenant::class)->require(), 'users.update');
    }

    public function block(User $user, User $subject): bool
    {
        return $user->id !== $subject->id && $user->hasPermission(app(CurrentTenant::class)->require(), 'users.block');
    }
}
