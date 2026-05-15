<?php

namespace App\Models\Administracion;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PerfilCliente extends Model
{
    use HasFactory;

    protected $table = 'admin_cliente_perfiles';

    protected $fillable = [
        'cliente_id',
        // Datos generales
        'nombre_legal', 'sectores_productivos', 'fecha_inicio_operaciones',
        'partes_relacionadas_extranjero', 'nombre_corporativo', 'ciudad_estado_pais_corporativo',
        'registro_marca', 'poliza_seguro_mercancias',
        // Programas
        'tiene_immex', 'immex_fecha',
        'tiene_immex_servicios', 'immex_servicios_fecha',
        'es_maquiladora', 'maquiladora_fecha',
        'maquiladora_servicios', 'maquiladora_servicios_fecha',
        'tiene_prosec', 'prosec_fecha',
        'transferencias_otras_immex',
        'empresa_certificada_oea', 'oea_fecha',
        'empresa_certificada_iva_eps', 'iva_eps_modalidad', 'iva_eps_fecha',
        'tiene_ctpat', 'ctpat_fecha',
        'utiliza_regla_octava',
        'automotriz_deposito_fiscal', 'automotriz_fecha',
        'proveedor_autopartes',
        // Perfil empresa
        'utiliza_almacen_fiscal', 'utiliza_regla_2',
        'estudio_precios_transferencia', 'estudio_valoracion_aduanera',
        'importa_mercancias_nom', 'nom_tipo', 'proveedores_sub_maquila',
        'importa_precios_estimados', 'importa_permisos_avisos',
        'destino_desperdicios',
        'certificados_origen_tlcan', 'certificados_origen_tlcue',
        'exporta_eua_canada', 'exporta_union_europea',
        'emite_certificados_eua_canada', 'emite_certificados_union_europea',
        // Sistemas
        'sistema_manufactura_erp', 'sistema_anexo_24', 'recibe_info_agentes_aduanales',
        // Manuales
        'manual_procedimientos_ce',
        // Antecedentes
        'ultima_auditoria_interna', 'ultima_auditoria_externa',
        'principales_hallazgos', 'auditado_shcp_se', 'auditado_shcp_se_fecha',
        'observaciones_multas',
        // Volumen
        'pedimentos_anuales_importacion', 'pedimentos_anuales_exportacion',
        'aduana_principal_importacion', 'aduana_principal_exportacion',
        // Proveedores y clientes
        'proveedores_extranjeros_cantidad', 'pais_origen_importaciones',
        'importa_fuera_tlcan', 'importa_fuera_tlcan_paises', 'clientes_extranjeros_cantidad',
        'pais_destino_exportaciones', 'insumos_importacion_importantes',
        'productos_exportacion_representativos',
        // Informante
        'informante_nombre', 'informante_puesto', 'informante_fecha',
    ];

    protected $casts = [
        'fecha_inicio_operaciones'        => 'date',
        'immex_fecha'                     => 'date',
        'immex_servicios_fecha'           => 'date',
        'maquiladora_fecha'               => 'date',
        'maquiladora_servicios_fecha'     => 'date',
        'prosec_fecha'                    => 'date',
        'oea_fecha'                       => 'date',
        'iva_eps_fecha'                   => 'date',
        'ctpat_fecha'                     => 'date',
        'automotriz_fecha'                => 'date',
        'auditado_shcp_se_fecha'          => 'date',
        'informante_fecha'                => 'date',
        'partes_relacionadas_extranjero'  => 'boolean',
        'registro_marca'                  => 'boolean',
        'poliza_seguro_mercancias'        => 'boolean',
        'tiene_immex'                     => 'boolean',
        'tiene_immex_servicios'           => 'boolean',
        'es_maquiladora'                  => 'boolean',
        'maquiladora_servicios'           => 'boolean',
        'tiene_prosec'                    => 'boolean',
        'transferencias_otras_immex'      => 'boolean',
        'empresa_certificada_oea'         => 'boolean',
        'empresa_certificada_iva_eps'     => 'boolean',
        'tiene_ctpat'                     => 'boolean',
        'utiliza_regla_octava'            => 'boolean',
        'automotriz_deposito_fiscal'      => 'boolean',
        'proveedor_autopartes'            => 'boolean',
        'utiliza_almacen_fiscal'          => 'boolean',
        'utiliza_regla_2'                 => 'boolean',
        'estudio_precios_transferencia'   => 'boolean',
        'estudio_valoracion_aduanera'     => 'boolean',
        'importa_mercancias_nom'          => 'boolean',
        'proveedores_sub_maquila'         => 'boolean',
        'importa_precios_estimados'       => 'boolean',
        'importa_permisos_avisos'         => 'boolean',
        'certificados_origen_tlcan'       => 'boolean',
        'certificados_origen_tlcue'       => 'boolean',
        'exporta_eua_canada'              => 'boolean',
        'exporta_union_europea'           => 'boolean',
        'emite_certificados_eua_canada'   => 'boolean',
        'emite_certificados_union_europea'=> 'boolean',
        'recibe_info_agentes_aduanales'   => 'boolean',
        'manual_procedimientos_ce'        => 'boolean',
        'auditado_shcp_se'                => 'boolean',
        'importa_fuera_tlcan'             => 'boolean',
    ];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
}
