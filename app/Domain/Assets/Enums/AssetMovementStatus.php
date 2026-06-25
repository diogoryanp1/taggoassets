<?php

namespace App\Domain\Assets\Enums;

enum AssetMovementStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Completed = 'completed';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Rascunho',
            self::PendingApproval => 'Aguardando aprovação',
            self::Approved => 'Aprovada',
            self::Rejected => 'Rejeitada',
            self::Completed => 'Concluída',
            self::Cancelled => 'Cancelada',
        };
    }
}
