@extends('layouts.master')

@section('title', '🆘 Manual de Ayuda - Sistema IT')

@section('content')
<main id="top" class="relative min-h-screen overflow-hidden bg-gradient-to-br from-white via-blue-50 to-blue-100">
    <div class="absolute inset-0 pointer-events-none">
        <div class="absolute -top-24 -left-32 h-72 w-72 rounded-full bg-blue-200/40 blur-3xl"></div>
        <div class="absolute top-1/3 -right-20 h-80 w-80 rounded-full bg-blue-300/30 blur-3xl"></div>
        <div class="absolute bottom-0 left-1/2 h-40 w-full -translate-x-1/2 bg-gradient-to-t from-white"></div>
    </div>

    <div class="relative mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-10">
        @php($hasSections = $sections->count() > 0)

        <!-- Header -->
        <div class="mb-12 text-center">
            <div class="mb-6 inline-flex h-20 w-20 items-center justify-center rounded-3xl bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-xl shadow-blue-500/30">
                    <x-ui.icon name="lifebuoy" class="h-10 w-10" />
                </div>
            <h1 class="mb-4 text-3xl font-bold text-slate-900 sm:text-4xl">Manual de Ayuda</h1>
            <p class="mx-auto max-w-2xl text-base text-slate-600 sm:text-lg">
                Guía completa para usar el Sistema de Tickets IT. Encuentra respuestas a las preguntas más frecuentes y aprende a utilizar todas las funcionalidades disponibles.
            </p>
        </div>

        <!-- Estadísticas -->
        <div class="mb-12 grid grid-cols-1 gap-6 lg:grid-cols-3">
            <div class="relative overflow-hidden rounded-3xl border border-blue-200/60 bg-white/80 p-6 shadow-lg shadow-blue-500/10 backdrop-blur">
                <div class="absolute -top-12 -right-8 h-28 w-28 rounded-full bg-blue-200/50 blur-2xl"></div>
                <div class="relative flex items-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-100 text-blue-600">
                        <x-ui.icon name="squares-2x2" class="h-6 w-6" />
                    </div>
                    <div class="ml-5">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Secciones disponibles</p>
                        <p class="text-3xl font-semibold text-slate-900">{{ $sections->count() }}</p>
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-3xl border border-emerald-200/60 bg-white/80 p-6 shadow-lg shadow-emerald-500/10 backdrop-blur">
                <div class="absolute -top-14 -right-10 h-32 w-32 rounded-full bg-emerald-200/50 blur-2xl"></div>
                <div class="relative flex items-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-100 text-emerald-600">
                        <x-ui.icon name="information-circle" class="h-6 w-6" />
                    </div>
                    <div class="ml-5">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Información útil</p>
                        <p class="text-3xl font-semibold text-slate-900">24/7</p>
                    </div>
                </div>
            </div>

            <div class="relative overflow-hidden rounded-3xl border border-purple-200/60 bg-white/80 p-6 shadow-lg shadow-purple-500/10 backdrop-blur">
                <div class="absolute -top-16 -right-8 h-36 w-36 rounded-full bg-purple-200/40 blur-2xl"></div>
                <div class="relative flex items-center">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-purple-100 text-purple-600">
                        <x-ui.icon name="clock" class="h-6 w-6" />
                    </div>
                    <div class="ml-5">
                        <p class="text-xs font-medium uppercase tracking-wide text-slate-500">Última actualización</p>
                        <p class="text-3xl font-semibold text-slate-900">
                            @if($hasSections)
                                {{ $sections->max('updated_at')->format('d/m') }}
                            @else
                                N/A
                            @endif
                        </p>
                    </div>
                </div>
            </div>
        </div>

    @if($hasSections)
        <!-- Índice de Contenidos -->
        <div class="mb-10 rounded-3xl border border-blue-200/70 bg-white/90 shadow-xl shadow-blue-500/10 backdrop-blur">
            <div class="flex items-center justify-between border-b border-blue-100/70 px-8 py-6">
                <div class="flex items-center">
                    <div class="mr-3 flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-100 text-blue-600">
                        <x-ui.icon name="book-open" class="h-5 w-5" />
                    </div>
                    <div>
                        <h2 class="text-lg font-semibold text-slate-900 sm:text-xl">Índice de contenidos</h2>
                        <p class="text-xs text-slate-500">Explora rápidamente las secciones disponibles</p>
                    </div>
                </div>
                <a href="#top" class="hidden text-sm font-medium text-blue-600 transition-colors hover:text-blue-800 sm:inline-flex">Volver al inicio</a>
            </div>
            <div class="grid gap-4 px-8 py-6 md:grid-cols-2">
                @foreach($sections as $section)
                    <a href="#section-{{ $section->id }}"
                       class="group relative overflow-hidden rounded-2xl border border-blue-100/60 bg-white/70 p-4 shadow-sm transition-all duration-300 hover:-translate-y-1 hover:border-blue-200 hover:shadow-lg">
                        <div class="absolute -top-10 -right-10 h-20 w-20 rounded-full bg-blue-100/60 blur-2xl transition-opacity duration-300 group-hover:opacity-100"></div>
                        <div class="relative flex items-start space-x-3">
                            <div class="flex h-12 w-12 flex-shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-500/30">
                                <span class="text-lg font-semibold">{{ $section->section_order }}</span>
                            </div>
                            <div class="flex-1">
                                <p class="text-base font-semibold text-slate-900 group-hover:text-blue-700">{{ $section->title }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ Str::limit(strip_tags($section->content), 80) }}</p>
                            </div>
                            <x-ui.icon name="chevron-right" class="mt-1 h-4 w-4 text-slate-400 transition-transform duration-300 group-hover:translate-x-1 group-hover:text-blue-600" />
                        </div>
                    </a>
                @endforeach
            </div>
        </div>

        <!-- Secciones del Manual -->
        <div class="space-y-10">
            @foreach($sections as $index => $section)
                <div id="section-{{ $section->id }}" class="scroll-mt-24 overflow-hidden rounded-3xl border border-blue-200/60 bg-white/90 shadow-xl shadow-blue-500/10 backdrop-blur">
                    <!-- Header de la Sección -->
                    <div class="border-b border-blue-100/60 bg-gradient-to-r from-blue-50/80 to-white px-6 py-5 sm:px-10 sm:py-6">
                        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center">
                            <div class="flex items-center">
                                <div class="mr-4 flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-blue-600 text-white shadow-lg shadow-blue-500/40">
                                    <span class="text-xl font-semibold">{{ $section->section_order }}</span>
                                </div>
                                <div>
                                    <h2 class="text-2xl font-bold text-slate-900">{{ $section->title }}</h2>
                                    <p class="text-sm text-slate-500">Sección {{ $section->section_order }} de {{ $sections->count() }}</p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-2 text-xs text-slate-500">
                                <x-ui.icon name="calendar" class="h-4 w-4 text-blue-500" />
                                <span>Actualizado: {{ $section->updated_at->format('d/m/Y') }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Contenido de la Sección -->
                    <div class="px-6 py-6 sm:px-10 sm:py-8">
                        <div class="prose prose-lg max-w-none text-slate-700 leading-relaxed">
                            {!! nl2br($section->getProcessedContent()) !!}
                        </div>
                    </div>

                    <!-- Footer de la Sección -->
                    <div class="border-t border-blue-100/60 bg-blue-50/50 px-6 py-4 sm:px-10">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div class="flex items-center text-sm text-slate-600">
                                <x-ui.icon name="clock" class="mr-2 h-4 w-4 text-blue-400" />
                                Actualizado el {{ $section->updated_at->format('d/m/Y \a \l\a\s H:i') }}
                            </div>
                            <div class="flex flex-wrap gap-3 sm:justify-end">
                                <a href="#top" class="inline-flex items-center text-sm font-medium text-slate-500 transition-colors hover:text-blue-700">
                                    <x-ui.icon name="arrow-up" class="mr-1 h-4 w-4" />
                                    Ir al índice
                                </a>
                                @if(!$loop->last)
                                    <a href="#section-{{ $sections[$index + 1]->id }}"
                                       class="inline-flex items-center text-sm font-medium text-blue-600 transition-colors hover:text-blue-800">
                                        Siguiente sección
                                        <x-ui.icon name="chevron-right" class="ml-1 h-4 w-4" />
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Botón para volver arriba -->
        <div class="mt-12 text-center">
            <a href="#top"
               class="inline-flex items-center justify-center rounded-2xl bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition-colors hover:from-blue-700 hover:to-blue-800">
                <x-ui.icon name="arrow-up" class="mr-2 h-4 w-4" />
                Volver al inicio
            </a>
        </div>
    @else
        <!-- Estado vacío -->
        <div class="rounded-3xl border border-blue-200/70 bg-white/90 px-6 py-12 text-center shadow-xl shadow-blue-500/10 backdrop-blur sm:px-10">
            <x-ui.icon name="squares-2x2" class="mx-auto mb-6 h-24 w-24 text-blue-200" />
            <h3 class="mb-4 text-2xl font-semibold text-slate-900">Manual en construcción</h3>
            <p class="mx-auto mb-8 max-w-2xl text-base text-slate-600">
                El manual de ayuda está siendo preparado por nuestro equipo. Pronto estará disponible con toda la información que necesitas para aprovechar el Sistema de Tickets.
            </p>
            <div class="mx-auto max-w-2xl rounded-3xl border border-blue-100/70 bg-gradient-to-br from-blue-50 to-white p-6 text-left shadow-inner">
                <div class="flex items-start gap-4">
                    <div class="flex h-10 w-10 flex-shrink-0 items-center justify-center rounded-2xl bg-blue-100 text-blue-600">
                        <x-ui.icon name="information-circle" class="h-5 w-5" />
                    </div>
                    <div>
                        <h4 class="mb-3 text-sm font-semibold text-blue-900">💡 Mientras tanto, puedes:</h4>
                        <ul class="space-y-2 text-sm text-blue-800">
                            <li class="flex items-start"><span class="mr-2 text-blue-500">•</span>Crear tickets de soporte desde el panel principal.</li>
                            <li class="flex items-start"><span class="mr-2 text-blue-500">•</span>Consultar tus tickets existentes en "Mis Tickets".</li>
                            <li class="flex items-start"><span class="mr-2 text-blue-500">•</span>Explorar el inventario disponible.</li>
                            <li class="flex items-start"><span class="mr-2 text-blue-500">•</span>Contactar al administrador para ayuda directa.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Información adicional -->
    <div class="mt-16 overflow-hidden rounded-3xl border border-blue-200/70 bg-white/90 px-6 py-8 shadow-xl shadow-blue-500/10 backdrop-blur sm:px-10">
        <div class="flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-start gap-4">
                <div class="flex h-14 w-14 flex-shrink-0 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-500 to-blue-600 text-white shadow-lg shadow-blue-500/30">
                    <x-ui.icon name="lifebuoy" class="h-7 w-7" />
                </div>
                <div>
                    <h3 class="text-xl font-semibold text-slate-900">¿No encuentras lo que buscas?</h3>
                    <p class="mt-2 max-w-2xl text-sm text-slate-600">
                        Si no encuentras la información que necesitas en este manual, ponte en contacto con el equipo de soporte para recibir ayuda personalizada.
                    </p>
                </div>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('tickets.create', 'software') }}"
                   class="inline-flex items-center justify-center rounded-2xl bg-gradient-to-r from-blue-600 to-blue-700 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition-colors hover:from-blue-700 hover:to-blue-800">
                    <x-ui.icon name="clipboard-document-list" class="mr-2 h-4 w-4" />
                    Crear ticket de soporte
                </a>
                <a href="mailto:soporte@estrategiaeinnovacion.com.mx"
                   class="inline-flex items-center justify-center rounded-2xl border border-blue-200 bg-white px-5 py-3 text-sm font-semibold text-blue-700 shadow-sm transition-colors hover:border-blue-300 hover:bg-blue-50">
                    <x-ui.icon name="envelope" class="mr-2 h-4 w-4" />
                    Enviar email directo
                </a>
            </div>
        </div>
    </div>
    </div>
</main>

@push('scripts')
    @vite('resources/js/Sistemas_IT/help-index.js')
@endpush

@endsection