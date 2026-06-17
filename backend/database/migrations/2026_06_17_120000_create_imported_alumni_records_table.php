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
        Schema::create('imported_alumni_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('nim')->unique();
            $table->string('name');
            $table->string('program_studi');
            $table->integer('tahun_masuk');
            $table->integer('tahun_lulus');
            $table->string('email')->unique();
            $table->string('whatsapp');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('imported_alumni_records');
    }
};
