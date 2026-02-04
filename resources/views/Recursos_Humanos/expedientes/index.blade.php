@extends('layouts.erp')

@section('title', 'Expedientes Digitales')

@section('content')
<div class="min-h-screen bg-slate-50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 gap-4">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Expedientes del Personal</h1>
                <p class="text-sm text-slate-500">Monitor de cumplimiento documental y datos.</p>
            </div>
            
            {{-- Buscador --}}
            <form method="GET" class="flex gap-2 w-full sm:w-auto">
                <div class="relative w-full sm:w-64">
                    <input type="text" name="search" placeholder="Buscar por nombre, puesto..." value="{{ request('search') }}" class="w-full pl-4 pr-10 py-2 rounded-lg border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500 shadow-sm">
                    <button type="submit" class="absolute right-2 top-2 text-slate-400 hover:text-indigo-600">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </button>
                </div>
            </form>
        </div>

        {{-- Tabla --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Empleado</th>
                            <th class="px-6 py-4 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Puesto</th>
                            {{-- NUEVA COLUMNA DE BARRA DE PROGRESO --}}
                            <th class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase tracking-wider w-1/4">Nivel de Cumplimiento</th>
                            <th class="px-6 py-4 text-center text-xs font-bold text-slate-500 uppercase tracking-wider">Estado</th>
                            <th class="px-6 py-4 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @forelse($empleados as $empleado)
                            <tr class="hover:bg-slate-50 transition group">
                                
                                {{-- Empleado --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold shadow-sm ring-2 ring-white text-xs">
                                            {{ substr($empleado->nombre, 0, 1) }}{{ substr($empleado->apellido_paterno, 0, 1) }}
                                        </div>
                                        <div class="ml-4">
                                            <div class="text-sm font-bold text-slate-900">{{ $empleado->nombre }} {{ $empleado->apellido_paterno }}</div>
                                            <div class="text-xs text-slate-500">{{ $empleado->email }}</div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Puesto --}}
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm text-slate-900 font-medium">{{ $empleado->posicion }}</div>
                                    <div class="text-xs text-slate-500">{{ $empleado->departamento ?? 'General' }}</div>
                                </td>

                                {{-- BARRA DE PROGRESO (USANDO VARIABLE REAL) --}}
                                <td class="px-6 py-4 align-middle">
                                    <div class="w-full max-w-xs mx-auto">
                                        <div class="flex justify-between items-center mb-1">
                                            <span class="text-xs font-bold text-slate-700">{{ $empleado->porcentaje_expediente }}%</span>
                                            @if($empleado->porcentaje_expediente < 100)
                                                <span class="text-[10px] text-slate-400">Faltan datos</span>
                                            @else
                                                <span class="text-[10px] text-emerald-600 font-bold">Completo</span>
                                            @endif
                                        </div>
                                        <div class="w-full bg-slate-100 rounded-full h-2.5 shadow-inner overflow-hidden border border-slate-200">
                                            @php
                                                $barColor = 'bg-red-500';
                                                $p = $empleado->porcentaje_expediente;
                                                if($p == 100) $barColor = 'bg-emerald-500';
                                                elseif($p >= 80) $barColor = 'bg-indigo-500';
                                                elseif($p >= 50) $barColor = 'bg-amber-400';
                                            @endphp
                                            <div class="{{ $barColor }} h-2.5 rounded-full transition-all duration-700 ease-out relative" style="width: {{ $p }}%">
                                                <div class="absolute top-0 left-0 w-full h-full bg-white opacity-20"></div>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                {{-- Estatus (Badge Calculado en Controller) --}}
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    @if($empleado->doc_status == 'missing')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600 border border-slate-200">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-400"></span> Incompleto
                                        </span>
                                    @elseif($empleado->doc_status == 'expired')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-rose-50 text-rose-700 border border-rose-200 animate-pulse">
                                            ⚠️ {{ $empleado->doc_msg }}
                                        </span>
                                    @elseif($empleado->doc_status == 'warning')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                            🕒 {{ $empleado->doc_msg }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                            Vigente
                                        </span>
                                    @endif
                                </td>

                                {{-- Acciones --}}
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <a href="{{ route('rh.expedientes.show', $empleado->id) }}" class="text-indigo-600 hover:text-indigo-900 bg-indigo-50 hover:bg-indigo-100 border border-indigo-200 px-3 py-1.5 rounded-lg text-xs font-bold transition shadow-sm inline-flex items-center gap-1">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        Gestionar
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center text-slate-500">
                                    <div class="flex flex-col items-center justify-center">
                                        <svg class="w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                        <p class="font-medium">No se encontraron empleados.</p>
                                    </div>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            {{-- Paginación --}}
            <div class="px-6 py-4 border-t border-slate-200 bg-slate-50">
                {{ $empleados->links() }}
            </div>
        </div>
    </div>
</div>
@endsection