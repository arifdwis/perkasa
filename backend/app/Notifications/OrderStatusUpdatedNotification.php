<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class OrderStatusUpdatedNotification extends Notification
{
    use Queueable;

    protected $order;

    protected $oldStatus;

    protected $newStatus;

    public function __construct($order, string $oldStatus, string $newStatus)
    {
        $this->order = $order;
        $this->oldStatus = $oldStatus;
        $this->newStatus = $newStatus;
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    private function getStatusLabel(string $status): string
    {
        switch ($status) {
            case 'menunggu_konfirmasi': return 'Menunggu Konfirmasi';
            case 'diproses': return 'Diproses';
            case 'dalam_pengantaran': return 'Dalam Pengantaran';
            case 'selesai': return 'Selesai';
            case 'dibatalkan': return 'Dibatalkan';
            default: return $status;
        }
    }

    public function toArray($notifiable): array
    {
        $statusLabel = $this->getStatusLabel($this->newStatus);

        return [
            'title' => 'Status Pesanan Diperbarui',
            'message' => 'Pesanan Anda #'.$this->order->order_number.' kini berstatus: '.$statusLabel.'.',
            'order_id' => $this->order->id,
            'status' => $this->newStatus,
            'type' => 'order_status_updated',
            'action_url' => '/orders/'.$this->order->id,
        ];
    }
}
