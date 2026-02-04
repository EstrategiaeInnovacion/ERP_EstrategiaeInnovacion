<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'ERP Corporativo') | Estrategia e Innovación</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    @stack('styles')
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-600">
    
    <div class="min-h-screen flex flex-col">
        @include('layouts.erp-navigation')

        <main class="flex-grow">
            <div class="py-8">
                @if(session('success') || session('error'))
                    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 mb-6">
                        @if(session('success'))
                            <div class="rounded-xl bg-emerald-50 border border-emerald-200 p-4 flex items-center gap-3 text-emerald-700 shadow-sm" role="alert">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span class="font-medium text-sm">{{ session('success') }}</span>
                            </div>
                        @endif
                        @if(session('error'))
                            <div class="rounded-xl bg-red-50 border border-red-200 p-4 flex items-center gap-3 text-red-700 shadow-sm" role="alert">
                                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                <span class="font-medium text-sm">{{ session('error') }}</span>
                            </div>
                        @endif
                    </div>
                @endif

                @yield('content')
            </div>
        </main>

        <footer class="bg-white border-t border-slate-200 mt-auto py-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 flex flex-col md:flex-row justify-between items-center gap-4">
                <div class="text-center md:text-left">
                    <p class="text-xs text-slate-400">
                        &copy; {{ date('Y') }} Estrategia e Innovación. <span class="hidden sm:inline">Todos los derechos reservados.</span>
                    </p>
                </div>
                <div class="flex items-center gap-4 opacity-50 grayscale hover:grayscale-0 transition-all duration-500">
                    <img src="{{ asset('images/logo-ei.png') }}" alt="E&I" class="h-6 w-auto">
                </div>
            </div>
        </footer>
    </div>

    @stack('scripts')
</body>
</html>