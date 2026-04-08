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
                <a href="{{ route('admin.activos.qr-scanner') }}"
                   class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white font-bold text-sm rounded-xl hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                    </svg>
                    Escanear QR
                </a>
                <button onclick="imprimirEtiquetas()"
                   class="inline-flex items-center px-5 py-2.5 bg-emerald-600 text-white font-bold text-sm rounded-xl hover:bg-emerald-700 transition shadow-lg shadow-emerald-200">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Imprimir etiquetas
                </button>
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

        {{-- Tarjetas de estadísticas (clickeables para filtrar) --}}
        @if($stats)
        @php
            $baseUrl    = ($soloLectura ?? false) ? route('rh.inventario.index') : route('admin.activos.index');
            $activeStatus = $status ?? '';
        @endphp
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
            {{-- Total (sin filtro de estado) --}}
            <a href="{{ $baseUrl . '?status=' }}"
               class="rounded-2xl border p-4 text-center shadow-sm transition hover:shadow-md
                      {{ $activeStatus === '' ? 'bg-slate-200 border-slate-400 ring-2 ring-slate-400' : 'bg-white border-slate-200' }}">
                <p class="text-2xl font-bold text-slate-900">{{ $stats['total'] }}</p>
                <p class="text-xs text-slate-500 mt-1 font-medium">Total</p>
            </a>
            {{-- Disponibles --}}
            <a href="{{ $baseUrl . '?status=available' }}"
               class="rounded-2xl border p-4 text-center shadow-sm transition hover:shadow-md
                      {{ $activeStatus === 'available' ? 'bg-emerald-200 border-emerald-500 ring-2 ring-emerald-400' : 'bg-emerald-50 border-emerald-200' }}">
                <p class="text-2xl font-bold text-emerald-700">{{ $stats['by_status']['available'] }}</p>
                <p class="text-xs text-emerald-600 mt-1 font-medium">Disponibles</p>
            </a>
            {{-- Asignados --}}
            <a href="{{ $baseUrl . '?status=assigned' }}"
               class="rounded-2xl border p-4 text-center shadow-sm transition hover:shadow-md
                      {{ $activeStatus === 'assigned' ? 'bg-sky-200 border-sky-500 ring-2 ring-sky-400' : 'bg-sky-50 border-sky-200' }}">
                <p class="text-2xl font-bold text-sky-700">{{ $stats['by_status']['assigned'] }}</p>
                <p class="text-xs text-sky-600 mt-1 font-medium">Asignados</p>
            </a>
            {{-- Mantenimiento --}}
            <a href="{{ $baseUrl . '?status=maintenance' }}"
               class="rounded-2xl border p-4 text-center shadow-sm transition hover:shadow-md
                      {{ $activeStatus === 'maintenance' ? 'bg-amber-200 border-amber-500 ring-2 ring-amber-400' : 'bg-amber-50 border-amber-200' }}">
                <p class="text-2xl font-bold text-amber-700">{{ $stats['by_status']['maintenance'] }}</p>
                <p class="text-xs text-amber-600 mt-1 font-medium">Mantenimiento</p>
            </a>
            {{-- Dañados --}}
            <a href="{{ $baseUrl . '?status=broken' }}"
               class="rounded-2xl border p-4 text-center shadow-sm transition hover:shadow-md
                      {{ $activeStatus === 'broken' ? 'bg-red-200 border-red-500 ring-2 ring-red-400' : 'bg-red-50 border-red-200' }}">
                <p class="text-2xl font-bold text-red-700">{{ $stats['by_status']['broken'] }}</p>
                <p class="text-xs text-red-600 mt-1 font-medium">Dañados</p>
            </a>
            {{-- Computadoras --}}
            <a href="{{ $baseUrl . '?type=computer' }}"
               class="rounded-2xl border p-4 text-center shadow-sm transition hover:shadow-md
                      {{ ($type ?? '') === 'computer' ? 'bg-violet-200 border-violet-500 ring-2 ring-violet-400' : 'bg-violet-50 border-violet-200' }}">
                <p class="text-2xl font-bold text-violet-700">{{ $stats['by_type']['computer'] }}</p>
                <p class="text-xs text-violet-600 mt-1 font-medium">Computadoras</p>
            </a>
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
                    <option value="available"   @selected(($status ?? '') === 'available')>Disponible</option>
                    <option value="assigned"    @selected(($status ?? '') === 'assigned')>Asignado</option>
                    <option value="maintenance" @selected(($status ?? '') === 'maintenance')>Mantenimiento</option>
                    <option value="broken"      @selected(($status ?? '') === 'broken')>Dañado</option>
                </select>
                <button type="submit"
                        class="px-5 py-2.5 bg-indigo-600 text-white text-sm font-bold rounded-xl hover:bg-indigo-700 transition shadow-md shadow-indigo-200 whitespace-nowrap">
                    Filtrar
                </button>
                @if($search || $type || ($status !== null && $status !== 'available'))
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
                                ? (($soloLectura ?? false) ? route('rh.inventario.photo', $d->photo_id) : route('admin.activos.photo', $d->photo_id))
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
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ ($soloLectura ?? false) ? route('rh.inventario.show', $d->uuid) : route('admin.activos.show', $d->uuid) }}"
                                       class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 text-xs font-semibold rounded-lg hover:bg-indigo-100 transition">
                                        Ver detalle
                                    </a>
                                    @unless($soloLectura ?? false)
                                    <button
                                        onclick="abrirQR('{{ $d->uuid }}', '{{ addslashes($d->name) }}')"
                                        class="inline-flex items-center px-2.5 py-1.5 bg-violet-50 text-violet-700 text-xs font-semibold rounded-lg hover:bg-violet-100 transition"
                                        title="Ver código QR">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
                                        </svg>
                                    </button>
                                    @endunless
                                </div>
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

