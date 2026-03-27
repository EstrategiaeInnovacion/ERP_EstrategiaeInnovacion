@extends('layouts.master')

@section('title', 'Detalle - Contraseñas y Equipos')

@section('content')
<div class="min-h-screen bg-slate-50 pb-12">

    {{-- Header --}}
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
                <span class="text-slate-600 text-sm font-medium">Registro #{{ $credencial->id }}</span>
            </div>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Detalle del Registro</h1>
                    <p class="text-slate-500 mt-1">Equipo asignado, credenciales, correos y periféricos.</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.credenciales.index') }}"
                       class="inline-flex items-center px-4 py-2 bg-slate-100 text-slate-700 font-medium text-sm rounded-xl hover:bg-slate-200 transition">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        Volver
                    </a>
                    <button type="button" onclick="document.getElementById('edit-modal').classList.remove('hidden')"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white font-medium text-sm rounded-xl hover:bg-indigo-700 transition">
                        <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Editar
                    </button>
                    <form method="POST" action="{{ route('admin.credenciales.destroy', $credencial) }}"
                          onsubmit="return confirm('¿Eliminar este registro? Esta acción no se puede deshacer.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2 bg-white border border-red-200 text-red-600 font-medium text-sm rounded-xl hover:bg-red-50 transition">
                            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- ---- Card: Usuario ---- --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                <h2 class="text-sm font-bold text-slate-600 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    USUARIO ASIGNADO
                </h2>
            </div>
            <div class="px-6 py-5 flex items-center gap-4">
                <div class="w-12 h-12 rounded-full bg-indigo-100 text-indigo-700 font-bold text-lg flex items-center justify-center shrink-0">
                    {{ strtoupper(mb_substr($credencial->user->name ?? '?', 0, 1)) }}
                </div>
                <div>
                    <p class="text-lg font-bold text-slate-900">{{ $credencial->user->name ?? 'Usuario eliminado' }}</p>
                    <p class="text-sm text-slate-500">{{ $credencial->user->email ?? '—' }}</p>
                </div>
            </div>
        </div>

        {{-- ---- Card: Equipo ---- --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                <h2 class="text-sm font-bold text-slate-600 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    INFORMACIÓN DEL EQUIPO
                </h2>
            </div>
            <div class="px-6 py-5">
                <div class="flex items-start gap-5">
                    {{-- Device photo --}}
                    @if($credencial->photo_id)
                    <div class="w-24 h-24 rounded-xl overflow-hidden border border-slate-200 bg-slate-50 shrink-0">
                        <img src="{{ route('admin.activos.photo', $credencial->photo_id) }}"
                             alt="Foto del equipo"
                             class="w-full h-full object-cover">
                    </div>
                    @else
                    <div class="w-24 h-24 rounded-xl border border-slate-200 bg-slate-50 shrink-0 flex items-center justify-center">
                        <svg class="w-10 h-10 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                  d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    @endif

                    <div class="flex-1 min-w-0">
                        <p class="text-xl font-bold text-slate-900">{{ $credencial->nombre_equipo }}</p>
                        <p class="text-sm text-slate-500 mt-0.5">{{ $credencial->modelo ?? 'Sin modelo' }}</p>
                        <p class="text-xs text-slate-400 font-mono mt-1">
                            S/N: {{ $credencial->numero_serie ?? 'N/A' }}
                        </p>
                        <p class="text-xs text-slate-400 mt-1 truncate">
                            UUID Activos: <span class="font-mono">{{ $credencial->uuid_activos }}</span>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        {{-- ---- Card: Credenciales PC ---- --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60">
                <h2 class="text-sm font-bold text-slate-600 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    CREDENCIALES DEL EQUIPO
                </h2>
            </div>
            <div class="px-6 py-5">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Usuario de PC</p>
                        <p class="text-sm font-mono font-semibold text-slate-800 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5">
                            {{ $credencial->nombre_usuario_pc }}
                        </p>
                    </div>
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Contraseña del Equipo</p>
                        <div class="flex items-center gap-2">
                            <div class="flex-1 text-sm font-mono font-semibold text-slate-800 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 tracking-[0.25em] overflow-hidden">
                                <span id="pass-equipo">••••••••••</span>
                            </div>
                            <button type="button"
                                    onclick="togglePass('pass-equipo', this)"
                                    data-secret="{{ $credencial->contrasena_descifrada }}"
                                    class="p-2 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 border border-slate-200 bg-white transition shrink-0"
                                    title="Mostrar / ocultar contraseña">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                @if($credencial->notas)
                <div class="mt-5">
                    <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1.5">Notas</p>
                    <p class="text-sm text-slate-700 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2.5 whitespace-pre-wrap">{{ $credencial->notas }}</p>
                </div>
                @endif
            </div>
        </div>

        {{-- ---- Card: Correos ---- --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-600 flex items-center gap-2">
                    <svg class="w-4 h-4 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    CORREOS DEL EQUIPO
                </h2>
                <span class="text-xs bg-sky-100 text-sky-700 font-bold rounded-full px-2.5 py-1">
                    {{ $credencial->correos->count() }}
                </span>
            </div>
            <div class="px-6 py-5">
                @if($credencial->correos->isEmpty())
                    <p class="text-sm text-slate-400 italic">Sin correos registrados.</p>
                @else
                <div class="space-y-3">
                    @foreach($credencial->correos as $correo)
                    <div class="flex items-center gap-3 bg-sky-50 border border-sky-100 rounded-xl px-4 py-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-800 truncate">{{ $correo->correo }}</p>
                        </div>
                        @if($correo->contrasena_correo)
                        <div class="flex items-center gap-2 shrink-0">
                            <div class="text-sm font-mono text-slate-700 tracking-[0.2em]">
                                <span id="pass-correo-{{ $correo->id }}">•••••••</span>
                            </div>
                            <button type="button"
                                    onclick="togglePass('pass-correo-{{ $correo->id }}', this)"
                                    data-secret="{{ $correo->contrasena_descifrada }}"
                                    class="p-1.5 rounded-lg text-slate-400 hover:text-sky-600 hover:bg-sky-100 transition"
                                    title="Mostrar / ocultar contraseña">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                        @else
                        <span class="text-xs text-slate-400 shrink-0">Sin contraseña</span>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        {{-- ---- Card: Periféricos ---- --}}
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/60 flex items-center justify-between">
                <h2 class="text-sm font-bold text-slate-600 flex items-center gap-2">
                    <svg class="w-4 h-4 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    PERIFÉRICOS
                </h2>
                <span class="text-xs bg-violet-100 text-violet-700 font-bold rounded-full px-2.5 py-1">
                    {{ $credencial->perifericos->count() }}
                </span>
            </div>
            <div class="px-6 py-5">
                @if($credencial->perifericos->isEmpty())
                    <p class="text-sm text-slate-400 italic">Sin periféricos asignados.</p>
                @else
                <div class="space-y-2">
                    @foreach($credencial->perifericos as $per)
                    <div class="flex items-center gap-4 bg-violet-50 border border-violet-100 rounded-xl px-4 py-3">
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-semibold text-slate-800">{{ $per->nombre }}</p>
                            <div class="flex items-center gap-3 mt-0.5">
                                @if($per->tipo)
                                <span class="text-xs bg-violet-100 text-violet-700 rounded-full px-2 py-0.5 font-medium">
                                    {{ $per->tipo }}
                                </span>
                                @endif
                                @if($per->numero_serie)
                                <span class="text-xs text-slate-400 font-mono">S/N: {{ $per->numero_serie }}</span>
                                @endif
                            </div>
                        </div>
                        <span class="text-xs text-slate-400 font-mono truncate shrink-0 max-w-[140px]" title="{{ $per->uuid_activos }}">
                            {{ substr($per->uuid_activos, 0, 8) }}…
                        </span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>

        {{-- ---- Card: Equipos Secundarios / de Cliente ---- --}}
        <div class="bg-white rounded-2xl shadow-sm border border-amber-200 overflow-hidden"
             x-data="secEquipoForm()">
            <div class="px-6 py-4 border-b border-amber-100 bg-amber-50/60 flex items-center justify-between">
                <h2 class="text-sm font-bold text-amber-700 flex items-center gap-2">
                    <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    EQUIPOS SECUNDARIOS / DE CLIENTE
                </h2>
                <span class="text-xs bg-amber-100 text-amber-700 font-bold rounded-full px-2.5 py-1">
                    {{ $equiposSecundarios->count() }}
                </span>
            </div>
            <div class="px-6 py-5 space-y-4">

                {{-- Lista de secundarios existentes --}}
                @if($equiposSecundarios->isEmpty())
                    <p class="text-sm text-slate-400 italic">No hay equipos secundarios registrados.</p>
                @else
                <div class="space-y-3">
                    @foreach($equiposSecundarios as $sec)
                    <div class="bg-amber-50 border border-amber-100 rounded-xl px-4 py-3">
                        <div class="flex items-start justify-between gap-3">
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <p class="text-sm font-bold text-slate-900">{{ $sec->nombre_equipo }}</p>
                                    <span class="text-xs bg-amber-200 text-amber-800 font-semibold rounded-full px-2 py-0.5">Secundario</span>
                                </div>
                                <p class="text-xs text-slate-500 mt-0.5">{{ $sec->modelo ?? 'Sin modelo' }} {{ $sec->numero_serie ? '· S/N: '.$sec->numero_serie : '' }}</p>
                                @if($sec->notas)
                                    <p class="text-xs text-slate-600 mt-1 bg-white border border-amber-100 rounded-lg px-2 py-1 whitespace-pre-wrap">{{ $sec->notas }}</p>
                                @endif
                                <div class="flex items-center gap-3 mt-1.5">
                                    @if($sec->correos->isNotEmpty())
                                        <span class="text-xs text-sky-600 font-medium">{{ $sec->correos->count() }} correo(s)</span>
                                    @endif
                                    @if($sec->perifericos->isNotEmpty())
                                        <span class="text-xs text-violet-600 font-medium">{{ $sec->perifericos->count() }} periférico(s)</span>
                                    @endif
                                    <span class="text-xs text-slate-400 font-mono">{{ $sec->created_at->format('d/m/Y') }}</span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2 shrink-0">
                                <a href="{{ route('admin.credenciales.show', $sec) }}"
                                   title="Ver detalle"
                                   class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600 hover:bg-indigo-100 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                    </svg>
                                </a>
                                <form method="POST"
                                      action="{{ route('admin.credenciales.secundarios.destroy', [$credencial, $sec]) }}"
                                      onsubmit="return confirm('¿Eliminar este equipo secundario?')">
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
                        </div>
                    </div>
                    @endforeach
                </div>
                @endif

                {{-- Botón para abrir formulario --}}
                <button type="button" @click="abrirForm()"
                        x-show="!showFormSecundario"
                        class="inline-flex items-center gap-2 px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white text-sm font-bold rounded-xl transition shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    Agregar Equipo Secundario
                </button>

                {{-- Formulario colapsable --}}
                <div x-show="showFormSecundario" x-cloak
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 -translate-y-2"
                     x-transition:enter-end="opacity-100 translate-y-0"
                     class="border border-amber-200 rounded-2xl p-5 bg-amber-50/40 space-y-4">

                    <div class="flex items-center justify-between">
                        <p class="text-xs font-bold text-amber-700 uppercase tracking-wider">Nuevo Equipo Secundario / de Cliente</p>
                        <button type="button" @click="cerrarForm()"
                                class="p-1 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>

                    {{-- Step: loading --}}
                    <div x-show="step === 'loading'" class="flex items-center gap-3 text-slate-500 text-sm py-2">
                        <svg class="w-5 h-5 animate-spin text-amber-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"/>
                        </svg>
                        Consultando sistema de activos…
                    </div>

                    {{-- Step: pick_device (solo computadoras) --}}
                    <div x-show="step === 'pick_device'">
                        <p class="text-sm font-semibold text-slate-700 mb-3">
                            Selecciona el equipo disponible:
                            <span class="text-xs font-normal text-slate-400 ml-1" x-text="'(' + disponibles.filter(d => d.type === 'computer').length + ' computadora(s))'"></span>
                        </p>

                        {{-- Device list: solo computadoras --}}
                        <div class="space-y-2 max-h-64 overflow-y-auto pr-1">
                            <template x-for="d in disponibles.filter(d => d.type === 'computer')" :key="d.uuid">
                                <button type="button"
                                        @click="seleccionarEquipo(d)"
                                        class="w-full text-left px-4 py-3 rounded-xl border border-slate-200 hover:border-amber-400 hover:bg-amber-50 transition flex items-center gap-4 group">
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
                                        <p class="text-xs text-slate-500 truncate" x-text="(d.brand ?? '') + (d.model ? ' · ' + d.model : '')"></p>
                                        <p class="text-xs text-slate-400 font-mono" x-text="'S/N: ' + (d.serial_number ?? 'N/A')"></p>
                                    </div>
                                    <svg class="w-5 h-5 text-slate-300 group-hover:text-amber-500 transition shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                    </svg>
                                </button>
                            </template>
                            <div x-show="!disponibles.filter(d => d.type === 'computer').length"
                                 class="text-sm text-slate-400 italic text-center py-6">
                                No hay computadoras disponibles en el sistema de activos.
                            </div>
                        </div>
                    </div>

                    {{-- Step: credentials --}}
                    <div x-show="step === 'credentials'" class="space-y-4">

                        {{-- Device card selected --}}
                        <template x-if="device">
                            <div class="bg-white rounded-xl border border-amber-200 p-4 flex items-start gap-4">
                                <div class="w-14 h-14 bg-slate-100 rounded-lg border border-slate-200 overflow-hidden shrink-0 flex items-center justify-center">
                                    <template x-if="device.photo_id">
                                        <img :src="`{{ url('admin/activos-api/fotos') }}/${device.photo_id}`"
                                             class="w-full h-full object-cover">
                                    </template>
                                    <template x-if="!device.photo_id">
                                        <svg class="w-7 h-7 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                                  d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                                        </svg>
                                    </template>
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="font-semibold text-slate-800" x-text="device.nombre"></p>
                                    <p class="text-sm text-slate-500" x-text="device.modelo || '—'"></p>
                                    <p class="text-xs text-slate-400 font-mono" x-text="'S/N: ' + (device.serie || 'N/A')"></p>
                                </div>
                                <button type="button" @click="step = 'pick_device'; device = null"
                                        title="Cambiar equipo"
                                        class="p-1.5 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-lg transition shrink-0">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                                    </svg>
                                </button>
                            </div>
                        </template>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-slate-600">Usuario de PC <span class="text-red-500">*</span></label>
                                <input type="text" x-model="nombreUsuarioPc"
                                       placeholder="Ej. jperez"
                                       class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
                            </div>
                            <div class="space-y-1">
                                <label class="text-xs font-semibold text-slate-600">Contraseña del equipo <span class="text-red-500">*</span></label>
                                <div class="relative">
                                    <input :type="showCont ? 'text' : 'password'" x-model="contrasenaEquipo"
                                           placeholder="••••••••"
                                           class="w-full border border-slate-200 rounded-xl px-3 py-2 pr-9 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white">
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
                            <div class="space-y-1 sm:col-span-2">
                                <label class="text-xs font-semibold text-slate-600">Notas</label>
                                <textarea x-model="notas" rows="2"
                                          placeholder="Información adicional (nombre del cliente, propósito del equipo…)"
                                          class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white resize-none"></textarea>
                                <p class="text-xs text-amber-600">Se añadirá automáticamente la etiqueta <strong>[Equipo Secundario / Cliente]</strong>.</p>
                            </div>
                        </div>

                        {{-- Periféricos adicionales --}}
                        <div class="border-t border-amber-100 pt-4">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="text-xs font-bold text-slate-600 flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                              d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                                    </svg>
                                    OTROS (Periféricos)
                                </h4>
                            </div>
                            <div class="flex gap-2 mb-2">
                                <select x-model="selectedPerUuid"
                                        class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-amber-400 bg-white min-w-0">
                                    <option value="">— Seleccionar periférico disponible —</option>
                                    <template x-for="d in disponibles.filter(d => d.type === 'peripheral' && !perifericos.find(p => p.uuid === d.uuid))" :key="d.uuid">
                                        <option :value="d.uuid"
                                                x-text="(d.name ?? d.nombre ?? 'Sin nombre') + (d.serial_number ? ' — S/N: ' + d.serial_number : '')"></option>
                                    </template>
                                </select>
                                <button type="button" @click="addPeriferico()"
                                        :disabled="!selectedPerUuid"
                                        class="px-3 py-2 bg-violet-600 text-white rounded-lg text-sm font-medium hover:bg-violet-700 disabled:opacity-50 disabled:cursor-not-allowed transition shrink-0">
                                    Añadir
                                </button>
                            </div>
                            <div class="space-y-1.5">
                                <template x-for="(p, i) in perifericos" :key="p.uuid">
                                    <div class="flex items-center gap-3 bg-violet-50 rounded-lg px-3 py-2 text-sm">
                                        <div class="flex-1 min-w-0">
                                            <span class="font-medium text-slate-800" x-text="p.nombre"></span>
                                            <span x-show="p.tipo" class="ml-2 text-xs text-violet-600 bg-violet-100 rounded-full px-2 py-0.5" x-text="p.tipo"></span>
                                            <span x-show="p.serie" class="ml-1 text-xs text-slate-400 font-mono" x-text="'S/N: ' + p.serie"></span>
                                        </div>
                                        <button type="button" @click="perifericos.splice(i, 1)"
                                                class="p-1 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-md transition shrink-0">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                            </svg>
                                        </button>
                                    </div>
                                </template>
                                <p x-show="!perifericos.length" class="text-xs text-slate-400 italic">Sin periféricos añadidos.</p>
                            </div>
                        </div>

                        <label class="flex items-center gap-2 text-sm text-slate-600 cursor-pointer select-none">
                            <input type="checkbox" x-model="assignNew"
                                   class="rounded border-slate-300 text-amber-600 focus:ring-amber-400">
                            Marcar como asignado en Sistema de Activos
                        </label>

                        <template x-if="errorMsg">
                            <p class="text-sm text-red-600 font-medium" x-text="errorMsg"></p>
                        </template>

                        <div class="flex items-center justify-end gap-3 pt-1">
                            <button type="button" @click="cerrarForm()"
                                    class="px-4 py-2 text-sm text-slate-600 hover:text-slate-800 font-medium rounded-xl hover:bg-slate-100 transition">
                                Cancelar
                            </button>
                            <button type="button" @click="guardar()" :disabled="guardando || !nombreUsuarioPc.trim() || !contrasenaEquipo.trim()"
                                    class="inline-flex items-center gap-2 px-5 py-2 bg-amber-600 hover:bg-amber-700 disabled:opacity-60 text-white text-sm font-bold rounded-xl transition shadow-sm">
                                <template x-if="guardando">
                                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                                    </svg>
                                </template>
                                <span x-text="guardando ? 'Guardando...' : 'Guardar Equipo Secundario'"></span>
                            </button>
                        </div>
                    </div>
                    {{-- end step credentials --}}

                </div>
                {{-- end form --}}

            </div>
        </div>

        {{-- Metadata --}}
        <p class="text-xs text-slate-400 text-center pb-4">            Registrado el {{ $credencial->created_at->format('d/m/Y \a \l\a\s H:i') }}
            @if($credencial->updated_at->ne($credencial->created_at))
                · Última modificación {{ $credencial->updated_at->diffForHumans() }}
            @endif
        </p>

    </div>
</div>

{{-- ══════════════ EDIT MODAL ══════════════ --}}
<div id="edit-modal"
     class="hidden fixed inset-0 z-50 bg-black/50 flex items-start justify-center p-4 sm:p-6 overflow-y-auto"
     x-data="editForm()"
     x-init="cargarDisponibles()">

    <div class="bg-white rounded-2xl shadow-xl w-full max-w-2xl my-6"
         @click.stop>

        {{-- Modal header --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <div>
                <h2 class="text-lg font-bold text-slate-900">Editar Equipo</h2>
                <p class="text-sm text-slate-500">Modifica los datos del equipo, credenciales, correos y periféricos.</p>
            </div>
            <button type="button" onclick="cerrarEditModal()"
                    class="p-2 text-slate-400 hover:text-slate-600 hover:bg-slate-100 rounded-xl transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        {{-- Modal body --}}
        <div class="px-6 py-5 space-y-6 max-h-[calc(100vh-12rem)] overflow-y-auto">

            {{-- Información del equipo --}}
            <div class="space-y-4">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Información del equipo
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1 sm:col-span-2">
                        <label class="text-xs font-semibold text-slate-600">Nombre del equipo <span class="text-red-500">*</span></label>
                        <input type="text" x-model="nombreEquipo" placeholder="Ej. Dell Latitude E7450"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white">
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-600">Modelo</label>
                        <input type="text" x-model="modelo" placeholder="Ej. Latitude E7450"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white">
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-600">Número de serie</label>
                        <input type="text" x-model="numeroSerie" placeholder="Ej. 5CG0381XYZ"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white">
                    </div>
                </div>
            </div>

            {{-- Credenciales --}}
            <div class="space-y-4 border-t border-slate-100 pt-4">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    Credenciales del equipo
                </h3>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-600">Usuario de PC <span class="text-red-500">*</span></label>
                        <input type="text" x-model="nombreUsuarioPc" placeholder="Ej. jperez"
                               class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white">
                    </div>
                    <div class="space-y-1">
                        <label class="text-xs font-semibold text-slate-600">Nueva contraseña <span class="text-slate-400 font-normal">(dejar vacío para no cambiar)</span></label>
                        <div class="relative">
                            <input :type="showCont ? 'text' : 'password'" x-model="contrasenaEquipo"
                                   placeholder="••••••••"
                                   class="w-full border border-slate-200 rounded-xl px-3 py-2 pr-9 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white">
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
                    <div class="space-y-1 sm:col-span-2">
                        <label class="text-xs font-semibold text-slate-600">Notas</label>
                        <textarea x-model="notas" rows="2" placeholder="Información adicional…"
                                  class="w-full border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-indigo-400 bg-white resize-none"></textarea>
                    </div>
                </div>
            </div>

            {{-- Correos --}}
            <div class="space-y-3 border-t border-slate-100 pt-4">
                <div class="flex items-center justify-between">
                    <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-2">
                        <svg class="w-3.5 h-3.5 text-sky-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        Correos
                    </h3>
                    <button type="button" @click="addCorreo()"
                            class="inline-flex items-center gap-1 px-3 py-1.5 bg-sky-50 text-sky-700 text-xs font-semibold rounded-lg hover:bg-sky-100 transition border border-sky-200">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                        </svg>
                        Agregar
                    </button>
                </div>
                <template x-for="(c, i) in correos" :key="i">
                    <div class="flex items-center gap-2">
                        <input type="email" x-model="c.correo" placeholder="correo@ejemplo.com"
                               class="flex-1 border border-slate-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white min-w-0">
                        <div class="relative">
                            <input :type="c.showPass ? 'text' : 'password'" x-model="c.contrasena_correo"
                                   :placeholder="c.id ? 'Nueva contraseña' : 'Contraseña'"
                                   class="w-36 border border-slate-200 rounded-xl px-3 py-2 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-sky-400 bg-white">
                            <button type="button" @click="c.showPass = !c.showPass"
                                    class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600">
                                <svg x-show="!c.showPass" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0zM2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg x-show="c.showPass" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 4.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                        <button type="button" @click="removeCorreo(i)"
                                class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition shrink-0">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                </template>
                <p x-show="!correos.length" class="text-xs text-slate-400 italic">Sin correos. Usa "Agregar" para añadir uno.</p>
            </div>

            {{-- Periféricos --}}
            <div class="space-y-3 border-t border-slate-100 pt-4">
                <h3 class="text-xs font-bold text-slate-500 uppercase tracking-wider flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    Periféricos
                </h3>

                {{-- Existing and newly added peripherals --}}
                <div class="space-y-1.5">
                    <template x-for="(p, i) in perifericos" :key="p.uuid + (p.id ?? ('new-' + i))">
                        <div class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm"
                             :class="p.id ? 'bg-violet-50 border border-violet-100' : 'bg-green-50 border border-green-100'">
                            <div class="flex-1 min-w-0">
                                <span class="font-medium text-slate-800" x-text="p.nombre"></span>
                                <span x-show="p.id" class="ml-2 text-xs text-violet-500 font-medium">existente</span>
                                <span x-show="!p.id" class="ml-2 text-xs text-green-600 font-medium">nuevo</span>
                                <span x-show="p.tipo" class="ml-2 text-xs text-slate-500 bg-slate-100 rounded-full px-2 py-0.5" x-text="p.tipo"></span>
                                <span x-show="p.serie" class="ml-1 text-xs text-slate-400 font-mono" x-text="'S/N: ' + p.serie"></span>
                            </div>
                            <button type="button" @click="removePeriferico(i)"
                                    class="p-1 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-md transition shrink-0"
                                    :title="p.id ? 'Quitar (libera en Sistema de Activos)' : 'Quitar'">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </template>
                    <p x-show="!perifericos.length" class="text-xs text-slate-400 italic">Sin periféricos.</p>
                </div>

                {{-- Add new peripheral from available list --}}
                <div class="flex gap-2 pt-1">
                    <select x-model="selectedPerUuid"
                            class="flex-1 border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-violet-400 bg-white min-w-0">
                        <option value="">— Añadir periférico disponible —</option>
                        <template x-for="d in disponibles.filter(d => d.type !== 'computer' && !perifericos.find(p => p.uuid === d.uuid))" :key="d.uuid">
                            <option :value="d.uuid"
                                    x-text="(d.name ?? d.nombre ?? 'Sin nombre') + (d.serial_number ? ' — S/N: ' + d.serial_number : '')"></option>
                        </template>
                    </select>
                    <button type="button" @click="addPeriferico()"
                            :disabled="!selectedPerUuid"
                            class="px-3 py-2 bg-violet-600 text-white rounded-lg text-sm font-medium hover:bg-violet-700 disabled:opacity-50 disabled:cursor-not-allowed transition shrink-0">
                        Añadir
                    </button>
                </div>
                <p class="text-xs text-slate-400">Los periféricos marcados como <span class="text-red-500 font-medium">eliminados</span> serán liberados en el Sistema de Activos. Los nuevos se marcarán como asignados.</p>
            </div>

        </div>

        {{-- Modal footer --}}
        <div class="px-6 py-4 border-t border-slate-200 flex items-center justify-between gap-3">
            <template x-if="errorMsg">
                <p class="text-sm text-red-600 font-medium flex-1" x-text="errorMsg"></p>
            </template>
            <div class="flex items-center gap-3 ml-auto">
                <button type="button" onclick="cerrarEditModal()"
                        class="px-4 py-2 text-sm text-slate-600 hover:text-slate-800 font-medium rounded-xl hover:bg-slate-100 transition">
                    Cancelar
                </button>
                <button type="button" @click="guardar()"
                        :disabled="guardando || !nombreEquipo.trim() || !nombreUsuarioPc.trim()"
                        class="inline-flex items-center gap-2 px-5 py-2 bg-indigo-600 hover:bg-indigo-700 disabled:opacity-60 text-white text-sm font-bold rounded-xl transition shadow-sm">
                    <template x-if="guardando">
                        <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z"></path>
                        </svg>
                    </template>
                    <span x-text="guardando ? 'Guardando...' : 'Guardar cambios'"></span>
                </button>
            </div>
        </div>
    </div>
</div>
{{-- ══ end edit modal ══ --}}

@push('scripts')
<script>
const revealed = {};

function togglePass(spanId, btn) {
    const span = document.getElementById(spanId);
    const secret = btn.dataset.secret;
    revealed[spanId] = !revealed[spanId];

    if (revealed[spanId]) {
        span.textContent = secret;
        span.classList.remove('tracking-[0.2em]', 'tracking-[0.25em]');
    } else {
        span.textContent = '••••••••••';
        span.classList.add('tracking-[0.25em]');
    }
}

// ─── Edit Modal ────────────────────────────────────────────────────────────
const editModal = document.getElementById('edit-modal');

function cerrarEditModal() {
    editModal.classList.add('hidden');
}

function editForm() {
    return {
        // Main fields
        nombreEquipo:    @json($credencial->nombre_equipo),
        modelo:          @json($credencial->modelo ?? ''),
        numeroSerie:     @json($credencial->numero_serie ?? ''),
        nombreUsuarioPc: @json($credencial->nombre_usuario_pc),
        contrasenaEquipo: '',
        showCont: false,
        notas:           @json($credencial->notas ?? ''),

        // Correos: id=null for new rows
        correos: @json($credencial->correos->map(fn($c) => ['id' => $c->id, 'correo' => $c->correo, 'contrasena_correo' => ''])),

        // Periféricos: id=null for new rows
        perifericos: @json($credencial->perifericos->map(fn($p) => ['id' => $p->id, 'uuid' => $p->uuid_activos, 'nombre' => $p->nombre, 'tipo' => $p->tipo ?? '', 'serie' => $p->numero_serie ?? ''])),

        // Available peripherals loaded from Activos
        disponibles: [],
        disponiblesLoaded: false,
        selectedPerUuid: '',

        guardando: false,
        errorMsg: '',

        async cargarDisponibles() {
            if (this.disponiblesLoaded) return;
            try {
                const resp = await fetch('{{ url("admin/activos-api/equipos-disponibles") }}', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (resp.ok) this.disponibles = await resp.json();
            } catch (e) {
                this.disponibles = [];
            }
            this.disponiblesLoaded = true;
        },

        addCorreo() {
            this.correos.push({ id: null, correo: '', contrasena_correo: '', showPass: false });
        },

        removeCorreo(i) {
            this.correos.splice(i, 1);
        },

        addPeriferico() {
            if (!this.selectedPerUuid) return;
            const d = this.disponibles.find(x => x.uuid === this.selectedPerUuid);
            if (!d || this.perifericos.find(p => p.uuid === d.uuid)) return;
            this.perifericos.push({
                id:     null,
                uuid:   d.uuid ?? '',
                nombre: d.name ?? d.nombre ?? '',
                tipo:   d.device_type ? d.device_type.name : (d.type_label ?? d.type ?? ''),
                serie:  d.serial_number ?? d.serie ?? '',
            });
            this.selectedPerUuid = '';
        },

        removePeriferico(i) {
            this.perifericos.splice(i, 1);
        },

        async guardar() {
            if (!this.nombreEquipo.trim() || !this.nombreUsuarioPc.trim()) return;
            this.guardando = true;
            this.errorMsg  = '';
            try {
                const resp = await fetch('{{ route('admin.credenciales.update', $credencial) }}', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':        'application/json',
                        'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        nombre_equipo:     this.nombreEquipo.trim(),
                        modelo:            this.modelo.trim() || null,
                        numero_serie:      this.numeroSerie.trim() || null,
                        nombre_usuario_pc: this.nombreUsuarioPc.trim(),
                        contrasena_equipo: this.contrasenaEquipo || null,
                        notas:             this.notas.trim() || null,
                        correos:           this.correos
                            .filter(c => c.correo.trim())
                            .map(c => ({
                                id:                c.id,
                                correo:            c.correo.trim(),
                                contrasena_correo: c.contrasena_correo || null,
                            })),
                        perifericos: this.perifericos.map(p => ({
                            id:     p.id,
                            uuid:   p.uuid,
                            nombre: p.nombre,
                            tipo:   p.tipo || null,
                            serie:  p.serie || null,
                        })),
                    }),
                });
                const data = await resp.json();
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    this.errorMsg = data.message || 'Error al guardar.';
                }
            } catch (e) {
                this.errorMsg = 'Error de conexión.';
            } finally {
                this.guardando = false;
            }
        },
    };
}
// ───────────────────────────────────────────────────────────────────────────

function secEquipoForm() {
    return {
        showFormSecundario: false,
        step: 'pick_device', // 'loading' | 'pick_device' | 'credentials'
        disponibles: [],
        device: null,
        nombreUsuarioPc: '',
        contrasenaEquipo: '',
        showCont: false,
        notas: '',
        assignNew: false,
        perifericos: [],
        selectedPerUuid: '',
        guardando: false,
        errorMsg: '',

        async abrirForm() {
            this.showFormSecundario = true;
            this.step = 'loading';
            this.device = null;
            this.errorMsg = '';
            try {
                const resp = await fetch('{{ url("admin/activos-api/equipos-disponibles") }}', {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                if (!resp.ok) throw new Error('HTTP ' + resp.status);
                this.disponibles = await resp.json();
            } catch (e) {
                this.disponibles = [];
                this.errorMsg = 'No se pudieron cargar los equipos disponibles.';
            }
            this.step = 'pick_device';
        },

        cerrarForm() {
            this.showFormSecundario = false;
            this.step = 'pick_device';
            this.device = null;
            this.nombreUsuarioPc = '';
            this.contrasenaEquipo = '';
            this.notas = '';
            this.assignNew = false;
            this.perifericos = [];
            this.selectedPerUuid = '';
            this.errorMsg = '';
            this.guardando = false;
        },

        addPeriferico() {
            if (!this.selectedPerUuid) return;
            const d = this.disponibles.find(x => x.uuid === this.selectedPerUuid);
            if (!d || this.perifericos.find(p => p.uuid === d.uuid)) return;
            this.perifericos.push({
                uuid:   d.uuid ?? d.id ?? '',
                nombre: d.name ?? d.nombre ?? '',
                tipo:   d.device_type ? d.device_type.name : (d.type_label ?? ''),
                serie:  d.serial_number ?? d.serie ?? '',
            });
            this.selectedPerUuid = '';
        },

        seleccionarEquipo(d) {
            const dev = d.device ?? d;
            this.device = {
                uuid:     dev.uuid ?? dev.id ?? '',
                nombre:   dev.name ?? dev.nombre ?? '',
                modelo:   dev.model ?? dev.modelo ?? '',
                serie:    dev.serial_number ?? dev.serie ?? '',
                photo_id: (dev.photos && dev.photos.length) ? dev.photos[0].id : null,
            };
            this.step = 'credentials';
        },

        async guardar() {
            if (!this.device || !this.nombreUsuarioPc.trim() || !this.contrasenaEquipo.trim()) return;
            this.guardando = true;
            this.errorMsg = '';
            try {
                const resp = await fetch('{{ route('admin.credenciales.secundarios.store', $credencial) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        uuid_activos:      this.device.uuid,
                        nombre_equipo:     this.device.nombre,
                        modelo:            this.device.modelo,
                        numero_serie:      this.device.serie,
                        photo_id:          this.device.photo_id,
                        nombre_usuario_pc: this.nombreUsuarioPc.trim(),
                        contrasena_equipo: this.contrasenaEquipo,
                        notas:             this.notas.trim() || null,
                        assign_new:        this.assignNew,
                        perifericos:       this.perifericos.map(p => ({
                            uuid:   p.uuid,
                            nombre: p.nombre,
                            tipo:   p.tipo || null,
                            serie:  p.serie || null,
                        })),
                    })
                });
                const data = await resp.json();
                if (data.success) {
                    window.location.href = data.redirect;
                } else {
                    this.errorMsg = data.message || 'Error al guardar.';
                }
            } catch (e) {
                this.errorMsg = 'Error de conexión.';
            } finally {
                this.guardando = false;
            }
        },
    };
}
</script>
@endpush
@endsection