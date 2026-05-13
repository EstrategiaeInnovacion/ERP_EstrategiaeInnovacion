<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matriz_seguimiento_comentarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('matriz_seguimiento_id')->constrained('matriz_seguimiento')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comentario');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matriz_seguimiento_comentarios');
    }
};
