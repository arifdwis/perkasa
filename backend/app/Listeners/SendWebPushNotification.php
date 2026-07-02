<?php

namespace App\Listeners;

use App\Services\WebPushService;
use Illuminate\Notifications\Events\NotificationSent;

class SendWebPushNotification
{
    public function handle(NotificationSent $event): void
    {
        if ($event->channel !== 'database') {
            return;
        }

        $data = $event->response;
        if (! is_array($data)) {
            return;
        }

        $webPush = app(WebPushService::class);
        $userId = $event->notifiable->id ?? null;

        if (! $userId) {
            return;
        }

        $title = $data['title'] ?? 'Notifikasi';
        $body = mb_strimwidth($data['message'] ?? '', 0, 150, '...');

        $webPush->sendToUser(
            $userId,
            'FEB Unmul: ' . $title,
            $body,
            '/logo_unmul.png',
            $data['action_url'] ?? null
        );
    }
}
