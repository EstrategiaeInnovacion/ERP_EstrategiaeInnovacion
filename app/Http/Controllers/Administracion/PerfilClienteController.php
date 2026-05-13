<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Models\Administracion\Cliente;
use App\Models\Administracion\PerfilCliente;
use Illuminate\Http\Request;

class PerfilClienteController extends Controller
{
    public function show(Cliente $cliente)
    {
        $perfil = $cliente->perfil ?? new PerfilCliente(['cliente_id' => $cliente->id]);
        return view('Administracion.perfil', compact('cliente', 'perfil'));
    }

    public function upsert(Request $request, Cliente $cliente)
    {
        $data = $request->validate([
            'nombre_legal'                    => 'nullable|string|max:255',
            'sectores_productivos'            => 'nullable|string',
            'fecha_inicio_operaciones'        => 'nullable|date',
            'partes_relacionadas_extranjero'  => 'nullable|boolean',
            'nombre_corporativo'              => 'nullable|string|max:255',
            'ciudad_estado_pais_corporativo'  => 'nullable|string|max:255',
            'tiene_immex'                     => 'nullable|boolean',
            'immex_fecha'                     => 'nullable|date',
            'es_maquiladora'                  => 'nullable|boolean',
            'maquiladora_fecha'               => 'nullable|date',
            'maquiladora_servicios'           => 'nullable|boolean',
            'maquiladora_servicios_fecha'     => 'nullable|date',
            'tiene_prosec'                    => 'nullable|boolean',
            'prosec_fecha'                    => 'nullable|date',
            'transferencias_otras_immex'      => 'nullable|boolean',
            'empresa_certificada_oea'         => 'nullable|boolean',
            'empresa_certificada_iva_eps'     => 'nullable|boolean',
            'iva_eps_modalidad'               => 'nullable|string|max:100',
            'utiliza_regla_octava'            => 'nullable|boolean',
            'automotriz_deposito_fiscal'      => 'nullable|boolean',
            'proveedor_autopartes'            => 'nullable|boolean',
            'utiliza_almacen_fiscal'          => 'nullable|boolean',
            'utiliza_regla_2'                 => 'nullable|boolean',
            'estudio_precios_transferencia'   => 'nullable|boolean',
            'estudio_valoracion_aduanera'     => 'nullable|boolean',
            'importa_mercancias_nom'          => 'nullable|boolean',
            'proveedores_sub_maquila'         => 'nullable|boolean',
            'importa_precios_estimados'       => 'nullable|boolean',
            'importa_permisos_avisos'         => 'nullable|boolean',
            'destino_desperdicios'            => 'nullable|string',
            'certificados_origen_tlcan'       => 'nullable|boolean',
            'certificados_origen_tlcue'       => 'nullable|boolean',
            'exporta_eua_canada'              => 'nullable|boolean',
            'exporta_union_europea'           => 'nullable|boolean',
            'emite_certificados_eua_canada'   => 'nullable|boolean',
            'emite_certificados_union_europea'=> 'nullable|boolean',
            'sistema_manufactura_erp'         => 'nullable|string|max:255',
            'sistema_anexo_24'                => 'nullable|string|max:255',
            'recibe_info_agentes_aduanales'   => 'nullable|boolean',
            'manual_procedimientos_ce'        => 'nullable|boolean',
            'ultima_auditoria_interna'        => 'nullable|string',
            'ultima_auditoria_externa'        => 'nullable|string',
            'principales_hallazgos'           => 'nullable|string',
            'auditado_shcp_se'                => 'nullable|boolean',
            'auditado_shcp_se_fecha'          => 'nullable|date',
            'observaciones_multas'            => 'nullable|string',
            'pedimentos_anuales_importacion'  => 'nullable|integer|min:0',
            'pedimentos_anuales_exportacion'  => 'nullable|integer|min:0',
            'aduana_principal_importacion'    => 'nullable|string|max:255',
            'aduana_principal_exportacion'    => 'nullable|string|max:255',
            'proveedores_extranjeros_cantidad'=> 'nullable|integer|min:0',
            'pais_origen_importaciones'       => 'nullable|string|max:255',
            'importa_fuera_tlcan'             => 'nullable|boolean',
            'clientes_extranjeros_cantidad'   => 'nullable|integer|min:0',
            'pais_destino_exportaciones'      => 'nullable|string|max:255',
            'insumos_importacion_importantes' => 'nullable|string',
            'productos_exportacion_representativos' => 'nullable|string',
            'informante_nombre'               => 'nullable|string|max:255',
            'informante_puesto'               => 'nullable|string|max:255',
            'informante_fecha'                => 'nullable|date',
        ]);

        // Checkboxes no enviados = false
        $booleans = [
            'partes_relacionadas_extranjero','tiene_immex','es_maquiladora',
            'maquiladora_servicios','tiene_prosec','transferencias_otras_immex',
            'empresa_certificada_oea','empresa_certificada_iva_eps','utiliza_regla_octava',
            'automotriz_deposito_fiscal','proveedor_autopartes','utiliza_almacen_fiscal',
            'utiliza_regla_2','estudio_precios_transferencia','estudio_valoracion_aduanera',
            'importa_mercancias_nom','proveedores_sub_maquila','importa_precios_estimados',
            'importa_permisos_avisos','certificados_origen_tlcan','certificados_origen_tlcue',
            'exporta_eua_canada','exporta_union_europea','emite_certificados_eua_canada',
            'emite_certificados_union_europea','recibe_info_agentes_aduanales',
            'manual_procedimientos_ce','auditado_shcp_se','importa_fuera_tlcan',
        ];
        foreach ($booleans as $b) {
            $data[$b] = $request->boolean($b);
        }

        $data['cliente_id'] = $cliente->id;

        PerfilCliente::updateOrCreate(['cliente_id' => $cliente->id], $data);

        return redirect()->route('administracion.clientes.perfil', $cliente)
            ->with('success', 'Perfil guardado correctamente.');
    }
}
