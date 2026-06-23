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
        Schema::create('solicitudes_permiso', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained()->onDelete('cascade');
            $table->string('tipo_permiso'); // corto, legal, especial
            $table->text('motivo_detalle')->nullable();
            
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            
            $table->string('reposicion_tipo')->nullable(); // tiempo_por_tiempo, descuento_nomina, horas_extra, etc.
            $table->string('comprobante_path')->nullable(); // ruta del archivo
            
            $table->string('estado')->default('pendiente'); // pendiente, aprobado_supervisor, aprobado, rechazado
            
            $table->foreignId('supervisor_id')->nullable()->constrained('empleados')->nullOnDelete();
            $table->timestamp('aprobado_supervisor_at')->nullable();
            $table->text('comentarios_supervisor')->nullable();
            
            $table->foreignId('rh_aprobador_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('aprobado_rh_at')->nullable();
            $table->text('comentarios_rh')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_permiso');
    }
};
