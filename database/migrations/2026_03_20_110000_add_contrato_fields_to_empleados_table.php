<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->date('fecha_inicio_contrato')->nullable()->after('fecha_ingreso');
            $table->date('fecha_fin_contrato')->nullable()->after('fecha_inicio_contrato');
            $table->string('tipo_contrato')->nullable()->after('fecha_fin_contrato');
        });
    }

    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn(['fecha_inicio_contrato', 'fecha_fin_contrato', 'tipo_contrato']);
        });
    }
};
