@extends('layouts.erp')

@section('title', 'Nuevo Día Festivo')

@section('content')
<div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    {{-- Header --}}
    <div class="mb-8">
        <a href="{{ route('rh.dias-festivos.index') }}" class="inline-flex items-center gap-2 text-slate-600 hover:text-slate-900 mb-4">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver a Días Festivos
        </a>
        <h1 class="text-3xl font-bold text-slate-900">Nuevo Día Festivo</h1>
        <p class="text-slate-500 mt-1">Agrega un nuevo día festivo o inhábil al calendario</p>
    </div>

    {{-- Formulario --}}
    <div class="bg-white rounded-xl shadow-sm border border-slate-200 p-6">
        <form method="POST" action="{{ route('rh.dias-festivos.store') }}" class="space-y-6">
            @csrf

            {{-- Nombre --}}
            <div>
                <label for="nombre" class="block text-sm font-bold text-slate-700 mb-1">
                    Nombre <span class="text-red-500">*</span>
                </label>
                <input type="text" name="nombre" id="nombre" 
                       value="{{ old('nombre') }}"
                       class="w-full rounded-xl border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                       placeholder="Ej: Día de la Revolución"
                       required>
                @error('nombre')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            {{-- Fecha y Tipo --}}
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="fecha" class="block text-sm font-bold text-slate-700 mb-1">
                        Fecha <span class="text-red-500">*</span>
                    </label>
                    <input type="date" name="fecha" id="fecha" 
                           value="{{ old('fecha') }}"
                           class="w-full rounded-xl border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                           required>
                    @error('fecha')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label for="tipo" class="block text-sm font-bold text-slate-700 mb-1">
                        Tipo <span class="text-red-500">*</span>
                    </label>
                    <select name="tipo" id="tipo" 
                            class="w-full rounded-xl border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                            required>
                        <option value="">Seleccionar...</option>
                        <option value="festivo" {{ old('tipo') === 'festivo' ? 'selected' : '' }}>Día Festivo</option>
                        <option value="inhabil" {{ old('tipo') === 'inhabil' ? 'selected' : '' }}>Día Inhábil</option>
                    </select>
                    @error('tipo')
                        <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>

            {{-- Recurrente --}}
            <div class="flex items-center gap-3">
                <input type="checkbox" name="es_anual" id="es_anual" 
                       value="1" {{ old('es_anual') ? 'checked' : '' }}
                       class="w-5 h-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <label for="es_anual" class="text-sm text-slate-700">
                    <strong>Repetir anualmente</strong>
                    <span class="text-slate-500 block text-xs">El sistema recordará este día cada año</span>
                </label>
            </div>

            {{-- Descripción --}}
            <div>
                <label for="descripcion" class="block text-sm font-bold text-slate-700 mb-1">
                    Descripción / Mensaje personalizado
                </label>
                <textarea name="descripcion" id="descripcion" rows="4"
                          class="w-full rounded-xl border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                          placeholder="Mensaje que recibirán los empleados en la notificación...">{{ old('descripcion') }}</textarea>
                <p class="text-xs text-slate-400 mt-1">Este mensaje se incluirá en las notificaciones que reciban los empleados.</p>
            </div>

            {{-- Activo --}}
            <div class="flex items-center gap-3">
                <input type="checkbox" name="activo" id="activo" 
                       value="1" {{ old('activo', '1') ? 'checked' : '' }}
                       class="w-5 h-5 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                <label for="activo" class="text-sm text-slate-700">
                    <strong>Activo</strong>
                    <span class="text-slate-500 block text-xs">Los empleados recibirán notificaciones de este día</span>
                </label>
            </div>

            {{-- Botones --}}
            <div class="flex items-center justify-end gap-4 pt-4 border-t border-slate-200">
                <a href="{{ route('rh.dias-festivos.index') }}" 
                   class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition">
                    Cancelar
                </a>
                <button type="submit" 
                        class="px-6 py-2 text-sm font-bold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition">
                    Guardar Día Festivo
                </button>
            </div>
        </form>
    </div>
</div>
@endsection