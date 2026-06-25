<?php

namespace App\Domain\Assets\Enums;

enum AssetMovementType: string
{
    case InitialAssignment = 'initial_assignment';
    case InternalTransfer = 'internal_transfer';
    case LocationChange = 'location_change';
    case CustodianChange = 'custodian_change';
    case TemporaryCheckout = 'temporary_checkout';
    case TemporaryReturn = 'temporary_return';
    case Loan = 'loan';
    case LoanReturn = 'loan_return';
    case MaintenanceDispatch = 'maintenance_dispatch';
    case MaintenanceReturn = 'maintenance_return';
    case InventoryAdjustment = 'inventory_adjustment';

    public function label(): string
    {
        return match ($this) {
            self::InitialAssignment => 'Atribuição inicial',
            self::InternalTransfer => 'Transferência interna',
            self::LocationChange => 'Alteração de localização',
            self::CustodianChange => 'Alteração de responsável',
            self::TemporaryCheckout => 'Saída temporária',
            self::TemporaryReturn => 'Retorno temporário',
            self::Loan => 'Empréstimo',
            self::LoanReturn => 'Retorno de empréstimo',
            self::MaintenanceDispatch => 'Envio para manutenção',
            self::MaintenanceReturn => 'Retorno da manutenção',
            self::InventoryAdjustment => 'Ajuste de inventário',
        };
    }

    public function implemented(): bool
    {
        return in_array($this, [
            self::InitialAssignment,
            self::InternalTransfer,
            self::LocationChange,
            self::CustodianChange,
            self::TemporaryCheckout,
            self::TemporaryReturn,
            self::Loan,
            self::LoanReturn,
        ], true);
    }

    /** @return list<self> */
    public static function operationalCases(): array
    {
        return array_values(array_filter(self::cases(), fn (self $type): bool => $type->implemented()));
    }
}
