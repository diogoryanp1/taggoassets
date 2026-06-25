<?php

namespace App\Domain\Assets\Enums;

enum AssetMovementDocumentType: string
{
    case GeneratedTerm = 'generated_term';
    case SignedTerm = 'signed_term';
    case Authorization = 'authorization';
    case Receipt = 'receipt';
    case Photo = 'photo';
    case SupportingDocument = 'supporting_document';

    public function label(): string
    {
        return match ($this) {
            self::GeneratedTerm => 'Termo gerado',
            self::SignedTerm => 'Termo assinado',
            self::Authorization => 'Autorização',
            self::Receipt => 'Comprovante',
            self::Photo => 'Fotografia',
            self::SupportingDocument => 'Documento complementar',
        };
    }
}
