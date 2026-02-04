@extends('layouts.erp')
@section('title','Expediente de ' . $empleado->nombre)

@section('content')
<main class="max-w-7xl mx-auto py-8 px-4 sm:px-6 lg:px-8 relative" x-data="{ activeTab: 'documentos' }">
    
    {{-- HEADER SUPERIOR (Siempre visible) --}}
    <div class="mb-6 flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div class="flex items-center gap-4">
            @if($empleado->foto_path)
                <img src="{{ asset('storage/'.$empleado->foto_path) }}" class="w-16 h-16 rounded-full object-cover border-2 border-white shadow-md">
            @else
                <div class="w-16 h-16 rounded-full bg-blue-100 flex items-center justify-center text-blue-600 font-bold text-xl shadow-sm">
                    {{ substr($empleado->nombre, 0, 1) }}
                </div>
            @endif
            <div>
                <h1 class="text-2xl font-bold text-slate-800">{{ $empleado->nombre }} {{ $empleado->apellido_paterno }}</h1>
                <p class="text-sm text-slate-500">{{ $empleado->posicion }} • {{ $empleado->area }}</p>
                <div class="flex items-center gap-2 mt-1">
                    <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $empleado->es_activo ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $empleado->es_activo ? 'ACTIVO' : 'BAJA' }}
                    </span>
                    <button onclick="openEditModal()" class="text-[10px] bg-slate-100 hover:bg-slate-200 text-slate-600 px-2 py-0.5 rounded border border-slate-300 transition flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/></svg>
                        Editar Info
                    </button>
                </div>
            </div>
        </div>
        
        <div class="flex gap-2">
            <a href="{{ route('rh.expedientes.index') }}" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-700 text-sm font-medium hover:bg-slate-50 shadow-sm transition">Volver</a>
        </div>
    </div>

    {{-- NAVEGACIÓN DE PESTAÑAS --}}
    <div class="mb-6 border-b border-slate-200">
        <nav class="-mb-px flex space-x-8" aria-label="Tabs">
            <button @click="activeTab = 'general'"
                :class="activeTab === 'general' ? 'border-blue-500 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                📋 Datos Generales
            </button>

            <button @click="activeTab = 'documentos'"
                :class="activeTab === 'documentos' ? 'border-blue-500 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                📂 Documentos
                <span class="ml-2 bg-slate-100 text-slate-600 py-0.5 px-2 rounded-full text-xs">
                    {{ $empleado->documentos->count() }}
                </span>
            </button>

            <button @click="activeTab = 'asistencias'"
                :class="activeTab === 'asistencias' ? 'border-blue-500 text-blue-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'"
                class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                ⏰ Asistencias
            </button>
        </nav>
    </div>

    {{-- CONTENIDO DE PESTAÑAS --}}
    
    {{-- TAB 1: DATOS GENERALES --}}
    <div x-show="activeTab === 'general'" x-transition:enter.duration.300ms>
        
        {{-- ALERTA INTELIGENTE DE FALTANTES (Restaurada) --}}
        @php
            $faltantes = [];
            if(empty($empleado->telefono)) $faltantes[] = 'Teléfono Celular';
            if(empty($empleado->direccion)) $faltantes[] = 'Domicilio';
            if(empty($empleado->contacto_emergencia_nombre)) $faltantes[] = 'Nombre Emergencia';
            
            if($empleado->es_practicante) {
                if(empty($empleado->curp)) $faltantes[] = 'CURP';
            } else {
                if(empty($empleado->nss)) $faltantes[] = 'NSS';
                if(empty($empleado->rfc)) $faltantes[] = 'RFC';
                if(empty($empleado->curp)) $faltantes[] = 'CURP';
            }
        @endphp

        @if(count($faltantes) > 0)
        <div class="mb-6 rounded-xl border border-red-200 bg-red-50 p-4 shadow-sm">
            <div class="flex items-start gap-4">
                <div class="p-2 bg-red-100 rounded-full text-red-600">
                    <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-bold text-red-800">Perfil Incompleto</h3>
                    <ul class="mt-2 list-disc list-inside text-xs text-red-700 font-medium grid grid-cols-1 sm:grid-cols-2 gap-1">
                        @foreach($faltantes as $falta)
                            <li>{{ $falta }}</li>
                        @endforeach
                    </ul>
                    <button onclick="openEditModal()" class="mt-3 text-xs bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded font-bold transition">
                        Completar Datos
                    </button>
                </div>
            </div>
        </div>
        @endif

        {{-- Resumen de Datos --}}
        <div class="bg-white rounded-xl border border-slate-200 shadow-sm p-6">
            <h3 class="font-bold text-slate-800 mb-4">Información Personal</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Contacto</label>
                    <p class="text-sm text-slate-800 mt-1">{{ $empleado->telefono ?? '--' }}</p>
                    <p class="text-sm text-slate-600">{{ $empleado->correo }}</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Domicilio</label>
                    <p class="text-sm text-slate-800 mt-1">{{ $empleado->direccion ?? 'No registrado' }}</p>
                    <p class="text-sm text-slate-600">{{ $empleado->ciudad }}</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Fiscal</label>
                    <p class="text-xs text-slate-600 mt-1">RFC: {{ $empleado->rfc ?? '--' }}</p>
                    <p class="text-xs text-slate-600">CURP: {{ $empleado->curp ?? '--' }}</p>
                    <p class="text-xs text-slate-600">NSS: {{ $empleado->nss ?? '--' }}</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase">Emergencia</label>
                    <div class="mt-1 bg-red-50 border border-red-100 p-2 rounded text-xs text-slate-700">
                        <strong>{{ $empleado->contacto_emergencia_nombre ?? 'Sin contacto' }}</strong><br>
                        Tel: {{ $empleado->contacto_emergencia_numero ?? '--' }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- TAB 2: DOCUMENTOS (Toda tu lógica original aquí) --}}
    <div x-show="activeTab === 'documentos'" x-transition:enter.duration.300ms style="display: none;">
        
        {{-- BARRA DE PROGRESO --}}
        @php 
            $porcentaje = $empleado->porcentaje_expediente;
            $color = $porcentaje < 50 ? 'bg-red-500' : ($porcentaje < 90 ? 'bg-yellow-500' : 'bg-green-500');
        @endphp
        <div class="mb-6 bg-white p-4 rounded-xl border border-slate-200 shadow-sm">
            <div class="flex justify-between items-center mb-2">
                <span class="text-xs font-bold text-slate-500">Progreso del Expediente</span>
                <span class="text-sm font-bold {{ $porcentaje < 90 ? 'text-blue-600' : 'text-green-600' }}">{{ $porcentaje }}%</span>
            </div>
            <div class="w-full bg-slate-100 rounded-full h-2">
                <div class="{{ $color }} h-2 rounded-full transition-all duration-1000" style="width: {{ $porcentaje }}%"></div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            {{-- COLUMNA IZQUIERDA: Herramientas (Restauradas) --}}
            <div class="lg:col-span-1 space-y-6">
                
                {{-- Switch Tipo Empleado --}}
                <div class="bg-white rounded-xl border border-slate-200 p-4 shadow-sm flex items-center justify-between">
                    <div>
                        <h4 class="text-sm font-bold text-slate-700">Tipo</h4>
                        <p class="text-[10px] text-slate-400">Define requisitos.</p>
                    </div>
                    <form action="{{ route('rh.expedientes.update', $empleado->id) }}" method="POST" id="form-toggle-tipo">
                        @csrf @method('PUT')
                        <input type="hidden" name="toggle_practicante" value="1">
                        <input type="hidden" name="es_practicante" value="0">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" name="es_practicante" value="1" class="sr-only peer" onchange="document.getElementById('form-toggle-tipo').submit()" {{ $empleado->es_practicante ? 'checked' : '' }}>
                            <div class="w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </form>
                </div>

                {{-- Carga Masiva (TU CÓDIGO RESTAURADO) --}}
                <div class="bg-indigo-50 border border-indigo-100 rounded-xl p-5 shadow-sm">
                    <div class="flex items-center justify-between mb-2">
                        <h3 class="font-bold text-indigo-900 flex items-center gap-2 text-sm">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                            Auto-Indexar Carpeta
                        </h3>
                        <span class="text-[10px] font-bold bg-indigo-200 text-indigo-800 px-1.5 py-0.5 rounded">BETA</span>
                    </div>
                    <div class="relative group">
                        <input type="file" id="folderInput" webkitdirectory directory multiple class="absolute inset-0 w-full h-full opacity-0 cursor-pointer z-10" />
                        <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold py-2 rounded-lg shadow transition flex justify-center items-center gap-2">
                            Seleccionar Carpeta
                        </button>
                    </div>
                    <div id="loading-indicator" class="hidden mt-3 text-center">
                        <span class="flex items-center justify-center gap-2 text-xs font-bold text-indigo-800 animate-pulse">
                            Procesando...
                        </span>
                    </div>
                    <ul id="results-list" class="mt-3 space-y-1 text-[10px] max-h-32 overflow-y-auto custom-scrollbar"></ul>
                </div>

                {{-- Carga Manual --}}
                <div class="bg-blue-50 rounded-xl border border-blue-100 p-5 shadow-sm">
                    <h3 class="font-bold text-blue-900 mb-4 flex items-center gap-2 text-sm">
                        <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                        Subir Manual
                    </h3>
                    <form action="{{ route('rh.expedientes.upload', $empleado->id) }}" method="POST" enctype="multipart/form-data" class="space-y-3">
                        @csrf
                        <div>
                            <select name="nombre" class="w-full text-xs rounded-lg border-blue-200 focus:ring-blue-500 bg-white py-1.5">
                                <option value="">-- Seleccionar Requisito --</option>
                                @foreach($checklistDocs as $doc)
                                    <option value="{{ $doc }}">{{ $doc }}</option>
                                @endforeach
                                <option value="Otro">Otro (Escribir manual)</option>
                            </select>
                        </div>
                        <div>
                            <select name="categoria" class="w-full text-xs rounded-lg border-blue-200 focus:ring-blue-500 bg-white py-1.5">
                                <option value="Identificación">Identificación</option>
                                <option value="Legal">Legal / Contratos</option>
                                <option value="Fiscal">Fiscal / IMSS</option>
                                <option value="Académico">Académico</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <input type="file" name="documento" required accept=".pdf,.jpg,.jpeg,.png,.xlsx,.xls,.csv,.doc,.docx" class="block w-full text-[10px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-full file:border-0 file:text-[10px] file:bg-blue-100 file:text-blue-700 hover:file:bg-blue-200"/>
                        <button type="submit" class="w-full bg-blue-600 text-white rounded-lg py-2 text-xs font-bold shadow hover:bg-blue-700 transition">Guardar</button>
                    </form>
                </div>

                {{-- Importar Excel (Restaurado) --}}
                <div class="bg-white border border-slate-200 rounded-lg p-4 shadow-sm">
                     <h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Formato ID (Excel)</h4>
                     <form action="{{ route('rh.expedientes.import-excel', $empleado->id) }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-2">
                        @csrf
                        <input type="file" name="archivo_excel" required accept=".xlsx,.xls,.csv" class="block w-full text-[10px] text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200"/>
                        <button type="submit" class="w-full px-3 py-1.5 bg-slate-800 text-white text-xs font-medium rounded hover:bg-slate-700 transition">Importar Datos</button>
                     </form>
                </div>

                {{-- Checklist Status --}}
                <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                    <div class="bg-slate-50 px-4 py-3 border-b border-slate-200">
                        <h3 class="font-semibold text-slate-800 text-sm">Checklist</h3>
                    </div>
                    <div class="p-4 space-y-2 max-h-60 overflow-y-auto">
                        @foreach($checklistDocs as $reqDoc)
                            @php
                                $subido = $empleado->documentos->contains(function($doc) use ($reqDoc) {
                                    $kwd = Str::lower($reqDoc);
                                    if(Str::contains($kwd, 'titulo')) return Str::contains(Str::lower($doc->nombre), ['titulo', 'cedula', 'pasante']);
                                    if(Str::contains($kwd, 'nss')) return Str::contains(Str::lower($doc->nombre), ['nss', 'imss', 'seguro']);
                                    return Str::contains(Str::lower($doc->nombre), $kwd);
                                });
                            @endphp
                            <div class="flex items-center justify-between text-sm">
                                <span class="{{ $subido ? 'text-slate-500 line-through' : 'text-slate-800 font-medium' }}">{{ $reqDoc }}</span>
                                <span class="text-xs {{ $subido ? 'text-green-600 font-bold' : 'text-red-400' }}">
                                    {{ $subido ? 'OK' : 'Falta' }}
                                </span>
                            </div>
                        @endforeach
                    </div>
                </div>

            </div>

            {{-- COLUMNA DERECHA: Lista de Documentos --}}
            <div class="lg:col-span-2 space-y-6">
                @if($docsGrouped->isEmpty())
                    <div class="bg-white rounded-xl border-2 border-dashed border-slate-300 p-12 text-center h-64 flex flex-col items-center justify-center">
                        <p class="text-slate-500 mt-1 max-w-xs mx-auto text-sm">Expediente Vacío. Usa el panel izquierdo para subir archivos.</p>
                    </div>
                @else
                    @foreach($docsGrouped as $categoria => $docs)
                        <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden hover:shadow-md transition duration-300">
                            <div class="bg-slate-50 px-6 py-3 border-b border-slate-200 flex justify-between items-center">
                                <h3 class="font-bold text-slate-700 text-sm">{{ $categoria }}</h3>
                                <span class="bg-white border border-slate-200 px-2 py-0.5 rounded-full text-[10px] font-bold text-slate-500">{{ count($docs) }}</span>
                            </div>
                            <ul class="divide-y divide-slate-100">
                                @foreach($docs as $doc)
                                    <li class="px-6 py-3 flex flex-col sm:flex-row sm:items-center justify-between hover:bg-slate-50 transition group gap-4">
                                        <div class="flex items-center gap-3">
                                            <div class="bg-blue-50 text-blue-600 p-2 rounded-lg">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-bold text-slate-800">{{ $doc->nombre }}</p>
                                                <p class="text-[10px] text-slate-400">{{ $doc->created_at->format('d/m/Y') }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            @if(Route::has('rh.expedientes.download'))
                                                <a href="{{ route('rh.expedientes.download', $doc->id) }}" target="_blank" class="px-3 py-1 bg-white border border-slate-200 rounded text-xs text-slate-600 hover:text-blue-600">Ver</a>
                                            @else
                                                 <a href="{{ asset('storage/'.$doc->ruta_archivo) }}" target="_blank" class="px-3 py-1 bg-white border border-slate-200 rounded text-xs text-slate-600 hover:text-blue-600">Ver</a>
                                            @endif
                                            
                                            <form action="{{ route('rh.expedientes.delete-doc', $doc->id) }}" method="POST" onsubmit="return confirm('¿Eliminar?')">
                                                @csrf @method('DELETE')
                                                <button type="submit" class="px-3 py-1 bg-white border border-slate-200 rounded text-xs text-slate-600 hover:text-red-600">X</button>
                                            </form>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>
    </div>

    {{-- TAB 3: ASISTENCIAS (Nuevo Módulo) --}}
    <div x-show="activeTab === 'asistencias'" x-transition:enter.duration.300ms style="display: none;">
        <div class="flex justify-between items-center mb-4">
             <h3 class="text-lg font-medium leading-6 text-slate-900">Historial de Asistencias</h3>
             <a href="{{ route('rh.reloj.index') }}" class="text-sm text-blue-600 hover:underline font-bold">Ir al Reloj Checador &rarr;</a>
        </div>
        
        @if($empleado->asistencias && $empleado->asistencias->count() > 0)
            <div class="bg-white rounded-xl border border-slate-200 shadow-sm overflow-hidden">
                <table class="min-w-full divide-y divide-slate-200">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Fecha</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Entrada</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Salida</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-slate-500 uppercase tracking-wider">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-slate-200">
                        @foreach($empleado->asistencias->sortByDesc('fecha')->take(10) as $asistencia)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">
                                    {{ \Carbon\Carbon::parse($asistencia->fecha)->translatedFormat('d M Y') }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $asistencia->entrada ?? '--:--' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-500">{{ $asistencia->salida ?? '--:--' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if($asistencia->es_retardo)
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">Retardo</span>
                                    @elseif($asistencia->tipo_registro == 'falta')
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">Falta</span>
                                    @else
                                        <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">Puntual</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <div class="text-center py-10 bg-slate-50 rounded-lg border border-dashed border-slate-300">
                <p class="text-slate-500">No hay registros de asistencia recientes.</p>
            </div>
        @endif
    </div>

</main>

{{-- MODAL DE EDICIÓN (Tu código original preservado) --}}
<div id="editModal" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" aria-hidden="true" onclick="closeEditModal()"></div>
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                <div class="flex justify-between items-center mb-5 border-b pb-2 border-slate-100">
                    <h3 class="text-lg leading-6 font-bold text-slate-900" id="modal-title">Editar Información Completa</h3>
                    <button type="button" onclick="closeEditModal()" class="text-slate-400 hover:text-slate-500">
                        <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                {{-- Formulario original --}}
                <form method="POST" action="{{ route('rh.expedientes.update',$empleado) }}" id="editForm" class="space-y-6">
                    @csrf @method('PUT')
                    {{-- 1. Datos Corporativos --}}
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-200">
                        <h4 class="text-xs font-bold text-blue-900 uppercase tracking-wider mb-3">Corporativo</h4>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 mb-1">Nombre</label>
                                <input type="text" value="{{ $empleado->nombre }}" disabled class="w-full rounded-lg border-slate-200 bg-slate-100 text-xs" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 mb-1">ID Empleado</label>
                                <input type="text" name="id_empleado" value="{{ $empleado->id_empleado }}" class="w-full rounded-lg border-slate-300 text-xs focus:ring-blue-500" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 mb-1">Puesto</label>
                                <input type="text" name="posicion" value="{{ $empleado->posicion }}" class="w-full rounded-lg border-slate-300 text-xs focus:ring-blue-500" />
                            </div>
                        </div>
                    </div>
                    {{-- 2. Datos Personales --}}
                    <div>
                         <h4 class="text-xs font-bold text-indigo-900 uppercase tracking-wider mb-3">Información Personal</h4>
                         <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <label class="block text-[10px] font-bold text-slate-500 mb-1">Domicilio Completo</label>
                                <input type="text" name="direccion" value="{{ $empleado->direccion }}" class="w-full rounded-lg border-slate-300 text-xs focus:ring-indigo-500" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 mb-1">Ciudad</label>
                                <input type="text" name="ciudad" value="{{ $empleado->ciudad }}" class="w-full rounded-lg border-slate-300 text-xs focus:ring-indigo-500" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 mb-1">Teléfono</label>
                                <input type="text" name="telefono" value="{{ $empleado->telefono }}" required class="w-full rounded-lg border-slate-300 text-xs focus:ring-indigo-500" />
                            </div>
                         </div>
                    </div>
                    {{-- 3. Fiscales --}}
                    <div>
                         <h4 class="text-xs font-bold text-green-900 uppercase tracking-wider mb-3">Datos Fiscales</h4>
                         <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 mb-1">CURP</label>
                                <input type="text" name="curp" value="{{ $empleado->curp }}" required class="w-full rounded-lg border-slate-300 text-xs focus:ring-green-500 uppercase" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 mb-1">RFC</label>
                                <input type="text" name="rfc" value="{{ $empleado->rfc }}" class="w-full rounded-lg border-slate-300 text-xs focus:ring-green-500 uppercase" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 mb-1">NSS</label>
                                <input type="text" name="nss" value="{{ $empleado->nss }}" class="w-full rounded-lg border-slate-300 text-xs focus:ring-green-500" />
                            </div>
                         </div>
                    </div>
                    {{-- 4. Emergencia --}}
                    <div class="bg-red-50 p-4 rounded-xl border border-red-100">
                         <h4 class="text-xs font-bold text-red-900 uppercase tracking-wider mb-3">Emergencia</h4>
                         <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 mb-1">Contacto</label>
                                <input type="text" name="contacto_emergencia_nombre" value="{{ $empleado->contacto_emergencia_nombre }}" required class="w-full rounded-lg border-slate-300 text-xs focus:ring-red-500" />
                            </div>
                            <div>
                                <label class="block text-[10px] font-bold text-slate-500 mb-1">Teléfono</label>
                                <input type="text" name="contacto_emergencia_numero" value="{{ $empleado->contacto_emergencia_numero }}" required class="w-full rounded-lg border-slate-300 text-xs focus:ring-red-500" />
                            </div>
                         </div>
                    </div>
                    <div class="pt-4 flex justify-end gap-3">
                        <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-white border border-slate-300 rounded-lg text-slate-700 text-sm font-medium hover:bg-slate-50">Cancelar</button>
                        <button type="submit" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-bold shadow">Guardar Cambios</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- JAVASCRIPT: Lógica de Carga Inteligente Preservada --}}
<script>
    function openEditModal() { document.getElementById('editModal').classList.remove('hidden'); }
    function closeEditModal() { document.getElementById('editModal').classList.add('hidden'); }

    document.addEventListener('DOMContentLoaded', () => {
        const folderInput = document.getElementById('folderInput');
        const resultsList = document.getElementById('results-list');
        const loader = document.getElementById('loading-indicator');
        
        const reglas = [
            { keys: ['ine', 'ife', 'identificacion', 'id_oficial'], name: 'INE', cat: 'Identificación' },
            { keys: ['curp'], name: 'CURP', cat: 'Identificación' },
            { keys: ['domicilio', 'agua', 'luz', 'cfe', 'predial'], name: 'Comprobante de Domicilio', cat: 'Identificación' },
            { keys: ['nss', 'imss', 'seguro', 'social'], name: 'NSS', cat: 'Fiscal' },
            { keys: ['rfc', 'csf', 'situacion', 'fiscal', 'constancia'], name: 'Constancia de Situacion Fiscal', cat: 'Fiscal' },
            { keys: ['nacimiento', 'acta'], name: 'Acta de Nacimiento', cat: 'Identificación' },
            { keys: ['titulo', 'cedula', 'profesional'], name: 'Titulo', cat: 'Académico' },
            { keys: ['contrato', 'laboral'], name: 'Contrato', cat: 'Legal' },
            { keys: ['cuenta', 'banco', 'clabe', 'santander', 'bbva'], name: 'Estado de Cuenta', cat: 'Administrativo' },
            { keys: ['formato', 'ficha', 'ingreso', 'xls', 'xlsx'], name: 'Formato ID', cat: 'Interno' }
        ];

        if(folderInput) {
            folderInput.addEventListener('change', async function(e) {
                const files = Array.from(e.target.files).filter(f => !f.name.startsWith('.'));
                if(files.length === 0) return;

                if(!confirm(`Se encontraron ${files.length} archivos. ¿Procesar carga inteligente?`)) {
                    this.value = ''; return;
                }

                loader.classList.remove('hidden');
                resultsList.innerHTML = '';
                let subidos = 0;

                for (let file of files) {
                    let filename = file.name.toLowerCase();
                    let match = reglas.find(r => r.keys.some(k => filename.includes(k)));

                    if (match) {
                        if(match.name === 'Formato ID' && (filename.endsWith('xls') || filename.endsWith('xlsx'))) {
                             const exito = await subirExcel(file);
                             agregarLog(file.name, 'Formato ID (Procesado)', exito);
                             if(exito) subidos++;
                        } else {
                             const exito = await subirArchivo(file, match.name, match.cat);
                             agregarLog(file.name, match.name, exito);
                             if(exito) subidos++;
                        }
                    } else {
                        agregarLog(file.name, 'No reconocido', false);
                    }
                }

                loader.classList.add('hidden');
                setTimeout(() => { 
                    alert(`Proceso finalizado. ${subidos} documentos importados.`);
                    window.location.reload(); 
                }, 500);
            });
        }

        async function subirArchivo(file, nombreDoc, categoria) {
            let formData = new FormData();
            formData.append('documento', file);
            formData.append('nombre', nombreDoc);
            formData.append('categoria', categoria);
            formData.append('_token', '{{ csrf_token() }}'); 
            try {
                let r = await fetch("{{ route('rh.expedientes.upload', $empleado->id) }}", { method: 'POST', body: formData });
                return r.ok;
            } catch (e) { return false; }
        }

        async function subirExcel(file) {
            let formData = new FormData();
            formData.append('archivo_excel', file);
            formData.append('_token', '{{ csrf_token() }}');
            try {
                let r = await fetch("{{ route('rh.expedientes.import-excel', $empleado->id) }}", { method: 'POST', body: formData });
                return r.ok;
            } catch (e) { return false; }
        }

        function agregarLog(archivo, resultado, exito) {
            const li = document.createElement('li');
            li.className = exito ? 'text-green-600 flex items-center gap-2' : 'text-slate-400 flex items-center gap-2 italic opacity-60';
            li.innerHTML = `<span class="${exito?'bg-green-100 text-green-700':'bg-slate-100 text-slate-500'} px-1 rounded font-bold text-[9px]">${exito?'OK':'SKIP'}</span> <span class="truncate max-w-[150px]">${archivo}</span> ➜ ${resultado}`;
            resultsList.prepend(li);
        }
    });
</script>
@endsection