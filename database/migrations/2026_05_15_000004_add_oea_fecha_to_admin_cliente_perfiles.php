<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_cliente_perfiles', function (Blueprint $table) {
            $table->date('oea_fecha')->nullable()->after('empresa_certificada_oea');
        });
    }

    public function down(): void
    {
        Schema::table('admin_cliente_perfiles', function (Blueprint $table) {
            $table->dropColumn('oea_fecha');
        });
    }
};
