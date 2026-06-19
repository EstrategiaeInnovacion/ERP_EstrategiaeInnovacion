<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE capacitaciones ADD COLUMN thumbnail_contenido LONGBLOB NULL AFTER thumbnail_path');
            DB::statement('ALTER TABLE capacitaciones ADD COLUMN thumbnail_mime_type VARCHAR(255) NULL AFTER thumbnail_contenido');
        } else {
            Schema::table('capacitaciones', function (Blueprint $table) {
                $table->binary('thumbnail_contenido')->nullable();
                $table->string('thumbnail_mime_type', 255)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE capacitaciones DROP COLUMN IF EXISTS thumbnail_contenido');
            DB::statement('ALTER TABLE capacitaciones DROP COLUMN IF EXISTS thumbnail_mime_type');
        } else {
            Schema::table('capacitaciones', function (Blueprint $table) {
                $table->dropColumn(['thumbnail_contenido', 'thumbnail_mime_type']);
            });
        }
    }
};
