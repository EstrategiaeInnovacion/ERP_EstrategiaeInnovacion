<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('admin_cliente_perfiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cliente_id')->constrained('admin_clientes')->cascadeOnDelete();

            // ── Datos Generales de la Empresa ──────────────────────────────
            $table->string('nombre_legal')->nullable();
            $table->text('sectores_productivos')->nullable();
            $table->date('fecha_inicio_operaciones')->nullable();
            $table->boolean('partes_relacionadas_extranjero')->default(false);
            $table->string('nombre_corporativo')->nullable();
            $table->string('ciudad_estado_pais_corporativo')->nullable();

            // ── Programas / Certificaciones ────────────────────────────────
            $table->boolean('tiene_immex')->default(false);
            $table->date('immex_fecha')->nullable();
            $table->boolean('es_maquiladora')->default(false);
            $table->date('maquiladora_fecha')->nullable();
            $table->boolean('maquiladora_servicios')->default(false);
            $table->date('maquiladora_servicios_fecha')->nullable();
            $table->boolean('tiene_prosec')->default(false);
            $table->date('prosec_fecha')->nullable();
            $table->boolean('transferencias_otras_immex')->default(false);
            $table->boolean('empresa_certificada_oea')->default(false);
            $table->boolean('empresa_certificada_iva_eps')->default(false);
            $table->string('iva_eps_modalidad')->nullable();
            $table->boolean('utiliza_regla_octava')->default(false);
            $table->boolean('automotriz_deposito_fiscal')->default(false);
            $table->boolean('proveedor_autopartes')->default(false);

            // ── Perfil de la Empresa ───────────────────────────────────────
            $table->boolean('utiliza_almacen_fiscal')->default(false);
            $table->boolean('utiliza_regla_2')->default(false);
            $table->boolean('estudio_precios_transferencia')->default(false);
            $table->boolean('estudio_valoracion_aduanera')->default(false);
            $table->boolean('importa_mercancias_nom')->default(false);
            $table->boolean('proveedores_sub_maquila')->default(false);
            $table->boolean('importa_precios_estimados')->default(false);
            $table->boolean('importa_permisos_avisos')->default(false);
            $table->text('destino_desperdicios')->nullable();
            $table->boolean('certificados_origen_tlcan')->default(false);
            $table->boolean('certificados_origen_tlcue')->default(false);
            $table->boolean('exporta_eua_canada')->default(false);
            $table->boolean('exporta_union_europea')->default(false);
            $table->boolean('emite_certificados_eua_canada')->default(false);
            $table->boolean('emite_certificados_union_europea')->default(false);

            // ── Sistemas de Información ────────────────────────────────────
            $table->string('sistema_manufactura_erp')->nullable();
            $table->string('sistema_anexo_24')->nullable();
            $table->boolean('recibe_info_agentes_aduanales')->default(false);

            // ── Manuales ───────────────────────────────────────────────────
            $table->boolean('manual_procedimientos_ce')->default(false);

            // ── Antecedentes ───────────────────────────────────────────────
            $table->text('ultima_auditoria_interna')->nullable();
            $table->text('ultima_auditoria_externa')->nullable();
            $table->text('principales_hallazgos')->nullable();
            $table->boolean('auditado_shcp_se')->default(false);
            $table->date('auditado_shcp_se_fecha')->nullable();
            $table->text('observaciones_multas')->nullable();

            // ── Volumen de Operaciones ─────────────────────────────────────
            $table->unsignedInteger('pedimentos_anuales_importacion')->nullable();
            $table->unsignedInteger('pedimentos_anuales_exportacion')->nullable();
            $table->string('aduana_principal_importacion')->nullable();
            $table->string('aduana_principal_exportacion')->nullable();

            // ── Proveedores y Clientes ─────────────────────────────────────
            $table->unsignedInteger('proveedores_extranjeros_cantidad')->nullable();
            $table->string('pais_origen_importaciones')->nullable();
            $table->boolean('importa_fuera_tlcan')->default(false);
            $table->unsignedInteger('clientes_extranjeros_cantidad')->nullable();
            $table->string('pais_destino_exportaciones')->nullable();
            $table->text('insumos_importacion_importantes')->nullable();
            $table->text('productos_exportacion_representativos')->nullable();

            // ── Información del Informante ─────────────────────────────────
            $table->string('informante_nombre')->nullable();
            $table->string('informante_puesto')->nullable();
            $table->date('informante_fecha')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('admin_cliente_perfiles');
    }
};
