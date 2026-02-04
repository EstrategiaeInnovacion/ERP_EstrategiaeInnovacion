@extends('layouts.master')

@section('title', 'Agenda de Mantenimientos - Panel Administrativo')

@section('content')
<main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900">Agenda de Mantenimientos</h1>
        <p class="mt-2 text-gray-600 text-sm">Configura horarios disponibles y consulta los últimos tickets de mantenimiento registrados.</p>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Horarios programados -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white shadow rounded-lg border border-gray-200 p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Horarios Programados</h2>
                @forelse($groupedSlots as $date => $slots)
                    <div class="mb-5">
                        <h3 class="text-sm font-semibold text-blue-700 mb-2">{{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}</h3>
                        <div class="space-y-2">
                            @foreach($slots as $slot)
                                <div class="flex items-center justify-between text-sm bg-blue-50 border border-blue-100 rounded px-3 py-2">
                                    <span class="font-medium text-blue-900">{{ \Carbon\Carbon::parse($slot->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($slot->end_time)->format('H:i') }}</span>
                                    <span class="text-xs {{ $slot->available_capacity > 0 ? 'text-green-600' : 'text-red-600' }}">Disponibilidad: {{ $slot->available_capacity }} / {{ $slot->capacity }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-500">No hay horarios de mantenimiento programados.</p>
                @endforelse
            </div>

            <div class="bg-white shadow rounded-lg border border-gray-200 p-6">
                <h2 class="text-xl font-semibold text-gray-900 mb-4">Últimos Tickets de Mantenimiento</h2>
                <div class="space-y-4">
                    @forelse($maintenanceTickets as $ticket)
                        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ $ticket->folio }}</p>
                                    <p class="text-xs text-gray-600">{{ $ticket->nombre_solicitante }} · {{ $ticket->correo_solicitante }}</p>
                                </div>
                                <a href="{{ route('admin.tickets.show', $ticket) }}" class="text-xs font-medium text-blue-600 hover:text-blue-700">Ver</a>
                            </div>
                            <div class="mt-2 text-xs text-gray-600">
                                @if($ticket->maintenanceSlot)
                                    <p>Programado: {{ $ticket->maintenanceSlot->date->format('d/m/Y') }} {{ \Carbon\Carbon::parse($ticket->maintenanceSlot->start_time)->format('H:i') }}-{{ \Carbon\Carbon::parse($ticket->maintenanceSlot->end_time)->format('H:i') }}</p>
                                @else
                                    <p>Sin horario asignado</p>
                                @endif
                                @if($ticket->computerProfile)
                                    <p class="mt-1">Equipo: <a href="{{ route('admin.maintenance.computers.show', $ticket->computerProfile) }}" class="underline text-gray-800">{{ $ticket->computerProfile->identifier }}</a></p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-gray-500">No hay tickets de mantenimiento recientes.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Panel lateral -->
        <div class="space-y-6">
            <div class="bg-white shadow rounded-lg border border-gray-200 p-6">
                <h2 class="text-lg font-semibold text-gray-900 mb-3">Componentes de Reemplazo</h2>
                <ul class="text-sm text-gray-700 space-y-1">
                    @foreach($componentOptions as $key => $label)
                        <li class="flex items-center">
                            <span class="w-2 h-2 bg-blue-500 rounded-full mr-2"></span>{{ $label }}
                        </li>
                    @endforeach
                </ul>
            </div>
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-5 text-sm text-blue-900">
                <p class="font-semibold mb-1">Nota</p>
                <p>Esta sección es provisional. Más herramientas de gestión (crear, editar y desactivar horarios) pueden añadirse posteriormente.</p>
            </div>
        </div>
    </div>
</main>
@endsection
