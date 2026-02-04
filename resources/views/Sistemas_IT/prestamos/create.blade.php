@extends('layouts.master')

@section('title', 'Registrar Préstamo - E&I Sistema de Préstamos')

@section('content')
        <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Form Container -->
            <div class="bg-white rounded-xl shadow-lg border border-blue-200 overflow-hidden">
                <!-- Form Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <h2 class="text-xl font-bold text-white">Registrar Nuevo Préstamo</h2>
                            <p class="text-blue-100 text-sm">Complete los datos del préstamo</p>
                        </div>
                    </div>
                </div>

                <!-- Error Messages -->
                @if($errors->any())
                    <div class="bg-red-50 border-l-4 border-red-400 p-4">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                </svg>
                            </div>
                            <div class="ml-3">
                                <h3 class="text-sm font-medium text-red-800">Por favor corrige los siguientes errores:</h3>
                                <div class="mt-2 text-sm text-red-700">
                                    <ul class="list-disc pl-5 space-y-1">
                                        @foreach($errors->all() as $error)
                                            <li>{{ $error }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <!-- Form -->
                <form method="POST" action="{{ route('prestamos.store') }}" class="p-6">
                    @csrf
                    
                    <div class="grid grid-cols-1 gap-6">
                        <!-- Información del Artículo -->
                        @if($inventario ?? null)
                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                <h3 class="text-md font-medium text-blue-800 mb-2">📦 Artículo Seleccionado</h3>
                                <div class="text-sm text-blue-700">
                                    <p><strong>Código:</strong> {{ $inventario->codigo_inventario }}</p>
                                    <p><strong>Artículo:</strong> {{ $inventario->articulo }}</p>
                                    <p><strong>Modelo:</strong> {{ $inventario->modelo }}</p>
                                    <p><strong>Categoría:</strong> {{ $inventario->categoria_formateada }}</p>
                                </div>
                                <input type="hidden" name="inventario_id" value="{{ $inventario->id }}">
                            </div>
                        @else
                            <!-- Selector de inventario -->
                            <div>
                                <label for="inventario_id" class="block text-sm font-medium text-gray-700 mb-1">
                                    Artículo a prestar *
                                </label>
                                <select name="inventario_id" 
                                        id="inventario_id" 
                                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                        required>
                                    <option value="">Seleccionar artículo</option>
                                    @foreach(\App\Models\Inventario::disponibles()->where('bloqueado_prestamo', false)->orderBy('articulo')->get() as $item)
                                        <option value="{{ $item->id }}" 
                                                data-disponible="{{ $item->cantidad_disponible }}"
                                                {{ (old('inventario_id') == $item->id) ? 'selected' : '' }}>
                                            {{ $item->codigo_inventario }} - {{ $item->articulo }} {{ $item->modelo }} 
                                            ({{ $item->cantidad_disponible }} disponibles)
                                        </option>
                                    @endforeach
                                </select>
                                @error('inventario_id')
                                    <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                @enderror
                            </div>
                        @endif

                        <!-- Usuario -->
                        <div>
                            <label for="user_id" class="block text-sm font-medium text-gray-700 mb-1">
                                Usuario solicitante *
                            </label>
                            <select name="user_id" 
                                    id="user_id" 
                                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                    required>
                                <option value="">Seleccionar usuario</option>
                                @foreach(\App\Models\User::orderBy('name')->get() as $user)
                                    <option value="{{ $user->id }}" {{ (old('user_id') == $user->id) ? 'selected' : '' }}>
                                        {{ $user->name }} ({{ $user->email }})
                                        @if($user->role === 'admin') - Administrador @endif
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Cantidad -->
                        <div>
                            <label for="cantidad_prestada" class="block text-sm font-medium text-gray-700 mb-1">
                                Cantidad a prestar *
                            </label>
                            <input type="number" 
                                   name="cantidad_prestada" 
                                   id="cantidad_prestada" 
                                   value="{{ old('cantidad_prestada', 1) }}"
                                   min="1"
                                   @if($inventario ?? null)
                                       max="{{ $inventario->cantidad_disponible }}"
                                   @endif
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" 
                                   required
                                   placeholder="1">
                            @error('cantidad_prestada')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            @if($inventario ?? null)
                                <p class="mt-1 text-xs text-gray-500">
                                    💡 Disponibles: {{ $inventario->cantidad_disponible }} unidades
                                </p>
                            @endif
                        </div>

                        <!-- Observaciones -->
                        <div>
                            <label for="observaciones_prestamo" class="block text-sm font-medium text-gray-700 mb-1">
                                Observaciones del préstamo
                            </label>
                            <textarea name="observaciones_prestamo" 
                                      id="observaciones_prestamo" 
                                      rows="3"
                                      class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                      placeholder="Motivo del préstamo, uso previsto, condiciones especiales, etc.">{{ old('observaciones_prestamo') }}</textarea>
                            @error('observaciones_prestamo')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="mt-8 flex items-center justify-end space-x-4 pt-6 border-t border-gray-200">
                        <a href="{{ route('prestamos.index') }}" 
                           class="px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200 rounded-md transition-colors duration-200">
                            Cancelar
                        </a>
                        <button type="submit" 
                                class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-md transition-colors duration-200">
                            💾 Guardar Préstamo
                        </button>
                    </div>
                </form>
            </div>
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 mt-12">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-4">
                <div class="flex justify-between items-center">
                    <div class="flex items-center space-x-4">
                        <img src="{{ asset('images/logo-ei.png') }}" alt="E&I Logo" class="h-8 w-auto">
                        <div class="text-sm text-gray-600">
                            <p>&copy; {{ date('Y') }} E&I - Sistema de Gestión de Préstamos</p>
                        </div>
                    </div>
                    <div class="text-sm text-gray-500">
                        Versión 2.0 | Desarrollado para E&I
                    </div>
                </div>
            </div>
        </footer>

        @push('scripts')
            @vite('resources/js/Sistemas_IT/prestamos-create.js')
        @endpush
@endsection
