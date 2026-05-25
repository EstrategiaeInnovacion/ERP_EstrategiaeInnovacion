@extends('layouts.erp')
@section('title', 'Matriz de Seguimiento')

@section('content')
<div class="min-h-screen bg-slate-50 pb-12">
    <div class="max-w-[1600px] mx-auto sm:px-6 lg:px-8 space-y-6">

        {{-- HEADER --}}
        <div class="bg-white border-b border-slate-200 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                        <a href="{{ route('logistica.index') }}" class="hover:text-emerald-600 transition-colors">Panel Logística</a>
                        <span>/</span>
                        <span class="text-slate-700 font-medium">Matriz de Seguimiento</span>
                    </div>
                    <h1 class="text-2xl font-bold text-slate-900">Matriz de Seguimiento</h1>
                    <p class="text-slate-500 mt-1 text-sm">Seguimiento y control del estado de operaciones de comercio exterior por cliente.</p>
                </div>
                <div class="flex items-center gap-3">
                    <input type="text" id="buscar-seguimiento"
                           placeholder="Buscar en la tabla..."
                           oninput="filtrarSeguimiento()"
                           class="text-sm border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-emerald-400 w-56">
                    <button onclick="abrirModal()"
                            class="flex items-center gap-1.5 px-4 py-2 text-white font-bold text-sm rounded-xl transition shadow-md hover:shadow-lg hover:-translate-y-0.5"
                            style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nuevo Registro
                    </button>
                </div>
            </div>
        </div>

        {{-- FILTER BAR --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm px-5 py-4">
            @if($esCoordinador)
            {{-- Coordinador: filtro server-side por cliente o ejecutivo --}}
            <form method="GET" action="{{ route('logistica.matriz-seguimiento') }}" class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1.5">Filtrar por cliente operación</label>
                    <select name="filtro_cliente"
                            class="border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 bg-white min-w-[180px]">
                        <option value="">— Todos los clientes —</option>
                        @foreach($todosClientes as $c)
                            <option value="{{ $c }}" {{ $filtroCliente === $c ? 'selected' : '' }}>{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1.5">Filtrar por ejecutivo</label>
                    <select name="filtro_ejecutivo"
                            class="border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 bg-white min-w-[180px]">
                        <option value="">— Solo mis operaciones —</option>
                        @foreach($ejecutivos as $ej)
                            <option value="{{ $ej->user_id }}" {{ $filtroEjecutivo == $ej->user_id ? 'selected' : '' }}>{{ $ej->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit"
                        class="px-4 py-2 rounded-xl text-white font-bold text-sm transition shadow-sm hover:shadow-md"
                        style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">Aplicar filtro</button>
                @if($filtroCliente || $filtroEjecutivo)
                    <a href="{{ route('logistica.matriz-seguimiento') }}"
                       class="px-4 py-2 rounded-xl border border-slate-200 text-slate-600 font-semibold text-sm hover:bg-slate-50 transition">Limpiar</a>
                    <span class="text-xs text-slate-500 self-center">
                        Mostrando resultados filtrados
                        @if($filtroCliente) &mdash; cliente: <strong>{{ $filtroCliente }}</strong>@endif
                        @if($filtroEjecutivo) &mdash; ejecutivo: <strong>{{ $ejecutivos->firstWhere('user_id', $filtroEjecutivo)?->nombre ?? 'ID '.$filtroEjecutivo }}</strong>@endif
                    </span>
                @endif
            </form>
            @else
            {{-- Ejecutivo: filtro client-side por mis clientes --}}
            <div class="flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-bold text-slate-500 mb-1.5">Cliente Operación</label>
                    <select id="filtro-mis-clientes" onchange="filtrarPorCliente()"
                            class="border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 bg-white min-w-[200px]">
                        <option value="">— Todos —</option>
                        @foreach($misClientesFiltro as $c)
                            <option value="{{ mb_strtolower($c, 'UTF-8') }}">{{ $c }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endif
        </div>

        {{-- TABLE --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between">
                <div>
                    <h3 class="font-bold text-slate-800 text-lg">Operaciones Activas</h3>
                    <p class="text-xs text-slate-400 mt-0.5">{{ $registros->count() }} registro(s)</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm" style="min-width: 1800px;">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-3 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Ref. Interna</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Proveedor / Cliente</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Factura</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">IMP / EX</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">T. Operación</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Transporte</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Aduana</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Clave</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Pedimento</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">BL / Guía</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">ETD</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">ETA</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Cita de Previo</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Cita de Despacho</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Fecha de Arribo a Planta</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Status</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Resultado</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Target</th>
                            <th class="px-3 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Comentarios</th>
                            <th class="px-3 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Indicadores</th>
                            <th class="px-3 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-seguimiento" class="divide-y divide-slate-100">
                        @forelse($registros as $reg)
                        @php
                            $searchStr = mb_strtolower(implode(' ', array_filter([
                                $reg->ref_interna, $reg->proveedor_cliente, $reg->cliente_operacion, $reg->factura,
                                $reg->impo_ex, $reg->tipo_operacion, $reg->transporte,
                                $reg->aduana, $reg->clave, $reg->pedimento, $reg->bl_guia,
                                $reg->status, $reg->resultado, $reg->target, $reg->comentarios,
                                $reg->user?->name,
                            ])));
                            $esMia = $esCoordinador && ($reg->user_id === $miUserId);

                            // Indicador de días libres (demurrage) para mostrar en la fila
                            $demurrageDotColor = null;
                            $demurrageDotTitle = null;
                            $diasLibresPre = $reg->dias_libres ?? 20;
                            if ($reg->eta) {
                                $drPre = \Carbon\Carbon::today()->diffInDays($reg->eta->copy()->addDays($diasLibresPre), false);
                                $demurrageDotTitle = $drPre < 0 ? 'Días libres: Vencido' : 'Días libres restantes: ' . $drPre . 'd';
                                if ($drPre < 0)       $demurrageDotColor = 'bg-red-900';
                                elseif ($drPre < 5)   $demurrageDotColor = 'bg-red-500';
                                elseif ($drPre < 10)  $demurrageDotColor = 'bg-orange-500';
                                elseif ($drPre < 15)  $demurrageDotColor = 'bg-yellow-400';
                                else                   $demurrageDotColor = 'bg-emerald-500';
                            }
                        @endphp
                        <tr class="seg-row transition-colors {{ $esMia ? 'bg-emerald-50/50 hover:bg-emerald-50 border-l-2 border-l-emerald-400' : 'hover:bg-slate-50' }}"
                            data-row-id="{{ $reg->id }}"
                            data-search="{{ $searchStr }}">
                            <td class="px-3 py-3 text-slate-700 whitespace-nowrap font-mono text-xs">
                                <div class="flex items-center gap-1.5">
                                    @if($demurrageDotColor)
                                        <span class="inline-block w-2.5 h-2.5 rounded-full flex-shrink-0 {{ $demurrageDotColor }}" title="{{ $demurrageDotTitle }}"></span>
                                    @endif
                                    @if($esMia)
                                        <span title="Mi operación" class="inline-flex items-center justify-center w-4 h-4 rounded-full bg-emerald-500 flex-shrink-0">
                                            <svg class="w-2.5 h-2.5 text-white" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                                        </span>
                                    @endif
                                    {{ $reg->ref_interna ?? '—' }}
                                </div>
                            </td>
                            <td class="px-3 py-3 text-slate-800 font-semibold whitespace-nowrap">{{ $reg->cliente_operacion ?? '—' }}</td>
                            <td class="px-3 py-3 text-slate-600 whitespace-nowrap">{{ $reg->factura ?? '—' }}</td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                @if($reg->impo_ex === 'IMP')
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-700">IMP</span>
                                @elseif($reg->impo_ex === 'EX')
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-orange-100 text-orange-700">EX</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-slate-600 whitespace-nowrap">{{ $reg->tipo_operacion ?? '—' }}</td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                @if($reg->transporte)
                                    <button data-id="{{ $reg->id }}" onclick="verTransporte(Number(this.dataset.id))"
                                            class="inline-flex items-center gap-1.5 text-sm text-emerald-700 font-semibold hover:underline focus:outline-none">
                                        {{ $reg->transporte }}
                                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </button>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-slate-600 whitespace-nowrap">{{ $reg->aduana ?? '—' }}</td>
                            <td class="px-3 py-3 text-slate-600 whitespace-nowrap font-mono text-xs">{{ $reg->clave ?? '—' }}</td>
                            <td class="px-3 py-3 text-slate-600 whitespace-nowrap font-mono text-xs">{{ $reg->pedimento ?? '—' }}</td>
                            <td class="px-3 py-3 text-slate-600 whitespace-nowrap font-mono text-xs">{{ $reg->bl_guia ?? '—' }}</td>
                            <td class="px-3 py-3 text-slate-600 whitespace-nowrap text-xs">{{ $reg->etd ? $reg->etd->format('d/m/Y') : '—' }}</td>
                            <td class="px-3 py-3 text-slate-600 whitespace-nowrap text-xs">{{ $reg->eta ? $reg->eta->format('d/m/Y') : '—' }}</td>
                            <td class="px-3 py-3 text-slate-600 whitespace-nowrap text-xs">{{ $reg->previo ? $reg->previo->format('d/m/Y') : '—' }}</td>
                            <td class="px-3 py-3 text-slate-600 whitespace-nowrap text-xs">{{ $reg->cita_despacho ? $reg->cita_despacho->format('d/m/Y') : '—' }}</td>
                            <td class="px-3 py-3 text-slate-600 whitespace-nowrap text-xs">{{ $reg->arribo_planta ? $reg->arribo_planta->format('d/m/Y') : '—' }}</td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                @php
                                    $statusColors = [
                                        'Pendiente'          => 'bg-slate-100 text-slate-600',
                                        'En Tránsito'        => 'bg-blue-100 text-blue-700',
                                        'En Aduana'          => 'bg-yellow-100 text-yellow-700',
                                        'Previo Programado'  => 'bg-purple-100 text-purple-700',
                                        'Cita Programada'    => 'bg-indigo-100 text-indigo-700',
                                        'Despachado'         => 'bg-cyan-100 text-cyan-700',
                                        'Entregado'          => 'bg-emerald-100 text-emerald-700',
                                        'Cancelado'          => 'bg-red-100 text-red-600',
                                    ];
                                    $sc = $statusColors[$reg->status] ?? 'bg-slate-100 text-slate-600';
                                @endphp
                                @if($reg->status)
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $sc }}">{{ $reg->status }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                @php
                                    $resColors = [
                                        'En Proceso' => 'bg-blue-100 text-blue-700',
                                        'Exitoso'    => 'bg-emerald-100 text-emerald-700',
                                        'Demorado'   => 'bg-amber-100 text-amber-700',
                                        'Cancelado'  => 'bg-red-100 text-red-600',
                                    ];
                                    $rc = $resColors[$reg->resultado] ?? 'bg-slate-100 text-slate-600';
                                @endphp
                                @if($reg->resultado)
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $rc }}">{{ $reg->resultado }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-slate-600 whitespace-nowrap">{{ $reg->target ?? '—' }}</td>
                            <td class="px-3 py-3 text-center whitespace-nowrap">
                                <button data-id="{{ $reg->id }}" onclick="abrirComentarios(Number(this.dataset.id))"
                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition"
                                        title="Ver / agregar comentarios">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3-3-3z"/>
                                    </svg>
                                    <span class="comentarios-count-{{ $reg->id }}">{{ $reg->historial->count() }}</span>
                                </button>
                            </td>
                            {{-- INDICADORES --}}
                            @php
                                $hoy = \Carbon\Carbon::today();

                                // 1. Métrica operativa (días en aduana vs target)
                                $metricaColor = null; $metricaLabel = null; $diasEnAduana = null;
                                if ($reg->eta) {
                                    $targetDias = $reg->tipo_operacion === 'Marítimo' ? 7 : 3;
                                    $diasEnAduana = $reg->eta->diffInDays($hoy, false);
                                    $statusTerminado = in_array($reg->status, ['Despachado', 'Entregado']);
                                    if ($statusTerminado) {
                                        $metricaColor = 'bg-emerald-100 text-emerald-700';
                                        $metricaLabel = 'Completado';
                                    } elseif ($diasEnAduana > $targetDias) {
                                        $metricaColor = 'bg-red-100 text-red-700';
                                        $metricaLabel = 'Fuera de métrica';
                                    } else {
                                        $metricaColor = 'bg-yellow-100 text-yellow-700';
                                        $metricaLabel = 'En proceso';
                                    }
                                }

                                // 2. Días libres / demurrage (fijo: 20 días)
                                $demurrageColor = null; $demurrageLabel = null; $diasRestantes = null;
                                $diasLibres = $reg->dias_libres ?? 20;
                                if ($reg->eta) {
                                    $ultimoDia = $reg->eta->copy()->addDays($diasLibres);
                                    $diasRestantes = $hoy->diffInDays($ultimoDia, false);
                                    if ($diasRestantes < 0) {
                                        $demurrageColor = 'bg-red-900 text-white';
                                        $demurrageLabel = 'Vencido';
                                    } elseif ($diasRestantes < 5) {
                                        $demurrageColor = 'bg-red-100 text-red-700';
                                        $demurrageLabel = $diasRestantes . 'd restantes';
                                    } elseif ($diasRestantes < 10) {
                                        $demurrageColor = 'bg-orange-100 text-orange-700';
                                        $demurrageLabel = $diasRestantes . 'd restantes';
                                    } elseif ($diasRestantes < 15) {
                                        $demurrageColor = 'bg-yellow-100 text-yellow-700';
                                        $demurrageLabel = $diasRestantes . 'd restantes';
                                    } else {
                                        $demurrageColor = 'bg-emerald-100 text-emerald-700';
                                        $demurrageLabel = $diasRestantes . 'd restantes';
                                    }
                                }
                            @endphp
                            <td class="px-3 py-3 whitespace-nowrap text-center">
                                <div class="flex flex-col items-center gap-1">
                                    @if($metricaLabel)
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $metricaColor }}" title="Días en aduana: {{ $diasEnAduana }}d">
                                            {{ $metricaLabel }}
                                        </span>
                                    @endif
                                    @if($demurrageLabel)
                                        <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $demurrageColor }}" title="Días libres: {{ $diasLibres }}">
                                            {{ $demurrageLabel }}
                                        </span>
                                    @endif
                                    @if(!$metricaLabel && !$demurrageLabel)
                                        <span class="text-slate-300 text-xs">—</span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-1">
                                    @if($reg->status !== 'Entregado')
                                    <button data-id="{{ $reg->id }}" onclick="completarRegistro(Number(this.dataset.id))"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-600 hover:bg-emerald-50 transition"
                                            title="Completar operación">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                        </svg>
                                    </button>
                                    @endif
                                    <button data-id="{{ $reg->id }}" onclick="editarRegistro(Number(this.dataset.id))"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition"
                                            title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button data-id="{{ $reg->id }}" onclick="eliminarRegistro(Number(this.dataset.id))"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition"
                                            title="Eliminar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr id="empty-row">
                            <td colspan="21" class="px-6 py-12 text-center text-slate-400">
                                <svg class="w-10 h-10 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                                </svg>
                                No hay registros aún. Crea el primero con el botón "+ Nuevo Registro".
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- COMPLETADOS --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between cursor-pointer select-none"
                 onclick="toggleCompletados()">
                <div class="flex items-center gap-3">
                    <h3 class="font-bold text-slate-800 text-lg">Completados</h3>
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">{{ $completados->count() }}</span>
                </div>
                <svg id="completados-arrow" class="w-5 h-5 text-slate-400 transform transition-transform duration-200"
                     fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </div>
            <div id="completados-body" class="hidden overflow-x-auto">
                <table class="w-full text-sm" style="min-width: 1100px;">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="px-3 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Ref. Interna</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Proveedor / Cliente</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Factura</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">T. Operación</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">ETA</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Fecha de Arribo a Planta</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Status</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Resultado</th>
                            <th class="px-3 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Target</th>
                            <th class="px-3 py-3 text-center text-xs font-bold text-slate-500 uppercase tracking-wider whitespace-nowrap">Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-completados" class="divide-y divide-slate-100">
                        @forelse($completados as $reg)
                        @php
                            $searchStrC = mb_strtolower(implode(' ', array_filter([
                                $reg->ref_interna, $reg->proveedor_cliente, $reg->cliente_operacion, $reg->factura,
                                $reg->tipo_operacion, $reg->status, $reg->resultado, $reg->target,
                                $reg->user?->name,
                            ])));
                            $statusColors = [
                                'Entregado' => 'bg-emerald-100 text-emerald-700',
                                'Cancelado' => 'bg-red-100 text-red-600',
                            ];
                            $resColors = [
                                'En Proceso' => 'bg-blue-100 text-blue-700',
                                'Exitoso'    => 'bg-emerald-100 text-emerald-700',
                                'Demorado'   => 'bg-amber-100 text-amber-700',
                                'Cancelado'  => 'bg-red-100 text-red-600',
                            ];
                            $esMiaC = $esCoordinador && ($reg->user_id === $miUserId);
                        @endphp
                        <tr class="seg-row transition-colors {{ $esMiaC ? 'bg-emerald-50/50 hover:bg-emerald-50 border-l-2 border-l-emerald-400' : 'hover:bg-slate-50' }}"
                            data-row-id="{{ $reg->id }}"
                            data-search="{{ $searchStrC }}">
                            <td class="px-3 py-3 text-slate-700 whitespace-nowrap font-mono text-xs">{{ $reg->ref_interna ?? '—' }}</td>
                            <td class="px-3 py-3 text-slate-800 font-semibold whitespace-nowrap">{{ $reg->cliente_operacion ?? '—' }}</td>
                            <td class="px-3 py-3 text-slate-600 whitespace-nowrap">{{ $reg->factura ?? '—' }}</td>
                            <td class="px-3 py-3 text-slate-600 whitespace-nowrap">{{ $reg->tipo_operacion ?? '—' }}</td>
                            <td class="px-3 py-3 text-slate-600 whitespace-nowrap text-xs">{{ $reg->eta ? $reg->eta->format('d/m/Y') : '—' }}</td>
                            <td class="px-3 py-3 text-slate-600 whitespace-nowrap text-xs">{{ $reg->arribo_planta ? $reg->arribo_planta->format('d/m/Y') : '—' }}</td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                @if($reg->status)
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $statusColors[$reg->status] ?? 'bg-slate-100 text-slate-600' }}">{{ $reg->status }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 whitespace-nowrap">
                                @if($reg->resultado)
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold {{ $resColors[$reg->resultado] ?? 'bg-slate-100 text-slate-600' }}">{{ $reg->resultado }}</span>
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="px-3 py-3 text-slate-600 whitespace-nowrap">{{ $reg->target ?? '—' }}</td>
                            <td class="px-3 py-3 whitespace-nowrap text-center">
                                <div class="flex items-center justify-center gap-1">
                                    <button data-id="{{ $reg->id }}" onclick="abrirComentarios(Number(this.dataset.id))"
                                            class="inline-flex items-center gap-1 px-2.5 py-1.5 rounded-lg text-xs font-semibold text-slate-500 hover:text-blue-600 hover:bg-blue-50 transition"
                                            title="Ver comentarios">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3-3-3z"/>
                                        </svg>
                                        <span class="comentarios-count-{{ $reg->id }}">{{ $reg->historial->count() }}</span>
                                    </button>
                                    <button data-id="{{ $reg->id }}" onclick="editarRegistro(Number(this.dataset.id))"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-blue-600 hover:bg-blue-50 transition"
                                            title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                        </svg>
                                    </button>
                                    <button data-id="{{ $reg->id }}" onclick="eliminarRegistro(Number(this.dataset.id))"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-red-600 hover:bg-red-50 transition"
                                            title="Eliminar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                        </svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="10" class="px-6 py-8 text-center text-slate-400 text-sm">
                                Sin operaciones completadas.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

