@extends('layouts.erp')

@section('title', 'Días Festivos e Inhábiles')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Header --}}
    <div class="mb-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-900">Días Festivos e Inhábiles</h1>
                <p class="text-slate-500 mt-1">Gestiona los días festivos y fechas inhábiles de la empresa</p>
            </div>
            <a href="{{ route('rh.dias-festivos.create') }}" 
               class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl font-bold text-sm transition-all shadow-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo Día Festivo
            </a>
        </div>
    </div>

    {{-- Estadísticas --}}
    <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-8">
        <div class="bg-white rounded-xl p-4 shadow-sm border border-slate-200">
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Total</p>
            <p class="text-2xl font-bold text-slate-900 mt-1">{{ $estadisticas['total'] }}</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-slate-200">
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Activos</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $estadisticas['activos'] }}</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-slate-200">
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Festivos</p>
            <p class="text-2xl font-bold text-indigo-600 mt-1">{{ $estadisticas['festivos'] }}</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-slate-200">
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Inhábiles</p>
            <p class="text-2xl font-bold text-amber-600 mt-1">{{ $estadisticas['inhábiles'] }}</p>
        </div>
        <div class="bg-white rounded-xl p-4 shadow-sm border border-slate-200">
            <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Próximos 30 días</p>
            <p class="text-2xl font-bold text-blue-600 mt-1">{{ $estadisticas['proximos'] }}</p>
        </div>
    </div>

    {{-- Filtros --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-4 mb-6">
        <form method="GET" class="flex flex-col sm:flex-row gap-4">
            <div class="flex-1">
                <label class="block text-xs font-bold text-slate-600 mb-1 uppercase tracking-wide">Tipo</label>
                <select name="tipo" class="w-full rounded-xl border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="todos" {{ $filtros['tipo'] === 'todos' ? 'selected' : '' }}>Todos</option>
                    <option value="festivo" {{ $filtros['tipo'] === 'festivo' ? 'selected' : '' }}>Día Festivo</option>
                    <option value="inhabil" {{ $filtros['tipo'] === 'inhabil' ? 'selected' : '' }}>Día Inhábil</option>
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-xs font-bold text-slate-600 mb-1 uppercase tracking-wide">Estado</label>
                <select name="estado" class="w-full rounded-xl border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                    <option value="todos" {{ $filtros['estado'] === 'todos' ? 'selected' : '' }}>Todos</option>
                    <option value="activos" {{ $filtros['estado'] === 'activos' ? 'selected' : '' }}>Activos</option>
                    <option value="inactivos" {{ $filtros['estado'] === 'inactivos' ? 'selected' : '' }}>Inactivos</option>
                    <option value="proximos" {{ $filtros['estado'] === 'proximos' ? 'selected' : '' }}>Próximos 30 días</option>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" class="px-4 py-2 bg-slate-800 text-white rounded-xl text-sm font-medium hover:bg-slate-700 transition">
                    Filtrar
                </button>
            </div>
        </form>
    </div>

    {{-- Lista de Días Festivos --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 overflow-hidden">
        @if($diasFestivos->isEmpty())
            <div class="p-12 text-center">
                <div class="mx-auto w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mb-4">
                    <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <h3 class="text-lg font-bold text-slate-700 mb-2">No hay días festivos</h3>
                <p class="text-slate-500 mb-4">Comienza agregando días festivos o inhábiles.</p>
                <a href="{{ route('rh.dias-festivos.create') }}" class="inline-flex items-center gap-2 text-indigo-600 hover:text-indigo-800 font-medium">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Agregar día festivo
                </a>
            </div>
        @else
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Fecha</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Tipo</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Recurrente</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-600 uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-slate-600 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-slate-200">
                    @foreach($diasFestivos as $dia)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="font-medium text-slate-900">{{ $dia->nombre }}</div>
                            @if($dia->descripcion)
                                <div class="text-sm text-slate-500 mt-0.5 line-clamp-1">{{ $dia->descripcion }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <span class="text-sm text-slate-900">{{ $dia->fecha_formateada }}</span>
                        </td>
                        <td class="px-6 py-4">
                            @if($dia->tipo === 'festivo')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                    Festivo
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                    Inhábil
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($dia->es_anual)
                                <span class="inline-flex items-center gap-1 text-xs text-emerald-600 font-medium">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                    Anual
                                </span>
                            @else
                                <span class="text-xs text-slate-400">Una vez</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            @if($dia->activo)
                                <span class="inline-flex items-center gap-1 text-xs text-emerald-600 font-medium">
                                    <span class="w-2 h-2 bg-emerald-500 rounded-full"></span>
                                    Activo
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 text-xs text-slate-400 font-medium">
                                    <span class="w-2 h-2 bg-slate-300 rounded-full"></span>
                                    Inactivo
                                </span>
                            @endif
                            @if($dia->notificacion_enviada)
                                <span class="ml-2 inline-flex items-center gap-1 text-xs text-blue-600 font-medium" title="Notificado el {{ $dia->notificacion_enviada_at?->format('d/m/Y H:i') }}">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                    </svg>
                                    Notif.
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-1">
                                <a href="{{ route('rh.dias-festivos.edit', $dia->id) }}" 
                                   class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition" title="Editar">
                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                    </svg>
                                </a>
                                @if(!$dia->notificacion_enviada)
                                    <form action="{{ route('rh.dias-festivos.enviar-notificacion', $dia->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit" 
                                                class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition"
                                                title="Enviar notificación a empleados"
                                                onclick="return confirm('¿Enviar notificación a todos los empleados?')">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                            </svg>
                                        </button>
                                    </form>
                                @else
                                    <span class="p-2 text-slate-300 cursor-not-allowed" title="Yaenviada">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                    </span>
                                @endif
                                <form action="{{ route('rh.dias-festivos.toggle', $dia->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" 
                                            class="p-2 {{ $dia->activo ? 'text-amber-600 hover:bg-amber-50' : 'text-emerald-600 hover:bg-emerald-50' }} rounded-lg transition"
                                            title="{{ $dia->activo ? 'Desactivar' : 'Activar' }}">
                                        @if($dia->activo)
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                                            </svg>
                                        @else
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                            </svg>
                                        @endif
                                    </button>
                                </form>
                                <form action="{{ route('rh.dias-festivos.destroy', $dia->id) }}" method="POST" class="inline">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition"
                                            title="Eliminar"
                                            onclick="return confirm('¿Estás seguro de eliminar este día festivo?')">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
            
            <div class="px-6 py-4 border-t border-slate-200">
                {{ $diasFestivos->withQueryString()->links() }}
            </div>
        @endif
    </div>
</div>
@endsection