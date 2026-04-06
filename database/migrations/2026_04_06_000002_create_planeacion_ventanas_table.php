<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('planeacion_ventanas', function (Blueprint $table) {
            $table->id();
            $table->tinyInteger('dia_semana')->comment('1=Lunes, 2=Martes, ..., 7=Domingo (ISO)');
            $table->time('hora_apertura')->comment('Hora en que se habilita la planeación');
            $table->time('hora_cierre')->comment('Hora en que se deshabilita la planeación');
            $table->boolean('activo')->default(true);
            $table->unsignedBigInteger('creado_por')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('planeacion_ventanas');
    }
};
