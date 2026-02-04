<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('empleados', function (Blueprint $table) {
            // Dirección detallada
            $table->string('ciudad')->nullable();
            $table->string('estado_federativo')->nullable(); // 'estado' a veces es reservado
            $table->string('codigo_postal')->nullable();
            $table->string('telefono_casa')->nullable();
            
            // Salud
            $table->text('alergias')->nullable();
            $table->text('enfermedades_cronicas')->nullable();
            
            // Emergencia
            $table->string('contacto_emergencia_nombre')->nullable();
            $table->string('contacto_emergencia_numero')->nullable();
            $table->string('contacto_emergencia_parentesco')->nullable();
        });
    }

    public function down()
    {
        Schema::table('empleados', function (Blueprint $table) {
            $table->dropColumn([
                'ciudad', 'estado_federativo', 'codigo_postal', 'telefono_casa',
                'alergias', 'enfermedades_cronicas',
                'contacto_emergencia_nombre', 'contacto_emergencia_numero', 'contacto_emergencia_parentesco'
            ]);
        });
    }
};
