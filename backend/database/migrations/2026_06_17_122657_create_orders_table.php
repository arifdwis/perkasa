<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('order_number')->unique();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignUuid('store_id')->constrained('stores')->restrictOnDelete();
            $table->string('nama_penerima');
            $table->string('telepon_penerima');
            $table->text('alamat_penerima');
            $table->string('wilayah_antar')->nullable();
            $table->decimal('subtotal', 15, 2);
            $table->decimal('biaya_antar', 15, 2);
            $table->decimal('total', 15, 2);
            $table->string('payment_method')->default('COD');
            $table->string('status')->default('menunggu_konfirmasi');
            $table->text('catatan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
