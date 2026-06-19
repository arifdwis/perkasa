<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class StoreVerificationNotification extends Notification
{
    use Queueable;

    protected $status;

    protected $storeName;

    public function __construct(string $status, string $storeName)
    {
        $this->status = $status;
        $this->storeName = $storeName;
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Status Pengajuan Toko - FEB Unmul')
            ->greeting('Halo '.$notifiable->name.',');

        if ($this->status === 'active') {
            $message->line('Selamat! Toko Anda "'.$this->storeName.'" telah disetujui oleh Admin.')
                ->line('Anda sekarang dapat menambahkan produk, jasa, dan mulai berjualan.')
                ->action('Kelola Toko Saya', url('/my-store'));
        } else {
            $message->line('Mohon maaf, toko Anda "'.$this->storeName.'" saat ini dinonaktifkan / disuspend oleh Admin.')
                ->line('Untuk pertanyaan lebih lanjut, silakan hubungi Admin FEB Unmul Marketplace.');
        }

        return $message;
    }

    public function toArray($notifiable): array
    {
        $title = $this->status === 'active' ? 'Toko Disetujui' : 'Toko Dinonaktifkan';
        $msg = $this->status === 'active'
            ? 'Selamat! Pengajuan Toko "'.$this->storeName.'" Anda telah disetujui.'
            : 'Toko "'.$this->storeName.'" Anda saat ini dinonaktifkan (suspended) oleh admin.';

        return [
            'title' => $title,
            'message' => $msg,
            'status' => $this->status,
            'type' => 'store_verification',
            'action_url' => '/my-store',
        ];
    }
}
