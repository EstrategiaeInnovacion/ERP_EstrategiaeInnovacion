@extends('layouts.erp')

@section('title', 'Resultados de Evaluación')

@section('content')
<div class="min-h-screen bg-slate-50 py-8">
    <div class="container mx-auto px-4 max-w-6xl">
        
        <div class="flex items-center justify-between mb-8">
            <a href="{{ route('rh.evaluacion.index', ['periodo' => $periodo]) }}" class="flex items-center text-slate-500 hover:text-slate-800 font-bold transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Volver al Tablero
            </a>
            <div class="px-4 py-2 bg-white rounded-full shadow-sm border border-slate-200 text-sm font-bold text-slate-600">
                Periodo: {{ $periodo }}
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            {{-- TARJETA RESUMEN --}}
            <div class="lg:col-span-1 space-y-6">
                <div class="bg-white rounded-3xl shadow-xl border border-slate-100 overflow-hidden text-center p-8">
                    <div class="w-24 h-24 mx-auto rounded-full bg-slate-100 border-4 border-white shadow-lg flex items-center justify-center text-3xl font-bold text-slate-400 mb-4">
                        {{ substr($empleado->nombre, 0, 1) }}
                    </div>
                    <h2 class="text-xl font-bold text-slate-800">{{ $empleado->nombre }} {{ $empleado->apellido_paterno }}</h2>
                    <p class="text-indigo-500 font-medium text-sm mb-6">{{ $empleado->posicion }}</p>

                    <div class="py-6 border-t border-slate-100">
                        <span class="block text-slate-400 text-xs font-bold uppercase tracking-wider mb-1">Calificación Final</span>
                        <div class="inline-flex items-baseline justify-center">
                            <span class="text-5xl font-extrabold {{ $promedioGeneral >= 90 ? 'text-emerald-500' : ($promedioGeneral >= 70 ? 'text-blue-500' : 'text-amber-500') }}">
                                {{ number_format($promedioGeneral, 1) }}
                            </span>
                            <span class="text-slate-400 font-bold ml-1">/ 100</span>
                        </div>
                    </div>
                    
                    <div class="mt-4 bg-slate-50 rounded-xl p-3 text-xs text-slate-500">
                        Basado en {{ $desglose->count() }} evaluaciones recibidas
                    </div>
                </div>
            </div>

            {{-- DETALLE POR EVALUADOR --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-3xl shadow-lg border border-slate-100 overflow-hidden">
                    <div class="bg-slate-900 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                        <h3 class="font-bold text-white">Desglose por Evaluador</h3>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm text-slate-600">
                            <thead class="bg-slate-50 text-xs uppercase font-bold text-slate-500">
                                <tr>
                                    <th class="px-6 py-4">Evaluador</th>
                                    <th class="px-6 py-4">Relación</th>
                                    <th class="px-6 py-4 text-center">Nota</th>
                                    <th class="px-6 py-4">Comentarios</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($desglose as $eval)
                                    <tr class="hover:bg-slate-50 transition">
                                        <td class="px-6 py-4 font-bold text-slate-800">
                                            {{ $eval->nombre_evaluador }}
                                        </td>
                                        <td class="px-6 py-4">
                                            <span class="px-2 py-1 rounded text-[10px] font-bold border 
                                                {{ $eval->rol_evaluador == 'Supervisor Directo' ? 'bg-purple-50 text-purple-700 border-purple-100' : 
                                                  ($eval->rol_evaluador == 'Subordinado' ? 'bg-blue-50 text-blue-700 border-blue-100' : 'bg-slate-100 text-slate-600 border-slate-200') }}">
                                                {{ $eval->rol_evaluador }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <span class="font-bold {{ $eval->promedio_final >= 80 ? 'text-emerald-600' : 'text-amber-600' }}">
                                                {{ number_format($eval->promedio_final, 1) }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-xs italic text-slate-500 max-w-xs truncate">
                                            {{ $eval->comentarios_generales ?? 'Sin comentarios' }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                {{-- DETALLE TÉCNICO (Opcional: Mostrar preguntas si quieres profundizar más) --}}
                <div class="mt-6 text-center">
                    <p class="text-xs text-slate-400">
                        * El promedio final es el promedio simple de todas las evaluaciones recibidas.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection