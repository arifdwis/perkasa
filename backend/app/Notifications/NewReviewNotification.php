<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class NewReviewNotification extends Notification
{
    use Queueable;

    protected $review;

    protected $itemName;

    protected $slugOrOrderId;

    public function __construct($review, string $itemName, string $slugOrOrderId)
    {
        $this->review = $review;
        $this->itemName = $itemName;
        $this->slugOrOrderId = $slugOrOrderId;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $isProduct = $this->review->reviewable_type === 'App\Models\Product';
        $actionUrl = $isProduct
            ? '/orders/'.$this->slugOrOrderId
            : '/services/'.$this->slugOrOrderId;

        return [
            'title' => 'Ulasan Baru Diterima',
            'message' => 'Anda menerima ulasan bintang '.$this->review->rating.' untuk "'.$this->itemName.'".',
            'review_id' => $this->review->id,
            'rating' => $this->review->rating,
            'type' => 'new_review',
            'action_url' => $actionUrl,
        ];
    }
}
