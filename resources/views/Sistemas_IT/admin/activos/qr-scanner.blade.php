@extends('layouts.master')

@section('title', 'Escáner QR — Activos IT')

@section('content')
<div class="min-h-screen bg-slate-50 pb-12">

    {{-- Header --}}
    <div class="bg-white border-b border-slate-200 mb-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center gap-3 mb-1">
                <a href="{{ route('admin.activos.index') }}"
                   class="text-slate-400 hover:text-indigo-600 transition-colors text-sm font-medium">Activos IT</a>
                <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-slate-600 text-sm font-medium">Escáner QR</span>
            </div>
            <div class="flex items-center gap-3">
                <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Escáner QR</h1>
            </div>
            <p class="text-slate-500 mt-1">Escanea el código QR de un dispositivo para asignarlo, prestarlo o registrar su devolución.</p>
        </div>
    </div>

    <div class="max-w-lg mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Mensajes de sesión --}}
        @if(session('error'))
        <div class="mb-4 bg-red-50 border border-red-200 rounded-2xl px-5 py-4 text-red-700 text-sm font-medium">
            {{ session('error') }}
        </div>
        @endif

        {{-- ====================== ESTADO 1: IDLE ====================== --}}
        <div id="state-idle" class="bg-white rounded-3xl shadow-sm border border-slate-200 p-10 text-center">
            <svg class="mx-auto h-14 w-14 text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                      d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z"/>
            </svg>
            <h3 class="text-lg font-bold text-slate-700 mb-1">Listo para escanear</h3>
            <p class="text-sm text-slate-500 mb-8">Activa la cámara y apunta al código QR del dispositivo.</p>
            <button id="btn-start-scan"
                    class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white font-bold text-sm rounded-xl hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
                Activar cámara
            </button>

            {{-- Entrada manual de UUID (para pruebas o si la cámara no funciona) --}}
            <div class="mt-6">
                <p class="text-xs text-slate-400 mb-2">¿Sin cámara? Ingresa el UUID manualmente:</p>
                <div class="flex gap-2">
                    <input id="manual-uuid" type="text" placeholder="UUID del dispositivo"
                           class="flex-1 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none transition">
                    <button id="btn-manual-lookup"
                            class="px-4 py-2 bg-slate-700 text-white text-sm font-semibold rounded-xl hover:bg-slate-800 transition">
                        Buscar
                    </button>
                </div>
            </div>
        </div>

        {{-- ====================== ESTADO 2: CÁMARA ====================== --}}
        <div id="state-camera" class="hidden">
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
                <div id="qr-reader" wire:ignore></div>
            </div>
            <p class="text-center text-xs text-slate-400 mt-3">Apunta la cámara al código QR del dispositivo.</p>
            <div class="mt-3 text-center">
                <button id="btn-cancel-scan"
                        class="px-5 py-2 bg-slate-100 text-slate-600 text-sm font-semibold rounded-xl hover:bg-slate-200 transition">
                    Cancelar
                </button>
            </div>
        </div>

        {{-- ====================== ESTADO 3: CARGANDO ====================== --}}
        <div id="state-loading" class="hidden bg-white rounded-3xl shadow-sm border border-slate-200 p-10 text-center">
            <div class="inline-block w-10 h-10 border-4 border-indigo-200 border-t-indigo-600 rounded-full animate-spin mb-4"></div>
            <p class="text-slate-500 text-sm">Buscando dispositivo…</p>
        </div>

        {{-- ====================== ESTADO 4: DISPOSITIVO ENCONTRADO ====================== --}}
        <div id="state-device" class="hidden bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">

            {{-- Info del dispositivo --}}
            <div id="device-header" class="px-8 pt-8 pb-5 border-b border-slate-100">
                <div class="flex items-center justify-between mb-1">
                    <h3 id="dev-name" class="text-xl font-bold text-slate-900"></h3>
                    <span id="dev-status-badge" class="px-3 py-1 rounded-full text-xs font-bold border"></span>
                </div>
                <p id="dev-meta" class="text-sm text-slate-500"></p>
                <p id="dev-assigned" class="text-sm text-slate-500 mt-1 hidden">
                    <span class="font-medium text-slate-700">Asignado a:</span>
                    <span id="dev-assigned-name"></span>
                </p>
            </div>

            {{-- Acciones --}}
            <div id="section-actions" class="px-8 py-6 space-y-3">

                {{-- Botón ASIGNAR / PRESTAR --}}
                <button id="btn-show-assign"
                        class="w-full flex items-center justify-center gap-2 px-5 py-3 bg-indigo-600 text-white font-bold text-sm rounded-xl hover:bg-indigo-700 transition shadow-md shadow-indigo-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Asignar / Prestar
                </button>

                {{-- Botón DEVOLVER --}}
                <button id="btn-return"
                        class="w-full flex items-center justify-center gap-2 px-5 py-3 bg-emerald-600 text-white font-bold text-sm rounded-xl hover:bg-emerald-700 transition shadow-md shadow-emerald-200">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                    </svg>
                    Registrar devolución
                </button>

                {{-- Aviso Dañado --}}
                <div id="banner-broken"
                     class="hidden text-center bg-red-50 border border-red-200 text-red-700 rounded-xl px-4 py-3 text-sm font-medium">
                    Este equipo está marcado como <strong>Dañado</strong> y no puede asignarse.
                </div>

                {{-- Aviso Mantenimiento --}}
                <div id="banner-maintenance"
                     class="hidden text-center bg-amber-50 border border-amber-200 text-amber-700 rounded-xl px-4 py-3 text-sm font-medium">
                    Este equipo está en <strong>Mantenimiento</strong> y no puede asignarse.
                </div>

            </div>

            {{-- Formulario ASIGNAR / PRESTAR --}}
            <div id="section-assign-form" class="hidden px-8 pb-8 border-t border-slate-100 pt-6 space-y-4">
                <h4 class="font-bold text-slate-700 text-sm">Datos de asignación / préstamo</h4>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Empleado <span class="text-red-500">*</span>
                    </label>
                    <select id="qr-empleado-id"
                            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none transition">
                        <option value="">— Seleccionar empleado —</option>
                        @foreach($empleados as $emp)
                        <option value="{{ $emp->id }}">
                            {{ $emp->nombre }}
                            @if($emp->area) — {{ $emp->area }}@endif
                        </option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Tipo de movimiento</label>
                    <select id="qr-tipo-movimiento"
                            class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none transition">
                        <option value="asignacion_fija">Asignación fija</option>
                        <option value="prestamo_temporal">Préstamo temporal</option>
                    </select>
                </div>

                <div id="field-fecha-devolucion" class="hidden">
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Fecha / hora de devolución <span class="text-red-500">*</span>
                    </label>
                    <input id="qr-fecha-devolucion" type="datetime-local"
                           class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none transition">
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">Notas <span class="text-xs font-normal text-slate-400">(opcional)</span></label>
                    <textarea id="qr-notas" rows="2" placeholder="Observaciones de entrega, condición del equipo…"
                              class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none transition resize-none"></textarea>
                </div>

                <p id="assign-error" class="hidden text-xs text-red-600 font-medium"></p>

                <div class="flex gap-3 pt-1">
                    <button id="btn-cancel-assign"
                            class="flex-1 px-4 py-2.5 bg-white border border-slate-200 text-slate-600 font-semibold text-sm rounded-xl hover:bg-slate-50 transition">
                        Cancelar
                    </button>
                    <button id="btn-confirm-assign"
                            class="flex-1 px-4 py-2.5 bg-indigo-600 text-white font-bold text-sm rounded-xl hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">
                        Confirmar
                    </button>
                </div>
            </div>

            {{-- Botón escanear otro (en pantalla de dispositivo) --}}
            <div class="px-8 pb-8">
                <button id="btn-scan-another"
                        class="w-full mt-2 px-5 py-2.5 bg-slate-100 text-slate-600 text-sm font-semibold rounded-xl hover:bg-slate-200 transition">
                    Escanear otro equipo
                </button>
                <div class="mt-3 text-center">
                    <a id="dev-detail-link" href="#" target="_blank"
                       class="text-xs text-indigo-500 hover:underline">Ver detalle del dispositivo →</a>
                </div>
            </div>
        </div>

        {{-- ====================== ESTADO 5: ÉXITO ====================== --}}
        <div id="state-success" class="hidden bg-white rounded-3xl shadow-sm border border-emerald-200 p-10 text-center">
            <svg class="mx-auto h-14 w-14 text-emerald-500 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h3 class="text-xl font-bold text-emerald-700 mb-2">¡Listo!</h3>
            <p id="success-msg" class="text-emerald-600 text-sm mb-8"></p>
            <button id="btn-scan-next"
                    class="w-full px-6 py-3 bg-indigo-600 text-white font-bold text-sm rounded-xl hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">
                Escanear otro equipo
            </button>
        </div>

        {{-- ====================== ESTADO 6: ERROR QR ====================== --}}
        <div id="state-error" class="hidden bg-white rounded-3xl shadow-sm border border-red-200 p-10 text-center">
            <svg class="mx-auto h-12 w-12 text-red-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <p id="error-msg" class="text-red-700 font-semibold text-sm mb-6"></p>
            <button id="btn-retry"
                    class="px-6 py-3 bg-indigo-600 text-white font-bold text-sm rounded-xl hover:bg-indigo-700 transition shadow-md">
                Intentar de nuevo
            </button>
        </div>

    </div>
