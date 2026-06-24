<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AlumniVerificationNotification extends Notification
{
    use Queueable;

    protected $status;

    protected $reason;

    public function __construct(string $status, ?string $reason = null)
    {
        $this->status = $status;
        $this->reason = $reason;
    }

    public function via($notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject('Status Verifikasi Akun Alumni FEB Unmul')
            ->greeting('Halo '.$notifiable->name.',');

        if ($this->status === 'verified') {
            $message->line('Selamat! Akun alumni Anda telah berhasil diverifikasi oleh Admin.')
                ->line('Anda sekarang dapat mengajukan pembukaan toko, membeli produk, atau mempublikasikan penawaran jasa Anda.')
                ->action('Buka Marketplace', url('/'));
        } else {
            $message->line('Mohon maaf, pengajuan verifikasi akun alumni Anda belum dapat disetujui.')
                ->line('Alasan penolakan: '.($this->reason ?? 'Data tidak sesuai.'))
                ->line('Silakan periksa kembali profil Anda dan ajukan verifikasi ulang.');
        }

        return $message;
    }

    public function toArray($notifiable): array
    {
        $title = $this->status === 'verified' ? 'Akun Terverifikasi' : 'Verifikasi Akun Ditolak';
        $msg = $this->status === 'verified'
            ? 'Selamat! Pengajuan verifikasi akun alumni Anda telah disetujui oleh admin.'
            : 'Mohon maaf, pengajuan verifikasi akun alumni Anda ditolak. Alasan: '.($this->reason ?? '-');

        return [
            'title' => $title,
            'message' => $msg,
            'status' => $this->status,
            'type' => 'alumni_verification',
            'category' => 'buyer',
            'action_url' => '/buyer/home',
        ];
    }
}
