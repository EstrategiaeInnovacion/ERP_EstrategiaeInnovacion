@extends('layouts.erp')

@section('content')
<div class="container mx-auto px-4 py-6">
    <!-- Botón regresar -->
    <a href="{{ route('logistica.catalogos') }}" class="inline-flex items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm text-slate-600 hover:bg-slate-50 hover:text-slate-800 shadow-sm transition-all duration-200 mb-4">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
        </svg>
        Regresar al Catálogo
    </a>
    
    <div class="bg-white rounded-lg shadow-md">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Catálogo de Correos CC</h1>
                    <p class="text-gray-600">Gestiona los correos que se incluirán en copia (CC) al enviar reportes</p>
                </div>
                <a href="{{ route('logistica.correos-cc.create') }}" 
                   class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded-lg transition duration-300">
                    <i class="fas fa-plus mr-2"></i>Agregar Correo CC
                </a>
            </div>
        </div>

        <!-- Alertas -->
        @if(session('success'))
            <div class="mx-6 mt-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                {{ session('success') }}
            </div>
        @endif

        <!-- Tabla de correos CC -->
        <div class="p-6">
            <div class="overflow-x-auto">
                <table class="min-w-full bg-white">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Nombre
                            </th>
                            <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Email
                            </th>
                            <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Tipo
                            </th>
                            <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Descripción
                            </th>
                            <th class="py-3 px-6 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Estado
                            </th>
                            <th class="py-3 px-6 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                                Acciones
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @forelse($correos as $correo)
                            <tr class="hover:bg-gray-50" data-correo-id="{{ $correo->id }}">
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900">{{ $correo->nombre }}</div>
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <div class="text-sm text-gray-900">{{ $correo->email }}</div>
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full
                                        @if($correo->tipo === 'administrador') bg-red-100 text-red-800
                                        @elseif($correo->tipo === 'supervisor') bg-yellow-100 text-yellow-800
                                        @else bg-blue-100 text-blue-800
                                        @endif">
                                        {{ ucfirst($correo->tipo) }}
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="text-sm text-gray-900 max-w-xs truncate" title="{{ $correo->descripcion }}">
                                        {{ $correo->descripcion ?? 'Sin descripción' }}
                                    </div>
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap">
                                    <span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full status-badge
                                        {{ $correo->activo ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                        {{ $correo->activo ? 'Activo' : 'Inactivo' }}
                                    </span>
                                </td>
                                <td class="py-4 px-6 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex justify-end space-x-2">
                                        <!-- Botón toggle activo/inactivo -->
                                        <button type="button" 
                                                data-action="toggle-activo"
                                                data-correo-id="{{ $correo->id }}"
                                                class="text-indigo-600 hover:text-indigo-900 transition duration-300"
                                                title="{{ $correo->activo ? 'Desactivar' : 'Activar' }}">
                                            <i class="fas {{ $correo->activo ? 'fa-toggle-on' : 'fa-toggle-off' }}"></i>
                                        </button>

                                        <!-- Botón editar -->
                                        <a href="{{ route('logistica.correos-cc.edit', $correo) }}" 
                                           class="text-blue-600 hover:text-blue-900 transition duration-300"
                                           title="Editar">
                                            <i class="fas fa-edit"></i>
                                        </a>

                                        <!-- Botón eliminar -->
                                        <button type="button" 
                                                data-action="eliminar"
                                                data-correo-id="{{ $correo->id }}"
                                                data-nombre="{{ $correo->nombre }}"
                                                class="text-red-600 hover:text-red-900 transition duration-300"
                                                title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="py-8 px-6 text-center text-gray-500">
                                    <i class="fas fa-inbox text-4xl mb-4"></i>
                                    <p class="text-lg">No hay correos CC configurados</p>
                                    <p class="text-sm">Agrega el primer correo CC para comenzar</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Info footer -->
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            <div class="text-sm text-gray-600">
                <p><i class="fas fa-info-circle mr-1"></i> 
                   Los correos marcados como "Activo" serán incluidos automáticamente como CC al enviar reportes de logística.
                </p>
                <p class="mt-1"><i class="fas fa-shield-alt mr-1"></i> 
                   Los administradores siempre serán incluidos en CC independientemente del estado del envío.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
<script src="{{ asset('js/Logistica/correos-cc.js') }}"></script>
@endpush