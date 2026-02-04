@extends('layouts.master')

@section('title', 'Inventario - E&I Sistema de Tickets')

@push('styles')
    @vite('resources/css/Sistemas_IT/inventario-index.css')
@endpush



@section('content')
        <!-- Main Content -->
        <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
            <!-- Header Section -->
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg mb-6">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-900 flex items-center">
                            <svg class="w-6 h-6 inline-block mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                            </svg>
                            Inventario de Tecnología
                        </h2>
                        <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                            @if (Auth::user()->isAdmin())
                                <a href="{{ route('inventario.create') }}" 
                                   class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 inline-flex items-center justify-center text-sm">
                                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Agregar Artículo
                                </a>
                                <a href="{{ route('prestamos.index') }}" 
                                   class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 inline-flex items-center justify-center text-sm">
                                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                                    </svg>
                                    Gestionar Préstamos
                                </a>
                            @endif
                            
                            <!-- Toggle Vista Agrupada -->
                            <a href="{{ request()->fullUrlWithQuery(['agrupado' => $vistaAgrupada ? '0' : '1']) }}" 
                               class="{{ $vistaAgrupada ? 'bg-purple-600 hover:bg-purple-700' : 'bg-gray-600 hover:bg-gray-700' }} text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    @if($vistaAgrupada)
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                    @else
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14-7H3m16 14H5"></path>
                                    @endif
                                </svg>
                                {{ $vistaAgrupada ? 'Vista Individual' : 'Agrupar Similares' }}
                            </a>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                        <div class="bg-green-50 p-4 rounded-lg border border-green-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-green-600">Total Artículos</p>
                                    <p class="text-2xl font-bold text-green-900">{{ $stats['total'] }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-blue-600">Funcionales</p>
                                    <p class="text-2xl font-bold text-blue-900">{{ $stats['funcionales'] }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-red-50 p-4 rounded-lg border border-red-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-red-600">Dañados</p>
                                    <p class="text-2xl font-bold text-red-900">{{ $stats['danados'] }}</p>
                                </div>
                            </div>
                        </div>

                        <div class="bg-yellow-50 p-4 rounded-lg border border-yellow-200">
                            <div class="flex items-center">
                                <div class="flex-shrink-0">
                                    <svg class="h-8 w-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                                    </svg>
                                </div>
                                <div class="ml-3">
                                    <p class="text-sm font-medium text-yellow-600">Prestados</p>
                                    <p class="text-2xl font-bold text-yellow-900">{{ $stats['prestados'] }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filters -->
                    <form method="GET" class="bg-gray-50 p-4 rounded-lg border">
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label for="categoria" class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                                <select name="categoria" id="categoria" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                                    <option value="">Todas las categorías</option>
                                    @foreach(App\Models\Inventario::getCategorias() as $key => $label)
                                        <option value="{{ $key }}" {{ request('categoria') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado</label>
                                <select name="estado" id="estado" class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                                    <option value="">Todos los estados</option>
                                    @foreach(App\Models\Inventario::getEstados() as $key => $label)
                                        <option value="{{ $key }}" {{ request('estado') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="busqueda" class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                                <input type="text" 
                                       name="busqueda" 
                                       id="busqueda" 
                                       value="{{ request('busqueda') }}"
                                       placeholder="Artículo, modelo..."
                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500">
                            </div>

                            <div class="flex items-end space-x-2">
                                <button type="submit" 
                                        class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-md transition-colors">
                                    <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                    </svg>
                                    Filtrar
                                </button>
                                <a href="{{ route('inventario.index') }}" 
                                   class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md transition-colors">
                                    Limpiar
                                </a>
                            </div>
                        </div>

                        <div class="flex space-x-4 mt-4">
                            <label class="flex items-center">
                                <input type="checkbox" name="funcionales" value="1" {{ request('funcionales') ? 'checked' : '' }} class="rounded border-gray-300 text-green-600 focus:border-green-500 focus:ring-green-500">
                                <span class="ml-2 text-sm text-gray-600">Solo funcionales</span>
                            </label>
                            <label class="flex items-center">
                                <input type="checkbox" name="disponibles" value="1" {{ request('disponibles') ? 'checked' : '' }} class="rounded border-gray-300 text-green-600 focus:border-green-500 focus:ring-green-500">
                                <span class="ml-2 text-sm text-gray-600">Solo disponibles</span>
                            </label>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Inventory Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                @forelse($inventarios as $inventario)
                    @if($vistaAgrupada && isset($inventario['grupo']))
                        <!-- Grouped View Card -->
                        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                            <!-- Image -->
                            <div class="h-32 bg-gray-100 flex items-center justify-center">
                                @if($inventario['inventarios']->first()->imagenes && count($inventario['inventarios']->first()->imagenes) > 0)
                                    @php
                                        $imagen = $inventario['inventarios']->first()->imagenes[0];
                                        $imagenSrc = is_array($imagen) && isset($imagen['data']) ? $imagen['data'] : 'data:image/jpeg;base64,' . $imagen;
                                    @endphp
                                    <img src="{{ $imagenSrc }}" 
                                         alt="{{ $inventario['grupo'] }}" 
                                         class="h-full w-full object-cover">
                                @else
                                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                @endif
                                <!-- Group indicator -->
                                <div class="absolute top-2 right-2 bg-purple-100 text-purple-800 text-xs px-2 py-1 rounded-full font-medium">
                                    Grupo de {{ $inventario['total'] }} unidades
                                </div>
                            </div>

                            <!-- Content -->
                            <div class="p-4">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $inventario['grupo'] }}</h3>
                                        
                                        <!-- Indicadores especiales para discos con información -->
                                        @php
                                            $tieneDiscosConInfo = $inventario['inventarios']->some(function($item) {
                                                return $item->categoria === 'discos_duros' && $item->tiene_informacion;
                                            });
                                            $tieneDiscosBloqueados = $inventario['inventarios']->some(function($item) {
                                                return $item->categoria === 'discos_duros' && $item->bloqueado_prestamo;
                                            });
                                        @endphp
                                        
                                        <div class="flex flex-wrap gap-1 mt-1">
                                            @foreach($inventario['inventarios']->take(3) as $item)
                                                <span class="inline-flex items-center px-2 py-1 text-xs font-mono font-medium bg-gray-100 text-gray-800 rounded border">
                                                    🏷️ {{ $item->codigo_inventario }}
                                                </span>
                                            @endforeach
                                            @if($inventario['total'] > 3)
                                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-purple-100 text-purple-800 rounded">
                                                    +{{ $inventario['total'] - 3 }} más
                                                </span>
                                            @endif
                                            
                                            <!-- Indicadores de discos con información -->
                                            @if($tieneDiscosConInfo)
                                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-orange-100 text-orange-800 rounded">
                                                    🔒 Con información
                                                </span>
                                            @endif
                                            @if($tieneDiscosBloqueados)
                                                <span class="inline-flex items-center px-2 py-1 text-xs font-medium bg-red-100 text-red-800 rounded">
                                                    🚫 Préstamos bloqueados
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                    <div class="text-right">
                                        <span class="block px-2 py-1 text-xs font-medium rounded-full bg-blue-100 text-blue-800">
                                            {{ $inventario['disponibles'] }}/{{ $inventario['total'] }} Disponibles
                                        </span>
                                    </div>
                                </div>

                                <p class="text-sm text-gray-600 mb-2">{{ $inventario['inventarios']->first()->modelo }}</p>
                                <p class="text-sm font-medium text-gray-700 mb-3">{{ $inventario['inventarios']->first()->categoria_formateada }}</p>
                                
                                <div class="grid grid-cols-2 gap-2 text-sm text-gray-600 mb-3">
                                    <div class="bg-green-50 p-2 rounded">
                                        <span class="text-green-600 font-medium">Funcionales:</span>
                                        <span class="block font-semibold">{{ $inventario['funcionales'] }}</span>
                                    </div>
                                    <div class="bg-red-50 p-2 rounded">
                                        <span class="text-red-600 font-medium">Dañadas:</span>
                                        <span class="block font-semibold">{{ $inventario['danadas'] }}</span>
                                    </div>
                                </div>

                                <!-- Actions -->
                                <div class="flex space-x-2 mt-3">
                                    <a href="{{ route('inventario.show', $inventario['inventarios']->first()->id) }}" 
                                       class="flex-1 bg-green-600 hover:bg-green-700 text-white text-center py-2 px-3 rounded text-sm font-medium transition-colors">
                                        Ver Grupo
                                    </a>
                                    
                                    @if(Auth::user()->isAdmin())
                                        @if($inventario['disponibles'] > 0)
                                            @php
                                                $unidadDisponible = $inventario['inventarios']->first(function($item) {
                                                    return $item->esta_disponible;
                                                });
                                            @endphp
                                            @if($unidadDisponible)
                                                <a href="{{ route('prestamos.create', ['inventario_id' => $unidadDisponible->id]) }}" 
                                                   class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-3 rounded text-sm font-medium transition-colors">
                                                    Prestar
                                                </a>
                                            @endif
                                        @endif
                                        <a href="{{ route('inventario.create', ['similar_to' => $inventario['inventarios']->first()->id]) }}" 
                                           class="bg-purple-600 hover:bg-purple-700 text-white py-2 px-3 rounded text-sm font-medium transition-colors">
                                            + Unidad
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @else
                        <!-- Individual View Card -->
                        <div class="bg-white rounded-lg shadow-md overflow-hidden hover:shadow-lg transition-shadow">
                            <!-- Image -->
                            <div class="h-32 bg-gray-100 flex items-center justify-center relative">
                                @if($inventario->imagenes && count($inventario->imagenes) > 0)
                                    @php
                                        $imagen = $inventario->imagenes[0];
                                        $imagenSrc = is_array($imagen) && isset($imagen['data']) ? $imagen['data'] : 'data:image/jpeg;base64,' . $imagen;
                                    @endphp
                                    <img src="{{ $imagenSrc }}" 
                                         alt="{{ $inventario->articulo }}" 
                                         class="h-full w-full object-cover">
                                @else
                                    <svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                @endif
                            </div>

                            <!-- Content -->
                            <div class="p-4">
                                <div class="flex justify-between items-start mb-2">
                                    <div>
                                        <h3 class="text-lg font-semibold text-gray-900">{{ $inventario->articulo }}</h3>
                                        @if($inventario->codigo_inventario)
                                            <span class="inline-flex items-center px-2 py-1 text-xs font-mono font-medium bg-gray-100 text-gray-800 rounded border">
                                                🏷️ {{ $inventario->codigo_inventario }}
                                            </span>
                                        @endif
                                    </div>
                                    <span class="px-2 py-1 text-xs font-medium rounded-full 
                                    @if($inventario->estado == 'nuevo') bg-green-100 text-green-800
                                    @elseif($inventario->estado == 'usado') bg-blue-100 text-blue-800
                                    @else bg-red-100 text-red-800 @endif"
                                        {{ $inventario->estado_formateado }}
                                    </span>
                                </div>

                                <p class="text-sm text-gray-600 mb-2">{{ $inventario->modelo }}</p>
                                <p class="text-sm font-medium text-gray-700 mb-3">{{ $inventario->categoria_formateada }}</p>
                                
                                <div class="flex justify-between items-center text-sm text-gray-600 mb-3">
                                    <span class="font-medium">Cantidad: <span class="font-semibold">{{ $inventario->cantidad }}</span></span>
                                    <span class="font-medium">Disponible: <span class="font-semibold text-green-600">{{ $inventario->cantidad_disponible }}</span></span>
                                </div>

                                @if($inventario->observaciones)
                                    <p class="text-xs text-gray-500 mb-3 line-clamp-2">{{ $inventario->observaciones }}</p>
                                @endif

                                <!-- Actions -->
                                <div class="flex space-x-2 mt-3">
                                    <a href="{{ route('inventario.show', $inventario->id) }}" 
                                       class="flex-1 bg-green-600 hover:bg-green-700 text-white text-center py-2 px-3 rounded text-sm font-medium transition-colors">
                                        Ver Detalles
                                    </a>
                                    
                                    @if(Auth::user()->isAdmin())
                                        @if($inventario->esta_disponible)
                                            <a href="{{ route('prestamos.create', ['inventario_id' => $inventario->id]) }}" 
                                               class="bg-blue-600 hover:bg-blue-700 text-white py-2 px-3 rounded text-sm font-medium transition-colors">
                                                Prestar
                                            </a>
                                        @endif
                                        <a href="{{ route('inventario.edit', $inventario->id) }}" 
                                           class="bg-yellow-600 hover:bg-yellow-700 text-white py-2 px-3 rounded text-sm font-medium transition-colors">
                                            Editar
                                        </a>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="col-span-full bg-white rounded-lg shadow p-8 text-center">
                        <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                        </svg>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No hay artículos</h3>
                        <p class="text-gray-600 mb-4">No se encontraron artículos que coincidan con los filtros aplicados.</p>
                        @if(Auth::user()->isAdmin())
                            <a href="{{ route('inventario.create') }}" 
                               class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                                Agregar Primer Artículo
                            </a>
                        @endif
                    </div>
                @endforelse
            </div>

            <!-- Pagination -->
            @if($inventarios->hasPages())
                <div class="mt-8">
                    {{ $inventarios->links() }}
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