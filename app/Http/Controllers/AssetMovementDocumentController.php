<?php

namespace App\Http\Controllers;

use App\Domain\Assets\Enums\AssetMovementDocumentType;
use App\Domain\Assets\Models\AssetMovement;
use App\Domain\Assets\Models\AssetMovementDocument;
use App\Domain\Audit\AuditLogger;
use App\Domain\Documents\Models\PrivateDocument;
use App\Domain\Tenancy\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use RuntimeException;

class AssetMovementDocumentController extends Controller
{
    public function store(Request $request, AssetMovement $movement, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        abort_unless($movement->tenant_id === $currentTenant->id(), 404);
        $this->authorize('upload', AssetMovementDocument::class);
        $this->authorize('view', $movement);
        $data = $request->validate([
            'document_type' => ['required', Rule::enum(AssetMovementDocumentType::class), Rule::notIn([AssetMovementDocumentType::GeneratedTerm->value])],
            'file' => ['required', 'file', 'max:'.config('taggo.private_document_max_size_kb', 10240), 'mimetypes:application/pdf,image/png,image/jpeg'],
        ]);
        $tenant = $currentTenant->require();
        $file = $data['file'];
        $extension = match ($file->getMimeType()) {
            'application/pdf' => 'pdf',
            'image/png' => 'png',
            'image/jpeg' => 'jpg',
            default => throw new RuntimeException('Unsupported document MIME type.'),
        };
        $key = 'tenants/'.$tenant->public_id.'/movement-documents/'.now()->format('Y/m').'/'.Str::ulid().'.'.$extension;
        if (! Storage::disk('private')->putFileAs(dirname($key), $file, basename($key))) {
            throw new RuntimeException('Unable to store movement document.');
        }

        try {
            $link = DB::transaction(function () use ($tenant, $movement, $request, $file, $key, $data): AssetMovementDocument {
                $document = PrivateDocument::forceCreate([
                    'tenant_id' => $tenant->id,
                    'organizational_unit_id' => $movement->destination_organizational_unit_id ?? $movement->origin_organizational_unit_id,
                    'uploaded_by' => $request->user()->id,
                    'original_name' => Str::limit(basename($file->getClientOriginalName()), 200, ''),
                    'stored_name' => $key,
                    'mime_type' => $file->getMimeType(),
                    'size_bytes' => $file->getSize(),
                    'sha256' => hash_file('sha256', Storage::disk('private')->path($key)),
                    'disk' => 'private',
                ]);

                return AssetMovementDocument::forceCreate([
                    'tenant_id' => $tenant->id,
                    'asset_movement_id' => $movement->id,
                    'private_document_id' => $document->id,
                    'document_type' => $data['document_type'],
                    'uploaded_by' => $request->user()->id,
                ]);
            });
        } catch (\Throwable $e) {
            Storage::disk('private')->delete($key);
            throw $e;
        }

        $audit->record('asset_movement_document.uploaded', $link, [], ['document_type' => $link->documentType()->value, 'movement' => $movement->public_id]);

        return back()->with('success', 'Documento anexado com sucesso.');
    }

    public function deactivate(AssetMovementDocument $movementDocument, CurrentTenant $currentTenant, AuditLogger $audit): RedirectResponse
    {
        abort_unless($movementDocument->tenant_id === $currentTenant->id(), 404);
        $this->authorize('deactivate', $movementDocument);
        $movementDocument->update(['deactivated_at' => now(), 'deactivated_by' => request()->user()->id]);
        $audit->record('asset_movement_document.deactivated', $movementDocument, [], ['document_type' => $movementDocument->documentType()->value]);

        return back()->with('success', 'Documento inativado no histórico da movimentação.');
    }
}
