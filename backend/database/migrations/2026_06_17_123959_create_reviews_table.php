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
        Schema::create('reviews', function (Blueprint $blueprint) {
            $blueprint->uuid('id')->primary();
            $blueprint->foreignUuid('user_id')->constrained('users')->cascadeOnDelete();
            $blueprint->foreignUuid('order_item_id')->nullable()->unique()->constrained('order_items')->cascadeOnDelete();
            $blueprint->foreignUuid('store_id')->constrained('stores')->cascadeOnDelete();

            // Polymorphic reviewable (Product or Service)
            $blueprint->uuid('reviewable_id');
            $blueprint->string('reviewable_type');

            $blueprint->unsignedTinyInteger('rating'); // 1 to 5
            $blueprint->text('comment')->nullable();

            $blueprint->text('reply')->nullable(); // seller reply
            $blueprint->timestamp('replied_at')->nullable();

            $blueprint->timestamps();

            // Indexes for faster lookups
            $blueprint->index(['reviewable_id', 'reviewable_type']);
            $blueprint->index('store_id');
            $blueprint->index('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
