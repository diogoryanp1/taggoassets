<?php

namespace App\Policies;

use App\Domain\Documents\Models\PrivateDocument;
use App\Domain\Tenancy\CurrentTenant;
use App\Models\User;
use Illuminate\Support\Facades\DB;

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
        $isTerm = DB::table('asset_movements')->where('term_document_id', $document->id)->exists();
        $hasMovementLink = DB::table('asset_movement_documents')->where('private_document_id', $document->id)->exists();
        $isMovementDocument = DB::table('asset_movement_documents')->where('private_document_id', $document->id)->whereNull('deactivated_at')->exists();
        if ($hasMovementLink && ! $isMovementDocument) {
            return false;
        }
        $canView = match (true) {
            $isTerm => $user->hasPermission($tenant, 'asset_terms.download'),
            $isMovementDocument => $user->hasPermission($tenant, 'asset_movement_documents.download'),
            default => $user->hasPermission($tenant, 'assets.view'),
        };
        if ($document->tenant_id !== $tenant->id || ! $canView) {
            return false;
        }

        return ! $document->organizational_unit_id || $user->hasPermission($tenant, 'organizations.update') || $user->organizationalUnits()->whereKey($document->organizational_unit_id)->exists();
    }
}
