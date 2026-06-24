<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewOrderNotification extends Notification
{
    use Queueable;

    protected $order;

    public function __construct($order)
    {
        $this->order = $order;
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Pesanan Baru Masuk - FEB Unmul')
            ->greeting('Halo '.$notifiable->name.',')
            ->line('Ada pesanan baru masuk untuk toko Anda!')
            ->line('Nomor Pesanan: '.$this->order->order_number)
            ->line('Total Transaksi: Rp'.number_format($this->order->total, 0, ',', '.'))
            ->line('Metode Pembayaran: COD')
            ->action('Lihat Detail Pesanan', url('/my-store/orders/'.$this->order->id))
            ->line('Silakan segera konfirmasi pesanan ini agar pembeli mendapat informasi terbaru.');
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Pesanan Baru',
            'message' => 'Ada pesanan baru masuk #'.$this->order->order_number.' senilai Rp'.number_format($this->order->total, 0, ',', '.'),
            'order_id' => $this->order->id,
            'type' => 'new_order',
            'category' => 'seller',
            'action_url' => '/seller/orders/'.$this->order->id,
        ];
    }
}
