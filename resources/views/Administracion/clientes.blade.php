@extends('layouts.master')
@section('title', 'Clientes - Administración')

@section('content')
<div class="min-h-screen bg-slate-50 pb-12">

    {{-- ENCABEZADO --}}
    <div class="bg-white border-b border-slate-200 mb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div>
                    <p class="text-xs font-bold text-indigo-500 uppercase tracking-widest mb-1">
                        <a href="{{ route('administracion.dashboard') }}" class="hover:underline">Panel Administración</a>
                        <span class="mx-1 text-slate-300">/</span> Perfil de Clientes
                    </p>
                    <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Perfil de Clientes</h1>
                    <p class="text-slate-500 mt-1">{{ $clientes->count() }} cliente{{ $clientes->count() !== 1 ? 's' : '' }} registrado{{ $clientes->count() !== 1 ? 's' : '' }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <button onclick="abrirModalImportar()"
                            class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white font-bold text-sm rounded-xl hover:bg-emerald-700 transition shadow-lg shadow-emerald-200 hover:-translate-y-0.5">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v2a2 2 0 002 2h12a2 2 0 002-2v-2M7 10l5 5 5-5M12 15V3"/>
                        </svg>
                        Importar Excel
                    </button>
                    <a href="{{ route('administracion.clientes.plantilla') }}"
                       class="inline-flex items-center px-4 py-2 bg-white text-slate-700 font-semibold text-sm rounded-xl border border-slate-200 hover:bg-slate-50 transition shadow-sm hover:-translate-y-0.5">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        Plantilla
                    </a>
                    <button onclick="abrirModal()"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-bold text-sm rounded-xl hover:bg-indigo-700 transition shadow-lg shadow-indigo-200 hover:-translate-y-0.5">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nuevo Cliente
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        @if(session('success'))
            <div class="flex items-center gap-3 p-4 rounded-xl border bg-emerald-50 border-emerald-100 text-emerald-700">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
        @endif

        <div class="relative max-w-sm">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" id="buscador" placeholder="Buscar cliente..."
                   class="pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-xl bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 w-full transition">
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            @if($clientes->isEmpty())
                <div class="py-20 text-center">
                    <svg class="mx-auto w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <p class="text-slate-400 font-medium">No hay clientes registrados.</p>
                    <button onclick="abrirModal()" class="mt-4 text-sm text-indigo-600 hover:underline font-semibold">Agregar el primero</button>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">#</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Nombre de Cliente</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Contacto</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Cuestionario</th>
                                <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($clientes as $i => $c)
                                @php $clienteJson = json_encode($c->toArray() + ['perfil' => $c->perfil?->toArray()]); @endphp
                                <tr class="hover:bg-slate-50 transition-colors fila" data-nombre="{{ strtolower($c->nombre) }}">
                                    <td class="px-6 py-4 text-slate-400 font-mono text-xs">{{ $i + 1 }}</td>
                                    <td class="px-6 py-4 font-semibold text-slate-800">{{ $c->nombre }}</td>
                                    <td class="px-6 py-4 text-slate-600 text-sm">{{ $c->perfil?->informante_nombre ?? '—' }}</td>
                                    <td class="px-6 py-4">
                                        @if($c->perfil)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold border border-emerald-100">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                Completo
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-xs font-semibold border border-amber-100">Pendiente</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <button type="button"
                                                    data-action="ver"
                                                    data-cliente="{{ $clienteJson }}"
                                                    class="p-1.5 rounded-lg text-slate-400 hover:text-sky-600 hover:bg-sky-50 transition" title="Visualizar información">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                                </svg>
                                            </button>
                                            <button type="button"
                                                    data-action="editar"
                                                    data-cliente="{{ $clienteJson }}"
                                                    class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition" title="Editar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                </svg>
                                            </button>
                                            <button type="button"
                                                    data-action="eliminar"
                                                    data-id="{{ $c->id }}"
                                                    data-nombre="{{ $c->nombre }}"
                                                    class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition" title="Eliminar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                </svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-3 bg-slate-50 border-t border-slate-100">
                    <p class="text-xs text-slate-400">
                        Mostrando <span id="num-visibles">{{ $clientes->count() }}</span> de {{ $clientes->count() }} clientes
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     MODAL — CUESTIONARIO DE PERFIL DE COMERCIO EXTERIOR
══════════════════════════════════════════════════════════════ --}}
<div id="modal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-modal="true">
    <div class="flex min-h-full items-start justify-center p-4 pt-6">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="cerrarModal()"></div>

        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-5xl z-10 mb-10 overflow-hidden flex flex-col" style="max-height:92vh">

            {{-- Cabecera --}}
            <div class="shrink-0 bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-900" id="modal-titulo">Nuevo Cliente</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Cuestionario de Perfil de Comercio Exterior</p>
                </div>
                <button onclick="cerrarModal()" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Cuerpo scrollable --}}
            <div class="overflow-y-auto flex-1">
            <form id="form-cliente">
            @csrf

            <table class="w-full text-sm border-collapse">

                {{-- ── TÍTULO PRINCIPAL ───────────────────────────── --}}
                <tr>
                    <td colspan="5" class="font-bold text-sm tracking-wide px-4 py-3 text-center text-white" style="background-color:#1d4ed8;">
                        Cuestionario de Perfil de Comercio Exterior
                    </td>
                </tr>

                {{-- ── DATOS GENERALES ─────────────────────────────── --}}
                <tr>
                    <td colspan="5" class="bg-slate-700 text-white font-bold text-xs uppercase tracking-wider px-4 py-2.5 text-left">
                        Datos Generales de la Empresa
                    </td>
                </tr>
                <tr class="bg-white border-b border-slate-100">
                    <td class="px-4 py-2.5 text-slate-700 font-medium">
                        Nombre Legal de la Empresa <span class="text-red-500">*</span>
                    </td>
                    <td colspan="4" class="px-4 py-2">
                        <input type="text" id="p-nombre-legal" placeholder="Razón social completa"
                               class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white">
                    </td>
                </tr>
                <tr class="bg-slate-50 border-b border-slate-100">
                    <td class="px-4 py-2.5 text-slate-700 font-medium">Sectores Productivos Usados</td>
                    <td colspan="4" class="px-4 py-2">
                        <input type="text" id="p-sectores"
                               class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white">
                    </td>
                </tr>
                <tr class="bg-white border-b border-slate-100">
                    <td class="px-4 py-2.5 text-slate-700 font-medium">Fecha de Inicio de Operaciones</td>
                    <td colspan="4" class="px-4 py-2">
                        <input type="date" id="p-fecha-inicio"
                               class="px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white">
                    </td>
                </tr>
                <tr class="bg-slate-50 border-b border-slate-100">
                    <td class="px-4 py-2.5 text-slate-700 font-medium">Realiza operaciones con partes relacionadas en el extranjero</td>
                    <td class="px-2 py-2 text-center w-16">
                        <label class="inline-flex flex-col items-center gap-1 cursor-pointer">
                            <input type="radio" name="p-partes-relacionadas" value="1" class="w-4 h-4 text-indigo-600 border-slate-300 cursor-pointer">
                            <span class="text-xs text-slate-500">Sí</span>
                        </label>
                    </td>
                    <td class="px-2 py-2 text-center w-16">
                        <label class="inline-flex flex-col items-center gap-1 cursor-pointer">
                            <input type="radio" name="p-partes-relacionadas" value="0" class="w-4 h-4 text-indigo-600 border-slate-300 cursor-pointer" checked>
                            <span class="text-xs text-slate-500">No</span>
                        </label>
                    </td>
                    <td colspan="2"></td>
                </tr>
                <tr class="bg-white border-b border-slate-100">
                    <td class="px-4 py-2.5 text-slate-700 font-medium">Nombre del Corporativo</td>
                    <td colspan="4" class="px-4 py-2">
                        <input type="text" id="p-nombre-corporativo"
                               class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white">
                    </td>
                </tr>
                <tr class="bg-slate-50 border-b border-slate-200">
                    <td class="px-4 py-2.5 text-slate-700 font-medium">Ciudad, Estado y País del Corporativo</td>
                    <td colspan="4" class="px-4 py-2">
                        <input type="text" id="p-ciudad-corporativo"
                               class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white">
                    </td>
                </tr>
                <tr class="bg-white border-b border-slate-100">
                    <td class="px-4 py-2.5 text-slate-700 font-medium">Cuenta con registro de marca</td>
                    <td class="px-2 py-2 text-center w-16">
                        <label class="inline-flex flex-col items-center gap-1 cursor-pointer">
                            <input type="radio" name="p-registro-marca" value="1" class="w-4 h-4 text-indigo-600 border-slate-300 cursor-pointer">
                            <span class="text-xs text-slate-500">Sí</span>
                        </label>
                    </td>
                    <td class="px-2 py-2 text-center w-16">
                        <label class="inline-flex flex-col items-center gap-1 cursor-pointer">
                            <input type="radio" name="p-registro-marca" value="0" class="w-4 h-4 text-indigo-600 border-slate-300 cursor-pointer" checked>
                            <span class="text-xs text-slate-500">No</span>
                        </label>
                    </td>
                    <td colspan="2"></td>
                </tr>
                <tr class="bg-slate-50 border-b border-slate-100">
                    <td class="px-4 py-2.5 text-slate-700 font-medium">Cuenta con póliza de seguro de las mercancías</td>
                    <td class="px-2 py-2 text-center w-16">
                        <label class="inline-flex flex-col items-center gap-1 cursor-pointer">
                            <input type="radio" name="p-poliza-seguro" value="1" class="w-4 h-4 text-indigo-600 border-slate-300 cursor-pointer">
                            <span class="text-xs text-slate-500">Sí</span>
                        </label>
                    </td>
                    <td class="px-2 py-2 text-center w-16">
                        <label class="inline-flex flex-col items-center gap-1 cursor-pointer">
                            <input type="radio" name="p-poliza-seguro" value="0" class="w-4 h-4 text-indigo-600 border-slate-300 cursor-pointer" checked>
                            <span class="text-xs text-slate-500">No</span>
                        </label>
                    </td>
                    <td colspan="2"></td>
                </tr>

                {{-- ── PERFIL DE LA EMPRESA ───────────────────────── --}}
                <tr>
                    <td colspan="4" class="bg-slate-700 text-white font-bold text-xs uppercase tracking-wider px-4 py-2.5 text-left">
                        Perfil de la Empresa
                    </td>
                </tr>
                <tr class="bg-slate-100">
                    <td class="px-4 py-2 text-xs font-bold text-slate-500 uppercase"></td>
                    <td class="px-4 py-2 text-xs font-bold text-slate-600 text-center w-16">Sí</td>
                    <td class="px-4 py-2 text-xs font-bold text-slate-600 text-center w-16">No</td>
                    <td class="px-4 py-2 text-xs font-bold text-slate-600 text-center">Desde Fecha</td>
                </tr>

                {{-- Con fecha --}}
                @php
                    $conFecha = [
                        ['id' => 'p-immex',          'label' => 'Cuenta con Programa IMMEX Industrial',    'fecha' => 'p-immex-fecha'],
                        ['id' => 'p-immex-servicios', 'label' => 'Cuenta con Programa IMMEX de Servicios', 'fecha' => 'p-immex-servicios-fecha'],
                        ['id' => 'p-maquiladora',   'label' => 'La empresa está registrada como Maquiladora',           'fecha' => 'p-maquiladora-fecha',   'sin_fecha' => true],
                        ['id' => 'p-maq-servicios', 'label' => 'La empresa está registrada como Maquiladora de Servicios', 'fecha' => 'p-maq-servicios-fecha', 'sin_fecha' => true],
                        ['id' => 'p-prosec',        'label' => 'Cuenta con Programa PROSEC',                       'fecha' => 'p-prosec-fecha'],
                        ['id' => 'p-oea',           'label' => 'Cuenta con Registro como Empresa Certificada OEA', 'fecha' => 'p-oea-fecha'],
                    ];
                @endphp
                @foreach($conFecha as $r)
                <tr class="{{ $loop->even ? 'bg-slate-50' : 'bg-white' }} border-b border-slate-100">
                    <td class="px-4 py-2.5 text-slate-700 font-medium">{{ $r['label'] }}</td>
                    <td class="px-2 py-2 text-center">
                        <label class="inline-flex flex-col items-center gap-1 cursor-pointer">
                            <input type="radio" name="{{ $r['id'] }}" value="1" class="w-4 h-4 text-indigo-600 border-slate-300 cursor-pointer">
                            <span class="text-xs text-slate-500">Sí</span>
                        </label>
                    </td>
                    <td class="px-2 py-2 text-center">
                        <label class="inline-flex flex-col items-center gap-1 cursor-pointer">
                            <input type="radio" name="{{ $r['id'] }}" value="0" class="w-4 h-4 text-indigo-600 border-slate-300 cursor-pointer" checked>
                            <span class="text-xs text-slate-500">No</span>
                        </label>
                    </td>
                    <td class="px-4 py-2">
                        @if(empty($r['sin_fecha']))
                            <input type="date" id="{{ $r['fecha'] }}" class="text-sm border border-slate-200 rounded-lg px-2 py-1 focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white w-full">
                        @endif
                    </td>
                </tr>
                @endforeach

                <x-cuestionario-yn-row label="Realiza transferencias de operación virtual" id="p-trans-immex"/>

                {{-- IVA/EPS + modalidad --}}
                <tr class="bg-white border-b border-slate-100">
                    <td class="px-4 py-2.5 text-slate-700 font-medium">Cuenta con Registro como Empresa Certificada IVA/EPS</td>
                    <td class="px-2 py-2 text-center">
                        <label class="inline-flex flex-col items-center gap-1 cursor-pointer">
                            <input type="radio" name="p-iva-eps" value="1" class="w-4 h-4 text-indigo-600 border-slate-300 cursor-pointer">
                            <span class="text-xs text-slate-500">Sí</span>
                        </label>
                    </td>
                    <td class="px-2 py-2 text-center">
                        <label class="inline-flex flex-col items-center gap-1 cursor-pointer">
                            <input type="radio" name="p-iva-eps" value="0" class="w-4 h-4 text-indigo-600 border-slate-300 cursor-pointer" checked>
                            <span class="text-xs text-slate-500">No</span>
                        </label>
                    </td>
                    <td class="px-4 py-2">
                        <div class="flex flex-col gap-1">
                            <input type="text" id="p-iva-eps-modalidad" placeholder="Modalidad"
                                   class="w-full text-sm border border-slate-200 rounded-lg px-2 py-1 focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white">
                            <input type="date" id="p-iva-eps-fecha"
                                   class="w-full text-sm border border-slate-200 rounded-lg px-2 py-1 focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white">
                        </div>
                    </td>
                </tr>

                {{-- CT-PAT + fecha --}}
                <tr class="bg-slate-50 border-b border-slate-100">
                    <td class="px-4 py-2.5 text-slate-700 font-medium">Cuenta con registro de CT-PAT</td>
                    <td class="px-2 py-2 text-center">
                        <label class="inline-flex flex-col items-center gap-1 cursor-pointer">
                            <input type="radio" name="p-ctpat" value="1" class="w-4 h-4 text-indigo-600 border-slate-300 cursor-pointer">
                            <span class="text-xs text-slate-500">Sí</span>
                        </label>
                    </td>
                    <td class="px-2 py-2 text-center">
                        <label class="inline-flex flex-col items-center gap-1 cursor-pointer">
                            <input type="radio" name="p-ctpat" value="0" class="w-4 h-4 text-indigo-600 border-slate-300 cursor-pointer" checked>
                            <span class="text-xs text-slate-500">No</span>
                        </label>
                    </td>
                    <td class="px-4 py-2">
                        <div class="flex items-center gap-2">
                            <label class="text-xs text-slate-500 font-medium whitespace-nowrap">Desde:</label>
                            <input type="date" id="p-ctpat-fecha"
                                   class="w-full text-sm border border-slate-200 rounded-lg px-2 py-1 focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white">
                        </div>
                    </td>
                </tr>

                <x-cuestionario-yn-row label="Utiliza Regla Octava" id="p-regla-octava"/>

                {{-- Automotriz + fecha --}}
                <tr class="bg-white border-b border-slate-100">
                    <td class="px-4 py-2.5 text-slate-700 font-medium">Tiene autorización de Ind. Automotriz Terminal (Depósito Fiscal Automotriz)</td>
                    <td class="px-2 py-2 text-center">
                        <label class="inline-flex flex-col items-center gap-1 cursor-pointer">
                            <input type="radio" name="p-automotriz" value="1" class="w-4 h-4 text-indigo-600 border-slate-300 cursor-pointer">
                            <span class="text-xs text-slate-500">Sí</span>
                        </label>
                    </td>
                    <td class="px-2 py-2 text-center">
                        <label class="inline-flex flex-col items-center gap-1 cursor-pointer">
                            <input type="radio" name="p-automotriz" value="0" class="w-4 h-4 text-indigo-600 border-slate-300 cursor-pointer" checked>
                            <span class="text-xs text-slate-500">No</span>
                        </label>
                    </td>
                    <td class="px-4 py-2">
                        <div class="flex items-center gap-2">
                            <label class="text-xs text-slate-500 font-medium whitespace-nowrap">Desde:</label>
                            <input type="date" id="p-automotriz-fecha"
                                   class="w-full text-sm border border-slate-200 rounded-lg px-2 py-1 focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white">
                        </div>
                    </td>
                </tr>

                <x-cuestionario-yn-row label="Es Proveedor de la Industria Automotriz (Autopartes)" id="p-proveedor-autopartes"/>
                <x-cuestionario-yn-row label="Utiliza el régimen de depósito fiscal / o recinto fiscalizado estratégico" id="p-almacen-fiscal"/>
                <x-cuestionario-yn-row label="Utiliza regla 2° para la importación de líneas de producción" id="p-regla-2"/>
                <x-cuestionario-yn-row label="Cuenta con Estudio de Precios de Transferencia" id="p-precios-transf"/>
                <x-cuestionario-yn-row label="Cuenta con Estudio de Valoración Aduanera" id="p-valoracion"/>

                {{-- NOM + tipo --}}
                <tr class="bg-white border-b border-slate-100">
                    <td class="px-4 py-2.5 text-slate-700 font-medium">Importa Mercancías Sujetas a NOM</td>
                    <td class="px-2 py-2 text-center">
                        <label class="inline-flex flex-col items-center gap-1 cursor-pointer">
                            <input type="radio" name="p-nom" value="1" class="w-4 h-4 text-indigo-600 border-slate-300 cursor-pointer">
                            <span class="text-xs text-slate-500">Sí</span>
                        </label>
                    </td>
                    <td class="px-2 py-2 text-center">
                        <label class="inline-flex flex-col items-center gap-1 cursor-pointer">
                            <input type="radio" name="p-nom" value="0" class="w-4 h-4 text-indigo-600 border-slate-300 cursor-pointer" checked>
                            <span class="text-xs text-slate-500">No</span>
                        </label>
                    </td>
                    <td class="px-4 py-2">
                        <input type="text" id="p-nom-tipo" placeholder="Tipo de NOM"
                               class="w-full text-sm border border-slate-200 rounded-lg px-2 py-1 focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white">
                    </td>
                </tr>

                <x-cuestionario-yn-row label="Cuenta con proveedores de sub maquila y sub manufactura" id="p-sub-maquila"/>
                <x-cuestionario-yn-row label="Importa Mercancías Sujetas a Precios Estimados" id="p-precios-estimados"/>
                <x-cuestionario-yn-row label="Importa Mercancías Sujetas a Permisos o Avisos de Importación" id="p-permisos-avisos"/>

                <tr class="bg-white border-b border-slate-100">
                    <td class="px-4 py-2.5 text-slate-700 font-medium">¿Cuál es el destino de los desperdicios?</td>
                    <td colspan="3" class="px-4 py-2">
                        <input type="text" id="p-desperdicios" class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white">
                    </td>
                </tr>

                <x-cuestionario-yn-row label="Utiliza Certificados de Origen T-MEC para Importar mercancías" id="p-tlcan-imp"/>
                <x-cuestionario-yn-row label="Utiliza Certificados de Origen TLCUEM para Importar mercancías" id="p-tlcue-imp"/>
                <x-cuestionario-yn-row label="Exporta a EUA y Canadá mercancías manufacturadas" id="p-exp-eua"/>
                <x-cuestionario-yn-row label="Exporta a la Unión Europea mercancías manufacturadas" id="p-exp-ue"/>
                <x-cuestionario-yn-row label="Emite Certificados de Origen a sus clientes en EUA y Canadá" id="p-cert-eua"/>
                <x-cuestionario-yn-row label="Emite Certificados de Origen a sus clientes en la Unión Europea" id="p-cert-ue"/>

                {{-- ── SISTEMAS DE INFORMACIÓN ────────────────────── --}}
                <tr>
                    <td colspan="4" class="bg-slate-700 text-white font-bold text-xs uppercase tracking-wider px-4 py-2.5 text-left">
                        Sistemas de Información
                    </td>
                </tr>
                <tr class="bg-white border-b border-slate-100">
                    <td class="px-4 py-2.5 text-slate-700 font-medium">Nombre de su sistema de Manufactura (ERP)</td>
                    <td colspan="3" class="px-4 py-2"><input type="text" id="p-erp" class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white"></td>
                </tr>
                <tr class="bg-slate-50 border-b border-slate-100">
                    <td class="px-4 py-2.5 text-slate-700 font-medium">Nombre de su sistema de Anexo 24 (en caso de aplicar)</td>
                    <td colspan="3" class="px-4 py-2"><input type="text" id="p-anexo24" class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white"></td>
                </tr>
                <x-cuestionario-yn-row label="Recibe información electrónica de sus Agentes Aduanales" id="p-agentes-electronicos"/>

                {{-- ── MANUALES ────────────────────────────────────── --}}
                <tr>
                    <td colspan="4" class="bg-slate-700 text-white font-bold text-xs uppercase tracking-wider px-4 py-2.5 text-left">
                        Manuales
                    </td>
                </tr>
                <x-cuestionario-yn-row label="Cuenta con procedimientos de comercio exterior" id="p-manual-ce"/>

                {{-- ── ANTECEDENTES ────────────────────────────────── --}}
                <tr>
                    <td colspan="4" class="bg-slate-700 text-white font-bold text-xs uppercase tracking-wider px-4 py-2.5 text-left">
                        Antecedentes
                    </td>
                </tr>
                @php
                    $antRows = [
                        ['id' => 'p-audit-interna', 'label' => 'Fecha de la última auditoría interna y responsable de la realización'],
                        ['id' => 'p-audit-externa', 'label' => 'Fecha de la última auditoría externa y firma auditora'],
                        ['id' => 'p-hallazgos',     'label' => 'Principales Hallazgos'],
                        ['id' => 'p-observaciones', 'label' => 'Observaciones y en su caso, multas fincadas y/o pagadas'],
                    ];
                @endphp
                @foreach($antRows as $r)
                <tr class="{{ $loop->even ? 'bg-slate-50' : 'bg-white' }} border-b border-slate-100">
                    <td class="px-4 py-2.5 text-slate-700 font-medium">{{ $r['label'] }}</td>
                    <td colspan="3" class="px-4 py-2"><input type="text" id="{{ $r['id'] }}" class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white"></td>
                </tr>
                @endforeach

                <tr class="bg-white border-b border-slate-100">
                    <td class="px-4 py-2.5 text-slate-700 font-medium">Han sido auditadas por la SHCP y SE en operaciones de Com. Exterior</td>
                    <td class="px-2 py-2 text-center">
                        <label class="inline-flex flex-col items-center gap-1 cursor-pointer">
                            <input type="radio" name="p-shcp" value="1" class="w-4 h-4 text-indigo-600 border-slate-300 cursor-pointer">
                            <span class="text-xs text-slate-500">Sí</span>
                        </label>
                    </td>
                    <td class="px-2 py-2 text-center">
                        <label class="inline-flex flex-col items-center gap-1 cursor-pointer">
                            <input type="radio" name="p-shcp" value="0" class="w-4 h-4 text-indigo-600 border-slate-300 cursor-pointer" checked>
                            <span class="text-xs text-slate-500">No</span>
                        </label>
                    </td>
                    <td class="px-4 py-2"><input type="date" id="p-shcp-fecha" class="text-sm border border-slate-200 rounded-lg px-2 py-1 focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white w-full"></td>
                </tr>

                {{-- ── VOLUMEN DE OPERACIONES ──────────────────────── --}}
                <tr>
                    <td colspan="4" class="bg-slate-700 text-white font-bold text-xs uppercase tracking-wider px-4 py-2.5 text-left">
                        Volumen de Operaciones
                    </td>
                </tr>
                @php
                    $volRows = [
                        ['id' => 'p-ped-imp',    'label' => 'Cantidad de Pedimentos Anuales Tramitados de Importación', 'type' => 'number'],
                        ['id' => 'p-ped-exp',    'label' => 'Cantidad de Pedimentos Anuales Tramitados de Exportación', 'type' => 'number'],
                        ['id' => 'p-aduana-imp', 'label' => 'Principales aduanas de importación',  'type' => 'text'],
                        ['id' => 'p-aduana-exp', 'label' => 'Principales aduanas de exportación',  'type' => 'text'],
                    ];
                @endphp
                @foreach($volRows as $r)
                <tr class="{{ $loop->even ? 'bg-slate-50' : 'bg-white' }} border-b border-slate-100">
                    <td class="px-4 py-2.5 text-slate-700 font-medium">{{ $r['label'] }}</td>
                    <td colspan="3" class="px-4 py-2"><input type="{{ $r['type'] }}" id="{{ $r['id'] }}" min="0" class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white"></td>
                </tr>
                @endforeach

                {{-- ── PROVEEDORES Y CLIENTES ──────────────────────── --}}
                <tr>
                    <td colspan="4" class="bg-slate-700 text-white font-bold text-xs uppercase tracking-wider px-4 py-2.5 text-left">
                        Proveedores y Clientes
                    </td>
                </tr>
                @php
                    $provRows = [
                        ['id' => 'p-prov-cant',   'label' => 'Cantidad de Proveedores Extranjeros durante el ejercicio', 'type' => 'number'],
                        ['id' => 'p-pais-origen', 'label' => 'País de origen más representativo de las importaciones',   'type' => 'text'],
                        ['id' => 'p-cli-cant',    'label' => 'Cantidad de clientes durante un ejercicio fiscal',    'type' => 'number'],
                        ['id' => 'p-pais-dest',   'label' => 'País de Destino más frecuente de sus exportaciones',       'type' => 'text'],
                        ['id' => 'p-insumos',     'label' => 'Insumos de Importación más importantes',                   'type' => 'text'],
                        ['id' => 'p-productos',   'label' => 'Productos de Exportación más representativos',             'type' => 'text'],
                    ];
                @endphp
                @foreach($provRows as $r)
                <tr class="{{ $loop->even ? 'bg-slate-50' : 'bg-white' }} border-b border-slate-100">
                    <td class="px-4 py-2.5 text-slate-700 font-medium">{{ $r['label'] }}</td>
                    <td colspan="3" class="px-4 py-2"><input type="{{ $r['type'] }}" id="{{ $r['id'] }}" min="0" class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white"></td>
                </tr>
                @endforeach
                {{-- Fuera T-MEC + países --}}
                <tr class="bg-white border-b border-slate-100">
                    <td class="px-4 py-2.5 text-slate-700 font-medium">Importa materiales de la región fuera del T-MEC y TLCUEM</td>
                    <td class="px-2 py-2 text-center">
                        <label class="inline-flex flex-col items-center gap-1 cursor-pointer">
                            <input type="radio" name="p-fuera-tlcan" value="1" class="w-4 h-4 text-indigo-600 border-slate-300 cursor-pointer">
                            <span class="text-xs text-slate-500">Sí</span>
                        </label>
                    </td>
                    <td class="px-2 py-2 text-center">
                        <label class="inline-flex flex-col items-center gap-1 cursor-pointer">
                            <input type="radio" name="p-fuera-tlcan" value="0" class="w-4 h-4 text-indigo-600 border-slate-300 cursor-pointer" checked>
                            <span class="text-xs text-slate-500">No</span>
                        </label>
                    </td>
                    <td class="px-4 py-2">
                        <input type="text" id="p-fuera-tlcan-paises" placeholder="Países"
                               class="w-full text-sm border border-slate-200 rounded-lg px-2 py-1 focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white">
                    </td>
                </tr>

                {{-- ── INFORMANTE ──────────────────────────────────── --}}
                <tr>
                    <td colspan="4" class="bg-slate-700 text-white font-bold text-xs uppercase tracking-wider px-4 py-2.5 text-center">
                        Información Proporcionada Por
                    </td>
                </tr>
                <tr class="bg-white border-b border-slate-100">
                    <td class="px-4 py-2.5 text-slate-700 font-medium">Nombre</td>
                    <td colspan="3" class="px-4 py-2"><input type="text" id="p-inf-nombre" class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white"></td>
                </tr>
                <tr class="bg-slate-50 border-b border-slate-100">
                    <td class="px-4 py-2.5 text-slate-700 font-medium">Puesto</td>
                    <td colspan="3" class="px-4 py-2"><input type="text" id="p-inf-puesto" class="w-full px-3 py-1.5 text-sm border border-slate-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white"></td>
                </tr>
                <tr class="bg-white">
                    <td class="px-4 py-2.5 text-slate-700 font-medium">Fecha</td>
                    <td colspan="3" class="px-4 py-2"><input type="date" id="p-inf-fecha" class="text-sm border border-slate-200 rounded-lg px-2 py-1 focus:outline-none focus:ring-2 focus:ring-indigo-300 bg-white"></td>
                </tr>

            </table>

            <p id="form-error" class="mx-6 my-4 text-sm text-red-600 hidden"></p>
            </form>
            </div>{{-- /overflow-y-auto --}}

            {{-- Pie --}}
            <div class="shrink-0 bg-white border-t border-slate-200 px-6 py-4 flex justify-end gap-3 shadow-[0_-4px_12px_rgba(0,0,0,.06)]">
                <button onclick="cerrarModal()" class="px-4 py-2 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition">Cancelar</button>
                <button onclick="guardar()" id="btn-guardar"
                        class="px-5 py-2 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition shadow-lg shadow-indigo-200">
                    Guardar Cliente
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     MODAL — VISUALIZAR INFORMACIÓN COMPLETA
══════════════════════════════════════════════════════════════ --}}
<div id="modal-ver" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-modal="true">
    <div class="flex min-h-full items-start justify-center p-4 pt-6">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="cerrarVerModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl z-10 mb-10 overflow-hidden flex flex-col" style="max-height:92vh">
            <div class="shrink-0 bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-900" id="ver-titulo"></h3>
                    <p class="text-xs text-slate-500 mt-0.5">Perfil de Clientes</p>
                </div>
                <button onclick="cerrarVerModal()" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="overflow-y-auto flex-1 p-6 space-y-6" id="ver-cuerpo"></div>
            <div class="shrink-0 bg-white border-t border-slate-200 px-6 py-4 flex justify-end">
                <button onclick="cerrarVerModal()" class="px-4 py-2 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition">Cerrar</button>
            </div>
        </div>
    </div>
