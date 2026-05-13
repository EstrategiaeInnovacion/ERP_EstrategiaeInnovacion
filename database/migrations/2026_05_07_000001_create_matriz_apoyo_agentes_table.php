<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matriz_apoyo_agentes', function (Blueprint $table) {
            $table->id();
            $table->string('agente_aduanal');
            $table->string('razon_social')->nullable();
            $table->string('patente', 50)->nullable();
            $table->tinyInteger('calificacion')->nullable();
            $table->string('responsabilidad', 120);
            $table->string('nombre')->nullable();
            $table->string('correo_electronico')->nullable();
            $table->string('telefono', 50)->nullable();
            $table->text('comentarios')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matriz_apoyo_agentes');
    }
};
