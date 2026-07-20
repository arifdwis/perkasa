<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_photos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('review_id')->constrained('reviews')->cascadeOnDelete();
            $table->string('image_path');
            $table->boolean('is_primary')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_photos');
    }
};
