<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('aviso_asistencias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->onDelete('cascade');
            $table->foreignId('enviado_por')->constrained('users')->onDelete('cascade');
            
            $table->string('tipo'); // retardos, faltas, general
            $table->text('mensaje')->nullable();
            $table->string('periodo')->nullable(); // Ej: "Marzo 2026"
            $table->integer('cantidad_incidencias')->default(0);
            
            $table->boolean('leido')->default(false);
            $table->timestamp('leido_at')->nullable();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aviso_asistencias');
    }
};
