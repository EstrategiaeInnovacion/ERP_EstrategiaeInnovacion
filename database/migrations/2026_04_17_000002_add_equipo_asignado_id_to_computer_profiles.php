<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('computer_profiles', function (Blueprint $table) {
            $table->unsignedBigInteger('equipo_asignado_id')->nullable()->after('last_ticket_id');
            $table->foreign('equipo_asignado_id')
                  ->references('id')
                  ->on('it_equipos_asignados')
                  ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('computer_profiles', function (Blueprint $table) {
            $table->dropForeign(['equipo_asignado_id']);
            $table->dropColumn('equipo_asignado_id');
        });
    }
};
