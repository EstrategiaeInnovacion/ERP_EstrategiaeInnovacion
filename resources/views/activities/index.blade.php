@extends('layouts.erp')

@section('title', 'Tablero de Actividades')

@section('content')
{{-- ======================================================= --}}
{{-- 1. LÓGICA INICIAL Y VARIABLES DE SEGURIDAD              --}}
{{-- ======================================================= --}}
@php
    $posicionUser = strtolower(Auth::user()->empleado->posicion ?? '');
    
    // Validar si tiene puesto de Planificación (Anexo 24 / Post-Op)
    $esPuestoPlanificador = isset($esPuestoPlanificador) ? $esPuestoPlanificador : \Illuminate\Support\Str::contains($posicionUser, ['anexo 24', 'anexo24', 'post-operacion', 'post operacion', 'post operación']);
    
    // Validar Horario (Lunes 9:00 - 11:00)
    $esHorarioPermitido = isset($esHorarioPermitido) ? $esHorarioPermitido : (now()->isMonday() && now()->hour >= 9 && now()->hour < 11);
    
    // Datos Dinámicos (Fallbacks por seguridad)
    $areasDisponibles = isset($areasSistema) ? $areasSistema : collect(['General', 'Operativo', 'Administrativo']);
    $usersList = isset($empleadosAsignables) ? $empleadosAsignables : collect([]);
    $usersWithPending = isset($usersWithPending) ? $usersWithPending : [];
@endphp

