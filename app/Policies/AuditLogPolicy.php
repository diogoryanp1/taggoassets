<?php

namespace App\Policies;

use App\Domain\Tenancy\CurrentTenant;
use App\Models\User;

class AuditLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(app(CurrentTenant::class)->require(), 'audit.view');
    }
}
