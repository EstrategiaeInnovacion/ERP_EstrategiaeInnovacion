@extends('layouts.erp')

@section('title', 'Portal Corporativo - Estrategia e Innovación')

@section('content')
    <main class="relative min-h-screen bg-slate-50 overflow-hidden font-sans text-slate-600">
        
        {{-- Fondo Decorativo Sutil --}}
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute top-0 left-0 w-full h-96 bg-gradient-to-b from-white to-slate-50"></div>
            <div class="absolute -top-24 -right-24 w-96 h-96 bg-indigo-50/40 blur-3xl rounded-full mix-blend-multiply"></div>
            <div class="absolute top-24 -left-24 w-72 h-72 bg-emerald-50/40 blur-3xl rounded-full mix-blend-multiply"></div>
        </div>

        <div class="relative max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-12 pb-16">

            {{-- ALERTAS --}}
            @if(session('success') || session('info'))
                <div class="max-w-3xl mx-auto mb-10 animate-fade-in-up">
                    <div class="flex items-center gap-3 p-4 rounded-xl border {{ session('success') ? 'bg-emerald-50 border-emerald-100 text-emerald-700' : 'bg-blue-50 border-blue-100 text-blue-700' }}">
                        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span class="text-sm font-medium">{{ session('success') ?? session('info') }}</span>
                    </div>
                </div>
            @endif

            @guest
                {{-- VISTA INVITADO: LOGIN LIMPIO --}}
                <div class="flex flex-col items-center justify-center min-h-[70vh]">
                    <div class="text-center mb-10">
                        <span class="px-3 py-1 rounded-full bg-white border border-slate-200 text-[10px] font-bold uppercase tracking-widest text-slate-400 shadow-sm">Acceso Corporativo</span>
                        <h1 class="mt-4 text-4xl sm:text-5xl font-extrabold text-slate-900 tracking-tight">
                            ERP <span class="text-indigo-600">Estrategia e Innovación</span>
                        </h1>
                        <p class="mt-3 text-lg text-slate-500 max-w-lg mx-auto">
                            Plataforma integral de gestión de recursos, capital humano y servicios tecnológicos.
                        </p>
                    </div>

                    <div class="w-full max-w-sm bg-white rounded-2xl shadow-xl border border-slate-100 overflow-hidden transform transition-all hover:scale-[1.01] duration-300">
                        <div class="h-1.5 bg-gradient-to-r from-indigo-500 via-emerald-500 to-indigo-500"></div>
                        <div class="p-8">
                            <div class="flex justify-center mb-6">
                                <div class="w-16 h-16 bg-slate-50 rounded-xl flex items-center justify-center text-slate-400">
                                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                </div>
                            </div>
                            <h2 class="text-center text-xl font-bold text-slate-800 mb-6">Bienvenido al Sistema</h2>
                            <div class="space-y-3">
                                <a href="{{ route('login') }}" class="block w-full py-3 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg text-center transition shadow-lg shadow-indigo-100 text-sm">
                                    Ingresar con Credenciales
                                </a>
                                <a href="{{ route('register') }}" class="block w-full py-3 px-4 bg-white border border-slate-200 hover:bg-slate-50 text-slate-600 font-semibold rounded-lg text-center transition text-sm">
                                    Solicitar Acceso
                                </a>
                            </div>
                        </div>
                        <div class="bg-slate-50 px-8 py-3 border-t border-slate-100 text-center">
                            <p class="text-[10px] text-slate-400 uppercase tracking-wider font-bold">Solo Personal Autorizado</p>
                        </div>
                    </div>
                </div>
            @endguest

            @auth
                {{-- VISTA AUTENTICADO: DASHBOARD CORPORATIVO --}}
                
                {{-- 1. HEADER DEL EMPLEADO --}}
                <div class="flex flex-col md:flex-row md:items-center justify-between mb-12 border-b border-slate-200 pb-6">
                    <div>
                        <p class="text-sm font-semibold text-indigo-600 uppercase tracking-wider mb-1">
                            {{ date('d') }} de {{ \Carbon\Carbon::now()->locale('es')->monthName }} del {{ date('Y') }}
                        </p>
                        <h1 class="text-3xl font-extrabold text-slate-900">
                            Hola, <span class="text-transparent bg-clip-text bg-gradient-to-r from-slate-700 to-slate-500">{{ Auth::user()->name }}</span>
                        </h1>
                        <p class="text-slate-500 mt-1">Bienvenido a tu panel de control.</p>
                    </div>
                    
                    {{-- ACCIONES RÁPIDAS POR ROL --}}
                    <div class="mt-6 md:mt-0 flex flex-wrap gap-3 items-center">
                        
                        {{-- Botón PANEL DINÁMICO (según posición) - Solo muestra si es IT, RH o Logística --}}
                        @php
                            $panelInfo = Auth::user()->getPanelInfo();
                        @endphp
                        @if($panelInfo['available'])
                            <a href="{{ $panelInfo['route'] }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-xs font-bold uppercase tracking-wide rounded-xl shadow-lg shadow-indigo-200 hover:bg-indigo-700 hover:-translate-y-0.5 transition-all">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"></path></svg>
                                {{ $panelInfo['label'] }}
                            </a>
                        @endif

                        <span class="inline-flex items-center px-3 py-1 rounded-full bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-100">
                            <span class="w-2 h-2 bg-emerald-500 rounded-full mr-2 animate-pulse"></span> Sistema Operativo
                        </span>
                    </div>
                </div>

                {{-- 2. SECCIÓN: GESTIÓN Y PRODUCTIVIDAD (Módulos Principales) --}}
                <div class="mb-10">
                    <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2 mb-6">
                        <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        Mis Herramientas de Gestión
                    </h2>
                    
                    {{-- GRID ACTUALIZADO A 3 COLUMNAS --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        
                        {{-- CARD 1: ACTIVIDADES --}}
                        <a href="{{ route('activities.index') }}" class="group relative bg-white p-6 rounded-2xl shadow-sm border border-slate-200 hover:shadow-lg hover:border-indigo-200 transition-all duration-300 flex items-start gap-5 overflow-hidden">
                            <div class="absolute right-0 top-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                                <svg class="w-40 h-40 text-indigo-600" fill="currentColor" viewBox="0 0 24 24"><path d="M9 12h3.75M9 15h3.75M9 12h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6a2.25 2.25 0 00-2.25-2.25H6A2.25 2.25 0 003.75 6v6a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 12V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44l-2.12-2.12a1.5 1.5 0 00-1.06-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9"/></svg>
                            </div>
                            <div class="w-14 h-14 rounded-xl bg-indigo-50 text-indigo-600 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                            </div>
                            <div class="relative z-10 flex-1">
                                <h3 class="text-xl font-bold text-slate-800 group-hover:text-indigo-700 transition-colors">Reporte de Actividades</h3>
                                <p class="text-sm text-slate-500 mt-2 leading-relaxed">
                                    Panel de control para seguimiento diario de tareas, prioridades y eficiencia.
                                </p>
                                <div class="mt-4 flex items-center text-xs font-bold text-indigo-600 uppercase tracking-wide">
                                    Ingresar al Módulo <span class="ml-2 group-hover:translate-x-1 transition-transform">→</span>
                                </div>
                            </div>
                        </a>

                        {{-- CARD 2: EVALUACIÓN --}}
                        <a href="{{ route('rh.evaluacion.index') }}" class="group relative bg-white p-6 rounded-2xl shadow-sm border border-slate-200 hover:shadow-lg hover:border-emerald-200 transition-all duration-300 flex items-start gap-5 overflow-hidden">
                            <div class="absolute right-0 top-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                                <svg class="w-40 h-40 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                            </div>
                            <div class="w-14 h-14 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                            </div>
                            <div class="relative z-10 flex-1">
                                <h3 class="text-xl font-bold text-slate-800 group-hover:text-emerald-700 transition-colors">Evaluación 360°</h3>
                                <p class="text-sm text-slate-500 mt-2 leading-relaxed">
                                    Realizar evaluaciones de desempeño, autoevaluaciones y feedback.
                                </p>
                                <div class="mt-4 flex items-center text-xs font-bold text-emerald-600 uppercase tracking-wide">
                                    Ingresar al Módulo <span class="ml-2 group-hover:translate-x-1 transition-transform">→</span>
                                </div>
                            </div>
                        </a>

                        {{-- CARD 3: CAPACITACIÓN --}}
                        <a href="{{ route('capacitacion.index') }}" class="group relative bg-white p-6 rounded-2xl shadow-sm border border-slate-200 hover:shadow-lg hover:border-violet-200 transition-all duration-300 flex items-start gap-5 overflow-hidden">
                            <div class="absolute right-0 top-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity">
                                <svg class="w-40 h-40 text-violet-600" fill="currentColor" viewBox="0 0 24 24"><path d="M4 6a2 2 0 012-2h12a2 2 0 012 2v12a2 2 0 01-2 2H6a2 2 0 01-2-2V6zm10.75 6.75l-4.5 2.6a.75.75 0 01-1.125-.65v-5.2a.75.75 0 011.125-.65l4.5 2.6a.75.75 0 010 1.3z" /></svg>
                            </div>
                            <div class="w-14 h-14 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center flex-shrink-0 group-hover:scale-110 transition-transform">
                                <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </div>
                            <div class="relative z-10 flex-1">
                                <h3 class="text-xl font-bold text-slate-800 group-hover:text-violet-700 transition-colors">Centro de Capacitación</h3>
                                <p class="text-sm text-slate-500 mt-2 leading-relaxed">
                                    Biblioteca de videos tutoriales, cursos internos y material de formación.
                                </p>
                                <div class="mt-4 flex items-center text-xs font-bold text-violet-600 uppercase tracking-wide">
                                    Ver Videos <span class="ml-2 group-hover:translate-x-1 transition-transform">→</span>
                                </div>
                            </div>
                        </a>

                    </div>
                </div>

                {{-- 3. SECCIÓN: SERVICIOS Y SOPORTE IT (Tickets) --}}
                <div>
                    {{-- Cabecera con Título y Botón "Mis Tickets" --}}
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                        <h2 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                            Mesa de Ayuda (Tickets IT)
                        </h2>
                        
                        <a href="{{ route('tickets.mis-tickets') }}" class="inline-flex items-center justify-center px-4 py-2 bg-white border border-slate-200 rounded-lg text-sm font-bold text-slate-600 hover:text-blue-600 hover:border-blue-200 hover:bg-blue-50 transition-all shadow-sm">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"></path></svg>
                            Ver Mis Tickets
                        </a>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
                        @php
                            $services = [
                                [
                                    'title' => 'Software',
                                    'desc' => 'Errores de sistema, instalación de programas o accesos.',
                                    'route' => route('tickets.create', 'software'),
                                    'icon' => 'M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4',
                                    'color' => 'blue'
                                ],
                                [
                                    'title' => 'Mantenimiento',
                                    'desc' => 'Limpieza preventiva, revisión física y optimización.',
                                    'route' => route('tickets.create', 'mantenimiento'),
                                    'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
                                    'color' => 'sky'
                                ],
                                [
                                    'title' => 'Hardware',
                                    'desc' => 'Fallas en equipos, impresoras, red o periféricos.',
                                    'route' => route('tickets.create', 'hardware'),
                                    'icon' => 'M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z',
                                    'color' => 'slate'
                                ]
                            ];
                        @endphp

                        @foreach($services as $srv)
                            <a href="{{ $srv['route'] }}" class="bg-white p-5 rounded-xl border border-slate-100 shadow-sm hover:shadow-md hover:border-{{ $srv['color'] }}-200 transition-all group flex flex-col h-full">
                                <div class="flex items-center gap-4 mb-3">
                                    <div class="w-10 h-10 rounded-lg bg-{{ $srv['color'] }}-50 text-{{ $srv['color'] }}-600 flex items-center justify-center group-hover:scale-110 transition-transform">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $srv['icon'] }}"></path></svg>
                                    </div>
                                    <h4 class="font-bold text-slate-800 group-hover:text-{{ $srv['color'] }}-600 transition-colors">{{ $srv['title'] }}</h4>
                                </div>
                                <p class="text-xs text-slate-500 mb-4 flex-1">
                                    {{ $srv['desc'] }}
                                </p>
                                <div class="text-[10px] font-bold text-{{ $srv['color'] }}-600 uppercase tracking-wider flex items-center">
                                    Crear Ticket <span class="ml-1 opacity-0 group-hover:opacity-100 transition-opacity">→</span>
                                </div>
                            </a>
                        @endforeach
                    </div>
                </div>

                {{-- FOOTER INTERNO --}}
                <div class="mt-16 text-center border-t border-slate-100 pt-8">
                    <p class="text-xs text-slate-400">
                        ¿Necesitas ayuda con el sistema? <a href="mailto:soporte@estrategiaeinnovacion.com.mx" class="text-indigo-500 hover:underline">Contactar a Soporte</a>
                    </p>
                </div>

            @endauth

        </div>
    </main>
@endsection