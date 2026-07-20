<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        \DB::statement('ALTER TABLE cart_items ADD INDEX cart_items_cart_id_index (cart_id)');
        \DB::statement('ALTER TABLE cart_items DROP INDEX cart_items_cart_id_product_id_unique');
        \DB::statement('ALTER TABLE cart_items ADD UNIQUE INDEX cart_items_unique (cart_id, product_id, product_variant_id)');
    }

    public function down(): void
    {
        \DB::statement('ALTER TABLE cart_items ADD INDEX cart_items_cart_id_index (cart_id)');
        \DB::statement('ALTER TABLE cart_items DROP INDEX cart_items_unique');
        \DB::statement('ALTER TABLE cart_items ADD UNIQUE INDEX cart_items_cart_id_product_id_unique (cart_id, product_id)');
    }
};
