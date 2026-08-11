<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL keeps the original raw statements: production has already run
        // them, so the applied result must stay byte-for-byte the same.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE cart_items ADD INDEX cart_items_cart_id_index (cart_id)');
            DB::statement('ALTER TABLE cart_items DROP INDEX cart_items_cart_id_product_id_unique');
            DB::statement('ALTER TABLE cart_items ADD UNIQUE INDEX cart_items_unique (cart_id, product_id, product_variant_id)');

            return;
        }

        // SQLite (test suite) has no ALTER TABLE ... ADD INDEX — go through the
        // schema builder, which emits portable CREATE/DROP INDEX statements.
        Schema::table('cart_items', function (Blueprint $table) {
            $table->index('cart_id', 'cart_items_cart_id_index');
            $table->dropUnique('cart_items_cart_id_product_id_unique');
            $table->unique(['cart_id', 'product_id', 'product_variant_id'], 'cart_items_unique');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE cart_items ADD INDEX cart_items_cart_id_index (cart_id)');
            DB::statement('ALTER TABLE cart_items DROP INDEX cart_items_unique');
            DB::statement('ALTER TABLE cart_items ADD UNIQUE INDEX cart_items_cart_id_product_id_unique (cart_id, product_id)');

            return;
        }

        Schema::table('cart_items', function (Blueprint $table) {
            $table->dropUnique('cart_items_unique');
            $table->dropIndex('cart_items_cart_id_index');
            $table->unique(['cart_id', 'product_id'], 'cart_items_cart_id_product_id_unique');
        });
    }
};
