@extends('layouts.master')

@section('title', 'Gestión de Tickets - Panel Administrativo')

@section('content')
<div class="min-h-screen bg-slate-50 pb-12">
    <div class="bg-white border-b border-slate-200 mb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Gestión de Tickets</h1>
                    <p class="text-slate-500 mt-1 text-lg">Administración y seguimiento de solicitudes de soporte.</p>
                </div>
                <div class="flex items-center gap-3">
                    <div class="px-4 py-2 bg-slate-100 rounded-xl border border-slate-200">
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total</span>
                        <span class="text-xl font-bold text-slate-900">{{ $tickets->total() }} Tickets</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-8 flex items-center p-4 bg-emerald-50 border border-emerald-200 rounded-2xl shadow-sm">
                <div class="p-2 bg-emerald-100 rounded-full text-emerald-600 mr-3">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                </div>
                <p class="text-emerald-800 font-medium">{{ session('success') }}</p>
            </div>
        @endif

        @php
            $stats = [
                ['label' => 'Abiertos', 'count' => $tickets->where('estado', 'abierto')->count(), 'color' => 'red', 'icon' => 'M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
                ['label' => 'En Proceso', 'count' => $tickets->where('estado', 'en_proceso')->count(), 'color' => 'amber', 'icon' => 'M12 6v6m0 0v6m0-6h6m-6 0H6'],
                ['label' => 'Cerrados', 'count' => $tickets->where('estado', 'cerrado')->count(), 'color' => 'emerald', 'icon' => 'M5 13l4 4L19 7'],
                ['label' => 'Software', 'count' => $tickets->where('tipo_problema', 'software')->count(), 'color' => 'indigo', 'icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z'],
                ['label' => 'Hardware', 'count' => $tickets->where('tipo_problema', 'hardware')->count(), 'color' => 'slate', 'icon' => 'M9 17v-2a1 1 0 011-1h4a1 1 0 011 1v2m3 4H6a2 2 0 01-2-2V7a2 2 0 012-2h3l2-2h2l2 2h3a2 2 0 012 2v12a2 2 0 01-2 2z'],
                ['label' => 'Mantenimiento', 'count' => $tickets->where('tipo_problema', 'mantenimiento')->count(), 'color' => 'cyan', 'icon' => 'M8 7V3a1 1 0 011-1h6a1 1 0 011 1v4h3a1 1 0 011 1v9a1 1 0 01-1 1H5a1 1 0 01-1-1V8a1 1 0 011-1h3z'],
            ];
        @endphp

        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
            @foreach($stats as $stat)
                <div class="bg-white p-4 rounded-3xl shadow-sm border border-slate-200 flex flex-col justify-between h-32 group hover:shadow-md transition-all">
                    <div class="flex justify-between items-start">
                        <div class="p-2 bg-{{ $stat['color'] }}-50 text-{{ $stat['color'] }}-600 rounded-xl group-hover:scale-110 transition-transform">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $stat['icon'] }}"></path></svg>
                        </div>
                        <span class="text-2xl font-bold text-slate-800">{{ $stat['count'] }}</span>
                    </div>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">{{ $stat['label'] }}</p>
                </div>
            @endforeach
        </div>

        <div class="flex justify-end mb-4">
             <a href="{{ route('admin.maintenance.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 text-slate-600 font-bold text-sm rounded-xl hover:bg-slate-50 hover:text-indigo-600 hover:border-indigo-200 transition shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Configurar horarios de mantenimiento
            </a>
        </div>

        <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 overflow-hidden">
            @if($tickets->isEmpty())
                 <div class="flex flex-col items-center justify-center py-20 text-center">
                    <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mb-6">
                        <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900">No hay tickets registrados</h3>
                    <p class="text-slate-500 mt-2">El sistema está limpio por ahora.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-100">
                                <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase tracking-wider">Folio / Solicitante</th>
                                <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase tracking-wider">Tipo</th>
                                <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase tracking-wider">Prioridad</th>
                                <th class="px-6 py-5 text-xs font-bold text-slate-400 uppercase tracking-wider">Fecha</th>
                                <th class="px-6 py-5 text-right text-xs font-bold text-slate-400 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($tickets as $ticket)
                                <tr class="hover:bg-slate-50/80 transition-colors group">
                                    <td class="px-6 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-indigo-600 font-mono mb-1">#{{ $ticket->folio }}</span>
                                            <span class="text-sm font-medium text-slate-900">{{ $ticket->nombre_solicitante }}</span>
                                            <span class="text-xs text-slate-400">{{ $ticket->correo_solicitante }}</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4">
                                        @php
                                            $typeColor = match($ticket->tipo_problema) {
                                                'software' => 'text-indigo-600 bg-indigo-50 border-indigo-100',
                                                'hardware' => 'text-slate-600 bg-slate-100 border-slate-200',
                                                'mantenimiento' => 'text-cyan-600 bg-cyan-50 border-cyan-100',
                                                default => 'text-slate-500'
                                            };
                                        @endphp
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-bold uppercase border {{ $typeColor }}">
                                            {{ ucfirst($ticket->tipo_problema) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-[10px] font-bold uppercase border {{ $ticket->estado_badge }}">
                                            {{ ucfirst(str_replace('_', ' ', $ticket->estado)) }}
                                        </span>
                                        @if($ticket->closed_by_user)
                                            <div class="mt-1 flex items-center text-red-500 text-[10px] font-bold uppercase tracking-wide">
                                                <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                Cancelado por usuario
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        @if($ticket->prioridad)
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold {{ $ticket->prioridad_badge }}">
                                                {{ ucfirst($ticket->prioridad) }}
                                            </span>
                                        @else
                                            <span class="text-slate-400 text-xs italic">--</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-xs font-bold text-slate-500">
                                        {{ $ticket->created_at->format('d/m/Y H:i') }}
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <div class="flex items-center justify-end gap-2">
                                            <a href="{{ route('admin.tickets.show', $ticket) }}"
                                               class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-200 hover:shadow-sm transition-all" title="Ver Detalles">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                            </a>
                                            <form method="POST" action="{{ route('tickets.destroy', $ticket) }}"
                                                  onsubmit="return confirm('¿Estás seguro de que deseas eliminar este ticket?')"
                                                  class="inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit"
                                                        class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-red-600 hover:border-red-200 hover:shadow-sm transition-all" title="Eliminar">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($tickets->hasPages())
                <div class="px-6 py-4 border-t border-slate-100">
                    {{ $tickets->links() }}
                </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection