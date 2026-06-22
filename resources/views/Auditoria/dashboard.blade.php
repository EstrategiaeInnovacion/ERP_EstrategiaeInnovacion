@extends('layouts.master')
@section('title', 'Panel Auditoría')
 
@section('content')
<div class="min-h-screen bg-slate-50/50 pb-12" x-data="{ openCreateModal: false }">
 
    {{-- ENCABEZADO --}}
    <div class="bg-white border-b border-slate-200/80 mb-8 shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <p class="text-xs font-bold text-indigo-600 uppercase tracking-widest mb-1 flex items-center gap-2">
                        <span class="w-2 h-2 bg-indigo-500 rounded-full animate-pulse"></span>
                        Panel Corporativo
                    </p>
                    <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">Auditoría de Comercio Exterior</h1>
                    <p class="text-slate-500 mt-1 text-sm">Control y seguimiento de matrices de cumplimiento por cliente y periodos fiscales.</p>
                </div>
                
                <div class="flex flex-wrap gap-3 w-full md:w-auto">
                    @if($esCoordinador)
                        <button @click="openCreateModal = true"
                                class="inline-flex items-center justify-center px-4 py-2.5 bg-gradient-to-r from-indigo-600 to-violet-600 text-white font-bold text-sm rounded-xl hover:from-indigo-700 hover:to-violet-700 active:scale-95 transition-all shadow-md shadow-indigo-100 w-full sm:w-auto">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                            </svg>
                            Nuevo Proyecto
                        </button>
                    @endif
                    
                    <a href="{{ route('welcome') }}"
                       class="inline-flex items-center justify-center px-4 py-2.5 bg-white border border-slate-200 text-slate-600 font-bold text-sm rounded-xl hover:bg-slate-50 hover:text-indigo-600 hover:border-indigo-200 transition-all shadow-sm w-full sm:w-auto">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Volver al Portal
                    </a>
                </div>
            </div>
        </div>
    </div>
 
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-6 p-4 rounded-xl border bg-emerald-50/50 border-emerald-200/80 text-emerald-800 flex items-center gap-3 animate-fade-in-up">
                <div class="p-1.5 bg-emerald-100 text-emerald-600 rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
        @endif
 
        {{-- CARDS METRICAS --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5 mb-8">
            <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm flex items-center gap-4 hover:shadow-md transition-shadow">
                <div class="p-3 bg-indigo-50 text-indigo-600 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">Total Proyectos</p>
                    <h3 class="text-2xl font-extrabold text-slate-800 mt-0.5">{{ $totalProyectos }}</h3>
                </div>
            </div>
 
            <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm flex items-center gap-4 hover:shadow-md transition-shadow">
                <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">En Proceso</p>
                    <h3 class="text-2xl font-extrabold text-slate-800 mt-0.5">{{ $proyectosEnProceso }}</h3>
                </div>
            </div>
 
            <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm flex items-center gap-4 hover:shadow-md transition-shadow">
                <div class="p-3 bg-amber-50 text-amber-600 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">Retrasados</p>
                    <h3 class="text-2xl font-extrabold text-slate-800 mt-0.5">{{ $proyectosRetrasados }}</h3>
                </div>
            </div>
 
            <div class="bg-white p-5 rounded-2xl border border-slate-200/60 shadow-sm flex items-center gap-4 hover:shadow-md transition-shadow">
                <div class="p-3 bg-slate-100 text-slate-600 rounded-xl">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">Cerrados</p>
                    <h3 class="text-2xl font-extrabold text-slate-800 mt-0.5">{{ $proyectosCerrados }}</h3>
                </div>
            </div>
        </div>
 
        {{-- FILTROS DE CLIENTE --}}
        <div class="bg-white p-4 rounded-2xl border border-slate-200/60 shadow-sm mb-8">
            <form action="{{ route('auditoria.dashboard') }}" method="GET" class="flex flex-col sm:flex-row items-end gap-4">
                <div class="flex-1 w-full">
                    <label for="cliente_id" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Seleccionar Cliente</label>
                    <select name="cliente_id" id="cliente_id"
                            class="w-full text-sm border border-slate-200 rounded-xl bg-slate-50/50 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:bg-white transition"
                            onchange="this.form.submit()">
                        <option value="">Todos los clientes...</option>
                        @foreach($clientesConProyectos as $nombreCliente)
                            <option value="{{ $nombreCliente }}" {{ request('cliente_id') == $nombreCliente ? 'selected' : '' }}>{{ $nombreCliente }}</option>
                        @endforeach
                    </select>
                </div>
 
                @if(request('cliente_id'))
                    <a href="{{ route('auditoria.dashboard') }}"
                       class="px-4 py-2.5 bg-slate-100 text-slate-600 font-semibold rounded-xl text-sm hover:bg-slate-200 active:scale-95 transition-all text-center w-full sm:w-auto">
                        Limpiar Filtro
                    </a>
                @endif
            </form>
        </div>
 
        {{-- LISTADO DE PROYECTOS --}}
        @if($proyectos->isEmpty())
            <div class="bg-white border border-slate-200/60 rounded-3xl p-16 text-center shadow-sm">
                <div class="w-16 h-16 bg-slate-50 rounded-2xl flex items-center justify-center text-slate-400 mx-auto mb-4 border border-slate-100 shadow-inner">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-800">No se encontraron proyectos</h3>
                <p class="text-slate-400 text-sm mt-1 max-w-sm mx-auto">No hay proyectos de auditoría registrados para la selección de filtros actual.</p>
                @if($esCoordinador)
                    <button @click="openCreateModal = true"
                            class="mt-5 inline-flex items-center justify-center px-4 py-2 bg-indigo-600 text-white font-bold text-sm rounded-xl hover:bg-indigo-700 active:scale-95 transition-all shadow-md">
                        Crear tu primer proyecto
                    </button>
                @endif
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($proyectos as $p)
                    <div class="group bg-white rounded-3xl border border-slate-200/75 shadow-sm hover:shadow-lg hover:border-indigo-200/80 transition-all duration-300 flex flex-col justify-between overflow-hidden relative">
                        {{-- Indicador visual superior según el estatus (calculado dinámicamente) --}}
                        @php
                            $avance = round($p->porcentaje_general_aprobado);
                            if ($avance >= 100) {
                                $estatusCalc = 'cerrado';
                                $badgeColors = ['bg' => 'bg-indigo-50', 'text' => 'text-indigo-700', 'border' => 'border-indigo-200', 'color' => 'from-indigo-600 to-violet-600'];
                            } elseif ($p->fecha_entrega_estimada && $p->fecha_entrega_estimada->isPast()) {
                                $estatusCalc = 'retrasado';
                                $badgeColors = ['bg' => 'bg-rose-50', 'text' => 'text-rose-700', 'border' => 'border-rose-200', 'color' => 'from-rose-500 to-rose-600'];
                            } else {
                                $estatusCalc = 'en proceso';
                                $badgeColors = ['bg' => 'bg-emerald-50', 'text' => 'text-emerald-700', 'border' => 'border-emerald-200', 'color' => 'from-emerald-500 to-emerald-600'];
                            }
                        @endphp
                        
                        <div class="h-1.5 w-full bg-gradient-to-r {{ $badgeColors['color'] }}"></div>
                        
                        <div class="p-6">
                            {{-- Cabecera de la card --}}
                            <div class="flex items-start justify-between gap-3 mb-4">
                                <span class="px-2.5 py-1 rounded-lg text-xs font-bold uppercase tracking-wider {{ $badgeColors['bg'] }} {{ $badgeColors['text'] }} border {{ $badgeColors['border'] }}">
                                    {{ $estatusCalc }}
                                </span>
                                <span class="text-xs text-slate-400 font-bold bg-slate-50 border border-slate-100 py-1 px-2.5 rounded-lg flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    Periodo {{ $p->periodo_fiscal }}
                                </span>
                            </div>
 
                            {{-- Info del cliente --}}
                            <h2 class="text-xl font-bold text-slate-900 group-hover:text-indigo-700 transition-colors line-clamp-1 mb-1">{{ $p->nombre_cliente }}</h2>
                            <p class="text-slate-400 text-xs font-medium flex items-center gap-1 mb-5">
                                <svg class="w-3 h-3 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/>
                                </svg>
                                Analista: {{ $p->analista->name }}
                            </p>
 
                            {{-- Línea visual de Fases simplificada --}}
                            <div class="space-y-1 mb-5">
                                <div class="flex justify-between text-xs font-semibold">
                                    <span class="text-slate-500">Fase actual ({{ $p->fase_actual }}/8)</span>
                                    <span class="text-slate-800 font-bold line-clamp-1 max-w-[170px]">{{ $p->fases_config[$p->fase_actual - 1] ?? 'Sin configurar' }}</span>
                                </div>
                                <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden flex gap-0.5">
                                    @for($i = 1; $i <= 8; $i++)
                                        <div class="flex-1 h-full rounded-full transition-colors {{ $i <= $p->fase_actual ? 'bg-indigo-600' : 'bg-slate-200' }}"></div>
                                    @endfor
                                </div>
                            </div>
 
                            {{-- Porcentaje e info de avance --}}
                            <div class="bg-slate-50 border border-slate-100/60 p-3.5 rounded-2xl space-y-3">
                                <div class="flex justify-between items-center text-xs">
                                    <span class="text-slate-500 font-medium flex items-center gap-1">
                                        <span class="w-2 h-2 bg-indigo-500 rounded-full"></span>
                                        Avance oficial (Aprobado)
                                    </span>
                                    <span class="font-extrabold text-slate-800">{{ round($p->porcentaje_general_aprobado) }}%</span>
                                </div>
                                <div class="w-full bg-slate-200/60 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-indigo-500 h-full rounded-full" style="width: {{ $p->porcentaje_general_aprobado }}%"></div>
                                </div>
                                
                                @if(round($p->porcentaje_general_aprobado) != round($p->porcentaje_general_interno))
                                    <div class="flex justify-between items-center text-[11px] pt-1.5 border-t border-slate-200/50">
                                        <span class="text-slate-400 font-medium flex items-center gap-1">
                                            <span class="w-2 h-2 bg-violet-400 rounded-full animate-pulse"></span>
                                            Avance interno (Borrador/Pendiente)
                                        </span>
                                        <span class="font-bold text-slate-600">{{ round($p->porcentaje_general_interno) }}%</span>
                                    </div>
                                @endif
                            </div>
                        </div>
 
                        {{-- Botón de acción --}}
                        <div class="p-6 pt-0 mt-auto border-t border-slate-50 bg-slate-50/20">
                            <a href="{{ route('auditoria.proyectos.show', $p->id) }}"
                               class="w-full inline-flex items-center justify-center px-4 py-3 bg-white border border-slate-200 text-slate-700 font-bold text-sm rounded-xl hover:bg-indigo-50 hover:text-indigo-700 hover:border-indigo-200 transition shadow-sm group-hover:shadow-md">
                                Ver Matriz Detalle
                                <svg class="ml-2 h-4 w-4 transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                                </svg>
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
 
    {{-- MODAL: NUEVO PROYECTO (COORDINADOR) --}}
    @if($esCoordinador)
        <div id="modal-create" 
             x-show="openCreateModal" 
             class="fixed inset-0 z-50 overflow-y-auto" 
             x-cloak
             aria-labelledby="modal-title" 
             role="dialog" 
             aria-modal="true">
            <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
                {{-- Fondo oscuro difuminado --}}
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" 
                     x-show="openCreateModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     @click="openCreateModal = false"></div>
 
                {{-- Contenido del modal --}}
                <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg animate-scale-up"
                     x-show="openCreateModal"
                     x-transition:enter="ease-out duration-300"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="ease-in duration-200"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                    
                    <form action="{{ route('auditoria.proyectos.store') }}" method="POST">
                        @csrf
                        <div class="bg-white px-6 py-6 border-b border-slate-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-bold text-slate-900" id="modal-title">Iniciar Nuevo Proyecto</h3>
                                <p class="text-xs text-slate-400 mt-0.5">Define los parámetros básicos para iniciar el seguimiento.</p>
                            </div>
                            <button type="button" @click="openCreateModal = false" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
 
                        <div class="bg-white px-6 py-6 space-y-4">
                            {{-- Cliente --}}
                            <div>
                                <label for="cliente_nombre" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Cliente</label>
                                <input type="text" name="cliente_nombre" id="cliente_nombre" required placeholder="Escribe el nombre del cliente..."
                                       class="w-full text-sm border border-slate-200 rounded-xl bg-slate-50/50 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:bg-white transition">
                            </div>
 
                            {{-- Periodo Fiscal y Cantidad de Expedientes --}}
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="periodo_fiscal" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Periodo Fiscal</label>
                                    <input type="text" name="periodo_fiscal" id="periodo_fiscal" required placeholder="Ej: 2025"
                                           class="w-full text-sm border border-slate-200 rounded-xl bg-slate-50/50 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:bg-white transition">
                                </div>
                                <div>
                                    <label for="cantidad_expedientes" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Cantidad Expedientes</label>
                                    <input type="number" name="cantidad_expedientes" id="cantidad_expedientes" required min="1" placeholder="Ej: 150"
                                           class="w-full text-sm border border-slate-200 rounded-xl bg-slate-50/50 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:bg-white transition">
                                </div>
                            </div>
 
                            {{-- Analista --}}
                            <div>
                                <label for="analista_id" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Analista Responsable</label>
                                <select name="analista_id" id="analista_id" required
                                        class="w-full text-sm border border-slate-200 rounded-xl bg-slate-50/50 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:bg-white transition">
                                    <option value="" disabled selected>Selecciona un responsable...</option>
                                    @foreach($analistas as $a)
                                        <option value="{{ $a->id }}">{{ $a->name }}</option>
                                    @endforeach
                                </select>
                            </div>
 
                            {{-- Fechas --}}
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="fecha_inicio" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Fecha Inicio</label>
                                    <input type="date" name="fecha_inicio" id="fecha_inicio" required
                                           class="w-full text-sm border border-slate-200 rounded-xl bg-slate-50/50 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:bg-white transition">
                                </div>
                                <div>
                                    <label for="fecha_entrega_estimada" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Entrega Estimada</label>
                                    <input type="date" name="fecha_entrega_estimada" id="fecha_entrega_estimada" required
                                           class="w-full text-sm border border-slate-200 rounded-xl bg-slate-50/50 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:bg-white transition">
                                </div>
                            </div>
                        </div>
 
                        <div class="bg-slate-50 px-6 py-4 flex justify-end gap-3 border-t border-slate-100 rounded-b-3xl">
                            <button type="button" @click="openCreateModal = false"
                                    class="px-4 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="px-5 py-2.5 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-md shadow-indigo-100 transition active:scale-95">
                                Crear Proyecto
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
 
</div>
@endsection
