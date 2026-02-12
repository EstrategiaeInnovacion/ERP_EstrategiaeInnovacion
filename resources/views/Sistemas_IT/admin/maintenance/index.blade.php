@extends('layouts.master')

@section('title', 'Gestión de Mantenimientos - Panel Administrativo')

@section('content')
    <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between mb-8">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Gestión de Mantenimientos</h2>
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
                <button type="button" data-tab-target="tab-agenda"
                    class="tab-trigger inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-blue-700 bg-white shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Agenda de Mantenimientos
                </button>
                <button type="button" data-tab-target="tab-profiles"
                    class="tab-trigger inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:text-blue-700 hover:bg-white/80">
                    <span class="hidden sm:inline">Ficha técnica</span>
                    <span class="sm:hidden">Ficha</span>
                </button>
                <button type="button" data-tab-target="tab-expedientes"
                    class="tab-trigger inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:text-blue-700 hover:bg-white/80">
                    <span class="hidden sm:inline">Expedientes</span>
                    <span class="sm:hidden">Expedientes</span>
                </button>
                <button type="button" data-tab-target="tab-bloqueos"
                    class="tab-trigger inline-flex items-center gap-2 rounded-lg px-4 py-2 text-sm font-semibold text-slate-600 hover:text-blue-700 hover:bg-white/80">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                    </svg>
                    Bloquear Horarios
                </button>
            </div>

            <div class="p-6 sm:p-8 space-y-10">
                {{-- Nueva pestaña: Agenda de Mantenimientos --}}
                <section id="tab-agenda" data-tab-panel class="space-y-8">
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold text-slate-900">Agenda de Mantenimientos</h3>
                        <p class="text-sm text-slate-500 max-w-2xl">Visualiza los mantenimientos programados de la semana. Los horarios disponibles son de 9:00 AM a 4:00 PM (bloques de 1 hora).</p>
                    </div>

                    {{-- Navegación de semanas --}}
                    <div class="flex items-center justify-between bg-slate-50 rounded-xl p-4 border border-slate-200">
                        <button type="button" id="prevWeek" class="p-2 rounded-lg hover:bg-white hover:shadow-sm transition-all">
                            <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                            </svg>
                        </button>
                        <div class="text-center">
                            <h4 id="weekLabel" class="text-lg font-bold text-slate-800">Cargando...</h4>
                            <p class="text-xs text-slate-500">Semana actual</p>
                        </div>
                        <button type="button" id="nextWeek" class="p-2 rounded-lg hover:bg-white hover:shadow-sm transition-all">
                            <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </button>
                    </div>

                    {{-- Mini calendario con días con mantenimientos --}}
                    <div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
                        <div class="lg:col-span-1">
                            <div class="bg-white border border-slate-200 rounded-xl p-4">
                                <h5 class="text-sm font-bold text-slate-700 mb-3">Calendario</h5>
                                <div id="miniCalendarContainer">
                                    <div id="miniCalendarLoading" class="text-center py-4">
                                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600 mx-auto"></div>
                                        <p class="text-xs text-slate-400 mt-2">Cargando...</p>
                                    </div>
                                    <div id="miniCalendar" class="hidden"></div>
                                </div>
                            </div>
                        </div>

                        {{-- Lista de mantenimientos de la semana --}}
                        <div class="lg:col-span-3">
                            <div id="weekView" class="space-y-4">
                                <div class="text-center py-10">
                                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                                    <p class="text-sm text-slate-500 mt-2">Cargando agenda...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                {{-- Pestaña de bloqueos --}}
                <section id="tab-bloqueos" data-tab-panel class="space-y-8 hidden">
                    <div class="space-y-2">
                        <h3 class="text-xl font-semibold text-slate-900">Bloquear Horarios</h3>
                        <p class="text-sm text-slate-500 max-w-2xl">Bloquea horarios específicos o rangos de fechas cuando no haya disponibilidad para mantenimientos.</p>
                    </div>

                    <form method="POST" action="{{ route('admin.maintenance.block-slot') }}" class="bg-slate-50 border border-slate-200 rounded-xl p-6 space-y-6">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                            <div>
                                <label for="date_start" class="block text-sm font-medium text-gray-700 mb-1">Fecha inicio <span class="text-red-500">*</span></label>
                                <input type="date" id="date_start" name="date_start" required
                                    min="{{ date('Y-m-d') }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div>
                                <label for="date_end" class="block text-sm font-medium text-gray-700 mb-1">Fecha fin (opcional)</label>
                                <input type="date" id="date_end" name="date_end"
                                    min="{{ date('Y-m-d') }}"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <p class="text-xs text-gray-500 mt-1">Deja vacío para bloquear solo un día</p>
                            </div>
                            <div>
                                <label for="time_slot" class="block text-sm font-medium text-gray-700 mb-1">Horario específico</label>
                                <select id="time_slot" name="time_slot" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                                    <option value="">Todo el día</option>
                                    @foreach($timeSlots ?? [] as $slot)
                                        <option value="{{ $slot['start'] }}">{{ $slot['label'] }} - {{ Carbon\Carbon::parse($slot['end'])->format('h:i A') }}</option>
                                    @endforeach
                                </select>
                                <p class="text-xs text-gray-500 mt-1">Deja vacío para bloquear todo el día</p>
                            </div>
                            <div>
                                <label for="reason" class="block text-sm font-medium text-gray-700 mb-1">Motivo (opcional)</label>
                                <input type="text" id="reason" name="reason" placeholder="Ej: Junta de departamento"
                                    class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            </div>
                        </div>
                        <div class="flex justify-end">
                            <button type="submit" class="inline-flex items-center px-5 py-2.5 bg-red-600 hover:bg-red-700 text-white font-semibold rounded-lg transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                </svg>
                                Bloquear horario
                            </button>
                        </div>
                    </form>

                    {{-- Lista de bloqueos activos --}}
                    <div class="space-y-4">
                        <h4 class="text-lg font-semibold text-slate-800">Bloqueos activos</h4>
                        @if(isset($blockedSlots) && $blockedSlots->count() > 0)
                            <div class="divide-y divide-gray-200 border border-gray-200 rounded-xl overflow-hidden">
                                @foreach($blockedSlots as $block)
                                    <div class="flex items-center justify-between p-4 bg-white hover:bg-slate-50">
                                        <div class="flex items-center gap-4">
                                            <div class="p-2 bg-red-100 rounded-lg">
                                                <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="font-medium text-slate-800">
                                                    {{ \Carbon\Carbon::parse($block->date_start)->translatedFormat('d M, Y') }}
                                                    @if($block->date_end)
                                                        - {{ \Carbon\Carbon::parse($block->date_end)->translatedFormat('d M, Y') }}
                                                    @endif
                                                </p>
                                                <p class="text-sm text-slate-500">
                                                    @if($block->time_slot)
                                                        Horario: {{ \Carbon\Carbon::parse($block->time_slot)->format('h:i A') }}
                                                    @else
                                                        Todo el día
                                                    @endif
                                                    @if($block->reason)
                                                        · {{ $block->reason }}
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                        <form method="POST" action="{{ route('admin.maintenance.unblock-slot', $block) }}" onsubmit="return confirm('¿Eliminar este bloqueo?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                        </form>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="bg-slate-50 border border-slate-200 rounded-xl p-6 text-center">
                                <p class="text-slate-500">No hay bloqueos activos.</p>
                            </div>
                        @endif
                    </div>
                </section>

                <section id="tab-profiles" data-tab-panel class="space-y-8 hidden">
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
            </div>
        </div>
    </main>

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
        /* Estilos personalizados para el mini calendario */
        #miniCalendarContainer {
            overflow: hidden;
        }
        #miniCalendar {
            display: flex;
            justify-content: center;
        }
        #miniCalendar .flatpickr-calendar {
            box-shadow: none !important;
            border: none !important;
            width: auto !important;
            max-width: 100% !important;
            font-size: 12px;
            position: relative !important;
            top: 0 !important;
            left: 0 !important;
            margin: 0 auto;
        }
        #miniCalendar .flatpickr-calendar.inline {
            position: relative !important;
            top: 0 !important;
        }
        #miniCalendar .flatpickr-months {
            padding: 0;
        }
        #miniCalendar .flatpickr-month {
            height: 32px;
        }
        #miniCalendar .flatpickr-current-month {
            font-size: 13px;
            padding-top: 6px;
        }
        #miniCalendar .flatpickr-monthDropdown-months,
        #miniCalendar .numInputWrapper {
            font-size: 12px;
        }
        #miniCalendar .flatpickr-innerContainer {
            max-width: 100%;
        }
        #miniCalendar .flatpickr-rContainer {
            max-width: 100%;
        }
        #miniCalendar .flatpickr-days {
            width: 100% !important;
        }
        #miniCalendar .dayContainer {
            width: 100% !important;
            min-width: auto !important;
            max-width: 100% !important;
            justify-content: center;
        }
        #miniCalendar .flatpickr-day {
            max-width: 28px;
            width: 28px;
            height: 28px;
            line-height: 28px;
            font-size: 11px;
            margin: 1px;
            flex-basis: auto !important;
        }
        #miniCalendar .flatpickr-day.selected {
            background: #3b82f6;
            border-color: #3b82f6;
        }
        #miniCalendar .flatpickr-day.today {
            border-color: #3b82f6;
        }
        #miniCalendar .flatpickr-weekdays {
            height: 24px;
        }
        #miniCalendar .flatpickr-weekday {
            font-size: 10px;
            color: #64748b;
            font-weight: 600;
        }
        #miniCalendar .flatpickr-prev-month,
        #miniCalendar .flatpickr-next-month {
            padding: 4px 8px;
        }
        #miniCalendar .flatpickr-prev-month svg,
        #miniCalendar .flatpickr-next-month svg {
            width: 10px;
            height: 10px;
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
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

        // ===== AGENDA DE MANTENIMIENTOS =====
        let currentWeekStart = null;
        let miniCalendarInstance = null;

        function initAgenda() {
            // Inicializar mini calendario
            initMiniCalendar();
            loadWeekMaintenances();

            document.getElementById('prevWeek')?.addEventListener('click', () => {
                if (currentWeekStart) {
                    const prevWeek = new Date(currentWeekStart);
                    prevWeek.setDate(prevWeek.getDate() - 7);
                    loadWeekMaintenances(prevWeek.toISOString().slice(0, 10));
                }
            });

            document.getElementById('nextWeek')?.addEventListener('click', () => {
                if (currentWeekStart) {
                    const nextWeek = new Date(currentWeekStart);
                    nextWeek.setDate(nextWeek.getDate() + 7);
                    loadWeekMaintenances(nextWeek.toISOString().slice(0, 10));
                }
            });
        }

        function initMiniCalendar() {
            const miniCalEl = document.getElementById('miniCalendar');
            const loadingEl = document.getElementById('miniCalendarLoading');
            if (!miniCalEl) return;

            // Mostrar calendario y ocultar loading
            if (loadingEl) loadingEl.classList.add('hidden');
            miniCalEl.classList.remove('hidden');

            miniCalendarInstance = flatpickr(miniCalEl, {
                inline: true,
                locale: 'es',
                dateFormat: 'Y-m-d',
                defaultDate: new Date(),
                disable: [
                    function(date) {
                        return (date.getDay() === 0 || date.getDay() === 6);
                    }
                ],
                onChange: function(selectedDates, dateStr) {
                    loadWeekMaintenances(dateStr);
                }
            });
        }

        async function loadWeekMaintenances(weekStart = null) {
            const weekView = document.getElementById('weekView');
            const weekLabel = document.getElementById('weekLabel');

            if (!weekView) return;

            weekView.innerHTML = `
                <div class="text-center py-10">
                    <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600 mx-auto"></div>
                    <p class="text-sm text-slate-500 mt-2">Cargando agenda...</p>
                </div>`;

            try {
                const url = new URL('{{ route("admin.maintenance.week-maintenances") }}');
                if (weekStart) url.searchParams.set('week_start', weekStart);

                const response = await fetch(url, {
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
                
                if (!response.ok) {
                    throw new Error('HTTP ' + response.status);
                }
                
                const data = await response.json();

                currentWeekStart = data.week_start;
                if (weekLabel) weekLabel.textContent = data.week_label;

                renderWeekView(data.days);
            } catch (error) {
                console.error('Error cargando agenda:', error);
                weekView.innerHTML = `
                    <div class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">
                        <p class="text-red-600">Error al cargar la agenda. Intenta de nuevo.</p>
                    </div>`;
            }
        }

        function renderWeekView(days) {
            const weekView = document.getElementById('weekView');
            if (!weekView) return;

            if (!days || days.length === 0) {
                weekView.innerHTML = `
                    <div class="bg-slate-50 border border-slate-200 rounded-xl p-6 text-center">
                        <p class="text-slate-500">No hay datos para mostrar.</p>
                    </div>`;
                return;
            }

            let html = '<div class="space-y-4">';

            days.forEach(day => {
                const hasMaintenances = day.maintenances && (Array.isArray(day.maintenances) ? day.maintenances.length > 0 : Object.keys(day.maintenances).length > 0);
                const isBlocked = day.blocked === 'all';
                const todayClass = day.is_today ? 'border-blue-400 bg-blue-50' : 'border-slate-200';

                html += `
                    <div class="border ${todayClass} rounded-xl overflow-hidden">
                        <div class="px-4 py-3 bg-white border-b border-slate-100 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-lg ${day.is_today ? 'bg-blue-600 text-white' : 'bg-slate-100 text-slate-700'} flex items-center justify-center font-bold">
                                    ${day.day_number}
                                </div>
                                <div>
                                    <p class="font-semibold text-slate-800 capitalize">${day.day_name}</p>
                                    <p class="text-xs text-slate-500">${day.month}</p>
                                </div>
                            </div>
                            ${day.is_today ? '<span class="px-2 py-1 text-xs font-bold bg-blue-100 text-blue-700 rounded-full">Hoy</span>' : ''}
                            ${isBlocked ? '<span class="px-2 py-1 text-xs font-bold bg-red-100 text-red-700 rounded-full">Día bloqueado</span>' : ''}
                        </div>`;

                if (isBlocked) {
                    html += `
                        <div class="px-4 py-6 bg-red-50 text-center">
                            <p class="text-sm text-red-600">Este día está bloqueado para mantenimientos.</p>
                        </div>`;
                } else if (!hasMaintenances) {
                    html += `
                        <div class="px-4 py-6 bg-slate-50 text-center">
                            <p class="text-sm text-slate-400">No hay mantenimientos programados.</p>
                        </div>`;
                } else {
                    html += '<div class="divide-y divide-slate-100">';
                    
                    const maintenances = Array.isArray(day.maintenances) ? day.maintenances : Object.values(day.maintenances).flat();
                    
                    maintenances.forEach(m => {
                        const estadoColor = m.estado === 'en_proceso' ? 'bg-amber-100 text-amber-700' : 'bg-green-100 text-green-700';
                        html += `
                            <div class="px-4 py-3 flex items-center justify-between hover:bg-slate-50 transition-colors">
                                <div class="flex items-center gap-4">
                                    <div class="px-3 py-1.5 bg-blue-50 text-blue-700 rounded-lg text-sm font-semibold">
                                        ${m.hora_label}
                                    </div>
                                    <div>
                                        <p class="font-medium text-slate-800">${m.solicitante}</p>
                                        <p class="text-xs text-slate-500">${m.folio} · ${m.asunto || 'Sin asunto'}</p>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-1 text-xs font-medium ${estadoColor} rounded-full capitalize">${m.estado.replace('_', ' ')}</span>
                                    <a href="{{ url('/admin/tickets') }}/${m.id}" class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-colors">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                        </svg>
                                    </a>
                                </div>
                            </div>`;
                    });
                    
                    html += '</div>';
                }

                html += '</div>';
            });

            html += '</div>';
            weekView.innerHTML = html;
        }

        // Inicializar agenda
        initAgenda();
    </script>
@endsection
