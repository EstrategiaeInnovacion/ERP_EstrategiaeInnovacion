<?php

use Illuminate\Database\Migrations\Migration; // <--- ESTA ES LA CORRECCIÓN (Antes decía Illuminate\Database\Eloquent\...)
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            // Agregamos la columna booleana, por defecto 0 (Empleado de nómina)
            // Se agrega después de 'posicion' para mantener orden
            $table->boolean('es_practicante')->default(false)->after('posicion');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn('es_practicante');
        });
    }
};