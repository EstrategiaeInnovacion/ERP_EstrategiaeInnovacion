<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Empleado;
use Illuminate\Http\Request;

class JerarquiaController extends Controller
{
    /**
     * Muestra la lista de empleados para gestionar sus supervisores.
     */
    public function index(Request $request)
    {
        $search = $request->input('search');
        $area = $request->input('area');

        // Consulta base con la relación de supervisor cargada
        $query = Empleado::query()->with('supervisor');

        // Filtros
        if ($search) {
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('id_empleado', 'like', "%{$search}%")
                  ->orWhere('posicion', 'like', "%{$search}%");
            });
        }

        if ($area && $area !== 'Todos') {
            $query->where('area', $area);
        }

        // Ordenar por área y luego por nombre para facilitar la lectura
        $empleados = $query->orderBy('area')->orderBy('nombre')->paginate(20);

        // Listas para los filtros y selects
        // Obtenemos todos los empleados para que puedan ser seleccionados como supervisores
        // Optimizamos seleccionando solo lo necesario
        $posiblesSupervisores = Empleado::select('id', 'nombre', 'posicion', 'area')
                                        ->orderBy('nombre')
                                        ->get();
                                        
        $areas = Empleado::select('area')->distinct()->whereNotNull('area')->pluck('area');

        return view('Recursos_Humanos.jerarquia.index', compact('empleados', 'posiblesSupervisores', 'areas'));
    }

    /**
     * Actualiza el supervisor de un empleado específico.
     */
    public function update(Request $request, $id)
    {
        $empleado = Empleado::findOrFail($id);

        $request->validate([
            'supervisor_id' => 'nullable|exists:empleados,id',
        ]);

        // Validación simple para evitar que alguien sea su propio jefe
        if ($request->supervisor_id == $empleado->id) {
            return back()->with('error', 'Un empleado no puede ser su propio supervisor.');
        }

        $empleado->supervisor_id = $request->supervisor_id;
        $empleado->save();

        return back()->with('success', "Supervisor actualizado para {$empleado->nombre}.");
    }
}