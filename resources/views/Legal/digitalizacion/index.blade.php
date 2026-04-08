@extends('layouts.master')

@section('title', 'Digitalización de Documentos — Área Legal')

@section('content')
<div class="min-h-screen bg-slate-50 pb-16">

    {{-- Header --}}
    <div class="bg-white border-b border-slate-200 mb-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center gap-2 text-sm text-slate-400 mb-2">
                <a href="{{ route('legal.dashboard') }}" class="hover:text-sky-600 transition-colors font-medium">Área Legal</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                <span class="text-slate-600">Digitalización de documentos</span>
            </div>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Digitalización de documentos</h1>
                    <p class="text-slate-500 mt-1">Herramientas PDF para cumplir con los requisitos de VUCEM. Todo procesado localmente.</p>
                </div>
                <div class="flex items-center gap-2 px-4 py-2 bg-sky-50 border border-sky-200 rounded-2xl text-sky-700 text-xs font-semibold shrink-0">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4M7.835 4.697a3.42 3.42 0 001.946-.806 3.42 3.42 0 014.438 0 3.42 3.42 0 001.946.806 3.42 3.42 0 013.138 3.138 3.42 3.42 0 00.806 1.946 3.42 3.42 0 010 4.438 3.42 3.42 0 00-.806 1.946 3.42 3.42 0 01-3.138 3.138 3.42 3.42 0 00-1.946.806 3.42 3.42 0 01-4.438 0 3.42 3.42 0 00-1.946-.806 3.42 3.42 0 01-3.138-3.138 3.42 3.42 0 00-.806-1.946 3.42 3.42 0 010-4.438 3.42 3.42 0 00.806-1.946 3.42 3.42 0 013.138-3.138z"/></svg>
                    Requisitos VUCEM: 300 DPI · PDF 1.4 · Escala de grises · &lt; 3 MB
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- ── SELECTOR DE MODO ──────────────────────────────────────────────── --}}
        <div class="mb-8">
            <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">¿Para qué tipo de trámite?</p>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4" id="modo-selector">

                <button type="button" id="modo-vucem" onclick="setModo('vucem')"
                        class="modo-btn text-left p-5 rounded-2xl border-2 transition-all
                               border-sky-500 bg-sky-50 ring-2 ring-sky-300">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 flex-shrink-0 w-5 h-5 rounded-full border-2 flex items-center justify-center border-sky-500 bg-sky-500">
                            <svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"/></svg>
                        </span>
                        <div>
                            <p class="font-bold text-sky-800 text-sm">VUCEM / MVE</p>
                            <p class="text-xs text-sky-600 mt-0.5">Para subir documentos al portal VUCEM o trámites de MVE</p>
                            <div class="flex flex-wrap gap-1.5 mt-2">
                                <span class="text-xs bg-sky-100 text-sky-700 font-semibold px-2 py-0.5 rounded-full">Máx. 3 MB</span>
                                <span class="text-xs bg-sky-100 text-sky-700 font-semibold px-2 py-0.5 rounded-full">300 DPI</span>
                                <span class="text-xs bg-sky-100 text-sky-700 font-semibold px-2 py-0.5 rounded-full">Escala de grises</span>
                                <span class="text-xs bg-sky-100 text-sky-700 font-semibold px-2 py-0.5 rounded-full">PDF 1.4</span>
                            </div>
                        </div>
                    </div>
                </button>

                <button type="button" id="modo-general" onclick="setModo('general')"
                        class="modo-btn text-left p-5 rounded-2xl border-2 transition-all
                               border-slate-200 bg-white hover:border-violet-300">
                    <div class="flex items-start gap-3">
                        <span class="mt-0.5 flex-shrink-0 w-5 h-5 rounded-full border-2 flex items-center justify-center border-slate-300">
                        </span>
                        <div>
                            <p class="font-bold text-slate-700 text-sm">Otros trámites</p>
                            <p class="text-xs text-slate-500 mt-0.5">Documentación de activos, expedientes internos u otros portales</p>
                            <div class="flex flex-wrap gap-1.5 mt-2">
                                <span class="text-xs bg-violet-100 text-violet-700 font-semibold px-2 py-0.5 rounded-full">Máx. 10 MB</span>
                                <span class="text-xs bg-violet-100 text-violet-700 font-semibold px-2 py-0.5 rounded-full">300 DPI</span>
                                <span class="text-xs bg-violet-100 text-violet-700 font-semibold px-2 py-0.5 rounded-full">Escala de grises</span>
                                <span class="text-xs bg-violet-100 text-violet-700 font-semibold px-2 py-0.5 rounded-full">PDF 1.4</span>
                            </div>
                        </div>
                    </div>
                </button>

            </div>
        </div>

        {{-- Tabs --}}
        <div class="flex flex-wrap gap-2 mb-8">
            @php
            $tabs = [
                ['id' => 'convertir',  'label' => 'Convertir PDF',    'icon' => 'M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12', 'color' => 'sky'],
                ['id' => 'validar',    'label' => 'Validar PDF',      'icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',               'color' => 'emerald'],
                ['id' => 'comprimir',  'label' => 'Comprimir PDF',    'icon' => 'M19 14l-7 7m0 0l-7-7m7 7V3',                                    'color' => 'violet'],
                ['id' => 'combinar',   'label' => 'Combinar PDFs',    'icon' => 'M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z', 'color' => 'amber'],
                ['id' => 'extraer',    'label' => 'Extraer imágenes', 'icon' => 'M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z', 'color' => 'rose'],
            ];
            $colorMap = [
                'sky'     => ['active' => 'bg-sky-600 text-white shadow-sky-200',    'inactive' => 'bg-white text-slate-600 hover:text-sky-700 hover:border-sky-200 border-slate-200'],
                'emerald' => ['active' => 'bg-emerald-600 text-white shadow-emerald-200', 'inactive' => 'bg-white text-slate-600 hover:text-emerald-700 hover:border-emerald-200 border-slate-200'],
                'violet'  => ['active' => 'bg-violet-600 text-white shadow-violet-200',  'inactive' => 'bg-white text-slate-600 hover:text-violet-700 hover:border-violet-200 border-slate-200'],
                'amber'   => ['active' => 'bg-amber-600 text-white shadow-amber-200',    'inactive' => 'bg-white text-slate-600 hover:text-amber-700 hover:border-amber-200 border-slate-200'],
                'rose'    => ['active' => 'bg-rose-600 text-white shadow-rose-200',      'inactive' => 'bg-white text-slate-600 hover:text-rose-700 hover:border-rose-200 border-slate-200'],
            ];
            @endphp

            @foreach($tabs as $i => $tab)
            <button type="button"
                    onclick="switchTab('{{ $tab['id'] }}')"
                    id="tab-btn-{{ $tab['id'] }}"
                    class="tab-btn flex items-center gap-2 px-4 py-2.5 text-sm font-bold rounded-xl border transition-all shadow-sm {{ $i === 0 ? $colorMap[$tab['color']]['active'] . ' shadow-lg' : $colorMap[$tab['color']]['inactive'] }}"
                    data-active-class="{{ $colorMap[$tab['color']]['active'] }} shadow-lg"
                    data-inactive-class="{{ $colorMap[$tab['color']]['inactive'] }}">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $tab['icon'] }}"/>
                </svg>
                {{ $tab['label'] }}
            </button>
            @endforeach
        </div>

        {{-- ================================================================== --}}
        {{-- TAB 1: CONVERTIR PDF                                                --}}
        {{-- ================================================================== --}}
        <div id="panel-convertir" class="tab-panel">
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-8">
                <h2 class="text-xl font-bold text-slate-900 mb-1">Convertir PDF a formato VUCEM</h2>
                <p class="text-slate-500 text-sm mb-1">Rasteriza el documento a exactamente 300 DPI, escala de grises y PDF versión 1.4.</p>
                <p class="text-xs text-slate-400 mb-6">Límite de tamaño para el modo actual: <span id="modo-size-hint" class="font-semibold text-sky-600">3 MB</span></p>

                <form id="form-convertir" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    {{-- Drop zone --}}
                    <div id="dz-convertir" class="dropzone border-2 border-dashed border-slate-200 rounded-2xl p-10 text-center cursor-pointer hover:border-sky-400 hover:bg-sky-50 transition-all"
                         onclick="document.getElementById('fi-convertir').click()">
                        <svg class="mx-auto h-10 w-10 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        <p class="text-sm font-semibold text-slate-600">Arrastra tu PDF o <span class="text-sky-600">haz clic para seleccionar</span></p>
                        <p class="text-xs text-slate-400 mt-1">Solo archivos PDF · Máx. 50 MB</p>
                    </div>
                    <input type="file" id="fi-convertir" name="file" accept=".pdf,application/pdf" class="hidden">

                    <div id="file-info-convertir" class="hidden flex items-center gap-3 bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3">
                        <svg class="w-8 h-8 text-sky-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <div class="flex-1 min-w-0">
                            <p id="fn-convertir" class="text-sm font-semibold text-slate-800 truncate"></p>
                            <p id="fs-convertir" class="text-xs text-slate-500"></p>
                        </div>
                        <button type="button" onclick="clearFile('convertir')" class="text-slate-400 hover:text-red-500 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    {{-- Opciones de división --}}
                    <div class="border border-slate-200 rounded-2xl p-5 space-y-4">
                        <label class="flex items-center gap-3 cursor-pointer">
                            <input type="checkbox" id="splitEnabled" name="splitEnabled" class="w-4 h-4 accent-sky-600">
                            <div>
                                <p class="text-sm font-bold text-slate-700">Dividir PDF en partes</p>
                                <p class="text-xs text-slate-500">Divide el documento en múltiples archivos más pequeños</p>
                            </div>
                        </label>
                        <div id="splitControls" class="hidden pl-7">
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Número de partes</label>
                            <select id="numberOfParts" name="numberOfParts"
                                    class="w-40 border border-slate-200 rounded-xl px-3 py-2 text-sm text-slate-700 focus:ring-2 focus:ring-sky-500 outline-none transition">
                                @for($i = 2; $i <= 8; $i++)
                                    <option value="{{ $i }}">{{ $i }} partes</option>
                                @endfor
                            </select>
                        </div>
                    </div>

                    {{-- Orientación --}}
                    <div class="border border-slate-200 rounded-2xl p-5">
                        <p class="text-sm font-bold text-slate-700 mb-3">Orientación de páginas</p>
                        <div class="grid grid-cols-3 gap-3">
                            @foreach([['auto','Automática','Detecta por página'],['portrait','Vertical','Todas en vertical'],['landscape','Horizontal','Todas en horizontal']] as [$val,$label,$desc])
                            <label class="cursor-pointer">
                                <input type="radio" name="orientation" value="{{ $val }}" {{ $val==='auto'?'checked':'' }} class="sr-only peer">
                                <div class="peer-checked:border-sky-500 peer-checked:bg-sky-50 peer-checked:text-sky-700 border border-slate-200 rounded-xl p-3 text-center text-xs transition hover:border-sky-300">
                                    <p class="font-bold mb-1">{{ $label }}</p>
                                    <p class="text-slate-500 peer-checked:text-sky-600">{{ $desc }}</p>
                                </div>
                            </label>
                            @endforeach
                        </div>
                    </div>

                    <button type="submit" id="btn-convertir" disabled
                            class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-sky-600 text-white font-bold text-sm rounded-xl hover:bg-sky-700 disabled:opacity-40 disabled:cursor-not-allowed transition shadow-lg shadow-sky-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/></svg>
                        Convertir a formato VUCEM
                    </button>
                </form>

                <div id="result-convertir" class="hidden mt-6"></div>
            </div>
        </div>

        {{-- ================================================================== --}}
        {{-- TAB 2: VALIDAR PDF                                                  --}}
        {{-- ================================================================== --}}
        <div id="panel-validar" class="tab-panel hidden">
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-8">
                <h2 class="text-xl font-bold text-slate-900 mb-1">Validar documento PDF</h2>
                <p class="text-slate-500 text-sm mb-6">Verifica que tu PDF cumpla con todos los requisitos de VUCEM antes de cargarlo al portal.</p>

                <form id="form-validar" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    <div id="dz-validar" class="dropzone border-2 border-dashed border-slate-200 rounded-2xl p-10 text-center cursor-pointer hover:border-emerald-400 hover:bg-emerald-50 transition-all"
                         onclick="document.getElementById('fi-validar').click()">
                        <svg class="mx-auto h-10 w-10 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <p class="text-sm font-semibold text-slate-600">Arrastra tu PDF o <span class="text-emerald-600">haz clic para seleccionar</span></p>
                        <p class="text-xs text-slate-400 mt-1">Solo archivos PDF · Máx. 50 MB</p>
                    </div>
                    <input type="file" id="fi-validar" name="pdf" accept=".pdf,application/pdf" class="hidden">

                    <div id="file-info-validar" class="hidden flex items-center gap-3 bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3">
                        <svg class="w-8 h-8 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <div class="flex-1 min-w-0">
                            <p id="fn-validar" class="text-sm font-semibold text-slate-800 truncate"></p>
                            <p id="fs-validar" class="text-xs text-slate-500"></p>
                        </div>
                        <button type="button" onclick="clearFile('validar')" class="text-slate-400 hover:text-red-500 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <button type="submit" id="btn-validar" disabled
                            class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-emerald-600 text-white font-bold text-sm rounded-xl hover:bg-emerald-700 disabled:opacity-40 disabled:cursor-not-allowed transition shadow-lg shadow-emerald-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        Validar documento
                    </button>
                </form>

                <div id="result-validar" class="hidden mt-6"></div>
            </div>
        </div>

        {{-- ================================================================== --}}
        {{-- TAB 3: COMPRIMIR PDF                                                --}}
        {{-- ================================================================== --}}
        <div id="panel-comprimir" class="tab-panel hidden">
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-8">
                <h2 class="text-xl font-bold text-slate-900 mb-1">Comprimir PDF</h2>
                <p class="text-slate-500 text-sm mb-6">Reduce el tamaño de tus PDFs sin perder los 300 DPI. Úsalo cuando el archivo supere los 3 MB.</p>

                <form id="form-comprimir" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    <div id="dz-comprimir" class="dropzone border-2 border-dashed border-slate-200 rounded-2xl p-10 text-center cursor-pointer hover:border-violet-400 hover:bg-violet-50 transition-all"
                         onclick="document.getElementById('fi-comprimir').click()">
                        <svg class="mx-auto h-10 w-10 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
                        </svg>
                        <p class="text-sm font-semibold text-slate-600">Arrastra tu PDF o <span class="text-violet-600">haz clic para seleccionar</span></p>
                        <p class="text-xs text-slate-400 mt-1">Solo archivos PDF · Máx. 100 MB</p>
                    </div>
                    <input type="file" id="fi-comprimir" name="file" accept=".pdf,application/pdf" class="hidden">

                    <div id="file-info-comprimir" class="hidden flex items-center gap-3 bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3">
                        <svg class="w-8 h-8 text-violet-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <div class="flex-1 min-w-0">
                            <p id="fn-comprimir" class="text-sm font-semibold text-slate-800 truncate"></p>
                            <p id="fs-comprimir" class="text-xs text-slate-500"></p>
                        </div>
                        <button type="button" onclick="clearFile('comprimir')" class="text-slate-400 hover:text-red-500 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <div class="border border-slate-200 rounded-2xl p-5">
                        <p class="text-sm font-bold text-slate-700 mb-3">Nivel de compresión</p>
                        <select id="compressionLevel" name="compressionLevel"
                                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-violet-500 outline-none transition">
                            <option value="screen">Pantalla (72 DPI) — Máxima compresión</option>
                            <option value="ebook">Ebook (150 DPI) — Compresión alta</option>
                            <option value="printer" selected>Impresora (300 DPI) — Mantiene calidad VUCEM</option>
                            <option value="prepress">Preimpresión (300 DPI) — Calidad profesional</option>
                        </select>
                        <p class="text-xs text-slate-500 mt-2">
                            <span class="text-violet-600 font-semibold">Recomendado:</span> Usa <em>Impresora</em> o <em>Preimpresión</em> para mantener los 300 DPI requeridos por VUCEM.
                        </p>
                    </div>

                    <button type="submit" id="btn-comprimir" disabled
                            class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-violet-600 text-white font-bold text-sm rounded-xl hover:bg-violet-700 disabled:opacity-40 disabled:cursor-not-allowed transition shadow-lg shadow-violet-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/></svg>
                        Comprimir PDF
                    </button>
                </form>

                <div id="result-comprimir" class="hidden mt-6"></div>
            </div>
        </div>

        {{-- ================================================================== --}}
        {{-- TAB 4: COMBINAR PDFs                                                --}}
        {{-- ================================================================== --}}
        <div id="panel-combinar" class="tab-panel hidden">
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-8">
                <h2 class="text-xl font-bold text-slate-900 mb-1">Combinar múltiples PDFs</h2>
                <p class="text-slate-500 text-sm mb-6">Une varios PDFs en uno solo preservando los 300 DPI originales. Mínimo 2, máximo 50 archivos.</p>

                <form id="form-combinar" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    <div id="dz-combinar" class="dropzone border-2 border-dashed border-slate-200 rounded-2xl p-10 text-center cursor-pointer hover:border-amber-400 hover:bg-amber-50 transition-all"
                         onclick="document.getElementById('fi-combinar').click()">
                        <svg class="mx-auto h-10 w-10 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-sm font-semibold text-slate-600">Arrastra tus PDFs o <span class="text-amber-600">haz clic para seleccionar</span></p>
                        <p class="text-xs text-slate-400 mt-1">Selecciona 2 o más PDFs · Máx. 50 MB por archivo</p>
                    </div>
                    <input type="file" id="fi-combinar" name="files[]" accept=".pdf,application/pdf" multiple class="hidden">

                    <div id="file-list-combinar" class="space-y-2"></div>

                    <div class="border border-slate-200 rounded-2xl p-5">
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Nombre del archivo combinado</label>
                        <input type="text" id="outputName" name="outputName" placeholder="documento_combinado"
                               class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-amber-500 outline-none transition">
                        <p class="text-xs text-slate-400 mt-1.5">Se añadirá "_combinado.pdf" automáticamente al final del nombre.</p>
                    </div>

                    <button type="submit" id="btn-combinar" disabled
                            class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-amber-600 text-white font-bold text-sm rounded-xl hover:bg-amber-700 disabled:opacity-40 disabled:cursor-not-allowed transition shadow-lg shadow-amber-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/></svg>
                        Combinar PDFs
                    </button>
                </form>

                <div id="result-combinar" class="hidden mt-6"></div>
            </div>
        </div>

        {{-- ================================================================== --}}
        {{-- TAB 5: EXTRAER IMÁGENES                                             --}}
        {{-- ================================================================== --}}
        <div id="panel-extraer" class="tab-panel hidden">
            <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-8">
                <h2 class="text-xl font-bold text-slate-900 mb-1">Extraer imágenes del PDF</h2>
                <p class="text-slate-500 text-sm mb-6">Extrae todas las páginas del PDF como imágenes JPEG a 300 DPI. Se descarga un archivo ZIP con todas las imágenes.</p>

                <form id="form-extraer" enctype="multipart/form-data" class="space-y-6">
                    @csrf
                    <div id="dz-extraer" class="dropzone border-2 border-dashed border-slate-200 rounded-2xl p-10 text-center cursor-pointer hover:border-rose-400 hover:bg-rose-50 transition-all"
                         onclick="document.getElementById('fi-extraer').click()">
                        <svg class="mx-auto h-10 w-10 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-sm font-semibold text-slate-600">Arrastra tu PDF o <span class="text-rose-600">haz clic para seleccionar</span></p>
                        <p class="text-xs text-slate-400 mt-1">Solo archivos PDF · Máx. 100 MB</p>
                    </div>
                    <input type="file" id="fi-extraer" name="pdf" accept=".pdf,application/pdf" class="hidden">

                    <div id="file-info-extraer" class="hidden flex items-center gap-3 bg-slate-50 border border-slate-200 rounded-2xl px-4 py-3">
                        <svg class="w-8 h-8 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <div class="flex-1 min-w-0">
                            <p id="fn-extraer" class="text-sm font-semibold text-slate-800 truncate"></p>
                            <p id="fs-extraer" class="text-xs text-slate-500"></p>
                        </div>
                        <button type="button" onclick="clearFile('extraer')" class="text-slate-400 hover:text-red-500 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>

                    <button type="submit" id="btn-extraer" disabled
                            class="w-full flex items-center justify-center gap-2 px-6 py-3 bg-rose-600 text-white font-bold text-sm rounded-xl hover:bg-rose-700 disabled:opacity-40 disabled:cursor-not-allowed transition shadow-lg shadow-rose-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                        Extraer imágenes a ZIP
                    </button>
                </form>

                <div id="result-extraer" class="hidden mt-6"></div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
