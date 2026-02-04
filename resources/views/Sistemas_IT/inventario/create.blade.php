@extends('layouts.master')

@section('title', 'Agregar Artículo - E&I Sistema de Inventario')

@push('styles')
    @vite('resources/css/pages/inventario-create.css')
@endpush

@section('content')
        <!-- Main Content -->
        <main class="max-w-4xl mx-auto py-10 px-4 sm:px-6 lg:px-8" data-inventory-create>
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">
                            <svg class="w-6 h-6 inline-block mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Agregar Nuevo Artículo
                        </h2>
                        <div class="flex space-x-2">
                            <a href="{{ route('inventario.index') }}" 
                               class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                                </svg>
                                Volver
                            </a>
                        </div>
                    </div>

                    @if ($errors->any())
                        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                            <ul class="list-disc list-inside">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

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

                    <!-- Similar Item Alert -->
                    @if(isset($similarItem))
                        <div class="mb-6 bg-purple-50 border border-purple-200 rounded-lg p-4">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-purple-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <h3 class="text-sm font-medium text-purple-800 mb-1">Creando unidad similar</h3>
                                    <p class="text-sm text-purple-700">
                                        Se pre-cargarán los datos del artículo: <strong>{{ $similarItem->articulo }} {{ $similarItem->modelo }}</strong>
                                        <br>
                                        <span class="font-mono text-xs bg-white px-2 py-1 rounded border">{{ $similarItem->codigo_inventario }}</span>
                                    </p>
                                </div>
                            </div>
                        </div>
                    @endif

                    <!-- Form -->
                    @php
                        $defaultColorPrimario = old('color_primario', isset($similarItem) ? $similarItem->color_primario : '');
                        $defaultColorSecundario = old('color_secundario', isset($similarItem) ? $similarItem->color_secundario : '');
                        $oldUnits = old('unidades');

                        if (is_array($oldUnits) && count($oldUnits) > 0) {
                            $unidadesIniciales = array_map(function ($unidad) {
                                return [
                                    'estado' => $unidad['estado'] ?? 'nuevo',
                                    'observaciones' => $unidad['observaciones'] ?? '',
                                    'color_primario' => $unidad['color_primario'] ?? '',
                                    'color_secundario' => $unidad['color_secundario'] ?? '',
                                ];
                            }, $oldUnits);
                        } else {
                            $unidadesIniciales = [[
                                'estado' => 'nuevo',
                                'observaciones' => '',
                                'color_primario' => '',
                                'color_secundario' => '',
                            ]];
                        }
                    @endphp

                    <form action="{{ route('inventario.store') }}" method="POST" enctype="multipart/form-data"
                          x-data="{
                              categoria: '{{ isset($similarItem) ? $similarItem->categoria : old('categoria') }}',
                              tipoCreacion: '{{ old('crear_como', 'unidad_unica') }}',
                              colorPrimario: @js($defaultColorPrimario),
                              colorSecundario: @js($defaultColorSecundario)
                          }">
                        @csrf
                        
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Columna izquierda -->
                            <div class="space-y-4">
                                <div>
                                    <label for="categoria" class="block text-sm font-medium text-gray-700 mb-1">Categoría *</label>
                                    <select name="categoria" 
                                            id="categoria" 
                                            x-model="categoria"
                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" 
                                            required>
                                        <option value="">Seleccionar categoría</option>
                                        @foreach($categorias as $key => $label)
                                            <option value="{{ $key }}" 
                                                {{ (old('categoria', isset($similarItem) ? $similarItem->categoria : '') == $key) ? 'selected' : '' }}>
                                                {{ $label }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <div class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded-lg">
                                        <div class="flex items-start">
                                            <svg class="w-4 h-4 text-blue-600 mt-0.5 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <div class="text-xs text-blue-700">
                                                <p class="font-medium mb-1">💡 Código Automático por Categoría:</p>
                                                <div class="grid grid-cols-2 gap-1 font-mono text-xs">
                                                    <span>Mouse → MOU001</span>
                                                    <span>Discos → DDU001</span>
                                                    <span>RAM → RAM001</span>
                                                    <span>Cargador → CAR001</span>
                                                    <span>Batería → BAT001</span>
                                                    <span>Computadora → COM001</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div>
                                    <label for="articulo" class="block text-sm font-medium text-gray-700 mb-1">Artículo *</label>
                                    <input type="text" 
                                           name="articulo" 
                                           id="articulo" 
                                           value="{{ old('articulo', isset($similarItem) ? $similarItem->articulo : '') }}"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                           required>
                                </div>

                                <div>
                                    <label for="modelo" class="block text-sm font-medium text-gray-700 mb-1">Modelo *</label>
                                    <input type="text"
                                           name="modelo"
                                           id="modelo"
                                           value="{{ old('modelo', isset($similarItem) ? $similarItem->modelo : '') }}"
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                           required>
                                </div>

                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Color primario</label>
                                        <input type="hidden" name="color_primario" x-model="colorPrimario">
                                        <div class="flex items-center gap-3">
                                            <input type="color"
                                                   class="h-10 w-16 border border-gray-300 rounded cursor-pointer"
                                                   x-bind:value="colorPrimario || '#000000'"
                                                   @input="colorPrimario = $event.target.value">
                                            <button type="button"
                                                    class="text-xs text-gray-600 hover:text-gray-800"
                                                    @click="colorPrimario = ''">
                                                Sin color
                                            </button>
                                        </div>
                                        <p class="text-[11px] text-gray-500 mt-1">
                                            <template x-if="colorPrimario">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 text-gray-700">
                                                    <span class="w-3 h-3 rounded-full border border-gray-300 mr-2"
                                                          x-bind:style="colorPrimario ? `background-color: ${colorPrimario}` : ''"></span>
                                                    <span x-text="colorPrimario.toUpperCase()"></span>
                                                </span>
                                            </template>
                                            <template x-if="!colorPrimario">
                                                <span>Sin color seleccionado</span>
                                            </template>
                                        </p>
                                    </div>

                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1">Color secundario</label>
                                        <input type="hidden" name="color_secundario" x-model="colorSecundario">
                                        <div class="flex items-center gap-3">
                                            <input type="color"
                                                   class="h-10 w-16 border border-gray-300 rounded cursor-pointer"
                                                   x-bind:value="colorSecundario || '#000000'"
                                                   @input="colorSecundario = $event.target.value">
                                            <button type="button"
                                                    class="text-xs text-gray-600 hover:text-gray-800"
                                                    @click="colorSecundario = ''">
                                                Sin color
                                            </button>
                                        </div>
                                        <p class="text-[11px] text-gray-500 mt-1">
                                            <template x-if="colorSecundario">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 text-gray-700">
                                                    <span class="w-3 h-3 rounded-full border border-gray-300 mr-2"
                                                          x-bind:style="colorSecundario ? `background-color: ${colorSecundario}` : ''"></span>
                                                    <span x-text="colorSecundario.toUpperCase()"></span>
                                                </span>
                                            </template>
                                            <template x-if="!colorSecundario">
                                                <span>Sin color seleccionado</span>
                                            </template>
                                        </p>
                                    </div>
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div x-show="tipoCreacion !== 'multiples_unidades'" x-transition>
                                        <label for="cantidad" class="block text-sm font-medium text-gray-700 mb-1">Cantidad *</label>
                                        <input type="number" 
                                               name="cantidad" 
                                               id="cantidad" 
                                               value="{{ old('cantidad', 1) }}"
                                               min="1"
                                               max="50"
                                               class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                               :required="tipoCreacion !== 'multiples_unidades'">
                                    </div>

                                    <div x-show="tipoCreacion !== 'multiples_unidades'" x-transition>
                                        <label for="estado" class="block text-sm font-medium text-gray-700 mb-1">Estado *</label>
                                        <select name="estado" id="estado" 
                                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500" 
                                                :required="tipoCreacion !== 'multiples_unidades'">
                                            <option value="">Seleccionar estado</option>
                                            @foreach($estados as $key => $label)
                                                <option value="{{ $key }}" {{ old('estado', isset($similarItem) ? $similarItem->estado : '') == $key ? 'selected' : '' }}>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>

                                <!-- Opciones de creación -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-3">¿Cómo deseas crear este inventario? *</label>
                                    
                                    <div class="space-y-3">
                                        <div class="flex items-start space-x-3 p-3 border rounded-lg" 
                                             :class="tipoCreacion === 'unidad_unica' ? 'border-green-300 bg-green-50' : 'border-gray-200'">
                                            <input type="radio" 
                                                   name="crear_como" 
                                                   id="unidad_unica" 
                                                   value="unidad_unica"
                                                   x-model="tipoCreacion"
                                                   class="mt-1 text-green-600 focus:ring-green-500 focus:border-green-500">
                                            <div>
                                                <label for="unidad_unica" class="text-sm font-medium text-gray-700 cursor-pointer">
                                                    📦 Unidad Única con Cantidad
                                                </label>
                                                <p class="text-xs text-gray-600 mt-1">
                                                    Ejemplo: 1 registro "MOU001" con cantidad 5 (útil para items idénticos no rastreables individualmente)
                                                </p>
                                            </div>
                                        </div>

                                        <div class="flex items-start space-x-3 p-3 border rounded-lg"
                                             :class="tipoCreacion === 'multiples_unidades' ? 'border-blue-300 bg-blue-50' : 'border-gray-200'">
                                            <input type="radio" 
                                                   name="crear_como" 
                                                   id="multiples_unidades" 
                                                   value="multiples_unidades"
                                                   x-model="tipoCreacion"
                                                   class="mt-1 text-blue-600 focus:ring-blue-500 focus:border-blue-500">
                                            <div>
                                                <label for="multiples_unidades" class="text-sm font-medium text-gray-700 cursor-pointer">
                                                    🏷️ Múltiples Unidades Individuales
                                                </label>
                                                <p class="text-xs text-gray-600 mt-1">
                                                    Ejemplo: 5 registros separados "MOU001, MOU002, MOU003..." (útil para rastrear cada item individualmente)
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Sección dinámica para múltiples unidades -->
                                    <div x-show="tipoCreacion === 'multiples_unidades'"
                                         x-transition
                                         x-data="{
                                             unidades: @js($unidadesIniciales),
                                             aplicarColoresGenerales(forzar = false) {
                                                 const colorPrimarioGeneral = this.$root.colorPrimario;
                                                 const colorSecundarioGeneral = this.$root.colorSecundario;

                                                 this.unidades = this.unidades.map((unidad) => {
                                                     const colorPrimario = forzar
                                                         ? (colorPrimarioGeneral !== undefined && colorPrimarioGeneral !== null
                                                             ? colorPrimarioGeneral
                                                             : '')
                                                         : (unidad.color_primario || colorPrimarioGeneral || '');

                                                     const colorSecundario = forzar
                                                         ? (colorSecundarioGeneral !== undefined && colorSecundarioGeneral !== null
                                                             ? colorSecundarioGeneral
                                                             : '')
                                                         : (unidad.color_secundario || colorSecundarioGeneral || '');

                                                     return {
                                                         ...unidad,
                                                         color_primario: colorPrimario,
                                                         color_secundario: colorSecundario
                                                     };
                                                 });
                                             },
                                             agregarUnidad() {
                                                 this.unidades.push({
                                                     estado: 'nuevo',
                                                     observaciones: '',
                                                     color_primario: this.$root.colorPrimario || '',
                                                     color_secundario: this.$root.colorSecundario || ''
                                                 });
                                             },
                                             eliminarUnidad(index) {
                                                 if (this.unidades.length > 1) {
                                                     this.unidades.splice(index, 1);
                                                 }
                                             }
                                         }"
                                         x-init="aplicarColoresGenerales()"
                                         class="mt-4 space-y-4">

                                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                                            <div class="flex flex-col gap-2 sm:flex-row sm:items-start sm:justify-between mb-3">
                                                <div>
                                                    <h4 class="text-sm font-medium text-blue-800 mb-1">🏷️ Configurar Unidades Individuales</h4>
                                                    <p class="text-xs text-blue-600">
                                                        Cada unidad tendrá su propio código único. Solo configura los campos que pueden variar.
                                                    </p>
                                                </div>
                                                <div class="flex items-center gap-2">
                                                    <button type="button"
                                                            @click="aplicarColoresGenerales(true)"
                                                            class="px-3 py-1 text-xs font-medium text-blue-700 bg-white border border-blue-200 rounded-md hover:bg-blue-100 transition-colors">
                                                        Aplicar colores generales
                                                    </button>
                                                    <button type="button"
                                                            @click="agregarUnidad()"
                                                            class="bg-blue-600 hover:bg-blue-700 text-white text-xs px-3 py-1 rounded-md transition-colors">
                                                        + Agregar Unidad
                                                    </button>
                                                </div>
                                            </div>

                                            <div class="space-y-4 max-h-[32rem] overflow-y-auto pr-1">
                                                <template x-for="(unidad, index) in unidades" :key="index">
                                                    <div class="bg-white border border-gray-200 rounded-lg p-4">
                                                        <div class="flex items-center justify-between mb-3">
                                                            <h5 class="text-sm font-medium text-gray-700">
                                                                Unidad <span x-text="index + 1"></span>
                                                                <span class="text-xs text-gray-500 font-mono ml-2">
                                                                    (Se asignará código automáticamente)
                                                                </span>
                                                            </h5>
                                                            <button type="button" 
                                                                    x-show="unidades.length > 1"
                                                                    @click="eliminarUnidad(index)"
                                                                    class="text-red-600 hover:text-red-700 text-xs">
                                                                🗑️ Eliminar
                                                            </button>
                                                        </div>

                                                        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                                                            <!-- Estado -->
                                                            <div>
                                                                <label class="block text-xs font-medium text-gray-700 mb-1">Estado</label>
                                                                <select x-model="unidad.estado"
                                                                        :name="'unidades[' + index + '][estado]'"
                                                                        class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                                                        required>
                                                                    @foreach($estados as $key => $label)
                                                                        <option value="{{ $key }}">{{ $label }}</option>
                                                                    @endforeach
                                                                </select>
                                                            </div>

                                                            <!-- Color primario -->
                                                            <div>
                                                                <label class="block text-xs font-medium text-gray-700 mb-1">Color primario</label>
                                                                <input type="hidden" :name="'unidades[' + index + '][color_primario]'" x-model="unidad.color_primario">
                                                                <div class="flex items-center gap-2">
                                                                    <input type="color"
                                                                           class="h-9 w-14 border border-gray-300 rounded cursor-pointer"
                                                                           x-bind:value="unidad.color_primario || $root.colorPrimario || '#000000'"
                                                                           @input="unidad.color_primario = $event.target.value">
                                                                    <button type="button"
                                                                            class="text-[11px] text-gray-600 hover:text-gray-800"
                                                                            @click="unidad.color_primario = ''">
                                                                        Sin color
                                                                    </button>
                                                                </div>
                                                                <p class="text-[11px] text-gray-500 mt-1">
                                                                    <template x-if="unidad.color_primario">
                                                                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 text-gray-700">
                                                                            <span class="w-3 h-3 rounded-full border border-gray-300 mr-1"
                                                                                  x-bind:style="unidad.color_primario ? `background-color: ${unidad.color_primario}` : ''"></span>
                                                                            <span x-text="unidad.color_primario.toUpperCase()"></span>
                                                                        </span>
                                                                    </template>
                                                                    <template x-if="!unidad.color_primario">
                                                                        <span>Sin color</span>
                                                                    </template>
                                                                </p>
                                                            </div>

                                                            <!-- Color secundario -->
                                                            <div>
                                                                <label class="block text-xs font-medium text-gray-700 mb-1">Color secundario</label>
                                                                <input type="hidden" :name="'unidades[' + index + '][color_secundario]'" x-model="unidad.color_secundario">
                                                                <div class="flex items-center gap-2">
                                                                    <input type="color"
                                                                           class="h-9 w-14 border border-gray-300 rounded cursor-pointer"
                                                                           x-bind:value="unidad.color_secundario || $root.colorSecundario || '#000000'"
                                                                           @input="unidad.color_secundario = $event.target.value">
                                                                    <button type="button"
                                                                            class="text-[11px] text-gray-600 hover:text-gray-800"
                                                                            @click="unidad.color_secundario = ''">
                                                                        Sin color
                                                                    </button>
                                                                </div>
                                                                <p class="text-[11px] text-gray-500 mt-1">
                                                                    <template x-if="unidad.color_secundario">
                                                                        <span class="inline-flex items-center px-2 py-0.5 rounded bg-gray-100 text-gray-700">
                                                                            <span class="w-3 h-3 rounded-full border border-gray-300 mr-1"
                                                                                  x-bind:style="unidad.color_secundario ? `background-color: ${unidad.color_secundario}` : ''"></span>
                                                                            <span x-text="unidad.color_secundario.toUpperCase()"></span>
                                                                        </span>
                                                                    </template>
                                                                    <template x-if="!unidad.color_secundario">
                                                                        <span>Sin color</span>
                                                                    </template>
                                                                </p>
                                                            </div>

                                                            <!-- Observaciones específicas -->
                                                            <div class="md:col-span-2">
                                                                <label class="block text-xs font-medium text-gray-700 mb-1">Observaciones específicas</label>
                                                                <textarea x-model="unidad.observaciones"
                                                                          :name="'unidades[' + index + '][observaciones]'"
                                                                          rows="2"
                                                                          class="w-full text-sm rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                                                          placeholder="Detalles únicos de esta unidad..."></textarea>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </div>

                                            <div class="mt-3 p-2 bg-amber-50 border border-amber-200 rounded text-xs text-amber-700">
                                                <strong>💡 Información compartida:</strong> Categoría, Artículo, Modelo y otras características generales se aplicarán a todas las unidades.
                                                Solo los campos arriba pueden variar entre unidades.
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Campos específicos para computadoras -->
                                <div x-show="categoria === 'computadoras'" x-transition class="space-y-4">
                                    <div class="bg-blue-50 p-4 rounded-lg border border-blue-200">
                                        <h4 class="text-md font-medium text-blue-800 mb-3">💻 Información de Computadora</h4>
                                        
                                        <div class="grid grid-cols-1 gap-4">
                                            <div>
                                                <label for="password_computadora" class="block text-sm font-medium text-gray-700 mb-1">Contraseña</label>
                                                <input type="text" 
                                                       name="password_computadora" 
                                                       id="password_computadora" 
                                                       value="{{ old('password_computadora') }}"
                                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                                       placeholder="Contraseña de acceso">
                                            </div>

                                            <div>
                                                <label for="anos_uso" class="block text-sm font-medium text-gray-700 mb-1">Años de Uso</label>
                                                <input type="number" 
                                                       name="anos_uso" 
                                                       id="anos_uso" 
                                                       value="{{ old('anos_uso') }}"
                                                       min="0"
                                                       max="50"
                                                       class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                                       placeholder="0">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Campos específicos para discos duros -->
                                <div x-show="categoria === 'discos_duros'" x-transition class="space-y-4">
                                    <div class="bg-orange-50 p-4 rounded-lg border border-orange-200">
                                        <h4 class="text-md font-medium text-orange-800 mb-3">💾 Información del Disco Duro</h4>
                                        
                                        <div class="space-y-4" x-data="{ tieneInfo: false, bloqueado: false }">
                                            <!-- Checkbox para marcar si tiene información -->
                                            <div class="flex items-start space-x-3">
                                                <input type="checkbox" 
                                                       name="tiene_informacion" 
                                                       id="tiene_informacion"
                                                       value="1"
                                                       x-model="tieneInfo"
                                                       class="mt-1 rounded border-gray-300 text-orange-600 focus:border-orange-500 focus:ring-orange-500">
                                                <div class="flex-1">
                                                    <label for="tiene_informacion" class="text-sm font-medium text-gray-700">
                                                        🔒 Este disco contiene información importante
                                                    </label>
                                                    <p class="text-xs text-gray-500 mt-1">
                                                        Marca esta opción si el disco tiene datos, documentos o información que requiere protección especial
                                                    </p>
                                                </div>
                                            </div>

                                            <!-- Campos que aparecen cuando se marca "tiene información" -->
                                            <div x-show="tieneInfo" x-transition class="space-y-4 mt-4 p-3 bg-white rounded border border-orange-200">
                                                <!-- Nivel de confidencialidad -->
                                                <div>
                                                    <label for="nivel_confidencialidad" class="block text-sm font-medium text-gray-700 mb-1">
                                                        🛡️ Nivel de Confidencialidad
                                                    </label>
                                                    <select name="nivel_confidencialidad" 
                                                            id="nivel_confidencialidad"
                                                            class="w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                                        <option value="">Seleccionar nivel</option>
                                                        @foreach(\App\Models\Inventario::getNivelesConfidencialidad() as $key => $label)
                                                            <option value="{{ $key }}" {{ (old('nivel_confidencialidad') == $key) ? 'selected' : '' }}>
                                                                {{ $label }}
                                                            </option>
                                                        @endforeach
                                                    </select>
                                                </div>

                                                <!-- Descripción del contenido -->
                                                <div>
                                                    <label for="informacion_contenido" class="block text-sm font-medium text-gray-700 mb-1">
                                                        📄 Descripción del Contenido
                                                    </label>
                                                    <textarea name="informacion_contenido" 
                                                              id="informacion_contenido"
                                                              rows="3"
                                                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-orange-500 focus:ring-orange-500"
                                                              placeholder="Describe qué tipo de información contiene (ej: Documentos financieros, bases de datos de clientes, código fuente, etc.)">{{ old('informacion_contenido') }}</textarea>
                                                </div>

                                                <!-- Bloquear préstamos -->
                                                <div class="flex items-start space-x-3">
                                                    <input type="checkbox" 
                                                           name="bloqueado_prestamo" 
                                                           id="bloqueado_prestamo"
                                                           value="1"
                                                           x-model="bloqueado"
                                                           class="mt-1 rounded border-gray-300 text-red-600 focus:border-red-500 focus:ring-red-500">
                                                    <div class="flex-1">
                                                        <label for="bloqueado_prestamo" class="text-sm font-medium text-gray-700">
                                                            🚫 Bloquear préstamos
                                                        </label>
                                                        <p class="text-xs text-gray-500 mt-1">
                                                            Impide que este disco sea prestado a usuarios
                                                        </p>
                                                    </div>
                                                </div>

                                                <!-- Razón del bloqueo -->
                                                <div x-show="bloqueado" x-transition>
                                                    <label for="razon_bloqueo" class="block text-sm font-medium text-gray-700 mb-1">
                                                        📋 Razón del Bloqueo
                                                    </label>
                                                    <textarea name="razon_bloqueo" 
                                                              id="razon_bloqueo"
                                                              rows="2"
                                                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-red-500 focus:ring-red-500"
                                                              placeholder="Explica por qué este disco no puede ser prestado (ej: Contiene información confidencial de la empresa)">{{ old('razon_bloqueo') }}</textarea>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Columna derecha -->
                            <div class="space-y-4">
                                <div>
                                    <label for="observaciones" class="block text-sm font-medium text-gray-700 mb-1">
                                        <span x-show="tipoCreacion !== 'multiples_unidades'">Observaciones</span>
                                        <span x-show="tipoCreacion === 'multiples_unidades'">Observaciones Generales (opcionales)</span>
                                    </label>
                                    <textarea name="observaciones" 
                                              id="observaciones" 
                                              rows="4"
                                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-green-500 focus:ring-green-500"
                                              x-bind:placeholder="tipoCreacion === 'multiples_unidades' ? 
                                                  'Información que se aplicará a todas las unidades (ej: marca, año de compra, etc.)' : 
                                                  'Detalles adicionales, condiciones especiales, etc.'">{{ old('observaciones', isset($similarItem) ? $similarItem->observaciones : '') }}</textarea>
                                    <p x-show="tipoCreacion === 'multiples_unidades'" class="text-xs text-gray-500 mt-1">
                                        💡 Las observaciones específicas de cada unidad se configuran más arriba
                                    </p>
                                </div>

                                <div>
                                    <label for="imagenes" class="block text-sm font-medium text-gray-700 mb-1">Imágenes (opcional)</label>
                                    <input type="file" 
                                           name="imagenes[]" 
                                           id="imagenes" 
                                           multiple
                                           accept="image/jpeg,image/jpg,image/png"
                                           class="w-full border border-gray-300 rounded-md p-2 focus:border-green-500 focus:ring-green-500">
                                    <div class="mt-1 flex items-center space-x-4">
                                        <p class="text-xs text-gray-500">
                                            <strong>📁 Formatos:</strong> Solo JPG, JPEG y PNG. <strong>💡 Tip:</strong> Mantén <kbd class="px-1 py-0.5 bg-gray-200 rounded text-xs">Ctrl</kbd> presionado para seleccionar múltiples archivos
                                        </p>
                                        <span id="image_count" class="text-xs text-green-600 font-medium"></span>
                                    </div>
                                    <div id="image_preview" class="mt-3 grid grid-cols-3 gap-2"></div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 mt-6 pt-6 border-t">
                            <a href="{{ route('inventario.index') }}" 
                               class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                                Cancelar
                            </a>
                            <button type="submit" 
                                    class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-6 rounded-lg transition-colors duration-200 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                                </svg>
                                Agregar Artículo
                            </button>
                        </div>
                    </form>
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

        @push('scripts')
            @vite('resources/js/Sistemas_IT/inventario-create.js')
        @endpush

@endsection