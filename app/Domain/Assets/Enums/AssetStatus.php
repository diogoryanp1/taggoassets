<?php

namespace App\Domain\Assets\Enums;

enum AssetStatus: string
{
    case Draft = 'draft';
    case Active = 'active';
    case InTransfer = 'in_transfer';
    case UnderMaintenance = 'under_maintenance';
    case Loaned = 'loaned';
    case Missing = 'missing';
    case Damaged = 'damaged';
    case Inactive = 'inactive';
    case WrittenOff = 'written_off';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho', self::Active => 'Ativo', self::InTransfer => 'Em transferência', self::UnderMaintenance => 'Em manutenção', self::Loaned => 'Emprestado', self::Missing => 'Não localizado', self::Damaged => 'Danificado', self::Inactive => 'Inativo', self::WrittenOff => 'Baixado',
        };
    }
}
