@extends('layouts.erp')

@section('title', 'Catálogo de Reglas T-MEC')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="{ tab: '{{ $tab }}' }">

    {{-- Header --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-400 mb-1">
                <a href="{{ route('legal.dashboard') }}" class="hover:text-indigo-600">Legal</a>
                <span>/</span>
                <a href="{{ route('legal.ce.bom.index') }}" class="hover:text-indigo-600">Análisis de Origen</a>
                <span>/</span>
                <span class="text-slate-600">Catálogo</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">Catálogo de Reglas T-MEC</h1>
        </div>
        <a href="{{ route('legal.ce.bom.index') }}"
           class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 text-slate-600 text-sm font-medium rounded-xl hover:bg-slate-50 transition">
            ← BOMs
        </a>
    </div>

    {{-- Chips resumen --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div class="bg-white rounded-xl border border-slate-200 p-3 text-center">
            <p class="text-xl font-bold text-indigo-600">{{ number_format($totalReglas) }}</p>
            <p class="text-xs text-slate-500">Reglas Sección B</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-3 text-center">
            <p class="text-xl font-bold text-amber-600">{{ number_format($totalApendice) }}</p>
            <p class="text-xs text-slate-500">Requieren Apéndice</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-3 text-center">
            <p class="text-xl font-bold text-emerald-600">{{ number_format($totalPartesCatalogo) }}</p>
            <p class="text-xs text-slate-500">Partes Catálogo</p>
        </div>
        <div class="bg-white rounded-xl border border-slate-200 p-3 text-center">
            <p class="text-xl font-bold text-sky-600">{{ number_format($totalSeccionC) }}</p>
            <p class="text-xs text-slate-500">Fracciones Sección C</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div class="border-b border-slate-200 mb-6">
        <nav class="flex gap-1 overflow-x-auto">
            @foreach([
                ['key' => 'seccion_b',  'label' => 'Sección B – Reglas'],
                ['key' => 'apend_a1',   'label' => 'Apénd. Tabla A.1'],
                ['key' => 'apend_a2',   'label' => 'Apénd. Tabla A.2'],
                ['key' => 'apend_bcd',  'label' => 'Tablas B/C/D/E/F'],
                ['key' => 'seccion_c',  'label' => 'Sección C'],
                ['key' => 'parametros', 'label' => 'Parámetros VCR'],
            ] as $t)
            <button @click="tab = '{{ $t['key'] }}'"
                    class="px-4 py-2.5 text-sm font-medium whitespace-nowrap border-b-2 -mb-px transition"
                    :class="tab === '{{ $t['key'] }}' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700'">
                {{ $t['label'] }}
            </button>
            @endforeach
        </nav>
    </div>

    {{-- ── Tab: Sección B ─────────────────────────────────────────────── --}}
    <div x-show="tab === 'seccion_b'" x-cloak>
        <form method="GET" action="{{ route('legal.ce.catalogo.index') }}" class="flex flex-wrap gap-3 mb-4">
            <input type="hidden" name="tab" value="seccion_b">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar fracción, descripción o regla..."
                   class="rounded-xl border-slate-300 text-sm focus:border-indigo-400 focus:ring-indigo-400 w-64">
            <input type="number" name="capitulo" value="{{ request('capitulo') }}" placeholder="Capítulo" min="1" max="99"
                   class="rounded-xl border-slate-300 text-sm w-28 focus:border-indigo-400 focus:ring-indigo-400">
            <label class="flex items-center gap-2 text-sm text-slate-600">
                <input type="checkbox" name="solo_apendice" value="1" {{ request('solo_apendice') ? 'checked' : '' }} class="rounded text-indigo-600">
                Solo con Apéndice
            </label>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition">Filtrar</button>
            @if(request()->hasAny(['search','capitulo','criterio','solo_apendice']))
                <a href="{{ route('legal.ce.catalogo.index') }}?tab=seccion_b" class="px-4 py-2 bg-white border border-slate-200 text-slate-600 text-sm rounded-xl hover:bg-slate-50 transition">Limpiar</a>
            @endif
        </form>

        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Fracción</th>
                        <th class="px-4 py-3 text-left">Cap.</th>
                        <th class="px-4 py-3 text-left">Descripción</th>
                        <th class="px-4 py-3 text-left">Regla</th>
                        <th class="px-4 py-3 text-center">VCR %</th>
                        <th class="px-4 py-3 text-center">Apéndice</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($reglasOrigen as $r)
                    <tr class="hover:bg-slate-50 transition">
                        <td class="px-4 py-3 font-mono text-xs text-indigo-700 font-semibold">{{ $r->fraccion_arancelaria }}</td>
                        <td class="px-4 py-3 text-center text-xs text-slate-500">{{ $r->capitulo }}</td>
                        <td class="px-4 py-3 text-slate-700 max-w-xs truncate text-xs">{{ $r->descripcion }}</td>
                        <td class="px-4 py-3 text-slate-600 max-w-sm text-xs">
                            <span class="line-clamp-2">{{ Str::limit($r->regla_texto, 120) }}</span>
                        </td>
                        <td class="px-4 py-3 text-center text-xs">
                            {{ $r->vcr_porcentaje ? $r->vcr_porcentaje . '%' : '—' }}
                        </td>
                        <td class="px-4 py-3 text-center">
                            @if($r->requiere_apendice)
                                <span class="inline-block w-2 h-2 rounded-full bg-amber-400 ring-2 ring-amber-100"></span>
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-6 py-8 text-center text-slate-400 text-sm">Sin resultados. Importa el catálogo Excel desde Configuración.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $reglasOrigen->appends(['tab' => 'seccion_b'])->links() }}</div>
    </div>

    {{-- ── Tab: Apéndice A.1 ──────────────────────────────────────────── --}}
    <div x-show="tab === 'apend_a1'" x-cloak>
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Fracción</th>
                        <th class="px-4 py-3 text-left">Descripción</th>
                        <th class="px-4 py-3 text-left">Categoría</th>
                        <th class="px-4 py-3 text-center">VCR Mín.</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($tablaA1 as $r)
                    <tr class="hover:bg-slate-50 transition text-xs">
                        <td class="px-4 py-3 font-mono text-indigo-700">{{ $r->fraccion_arancelaria ?: '—' }}</td>
                        <td class="px-4 py-3 text-slate-700 max-w-xs">{{ Str::limit($r->descripcion, 80) }}</td>
                        <td class="px-4 py-3 text-slate-500">{{ $r->categoria ?: '—' }}</td>
                        <td class="px-4 py-3 text-center text-slate-600">{{ $r->vcr_umbral_cn_pct ? $r->vcr_umbral_cn_pct . '%' : '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-slate-400 text-sm">Sin datos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $tablaA1->appends(['tab' => 'apend_a1'])->links() }}</div>
    </div>

    {{-- ── Tab: Apéndice A.2 ──────────────────────────────────────────── --}}
    <div x-show="tab === 'apend_a2'" x-cloak>
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Tipo Material</th>
                        <th class="px-4 py-3 text-left">Descripción</th>
                        <th class="px-4 py-3 text-center">VCR Mín.</th>
                        <th class="px-4 py-3 text-center">Año Vigencia</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($tablaA2 as $r)
                    <tr class="hover:bg-slate-50 transition text-xs">
                        <td class="px-4 py-3 text-slate-700 font-medium">{{ $r->tipo_material }}</td>
                        <td class="px-4 py-3 text-slate-600 max-w-sm">{{ Str::limit($r->descripcion, 80) }}</td>
                        <td class="px-4 py-3 text-center">{{ $r->porcentaje_min ? $r->porcentaje_min . '%' : '—' }}</td>
                        <td class="px-4 py-3 text-center text-slate-500">{{ $r->anio_vigencia ?: '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="4" class="px-6 py-8 text-center text-slate-400 text-sm">Sin datos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $tablaA2->appends(['tab' => 'apend_a2'])->links() }}</div>
    </div>

    {{-- ── Tab: Tablas B/C/D/E/F ──────────────────────────────────────── --}}
    <div x-show="tab === 'apend_bcd'" x-cloak>
        {{-- Resumen de tablas --}}
        <div class="flex flex-wrap gap-2 mb-4">
            @foreach($resumenTablas as $rt)
            <span class="inline-flex items-center gap-1.5 px-3 py-1 bg-slate-100 text-slate-700 text-xs font-semibold rounded-full">
                <span class="font-mono">{{ $rt->tabla_codigo }}</span>
                <span class="text-slate-400">{{ number_format($rt->total) }}</span>
            </span>
            @endforeach
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-3 text-center">Tabla</th>
                        <th class="px-4 py-3 text-left">Fracción</th>
                        <th class="px-4 py-3 text-left">Descripción</th>
                        <th class="px-4 py-3 text-center">VCR CN</th>
                        <th class="px-4 py-3 text-center">VCR VT</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($tablasBcd as $r)
                    <tr class="hover:bg-slate-50 transition text-xs">
                        <td class="px-4 py-3 text-center">
                            <span class="inline-block px-2 py-0.5 bg-indigo-50 text-indigo-700 font-mono font-semibold rounded">{{ $r->tabla_codigo }}</span>
                        </td>
                        <td class="px-4 py-3 font-mono text-slate-700">{{ $r->fraccion_arancelaria ?: '—' }}</td>
                        <td class="px-4 py-3 text-slate-600 max-w-sm">{{ Str::limit($r->descripcion, 80) }}</td>
                        <td class="px-4 py-3 text-center text-slate-600">{{ $r->vcr_umbral_cn_pct ? $r->vcr_umbral_cn_pct . '%' : '—' }}</td>
                        <td class="px-4 py-3 text-center text-slate-600">{{ $r->vcr_umbral_vt_pct ? $r->vcr_umbral_vt_pct . '%' : '—' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-8 text-center text-slate-400 text-sm">Sin datos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $tablasBcd->appends(['tab' => 'apend_bcd'])->links() }}</div>
    </div>

    {{-- ── Tab: Sección C ─────────────────────────────────────────────── --}}
    <div x-show="tab === 'seccion_c'" x-cloak>
        <form method="GET" action="{{ route('legal.ce.catalogo.index') }}" class="flex gap-3 mb-4">
            <input type="hidden" name="tab" value="seccion_c">
            <input type="text" name="search_c" value="{{ request('search_c') }}" placeholder="Buscar fracción TMEC o México..."
                   class="rounded-xl border-slate-300 text-sm focus:border-indigo-400 focus:ring-indigo-400 w-64">
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition">Filtrar</button>
        </form>
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                    <tr>
                        <th class="px-4 py-3 text-left">Fracc. TMEC</th>
                        <th class="px-4 py-3 text-left">Canadá</th>
                        <th class="px-4 py-3 text-left">EE.UU.</th>
                        <th class="px-4 py-3 text-left">México</th>
                        <th class="px-4 py-3 text-left">Descripción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($seccionC as $r)
                    <tr class="hover:bg-slate-50 transition text-xs">
                        <td class="px-4 py-3 font-mono text-indigo-700 font-semibold">{{ $r->fraccion_tmec }}</td>
                        <td class="px-4 py-3 font-mono text-slate-600">{{ $r->fraccion_canada ?: '—' }}</td>
                        <td class="px-4 py-3 font-mono text-slate-600">{{ $r->fraccion_eeuu ?: '—' }}</td>
                        <td class="px-4 py-3 font-mono text-slate-600">{{ $r->fraccion_mexico ?: '—' }}</td>
                        <td class="px-4 py-3 text-slate-600 max-w-xs">{{ Str::limit($r->descripcion, 60) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="px-6 py-8 text-center text-slate-400 text-sm">Sin datos.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-3">{{ $seccionC->appends(['tab' => 'seccion_c'])->links() }}</div>
    </div>

    {{-- ── Tab: Parámetros VCR ─────────────────────────────────────────── --}}
    <div x-show="tab === 'parametros'" x-cloak>
        @if($parametros->count() > 0)
        <div class="bg-white rounded-2xl border border-slate-200 overflow-hidden shadow-sm">
            <table class="min-w-full divide-y divide-slate-100 text-sm">
                <thead class="bg-slate-50 text-xs text-slate-500 uppercase">
                    <tr>
                        <th class="px-6 py-3 text-left">Clave</th>
                        <th class="px-6 py-3 text-left">Descripción</th>
                        <th class="px-6 py-3 text-center">Valor</th>
                        <th class="px-6 py-3 text-center">Año Vigencia</th>
                        <th class="px-6 py-3 text-center">Activo</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @foreach($parametros as $p)
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-3 font-mono text-xs text-indigo-700">{{ $p->clave }}</td>
                        <td class="px-6 py-3 text-slate-700 text-sm">{{ $p->descripcion }}</td>
                        <td class="px-6 py-3 text-center font-semibold text-slate-900">
                            {{ $p->valor_decimal ? $p->valor_decimal . '%' : ($p->valor_texto ?: '—') }}
                        </td>
                        <td class="px-6 py-3 text-center text-slate-500">{{ $p->anio_vigencia ?: '—' }}</td>
                        <td class="px-6 py-3 text-center">
                            <span class="inline-block w-2 h-2 rounded-full {{ $p->activo ? 'bg-emerald-400' : 'bg-slate-300' }}"></span>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @else
        <div class="bg-white rounded-2xl border border-slate-200 p-10 text-center">
            <p class="text-slate-400 text-sm">Sin parámetros configurados.</p>
            <a href="{{ route('legal.ce.configuracion.index') }}" class="mt-3 inline-block text-sm text-indigo-600 hover:underline">Ir a Configuración</a>
        </div>
        @endif
    </div>

</div>
@endsection
