@extends('layouts.erp')

@section('title', 'BOM ' . $bom->clave)

@section('content')
@php
    $hasAnalysis = isset($analysis) && $analysis;
    $qualifies   = $hasAnalysis ? $analysis->qualifies : null;
    $fraccionFG  = $items->first()?->fraccion_arancelaria_fg ?? '';
    $fracDigits  = preg_replace('/\D/', '', $fraccionFG);
    $chapFrac    = strlen($fracDigits) >= 4 ? substr($fracDigits, 0, 4) : $fracDigits;
@endphp
<div x-data="bomShow()"
     data-bom-id="{{ $bom->id }}"
     data-update-url="{{ route('legal.ce.bom.items.update', $bom->id) }}"
     class="max-w-full px-4 sm:px-6 lg:px-8">

    {{-- ── Breadcrumb + título ───────────────────────────────────────── --}}
    <div class="mb-5">
        <div class="flex items-center gap-2 text-sm text-slate-400 mb-1">
            <a href="{{ route('legal.dashboard') }}" class="hover:text-indigo-600">Legal</a>
            <span>/</span>
            <a href="{{ route('legal.ce.bom.index') }}" class="hover:text-indigo-600">BOMs</a>
            <span>/</span>
            <span class="font-mono text-slate-700">{{ $bom->clave }}</span>
        </div>
        <div class="flex items-center gap-3 flex-wrap">
            <h1 class="text-xl font-bold text-slate-900">{{ $bom->nombre ?: 'BOM sin nombre' }}</h1>
            <span class="bom-clave-badge">{{ $bom->clave }}</span>
        </div>
        <p class="text-xs text-slate-500 mt-0.5">
            <span x-text="items.length"></span> filas
            @if($bom->archivo_original)
                &nbsp;&bull;&nbsp; {{ $bom->archivo_original }}
            @endif
            &nbsp;&bull;&nbsp; {{ $bom->created_at->format('d/m/Y H:i') }}
        </p>
    </div>

    {{-- ── Barra de acciones ─────────────────────────────────────────── --}}
    <div class="flex items-center justify-between flex-wrap gap-3 mb-4">

        <div class="flex items-center gap-4 flex-wrap">
            <span class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Leyenda:</span>
            <span class="inline-flex items-center gap-1.5 text-xs font-medium">
                <span class="w-4 h-4 rounded-sm inline-block" style="background:#7f1d1d"></span>Finished Goods
            </span>
            <span class="inline-flex items-center gap-1.5 text-xs font-medium">
                <span class="w-4 h-4 rounded-sm inline-block" style="background:#9ca3af"></span>Raw Material
            </span>
            <span class="inline-flex items-center gap-1.5 text-xs font-medium">
                <span class="w-4 h-4 rounded-sm inline-block" style="background:#BDD7EE"></span>Análisis
            </span>
        </div>

        <div class="flex items-center gap-2">

            {{-- Modo NORMAL --}}
            <template x-if="!editMode && !analysisEditMode">
                <div class="flex items-center gap-2">
                    <button @click="startEdit"
                            class="inline-flex items-center gap-2 px-3 py-2 text-sm font-semibold text-indigo-600 bg-indigo-50 border border-indigo-200 rounded-xl hover:bg-indigo-100 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Editar BOM
                    </button>

                    @if($hasAnalysis)
                    <button @click="startAnalysisEdit"
                            class="inline-flex items-center gap-2 px-3 py-2 text-sm font-semibold text-purple-600 bg-purple-50 border border-purple-200 rounded-xl hover:bg-purple-100 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        Editar análisis
                    </button>
                    @endif

                    <form action="{{ route('legal.ce.origen.store', $bom) }}" method="POST" class="flex items-center gap-2">
                        @csrf
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-emerald-600 hover:bg-emerald-700 rounded-xl transition shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                            </svg>
                            Análisis T-MEC
                        </button>
                        @if(isset($modo))
                            @if($modo === 'ia')
                                <span class="inline-flex items-center gap-1 text-xs font-semibold px-2.5 py-1 rounded-full bg-indigo-100 text-indigo-700">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                                    IA activa
                                </span>
                            @else
                                <span class="text-xs font-semibold px-2.5 py-1 rounded-full bg-slate-100 text-slate-500">Motor local</span>
                            @endif
                        @endif
                    </form>

                    @if($hasAnalysis)
                    <a href="{{ route('legal.ce.origen.export', $bom) }}"
                       class="inline-flex items-center gap-2 px-3 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                        </svg>
                        Exportar Excel
                    </a>
                    @endif

                    <form method="POST" action="{{ route('legal.ce.bom.destroy', $bom->id) }}"
                          onsubmit="return confirm('¿Eliminar este BOM?')">
                        @csrf @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-3 py-2 text-sm font-semibold text-red-600 bg-red-50 border border-red-200 rounded-xl hover:bg-red-100 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Eliminar
                        </button>
                    </form>
                </div>
            </template>

            {{-- Modo EDICIÓN BOM --}}
            <template x-if="editMode">
                <div class="flex items-center gap-2">
                    <button @click="saveEdit" :disabled="saving"
                            class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50 rounded-xl transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span x-text="saving ? 'Guardando...' : 'Guardar cambios'"></span>
                    </button>
                    <button @click="cancelEdit" :disabled="saving"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 disabled:opacity-50 transition">
                        Cancelar
                    </button>
                </div>
            </template>

            {{-- Modo EDICIÓN ANÁLISIS --}}
            <template x-if="analysisEditMode">
                <div class="flex items-center gap-2">
                    <button @click="saveAnalysisEdit" :disabled="analysisSaving"
                            class="inline-flex items-center gap-2 px-5 py-2 text-sm font-semibold text-white bg-purple-600 hover:bg-purple-700 disabled:opacity-50 rounded-xl transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span x-text="analysisSaving ? 'Guardando...' : 'Guardar análisis'"></span>
                    </button>
                    <button @click="cancelAnalysisEdit" :disabled="analysisSaving"
                            class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 disabled:opacity-50 transition">
                        Cancelar
                    </button>
                </div>
            </template>
        </div>
    </div>

    {{-- Banner edición BOM --}}
    <div x-show="editMode" x-cloak class="bom-edit-banner mb-3">
        <svg class="w-5 h-5 text-amber-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
        </svg>
        Modo edición — haz clic en cualquier celda para modificar su valor, luego presiona <strong>&nbsp;Guardar cambios</strong>.
        <span class="ml-auto text-xs font-normal bg-amber-100 text-amber-800 px-2 py-0.5 rounded-full">
            <span x-text="items.length"></span> filas
        </span>
    </div>
    {{-- Banner edición análisis --}}
    <div x-show="analysisEditMode" x-cloak class="bom-edit-banner mb-3" style="background:#faf5ff;border-color:#e9d5ff">
        <svg class="w-5 h-5 text-purple-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
        </svg>
        <span class="text-purple-800">Modo edición de análisis — modifica las columnas P, Q, R, S, V, luego presiona <strong>Guardar análisis</strong>.</span>
        <span class="ml-auto text-xs font-normal bg-purple-100 text-purple-800 px-2 py-0.5 rounded-full">
            <span x-text="items.length"></span> filas
        </span>
    </div>

    {{-- Toast éxito --}}
    <div x-show="saved" x-cloak x-transition
         class="mb-3 flex items-center gap-2 p-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl text-sm font-medium">
        <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        Cambios guardados correctamente.
    </div>

    {{-- Flash --}}
    @if(session('success'))
    <div class="mb-3 flex items-center gap-3 bg-emerald-50 border border-emerald-200 text-emerald-800 rounded-xl px-4 py-3 text-sm">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('success') }}
    </div>
    @endif
    @if(session('error'))
    <div class="mb-3 flex items-center gap-3 bg-red-50 border border-red-200 text-red-800 rounded-xl px-4 py-3 text-sm">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        {{ session('error') }}
    </div>
    @endif

    {{-- ── Panel análisis T-MEC ─────────────────────────────────────── --}}

    @if(!$hasAnalysis)
    <div class="mb-4 bg-amber-50 border border-amber-200 rounded-xl p-4 text-sm text-amber-800 flex items-center gap-3">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        Sin dictamen registrado. Haz clic en <strong class="mx-1">Análisis T-MEC</strong> para generar el análisis de origen.
    </div>
    @endif

    @if($hasAnalysis)
    @php
        $accentBg  = $qualifies ? 'bg-emerald-600' : 'bg-red-600';
        $accentBdr = $qualifies ? 'border-emerald-200' : 'border-red-200';
        $lightBg   = $qualifies ? 'bg-emerald-50' : 'bg-red-50';
    @endphp
    <div class="mb-5 rounded-2xl border {{ $accentBdr }} shadow-sm overflow-hidden">
        <div class="{{ $accentBg }} px-5 py-4 flex items-center justify-between gap-4 flex-wrap">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-full bg-white/20 flex items-center justify-center flex-shrink-0">
                    @if($qualifies)
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    @else
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M6 18L18 6M6 6l12 12"/></svg>
                    @endif
                </div>
                <div>
                    <p class="text-white font-bold text-base">
                        {{ $qualifies ? 'CALIFICA COMO ORIGINARIO T-MEC' : 'NO CALIFICA COMO ORIGINARIO T-MEC' }}
                    </p>
                    <p class="text-white/75 text-xs mt-0.5">
                        {{ $analysis->analyst?->name ?? 'sistema' }} &bull; {{ $analysis->updated_at?->format('d/m/Y H:i') }}
                    </p>
                </div>
            </div>
            <button onclick="chatToggle()"
                    class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-semibold bg-white/15 hover:bg-white/25 text-white rounded-lg border border-white/30 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                </svg>
                Consultar IA
            </button>
        </div>

        <div class="{{ $lightBg }} border-b {{ $accentBdr }} px-5 py-3 grid grid-cols-2 sm:grid-cols-5 gap-4">
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Fracción FG</p>
                <p class="font-bold text-slate-800 font-mono mt-0.5">{{ $calc['fg_fraction'] ?? '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">VCR calculado</p>
                <p class="font-bold mt-0.5 {{ $calc['rvc_percentage'] >= 75 ? 'text-emerald-700' : ($calc['rvc_percentage'] >= 60 ? 'text-amber-600' : 'text-red-600') }}">
                    {{ $calc['rvc_percentage'] }}%
                </p>
            </div>
            @if($analysis->rvc_threshold !== null)
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Umbral mínimo</p>
                <p class="font-bold text-slate-800 mt-0.5">{{ $analysis->rvc_threshold }}%</p>
            </div>
            @endif
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Criterio</p>
                <p class="font-bold text-slate-800 mt-0.5">{{ $analysis->origin_criterion ? 'Criterio '.$analysis->origin_criterion : '—' }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold text-slate-500 uppercase tracking-wider">Vigente hasta</p>
                <p class="font-bold text-slate-800 mt-0.5">{{ $analysis->valid_until?->format('d/m/Y') ?? '—' }}</p>
            </div>
        </div>

        <div class="bg-white px-5 py-4 grid grid-cols-1 sm:grid-cols-3 gap-6 text-sm">
            <div>
                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Precio Final (CN)</p>
                <p class="text-xl font-bold text-slate-800">${{ number_format($calc['fg_price_usd'], 4) }} <span class="text-xs font-normal text-slate-400">USD</span></p>
            </div>
            <div>
                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">Costo No Originario (VMNO)</p>
                <p class="text-xl font-bold text-red-600">${{ number_format($calc['non_orig_cost_usd'], 4) }} <span class="text-xs font-normal text-slate-400">USD</span></p>
            </div>
            <div>
                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wider">VCR = (CN − VMNO) / CN</p>
                <p class="text-xl font-bold {{ $calc['rvc_percentage'] >= 75 ? 'text-emerald-600' : ($calc['rvc_percentage'] >= 60 ? 'text-amber-600' : 'text-red-600') }}">
                    {{ $calc['rvc_percentage'] }}%
                </p>
            </div>
        </div>

        @php $justificacion = $analysis->copilot_response['justificacion'] ?? null; @endphp
        @if($justificacion)
        <div class="border-t border-slate-100 px-5 py-3 flex items-start gap-2.5 bg-indigo-50">
            <svg class="w-4 h-4 text-indigo-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
            </svg>
            <div>
                <span class="text-xs font-semibold text-indigo-600 uppercase tracking-wider block mb-0.5">Justificación IA</span>
                <p class="text-sm text-slate-700 leading-relaxed">{{ $justificacion }}</p>
            </div>
        </div>
        @endif
    </div>
    @endif

    {{-- ── Chat IA ───────────────────────────────────────────────────── --}}
    <div id="chat-widget" style="display:none"
         class="mb-5 bg-white border border-indigo-200 rounded-2xl shadow-md overflow-hidden">
        <div class="flex items-center justify-between px-5 py-3 bg-indigo-600 text-white">
            <div class="flex items-center gap-2 text-sm font-semibold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                </svg>
                Asistente IA T-MEC
                <span class="text-indigo-200 font-normal text-xs">Groq / Llama</span>
            </div>
            <button onclick="chatToggle()" class="text-indigo-200 hover:text-white text-xl leading-none">&times;</button>
        </div>
        <div id="chat-history" class="px-5 pt-4 pb-2 space-y-3 overflow-y-auto" style="max-height:300px">
            <div class="flex gap-3">
                <div class="w-7 h-7 rounded-full bg-indigo-100 flex-shrink-0 flex items-center justify-center">
                    <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                    </svg>
                </div>
                <div class="flex-1 bg-indigo-50 rounded-lg rounded-tl-none px-4 py-3 text-sm text-slate-700 leading-relaxed">
                    Soy tu asistente T-MEC con IA. Puedo explicar el análisis, revisar el criterio, o analizar con parámetros diferentes.
                    <span class="text-xs text-indigo-500 block mt-1">Ej: "¿por qué califica?", "analiza con VCR 65%", "explica el criterio B"</span>
                </div>
            </div>
        </div>
        <div class="border-t border-slate-100 px-5 py-3 flex gap-2">
            <input id="chat-input" type="text" placeholder="Escribe tu pregunta…"
                   onkeydown="if(event.key==='Enter'){chatSend();}"
                   class="flex-1 text-sm border border-slate-200 rounded-lg px-4 py-2.5 focus:outline-none focus:ring-2 focus:ring-indigo-300">
            <button onclick="chatSend()" id="chat-send-btn"
                    class="inline-flex items-center gap-2 px-4 py-2 text-sm font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                Enviar
            </button>
        </div>
    </div>

    {{-- ══════════════════════════════════════════════════════════
         TABLA BOM — 19 columnas con scroll horizontal
    ══════════════════════════════════════════════════════════ --}}
    <div class="bom-table-wrapper">
        <table class="bom-table" :class="{ 'edit-mode': editMode }">
            <thead>
                <tr>
                    <th colspan="5"  class="section-fg">Finished Goods</th>
                    <th colspan="9"  class="section-rm">Raw Material</th>
                    <th colspan="5"  class="section-analisis">Análisis</th>
                </tr>
                <tr>
                    <th class="col-header">Número de Parte</th>
                    <th class="col-header">Fracción Arancelaria</th>
                    <th class="col-header">Descripción</th>
                    <th class="col-header">Precio Final (USD)</th>
                    <th class="col-header">Nivel</th>
                    <th class="col-header">No. de parte de insumo</th>
                    <th class="col-header">Descripción</th>
                    <th class="col-header">Cantidad incorporada</th>
                    <th class="col-header">Precio Unitario</th>
                    <th class="col-header">Unidad de Medida</th>
                    <th class="col-header">Costo Total USD</th>
                    <th class="col-header">Costo Total Pesos</th>
                    <th class="col-header">Fracción Arancelaria</th>
                    <th class="col-header">País de Origen</th>
                    <th class="col-header">Presenta cambio de Fracción?</th>
                    <th class="col-header">Cumple demás requisitos?</th>
                    <th class="col-header">Califica como originario?</th>
                    <th class="col-header">Regla de origen</th>
                    <th class="col-header">Criterio de origen</th>
                </tr>
            </thead>
            <tbody>
                <template x-for="(row, idx) in items" :key="row.id">
                    <tr>
                        {{-- Finished Goods --}}
                        <td>
                            <span x-show="!editMode" x-text="row.numero_de_parte"></span>
                            <input x-show="editMode" x-cloak type="text" x-model="items[idx].numero_de_parte" class="bom-edit-input">
                        </td>
                        <td class="center">
                            <span x-show="!editMode" x-text="row.fraccion_arancelaria_fg"></span>
                            <input x-show="editMode" x-cloak type="text" x-model="items[idx].fraccion_arancelaria_fg" class="bom-edit-input">
                        </td>
                        <td>
                            <span x-show="!editMode" x-text="row.descripcion_fg"></span>
                            <input x-show="editMode" x-cloak type="text" x-model="items[idx].descripcion_fg" class="bom-edit-input" style="min-width:180px">
                        </td>
                        <td class="num">
                            <span x-show="!editMode" x-text="row.precio_final_usd"></span>
                            <input x-show="editMode" x-cloak type="number" step="0.0001" x-model="items[idx].precio_final_usd" class="bom-edit-input num">
                        </td>
                        <td class="center">
                            <span x-show="!editMode" x-text="row.nivel"></span>
                            <input x-show="editMode" x-cloak type="text" x-model="items[idx].nivel" class="bom-edit-input" style="min-width:60px">
                        </td>
                        {{-- Raw Material --}}
                        <td>
                            <span x-show="!editMode" x-text="row.no_parte_insumo"></span>
                            <input x-show="editMode" x-cloak type="text" x-model="items[idx].no_parte_insumo" class="bom-edit-input">
                        </td>
                        <td>
                            <span x-show="!editMode" x-text="row.descripcion_rm"></span>
                            <input x-show="editMode" x-cloak type="text" x-model="items[idx].descripcion_rm" class="bom-edit-input" style="min-width:180px">
                        </td>
                        <td class="num">
                            <span x-show="!editMode" x-text="row.cantidad_incorporada"></span>
                            <input x-show="editMode" x-cloak type="number" step="0.0001" x-model="items[idx].cantidad_incorporada" class="bom-edit-input num">
                        </td>
                        <td class="num">
                            <span x-show="!editMode" x-text="row.precio_unitario"></span>
                            <input x-show="editMode" x-cloak type="number" step="0.0001" x-model="items[idx].precio_unitario" class="bom-edit-input num">
                        </td>
                        <td class="center">
                            <span x-show="!editMode" x-text="row.unidad_de_medida"></span>
                            <input x-show="editMode" x-cloak type="text" x-model="items[idx].unidad_de_medida" class="bom-edit-input" style="min-width:70px">
                        </td>
                        <td class="num">
                            <span x-show="!editMode" x-text="row.costo_total_usd"></span>
                            <input x-show="editMode" x-cloak type="number" step="0.0001" x-model="items[idx].costo_total_usd" class="bom-edit-input num">
                        </td>
                        <td class="num">
                            <span x-show="!editMode" x-text="row.costo_total_pesos"></span>
                            <input x-show="editMode" x-cloak type="number" step="0.0001" x-model="items[idx].costo_total_pesos" class="bom-edit-input num">
                        </td>
                        <td class="center">
                            <span x-show="!editMode" x-text="row.fraccion_arancelaria_rm"></span>
                            <input x-show="editMode" x-cloak type="text" x-model="items[idx].fraccion_arancelaria_rm" class="bom-edit-input">
                        </td>
                        <td class="center">
                            <span x-show="!editMode" x-text="row.pais_de_origen"></span>
                            <input x-show="editMode" x-cloak type="text" x-model="items[idx].pais_de_origen" class="bom-edit-input" style="min-width:80px">
                        </td>
                        {{-- Análisis --}}
                        <td class="analisis center">
                            <div x-show="!analysisEditMode">
                                <template x-if="row.presenta_cambio_fraccion === 'Sí'"><span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-700">SI</span></template>
                                <template x-if="row.presenta_cambio_fraccion === 'No'"><span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">NO</span></template>
                                <template x-if="row.presenta_cambio_fraccion === 'N/A'"><span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold bg-gray-100 text-gray-500">N/A</span></template>
                                <template x-if="!row.presenta_cambio_fraccion"><span class="text-gray-300">—</span></template>
                            </div>
                            <div x-show="analysisEditMode">
                                <select x-model="items[idx].presenta_cambio_fraccion" class="text-xs border border-purple-300 rounded px-1 py-0.5 bg-white focus:outline-none">
                                    <option value="">—</option>
                                    <option value="Sí">SI</option>
                                    <option value="No">NO</option>
                                    <option value="N/A">N/A</option>
                                </select>
                            </div>
                        </td>
                        <td class="analisis center">
                            <div x-show="!analysisEditMode">
                                <template x-if="row.cumple_demas_requisitos === 'Sí'"><span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-700">SI</span></template>
                                <template x-if="row.cumple_demas_requisitos === 'No'"><span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">NO</span></template>
                                <template x-if="!row.cumple_demas_requisitos"><span class="text-gray-300">—</span></template>
                            </div>
                            <div x-show="analysisEditMode">
                                <select x-model="items[idx].cumple_demas_requisitos" class="text-xs border border-purple-300 rounded px-1 py-0.5 bg-white focus:outline-none">
                                    <option value="">—</option>
                                    <option value="Sí">SI</option>
                                    <option value="No">NO</option>
                                </select>
                            </div>
                        </td>
                        <td class="analisis center">
                            <div x-show="!analysisEditMode">
                                <template x-if="row.califica_originario === 'Sí'"><span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold bg-green-100 text-green-700">SI</span></template>
                                <template x-if="row.califica_originario === 'No'"><span class="inline-block px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700">NO</span></template>
                                <template x-if="!row.califica_originario"><span class="text-gray-300">—</span></template>
                            </div>
                            <div x-show="analysisEditMode">
                                <select x-model="items[idx].califica_originario" class="text-xs border border-purple-300 rounded px-1 py-0.5 bg-white focus:outline-none">
                                    <option value="">—</option>
                                    <option value="Sí">SI</option>
                                    <option value="No">NO</option>
                                </select>
                            </div>
                        </td>
                        <td class="analisis" style="white-space:normal;min-width:200px;max-width:280px;font-size:11px;line-height:1.4;">
                            <span x-show="!analysisEditMode" x-text="row.regla_de_origen || '—'" class="text-gray-700"></span>
                            <input x-show="analysisEditMode" type="text" x-model="items[idx].regla_de_origen" placeholder="Regla…"
                                   class="text-xs border border-purple-300 rounded px-1 py-0.5 bg-white focus:outline-none w-full">
                        </td>
                        <td class="analisis center">
                            <div x-show="!analysisEditMode">
                                <template x-if="row.criterio_de_origen">
                                    <span class="inline-block px-2 py-0.5 rounded text-xs font-bold bg-blue-100 text-blue-800" x-text="row.criterio_de_origen"></span>
                                </template>
                                <template x-if="!row.criterio_de_origen"><span class="text-gray-300">—</span></template>
                            </div>
                            <div x-show="analysisEditMode">
                                <select x-model="items[idx].criterio_de_origen" class="text-xs border border-purple-300 rounded px-1 py-0.5 bg-white focus:outline-none">
                                    <option value="">—</option>
                                    <option value="A">A</option>
                                    <option value="B">B</option>
                                    <option value="C">C</option>
                                    <option value="D">D</option>
                                </select>
                            </div>
                        </td>
                    </tr>
                </template>
                <tr x-show="items.length === 0" class="bom-empty-row">
                    <td colspan="19">Este BOM no tiene filas de datos.</td>
                </tr>
            </tbody>
        </table>
    </div>
    <p class="mt-2 text-xs text-slate-400 text-right">Total de filas: <span x-text="items.length"></span></p>

    @if($hasAnalysis)
    @php
        $partNum   = $items->first()?->numero_de_parte ?? 'el número de parte';
        $criterion = $analysis->origin_criterion ?? 'B';
        $vcr       = $analysis->rvc_percentage   ?? 0;
        $thr       = $analysis->rvc_threshold    ?? 0;
    @endphp

    {{-- Reglas de origen aplicables --}}
    <div class="mt-5 rounded-2xl border border-slate-200 shadow-sm overflow-hidden bg-white">
        <div class="px-5 py-2.5 bg-slate-50 border-b border-slate-200 flex items-center gap-2">
            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
            </svg>
            <span class="text-xs font-bold text-slate-600 uppercase tracking-wider">Reglas de origen aplicables — Fracción {{ $chapFrac }}</span>
        </div>
        <table class="w-full text-xs">
            <thead>
                <tr style="background:#1F4E79">
                    <th class="px-4 py-2.5 text-left text-white font-semibold w-32 whitespace-nowrap">Partida(s)</th>
                    <th class="px-4 py-2.5 text-left text-white font-semibold">Regla(s) aplicable(s)</th>
                </tr>
            </thead>
            <tbody>
                <tr class="align-top">
                    <td class="px-4 py-3 font-mono font-bold text-slate-800 border-r border-slate-200 whitespace-nowrap">{{ $fraccionFG }}</td>
                    <td class="px-4 py-3 text-slate-700 leading-6 whitespace-pre-wrap text-xs">{{ $analysis->applicable_rule }}</td>
                </tr>
            </tbody>
        </table>
    </div>

    {{-- Resultado del análisis --}}
    <div class="mt-4 rounded-2xl border border-slate-200 shadow-sm overflow-hidden bg-white">
        <div class="px-5 py-2.5 bg-slate-50 border-b border-slate-200 flex items-center gap-2">
            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-xs font-bold text-slate-600 uppercase tracking-wider">Resultado del análisis de calificación de origen</span>
        </div>
        <div class="px-5 py-4">
            <p class="text-sm leading-7 text-slate-800 rounded-xl px-5 py-4 border
                {{ $analysis->qualifies ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200' }}">
                @if($analysis->qualifies)
                    El número de parte <strong>{{ $partNum }}</strong> adquiere el carácter de
                    <strong class="text-emerald-700">originario del T-MEC</strong>, derivado de que cumple con la totalidad de los requisitos
                    de las reglas de origen específicas para la fracción arancelaria <strong>{{ $fraccionFG }}</strong>,
                    con un VCR calculado de <strong>{{ $vcr }}%</strong> que supera el umbral mínimo de
                    <strong>{{ $thr }}%</strong>, de conformidad con el Criterio <strong>{{ $criterion }}</strong>
                    del Artículo 4.2 del Tratado entre México, Estados Unidos y Canadá (T-MEC).
                @else
                    El número de parte <strong>{{ $partNum }}</strong>
                    <strong class="text-red-700">NO califica</strong> como originario del T-MEC.
                    VCR calculado: <strong>{{ $vcr }}%</strong> — umbral requerido: <strong>{{ $thr }}%</strong>.
                    Fracción arancelaria <strong>{{ $fraccionFG }}</strong>, Criterio <strong>{{ $criterion }}</strong>.
                @endif
            </p>
        </div>
    </div>
    @endif

</div>
@endsection

@push('styles')
@vite('resources/css/ComercioExterior/bom.css')
@endpush

@push('scripts')
{{-- Datos JSON en elemento separado para evitar conflictos de parseo IDE --}}
<script type="application/json" id="__bom_items_data__">@json($items->toArray())</script>
<script>
const CORREGIR_URL = "{{ route('legal.ce.origen.corregir', $bom) }}";
const CHAT_URL     = "{{ route('legal.ce.origen.chat', $bom) }}";

function bomShow() {
    return {
        editMode:         false,
        saving:           false,
        saved:            false,
        items:            JSON.parse(document.getElementById('__bom_items_data__').textContent),
        _snap:            null,
        analysisEditMode: false,
        analysisSaving:   false,
        _analysisSnap:    null,

        init() { window._bomShow = this; },

        get updateUrl() {
            return this.$el.closest('[data-update-url]').dataset.updateUrl;
        },

        startEdit() {
            this._snap    = JSON.stringify(this.items);
            this.editMode = true;
            this.saved    = false;
        },

        cancelEdit() {
            this.items    = JSON.parse(this._snap);
            this._snap    = null;
            this.editMode = false;
        },

        async saveEdit() {
            this.saving = true;
            const csrf  = document.querySelector('meta[name="csrf-token"]').content;
            try {
                const res  = await fetch(this.updateUrl, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: JSON.stringify({ items: this.items }),
                });
                const data = await res.json();
                if (data.success) {
                    this._snap    = null;
                    this.editMode = false;
                    this.saved    = true;
                    setTimeout(() => this.saved = false, 3500);
                } else {
                    alert('No se pudieron guardar los cambios.');
                }
            } catch { alert('Error de conexión al guardar.'); }
            finally  { this.saving = false; }
        },

        startAnalysisEdit() {
            this._analysisSnap = JSON.stringify(this.items.map(i => ({
                id: i.id,
                presenta_cambio_fraccion: i.presenta_cambio_fraccion,
                cumple_demas_requisitos:  i.cumple_demas_requisitos,
                califica_originario:      i.califica_originario,
                criterio_de_origen:       i.criterio_de_origen,
                regla_de_origen:          i.regla_de_origen,
            })));
            this.analysisEditMode = true;
        },

        cancelAnalysisEdit() {
            const snap = JSON.parse(this._analysisSnap);
            snap.forEach(s => {
                const item = this.items.find(i => i.id === s.id);
                if (item) Object.assign(item, s);
            });
            this._analysisSnap    = null;
            this.analysisEditMode = false;
        },

        async saveAnalysisEdit() {
            this.analysisSaving = true;
            const csrf = document.querySelector('meta[name="csrf-token"]').content;
            const payload = {
                items: this.items.map(i => ({
                    id:                       i.id,
                    presenta_cambio_fraccion: i.presenta_cambio_fraccion,
                    cumple_demas_requisitos:  i.cumple_demas_requisitos,
                    califica_originario:      i.califica_originario,
                    criterio_de_origen:       i.criterio_de_origen,
                    regla_de_origen:          i.regla_de_origen,
                }))
            };
            try {
                const res  = await fetch(CORREGIR_URL, {
                    method: 'PUT',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' },
                    body: JSON.stringify(payload),
                });
                const data = await res.json();
                if (data.success) {
                    this._analysisSnap    = null;
                    this.analysisEditMode = false;
                    this.saved = true;
                    setTimeout(() => this.saved = false, 3500);
                } else {
                    alert('No se pudieron guardar los cambios del análisis.');
                }
            } catch { alert('Error de conexión.'); }
            finally  { this.analysisSaving = false; }
        },
    };
}

// ── Chat IA ────────────────────────────────────────────────────
let chatHistory = [];

function chatToggle() {
    const w = document.getElementById('chat-widget');
    if (!w) return;
    const hidden = w.style.display === 'none';
    w.style.display = hidden ? 'block' : 'none';
    if (hidden) setTimeout(() => document.getElementById('chat-input')?.focus(), 80);
}

function chatFmt(t) {
    return t.replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;')
            .replace(/\*\*(.+?)\*\*/g,'<strong>$1</strong>').replace(/\n/g,'<br>');
}

function chatAppend(html) {
    const el = document.getElementById('chat-history');
    if (!el) return;
    el.insertAdjacentHTML('beforeend', html);
    el.scrollTop = el.scrollHeight;
}

async function chatSend() {
    const input = document.getElementById('chat-input');
    const btn   = document.getElementById('chat-send-btn');
    const text  = input?.value.trim();
    if (!text) return;
    input.value = ''; input.disabled = true; btn.disabled = true;
    btn.textContent = 'Enviando…';

    chatAppend(`<div class="flex gap-3 justify-end">
        <div class="max-w-xs bg-slate-800 text-white rounded-lg rounded-tr-none px-4 py-2.5 text-sm">${chatFmt(text)}</div>
        <div class="w-7 h-7 rounded-full bg-slate-700 flex-shrink-0 flex items-center justify-center text-white text-xs font-bold">T</div>
    </div>`);

    const tid = 'typing-' + Date.now();
    chatAppend(`<div id="${tid}" class="flex gap-3">
        <div class="w-7 h-7 rounded-full bg-indigo-100 flex-shrink-0 flex items-center justify-center">
            <svg class="w-3.5 h-3.5 text-indigo-600 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
            </svg>
        </div>
        <div class="bg-indigo-50 rounded-lg rounded-tl-none px-4 py-2.5 text-sm text-indigo-400 italic">Analizando con IA…</div>
    </div>`);

    try {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const res  = await fetch(CHAT_URL, {
            method:'POST',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
            body: JSON.stringify({ message: text, history: chatHistory.slice(-6) }),
        });
        const data = await res.json();
        document.getElementById(tid)?.remove();

        const aiText  = res.ok ? (data.assistant ?? 'Sin respuesta.') : (data.error ?? 'Error.');
        const isError = !res.ok;
        const an      = data.analysis   ?? null;
        const corr    = data.corrections ?? null;

        let mini = '';
        if (an) {
            const cc  = an.cc_complies ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
            const cal = an.qualifies   ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700';
            mini = `<div class="mt-3 pt-3 border-t border-indigo-200 grid grid-cols-3 gap-2 text-center text-xs">
                <div><div class="text-gray-400 mb-1">CC</div><span class="px-2 py-0.5 rounded-full font-bold ${cc}">${an.cc_complies?'SÍ':'NO'}</span></div>
                <div><div class="text-gray-400 mb-1">Califica</div><span class="px-2 py-0.5 rounded-full font-bold ${cal}">${an.qualifies?'SÍ':'NO'}</span></div>
                <div><div class="text-gray-400 mb-1">Criterio</div><span class="px-2 py-0.5 rounded-full font-bold bg-blue-100 text-blue-800">${an.origin_criterion??'—'}</span></div>
            </div>`;
        }

        let applyBtn = '';
        if (corr) {
            applyBtn = `<div class="mt-3 pt-3 border-t border-indigo-200">
                <button onclick="applyChatCorrections(JSON.parse(this.dataset.corr))" data-corr='${JSON.stringify(corr)}'
                        class="inline-flex items-center gap-2 px-3 py-1.5 text-xs font-semibold text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition">
                    ✓ Aplicar corrección
                </button>
                <span class="text-xs text-gray-400 ml-2">La IA detectó valores a corregir</span>
            </div>`;
        }

        chatAppend(`<div class="flex gap-3">
            <div class="w-7 h-7 rounded-full bg-indigo-100 flex-shrink-0 flex items-center justify-center">
                <svg class="w-3.5 h-3.5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/>
                </svg>
            </div>
            <div class="flex-1 rounded-lg rounded-tl-none px-4 py-3 text-sm leading-relaxed ${isError?'bg-red-50 text-red-700':'bg-indigo-50 text-slate-700'}">
                ${chatFmt(aiText)}${mini}${applyBtn}
            </div>
        </div>`);
        chatHistory.push({ user: text, assistant: aiText });
    } catch(e) {
        document.getElementById(tid)?.remove();
        chatAppend(`<div class="text-red-600 text-sm px-4 py-2">Error de conexión.</div>`);
    } finally {
        input.disabled = false; btn.disabled = false;
        btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg> Enviar';
        input.focus();
    }
}

async function applyChatCorrections(corrections) {
    const btn = event.currentTarget;
    btn.disabled = true; btn.textContent = 'Aplicando…';
    try {
        const csrf = document.querySelector('meta[name="csrf-token"]').content;
        const res  = await fetch(CORREGIR_URL, {
            method:'PUT',
            headers:{'Content-Type':'application/json','X-CSRF-TOKEN':csrf,'Accept':'application/json'},
            body: JSON.stringify(corrections),
        });
        const data = await res.json();
        if (data.success) {
            if (corrections.items && window._bomShow) {
                corrections.items.forEach(c => {
                    const item = window._bomShow.items.find(i => i.id == c.id);
                    if (item) ['presenta_cambio_fraccion','cumple_demas_requisitos','califica_originario','criterio_de_origen','regla_de_origen']
                        .forEach(f => { if (c[f] !== undefined) item[f] = c[f]; });
                });
            }
            btn.closest('.mt-3')?.remove();
            chatAppend(`<div class="flex gap-2 items-center px-4 py-2.5 text-xs bg-green-50 border border-green-200 text-green-700 rounded-lg">
                ✓ Corrección aplicada. La tabla de análisis se ha actualizado.
            </div>`);
        } else {
            alert('No se pudo aplicar la corrección.');
            btn.disabled = false; btn.textContent = 'Aplicar corrección';
        }
    } catch {
        alert('Error de conexión.');
        btn.disabled = false; btn.textContent = 'Aplicar corrección';
    }
}
</script>
@endpush
