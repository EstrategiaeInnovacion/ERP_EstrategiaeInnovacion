<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration 
{
    /**
     * Cambiar tipo_registro de enum a string para soportar nuevos valores
     * como 'incompleto' sin necesidad de migraciones adicionales.
     */
    public function up(): void
    {
        // SQLite no soporta ALTER COLUMN, así que recreamos la columna.
        // Primero renombramos, luego creamos la nueva, copiamos datos y eliminamos la vieja.
        Schema::table('asistencias', function (Blueprint $table) {
            $table->renameColumn('tipo_registro', 'tipo_registro_old');
        });

        Schema::table('asistencias', function (Blueprint $table) {
            $table->string('tipo_registro', 50)->default('asistencia')->after('salida');
        });

        // Copiar datos
        DB::statement('UPDATE asistencias SET tipo_registro = tipo_registro_old');

        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropColumn('tipo_registro_old');
        });
    }

    public function down(): void
    {
        // Revertir a enum (no es crítico, solo para rollback)
        Schema::table('asistencias', function (Blueprint $table) {
            $table->renameColumn('tipo_registro', 'tipo_registro_old');
        });

        Schema::table('asistencias', function (Blueprint $table) {
            $table->enum('tipo_registro', ['asistencia', 'falta', 'vacaciones', 'incapacidad', 'permiso', 'descanso', 'incompleto'])
                ->default('asistencia')
                ->after('salida');
        });

        DB::statement('UPDATE asistencias SET tipo_registro = tipo_registro_old');

        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropColumn('tipo_registro_old');
        });
    }
};
