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
        window.empleadoIdActual = {{ $empleadoActual ? $empleadoActual->id : 'null' }};
    </script>
    {{-- Asegúrate de que este archivo JS tenga el código actualizado que te di en el paso anterior --}}
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

            {{-- 2. FILTROS RÁPIDOS --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                <form method="GET" action="{{ route('logistica.matriz-seguimiento') }}" class="grid grid-cols-1 md:grid-cols-4 lg:grid-cols-6 gap-4 items-end">
                    <div class="col-span-1 md:col-span-2">
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Buscar</label>
                        <div class="relative">
                            <input type="text" name="search" value="{{ request('search') }}" placeholder="Folio, Cliente, Pedimento..." class="w-full pl-10 pr-4 py-2 rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500 text-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                            </div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Cliente</label>
                        <select name="cliente" class="w-full rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                            <option value="">Todos</option>
                            @foreach($clientesUnicos ?? [] as $cli)
                                <option value="{{ $cli }}" {{ request('cliente') == $cli ? 'selected' : '' }}>{{ $cli }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Status</label>
                        <select name="status" class="w-full rounded-xl border-slate-200 focus:border-blue-500 focus:ring-blue-500 text-sm py-2">
                            <option value="">Todos</option>
                            <option value="In Process" {{ request('status') == 'In Process' ? 'selected' : '' }}>En Proceso</option>
                            <option value="Done" {{ request('status') == 'Done' ? 'selected' : '' }}>Completado</option>
                            <option value="Out of Metric" {{ request('status') == 'Out of Metric' ? 'selected' : '' }}>Fuera Métrica</option>
                        </select>
                    </div>
                    <div>
                        <button type="submit" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 font-medium py-2 px-4 rounded-xl transition-colors text-sm">Filtrar</button>
                    </div>
                    <div>
                        <a href="{{ route('logistica.matriz-seguimiento') }}" class="flex items-center justify-center w-full text-slate-400 hover:text-red-500 py-2 transition-colors text-sm">Limpiar</a>
                    </div>
                </form>
            </div>

            {{-- 3. TABLA PRINCIPAL --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="table-container">
                    <table class="w-full text-sm text-left text-slate-600">
                        <thead class="bg-slate-50 text-slate-700 uppercase font-bold text-xs tracking-wider">
                            <tr>
                                <th class="px-4 py-3 bg-slate-50 border-b border-slate-200 whitespace-nowrap">Folio</th>
                                <th class="px-4 py-3 bg-slate-50 border-b border-slate-200 whitespace-nowrap min-w-[150px]">Cliente</th>
                                <th class="px-4 py-3 bg-slate-50 border-b border-slate-200 whitespace-nowrap">Operación</th>
                                <th class="px-4 py-3 bg-slate-50 border-b border-slate-200 whitespace-nowrap">Status</th>
                                <th class="px-4 py-3 bg-slate-50 border-b border-slate-200 whitespace-nowrap">Fechas (E/A)</th>
                                <th class="px-4 py-3 bg-slate-50 border-b border-slate-200 whitespace-nowrap min-w-[120px]">Pedimento</th>
                                
                                {{-- COLUMNA NUEVA: POST-OPERACIONES --}}
                                <th class="px-4 py-3 bg-slate-50 border-b border-slate-200 whitespace-nowrap text-center">Post-Op</th>
                                
                                <th class="px-4 py-3 bg-slate-50 border-b border-slate-200 whitespace-nowrap min-w-[120px]">Ejecutivo</th>
                                
                                {{-- Columnas Dinámicas --}}
                                @foreach($camposPersonalizados ?? [] as $campo)
                                    <th class="px-4 py-3 bg-indigo-50 border-b border-indigo-100 whitespace-nowrap text-indigo-700">{{ Str::limit($campo->nombre, 15) }}</th>
                                @endforeach
                                
                                <th class="px-4 py-3 bg-slate-50 border-b border-slate-200 text-right sticky-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($operaciones as $op)
                                <tr class="hover:bg-slate-50 transition-colors group">
                                    {{-- Folio --}}
                                    <td class="px-4 py-3 font-medium text-slate-900">#{{ $op->id }}</td>
                                    
                                    {{-- Cliente --}}
                                    <td class="px-4 py-3">
                                        <div class="font-medium text-slate-800 truncate max-w-[200px]" title="{{ $op->cliente }}">{{ $op->cliente }}</div>
                                        <div class="text-xs text-slate-400 truncate max-w-[200px]">{{ $op->proveedor_o_cliente }}</div>
                                    </td>

                                    {{-- Operación --}}
                                    <td class="px-4 py-3">
                                        <span class="inline-flex px-2 py-1 rounded text-xs font-semibold {{ $op->operacion == 'IMPORTACION' ? 'bg-blue-100 text-blue-700' : 'bg-green-100 text-green-700' }}">
                                            {{ substr($op->operacion, 0, 3) }}
                                        </span>
                                        <div class="text-xs text-slate-500 mt-1">{{ $op->tipo_operacion_enum }}</div>
                                    </td>

                                    {{-- Status --}}
                                    <td class="px-4 py-3">
                                        @php
                                            $colorClass = match($op->color_status) {
                                                'green' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                                'yellow' => 'bg-amber-100 text-amber-700 border-amber-200',
                                                'red' => 'bg-rose-100 text-rose-700 border-rose-200',
                                                default => 'bg-slate-100 text-slate-600 border-slate-200'
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium border {{ $colorClass }}">
                                            {{ $op->status_manual ?: $op->status_calculado }}
                                        </span>
                                    </td>

                                    {{-- Fechas --}}
                                    <td class="px-4 py-3 text-xs">
                                        <div class="flex flex-col gap-1">
                                            <span title="Embarque">📅 {{ $op->fecha_embarque ? $op->fecha_embarque->format('d/m') : '--' }}</span>
                                            <span title="Arribo" class="text-slate-400">🛬 {{ $op->fecha_arribo_aduana ? $op->fecha_arribo_aduana->format('d/m') : '--' }}</span>
                                        </div>
                                    </td>

                                    {{-- Pedimento --}}
                                    <td class="px-4 py-3 text-xs font-mono text-slate-600">{{ $op->no_pedimento ?? '--' }}</td>
                                    
                                    {{-- POST-OPERACIONES (BARRA DE PROGRESO) --}}
                                    <td class="px-4 py-3 text-center">
                                        @php
                                            $totalPost = $op->postOperaciones->count();
                                            $completas = $op->postOperaciones->where('status', 'Completado')->count();
                                            $porcentaje = $totalPost > 0 ? ($completas / $totalPost) * 100 : 0;
                                            $barColor = $porcentaje == 100 ? 'bg-emerald-500' : ($porcentaje > 0 ? 'bg-blue-500' : 'bg-slate-300');
                                        @endphp
                                        <button onclick="verPostOperaciones({{ $op->id }})" class="group relative w-full max-w-[100px] mx-auto hover:opacity-80 transition-opacity" title="Gestionar Checklist">
                                            <div class="flex justify-between text-[10px] font-bold text-slate-600 mb-1">
                                                <span>Tareas</span>
                                                <span>{{ $completas }}/{{ $totalPost }}</span>
                                            </div>
                                            <div class="w-full bg-slate-200 rounded-full h-2 overflow-hidden">
                                                <div class="{{ $barColor }} h-2 rounded-full transition-all duration-500" style="width: {{ $porcentaje }}%"></div>
                                            </div>
                                        </button>
                                    </td>

                                    {{-- Ejecutivo --}}
                                    <td class="px-4 py-3 text-xs">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-[10px] font-bold">
                                                {{ substr($op->ejecutivo ?? 'U', 0, 1) }}
                                            </div>
                                            <span class="truncate max-w-[100px]">{{ $op->ejecutivo ?? 'N/A' }}</span>
                                        </div>
                                    </td>

                                    {{-- Campos Personalizados --}}
                                    @foreach($camposPersonalizados ?? [] as $campo)
                                        <td class="px-4 py-3 text-xs bg-indigo-50/30">
                                            @include('Logistica.partials.campo-personalizado-celda', ['operacion' => $op, 'campo' => $campo])
                                        </td>
                                    @endforeach

                                    {{-- Acciones --}}
                                    <td class="px-4 py-3 text-right sticky-right group-hover:bg-slate-50">
                                        <div class="flex justify-end gap-1">
                                            <button onclick="verHistorial({{ $op->id }})" class="p-1.5 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="Historial">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            </button>
                                            <button onclick="editarOperacion({{ $op->id }})" class="p-1.5 text-slate-400 hover:text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="Editar">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                            </button>
                                            <button onclick="verComentarios({{ $op->id }})" class="p-1.5 text-slate-400 hover:text-green-600 hover:bg-green-50 rounded-lg transition-colors" title="Comentarios">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"></path></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="20" class="px-6 py-12 text-center text-slate-400">
                                        No se encontraron operaciones con los filtros seleccionados.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200">
                    {{ $operaciones->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- ========================================================= --}}
    {{-- SECCIÓN DE MODALES --}}
    {{-- ========================================================= --}}

    <div id="modalOperacion" class="modal-overlay fixed inset-0 bg-black/50 hidden z-50 flex items-center justify-center p-4 backdrop-blur-sm">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] flex flex-col">
            <div class="flex justify-between items-center p-6 border-b border-slate-100">
                <h3 class="text-xl font-bold text-slate-800" id="modalTitle">Nueva Operación</h3>
                <button onclick="cerrarModal()" class="text-slate-400 hover:text-slate-600 text-2xl">&times;</button>
            </div>
            <div class="p-6 overflow-y-auto custom-scrollbar">
                <form id="formOperacion" class="space-y-6">
                    @csrf
                    <input type="hidden" id="operacionId" name="operacion_id">
                    <input type="hidden" id="isEditing" name="_method">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div><label class="block text-sm font-medium mb-1 text-slate-700">Tipo de Operación</label><select name="operacion" class="w-full rounded-lg border-slate-300 focus:border-blue-500"><option value="IMPORTACION">Importación</option><option value="EXPORTACION">Exportación</option></select></div>
                            <div><label class="block text-sm font-medium mb-1 text-slate-700">Medio Transporte</label><select name="tipo_operacion_enum" class="w-full rounded-lg border-slate-300 focus:border-blue-500"><option value="Terrestre">Terrestre</option><option value="Aerea">Aérea</option><option value="Maritima">Marítima</option><option value="Ferrocarril">Ferrocarril</option></select></div>
                            <div><label class="block text-sm font-medium mb-1 text-slate-700">Cliente</label><input type="text" name="cliente" class="w-full rounded-lg border-slate-300 focus:border-blue-500" required></div>
                            <div><label class="block text-sm font-medium mb-1 text-slate-700">Referencia</label><input type="text" name="referencia_cliente" class="w-full rounded-lg border-slate-300 focus:border-blue-500"></div>
                        </div>
                        <div class="space-y-4">
                            <div><label class="block text-sm font-medium mb-1 text-slate-700">Fecha Embarque</label><input type="date" name="fecha_embarque" class="w-full rounded-lg border-slate-300 focus:border-blue-500"></div>
                            <div><label class="block text-sm font-medium mb-1 text-slate-700">Fecha Arribo (ETA)</label><input type="date" name="fecha_arribo_aduana" class="w-full rounded-lg border-slate-300 focus:border-blue-500"></div>
                            <div><label class="block text-sm font-medium mb-1 text-slate-700">No. Pedimento</label><input type="text" name="no_pedimento" class="w-full rounded-lg border-slate-300 focus:border-blue-500"></div>
                            <div>
                                <label class="block text-sm font-medium mb-1 text-slate-700">Ejecutivo Asignado</label>
                                <select name="ejecutivo" class="w-full rounded-lg border-slate-300 focus:border-blue-500">
                                    @foreach($empleados ?? [] as $emp)
                                        <option value="{{ $emp->nombre }}">{{ $emp->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <div id="statusManualSection" class="hidden pt-4 border-t border-slate-100">
                        <label class="block text-sm font-medium mb-1 text-slate-700">Estatus Manual (Forzado)</label>
                        <select name="status_manual" class="w-full rounded-lg border-slate-300">
                            <option value="In Process">En Proceso</option>
                            <option value="Done">Completado</option>
                            <option value="Out of Metric">Fuera Métrica</option>
                        </select>
                    </div>

                    <div class="flex justify-end gap-3 pt-6">
                        <button type="button" onclick="cerrarModal()" class="px-5 py-2.5 rounded-xl border border-slate-300 hover:bg-slate-50 font-medium">Cancelar</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-blue-600 text-white hover:bg-blue-700 font-medium shadow-sm">Guardar</button>
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
        <div class="bg-white rounded-2xl shadow-xl w-full max-w-5xl h-[85vh] flex flex-col">
            <div class="p-6 border-b flex justify-between items-center bg-slate-800 text-white rounded-t-2xl">
                <div>
                    <h3 class="font-bold text-xl">Configuración del Sistema</h3>
                    <p class="text-slate-300 text-sm">Gestiona columnas y tareas estándar.</p>
                </div>
                <button onclick="cerrarModalCamposPersonalizados()" class="text-slate-400 hover:text-white text-2xl">&times;</button>
            </div>
            <div class="flex-1 overflow-hidden flex flex-col md:flex-row">
                <div class="w-full md:w-1/2 p-6 border-r border-slate-200 overflow-y-auto">
                    <h4 class="font-bold text-lg text-slate-800 mb-2">Columnas Extra</h4>
                    <p class="text-sm text-slate-500 mb-4">Campos adicionales en la tabla.</p>
                    <form id="formNuevoCampo" class="flex gap-2 mb-4">
                        <input type="text" id="newCampoNombre" class="flex-1 rounded-lg border-slate-300 text-sm" placeholder="Nombre...">
                        <button type="submit" class="bg-blue-600 text-white px-3 py-2 rounded-lg text-sm hover:bg-blue-700">Agregar</button>
                    </form>
                    <div id="listaCamposConfig" class="space-y-2"></div>
                </div>
                <div class="w-full md:w-1/2 p-6 overflow-y-auto bg-slate-50">
                    <h4 class="font-bold text-lg text-slate-800 mb-2">Checklist Estándar</h4>
                    <p class="text-sm text-slate-500 mb-4">Tareas automáticas para nuevas operaciones.</p>
                    <form id="formNuevaPlantilla" class="flex gap-2 mb-4">
                        <input type="text" id="newPlantillaNombre" class="flex-1 rounded-lg border-slate-300 text-sm" placeholder="Tarea...">
                        <button type="submit" class="bg-green-600 text-white px-3 py-2 rounded-lg text-sm hover:bg-green-700">Agregar</button>
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