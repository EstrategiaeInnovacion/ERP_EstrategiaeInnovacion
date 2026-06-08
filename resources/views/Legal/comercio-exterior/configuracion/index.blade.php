@extends('layouts.erp')

@section('title', 'Configuración – Comercio Exterior')

@section('content')
<div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

    {{-- Breadcrumb --}}
    <div class="mb-6 flex items-center gap-2 text-sm text-slate-400">
        <a href="{{ route('legal.dashboard') }}" class="hover:text-indigo-600 transition">Legal</a>
        <span>/</span>
        <a href="{{ route('legal.ce.bom.index') }}" class="hover:text-indigo-600 transition">Análisis de Origen</a>
        <span>/</span>
        <span class="text-slate-600">Configuración</span>
    </div>

    <h1 class="text-2xl font-bold text-slate-900 mb-8">Configuración – Comercio Exterior</h1>

    <div class="space-y-6">

        {{-- ── Catálogo de Reglas ──────────────────────────────────────────── --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h2 class="text-base font-semibold text-slate-900">Catálogo de Reglas de Origen</h2>
                <p class="text-sm text-slate-500 mt-0.5">Sube el archivo Excel con las reglas T-MEC para actualizar el catálogo.</p>
            </div>

            {{-- Resumen del catálogo actual --}}
            <div class="px-6 py-4 border-b border-slate-100">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                    @php
                        $stats = [
                            ['label' => 'Reglas de Origen',   'value' => $totalReglas,       'color' => 'text-indigo-600',  'bg' => 'bg-indigo-50'],
                            ['label' => 'Reglas Automotrices','value' => $totalAutomotrices,  'color' => 'text-amber-600',   'bg' => 'bg-amber-50'],
                            ['label' => 'Partes Apéndice',    'value' => $totalPartes,         'color' => 'text-emerald-600', 'bg' => 'bg-emerald-50'],
                            ['label' => 'Sección C',          'value' => $totalSeccionC,       'color' => 'text-sky-600',     'bg' => 'bg-sky-50'],
                        ];
                    @endphp
                    @foreach($stats as $stat)
                    <div class="text-center p-3 {{ $stat['bg'] }} rounded-xl">
                        <p class="text-2xl font-bold {{ $stat['color'] }}">{{ number_format($stat['value']) }}</p>
                        <p class="text-xs text-slate-500 mt-0.5">{{ $stat['label'] }}</p>
                    </div>
                    @endforeach
                </div>
                @if($ultimaImport)
                    <p class="text-xs text-slate-400 mt-3">
                        Última actualización: {{ \Carbon\Carbon::parse($ultimaImport)->format('d/m/Y H:i') }}
                    </p>
                @endif
            </div>

            {{-- Formulario de carga --}}
            <div class="px-6 py-5">
                <form method="POST" action="{{ route('legal.ce.configuracion.catalogo.upload') }}"
                      enctype="multipart/form-data" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-sm font-medium text-slate-700 mb-2">
                            Archivo Excel del catálogo
                            <span class="text-slate-400 font-normal ml-1">(.xlsx · máx. 50 MB)</span>
                        </label>
                        <div class="flex items-center gap-3">
                            <input type="file" name="catalogo" accept=".xlsx,.xls" required
                                   class="block w-full text-sm text-slate-600 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 cursor-pointer border border-slate-200 rounded-xl p-1">
                        </div>
                        <p class="text-xs text-slate-400 mt-1">
                            El archivo debe contener las hojas: "Reglas de Origen", "Apéndice Automotriz", "Apéndice – Tablas de Partes", "Apéndice – Sección C".
                        </p>
                    </div>
                    <div class="flex items-center gap-2 p-3 bg-amber-50 border border-amber-200 rounded-xl">
                        <svg class="w-4 h-4 text-amber-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <p class="text-xs text-amber-700">
                            Esta operación reemplazará <strong>todo el catálogo existente</strong> con los datos del archivo. Las relaciones se recalcularán automáticamente.
                        </p>
                    </div>
                    <button type="submit"
                            class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        Importar Catálogo
                    </button>
                </form>
            </div>
        </div>

        {{-- ── Modo de Análisis ─────────────────────────────────────────────── --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-200 bg-slate-50">
                <h2 class="text-base font-semibold text-slate-900">Motor de Análisis</h2>
                <p class="text-sm text-slate-500 mt-0.5">Selecciona cómo se ejecutará el análisis de origen.</p>
            </div>
            <div class="px-6 py-5">
                <form method="POST" action="{{ route('legal.ce.configuracion.update') }}" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <label class="relative flex items-start gap-4 p-4 border rounded-xl cursor-pointer transition
                                      {{ $modo === 'local' ? 'border-indigo-400 bg-indigo-50' : 'border-slate-200 hover:border-slate-300' }}">
                            <input type="radio" name="modo_analisis" value="local" {{ $modo === 'local' ? 'checked' : '' }}
                                   class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Motor Local</p>
                                <p class="text-xs text-slate-500 mt-0.5">Reglas basadas en el catálogo cargado. Sin dependencias externas. Recomendado.</p>
                            </div>
                        </label>
                        <label class="relative flex items-start gap-4 p-4 border rounded-xl cursor-pointer transition
                                      {{ $modo === 'ia' ? 'border-indigo-400 bg-indigo-50' : 'border-slate-200 hover:border-slate-300' }}">
                            <input type="radio" name="modo_analisis" value="ia" {{ $modo === 'ia' ? 'checked' : '' }}
                                   class="mt-0.5 text-indigo-600 focus:ring-indigo-500">
                            <div>
                                <p class="text-sm font-semibold text-slate-900">Motor IA (Groq)</p>
                                <p class="text-xs text-slate-500 mt-0.5">Análisis asistido por IA. Requiere GROQ_API_KEY configurada en el servidor.</p>
                            </div>
                        </label>
                    </div>
                    <button type="submit"
                            class="inline-flex items-center px-5 py-2.5 bg-slate-700 text-white text-sm font-semibold rounded-xl hover:bg-slate-800 transition shadow-sm">
                        Guardar configuración
                    </button>
                </form>
            </div>
        </div>

        {{-- Accesos rápidos --}}
        <div class="flex gap-3">
            <a href="{{ route('legal.ce.bom.index') }}"
               class="inline-flex items-center px-4 py-2 text-sm text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition">
                ← Volver a BOMs
            </a>
            <a href="{{ route('legal.ce.catalogo.index') }}"
               class="inline-flex items-center px-4 py-2 text-sm text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition">
                Ver catálogo
            </a>
        </div>

    </div>
</div>
@endsection
