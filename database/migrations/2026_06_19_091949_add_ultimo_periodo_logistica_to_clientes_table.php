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
        Schema::table('clientes', function (Blueprint $table) {
            // Formato 'YYYY-MM'. Cuando el periodo actual no coincide con éste,
            // el consecutivo de Referencia se reinicia en 01 para ese cliente.
            $table->string('ultimo_periodo_logistica', 7)->nullable()->after('ultimo_consecutivo_logistica');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('clientes', function (Blueprint $table) {
            $table->dropColumn('ultimo_periodo_logistica');
        });
    }
};
