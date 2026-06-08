@extends('layouts.master')

@section('title', 'Ficha técnica — ' . ($profile->identifier ?? 'Equipo'))

@section('content')
@php
    $componentLabels = collect($profile->replacement_components ?? [])
        ->map(fn($c) => $componentOptions[$c] ?? ucfirst(str_replace('_', ' ', $c)));

    $userImages = collect(optional($latestTicket)->imagenes ?? [])
        ->map(function ($img, $i) {
            if (is_array($img) && isset($img['data'])) {
                return ['src' => "data:{$img['mime']};base64,{$img['data']}", 'label' => $img['name'] ?? "Imagen ".($i+1)];
            }
            if (is_string($img) && str_starts_with($img, 'data:image')) {
                return ['src' => $img, 'label' => "Imagen ".($i+1)];
            }
            return null;
        })->filter();

    $adminImages = collect(optional($latestTicket)->imagenes_admin ?? [])
        ->map(fn($img, $i) => [
            'src'   => str_starts_with($img, 'data:image') ? $img : "data:image/jpeg;base64,{$img}",
            'label' => "Foto TI ".($i+1),
        ]);

    $lastMaint = $profile->last_maintenance_at
        ? $profile->last_maintenance_at->copy()->timezone('America/Mexico_City')
        : null;
    $nextMaint = $profile->next_maintenance_at
        ? $profile->next_maintenance_at->copy()->timezone('America/Mexico_City')
        : ($lastMaint ? $lastMaint->copy()->addMonths(4) : null);

    $diasRestantes = $nextMaint
        ? (int) now('America/Mexico_City')->startOfDay()->diffInDays($nextMaint->copy()->startOfDay(), false)
        : null;

    $batLabels = ['functional' => 'Funcional', 'partially_functional' => 'Parcial', 'damaged' => 'Dañada'];

    $equipoMostrar = $equiposAsignados->firstWhere('id', $profile->equipo_asignado_id)
        ?? $equiposAsignados->firstWhere('es_principal', true)
        ?? $equiposAsignados->first();

    $usuarioAsignado = $equipoMostrar?->user ?? $latestTicket?->user ?? null;
@endphp

