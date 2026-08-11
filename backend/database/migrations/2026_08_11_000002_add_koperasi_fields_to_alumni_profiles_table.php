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
        Schema::table('alumni_profiles', function (Blueprint $table) {
            // Koperasi membership lives here from day one so that merging
            // koperasi_members into this table later needs no backfill.
            $table->boolean('is_koperasi_member')->default(false)->after('badge_verified');
            $table->enum('status_koperasi', ['pending', 'approved', 'rejected'])->nullable()->after('is_koperasi_member');
            $table->timestamp('koperasi_approved_at')->nullable()->after('status_koperasi');

            $table->index('is_koperasi_member');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('alumni_profiles', function (Blueprint $table) {
            $table->dropIndex(['is_koperasi_member']);
            $table->dropColumn(['is_koperasi_member', 'status_koperasi', 'koperasi_approved_at']);
        });
    }
};