</div>

@push('scripts')
{{-- html5-qrcode: misma librería que usa AuditoriaActivos --}}
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>

<script>
/* =====================================================================
   Escáner QR — Activos IT (ERP)
   Misma lógica que AuditoriaActivos/QrScanner.php / qr-scanner.blade.php
   ===================================================================== */

const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

// Rutas generadas desde Blade para no hard-codear URLs
const API_LOOKUP  = (uuid) => `/admin/activos-api/dispositivo/${uuid}`;
const API_ASSIGN  = (uuid) => `/admin/activos-api/qr-asignar/${uuid}`;
const API_RETURN  = (uuid) => `/admin/activos-api/qr-devolver/${uuid}`;
const SHOW_URL    = (uuid) => `/admin/activos/${uuid}`;

// --- Referencias DOM ---
const $idle       = document.getElementById('state-idle');
const $camera     = document.getElementById('state-camera');
const $loading    = document.getElementById('state-loading');
const $device     = document.getElementById('state-device');
const $success    = document.getElementById('state-success');
const $errState   = document.getElementById('state-error');

let scanner       = null;
let currentUuid   = null;
let currentStatus = null;

// --- Utilidad: mostrar un solo estado ---
function showState(stateEl) {
    [$idle, $camera, $loading, $device, $success, $errState].forEach(s => {
        if (s) s.classList.add('hidden');
    });
    if (stateEl) stateEl.classList.remove('hidden');
}

