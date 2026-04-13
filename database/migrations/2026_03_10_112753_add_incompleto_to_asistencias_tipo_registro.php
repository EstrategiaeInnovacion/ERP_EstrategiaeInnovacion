<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Cambiar tipo_registro de enum a string para soportar nuevos valores
     * como 'incompleto' sin necesidad de migraciones adicionales.
     */
    public function up(): void
    {
        // En lugar de renombrar (que puede causar problemas en algunos DBMS),
        // creamos una nueva columna, copiamos datos y eliminamos la antigua
        Schema::table('asistencias', function (Blueprint $table) {
            $table->string('tipo_registro_nuevo', 50)->default('asistencia')->nullable()->after('salida');
        });

        // Copiar datos de la columna antigua a la nueva
        DB::statement('UPDATE asistencias SET tipo_registro_nuevo = tipo_registro');

        // Eliminar la columna antigua
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropColumn('tipo_registro');
        });

        // Renombrar la nueva columna al nombre original
        Schema::table('asistencias', function (Blueprint $table) {
            $table->renameColumn('tipo_registro_nuevo', 'tipo_registro');
        });
    }

    public function down(): void
    {
        // Revertir a enum creando una nueva columna, copiando datos y eliminando la actual
        Schema::table('asistencias', function (Blueprint $table) {
            $table->enum('tipo_registro_antiguo', ['asistencia', 'falta', 'vacaciones', 'incapacidad', 'permiso', 'descanso', 'incompleto'], 'asistencia')
                ->nullable()
                ->after('salida');
        });

        // Copiar datos de la columna actual a la antigua
        DB::statement('UPDATE asistencias SET tipo_registro_antiguo = tipo_registro');

        // Eliminar la columna actual
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropColumn('tipo_registro');
        });

        // Renombrar la columna antigua al nombre original
        Schema::table('asistencias', function (Blueprint $table) {
            $table->renameColumn('tipo_registro_antiguo', 'tipo_registro');
        });
    }
};
