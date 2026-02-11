@extends('layouts.master')

@section('title', 'Ver Usuario - ' . $user->name)

@section('content')
<div class="min-h-screen bg-slate-50 pb-12">
    <div class="bg-white border-b border-slate-200 mb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Perfil de Usuario</h1>
                    <p class="text-slate-500 mt-1 text-lg">Información completa e historial de actividad</p>
                </div>
                <div class="flex gap-3">
                    <a href="{{ route('admin.users.edit', $user) }}"
                       class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white font-bold text-sm rounded-xl hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path>
                        </svg>
                        Editar Usuario
                    </a>
                    <a href="{{ route('admin.users') }}"
                       class="inline-flex items-center px-5 py-2.5 bg-white border border-slate-200 text-slate-700 font-bold text-sm rounded-xl hover:bg-slate-50 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                        </svg>
                        Volver
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        {{-- Alertas --}}
        @foreach(['success' => 'emerald', 'error' => 'red', 'info' => 'blue'] as $key => $color)
            @if(session($key))
                <div class="mb-6 flex items-center p-4 bg-{{ $color }}-50 border border-{{ $color }}-200 rounded-2xl shadow-sm">
                    <div class="p-2 bg-{{ $color }}-100 rounded-full text-{{ $color }}-600 mr-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if($key == 'success') <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            @elseif($key == 'error') <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            @else <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 18a9 9 0 110-18 9 9 0 010 18z"></path> @endif
                        </svg>
                    </div>
                    <p class="text-{{ $color }}-800 font-medium">{{ session($key) }}</p>
                </div>
            @endif
        @endforeach

        {{-- Grid Layout --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Columna izquierda: Info del usuario --}}
            <div class="lg:col-span-1 space-y-6">

                {{-- Tarjeta de perfil --}}
                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="p-6">
                        <div class="text-center">
                            <div class="w-20 h-20 bg-gradient-to-r from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-4">
                                <span class="text-2xl font-bold text-white">
                                    {{ strtoupper(substr($user->name, 0, 2)) }}
                                </span>
                            </div>

                            <h2 class="text-xl font-bold text-slate-900">{{ $user->name }}</h2>
                            <p class="text-slate-500 text-sm">{{ $user->email }}</p>

                            <div class="mt-4 flex flex-wrap justify-center gap-2">
                                @if($user->role === 'admin')
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold uppercase bg-purple-50 text-purple-700 border border-purple-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span> Administrador
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold uppercase bg-slate-100 text-slate-600 border border-slate-200">
                                        Usuario
                                    </span>
                                @endif

                                @if($user->status === 'approved')
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[10px] font-bold uppercase bg-green-50 text-green-700 border border-green-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Aprobado
                                    </span>
                                @elseif($user->status === 'pending')
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[10px] font-bold uppercase bg-yellow-50 text-yellow-700 border border-yellow-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-yellow-500 animate-pulse"></span> Pendiente
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1 px-3 py-1 rounded-full text-[10px] font-bold uppercase bg-red-50 text-red-700 border border-red-100">
                                        <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Rechazado
                                    </span>
                                @endif
                            </div>
                        </div>

                        <div class="mt-6 pt-6 border-t border-slate-100 space-y-3">
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-500">ID de Usuario:</span>
                                <span class="text-sm font-bold text-slate-900">#{{ $user->id }}</span>
                            </div>
                            @if(optional($user->empleado)->area)
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-500">Área:</span>
                                <span class="text-sm font-bold text-slate-900">{{ $user->empleado->area }}</span>
                            </div>
                            @endif
                            @if(optional($user->empleado)->posicion)
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-500">Posición:</span>
                                <span class="text-sm font-bold text-slate-900">{{ $user->empleado->posicion }}</span>
                            </div>
                            @endif
                            @if(optional($user->empleado)->id_empleado)
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-500">No. Nómina:</span>
                                <span class="text-sm font-bold text-slate-900">#{{ $user->empleado->id_empleado }}</span>
                            </div>
                            @endif
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-500">Registro:</span>
                                <span class="text-sm font-bold text-slate-900">{{ $user->created_at->format('d/m/Y') }}</span>
                            </div>
                            @if($user->approved_at)
                            <div class="flex justify-between">
                                <span class="text-sm text-slate-500">Aprobado:</span>
                                <span class="text-sm font-bold text-slate-900">{{ $user->approved_at->format('d/m/Y') }}</span>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>

                {{-- Resumen de Actividad --}}
                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 bg-slate-50/50 border-b border-slate-100">
                        <h3 class="text-sm font-bold text-slate-700 uppercase tracking-wide">Resumen de Actividad</h3>
                    </div>
                    <div class="p-6 space-y-4">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-slate-600">Total Tickets:</span>
                            <span class="font-bold text-indigo-600">{{ $stats['total_tickets'] }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-slate-600">Abiertos:</span>
                            <span class="font-bold text-red-600">{{ $stats['tickets_abiertos'] }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-slate-600">En Proceso:</span>
                            <span class="font-bold text-yellow-600">{{ $stats['tickets_en_proceso'] }}</span>
                        </div>
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-slate-600">Cerrados:</span>
                            <span class="font-bold text-green-600">{{ $stats['tickets_cerrados'] }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Columna derecha: Historial --}}
            <div class="lg:col-span-2 space-y-6">

                {{-- Historial de Tickets --}}
                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Historial de Tickets</h3>
                            <p class="text-sm text-slate-500">{{ $tickets->count() }} ticket(s) registrados.</p>
                        </div>
                    </div>

                    @if($tickets->count() > 0)
                        <div class="divide-y divide-slate-100 max-h-[500px] overflow-y-auto">
                            @foreach($tickets as $ticket)
                            <div class="px-8 py-4 hover:bg-slate-50/80 transition-colors">
                                <div class="flex justify-between items-start">
                                    <div class="flex-1">
                                        <div class="flex items-center gap-2 mb-2">
                                            <span class="text-sm font-bold text-slate-900">#{{ $ticket->id }}</span>
                                            @if($ticket->estado === 'abierto')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-red-50 text-red-700 border border-red-100">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span> Abierto
                                                </span>
                                            @elseif($ticket->estado === 'en_proceso')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-yellow-50 text-yellow-700 border border-yellow-100">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-yellow-500"></span> En Proceso
                                                </span>
                                            @else
                                                <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase bg-green-50 text-green-700 border border-green-100">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span> Cerrado
                                                </span>
                                            @endif
                                        </div>
                                        <p class="text-sm text-slate-600 mb-1">{{ Str::limit($ticket->descripcion, 100) }}</p>
                                        <div class="flex items-center gap-4 text-xs text-slate-400">
                                            <span>{{ ucfirst($ticket->tipo_problema) }}</span>
                                            <span>{{ $ticket->created_at->format('d/m/Y H:i') }}</span>
                                        </div>
                                    </div>
                                    <a href="{{ route('admin.tickets.show', $ticket) }}"
                                       class="ml-4 inline-flex items-center justify-center w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-200 hover:shadow-sm transition-all"
                                       title="Ver ticket">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                                        </svg>
                                    </a>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    @else
                        <div class="py-12 text-center">
                            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 mb-4">
                                <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                            </div>
                            <h3 class="text-lg font-bold text-slate-900">Sin tickets registrados</h3>
                            <p class="text-slate-500 mt-1">Este usuario aún no ha creado ningún ticket.</p>
                        </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</div>
@endsection