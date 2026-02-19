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
        Schema::table('capacitaciones', function (Blueprint $table) {
            $table->string('categoria')->nullable()->after('descripcion');
            $table->json('puestos_permitidos')->nullable()->after('categoria');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('capacitaciones', function (Blueprint $table) {
            $table->dropColumn(['categoria', 'puestos_permitidos']);
        });
    }
};
