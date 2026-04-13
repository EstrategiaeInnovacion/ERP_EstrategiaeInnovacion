<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\DiaFestivo;
use Illuminate\Http\Request;

class DiaFestivoController extends Controller
{
    public function index(Request $request)
    {
        $query = DiaFestivo::orderBy('fecha', 'desc');

        if ($request->filled('tipo') && $request->tipo !== 'todos') {
            $query->delTipo($request->tipo);
        }

        if ($request->filled('estado')) {
            if ($request->estado === 'activos') {
                $query->activos();
            } elseif ($request->estado === 'inactivos') {
                $query->where('activo', false);
            } elseif ($request->estado === 'proximos') {
                $query->proximos(30);
            }
        }

        $diasFestivos = $query->paginate(15);

        $estadisticas = [
            'total' => DiaFestivo::count(),
            'activos' => DiaFestivo::activos()->count(),
            'festivos' => DiaFestivo::activos()->delTipo('festivo')->count(),
            'inhábiles' => DiaFestivo::activos()->delTipo('inhabil')->count(),
            'proximos' => DiaFestivo::proximos(30)->count(),
        ];

        return view('Recursos_Humanos.dias_festivos.index', [
            'diasFestivos' => $diasFestivos,
            'estadisticas' => $estadisticas,
            'filtros' => [
                'tipo' => $request->tipo ?? 'todos',
                'estado' => $request->estado ?? 'todos',
            ],
        ]);
    }

    public function create()
    {
        return view('Recursos_Humanos.dias_festivos.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'fecha' => 'required|date',
            'tipo' => 'required|in:festivo,inhabil',
            'es_anual' => 'boolean',
            'descripcion' => 'nullable|string|max:1000',
            'activo' => 'boolean',
        ]);

        $validated['es_anual'] = $request->has('es_anual');
        $validated['activo'] = $request->has('activo');

        $diaFestivo = DiaFestivo::create($validated);

        $diaFestivo->crearRecordatorio(auth()->user());

        return redirect()->route('rh.dias-festivos.index')
            ->with('success', 'Día festivo creado y recordatorio generado correctamente.');
    }

    public function edit(DiaFestivo $diaFestivo)
    {
        return view('Recursos_Humanos.dias_festivos.edit', [
            'diaFestivo' => $diaFestivo,
        ]);
    }

    public function update(Request $request, DiaFestivo $diaFestivo)
    {
        $validated = $request->validate([
            'nombre' => 'required|string|max:255',
            'fecha' => 'required|date',
            'tipo' => 'required|in:festivo,inhabil',
            'es_anual' => 'boolean',
            'descripcion' => 'nullable|string|max:1000',
            'activo' => 'boolean',
        ]);

        $validated['es_anual'] = $request->has('es_anual');
        $validated['activo'] = $request->has('activo');

        $diaFestivo->update($validated);

        return redirect()->route('rh.dias-festivos.index')
            ->with('success', 'Día festivo actualizado correctamente.');
    }

    public function destroy(DiaFestivo $diaFestivo)
    {
        $diaFestivo->update(['activo' => false]);

        return redirect()->back()
            ->with('success', 'Día festivo eliminado correctamente.');
    }

    public function toggle(DiaFestivo $diaFestivo)
    {
        $diaFestivo->update(['activo' => ! $diaFestivo->activo]);

        $mensaje = $diaFestivo->activo
            ? 'Día festivo activado correctamente.'
            : 'Día festivo desactivado correctamente.';

        return redirect()->back()->with('success', $mensaje);
    }

    public function enviarNotificacion(DiaFestivo $diaFestivo)
    {
        if ($diaFestivo->notificacion_enviada) {
            return redirect()->back()->with('error', 'Ya se ha enviado la notificación anteriormente.');
        }

        $enviados = $diaFestivo->enviarNotificaciones();

        return redirect()->back()->with('success', "Notificaciones enviadas a {$enviados} empleados.");
    }

    public function crearRecordatorio(DiaFestivo $diaFestivo)
    {
        $existeRecordatorio = \App\Models\Recordatorio::where('tabla_relacionada', 'dias_festivos')
            ->where('registro_id', $diaFestivo->id)
            ->where('activo', true)
            ->exists();

        if ($existeRecordatorio) {
            return redirect()->back()->with('error', 'Ya existe un recordatorio para este día festivo.');
        }

        $diaFestivo->crearRecordatorio(auth()->user());

        return redirect()->back()->with('success', 'Recordatorio creado correctamente.');
    }
}
