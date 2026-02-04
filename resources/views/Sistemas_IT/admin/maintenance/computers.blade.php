@extends('layouts.master')

@section('title', 'Expedientes de Equipos - Panel Administrativo')

@section('content')
        <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
            <div class="mb-8">
                <h2 class="text-3xl font-bold text-gray-900">Historial de equipos</h2>
                <p class="text-gray-600">Mantén un registro centralizado de los equipos intervenidos y su estado de préstamo.</p>
            </div>

            @if(session('success'))
                <div class="bg-green-50 border border-green-200 rounded-lg p-4 mb-8">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-500 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span class="text-sm text-green-800 font-medium">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            <div class="bg-white border border-blue-100 rounded-xl shadow-sm">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h3 class="text-lg font-semibold text-gray-900">Equipos registrados ({{ $profiles->count() }})</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Identificador</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Equipo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Último mantenimiento</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Próximo mantenimiento</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Préstamo</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            @forelse($profiles as $profile)
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-semibold text-blue-600">{{ $profile->identifier ?? 'Sin asignar' }}</div>
                                        @if($profile->last_ticket_id)
                                            <div class="text-xs text-gray-500">Ticket: <a href="{{ route('admin.tickets.show', $profile->last_ticket_id) }}" class="text-blue-600 hover:text-blue-800">{{ optional($profile->ticket)->folio ?? $profile->last_ticket_id }}</a></div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-semibold text-gray-900">{{ $profile->brand ?? 'Marca no definida' }} {{ $profile->model }}</div>
                                        @if($profile->disk_type || $profile->ram_capacity)
                                            <div class="text-xs text-gray-500 mt-1 flex flex-col space-y-1">
                                                @if($profile->disk_type)
                                                    <span>Disco: {{ $profile->disk_type }}</span>
                                                @endif
                                                @if($profile->ram_capacity)
                                                    <span>RAM: {{ $profile->ram_capacity }}</span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    @php
                                        $lastMaintenance = $profile->last_maintenance_at
                                            ? $profile->last_maintenance_at->copy()->timezone('America/Mexico_City')
                                            : null;
                                        $nextMaintenance = $lastMaintenance ? $lastMaintenance->copy()->addMonths(4) : null;
                                    @endphp
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        @if($lastMaintenance)
                                            {{ $lastMaintenance->format('d/m/Y H:i') }}
                                        @else
                                            <span class="text-gray-400">Sin registro</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        @if($nextMaintenance)
                                            {{ $nextMaintenance->format('d/m/Y H:i') }}
                                        @else
                                            <span class="text-gray-400">Pendiente</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <div class="font-medium {{ $profile->is_loaned ? 'text-green-600' : 'text-gray-500' }}">
                                            {{ $profile->is_loaned ? 'Prestado' : 'Disponible' }}
                                        </div>
                                        @if($profile->is_loaned)
                                            <div class="text-xs text-gray-500 mt-1">
                                                {{ $profile->loaned_to_name }}<br>
                                                {{ $profile->loaned_to_email }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600">
                                        <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-2 space-y-2 sm:space-y-0">
                                            <a href="{{ route('admin.maintenance.computers.edit', $profile) }}" class="inline-flex items-center px-3 py-2 bg-blue-50 text-blue-700 hover:bg-blue-100 border border-blue-200 rounded-lg text-xs font-semibold transition-colors">Editar</a>
                                            <a href="{{ route('admin.maintenance.computers.show', $profile) }}" class="inline-flex items-center px-3 py-2 bg-gray-50 text-gray-700 hover:bg-gray-100 border border-gray-200 rounded-lg text-xs font-semibold transition-colors">Ver detalles</a>
                                            <form method="POST" action="{{ route('admin.maintenance.computers.destroy', $profile) }}" onsubmit="return confirm('¿Deseas eliminar esta ficha técnica? Esta acción no se puede deshacer.');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="inline-flex items-center px-3 py-2 bg-red-50 text-red-600 hover:bg-red-100 border border-red-200 rounded-lg text-xs font-semibold transition-colors">Borrar ficha técnica</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-10 text-center text-gray-500">
                                        No hay expedientes registrados aún.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
@endsection
