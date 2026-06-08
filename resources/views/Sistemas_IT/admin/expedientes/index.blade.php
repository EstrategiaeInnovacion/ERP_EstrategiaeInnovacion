@extends('layouts.master')

@section('title', 'Expedientes de Mantenimiento')

@section('content')
<div class="min-h-screen bg-slate-50 pb-12">

    {{-- Header --}}
    <div class="bg-white border-b border-slate-200 mb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900">Expedientes de Mantenimiento</h1>
                    <p class="text-slate-500 mt-1 text-sm">Hoja de vida por equipo — historial completo de mantenimientos.</p>
                </div>
                <a href="{{ route('admin.maintenance.index') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 text-slate-600 text-sm font-semibold rounded-xl hover:bg-slate-50 hover:border-slate-300 transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Panel de Mantenimiento
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6 flex items-center gap-3">
            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-sm text-green-800 font-medium">{{ session('success') }}</span>
        </div>
        @endif

        {{-- Filtros --}}
        <form method="GET" class="bg-white border border-slate-200 rounded-xl shadow-sm p-4 mb-6 flex flex-wrap gap-3 items-end">
            <div class="flex-1 min-w-48">
                <label class="block text-xs font-semibold text-slate-500 mb-1">Buscar equipo</label>
                <input type="text" name="q" value="{{ request('q') }}"
                       placeholder="Identificador, marca, modelo…"
                       class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>
            <div class="min-w-40">
                <label class="block text-xs font-semibold text-slate-500 mb-1">Estado</label>
                <select name="estado" class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="">Todos</option>
                    <option value="activo" @selected(request('estado') === 'activo')>Activo</option>
                    <option value="en_reparacion" @selected(request('estado') === 'en_reparacion')>En Reparación</option>
                    <option value="retirado" @selected(request('estado') === 'retirado')>Retirado</option>
                    <option value="renovado" @selected(request('estado') === 'renovado')>Renovado</option>
                </select>
            </div>
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition">
                Buscar
            </button>
            @if(request('q') || request('estado'))
            <a href="{{ route('admin.expedientes.index') }}"
               class="px-4 py-2 bg-slate-100 text-slate-600 text-sm font-semibold rounded-lg hover:bg-slate-200 transition">
                Limpiar
            </a>
            @endif
        </form>

        {{-- Tabla --}}
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
            @if($expedientes->isEmpty())
            <div class="py-16 text-center">
                <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="text-slate-500 text-sm">No hay expedientes que coincidan con los filtros.</p>
                <p class="text-slate-400 text-xs mt-1">Los expedientes se crean automáticamente al registrar el perfil del equipo.</p>
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Equipo</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Usuario actual</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Estado</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Apertura</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Último mant.</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wide">Próximo mant.</th>
                            <th class="px-4 py-3"></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($expedientes as $exp)
                        @php
                            $ea      = $exp->equipoAsignado;
                            $usuario = $ea?->user;
                        @endphp
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-4 py-3">
                                <div class="font-semibold text-slate-900 text-sm">{{ $ea->nombre_equipo ?? '—' }}</div>
                                <div class="text-xs text-slate-500">{{ $ea->modelo ?? '—' }} &nbsp;·&nbsp; {{ $ea->numero_serie ?? '' }}</div>
                            </td>
                            <td class="px-4 py-3">
                                @if($usuario)
                                <div class="text-sm text-slate-800">{{ $usuario->name }}</div>
                                <div class="text-xs text-slate-500">{{ $usuario->empleado?->area ?? '—' }}</div>
                                @else
                                <span class="text-xs text-slate-400">Sin asignar</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold {{ $exp->estado_badge }}">
                                    {{ $exp->estado_label }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ $exp->fecha_apertura->format('d/m/Y') }}
                            </td>
                            <td class="px-4 py-3 text-sm text-slate-600">
                                {{ $ea?->last_maintenance_at ? $ea->last_maintenance_at->format('d/m/Y') : '—' }}
                            </td>
                            <td class="px-4 py-3">
                                @if($ea?->next_maintenance_at)
                                    @php $vence = $ea->next_maintenance_at->isPast(); @endphp
                                    <span class="text-sm {{ $vence ? 'text-red-600 font-semibold' : 'text-slate-600' }}">
                                        {{ $ea->next_maintenance_at->format('d/m/Y') }}
                                        @if($vence) <span class="text-xs">(vencido)</span> @endif
                                    </span>
                                @else
                                    <span class="text-xs text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a href="{{ route('admin.expedientes.show', $exp) }}"
                                   class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-50 text-blue-700 text-xs font-semibold rounded-lg hover:bg-blue-100 transition">
                                    Ver expediente
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if($expedientes->hasPages())
            <div class="border-t border-slate-100 px-4 py-4">
                {{ $expedientes->links() }}
            </div>
            @endif
            @endif
        </div>

    </div>
</div>
@endsection
