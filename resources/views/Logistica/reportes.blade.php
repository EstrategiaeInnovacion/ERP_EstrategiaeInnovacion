@extends('layouts.erp')
@section('title', 'Reportes — Logística')

@section('content')
<div class="min-h-screen bg-slate-50 pb-12">
    <div class="max-w-[1400px] mx-auto sm:px-6 lg:px-8 space-y-6">

        {{-- HEADER --}}
        <div class="bg-white border-b border-slate-200 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                        <a href="{{ route('logistica.index') }}" class="hover:text-violet-600 transition-colors">Panel Logística</a>
                        <span>/</span>
                        <span class="text-slate-700 font-medium">Reportes</span>
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900">Reportes Operativos</h1>
                    <p class="text-slate-500 mt-1 text-sm">Análisis y métricas de operaciones de comercio exterior.</p>
                </div>
                <span class="text-xs text-slate-400 font-medium">{{ \Carbon\Carbon::now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</span>
            </div>
        </div>

        {{-- FILTER BAR --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm px-5 py-4">
            <form method="GET" action="{{ route('logistica.reportes') }}" class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1.5">Período</label>
                    <select name="periodo"
                            class="border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-400 bg-white min-w-[180px]">
                        <option value="semana"  {{ $periodo === 'semana'  ? 'selected' : '' }}>Última semana</option>
                        <option value="mes"     {{ $periodo === 'mes'     ? 'selected' : '' }}>Último mes</option>
                        <option value="año"     {{ $periodo === 'año'     ? 'selected' : '' }}>Último año</option>
                        <option value="rango"   {{ $periodo === 'rango'   ? 'selected' : '' }}>Rango personalizado</option>
                    </select>
                </div>
                <div id="rango-campos" class="{{ $periodo === 'rango' ? 'flex' : 'hidden' }} items-end gap-2">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1.5">Desde</label>
                        <input type="date" name="desde" value="{{ $filtroDesde }}"
                               class="border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-400">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 mb-1.5">Hasta</label>
                        <input type="date" name="hasta" value="{{ $filtroHasta }}"
                               class="border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-400">
                    </div>
                </div>
                <button type="submit" name="aplicar" value="1"
                        class="px-4 py-2 rounded-xl text-white font-bold text-sm transition shadow-sm hover:shadow-md"
                        style="background: linear-gradient(135deg, #7c3aed 0%, #6d28d9 100%);">
                    Aplicar filtro
                </button>
                @if($filtroAplicado)
                    <a href="{{ route('logistica.reportes') }}"
                       class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-semibold text-sm hover:bg-slate-50 transition">
                        Limpiar
                    </a>
                    <span class="text-xs text-slate-500 self-center">
                        Mostrando:
                        @if($periodo === 'semana') última semana
                        @elseif($periodo === 'mes') último mes
                        @elseif($periodo === 'año') último año
                        @elseif($filtroDesde && $filtroHasta) {{ \Carbon\Carbon::parse($filtroDesde)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($filtroHasta)->format('d/m/Y') }}
                        @endif
                        &mdash; <strong>{{ $operacionesFiltradas->count() }} operaciones</strong>
                    </span>
                @endif
            </form>
        </div>

        {{-- KPI CARDS --}}
        <div class="grid grid-cols-2 lg:grid-cols-5 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Total operaciones</p>
                <p class="text-3xl font-bold text-slate-800">{{ $totalOps }}</p>
                <p class="text-xs text-slate-400 mt-1">Historial completo</p>
            </div>
            <div class="bg-white rounded-2xl border border-emerald-200 shadow-sm p-5">
                <p class="text-xs font-bold text-emerald-500 uppercase tracking-wider mb-1">Exitosas</p>
                <p class="text-3xl font-bold text-emerald-600">{{ $exitosas }}</p>
                <p class="text-xs text-slate-400 mt-1">Entregadas a tiempo</p>
            </div>
            <div class="bg-white rounded-2xl border border-amber-200 shadow-sm p-5">
                <p class="text-xs font-bold text-amber-500 uppercase tracking-wider mb-1">Demoradas</p>
                <p class="text-3xl font-bold text-amber-600">{{ $demoradas }}</p>
                <p class="text-xs text-slate-400 mt-1">Fuera de target</p>
            </div>
            <div class="bg-white rounded-2xl border border-blue-200 shadow-sm p-5">
                <p class="text-xs font-bold text-blue-500 uppercase tracking-wider mb-1">En proceso</p>
                <p class="text-3xl font-bold text-blue-600">{{ $enProceso }}</p>
                <p class="text-xs text-slate-400 mt-1">Operaciones activas</p>
            </div>
            <div class="bg-white rounded-2xl border border-violet-200 shadow-sm p-5">
                <p class="text-xs font-bold text-violet-500 uppercase tracking-wider mb-1">Eficiencia</p>
                <p class="text-3xl font-bold text-violet-600">{{ $eficiencia }}%</p>
                <p class="text-xs text-slate-400 mt-1">Exitosas / completadas</p>
            </div>
        </div>

        {{-- SEMANA ACTUAL + RANKING --}}
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

            {{-- Semana actual --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-slate-800">Operaciones — Semana Actual</h3>
                        <p class="text-xs text-slate-400 mt-0.5">{{ now()->startOfWeek()->format('d/m/Y') }} al {{ now()->endOfWeek()->format('d/m/Y') }}</p>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-violet-100 text-violet-700">{{ $semanaActual->count() }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th class="px-4 py-2.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Cliente</th>
                                <th class="px-4 py-2.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Cliente / Proveedor</th>
                                <th class="px-4 py-2.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Analista</th>
                                <th class="px-4 py-2.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($semanaActual as $reg)
                            @php
                                $sc = [
                                    'Pendiente'         => 'bg-slate-100 text-slate-600',
                                    'En Tránsito'       => 'bg-blue-100 text-blue-700',
                                    'En Aduana'         => 'bg-yellow-100 text-yellow-700',
                                    'Previo Programado' => 'bg-purple-100 text-purple-700',
                                    'Cita Programada'   => 'bg-indigo-100 text-indigo-700',
                                    'Despachado'        => 'bg-cyan-100 text-cyan-700',
                                    'Entregado'         => 'bg-emerald-100 text-emerald-700',
                                    'Cancelado'         => 'bg-red-100 text-red-600',
                                ][$reg->status] ?? 'bg-slate-100 text-slate-600';
                            @endphp
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-2.5 text-slate-700 font-semibold text-xs whitespace-nowrap">{{ $reg->proveedor_cliente ?? '—' }}</td>
                                <td class="px-4 py-2.5 text-slate-600 text-xs whitespace-nowrap">{{ $reg->cliente_operacion ?? '—' }}</td>
                                <td class="px-4 py-2.5 text-slate-500 text-xs whitespace-nowrap">{{ $reg->user?->name ?? '—' }}</td>
                                <td class="px-4 py-2.5 whitespace-nowrap">
                                    @if($reg->status)
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $sc }}">{{ $reg->status }}</span>
                                    @else
                                        <span class="text-slate-300">—</span>
                                    @endif
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-slate-400 text-xs">Sin operaciones esta semana.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Ranking últimos 7 días --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="font-bold text-slate-800">Ranking — Últimos 7 Días</h3>
                        <p class="text-xs text-slate-400 mt-0.5">Operaciones con actividad reciente</p>
                    </div>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700">{{ $ultimosSieteDias->count() }}</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-100">
                                <th class="px-4 py-2.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">#</th>
                                <th class="px-4 py-2.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Cliente</th>
                                <th class="px-4 py-2.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Cliente / Proveedor</th>
                                <th class="px-4 py-2.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Analista</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($ultimosSieteDias as $i => $reg)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-4 py-2.5">
                                    <span class="w-6 h-6 rounded-full flex items-center justify-center text-xs font-bold
                                        {{ $i === 0 ? 'bg-amber-400 text-white' : ($i === 1 ? 'bg-slate-300 text-slate-700' : ($i === 2 ? 'bg-orange-300 text-white' : 'bg-slate-100 text-slate-500')) }}">
                                        {{ $i + 1 }}
                                    </span>
                                </td>
                                <td class="px-4 py-2.5 text-slate-700 font-semibold text-xs whitespace-nowrap">{{ $reg->proveedor_cliente ?? '—' }}</td>
                                <td class="px-4 py-2.5 text-slate-600 text-xs whitespace-nowrap">{{ $reg->cliente_operacion ?? '—' }}</td>
                                <td class="px-4 py-2.5 text-slate-500 text-xs whitespace-nowrap">{{ $reg->user?->name ?? '—' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-4 py-8 text-center text-slate-400 text-xs">Sin actividad en los últimos 7 días.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- GRÁFICAS --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Pie: distribución por status --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <h3 class="font-bold text-slate-800 mb-1">Estado de Operaciones</h3>
                <p class="text-xs text-slate-400 mb-4">Distribución por status</p>
                <div class="relative h-56">
                    <canvas id="chart-pie"></canvas>
                </div>
                <div class="mt-4 space-y-1.5">
                    @foreach($statsByStatus as $status => $count)
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-600">{{ $status }}</span>
                        <span class="font-bold text-slate-800">{{ $count }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Line: ops por día --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <h3 class="font-bold text-slate-800 mb-1">Operaciones por Día</h3>
                <p class="text-xs text-slate-400 mb-4">Últimos 30 días</p>
                <div class="relative h-56">
                    <canvas id="chart-line"></canvas>
                </div>
            </div>

            {{-- Bar: por tipo de operación --}}
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                <h3 class="font-bold text-slate-800 mb-1">Por Tipo de Operación</h3>
                <p class="text-xs text-slate-400 mb-4">Distribución histórica</p>
                <div class="relative h-56">
                    <canvas id="chart-bar"></canvas>
                </div>
                <div class="mt-4 space-y-1.5">
                    @foreach($statsByTipo as $tipo => $count)
                    <div class="flex items-center justify-between text-xs">
                        <span class="text-slate-600">{{ $tipo }}</span>
                        <span class="font-bold text-slate-800">{{ $count }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- ANÁLISIS DE EFICIENCIA --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
            <h3 class="font-bold text-slate-800 text-lg mb-1">Análisis de Eficiencia General</h3>
            <p class="text-xs text-slate-400 mb-6">Basado en el historial completo de operaciones</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                {{-- Barra de eficiencia --}}
                <div class="space-y-4">
                    <div>
                        <div class="flex justify-between text-sm mb-1.5">
                            <span class="font-semibold text-slate-700">Tasa de éxito</span>
                            <span class="font-bold text-emerald-600">{{ $eficiencia }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-3">
                            <div class="h-3 rounded-full bg-gradient-to-r from-emerald-400 to-emerald-600 transition-all"
                                 style="width: {{ $eficiencia }}%"></div>
                        </div>
                    </div>

                    @php
                        $pctDemoradas = $completadas > 0 ? round(($demoradas / $completadas) * 100, 1) : 0;
                        $pctCanceladas = $completadas > 0 ? round(($canceladas / $completadas) * 100, 1) : 0;
                    @endphp

                    <div>
                        <div class="flex justify-between text-sm mb-1.5">
                            <span class="font-semibold text-slate-700">Operaciones demoradas</span>
                            <span class="font-bold text-amber-600">{{ $pctDemoradas }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-3">
                            <div class="h-3 rounded-full bg-gradient-to-r from-amber-400 to-amber-500"
                                 style="width: {{ $pctDemoradas }}%"></div>
                        </div>
                    </div>

                    <div>
                        <div class="flex justify-between text-sm mb-1.5">
                            <span class="font-semibold text-slate-700">Canceladas</span>
                            <span class="font-bold text-red-600">{{ $pctCanceladas }}%</span>
                        </div>
                        <div class="w-full bg-slate-100 rounded-full h-3">
                            <div class="h-3 rounded-full bg-gradient-to-r from-red-400 to-red-500"
                                 style="width: {{ $pctCanceladas }}%"></div>
                        </div>
                    </div>
                </div>

                {{-- Tabla resumen --}}
                <div class="space-y-2">
                    @php
                        $resumen = [
                            ['label' => 'Total operaciones registradas', 'valor' => $totalOps,    'color' => 'text-slate-700'],
                            ['label' => 'Operaciones completadas',       'valor' => $completadas, 'color' => 'text-slate-700'],
                            ['label' => 'En proceso actualmente',        'valor' => $enProceso,   'color' => 'text-blue-600'],
                            ['label' => 'Entregadas exitosamente',       'valor' => $exitosas,    'color' => 'text-emerald-600'],
                            ['label' => 'Entregadas con demora',         'valor' => $demoradas,   'color' => 'text-amber-600'],
                            ['label' => 'Canceladas',                    'valor' => $canceladas,  'color' => 'text-red-600'],
                        ];
                    @endphp
                    @foreach($resumen as $r)
                    <div class="flex items-center justify-between py-2 border-b border-slate-50 last:border-0">
                        <span class="text-sm text-slate-500">{{ $r['label'] }}</span>
                        <span class="text-sm font-bold {{ $r['color'] }}">{{ $r['valor'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>

            {{-- Top clientes --}}
            @if(count($topClientes) > 0)
            <div class="mt-6 pt-6 border-t border-slate-100">
                <h4 class="font-bold text-slate-700 text-sm mb-3">Top Clientes por Volumen de Operaciones</h4>
                <div class="space-y-2">
                    @php $maxVal = max($topClientes); @endphp
                    @foreach($topClientes as $cliente => $count)
                    <div class="flex items-center gap-3">
                        <span class="text-xs text-slate-600 w-40 truncate flex-shrink-0">{{ $cliente }}</span>
                        <div class="flex-1 bg-slate-100 rounded-full h-2">
                            <div class="h-2 rounded-full" style="width: {{ round(($count / $maxVal) * 100) }}%; background: linear-gradient(90deg, #7c3aed, #a78bfa);"></div>
                        </div>
                        <span class="text-xs font-bold text-slate-700 w-6 text-right flex-shrink-0">{{ $count }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

        {{-- OPERACIONES FILTRADAS (solo con filtro aplicado) --}}
        @if($filtroAplicado)
        <div class="bg-white rounded-2xl border border-violet-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-violet-100 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-800">Operaciones del Período Filtrado</h3>
                    <p class="text-xs text-slate-400 mt-0.5">
                        @if($periodo === 'semana') Última semana
                        @elseif($periodo === 'mes') Último mes
                        @elseif($periodo === 'año') Último año
                        @elseif($filtroDesde && $filtroHasta) {{ \Carbon\Carbon::parse($filtroDesde)->format('d/m/Y') }} — {{ \Carbon\Carbon::parse($filtroHasta)->format('d/m/Y') }}
                        @endif
                    </p>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-violet-100 text-violet-700">{{ $operacionesFiltradas->count() }} registros</span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm" style="min-width: 900px;">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="px-4 py-2.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Ref. Interna</th>
                            <th class="px-4 py-2.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Cliente</th>
                            <th class="px-4 py-2.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Cliente / Proveedor</th>
                            <th class="px-4 py-2.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Analista</th>
                            <th class="px-4 py-2.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Tipo</th>
                            <th class="px-4 py-2.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">IMP / EXP</th>
                            <th class="px-4 py-2.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">ETA</th>
                            <th class="px-4 py-2.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Status</th>
                            <th class="px-4 py-2.5 text-left text-xs font-bold text-slate-400 uppercase tracking-wider">Resultado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @forelse($operacionesFiltradas as $reg)
                        @php
                            $sc = [
                                'Pendiente'         => 'bg-slate-100 text-slate-600',
                                'En Tránsito'       => 'bg-blue-100 text-blue-700',
                                'En Aduana'         => 'bg-yellow-100 text-yellow-700',
                                'Previo Programado' => 'bg-purple-100 text-purple-700',
                                'Cita Programada'   => 'bg-indigo-100 text-indigo-700',
                                'Despachado'        => 'bg-cyan-100 text-cyan-700',
                                'Entregado'         => 'bg-emerald-100 text-emerald-700',
                                'Cancelado'         => 'bg-red-100 text-red-600',
                            ][$reg->status] ?? 'bg-slate-100 text-slate-600';
                            $rc = [
                                'En Proceso' => 'bg-blue-100 text-blue-700',
                                'Exitoso'    => 'bg-emerald-100 text-emerald-700',
                                'Demorado'   => 'bg-amber-100 text-amber-700',
                                'Cancelado'  => 'bg-red-100 text-red-600',
                            ][$reg->resultado] ?? 'bg-slate-100 text-slate-600';
                        @endphp
                        <tr class="hover:bg-violet-50/30 transition-colors">
                            <td class="px-4 py-2.5 font-mono text-xs text-slate-600">{{ $reg->ref_interna ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-xs font-semibold text-slate-700 whitespace-nowrap">{{ $reg->proveedor_cliente ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-xs text-slate-600 whitespace-nowrap">{{ $reg->cliente_operacion ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-xs text-slate-500 whitespace-nowrap">{{ $reg->user?->name ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-xs text-slate-600 whitespace-nowrap">{{ $reg->tipo_operacion ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-xs text-slate-600">{{ $reg->impo_ex ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-xs text-slate-600 whitespace-nowrap">{{ $reg->eta ? $reg->eta->format('d/m/Y') : '—' }}</td>
                            <td class="px-4 py-2.5 whitespace-nowrap">
                                @if($reg->status)
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $sc }}">{{ $reg->status }}</span>
                                @else <span class="text-slate-300">—</span> @endif
                            </td>
                            <td class="px-4 py-2.5 whitespace-nowrap">
                                @if($reg->resultado)
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $rc }}">{{ $reg->resultado }}</span>
                                @else <span class="text-slate-300">—</span> @endif
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="9" class="px-4 py-10 text-center text-slate-400 text-sm">Sin operaciones en este período.</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @endif

    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
// ── Datos desde PHP ───────────────────────────────────────────────────────────
const pieLabels  = @json(array_keys($statsByStatus));
const pieData    = @json(array_values($statsByStatus));
const lineLabels = @json($lineLabels);
const lineData   = @json($lineData);
const barLabels  = @json(array_keys($statsByTipo));
const barData    = @json(array_values($statsByTipo));

const statusColors = {
    'Pendiente':         '#94a3b8',
    'En Tránsito':       '#60a5fa',
    'En Aduana':         '#fbbf24',
    'Previo Programado': '#a78bfa',
    'Cita Programada':   '#818cf8',
    'Despachado':        '#22d3ee',
    'Entregado':         '#34d399',
    'Cancelado':         '#f87171',
};
const pieColors = pieLabels.map(l => statusColors[l] || '#cbd5e1');

Chart.defaults.font.family = "'Inter', 'system-ui', sans-serif";
Chart.defaults.font.size   = 12;

// ── PIE ───────────────────────────────────────────────────────────────────────
new Chart(document.getElementById('chart-pie'), {
    type: 'doughnut',
    data: {
        labels: pieLabels,
        datasets: [{
            data: pieData,
            backgroundColor: pieColors,
            borderWidth: 2,
            borderColor: '#fff',
            hoverOffset: 6,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        cutout: '60%',
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => ` ${ctx.label}: ${ctx.parsed} (${Math.round(ctx.parsed / ctx.dataset.data.reduce((a,b)=>a+b,0) * 100)}%)`
                }
            }
        }
    }
});

// ── LINE ──────────────────────────────────────────────────────────────────────
new Chart(document.getElementById('chart-line'), {
    type: 'line',
    data: {
        labels: lineLabels,
        datasets: [{
            label: 'Operaciones',
            data: lineData,
            borderColor: '#7c3aed',
            backgroundColor: 'rgba(124,58,237,0.08)',
            borderWidth: 2,
            pointRadius: 3,
            pointBackgroundColor: '#7c3aed',
            fill: true,
            tension: 0.4,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1, color: '#94a3b8' },
                grid:  { color: '#f1f5f9' },
            },
            x: {
                ticks: {
                    color: '#94a3b8',
                    maxTicksLimit: 10,
                    maxRotation: 0,
                },
                grid: { display: false },
            }
        },
        plugins: { legend: { display: false } }
    }
});

// ── BAR ───────────────────────────────────────────────────────────────────────
const barColors = ['#7c3aed','#a78bfa','#6d28d9','#8b5cf6'];
new Chart(document.getElementById('chart-bar'), {
    type: 'bar',
    data: {
        labels: barLabels,
        datasets: [{
            label: 'Operaciones',
            data: barData,
            backgroundColor: barLabels.map((_, i) => barColors[i % barColors.length]),
            borderRadius: 8,
            borderSkipped: false,
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: true,
        scales: {
            y: {
                beginAtZero: true,
                ticks: { stepSize: 1, color: '#94a3b8' },
                grid:  { color: '#f1f5f9' },
            },
            x: {
                ticks: { color: '#94a3b8' },
                grid:  { display: false },
            }
        },
        plugins: { legend: { display: false } }
    }
});

// ── Toggle rango personalizado ────────────────────────────────────────────────
document.querySelector('select[name="periodo"]').addEventListener('change', function () {
    const campos = document.getElementById('rango-campos');
    campos.classList.toggle('hidden', this.value !== 'rango');
    campos.classList.toggle('flex',   this.value === 'rango');
});
</script>
@endpush
