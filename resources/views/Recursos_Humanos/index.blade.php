@extends('layouts.erp')

@section('title', 'Recursos Humanos')

@section('content')
<div class="min-h-screen bg-slate-50 pb-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
        
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-8 relative overflow-hidden">
            <div class="absolute right-0 top-0 h-full w-1/2 bg-gradient-to-l from-indigo-50/80 to-transparent pointer-events-none"></div>
            
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-2">
                    <span class="px-3 py-1 rounded-full bg-indigo-100 text-indigo-700 text-xs font-bold uppercase tracking-wider border border-indigo-200">
                        Portal Administrativo
                    </span>
                    <span class="text-sm text-slate-400 font-medium">{{ \Carbon\Carbon::now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</span>
                </div>
                <h3 class="text-3xl font-bold text-slate-900 tracking-tight">Panel de Control RH</h3>
                <p class="mt-2 text-slate-500 max-w-2xl text-lg leading-relaxed">
                    Acceso centralizado a la gestión de expedientes, control de asistencia y procesos de evaluación del personal.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            
            <a href="{{ route('rh.expedientes.index') }}" class="group relative bg-white rounded-3xl p-8 shadow-sm border border-slate-200 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col">
                <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity text-blue-600">
                    <svg class="w-40 h-40" fill="currentColor" viewBox="0 0 24 24"><path d="M16 11c1.657 0 3-1.343 3-3S17.657 5 16 5s-3 1.343-3 3 1.343 3 3 3zm-2.97 3.515C11.393 14.825 9 16.52 9 20h14c0-3.48-2.393-5.175-4.03-5.485-.68-.13-1.35.43-1.35 1.135 0 .393.21 1.05.57 1.35.2.167.3.38.3.606V19a1 1 0 0 1-1 1h-2a1 1 0 0 1-1-1v-.95c0-.26.11-.51.3-.67.36-.3.57-.96.57-1.35 0-.7-.67-1.266-1.35-1.135zM4 20h3v-2.34l3.17-2.642a4.978 4.978 0 0 1-.955-1.703L4 16.5V20z"></path></svg>
                </div>
                
                <div class="relative z-10 flex-1">
                    <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 border border-blue-100">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0c0 .883.393 1.627 1 2.188m-4.546.364l-3.364-1.591m12.728 0l-3.364 1.591" /></svg>
                    </div>
                    <h4 class="text-xl font-bold text-slate-800 mb-2 group-hover:text-blue-600 transition-colors">Expedientes</h4>
                    <p class="text-slate-500 text-sm leading-relaxed">Base de datos centralizada de colaboradores, contratos y documentación personal.</p>
                </div>
                
                <div class="relative z-10 mt-6 flex items-center text-blue-600 font-bold text-sm">
                    Gestionar Personal <span class="ml-2 group-hover:translate-x-1 transition-transform">→</span>
                </div>
            </a>

            <a href="{{ route('rh.reloj.index') }}" class="group relative bg-white rounded-3xl p-8 shadow-sm border border-slate-200 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col">
                <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity text-emerald-600">
                    <svg class="w-40 h-40" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm.5-13H11v6l5.25 3.15.75-1.23-4.5-2.67z"></path></svg>
                </div>

                <div class="relative z-10 flex-1">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 border border-emerald-100">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    </div>
                    <h4 class="text-xl font-bold text-slate-800 mb-2 group-hover:text-emerald-600 transition-colors">Reloj Checador</h4>
                    <p class="text-slate-500 text-sm leading-relaxed">Control de asistencia, retardos, faltas y reportes de puntualidad.</p>
                </div>

                <div class="relative z-10 mt-6 flex items-center text-emerald-600 font-bold text-sm">
                    Ver Asistencias <span class="ml-2 group-hover:translate-x-1 transition-transform">→</span>
                </div>
            </a>

            <a href="{{ route('rh.evaluacion.index') }}" class="group relative bg-white rounded-3xl p-8 shadow-sm border border-slate-200 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col">
                <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity text-indigo-600">
                    <svg class="w-40 h-40" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"></path></svg>
                </div>

                <div class="relative z-10 flex-1">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 border border-indigo-100">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" /></svg>
                    </div>
                    <h4 class="text-xl font-bold text-slate-800 mb-2 group-hover:text-indigo-600 transition-colors">Evaluación</h4>
                    <p class="text-slate-500 text-sm leading-relaxed">Medición de competencias, cumplimiento de objetivos y KPIs por área.</p>
                </div>

                <div class="relative z-10 mt-6 flex items-center text-indigo-600 font-bold text-sm">
                    Ir a Evaluaciones <span class="ml-2 group-hover:translate-x-1 transition-transform">→</span>
                </div>
            </a>

            <a href="{{ route('rh.inventario.index') }}" class="group relative bg-white rounded-3xl p-8 shadow-sm border border-slate-200 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col">
                <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity text-violet-600">
                    <svg class="w-40 h-40" fill="currentColor" viewBox="0 0 24 24"><path d="M20 7H4a2 2 0 00-2 2v10a2 2 0 002 2h16a2 2 0 002-2V9a2 2 0 00-2-2zm-9 3h2v2h-2v-2zm0 4h2v2h-2v-2zM3 5h18v2H3V5z"/></svg>
                </div>
                <div class="relative z-10 flex-1">
                    <div class="w-14 h-14 rounded-2xl bg-violet-50 text-violet-600 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 border border-violet-100">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/>
                        </svg>
                    </div>
                    <h4 class="text-xl font-bold text-slate-800 mb-2 group-hover:text-violet-600 transition-colors">Inventario IT</h4>
                    <p class="text-slate-500 text-sm leading-relaxed">Catálogo completo de equipos y dispositivos de la organización con su estado actual.</p>
                </div>
                <div class="relative z-10 mt-6 flex items-center text-violet-600 font-bold text-sm">
                    Ver Inventario <span class="ml-2 group-hover:translate-x-1 transition-transform">→</span>
                </div>
            </a>

            <a href="{{ route('rh.dias-festivos.index') }}" class="group relative bg-white rounded-3xl p-8 shadow-sm border border-slate-200 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col">
                <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity text-rose-600">
                    <svg class="w-40 h-40" fill="currentColor" viewBox="0 0 24 24"><path d="M19 4h-1V2h-2v2H8V2H6v2H5c-1.11 0-1.99.9-1.99 2L3 20a2 2 0 002 2h14c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 16H5V9h14v11zM9 11H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2zm-8 4H7v2h2v-2zm4 0h-2v2h2v-2zm4 0h-2v2h2v-2z"/></svg>
                </div>
                <div class="relative z-10 flex-1">
                    <div class="w-14 h-14 rounded-2xl bg-rose-50 text-rose-600 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 border border-rose-100">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h4 class="text-xl font-bold text-slate-800 mb-2 group-hover:text-rose-600 transition-colors">Días Festivos</h4>
                    <p class="text-slate-500 text-sm leading-relaxed">Gestión de días festivos e inhábiles. Notificaciones automáticas a empleados.</p>
                </div>
                <div class="relative z-10 mt-6 flex items-center text-rose-600 font-bold text-sm">
                    Administrar <span class="ml-2 group-hover:translate-x-1 transition-transform">→</span>
                </div>
            </a>

            <a href="{{ route('rh.recordatorios.index') }}" class="group relative bg-white rounded-3xl p-8 shadow-sm border border-slate-200 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col">
                <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity text-amber-600">
                    <svg class="w-40 h-40" fill="currentColor" viewBox="0 0 24 24"><path d="M12 22c1.1 0 2-.9 2-2h-4c0 1.1.9 2 2 2zm6-6v-5c0-3.07-1.64-5.64-4.5-6.32V4c0-.83-.67-1.5-1.5-1.5s-1.5.67-1.5 1.5v.68C7.63 5.36 6 7.92 6 11v5l-2 2v1h16v-1l-2-2z"></path></svg>
                </div>
                <div class="relative z-10 flex-1">
                    <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 border border-amber-100">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                    </div>
                    <h4 class="text-xl font-bold text-slate-800 mb-2 group-hover:text-amber-600 transition-colors">Recordatorios</h4>
                    <p class="text-slate-500 text-sm leading-relaxed">Cumpleaños, aniversarios laborales y eventos importantes.</p>
                </div>
                <div class="relative z-10 mt-6 flex items-center text-amber-600 font-bold text-sm">
                    Ver Recordatorios <span class="ml-2 group-hover:translate-x-1 transition-transform">→</span>
                </div>
            </a>
        </div>

        @php
            $recordatoriosSemana = \App\Models\Recordatorio::where('activo', true)
                ->whereDate('fecha_evento', '>=', now()->startOfWeek())
                ->whereDate('fecha_evento', '<=', now()->endOfWeek()->addDays(7))
                ->orderBy('fecha_evento')
                ->get()
                ->groupBy(function($rec) {
                    return $rec->fecha_evento->format('Y-m-d');
                });
            $totalRecordatorios = \App\Models\Recordatorio::where('activo', true)
                ->whereDate('fecha_evento', '>=', now()->subDays(7))
                ->whereDate('fecha_evento', '<=', now()->addDays(30))
                ->count();
        @endphp

        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="bg-gradient-to-r from-amber-500 to-orange-500 px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-3 text-white">
                    <div class="w-10 h-10 rounded-xl bg-white/20 flex items-center justify-center">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                        </svg>
                    </div>
                    <div>
                        <h5 class="text-lg font-bold">Recordatorios de la Semana</h5>
                        <p class="text-amber-100 text-xs">{{ now()->startOfWeek()->locale('es')->format('d MMM') }} - {{ now()->endOfWeek()->addDays(7)->locale('es')->format('d MMM') }}</p>
                    </div>
                </div>
                <a href="{{ route('rh.recordatorios.index') }}" class="bg-white text-amber-600 px-4 py-2 rounded-xl font-bold text-sm hover:bg-amber-50 transition-all flex items-center gap-2">
                    Ver mes ({{ $totalRecordatorios }})
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>
            </div>
            
            @if($recordatoriosSemana->isEmpty())
                <div class="p-8 text-center">
                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <p class="text-slate-500 font-medium">Sin recordatorios esta semana</p>
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($recordatoriosSemana as $fecha => $recs)
                        <div class="px-6 py-4 hover:bg-slate-50 transition-colors">
                            <div class="flex items-start gap-4">
                                <div class="flex-shrink-0">
                                    <div class="w-12 h-12 rounded-xl {{ $recs->first()->color_urgencia['bg'] ?? 'bg-slate-100' }} flex flex-col items-center justify-center">
                                        <span class="text-xs font-bold {{ $recs->first()->color_urgencia['text'] ?? 'text-slate-600' }}">
                                            {{ $fecha ? \Carbon\Carbon::parse($fecha)->locale('es')->format('d') : '?' }}
                                        </span>
                                        <span class="text-[10px] font-medium {{ $recs->first()->color_urgencia['text'] ?? 'text-slate-500' }} uppercase">
                                            {{ $fecha ? \Carbon\Carbon::parse($fecha)->locale('es')->format('M') : '' }}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex-1 min-w-0">
                                    @foreach($recs as $rec)
                                        <div class="flex items-center gap-3 mb-2 last:mb-0">
                                            <span class="text-xl">{{ $rec->icono_tipo }}</span>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-sm font-medium text-slate-900 truncate">{{ $rec->titulo }}</p>
                                                <p class="text-xs text-slate-500">{{ $rec->descripcion }}</p>
                                            </div>
                                            @if($rec->dias_restantes !== null && $rec->dias_restantes < 0)
                                                <span class="flex-shrink-0 text-xs font-bold text-red-600 bg-red-100 px-2 py-1 rounded-full">
                                                    Vencido
                                                </span>
                                            @elseif($rec->dias_restantes == 0)
                                                <span class="flex-shrink-0 text-xs font-bold text-amber-600 bg-amber-100 px-2 py-1 rounded-full">
                                                    Hoy
                                                </span>
                                            @else
                                                <span class="flex-shrink-0 text-xs font-medium text-slate-500">
                                                    En {{ $rec->dias_restantes }} días
                                                </span>
                                            @endif
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-indigo-600 rounded-3xl p-6 text-white flex items-center justify-between shadow-lg shadow-indigo-200 relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-r from-indigo-500 to-indigo-600 opacity-50 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10">
                    <p class="text-indigo-100 text-xs font-bold uppercase tracking-wider mb-1">Soporte IT</p>
                    <h5 class="text-lg font-bold">Reportar Incidencia</h5>
                </div>
                <a href="{{ route('welcome', ['from' => 'tickets']) }}" class="relative z-10 bg-white/20 backdrop-blur-sm border border-white/30 text-white px-5 py-2.5 rounded-xl font-bold text-sm hover:bg-white hover:text-indigo-600 transition-all">
                    Nuevo Ticket
                </a>
            </div>
        </div>

    </div>
</div>
@endsection