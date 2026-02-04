@extends('layouts.master')

@section('title', 'Retirar Disco - E&I Sistema de Inventario')

@section('content')


        <!-- Main Content -->
        <main class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Breadcrumb -->
            <div class="flex items-center space-x-2 text-sm text-gray-600 mb-6">
                <a href="{{ route('inventario.index') }}" class="hover:text-red-600 transition-colors">📦 Inventario</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
                <a href="{{ route('discos-en-uso.index') }}" class="hover:text-red-600 transition-colors">💾 Discos en Uso</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
                <a href="{{ route('discos-en-uso.show', $discoEnUso) }}" class="hover:text-red-600 transition-colors">{{ $discoEnUso->inventario->codigo_inventario }}</a>
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                </svg>
                <span class="text-gray-800 font-medium">Retirar</span>
            </div>

            <!-- Alerta de confirmación -->
            <div class="bg-red-50 border border-red-200 rounded-lg p-6 mb-6">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-red-600 mr-3 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                    <div>
                        <h3 class="text-lg font-medium text-red-800 mb-2">⚠️ Confirmar Retiro de Disco</h3>
                        <p class="text-sm text-red-700 mb-4">
                            Está a punto de retirar el disco <strong>{{ $discoEnUso->inventario->codigo_inventario }}</strong> 
                            de la computadora <strong>{{ $discoEnUso->computadora_ubicacion }}</strong>.
                        </p>
                        <p class="text-sm text-red-700">
                            Esta acción marcará el disco como disponible nuevamente en el inventario y registrará la fecha de retiro.
                        </p>
                    </div>
                </div>
            </div>

            <!-- Información del Disco a Retirar -->
            <div class="bg-white rounded-lg shadow-lg border border-red-200 mb-6">
                <div class="px-6 py-4 border-b border-red-100">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        Información del Disco en Uso
                    </h2>
                </div>

                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">💾 Disco Duro</label>
                                <p class="mt-1 text-sm font-mono bg-gray-50 px-3 py-2 rounded border">
                                    {{ $discoEnUso->inventario->codigo_inventario }}
                                </p>
                                <p class="text-xs text-gray-600 mt-1">
                                    {{ $discoEnUso->inventario->articulo }} {{ $discoEnUso->inventario->modelo }}
                                </p>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">🖥️ Computadora</label>
                                <p class="mt-1 text-sm bg-blue-50 px-3 py-2 rounded border text-blue-900">
                                    {{ $discoEnUso->computadora_ubicacion }}
                                </p>
                                @if($discoEnUso->area_computadora)
                                    <p class="text-xs text-gray-600 mt-1">
                                        Área: {{ $discoEnUso->area_computadora }}
                                    </p>
                                @endif
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700">👤 Responsable</label>
                                <p class="mt-1 text-sm text-gray-900">{{ $discoEnUso->usuario->name }}</p>
                            </div>
                        </div>

                        <div class="space-y-4">
                            @if($discoEnUso->fecha_instalacion)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">📅 Instalado desde</label>
                                    <p class="mt-1 text-sm text-gray-900">
                                        {{ $discoEnUso->fecha_instalacion->format('d/m/Y') }}
                                        <br>
                                        <span class="text-xs text-gray-600">
                                            ({{ $discoEnUso->fecha_instalacion->diffForHumans() }})
                                        </span>
                                    </p>
                                </div>
                            @endif

                            @if($discoEnUso->razon_uso)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">📋 Razón de uso</label>
                                    <div class="mt-1 bg-gray-50 p-3 rounded border text-sm text-gray-900">
                                        {{ Str::limit($discoEnUso->razon_uso, 100) }}
                                    </div>
                                </div>
                            @endif

                            <div>
                                <label class="block text-sm font-medium text-gray-700">⏱️ Tiempo en uso</label>
                                <p class="mt-1 text-sm text-orange-600 font-medium">
                                    @if($discoEnUso->fecha_instalacion)
                                        {{ $discoEnUso->fecha_instalacion->diffForHumans(null, true) }}
                                    @else
                                        {{ $discoEnUso->created_at->diffForHumans(null, true) }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulario de Retiro -->
            <div class="bg-white rounded-lg shadow-lg border border-red-200">
                <div class="px-6 py-4 border-b border-red-100">
                    <h2 class="text-lg font-semibold text-gray-900 flex items-center">
                        <svg class="w-5 h-5 mr-2 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Información de Retiro
                    </h2>
                    <p class="text-sm text-gray-600 mt-1">Complete la información sobre el retiro del disco</p>
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

                    <form action="{{ route('discos-en-uso.procesar-retiro', $discoEnUso) }}" method="POST" data-disk-withdrawal-form>
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="space-y-6">
                                <div>
                                    <label for="fecha_retiro" class="block text-sm font-medium text-gray-700 mb-2">
                                        📅 Fecha de Retiro *
                                    </label>
                                    <input type="datetime-local" name="fecha_retiro" id="fecha_retiro" 
                                           value="{{ old('fecha_retiro', now()->format('Y-m-d\TH:i')) }}" required
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                                    @error('fecha_retiro')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="observaciones_retiro" class="block text-sm font-medium text-gray-700 mb-2">
                                        📋 Motivo del Retiro *
                                    </label>
                                    <textarea name="observaciones_retiro" id="observaciones_retiro" rows="4" required
                                              placeholder="Describe el motivo por el cual se retira el disco..."
                                              class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">{{ old('observaciones_retiro') }}</textarea>
                                    @error('observaciones_retiro')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="space-y-6">
                                <div>
                                    <label for="disco_sustituto" class="block text-sm font-medium text-gray-700 mb-2">
                                        � Disco Sustituto (Opcional)
                                    </label>
                                    <input type="text" name="disco_sustituto" id="disco_sustituto" 
                                           value="{{ old('disco_sustituto') }}"
                                           placeholder="Si se instaló otro disco, especificar cuál"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-red-500">
                                    @error('disco_sustituto')
                                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-2">
                                        � Observaciones Adicionales
                                    </label>
                                    <div class="text-sm text-gray-600 p-3 bg-gray-50 rounded border">
                                        El motivo del retiro se guardará como observación principal. 
                                        Puedes agregar detalles adicionales en el campo anterior.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Confirmación -->
                        <div class="mt-8 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-yellow-600 mr-2 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div class="text-sm">
                                    <p class="font-medium text-yellow-800">Confirmación requerida</p>
                                    <p class="text-yellow-700 mt-1">
                                        Al retirar este disco, se marcará como disponible en el inventario y se registrará toda la información del retiro. 
                                        Esta acción no se puede deshacer fácilmente.
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Botones de Acción -->
                        <div class="flex justify-end space-x-4 mt-8 pt-6 border-t border-gray-200">
                            <a href="{{ route('discos-en-uso.show', $discoEnUso) }}" 
                               class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                                ❌ Cancelar
                            </a>
                            <button type="submit"
                                    class="px-6 py-2 bg-red-600 hover:bg-red-700 text-white text-sm font-medium rounded-lg transition-colors duration-200 flex items-center"
                                    data-confirm-message="¿Está completamente seguro de retirar este disco? Esta acción registrará la fecha de retiro y liberará el disco en el inventario.">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                                Confirmar Retiro de Disco
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>

        <!-- Scripts adicionales -->
        @push('scripts')
            @vite('resources/js/Sistemas_IT/discos-uso-retirar.js')
        @endpush
    </body>
</html>