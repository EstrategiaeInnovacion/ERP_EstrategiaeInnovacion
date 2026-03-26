<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_proyectos', function (Blueprint $table) {
            $table->id();
            $table->string('empresa');
            $table->foreignId('categoria_id')->constrained('legal_categorias')->cascadeOnDelete();
            $table->text('consulta');
            $table->text('resultado');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_proyectos');
    }
};
