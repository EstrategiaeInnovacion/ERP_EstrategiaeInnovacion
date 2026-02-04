@extends('layouts.master')

@section('title', 'Detalles de ficha técnica - Panel Administrativo')

@section('content')
    @php
        $componentLabels = collect($profile->replacement_components ?? [])
            ->map(fn ($component) => $componentOptions[$component] ?? ucfirst(str_replace('_', ' ', $component)));

        $latestTicketComponents = collect(optional($latestTicket)->replacement_components ?? [])
            ->map(fn ($component) => $componentOptions[$component] ?? ucfirst(str_replace('_', ' ', $component)));

        $userImages = collect(optional($latestTicket)->imagenes ?? [])
            ->map(function ($image, $index) {
                if (is_array($image) && isset($image['data'])) {
                    $mime = $image['mime'] ?? 'image/jpeg';
                    return [
                        'src' => "data:{$mime};base64," . $image['data'],
                        'label' => $image['name'] ?? 'Imagen ' . ($index + 1),
                    ];
                }

                if (is_string($image) && str_starts_with($image, 'data:image')) {
                    return [
                        'src' => $image,
                        'label' => 'Imagen ' . ($index + 1),
                    ];
                }

                return null;
            })
            ->filter();

        $adminImages = collect(optional($latestTicket)->imagenes_admin ?? [])
            ->map(fn ($image, $index) => [
                'src' => str_starts_with($image, 'data:image') ? $image : 'data:image/jpeg;base64,' . $image,
                'label' => 'Imagen administrador ' . ($index + 1),
            ]);

        $imageCount = $userImages->count() + $adminImages->count();
        $lastMaintenanceAt = $profile->last_maintenance_at ? $profile->last_maintenance_at->copy()->timezone('America/Mexico_City') : null;
        $nextMaintenanceAt = $lastMaintenanceAt ? $lastMaintenanceAt->copy()->addMonths(4) : null;
        $lastUpdatedAt = $lastMaintenanceAt ?? optional($latestTicket)->updated_at ?? $profile->updated_at;
    @endphp

    <main class="max-w-6xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 mb-8">
            <div>
                <p class="text-xs font-semibold tracking-[0.2em] uppercase text-blue-500">Ficha técnica de mantenimiento</p>
                <h1 class="mt-2 text-3xl md:text-4xl font-bold text-slate-900">{{ $profile->identifier ?? 'Equipo sin identificador' }}</h1>
                <p class="mt-2 text-slate-600">
                    Información consolidada del equipo, los reportes del usuario y las intervenciones del equipo de TI.
                    @if($lastUpdatedAt)
                        <span class="block text-sm text-slate-500 mt-1">Última actualización {{ $lastUpdatedAt->timezone('America/Mexico_City')->format('d \d\e F Y \a \l\a\s H:i') }}</span>
                    @endif
                </p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('admin.maintenance.computers.index') }}"
                   class="inline-flex items-center px-4 py-2 rounded-lg border border-slate-300 text-slate-700 bg-white hover:bg-slate-50 transition-colors text-sm font-semibold shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Volver al historial
                </a>
                <a href="{{ route('admin.maintenance.computers.edit', $profile) }}"
                   class="inline-flex items-center px-4 py-2 rounded-lg bg-blue-600 text-white hover:bg-blue-700 transition-colors text-sm font-semibold shadow-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5h2m2 0h.01M7 5h.01M5 7h14M5 11h14M5 15h10" />
                    </svg>
                    Editar ficha
                </a>
                @if($profile->last_ticket_id)
                    <a href="{{ route('admin.tickets.show', $profile->last_ticket_id) }}"
                       class="inline-flex items-center px-4 py-2 rounded-lg bg-slate-900 text-white hover:bg-slate-800 transition-colors text-sm font-semibold shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8h2a2 2 0 002-2V6a2 2 0 00-2-2H7a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Ver ticket {{ optional($profile->ticket)->folio ?? '#' . $profile->last_ticket_id }}
                    </a>
                @endif
            </div>
        </div>

        <div class="bg-white border border-slate-200 rounded-3xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-blue-50 via-white to-white border-b border-slate-100 px-6 md:px-10 py-6 md:py-8 flex flex-col md:flex-row md:items-center md:justify-between gap-6">
                <div>
                    <h2 class="text-lg font-semibold text-slate-900">Resumen del equipo</h2>
                    <p class="text-sm text-slate-500">Datos principales para identificar el equipo y su estado actual.</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $profile->is_loaned ? 'bg-emerald-100 text-emerald-700' : 'bg-blue-100 text-blue-700' }}">
                        {{ $profile->is_loaned ? 'Prestado' : 'Disponible' }}
                    </span>
                    @if($latestTicket)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold {{ $latestTicket->estado_badge }}">
                            Ticket {{ $latestTicket->folio }} · {{ ucfirst(str_replace('_', ' ', $latestTicket->estado)) }}
                        </span>
                    @endif
                    @if($latestTicket && $latestTicket->maintenance_scheduled_at)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-700">
                            Mantenimiento {{ $latestTicket->maintenance_scheduled_at->timezone('America/Mexico_City')->format('d/m/Y H:i') }}
                        </span>
                    @endif
                    @if($nextMaintenanceAt)
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                            Próximo {{ $nextMaintenanceAt->format('d/m/Y H:i') }}
                        </span>
                    @endif
                </div>
            </div>

            <div class="p-6 md:p-10">
                <div class="grid gap-10 lg:grid-cols-3">
                    <section class="lg:col-span-2 space-y-10">
                        <div>
                            <h3 class="text-xs font-semibold tracking-[0.3em] uppercase text-slate-500 mb-4">Características técnicas</h3>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <p class="text-xs font-medium text-slate-500 uppercase">Identificador del equipo</p>
                                    <p class="text-lg font-semibold text-blue-600 mt-1">{{ $profile->identifier ?? 'Sin asignar' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-slate-500 uppercase">Marca y modelo</p>
                                    <p class="text-base text-slate-800 mt-1">{{ trim(($profile->brand ?? 'Marca no definida') . ' ' . ($profile->model ?? '')) }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-slate-500 uppercase">Tipo de disco</p>
                                    <p class="text-base text-slate-800 mt-1">{{ $profile->disk_type ?? 'Sin registro' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-slate-500 uppercase">Memoria RAM</p>
                                    <p class="text-base text-slate-800 mt-1">{{ $profile->ram_capacity ?? 'Sin registro' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-slate-500 uppercase">Estado de la batería</p>
                                    <p class="text-base text-slate-800 mt-1">{{ $profile->battery_status ? ucfirst(str_replace('_', ' ', $profile->battery_status)) : 'Sin registro' }}</p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-slate-500 uppercase">Último mantenimiento registrado</p>
                                    <p class="text-base text-slate-800 mt-1">
                                        @if($lastMaintenanceAt)
                                            {{ $lastMaintenanceAt->format('d/m/Y H:i') }}
                                        @else
                                            Sin registro
                                        @endif
                                    </p>
                                </div>
                                <div>
                                    <p class="text-xs font-medium text-slate-500 uppercase">Próximo mantenimiento estimado</p>
                                    <p class="text-base text-slate-800 mt-1">
                                        @if($nextMaintenanceAt)
                                            {{ $nextMaintenanceAt->format('d/m/Y H:i') }}
                                        @else
                                            En seguimiento
                                        @endif
                                    </p>
                                </div>
                            </div>

                            <div class="mt-6">
                                <p class="text-xs font-medium text-slate-500 uppercase">Observaciones estéticas</p>
                                <div class="mt-2 rounded-2xl border border-slate-200 bg-slate-50 p-4 text-sm leading-relaxed text-slate-700">
                                    {{ $profile->aesthetic_observations ?? 'Sin observaciones registradas.' }}
                                </div>
                            </div>

                            <div class="mt-6">
                                <p class="text-xs font-medium text-slate-500 uppercase">Componentes reemplazados</p>
                                @if($componentLabels->isNotEmpty())
                                    <div class="flex flex-wrap gap-2 mt-2">
                                        @foreach($componentLabels as $label)
                                            <span class="inline-flex items-center px-3 py-1 rounded-full bg-blue-50 text-blue-700 text-xs font-semibold border border-blue-200">
                                                {{ $label }}
                                            </span>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-sm text-slate-600 mt-2">No se registraron reemplazos en la ficha técnica.</p>
                                @endif
                            </div>
                        </div>

                        @if($latestTicket)
                            <div>
                                <h3 class="text-xs font-semibold tracking-[0.3em] uppercase text-slate-500 mb-4">Información capturada en el ticket</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                    <div class="space-y-4">
                                        <div>
                                            <p class="text-xs font-medium text-slate-500 uppercase">Solicitante</p>
                                            <p class="text-base font-semibold text-slate-900">{{ $latestTicket->nombre_solicitante }}</p>
                                            <p class="text-sm text-blue-600">{{ $latestTicket->correo_solicitante }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-medium text-slate-500 uppercase">Equipo o programa reportado</p>
                                            <p class="text-sm text-slate-700">{{ $latestTicket->nombre_programa ?? 'No especificado' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-medium text-slate-500 uppercase">Descripción del usuario</p>
                                            <div class="mt-2 rounded-2xl border border-slate-200 bg-white p-4 text-sm leading-relaxed text-slate-700 shadow-sm">
                                                {{ $latestTicket->descripcion_problema }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="space-y-4">
                                        <div>
                                            <p class="text-xs font-medium text-slate-500 uppercase">Programación del mantenimiento</p>
                                            <div class="mt-2 rounded-2xl border border-indigo-200 bg-indigo-50 p-4 text-sm text-indigo-800 shadow-sm space-y-1">
                                                <p><span class="font-semibold">Fecha:</span> {{ optional($latestTicket->maintenance_scheduled_at)->timezone('America/Mexico_City')->format('d/m/Y H:i') ?? 'Por asignar' }}</p>
                                                <p><span class="font-semibold">Horario:</span>
                                                    @if($latestTicket->maintenanceSlot)
                                                        {{ \Carbon\Carbon::parse($latestTicket->maintenanceSlot->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($latestTicket->maintenanceSlot->end_time)->format('H:i') }}
                                                    @else
                                                        No definido
                                                    @endif
                                                </p>
                                                <p><span class="font-semibold">Folio:</span> {{ $latestTicket->folio }}</p>
                                            </div>
                                        </div>
                                        <div>
                                            <p class="text-xs font-medium text-slate-500 uppercase">Detalles adicionales del usuario</p>
                                            <div class="mt-2 rounded-2xl border border-slate-200 bg-white p-4 text-sm leading-relaxed text-slate-700 shadow-sm">
                                                {{ $latestTicket->maintenance_details ?? 'Sin información adicional proporcionada.' }}
                                            </div>
                                        </div>
                                        <div>
                                            <p class="text-xs font-medium text-slate-500 uppercase">Componentes sugeridos por el usuario</p>
                                            @if($latestTicketComponents->isNotEmpty())
                                                <div class="flex flex-wrap gap-2 mt-2">
                                                    @foreach($latestTicketComponents as $label)
                                                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold border border-emerald-200">
                                                            {{ $label }}
                                                        </span>
                                                    @endforeach
                                                </div>
                                            @else
                                                <p class="text-sm text-slate-600 mt-2">Sin componentes sugeridos en este ticket.</p>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <div>
                                    <h3 class="text-xs font-semibold tracking-[0.3em] uppercase text-slate-500 mb-3">Reporte técnico del administrador</h3>
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm leading-relaxed text-slate-800 min-h-[160px]">
                                        {{ $latestTicket->maintenance_report ?? 'Aún no se registra un reporte técnico para este mantenimiento.' }}
                                    </div>
                                </div>
                                <div>
                                    <h3 class="text-xs font-semibold tracking-[0.3em] uppercase text-slate-500 mb-3">Observaciones y cierre</h3>
                                    <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 text-sm leading-relaxed text-slate-800 space-y-4 min-h-[160px]">
                                        <div>
                                            <p class="text-xs font-medium text-slate-500 uppercase">Observaciones al cierre</p>
                                            <p class="mt-2">{{ $latestTicket->closure_observations ?? 'Sin observaciones registradas al cierre.' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs font-medium text-slate-500 uppercase">Notas del administrador en el ticket</p>
                                            <p class="mt-2">{{ $latestTicket->observaciones ?? 'Sin notas adicionales.' }}</p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div>
                                <h3 class="text-xs font-semibold tracking-[0.3em] uppercase text-slate-500 mb-3">Archivos e imágenes del caso</h3>
                                @if($imageCount > 0)
                                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                                        @foreach($userImages as $image)
                                            <button type="button" onclick="openMaintenanceImageModal('{{ $image['src'] }}', '{{ $image['label'] }}')"
                                                    class="group relative aspect-video overflow-hidden rounded-2xl border border-slate-200 bg-slate-100 shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                <img src="{{ $image['src'] }}" alt="{{ $image['label'] }}" class="h-full w-full object-cover transition duration-200 group-hover:scale-105 group-hover:brightness-90">
                                                <span class="absolute bottom-0 inset-x-0 bg-slate-900/70 text-white text-xs font-medium px-3 py-2 text-left">{{ $image['label'] }}</span>
                                            </button>
                                        @endforeach
                                        @foreach($adminImages as $image)
                                            <button type="button" onclick="openMaintenanceImageModal('{{ $image['src'] }}', '{{ $image['label'] }}')"
                                                    class="group relative aspect-video overflow-hidden rounded-2xl border border-emerald-200 bg-emerald-50 shadow-sm focus:outline-none focus:ring-2 focus:ring-emerald-500">
                                                <img src="{{ $image['src'] }}" alt="{{ $image['label'] }}" class="h-full w-full object-cover transition duration-200 group-hover:scale-105 group-hover:brightness-95">
                                                <span class="absolute bottom-0 inset-x-0 bg-emerald-800/80 text-white text-xs font-medium px-3 py-2 text-left">{{ $image['label'] }}</span>
                                            </button>
                                        @endforeach
                                    </div>
                                @else
                                    <div class="rounded-2xl border border-dashed border-slate-300 bg-slate-50 p-6 text-center text-sm text-slate-500">
                                        No hay imágenes adjuntas para este mantenimiento.
                                    </div>
                                @endif
                            </div>
                        @endif
                    </section>

                    <aside class="space-y-6">
                        <div class="rounded-3xl border border-slate-200 bg-slate-50 p-6 shadow-inner">
                            <h3 class="text-sm font-semibold text-slate-900 mb-4">Situación del préstamo</h3>
                            <dl class="space-y-3 text-sm text-slate-700">
                                <div class="flex items-start justify-between gap-4">
                                    <dt class="text-slate-500">Estado</dt>
                                    <dd class="font-medium">{{ $profile->is_loaned ? 'Prestado' : 'Disponible' }}</dd>
                                </div>
                                @if($profile->is_loaned)
                                    <div>
                                        <dt class="text-slate-500">Responsable</dt>
                                        <dd class="font-medium">{{ $profile->loaned_to_name }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-slate-500">Correo</dt>
                                        <dd class="font-medium break-words">{{ $profile->loaned_to_email }}</dd>
                                    </div>
                                @endif
                                <div>
                                    <dt class="text-slate-500">Último ticket vinculado</dt>
                                    <dd class="font-medium">{{ optional($latestTicket)->folio ?? 'Sin asignar' }}</dd>
                                </div>
                                <div>
                                    <dt class="text-slate-500">Última intervención</dt>
                                    <dd class="font-medium">
                                        @if($lastMaintenanceAt)
                                            {{ $lastMaintenanceAt->format('d/m/Y H:i') }}
                                        @elseif($latestTicket)
                                            {{ $latestTicket->updated_at->timezone('America/Mexico_City')->format('d/m/Y H:i') }}
                                        @else
                                            Sin registro
                                        @endif
                                    </dd>
                                </div>
                            </dl>
                        </div>

                        @if($empleado)
                            <div class="rounded-3xl border border-blue-200 bg-blue-50 p-6 shadow-inner">
                                <h3 class="text-sm font-semibold text-blue-900 mb-4 flex items-center">
                                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                                    </svg>
                                    Empleado asociado
                                </h3>
                                <div class="space-y-3 text-sm">
                                    @if($empleado->foto_path)
                                        <div class="flex justify-center mb-4">
                                            <img src="{{ asset('storage/' . $empleado->foto_path) }}" 
                                                 alt="{{ $empleado->nombre }}"
                                                 class="w-20 h-20 rounded-full object-cover border-2 border-blue-300 shadow-md">
                                        </div>
                                    @endif
                                    <div>
                                        <dt class="text-blue-600 text-xs font-medium uppercase">Nombre completo</dt>
                                        <dd class="font-semibold text-blue-900 mt-1">{{ $empleado->nombre }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-blue-600 text-xs font-medium uppercase">ID Empleado</dt>
                                        <dd class="font-medium text-blue-900 mt-1">{{ $empleado->id_empleado }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-blue-600 text-xs font-medium uppercase">Área</dt>
                                        <dd class="font-medium text-blue-900 mt-1">{{ $empleado->area ?? 'Sin especificar' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-blue-600 text-xs font-medium uppercase">Posición</dt>
                                        <dd class="font-medium text-blue-900 mt-1">{{ $empleado->posicion ?? 'Sin especificar' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="text-blue-600 text-xs font-medium uppercase">Correo corporativo</dt>
                                        <dd class="font-medium text-blue-900 mt-1 break-words">{{ $empleado->correo }}</dd>
                                    </div>
                                    @if($empleado->telefono)
                                        <div>
                                            <dt class="text-blue-600 text-xs font-medium uppercase">Teléfono</dt>
                                            <dd class="font-medium text-blue-900 mt-1">{{ $empleado->telefono }}</dd>
                                        </div>
                                    @endif
                                    @if($empleado->supervisor)
                                        <div>
                                            <dt class="text-blue-600 text-xs font-medium uppercase">Supervisor</dt>
                                            <dd class="font-medium text-blue-900 mt-1">{{ $empleado->supervisor->nombre ?? 'Sin asignar' }}</dd>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
                            <h3 class="text-sm font-semibold text-slate-900 mb-4">Historial de mantenimientos</h3>
                            @if($historyTickets->isNotEmpty())
                                <ul class="space-y-4">
                                    @foreach($historyTickets as $ticket)
                                        <li class="relative pl-5">
                                            <span class="absolute left-0 top-1.5 h-2 w-2 rounded-full {{ $ticket->id === optional($latestTicket)->id ? 'bg-blue-500' : 'bg-slate-300' }}"></span>
                                            <p class="text-xs uppercase tracking-wide text-slate-400">{{ $ticket->created_at->timezone('America/Mexico_City')->format('d/m/Y H:i') }}</p>
                                            <p class="text-sm font-semibold text-slate-900">{{ $ticket->folio }}</p>
                                            <p class="text-xs text-slate-500 mt-1">{{ ucfirst(str_replace('_', ' ', $ticket->estado)) }} · {{ $ticket->maintenance_scheduled_at ? $ticket->maintenance_scheduled_at->timezone('America/Mexico_City')->format('d/m/Y H:i') : 'Sin programación' }}</p>
                                            <a href="{{ route('admin.tickets.show', $ticket) }}" class="mt-2 inline-flex items-center text-xs font-semibold text-blue-600 hover:text-blue-800">
                                                Ver detalles
                                                <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                </svg>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @else
                                <p class="text-sm text-slate-500">Aún no hay tickets de mantenimiento relacionados con este equipo.</p>
                            @endif
                        </div>

                        @if($latestTicket)
                            <div class="rounded-3xl border border-blue-200 bg-blue-50 p-6 shadow-inner">
                                <h3 class="text-sm font-semibold text-blue-900 mb-2">Actualizar seguimiento administrativo</h3>
                                <p class="text-xs text-blue-700 mb-4">Los cambios que realices aquí se guardan directamente en el ticket {{ $latestTicket->folio }}.</p>

                                <form method="POST" action="{{ route('admin.tickets.update', $latestTicket) }}" class="space-y-5" enctype="multipart/form-data">
                                    @csrf
                                    @method('PATCH')

                                    <input type="hidden" name="estado" value="{{ old('estado', $latestTicket->estado) }}">

                                    <div class="space-y-2">
                                        <label for="maintenance_observaciones" class="block text-xs font-medium text-blue-900">Observaciones del administrador</label>
                                        <textarea id="maintenance_observaciones"
                                                  name="observaciones"
                                                  rows="3"
                                                  class="w-full border border-blue-200 rounded-lg px-3 py-2 text-sm text-blue-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-blue-400"
                                                  placeholder="Registra notas importantes sobre este mantenimiento">{{ old('observaciones', $latestTicket->observaciones) }}</textarea>
                                        @error('observaciones')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                    </div>

                                    <div class="space-y-3">
                                        <div class="space-y-2">
                                            <label for="maintenanceAdminImages" class="block text-xs font-medium text-blue-900">Imágenes del administrador</label>
                                            <input type="file"
                                                   id="maintenanceAdminImages"
                                                   name="imagenes_admin[]"
                                                   multiple
                                                   accept="image/*"
                                                   class="block w-full text-sm text-blue-900 border border-blue-200 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-blue-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:bg-blue-100 file:text-blue-800 hover:file:bg-blue-200">
                                            <p class="text-xs text-blue-700">Puedes adjuntar evidencias visuales o capturas del trabajo realizado.</p>
                                            @error('imagenes_admin')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                            @error('imagenes_admin.*')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                        </div>

                                        <div id="maintenanceImagePreview" class="grid grid-cols-2 gap-3" style="display: none;"></div>

                                        <div id="maintenanceUploadStatus" class="hidden text-xs text-blue-800 bg-blue-100 border border-blue-200 rounded-lg px-3 py-2">
                                            <span id="maintenanceFileCount">0</span> archivo(s) seleccionado(s). Recuerda guardar los cambios para aplicar la actualización.
                                        </div>

                                        @if($latestTicket->imagenes_admin && count($latestTicket->imagenes_admin) > 0)
                                            <div class="bg-white border border-blue-200 rounded-lg p-3 space-y-2">
                                                <p class="text-xs font-semibold text-blue-900">Imágenes existentes ({{ count($latestTicket->imagenes_admin) }})</p>
                                                <div class="grid grid-cols-2 gap-2">
                                                    @foreach($latestTicket->imagenes_admin as $index => $imagen)
                                                        <div class="relative group rounded-lg overflow-hidden border border-blue-200">
                                                            <img src="data:image/jpeg;base64,{{ $imagen }}"
                                                                 alt="Imagen administrador {{ $index + 1 }}"
                                                                 class="h-24 w-full object-cover cursor-pointer transition duration-200 group-hover:scale-105"
                                                                 onclick="openMaintenanceImageModal('data:image/jpeg;base64,{{ $imagen }}', 'Imagen administrador {{ $index + 1 }}')">
                                                            <button type="button"
                                                                    onclick="removeExistingMaintenanceAdminImage(event, {{ $index }})"
                                                                    class="absolute top-2 right-2 bg-red-500 hover:bg-red-600 text-white rounded-full p-1 shadow-lg opacity-0 group-hover:opacity-100 transition">
                                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                                                </svg>
                                                            </button>
                                                            <span class="absolute bottom-1 left-1 bg-blue-900/80 text-white text-[10px] font-medium px-2 py-0.5 rounded">IMG {{ $index + 1 }}</span>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="grid grid-cols-1 gap-4">
                                        <div class="space-y-2">
                                            <label for="maintenance_report_form" class="block text-xs font-medium text-blue-900">Reporte técnico</label>
                                            <textarea id="maintenance_report_form"
                                                      name="maintenance_report"
                                                      rows="3"
                                                      class="w-full border border-blue-200 rounded-lg px-3 py-2 text-sm text-blue-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-blue-400"
                                                      placeholder="Describe el trabajo realizado, piezas reemplazadas u observaciones relevantes">{{ old('maintenance_report', $latestTicket->maintenance_report) }}</textarea>
                                            @error('maintenance_report')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                        </div>
                                        <div class="space-y-2">
                                            <label for="closure_observations_form" class="block text-xs font-medium text-blue-900">Observaciones al cerrar</label>
                                            <textarea id="closure_observations_form"
                                                      name="closure_observations"
                                                      rows="2"
                                                      class="w-full border border-blue-200 rounded-lg px-3 py-2 text-sm text-blue-900 bg-white focus:outline-none focus:ring-2 focus:ring-blue-400 focus:border-blue-400"
                                                      placeholder="Notas finales cuando se cierre el ticket">{{ old('closure_observations', $latestTicket->closure_observations) }}</textarea>
                                            @error('closure_observations')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                        </div>
                                    </div>

                                    <div class="pt-3 border-t border-blue-200">
                                        <button type="submit" class="w-full inline-flex items-center justify-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                            Guardar seguimiento
                                        </button>
                                    </div>
                                </form>
                            </div>
                        @endif
                    </aside>
                </div>
            </div>
        </div>
    </main>

    <div id="maintenanceImageModal" class="fixed inset-0 z-50 hidden bg-black/70 backdrop-blur-sm">
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="relative max-w-4xl w-full">
                <button type="button" onclick="closeMaintenanceImageModal()" class="absolute -top-10 right-0 text-white hover:text-slate-200 transition">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
                <div class="overflow-hidden rounded-3xl shadow-2xl border border-white/10 bg-slate-900">
                    <img id="maintenanceModalImage" src="" alt="" class="w-full max-h-[75vh] object-contain bg-black">
                    <div id="maintenanceModalCaption" class="px-6 py-4 text-sm text-slate-200 border-t border-white/10"></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function openMaintenanceImageModal(src, caption) {
            const modal = document.getElementById('maintenanceImageModal');
            const image = document.getElementById('maintenanceModalImage');
            const label = document.getElementById('maintenanceModalCaption');

            image.src = src;
            image.alt = caption;
            label.textContent = caption || '';

            modal.classList.remove('hidden');
            document.body.style.overflow = 'hidden';
        }

        function closeMaintenanceImageModal() {
            const modal = document.getElementById('maintenanceImageModal');
            const image = document.getElementById('maintenanceModalImage');
            const label = document.getElementById('maintenanceModalCaption');

            image.src = '';
            image.alt = '';
            label.textContent = '';

            modal.classList.add('hidden');
            document.body.style.overflow = '';
        }

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeMaintenanceImageModal();
            }
        });

        document.addEventListener('click', (event) => {
            const modal = document.getElementById('maintenanceImageModal');
            if (!modal.classList.contains('hidden') && event.target === modal) {
                closeMaintenanceImageModal();
            }
        });

        let maintenanceSelectedFiles = [];
        const maintenanceImagesInput = document.getElementById('maintenanceAdminImages');

        if (maintenanceImagesInput) {
            maintenanceImagesInput.addEventListener('change', (event) => {
                const files = Array.from(event.target.files);
                maintenanceSelectedFiles = [...maintenanceSelectedFiles, ...files];
                updateMaintenanceImagePreview();
            });
        }

        function updateMaintenanceImagePreview() {
            const previewContainer = document.getElementById('maintenanceImagePreview');
            const uploadStatus = document.getElementById('maintenanceUploadStatus');
            const fileCount = document.getElementById('maintenanceFileCount');

            if (!previewContainer || !uploadStatus || !fileCount) {
                return;
            }

            previewContainer.innerHTML = '';

            if (maintenanceSelectedFiles.length > 0) {
                previewContainer.style.display = 'grid';
                uploadStatus.classList.remove('hidden');
                fileCount.textContent = maintenanceSelectedFiles.length;

                maintenanceSelectedFiles.forEach((file, index) => {
                    if (file && file.type && file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function (e) {
                            const card = document.createElement('div');
                            card.className = 'relative group';
                            card.innerHTML = `
                                <img src="${e.target.result}" alt="Vista previa ${index + 1}" class="h-24 w-full object-cover rounded-lg border border-blue-200 cursor-pointer transition hover:border-blue-400" onclick="openMaintenanceImageModal('${e.target.result}', 'Vista previa ${index + 1}')">
                                <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition">
                                    <button type="button" onclick="removeMaintenancePreviewImage(${index})" class="bg-red-500 hover:bg-red-600 text-white rounded-full p-1.5 text-xs shadow-lg">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                        </svg>
                                    </button>
                                </div>
                                <span class="absolute bottom-1 left-1 bg-blue-900/80 text-white text-[10px] font-medium px-2 py-0.5 rounded">${Math.round(file.size / 1024)} KB</span>
                            `;
                            previewContainer.appendChild(card);
                        };
                        reader.readAsDataURL(file);
                    }
                });
            } else {
                previewContainer.style.display = 'none';
                uploadStatus.classList.add('hidden');
            }

            updateMaintenanceFileInput();
        }

        function updateMaintenanceFileInput() {
            if (!maintenanceImagesInput) {
                return;
            }

            const dt = new DataTransfer();

            maintenanceSelectedFiles.forEach(file => {
                if (file) {
                    dt.items.add(file);
                }
            });

            maintenanceImagesInput.files = dt.files;
        }

        function removeMaintenancePreviewImage(index) {
            maintenanceSelectedFiles.splice(index, 1);
            updateMaintenanceImagePreview();
        }

        let maintenanceRemovedAdminImages = [];

        function removeExistingMaintenanceAdminImage(event, index) {
            if (!event) {
                return;
            }

            if (!maintenanceRemovedAdminImages.includes(index)) {
                maintenanceRemovedAdminImages.push(index);
            }

            const trigger = event.currentTarget || event.target;
            const container = trigger.closest('.relative');
            if (container) {
                container.style.display = 'none';
            }

            const form = trigger.closest('form');
            if (form && !form.querySelector(`input[name="removed_admin_images[]"][value="${index}"]`)) {
                const hiddenInput = document.createElement('input');
                hiddenInput.type = 'hidden';
                hiddenInput.name = 'removed_admin_images[]';
                hiddenInput.value = index;
                form.appendChild(hiddenInput);
            }
        }
    </script>
@endpush
