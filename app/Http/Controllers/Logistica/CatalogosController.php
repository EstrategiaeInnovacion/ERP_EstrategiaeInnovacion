<?php

namespace App\Http\Controllers\Logistica;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
// Modelos necesarios
use App\Models\Logistica\Cliente;
use App\Models\Logistica\AgenteAduanal;
use App\Models\Logistica\Transporte;
use App\Models\Logistica\Aduana;
use App\Models\Logistica\Pedimento;
use App\Models\Logistica\LogisticaCorreoCC;
use App\Models\Empleado;

class CatalogosController extends Controller
{
    /**
     * Muestra la vista unificada de catálogos (Clientes, Agentes, etc.)
     */
    public function index()
    {
        $usuarioActual = auth()->user();
        $empleadoActual = null;
        $esAdmin = false;

        if ($usuarioActual) {
            $empleadoActual = Empleado::where('correo', $usuarioActual->email)
                ->orWhere('nombre', 'like', '%' . $usuarioActual->name . '%')
                ->first();
            $esAdmin = $usuarioActual->hasRole('admin');
        }

        // Logic for Logistics Supervisor (Same as in ClienteController)
        $esSupervisorLogistica = false;
        if ($empleadoActual && $empleadoActual->es_coordinador) {
            $area = mb_strtolower($empleadoActual->area, 'UTF-8');
            $posicion = mb_strtolower($empleadoActual->posicion ?? '', 'UTF-8');
            $areasPermitidas = ['logística', 'logistica', 'sistemas', 'dirección', 'direccion'];

            foreach ($areasPermitidas as $permitido) {
                if (str_contains($area, $permitido) || str_contains($posicion, $permitido)) {
                    $esSupervisorLogistica = true;
                    break;
                }
            }
        }

        // Get Team if Supervisor
        $equipo = [];
        if ($esSupervisorLogistica && $empleadoActual) {
            $equipo = Empleado::where('supervisor_id', $empleadoActual->id)
                ->where('es_activo', true)
                ->orderBy('nombre')
                ->get();
        }

        // Recuperamos los datos paginados. 
        // Usamos nombres de parámetro distintos ('clientes_page', etc.) 
        // para que la paginación de una tab no afecte a las otras.

        $clientes = Cliente::with('ejecutivoAsignado')
            ->orderBy('cliente')
            ->paginate(15, ['*'], 'clientes_page');

        $agentesAduanales = AgenteAduanal::orderBy('agente_aduanal')
            ->paginate(15, ['*'], 'agentes_page');

        $transportes = Transporte::orderBy('transporte')
            ->paginate(15, ['*'], 'transportes_page');

        $aduanas = Aduana::orderBy('aduana')
            ->orderBy('seccion')
            ->paginate(15, ['*'], 'aduanas_page');

        $pedimentos = Pedimento::orderBy('clave')
            ->paginate(15, ['*'], 'pedimentos_page');

        // Filtro para encontrar ejecutivos de logística
        $filtroLogistica = function ($query) {
            $query->where('posicion', 'like', '%logistic%')
                ->orWhere('area', 'like', '%logistic%');
        };

        $ejecutivos = Empleado::where($filtroLogistica)
            ->orderBy('nombre')
            ->paginate(15, ['*'], 'ejecutivos_page');

        // Para los selects (sin paginar)
        $todosEjecutivos = Empleado::where($filtroLogistica)
            ->orderBy('nombre')
            ->get();

        $correosCC = LogisticaCorreoCC::orderBy('tipo')
            ->orderBy('nombre')
            ->get();

        return view('Logistica.catalogos', compact(
            'clientes',
            'agentesAduanales',
            'transportes',
            'ejecutivos',
            'todosEjecutivos',
            'aduanas',
            'pedimentos',
            'correosCC',
            'empleadoActual',
            'empleadoActual',
            'esAdmin',
            'esSupervisorLogistica',
            'equipo'
        ));
    }
}