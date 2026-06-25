<?php

namespace App\Policies;

use App\Domain\Tenancy\CurrentTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

abstract class TenantCatalogPolicy
{
    abstract protected function permissionPrefix(): string;

    public function viewAny(User $user): bool
    {
        return $user->hasPermission(app(CurrentTenant::class)->require(), $this->permissionPrefix().'.view');
    }

    public function create(User $user): bool
    {
        return $user->hasPermission(app(CurrentTenant::class)->require(), $this->permissionPrefix().'.create') || $user->hasPermission(app(CurrentTenant::class)->require(), $this->permissionPrefix().'.manage');
    }

    public function update(User $user, Model $model): bool
    {
        return $model->getAttribute('tenant_id') === app(CurrentTenant::class)->id() && ($user->hasPermission(app(CurrentTenant::class)->require(), $this->permissionPrefix().'.update') || $user->hasPermission(app(CurrentTenant::class)->require(), $this->permissionPrefix().'.manage'));
    }

    public function deactivate(User $user, Model $model): bool
    {
        return $model->getAttribute('tenant_id') === app(CurrentTenant::class)->id() && ($user->hasPermission(app(CurrentTenant::class)->require(), $this->permissionPrefix().'.deactivate') || $user->hasPermission(app(CurrentTenant::class)->require(), $this->permissionPrefix().'.manage'));
    }
}
