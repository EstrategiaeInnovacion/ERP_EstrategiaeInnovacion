<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('it_equipos_asignados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('uuid_activos')->comment('UUID del equipo en el sistema de activos');
            $table->string('nombre_equipo');
            $table->string('modelo')->nullable();
            $table->string('numero_serie')->nullable();
            $table->unsignedInteger('photo_id')->nullable()->comment('ID de foto en sistema de activos');
            $table->string('nombre_usuario_pc')->comment('Nombre de usuario en la computadora');
            $table->text('contrasena_equipo')->comment('Contraseña cifrada con Crypt');
            $table->text('notas')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('it_equipos_asignados');
    }
};
