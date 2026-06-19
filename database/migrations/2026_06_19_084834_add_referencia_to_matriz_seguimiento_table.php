<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Las operaciones que ya existían se dejan sin "Referencia Interna" (quedan
     * en blanco/—) a propósito: ese campo solo aplica a operaciones nuevas, que
     * lo generan automáticamente al crearse (ver MatrizSeguimientoController).
     */
    public function up(): void
    {
        Schema::table('matriz_seguimiento', function (Blueprint $table) {
            $table->string('referencia', 20)->nullable()->unique()->after('id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('matriz_seguimiento', function (Blueprint $table) {
            $table->dropColumn('referencia');
        });
    }
};
