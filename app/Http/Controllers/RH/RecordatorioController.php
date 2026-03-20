<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\Recordatorio;
use Illuminate\Http\Request;
use Carbon\Carbon;

class RecordatorioController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $query = Recordatorio::with('empleado')
            ->where('activo', true)
            ->whereDate('fecha_evento', '>=', Carbon::today()->subDays(7))
            ->whereDate('fecha_evento', '<=', Carbon::today()->addDays(30))
            ->orderByRaw("CASE WHEN fecha_evento >= date('now') THEN 0 ELSE 1 END")
            ->orderBy('fecha_evento');

        if ($request->filled('tipo') && $request->tipo !== 'todos') {
            $query->where('tipo', $request->tipo);
        }

        if ($request->filled('estado')) {
            if ($request->estado === 'vencidos') {
                $query->whereDate('fecha_evento', '<', Carbon::today());
            } elseif ($request->estado === 'no_leidos') {
                $query->where('leido', false);
            } elseif ($request->estado === 'urgentes') {
                $query->whereDate('fecha_evento', '<=', Carbon::today()->addDays(7));
            }
        }

        $recordatorios = $query->paginate(15);

        $rangoInicio = Carbon::today()->subDays(7);
        $rangoFin = Carbon::today()->addDays(30);

        $estadisticas = [
            'total' => Recordatorio::where('activo', true)
                ->whereBetween('fecha_evento', [$rangoInicio, $rangoFin])
                ->count(),
            'no_leidos' => Recordatorio::where('activo', true)
                ->where('leido', false)
                ->whereBetween('fecha_evento', [$rangoInicio, $rangoFin])
                ->count(),
            'urgentes' => Recordatorio::where('activo', true)
                ->whereDate('fecha_evento', '<=', Carbon::today()->addDays(7))
                ->count(),
            'vencidos' => Recordatorio::where('activo', true)
                ->whereDate('fecha_evento', '<', Carbon::today())
                ->count(),
        ];

        $porTipo = [];
        foreach (Recordatorio::TIPOS as $key => $nombre) {
            $porTipo[$key] = [
                'nombre' => $nombre,
                'cantidad' => Recordatorio::where('tipo', $key)
                    ->where('activo', true)
                    ->whereBetween('fecha_evento', [$rangoInicio, $rangoFin])
                    ->count(),
            ];
        }

        return view('Recursos_Humanos.recordatorios.index', [
            'recordatorios' => $recordatorios,
            'estadisticas' => $estadisticas,
            'porTipo' => $porTipo,
            'filtros' => [
                'tipo' => $request->tipo ?? 'todos',
                'estado' => $request->estado ?? 'todos',
            ],
        ]);
    }

    public function show(int $id)
    {
        $recordatorio = Recordatorio::with('empleado', 'creador')->findOrFail($id);

        if (!$recordatorio->leido) {
            $recordatorio->marcarLeido();
        }

        return view('Recursos_Humanos.recordatorios.show', [
            'recordatorio' => $recordatorio,
        ]);
    }

    public function marcarLeido(Request $request, int $id)
    {
        $recordatorio = Recordatorio::findOrFail($id);
        $recordatorio->marcarLeido();

        if ($request->ajax()) {
            return response()->json(['success' => true]);
        }

        return redirect()->back();
    }

    public function marcarTodosLeidos()
    {
        Recordatorio::where('activo', true)
            ->where('leido', false)
            ->update([
                'leido' => true,
                'leido_at' => Carbon::now(),
            ]);

        return redirect()->back()->with('success', 'Todos los recordatorios marcados como leídos');
    }

    public function destruir(int $id)
    {
        $recordatorio = Recordatorio::findOrFail($id);
        $recordatorio->update(['activo' => false]);

        return redirect()->back()->with('success', 'Recordatorio eliminado');
    }

    public function generarManual()
    {
        \Illuminate\Support\Facades\Artisan::call('rh:generar-recordatorios');

        return redirect()->back()->with(
            'success',
            'Recordatorios regenerados correctamente. ' . \Illuminate\Support\Facades\Artisan::output()
        );
    }

    public function calendario()
    {
        $recordatorios = Recordatorio::with('empleado')
            ->where('activo', true)
            ->whereBetween('fecha_evento', [
                Carbon::now()->startOfMonth()->subMonth(),
                Carbon::now()->endOfMonth()->addMonths(2),
            ])
            ->get()
            ->map(function ($r) {
                return [
                    'id' => $r->id,
                    'title' => $r->titulo,
                    'start' => $r->fecha_evento->format('Y-m-d'),
                    'className' => ' urgency-' . $r->urgencia,
                    'url' => route('rh.recordatorios.show', $r->id),
                ];
            });

        return view('Recursos_Humanos.recordatorios.calendario', [
            'recordatorios' => $recordatorios,
        ]);
    }
}
