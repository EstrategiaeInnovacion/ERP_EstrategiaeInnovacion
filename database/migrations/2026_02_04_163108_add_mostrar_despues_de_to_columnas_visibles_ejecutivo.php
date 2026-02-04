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
        Schema::table('columnas_visibles_ejecutivo', function (Blueprint $table) {
            // Columna predeterminada después de la cual mostrar esta columna opcional
            $table->string('mostrar_despues_de', 50)->nullable()->after('orden');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('columnas_visibles_ejecutivo', function (Blueprint $table) {
            $table->dropColumn('mostrar_despues_de');
        });
    }
};