<div class="min-h-screen bg-slate-50/50 py-8" x-data="{ showFilters: false }">
    <div class="max-w-[98%] mx-auto space-y-6">
        
        {{-- ======================================================= --}}
        {{-- 2. HEADER: TÍTULO, CONTEXTO Y NAVEGACIÓN                --}}
        {{-- ======================================================= --}}
        <div class="flex flex-col xl:flex-row justify-between items-start xl:items-center bg-white p-6 rounded-2xl shadow-sm border border-slate-100 gap-6 transition-all hover:shadow-md">
            
            {{-- IZQUIERDA: Info del Tablero Actual --}}
            <div class="flex items-center gap-5 min-w-[300px]">
                <div class="relative">
                    <div class="w-14 h-14 rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-700 text-white flex items-center justify-center font-bold text-xl shadow-lg shadow-indigo-200">
                        {{ substr($targetUser->name, 0, 2) }}
                    </div>
                    @if($targetUser->id === Auth::id())
                        <div class="absolute -bottom-1 -right-1 w-5 h-5 bg-emerald-500 border-2 border-white rounded-full" title="Tú"></div>
                    @endif
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800 tracking-tight leading-none">
                        {{ $targetUser->id === Auth::id() ? 'Mi Tablero' : $targetUser->name }}
                    </h1>
                    <div class="flex items-center gap-2 mt-1.5">
                        @if($isHistoryView)
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-100 text-slate-500 border border-slate-200 uppercase tracking-wide">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                Historial
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-50 text-emerald-600 border border-emerald-100 uppercase tracking-wide">
                                <span class="relative flex h-2 w-2">
                                  <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-emerald-400 opacity-75"></span>
                                  <span class="relative inline-flex rounded-full h-2 w-2 bg-emerald-500"></span>
                                </span>
                                En Vivo
                            </span>
                        @endif
                        <span class="text-xs text-slate-400 border-l border-slate-200 pl-2 ml-1">
                            {{ $targetUser->empleado->posicion ?? 'Colaborador' }}
                        </span>
                    </div>
                </div>
            </div>

            {{-- CENTRO: Selector de Empleado (SOLO JEFES - PARA VER TABLEROS AJENOS) --}}
            @if(($esSupervisor || $esDireccion) && $teamUsers->count() > 0)
                <div class="w-full xl:w-auto flex-1 max-w-xl">
                    <form method="GET" id="userSelectorForm" class="relative group">
                        @foreach(request()->except(['user_id', 'page']) as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        
                        <div class="absolute inset-y-0 left-0 flex items-center pl-4 pointer-events-none">
                            <svg class="w-5 h-5 text-slate-400 group-hover:text-indigo-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                        </div>
                        <select name="user_id" onchange="document.getElementById('userSelectorForm').submit()" 
                                class="block w-full rounded-xl border-slate-200 bg-slate-50 pl-11 pr-10 py-3 text-sm font-semibold text-slate-700 shadow-inner focus:border-indigo-500 focus:ring-indigo-500 transition cursor-pointer hover:bg-white focus:bg-white">
                            <option value="{{ Auth::id() }}">Ver mi propio tablero</option>
                            <optgroup label="Mi Equipo">
                                @foreach($teamUsers as $u)
                                    @if($u->id !== Auth::id())
                                        {{-- AQUÍ ESTÁ EL CAMBIO: Alerta solo si SU id está en la lista de pendientes --}}
                                        <option value="{{ $u->id }}" {{ $targetUser->id == $u->id ? 'selected' : '' }}>
                                            {{ $u->name }} {{ in_array($u->id, $usersWithPending) ? '(⚠ Pendientes)' : '' }}
                                        </option>
                                    @endif
                                @endforeach
                            </optgroup>
                        </select>
                    </form>
                </div>
            @endif
            
            {{-- DERECHA: Navegación Fechas --}}
            <div class="flex items-center bg-slate-50 rounded-xl p-1.5 border border-slate-200 shadow-sm">
                <a href="{{ request()->fullUrlWithQuery(['week_view' => $startOfWeek->copy()->subWeek()->format('Y-m-d')]) }}" class="p-2 hover:bg-white rounded-lg text-slate-400 hover:text-indigo-600 transition shadow-none hover:shadow-sm" title="Semana Anterior">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" /></svg>
                </a>
                <div class="px-6 text-center min-w-[160px]">
                    <span class="block text-[9px] uppercase font-bold text-slate-400 tracking-wide mb-0.5">Semana del</span>
                    <span class="text-sm font-bold text-slate-800">{{ $startOfWeek->format('d M') }} - {{ $endOfWeek->format('d M') }}</span>
                </div>
                <a href="{{ request()->fullUrlWithQuery(['week_view' => $startOfWeek->copy()->addWeek()->format('Y-m-d')]) }}" class="p-2 hover:bg-white rounded-lg text-slate-400 hover:text-indigo-600 transition shadow-none hover:shadow-sm" title="Semana Siguiente">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" /></svg>
                </a>
                
                @if($isHistoryView || request('week_view'))
                    <a href="{{ route('activities.index', ['user_id' => $targetUser->id]) }}" class="ml-3 px-4 py-1.5 text-[10px] font-bold text-indigo-600 bg-indigo-50 rounded-lg hover:bg-indigo-100 transition border border-indigo-100">
                        IR A HOY
                    </a>
                @endif
            </div>
        </div>

        {{-- ======================================================= --}}
        {{-- 3. ALERTAS (GLOBALES Y PERSONALES)                      --}}
        {{-- ======================================================= --}}
        
        @if(($esSupervisor || $esDireccion) && $globalPendingCount > 0)
            <div class="bg-gradient-to-r from-orange-50 to-white border-l-4 border-orange-400 p-4 rounded-r-xl shadow-sm flex flex-col md:flex-row items-center justify-between gap-4 animate-fade-in-down">
                <div class="flex items-center gap-4">
                    <div class="p-2 bg-orange-100 text-orange-600 rounded-full shadow-sm ring-4 ring-orange-50 animate-bounce">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-orange-900">Tienes {{ $globalPendingCount }} actividades esperando aprobación en tu equipo.</p>
                        <p class="text-xs text-orange-600 mt-0.5">Revisa el tablero de tus subordinados marcados en el selector.</p>
                    </div>
                </div>
            </div>
        @endif

        @if($targetUser->id === Auth::id() && $misRechazos->count() > 0)
            <div class="space-y-3">
            @foreach($misRechazos as $rej)
                <div class="bg-red-50 border border-red-200 p-4 rounded-xl flex flex-col md:flex-row justify-between items-center shadow-sm animate-fade-in-down gap-3">
                    <div class="flex items-start gap-3">
                        <svg class="w-5 h-5 text-red-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        <div>
                            <p class="text-sm font-bold text-red-800">Actividad Rechazada: <span class="font-normal text-red-900">"{{ $rej->nombre_actividad }}"</span></p>
                            <p class="text-xs text-red-600 mt-1 font-medium bg-red-100 px-2 py-0.5 rounded inline-block">Motivo: {{ $rej->motivo_rechazo }}</p>
                        </div>
                    </div>
                    <button onclick='openNotes(@json($rej), true)' class="bg-white border border-red-200 text-red-600 px-4 py-2 rounded-lg text-xs font-bold uppercase hover:bg-red-600 hover:text-white transition shadow-sm">
                        Corregir Ahora
                    </button>
                </div>
            @endforeach
            </div>
        @endif

        {{-- ======================================================= --}}
        {{-- 4. BARRA DE HERRAMIENTAS Y KPI RÁPIDO                   --}}
        {{-- ======================================================= --}}
        <div class="flex flex-col sm:flex-row justify-between items-center gap-4 py-2">
            
            {{-- Filtro Switch --}}
            <form method="GET" id="filterOptions" class="flex items-center gap-2">
                @foreach(request()->except(['ver_historial']) as $k=>$v) <input type="hidden" name="{{ $k }}" value="{{ $v }}"> @endforeach
                <label class="flex items-center gap-3 cursor-pointer bg-white px-4 py-2.5 rounded-xl border border-slate-200 shadow-sm hover:border-indigo-300 transition group select-none">
                    <input type="checkbox" name="ver_historial" value="1" onchange="document.getElementById('filterOptions').submit()" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 w-4 h-4" {{ $verTodo ? 'checked' : '' }}>
                    <span class="text-xs font-bold text-slate-600 group-hover:text-indigo-600 transition-colors">Mostrar Terminados</span>
                </label>
            </form>

            {{-- Botones de Acción --}}
            <div class="flex gap-3 w-full sm:w-auto">
                
                {{-- BOTÓN PLANIFICAR: SOLO ANEXO 24 / POST-OP --}}
                @if($targetUser->id === Auth::id() && $esPuestoPlanificador)
                    @if($esHorarioPermitido)
                        {{-- BOTÓN ACTIVO --}}
                        <button onclick="openPlanModal()" class="flex-1 sm:flex-none bg-white text-indigo-600 border border-indigo-200 px-5 py-2.5 rounded-xl text-xs font-bold shadow-sm hover:bg-indigo-50 hover:shadow-md transition flex items-center justify-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            Planificar Semana
                        </button>
                    @else
                        {{-- BOTÓN DESHABILITADO --}}
                        <button disabled class="flex-1 sm:flex-none bg-slate-100 text-slate-400 border border-slate-200 px-5 py-2.5 rounded-xl text-xs font-bold cursor-not-allowed flex items-center justify-center gap-2" title="Solo disponible Lunes 9:00 - 11:00 AM">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            Planificación Cerrada
                        </button>
                    @endif
                @endif
                
                {{-- BOTÓN CREAR: DISPONIBLE PARA TODOS (Para asignación individual) --}}
                <button onclick="document.getElementById('quickCreateModal').classList.remove('hidden')" class="flex-1 sm:flex-none bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-xs font-bold shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:scale-105 transition transform flex items-center justify-center gap-2">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg> 
                    {{ $targetUser->id === Auth::id() ? 'Nueva Actividad' : 'Asignar Tarea' }}
                </button>
            </div>
        </div>

        {{-- ======================================================= --}}
        {{-- 5. TABLA PRINCIPAL UNIFICADA                            --}}
        {{-- ======================================================= --}}
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-slate-100 relative">
            
            {{-- Header de la Tabla --}}
            <div class="bg-slate-800 px-6 py-4 border-b border-slate-700 flex justify-between items-center">
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    Listado de Actividades
                </h3>
                <div class="flex items-center gap-4">
                    <div class="hidden sm:flex items-center gap-1 text-[10px] text-slate-400 bg-slate-700/50 px-3 py-1 rounded-full border border-slate-600">
                        <span class="w-2 h-2 rounded-full bg-red-500"></span> Alta
                        <span class="w-2 h-2 rounded-full bg-amber-400 ml-2"></span> Media
                        <span class="w-2 h-2 rounded-full bg-blue-300 ml-2"></span> Baja
                    </div>
                    <span class="bg-indigo-600 text-white text-[10px] px-2.5 py-0.5 rounded-full font-mono shadow-md border border-indigo-500">
                        {{ $mainActivities->count() }} Total
                    </span>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="min-w-full text-xs text-left border-collapse">
                    <thead class="bg-slate-50 text-slate-500 font-bold uppercase tracking-wider text-[10px] border-b border-slate-200">
                        <tr>
                            <th class="px-4 py-4 w-10 text-center">#</th>
                            <th class="px-4 py-4 w-20 text-center text-slate-400">F. Asignación</th>
                            <th class="px-4 py-4 w-16 text-center">Prio</th>
                            <th class="px-4 py-4 min-w-[280px]">Descripción</th>
                            <th class="px-4 py-4 w-32">Cliente/Área</th>
                            <th class="px-4 py-4 w-24 text-center">Promesa</th>
                            <th class="px-2 py-4 w-20 text-center bg-slate-100/50 text-slate-600 border-l border-slate-100">Fin Real</th>
                            <th class="px-2 py-4 w-16 text-center bg-slate-100/50 text-slate-600">Días</th>
                            <th class="px-2 py-4 w-16 text-center bg-slate-100/50 text-slate-600 border-r border-slate-100">% Efic.</th>
                            <th class="px-4 py-4 w-32 text-center">Estatus</th>
                            <th class="px-4 py-4 w-32 text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse($mainActivities as $index => $act)
                            <tr class="hover:bg-indigo-50/40 transition-colors group 
                                {{ $act->estatus == 'Completado' ? 'bg-slate-50/70 opacity-70' : '' }} 
                                {{ $act->estatus == 'Por Aprobar' ? 'bg-orange-50/30' : '' }}">
                                
                                <td class="px-4 py-4 text-center text-slate-400 font-mono text-[10px]">{{ $index + 1 }}</td>
                                
                                <td class="px-4 py-4 text-center text-slate-400 font-mono text-[10px]">
                                    {{ $act->fecha_inicio ? $act->fecha_inicio->format('d/m') : '-' }}
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <span class="inline-flex items-center justify-center w-6 h-6 rounded-full text-[9px] font-bold text-white shadow-sm ring-2 ring-white
                                        {{ $act->prioridad == 'Alta' ? 'bg-red-500 shadow-red-100' : ($act->prioridad == 'Media' ? 'bg-amber-400 shadow-amber-100' : 'bg-blue-300 shadow-blue-100') }}" 
                                        title="{{ $act->prioridad }}">
                                        {{ substr($act->prioridad, 0, 1) }}
                                    </span>
                                </td>

                                <td class="px-4 py-4">
                                    <div class="flex flex-col gap-1">
                                        <span class="{{ $act->estatus == 'Completado' ? 'line-through text-slate-400' : 'text-slate-800 font-semibold' }} text-sm leading-snug">
                                            {{ $act->nombre_actividad }}
                                        </span>
                                        <div class="flex items-center gap-2">
                                            @if($act->hora_inicio_programada)
                                                <span class="text-[9px] text-slate-500 font-mono bg-slate-100 px-1.5 py-0.5 rounded border border-slate-200">
                                                    {{ \Carbon\Carbon::parse($act->hora_inicio_programada)->format('H:i') }} - {{ \Carbon\Carbon::parse($act->hora_fin_programada)->format('H:i') }}
                                                </span>
                                            @endif
                                            @if($act->comentarios)
                                                <div class="flex items-center gap-1 text-[9px] text-indigo-400 truncate max-w-[150px]" title="{{ $act->comentarios }}">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
                                                    {{ $act->comentarios }}
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </td>

                                <td class="px-4 py-4">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-slate-600 text-[11px]">{{ Str::limit($act->cliente ?? '-', 18) }}</span>
                                        <span class="text-[9px] text-slate-400">{{ $act->area ?? 'General' }}</span>
                                    </div>
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <div class="flex flex-col items-center">
                                        <span class="font-bold text-[11px] {{ $act->fecha_compromiso->isToday() ? 'text-indigo-600 bg-indigo-50 px-2 py-0.5 rounded' : 'text-slate-600' }}">
                                            {{ $act->fecha_compromiso->format('d M') }}
                                        </span>
                                        <span class="text-[9px] text-slate-400">{{ $act->fecha_compromiso->format('l') }}</span>
                                    </div>
                                </td>

                                <td class="px-2 py-4 text-center border-l border-slate-100">
                                    @if($act->fecha_final)
                                        <span class="text-[10px] font-mono text-slate-600 block">{{ $act->fecha_final->format('d M') }}</span>
                                        <span class="text-[9px] text-slate-400">{{ $act->fecha_final->format('H:i') }}</span>
                                    @else
                                        <span class="text-slate-200 text-[10px]">-</span>
                                    @endif
                                </td>

                                <td class="px-2 py-4 text-center">
                                    @php
                                        $dias = $act->resultado_dias;
                                        $color = 'text-slate-300';
                                        if($dias !== null) {
                                            $metrico = $act->metrico ?? 1;
                                            $color = ($dias > $metrico) ? 'text-red-600 font-black' : 'text-emerald-600 font-black';
                                        }
                                    @endphp
                                    <span class="text-[11px] {{ $color }}">{{ $dias ?? '-' }}</span>
                                </td>

                                <td class="px-2 py-4 text-center border-r border-slate-100">
                                    @if(isset($act->porcentaje))
                                        <span class="text-[10px] font-black {{ $act->porcentaje < 100 ? 'text-orange-500' : 'text-slate-700' }}">
                                            {{ number_format($act->porcentaje, 0) }}%
                                        </span>
                                    @else
                                        <span class="text-slate-300">-</span>
                                    @endif
                                </td>

                                <td class="px-4 py-4 text-center">
                                    @php
                                        $badges = [
                                            'Por Aprobar' => 'bg-orange-50 text-orange-700 ring-1 ring-orange-200',
                                            'Planeado' => 'bg-indigo-50 text-indigo-700 ring-1 ring-indigo-200',
                                            'En proceso' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-200',
                                            'Completado' => 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200',
                                            'Retardo' => 'bg-red-50 text-red-700 ring-1 ring-red-200 animate-pulse',
                                            'En blanco' => 'bg-slate-100 text-slate-500 border border-slate-200',
                                            'Rechazado' => 'bg-red-50 text-red-600 ring-1 ring-red-200',
                                        ];
                                        $class = $badges[$act->estatus] ?? 'bg-gray-100 text-gray-500';
                                        $label = $act->estatus == 'Planeado' ? 'Autorizado' : $act->estatus;
                                    @endphp
                                    <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase tracking-wide {{ $class }} shadow-sm">
                                        {{ $label }}
                                    </span>
                                </td>

                                <td class="px-4 py-4 text-center">
                                    <div class="flex justify-center items-center gap-2">
                                        
                                        @if($act->estatus == 'Por Aprobar')
                                            @if(($esSupervisor || $esDireccion) && $targetUser->id !== Auth::id())
                                                <form action="{{ route('activities.approve', $act->id) }}" method="POST">@csrf @method('PUT')
                                                    <button class="text-emerald-500 hover:bg-emerald-50 p-2 rounded-lg border border-transparent hover:border-emerald-200 transition" title="Aprobar"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg></button>
                                                </form>
                                                <button onclick="rejectActivity({{ $act->id }})" class="text-red-500 hover:bg-red-50 p-2 rounded-lg border border-transparent hover:border-red-200 transition" title="Rechazar"><svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                                            @else
                                                <span class="text-[9px] text-orange-400 italic font-medium flex items-center gap-1">
                                                    <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg> Revisión
                                                </span>
                                            @endif

                                        @elseif($act->estatus == 'Planeado' && !$isHistoryView && $targetUser->id == Auth::id())
                                            <form action="{{ route('activities.start', $act->id) }}" method="POST">
                                                @csrf @method('PUT')
                                                <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-3 py-1.5 rounded-lg text-[10px] font-bold shadow-md shadow-indigo-200 transition transform hover:-translate-y-0.5">
                                                    INICIAR
                                                </button>
                                            </form>

                                        @else
                                            <button onclick='openNotes(@json($act), {{ ($esSupervisor || $esDireccion) ? "true" : "false" }})' class="text-slate-400 hover:text-indigo-600 transition p-2 hover:bg-indigo-50 rounded-lg" title="Ver Detalles">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                                            </button>
                                            
                                            @if(($esSupervisor || $esDireccion) || $act->estatus == 'En blanco')
                                                <form action="{{ route('activities.destroy', $act->id) }}" method="POST" onsubmit="return confirm('¿Eliminar actividad?')" class="inline">
                                                    @csrf @method('DELETE')
                                                    <button class="text-slate-300 hover:text-red-500 transition p-2" title="Eliminar"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>
                                                </form>
                                            @endif
                                        @endif

                                        <button onclick="openHistory({{ $act->id }})" class="text-slate-300 hover:text-indigo-500 transition p-2 hover:bg-slate-100 rounded-lg" title="Ver Historial">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </button>
                                        
                                        <script id="history-json-{{ $act->id }}" type="application/json">
                                            @json($act->historial)
                                        </script>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="py-20 text-center">
                                    <div class="flex flex-col items-center justify-center text-slate-400">
                                        <div class="bg-slate-50 p-5 rounded-full mb-4 border border-slate-100 shadow-sm">
                                            <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                        </div>
                                        <p class="text-base font-bold text-slate-600">Todo limpio por aquí</p>
                                        <p class="text-xs text-slate-400 mt-1">No hay actividades visibles con los filtros actuales.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

{{-- ================= MODALES ================= --}}

{{-- 1. MODAL CREACIÓN RÁPIDA (CON SELECTOR DE ASIGNACIÓN) --}}
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
                        
                        {{-- SELECTOR DE DESTINATARIO --}}
                        <div class="mb-6 bg-indigo-50 p-4 rounded-xl border border-indigo-100">
                            <label class="block text-xs font-bold text-indigo-800 uppercase mb-2 tracking-wide">Asignar tarea a:</label>
                            <select name="assigned_to" class="w-full rounded-lg border-indigo-200 text-sm focus:ring-indigo-500 bg-white shadow-sm text-slate-700 py-2.5">
                                <option value="{{ Auth::id() }}">Mí mismo ({{ Auth::user()->name }})</option>
                                @foreach($usersList as $u)
                                    @if($u->id !== Auth::id())
                                        <option value="{{ $u->id }}" {{ $targetUser->id == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                            <p class="text-[10px] text-indigo-400 mt-2 italic">* Si asignas a un compañero, su supervisor deberá aprobarlo primero.</p>
                        </div>

                        <div class="space-y-5">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Descripción</label>
                                <input type="text" name="nombre_actividad" class="w-full rounded-lg border-slate-300 text-sm focus:ring-indigo-500 py-2.5" placeholder="¿Qué se debe hacer?" required>
                            </div>
                            <div class="grid grid-cols-2 gap-5">
                                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Fecha</label><input type="date" name="fecha_compromiso" value="{{ now()->format('Y-m-d') }}" class="w-full rounded-lg border-slate-300 text-sm py-2.5"></div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Prioridad</label>
                                    <select name="prioridad" class="w-full rounded-lg border-slate-300 text-sm py-2.5">
                                        <option value="Media">Media</option>
                                        <option value="Alta">Alta 🔥</option>
                                        <option value="Baja">Baja</option>
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-5">
                                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Inicio (Opcional)</label><input type="time" name="hora_inicio_programada" class="w-full rounded-lg border-slate-300 text-sm py-2.5"></div>
                                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Fin (Opcional)</label><input type="time" name="hora_fin_programada" class="w-full rounded-lg border-slate-300 text-sm py-2.5"></div>
                            </div>
                            <div class="grid grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Área</label>
                                    <select name="area" class="w-full rounded-lg border-slate-300 text-sm py-2.5 bg-white focus:ring-indigo-500">
                                        @foreach($areasDisponibles as $areaOp)
                                            <option value="{{ $areaOp }}" {{ $areaOp == 'General' ? 'selected' : '' }}>{{ $areaOp }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Cliente</label>
                                    <input type="text" name="cliente" class="w-full rounded-lg border-slate-300 text-sm py-2.5" placeholder="Opcional">
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-8 py-5 flex flex-row-reverse gap-3 border-t border-slate-200">
                        <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-bold shadow-md hover:bg-indigo-700 transition">Guardar Actividad</button>
                        <button type="button" onclick="document.getElementById('quickCreateModal').classList.add('hidden')" class="bg-white text-slate-700 border border-slate-300 px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-slate-50 transition">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- 2. MODAL EDICIÓN --}}
<div id="notesModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" onclick="closeNotes()"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-2xl transition-all sm:w-full sm:max-w-lg border border-slate-200">
                <form id="notesForm" method="POST" enctype="multipart/form-data">
                    @csrf @method('PUT')
                    <div class="bg-white px-8 py-8">
                        <div class="flex justify-between items-start mb-6">
                            <h3 class="text-xl font-bold text-slate-800">Detalles de Actividad</h3>
                            <button type="button" onclick="closeNotes()" class="text-slate-400 hover:text-slate-600"><svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                        </div>
                        
                        <div class="mb-6 p-4 bg-slate-50 rounded-xl border border-slate-100 flex justify-between items-center">
                            <div><span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wide">Responsable</span><span id="modal-responsable" class="text-sm font-bold text-indigo-600">-</span></div>
                            <div class="text-right"><span class="text-[10px] uppercase font-bold text-slate-400 block tracking-wide">Supervisor</span><span id="modal-supervisor" class="text-sm font-bold text-slate-700">-</span></div>
                        </div>

                        <div id="modal-rejection-alert" class="hidden mb-6 bg-red-50 border-l-4 border-red-500 p-4 rounded-r text-sm shadow-sm">
                            <p class="font-bold text-red-800 flex items-center gap-2">⚠️ Rechazado</p>
                            <p class="text-red-700 mt-1 text-xs pl-6">Motivo: <span id="modal-rejection-reason" class="font-bold italic">...</span></p>
                        </div>

                        <div class="space-y-5">
                            <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Descripción</label><textarea name="nombre_actividad" id="modal-activity-name" rows="2" class="w-full text-sm rounded-lg border-slate-300 bg-slate-50 focus:bg-white transition resize-none py-2.5"></textarea></div>
                            <div class="grid grid-cols-2 gap-5">
                                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Fecha</label><input type="date" name="fecha_compromiso" id="modal-fecha" class="w-full text-sm rounded-lg border-slate-300 py-2.5"></div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Prioridad</label>
                                    <select name="prioridad" id="modal-prioridad" class="w-full text-sm rounded-lg border-slate-300 py-2.5">
                                        <option value="Alta">Alta 🔥</option>
                                        <option value="Media">Media</option>
                                        <option value="Baja">Baja</option>
                                    </select>
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-5">
                                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Inicio</label><input type="time" name="hora_inicio_programada" id="modal-hora-inicio" class="w-full text-sm rounded-lg border-slate-300 py-2.5"></div>
                                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Fin</label><input type="time" name="hora_fin_programada" id="modal-hora-fin" class="w-full text-sm rounded-lg border-slate-300 py-2.5"></div>
                            </div>
                            <div class="grid grid-cols-2 gap-5">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Área</label>
                                    <select name="area" id="modal-area" class="w-full text-sm rounded-lg border-slate-300 py-2.5 bg-white focus:ring-indigo-500">
                                        @foreach($areasDisponibles as $areaOp)
                                            <option value="{{ $areaOp }}">{{ $areaOp }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Cliente</label><input type="text" name="cliente" id="modal-cliente" class="w-full text-sm rounded-lg border-slate-300 py-2.5"></div>
                            </div>
                            <div id="div-estatus-selector">
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Estatus</label>
                                <select name="estatus" id="modal-estatus" class="w-full text-sm rounded-lg border-slate-300 py-2.5 font-bold text-slate-700">
                                    <option value="En proceso">En proceso</option>
                                    <option value="Completado">Completado</option>
                                    <option value="Retardo">Retardo</option>
                                    <option value="Por Aprobar">Por Aprobar (Revisión)</option>
                                </select>
                            </div>
                            <div><label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Comentarios / Bitácora</label><textarea name="comentarios" id="modal-comentarios" rows="3" class="w-full text-sm rounded-lg border-slate-300 placeholder-slate-400 py-2.5" placeholder="Escribe actualizaciones aquí..."></textarea></div>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-8 py-5 flex flex-row-reverse gap-3 border-t border-slate-200">
                        <button type="submit" class="bg-indigo-600 text-white px-6 py-2.5 rounded-xl text-sm font-bold shadow hover:bg-indigo-700 transition">Guardar Cambios</button>
                        <button type="button" onclick="closeNotes()" class="bg-white text-slate-700 border border-slate-300 px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-slate-50 transition">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- 3. MODAL RECHAZO --}}
<div id="rejectModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm transition-opacity" onclick="document.getElementById('rejectModal').classList.add('hidden')"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:w-full sm:max-w-md border border-red-200">
                <form id="rejectForm" method="POST">
                    @csrf @method('PUT')
                    <div class="bg-white px-6 py-6">
                        <h3 class="text-lg font-bold text-red-700 mb-2">Rechazar Actividad</h3>
                        <p class="text-sm text-slate-500 mb-4">Indica el motivo para que el empleado lo corrija.</p>
                        <textarea name="motivo" class="w-full rounded-lg border-red-300 focus:ring-red-500 focus:border-red-500" rows="3" required placeholder="Ej. Fecha incorrecta..."></textarea>
                    </div>
                    <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse gap-2">
                        <button type="submit" class="bg-red-600 text-white px-4 py-2 rounded-lg text-sm font-bold hover:bg-red-700">Confirmar Rechazo</button>
                        <button type="button" onclick="document.getElementById('rejectModal').classList.add('hidden')" class="bg-white text-slate-700 border border-slate-300 px-4 py-2 rounded-lg text-sm font-bold">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- 4. MODAL PLANIFICADOR --}}
