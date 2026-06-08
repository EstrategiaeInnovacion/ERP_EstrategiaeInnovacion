@extends('layouts.master')

@section('title', 'Expediente — ' . ($expediente->equipoAsignado?->nombre_equipo ?? 'Equipo'))

@section('content')
<div class="min-h-screen bg-slate-50 pb-16">

    {{-- Header --}}
    <div class="bg-white border-b border-slate-200 mb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.expedientes.index') }}"
                       class="p-2 rounded-lg hover:bg-slate-100 text-slate-500 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold text-slate-900">
                            Expediente: {{ $expediente->equipoAsignado?->nombre_equipo ?? 'Equipo sin nombre' }}
                        </h1>
                        <p class="text-slate-500 text-sm">
                            {{ $expediente->equipoAsignado?->modelo ?? '' }}
                            @if($expediente->equipoAsignado?->numero_serie) &nbsp;·&nbsp; S/N {{ $expediente->equipoAsignado->numero_serie }} @endif
                            &nbsp;·&nbsp; Abierto el {{ $expediente->fecha_apertura->format('d/m/Y') }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <span class="inline-flex px-3 py-1 rounded-full text-sm font-semibold {{ $expediente->estado_badge }}">
                        {{ $expediente->estado_label }}
                    </span>
                    @if($expediente->estaActivo())
                    <a href="{{ route('admin.expedientes.mantenimiento.create', $expediente) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-xl hover:bg-blue-700 transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        Nuevo mantenimiento
                    </a>
                    @endif
                    <form method="POST" action="{{ route('admin.expedientes.destroy', $expediente) }}"
                          onsubmit="return confirm('¿Eliminar este expediente y todos sus mantenimientos? Esta acción no se puede deshacer.');">
                        @csrf
                        @method('DELETE')
                        <button type="submit"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white text-sm font-semibold rounded-xl hover:bg-red-700 transition shadow-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                            </svg>
                            Eliminar expediente
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-xl p-4 flex items-center gap-3">
            <svg class="w-5 h-5 text-green-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-sm text-green-800 font-medium">{{ session('success') }}</span>
        </div>
        @endif

        {{-- Ficha del equipo + acciones de cierre --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Datos del equipo --}}
            <div class="lg:col-span-2 bg-white border border-slate-200 rounded-xl shadow-sm p-6 space-y-5">
                <h2 class="text-base font-semibold text-slate-800">Información del Equipo</h2>
                @php $ea = $expediente->equipoAsignado; $usuario = $ea?->user; @endphp
                <dl class="grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-3 text-sm">
                    <div>
                        <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Nombre del equipo</dt>
                        <dd class="text-slate-800 font-medium mt-0.5">{{ $ea->nombre_equipo ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Modelo</dt>
                        <dd class="text-slate-800 mt-0.5">{{ $ea->modelo ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Número de serie</dt>
                        <dd class="text-slate-800 mt-0.5 font-mono text-xs">{{ $ea->numero_serie ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Usuario Windows</dt>
                        <dd class="text-slate-800 mt-0.5 font-mono text-xs">{{ $ea->nombre_usuario_pc ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Usuario asignado</dt>
                        <dd class="text-slate-800 mt-0.5">{{ $usuario?->name ?? 'Sin asignar' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Área</dt>
                        <dd class="text-slate-800 mt-0.5">{{ $usuario?->empleado?->area ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Último mantenimiento</dt>
                        <dd class="text-slate-800 mt-0.5">{{ $ea?->last_maintenance_at ? $ea->last_maintenance_at->format('d/m/Y') : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Próximo mantenimiento</dt>
                        <dd class="mt-0.5">
                            @if($ea?->next_maintenance_at)
                                @php $vence = $ea->next_maintenance_at->isPast(); @endphp
                                <span class="{{ $vence ? 'text-red-600 font-semibold' : 'text-slate-800' }}">
                                    {{ $ea->next_maintenance_at->format('d/m/Y') }}
                                    @if($vence) <span class="text-xs font-normal">(vencido)</span> @endif
                                </span>
                            @else
                                <span class="text-slate-400">—</span>
                            @endif
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wide">Abierto por</dt>
                        <dd class="text-slate-800 mt-0.5">{{ $expediente->creador?->name ?? '—' }}</dd>
                    </div>
                </dl>

                @if($ea?->notas)
                <div class="border-t border-slate-100 pt-3">
                    <dt class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-1">Notas</dt>
                    <dd class="text-sm text-slate-700">{{ $ea->notas }}</dd>
                </div>
                @endif

                @if($expediente->motivo_cierre)
                <div class="border-t border-slate-100 pt-3 bg-red-50 rounded-lg p-3">
                    <p class="text-xs font-semibold text-red-500 uppercase tracking-wide mb-1">Motivo de cierre</p>
                    <p class="text-sm text-red-800">{{ $expediente->motivo_cierre }}</p>
                </div>
                @endif
            </div>

            {{-- Acciones --}}
            <div class="space-y-4">

                @if($ticketsPendientes->isNotEmpty())
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4">
                    <p class="text-xs font-semibold text-amber-700 uppercase tracking-wide mb-2">Tickets pendientes</p>
                    <ul class="space-y-1.5">
                        @foreach($ticketsPendientes as $tkt)
                        <li class="flex items-center justify-between gap-2 text-sm">
                            <span class="font-medium text-amber-900">{{ $tkt->folio }}</span>
                            <span class="text-xs text-amber-600 capitalize">{{ $tkt->estado }}</span>
                        </li>
                        @endforeach
                    </ul>
                </div>
                @endif

                {{-- Cerrar expediente --}}
                @if($expediente->estaActivo())
                <div class="bg-white border border-slate-200 rounded-xl p-4">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Cerrar expediente</p>
                    <form action="{{ route('admin.expedientes.cerrar', $expediente) }}" method="POST"
                          x-data="{ open: false }" @submit.prevent="if(confirm('¿Cerrar este expediente?')) $el.submit()">
                        @csrf
                        <div class="space-y-3">
                            <div>
                                <label class="text-xs font-semibold text-slate-600 block mb-1">Motivo</label>
                                <select name="estado" required
                                        class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="retirado">Equipo retirado / dado de baja</option>
                                    <option value="renovado">Equipo renovado / reemplazado</option>
                                </select>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-slate-600 block mb-1">Descripción</label>
                                <input type="text" name="motivo_cierre" required maxlength="500"
                                       placeholder="Describe el motivo…"
                                       class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <button type="submit"
                                    class="w-full px-3 py-2 bg-red-600 text-white text-sm font-semibold rounded-lg hover:bg-red-700 transition">
                                Cerrar expediente
                            </button>
                        </div>
                    </form>
                </div>
                @else
                <div class="bg-white border border-slate-200 rounded-xl p-4">
                    <p class="text-xs font-semibold text-slate-500 uppercase tracking-wide mb-3">Reactivar expediente</p>
                    <form action="{{ route('admin.expedientes.reactivar', $expediente) }}" method="POST"
                          onsubmit="return confirm('¿Reactivar este expediente?')">
                        @csrf
                        <button type="submit"
                                class="w-full px-3 py-2 bg-green-600 text-white text-sm font-semibold rounded-lg hover:bg-green-700 transition">
                            Reactivar
                        </button>
                    </form>
                </div>
                @endif

            </div>
        </div>

        {{-- Historial de mantenimientos --}}
        <div>
            <h2 class="text-base font-semibold text-slate-800 mb-3">
                Historial de mantenimientos
                <span class="ml-2 text-xs font-normal text-slate-500">({{ $expediente->mantenimientos->count() }} registros)</span>
            </h2>

            @if($expediente->mantenimientos->isEmpty())
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm py-12 text-center">
                <svg class="w-10 h-10 text-slate-300 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="text-slate-500 text-sm">No hay mantenimientos registrados.</p>
                @if($expediente->estaActivo())
                <a href="{{ route('admin.expedientes.mantenimiento.create', $expediente) }}"
                   class="inline-flex items-center gap-1.5 mt-3 px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-xl hover:bg-blue-700 transition">
                    Registrar primer mantenimiento
                </a>
                @endif
            </div>
            @else

            <div class="relative">
                {{-- Timeline line --}}
                <div class="absolute left-5 top-0 bottom-0 w-px bg-slate-200" style="margin-left: 1px;"></div>

                <div class="space-y-4">
                    @foreach($expediente->mantenimientos as $mnt)
                    <div class="relative pl-12">
                        {{-- Dot --}}
                        <div class="absolute left-0 top-4 w-5 h-5 rounded-full border-2 flex items-center justify-center
                            @if($mnt->estado === 'completado') bg-green-500 border-green-500
                            @elseif($mnt->estado === 'en_proceso') bg-yellow-400 border-yellow-400
                            @elseif($mnt->estado === 'cancelado') bg-red-300 border-red-300
                            @else bg-slate-300 border-slate-300 @endif">
                        </div>

                        <div class="bg-white border border-slate-200 rounded-xl shadow-sm hover:shadow-md transition overflow-hidden">
                            <div class="flex flex-wrap items-center justify-between gap-3 px-5 py-3 border-b border-slate-100">
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-mono text-sm font-bold text-slate-700">{{ $mnt->folio }}</span>
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold {{ $mnt->tipo_badge }}">
                                        {{ $mnt->tipo_label }}
                                    </span>
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold {{ $mnt->estado_badge }}">
                                        {{ $mnt->estado_label }}
                                    </span>
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-semibold {{ $mnt->prioridad_badge }}">
                                        Prioridad: {{ ucfirst($mnt->prioridad) }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <a href="{{ route('admin.expedientes.mantenimiento.show', [$expediente, $mnt]) }}"
                                       class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition">
                                        Ver detalle
                                    </a>
                                    @if($mnt->estado !== 'cancelado')
                                    <a href="{{ route('admin.expedientes.mantenimiento.edit', [$expediente, $mnt]) }}"
                                       class="text-xs font-semibold text-slate-500 hover:text-slate-700 transition">
                                        Editar
                                    </a>
                                    @endif
                                    <form action="{{ route('admin.expedientes.mantenimiento.destroy', [$expediente, $mnt]) }}"
                                          method="POST" class="inline"
                                          onsubmit="return confirm('¿Eliminar este mantenimiento?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="text-xs font-semibold text-red-400 hover:text-red-600 transition">
                                            Eliminar
                                        </button>
                                    </form>
                                </div>
                            </div>

                            <div class="px-5 py-3 grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
                                <div>
                                    <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Técnico</p>
                                    <p class="text-slate-700 mt-0.5">{{ $mnt->tecnico?->name ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Usuario al momento</p>
                                    <p class="text-slate-700 mt-0.5">{{ $mnt->usuario_al_momento ?? '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Inicio</p>
                                    <p class="text-slate-700 mt-0.5">{{ $mnt->fecha_inicio ? $mnt->fecha_inicio->format('d/m/Y H:i') : '—' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Fin / Duración</p>
                                    <p class="text-slate-700 mt-0.5">
                                        {{ $mnt->fecha_fin ? $mnt->fecha_fin->format('d/m/Y H:i') : '—' }}
                                        @if($mnt->duracion) <span class="text-xs text-slate-400">({{ $mnt->duracion }})</span> @endif
                                    </p>
                                </div>
                            </div>

                            @if($mnt->descripcion_problema)
                            <div class="px-5 pb-3">
                                <p class="text-xs text-slate-400 font-semibold uppercase tracking-wide">Descripción del problema</p>
                                <p class="text-sm text-slate-700 mt-0.5">{{ $mnt->descripcion_problema }}</p>
                            </div>
                            @endif

                            @if($mnt->archivos->isNotEmpty())
                            <div class="px-5 pb-3 flex flex-wrap gap-2">
                                @foreach($mnt->archivos as $archivo)
                                <a href="{{ Storage::url($archivo->ruta) }}" target="_blank"
                                   class="inline-flex items-center gap-1 px-2 py-1 bg-slate-100 text-slate-600 rounded text-xs hover:bg-slate-200 transition">
                                    @if($archivo->es_imagen)
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    @else
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                    @endif
                                    {{ $archivo->momento_label }}: {{ Str::limit($archivo->nombre_original ?? 'archivo', 20) }}
                                </a>
                                @endforeach
                            </div>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif
        </div>

    </div>
</div>
@endsection
