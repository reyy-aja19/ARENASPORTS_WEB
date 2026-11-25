<?php

namespace App\Notifications;

use App\Notifications\Channels\FcmChannel;
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
        return ['mail', 'database', FcmChannel::class];
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

    public function toDatabase(object $notifiable): array
    {
        return [
            'title' => $this->subject,
            'message' => $this->message,
            'url' => '/profile',
            'type' => 'account',
        ];
    }

    public function toFcm(object $notifiable): array
    {
        return [
            'title' => $this->subject,
            'body' => $this->message,
            'data' => [
                'type' => 'account',
                'url' => '/profile',
            ],
        ];
    }
}
