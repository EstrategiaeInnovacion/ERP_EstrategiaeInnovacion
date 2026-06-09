<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE capacitaciones ADD COLUMN archivo_contenido LONGBLOB NULL AFTER archivo_path');
        DB::statement('ALTER TABLE capacitaciones ADD COLUMN archivo_mime_type VARCHAR(255) NULL AFTER archivo_contenido');
        DB::statement('ALTER TABLE capacitacion_adjuntos ADD COLUMN archivo_contenido LONGBLOB NULL AFTER archivo_path');
        DB::statement('ALTER TABLE capacitacion_adjuntos ADD COLUMN archivo_mime_type VARCHAR(255) NULL AFTER archivo_contenido');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE capacitaciones DROP COLUMN IF EXISTS archivo_contenido');
        DB::statement('ALTER TABLE capacitaciones DROP COLUMN IF EXISTS archivo_mime_type');
        DB::statement('ALTER TABLE capacitacion_adjuntos DROP COLUMN IF EXISTS archivo_contenido');
        DB::statement('ALTER TABLE capacitacion_adjuntos DROP COLUMN IF EXISTS archivo_mime_type');
    }
};
