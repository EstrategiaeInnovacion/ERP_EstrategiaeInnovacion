@extends('layouts.erp')

@section('title', 'Control de Pedimentos - Logística')

@section('content')
    <div class="min-h-screen bg-slate-50 pb-12">
        <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- 1. ENCABEZADO SUPERIOR --}}
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pt-6">
                <div>
                    <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Control de Pedimentos</h1>
                    <p class="text-slate-500 text-sm">Monitoreo de claves, estados de pago y consolidación por cliente.</p>
                </div>
                
                <div class="flex flex-wrap gap-3">
                    {{-- Botón Exportar --}}
                    <a href="{{ route('logistica.reportes.pedimentos.export') }}" class="inline-flex items-center gap-2 bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 px-5 py-2.5 rounded-xl font-medium shadow-sm transition-all group">
                        <svg class="w-5 h-5 text-green-600 group-hover:scale-110 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                        Exportar Reporte
                    </a>

                    {{-- Botón Importar (Si tienes permisos) --}}
                    <button onclick="document.getElementById('modalImportar').classList.remove('hidden')" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-5 py-2.5 rounded-xl font-semibold shadow-sm transition-all hover:shadow-md hover:-translate-y-0.5">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                        Importar Pedimentos
                    </button>
                </div>
            </div>

            {{-- 2. TARJETAS DE ESTADÍSTICAS (KPIs) --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Claves Activas</p>
                        <p class="text-2xl font-bold text-slate-800 mt-1">{{ $stats['total_claves'] }}</p>
                    </div>
                    <div class="p-3 bg-blue-50 rounded-xl text-blue-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"></path></svg>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Total Operaciones</p>
                        <p class="text-2xl font-bold text-slate-800 mt-1">{{ $stats['total_pedimentos'] }}</p>
                    </div>
                    <div class="p-3 bg-indigo-50 rounded-xl text-indigo-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pagados</p>
                        <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $stats['pagados'] }}</p>
                    </div>
                    <div class="p-3 bg-emerald-50 rounded-xl text-emerald-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>

                <div class="bg-white p-5 rounded-2xl shadow-sm border border-slate-100 flex items-center justify-between border-b-4 border-rose-500">
                    <div>
                        <p class="text-xs font-semibold text-slate-400 uppercase tracking-wider">Pendientes de Pago</p>
                        <p class="text-2xl font-bold text-rose-600 mt-1">{{ $stats['pendientes'] }}</p>
                    </div>
                    <div class="p-3 bg-rose-50 rounded-xl text-rose-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                </div>
            </div>

            {{-- 3. BARRA DE FILTROS --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-5">
                <form method="GET" action="{{ route('logistica.pedimentos.index') }}" class="grid grid-cols-1 md:grid-cols-12 gap-4 items-end">
                    
                    <div class="col-span-1 md:col-span-5">
                        <label for="buscar" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Buscar</label>
                        <div class="relative">
                            <input type="text" name="buscar" value="{{ request('buscar') }}" placeholder="Clave, Cliente, Pedimento..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                        </div>
                    </div>

                    <div class="col-span-1 md:col-span-3">
                        <label for="estado_pago" class="block text-xs font-semibold text-slate-500 uppercase tracking-wider mb-1">Estado de Pago</label>
                        <select name="estado_pago" class="w-full rounded-xl border-slate-200 focus:border-indigo-500 focus:ring-indigo-500 text-sm py-2.5">
                            <option value="">Todos</option>
                            <option value="pendiente" {{ request('estado_pago') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                            <option value="pagado" {{ request('estado_pago') == 'pagado' ? 'selected' : '' }}>Pagado</option>
                        </select>
                    </div>

                    <div class="col-span-1 md:col-span-4 flex gap-2">
                        <button type="submit" class="flex-1 bg-slate-800 hover:bg-slate-900 text-white font-medium py-2.5 px-4 rounded-xl transition-colors text-sm shadow-lg shadow-slate-800/20">
                            Filtrar
                        </button>
                        <a href="{{ route('logistica.pedimentos.index') }}" class="flex-none flex items-center justify-center bg-white border border-slate-200 text-slate-500 hover:text-rose-500 hover:border-rose-200 px-4 rounded-xl transition-all" title="Limpiar Filtros">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </a>
                    </div>
                </form>
            </div>

            {{-- 4. TABLA DE RESULTADOS --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-slate-600">
                        <thead class="bg-slate-50 text-slate-700 uppercase font-bold text-xs tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4">Clave</th>
                                <th class="px-6 py-4">Cliente(s)</th>
                                <th class="px-6 py-4 text-center">Total Ops.</th>
                                <th class="px-6 py-4 text-center">Estado Pago</th>
                                <th class="px-6 py-4 text-center">Progreso</th>
                                <th class="px-6 py-4">Rango Fechas</th>
                                <th class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($paginatedPedimentos as $pedimento)
                                <tr class="hover:bg-slate-50 transition-colors group">
                                    {{-- Clave --}}
                                    <td class="px-6 py-4">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center font-bold text-sm mr-3">
                                                {{ $pedimento->clave }}
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-900">{{ $pedimento->clave }}</div>
                                                <div class="text-xs text-slate-400">Ref. Agrupada</div>
                                            </div>
                                        </div>
                                    </td>

                                    {{-- Clientes --}}
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-slate-700 truncate max-w-[200px]" title="{{ $pedimento->clientes }}">
                                            {{ Str::limit($pedimento->clientes, 30) }}
                                        </div>
                                        <div class="text-xs text-slate-400 mt-0.5 truncate max-w-[200px]">
                                            Ejec: {{ Str::limit($pedimento->ejecutivos, 25) }}
                                        </div>
                                    </td>

                                    {{-- Total Ops --}}
                                    <td class="px-6 py-4 text-center">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-800">
                                            {{ $pedimento->total_pedimentos }}
                                        </span>
                                    </td>

                                    {{-- Estado Pago --}}
                                    <td class="px-6 py-4 text-center">
                                        @if($pedimento->estado_pago === 'pagado')
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">
                                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                                PAGADO
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-700 border border-rose-200">
                                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                                PENDIENTE
                                            </span>
                                        @endif
                                    </td>

                                    {{-- Progreso (Visual extra) --}}
                                    <td class="px-6 py-4">
                                        <div class="w-full bg-slate-200 rounded-full h-1.5 mb-1">
                                            @php
                                                $percentage = $pedimento->total_pedimentos > 0 
                                                    ? ($pedimento->pedimentos_pagados / $pedimento->total_pedimentos) * 100 
                                                    : 0;
                                            @endphp
                                            <div class="bg-indigo-500 h-1.5 rounded-full transition-all duration-500" style="width: {{ $percentage }}%"></div>
                                        </div>
                                        <div class="flex justify-between text-[10px] text-slate-400">
                                            <span>{{ $pedimento->pedimentos_pagados }} pagados</span>
                                            <span>{{ $pedimento->pedimentos_por_pagar }} pendientes</span>
                                        </div>
                                    </td>

                                    {{-- Fechas --}}
                                    <td class="px-6 py-4 text-xs text-slate-500">
                                        <div>Inicio: {{ \Carbon\Carbon::parse($pedimento->primera_fecha)->format('d/m/Y') }}</div>
                                        <div>Fin: {{ \Carbon\Carbon::parse($pedimento->ultima_fecha)->format('d/m/Y') }}</div>
                                    </td>

                                    {{-- Acciones --}}
                                    <td class="px-6 py-4 text-right">
                                        <a href="{{ route('logistica.pedimentos.show', $pedimento->clave) }}" class="inline-flex items-center justify-center p-2 bg-white border border-slate-200 rounded-lg text-slate-600 hover:text-blue-600 hover:border-blue-200 transition-all shadow-sm hover:shadow-md group-hover:bg-blue-50">
                                            <svg class="w-5 h-5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            <span class="text-xs font-semibold">Detalles</span>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                        <div class="flex flex-col items-center justify-center">
                                            <svg class="w-16 h-16 text-slate-200 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            <p class="text-lg font-medium text-slate-500">No se encontraron pedimentos</p>
                                            <p class="text-sm">Intenta ajustar los filtros de búsqueda.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                {{-- Paginación --}}
                <div class="bg-slate-50 px-6 py-4 border-t border-slate-200">
                    {{ $paginatedPedimentos->links() }}
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL IMPORTAR --}}
    <div id="modalImportar" class="hidden fixed inset-0 bg-black/50 z-50 flex items-center justify-center p-4 backdrop-blur-sm transition-opacity">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-lg p-6 transform transition-all scale-100">
            <div class="flex justify-between items-center mb-6 border-b border-slate-100 pb-4">
                <h3 class="text-xl font-bold text-slate-800">Importar Pedimentos</h3>
                <button onclick="document.getElementById('modalImportar').classList.add('hidden')" class="text-slate-400 hover:text-slate-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <form action="{{ route('logistica.pedimentos.import.legacy') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                @csrf
                
                <div class="w-full flex justify-center px-6 pt-5 pb-6 border-2 border-slate-300 border-dashed rounded-xl hover:border-indigo-400 hover:bg-indigo-50 transition-colors group cursor-pointer relative">
                    <input id="file-upload" name="file" type="file" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" onchange="mostrarNombreArchivo(this)">
                    <div class="space-y-1 text-center">
                        <svg class="mx-auto h-12 w-12 text-slate-400 group-hover:text-indigo-500 transition-colors" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="flex text-sm text-slate-600 justify-center">
                            <span class="font-medium text-indigo-600 hover:text-indigo-500">Sube un archivo</span>
                            <p class="pl-1">o arrastra y suelta</p>
                        </div>
                        <p class="text-xs text-slate-500">Excel (XLSX, XLS)</p>
                        <p id="nombreArchivo" class="text-sm font-bold text-indigo-600 mt-2 hidden"></p>
                    </div>
                </div>

                <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 text-sm text-amber-700">
                    <strong>Nota:</strong> El archivo debe contener las columnas: <code>Clave</code>, <code>No. Pedimento</code>, <code>Monto</code>.
                </div>

                <div class="flex justify-end gap-3 pt-2">
                    <button type="button" onclick="document.getElementById('modalImportar').classList.add('hidden')" class="px-5 py-2.5 rounded-xl border border-slate-300 text-slate-700 hover:bg-slate-50 font-medium transition-colors">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-indigo-600 text-white hover:bg-indigo-700 font-medium shadow-sm transition-all hover:shadow-md">
                        Importar Ahora
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function mostrarNombreArchivo(input) {
            const nombre = document.getElementById('nombreArchivo');
            if (input.files && input.files[0]) {
                nombre.textContent = input.files[0].name;
                nombre.classList.remove('hidden');
            } else {
                nombre.classList.add('hidden');
            }
        }
    </script>
@endsection