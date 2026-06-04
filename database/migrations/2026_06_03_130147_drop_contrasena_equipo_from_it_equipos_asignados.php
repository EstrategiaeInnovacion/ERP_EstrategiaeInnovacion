<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('it_equipos_asignados', function (Blueprint $table) {
            $table->dropColumn('contrasena_equipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('it_equipos_asignados', function (Blueprint $table) {
            $table->text('contrasena_equipo')->nullable();
        });
    }
};
