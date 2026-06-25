<?php

namespace App\Notifications;

use App\Domain\Identity\Models\UserInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(private readonly UserInvitation $invitation, private readonly string $token)
    {
        $this->afterCommit();
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('invitations.accept', ['invitation' => $this->invitation->public_id, 'token' => $this->token]);

        return (new MailMessage)->subject('Convite — Taggo Assets')->greeting("Olá, {$this->invitation->name}")->line("Você foi convidado para {$this->invitation->tenant->name}.")->action('Aceitar convite', $url)->line("O convite expira em {$this->invitation->expires_at->format('d/m/Y H:i')}. Ignore esta mensagem se não reconhecer o convite.");
    }
}
