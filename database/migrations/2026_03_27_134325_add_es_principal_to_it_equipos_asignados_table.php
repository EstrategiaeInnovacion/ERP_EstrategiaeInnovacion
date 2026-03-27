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
            $table->boolean('es_principal')->default(true)->after('notas')
                  ->comment('true = equipo principal del usuario, false = equipo secundario / de cliente');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('it_equipos_asignados', function (Blueprint $table) {
            $table->dropColumn('es_principal');
        });
    }
};
