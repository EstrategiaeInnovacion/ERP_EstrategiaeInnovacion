@extends('layouts.erp')

@section('title', 'Tablero de Actividades')

@section('content')
{{-- ======================================================= --}}
{{-- 1. LÓGICA INICIAL Y VARIABLES DE VISUALIZACIÓN          --}}
{{-- ======================================================= --}}
@php
    $posicionUser = strtolower(Auth::user()->empleado->posicion ?? '');
    
    // Validar si tiene puesto de Planificación
    $esPuestoPlanificador = isset($esPuestoPlanificador) ? $esPuestoPlanificador : \Illuminate\Support\Str::contains($posicionUser, ['anexo 24', 'anexo24', 'post-operacion', 'post operacion', 'post operación']);
    
    // Validar Horario (configurable desde BD, fallback lunes 9-11)
    $esHorarioPermitido = isset($esHorarioPermitido) ? $esHorarioPermitido : \App\Models\PlaneacionVentana::estaAbierta();
    
    // Datos Dinámicos
    $areasDisponibles = isset($areasSistema) ? $areasSistema : collect(['General', 'Operativo', 'Administrativo']);
    $usersList = isset($empleadosAsignables) ? $empleadosAsignables : collect([]);
    $usersWithPending = isset($usersWithPending) ? $usersWithPending : [];

    // Variables de Filtro
    $filterOrigin = request('filter_origin', 'todos');
    // Las variables $startDate, $endDate vienen del controlador
@endphp

