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
        Schema::create('koperasi_members', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // --- Mirror of the destination tables -------------------------
            // Column names deliberately match users / alumni_profiles so the
            // eventual merge is a straight mapping with no renaming.
            $table->string('name');                 // -> users.name
            $table->string('email')->unique();      // -> users.email
            $table->string('nim')->unique();        // -> alumni_profiles.nim
            $table->string('program_studi');        // -> alumni_profiles.program_studi
            $table->integer('tahun_masuk');         // -> alumni_profiles.tahun_masuk
            $table->integer('tahun_lulus');         // -> alumni_profiles.tahun_lulus
            $table->string('whatsapp');             // -> alumni_profiles.whatsapp

            // --- Registration state ---------------------------------------
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('catatan_admin')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->foreignUuid('approved_by')->nullable()->constrained('users')->nullOnDelete();

            // --- Merge trail ----------------------------------------------
            $table->foreignUuid('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('migrated_at')->nullable();

            $table->timestamps();

            // Lookup for the activation step (NIM + nama)
            $table->index(['nim', 'name']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('koperasi_members');
    }
};
