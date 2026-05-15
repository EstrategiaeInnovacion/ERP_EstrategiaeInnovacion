<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_cliente_perfiles', function (Blueprint $table) {
            $table->date('iva_eps_fecha')->nullable()->after('iva_eps_modalidad');
        });
    }

    public function down(): void
    {
        Schema::table('admin_cliente_perfiles', function (Blueprint $table) {
            $table->dropColumn('iva_eps_fecha');
        });
    }
};