{{-- ============================================================
     MODAL QR GLOBAL (índice)
     ============================================================ --}}
@unless($soloLectura ?? false)
<div id="modal-qr-idx" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4" onclick="if(event.target===this)cerrarQR()">
    <div class="bg-white rounded-3xl shadow-2xl w-full max-w-sm p-8 text-center">
        <h3 id="qr-idx-nombre" class="text-lg font-bold text-slate-800 mb-1"></h3>
        <p class="text-xs text-slate-400 mb-5">Escanea para asignar, prestar o liberar este equipo</p>

        <div id="qr-idx-canvas" class="flex justify-center mb-4"></div>
        <p id="qr-idx-uuid" class="text-[10px] text-slate-400 font-mono break-all mb-6"></p>

        <div class="flex gap-3">
            <button onclick="descargarQRIdx()" class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-violet-600 text-white font-bold text-sm rounded-xl hover:bg-violet-700 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                Descargar
            </button>
            <button onclick="imprimirQRIdx()" class="flex-1 flex items-center justify-center gap-2 px-4 py-2.5 bg-slate-100 text-slate-700 font-bold text-sm rounded-xl hover:bg-slate-200 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                Imprimir
            </button>
            <button onclick="cerrarQR()" class="px-4 py-2.5 bg-white border border-slate-200 text-slate-500 font-bold text-sm rounded-xl hover:bg-slate-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    </div>
</div>
@endunless

@push('scripts')
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
<script>
(function () {
    const BASE_URL   = '{{ url('/admin/activos') }}';
    const modal      = document.getElementById('modal-qr-idx');
    const container  = document.getElementById('qr-idx-canvas');
    let currentUuid  = null;
    let currentNombre = null;
    let qrInstance   = null;

    window.abrirQR = function(uuid, nombre) {
        currentUuid   = uuid;
        currentNombre = nombre;
        document.getElementById('qr-idx-nombre').textContent = nombre;
        document.getElementById('qr-idx-uuid').textContent   = uuid;

        // Limpiar QR anterior
        container.innerHTML = '';
        qrInstance = null;

        qrInstance = new QRCode(container, {
            text: BASE_URL + '/' + uuid,
            width: 220,
            height: 220,
            colorDark: '#1e1b4b',
            colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.H,
        });

        modal.classList.remove('hidden');
    };

    window.cerrarQR = function() {
        modal.classList.add('hidden');
    };

    window.descargarQRIdx = function () {
        const img = container.querySelector('img');
        if (!img) return;
        const a = document.createElement('a');
        a.href = img.src;
        a.download = 'QR-' + (currentNombre || currentUuid).replace(/[^a-z0-9]/gi, '_') + '.png';
        a.click();
    };

    window.imprimirQRIdx = function () {
        const img = container.querySelector('img');
        if (!img) return;
        const w = window.open('', '', 'width=400,height=500');
        w.document.write(`
            <html><head><title>QR — ${currentNombre}</title></head>
            <body style="display:flex;flex-direction:column;align-items:center;justify-content:center;font-family:sans-serif;padding:32px;">
                <h2 style="margin-bottom:4px;">${currentNombre}</h2>
                <img src="${img.src}" style="width:220px;height:220px;">
                <p style="font-size:10px;color:#888;margin-top:8px;">${currentUuid}</p>
            </body></html>
        `);
        w.document.close();
        w.focus();
        w.print();
        w.close();
    };
}());
</script>

