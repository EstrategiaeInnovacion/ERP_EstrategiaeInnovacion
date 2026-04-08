<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Paso 1: agregar columna y FK solo si no existen ya (por ejecución parcial previa)
        if (!Schema::hasColumn('evaluaciones', 'ventana_id')) {
            Schema::table('evaluaciones', function (Blueprint $table) {
                $table->unsignedBigInteger('ventana_id')->nullable()->after('periodo');
                $table->foreign('ventana_id')->references('id')->on('evaluacion_ventanas')->onDelete('set null');
            });
        }

        // Paso 2: agregar nuevo constraint si no existe aún
        $indexes = collect(DB::select("SHOW INDEX FROM evaluaciones WHERE Key_name = 'eval_unica_ventana'"));
        if ($indexes->isEmpty()) {
            Schema::table('evaluaciones', function (Blueprint $table) {
                $table->unique(['empleado_id', 'evaluador_id', 'ventana_id'], 'eval_unica_ventana');
            });
        }

        // Paso 3: borrar el constraint antiguo si todavía existe
        $oldIndex = collect(DB::select("SHOW INDEX FROM evaluaciones WHERE Key_name = 'eval_unica_par'"));
        if ($oldIndex->isNotEmpty()) {
            Schema::table('evaluaciones', function (Blueprint $table) {
                $table->dropUnique('eval_unica_par');
            });
        }
    }

    public function down(): void
    {
        // Restaurar constraint antiguo si no existe
        $oldIndex = collect(DB::select("SHOW INDEX FROM evaluaciones WHERE Key_name = 'eval_unica_par'"));
        if ($oldIndex->isEmpty()) {
            Schema::table('evaluaciones', function (Blueprint $table) {
                $table->unique(['empleado_id', 'evaluador_id', 'periodo'], 'eval_unica_par');
            });
        }

        // Borrar nuevo constraint si existe
        $newIndex = collect(DB::select("SHOW INDEX FROM evaluaciones WHERE Key_name = 'eval_unica_ventana'"));
        if ($newIndex->isNotEmpty()) {
            Schema::table('evaluaciones', function (Blueprint $table) {
                $table->dropUnique('eval_unica_ventana');
            });
        }

        // Borrar columna si existe
        if (Schema::hasColumn('evaluaciones', 'ventana_id')) {
            Schema::table('evaluaciones', function (Blueprint $table) {
                $table->dropForeign(['ventana_id']);
                $table->dropColumn('ventana_id');
            });
        }
    }
};
