<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class AlumniRegisteredNotification extends Notification
{
    use Queueable;

    public function __construct()
    {
        //
    }

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'title' => 'Registrasi Berhasil',
            'message' => 'Selamat datang di FEB Unmul Marketplace! Akun Anda berhasil terdaftar.',
            'type' => 'registration',
            'action_url' => '/profile',
        ];
    }
}
