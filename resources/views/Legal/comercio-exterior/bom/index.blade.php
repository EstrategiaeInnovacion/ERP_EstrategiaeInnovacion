@extends('layouts.erp')

@section('title', 'Análisis de Origen T-MEC – BOMs')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- Inyectar URL de la ruta para que bom.js pueda usarla sin Blade --}}
    <script>window.BOM_STORE_URL = '{{ route('legal.ce.bom.store') }}';</script>

    {{-- Header --}}
    <div class="mb-8 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-400 mb-1">
                <a href="{{ route('legal.dashboard') }}" class="hover:text-indigo-600 transition">Legal</a>
                <span>/</span>
                <span class="text-slate-600">Análisis de Origen T-MEC</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">BOMs de Análisis</h1>
            <p class="text-sm text-slate-500 mt-1">Bill of Materials para análisis de origen T-MEC/USMCA.</p>
        </div>
        <div class="flex gap-3">
            <a href="{{ route('legal.ce.catalogo.index') }}"
               class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 text-slate-600 text-sm font-medium rounded-xl hover:bg-slate-50 transition">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
                Catálogo de Reglas
            </a>
            <button id="bom-btn-nuevo"
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo BOM
            </button>
        </div>
    </div>

    {{-- Tabla de BOMs --}}
    @if($boms->count() > 0)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Clave</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Nombre</th>
                        <th class="px-6 py-3 text-center text-xs font-semibold text-slate-500 uppercase tracking-wider">Ítems</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Archivo</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-slate-500 uppercase tracking-wider">Creado</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-500 uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($boms as $bom)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-6 py-4">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-lg bg-indigo-50 text-indigo-700 text-xs font-mono font-semibold">
                                {{ $bom->clave }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm font-medium text-slate-900">
                            {{ $bom->nombre ?: '—' }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="text-sm font-semibold text-slate-700">{{ $bom->items()->count() }}</span>
                        </td>
                        <td class="px-6 py-4 text-xs text-slate-500 truncate max-w-xs">
                            @if($bom->archivo_original)
                                <span class="inline-flex items-center gap-1">
                                    <svg class="w-3 h-3 text-emerald-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    {{ $bom->archivo_original }}
                                </span>
                            @else
                                <span class="text-slate-300">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-500">
                            {{ $bom->created_at->format('d/m/Y H:i') }}
                        </td>
                        <td class="px-6 py-4 text-right">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('legal.ce.bom.show', $bom) }}"
                                   class="inline-flex items-center px-3 py-1.5 bg-indigo-50 text-indigo-700 text-xs font-semibold rounded-lg hover:bg-indigo-100 transition">
                                    Ver BOM
                                </a>
                                <a href="{{ route('legal.ce.origen.show', $bom) }}"
                                   class="inline-flex items-center px-3 py-1.5 bg-emerald-50 text-emerald-700 text-xs font-semibold rounded-lg hover:bg-emerald-100 transition">
                                    Análisis
                                </a>
                                <form method="POST" action="{{ route('legal.ce.bom.destroy', $bom) }}"
                                      onsubmit="return confirm('¿Eliminar este BOM? Esta acción es irreversible.')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 bg-red-50 text-red-600 text-xs font-semibold rounded-lg hover:bg-red-100 transition">
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
        <div class="mt-4">{{ $boms->links() }}</div>
    @else
        <div class="bg-white rounded-2xl border border-slate-200 border-dashed p-16 text-center">
            <div class="w-14 h-14 bg-indigo-50 rounded-2xl flex items-center justify-center mx-auto mb-4">
                <svg class="w-7 h-7 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <h3 class="text-lg font-semibold text-slate-800 mb-1">Sin BOMs registrados</h3>
            <p class="text-sm text-slate-500 mb-6">Crea el primer BOM cargando tu archivo Excel o manualmente.</p>
            <button id="bom-btn-nuevo-empty"
                    onclick="document.getElementById('bom-btn-nuevo').click()"
                    class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Nuevo BOM
            </button>
        </div>
    @endif

</div>

{{-- ═══════════════════════════════════════════════════════════════
     Overlay oscuro (fuera del contenedor)
════════════════════════════════════════════════════════════════ --}}
<div id="bom-overlay" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm z-40"></div>

