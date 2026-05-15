<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_cliente_perfiles', function (Blueprint $table) {
            $table->date('automotriz_fecha')->nullable()->after('automotriz_deposito_fiscal');
        });
    }

    public function down(): void
    {
        Schema::table('admin_cliente_perfiles', function (Blueprint $table) {
            $table->dropColumn('automotriz_fecha');
        });
    }
};
