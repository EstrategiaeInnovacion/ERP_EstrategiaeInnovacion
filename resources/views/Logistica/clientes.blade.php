@extends('layouts.erp')

@section('title', 'Administrar Clientes')

@section('content')
<div class="min-h-screen bg-slate-50 pb-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        {{-- HEADER --}}
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-8 relative overflow-hidden">
            <div class="absolute right-0 top-0 h-full w-1/2 bg-gradient-to-l from-indigo-50/80 to-transparent pointer-events-none"></div>
            <div class="relative z-10 flex items-center justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <span class="px-3 py-1 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold uppercase tracking-wider border border-indigo-200">
                            Logística
                        </span>
                    </div>
                    <h3 class="text-3xl font-bold text-slate-900 tracking-tight">Administrar Clientes</h3>
                    <p class="mt-1 text-slate-500">{{ $clientes->total() }} cliente{{ $clientes->total() !== 1 ? 's' : '' }} registrado{{ $clientes->total() !== 1 ? 's' : '' }}</p>
                </div>
                <div class="flex items-center gap-3">
                    <button onclick="abrirModal()"
                            class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 text-white rounded-xl font-semibold text-sm hover:bg-indigo-700 transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Nuevo Cliente
                    </button>
                    <a href="{{ route('logistica.index') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-xl font-semibold text-sm hover:bg-slate-50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        Volver
                    </a>
                </div>
            </div>
        </div>

        {{-- TABLA --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="border-b border-slate-200 bg-slate-50">
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">#</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Cliente</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Clave</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Ejecutivo Asignado</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Periodicidad</th>
                            <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Correos</th>
                            <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($clientes as $i => $c)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4 text-slate-400 font-mono text-xs">{{ $clientes->firstItem() + $i }}</td>
                                <td class="px-6 py-4 font-semibold text-slate-800">{{ $c->cliente }}</td>
                                <td class="px-6 py-4 text-slate-500 font-mono text-xs">{{ $c->clave ?? '—' }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $c->ejecutivoAsignado?->nombre ?? '—' }}</td>
                                <td class="px-6 py-4 text-slate-600">{{ $c->periodicidad_reporte ?? '—' }}</td>
                                <td class="px-6 py-4 text-slate-500 text-xs">{{ $c->correos_string ?: '—' }}</td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button data-id="{{ $c->id }}" data-record='@json($c->toArray())'
                                                onclick="editarCliente(this)"
                                                class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition" title="Editar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <button data-id="{{ $c->id }}"
                                                onclick="eliminarCliente(this.dataset.id)"
                                                class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition" title="Eliminar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-6 py-16 text-center text-slate-400">No hay clientes registrados.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($clientes->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">{{ $clientes->links() }}</div>
            @endif
        </div>

    </div>
</div>

{{-- MODAL --}}
<div id="modalCliente" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/40">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg mx-4">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <h3 class="text-lg font-bold text-slate-800" id="modalTitulo">Nuevo Cliente</h3>
            <button onclick="cerrarModal()" class="text-slate-400 hover:text-slate-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <form id="formCliente" class="p-6 space-y-4">
            <input type="hidden" id="clienteId">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Nombre del Cliente <span class="text-red-500">*</span></label>
                <input type="text" id="clienteNombre"
                       class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 uppercase" required>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Clave (referencia interna)</label>
                <input type="text" id="clienteClave" maxlength="50" placeholder="Ej. CLI-001"
                       class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Ejecutivo Asignado</label>
                <select id="clienteEjecutivo" class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    <option value="">— Sin asignar —</option>
                    @foreach($todosEjecutivos as $e)
                        <option value="{{ $e->id }}">{{ $e->nombre }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Periodicidad de Reporte</label>
                <select id="clientePeriodicidad" class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    <option value="Diario">Diario</option>
                    <option value="Semanal">Semanal</option>
                    <option value="Quincenal">Quincenal</option>
                    <option value="Mensual">Mensual</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-1">Correos (separados por coma)</label>
                <input type="text" id="clienteCorreos" placeholder="correo1@ejemplo.com, correo2@ejemplo.com"
                       class="w-full border border-slate-300 rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-300">
            </div>
            <div id="mensajeError" class="hidden text-sm text-red-600 bg-red-50 border border-red-200 rounded-lg px-4 py-2"></div>
        </form>
        <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-200">
            <button onclick="cerrarModal()" class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 text-sm font-semibold hover:bg-slate-50 transition">Cancelar</button>
            <button onclick="guardarCliente()" class="px-5 py-2 rounded-xl bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 transition">Guardar</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;
let editandoId = null;

function abrirModal(titulo = 'Nuevo Cliente') {
    editandoId = null;
    document.getElementById('modalTitulo').textContent = titulo;
    document.getElementById('clienteId').value = '';
    document.getElementById('clienteNombre').value = '';
    document.getElementById('clienteClave').value = '';
    document.getElementById('clientePeriodicidad').value = 'Diario';
    document.getElementById('clienteCorreos').value = '';
    const sel = document.getElementById('clienteEjecutivo');
    if (sel) sel.value = '';
    ocultarError();
    document.getElementById('modalCliente').classList.remove('hidden');
}

function editarCliente(btn) {
    const id   = btn.dataset.id;
    const data = JSON.parse(btn.dataset.record);
    editandoId = id;
    document.getElementById('modalTitulo').textContent = 'Editar Cliente';
    document.getElementById('clienteId').value = id;
    document.getElementById('clienteNombre').value = data.cliente ?? '';
    document.getElementById('clienteClave').value = data.clave ?? '';
    document.getElementById('clientePeriodicidad').value = data.periodicidad_reporte ?? 'Diario';
    const correos = Array.isArray(data.correos) ? data.correos.join(', ') : (data.correos ?? '');
    document.getElementById('clienteCorreos').value = correos;
    const sel = document.getElementById('clienteEjecutivo');
    if (sel) sel.value = data.ejecutivo_asignado_id ?? '';
    ocultarError();
    document.getElementById('modalCliente').classList.remove('hidden');
}

function cerrarModal() {
    document.getElementById('modalCliente').classList.add('hidden');
}

function ocultarError() {
    const el = document.getElementById('mensajeError');
    el.classList.add('hidden');
    el.textContent = '';
}

function mostrarError(msg) {
    const el = document.getElementById('mensajeError');
    el.textContent = msg;
    el.classList.remove('hidden');
}

async function guardarCliente() {
    const nombre = document.getElementById('clienteNombre').value.trim();
    if (!nombre) { mostrarError('El nombre del cliente es requerido.'); return; }

    const correoRaw = document.getElementById('clienteCorreos').value.trim();
    const correos = correoRaw ? correoRaw.split(',').map(e => e.trim()).filter(Boolean) : [];

    const payload = {
        cliente: nombre,
        clave: document.getElementById('clienteClave').value.trim() || null,
        periodicidad_reporte: document.getElementById('clientePeriodicidad').value,
        correos: JSON.stringify(correos),
    };

    const sel = document.getElementById('clienteEjecutivo');
    if (sel) payload.ejecutivo_asignado_id = sel.value || null;

    const url  = editandoId ? `/logistica/clientes/${editandoId}` : '/logistica/clientes';
    const method = editandoId ? 'PUT' : 'POST';

    try {
        const resp = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
            body: JSON.stringify(payload),
        });
        const json = await resp.json();
        if (!resp.ok) { mostrarError(json.message ?? 'Error al guardar.'); return; }
        window.location.reload();
    } catch (e) {
        mostrarError('Error de conexión.');
    }
}

async function eliminarCliente(id) {
    if (!confirm('¿Eliminar este cliente?')) return;
    try {
        const resp = await fetch(`/logistica/clientes/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF, 'Accept': 'application/json' },
        });
        const json = await resp.json();
        if (!resp.ok) { alert(json.message ?? 'No se pudo eliminar.'); return; }
        window.location.reload();
    } catch (e) {
        alert('Error de conexión.');
    }
}

</script>
@endpush
@endsection