<div class="min-h-screen bg-slate-50 pb-16">

    {{-- ── Header ──────────────────────────────────────────────────────── --}}
    <div class="bg-white border-b border-slate-200">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.maintenance.computers.index') }}"
                       class="p-2 rounded-lg hover:bg-slate-100 text-slate-400 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-widest">Ficha técnica</p>
                        <h1 class="text-xl font-bold text-slate-900 leading-tight">
                            {{ $profile->identifier ?? 'Sin identificador' }}
                            <span class="text-slate-400 font-normal text-base ml-1">{{ $profile->brand }} {{ $profile->model }}</span>
                        </h1>
                    </div>
                </div>
                <div class="flex flex-wrap items-center gap-2">
                    @if($profile->expediente)
                    <a href="{{ route('admin.expedientes.show', $profile->expediente) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-2 bg-teal-600 text-white text-xs font-semibold rounded-lg hover:bg-teal-700 transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                        Expediente
                        @if($profile->expediente->mantenimientos_count ?? $profile->expediente->mantenimientos()->count())
                            <span class="bg-teal-500 rounded-full px-1.5 py-0.5 text-[10px]">
                                {{ $profile->expediente->mantenimientos()->count() }}
                            </span>
                        @endif
                    </a>
                    @endif
                    <a href="{{ route('admin.maintenance.computers.edit', $profile) }}"
                       class="inline-flex items-center gap-1.5 px-3 py-2 bg-blue-600 text-white text-xs font-semibold rounded-lg hover:bg-blue-700 transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Editar ficha
                    </a>
                    <button onclick="document.getElementById('modalEliminar').classList.remove('hidden')"
                            class="inline-flex items-center gap-1.5 px-3 py-2 bg-red-50 border border-red-200 text-red-600 text-xs font-semibold rounded-lg hover:bg-red-100 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Modal eliminar ───────────────────────────────────────────────── --}}
    <div id="modalEliminar" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm"
             onclick="document.getElementById('modalEliminar').classList.add('hidden')"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-md p-6 z-10">
            <div class="flex items-start gap-4 mb-5">
                <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center flex-shrink-0">
                    <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-bold text-slate-900">¿Eliminar esta ficha técnica?</h3>
                    <p class="text-sm text-slate-600 mt-1">
                        Se eliminará permanentemente la ficha de
                        <strong>{{ $profile->identifier ?? 'este equipo' }}</strong>.
                        El expediente y los tickets vinculados no se eliminarán.
                    </p>
                    <p class="text-xs text-red-600 mt-2 font-medium">Esta acción no se puede deshacer.</p>
                </div>
            </div>
            <div class="flex justify-end gap-3">
                <button onclick="document.getElementById('modalEliminar').classList.add('hidden')"
                        class="px-4 py-2 border border-slate-200 text-slate-600 text-sm font-semibold rounded-lg hover:bg-slate-50 transition">
                    Cancelar
                </button>
                <form method="POST" action="{{ route('admin.maintenance.computers.destroy', $profile) }}">
                    @csrf @method('DELETE')
                    <button type="submit"
                            class="px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition shadow-sm">
                        Sí, eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-6">

        @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-xl p-3 mb-5 flex items-center gap-2 text-sm text-green-800">
            <svg class="w-4 h-4 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            {{ session('success') }}
        </div>
        @endif

        {{-- ── Tarjetas de estado ────────────────────────────────────────── --}}
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">

            {{-- Hardware --}}
            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Hardware</p>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-2">
                        <dt class="text-slate-500">Disco</dt>
                        <dd class="font-medium text-slate-800 text-right">{{ $profile->disk_type ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-slate-500">RAM</dt>
                        <dd class="font-medium text-slate-800 text-right">{{ $profile->ram_capacity ?? '—' }}</dd>
                    </div>
                    <div class="flex justify-between gap-2">
                        <dt class="text-slate-500">Batería</dt>
                        <dd class="font-medium text-right">
                            @php $bat = $profile->battery_status; @endphp
                            @if($bat === 'functional')
                                <span class="text-green-700">Funcional</span>
                            @elseif($bat === 'partially_functional')
                                <span class="text-amber-600">Parcial</span>
                            @elseif($bat === 'damaged')
                                <span class="text-red-600">Dañada</span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </dd>
                    </div>
                </dl>
                @if($componentLabels->isNotEmpty())
                <div class="mt-3 pt-3 border-t border-slate-100 flex flex-wrap gap-1">
                    @foreach($componentLabels as $label)
                    <span class="inline-flex px-1.5 py-0.5 bg-blue-50 text-blue-700 text-[11px] font-semibold rounded border border-blue-100">
                        {{ $label }}
                    </span>
                    @endforeach
                </div>
                @endif
            </div>

            {{-- Asignación --}}
            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Asignado a</p>
                @if($usuarioAsignado)
                <div class="flex items-center gap-3 mb-3">
                    @if($empleado?->foto_path)
                    <img src="{{ asset('storage/'.$empleado->foto_path) }}" alt="{{ $usuarioAsignado->name }}"
                         class="w-10 h-10 rounded-full object-cover border border-slate-200 flex-shrink-0">
                    @else
                    <div class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center flex-shrink-0">
                        <span class="text-sm font-bold text-slate-500">{{ strtoupper(substr($usuarioAsignado->name, 0, 1)) }}</span>
                    </div>
                    @endif
                    <div class="min-w-0">
                        <p class="font-semibold text-slate-900 text-sm truncate">{{ $usuarioAsignado->name }}</p>
                        <p class="text-xs text-slate-500 truncate">{{ $usuarioAsignado->email }}</p>
                    </div>
                </div>
                <dl class="space-y-1.5 text-sm">
                    @if($empleado?->area)
                    <div class="flex justify-between gap-2">
                        <dt class="text-slate-500">Área</dt>
                        <dd class="font-medium text-slate-800 text-right text-xs">{{ $empleado->area }}</dd>
                    </div>
                    @endif
                    @if($empleado?->posicion)
                    <div class="flex justify-between gap-2">
                        <dt class="text-slate-500">Puesto</dt>
                        <dd class="font-medium text-slate-800 text-right text-xs">{{ $empleado->posicion }}</dd>
                    </div>
                    @endif
                    @if($equipoMostrar?->nombre_usuario_pc)
                    <div class="flex justify-between gap-2">
                        <dt class="text-slate-500">Usuario PC</dt>
                        <dd class="font-mono text-slate-800 text-right text-xs">{{ $equipoMostrar->nombre_usuario_pc }}</dd>
                    </div>
                    @endif
                </dl>
                @else
                <p class="text-sm text-slate-400">Sin usuario asignado</p>
                @if($profile->is_loaned && $profile->loaned_to_name)
                <p class="text-xs text-amber-600 mt-1">Prestado a: {{ $profile->loaned_to_name }}</p>
                @endif
                @endif
            </div>

            {{-- Mantenimiento --}}
            <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Mantenimiento</p>
                <dl class="space-y-2 text-sm">
                    <div>
                        <dt class="text-xs text-slate-400 mb-0.5">Último</dt>
                        <dd class="font-medium text-slate-800">
                            {{ $lastMaint ? $lastMaint->format('d/m/Y') : '—' }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-400 mb-0.5">Próximo</dt>
                        <dd class="flex items-center gap-1.5 flex-wrap">
                            @if($nextMaint)
                                <span class="font-medium text-slate-800">{{ $nextMaint->format('d/m/Y') }}</span>
                                @if($diasRestantes < 0)
                                    <span class="px-1.5 py-0.5 text-[10px] font-bold bg-red-100 text-red-700 rounded-full">
                                        VENCIDO {{ abs($diasRestantes) }}d
                                    </span>
                                @elseif($diasRestantes === 0)
                                    <span class="px-1.5 py-0.5 text-[10px] font-bold bg-red-100 text-red-700 rounded-full">HOY</span>
                                @elseif($diasRestantes <= 7)
                                    <span class="px-1.5 py-0.5 text-[10px] font-bold bg-amber-100 text-amber-700 rounded-full">{{ $diasRestantes }}d</span>
                                @else
                                    <span class="px-1.5 py-0.5 text-[10px] font-bold bg-green-100 text-green-700 rounded-full">{{ $diasRestantes }}d</span>
                                @endif
                            @else
                                <span class="text-slate-400">Sin programar</span>
                            @endif
                        </dd>
                    </div>
                </dl>
                @if($latestTicket)
                <div class="mt-3 pt-3 border-t border-slate-100">
                    <a href="{{ route('admin.tickets.show', $latestTicket) }}"
                       class="inline-flex items-center gap-1 text-xs font-semibold text-blue-600 hover:text-blue-800">
                        Ticket activo: {{ $latestTicket->folio }}
                        <span class="@php
                            echo match($latestTicket->estado) {
                                'abierto' => 'bg-sky-100 text-sky-700',
                                'en_proceso' => 'bg-yellow-100 text-yellow-700',
                                'cerrado' => 'bg-slate-100 text-slate-600',
                                default => 'bg-slate-100 text-slate-600',
                            }
                        @endphp px-1.5 py-0.5 rounded-full text-[10px] font-semibold ml-1">
                            {{ ucfirst(str_replace('_', ' ', $latestTicket->estado)) }}
                        </span>
                    </a>
                    @if($latestTicket->maintenance_scheduled_at)
                    <p class="text-xs text-slate-500 mt-1">
                        Agendado: {{ $latestTicket->maintenance_scheduled_at->timezone('America/Mexico_City')->format('d/m/Y H:i') }}
                    </p>
                    @endif
                </div>
                @endif
            </div>
        </div>

        {{-- ── Contenido principal ─────────────────────────────────────── --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Columna izquierda (2/3) --}}
            <div class="lg:col-span-2 space-y-5">

                {{-- Observaciones estéticas --}}
                @if($profile->aesthetic_observations)
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Observaciones estéticas</p>
                    <p class="text-sm text-slate-700 leading-relaxed">{{ $profile->aesthetic_observations }}</p>
                </div>
                @endif

                {{-- Ticket: descripción y notas TI --}}
                @if($latestTicket)
                <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                        <div>
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Ticket {{ $latestTicket->folio }}</p>
                            <p class="text-sm font-semibold text-slate-800 mt-0.5">
                                Solicitante: {{ $latestTicket->nombre_solicitante }}
                            </p>
                        </div>
                        <a href="{{ route('admin.tickets.show', $latestTicket) }}"
                           class="text-xs text-blue-600 font-semibold hover:underline">
                            Ver ticket completo →
                        </a>
                    </div>

                    <div class="p-5 space-y-4">
                        {{-- Descripción del usuario --}}
                        @if($latestTicket->descripcion_problema)
                        <div>
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1.5">Descripción del usuario</p>
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 text-sm text-slate-700 leading-relaxed">
                                {{ $latestTicket->descripcion_problema }}
                            </div>
                        </div>
                        @endif

                        {{-- Reporte técnico --}}
                        @if($latestTicket->maintenance_report)
                        <div>
                            <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1.5">Reporte técnico</p>
                            <div class="bg-slate-50 border border-slate-200 rounded-lg p-3 text-sm text-slate-700 leading-relaxed">
                                {{ $latestTicket->maintenance_report }}
                            </div>
                        </div>
                        @endif

                        {{-- Observaciones al cierre --}}
                        @if($latestTicket->closure_observations || $latestTicket->observaciones)
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            @if($latestTicket->closure_observations)
                            <div>
                                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1.5">Observaciones de cierre</p>
                                <p class="text-sm text-slate-700 bg-slate-50 border border-slate-200 rounded-lg p-3 leading-relaxed">
                                    {{ $latestTicket->closure_observations }}
                                </p>
                            </div>
                            @endif
                            @if($latestTicket->observaciones)
                            <div>
                                <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1.5">Notas del administrador</p>
                                <p class="text-sm text-slate-700 bg-slate-50 border border-slate-200 rounded-lg p-3 leading-relaxed">
                                    {{ $latestTicket->observaciones }}
                                </p>
                            </div>
                            @endif
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Imágenes --}}
                @if($userImages->isNotEmpty() || $adminImages->isNotEmpty())
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">
                        Evidencia fotográfica
                        <span class="font-normal text-slate-400 ml-1">({{ $userImages->count() + $adminImages->count() }} archivos)</span>
                    </p>
                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                        @foreach($userImages as $imgItem)
                        @php $imgSrc = $imgItem['src']; $imgLbl = $imgItem['label']; @endphp
                        <button data-img-src="{{ $imgSrc }}" data-img-label="{{ $imgLbl }}"
                                onclick="openImgModal(this.dataset.imgSrc, this.dataset.imgLabel)"
                                class="group relative aspect-video overflow-hidden rounded-lg border border-slate-200 bg-slate-100 focus:outline-none">
                            <img src="{{ $imgSrc }}" alt="{{ $imgLbl }}"
                                 class="h-full w-full object-cover group-hover:scale-105 group-hover:brightness-90 transition duration-200">
                            <span class="absolute bottom-0 inset-x-0 bg-slate-900/70 text-white text-[10px] px-2 py-1 text-left">
                                {{ $imgLbl }}
                            </span>
                        </button>
                        @endforeach
                        @foreach($adminImages as $imgItem)
                        @php $imgSrc = $imgItem['src']; $imgLbl = $imgItem['label']; @endphp
                        <button data-img-src="{{ $imgSrc }}" data-img-label="{{ $imgLbl }}"
                                onclick="openImgModal(this.dataset.imgSrc, this.dataset.imgLabel)"
                                class="group relative aspect-video overflow-hidden rounded-lg border border-teal-200 bg-teal-50 focus:outline-none">
                            <img src="{{ $imgSrc }}" alt="{{ $imgLbl }}"
                                 class="h-full w-full object-cover group-hover:scale-105 group-hover:brightness-95 transition duration-200">
                            <span class="absolute bottom-0 inset-x-0 bg-teal-800/80 text-white text-[10px] px-2 py-1 text-left">
                                {{ $imgLbl }}
                            </span>
                        </button>
                        @endforeach
                    </div>
                </div>
                @endif
                @endif

                {{-- Historial de tickets anteriores --}}
                @if($historyTickets->isNotEmpty())
                <div class="bg-white border border-slate-200 rounded-xl p-5 shadow-sm">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">
                        Tickets anteriores
                        <span class="font-normal ml-1">({{ $historyTickets->count() }})</span>
                    </p>
                    <div class="space-y-2">
                        @foreach($historyTickets as $tkt)
                        <div class="flex items-center justify-between gap-3 py-2 border-b border-slate-100 last:border-0">
                            <div class="flex items-center gap-3">
                                <span class="w-2 h-2 rounded-full bg-slate-300 flex-shrink-0"></span>
                                <div>
                                    <span class="font-semibold text-slate-800 text-sm">{{ $tkt->folio }}</span>
                                    <span class="text-xs text-slate-400 ml-2">
                                        {{ $tkt->created_at->timezone('America/Mexico_City')->format('d/m/Y') }}
                                    </span>
                                </div>
                            </div>
                            <a href="{{ route('admin.tickets.show', $tkt) }}"
                               class="text-xs text-blue-600 font-semibold hover:underline flex-shrink-0">
                                Ver →
                            </a>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif

            </div>

            {{-- Columna derecha (1/3) --}}
            <div class="space-y-5">

                {{-- Selector de equipo asignado (si hay varios) --}}
                @if($equiposAsignados->count() > 1)
                <div class="bg-white border border-slate-200 rounded-xl p-4 shadow-sm">
                    <p class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Equipo a mantener</p>
                    <form method="POST" action="{{ route('admin.maintenance.computers.setEquipo', $profile) }}" class="space-y-2">
                        @csrf @method('PATCH')
                        <select name="equipo_asignado_id"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach($equiposAsignados as $eq)
                            <option value="{{ $eq->id }}" @selected($profile->equipo_asignado_id == $eq->id)>
                                {{ $eq->nombre_equipo }}
                                {{ $eq->es_principal ? '(Principal)' : '(Secundaria)' }}
                            </option>
                            @endforeach
                        </select>
                        <button type="submit"
                                class="w-full py-2 bg-slate-700 text-white text-xs font-bold rounded-lg hover:bg-slate-800 transition">
                            Confirmar selección
                        </button>
                    </form>
                </div>
                @endif

                {{-- Actualizar ticket --}}
                @if($latestTicket)
                <div class="bg-white border border-slate-200 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-4 py-3 bg-slate-50 border-b border-slate-200">
                        <p class="text-xs font-semibold text-slate-600 uppercase tracking-wide">Actualizar ticket</p>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $latestTicket->folio }}</p>
                    </div>
                    <div class="p-4">
                        <form method="POST" action="{{ route('admin.tickets.update', $latestTicket) }}"
                              class="space-y-4" enctype="multipart/form-data">
                            @csrf @method('PATCH')
                            <input type="hidden" name="estado" value="{{ $latestTicket->estado }}">

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Reporte técnico</label>
                                <textarea name="maintenance_report" rows="3"
                                          placeholder="Trabajo realizado, componentes, diagnóstico…"
                                          class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-y">{{ old('maintenance_report', $latestTicket->maintenance_report) }}</textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Observaciones de cierre</label>
                                <textarea name="closure_observations" rows="2"
                                          placeholder="Notas al cerrar el ticket…"
                                          class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-y">{{ old('closure_observations', $latestTicket->closure_observations) }}</textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Notas internas</label>
                                <textarea name="observaciones" rows="2"
                                          placeholder="Notas visibles solo para el administrador…"
                                          class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-y">{{ old('observaciones', $latestTicket->observaciones) }}</textarea>
                            </div>

                            {{-- Imágenes --}}
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Agregar fotos de TI</label>
                                <input type="file" id="adminImages" name="imagenes_admin[]" multiple accept="image/*"
                                       class="w-full text-xs text-slate-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200">
                                <div id="imgPreview" class="grid grid-cols-2 gap-2 mt-2" style="display:none"></div>
                                <div id="imgStatus" class="hidden text-xs text-slate-500 mt-1">
                                    <span id="imgCount">0</span> archivo(s) seleccionado(s).
                                </div>
                            </div>

                            {{-- Imágenes existentes --}}
                            @if($latestTicket->imagenes_admin && count($latestTicket->imagenes_admin) > 0)
                            <div>
                                <p class="text-xs font-semibold text-slate-500 mb-2">
                                    Fotos existentes ({{ count($latestTicket->imagenes_admin) }})
                                </p>
                                <div class="grid grid-cols-3 gap-1.5">
                                    @foreach($latestTicket->imagenes_admin as $idx => $adminImg)
                                    @php $adminImgSrc = 'data:image/jpeg;base64,' . $adminImg; $adminImgLbl = 'Foto TI ' . ($idx + 1); @endphp
                                    <div class="relative group rounded-lg overflow-hidden border border-slate-200 aspect-square">
                                        <img src="{{ $adminImgSrc }}" alt="{{ $adminImgLbl }}"
                                             class="h-full w-full object-cover cursor-pointer hover:scale-105 transition"
                                             data-img-src="{{ $adminImgSrc }}" data-img-label="{{ $adminImgLbl }}"
                                             onclick="openImgModal(this.dataset.imgSrc, this.dataset.imgLabel)">
                                        <button type="button" data-rm-idx="{{ $idx }}"
                                                onclick="removeExistingImg(event, parseInt(this.dataset.rmIdx))"
                                                class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                    @endforeach
                                </div>
                            </div>
                            @endif

                            <button type="submit"
                                    class="w-full py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-lg hover:bg-blue-700 transition shadow-sm">
                                Guardar cambios
                            </button>
                        </form>
                    </div>
                </div>
                @endif

            </div>
        </div>
    </div>
