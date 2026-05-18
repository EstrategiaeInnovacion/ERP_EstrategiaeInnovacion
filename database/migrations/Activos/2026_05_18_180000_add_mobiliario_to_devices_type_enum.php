<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE devices MODIFY COLUMN type ENUM('computer', 'peripheral', 'printer', 'mobiliario', 'other') NOT NULL");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("UPDATE devices SET type = 'other' WHERE type = 'mobiliario'");
        DB::statement("ALTER TABLE devices MODIFY COLUMN type ENUM('computer', 'peripheral', 'printer', 'other') NOT NULL");
    }
};