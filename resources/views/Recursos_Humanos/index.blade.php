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
                    <span class="text-sm text-slate-400 font-medium">{{ date('F  d\t\h, Y') }}</span>
                </div>
                <h3 class="text-3xl font-bold text-slate-900 tracking-tight">Panel de Control RH</h3>
                <p class="mt-2 text-slate-500 max-w-2xl text-lg leading-relaxed">
                    Acceso centralizado a la gestión de expedientes, control de asistencia y procesos de evaluación del personal.
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
            
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
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="bg-slate-900 rounded-3xl p-6 text-white flex items-center justify-between shadow-lg relative overflow-hidden group">
                <div class="absolute inset-0 bg-gradient-to-r from-indigo-900 to-slate-900 opacity-50 group-hover:opacity-100 transition-opacity"></div>
                <div class="relative z-10">
                    <p class="text-indigo-200 text-xs font-bold uppercase tracking-wider mb-1">Acción Rápida</p>
                    <h5 class="text-lg font-bold">Registrar Nuevo Empleado</h5>
                </div>
                {{-- Ajusta la ruta a tu formulario de creación de empleados --}}
                <button class="relative z-10 bg-white text-slate-900 px-5 py-2.5 rounded-xl font-bold text-sm hover:bg-indigo-50 transition-colors shadow-sm">
                    Crear +
                </button>
            </div>
            
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
@endsection|