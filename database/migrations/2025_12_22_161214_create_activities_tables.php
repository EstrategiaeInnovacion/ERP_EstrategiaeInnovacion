<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        // 1. TABLA PRINCIPAL (ACTIVITIES)
        Schema::create('activities', function (Blueprint $table) {
            $table->id();
            
            // "Firma" del dueño de la actividad
            $table->foreignId('user_id')->constrained('users'); 
            
            // Clasificación
            $table->string('nombre_actividad');
            $table->string('area')->nullable();
            $table->string('tipo_actividad')->nullable();
            $table->string('prioridad')->default('Media');
            
            // Fechas
            $table->date('fecha_inicio')->useCurrent();
            $table->date('fecha_compromiso');
            $table->date('fecha_final')->nullable();
            
            // Métricas
            $table->integer('metrico')->default(1);
            $table->integer('resultado_dias')->nullable();
            $table->decimal('porcentaje', 10, 2)->nullable();
            
            // Estado y NOTAS ACTUALES
            $table->string('estatus')->default('En blanco');
            $table->text('comentarios')->nullable(); // <--- Aquí guardamos la nota actual
            $table->string('evidencia_path')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
        });

        // 2. TABLA DE HISTORIAL (FIRMAS Y AVANCES)
        Schema::create('activity_histories', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('activity_id')->constrained('activities')->onDelete('cascade');
            
            // "Firma" de quien hizo el cambio (Auditoría)
            $table->foreignId('user_id')->constrained('users'); 
            
            // Campos Técnicos (Para el Timeline)
            $table->string('action')->nullable();      // 'created', 'updated', 'comment'
            $table->string('field')->nullable();       // Qué cambió (ej: estatus)
            
            $table->text('old_value')->nullable();     // Valor Antes
            $table->text('new_value')->nullable();     // Valor Después
            
            $table->text('details')->nullable();       // Resumen
            $table->text('comentario')->nullable();    // Comentario específico del historial
            
            $table->timestamps(); // Registra CUÁNDO se firmó/cambió
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_histories');
        Schema::dropIfExists('activities');
    }
};