<div class="min-h-screen bg-slate-50/50 py-8" x-data="{ showFilters: false }">
    <div class="max-w-[98%] mx-auto space-y-6">
        
        {{-- ======================================================= --}}
        {{-- 2. HEADER: TÍTULO, USUARIO Y SELECTOR DE FECHAS         --}}
        {{-- ======================================================= --}}
        <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center bg-white p-4 rounded-2xl shadow-sm border border-slate-100 gap-4 transition-all hover:shadow-md">
            
            {{-- IZQUIERDA: Info del Tablero Actual --}}
            <div class="flex items-center gap-4 min-w-[250px]">
                <div class="relative">
                    <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-indigo-600 to-violet-700 text-white flex items-center justify-center font-bold text-lg shadow-md">
                        {{ substr($targetUser->name, 0, 2) }}
                    </div>
                    @if($targetUser->id === Auth::id())
                        <div class="absolute -bottom-1 -right-1 w-4 h-4 bg-emerald-500 border-2 border-white rounded-full" title="Tú"></div>
                    @endif
                </div>
                <div>
                    <h1 class="text-xl font-bold text-slate-800 tracking-tight leading-none">
                        {{ $targetUser->id === Auth::id() ? 'Mi Tablero' : $targetUser->name }}
                    </h1>
                    <span class="text-xs text-slate-400 block mt-1">
                        {{ $targetUser->empleado->posicion ?? 'Colaborador' }}
                    </span>
                </div>
            </div>

            {{-- CENTRO: Selector de Empleado (SOLO JEFES) --}}
            @if(($esSupervisor || $esDireccion) && $teamUsers->count() > 0)
                <div class="w-full xl:w-auto flex-1 max-w-sm">
                    <form method="GET" id="userSelectorForm">
                        {{-- Preservar filtros actuales --}}
                        @foreach(request()->except(['user_id', 'page']) as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <select name="user_id" onchange="document.getElementById('userSelectorForm').submit()" 
                                class="block w-full rounded-lg border-slate-200 bg-slate-50 py-2 text-xs font-bold text-slate-700 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 cursor-pointer">
                            <option value="{{ Auth::id() }}">Ver mi propio tablero</option>
                            <optgroup label="Mi Equipo">
                                @foreach($teamUsers as $u)
                                    @if($u->id !== Auth::id())
                                        <option value="{{ $u->id }}" {{ $targetUser->id == $u->id ? 'selected' : '' }}>
                                            {{ $u->name }} {{ in_array($u->id, $usersWithPending) ? '(⚠)' : '' }}
                                        </option>
                                    @endif
                                @endforeach
                            </optgroup>
                        </select>
                    </form>
                </div>
            @endif
            
            {{-- DERECHA: SELECTOR DE FECHAS (Rango Personalizado) --}}
            <div class="w-full xl:w-auto">
                <form method="GET" class="flex flex-wrap items-end gap-2 bg-slate-50 p-2 rounded-xl border border-slate-200">
                    {{-- Mantener otros filtros excepto fechas --}}
                    @foreach(request()->except(['date_start', 'date_end', 'ref_date', 'range']) as $k=>$v) 
                        <input type="hidden" name="{{ $k }}" value="{{ $v }}"> 
                    @endforeach

                    <div>
                        <label class="block text-[9px] font-bold text-slate-400 uppercase ml-1 mb-0.5">Desde</label>
                        <input type="date" name="date_start" value="{{ request('date_start', $startDate->format('Y-m-d')) }}" 
                               class="text-xs font-bold text-slate-700 border-slate-200 rounded-lg py-1.5 px-2 shadow-sm focus:ring-indigo-500 h-8">
                    </div>
                    <div>
                        <label class="block text-[9px] font-bold text-slate-400 uppercase ml-1 mb-0.5">Hasta</label>
                        <input type="date" name="date_end" value="{{ request('date_end', $endDate->format('Y-m-d')) }}" 
                               class="text-xs font-bold text-slate-700 border-slate-200 rounded-lg py-1.5 px-2 shadow-sm focus:ring-indigo-500 h-8">
                    </div>
                    <button type="submit" class="bg-indigo-600 text-white p-2 rounded-lg hover:bg-indigo-700 transition shadow-md h-8 flex items-center" title="Aplicar Filtro de Fechas">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </button>
                    
                    {{-- Botón Reset a "Hoy/Semana Actual" --}}
                    <a href="{{ route('activities.index', ['user_id' => $targetUser->id]) }}" class="text-slate-400 hover:text-indigo-600 p-2 transition" title="Restablecer a semana actual">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    </a>
                </form>
            </div>
        </div>

        {{-- ======================================================= --}}
        {{-- 3. ALERTAS (GLOBALES Y PERSONALES)                      --}}
        {{-- ======================================================= --}}
        
        @if(($esSupervisor || $esDireccion) && $globalPendingCount > 0)
            <div class="bg-orange-50 border-l-4 border-orange-400 p-3 rounded-r-lg shadow-sm flex items-center gap-3 animate-fade-in-down">
                <div class="text-orange-500"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg></div>
                <p class="text-xs font-bold text-orange-800">Atención: Tienes {{ $globalPendingCount }} actividades pendientes de aprobación en tu equipo.</p>
            </div>
        @endif

        @if($targetUser->id === Auth::id() && $misRechazos->count() > 0)
            <div class="space-y-2">
            @foreach($misRechazos as $rej)
                <div class="bg-red-50 border border-red-200 p-3 rounded-lg flex justify-between items-center shadow-sm animate-fade-in-down">
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="text-xs font-bold text-red-700">Rechazada: "{{ $rej->nombre_actividad }}" ({{ $rej->motivo_rechazo }})</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <form action="{{ route('activities.destroy', $rej->id) }}" method="POST" onsubmit="return confirm('¿Eliminar esta actividad? Una vez eliminada no se podrá recuperar.')" class="inline">
                            @csrf @method('DELETE')
                            <button type="submit" class="bg-white border border-slate-300 text-slate-600 px-3 py-1 rounded text-[10px] font-bold uppercase hover:bg-slate-100 hover:text-slate-800 transition flex items-center gap-1">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                Eliminar
                            </button>
                        </form>
                        <button onclick='openNotes(@json($rej), true)' class="bg-white border border-red-200 text-red-600 px-3 py-1 rounded text-[10px] font-bold uppercase hover:bg-red-100 transition flex items-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            Corregir
                        </button>
                    </div>
                </div>
            @endforeach
            </div>
        @endif

        {{-- ======================================================= --}}
        {{-- 4. BARRA DE HERRAMIENTAS Y FILTROS VISUALES             --}}
        {{-- ======================================================= --}}
        {{-- 3.1. SIDEBAR DE PROYECTOS --}}
        {{-- ======================================================= --}}
        @php
            $proyectoSeleccionadoId = request('proyecto_id');
            $mostrarSidebarProyectos = (isset($proyectos) && $proyectos->count() > 0) || !empty($proyectoSeleccionadoId);
        @endphp
        @if($mostrarSidebarProyectos)
        @php
            $proyectoSeleccionadoId = request('proyecto_id');
        @endphp
        <div class="bg-white rounded-2xl shadow-sm border border-slate-100 p-4">
            <div class="flex items-center justify-between mb-3">
                <h3 class="text-xs font-bold text-slate-600 uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                    Proyectos
                </h3>
                @if(isset($esRh) && $esRh)
                    <a href="{{ route('proyectos.index') }}" class="text-[10px] text-indigo-600 hover:underline font-bold">Administrar</a>
                @else
                    <a href="{{ route('proyectos.index') }}" class="text-[10px] text-indigo-600 hover:underline font-bold">Ver todos</a>
                @endif
            </div>
            @php
                $proyectosList = isset($proyectos) ? $proyectos : collect();
                $proyectoSeleccionado = null;

                // Si hay un proyecto seleccionado pero no está en la lista, buscarlo para mostrarlo
                if (!empty($proyectoSeleccionadoId) && $proyectoSeleccionadoId !== 'sin_proyecto' && !$proyectosList->contains('id', (int)$proyectoSeleccionadoId)) {
                    $proyectoSeleccionado = Proyecto::find((int)$proyectoSeleccionadoId);
                }
            @endphp
            <div class="flex flex-wrap gap-2">
                {{-- Opción: Todos los proyectos --}}
                <a href="{{ request()->fullUrlWithQuery(['proyecto_id' => null]) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all flex items-center gap-2 {{ !$proyectoSeleccionadoId ? 'bg-indigo-100 text-indigo-700 border border-indigo-300' : 'bg-slate-50 text-slate-500 border border-slate-200 hover:bg-slate-100' }}">
                    <span class="whitespace-nowrap">Todos</span>
                </a>
                
                {{-- Lista de proyectos --}}
                @foreach($proyectosList as $proy)
                    <a href="{{ request()->fullUrlWithQuery(['proyecto_id' => $proy->id]) }}" 
                       class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all flex items-center gap-2 {{ $proyectoSeleccionadoId == $proy->id ? 'bg-indigo-100 text-indigo-700 border border-indigo-300' : 'bg-slate-50 text-slate-500 border border-slate-200 hover:bg-slate-100' }}"
                       title="{{ $proy->nombre }}">
                        <span class="whitespace-nowrap truncate max-w-[120px]">{{ $proy->nombre }}</span>
                        <span class="text-[10px] text-slate-400">({{ $proy->actividades()->count() }})</span>
                    </a>
                @endforeach

                {{-- Proyecto seleccionado que no está en la lista (ej: responsable IT que no es owner) --}}
                @if($proyectoSeleccionado)
                    <span class="px-3 py-1.5 rounded-lg text-xs font-medium bg-indigo-100 text-indigo-700 border border-indigo-300 flex items-center gap-2">
                        <span class="whitespace-nowrap truncate max-w-[120px]">{{ $proyectoSeleccionado->nombre }}</span>
                        <span class="text-[10px] text-slate-400">({{ $proyectoSeleccionado->actividades()->count() }})</span>
                    </span>
                @endif

                {{-- Opción: Sin proyecto --}}
                <a href="{{ request()->fullUrlWithQuery(['proyecto_id' => 'sin_proyecto']) }}" 
                   class="px-3 py-1.5 rounded-lg text-xs font-medium transition-all flex items-center gap-2 {{ $proyectoSeleccionadoId == 'sin_proyecto' ? 'bg-amber-100 text-amber-700 border border-amber-300' : 'bg-slate-50 text-slate-500 border border-slate-200 hover:bg-slate-100' }}">
                    <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span class="whitespace-nowrap">Sin proyecto</span>
                </a>
            </div>
        </div>
        @endif

        {{-- ======================================================= --}}
        <div class="flex flex-col xl:flex-row justify-between items-center gap-4 py-2">
            
            {{-- IZQUIERDA: FILTROS DE ORIGEN --}}
            <div class="flex flex-col lg:flex-row items-center gap-3 w-full xl:w-auto">
                <div class="flex items-center bg-white p-1 rounded-xl border border-slate-200 shadow-sm w-full lg:w-auto overflow-x-auto">
                    {{-- Filtro: TODOS --}}
                    <a href="{{ request()->fullUrlWithQuery(['filter_origin' => 'todos']) }}" 
                       class="px-3 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-2 {{ $filterOrigin == 'todos' ? 'bg-slate-800 text-white shadow-md' : 'text-slate-500 hover:bg-slate-50' }}">
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg> 
                        <span class="whitespace-nowrap">Todos</span>
                    </a>
                    
                    {{-- Filtro: MIS TAREAS --}}
                    <a href="{{ request()->fullUrlWithQuery(['filter_origin' => 'propias']) }}" 
                       class="px-3 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-2 {{ $filterOrigin == 'propias' ? 'bg-indigo-600 text-white shadow-md' : 'text-slate-500 hover:bg-indigo-50 hover:text-indigo-600' }}">
                       <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg> 
                       <span class="whitespace-nowrap">Mis Tareas</span>
                    </a>

                    {{-- Filtro: RECIBIDAS --}}
                    <a href="{{ request()->fullUrlWithQuery(['filter_origin' => 'recibidas']) }}" 
                       class="px-3 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-2 {{ $filterOrigin == 'recibidas' ? 'bg-blue-500 text-white shadow-md' : 'text-slate-500 hover:bg-blue-50 hover:text-blue-600' }}">
                       <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 4H6a2 2 0 00-2 2v12a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-2m-4-1v8m0 0l3-3m-3 3L9 8m-5 5h2.586a1 1 0 01.707.293l2.414 2.414a1 1 0 00.707.293h3.172a1 1 0 00.707-.293l2.414-2.414a1 1 0 01.707-.293H20"/></svg> 
                       <span class="whitespace-nowrap">Recibidas</span>
                    </a>

                    {{-- Filtro: DELEGADAS (Solo visible si es mi tablero) --}}
                    @if($targetUser->id == Auth::id())
                    <a href="{{ request()->fullUrlWithQuery(['filter_origin' => 'delegadas']) }}" 
                       class="px-3 py-2 rounded-lg text-xs font-bold transition-all flex items-center gap-2 {{ $filterOrigin == 'delegadas' ? 'bg-purple-500 text-white shadow-md' : 'text-slate-500 hover:bg-purple-50 hover:text-purple-600' }}">
                       <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg> 
                       <span class="whitespace-nowrap">Delegadas</span>
                    </a>
                    @endif
                </div>

                {{-- Checkbox Ver Terminados --}}
                <form method="GET" id="filterOptions" class="flex items-center h-full">
                    @foreach(request()->except(['ver_historial']) as $k=>$v) <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endforeach
                    <label class="flex items-center gap-2 cursor-pointer bg-white px-3 py-2 rounded-xl border border-slate-200 shadow-sm hover:border-indigo-300 transition select-none h-full">
                        <input type="checkbox" name="ver_historial" value="1" onchange="document.getElementById('filterOptions').submit()" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4" {{ $verTodo ? 'checked' : '' }}>
                        <span class="text-xs font-bold text-slate-600">Ver Terminados</span>
                    </label>
                </form>

                {{-- Checkbox Ver Eliminadas (solo coordinadores / dirección) --}}
                @if($esDireccion || (($esCoordinador ?? false) || $esSupervisor) && $targetUser->id !== Auth::id())
                <form method="GET" id="filterEliminadas" class="flex items-center h-full">
                    @foreach(request()->except(['ver_eliminadas']) as $k=>$v) <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endforeach
                    <label class="flex items-center gap-2 cursor-pointer bg-white px-3 py-2 rounded-xl border border-red-200 shadow-sm hover:border-red-300 transition select-none h-full">
                        <input type="checkbox" name="ver_eliminadas" value="1" onchange="document.getElementById('filterEliminadas').submit()" class="rounded border-red-300 text-red-500 focus:ring-red-400 w-4 h-4" {{ request('ver_eliminadas') ? 'checked' : '' }}>
                        <span class="text-xs font-bold text-red-500">Ver Eliminadas</span>
                    </label>
                </form>
                @endif
            </div>

            {{-- DERECHA: Botones de Acción --}}
            <div class="flex gap-2 w-full sm:w-auto">
                @if($targetUser->id === Auth::id() && $esPuestoPlanificador)
                    @if($esHorarioPermitido)
                        <button onclick="openPlanModal()" class="flex-1 sm:flex-none bg-white text-indigo-600 border border-indigo-200 px-4 py-2 rounded-lg text-xs font-bold shadow-sm hover:bg-indigo-50 transition flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg> Planificar
                        </button>
                    @else
                        <button disabled class="flex-1 sm:flex-none bg-slate-100 text-slate-400 border border-slate-200 px-4 py-2 rounded-lg text-xs font-bold cursor-not-allowed flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Cerrado
                        </button>
                    @endif
                @endif

                {{-- Botón Admin: Configurar ventana de planeación --}}
                @if(!empty($puedeGestionarPlaneacion))
                    <button onclick="document.getElementById('modalPlaneacionVentana').classList.remove('hidden')"
                        class="bg-white text-violet-600 border border-violet-200 px-3 py-2 rounded-lg text-xs font-bold shadow-sm hover:bg-violet-50 transition flex items-center gap-2"
                        title="Configurar ventana de planeación">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <span class="hidden md:inline">Ventana</span>
                    </button>
                @endif
                
                {{-- Botón Generar Reporte --}}
                <button onclick="document.getElementById('reportModal').classList.remove('hidden')" class="bg-white text-slate-600 border border-slate-200 px-3 py-2 rounded-lg text-xs font-bold shadow-sm hover:bg-slate-50 hover:text-indigo-600 transition flex items-center gap-2" title="Generar PDF Cliente">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="hidden md:inline">Reporte</span>
                </button>

                {{-- Botón Exportar Excel --}}
                <button onclick="document.getElementById('excelModal').classList.remove('hidden')" class="bg-emerald-600 text-white border border-emerald-700 px-3 py-2 rounded-lg text-xs font-bold shadow-sm hover:bg-emerald-700 transition flex items-center gap-2" title="Exportar a Excel">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="hidden md:inline">Excel</span>
                </button>

                {{-- Botón Importar: disponible para todos --}}
                <button onclick="document.getElementById('importModal').classList.remove('hidden')" class="bg-blue-600 text-white border border-blue-700 px-3 py-2 rounded-lg text-xs font-bold shadow-sm hover:bg-blue-700 transition flex items-center gap-2" title="Cargar tareas desde Excel">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                    <span class="hidden md:inline">Importar</span>
                </button>

                <button onclick="document.getElementById('quickCreateModal').classList.remove('hidden')" class="flex-1 sm:flex-none bg-indigo-600 text-white px-5 py-2 rounded-lg text-xs font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 transition flex items-center justify-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> 
                    {{ $targetUser->id === Auth::id() ? 'Nueva' : 'Asignar' }}
                </button>


            </div>
        </div>

        {{-- ======================================================= --}}
        {{-- 5. TABLA PRINCIPAL                                      --}}
        {{-- ======================================================= --}}
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-slate-100 relative">
            <div class="bg-slate-800 px-6 py-3 border-b border-slate-700 flex justify-between items-center">
                <h3 class="text-xs font-bold text-white uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-4 h-4 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Listado de Actividades ({{ $startDate->format('d/m') }} - {{ $endDate->format('d/m') }})
                </h3>
                <span class="bg-indigo-600 text-white text-[10px] px-2 py-0.5 rounded-full font-mono font-bold">{{ $mainActivities->count() }}</span>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs text-left border-collapse">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                        <tr>
                            <th class="px-3 py-3 text-center w-8">
                                <input type="checkbox" id="selectAllCb" onchange="toggleSelectAll(this)"
                                    class="rounded border-slate-400 text-red-500 focus:ring-red-400 cursor-pointer"
                                    title="Seleccionar todas las eliminables">
                            </th>
                            <th class="px-4 py-3 text-center">#</th>
                            <th class="px-4 py-3 text-center w-20">Origen</th>
                            <th class="px-4 py-3 text-center">Prio</th>
                            <th class="px-4 py-3 min-w-[250px]">Descripción</th>
                            <th class="px-4 py-3">Cliente/Área</th>
                            <th class="px-4 py-3 text-center">Responsable</th>
                            <th class="px-4 py-3 text-center">Fecha Asignación</th>
                            <th class="px-4 py-3 text-center">Fecha Compromiso</th>
                            <th class="px-2 py-3 text-center bg-slate-100/50 border-l border-slate-100">Fin Real</th>
                            <th class="px-2 py-3 text-center bg-slate-100/50">Días</th>
                            <th class="px-2 py-3 text-center bg-slate-100/50 border-r border-slate-100">%</th>
                            <th class="px-4 py-3 text-center">Estatus</th>
                            <th class="px-4 py-3 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($mainActivities as $index => $act)
                            @php
                                $isMine = ($act->user_id === Auth::id());
                                $isSelfAssigned = ($act->asignado_por === Auth::id());
                                
                                // Determinar origen
                                $rowType = 'ajena';
                                $rowClass = 'border-l-4 border-sky-400 bg-sky-50/20 hover:bg-sky-50/50';

                                if ($isMine && $isSelfAssigned) {
                                    $rowType = 'personal'; 
                                    $rowClass = 'border-l-4 border-indigo-200 hover:bg-indigo-50/20';
                                } elseif ($isMine && !$isSelfAssigned) {
                                    $rowType = 'recibida';
                                    $rowClass = 'border-l-4 border-blue-400 bg-blue-50/30 hover:bg-blue-50/60';
                                } elseif (!$isMine && $isSelfAssigned) {
                                    $rowType = 'delegada';
                                    $rowClass = 'border-l-4 border-purple-400 bg-purple-50/30 hover:bg-purple-50/60';
                                }

                                // Estilos extra
                                if (in_array($act->estatus, ['Completado', 'Completado con retardo'])) $rowClass .= ' opacity-60 bg-slate-50/50';
                                if ($act->estatus == 'Por Aprobar') $rowClass .= ' bg-orange-50/30';
                                if ($act->estatus == 'Por Validar') $rowClass .= ' bg-purple-50/20'; // Fondo tenue para validación

                                $puedeEliminarBulk = $act->estatus !== 'Por Aprobar'
                                    && (($act->user_id == Auth::id() && (is_null($act->asignado_por) || $act->asignado_por == Auth::id()))
                                        || $act->asignado_por == Auth::id());
                            @endphp

                            <tr class="transition-colors group {{ $rowClass }}" data-activity-id="{{ $act->id }}">
                                <td class="px-3 py-3 text-center">
                                    @if($puedeEliminarBulk)
                                        <input type="checkbox" class="bulk-cb rounded border-slate-300 text-red-500 focus:ring-red-400 cursor-pointer"
                                            data-id="{{ $act->id }}" onchange="updateBulkBar()">
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-center text-slate-400 font-mono">{{ $index + 1 }}</td>
                                
                                <td class="px-4 py-3 text-center">
                                    @if($rowType == 'personal')
                                        <div class="flex flex-col items-center justify-center" title="Personal"><div class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></div></div>
                                    @elseif($rowType == 'recibida')
                                        <div class="flex flex-col items-center justify-center" title="De: {{ $act->asignador->name ?? '?' }}"><div class="w-5 h-5 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center animate-pulse"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg></div></div>
                                    @elseif($rowType == 'delegada')
                                        <div class="flex flex-col items-center justify-center" title="Para: {{ $act->user->name }}"><div class="w-5 h-5 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg></div></div>
                                    @elseif($rowType == 'ajena')
                                        <div class="flex flex-col items-center justify-center" title="Subordinado: {{ $act->user->name }}"><div class="w-5 h-5 rounded-full bg-sky-100 text-sky-600 flex items-center justify-center"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div></div>
                                    @endif
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <span class="inline-flex items-center justify-center w-5 h-5 rounded-full text-[9px] font-bold text-white shadow-sm
                                        {{ $act->prioridad == 'Alta' ? 'bg-red-500' : ($act->prioridad == 'Media' ? 'bg-amber-400' : 'bg-blue-300') }}">
                                        {{ substr($act->prioridad, 0, 1) }}
                                    </span>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex flex-col">
                                        <span class="{{ in_array($act->estatus, ['Completado', 'Completado con retardo']) ? 'line-through text-slate-400' : 'text-slate-800 font-semibold' }} text-xs leading-snug">
                                            {{ $act->nombre_actividad }}
                                        </span>
                                        <div class="flex flex-wrap items-center gap-1 mt-0.5">
                                            @if($rowType == 'delegada') <span class="text-[9px] text-purple-600 bg-purple-50 px-1 rounded border border-purple-100">↪ {{ strtok($act->user->name, ' ') }}</span> @endif
                                            @if($rowType == 'recibida') <span class="text-[9px] text-blue-600 bg-blue-50 px-1 rounded border border-blue-100">↩ {{ strtok($act->asignador->name ?? '?', ' ') }}</span> @endif
                                            @if($rowType == 'ajena') <span class="text-[9px] text-sky-600 bg-sky-50 px-1 rounded border border-sky-200">👤 {{ strtok($act->user->name, ' ') }}</span> @endif
                                            @if($act->hora_inicio_programada) <span class="text-[9px] text-slate-500 font-mono bg-slate-100 px-1 rounded">{{ \Carbon\Carbon::parse($act->hora_inicio_programada)->format('H:i') }}</span> @endif
                                        </div>
                                        @if($act->comentarios)
                                            <div class="flex items-center gap-1 text-[9px] text-indigo-400 truncate max-w-[250px] mt-0.5" title="{{ $act->comentarios }}">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                                {{ $act->comentarios }}
                                            </div>
                                        @endif
                                    </div>
                                </td>

                                <td class="px-4 py-3">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-600 text-[10px]">{{ Str::limit($act->cliente ?? '-', 15) }}</span>
                                        <span class="text-[9px] text-slate-400">{{ $act->area ?? 'General' }}</span>
                                    </div>
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <span class="text-[10px] font-semibold {{ $act->user_id === Auth::id() ? 'text-indigo-600' : 'text-slate-600' }}">
                                        {{ strtok($act->user->name ?? 'N/A', ' ') }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <span class="text-[10px] text-slate-500">
                                        {{ $act->created_at->format('d M') }}
                                    </span>
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <span class="font-bold text-[10px] {{ $act->fecha_compromiso->isToday() ? 'text-indigo-600 bg-indigo-50 px-1 rounded' : 'text-slate-600' }}">
                                        {{ $act->fecha_compromiso->format('d M') }}
                                    </span>
                                </td>

                                <td class="px-2 py-3 text-center border-l border-slate-100">
                                    <span class="text-[10px] text-slate-500">{{ $act->fecha_final ? $act->fecha_final->format('d M') : '-' }}</span>
                                </td>

                                <td class="px-2 py-3 text-center">
                                    @php $color = ($act->resultado_dias !== null && $act->resultado_dias > $act->metrico) ? 'text-red-600 font-bold' : 'text-slate-400'; @endphp
                                    <span class="text-[10px] {{ $color }}">{{ $act->resultado_dias ?? '-' }}</span>
                                </td>

                                <td class="px-2 py-3 text-center border-r border-slate-100">
                                    <span class="text-[10px] font-bold {{ ($act->porcentaje ?? 0) < 100 ? 'text-orange-500' : 'text-slate-700' }}">{{ isset($act->porcentaje) ? number_format($act->porcentaje,0).'%' : '-' }}</span>
                                </td>

                                <td class="px-4 py-3 text-center">
                                    @php
                                        $badges = [
                                            'Por Aprobar'=>'bg-orange-100 text-orange-700', 
                                            'Por Validar'=>'bg-purple-100 text-purple-700 animate-pulse',
                                            'Planeado'=>'bg-indigo-100 text-indigo-700', 
                                            'En proceso'=>'bg-blue-100 text-blue-700', 
                                            'Completado'=>'bg-emerald-100 text-emerald-700', 
                                            'Completado con retardo'=>'bg-amber-100 text-amber-700',
                                            'Retardo'=>'bg-red-100 text-red-700', 
                                            'Rechazado'=>'bg-red-200 text-red-800'
                                        ];
                                    @endphp
                                    <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase {{ $badges[$act->estatus] ?? 'bg-gray-100' }}">{{ $act->estatus }}</span>
                                </td>

                                <td class="px-4 py-3 text-center">
                                    <div class="flex justify-center items-center gap-1">
                                        
                                        {{-- CASO 1: APROBACIÓN DE ASIGNACIÓN --}}
                                        @if($act->estatus == 'Por Aprobar')
                                            @php
                                                $canApprove = false;
                                                if ($esDireccion) $canApprove = true;
                                                elseif ($esSupervisor) {
                                                    $isSupToSup = (\App\Models\Empleado::where('user_id', $act->asignado_por)->exists() && \App\Models\Empleado::where('supervisor_id', \App\Models\Empleado::where('user_id', $act->asignado_por)->value('id'))->exists()) 
                                                                  && (\App\Models\Empleado::where('user_id', $act->user_id)->exists() && \App\Models\Empleado::where('supervisor_id', \App\Models\Empleado::where('user_id', $act->user_id)->value('id'))->exists());
                                                    if (!$isSupToSup) {
                                                        $targetEmp = $act->user->empleado ?? null;
                                                        $myEmpId = Auth::user()->empleado->id ?? null;
                                                        if ($targetEmp && $myEmpId && $targetEmp->supervisor_id === $myEmpId) $canApprove = true;
                                                    }
                                                }
                                                if ($act->user_id === Auth::id()) $canApprove = false;
                                            @endphp

                                            @if($canApprove)
                                                <form action="{{ route('activities.approve', $act->id) }}" method="POST">@csrf @method('PUT')<button class="text-emerald-500 hover:bg-emerald-50 p-1.5 rounded" title="Aprobar"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></button></form>
                                                <button onclick="rejectActivity({{ $act->id }})" class="text-red-500 hover:bg-red-50 p-1.5 rounded" title="Rechazar"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                                            @else
                                                <svg class="w-4 h-4 text-slate-300" fill="none" viewBox="0 0 24 24" stroke="currentColor" title="Esperando autorización de nivel superior"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                                            @endif

                                        {{-- CASO 2: VALIDACIÓN DE CIERRE --}}
                                        @elseif($act->estatus == 'Por Validar')
                                            @php $puedeEliminar = ($act->user_id == Auth::id() && (is_null($act->asignado_por) || $act->asignado_por == Auth::id())) || $act->asignado_por == Auth::id(); @endphp
                                            @if($esSupervisor || $esDireccion)
                                                <form action="{{ route('activities.validate', $act->id) }}" method="POST">@csrf @method('PUT')
                                                    <button class="bg-purple-600 text-white px-2 py-0.5 rounded text-[9px] font-bold hover:bg-purple-700 shadow-sm flex items-center gap-1" title="Validar Cierre">
                                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> VALIDAR
                                                    </button>
                                                </form>
                                                <button onclick="rejectActivity({{ $act->id }})" class="text-red-400 hover:text-red-600 p-1" title="Rechazar entrega"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                                            @else
                                                <span class="text-[9px] text-purple-400 italic font-medium flex items-center gap-1"><svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Revisión</span>
                                            @endif
                                            @if($puedeEliminar)
                                                <form action="{{ route('activities.destroy', $act->id) }}" method="POST" onsubmit="return confirm('¿Eliminar esta actividad? No se podrá recuperar.')" class="inline">@csrf @method('DELETE')<button class="text-slate-300 hover:text-red-500 p-1.5" title="Eliminar"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></form>
                                            @endif

                                        {{-- CASO 3: FLUJO NORMAL --}}
                                        @elseif($act->estatus == 'Planeado' && !$isHistoryView && $act->user_id == Auth::id())
                                            <form action="{{ route('activities.start', $act->id) }}" method="POST">@csrf @method('PUT')<button class="bg-indigo-600 text-white px-2 py-0.5 rounded text-[9px] font-bold hover:bg-indigo-700">INICIAR</button></form>
                                            @php $puedeEliminar = ($act->user_id == Auth::id() && (is_null($act->asignado_por) || $act->asignado_por == Auth::id())) || $act->asignado_por == Auth::id(); @endphp
                                            @if($puedeEliminar)
                                                <form action="{{ route('activities.destroy', $act->id) }}" method="POST" onsubmit="return confirm('¿Eliminar esta actividad? No se podrá recuperar.')" class="inline">@csrf @method('DELETE')<button class="text-slate-300 hover:text-red-500 p-1.5" title="Eliminar"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></form>
                                            @endif
                                        @else
                                            <button onclick='openNotes(@json($act), {{ ($esSupervisor || $esDireccion) ? "true" : "false" }})' class="text-slate-400 hover:text-indigo-600 p-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg></button>
                                            @php $puedeEliminar = ($act->user_id == Auth::id() && (is_null($act->asignado_por) || $act->asignado_por == Auth::id())) || $act->asignado_por == Auth::id(); @endphp
                                            @if($puedeEliminar)
                                                <form action="{{ route('activities.destroy', $act->id) }}" method="POST" onsubmit="return confirm('¿Eliminar esta actividad? No se podrá recuperar.')" class="inline">@csrf @method('DELETE')<button class="text-slate-300 hover:text-red-500 p-1.5" title="Eliminar"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></form>
                                            @endif
                                        @endif

                                        <button onclick="openHistory({{ $act->id }})" class="text-slate-300 hover:text-indigo-500 p-1.5"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></button>
                                        <script id="history-json-{{ $act->id }}" type="application/json">@json($act->historial)</script>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="14" class="py-12 text-center text-slate-400">Sin actividades en este rango.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- BARRA FLOTANTE DE SELECCIÓN MASIVA (creada por JS, ver script) --}}

    {{-- ======================================================= --}}
    {{-- 5B. TAREAS BORRADAS (COORDINADOR / DIRECCIÓN)           --}}
    {{-- ======================================================= --}}
    @if(isset($deletedActivities) && $deletedActivities->count() > 0)
    <div class="max-w-[98%] mx-auto mt-4 pb-8">
        <div class="bg-white rounded-2xl shadow-sm border border-red-100 overflow-hidden">
        <div class="flex items-center gap-3 px-6 py-4 border-b border-red-100 bg-red-50">
            <svg class="w-5 h-5 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            <div>
                <h3 class="text-sm font-bold text-red-700 uppercase tracking-wide">
                    @if($esDireccion)
                        Tareas eliminadas por coordinadores
                    @else
                        Tareas eliminadas de {{ $targetUser->name }}
                    @endif
                </h3>
                <p class="text-[10px] text-red-400">Registro de auditoría — solo visible para {{ $esDireccion ? 'dirección' : 'coordinadores' }}</p>
            </div>
            <span class="ml-auto bg-red-100 text-red-600 text-xs font-bold px-2 py-0.5 rounded-full">{{ $deletedActivities->count() }}</span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-red-50/50 border-b border-red-100">
                        <th class="px-4 py-2 text-left text-[10px] font-bold text-red-500 uppercase tracking-wider">Tarea eliminada</th>
                        <th class="px-4 py-2 text-center text-[10px] font-bold text-red-500 uppercase tracking-wider">Asignada a</th>
                        <th class="px-4 py-2 text-center text-[10px] font-bold text-red-500 uppercase tracking-wider">Eliminada por</th>
                        <th class="px-4 py-2 text-center text-[10px] font-bold text-red-500 uppercase tracking-wider">Fecha de eliminación</th>
                        <th class="px-4 py-2 text-center text-[10px] font-bold text-red-500 uppercase tracking-wider">Estatus previo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-red-50">
                    @foreach($deletedActivities as $del)
                        <tr class="hover:bg-red-50/40 transition-colors">
                            <td class="px-4 py-3">
                                <span class="font-semibold text-slate-600 line-through decoration-red-300">{{ $del->nombre_actividad }}</span>
                                @if($del->cliente)
                                    <span class="ml-1 text-[9px] text-slate-400">({{ $del->cliente }})</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-slate-500">{{ $del->user->name ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="font-semibold text-red-600">{{ $del->deletedByUser->name ?? '—' }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="text-slate-500">{{ \Carbon\Carbon::parse($del->deleted_at)->format('d M Y H:i') }}</span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $delBadge = ['Por Aprobar'=>'bg-orange-100 text-orange-700','Por Validar'=>'bg-purple-100 text-purple-700','Planeado'=>'bg-indigo-100 text-indigo-700','En proceso'=>'bg-blue-100 text-blue-700','Completado'=>'bg-emerald-100 text-emerald-700','Completado con retardo'=>'bg-amber-100 text-amber-700','Retardo'=>'bg-red-100 text-red-700','Rechazado'=>'bg-red-200 text-red-800'];
                                @endphp
                                <span class="px-2 py-0.5 rounded text-[9px] font-bold uppercase {{ $delBadge[$del->estatus] ?? 'bg-gray-100 text-gray-600' }}">{{ $del->estatus }}</span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
    </div>
    @endif
