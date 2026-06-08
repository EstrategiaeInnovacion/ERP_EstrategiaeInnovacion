<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ── Catálogos de Reglas de Origen T-MEC ─────────────────────────────

        Schema::create('reglas_origen', function (Blueprint $table) {
            $table->id();
            $table->string('fraccion_arancelaria', 20)->unique();
            $table->string('fraccion_inicio_norm', 16)->nullable();
            $table->string('fraccion_fin_norm', 16)->nullable();
            $table->text('descripcion')->nullable();
            $table->string('criterio', 10)->nullable();
            $table->text('regla_texto');
            $table->smallInteger('capitulo')->unsigned()->nullable();
            $table->boolean('requiere_apendice')->default(false);
            $table->text('nota_apendice')->nullable();
            $table->text('referencia_apendice_texto')->nullable();
            $table->decimal('vcr_porcentaje', 5, 2)->nullable();
            $table->string('metodo_vcr', 20)->nullable();
            $table->boolean('requiere_cambio_fraccion')->default(false);
            $table->string('nivel_cambio', 20)->nullable();
            $table->timestamps();

            $table->index('capitulo');
            $table->index('criterio');
            $table->index('requiere_apendice');
            $table->index(['fraccion_inicio_norm', 'fraccion_fin_norm'], 'reglas_origen_fraccion_norm_idx');
        });

        Schema::create('reglas_automotrices', function (Blueprint $table) {
            $table->id();
            $table->string('fraccion_arancelaria', 30);
            $table->string('fraccion_inicio_norm', 16)->nullable();
            $table->string('fraccion_fin_norm', 16)->nullable();
            $table->string('tipo_vehiculo_pt', 60)->nullable();
            $table->boolean('requiere_cc')->nullable();
            $table->string('nivel_cc', 20)->nullable();
            $table->string('cc_excepcion_desde', 50)->nullable();
            $table->string('vcr_metodo', 30)->nullable();
            $table->decimal('vcr_umbral_pct', 5, 2)->nullable();
            $table->string('tabla_partes_ref', 250)->nullable();
            $table->string('articulo_apendice', 50)->nullable();
            $table->text('regla_texto');
            $table->string('referencia_nota', 50)->nullable();
            $table->timestamps();

            $table->index(['fraccion_inicio_norm', 'fraccion_fin_norm'], 'reglas_automotrices_fraccion_norm_idx');
            $table->index('tipo_vehiculo_pt', 'reglas_automotrices_tipo_vehiculo_idx');
        });

        Schema::create('seccion_c_fracciones', function (Blueprint $table) {
            $table->id();
            $table->string('fraccion_tmec', 100);
            $table->string('fraccion_tmec_norm', 16)->nullable();
            $table->text('fraccion_canada')->nullable();
            $table->text('fraccion_eeuu')->nullable();
            $table->text('fraccion_mexico')->nullable();
            $table->text('descripcion')->nullable();
            $table->timestamps();

            $table->index('fraccion_tmec');
            $table->index('fraccion_tmec_norm');
        });

        Schema::create('apendice_partes_catalogo', function (Blueprint $table) {
            $table->id();
            $table->string('tabla', 150);
            $table->string('tabla_codigo', 20)->nullable();
            $table->text('fraccion_arancelaria')->nullable();
            $table->string('fraccion_inicio_norm', 16)->nullable();
            $table->string('fraccion_fin_norm', 16)->nullable();
            $table->text('fraccion_normalizada')->nullable();
            $table->boolean('tiene_ex_prefix')->default(false);
            $table->decimal('vcr_umbral_cn_pct', 5, 2)->nullable();
            $table->decimal('vcr_umbral_vt_pct', 5, 2)->nullable();
            $table->text('descripcion');
            $table->timestamps();

            $table->index(
                ['tabla_codigo', 'fraccion_inicio_norm', 'fraccion_fin_norm'],
                'apendice_partes_fraccion_norm_idx'
            );
        });

        Schema::create('apendice_tabla_a1', function (Blueprint $table) {
            $table->id();
            $table->text('descripcion_parte');
            $table->string('fraccion_arancelaria', 20)->nullable();
            $table->string('categoria', 100)->nullable();
            $table->decimal('porcentaje_min', 5, 2)->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index('fraccion_arancelaria');
        });

        Schema::create('apendice_tabla_a2', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_material', 100);
            $table->text('descripcion')->nullable();
            $table->decimal('porcentaje_min', 5, 2)->nullable();
            $table->smallInteger('anio_vigencia')->unsigned()->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();
        });

        Schema::create('apendice_tablas_bcd', function (Blueprint $table) {
            $table->id();
            $table->string('tabla', 5);
            $table->string('fraccion_arancelaria', 20)->nullable();
            $table->text('descripcion');
            $table->string('categoria', 100)->nullable();
            $table->decimal('valor_umbral', 10, 4)->nullable();
            $table->decimal('porcentaje_umbral', 5, 2)->nullable();
            $table->text('notas')->nullable();
            $table->timestamps();

            $table->index(['tabla', 'fraccion_arancelaria']);
        });

        Schema::create('parametros_sistema_ce', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 80)->unique();
            $table->string('descripcion', 255)->nullable();
            $table->decimal('valor_decimal', 10, 4)->nullable();
            $table->string('valor_texto', 255)->nullable();
            $table->smallInteger('anio_vigencia')->unsigned()->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->index('anio_vigencia');
        });

        // ── Índice relacional de catálogos ───────────────────────────────────

        Schema::create('catalogo_relaciones', function (Blueprint $table) {
            $table->id();
            $table->string('relation_type', 50);
            $table->string('relation_key', 100)->nullable();
            $table->foreignId('regla_origen_id')->nullable()->constrained('reglas_origen')->nullOnDelete();
            $table->foreignId('regla_automotriz_id')->nullable()->constrained('reglas_automotrices')->nullOnDelete();
            $table->foreignId('seccion_c_fraccion_id')->nullable()->constrained('seccion_c_fracciones')->nullOnDelete();
            $table->foreignId('apendice_parte_id')->nullable()->constrained('apendice_partes_catalogo')->nullOnDelete();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['relation_type', 'relation_key'], 'catalogo_relaciones_type_key_idx');
        });

        // ── BOMs y análisis de origen ────────────────────────────────────────

        Schema::create('boms', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 40)->unique();
            $table->string('nombre')->nullable();
            $table->string('archivo_original')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            $table->index('created_by');
        });

        Schema::create('bom_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained('boms')->cascadeOnDelete();

            // Producto terminado (Finished Good)
            $table->string('numero_de_parte')->nullable();
            $table->string('fraccion_arancelaria_fg')->nullable();
            $table->string('descripcion_fg')->nullable();
            $table->decimal('precio_final_usd', 18, 6)->nullable();
            $table->string('nivel')->nullable();

            // Insumo (Raw Material)
            $table->string('no_parte_insumo')->nullable();
            $table->string('descripcion_rm')->nullable();
            $table->decimal('cantidad_incorporada', 18, 6)->nullable();
            $table->decimal('precio_unitario', 18, 6)->nullable();
            $table->string('unidad_de_medida')->nullable();
            $table->decimal('costo_total_usd', 18, 6)->nullable();
            $table->decimal('costo_total_pesos', 18, 6)->nullable();
            $table->string('fraccion_arancelaria_rm')->nullable();
            $table->string('pais_de_origen')->nullable();

            // Análisis
            $table->string('nombre_proveedor')->nullable();
            $table->string('presenta_cambio_fraccion')->nullable();
            $table->string('cumple_demas_requisitos')->nullable();
            $table->string('califica_originario')->nullable();
            $table->text('regla_de_origen')->nullable();
            $table->string('criterio_de_origen')->nullable();
            $table->foreignId('regla_origen_id')
                ->nullable()
                ->constrained('reglas_origen')
                ->nullOnDelete();
            $table->json('analisis_detalle')->nullable();
            $table->timestamp('analisis_en')->nullable();

            $table->timestamps();
        });

        Schema::create('origin_analyses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained('boms')->cascadeOnDelete();
            $table->string('part_number', 100)->nullable();
            $table->string('fg_fraction', 30)->nullable();
            $table->decimal('fg_price_usd', 14, 6)->nullable();
            $table->decimal('non_orig_cost_usd', 14, 6)->default(0);
            $table->decimal('rvc_percentage', 6, 2)->nullable();
            $table->decimal('rvc_threshold', 6, 2)->nullable();
            $table->boolean('cc_complies')->nullable();
            $table->char('origin_criterion', 1)->nullable();
            $table->boolean('qualifies')->nullable();
            $table->text('applicable_rule')->nullable();
            $table->json('copilot_response')->nullable();
            $table->foreignId('analyst_id')->constrained('users');
            $table->timestamp('analyzed_at')->useCurrent();
            $table->date('valid_until')->nullable();
            $table->timestamps();

            $table->index(['bom_id', 'analyzed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('origin_analyses');
        Schema::dropIfExists('bom_items');
        Schema::dropIfExists('boms');
        Schema::dropIfExists('catalogo_relaciones');
        Schema::dropIfExists('apendice_partes_catalogo');
        Schema::dropIfExists('apendice_tabla_a1');
        Schema::dropIfExists('apendice_tabla_a2');
        Schema::dropIfExists('apendice_tablas_bcd');
        Schema::dropIfExists('seccion_c_fracciones');
        Schema::dropIfExists('reglas_automotrices');
        Schema::dropIfExists('reglas_origen');
        Schema::dropIfExists('parametros_sistema_ce');
    }
};
