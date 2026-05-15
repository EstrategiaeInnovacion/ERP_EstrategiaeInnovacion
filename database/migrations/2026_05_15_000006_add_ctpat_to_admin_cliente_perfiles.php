<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_cliente_perfiles', function (Blueprint $table) {
            $table->boolean('tiene_ctpat')->default(false)->after('iva_eps_fecha');
            $table->date('ctpat_fecha')->nullable()->after('tiene_ctpat');
        });
    }

    public function down(): void
    {
        Schema::table('admin_cliente_perfiles', function (Blueprint $table) {
            $table->dropColumn(['tiene_ctpat', 'ctpat_fecha']);
        });
    }
};