</div>

{{-- Modal imagen --}}
<div id="imgModal" class="fixed inset-0 z-50 hidden bg-black/80 backdrop-blur-sm flex items-center justify-center p-4"
     onclick="closeImgModal()">
    <div class="relative max-w-4xl w-full" onclick="event.stopPropagation()">
        <button onclick="closeImgModal()"
                class="absolute -top-8 right-0 text-white/80 hover:text-white">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
            </svg>
        </button>
        <div class="rounded-2xl overflow-hidden bg-slate-900 shadow-2xl">
            <img id="imgModalSrc" src="" alt="" class="w-full max-h-[78vh] object-contain bg-black">
            <p id="imgModalCaption" class="px-5 py-3 text-sm text-slate-300 border-t border-white/10"></p>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openImgModal(src, caption) {
    document.getElementById('imgModalSrc').src = src;
    document.getElementById('imgModalCaption').textContent = caption ?? '';
    document.getElementById('imgModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}
function closeImgModal() {
    document.getElementById('imgModal').classList.add('hidden');
    document.getElementById('imgModalSrc').src = '';
    document.body.style.overflow = '';
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeImgModal(); });

// Image preview for new uploads
let selectedFiles = [];
const adminInput = document.getElementById('adminImages');
if (adminInput) {
    adminInput.addEventListener('change', () => {
        selectedFiles = [...selectedFiles, ...Array.from(adminInput.files)];
        renderPreview();
    });
}
function renderPreview() {
    const grid   = document.getElementById('imgPreview');
    const status = document.getElementById('imgStatus');
    const count  = document.getElementById('imgCount');
    grid.innerHTML = '';
    if (selectedFiles.length) {
        grid.style.display = 'grid';
        status.classList.remove('hidden');
        count.textContent = selectedFiles.length;
        selectedFiles.forEach((file, i) => {
            if (!file?.type?.startsWith('image/')) return;
            const r = new FileReader();
            r.onload = e => {
                const div = document.createElement('div');
                div.className = 'relative group aspect-square rounded-lg overflow-hidden border border-slate-200';
                div.innerHTML = `
                    <img src="${e.target.result}" class="h-full w-full object-cover cursor-pointer" onclick="openImgModal('${e.target.result}', 'Vista previa ${i+1}')">
                    <button type="button" onclick="removePreview(${i})" class="absolute top-1 right-1 bg-red-500 text-white rounded-full p-0.5 opacity-0 group-hover:opacity-100 transition">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>`;
                grid.appendChild(div);
            };
            r.readAsDataURL(file);
        });
    } else {
        grid.style.display = 'none';
        status.classList.add('hidden');
    }
    const dt = new DataTransfer();
    selectedFiles.forEach(f => f && dt.items.add(f));
    if (adminInput) adminInput.files = dt.files;
}
function removePreview(i) { selectedFiles.splice(i, 1); renderPreview(); }
function removeExistingImg(event, idx) {
    event.currentTarget.closest('.relative').style.display = 'none';
    const form = event.currentTarget.closest('form');
    if (!form.querySelector(`input[name="removed_admin_images[]"][value="${idx}"]`)) {
        const h = document.createElement('input');
        h.type = 'hidden'; h.name = 'removed_admin_images[]'; h.value = idx;
        form.appendChild(h);
    }
}
</script>
@endpush
@endsection
