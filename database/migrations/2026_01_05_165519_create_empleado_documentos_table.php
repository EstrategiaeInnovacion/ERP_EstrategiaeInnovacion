<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('empleado_documentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->onDelete('cascade');
            $table->string('nombre'); // Ej: INE Frontal
            $table->string('categoria'); // Ej: Identificación, Contratos, Certificaciones
            $table->string('ruta_archivo'); // path en storage
            $table->date('fecha_vencimiento')->nullable(); // CLAVE PARA ALERTAS
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('empleado_documentos');
    }
};
