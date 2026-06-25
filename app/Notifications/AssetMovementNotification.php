<?php

namespace App\Notifications;

use App\Domain\Assets\Models\AssetMovement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AssetMovementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly AssetMovement $movement, public readonly string $event)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $movement = $this->movement->loadMissing('asset');

        return (new MailMessage)
            ->subject('Movimentação patrimonial: '.$movement->movementType()->label())
            ->line($this->message())
            ->line('Ativo: '.$movement->asset->asset_number.' - '.$movement->asset->description)
            ->line('Status: '.$movement->movementStatus()->label());
    }

    private function message(): string
    {
        return match ($this->event) {
            'pending' => 'A movimentação está aguardando aprovação.',
            'approved' => 'A movimentação foi aprovada.',
            'rejected' => 'A movimentação foi rejeitada.',
            'upcoming_return' => 'O retorno patrimonial está próximo do vencimento.',
            'overdue' => 'Há retorno patrimonial vencido.',
            default => 'A movimentação patrimonial foi atualizada.',
        };
    }
}
