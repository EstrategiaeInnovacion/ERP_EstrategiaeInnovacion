<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Deshabilitar FK checks para evitar conflictos con FKs huérfanas que ya existen
        Schema::disableForeignKeyConstraints();

        if (! Schema::hasTable('proyectos')) {
            Schema::create('proyectos', function (Blueprint $table) {
                $table->id();
                $table->string('nombre');
                $table->text('descripcion')->nullable();
                $table->foreignId('usuario_id')->constrained('users');
                $table->date('fecha_inicio');
                $table->date('fecha_fin');
                $table->date('fecha_fin_real')->nullable();
                $table->boolean('finalizado')->default(false);
                $table->enum('recurrencia', ['semanal', 'quincenal', 'mensual'])->default('mensual');
                $table->text('notas')->nullable();
                $table->boolean('archivado')->default(false);
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('proyecto_usuarios')) {
            Schema::create('proyecto_usuarios', function (Blueprint $table) {
                $table->foreignId('proyecto_id')->constrained('proyectos')->onDelete('cascade');
                $table->foreignId('usuario_id')->constrained('users')->onDelete('cascade');
                $table->primary(['proyecto_id', 'usuario_id']);
            });
        }

        Schema::enableForeignKeyConstraints();
    }

    public function down(): void
    {
        Schema::dropIfExists('proyecto_usuarios');
        Schema::dropIfExists('proyectos');
    }
};
