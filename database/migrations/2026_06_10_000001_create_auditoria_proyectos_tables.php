<?php
 
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
 
return new class extends Migration
{
    public function up(): void
    {
        // 1. Proyectos de Auditoría
        Schema::create('auditoria_proyectos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('cliente_id'); // Relación con admin_clientes
            $table->string('periodo_fiscal');
            $table->unsignedBigInteger('coordinador_id'); // Relación con users
            $table->unsignedBigInteger('analista_id'); // Relación con users
            $table->integer('cantidad_expedientes')->default(0);
            $table->date('fecha_inicio');
            $table->date('fecha_entrega_estimada');
            $table->string('estatus_general')->default('pendiente');
            $table->integer('fase_actual')->default(1);
            $table->json('fases_config'); // Arreglo JSON de las 8 fases
            $table->decimal('porcentaje_general_aprobado', 5, 2)->default(0.00);
            $table->decimal('porcentaje_general_interno', 5, 2)->default(0.00);
            $table->decimal('porcentaje_general_publicado', 5, 2)->default(0.00);
            $table->string('token_publico', 100)->unique()->nullable();
            $table->string('publico_password')->nullable();
            $table->timestamp('publico_expira_at')->nullable();
            $table->boolean('mostrar_detalle_cliente')->default(false);
            $table->timestamp('ultima_publicacion_at')->nullable();
            $table->unsignedBigInteger('ultima_publicacion_user_id')->nullable(); // Relación con users
            $table->timestamps();
            $table->softDeletes();
 
            // Foreign Keys
            $table->foreign('cliente_id')->references('id')->on('admin_clientes')->onDelete('cascade');
            $table->foreign('coordinador_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('analista_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('ultima_publicacion_user_id')->references('id')->on('users')->onDelete('set null');
        });
 
        // 2. Matriz de Actividades y Subprocesos
        Schema::create('auditoria_actividades', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proyecto_id');
            $table->unsignedBigInteger('padre_id')->nullable(); // Para jerarquía de subprocesos
            $table->integer('orden')->default(0);
            $table->text('actividad');
            $table->unsignedBigInteger('responsable_id')->nullable(); // Relación con users (analista)
            $table->string('responsable', 100)->nullable(); // 'E&I' o nombre del cliente
            $table->date('plazo')->nullable();
            $table->string('estatus_oficial', 50)->default('pendiente');
            $table->integer('porcentaje_oficial')->default(0);
            $table->string('estatus_publicado', 50)->default('pendiente');
            $table->integer('porcentaje_published')->default(0);
            $table->text('comentarios')->nullable();
            $table->boolean('es_proceso_principal')->default(true);
            $table->timestamps();
            $table->softDeletes();
 
            // Foreign Keys
            $table->foreign('proyecto_id')->references('id')->on('auditoria_proyectos')->onDelete('cascade');
            $table->foreign('padre_id')->references('id')->on('auditoria_actividades')->onDelete('cascade');
            $table->foreign('responsable_id')->references('id')->on('users')->onDelete('set null');
        });
 
        // 3. Cambios Propuestos por Analistas
        Schema::create('auditoria_cambios_propuestos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actividad_id')->nullable(); // Null si es un nuevo subproceso sugerido
            $table->unsignedBigInteger('proyecto_id');
            $table->unsignedBigInteger('padre_id')->nullable(); // Por si se sugiere un subproceso nuevo bajo un proceso
            $table->unsignedBigInteger('user_id'); // El analista que propone
            $table->string('tipo_cambio', 50); // 'update_activity', 'create_subprocess'
            $table->string('actividad_nombre_propuesto')->nullable(); // Solo para nuevos subprocesos sugeridos
            $table->string('responsable_propuesto', 100)->nullable(); // Empresa o Cliente
            $table->string('estatus_propuesto', 50)->nullable();
            $table->integer('porcentaje_propuesto')->default(0);
            $table->text('comentario_propuesto')->nullable();
            $table->boolean('comentario_visible_cliente')->default(false);
            $table->string('estatus_revision', 50)->default('pendiente'); // 'borrador', 'pendiente', 'aprobado', 'rechazado', 'ajuste_solicitado'
            $table->text('motivo_rechazo')->nullable();
            $table->unsignedBigInteger('revisado_por')->nullable(); // Relación con users (coordinador)
            $table->timestamp('fecha_revision')->nullable();
            $table->timestamps();
 
            // Foreign Keys
            $table->foreign('actividad_id')->references('id')->on('auditoria_actividades')->onDelete('cascade');
            $table->foreign('proyecto_id')->references('id')->on('auditoria_proyectos')->onDelete('cascade');
            $table->foreign('padre_id')->references('id')->on('auditoria_actividades')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('revisado_por')->references('id')->on('users')->onDelete('set null');
        });
 
        // 4. Comentarios Aprobados de Actividades
        Schema::create('auditoria_comentarios', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actividad_id');
            $table->unsignedBigInteger('user_id');
            $table->text('comentario');
            $table->boolean('visible_cliente')->default(false);
            $table->timestamps();
 
            // Foreign Keys
            $table->foreign('actividad_id')->references('id')->on('auditoria_actividades')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
 
        // 5. Historial de Publicaciones al Cliente
        Schema::create('auditoria_historial_publicaciones', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proyecto_id');
            $table->unsignedBigInteger('user_id'); // Quién publicó
            $table->decimal('avance_publicado', 5, 2);
            $table->integer('fase_publicada');
            $table->json('detalles')->nullable(); // Captura de estado opcional
            $table->timestamps();
 
            // Foreign Keys
            $table->foreign('proyecto_id')->references('id')->on('auditoria_proyectos')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
 
        // 6. Bitácora General de Cambios (Trazabilidad)
        Schema::create('auditoria_bitacora', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('proyecto_id');
            $table->unsignedBigInteger('actividad_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->string('accion', 100);
            $table->string('campo', 100)->nullable();
            $table->text('valor_anterior')->nullable();
            $table->text('valor_nuevo')->nullable();
            $table->text('comentario')->nullable();
            $table->timestamps();
 
            // Foreign Keys
            $table->foreign('proyecto_id')->references('id')->on('auditoria_proyectos')->onDelete('cascade');
            $table->foreign('actividad_id')->references('id')->on('auditoria_actividades')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }
 
    public function down(): void
    {
        Schema::dropIfExists('auditoria_bitacora');
        Schema::dropIfExists('auditoria_historial_publicaciones');
        Schema::dropIfExists('auditoria_comentarios');
        Schema::dropIfExists('auditoria_cambios_propuestos');
        Schema::dropIfExists('auditoria_actividades');
        Schema::dropIfExists('auditoria_proyectos');
    }
};
