@extends('layouts.master')

@section('title', 'Nuevo Disco en Uso - E&I Sistema de Inventario')

@section('content')


        <!-- Main Content -->
        <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Breadcrumb -->
            <div class="flex items-center space-x-2 text-sm text-gray-600 mb-6">
                <a href="{{ route('inventario.index') }}" class="hover:text-orange-600 transition-colors">📦 Inventario</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
                <a href="{{ route('discos-en-uso.index') }}" class="hover:text-orange-600 transition-colors">💾 Discos en Uso</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
                <span class="text-gray-800 font-medium">Registrar Uso</span>
            </div>

            <div class="bg-white rounded-lg shadow-lg border border-orange-200">
                <div class="px-6 py-4 border-b border-orange-100">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                        </svg>
                        Registrar Disco en Uso
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">Complete la información del disco que será instalado en una computadora</p>
                </div>

                <div class="p-6">
                    @if($errors->any())
                        <div class="mb-6 bg-red-50 border border-red-200 text-red-800 px-4 py-3 rounded-lg">
                            <div class="flex items-center mb-2">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <strong class="font-medium">Por favor, corrige los siguientes errores:</strong>
                            </div>
                            <ul class="list-disc list-inside text-sm space-y-1">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('discos-en-uso.store') }}" method="POST" x-data="{
                        selectedInventario: @json(request('inventario_id')),
                        inventarios: @json($inventariosData)
                    }" data-disk-usage-form>
                        @csrf

                        <!-- Selección de Disco -->
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="space-y-6">
                                <div>
                                    <label for="inventario_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        💾 Disco Duro a Instalar *
                                    </label>
                                    <select name="inventario_id" id="inventario_id" required
                                            x-model="selectedInventario"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                        <option value="">Selecciona un disco disponible...</option>
                                        @foreach($inventarios as $inv)
                                            <option value="{{ $inv->id }}" 
                                                    {{ request('inventario_id') == $inv->id ? 'selected' : '' }}
                                                    {{ $inv->cantidad_disponible == 0 ? 'disabled' : '' }}>
                                                {{ $inv->codigo_inventario }} - {{ $inv->articulo }} {{ $inv->modelo }}
                                                {{ $inv->cantidad_disponible == 0 ? ' (No disponible)' : ' ✓' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('inventario_id')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Información de la Computadora -->
                                <div>
                                    <label for="nombre_computadora" class="block text-sm font-medium text-gray-700 mb-2">
                                        🖥️ Computadora de Destino *
                                    </label>
                                    <input type="text" name="nombre_computadora" id="nombre_computadora"
                                           value="{{ old('nombre_computadora') }}" required
                                           placeholder="Ej: PC-Oficina-001, Laptop-Desarrollo-005"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                    @error('nombre_computadora')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="computadora_inventario_id" class="block text-sm font-medium text-gray-700 mb-2">
                                        🗂️ Vincular computadora del inventario (opcional)
                                    </label>
                                    <select name="computadora_inventario_id" id="computadora_inventario_id"
                                            class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                        <option value="">No vincular computadora del inventario</option>
                                        @foreach($computadoras as $computadora)
                                            <option value="{{ $computadora->id }}" {{ old('computadora_inventario_id') == $computadora->id ? 'selected' : '' }}>
                                                {{ $computadora->codigo_inventario }} - {{ $computadora->articulo }} {{ $computadora->modelo }}
                                            </option>
                                        @endforeach
                                    </select>
                                    @error('computadora_inventario_id')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="razon_uso" class="block text-sm font-medium text-gray-700 mb-2">
                                        📋 Razón de Uso *
                                    </label>
                                    <textarea name="razon_uso" id="razon_uso" rows="4" required
                                              placeholder="Describe por qué se necesita instalar este disco en la computadora..."
                                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">{{ old('razon_uso') }}</textarea>
                                    @error('razon_uso')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="space-y-6">
                                <!-- Fechas -->
                                <div>
                                    <label for="fecha_instalacion" class="block text-sm font-medium text-gray-700 mb-2">
                                        📅 Fecha de Instalación
                                    </label>
                                    <input type="date" name="fecha_instalacion" id="fecha_instalacion" 
                                           value="{{ old('fecha_instalacion', date('Y-m-d')) }}"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                    @error('fecha_instalacion')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <!-- Información del Disco de Reemplazo -->
                                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                    <h4 class="text-sm font-medium text-gray-800 mb-3 flex items-center">
                                        🔄 Disco de Reemplazo (Opcional)
                                    </h4>
                                    
                                    <div class="space-y-3">
                                        <div>
                                            <label for="disco_reemplazado" class="block text-xs font-medium text-gray-600 mb-1">
                                                💾 Disco que será reemplazado
                                            </label>
                                            <input type="text" name="disco_reemplazado" id="disco_reemplazado"
                                                   value="{{ old('disco_reemplazado') }}"
                                                   placeholder="Ej: HDD 500GB Kingston"
                                                   class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">
                                            @error('disco_reemplazado')
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>

                                        <div>
                                            <label for="detalles_reemplazo" class="block text-xs font-medium text-gray-600 mb-1">
                                                📝 Motivo del reemplazo
                                            </label>
                                            <textarea name="detalles_reemplazo" id="detalles_reemplazo" rows="3"
                                                      placeholder="Ej: Disco anterior dañado, upgrade de capacidad"
                                                      class="w-full px-3 py-2 border border-gray-300 rounded text-sm focus:ring-2 focus:ring-orange-500 focus:border-orange-500">{{ old('detalles_reemplazo') }}</textarea>
                                            @error('detalles_reemplazo')
                                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Observaciones adicionales -->
                                <div>
                                    <label for="observaciones" class="block text-sm font-medium text-gray-700 mb-2">
                                        📝 Observaciones de Instalación
                                    </label>
                                    <textarea name="observaciones" id="observaciones" rows="3"
                                              placeholder="Observaciones adicionales sobre la instalación..."
                                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-orange-500 focus:border-orange-500">{{ old('observaciones') }}</textarea>
                                    @error('observaciones')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Información del Disco Seleccionado -->
                        <div x-show="selectedInventario" class="mt-6 p-4 bg-orange-50 border border-orange-200 rounded-lg">
                            <h4 class="text-sm font-medium text-orange-800 mb-2">📋 Información del Disco Seleccionado</h4>
                            <template x-for="inventario in inventarios" :key="inventario.id">
                                <div x-show="inventario.id == selectedInventario" class="text-sm text-orange-700">
                                    <p><span class="font-medium">Código:</span> <span x-text="inventario.codigo"></span></p>
                                    <p><span class="font-medium">Modelo:</span> <span x-text="inventario.modelo"></span></p>
                                    <p><span class="font-medium">Disponible:</span> 
                                        <span x-text="inventario.disponible ? 'Sí ✓' : 'No disponible ✗'"
                                              :class="inventario.disponible ? 'text-green-700' : 'text-red-700'"></span>
                                    </p>
                                </div>
                            </template>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                            <a href="{{ route('discos-en-uso.index') }}" 
                               class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                                ❌ Cancelar
                            </a>
                            <button type="submit" 
                                    class="px-6 py-2 bg-orange-600 hover:bg-orange-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Registrar Uso de Disco
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>

        <!-- Scripts adicionales -->
        @push('scripts')
            @vite('resources/js/Sistemas_IT/discos-uso-create.js')
        @endpush
    </body>
</html>