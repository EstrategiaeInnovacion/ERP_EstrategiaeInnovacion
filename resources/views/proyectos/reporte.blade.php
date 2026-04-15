@extends('layouts.erp')

@section('title', "Reporte - {$proyecto->nombre}")

@section('content')
@php
    $esRh = $esRh ?? false;
@endphp

<style>
    @page {
        size: A4;
        margin: 1.5cm;
    }
    
    @media print {
        * {
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        
        body {
            background: white !important;
            font-size: 11px !important;
            line-height: 1.4 !important;
        }
        
        .no-print { display: none !important; }
        
        .page-break { page-break-after: always; }
        
        .print-container {
            max-width: 100% !important;
            padding: 0 !important;
            margin: 0 !important;
        }
        
        .shadow-sm, .border {
            box-shadow: none !important;
            border: 1px solid #cbd5e1 !important;
        }
        
        .rounded-xl { border-radius: 8px !important; }
        
        table {
            font-size: 9px !important;
        }
        
        th, td {
            padding: 6px 8px !important;
        }
    }
    
    /* Estilos para pantalla */
    .print-container {
        background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%);
        min-height: 100vh;
        padding: 2rem;
    }
</style>

<div class="print-container">
    {{-- Encabezado --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-6 overflow-hidden">
        <div class="bg-gradient-to-r from-indigo-600 via-indigo-700 to-indigo-800 px-8 py-6">
            <div class="flex justify-between items-center">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-white/20 rounded-lg flex items-center justify-center backdrop-blur">
                        <svg class="w-6 h-6 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                    <div>
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 bg-emerald-400 text-white text-xs font-bold rounded uppercase tracking-wide">Finalizado</span>
                            <span class="text-indigo-200 text-xs uppercase tracking-wider">Reporte Oficial</span>
                        </div>
                        <h1 class="text-2xl font-bold text-white">{{ $proyecto->nombre }}</h1>
                    </div>
                </div>
                <div class="no-print">
                    <button onclick="window.print()" class="bg-white text-indigo-600 px-5 py-2.5 rounded-lg font-bold text-sm hover:bg-indigo-50 transition shadow-lg flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                        Exportar PDF
                    </button>
                </div>
            </div>
        </div>
        
        <div class="px-8 py-5">
            @if($proyecto->descripcion)
            <p class="text-slate-600 text-sm mb-5">{{ $proyecto->descripcion }}</p>
            @endif
            
            <div class="grid grid-cols-4 gap-6">
                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                    <div class="text-slate-400 text-xs uppercase tracking-wider mb-1">Fecha Inicio</div>
                    <div class="text-slate-800 font-bold">{{ \Carbon\Carbon::parse($proyecto->fecha_inicio)->format('d/m/Y') }}</div>
                </div>
                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                    <div class="text-slate-400 text-xs uppercase tracking-wider mb-1">Fecha Fin Planeada</div>
                    <div class="text-slate-800 font-bold">{{ \Carbon\Carbon::parse($proyecto->fecha_fin)->format('d/m/Y') }}</div>
                </div>
                <div class="bg-emerald-50 rounded-lg p-4 border border-emerald-200">
                    <div class="text-emerald-600 text-xs uppercase tracking-wider mb-1">Fecha Fin Real</div>
                    <div class="text-emerald-700 font-bold">{{ \Carbon\Carbon::parse($proyecto->fecha_fin_real)->format('d/m/Y') }}</div>
                </div>
                <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                    <div class="text-slate-400 text-xs uppercase tracking-wider mb-1">Responsable</div>
                    <div class="text-slate-800 font-bold">{{ $proyecto->creador->name ?? 'N/A' }}</div>
                </div>
            </div>
            
            @if($proyecto->usuarios->count() > 0)
            <div class="mt-5 pt-4 border-t border-slate-200">
                <div class="text-slate-400 text-xs uppercase tracking-wider mb-2">Equipo de Trabajo</div>
                <div class="flex flex-wrap gap-2">
                    @foreach($proyecto->usuarios as $usu)
                        <span class="bg-indigo-50 text-indigo-700 px-3 py-1 rounded-full text-xs font-medium border border-indigo-200">{{ $usu->name }}</span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>

    {{-- Métricas --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 mb-6 p-6">
        <h3 class="text-sm font-bold text-slate-800 uppercase tracking-wider mb-4 flex items-center gap-2">
            <span class="w-1 h-4 bg-indigo-600 rounded-full"></span>
            Resumen de Métricas
        </h3>
        
        <div class="grid grid-cols-4 gap-4 mb-4">
            <div class="bg-gradient-to-br from-slate-700 to-slate-800 rounded-lg p-4 text-white text-center">
                <div class="text-3xl font-bold">{{ $metricas['total'] }}</div>
                <div class="text-slate-300 text-xs">Total</div>
            </div>
            <div class="bg-gradient-to-br from-emerald-500 to-emerald-600 rounded-lg p-4 text-white text-center">
                <div class="text-3xl font-bold">{{ $metricas['completadas'] }}</div>
                <div class="text-emerald-100 text-xs">Completadas</div>
            </div>
            <div class="bg-gradient-to-br from-blue-500 to-blue-600 rounded-lg p-4 text-white text-center">
                <div class="text-3xl font-bold">{{ $metricas['a_tiempo'] }}</div>
                <div class="text-blue-100 text-xs">A Tiempo</div>
            </div>
            <div class="bg-gradient-to-br from-red-500 to-red-600 rounded-lg p-4 text-white text-center">
                <div class="text-3xl font-bold">{{ $metricas['con_retraso'] }}</div>
                <div class="text-red-100 text-xs">Con Retraso</div>
            </div>
        </div>
        
        <div class="grid grid-cols-3 gap-4">
            <div class="bg-amber-50 rounded-lg p-4 border border-amber-200 text-center">
                <div class="text-2xl font-bold text-amber-600">{{ $metricas['porcentaje_completado'] }}%</div>
                <div class="text-amber-700 text-xs">Completado</div>
            </div>
            <div class="bg-purple-50 rounded-lg p-4 border border-purple-200 text-center">
                <div class="text-2xl font-bold text-purple-600">{{ $metricas['promedio_eficiencia'] }}%</div>
                <div class="text-purple-700 text-xs">Eficiencia</div>
            </div>
            <div class="bg-indigo-50 rounded-lg p-4 border border-indigo-200 text-center">
                <div class="text-2xl font-bold text-indigo-600">{{ $metricas['en_proceso'] }}</div>
                <div class="text-indigo-700 text-xs">Pendientes</div>
            </div>
        </div>
    </div>

    {{-- Tabla de Actividades --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden mb-6">
        <div class="bg-slate-800 px-6 py-3 flex items-center gap-3">
            <svg class="w-5 h-5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <h3 class="text-white font-bold">Detalle de Actividades</h3>
        </div>
        
        @if($actividades->isEmpty())
            <div class="p-8 text-center text-slate-400">No hay actividades registradas</div>
        @else
            <table class="w-full text-sm">
                <thead class="bg-slate-100 border-b border-slate-300">
                    <tr>
                        <th class="text-left py-3 px-4 font-bold text-slate-600 text-xs uppercase">#</th>
                        <th class="text-left py-3 px-4 font-bold text-slate-600 text-xs uppercase">Actividad</th>
                        <th class="text-left py-3 px-4 font-bold text-slate-600 text-xs uppercase">Responsable</th>
                        <th class="text-center py-3 px-4 font-bold text-slate-600 text-xs uppercase">Estatus</th>
                        <th class="text-center py-3 px-4 font-bold text-slate-600 text-xs uppercase">Días Plan.</th>
                        <th class="text-center py-3 px-4 font-bold text-slate-600 text-xs uppercase">Días Reales</th>
                        <th class="text-center py-3 px-4 font-bold text-slate-600 text-xs uppercase">Eficiencia</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    @foreach($actividades as $index => $act)
                    <tr class="hover:bg-slate-50">
                        <td class="py-2 px-4 text-slate-400 font-medium">{{ $index + 1 }}</td>
                        <td class="py-2 px-4">
                            <div class="font-medium text-slate-800">{{ $act->nombre_actividad }}</div>
                            @if($act->cliente)<div class="text-indigo-600 text-xs">{{ $act->cliente }}</div>@endif
                        </td>
                        <td class="py-2 px-4 text-slate-600">{{ $act->user->name ?? 'N/A' }}</td>
                        <td class="py-2 px-4 text-center">
                            @php
                                $colors = [
                                    'Completado' => 'bg-emerald-100 text-emerald-700',
                                    'Completado con retardo' => 'bg-red-100 text-red-700',
                                    'En proceso' => 'bg-blue-100 text-blue-700',
                                    'Planeado' => 'bg-slate-200 text-slate-600',
                                    'Por Aprobar' => 'bg-amber-100 text-amber-700',
                                    'Por Validar' => 'bg-purple-100 text-purple-700',
                                    'Retardo' => 'bg-red-100 text-red-700',
                                    'Rechazado' => 'bg-red-100 text-red-700',
                                ];
                            @endphp
                            <span class="px-2 py-0.5 rounded text-xs font-medium {{ $colors[$act->estatus] ?? 'bg-slate-200' }}">{{ $act->estatus }}</span>
                        </td>
                        <td class="py-2 px-4 text-center text-slate-600 font-medium">{{ $act->metrico ?? '-' }}</td>
                        <td class="py-2 px-4 text-center text-slate-600 font-medium">{{ $act->resultado_dias ?? '-' }}</td>
                        <td class="py-2 px-4 text-center">
                            @if($act->porcentaje)
                                @if($act->porcentaje == 100)
                                    <span class="text-emerald-600 font-bold">✓ {{ $act->porcentaje }}%</span>
                                @elseif($act->porcentaje >= 50)
                                    <span class="text-amber-600 font-bold">{{ $act->porcentaje }}%</span>
                                @else
                                    <span class="text-red-600 font-bold">{{ $act->porcentaje }}%</span>
                                @endif
                            @else
                                <span class="text-slate-400">-</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>

    {{-- Notas --}}
    @if($proyecto->notas)
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
        <h4 class="text-amber-800 font-bold text-sm mb-2">📝 Notas</h4>
        <p class="text-amber-900 text-sm">{{ $proyecto->notas }}</p>
    </div>
    @endif

    {{-- Footer --}}
    <div class="text-center pt-4 border-t border-slate-200">
        <p class="text-slate-500 text-xs">Reporte generado el {{ now()->format('d/m/Y H:i') }} | Estrategia e Innovación</p>
    </div>
</div>
@endsection