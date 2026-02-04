<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Asistencia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;
use Carbon\Carbon;

class ProfileController extends Controller
{
    public function edit(Request $request): View
    {
        $user = $request->user();
        $empleado = $user->empleado;

        // 1. Periodo (Mes seleccionado)
        $periodoActual = $request->input('periodo', now()->format('Y-m'));
        $fechaBase = Carbon::createFromFormat('Y-m', $periodoActual);
        
        $inicioMes = $fechaBase->copy()->startOfMonth();
        $finMes = $fechaBase->copy()->endOfMonth();

        // 2. Obtener Asistencias
        $asistencias = collect();
        if ($empleado) {
            $asistencias = Asistencia::where('empleado_id', $empleado->id)
                ->whereBetween('fecha', [$inicioMes, $finMes->copy()->endOfDay()])
                ->get()
                ->keyBy(function($item) {
                    return Carbon::parse($item->fecha)->format('Y-m-d');
                });
        }

        // 3. CONSTRUIR DATOS DEL CALENDARIO
        $calendarData = [];
        $startDayOfWeek = $inicioMes->dayOfWeek; // 0 (Domingo) - 6 (Sábado)
        
        // Ajuste para que Lunes sea 0 o 1 según prefieras (Aquí usaremos Lunes=1er columna)
        // Carbon: 0=Domingo, 1=Lunes... 
        // Visualmente queremos: Lun, Mar, Mie, Jue, Vie, Sab, Dom
        // Ajustamos para padding inicial
        $blankDays = ($startDayOfWeek == 0) ? 6 : $startDayOfWeek - 1; 

        // Recorremos todos los días del mes
        for ($date = $inicioMes->copy(); $date->lte($finMes); $date->addDay()) {
            $fechaStr = $date->format('Y-m-d');
            $registro = $asistencias->get($fechaStr);
            
            $status = 'none'; // none, ok, late, absent, holiday
            $color = 'bg-white text-slate-300';
            $info = null;

            if ($registro) {
                // Determinar estado y color
                if ($registro->tipo_registro == 'asistencia') {
                    if ($registro->es_retardo && !$registro->es_justificado) {
                        $status = 'late';
                        $color = 'bg-amber-100 text-amber-700 border-amber-200 font-bold';
                    } else {
                        $status = 'ok';
                        $color = 'bg-emerald-100 text-emerald-700 border-emerald-200 font-bold';
                    }
                } elseif ($registro->tipo_registro == 'falta') {
                    $status = $registro->es_justificado ? 'justified' : 'absent';
                    $color = $registro->es_justificado 
                        ? 'bg-orange-100 text-orange-700 border-orange-200 font-bold' 
                        : 'bg-red-100 text-red-700 border-red-200 font-bold';
                } else {
                    // Vacaciones, incapacidad, etc.
                    $status = 'special';
                    $color = 'bg-blue-100 text-blue-700 border-blue-200 font-bold';
                }

                // Info para mostrar al dar clic
                $info = [
                    'tipo' => ucfirst($registro->tipo_registro),
                    'entrada' => $registro->entrada ? substr($registro->entrada, 0, 5) : '--:--',
                    'salida' => $registro->salida ? substr($registro->salida, 0, 5) : '--:--',
                    'comentarios' => $registro->comentarios,
                    'estado_texto' => $this->getStatusText($registro)
                ];
            } else {
                // Sin registro (Fin de semana o día futuro)
                if ($date->isWeekend()) {
                    $color = 'bg-slate-50 text-slate-400';
                } elseif ($date->isFuture()) {
                    $color = 'bg-white text-slate-300';
                } else {
                    // Día pasado entre semana sin registro (posible falta no generada)
                    $color = 'bg-red-50 text-red-300 border-red-100'; 
                }
            }

            $calendarData[] = [
                'day' => $date->day,
                'full_date' => $fechaStr,
                'weekday_name' => $date->isoFormat('dddd'),
                'color_class' => $color,
                'has_record' => $registro ? true : false,
                'details' => $info
            ];
        }

        // KPIs (igual que antes)
        $kpis = [
            'retardos' => $asistencias->where('es_retardo', true)->where('es_justificado', false)->count(),
            'faltas' => $asistencias->where('tipo_registro', 'falta')->where('es_justificado', false)->count(),
            'horas' => $this->calcularHoras($asistencias),
        ];

        return view('Sistemas_IT.profile.edit', [
            'user' => $user,
            'empleado' => $empleado,
            'calendarData' => $calendarData, // Datos del calendario
            'blankDays' => $blankDays,       // Días vacíos al inicio
            'kpis' => $kpis,
            'periodoActual' => $periodoActual,
        ]);
    }

    // Helper para textos
    private function getStatusText($registro) {
        if ($registro->tipo_registro == 'asistencia') {
            return ($registro->es_retardo && !$registro->es_justificado) ? 'Retardo' : 'Puntual';
        }
        if ($registro->tipo_registro == 'falta') {
            return $registro->es_justificado ? 'Justificada' : 'Falta';
        }
        return ucfirst($registro->tipo_registro);
    }

    // Helper para horas
    private function calcularHoras($asistencias) {
        $minutos = 0;
        foreach ($asistencias as $reg) {
            if ($reg->entrada && $reg->salida) {
                $ent = Carbon::parse($reg->entrada);
                $sal = Carbon::parse($reg->salida);
                if ($sal->gt($ent)) $minutos += $ent->diffInMinutes($sal);
            }
        }
        return sprintf('%02d:%02d', floor($minutos/60), $minutos%60);
    }

    // Métodos update y destroy se quedan igual...
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());
        if ($request->user()->isDirty('email')) $request->user()->email_verified_at = null;
        $request->user()->save();
        return Redirect::route('profile.edit')->with('status', 'profile-updated');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', ['password' => ['required', 'current_password']]);
        $user = $request->user();
        Auth::logout();
        $user->delete();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return Redirect::to('/');
    }
}