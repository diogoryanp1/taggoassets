<?php

namespace App\Http\Controllers;

use App\Domain\Audit\AuditLogger;
use App\Domain\Documents\Models\PrivateDocument;
use App\Domain\Tenancy\CurrentTenant;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PrivateDocumentController extends Controller
{
    public function view(PrivateDocument $document, CurrentTenant $currentTenant, AuditLogger $audit): StreamedResponse
    {
        abort_unless($document->tenant_id === $currentTenant->id(), 404);
        $this->authorize('view', $document);
        abort_unless(in_array($document->mime_type, ['application/pdf', 'image/png', 'image/jpeg'], true) && Storage::disk($document->disk)->exists($document->stored_name), 404);
        $audit->record($this->isMovementDocument($document) ? 'asset_movement_document.viewed' : 'document.viewed', $document);

        return Storage::disk($document->disk)->response($document->stored_name, $document->original_name, ['Content-Type' => $document->mime_type, 'Content-Disposition' => 'inline', 'X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store']);
    }

    public function download(PrivateDocument $document, CurrentTenant $currentTenant, AuditLogger $audit): StreamedResponse
    {
        abort_unless($document->tenant_id === $currentTenant->id(), 404);
        $this->authorize('download', $document);
        abort_unless(Storage::disk($document->disk)->exists($document->stored_name), 404);
        $audit->record($this->isMovementDocument($document) ? 'asset_movement_document.downloaded' : 'document.downloaded', $document);

        return Storage::disk($document->disk)->download($document->stored_name, $document->original_name, ['Content-Type' => $document->mime_type, 'X-Content-Type-Options' => 'nosniff', 'Content-Disposition' => 'attachment']);
    }

    private function isMovementDocument(PrivateDocument $document): bool
    {
        return DB::table('asset_movement_documents')->where('private_document_id', $document->id)->whereNull('deactivated_at')->exists();
    }
}
