@extends('layouts.master')

@section('title', 'Gestión de Préstamos - E&I Sistema de Inventario')

@section('content')
        <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
            <div class="space-y-6">
                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Header y Stats -->
                <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <div class="flex items-center justify-between mb-6">
                            <h2 class="text-2xl font-bold text-gray-900">
                                <svg class="w-6 h-6 inline-block mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                Gestión de Préstamos
                            </h2>
                            <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                                <a href="{{ route('prestamos.create') }}" 
                                   class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 inline-flex items-center justify-center text-sm">
                                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Nuevo Préstamo
                                </a>
                            </div>
                        </div>

                        <!-- Estadísticas -->
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                            <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-blue-600">Préstamos Activos</p>
                                        <p class="text-2xl font-bold text-blue-900">{{ $stats['activos'] }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-green-600">Devueltos Este Mes</p>
                                        <p class="text-2xl font-bold text-green-900">{{ $stats['devueltos_mes'] }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-purple-50 p-4 rounded-lg border border-purple-200">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-purple-600">Este Mes</p>
                                        <p class="text-2xl font-bold text-purple-900">{{ $stats['devueltos_mes'] }}</p>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-gray-50 p-4 rounded-lg border border-gray-200">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0">
                                        <svg class="w-8 h-8 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                        </svg>
                                    </div>
                                    <div class="ml-3">
                                        <p class="text-sm font-medium text-gray-600">Total Préstamos</p>
                                        <p class="text-2xl font-bold text-gray-900">{{ $stats['total'] }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Filtros -->
                        <div x-data="{ showFilters: false }" class="mb-6">
                            <div class="flex items-center justify-between">
                                <button @click="showFilters = !showFilters" 
                                        class="flex items-center space-x-2 px-4 py-2 bg-gray-100 hover:bg-gray-200 rounded-lg transition-colors duration-200">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.207A1 1 0 013 6.5V4z"></path>
                                    </svg>
                                    <span>Filtros</span>
                                    <svg class="w-4 h-4 transition-transform duration-200" :class="{ 'rotate-180': showFilters }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                    </svg>
                                </button>

                                <div class="text-sm text-gray-600">
                                    <span class="font-medium">{{ $prestamos->count() }}</span> de <span class="font-medium">{{ $prestamos->total() }}</span> préstamos
                                </div>
                            </div>

                            <div x-show="showFilters" x-transition class="mt-4 bg-gray-50 p-4 rounded-lg">
                                <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                                        <select name="estado" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                                            <option value="">Todos los estados</option>
                                            <option value="activo" {{ request('estado') == 'activo' ? 'selected' : '' }}>Activo</option>
                                            <option value="devuelto" {{ request('estado') == 'devuelto' ? 'selected' : '' }}>Devuelto</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Usuario</label>
                                        <input type="text" name="usuario" value="{{ request('usuario') }}" 
                                               placeholder="Buscar por nombre o email"
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Artículo</label>
                                        <input type="text" name="articulo" value="{{ request('articulo') }}" 
                                               placeholder="Buscar por artículo"
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                                    </div>

                                    <div class="flex items-end space-x-2">
                                        <button type="submit" 
                                                class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                                            Aplicar Filtros
                                        </button>
                                        <a href="{{ route('prestamos.index') }}" 
                                           class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                                            Limpiar
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lista de Préstamos -->
                @if($prestamos->count() > 0)
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        @foreach($prestamos as $prestamo)
                            <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow border border-gray-200">
                                <!-- Header -->
                                <div class="bg-gradient-to-r {{ $prestamo->fecha_devolucion_real ? 'from-green-500 to-green-600' : 'from-blue-500 to-blue-600' }} px-4 py-3">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center">
                                            <div class="w-8 h-8 bg-white bg-opacity-20 rounded-full flex items-center justify-center">
                                                <span class="text-sm font-medium text-white">
                                                    {{ substr($prestamo->usuario->name, 0, 1) }}
                                                </span>
                                            </div>
                                            <div class="ml-3">
                                                <h3 class="text-white font-medium text-sm">{{ $prestamo->usuario->name }}</h3>
                                                <p class="text-white text-opacity-80 text-xs">ID: #{{ $prestamo->id }}</p>
                                            </div>
                                        </div>
                                        <div class="text-right">
                                            @if($prestamo->fecha_devolucion_real)
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-white bg-opacity-20 text-white">
                                                    ✅ Devuelto
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-white bg-opacity-20 text-white">
                                                    🔄 Activo
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <!-- Content -->
                                <div class="p-4">
                                    <!-- Artículo -->
                                    <div class="mb-3">
                                        <h4 class="font-semibold text-gray-900 text-sm">{{ $prestamo->inventario->articulo }}</h4>
                                        <p class="text-xs text-gray-600">{{ $prestamo->inventario->modelo }}</p>
                                        @if($prestamo->inventario->codigo_inventario)
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-mono font-medium bg-gray-100 text-gray-800 rounded border mt-1">
                                                🏷️ {{ $prestamo->inventario->codigo_inventario }}
                                            </span>
                                        @endif
                                    </div>

                                    <!-- Información del Préstamo -->
                                    <div class="space-y-2 text-xs text-gray-600 mb-3">
                                        <div class="flex justify-between">
                                            <span>Cantidad:</span>
                                            <span class="font-semibold text-gray-900">{{ $prestamo->cantidad }}</span>
                                        </div>
                                        <div class="flex justify-between">
                                            <span>Fecha préstamo:</span>
                                            <span class="font-medium">{{ $prestamo->fecha_prestamo->format('d/m/Y') }}</span>
                                        </div>

                                        @if($prestamo->fecha_devolucion_real)
                                            <div class="flex justify-between">
                                                <span>Devuelto:</span>
                                                <span class="font-medium text-green-600">{{ $prestamo->fecha_devolucion_real->format('d/m/Y') }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Descripción -->
                                    @if($prestamo->descripcion)
                                        <div class="mb-3">
                                            <p class="text-xs text-gray-500 line-clamp-2">{{ $prestamo->descripcion }}</p>
                                        </div>
                                    @endif

                                    <!-- Actions -->
                                    <div class="flex space-x-2 mt-3">
                                        <a href="{{ route('prestamos.show', $prestamo) }}" 
                                           class="flex-1 bg-blue-600 hover:bg-blue-700 text-white text-center py-2 px-3 rounded text-xs font-medium transition-colors">
                                            Ver Detalles
                                        </a>
                                        @if(!$prestamo->fecha_devolucion_real)
                                            <a href="{{ route('prestamos.devolver', $prestamo) }}" 
                                               class="flex-1 bg-green-600 hover:bg-green-700 text-white text-center py-2 px-3 rounded text-xs font-medium transition-colors">
                                                Devolver
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Paginación -->
                    @if($prestamos->hasPages())
                        <div class="mt-6 flex justify-center">
                            {{ $prestamos->links() }}
                        </div>
                    @endif
                @else
                    <div class="bg-white rounded-xl shadow-lg border border-gray-200 overflow-hidden">
                        <div class="text-center py-12">
                            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">No hay préstamos registrados</h3>
                            <p class="text-gray-600 mb-6">No se encontraron préstamos con los filtros aplicados.</p>
                            <a href="{{ route('prestamos.create') }}" 
                               class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                </svg>
                                Crear Primer Préstamo
                            </a>
                        </div>
                    </div>
                @endif
            </div>
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