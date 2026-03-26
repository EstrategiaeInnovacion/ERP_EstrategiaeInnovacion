@extends('layouts.master')

@section('title', 'Programas y Páginas - Legal')

@section('content')

{{-- HEADER --}}
<div class="bg-white border-b border-slate-200 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-6 mb-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('legal.dashboard') }}" class="hover:text-amber-600 transition-colors">Panel Legal</a>
                <span>/</span>
                <span class="text-slate-700 font-medium">Programas y Páginas</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">Programas y Páginas</h1>
            <p class="text-slate-500 mt-1 text-sm">Acceso rápido a plataformas, sistemas y páginas de referencia del área legal.</p>
        </div>
        <button onclick="abrirModal('modalAgregar')"
            class="inline-flex items-center px-4 py-2 bg-slate-700 hover:bg-slate-800 text-white font-bold text-sm rounded-xl shadow-lg shadow-slate-200 hover:-translate-y-0.5 transition-all">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Añadir Programa / Página
        </button>
    </div>
</div>

{{-- ALERTAS --}}
@if(session('success'))
    <div class="mb-6 flex items-center gap-3 p-4 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <span class="text-sm font-medium">{{ session('success') }}</span>
    </div>
@endif

@if($errors->any())
    <div class="mb-6 flex items-start gap-3 p-4 rounded-xl bg-red-50 border border-red-100 text-red-700">
        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <ul class="text-sm font-medium space-y-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif

{{-- TABLA --}}
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    @if($paginas->isEmpty())
        <div class="flex flex-col items-center justify-center py-20 text-slate-400">
            <svg class="w-14 h-14 mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
            <p class="text-base font-semibold text-slate-500 mb-1">Sin programas registrados</p>
            <p class="text-sm text-slate-400">Añade el primer programa o página con el botón de arriba.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Nombre del Sistema</th>
                        <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">URL</th>
                        <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($paginas as $pagina)
                        <tr class="hover:bg-slate-50/70 transition-colors">
                            <td class="px-6 py-4 text-sm font-semibold text-slate-800">
                                {{ $pagina->nombre }}
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-500 max-w-xs">
                                <a href="{{ $pagina->url }}" target="_blank" rel="noopener noreferrer"
                                   class="inline-flex items-center gap-1.5 text-amber-600 hover:text-amber-800 font-medium transition-colors truncate max-w-full"
                                   title="{{ $pagina->url }}">
                                    <svg class="w-3.5 h-3.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/>
                                    </svg>
                                    <span class="truncate">{{ $pagina->url }}</span>
                                </a>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <div class="flex items-center justify-end gap-2">
                                    <button
                                        onclick="abrirEditar({{ $pagina->id }}, '{{ addslashes($pagina->nombre) }}', '{{ addslashes($pagina->url) }}')"
                                        class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-lg transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                        </svg>
                                        Editar
                                    </button>
                                    <form method="POST" action="{{ route('legal.programas.destroy', $pagina->id) }}"
                                          onsubmit="return confirm('¿Eliminar «{{ addslashes($pagina->nombre) }}»?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold text-red-600 bg-red-50 hover:bg-red-100 rounded-lg transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>

{{-- ==================== MODAL: AÑADIR ==================== --}}
<div id="modalAgregar" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="cerrarModal('modalAgregar')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md">
            <div class="flex items-center justify-between p-6 border-b border-slate-100">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Añadir Programa / Página</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Registra un sistema o página de referencia</p>
                </div>
                <button onclick="cerrarModal('modalAgregar')" class="p-2 rounded-xl hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form method="POST" action="{{ route('legal.programas.store') }}" class="p-6 space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">Nombre del Sistema <span class="text-red-500">*</span></label>
                    <input type="text" name="nombre" required placeholder="Ej. Portal SAT, IMSS Digital..."
                        class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-700 focus:border-slate-500 focus:ring-slate-500 text-sm py-2.5 px-3"
                        value="{{ old('nombre') }}">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">URL <span class="text-red-500">*</span></label>
                    <input type="url" name="url" required placeholder="https://..."
                        class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-700 focus:border-slate-500 focus:ring-slate-500 text-sm py-2.5 px-3"
                        value="{{ old('url') }}">
                    <p class="text-xs text-slate-400 mt-1">Debe incluir https:// o http://</p>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="cerrarModal('modalAgregar')"
                        class="flex-1 py-2.5 bg-white border border-slate-200 text-slate-600 font-bold text-sm rounded-xl hover:bg-slate-50 transition">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="flex-1 py-2.5 bg-slate-700 hover:bg-slate-800 text-white font-bold text-sm rounded-xl transition shadow-lg shadow-slate-200">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ==================== MODAL: EDITAR ==================== --}}
<div id="modalEditar" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="cerrarModal('modalEditar')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md">
            <div class="flex items-center justify-between p-6 border-b border-slate-100">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Editar Programa / Página</h2>
                    <p class="text-xs text-slate-500 mt-0.5">Modifica los datos del programa o página</p>
                </div>
                <button onclick="cerrarModal('modalEditar')" class="p-2 rounded-xl hover:bg-slate-100 text-slate-400 hover:text-slate-600 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            <form id="formEditar" method="POST" action="" class="p-6 space-y-4">
                @csrf
                @method('PUT')
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">Nombre del Sistema <span class="text-red-500">*</span></label>
                    <input type="text" id="editNombre" name="nombre" required placeholder="Ej. Portal SAT, IMSS Digital..."
                        class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-700 focus:border-slate-500 focus:ring-slate-500 text-sm py-2.5 px-3">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">URL <span class="text-red-500">*</span></label>
                    <input type="url" id="editUrl" name="url" required placeholder="https://..."
                        class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-700 focus:border-slate-500 focus:ring-slate-500 text-sm py-2.5 px-3">
                    <p class="text-xs text-slate-400 mt-1">Debe incluir https:// o http://</p>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="cerrarModal('modalEditar')"
                        class="flex-1 py-2.5 bg-white border border-slate-200 text-slate-600 font-bold text-sm rounded-xl hover:bg-slate-50 transition">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="flex-1 py-2.5 bg-slate-700 hover:bg-slate-800 text-white font-bold text-sm rounded-xl transition shadow-lg shadow-slate-200">
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    const baseUpdateUrl = '{{ url("legal/programas") }}';

    function abrirModal(id) {
        document.getElementById(id).classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function cerrarModal(id) {
        document.getElementById(id).classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function abrirEditar(id, nombre, url) {
        document.getElementById('editNombre').value = nombre;
        document.getElementById('editUrl').value    = url;
        document.getElementById('formEditar').action = `${baseUpdateUrl}/${id}`;
        abrirModal('modalEditar');
    }

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            ['modalAgregar', 'modalEditar'].forEach(id => {
                if (!document.getElementById(id).classList.contains('hidden')) {
                    cerrarModal(id);
                }
            });
        }
    });
</script>
@endpush
