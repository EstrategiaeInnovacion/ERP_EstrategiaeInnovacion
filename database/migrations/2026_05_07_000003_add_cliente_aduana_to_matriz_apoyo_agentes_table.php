<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('matriz_apoyo_agentes', function (Blueprint $table) {
            $table->string('cliente')->nullable()->after('id');
            $table->string('aduana')->nullable()->after('cliente');
        });
    }

    public function down(): void
    {
        Schema::table('matriz_apoyo_agentes', function (Blueprint $table) {
            $table->dropColumn(['cliente', 'aduana']);
        });
    }
};
