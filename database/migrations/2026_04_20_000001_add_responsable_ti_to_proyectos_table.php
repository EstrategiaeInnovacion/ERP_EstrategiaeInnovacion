<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('proyecto_responsable_ti', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('proyectos')->onDelete('cascade');
            $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
            $table->unique(['proyecto_id', 'usuario_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('proyecto_responsable_ti');
    }
};