@if($esPuestoPlanificador)
<div id="planModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" onclick="document.getElementById('planModal').classList.add('hidden')"></div>
    <div class="fixed inset-0 z-10 flex items-center justify-center p-4 sm:p-6">
        <div class="relative w-full max-w-[95vw] h-[90vh] bg-white rounded-2xl shadow-2xl flex flex-col overflow-hidden border border-slate-200">
            <form action="{{ route('activities.storeBatch') }}" method="POST" class="flex flex-col h-full" onsubmit="return submitPlan(event)">
                @csrf
                <div class="px-8 py-5 border-b border-slate-100 flex flex-col md:flex-row justify-between items-center gap-4 bg-white z-20">
                    <div>
                        <h3 class="text-2xl font-bold text-slate-800 flex items-center gap-3">
                            <span class="p-2 bg-indigo-50 rounded-xl text-indigo-600">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            </span>
                            Planificador Semanal
                        </h3>
                        <p class="text-sm text-slate-500 mt-1 ml-1">Diseña tu semana. Estas actividades se enviarán a aprobación.</p>
                    </div>
                    <div class="flex items-center gap-3 bg-slate-50 p-1.5 rounded-xl border border-slate-200 shadow-sm">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider pl-3">Semana:</span>
                        <input type="date" name="semana_inicio" id="weekPicker" class="border-none bg-white text-slate-700 text-sm font-bold rounded-lg focus:ring-2 focus:ring-indigo-500 shadow-sm cursor-pointer hover:text-indigo-600 transition" value="{{ now()->startOfWeek()->format('Y-m-d') }}" onchange="updateWeekLabels()">
                    </div>
                </div>
                
                <div class="flex-1 overflow-hidden relative bg-slate-100">
                    <div class="h-full overflow-x-auto custom-scrollbar">
                        <div class="flex h-full min-w-max">
                            @foreach(['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes'] as $index => $dia)
                            <div class="w-[320px] flex flex-col h-full border-r border-slate-200 bg-slate-50/50 group transition-colors hover:bg-slate-100/50">
                                <div class="p-4 text-center border-b border-slate-200 bg-white sticky top-0 z-10 shadow-sm">
                                    <h4 class="text-sm font-black text-slate-700 uppercase tracking-wide">{{ $dia }}</h4>
                                    <span class="text-xs font-bold text-indigo-500 bg-indigo-50 px-2 py-0.5 rounded-full mt-1 inline-block" id="label-date-{{ $index }}">--/--</span>
                                </div>
                                <div class="flex-1 p-3 space-y-3 overflow-y-auto custom-scrollbar" id="container-day-{{ $index }}"></div>
                                <div class="p-3 bg-white border-t border-slate-200 sticky bottom-0 z-10">
                                    <button type="button" onclick="addTaskCard({{ $index }})" class="w-full py-2.5 border-2 border-dashed border-slate-300 rounded-xl text-slate-400 text-xs font-bold hover:border-indigo-400 hover:text-indigo-600 hover:bg-slate-50 transition-all flex justify-center items-center gap-2">
                                        <div class="bg-slate-200 rounded-full p-0.5 text-white group-hover:bg-indigo-500 transition-colors"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M12 4v16m8-8H4"/></svg></div>
                                        Agregar Tarea
                                    </button>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                </div>

                <div class="px-8 py-5 border-t border-slate-200 bg-white flex justify-between items-center z-20">
                    <button type="button" onclick="document.getElementById('planModal').classList.add('hidden')" class="text-slate-500 hover:text-slate-800 font-bold text-sm px-4 transition">Cancelar</button>
                    <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-8 py-3 rounded-xl text-sm font-bold shadow-lg shadow-indigo-200 transform hover:-translate-y-0.5 transition flex items-center gap-2">
                        <span>Enviar Planificación</span>
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

