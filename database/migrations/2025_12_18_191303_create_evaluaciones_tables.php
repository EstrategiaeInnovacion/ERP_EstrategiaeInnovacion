<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->onDelete('cascade'); // El Evaluado
            $table->foreignId('evaluador_id')->constrained('users'); // Quien califica
            $table->string('periodo'); 
            $table->decimal('promedio_final', 5, 2)->nullable();
            $table->text('comentarios_generales')->nullable();
            $table->integer('edit_count')->default(0); 
            // Eliminamos fecha_firma_empleado ya que no se usará
            $table->timestamps();

            // CAMBIO CRÍTICO: Permitimos múltiples evaluaciones al mismo empleado, 
            // siempre y cuando vengan de evaluadores distintos.
            $table->unique(['empleado_id', 'evaluador_id', 'periodo'], 'eval_unica_par');
        });

        Schema::create('evaluacion_detalles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('evaluacion_id')->constrained('evaluaciones')->onDelete('cascade');
            $table->foreignId('criterio_id')->constrained('criterios_evaluacion');
            $table->decimal('calificacion', 5, 2); 
            $table->text('observaciones')->nullable(); 
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluacion_detalles');
        Schema::dropIfExists('evaluaciones');
    }
};