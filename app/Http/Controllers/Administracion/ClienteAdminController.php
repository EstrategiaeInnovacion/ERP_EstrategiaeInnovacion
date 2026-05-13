<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Models\Administracion\Cliente;
use App\Models\Administracion\PerfilCliente;
use Illuminate\Http\Request;

class ClienteAdminController extends Controller
{
    public function index()
    {
        $clientes = Cliente::with('perfil')->orderBy('nombre')->get();
        return view('Administracion.clientes', compact('clientes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre_legal' => 'required|string|max:255',
        ], ['nombre_legal.required' => 'El Nombre Legal de la Empresa es obligatorio.']);

        $cliente = Cliente::create([
            'nombre'   => $request->nombre_legal,
            'empresa'  => $request->nombre_legal,
            'contacto' => $request->informante_nombre,
            'correo'   => null,
            'telefono' => null,
            'notas'    => null,
        ]);

        $this->guardarPerfil($cliente, $request);

        return response()->json(['success' => true, 'message' => 'Cliente creado correctamente.']);
    }

    public function update(Request $request, Cliente $cliente)
    {
        $request->validate([
            'nombre_legal' => 'required|string|max:255',
        ], ['nombre_legal.required' => 'El Nombre Legal de la Empresa es obligatorio.']);

        $cliente->update([
            'nombre'   => $request->nombre_legal,
            'empresa'  => $request->nombre_legal,
            'contacto' => $request->informante_nombre,
        ]);

        $this->guardarPerfil($cliente, $request);

        return response()->json(['success' => true, 'message' => 'Cliente actualizado.']);
    }

    public function destroy(Cliente $cliente)
    {
        $cliente->delete();
        return response()->json(['success' => true, 'message' => 'Cliente eliminado.']);
    }

    private function guardarPerfil(Cliente $cliente, Request $request): void
    {
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

        $data = ['cliente_id' => $cliente->id];

        foreach ($booleans as $b) {
            $data[$b] = (bool) $request->input($b, false);
        }

        $fields = [
            'nombre_legal','sectores_productivos','fecha_inicio_operaciones',
            'nombre_corporativo','ciudad_estado_pais_corporativo',
            'immex_fecha','maquiladora_fecha','maquiladora_servicios_fecha','prosec_fecha',
            'iva_eps_modalidad','destino_desperdicios','sistema_manufactura_erp','sistema_anexo_24',
            'ultima_auditoria_interna','ultima_auditoria_externa','principales_hallazgos',
            'auditado_shcp_se_fecha','observaciones_multas',
            'pedimentos_anuales_importacion','pedimentos_anuales_exportacion',
            'aduana_principal_importacion','aduana_principal_exportacion',
            'proveedores_extranjeros_cantidad','pais_origen_importaciones',
            'clientes_extranjeros_cantidad','pais_destino_exportaciones',
            'insumos_importacion_importantes','productos_exportacion_representativos',
            'informante_nombre','informante_puesto','informante_fecha',
        ];

        foreach ($fields as $f) {
            $val = $request->input($f);
            $data[$f] = ($val === '' || $val === null) ? null : $val;
        }

        PerfilCliente::updateOrCreate(['cliente_id' => $cliente->id], $data);
    }
}
