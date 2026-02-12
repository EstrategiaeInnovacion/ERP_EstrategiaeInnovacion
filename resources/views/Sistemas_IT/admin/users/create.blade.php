@extends('layouts.master')

@section('title', 'Crear Usuario - Admin')

@section('content')

    <main class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">
        <div class="bg-white shadow-xl rounded-lg border border-blue-100">
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Crear Nuevo Usuario</h2>
                        <p class="text-gray-600 mt-1">Completa los datos para crear el usuario y su perfil de empleado asociado.</p>
                    </div>
                    <a href="{{ route('admin.users') }}"
                       class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm font-medium transition-colors duration-200 group flex-shrink-0">
                        <svg class="w-4 h-4 mr-1.5 group-hover:-translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Volver a Gestión de Usuarios
                    </a>
                </div>
            </div>

            <form method="POST" action="{{ route('admin.users.store') }}" class="p-8 space-y-6">
                @csrf

                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nombre Completo <span class="text-red-500">*</span>
                    </label>
                    <input type="text" 
                           name="name" 
                           id="name"
                           value="{{ old('name') }}"
                           required
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('name') border-red-300 @enderror" 
                           placeholder="Nombre completo del colaborador">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="space-y-4">
                    <div>
                        <label for="area" class="block text-sm font-medium text-gray-700 mb-2">
                            Área / Departamento <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               name="area"
                               id="area"
                               list="areas-list"
                               value="{{ old('area', 'Estrategia e Innovacion') }}"
                               required
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('area') border-red-300 @enderror"
                               placeholder="Escribe o selecciona un área">
                        <datalist id="areas-list">
                            @foreach(['Estrategia e Innovacion','Recursos Humanos','Chronos Fullfillment','Siegwerk','AGC','PPM Industries','EB-Tecnica','Sarrel','AsiaWay','Comercio Exterior'] as $a)
                                <option value="{{ $a }}">
                            @endforeach
                        </datalist>
                        @error('area')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div id="subdepartamentoWrapper" class="hidden bg-gray-50 p-3 rounded-md border border-gray-200">
                        <label for="subdepartamento_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Subdepartamento (Comercio Exterior) <span class="text-red-500">*</span>
                        </label>
                        <select name="subdepartamento_id" id="subdepartamento_id" class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('subdepartamento_id') border-red-300 @enderror">
                            <option value="">Selecciona un subdepartamento</option>
                            @if(isset($subdepartamentosCE))
                                @foreach($subdepartamentosCE as $sd)
                                    <option value="{{ $sd->id }}" {{ (string)old('subdepartamento_id') === (string)$sd->id ? 'selected' : '' }}>{{ $sd->nombre }}</option>
                                @endforeach
                            @endif
                        </select>
                        @error('subdepartamento_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="bg-slate-50 p-5 rounded-lg border border-slate-200 space-y-5">
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide border-b border-slate-200 pb-2">
                        Información Laboral (RH)
                    </h3>

                    <div>
                        <label for="posicion" class="block text-sm font-medium text-gray-700 mb-2">
                            Cargo / Posición <span class="text-red-500">*</span>
                        </label>
                        <select name="posicion" id="posicion" required
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('posicion') border-red-300 @enderror">
                            <option value="">Selecciona una posición</option>
                            @foreach(['Direccion','Administracion RH','Auditoria','Logistica','Legal','Post-Operacion','TI','Anexo 24'] as $pos)
                                <option value="{{ $pos }}" {{ old('posicion') === $pos ? 'selected' : '' }}>{{ $pos }}</option>
                            @endforeach
                        </select>
                        @error('posicion')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex items-center gap-4 py-2">
                        <label for="es_coordinador" class="relative inline-flex items-center cursor-pointer">
                            <input type="hidden" name="es_coordinador" value="0">
                            <input type="checkbox" id="es_coordinador" name="es_coordinador" value="1"
                                   {{ old('es_coordinador') ? 'checked' : '' }}
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                            <span class="ml-3 text-sm font-medium text-gray-700">Es Coordinador / Jefe</span>
                        </label>
                        <p class="text-xs text-gray-500">Activar si este empleado es coordinador o jefe de área.</p>
                    </div>

                    <div>
                        <label for="supervisor_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Jefe Directo (Para Organigrama)
                        </label>
                        <select name="supervisor_id"
                                id="supervisor_id"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                            <option value="">-- Sin Supervisor Asignado --</option>
                            @if(isset($jefes))
                                @foreach($jefes as $jefe)
                                    <option value="{{ $jefe->id }}" {{ old('supervisor_id') == $jefe->id ? 'selected' : '' }}>
                                        {{ $jefe->nombre }} - {{ $jefe->posicion }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Solo coordinadores y director. Selecciona a quién le reporta.</p>
                        @error('supervisor_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <div class="space-y-6 pt-2">
                    <h3 class="text-sm font-bold text-gray-900">Credenciales de Acceso</h3>

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                            Correo Electrónico (Usuario) <span class="text-red-500">*</span>
                        </label>
                        <input type="email"
                               id="email"
                               name="email"
                               value="{{ old('email') }}"
                               required
                               class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('email') border-red-300 @enderror"
                               placeholder="correo@ejemplo.com"
                               autocomplete="email">
                        @error('email')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-2">
                            Nivel de Permisos <span class="text-red-500">*</span>
                        </label>
                        <select name="role" 
                                id="role" 
                                required
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('role') border-red-300 @enderror">
                            <option value="">Selecciona un rol</option>
                            <option value="user" {{ old('role') === 'user' ? 'selected' : '' }}>Usuario Estándar</option>
                            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Administrador</option>
                        </select>
                        @error('role')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                Contraseña <span class="text-red-500">*</span>
                            </label>
                            <input type="password" 
                                   name="password" 
                                   id="password"
                                   required
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('password') border-red-300 @enderror" 
                                   placeholder="********">
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                                Confirmar Contraseña <span class="text-red-500">*</span>
                            </label>
                            <input type="password" 
                                   name="password_confirmation" 
                                   id="password_confirmation"
                                   required
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200" 
                                   placeholder="********">
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                    <a href="{{ route('admin.users') }}" 
                       class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors duration-200">
                        Cancelar
                    </a>
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition-colors duration-200 flex items-center shadow-sm">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path>
                        </svg>
                        Crear Usuario
                    </button>
                </div>
            </form>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const areaInput = document.getElementById('area');
            const wrapper = document.getElementById('subdepartamentoWrapper');

            function toggleSub() {
                if (!areaInput || !wrapper) return;

                if (areaInput.value === 'Comercio Exterior') {
                    wrapper.classList.remove('hidden');
                } else {
                    wrapper.classList.add('hidden');
                    const subSelect = document.getElementById('subdepartamento_id');
                    if (subSelect) subSelect.value = '';
                }
            }

            toggleSub();
            areaInput.addEventListener('input', toggleSub);
            areaInput.addEventListener('change', toggleSub);
        });
    </script>

@endsection