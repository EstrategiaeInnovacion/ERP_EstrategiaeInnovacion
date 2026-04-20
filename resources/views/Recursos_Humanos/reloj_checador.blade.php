@extends('layouts.erp')

@push('styles')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush

@section('content')
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-gray-800 leading-tight tracking-tight">
                    {{ __('Control de Asistencia') }}
                </h2>
                <p class="text-xs text-gray-500 mt-1">Gestión de entradas, salidas e incidencias del personal.</p>
            </div>
            <div class="flex gap-3">
                @if(!($esSoloLectura ?? false))
                <button onclick="abrirModalIncidencia()" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-lg font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:border-indigo-900 focus:ring ring-indigo-300 disabled:opacity-25 transition shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Registrar Incidencia
                </button>
                @endif
            </div>
        </div>
    </x-slot>

    <div class="py-8 bg-gray-50/50 min-h-screen" x-data="{ openImport: false, showNoResults: {{ ($sinResultados ?? false) ? 'true' : 'false' }} }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            {{-- MODAL SIN RESULTADOS --}}
            <div x-show="showNoResults" x-cloak class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
                <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" @click="showNoResults = false"></div>
                    <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
                    <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md w-full">
                        <div class="bg-white px-6 pt-6 pb-4">
                            <div class="flex flex-col items-center text-center">
                                <div class="mx-auto flex items-center justify-center h-16 w-16 rounded-full bg-amber-100 mb-4">
                                    <svg class="h-8 w-8 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                                    </svg>
                                </div>
                                <h3 class="text-lg font-bold text-gray-900 mb-2" id="modal-title">
                                    Sin registros encontrados
                                </h3>
                                <p class="text-sm text-gray-500 mb-2">
                                    No se encontraron registros para <span class="font-semibold text-gray-700">"{{ $busqueda ?? '' }}"</span>
                                </p>
                                <p class="text-sm text-gray-500">
                                    en el período del <span class="font-medium text-indigo-600">{{ $fechaInicioFormato ?? '' }}</span> al <span class="font-medium text-indigo-600">{{ $fechaFinFormato ?? '' }}</span>
                                </p>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-6 py-4 flex justify-center gap-3">
                            <a href="{{ route('rh.reloj.index') }}" class="inline-flex justify-center rounded-lg border border-gray-300 shadow-sm px-4 py-2 bg-white text-sm font-medium text-gray-700 hover:bg-gray-50 transition">
                                Limpiar filtros
                            </a>
                            <button type="button" @click="showNoResults = false" class="inline-flex justify-center rounded-lg border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-sm font-medium text-white hover:bg-indigo-700 transition">
                                Entendido
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            
            {{-- SECCIÓN DE ESTADÍSTICAS --}}
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                
                {{-- 1. HORAS TOTALES --}}
                <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-blue-500 relative overflow-hidden group hover:shadow-md transition">
                    <div class="relative z-10">
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Horas Totales</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $horasTotales ?? 0 }} <span class="text-lg text-gray-400 font-normal">hrs</span></p>
                        <p class="text-xs text-blue-600 font-medium mt-1">Periodo actual</p>
                    </div>
                    <div class="absolute right-0 top-0 h-full w-24 bg-gradient-to-l from-blue-50 to-transparent opacity-50 group-hover:opacity-100 transition"></div>
                    <div class="absolute -right-2 -bottom-4 text-blue-100 opacity-50">
                        <svg class="w-24 h-24" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>

                {{-- 2. EFICIENCIA --}}
                <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-indigo-500 relative overflow-hidden group hover:shadow-md transition">
                    <div class="relative z-10">
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Eficiencia</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $porcentajeAsistencia }}<span class="text-lg text-gray-400 font-normal">%</span></p>
                    </div>
                    <div class="absolute right-0 top-0 h-full w-24 bg-gradient-to-l from-indigo-50 to-transparent opacity-50 group-hover:opacity-100 transition"></div>
                    <div class="absolute -right-2 -bottom-4 text-indigo-100 opacity-50">
                        <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24"><path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>

                {{-- 3. RETARDOS --}}
                <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-amber-500 relative overflow-hidden group hover:shadow-md transition">
                    <div class="relative z-10">
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Retardos</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $retardos }}</p>
                        <p class="text-xs text-amber-600 font-medium mt-1">Sin justificar</p>
                    </div>
                    <div class="absolute -right-4 -bottom-4 text-amber-100 opacity-50">
                        <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>

                {{-- 4. AUSENCIAS --}}
                <div class="bg-white rounded-xl shadow-sm p-6 border-l-4 border-red-500 relative overflow-hidden group hover:shadow-md transition">
                    <div class="relative z-10">
                        <p class="text-sm font-medium text-gray-500 uppercase tracking-wider">Ausencias</p>
                        <p class="text-3xl font-bold text-gray-900 mt-1">{{ $faltas }}</p>
                        <p class="text-xs text-red-600 font-medium mt-1">Requieren atención</p>
                    </div>
                    <div class="absolute -right-4 -bottom-4 text-red-100 opacity-50">
                        <svg class="w-24 h-24" fill="currentColor" viewBox="0 0 24 24"><path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                    </div>
                </div>

                {{-- 5. TOP RETARDOS --}}
                <div class="bg-white rounded-xl shadow-sm p-5 border border-gray-100 overflow-hidden">
                    <div class="flex items-center justify-between mb-3">
                        <p class="text-xs font-bold text-gray-400 uppercase tracking-wider">Top Retardos</p>
                        <span class="text-xs px-2 py-0.5 rounded bg-gray-100 text-gray-500">Este mes</span>
                    </div>
                    <div class="space-y-3">
                        @forelse($topRetardos as $top)
                            <div class="flex items-center justify-between group">
                                <div class="flex items-center gap-2 overflow-hidden">
                                    <div class="w-6 h-6 rounded-full bg-gray-200 text-[10px] flex items-center justify-center font-bold text-gray-600 flex-shrink-0">
                                        {{ substr($top->nombre, 0, 1) }}
                                    </div>
                                    <span class="text-xs font-medium text-gray-700 truncate group-hover:text-indigo-600 transition">{{ Str::limit($top->nombre, 15) }}</span>
                                </div>
                                <span class="text-xs font-bold text-red-500 bg-red-50 px-1.5 py-0.5 rounded">{{ $top->total }}</span>
                            </div>
                        @empty
                            <div class="text-center py-4">
                                <p class="text-xs text-gray-400">¡Sin retardos registrados! 🎉</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- IMPORTADOR Y FILTROS --}}
            <div class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden">
                <div class="px-6 py-4 bg-white border-b border-gray-100 flex flex-col lg:flex-row justify-between items-center gap-4">
                    
                    <button @click="openImport = !openImport" :class="{'bg-blue-50 text-blue-700 border-blue-100': openImport, 'bg-gray-50 text-gray-700 border-gray-200': !openImport}" class="flex items-center px-4 py-2 rounded-lg border text-sm font-semibold transition-all duration-200 w-full lg:w-auto justify-center lg:justify-start group">
                        <svg class="w-5 h-5 mr-2 transition-transform group-hover:scale-110" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                        <span x-text="openImport ? 'Cerrar Importador' : 'Importar Archivo Excel'"></span>
                    </button>

                    <form method="GET" action="{{ route('rh.reloj.index') }}" class="flex flex-col sm:flex-row gap-3 w-full lg:w-auto items-center">
                        <div class="relative w-full sm:w-64">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </div>
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar empleado..." class="pl-9 w-full rounded-lg border-gray-300 bg-gray-50 focus:bg-white text-sm focus:ring-indigo-500 focus:border-indigo-500 transition-colors">
                        </div>
                        
                        <div class="flex gap-2 w-full sm:w-auto items-center bg-gray-50 p-1 rounded-lg border border-gray-200">
                            <input type="date" name="fecha_inicio" value="{{ request('fecha_inicio', now()->startOfMonth()->toDateString()) }}" class="border-none bg-transparent text-sm text-gray-600 focus:ring-0 w-32 p-1">
                            <span class="text-gray-400 text-xs">➜</span>
                            <input type="date" name="fecha_fin" value="{{ request('fecha_fin', now()->endOfMonth()->toDateString()) }}" class="border-none bg-transparent text-sm text-gray-600 focus:ring-0 w-32 p-1">
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white rounded p-1.5 shadow-sm transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path></svg>
                            </button>
                        </div>
                    </form>
                </div>

                {{-- AREA DE CARGA (Importador) --}}
                <div x-show="openImport" x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="bg-blue-50/50 border-b border-blue-100 p-6" style="display: none;">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        <div>
                            <h4 class="text-sm font-bold text-blue-900 mb-2">Carga de Datos</h4>
                            <p class="text-xs text-blue-700 mb-4">Suba el archivo .xlsx exportado del reloj checador ZKTeco.</p>
                            
                            <form id="importForm" class="space-y-4">
                                @csrf
                                <div class="flex gap-3">
                                    <input type="file" name="archivo" accept=".xls,.xlsx" class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-xs file:font-semibold file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200 transition bg-white border border-blue-200 rounded-lg cursor-pointer">
                                    <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-lg shadow-sm transition flex items-center gap-2 whitespace-nowrap">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                        Procesar
                                    </button>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-blue-800 mb-1">Filtrar por empleado(s) <span class="font-normal text-blue-600">(opcional — dejar vacío para importar todos)</span></label>
                                    <select id="empleadosFiltro" multiple class="w-full text-xs rounded-lg border-blue-200 focus:ring-blue-500 bg-white py-1.5" style="min-height: 60px;">
                                        @foreach(\App\Models\Empleado::where('es_activo', true)->orderBy('nombre')->get() as $emp)
                                            <option value="{{ $emp->id_empleado }}">{{ $emp->nombre }} ({{ $emp->id_empleado ?? 'S/N' }})</option>
                                        @endforeach
                                    </select>
                                    <p class="text-[10px] text-blue-600 mt-1">Ctrl+click para seleccionar varios. Solo se procesarán los registros de los empleados seleccionados.</p>
                                </div>
                                <div id="progressContainer" class="hidden">
                                    <div class="flex justify-between text-xs font-semibold text-blue-800 mb-1">
                                        <span id="progressMessage">Cargando...</span>
                                        <span id="progressPercent">0%</span>
                                    </div>
                                    <div class="w-full bg-blue-200 rounded-full h-2 overflow-hidden">
                                        <div id="progressBar" class="bg-blue-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                                    </div>
                                </div>
                            </form>
                        </div>
                        <div class="border-l border-blue-200 pl-8 flex flex-col justify-center">
                            <h4 class="text-sm font-bold text-red-900 mb-2">Zona de Peligro — Reloj Checador</h4>
                            <p class="text-xs text-red-700 mb-4">Eliminar registros de asistencia del reloj checador por rango de fechas o por completo.</p>
                            <div class="flex flex-col gap-2">
                                {{-- Revertir por rango --}}
                                <button type="button" onclick="document.getElementById('modalRevertirRango').classList.remove('hidden')"
                                    class="text-amber-600 hover:text-amber-800 text-xs font-bold flex items-center gap-1 hover:underline decoration-amber-300">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                                    Revertir por Rango (por Empleado)
                                </button>
                                {{-- Borrar por rango --}}
                                <button type="button" onclick="document.getElementById('modalClearRango').classList.remove('hidden')"
                                    class="text-orange-600 hover:text-orange-800 text-xs font-bold flex items-center gap-1 hover:underline decoration-orange-300">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    Borrar Asistencias por Rango de Fechas
                                </button>
                                {{-- Borrar todo --}}
                                <form action="{{ route('rh.reloj.clear') }}" method="POST" onsubmit="return confirm('ATENCIÓN: Esto borrará TODOS los registros del reloj checador.\n\nNo afecta ningún otro módulo del sistema. ¿Está seguro?');">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-bold flex items-center gap-1 hover:underline decoration-red-300">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                        Vaciar Todos los Registros del Reloj
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- VISTA CENTRADA EN EL EMPLEADO (Employee-First) --}}
            <div class="space-y-6">
                @if($empleados->isEmpty())
                     <div class="bg-white rounded-xl shadow-sm border border-gray-200 p-12 text-center">
                        <svg class="w-16 h-16 text-gray-300 mb-4 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <p class="text-lg font-medium text-gray-500 mb-1">No se encontraron empleados</p>
                        <p class="text-sm text-gray-400">Intenta con otra búsqueda o cambia las fechas del período</p>
                    </div>
                @else
                    @foreach($empleados as $empleado)
                        <div id="empleado-{{ $empleado->id }}" class="bg-white rounded-xl shadow-sm border border-gray-200 overflow-hidden group hover:shadow-md transition-all duration-300 {{ !$empleado->es_activo ? 'opacity-60 border-red-200' : '' }}">
                            {{-- ENCABEZADO EMPLEADO --}}
                            <div class="px-6 py-4 border-b border-gray-100 flex flex-col md:flex-row justify-between items-start md:items-center gap-4 bg-gradient-to-r from-gray-50 to-white">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-full {{ !$empleado->es_activo ? 'bg-red-50 border-red-200 text-red-600' : 'bg-white border-indigo-100 text-indigo-600' }} border-2 flex items-center justify-center text-sm font-bold shadow-sm">
                                        {{ substr($empleado->nombre, 0, 2) }}
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h3 class="text-base font-bold text-gray-900 leading-tight">{{ $empleado->nombre }}</h3>
                                            @if(!$empleado->es_activo)
                                                <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold bg-red-100 text-red-700 border border-red-200">BAJA</span>
                                            @endif
                                        </div>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-xs text-gray-400 font-mono bg-gray-100 px-1.5 py-0.5 rounded">ID: {{ $empleado->id_empleado ?? 'S/N' }}</span>
                                            {{-- Resumen Rápido (Opcional, se puede calcular si se desea mayor detalle) --}}
                                            @php
                                                // Pequeño cálculo al vuelo para el resumen de este empleado en el rango
                                                $asistenciasEmp = $empleado->asistencias->whereBetween('fecha', [$fechaInicioDb, $fechaFinDb]);
                                                $retardosEmp = $asistenciasEmp->where('es_retardo', true)->where('es_justificado', false)->count();
                                                $faltasEmp = $asistenciasEmp->where('tipo_registro', 'falta')->where('es_justificado', false)->count();
                                                
                                                // Calcular Horas Totales
                                                $totalMinutos = 0;
                                                foreach($asistenciasEmp as $asist) {
                                                    if($asist->tipo_registro == 'asistencia' && $asist->entrada && $asist->salida) {
                                                        try {
                                                            $ent = \Carbon\Carbon::parse($asist->entrada);
                                                            $sal = \Carbon\Carbon::parse($asist->salida);
                                                            if($sal->gt($ent)) {
                                                                $totalMinutos += $ent->diffInMinutes($sal);
                                                            }
                                                        } catch(\Exception $e) {}
                                                    }
                                                }
                                                $horasTotales = floor($totalMinutos / 60);
                                                $minutosRestantes = $totalMinutos % 60;
                                            @endphp
                                            
                                            <span class="text-[10px] font-bold text-blue-600 bg-blue-50 px-1.5 py-0.5 rounded border border-blue-100" title="{{ $totalMinutos }} minutos">
                                                ⏱️ {{ $horasTotales }}h {{ $minutosRestantes > 0 ? $minutosRestantes.'m' : '' }}
                                            </span>

                                            @if($retardosEmp > 0)
                                                <span class="text-[10px] font-bold text-amber-600 bg-amber-50 px-1.5 py-0.5 rounded border border-amber-100">{{ $retardosEmp }} Retardos</span>
                                            @endif
                                            @if($faltasEmp > 0)
                                                <span class="text-[10px] font-bold text-red-600 bg-red-50 px-1.5 py-0.5 rounded border border-red-100">{{ $faltasEmp }} Faltas</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    {{-- Botón de Aviso (Siempre visible para cualquier tipo de aviso) --}}
                                        <button onclick="abrirModalAviso({{ $empleado->id }}, '{{ addslashes($empleado->nombre) }}', {{ $retardosEmp }}, {{ $faltasEmp }}, '{{ $fechaInicioFormato }} al {{ $fechaFinFormato }}')" class="inline-flex items-center px-3 py-1.5 bg-rose-50 border border-rose-300 rounded-lg text-xs font-medium text-rose-700 shadow-sm hover:bg-rose-100 transition" title="Enviar aviso al empleado">
                                            <svg class="w-3.5 h-3.5 mr-1.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                            Enviar Aviso
                                        </button>

                                    {{-- Botón de Historial de Avisos (Solo si tiene avisos previos) --}}
                                    @if($empleado->avisosAsistencia->count() > 0)
                                        <button onclick="abrirModalHistorialAvisos('{{ addslashes($empleado->nombre) }}', {{ json_encode($empleado->avisosAsistencia) }})" class="inline-flex items-center px-3 py-1.5 bg-amber-50 border border-amber-300 rounded-lg text-xs font-medium text-amber-700 shadow-sm hover:bg-amber-100 transition" title="Ver historial de avisos">
                                            <svg class="w-3.5 h-3.5 mr-1.5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            Historial Avisos
                                        </button>
                                    @endif

                                    @if(!($esSoloLectura ?? false))
                                    <button onclick="abrirModalAsistencia({{ $empleado->id }}, '{{ addslashes($empleado->nombre) }}')" class="inline-flex items-center px-3 py-1.5 bg-emerald-50 border border-emerald-300 rounded-lg text-xs font-medium text-emerald-700 shadow-sm hover:bg-emerald-100 transition">
                                        <svg class="w-3.5 h-3.5 mr-1.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        Registrar Asistencia
                                    </button>
                                    @endif
                                    <button onclick="abrirModalIncidencia({{ $empleado->id }}, '{{ now()->toDateString() }}')" class="inline-flex items-center px-3 py-1.5 bg-white border border-gray-300 rounded-lg text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 transition">
                                        <svg class="w-3.5 h-3.5 mr-1.5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                        Nueva Incidencia
                                    </button>
                                </div>
                            </div>

                            {{-- BODY: TIRA DE FECHAS (Scroll Horizontal) --}}
                            <div class="p-4 overflow-x-auto">
                                <div class="flex gap-2 min-w-max pb-2">
                                    @foreach($fechas as $fechaObj)
                                        @php
                                            $asistencia = $empleado->asistencias->first(function($item) use ($fechaObj) {
                                                return \Carbon\Carbon::parse($item->fecha)->format('Y-m-d') === $fechaObj->format('Y-m-d');
                                            });

                                            // Estilos base
                                            $cardClass = 'bg-gray-50 border-gray-100';
                                            $textClass = 'text-gray-400';
                                            $icon = null;
                                            
                                            // Estado
                                            if($asistencia) {
                                                $tipo = $asistencia->tipo_registro;
                                                if($tipo == 'asistencia') {
                                                    if($asistencia->es_retardo && !$asistencia->es_justificado) {
                                                        $cardClass = 'bg-amber-50 border-amber-200';
                                                        $textClass = 'text-amber-700';
                                                        $icon = '⚠️';
                                                    } elseif($asistencia->es_justificado) {
                                                        $cardClass = 'bg-indigo-50 border-indigo-200';
                                                        $textClass = 'text-indigo-700';
                                                        $icon = '✅';
                                                    } else {
                                                        $cardClass = 'bg-green-50 border-green-200';
                                                        $textClass = 'text-green-700';
                                                    }
                                                } elseif($tipo == 'falta') {
                                                    $cardClass = $asistencia->es_justificado ? 'bg-orange-50 border-orange-200' : 'bg-red-50 border-red-200';
                                                    $textClass = $asistencia->es_justificado ? 'text-orange-700' : 'text-red-700';
                                                    $icon = '❌';
                                                } elseif($tipo == 'incompleto') {
                                                    $cardClass = 'bg-yellow-50 border-yellow-300';
                                                    $textClass = 'text-yellow-700';
                                                    $icon = '⚠️';
                                                } else {
                                                    // Vacaciones, Incapacidad, Permiso, Descanso
                                                    $cardClass = 'bg-blue-50 border-blue-200';
                                                    $textClass = 'text-blue-700';
                                                    $icon = ($tipo == 'vacaciones' ? '🌴' : ($tipo == 'descanso' ? '🏠' : '🏥'));
                                                }
                                            } else {
                                                // Sin Registro
                                                $cardClass = 'bg-gray-50 border-gray-100 opacity-60 hover:opacity-100';
                                            }
                                        @endphp
                                        
                                        <div class="relative w-[130px] flex-shrink-0 rounded-lg border {{ $cardClass }} p-2.5 transition-all duration-200 hover:shadow-md cursor-pointer group/day"
                                             @if($asistencia)
                                                 onclick="abrirModalEdicion({{ $asistencia }})"
                                                 title="Click para editar"
                                             @else
                                                 onclick="abrirModalJustificar({{ $empleado->id }}, '{{ addslashes($empleado->nombre) }}', '{{ $fechaObj->toDateString() }}', '{{ $fechaObj->translatedFormat('d M Y') }}')"
                                                 title="Click para justificar"
                                             @endif
                                        >
                                            <div class="flex justify-between items-center mb-2">
                                                <span class="text-xs font-bold text-gray-500">{{ $fechaObj->translatedFormat('d M') }}</span>
                                                <span class="text-[10px] uppercase text-gray-400">{{ $fechaObj->translatedFormat('D') }}</span>
                                            </div>

                                            {{-- Contenido Central --}}
                                            <div class="text-center h-12 flex flex-col justify-center items-center">
                                                @if($asistencia)
                                                    {{-- Mostrar horarios si existen, sin importar el tipo --}}
                                                    @if($asistencia->entrada || $asistencia->salida)
                                                        <div class="text-xs font-mono font-bold {{ $asistencia->tipo_registro == 'incompleto' ? 'text-yellow-800' : 'text-gray-800' }} block">
                                                            {{ $asistencia->entrada ? substr($asistencia->entrada, 0, 5) : '--:--' }}
                                                            <span class="text-gray-300 mx-0.5">-</span>
                                                            {{ $asistencia->salida ? substr($asistencia->salida, 0, 5) : '--:--' }}
                                                        </div>
                                                    @endif

                                                    @if($asistencia->tipo_registro == 'incompleto')
                                                        <span class="text-[9px] font-bold text-yellow-600 bg-yellow-100 px-1 rounded mt-0.5">⚠️ Incompleto</span>
                                                    @elseif(!in_array($asistencia->tipo_registro, ['asistencia']))
                                                        {{-- Mostrar etiqueta del tipo para registros no-asistencia --}}
                                                        <span class="text-[9px] font-bold {{ $textClass }} truncate w-full mt-0.5">
                                                            {{ $icon }} {{ ucfirst($asistencia->tipo_registro) }}
                                                        </span>
                                                    @else
                                                        {{-- Cálculo de Horas Diarias (solo asistencia normal) --}}
                                                        @php
                                                            $horasDia = null;
                                                            if($asistencia->entrada && $asistencia->salida) {
                                                                try {
                                                                    $entrada = \Carbon\Carbon::parse($asistencia->entrada);
                                                                    $salida = \Carbon\Carbon::parse($asistencia->salida);
                                                                    if($salida->gt($entrada)) {
                                                                        $diff = $entrada->diffInMinutes($salida);
                                                                        $horasDia = floor($diff/60) . 'h ' . ($diff%60) . 'm';
                                                                    }
                                                                } catch(\Exception $e) {}
                                                            }
                                                        @endphp

                                                        @if($horasDia)
                                                            <span class="text-[9px] font-bold text-gray-500 bg-gray-100 px-1 rounded mt-0.5">
                                                                {{ $horasDia }}
                                                            </span>
                                                        @endif

                                                        @if($icon) <span class="text-[10px] mt-0.5 block">{{ $icon }}</span> @endif
                                                    @endif
                                                @else
                                                    <span class="text-[10px] text-gray-300 italic group-hover/day:text-indigo-400">
                                                        -- Vacío --
                                                    </span>
                                                @endif
                                            </div>

                                            {{-- Hover Action Indicator (Sutil) --}}
                                            <div class="absolute inset-0 border-2 border-indigo-400 rounded-lg opacity-0 group-hover/day:opacity-100 pointer-events-none transition-opacity"></div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- PAGINACIÓN --}}
            @if($empleados->hasPages())
                <div class="mt-8 bg-white px-6 py-4 rounded-xl border border-gray-200 shadow-sm">
                    {{ $empleados->links() }}
                </div>
            @endif
        </div>
    </div>

    {{-- MODAL DE EDICIÓN (Registro Existente) --}}
    <div id="modalEdicion" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="cerrarModalEdicion()"></div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <form id="formEdicion" method="POST">
                    @csrf @method('PUT')
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">Editar Registro Individual</h3>
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tipo de Incidencia</label>
                                <select name="tipo_registro" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    <option value="asistencia">Asistencia Normal</option>
                                    <option value="falta">Falta</option>
                                    <option value="incompleto">Incompleto</option>
                                    <option value="vacaciones">Vacaciones</option>
                                    <option value="incapacidad">Incapacidad</option>
                                    <option value="permiso">Permiso con Goce</option>
                                    <option value="descanso">Día de Descanso</option>
                                </select>
                            </div>
                            <div class="flex items-center bg-gray-50 p-2 rounded border border-gray-200">
                                <input id="es_justificado" name="es_justificado" type="checkbox" class="h-4 w-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500">
                                <label for="es_justificado" class="ml-2 block text-sm text-gray-900 font-medium">Justificar Retardo / Falta</label>
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Comentarios</label>
                                <textarea name="comentarios" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 flex flex-col sm:flex-row-reverse gap-2">
                        <button type="button" onclick="guardarEdicion()" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-indigo-600 text-base font-medium text-white hover:bg-indigo-700 sm:ml-3 sm:w-auto sm:text-sm">Guardar Cambios</button>
                        <button type="button" onclick="cerrarModalEdicion()" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:ml-3 sm:w-auto sm:text-sm">Cancelar</button>
                        <button type="button" onclick="eliminarRegistro()" class="w-full inline-flex justify-center items-center gap-1.5 rounded-md border border-red-300 shadow-sm px-4 py-2 bg-red-50 text-sm font-medium text-red-700 hover:bg-red-100 sm:w-auto sm:mr-auto">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                            Revertir / Deshacer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL DE CREACIÓN (Nueva Incidencia / Justificación Rápida) --}}
    <div id="modalIncidencia" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="cerrarModalIncidencia()"></div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <form action="{{ route('rh.reloj.store') }}" method="POST">
                    @csrf
                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6" x-data="{ tipo: 'vacaciones' }">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-bold text-gray-900">Registrar / Justificar Incidencia</h3>
                            <button type="button" onclick="cerrarModalIncidencia()" class="text-gray-400 hover:text-gray-500">
                                <span class="sr-only">Cerrar</span>
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                            </button>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Empleado</label>
                                <select name="empleado_id" id="modal_empleado_id" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    
                                    {{-- OPCIÓN NUEVA PARA MASIVOS --}}
                                    <option value="all" class="font-bold text-indigo-600 bg-indigo-50">
                                        👥 APLICAR A TODOS LOS EMPLEADOS (Masivo)
                                    </option>
                                    <option disabled>──────────────────────────</option>

                                    @foreach(\App\Models\Empleado::where('es_activo', true)->orderBy('nombre')->get() as $emp)
                                        <option value="{{ $emp->id }}">{{ $emp->nombre }} ({{ $emp->id_empleado }})</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Tipo de Registro</label>
                                <select name="tipo_registro" id="modal_tipo_registro" x-model="tipo" class="mt-1 block w-full py-2 px-3 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    <option value="vacaciones">🌴 Vacaciones</option>
                                    <option value="incapacidad">🏥 Incapacidad</option>
                                    <option value="permiso">📄 Permiso Especial</option>
                                    <option value="falta">❌ Falta / Justificación</option>
                                    <option value="descanso">🏠 Día de Descanso</option>
                                </select>
                            </div>

                            <div class="bg-gray-50 p-3 rounded-md border border-gray-200">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Periodo a Aplicar</label>
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Desde</label>
                                        <input type="date" name="fecha_inicio" id="modal_fecha_inicio" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Hasta</label>
                                        <input type="date" name="fecha_fin" id="modal_fecha_fin" required class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm">
                                    </div>
                                </div>
                                <p class="text-xs text-gray-500 mt-2">
                                    * Se crean registros para cada día del rango indicado. Si es un solo día, pon la misma fecha en ambos campos.
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">Motivo / Comentarios</label>
                                <textarea name="comentarios" rows="2" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm" placeholder="Ej: Autorizado por Gerencia"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Guardar Registros
                        </button>
                        <button type="button" onclick="cerrarModalIncidencia()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL: Revertir por Rango de Fechas (por Empleado) --}}
    <div id="modalRevertirRango" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-60 transition-opacity" onclick="document.getElementById('modalRevertirRango').classList.add('hidden')"></div>
            <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full z-10 overflow-hidden">
                <form id="formRevertirRango" method="POST" action="{{ route('rh.reloj.revertirRango') }}"
                      onsubmit="return confirmarRevertirRango(event)">
                    @csrf @method('DELETE')

                    <div class="px-6 pt-6 pb-4">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="p-2 bg-amber-100 rounded-full flex-shrink-0">
                                <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Revertir Asistencias por Rango</h3>
                                <p class="text-sm text-slate-500">Revierte los registros de un empleado a su estado original (sin justificaciones ni ediciones manuales).</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            {{-- Empleado --}}
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Empleado *</label>
                                <select name="empleado_id" id="revertirRango_empleado" required
                                        class="w-full rounded-xl border-slate-300 text-sm focus:ring-amber-500 focus:border-amber-500">
                                    <option value="">— Seleccionar empleado —</option>
                                    @foreach($todosEmpleados as $emp)
                                        <option value="{{ $emp->id }}">{{ $emp->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            {{-- Atajos de mes --}}
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wide">Atajo rápido — Seleccionar mes</label>
                                <div class="grid grid-cols-3 gap-2" id="botonesRapidosMesRevertir"></div>
                            </div>

                            <div class="flex items-center gap-2 text-xs text-slate-400">
                                <div class="flex-1 h-px bg-slate-200"></div>
                                <span>o elige un rango personalizado</span>
                                <div class="flex-1 h-px bg-slate-200"></div>
                            </div>

                            {{-- Rango manual --}}
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1">Fecha Inicio *</label>
                                    <input type="date" name="fecha_inicio" id="revertirRango_inicio" required
                                           class="w-full rounded-xl border-slate-300 text-sm focus:ring-amber-500 focus:border-amber-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1">Fecha Fin *</label>
                                    <input type="date" name="fecha_fin" id="revertirRango_fin" required
                                           class="w-full rounded-xl border-slate-300 text-sm focus:ring-amber-500 focus:border-amber-500">
                                </div>
                            </div>

                            <div id="revertirRango_preview" class="hidden bg-amber-50 border border-amber-200 rounded-lg p-3 text-sm text-amber-800 font-medium"></div>
                        </div>
                    </div>

                    <div class="bg-slate-50 px-6 py-4 flex justify-end gap-3 rounded-b-2xl">
                        <button type="button" onclick="document.getElementById('modalRevertirRango').classList.add('hidden')"
                                class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-bold text-white bg-amber-600 rounded-xl hover:bg-amber-700 transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"></path></svg>
                            Revertir Registros
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL: Borrar por Rango de Fechas --}}
    <div id="modalClearRango" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-gray-900 bg-opacity-60 transition-opacity" onclick="document.getElementById('modalClearRango').classList.add('hidden')"></div>
            <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full z-10 overflow-hidden">
                <form id="formClearRango" method="POST" action="{{ route('rh.reloj.clearRango') }}"
                      onsubmit="return confirmarBorradoRango(event)">
                    @csrf @method('DELETE')

                    {{-- Header --}}
                    <div class="px-6 pt-6 pb-4">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="p-2 bg-orange-100 rounded-full flex-shrink-0">
                                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Borrar Asistencias del Reloj Checador</h3>
                                <p class="text-sm text-slate-500">Se eliminarán los registros de asistencia del período indicado. <span class="font-semibold text-orange-600">No afecta otros módulos.</span></p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            {{-- Atajos de mes --}}
                            <div>
                                <label class="block text-xs font-bold text-slate-600 mb-2 uppercase tracking-wide">Atajo rápido — Seleccionar mes</label>
                                <div class="grid grid-cols-3 gap-2" id="botonesRapidosMes"></div>
                            </div>

                            <div class="flex items-center gap-2 text-xs text-slate-400">
                                <div class="flex-1 h-px bg-slate-200"></div>
                                <span>o elige un rango personalizado</span>
                                <div class="flex-1 h-px bg-slate-200"></div>
                            </div>

                            {{-- Rango manual --}}
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1">Fecha Inicio *</label>
                                    <input type="date" name="fecha_inicio" id="clearRango_inicio" required
                                           class="w-full rounded-xl border-slate-300 text-sm focus:ring-orange-500 focus:border-orange-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1">Fecha Fin *</label>
                                    <input type="date" name="fecha_fin" id="clearRango_fin" required
                                           class="w-full rounded-xl border-slate-300 text-sm focus:ring-orange-500 focus:border-orange-500">
                                </div>
                            </div>

                            <div id="clearRango_preview" class="hidden bg-orange-50 border border-orange-200 rounded-lg p-3 text-sm text-orange-800 font-medium"></div>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="bg-slate-50 px-6 py-4 flex justify-end gap-3 rounded-b-2xl">
                        <button type="button" onclick="document.getElementById('modalClearRango').classList.add('hidden')"
                                class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition">
                            Cancelar
                        </button>
                        <button type="submit"
                                class="px-4 py-2 text-sm font-bold text-white bg-orange-600 rounded-xl hover:bg-orange-700 transition flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            Eliminar Registros
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // === Guardar scroll (vertical, horizontal general, y contenedores de fechas individuales) ===
        window.guardarScrollReloj = function(empleadoId = null) {
            // 1. Guardar scroll general de la ventana
            sessionStorage.setItem('reloj_scroll_top', window.scrollY);
            sessionStorage.setItem('reloj_scroll_left', window.scrollX);
            
            // 2. Guardar el ID del empleado seleccionado (para hacer focus después)
            if (empleadoId) {
                sessionStorage.setItem('reloj_empleado_id', String(empleadoId));
            }

            // 3. Guardar scroll horizontal de TODOS los contenedores de fechas individualmente
            const contenedoresFechas = document.querySelectorAll('.overflow-x-auto');
            contenedoresFechas.forEach((container, index) => {
                // Buscamos el div padre que tiene el ID del empleado para usarlo como llave única
                const empleadoDiv = container.closest('[id^="empleado-"]');
                const key = empleadoDiv ? 'reloj_fechas_scroll_' + empleadoDiv.id : 'reloj_fechas_scroll_idx_' + index;
                sessionStorage.setItem(key, container.scrollLeft);
            });
        };

        // === Restaurar scrolls después de la recarga del DOM ===
        document.addEventListener('DOMContentLoaded', function() {
            // 1. Restaurar scroll general de la ventana
            const savedTop = sessionStorage.getItem('reloj_scroll_top');
            const savedLeft = sessionStorage.getItem('reloj_scroll_left');
            
            if (savedTop !== null || savedLeft !== null) {
                window.scrollTo({
                    top: savedTop ? parseInt(savedTop) : 0,
                    left: savedLeft ? parseInt(savedLeft) : 0,
                    behavior: 'instant'
                });
                sessionStorage.removeItem('reloj_scroll_top');
                sessionStorage.removeItem('reloj_scroll_left');
            }
            
            // 2. Restaurar scroll horizontal de CADA contenedor de fechas individualmente
            setTimeout(function() {
                const contenedoresFechas = document.querySelectorAll('.overflow-x-auto');
                contenedoresFechas.forEach((container, index) => {
                    const empleadoDiv = container.closest('[id^="empleado-"]');
                    const key = empleadoDiv ? 'reloj_fechas_scroll_' + empleadoDiv.id : 'reloj_fechas_scroll_idx_' + index;
                    const savedScroll = sessionStorage.getItem(key);
                    
                    if (savedScroll !== null) {
                        container.scrollLeft = parseInt(savedScroll);
                        sessionStorage.removeItem(key); // Limpiamos la sesión después de usarla
                    }
                });
            }, 50); // Un pequeño delay para asegurar que el DOM está renderizado completamente
        });

        // === Enfoque al empleado (Centrar pantalla) después de que Alpine procesa la UI ===
        document.addEventListener('alpine:initialized', function() {
            const savedId = sessionStorage.getItem('reloj_empleado_id');
            if (savedId) {
                sessionStorage.removeItem('reloj_empleado_id');
                const el = document.getElementById('empleado-' + savedId);
                if (el) {
                    setTimeout(function() {
                        el.scrollIntoView({ behavior: 'instant', block: 'center' });
                    }, 30);
                }
            }
        });

        // === Guardar scroll antes de cualquier form que cause recarga ===
        document.addEventListener('submit', function(e) {
            const form = e.target;
            if (form.method && form.action && !form.action.includes('javascript')) {
                window.guardarScrollReloj();
            }
        });

        // Importador JS (Sin cambios)
        document.getElementById('importForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const uniqueKey = 'import_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            formData.append('progress_key', uniqueKey);

            // Agregar filtro de empleados seleccionados
            const filtroSelect = document.getElementById('empleadosFiltro');
            const selectedEmpleados = Array.from(filtroSelect.selectedOptions).map(o => o.value);
            if (selectedEmpleados.length > 0) {
                formData.append('empleados_filtro', selectedEmpleados.join(','));
            }

            const btn = this.querySelector('button');
            const originalText = btn.innerHTML;
            const progressContainer = document.getElementById('progressContainer');
            const progressBar = document.getElementById('progressBar');
            const progressPercent = document.getElementById('progressPercent');
            const progressMessage = document.getElementById('progressMessage');

            btn.disabled = true;
            btn.innerHTML = 'Cargando...';
            progressContainer.classList.remove('hidden');
            progressBar.style.width = '0%';
            progressPercent.innerText = '0%';
            progressMessage.innerText = 'Iniciando...';

            let pollInterval = setInterval(() => {
                fetch(`/recursos-humanos/reloj/progress/${uniqueKey}`)
                    .then(r => r.json())
                    .then(status => {
                        let p = status.percent || 0;
                        progressBar.style.width = p + '%';
                        progressPercent.innerText = p + '%';
                        progressMessage.innerText = status.mensaje || 'Procesando...';
                        if (status.finalizado || status.status === 'error') {
                            clearInterval(pollInterval);
                            if(status.status === 'error') {
                                alert('Error: ' + status.mensaje);
                                btn.disabled = false;
                                btn.innerHTML = originalText;
                            }
                        }
                    }).catch(err => console.log(err));
            }, 1000);

            fetch("{{ route('rh.reloj.start') }}", {
                method: 'POST', body: formData, headers: { 'X-CSRF-TOKEN': "{{ csrf_token() }}" }
            })
            .then(r => r.json())
            .then(data => {
                clearInterval(pollInterval);
                if (data.error) throw new Error(data.error);
                progressBar.style.width = '100%';
                progressPercent.innerText = '100%';
                progressMessage.innerText = '¡Completado!';
                setTimeout(() => {
                    window.guardarScrollReloj();
                    window.location.reload();
                }, 1000);
            })
            .catch(error => {
                clearInterval(pollInterval);
                console.error(error);
                alert('Error al procesar: ' + error.message);
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
        });

        // Modales
        var currentRecordId = null;
        var currentEmpleadoId = null;

        function abrirModalEdicion(asistencia) {
            const form = document.getElementById('formEdicion');
            form.action = `/recursos-humanos/reloj/update/${asistencia.id}`;
            form.querySelector('[name="tipo_registro"]').value = asistencia.tipo_registro;
            form.querySelector('[name="comentarios"]').value = asistencia.comentarios || '';
            form.querySelector('[name="es_justificado"]').checked = asistencia.es_justificado;
            currentRecordId = asistencia.id;
            currentEmpleadoId = asistencia.empleado_id;
            document.getElementById('modalEdicion').classList.remove('hidden');
        }
        function cerrarModalEdicion() {
            document.getElementById('modalEdicion').classList.add('hidden');
        }

        // === AJAX: Guardar edición sin recargar la página ===
        document.getElementById('formEdicion').addEventListener('submit', function(e) {
            e.preventDefault(); // fallback: por si algún input dispara submit
        });

        function guardarEdicion() {
            const form = document.getElementById('formEdicion');
            const btn = form.closest('[id="modalEdicion"]').querySelector('button[onclick="guardarEdicion()"]');
            const prevHTML = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = 'Guardando...';

            fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(function(r) {
                if (!r.ok) throw new Error('server_error');
                return r.json();
            })
            .then(function(data) {
                cerrarModalEdicion();
                window.guardarScrollReloj(currentEmpleadoId);
                mostrarToastReloj(data.message || 'Registro actualizado.');
                setTimeout(function() { window.location.reload(); }, 500);
            })
            .catch(function() {
                alert('Error al guardar. Intente de nuevo.');
            })
            .finally(function() {
                btn.disabled = false;
                btn.innerHTML = prevHTML;
            });
        }

        function eliminarRegistro() {
            if (!currentRecordId) return;
            if (!confirm('¿Deshacer los cambios de este registro?\n\n• Si tiene horario: volverá a su estado original (retardo/asistencia).\n• Si fue creado manualmente: se eliminará por completo.')) return;

            fetch('/recursos-humanos/reloj/revertir/' + currentRecordId, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: '_method=DELETE'
            })
            .then(function(r) {
                if (!r.ok) throw new Error('server_error');
                return r.json();
            })
            .then(function(data) {
                cerrarModalEdicion();
                window.guardarScrollReloj(currentEmpleadoId);
                mostrarToastReloj(data.message || 'Registro revertido.');
                setTimeout(function() { window.location.reload(); }, 500);
            })
            .catch(function() {
                alert('Error al revertir. Intente de nuevo.');
            });
        }

        // Toast de confirmación ligero
        function mostrarToastReloj(msg) {
            var t = document.getElementById('toastReloj');
            if (!t) {
                t = document.createElement('div');
                t.id = 'toastReloj';
                t.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;padding:12px 20px;background:#1e293b;color:#fff;border-radius:10px;font-size:14px;box-shadow:0 4px 20px rgba(0,0,0,.25);transition:opacity .3s';
                document.body.appendChild(t);
            }
            t.textContent = '✓ ' + msg;
            t.style.opacity = '1';
            clearTimeout(t._hide);
            t._hide = setTimeout(function() { t.style.opacity = '0'; }, 3000);
        }

        // Nueva función para abrir modal desde "Sin Registro" pre-llenado
        function abrirModalIncidencia(empleadoId = null, fecha = null) {
            window.guardarScrollReloj(empleadoId);
            // Siempre resetear fechas para evitar que queden valores de usos anteriores
            const today = new Date().toISOString().slice(0, 10);
            const fechaUso = fecha || today;
            document.getElementById('modal_fecha_inicio').value = fechaUso;
            document.getElementById('modal_fecha_fin').value = fechaUso;

            if (empleadoId) {
                document.getElementById('modal_empleado_id').value = empleadoId;
                document.getElementById('modal_tipo_registro').value = 'falta';
                document.getElementById('modal_tipo_registro').dispatchEvent(new Event('change'));
            }
            document.getElementById('modalIncidencia').classList.remove('hidden');
        }
        function cerrarModalIncidencia() {
            document.getElementById('modalIncidencia').classList.add('hidden');
        }

        // Modal Justificar Falta
        function abrirModalJustificar(empleadoId, empleadoNombre, fecha, fechaDisplay) {
            window.guardarScrollReloj(empleadoId);
            document.getElementById('justificar_empleado_id').value = empleadoId;
            document.getElementById('justificar_fecha_inicio').value = fecha;
            document.getElementById('justificar_fecha_fin').value = fecha;
            document.getElementById('justificar_empleado_nombre').textContent = empleadoNombre;
            document.getElementById('justificar_fecha_display').textContent = fechaDisplay;
            document.getElementById('justificar_motivo').value = 'falta';
            document.getElementById('justificar_comentarios').value = '';
            document.getElementById('modalJustificar').classList.remove('hidden');
        }
        function cerrarModalJustificar() {
            document.getElementById('modalJustificar').classList.add('hidden');
        }
        document.getElementById('justificar_motivo').addEventListener('change', function() {
            var selected = this.options[this.selectedIndex];
            document.getElementById('justificar_es_justificado').value = selected.getAttribute('data-justificado') || '1';
        });

        // Modal Asistencia Manual
        function abrirModalAsistencia(empleadoId, empleadoNombre) {
            window.guardarScrollReloj(empleadoId);
            document.getElementById('manual_empleado_id').value = empleadoId;
            document.getElementById('manual_empleado_nombre').textContent = empleadoNombre;
            document.getElementById('manual_fecha').value = new Date().toISOString().slice(0, 10);
            document.getElementById('manual_entrada').value = '';
            document.getElementById('manual_salida').value = '';
            document.getElementById('modalAsistenciaManual').classList.remove('hidden');
        }
        function cerrarModalAsistencia() {
            document.getElementById('modalAsistenciaManual').classList.add('hidden');
        }

        // === Modal Borrar por Rango ===
        (function () {
            const MESES = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
            const hoy = new Date();
            const contenedor = document.getElementById('botonesRapidosMes');

            // Generar botones de los últimos 6 meses
            for (let i = 5; i >= 0; i--) {
                const d = new Date(hoy.getFullYear(), hoy.getMonth() - i, 1);
                const year = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const lastDay = new Date(year, d.getMonth() + 1, 0).getDate();
                const inicio = `${year}-${month}-01`;
                const fin    = `${year}-${month}-${String(lastDay).padStart(2, '0')}`;
                const label  = `${MESES[d.getMonth()]} ${year}`;

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = label;
                btn.className = 'text-xs font-semibold px-2 py-1.5 rounded-lg border border-orange-200 bg-white text-orange-700 hover:bg-orange-50 transition';
                btn.addEventListener('click', function () {
                    document.getElementById('clearRango_inicio').value = inicio;
                    document.getElementById('clearRango_fin').value   = fin;
                    actualizarPreview();
                    // Resaltar botón seleccionado
                    contenedor.querySelectorAll('button').forEach(b => b.classList.remove('bg-orange-100', 'ring-2', 'ring-orange-400'));
                    btn.classList.add('bg-orange-100', 'ring-2', 'ring-orange-400');
                });
                contenedor.appendChild(btn);
            }

            // Actualizar preview al cambiar fechas manualmente
            ['clearRango_inicio', 'clearRango_fin'].forEach(id => {
                document.getElementById(id).addEventListener('change', function () {
                    actualizarPreview();
                    contenedor.querySelectorAll('button').forEach(b => b.classList.remove('bg-orange-100', 'ring-2', 'ring-orange-400'));
                });
            });

            function actualizarPreview() {
                const inicio = document.getElementById('clearRango_inicio').value;
                const fin    = document.getElementById('clearRango_fin').value;
                const preview = document.getElementById('clearRango_preview');
                if (inicio && fin) {
                    preview.classList.remove('hidden');
                    preview.textContent = `⚠️ Se eliminarán todos los registros del reloj checador del ${inicio} al ${fin}. El resto del sistema no se verá afectado.`;
                } else {
                    preview.classList.add('hidden');
                }
            }
        })();

        function confirmarBorradoRango(e) {
            const inicio = document.getElementById('clearRango_inicio').value;
            const fin    = document.getElementById('clearRango_fin').value;
            if (!inicio || !fin) return false;
            return confirm(`¿Estás seguro de eliminar TODOS los registros de asistencia del ${inicio} al ${fin}?\n\nEsta acción no se puede deshacer.`);
        }

        // === Modal Revertir por Rango ===
        (function () {
            const MESES = ['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
            const hoy = new Date();
            const contenedor = document.getElementById('botonesRapidosMesRevertir');

            for (let i = 5; i >= 0; i--) {
                const d = new Date(hoy.getFullYear(), hoy.getMonth() - i, 1);
                const year  = d.getFullYear();
                const month = String(d.getMonth() + 1).padStart(2, '0');
                const lastDay = new Date(year, d.getMonth() + 1, 0).getDate();
                const inicio = `${year}-${month}-01`;
                const fin    = `${year}-${month}-${String(lastDay).padStart(2, '0')}`;
                const label  = `${MESES[d.getMonth()]} ${year}`;

                const btn = document.createElement('button');
                btn.type = 'button';
                btn.textContent = label;
                btn.className = 'text-xs font-semibold px-2 py-1.5 rounded-lg border border-amber-200 bg-white text-amber-700 hover:bg-amber-50 transition';
                btn.addEventListener('click', function () {
                    document.getElementById('revertirRango_inicio').value = inicio;
                    document.getElementById('revertirRango_fin').value   = fin;
                    actualizarPreviewRevertir();
                    contenedor.querySelectorAll('button').forEach(b => b.classList.remove('bg-amber-100', 'ring-2', 'ring-amber-400'));
                    btn.classList.add('bg-amber-100', 'ring-2', 'ring-amber-400');
                });
                contenedor.appendChild(btn);
            }

            ['revertirRango_inicio', 'revertirRango_fin', 'revertirRango_empleado'].forEach(id => {
                document.getElementById(id).addEventListener('change', function () {
                    actualizarPreviewRevertir();
                    if (id !== 'revertirRango_empleado') {
                        contenedor.querySelectorAll('button').forEach(b => b.classList.remove('bg-amber-100', 'ring-2', 'ring-amber-400'));
                    }
                });
            });

            function actualizarPreviewRevertir() {
                const inicio   = document.getElementById('revertirRango_inicio').value;
                const fin      = document.getElementById('revertirRango_fin').value;
                const sel      = document.getElementById('revertirRango_empleado');
                const empleado = sel.options[sel.selectedIndex]?.text || '';
                const preview  = document.getElementById('revertirRango_preview');
                if (inicio && fin && sel.value) {
                    preview.classList.remove('hidden');
                    preview.textContent = `↺ Se revertirán los registros de "${empleado}" del ${inicio} al ${fin} a su estado original importado.`;
                } else {
                    preview.classList.add('hidden');
                }
            }
        })();

        function confirmarRevertirRango(e) {
            const inicio   = document.getElementById('revertirRango_inicio').value;
            const fin      = document.getElementById('revertirRango_fin').value;
            const sel      = document.getElementById('revertirRango_empleado');
            const empleado = sel.options[sel.selectedIndex]?.text || 'el empleado seleccionado';
            if (!inicio || !fin || !sel.value) return false;
            return confirm(`¿Revertir los registros de "${empleado}" del ${inicio} al ${fin}?\n\n• Registros con horario: volverán al estado original (sin ediciones ni justificaciones).\n• Registros manuales sin horario: serán eliminados.\n\nEsta acción no se puede deshacer.`);
        }
    </script>

    {{-- MODAL ASISTENCIA MANUAL --}}
    <div id="modalAsistenciaManual" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="cerrarModalAsistencia()"></div>
            <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full z-10">
                <form method="POST" action="{{ route('rh.reloj.storeManual') }}">
                    @csrf
                    <input type="hidden" name="empleado_id" id="manual_empleado_id">
                    <div class="px-6 pt-6 pb-4">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="p-2 bg-emerald-100 rounded-full">
                                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Registrar Asistencia Manual</h3>
                                <p class="text-sm text-slate-500"><span id="manual_empleado_nombre" class="font-bold text-slate-700"></span></p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1">Fecha Inicio *</label>
                                    <input type="date" name="fecha_inicio" id="manual_fecha" required class="w-full rounded-xl border-slate-300 text-sm focus:ring-emerald-500 focus:border-emerald-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1">Fecha Fin</label>
                                    <input type="date" name="fecha_fin" id="manual_fecha_fin" class="w-full rounded-xl border-slate-300 text-sm focus:ring-emerald-500 focus:border-emerald-500">
                                    <p class="text-[10px] text-slate-400 mt-1">Dejar vacío para un solo día</p>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1">Hora Entrada</label>
                                    <input type="time" name="entrada" id="manual_entrada" class="w-full rounded-xl border-slate-300 text-sm focus:ring-emerald-500 focus:border-emerald-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-bold text-slate-700 mb-1">Hora Salida</label>
                                    <input type="time" name="salida" id="manual_salida" class="w-full rounded-xl border-slate-300 text-sm focus:ring-emerald-500 focus:border-emerald-500">
                                </div>
                            </div>
                            <p class="text-[10px] text-slate-400">Se aplica la misma entrada/salida a todos los días hábiles del rango (sáb/dom se omiten). Si ya existen registros, se actualizan.</p>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-6 py-4 rounded-b-2xl flex justify-end gap-3">
                        <button type="button" onclick="cerrarModalAsistencia()" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition">Cancelar</button>
                        <button type="submit" class="px-4 py-2 text-sm font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 transition shadow-sm">Guardar Registro</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL JUSTIFICAR FALTA --}}
    <div id="modalJustificar" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="cerrarModalJustificar()"></div>
            <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full z-10">
                <form method="POST" action="{{ route('rh.reloj.store') }}">
                    @csrf
                    <input type="hidden" name="empleado_id" id="justificar_empleado_id">
                    <input type="hidden" name="fecha_inicio" id="justificar_fecha_inicio">
                    <input type="hidden" name="fecha_fin" id="justificar_fecha_fin">
                    <input type="hidden" name="es_justificado" id="justificar_es_justificado" value="1">

                    <div class="px-6 pt-6 pb-4">
                        <div class="flex items-center gap-3 mb-5">
                            <div class="p-2 bg-orange-100 rounded-full">
                                <svg class="w-6 h-6 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            </div>
                            <div>
                                <h3 class="text-lg font-bold text-slate-900">Justificar Falta</h3>
                                <p class="text-sm text-slate-500">
                                    <span id="justificar_empleado_nombre" class="font-bold text-slate-700"></span>
                                    — <span id="justificar_fecha_display" class="text-slate-600"></span>
                                </p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Motivo *</label>
                                <select name="tipo_registro" id="justificar_motivo" required class="w-full rounded-xl border-slate-300 text-sm focus:ring-orange-500 focus:border-orange-500">
                                    <option value="falta" data-justificado="1">✅ Falta Justificada</option>
                                    <option value="falta" data-justificado="0">❌ Falta Injustificada</option>
                                    <option value="vacaciones" data-justificado="1">🌴 Vacaciones</option>
                                    <option value="incapacidad" data-justificado="1">🏥 Incapacidad</option>
                                    <option value="permiso" data-justificado="1">📄 Permiso con Goce</option>
                                    <option value="descanso" data-justificado="1">🏠 Día de Descanso</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm font-bold text-slate-700 mb-1">Comentarios / Observaciones</label>
                                <textarea name="comentarios" id="justificar_comentarios" rows="3" placeholder="Ej: Cita médica, permiso autorizado por gerencia..." class="w-full rounded-xl border-slate-300 text-sm focus:ring-orange-500 focus:border-orange-500 placeholder:text-slate-400"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-6 py-4 rounded-b-2xl flex justify-end gap-3">
                        <button type="button" onclick="cerrarModalJustificar()" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition">Cancelar</button>
                        <button type="submit" class="px-4 py-2 text-sm font-bold text-white bg-orange-600 rounded-xl hover:bg-orange-700 transition shadow-sm">Guardar Justificación</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    {{-- MODAL ENVIAR AVISO --}}
    <div id="modalAviso" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="cerrarModalAviso()"></div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg w-full">
                <form action="{{ route('rh.reloj.aviso') }}" method="POST">
                    @csrf
                    <input type="hidden" name="empleado_id" id="aviso_empleado_id">
                    <input type="hidden" name="cantidad_incidencias" id="aviso_cantidad">
                    <input type="hidden" name="periodo" id="aviso_periodo">

                    <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <div class="sm:flex sm:items-start">
                            <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-rose-100 sm:mx-0 sm:h-10 sm:w-10">
                                <svg class="h-6 w-6 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                            </div>
                            <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                                <h3 class="text-lg leading-6 font-medium text-gray-900" id="aviso_titulo_modal">Enviar Aviso de Asistencia</h3>
                                <div class="mt-4 space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Tipo de Alerta</label>
                                        <select name="tipo" id="aviso_tipo" onchange="actualizarMensajeAviso()" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                            <option value="retardos">Acumulación de Retardos</option>
                                            <option value="faltas">Acumulación de Faltas</option>
                                            <option value="general">Llamado de Atención General</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 flex justify-between">
                                            <span>Mensaje a enviar</span>
                                            <span class="text-xs text-gray-400 font-normal">Saldrá en el dashboard de <span id="aviso_nombre_display">el empleado</span></span>
                                        </label>
                                        <textarea name="mensaje" id="aviso_mensaje" rows="4" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" required></textarea>
                                    </div>
                                    <div class="bg-gray-50 border border-gray-200 rounded p-3 text-sm text-gray-600">
                                        Período evaluado: <span id="aviso_periodo_display" class="font-semibold text-gray-800"></span><br>
                                        Incidencias: <span id="aviso_cantidad_display" class="font-semibold text-rose-600">0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-rose-600 text-base font-medium text-white hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500 sm:ml-3 sm:w-auto sm:text-sm">
                            Enviar Aviso
                        </button>
                        <button type="button" onclick="cerrarModalAviso()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL HISTORIAL DE AVISOS --}}
    <div id="modalHistorialAvisos" class="fixed inset-0 z-50 hidden overflow-y-auto">
        <div class="flex items-center justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="cerrarModalHistorialAvisos()"></div>
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-amber-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-medium text-gray-900">Historial de Avisos: <span id="historial_nombre_display" class="font-bold"></span></h3>
                            <div class="mt-4 overflow-x-auto">
                                <table class="min-w-full divide-y divide-gray-200">
                                    <thead class="bg-gray-50">
                                        <tr>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha / Tipo</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Período / Incidencias</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Enviado por</th>
                                            <th class="px-3 py-2 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody id="historial_avisos_body" class="bg-white divide-y divide-gray-200">
                                        {{-- Llenado por JS --}}
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button type="button" onclick="cerrarModalHistorialAvisos()" class="w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:ml-3 sm:w-auto sm:text-sm">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        // Modal Aviso
        let currentAvisoNombre = '';
        let currentAvisoRetardos = 0;
        let currentAvisoFaltas = 0;

        function abrirModalAviso(id, nombre, retardos, faltas, periodo) {
            document.getElementById('aviso_empleado_id').value = id;
            document.getElementById('aviso_periodo').value = periodo;
            document.getElementById('aviso_periodo_display').innerText = periodo;
            document.getElementById('aviso_nombre_display').innerText = nombre;
            
            currentAvisoNombre = nombre;
            currentAvisoRetardos = retardos;
            currentAvisoFaltas = faltas;

            // Preseleccionar tipo según las incidencias (prioridad a faltas si hay ambos, o retardos si hay más, etc.)
            let selectTipo = document.getElementById('aviso_tipo');
            if (faltas > 0 && faltas >= retardos) {
                selectTipo.value = 'faltas';
            } else if (retardos > 0) {
                selectTipo.value = 'retardos';
            } else {
                selectTipo.value = 'general';
            }

            actualizarMensajeAviso();
            document.getElementById('modalAviso').classList.remove('hidden');
        }

        function cerrarModalAviso() {
            document.getElementById('modalAviso').classList.add('hidden');
        }

        function actualizarMensajeAviso() {
            let tipo = document.getElementById('aviso_tipo').value;
            let mensaje = document.getElementById('aviso_mensaje');
            let cantidadInput = document.getElementById('aviso_cantidad');
            let cantidadDisplay = document.getElementById('aviso_cantidad_display');

            if (tipo === 'retardos') {
                cantidadInput.value = currentAvisoRetardos;
                cantidadDisplay.innerText = currentAvisoRetardos + ' retardos';
                mensaje.value = `Estimado(a) ${currentAvisoNombre},\n\nSe le recuerda de manera atenta la importancia de cumplir con su horario de entrada. Actualmente, en este período se han registrado ${currentAvisoRetardos} retardos injustificados en su asistencia.\n\nLe solicitamos tomar las medidas necesarias.`;
            } else if (tipo === 'faltas') {
                cantidadInput.value = currentAvisoFaltas;
                cantidadDisplay.innerText = currentAvisoFaltas + ' faltas';
                mensaje.value = `Estimado(a) ${currentAvisoNombre},\n\nHemos detectado ${currentAvisoFaltas} faltas sin justificación en el período actual.\n\nLe recordamos la obligación de reportar o justificar sus inasistencias en tiempo y forma con el departamento correspondiente.`;
            } else {
                cantidadInput.value = 0;
                cantidadDisplay.innerText = '0 (Llamado General)';
                mensaje.value = `Estimado(a) ${currentAvisoNombre},\n\n`;
            }
        }

        // Historial de Avisos
        function abrirModalHistorialAvisos(nombre, avisos) {
            document.getElementById('historial_nombre_display').innerText = nombre;
            let tbody = document.getElementById('historial_avisos_body');
            tbody.innerHTML = '';

            avisos.forEach(aviso => {
                let tr = document.createElement('tr');
                
                // Formatear Fecha
                let fechaObj = new Date(aviso.created_at);
                let fechaStr = fechaObj.toLocaleDateString('es-ES', { day: '2-digit', month: 'short', year: 'numeric' });
                
                // Tipo y Color
                let tipoBadge = '';
                if(aviso.tipo === 'retardos') tipoBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-amber-100 text-amber-800">Retardos</span>';
                else if(aviso.tipo === 'faltas') tipoBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Faltas</span>';
                else tipoBadge = '<span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">General</span>';

                // Estado de Lectura
                let estadoBadge = aviso.leido 
                    ? `<span class="inline-flex items-center gap-1 text-xs text-emerald-600 font-medium"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> Leído</span><br><span class="text-[10px] text-gray-400">el ${new Date(aviso.leido_at).toLocaleDateString('es-ES')}</span>` 
                    : '<span class="inline-flex items-center gap-1 text-xs text-amber-500 font-medium"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg> Pendiente</span>';

                tr.innerHTML = `
                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-900">
                        <div class="font-medium">${fechaStr}</div>
                        <div class="mt-1">${tipoBadge}</div>
                    </td>
                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500">
                        <div class="font-medium text-gray-900">${aviso.periodo}</div>
                        <div class="text-xs">${aviso.cantidad_incidencias} incidencias</div>
                    </td>
                    <td class="px-3 py-3 whitespace-nowrap text-sm text-gray-500">
                        ${aviso.enviado_por ? aviso.enviado_por.name : 'Sistema'}
                    </td>
                    <td class="px-3 py-3 whitespace-nowrap text-sm">
                        ${estadoBadge}
                    </td>
                `;
                tbody.appendChild(tr);
            });

            document.getElementById('modalHistorialAvisos').classList.remove('hidden');
        }

        function cerrarModalHistorialAvisos() {
            document.getElementById('modalHistorialAvisos').classList.add('hidden');
        }
    </script>
    @endpush
@endsection