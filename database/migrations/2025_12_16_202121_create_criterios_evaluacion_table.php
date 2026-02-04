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
        Schema::create('criterios_evaluacion', function (Blueprint $table) {
            $table->id();
            $table->string('area'); // 'Logistica', 'RH', etc.
            $table->string('criterio');
            $table->text('descripcion')->nullable();
            $table->integer('peso'); // Porcentaje (0-100)
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('criterios_evaluacion');
    }
}; 