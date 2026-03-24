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

        {{-- Metadata --}}
        <p class="text-xs text-slate-400 text-center pb-4">
            Registrado el {{ $credencial->created_at->format('d/m/Y \a \l\a\s H:i') }}
            @if($credencial->updated_at->ne($credencial->created_at))
                · Última modificación {{ $credencial->updated_at->diffForHumans() }}
            @endif
        </p>

    </div>
</div>

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
</script>
@endpush
@endsection

            <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Detalle del Registro</h1>
                    <p class="text-slate-500 mt-1">Información completa del equipo y credenciales.</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.credenciales.edit', $credencial) }}"
                       class="inline-flex items-center px-4 py-2.5 bg-amber-500 text-white font-bold text-sm rounded-xl hover:bg-amber-600 transition shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Editar
                    </a>

                    <form method="POST" action="{{ route('admin.credenciales.destroy', $credencial) }}"
                          onsubmit="return confirm('¿Confirmas eliminar este registro?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center px-4 py-2.5 bg-white border border-red-200 text-red-600 font-bold text-sm rounded-xl hover:bg-red-50 transition">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- Tarjeta Usuario --}}
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-8 py-5 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-base font-bold text-slate-700 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    Información del Usuario
                </h2>
            </div>
            <div class="p-8">
                <div class="flex items-center gap-4 mb-6">
                    <div class="h-14 w-14 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold text-xl">
                        {{ strtoupper(mb_substr($credencial->user->name ?? '?', 0, 1)) }}
                    </div>
                    <div>
                        <p class="text-xl font-bold text-slate-900">{{ $credencial->user->name ?? 'Usuario eliminado' }}</p>
                        <p class="text-sm text-slate-500">{{ $credencial->user->email ?? '—' }}</p>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Login de sistema</p>
                        <p class="text-sm font-mono font-semibold text-slate-800 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2">
                            {{ $credencial->nombre_usuario_sistema }}
                        </p>
                    </div>

                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Contraseña</p>
                        <div class="flex items-center gap-2">
                            <div class="flex-1 text-sm font-mono font-semibold text-slate-800 bg-slate-50 border border-slate-200 rounded-lg px-3 py-2 tracking-[0.25em]">
                                <span id="password-display">••••••••••••</span>
                            </div>
                            <button type="button" onclick="toggleShowPassword()"
                                    id="btn-reveal"
                                    data-password="{{ $credencial->contrasena_descifrada }}"
                                    class="p-2 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition border border-slate-200 bg-white"
                                    title="Mostrar / ocultar contraseña">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tarjeta Equipo --}}
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-8 py-5 border-b border-slate-100 bg-slate-50/50">
                <h2 class="text-base font-bold text-slate-700 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                    Información del Equipo
                </h2>
            </div>
            <div class="p-8">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="sm:col-span-2">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Equipo asignado</p>
                        <p class="text-lg font-bold text-slate-900">{{ $credencial->equipo_asignado }}</p>
                    </div>

                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Tipo</p>
                        @php
                            $tipoBadge = match($credencial->tipo_equipo) {
                                'Laptop'   => 'bg-blue-100 text-blue-700',
                                'Desktop'  => 'bg-purple-100 text-purple-700',
                                'Tablet'   => 'bg-emerald-100 text-emerald-700',
                                'Servidor' => 'bg-red-100 text-red-700',
                                default    => 'bg-slate-100 text-slate-600',
                            };
                        @endphp
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-bold {{ $tipoBadge }}">
                            {{ $credencial->tipo_equipo }}
                        </span>
                    </div>

                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Número de serie</p>
                        <p class="text-sm font-mono text-slate-700">{{ $credencial->numero_serie ?? '—' }}</p>
                    </div>

                    @if($credencial->sistema_operativo)
                    <div>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Sistema operativo</p>
                        <p class="text-sm text-slate-700">{{ $credencial->sistema_operativo }}</p>
                    </div>
                    @endif

                    @if($credencial->observaciones)
                    <div class="sm:col-span-2">
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-1">Observaciones</p>
                        <p class="text-sm text-slate-600 bg-slate-50 rounded-xl px-4 py-3 border border-slate-200">
                            {{ $credencial->observaciones }}
                        </p>
                    </div>
                    @endif

                    <div class="sm:col-span-2 pt-2 border-t border-slate-100">
                        <p class="text-xs text-slate-400">
                            Registrado el {{ $credencial->created_at->format('d/m/Y \a \l\a\s H:i') }}
                            @if($credencial->updated_at->ne($credencial->created_at))
                                · Última modificación {{ $credencial->updated_at->diffForHumans() }}
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

@push('scripts')
<script>
let revealed = false;

function toggleShowPassword() {
    const display = document.getElementById('password-display');
    const btn = document.getElementById('btn-reveal');
    const real = btn.dataset.password;

    revealed = !revealed;
    display.textContent = revealed ? real : '••••••••••••';
    display.classList.toggle('tracking-[0.25em]', !revealed);
}
</script>
@endpush
@endsection
