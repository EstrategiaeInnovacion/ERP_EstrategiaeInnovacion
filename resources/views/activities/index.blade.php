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
    
    // Validar Horario (Lunes 9:00 - 11:00)
    $esHorarioPermitido = isset($esHorarioPermitido) ? $esHorarioPermitido : (now()->isMonday() && now()->hour >= 9 && now()->hour < 11);
    
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
                    <button onclick='openNotes(@json($rej), true)' class="bg-white border border-red-200 text-red-600 px-3 py-1 rounded text-[10px] font-bold uppercase hover:bg-red-50 transition">Corregir</button>
                </div>
            @endforeach
            </div>
        @endif

        {{-- ======================================================= --}}
        {{-- 4. BARRA DE HERRAMIENTAS Y FILTROS VISUALES             --}}
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
                
                {{-- Botón Generar Reporte --}}
                <button onclick="document.getElementById('reportModal').classList.remove('hidden')" class="bg-white text-slate-600 border border-slate-200 px-3 py-2 rounded-lg text-xs font-bold shadow-sm hover:bg-slate-50 hover:text-indigo-600 transition flex items-center gap-2" title="Generar PDF Cliente">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    <span class="hidden md:inline">Reporte</span>
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
                            <th class="px-4 py-3 text-center">#</th>
                            <th class="px-4 py-3 text-center w-20">Origen</th>
                            <th class="px-4 py-3 text-center">Prio</th>
                            <th class="px-4 py-3 min-w-[250px]">Descripción</th>
                            <th class="px-4 py-3">Cliente/Área</th>
                            <th class="px-4 py-3 text-center">Fecha</th>
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
                                $rowClass = 'border-l-4 border-slate-200 opacity-75';

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
                                if ($act->estatus == 'Completado') $rowClass .= ' opacity-60 bg-slate-50/50';
                                if ($act->estatus == 'Por Aprobar') $rowClass .= ' bg-orange-50/30';
                                if ($act->estatus == 'Por Validar') $rowClass .= ' bg-purple-50/20'; // Fondo tenue para validación
                            @endphp

                            <tr class="transition-colors group {{ $rowClass }}">
                                <td class="px-4 py-3 text-center text-slate-400 font-mono">{{ $index + 1 }}</td>
                                
                                <td class="px-4 py-3 text-center">
                                    @if($rowType == 'personal')
                                        <div class="flex flex-col items-center justify-center" title="Personal"><div class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg></div></div>
                                    @elseif($rowType == 'recibida')
                                        <div class="flex flex-col items-center justify-center" title="De: {{ $act->asignador->name ?? '?' }}"><div class="w-5 h-5 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center animate-pulse"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg></div></div>
                                    @elseif($rowType == 'delegada')
                                        <div class="flex flex-col items-center justify-center" title="Para: {{ $act->user->name }}"><div class="w-5 h-5 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg></div></div>
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
                                        <span class="{{ $act->estatus == 'Completado' ? 'line-through text-slate-400' : 'text-slate-800 font-semibold' }} text-xs leading-snug">
                                            {{ $act->nombre_actividad }}
                                        </span>
                                        <div class="flex flex-wrap items-center gap-1 mt-0.5">
                                            @if($rowType == 'delegada') <span class="text-[9px] text-purple-600 bg-purple-50 px-1 rounded border border-purple-100">↪ {{ strtok($act->user->name, ' ') }}</span> @endif
                                            @if($rowType == 'recibida') <span class="text-[9px] text-blue-600 bg-blue-50 px-1 rounded border border-blue-100">↩ {{ strtok($act->asignador->name ?? '?', ' ') }}</span> @endif
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
                                            'Por Validar'=>'bg-purple-100 text-purple-700 animate-pulse', // Nuevo Badge
                                            'Planeado'=>'bg-indigo-100 text-indigo-700', 
                                            'En proceso'=>'bg-blue-100 text-blue-700', 
                                            'Completado'=>'bg-emerald-100 text-emerald-700', 
                                            'Retardo'=>'bg-red-100 text-red-700', 
                                            'En blanco'=>'bg-slate-100 text-slate-500', 
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

                                        {{-- CASO 2: VALIDACIÓN DE CIERRE (NUEVO) --}}
                                        @elseif($act->estatus == 'Por Validar')
                                            @if($esSupervisor || $esDireccion)
                                                {{-- Botón validar cierre --}}
                                                <form action="{{ route('activities.validate', $act->id) }}" method="POST">@csrf @method('PUT')
                                                    <button class="bg-purple-600 text-white px-2 py-0.5 rounded text-[9px] font-bold hover:bg-purple-700 shadow-sm flex items-center gap-1" title="Validar Cierre">
                                                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> VALIDAR
                                                    </button>
                                                </form>
                                                <button onclick="rejectActivity({{ $act->id }})" class="text-red-400 hover:text-red-600 p-1" title="Rechazar entrega"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                                            @else
                                                <span class="text-[9px] text-purple-400 italic font-medium flex items-center gap-1"><svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg> Revisión</span>
                                            @endif

                                        {{-- CASO 3: FLUJO NORMAL --}}
                                        @elseif($act->estatus == 'Planeado' && !$isHistoryView && $act->user_id == Auth::id())
                                            <form action="{{ route('activities.start', $act->id) }}" method="POST">@csrf @method('PUT')<button class="bg-indigo-600 text-white px-2 py-0.5 rounded text-[9px] font-bold hover:bg-indigo-700">INICIAR</button></form>
                                        @else
                                            <button onclick='openNotes(@json($act), {{ ($esSupervisor || $esDireccion) ? "true" : "false" }})' class="text-slate-400 hover:text-indigo-600 p-1.5"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg></button>
                                            @if(($esSupervisor || $esDireccion) || $act->estatus == 'En blanco' || ($act->asignado_por == Auth::id()))
                                                <form action="{{ route('activities.destroy', $act->id) }}" method="POST" onsubmit="return confirm('¿Eliminar?')" class="inline">@csrf @method('DELETE')<button class="text-slate-300 hover:text-red-500 p-1.5"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></form>
                                            @endif
                                        @endif

                                        <button onclick="openHistory({{ $act->id }})" class="text-slate-300 hover:text-indigo-500 p-1.5"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></button>
                                        <script id="history-json-{{ $act->id }}" type="application/json">@json($act->historial)</script>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="11" class="py-12 text-center text-slate-400">Sin actividades en este rango.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
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
                <form action="{{ route('activities.store') }}" method="POST">
                    @csrf
                    <div class="bg-white px-8 py-8">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-bold text-slate-800">Nueva Actividad</h3>
                            <button type="button" onclick="document.getElementById('quickCreateModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600"><svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                        </div>
                        <div class="mb-6 bg-indigo-50 p-4 rounded-xl border border-indigo-100">
                            <label class="block text-xs font-bold text-indigo-800 uppercase mb-2 tracking-wide">Asignar tarea a:</label>
                            <select name="assigned_to" class="w-full rounded-lg border-indigo-200 text-sm focus:ring-indigo-500 bg-white shadow-sm text-slate-700 py-2.5">
                                <option value="{{ Auth::id() }}">Mí mismo ({{ Auth::user()->name }})</option>
                                @foreach($usersList as $u) @if($u->id !== Auth::id()) <option value="{{ $u->id }}" {{ $targetUser->id == $u->id ? 'selected' : '' }}>{{ $u->name }}</option> @endif @endforeach
                            </select>
                        </div>
                        <div class="space-y-5">
                            <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Descripción</label><input type="text" name="nombre_actividad" class="w-full rounded-lg border-slate-300 text-sm focus:ring-indigo-500 py-2.5" placeholder="¿Qué se debe hacer?" required></div>
                            <div class="grid grid-cols-2 gap-5">
                                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Fecha</label><input type="date" name="fecha_compromiso" value="{{ now()->format('Y-m-d') }}" class="w-full rounded-lg border-slate-300 text-sm py-2.5"></div>
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
                        </div>
                    </div>
                    <div class="bg-slate-50 px-8 py-5 flex flex-row-reverse gap-3 border-t border-slate-200">
                        <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-bold shadow-md hover:bg-indigo-700 transition">Guardar</button>
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
                        <div class="mb-6 p-4 bg-slate-50 rounded-xl border border-slate-100 flex justify-between items-center">
                            <div><span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wide">Responsable</span><span id="modal-responsable" class="text-sm font-bold text-indigo-600">-</span></div>
                            <div class="text-right"><span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wide">Supervisor</span><span id="modal-supervisor" class="text-sm font-bold text-slate-700">-</span></div>
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
                            <div id="div-estatus-selector">
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Estatus</label>
                                <select name="estatus" id="modal-estatus" class="w-full text-sm rounded-lg border-slate-300 py-2.5 font-bold text-slate-700">
                                    <option value="En proceso">En proceso</option>
                                    <option value="Completado">Completado</option>
                                    <option value="Retardo">Retardo</option>
                                    <option value="Por Aprobar">Por Aprobar (Revisión)</option>
                                    <option value="Por Validar">Por Validar (Revisión de Cierre)</option>
                                </select>
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
        const now = new Date();
        if (now.getDay() !== 1 || now.getHours() < 9 || now.getHours() >= 11) {
            e.preventDefault(); alert("⚠️ Periodo de planificación cerrado (Lunes 9:00 - 11:00)."); return false;
        }
    }
    function openNotes(act, canEditAll) {
        const f = document.getElementById('notesForm'); f.action = "/activities/" + act.id;
        document.getElementById('modal-activity-name').value = act.nombre_actividad;
        document.getElementById('modal-estatus').value = act.estatus;
        document.getElementById('modal-prioridad').value = act.prioridad || 'Media';
        document.getElementById('modal-fecha').value = act.fecha_compromiso ? act.fecha_compromiso.split('T')[0] : '';
        document.getElementById('modal-hora-inicio').value = act.hora_inicio_programada ? act.hora_inicio_programada.substring(0,5) : '';
        document.getElementById('modal-hora-fin').value = act.hora_fin_programada ? act.hora_fin_programada.substring(0,5) : '';
        document.getElementById('modal-comentarios').value = act.comentarios || '';
        document.getElementById('modal-area').value = act.area || 'General';
        document.getElementById('modal-cliente').value = act.cliente || '';
        document.getElementById('modal-responsable').innerText = act.user ? act.user.name : '-';
        document.getElementById('modal-supervisor').innerText = (act.user && act.user.empleado && act.user.empleado.supervisor) ? act.user.empleado.supervisor.nombre : 'N/A';
        
        const inputs = ['modal-activity-name','modal-fecha','modal-prioridad','modal-hora-inicio','modal-hora-fin','modal-area','modal-cliente'];
        inputs.forEach(id => {
            const el = document.getElementById(id);
            if(!canEditAll){ el.readOnly=true; el.classList.add('bg-slate-100'); } else { el.readOnly=false; el.classList.remove('bg-slate-100'); }
        });

        const divRej = document.getElementById('modal-rejection-alert');
        if(act.estatus === 'Rechazado'){ divRej.classList.remove('hidden'); document.getElementById('modal-rejection-reason').innerText=act.motivo_rechazo; } else { divRej.classList.add('hidden'); }
        document.getElementById('notesModal').classList.remove('hidden');
    }
    function closeNotes(){ document.getElementById('notesModal').classList.add('hidden'); }
    function rejectActivity(id){ document.getElementById('rejectForm').action="/activities/"+id+"/reject"; document.getElementById('rejectModal').classList.remove('hidden'); }
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
@endsection