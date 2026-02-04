@extends('layouts.erp')

@section('title', 'Catálogos Maestros - Logística')

@push('styles')
    <style>
        /* Estilos para pestañas activas */
        .tab-button.active {
            color: #2563eb; /* blue-600 */
            border-bottom-color: #2563eb;
        }
        .tab-button {
            border-bottom-width: 2px;
            border-bottom-color: transparent;
        }
    </style>
@endpush

@section('content')
    <div class="min-h-screen bg-slate-50 pb-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            
            {{-- 1. ENCABEZADO --}}
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pt-8">
                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <a href="{{ route('logistica.index') }}" class="text-slate-400 hover:text-slate-600 transition-colors">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        </a>
                        <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Catálogos Maestros</h1>
                    </div>
                    <p class="text-slate-500 text-sm ml-7">Administración centralizada de datos auxiliares.</p>
                </div>
            </div>

            {{-- ALERTAS --}}
            @if(session('success'))
                <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 flex items-start gap-3">
                    <svg class="w-5 h-5 text-emerald-500 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="text-sm text-emerald-800 font-medium">{{ session('success') }}</p>
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                    <ul class="list-disc list-inside text-sm text-red-800">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            {{-- 2. CONTENEDOR PRINCIPAL --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden relative z-10">
                
                {{-- NAVEGACIÓN TABS --}}
                <div class="border-b border-slate-200 bg-white px-6">
                    <nav class="flex space-x-8 overflow-x-auto" aria-label="Tabs">
                        @if(isset($esAdmin) && $esAdmin)
                            <button onclick="switchTab('clientes')" id="tab-clientes" class="tab-button active py-4 px-1 text-sm font-medium text-slate-500 hover:text-slate-700 whitespace-nowrap transition-colors">Clientes</button>
                        @endif
                        <button onclick="switchTab('agentes')" id="tab-agentes" class="tab-button {{ !(isset($esAdmin) && $esAdmin) ? 'active' : '' }} py-4 px-1 text-sm font-medium text-slate-500 hover:text-slate-700 whitespace-nowrap transition-colors">Agentes Aduanales</button>
                        <button onclick="switchTab('transportes')" id="tab-transportes" class="tab-button py-4 px-1 text-sm font-medium text-slate-500 hover:text-slate-700 whitespace-nowrap transition-colors">Transportes</button>
                        
                        @if(isset($esAdmin) && $esAdmin)
                            <button onclick="switchTab('ejecutivos')" id="tab-ejecutivos" class="tab-button py-4 px-1 text-sm font-medium text-slate-500 hover:text-slate-700 whitespace-nowrap transition-colors">Ejecutivos</button>
                            <button onclick="switchTab('aduanas')" id="tab-aduanas" class="tab-button py-4 px-1 text-sm font-medium text-slate-500 hover:text-slate-700 whitespace-nowrap transition-colors">Aduanas</button>
                            <button onclick="switchTab('pedimentos')" id="tab-pedimentos" class="tab-button py-4 px-1 text-sm font-medium text-slate-500 hover:text-slate-700 whitespace-nowrap transition-colors">Pedimentos</button>
                            <button onclick="switchTab('correos-cc')" id="tab-correos-cc" class="tab-button py-4 px-1 text-sm font-medium text-slate-500 hover:text-slate-700 whitespace-nowrap transition-colors">Correos CC</button>
                        @endif
                    </nav>
                </div>

                {{-- CONTENIDO --}}
                <div class="p-6 bg-white min-h-[500px]">

                    {{-- 1. TAB CLIENTES --}}
                    @if(isset($esAdmin) && $esAdmin)
                    <div id="clientes-content" class="tab-content block">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-bold text-slate-800">Directorio de Clientes</h3>
                            <button onclick="openModal('createClienteModal')" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition-all text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Nuevo Cliente
                            </button>
                        </div>
                        <div class="overflow-x-auto border border-slate-200 rounded-lg">
                            <table class="w-full text-sm text-left text-slate-600">
                                <thead class="bg-slate-50 text-slate-700 uppercase font-bold text-xs tracking-wider border-b border-slate-200">
                                    <tr>
                                        <th class="px-4 py-3">Cliente</th>
                                        <th class="px-4 py-3">Ejecutivo</th>
                                        <th class="px-4 py-3">Periodicidad</th>
                                        <th class="px-4 py-3 text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse($clientes as $cliente)
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-4 py-3 font-medium text-slate-900">{{ $cliente->cliente }}</td>
                                        <td class="px-4 py-3">{{ $cliente->ejecutivoAsignado->nombre ?? '--' }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800">{{ $cliente->periodicidad_reporte ?? 'N/D' }}</span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <button onclick="openEditModal('clientes', {{ $cliente->id }}, '{{ $cliente->cliente }}')" class="text-blue-600 hover:text-blue-800 font-medium text-xs">Editar</button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="4" class="px-4 py-8 text-center text-slate-400">No hay clientes registrados.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">{{ $clientes->appends(['tab' => 'clientes'])->links() }}</div>
                    </div>
                    @endif

                    {{-- 2. TAB AGENTES --}}
                    <div id="agentes-content" class="tab-content {{ !(isset($esAdmin) && $esAdmin) ? 'block' : 'hidden' }}">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-bold text-slate-800">Agentes Aduanales</h3>
                            <button onclick="openModal('createAgenteModal')" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition-all text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Nuevo Agente
                            </button>
                        </div>
                        <div class="overflow-x-auto border border-slate-200 rounded-lg">
                            <table class="w-full text-sm text-left text-slate-600">
                                <thead class="bg-slate-50 text-slate-700 uppercase font-bold text-xs tracking-wider border-b border-slate-200">
                                    <tr>
                                        <th class="px-4 py-3">Nombre Agente</th>
                                        <th class="px-4 py-3 text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse($agentesAduanales as $agente)
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-4 py-3 font-medium text-slate-900">{{ $agente->agente_aduanal }}</td>
                                        <td class="px-4 py-3 text-right">
                                            <button onclick="openEditModal('agentes', {{ $agente->id }}, '{{ $agente->agente_aduanal }}')" class="text-blue-600 hover:text-blue-800 font-medium text-xs">Editar</button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="2" class="px-4 py-8 text-center text-slate-400">No hay agentes.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">{{ $agentesAduanales->appends(['tab' => 'agentes'])->links() }}</div>
                    </div>

                    {{-- 3. TAB TRANSPORTES --}}
                    <div id="transportes-content" class="tab-content hidden">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-lg font-bold text-slate-800">Transportes</h3>
                            <button onclick="openModal('createTransporteModal')" class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium shadow-sm transition-all text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Nuevo Transporte
                            </button>
                        </div>
                        <div class="overflow-x-auto border border-slate-200 rounded-lg">
                            <table class="w-full text-sm text-left text-slate-600">
                                <thead class="bg-slate-50 text-slate-700 uppercase font-bold text-xs tracking-wider border-b border-slate-200">
                                    <tr>
                                        <th class="px-4 py-3">Transporte</th>
                                        <th class="px-4 py-3">Tipo</th>
                                        <th class="px-4 py-3 text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @forelse($transportes as $transporte)
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-4 py-3 font-medium text-slate-900">{{ $transporte->transporte }}</td>
                                        <td class="px-4 py-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium {{ $transporte->tipo_operacion == 'EXPORTACION' ? 'bg-amber-100 text-amber-800' : 'bg-blue-100 text-blue-800' }}">
                                                {{ $transporte->tipo_operacion }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-right">
                                            <button onclick="openEditModal('transportes', {{ $transporte->id }}, '{{ $transporte->transporte }}')" class="text-blue-600 hover:text-blue-800 font-medium text-xs">Editar</button>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr><td colspan="3" class="px-4 py-8 text-center text-slate-400">No hay transportes.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <div class="mt-4">{{ $transportes->appends(['tab' => 'transportes'])->links() }}</div>
                    </div>

                    {{-- 4. OTRAS TABS DE ADMIN (Solo lectura rápida en este ejemplo) --}}
                    @if(isset($esAdmin) && $esAdmin)
                        <div id="ejecutivos-content" class="tab-content hidden">
                            <h3 class="text-lg font-bold text-slate-800 mb-6">Ejecutivos</h3>
                            <div class="overflow-x-auto border border-slate-200 rounded-lg"><table class="w-full text-sm text-left text-slate-600"><thead class="bg-slate-50 text-slate-700 uppercase text-xs"><tr><th class="px-4 py-3">Nombre</th><th class="px-4 py-3">Correo</th></tr></thead><tbody>@foreach($ejecutivos as $e)<tr class="hover:bg-slate-50"><td class="px-4 py-3 font-medium">{{ $e->nombre }}</td><td class="px-4 py-3">{{ $e->correo }}</td></tr>@endforeach</tbody></table></div>
                        </div>
                        <div id="aduanas-content" class="tab-content hidden">
                            <h3 class="text-lg font-bold text-slate-800 mb-6">Aduanas</h3>
                            <div class="overflow-x-auto border border-slate-200 rounded-lg"><table class="w-full text-sm text-left text-slate-600"><thead class="bg-slate-50 text-slate-700 uppercase text-xs"><tr><th class="px-4 py-3">Código</th><th class="px-4 py-3">Denominación</th></tr></thead><tbody>@foreach($aduanas as $a)<tr class="hover:bg-slate-50"><td class="px-4 py-3 font-mono font-bold text-indigo-600">{{ $a->aduana }}</td><td class="px-4 py-3">{{ $a->denominacion }}</td></tr>@endforeach</tbody></table></div>
                        </div>
                        <div id="pedimentos-content" class="tab-content hidden">
                            <h3 class="text-lg font-bold text-slate-800 mb-6">Pedimentos</h3>
                            <div class="overflow-x-auto border border-slate-200 rounded-lg"><table class="w-full text-sm text-left text-slate-600"><thead class="bg-slate-50 text-slate-700 uppercase text-xs"><tr><th class="px-4 py-3">Clave</th><th class="px-4 py-3">Descripción</th></tr></thead><tbody>@foreach($pedimentos as $p)<tr class="hover:bg-slate-50"><td class="px-4 py-3 font-mono font-bold">{{ $p->clave }}</td><td class="px-4 py-3">{{ $p->descripcion }}</td></tr>@endforeach</tbody></table></div>
                        </div>
                        <div id="correos-cc-content" class="tab-content hidden">
                            <div class="flex justify-between items-center mb-6"><h3 class="text-lg font-bold text-slate-800">Correos CC</h3><a href="{{ route('logistica.correos-cc.index') }}" class="text-blue-600 hover:underline text-sm font-medium">Gestionar</a></div>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">@foreach($correosCC as $c)<div class="bg-slate-50 p-4 rounded-xl border border-slate-200"><p class="font-bold text-slate-800">{{ $c->nombre }}</p><p class="text-sm text-slate-500">{{ $c->email }}</p></div>@endforeach</div>
                        </div>
                    @endif

                </div>
            </div>
        </div>
    </div>

    {{-- ======================================================================== --}}
    {{-- MODALES DE CREACIÓN (AQUÍ ESTABAN LOS FALTANTES) --}}
    {{-- ======================================================================== --}}

    {{-- 1. Modal Nuevo Cliente --}}
    <div id="createClienteModal" class="relative z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" onclick="closeModal('createClienteModal')"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <form action="{{ route('logistica.clientes.store') }}" method="POST">
                        @csrf
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-bold text-slate-900 mb-4">Nuevo Cliente</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Razón Social</label>
                                    <input type="text" name="cliente" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Ejecutivo Asignado</label>
                                    <select name="ejecutivo_asignado_id" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        <option value="">-- Seleccionar --</option>
                                        @foreach($todosEjecutivos as $ejecutivo)
                                            <option value="{{ $ejecutivo->id }}">{{ $ejecutivo->nombre }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Periodicidad Reporte</label>
                                    <select name="periodicidad_reporte" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        <option value="Diario">Diario</option>
                                        <option value="Semanal">Semanal</option>
                                        <option value="Tri-semanal">Tri-semanal</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Correos (Separados por coma)</label>
                                    <textarea name="correos_comunicacion" rows="2" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm"></textarea>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button type="submit" class="inline-flex w-full justify-center rounded-xl bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto">Guardar</button>
                            <button type="button" onclick="closeModal('createClienteModal')" class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. Modal Nuevo Agente --}}
    <div id="createAgenteModal" class="relative z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" onclick="closeModal('createAgenteModal')"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <form action="{{ route('logistica.agentes.store') }}" method="POST">
                        @csrf
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-bold text-slate-900 mb-4">Nuevo Agente Aduanal</h3>
                            <div>
                                <label class="block text-sm font-medium text-slate-700">Nombre / Patente</label>
                                <input type="text" name="agente_aduanal" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                        </div>
                        <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button type="submit" class="inline-flex w-full justify-center rounded-xl bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto">Guardar</button>
                            <button type="button" onclick="closeModal('createAgenteModal')" class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Modal Nuevo Transporte --}}
    <div id="createTransporteModal" class="relative z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" onclick="closeModal('createTransporteModal')"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <form action="{{ route('logistica.transportes.store') }}" method="POST">
                        @csrf
                        <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                            <h3 class="text-lg font-bold text-slate-900 mb-4">Nuevo Transporte</h3>
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Nombre Transporte</label>
                                    <input type="text" name="transporte" required class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-slate-700">Tipo de Operación</label>
                                    <select name="tipo_operacion" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                                        <option value="IMPORTACION">IMPORTACION</option>
                                        <option value="EXPORTACION">EXPORTACION</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                            <button type="submit" class="inline-flex w-full justify-center rounded-xl bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto">Guardar</button>
                            <button type="button" onclick="closeModal('createTransporteModal')" class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">Cancelar</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    {{-- 4. Modal Edición Genérico --}}
    <div id="editModal" class="relative z-50 hidden" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="fixed inset-0 bg-slate-900 bg-opacity-75 transition-opacity" onclick="closeModal('editModal')"></div>
        <div class="fixed inset-0 z-10 overflow-y-auto">
            <div class="flex min-h-full items-end justify-center p-4 text-center sm:items-center sm:p-0">
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    
                    <div class="bg-white px-4 pb-4 pt-5 sm:p-6 sm:pb-4">
                        <h3 class="text-lg font-bold leading-6 text-slate-900 mb-4" id="modalTitle">Editar Registro</h3>
                        
                        <form id="editForm" method="POST" class="space-y-4">
                            @csrf
                            @method('PUT')
                            <input type="hidden" id="editId" name="id">
                            
                            {{-- Campo Nombre (Común) --}}
                            <div>
                                <label for="editName" class="block text-sm font-medium text-slate-700">Nombre / Descripción</label>
                                <input type="text" name="name" id="editName" class="mt-1 block w-full rounded-lg border-slate-300 shadow-sm focus:border-blue-500 focus:ring-blue-500 sm:text-sm">
                            </div>
                        </form>
                    </div>

                    <div class="bg-slate-50 px-4 py-3 sm:flex sm:flex-row-reverse sm:px-6">
                        <button type="submit" form="editForm" class="inline-flex w-full justify-center rounded-xl bg-blue-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-blue-500 sm:ml-3 sm:w-auto">Guardar Cambios</button>
                        <button type="button" onclick="closeModal('editModal')" class="mt-3 inline-flex w-full justify-center rounded-xl bg-white px-3 py-2 text-sm font-semibold text-slate-900 shadow-sm ring-1 ring-inset ring-slate-300 hover:bg-slate-50 sm:mt-0 sm:w-auto">Cancelar</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- SCRIPTS --}}
    @push('scripts')
    <script>
        // 1. TABS
        function switchTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(el => {
                el.classList.add('hidden');
                el.classList.remove('block');
            });
            document.querySelectorAll('.tab-button').forEach(el => {
                el.classList.remove('active', 'text-blue-600', 'border-blue-600');
                el.classList.add('text-slate-500', 'border-transparent');
            });

            const content = document.getElementById(tabName + '-content');
            if(content) {
                content.classList.remove('hidden');
                content.classList.add('block');
            }
            const btn = document.getElementById('tab-' + tabName);
            if(btn) {
                btn.classList.add('active', 'text-blue-600', 'border-blue-600');
                btn.classList.remove('text-slate-500', 'border-transparent');
            }
        }

        // 2. MODALES
        function openModal(modalId) {
            document.getElementById(modalId)?.classList.remove('hidden');
        }

        function closeModal(modalId) {
            document.getElementById(modalId)?.classList.add('hidden');
        }

        function openEditModal(type, id, name) {
            const modal = document.getElementById('editModal');
            const form = document.getElementById('editForm');
            const nameInput = document.getElementById('editName');

            let url = '';
            if(type === 'clientes') url = `/logistica/clientes/${id}`;
            if(type === 'agentes') url = `/logistica/agentes/${id}`;
            if(type === 'transportes') url = `/logistica/transportes/${id}`;

            form.action = url;
            nameInput.value = name;
            modal.classList.remove('hidden');
        }

        // Init
        document.addEventListener('DOMContentLoaded', () => {
            const urlParams = new URLSearchParams(window.location.search);
            const tab = urlParams.get('tab') || '{{ isset($esAdmin) && $esAdmin ? "clientes" : "agentes" }}';
            switchTab(tab);
        });
    </script>
    @endpush

@endsection