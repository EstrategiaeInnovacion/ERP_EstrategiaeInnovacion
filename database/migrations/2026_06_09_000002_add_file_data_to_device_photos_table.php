<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::connection('activos')->statement('ALTER TABLE device_photos ADD COLUMN file_data LONGBLOB NULL AFTER file_path');
        DB::connection('activos')->statement('ALTER TABLE device_photos ADD COLUMN mime_type VARCHAR(100) NULL AFTER file_data');
    }

    public function down(): void
    {
        DB::connection('activos')->statement('ALTER TABLE device_photos DROP COLUMN IF EXISTS file_data');
        DB::connection('activos')->statement('ALTER TABLE device_photos DROP COLUMN IF EXISTS mime_type');
    }
};
