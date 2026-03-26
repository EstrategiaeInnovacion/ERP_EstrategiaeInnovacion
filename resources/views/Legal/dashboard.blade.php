@extends('layouts.master')

@section('title', 'Panel Legal - Área Jurídica')

@section('content')
<div class="min-h-screen bg-slate-50 pb-12">
    <div class="bg-white border-b border-slate-200 mb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Panel Legal</h1>
                    <p class="text-slate-500 mt-1 text-lg">Gestión centralizada del área jurídica y legal.</p>
                </div>
                <a href="{{ route('welcome') }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 text-slate-600 font-bold text-sm rounded-xl hover:bg-slate-50 hover:text-amber-600 hover:border-amber-200 transition shadow-sm group">
                    <svg class="w-4 h-4 mr-2 group-hover:-translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                    </svg>
                    Volver al Portal
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        @php
            $legalCards = [
                [
                    'title'        => 'Matriz de Consulta',
                    'description'  => 'Consulta y seguimiento de expedientes, casos y gestiones legales.',
                    'route'        => '#',
                    'cta'          => 'Ir a la matriz',
                    'icon'         => 'M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3',
                    'color_bg'     => 'bg-amber-50',
                    'color_text'   => 'text-amber-600',
                    'hover_border' => 'hover:border-amber-200',
                    'btn_color'    => 'bg-amber-600 hover:bg-amber-700 shadow-amber-200',
                ],
            ];
        @endphp

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($legalCards as $card)
                <article class="bg-white p-6 rounded-3xl shadow-sm border border-slate-200 flex flex-col justify-between h-full group hover:shadow-md transition-all duration-300 {{ $card['hover_border'] }}">

                    <div class="flex items-start justify-between mb-4">
                        <div class="p-3 {{ $card['color_bg'] }} {{ $card['color_text'] }} rounded-2xl transition-colors">
                            <svg class="h-8 w-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $card['icon'] }}" />
                            </svg>
                        </div>
                    </div>

                    <div class="mb-6">
                        <h2 class="text-xl font-bold text-slate-900 mb-2">{{ $card['title'] }}</h2>
                        <p class="text-slate-500 text-sm leading-relaxed">{{ $card['description'] }}</p>
                    </div>

                    <div class="mt-auto pt-4 border-t border-slate-100">
                        <a href="{{ $card['route'] }}"
                           class="group flex items-center justify-center w-full px-4 py-3 {{ $card['btn_color'] }} text-white font-bold text-sm rounded-xl transition-all shadow-lg hover:shadow-xl hover:-translate-y-0.5">
                            {{ $card['cta'] }}
                            <svg class="ml-2 h-4 w-4 transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                            </svg>
                        </a>
                    </div>
                </article>
            @endforeach
        </div>

        <div class="mt-12 bg-slate-100/50 rounded-3xl border border-slate-200 border-dashed p-8 text-center">
            <p class="text-slate-400 text-sm">
                Más módulos del área Legal serán agregados aquí próximamente.
            </p>
        </div>

    </div>
</div>
@endsection