// --- Iniciar cámara (misma configuración que AuditoriaActivos) ---
function startCamera() {
    showState($camera);
    if (!scanner) {
        scanner = new Html5QrcodeScanner(
            'qr-reader',
            { fps: 10, qrbox: { width: 250, height: 250 } },
            false
        );
    }
    scanner.render(onScanSuccess, () => {});
}

function stopCamera() {
    if (scanner) {
        scanner.clear().catch(() => {});
    }
}

// --- Callback de escaneo exitoso ---
function onScanSuccess(decodedText) {
    stopCamera();
    // Extraer UUID del texto (puede ser una URL completa o solo el UUID)
    const parsed = new URL(decodedText, 'https://x');
    const uuid   = parsed.pathname.split('/').filter(Boolean).pop() || decodedText.trim();
    lookupDevice(uuid);
}

// --- Buscar dispositivo en el API ---
async function lookupDevice(uuid) {
    currentUuid = uuid;
    showState($loading);

    try {
        const res  = await fetch(API_LOOKUP(uuid), { headers: { 'Accept': 'application/json' } });
        const data = await res.json();

        if (!res.ok || data.error) {
            showError(data.error ?? 'No se pudo obtener información del dispositivo.');
            return;
        }

        renderDevice(data);

    } catch (e) {
        showError('Error de conexión al buscar el dispositivo.');
    }
}

