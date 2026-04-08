<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Paso 1: agregar columna y FK de ventana_id
        Schema::table('evaluaciones', function (Blueprint $table) {
            $table->unsignedBigInteger('ventana_id')->nullable()->after('periodo');
            $table->foreign('ventana_id')->references('id')->on('evaluacion_ventanas')->onDelete('set null');
        });

        // Paso 2: agregar el nuevo constraint ANTES de borrar el viejo.
        // MySQL usa eval_unica_par como índice de soporte de la FK empleado_id;
        // si lo borramos primero, falla. Al agregar eval_unica_ventana (que también
        // empieza con empleado_id) primero, MySQL ya tiene soporte para la FK.
        Schema::table('evaluaciones', function (Blueprint $table) {
            $table->unique(['empleado_id', 'evaluador_id', 'ventana_id'], 'eval_unica_ventana');
        });

        // Paso 3: ahora sí se puede borrar el constraint antiguo
        Schema::table('evaluaciones', function (Blueprint $table) {
            $table->dropUnique('eval_unica_par');
        });
    }

    public function down(): void
    {
        Schema::table('evaluaciones', function (Blueprint $table) {
            $table->unique(['empleado_id', 'evaluador_id', 'periodo'], 'eval_unica_par');
        });

        Schema::table('evaluaciones', function (Blueprint $table) {
            $table->dropUnique('eval_unica_ventana');
        });

        Schema::table('evaluaciones', function (Blueprint $table) {
            $table->dropForeign(['ventana_id']);
            $table->dropColumn('ventana_id');
        });
    }
};
