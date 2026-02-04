@extends('layouts.master')

@section('title', 'Ficha de Equipo - Mantenimiento')

@section('content')
<main class="max-w-5xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-2xl font-bold text-gray-900">Ficha de Equipo</h1>
        <p class="mt-1 text-sm text-gray-600">Información técnica y historial de mantenimientos.</p>
        <a href="{{ route('admin.maintenance.index') }}" class="inline-flex items-center mt-3 text-xs text-blue-600 hover:text-blue-700">&larr; Volver a agenda</a>
    </div>

    <div class="space-y-8">
        <div class="bg-white shadow rounded-lg border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Detalles del Equipo</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm text-gray-700">
                <div>
                    <p class="text-xs uppercase text-gray-500">Identificador</p>
                    <p class="font-medium">{{ $computerProfile->identifier ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase text-gray-500">Marca</p>
                    <p class="font-medium">{{ $computerProfile->brand ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase text-gray-500">Modelo</p>
                    <p class="font-medium">{{ $computerProfile->model ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase text-gray-500">Tipo de disco</p>
                    <p class="font-medium">{{ $computerProfile->disk_type ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase text-gray-500">RAM</p>
                    <p class="font-medium">{{ $computerProfile->ram_capacity ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase text-gray-500">Estado batería</p>
                    <p class="font-medium">{{ $computerProfile->battery_status ?? '—' }}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-xs uppercase text-gray-500">Observaciones estéticas</p>
                    <p class="font-medium">{{ $computerProfile->aesthetic_observations ?? '—' }}</p>
                </div>
                <div class="md:col-span-2">
                    <p class="text-xs uppercase text-gray-500">Componentes sugeridos para reemplazo</p>
                    <div class="mt-1 flex flex-wrap gap-2">
                        @forelse(($computerProfile->replacement_components ?? []) as $comp)
                            <span class="px-2 py-1 text-xs rounded-full bg-blue-100 text-blue-700">{{ $comp }}</span>
                        @empty
                            <span class="text-gray-400 text-xs">Ninguno</span>
                        @endforelse
                    </div>
                </div>
                <div>
                    <p class="text-xs uppercase text-gray-500">Último mantenimiento</p>
                    <p class="font-medium">{{ optional($computerProfile->last_maintenance_at)->timezone('America/Mexico_City')->format('d/m/Y H:i') ?? '—' }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase text-gray-500">Préstamo</p>
                    <p class="font-medium">
                        @if($computerProfile->is_loaned)
                            Prestado a {{ $computerProfile->loaned_to_name }} ({{ $computerProfile->loaned_to_email }})
                        @else
                            No prestado
                        @endif
                    </p>
                </div>
            </div>
        </div>

        <div class="bg-white shadow rounded-lg border border-gray-200 p-6">
            <h2 class="text-lg font-semibold text-gray-900 mb-4">Historial de Tickets de Mantenimiento</h2>
            @if($tickets->isEmpty())
                <p class="text-sm text-gray-500">No hay tickets de mantenimiento asociados a este equipo.</p>
            @else
                <div class="space-y-4">
                    @foreach($tickets as $ticket)
                        <div class="border border-gray-200 rounded-lg p-4 bg-gray-50">
                            <div class="flex justify-between items-start">
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
                                @if($ticket->maintenance_report)
                                    <p class="mt-1">Reporte técnico: <span class="text-gray-800">{{ \Illuminate\Support\Str::limit($ticket->maintenance_report, 120) }}</span></p>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="bg-blue-50 border border-blue-200 rounded-lg p-5 text-xs text-blue-900">
            <p><strong>Nota:</strong> Esta vista es básica. En el futuro puede ampliarse para editar directamente la ficha técnica.</p>
        </div>
    </div>
</main>
@endsection
