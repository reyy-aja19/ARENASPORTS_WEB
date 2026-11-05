<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountActivityNotification extends Notification
{
    use Queueable;

    public function __construct(
        private string $subject,
        private string $message,
        private string $method,
        private string $time
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('ArenaSports - '.$this->subject)
            ->greeting('Halo '.$notifiable->name.'!')
            ->line($this->message)
            ->line('Metode: '.$this->method)
            ->line('Waktu: '.$this->time)
            ->line('Jika aktivitas ini bukan kamu, segera hubungi administrator ArenaSports.');
    }
}
