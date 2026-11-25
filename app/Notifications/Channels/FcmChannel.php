<?php

namespace App\Notifications\Channels;

use App\Services\FcmService;
use Illuminate\Notifications\Notification;

class FcmChannel
{
    public function __construct(private FcmService $fcm)
    {
    }

    public function send(object $notifiable, Notification $notification): void
    {
        if (! method_exists($notification, 'toFcm')) {
            return;
        }

        $payload = $notification->toFcm($notifiable);
        if (! is_array($payload)) {
            return;
        }

        $title = (string) ($payload['title'] ?? 'ARENASPORTS');
        $body = (string) ($payload['body'] ?? $payload['message'] ?? '');
        $data = is_array($payload['data'] ?? null) ? $payload['data'] : [];

        if (empty($data)) {
            $data = array_filter([
                'type' => $payload['type'] ?? null,
                'url' => $payload['url'] ?? null,
            ], fn ($v) => $v !== null);
        }

        $this->fcm->sendToUser($notifiable, $title, $body, $data);
    }
}