</div>

{{-- ═══════════════════════════════════════════════════════════
     MODAL — IMPORTAR EXCEL
══════════════════════════════════════════════════════════════ --}}
<div id="modal-importar" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-modal="true">
    <div class="flex min-h-full items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="cerrarModalImportar()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg z-10 overflow-hidden">
            <div class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Importar Clientes desde Excel</h3>
                    <p class="text-xs text-slate-500 mt-0.5">Selecciona el archivo .xlsx con la plantilla llena</p>
                </div>
                <button onclick="cerrarModalImportar()" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="px-6 py-6 space-y-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-1.5">Archivo Excel</label>
                    <input type="file" id="input-excel" accept=".xlsx,.xls"
                           class="block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer">
                </div>
                <div id="import-progress" class="hidden">
                    <div class="flex items-center gap-3 text-sm text-slate-600">
                        <svg class="animate-spin h-5 w-5 text-indigo-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span>Procesando archivo…</span>
                    </div>
                </div>
                <div id="import-result" class="hidden"></div>
            </div>
            <div class="bg-white border-t border-slate-200 px-6 py-4 flex justify-end gap-3">
                <button onclick="cerrarModalImportar()" class="px-4 py-2 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition">Cerrar</button>
                <button onclick="importarExcel()" id="btn-importar"
                        class="px-5 py-2 text-sm font-bold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition shadow-lg shadow-emerald-200">
                    Importar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
