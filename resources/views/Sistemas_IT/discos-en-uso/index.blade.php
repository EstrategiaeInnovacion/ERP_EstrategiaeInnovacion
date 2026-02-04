@extends('layouts.master')

@section('title', 'Discos en Uso - E&I Sistema de Inventario')

@section('content')
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Mensajes de éxito/error -->
            @if(session('success'))
                <div class="mb-6 bg-green-50 border-l-4 border-green-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-800">{{ session('success') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            @if(session('error'))
                <div class="mb-6 bg-red-50 border-l-4 border-red-400 p-4">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-red-800">{{ session('error') }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Estadísticas -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-green-600">En Uso</p>
                            <p class="text-2xl font-bold text-green-900">{{ $stats['activos'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-gray-600">Retirados</p>
                            <p class="text-2xl font-bold text-gray-900">{{ $stats['retirados'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-blue-600">Este Mes</p>
                            <p class="text-2xl font-bold text-blue-900">{{ $stats['este_mes'] }}</p>
                        </div>
                    </div>
                </div>

                <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm font-medium text-purple-600">Total</p>
                            <p class="text-2xl font-bold text-purple-900">{{ $stats['total'] }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Acciones y Filtros -->
            <div class="bg-white rounded-lg shadow-sm border border-gray-200 mb-6" x-data="{ showFilters: false }">
                <div class="p-4 border-b border-gray-200">
                    <div class="flex justify-between items-center">
                        <h2 class="text-lg font-semibold text-gray-900">💾 Gestión de Discos Duros</h2>
                        <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                            <button @click="showFilters = !showFilters" 
                                    class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium py-2 px-4 rounded-lg transition-colors duration-200 inline-flex items-center justify-center text-sm">
                                <span x-show="!showFilters">🔍 Filtros</span>
                                <span x-show="showFilters">✕ Cerrar</span>
                            </button>
                            <a href="{{ route('discos-en-uso.create') }}" 
                               class="bg-orange-600 hover:bg-orange-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 inline-flex items-center justify-center text-sm">
                                💾 Registrar Disco en Uso
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div x-show="showFilters" x-transition class="p-4 bg-gray-50 border-b border-gray-200">
                    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                            <select name="estado" class="w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                <option value="">Todos los estados</option>
                                <option value="activo" {{ request('estado') == 'activo' ? 'selected' : '' }}>En Uso</option>
                                <option value="retirado" {{ request('estado') == 'retirado' ? 'selected' : '' }}>Retirado</option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Computadora</label>
                            <input type="text" name="computadora" value="{{ request('computadora') }}" 
                                   placeholder="Buscar por nombre de computadora"
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                        </div>

                        <div class="flex items-end">
                            <button type="submit" 
                                    class="w-full bg-orange-600 hover:bg-orange-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                                Aplicar Filtros
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Lista de Discos en Uso -->
            @if($discosEnUso->count() > 0)
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach($discosEnUso as $disco)
                        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow border {{ $disco->esta_activo ? 'border-green-200' : 'border-gray-200' }}">
                            <!-- Header -->
                            <div class="bg-gradient-to-r {{ $disco->esta_activo ? 'from-green-500 to-green-600' : 'from-gray-500 to-gray-600' }} px-4 py-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                            <span class="text-sm font-medium text-white">💾</span>
                                        </div>
                                        <div class="ml-3">
                                            <h3 class="text-white font-medium text-sm">{{ $disco->inventario->articulo }}</h3>
                                            <p class="text-white text-opacity-90 text-xs">{{ $disco->inventario->codigo_inventario }}</p>
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-white bg-opacity-20 text-white">
                                            {{ $disco->esta_activo ? '✅ En Uso' : '📦 Retirado' }}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="p-4">
                                <!-- Información del Disco -->
                                <div class="mb-3">
                                    <h4 class="font-semibold text-gray-900 text-sm mb-1">💻 {{ $disco->nombre_computadora }}</h4>
                                    @if($disco->computadoraInventario)
                                        <p class="text-xs text-blue-600">
                                            📋 Registrada: {{ $disco->computadoraInventario->articulo }} {{ $disco->computadoraInventario->modelo }}
                                        </p>
                                    @else
                                        <p class="text-xs text-gray-600">📋 No registrada en inventario</p>
                                    @endif
                                </div>

                                <!-- Detalles -->
                                <div class="space-y-2 text-xs text-gray-600 mb-3">
                                    <div class="flex justify-between">
                                        <span>Modelo:</span>
                                        <span class="font-medium">{{ $disco->inventario->modelo }}</span>
                                    </div>
                                    <div class="flex justify-between">
                                        <span>Instalado:</span>
                                        <span class="font-medium">{{ $disco->fecha_instalacion->format('d/m/Y') }}</span>
                                    </div>
                                    @if(!$disco->esta_activo && $disco->fecha_retiro)
                                        <div class="flex justify-between">
                                            <span>Retirado:</span>
                                            <span class="font-medium text-gray-500">{{ $disco->fecha_retiro->format('d/m/Y') }}</span>
                                        </div>
                                    @endif
                                    @if($disco->disco_reemplazado)
                                        <div>
                                            <span class="text-amber-600">🔄 Reemplazó:</span>
                                            <span class="font-medium">{{ $disco->disco_reemplazado }}</span>
                                        </div>
                                    @endif
                                </div>

                                <!-- Razón de uso -->
                                <div class="mb-3">
                                    <p class="text-xs text-gray-500 mb-1">📝 Razón de uso:</p>
                                    <p class="text-xs text-gray-800 bg-gray-50 p-2 rounded">{{ Str::limit($disco->razon_uso, 80) }}</p>
                                </div>

                                <!-- Actions -->
                                <div class="flex space-x-2">
                                    <a href="{{ route('discos-en-uso.show', $disco) }}" 
                                       class="flex-1 bg-orange-600 hover:bg-orange-700 text-white text-center py-2 px-3 rounded text-xs font-medium transition-colors">
                                        Ver Detalles
                                    </a>
                                    @if($disco->esta_activo)
                                        <a href="{{ route('discos-en-uso.retirar', $disco) }}" 
                                           class="flex-1 bg-red-600 hover:bg-red-700 text-white text-center py-2 px-3 rounded text-xs font-medium transition-colors">
                                            Retirar
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>

                <!-- Paginación -->
                <div class="mt-8">
                    {{ $discosEnUso->links() }}
                </div>
            @else
                <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-12 text-center">
                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <h3 class="mt-2 text-sm font-medium text-gray-900">No hay discos registrados</h3>
                    <p class="mt-1 text-sm text-gray-500">Comienza registrando el primer disco en uso.</p>
                    <div class="mt-6">
                        <a href="{{ route('discos-en-uso.create') }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-orange-600 hover:bg-orange-700">
                            💾 Registrar Primer Disco
                        </a>
                    </div>
                </div>
            @endif
        </main>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 mt-12">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <div class="text-center text-gray-500 text-sm">
                    <p>&copy; {{ date('Y') }} E&I - Comercio Exterior, Logística y Tecnología. Todos los derechos reservados.</p>
                </div>
            </div>
        </footer>
@endsection