<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matriz_seguimiento', function (Blueprint $table) {
            $table->string('cliente_operacion')->nullable()->after('proveedor_cliente');
        });
    }

    public function down(): void
    {
        Schema::table('matriz_seguimiento', function (Blueprint $table) {
            $table->dropColumn('cliente_operacion');
        });
    }
};
