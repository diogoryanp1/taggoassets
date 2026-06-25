<?php

namespace App\Domain\Assets\Services;

use App\Domain\Assets\Enums\AssetMovementDocumentType;
use App\Domain\Assets\Enums\AssetMovementType;
use App\Domain\Assets\Models\AssetMovement;
use App\Domain\Assets\Models\AssetMovementDocument;
use App\Domain\Documents\Models\PrivateDocument;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

final class AssetTermGenerator
{
    public function generate(AssetMovement $movement, User $user): PrivateDocument
    {
        $movement->load(['tenant', 'asset.category', 'asset.type', 'asset.brand', 'asset.model', 'originUnit', 'destinationUnit', 'originLocation', 'destinationLocation', 'originCustodian', 'destinationCustodian', 'requester', 'approver']);
        $existing = $movement->termDocument()->first();
        if ($existing && Storage::disk($existing->disk)->exists($existing->stored_name)) {
            return $existing;
        }
        $title = match ($movement->movementType()) {
            AssetMovementType::Loan => 'Termo de empréstimo',
            AssetMovementType::InternalTransfer => 'Termo de transferência',
            default => 'Termo de responsabilidade',
        };
        $view = match ($movement->movementType()) {
            AssetMovementType::Loan => 'pdfs.asset-terms.loan',
            AssetMovementType::InternalTransfer => 'pdfs.asset-terms.transfer',
            default => 'pdfs.asset-terms.responsibility',
        };
        $content = Pdf::loadView($view, ['movement' => $movement, 'title' => $title, 'generatedAt' => now()])
            ->setPaper('a4')
            ->output();
        $key = 'tenants/'.$movement->tenant->public_id.'/terms/'.now()->format('Y/m').'/'.$movement->public_id.'.pdf';
        Storage::disk('private')->put($key, $content);

        return DB::transaction(function () use ($movement, $user, $key, $title): PrivateDocument {
            $path = Storage::disk('private')->path($key);
            $document = PrivateDocument::forceCreate([
                'tenant_id' => $movement->tenant_id,
                'organizational_unit_id' => $movement->destination_organizational_unit_id ?? $movement->origin_organizational_unit_id,
                'uploaded_by' => $user->id,
                'original_name' => Str::slug($title.'-'.$movement->public_id).'.pdf',
                'stored_name' => $key,
                'mime_type' => 'application/pdf',
                'size_bytes' => Storage::disk('private')->size($key),
                'sha256' => hash_file('sha256', $path),
                'disk' => 'private',
            ]);
            $movement->forceFill(['term_document_id' => $document->id])->save();
            if (! AssetMovementDocument::query()->where('asset_movement_id', $movement->id)->where('private_document_id', $document->id)->exists()) {
                AssetMovementDocument::forceCreate([
                    'tenant_id' => $movement->tenant_id,
                    'asset_movement_id' => $movement->id,
                    'private_document_id' => $document->id,
                    'document_type' => AssetMovementDocumentType::GeneratedTerm->value,
                    'uploaded_by' => $user->id,
                ]);
            }

            return $document;
        });
    }
}
