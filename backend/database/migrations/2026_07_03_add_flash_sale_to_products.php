<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_flash_sale')->default(false)->after('product_type');
            $table->decimal('flash_sale_price', 15, 2)->nullable()->after('is_flash_sale');
            $table->dateTime('flash_sale_start')->nullable()->after('flash_sale_price');
            $table->dateTime('flash_sale_end')->nullable()->after('flash_sale_start');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['is_flash_sale', 'flash_sale_price', 'flash_sale_start', 'flash_sale_end']);
        });
    }
};
