@extends('layouts.master')

@section('title', 'Ver Artículo - E&I Sistema de Inventario')

@section('content')
        <!-- Main Content -->
        <main class="max-w-6xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
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

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Información Principal -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <div class="flex items-center justify-between mb-6">
                                <div>
                                    <h2 class="text-2xl font-bold text-gray-900">
                                        <svg class="w-6 h-6 inline-block mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                        </svg>
                                        {{ $inventario->articulo }}
                                    </h2>
                                    @if($inventario->codigo_inventario)
                                        <div class="mt-1">
                                            <span class="inline-flex items-center px-3 py-1 text-sm font-mono font-bold bg-green-100 text-green-800 rounded-lg border border-green-200">
                                                🏷️ {{ $inventario->codigo_inventario }}
                                            </span>
                                            <span class="text-sm text-gray-500 ml-2">ID: #{{ $inventario->id }}</span>
                                        </div>
                                    @else
                                        <span class="text-lg text-gray-500 font-normal">ID: #{{ $inventario->id }}</span>
                                    @endif
                                </div>
                                <div class="flex space-x-2">
                                    <a href="{{ route('inventario.edit', $inventario) }}" 
                                       class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                                        <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                        </svg>
                                        Editar
                                    </a>
                                    <a href="{{ route('inventario.index') }}" 
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
                                        <label class="block text-sm font-medium text-gray-700">Categoría</label>
                                        <div class="mt-1">
                                            @php
                                                $categoria_icons = [
                                                    'mouse' => '🖱️',
                                                    'discos_duros' => '💾',
                                                    'memorias_ram' => '🧠',
                                                    'cargadores' => '🔌',
                                                    'baterias' => '🔋',
                                                    'computadoras' => '💻',
                                                    'otros' => '📦'
                                                ];
                                                $categoria_colors = [
                                                    'mouse' => 'bg-blue-100 text-blue-800',
                                                    'discos_duros' => 'bg-purple-100 text-purple-800',
                                                    'memorias_ram' => 'bg-green-100 text-green-800',
                                                    'cargadores' => 'bg-yellow-100 text-yellow-800',
                                                    'baterias' => 'bg-red-100 text-red-800',
                                                    'computadoras' => 'bg-indigo-100 text-indigo-800',
                                                    'otros' => 'bg-gray-100 text-gray-800'
                                                ];
                                            @endphp
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $categoria_colors[$inventario->categoria] ?? 'bg-gray-100 text-gray-800' }}">
                                                {{ $categoria_icons[$inventario->categoria] ?? '📦' }} {{ $categorias[$inventario->categoria] }}
                                            </span>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Modelo</label>
                                        <p class="mt-1 text-sm text-gray-900 font-mono">{{ $inventario->modelo }}</p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Estado</label>
                                        <div class="mt-1">
                                            @php
                                                $estado_colors = [
                                                    'nuevo' => 'bg-green-100 text-green-800',
                                                    'usado' => 'bg-blue-100 text-blue-800',
                                                    'dañado' => 'bg-red-100 text-red-800'
                                                ];
                                            @endphp

                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $estado_colors[$inventario->estado] ?? 'bg-gray-100 text-gray-800' }}">
                                                {{ $estados[$inventario->estado] }}
                                            </span>
                                        </div>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Colores</label>
                                        <div class="mt-1 space-y-1 text-sm">
                                            <div class="flex items-center gap-3">
                                                <span class="text-xs uppercase tracking-wide text-gray-500">Primario</span>
                                                @if($inventario->color_primario)
                                                    <span class="inline-flex items-center gap-2 px-2 py-0.5 rounded bg-gray-100 text-gray-700">
                                                        <span class="w-4 h-4 rounded border border-gray-300" style="background-color: {{ $inventario->color_primario }}"></span>
                                                        <span class="font-mono text-xs">{{ strtoupper($inventario->color_primario) }}</span>
                                                    </span>
                                                @else
                                                    <span class="text-xs text-gray-500">Sin asignar</span>
                                                @endif
                                            </div>
                                            <div class="flex items-center gap-3">
                                                <span class="text-xs uppercase tracking-wide text-gray-500">Secundario</span>
                                                @if($inventario->color_secundario)
                                                    <span class="inline-flex items-center gap-2 px-2 py-0.5 rounded bg-gray-100 text-gray-700">
                                                        <span class="w-4 h-4 rounded border border-gray-300" style="background-color: {{ $inventario->color_secundario }}"></span>
                                                        <span class="font-mono text-xs">{{ strtoupper($inventario->color_secundario) }}</span>
                                                    </span>
                                                @else
                                                    <span class="text-xs text-gray-500">Sin asignar</span>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Cantidad Total</label>
                                        <p class="mt-1 text-sm text-gray-900">
                                            <span class="font-bold text-lg">{{ $inventario->cantidad }}</span> unidades
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Cantidad Disponible</label>
                                        <p class="mt-1 text-sm">
                                            <span class="font-bold text-lg {{ $inventario->cantidad_disponible > 0 ? 'text-green-600' : 'text-red-600' }}">
                                                {{ $inventario->cantidad_disponible }}
                                            </span> 
                                            <span class="text-gray-600">disponibles</span>
                                        </p>
                                        
                                        @if($inventario->estado == 'dañado')
                                            <p class="text-xs text-gray-500 mt-1">
                                                (Esta unidad no está disponible para préstamo por estar dañada)
                                            </p>
                                        @endif
                                    </div>

                                    <!-- Información específica para discos duros en uso -->
                                    @if($inventario->categoria === 'discos_duros')
                                        @if($discoEnUso)
                                            <div>
                                                <label class="block text-sm font-medium text-orange-700">💾 Estado de Uso</label>
                                                <div class="mt-1 space-y-2">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                        🖥️ En uso
                                                    </span>
                                                    
                                                    <div class="text-sm text-gray-700">
                                                        <p><span class="font-medium">Computadora:</span> {{ $discoEnUso->computadora_ubicacion }}</p>
                                                        <p><span class="font-medium">Responsable:</span> {{ $discoEnUso->usuario->name }}</p>
                                                        <p><span class="font-medium">Desde:</span> {{ $discoEnUso->fecha_instalacion ? $discoEnUso->fecha_instalacion->format('d/m/Y') : 'No especificada' }}</p>
                                                        @if($discoEnUso->razon_uso)
                                                            <p><span class="font-medium">Razón:</span> {{ Str::limit($discoEnUso->razon_uso, 50) }}</p>
                                                        @endif
                                                    </div>
                                                    
                                                    <div class="flex space-x-2 mt-2">
                                                        <a href="{{ route('discos-en-uso.show', $discoEnUso) }}" 
                                                           class="inline-flex items-center px-2 py-1 text-xs font-medium text-orange-700 bg-orange-50 border border-orange-200 rounded hover:bg-orange-100 transition-colors">
                                                            Ver detalles de uso
                                                        </a>
                                                        @if(auth()->user()->role === 'admin')
                                                            <a href="{{ route('discos-en-uso.retirar', $discoEnUso) }}" 
                                                               class="inline-flex items-center px-2 py-1 text-xs font-medium text-red-700 bg-red-50 border border-red-200 rounded hover:bg-red-100 transition-colors"
                                                               onclick="return confirm('¿Está seguro de retirar este disco?')">
                                                                Retirar disco
                                                            </a>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                        @else
                                            <div>
                                                <label class="block text-sm font-medium text-green-700">💾 Estado de Uso</label>
                                                <div class="mt-1 space-y-2">
                                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                                        ✅ Disponible para uso
                                                    </span>
                                                    
                                                    @if(auth()->user()->role === 'admin' && $inventario->cantidad_disponible > 0)
                                                        <div class="mt-2">
                                                            <a href="{{ route('discos-en-uso.create', ['inventario_id' => $inventario->id]) }}" 
                                                               class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-white bg-orange-600 border border-transparent rounded hover:bg-orange-700 transition-colors">
                                                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                                </svg>
                                                                Marcar como en uso
                                                            </a>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    @else
                                        @if($inventario->estado === 'dañado')
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">Disponibilidad</label>
                                                <p class="mt-1 text-sm text-red-600">
                                                    <span class="font-bold text-lg">No disponible</span>
                                                    <span class="text-gray-600 block text-xs">Esta unidad no se encuentra disponible debido a su estado.</span>
                                                </p>
                                            </div>
                                        @elseif($inventario->cantidad_disponible != $inventario->cantidad)
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700">En Préstamo</label>
                                                <p class="mt-1 text-sm">
                                                    <span class="font-bold text-lg text-amber-600">
                                                        {{ $inventario->cantidad - $inventario->cantidad_disponible }}
                                                    </span>
                                                    <span class="text-gray-600">prestadas</span>
                                                </p>
                                            </div>
                                        @endif
                                    @endif

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700">Creado</label>
                                        <p class="mt-1 text-sm text-gray-600">
                                            {{ $inventario->created_at->format('d/m/Y H:i') }}
                                            <br>
                                            <span class="text-xs">por {{ $inventario->createdBy->name ?? 'Sistema' }}</span>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <!-- Unidades del Mismo Tipo -->
                            @php
                                $unidadesSimilares = $inventario->unidades_similares;
                            @endphp
                            
                            @if($unidadesSimilares->count() > 0)
                                <div class="mt-6 bg-purple-50 p-4 rounded-lg border border-purple-200">
                                    <div class="flex items-center justify-between mb-4">
                                        <h4 class="text-md font-medium text-purple-800">
                                            🏷️ Unidades del Mismo Tipo ({{ $unidadesSimilares->count() + 1 }} total)
                                        </h4>
                                        <a href="{{ route('inventario.create', ['similar_to' => $inventario->id]) }}" 
                                           class="bg-green-600 hover:bg-green-700 text-white text-xs font-medium py-1 px-3 rounded-lg transition-colors duration-200 flex items-center">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                            </svg>
                                            Agregar Nueva Unidad
                                        </a>
                                    </div>
                                    
                                    <div class="text-sm text-purple-700 mb-4">
                                        Artículos del mismo tipo: <strong>{{ $inventario->articulo }} {{ $inventario->modelo }}</strong>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                                        <!-- Unidad actual -->
                                        <div class="bg-purple-100 border-2 border-purple-400 rounded-lg p-3">
                                            <div class="flex items-center justify-between mb-2">
                                                <div class="font-mono text-sm font-bold text-purple-900">{{ $inventario->codigo_inventario }}</div>
                                                <span class="bg-purple-600 text-white text-xs px-2 py-1 rounded-full font-medium">ACTUAL</span>
                                            </div>
                                            
                                                <div class="space-y-2">
                                                    <div class="flex items-center justify-between text-xs">
                                                        <span class="text-purple-700">Estado:</span>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium
                                                            @if($inventario->estado == 'nuevo') bg-green-100 text-green-800
                                                            @elseif($inventario->estado == 'usado') bg-blue-100 text-blue-800
                                                            @else bg-red-100 text-red-800 @endif">
                                                            {{ ucfirst($inventario->estado) }}
                                                        </span>
                                                    </div>

                                                    <div class="flex items-center justify-between text-xs">
                                                        <span class="text-purple-700">Disponibilidad:</span>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium
                                                            @if($inventario->cantidad_disponible > 0 && $inventario->estado != 'dañado') bg-green-100 text-green-800
                                                            @else bg-red-100 text-red-800 @endif">
                                                            @if($inventario->cantidad_disponible > 0 && $inventario->estado != 'dañado') Disponible
                                                            @else No Disponible @endif
                                                        </span>
                                                    </div>

                                                    <div class="flex items-start justify-between text-xs">
                                                        <span class="text-purple-700">Colores:</span>
                                                        <div class="flex flex-col items-end gap-1">
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-[11px] text-gray-500">Primario</span>
                                                                @if($inventario->color_primario)
                                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-gray-100 text-gray-700">
                                                                        <span class="w-3 h-3 rounded border border-gray-300" style="background-color: {{ $inventario->color_primario }}"></span>
                                                                        <span class="font-mono">{{ strtoupper($inventario->color_primario) }}</span>
                                                                    </span>
                                                                @else
                                                                    <span class="text-gray-400">—</span>
                                                                @endif
                                                            </div>
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-[11px] text-gray-500">Secundario</span>
                                                                @if($inventario->color_secundario)
                                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-gray-100 text-gray-700">
                                                                        <span class="w-3 h-3 rounded border border-gray-300" style="background-color: {{ $inventario->color_secundario }}"></span>
                                                                        <span class="font-mono">{{ strtoupper($inventario->color_secundario) }}</span>
                                                                    </span>
                                                                @else
                                                                    <span class="text-gray-400">—</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>

                                                    @if($inventario->observaciones && !str_contains($inventario->observaciones, '--- DETALLES POR UNIDAD ---'))
                                                        <div class="text-xs">
                                                            <span class="text-purple-700">Notas:</span>
                                                            <p class="text-gray-700 mt-1 bg-white p-2 rounded text-xs">{{ Str::limit($inventario->observaciones, 60) }}</p>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>

                                        @foreach($unidadesSimilares as $unidad)
                                            <div class="bg-white border border-purple-200 rounded-lg p-3 hover:bg-purple-50 transition-colors">
                                                <div class="flex items-center justify-between mb-2">
                                                    <a href="{{ route('inventario.show', $unidad) }}" class="font-mono text-sm font-bold text-purple-900 hover:text-purple-700 transition-colors">
                                                        {{ $unidad->codigo_inventario }}
                                                    </a>
                                                    <span class="text-gray-500 text-xs">ID: #{{ $unidad->id }}</span>
                                                </div>
                                                
                                                <div class="space-y-2">
                                                    <div class="flex items-center justify-between text-xs">
                                                        <span class="text-gray-600">Estado:</span>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium
                                                            @if($unidad->estado == 'nuevo') bg-green-100 text-green-800
                                                            @elseif($unidad->estado == 'usado') bg-blue-100 text-blue-800
                                                            @else bg-red-100 text-red-800 @endif">
                                                            {{ ucfirst($unidad->estado) }}
                                                        </span>
                                                    </div>

                                                    <div class="flex items-center justify-between text-xs">
                                                        <span class="text-gray-600">Disponibilidad:</span>
                                                        <span class="inline-flex items-center px-2 py-0.5 rounded-full font-medium
                                                            @if($unidad->cantidad_disponible > 0 && $unidad->estado != 'dañado') bg-green-100 text-green-800
                                                            @else bg-red-100 text-red-800 @endif">
                                                            @if($unidad->cantidad_disponible > 0 && $unidad->estado != 'dañado') Disponible
                                                            @else No Disponible @endif
                                                        </span>
                                                    </div>

                                                    <div class="flex items-start justify-between text-xs">
                                                        <span class="text-gray-600">Colores:</span>
                                                        <div class="flex flex-col items-end gap-1">
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-[11px] text-gray-500">Primario</span>
                                                                @if($unidad->color_primario)
                                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-gray-100 text-gray-700">
                                                                        <span class="w-3 h-3 rounded border border-gray-300" style="background-color: {{ $unidad->color_primario }}"></span>
                                                                        <span class="font-mono">{{ strtoupper($unidad->color_primario) }}</span>
                                                                    </span>
                                                                @else
                                                                    <span class="text-gray-400">—</span>
                                                                @endif
                                                            </div>
                                                            <div class="flex items-center gap-2">
                                                                <span class="text-[11px] text-gray-500">Secundario</span>
                                                                @if($unidad->color_secundario)
                                                                    <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded bg-gray-100 text-gray-700">
                                                                        <span class="w-3 h-3 rounded border border-gray-300" style="background-color: {{ $unidad->color_secundario }}"></span>
                                                                        <span class="font-mono">{{ strtoupper($unidad->color_secundario) }}</span>
                                                                    </span>
                                                                @else
                                                                    <span class="text-gray-400">—</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>

                                                    @if($unidad->observaciones && !str_contains($unidad->observaciones, '--- DETALLES POR UNIDAD ---'))
                                                        <div class="text-xs">
                                                            <span class="text-gray-600">Notas:</span>
                                                            <p class="text-gray-700 mt-1 bg-gray-50 p-2 rounded text-xs">{{ Str::limit($unidad->observaciones, 60) }}</p>
                                                        </div>
                                                    @endif
                                                </div>
                                                
                                                <div class="mt-2 pt-2 border-t border-gray-100">
                                                    <a href="{{ route('inventario.show', $unidad) }}" 
                                                       class="text-xs text-blue-600 hover:text-blue-800 transition-colors">
                                                        Ver detalles →
                                                    </a>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>

                                    <!-- Resumen del grupo -->
                                    @php
                                        $todasLasUnidades = $unidadesSimilares->push($inventario);
                                        $funcionalesGrupo = $inventario->funcionales_grupo;
                                        $disponiblesGrupo = $inventario->disponibles_grupo;
                                        $totalGrupo = $inventario->total_grupo;
                                    @endphp
                                    
                                    <div class="mt-4 bg-white border border-purple-200 rounded-lg p-3">
                                        <h5 class="text-sm font-medium text-gray-800 mb-2">📊 Resumen del Grupo</h5>
                                        <div class="grid grid-cols-4 gap-4 text-center text-xs">
                                            <div>
                                                <div class="text-lg font-bold text-gray-900">{{ $totalGrupo }}</div>
                                                <div class="text-gray-600">Total</div>
                                            </div>
                                            <div>
                                                <div class="text-lg font-bold text-green-600">{{ $disponiblesGrupo }}</div>
                                                <div class="text-gray-600">Disponibles</div>
                                            </div>
                                            <div>
                                                <div class="text-lg font-bold text-blue-600">{{ $funcionalesGrupo }}</div>
                                                <div class="text-gray-600">Funcionales</div>
                                            </div>
                                            <div>
                                                <div class="text-lg font-bold text-red-600">{{ $totalGrupo - $funcionalesGrupo }}</div>
                                                <div class="text-gray-600">Dañadas</div>
                                            </div>
                                        </div>
                                        
                                        <div class="mt-2 text-xs text-gray-600">
                                            💡 Las unidades dañadas no están disponibles para préstamo
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <!-- Información específica de computadoras -->
                            @if($inventario->categoria === 'computadoras')
                                <div class="mt-6 bg-blue-50 p-4 rounded-lg border border-blue-200">
                                    <h4 class="text-md font-medium text-blue-800 mb-3">💻 Información de Computadora</h4>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        @if($inventario->password_computadora)
                                            <div>
                                                <label class="block text-sm font-medium text-blue-700">Contraseña</label>
                                                <div class="mt-1" x-data="{ show: false }">
                                                    <div class="flex items-center space-x-2">
                                                        <code x-show="!show" class="bg-blue-100 px-2 py-1 rounded text-sm">••••••••</code>
                                                        <code x-show="show" class="bg-blue-100 px-2 py-1 rounded text-sm">{{ $inventario->password_computadora }}</code>
                                                        <button @click="show = !show" 
                                                                class="text-blue-600 hover:text-blue-800 text-sm">
                                                            <span x-show="!show">👁️ Mostrar</span>
                                                            <span x-show="show">🙈 Ocultar</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        @endif

                                        @if($inventario->anos_uso !== null)
                                            <div>
                                                <label class="block text-sm font-medium text-blue-700">Años de Uso</label>
                                                <p class="mt-1 text-sm text-blue-900">
                                                    <span class="font-bold">{{ $inventario->anos_uso }}</span> 
                                                    año{{ $inventario->anos_uso != 1 ? 's' : '' }}
                                                </p>
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            @endif

                            <!-- Información específica para discos duros -->
                            @if($inventario->categoria === 'discos_duros' && $inventario->tiene_informacion)
                                <div class="mt-6 bg-orange-50 p-4 rounded-lg border border-orange-200">
                                    <h4 class="text-md font-medium text-orange-800 mb-3">💾 Información del Disco Duro</h4>
                                    
                                    <div class="space-y-4">
                                        <!-- Estado de información -->
                                        <div class="flex items-center space-x-2">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-800">
                                                🔒 Contiene información importante
                                            </span>
                                            @if($inventario->bloqueado_prestamo)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                                    🚫 Préstamos bloqueados
                                                </span>
                                            @endif
                                        </div>

                                        <div class="grid grid-cols-1 gap-4">
                                            <!-- Nivel de confidencialidad -->
                                            @if($inventario->nivel_confidencialidad)
                                                <div>
                                                    <label class="block text-sm font-medium text-orange-700">🛡️ Nivel de Confidencialidad</label>
                                                    <div class="mt-1">
                                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium {{ $inventario->color_confidencialidad }}">
                                                            {{ \App\Models\Inventario::getNivelesConfidencialidad()[$inventario->nivel_confidencialidad] ?? 'No especificado' }}
                                                        </span>
                                                    </div>
                                                </div>
                                            @endif

                                            <!-- Descripción del contenido -->
                                            @if($inventario->informacion_contenido)
                                                <div>
                                                    <label class="block text-sm font-medium text-orange-700">📄 Descripción del Contenido</label>
                                                    <div class="mt-1 bg-white p-3 rounded border border-orange-200">
                                                        <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ $inventario->informacion_contenido }}</p>
                                                    </div>
                                                </div>
                                            @endif

                                            <!-- Razón del bloqueo -->
                                            @if($inventario->bloqueado_prestamo && $inventario->razon_bloqueo)
                                                <div>
                                                    <label class="block text-sm font-medium text-red-700">📋 Razón del Bloqueo de Préstamos</label>
                                                    <div class="mt-1 bg-red-50 p-3 rounded border border-red-200">
                                                        <p class="text-sm text-red-900 whitespace-pre-wrap">{{ $inventario->razon_bloqueo }}</p>
                                                    </div>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif

                            @php
                                $observacionesSeparadas = $inventario->observaciones_separadas;
                            @endphp
                            @if($observacionesSeparadas['general'] || count($observacionesSeparadas['detalles']) > 0)
                                <div class="mt-6 space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-2">Observaciones</label>
                                        <div class="bg-gray-50 p-4 rounded-lg border space-y-3">
                                            @if($observacionesSeparadas['general'])
                                                <div>
                                                    <h4 class="text-sm font-semibold text-gray-800 mb-1">Generales</h4>
                                                    <p class="text-sm text-gray-900 whitespace-pre-wrap">{{ $observacionesSeparadas['general'] }}</p>
                                                </div>
                                            @endif

                                            @if(count($observacionesSeparadas['detalles']) > 0)
                                                <div>
                                                    <h4 class="text-sm font-semibold text-gray-800 mb-1">Por unidad</h4>
                                                    <ul class="list-disc list-inside space-y-1 text-sm text-gray-900">
                                                        @foreach($observacionesSeparadas['detalles'] as $detalle)
                                                            <li class="whitespace-pre-line">{{ $detalle }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    <!-- Préstamos Activos -->
                    @if($prestamosActivos->count() > 0)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">
                                    <svg class="w-5 h-5 inline-block mr-2 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Préstamos Activos ({{ $prestamosActivos->count() }})
                                </h3>
                                
                                <div class="space-y-3">
                                    @foreach($prestamosActivos as $prestamo)
                                        <div class="flex items-center justify-between p-3 bg-amber-50 rounded-lg border border-amber-200">
                                            <div class="flex-1">
                                                <p class="font-medium text-amber-900">{{ $prestamo->usuario->name }}</p>
                                                <p class="text-sm text-amber-700">
                                                    Prestado el {{ $prestamo->fecha_prestamo->format('d/m/Y') }}
                                                    @if($prestamo->fecha_devolucion_estimada)
                                                        · Devolución estimada: {{ $prestamo->fecha_devolucion_estimada->format('d/m/Y') }}
                                                    @endif
                                                </p>
                                                <p class="text-xs text-amber-600">{{ $prestamo->cantidad }} unidad(es)</p>
                                            </div>
                                            <div class="flex items-center space-x-2">
                                                @php
                                                    $dias = now()->diffInDays($prestamo->fecha_prestamo, false);
                                                @endphp
                                                <span class="text-xs {{ $dias > 30 ? 'text-red-600 bg-red-100' : ($dias > 14 ? 'text-amber-600 bg-amber-100' : 'text-green-600 bg-green-100') }} px-2 py-1 rounded">
                                                    {{ abs($dias) }} día{{ abs($dias) != 1 ? 's' : '' }}
                                                </span>
                                                <a href="{{ route('prestamos.show', $prestamo) }}" 
                                                   class="text-blue-600 hover:text-blue-800 text-sm">
                                                    Ver detalles
                                                </a>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Sidebar -->
                <div class="space-y-6">
                    <!-- Imágenes -->
                    @if($inventario->imagenes && count($inventario->imagenes) > 0)
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6">
                                <h3 class="text-lg font-medium text-gray-900 mb-4">
                                    <svg class="w-5 h-5 inline-block mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                    </svg>
                                    Imágenes ({{ count($inventario->imagenes) }})
                                </h3>
                                
                                <div class="grid grid-cols-1 gap-4" x-data="{ selectedImage: null }">
                                    @foreach($inventario->imagenes as $index => $imagen)
                                        <div class="relative group cursor-pointer" @click="selectedImage = {{ $index }}">
                                            <img src="{{ is_array($imagen) && isset($imagen['data']) ? $imagen['data'] : 'data:image/jpeg;base64,' . $imagen }}" 
                                                 class="w-full h-32 object-cover rounded-lg border hover:shadow-md transition-all duration-200" />
                                            <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-30 rounded-lg transition-all duration-200 flex items-center justify-center">
                                                <svg class="w-8 h-8 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v3m0 0v3m0-3h3m-3 0H7"></path>
                                                </svg>
                                            </div>
                                            <div class="absolute bottom-2 left-2 bg-black bg-opacity-50 text-white text-xs px-2 py-1 rounded">
                                                Imagen {{ $index + 1 }}
                                            </div>
                                        </div>
                                    @endforeach

                                    <!-- Modal de imagen -->
                                    <div x-show="selectedImage !== null" 
                                         x-transition
                                         @click.away="selectedImage = null"
                                         @keydown.escape.window="selectedImage = null"
                                         class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-75"
                                         style="display: none;">
                                        <div class="relative max-w-4xl max-h-full p-4">
                                            <template x-for="(imagen, index) in {{ json_encode($inventario->imagenes) }}" :key="index">
                                                <img x-show="selectedImage === index" 
                                                     :src="typeof imagen === 'object' && imagen.data ? imagen.data : 'data:image/jpeg;base64,' + imagen"
                                                     class="max-w-full max-h-full object-contain rounded-lg" />
                                            </template>
                                            
                                            <button @click="selectedImage = null" 
                                                    class="absolute top-4 right-4 text-white bg-black bg-opacity-50 hover:bg-opacity-75 rounded-full p-2">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                                </svg>
                                            </button>
                                            
                                            @if(count($inventario->imagenes) > 1)
                                                <!-- Navegación anterior -->
                                                <button @click="selectedImage = selectedImage > 0 ? selectedImage - 1 : {{ count($inventario->imagenes) - 1 }}"
                                                        class="absolute left-4 top-1/2 transform -translate-y-1/2 text-white bg-black bg-opacity-50 hover:bg-opacity-75 rounded-full p-2">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                                                    </svg>
                                                </button>
                                                
                                                <!-- Navegación siguiente -->
                                                <button @click="selectedImage = selectedImage < {{ count($inventario->imagenes) - 1 }} ? selectedImage + 1 : 0"
                                                        class="absolute right-4 top-1/2 transform -translate-y-1/2 text-white bg-black bg-opacity-50 hover:bg-opacity-75 rounded-full p-2">
                                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                                    </svg>
                                                </button>
                                            @endif
                                            
                                            <div class="absolute bottom-4 left-1/2 transform -translate-x-1/2 text-white bg-black bg-opacity-50 px-3 py-1 rounded">
                                                <span x-text="(selectedImage + 1) + ' de {{ count($inventario->imagenes) }}'"></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div class="p-6 text-center">
                                <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                                </svg>
                                <h3 class="text-sm font-medium text-gray-900">Sin imágenes</h3>
                                <p class="text-sm text-gray-500">Este artículo no tiene imágenes asociadas</p>
                            </div>
                        </div>
                    @endif

                    <!-- Acciones Rápidas -->
                    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div class="p-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-4">
                                <svg class="w-5 h-5 inline-block mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                Acciones Rápidas
                            </h3>
                            
                            <div class="space-y-3">
                                @if($inventario->cantidad_disponible > 0)
                                    <a href="{{ route('prestamos.create', ['inventario_id' => $inventario->id]) }}" 
                                       class="w-full bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                        </svg>
                                        Crear Préstamo
                                    </a>
                                @else
                                    <button disabled 
                                            class="w-full bg-gray-400 text-white font-medium py-2 px-4 rounded-lg cursor-not-allowed flex items-center justify-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728L5.636 5.636m12.728 12.728L18.364 5.636M5.636 18.364l12.728-12.728"></path>
                                        </svg>
                                        Sin Stock Disponible
                                    </button>
                                @endif

                                <a href="{{ route('inventario.edit', $inventario) }}" 
                                   class="w-full bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                                    </svg>
                                    Editar Artículo
                                </a>

                                <form method="POST" 
                                      action="{{ route('inventario.destroy', $inventario) }}" 
                                      onsubmit="return confirm('¿Estás seguro de eliminar este artículo? Esta acción no se puede deshacer.')"
                                      class="w-full">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" 
                                            class="w-full bg-red-600 hover:bg-red-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 flex items-center justify-center">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                        Eliminar Artículo
                                    </button>
                                </form>
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