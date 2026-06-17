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
        Schema::create('favorites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $table->uuid('favoritable_id');
            $table->string('favoritable_type');
            $table->timestamps();

            // Indexing for high-performance polymorphic lookups
            $table->index(['favoritable_id', 'favoritable_type']);
            
            // Avoid duplicate favorites of the same model by the same user
            $table->unique(['user_id', 'favoritable_id', 'favoritable_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('favorites');
    }
};
