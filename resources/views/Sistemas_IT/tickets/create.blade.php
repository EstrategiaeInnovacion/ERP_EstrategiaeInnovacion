@extends('Sistemas_IT.layouts.master')

@section('title', 'Nuevo Ticket')

{{-- 1. ESTILOS --}}
@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
<style>
    /* Estilos del Calendario */
    .flatpickr-calendar.inline { 
        width: 100% !important; max-width: 100% !important; 
        box-shadow: none !important; border: none !important;
        margin: 0 !important; top: 0 !important; background: transparent !important;
    }
    .flatpickr-innerContainer, .flatpickr-rContainer, .flatpickr-days { width: 100% !important; }
    
    .flatpickr-days { 
        background: white; border: 1px solid #e2e8f0; border-radius: 0 0 1rem 1rem; 
    }

    .dayContainer {
        width: 100% !important; min-width: 100% !important; max-width: 100% !important; 
        padding: 5px 0;
    }

    .flatpickr-day {
        border-radius: 0.5rem !important; height: 38px !important; line-height: 38px !important;
        margin: 0 !important; width: 14.28% !important; max-width: 14.28% !important;
        color: #334155 !important; font-weight: 500 !important;
    }

    /* Días Inhábiles */
    .flatpickr-day.flatpickr-disabled, .flatpickr-day.flatpickr-disabled:hover {
        color: #cbd5e1 !important; background: transparent !important; border-color: transparent !important; cursor: not-allowed !important;
    }

    /* Día Seleccionado */
    .flatpickr-day.selected, .flatpickr-day.selected:hover { 
        background: #10b981 !important; border-color: #10b981 !important; color: white !important; font-weight: bold !important;
    }

    .flatpickr-months { 
        background: #f8fafc; border-radius: 1rem 1rem 0 0; border: 1px solid #e2e8f0; border-bottom: none; padding: 15px 10px;
    }
    
    .flatpickr-weekdays { 
        background: white; border-left: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; height: 36px !important;
    }
    
    span.flatpickr-weekday { color: #64748b !important; font-weight: 700 !important; font-size: 0.8rem !important; }
    .flatpickr-prev-month, .flatpickr-next-month { fill: #64748b !important; }
</style>
@endpush

@section('content')
@php
    $tipo = request('tipo', 'general');
    $config = match($tipo) {
        'software' => ['color' => 'indigo', 'titulo' => 'Soporte de Software', 'desc' => 'Problemas con programas, licencias, correo o acceso al ERP.', 'icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4', 'gradient' => 'from-indigo-500 to-purple-600'],
        'hardware' => ['color' => 'slate', 'titulo' => 'Falla de Hardware', 'desc' => 'Problemas físicos: monitor, teclado, impresora o red.', 'icon' => 'M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z', 'gradient' => 'from-slate-600 to-slate-800'],
        'mantenimiento' => ['color' => 'emerald', 'titulo' => 'Mantenimiento Preventivo', 'desc' => 'Solicitud de limpieza de equipos o revisión programada.', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z', 'gradient' => 'from-emerald-500 to-teal-600'],
        default => ['color' => 'blue', 'titulo' => 'Crear Nuevo Ticket', 'desc' => 'Describe tu solicitud para el departamento de sistemas.', 'icon' => 'M12 4v16m8-8H4', 'gradient' => 'from-blue-500 to-blue-600']
    };
    $c = $config['color'];
@endphp

<div class="min-h-screen bg-slate-50 pb-12">
    <div class="max-w-4xl mx-auto px-4 sm:px-6">
        
        <div class="py-6">
            <a href="{{ route('welcome', ['from' => 'tickets']) }}" class="inline-flex items-center text-sm font-medium text-slate-500 hover:text-{{ $c }}-600 transition-colors">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                Volver al Menú
            </a>
        </div>

        <div class="bg-white rounded-[2rem] shadow-xl shadow-slate-200 border border-slate-100 overflow-hidden relative">
            
            {{-- Header con Gradiente --}}
            <div class="relative bg-gradient-to-r {{ $config['gradient'] }} p-8 sm:p-10 text-white overflow-hidden">
                <div class="absolute right-0 top-0 -mt-4 -mr-4 text-white opacity-10 transform rotate-12">
                    <svg class="w-64 h-64" fill="currentColor" viewBox="0 0 24 24"><path d="{{ $config['icon'] }}"></path></svg>
                </div>
                <div class="relative z-10 flex items-center gap-6">
                    <div class="w-20 h-20 rounded-2xl bg-white/20 backdrop-blur-sm border border-white/30 flex items-center justify-center text-white shadow-lg">
                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $config['icon'] }}"></path></svg>
                    </div>
                    <div>
                        <div class="inline-flex items-center px-3 py-1 rounded-full bg-white/20 text-xs font-bold uppercase tracking-wider mb-2 border border-white/20 backdrop-blur-md">
                            Nueva Solicitud
                        </div>
                        <h1 class="text-3xl font-bold tracking-tight text-white">{{ $config['titulo'] }}</h1>
                        <p class="text-indigo-100 mt-1 text-lg font-medium opacity-90">{{ $config['desc'] }}</p>
                    </div>
                </div>
            </div>

            <div class="p-8 sm:p-10">
                <form action="{{ route('tickets.store') }}" method="POST" enctype="multipart/form-data" class="space-y-8" id="ticketForm">
                    @csrf
                    <input type="hidden" name="tipo_problema" value="{{ $tipo }}">

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                        
                        <div class="col-span-2">
                            <label for="nombre_programa" class="block text-sm font-bold text-slate-700 mb-2">Asunto Breve</label>
                            <input type="text" name="{{ $tipo == 'software' ? 'otro_programa_nombre' : 'nombre_programa' }}" id="titulo" required 
                                class="w-full rounded-2xl border-slate-200 bg-slate-50 focus:border-{{ $c }}-500 focus:ring-{{ $c }}-500 focus:bg-white transition-all py-3 px-4 shadow-sm placeholder:text-slate-400 font-medium"
                                placeholder="Ej: {{ $tipo == 'hardware' ? 'El monitor parpadea' : ($tipo == 'software' ? 'Outlook no conecta' : 'Limpieza preventiva') }}">
                            @if($tipo == 'software') <input type="hidden" name="nombre_programa" value="Otro"> @endif
                        </div>

                        {{-- Solo mostrar Prioridad si NO es mantenimiento --}}
                        @if($tipo != 'mantenimiento')
                        <div class="col-span-2 md:col-span-1">
                            <label for="prioridad" class="block text-sm font-bold text-slate-700 mb-2">Nivel de Impacto</label>
                            <div class="relative">
                                <select name="prioridad" id="prioridad" class="w-full rounded-2xl border-slate-200 bg-slate-50 focus:border-{{ $c }}-500 focus:ring-{{ $c }}-500 focus:bg-white transition-all py-3 px-4 shadow-sm appearance-none font-medium text-slate-600 cursor-pointer">
                                    <option value="Baja">🟢 Baja (No urge)</option>
                                    <option value="Media" selected>🔵 Media (Afecta rendimiento)</option>
                                    <option value="Alta">🟠 Alta (No puedo trabajar)</option>
                                    <option value="Critica">🔴 Crítica (Sistema caído)</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center px-4 text-slate-500">
                                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l4-4 4 4m0 6l-4 4-4-4"></path></svg>
                                </div>
                            </div>
                        </div>
                        @endif

                        @if($tipo == 'mantenimiento')
                            <div class="col-span-2">
                                <label class="block text-sm font-bold text-slate-700 mb-2">Agendar Cita de Mantenimiento</label>
                                <div class="bg-slate-50 border border-slate-200 rounded-3xl p-6">
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                                        
                                        <div>
                                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 text-center">1. Elige Fecha</p>
                                            <div id="calendar-inline"></div>
                                        </div>

                                        <div>
                                            <p class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-3 text-center">2. Elige Hora</p>
                                            
                                            <input type="hidden" name="fecha_requerida" id="fecha_requerida_input" required>
                                            <input type="hidden" name="hora_requerida" id="hora_requerida_input" required>
                                            {{-- Para compatibilidad con tu validación de backend si usas slots ID --}}
                                            <input type="hidden" name="maintenance_slot_id" id="maintenance_slot_id" value="1"> 

                                            <div id="time-slots-container" class="grid grid-cols-3 gap-2 max-h-[320px] overflow-y-auto pr-1">
                                                <div class="col-span-3 text-center py-10">
                                                    <div class="inline-flex p-3 bg-white rounded-full text-slate-300 mb-2">
                                                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                                    </div>
                                                    <p class="text-sm text-slate-400 italic">Selecciona un día en el calendario</p>
                                                </div>
                                            </div>
                                            
                                            <div class="mt-4 p-3 bg-emerald-50 rounded-xl border border-emerald-100 hidden" id="selection-summary">
                                                <div class="flex items-center gap-3">
                                                    <div class="p-2 bg-white rounded-lg text-emerald-600 shadow-sm">
                                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                    </div>
                                                    <div>
                                                        <p class="text-xs text-emerald-800 font-bold">Reserva Confirmada:</p>
                                                        <p class="text-sm font-bold text-slate-800" id="selected-datetime-text">--</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="col-span-2">
                            <label for="descripcion_problema" class="block text-sm font-bold text-slate-700 mb-2">Detalles</label>
                            <textarea name="descripcion_problema" id="descripcion_problema" rows="4" required
                                class="w-full rounded-2xl border-slate-200 bg-slate-50 focus:border-{{ $c }}-500 focus:ring-{{ $c }}-500 focus:bg-white transition-all py-3 px-4 shadow-sm placeholder:text-slate-400 resize-none font-medium leading-relaxed"
                                placeholder="Describe el problema o requerimiento..."></textarea>
                        </div>

                        <div class="col-span-2">
                            <label class="block text-sm font-bold text-slate-700 mb-2">Adjuntar Imágenes (Opcional)</label>
                            <div id="dropzone" class="border-2 border-dashed border-slate-300 rounded-2xl p-6 flex flex-col items-center justify-center text-center hover:bg-{{ $c }}-50/50 hover:border-{{ $c }}-300 transition-all group cursor-pointer">
                                <div class="p-2 bg-slate-100 text-slate-400 rounded-full mb-2 group-hover:bg-white group-hover:text-{{ $c }}-500 group-hover:shadow-sm transition-all">
                                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>
                                <label for="imagenes" class="cursor-pointer text-sm font-bold text-{{ $c }}-600 hover:underline">
                                    <span>Seleccionar imágenes</span>
                                    <input id="imagenes" name="imagenes[]" type="file" multiple accept="image/*" class="sr-only">
                                </label>
                                <p class="text-xs text-slate-400 mt-1">Máximo 10 imágenes, 5MB cada una. (JPG, PNG, GIF)</p>
                            </div>
                            {{-- Barra de progreso de subida --}}
                            <div id="upload-progress" class="hidden mt-3">
                                <div class="flex items-center justify-between text-xs text-slate-600 mb-1">
                                    <span>Procesando imágenes...</span>
                                    <span id="progress-text">0/0</span>
                                </div>
                                <div class="w-full bg-slate-200 rounded-full h-2">
                                    <div id="progress-bar" class="bg-{{ $c }}-600 h-2 rounded-full transition-all duration-300" style="width: 0%"></div>
                                </div>
                            </div>
                            <div id="preview-container" class="grid grid-cols-2 sm:grid-cols-5 gap-3 mt-3" style="display:none;"></div>
                            <p id="file-count" class="text-xs text-slate-500 mt-2 hidden"></p>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-4 pt-6 border-t border-slate-100">
                        <a href="{{ route('welcome', ['from' => 'tickets']) }}" class="px-6 py-3 text-sm font-bold text-slate-500 hover:text-slate-800 transition-colors">Cancelar</a>
                        <button type="submit" class="inline-flex items-center px-8 py-3.5 bg-{{ $c }}-600 border border-transparent rounded-2xl font-bold text-sm text-white uppercase tracking-wider hover:bg-{{ $c }}-700 focus:outline-none focus:ring-4 focus:ring-{{ $c }}-100 transition-all shadow-lg shadow-{{ $c }}-200 hover:-translate-y-0.5">
                            Enviar Solicitud
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const input = document.getElementById('imagenes');
    const previewContainer = document.getElementById('preview-container');
    const fileCount = document.getElementById('file-count');
    const dropzone = document.getElementById('dropzone');
    const form = document.getElementById('ticketForm');
    const progressContainer = document.getElementById('upload-progress');
    const progressBar = document.getElementById('progress-bar');
    const progressText = document.getElementById('progress-text');
    const MAX_FILES = 10;
    const MAX_SIZE_MB = 5;

    let selectedFiles = [];

    // Click en dropzone abre el selector
    dropzone.addEventListener('click', function(e) {
        if (e.target.closest('label')) return;
        input.click();
    });

    // Drag & drop
    dropzone.addEventListener('dragover', function(e) {
        e.preventDefault();
        dropzone.classList.add('border-blue-400', 'bg-blue-50/50');
    });
    dropzone.addEventListener('dragleave', function() {
        dropzone.classList.remove('border-blue-400', 'bg-blue-50/50');
    });
    dropzone.addEventListener('drop', function(e) {
        e.preventDefault();
        dropzone.classList.remove('border-blue-400', 'bg-blue-50/50');
        addFiles(e.dataTransfer.files);
    });

    input.addEventListener('change', function() {
        addFiles(this.files);
        this.value = '';
    });

    function addFiles(fileList) {
        const filesToProcess = Array.from(fileList);
        let processed = 0;
        let totalToProcess = filesToProcess.length;
        
        // Mostrar barra de progreso
        if (totalToProcess > 0) {
            progressContainer.classList.remove('hidden');
            progressBar.style.width = '0%';
            progressText.textContent = '0/' + totalToProcess;
        }
        
        // Procesar archivos con animación
        filesToProcess.forEach(function(file, i) {
            setTimeout(function() {
                if (selectedFiles.length >= MAX_FILES) {
                    if (processed === 0) {
                        alert('Máximo ' + MAX_FILES + ' imágenes permitidas.');
                    }
                } else if (!file.type.startsWith('image/')) {
                    alert(file.name + ' no es una imagen válida.');
                } else if (file.size > MAX_SIZE_MB * 1024 * 1024) {
                    alert(file.name + ' excede el límite de ' + MAX_SIZE_MB + 'MB.');
                } else {
                    selectedFiles.push(file);
                }
                
                processed++;
                const percent = Math.round((processed / totalToProcess) * 100);
                progressBar.style.width = percent + '%';
                progressText.textContent = processed + '/' + totalToProcess;
                
                // Cuando termine, ocultar progreso y renderizar
                if (processed >= totalToProcess) {
                    setTimeout(function() {
                        progressContainer.classList.add('hidden');
                        renderPreviews();
                    }, 300);
                }
            }, i * 100); // Pequeño delay entre cada archivo para efecto visual
        });
        
        // Si no hay archivos, renderizar inmediatamente
        if (totalToProcess === 0) {
            renderPreviews();
        }
    }

    function renderPreviews() {
        previewContainer.innerHTML = '';
        if (selectedFiles.length === 0) {
            previewContainer.style.display = 'none';
            fileCount.classList.add('hidden');
            return;
        }
        previewContainer.style.display = 'grid';
        fileCount.classList.remove('hidden');
        fileCount.textContent = selectedFiles.length + ' de ' + MAX_FILES + ' imágenes seleccionadas';

        selectedFiles.forEach(function(file, index) {
            const card = document.createElement('div');
            card.className = 'relative group rounded-xl overflow-hidden border border-slate-200 shadow-sm bg-white animate-fadeIn';
            card.style.animation = 'fadeIn 0.3s ease-in-out';

            const img = document.createElement('img');
            img.className = 'w-full h-24 object-cover bg-slate-100';
            img.alt = file.name;
            
            // Placeholder mientras carga
            const placeholder = document.createElement('div');
            placeholder.className = 'w-full h-24 bg-slate-100 flex items-center justify-center';
            placeholder.innerHTML = '<svg class="w-6 h-6 text-slate-300 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>';
            card.appendChild(placeholder);
            
            const reader = new FileReader();
            reader.onload = function(e) { 
                img.src = e.target.result;
                card.replaceChild(img, placeholder);
            };
            reader.readAsDataURL(file);

            const info = document.createElement('div');
            info.className = 'px-2 py-1.5';
            const sizeKB = (file.size / 1024).toFixed(0);
            const sizeText = sizeKB > 1024 ? (file.size / 1024 / 1024).toFixed(1) + ' MB' : sizeKB + ' KB';
            info.innerHTML = '<p class="text-[10px] text-slate-600 font-medium truncate" title="' + file.name + '">' + file.name + '</p><p class="text-[9px] text-slate-400">' + sizeText + '</p>';

            const removeBtn = document.createElement('button');
            removeBtn.type = 'button';
            removeBtn.className = 'absolute top-1 right-1 w-5 h-5 bg-red-500 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity shadow-lg hover:bg-red-600';
            removeBtn.innerHTML = '<svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
            removeBtn.addEventListener('click', function() {
                selectedFiles.splice(index, 1);
                renderPreviews();
            });

            card.appendChild(info);
            card.appendChild(removeBtn);
            previewContainer.appendChild(card);
        });
    }

    // Antes de enviar, inyectar archivos en un nuevo input
    form.addEventListener('submit', function() {
        // Limpiar inputs anteriores
        form.querySelectorAll('input[name="imagenes[]"][data-injected]').forEach(function(el) { el.remove(); });

        const dt = new DataTransfer();
        selectedFiles.forEach(function(file) { dt.items.add(file); });
        input.files = dt.files;
    });
});
</script>
<style>
@keyframes fadeIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
</style>
@endpush

