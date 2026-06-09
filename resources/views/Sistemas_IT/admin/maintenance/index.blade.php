@extends('layouts.master')

@section('title', 'Gestión de Mantenimientos - Panel Administrativo')

@section('content')
    <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between mb-8">
            <div>
                <h2 class="text-3xl font-bold text-gray-900">Gestión de Mantenimientos</h2>
                <p class="text-gray-600">Administra la agenda de mantenimientos y la documentación técnica de los equipos.</p>
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

        @if (session('error'))
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-8">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span class="text-sm text-red-800 font-medium">{{ session('error') }}</span>
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
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
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
                        <div class="lg:col-span-2">
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
                                                        Â· {{ $block->reason }}
                                                    @endif
                                                </p>
                                            </div>
                                        </div>
                                        <form method="POST" action="{{ route('admin.maintenance.unblock-slot', $block) }}" onsubmit="return confirm('Â¿Eliminar este bloqueo?');">
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


            </div>
        </div>
    </main>

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

            const isLoanedCheckbox = null;
            const loanDetails = null;
            const nameInput = null;
            const emailInput = null;
            const maintenanceUsers = @json($users->map(function ($user) {
                return ['name' => $user->name, 'email' => $user->email];
            }));

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
            const equipoPicker = document.getElementById('equipoPicker');
            const equipoPickerOptions = document.getElementById('equipoPickerOptions');

            function aplicarEquipo(eq) {
                const identifierField = document.getElementById('identifier');
                const brandField     = document.getElementById('brand');
                const modelField     = document.getElementById('model');
                if (identifierField) identifierField.value = eq.nombre || '';
                if (brandField)      brandField.value      = '';
                if (modelField)      modelField.value      = eq.modelo || '';
            }

            function renderEquipoPicker(equipos) {
                if (!equipoPicker || !equipoPickerOptions) return;
                if (!equipos || equipos.length === 0) {
                    equipoPicker.classList.add('hidden');
                    return;
                }
                equipoPickerOptions.innerHTML = '';
                equipos.forEach((eq, idx) => {
                    const card = document.createElement('label');
                    card.className = 'flex items-start gap-3 p-2.5 bg-white border border-indigo-200 rounded-lg cursor-pointer hover:border-indigo-400 transition';
                    card.innerHTML = `
                        <input type="radio" name="_equipo_picker" value="${idx}" class="mt-0.5 accent-indigo-600" ${eq.principal ? 'checked' : ''}>
                        <div class="text-sm">
                            <span class="font-semibold text-indigo-900">${eq.nombre}</span>
                            ${eq.principal ? '<span class="ml-1.5 text-[10px] font-bold bg-indigo-100 text-indigo-700 px-1.5 py-0.5 rounded-full">Principal</span>' : '<span class="ml-1.5 text-[10px] font-bold bg-slate-100 text-slate-600 px-1.5 py-0.5 rounded-full">Secundaria</span>'}
                            <br>
                            <span class="text-xs text-gray-500">${eq.modelo || 'Sin modelo'}${eq.serie ? ' Â· S/N: ' + eq.serie : ''}${eq.usuario ? ' Â· Usuario: ' + eq.usuario : ''}</span>
                        </div>`;
                    const radio = card.querySelector('input');
                    radio.addEventListener('change', () => {
                        if (radio.checked) aplicarEquipo(eq);
                    });
                    equipoPickerOptions.appendChild(card);
                    // Auto-fill con principal o primero
                    if (eq.principal || idx === 0) aplicarEquipo(eq);
                });
                equipoPicker.classList.remove('hidden');
            }

            if (profileTicketSelector) {
                profileTicketSelector.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    
                    if (selectedOption && selectedOption.value) {
                        // Leer equipos asignados del usuario
                        let equiposAsignados = [];
                        try {
                            equiposAsignados = JSON.parse(selectedOption.dataset.equiposAsignados || '[]');
                        } catch(e) {}

                        // Datos del ticket (fallback si no hay equipo asignado)
                        const equipmentIdentifier = selectedOption.dataset.equipmentIdentifier || '';
                        const equipmentBrand      = selectedOption.dataset.equipmentBrand || '';
                        const equipmentModel      = selectedOption.dataset.equipmentModel || '';
                        const diskType            = selectedOption.dataset.diskType || '';
                        const ramCapacity         = selectedOption.dataset.ramCapacity || '';
                        const batteryStatus       = selectedOption.dataset.batteryStatus || '';
                        const aestheticObservations = selectedOption.dataset.aestheticObservations || '';

                        // Llenar campos de especificaciones técnicas desde el ticket
                        const diskTypeField   = document.getElementById('disk_type');
                        const ramField        = document.getElementById('ram_capacity');
                        const batteryField    = document.getElementById('battery_status');
                        const aestheticField  = document.getElementById('aesthetic_observations');
                        if (diskTypeField)  diskTypeField.value  = diskType;
                        if (ramField)       ramField.value       = ramCapacity;
                        if (batteryField)   batteryField.value   = batteryStatus;
                        if (aestheticField) aestheticField.value = aestheticObservations;

                        // Si hay equipos asignados, mostrar picker (prioridad sobre campos del ticket)
                        if (equiposAsignados.length > 0) {
                            renderEquipoPicker(equiposAsignados);
                        } else {
                            // Fallback: usar datos del ticket
                            if (equipoPicker) equipoPicker.classList.add('hidden');
                            const identifierField = document.getElementById('identifier');
                            const brandField      = document.getElementById('brand');
                            const modelField      = document.getElementById('model');
                            if (identifierField) identifierField.value = equipmentIdentifier;
                            if (brandField)      brandField.value      = equipmentBrand;
                            if (modelField)      modelField.value      = equipmentModel;
                        }

                        // Componentes de reemplazo
                        try {
                            const replacementComponents = JSON.parse(selectedOption.dataset.replacementComponents || '[]');
                            document.querySelectorAll('input[name="replacement_components[]"]').forEach(cb => { cb.checked = false; });
                            replacementComponents.forEach(component => {
                                const cb = document.querySelector(`input[name="replacement_components[]"][value="${component}"]`);
                                if (cb) cb.checked = true;
                            });
                        } catch (e) {}

                        // Mostrar/ocultar Step 6
                        const seguimientoNoTicket = document.getElementById('seguimientoNoTicket');
                        const seguimientoContent  = document.getElementById('seguimientoContent');
                        if (seguimientoNoTicket) seguimientoNoTicket.classList.add('hidden');
                        if (seguimientoContent)  seguimientoContent.classList.remove('hidden');
                    } else {
                        if (equipoPicker) equipoPicker.classList.add('hidden');
                        ['identifier','brand','model','disk_type','ram_capacity','battery_status','aesthetic_observations'].forEach(id => {
                            const f = document.getElementById(id);
                            if (f) f.value = '';
                        });
                        document.querySelectorAll('input[name="replacement_components[]"]').forEach(cb => { cb.checked = false; });

                        // Ocultar Step 6
                        const seguimientoNoTicket = document.getElementById('seguimientoNoTicket');
                        const seguimientoContent  = document.getElementById('seguimientoContent');
                        if (seguimientoNoTicket) seguimientoNoTicket.classList.remove('hidden');
                        if (seguimientoContent)  seguimientoContent.classList.add('hidden');
                    }
                });
            }

            // (seguimiento integrado en Step 6 del formulario â€” no requiere paneles separados)

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
            if (typeof flatpickr === 'undefined') {
                console.error('flatpickr no está disponible. Verifique la carga del CDN.');
                return;
            }

            // Mostrar calendario y ocultar loading
            if (loadingEl) loadingEl.classList.add('hidden');
            miniCalEl.classList.remove('hidden');

            miniCalendarInstance = flatpickr(miniCalEl, {
                inline: true,
                locale: (typeof flatpickr !== 'undefined' && flatpickr.l10ns && flatpickr.l10ns.es) ? flatpickr.l10ns.es : 'es',
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
                                        <p class="text-xs text-slate-500">${m.folio} Â· ${m.asunto || 'Sin asunto'}</p>
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

    </script>

@push('scripts')
    <script>
        window.addEventListener('load', function () {
            if (window.flatpickr && window.flatpickr.l10ns && window.flatpickr.l10ns.es) {
                window.flatpickr.localize(window.flatpickr.l10ns.es);
            }
            initAgenda();
        });
    </script>
@endpush
@endsection
