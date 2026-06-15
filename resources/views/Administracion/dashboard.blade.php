@extends('layouts.erp')

@section('title', 'Panel de Administración')

@section('content')
<div class="min-h-screen bg-slate-50 pb-12">

    {{-- ENCABEZADO --}}
    <div class="bg-white border-b border-slate-200 mb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div>
                    <p class="text-xs font-bold text-indigo-500 uppercase tracking-widest mb-1">Panel Corporativo</p>
                    <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Administración</h1>
                    <p class="text-slate-500 mt-1">Gestión centralizada de recursos administrativos.</p>
                </div>
                <a href="{{ route('welcome') }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 text-slate-600 font-bold text-sm rounded-xl hover:bg-slate-50 hover:text-indigo-600 hover:border-indigo-200 transition shadow-sm group">
                    <svg class="w-4 h-4 mr-2 group-hover:-translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Volver al Portal
                </a>
            </div>
        </div>
    </div>

    {{-- MÓDULOS --}}
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">

            {{-- CARD: CLIENTES --}}
            <a href="{{ route('administracion.clientes.index') }}"
               class="group bg-white p-6 rounded-3xl shadow-sm border border-slate-200 flex flex-col justify-between hover:shadow-md hover:border-indigo-200 transition-all duration-300">

                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 bg-indigo-50 text-indigo-600 rounded-2xl">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                    </div>
                    <span class="text-3xl font-extrabold text-slate-800">{{ $totalClientes }}</span>
                </div>

                <div class="mb-6">
                    <h2 class="text-xl font-bold text-slate-900 mb-1">Perfil de Clientes</h2>
                    <p class="text-slate-500 text-sm leading-relaxed">
                        Registro y gestión de clientes del área de administración.
                    </p>
                </div>

                <div class="mt-auto pt-4 border-t border-slate-100">
                    <span class="group flex items-center justify-center w-full px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl transition-all shadow-lg shadow-indigo-200 hover:shadow-xl hover:-translate-y-0.5">
                        Ver Perfil de Clientes
                        <svg class="ml-2 h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </span>
                </div>
            </a>

            {{-- CARD: MATRIZ DE CONSULTA LEGAL --}}
            <a href="{{ route('legal.matriz.index') }}"
               class="group bg-white p-6 rounded-3xl shadow-sm border border-slate-200 flex flex-col justify-between hover:shadow-md hover:border-amber-200 transition-all duration-300">
                <div class="flex items-start justify-between mb-4">
                    <div class="p-3 bg-amber-50 text-amber-600 rounded-2xl">
                        <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3"/>
                        </svg>
                    </div>
                </div>
                <div class="mb-6">
                    <h2 class="text-xl font-bold text-slate-900 mb-1">Matriz de Consulta Legal</h2>
                    <p class="text-slate-500 text-sm leading-relaxed">Consulta y seguimiento de expedientes, casos y gestiones jurídicas por empresa y categoría.</p>
                </div>
                <div class="mt-auto pt-4 border-t border-slate-100">
                    <span class="group flex items-center justify-center w-full px-4 py-3 bg-amber-600 hover:bg-amber-700 text-white font-bold text-sm rounded-xl transition-all shadow-lg shadow-amber-200 hover:shadow-xl hover:-translate-y-0.5">
                        Ver Matriz Legal
                        <svg class="ml-2 h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6"/>
                        </svg>
                    </span>
                </div>
            </a>

        </div>
    </div>

</div>
@endsection
