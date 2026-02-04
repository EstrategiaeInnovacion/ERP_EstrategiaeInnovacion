<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Subdepartamentos
        Schema::create('subdepartamentos', function (Blueprint $table) {
            $table->id();
            $table->string('area');
            $table->string('nombre');
            $table->boolean('activo')->default(1);
            $table->timestamps();
            
            $table->unique(['area', 'nombre']);
        });

        // 2. Empleados
        Schema::create('empleados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->onDelete('cascade');
            $table->string('nombre');
            $table->string('correo')->index();
            $table->string('area')->nullable();
            $table->boolean('es_activo')->default(true);
            $table->string('id_empleado', 30)->nullable();
            $table->foreignId('subdepartamento_id')->nullable()->constrained('subdepartamentos')->onDelete('set null');
            $table->string('posicion')->nullable();
            $table->string('telefono')->nullable();
            $table->string('correo_personal')->nullable();
            $table->string('foto_path')->nullable();
            $table->text('direccion')->nullable();
            $table->timestamps();
        });

        // 3. Asistencias
        Schema::create('asistencias', function (Blueprint $table) {
            $table->id();
            $table->string('empleado_no')->nullable()->index();
            $table->string('nombre')->index();
            $table->date('fecha')->index();
            $table->time('entrada')->nullable();
            $table->time('salida')->nullable();
            // JSON válido para checadas
            $table->longText('checadas'); 
            $table->foreignId('empleado_id')->nullable()->constrained('empleados')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asistencias');
        Schema::dropIfExists('empleados');
        Schema::dropIfExists('subdepartamentos');
    }
};