{{-- ── Datos para impresión de etiquetas ──────────────────────────── --}}
@php
    $etiquetasArray = [];
    if ($dispositivos && !isset($noConexion)) {
        foreach ($dispositivos as $d) {
            $estadoLabel = match($d->status ?? '') {
                'available'   => 'Disponible',
                'assigned'    => 'Asignado',
                'maintenance' => 'Mantenimiento',
                'broken'      => 'Dañado',
                default       => $d->status ?? '',
            };
            $etiquetasArray[] = [
                'uuid'     => $d->uuid,
                'nombre'   => $d->name,
                'marca'    => trim(($d->brand ?? '') . ' ' . ($d->model ?? '')),
                'serie'    => $d->serial_number ?? '',
                'estado'   => $estadoLabel,
                'asignado' => $d->employee_name ?? $d->assigned_to ?? '',
            ];
        }
    }

    $labelSeccion = '';
    if (!empty($type)) {
        $labelSeccion = match($type) {
            'computer'   => 'Computadoras',
            'peripheral' => 'Periféricos',
            'printer'    => 'Impresoras',
            default      => 'Otro',
        };
    } elseif (!empty($status)) {
        $labelSeccion = match($status) {
            'available'   => 'Disponibles',
            'assigned'    => 'Asignados',
            'maintenance' => 'En Mantenimiento',
            'broken'      => 'Dañados',
            default       => 'Activos IT',
        };
    } elseif (!empty($search)) {
        $labelSeccion = 'Búsqueda: ' . $search;
    } else {
        $labelSeccion = 'Todos los Activos IT';
    }
@endphp
<script>
const ETIQUETAS_DATA     = @json($etiquetasArray);
const ETIQUETAS_BASE_URL = '{{ url('/admin/activos') }}';
const ETIQUETAS_SECCION  = '{{ addslashes($labelSeccion) }}';