{{-- MODAL ADD/EDIT --}}
<div id="modal-seguimiento" class="fixed inset-0 z-50 hidden items-center justify-center">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="cerrarModal()"></div>
    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-4xl mx-4 max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white rounded-t-3xl border-b border-slate-100 px-8 py-5 flex items-center justify-between z-10">
            <h2 id="modal-title" class="text-xl font-bold text-slate-800">Nuevo Registro</h2>
            <button onclick="cerrarModal()" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <form id="form-seguimiento" class="px-8 py-6 space-y-6" onsubmit="submitForm(event)">
            <input type="hidden" id="registro-id" value="">

            {{-- Selector de cliente (uso interno para filtros) --}}
            <div class="bg-slate-50 rounded-2xl border border-slate-200 px-4 py-3">
                <label class="block text-xs font-bold text-slate-500 mb-1.5">Cliente <span class="font-normal text-slate-400">(uso interno — para filtrar operaciones)</span></label>
                <select id="f-proveedor_cliente"
                        class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 bg-white">
                    <option value="">— Seleccionar cliente —</option>
                    @foreach($misClientes as $cliente)
                        <option value="{{ $cliente }}">{{ $cliente }}</option>
                    @endforeach
                </select>
            </div>

            {{-- Row 1 --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">Ref. Interna</label>
                    <input type="text" id="f-ref_interna" maxlength="100"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"
                           placeholder="REF-001">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">Cliente / Proveedor</label>
                    <input type="text" id="f-cliente_operacion" maxlength="255"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"
                           placeholder="Nombre del cliente o proveedor">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">Factura</label>
                    <input type="text" id="f-factura" maxlength="100"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"
                           placeholder="No. de factura">
                </div>
            </div>

            {{-- Row 2 --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">IMP / EX</label>
                    <select id="f-impo_ex" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 bg-white">
                        <option value="">— Seleccionar —</option>
                        <option value="IMP">IMP</option>
                        <option value="EX">EX</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">T. Operación</label>
                    <select id="f-tipo_operacion" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 bg-white">
                        <option value="">— Seleccionar —</option>
                        @foreach($tiposOperacion as $tipo)
                            <option value="{{ $tipo }}">{{ $tipo }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">Transporte</label>
                    <input type="text" id="f-transporte" maxlength="255"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"
                           placeholder="Nombre del transportista">
                </div>
            </div>

            {{-- Detalles de Transporte --}}
            <div id="seccion-detalles-maritimo" class="rounded-2xl border border-slate-200 bg-slate-50 p-4 space-y-4 hidden">
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider">Detalles de Transporte <span class="font-normal text-slate-400 normal-case">(opcional)</span></p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label id="lbl-naviera" class="block text-xs font-semibold text-slate-600 mb-1.5">Naviera</label>
                        <input type="text" id="f-naviera" maxlength="255"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 bg-white"
                               placeholder="Nombre de la naviera">
                    </div>
                    <div id="campo-buque">
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Buque</label>
                        <input type="text" id="f-buque" maxlength="255"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 bg-white"
                               placeholder="Nombre del buque">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Carga FCL / LCL</label>
                        <select id="f-carga_tipo" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 bg-white">
                            <option value="">— Seleccionar —</option>
                            @foreach($cargaTipos as $ct)
                                <option value="{{ $ct }}">{{ $ct }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label id="lbl-no-contenedor" class="block text-xs font-semibold text-slate-600 mb-1.5">No. Contenedor</label>
                        <input type="text" id="f-no_contenedor" maxlength="100"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 bg-white"
                               placeholder="Número de contenedor">
                    </div>
                    <div>
                        <label id="lbl-tipo-contenedor" class="block text-xs font-semibold text-slate-600 mb-1.5">Tipo de Contenedor</label>
                        <input type="text" id="f-tipo_contenedor" maxlength="50"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 bg-white"
                               placeholder="Ej. 40' HC, 20' ST...">
                    </div>
                </div>
            </div>

            {{-- Row 3 --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">Aduana</label>
                    <select id="f-aduana" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 bg-white">
                        <option value="">— Sin aduana —</option>
                        @foreach($aduanas as $a)
                            <option value="{{ $a->aduana }}{{ $a->seccion }}">{{ $a->aduana }}{{ $a->seccion }} - {{ $a->denominacion }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">Clave</label>
                    <select id="f-clave" class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 bg-white">
                        <option value="">— Sin clave —</option>
                        @foreach($claves as $c)
                            <option value="{{ $c->clave }}">{{ $c->clave }} - {{ $c->descripcion }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">Pedimento</label>
                    <input type="text" id="f-pedimento" maxlength="100"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"
                           placeholder="No. pedimento">
                </div>
            </div>

            {{-- Row 4 --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">BL / Guía</label>
                    <input type="text" id="f-bl_guia" maxlength="100"
                           class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400"
                           placeholder="No. BL o guía">
                </div>
            </div>

            {{-- Dates Row --}}
            <div>
                <p class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3">Fechas</p>
                <div class="grid grid-cols-2 sm:grid-cols-6 gap-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">ETD</label>
                        <input type="date" id="f-etd"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">ETA</label>
                        <input type="date" id="f-eta"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Cita de Previo</label>
                        <input type="date" id="f-previo"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Cita Despacho</label>
                        <input type="date" id="f-cita_despacho"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1.5">Fecha de Arribo a Planta</label>
                        <input type="date" id="f-arribo_planta"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400">
                    </div>
                </div>
            </div>

            {{-- Días libres --}}
            <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 flex items-center gap-4">
                <div class="flex-shrink-0">
                    <label class="block text-xs font-bold text-slate-600 mb-1.5">
                        Días Libres
                        <span class="font-normal text-slate-400 ml-1">(la cuenta inicia desde ETA)</span>
                    </label>
                    <input type="number" id="f-dias_libres" min="0" max="99" value="20"
                           class="w-24 border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 bg-white text-center font-bold">
                </div>
                <p class="text-xs text-slate-400 leading-relaxed">
                    Número de días sin cargo de demurrage a partir de la fecha ETA. Varía según el embarque y la línea naviera.
                </p>
            </div>

            <input type="hidden" id="f-target" maxlength="100">

            {{-- Footer --}}
            <div class="flex justify-end gap-3 pt-2 border-t border-slate-100">
                <button type="button" onclick="cerrarModal()"
                        class="px-6 py-2.5 rounded-xl border border-slate-200 text-slate-600 font-semibold text-sm hover:bg-slate-50 transition">
                    Cancelar
                </button>
                <button type="submit" id="btn-submit"
                        class="px-6 py-2.5 rounded-xl text-white font-bold text-sm transition shadow-md hover:shadow-lg hover:-translate-y-0.5"
                        style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    Guardar
                </button>
            </div>
        </form>
    </div>
</div>
@php
$mapReg = function ($r) {
    return [
        'id'                => $r->id,
        'ref_interna'       => $r->ref_interna,
        'proveedor_cliente' => $r->proveedor_cliente,
        'cliente_operacion' => $r->cliente_operacion,
        'factura'           => $r->factura,
        'impo_ex'          => $r->impo_ex,
        'tipo_operacion'   => $r->tipo_operacion,
        'transporte'       => $r->transporte,
        'naviera'          => $r->naviera,
        'buque'            => $r->buque,
        'carga_tipo'       => $r->carga_tipo,
        'no_contenedor'    => $r->no_contenedor,
        'tipo_contenedor'  => $r->tipo_contenedor,
        'aduana'           => $r->aduana,
        'clave'            => $r->clave,
        'pedimento'        => $r->pedimento,
        'bl_guia'          => $r->bl_guia,
        'etd'              => optional($r->etd)->format('Y-m-d'),
        'eta'              => optional($r->eta)->format('Y-m-d'),
        'dias_libres'      => $r->dias_libres,
        'previo'           => optional($r->previo)->format('Y-m-d'),
        'cita_despacho'    => optional($r->cita_despacho)->format('Y-m-d'),
        'arribo_planta'    => optional($r->arribo_planta)->format('Y-m-d'),
        'status'           => $r->status,
        'resultado'        => $r->resultado,
        'target'           => $r->target,
        'comentarios'      => $r->comentarios,
        'user_id'          => $r->user_id,
        'user_name'        => $r->user?->name,
    ];
};
$registrosJs = $registros->merge($completados)->map($mapReg)->values()->toArray();
@endphp
<div id="registros-data" data-registros='@json($registrosJs)' class="hidden"></div>

{{-- MODAL DETALLE TRANSPORTE --}}
<div id="modal-transporte" class="fixed inset-0 z-50 hidden items-center justify-center">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="cerrarModalTransporte()"></div>
    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-md mx-4">
        <div class="bg-white rounded-t-3xl border-b border-slate-100 px-6 py-4 flex items-center justify-between">
            <div>
                <h2 class="text-lg font-bold text-slate-800">Detalle de Transporte</h2>
                <p id="dt-transporte-nombre" class="text-sm text-emerald-700 font-semibold mt-0.5"></p>
            </div>
            <button onclick="cerrarModalTransporte()" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        <div class="px-6 py-5 space-y-4">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <p id="dt-lbl-naviera" class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Naviera</p>
                    <p id="dt-naviera" class="text-sm font-semibold text-slate-700">—</p>
                </div>
                <div id="dt-campo-buque">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Buque</p>
                    <p id="dt-buque" class="text-sm font-semibold text-slate-700">—</p>
                </div>
                <div>
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Carga</p>
                    <p id="dt-carga_tipo" class="text-sm font-semibold text-slate-700">—</p>
                </div>
                <div>
                    <p id="dt-lbl-no-contenedor" class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">No. Contenedor</p>
                    <p id="dt-no_contenedor" class="text-sm font-semibold text-slate-700 font-mono">—</p>
                </div>
                <div class="col-span-2">
                    <p id="dt-lbl-tipo-contenedor" class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Tipo de Contenedor</p>
                    <p id="dt-tipo_contenedor" class="text-sm font-semibold text-slate-700">—</p>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- MODAL COMENTARIOS --}}
<div id="modal-comentarios" class="fixed inset-0 z-50 hidden items-center justify-center">
    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" onclick="cerrarComentarios()"></div>
    <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-lg mx-4 flex flex-col" style="max-height:85vh">
        <div class="bg-white rounded-t-3xl border-b border-slate-100 px-6 py-4 flex items-center justify-between flex-shrink-0">
            <div>
                <h2 class="text-lg font-bold text-slate-800">Historial de Comentarios</h2>
                <p id="mc-ref" class="text-sm text-emerald-700 font-semibold mt-0.5"></p>
            </div>
            <button onclick="cerrarComentarios()" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>
        {{-- Lista de comentarios --}}
        <div id="mc-lista" class="flex-1 overflow-y-auto px-6 py-4 space-y-3 min-h-[100px]">
            <p class="text-slate-400 text-sm text-center py-6">Cargando...</p>
        </div>
        {{-- Nuevo comentario --}}
        <div class="border-t border-slate-100 px-6 py-4 flex-shrink-0">
            <div class="flex gap-2">
                <textarea id="mc-texto" rows="2"
                          class="flex-1 border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-emerald-400 resize-none"
                          placeholder="Escribe un comentario..."></textarea>
                <button onclick="guardarComentario()"
                        class="self-end px-4 py-2.5 rounded-xl text-white font-bold text-sm transition shadow-md hover:shadow-lg"
                        style="background: linear-gradient(135deg, #10b981 0%, #059669 100%);">
                    Enviar
                </button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const CSRF = document.querySelector('meta[name="csrf-token"]').content;

let registros = JSON.parse(document.getElementById('registros-data').dataset.registros);

// ── Filter ───────────────────────────────────────────────────────────
function filtrarSeguimiento() {
    const q = document.getElementById('buscar-seguimiento').value.toLowerCase();
    document.querySelectorAll('#tbody-seguimiento .seg-row, #tbody-completados .seg-row').forEach(tr => {
        tr.style.display = (!q || tr.dataset.search.includes(q)) ? '' : 'none';
    });
}

function filtrarPorCliente() {
    const sel = document.getElementById('filtro-mis-clientes');
    const val = sel ? sel.value : '';
    document.querySelectorAll('#tbody-seguimiento .seg-row, #tbody-completados .seg-row').forEach(tr => {
        if (!val) { tr.style.display = ''; return; }
        const cliente = tr.querySelector('td:nth-child(2)')?.textContent.trim().toLowerCase() ?? '';
        tr.style.display = cliente.includes(val) ? '' : 'none';
    });
}

function toggleCompletados() {
    const body  = document.getElementById('completados-body');
    const arrow = document.getElementById('completados-arrow');
    body.classList.toggle('hidden');
    arrow.classList.toggle('rotate-180');
}

// ── Modal helpers ────────────────────────────────────────────────────
function setProveedorClienteValue(valor) {
    document.getElementById('f-proveedor_cliente').value = valor ?? '';
}

function abrirModal() {
    document.getElementById('modal-title').textContent = 'Nuevo Registro';
    document.getElementById('registro-id').value = '';
    document.getElementById('form-seguimiento').reset();
    toggleDetallesMaritimo('');
    document.getElementById('modal-seguimiento').classList.remove('hidden');
    document.getElementById('modal-seguimiento').classList.add('flex');
}

function cerrarModal() {
    document.getElementById('modal-seguimiento').classList.add('hidden');
    document.getElementById('modal-seguimiento').classList.remove('flex');
}

function editarRegistro(id) {
    const reg = registros.find(r => r.id === id);
    if (!reg) return;

    document.getElementById('modal-title').textContent = 'Editar Registro';
    document.getElementById('registro-id').value = id;

    setProveedorClienteValue(reg.proveedor_cliente);
    const elClienteOp = document.getElementById('f-cliente_operacion');
    if (elClienteOp) elClienteOp.value = reg.cliente_operacion ?? '';

    [
        'ref_interna','factura','impo_ex','tipo_operacion',
        'transporte','naviera','buque','carga_tipo','no_contenedor','tipo_contenedor',
        'aduana','clave','pedimento','bl_guia',
        'etd','eta','dias_libres','previo','cita_despacho','arribo_planta',
        'target','comentarios'
    ].forEach(c => {
        const el = document.getElementById('f-' + c);
        if (el) el.value = reg[c] ?? '';
    });

    toggleDetallesMaritimo(reg.tipo_operacion ?? '');
    document.getElementById('modal-seguimiento').classList.remove('hidden');
    document.getElementById('modal-seguimiento').classList.add('flex');
}

// ── Detalle Transporte ───────────────────────────────────────────────
function verTransporte(id) {
    const reg = registros.find(r => r.id === id);
    if (!reg) return;
    const set = (elId, val) => { document.getElementById(elId).textContent = val || '—'; };
    const esAereo = reg.tipo_operacion === 'Aéreo';

    document.getElementById('dt-transporte-nombre').textContent = reg.transporte || '—';

    // Naviera / Aerolínea
    document.getElementById('dt-lbl-naviera').textContent = esAereo ? 'Aerolínea' : 'Naviera';
    set('dt-naviera', reg.naviera);

    // Buque: solo marítimo
    const campoBuque = document.getElementById('dt-campo-buque');
    if (esAereo) {
        campoBuque.classList.add('hidden');
    } else {
        campoBuque.classList.remove('hidden');
        set('dt-buque', reg.buque);
    }

    // Contenedor / Caja
    document.getElementById('dt-lbl-no-contenedor').textContent = esAereo ? 'No. Caja' : 'No. Contenedor';
    document.getElementById('dt-lbl-tipo-contenedor').textContent = esAereo ? 'Tipo de Caja' : 'Tipo de Contenedor';
    set('dt-carga_tipo',     reg.carga_tipo);
    set('dt-no_contenedor',  reg.no_contenedor);
    set('dt-tipo_contenedor',reg.tipo_contenedor);

    document.getElementById('modal-transporte').classList.remove('hidden');
    document.getElementById('modal-transporte').classList.add('flex');
}
function cerrarModalTransporte() {
    document.getElementById('modal-transporte').classList.add('hidden');
    document.getElementById('modal-transporte').classList.remove('flex');
}

// ── Comentarios ──────────────────────────────────────────────────────
let comentariosRegistroId = null;

async function abrirComentarios(id) {
    const reg = registros.find(r => r.id === id);
    comentariosRegistroId = id;
    document.getElementById('mc-ref').textContent = reg?.ref_interna || ('Operación #' + id);
    document.getElementById('mc-texto').value = '';
    document.getElementById('mc-lista').innerHTML = '<p class="text-slate-400 text-sm text-center py-6">Cargando...</p>';
    document.getElementById('modal-comentarios').classList.remove('hidden');
    document.getElementById('modal-comentarios').classList.add('flex');

    try {
        const res = await fetch(`/logistica/matriz-seguimiento/${id}/comentarios`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
        });
        const data = await res.json();
        renderComentarios(data.comentarios);
    } catch {
        document.getElementById('mc-lista').innerHTML = '<p class="text-red-400 text-sm text-center py-6">Error al cargar comentarios.</p>';
    }
}

function renderComentarios(lista) {
    const el = document.getElementById('mc-lista');
    if (!lista.length) {
        el.innerHTML = '<p class="text-slate-400 text-sm text-center py-6">Sin comentarios aún.</p>';
        return;
    }
    el.innerHTML = lista.map(c => `
        <div class="bg-slate-50 rounded-2xl px-4 py-3 space-y-1">
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-emerald-700">${c.usuario}</span>
                <span class="text-xs text-slate-400">${c.fecha}</span>
            </div>
            <p class="text-sm text-slate-700 whitespace-pre-wrap">${c.comentario}</p>
        </div>
    `).join('');
}

async function guardarComentario() {
    const texto = document.getElementById('mc-texto').value.trim();
    if (!texto) return;

    try {
        const res = await fetch(`/logistica/matriz-seguimiento/${comentariosRegistroId}/comentarios`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify({ comentario: texto }),
        });
        if (!res.ok) throw new Error();
        const data = await res.json();
        document.getElementById('mc-texto').value = '';

        // Agregar al inicio de la lista
        const el = document.getElementById('mc-lista');
        const p = el.querySelector('p');
        if (p) el.innerHTML = '';
        const div = document.createElement('div');
        div.className = 'bg-slate-50 rounded-2xl px-4 py-3 space-y-1';
        div.innerHTML = `
            <div class="flex items-center justify-between">
                <span class="text-xs font-bold text-emerald-700">${data.comentario.usuario}</span>
                <span class="text-xs text-slate-400">${data.comentario.fecha}</span>
            </div>
            <p class="text-sm text-slate-700 whitespace-pre-wrap">${data.comentario.comentario}</p>
        `;
        el.prepend(div);

        // Actualizar contador en la tabla
        const span = document.querySelector(`.comentarios-count-${comentariosRegistroId}`);
        if (span) span.textContent = parseInt(span.textContent || '0') + 1;
    } catch {
        alert('Error al guardar el comentario.');
    }
}

function cerrarComentarios() {
    document.getElementById('modal-comentarios').classList.add('hidden');
    document.getElementById('modal-comentarios').classList.remove('flex');
    comentariosRegistroId = null;
}

// ── Submit ───────────────────────────────────────────────────────────
async function submitForm(e) {
    e.preventDefault();
    const id = document.getElementById('registro-id').value;
    const url = id
        ? `/logistica/matriz-seguimiento/${id}`
        : '/logistica/matriz-seguimiento';
    const method = id ? 'PUT' : 'POST';

    const g = elId => document.getElementById(elId)?.value || null;
    const body = {
        ref_interna:        g('f-ref_interna'),
        proveedor_cliente:  document.getElementById('f-proveedor_cliente')?.value || null,
        cliente_operacion:  g('f-cliente_operacion'),
        factura:            g('f-factura'),
        impo_ex:           g('f-impo_ex'),
        tipo_operacion:    g('f-tipo_operacion'),
        transporte:        g('f-transporte'),
        naviera:           g('f-naviera'),
        buque:             g('f-buque'),
        carga_tipo:        g('f-carga_tipo'),
        no_contenedor:     g('f-no_contenedor'),
        tipo_contenedor:   g('f-tipo_contenedor'),
        aduana:            g('f-aduana'),
        clave:             g('f-clave'),
        pedimento:         g('f-pedimento'),
        bl_guia:           g('f-bl_guia'),
        etd:               g('f-etd'),
        eta:               g('f-eta'),
        dias_libres:       parseInt(document.getElementById('f-dias_libres')?.value) || 20,
        previo:            g('f-previo'),
        cita_despacho:     g('f-cita_despacho'),
        arribo_planta:     g('f-arribo_planta'),
        target:            g('f-target'),
    };

    const btn = document.getElementById('btn-submit');
    btn.disabled = true;
    btn.textContent = 'Guardando...';

    try {
        const res = await fetch(url, {
            method,
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': CSRF
            },
            body: JSON.stringify(body),
        });
        if (!res.ok) {
            const err = await res.json().catch(() => ({}));
            const msg = err.message || (err.errors ? Object.values(err.errors).flat().join('\n') : 'Error al guardar');
            throw new Error(msg);
        }
        cerrarModal();
        window.location.reload();
    } catch (err) {
        alert('Error al guardar:\n' + err.message);
    } finally {
        btn.disabled = false;
        btn.textContent = 'Guardar';
    }
}

// ── Delete ───────────────────────────────────────────────────────────
async function completarRegistro(id) {
    if (!confirm('¿Marcar esta operación como completada? Se registrará el arribo a planta con la fecha de hoy.')) return;
    try {
        const res = await fetch(`/logistica/matriz-seguimiento/${id}/completar`, {
            method: 'PATCH',
            headers: { 'X-CSRF-TOKEN': CSRF },
        });
        if (!res.ok) throw new Error();
        window.location.reload();
    } catch {
        alert('No se pudo completar la operación.');
    }
}

async function eliminarRegistro(id) {
    if (!confirm('¿Eliminar este registro? Esta acción no se puede deshacer.')) return;
    try {
        const res = await fetch(`/logistica/matriz-seguimiento/${id}`, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': CSRF },
        });
        if (!res.ok) throw new Error();
        window.location.reload();
    } catch {
        alert('No se pudo eliminar el registro.');
    }
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape') { cerrarModal(); cerrarModalTransporte(); cerrarComentarios(); }
});

// ── Auto-Target por T. Operación ─────────────────────────────────────
const TARGET_POR_OPERACION = {
    'Marítimo':   '5-7 días',
    'Aéreo':      '2-3 días',
    'Terrestre':  '2-3 días',
    'Ferroviario':'2-3 días',
};

function toggleDetallesMaritimo(tipo) {
    const seccion          = document.getElementById('seccion-detalles-maritimo');
    const lblNav           = document.getElementById('lbl-naviera');
    const campoBuque       = document.getElementById('campo-buque');
    const inputNaviera     = document.getElementById('f-naviera');
    const lblNoContenedor  = document.getElementById('lbl-no-contenedor');
    const lblTipoContenedor= document.getElementById('lbl-tipo-contenedor');
    const inputNoContenedor= document.getElementById('f-no_contenedor');
    const inputTipoContenedor = document.getElementById('f-tipo_contenedor');

    if (tipo === 'Marítimo') {
        seccion.classList.remove('hidden');
        lblNav.textContent = 'Naviera';
        inputNaviera.placeholder = 'Nombre de la naviera';
        campoBuque.classList.remove('hidden');
        lblNoContenedor.textContent = 'No. Contenedor';
        inputNoContenedor.placeholder = 'Número de contenedor';
        lblTipoContenedor.textContent = 'Tipo de Contenedor';
        inputTipoContenedor.placeholder = "Ej. 40' HC, 20' ST...";
    } else if (tipo === 'Aéreo') {
        seccion.classList.remove('hidden');
        lblNav.textContent = 'Aerolínea';
        inputNaviera.placeholder = 'Nombre de la aerolínea';
        campoBuque.classList.add('hidden');
        document.getElementById('f-buque').value = '';
        lblNoContenedor.textContent = 'No. Caja';
        inputNoContenedor.placeholder = 'Número de caja';
        lblTipoContenedor.textContent = 'Tipo de Caja';
        inputTipoContenedor.placeholder = 'Tipo de caja';
    } else {
        seccion.classList.add('hidden');
    }
}

document.getElementById('f-tipo_operacion').addEventListener('change', function () {
    const target = document.getElementById('f-target');
    if (TARGET_POR_OPERACION[this.value]) {
        target.value = TARGET_POR_OPERACION[this.value];
    }
    toggleDetallesMaritimo(this.value);
});
</script>
@endpush
