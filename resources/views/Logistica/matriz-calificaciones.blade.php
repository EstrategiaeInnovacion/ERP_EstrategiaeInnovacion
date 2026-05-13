@extends('layouts.erp')
@section('title', 'Calificaciones - Directorio de Proveedores')

@section('content')
<div class="min-h-screen bg-slate-50 pb-12">
    <div class="max-w-[1400px] mx-auto sm:px-6 lg:px-8 space-y-6">

        {{-- HEADER --}}
        <div class="bg-white border-b border-slate-200 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                        <a href="{{ route('logistica.index') }}" class="hover:text-blue-600 transition-colors">Panel Logística</a>
                        <span>/</span>
                        <a href="{{ route('logistica.matriz-apoyo') }}" class="hover:text-blue-600 transition-colors">Directorio de Proveedores</a>
                        <span>/</span>
                        <span class="text-slate-700 font-medium">Calificaciones</span>
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900">Análisis de Calificaciones</h1>
                    <p class="text-slate-500 mt-1 text-sm">Proveedores mejor valorados por categoría.</p>
                </div>
                <a href="{{ route('logistica.matriz-apoyo') }}"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-xl text-sm font-semibold hover:bg-slate-50 transition">
                    ← Volver a Matriz
                </a>
            </div>
        </div>

        {{-- RESUMEN STATS --}}
        @php
            // Agrupar por razón social; registros sin razón social quedan como individuales
            $makeGrupos = function($col) {
                return $col->groupBy(fn($r) => $r->razon_social ?: ('__ind_'.$r->id))
                           ->sortByDesc(fn($g) => round($g->avg('calificacion'), 1));
            };
            $gruposAgentes    = $makeGrupos($agentes);
            $gruposForwarders = $makeGrupos($forwarders);
            $gruposNavieras   = $makeGrupos($navieras);
            $gruposArrastres  = $makeGrupos($arrastres);

            $totalEquipos  = $gruposAgentes->count() + $gruposForwarders->count() + $gruposNavieras->count() + $gruposArrastres->count();
            $todos         = $agentes->concat($forwarders)->concat($navieras)->concat($arrastres);
            $promedio      = $todos->count() > 0 ? round($todos->avg('calificacion'), 1) : 0;
            $cincoEstrellas = $todos->where('calificacion', 5)->count();
        @endphp
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 text-center">
                <div class="text-3xl font-bold text-slate-800">{{ $totalEquipos }}</div>
                <div class="text-xs text-slate-500 mt-1 font-medium uppercase tracking-wide">Equipos calificados</div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 text-center">
                <div class="text-3xl font-bold text-amber-600">{{ $promedio }}</div>
                <div class="text-xs text-slate-500 mt-1 font-medium uppercase tracking-wide">Calificación promedio</div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 text-center">
                <div class="text-3xl font-bold text-green-600">{{ $cincoEstrellas }}</div>
                <div class="text-xs text-slate-500 mt-1 font-medium uppercase tracking-wide">Con 5 estrellas</div>
            </div>
            <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-5 text-center">
                <div class="text-3xl font-bold text-slate-800">4</div>
                <div class="text-xs text-slate-500 mt-1 font-medium uppercase tracking-wide">Categorías</div>
            </div>
        </div>

        {{-- FILTRO GLOBAL DE CALIFICACIÓN --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm px-6 py-4 flex flex-wrap items-center gap-3">
            <span class="text-sm font-semibold text-slate-600">Filtrar calificación:</span>
            <div class="flex gap-2 flex-wrap">
                <button onclick="setFiltro(0)" id="btn-f-0"
                        class="filtro-btn px-4 py-1.5 rounded-xl text-sm font-semibold border transition active-btn">
                    Todas
                </button>
                @for($i = 5; $i >= 1; $i--)
                <button onclick="setFiltro({{ $i }})" id="btn-f-{{ $i }}"
                        class="filtro-btn px-4 py-1.5 rounded-xl text-sm font-semibold border transition">
                    {{ str_repeat('★', $i) }}{{ str_repeat('☆', 5 - $i) }}
                </button>
                @endfor
            </div>
        </div>

        {{-- SECCIÓN: Agentes Aduanales --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-slate-800 text-lg">Agentes Aduanales</h2>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $gruposAgentes->count() }} equipo(s) · {{ $agentes->count() }} registros</p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 border border-amber-200">
                    Promedio {{ $agentes->count() ? round($agentes->avg('calificacion'), 1) : '—' }} ★
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide w-40">Calificación</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Cliente</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Razón Social</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide w-24">Equipo</th>
                            <th class="px-4 py-3 w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="tbody-cal-agentes">
                        @forelse($gruposAgentes as $rsKey => $miembros)
                        @php
                            $eqId     = 'ag-'.$loop->index;
                            $promEq   = round($miembros->avg('calificacion'), 1);
                            $califInt = (int) round($promEq);
                            $clientes = $miembros->pluck('cliente')->filter()->unique()->implode(', ') ?: '—';
                            $rSocial  = $miembros->first()->razon_social ?? '—';
                            $esGrupo  = $miembros->count() > 1;
                        @endphp
                        {{-- Fila resumen (equipo) --}}
                        <tr class="border-b border-slate-200 cal-row {{ $esGrupo ? 'cursor-pointer hover:bg-amber-50/50' : 'hover:bg-amber-50/30' }} transition-colors"
                            data-calif="{{ $califInt }}"
                            data-eq="{{ $eqId }}"
                            {{ $esGrupo ? 'onclick=toggleEquipo("'.$eqId.'")'  : '' }}>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex gap-0.5">
                                    @for($s = 1; $s <= 5; $s++)
                                        <span class="{{ $s <= $promEq ? 'text-amber-400' : 'text-slate-200' }} text-lg leading-none">★</span>
                                    @endfor
                                </div>
                                <span class="text-xs text-slate-400">{{ $promEq }}/5</span>
                            </td>
                            <td class="px-4 py-3 text-slate-700 font-medium">{{ $clientes }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ $rSocial }}</td>
                            <td class="px-4 py-3">
                                @if($esGrupo)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700 border border-blue-200">
                                    {{ $miembros->count() }} miembros
                                </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-slate-400">
                                @if($esGrupo)
                                <span id="arrow-{{ $eqId }}" class="text-sm select-none">▼</span>
                                @endif
                            </td>
                        </tr>
                        @if($esGrupo)
                        {{-- Filas de detalle (miembros del equipo) --}}
                        @foreach($miembros as $row)
                        <tr class="eq-{{ $eqId }} border-b border-slate-100 bg-blue-50/30 hidden transition-colors">
                            <td class="px-4 py-2.5 pl-8 whitespace-nowrap">
                                <div class="flex gap-0.5">
                                    @for($s = 1; $s <= 5; $s++)
                                        <span class="{{ $s <= $row->calificacion ? 'text-amber-400' : 'text-slate-200' }} text-base leading-none">★</span>
                                    @endfor
                                </div>
                                <span class="text-xs text-slate-400">{{ $row->calificacion }}/5</span>
                            </td>
                            <td class="px-4 py-2.5 text-slate-600 text-xs">{{ $row->agente_aduanal }}</td>
                            <td class="px-4 py-2.5 text-slate-500 text-xs">{{ $row->aduana ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-slate-500 text-xs">{{ $row->responsabilidad }}</td>
                            <td class="px-4 py-2.5 text-xs text-slate-400">
                                @if($row->nombre)<div>{{ $row->nombre }}</div>@endif
                                @if($row->correo_electronico)<a href="mailto:{{ $row->correo_electronico }}" class="text-blue-600 hover:underline">{{ $row->correo_electronico }}</a>@endif
                            </td>
                        </tr>
                        @endforeach
                        @endif
                        @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400 text-sm">Sin registros calificados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- SECCIÓN: Forwarders --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-slate-800 text-lg">Forwarders</h2>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $gruposForwarders->count() }} equipo(s) · {{ $forwarders->count() }} registros</p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 border border-amber-200">
                    Promedio {{ $forwarders->count() ? round($forwarders->avg('calificacion'), 1) : '—' }} ★
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide w-40">Calificación</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Cliente</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Razón Social</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide w-24">Equipo</th>
                            <th class="px-4 py-3 w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="tbody-cal-forwarders">
                        @forelse($gruposForwarders as $rsKey => $miembros)
                        @php
                            $eqId     = 'fw-'.$loop->index;
                            $promEq   = round($miembros->avg('calificacion'), 1);
                            $califInt = (int) round($promEq);
                            $clientes = $miembros->pluck('cliente')->filter()->unique()->implode(', ') ?: '—';
                            $rSocial  = $miembros->first()->razon_social ?? '—';
                            $esGrupo  = $miembros->count() > 1;
                        @endphp
                        <tr class="border-b border-slate-200 cal-row {{ $esGrupo ? 'cursor-pointer hover:bg-amber-50/50' : 'hover:bg-amber-50/30' }} transition-colors"
                            data-calif="{{ $califInt }}"
                            data-eq="{{ $eqId }}"
                            {{ $esGrupo ? 'onclick=toggleEquipo("'.$eqId.'")'  : '' }}>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex gap-0.5">
                                    @for($s = 1; $s <= 5; $s++)
                                        <span class="{{ $s <= $promEq ? 'text-amber-400' : 'text-slate-200' }} text-lg leading-none">★</span>
                                    @endfor
                                </div>
                                <span class="text-xs text-slate-400">{{ $promEq }}/5</span>
                            </td>
                            <td class="px-4 py-3 text-slate-700 font-medium">{{ $clientes }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ $rSocial }}</td>
                            <td class="px-4 py-3">
                                @if($esGrupo)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700 border border-blue-200">
                                    {{ $miembros->count() }} miembros
                                </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-slate-400">
                                @if($esGrupo)<span id="arrow-{{ $eqId }}" class="text-sm select-none">▼</span>@endif
                            </td>
                        </tr>
                        @if($esGrupo)
                        @foreach($miembros as $row)
                        <tr class="eq-{{ $eqId }} border-b border-slate-100 bg-blue-50/30 hidden">
                            <td class="px-4 py-2.5 pl-8 whitespace-nowrap">
                                <div class="flex gap-0.5">
                                    @for($s = 1; $s <= 5; $s++)
                                        <span class="{{ $s <= $row->calificacion ? 'text-amber-400' : 'text-slate-200' }} text-base leading-none">★</span>
                                    @endfor
                                </div>
                                <span class="text-xs text-slate-400">{{ $row->calificacion }}/5</span>
                            </td>
                            <td class="px-4 py-2.5 text-slate-600 text-xs">{{ $row->cliente ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-slate-500 text-xs">{{ $row->aduana ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-slate-500 text-xs">{{ $row->responsabilidad }}</td>
                            <td class="px-4 py-2.5 text-xs text-slate-400">
                                @if($row->nombre)<div>{{ $row->nombre }}</div>@endif
                                @if($row->correo_electronico)<a href="mailto:{{ $row->correo_electronico }}" class="text-blue-600 hover:underline">{{ $row->correo_electronico }}</a>@endif
                            </td>
                        </tr>
                        @endforeach
                        @endif
                        @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400 text-sm">Sin registros calificados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- SECCIÓN: Navieras --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-slate-800 text-lg">Navieras</h2>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $gruposNavieras->count() }} equipo(s) · {{ $navieras->count() }} registros</p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 border border-amber-200">
                    Promedio {{ $navieras->count() ? round($navieras->avg('calificacion'), 1) : '—' }} ★
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide w-40">Calificación</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Cliente</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Razón Social</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide w-24">Equipo</th>
                            <th class="px-4 py-3 w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="tbody-cal-navieras">
                        @forelse($gruposNavieras as $rsKey => $miembros)
                        @php
                            $eqId     = 'nv-'.$loop->index;
                            $promEq   = round($miembros->avg('calificacion'), 1);
                            $califInt = (int) round($promEq);
                            $clientes = $miembros->pluck('cliente')->filter()->unique()->implode(', ') ?: '—';
                            $rSocial  = $miembros->first()->razon_social ?? '—';
                            $esGrupo  = $miembros->count() > 1;
                        @endphp
                        <tr class="border-b border-slate-200 cal-row {{ $esGrupo ? 'cursor-pointer hover:bg-amber-50/50' : 'hover:bg-amber-50/30' }} transition-colors"
                            data-calif="{{ $califInt }}"
                            data-eq="{{ $eqId }}"
                            {{ $esGrupo ? 'onclick=toggleEquipo("'.$eqId.'")'  : '' }}>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex gap-0.5">
                                    @for($s = 1; $s <= 5; $s++)
                                        <span class="{{ $s <= $promEq ? 'text-amber-400' : 'text-slate-200' }} text-lg leading-none">★</span>
                                    @endfor
                                </div>
                                <span class="text-xs text-slate-400">{{ $promEq }}/5</span>
                            </td>
                            <td class="px-4 py-3 text-slate-700 font-medium">{{ $clientes }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ $rSocial }}</td>
                            <td class="px-4 py-3">
                                @if($esGrupo)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700 border border-blue-200">
                                    {{ $miembros->count() }} miembros
                                </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-slate-400">
                                @if($esGrupo)<span id="arrow-{{ $eqId }}" class="text-sm select-none">▼</span>@endif
                            </td>
                        </tr>
                        @if($esGrupo)
                        @foreach($miembros as $row)
                        <tr class="eq-{{ $eqId }} border-b border-slate-100 bg-blue-50/30 hidden">
                            <td class="px-4 py-2.5 pl-8 whitespace-nowrap">
                                <div class="flex gap-0.5">
                                    @for($s = 1; $s <= 5; $s++)
                                        <span class="{{ $s <= $row->calificacion ? 'text-amber-400' : 'text-slate-200' }} text-base leading-none">★</span>
                                    @endfor
                                </div>
                                <span class="text-xs text-slate-400">{{ $row->calificacion }}/5</span>
                            </td>
                            <td class="px-4 py-2.5 text-slate-600 text-xs">{{ $row->cliente ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-slate-500 text-xs">{{ $row->aduana ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-slate-500 text-xs">{{ $row->responsabilidad }}</td>
                            <td class="px-4 py-2.5 text-xs text-slate-400">
                                @if($row->nombre)<div>{{ $row->nombre }}</div>@endif
                                @if($row->correo_electronico)<a href="mailto:{{ $row->correo_electronico }}" class="text-blue-600 hover:underline">{{ $row->correo_electronico }}</a>@endif
                            </td>
                        </tr>
                        @endforeach
                        @endif
                        @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400 text-sm">Sin registros calificados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- SECCIÓN: Arrastres --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h2 class="font-bold text-slate-800 text-lg">Arrastres Nacionales</h2>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $gruposArrastres->count() }} equipo(s) · {{ $arrastres->count() }} registros</p>
                </div>
                <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700 border border-amber-200">
                    Promedio {{ $arrastres->count() ? round($arrastres->avg('calificacion'), 1) : '—' }} ★
                </span>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide w-40">Calificación</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Cliente</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide">Razón Social</th>
                            <th class="px-4 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wide w-24">Equipo</th>
                            <th class="px-4 py-3 w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="tbody-cal-arrastres">
                        @forelse($gruposArrastres as $rsKey => $miembros)
                        @php
                            $eqId     = 'ar-'.$loop->index;
                            $promEq   = round($miembros->avg('calificacion'), 1);
                            $califInt = (int) round($promEq);
                            $clientes = $miembros->pluck('cliente')->filter()->unique()->implode(', ') ?: '—';
                            $rSocial  = $miembros->first()->razon_social ?? '—';
                            $esGrupo  = $miembros->count() > 1;
                        @endphp
                        <tr class="border-b border-slate-200 cal-row {{ $esGrupo ? 'cursor-pointer hover:bg-amber-50/50' : 'hover:bg-amber-50/30' }} transition-colors"
                            data-calif="{{ $califInt }}"
                            data-eq="{{ $eqId }}"
                            {{ $esGrupo ? 'onclick=toggleEquipo("'.$eqId.'")'  : '' }}>
                            <td class="px-4 py-3 whitespace-nowrap">
                                <div class="flex gap-0.5">
                                    @for($s = 1; $s <= 5; $s++)
                                        <span class="{{ $s <= $promEq ? 'text-amber-400' : 'text-slate-200' }} text-lg leading-none">★</span>
                                    @endfor
                                </div>
                                <span class="text-xs text-slate-400">{{ $promEq }}/5</span>
                            </td>
                            <td class="px-4 py-3 text-slate-700 font-medium">{{ $clientes }}</td>
                            <td class="px-4 py-3 font-semibold text-slate-800">{{ $rSocial }}</td>
                            <td class="px-4 py-3">
                                @if($esGrupo)
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700 border border-blue-200">
                                    {{ $miembros->count() }} miembros
                                </span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-center text-slate-400">
                                @if($esGrupo)<span id="arrow-{{ $eqId }}" class="text-sm select-none">▼</span>@endif
                            </td>
                        </tr>
                        @if($esGrupo)
                        @foreach($miembros as $row)
                        <tr class="eq-{{ $eqId }} border-b border-slate-100 bg-blue-50/30 hidden">
                            <td class="px-4 py-2.5 pl-8 whitespace-nowrap">
                                <div class="flex gap-0.5">
                                    @for($s = 1; $s <= 5; $s++)
                                        <span class="{{ $s <= $row->calificacion ? 'text-amber-400' : 'text-slate-200' }} text-base leading-none">★</span>
                                    @endfor
                                </div>
                                <span class="text-xs text-slate-400">{{ $row->calificacion }}/5</span>
                            </td>
                            <td class="px-4 py-2.5 text-slate-600 text-xs">{{ $row->cliente ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-slate-500 text-xs">{{ $row->aduana ?? '—' }}</td>
                            <td class="px-4 py-2.5 text-slate-500 text-xs">{{ $row->responsabilidad }}</td>
                            <td class="px-4 py-2.5 text-xs text-slate-400">
                                @if($row->nombre)<div>{{ $row->nombre }}</div>@endif
                                @if($row->correo_electronico)<a href="mailto:{{ $row->correo_electronico }}" class="text-blue-600 hover:underline">{{ $row->correo_electronico }}</a>@endif
                            </td>
                        </tr>
                        @endforeach
                        @endif
                        @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-slate-400 text-sm">Sin registros calificados.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<style>
.filtro-btn { background: white; border-color: #e2e8f0; color: #64748b; }
.filtro-btn:hover { background: #fef9c3; border-color: #fbbf24; color: #92400e; }
.active-btn { background: #fef3c7 !important; border-color: #f59e0b !important; color: #92400e !important; font-weight: 700; }
</style>

<script>
let filtroActivo = 0;

function toggleEquipo(id) {
    const detalles = document.querySelectorAll('.eq-' + id);
    const arrow    = document.getElementById('arrow-' + id);
    if (!detalles.length) return;
    const estaAbierto = !detalles[0].classList.contains('hidden');
    detalles.forEach(r => r.classList.toggle('hidden', estaAbierto));
    if (arrow) arrow.textContent = estaAbierto ? '▼' : '▲';
}

function setFiltro(calif) {
    filtroActivo = calif;

    document.querySelectorAll('.filtro-btn').forEach(b => b.classList.remove('active-btn'));
    document.getElementById('btn-f-' + calif).classList.add('active-btn');

    document.querySelectorAll('.cal-row').forEach(row => {
        const rc   = parseInt(row.dataset.calif);
        const show = calif === 0 || rc === calif;
        row.style.display = show ? '' : 'none';
        // Ocultar detalles del equipo si el resumen se oculta
        const eqId = row.dataset.eq;
        if (eqId) {
            document.querySelectorAll('.eq-' + eqId).forEach(r => {
                if (!show) r.style.display = 'none';
                // Si se muestra, respetar el estado del toggle (oculto por defecto)
            });
        }
    });
}

document.addEventListener('DOMContentLoaded', () => setFiltro(0));
</script>
@endsection
