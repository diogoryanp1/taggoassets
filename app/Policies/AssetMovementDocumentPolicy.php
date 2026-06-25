<?php

namespace App\Policies;

use App\Domain\Assets\Models\AssetMovementDocument;
use App\Domain\Tenancy\CurrentTenant;
use App\Models\User;

class AssetMovementDocumentPolicy
{
    public function view(User $user, AssetMovementDocument $document): bool
    {
        $tenant = app(CurrentTenant::class)->require();

        return $document->tenant_id === $tenant->id && $user->hasPermission($tenant, 'asset_movement_documents.view');
    }

    public function download(User $user, AssetMovementDocument $document): bool
    {
        $tenant = app(CurrentTenant::class)->require();

        return $document->tenant_id === $tenant->id && $user->hasPermission($tenant, 'asset_movement_documents.download');
    }

    public function upload(User $user): bool
    {
        return $user->hasPermission(app(CurrentTenant::class)->require(), 'asset_movement_documents.upload');
    }

    public function deactivate(User $user, AssetMovementDocument $document): bool
    {
        $tenant = app(CurrentTenant::class)->require();

        return $document->tenant_id === $tenant->id && $document->deactivated_at === null && $document->documentType()->value !== 'generated_term' && $user->hasPermission($tenant, 'asset_movement_documents.deactivate');
    }
}
