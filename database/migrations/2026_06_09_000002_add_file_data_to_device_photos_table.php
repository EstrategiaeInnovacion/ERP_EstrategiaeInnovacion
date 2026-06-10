<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::connection('activos')->hasTable('device_photos')) {
            return;
        }

        if (Schema::connection('activos')->hasColumn('device_photos', 'file_data')) {
            return;
        }

        $conn = DB::connection('activos');
        if ($conn->getDriverName() === 'mysql') {
            $conn->statement('ALTER TABLE device_photos ADD COLUMN file_data LONGBLOB NULL AFTER file_path');
            $conn->statement('ALTER TABLE device_photos ADD COLUMN mime_type VARCHAR(100) NULL AFTER file_data');
        } else {
            Schema::connection('activos')->table('device_photos', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->binary('file_data')->nullable();
                $table->string('mime_type', 100)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (!Schema::connection('activos')->hasTable('device_photos')) {
            return;
        }

        $conn = DB::connection('activos');
        if ($conn->getDriverName() === 'mysql') {
            $conn->statement('ALTER TABLE device_photos DROP COLUMN IF EXISTS file_data');
            $conn->statement('ALTER TABLE device_photos DROP COLUMN IF EXISTS mime_type');
        } else {
            Schema::connection('activos')->table('device_photos', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->dropColumn(['file_data', 'mime_type']);
            });
        }
    }
};
