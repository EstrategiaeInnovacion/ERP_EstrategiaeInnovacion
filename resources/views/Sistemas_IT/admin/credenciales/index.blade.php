@extends('layouts.master')

@section('title', 'Contraseñas y Equipos - IT')

@section('content')
<div class="min-h-screen bg-slate-50 pb-12" x-data="equipoModal(@json($usuarios))">

    {{-- Header --}}
    <div class="bg-white border-b border-slate-200 mb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <a href="{{ route('admin.dashboard') }}"
                           class="text-slate-400 hover:text-indigo-600 transition-colors text-sm font-medium">
                            Panel Admin
                        </a>
                        <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                        <span class="text-slate-600 text-sm font-medium">Contraseñas y Equipos</span>
                    </div>
                    <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Contraseñas y Equipos</h1>
                    <p class="text-slate-500 mt-1">Gestión de credenciales y equipos asignados al personal.</p>
                </div>
                <button @click="abrirModal()"
                        class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white font-bold text-sm rounded-xl hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Añadir
                </button>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- Flash messages --}}
        @if(session('success'))
        <div class="flex items-center gap-3 bg-green-50 border border-green-200 text-green-800 rounded-xl px-4 py-3 text-sm">
            <svg class="w-5 h-5 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
            </svg>
            {{ session('success') }}
        </div>
        @endif

        {{-- Search --}}
        <form method="GET" class="flex gap-3">
            <div class="relative flex-1 max-w-md">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
                </svg>
                <input type="text" name="search" value="{{ request('search') }}"
                       placeholder="Buscar por usuario, equipo o serie..."
                       class="pl-9 pr-4 py-2 w-full border border-slate-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
            </div>
            <button type="submit"
                    class="px-4 py-2 bg-indigo-600 text-white rounded-xl text-sm font-medium hover:bg-indigo-700 transition">
                Buscar
            </button>
            @if(request('search'))
            <a href="{{ route('admin.credenciales.index') }}"
               class="px-4 py-2 bg-slate-100 text-slate-600 rounded-xl text-sm font-medium hover:bg-slate-200 transition">
                Limpiar
            </a>
            @endif
        </form>

        {{-- Table --}}
        <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
            @if($equipos->isEmpty())
            <div class="text-center py-16 text-slate-400">
                <svg class="w-12 h-12 mx-auto mb-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                          d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
                <p class="font-medium text-slate-500">No hay registros aún.</p>
                <p class="text-sm mt-1">Usa el botón <strong>Añadir</strong> para agregar un equipo.</p>
            </div>
            @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-200">
                            <th class="text-left px-4 py-3 font-semibold text-slate-600">#</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-600">Usuario</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-600">Equipo</th>
                            <th class="text-left px-4 py-3 font-semibold text-slate-600">N° Serie</th>
                            <th class="text-center px-4 py-3 font-semibold text-slate-600">Periféricos</th>
                            <th class="text-center px-4 py-3 font-semibold text-slate-600">Correos</th>
                            <th class="text-center px-4 py-3 font-semibold text-slate-600">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($equipos as $equipo)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="px-4 py-3 text-slate-400 font-mono text-xs">
                                {{ $equipos->firstItem() + $loop->index }}
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-full bg-indigo-100 text-indigo-700 font-bold text-sm flex items-center justify-center shrink-0">
                                        {{ strtoupper(substr($equipo->user->name ?? '?', 0, 1)) }}
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-800">{{ $equipo->user->name ?? '—' }}</p>
                                        <p class="text-xs text-slate-400">{{ $equipo->user->email ?? '' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-slate-800">{{ $equipo->nombre_equipo }}</p>
                                <p class="text-xs text-slate-400">{{ $equipo->modelo ?? '—' }}</p>
                            </td>
                            <td class="px-4 py-3 text-slate-600 font-mono text-xs">
                                {{ $equipo->numero_serie ?? '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full
                                    {{ $equipo->perifericos->count() > 0 ? 'bg-violet-100 text-violet-700' : 'bg-slate-100 text-slate-400' }}
                                    text-xs font-bold">
                                    {{ $equipo->perifericos->count() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <span class="inline-flex items-center justify-center w-7 h-7 rounded-full
                                    {{ $equipo->correos->count() > 0 ? 'bg-sky-100 text-sky-700' : 'bg-slate-100 text-slate-400' }}
                                    text-xs font-bold">
                                    {{ $equipo->correos->count() }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-center gap-2">
                                    {{-- Visualizar --}}
                                    <a href="{{ route('admin.credenciales.show', $equipo) }}"
                                       title="Visualizar"
                                       class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                  d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                        </svg>
                                    </a>
                                    {{-- Eliminar --}}
                                    <form action="{{ route('admin.credenciales.destroy', $equipo) }}" method="POST"
                                          onsubmit="return confirm('¿Eliminar este registro? Esta acción no se puede deshacer.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" title="Eliminar"
                                                class="p-1.5 rounded-lg bg-red-50 text-red-500 hover:bg-red-100 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pagination --}}
            @if($equipos->hasPages())
            <div class="px-6 py-4 border-t border-slate-100">
                {{ $equipos->links() }}
            </div>
            @endif
            @endif
        </div>
    </div>

    {{-- ===================== MODAL ===================== --}}
    <div x-show="modalOpen"
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center p-4"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0">

        {{-- Backdrop --}}
        <div class="absolute inset-0 bg-black/50" @click="cerrarModal()"></div>

        {{-- Panel --}}
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl flex flex-col max-h-[92vh]"
             x-transition:enter="transition ease-out duration-200"
             x-transition:enter-start="opacity-0 scale-95 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0">

            {{-- Modal header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 shrink-0">
                <div>
                    <h2 class="text-lg font-bold text-slate-900">Añadir Equipo y Credenciales</h2>
                    <p class="text-xs text-slate-400 mt-0.5">Selecciona un usuario para consultar su equipo asignado.</p>
                </div>
                <button @click="cerrarModal()"
                        class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Modal body (scrollable) --}}
            <div class="overflow-y-auto flex-1 px-6 py-5 space-y-5">

                {{-- Error --}}
                <div x-show="errorMsg"
                     class="flex items-start gap-3 bg-red-50 border border-red-200 rounded-xl px-4 py-3 text-red-700 text-sm">
                    <svg class="w-5 h-5 shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/>
                    </svg>
                    <span x-text="errorMsg"></span>
                </div>

                {{-- ---- Step: Select user ---- --}}
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                        Usuario <span class="text-red-500">*</span>
                    </label>
                    <select x-model="userId"
                            @change="onUserChange()"
                            class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                        <option value="">— Seleccionar usuario —</option>
                        <template x-for="u in usuarios" :key="u.id">
                            <option :value="u.id" x-text="`${u.name} (${u.email})`"></option>
                        </template>
                    </select>
                </div>

                {{-- ---- Loading ---- --}}
                <div x-show="step === 'loading'" class="flex items-center gap-3 text-slate-500 text-sm py-2">
                    <svg class="w-5 h-5 animate-spin text-indigo-500" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                    Consultando sistema de activos…
                </div>

                {{-- ---- Step: Select available device ---- --}}
                <div x-show="step === 'select_device'">
                    <p class="text-sm font-semibold text-slate-700 mb-2">Este usuario no tiene equipo asignado. Selecciona uno disponible:</p>
                    <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
                        <template x-for="d in disponibles" :key="d.uuid">
                            <button type="button"
                                    @click="seleccionarDisponible(d)"
                                    class="w-full text-left px-4 py-3 rounded-xl border border-slate-200 hover:border-indigo-400 hover:bg-indigo-50 transition flex items-center gap-4 group">
                                <div class="w-12 h-12 bg-slate-100 rounded-lg overflow-hidden shrink-0 flex items-center justify-center">
                                    <template x-if="d.photos && d.photos.length">
                                        <img :src="`{{ url('admin/activos-api/fotos') }}/${d.photos[0].id}`"
                                             class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!d.photos || !d.photos.length">
                                        <svg class="w-6 h-6 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                  d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </template>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-medium text-slate-800 truncate" x-text="d.name ?? d.nombre ?? 'Sin nombre'"></p>
                                    <p class="text-xs text-slate-500 truncate" x-text="(d.model ?? d.modelo ?? '') + (d.device_type ? ' · ' + d.device_type.name : '')"></p>
                                    <p class="text-xs text-slate-400 font-mono" x-text="'S/N: ' + (d.serial_number ?? d.serie ?? 'N/A')"></p>
                                </div>
                                <svg class="w-5 h-5 text-slate-300 group-hover:text-indigo-500 transition shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                </svg>
                            </button>
                        </template>
                        <div x-show="!disponibles.length" class="text-sm text-slate-400 text-center py-6">
                            No hay equipos disponibles en el sistema de activos.
                        </div>
                    </div>
                </div>

                {{-- ---- Device card (shows once device is selected) ---- --}}
                <template x-if="device && (step === 'credentials' || step === 'select_perif')">
                    <div class="bg-slate-50 rounded-xl border border-slate-200 p-4 flex items-start gap-4">
                        <div class="w-16 h-16 bg-white rounded-lg border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center">
                            <template x-if="device.photo_id">
                                <img :src="`{{ url('admin/activos-api/fotos') }}/${device.photo_id}`"
                                     class="w-full h-full object-cover">
                            </template>
                            <template x-if="!device.photo_id">
                                <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                          d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                            </template>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <p class="font-semibold text-slate-800" x-text="device.nombre || 'Equipo'"></p>
                                    <p class="text-sm text-slate-500" x-text="device.modelo || '—'"></p>
                                    <p class="text-xs text-slate-400 font-mono" x-text="'S/N: ' + (device.serie || 'N/A')"></p>
                                </div>
                                <span x-show="device.assign_new"
                                      class="shrink-0 text-xs bg-amber-100 text-amber-700 rounded-full px-2.5 py-1 font-medium">
                                    Se asignará al guardar
                                </span>
                                <span x-show="!device.assign_new"
                                      class="shrink-0 text-xs bg-blue-100 text-blue-700 rounded-full px-2.5 py-1 font-medium">
                                    Ya asignado
                                </span>
                            </div>
                        </div>
                        <button type="button" title="Cambiar equipo" @click="cambiarEquipo()"
                                class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-200 rounded-lg transition shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                            </svg>
                        </button>
                    </div>
                </template>

                {{-- ---- Step: Credentials ---- --}}
                <div x-show="step === 'credentials'">

                    {{-- PC Username + Password --}}
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                Usuario de PC <span class="text-red-500">*</span>
                            </label>
                            <input x-model="nombreUsuarioPc"
                                   type="text" placeholder="ej. juanperez"
                                   class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-1.5">
                                Contraseña del Equipo <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <input x-model="contrasenaEquipo"
                                       :type="showCont ? 'text' : 'password'"
                                       placeholder="••••••••"
                                       class="w-full border border-slate-200 rounded-xl px-3 py-2.5 pr-10 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <button type="button" @click="showCont = !showCont"
                                        class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                    <svg x-show="!showCont" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                    <svg x-show="showCont" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 4.411m0 0L21 21"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </div>

                    {{-- Notas --}}
                    <div class="mb-5">
                        <label class="block text-sm font-semibold text-slate-700 mb-1.5">Notas (opcional)</label>
                        <textarea x-model="notas" rows="2" placeholder="Observaciones sobre el equipo o la asignación..."
                                  class="w-full border border-slate-200 rounded-xl px-3 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 resize-none"></textarea>
                    </div>

                    {{-- ---- Emails ---- --}}
                    <div class="border-t border-slate-100 pt-4 mb-5">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                <svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                </svg>
                                Correos del Equipo
                            </h3>
                            <button type="button" @click="addCorreo()"
                                    class="inline-flex items-center gap-1.5 text-xs font-medium text-indigo-600 hover:text-indigo-800 hover:bg-indigo-50 rounded-lg px-2.5 py-1.5 transition">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                </svg>
                                Añadir correo
                            </button>
                        </div>

                        <div class="space-y-2">
                            <template x-for="(c, i) in correos" :key="i">
                                <div class="flex items-center gap-2">
                                    <input x-model="c.correo"
                                           type="email" placeholder="correo@dominio.com"
                                           class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 min-w-0">
                                    <div class="relative w-44 shrink-0">
                                        <input x-model="c.contrasena"
                                               :type="c.show ? 'text' : 'password'"
                                               placeholder="Contraseña"
                                               class="w-full border border-slate-200 rounded-lg px-3 py-2 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                        <button type="button" @click="c.show = !c.show"
                                                class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                            <svg x-show="!c.show" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                            <svg x-show="c.show" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                      d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 4.411m0 0L21 21"/>
                                            </svg>
                                        </button>
                                    </div>
                                    <button type="button" @click="removeCorreo(i)"
                                            class="p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                            <p x-show="!correos.length" class="text-xs text-slate-400 italic">Sin correos agregados.</p>
                        </div>
                    </div>

                    {{-- ---- Peripherals ---- --}}
                    <div class="border-t border-slate-100 pt-4">
                        <div class="flex items-center justify-between mb-3">
                            <h3 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                <svg class="w-4 h-4 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                </svg>
                                Periféricos (opcional)
                            </h3>
                        </div>

                        <div class="flex gap-2 mb-3">
                            <select x-model="selectedPerUuid"
                                    @focus="cargarPerifericos()"
                                    class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white min-w-0">
                                <option value="">— Seleccionar periférico disponible —</option>
                                <template x-for="d in perifericos_disponibles.filter(d => d.uuid !== device?.uuid && !perifericos.find(p => p.uuid === d.uuid))" :key="d.uuid">
                                    <option :value="d.uuid"
                                            x-text="`${d.name ?? d.nombre ?? 'Sin nombre'} ${d.device_type ? '(' + d.device_type.name + ')' : ''} — S/N: ${d.serial_number ?? d.serie ?? 'N/A'}`">
                                    </option>
                                </template>
                            </select>
                            <button type="button" @click="addPeriferico()"
                                    :disabled="!selectedPerUuid"
                                    class="px-4 py-2 bg-violet-600 text-white rounded-lg text-sm font-medium hover:bg-violet-700 disabled:opacity-50 disabled:cursor-not-allowed transition shrink-0">
                                Añadir
                            </button>
                        </div>

                        <div class="space-y-1.5">
                            <template x-for="(p, i) in perifericos" :key="i">
                                <div class="flex items-center gap-3 bg-violet-50 rounded-lg px-3 py-2 text-sm">
                                    <div class="flex-1 min-w-0">
                                        <span class="font-medium text-slate-800" x-text="p.nombre"></span>
                                        <span x-show="p.tipo" class="ml-2 text-xs text-violet-600 bg-violet-100 rounded-full px-2 py-0.5" x-text="p.tipo"></span>
                                        <span x-show="p.serie" class="ml-1 text-xs text-slate-400 font-mono" x-text="`S/N: ${p.serie}`"></span>
                                    </div>
                                    <button type="button" @click="removePeriferico(i)"
                                            class="p-1 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-md transition shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                            <p x-show="!perifericos.length" class="text-xs text-slate-400 italic">Sin periféricos asignados.</p>
                        </div>
                    </div>

                </div>
                {{-- end credentials step --}}

            </div>
            {{-- end body --}}

            {{-- Modal footer --}}
            <div class="flex items-center justify-end gap-3 px-6 py-4 border-t border-slate-200 bg-slate-50 rounded-b-2xl shrink-0">
                <button type="button" @click="cerrarModal()"
                        class="px-4 py-2 text-sm font-medium text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition">
                    Cancelar
                </button>
                <button type="button"
                        @click="enviar()"
                        x-show="step === 'credentials'"
                        :disabled="!device || !nombreUsuarioPc.trim() || !contrasenaEquipo.trim() || enviando"
                        class="inline-flex items-center gap-2 px-5 py-2 text-sm font-bold text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed transition shadow-sm shadow-indigo-200">
                    <svg x-show="enviando" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                    </svg>
                    <span x-text="enviando ? 'Guardando…' : 'Guardar registro'"></span>
                </button>
            </div>

        </div>
    </div>
    {{-- end modal --}}

</div>
@endsection

@push('scripts')
<script>
function equipoModal(usuarios) {
    return {
        // state
        modalOpen: false,
        step: 'select_user', // select_user | loading | select_device | credentials
        usuarios: usuarios,
        userId: '',
        userHasDevice: false,
        disponibles: [],
        device: null,       // { uuid, nombre, modelo, serie, photo_id, assign_new }

        // credentials form
        nombreUsuarioPc: '',
        contrasenaEquipo: '',
        showCont: false,
        notas: '',

        // emails
        correos: [],

        // peripherals
        perifericos: [],
        perifericos_disponibles: [],
        selectedPerUuid: '',

        // ui state
        enviando: false,
        errorMsg: null,

        // ---- lifecycle ----
        abrirModal() {
            this.resetModal();
            this.modalOpen = true;
        },

        cerrarModal() {
            this.modalOpen = false;
            setTimeout(() => this.resetModal(), 200);
        },

        resetModal() {
            this.step = 'select_user';
            this.userId = '';
            this.device = null;
            this.disponibles = [];
            this.nombreUsuarioPc = '';
            this.contrasenaEquipo = '';
            this.showCont = false;
            this.notas = '';
            this.correos = [];
            this.perifericos = [];
            this.perifericos_disponibles = [];
            this.selectedPerUuid = '';
            this.enviando = false;
            this.errorMsg = null;
            this.userHasDevice = false;
        },

        // ---- device lookup ----
        async onUserChange() {
            if (!this.userId) return;
            this.step = 'loading';
            this.device = null;
            this.errorMsg = null;

            try {
                const resp = await fetch(
                    `{{ url('admin/activos-api/usuario') }}/${this.userId}/equipo`,
                    { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
                );

                if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
                const data = await resp.json();
                this.userHasDevice = data.has_device;

                if (data.has_device && data.devices && data.devices.length) {
                    const d = data.devices[0];
                    this.device = this.mapDevice(d, false);
                    this.step = 'credentials';
                } else {
                    await this.cargarDisponibles();
                    this.step = 'select_device';
                }
            } catch (e) {
                this.errorMsg = 'Error al consultar el sistema de activos: ' + e.message;
                this.step = 'select_user';
            }
        },

        async cargarDisponibles() {
            try {
                const resp = await fetch(
                    '{{ url("admin/activos-api/equipos-disponibles") }}',
                    { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
                );
                if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
                this.disponibles = await resp.json();
            } catch (e) {
                this.disponibles = [];
                this.errorMsg = 'No se pudieron cargar los equipos disponibles.';
            }
        },

        seleccionarDisponible(d) {
            this.device = this.mapDevice(d, true);
            this.step = 'credentials';
        },

        cambiarEquipo() {
            this.device = null;
            if (this.userHasDevice) {
                this.onUserChange();
            } else {
                this.step = 'select_device';
            }
        },

        mapDevice(d, assign_new) {
            return {
                uuid:      d.uuid ?? d.id ?? '',
                nombre:    d.name ?? d.nombre ?? '',
                modelo:    d.model ?? d.modelo ?? '',
                serie:     d.serial_number ?? d.serie ?? '',
                photo_id:  (d.photos && d.photos.length) ? d.photos[0].id : null,
                assign_new: assign_new,
            };
        },

        // ---- emails ----
        addCorreo() {
            this.correos.push({ correo: '', contrasena: '', show: false });
        },
        removeCorreo(i) {
            this.correos.splice(i, 1);
        },

        // ---- peripherals ----
        async cargarPerifericos() {
            if (this.perifericos_disponibles.length) return;
            try {
                const resp = await fetch(
                    '{{ url("admin/activos-api/equipos-disponibles") }}',
                    { headers: { 'X-Requested-With': 'XMLHttpRequest' } }
                );
                if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
                this.perifericos_disponibles = await resp.json();
            } catch (e) {
                this.perifericos_disponibles = [];
            }
        },

        addPeriferico() {
            if (!this.selectedPerUuid) return;
            const d = this.perifericos_disponibles.find(x => x.uuid === this.selectedPerUuid);
            if (!d) return;
            if (this.perifericos.find(p => p.uuid === d.uuid)) return;
            this.perifericos.push({
                uuid:   d.uuid ?? d.id ?? '',
                nombre: d.name ?? d.nombre ?? '',
                tipo:   d.device_type ? d.device_type.name : '',
                serie:  d.serial_number ?? d.serie ?? '',
            });
            this.selectedPerUuid = '';
        },

        removePeriferico(i) {
            this.perifericos.splice(i, 1);
        },

        // ---- submit ----
        async enviar() {
            if (!this.device || !this.nombreUsuarioPc.trim() || !this.contrasenaEquipo.trim()) return;

            this.enviando = true;
            this.errorMsg = null;

            const payload = {
                user_id:           this.userId,
                assign_new:        this.device.assign_new,
                uuid_activos:      this.device.uuid,
                nombre_equipo:     this.device.nombre,
                modelo:            this.device.modelo,
                numero_serie:      this.device.serie,
                photo_id:          this.device.photo_id,
                nombre_usuario_pc: this.nombreUsuarioPc.trim(),
                contrasena_equipo: this.contrasenaEquipo,
                notas:             this.notas.trim() || null,
                correos:           this.correos
                    .filter(c => c.correo.trim())
                    .map(c => ({ correo: c.correo.trim(), contrasena_correo: c.contrasena || null })),
                perifericos:       this.perifericos.map(p => ({
                    uuid:   p.uuid,
                    nombre: p.nombre,
                    tipo:   p.tipo || null,
                    serie:  p.serie || null,
                })),
            };

            try {
                const resp = await fetch('{{ route("admin.credenciales.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify(payload),
                });

                const data = await resp.json();

                if (data.success) {
                    window.location.href = data.redirect ?? window.location.pathname;
                } else {
                    this.errorMsg = data.message ?? 'Error al guardar el registro.';
                    this.enviando = false;
                }
            } catch (e) {
                this.errorMsg = 'Error de conexión al guardar. Inténtalo de nuevo.';
                this.enviando = false;
            }
        },
    };
}
</script>
@endpush
