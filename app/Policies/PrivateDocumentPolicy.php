<?php

namespace App\Policies;

use App\Domain\Documents\Models\PrivateDocument;
use App\Domain\Tenancy\CurrentTenant;
use App\Models\User;

class PrivateDocumentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPermission(app(CurrentTenant::class)->require(), 'assets.view');
    }

    public function upload(User $user): bool
    {
        return $user->hasPermission(app(CurrentTenant::class)->require(), 'assets.create');
    }

    public function view(User $user, PrivateDocument $document): bool
    {
        return $this->download($user, $document);
    }

    public function download(User $user, PrivateDocument $document): bool
    {
        $tenant = app(CurrentTenant::class)->require();
        if ($document->tenant_id !== $tenant->id || ! $user->hasPermission($tenant, 'assets.view')) {
            return false;
        }

        return ! $document->organizational_unit_id || $user->hasPermission($tenant, 'organizations.update') || $user->organizationalUnits()->whereKey($document->organizational_unit_id)->exists();
    }
}
