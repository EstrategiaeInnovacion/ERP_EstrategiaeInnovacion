@extends('layouts.master')

@section('title', 'Configuración de Mantenimientos - Panel Administrativo')

@section('content')
    <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between mb-8">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Horarios de mantenimiento</h2>
                <p class="text-gray-600">Administra la agenda de mantenimientos y la documentación técnica de los equipos.</p>
            </div>
            <div class="flex flex-col sm:flex-row gap-3 sm:items-center">
                <a href="{{ route('admin.maintenance.computers.index') }}"
                    class="inline-flex items-center px-4 py-2 rounded-lg border border-green-300 bg-green-50 text-green-700 hover:bg-green-100 transition-colors">
                    Expedientes de equipos
                    <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>

                <form method="POST" action="{{ route('admin.maintenance.slots.destroy-past') }}" class="inline-flex"
                    onsubmit="return confirm('¿Eliminar todos los horarios pasados? Esta acción cancelará las reservaciones asociadas.');">
                    @csrf
                    @method('DELETE')
                    <button type="submit"
                        class="inline-flex items-center px-4 py-2 rounded-lg border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 text-sm font-medium transition-colors">
                        Eliminar horarios pasados
                    </button>
                </form>
            </div>
        </div>

        @if (session('success'))
            <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-8">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm text-green-800 font-medium">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        <div class="bg-white border border-blue-100 rounded-2xl shadow-sm overflow-hidden">
            <div class="bg-slate-50 border-b border-blue-100 flex flex-wrap items-center gap-2 px-4 sm:px-6 py-3">
                <button type="button" data-tab-target="tab-profiles"
                    class="tab-trigger inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-blue-700 bg-white shadow-sm">
                    <span class="hidden sm:inline">Ficha técnica</span>
                    <span class="sm:hidden">Ficha</span>
                </button>
                <button type="button" data-tab-target="tab-expedientes"
                    class="tab-trigger inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:text-blue-700 hover:bg-white/80">
                    <span class="hidden sm:inline">Expedientes</span>
                    <span class="sm:hidden">Expedientes</span>
                </button>
                <button type="button" data-tab-target="tab-bulk"
                    class="tab-trigger inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:text-blue-700 hover:bg-white/80">
                    Horarios en lote
                </button>
                <button type="button" data-tab-target="tab-individual"
                    class="tab-trigger inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:text-blue-700 hover:bg-white/80">
                    Horario individual
                </button>
                <button type="button" data-tab-target="tab-agenda"
                    class="tab-trigger inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:text-blue-700 hover:bg-white/80">
                    Agenda programada
                </button>
            </div>

            <div class="p-6 sm:p-8 space-y-10">
                <section id="tab-profiles" data-tab-panel class="space-y-8">
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold text-slate-900">Registrar ficha técnica de equipo</h3>
                        <p class="text-sm text-slate-500 max-w-2xl">Selecciona un ticket desde "Seguimiento administrativo de tickets" para completar los datos de la ficha técnica del equipo.</p>
                    </div>

                    <form method="POST" action="{{ route('admin.maintenance.computers.store') }}" class="space-y-6 hidden bg-white border border-gray-200 rounded-xl p-6 shadow-sm" id="technicalProfileForm">
                        @csrf

                        <!-- Sección: Información básica del equipo -->
                        <div class="border-b border-gray-200 pb-6">
                            <h3 class="text-base font-semibold text-gray-900 mb-4">Información básica del equipo</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                <div>
                                    <label for="identifier" class="block text-sm font-medium text-gray-700 mb-1.5">
                                        Identificador del equipo <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" id="identifier" name="identifier" value="{{ old('identifier') }}"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        required placeholder="Ej: LAPTOP001">
                                    @error('identifier')
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="brand" class="block text-sm font-medium text-gray-700 mb-1.5">Marca</label>
                                    <input type="text" id="brand" name="brand" value="{{ old('brand') }}"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Ej: Dell, HP, Lenovo">
                                    @error('brand')
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="model" class="block text-sm font-medium text-gray-700 mb-1.5">Modelo</label>
                                    <input type="text" id="model" name="model" value="{{ old('model') }}"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Ej: Latitude 5420">
                                    @error('model')
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Sección: Especificaciones técnicas -->
                        <div class="border-b border-gray-200 pb-6">
                            <h3 class="text-base font-semibold text-gray-900 mb-4">Especificaciones técnicas</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <div>
                                    <label for="disk_type" class="block text-sm font-medium text-gray-700 mb-1.5">Tipo de disco</label>
                                    <input type="text" id="disk_type" name="disk_type" value="{{ old('disk_type') }}"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Ej: SSD 256GB">
                                    @error('disk_type')
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="ram_capacity" class="block text-sm font-medium text-gray-700 mb-1.5">Capacidad de RAM</label>
                                    <input type="text" id="ram_capacity" name="ram_capacity" value="{{ old('ram_capacity') }}"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                        placeholder="Ej: 8GB DDR4">
                                    @error('ram_capacity')
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="battery_status" class="block text-sm font-medium text-gray-700 mb-1.5">Estado de batería</label>
                                    <select id="battery_status" name="battery_status"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Selecciona una opción</option>
                                        <option value="functional" {{ old('battery_status') === 'functional' ? 'selected' : '' }}>Funcional</option>
                                        <option value="partially_functional" {{ old('battery_status') === 'partially_functional' ? 'selected' : '' }}>Parcialmente funcional</option>
                                        <option value="damaged" {{ old('battery_status') === 'damaged' ? 'selected' : '' }}>Dañada</option>
                                    </select>
                                    @error('battery_status')
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Sección: Mantenimiento y observaciones -->
                        <div class="border-b border-gray-200 pb-6">
                            <h3 class="text-base font-semibold text-gray-900 mb-4">Mantenimiento y observaciones</h3>
                            
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label for="maintenance_ticket_id" class="block text-sm font-medium text-gray-700 mb-1.5">
                                        Ticket de mantenimiento relacionado
                                    </label>
                                    <select id="maintenance_ticket_id" name="maintenance_ticket_id"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                        <option value="">Selecciona un ticket</option>
                                        @foreach ($maintenanceTickets as $ticket)
                                            @php
                                                $createdAt = optional($ticket->created_at)->timezone('America/Mexico_City');
                                            @endphp
                                            <option value="{{ $ticket->id }}" 
                                                {{ (string) old('maintenance_ticket_id') === (string) $ticket->id ? 'selected' : '' }}
                                                data-equipment-identifier="{{ $ticket->equipment_identifier ?? '' }}"
                                                data-equipment-brand="{{ $ticket->equipment_brand ?? '' }}"
                                                data-equipment-model="{{ $ticket->equipment_model ?? '' }}"
                                                data-disk-type="{{ $ticket->disk_type ?? '' }}"
                                                data-ram-capacity="{{ $ticket->ram_capacity ?? '' }}"
                                                data-battery-status="{{ $ticket->battery_status ?? '' }}"
                                                data-aesthetic-observations="{{ $ticket->aesthetic_observations ?? '' }}"
                                                data-replacement-components="{{ $ticket->replacement_components ? json_encode($ticket->replacement_components) : '[]' }}">
                                                {{ $ticket->folio }} · {{ $ticket->nombre_solicitante }} · {{ $createdAt ? $createdAt->format('d/m/Y H:i') : 'Sin fecha' }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <p class="text-xs text-gray-500 mt-1.5">El ticket seleccionado se vinculará como el último mantenimiento realizado.</p>
                                    @error('maintenance_ticket_id')
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div>
                                    <label for="last_maintenance_at" class="block text-sm font-medium text-gray-700 mb-1.5">
                                        Último mantenimiento registrado
                                    </label>
                                    <input type="datetime-local" id="last_maintenance_at" name="last_maintenance_at"
                                        value="{{ old('last_maintenance_at') }}"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                                    @error('last_maintenance_at')
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div>
                                <label for="aesthetic_observations" class="block text-sm font-medium text-gray-700 mb-1.5">
                                    Observaciones estéticas
                                </label>
                                <textarea id="aesthetic_observations" name="aesthetic_observations" rows="3"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                    placeholder="Describe el estado físico del equipo, rayones, golpes, etc.">{{ old('aesthetic_observations') }}</textarea>
                                @error('aesthetic_observations')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <!-- Sección: Componentes reemplazados -->
                        <div class="border-b border-gray-200 pb-6">
                            <h3 class="text-base font-semibold text-gray-900 mb-3">Componentes reemplazados</h3>
                            <p class="text-sm text-gray-600 mb-4">Marca los componentes que fueron reemplazados durante el mantenimiento</p>
                            
                            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-3">
                                @foreach ($componentOptions as $value => $label)
                                    <label class="flex items-center text-sm text-gray-700 bg-gray-50 hover:bg-gray-100 border border-gray-200 rounded-lg px-3 py-2.5 cursor-pointer transition-colors">
                                        <input type="checkbox" name="replacement_components[]" value="{{ $value }}"
                                            class="mr-2 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                            {{ is_array(old('replacement_components')) && in_array($value, old('replacement_components', []), true) ? 'checked' : '' }}>
                                        <span class="text-xs">{{ $label }}</span>
                                    </label>
                                @endforeach
                            </div>
                            @error('replacement_components')
                                <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                            @enderror
                            @error('replacement_components.*')
                                <p class="text-xs text-red-600 mt-2">{{ $message }}</p>
                            @enderror
                        </div>

                        <!-- Sección: Préstamo de equipo -->
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-5">
                            <label class="flex items-start text-sm text-gray-900 font-medium cursor-pointer">
                                <input type="checkbox" name="is_loaned" value="1" id="is_loaned"
                                    class="mt-0.5 mr-3 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                    {{ old('is_loaned') ? 'checked' : '' }}>
                                <div>
                                    <span class="block mb-1">Marcar equipo como prestado actualmente</span>
                                    <span class="text-xs text-gray-600 font-normal">Selecciona a la persona responsable desde el directorio de usuarios.</span>
                                </div>
                            </label>

                            <div id="loanDetails" class="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4 {{ old('is_loaned') ? '' : 'hidden' }}">
                                <div>
                                    <label for="loaned_to_name" class="block text-sm font-medium text-gray-700 mb-1.5">
                                        Nombre de la persona
                                    </label>
                                    <input list="loanedNameOptions" type="text" id="loaned_to_name" name="loaned_to_name"
                                        value="{{ old('loaned_to_name') }}"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
                                        placeholder="Selecciona o escribe el nombre">
                                    <datalist id="loanedNameOptions">
                                        @foreach ($users as $user)
                                            <option value="{{ $user->name }}"></option>
                                        @endforeach
                                    </datalist>
                                    @error('loaned_to_name')
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div>
                                    <label for="loaned_to_email" class="block text-sm font-medium text-gray-700 mb-1.5">
                                        Correo electrónico
                                    </label>
                                    <input list="loanedEmailOptions" type="email" id="loaned_to_email" name="loaned_to_email"
                                        value="{{ old('loaned_to_email') }}"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 bg-white"
                                        placeholder="Selecciona o escribe el correo">
                                    <datalist id="loanedEmailOptions">
                                        @foreach ($users as $user)
                                            <option value="{{ $user->email }}"></option>
                                        @endforeach
                                    </datalist>
                                    @error('loaned_to_email')
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>

                        <!-- Botón de envío -->

                        <!-- Botón de envío -->
                        <div class="flex justify-end pt-4">
                            <button type="submit"
                                class="inline-flex items-center px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg shadow-sm transition-colors">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                </svg>
                                Registrar ficha técnica
                            </button>
                        </div>
                    </form>

                    <div class="border border-blue-100 rounded-2xl bg-white shadow-sm mt-6">
                        <div class="px-5 py-4 border-b border-blue-100 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                            <div>
                                <h4 class="text-lg font-semibold text-slate-900">Seguimiento administrativo de tickets</h4>
                                <p class="text-sm text-slate-500">Actualiza observaciones, reportes y evidencias directamente desde la ficha técnica.</p>
                            </div>
                            <div class="flex items-center gap-2 text-xs text-slate-500 bg-blue-50 border border-blue-100 rounded-lg px-3 py-1.5">
                                <svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                                Selecciona un ticket para mostrar el formulario.
                            </div>
                        </div>

                        <div class="p-6 space-y-6">
                            @php
                                $activeTicketId = old('target_ticket_id', session('active_ticket_form'));
                                // Filtrar solo tickets sin ficha técnica asociada y no cancelados por el usuario
                                $ticketsWithoutProfile = $maintenanceTickets->filter(function($ticket) {
                                    return is_null($ticket->computer_profile_id) && !$ticket->closed_by_user;
                                });
                            @endphp

                            @if($ticketsWithoutProfile->isEmpty())
                                <p class="text-sm text-slate-500">Todos los tickets de mantenimiento ya tienen ficha técnica registrada.</p>
                            @else
                                <div class="space-y-2">
                                    <label for="maintenanceTicketSelector" class="block text-sm font-medium text-slate-700">Ticket de mantenimiento</label>
                                    <select id="maintenanceTicketSelector" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500" data-default="{{ $activeTicketId ? 'ticket-' . $activeTicketId : '' }}">
                                        <option value="">Selecciona un ticket para gestionarlo</option>
                                        @foreach($ticketsWithoutProfile as $ticket)
                                            @php
                                                $createdAt = optional($ticket->created_at)->timezone('America/Mexico_City');
                                                $closedAt = optional($ticket->fecha_cierre)->timezone('America/Mexico_City');
                                                $label = $ticket->folio . ' · ' . $ticket->nombre_solicitante;
                                            @endphp
                                            <option value="ticket-{{ $ticket->id }}" {{ (string) $activeTicketId === (string) $ticket->id ? 'selected' : '' }}>
                                                {{ $label }} ({{ $createdAt ? $createdAt->format('d/m/Y H:i') : 'Sin fecha' }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="space-y-6" id="maintenanceTicketForms">
                                    @foreach($ticketsWithoutProfile as $ticket)
                                        @php
                                            $isActiveTicket = (string) $activeTicketId === (string) $ticket->id;
                                            $createdAt = optional($ticket->created_at)->timezone('America/Mexico_City');
                                            $closedAt = optional($ticket->fecha_cierre)->timezone('America/Mexico_City');
                                            $scheduledAt = optional($ticket->maintenance_scheduled_at)->timezone('America/Mexico_City');
                                            $observacionesValue = $isActiveTicket ? old('observaciones', $ticket->observaciones) : $ticket->observaciones;
                                            $maintenanceReportValue = $isActiveTicket ? old('maintenance_report', $ticket->maintenance_report) : $ticket->maintenance_report;
                                            $closureObservationsValue = $isActiveTicket ? old('closure_observations', $ticket->closure_observations) : $ticket->closure_observations;
                                            $removedImages = $isActiveTicket ? (array) old('removed_admin_images', []) : [];
                                        @endphp
                                        <div class="border border-slate-200 rounded-xl bg-slate-50/60 p-5 space-y-4 hidden" data-ticket-panel="ticket-{{ $ticket->id }}">
                                            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                                                <div>
                                                    <p class="text-xs font-semibold tracking-[0.3em] uppercase text-slate-500">Ticket de mantenimiento</p>
                                                    <h5 class="text-lg font-semibold text-slate-900">{{ $ticket->folio }}</h5>
                                                    <p class="text-xs text-slate-500 mt-1">
                                                        Creado {{ $createdAt ? $createdAt->format('d/m/Y H:i') : 'sin fecha' }} ·
                                                        Estado {{ ucfirst(str_replace('_', ' ', $ticket->estado)) }}
                                                        @if($closedAt)
                                                            · Cerrado {{ $closedAt->format('d/m/Y H:i') }}
                                                        @endif
                                                        @if($scheduledAt)
                                                            · Programado {{ $scheduledAt->format('d/m/Y H:i') }}
                                                        @endif
                                                    </p>
                                                </div>
                                                <a href="{{ route('admin.tickets.show', $ticket) }}" class="inline-flex items-center px-3 py-2 text-xs font-semibold text-blue-600 bg-white border border-blue-200 rounded-lg hover:bg-blue-50 transition">
                                                    Ver ticket completo
                                                    <svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </a>
                                            </div>

                                            <form method="POST" action="{{ route('admin.tickets.update', $ticket) }}" class="space-y-5" enctype="multipart/form-data">
                                                @csrf
                                                @method('PATCH')

                                                <input type="hidden" name="estado" value="{{ $isActiveTicket ? old('estado', $ticket->estado) : $ticket->estado }}">
                                                <input type="hidden" name="target_ticket_id" value="{{ $ticket->id }}">
                                                <input type="hidden" name="redirect_to" value="{{ request()->fullUrl() }}">

                                                <div class="space-y-2">
                                                    <label for="adminObservations{{ $ticket->id }}" class="block text-xs font-medium text-slate-700">Observaciones del administrador</label>
                                                    <textarea id="adminObservations{{ $ticket->id }}" name="observaciones" rows="3" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ $observacionesValue }}</textarea>
                                                    @if($isActiveTicket)
                                                        @error('observaciones')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                                    @endif
                                                </div>

                                                <div class="space-y-3">
                                                    <div class="space-y-2">
                                                        <label for="adminImages{{ $ticket->id }}" class="block text-xs font-medium text-slate-700">Imágenes del administrador</label>
                                                        <input type="file" id="adminImages{{ $ticket->id }}" name="imagenes_admin[]" multiple accept="image/*" class="block w-full text-sm border border-slate-300 rounded-lg px-3 py-2 bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-xs file:bg-blue-100 file:text-blue-800 hover:file:bg-blue-200" data-maintenance-upload>
                                                        <p class="text-xs text-slate-500" data-upload-status>0 archivos seleccionados.</p>
                                                        @if($isActiveTicket)
                                                            @error('imagenes_admin')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                                            @error('imagenes_admin.*')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                                        @endif
                                                    </div>

                                                    @if($ticket->imagenes_admin && count($ticket->imagenes_admin) > 0)
                                                        <div class="bg-white border border-slate-200 rounded-lg p-3 space-y-2">
                                                            <p class="text-xs font-semibold text-slate-700">Imágenes existentes</p>
                                                            <div class="grid grid-cols-2 md:grid-cols-3 gap-3">
                                                                @foreach($ticket->imagenes_admin as $index => $imagen)
                                                                    <label class="group relative cursor-pointer border border-slate-200 rounded-lg overflow-hidden">
                                                                        <img src="data:image/jpeg;base64,{{ $imagen }}" alt="Imagen administrador {{ $index + 1 }}" class="h-24 w-full object-cover">
                                                                        <span class="absolute bottom-1 left-1 bg-slate-900/80 text-white text-[10px] font-medium px-2 py-0.5 rounded">IMG {{ $index + 1 }}</span>
                                                                        <input type="checkbox" name="removed_admin_images[]" value="{{ $index }}" class="absolute top-2 right-2 h-4 w-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500" {{ in_array($index, $removedImages, true) ? 'checked' : '' }}>
                                                                    </label>
                                                                @endforeach
                                                            </div>
                                                            <p class="text-[11px] text-slate-500">Marca las imágenes que deseas eliminar antes de guardar.</p>
                                                        </div>
                                                    @endif
                                                </div>

                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div class="space-y-2">
                                                        <label for="maintenanceReport{{ $ticket->id }}" class="block text-xs font-medium text-slate-700">Reporte técnico</label>
                                                        <textarea id="maintenanceReport{{ $ticket->id }}" name="maintenance_report" rows="3" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ $maintenanceReportValue }}</textarea>
                                                        @if($isActiveTicket)
                                                            @error('maintenance_report')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                                        @endif
                                                    </div>
                                                    <div class="space-y-2">
                                                        <label for="closureObservations{{ $ticket->id }}" class="block text-xs font-medium text-slate-700">Observaciones al cerrar</label>
                                                        <textarea id="closureObservations{{ $ticket->id }}" name="closure_observations" rows="3" class="w-full border border-slate-300 rounded-lg px-3 py-2 text-sm bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500">{{ $closureObservationsValue }}</textarea>
                                                        @if($isActiveTicket)
                                                            @error('closure_observations')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                                        @endif
                                                    </div>
                                                </div>

                                                <div class="pt-3 border-t border-slate-200 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
                                                    <p class="text-[11px] text-slate-500">Los cambios se guardarán directamente en el ticket seleccionado.</p>
                                                    <button type="submit" class="inline-flex items-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-semibold rounded-lg transition">
                                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                        </svg>
                                                        Guardar seguimiento
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                </section>

                <section id="tab-expedientes" data-tab-panel class="space-y-6 hidden">
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold text-slate-900">Expedientes de Equipos</h3>
                        <p class="text-sm text-slate-500 max-w-2xl">Historial de equipos con mantenimiento registrado y su estado de préstamo.</p>
                    </div>

                    @if($profiles->isEmpty())
                        <div class="bg-gray-50 border border-gray-200 rounded-xl p-8 text-center">
                            <svg class="w-16 h-16 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            <p class="text-gray-600 font-medium">No hay equipos registrados</p>
                            <p class="text-sm text-gray-500 mt-1">Comienza creando una ficha técnica desde la pestaña "Ficha técnica"</p>
                        </div>
                    @else
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 mb-4">
                            <div class="flex items-start">
                                <svg class="w-5 h-5 text-blue-600 mt-0.5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-blue-900">Total de equipos registrados: {{ $profiles->count() }}</p>
                                    <p class="text-xs text-blue-700 mt-1">Haz clic en "Ver detalles" para consultar la ficha técnica completa, tickets asociados y empleado asignado</p>
                                </div>
                            </div>
                        </div>

                        <div class="overflow-x-auto rounded-xl border border-gray-200">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Identificador</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Equipo</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Último mantenimiento</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Estado</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white divide-y divide-gray-200">
                                    @foreach($profiles as $profile)
                                        <tr class="hover:bg-gray-50 transition-colors">
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <div class="text-sm font-semibold text-blue-600">{{ $profile->identifier ?? 'Sin asignar' }}</div>
                                            </td>
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-semibold text-gray-900">{{ $profile->brand ?? 'Marca no definida' }} {{ $profile->model }}</div>
                                                @if($profile->disk_type || $profile->ram_capacity)
                                                    <div class="text-xs text-gray-500 mt-1">
                                                        @if($profile->disk_type)
                                                            <span>Disco: {{ $profile->disk_type }}</span>
                                                        @endif
                                                        @if($profile->ram_capacity)
                                                            <span class="ml-2">RAM: {{ $profile->ram_capacity }}</span>
                                                        @endif
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                                @php
                                                    $lastMaintenance = $profile->last_maintenance_at
                                                        ? $profile->last_maintenance_at->copy()->timezone('America/Mexico_City')
                                                        : null;
                                                @endphp
                                                @if($lastMaintenance)
                                                    {{ $lastMaintenance->format('d/m/Y H:i') }}
                                                @else
                                                    <span class="text-gray-400">Sin registro</span>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-sm">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $profile->is_loaned ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-800' }}">
                                                    {{ $profile->is_loaned ? 'Prestado' : 'Disponible' }}
                                                </span>
                                                @if($profile->is_loaned && $profile->loaned_to_name)
                                                    <div class="text-xs text-gray-500 mt-1">{{ $profile->loaned_to_name }}</div>
                                                @endif
                                            </td>
                                            <td class="px-6 py-4 text-sm">
                                                <a href="{{ route('admin.maintenance.computers.show', $profile) }}" 
                                                   class="inline-flex items-center px-3 py-1.5 bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 rounded-lg text-xs font-semibold transition-colors">
                                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                                    </svg>
                                                    Ver detalles
                                                </a>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </section>

                <section id="tab-bulk" data-tab-panel class="space-y-8 hidden">
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold text-slate-900">Agregar horarios en lote</h3>
                        <p class="text-sm text-slate-500 max-w-2xl">Crea múltiples horarios seleccionando un rango de fechas, los días de la semana y la capacidad disponible. El sistema calcula automáticamente los bloques de tiempo.</p>
                    </div>

                    <form method="POST" action="{{ route('admin.maintenance.slots.store-bulk') }}" id="bulkScheduleForm" class="space-y-8">
                        @csrf
                        <div class="grid grid-cols-1 xl:grid-cols-2 gap-8">
                            <div class="space-y-6">
                                <div class="space-y-3">
                                    <h4 class="font-medium text-gray-800">Rango de fechas</h4>
                                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                        <div>
                                            <label for="start_date" class="block text-sm font-medium text-gray-700 mb-1">Fecha inicio <span class="text-red-500">*</span></label>
                                            <input type="date" id="start_date" name="start_date" value="{{ old('start_date') }}"
                                                min="{{ date('Y-m-d') }}"
                                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                required>
                                            @error('start_date')
                                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                        <div>
                                            <label for="end_date" class="block text-sm font-medium text-gray-700 mb-1">Fecha fin <span class="text-red-500">*</span></label>
                                            <input type="date" id="end_date" name="end_date" value="{{ old('end_date') }}"
                                                min="{{ date('Y-m-d') }}"
                                                class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                required>
                                            @error('end_date')
                                                <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <div class="space-y-3">
                                    <h4 class="font-medium text-gray-800">Días de aplicación</h4>
                                    <div class="grid grid-cols-2 sm:grid-cols-3 gap-2">
                                        @php
                                            $daysSelected = old('days', []);
                                            $daysOptions = [
                                                'monday' => 'Lunes',
                                                'tuesday' => 'Martes',
                                                'wednesday' => 'Miércoles',
                                                'thursday' => 'Jueves',
                                                'friday' => 'Viernes',
                                                'saturday' => 'Sábado',
                                                'sunday' => 'Domingo',
                                            ];
                                        @endphp
                                        @foreach ($daysOptions as $value => $label)
                                            <label class="flex items-center bg-slate-50 border border-gray-200 rounded-lg px-3 py-2 text-sm text-gray-700">
                                                <input type="checkbox" name="days[]" value="{{ $value }}"
                                                    class="rounded border-gray-300 text-blue-600 mr-2"
                                                    {{ in_array($value, $daysSelected, true) ? 'checked' : '' }}>
                                                {{ $label }}
                                            </label>
                                        @endforeach
                                    </div>
                                    @error('days')
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>

                            <div class="space-y-6">
                                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label for="bulk_start_time" class="block text-sm font-medium text-gray-700 mb-1">Hora inicio <span class="text-red-500">*</span></label>
                                        <input type="time" id="bulk_start_time" name="bulk_start_time"
                                            value="{{ old('bulk_start_time', '09:00') }}"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            required>
                                        @error('bulk_start_time')
                                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                    <div>
                                        <label for="bulk_end_time" class="block text-sm font-medium text-gray-700 mb-1">Hora fin <span class="text-red-500">*</span></label>
                                        <input type="time" id="bulk_end_time" name="bulk_end_time"
                                            value="{{ old('bulk_end_time', '13:00') }}"
                                            class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                            required>
                                        @error('bulk_end_time')
                                            <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                        @enderror
                                    </div>
                                </div>

                                <div>
                                    <label for="total_capacity" class="block text-sm font-medium text-gray-700 mb-1">Capacidad total <span class="text-red-500">*</span></label>
                                    <input type="number" min="1" max="20" id="total_capacity" name="total_capacity"
                                        value="{{ old('total_capacity', 4) }}"
                                        class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                        onchange="calculateSlots()" required>
                                    <p class="text-xs text-gray-500 mt-1">Número de horarios en los que se dividirá el rango seleccionado.</p>
                                    @error('total_capacity')
                                        <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                    @enderror
                                </div>

                                <div id="slotsPreview" class="bg-slate-50 border border-blue-100 rounded-xl p-4 space-y-2">
                                    <div class="flex items-center justify-between">
                                        <h5 class="text-sm font-semibold text-gray-700">Vista previa de horarios</h5>
                                        <span class="text-xs text-gray-500" id="previewSlotCount">0</span>
                                    </div>
                                    <div id="slotsContainer" class="space-y-1 text-xs text-gray-600">
                                        <p class="text-gray-500">Completa los campos para ver la vista previa.</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                            <div class="text-sm text-gray-600 bg-slate-50 border border-gray-200 rounded-lg px-4 py-3 space-y-1 sm:space-y-0 sm:flex sm:flex-wrap sm:items-center sm:gap-2">
                                <span><span id="totalDays" class="font-semibold text-blue-600">0</span> días seleccionados</span>
                                <span class="hidden sm:inline">·</span>
                                <span><span id="totalSlots" class="font-semibold text-blue-600">0</span> horarios por día</span>
                                <span class="hidden sm:inline">·</span>
                                <span><span id="totalSchedules" class="font-semibold text-blue-600">0</span> horarios totales</span>
                            </div>
                            <button type="submit"
                                class="inline-flex items-center px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6" />
                                </svg>
                                Crear horarios en lote
                            </button>
                        </div>
                    </form>
                </section>

                <section id="tab-individual" data-tab-panel class="space-y-8 hidden">
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold text-slate-900">Agregar horario individual</h3>
                        <p class="text-sm text-slate-500 max-w-2xl">Utiliza este formulario cuando necesites un ajuste puntual en la agenda sin afectar otras fechas.</p>
                    </div>

                    <form method="POST" action="{{ route('admin.maintenance.slots.store') }}" class="space-y-6">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label for="date" class="block text-sm font-medium text-gray-700 mb-1">Fecha <span class="text-red-500">*</span></label>
                                <input type="date" id="date" name="date" value="{{ old('date') }}"
                                    min="{{ date('Y-m-d') }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required>
                                @error('date')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="start_time" class="block text-sm font-medium text-gray-700 mb-1">Hora inicio <span class="text-red-500">*</span></label>
                                <input type="time" id="start_time" name="start_time" value="{{ old('start_time', '09:00') }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required>
                                @error('start_time')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="end_time" class="block text-sm font-medium text-gray-700 mb-1">Hora fin <span class="text-red-500">*</span></label>
                                <input type="time" id="end_time" name="end_time" value="{{ old('end_time', '10:00') }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required>
                                @error('end_time')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                            <div>
                                <label for="capacity" class="block text-sm font-medium text-gray-700 mb-1">Capacidad <span class="text-red-500">*</span></label>
                                <input type="number" id="capacity" name="capacity" min="1" max="10" value="{{ old('capacity', 2) }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                    required>
                                @error('capacity')
                                    <p class="text-xs text-red-600 mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>

                        <div class="flex justify-end">
                            <button type="submit"
                                class="inline-flex items-center px-5 py-2.5 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v12m6-6H6" />
                                </svg>
                                Guardar horario
                            </button>
                        </div>
                    </form>
                </section>

                <section id="tab-agenda" data-tab-panel class="space-y-8 hidden">
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold text-slate-900">Agenda programada</h3>
                        <p class="text-sm text-slate-500 max-w-2xl">Consulta los horarios configurados, ajusta su capacidad y desactiva los que no estén disponibles temporalmente.</p>
                    </div>

                    @php
                        $allSlots = $groupedSlots->flatten(1);
                        $totalConfigured = $allSlots->count();
                        $totalCapacity = $allSlots->sum('capacity');
                        $totalBooked = $allSlots->sum('booked_count');
                        $activeSlots = $allSlots->filter(fn($slot) => $slot->is_active)->count();
                    @endphp

                    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
                        <div class="rounded-xl border border-blue-100 bg-blue-50/50 p-4">
                            <p class="text-xs uppercase font-semibold text-blue-600 tracking-wide">Horarios configurados</p>
                            <p class="text-2xl font-bold text-slate-900 mt-2">{{ $totalConfigured }}</p>
                        </div>
                        <div class="rounded-xl border border-emerald-100 bg-emerald-50/60 p-4">
                            <p class="text-xs uppercase font-semibold text-emerald-600 tracking-wide">Capacidad total</p>
                            <p class="text-2xl font-bold text-slate-900 mt-2">{{ $totalCapacity }}</p>
                        </div>
                        <div class="rounded-xl border border-amber-100 bg-amber-50/60 p-4">
                            <p class="text-xs uppercase font-semibold text-amber-600 tracking-wide">Reservas activas</p>
                            <p class="text-2xl font-bold text-slate-900 mt-2">{{ $totalBooked }}</p>
                        </div>
                        <div class="rounded-xl border border-indigo-100 bg-indigo-50/60 p-4">
                            <p class="text-xs uppercase font-semibold text-indigo-600 tracking-wide">Horarios activos</p>
                            <p class="text-2xl font-bold text-slate-900 mt-2">{{ $activeSlots }}</p>
                        </div>
                    </div>

                    <div class="space-y-6">
                        @forelse ($groupedSlots as $date => $slots)
                            <div class="border border-gray-200 rounded-2xl overflow-hidden">
                                <div class="px-6 py-4 bg-slate-50 border-b border-gray-200 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                                    <div>
                                        <h4 class="text-lg font-semibold text-gray-900">{{ \Carbon\Carbon::parse($date)->translatedFormat('d \d\e F, Y') }}</h4>
                                        <p class="text-sm text-gray-500">{{ $slots->count() }} horario(s) configurado(s)</p>
                                    </div>
                                    <div class="flex items-center gap-4 text-sm text-gray-600">
                                        <span>Capacidad total: <span class="font-semibold text-blue-600">{{ $slots->sum('capacity') }}</span></span>
                                        <span>Reservados: <span class="font-semibold text-amber-600">{{ $slots->sum('booked_count') }}</span></span>
                                    </div>
                                </div>
                                <div class="divide-y divide-gray-100">
                                    @foreach ($slots as $slot)
                                        <div class="px-6 py-4 flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                            <div class="flex flex-col sm:flex-row sm:items-center sm:gap-4">
                                                <div class="px-3 py-2 bg-blue-50 border border-blue-200 rounded-lg text-sm font-semibold text-blue-700">
                                                    {{ \Carbon\Carbon::parse($slot->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($slot->end_time)->format('H:i') }}
                                                </div>
                                                <div class="text-sm text-gray-600 grid grid-cols-2 sm:grid-cols-3 gap-x-6 gap-y-1">
                                                    <span>Capacidad: <span class="font-semibold text-gray-900">{{ $slot->capacity }}</span></span>
                                                    <span>Reservados: <span class="font-semibold text-gray-900">{{ $slot->booked_count }}</span></span>
                                                    <span>Estado: <span class="font-semibold {{ $slot->is_active ? 'text-green-600' : 'text-gray-500' }}">{{ $slot->is_active ? 'Activo' : 'Inactivo' }}</span></span>
                                                </div>
                                            </div>
                                            <div class="flex flex-wrap items-center gap-3">
                                                <form method="POST" action="{{ route('admin.maintenance.slots.update', $slot) }}" class="flex items-center gap-2">
                                                    @csrf
                                                    @method('PUT')
                                                    <input type="number" min="1" max="20" name="capacity" value="{{ $slot->capacity }}"
                                                        class="w-20 border border-gray-300 rounded-lg px-2 py-1 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                                    <label class="flex items-center text-xs text-gray-600">
                                                        <input type="checkbox" name="is_active" value="1"
                                                            class="mr-2 rounded border-gray-300 text-green-600 focus:ring-green-500" {{ $slot->is_active ? 'checked' : '' }}>
                                                        Activo
                                                    </label>
                                                    <button type="submit"
                                                        class="inline-flex items-center px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-semibold rounded-lg transition-colors">Actualizar</button>
                                                </form>
                                                <form method="POST" action="{{ route('admin.maintenance.slots.destroy', $slot) }}"
                                                    onsubmit="return confirm('¿Seguro que deseas eliminar este horario?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit"
                                                        class="inline-flex items-center px-3 py-2 bg-red-600 hover:bg-red-700 text-white text-xs font-semibold rounded-lg transition-colors">Eliminar</button>
                                                </form>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="bg-slate-50 border border-gray-200 rounded-xl p-6 text-center text-gray-600">
                                <p>No hay horarios configurados. ¡Comienza agregando uno!</p>
                            </div>
                        @endforelse
                    </div>
                </section>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const tabButtons = document.querySelectorAll('.tab-trigger');
            const tabPanels = document.querySelectorAll('[data-tab-panel]');

            tabButtons.forEach(button => {
                button.addEventListener('click', () => {
                    const targetId = button.getAttribute('data-tab-target');

                    tabButtons.forEach(btn => {
                        btn.classList.remove('bg-white', 'shadow-sm', 'text-blue-700');
                        btn.classList.add('text-slate-600');
                    });

                    button.classList.add('bg-white', 'shadow-sm', 'text-blue-700');
                    button.classList.remove('text-slate-600');

                    tabPanels.forEach(panel => {
                        panel.classList.toggle('hidden', panel.id !== targetId);
                    });
                });
            });

            const isLoanedCheckbox = document.getElementById('is_loaned');
            const loanDetails = document.getElementById('loanDetails');
            const nameInput = document.getElementById('loaned_to_name');
            const emailInput = document.getElementById('loaned_to_email');
            const maintenanceUsers = @json($users->map(function ($user) {
                return ['name' => $user->name, 'email' => $user->email];
            }));

            function toggleLoanDetails() {
                if (!loanDetails) {
                    return;
                }

                if (isLoanedCheckbox && isLoanedCheckbox.checked) {
                    loanDetails.classList.remove('hidden');
                } else {
                    loanDetails.classList.add('hidden');
                }
            }

            function syncFromName() {
                if (!nameInput || !emailInput) {
                    return;
                }

                const value = nameInput.value.trim().toLowerCase();
                const user = maintenanceUsers.find(user => user.name.toLowerCase() === value);
                if (user) {
                    emailInput.value = user.email;
                }
            }

            function syncFromEmail() {
                if (!nameInput || !emailInput) {
                    return;
                }

                const value = emailInput.value.trim().toLowerCase();
                const user = maintenanceUsers.find(user => user.email.toLowerCase() === value);
                if (user) {
                    nameInput.value = user.name;
                }
            }

            if (isLoanedCheckbox) {
                isLoanedCheckbox.addEventListener('change', toggleLoanDetails);
                toggleLoanDetails();
            }

            if (nameInput) {
                nameInput.addEventListener('change', syncFromName);
                nameInput.addEventListener('blur', syncFromName);
            }

            if (emailInput) {
                emailInput.addEventListener('change', syncFromEmail);
                emailInput.addEventListener('blur', syncFromEmail);
            }

            calculateSlots();
            updateTotalDays();

            const startTimeInput = document.getElementById('bulk_start_time');
            const endTimeInput = document.getElementById('bulk_end_time');
            const capacityInput = document.getElementById('total_capacity');
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');

            if (startTimeInput) {
                startTimeInput.addEventListener('change', calculateSlots);
            }
            if (endTimeInput) {
                endTimeInput.addEventListener('change', calculateSlots);
            }
            if (capacityInput) {
                capacityInput.addEventListener('change', calculateSlots);
            }
            if (startDateInput) {
                startDateInput.addEventListener('change', function () {
                    updateEndDateMin();
                    updateTotalDays();
                });
            }
            if (endDateInput) {
                endDateInput.addEventListener('change', updateTotalDays);
            }

            document.querySelectorAll('input[name="days[]"]').forEach(checkbox => {
                checkbox.addEventListener('change', updateTotalDays);
            });

            // Event listener para auto-llenar formulario de ficha técnica
            const profileTicketSelector = document.getElementById('maintenance_ticket_id');
            if (profileTicketSelector) {
                profileTicketSelector.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    
                    if (selectedOption && selectedOption.value) {
                        // Obtener los datos del ticket desde los data attributes
                        const equipmentIdentifier = selectedOption.dataset.equipmentIdentifier || '';
                        const equipmentBrand = selectedOption.dataset.equipmentBrand || '';
                        const equipmentModel = selectedOption.dataset.equipmentModel || '';
                        const diskType = selectedOption.dataset.diskType || '';
                        const ramCapacity = selectedOption.dataset.ramCapacity || '';
                        const batteryStatus = selectedOption.dataset.batteryStatus || '';
                        const aestheticObservations = selectedOption.dataset.aestheticObservations || '';
                        
                        console.log('Datos del ticket:', {
                            equipmentIdentifier, equipmentBrand, equipmentModel, 
                            diskType, ramCapacity, batteryStatus
                        });
                        
                        // Llenar los campos del formulario (IDs correctos)
                        const identifierField = document.getElementById('identifier');
                        const brandField = document.getElementById('brand');
                        const modelField = document.getElementById('model');
                        const diskTypeField = document.getElementById('disk_type');
                        const ramField = document.getElementById('ram_capacity');
                        const batteryField = document.getElementById('battery_status');
                        const aestheticField = document.getElementById('aesthetic_observations');
                        
                        if (identifierField) {
                            identifierField.value = equipmentIdentifier;
                            console.log('Identificador llenado:', equipmentIdentifier);
                        }
                        if (brandField) {
                            brandField.value = equipmentBrand;
                            console.log('Marca llenada:', equipmentBrand);
                        }
                        if (modelField) {
                            modelField.value = equipmentModel;
                            console.log('Modelo llenado:', equipmentModel);
                        }
                        if (diskTypeField) diskTypeField.value = diskType;
                        if (ramField) ramField.value = ramCapacity;
                        if (batteryField) batteryField.value = batteryStatus;
                        if (aestheticField) aestheticField.value = aestheticObservations;
                        
                        // Manejar componentes de reemplazo (checkboxes)
                        try {
                            const replacementComponents = JSON.parse(selectedOption.dataset.replacementComponents || '[]');
                            
                            // Desmarcar todos los checkboxes primero
                            document.querySelectorAll('input[name="replacement_components[]"]').forEach(checkbox => {
                                checkbox.checked = false;
                            });
                            
                            // Marcar los checkboxes que vienen en el ticket
                            replacementComponents.forEach(component => {
                                const checkbox = document.querySelector(`input[name="replacement_components[]"][value="${component}"]`);
                                if (checkbox) {
                                    checkbox.checked = true;
                                }
                            });
                        } catch (e) {
                            console.error('Error al procesar componentes de reemplazo:', e);
                        }
                    } else {
                        // Si se deselecciona el ticket, limpiar los campos
                        const fieldsToClear = [
                            'identifier', 'brand', 'model', 
                            'disk_type', 'ram_capacity', 'battery_status', 'aesthetic_observations'
                        ];
                        
                        fieldsToClear.forEach(fieldId => {
                            const field = document.getElementById(fieldId);
                            if (field) field.value = '';
                        });
                        
                        // Desmarcar todos los checkboxes
                        document.querySelectorAll('input[name="replacement_components[]"]').forEach(checkbox => {
                            checkbox.checked = false;
                        });
                    }
                });
            }

            const maintenanceSelector = document.getElementById('maintenanceTicketSelector');
            const maintenancePanels = document.querySelectorAll('[data-ticket-panel]');

            console.log('=== INICIALIZACIÓN DE SEGUIMIENTO ===');
            console.log('maintenanceSelector encontrado:', maintenanceSelector);
            console.log('maintenancePanels encontrados:', maintenancePanels.length);

            if (maintenanceSelector && maintenancePanels.length) {
                const showMaintenancePanel = (panelId) => {
                    maintenancePanels.forEach((panel) => {
                        panel.classList.toggle('hidden', panel.dataset.ticketPanel !== panelId || !panelId);
                    });
                };

                const applyDefaultPanel = () => {
                    const defaultValue = maintenanceSelector.dataset.default;

                    if (maintenanceSelector.value) {
                        showMaintenancePanel(maintenanceSelector.value);
                    } else if (defaultValue) {
                        maintenanceSelector.value = defaultValue;
                        showMaintenancePanel(defaultValue);
                    } else {
                        showMaintenancePanel('');
                    }
                };

                maintenanceSelector.addEventListener('change', (event) => {
                    const selectedValue = event.target.value;
                    console.log('=== EVENTO CHANGE EN SEGUIMIENTO ===');
                    console.log('Valor seleccionado:', selectedValue);
                    showMaintenancePanel(selectedValue);
                    
                    // Si se seleccionó un ticket, también seleccionarlo arriba en el formulario de ficha técnica
                    if (selectedValue) {
                        const ticketId = selectedValue.replace('ticket-', '');
                        console.log('ID del ticket extraído:', ticketId);
                        
                        // Activar el tab de "Ficha técnica" primero
                        const tabProfilesTrigger = document.querySelector('[data-tab-target="tab-profiles"]');
                        console.log('Tab trigger encontrado:', tabProfilesTrigger);
                        
                        if (tabProfilesTrigger) {
                            const targetId = 'tab-profiles';
                            
                            // Actualizar estilos de los botones
                            const tabButtons = document.querySelectorAll('.tab-trigger');
                            console.log('Botones de tab encontrados:', tabButtons.length);
                            tabButtons.forEach(btn => {
                                btn.classList.remove('bg-white', 'shadow-sm', 'text-blue-700');
                                btn.classList.add('text-slate-600');
                            });
                            tabProfilesTrigger.classList.add('bg-white', 'shadow-sm', 'text-blue-700');
                            tabProfilesTrigger.classList.remove('text-slate-600');
                            
                            // Mostrar el panel correcto
                            const tabPanels = document.querySelectorAll('[data-tab-panel]');
                            console.log('Paneles de tab encontrados:', tabPanels.length);
                            tabPanels.forEach(panel => {
                                const shouldHide = panel.id !== targetId;
                                console.log(`Panel ${panel.id}: ${shouldHide ? 'ocultar' : 'mostrar'}`);
                                panel.classList.toggle('hidden', shouldHide);
                            });
                            console.log('✓ Tab activado');
                        } else {
                            console.error('✗ No se encontró el tab trigger');
                        }
                        
                        // Pequeña pausa para que el tab se active
                        setTimeout(() => {
                            const profileTicketSelector = document.getElementById('maintenance_ticket_id');
                            const technicalProfileForm = document.getElementById('technicalProfileForm');
                            
                            console.log('Buscando elementos del formulario...');
                            console.log('- Formulario:', technicalProfileForm);
                            console.log('- Selector de ticket:', profileTicketSelector);
                            console.log('- Clases del formulario:', technicalProfileForm?.classList.toString());
                            
                            if (profileTicketSelector && technicalProfileForm) {
                                // Mostrar el formulario
                                console.log('Removiendo clase hidden del formulario...');
                                technicalProfileForm.classList.remove('hidden');
                                console.log('- Clases después de remover hidden:', technicalProfileForm.classList.toString());
                                console.log('✓ Formulario debería estar visible');
                                
                                // Seleccionar el ticket
                                console.log('Asignando ticketId al selector:', ticketId);
                                profileTicketSelector.value = ticketId;
                                console.log('Valor del selector después de asignar:', profileTicketSelector.value);
                                
                                // Disparar evento change para que se llenen los campos
                                console.log('Disparando evento change...');
                                profileTicketSelector.dispatchEvent(new Event('change'));
                                console.log('✓ Evento change disparado');
                                
                                // Scroll hacia el formulario
                                console.log('Haciendo scroll al formulario...');
                                technicalProfileForm.scrollIntoView({ behavior: 'smooth', block: 'start' });
                                console.log('✓ Scroll completado');
                            } else {
                                console.error('✗ No se encontró el formulario o el selector de ticket');
                                console.error('- profileTicketSelector:', profileTicketSelector);
                                console.error('- technicalProfileForm:', technicalProfileForm);
                            }
                        }, 200);
                    } else {
                        // Si se deselecciona, ocultar el formulario
                        console.log('Deseleccionado - ocultando formulario');
                        const technicalProfileForm = document.getElementById('technicalProfileForm');
                        if (technicalProfileForm) {
                            technicalProfileForm.classList.add('hidden');
                            console.log('✓ Formulario oculto');
                        }
                    }
                });

                applyDefaultPanel();
            }
        });

        function updateEndDateMin() {
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');

            if (!startDateInput || !endDateInput) {
                return;
            }

            const startDate = startDateInput.value;
            if (startDate) {
                endDateInput.min = startDate;
                if (endDateInput.value && endDateInput.value < startDate) {
                    endDateInput.value = '';
                }
            } else {
                endDateInput.min = '{{ date('Y-m-d') }}';
            }
        }

        function calculateSlots() {
            const startTimeInput = document.getElementById('bulk_start_time');
            const endTimeInput = document.getElementById('bulk_end_time');
            const capacityInput = document.getElementById('total_capacity');
            const container = document.getElementById('slotsContainer');
            const totalSlotsLabel = document.getElementById('totalSlots');
            const totalSchedulesLabel = document.getElementById('totalSchedules');
            const previewSlotCount = document.getElementById('previewSlotCount');

            if (!startTimeInput || !endTimeInput || !capacityInput || !container) {
                return;
            }

            const startTime = startTimeInput.value;
            const endTime = endTimeInput.value;
            const capacity = parseInt(capacityInput.value, 10);

            if (!startTime || !endTime || !capacity) {
                container.innerHTML = '<p class="text-gray-500">Completa los campos para ver la vista previa.</p>';
                if (totalSlotsLabel) totalSlotsLabel.textContent = '0';
                if (totalSchedulesLabel) totalSchedulesLabel.textContent = '0';
                if (previewSlotCount) previewSlotCount.textContent = '0';
                return;
            }

            const start = new Date(`1970-01-01T${startTime}:00`);
            const end = new Date(`1970-01-01T${endTime}:00`);
            const diffMinutes = Math.abs((end - start) / 60000);

            if (diffMinutes === 0 || capacity === 0) {
                container.innerHTML = '<p class="text-red-500 text-sm">Verifica la hora de inicio, fin y capacidad.</p>';
                if (totalSlotsLabel) totalSlotsLabel.textContent = '0';
                if (totalSchedulesLabel) totalSchedulesLabel.textContent = '0';
                if (previewSlotCount) previewSlotCount.textContent = '0';
                return;
            }

            const slotDuration = Math.floor(diffMinutes / capacity);
            if (slotDuration < 1) {
                container.innerHTML = '<p class="text-red-500 text-sm">La duración calculada por horario es menor a un minuto. Ajusta la capacidad o el rango de tiempo.</p>';
                if (totalSlotsLabel) totalSlotsLabel.textContent = '0';
                if (totalSchedulesLabel) totalSchedulesLabel.textContent = '0';
                if (previewSlotCount) previewSlotCount.textContent = '0';
                return;
            }

            let currentTime = new Date(start);
            const rows = [];

            for (let i = 0; i < capacity; i++) {
                const slotStart = new Date(currentTime);
                const slotEnd = new Date(currentTime.getTime() + slotDuration * 60000);
                if (slotEnd > end) {
                    break;
                }

                rows.push(`<div class="flex items-center justify-between bg-white border border-gray-200 rounded-lg px-3 py-2">
                        <span class="font-medium text-gray-700">${slotStart.toTimeString().slice(0, 5)} - ${slotEnd.toTimeString().slice(0, 5)}</span>
                        <span class="text-xs text-gray-500">${slotDuration} minutos</span>
                    </div>`);
                currentTime = slotEnd;
            }

            container.innerHTML = rows.join('');
            if (totalSlotsLabel) totalSlotsLabel.textContent = rows.length.toString();
            if (previewSlotCount) previewSlotCount.textContent = rows.length.toString();
            updateTotalDays();
        }

        function updateTotalDays() {
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            const totalDaysLabel = document.getElementById('totalDays');
            const totalSlotsLabel = document.getElementById('totalSlots');
            const totalSchedulesLabel = document.getElementById('totalSchedules');

            if (!startDateInput || !endDateInput) {
                return;
            }

            const startDateValue = startDateInput.value;
            const endDateValue = endDateInput.value;
            const selectedDays = Array.from(document.querySelectorAll('input[name="days[]"]:checked'));

            if (!startDateValue || !endDateValue || selectedDays.length === 0) {
                if (totalDaysLabel) {
                    totalDaysLabel.textContent = '0';
                }
                if (totalSchedulesLabel) {
                    totalSchedulesLabel.textContent = '0';
                }
                return;
            }

            const startDate = new Date(startDateValue);
            const endDate = new Date(endDateValue);
            let count = 0;

            for (let date = new Date(startDate); date <= endDate; date.setDate(date.getDate() + 1)) {
                const dayOfWeek = date.getDay();
                const matches = selectedDays.some(checkbox => {
                    const value = checkbox.value;
                    const map = {
                        sunday: 0,
                        monday: 1,
                        tuesday: 2,
                        wednesday: 3,
                        thursday: 4,
                        friday: 5,
                        saturday: 6,
                    };
                    return map[value] === dayOfWeek;
                });

                if (matches) {
                    count++;
                }
            }

            if (totalDaysLabel) totalDaysLabel.textContent = count.toString();

            const slotsContainer = document.getElementById('slotsContainer');
            const totalSlots = slotsContainer ? slotsContainer.children.length : 0;

            if (totalSchedulesLabel) {
                totalSchedulesLabel.textContent = (count * totalSlots).toString();
            }
            if (totalSlotsLabel) {
                totalSlotsLabel.textContent = totalSlots.toString();
            }
        }
    </script>
@endsection
