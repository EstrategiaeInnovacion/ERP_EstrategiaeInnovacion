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
        Schema::table('matriz_seguimiento', function (Blueprint $table) {
            $table->string('naviera')->nullable()->after('transporte');
            $table->string('buque')->nullable()->after('naviera');
            $table->string('carga_tipo')->nullable()->after('buque');
            $table->string('no_contenedor')->nullable()->after('carga_tipo');
            $table->string('tipo_contenedor')->nullable()->after('no_contenedor');
        });
    }

    public function down(): void
    {
        Schema::table('matriz_seguimiento', function (Blueprint $table) {
            $table->dropColumn(['naviera', 'buque', 'carga_tipo', 'no_contenedor', 'tipo_contenedor']);
        });
    }
};
