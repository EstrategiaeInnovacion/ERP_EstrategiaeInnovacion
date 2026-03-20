<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('recordatorios', function (Blueprint $table) {
            $table->id();
            $table->string('tipo');
            $table->string('titulo');
            $table->text('descripcion')->nullable();
            $table->date('fecha_evento');
            $table->integer('dias_anticipacion')->default(7);
            $table->string('tabla_relacionada')->nullable();
            $table->unsignedBigInteger('registro_id')->nullable();
            $table->foreignId('empleado_id')->nullable()->constrained('empleados')->onDelete('cascade');
            $table->foreignId('creado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->boolean('leido')->default(false);
            $table->datetime('leido_at')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
            
            $table->index(['tipo', 'fecha_evento']);
            $table->index(['tabla_relacionada', 'registro_id']);
            $table->index('empleado_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('recordatorios');
    }
};
