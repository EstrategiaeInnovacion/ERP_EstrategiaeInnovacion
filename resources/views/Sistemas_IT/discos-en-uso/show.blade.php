@extends('layouts.master')

@section('title', 'Disco #' . $disco->id . ' - E&I Sistema de Inventario')

@section('content')


        <!-- Main Content -->
        <main class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
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
                <span class="text-gray-800 font-medium">{{ $discoEnUso->inventario->codigo_inventario }}</span>
            </div>

            <!-- Estado y Acciones -->
            <div class="flex justify-between items-start mb-6">
                <div class="flex items-center space-x-3">
                    <span class="inline-flex items-center px-3 py-1.5 rounded-full text-sm font-medium {{ $discoEnUso->esta_activo ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-800' }}">
                        {{ $discoEnUso->esta_activo ? '🖥️ En Uso Activo' : '🔄 Retirado' }}
                    </span>
                    @if($discoEnUso->esta_activo && $discoEnUso->fecha_instalacion)
                        <span class="text-sm text-gray-600">
                            📅 {{ $discoEnUso->fecha_instalacion->diffForHumans() }}
                        </span>
                    @endif
                </div>

                @if(auth()->user()->role === 'admin' && $discoEnUso->esta_activo)
                    <div class="flex space-x-2">
                        <a href="{{ route('inventario.show', $discoEnUso->inventario) }}" 
                           class="inline-flex items-center px-3 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                            </svg>
                            Ver Inventario
                        </a>
                        <a href="{{ route('discos-en-uso.retirar', $discoEnUso) }}" 
                           class="inline-flex items-center px-3 py-2 text-sm font-medium text-white bg-red-600 border border-transparent rounded-lg hover:bg-red-700 transition-colors"
                           onclick="return confirm('¿Está seguro de retirar este disco? Esta acción marcará el disco como disponible nuevamente.')">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Retirar Disco
                        </a>
                    </div>
                @endif
            </div>

            <!-- Main Content Grid -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- Información Principal -->
                <div class="xl:col-span-2 space-y-6">
                    <!-- Información del Disco -->
                    <div class="bg-white rounded-lg shadow-lg border border-orange-200">
                        <div class="px-6 py-4 border-b border-orange-100">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"></path>
                                </svg>
                                Información del Disco Duro
                            </h3>
                        </div>

                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Código de Inventario</label>
                                        <p class="mt-1 text-sm font-mono bg-gray-50 px-3 py-2 rounded border">
                                            {{ $discoEnUso->inventario->codigo_inventario }}
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Modelo</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ $discoEnUso->inventario->articulo }} {{ $discoEnUso->inventario->modelo }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Estado del Disco</label>
                                        <span class="mt-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium 
                                            @if($discoEnUso->inventario->estado == 'nuevo') bg-green-100 text-green-800
                                            @elseif($discoEnUso->inventario->estado == 'usado') bg-blue-100 text-blue-800
                                            @else bg-red-100 text-red-800 @endif">
                                            {{ ucfirst($discoEnUso->inventario->estado) }}
                                        </span>
                                    </div>

                                    @if($discoEnUso->inventario->observaciones)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Observaciones del Inventario</label>
                                            <div class="mt-1 bg-gray-50 p-3 rounded border text-sm text-gray-900 whitespace-pre-wrap">
                                                {{ $discoEnUso->inventario->observaciones }}
                                            </div>
                                        </div>
                                    @endif
                                </div>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Responsable del Uso</label>
                                        <p class="mt-1 text-sm text-gray-900">{{ $discoEnUso->usuario->name }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Fecha de Registro</label>
                                        <p class="mt-1 text-sm text-gray-600">
                                            {{ $discoEnUso->created_at->format('d/m/Y H:i') }}
                                            <br>
                                            <span class="text-xs">{{ $discoEnUso->created_at->diffForHumans() }}</span>
                                        </p>
                                    </div>

                                    @if($discoEnUso->fecha_instalacion)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Fecha de Instalación</label>
                                            <p class="mt-1 text-sm text-gray-600">
                                                {{ $discoEnUso->fecha_instalacion->format('d/m/Y') }}
                                                <br>
                                                <span class="text-xs">{{ $discoEnUso->fecha_instalacion->diffForHumans() }}</span>
                                            </p>
                                        </div>
                                    @endif

                                    @if(!$discoEnUso->esta_activo && $discoEnUso->fecha_retiro)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Fecha de Retiro</label>
                                            <p class="mt-1 text-sm text-red-600">
                                                {{ $discoEnUso->fecha_retiro->format('d/m/Y H:i') }}
                                                <br>
                                                <span class="text-xs">{{ $discoEnUso->fecha_retiro->diffForHumans() }}</span>
                                            </p>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información de la Computadora -->
                    <div class="bg-white rounded-lg shadow-lg border border-orange-200">
                        <div class="px-6 py-4 border-b border-orange-100">
                            <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                </svg>
                                Información de la Computadora
                            </h3>
                        </div>

                        <div class="p-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Computadora</label>
                                        <p class="mt-1 text-sm font-mono bg-blue-50 px-3 py-2 rounded border text-blue-900">
                                            {{ $discoEnUso->computadora_ubicacion }}
                                        </p>
                                    </div>

                                    @if($discoEnUso->area_computadora)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Área/Departamento</label>
                                            <p class="mt-1 text-sm text-gray-900">{{ $discoEnUso->area_computadora }}</p>
                                        </div>
                                    @endif
                                </div>

                                <div class="space-y-4">
                                    @if($discoEnUso->razon_uso)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Razón de Uso</label>
                                            <div class="mt-1 bg-blue-50 p-3 rounded border text-sm text-gray-900 whitespace-pre-wrap">
                                                {{ $discoEnUso->razon_uso }}
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>

                            @if($discoEnUso->observaciones_instalacion)
                                <div class="mt-4 pt-4 border-t border-gray-200">
                                    <label class="block text-sm font-medium text-gray-700 mb-2">Observaciones de Instalación</label>
                                    <div class="bg-gray-50 p-3 rounded border text-sm text-gray-900 whitespace-pre-wrap">
                                        {{ $discoEnUso->observaciones_instalacion }}
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Información de Reemplazo -->
                    @if($discoEnUso->disco_reemplazado || $discoEnUso->motivo_reemplazo)
                        <div class="bg-white rounded-lg shadow-lg border border-orange-200">
                            <div class="px-6 py-4 border-b border-orange-100">
                                <h3 class="text-lg font-semibold text-gray-900 flex items-center">
                                    <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                                    </svg>
                                    Información de Reemplazo
                                </h3>
                            </div>

                            <div class="p-6">
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    @if($discoEnUso->disco_reemplazado)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Disco Reemplazado</label>
                                            <p class="mt-1 text-sm bg-yellow-50 px-3 py-2 rounded border text-yellow-900">
                                                {{ $discoEnUso->disco_reemplazado }}
                                            </p>
                                        </div>
                                    @endif

                                    @if($discoEnUso->motivo_reemplazo)
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700">Motivo del Reemplazo</label>
                                            <div class="mt-1 bg-yellow-50 p-3 rounded border text-sm text-yellow-900 whitespace-pre-wrap">
                                                {{ $discoEnUso->motivo_reemplazo }}
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Enlaces Rápidos -->
                    <div class="bg-white rounded-lg shadow-lg border border-orange-200">
                        <div class="px-4 py-3 border-b border-orange-100">
                            <h4 class="text-sm font-semibold text-gray-900">🔗 Enlaces Rápidos</h4>
                        </div>
                        <div class="p-4 space-y-2">
                            <a href="{{ route('inventario.show', $discoEnUso->inventario) }}" 
                               class="block w-full px-3 py-2 text-sm text-center text-orange-700 bg-orange-50 border border-orange-200 rounded hover:bg-orange-100 transition-colors">
                                📦 Ver en Inventario
                            </a>
                            
                            <a href="{{ route('discos-en-uso.index') }}" 
                               class="block w-full px-3 py-2 text-sm text-center text-gray-700 bg-gray-50 border border-gray-200 rounded hover:bg-gray-100 transition-colors">
                                💾 Todos los Discos en Uso
                            </a>

                            @if(auth()->user()->role === 'admin')
                                <a href="{{ route('discos-en-uso.create') }}" 
                                   class="block w-full px-3 py-2 text-sm text-center text-green-700 bg-green-50 border border-green-200 rounded hover:bg-green-100 transition-colors">
                                    ➕ Registrar Nuevo Uso
                                </a>
                            @endif
                        </div>
                    </div>

                    <!-- Estadísticas Rápidas -->
                    <div class="bg-white rounded-lg shadow-lg border border-orange-200">
                        <div class="px-4 py-3 border-b border-orange-100">
                            <h4 class="text-sm font-semibold text-gray-900">📊 Información Adicional</h4>
                        </div>
                        <div class="p-4 space-y-3">
                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600">Tiempo de uso:</span>
                                <span class="font-medium text-gray-900">
                                    @if($discoEnUso->fecha_instalacion)
                                        @if($discoEnUso->esta_activo)
                                            {{ $discoEnUso->fecha_instalacion->diffForHumans(null, true) }}
                                        @else
                                            {{ $discoEnUso->fecha_instalacion->diffInDays($discoEnUso->fecha_retiro) }} días
                                        @endif
                                    @else
                                        No especificado
                                    @endif
                                </span>
                            </div>

                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600">Estado:</span>
                                <span class="font-medium {{ $discoEnUso->esta_activo ? 'text-orange-600' : 'text-gray-600' }}">
                                    {{ $discoEnUso->esta_activo ? 'Activo' : 'Retirado' }}
                                </span>
                            </div>

                            <div class="flex justify-between items-center text-sm">
                                <span class="text-gray-600">ID de Registro:</span>
                                <span class="font-mono text-xs text-gray-900">#{{ $discoEnUso->id }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Alerta para Administradores -->
                    @if(auth()->user()->role === 'admin' && $discoEnUso->esta_activo)
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-amber-600 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 15.5c-.77.833.192 2.5 1.732 2.5z"></path>
                                </svg>
                                <div class="text-sm">
                                    <p class="font-medium text-amber-800">Recordatorio</p>
                                    <p class="text-amber-700 mt-1">Recuerda retirar el disco cuando ya no esté en uso para mantener el inventario actualizado.</p>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </main>
    </body>
</html>