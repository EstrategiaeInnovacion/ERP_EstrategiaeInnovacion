@extends('layouts.erp')
@section('title', 'Directorio de Proveedores')

@section('content')
<div id="mis-clientes-data" data-clientes='@json($misClientes)' class="hidden"></div>
<div class="min-h-screen bg-slate-50 pb-12">
    <div class="max-w-[1400px] mx-auto sm:px-6 lg:px-8 space-y-6">

        {{-- HEADER --}}
        <div class="bg-white border-b border-slate-200 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                        <a href="{{ route('logistica.index') }}" class="hover:text-blue-600 transition-colors">Panel Logística</a>
                        <span>/</span>
                        <span class="text-slate-700 font-medium">Directorio de Proveedores</span>
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900">Directorio de Proveedores</h1>
                    <p class="text-slate-500 mt-1 text-sm">Registro y seguimiento de actividades de apoyo operativo entre áreas.</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('logistica.matriz-apoyo.calificaciones') }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-amber-50 border border-amber-300 text-amber-700 rounded-xl text-sm font-semibold hover:bg-amber-100 transition">
                        ★ Calificaciones
                    </a>
                    <label class="text-sm font-semibold text-slate-600 whitespace-nowrap">Filtrar por cliente:</label>
                    <select id="filtro-cliente-global" onchange="filtrarTodo()"
                            class="text-sm border border-slate-300 rounded-xl px-4 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white shadow-sm min-w-[200px]">
                        <option value="">Mis clientes</option>
                        @foreach($clientes as $cl)
                            <option value="{{ mb_strtolower($cl) }}">{{ $cl }}</option>
                        @endforeach
                    </select>
                    <button onclick="limpiarFiltroGlobal()" title="Ver mis clientes"
                            class="p-2 rounded-xl border border-slate-200 text-slate-500 hover:text-amber-700 hover:bg-amber-50 transition text-sm">
                        ✕
                    </button>
                </div>
            </div>
        </div>

        {{-- TABS NAV --}}
        <div class="flex gap-1 pt-2">
            @php
                $tabs = [
                    ['key' => 'agentes',    'label' => 'Agentes A'],
                    ['key' => 'forwarders', 'label' => 'Forwarders'],
                    ['key' => 'navieras',   'label' => 'Navieras'],
                    ['key' => 'arrastres',  'label' => 'Arrastres'],
                ];
            @endphp
            @foreach($tabs as $tab)
                @php $tabKey = $tab['key']; @endphp
                <button onclick="cambiarTab(this.dataset.tab)" data-tab="{{ $tabKey }}" id="tab-{{ $tabKey }}"
                    class="tab-btn px-6 py-3 text-sm rounded-t-2xl border border-b-0 transition-all
                           {{ $loop->first
                               ? 'font-bold border-amber-300 bg-amber-50 text-amber-700 shadow-sm'
                               : 'font-semibold border-slate-200 bg-white text-slate-500 hover:text-slate-700' }}">
                    {{ $tab['label'] }}
                </button>
            @endforeach
        </div>

        {{-- PANEL: Agentes A --}}
        <div id="panel-agentes" class="tab-panel">
            <div class="bg-white rounded-b-2xl rounded-tr-2xl border border-slate-200 shadow-sm overflow-hidden">

                {{-- Toolbar --}}
                <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-slate-800 text-lg">Agentes Aduanales</h3>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $agentes->count() }} registros</p>
                    </div>
                    <div class="flex gap-2 items-center flex-wrap">
                        <input type="text" id="buscar-agentes"
                               placeholder="Buscar en la tabla..."
                               oninput="filtrarAgentes()"
                               class="text-sm border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400 w-56">
                        <button onclick="abrirModalAgente()"
                                class="flex items-center gap-1.5 px-4 py-2 text-white font-bold text-sm rounded-xl transition shadow-md hover:shadow-lg hover:-translate-y-0.5"
                                style="background-color:#1a5276;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Agregar
                        </button>
                    </div>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border-collapse" id="tabla-agentes">
                        <thead>
                            <tr style="background-color:#1a5276;">
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[130px]">Cliente</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[120px]">Aduana</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[130px]">Agente Aduanal</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[180px]">Razón Social</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[90px]">Patente</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-center border-r border-white/20 min-w-[130px]">Calificación (1-5)</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[230px]">Responsabilidad en la Operación</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[150px]">Nombre</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[200px]">Correo Electrónico</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[120px]">Teléfono</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[180px]">Comentarios</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-center min-w-[90px]">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-agentes">
                            @php
                                $grouped    = $agentes->groupBy(fn($r) => ($r->cliente ?? '') . '||' . $r->agente_aduanal);
                                $groupIndex = 0;
                            @endphp
                            @forelse($grouped as $groupKey => $rows)
                                @php $bgClass = $groupIndex % 2 === 0 ? 'bg-white' : 'bg-slate-50/60'; @endphp
                                @foreach($rows as $i => $row)
                                    <tr class="{{ $bgClass }} border-b border-slate-100 hover:bg-amber-50/40 transition-colors group-row"
                                        data-grupo="{{ $groupIndex }}"
                                        data-cliente="{{ mb_strtolower($row->cliente ?? '') }}"
                                        data-propio="{{ in_array(mb_strtolower($row->cliente ?? ''), $misClientes) ? '1' : '0' }}"
                                        data-search="{{ mb_strtolower($row->cliente . ' ' . $row->aduana . ' ' . $row->agente_aduanal . ' ' . $row->razon_social . ' ' . $row->patente . ' ' . $row->responsabilidad . ' ' . $row->nombre . ' ' . $row->correo_electronico . ' ' . $row->telefono . ' ' . $row->comentarios) }}">

                                        @if($i === 0)
                                            <td rowspan="{{ $rows->count() }}" class="px-4 py-3 font-semibold text-slate-800 align-top border-r border-slate-100 border-b border-slate-200">
                                                {{ $row->cliente }}
                                            </td>
                                            <td rowspan="{{ $rows->count() }}" class="px-4 py-3 text-slate-600 align-top border-r border-slate-100 border-b border-slate-200">
                                                {{ $row->aduana }}
                                            </td>
                                            <td rowspan="{{ $rows->count() }}" class="px-4 py-3 font-semibold text-slate-800 align-top border-r border-slate-100 border-b border-slate-200">
                                                {{ $row->agente_aduanal }}
                                            </td>
                                            <td rowspan="{{ $rows->count() }}" class="px-4 py-3 text-slate-600 align-top border-r border-slate-100 border-b border-slate-200">
                                                {{ $row->razon_social }}
                                            </td>
                                            <td rowspan="{{ $rows->count() }}" class="px-4 py-3 text-slate-600 align-top border-r border-slate-100 border-b border-slate-200 font-mono text-xs">
                                                {{ $row->patente }}
                                            </td>
                                            <td rowspan="{{ $rows->count() }}" class="px-4 py-3 align-top border-r border-slate-100 border-b border-slate-200 text-center">
                                                @if($row->calificacion)
                                                    <div class="flex justify-center gap-0.5 mb-0.5">
                                                        @for($s = 1; $s <= 5; $s++)
                                                            <span class="{{ $s <= $row->calificacion ? 'text-amber-400' : 'text-slate-200' }} text-base leading-none">★</span>
                                                        @endfor
                                                    </div>
                                                    <span class="text-xs text-slate-400">{{ $row->calificacion }}/5</span>
                                                @else
                                                    <span class="text-slate-300 text-xs">—</span>
                                                @endif
                                            </td>
                                        @endif

                                        <td class="px-4 py-3 text-slate-700 border-r border-slate-100 font-medium">{{ $row->responsabilidad }}</td>
                                        <td class="px-4 py-3 text-slate-600 border-r border-slate-100">{{ $row->nombre }}</td>
                                        <td class="px-4 py-3 border-r border-slate-100">
                                            @if($row->correo_electronico)
                                                <a href="mailto:{{ $row->correo_electronico }}"
                                                   class="text-blue-600 hover:underline text-xs">{{ $row->correo_electronico }}</a>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-slate-600 border-r border-slate-100 font-mono text-xs">{{ $row->telefono }}</td>
                                        <td class="px-4 py-3 text-slate-500 border-r border-slate-100 text-xs max-w-[200px] whitespace-pre-line">{{ $row->comentarios }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <button data-id="{{ $row->id }}" data-record='@json($row->toArray())' onclick="editarAgente(this)"
                                                        class="p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Editar">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                </button>
                                                <button data-id="{{ $row->id }}" onclick="eliminarAgente(this.dataset.id)"
                                                        class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Eliminar">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                @php $groupIndex++; @endphp
                            @empty
                                <tr id="fila-vacia">
                                    <td colspan="12" class="py-16 text-center">
                                        <div class="flex flex-col items-center gap-2 text-slate-400">
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                            </svg>
                                            <span class="font-medium">No hay registros</span>
                                            <span class="text-xs">Haz clic en "Agregar" para ingresar el primer agente.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

        {{-- PANEL: Forwarders --}}
        <div id="panel-forwarders" class="tab-panel hidden">
            <div class="bg-white rounded-b-2xl rounded-tr-2xl border border-slate-200 shadow-sm overflow-hidden">

                {{-- Toolbar --}}
                <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-slate-800 text-lg">Forwarders</h3>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $forwarders->count() }} registros</p>
                    </div>
                    <div class="flex gap-2 items-center flex-wrap">
                        <input type="text" id="buscar-forwarders"
                               placeholder="Buscar en la tabla..."
                               oninput="filtrarForwarders()"
                               class="text-sm border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400 w-56">
                        <button onclick="abrirModalForwarder()"
                                class="flex items-center gap-1.5 px-4 py-2 text-white font-bold text-sm rounded-xl transition shadow-md hover:shadow-lg hover:-translate-y-0.5"
                                style="background-color:#1a5276;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                            </svg>
                            Agregar
                        </button>
                    </div>
                </div>

                {{-- Table --}}
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border-collapse" id="tabla-forwarders">
                        <thead>
                            <tr style="background-color:#1a5276;">
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[140px]">Cliente</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[130px]">Aduana</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[180px]">Razón Social</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-center border-r border-white/20 min-w-[130px]">Calificación (1-5)</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[210px]">Responsabilidades en la Operación</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[150px]">Nombre</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[200px]">Correo Electrónico</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[120px]">Teléfono</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[180px]">Comentarios</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-center min-w-[90px]">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-forwarders">
                            @php
                                $groupedFw    = $forwarders->groupBy('cliente');
                                $groupIdxFw   = 0;
                            @endphp
                            @forelse($groupedFw as $clienteName => $rows)
                                @php $bgClass = $groupIdxFw % 2 === 0 ? 'bg-white' : 'bg-slate-50/60'; @endphp
                                @foreach($rows as $i => $row)
                                    <tr class="{{ $bgClass }} border-b border-slate-100 hover:bg-amber-50/40 transition-colors fw-row"
                                        data-grupo="{{ $groupIdxFw }}"
                                        data-cliente="{{ mb_strtolower($row->cliente ?? '') }}"
                                        data-propio="{{ in_array(mb_strtolower($row->cliente ?? ''), $misClientes) ? '1' : '0' }}"
                                        data-search="{{ mb_strtolower($row->cliente . ' ' . $row->aduana . ' ' . $row->razon_social . ' ' . $row->responsabilidad . ' ' . $row->nombre . ' ' . $row->correo_electronico . ' ' . $row->telefono . ' ' . $row->comentarios) }}">

                                        @if($i === 0)
                                            <td rowspan="{{ $rows->count() }}" class="px-4 py-3 font-semibold text-slate-800 align-top border-r border-slate-100 border-b border-slate-200">
                                                {{ $row->cliente }}
                                            </td>
                                            <td rowspan="{{ $rows->count() }}" class="px-4 py-3 text-slate-600 align-top border-r border-slate-100 border-b border-slate-200">
                                                {{ $row->aduana }}
                                            </td>
                                            <td rowspan="{{ $rows->count() }}" class="px-4 py-3 text-slate-600 align-top border-r border-slate-100 border-b border-slate-200">
                                                {{ $row->razon_social }}
                                            </td>
                                            <td rowspan="{{ $rows->count() }}" class="px-4 py-3 align-top border-r border-slate-100 border-b border-slate-200 text-center">
                                                @if($row->calificacion)
                                                    <div class="flex justify-center gap-0.5 mb-0.5">
                                                        @for($s = 1; $s <= 5; $s++)
                                                            <span class="{{ $s <= $row->calificacion ? 'text-amber-400' : 'text-slate-200' }} text-base leading-none">★</span>
                                                        @endfor
                                                    </div>
                                                    <span class="text-xs text-slate-400">{{ $row->calificacion }}/5</span>
                                                @else
                                                    <span class="text-slate-300 text-xs">—</span>
                                                @endif
                                            </td>
                                        @endif

                                        <td class="px-4 py-3 text-slate-700 border-r border-slate-100 font-medium">{{ $row->responsabilidad }}</td>
                                        <td class="px-4 py-3 text-slate-600 border-r border-slate-100">{{ $row->nombre }}</td>
                                        <td class="px-4 py-3 border-r border-slate-100">
                                            @if($row->correo_electronico)
                                                <a href="mailto:{{ $row->correo_electronico }}" class="text-blue-600 hover:underline text-xs">{{ $row->correo_electronico }}</a>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-slate-600 border-r border-slate-100 font-mono text-xs">{{ $row->telefono }}</td>
                                        <td class="px-4 py-3 text-slate-500 border-r border-slate-100 text-xs max-w-[200px] whitespace-pre-line">{{ $row->comentarios }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <button data-id="{{ $row->id }}" data-record='@json($row->toArray())' onclick="editarForwarder(this)"
                                                        class="p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Editar">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                                    </svg>
                                                </button>
                                                <button data-id="{{ $row->id }}" onclick="eliminarForwarder(this.dataset.id)"
                                                        class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Eliminar">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                @php $groupIdxFw++; @endphp
                            @empty
                                <tr>
                                    <td colspan="10" class="py-16 text-center">
                                        <div class="flex flex-col items-center gap-2 text-slate-400">
                                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/>
                                            </svg>
                                            <span class="font-medium">No hay registros</span>
                                            <span class="text-xs">Haz clic en "Agregar" para ingresar el primer forwarder.</span>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

            </div>
        </div>

        {{-- PANEL: Navieras --}}
        <div id="panel-navieras" class="tab-panel hidden">
            <div class="bg-white rounded-b-2xl rounded-tr-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-slate-800 text-lg">Navieras</h3>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $navieras->count() }} registros</p>
                    </div>
                    <div class="flex gap-2 items-center flex-wrap">
                        <input type="text" id="buscar-navieras" placeholder="Buscar en la tabla..." oninput="filtrarNavieras()"
                               class="text-sm border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400 w-48">
                        <button onclick="abrirModalNaviera()"
                                class="flex items-center gap-1.5 px-4 py-2 text-white font-bold text-sm rounded-xl transition shadow-md hover:shadow-lg hover:-translate-y-0.5"
                                style="background-color:#1a5276;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Agregar
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr style="background-color:#1a5276;">
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[130px]">Cliente</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[120px]">Aduana</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[180px]">Razón Social</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-center border-r border-white/20 min-w-[130px]">Calificación (1-5)</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[180px]">Responsabilidades en la Operación</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[150px]">Nombre</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[200px]">Correo Electrónico</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[120px]">Teléfono</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[180px]">Comentarios</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-center min-w-[90px]">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-navieras">
                            @php $groupedNv = $navieras->groupBy(fn($r) => ($r->cliente ?? '') . '||' . ($r->razon_social ?? '')); $gIdxNv = 0; @endphp
                            @forelse($groupedNv as $gk => $rows)
                                @php $bgClass = $gIdxNv % 2 === 0 ? 'bg-white' : 'bg-slate-50/60'; @endphp
                                @foreach($rows as $i => $row)
                                    <tr class="{{ $bgClass }} border-b border-slate-100 hover:bg-amber-50/40 transition-colors nv-row"
                                        data-grupo="{{ $gIdxNv }}"
                                        data-cliente="{{ mb_strtolower($row->cliente ?? '') }}"
                                        data-propio="{{ in_array(mb_strtolower($row->cliente ?? ''), $misClientes) ? '1' : '0' }}"
                                        data-search="{{ mb_strtolower($row->cliente . ' ' . $row->aduana . ' ' . $row->razon_social . ' ' . $row->responsabilidad . ' ' . $row->nombre . ' ' . $row->correo_electronico . ' ' . $row->comentarios) }}">
                                        @if($i === 0)
                                            <td rowspan="{{ $rows->count() }}" class="px-4 py-3 font-semibold text-slate-800 align-top border-r border-slate-100 border-b border-slate-200">{{ $row->cliente }}</td>
                                            <td rowspan="{{ $rows->count() }}" class="px-4 py-3 text-slate-600 align-top border-r border-slate-100 border-b border-slate-200">{{ $row->aduana }}</td>
                                            <td rowspan="{{ $rows->count() }}" class="px-4 py-3 text-slate-600 align-top border-r border-slate-100 border-b border-slate-200">{{ $row->razon_social }}</td>
                                            <td rowspan="{{ $rows->count() }}" class="px-4 py-3 align-top border-r border-slate-100 border-b border-slate-200 text-center">
                                                @if($row->calificacion)
                                                    <div class="flex justify-center gap-0.5 mb-0.5">@for($s=1;$s<=5;$s++)<span class="{{ $s<=$row->calificacion?'text-amber-400':'text-slate-200' }} text-base">★</span>@endfor</div>
                                                    <span class="text-xs text-slate-400">{{ $row->calificacion }}/5</span>
                                                @else<span class="text-slate-300 text-xs">—</span>@endif
                                            </td>
                                        @endif
                                        <td class="px-4 py-3 text-slate-700 border-r border-slate-100 font-medium">{{ $row->responsabilidad }}</td>
                                        <td class="px-4 py-3 text-slate-600 border-r border-slate-100">{{ $row->nombre }}</td>
                                        <td class="px-4 py-3 border-r border-slate-100">@if($row->correo_electronico)<a href="mailto:{{ $row->correo_electronico }}" class="text-blue-600 hover:underline text-xs">{{ $row->correo_electronico }}</a>@endif</td>
                                        <td class="px-4 py-3 text-slate-600 border-r border-slate-100 font-mono text-xs">{{ $row->telefono }}</td>
                                        <td class="px-4 py-3 text-slate-500 border-r border-slate-100 text-xs max-w-[200px]">{{ $row->comentarios }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <button data-id="{{ $row->id }}" data-record='@json($row->toArray())' onclick="editarNaviera(this)" class="p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Editar">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </button>
                                                <button data-id="{{ $row->id }}" onclick="eliminarNaviera(this.dataset.id)" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Eliminar">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                @php $gIdxNv++; @endphp
                            @empty
                                <tr><td colspan="10" class="py-16 text-center"><div class="flex flex-col items-center gap-2 text-slate-400"><svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg><span class="font-medium">No hay registros</span><span class="text-xs">Haz clic en "Agregar" para ingresar la primera naviera.</span></div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- PANEL: Arrastres --}}
        <div id="panel-arrastres" class="tab-panel hidden">
            <div class="bg-white rounded-b-2xl rounded-tr-2xl border border-slate-200 shadow-sm overflow-hidden">
                <div class="px-5 py-4 border-b border-slate-100 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3">
                    <div>
                        <h3 class="font-bold text-slate-800 text-lg">Arrastres Nacionales</h3>
                        <p class="text-xs text-slate-400 mt-0.5">{{ $arrastres->count() }} registros</p>
                    </div>
                    <div class="flex gap-2 items-center flex-wrap">
                        <input type="text" id="buscar-arrastres" placeholder="Buscar en la tabla..." oninput="filtrarArrastres()"
                               class="text-sm border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-amber-400 w-48">
                        <button onclick="abrirModalArrastre()"
                                class="flex items-center gap-1.5 px-4 py-2 text-white font-bold text-sm rounded-xl transition shadow-md hover:shadow-lg hover:-translate-y-0.5"
                                style="background-color:#1a5276;">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            Agregar
                        </button>
                    </div>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm border-collapse">
                        <thead>
                            <tr style="background-color:#1a5276;">
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[130px]">Cliente</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[120px]">Aduana</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[180px]">Razón Social</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-center border-r border-white/20 min-w-[130px]">Calificación (1-5)</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[200px]">Responsabilidades en la Operación</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[150px]">Nombre</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[200px]">Correo Electrónico</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[120px]">Teléfono</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-left border-r border-white/20 min-w-[180px]">Comentarios</th>
                                <th class="text-white font-bold text-xs uppercase tracking-wide px-4 py-3 text-center min-w-[90px]">Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="tbody-arrastres">
                            @php $groupedAr = $arrastres->groupBy(fn($r) => ($r->cliente ?? '') . '||' . ($r->razon_social ?? '')); $gIdxAr = 0; @endphp
                            @forelse($groupedAr as $gk => $rows)
                                @php $bgClass = $gIdxAr % 2 === 0 ? 'bg-white' : 'bg-slate-50/60'; @endphp
                                @foreach($rows as $i => $row)
                                    <tr class="{{ $bgClass }} border-b border-slate-100 hover:bg-amber-50/40 transition-colors ar-row"
                                        data-grupo="{{ $gIdxAr }}"
                                        data-cliente="{{ mb_strtolower($row->cliente ?? '') }}"
                                        data-propio="{{ in_array(mb_strtolower($row->cliente ?? ''), $misClientes) ? '1' : '0' }}"
                                        data-search="{{ mb_strtolower($row->cliente . ' ' . $row->aduana . ' ' . $row->razon_social . ' ' . $row->responsabilidad . ' ' . $row->nombre . ' ' . $row->correo_electronico . ' ' . $row->comentarios) }}">
                                        @if($i === 0)
                                            <td rowspan="{{ $rows->count() }}" class="px-4 py-3 font-semibold text-slate-800 align-top border-r border-slate-100 border-b border-slate-200">{{ $row->cliente }}</td>
                                            <td rowspan="{{ $rows->count() }}" class="px-4 py-3 text-slate-600 align-top border-r border-slate-100 border-b border-slate-200">{{ $row->aduana }}</td>
                                            <td rowspan="{{ $rows->count() }}" class="px-4 py-3 text-slate-600 align-top border-r border-slate-100 border-b border-slate-200">{{ $row->razon_social }}</td>
                                            <td rowspan="{{ $rows->count() }}" class="px-4 py-3 align-top border-r border-slate-100 border-b border-slate-200 text-center">
                                                @if($row->calificacion)
                                                    <div class="flex justify-center gap-0.5 mb-0.5">@for($s=1;$s<=5;$s++)<span class="{{ $s<=$row->calificacion?'text-amber-400':'text-slate-200' }} text-base">★</span>@endfor</div>
                                                    <span class="text-xs text-slate-400">{{ $row->calificacion }}/5</span>
                                                @else<span class="text-slate-300 text-xs">—</span>@endif
                                            </td>
                                        @endif
                                        <td class="px-4 py-3 text-slate-700 border-r border-slate-100 font-medium">{{ $row->responsabilidad }}</td>
                                        <td class="px-4 py-3 text-slate-600 border-r border-slate-100">{{ $row->nombre }}</td>
                                        <td class="px-4 py-3 border-r border-slate-100">@if($row->correo_electronico)<a href="mailto:{{ $row->correo_electronico }}" class="text-blue-600 hover:underline text-xs">{{ $row->correo_electronico }}</a>@endif</td>
                                        <td class="px-4 py-3 text-slate-600 border-r border-slate-100 font-mono text-xs">{{ $row->telefono }}</td>
                                        <td class="px-4 py-3 text-slate-500 border-r border-slate-100 text-xs max-w-[200px]">{{ $row->comentarios }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex items-center justify-center gap-1">
                                                <button data-id="{{ $row->id }}" data-record='@json($row->toArray())' onclick="editarArrastre(this)" class="p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition" title="Editar">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                                </button>
                                                <button data-id="{{ $row->id }}" onclick="eliminarArrastre(this.dataset.id)" class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition" title="Eliminar">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                                @php $gIdxAr++; @endphp
                            @empty
                                <tr><td colspan="10" class="py-16 text-center"><div class="flex flex-col items-center gap-2 text-slate-400"><svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2"/></svg><span class="font-medium">No hay registros</span><span class="text-xs">Haz clic en "Agregar" para ingresar el primer arrastre.</span></div></td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>

{{-- MODAL: Agregar / Editar Agente --}}
<div id="modal-agente" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="cerrarModalAgente()"></div>
    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden">

        <div class="px-6 py-4 flex items-center justify-between" style="background-color:#1a5276;">
            <h3 id="modal-titulo" class="font-bold text-white text-lg">Agregar Agente Aduanal</h3>
            <button onclick="cerrarModalAgente()" class="text-white/70 hover:text-white transition p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="form-agente" class="p-6 overflow-y-auto max-h-[80vh]" onsubmit="guardarAgente(event)">
            <input type="hidden" id="agente-id" value="">
            <div class="grid grid-cols-2 gap-4">

                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Cliente</label>
                    <select id="f-cliente" class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                        <option value="">— Sin cliente —</option>
                        @foreach($clientes as $cl)
                            @if(in_array(mb_strtolower($cl), $misClientes))
                                <option value="{{ $cl }}">{{ $cl }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Aduana</label>
                    <select id="f-aduana" class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                        <option value="">— Sin aduana —</option>
                        @foreach($aduanas as $a)
                            <option value="{{ $a->aduana.$a->seccion }}">{{ $a->aduana.$a->seccion }} - {{ $a->denominacion }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Agente Aduanal <span class="text-red-500">*</span></label>
                    <input type="text" id="f-agente_aduanal" required
                           class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Razón Social</label>
                    <input type="text" id="f-razon_social"
                           class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Patente</label>
                    <input type="text" id="f-patente"
                           class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Calificación (1-5)</label>
                    <select id="f-calificacion"
                            class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                        <option value="">Sin calificación</option>
                        @for($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}">{{ $i }} — {{ str_repeat('★', $i) }}{{ str_repeat('☆', 5 - $i) }}</option>
                        @endfor
                    </select>
                </div>

                <div class="col-span-2">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Responsabilidad en la Operación <span class="text-red-500">*</span></label>
                    <select id="f-responsabilidad" required
                            class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                        <option value="">Seleccionar responsabilidad...</option>
                        @foreach($responsabilidades as $r)
                            <option value="{{ $r }}">{{ $r }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Nombre</label>
                    <input type="text" id="f-nombre"
                           class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Correo Electrónico</label>
                    <input type="email" id="f-correo_electronico"
                           class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Teléfono</label>
                    <input type="text" id="f-telefono"
                           class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>

                <div class="col-span-2">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Comentarios</label>
                    <textarea id="f-comentarios" rows="3"
                              class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400 resize-none"></textarea>
                </div>

            </div>

            <div class="flex justify-end gap-2 mt-6 pt-4 border-t border-slate-100">
                <button type="button" onclick="cerrarModalAgente()"
                        class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition">
                    Cancelar
                </button>
                <button type="submit" id="btn-guardar-agente"
                        class="px-5 py-2.5 text-sm font-bold text-white rounded-xl transition shadow-md hover:shadow-lg hover:-translate-y-0.5"
                        style="background-color:#1a5276;">
                    Guardar
                </button>
            </div>
        </form>

    </div>
</div>

{{-- MODAL: Agregar / Editar Forwarder --}}
<div id="modal-forwarder" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="cerrarModalForwarder()"></div>
    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden">

        <div class="px-6 py-4 flex items-center justify-between" style="background-color:#1a5276;">
            <h3 id="modal-titulo-fw" class="font-bold text-white text-lg">Agregar Forwarder</h3>
            <button onclick="cerrarModalForwarder()" class="text-white/70 hover:text-white transition p-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="form-forwarder" class="p-6 overflow-y-auto max-h-[80vh]" onsubmit="guardarForwarder(event)">
            <input type="hidden" id="forwarder-id" value="">
            <div class="grid grid-cols-2 gap-4">

                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Cliente <span class="text-red-500">*</span></label>
                    <select id="fw-cliente" required class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                        <option value="">— Seleccionar cliente —</option>
                        @foreach($clientes as $cl)
                            @if(in_array(mb_strtolower($cl), $misClientes))
                                <option value="{{ $cl }}">{{ $cl }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Aduana</label>
                    <select id="fw-aduana" class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                        <option value="">— Sin aduana —</option>
                        @foreach($aduanas as $a)
                            <option value="{{ $a->aduana.$a->seccion }}">{{ $a->aduana.$a->seccion }} - {{ $a->denominacion }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Razón Social</label>
                    <input type="text" id="fw-razon_social"
                           class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Calificación (1-5)</label>
                    <select id="fw-calificacion"
                            class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                        <option value="">Sin calificación</option>
                        @for($i = 1; $i <= 5; $i++)
                            <option value="{{ $i }}">{{ $i }} — {{ str_repeat('★', $i) }}{{ str_repeat('☆', 5 - $i) }}</option>
                        @endfor
                    </select>
                </div>

                <div class="col-span-2">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Responsabilidad en la Operación <span class="text-red-500">*</span></label>
                    <select id="fw-responsabilidad" required
                            class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                        <option value="">Seleccionar responsabilidad...</option>
                        @foreach($responsabilidadesForwarder as $r)
                            <option value="{{ $r }}">{{ $r }}</option>
                        @endforeach
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Nombre</label>
                    <input type="text" id="fw-nombre"
                           class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Correo Electrónico</label>
                    <input type="email" id="fw-correo_electronico"
                           class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Teléfono</label>
                    <input type="text" id="fw-telefono"
                           class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400">
                </div>

                <div class="col-span-2">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Comentarios</label>
                    <textarea id="fw-comentarios" rows="3"
                              class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400 resize-none"></textarea>
                </div>

            </div>

            <div class="flex justify-end gap-2 mt-6 pt-4 border-t border-slate-100">
                <button type="button" onclick="cerrarModalForwarder()"
                        class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition">
                    Cancelar
                </button>
                <button type="submit" id="btn-guardar-fw"
                        class="px-5 py-2.5 text-sm font-bold text-white rounded-xl transition shadow-md hover:shadow-lg hover:-translate-y-0.5"
                        style="background-color:#1a5276;">
                    Guardar
                </button>
            </div>
        </form>

    </div>
</div>

{{-- MODAL: Navieras --}}
<div id="modal-naviera" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="cerrarModalNaviera()"></div>
    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden">
        <div class="px-6 py-4 flex items-center justify-between" style="background-color:#1a5276;">
            <h3 id="modal-titulo-nv" class="font-bold text-white text-lg">Agregar Naviera</h3>
            <button onclick="cerrarModalNaviera()" class="text-white/70 hover:text-white transition p-1"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="form-naviera" class="p-6 overflow-y-auto max-h-[80vh]" onsubmit="guardarNaviera(event)">
            <input type="hidden" id="naviera-id" value="">
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Cliente</label><select id="nv-cliente" class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white"><option value="">— Sin cliente —</option>@foreach($clientes as $cl)@if(in_array(mb_strtolower($cl),$misClientes))<option value="{{ $cl }}">{{ $cl }}</option>@endif @endforeach</select></div>
                <div><label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Aduana</label><select id="nv-aduana" class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white"><option value="">— Sin aduana —</option>@foreach($aduanas as $a)<option value="{{ $a->aduana.$a->seccion }}">{{ $a->aduana.$a->seccion }} - {{ $a->denominacion }}</option>@endforeach</select></div>
                <div><label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Razón Social</label><input type="text" id="nv-razon_social" class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400"></div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Calificación (1-5)</label>
                    <select id="nv-calificacion" class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                        <option value="">Sin calificación</option>
                        @for($i=1;$i<=5;$i++)<option value="{{ $i }}">{{ $i }} — {{ str_repeat('★',$i) }}{{ str_repeat('☆',5-$i) }}</option>@endfor
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Responsabilidad en la Operación <span class="text-red-500">*</span></label>
                    <select id="nv-responsabilidad" required class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                        <option value="">Seleccionar...</option>
                        @foreach($responsabilidadesNaviera as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach
                    </select>
                </div>
                <div><label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Nombre</label><input type="text" id="nv-nombre" class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400"></div>
                <div><label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Correo Electrónico</label><input type="email" id="nv-correo_electronico" class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400"></div>
                <div><label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Teléfono</label><input type="text" id="nv-telefono" class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400"></div>
                <div class="col-span-2"><label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Comentarios</label><textarea id="nv-comentarios" rows="3" class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400 resize-none"></textarea></div>
            </div>
            <div class="flex justify-end gap-2 mt-6 pt-4 border-t border-slate-100">
                <button type="button" onclick="cerrarModalNaviera()" class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition">Cancelar</button>
                <button type="submit" id="btn-guardar-nv" class="px-5 py-2.5 text-sm font-bold text-white rounded-xl transition shadow-md hover:shadow-lg hover:-translate-y-0.5" style="background-color:#1a5276;">Guardar</button>
            </div>
        </form>
    </div>
</div>

{{-- MODAL: Arrastres --}}
<div id="modal-arrastre" class="hidden fixed inset-0 z-50 flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="cerrarModalArrastre()"></div>
    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden">
        <div class="px-6 py-4 flex items-center justify-between" style="background-color:#1a5276;">
            <h3 id="modal-titulo-ar" class="font-bold text-white text-lg">Agregar Arrastre</h3>
            <button onclick="cerrarModalArrastre()" class="text-white/70 hover:text-white transition p-1"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <form id="form-arrastre" class="p-6 overflow-y-auto max-h-[80vh]" onsubmit="guardarArrastre(event)">
            <input type="hidden" id="arrastre-id" value="">
            <div class="grid grid-cols-2 gap-4">
                <div><label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Cliente</label><select id="ar-cliente" class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white"><option value="">— Sin cliente —</option>@foreach($clientes as $cl)@if(in_array(mb_strtolower($cl),$misClientes))<option value="{{ $cl }}">{{ $cl }}</option>@endif @endforeach</select></div>
                <div><label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Aduana</label><select id="ar-aduana" class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white"><option value="">— Sin aduana —</option>@foreach($aduanas as $a)<option value="{{ $a->aduana.$a->seccion }}">{{ $a->aduana.$a->seccion }} - {{ $a->denominacion }}</option>@endforeach</select></div>
                <div><label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Razón Social</label><input type="text" id="ar-razon_social" class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400"></div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Calificación (1-5)</label>
                    <select id="ar-calificacion" class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                        <option value="">Sin calificación</option>
                        @for($i=1;$i<=5;$i++)<option value="{{ $i }}">{{ $i }} — {{ str_repeat('★',$i) }}{{ str_repeat('☆',5-$i) }}</option>@endfor
                    </select>
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Responsabilidad en la Operación <span class="text-red-500">*</span></label>
                    <select id="ar-responsabilidad" required class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                        <option value="">Seleccionar...</option>
                        @foreach($responsabilidadesArrastre as $r)<option value="{{ $r }}">{{ $r }}</option>@endforeach
                    </select>
                </div>
                <div><label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Nombre</label><input type="text" id="ar-nombre" class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400"></div>
                <div><label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Correo Electrónico</label><input type="email" id="ar-correo_electronico" class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400"></div>
                <div><label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Teléfono</label><input type="text" id="ar-telefono" class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400"></div>
                <div class="col-span-2"><label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1">Comentarios</label><textarea id="ar-comentarios" rows="3" class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2.5 focus:outline-none focus:ring-2 focus:ring-amber-400 resize-none"></textarea></div>
            </div>
            <div class="flex justify-end gap-2 mt-6 pt-4 border-t border-slate-100">
                <button type="button" onclick="cerrarModalArrastre()" class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition">Cancelar</button>
                <button type="submit" id="btn-guardar-ar" class="px-5 py-2.5 text-sm font-bold text-white rounded-xl transition shadow-md hover:shadow-lg hover:-translate-y-0.5" style="background-color:#1a5276;">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
const TABS = ['agentes', 'forwarders', 'navieras', 'arrastres'];

function cambiarTab(tab) {
    TABS.forEach(t => {
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
}

// ── SEARCH ──────────────────────────────────────────────
const MIS_CLIENTES = JSON.parse(document.getElementById('mis-clientes-data').dataset.clientes);

function getFiltroGlobal() {
    return document.getElementById('filtro-cliente-global').value;
}

function aplicarFiltro(rows, q) {
    const cf = getFiltroGlobal();
    const grupos = {};
    rows.forEach(r => {
        const g = r.dataset.grupo;
        if (!grupos[g]) grupos[g] = [];
        grupos[g].push(r);
    });
    Object.values(grupos).forEach(gRows => {
        const esPropio = gRows.some(r => r.dataset.propio === '1');
        const matchQ   = !q || gRows.some(r => r.dataset.search.includes(q));
        const visible  = cf
            ? matchQ && gRows.some(r => r.dataset.cliente === cf)
            : esPropio && matchQ;
        gRows.forEach(r => r.style.display = visible ? '' : 'none');
    });
}

function filtrarAgentes() {
    aplicarFiltro(document.querySelectorAll('#tbody-agentes tr.group-row'),
        document.getElementById('buscar-agentes').value.toLowerCase().trim());
}

function filtrarTodo() {
    filtrarAgentes();
    filtrarForwarders();
    filtrarNavieras();
    filtrarArrastres();
}

function limpiarFiltroGlobal() {
    document.getElementById('filtro-cliente-global').value = '';
    filtrarTodo();
}

// ── MODAL ────────────────────────────────────────────────
function abrirModalAgente() {
    document.getElementById('modal-titulo').textContent = 'Agregar Agente Aduanal';
    document.getElementById('form-agente').reset();
    document.getElementById('agente-id').value = '';
    document.getElementById('modal-agente').classList.remove('hidden');
}

function editarAgente(btn) {
    const data = JSON.parse(btn.dataset.record);
    document.getElementById('modal-titulo').textContent = 'Editar Registro';
    document.getElementById('agente-id').value             = btn.dataset.id;
    document.getElementById('f-cliente').value             = data.cliente             || '';
    document.getElementById('f-aduana').value              = data.aduana              || '';
    document.getElementById('f-agente_aduanal').value      = data.agente_aduanal      || '';
    document.getElementById('f-razon_social').value        = data.razon_social        || '';
    document.getElementById('f-patente').value             = data.patente             || '';
    document.getElementById('f-calificacion').value        = data.calificacion        || '';
    document.getElementById('f-responsabilidad').value     = data.responsabilidad     || '';
    document.getElementById('f-nombre').value              = data.nombre              || '';
    document.getElementById('f-correo_electronico').value  = data.correo_electronico  || '';
    document.getElementById('f-telefono').value            = data.telefono            || '';
    document.getElementById('f-comentarios').value         = data.comentarios         || '';
    document.getElementById('modal-agente').classList.remove('hidden');
}

function cerrarModalAgente() {
    document.getElementById('modal-agente').classList.add('hidden');
}

// ── CRUD ─────────────────────────────────────────────────
async function guardarAgente(e) {
    e.preventDefault();
    const id          = document.getElementById('agente-id').value;
    const csrfToken   = document.querySelector('meta[name="csrf-token"]').content;
    const btn         = document.getElementById('btn-guardar-agente');

    const payload = {
        cliente:            document.getElementById('f-cliente').value,
        aduana:             document.getElementById('f-aduana').value,
        agente_aduanal:     document.getElementById('f-agente_aduanal').value,
        razon_social:       document.getElementById('f-razon_social').value,
        patente:            document.getElementById('f-patente').value,
        calificacion:       document.getElementById('f-calificacion').value || null,
        responsabilidad:    document.getElementById('f-responsabilidad').value,
        nombre:             document.getElementById('f-nombre').value,
        correo_electronico: document.getElementById('f-correo_electronico').value,
        telefono:           document.getElementById('f-telefono').value,
        comentarios:        document.getElementById('f-comentarios').value,
    };

    btn.disabled    = true;
    btn.textContent = 'Guardando...';

    const url    = id ? `/logistica/matriz-apoyo/agentes/${id}` : '/logistica/matriz-apoyo/agentes';
    const method = id ? 'PUT' : 'POST';

    try {
        const res  = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept':       'application/json',
                'X-CSRF-TOKEN': csrfToken,
            },
            body: JSON.stringify(payload),
        });
        const json = await res.json();
        if (json.success) {
            cerrarModalAgente();
            window.location.reload();
        } else {
            alert('Error al guardar el registro.');
        }
    } catch {
        alert('Error de conexión.');
    } finally {
        btn.disabled    = false;
        btn.textContent = 'Guardar';
    }
}

async function eliminarAgente(id) {
    if (!confirm('¿Eliminar este registro?')) return;
    id = String(id);
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    try {
        const res  = await fetch(`/logistica/matriz-apoyo/agentes/${id}`, {
            method:  'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        });
        const json = await res.json();
        if (json.success) window.location.reload();
        else alert('Error al eliminar.');
    } catch {
        alert('Error de conexión.');
    }
}

// ── FORWARDERS ────────────────────────────────────────────

function filtrarForwarders() {
    aplicarFiltro(document.querySelectorAll('#tbody-forwarders tr.fw-row'),
        document.getElementById('buscar-forwarders').value.toLowerCase().trim());
}

function abrirModalForwarder() {
    document.getElementById('modal-titulo-fw').textContent = 'Agregar Forwarder';
    document.getElementById('form-forwarder').reset();
    document.getElementById('forwarder-id').value = '';
    document.getElementById('modal-forwarder').classList.remove('hidden');
}

function editarForwarder(btn) {
    const data = JSON.parse(btn.dataset.record);
    document.getElementById('modal-titulo-fw').textContent = 'Editar Registro';
    document.getElementById('forwarder-id').value             = btn.dataset.id;
    document.getElementById('fw-cliente').value              = data.cliente              || '';
    document.getElementById('fw-aduana').value               = data.aduana               || '';
    document.getElementById('fw-razon_social').value         = data.razon_social         || '';
    document.getElementById('fw-calificacion').value         = data.calificacion         || '';
    document.getElementById('fw-responsabilidad').value      = data.responsabilidad      || '';
    document.getElementById('fw-nombre').value               = data.nombre               || '';
    document.getElementById('fw-correo_electronico').value   = data.correo_electronico   || '';
    document.getElementById('fw-telefono').value             = data.telefono             || '';
    document.getElementById('fw-comentarios').value          = data.comentarios          || '';
    document.getElementById('modal-forwarder').classList.remove('hidden');
}

function cerrarModalForwarder() {
    document.getElementById('modal-forwarder').classList.add('hidden');
}

async function guardarForwarder(e) {
    e.preventDefault();
    const id        = document.getElementById('forwarder-id').value;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const btn       = document.getElementById('btn-guardar-fw');

    const payload = {
        cliente:            document.getElementById('fw-cliente').value,
        aduana:             document.getElementById('fw-aduana').value,
        razon_social:       document.getElementById('fw-razon_social').value,
        calificacion:       document.getElementById('fw-calificacion').value || null,
        responsabilidad:    document.getElementById('fw-responsabilidad').value,
        nombre:             document.getElementById('fw-nombre').value,
        correo_electronico: document.getElementById('fw-correo_electronico').value,
        telefono:           document.getElementById('fw-telefono').value,
        comentarios:        document.getElementById('fw-comentarios').value,
    };

    btn.disabled    = true;
    btn.textContent = 'Guardando...';

    const url    = id ? `/logistica/matriz-apoyo/forwarders/${id}` : '/logistica/matriz-apoyo/forwarders';
    const method = id ? 'PUT' : 'POST';

    try {
        const res  = await fetch(url, {
            method,
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
            body: JSON.stringify(payload),
        });
        const json = await res.json();
        if (json.success) { cerrarModalForwarder(); window.location.reload(); }
        else alert('Error al guardar el registro.');
    } catch {
        alert('Error de conexión.');
    } finally {
        btn.disabled    = false;
        btn.textContent = 'Guardar';
    }
}

async function eliminarForwarder(id) {
    if (!confirm('¿Eliminar este registro?')) return;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    try {
        const res  = await fetch(`/logistica/matriz-apoyo/forwarders/${id}`, {
            method:  'DELETE',
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken },
        });
        const json = await res.json();
        if (json.success) window.location.reload();
        else alert('Error al eliminar.');
    } catch {
        alert('Error de conexión.');
    }
}

// ── NAVIERAS ─────────────────────────────────────────────

function filtrarNavieras() {
    aplicarFiltro(document.querySelectorAll('#tbody-navieras tr.nv-row'),
        document.getElementById('buscar-navieras').value.toLowerCase().trim());
}
function abrirModalNaviera() {
    document.getElementById('modal-titulo-nv').textContent = 'Agregar Naviera';
    document.getElementById('form-naviera').reset();
    document.getElementById('naviera-id').value = '';
    document.getElementById('modal-naviera').classList.remove('hidden');
}
function editarNaviera(btn) {
    const data = JSON.parse(btn.dataset.record);
    document.getElementById('modal-titulo-nv').textContent = 'Editar Registro';
    document.getElementById('naviera-id').value             = btn.dataset.id;
    document.getElementById('nv-cliente').value            = data.cliente            || '';
    document.getElementById('nv-aduana').value             = data.aduana             || '';
    document.getElementById('nv-razon_social').value       = data.razon_social       || '';
    document.getElementById('nv-calificacion').value       = data.calificacion       || '';
    document.getElementById('nv-responsabilidad').value    = data.responsabilidad    || '';
    document.getElementById('nv-nombre').value             = data.nombre             || '';
    document.getElementById('nv-correo_electronico').value = data.correo_electronico || '';
    document.getElementById('nv-telefono').value           = data.telefono           || '';
    document.getElementById('nv-comentarios').value        = data.comentarios        || '';
    document.getElementById('modal-naviera').classList.remove('hidden');
}
function cerrarModalNaviera() { document.getElementById('modal-naviera').classList.add('hidden'); }
async function guardarNaviera(e) {
    e.preventDefault();
    const id = document.getElementById('naviera-id').value;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const btn = document.getElementById('btn-guardar-nv');
    const payload = {
        cliente: document.getElementById('nv-cliente').value,
        aduana: document.getElementById('nv-aduana').value,
        razon_social: document.getElementById('nv-razon_social').value,
        calificacion: document.getElementById('nv-calificacion').value || null,
        responsabilidad: document.getElementById('nv-responsabilidad').value,
        nombre: document.getElementById('nv-nombre').value,
        correo_electronico: document.getElementById('nv-correo_electronico').value,
        telefono: document.getElementById('nv-telefono').value,
        comentarios: document.getElementById('nv-comentarios').value,
    };
    btn.disabled = true; btn.textContent = 'Guardando...';
    const url = id ? `/logistica/matriz-apoyo/navieras/${id}` : '/logistica/matriz-apoyo/navieras';
    try {
        const res = await fetch(url, { method: id ? 'PUT' : 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }, body: JSON.stringify(payload) });
        const json = await res.json();
        if (json.success) { cerrarModalNaviera(); window.location.reload(); } else alert('Error al guardar.');
    } catch { alert('Error de conexión.'); } finally { btn.disabled = false; btn.textContent = 'Guardar'; }
}
async function eliminarNaviera(id) {
    if (!confirm('¿Eliminar este registro?')) return;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    try {
        const res = await fetch(`/logistica/matriz-apoyo/navieras/${id}`, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken } });
        const json = await res.json();
        if (json.success) window.location.reload(); else alert('Error al eliminar.');
    } catch { alert('Error de conexión.'); }
}

// ── ARRASTRES ─────────────────────────────────────────────

function filtrarArrastres() {
    aplicarFiltro(document.querySelectorAll('#tbody-arrastres tr.ar-row'),
        document.getElementById('buscar-arrastres').value.toLowerCase().trim());
}
function abrirModalArrastre() {
    document.getElementById('modal-titulo-ar').textContent = 'Agregar Arrastre';
    document.getElementById('form-arrastre').reset();
    document.getElementById('arrastre-id').value = '';
    document.getElementById('modal-arrastre').classList.remove('hidden');
}
function editarArrastre(btn) {
    const data = JSON.parse(btn.dataset.record);
    document.getElementById('modal-titulo-ar').textContent = 'Editar Registro';
    document.getElementById('arrastre-id').value             = btn.dataset.id;
    document.getElementById('ar-cliente').value            = data.cliente            || '';
    document.getElementById('ar-aduana').value             = data.aduana             || '';
    document.getElementById('ar-razon_social').value       = data.razon_social       || '';
    document.getElementById('ar-calificacion').value       = data.calificacion       || '';
    document.getElementById('ar-responsabilidad').value    = data.responsabilidad    || '';
    document.getElementById('ar-nombre').value             = data.nombre             || '';
    document.getElementById('ar-correo_electronico').value = data.correo_electronico || '';
    document.getElementById('ar-telefono').value           = data.telefono           || '';
    document.getElementById('ar-comentarios').value        = data.comentarios        || '';
    document.getElementById('modal-arrastre').classList.remove('hidden');
}
function cerrarModalArrastre() { document.getElementById('modal-arrastre').classList.add('hidden'); }
async function guardarArrastre(e) {
    e.preventDefault();
    const id = document.getElementById('arrastre-id').value;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    const btn = document.getElementById('btn-guardar-ar');
    const payload = {
        cliente: document.getElementById('ar-cliente').value,
        aduana: document.getElementById('ar-aduana').value,
        razon_social: document.getElementById('ar-razon_social').value,
        calificacion: document.getElementById('ar-calificacion').value || null,
        responsabilidad: document.getElementById('ar-responsabilidad').value,
        nombre: document.getElementById('ar-nombre').value,
        correo_electronico: document.getElementById('ar-correo_electronico').value,
        telefono: document.getElementById('ar-telefono').value,
        comentarios: document.getElementById('ar-comentarios').value,
    };
    btn.disabled = true; btn.textContent = 'Guardando...';
    const url = id ? `/logistica/matriz-apoyo/arrastres/${id}` : '/logistica/matriz-apoyo/arrastres';
    try {
        const res = await fetch(url, { method: id ? 'PUT' : 'POST', headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken }, body: JSON.stringify(payload) });
        const json = await res.json();
        if (json.success) { cerrarModalArrastre(); window.location.reload(); } else alert('Error al guardar.');
    } catch { alert('Error de conexión.'); } finally { btn.disabled = false; btn.textContent = 'Guardar'; }
}
document.addEventListener('DOMContentLoaded', filtrarTodo);

async function eliminarArrastre(id) {
    if (!confirm('¿Eliminar este registro?')) return;
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    try {
        const res = await fetch(`/logistica/matriz-apoyo/arrastres/${id}`, { method: 'DELETE', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrfToken } });
        const json = await res.json();
        if (json.success) window.location.reload(); else alert('Error al eliminar.');
    } catch { alert('Error de conexión.'); }
}
</script>
@endsection
