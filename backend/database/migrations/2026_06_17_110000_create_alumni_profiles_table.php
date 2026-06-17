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
        Schema::create('alumni_profiles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('nim')->unique();
            $table->string('program_studi');
            $table->integer('tahun_masuk');
            $table->integer('tahun_lulus');
            $table->string('whatsapp');
            $table->string('domisili')->nullable();
            $table->string('foto_profil')->nullable();
            $table->enum('status_verifikasi', ['pending', 'verified', 'rejected', 'suspended'])->default('pending');
            $table->boolean('badge_verified')->default(false);
            
            // Index for high performance identity search (specified in plan.md)
            $table->index(['program_studi', 'tahun_masuk', 'tahun_lulus']);
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alumni_profiles');
    }
};
