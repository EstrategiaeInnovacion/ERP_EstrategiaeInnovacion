<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('matriz_seguimiento', function (Blueprint $table) {
            $table->id();
            $table->string('ref_interna')->nullable();
            $table->string('proveedor_cliente')->nullable();
            $table->string('factura')->nullable();
            $table->enum('impo_ex', ['IMPO', 'EX'])->nullable();
            $table->string('tipo_operacion')->nullable();
            $table->string('transporte')->nullable();
            $table->string('aduana')->nullable();
            $table->string('clave')->nullable();
            $table->string('pedimento')->nullable();
            $table->string('bl_guia')->nullable();
            $table->date('etd')->nullable();
            $table->date('eta')->nullable();
            $table->date('previo')->nullable();
            $table->date('cita_despacho')->nullable();
            $table->date('arribo_planta')->nullable();
            $table->string('status')->nullable();
            $table->string('resultado')->nullable();
            $table->string('target')->nullable();
            $table->text('comentarios')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('matriz_seguimiento');
    }
};
