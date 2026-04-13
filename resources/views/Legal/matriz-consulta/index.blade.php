@extends('layouts.master')

@section('title', 'Matriz de Consulta - Legal')

@push('styles')
<style>
    .tipo-badge { @apply inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold; }
    .tipo-pdf    { background:#fee2e2; color:#991b1b; }
    .tipo-excel  { background:#d1fae5; color:#065f46; }
    .tipo-word   { background:#dbeafe; color:#1e40af; }
    .tipo-imagen { background:#fef3c7; color:#92400e; }
    .tipo-otro   { background:#f1f5f9; color:#475569; }
</style>
@endpush

@section('content')

{{-- HEADER --}}
<div class="bg-white border-b border-slate-200 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-6 mb-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('legal.dashboard') }}" class="hover:text-amber-600 transition-colors">Panel Legal</a>
                <span>/</span>
                <span class="text-slate-700 font-medium">Matriz de Consulta</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">Matriz de Consulta</h1>
            <p class="text-slate-500 mt-1 text-sm">Registro y seguimiento de consultas jurídicas por empresa.</p>
        </div>
        <div class="flex gap-3 flex-wrap">
            <a href="{{ route('legal.categorias.index') }}"
               class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 text-slate-600 font-semibold text-sm rounded-xl hover:bg-slate-50 hover:border-amber-200 hover:text-amber-600 transition shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                Gestionar Categorías
            </a>
            <button onclick="abrirModal('modalAgregarProyecto')"
                class="inline-flex items-center px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold text-sm rounded-xl shadow-lg shadow-amber-200 hover:-translate-y-0.5 transition-all">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                </svg>
                Añadir Proyecto
            </button>
        </div>
    </div>
</div>

{{-- ALERTAS --}}
@if(session('success'))
    <div class="mb-6 flex items-center gap-3 p-4 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span class="text-sm font-medium">{{ session('success') }}</span>
    </div>
@endif

{{-- FILTROS Y PESTAÑAS --}}
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm mb-6">
    {{-- Pestañas --}}
    <div class="border-b border-slate-200 px-6 pt-4">
        <nav class="flex gap-1" aria-label="Tabs">
            @php
                $tipoActual = request('tipo', 'todos');
                $counts = [
                    'todos' => $proyectos->count(),
                    'escritos' => $proyectos->where('tipo', 'escritos')->count(),
                    'consulta' => $proyectos->where('tipo', 'consulta')->count(),
                    'ambos' => $proyectos->where('tipo', 'ambos')->count(),
                ];
            @endphp
            <a href="{{ route('legal.matriz.index', array_merge(request()->except('tipo'), ['tipo' => 'todos'])) }}"
               class="px-4 py-3 text-sm font-semibold border-b-2 transition-colors {{ $tipoActual === 'todos' ? 'border-amber-500 text-amber-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                Todos <span class="ml-1 text-xs bg-slate-100 px-2 py-0.5 rounded-full">{{ $counts['todos'] }}</span>
            </a>
            <a href="{{ route('legal.matriz.index', array_merge(request()->except('tipo'), ['tipo' => 'escritos'])) }}"
               class="px-4 py-3 text-sm font-semibold border-b-2 transition-colors {{ $tipoActual === 'escritos' ? 'border-purple-500 text-purple-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                📝 Escritos <span class="ml-1 text-xs bg-slate-100 px-2 py-0.5 rounded-full">{{ $counts['escritos'] }}</span>
            </a>
            <a href="{{ route('legal.matriz.index', array_merge(request()->except('tipo'), ['tipo' => 'consulta'])) }}"
               class="px-4 py-3 text-sm font-semibold border-b-2 transition-colors {{ $tipoActual === 'consulta' ? 'border-blue-500 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                💬 Consultas <span class="ml-1 text-xs bg-slate-100 px-2 py-0.5 rounded-full">{{ $counts['consulta'] }}</span>
            </a>
            <a href="{{ route('legal.matriz.index', array_merge(request()->except('tipo'), ['tipo' => 'ambos'])) }}"
               class="px-4 py-3 text-sm font-semibold border-b-2 transition-colors {{ $tipoActual === 'ambos' ? 'border-emerald-500 text-emerald-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                🔄 Ambos <span class="ml-1 text-xs bg-slate-100 px-2 py-0.5 rounded-full">{{ $counts['ambos'] }}</span>
            </a>
        </nav>
    </div>

    {{-- Filtros --}}
    <form method="GET" action="{{ route('legal.matriz.index') }}" class="p-4">
        @if(request('tipo'))
            <input type="hidden" name="tipo" value="{{ request('tipo') }}">
        @endif
        <div class="flex flex-col sm:flex-row gap-3 items-end">
            <div class="flex-1">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">Buscar</label>
                <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Empresa o consulta..."
                    class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-2.5 px-3">
            </div>
            <div class="flex-1">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">Empresa</label>
                <select name="empresa" class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-2.5">
                    <option value="">Todas las empresas</option>
                    @foreach($empresas as $emp)
                        <option value="{{ $emp }}" {{ request('empresa') === $emp ? 'selected' : '' }}>{{ $emp }}</option>
                    @endforeach
                </select>
            </div>
            <div class="flex-1">
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">Categoría</label>
                <select name="categoria_id" class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-2.5">
                    <option value="">Todas las categorías</option>
                    @foreach($categorias as $cat)
                        <option value="{{ $cat->id }}" {{ request('categoria_id') == $cat->id ? 'selected' : '' }}>
                            {{ $cat->nombre }}
                        </option>
                        @foreach($cat->subcategorias as $sub)
                            <option value="{{ $sub->id }}" {{ request('categoria_id') == $sub->id ? 'selected' : '' }}>
                                &nbsp;&nbsp;&nbsp;↳ {{ $sub->nombre }}
                            </option>
                        @endforeach
                    @endforeach
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-bold text-sm rounded-xl transition shadow-sm">
                    Filtrar
                </button>
                @if(request('empresa') || request('categoria_id') || request('buscar') || request('tipo'))
                    <a href="{{ route('legal.matriz.index') }}" class="px-4 py-2.5 bg-white border border-slate-200 text-slate-600 font-semibold text-sm rounded-xl hover:bg-slate-50 transition shadow-sm">
                        Limpiar
                    </a>
                @endif
            </div>
        </div>
    </form>
</div>

{{-- TABLA --}}
<div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
    @if($proyectos->isEmpty())
        <div class="text-center py-20 text-slate-400">
            <svg class="w-12 h-12 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <p class="font-semibold text-slate-500">No hay proyectos registrados</p>
            <p class="text-sm mt-1">Usa "Añadir Proyecto" para comenzar.</p>
        </div>
    @else
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200 text-left">
                        <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wide">Nombre del Proyecto</th>
                        <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wide">Tipo</th>
                        <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wide">Categoría</th>
                        <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wide">Consulta</th>
                        <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wide">Resultado</th>
                        <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wide text-center">Recursos</th>
                        <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wide text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($proyectos as $proyecto)
                    <tr class="hover:bg-amber-50/40 transition-colors group">
                        <td class="px-5 py-4 font-semibold text-slate-800 whitespace-nowrap">
                            {{ $proyecto->empresa ?? '—' }}
                        </td>
                        <td class="px-5 py-4">
                            @if($proyecto->tipo === 'consulta')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-blue-100 text-blue-800">
                                    Consulta
                                </span>
                            @elseif($proyecto->tipo === 'escritos')
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-purple-100 text-purple-800">
                                    Escritos
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-800">
                                    Ambos
                                </span>
                            @endif
                        </td>
                        <td class="px-5 py-4">
                            @if($proyecto->categoria?->parent)
                                <span class="text-xs text-slate-400">{{ $proyecto->categoria->parent->nombre }}&nbsp;/&nbsp;</span>
                            @endif
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-semibold bg-amber-100 text-amber-800">
                                {{ $proyecto->categoria?->nombre ?? '—' }}
                            </span>
                        </td>
                        <td class="px-5 py-4 text-slate-600 max-w-xs">
                            <div class="line-clamp-3">{{ $proyecto->consulta }}</div>
                        </td>
                        <td class="px-5 py-4 text-slate-600 max-w-xs">
                            <div class="line-clamp-3">{{ $proyecto->resultado }}</div>
                        </td>
                        <td class="px-5 py-4 text-center">
                            <button onclick="verExpediente({{ $proyecto->id }})"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-amber-50 hover:bg-amber-100 text-amber-700 font-semibold text-xs rounded-lg border border-amber-200 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                Ver ({{ $proyecto->archivos_count ?? $proyecto->archivos->count() }})
                            </button>
                        </td>
                        <td class="px-5 py-4 text-center">
                            <div class="flex items-center justify-center gap-1.5">
                                <button onclick="abrirEditar({{ $proyecto->id }})"
                                    class="inline-flex items-center px-2.5 py-1.5 text-xs font-semibold text-slate-600 hover:bg-slate-100 rounded-lg border border-transparent hover:border-slate-200 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                                    </svg>
                                </button>
                                <form action="{{ route('legal.matriz.destroy', $proyecto->id) }}" method="POST"
                                      onsubmit="return confirm('¿Eliminar el proyecto de {{ addslashes($proyecto->empresa) }}? Se borrarán también sus archivos.')">
                                    @csrf @method('DELETE')
                                    <button type="submit"
                                        class="inline-flex items-center px-2.5 py-1.5 text-xs font-semibold text-red-600 hover:bg-red-50 rounded-lg border border-transparent hover:border-red-200 transition">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        </div>
    @endif
</div>

{{-- ==================== MODAL: AÑADIR PROYECTO ==================== --}}
<div id="modalAgregarProyecto" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="cerrarModal('modalAgregarProyecto')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 overflow-y-auto">
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl my-8">
            {{-- Header --}}
            <div class="flex items-center justify-between p-6 border-b border-slate-100">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Añadir Proyecto</h2>
                    <p class="text-sm text-slate-500 mt-0.5">Registra una nueva consulta en la matriz.</p>
                </div>
                <button onclick="cerrarModal('modalAgregarProyecto')" class="p-2 rounded-xl hover:bg-slate-100 text-slate-400 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            {{-- Body --}}
            <form id="formAgregarProyecto" action="{{ route('legal.matriz.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-5">
                @csrf

                <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">Nombre del Proyecto <span class="text-red-500">*</span></label>
                        <input type="text" name="empresa" required placeholder="Nombre del proyecto"
                            class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-2.5">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">Cliente</label>
                        <input type="text" name="cliente" placeholder="Nombre del cliente"
                            class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-2.5">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">Tipo <span class="text-red-500">*</span></label>
                        <select name="tipo" id="tipoSelect" required onchange="cambiarCamposTipo()"
                            class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-2.5">
                            <option value="consulta">Consulta</option>
                            <option value="escritos">Escritos</option>
                            <option value="ambos">Ambos</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">Categoría *</label>
                        <div class="relative">
                            <select name="categoria_id" id="nuevaCategoria" required
                                class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-2.5 pr-10">
                                <option value="">Selecciona una categoría</option>
                                @foreach($categorias as $cat)
                                    <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                                @endforeach
                                <option value="__nueva__">+ Crear nueva categoría</option>
                            </select>
                            <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-3 text-slate-500">
                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </div>
                        </div>
                        <div id="nuevaCategoriaInput" class="hidden mt-2">
                            <input type="text" name="nueva_categoria_nombre" 
                                placeholder="Nombre de la nueva categoría"
                                class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-2.5">
                            <p class="text-xs text-amber-600 mt-1">Se creará automáticamente al guardar el proyecto</p>
                        </div>
                    </div>
                </div>

                {{-- Campos condicionales según tipo --}}
                <div id="camposConsulta" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">Consulta</label>
                        <textarea name="consulta" rows="3" placeholder="Descripción detallada de la consulta..."
                            class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-2.5"></textarea>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">Resultado <span class="text-slate-400 font-normal">(opcional)</span></label>
                        <textarea name="resultado" rows="3" placeholder="Resultado o resolución de la consulta..."
                            class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-2.5"></textarea>
                    </div>
                </div>

                <div id="camposEscritos" class="hidden space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">Detalles</label>
                        <textarea name="detalles" rows="4" placeholder="Detalles del escrito o documento..."
                            class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-2.5"></textarea>
                    </div>
                    
                    {{-- Sección: Archivos --}}
                    <div class="border border-slate-200 rounded-2xl p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-bold text-slate-700 flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                </svg>
                                Archivos del sistema
                            </p>
                            <span class="text-xs text-slate-400">PDF, Word, Excel, Imágenes</span>
                        </div>

                        <div id="archivosContainer" class="space-y-2"></div>

                        <button type="button" onclick="agregarArchivoRow()"
                            class="w-full flex items-center justify-center gap-2 py-2 border border-dashed border-amber-300 text-amber-600 rounded-xl text-sm font-semibold hover:bg-amber-50 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Añadir archivo
                        </button>
                    </div>
                </div>

                <div id="camposAmbos" class="hidden space-y-4">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">Consulta</label>
                            <textarea name="consulta" rows="3" placeholder="Descripción detallada de la consulta..."
                                class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-2.5"></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">Resultado</label>
                            <textarea name="resultado" rows="3" placeholder="Resultado o resolución..."
                                class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-2.5"></textarea>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">Detalles</label>
                        <textarea name="detalles" rows="2" placeholder="Detalles adicionales..."
                            class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-2.5"></textarea>
                    </div>
                    <div class="border border-slate-200 rounded-2xl p-4 space-y-3">
                        <div class="flex items-center justify-between">
                            <p class="text-sm font-bold text-slate-700 flex items-center gap-2">
                                <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/>
                                </svg>
                                Archivos del sistema
                            </p>
                        </div>
                        <div id="archivosContainerAmbos" class="space-y-2"></div>
                        <button type="button" onclick="agregarArchivoRowAmbos()"
                            class="w-full flex items-center justify-center gap-2 py-2 border border-dashed border-amber-300 text-amber-600 rounded-xl text-sm font-semibold hover:bg-amber-50 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Añadir archivo
                        </button>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="cerrarModal('modalAgregarProyecto')"
                        class="flex-1 py-3 bg-white border border-slate-200 text-slate-600 font-bold text-sm rounded-xl hover:bg-slate-50 transition">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="flex-1 py-3 bg-amber-600 hover:bg-amber-700 text-white font-bold text-sm rounded-xl transition shadow-lg shadow-amber-200">
                        Guardar Proyecto
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ==================== MODAL: EDITAR PROYECTO ==================== --}}
<div id="modalEditarProyecto" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="cerrarModal('modalEditarProyecto')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 overflow-y-auto">
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl my-8">
            <div class="flex items-center justify-between p-6 border-b border-slate-100">
                <div>
                    <h2 class="text-xl font-bold text-slate-900">Editar Proyecto</h2>
                    <p class="text-sm text-slate-500 mt-0.5" id="editSubtitulo">Modifica los datos de la consulta.</p>
                </div>
                <button onclick="cerrarModal('modalEditarProyecto')" class="p-2 rounded-xl hover:bg-slate-100 text-slate-400 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div id="editCargando" class="p-10 text-center text-slate-400">
                <svg class="w-8 h-8 mx-auto animate-spin mb-3 text-amber-400" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                </svg>
                Cargando...
            </div>
            <form id="formEditarProyecto" action="" method="POST" class="p-6 space-y-5 hidden">
                @csrf
                @method('PUT')
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">Nombre del Proyecto <span class="text-red-500">*</span></label>
                        <input type="text" id="editEmpresa" name="empresa" placeholder="Nombre del proyecto" required
                            class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-2.5">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">Tipo <span class="text-red-500">*</span></label>
                        <select id="editTipo" name="tipo" required
                            class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-2.5">
                            <option value="consulta">Consulta</option>
                            <option value="escritos">Escritos</option>
                            <option value="ambos">Ambos</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">Categoría *</label>
                        <select id="editCategoria" name="categoria_id" required
                            class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-2.5">
                            <option value="">Selecciona una categoría</option>
                            @foreach($categorias as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">Consulta *</label>
                    <textarea id="editConsulta" name="consulta" rows="3" required placeholder="Descripción detallada de la consulta..."
                        class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-2.5"></textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">Resultado <span class="text-slate-400 font-normal">(opcional)</span></label>
                    <textarea id="editResultado" name="resultado" rows="3" placeholder="Resultado o resolución de la consulta..."
                        class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-2.5"></textarea>
                </div>
                <div class="flex gap-3 pt-2">
                    <button type="button" onclick="cerrarModal('modalEditarProyecto')"
                        class="flex-1 py-3 bg-white border border-slate-200 text-slate-600 font-bold text-sm rounded-xl hover:bg-slate-50 transition">
                        Cancelar
                    </button>
                    <button type="submit"
                        class="flex-1 py-3 bg-amber-600 hover:bg-amber-700 text-white font-bold text-sm rounded-xl transition shadow-lg shadow-amber-200">
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- ==================== MODAL: VER EXPEDIENTE ==================== --}}
<div id="modalExpediente" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" onclick="cerrarModal('modalExpediente')"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4 overflow-y-auto">
        <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-xl my-8">
            <div class="flex items-center justify-between p-6 border-b border-slate-100">
                <div>
                    <h2 class="text-xl font-bold text-slate-900" id="expEmpresa">Expediente</h2>
                    <p class="text-sm text-slate-500 mt-0.5" id="expCategoria"></p>
                </div>
                <button onclick="cerrarModal('modalExpediente')" class="p-2 rounded-xl hover:bg-slate-100 text-slate-400 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <div class="p-6 space-y-5">
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-1">Consulta</p>
                    <p id="expConsulta" class="text-sm text-slate-700 leading-relaxed"></p>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-1">Resultado</p>
                    <p id="expResultado" class="text-sm text-slate-700 leading-relaxed"></p>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wide mb-2">Recursos y Archivos</p>
                    <div id="expArchivos" class="space-y-2">
                        <p class="text-sm text-slate-400">Cargando...</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    let archivoIdx = 0;

    // Mostrar/ocultar input de nueva categoría
    document.getElementById('nuevaCategoria').addEventListener('change', function() {
        const inputContainer = document.getElementById('nuevaCategoriaInput');
        if (this.value === '__nueva__') {
            inputContainer.classList.remove('hidden');
            this.required = false;
            this.value = '';
        } else {
            inputContainer.classList.add('hidden');
            if (this.value !== '') {
                this.required = true;
            }
        }
    });

    // Cambiar campos según tipo
    function cambiarCamposTipo() {
        const tipo = document.getElementById('tipoSelect').value;
        const camposConsulta = document.getElementById('camposConsulta');
        const camposEscritos = document.getElementById('camposEscritos');
        const camposAmbos = document.getElementById('camposAmbos');
        
        camposConsulta.classList.add('hidden');
        camposEscritos.classList.add('hidden');
        camposAmbos.classList.add('hidden');
        
        if (tipo === 'consulta') {
            camposConsulta.classList.remove('hidden');
        } else if (tipo === 'escritos') {
            camposEscritos.classList.remove('hidden');
        } else if (tipo === 'ambos') {
            camposAmbos.classList.remove('hidden');
        }
    }

    function abrirModal(id) {
        document.getElementById(id).classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function cerrarModal(id) {
        document.getElementById(id).classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    let archivoIdxAmbos = 0;

    function agregarArchivoRow() {
        const container = document.getElementById('archivosContainer');
        const idx = archivoIdx++;
        const row = document.createElement('div');
        row.className = 'flex gap-2 items-start';
        row.innerHTML = `
            <div class="flex-1 space-y-1.5">
                <input type="text" name="archivos_nombre[${idx}]" placeholder="Nombre del archivo (opcional)"
                    class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-700 focus:border-amber-500 focus:ring-amber-500 text-xs py-2 px-3">
                <input type="file" name="archivos_file[${idx}]"
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp"
                    class="block w-full text-xs text-slate-600 file:mr-2 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
            </div>
            <button type="button" onclick="this.closest('div').remove()"
                class="mt-1 p-1.5 rounded-lg hover:bg-red-50 text-slate-400 hover:text-red-500 transition flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>`;
        container.appendChild(row);
    }

    function agregarArchivoRowAmbos() {
        const container = document.getElementById('archivosContainerAmbos');
        const idx = archivoIdxAmbos++;
        const row = document.createElement('div');
        row.className = 'flex gap-2 items-start';
        row.innerHTML = `
            <div class="flex-1 space-y-1.5">
                <input type="text" name="archivos_nombre_ambos[${idx}]" placeholder="Nombre del archivo (opcional)"
                    class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-700 focus:border-amber-500 focus:ring-amber-500 text-xs py-2 px-3">
                <input type="file" name="archivos_file_ambos[${idx}]"
                    accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.webp"
                    class="block w-full text-xs text-slate-600 file:mr-2 file:py-1 file:px-2 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-amber-50 file:text-amber-700 hover:file:bg-amber-100">
            </div>
            <button type="button" onclick="this.closest('div').remove()"
                class="mt-1 p-1.5 rounded-lg hover:bg-red-50 text-slate-400 hover:text-red-500 transition flex-shrink-0">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>`;
        container.appendChild(row);
    }

    const tipoClases = {
        pdf:    'tipo-pdf',
        excel:  'tipo-excel',
        word:   'tipo-word',
        imagen: 'tipo-imagen',
        otro:   'tipo-otro',
    };

    const tipoIconos = {
        pdf:    '📄',
        excel:  '📊',
        word:   '📝',
        imagen: '🖼️',
        otro:   '📎',
    };

    function verExpediente(id) {
        document.getElementById('expEmpresa').textContent   = 'Cargando...';
        document.getElementById('expCategoria').textContent = '';
        document.getElementById('expConsulta').textContent  = '';
        document.getElementById('expResultado').textContent = '';
        document.getElementById('expArchivos').innerHTML    = '<p class="text-sm text-slate-400">Cargando...</p>';
        abrirModal('modalExpediente');

        fetch(`/legal/matriz/${id}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            const p = data.proyecto;
            document.getElementById('expEmpresa').textContent   = p.empresa;
            document.getElementById('expCategoria').textContent = p.categoria ?? '';
            document.getElementById('expConsulta').textContent  = p.consulta;
            document.getElementById('expResultado').textContent = p.resultado;

            const cont = document.getElementById('expArchivos');
            if (!p.archivos || p.archivos.length === 0) {
                cont.innerHTML = '<p class="text-sm text-slate-400 italic">Sin archivos adjuntos.</p>';
                return;
            }

            cont.innerHTML = p.archivos.map(a => {
                const cls    = tipoClases[a.tipo] || 'tipo-otro';
                const icono  = tipoIconos[a.tipo] || '📎';
                const etiq   = a.es_url
                    ? `<span class="tipo-badge tipo-otro">🔗 Ruta externa</span>`
                    : `<span class="tipo-badge ${cls}">${icono} ${a.tipo.toUpperCase()}</span>`;

                const action = a.es_url
                    ? buildRutaAction(a.url_publica)
                    : `<a href="/legal/matriz/archivo/${a.id}/download"
                            class="text-xs font-semibold text-amber-600 hover:text-amber-800 transition">Descargar ↓</a>`;

                const deleteBtn = `
                    <button onclick="eliminarArchivo(${a.id}, this)" title="Eliminar"
                        class="text-xs text-red-400 hover:text-red-600 transition ml-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                    </button>`;

                return `
                    <div class="flex items-center justify-between p-3 bg-slate-50 rounded-xl border border-slate-100">
                        <div class="flex items-center gap-2 flex-1 min-w-0">
                            ${etiq}
                            <span class="text-sm text-slate-700 truncate" title="${a.nombre}">${a.nombre}</span>
                        </div>
                        <div class="flex items-center gap-1 flex-shrink-0 ml-2">
                            ${action}
                            ${deleteBtn}
                        </div>
                    </div>`;
            }).join('');
        })
        .catch(() => {
            document.getElementById('expArchivos').innerHTML = '<p class="text-sm text-red-500">Error al cargar los datos.</p>';
        });
    }

    function eliminarArchivo(id, btn) {
        if (!confirm('¿Eliminar este archivo?')) return;
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        fetch(`/legal/matriz/archivo/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': csrfToken, 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                btn.closest('.flex.items-center.justify-between').remove();
            }
        });
    }

    /**
     * Construye el bloque de acción para rutas/URLs externas.
     * - http/https  → enlace normal en nueva pestaña
     * - \\servidor\carpeta (UNC) → convierte a file://servidor/carpeta/ + botón copiar
     * - C:\carpeta (ruta local Windows) → convierte a file:///C:/carpeta + botón copiar
     */
    function buildRutaAction(ruta) {
        if (/^https?:\/\//i.test(ruta)) {
            // URL web normal
            return `<a href="${ruta}" target="_blank" rel="noopener"
                        class="text-xs font-semibold text-amber-600 hover:text-amber-800 transition">Abrir →</a>`;
        }

        // Construir href para file://
        let fileHref = ruta;
        if (ruta.startsWith('\\\\')) {
            // UNC: \\servidor\carpeta → file://servidor/carpeta
            fileHref = 'file:' + ruta.replace(/\\/g, '/');
        } else if (/^[A-Za-z]:\\/.test(ruta)) {
            // Ruta local: C:\carpeta → file:///C:/carpeta
            fileHref = 'file:///' + ruta.replace(/\\/g, '/');
        }

        const escaped = ruta.replace(/"/g, '&quot;').replace(/'/g, "\\'");
        return `<a href="${fileHref}" title="Abrir ruta en el explorador"
                    class="text-xs font-semibold text-amber-600 hover:text-amber-800 transition">Abrir →</a>
                <button type="button" onclick="copiarRuta('${escaped}')" title="Copiar ruta al portapapeles"
                    class="text-xs text-slate-500 hover:text-slate-700 transition ml-1" aria-label="Copiar ruta">📋</button>`;
    }

    function copiarRuta(ruta) {
        navigator.clipboard.writeText(ruta).then(() => {
            // Feedback visual breve en el botón disparador (si está disponible)
            const btn = event && event.target ? event.target : null;
            if (btn) {
                const orig = btn.textContent;
                btn.textContent = '✅';
                setTimeout(() => { btn.textContent = orig; }, 1500);
            }
        }).catch(() => {
            prompt('Copia esta ruta:', ruta);
        });
    }

    /**
     * Abre el modal de edición pre-rellenando los datos del proyecto via AJAX.
     */
    function abrirEditar(id) {
        const cargando = document.getElementById('editCargando');
        const form     = document.getElementById('formEditarProyecto');
        cargando.classList.remove('hidden');
        form.classList.add('hidden');
        abrirModal('modalEditarProyecto');

        fetch(`/legal/matriz/${id}`, {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(r => r.json())
        .then(data => {
            const p = data.proyecto;
            document.getElementById('editEmpresa').value   = p.empresa   || '';
            document.getElementById('editTipo').value     = p.tipo      || 'consulta';
            document.getElementById('editConsulta').value  = p.consulta  || '';
            document.getElementById('editResultado').value = p.resultado || '';
            document.getElementById('editSubtitulo').textContent = p.empresa
                ? `Editando: ${p.empresa}`
                : 'Modifica los datos de la consulta.';

            const sel = document.getElementById('editCategoria');
            if (p.categoria_id) {
                sel.value = p.categoria_id;
            }

            form.action = `/legal/matriz/${id}`;
            cargando.classList.add('hidden');
            form.classList.remove('hidden');
        })
        .catch(() => {
            cerrarModal('modalEditarProyecto');
            alert('Error al cargar los datos del proyecto.');
        });
    }

    // Cerrar modal con Escape
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            ['modalAgregarProyecto', 'modalEditarProyecto', 'modalExpediente'].forEach(id => {
                if (!document.getElementById(id).classList.contains('hidden')) {
                    cerrarModal(id);
                }
            });
        }
    });

    // Auto-abrir modal de nuevo proyecto cuando se viene de crear una categoría
    (function autoAbrirNuevaCategoria() {
        const params = new URLSearchParams(window.location.search);
        const catId  = params.get('nueva_categoria');
        if (!catId) return;

        const sel = document.getElementById('nuevaCategoria');
        if (sel) {
            sel.value = catId;
        }
        abrirModal('modalAgregarProyecto');

        // Limpiar el param de la URL sin recargar
        params.delete('nueva_categoria');
        const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        history.replaceState(null, '', newUrl);
    })();
</script>
@endpush
