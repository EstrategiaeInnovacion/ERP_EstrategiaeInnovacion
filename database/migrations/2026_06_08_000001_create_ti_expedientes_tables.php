<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hoja de vida del equipo — 1 por ComputerProfile
        Schema::create('ti_expedientes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('computer_profile_id')->constrained('computer_profiles')->cascadeOnDelete();
            $table->enum('estado', ['activo', 'en_reparacion', 'retirado', 'renovado'])->default('activo');
            $table->date('fecha_apertura');
            $table->date('fecha_cierre')->nullable();
            $table->string('motivo_cierre')->nullable();
            $table->text('observaciones')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->unique('computer_profile_id');
        });

        // Registro de cada visita/mantenimiento al equipo
        Schema::create('ti_mantenimientos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('expediente_id')->constrained('ti_expedientes')->cascadeOnDelete();
            $table->foreignId('ticket_id')->nullable()->constrained('tickets')->nullOnDelete();
            $table->string('folio', 20)->unique();
            $table->enum('tipo', ['preventivo', 'correctivo', 'emergente']);
            $table->enum('estado', ['pendiente', 'en_proceso', 'completado', 'cancelado'])->default('pendiente');
            $table->enum('prioridad', ['baja', 'media', 'alta', 'critica'])->default('media');
            $table->dateTime('fecha_inicio')->nullable();
            $table->dateTime('fecha_fin')->nullable();
            $table->foreignId('tecnico_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('usuario_al_momento')->nullable();  // snapshot del usuario que tenía el equipo
            $table->string('area_al_momento')->nullable();     // snapshot del área
            $table->text('descripcion_problema')->nullable();
            $table->json('actividades')->nullable();           // checklist [{categoria, actividad, estado, observaciones}]
            $table->json('hallazgos')->nullable();             // [{descripcion, nivel_riesgo, recomendacion}]
            $table->text('observaciones')->nullable();
            $table->date('proximo_mantenimiento')->nullable();
            $table->enum('frecuencia_siguiente', ['mensual', 'trimestral', 'semestral', 'anual'])->nullable();
            $table->longText('firma_tecnico')->nullable();     // base64 PNG
            $table->longText('firma_usuario')->nullable();    // base64 PNG
            $table->string('nombre_firma_usuario')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->softDeletes();
            $table->timestamps();
        });

        // Archivos adjuntos por mantenimiento
        Schema::create('ti_mantenimiento_archivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mantenimiento_id')->constrained('ti_mantenimientos')->cascadeOnDelete();
            $table->enum('momento', ['antes', 'despues', 'documento']);
            $table->string('ruta');
            $table->string('nombre_original')->nullable();
            $table->string('tipo_mime')->nullable();
            $table->unsignedBigInteger('tamanio_bytes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ti_mantenimiento_archivos');
        Schema::dropIfExists('ti_mantenimientos');
        Schema::dropIfExists('ti_expedientes');
    }
};
