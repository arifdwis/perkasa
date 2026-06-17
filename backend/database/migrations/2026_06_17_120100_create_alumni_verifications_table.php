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
        Schema::create('alumni_verifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('alumni_profile_id')->constrained('alumni_profiles')->cascadeOnDelete();
            $table->foreignUuid('admin_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('action', ['approve', 'reject', 'suspend']);
            $table->text('reason')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alumni_verifications');
    }
};
