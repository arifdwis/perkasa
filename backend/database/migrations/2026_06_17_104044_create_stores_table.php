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
        Schema::create('stores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('alumni_profile_id')->unique()->constrained('alumni_profiles')->cascadeOnDelete();
            $table->string('name');
            $table->string('logo')->nullable();
            $table->string('banner')->nullable();
            $table->text('description')->nullable();
            $table->string('kategori_usaha');
            $table->string('whatsapp');
            $table->string('kota');
            $table->integer('tahun_berdiri');
            $table->enum('status', ['pending', 'active', 'suspended'])->default('pending');
            $table->enum('delivery_type', ['fixed', 'per_wilayah'])->default('fixed');
            $table->decimal('fixed_delivery_fee', 15, 2)->default(0.00);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stores');
    }
};
