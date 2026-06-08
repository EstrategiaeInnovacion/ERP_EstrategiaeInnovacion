@extends('layouts.erp')

@section('title', 'Logística')

@section('content')
<div class="min-h-screen bg-slate-50 pb-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">

        {{-- HERO HEADER --}}
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-8 relative overflow-hidden">
            <div class="absolute right-0 top-0 h-full w-1/2 bg-gradient-to-l from-blue-50/80 to-transparent pointer-events-none"></div>
            <div class="relative z-10">
                <div class="flex items-center gap-3 mb-2">
                    <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-xs font-bold uppercase tracking-wider border border-blue-200">
                        Operaciones
                    </span>
                    <span class="text-sm text-slate-400 font-medium">{{ \Carbon\Carbon::now()->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}</span>
                </div>
                <h3 class="text-3xl font-bold text-slate-900 tracking-tight">Panel de Logística</h3>
                <p class="mt-2 text-slate-500 max-w-2xl text-lg leading-relaxed">
                    Control de operaciones de comercio exterior y gestión de reportes.
                </p>
            </div>
        </div>

        {{-- MÓDULOS --}}
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            <a href="{{ route('logistica.clientes.index') }}"
               class="group relative bg-white rounded-3xl p-8 shadow-sm border border-slate-200 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col">
                <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity text-indigo-600">
                    <svg class="w-40 h-40" fill="currentColor" viewBox="0 0 24 24"><path d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </div>
                <div class="relative z-10 flex-1">
                    <div class="w-14 h-14 rounded-2xl bg-indigo-50 text-indigo-600 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 border border-indigo-100">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                        </svg>
                    </div>
                    <h4 class="text-xl font-bold text-slate-800 mb-2 group-hover:text-indigo-600 transition-colors">Administrar Clientes</h4>
                    <p class="text-slate-500 text-sm leading-relaxed">Alta, edición y configuración de clientes de comercio exterior.</p>
                </div>
                <div class="relative z-10 mt-6 flex items-center text-indigo-600 font-bold text-sm">
                    Ir a Clientes <span class="ml-2 group-hover:translate-x-1 transition-transform">→</span>
                </div>
            </a>

            <a href="{{ route('logistica.matriz-apoyo') }}"
               class="group relative bg-white rounded-3xl p-8 shadow-sm border border-slate-200 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col">
                <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity text-amber-600">
                    <svg class="w-40 h-40" fill="currentColor" viewBox="0 0 24 24"><path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                </div>
                <div class="relative z-10 flex-1">
                    <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 border border-amber-100">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/>
                        </svg>
                    </div>
                    <h4 class="text-xl font-bold text-slate-800 mb-2 group-hover:text-amber-600 transition-colors">Directorio de Proveedores</h4>
                    <p class="text-slate-500 text-sm leading-relaxed">Registro y seguimiento de actividades de apoyo operativo entre áreas.</p>
                </div>
                <div class="relative z-10 mt-6 flex items-center text-amber-600 font-bold text-sm">
                    Ir a la Matriz <span class="ml-2 group-hover:translate-x-1 transition-transform">→</span>
                </div>
            </a>

            <a href="{{ route('logistica.clientes.perfil') }}"
               class="group relative bg-white rounded-3xl p-8 shadow-sm border border-slate-200 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col">
                <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity text-sky-600">
                    <svg class="w-40 h-40" fill="currentColor" viewBox="0 0 24 24"><path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </div>
                <div class="relative z-10 flex-1">
                    <div class="w-14 h-14 rounded-2xl bg-sky-50 text-sky-600 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 border border-sky-100">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <h4 class="text-xl font-bold text-slate-800 mb-2 group-hover:text-sky-600 transition-colors">Perfil de Clientes</h4>
                    <p class="text-slate-500 text-sm leading-relaxed">Consulta el cuestionario de perfil de comercio exterior de los clientes.</p>
                </div>
                <div class="relative z-10 mt-6 flex items-center text-sky-600 font-bold text-sm">
                    Ver Perfil de Clientes <span class="ml-2 group-hover:translate-x-1 transition-transform">→</span>
                </div>
            </a>

            <a href="{{ route('logistica.matriz-seguimiento') }}"
               class="group relative bg-white rounded-3xl p-8 shadow-sm border border-slate-200 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col">
                <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity text-emerald-600">
                    <svg class="w-40 h-40" fill="currentColor" viewBox="0 0 24 24"><path d="M3 10h18M3 14h18M10 3v18M14 3v18M3 3h18v18H3z"/></svg>
                </div>
                <div class="relative z-10 flex-1">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 border border-emerald-100">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"/>
                        </svg>
                    </div>
                    <h4 class="text-xl font-bold text-slate-800 mb-2 group-hover:text-emerald-600 transition-colors">Matriz de Seguimiento</h4>
                    <p class="text-slate-500 text-sm leading-relaxed">Seguimiento y control del estado de operaciones de comercio exterior por cliente.</p>
                </div>
                <div class="relative z-10 mt-6 flex items-center text-emerald-600 font-bold text-sm">
                    Ir a Seguimiento <span class="ml-2 group-hover:translate-x-1 transition-transform">→</span>
                </div>
            </a>

            <a href="{{ route('logistica.reportes') }}"
               class="group relative bg-white rounded-3xl p-8 shadow-sm border border-slate-200 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col">
                <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity text-violet-600">
                    <svg class="w-40 h-40" fill="currentColor" viewBox="0 0 24 24"><path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                </div>
                <div class="relative z-10 flex-1">
                    <div class="w-14 h-14 rounded-2xl bg-violet-50 text-violet-600 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 border border-violet-100">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                        </svg>
                    </div>
                    <h4 class="text-xl font-bold text-slate-800 mb-2 group-hover:text-violet-600 transition-colors">Reportes</h4>
                    <p class="text-slate-500 text-sm leading-relaxed">Generación y consulta de reportes de operaciones de comercio exterior.</p>
                </div>
                <div class="relative z-10 mt-6 flex items-center text-violet-600 font-bold text-sm">
                    Ver Reportes <span class="ml-2 group-hover:translate-x-1 transition-transform">→</span>
                </div>
            </a>

            {{-- CARD: MATRIZ DE CONSULTA LEGAL --}}
            <a href="{{ route('legal.matriz.index') }}"
               class="group relative bg-white rounded-3xl p-8 shadow-sm border border-slate-200 hover:shadow-xl hover:-translate-y-1 transition-all duration-300 overflow-hidden flex flex-col">
                <div class="absolute top-0 right-0 p-4 opacity-5 group-hover:opacity-10 transition-opacity text-amber-600">
                    <svg class="w-40 h-40" fill="currentColor" viewBox="0 0 24 24"><path d="M3 6l3 1m0 0l-3 9a5 5 0 006 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5 5 0 006 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/></svg>
                </div>
                <div class="relative z-10 flex-1">
                    <div class="w-14 h-14 rounded-2xl bg-amber-50 text-amber-600 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform duration-300 border border-amber-100">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                        </svg>
                    </div>
                    <h4 class="text-xl font-bold text-slate-800 mb-2 group-hover:text-amber-600 transition-colors">Matriz de Consulta Legal</h4>
                    <p class="text-slate-500 text-sm leading-relaxed">Consulta y seguimiento de expedientes, casos y gestiones jurídicas por empresa y categoría.</p>
                </div>
                <div class="relative z-10 mt-6 flex items-center text-amber-600 font-bold text-sm">
                    Ver Matriz Legal <span class="ml-2 group-hover:translate-x-1 transition-transform">→</span>
                </div>
            </a>

        </div>

    </div>
</div>
@endsection
