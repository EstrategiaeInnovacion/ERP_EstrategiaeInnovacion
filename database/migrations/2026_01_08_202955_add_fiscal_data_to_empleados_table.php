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
        Schema::table('empleados', function (Blueprint $table) {
            // Agregamos los campos fiscales después de la dirección
            $table->string('curp', 18)->nullable()->after('direccion');
            $table->string('rfc', 13)->nullable()->after('curp');
            $table->string('nss', 15)->nullable()->after('rfc');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn(['curp', 'rfc', 'nss']);
        });
    }
};