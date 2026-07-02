<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('product_type')->default('regular')->after('is_featured');
            $table->dateTime('pre_order_deadline')->nullable()->after('product_type');
            $table->date('pre_order_estimated_ship')->nullable()->after('pre_order_deadline');
            $table->integer('pre_order_min_qty')->default(1)->after('pre_order_estimated_ship');
            $table->integer('pre_order_max_qty')->default(10)->after('pre_order_min_qty');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['product_type', 'pre_order_deadline', 'pre_order_estimated_ship', 'pre_order_min_qty', 'pre_order_max_qty']);
        });
    }
};