/* =============================================================================
   Digitalización de documentos — Área Legal / ERP
   ============================================================================= */

const CSRF = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

// ── Modo (vucem = 3 MB | general = 10 MB) ────────────────────────────────────

let currentModo = 'vucem';

function getModo() { return currentModo; }

function setModo(modo) {
    currentModo = modo;

    const btnVucem   = document.getElementById('modo-vucem');
    const btnGeneral = document.getElementById('modo-general');
    const dotVucem   = btnVucem?.querySelector('span > svg')?.closest('span');
    const dotGeneral = btnGeneral?.querySelector('span')?.querySelector(':scope > svg')?.closest('span') ?? btnGeneral?.querySelectorAll('span')[0];

    if (modo === 'vucem') {
        btnVucem?.classList.add('border-sky-500','bg-sky-50','ring-2','ring-sky-300');
        btnVucem?.classList.remove('border-slate-200','bg-white','hover:border-sky-300','border-violet-400','bg-violet-50','ring-violet-300');
        btnGeneral?.classList.remove('border-violet-400','bg-violet-50','ring-2','ring-violet-300');
        btnGeneral?.classList.add('border-slate-200','bg-white','hover:border-violet-300');
        // swap radio dots
        btnVucem?.querySelectorAll('span')[0].innerHTML = '<svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"/></svg>';
        btnVucem?.querySelectorAll('span')[0].classList.add('border-sky-500','bg-sky-500');
        btnVucem?.querySelectorAll('span')[0].classList.remove('border-slate-300');
        btnGeneral?.querySelectorAll('span')[0].innerHTML = '';
        btnGeneral?.querySelectorAll('span')[0].classList.remove('border-violet-500','bg-violet-500');
        btnGeneral?.querySelectorAll('span')[0].classList.add('border-slate-300');
    } else {
        btnGeneral?.classList.add('border-violet-400','bg-violet-50','ring-2','ring-violet-300');
        btnGeneral?.classList.remove('border-slate-200','bg-white','hover:border-violet-300');
        btnVucem?.classList.remove('border-sky-500','bg-sky-50','ring-2','ring-sky-300');
        btnVucem?.classList.add('border-slate-200','bg-white','hover:border-sky-300');
        // swap radio dots
        btnGeneral?.querySelectorAll('span')[0].innerHTML = '<svg class="w-3 h-3 text-white" fill="currentColor" viewBox="0 0 8 8"><circle cx="4" cy="4" r="3"/></svg>';
        btnGeneral?.querySelectorAll('span')[0].classList.add('border-violet-500','bg-violet-500');
        btnGeneral?.querySelectorAll('span')[0].classList.remove('border-slate-300');
        btnVucem?.querySelectorAll('span')[0].innerHTML = '';
        btnVucem?.querySelectorAll('span')[0].classList.remove('border-sky-500','bg-sky-500');
        btnVucem?.querySelectorAll('span')[0].classList.add('border-slate-300');
    }

    // Update size hint labels visible on the convert tab
    const sizeHint = document.getElementById('modo-size-hint');
    if (sizeHint) sizeHint.textContent = modo === 'general' ? '10 MB' : '3 MB';
}

