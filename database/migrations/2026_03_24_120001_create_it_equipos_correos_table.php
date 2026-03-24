<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('it_equipos_correos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('equipo_asignado_id')
                  ->constrained('it_equipos_asignados')
                  ->cascadeOnDelete();
            $table->string('correo');
            $table->text('contrasena_correo')->nullable()->comment('Contraseña cifrada con Crypt');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('it_equipos_correos');
    }
};