</div>

{{-- ======================================================= --}}
{{-- 6. MODALES (COMPLETOS)                                  --}}
{{-- ======================================================= --}}

{{-- Modal Crear --}}
<div id="quickCreateModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" onclick="this.parentElement.classList.add('hidden')"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:w-full sm:max-w-lg border border-slate-200">
                <form action="{{ route('activities.store') }}" method="POST" id="activityCreateForm" onsubmit="return submitActivityForm(this)">
                    @csrf
                    <input type="hidden" name="form_token" id="activityFormToken" value="{{ \Illuminate\Support\Str::uuid() }}">
                    <div class="bg-white px-8 py-8">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-bold text-slate-800">Nueva Actividad</h3>
                            <button type="button" onclick="document.getElementById('quickCreateModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600"><svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                        </div>
                        <div class="mb-6 bg-indigo-50 p-4 rounded-xl border border-indigo-100">
                            <label class="block text-xs font-bold text-indigo-800 uppercase mb-2 tracking-wide">Asignar tarea a:</label>
                            <select name="assigned_to" id="create-assigned-to" class="w-full rounded-lg border-indigo-200 text-sm focus:ring-indigo-500 bg-white shadow-sm text-slate-700 py-2.5">
                                <option value="{{ Auth::id() }}" data-supervisor="">Mí mismo ({{ Auth::user()->name }})</option>
                                @foreach($usersList as $u)
                                    @if($u->id !== Auth::id())
                                        @php $supName = $u->empleado?->supervisor?->user?->name ?? ($u->empleado?->supervisor?->nombre ?? ''); @endphp
                                        <option value="{{ $u->id }}" {{ $targetUser->id == $u->id ? 'selected' : '' }} data-supervisor="{{ $supName }}">{{ $u->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <div id="create-supervisor-info" class="mt-2 text-[10px] {{ $targetUser->id != Auth::id() && $usersList->where('id', $targetUser->id)->first()?->empleado?->supervisor ? 'text-slate-500' : 'text-slate-400' }}">
                                @php
                                    $targetUserObj = $usersList->where('id', $targetUser->id)->first();
                                    $targetSupName = $targetUserObj?->empleado?->supervisor?->user?->name ?? ($targetUserObj?->empleado?->supervisor?->nombre ?? '');
                                @endphp
                                <span id="create-supervisor-label">@if($targetUser->id != Auth::id() && $targetSupName) Supervisor: <strong>{{ $targetSupName }}</strong> @else Sin supervisor asignado @endif</span>
                            </div>
                        </div>
                        <div class="space-y-5">
                            <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Descripción</label><input type="text" name="nombre_actividad" class="w-full rounded-lg border-slate-300 text-sm focus:ring-indigo-500 py-2.5" placeholder="¿Qué se debe hacer?" required></div>
                            <div class="grid grid-cols-2 gap-5">
                                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Fecha Compromiso</label><input type="date" name="fecha_compromiso" value="{{ now()->format('Y-m-d') }}" class="w-full rounded-lg border-slate-300 text-sm py-2.5"></div>
                                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Prioridad</label><select name="prioridad" class="w-full rounded-lg border-slate-300 text-sm py-2.5"><option value="Media">Media</option><option value="Alta">Alta 🔥</option><option value="Baja">Baja</option></select></div>
                            </div>
                            <div class="grid grid-cols-2 gap-5">
                                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Inicio (Opcional)</label><input type="time" name="hora_inicio_programada" class="w-full rounded-lg border-slate-300 text-sm py-2.5"></div>
                                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Fin (Opcional)</label><input type="time" name="hora_fin_programada" class="w-full rounded-lg border-slate-300 text-sm py-2.5"></div>
                            </div>
                            <div class="grid grid-cols-2 gap-5">
                                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Área</label><select name="area" class="w-full rounded-lg border-slate-300 text-sm py-2.5 bg-white focus:ring-indigo-500">@foreach($areasDisponibles as $areaOp) <option value="{{ $areaOp }}" {{ $areaOp == 'General' ? 'selected' : '' }}>{{ $areaOp }}</option> @endforeach</select></div>
                                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Cliente</label><input type="text" name="cliente" class="w-full rounded-lg border-slate-300 text-sm py-2.5" placeholder="Opcional"></div>
                            </div>
                            @if(isset($proyectos) && $proyectos->count() > 0)
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Proyecto (Opcional)</label>
                                <select name="proyecto_id" class="w-full rounded-lg border-slate-300 text-sm py-2.5 bg-white focus:ring-indigo-500">
                                    <option value="">Sin proyecto</option>
                                    @foreach($proyectos as $proy)
                                        <option value="{{ $proy->id }}" {{ request('proyecto_id') == $proy->id ? 'selected' : '' }}>{{ $proy->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                        </div>
                    </div>
                    <div class="bg-slate-50 px-8 py-5 flex flex-row-reverse gap-3 border-t border-slate-200">
                        <button type="submit" id="activitySubmitBtn" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-bold shadow-md hover:bg-indigo-700 transition flex items-center gap-2">
                            <span id="activitySubmitText">Guardar</span>
                            <svg id="activitySubmitSpinner" class="hidden animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Modal Editar --}}
<div id="notesModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" onclick="closeNotes()"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:w-full sm:max-w-lg border border-slate-200">
                <form id="notesForm" method="POST" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="bg-white px-8 py-8">
                        <div class="flex justify-between items-start mb-6">
                            <h3 class="text-xl font-bold text-slate-800">Detalles</h3>
                            <button type="button" onclick="closeNotes()" class="text-slate-400 hover:text-slate-600"><svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                        </div>
                        <div class="mb-6 p-4 bg-slate-50 rounded-xl border border-slate-100">
                            <div class="flex justify-between items-center mb-3">
                                <div><span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wide">Responsable</span><span id="modal-responsable" class="text-sm font-bold text-indigo-600">-</span></div>
                                <div class="text-right"><span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wide">Supervisor</span><span id="modal-supervisor" class="text-sm font-bold text-slate-700">-</span></div>
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 uppercase mb-1">Reasignar a</label>
                                <select name="user_id" id="modal-user-id" class="w-full text-sm rounded-lg border-slate-300 py-2 bg-white focus:ring-indigo-500">
                                    @foreach($empleadosAsignables as $u)
                                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div id="modal-rejection-alert" class="hidden mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r text-sm shadow-sm"><p class="font-bold text-red-800">⚠️ Rechazado</p><p class="text-red-700 mt-1 text-xs pl-6">Motivo: <span id="modal-rejection-reason" class="font-bold italic">...</span></p></div>
                        <div class="space-y-5">
                            <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Descripción</label><textarea name="nombre_actividad" id="modal-activity-name" rows="2" class="w-full text-sm rounded-lg border-slate-300 bg-slate-50 py-2.5"></textarea></div>
                            <div class="grid grid-cols-2 gap-5">
                                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Fecha</label><input type="date" name="fecha_compromiso" id="modal-fecha" class="w-full text-sm rounded-lg border-slate-300 py-2.5"></div>
                                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Prioridad</label><select name="prioridad" id="modal-prioridad" class="w-full text-sm rounded-lg border-slate-300 py-2.5"><option value="Alta">Alta 🔥</option><option value="Media">Media</option><option value="Baja">Baja</option></select></div>
                            </div>
                            <div class="grid grid-cols-2 gap-5">
                                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Inicio</label><input type="time" name="hora_inicio_programada" id="modal-hora-inicio" class="w-full text-sm rounded-lg border-slate-300 py-2.5"></div>
                                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Fin</label><input type="time" name="hora_fin_programada" id="modal-hora-fin" class="w-full text-sm rounded-lg border-slate-300 py-2.5"></div>
                            </div>
                            <div class="grid grid-cols-2 gap-5">
                                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Área</label><select name="area" id="modal-area" class="w-full text-sm rounded-lg border-slate-300 py-2.5 bg-white">@foreach($areasDisponibles as $areaOp)<option value="{{ $areaOp }}">{{ $areaOp }}</option>@endforeach</select></div>
                                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Cliente</label><input type="text" name="cliente" id="modal-cliente" class="w-full text-sm rounded-lg border-slate-300 py-2.5"></div>
                            </div>
                            @if(isset($proyectos) && $proyectos->count() > 0)
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Proyecto (Opcional)</label>
                                <select name="proyecto_id" id="modal-proyecto" class="w-full text-sm rounded-lg border-slate-300 py-2.5 bg-white focus:ring-indigo-500">
                                    <option value="">Sin proyecto</option>
                                    @foreach($proyectos as $proy)
                                        <option value="{{ $proy->id }}">{{ $proy->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            @endif
                            <div id="div-estatus-selector">
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Estatus</label>
                                    <select name="estatus" id="modal-estatus" class="w-full text-sm rounded-lg border-slate-300 py-2.5 font-bold text-slate-700"></select>
                            </div>
                            <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Comentarios</label><textarea name="comentarios" id="modal-comentarios" rows="3" class="w-full text-sm rounded-lg border-slate-300 placeholder-slate-400 py-2.5"></textarea></div>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-8 py-5 flex flex-row-reverse gap-3 border-t border-slate-200">
                        <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-bold shadow hover:bg-indigo-700 transition">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Modal Rechazo --}}
<div id="rejectModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" onclick="document.getElementById('rejectModal').classList.add('hidden')"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:w-full sm:max-w-md border border-red-200">
                <form id="rejectForm" method="POST">
                    @csrf @method('PUT')
                    <div class="bg-white px-6 py-6">
                        <h3 class="text-lg font-bold text-red-700 mb-2">Rechazar Actividad</h3>
                        <textarea name="motivo" class="w-full rounded-lg border-red-300 focus:ring-red-500 focus:border-red-500" rows="3" required placeholder="Motivo..."></textarea>
                    </div>
                    <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse gap-2">
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-red-700">Confirmar</button>
                        <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')" class="bg-white text-slate-700 border border-slate-300 px-4 py-2 rounded-lg text-sm font-bold">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- MODAL REPORTE CLIENTE --}}
<div id="reportModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="document.getElementById('reportModal').classList.add('hidden')"></div>
    <div class="fixed inset-0 z-10 flex items-center justify-center p-4">
        <div class="relative w-full max-w-sm bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden transform transition-all">
            <form action="{{ route('activities.client_report') }}" method="GET" target="_blank"> {{-- target_blank abre en nueva pestaña --}}
                <div class="bg-slate-50 px-6 py-4 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-bold text-slate-700">Generar Reporte Cliente</h3>
                    <button type="button" onclick="document.getElementById('reportModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                
                <div class="p-6 space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Nombre del Cliente</label>
                        <input type="text" name="cliente_reporte" class="w-full rounded-lg border-slate-300 text-sm focus:ring-indigo-500" placeholder="Ej. Coca Cola" required>
                        <p class="text-[10px] text-slate-400 mt-1">Debe coincidir con el nombre usado en las actividades.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Mes del Reporte</label>
                        <input type="month" name="mes_reporte" value="{{ now()->format('Y-m') }}" class="w-full rounded-lg border-slate-300 text-sm focus:ring-indigo-500" required>
                    </div>
                </div>

                <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex justify-end">
                    <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg text-sm font-bold shadow hover:bg-indigo-700 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Generar PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- MODAL EXPORTAR EXCEL --}}
<div id="excelModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="document.getElementById('excelModal').classList.add('hidden')"></div>
    <div class="fixed inset-0 z-10 flex items-center justify-center p-4">
        <div class="relative w-full max-w-md bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden transform transition-all">
            <form action="{{ route('activities.export_excel') }}" method="GET">
                <div class="bg-emerald-600 px-6 py-4 flex justify-between items-center">
                    <h3 class="font-bold text-white">Exportar a Excel</h3>
                    <button type="button" onclick="document.getElementById('excelModal').classList.add('hidden')" class="text-emerald-200 hover:text-white"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                
                <div class="p-6 space-y-4">
                    @php
                        $miEmpleado = Auth::user()->empleado;
                        $posicionLower = mb_strtolower($miEmpleado->posicion ?? '', 'UTF-8');
                        $esDireccion = Str::contains($posicionLower, 'direcc');
                        $subordinados = $miEmpleado ? \App\Models\Empleado::where('supervisor_id', $miEmpleado->id)->whereNotNull('user_id')->get() : collect();
                        $esSupervisor = $subordinados->count() > 0;
                    @endphp

                    @if($esDireccion || $esSupervisor)
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-2">Seleccionar Usuarios</label>
                            <div class="space-y-2 max-h-40 overflow-y-auto border border-slate-200 rounded-lg p-3 bg-slate-50">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" id="excel_all_users" onchange="toggleAllUsersExcel(this)" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500">
                                    <span class="text-sm font-medium text-slate-700">Todos (incluye分析师)</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="user_ids[]" value="{{ Auth::id() }}" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 user-checkbox-excel" checked>
                                    <span class="text-sm font-medium text-slate-700">{{ Auth::user()->name }} (Yo)</span>
                                </label>
                                @foreach($subordinados as $sub)
                                    @if($sub->user_id && $sub->user_id != Auth::id())
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="checkbox" name="user_ids[]" value="{{ $sub->user_id }}" class="rounded border-slate-300 text-emerald-600 focus:ring-emerald-500 user-checkbox-excel">
                                            <span class="text-sm font-medium text-slate-700">{{ $sub->user->name ?? 'Usuario' }}</span>
                                        </label>
                                    @endif
                                @endforeach
                            </div>
                            <p class="text-[10px] text-slate-400 mt-1">Selecciona uno o varios usuarios. Se creará una hoja por cada uno.</p>
                        </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Fecha Inicio</label>
                            <input type="date" name="date_start" value="{{ $startDate->format('Y-m-d') }}" class="w-full rounded-lg border-slate-300 text-sm focus:ring-emerald-500" required>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Fecha Fin</label>
                            <input type="date" name="date_end" value="{{ $endDate->format('Y-m-d') }}" class="w-full rounded-lg border-slate-300 text-sm focus:ring-emerald-500" required>
                        </div>
                    </div>
                </div>

                <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex justify-end">
                    <button type="submit" class="bg-emerald-600 text-white px-6 py-2 rounded-lg text-sm font-bold shadow hover:bg-emerald-700 flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        Descargar Excel
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function submitActivityForm(form) {
    const btn = document.getElementById('activitySubmitBtn');
    const text = document.getElementById('activitySubmitText');
    const spinner = document.getElementById('activitySubmitSpinner');
    if (btn.disabled) return false; // ya se envió
    btn.disabled = true;
    btn.classList.add('opacity-75', 'cursor-not-allowed');
    text.textContent = 'Guardando...';
    spinner.classList.remove('hidden');
    return true;
}

function toggleAllUsersExcel(checkbox) {
    const checkboxes = document.querySelectorAll('.user-checkbox-excel');
    checkboxes.forEach(cb => cb.checked = checkbox.checked);
    if (checkbox.checked) {
        document.querySelector('input[name="user_ids[]"][value="{{ Auth::id() }}"]').checked = true;
    }
}
</script>

{{-- Modal Planificador --}}
@if($esPuestoPlanificador)
<div id="planModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="document.getElementById('planModal').classList.add('hidden')"></div>
    <div class="fixed inset-0 z-10 flex items-center justify-center p-4 sm:p-6">
        <div class="relative w-full max-w-[95vw] h-[90vh] bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden border border-slate-200">
            <form action="{{ route('activities.storeBatch') }}" method="POST" class="flex flex-col h-full" onsubmit="return submitPlan(event)">
                @csrf
                <div class="px-8 py-5 border-b border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-white z-20">
                    <h3 class="text-2xl font-bold text-slate-800">Planificador Semanal</h3>
                    <div class="flex items-center gap-3 bg-slate-50 p-1.5 rounded-xl border border-slate-200 shadow-sm">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider pl-3">Semana:</span>
                        <input type="date" name="semana_inicio" id="weekPicker" class="border-none bg-white text-slate-700 text-sm font-bold rounded-lg" value="{{ now()->startOfWeek()->format('Y-m-d') }}" onchange="updateWeekLabels()">
                    </div>
                </div>
                <div class="flex-1 overflow-hidden relative bg-slate-100">
                    <div class="h-full overflow-x-auto custom-scrollbar">
                        <div class="flex h-full min-w-max">
                            @foreach(['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'] as $index => $dia)
                            <div class="w-[320px] flex flex-col h-full border-r border-slate-200 bg-slate-50/50 group hover:bg-slate-100/50">
                                <div class="p-4 text-center border-b border-slate-200 bg-white sticky top-0 z-10 shadow-sm">
                                    <h4 class="text-sm font-black text-slate-700 uppercase tracking-wide">{{ $dia }}</h4>
                                    <span class="text-xs font-bold text-indigo-500 bg-indigo-50 px-2 py-0.5 rounded-full mt-1 inline-block" id="label-date-{{ $index }}">--/--</span>
                                </div>
                                <div class="flex-1 p-3 space-y-3 overflow-y-auto custom-scrollbar" id="container-day-{{ $index }}"></div>
                                <div class="p-3 bg-white border-t border-slate-200 sticky bottom-0 z-10">
                                    <button type="button" onclick="addTaskCard({{ $index }})" class="w-full py-2.5 border-2 border-dashed border-slate-300 rounded-xl text-slate-400 text-xs font-bold hover:border-indigo-400 hover:text-indigo-600 flex justify-center items-center gap-2">Agregar Tarea</button>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>
                <div class="px-8 py-5 border-t border-slate-200 bg-white flex justify-between items-center z-20">
                    <button type="button" onclick="document.getElementById('planModal').classList.add('hidden')" class="text-slate-500 font-bold text-sm px-4">Cancelar</button>
                    <button type="submit" class="bg-indigo-600 text-white px-8 py-3 rounded-xl text-sm font-bold shadow-lg hover:bg-indigo-700">Enviar Planificación</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- MODAL IMPORTAR TAREAS --}}
<div id="importModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="closeImportModal()"></div>
    <div class="fixed inset-0 z-10 flex items-center justify-center p-4">

        {{-- PASO 1: Subir archivo --}}
        <div id="importStep1" class="relative w-full max-w-md bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
            <div class="bg-blue-600 px-6 py-4 flex justify-between items-center">
                <h3 class="font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20H7a2 2 0 01-2-2V6a2 2 0 012-2h6l4 4v10a2 2 0 01-2 2z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 14l2 2 4-4"/></svg>
                    Delegar Tareas desde Excel
                </h3>
                <button type="button" onclick="closeImportModal()" class="text-blue-200 hover:text-white">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 space-y-4">
                <div class="bg-blue-50 border border-blue-200 rounded-xl p-3 text-xs text-blue-800 flex gap-2">
                    <svg class="w-4 h-4 mt-0.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <span>Las tareas delegadas a tus analistas se crean en estado <strong>Planeado</strong> directamente, sin pasar por aprobación de Dirección.</span>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Archivo Excel o CSV</label>
                    <input type="file" id="importFile" accept=".xlsx,.xls,.csv"
                        class="w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:font-bold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 border border-slate-300 rounded-lg py-1.5 px-3">
                    <p class="text-[10px] text-slate-400 mt-1.5">La primera fila debe contener los encabezados. <a href="{{ route('activities.import_template') }}" class="text-blue-600 font-bold underline hover:text-blue-800">Descargar plantilla</a></p>
                </div>

                @if(isset($proyectos) && $proyectos->count() > 0)
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Proyecto (Opcional)</label>
                    <select id="importProjectId" class="w-full rounded-lg border-slate-300 text-sm py-2.5 bg-white focus:ring-blue-500">
                        <option value="">Sin proyecto</option>
                        @foreach($proyectos as $proy)
                            <option value="{{ $proy->id }}">{{ $proy->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div id="importStep1Error"></div>
            </div>
            <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex justify-end gap-2">
                <button type="button" onclick="closeImportModal()" class="bg-white text-slate-600 border border-slate-300 px-4 py-2 rounded-lg text-sm font-bold hover:bg-slate-50">Cancelar</button>
                <button type="button" id="importPreviewBtn" onclick="loadImportPreview()" class="bg-blue-600 text-white px-6 py-2 rounded-lg text-sm font-bold shadow hover:bg-blue-700 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                    Ver Tareas
                </button>
            </div>
        </div>

        {{-- PASO 2: Asignar por tarea --}}
        <div id="importStep2" class="hidden relative w-full max-w-3xl bg-white rounded-2xl shadow-xl border border-slate-200 overflow-hidden">
            <div class="bg-blue-600 px-6 py-4 flex justify-between items-center">
                <h3 class="font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                    Asignar Tareas — <span id="importTaskCount" class="ml-1 bg-blue-500 text-white text-xs px-2 py-0.5 rounded-full">0</span>
                </h3>
                <button type="button" onclick="closeImportModal()" class="text-blue-200 hover:text-white">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-5 space-y-4">
                @if($puedeAsignarAOtros)
                <div class="bg-indigo-50 border border-indigo-200 rounded-xl p-3 flex flex-wrap items-center gap-3">
                    <label class="text-xs font-bold text-indigo-800 whitespace-nowrap">Asignar todas a:</label>
                    <select id="importDefaultAnalista" onchange="setAllAnalistas(this.value)" class="flex-1 min-w-[160px] rounded-lg border-indigo-300 text-sm py-2 bg-white focus:ring-indigo-500 text-slate-700">
                        <option value="">— Seleccionar para todas —</option>
                        @foreach($teamUsers as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}{{ $u->id === Auth::id() ? ' (Yo)' : '' }}</option>
                        @endforeach
                    </select>
                    <span class="text-[10px] text-indigo-600">Aplicar a toda la lista</span>
                </div>
                @else
                <div class="bg-slate-50 border border-slate-200 rounded-xl p-3 text-xs text-slate-500">
                    Las tareas se asignarán a <strong class="text-slate-700">{{ Auth::user()->name }}</strong> (tú mismo).
                </div>
                @endif

                <div class="overflow-y-auto max-h-72 rounded-xl border border-slate-200">
                    <table class="w-full text-xs">
                        <thead class="sticky top-0 bg-slate-800 text-white">
                            <tr>
                                <th class="px-3 py-2 text-left font-bold w-8">#</th>
                                <th class="px-3 py-2 text-left font-bold">Tarea</th>
                                <th class="px-3 py-2 text-center font-bold w-24">Fecha</th>
                                <th class="px-3 py-2 text-center font-bold w-20">Prioridad</th>
                                @if($puedeAsignarAOtros)
                                <th class="px-3 py-2 text-left font-bold w-44">Asignar a</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody id="importTasksBody" class="divide-y divide-slate-100"></tbody>
                    </table>
                </div>

                <div id="importStep2Error"></div>
            </div>
            <div class="bg-slate-50 px-6 py-4 border-t border-slate-100 flex justify-between gap-2">
                <button type="button" onclick="backToImportStep1()" class="bg-white text-slate-600 border border-slate-300 px-4 py-2 rounded-lg text-sm font-bold hover:bg-slate-50 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    Volver
                </button>
                <button type="button" id="importSendBtn" onclick="sendImportTasks()" class="bg-emerald-600 text-white px-6 py-2 rounded-lg text-sm font-bold shadow hover:bg-emerald-700 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    Enviar Tareas
                </button>
            </div>
        </div>

    </div>
</div>

{{-- Modal Historial --}}
<div id="historyModal" class="fixed inset-0 z-50 hidden" aria-hidden="true" role="dialog">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" onclick="document.getElementById('historyModal').classList.add('hidden')"></div>
    <div class="flex items-center justify-center min-h-screen px-4 pointer-events-none">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md relative z-10 overflow-hidden pointer-events-auto border border-slate-100 transform transition-all">
            <div class="bg-slate-50 px-5 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="font-bold text-slate-700">Historial</h3>
                <button onclick="document.getElementById('historyModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600"><svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
            </div>
            <div class="p-6 max-h-[60vh] overflow-y-auto custom-scrollbar" id="history-container"></div>
        </div>
    </div>
</div>

{{-- ======================================================= --}}
{{-- 7. SCRIPTS Y ESTILOS                                    --}}
{{-- ======================================================= --}}
<script>
    function submitPlan(e) {
        const esHorarioPermitido = @json($esHorarioPermitido);
        if (!esHorarioPermitido) {
            e.preventDefault(); alert("⚠️ Periodo de planificación cerrado."); return false;
        }
    }
    function openNotes(act, canEditAll) {
        const f = document.getElementById('notesForm'); f.action = "/activities/" + act.id;
        document.getElementById('modal-activity-name').value = act.nombre_actividad;
        document.getElementById('modal-prioridad').value = act.prioridad || 'Media';
        document.getElementById('modal-fecha').value = act.fecha_compromiso ? act.fecha_compromiso.split('T')[0] : '';
        document.getElementById('modal-hora-inicio').value = act.hora_inicio_programada ? act.hora_inicio_programada.substring(0,5) : '';
        document.getElementById('modal-hora-fin').value = act.hora_fin_programada ? act.hora_fin_programada.substring(0,5) : '';
        document.getElementById('modal-comentarios').value = act.comentarios || '';
        document.getElementById('modal-area').value = act.area || 'General';
        document.getElementById('modal-cliente').value = act.cliente || '';
        if (document.getElementById('modal-proyecto')) {
            document.getElementById('modal-proyecto').value = act.proyecto_id || '';
        }
        if (document.getElementById('modal-user-id')) {
            document.getElementById('modal-user-id').value = act.user_id || '';
        }
        document.getElementById('modal-responsable').innerText = act.user ? act.user.name : '-';
        document.getElementById('modal-supervisor').innerText = (act.user && act.user.empleado && act.user.empleado.supervisor) ? act.user.empleado.supervisor.nombre : 'N/A';
        
        // Opciones dinámicas de estatus según contexto
        const estatusSelect = document.getElementById('modal-estatus');
        estatusSelect.innerHTML = '';
        
        const opcionesPermitidas = {
            'En proceso': ['En proceso', 'Completado'],
            'Planeado': canEditAll ? ['Planeado', 'En proceso', 'Completado'] : ['En proceso'],
            'Completado': ['Completado'],
            'Completado con retardo': ['Completado con retardo'],
            'Por Aprobar': ['Por Aprobar'],
            'Por Validar': ['Por Validar'],
            'Rechazado': canEditAll ? ['Rechazado', 'En proceso', 'Planeado'] : ['En proceso'],
            'Retardo': canEditAll ? ['Retardo', 'En proceso', 'Completado'] : ['En proceso', 'Completado']
        };
        
        const opciones = opcionesPermitidas[act.estatus] || ['En proceso', 'Completado'];
        opciones.forEach(opt => {
            const option = document.createElement('option');
            option.value = opt;
            option.textContent = opt;
            if (opt === act.estatus) option.selected = true;
            estatusSelect.appendChild(option);
        });
        
        const inputs = ['modal-activity-name','modal-fecha','modal-prioridad','modal-hora-inicio','modal-hora-fin','modal-area','modal-cliente'];
        inputs.forEach(id => {
            const el = document.getElementById(id);
            if(!canEditAll){ el.readOnly=true; el.classList.add('bg-slate-100'); } else { el.readOnly=false; el.classList.remove('bg-slate-100'); }
        });
        const userSelect = document.getElementById('modal-user-id');
        if (userSelect) {
            if(!canEditAll){ userSelect.disabled=true; userSelect.classList.add('bg-slate-100'); } else { userSelect.disabled=false; userSelect.classList.remove('bg-slate-100'); }
        }

        const divRej = document.getElementById('modal-rejection-alert');
        if(act.estatus === 'Rechazado'){ divRej.classList.remove('hidden'); document.getElementById('modal-rejection-reason').innerText=act.motivo_rechazo; } else { divRej.classList.add('hidden'); }
        document.getElementById('notesModal').classList.remove('hidden');
    }
    function closeNotes(){ document.getElementById('notesModal').classList.add('hidden'); }
    document.addEventListener('DOMContentLoaded', function () {
        var sel = document.getElementById('create-assigned-to');
        var info = document.getElementById('create-supervisor-info');
        var label = document.getElementById('create-supervisor-label');
        if (sel && info && label) {
            sel.addEventListener('change', function () {
                var opt = sel.options[sel.selectedIndex];
                var sup = opt ? opt.getAttribute('data-supervisor') : '';
                label.innerHTML = sup ? 'Supervisor: <strong>' + sup + '</strong>' : 'Sin supervisor asignado';
            });
        }
    });
    function rejectActivity(id){ document.getElementById('rejectForm').action="/activities/"+id+"/reject"; document.getElementById('rejectModal').classList.remove('hidden'); }

    // ── Selección masiva ─────────────────────────────────────────────────────
    // Crear la barra flotante directamente en <body> para evitar problemas de
    // contenedores con overflow/transform que rompen position:fixed
    (function () {
        var bar = document.createElement('div');
        bar.id = 'bulkBar';
        bar.style.cssText = 'display:none;position:fixed;bottom:24px;left:50%;transform:translateX(-50%);z-index:9999;';
        bar.innerHTML = [
            '<div style="background:#0f172a;color:#fff;padding:10px 20px;border-radius:16px;',
            'box-shadow:0 20px 40px rgba(0,0,0,.4);border:1px solid #334155;',
            'display:flex;align-items:center;gap:16px;white-space:nowrap;">',
            '<span style="font-size:13px;font-weight:700;display:flex;align-items:center;gap:8px;">',
            '<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor">',
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" ',
            'd="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>',
            '<span id="bulkCount">0</span> seleccionadas</span>',
            '<button onclick="bulkDeleteSelected()" style="background:#ef4444;color:#fff;padding:7px 16px;',
            'border-radius:8px;font-size:12px;font-weight:700;border:none;cursor:pointer;',
            'display:flex;align-items:center;gap:6px;transition:background .15s;">',
            '<svg width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor">',
            '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" ',
            'd="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>',
            'Eliminar seleccionadas</button>',
            '<button onclick="clearBulkSelection()" title="Cancelar selección" ',
            'style="background:none;border:none;color:#94a3b8;cursor:pointer;font-size:20px;line-height:1;padding:0 2px;">&#x2715;</button>',
            '</div>'
        ].join('');
        document.body.appendChild(bar);
    })();

    function toggleSelectAll(masterCb) {
        document.querySelectorAll('.bulk-cb').forEach(function(cb) { cb.checked = masterCb.checked; });
        updateBulkBar();
    }

    function updateBulkBar() {
        var checked = document.querySelectorAll('.bulk-cb:checked');
        var total   = document.querySelectorAll('.bulk-cb').length;
        var bar     = document.getElementById('bulkBar');
        var master  = document.getElementById('selectAllCb');
        var countEl = document.getElementById('bulkCount');
        if (countEl) countEl.textContent = checked.length;
        if (bar) bar.style.display = checked.length > 0 ? 'block' : 'none';
        if (master) {
            master.checked       = total > 0 && checked.length === total;
            master.indeterminate = checked.length > 0 && checked.length < total;
        }
    }

    function clearBulkSelection() {
        document.querySelectorAll('.bulk-cb').forEach(function(cb) { cb.checked = false; });
        var master = document.getElementById('selectAllCb');
        if (master) { master.checked = false; master.indeterminate = false; }
        updateBulkBar();
    }

    function bulkDeleteSelected() {
        var ids = Array.from(document.querySelectorAll('.bulk-cb:checked')).map(function(cb) { return cb.getAttribute('data-id'); });
        if (!ids.length) return;
        if (!confirm('¿Eliminar ' + ids.length + ' ' + (ids.length === 1 ? 'tarea' : 'tareas') + '? Esta acción no se puede deshacer.')) return;

        fetch('{{ url("activities/bulk-destroy") }}', {
            method: 'DELETE',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify({ ids: ids })
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                ids.forEach(function(id) {
                    var cb  = document.querySelector('.bulk-cb[data-id="' + id + '"]');
                    var row = cb ? cb.closest('tr') : document.querySelector('tr[data-activity-id="' + id + '"]');
                    if (row) row.remove();
                });
                clearBulkSelection();
                var toast = document.createElement('div');
                toast.style.cssText = 'position:fixed;top:20px;right:20px;z-index:9999;background:#059669;color:#fff;padding:12px 20px;border-radius:12px;box-shadow:0 10px 25px rgba(0,0,0,.2);font-size:14px;font-weight:700;display:flex;align-items:center;gap:8px;';
                toast.innerHTML = '<svg width="16" height="16" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>' + data.message;
                document.body.appendChild(toast);
                setTimeout(function() { toast.remove(); }, 3500);
            } else {
                alert(data.message || 'Error al eliminar.');
            }
        })
        .catch(function() { alert('Error de conexión. Intenta de nuevo.'); });
    }
    // ── Delegación de Tareas (2 pasos) ──────────────────────────────────────
    let _importTasksData = [];

    function closeImportModal() {
        document.getElementById('importModal').classList.add('hidden');
        document.getElementById('importStep1').classList.remove('hidden');
        document.getElementById('importStep2').classList.add('hidden');
        const fi = document.getElementById('importFile'); if (fi) fi.value = '';
        document.getElementById('importStep1Error').innerHTML = '';
        document.getElementById('importStep2Error').innerHTML = '';
        _importTasksData = [];
    }

    function backToImportStep1() {
        document.getElementById('importStep2').classList.add('hidden');
        document.getElementById('importStep1').classList.remove('hidden');
        document.getElementById('importStep1Error').innerHTML = '';
    }

    function loadImportPreview() {
        const fileInput = document.getElementById('importFile');
        const errDiv = document.getElementById('importStep1Error');
        errDiv.innerHTML = '';
        if (!fileInput || !fileInput.files.length) {
            errDiv.innerHTML = '<div class="bg-red-50 text-red-800 p-3 rounded-lg border border-red-200 text-xs font-bold mt-2">Selecciona un archivo Excel o CSV.</div>';
            return;
        }
        const btn = document.getElementById('importPreviewBtn');
        btn.disabled = true;
        btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Leyendo...';
        const formData = new FormData();
        formData.append('file', fileInput.files[0]);
        formData.append('_token', '{{ csrf_token() }}');
        fetch('{{ route("activities.import_preview") }}', {
            method: 'POST',
            headers: { 'Accept': 'application/json' },
            body: formData
        })
        .then(async r => {
            const text = await r.text();
            try { return JSON.parse(text); }
            catch (e) { throw new Error('Respuesta no JSON. Status ' + r.status + ': ' + text.substring(0, 200)); }
        })
        .then(data => {
            if (data.success) {
                _importTasksData = data.tasks;
                _renderImportStep2(data.tasks);
            } else {
                errDiv.innerHTML = '<div class="bg-red-50 text-red-800 p-3 rounded-lg border border-red-200 text-xs font-bold mt-2">' + (data.message || 'Error al leer el archivo.') + '</div>';
            }
        })
        .catch(e => {
            if (e.message && e.message.includes('<!DOCTYPE')) {
                errDiv.innerHTML = '<div class="bg-red-50 text-red-800 p-3 rounded-lg border border-red-200 text-xs font-bold mt-2">Error del servidor. Revisa la consola o contacta al administrador.</div>';
            } else {
                errDiv.innerHTML = '<div class="bg-red-50 text-red-800 p-3 rounded-lg border border-red-200 text-xs font-bold mt-2">' + (e.message || 'Error de conexión. Intenta de nuevo.') + '</div>';
            }
        })
        .finally(() => {
            btn.disabled = false;
            btn.innerHTML = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg> Ver Tareas';
        });
    }

    function _renderImportStep2(tasks) {
        document.getElementById('importTaskCount').textContent = tasks.length;
        const defaultAnalista = document.getElementById('importDefaultAnalista');
        if (defaultAnalista) defaultAnalista.value = '';
        const PUEDE_ASIGNAR = @json($puedeAsignarAOtros);
        const MI_USER_ID   = @json(Auth::id());
        const MI_USER_NAME = @json(Auth::user()->name);
        const priorityColors = { Alta: 'bg-red-100 text-red-700', Media: 'bg-yellow-100 text-yellow-700', Baja: 'bg-blue-100 text-blue-700' };
        const analistas = @json($teamUsers->map(fn($u) => ['id' => $u->id, 'name' => $u->name . ($u->id === Auth::id() ? ' (Yo)' : '')]));
        let options = '<option value="">— Asignar a —</option>';
        analistas.forEach(a => { options += `<option value="${a.id}">${_esc(a.name)}</option>`; });
        const tbody = document.getElementById('importTasksBody');
        tbody.innerHTML = '';
        tasks.forEach((task, i) => {
            const pc = priorityColors[task.prioridad] || 'bg-slate-100 text-slate-600';
            const tr = document.createElement('tr');
            tr.className = i % 2 === 0 ? 'bg-white' : 'bg-slate-50';
            const asignarCell = PUEDE_ASIGNAR
                ? `<td class="px-3 py-2"><select class="analista-select w-full rounded-lg border-slate-300 text-xs py-1.5 bg-white focus:ring-blue-500" data-index="${i}" required>${options}</select></td>`
                : `<td class="px-3 py-2"><select class="analista-select" data-index="${i}" style="display:none"><option value="${MI_USER_ID}" selected>${_esc(MI_USER_NAME)}</option></select></td>`;
            tr.innerHTML = `
                <td class="px-3 py-2 text-slate-400 font-mono">${i + 1}</td>
                <td class="px-3 py-2 text-slate-800 font-medium max-w-xs"><span class="line-clamp-2 block" title="${_esc(task.nombre_actividad)}">${_esc(task.nombre_actividad)}</span></td>
                <td class="px-3 py-2 text-center text-slate-500 whitespace-nowrap">${task.fecha_compromiso || '—'}</td>
                <td class="px-3 py-2 text-center"><span class="inline-block px-2 py-0.5 rounded-full text-[10px] font-bold ${pc}">${task.prioridad}</span></td>
                ${asignarCell}`;
            tbody.appendChild(tr);
        });
        document.getElementById('importStep1').classList.add('hidden');
        document.getElementById('importStep2').classList.remove('hidden');
    }

    function setAllAnalistas(userId) {
        document.querySelectorAll('.analista-select').forEach(sel => {
            if (userId) sel.value = userId;
        });
    }

    function sendImportTasks() {
        const selects = document.querySelectorAll('.analista-select');
        const errDiv = document.getElementById('importStep2Error');
        errDiv.innerHTML = '';
        let hasUnassigned = false;
        const tasks = [];
        selects.forEach((sel, i) => {
            if (!sel.value) { hasUnassigned = true; sel.classList.add('border-red-400', 'ring-1', 'ring-red-400'); }
            else { sel.classList.remove('border-red-400', 'ring-1', 'ring-red-400'); tasks.push(Object.assign({}, _importTasksData[i], { assigned_to: sel.value })); }
        });
        if (hasUnassigned) {
            errDiv.innerHTML = '<div class="bg-red-50 text-red-800 p-3 rounded-lg border border-red-200 text-xs font-bold">Asigna un responsable a cada tarea antes de continuar.</div>';
            return;
        }
        const projectIdEl = document.getElementById('importProjectId');
        const payload = { tasks, _token: '{{ csrf_token() }}' };
        if (projectIdEl && projectIdEl.value) payload.proyecto_id = projectIdEl.value;
        const btn = document.getElementById('importSendBtn');
        btn.disabled = true;
        btn.innerHTML = '<svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Enviando...';
        fetch('{{ route("activities.import") }}', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(async r => {
            const text = await r.text();
            try { return JSON.parse(text); }
            catch (e) { throw new Error('Respuesta no JSON. Status ' + r.status + ': ' + text.substring(0, 200)); }
        })
        .then(data => {
            if (data.success) {
                errDiv.innerHTML = '<div class="bg-emerald-50 text-emerald-800 p-3 rounded-lg border border-emerald-200 text-xs font-bold flex items-center gap-2"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>' + data.message + '</div>';
                setTimeout(() => { closeImportModal(); location.reload(); }, 2000);
            } else {
                errDiv.innerHTML = '<div class="bg-red-50 text-red-800 p-3 rounded-lg border border-red-200 text-xs font-bold">' + (data.message || 'Error al importar.') + '</div>';
                btn.disabled = false;
                btn.innerHTML = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Enviar Tareas';
            }
        })
        .catch(e => {
            const msg = (e.message && !e.message.includes('<!DOCTYPE')) ? e.message : 'Error del servidor. Revisa la consola o contacta al administrador.';
            errDiv.innerHTML = '<div class="bg-red-50 text-red-800 p-3 rounded-lg border border-red-200 text-xs font-bold">' + msg + '</div>';
            btn.disabled = false;
            btn.innerHTML = '<svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> Enviar Tareas';
        });
    }

    function _esc(str) { const d = document.createElement('div'); d.appendChild(document.createTextNode(str || '')); return d.innerHTML; }
    function openPlanModal(){ document.getElementById('planModal').classList.remove('hidden'); updateWeekLabels(); for(let i=0;i<5;i++){const c=document.getElementById(`container-day-${i}`);if(c && c.children.length===0)addTaskCard(i);} }
    function updateWeekLabels(){ const v=document.getElementById('weekPicker').value; if(!v)return; const l=new Date(v+'T00:00:00'); for(let i=0;i<5;i++){const d=new Date(l);d.setDate(l.getDate()+i); document.getElementById(`label-date-${i}`).innerText=d.toLocaleDateString('es-MX',{day:'numeric',month:'short'}); } }
    function addTaskCard(dayIndex){
        const c = document.getElementById(`container-day-${dayIndex}`); const idx = c.children.length + Math.floor(Math.random()*9999);
        c.insertAdjacentHTML('beforeend', `<div class="bg-white p-2 rounded border border-slate-200 shadow-sm relative group"><div onclick="this.parentElement.remove()" class="absolute -top-1 -right-1 text-slate-300 hover:text-red-500 cursor-pointer bg-white rounded-full"><svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg></div><input type="hidden" name="plan[${dayIndex}][${idx}][area]" value="General"><div class="flex gap-1 mb-1"><input type="time" name="plan[${dayIndex}][${idx}][start_time]" class="text-[10px] w-full border-slate-200 rounded px-1"><input type="time" name="plan[${dayIndex}][${idx}][end_time]" class="text-[10px] w-full border-slate-200 rounded px-1"></div><input type="text" name="plan[${dayIndex}][${idx}][cliente]" placeholder="Cliente" class="w-full text-[10px] border-none border-b border-slate-200 p-0 mb-1 focus:ring-0 text-indigo-600 font-bold"><textarea name="plan[${dayIndex}][${idx}][actividad]" rows="2" class="w-full text-xs border-slate-200 rounded p-1" placeholder="Actividad..." required></textarea></div>`);
    }
    function openHistory(id) {
        const d = JSON.parse(document.getElementById('history-json-'+id).textContent); const c = document.getElementById('history-container');
        if(!d || !d.length) { c.innerHTML='<p class="text-center text-slate-400 py-4 text-xs">Sin historial.</p>'; } 
        else {
            d.sort((a,b)=>new Date(b.created_at)-new Date(a.created_at));
            let h = '<div class="space-y-3 relative border-l border-slate-200 ml-2">';
            d.forEach(x => {
                const date = new Date(x.created_at).toLocaleDateString('es-MX', {day:'numeric', month:'short', hour:'2-digit', minute:'2-digit'});
                h+=`<div class="ml-4 relative"><span class="absolute -left-[1.35rem] top-1 w-2.5 h-2.5 bg-slate-300 rounded-full ring-4 ring-white"></span><p class="text-[10px] text-slate-400 font-mono">${date}</p><p class="text-xs text-slate-700 font-bold">${x.user?x.user.name.split(' ')[0]:'Sistema'}</p><p class="text-xs text-slate-500 bg-slate-50 p-2 rounded border border-slate-100 mt-1">${x.details||x.action}</p></div>`;
            });
            h+='</div>'; c.innerHTML=h;
        }
        document.getElementById('historyModal').classList.remove('hidden');
    }
</script>
<style>
    .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
    .animate-fade-in-down { animation: fadeInDown 0.3s ease-out; }
    @keyframes fadeInDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
</style>

{{-- ============================================================ --}}
{{-- MODAL: CONFIGURAR VENTANA DE PLANEACIÓN (Solo Admin)        --}}
{{-- ============================================================ --}}
@if(!empty($puedeGestionarPlaneacion))
<div id="modalPlaneacionVentana" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">

        <div class="flex items-center justify-between p-6 border-b border-slate-100">
            <div class="flex items-center gap-3">
                <div class="p-2.5 bg-violet-100 text-violet-600 rounded-xl">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Ventana de Planeación</h3>
                    <p class="text-xs text-slate-500">Configura qué día y horario se habilita la planeación semanal.</p>
                </div>
            </div>
            <button onclick="document.getElementById('modalPlaneacionVentana').classList.add('hidden')"
                class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <div class="px-6 pt-5">
            <h4 class="text-xs font-bold text-slate-600 uppercase tracking-wider mb-3">Nueva Configuración</h4>
            <form id="formPlaneacionVentana" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Día de la semana</label>
                    <select id="pv_dia" name="dia_semana" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-violet-400" required>
                        <option value="1">Lunes</option>
                        <option value="2">Martes</option>
                        <option value="3">Miércoles</option>
                        <option value="4">Jueves</option>
                        <option value="5">Viernes</option>
                        <option value="6">Sábado</option>
                        <option value="7">Domingo</option>
                    </select>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Hora de apertura</label>
                        <input type="time" id="pv_apertura" name="hora_apertura" value="09:00"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-violet-400" required>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Hora de cierre</label>
                        <input type="time" id="pv_cierre" name="hora_cierre" value="11:00"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-violet-400" required>
                    </div>
                </div>

                <div id="pvMsg" class="hidden text-sm px-4 py-3 rounded-xl font-medium"></div>

                <div class="flex gap-3 pt-1">
                    <button type="submit" class="flex-1 flex items-center justify-center gap-2 px-5 py-2.5 bg-violet-600 hover:bg-violet-700 text-white text-sm font-bold rounded-xl transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        Guardar
                    </button>
                    <button type="button" onclick="document.getElementById('modalPlaneacionVentana').classList.add('hidden')"
                        class="px-5 py-2.5 border border-slate-200 text-slate-600 text-sm font-bold rounded-xl hover:bg-slate-50 transition-colors">
                        Cancelar
                    </button>
                </div>
            </form>
        </div>

        <div class="px-6 pt-4 pb-6">
            <h4 class="text-xs font-bold text-slate-600 uppercase tracking-wider mb-3">Ventanas Configuradas</h4>
            <div id="listaPvVentanas" class="space-y-2">
                <p class="text-xs text-slate-400 text-center py-4">Cargando...</p>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const modal = document.getElementById('modalPlaneacionVentana');
    const form  = document.getElementById('formPlaneacionVentana');
    const msg   = document.getElementById('pvMsg');
    const lista = document.getElementById('listaPvVentanas');

    modal.addEventListener('click', function(e) { if (e.target === modal) modal.classList.add('hidden'); });

    document.querySelector('[onclick*="modalPlaneacionVentana"]')?.addEventListener('click', cargarVentanas);

    function cargarVentanas() {
        fetch('{{ route("activities.planeacion.ventanas") }}', {
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.error) {
                lista.innerHTML = `<p class="text-xs text-red-400 text-center py-4">Error: ${data.error}</p>`;
                return;
            }
            if (!data.ventanas || data.ventanas.length === 0) {
                lista.innerHTML = '<p class="text-xs text-slate-400 text-center py-4">Sin configuración guardada. Se usa el horario por defecto (Lunes 9-11 AM).</p>';
                return;
            }
            lista.innerHTML = data.ventanas.map(v => `
                <div class="flex items-center justify-between p-3 rounded-xl border ${v.activo ? 'border-violet-200 bg-violet-50' : 'border-slate-100 bg-white'}">
                    <div>
                        <p class="text-sm font-bold ${v.activo ? 'text-violet-800' : 'text-slate-600'}">${v.dia_nombre}</p>
                        <p class="text-xs ${v.activo ? 'text-violet-600' : 'text-slate-400'} mt-0.5">${v.hora_apertura.substring(0,5)} → ${v.hora_cierre.substring(0,5)}</p>
                    </div>
                    <button onclick="eliminarVentana(${v.id}, this)"
                        class="text-xs font-bold px-3 py-1.5 rounded-lg bg-red-50 text-red-600 hover:bg-red-100 transition-colors">
                        Eliminar
                    </button>
                </div>
            `).join('');
        })
        .catch(() => { lista.innerHTML = '<p class="text-xs text-red-400 text-center py-4">Error al cargar.</p>'; });
    }

    window.eliminarVentana = function(id, btn) {
        if (!confirm('¿Eliminar esta ventana de planeación?')) return;
        btn.disabled = true;
        fetch(`{{ url('activities/planeacion-ventanas') }}/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(() => cargarVentanas())
        .catch(() => { btn.disabled = false; });
    };

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        msg.classList.add('hidden');

        const data = {
            dia_semana:    document.getElementById('pv_dia').value,
            hora_apertura: document.getElementById('pv_apertura').value,
            hora_cierre:   document.getElementById('pv_cierre').value,
        };

        fetch('{{ route("activities.planeacion.save") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                msg.textContent = res.message;
                msg.className = 'text-sm px-4 py-3 rounded-xl font-medium bg-emerald-50 text-emerald-800 border border-emerald-200';
                msg.classList.remove('hidden');
                form.reset();
                document.getElementById('pv_apertura').value = '09:00';
                document.getElementById('pv_cierre').value = '11:00';
                cargarVentanas();
                setTimeout(() => location.reload(), 1800);
            } else {
                msg.textContent = res.message ?? 'Error al guardar.';
                msg.className = 'text-sm px-4 py-3 rounded-xl font-medium bg-red-50 text-red-800 border border-red-200';
                msg.classList.remove('hidden');
            }
        })
        .catch(() => {
            msg.textContent = 'Error de conexión.';
            msg.className = 'text-sm px-4 py-3 rounded-xl font-medium bg-red-50 text-red-800 border border-red-200';
            msg.classList.remove('hidden');
        });
    });
})();
</script>
@endif
@endsection