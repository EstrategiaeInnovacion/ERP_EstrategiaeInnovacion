@extends('layouts.erp')

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
});
</script>
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
</style>
@endpush

@section('content')
<div class="min-h-screen bg-slate-50 pb-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-xl font-bold text-slate-900">Calendario de Recordatorios</h3>
                    <p class="text-slate-500 text-sm mt-1">Vista mensual de todos los eventos y recordatorios programados</p>
                </div>
                <a href="{{ route('rh.recordatorios.index') }}" class="inline-flex items-center text-sm text-slate-600 hover:text-indigo-600 transition-colors">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                    </svg>
                    Vista lista
                </a>
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
    </div>
</div>
@endsection