// ── Helpers ──────────────────────────────────────────────────────────────────

function fmtSize(bytes) {
    if (!bytes) return '0 B';
    const k = 1024, s = ['B','KB','MB','GB'];
    const i = Math.floor(Math.log(bytes) / Math.log(k));
    return (bytes / Math.pow(k, i)).toFixed(2) + ' ' + s[i];
}

function b64download(b64, filename, mime) {
    const url = 'data:' + mime + ';base64,' + b64;
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
}

function setLoading(btnId, loading, originalHtml) {
    const btn = document.getElementById(btnId);
    if (!btn) return;
    if (loading) {
        btn.disabled = true;
        btn.innerHTML = '<svg class="w-5 h-5 animate-spin" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"/></svg> Procesando…';
        btn.dataset.orig = originalHtml ?? btn.innerHTML;
    } else {
        btn.disabled = false;
        btn.innerHTML = btn.dataset.orig ?? originalHtml;
    }
}

function showResult(panelId, html) {
    const el = document.getElementById('result-' + panelId);
    el.innerHTML = html;
    el.classList.remove('hidden');
    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

function clearResult(panelId) {
    const el = document.getElementById('result-' + panelId);
    if (el) { el.innerHTML = ''; el.classList.add('hidden'); }
}

function resultSuccess(content) {
    return `<div class="bg-emerald-50 border border-emerald-200 rounded-2xl p-6">${content}</div>`;
}

function resultError(msg) {
    return `<div class="bg-red-50 border border-red-200 rounded-2xl p-6 flex items-start gap-3">
        <svg class="w-5 h-5 text-red-500 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="text-sm font-semibold text-red-700">${msg}</p>
    </div>`;
}

function downloadBtn(b64, filename, mime, label, color) {
    return `<button type="button" onclick="b64download('${b64}','${filename}','${mime}')"
        class="mt-4 w-full flex items-center justify-center gap-2 px-5 py-3 bg-${color}-600 text-white font-bold text-sm rounded-xl hover:bg-${color}-700 transition shadow-lg shadow-${color}-200">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
        ${label}
    </button>`;
}

// ── Tab navigation ────────────────────────────────────────────────────────────

const PANELS = ['convertir', 'validar', 'comprimir', 'combinar', 'extraer'];

function switchTab(id) {
    PANELS.forEach(p => {
        const panel = document.getElementById('panel-' + p);
        const btn   = document.getElementById('tab-btn-' + p);
        if (!panel || !btn) return;
        if (p === id) {
            panel.classList.remove('hidden');
            btn.className = btn.className.replace(btn.dataset.inactiveClass, '').trim() + ' ' + btn.dataset.activeClass;
        } else {
            panel.classList.add('hidden');
            btn.className = btn.className.replace(btn.dataset.activeClass, '').trim() + ' ' + btn.dataset.inactiveClass;
        }
    });
}

// Store original classes for tab buttons
document.querySelectorAll('.tab-btn').forEach(btn => {
    btn.dataset.activeClass   = btn.dataset.activeClass   ?? '';
    btn.dataset.inactiveClass = btn.dataset.inactiveClass ?? '';
});

// ── Drop zone helper ──────────────────────────────────────────────────────────

function setupDropzone(dzId, fiId, infoDivId, fnId, fsId, btnId, multiple) {
    const dz  = document.getElementById(dzId);
    const fi  = document.getElementById(fiId);
    const btn = document.getElementById(btnId);

    if (!dz || !fi) return;

    ['dragenter','dragover','dragleave','drop'].forEach(e => dz.addEventListener(e, ev => ev.preventDefault()));
    dz.addEventListener('dragover',  () => dz.classList.add('ring-2', 'ring-offset-2'));
    dz.addEventListener('dragleave', () => dz.classList.remove('ring-2', 'ring-offset-2'));
    dz.addEventListener('drop', e => {
        dz.classList.remove('ring-2', 'ring-offset-2');
        handleFileInput(e.dataTransfer.files, fiId, infoDivId, fnId, fsId, btnId, multiple);
    });

    fi.addEventListener('change', () => {
        handleFileInput(fi.files, fiId, infoDivId, fnId, fsId, btnId, multiple);
    });
}

function handleFileInput(files, fiId, infoDivId, fnId, fsId, btnId, multiple) {
    const info = document.getElementById(infoDivId);
    const btn  = document.getElementById(btnId);
    if (!files || files.length === 0) return;

    if (!multiple) {
        const file = files[0];
        document.getElementById(fnId).textContent = file.name;
        document.getElementById(fsId).textContent = fmtSize(file.size);
        info?.classList.remove('hidden');
        if (btn) btn.disabled = false;
    }
}

window.clearFile = function(tool) {
    const fi  = document.getElementById('fi-' + tool);
    const info = document.getElementById('file-info-' + tool);
    const btn  = document.getElementById('btn-' + tool);
    if (fi)   fi.value = '';
    info?.classList.add('hidden');
    if (btn) btn.disabled = true;
    clearResult(tool);
};

// ── Setup all dropzones ───────────────────────────────────────────────────────

setupDropzone('dz-convertir', 'fi-convertir', 'file-info-convertir', 'fn-convertir', 'fs-convertir', 'btn-convertir', false);
setupDropzone('dz-validar',   'fi-validar',   'file-info-validar',   'fn-validar',   'fs-validar',   'btn-validar',   false);
setupDropzone('dz-comprimir', 'fi-comprimir', 'file-info-comprimir', 'fn-comprimir', 'fs-comprimir', 'btn-comprimir', false);
setupDropzone('dz-extraer',   'fi-extraer',   'file-info-extraer',   'fn-extraer',   'fs-extraer',   'btn-extraer',   false);

// ── Combinar: list de archivos ────────────────────────────────────────────────

let combinarFiles = [];

document.getElementById('fi-combinar')?.addEventListener('change', function() {
    for (const f of this.files) {
        if (!combinarFiles.find(cf => cf.name === f.name && cf.size === f.size)) {
            combinarFiles.push(f);
        }
    }
    renderCombinarList();
});

document.getElementById('dz-combinar')?.addEventListener('drop', function(e) {
    e.preventDefault();
    for (const f of e.dataTransfer.files) {
        if (f.type === 'application/pdf' || f.name.endsWith('.pdf')) {
            if (!combinarFiles.find(cf => cf.name === f.name && cf.size === f.size)) {
                combinarFiles.push(f);
            }
        }
    }
    renderCombinarList();
});

function renderCombinarList() {
    const listEl = document.getElementById('file-list-combinar');
    const btn    = document.getElementById('btn-combinar');
    if (!listEl) return;

    listEl.innerHTML = combinarFiles.map((f, i) => `
        <div class="flex items-center gap-3 bg-slate-50 border border-slate-200 rounded-xl px-4 py-2.5">
            <svg class="w-5 h-5 text-amber-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            <span class="flex-1 text-sm text-slate-700 truncate">${f.name}</span>
            <span class="text-xs text-slate-500 shrink-0">${fmtSize(f.size)}</span>
            <button type="button" onclick="removeCombinarFile(${i})" class="text-slate-400 hover:text-red-500 transition ml-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
    `).join('');

    if (btn) btn.disabled = combinarFiles.length < 2;
}

window.removeCombinarFile = function(i) {
    combinarFiles.splice(i, 1);
    renderCombinarList();
    clearResult('combinar');
};

// ── Opciones dividir ──────────────────────────────────────────────────────────

document.getElementById('splitEnabled')?.addEventListener('change', function() {
    const ctrl = document.getElementById('splitControls');
    if (ctrl) ctrl.classList.toggle('hidden', !this.checked);
});

// =============================================================================
// FORM HANDLERS
// =============================================================================

// ── 1. Convertir ─────────────────────────────────────────────────────────────

document.getElementById('form-convertir')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const fi = document.getElementById('fi-convertir');
    if (!fi?.files[0]) return;

    const origHtml = document.getElementById('btn-convertir').innerHTML;
    setLoading('btn-convertir', true);
    clearResult('convertir');

    const fd = new FormData();
    fd.append('_token', CSRF);
    fd.append('file', fi.files[0]);
    fd.append('modo', getModo());
    fd.append('splitEnabled', document.getElementById('splitEnabled')?.checked ? '1' : '0');
    fd.append('numberOfParts', document.getElementById('numberOfParts')?.value ?? '2');
    fd.append('orientation', document.querySelector('input[name="orientation"]:checked')?.value ?? 'auto');

    try {
        const res  = await fetch('{{ route("legal.digitalizacion.convert") }}', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
        const data = await res.json();

        if (!data.success) { showResult('convertir', resultError(data.error ?? 'Error al convertir.')); return; }

        if (data.split && data.files) {
            const modoLabel = data.modo === 'general' ? 'Otros trámites (máx. '+data.max_size_mb+' MB)' : 'VUCEM / MVE (máx. '+data.max_size_mb+' MB)';
            const filesBtns = data.files.map(f => {
                const overLimit = f.exceeds_limit ? `<span class="text-xs text-red-600 block mt-0.5">⚠️ Supera el límite de ${data.max_size_mb} MB</span>` : '';
                return downloadBtn(f.content, f.name, 'application/pdf', `Descargar parte ${f.part} (${f.size_mb} MB)`, 'sky') + overLimit;
            }).join('');
            showResult('convertir', resultSuccess(`
                <div class="flex items-center gap-3 mb-4">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div><p class="font-bold text-emerald-800">Conversión completada — ${data.total_parts} archivos</p>
                    <p class="text-xs text-emerald-600">Modo: ${modoLabel} • Tamaño convertido: ${data.converted_size_mb} MB ${data.was_reduced ? '(−'+Math.abs(data.size_change_percent)+'%)' : ''}</p></div>
                </div>
                ${filesBtns}
            `));
        } else {
            const modoLabel = data.modo === 'general' ? 'Otros trámites (máx. '+data.max_size_mb+' MB)' : 'VUCEM / MVE (máx. '+data.max_size_mb+' MB)';
            const exceedHtml = data.exceeds_limit
                ? `<div class="mt-2 p-2 bg-amber-50 border border-amber-200 rounded-xl text-xs text-amber-700"><b>⚠️ Aviso:</b> El archivo (${data.file.size_mb} MB) supera el límite de ${data.max_size_mb} MB. Considera utilizar la opción de dividir en partes.</div>`
                : '';
            showResult('convertir', resultSuccess(`
                <div class="flex items-center gap-3 mb-4">
                    <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div><p class="font-bold text-emerald-800">Conversión completada</p>
                    <p class="text-xs text-emerald-600">Modo: ${modoLabel} • ${data.file.size_mb} MB ${data.was_reduced ? '(antes: '+data.original_size_mb+' MB, −'+Math.abs(data.size_change_percent)+'%)' : ''}</p></div>
                </div>
                ${data.messages?.length ? `<ul class="text-xs text-emerald-700 mb-2 space-y-0.5">${data.messages.map(m=>`<li>${m}</li>`).join('')}</ul>` : ''}
                ${exceedHtml}
                ${downloadBtn(data.file.content, data.file.name, 'application/pdf', 'Descargar PDF convertido', 'sky')}
            `));
        }
    } catch (err) {
        showResult('convertir', resultError('Error de conexión: ' + err.message));
    } finally {
        setLoading('btn-convertir', false, origHtml);
    }
});