const modal     = document.getElementById('modal');
const formError = document.getElementById('form-error');
let editandoId  = null;

function sv(id, val) { const el = document.getElementById(id); if (el) el.value = val ?? ''; }
function sr(name, val) {
    const el = document.querySelector('input[name="' + name + '"][value="' + (val ? 1 : 0) + '"]');
    if (el) el.checked = true;
}
function gv(id)   { return (document.getElementById(id)?.value ?? '').trim(); }
function gr(name) { return document.querySelector('input[name="' + name + '"]:checked')?.value ?? '0'; }

function limpiarForm() {
    document.querySelectorAll('#form-cliente input[type="text"], #form-cliente input[type="date"], #form-cliente input[type="number"], #form-cliente input[type="email"]')
        .forEach(el => el.value = '');
    document.querySelectorAll('#form-cliente input[type="radio"][value="0"]')
        .forEach(el => el.checked = true);
    formError.classList.add('hidden');
}

function abrirModal() {
    editandoId = null;
    document.getElementById('modal-titulo').textContent = 'Nuevo Cliente';
    limpiarForm();
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function abrirEditar(c) {
    editandoId = c.id;
    document.getElementById('modal-titulo').textContent = 'Editar — ' + c.nombre;
    limpiarForm();
    const p = c.perfil ?? {};
    sv('p-nombre-legal',       p.nombre_legal ?? c.nombre);
    sv('p-sectores',           p.sectores_productivos);
    sv('p-fecha-inicio',       (p.fecha_inicio_operaciones ?? '').substring(0, 10));
    sv('p-nombre-corporativo', p.nombre_corporativo);
    sv('p-ciudad-corporativo', p.ciudad_estado_pais_corporativo);
    sr('p-partes-relacionadas', p.partes_relacionadas_extranjero);
    sr('p-registro-marca',     p.registro_marca);
    sr('p-poliza-seguro',      p.poliza_seguro_mercancias);
    sr('p-immex',              p.tiene_immex);              sv('p-immex-fecha',          (p.immex_fecha ?? '').substring(0,10));
    sr('p-immex-servicios',    p.tiene_immex_servicios);   sv('p-immex-servicios-fecha', (p.immex_servicios_fecha ?? '').substring(0,10));
    sr('p-maquiladora',        p.es_maquiladora);           sv('p-maquiladora-fecha',   (p.maquiladora_fecha ?? '').substring(0,10));
    sr('p-maq-servicios',      p.maquiladora_servicios);    sv('p-maq-servicios-fecha', (p.maquiladora_servicios_fecha ?? '').substring(0,10));
    sr('p-prosec',             p.tiene_prosec);             sv('p-prosec-fecha',        (p.prosec_fecha ?? '').substring(0,10));
    sr('p-trans-immex',        p.transferencias_otras_immex);
    sr('p-oea',                p.empresa_certificada_oea);        sv('p-oea-fecha',           (p.oea_fecha ?? '').substring(0,10));
    sr('p-iva-eps',            p.empresa_certificada_iva_eps);  sv('p-iva-eps-modalidad', p.iva_eps_modalidad);  sv('p-iva-eps-fecha', (p.iva_eps_fecha ?? '').substring(0,10));
    sr('p-ctpat',              p.tiene_ctpat);                  sv('p-ctpat-fecha',        (p.ctpat_fecha ?? '').substring(0,10));
    sr('p-regla-octava',       p.utiliza_regla_octava);
    sr('p-automotriz',         p.automotriz_deposito_fiscal);   sv('p-automotriz-fecha', (p.automotriz_fecha ?? '').substring(0,10));
    sr('p-proveedor-autopartes', p.proveedor_autopartes);
    sr('p-almacen-fiscal',     p.utiliza_almacen_fiscal);
    sr('p-regla-2',            p.utiliza_regla_2);
    sr('p-precios-transf',     p.estudio_precios_transferencia);
    sr('p-valoracion',         p.estudio_valoracion_aduanera);
    sr('p-nom',                p.importa_mercancias_nom);        sv('p-nom-tipo',           p.nom_tipo);
    sr('p-sub-maquila',        p.proveedores_sub_maquila);
    sr('p-precios-estimados',  p.importa_precios_estimados);
    sr('p-permisos-avisos',    p.importa_permisos_avisos);
    sv('p-desperdicios',       p.destino_desperdicios);
    sr('p-tlcan-imp',          p.certificados_origen_tlcan);
    sr('p-tlcue-imp',          p.certificados_origen_tlcue);
    sr('p-exp-eua',            p.exporta_eua_canada);
    sr('p-exp-ue',             p.exporta_union_europea);
    sr('p-cert-eua',           p.emite_certificados_eua_canada);
    sr('p-cert-ue',            p.emite_certificados_union_europea);
    sv('p-erp',                p.sistema_manufactura_erp);
    sv('p-anexo24',            p.sistema_anexo_24);
    sr('p-agentes-electronicos', p.recibe_info_agentes_aduanales);
    sr('p-manual-ce',          p.manual_procedimientos_ce);
    sv('p-audit-interna',      p.ultima_auditoria_interna);
    sv('p-audit-externa',      p.ultima_auditoria_externa);
    sv('p-hallazgos',          p.principales_hallazgos);
    sv('p-observaciones',      p.observaciones_multas);
    sr('p-shcp',               p.auditado_shcp_se);         sv('p-shcp-fecha', (p.auditado_shcp_se_fecha ?? '').substring(0,10));
    sv('p-ped-imp',            p.pedimentos_anuales_importacion);
    sv('p-ped-exp',            p.pedimentos_anuales_exportacion);
    sv('p-aduana-imp',         p.aduana_principal_importacion);
    sv('p-aduana-exp',         p.aduana_principal_exportacion);
    sv('p-prov-cant',          p.proveedores_extranjeros_cantidad);
    sv('p-pais-origen',        p.pais_origen_importaciones);
    sr('p-fuera-tlcan',        p.importa_fuera_tlcan);  sv('p-fuera-tlcan-paises', p.importa_fuera_tlcan_paises);
    sv('p-cli-cant',           p.clientes_extranjeros_cantidad);
    sv('p-pais-dest',          p.pais_destino_exportaciones);
    sv('p-insumos',            p.insumos_importacion_importantes);
    sv('p-productos',          p.productos_exportacion_representativos);
    sv('p-inf-nombre',         p.informante_nombre);
    sv('p-inf-puesto',         p.informante_puesto);
    sv('p-inf-fecha',          (p.informante_fecha ?? '').substring(0,10));
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function cerrarModal() {
    modal.classList.add('hidden');
    document.body.style.overflow = '';
    editandoId = null;
}

function guardar() {
    const nombreLegal = gv('p-nombre-legal');
    if (!nombreLegal) {
        formError.textContent = 'El Nombre Legal de la Empresa es obligatorio.';
        formError.classList.remove('hidden');
        document.getElementById('p-nombre-legal').focus();
        return;
    }

    const payload = {
        nombre_legal:                       nombreLegal,
        sectores_productivos:               gv('p-sectores'),
        fecha_inicio_operaciones:           gv('p-fecha-inicio'),
        nombre_corporativo:                 gv('p-nombre-corporativo'),
        ciudad_estado_pais_corporativo:     gv('p-ciudad-corporativo'),
        partes_relacionadas_extranjero:     gr('p-partes-relacionadas'),
        registro_marca:                     gr('p-registro-marca'),
        poliza_seguro_mercancias:           gr('p-poliza-seguro'),
        tiene_immex:                        gr('p-immex'),
        immex_fecha:                        gv('p-immex-fecha'),
        tiene_immex_servicios:              gr('p-immex-servicios'),
        immex_servicios_fecha:              gv('p-immex-servicios-fecha'),
        es_maquiladora:                     gr('p-maquiladora'),
        maquiladora_fecha:                  gv('p-maquiladora-fecha'),
        maquiladora_servicios:              gr('p-maq-servicios'),
        maquiladora_servicios_fecha:        gv('p-maq-servicios-fecha'),
        tiene_prosec:                       gr('p-prosec'),
        prosec_fecha:                       gv('p-prosec-fecha'),
        transferencias_otras_immex:         gr('p-trans-immex'),
        empresa_certificada_oea:            gr('p-oea'),
        oea_fecha:                          gv('p-oea-fecha'),
        empresa_certificada_iva_eps:        gr('p-iva-eps'),
        iva_eps_modalidad:                  gv('p-iva-eps-modalidad'),
        iva_eps_fecha:                      gv('p-iva-eps-fecha'),
        tiene_ctpat:                        gr('p-ctpat'),
        ctpat_fecha:                        gv('p-ctpat-fecha'),
        utiliza_regla_octava:               gr('p-regla-octava'),
        automotriz_deposito_fiscal:         gr('p-automotriz'),
        automotriz_fecha:                   gv('p-automotriz-fecha'),
        proveedor_autopartes:               gr('p-proveedor-autopartes'),
        utiliza_almacen_fiscal:             gr('p-almacen-fiscal'),
        utiliza_regla_2:                    gr('p-regla-2'),
        estudio_precios_transferencia:      gr('p-precios-transf'),
        estudio_valoracion_aduanera:        gr('p-valoracion'),
        importa_mercancias_nom:             gr('p-nom'),
        nom_tipo:                           gv('p-nom-tipo'),
        proveedores_sub_maquila:            gr('p-sub-maquila'),
        importa_precios_estimados:          gr('p-precios-estimados'),
        importa_permisos_avisos:            gr('p-permisos-avisos'),
        destino_desperdicios:               gv('p-desperdicios'),
        certificados_origen_tlcan:          gr('p-tlcan-imp'),
        certificados_origen_tlcue:          gr('p-tlcue-imp'),
        exporta_eua_canada:                 gr('p-exp-eua'),
        exporta_union_europea:              gr('p-exp-ue'),
        emite_certificados_eua_canada:      gr('p-cert-eua'),
        emite_certificados_union_europea:   gr('p-cert-ue'),
        sistema_manufactura_erp:            gv('p-erp'),
        sistema_anexo_24:                   gv('p-anexo24'),
        recibe_info_agentes_aduanales:      gr('p-agentes-electronicos'),
        manual_procedimientos_ce:           gr('p-manual-ce'),
        ultima_auditoria_interna:           gv('p-audit-interna'),
        ultima_auditoria_externa:           gv('p-audit-externa'),
        principales_hallazgos:              gv('p-hallazgos'),
        observaciones_multas:               gv('p-observaciones'),
        auditado_shcp_se:                   gr('p-shcp'),
        auditado_shcp_se_fecha:             gv('p-shcp-fecha'),
        pedimentos_anuales_importacion:     gv('p-ped-imp'),
        pedimentos_anuales_exportacion:     gv('p-ped-exp'),
        aduana_principal_importacion:       gv('p-aduana-imp'),
        aduana_principal_exportacion:       gv('p-aduana-exp'),
        proveedores_extranjeros_cantidad:   gv('p-prov-cant'),
        pais_origen_importaciones:          gv('p-pais-origen'),
        importa_fuera_tlcan:                gr('p-fuera-tlcan'),
        importa_fuera_tlcan_paises:         gv('p-fuera-tlcan-paises'),
        clientes_extranjeros_cantidad:      gv('p-cli-cant'),
        pais_destino_exportaciones:         gv('p-pais-dest'),
        insumos_importacion_importantes:    gv('p-insumos'),
        productos_exportacion_representativos: gv('p-productos'),
        informante_nombre:                  gv('p-inf-nombre'),
        informante_puesto:                  gv('p-inf-puesto'),
        informante_fecha:                   gv('p-inf-fecha'),
    };

    const url    = editandoId ? '/administracion/clientes/' + editandoId : '/administracion/clientes';
    const method = editandoId ? 'PUT' : 'POST';
    const btn    = document.getElementById('btn-guardar');
    btn.disabled = true;
    btn.innerHTML = '<svg class="animate-spin -ml-1 mr-2 h-4 w-4 inline-block" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg> Guardando…';

    fetch(url, {
        method,
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        body: JSON.stringify(payload),
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) { cerrarModal(); location.reload(); }
        else {
            formError.textContent = data.message ?? 'Error al guardar.';
            formError.classList.remove('hidden');
        }
    })
    .catch(() => { formError.textContent = 'Error de conexión.'; formError.classList.remove('hidden'); })
    .finally(() => { btn.disabled = false; btn.innerHTML = 'Guardar Cliente'; });
}

function eliminar(id, nombre) {
    if (!confirm('¿Eliminar al cliente "' + nombre + '"? Esta acción no se puede deshacer.')) return;
    fetch('/administracion/clientes/' + id, {
        method: 'DELETE',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
    })
    .then(r => r.json())
    .then(data => { if (data.success) location.reload(); else alert(data.message ?? 'Error al eliminar.'); })
    .catch(() => alert('Error de conexión al eliminar.'));
}

function verCliente(c) {
    const p = c.perfil ?? {};
    document.getElementById('ver-titulo').textContent = c.nombre;

    function bool(val) {
        return (val == 1 || val === true)
            ? '<span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold border border-emerald-100">Sí</span>'
            : '<span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-xs font-semibold">No</span>';
    }
    function txt(val) { return val ? String(val) : '<span class="text-slate-300">—</span>'; }
    function row(label, val) {
        return '<div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-slate-400 mb-1">' + label + '</p><div class="text-sm text-slate-800">' + val + '</div></div>';
    }
    function boolFecha(boolVal, fecha) {
        return bool(boolVal) + (fecha ? ' <span class="text-xs text-slate-400 ml-1">desde ' + fecha.substring(0,10) + '</span>' : '');
    }
    function section(title, rows) {
        return '<div><h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3 pb-2 border-b border-slate-100">' + title + '</h4>'
             + '<div class="grid grid-cols-1 md:grid-cols-2 gap-3">' + rows.join('') + '</div></div>';
    }

    document.getElementById('ver-cuerpo').innerHTML = [
        section('Datos Generales', [
            row('Nombre Legal', txt(p.nombre_legal ?? c.nombre)),
            row('Sectores Productivos', txt(p.sectores_productivos)),
            row('Fecha Inicio de Operaciones', txt(p.fecha_inicio_operaciones ? p.fecha_inicio_operaciones.substring(0,10) : null)),
            row('Partes Relacionadas en Extranjero', bool(p.partes_relacionadas_extranjero)),
            row('Nombre del Corporativo', txt(p.nombre_corporativo)),
            row('Ciudad / Estado / País del Corporativo', txt(p.ciudad_estado_pais_corporativo)),
            row('Registro de Marca', bool(p.registro_marca)),
            row('Póliza de Seguro de las Mercancías', bool(p.poliza_seguro_mercancias)),
        ]),
        section('Perfil de la Empresa', [
            row('Programa IMMEX', boolFecha(p.tiene_immex, p.immex_fecha)),
            row('Maquiladora', boolFecha(p.es_maquiladora, p.maquiladora_fecha)),
            row('Maquiladora de Servicios', boolFecha(p.maquiladora_servicios, p.maquiladora_servicios_fecha)),
            row('PROSEC', boolFecha(p.tiene_prosec, p.prosec_fecha)),
            row('Transferencias otras IMMEX', bool(p.transferencias_otras_immex)),
            row('Empresa Certificada OEA', bool(p.empresa_certificada_oea)),
            row('Empresa Certificada IVA/EPS', bool(p.empresa_certificada_iva_eps) + (p.iva_eps_modalidad ? ' <span class="text-xs text-slate-500">(' + p.iva_eps_modalidad + ')</span>' : '')),
            row('CT-PAT', bool(p.tiene_ctpat) + (p.ctpat_fecha ? ' <span class="text-xs text-slate-500">desde ' + p.ctpat_fecha.substring(0,10) + '</span>' : '')),
            row('Utiliza Regla Octava', bool(p.utiliza_regla_octava)),
            row('Automotriz Depósito Fiscal', bool(p.automotriz_deposito_fiscal) + (p.automotriz_fecha ? ' <span class="text-xs text-slate-500">desde ' + p.automotriz_fecha.substring(0,10) + '</span>' : '')),
            row('Depósito Fiscal / Recinto Fiscalizado Estratégico', bool(p.utiliza_almacen_fiscal)),
            row('Regla 2° Importación Líneas de Producción', bool(p.utiliza_regla_2)),
            row('Estudio Precios de Transferencia', bool(p.estudio_precios_transferencia)),
            row('Estudio Valoración Aduanera', bool(p.estudio_valoracion_aduanera)),
            row('Importa Mercancías NOM', bool(p.importa_mercancias_nom) + (p.nom_tipo ? ' <span class="text-xs text-slate-500">(' + p.nom_tipo + ')</span>' : '')),
            row('Proveedores Sub Maquila / Sub Manufactura / T-MEC', bool(p.proveedores_sub_maquila)),
            row('Importa Precios Estimados', bool(p.importa_precios_estimados)),
            row('Importa con Permisos / Avisos', bool(p.importa_permisos_avisos)),
            row('Destino de Desperdicios', txt(p.destino_desperdicios)),
            row('Cert. Origen T-MEC (Importar)', bool(p.certificados_origen_tlcan)),
            row('Cert. Origen TLCUEM (Importar)', bool(p.certificados_origen_tlcue)),
            row('Exporta a EUA / Canadá', bool(p.exporta_eua_canada)),
            row('Exporta a Unión Europea', bool(p.exporta_union_europea)),
            row('Emite Cert. Origen EUA / Canadá', bool(p.emite_certificados_eua_canada)),
            row('Emite Cert. Origen Unión Europea', bool(p.emite_certificados_union_europea)),
        ]),
        section('Sistemas de Información', [
            row('Sistema ERP (Manufactura)', txt(p.sistema_manufactura_erp)),
            row('Sistema Anexo 24', txt(p.sistema_anexo_24)),
            row('Recibe Info Electrónica Agentes Aduanales', bool(p.recibe_info_agentes_aduanales)),
        ]),
        section('Manuales', [
            row('Manual de Procedimientos de Comercio Exterior', bool(p.manual_procedimientos_ce)),
        ]),
        section('Antecedentes', [
            row('Última Auditoría Interna', txt(p.ultima_auditoria_interna)),
            row('Última Auditoría Externa', txt(p.ultima_auditoria_externa)),
            row('Principales Hallazgos', txt(p.principales_hallazgos)),
            row('Observaciones y Multas', txt(p.observaciones_multas)),
            row('Auditado SHCP / SE', boolFecha(p.auditado_shcp_se, p.auditado_shcp_se_fecha)),
        ]),
        section('Volumen de Operaciones', [
            row('Pedimentos Anuales Importación', txt(p.pedimentos_anuales_importacion)),
            row('Pedimentos Anuales Exportación', txt(p.pedimentos_anuales_exportacion)),
            row('Aduana Principal Importación', txt(p.aduana_principal_importacion)),
            row('Aduana Principal Exportación', txt(p.aduana_principal_exportacion)),
        ]),
        section('Proveedores y Clientes', [
            row('Cantidad Proveedores Extranjeros', txt(p.proveedores_extranjeros_cantidad)),
            row('País Origen más Representativo (Importación)', txt(p.pais_origen_importaciones)),
            row('Cantidad Clientes Extranjeros', txt(p.clientes_extranjeros_cantidad)),
            row('País Destino más Frecuente (Exportación)', txt(p.pais_destino_exportaciones)),
            row('Insumos de Importación más Importantes', txt(p.insumos_importacion_importantes)),
            row('Productos de Exportación más Representativos', txt(p.productos_exportacion_representativos)),
            row('Importa Fuera de T-MEC / TLCUEM', bool(p.importa_fuera_tlcan) + (p.importa_fuera_tlcan_paises ? ' <span class="text-xs text-slate-500">(' + p.importa_fuera_tlcan_paises + ')</span>' : '')),
        ]),
        section('Información del Informante', [
            row('Nombre', txt(p.informante_nombre)),
            row('Puesto', txt(p.informante_puesto)),
            row('Fecha', txt(p.informante_fecha ? p.informante_fecha.substring(0,10) : null)),
        ]),
    ].join('');

    document.getElementById('modal-ver').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function cerrarVerModal() {
    document.getElementById('modal-ver').classList.add('hidden');
    document.body.style.overflow = '';
}

// ── Importar Excel ──
const modalImportar = document.getElementById('modal-importar');

function abrirModalImportar() {
    document.getElementById('input-excel').value = '';
    document.getElementById('import-result').classList.add('hidden');
    document.getElementById('import-result').innerHTML = '';
    document.getElementById('import-progress').classList.add('hidden');
    modalImportar.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function cerrarModalImportar() {
    modalImportar.classList.add('hidden');
    document.body.style.overflow = '';
}

function importarExcel() {
    const input = document.getElementById('input-excel');
    const file = input.files[0];
    if (!file) {
        mostrarResultado('error', 'Selecciona un archivo Excel primero.');
        return;
    }

    const formData = new FormData();
    formData.append('archivo', file);
    formData.append('_token', '{{ csrf_token() }}');

    document.getElementById('import-progress').classList.remove('hidden');
    document.getElementById('import-result').classList.add('hidden');
    document.getElementById('btn-importar').disabled = true;

    fetch('{{ route("administracion.clientes.importar") }}', {
        method: 'POST',
        body: formData,
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            let html = '<div class="p-3 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700 text-sm font-medium">';
            html += '<svg class="w-5 h-5 inline-block mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
            html += data.message + '</div>';
            if (data.errores && data.errores.length) {
                html += '<div class="mt-3 p-3 rounded-xl bg-red-50 border border-red-100 text-red-700 text-sm"><ul class="list-disc pl-4 space-y-1">';
                data.errores.forEach(e => { html += '<li>' + e + '</li>'; });
                html += '</ul></div>';
            }
            if (data.resultados && data.resultados.length) {
                html += '<div class="mt-3 max-h-40 overflow-y-auto text-xs text-slate-600 space-y-0.5">';
                data.resultados.forEach(r => { html += '<div>' + r + '</div>'; });
                html += '</div>';
            }
            mostrarResultado('success', html);
            setTimeout(() => { cerrarModalImportar(); location.reload(); }, data.errores?.length ? 4000 : 2000);
        } else {
            mostrarResultado('error', data.message ?? 'Error al importar.');
        }
    })
    .catch(() => { mostrarResultado('error', 'Error de conexión al importar.'); })
    .finally(() => {
        document.getElementById('import-progress').classList.add('hidden');
        document.getElementById('btn-importar').disabled = false;
    });
}

function mostrarResultado(tipo, contenido) {
    const el = document.getElementById('import-result');
    el.className = 'mt-2 ' + (tipo === 'error' ? 'text-red-600' : '');
    el.innerHTML = contenido;
    el.classList.remove('hidden');
}

document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-action]');
    if (!btn) return;
    if (btn.dataset.action === 'ver') {
        verCliente(JSON.parse(btn.dataset.cliente));
    } else if (btn.dataset.action === 'editar') {
        abrirEditar(JSON.parse(btn.dataset.cliente));
    } else if (btn.dataset.action === 'eliminar') {
        eliminar(btn.dataset.id, btn.dataset.nombre);
    }
});

document.getElementById('buscador')?.addEventListener('input', function () {
    const t = this.value.toLowerCase();
    let v = 0;
    document.querySelectorAll('.fila').forEach(f => {
        const show = f.dataset.nombre.includes(t);
        f.style.display = show ? '' : 'none';
        if (show) v++;
    });
    const el = document.getElementById('num-visibles');
    if (el) el.textContent = v;
});
</script>
@endsection
