<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            // Para marcar tipos de incidencia
            $table->enum('tipo_registro', ['asistencia', 'falta', 'vacaciones', 'incapacidad', 'permiso', 'descanso'])
                  ->default('asistencia')
                  ->after('salida');
            
            // Banderas lógicas
            $table->boolean('es_retardo')->default(false)->after('tipo_registro');
            $table->boolean('es_justificado')->default(false)->after('es_retardo');
            
            // Notas de RH
            $table->text('comentarios')->nullable()->after('es_justificado');
        });
    }

    public function down(): void
    {
        Schema::table('asistencias', function (Blueprint $table) {
            $table->dropColumn(['tipo_registro', 'es_retardo', 'es_justificado', 'comentarios']);
        });
    }
};