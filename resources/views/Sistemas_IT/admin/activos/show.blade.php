@extends('layouts.master')

@section('title', $dispositivo->name . ' — Activos IT')

@section('content')
@php
    $statusConfig = match($dispositivo->status) {
        'available'   => ['label' => 'Disponible',    'class' => 'bg-emerald-100 text-emerald-700 border-emerald-200'],
        'assigned'    => ['label' => 'Asignado',      'class' => 'bg-sky-100 text-sky-700 border-sky-200'],
        'maintenance' => ['label' => 'Mantenimiento', 'class' => 'bg-amber-100 text-amber-700 border-amber-200'],
        'broken'      => ['label' => 'Dañado',        'class' => 'bg-red-100 text-red-700 border-red-200'],
        default       => ['label' => $dispositivo->status, 'class' => 'bg-slate-100 text-slate-600 border-slate-200'],
    };

    $typeLabel = match($dispositivo->type) {
        'computer'   => 'Computadora',
        'peripheral' => 'Periférico',
        'printer'    => 'Impresora',
        default      => 'Otro',
    };

    $asignadoActual = $dispositivo->employee_name ?? $dispositivo->assigned_to ?? null;
@endphp

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
                        <a href="{{ ($soloLectura ?? false) ? route('rh.inventario.index') : route('admin.activos.index') }}"
                           class="text-slate-400 hover:text-indigo-600 transition-colors text-sm font-medium">
                            Activos IT
                        </a>
                        <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <span class="text-slate-600 text-sm font-medium truncate max-w-xs">{{ $dispositivo->name }}</span>
                    </div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-3xl font-bold text-slate-900 tracking-tight">{{ $dispositivo->name }}</h1>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold border {{ $statusConfig['class'] }}">
                            {{ $statusConfig['label'] }}
                        </span>
                    </div>
                    <p class="text-slate-500 mt-1">{{ $dispositivo->brand }} {{ $dispositivo->model }} — {{ $typeLabel }}</p>
                </div>
                <div class="flex items-center gap-2 flex-wrap">
                    @unless($soloLectura ?? false)
                    @if($dispositivo->status !== 'assigned')
                    <button onclick="document.getElementById('modal-asignar').classList.remove('hidden')"
                            class="inline-flex items-center px-4 py-2 bg-sky-600 text-white font-semibold text-sm rounded-xl hover:bg-sky-700 transition shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Asignar
                    </button>
                    @else
                    <form method="POST" action="{{ route('admin.activos.return', $dispositivo->uuid) }}"
                          onsubmit="return confirm('¿Confirmar devolución del dispositivo?')">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white font-semibold text-sm rounded-xl hover:bg-emerald-700 transition shadow-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                            </svg>
                            Registrar devolución
                        </button>
                    </form>
                    @endif

                    <a href="{{ route('admin.activos.edit', $dispositivo->uuid) }}"
                       class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 text-slate-600 font-semibold text-sm rounded-xl hover:bg-slate-50 transition shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Editar
                    </a>
                    @endunless

                    @unless($soloLectura ?? false)
                    <a href="{{ route('admin.activos.qr-scanner') }}"
                       class="inline-flex items-center px-4 py-2 bg-indigo-50 border border-indigo-200 text-indigo-700 font-semibold text-sm rounded-xl hover:bg-indigo-100 transition shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                        </svg>
                        Escanear QR
                    </a>
                    @endunless
                    <a href="{{ ($soloLectura ?? false) ? route('rh.inventario.index') : route('admin.activos.index') }}"
                       class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 text-slate-600 font-semibold text-sm rounded-xl hover:bg-slate-50 transition shadow-sm group">
                        <svg class="w-4 h-4 mr-2 group-hover:-translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Volver al listado
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Columna izquierda: datos + foto --}}
            <div class="lg:col-span-1 space-y-6">

                {{-- Foto(s) --}}
                @if(count($fotos) > 0)
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
                    <img src="{{ $fotos[0]['url'] }}"
                         alt="{{ $dispositivo->name }}"
                         class="w-full object-cover max-h-64">
                    @if(count($fotos) > 1)
                    <div class="p-4 grid grid-cols-4 gap-2">
                        @foreach(array_slice($fotos, 1) as $foto)
                        <img src="{{ $foto['url'] }}"
                             alt="{{ $foto['caption'] ?: $dispositivo->name }}"
                             class="w-full h-16 object-cover rounded-lg border border-slate-200 cursor-pointer hover:opacity-80 transition">
                        @endforeach
                    </div>
                    @endif
                </div>
                @else
                <div class="bg-slate-100 rounded-2xl border border-slate-200 flex items-center justify-center h-48">
                    <svg class="w-16 h-16 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                @endif

                {{-- Datos del dispositivo --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-4">Información del equipo</h2>
                    <dl class="space-y-3 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Marca</dt>
                            <dd class="font-semibold text-slate-800">{{ $dispositivo->brand ?: '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Modelo</dt>
                            <dd class="font-semibold text-slate-800">{{ $dispositivo->model ?: '—' }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Tipo</dt>
                            <dd class="font-semibold text-slate-800">{{ $typeLabel }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-slate-500">No. Serie</dt>
                            <dd>
                                <code class="text-xs bg-slate-100 px-2 py-0.5 rounded text-slate-700">
                                    {{ $dispositivo->serial_number ?: '—' }}
                                </code>
                            </dd>
                        </div>
                        @if($dispositivo->purchase_date)
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Compra</dt>
                            <dd class="font-semibold text-slate-800">
                                {{ \Carbon\Carbon::parse($dispositivo->purchase_date)->format('d/m/Y') }}
                            </dd>
                        </div>
                        @endif
                        @if($dispositivo->warranty_expiration)
                        @php
                            $garantia = \Carbon\Carbon::parse($dispositivo->warranty_expiration);
                            $garantiaVencida = $garantia->isPast();
                        @endphp
                        <div class="flex justify-between">
                            <dt class="text-slate-500">Garantía</dt>
                            <dd class="font-semibold {{ $garantiaVencida ? 'text-red-600' : 'text-slate-800' }}">
                                {{ $garantia->format('d/m/Y') }}
                                @if($garantiaVencida)
                                    <span class="text-xs font-normal">(vencida)</span>
                                @endif
                            </dd>
                        </div>
                        @endif
                    </dl>
                    @if($dispositivo->notes)
                    <div class="mt-4 pt-4 border-t border-slate-100">
                        <p class="text-xs text-slate-500 font-semibold mb-1">Notas</p>
                        <p class="text-sm text-slate-700">{{ $dispositivo->notes }}</p>
                    </div>
                    @endif
                </div>

                {{-- Documentos --}}
                @if(count($documentos) > 0)
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5">
                    <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-4">Documentos</h2>
                    <ul class="space-y-2">
                        @foreach($documentos as $doc)
                        @php
                            $tipoDoc = match($doc->type ?? '') {
                                'factura'  => 'Factura',
                                'garantia' => 'Garantía',
                                'contrato' => 'Contrato',
                                'manual'   => 'Manual',
                                default    => 'Otro',
                            };
                        @endphp
                        <li class="flex items-center gap-3 text-sm">
                            <svg class="w-4 h-4 text-slate-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            <div class="flex-1 min-w-0">
                                <p class="text-slate-800 truncate">{{ $doc->original_name }}</p>
                                <p class="text-xs text-slate-400">{{ $tipoDoc }}</p>
                            </div>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

            </div>

            {{-- Columna derecha: asignación actual + historial --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Asignación actual --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                    <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-4">Asignación actual</h2>
                    @if($asignadoActual)
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-12 h-12 bg-sky-100 rounded-full flex items-center justify-center">
                            <svg class="w-6 h-6 text-sky-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                        <div>
                            <p class="font-bold text-slate-900 text-lg">{{ $asignadoActual }}</p>
                            @if($dispositivo->employee_badge)
                            <p class="text-sm text-slate-500">ID Empleado: <code class="bg-slate-100 px-1 rounded">{{ $dispositivo->employee_badge }}</code></p>
                            @endif
                            @if($dispositivo->department)
                            <p class="text-sm text-slate-500">{{ $dispositivo->department }}@if($dispositivo->position) — {{ $dispositivo->position }}@endif</p>
                            @endif
                            @if($dispositivo->assigned_at)
                            <p class="text-xs text-slate-400 mt-1">
                                Asignado el {{ \Carbon\Carbon::parse($dispositivo->assigned_at)->format('d/m/Y') }}
                            </p>
                            @endif
                            @if($dispositivo->assignment_notes)
                            <p class="text-sm text-slate-600 mt-2 bg-slate-50 rounded-lg px-3 py-2 border border-slate-200">
                                {{ $dispositivo->assignment_notes }}
                            </p>
                            @endif
                        </div>
                    </div>
                    @else
                    <div class="flex items-center gap-3 text-slate-500">
                        <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>Este dispositivo está libre, sin asignación activa.</span>
                    </div>
                    @endif
                </div>

                {{-- Historial de asignaciones --}}
                <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
                    <h2 class="text-sm font-bold text-slate-700 uppercase tracking-wide mb-4">
                        Historial de asignaciones
                        <span class="ml-2 text-xs font-normal text-slate-400 normal-case">
                            {{ count($historial) }} registro(s)
                        </span>
                    </h2>

                    @if(count($historial) > 0)
                    <div class="space-y-3">
                        @foreach($historial as $h)
                        @php
                            $estaActivo    = is_null($h->returned_at);
                            $nombreAsignado = $h->employee_name ?? $h->activos_user_name ?? $h->assigned_to ?? '—';
                        @endphp
                        <div class="flex items-start gap-4 p-4 rounded-xl {{ $estaActivo ? 'bg-sky-50 border border-sky-200' : 'bg-slate-50 border border-slate-200' }}">
                            <div class="flex-shrink-0 mt-0.5">
                                @if($estaActivo)
                                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-sky-500 ring-2 ring-sky-200 ring-offset-1"></span>
                                @else
                                    <span class="inline-block w-2.5 h-2.5 rounded-full bg-slate-300"></span>
                                @endif
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="font-semibold text-slate-900 text-sm">{{ $nombreAsignado }}</p>
                                @if($h->employee_badge)
                                <p class="text-xs text-slate-500">ID: {{ $h->employee_badge }}</p>
                                @endif
                                <div class="flex flex-wrap gap-4 mt-1 text-xs text-slate-500">
                                    <span>
                                        Desde: <strong>{{ \Carbon\Carbon::parse($h->assigned_at)->format('d/m/Y') }}</strong>
                                    </span>
                                    @if($h->returned_at)
                                    <span>
                                        Hasta: <strong>{{ \Carbon\Carbon::parse($h->returned_at)->format('d/m/Y') }}</strong>
                                    </span>
                                    <span class="text-slate-400">
                                        ({{ \Carbon\Carbon::parse($h->assigned_at)->diffForHumans(\Carbon\Carbon::parse($h->returned_at), true) }})
                                    </span>
                                    @else
                                    <span class="text-sky-600 font-semibold">Vigente</span>
                                    @endif
                                </div>
                                @if($h->notes)
                                <p class="text-xs text-slate-500 mt-1 italic">{{ $h->notes }}</p>
                                @endif
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @else
                    <p class="text-slate-400 text-sm">Este dispositivo nunca ha sido asignado.</p>
                    @endif
                </div>

            </div>
        </div>
    </div>
</div>

{{-- Modal: Asignar dispositivo --}}
<div id="modal-asignar" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-md overflow-hidden">
        <div class="px-8 pt-8 pb-6 border-b border-slate-100">
            <div class="flex items-center justify-between">
                <h3 class="text-xl font-bold text-slate-900">Asignar dispositivo</h3>
                <button onclick="document.getElementById('modal-asignar').classList.add('hidden')"
                        class="text-slate-400 hover:text-slate-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <p class="text-sm text-slate-500 mt-1">Selecciona el empleado que recibirá: <strong>{{ $dispositivo->name }}</strong></p>
        </div>

        <form method="POST" action="{{ route('admin.activos.assign', $dispositivo->uuid) }}" class="px-8 py-6 space-y-5">
            @csrf

            @if(session('error'))
            <div class="bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-red-700 text-sm">
                {{ session('error') }}
            </div>
            @endif

            <div>
                <label for="empleado_id" class="block text-sm font-semibold text-slate-700 mb-1.5">
                    Empleado <span class="text-red-500">*</span>
                </label>
                <select id="empleado_id" name="empleado_id" required
                        class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition">
                    <option value="">— Seleccionar empleado —</option>
                    @foreach($empleados as $emp)
                    <option value="{{ $emp->id }}">
                        {{ $emp->nombre }}
                        @if($emp->area) — {{ $emp->area }}@endif
                        @if($emp->posicion) ({{ $emp->posicion }})@endif
                    </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label for="notes" class="block text-sm font-semibold text-slate-700 mb-1.5">Notas de asignación</label>
                <textarea id="notes" name="notes" rows="2"
                          placeholder="Observaciones opcionales…"
                          class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-sky-500 focus:border-sky-500 outline-none transition resize-none"></textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button"
                        onclick="document.getElementById('modal-asignar').classList.add('hidden')"
                        class="px-5 py-2.5 bg-white border border-slate-200 text-slate-600 font-semibold text-sm rounded-xl hover:bg-slate-50 transition">
                    Cancelar
                </button>
                <button type="submit"
                        class="px-6 py-2.5 bg-sky-600 text-white font-bold text-sm rounded-xl hover:bg-sky-700 transition shadow-lg shadow-sky-200">
                    Confirmar asignación
                </button>
            </div>
        </form>
    </div>
</div>

@endsection
