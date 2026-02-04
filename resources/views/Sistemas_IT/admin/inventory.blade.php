@extends('layouts.master')

@section('title', 'Inventario de Equipos - Sistema IT')

@section('content')
        <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8 space-y-10">
            <div class="text-center">
                <h2 class="text-3xl sm:text-4xl font-bold text-gray-900 mb-4">
                    Control de <span class="text-blue-600">Inventario</span>
                </h2>
                <p class="text-lg text-gray-600 max-w-2xl mx-auto">
                    Visualiza el estado real de cada activo agrupado por producto. Identifica unidades disponibles, prestadas o fuera de servicio.
                </p>
            </div>

            <!-- Global Stats -->
            <section class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6">
                <div class="bg-white border border-blue-100 rounded-xl shadow-sm p-6">
                    <p class="text-sm text-gray-500 uppercase tracking-wide">Total de activos</p>
                    <p class="text-3xl font-bold text-gray-900 mt-2">{{ number_format($globalStats['total']) }}</p>
                </div>
                <div class="bg-white border border-green-100 rounded-xl shadow-sm p-6">
                    <p class="text-sm text-gray-500 uppercase tracking-wide">Disponibles</p>
                    <p class="text-3xl font-bold text-green-600 mt-2">{{ number_format($globalStats['disponibles']) }}</p>
                </div>
                <div class="bg-white border border-emerald-100 rounded-xl shadow-sm p-6">
                    <p class="text-sm text-gray-500 uppercase tracking-wide">Funcionales</p>
                    <p class="text-3xl font-bold text-emerald-600 mt-2">{{ number_format($globalStats['funcionales']) }}</p>
                </div>
                <div class="bg-white border border-red-100 rounded-xl shadow-sm p-6">
                    <p class="text-sm text-gray-500 uppercase tracking-wide">No disponibles por daño</p>
                    <p class="text-3xl font-bold text-red-600 mt-2">{{ number_format($globalStats['danados']) }}</p>
                </div>
            </section>

            <!-- Filters -->
            <section class="bg-white border border-blue-100 rounded-xl shadow-sm p-6">
                <form method="GET" class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <div>
                        <h3 class="text-lg font-semibold text-gray-900">Filtros rápidos</h3>
                        <p class="text-sm text-gray-500">Ajusta la vista del inventario según disponibilidad o estado funcional.</p>
                    </div>
                    <div class="flex flex-wrap gap-4">
                        <label class="inline-flex items-center space-x-2 text-sm font-medium text-gray-700">
                            <input type="checkbox" name="solo_funcionales" value="1" class="h-5 w-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500" {{ $filters['solo_funcionales'] ? 'checked' : '' }} onchange="this.form.submit()">
                            <span>Solo funcionales</span>
                        </label>
                        <label class="inline-flex items-center space-x-2 text-sm font-medium text-gray-700">
                            <input type="checkbox" name="solo_disponibles" value="1" class="h-5 w-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500" {{ $filters['solo_disponibles'] ? 'checked' : '' }} onchange="this.form.submit()">
                            <span>Solo disponibles</span>
                        </label>
                        <a href="{{ route('admin.inventory') }}" class="text-sm text-blue-600 hover:text-blue-800 font-medium">Limpiar filtros</a>
                    </div>
                </form>
            </section>

            @php
                $estadoBadgeClasses = \App\Models\InventoryItem::estadoBadgeClasses();
            @endphp

            @if ($inventoryGroups->isEmpty())
                <section class="bg-white border border-blue-100 rounded-xl shadow-sm p-12 text-center">
                    <div class="flex flex-col items-center space-y-4">
                        <div class="w-16 h-16 bg-blue-50 border border-blue-100 rounded-full flex items-center justify-center">
                            <svg class="w-8 h-8 text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m9 1a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="text-xl font-semibold text-gray-900">No encontramos activos con los filtros aplicados</h3>
                            <p class="text-sm text-gray-500 mt-2">Prueba con otros criterios o limpia los filtros para ver el inventario completo.</p>
                        </div>
                    </div>
                </section>
            @else
                <section class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    @foreach ($inventoryGroups as $group)
                        <article class="bg-white border border-blue-100 rounded-2xl shadow-sm hover:shadow-lg transition-shadow duration-300">
                            <div class="p-6 flex flex-col space-y-6">
                                <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
                                    <div>
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-blue-50 text-blue-700 border border-blue-100 uppercase tracking-wide">
                                            {{ $group['categoria'] }}
                                        </span>
                                        <h3 class="mt-3 text-2xl font-semibold text-gray-900">{{ $group['nombre'] }}</h3>
                                        <p class="text-sm text-gray-500">{{ $group['marca'] }} {{ $group['modelo'] }}</p>
                                    </div>
                                    <div class="text-left lg:text-right">
                                        <p class="text-xs text-gray-500 uppercase tracking-wide">Código de producto</p>
                                        <p class="text-lg font-semibold text-gray-900">{{ $group['codigo_producto'] }}</p>
                                        <p class="mt-2 text-sm text-gray-500">Total de unidades: <span class="font-semibold text-gray-900">{{ $group['total'] }}</span></p>
                                    </div>
                                </div>

                                @if ($group['descripcion_general'])
                                    <p class="text-sm text-gray-600 leading-relaxed">{{ $group['descripcion_general'] }}</p>
                                @endif

                                <div class="flex flex-wrap gap-2">
                                    @foreach ($group['stateCounts'] as $state => $count)
                                        @if ($count > 0)
                                            <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $estadoBadgeClasses[$state] ?? 'bg-gray-100 text-gray-800 border border-gray-200' }}">
                                                {{ $estadoLabels[$state] ?? \Illuminate\Support\Str::headline($state) }}
                                                <span class="ml-2 font-semibold">{{ $count }}</span>
                                            </span>
                                        @endif
                                    @endforeach
                                </div>

                                <div class="border border-gray-100 rounded-xl">
                                    <div class="px-5 py-3 bg-gray-50 border-b border-gray-100 rounded-t-xl">
                                        <h4 class="text-sm font-semibold text-gray-700 uppercase tracking-wide">Detalle de unidades</h4>
                                    </div>
                                    <div class="divide-y divide-gray-100">
                                        @foreach ($group['items'] as $item)
                                            <div class="px-5 py-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
                                                <div>
                                                    <p class="text-sm font-semibold text-gray-900">ID interno: {{ $item->identificador ?? $item->id }}</p>
                                                    <p class="text-sm text-gray-500">Serie: {{ $item->numero_serie ?? 'Sin serie registrada' }}</p>
                                                    @if ($item->ubicacion)
                                                        <p class="text-xs text-gray-500 mt-1">Ubicación: {{ $item->ubicacion }}</p>
                                                    @endif
                                                    @if ($item->notas)
                                                        <p class="text-xs text-gray-500 mt-1">Notas: {{ $item->notas }}</p>
                                                    @endif
                                                </div>
                                                <div class="flex flex-wrap md:flex-nowrap items-center gap-2">
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $item->estado_badge_class }}">
                                                        {{ $item->estado_label }}
                                                    </span>
                                                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium {{ $item->es_funcional ? 'bg-emerald-50 text-emerald-700 border border-emerald-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
                                                        {{ $item->funcionamiento_label }}
                                                    </span>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        </article>
                    @endforeach
                </section>
            @endif
        </main>
    </body>
</html>
