<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // LONGBLOB soporta hasta 4GB; necesario para archivos de hasta 20 MB
        DB::statement('ALTER TABLE legal_archivos ADD COLUMN contenido LONGBLOB NULL AFTER mime_type');

        Schema::table('legal_archivos', function (Blueprint $table) {
            $table->string('ruta')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('legal_archivos', function (Blueprint $table) {
            $table->dropColumn('contenido');
            $table->string('ruta')->nullable(false)->change();
        });
    }
};
