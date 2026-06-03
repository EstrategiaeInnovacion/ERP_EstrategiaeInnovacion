@extends('layouts.erp')

@section('title', 'Resultados de Evaluación')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
<div class="min-h-screen bg-slate-50 py-8">
    <div class="container mx-auto px-4 max-w-6xl">
        
        <div class="flex items-center justify-between mb-8">
            <a href="{{ route('rh.evaluacion.index', ['periodo' => $periodo]) }}" class="flex items-center text-slate-500 hover:text-slate-800 font-bold transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Volver al Tablero
            </a>
            <div class="flex items-center gap-3">
                <form method="GET" class="flex items-center gap-2">
                    <span class="text-xs font-bold text-slate-500 uppercase">Periodo:</span>
                    <select name="periodo" onchange="this.form.submit()"
                        class="text-sm bg-white border border-slate-200 rounded-full px-4 py-2 text-slate-700 font-bold shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 cursor-pointer">
                        @foreach($periodos as $p)
                            <option value="{{ $p }}" {{ $periodo == $p ? 'selected' : '' }}>{{ $p }}</option>
                        @endforeach
                    </select>
                </form>
                <a href="{{ route('rh.evaluacion.resultados.excel', ['id' => $empleado->id, 'periodo' => $periodo]) }}"
                   class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full text-xs font-bold shadow-sm transition flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Descargar Excel
                </a>
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
                                    <th class="px-6 py-4 text-center">Tipo</th>
                                    <th class="px-6 py-4 text-center">Nota</th>
                                    <th class="px-6 py-4">Comentarios</th>
                                    <th class="px-6 py-4 text-center w-24">Detalle</th>
                                </tr>
                            </thead>
                                @foreach($desglose as $eval)
                                <tbody x-data="{ open: false }" class="divide-y divide-slate-100">
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
                                            @php
                                                $tipoLabel = match($eval->tipo ?? 'supervisor') {
                                                    'admin_rh' => 'Admin RH',
                                                    'subordinado' => 'Subordinado',
                                                    'autoevaluacion' => 'Autoevaluación',
                                                    default => 'Supervisor',
                                                };
                                                $tipoColor = match($eval->tipo ?? 'supervisor') {
                                                    'admin_rh' => 'bg-purple-50 text-purple-700 border-purple-200',
                                                    'subordinado' => 'bg-teal-50 text-teal-700 border-teal-200',
                                                    'autoevaluacion' => 'bg-amber-50 text-amber-700 border-amber-200',
                                                    default => 'bg-slate-100 text-slate-600 border-slate-200',
                                                };
                                            @endphp
                                            <span class="px-2 py-1 rounded text-[10px] font-bold border {{ $tipoColor }}">
                                                {{ $tipoLabel }}
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
                                        <td class="px-6 py-4 text-center">
                                            <button @click="open = !open" type="button"
                                                class="text-xs font-bold text-indigo-600 hover:text-indigo-800 hover:underline transition whitespace-nowrap">
                                                <span x-show="!open">Ver detalle</span>
                                                <span x-show="open" x-cloak>Ocultar</span>
                                            </button>
                                        </td>
                                    </tr>
                                    <tr x-show="open" x-cloak>
                                        <td colspan="6" class="px-6 py-4 bg-slate-50/80 border-b border-slate-200">
                                            <div class="text-sm font-bold text-slate-700 mb-3 flex items-center gap-2">
                                                <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                                                Desglose de preguntas
                                            </div>
                                            <table class="w-full text-xs">
                                                <thead>
                                                    <tr class="text-slate-500 uppercase font-bold border-b border-slate-200">
                                                        <th class="px-3 py-2 text-left w-1/2">Pregunta</th>
                                                        <th class="px-3 py-2 text-center w-[10%]">Peso</th>
                                                        <th class="px-3 py-2 text-center w-[10%]">Nota</th>
                                                        <th class="px-3 py-2 text-left">Comentario</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    @foreach($eval->detalles as $detalle)
                                                        <tr class="border-b border-slate-100 hover:bg-white transition">
                                                            <td class="px-3 py-2.5 text-slate-700 font-medium">
                                                                {{ $detalle->criterio->descripcion ?? 'Criterio #'.$detalle->criterio_id }}
                                                            </td>
                                                            <td class="px-3 py-2.5 text-center text-slate-400">
                                                                {{ $detalle->criterio->peso ?? '-' }}%
                                                            </td>
                                                            <td class="px-3 py-2.5 text-center font-bold {{ $detalle->calificacion >= 80 ? 'text-emerald-600' : ($detalle->calificacion >= 50 ? 'text-amber-600' : 'text-red-500') }}">
                                                                {{ number_format($detalle->calificacion, 0) }}
                                                            </td>
                                                            <td class="px-3 py-2.5 text-slate-500 italic max-w-xs truncate">
                                                                {{ $detalle->observaciones ?? 'Sin comentario' }}
                                                            </td>
                                                        </tr>
                                                    @endforeach
                                                </tbody>
                                                <tfoot>
                                                    <tr class="bg-slate-100 font-bold text-slate-700">
                                                        <td class="px-3 py-2.5 text-right" colspan="2">Promedio ponderado:</td>
                                                        <td class="px-3 py-2.5 text-center {{ $eval->promedio_final >= 80 ? 'text-emerald-600' : 'text-amber-600' }}">
                                                            {{ number_format($eval->promedio_final, 1) }}
                                                        </td>
                                                        <td></td>
                                                    </tr>
                                                </tfoot>
                                            </table>

                                            <div class="mt-4 p-4 bg-white rounded-xl border border-slate-200">
                                                <div class="flex items-start gap-3">
                                                    <svg class="w-5 h-5 text-slate-400 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                                    <div>
                                                        <p class="text-xs font-bold text-slate-600 uppercase mb-1">Comentario final</p>
                                                        <p class="text-sm text-slate-700 leading-relaxed">{{ $eval->comentarios_generales ?: 'Sin comentario' }}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                                @endforeach
                        </table>
                    </div>
                </div>

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