<?php

namespace App\Http\Controllers\Logistica;

use App\Http\Controllers\Controller;
use App\Models\Empleado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class EquipoController extends Controller
{
    /**
     * Helper para verificar si el usuario actual es supervisor permitido
     * (Logística, Sistemas o Dirección)
     */
    private function checkPermission()
    {
        $user = auth()->user();
        if (!$user)
            return null;

        $empleado = Empleado::where('user_id', $user->id)->first();
        if (!$empleado || !$empleado->es_coordinador)
            return null;

        // Normalizar área para comparación
        $area = mb_strtolower($empleado->area, 'UTF-8');
        $posicion = mb_strtolower($empleado->posicion ?? '', 'UTF-8');

        // Áreas permitidas
        $areasPermitidas = ['logística', 'logistica', 'sistemas', 'dirección', 'direccion'];

        // Verificar si el área o posición coincide
        $esPermitido = false;
        foreach ($areasPermitidas as $permitido) {
            if (str_contains($area, $permitido) || str_contains($posicion, $permitido)) {
                $esPermitido = true;
                break;
            }
        }

        if ($esPermitido)
            return $empleado;
        return null;
    }

    public function index()
    {
        $supervisor = $this->checkPermission();
        if (!$supervisor) {
            return redirect()->route('logistica.index')
                ->with('error', 'No tienes permisos de Coordinador para ver esta sección.');
        }

        // Obtener subordinados directos
        $equipo = Empleado::where('supervisor_id', $supervisor->id)
            ->where('es_activo', true)
            ->orderBy('nombre')
            ->get();

        return view('Logistica.equipo.index', compact('equipo', 'supervisor'));
    }

    public function store(Request $request)
    {
        $supervisor = $this->checkPermission();
        if (!$supervisor)
            abort(403);

        $request->validate([
            'busqueda' => 'required|string|min:3', // Puede ser correo o ID empleado
        ]);

        $termino = $request->busqueda;

        // Buscar empleado (que no sea él mismo)
        $empleado = Empleado::where('id', '!=', $supervisor->id)
            ->where(function ($q) use ($termino) {
            $q->where('correo', $termino)
                ->orWhere('id_empleado', $termino);
        })->first();

        if (!$empleado) {
            return back()->with('error', 'No se encontró ningún empleado con ese Correo o ID.');
        }

        if ($empleado->supervisor_id === $supervisor->id) {
            return back()->with('info', 'Este empleado ya está en tu equipo.');
        }

        // Asignar supervisor
        $empleado->supervisor_id = $supervisor->id;
        $empleado->save();

        return back()->with('success', "Has agregado a {$empleado->nombre} a tu equipo correctamente.");
    }

    public function destroy($id)
    {
        $supervisor = $this->checkPermission();
        if (!$supervisor)
            abort(403);

        $empleado = Empleado::findOrFail($id);

        if ($empleado->supervisor_id !== $supervisor->id) {
            return back()->with('error', 'No puedes remover a un empleado que no está en tu equipo.');
        }

        // Desvincular
        $empleado->supervisor_id = null;
        $empleado->save();

        return back()->with('success', "Has removido a {$empleado->nombre} de tu equipo.");
    }
}
