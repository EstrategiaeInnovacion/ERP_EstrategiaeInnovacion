@extends('layouts.erp')

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
                    'description'  => 'Consulta y seguimiento de expedientes, casos y gestiones jurídicas por empresa y categoría.',
                    'route'        => route('legal.matriz.index'),
                    'cta'          => 'Ir a la matriz',
                    'icon'         => 'M3 6l3 1m0 0l-3 9a5.002 5.002 0 006.001 0M6 7l3 9M6 7l6-2m6 2l3-1m-3 1l-3 9a5.002 5.002 0 006.001 0M18 7l3 9m-3-9l-6-2m0-2v2m0 16V5m0 16H9m3 0h3',
                    'color_bg'     => 'bg-amber-50',
                    'color_text'   => 'text-amber-600',
                    'hover_border' => 'hover:border-amber-200',
                    'btn_color'    => 'bg-amber-600 hover:bg-amber-700 shadow-amber-200',
                ],
                [
                    'title'        => 'Programas y Páginas',
                    'description'  => 'Acceso a programas, plataformas y páginas de referencia del área legal.',
                    'route'        => route('legal.programas.index'),
                    'cta'          => 'Ver recursos',
                    'icon'         => 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
                    'color_bg'     => 'bg-slate-100',
                    'color_text'   => 'text-slate-600',
                    'hover_border' => 'hover:border-slate-300',
                    'btn_color'    => 'bg-slate-600 hover:bg-slate-700 shadow-slate-200',
                ],
                [
                    'title'        => 'Perfil de Clientes',
                    'description'  => 'Consulta el cuestionario de perfil de comercio exterior de los clientes (solo lectura).',
                    'route'        => route('legal.clientes'),
                    'cta'          => 'Ver Perfil de Clientes',
                    'icon'         => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
                    'color_bg'     => 'bg-sky-50',
                    'color_text'   => 'text-sky-600',
                    'hover_border' => 'hover:border-sky-200',
                    'btn_color'    => 'bg-sky-600 hover:bg-sky-700 shadow-sky-200',
                ],
                [
                    'title'        => 'Digitalización de documentos',
                    'description'  => 'Convierte, valida, comprime, combina y extrae imágenes de PDFs cumpliendo los requisitos VUCEM (300 DPI, PDF 1.4, escala de grises, máx. 3 MB).',
                    'route'        => route('digitalizacion.index'),
                    'cta'          => 'Ir a herramientas PDF',
                    'icon'         => 'M7 21h10M7 21a2 2 0 01-2-2V5a2 2 0 012-2h6l5 5v11a2 2 0 01-2 2M7 21H5m14 0h2M9 7h1m-1 4h6m-6 4h6',
                    'color_bg'     => 'bg-sky-50',
                    'color_text'   => 'text-sky-600',
                    'hover_border' => 'hover:border-sky-200',
                    'btn_color'    => 'bg-sky-600 hover:bg-sky-700 shadow-sky-200',
                ],
                [
                    'title'        => 'Análisis de Origen T-MEC',
                    'description'  => 'Determina si tus productos califican como originarios bajo las reglas T-MEC/USMCA. Carga BOMs, calcula VCR, verifica CC y emite el dictamen de origen.',
                    'route'        => route('legal.ce.bom.index'),
                    'cta'          => 'Ir al Análisis de Origen',
                    'icon'         => 'M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z',
                    'color_bg'     => 'bg-indigo-50',
                    'color_text'   => 'text-indigo-600',
                    'hover_border' => 'hover:border-indigo-200',
                    'btn_color'    => 'bg-indigo-600 hover:bg-indigo-700 shadow-indigo-200',
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

        <div class="mt-12 bg-indigo-50/50 rounded-3xl border border-indigo-100 border-dashed p-6 flex flex-col sm:flex-row items-center justify-between gap-4">
            <div>
                <p class="text-sm font-semibold text-indigo-700">Configurar Análisis de Origen</p>
                <p class="text-xs text-slate-500 mt-0.5">Carga el catálogo de reglas T-MEC desde Excel y configura el motor de análisis.</p>
            </div>
            <a href="{{ route('legal.ce.configuracion.index') }}"
               class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition shadow-sm shrink-0">
                Ir a Configuración CE
            </a>
        </div>

    </div>
</div>
@endsection
