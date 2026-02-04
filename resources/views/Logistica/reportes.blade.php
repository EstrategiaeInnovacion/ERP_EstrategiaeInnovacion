@extends('layouts.erp')

@section('title', 'Reportes y Métricas - Logística')

@push('scripts')
    {{-- Librería de Gráficas (Chart.js) --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Datos traídos desde Laravel
            const stats = @json($statsTemporales);

            // 1. Gráfica de Donas (Estatus General)
            const ctxStatus = document.getElementById('chartStatus').getContext('2d');
            new Chart(ctxStatus, {
                type: 'doughnut',
                data: {
                    labels: ['En Tiempo', 'En Riesgo', 'Retrasado', 'Completado OK', 'Completado Tarde'],
                    datasets: [{
                        data: [
                            stats.en_tiempo, 
                            stats.en_riesgo, 
                            stats.con_retraso,
                            stats.completado_tiempo,
                            stats.completado_retraso
                        ],
                        backgroundColor: ['#10b981', '#f59e0b', '#ef4444', '#059669', '#b91c1c'],
                        borderWidth: 0,
                        hoverOffset: 4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { position: 'right', labels: { usePointStyle: true, boxWidth: 6 } }
                    },
                    cutout: '75%'
                }
            });

            // 2. Gráfica de Barras (Desempeño)
            // Calculamos eficiencia (Operaciones OK vs Total)
            const totalOps = stats.total_operaciones || 1;
            const opsExitosas = stats.en_tiempo + stats.completado_tiempo;
            const eficiencia = Math.round((opsExitosas / totalOps) * 100);

            const ctxEficiencia = document.getElementById('chartEficiencia').getContext('2d');
            new Chart(ctxEficiencia, {
                type: 'bar',
                data: {
                    labels: ['Eficiencia Global (%)', 'Promedio Target (Días)', 'Promedio Real (Días)'],
                    datasets: [{
                        label: 'Métricas',
                        data: [
                            eficiencia, 
                            parseFloat(stats.promedio_target).toFixed(1), 
                            parseFloat(stats.promedio_dias).toFixed(1)
                        ],
                        backgroundColor: ['#3b82f6', '#6366f1', '#8b5cf6'],
                        borderRadius: 6,
                        barThickness: 40
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { y: { beginAtZero: true } },
                    plugins: { legend: { display: false } }
                }
            });
        });
    </script>
@endpush

@section('content')
    <div class="min-h-screen bg-slate-50 pb-12">
        <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- 1. ENCABEZADO Y FILTROS --}}
            <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 mt-6">
                <div class="flex flex-col md:flex-row md:items-center justify-between gap-6">
                    <div>
                        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Reportes Ejecutivos</h1>
                        <p class="text-slate-500 text-sm">Análisis de KPI's, tiempos de tránsito y cumplimiento.</p>
                    </div>

                    <form method="GET" action="{{ route('logistica.reportes.index') }}" class="flex flex-wrap items-end gap-3 bg-slate-50 p-2 rounded-xl border border-slate-200">
                        <div>
                            <select name="periodo" class="bg-transparent border-none text-sm font-medium text-slate-700 focus:ring-0 cursor-pointer">
                                <option value="">Todo el Histórico</option>
                                <option value="semanal" {{ request('periodo') == 'semanal' ? 'selected' : '' }}>Última Semana</option>
                                <option value="mensual" {{ request('periodo') == 'mensual' ? 'selected' : '' }}>Último Mes</option>
                                <option value="anual" {{ request('periodo') == 'anual' ? 'selected' : '' }}>Último Año</option>
                            </select>
                        </div>
                        <div class="h-6 w-px bg-slate-300"></div>
                        <div>
                            <select name="cliente" class="bg-transparent border-none text-sm font-medium text-slate-700 focus:ring-0 cursor-pointer max-w-[150px]">
                                <option value="">Todos los Clientes</option>
                                @foreach($clientes as $c)
                                    <option value="{{ $c }}" {{ request('cliente') == $c ? 'selected' : '' }}>{{ Str::limit($c, 15) }}</option>
                                @endforeach
                            </select>
                        </div>
                        <button type="submit" class="bg-indigo-600 text-white px-4 py-1.5 rounded-lg text-sm font-bold shadow-sm hover:bg-indigo-700 transition-colors">
                            Filtrar
                        </button>
                    </form>
                </div>
            </div>

            {{-- 2. TARJETAS KPI (KPI Cards) --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                {{-- Total --}}
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 relative overflow-hidden group">
                    <div class="absolute right-0 top-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                        <svg class="w-20 h-20 text-slate-800" fill="currentColor" viewBox="0 0 20 20"><path d="M7 3a1 1 0 000 2h6a1 1 0 100-2H7zM4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM2 11a2 2 0 012-2h12a2 2 0 012 2v4a2 2 0 01-2 2H4a2 2 0 01-2-2v-4z"></path></svg>
                    </div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Operaciones</p>
                    <p class="text-3xl font-bold text-slate-800 mt-2">{{ $statsTemporales['total_operaciones'] }}</p>
                    <div class="mt-2 flex items-center text-xs text-slate-500">
                        <span class="text-indigo-600 font-bold mr-1">{{ $statsTemporales['completado_tiempo'] + $statsTemporales['completado_retraso'] }}</span> finalizadas
                    </div>
                </div>

                {{-- En Tiempo (Saludable) --}}
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 border-b-4 border-emerald-500">
                    <p class="text-xs font-bold text-emerald-600 uppercase tracking-wider">Salud Operativa</p>
                    <div class="flex items-end gap-2 mt-2">
                        <p class="text-3xl font-bold text-slate-800">{{ $statsTemporales['en_tiempo'] + $statsTemporales['completado_tiempo'] }}</p>
                        <span class="text-sm font-medium text-emerald-600 mb-1">
                            ({{ $statsTemporales['total_operaciones'] > 0 ? round((($statsTemporales['en_tiempo'] + $statsTemporales['completado_tiempo']) / $statsTemporales['total_operaciones']) * 100) : 0 }}%)
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 mt-2">En tiempo o completadas OK</p>
                </div>

                {{-- En Riesgo (Warning) --}}
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 border-b-4 border-amber-400">
                    <p class="text-xs font-bold text-amber-600 uppercase tracking-wider">Alerta / Riesgo</p>
                    <p class="text-3xl font-bold text-slate-800 mt-2">{{ $statsTemporales['en_riesgo'] }}</p>
                    <p class="text-xs text-slate-400 mt-2">Próximas a vencer</p>
                </div>

                {{-- Críticos (Late) --}}
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 border-b-4 border-rose-500">
                    <p class="text-xs font-bold text-rose-600 uppercase tracking-wider">Fuera de Métrica</p>
                    <div class="flex items-end gap-2 mt-2">
                        <p class="text-3xl font-bold text-slate-800">{{ $statsTemporales['con_retraso'] + $statsTemporales['completado_retraso'] }}</p>
                        <span class="text-sm font-medium text-rose-600 mb-1">ops</span>
                    </div>
                    <p class="text-xs text-slate-400 mt-2">Requieren atención inmediata</p>
                </div>
            </div>

            {{-- 3. GRÁFICAS Y EXPORTACIÓN --}}
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                
                {{-- Gráfica 1: Distribución --}}
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex flex-col">
                    <h3 class="text-lg font-bold text-slate-800 mb-4">Estatus Actual</h3>
                    <div class="flex-1 relative min-h-[200px]">
                        <canvas id="chartStatus"></canvas>
                    </div>
                </div>

                {{-- Gráfica 2: Eficiencia --}}
                <div class="bg-white p-6 rounded-2xl shadow-sm border border-slate-100 flex flex-col">
                    <h3 class="text-lg font-bold text-slate-800 mb-4">Eficiencia & Tiempos</h3>
                    <div class="flex-1 relative min-h-[200px]">
                        <canvas id="chartEficiencia"></canvas>
                    </div>
                </div>

                {{-- Tarjeta de Exportación (Excel con Gráficas) --}}
                <div class="bg-gradient-to-br from-slate-800 to-slate-900 text-white p-6 rounded-2xl shadow-lg flex flex-col justify-between relative overflow-hidden">
                    <div class="absolute top-0 right-0 -mt-8 -mr-8 w-40 h-40 bg-white opacity-5 rounded-full blur-3xl"></div>
                    
                    <div>
                        <h3 class="text-xl font-bold mb-2">Exportar Reportes</h3>
                        <p class="text-slate-300 text-sm mb-6">Genera archivos listos para presentar a dirección.</p>
                    </div>

                    <div class="space-y-3 relative z-10">
                        <a href="{{ route('logistica.reportes.export-excel', request()->query()) }}" class="flex items-center justify-between w-full bg-white/10 hover:bg-white/20 p-3 rounded-xl transition-all group border border-white/5">
                            <div class="flex items-center gap-3">
                                <div class="bg-green-500 p-2.5 rounded-lg text-white shadow-lg shadow-green-500/20">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </div>
                                <div class="text-left">
                                    <p class="font-bold text-sm text-white">Reporte Excel + Gráficas</p>
                                    <p class="text-[10px] text-slate-300">Formato ejecutivo completo</p>
                                </div>
                            </div>
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        </a>

                        <a href="{{ route('logistica.reportes.resumen.export', request()->query()) }}" class="flex items-center justify-between w-full bg-white/5 hover:bg-white/10 p-3 rounded-xl transition-all group border border-white/5">
                            <div class="flex items-center gap-3">
                                <div class="bg-blue-500 p-2.5 rounded-lg text-white shadow-lg shadow-blue-500/20">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                </div>
                                <div class="text-left">
                                    <p class="font-bold text-sm text-white">Resumen CSV</p>
                                    <p class="text-[10px] text-slate-300">Datos ligeros para análisis</p>
                                </div>
                            </div>
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-white transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        </a>
                    </div>
                </div>
            </div>

            {{-- 4. TABLA DETALLADA --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mt-6">
                <div class="p-5 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-bold text-slate-800">Detalle Operativo del Periodo</h3>
                    <span class="text-xs font-semibold bg-slate-100 text-slate-500 px-2 py-1 rounded-lg">Mostrando últimos 50 registros</span>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-slate-600">
                        <thead class="bg-slate-50 text-slate-700 uppercase font-bold text-xs">
                            <tr>
                                <th class="px-6 py-3">Folio</th>
                                <th class="px-6 py-3">Cliente</th>
                                <th class="px-6 py-3">Estatus</th>
                                <th class="px-6 py-3">Categoría</th>
                                <th class="px-6 py-3 text-right">Target / Real</th>
                                <th class="px-6 py-3 text-center">Progreso</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse(array_slice($comportamientoTemporal, 0, 50) as $row)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-3 font-medium text-slate-900">
                                    #{{ $row['id'] }}
                                </td>
                                <td class="px-6 py-3">
                                    <div class="font-medium text-slate-700">{{ $row['cliente'] }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $row['ejecutivo'] }}</div>
                                </td>
                                <td class="px-6 py-3">
                                    {{ $row['status'] }}
                                </td>
                                <td class="px-6 py-3">
                                    @php
                                        $catColor = match(true) {
                                            str_contains($row['categoria'], 'Tiempo') => 'bg-emerald-100 text-emerald-700',
                                            str_contains($row['categoria'], 'Riesgo') => 'bg-amber-100 text-amber-700',
                                            str_contains($row['categoria'], 'Retraso') => 'bg-rose-100 text-rose-700',
                                            default => 'bg-slate-100 text-slate-700'
                                        };
                                    @endphp
                                    <span class="px-2 py-1 rounded-full text-xs font-bold {{ $catColor }}">
                                        {{ $row['categoria'] }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-right">
                                    <span class="text-slate-400">{{ $row['target'] }}</span> / 
                                    <span class="font-bold {{ $row['dias_transcurridos'] > $row['target'] ? 'text-rose-600' : 'text-slate-700' }}">{{ $row['dias_transcurridos'] }}</span> días
                                </td>
                                <td class="px-6 py-3 align-middle">
                                    <div class="w-24 mx-auto bg-slate-200 rounded-full h-1.5">
                                        <div class="h-1.5 rounded-full {{ $row['dias_transcurridos'] > $row['target'] ? 'bg-rose-500' : 'bg-indigo-500' }}" style="width: {{ $row['porcentaje_progreso'] }}%"></div>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="px-6 py-8 text-center text-slate-400">No hay datos en este periodo.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
@endsection