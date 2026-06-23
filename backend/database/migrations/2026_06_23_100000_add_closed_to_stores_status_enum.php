<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE stores MODIFY COLUMN status ENUM('pending', 'active', 'suspended', 'closed') DEFAULT 'pending'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE stores MODIFY COLUMN status ENUM('pending', 'active', 'suspended') DEFAULT 'pending'");
    }
};
