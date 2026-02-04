@extends('layouts.master')

@section('title', 'Préstamo #{{ $prestamo->id }} - E&I Sistema de Inventario')

@section('content')
        <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <!-- Success/Error Messages -->
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-6">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6">
                    {{ session('error') }}
                </div>
            @endif

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Main Content -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Préstamo Info -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h2 class="text-2xl font-bold text-gray-900 flex items-center">
                                        <svg class="w-6 h-6 mr-3 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3a2 2 0 012-2h4a2 2 0 012 2v4m-6 0V5.5a.5.5 0 01.5-.5h5a.5.5 0 01.5.5V7m-6 0h6m-6 0l-1 12a2 2 0 002 2h8a2 2 0 002-2l-1-12m-6 0V9"></path>
                                        </svg>
                                        Préstamo #{{ $prestamo->id }}
                                    </h2>
                                    <div class="mt-2">
                                        <span class="inline-flex items-center px-3 py-1 text-sm font-medium rounded-full
                                            @if($prestamo->estado_prestamo == 'activo') bg-green-100 text-green-800
                                            @elseif($prestamo->estado_prestamo == 'devuelto') bg-blue-100 text-blue-800
                                            @elseif($prestamo->estado_prestamo == 'vencido') bg-red-100 text-red-800
                                            @else bg-gray-100 text-gray-800 @endif">
                                            {{ $prestamo->estado_formateado }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex space-x-2">
                                    @if($prestamo->estado_prestamo == 'activo')
                                        <a href="{{ route('prestamos.devolver', $prestamo) }}" 
                                           class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                                            <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            Procesar Devolución
                                        </a>
                                    @endif
                                    <a href="{{ route('prestamos.index') }}" 
                                       class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                                        <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                        </svg>
                                        Volver
                                    </a>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Usuario</label>
                                        <p class="mt-1 text-lg font-semibold text-gray-900">{{ $prestamo->usuario->name }}</p>
                                        <p class="text-sm text-gray-500">{{ $prestamo->usuario->email }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Artículo</label>
                                        <div class="mt-1 flex items-center">
                                            <a href="{{ route('inventario.show', $prestamo->inventario) }}" 
                                               class="text-lg font-semibold text-blue-600 hover:text-blue-800 transition-colors">
                                                {{ $prestamo->inventario->articulo }} {{ $prestamo->inventario->modelo }}
                                            </a>
                                        </div>
                                        @if($prestamo->inventario->codigo_inventario)
                                            <p class="text-sm text-gray-500 font-mono">{{ $prestamo->inventario->codigo_inventario }}</p>
                                        @endif
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Cantidad Prestada</label>
                                        <p class="mt-1 text-lg font-bold text-gray-900">{{ $prestamo->cantidad_prestada }} unidades</p>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Fecha de Préstamo</label>
                                        <p class="mt-1 text-lg text-gray-900">{{ $prestamo->fecha_prestamo->format('d/m/Y H:i') }}</p>
                                        <p class="text-sm text-gray-500">{{ $prestamo->fecha_prestamo->diffForHumans() }}</p>
                                    </div>

                                    @if($prestamo->fecha_devolucion_estimada)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Devolución Estimada</label>
                                            <p class="mt-1 text-lg text-gray-900">{{ $prestamo->fecha_devolucion_estimada->format('d/m/Y') }}</p>
                                            @if($prestamo->fecha_devolucion_estimada < now() && $prestamo->estado_prestamo == 'activo')
                                                <p class="text-sm text-red-600 font-medium">¡Vencido!</p>
                                            @endif
                                        </div>
                                    @endif

                                    @if($prestamo->fecha_devolucion)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Fecha de Devolución</label>
                                            <p class="mt-1 text-lg text-gray-900">{{ $prestamo->fecha_devolucion->format('d/m/Y H:i') }}</p>
                                        </div>
                                    @endif

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Días Transcurridos</label>
                                        <p class="mt-1 text-lg font-bold text-gray-900">{{ $prestamo->dias_prestamo }} días</p>
                                    </div>
                                </div>
                            </div>

                            @if($prestamo->observaciones_prestamo)
                                <div class="mt-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Observaciones del Préstamo</label>
                                    <div class="bg-gray-50 p-4 rounded-lg border">
                                        <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ $prestamo->observaciones_prestamo }}</p>
                                    </div>
                                </div>
                            @endif

                            @if($prestamo->observaciones_devolucion)
                                <div class="mt-6">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Observaciones de Devolución</label>
                                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                        <p class="text-sm text-blue-900 whitespace-pre-wrap">{{ $prestamo->observaciones_devolucion }}</p>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Gestión -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                <svg class="w-5 h-5 inline-block mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                </svg>
                                Gestión del Préstamo
                            </h3>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700">Prestado por</label>
                                    <p class="mt-1 text-sm text-gray-900">{{ $prestamo->prestadoPor->name ?? 'Sistema' }}</p>
                                </div>

                                @if($prestamo->recibidoPor)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Recibido por</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ $prestamo->recibidoPor->name }}</p>
                                    </div>
                                @endif

                                <div class="pt-4 border-t border-gray-200">
                                    <p class="text-xs text-gray-500">
                                        Creado el {{ $prestamo->created_at->format('d/m/Y H:i') }}
                                    </p>
                                    @if($prestamo->updated_at != $prestamo->created_at)
                                        <p class="text-xs text-gray-500">
                                            Actualizado el {{ $prestamo->updated_at->format('d/m/Y H:i') }}
                                        </p>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                <svg class="w-5 h-5 inline-block mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                Acciones Disponibles
                            </h3>
                            
                            <div class="space-y-3">
                                @if($prestamo->estado_prestamo == 'activo')
                                    <a href="{{ route('prestamos.devolver', $prestamo) }}" 
                                       class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        Procesar Devolución
                                    </a>
                                @else
                                    <button disabled 
                                            class="w-full bg-gray-400 text-white font-medium py-2 px-4 rounded-lg cursor-not-allowed flex items-center justify-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
                                        </svg>
                                        Préstamo {{ ucfirst($prestamo->estado_prestamo) }}
                                    </button>
                                @endif

                                <a href="{{ route('inventario.show', $prestamo->inventario) }}" 
                                   class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                    Ver Artículo
                                </a>

                                <a href="{{ route('prestamos.usuario', $prestamo->usuario) }}" 
                                   class="w-full bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    Ver Préstamos del Usuario
                                </a>

                                <a href="{{ route('prestamos.index') }}" 
                                   class="w-full bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"></path>
                                    </svg>
                                    Lista de Préstamos
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
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
    </body>
</html>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3 pt-6 border-t border-gray-200">
                        <a
                            href="{{ route('prestamos.edit', $prestamoId) }}"
                            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-500 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
                        >
                            Editar préstamo
                        </a>
                        <form method="POST" action="{{ route('prestamos.destroy', $prestamoId) }}">
                            @csrf
                            @method('DELETE')

                            <x-danger-button onclick="return confirm('¿Deseas eliminar este préstamo?')">
                                Eliminar
                            </x-danger-button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