// --- Renderizar la tarjeta del dispositivo ---
function renderDevice(data) {
    currentStatus = data.status;

    document.getElementById('dev-name').textContent   = data.name;
    document.getElementById('dev-meta').textContent   = `${data.type_label} · ${data.brand} ${data.model} · S/N: ${data.serial}`;
    document.getElementById('dev-detail-link').href   = SHOW_URL(data.uuid);

    // Badge de estado
    const badge = document.getElementById('dev-status-badge');
    badge.textContent = data.status_label;
    badge.className   = 'px-3 py-1 rounded-full text-xs font-bold border '
        + statusBadgeClass(data.status);

    // Asignado a
    const divAssigned = document.getElementById('dev-assigned');
    if (data.assigned_to) {
        document.getElementById('dev-assigned-name').textContent = data.assigned_to;
        divAssigned.classList.remove('hidden');
    } else {
        divAssigned.classList.add('hidden');
    }

    // Mostrar/ocultar botones según estado
    const btnAssign      = document.getElementById('btn-show-assign');
    const btnReturn      = document.getElementById('btn-return');
    const bannerBroken   = document.getElementById('banner-broken');
    const bannerMaint    = document.getElementById('banner-maintenance');

    btnAssign.classList.add('hidden');
    btnReturn.classList.add('hidden');
    bannerBroken.classList.add('hidden');
    bannerMaint.classList.add('hidden');

    if (data.status === 'available') {
        btnAssign.classList.remove('hidden');
    } else if (data.status === 'assigned') {
        btnAssign.classList.remove('hidden');   // Reasignar
        btnReturn.classList.remove('hidden');
    } else if (data.status === 'maintenance') {
        bannerMaint.classList.remove('hidden');
        btnReturn.classList.remove('hidden');   // Puede devolverse de mantenimiento
    } else if (data.status === 'broken') {
        bannerBroken.classList.remove('hidden');
    }

    // Ocultar formulario de asignación si estaba visible
    document.getElementById('section-assign-form').classList.add('hidden');
    document.getElementById('section-actions').classList.remove('hidden');

    showState($device);
}

function statusBadgeClass(status) {
    return {
        available:   'bg-emerald-100 text-emerald-700 border-emerald-200',
        assigned:    'bg-sky-100 text-sky-700 border-sky-200',
        maintenance: 'bg-amber-100 text-amber-700 border-amber-200',
        broken:      'bg-red-100 text-red-700 border-red-200',
    }[status] ?? 'bg-slate-100 text-slate-600 border-slate-200';
}

// --- Mostrar formulario de asignación ---
document.getElementById('btn-show-assign').addEventListener('click', () => {
    document.getElementById('section-actions').classList.add('hidden');
    document.getElementById('section-assign-form').classList.remove('hidden');
    document.getElementById('assign-error').classList.add('hidden');
});

// --- Toggle fecha devolución ---
document.getElementById('qr-tipo-movimiento').addEventListener('change', function () {
    const fieldFecha = document.getElementById('field-fecha-devolucion');
    if (this.value === 'prestamo_temporal') {
        fieldFecha.classList.remove('hidden');
    } else {
        fieldFecha.classList.add('hidden');
    }
});

// --- Cancelar asignación ---
document.getElementById('btn-cancel-assign').addEventListener('click', () => {
    document.getElementById('section-assign-form').classList.add('hidden');
    document.getElementById('section-actions').classList.remove('hidden');
});

