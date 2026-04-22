@extends('layouts.master')

@section('title', 'Matriz de Consultas y Escritos - Legal')

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
                <span class="text-slate-700 font-medium">Matriz de Consultas y Escritos</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">Matriz de Consultas y Escritos</h1>
            <p class="text-slate-500 mt-1 text-sm">Registro y seguimiento de consultas jurídicas por empresa.</p>
        </div>
        <div class="flex gap-3 flex-wrap">
            <a href="{{ route('legal.categorias.index') }}"
               class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 text-slate-600 font-semibold text-sm rounded-xl hover:bg-slate-50 hover:border-amber-200 hover:text-amber-600 transition shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                Gestionar Dependencias
            </a>
            <button onclick="abrirAgregarProyecto()"
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

{{-- FILTROS --}}
<form method="GET" action="{{ route('legal.matriz.index') }}" class="bg-white rounded-2xl border border-slate-200 shadow-sm p-4 mb-6">
    <div class="flex flex-col sm:flex-row gap-3 items-end">
        <div class="flex-1">
            <label id="labelFiltroEmpresa" class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">Empresa</label>
            <select name="empresa" id="filtroEmpresa" class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-2.5">
                <option value="" id="filtroEmpresaPlaceholder">Todas las empresas</option>
                {{-- Opciones inyectadas por JS según pestaña --}}
                @foreach($empresas as $emp)
                    <option value="{{ $emp }}" data-grupo="consultas" {{ request('empresa') === $emp ? 'selected' : '' }}>{{ $emp }}</option>
                @endforeach
                @foreach($proyectosNombres as $proy)
                    <option value="{{ $proy }}" data-grupo="escritos" {{ request('empresa') === $proy ? 'selected' : '' }}>{{ $proy }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex-1">
            <label class="block text-xs font-bold text-slate-500 uppercase tracking-wide mb-1.5">Dependencia</label>
            <select name="categoria_id" id="filtroCategorias" class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-2.5">
                <option value="">Todas las dependencias</option>
                @foreach($categoriasConsultas as $cat)
                    <option value="{{ $cat->id }}" data-grupo="consultas" {{ request('categoria_id') == $cat->id ? 'selected' : '' }}>{{ $cat->nombre }}</option>
                @endforeach
                @foreach($categoriasEscritos as $cat)
                    <option value="{{ $cat->id }}" data-grupo="escritos" {{ request('categoria_id') == $cat->id ? 'selected' : '' }}>{{ $cat->nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="flex gap-2">
            <button type="submit" class="px-4 py-2.5 bg-amber-600 hover:bg-amber-700 text-white font-bold text-sm rounded-xl transition shadow-sm">
                Filtrar
            </button>
            @if(request('empresa') || request('categoria_id') || request('buscar'))
                <a href="{{ route('legal.matriz.index') }}" class="px-4 py-2.5 bg-white border border-slate-200 text-slate-600 font-semibold text-sm rounded-xl hover:bg-slate-50 transition shadow-sm">
                    Limpiar
                </a>
            @endif
        </div>
    </div>
</form>

@php
    $proyectosConsultas = $proyectos->filter(fn($p) => in_array($p->tipo, ['consulta', 'ambos']));
    $proyectosEscritos  = $proyectos->filter(fn($p) => in_array($p->tipo, ['escritos', 'ambos']));
@endphp

{{-- TABS NAV --}}
<div class="flex gap-1">
    <button onclick="cambiarTab('consultas')" id="tab-consultas"
        class="tab-btn px-6 py-3 text-sm font-bold rounded-t-2xl border border-b-0 border-amber-300 bg-amber-50 text-amber-700 transition-all shadow-sm">
        <span class="flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3-3-3z"/>
            </svg>
            Consultas
            <span class="px-2 py-0.5 rounded-full text-xs bg-amber-100 text-amber-700 font-semibold">{{ $proyectosConsultas->count() }}</span>
        </span>
    </button>
    <button onclick="cambiarTab('escritos')" id="tab-escritos"
        class="tab-btn px-6 py-3 text-sm font-semibold rounded-t-2xl border border-b-0 border-slate-200 bg-white text-slate-500 hover:text-slate-700 transition-all">
        <span class="flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            Escritos
            <span class="px-2 py-0.5 rounded-full text-xs bg-slate-100 text-slate-600 font-semibold">{{ $proyectosEscritos->count() }}</span>
        </span>
    </button>
</div>

{{-- PANEL: Consultas --}}
<div id="panel-consultas" class="tab-panel">
    {{-- Buscador interno --}}
    <div class="bg-white border border-b-0 border-slate-200 rounded-tr-2xl px-4 pt-3 pb-3">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" id="buscarConsultas" oninput="buscarEnTab('buscarConsultas','tbodyConsultas')" placeholder="Buscar por empresa, categoría o consulta..."
                class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-800 focus:border-amber-500 focus:ring-amber-500 focus:outline-none">
        </div>
    </div>
    <div class="bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-sm overflow-hidden">
        @if($proyectosConsultas->isEmpty())
            <div class="text-center py-20 text-slate-400">
                <svg class="w-12 h-12 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="font-semibold text-slate-500">No hay consultas registradas</p>
                <p class="text-sm mt-1">Usa "Añadir Proyecto" para comenzar.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-left">
                            <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wide">Nombre del Proyecto</th>
                            <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wide">Categoría</th>
                            <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wide">Consulta</th>
                            <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wide">Resultado</th>
                            <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wide text-center">Recursos</th>
                            <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wide text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyConsultas" class="divide-y divide-slate-100">
                        @foreach($proyectosConsultas as $proyecto)
                        <tr class="hover:bg-amber-50/40 transition-colors group" data-search="{{ strtolower(($proyecto->empresa ?? '') . ' ' . ($proyecto->categoria?->nombre ?? '') . ' ' . ($proyecto->consulta ?? '') . ' ' . ($proyecto->resultado ?? '')) }}">
                            <td class="px-5 py-4 font-semibold text-slate-800 whitespace-nowrap">
                                {{ $proyecto->empresa ?? '—' }}
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
</div>

{{-- PANEL: Escritos --}}
<div id="panel-escritos" class="tab-panel hidden">
    {{-- Buscador interno --}}
    <div class="bg-white border border-b-0 border-slate-200 rounded-tr-2xl px-4 pt-3 pb-3">
        <div class="relative">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400 pointer-events-none" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" id="buscarEscritos" oninput="buscarEnTab('buscarEscritos','tbodyEscritos')" placeholder="Buscar por proyecto, dependencia o descripción..."
                class="w-full pl-9 pr-4 py-2 rounded-xl border border-slate-200 bg-slate-50 text-sm text-slate-800 focus:border-amber-500 focus:ring-amber-500 focus:outline-none">
        </div>
    </div>
    <div class="bg-white rounded-b-2xl border border-t-0 border-slate-200 shadow-sm overflow-hidden">
        @if($proyectosEscritos->isEmpty())
            <div class="text-center py-20 text-slate-400">
                <svg class="w-12 h-12 mx-auto mb-4 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                <p class="font-semibold text-slate-500">No hay escritos registrados</p>
                <p class="text-sm mt-1">Usa "Añadir Proyecto" para comenzar.</p>
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200 text-left">
                            <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wide">Nombre del Proyecto</th>
                            <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wide">Dependencia</th>
                            <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wide">Descripción</th>
                            <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wide">Resultado</th>
                            <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wide text-center">Recursos</th>
                            <th class="px-5 py-3.5 text-xs font-bold text-slate-500 uppercase tracking-wide text-center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbodyEscritos" class="divide-y divide-slate-100">
                        @foreach($proyectosEscritos as $proyecto)
                        <tr class="hover:bg-amber-50/40 transition-colors group" data-search="{{ strtolower(($proyecto->empresa ?? '') . ' ' . ($proyecto->categoria?->nombre ?? '') . ' ' . ($proyecto->consulta ?? '') . ' ' . ($proyecto->resultado ?? '')) }}">
                            <td class="px-5 py-4 font-semibold text-slate-800 whitespace-nowrap">
                                {{ $proyecto->empresa ?? '—' }}
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

                {{-- TIPO TOGGLE --}}
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-2">Tipo *</label>
                    <div class="flex rounded-xl overflow-hidden border border-slate-200">
                        <button type="button" id="btnNuevoConsulta" onclick="cambiarTipoFormNuevo('consulta')"
                            class="flex-1 flex items-center justify-center gap-2 py-3 text-sm font-bold bg-amber-600 text-white transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3-3-3z"/>
                            </svg>
                            Consulta
                        </button>
                        <button type="button" id="btnNuevoEscritos" onclick="cambiarTipoFormNuevo('escritos')"
                            class="flex-1 flex items-center justify-center gap-2 py-3 text-sm font-semibold bg-white text-slate-500 hover:bg-slate-50 transition border-l border-slate-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Escritos
                        </button>
                    </div>
                    <input type="hidden" name="tipo" id="nuevoTipoInput" value="consulta">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label id="labelNuevoEmpresa" class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">Nombre de la Empresa</label>
                        <input type="text" name="empresa" id="nuevoEmpresa" placeholder="Nombre de la empresa"
                            class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-2.5">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">Dependencia *</label>
                        <select name="categoria_id" id="nuevaCategoria" required
                            class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-2.5">
                            <option value="">Selecciona una dependencia</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label id="labelNuevoConsulta" class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">Consulta</label>
                    <textarea name="consulta" id="nuevoConsulta" rows="3" placeholder="Descripción detallada de la consulta..."
                        class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-2.5"></textarea>
                </div>

                <div id="divNuevoResultado">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">Resultado <span class="text-slate-400 font-normal">(opcional)</span></label>
                    <textarea name="resultado" rows="3" placeholder="Resultado o resolución de la consulta..."
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

                {{-- TIPO TOGGLE --}}
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-2">Tipo *</label>
                    <div class="flex rounded-xl overflow-hidden border border-slate-200">
                        <button type="button" id="btnEditConsulta" onclick="cambiarTipoFormEditar('consulta')"
                            class="flex-1 flex items-center justify-center gap-2 py-3 text-sm font-bold bg-amber-600 text-white transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3-3-3z"/>
                            </svg>
                            Consulta
                        </button>
                        <button type="button" id="btnEditEscritos" onclick="cambiarTipoFormEditar('escritos')"
                            class="flex-1 flex items-center justify-center gap-2 py-3 text-sm font-semibold bg-white text-slate-500 hover:bg-slate-50 transition border-l border-slate-200">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Escritos
                        </button>
                    </div>
                    <input type="hidden" name="tipo" id="editTipoInput" value="consulta">
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label id="labelEditEmpresa" class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">Nombre de la Empresa</label>
                        <input type="text" id="editEmpresa" name="empresa" placeholder="Nombre de la empresa"
                            class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-2.5">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">Dependencia *</label>
                        <select id="editCategoria" name="categoria_id" required
                            class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-2.5">
                            <option value="">Selecciona una dependencia</option>
                        </select>
                    </div>
                </div>
                <div>
                    <label id="labelEditConsulta" class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">Consulta</label>
                    <textarea id="editConsulta" name="consulta" rows="3" placeholder="Descripción detallada de la consulta..."
                        class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-2.5"></textarea>
                </div>
                <div id="divEditResultado">
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
    const categoriasData = @json($categorias->map(fn($c) => ['id' => $c->id, 'nombre' => $c->nombre, 'tipo' => $c->tipo])->values());

    let tabActiva = 'consultas';

    const TIPO_ACTIVE   = 'flex-1 flex items-center justify-center gap-2 py-3 text-sm font-bold bg-amber-600 text-white transition';
    const TIPO_INACTIVE = 'flex-1 flex items-center justify-center gap-2 py-3 text-sm font-semibold bg-white text-slate-500 hover:bg-slate-50 transition border-l border-slate-200';

    function filtrarCategoriasSelect(selectId, tipo, selectedId) {
        const sel = document.getElementById(selectId);
        if (!sel) return;
        const filtradas = categoriasData.filter(c => c.tipo === tipo);
        sel.innerHTML = '<option value="">Selecciona una dependencia</option>';
        filtradas.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.id;
            opt.textContent = c.nombre;
            if (selectedId && c.id == selectedId) opt.selected = true;
            sel.appendChild(opt);
        });
    }

    function cambiarTipoFormNuevo(tipo) {
        document.getElementById('nuevoTipoInput').value = tipo;
        const btnC = document.getElementById('btnNuevoConsulta');
        const btnE = document.getElementById('btnNuevoEscritos');
        if (tipo === 'consulta') {
            btnC.className = TIPO_ACTIVE;
            btnE.className = TIPO_INACTIVE;
            document.getElementById('labelNuevoEmpresa').textContent = 'NOMBRE DE LA EMPRESA';
            document.getElementById('nuevoEmpresa').placeholder = 'Nombre de la empresa';
            document.getElementById('labelNuevoConsulta').textContent = 'CONSULTA';
            document.getElementById('nuevoConsulta').placeholder = 'Descripción detallada de la consulta...';
            document.getElementById('divNuevoResultado').classList.remove('hidden');
        } else {
            btnC.className = TIPO_INACTIVE.replace('border-l border-slate-200', '');
            btnE.className = TIPO_ACTIVE;
            document.getElementById('labelNuevoEmpresa').textContent = 'NOMBRE DEL PROYECTO';
            document.getElementById('nuevoEmpresa').placeholder = 'Nombre del proyecto';
            document.getElementById('labelNuevoConsulta').textContent = 'DESCRIPCIÓN';
            document.getElementById('nuevoConsulta').placeholder = 'Descripción del escrito...';
            document.getElementById('divNuevoResultado').classList.add('hidden');
        }
        filtrarCategoriasSelect('nuevaCategoria', tipo);
    }

    function cambiarTipoFormEditar(tipo, selectedCatId) {
        document.getElementById('editTipoInput').value = tipo;
        const btnC = document.getElementById('btnEditConsulta');
        const btnE = document.getElementById('btnEditEscritos');
        if (tipo === 'consulta') {
            btnC.className = TIPO_ACTIVE;
            btnE.className = TIPO_INACTIVE;
            document.getElementById('labelEditEmpresa').textContent = 'NOMBRE DE LA EMPRESA';
            document.getElementById('editEmpresa').placeholder = 'Nombre de la empresa';
            document.getElementById('labelEditConsulta').textContent = 'CONSULTA';
            document.getElementById('editConsulta').placeholder = 'Descripción detallada de la consulta...';
            document.getElementById('divEditResultado').classList.remove('hidden');
        } else {
            btnC.className = TIPO_INACTIVE.replace('border-l border-slate-200', '');
            btnE.className = TIPO_ACTIVE;
            document.getElementById('labelEditEmpresa').textContent = 'NOMBRE DEL PROYECTO';
            document.getElementById('editEmpresa').placeholder = 'Nombre del proyecto';
            document.getElementById('labelEditConsulta').textContent = 'DESCRIPCIÓN';
            document.getElementById('editConsulta').placeholder = 'Descripción del escrito...';
            document.getElementById('divEditResultado').classList.add('hidden');
        }
        filtrarCategoriasSelect('editCategoria', tipo, selectedCatId);
    }

    function abrirAgregarProyecto() {
        const tipo = tabActiva === 'escritos' ? 'escritos' : 'consulta';
        cambiarTipoFormNuevo(tipo);
        abrirModal('modalAgregarProyecto');
    }

    let archivoIdx = 0;

    function buscarEnTab(inputId, tbodyId) {
        const term  = document.getElementById(inputId).value.toLowerCase().trim();
        const tbody = document.getElementById(tbodyId);
        if (!tbody) return;
        let visible = 0;
        tbody.querySelectorAll('tr[data-search]').forEach(row => {
            const match = !term || row.dataset.search.includes(term);
            row.style.display = match ? '' : 'none';
            if (match) visible++;
        });
        // Mostrar fila vacía si no hay resultados
        let emptyRow = tbody.querySelector('tr.no-results');
        if (!emptyRow) {
            emptyRow = document.createElement('tr');
            emptyRow.className = 'no-results';
            emptyRow.innerHTML = '<td colspan="6" class="text-center py-10 text-slate-400 text-sm">Sin resultados para "<span class="font-semibold text-slate-600"></span>"</td>';
            tbody.appendChild(emptyRow);
        }
        if (visible === 0 && term) {
            emptyRow.querySelector('span').textContent = term;
            emptyRow.style.display = '';
        } else {
            emptyRow.style.display = 'none';
        }
    }

    function sincronizarFiltros(tab) {
        // Label empresa/proyecto
        const labelEmp = document.getElementById('labelFiltroEmpresa');
        if (labelEmp) labelEmp.textContent = tab === 'escritos' ? 'PROYECTO' : 'EMPRESA';

        // Placeholder del select
        const placeholder = document.getElementById('filtroEmpresaPlaceholder');
        if (placeholder) placeholder.textContent = tab === 'escritos' ? 'Todos los proyectos' : 'Todas las empresas';

        // Mostrar/ocultar opciones empresa
        document.querySelectorAll('#filtroEmpresa option[data-grupo]').forEach(opt => {
            opt.hidden = opt.dataset.grupo !== tab;
        });
        // Resetear si la opción seleccionada no pertenece al tab activo
        const empSel = document.getElementById('filtroEmpresa');
        if (empSel) {
            const selected = empSel.options[empSel.selectedIndex];
            if (selected && selected.dataset.grupo && selected.dataset.grupo !== tab) {
                empSel.value = '';
            }
        }

        // Mostrar/ocultar opciones de categoría
        document.querySelectorAll('#filtroCategorias option[data-grupo]').forEach(opt => {
            opt.hidden = opt.dataset.grupo !== tab;
        });
        const catSel = document.getElementById('filtroCategorias');
        if (catSel) {
            const selectedCat = catSel.options[catSel.selectedIndex];
            if (selectedCat && selectedCat.dataset.grupo && selectedCat.dataset.grupo !== tab) {
                catSel.value = '';
            }
        }
    }

    function cambiarTab(tab) {
        tabActiva = tab;
        ['consultas', 'escritos'].forEach(t => {
            const btn   = document.getElementById('tab-' + t);
            const panel = document.getElementById('panel-' + t);
            if (t === tab) {
                btn.className = 'tab-btn px-6 py-3 text-sm font-bold rounded-t-2xl border border-b-0 border-amber-300 bg-amber-50 text-amber-700 transition-all shadow-sm';
                panel.classList.remove('hidden');
            } else {
                btn.className = 'tab-btn px-6 py-3 text-sm font-semibold rounded-t-2xl border border-b-0 border-slate-200 bg-white text-slate-500 hover:text-slate-700 transition-all';
                panel.classList.add('hidden');
            }
        });
        sincronizarFiltros(tab);
    }

    function abrirModal(id) {
        document.getElementById(id).classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function cerrarModal(id) {
        document.getElementById(id).classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

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
            document.getElementById('editConsulta').value  = p.consulta  || '';
            document.getElementById('editResultado').value = p.resultado || '';
            document.getElementById('editSubtitulo').textContent = p.empresa
                ? `Editando: ${p.empresa}`
                : 'Modifica los datos de la consulta.';

            cambiarTipoFormEditar(p.tipo || 'consulta', p.categoria_id);

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

    // Inicializar filtros según pestaña activa al cargar
    sincronizarFiltros(tabActiva);

    (function autoAbrirNuevaCategoria() {
        const params = new URLSearchParams(window.location.search);
        const catId  = params.get('nueva_categoria');
        if (!catId) return;

        const catTipo = params.get('nueva_categoria_tipo') || 'consulta';
        cambiarTipoFormNuevo(catTipo);

        const sel = document.getElementById('nuevaCategoria');
        if (sel) sel.value = catId;
        abrirModal('modalAgregarProyecto');

        // Limpiar los params de la URL sin recargar
        params.delete('nueva_categoria');
        params.delete('nueva_categoria_tipo');
        const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
        history.replaceState(null, '', newUrl);
    })();
</script>
@endpush
