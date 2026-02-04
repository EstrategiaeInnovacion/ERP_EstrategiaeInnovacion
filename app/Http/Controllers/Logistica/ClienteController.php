<?php

namespace App\Http\Controllers\Logistica;

use App\Http\Controllers\Controller;
use App\Models\Logistica\Cliente;
use App\Models\Logistica\OperacionLogistica;
use App\Models\Empleado;
use App\Services\ClienteImportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ClienteController extends Controller
{
    /**
     * Listado de clientes (API para selects o Tabla)
     */
    public function index(Request $request)
    {
        $usuarioActual = auth()->user();
        $empleadoActual = $usuarioActual ? Empleado::where('correo', $usuarioActual->email)->first() : null;
        $esAdmin = $usuarioActual && $usuarioActual->hasRole('admin');

        $query = Cliente::with('ejecutivoAsignado')->orderBy('cliente');

        // Si no es admin, solo ver sus clientes (opcional, según tu regla de negocio)
        if (!$esAdmin && $empleadoActual) {
            $query->where(function($q) use ($empleadoActual) {
                $q->where('ejecutivo_asignado_id', $empleadoActual->id)
                  ->orWhereNull('ejecutivo_asignado_id');
            });
        }

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'clientes' => $query->get()]);
        }

        // Si necesitas paginación para una vista
        return $query->paginate(15);
    }

    public function store(Request $request)
    {
        try {
            $nombreCliente = strtoupper($request->cliente);

            $request->validate([
                'cliente' => 'required|string|max:255',
                'ejecutivo_asignado_id' => 'nullable|exists:empleados,id',
                'correos' => 'nullable|string', // JSON string
                'periodicidad_reporte' => 'nullable|string|max:50'
            ]);

            // Validación Manual de Unique (CASE INSENSITIVE)
            if (Cliente::whereRaw('UPPER(cliente) = ?', [$nombreCliente])->exists()) {
                return response()->json(['success' => false, 'message' => 'El cliente ya existe.'], 422);
            }

            // Procesar correos
            $correosArray = $request->correos ? json_decode($request->correos, true) : null;

            // Asignar ejecutivo por defecto si no viene
            $ejecutivoId = $request->ejecutivo_asignado_id;
            if (!$ejecutivoId && auth()->user()) {
                $empleado = Empleado::where('correo', auth()->user()->email)->first();
                if ($empleado) $ejecutivoId = $empleado->id;
            }

            $cliente = Cliente::create([
                'cliente' => $nombreCliente,
                'ejecutivo_asignado_id' => $ejecutivoId,
                'correos' => $correosArray,
                'periodicidad_reporte' => $request->periodicidad_reporte ?? 'Diario'
            ]);

            return response()->json([
                'success' => true, 
                'cliente' => $cliente->load('ejecutivoAsignado'), 
                'message' => 'Cliente creado correctamente'
            ]);

        } catch (\Exception $e) {
            Log::error('Error creando cliente: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Error interno: ' . $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $cliente = Cliente::findOrFail($id);
        $nombreCliente = strtoupper($request->cliente);

        // Validación básica de duplicados excluyendo el actual
        if (Cliente::whereRaw('UPPER(cliente) = ?', [$nombreCliente])->where('id', '!=', $id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Ya existe un cliente con ese nombre.'], 422);
        }

        $updateData = [
            'cliente' => $nombreCliente,
            'ejecutivo_asignado_id' => $request->ejecutivo_asignado_id
        ];

        if ($request->has('correos')) {
            $updateData['correos'] = $request->correos ? json_decode($request->correos, true) : null;
        }
        if ($request->has('periodicidad_reporte')) {
            $updateData['periodicidad_reporte'] = $request->periodicidad_reporte;
        }

        $cliente->update($updateData);

        return response()->json(['success' => true, 'cliente' => $cliente->load('ejecutivoAsignado'), 'message' => 'Cliente actualizado']);
    }

    public function destroy($id)
    {
        $cliente = Cliente::findOrFail($id);
        
        // Verificar uso en operaciones (por nombre, ya que no usas ID en operaciones)
        $uso = OperacionLogistica::where('cliente', $cliente->cliente)->count();
        if ($uso > 0) {
            return response()->json(['success' => false, 'message' => "No se puede eliminar: tiene $uso operaciones asociadas."], 400);
        }

        $cliente->delete();
        return response()->json(['success' => true, 'message' => 'Cliente eliminado']);
    }

    public function asignarEjecutivo(Request $request)
    {
        // Solo admins
        if (!auth()->user()->hasRole('admin')) abort(403);

        $request->validate([
            'cliente_ids' => 'required|array',
            'ejecutivo_id' => 'required|exists:empleados,id'
        ]);

        Cliente::whereIn('id', $request->cliente_ids)
            ->update(['ejecutivo_asignado_id' => $request->ejecutivo_id]);

        return response()->json(['success' => true, 'message' => 'Clientes reasignados correctamente']);
    }

    public function import(Request $request, ClienteImportService $importService)
    {
        $request->validate(['clientes_file' => 'required|file|mimes:xlsx,xls']);
        
        $path = $request->file('clientes_file')->store('temp');
        $resultados = $importService->importFromExcel(storage_path("app/$path"));
        
        return response()->json(['success' => true, 'resultados' => $resultados]);
    }

    public function deleteAll()
    {
        if (!auth()->user()->hasRole('admin')) abort(403);
        Cliente::truncate();
        return response()->json(['success' => true, 'message' => 'Todos los clientes han sido eliminados']);
    }
}