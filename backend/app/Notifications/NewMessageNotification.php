<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewMessageNotification extends Notification
{
    use Queueable;

    protected $conversation;
    protected $message;

    public function __construct($conversation, $message)
    {
        $this->conversation = $conversation;
        $this->message = $message;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $senderName = $this->message->user?->name ?? 'Pengguna';
        $storeName = $this->conversation->store?->name ?? 'Toko';

        return [
            'title' => 'Pesan Baru',
            'message' => $senderName.': '.mb_strimwidth($this->message->text, 0, 100, '...'),
            'conversation_id' => $this->conversation->id,
            'store_name' => $storeName,
            'type' => 'new_message',
            'category' => $notifiable->id === ($this->conversation->store->alumniProfile->user_id ?? null) ? 'seller' : 'buyer',
            'action_url' => $notifiable->id === ($this->conversation->store->alumniProfile->user_id ?? null)
                ? '/seller/chat/'.$this->conversation->id
                : '/buyer/chat/'.$this->conversation->id,
        ];
    }
}
