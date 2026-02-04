@extends('layouts.erp')

@section('content')
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-slate-800 leading-tight tracking-tight">
                    {{ __('Evaluación de Desempeño') }}
                </h2>
                <p class="text-xs text-slate-500 mt-1">Gestión del talento y medición de competencias por área.</p>
            </div>
            
            <div class="flex items-center gap-2 bg-white p-2 rounded-lg shadow-sm border border-slate-200">
                <span class="text-xs font-bold text-slate-500 uppercase px-2">Periodo:</span>
                <form method="GET" action="{{ route('rh.evaluacion.index') }}">
                    @if(request('area'))
                        <input type="hidden" name="area" value="{{ request('area') }}">
                    @endif
                    <select name="periodo" onchange="this.form.submit()" class="text-sm border-none bg-slate-50 rounded-md focus:ring-indigo-500 text-slate-700 font-semibold cursor-pointer py-1 pl-3 pr-8">
                        @foreach($periodos as $periodoOption)
                            <option value="{{ $periodoOption }}" {{ $selectedPeriod == $periodoOption ? 'selected' : '' }}>
                                {{ $periodoOption }}
                            </option>
                        @endforeach
                    </select>
                </form>
            </div>
        </div>
    </x-slot>

    @php
        $categoriasPrincipales = ['Logistica', 'Legal', 'Anexo 24', 'Auditoria', 'TI'];
        $todosLosPuestos = $empleados->pluck('posicion')->unique()->values()->toArray();
        $todasLasCategorias = array_unique(array_merge($categoriasPrincipales, $todosLosPuestos));
    @endphp

    <div class="py-12 bg-slate-50 min-h-screen" x-data="{ activeTab: 'Logistica' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            {{-- BANNER DE ESTADO DEL PERIODO --}}
            @if(!$isWindowOpen)
                <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r shadow-sm">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg leading-6 font-bold text-amber-800">Periodo de Evaluaciones Cerrado</h3>
                            <p class="text-sm text-amber-700 mt-1">
                                Las evaluaciones solo se pueden crear o editar durante los <strong>últimos 10 días</strong> del semestre. Actualmente modo <strong>solo lectura</strong>.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex items-center justify-end px-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                    Evaluando: {{ $selectedPeriod }}
                </span>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-2">
                <nav class="flex space-x-1 overflow-x-auto custom-scrollbar pb-2 md:pb-0" aria-label="Tabs">
                    @foreach($todasLasCategorias as $categoria)
                        @if(!empty($categoria))
                            <button 
                                @click="activeTab = '{{ $categoria }}'"
                                :class="activeTab === '{{ $categoria }}' ? 'bg-indigo-50 text-indigo-700 shadow-sm ring-1 ring-indigo-200' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'"
                                class="whitespace-nowrap px-5 py-2.5 rounded-xl font-semibold text-sm transition-all duration-200 ease-in-out flex-shrink-0">
                                {{ $categoria }}
                            </button>
                        @endif
                    @endforeach
                </nav>
            </div>

            <div class="space-y-6">
                @foreach($todasLasCategorias as $categoria)
                    @if(!empty($categoria))
                        <div x-show="activeTab === '{{ $categoria }}'" style="display: none;">
                            <div class="flex items-center justify-between mb-6 px-2">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-indigo-100 text-indigo-600 rounded-lg">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    </div>
                                    <h4 class="text-xl font-bold text-slate-800">{{ $categoria }}</h4>
                                </div>
                                <span class="bg-white text-slate-600 text-xs font-bold px-3 py-1 rounded-full border border-slate-200 shadow-sm">
                                    {{ $empleados->filter(fn($e) => str_contains($e->posicion, $categoria) || $e->posicion == $categoria)->count() }} Miembros
                                </span>
                            </div>

                            @php $empleadosCategoria = $empleados->filter(fn($e) => $e->posicion === $categoria || str_contains($e->posicion, $categoria)); @endphp

                            @if($empleadosCategoria->isEmpty())
                                <div class="flex flex-col items-center justify-center py-16 bg-white rounded-3xl border border-dashed border-slate-300">
                                    <h3 class="text-slate-900 font-semibold">Sin colaboradores asignados</h3>
                                </div>
                            @else
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                                    @foreach($empleadosCategoria as $empleado)
                                        <div class="group bg-white rounded-2xl p-5 border border-slate-200 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                                            <div class="relative z-10 flex flex-col h-full">
                                                <div class="flex items-start justify-between mb-4">
                                                    <div class="h-14 w-14 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xl shadow-sm overflow-hidden">
                                                        @if(isset($empleado->foto_path) && $empleado->foto_path)
                                                            <img src="{{ asset('storage/' . $empleado->foto_path) }}" class="w-full h-full object-cover">
                                                        @else
                                                            {{ substr($empleado->nombre, 0, 1) }}
                                                        @endif
                                                    </div>
                                                    @if(isset($empleado->evaluacion_actual))
                                                        <span class="inline-flex items-center px-2 py-1 rounded-md text-[10px] font-bold {{ $empleado->evaluacion_actual->edit_count >= 1 ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                                            {{ $empleado->evaluacion_actual->edit_count >= 1 ? 'FINALIZADA' : 'EN REVISIÓN' }}
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2 py-1 rounded-md text-[10px] font-bold bg-slate-50 text-slate-500">PENDIENTE</span>
                                                    @endif
                                                </div>

                                                <div class="flex-1">
                                                    <h5 class="text-lg font-bold text-slate-800 truncate">{{ $empleado->nombre }}</h5>
                                                    <p class="text-xs text-slate-500 font-medium uppercase truncate">{{ $empleado->apellido_paterno }}</p>
                                                    <div class="mt-3 flex items-center text-xs text-slate-500">
                                                        <span class="truncate">{{ $empleado->posicion }}</span>
                                                    </div>
                                                </div>

                                                <div class="mt-5 pt-4 border-t border-slate-100">
                                                    @if(!$isWindowOpen)
                                                        @if(isset($empleado->evaluacion_actual))
                                                            <a href="{{ route('rh.evaluacion.show', ['id' => $empleado->id, 'periodo' => $selectedPeriod]) }}" class="flex items-center justify-center w-full px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-600 text-xs font-bold uppercase tracking-wider rounded-lg transition-colors">
                                                                Ver Evaluación (Cerrado)
                                                            </a>
                                                        @else
                                                            <button disabled class="flex items-center justify-center w-full px-4 py-2 bg-slate-100 text-slate-400 text-xs font-bold uppercase tracking-wider rounded-lg cursor-not-allowed">
                                                                Fuera de Fecha
                                                            </button>
                                                        @endif
                                                    @else
                                                        @if(isset($empleado->evaluacion_actual))
                                                            @if($empleado->evaluacion_actual->edit_count >= 1)
                                                                <a href="{{ route('rh.evaluacion.show', ['id' => $empleado->id, 'periodo' => $selectedPeriod]) }}" class="flex items-center justify-center w-full px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold uppercase tracking-wider rounded-lg transition-colors">
                                                                    Ver Resultados
                                                                </a>
                                                            @else
                                                                <a href="{{ route('rh.evaluacion.show', ['id' => $empleado->id, 'periodo' => $selectedPeriod]) }}" class="flex items-center justify-center w-full px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold uppercase tracking-wider rounded-lg transition-colors">
                                                                    Editar Evaluación
                                                                </a>
                                                            @endif
                                                        @else
                                                            <div class="mt-4 flex gap-2">
                                                                <a href="{{ route('rh.evaluacion.show', ['id' => $empleado->id, 'periodo' => $selectedPeriod]) }}" 
                                                                class="flex-1 text-center px-4 py-2 bg-indigo-50 text-indigo-700 rounded-lg text-sm font-bold hover:bg-indigo-100 transition">
                                                                Evaluar
                                                                </a>

                                                                @if(isset($hasFullVisibility) && $hasFullVisibility)
                                                                    <a href="{{ route('rh.evaluacion.resultados', ['id' => $empleado->id, 'periodo' => $selectedPeriod]) }}" 
                                                                    class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition" 
                                                                    title="Ver Resultados Consolidados">
                                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                                                    </a>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>
@endsection