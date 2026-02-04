@extends('layouts.master')

@section('title', 'Editar ficha técnica - Panel Administrativo')

@section('content')
    <main class="max-w-5xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-8 gap-4">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Editar ficha técnica</h2>
                <p class="text-gray-600">Actualiza la información del equipo {{ $profile->identifier ?? 'sin identificador' }}.</p>
            </div>
            <a href="{{ route('admin.maintenance.computers.show', $profile) }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-sm font-semibold">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Volver a la ficha
            </a>
        </div>

        @php
            $lastMaintenanceValue = old('last_maintenance_at', optional($profile->last_maintenance_at)->format('Y-m-d\TH:i'));
            $selectedComponents = old('replacement_components', $profile->replacement_components ?? []);
        @endphp

        <form method="POST" action="{{ route('admin.maintenance.computers.update', $profile) }}" class="bg-white border border-gray-200 rounded-xl shadow-sm p-6 space-y-6">
            @csrf
            @method('PUT')

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="identifier" class="block text-sm font-medium text-gray-700 mb-1">Identificador del equipo <span class="text-red-500">*</span></label>
                    <input type="text" id="identifier" name="identifier" value="{{ old('identifier', $profile->identifier) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    @error('identifier')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="brand" class="block text-sm font-medium text-gray-700 mb-1">Marca</label>
                        <input type="text" id="brand" name="brand" value="{{ old('brand', $profile->brand) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('brand')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="model" class="block text-sm font-medium text-gray-700 mb-1">Modelo</label>
                        <input type="text" id="model" name="model" value="{{ old('model', $profile->model) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('model')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div>
                    <label for="disk_type" class="block text-sm font-medium text-gray-700 mb-1">Tipo de disco</label>
                    <input type="text" id="disk_type" name="disk_type" value="{{ old('disk_type', $profile->disk_type) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('disk_type')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="ram_capacity" class="block text-sm font-medium text-gray-700 mb-1">Capacidad de RAM</label>
                    <input type="text" id="ram_capacity" name="ram_capacity" value="{{ old('ram_capacity', $profile->ram_capacity) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('ram_capacity')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="battery_status" class="block text-sm font-medium text-gray-700 mb-1">Estado de batería</label>
                    <select id="battery_status" name="battery_status" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="">Selecciona una opción</option>
                        <option value="functional" {{ old('battery_status', $profile->battery_status) === 'functional' ? 'selected' : '' }}>Funcional</option>
                        <option value="partially_functional" {{ old('battery_status', $profile->battery_status) === 'partially_functional' ? 'selected' : '' }}>Parcialmente funcional</option>
                        <option value="damaged" {{ old('battery_status', $profile->battery_status) === 'damaged' ? 'selected' : '' }}>Dañada</option>
                    </select>
                    @error('battery_status')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label for="last_maintenance_at" class="block text-sm font-medium text-gray-700 mb-1">Último mantenimiento registrado</label>
                    <input type="datetime-local" id="last_maintenance_at" name="last_maintenance_at" value="{{ $lastMaintenanceValue }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    @error('last_maintenance_at')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
                <div>
                    <label for="aesthetic_observations" class="block text-sm font-medium text-gray-700 mb-1">Observaciones estéticas</label>
                    <textarea id="aesthetic_observations" name="aesthetic_observations" rows="3" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ old('aesthetic_observations', $profile->aesthetic_observations) }}</textarea>
                    @error('aesthetic_observations')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                </div>
            </div>

            <div>
                <h4 class="text-sm font-semibold text-gray-700 mb-3">Componentes reemplazados</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-2">
                    @foreach($componentOptions as $value => $label)
                        <label class="inline-flex items-center text-xs text-gray-600 bg-gray-50 border border-gray-200 rounded-lg px-3 py-2">
                            <input type="checkbox" name="replacement_components[]" value="{{ $value }}" class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500" {{ is_array($selectedComponents) && in_array($value, $selectedComponents, true) ? 'checked' : '' }}>
                            {{ $label }}
                        </label>
                    @endforeach
                </div>
                @error('replacement_components')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                @error('replacement_components.*')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
            </div>

            <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 space-y-3">
                <label class="flex items-start text-sm text-gray-700">
                    <input type="checkbox" name="is_loaned" value="1" class="mt-1 mr-2 rounded border-gray-300 text-green-600 focus:ring-green-500" {{ old('is_loaned', $profile->is_loaned) ? 'checked' : '' }}>
                    <span>Marcar equipo como prestado actualmente<br><span class="text-xs text-gray-500">Proporciona los datos de la persona que tiene el equipo en resguardo.</span></span>
                </label>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="loaned_to_name" class="block text-xs font-medium text-gray-600 mb-1">Nombre de la persona</label>
                        <input type="text" id="loaned_to_name" name="loaned_to_name" value="{{ old('loaned_to_name', $profile->loaned_to_name) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('loaned_to_name')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="loaned_to_email" class="block text-xs font-medium text-gray-600 mb-1">Correo electrónico</label>
                        <input type="email" id="loaned_to_email" name="loaned_to_email" value="{{ old('loaned_to_email', $profile->loaned_to_email) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        @error('loaned_to_email')<p class="text-xs text-red-600 mt-1">{{ $message }}</p>@enderror
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div class="text-sm text-gray-500">
                    Última actualización: {{ optional($profile->updated_at)->format('d/m/Y H:i') ?? 'Sin registro' }}
                </div>
                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('admin.maintenance.computers.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition-colors text-sm font-semibold">Cancelar</a>
                    <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Guardar cambios
                    </button>
                </div>
            </div>
        </form>
    </main>
@endsection
