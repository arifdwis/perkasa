<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Store;
use App\Services\WebPushService;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendDailyReportNotifications extends Command
{
    protected $signature = 'perkasa:daily-report';
    protected $description = 'Kirim push notification laporan penjualan harian ke setiap seller.';

    public function handle(): int
    {
        $yesterday = Carbon::yesterday();
        $push = app(WebPushService::class);
        $sent = 0;

        Store::where('status', 'active')->chunk(50, function ($stores) use ($yesterday, $push, &$sent) {
            foreach ($stores as $store) {
                $orders = Order::where('store_id', $store->id)
                    ->whereDate('created_at', $yesterday)
                    ->get();

                if ($orders->isEmpty()) continue;

                $totalRevenue = $orders->sum('total');
                $orderCount = $orders->count();
                $completed = $orders->where('status', 'selesai')->count();

                $push->sendToUser(
                    $store->alumniProfile->user_id,
                    'Laporan Harian ' . $yesterday->translatedFormat('d M Y'),
                    $orderCount . ' pesanan · Rp' . number_format($totalRevenue, 0, ',', '.') . ' · ' . $completed . ' selesai',
                    '/logo_unmul.png',
                    '/seller/daily-report?date=' . $yesterday->toDateString()
                );

                $sent++;
            }
        });

        $this->info("Laporan harian dikirim ke {$sent} toko.");
        return 0;
    }
}
