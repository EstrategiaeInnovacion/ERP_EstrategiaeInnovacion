<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (\Illuminate\Support\Facades\Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE capacitaciones ADD COLUMN archivo_contenido LONGBLOB NULL AFTER archivo_path');
            DB::statement('ALTER TABLE capacitaciones ADD COLUMN archivo_mime_type VARCHAR(255) NULL AFTER archivo_contenido');
            DB::statement('ALTER TABLE capacitacion_adjuntos ADD COLUMN archivo_contenido LONGBLOB NULL AFTER archivo_path');
            DB::statement('ALTER TABLE capacitacion_adjuntos ADD COLUMN archivo_mime_type VARCHAR(255) NULL AFTER archivo_contenido');
        } else {
            \Illuminate\Support\Facades\Schema::table('capacitaciones', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->binary('archivo_contenido')->nullable();
                $table->string('archivo_mime_type', 255)->nullable();
            });
            \Illuminate\Support\Facades\Schema::table('capacitacion_adjuntos', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->binary('archivo_contenido')->nullable();
                $table->string('archivo_mime_type', 255)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (\Illuminate\Support\Facades\Schema::getConnection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE capacitaciones DROP COLUMN IF EXISTS archivo_contenido');
            DB::statement('ALTER TABLE capacitaciones DROP COLUMN IF EXISTS archivo_mime_type');
            DB::statement('ALTER TABLE capacitacion_adjuntos DROP COLUMN IF EXISTS archivo_contenido');
            DB::statement('ALTER TABLE capacitacion_adjuntos DROP COLUMN IF EXISTS archivo_mime_type');
        } else {
            \Illuminate\Support\Facades\Schema::table('capacitaciones', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->dropColumn(['archivo_contenido', 'archivo_mime_type']);
            });
            \Illuminate\Support\Facades\Schema::table('capacitacion_adjuntos', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->dropColumn(['archivo_contenido', 'archivo_mime_type']);
            });
        }
    }
};