// ── 2. Validar ────────────────────────────────────────────────────────────────

document.getElementById('form-validar')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const fi = document.getElementById('fi-validar');
    if (!fi?.files[0]) return;

    const origHtml = document.getElementById('btn-validar').innerHTML;
    setLoading('btn-validar', true);
    clearResult('validar');

    const fd = new FormData();
    fd.append('_token', CSRF);
    fd.append('pdf', fi.files[0]);
    fd.append('modo', getModo());

    try {
        const res  = await fetch('{{ route("legal.digitalizacion.validate") }}', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
        const data = await res.json();

        if (!data.success) { showResult('validar', resultError(data.error ?? 'Error al validar.')); return; }

        const maxMb = data.max_size_mb ?? 3;
        const modoLabel = data.modo === 'general' ? `Otros trámites (máx. ${maxMb} MB)` : `VUCEM / MVE (máx. ${maxMb} MB)`;

        const checkLabels = {
            size:       { ok: `✓ Tamaño permitido (<${maxMb} MB)`, fail: `✗ Tamaño excede ${maxMb} MB`, icon: 'M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3' },
            version:    { ok: '✓ Versión PDF 1.4',          fail: '✗ Versión PDF incorrecta', icon: 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z' },
            grayscale:  { ok: '✓ Escala de grises',         fail: '✗ Contiene color', icon: 'M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4' },
            dpi:        { ok: '✓ 300 DPI',                  fail: '✗ DPI incorrecto', icon: 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z' },
            encryption: { ok: '✓ Sin contraseña',           fail: '✗ Archivo encriptado', icon: 'M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z' },
        };

        const allColor  = data.allOk ? 'emerald' : 'red';
        const allBg     = data.allOk ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200';
        const allText   = data.allOk ? 'text-emerald-800' : 'text-red-800';
        const allSubtxt = data.allOk ? 'text-emerald-600' : 'text-red-600';

        const checksHtml = Object.entries(data.checks).map(([key, check]) => {
            const cfg   = checkLabels[key] ?? { ok: '✓ OK', fail: '✗ Error', icon: '' };
            const color = check.ok ? 'emerald' : 'red';
            return `
            <div class="flex items-start gap-3 py-2.5 border-b border-slate-100 last:border-0">
                <span class="mt-0.5 flex-shrink-0 w-6 h-6 rounded-full flex items-center justify-center bg-${color}-100 text-${color}-600">
                    ${check.ok
                        ? '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>'
                        : '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>'
                    }
                </span>
                <div>
                    <p class="text-sm font-semibold text-slate-700">${check.label}</p>
                    <p class="text-xs text-slate-500 whitespace-pre-line">${check.value}</p>
                </div>
            </div>`;
        }).join('');

        const recommendation = !data.allOk
            ? `<div class="mt-4 p-4 bg-sky-50 border border-sky-200 rounded-xl text-sm text-sky-800">
                <strong>Recomendación:</strong> Usa la herramienta <em>Convertir PDF</em> para transformar el documento al formato correcto de VUCEM.
               </div>`
            : '';

        showResult('validar', `
            <div class="${allBg} border rounded-2xl p-5">
                <div class="flex items-center gap-3 mb-4">
                    <svg class="w-6 h-6 text-${allColor}-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        ${data.allOk
                            ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>'
                            : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>'}
                    </svg>
                    <div>
                        <p class="font-bold ${allText}">${data.allOk ? '¡Documento válido!' : 'Documento no cumple con los requisitos'}</p>
                        <p class="text-xs ${allSubtxt}">${modoLabel} &bull; ${data.fileName}</p>
                    </div>
                </div>
                <div class="divide-y divide-slate-100">${checksHtml}</div>
                ${recommendation}
            </div>
        `);
    } catch (err) {
        showResult('validar', resultError('Error de conexión: ' + err.message));
    } finally {
        setLoading('btn-validar', false, origHtml);
    }
});

// ── 3. Comprimir ─────────────────────────────────────────────────────────────

document.getElementById('form-comprimir')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const fi = document.getElementById('fi-comprimir');
    if (!fi?.files[0]) return;

    const origHtml = document.getElementById('btn-comprimir').innerHTML;
    setLoading('btn-comprimir', true);
    clearResult('comprimir');

    const fd = new FormData();
    fd.append('_token', CSRF);
    fd.append('file', fi.files[0]);
    fd.append('compressionLevel', document.getElementById('compressionLevel')?.value ?? 'printer');

    try {
        const res  = await fetch('{{ route("legal.digitalizacion.compress") }}', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
        const data = await res.json();

        if (!data.success) { showResult('comprimir', resultError(data.error ?? 'Error al comprimir.')); return; }

        const savedPct = data.reduction_percent > 0 ? `(−${data.reduction_percent}%)` : '(sin reducción)';
        showResult('comprimir', resultSuccess(`
            <div class="flex items-center gap-3 mb-4">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <p class="font-bold text-emerald-800">Compresión completada</p>
                    <p class="text-xs text-emerald-600">${data.input_size_mb} MB → ${data.output_size_mb} MB ${savedPct}</p>
                </div>
            </div>
            ${downloadBtn(data.file.content, data.file.name, 'application/pdf', 'Descargar PDF comprimido', 'violet')}
        `));
    } catch (err) {
        showResult('comprimir', resultError('Error de conexión: ' + err.message));
    } finally {
        setLoading('btn-comprimir', false, origHtml);
    }
});

