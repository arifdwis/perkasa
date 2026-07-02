<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;

class ClosePreOrderProducts extends Command
{
    protected $signature = 'perkasa:close-pre-orders';
    protected $description = 'Auto-disable pre-order products whose deadline has passed.';

    public function handle(): int
    {
        $count = Product::where('product_type', 'pre_order')
            ->where('status', 'active')
            ->where('pre_order_deadline', '<', now())
            ->update(['status' => 'inactive']);

        $this->info("Deactivated {$count} expired pre-order product(s).");

        return 0;
    }
}
