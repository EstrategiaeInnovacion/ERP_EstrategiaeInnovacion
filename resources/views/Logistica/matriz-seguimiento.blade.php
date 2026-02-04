@extends('layouts.erp')

@section('title', 'Matriz de Seguimiento - Logística')

@push('styles')
    <style>
        /* Estilos para la tabla con cabecera fija */
        .table-container {
            max-height: 75vh;
            overflow: auto;
            position: relative;
        }
        thead th {
            position: sticky;
            top: 0;
            z-index: 20;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }
        /* La columna de acciones fija a la derecha */
        .sticky-right {
            position: sticky;
            right: 0;
            z-index: 25;
            background-color: white;
            box-shadow: -4px 0 8px -4px rgba(0,0,0,0.1);
        }
        
        /* Scrollbar personalizado sutil */
        .custom-scrollbar::-webkit-scrollbar {
            width: 6px;
            height: 6px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 3px;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb:hover {
            background: #94a3b8;
        }
    </style>
@endpush

@push('scripts')
    <script>
        // Configuración global para el JS
        window.token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        window.empleadoIdActual = {{ isset($empleadoActual) && $empleadoActual ? $empleadoActual->id : 'null' }};
        // Columnas opcionales activas para el empleado actual (para mostrar/ocultar campos en modal)
        window.columnasOpcionalesActivas = @json($columnasOpcionalesVisibles ?? []);
    </script>
    <script src="{{ asset('js/Logistica/matriz-seguimiento.js') }}?v={{ md5(time()) }}"></script>
@endpush

@section('content')
    <div class="min-h-screen bg-slate-50 pb-12">
        <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">
            
            {{-- Banner Preview (Solo visible para Admin en modo preview) --}}
            @if(isset($modoPreview) && $modoPreview && isset($empleadoPreview))
            <div class="bg-amber-100 border border-amber-200 rounded-2xl p-4 shadow-sm mb-6 flex items-center justify-between mt-6">
                <div class="flex items-center gap-3">
                    <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    <div>
                        <h3 class="font-bold text-amber-800">Modo Previsualización</h3>
                        <p class="text-amber-700 text-sm">Viendo como: <strong>{{ $empleadoPreview->nombre }}</strong></p>
                    </div>
                </div>
                <a href="{{ route('logistica.matriz-seguimiento') }}" class="text-sm bg-amber-600 text-white px-4 py-2 rounded-lg hover:bg-amber-700 transition">Salir</a>
            </div>
            @endif

            {{-- 1. ENCABEZADO Y BOTONES --}}
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pt-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Matriz de Seguimiento</h1>
                    <p class="text-slate-500 text-sm">Gestión operativa y control de tiempos logísticos.</p>
                </div>
                
                <div class="flex flex-wrap gap-3">
                    <button onclick="abrirModal()" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl font-semibold shadow-sm transition-all hover:shadow-md">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Nueva Operación
                    </button>

                    {{-- Botón Exportar preservando filtros actuales --}}
                    <a href="{{ route('logistica.reportes.export-matriz', request()->query()) }}" class="inline-flex items-center gap-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 px-5 py-2.5 rounded-xl font-medium shadow-sm transition-all">
                        <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Exportar Excel
                    </a>

                    @if(isset($esAdmin) && $esAdmin)
                    <button onclick="abrirModalCamposPersonalizados()" class="inline-flex items-center gap-2 bg-slate-800 hover:bg-slate-900 text-white px-4 py-2.5 rounded-xl font-medium shadow-sm transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        Configurar
                    </button>
                    @endif
                </div>
            </div>

            {{-- 2. FILTROS RÁPIDOS (Actualizado para Spatie Query Builder) --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                <form method="GET" action="{{ route('logistica.matriz-seguimiento') }}" class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-4 items-end">
                    
                    {{-- Preservar el ordenamiento al filtrar --}}
                    @if(request('sort'))
                        <input type="hidden" name="sort" value="{{ request('sort') }}">
                    @endif

                    {{-- Filtro Búsqueda General --}}
                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Buscar</label>
                        <div class="relative">
                            <input type="text" name="filter[search]" value="{{ request('filter.search') }}" placeholder="Folio, Cliente, Pedimento..." class="w-full pl-10 pr-4 py-2 rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </div>
                        </div>
                    </div>

                    {{-- Filtro Cliente --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Cliente</label>
                        <select name="filter[cliente]" class="w-full rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                            <option value="todos">Todos</option>
                            @foreach($clientes as $cliente)
                                <option value="{{ $cliente->id }}" {{ request('filter.cliente') == $cliente->id ? 'selected' : '' }}>
                                    {{ $cliente->razon_social ?? $cliente->cliente }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filtro Ejecutivo --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Ejecutivo</label>
                        <select name="filter[ejecutivo]" class="w-full rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                            <option value="todos">Todos</option>
                            @foreach($empleados ?? [] as $ejecutivo)
                                <option value="{{ $ejecutivo->nombre }}" {{ request('filter.ejecutivo') == $ejecutivo->nombre ? 'selected' : '' }}>
                                    {{ $ejecutivo->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filtro Status --}}
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Status</label>
                        <select name="filter[status]" class="w-full rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                            <option value="todos">Todos</option>
                            <option value="In Process" {{ request('filter.status') == 'In Process' ? 'selected' : '' }}>En Proceso</option>
                            <option value="Done" {{ request('filter.status') == 'Done' ? 'selected' : '' }}>Completado</option>
                            <option value="Out of Metric" {{ request('filter.status') == 'Out of Metric' ? 'selected' : '' }}>Fuera Métrica</option>
                        </select>
                    </div>

                    {{-- Botones de Acción --}}
                    <div class="flex gap-2">
                        <button type="submit" class="flex-1 bg-slate-800 hover:bg-slate-700 text-white font-medium py-2 px-3 rounded-xl transition-colors text-sm">
                            Filtrar
                        </button>
                        <a href="{{ route('logistica.matriz-seguimiento') }}" class="flex items-center justify-center px-3 bg-slate-100 hover:bg-slate-200 text-slate-500 rounded-xl transition-colors" title="Limpiar Filtros">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </a>
                    </div>
                </form>
            </div>

            {{-- 3. TABLA PRINCIPAL --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="table-container custom-scrollbar">
                    <table class="w-full text-sm text-left text-slate-600">
                        <thead class="bg-slate-50 text-slate-700 uppercase font-bold text-xs tracking-wider">
                            <tr>
                                {{-- COLUMNAS ORDENADAS (Predeterminadas + Opcionales en su posición) --}}
                                @foreach($columnasOrdenadas ?? [] as $colInfo)
                                    @php
                                        $esOpcional = ($colInfo['tipo'] ?? 'predeterminada') === 'opcional';
                                        $headerClass = $esOpcional 
                                            ? 'bg-slate-200 border-b border-slate-300 text-slate-600' 
                                            : 'bg-slate-50 border-b border-slate-200';
                                    @endphp
                                    <th class="px-3 py-3 {{ $headerClass }} whitespace-nowrap text-xs">
                                        {{ $colInfo['nombre'] }}
                                        @if($esOpcional)
                                            <span class="ml-1 text-slate-400 text-[9px]">●</span>
                                        @endif
                                    </th>
                                @endforeach
                                
                                {{-- Columnas Dinámicas (Campos Personalizados) --}}
                                @foreach($camposPersonalizados ?? [] as $campo)
                                    <th class="px-3 py-3 bg-indigo-50 border-b border-indigo-100 whitespace-nowrap text-indigo-700 text-xs">{{ Str::limit($campo->nombre, 15) }}</th>
                                @endforeach
                                
                                <th class="px-3 py-3 bg-slate-50 border-b border-slate-200 text-right sticky-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($operaciones as $op)
                                <tr class="hover:bg-slate-50 transition-colors group">
                                    @foreach($columnasOrdenadas ?? [] as $colInfo)
                                        @php
                                            $columna = $colInfo['columna'];
                                            $esOpcional = ($colInfo['tipo'] ?? 'predeterminada') === 'opcional';
                                            $cellClass = $esOpcional ? 'bg-slate-100/50' : '';
                                        @endphp
                                        <td class="px-3 py-2.5 text-xs {{ $cellClass }}">
                                            @switch($columna)
                                                @case('id')
                                                    <span class="font-bold text-slate-900">#{{ $op->id }}</span>
                                                    @break
                                                @case('cliente')
                                                    <div class="font-medium text-slate-800 truncate max-w-[150px]" title="{{ $op->cliente }}">
                                                        {{ Str::limit($op->cliente, 20) }}
                                                    </div>
                                                    @break
                                                @case('operacion')
                                                    <span class="inline-flex px-2 py-0.5 rounded text-[10px] font-semibold {{ $op->operacion == 'IMPORTACION' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
                                                        {{ substr($op->operacion, 0, 3) }}
                                                        </span>
                                                        @break
                                                    @case('tipo_operacion_enum')
                                                        <span class="text-slate-500">{{ $op->tipo_operacion_enum ?? '--' }}</span>
                                                        @break
                                                    @case('status')
                                                        @php
                                                            $status = $op->status_manual ?: $op->status_calculado;
                                                            $colorClass = match($status) {
                                                                'Done' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                                                'Out of Metric' => 'bg-rose-100 text-rose-700 border-rose-200',
                                                                default => 'bg-amber-100 text-amber-700 border-amber-200'
                                                            };
                                                        @endphp
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium border {{ $colorClass }}">
                                                            {{ $status ?? 'In Process' }}
                                                        </span>
                                                        @break
                                                    @case('ejecutivo')
                                                        <div class="flex items-center gap-1">
                                                            <div class="w-5 h-5 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-[9px] font-bold">
                                                                {{ substr($op->ejecutivo ?? 'U', 0, 1) }}
                                                            </div>
                                                            <span class="truncate max-w-[80px]">{{ Str::limit($op->ejecutivo, 12) ?? 'N/A' }}</span>
                                                        </div>
                                                        @break
                                                    @case('fecha_embarque')
                                                    @case('fecha_arribo_aduana')
                                                    @case('fecha_modulacion')
                                                    @case('fecha_arribo_planta')
                                                    @case('fecha_etd')
                                                    @case('fecha_zarpe')
                                                        {{ $op->$columna ? $op->$columna->format('d/m/y') : '--' }}
                                                        @break
                                                    @case('post_operaciones')
                                                        @php
                                                            $totalPost = $op->postOperaciones->count();
                                                            $completas = $op->postOperaciones->where('status', 'Completado')->count();
                                                            $porcentaje = $totalPost > 0 ? ($completas / $totalPost) * 100 : 0;
                                                            $barColor = $porcentaje == 100 ? 'bg-emerald-500' : ($porcentaje > 0 ? 'bg-blue-500' : 'bg-slate-300');
                                                        @endphp
                                                        <button onclick="verPostOperaciones({{ $op->id }})" class="group w-full max-w-[80px]" title="Gestionar Checklist">
                                                            <div class="flex justify-between text-[9px] font-bold text-slate-600 mb-0.5">
                                                                <span>{{ $completas }}/{{ $totalPost }}</span>
                                                            </div>
                                                            <div class="w-full bg-slate-200 rounded-full h-1.5 overflow-hidden">
                                                                <div class="{{ $barColor }} h-1.5 rounded-full" style="width: {{ $porcentaje }}%"></div>
                                                            </div>
                                                        </button>
                                                        @break
                                                    @case('pedimento_en_carpeta')
                                                        @if($op->pedimento_en_carpeta)
                                                            <span class="text-emerald-600">✓</span>
                                                        @else
                                                            <span class="text-slate-400">○</span>
                                                        @endif
                                                        @break
                                                    @case('resultado')
                                                    @case('target')
                                                    @case('dias_transito')
                                                        <span class="font-mono">{{ $op->$columna ?? '--' }}</span>
                                                        @break
                                                    @case('comentarios')
                                                        <button onclick="verComentarios({{ $op->id }})" class="text-slate-400 hover:text-blue-600" title="{{ $op->comentarios }}">
                                                            @if($op->comentarios)
                                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M2 5a2 2 0 012-2h12a2 2 0 012 2v10a2 2 0 01-2 2H6l-4 4V5z"></path></svg>
                                                            @else
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                                                            @endif
                                                        </button>
                                                        @break
                                                    @default
                                                        <span class="truncate max-w-[100px] block" title="{{ $op->$columna }}">{{ Str::limit($op->$columna, 15) ?? '--' }}</span>
                                                @endswitch
                                            </td>
                                    @endforeach

                                    {{-- Campos Personalizados --}}
                                    @foreach($camposPersonalizados ?? [] as $campo)
                                        <td class="px-3 py-2.5 text-xs bg-indigo-50/30">
                                            @include('Logistica.partials.campo-personalizado-celda', ['operacion' => $op, 'campo' => $campo])
                                        </td>
                                    @endforeach

                                    {{-- Acciones --}}
                                    <td class="px-3 py-2.5 text-right sticky-right group-hover:bg-slate-50">
                                        <div class="flex justify-end gap-1">
                                            <button onclick="verHistorial({{ $op->id }})" class="p-1 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Historial">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            </button>
                                            <button onclick="editarOperacion({{ $op->id }})" class="p-1 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Editar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="50" class="px-6 py-12 text-center text-slate-400">
                                        No se encontraron operaciones con los filtros seleccionados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{-- Paginación manteniendo filtros --}}
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                    {{ $operaciones->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- SECCIÓN DE MODALES (Mantenida intacta) --}}
    {{-- ========================================================= --}}

    <div id="modalOperacion" class="modal-overlay fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-5xl max-h-[90vh] flex flex-col">
            <div class="flex justify-between items-center p-6 border-b border-slate-100">
                <h3 class="text-xl font-bold text-slate-800" id="modalTitle">Nueva Operación</h3>
                <button onclick="cerrarModal()" class="text-slate-400 hover:text-slate-600 text-2xl">&times;</button>
            </div>
            <div class="p-6 overflow-y-auto custom-scrollbar">
                <form id="formOperacion" class="space-y-6">
                    @csrf
                    <input type="hidden" id="operacionId" name="operacion_id">
                    <input type="hidden" id="isEditing" name="_method">
                    
                    {{-- Sección 1: Información Principal --}}
                    <div class="bg-slate-50 rounded-xl p-4">
                        <h4 class="font-semibold text-slate-700 mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Información Principal
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Tipo de Operación *</label>
                                <select name="operacion" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm">
                                    <option value="IMPORTACION">Importación</option>
                                    <option value="EXPORTACION">Exportación</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Medio de Transporte</label>
                                <select name="tipo_operacion_enum" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm">
                                    <option value="">Seleccionar...</option>
                                    <option value="Terrestre">Terrestre</option>
                                    <option value="Aerea">Aérea</option>
                                    <option value="Maritima">Marítima</option>
                                    <option value="Ferrocarril">Ferrocarril</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Ejecutivo Asignado *</label>
                                <select name="ejecutivo" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm">
                                    @foreach($empleados ?? [] as $emp)
                                        <option value="{{ $emp->nombre }}">{{ $emp->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Cliente *</label>
                                <select name="cliente" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm">
                                    <option value="">Seleccionar cliente...</option>
                                    @foreach($clientes ?? [] as $cli)
                                        <option value="{{ $cli->cliente }}">{{ $cli->cliente }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Proveedor o Cliente (Destino/Origen)</label>
                                <input type="text" name="proveedor_o_cliente" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm" placeholder="Nombre del proveedor o destinatario">
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Proveedor</label>
                                <input type="text" name="proveedor" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm" placeholder="Nombre del proveedor">
                            </div>
                        </div>
                    </div>

                    {{-- Sección 2: Referencias y Documentos --}}
                    <div class="bg-blue-50 rounded-xl p-4">
                        <h4 class="font-semibold text-slate-700 mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                            Referencias y Documentos
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Referencia Cliente</label>
                                <input type="text" name="referencia_cliente" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm" placeholder="REF-001">
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Referencia Interna</label>
                                <input type="text" name="referencia_interna" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm" placeholder="INT-001">
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Clave</label>
                                <input type="text" name="clave" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm" placeholder="Clave de pedimento">
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">No. de Factura</label>
                                <input type="text" name="no_factura" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm" placeholder="FAC-12345">
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">No. Pedimento</label>
                                <input type="text" name="no_pedimento" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm" placeholder="25 XX XXXX XXXXXXX">
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Guía / BL</label>
                                <input type="text" name="guia_bl" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm" placeholder="Tracking o B/L">
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Asunto de Correo</label>
                                <input type="text" name="mail_subject" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm" placeholder="Subject email">
                            </div>
                            <div class="flex items-center pt-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="pedimento_en_carpeta" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-xs text-slate-600">Pedimento en Carpeta</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    {{-- Sección 3: Agente Aduanal y Aduana --}}
                    <div class="bg-amber-50 rounded-xl p-4">
                        <h4 class="font-semibold text-slate-700 mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                            Aduana y Agente Aduanal
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Aduana</label>
                                <select name="aduana" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm">
                                    <option value="">Seleccionar...</option>
                                    @foreach($aduanas ?? [] as $aduana)
                                        <option value="{{ $aduana->aduana }}">{{ $aduana->aduana }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Agente Aduanal</label>
                                <select name="agente_aduanal" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm">
                                    <option value="">Seleccionar...</option>
                                    @foreach($agentesAduanales ?? [] as $agente)
                                        <option value="{{ $agente->agente_aduanal }}">{{ $agente->agente_aduanal }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Referencia A.A</label>
                                <input type="text" name="referencia_aa" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm" placeholder="Ref. del agente">
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Transporte</label>
                                <select name="transporte" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm">
                                    <option value="">Seleccionar...</option>
                                    @foreach($transportes ?? [] as $trans)
                                        <option value="{{ $trans->transporte }}">{{ $trans->transporte }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    {{-- Sección 4: Fechas --}}
                    <div class="bg-green-50 rounded-xl p-4">
                        <h4 class="font-semibold text-slate-700 mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                            Fechas
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Fecha ETD</label>
                                <input type="date" name="fecha_etd" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Fecha de Zarpe</label>
                                <input type="date" name="fecha_zarpe" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Fecha de Embarque</label>
                                <input type="date" name="fecha_embarque" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Fecha Arribo Aduana</label>
                                <input type="date" name="fecha_arribo_aduana" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Fecha Salida Aduana (Modulación)</label>
                                <input type="date" name="fecha_modulacion" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm">
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Fecha Arribo Planta</label>
                                <input type="date" name="fecha_arribo_planta" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm">
                            </div>
                        </div>
                    </div>

                    {{-- Sección 5: Información Adicional (Solo campos opcionales activos) --}}
                    @php
                        $columnasOpcionalesActivas = $columnasOpcionalesVisibles ?? [];
                        $tieneOpcionalesActivos = count($columnasOpcionalesActivas) > 0;
                    @endphp
                    
                    @if($tieneOpcionalesActivos)
                    <div class="bg-purple-50 rounded-xl p-4">
                        <h4 class="font-semibold text-slate-700 mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                            Información Adicional
                            <span class="text-xs text-slate-400 font-normal">(Campos personalizados)</span>
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                            @if(in_array('tipo_carga', $columnasOpcionalesActivas))
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Tipo de Carga</label>
                                <select name="tipo_carga" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm">
                                    <option value="">Seleccionar...</option>
                                    <option value="FCL">FCL (Full Container)</option>
                                    <option value="LCL">LCL (Less Container)</option>
                                    <option value="FTL">FTL (Full Truck)</option>
                                    <option value="LTL">LTL (Less Truck)</option>
                                    <option value="Bulk">Bulk / Granel</option>
                                </select>
                            </div>
                            @endif
                            @if(in_array('tipo_incoterm', $columnasOpcionalesActivas))
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Incoterm</label>
                                <select name="tipo_incoterm" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm">
                                    <option value="">Seleccionar...</option>
                                    <option value="EXW">EXW - Ex Works</option>
                                    <option value="FCA">FCA - Free Carrier</option>
                                    <option value="FAS">FAS - Free Alongside Ship</option>
                                    <option value="FOB">FOB - Free on Board</option>
                                    <option value="CFR">CFR - Cost and Freight</option>
                                    <option value="CIF">CIF - Cost, Insurance & Freight</option>
                                    <option value="CPT">CPT - Carriage Paid To</option>
                                    <option value="CIP">CIP - Carriage & Insurance Paid</option>
                                    <option value="DAP">DAP - Delivered at Place</option>
                                    <option value="DPU">DPU - Delivered at Place Unloaded</option>
                                    <option value="DDP">DDP - Delivered Duty Paid</option>
                                </select>
                            </div>
                            @endif
                            @if(in_array('puerto_salida', $columnasOpcionalesActivas))
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Puerto de Salida</label>
                                <input type="text" name="puerto_salida" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm" placeholder="Puerto/Aeropuerto origen">
                            </div>
                            @endif
                            @if(in_array('in_charge', $columnasOpcionalesActivas))
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Responsable (In Charge)</label>
                                <input type="text" name="in_charge" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm" placeholder="Nombre responsable">
                            </div>
                            @endif
                            @if(in_array('tipo_previo', $columnasOpcionalesActivas))
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Modalidad/Previo</label>
                                <select name="tipo_previo" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm">
                                    <option value="">Seleccionar...</option>
                                    <option value="Directo">Directo</option>
                                    <option value="Previo">Previo</option>
                                    <option value="Consolidado">Consolidado</option>
                                </select>
                            </div>
                            @endif
                            @if(in_array('proveedor', $columnasOpcionalesActivas))
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Proveedor</label>
                                <input type="text" name="proveedor" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm" placeholder="Nombre del proveedor">
                            </div>
                            @endif
                            @if(in_array('fecha_etd', $columnasOpcionalesActivas))
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Fecha ETD</label>
                                <input type="date" name="fecha_etd" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm">
                            </div>
                            @endif
                            @if(in_array('fecha_zarpe', $columnasOpcionalesActivas))
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Fecha de Zarpe</label>
                                <input type="date" name="fecha_zarpe" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm">
                            </div>
                            @endif
                            @if(in_array('pedimento_en_carpeta', $columnasOpcionalesActivas))
                            <div class="flex items-center pt-4">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="pedimento_en_carpeta" value="1" class="rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                                    <span class="text-xs text-slate-600">Pedimento en Carpeta</span>
                                </label>
                            </div>
                            @endif
                            @if(in_array('referencia_cliente', $columnasOpcionalesActivas))
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Referencia Cliente</label>
                                <input type="text" name="referencia_cliente" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm" placeholder="REF-001">
                            </div>
                            @endif
                            @if(in_array('mail_subject', $columnasOpcionalesActivas))
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Asunto de Correo</label>
                                <input type="text" name="mail_subject" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm" placeholder="Subject email">
                            </div>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Sección 6: Status y Comentarios --}}
                    <div class="bg-rose-50 rounded-xl p-4">
                        <h4 class="font-semibold text-slate-700 mb-3 flex items-center gap-2">
                            <svg class="w-5 h-5 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Status y Comentarios
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Status Manual (Forzado)</label>
                                <select name="status_manual" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm">
                                    <option value="">Automático (calculado)</option>
                                    <option value="In Process">En Proceso</option>
                                    <option value="Done">Completado</option>
                                    <option value="Out of Metric">Fuera de Métrica</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-xs font-medium mb-1 text-slate-600">Comentarios</label>
                                <textarea name="comentarios" rows="2" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm" placeholder="Observaciones generales..."></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end gap-3 pt-4 border-t border-slate-200">
                        <button type="button" onclick="cerrarModal()" class="px-5 py-2.5 rounded-xl border border-slate-300 hover:bg-slate-50 font-medium text-slate-600">Cancelar</button>
                        <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 text-white hover:bg-blue-700 font-medium shadow-sm">
                            <span class="flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                Guardar Operación
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="modalPostOperaciones" class="modal-overlay fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl flex flex-col max-h-[85vh]">
            <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-indigo-50/50 rounded-t-2xl">
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Tareas Post-Operación</h3>
                    <p class="text-sm text-slate-500" id="tituloPostOp">Checklist de cumplimiento</p>
                </div>
                <button onclick="cerrarModalPostOperaciones()" class="text-slate-400 hover:text-slate-600 text-2xl">&times;</button>
            </div>
            <div class="p-6 overflow-y-auto custom-scrollbar flex-1 bg-slate-50/30">
                <div id="loaderPostOp" class="hidden flex justify-center py-8">Cargando...</div>
                <div id="listaPostOperaciones" class="space-y-3"></div>
                <div id="emptyPostOp" class="hidden text-center py-8 text-slate-500">No hay tareas asignadas.</div>
            </div>
            <div class="p-4 border-t bg-white rounded-b-2xl flex justify-end">
                <button onclick="guardarCambiosPostOp()" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl font-medium shadow-sm transition-all">Guardar Cambios</button>
            </div>
        </div>
    </div>

    @if(isset($esAdmin) && $esAdmin)
    <div id="modalCamposPersonalizados" class="modal-overlay fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-6xl h-[90vh] flex flex-col">
            <div class="p-6 border-b flex justify-between items-center bg-slate-800 text-white rounded-t-2xl">
                <div>
                    <h3 class="font-bold text-xl">Configuración del Sistema</h3>
                    <p class="text-slate-300 text-sm">Gestiona columnas opcionales por ejecutivo y tareas estándar.</p>
                </div>
                <button onclick="cerrarModalCamposPersonalizados()" class="text-slate-400 hover:text-white text-2xl">&times;</button>
            </div>
            <div class="flex-1 overflow-hidden flex flex-col md:flex-row">
                {{-- Panel Izquierdo: Columnas Opcionales por Ejecutivo --}}
                <div class="w-full md:w-2/3 p-6 border-r border-slate-200 overflow-y-auto">
                    <h4 class="font-bold text-lg text-slate-800 mb-2">Columnas Extra por Ejecutivo</h4>
                    <p class="text-sm text-slate-500 mb-4">Configura qué campos opcionales se muestran para cada ejecutivo en la matriz y el formulario.</p>
                    
                    {{-- Selector de Ejecutivo --}}
                    <div class="bg-blue-50 rounded-xl p-4 mb-4">
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Seleccionar Ejecutivo</label>
                        <select id="selectorEjecutivoConfig" class="w-full rounded-lg border-slate-300 focus:border-blue-500 text-sm py-2" onchange="cargarConfiguracionEjecutivo(this.value)">
                            <option value="">-- Selecciona un ejecutivo --</option>
                            @foreach($empleados ?? [] as $emp)
                                <option value="{{ $emp->id }}">{{ $emp->nombre }} - {{ $emp->posicion }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Lista de Columnas Opcionales --}}
                    <div id="contenedorColumnasOpcionales" class="hidden">
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4">
                            <p class="text-sm text-amber-800">
                                <strong>Nota:</strong> Las columnas opcionales aparecerán en <span class="bg-slate-200 px-1 rounded">color gris</span> en la matriz para diferenciarse de las predeterminadas.
                            </p>
                        </div>
                        
                        <div id="listaColumnasOpcionales" class="space-y-3">
                            {{-- Se llena dinámicamente por JS --}}
                        </div>
                        
                        <div class="mt-4 flex justify-end">
                            <button onclick="guardarConfiguracionColumnas()" class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg text-sm font-medium">
                                Guardar Configuración
                            </button>
                        </div>
                    </div>
                    
                    <div id="mensajeSeleccionarEjecutivo" class="text-center py-12 text-slate-400">
                        <svg class="w-16 h-16 mx-auto mb-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Selecciona un ejecutivo para configurar sus columnas
                    </div>
                </div>
                
                {{-- Panel Derecho: Checklist Estándar --}}
                <div class="w-full md:w-1/3 p-6 overflow-y-auto bg-slate-50">
                    <h4 class="font-bold text-lg text-slate-800 mb-2">Checklist Estándar</h4>
                    <p class="text-sm text-slate-500 mb-4">Tareas automáticas para nuevas operaciones.</p>
                    <form id="formNuevaPlantilla" class="flex gap-2 mb-4">
                        <input type="text" id="newPlantillaNombre" class="flex-1 rounded-lg border-slate-300 text-sm" placeholder="Nueva tarea...">
                        <button type="submit" class="bg-green-600 text-white px-3 py-2 rounded-lg text-sm hover:bg-green-700">+</button>
                    </form>
                    <div id="listaPlantillasConfig" class="space-y-2 bg-white p-4 rounded-xl border border-slate-200"></div>
                </div>
            </div>
            <div class="p-4 border-t bg-slate-50 rounded-b-2xl text-right">
                <button onclick="cerrarModalCamposPersonalizados()" class="px-4 py-2 border border-slate-300 rounded-lg text-slate-600 hover:bg-white">Cerrar</button>
            </div>
        </div>
    </div>
    @endif

    <div id="modalHistorial" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"><div class="bg-white rounded-2xl p-6 w-full max-w-3xl max-h-[80vh] overflow-auto"><div class="flex justify-between mb-4"><h3 class="font-bold text-lg">Historial</h3><button onclick="cerrarModalHistorial()" class="text-2xl">&times;</button></div><div id="historialContent"></div></div></div>
    
    <div id="modalComentarios" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4"><div class="bg-white rounded-2xl p-6 w-full max-w-2xl"><div class="flex justify-between mb-4"><h3 class="font-bold text-lg">Comentarios</h3><button onclick="cerrarModalComentarios()" class="text-2xl">&times;</button></div><div id="listaComentarios" class="max-h-60 overflow-auto mb-4"></div><form id="formComentario"><textarea id="nuevoComentario" class="w-full border rounded mb-2" rows="2"></textarea><button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded">Enviar</button></form></div></div>

@endsection