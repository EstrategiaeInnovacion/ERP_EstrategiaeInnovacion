@extends('layouts.erp')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-md">
        <!-- Header -->
        <div class="p-6 border-b border-gray-200">
            <div class="flex items-center">
                <a href="{{ route('logistica.correos-cc.index') }}" 
                   class="text-gray-600 hover:text-gray-900 mr-4">
                    <i class="fas fa-arrow-left text-lg"></i>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900">Agregar Correo CC</h1>
                    <p class="text-gray-600">Configura un nuevo correo para incluir en copia</p>
                </div>
            </div>
        </div>

        <!-- Formulario -->
        <form action="{{ route('logistica.correos-cc.store') }}" method="POST" class="p-6">
            @csrf

            <!-- Nombre -->
            <div class="mb-6">
                <label for="nombre" class="block text-sm font-medium text-gray-700 mb-2">
                    Nombre <span class="text-red-500">*</span>
                </label>
                <input type="text" 
                       id="nombre" 
                       name="nombre" 
                       value="{{ old('nombre') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('nombre') border-red-500 @enderror"
                       placeholder="Ej: Administrador Logística"
                       required>
                @error('nombre')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Email -->
            <div class="mb-6">
                <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                    Correo Electrónico <span class="text-red-500">*</span>
                </label>
                <input type="email" 
                       id="email" 
                       name="email" 
                       value="{{ old('email') }}"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('email') border-red-500 @enderror"
                       placeholder="Ej: logistica@estrategiaeinnovacion.com.mx"
                       required>
                @error('email')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Tipo -->
            <div class="mb-6">
                <label for="tipo" class="block text-sm font-medium text-gray-700 mb-2">
                    Tipo <span class="text-red-500">*</span>
                </label>
                <select id="tipo" 
                        name="tipo" 
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('tipo') border-red-500 @enderror"
                        required>
                    <option value="">Seleccionar tipo...</option>
                    <option value="administrador" {{ old('tipo') === 'administrador' ? 'selected' : '' }}>
                        Administrador
                    </option>
                    <option value="supervisor" {{ old('tipo') === 'supervisor' ? 'selected' : '' }}>
                        Supervisor
                    </option>
                    <option value="notificacion" {{ old('tipo') === 'notificacion' ? 'selected' : '' }}>
                        Notificación
                    </option>
                </select>
                @error('tipo')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
                
                <!-- Info de tipos -->
                <div class="mt-2 text-sm text-gray-600">
                    <div class="flex items-center mb-1">
                        <span class="inline-block w-3 h-3 bg-red-100 rounded-full mr-2"></span>
                        <span><strong>Administrador:</strong> Siempre incluido en CC, alta prioridad</span>
                    </div>
                    <div class="flex items-center mb-1">
                        <span class="inline-block w-3 h-3 bg-yellow-100 rounded-full mr-2"></span>
                        <span><strong>Supervisor:</strong> Incluido según configuración</span>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-block w-3 h-3 bg-blue-100 rounded-full mr-2"></span>
                        <span><strong>Notificación:</strong> Para alertas y notificaciones</span>
                    </div>
                </div>
            </div>

            <!-- Descripción -->
            <div class="mb-6">
                <label for="descripcion" class="block text-sm font-medium text-gray-700 mb-2">
                    Descripción
                </label>
                <textarea id="descripcion" 
                          name="descripcion" 
                          rows="3"
                          class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @error('descripcion') border-red-500 @enderror"
                          placeholder="Descripción opcional del rol o propósito de este correo...">{{ old('descripcion') }}</textarea>
                @error('descripcion')
                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <!-- Estado Activo -->
            <div class="mb-6">
                <div class="flex items-center">
                    <input type="checkbox" 
                           id="activo" 
                           name="activo" 
                           value="1"
                           {{ old('activo', '1') ? 'checked' : '' }}
                           class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                    <label for="activo" class="ml-2 block text-sm text-gray-900">
                        Activo (incluir en correos automáticamente)
                    </label>
                </div>
                <p class="mt-1 text-sm text-gray-600">
                    Si está marcado, este correo será incluido automáticamente en el CC de los reportes enviados
                </p>
            </div>

            <!-- Botones -->
            <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                <a href="{{ route('logistica.correos-cc.index') }}" 
                   class="px-6 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition duration-300">
                    Cancelar
                </a>
                <button type="submit" 
                        class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition duration-300">
                    <i class="fas fa-save mr-2"></i>Guardar Correo CC
                </button>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
@endpush