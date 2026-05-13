<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('pedimentos_operaciones');
        Schema::dropIfExists('logistica_correos_cc');
    }

    public function down(): void
    {
        Schema::create('logistica_correos_cc', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('email')->unique();
            $table->enum('tipo', ['cliente', 'interno', 'otro'])->default('interno');
            $table->string('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });

        Schema::create('pedimentos_operaciones', function (Blueprint $table) {
            $table->id();
            $table->string('no_pedimento');
            $table->string('clave');
            $table->unsignedBigInteger('operacion_logistica_id');
            $table->enum('estado_pago', ['pendiente', 'pagado'])->default('pendiente');
            $table->date('fecha_pago')->nullable();
            $table->timestamps();
        });
    }
};