window.imprimirEtiquetas = function () {
    if (!ETIQUETAS_DATA || ETIQUETAS_DATA.length === 0) {
        alert('No hay dispositivos en la vista actual para imprimir.');
        return;
    }

    // Crear QR como data-URL para cada dispositivo usando QRCode.js en canvas oculto
    const total = ETIQUETAS_DATA.length;
    const qrDataUrls = [];
    let generados = 0;

    function onQRGenerado(idx, dataUrl) {
        qrDataUrls[idx] = dataUrl;
        generados++;
        if (generados === total) abrirVentanaImpresion();
    }

    ETIQUETAS_DATA.forEach((d, idx) => {
        const div = document.createElement('div');
        div.style.position = 'absolute';
        div.style.left     = '-9999px';
        document.body.appendChild(div);

        const qr = new QRCode(div, {
            text: ETIQUETAS_BASE_URL + '/' + d.uuid,
            width: 120, height: 120,
            colorDark: '#111827', colorLight: '#ffffff',
            correctLevel: QRCode.CorrectLevel.H,
        });

        // QRCode.js genera la imagen de forma síncrona; la imagen ya existe
        setTimeout(() => {
            const img = div.querySelector('img');
            onQRGenerado(idx, img ? img.src : '');
            document.body.removeChild(div);
        }, 60);
    });

    function abrirVentanaImpresion() {
        // 3 columnas × N filas, tamaño etiqueta ≈ 6 cm × 7 cm
        const cols = 3;
        const labelW = '180px';
        const labelH = '190px';

        const etiquetasHtml = ETIQUETAS_DATA.map((d, i) => `
            <div class="etiqueta">
                <div class="etq-header">E&amp;I — Activos IT</div>
                <img src="${qrDataUrls[i]}" class="etq-qr" alt="QR">
                <div class="etq-nombre">${d.nombre}</div>
                <div class="etq-meta">${d.marca}</div>
                ${d.serie ? `<div class="etq-serie">S/N: ${d.serie}</div>` : ''}
                <div class="etq-estado etq-estado-${(ETIQUETAS_DATA[i]?.estado||'').toLowerCase().replace(/ /g,'-')}">${d.estado}</div>
                ${d.asignado ? `<div class="etq-asignado">${d.asignado}</div>` : ''}
            </div>
        `).join('');

        const w = window.open('', '_blank', 'width=900,height=700');
        w.document.write(`<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Etiquetas — ${ETIQUETAS_SECCION}</title>
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; }
  body { font-family: 'Arial', sans-serif; background: #fff; }

  .hoja-header {
    padding: 12px 20px 8px;
    border-bottom: 2px solid #1e1b4b;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 16px;
  }
  .hoja-header h1 { font-size: 15px; color: #1e1b4b; font-weight: 700; }
  .hoja-header span { font-size: 11px; color: #6b7280; }

  .grid {
    display: grid;
    grid-template-columns: repeat(${cols}, 1fr);
    gap: 8px;
    padding: 0 16px 20px;
  }

  .etiqueta {
    border: 1.5px solid #d1d5db;
    border-radius: 8px;
    padding: 8px 8px 6px;
    display: flex;
    flex-direction: column;
    align-items: center;
    width: ${labelW};
    min-height: ${labelH};
    page-break-inside: avoid;
    overflow: hidden;
  }
  .etq-header {
    font-size: 7.5px;
    font-weight: 700;
    color: #1e1b4b;
    text-transform: uppercase;
    letter-spacing: .05em;
    margin-bottom: 4px;
  }
  .etq-qr {
    width: 90px;
    height: 90px;
    flex-shrink: 0;
  }
  .etq-nombre {
    font-size: 9px;
    font-weight: 700;
    color: #111827;
    text-align: center;
    margin-top: 5px;
    line-height: 1.2;
    word-break: break-word;
    max-width: 100%;
  }
  .etq-meta {
    font-size: 8px;
    color: #6b7280;
    text-align: center;
    margin-top: 2px;
    word-break: break-word;
    max-width: 100%;
  }
  .etq-serie {
    font-size: 7.5px;
    color: #9ca3af;
    font-family: monospace;
    text-align: center;
    margin-top: 2px;
  }
  .etq-estado {
    margin-top: 4px;
    font-size: 7.5px;
    font-weight: 700;
    padding: 2px 8px;
    border-radius: 99px;
    text-transform: uppercase;
    letter-spacing: .04em;
  }
  .etq-estado-disponible    { background: #d1fae5; color: #065f46; }
  .etq-estado-asignado      { background: #dbeafe; color: #1e40af; }
  .etq-estado-en-mantenimiento { background: #fef3c7; color: #92400e; }
  .etq-estado-dañado        { background: #fee2e2; color: #991b1b; }
  .etq-asignado {
    font-size: 7.5px;
    color: #374151;
    text-align: center;
    margin-top: 2px;
    word-break: break-word;
    max-width: 100%;
  }

  @media print {
    body { print-color-adjust: exact; -webkit-print-color-adjust: exact; }
    .hoja-header { position: fixed; top: 0; width: 100%; }
    .grid { margin-top: 50px; }
    @page { margin: 10mm; size: A4; }
  }
</style>
</head>
<body>
  <div class="hoja-header">
    <h1>${ETIQUETAS_SECCION}</h1>
    <span>${total} etiqueta${total !== 1 ? 's' : ''} · ${new Date().toLocaleDateString('es-MX')}</span>
  </div>
  <div class="grid">
    ${etiquetasHtml}
  </div>
  <script>window.onload = function(){ window.print(); };<\/script>
</body>
</html>`);
        w.document.close();
    }
};
</script>
@endpush

@endsection
