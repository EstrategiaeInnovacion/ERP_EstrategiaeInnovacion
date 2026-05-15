<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_cliente_perfiles', function (Blueprint $table) {
            $table->string('nom_tipo')->nullable()->after('importa_mercancias_nom');
        });
    }

    public function down(): void
    {
        Schema::table('admin_cliente_perfiles', function (Blueprint $table) {
            $table->dropColumn('nom_tipo');
        });
    }
};
