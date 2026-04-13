<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dias_festivos', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 255);
            $table->date('fecha');
            $table->enum('tipo', ['festivo', 'inhabil'])->default('festivo');
            $table->boolean('es_anual')->default(false);
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->boolean('notificacion_enviada')->default(false);
            $table->timestamp('notificacion_enviada_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dias_festivos');
    }
};
