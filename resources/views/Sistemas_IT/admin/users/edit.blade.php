@extends('layouts.master')

@section('title', 'Editar Usuario - ' . $user->name)

@section('content')

    <main class="max-w-4xl mx-auto py-8 px-4 sm:px-6 lg:px-8">

        {{-- Alertas --}}
        @foreach(['success' => 'emerald', 'error' => 'red', 'info' => 'blue'] as $key => $color)
            @if(session($key))
                <div class="mb-6 flex items-center p-4 bg-{{ $color }}-50 border border-{{ $color }}-200 rounded-2xl shadow-sm">
                    <div class="p-2 bg-{{ $color }}-100 rounded-full text-{{ $color }}-600 mr-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if($key == 'success') <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            @elseif($key == 'error') <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            @else <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 18a9 9 0 110-18 9 9 0 010 18z"></path> @endif
                        </svg>
                    </div>
                    <p class="text-{{ $color }}-800 font-medium">{{ session($key) }}</p>
                </div>
            @endif
        @endforeach

        {{-- Tarjeta principal --}}
        <div class="bg-white shadow-xl rounded-lg border border-blue-100">

            {{-- Header de la tarjeta --}}
            <div class="px-6 py-4 border-b border-gray-200">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-gray-900">Editar Usuario</h2>
                        <p class="text-gray-600 mt-1">Puedes modificar nombre, correo, contraseña, rol y área.</p>
                    </div>
                    <a href="{{ route('admin.users.show', $user) }}"
                       class="inline-flex items-center text-blue-600 hover:text-blue-800 text-sm font-medium transition-colors duration-200 group flex-shrink-0">
                        <svg class="w-4 h-4 mr-1.5 group-hover:-translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Volver al Perfil
                    </a>
                </div>
            </div>

            {{-- Info del usuario (solo lectura) --}}
            <div class="px-6 py-4 bg-blue-50/60 border-b border-blue-100">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center flex-shrink-0">
                        <span class="text-lg font-bold text-white">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </span>
                    </div>
                    <div class="min-w-0">
                        <h3 class="text-lg font-bold text-gray-900 truncate">{{ $user->name }}</h3>
                        <div class="flex flex-wrap items-center gap-2 mt-1">
                            <span class="text-xs text-gray-500">ID: #{{ $user->id }}</span>
                            <span class="text-gray-300">|</span>
                            @if($user->role === 'admin')
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-purple-50 text-purple-700 border border-purple-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span> Administrador
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-slate-100 text-slate-600 border border-slate-200">
                                    Usuario
                                </span>
                            @endif
                            <span class="text-gray-300">|</span>
                            @if($user->status === 'approved')
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-green-50 text-green-700 border border-green-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Aprobado
                                </span>
                            @elseif($user->status === 'pending')
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-yellow-50 text-yellow-700 border border-yellow-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-yellow-500 animate-pulse"></span> Pendiente
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-red-50 text-red-700 border border-red-100">
                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Rechazado
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Formulario --}}
            <form method="POST" action="{{ route('admin.users.update', $user) }}" class="p-8 space-y-6">
                @csrf
                @method('PUT')

                {{-- Nombre --}}
                <div>
                    <label for="name" class="block text-sm font-medium text-gray-700 mb-2">
                        Nombre Completo <span class="text-red-500">*</span>
                    </label>
                    <input type="text"
                           id="name"
                           name="name"
                           value="{{ old('name', $user->name) }}"
                           required
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('name') border-red-300 @enderror"
                           placeholder="Nombre completo del usuario">
                    @error('name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Email --}}
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 mb-2">
                        Correo Electrónico <span class="text-red-500">*</span>
                    </label>
                    <input type="email"
                           id="email"
                           name="email"
                           value="{{ old('email', $user->email) }}"
                           required
                           class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('email') border-red-300 @enderror"
                           placeholder="correo@ejemplo.com"
                           autocomplete="email">
                    @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Información Laboral --}}
                <div class="bg-slate-50 p-5 rounded-lg border border-slate-200 space-y-5">
                    <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide border-b border-slate-200 pb-2">
                        Información Laboral (RH)
                    </h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- Área (texto libre, default Estrategia e Innovación) --}}
                        <div>
                            <label for="area" class="block text-sm font-medium text-gray-700 mb-2">
                                Área / Empresa <span class="text-red-500">*</span>
                            </label>
                            <input type="text"
                                   id="area"
                                   name="area"
                                   value="{{ old('area', optional($user->empleado)->area ?? 'Estrategia e Innovacion') }}"
                                   required
                                   list="areas-list"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('area') border-red-300 @enderror"
                                   placeholder="Ej. Estrategia e Innovacion">
                            <datalist id="areas-list">
                                <option value="Estrategia e Innovacion">
                                <option value="Recursos Humanos">
                                <option value="Chronos Fullfillment">
                                <option value="Siegwerk">
                                <option value="AGC">
                                <option value="PPM Industries">
                                <option value="EB-Tecnica">
                                <option value="Sarrel">
                                <option value="AsiaWay">
                            </datalist>
                            @error('area')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>

                        {{-- Posición --}}
                        <div>
                            <label for="posicion" class="block text-sm font-medium text-gray-700 mb-2">
                                Cargo / Posición <span class="text-red-500">*</span>
                            </label>
                            @php($posiciones = ['Direccion','Administracion RH','Auditoria','Logistica','Legal','Post-Operacion','TI','Anexo 24'])
                            <select id="posicion" name="posicion" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('posicion') border-red-300 @enderror">
                                <option value="">Selecciona una posición</option>
                                @foreach($posiciones as $pos)
                                    <option value="{{ $pos }}" {{ old('posicion', optional($user->empleado)->posicion) === $pos ? 'selected' : '' }}>{{ $pos }}</option>
                                @endforeach
                            </select>
                            @error('posicion')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    {{-- Es Coordinador + Jefe Directo --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">
                                Rol en Organigrama
                            </label>
                            <label for="es_coordinador" class="relative inline-flex items-center cursor-pointer">
                                <input type="hidden" name="es_coordinador" value="0">
                                <input type="checkbox"
                                       id="es_coordinador"
                                       name="es_coordinador"
                                       value="1"
                                       {{ old('es_coordinador', optional($user->empleado)->es_coordinador) ? 'checked' : '' }}
                                       class="sr-only peer">
                                <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-100 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                <span class="ml-3 text-sm font-medium text-gray-700">Es Coordinador / Jefe</span>
                            </label>
                            <p class="mt-1 text-xs text-gray-500">Actívalo si este empleado tiene gente a su cargo. Aparecerá como opción de jefe directo.</p>
                        </div>

                        <div>
                            <label for="supervisor_id" class="block text-sm font-medium text-gray-700 mb-2">
                                Jefe Directo (Organigrama)
                            </label>
                        <select id="supervisor_id"
                                name="supervisor_id"
                                class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200">
                            <option value="">-- Sin Supervisor Asignado --</option>
                            @if(isset($jefes))
                                @foreach($jefes as $jefe)
                                    <option value="{{ $jefe->id }}" {{ old('supervisor_id', optional($user->empleado)->supervisor_id) == $jefe->id ? 'selected' : '' }}>
                                        {{ $jefe->nombre }} - {{ $jefe->posicion }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                        <p class="mt-1 text-xs text-gray-500">Solo aparecen coordinadores y director.</p>
                        @error('supervisor_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        </div>
                    </div>
                </div>

                {{-- Subdepartamento (Comercio Exterior) --}}
                <div id="subdepartamentoWrapper" class="{{ old('area', optional($user->empleado)->area) === 'Comercio Exterior' ? '' : 'hidden' }} bg-gray-50 p-3 rounded-md border border-gray-200">
                    <label for="subdepartamento_id" class="block text-sm font-medium text-gray-700 mb-2">
                        Subdepartamento (Comercio Exterior) <span class="text-red-500">*</span>
                    </label>
                    <select id="subdepartamento_id" name="subdepartamento_id" class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('subdepartamento_id') border-red-300 @enderror">
                        <option value="">Selecciona un subdepartamento</option>
                        @foreach($subdepartamentosCE as $sd)
                            <option value="{{ $sd->id }}" {{ (string)old('subdepartamento_id', optional($user->empleado)->subdepartamento_id) === (string)$sd->id ? 'selected' : '' }}>{{ $sd->nombre }}</option>
                        @endforeach
                    </select>
                    @error('subdepartamento_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Credenciales de Acceso --}}
                <div class="space-y-6 pt-2">
                    <h3 class="text-sm font-bold text-gray-900">Credenciales de Acceso</h3>

                    {{-- Rol --}}
                    <div>
                        <label for="role" class="block text-sm font-medium text-gray-700 mb-2">
                            Nivel de Permisos <span class="text-red-500">*</span>
                        </label>
                        <select id="role" name="role" required class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('role') border-red-300 @enderror">
                            <option value="user" {{ old('role', $user->role) === 'user' ? 'selected' : '' }}>Usuario Estándar</option>
                            <option value="admin" {{ old('role', $user->role) === 'admin' ? 'selected' : '' }}>Administrador</option>
                        </select>
                        @error('role')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">Usuario: acceso estándar · Administrador: gestión avanzada del sistema.</p>
                    </div>

                    {{-- Contraseñas --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label for="password" class="block text-sm font-medium text-gray-700 mb-2">
                                Nueva Contraseña
                            </label>
                            <input type="password"
                                   id="password"
                                   name="password"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200 @error('password') border-red-300 @enderror"
                                   placeholder="********">
                            @error('password')
                                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <p class="mt-1 text-xs text-gray-500">Dejar en blanco si no deseas cambiarla. Mínimo 8 caracteres.</p>
                        </div>

                        <div>
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 mb-2">
                                Confirmar Contraseña
                            </label>
                            <input type="password"
                                   id="password_confirmation"
                                   name="password_confirmation"
                                   class="block w-full px-3 py-2 border border-gray-300 rounded-lg shadow-sm placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors duration-200"
                                   placeholder="********">
                        </div>
                    </div>
                </div>

                {{-- Botones --}}
                <div class="flex justify-end space-x-4 pt-6 border-t border-gray-200">
                    <a href="{{ route('admin.users.show', $user) }}"
                       class="px-4 py-2 border border-gray-300 rounded-lg text-gray-700 hover:bg-gray-50 font-medium transition-colors duration-200">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition-colors duration-200 flex items-center shadow-sm">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        Guardar Cambios
                    </button>
                </div>
            </form>
        </div>

        {{-- Información del Sistema --}}
        <div class="mt-8 bg-white rounded-lg shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100">
                <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Información del Sistema</h3>
            </div>
            <div class="px-6 py-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">ID de Usuario:</span>
                        <span class="font-medium text-gray-900">#{{ $user->id }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Fecha de Registro:</span>
                        <span class="font-medium text-gray-900">{{ $user->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Tickets Creados:</span>
                        <span class="font-medium text-gray-900">{{ $user->tickets()->count() }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Préstamos Realizados:</span>
                        <span class="font-medium text-gray-900">—</span>
                    </div>
                </div>
                <p class="mt-4 text-xs text-gray-400">
                    Todos los datos del historial permanecen intactos y vinculados al ID único del usuario.
                </p>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const areaInput = document.getElementById('area');
            const wrapper = document.getElementById('subdepartamentoWrapper');
            function toggleSub() {
                if (!areaInput || !wrapper) return;
                if (areaInput.value.trim() === 'Comercio Exterior') {
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