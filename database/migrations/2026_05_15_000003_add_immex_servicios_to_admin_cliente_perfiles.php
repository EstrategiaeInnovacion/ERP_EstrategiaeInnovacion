<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_cliente_perfiles', function (Blueprint $table) {
            $table->boolean('tiene_immex_servicios')->default(false)->after('immex_fecha');
            $table->date('immex_servicios_fecha')->nullable()->after('tiene_immex_servicios');
        });
    }

    public function down(): void
    {
        Schema::table('admin_cliente_perfiles', function (Blueprint $table) {
            $table->dropColumn(['tiene_immex_servicios', 'immex_servicios_fecha']);
        });
    }
};