@endsection

{{-- 2. LÓGICA REFORZADA DEL CALENDARIO --}}
@if($tipo == 'mantenimiento')
    @push('scripts')
    <script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
    <script src="https://cdn.jsdelivr.net/npm/flatpickr/dist/l10n/es.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // ============================================
            // 1. OBTENER FECHA DEL SERVIDOR (PHP -> JS)
            // ============================================
            @if(isset($serverTime))
                const serverDateStr = "{{ $serverTime->format('Y-m-d') }}";
                const currentServerHour = {{ $serverTime->hour }};
                const currentServerMinute = {{ $serverTime->minute }};
            @else
                const now = new Date();
                const serverDateStr = now.toISOString().split('T')[0];
                const currentServerHour = now.getHours();
                const currentServerMinute = now.getMinutes();
            @endif

            // Nuevos slots predefinidos: 9am a 4pm (7 slots de 1 hora cada uno)
            const timeSlots = [
                { start: '09:00', end: '10:00', label: '09:00 AM' },
                { start: '10:00', end: '11:00', label: '10:00 AM' },
                { start: '11:00', end: '12:00', label: '11:00 AM' },
                { start: '12:00', end: '13:00', label: '12:00 PM' },
                { start: '13:00', end: '14:00', label: '01:00 PM' },
                { start: '14:00', end: '15:00', label: '02:00 PM' },
                { start: '15:00', end: '16:00', label: '03:00 PM' }
            ];

            // Cache de disponibilidad por fecha
            const availabilityCache = {};
            
            // URLs de API
            const apiSlotsUrl = "{{ route('maintenance.slots') }}";
            const apiCheckUrl = "{{ route('maintenance.check-availability') }}";

            const dateInput = document.getElementById('fecha_requerida_input');
            const timeInput = document.getElementById('hora_requerida_input');
            const slotsContainer = document.getElementById('time-slots-container');
            const summaryBox = document.getElementById('selection-summary');
            const summaryText = document.getElementById('selected-datetime-text');

            flatpickr("#calendar-inline", {
                inline: true,
                locale: "es",
                minDate: serverDateStr,
                defaultDate: serverDateStr,
                dateFormat: "Y-m-d",
                disable: [
                    function(date) {
                        return (date.getDay() === 0 || date.getDay() === 6);
                    }
                ],
                onChange: function(selectedDates, dateStr) {
                    dateInput.value = dateStr;
                    loadSlotsForDate(dateStr);
                    timeInput.value = '';
                    summaryBox.classList.add('hidden');
                }
            });

            // Cargar slots iniciales
            loadSlotsForDate(serverDateStr);
            dateInput.value = serverDateStr;

            async function loadSlotsForDate(dateStr) {
                // Mostrar loading
                slotsContainer.innerHTML = `
                    <div class="col-span-3 py-6 text-center">
                        <svg class="animate-spin h-6 w-6 mx-auto text-emerald-500" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <p class="text-sm text-slate-500 mt-2">Cargando disponibilidad...</p>
                    </div>
                `;

                try {
                    // Obtener disponibilidad de la API
                    const response = await fetch(`${apiSlotsUrl}?date=${dateStr}`, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    
                    if (!response.ok) {
                        throw new Error('HTTP ' + response.status);
                    }
                    
                    const data = await response.json();
                    
                    // Guardar en cache
                    availabilityCache[dateStr] = data.slots || [];
                    
                    generateTimeSlots(dateStr, data.slots || []);
                } catch (error) {
                    console.error('Error cargando slots:', error);
                    slotsContainer.innerHTML = `
                        <div class="col-span-3 py-6 text-center text-red-400 text-sm">
                            Error al cargar disponibilidad. <button type="button" onclick="loadSlotsForDate('${dateStr}')" class="underline">Reintentar</button>
                        </div>
                    `;
                }
            }

            function generateTimeSlots(dateStr, apiSlots) {
                slotsContainer.innerHTML = '';
                
                const isToday = (dateStr === serverDateStr);
                let availableCount = 0;

                // Crear un mapa de disponibilidad desde la API
                const slotMap = {};
                apiSlots.forEach(s => {
                    slotMap[s.start] = s;
                });

                timeSlots.forEach(slot => {
                    const apiSlot = slotMap[slot.start];
                    let isBooked = apiSlot ? apiSlot.is_booked : false;
                    let isBlocked = apiSlot ? apiSlot.is_blocked : false;
                    let isPast = apiSlot ? apiSlot.is_past : false;

                    const isDisabled = isBooked || isPast || isBlocked;
                    if (!isDisabled) availableCount++;

                    const btn = document.createElement('button');
                    btn.type = 'button';
                    btn.dataset.slot = slot.start;
                    btn.className = `
                        relative w-full py-3 px-2 rounded-xl border text-sm font-bold transition-all
                        flex flex-col items-center justify-center gap-1
                        ${isDisabled 
                            ? 'bg-slate-50 border-slate-100 text-slate-300 cursor-not-allowed opacity-60' 
                            : 'bg-white border-slate-200 text-slate-600 hover:border-emerald-500 hover:bg-emerald-50 hover:text-emerald-700 hover:shadow-md'
                        }
                    `;
                    
                    if (isDisabled) {
                        btn.disabled = true;
                        let statusText = 'Ocupado';
                        let statusColor = 'text-red-300';
                        if (isPast) {
                            statusText = 'Pasado';
                            statusColor = 'text-slate-300';
                        } else if (isBlocked) {
                            statusText = 'No disponible';
                            statusColor = 'text-orange-300';
                        }
                        btn.innerHTML = `
                            <span class="line-through">${slot.label}</span>
                            <span class="text-[9px] uppercase font-bold ${statusColor}">${statusText}</span>
                        `;
                    } else {
                        btn.innerHTML = `
                            <span>${slot.label}</span>
                            <span class="text-[9px] text-slate-400 font-normal">1 hora</span>
                        `;
                        
                        btn.onclick = async function() {
                            // Verificar disponibilidad en tiempo real antes de seleccionar
                            btn.disabled = true;
                            btn.innerHTML = `
                                <svg class="animate-spin h-4 w-4 text-emerald-500" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                                </svg>
                            `;

                            try {
                                const checkResponse = await fetch(`${apiCheckUrl}?date=${dateStr}&time=${slot.start}`, {
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest'
                                    }
                                });
                                
                                if (!checkResponse.ok) {
                                    throw new Error('HTTP ' + checkResponse.status);
                                }
                                
                                const checkData = await checkResponse.json();

                                if (!checkData.available) {
                                    // Ya no está disponible - recargar slots
                                    alert('Este horario acaba de ser ocupado. Se actualizará la disponibilidad.');
                                    loadSlotsForDate(dateStr);
                                    return;
                                }

                                // Está disponible - seleccionar
                                document.querySelectorAll('#time-slots-container button').forEach(b => {
                                    if(!b.disabled) {
                                        b.className = b.className.replace('ring-2 ring-emerald-500 bg-emerald-50 border-emerald-500 text-emerald-700', 'bg-white border-slate-200 text-slate-600');
                                    }
                                });
                                
                                btn.className = `
                                    relative w-full py-3 px-2 rounded-xl border text-sm font-bold transition-all
                                    flex flex-col items-center justify-center gap-1
                                    ring-2 ring-emerald-500 bg-emerald-50 border-emerald-500 text-emerald-700
                                `;
                                btn.innerHTML = `
                                    <span>${slot.label}</span>
                                    <span class="text-[9px] text-emerald-500 font-normal">✓ Seleccionado</span>
                                `;
                                btn.disabled = false;
                                
                                timeInput.value = slot.start;
                                summaryText.textContent = `${formatDate(dateStr)} • ${slot.label}`;
                                summaryBox.classList.remove('hidden');
                            } catch (error) {
                                console.error('Error verificando disponibilidad:', error);
                                btn.disabled = false;
                                btn.innerHTML = `
                                    <span>${slot.label}</span>
                                    <span class="text-[9px] text-slate-400 font-normal">1 hora</span>
                                `;
                            }
                        };
                    }
                    slotsContainer.appendChild(btn);
                });

                if (availableCount === 0) {
                    slotsContainer.innerHTML = `
                        <div class="col-span-3 py-6 text-center text-slate-400 text-sm italic bg-white rounded-xl border border-dashed border-slate-200">
                            <svg class="w-8 h-8 mx-auto text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            No hay horarios disponibles para esta fecha.<br>
                            <span class="text-xs">Intenta seleccionar otro día.</span>
                        </div>`;
                }
            }

            function formatDate(dateString) {
                const parts = dateString.split('-');
                const date = new Date(parts[0], parts[1] - 1, parts[2]); 
                const options = { weekday: 'long', day: 'numeric', month: 'short' };
                return date.toLocaleDateString('es-ES', options);
            }

            // Refrescar disponibilidad cada 30 segundos si la pestaña está visible
            let refreshInterval;
            function startAutoRefresh() {
                refreshInterval = setInterval(() => {
                    if (document.visibilityState === 'visible' && dateInput.value) {
                        loadSlotsForDate(dateInput.value);
                    }
                }, 30000);
            }
            startAutoRefresh();

            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible' && dateInput.value) {
                    loadSlotsForDate(dateInput.value);
                }
            });
        });
    </script>
    @endpush
@endif