{{-- ═══════════════════════════════════════════════════════════════
     Modal — Nuevo BOM
════════════════════════════════════════════════════════════════ --}}
<div id="bom-modal" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4 pointer-events-none">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md overflow-hidden pointer-events-auto">

        {{-- Header degradado --}}
        <div class="bg-gradient-to-r from-indigo-600 to-blue-500 px-6 py-4 flex items-center justify-between">
            <div>
                <h3 class="text-white font-bold text-base">Nuevo BOM</h3>
                <p class="text-indigo-200 text-xs mt-0.5">Carga un Excel o crea manualmente</p>
            </div>
            <button type="button" class="bom-modal-close text-white/70 hover:text-white transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="p-6 space-y-5">

            {{-- Nombre (opcional) --}}
            <div>
                <label for="bom-nombre" class="block text-sm font-medium text-slate-700 mb-1">
                    Nombre del BOM <span class="text-slate-400 font-normal">(opcional)</span>
                </label>
                <input id="bom-nombre" type="text"
                       placeholder="Ej. Motor V6 2026 – Rev A"
                       class="w-full rounded-xl border-slate-300 text-sm focus:border-indigo-400 focus:ring-indigo-400">
            </div>

            {{-- Drop zone --}}
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-1">
                    Archivo Excel
                    <span class="text-slate-400 font-normal">(.xlsx o .csv)</span>
                </label>
                <div id="bom-drop-zone"
                     class="border-2 border-dashed border-slate-300 rounded-xl p-8 text-center cursor-pointer transition hover:border-indigo-400 hover:bg-indigo-50">
                    <input type="file" id="bom-file-input" accept=".xlsx,.xls,.csv" class="hidden">

                    {{-- Estado inicial --}}
                    <div id="bom-drop-text">
                        <svg class="w-10 h-10 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <p class="text-sm font-medium text-slate-500">Arrastra el Excel aquí o <span class="text-indigo-600 font-semibold">haz clic</span></p>
                        <p class="text-xs text-slate-400 mt-1">.xlsx, .csv — Estructura BOM estándar (20 columnas)</p>
                    </div>

                    {{-- Estado archivo seleccionado --}}
                    <div id="bom-file-info" class="hidden">
                        <svg class="w-10 h-10 text-indigo-400 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p id="bom-file-name" class="text-sm font-semibold text-slate-800"></p>
                        <p id="bom-file-rows" class="text-xs text-indigo-600 font-semibold mt-1"></p>
                        <p class="text-xs text-slate-400 mt-1">Clic para cambiar el archivo</p>
                    </div>
                </div>
            </div>

            {{-- Hint estructura --}}
            <div class="flex gap-2 p-3 bg-amber-50 border border-amber-200 rounded-xl">
                <svg class="w-4 h-4 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-xs text-amber-700">
                    <strong>Estructura esperada:</strong> hasta 2 filas de encabezado, datos desde la fila 3.
                    20 columnas: Finished Goods (A–E) + Raw Material (F–O) + Análisis (P–T).
                </p>
            </div>

            {{-- Barra de progreso --}}
            <div id="bom-progress" class="hidden h-1.5 bg-slate-200 rounded-full overflow-hidden">
                <div id="bom-progress-bar" class="h-full bg-gradient-to-r from-indigo-500 to-blue-400 rounded-full transition-all duration-300" style="width:0%"></div>
            </div>

            {{-- Botones --}}
            <div class="flex gap-3 pt-1">
                <button type="button" class="bom-modal-close flex-1 py-2.5 rounded-xl border border-slate-200 text-slate-600 text-sm font-medium hover:bg-slate-50 transition">
                    Cancelar
                </button>
                <button id="bom-btn-procesar" type="button" disabled
                        class="flex-1 py-2.5 rounded-xl bg-indigo-600 hover:bg-indigo-700 disabled:opacity-40 disabled:cursor-not-allowed text-white text-sm font-semibold transition">
                    Cargar BOM
                </button>
            </div>

        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    /* Drop zone dragging state */
    #bom-drop-zone.bom-dragover {
        border-color: #4f46e5;
        background-color: #eef2ff;
    }
</style>
@endpush

@push('scripts')
@vite('resources/js/ComercioExterior/bom.js')
@endpush
