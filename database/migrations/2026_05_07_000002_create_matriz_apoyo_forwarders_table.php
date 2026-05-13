<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matriz_apoyo_forwarders', function (Blueprint $table) {
            $table->id();
            $table->string('cliente');
            $table->string('aduana')->nullable();
            $table->string('razon_social')->nullable();
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
        Schema::dropIfExists('matriz_apoyo_forwarders');
    }
};
