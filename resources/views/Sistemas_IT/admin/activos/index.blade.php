@extends('layouts.master')

@section('title', 'Activos IT')

@section('content')
<div class="min-h-screen bg-slate-50 pb-12">

    {{-- Header --}}
    <div class="bg-white border-b border-slate-200 mb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <a href="{{ ($soloLectura ?? false) ? route('recursos-humanos.index') : route('admin.dashboard') }}"
                           class="text-slate-400 hover:text-indigo-600 transition-colors text-sm font-medium">
                            {{ ($soloLectura ?? false) ? 'Panel RH' : 'Panel Admin' }}
                        </a>
                        <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <span class="text-slate-600 text-sm font-medium">Activos IT</span>
                    </div>
                    <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Activos IT</h1>
                    <p class="text-slate-500 mt-1">Inventario completo de equipos y dispositivos de la organización.</p>
                </div>
                @unless($soloLectura ?? false)
                <a href="{{ route('admin.activos.create') }}"
                   class="inline-flex items-center px-5 py-2.5 bg-amber-600 text-white font-bold text-sm rounded-xl hover:bg-amber-700 transition shadow-lg shadow-amber-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    Nuevo dispositivo
                </a>
                @endunless
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Error de conexión --}}
        @if(isset($noConexion) && $noConexion)
            <div class="bg-red-50 border border-red-200 rounded-2xl p-6 text-center mb-8">
                <svg class="mx-auto h-10 w-10 text-red-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.96-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                </svg>
                <p class="text-red-700 font-semibold">No se pudo conectar a la base de datos de activos.</p>
                <p class="text-red-500 text-sm mt-1">Verifica las variables <code class="bg-red-100 px-1 rounded">DB_ACTIVOS_*</code> en el archivo <code class="bg-red-100 px-1 rounded">.env</code>.</p>
            </div>
        @else

        {{-- Tarjetas de estadísticas --}}
        @if($stats)
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
            <div class="bg-white rounded-2xl border border-slate-200 p-4 text-center shadow-sm">
                <p class="text-2xl font-bold text-slate-900">{{ $stats['total'] }}</p>
                <p class="text-xs text-slate-500 mt-1 font-medium">Total</p>
            </div>
            <div class="bg-emerald-50 rounded-2xl border border-emerald-200 p-4 text-center shadow-sm">
                <p class="text-2xl font-bold text-emerald-700">{{ $stats['by_status']['available'] }}</p>
                <p class="text-xs text-emerald-600 mt-1 font-medium">Disponibles</p>
            </div>
            <div class="bg-sky-50 rounded-2xl border border-sky-200 p-4 text-center shadow-sm">
                <p class="text-2xl font-bold text-sky-700">{{ $stats['by_status']['assigned'] }}</p>
                <p class="text-xs text-sky-600 mt-1 font-medium">Asignados</p>
            </div>
            <div class="bg-amber-50 rounded-2xl border border-amber-200 p-4 text-center shadow-sm">
                <p class="text-2xl font-bold text-amber-700">{{ $stats['by_status']['maintenance'] }}</p>
                <p class="text-xs text-amber-600 mt-1 font-medium">Mantenimiento</p>
            </div>
            <div class="bg-red-50 rounded-2xl border border-red-200 p-4 text-center shadow-sm">
                <p class="text-2xl font-bold text-red-700">{{ $stats['by_status']['broken'] }}</p>
                <p class="text-xs text-red-600 mt-1 font-medium">Dañados</p>
            </div>
            <div class="bg-violet-50 rounded-2xl border border-violet-200 p-4 text-center shadow-sm">
                <p class="text-2xl font-bold text-violet-700">{{ $stats['by_type']['computer'] }}</p>
                <p class="text-xs text-violet-600 mt-1 font-medium">Computadoras</p>
            </div>
        </div>
        @endif

        {{-- Filtros --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 mb-6">
            <form method="GET" action="{{ ($soloLectura ?? false) ? route('rh.inventario.index') : route('admin.activos.index') }}" class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1 relative">
                    <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <input type="text" name="search" value="{{ $search }}"
                           placeholder="Buscar por nombre, marca, modelo, serial o asignado…"
                           class="w-full pl-9 pr-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                </div>
                <select name="type"
                        class="px-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition bg-white">
                    <option value="">Todos los tipos</option>
                    <option value="computer"   @selected($type === 'computer')>Computadora</option>
                    <option value="peripheral" @selected($type === 'peripheral')>Periférico</option>
                    <option value="printer"    @selected($type === 'printer')>Impresora</option>
                    <option value="other"      @selected($type === 'other')>Otro</option>
                </select>
                <select name="status"
                        class="px-4 py-2.5 text-sm border border-slate-200 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition bg-white">
                    <option value="">Todos los estados</option>
                    <option value="available"   @selected($status === 'available')>Disponible</option>
                    <option value="assigned"    @selected($status === 'assigned')>Asignado</option>
                    <option value="maintenance" @selected($status === 'maintenance')>Mantenimiento</option>
                    <option value="broken"      @selected($status === 'broken')>Dañado</option>
                </select>
                <button type="submit"
                        class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-xl hover:bg-indigo-700 transition shadow-md shadow-indigo-200 whitespace-nowrap">
                    Filtrar
                </button>
                @if($search || $type || $status)
                    <a href="{{ ($soloLectura ?? false) ? route('rh.inventario.index') : route('admin.activos.index') }}"
                       class="px-5 py-2.5 bg-slate-100 text-slate-600 text-sm font-semibold rounded-xl hover:bg-slate-200 transition whitespace-nowrap">
                        Limpiar
                    </a>
                @endif
            </form>
        </div>

        {{-- Tabla de dispositivos --}}
        @if($dispositivos && $dispositivos->total() > 0)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-left">
                            <th class="px-4 py-3 font-semibold text-slate-600 w-14"></th>
                            <th class="px-4 py-3 font-semibold text-slate-600">Dispositivo</th>
                            <th class="px-4 py-3 font-semibold text-slate-600">Tipo</th>
                            <th class="px-4 py-3 font-semibold text-slate-600">Serie</th>
                            <th class="px-4 py-3 font-semibold text-slate-600">Estado</th>
                            <th class="px-4 py-3 font-semibold text-slate-600">Asignado a</th>
                            <th class="px-4 py-3 font-semibold text-slate-600 text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($dispositivos as $d)
                        @php
                            $photoUrl = $d->photo_id
                                ? (($soloLectura ?? false) ? route('rh.activos.photo', $d->photo_id) : route('admin.activos.photo', $d->photo_id))
                                : null;

                            $statusConfig = match($d->status) {
                                'available'   => ['label' => 'Disponible',    'class' => 'bg-emerald-100 text-emerald-700'],
                                'assigned'    => ['label' => 'Asignado',      'class' => 'bg-sky-100 text-sky-700'],
                                'maintenance' => ['label' => 'Mantenimiento', 'class' => 'bg-amber-100 text-amber-700'],
                                'broken'      => ['label' => 'Dañado',        'class' => 'bg-red-100 text-red-700'],
                                default       => ['label' => $d->status,      'class' => 'bg-slate-100 text-slate-600'],
                            };

                            $typeLabel = match($d->type) {
                                'computer'   => 'Computadora',
                                'peripheral' => 'Periférico',
                                'printer'    => 'Impresora',
                                default      => 'Otro',
                            };

                            $asignado = $d->employee_name ?? $d->assigned_to ?? null;
                        @endphp
                        <tr class="hover:bg-slate-50 transition-colors">
                            {{-- Foto --}}
                            <td class="px-4 py-3">
                                @if($photoUrl)
                                    <img src="{{ $photoUrl }}"
                                         alt="{{ $d->name }}"
                                         class="w-10 h-10 rounded-lg object-cover border border-slate-200">
                                @else
                                    <div class="w-10 h-10 rounded-lg bg-slate-100 border border-slate-200 flex items-center justify-center">
                                        <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M9 3H5a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2V9l-6-6z"/>
                                        </svg>
                                    </div>
                                @endif
                            </td>
                            {{-- Nombre + marca/modelo --}}
                            <td class="px-4 py-3">
                                <p class="font-semibold text-slate-900">{{ $d->name }}</p>
                                <p class="text-xs text-slate-400">{{ $d->brand }} {{ $d->model }}</p>
                            </td>
                            {{-- Tipo --}}
                            <td class="px-4 py-3 text-slate-600">{{ $typeLabel }}</td>
                            {{-- Serie --}}
                            <td class="px-4 py-3">
                                <code class="text-xs bg-slate-100 px-2 py-0.5 rounded text-slate-700">
                                    {{ $d->serial_number ?: '—' }}
                                </code>
                            </td>
                            {{-- Estado --}}
                            <td class="px-4 py-3">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $statusConfig['class'] }}">
                                    {{ $statusConfig['label'] }}
                                </span>
                            </td>
                            {{-- Asignado a --}}
                            <td class="px-4 py-3 text-slate-600 text-sm">
                                {{ $asignado ?? '—' }}
                            </td>
                            {{-- Acciones --}}
                            <td class="px-4 py-3 text-right">
                                <a href="{{ ($soloLectura ?? false) ? route('rh.inventario.show', $d->uuid) : route('admin.activos.show', $d->uuid) }}"
                                   class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 text-xs font-semibold rounded-lg hover:bg-indigo-100 transition">
                                    Ver detalle
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Paginación --}}
            @if($dispositivos->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $dispositivos->withQueryString()->links() }}
            </div>
            @endif
        </div>

        <p class="text-xs text-slate-400 mt-3 text-right">
            {{ $dispositivos->total() }} dispositivo(s) encontrado(s)
        </p>

        @elseif($dispositivos)
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-12 text-center">
            <svg class="mx-auto h-12 w-12 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/>
            </svg>
            <p class="text-slate-500 font-medium">No se encontraron dispositivos con esos filtros.</p>
            <a href="{{ ($soloLectura ?? false) ? route('rh.inventario.index') : route('admin.activos.index') }}" class="mt-3 inline-block text-indigo-600 text-sm hover:underline">
                Limpiar filtros
            </a>
        </div>
        @endif

        @endif {{-- fin bloque conexión --}}
    </div>
</div>
@endsection
