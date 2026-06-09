<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('logistica_campo_valores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campo_id')
                  ->constrained('logistica_campos_personalizados')
                  ->onDelete('cascade');
            $table->foreignId('matriz_seguimiento_id')
                  ->constrained('matriz_seguimiento')
                  ->onDelete('cascade');
            $table->text('valor')->nullable();
            $table->timestamps();
            $table->unique(['campo_id', 'matriz_seguimiento_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('logistica_campo_valores');
    }
};
