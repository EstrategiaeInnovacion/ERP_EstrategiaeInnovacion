<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // --- CATÁLOGOS ---

        // 1. Aduanas
        Schema::create('aduanas', function (Blueprint $table) {
            $table->id();
            $table->string('aduana', 2);
            $table->string('seccion', 1)->default('0');
            $table->text('denominacion');
            $table->string('patente')->nullable();
            $table->string('pais')->default('MX');
            $table->timestamps();
            
            $table->index(['aduana', 'seccion']);
        });

        // 2. Agentes Aduanales
        Schema::create('agentes_aduanales', function (Blueprint $table) {
            $table->id();
            $table->string('agente_aduanal')->index();
            $table->timestamps();
        });

        // 3. Transportes
        Schema::create('transportes', function (Blueprint $table) {
            $table->id();
            $table->string('transporte');
            $table->enum('tipo_operacion', ['Aerea', 'Terrestre', 'Maritima', 'Ferrocarril'])->index();
            $table->timestamps();
        });

        // 4. Clientes
        Schema::create('clientes', function (Blueprint $table) {
            $table->id();
            $table->string('cliente')->index();
            $table->longText('correos')->nullable(); // JSON
            $table->string('periodicidad_reporte')->nullable();
            $table->timestamp('fecha_carga_excel')->nullable();
            $table->foreignId('ejecutivo_asignado_id')->nullable()->constrained('empleados')->onDelete('set null');
            $table->timestamps();
        });

        // 5. Correos CC
        Schema::create('logistica_correos_cc', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('email')->unique();
            $table->enum('tipo', ['administrador', 'supervisor', 'notificacion'])->default('notificacion');
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(1);
            $table->timestamps();
        });

        // 6. Pedimentos (Catálogo)
        Schema::create('pedimentos', function (Blueprint $table) {
            $table->id();
            $table->string('categoria')->nullable();
            $table->string('clave', 50)->unique();
            $table->text('descripcion');
            $table->timestamps();
        });

        // 6.1 Incoterms (Catálogo)
        Schema::create('incoterms', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 10)->unique(); // EXW, FOB, CIF, etc.
            $table->string('nombre', 100); // Nombre completo
            $table->text('descripcion')->nullable(); // Descripción detallada
            $table->enum('grupo', ['E', 'F', 'C', 'D']); // Grupo de incoterm
            $table->boolean('aplicable_importacion')->default(true);
            $table->boolean('aplicable_exportacion')->default(true);
            $table->boolean('activo')->default(true);
            $table->integer('orden')->default(0);
            $table->timestamps();
        });

        // 7. Campos Personalizados
        Schema::create('campos_personalizados_matriz', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 100);
            // Tipos: texto, fecha, numero, decimal, descripcion, booleano, selector, multiple, email, telefono, url, moneda
            $table->string('tipo', 30)->default('texto');
            $table->json('opciones')->nullable(); // Para selector y multiple: ["Opción 1", "Opción 2"]
            $table->json('configuracion')->nullable(); // Config adicional: {"min": 0, "max": 100, "decimales": 2, "moneda": "MXN"}
            $table->boolean('requerido')->default(false);
            $table->boolean('activo')->default(1);
            $table->integer('orden')->default(0);
            $table->string('mostrar_despues_de', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('campo_personalizado_ejecutivo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campo_personalizado_id')->constrained('campos_personalizados_matriz')->onDelete('cascade');
            $table->foreignId('empleado_id')->constrained('empleados')->onDelete('cascade');
            $table->integer('orden')->default(0); // Orden personalizado del campo
            $table->timestamps();
            $table->unique(['campo_personalizado_id', 'empleado_id'], 'campo_pers_ejec_unique');
        });

        Schema::create('columnas_visibles_ejecutivo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('empleado_id')->constrained('empleados')->onDelete('cascade');
            $table->string('columna', 50);
            $table->boolean('visible')->default(0);
            $table->integer('orden')->default(0); // Orden personalizado de la columna
            $table->enum('idioma_nombres', ['es', 'en'])->default('es'); // Idioma de los nombres de columnas
            $table->timestamps();
            $table->unique(['empleado_id', 'columna'], 'col_vis_ejec_unique');
        });

        // --- OPERACIONES ---

        // 8. Post Operaciones (definiciones globales)
        Schema::create('post_operaciones', function (Blueprint $table) {
            $table->id();
            $table->string('nombre')->nullable();
            $table->text('descripcion')->nullable();
            $table->unsignedBigInteger('operacion_logistica_id')->nullable(); // Se agrega FK después
            $table->string('no_pedimento')->nullable();
            $table->string('post_operacion')->nullable();
            $table->string('status')->default('Pendiente')->index();
            $table->timestamp('fecha_creacion')->nullable();
            $table->timestamp('fecha_completado')->nullable();
            $table->timestamps();
            
            $table->index('post_operacion');
        });

        // 9. Operaciones Logísticas
        Schema::create('operaciones_logisticas', function (Blueprint $table) {
            $table->id();
            $table->string('folio')->nullable()->unique(); // Folio único de la operación
            $table->string('ejecutivo')->nullable();
            $table->string('operacion')->nullable();
            $table->string('cliente')->nullable();
            $table->string('proveedor_o_cliente')->nullable();
            $table->string('no_factura')->nullable();
            $table->string('tipo_carga', 100)->nullable();
            $table->string('tipo_incoterm', 50)->nullable();
            $table->enum('tipo_operacion_enum', ['Aerea', 'Terrestre', 'Maritima', 'Ferrocarril'])->nullable();
            $table->string('clave')->nullable();
            $table->string('referencia_interna')->nullable();
            $table->string('aduana')->nullable();
            $table->string('agente_aduanal')->nullable();
            $table->string('referencia_aa')->nullable();
            $table->string('no_pedimento')->nullable();
            $table->string('transporte')->nullable();
            $table->string('guia_bl')->nullable();
            $table->string('puerto_salida', 150)->nullable();
            
            // ═══════════════════════════════════════════════════════════════
            // NUEVOS CAMPOS (Opcionales) - Mapeo Excel
            // ═══════════════════════════════════════════════════════════════
            $table->string('in_charge')->nullable();              // In charge - Responsable
            $table->string('proveedor')->nullable();              // Supplier Name - Proveedor
            $table->string('tipo_previo')->nullable();            // MODALIDAD/PREVIO
            $table->date('fecha_etd')->nullable();                // Shipp date (ETD) - Fecha de salida estimada
            $table->date('fecha_zarpe')->nullable();              // Shipp date Zarpe - Fecha real de zarpe
            $table->boolean('pedimento_en_carpeta')->nullable();  // PEDIMENTO EN CARPETA (SI/NO)
            $table->string('referencia_cliente')->nullable();     // REF - Referencia del cliente
            $table->text('mail_subject')->nullable();             // MAIL SUBJECT - Asunto del correo
            // ═══════════════════════════════════════════════════════════════
            
            // Statuses
            $table->enum('status_calculado', ['In Process', 'Done', 'Out of Metric'])->default('In Process');
            $table->enum('status_manual', ['In Process', 'Done', 'Out of Metric'])->default('In Process');
            $table->timestamp('fecha_status_manual')->nullable();
            $table->enum('color_status', ['verde', 'amarillo', 'rojo', 'sin_fecha'])->default('sin_fecha');
            $table->integer('dias_transcurridos_calculados')->nullable();
            $table->timestamp('fecha_ultimo_calculo')->nullable();
            
            $table->text('comentarios')->nullable();
            
            // Fechas
            $table->date('fecha_embarque')->nullable()->index();
            $table->date('fecha_arribo_aduana')->nullable();
            $table->date('fecha_modulacion')->nullable();
            $table->date('fecha_arribo_planta')->nullable();
            
            $table->integer('resultado')->nullable();
            $table->integer('target')->nullable();
            $table->integer('dias_transito')->nullable();
            
            // Referencias cruzadas
            $table->foreignId('post_operacion_id')->nullable()->constrained('post_operaciones')->onDelete('set null');
            $table->enum('post_operacion_status', ['In Process', 'Done', 'Out of Metric'])->default('In Process');
            
            $table->timestamps();
        });

        // 10. Operacion Comentarios
        Schema::create('operacion_comentarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operacion_logistica_id')->constrained('operaciones_logisticas')->onDelete('cascade');
            $table->text('comentario');
            $table->string('status_en_momento')->nullable();
            $table->string('tipo_accion')->default('comentario');
            $table->string('usuario_nombre')->nullable();
            $table->integer('usuario_id')->nullable();
            $table->longText('contexto_operacion')->nullable();
            $table->timestamps();
            
            $table->index(['operacion_logistica_id', 'created_at']);
        });

        // 11. Historico
        Schema::create('historico_matriz_sgm', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operacion_logistica_id')->constrained('operaciones_logisticas')->onDelete('cascade');
            $table->date('fecha_arribo_aduana')->nullable();
            $table->date('fecha_registro');
            $table->integer('dias_transcurridos');
            $table->integer('target_dias');
            $table->enum('color_status', ['verde', 'amarillo', 'rojo'])->index();
            $table->enum('operacion_status', ['In Process', 'Done', 'Out of Metric']);
            $table->text('observaciones')->nullable();
            $table->timestamps();
            
            $table->index(['operacion_logistica_id', 'fecha_registro']);
        });

        // 12. Post Operacion Pivot
        Schema::create('post_operacion_operacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('post_operacion_id')->constrained('post_operaciones')->onDelete('cascade');
            $table->foreignId('operacion_logistica_id')->constrained('operaciones_logisticas')->onDelete('cascade');
            $table->enum('status', ['Pendiente', 'Completado', 'No Aplica'])->default('Pendiente');
            $table->timestamp('fecha_asignacion')->useCurrent();
            $table->timestamp('fecha_completado')->nullable();
            $table->text('notas_especificas')->nullable();
            $table->timestamps();
            
            $table->unique(['post_operacion_id', 'operacion_logistica_id'], 'unique_post_op_operacion');
        });

        // 13. Pedimentos Operaciones
        Schema::create('pedimentos_operaciones', function (Blueprint $table) {
            $table->id();
            $table->string('no_pedimento')->index();
            $table->string('clave', 10);
            $table->foreignId('operacion_logistica_id')->constrained('operaciones_logisticas')->onDelete('cascade');
            $table->enum('estado_pago', ['pendiente', 'pagado'])->default('pendiente');
            $table->date('fecha_pago')->nullable();
            $table->decimal('monto', 10, 2)->nullable();
            $table->string('moneda', 10)->default('MXN');
            $table->text('observaciones')->nullable();
            $table->timestamps();
            
            $table->unique(['no_pedimento', 'operacion_logistica_id'], 'unique_pedimento_operacion');
        });

        // 14. Valores Campos Personalizados
        Schema::create('valores_campos_personalizados', function (Blueprint $table) {
            $table->id();
            $table->foreignId('operacion_logistica_id')->constrained('operaciones_logisticas')->onDelete('cascade');
            $table->foreignId('campo_personalizado_id')->constrained('campos_personalizados_matriz')->onDelete('cascade');
            $table->text('valor')->nullable();
            $table->timestamps();
            
            $table->unique(['operacion_logistica_id', 'campo_personalizado_id'], 'valor_campo_operacion_unique');
        });

        // 15. Circular FK for post_operaciones
        Schema::table('post_operaciones', function (Blueprint $table) {
            $table->foreign('operacion_logistica_id')->references('id')->on('operaciones_logisticas')->onDelete('set null');
        });
    }

    public function down(): void
    {
        // Drop circular FKs first
        Schema::table('post_operaciones', function (Blueprint $table) {
            $table->dropForeign(['operacion_logistica_id']);
        });

        Schema::dropIfExists('valores_campos_personalizados');
        Schema::dropIfExists('pedimentos_operaciones');
        Schema::dropIfExists('post_operacion_operacion');
        Schema::dropIfExists('historico_matriz_sgm');
        Schema::dropIfExists('operacion_comentarios');
        Schema::dropIfExists('operaciones_logisticas');
        Schema::dropIfExists('post_operaciones');
        Schema::dropIfExists('columnas_visibles_ejecutivo');
        Schema::dropIfExists('campo_personalizado_ejecutivo');
        Schema::dropIfExists('campos_personalizados_matriz');
        Schema::dropIfExists('incoterms');
        Schema::dropIfExists('pedimentos');
        Schema::dropIfExists('logistica_correos_cc');
        Schema::dropIfExists('clientes');
        Schema::dropIfExists('transportes');
        Schema::dropIfExists('agentes_aduanales');
        Schema::dropIfExists('aduanas');
    }
};