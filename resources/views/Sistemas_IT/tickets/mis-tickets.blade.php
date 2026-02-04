@extends('Sistemas_IT.layouts.master')

@section('title', 'Mis Tickets')

@section('content')
<div class="min-h-screen bg-slate-50 pb-12">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="flex flex-col md:flex-row justify-between items-end mb-8 gap-4">
            <div>
                <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Mis Solicitudes</h1>
                <p class="text-slate-500 mt-1 text-lg">Historial y estado de tus reportes de IT.</p>
            </div>
            
            <div class="flex gap-2">
                <a href="{{ route('tickets.create', ['tipo' => 'software']) }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 text-indigo-600 font-bold text-sm rounded-xl hover:bg-indigo-50 hover:border-indigo-200 transition shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-indigo-500 mr-2"></span> Software
                </a>
                <a href="{{ route('tickets.create', ['tipo' => 'hardware']) }}" class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 text-slate-600 font-bold text-sm rounded-xl hover:bg-slate-50 hover:border-slate-300 transition shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-slate-500 mr-2"></span> Hardware
                </a>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
            <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-200 flex flex-col justify-between h-32 group hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start">
                    <div class="p-2 bg-blue-50 text-blue-600 rounded-xl group-hover:bg-blue-100 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                    </div>
                    <span class="text-3xl font-bold text-slate-800">{{ $tickets->count() }}</span>
                </div>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">Total Tickets</p>
            </div>
            
            <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-200 flex flex-col justify-between h-32 group hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start">
                    <div class="p-2 bg-emerald-50 text-emerald-600 rounded-xl group-hover:bg-emerald-100 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <span class="text-3xl font-bold text-slate-800">{{ $tickets->where('estado', 'Abierto')->count() }}</span>
                </div>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">Abiertos</p>
            </div>

            <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-200 flex flex-col justify-between h-32 group hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start">
                    <div class="p-2 bg-amber-50 text-amber-600 rounded-xl group-hover:bg-amber-100 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    </div>
                    <span class="text-3xl font-bold text-slate-800">{{ $tickets->where('estado', 'En Proceso')->count() }}</span>
                </div>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">En Proceso</p>
            </div>
            
             <div class="bg-white p-5 rounded-3xl shadow-sm border border-slate-200 flex flex-col justify-between h-32 group hover:shadow-md transition-shadow">
                <div class="flex justify-between items-start">
                    <div class="p-2 bg-slate-100 text-slate-600 rounded-xl group-hover:bg-slate-200 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </div>
                    <span class="text-3xl font-bold text-slate-800">{{ $tickets->where('estado', 'Cerrado')->count() }}</span>
                </div>
                <p class="text-xs text-slate-400 font-bold uppercase tracking-wider">Resueltos</p>
            </div>
        </div>

        <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 overflow-hidden">
            @if($tickets->isEmpty())
                <div class="flex flex-col items-center justify-center py-20 text-center">
                    <div class="w-24 h-24 bg-slate-50 rounded-full flex items-center justify-center mb-6">
                        <svg class="w-12 h-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                    </div>
                    <h3 class="text-xl font-bold text-slate-900">No tienes tickets registrados</h3>
                    <p class="text-slate-500 mt-2 max-w-sm mx-auto">
                        Afortunadamente todo parece estar funcionando bien. Si surge algo, estamos aquí.
                    </p>
                    <a href="{{ route('welcome', ['from' => 'tickets']) }}" class="mt-8 px-6 py-3 bg-indigo-600 text-white font-bold rounded-xl hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">
                        Crear mi primer reporte
                    </a>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/80 border-b border-slate-100">
                                <th class="px-8 py-5 text-xs font-bold text-slate-400 uppercase tracking-wider">Folio</th>
                                <th class="px-8 py-5 text-xs font-bold text-slate-400 uppercase tracking-wider">Detalles</th>
                                <th class="px-8 py-5 text-xs font-bold text-slate-400 uppercase tracking-wider">Estado</th>
                                <th class="px-8 py-5 text-xs font-bold text-slate-400 uppercase tracking-wider">Prioridad</th>
                                <th class="px-8 py-5 text-xs font-bold text-slate-400 uppercase tracking-wider text-right"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($tickets as $ticket)
                                <tr class="hover:bg-slate-50/80 transition-colors group">
                                    <td class="px-8 py-5 whitespace-nowrap">
                                        <span class="px-2 py-1 bg-slate-100 text-slate-500 rounded-lg font-mono text-xs font-bold">#{{ $ticket->id }}</span>
                                    </td>
                                    <td class="px-8 py-5">
                                        <div class="flex flex-col">
                                            <span class="text-sm font-bold text-slate-800 group-hover:text-indigo-600 transition-colors mb-1">
                                                {{ Str::limit($ticket->titulo, 50) }}
                                            </span>
                                            <div class="flex items-center gap-2">
                                                @php
                                                    $catColor = match(strtolower($ticket->categoria)) {
                                                        'software' => 'text-indigo-500',
                                                        'hardware' => 'text-slate-500',
                                                        'mantenimiento' => 'text-emerald-500',
                                                        default => 'text-slate-400'
                                                    };
                                                @endphp
                                                <span class="text-[10px] font-bold uppercase tracking-wide {{ $catColor }}">
                                                    {{ $ticket->categoria }}
                                                </span>
                                                <span class="text-slate-300">•</span>
                                                <span class="text-xs text-slate-400">{{ $ticket->created_at->diffForHumans() }}</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5 whitespace-nowrap">
                                        @php
                                            $estadoClasses = match($ticket->estado) {
                                                'Abierto' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                                'En Proceso' => 'bg-blue-50 text-blue-700 border-blue-100',
                                                'Esperando Respuesta' => 'bg-amber-50 text-amber-700 border-amber-100',
                                                'Cerrado' => 'bg-slate-100 text-slate-500 border-slate-200',
                                                default => 'bg-slate-100 text-slate-600'
                                            };
                                        @endphp
                                        <span class="px-3 py-1 rounded-full text-[10px] font-bold uppercase border {{ $estadoClasses }}">
                                            {{ $ticket->estado }}
                                        </span>
                                    </td>
                                    <td class="px-8 py-5 whitespace-nowrap">
                                        @php
                                            $prioConfig = match($ticket->prioridad) {
                                                'Critica' => ['🔥', 'text-red-600 bg-red-50 border-red-100'],
                                                'Alta' => ['🟠', 'text-orange-600 bg-orange-50 border-orange-100'],
                                                'Media' => ['🔵', 'text-blue-600 bg-blue-50 border-blue-100'],
                                                default => ['🟢', 'text-slate-500 bg-slate-100 border-slate-200']
                                            };
                                        @endphp
                                        <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full border {{ $prioConfig[1] }}">
                                            <span class="text-xs">{{ $prioConfig[0] }}</span>
                                            <span class="text-[10px] font-bold uppercase">{{ $ticket->prioridad }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5 whitespace-nowrap text-right">
                                        <a href="{{ route('tickets.show', $ticket->id) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-full bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-200 hover:shadow-sm transition-all" title="Ver Detalles">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                
                @if(method_exists($tickets, 'links'))
                    <div class="bg-white px-8 py-6 border-t border-slate-100">
                        {{ $tickets->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection