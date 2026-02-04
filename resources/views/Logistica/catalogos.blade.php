@extends('layouts.erp')

@section('title', 'Catálogos Maestros - Logística')

@push('styles')
<link rel="stylesheet" href="{{ asset('css/Logistica/catalogos.css') }}">
@endpush

@section('content')
    <main class="relative overflow-hidden bg-gradient-to-br from-white via-blue-50 to-blue-100 min-h-screen">
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute -top-32 -left-20 w-96 h-96 bg-blue-200/40 blur-3xl rounded-full"></div>
            <div class="absolute top-40 -right-24 w-96 h-96 bg-blue-300/30 blur-3xl rounded-full"></div>
        </div>

        <!-- Header -->
        <div class="relative max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-10">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <a href="{{ route('logistica.index') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-800 shadow-sm transition-all duration-200 mb-4">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Regresar
                    </a>
                    <h1 class="text-2xl font-bold text-slate-900">Catálogos Maestros</h1>
                    <p class="text-sm text-slate-600 mt-1">Administración de datos maestros del área de logística</p>
                </div>
            </div>

            <!-- Mensajes Flash -->
            @if(session('success'))
                <div class="mb-6 bg-green-50 border border-green-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 bg-red-50 border border-red-200 rounded-lg p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Pestañas -->
            <div class="bg-white/90 backdrop-blur rounded-2xl border border-blue-100/80 shadow-lg shadow-blue-500/10 overflow-hidden">
                <!-- Tab Headers -->
                <div class="border-b border-slate-200">
                    <nav class="flex space-x-8 px-6" aria-label="Tabs">
                        @if(auth()->user() && auth()->user()->hasRole('admin'))
                        <button data-tab="clientes" id="tab-clientes"
                                class="tab-button active whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                            <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                            </svg>
                            Clientes
                            <span class="ml-2 bg-blue-100 text-blue-600 py-0.5 px-2 rounded-full text-xs">{{ $clientes->total() }}</span>
                        </button>
                        @endif
                        <button data-tab="agentes" id="tab-agentes"
                                class="tab-button {{ !(auth()->user() && auth()->user()->hasRole('admin')) ? 'active' : '' }} whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                            <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            Agentes Aduanales
                            <span class="ml-2 bg-slate-100 text-slate-600 py-0.5 px-2 rounded-full text-xs">{{ $agentesAduanales->total() }}</span>
                        </button>
                        <button data-tab="transportes" id="tab-transportes"
                                class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                            <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                            </svg>
                            Transportes
                            <span class="ml-2 bg-slate-100 text-slate-600 py-0.5 px-2 rounded-full text-xs">{{ $transportes->total() }}</span>
                        </button>
                        @if(auth()->user() && auth()->user()->hasRole('admin'))
                        <button data-tab="ejecutivos" id="tab-ejecutivos"
                                class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                            <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Ejecutivos
                            <span class="ml-2 bg-slate-100 text-slate-600 py-0.5 px-2 rounded-full text-xs">{{ $ejecutivos->total() }}</span>
                        </button>
                        <button data-tab="aduanas" id="tab-aduanas"
                                class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                            <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                            </svg>
                            Aduanas
                            <span class="ml-2 bg-purple-100 text-purple-600 py-0.5 px-2 rounded-full text-xs">{{ $aduanas->total() }}</span>
                        </button>
                        <button data-tab="pedimentos" id="tab-pedimentos"
                                class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                            <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            Pedimentos
                            <span class="ml-2 bg-indigo-100 text-indigo-600 py-0.5 px-2 rounded-full text-xs">{{ $pedimentos->total() }}</span>
                        </button>
                        <button data-tab="correos-cc" id="tab-correos-cc"
                                class="tab-button whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                            <svg class="w-4 h-4 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            Correos CC
                            <span class="ml-2 bg-green-100 text-green-600 py-0.5 px-2 rounded-full text-xs">{{ $correosCC->count() ?? 0 }}</span>
                        </button>
                        @endif
                    </nav>
                </div>

                <!-- Tab Contents -->

                <!-- Clientes Tab - Solo Admin -->
                @if(auth()->user() && auth()->user()->hasRole('admin'))
                <div id="clientes-content" class="tab-content">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-lg font-semibold text-slate-800">Gestión de Clientes</h2>
                            <div class="flex space-x-3">
                                @if(auth()->user() && auth()->user()->hasRole('admin'))
                                <button id="assignExecutiveBtn" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors shadow-sm" disabled>
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    Asignar Ejecutivo
                                </button>
                                <button id="deleteAllClientsBtn" class="inline-flex items-center px-4 py-2 bg-gray-500 text-white rounded-xl hover:bg-gray-600 transition-colors shadow-sm">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Limpiar Clientes
                                </button>
                                @endif
                                <button class="btn-add inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors shadow-sm" data-type="clientes">
                                    <span class="mr-2 font-bold">+</span>
                                    Agregar Cliente
                                </button>
                            </div>
                        </div>



                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr>
                                        @if(auth()->user() && auth()->user()->hasRole('admin'))
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">
                                            <input type="checkbox" id="selectAllClientes" class="rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50">
                                        </th>
                                        @endif
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">ID</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Cliente</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Correos</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Ejecutivo Asignado</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Periodicidad</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Fecha Creación</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    @forelse($clientes as $cliente)
                                    <tr class="hover:bg-blue-50/50 transition-colors">
                                        @if(auth()->user() && auth()->user()->hasRole('admin'))
                                        <td class="px-4 py-3">
                                            <input type="checkbox" class="cliente-checkbox rounded border-gray-300 text-blue-600 shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50" value="{{ $cliente->id }}">
                                        </td>
                                        @endif
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ $cliente->id }}</td>
                                        <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $cliente->cliente }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-600">
                                            @if($cliente->correos && count($cliente->correos) > 0)
                                                <div class="space-y-1">
                                                    @foreach($cliente->correos as $correo)
                                                        <div class="inline-block bg-blue-50 text-blue-700 px-2 py-1 rounded text-xs">
                                                            {{ $correo }}
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @else
                                                <span class="text-gray-400 text-xs">Sin correos</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-600">
                                            @if($cliente->ejecutivoAsignado)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                    {{ $cliente->ejecutivoAsignado->nombre }}
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-600">
                                                    Sin asignar
                                                </span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-600">
                                            @if($cliente->periodicidad_reporte)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium 
                                                    {{ $cliente->periodicidad_reporte === 'Diario' ? 'bg-red-100 text-red-800' : 
                                                       ($cliente->periodicidad_reporte === 'Semanal' ? 'bg-yellow-100 text-yellow-800' : 'bg-blue-100 text-blue-800') }}">
                                                    {{ $cliente->periodicidad_reporte }}
                                                </span>
                                            @else
                                                <span class="text-gray-400 text-xs">No definida</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ $cliente->created_at->format('d/m/Y') }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <div class="flex space-x-2">
                                                @php
                                                    $puedeEditar = $esAdmin ?? true;
                                                    if (!$puedeEditar && isset($empleadoActual) && $empleadoActual) {
                                                        $puedeEditar = $cliente->ejecutivo_asignado_id == $empleadoActual->id;
                                                    }
                                                @endphp
                                                
                                                @if($puedeEditar)
                                                    <button class="btn-edit px-3 py-1 rounded-lg text-sm font-medium transition-all"
                                                            data-id="{{ $cliente->id }}"
                                                            data-type="clientes"
                                                            data-name="{{ $cliente->cliente }}"
                                                            data-ejecutivo-id="{{ $cliente->ejecutivo_asignado_id }}"
                                                            data-periodicidad="{{ $cliente->periodicidad_reporte }}"
                                                            data-correos="{{ $cliente->correos_string }}">
                                                        Editar
                                                    </button>
                                                    <button class="btn-delete px-3 py-1 rounded-lg text-sm font-medium transition-all"
                                                            data-id="{{ $cliente->id }}"
                                                            data-type="clientes"
                                                            data-name="{{ $cliente->cliente }}">
                                                        Eliminar
                                                    </button>
                                                @else
                                                    <span class="text-xs text-gray-400 italic">Sin permisos</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="{{ auth()->user() && auth()->user()->hasRole('admin') ? 6 : 5 }}" class="px-4 py-8 text-center text-slate-500">
                                            <div class="flex flex-col items-center">
                                                <svg class="w-12 h-12 mb-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path>
                                                </svg>
                                                <p>No hay clientes registrados</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($clientes->hasPages())
                        <div class="mt-6 flex justify-center">
                            {{ $clientes->appends(['tab' => 'clientes'])->links() }}
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Agentes Tab - Visible para todos -->
                <div id="agentes-content" class="tab-content {{ !(auth()->user() && auth()->user()->hasRole('admin')) ? '' : 'hidden' }}">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-lg font-semibold text-slate-800">Gestión de Agentes Aduanales</h2>
                            <button class="btn-add inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors shadow-sm" data-type="agentes">
                                <span class="mr-2 font-bold">+</span>
                                Agregar Agente
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">ID</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Agente Aduanal</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Fecha Creación</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    @forelse($agentesAduanales as $agente)
                                    <tr class="hover:bg-blue-50/50 transition-colors">
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ $agente->id }}</td>
                                        <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $agente->agente_aduanal }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ $agente->created_at->format('d/m/Y') }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <div class="flex space-x-2">
                                                <button class="btn-edit px-3 py-1 rounded-lg text-sm font-medium transition-all"
                                                        data-id="{{ $agente->id }}"
                                                        data-type="agentes"
                                                        data-name="{{ $agente->agente_aduanal }}">
                                                    Editar
                                                </button>
                                                <button class="btn-delete px-3 py-1 rounded-lg text-sm font-medium transition-all"
                                                        data-id="{{ $agente->id }}"
                                                        data-type="agentes"
                                                        data-name="{{ $agente->agente_aduanal }}">
                                                    Eliminar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="4" class="px-4 py-8 text-center text-slate-500">
                                            <div class="flex flex-col items-center">
                                                <svg class="w-12 h-12 mb-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                </svg>
                                                <p>No hay agentes aduanales registrados</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($agentesAduanales->hasPages())
                        <div class="mt-6 flex justify-center">
                            {{ $agentesAduanales->appends(['tab' => 'agentes'])->links() }}
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Transportes Tab -->
                <div id="transportes-content" class="tab-content hidden">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-lg font-semibold text-slate-800">Gestión de Transportes</h2>
                            <button class="btn-add inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors shadow-sm" data-type="transportes">
                                <span class="mr-2 font-bold">+</span>
                                Agregar Transporte
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">ID</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Transporte</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Tipo Operación</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Fecha Creación</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    @forelse($transportes as $transporte)
                                    <tr class="hover:bg-blue-50/50 transition-colors">
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ $transporte->id }}</td>
                                        <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $transporte->transporte }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-600">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                                {{ $transporte->tipo_operacion == 'EXPORTACION' ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                                {{ $transporte->tipo_operacion }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ $transporte->created_at->format('d/m/Y') }}</td>
                                        <td class="px-4 py-3 text-sm">
                                            <div class="flex space-x-2">
                                                <button class="btn-edit px-3 py-1 rounded-lg text-sm font-medium transition-all"
                                                        data-id="{{ $transporte->id }}"
                                                        data-type="transportes"
                                                        data-name="{{ $transporte->transporte }}">
                                                    Editar
                                                </button>
                                                <button class="btn-delete px-3 py-1 rounded-lg text-sm font-medium transition-all"
                                                        data-id="{{ $transporte->id }}"
                                                        data-type="transportes"
                                                        data-name="{{ $transporte->transporte }}">
                                                    Eliminar
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                                            <div class="flex flex-col items-center">
                                                <svg class="w-12 h-12 mb-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path>
                                                </svg>
                                                <p>No hay transportes registrados</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($transportes->hasPages())
                        <div class="mt-6 flex justify-center">
                            {{ $transportes->appends(['tab' => 'transportes'])->links() }}
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Ejecutivos Tab - Solo Admin -->
                @if(auth()->user() && auth()->user()->hasRole('admin'))
                <div id="ejecutivos-content" class="tab-content hidden">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <div>
                                <h2 class="text-lg font-semibold text-slate-800">Ejecutivos de Logística</h2>
                                <p class="text-sm text-slate-600">Solo se muestran empleados del área de logística</p>
                            </div>
                            <button id="searchEmployeeBtn" onclick="openSearchEmployeeModal()" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors shadow-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                                Buscar Empleado
                            </button>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">ID</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Nombre</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Área</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Correo</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">ID Empleado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    @forelse($ejecutivos as $ejecutivo)
                                    <tr class="hover:bg-blue-50/50 transition-colors">
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ $ejecutivo->id }}</td>
                                        <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $ejecutivo->nombre }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-600">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                {{ $ejecutivo->area }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ $ejecutivo->correo ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ $ejecutivo->id_empleado ?? 'N/A' }}</td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="5" class="px-4 py-8 text-center text-slate-500">
                                            <div class="flex flex-col items-center">
                                                <svg class="w-12 h-12 mb-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                                <p>No hay ejecutivos de logística registrados</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($ejecutivos->hasPages())
                        <div class="mt-6 flex justify-center">
                            {{ $ejecutivos->appends(['tab' => 'ejecutivos'])->links() }}
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Aduanas Tab - Solo Admin -->
                <div id="aduanas-content" class="tab-content hidden">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-lg font-semibold text-slate-800">Gestión de Aduanas</h2>
                            <div class="flex space-x-3">
                                <button id="addAduanaBtn" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors shadow-sm">
                                    <span class="mr-2 font-bold">+</span>
                                    Añadir Aduana
                                </button>
                                <button id="clearAduanasBtn" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors shadow-sm">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Limpiar Todo
                                </button>
                                <button id="importADUANASBtn" class="inline-flex items-center px-4 py-2 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition-colors shadow-sm" style="display: {{ $aduanas->total() > 0 ? 'none' : 'inline-flex' }};">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    Importar desde Archivo
                                </button>
                            </div>
                        </div>

                        <!-- Progress bar para importación -->
                        <div id="importProgress" class="hidden mb-6">
                            <div class="bg-gray-200 rounded-full h-2 mb-2">
                                <div id="progressBar" class="bg-purple-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                            </div>
                            <p id="progressText" class="text-sm text-gray-600">Procesando archivo...</p>
                        </div>

                        <!-- Estadísticas -->
                        <div id="aduanasStats" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div class="bg-purple-50 p-4 rounded-lg">
                                <h3 class="text-sm font-medium text-purple-800">Total Aduanas</h3>
                                <p id="totalAduanas" class="text-2xl font-bold text-purple-600">{{ $aduanas->total() }}</p>
                            </div>
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <h3 class="text-sm font-medium text-blue-800">Última Importación</h3>
                                <p id="ultimaImportacion" class="text-sm text-blue-600">-</p>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <h3 class="text-sm font-medium text-green-800">Estado</h3>
                                <p id="estadoImportacion" class="text-sm text-green-600">Listo para importar</p>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Aduana</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Sección</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Denominación</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Patente</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">País</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Fecha</th>
                                        <th class="px-4 py-3 text-center text-sm font-semibold text-slate-700">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200" id="aduanasTableBody">
                                    @forelse($aduanas as $aduana)
                                    <tr class="hover:bg-purple-50/50 transition-colors">
                                        <td class="px-4 py-3 text-sm font-medium text-slate-900">{{ $aduana->aduana }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ $aduana->seccion }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ Str::limit($aduana->denominacion, 50) }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ $aduana->patente ?? 'N/A' }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-600">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                {{ $aduana->pais }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ $aduana->created_at->format('d/m/Y') }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex justify-center space-x-2">
                                                <button onclick="editarAduana({{ $aduana->id }}, '{{ $aduana->aduana }}', '{{ $aduana->seccion }}', '{{ addslashes($aduana->denominacion) }}', '{{ $aduana->patente }}', '{{ $aduana->pais }}')"
                                                        class="text-blue-600 hover:text-blue-800 transition-colors" title="Editar">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </button>
                                                <button onclick="eliminarAduana({{ $aduana->id }})"
                                                        class="text-red-600 hover:text-red-800 transition-colors" title="Eliminar">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="px-4 py-8 text-center text-slate-500">
                                            <div class="flex flex-col items-center">
                                                <svg class="w-12 h-12 mb-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path>
                                                </svg>
                                                <p class="mb-2">No hay aduanas registradas</p>
                                                <p class="text-xs">Importa un archivo Word, Excel o CSV con la información de aduanas</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($aduanas->hasPages())
                        <div class="mt-6 flex justify-center">
                            {{ $aduanas->appends(['tab' => 'aduanas'])->links() }}
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Pedimentos Tab - Solo Admin -->
                <div id="pedimentos-content" class="tab-content hidden">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-lg font-semibold text-slate-800">Gestión de Claves de Pedimentos</h2>
                            <div class="flex space-x-3">
                                <button id="addPedimentoBtn" onclick="openAddPedimentoModal()" class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-xl hover:bg-green-700 transition-colors shadow-sm">
                                    <span class="mr-2 font-bold">+</span>
                                    Añadir Pedimento
                                </button>
                                <button id="clearPedimentosBtn" onclick="clearAllPedimentos()" class="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors shadow-sm">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                    Limpiar Todo
                                </button>
                                <button id="importPedimentosBtn" onclick="openImportPedimentosModal()" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition-colors shadow-sm" style="display: {{ $pedimentos->total() > 0 ? 'none' : 'inline-flex' }};">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
                                    </svg>
                                    Importar desde Archivo
                                </button>
                            </div>
                        </div>

                        <!-- Progress bar para importación -->
                        <div id="importPedimentosProgress" class="hidden mb-6">
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                <div class="flex items-center mb-2">
                                    <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mr-2"></div>
                                    <span class="text-sm font-medium text-blue-800" id="progressPedimentosText">Procesando archivo...</span>
                                </div>
                                <div class="w-full bg-blue-200 rounded-full h-2">
                                    <div class="bg-blue-600 h-2 rounded-full transition-all duration-500" style="width: 0%" id="progressPedimentosBar"></div>
                                </div>
                            </div>
                        </div>

                        <!-- Estadísticas de pedimentos -->
                        <div id="pedimentosStats" class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
                            <div class="bg-indigo-50 p-4 rounded-lg">
                                <h3 class="text-sm font-medium text-indigo-800">Total Pedimentos</h3>
                                <p id="totalPedimentos" class="text-2xl font-bold text-indigo-600">{{ $pedimentos->total() }}</p>
                            </div>
                            <div class="bg-blue-50 p-4 rounded-lg">
                                <h3 class="text-sm font-medium text-blue-800">Última Importación</h3>
                                <p id="ultimaImportacionPedimentos" class="text-sm text-blue-600">-</p>
                            </div>
                            <div class="bg-green-50 p-4 rounded-lg">
                                <h3 class="text-sm font-medium text-green-800">Estado</h3>
                                <p id="estadoImportacionPedimentos" class="text-sm text-green-600">Listo para importar</p>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-slate-50 border-b border-slate-200">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Clave</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Categoría</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Descripción</th>
                                        <th class="px-4 py-3 text-left text-sm font-semibold text-slate-700">Fecha</th>
                                        <th class="px-4 py-3 text-center text-sm font-semibold text-slate-700">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200" id="pedimentosTableBody">
                                    @forelse($pedimentos as $pedimento)
                                    <tr class="hover:bg-indigo-50/50 transition-colors">
                                        <td class="px-4 py-3 text-sm font-medium text-slate-900">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                                {{ $pedimento->clave }}
                                            </span>
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-600">
                                            @if($pedimento->categoria)
                                                <span class="inline-flex items-center px-2 py-1 rounded-md text-xs font-medium bg-blue-100 text-blue-800">
                                                    {{ $pedimento->categoria }}
                                                </span>
                                            @else
                                                <span class="text-slate-400">-</span>
                                            @endif
                                        </td>
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ Str::limit($pedimento->descripcion, 80) }}</td>
                                        <td class="px-4 py-3 text-sm text-slate-600">{{ $pedimento->created_at->format('d/m/Y') }}</td>
                                        <td class="px-4 py-3 text-center">
                                            <div class="flex justify-center space-x-2">
                                                <button onclick="editarPedimento({{ $pedimento->id }}, '{{ $pedimento->clave }}', '{{ addslashes($pedimento->descripcion) }}', '{{ addslashes($pedimento->categoria ?? '') }}')"
                                                        class="text-blue-600 hover:text-blue-800 transition-colors" title="Editar">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                                    </svg>
                                                </button>
                                                <button onclick="eliminarPedimento({{ $pedimento->id }})"
                                                        class="text-red-600 hover:text-red-800 transition-colors" title="Eliminar">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="6" class="px-4 py-8 text-center text-slate-500">
                                            <div class="flex flex-col items-center">
                                                <svg class="w-12 h-12 mb-2 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                                </svg>
                                                <p class="mb-2">No hay claves de pedimentos registradas</p>
                                                <p class="text-xs">Importa un archivo Word, Excel o CSV con las claves de pedimentos</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>

                        @if($pedimentos->hasPages())
                        <div class="mt-6 flex justify-center">
                            {{ $pedimentos->appends(['tab' => 'pedimentos'])->links() }}
                        </div>
                        @endif
                    </div>
                </div>

                <!-- Correos CC Tab - Solo Admin -->
                <div id="correos-cc-content" class="tab-content hidden">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-6">
                            <h2 class="text-xl font-semibold text-slate-800">Correos CC para Reportes</h2>
                            <div class="flex items-center space-x-3">
                                <a href="{{ route('logistica.correos-cc.index') }}" 
                                   class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Gestionar Correos CC
                                </a>
                            </div>
                        </div>

                        <div class="bg-blue-50 rounded-lg p-6 border border-blue-200">
                            <div class="flex items-start">
                                <div class="flex-shrink-0">
                                    <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <h3 class="text-lg font-medium text-blue-900">Administración de Correos CC</h3>
                                    <div class="mt-2 text-sm text-blue-700">
                                        <p class="mb-2">Los correos configurados en esta sección serán incluidos automáticamente como CC al enviar reportes de logística.</p>
                                        <ul class="list-disc ml-4 space-y-1">
                                            <li><strong>Administradores:</strong> Siempre incluidos en CC independientemente del estado</li>
                                            <li><strong>Supervisores:</strong> Incluidos según configuración específica del envío</li>
                                            <li><strong>Notificaciones:</strong> Para alertas automáticas y notificaciones del sistema</li>
                                        </ul>
                                        <div class="mt-4 flex items-center justify-between">
                                            <p class="text-sm">
                                                <strong>Correos activos configurados:</strong> <span class="bg-blue-200 px-2 py-1 rounded text-blue-800">{{ $correosCC->count() ?? 0 }}</span>
                                            </p>
                                            <a href="{{ route('logistica.correos-cc.index') }}" 
                                               class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm font-medium">
                                                Ver detalles
                                                <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                </svg>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @if(isset($correosCC) && $correosCC->count() > 0)
                        <div class="mt-6">
                            <h4 class="text-sm font-medium text-slate-700 mb-3">Vista previa de correos configurados:</h4>
                            <div class="grid gap-2 max-h-40 overflow-y-auto">
                                @foreach($correosCC->take(5) as $correo)
                                <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg text-sm">
                                    <div class="flex items-center space-x-2">
                                        <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                            @if($correo->tipo === 'administrador') bg-red-100 text-red-800
                                            @elseif($correo->tipo === 'supervisor') bg-yellow-100 text-yellow-800
                                            @else bg-blue-100 text-blue-800
                                            @endif">
                                            {{ ucfirst($correo->tipo) }}
                                        </span>
                                        <span class="font-medium">{{ $correo->nombre }}</span>
                                        <span class="text-gray-600">{{ $correo->email }}</span>
                                    </div>
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                        {{ $correo->activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $correo->activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </div>
                                @endforeach
                                @if($correosCC->count() > 5)
                                <div class="text-center">
                                    <a href="{{ route('logistica.correos-cc.index') }}" 
                                       class="text-blue-600 hover:text-blue-800 text-sm">
                                        Ver todos ({{ $correosCC->count() }} correos)
                                    </a>
                                </div>
                                @endif
                            </div>
                        </div>
                        @else
                        <div class="mt-6 text-center py-8 text-gray-500">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 4.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                            </svg>
                            <p>No hay correos CC configurados</p>
                            <p class="text-sm">Agrega el primer correo CC para comenzar</p>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Modal de Edición -->
    <div id="editModal" class="modal-overlay fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('editModal')"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-green-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 id="modalTitle" class="text-lg leading-6 font-medium text-gray-900">Editar Item</h3>
                            <div class="mt-4">

            <form id="editForm" class="p-6">
                <div class="mb-4">
                    <label for="editName" class="block text-sm font-medium text-gray-700 mb-2">Nombre</label>
                    <input type="text" id="editName" name="name" required
                           class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <!-- Campos adicionales solo para clientes -->
                <div id="clienteFieldsGroup" class="hidden">
                    <!-- Campo de ejecutivo asignado -->
                    <div class="mb-4">
                        <label for="editEjecutivo" class="block text-sm font-medium text-gray-700 mb-2">Ejecutivo Asignado</label>
                        <select id="editEjecutivo" name="ejecutivo_asignado_id"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                            <option value="">Sin asignar</option>
                            @foreach($todosEjecutivos as $ejecutivo)
                            <option value="{{ $ejecutivo->id }}">{{ $ejecutivo->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Campo de periodicidad -->
                    <div class="mb-4">
                        <label for="editPeriodicidad" class="block text-sm font-medium text-gray-700 mb-2">Periodicidad de Reporte <span class="text-gray-500 text-xs">(Opcional)</span></label>
                        <select id="editPeriodicidad" name="periodicidad_tipo" onchange="togglePeriodicidadOptions()"
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                            <option value="Diario">Diario (Lunes a Viernes)</option>
                            <option value="Tri-semanal">Tri-semanal (Lunes, Miércoles, Viernes)</option>
                            <option value="Semanal">Semanal (Elegir día)</option>
                        </select>
                        
                        <!-- Opciones adicionales para semanal -->
                        <div id="opciones-semanal" class="mt-2 hidden">
                            <label class="block text-xs font-medium text-gray-600 mb-1">Día de la semana</label>
                            <select id="dia-semanal" name="dia_semanal" class="w-full px-2 py-1 text-sm border border-gray-300 rounded focus:ring-1 focus:ring-blue-400 bg-white">
                                <option value="lunes">Lunes</option>
                            </select>
                        </div>
                        
                        <p class="text-xs text-gray-500 mt-2">
                            <span id="periodicidad-help">Los reportes se envían solo en días hábiles (L-V)</span>
                        </p>
                    </div>

                    <!-- Campo de correos -->
                    <div class="mb-4">
                        <label for="editCorreos" class="block text-sm font-medium text-gray-700 mb-2">Correos del Cliente <span class="text-gray-500 text-xs">(Opcional - separados por comas)</span></label>
                        <textarea id="editCorreos" name="correos_string" rows="2"
                                 placeholder="correo1@empresa.com, correo2@empresa.com"
                                 class="form-input w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 resize-none"></textarea>
                        <p class="text-xs text-gray-500 mt-1">Separe múltiples correos con comas</p>
                    </div>
                </div>

                <div class="modal-footer flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" class="btn-cancel px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" id="submitEditBtn" class="btn-primary px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        Guardar
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Eliminación -->
    <div id="deleteModal" class="modal-overlay fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('deleteModal')"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
                <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                <h3 class="text-lg font-semibold text-gray-900">Confirmar Eliminación</h3>
            </div>

            <div class="p-6">
                <div class="flex items-center mb-4">
                    <div class="flex-shrink-0">
                        <svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p id="deleteMessage" class="text-sm text-gray-700">
                            ¿Estás seguro de que deseas eliminar este elemento?
                        </p>
                    </div>
                </div>

                <div class="modal-footer flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" class="btn-cancel px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        Cancelar
                    </button>
                    <button type="button" id="confirmDeleteBtn" class="btn-delete px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg transition-colors">
                        Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Asignación de Ejecutivo -->
    <div id="assignExecutiveModal" class="modal-overlay fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeModal('assignExecutiveModal')"></div>
            
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            
            <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-md sm:w-full">
        <div class="modal-content bg-white rounded-2xl shadow-2xl w-full max-w-md transform scale-95 transition-all duration-300">
            <div class="modal-header p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Asignar Ejecutivo a Clientes</h3>
            </div>

            <form id="assignExecutiveForm" class="p-6">
                <div class="mb-4">
                    <p class="text-sm text-gray-600 mb-4">
                        Se asignarán <span id="selectedClientsCount" class="font-semibold text-blue-600">0</span> clientes seleccionados al ejecutivo elegido.
                    </p>

                    <label for="selectEjecutivo" class="block text-sm font-medium text-gray-700 mb-2">Seleccionar Ejecutivo</label>
                    <select id="selectEjecutivo" name="ejecutivo_id" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white">
                        <option value="">Seleccione un ejecutivo...</option>
                        @foreach($todosEjecutivos as $ejecutivo)
                        <option value="{{ $ejecutivo->id }}">{{ $ejecutivo->nombre }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="modal-footer flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" class="btn-cancel px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" class="btn-primary px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                        Asignar Ejecutivo
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal de Importación de Aduanas -->
    <div id="importAduanasModal" class="modal-overlay fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="modal-content bg-white rounded-2xl shadow-2xl w-full max-w-lg transform scale-95 transition-all duration-300">
            <div class="modal-header p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Importar Aduanas desde Word</h3>
                <p class="text-sm text-gray-600 mt-1">Sube un archivo .xlsx con la información de las aduanas</p>
            </div>

            <form id="importAduanasForm" class="p-6" enctype="multipart/form-data">
                <div class="mb-6">
                    <label for="ADUANASFile" class="block text-sm font-medium text-gray-700 mb-2">Archivo de Aduanas</label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-400 transition-colors">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="mt-4">
                            <label for="ADUANASFile" class="cursor-pointer">
                                <span class="mt-2 block text-sm font-medium text-gray-900">
                                    Selecciona un archivo de aduanas o arrastra aquí
                                </span>
                                <input id="ADUANASFile" name="file" type="file" class="sr-only" accept=".xlsx,.xls" required>
                            </label>
                            <p class="mt-1 text-xs text-gray-500">
                                Formatos compatibles: Word (.docx), Excel (.xlsx), CSV (.csv)
                            </p>
                        </div>
                        <div id="selectedFileName" class="mt-3 text-sm text-green-600 hidden"></div>
                    </div>
                    <div id="selectedFileName" class="mt-2 text-sm text-gray-600 hidden"></div>
                </div>

                <div class="bg-blue-50 p-4 rounded-lg mb-6">
                    <h4 class="text-sm font-medium text-blue-800 mb-2">Formato esperado:</h4>
                    <div class="text-xs text-blue-700">
                        <p>• Código aduana (2 dígitos) + espacio + sección (1 dígito) + espacio + denominación</p>
                        <p>• Ejemplo: <code class="bg-blue-100 px-1 rounded">01 0 Aduana de Tijuana</code></p>
                    </div>
                </div>

                <div class="modal-footer flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" class="btn-cancel px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" id="importADUANASBtn" class="btn-primary px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        <span class="import-text">Importar Aduanas</span>
                        <span class="loading-text hidden">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Importando...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para Añadir Nueva Aduana -->
    <div id="addAduanaModal" class="modal-overlay fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="modal-content bg-white rounded-2xl shadow-2xl w-full max-w-lg transform scale-95 transition-all duration-300">
            <div class="modal-header p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Añadir Nueva Aduana</h3>
                <p class="text-sm text-gray-600 mt-1">Completa los datos de la nueva aduana</p>
            </div>

            <form id="addAduanaForm" class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="aduanaCodigo" class="block text-sm font-medium text-gray-700 mb-2">Código de Aduana *</label>
                        <input type="text" id="aduanaCodigo" name="aduana" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                               placeholder="01-99" maxlength="2" pattern="[0-9]{2}">
                        <p class="text-xs text-gray-500 mt-1">2 dígitos (01-99)</p>
                    </div>
                    <div>
                        <label for="aduanaSeccion" class="block text-sm font-medium text-gray-700 mb-2">Sección</label>
                        <input type="text" id="aduanaSeccion" name="seccion" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                               placeholder="0-9" maxlength="1" value="0" pattern="[0-9]{1}">
                        <p class="text-xs text-gray-500 mt-1">1 dígito (0-9)</p>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="aduanaDenominacion" class="block text-sm font-medium text-gray-700 mb-2">Denominación *</label>
                    <input type="text" id="aduanaDenominacion" name="denominacion" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                           placeholder="Nombre completo de la aduana">
                </div>

                <div class="modal-footer flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" class="btn-cancel px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" id="saveAduanaBtn" class="btn-primary px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                        <span class="save-text">Guardar Aduana</span>
                        <span class="loading-text hidden">Guardando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para Editar Aduana -->
    <div id="editAduanaModal" class="modal-overlay fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="modal-content bg-white rounded-2xl shadow-2xl w-full max-w-lg transform scale-95 transition-all duration-300">
            <div class="modal-header p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Editar Aduana</h3>
                <p class="text-sm text-gray-600 mt-1">Modifica los datos de la aduana</p>
            </div>

            <form id="editAduanaForm" class="p-6">
                <input type="hidden" id="editAduanaId" name="id">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="editAduanaCodigo" class="block text-sm font-medium text-gray-700 mb-2">Código de Aduana *</label>
                        <input type="text" id="editAduanaCodigo" name="aduana" required 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                               placeholder="01-99" maxlength="2" pattern="[0-9]{2}">
                        <p class="text-xs text-gray-500 mt-1">2 dígitos (01-99)</p>
                    </div>
                    <div>
                        <label for="editAduanaSeccion" class="block text-sm font-medium text-gray-700 mb-2">Sección</label>
                        <input type="text" id="editAduanaSeccion" name="seccion" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                               placeholder="0-9" maxlength="1" pattern="[0-9]{1}">
                        <p class="text-xs text-gray-500 mt-1">1 dígito (0-9)</p>
                    </div>
                </div>
                <div class="mb-4">
                    <label for="editAduanaDenominacion" class="block text-sm font-medium text-gray-700 mb-2">Denominación *</label>
                    <input type="text" id="editAduanaDenominacion" name="denominacion" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                           placeholder="Nombre completo de la aduana">
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                    <div>
                        <label for="editAduanaPatente" class="block text-sm font-medium text-gray-700 mb-2">Patente</label>
                        <input type="text" id="editAduanaPatente" name="patente" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                               placeholder="Número de patente (opcional)">
                    </div>
                    <div>
                        <label for="editAduanaPais" class="block text-sm font-medium text-gray-700 mb-2">País</label>
                        <input type="text" id="editAduanaPais" name="pais" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                               placeholder="País (opcional)" value="MX">
                    </div>
                </div>

                <div class="modal-footer flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" class="btn-cancel px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" id="updateAduanaBtn" class="btn-primary px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        <span class="update-text">Actualizar Aduana</span>
                        <span class="loading-text hidden">Actualizando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para Importar Pedimentos -->
    <div id="importPedimentosModal" class="modal-overlay fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="modal-content bg-white rounded-2xl shadow-2xl w-full max-w-lg transform scale-95 transition-all duration-300">
            <div class="modal-header p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Importar Claves de Pedimentos</h3>
                <p class="text-sm text-gray-600 mt-1">Sube un archivo con las claves y descripciones de pedimentos</p>
            </div>

            <form id="importPedimentosForm" class="p-6" enctype="multipart/form-data" action="{{ route('logistica.pedimentos.import.legacy') }}" method="POST">
                @csrf
                <div class="mb-6">
                    <label for="pedimentosFile" class="block text-sm font-medium text-gray-700 mb-2">Seleccionar Archivo de Pedimentos</label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6 text-center hover:border-blue-400 transition-colors">
                        <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                            <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                        </svg>
                        <div class="mt-4">
                            <label for="pedimentosFile" class="cursor-pointer">
                                <span class="mt-2 block text-sm font-medium text-gray-900">
                                    Selecciona un archivo de pedimentos o arrastra aquí
                                </span>
                                <input id="pedimentosFile" name="pedimentos_file" type="file" class="sr-only" accept=".xlsx,.xls" required>
                            </label>
                            <p class="mt-1 text-xs text-gray-500">
                                Formatos compatibles: Excel (.xlsx, .xls)
                            </p>
                        </div>
                        <div id="selectedPedimentosFileName" class="mt-3 text-sm text-green-600 hidden"></div>
                    </div>
                </div>

                <!-- Progress bar -->
                <div id="importPedimentosProgressModal" class="hidden mb-4">
                    <div class="flex items-center mb-2">
                        <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-indigo-600 mr-2"></div>
                        <span class="text-sm text-indigo-600" id="progressPedimentosTextModal">Procesando...</span>
                    </div>
                    <div class="w-full bg-gray-200 rounded-full h-2">
                        <div class="bg-indigo-600 h-2 rounded-full transition-all duration-500" style="width: 0%" id="progressPedimentosBarModal"></div>
                    </div>
                </div>

                <div class="bg-indigo-50 p-4 rounded-lg mb-4">
                    <h4 class="text-sm font-medium text-indigo-800 mb-2">Formato esperado:</h4>
                    <div class="text-xs text-indigo-700">
                        <p>• Clave + separador + descripción</p>
                        <p>• Ejemplo: <code class="bg-indigo-100 px-1 rounded">A1 - IMPORTACIÓN O EXPORTACIÓN DEFINITIVA</code></p>
                        <p>• También: <code class="bg-indigo-100 px-1 rounded">C1, IMPORTACIÓN DEFINITIVA A LA FRANJA</code></p>
                    </div>
                </div>

                <div class="modal-footer flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeImportPedimentosModal()" class="btn-cancel px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" id="submitPedimentosBtn" class="btn-primary px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        <span class="import-text">Importar Pedimentos</span>
                        <span class="loading-text hidden">
                            <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Importando...
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para Añadir Nuevo Pedimento -->
    <div id="addPedimentoModal" class="modal-overlay fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="modal-content bg-white rounded-2xl shadow-2xl w-full max-w-lg transform scale-95 transition-all duration-300">
            <div class="modal-header p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Añadir Nuevo Pedimento</h3>
                <p class="text-sm text-gray-600 mt-1">Completa los datos del nuevo pedimento</p>
            </div>

            <form id="addPedimentoForm" class="p-6">
                <div class="mb-4">
                    <label for="pedimentoClave" class="block text-sm font-medium text-gray-700 mb-2">Clave *</label>
                    <input type="text" id="pedimentoClave" name="clave" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                           placeholder="A1, C1, etc." maxlength="10">
                    <p class="text-xs text-gray-500 mt-1">Ejemplo: A1, C1, F1, etc.</p>
                </div>
                <div class="mb-4">
                    <label for="pedimentoCategoria" class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
                    <input type="text" id="pedimentoCategoria" name="categoria"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                           placeholder="DEPOSITO FISCAL, INDUSTRIA AUTOMOTRIZ, etc." maxlength="255">
                    <p class="text-xs text-gray-500 mt-1">Opcional: Categoría principal del pedimento</p>
                </div>
                <div class="mb-4">
                    <label for="pedimentoDescripcion" class="block text-sm font-medium text-gray-700 mb-2">Descripción *</label>
                    <textarea id="pedimentoDescripcion" name="descripcion" required rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-transparent" 
                              placeholder="Descripción completa del pedimento"></textarea>
                </div>

                <div class="modal-footer flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeAddPedimentoModal()" class="btn-cancel px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" id="savePedimentoBtn" class="btn-primary px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg transition-colors">
                        <span class="save-text">Guardar Pedimento</span>
                        <span class="loading-text hidden">Guardando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal para Editar Pedimento -->
    <div id="editPedimentoModal" class="modal-overlay fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
        <div class="modal-content bg-white rounded-2xl shadow-2xl w-full max-w-lg transform scale-95 transition-all duration-300">
            <div class="modal-header p-6 border-b border-gray-200">
                <h3 class="text-lg font-semibold text-gray-900">Editar Pedimento</h3>
                <p class="text-sm text-gray-600 mt-1">Modifica los datos del pedimento</p>
            </div>

            <form id="editPedimentoForm" class="p-6">
                <input type="hidden" id="editPedimentoId" name="id">
                <div class="mb-4">
                    <label for="editPedimentoClave" class="block text-sm font-medium text-gray-700 mb-2">Clave *</label>
                    <input type="text" id="editPedimentoClave" name="clave" required 
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                           placeholder="A1, C1, etc." maxlength="10">
                    <p class="text-xs text-gray-500 mt-1">Ejemplo: A1, C1, F1, etc.</p>
                </div>
                <div class="mb-4">
                    <label for="editPedimentoCategoria" class="block text-sm font-medium text-gray-700 mb-2">Categoría</label>
                    <input type="text" id="editPedimentoCategoria" name="categoria"
                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                           placeholder="DEPOSITO FISCAL, INDUSTRIA AUTOMOTRIZ, etc." maxlength="255">
                    <p class="text-xs text-gray-500 mt-1">Opcional: Categoría principal del pedimento</p>
                </div>
                <div class="mb-4">
                    <label for="editPedimentoDescripcion" class="block text-sm font-medium text-gray-700 mb-2">Descripción *</label>
                    <textarea id="editPedimentoDescripcion" name="descripcion" required rows="3"
                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent" 
                              placeholder="Descripción completa del pedimento"></textarea>
                </div>

                <div class="modal-footer flex justify-end space-x-3 pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeEditPedimentoModal()" class="btn-cancel px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" id="updatePedimentoBtn" class="btn-primary px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors">
                        <span class="update-text">Actualizar Pedimento</span>
                        <span class="loading-text hidden">Actualizando...</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

<!-- Modal de Confirmación Reutilizable -->
<div id="confirmModal" class="modal-overlay fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="modal-content bg-white rounded-2xl shadow-2xl max-w-md w-full mx-4 transform scale-95 transition-all duration-300">
        <div class="p-6">
            <!-- Header -->
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center">
                    <div class="w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mr-3">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.268 19.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <h3 id="confirmModalTitle" class="text-lg font-semibold text-gray-900">Confirmar Acción</h3>
                </div>
                <button onclick="closeConfirmModal()" class="text-gray-400 hover:text-gray-600 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>

            <!-- Mensaje -->
            <div class="mb-6">
                <p id="confirmModalMessage" class="text-gray-700"></p>
            </div>

            <!-- Botones -->
            <div class="flex justify-end space-x-3">
                <button onclick="closeConfirmModal()" class="px-4 py-2 text-gray-500 hover:text-gray-700 font-medium transition-colors">
                    Cancelar
                </button>
                <button id="confirmModalBtn" class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors">
                    <span class="confirm-text">Eliminar</span>
                    <span class="loading-text hidden">
                        <svg class="animate-spin -ml-1 mr-2 h-4 w-4 text-white inline" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        Eliminando...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>
<!-- /Modal de Confirmación -->

<!-- Modal de Búsqueda de Empleados -->
@if(auth()->user() && auth()->user()->hasRole('admin'))
<div id="searchEmployeeModal" class="modal-overlay fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4">
    <div class="modal-content bg-white rounded-2xl shadow-2xl w-full max-w-4xl transform scale-95 transition-all duration-300">
        <div class="modal-header p-6 border-b border-gray-200">
            <h3 class="text-lg font-semibold text-gray-900">Buscar Empleado para Agregar como Ejecutivo</h3>
            <p class="text-sm text-gray-600 mt-1">Busca empleados en la base de datos para agregarlos como ejecutivos de logística</p>
        </div>

        <div class="p-6">
            <!-- Barra de búsqueda -->
            <div class="mb-6">
                <div class="relative">
                    <input type="text" id="employeeSearchInput" placeholder="Buscar por nombre, ID empleado o correo..." 
                           class="w-full px-4 py-3 pl-10 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center">
                        <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </div>
                </div>
            </div>

            <!-- Resultados de búsqueda -->
            <div id="searchResults" class="hidden">
                <h4 class="text-sm font-medium text-gray-700 mb-3">Resultados de búsqueda:</h4>
                <div id="searchResultsList" class="max-h-96 overflow-y-auto border border-gray-200 rounded-lg">
                    <!-- Los resultados se cargarán aquí dinámicamente -->
                </div>
            </div>

            <!-- Loading -->
            <div id="searchLoading" class="hidden text-center py-8">
                <svg class="animate-spin h-8 w-8 mx-auto text-indigo-600" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <p class="text-sm text-gray-600 mt-2">Buscando empleados...</p>
            </div>

            <!-- Estado inicial -->
            <div id="searchInitialState" class="text-center py-12">
                <svg class="w-16 h-16 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <p class="text-gray-500">Escribe en la barra de búsqueda para encontrar empleados</p>
            </div>
        </div>

        <div class="modal-footer flex justify-end space-x-3 p-6 border-t border-gray-200">
            <button type="button" onclick="closeSearchEmployeeModal()" class="btn-cancel px-4 py-2 text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors">
                Cerrar
            </button>
        </div>
    </div>
</div>
@endif

<!-- Modal de Alerta (Reemplazo de alert) -->
<div id="modalAlert" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-[9999] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full transform transition-all">
        <div class="p-6">
            <div class="flex items-center gap-4 mb-4">
                <div id="modalAlertIcon" class="flex-shrink-0">
                    <!-- Icon will be inserted here -->
                </div>
                <h3 id="modalAlertTitle" class="text-xl font-semibold text-slate-900"></h3>
            </div>
            <p id="modalAlertMessage" class="text-slate-600 mb-6"></p>
            <div class="flex justify-end">
                <button onclick="cerrarModalAlert()" class="px-6 py-2.5 bg-blue-600 text-white rounded-xl hover:bg-blue-700 transition-colors font-medium">
                    Aceptar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Confirmación (Reemplazo de confirm) -->
<div id="modalConfirm" class="hidden fixed inset-0 bg-black/50 backdrop-blur-sm z-[9999] flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full transform transition-all">
        <div class="p-6">
            <div class="flex items-center gap-4 mb-4">
                <div class="flex-shrink-0">
                    <svg class="w-12 h-12 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <h3 id="modalConfirmTitle" class="text-xl font-semibold text-slate-900">Confirmar acción</h3>
            </div>
            <p id="modalConfirmMessage" class="text-slate-600 mb-6"></p>
            <div class="flex justify-end gap-3">
                <button onclick="cerrarModalConfirm(false)" class="px-6 py-2.5 bg-slate-200 text-slate-700 rounded-xl hover:bg-slate-300 transition-colors font-medium">
                    Cancelar
                </button>
                <button id="modalConfirmBtn" class="px-6 py-2.5 bg-red-600 text-white rounded-xl hover:bg-red-700 transition-colors font-medium">
                    Confirmar
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script src="{{ asset('js/Logistica/catalogos.js') }}?v={{ time() }}"></script>
@endpush
