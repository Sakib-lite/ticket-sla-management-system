<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Queue\SerializesModels;

abstract class TicketNotification extends Notification implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public readonly Ticket $ticket) {}

    abstract public function kind(): string;

    abstract protected function subject(User $notifiable): string;

    abstract protected function message(User $notifiable): string;

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[{$this->ticket->ticket_number}] {$this->subject($notifiable)}")
            ->greeting("Hello {$notifiable->name},")
            ->line($this->message($notifiable))
            ->line("Ticket {$this->ticket->ticket_number}: {$this->ticket->subject}")
            ->action('View ticket', $this->ticketUrl());
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'kind' => $this->kind(),
            'ticket_id' => $this->ticket->id,
            'ticket_number' => $this->ticket->ticket_number,
            'ticket_subject' => $this->ticket->subject,
            'message' => $this->message($notifiable),
        ];
    }

    protected function ticketUrl(): string
    {
        return rtrim(config('app.frontend_url'), '/')."/dashboard/tickets/{$this->ticket->id}";
    }
}
