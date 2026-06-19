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
        Schema::create('solicitudes_vacaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->onDelete('cascade');
            $table->date('fecha_inicio');
            $table->date('fecha_fin');
            $table->integer('dias_solicitados');
            $table->string('motivo')->nullable();
            
            $table->enum('estado', ['pendiente', 'aprobado_supervisor', 'aprobado', 'rechazado'])->default('pendiente');
            
            $table->foreignId('supervisor_id')->nullable()->constrained('empleados')->onDelete('set null');
            $table->timestamp('aprobado_supervisor_at')->nullable();
            $table->text('comentarios_supervisor')->nullable();
            
            $table->foreignId('rh_aprobador_id')->nullable()->constrained('users')->onDelete('set null');
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
        Schema::dropIfExists('solicitudes_vacaciones');
    }
};
