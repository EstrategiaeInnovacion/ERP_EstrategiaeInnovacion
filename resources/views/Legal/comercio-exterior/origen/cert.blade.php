@extends('layouts.erp')

@section('title', 'Certificado USMCA – ' . $bom->clave)

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 pb-12">

    {{-- Breadcrumb --}}
    <div class="flex items-center gap-2 text-sm text-slate-400 mb-4">
        <a href="{{ route('legal.dashboard') }}" class="hover:text-indigo-600">Legal</a>
        <span>/</span>
        <a href="{{ route('legal.ce.bom.index') }}" class="hover:text-indigo-600">BOMs</a>
        <span>/</span>
        <a href="{{ route('legal.ce.bom.show', $bom) }}" class="hover:text-indigo-600">{{ $bom->clave }}</a>
        <span>/</span>
        <span class="text-slate-600">Certificado USMCA</span>
    </div>

    {{-- Header --}}
    <div class="mb-6 flex items-start justify-between gap-4 flex-wrap">
        <div>
            <h1 class="text-2xl font-bold text-slate-900">Certificado de Origen USMCA</h1>
            <p class="text-sm text-slate-500 mt-0.5 font-mono">{{ $bom->clave }}{{ $bom->nombre ? ' – ' . $bom->nombre : '' }}</p>
        </div>
        <div class="flex items-center gap-3">
            @if(!empty($saved))
            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-semibold rounded-xl">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
                Datos guardados
            </span>
            @endif
            <a href="{{ route('legal.ce.bom.show', $bom) }}"
               class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 text-slate-600 text-sm font-medium rounded-xl hover:bg-slate-50 transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Volver al BOM
            </a>
        </div>
    </div>

    <form method="POST" action="{{ route('legal.ce.origen.cert.download', $bom) }}">
    @csrf
    {{-- Helper: prioridad old() → BD → default --}}
    @php $v = fn($k, $d = '') => old($k, $saved[$k] ?? $d); @endphp

    {{-- ── BLOQUE 1: Blanket Period + Certifier Type ────────────────────── --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm mb-5 overflow-hidden">
        <div class="px-6 py-3.5 border-b border-slate-100 flex items-center gap-2"
             style="background:linear-gradient(135deg,#f8fafc,#f1f5f9)">
            <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <h2 class="text-sm font-bold text-slate-700">Blanket Period &amp; Certifier Type</h2>
        </div>
        <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-3 gap-5">

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">
                    Blanket Period — FROM <span class="text-slate-400 font-normal">(I3)</span>
                </label>
                <input type="date" name="blanket_from" value="{{ $v('blanket_from') }}"
                       class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">
                    Blanket Period — TO <span class="text-slate-400 font-normal">(I4)</span>
                </label>
                <input type="date" name="blanket_to" value="{{ $v('blanket_to') }}"
                       class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-slate-400">
            </div>

            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-2">
                    Certifier Type <span class="text-slate-400 font-normal">(C4 / E4 / G4)</span>
                </label>
                @php $certType = old('certifier_type', $saved['certifier_type'] ?? 'EXPORTER'); @endphp
                <div class="flex gap-4">
                    @foreach(['IMPORTER' => 'Importer', 'EXPORTER' => 'Exporter', 'PRODUCER' => 'Producer'] as $val => $lbl)
                    <label class="flex items-center gap-1.5 cursor-pointer text-sm text-slate-700">
                        <input type="radio" name="certifier_type" value="{{ $val }}"
                               {{ $certType === $val ? 'checked' : '' }}
                               class="accent-indigo-600">
                        {{ $lbl }}
                    </label>
                    @endforeach
                </div>
            </div>

        </div>
    </div>

    {{-- ── BLOQUE 2: Certifier + Exporter ─────────────────────────────── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">

        {{-- Certifier (Section 2) --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100 flex items-center gap-2"
                 style="background:linear-gradient(135deg,#f0fdf4,#dcfce7)">
                <svg class="w-4 h-4 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                </svg>
                <h2 class="text-sm font-bold text-emerald-800">Section 2 — Certifier</h2>
            </div>
            <div class="px-5 py-4 space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Name <span class="text-slate-400 font-normal">(B6)</span></label>
                    <input type="text" name="certifier_name" value="{{ $v('certifier_name') }}"
                           placeholder="Nombre / Razón social"
                           class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Address <span class="text-slate-400 font-normal">(B7)</span></label>
                    <input type="text" name="certifier_address" value="{{ $v('certifier_address') }}"
                           placeholder="Dirección completa"
                           class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Country <span class="text-slate-400 font-normal">(B9)</span></label>
                        <input type="text" name="certifier_country" value="{{ $v('certifier_country') }}"
                               placeholder="MEXICO"
                               class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Phone <span class="text-slate-400 font-normal">(D9)</span></label>
                        <input type="text" name="certifier_phone" value="{{ $v('certifier_phone') }}"
                               placeholder="+52 ..."
                               class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Email <span class="text-slate-400 font-normal">(B10)</span></label>
                    <input type="email" name="certifier_email" value="{{ $v('certifier_email') }}"
                           placeholder="correo@empresa.com"
                           class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
                </div>
                <p class="text-xs text-slate-400">Tax ID (RSM1504286M7) precargado en la plantilla.</p>
            </div>
        </div>

        {{-- Exporter (Section 3) --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100 flex items-center gap-2"
                 style="background:linear-gradient(135deg,#fefce8,#fef9c3)">
                <svg class="w-4 h-4 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                </svg>
                <h2 class="text-sm font-bold text-yellow-800">Section 3 — Exporter</h2>
                <button type="button" id="btn-copy-certifier"
                        class="ml-auto text-xs text-yellow-700 underline hover:text-yellow-900 transition">
                    Copiar de Certifier
                </button>
            </div>
            <div class="px-5 py-4 space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Name <span class="text-slate-400 font-normal">(F6)</span></label>
                    <input type="text" name="exporter_name" id="exporter_name" value="{{ $v('exporter_name') }}"
                           placeholder="Nombre / Razón social"
                           class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Address <span class="text-slate-400 font-normal">(F7)</span></label>
                    <input type="text" name="exporter_address" id="exporter_address" value="{{ $v('exporter_address') }}"
                           placeholder="Dirección completa"
                           class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Country <span class="text-slate-400 font-normal">(F9)</span></label>
                        <input type="text" name="exporter_country" id="exporter_country" value="{{ $v('exporter_country') }}"
                               placeholder="MEXICO"
                               class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Phone <span class="text-slate-400 font-normal">(H9)</span></label>
                        <input type="text" name="exporter_phone" id="exporter_phone" value="{{ $v('exporter_phone') }}"
                               placeholder="+52 ..."
                               class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Email <span class="text-slate-400 font-normal">(F10)</span></label>
                    <input type="email" name="exporter_email" id="exporter_email" value="{{ $v('exporter_email') }}"
                           placeholder="correo@empresa.com"
                           class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-yellow-400">
                </div>
                <p class="text-xs text-slate-400">Tax ID (RSM1504286M7) precargado en la plantilla.</p>
            </div>
        </div>

    </div>

    {{-- ── BLOQUE 3: Producer + Importer ───────────────────────────────── --}}
    <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">

        {{-- Producer (Section 4) --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100 flex items-center gap-2"
                 style="background:linear-gradient(135deg,#eff6ff,#dbeafe)">
                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                </svg>
                <h2 class="text-sm font-bold text-blue-800">Section 4 — Producer</h2>
            </div>
            <div class="px-5 py-4 space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Name <span class="text-slate-400 font-normal">(B13)</span></label>
                    <input type="text" name="producer_name" value="{{ $v('producer_name') }}"
                           placeholder="Nombre del productor"
                           class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Address <span class="text-slate-400 font-normal">(B14)</span></label>
                    <input type="text" name="producer_address" value="{{ $v('producer_address') }}"
                           placeholder="Dirección completa"
                           class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Country <span class="text-slate-400 font-normal">(B16)</span></label>
                        <input type="text" name="producer_country" value="{{ $v('producer_country') }}"
                               placeholder="MX"
                               class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Phone <span class="text-slate-400 font-normal">(D16)</span></label>
                        <input type="text" name="producer_phone" value="{{ $v('producer_phone') }}"
                               placeholder="+52 33 ..."
                               class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Email <span class="text-slate-400 font-normal">(B17)</span></label>
                    <input type="email" name="producer_email" value="{{ $v('producer_email') }}"
                           placeholder="correo@empresa.com"
                           class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Tax ID / RFC <span class="text-slate-400 font-normal">(C18)</span></label>
                    <input type="text" name="producer_tax_id" value="{{ $v('producer_tax_id') }}"
                           placeholder="RFC o EIN"
                           class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-400">
                </div>
            </div>
        </div>

        {{-- Importer (Section 5) --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-3 border-b border-slate-100 flex items-center gap-2"
                 style="background:linear-gradient(135deg,#fdf4ff,#fae8ff)">
                <svg class="w-4 h-4 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                </svg>
                <h2 class="text-sm font-bold text-purple-800">Section 5 — Importer</h2>
                <span class="ml-auto text-xs text-slate-400">Country: MEXICO (prefilled)</span>
            </div>
            <div class="px-5 py-4 space-y-3">
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Name <span class="text-slate-400 font-normal">(F13)</span></label>
                    <input type="text" name="importer_name" value="{{ $v('importer_name') }}"
                           placeholder="Nombre del importador"
                           class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Address <span class="text-slate-400 font-normal">(F14)</span></label>
                    <input type="text" name="importer_address" value="{{ $v('importer_address') }}"
                           placeholder="Dirección completa"
                           class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Phone <span class="text-slate-400 font-normal">(H16)</span></label>
                    <input type="text" name="importer_phone" value="{{ $v('importer_phone') }}"
                           placeholder="+1 ..."
                           class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Email <span class="text-slate-400 font-normal">(F17)</span></label>
                    <input type="email" name="importer_email" value="{{ $v('importer_email') }}"
                           placeholder="correo@importador.com"
                           class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400">
                </div>
                <div>
                    <label class="block text-xs font-semibold text-slate-600 mb-1">Tax ID / EIN <span class="text-slate-400 font-normal">(G18)</span></label>
                    <input type="text" name="importer_tax_id" value="{{ $v('importer_tax_id') }}"
                           placeholder="EIN o RFC"
                           class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-purple-400">
                </div>
            </div>
        </div>

    </div>

    {{-- ── BLOQUE 4: Continuation Page — partes originarias ───────────── --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm mb-5 overflow-hidden">
        <div class="px-6 py-3.5 border-b border-slate-100 flex items-center gap-3"
             style="background:linear-gradient(135deg,#f0fdf4,#dcfce7)">
            <div class="w-7 h-7 bg-emerald-500 rounded-xl flex items-center justify-center flex-shrink-0">
                <svg class="w-3.5 h-3.5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <div>
                <h2 class="text-sm font-bold text-emerald-800">Continuation Page — Partes Originarias</h2>
                <p class="text-xs text-emerald-600">
                    {{ count($parts) }} {{ count($parts) === 1 ? 'parte califica' : 'partes califican' }} —
                    completa el <strong>Supplier Part Number</strong> de cada una (col B)
                </p>
            </div>
        </div>

        @if(count($parts) > 0)
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="bg-slate-50 border-b border-slate-200">
                        <th class="px-4 py-2.5 text-left font-semibold text-slate-600 uppercase tracking-wider">Part Number</th>
                        <th class="px-4 py-2.5 text-left font-semibold text-slate-600 uppercase tracking-wider">
                            Supplier Part # <span class="font-normal text-slate-400">(col B)</span>
                        </th>
                        <th class="px-4 py-2.5 text-left font-semibold text-slate-600 uppercase tracking-wider">Description</th>
                        <th class="px-4 py-2.5 text-center font-semibold text-slate-600 uppercase tracking-wider">HTS</th>
                        <th class="px-4 py-2.5 text-center font-semibold text-slate-600 uppercase tracking-wider">Criterion</th>
                        <th class="px-4 py-2.5 text-center font-semibold text-slate-600 uppercase tracking-wider">Producer</th>
                        <th class="px-4 py-2.5 text-center font-semibold text-slate-600 uppercase tracking-wider">Method</th>
                        <th class="px-4 py-2.5 text-center font-semibold text-slate-600 uppercase tracking-wider">Country</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($parts as $part)
                    @php
                        $spn = old(
                            'supplier_part_number.' . $part['part_number'],
                            ($saved['supplier_part_number'][$part['part_number']] ?? '')
                        );
                    @endphp
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-4 py-2.5 font-mono font-semibold text-slate-800 whitespace-nowrap">{{ $part['part_number'] }}</td>
                        <td class="px-3 py-2">
                            <input type="text"
                                   name="supplier_part_number[{{ $part['part_number'] }}]"
                                   value="{{ $spn }}"
                                   placeholder="Núm. proveedor"
                                   class="w-36 rounded-lg border border-slate-200 px-2 py-1 text-xs focus:outline-none focus:ring-1 focus:ring-emerald-400 focus:border-emerald-400">
                        </td>
                        <td class="px-4 py-2.5 text-slate-600 max-w-xs truncate" title="{{ $part['description'] }}">{{ $part['description'] }}</td>
                        <td class="px-4 py-2.5 text-center font-mono text-slate-700">{{ $part['hts'] }}</td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-blue-50 text-blue-700">{{ $part['origin_criterion'] }}</span>
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold
                                {{ $part['producer'] === 'YES' ? 'bg-emerald-50 text-emerald-700' : 'bg-slate-100 text-slate-500' }}">
                                {{ $part['producer'] }}
                            </span>
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-purple-50 text-purple-700">{{ $part['method'] ?: '—' }}</span>
                        </td>
                        <td class="px-4 py-2.5 text-center">
                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-slate-100 text-slate-700">MX</span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="px-6 py-8 text-center text-slate-400 text-sm">No se encontraron partes originarias en el análisis.</div>
        @endif
    </div>

    {{-- ── BLOQUE 5: Section 12 — Certifier signature block ───────────── --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm mb-5 overflow-hidden">
        <div class="px-6 py-3.5 border-b border-slate-100 flex items-center gap-2"
             style="background:linear-gradient(135deg,#fff7ed,#ffedd5)">
            <svg class="w-4 h-4 text-orange-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
            </svg>
            <h2 class="text-sm font-bold text-orange-800">Section 12 — Certifier / Signature Block</h2>
        </div>
        <div class="px-6 py-4 grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">12b. Company <span class="text-slate-400 font-normal">(G48)</span></label>
                <input type="text" name="cert_company" value="{{ $v('cert_company') }}"
                       placeholder="Nombre de empresa"
                       class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">12c. Name <span class="text-slate-400 font-normal">(C50)</span></label>
                <input type="text" name="cert_name" value="{{ $v('cert_name') }}"
                       placeholder="Nombre del firmante"
                       class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">12d. Title <span class="text-slate-400 font-normal">(G50)</span></label>
                <input type="text" name="cert_title" value="{{ $v('cert_title') }}"
                       placeholder="Cargo / Título"
                       class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">12e. Date <span class="text-slate-400 font-normal">(B53)</span></label>
                <input type="date" name="cert_date" value="{{ $v('cert_date', now()->format('Y-m-d')) }}"
                       class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">12f. Telephone <span class="text-slate-400 font-normal">(D53)</span></label>
                <input type="text" name="cert_phone" value="{{ $v('cert_phone') }}"
                       placeholder="+52 33 ..."
                       class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300">
            </div>
            <div>
                <label class="block text-xs font-semibold text-slate-600 mb-1">12g. Email <span class="text-slate-400 font-normal">(F53)</span></label>
                <input type="email" name="cert_email" value="{{ $v('cert_email') }}"
                       placeholder="correo@empresa.com"
                       class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-300">
            </div>
        </div>
        <div class="px-6 pb-4">
            <p class="text-xs text-slate-400">La firma (12a) es manuscrita — no se completa digitalmente.</p>
        </div>
    </div>

    {{-- ── Nota informativa ──────────────────────────────────────────── --}}
    <div class="flex gap-3 p-4 bg-amber-50 border border-amber-200 rounded-2xl mb-6 text-sm text-amber-800">
        <svg class="w-5 h-5 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                  d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            Los campos vacíos aparecerán como
            <code class="px-1 py-0.5 bg-amber-100 rounded text-blue-700 font-mono text-xs">[Campo]</code>
            en azul dentro del Excel. Los datos se guardan automáticamente al descargar.
        </div>
    </div>

    {{-- ── Botones ───────────────────────────────────────────────────── --}}
    <div class="flex justify-end">
        <button type="submit"
                class="inline-flex items-center gap-2 px-7 py-3 bg-blue-600 hover:bg-blue-700 text-white text-sm font-bold rounded-2xl transition shadow-md hover:shadow-lg">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            Guardar y Descargar Certificado USMCA (.xlsx)
        </button>
    </div>

    </form>

</div>

@push('scripts')
<script>
document.getElementById('btn-copy-certifier')?.addEventListener('click', function () {
    const map = {
        'exporter_name':    '[name="certifier_name"]',
        'exporter_address': '[name="certifier_address"]',
        'exporter_country': '[name="certifier_country"]',
        'exporter_phone':   '[name="certifier_phone"]',
        'exporter_email':   '[name="certifier_email"]',
    };
    Object.entries(map).forEach(([id, sel]) => {
        const src = document.querySelector(sel);
        const dst = document.getElementById(id);
        if (src && dst) dst.value = src.value;
    });
});
</script>
@endpush
@endsection
