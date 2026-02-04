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
        Schema::table('activities', function (Blueprint $table) {
            // Agregamos horas programadas (nullable por si es una tarea rápida sin horario fijo)
            $table->time('hora_inicio_programada')->nullable()->after('fecha_compromiso');
            $table->time('hora_fin_programada')->nullable()->after('hora_inicio_programada');
        });
    }

    public function down()
    {
        Schema::table('activities', function (Blueprint $table) {
            $table->dropColumn(['hora_inicio_programada', 'hora_fin_programada']);
        });
    }
};