{{-- 5. MODAL HISTORIAL --}}
<div id="historyModal" class="fixed inset-0 z-50 hidden" aria-hidden="true" role="dialog">
    <div class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm transition-opacity" onclick="document.getElementById('historyModal').classList.add('hidden')"></div>
    <div class="flex items-center justify-center min-h-screen px-4 pointer-events-none">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md relative z-10 overflow-hidden pointer-events-auto border border-slate-100 transform transition-all">
            <div class="bg-slate-50 px-5 py-4 border-b border-slate-100 flex justify-between items-center">
                <h3 class="font-bold text-slate-700 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Historial de Movimientos
                </h3>
                <button onclick="document.getElementById('historyModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition p-1 rounded-full hover:bg-slate-200">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 max-h-[60vh] overflow-y-auto custom-scrollbar" id="history-container"></div>
            <div class="bg-slate-50 px-5 py-3 border-t border-slate-100 text-center">
                <button onclick="document.getElementById('historyModal').classList.add('hidden')" class="text-xs font-bold text-indigo-600 hover:text-indigo-800 uppercase tracking-wide">Cerrar Historial</button>
            </div>
        </div>
    </div>
</div>

<script>
    // --- VALIDACIÓN JS: CANDADO DE HORARIO ---
    function submitPlan(e) {
        const now = new Date();
        const day = now.getDay(); // 1 = Lunes
        const hour = now.getHours();

        // Validar Lunes (1) entre 9 y 11 AM
        if (day !== 1 || hour < 9 || hour >= 11) {
            e.preventDefault();
            alert("⚠️ ¡TIEMPO AGOTADO!\n\nEl periodo de planificación (Lunes 9:00 - 11:00) ha finalizado.\n\nEl sistema ya no aceptará este envío. Toma capturas de tu pantalla y notifica a tu supervisor.");
            return false;
        }
    }

    function openNotes(act, canEditAll) {
        const form = document.getElementById('notesForm');
        form.action = "/activities/" + act.id;
        
        document.getElementById('modal-activity-name').value = act.nombre_actividad;
        document.getElementById('modal-estatus').value = act.estatus;
        document.getElementById('modal-prioridad').value = act.prioridad || 'Media';
        document.getElementById('modal-fecha').value = act.fecha_compromiso ? act.fecha_compromiso.split('T')[0] : '';
        document.getElementById('modal-hora-inicio').value = act.hora_inicio_programada ? act.hora_inicio_programada.substring(0,5) : '';
        document.getElementById('modal-hora-fin').value = act.hora_fin_programada ? act.hora_fin_programada.substring(0,5) : '';
        document.getElementById('modal-comentarios').value = act.comentarios || '';
        document.getElementById('modal-area').value = act.area || 'General';
        document.getElementById('modal-cliente').value = act.cliente || '';
        
        const userName = act.user ? act.user.name : '-';
        const supervisorName = (act.user && act.user.empleado && act.user.empleado.supervisor) ? act.user.empleado.supervisor.nombre : 'N/A';
        document.getElementById('modal-responsable').innerText = userName;
        document.getElementById('modal-supervisor').innerText = supervisorName;

        const inputsToLock = ['modal-activity-name', 'modal-fecha', 'modal-prioridad', 'modal-hora-inicio', 'modal-hora-fin', 'modal-area', 'modal-cliente'];
        inputsToLock.forEach(id => {
            const el = document.getElementById(id);
            if (!canEditAll) {
                el.readOnly = true; el.disabled = true;
                el.classList.add('bg-slate-100', 'text-slate-500', 'cursor-not-allowed');
                el.classList.remove('bg-white', 'focus:ring-indigo-500');
            } else {
                el.readOnly = false; el.disabled = false;
                el.classList.remove('bg-slate-100', 'text-slate-500', 'cursor-not-allowed');
                el.classList.add('bg-white', 'focus:ring-indigo-500');
            }
        });

        const divRechazo = document.getElementById('modal-rejection-alert');
        if (act.estatus === 'Rechazado') {
            divRechazo.classList.remove('hidden');
            document.getElementById('modal-rejection-reason').innerText = act.motivo_rechazo || 'Sin motivo';
            document.getElementById('div-estatus-selector').classList.add('hidden');
        } else {
            divRechazo.classList.add('hidden');
            document.getElementById('div-estatus-selector').classList.remove('hidden');
        }
        document.getElementById('notesModal').classList.remove('hidden');
    }
    
    function closeNotes() { document.getElementById('notesModal').classList.add('hidden'); }
    function rejectActivity(id) { const f=document.getElementById('rejectForm'); f.action="/activities/"+id+"/reject"; document.getElementById('rejectModal').classList.remove('hidden'); }
    
    function openPlanModal() { document.getElementById('planModal').classList.remove('hidden'); updateWeekLabels(); for(let i=0;i<5;i++){ const c=document.getElementById(`container-day-${i}`); if(c && c.children.length===0) addTaskCard(i); } }
    function updateWeekLabels() { const v=document.getElementById('weekPicker').value; if(!v)return; const l=new Date(v+'T00:00:00'); for(let i=0;i<5;i++){ const d=new Date(l); d.setDate(l.getDate()+i); const t=d.toLocaleDateString('es-MX',{day:'numeric',month:'short'}); const e=document.getElementById(`label-date-${i}`); if(e)e.innerText=t; } }
    
    function addTaskCard(dayIndex) {
        const container = document.getElementById(`container-day-${dayIndex}`);
        const cardIndex = container.children.length + Math.floor(Math.random() * 9999);
        const cardHTML = `
            <div class="bg-white p-3 rounded-xl shadow-sm border border-slate-200 group animate-fade-in-up relative transition-all hover:shadow-md hover:border-indigo-200">
                <div class="absolute -top-2 -right-2 opacity-0 group-hover:opacity-100 transition-opacity cursor-pointer z-10 bg-white text-slate-400 hover:text-red-500 rounded-full p-1 shadow-md border border-slate-100" onclick="this.parentElement.remove()" title="Quitar"><svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></div>
                <div class="space-y-2">
                    {{-- AREA POR DEFECTO PARA EL PLANIFICADOR (DINÁMICA O GENERAL) --}}
                    <input type="hidden" name="plan[${dayIndex}][${cardIndex}][area]" value="{{ $areasDisponibles->first() ?? 'General' }}">
                    <div class="flex items-center gap-1">
                        <div class="flex-1 flex items-center bg-slate-50 rounded-lg px-2 py-1 border border-slate-100 focus-within:border-indigo-300 focus-within:ring-1 focus-within:ring-indigo-100 transition"><svg class="w-3 h-3 text-slate-400 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg><input type="time" name="plan[${dayIndex}][${cardIndex}][start_time]" class="w-full text-[10px] font-bold border-0 bg-transparent p-0 focus:ring-0 text-slate-600 h-4 leading-none"></div>
                        <span class="text-slate-300 text-[10px]">-</span>
                        <div class="flex-1 flex items-center bg-slate-50 rounded-lg px-2 py-1 border border-slate-100 focus-within:border-indigo-300 focus-within:ring-1 focus-within:ring-indigo-100 transition"><input type="time" name="plan[${dayIndex}][${cardIndex}][end_time]" class="w-full text-[10px] font-bold border-0 bg-transparent p-0 focus:ring-0 text-slate-600 h-4 leading-none text-center"></div>
                    </div>
                    <div class="relative"><input type="text" name="plan[${dayIndex}][${cardIndex}][cliente]" class="w-full text-[10px] font-bold text-indigo-600 placeholder-indigo-300 border-0 border-b border-dashed border-slate-200 focus:border-indigo-500 focus:ring-0 px-0 py-1 bg-transparent transition" placeholder="+ Cliente"></div>
                    <textarea name="plan[${dayIndex}][${cardIndex}][actividad]" rows="2" class="w-full text-xs text-slate-700 font-medium border-0 bg-slate-50/50 rounded-lg p-2 focus:bg-white focus:ring-2 focus:ring-indigo-100 focus:shadow-sm resize-none transition placeholder-slate-400" placeholder="Actividad..." required></textarea>
                </div>
            </div>`;
        container.insertAdjacentHTML('beforeend', cardHTML);
    }

    function openHistory(id) {
        const s = document.getElementById('history-json-'+id); 
        const c = document.getElementById('history-container'); 
        const m = document.getElementById('historyModal');
        
        if(!c || !m || !s){ console.error("Error al cargar componentes de historial"); return; }
        
        try {
            const l = JSON.parse(s.textContent);
            if(!l || l.length === 0){ 
                c.innerHTML = `<div class="flex flex-col items-center justify-center py-12 opacity-50"><div class="bg-slate-100 p-4 rounded-full mb-3"><svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div><p class="text-sm font-medium text-slate-500">Aún no hay historial de cambios.</p></div>`; 
            } else {
                l.sort((a,b)=>new Date(b.created_at)-new Date(a.created_at));
                let h = '<div class="relative pl-4 border-l-2 border-slate-200 space-y-8 my-4">';
                l.forEach(x => {
                    const d = new Date(x.created_at);
                    const user = x.user ? x.user.name.split(' ')[0] : 'Sistema';
                    const det = x.details || x.action;
                    
                    let ic='<svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>'; 
                    let cl='text-amber-600'; let bg='bg-amber-100 ring-amber-50';

                    if(det.includes('Creó')){ ic='<svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>'; cl='text-indigo-600'; bg='bg-indigo-100 ring-indigo-50'; }
                    else if(det.includes('Estatus')||det.includes('aprobado')||det.includes('Inició')||det.includes('Completado')){ ic='<svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'; cl='text-emerald-600'; bg='bg-emerald-100 ring-emerald-50'; }
                    else if(det.includes('Rechazado')||det.includes('Rechazo')){ ic='<svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>'; cl='text-red-600'; bg='bg-red-100 ring-red-50'; }
                    else if(det.includes('Evidencia')||det.includes('archivo')){ ic='<svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>'; cl='text-blue-600'; bg='bg-blue-100 ring-blue-50'; }

                    h += `<div class="relative">
                            <span class="absolute -left-[1.35rem] top-1 flex items-center justify-center w-6 h-6 rounded-full ${bg} ring-4 ring-white ${cl}">${ic}</span>
                            <div class="flex flex-col gap-1">
                                <div class="flex justify-between items-baseline"><span class="text-xs font-bold text-slate-700">${user}</span><span class="text-[10px] text-slate-400 font-mono">${d.toLocaleDateString('es-MX',{day:'numeric',month:'short'})} • ${d.toLocaleTimeString('es-MX',{hour:'2-digit',minute:'2-digit'})}</span></div>
                                <div class="text-xs text-slate-600 bg-slate-50 p-2.5 rounded-lg border border-slate-100 shadow-sm leading-relaxed">${det}</div>
                            </div>
                          </div>`;
                });
                h += '</div>'; c.innerHTML = h;
            }
            m.classList.remove('hidden');
        } catch(e){ console.error(e); c.innerHTML='<p class="text-red-500 text-xs text-center">Error al leer datos.</p>'; }
    }
</script>

<style>
    .animate-fade-in-down { animation: fadeInDown 0.5s ease-out; }
    .animate-fade-in-up { animation: fadeInUp 0.3s ease-out; }
    @keyframes fadeInDown { from { opacity: 0; transform: translateY(-10px); } to { opacity: 1; transform: translateY(0); } }
    @keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
    .custom-scrollbar::-webkit-scrollbar { width: 6px; height: 6px; }
    .custom-scrollbar::-webkit-scrollbar-track { background: #f1f5f9; }
    .custom-scrollbar::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 3px; }
    .custom-scrollbar::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
</style>
@endsection