// --- Confirmar asignación ---
document.getElementById('btn-confirm-assign').addEventListener('click', async () => {
    const empleadoId     = document.getElementById('qr-empleado-id').value;
    const tipoMovimiento = document.getElementById('qr-tipo-movimiento').value;
    const fechaDevol     = document.getElementById('qr-fecha-devolucion').value;
    const notas          = document.getElementById('qr-notas').value;
    const errEl          = document.getElementById('assign-error');

    if (!empleadoId) {
        errEl.textContent = 'Selecciona un empleado.';
        errEl.classList.remove('hidden');
        return;
    }
    if (tipoMovimiento === 'prestamo_temporal' && !fechaDevol) {
        errEl.textContent = 'Indica la fecha/hora de devolución para el préstamo.';
        errEl.classList.remove('hidden');
        return;
    }
    errEl.classList.add('hidden');

    const body = new URLSearchParams({
        _token:           CSRF,
        empleado_id:      empleadoId,
        tipo_movimiento:  tipoMovimiento,
        fecha_devolucion: fechaDevol,
        notas:            notas,
    });

    try {
        const res  = await fetch(API_ASSIGN(currentUuid), { method: 'POST', body, headers: { 'Accept': 'application/json' } });
        const data = await res.json();

        if (!res.ok || data.error) {
            errEl.textContent = data.error ?? 'No se pudo registrar la asignación.';
            errEl.classList.remove('hidden');
            return;
        }

        showSuccessMsg(data.message);

    } catch (e) {
        errEl.textContent = 'Error de conexión. Intenta de nuevo.';
        errEl.classList.remove('hidden');
    }
});

// --- Devolver dispositivo ---
document.getElementById('btn-return').addEventListener('click', async () => {
    if (!confirm('¿Confirmar devolución del dispositivo?')) return;

    try {
        const body = new URLSearchParams({ _token: CSRF });
        const res  = await fetch(API_RETURN(currentUuid), { method: 'POST', body, headers: { 'Accept': 'application/json' } });
        const data = await res.json();

        if (!res.ok || data.error) {
            showError(data.error ?? 'No se pudo registrar la devolución.');
            return;
        }

        showSuccessMsg(data.message);

    } catch (e) {
        showError('Error de conexión. Intenta de nuevo.');
    }
});

// --- Éxito ---
function showSuccessMsg(msg) {
    document.getElementById('success-msg').textContent = msg;
    showState($success);
}

// --- Error ---
function showError(msg) {
    document.getElementById('error-msg').textContent = msg;
    showState($errState);
}

// --- Reset completo → volver a idle ---
function resetToIdle() {
    currentUuid   = null;
    currentStatus = null;
    document.getElementById('qr-empleado-id').value     = '';
    document.getElementById('qr-tipo-movimiento').value  = 'asignacion_fija';
    document.getElementById('qr-fecha-devolucion').value = '';
    document.getElementById('qr-notas').value            = '';
    document.getElementById('field-fecha-devolucion').classList.add('hidden');
    document.getElementById('manual-uuid').value         = '';
    showState($idle);
}

// --- Botones de navegación ---
document.getElementById('btn-start-scan')  .addEventListener('click', startCamera);
document.getElementById('btn-cancel-scan') .addEventListener('click', () => { stopCamera(); resetToIdle(); });
document.getElementById('btn-scan-another').addEventListener('click', () => { resetToIdle(); });
document.getElementById('btn-scan-next')   .addEventListener('click', () => { resetToIdle(); });
document.getElementById('btn-retry')       .addEventListener('click', () => { resetToIdle(); });

// --- Búsqueda manual por UUID ---
document.getElementById('btn-manual-lookup').addEventListener('click', () => {
    const uuid = document.getElementById('manual-uuid').value.trim();
    if (uuid) lookupDevice(uuid);
});
document.getElementById('manual-uuid').addEventListener('keydown', (e) => {
    if (e.key === 'Enter') document.getElementById('btn-manual-lookup').click();
});
</script>
@endpush

@endsection
