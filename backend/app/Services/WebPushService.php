<?php

namespace App\Services;

use App\Models\PushSubscription;
use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

class WebPushService
{
    protected WebPush $webPush;

    public function __construct()
    {
        $this->webPush = new WebPush([
            'VAPID' => [
                'subject' => config('app.url'),
                'publicKey' => env('VAPID_PUBLIC_KEY'),
                'privateKey' => env('VAPID_PRIVATE_KEY'),
            ],
        ]);
    }

    public function sendToUser($userId, string $title, string $body, string $icon = '/logo_unmul.png', ?string $actionUrl = null): void
    {
        $subscriptions = PushSubscription::where('user_id', $userId)->get();

        if ($subscriptions->isEmpty()) {
            return;
        }

        $payload = json_encode([
            'title' => $title,
            'body' => $body,
            'icon' => $icon,
            'badge' => $icon,
            'data' => [
                'action_url' => $actionUrl,
            ],
        ]);

        foreach ($subscriptions as $sub) {
            $subscription = Subscription::create([
                'endpoint' => $sub->endpoint,
                'publicKey' => $sub->p256dh,
                'authToken' => $sub->auth,
            ]);

            $this->webPush->queueNotification($subscription, $payload);
        }

        foreach ($this->webPush->flush() as $report) {
            if (! $report->isSuccess()) {
                $this->removeSubscription($report->getEndpoint());
            }
        }
    }

    protected function removeSubscription(string $endpoint): void
    {
        PushSubscription::where('endpoint', $endpoint)->delete();
    }
}
