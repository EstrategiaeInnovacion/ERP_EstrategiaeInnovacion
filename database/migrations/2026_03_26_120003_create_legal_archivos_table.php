<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('legal_archivos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('proyecto_id')->constrained('legal_proyectos')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('tipo')->default('otro'); // excel, pdf, word, imagen, otro
            $table->string('ruta');                   // storage path o URL/ruta externa
            $table->boolean('es_url')->default(false); // true = ruta/URL externa, false = archivo subido
            $table->string('mime_type')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('legal_archivos');
    }
};
