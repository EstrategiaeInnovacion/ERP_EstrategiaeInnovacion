<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluacion_ventanas', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->comment('Ej: 2026 | Enero - Junio');
            $table->date('fecha_apertura')->comment('Fecha en que se abre la ventana de evaluaciones');
            $table->date('fecha_cierre')->comment('Fecha en que se cierra la ventana de evaluaciones');
            $table->boolean('activo')->default(true);
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluacion_ventanas');
    }
};
