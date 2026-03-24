@extends('layouts.master')

@section('title', 'Nuevo Registro - Contraseñas y Equipos')

@section('content')
<div class="min-h-screen bg-slate-50 pb-12">

    <div class="bg-white border-b border-slate-200 mb-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center gap-3 mb-1">
                <a href="{{ route('admin.credenciales.index') }}"
                   class="text-slate-400 hover:text-indigo-600 transition-colors text-sm font-medium">
                    Contraseñas y Equipos
                </a>
                <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-slate-600 text-sm font-medium">Nuevo Registro</span>
            </div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Nuevo Registro</h1>
            <p class="text-slate-500 mt-1">Introduce los datos del equipo y las credenciales del usuario.</p>
        </div>
    </div>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            <form method="POST" action="{{ route('admin.credenciales.store') }}" class="divide-y divide-slate-100">
                @csrf

                {{-- Sección: Usuario --}}
                <div class="p-8">
                    <h2 class="text-base font-bold text-slate-700 mb-5 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                        Datos del Usuario
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="sm:col-span-2">
                            <label for="user_id" class="block text-sm font-semibold text-slate-700 mb-1.5">
                                Usuario del sistema <span class="text-red-500">*</span>
                            </label>
                            <select id="user_id" name="user_id"
                                    class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition @error('user_id') border-red-400 @enderror">
                                <option value="">— Seleccionar usuario —</option>
                                @foreach($usuarios as $usuario)
                                    <option value="{{ $usuario->id }}" {{ old('user_id') == $usuario->id ? 'selected' : '' }}>
                                        {{ $usuario->name }} — {{ $usuario->email }}
                                    </option>
                                @endforeach
                            </select>
                            @error('user_id')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="nombre_usuario_sistema" class="block text-sm font-semibold text-slate-700 mb-1.5">
                                Login / Nombre de usuario <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="nombre_usuario_sistema" name="nombre_usuario_sistema"
                                   value="{{ old('nombre_usuario_sistema') }}"
                                   placeholder="ej. jlopez"
                                   class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-mono text-slate-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition @error('nombre_usuario_sistema') border-red-400 @enderror">
                            @error('nombre_usuario_sistema')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="contrasena" class="block text-sm font-semibold text-slate-700 mb-1.5">
                                Contraseña <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input type="password" id="contrasena" name="contrasena"
                                       placeholder="••••••••"
                                       class="w-full border border-slate-200 rounded-xl px-4 py-2.5 pr-12 text-sm font-mono text-slate-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition @error('contrasena') border-red-400 @enderror">
                                <button type="button" onclick="togglePassword('contrasena')"
                                        class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </button>
                            </div>
                            @error('contrasena')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                </div>

                {{-- Sección: Equipo --}}
                <div class="p-8">
                    <h2 class="text-base font-bold text-slate-700 mb-5 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Datos del Equipo
                    </h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                        <div class="sm:col-span-2">
                            <label for="equipo_asignado" class="block text-sm font-semibold text-slate-700 mb-1.5">
                                Equipo asignado <span class="text-red-500">*</span>
                            </label>
                            <input type="text" id="equipo_asignado" name="equipo_asignado"
                                   value="{{ old('equipo_asignado') }}"
                                   placeholder="ej. Dell Latitude 5530"
                                   class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition @error('equipo_asignado') border-red-400 @enderror">
                            @error('equipo_asignado')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="tipo_equipo" class="block text-sm font-semibold text-slate-700 mb-1.5">
                                Tipo de equipo <span class="text-red-500">*</span>
                            </label>
                            <select id="tipo_equipo" name="tipo_equipo"
                                    class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition @error('tipo_equipo') border-red-400 @enderror">
                                @foreach($tipos as $tipo)
                                    <option value="{{ $tipo }}" {{ old('tipo_equipo', 'Laptop') === $tipo ? 'selected' : '' }}>
                                        {{ $tipo }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tipo_equipo')
                                <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="numero_serie" class="block text-sm font-semibold text-slate-700 mb-1.5">
                                Número de serie
                            </label>
                            <input type="text" id="numero_serie" name="numero_serie"
                                   value="{{ old('numero_serie') }}"
                                   placeholder="ej. 8XFKQ23"
                                   class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-mono text-slate-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="sistema_operativo" class="block text-sm font-semibold text-slate-700 mb-1.5">
                                Sistema operativo
                            </label>
                            <input type="text" id="sistema_operativo" name="sistema_operativo"
                                   value="{{ old('sistema_operativo') }}"
                                   placeholder="ej. Windows 11 Pro"
                                   class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition">
                        </div>

                        <div class="sm:col-span-2">
                            <label for="observaciones" class="block text-sm font-semibold text-slate-700 mb-1.5">
                                Observaciones
                            </label>
                            <textarea id="observaciones" name="observaciones" rows="3"
                                      placeholder="Notas adicionales..."
                                      class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 outline-none transition resize-none">{{ old('observaciones') }}</textarea>
                        </div>
                    </div>
                </div>

                {{-- Botones --}}
                <div class="px-8 py-5 bg-slate-50/50 flex justify-end gap-3">
                    <a href="{{ route('admin.credenciales.index') }}"
                       class="px-5 py-2.5 bg-white border border-slate-200 text-slate-600 font-bold text-sm rounded-xl hover:bg-slate-50 transition">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="px-5 py-2.5 bg-indigo-600 text-white font-bold text-sm rounded-xl hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">
                        Guardar Registro
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function togglePassword(id) {
    const input = document.getElementById(id);
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>
@endpush
@endsection