// ── 4. Combinar ───────────────────────────────────────────────────────────────

document.getElementById('form-combinar')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    if (combinarFiles.length < 2) return;

    const origHtml = document.getElementById('btn-combinar').innerHTML;
    setLoading('btn-combinar', true);
    clearResult('combinar');

    const fd = new FormData();
    fd.append('_token', CSRF);
    combinarFiles.forEach(f => fd.append('files[]', f));
    fd.append('outputName', document.getElementById('outputName')?.value ?? 'documento_combinado');

    try {
        const res  = await fetch('{{ route("legal.digitalizacion.merge") }}', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
        const data = await res.json();

        if (!data.success) { showResult('combinar', resultError(data.error ?? 'Error al combinar.')); return; }

        showResult('combinar', resultSuccess(`
            <div class="flex items-center gap-3 mb-4">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <p class="font-bold text-emerald-800">PDFs combinados exitosamente</p>
                    <p class="text-xs text-emerald-600">${data.files_merged} archivos → ${data.output_size_mb} MB</p>
                </div>
            </div>
            ${downloadBtn(data.file.content, data.file.name, 'application/pdf', 'Descargar PDF combinado', 'amber')}
        `));
        combinarFiles = [];
        renderCombinarList();
    } catch (err) {
        showResult('combinar', resultError('Error de conexión: ' + err.message));
    } finally {
        setLoading('btn-combinar', false, origHtml);
    }
});

