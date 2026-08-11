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
        Schema::table('koperasi_members', function (Blueprint $table) {
            // Admin-issued activation link. Unlike the 15-minute token from the
            // NIM + nama lookup (which lives in cache), this one is sent over
            // WhatsApp and has to survive for days, so it is persisted.
            $table->string('token_aktivasi', 64)->nullable()->unique()->after('migrated_at');
            $table->timestamp('token_expires_at')->nullable()->after('token_aktivasi');
            $table->timestamp('token_diterbitkan_at')->nullable()->after('token_expires_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('koperasi_members', function (Blueprint $table) {
            $table->dropUnique(['token_aktivasi']);
            $table->dropColumn(['token_aktivasi', 'token_expires_at', 'token_diterbitkan_at']);
        });
    }
};
