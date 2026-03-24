@extends('layouts.erp')
@php use App\Models\Recordatorio; @endphp

@section('title', 'Calendario de Recordatorios')

@push('styles')
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.css">
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/index.global.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@6.1.10/locales/es.global.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
        initialView: 'dayGridMonth',
        locale: 'es',
        buttonText: {
            today: 'Hoy',
            month: 'Mes',
            list: 'Lista'
        },
        headerToolbar: {
            left: 'prev,next today',
            center: 'title',
            right: 'dayGridMonth,listWeek'
        },
        events: {!! json_encode($recordatorios) !!},
        selectable: true,
        selectMirror: true,
        dateClick: function(info) {
            document.getElementById('fecha_evento').value = info.dateStr;
            document.getElementById('modalCrearEvento').classList.remove('hidden');
        },
        eventClick: function(info) {
            info.jsEvent.preventDefault();
            if (info.event.url) {
                window.location.href = info.event.url;
            }
        },
        eventClassNames: function(arg) {
            return ['rounded-lg', 'px-2', 'py-1', 'text-xs', 'font-medium'];
        }
    });
    calendar.render();
    
    window.calendar = calendar;
});

function cerrarModal() {
    document.getElementById('modalCrearEvento').classList.add('hidden');
    document.getElementById('formCrearEvento').reset();
}

function seleccionarColor(color) {
    document.querySelectorAll('.color-option').forEach(el => el.classList.remove('ring-2', 'ring-offset-2'));
    document.querySelector(`[data-color="${color}"]`).classList.add('ring-2', 'ring-offset-2');
    document.getElementById('color_evento').value = color;
}
</script>
@endpush

<style>
.fc-event {
    cursor: pointer;
}
.fc-event.urgencia-vencido {
    background-color: #fef2f2 !important;
    border-color: #fecaca !important;
    color: #991b1b !important;
}
.fc-event.urgencia-critico {
    background-color: #fff7ed !important;
    border-color: #fed7aa !important;
    color: #9a3412 !important;
}
.fc-event.urgencia-alerta {
    background-color: #fefce8 !important;
    border-color: #fef08a !important;
    color: #854d0e !important;
}
.fc-event.urgencia-pronto {
    background-color: #eff6ff !important;
    border-color: #bfdbfe !important;
    color: #1e40af !important;
}
.fc-event.urgencia-normal {
    background-color: #ecfdf5 !important;
    border-color: #a7f3d0 !important;
    color: #166534 !important;
}
.fc-event.event-manual {
    border-width: 2px !important;
    border-style: dashed !important;
}
</style>

@section('content')
<div class="min-h-screen bg-slate-50 pb-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-slate-900">Calendario de Recordatorios</h3>
                    <p class="text-slate-500 text-sm mt-1">Vista mensual de todos los eventos. Haz clic en cualquier día para crear un evento personalizado.</p>
                </div>
                <div class="flex items-center gap-3">
                    <a href="{{ route('rh.recordatorios.index') }}" class="inline-flex items-center text-sm text-slate-600 hover:text-indigo-600 transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                        </svg>
                        Vista lista
                    </a>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
            <div id="calendar" class="text-slate-700"></div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-5 gap-4">
            <div class="bg-white rounded-xl p-4 shadow-sm border border-slate-200 flex items-center gap-3">
                <div class="w-4 h-4 rounded bg-red-100 border border-red-200"></div>
                <span class="text-sm text-slate-600">Vencido</span>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-sm border border-slate-200 flex items-center gap-3">
                <div class="w-4 h-4 rounded bg-orange-100 border border-orange-200"></div>
                <span class="text-sm text-slate-600">Crítico (≤3 días)</span>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-sm border border-slate-200 flex items-center gap-3">
                <div class="w-4 h-4 rounded bg-yellow-100 border border-yellow-200"></div>
                <span class="text-sm text-slate-600">Alerta (≤7 días)</span>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-sm border border-slate-200 flex items-center gap-3">
                <div class="w-4 h-4 rounded bg-blue-100 border border-blue-200"></div>
                <span class="text-sm text-slate-600">Pronto (≤15 días)</span>
            </div>
            <div class="bg-white rounded-xl p-4 shadow-sm border border-slate-200 flex items-center gap-3">
                <div class="w-4 h-4 rounded bg-emerald-100 border border-emerald-200"></div>
                <span class="text-sm text-slate-600">Normal</span>
            </div>
        </div>

        <div class="bg-white rounded-xl p-4 shadow-sm border border-slate-200">
            <div class="flex items-center gap-4 text-sm text-slate-600">
                <span class="text-xs font-medium text-slate-500">Indicadores:</span>
                <div class="flex items-center gap-1">
                    <div class="w-3 h-3 rounded bg-purple-500"></div>
                    <span>Evento manual</span>
                </div>
                <div class="flex items-center gap-1">
                    <div class="w-3 h-3 rounded border-2 border-dashed border-slate-400"></div>
                    <span>Personalizable</span>
                </div>
            </div>
        </div>
    </div>
</div>

<div id="modalCrearEvento" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-slate-900/50" onclick="cerrarModal()"></div>
    <div class="absolute top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2 w-full max-w-md">
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden">
            <div class="bg-gradient-to-r from-indigo-600 to-purple-600 px-6 py-4">
                <h3 class="text-lg font-bold text-white">Crear Evento Personal</h3>
                <p class="text-indigo-100 text-sm">Agrega un recordatorio al calendario</p>
            </div>
            
            <form id="formCrearEvento" action="{{ route('rh.recordatorios.crear-manual') }}" method="POST" class="p-6 space-y-4">
                @csrf
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Título *</label>
                    <input type="text" name="titulo" required 
                           class="w-full rounded-lg border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                           placeholder="Nombre del evento">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Fecha *</label>
                    <input type="date" id="fecha_evento" name="fecha_evento" required 
                           class="w-full rounded-lg border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-1">Descripción</label>
                    <textarea name="descripcion" rows="3"
                              class="w-full rounded-lg border-slate-300 text-sm focus:ring-indigo-500 focus:border-indigo-500"
                              placeholder="Detalles adicionales (opcional)"></textarea>
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">Color del evento</label>
                    <input type="hidden" id="color_evento" name="color_evento" value="#8B5CF6">
                    <div class="flex flex-wrap gap-2">
                        @foreach(Recordatorio::COLORES_EVENTO as $color => $nombre)
                        <button type="button" 
                                data-color="{{ $color }}"
                                onclick="seleccionarColor('{{ $color }}')"
                                class="color-option w-8 h-8 rounded-full ring-offset-2 transition-all hover:scale-110"
                                style="background-color: {{ $color }};"
                                title="{{ $nombre }}">
                        </button>
                        @endforeach
                    </div>
                </div>
                
                <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-200">
                    <button type="button" onclick="cerrarModal()" 
                            class="px-4 py-2 text-sm font-medium text-slate-600 hover:text-slate-800 transition-colors">
                        Cancelar
                    </button>
                    <button type="submit" 
                            class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg transition-colors">
                        Crear Evento
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