// ── 5. Extraer imágenes ───────────────────────────────────────────────────────

document.getElementById('form-extraer')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    const fi = document.getElementById('fi-extraer');
    if (!fi?.files[0]) return;

    const origHtml = document.getElementById('btn-extraer').innerHTML;
    setLoading('btn-extraer', true);
    clearResult('extraer');

    const fd = new FormData();
    fd.append('_token', CSRF);
    fd.append('pdf', fi.files[0]);

    try {
        const res  = await fetch('{{ route("legal.digitalizacion.extract") }}', { method: 'POST', body: fd, headers: { 'Accept': 'application/json' } });
        const data = await res.json();

        if (!data.success) { showResult('extraer', resultError(data.error ?? 'Error al extraer.')); return; }

        showResult('extraer', resultSuccess(`
            <div class="flex items-center gap-3 mb-4">
                <svg class="w-6 h-6 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <div>
                    <p class="font-bold text-emerald-800">Extracción completada</p>
                    <p class="text-xs text-emerald-600">${data.images_count} imagen(es) · ${data.file.size_mb} MB (ZIP)</p>
                </div>
            </div>
            ${downloadBtn(data.file.content, data.file.name, 'application/zip', 'Descargar imágenes (.zip)', 'rose')}
        `));
    } catch (err) {
        showResult('extraer', resultError('Error de conexión: ' + err.message));
    } finally {
        setLoading('btn-extraer', false, origHtml);
    }
});
</script>
@endpush

@endsection
