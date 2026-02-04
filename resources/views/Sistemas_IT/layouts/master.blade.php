<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Sistemas IT') | Estrategia e Innovación</title>

    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

    @vite(['resources/css/app.css', 'resources/js/app.js'])

    {{-- AQUI ESTABA EL FALTANTE: --}}
    @stack('styles') 
</head>
<body class="font-sans antialiased bg-slate-50 text-slate-600">
    
    <div class="min-h-screen flex flex-col">
        @include('Sistemas_IT.layouts.navigation')

        <main class="flex-grow">
            <div class="py-8">
                <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
                    @yield('content')
                </div>
            </div>
        </main>

        <footer class="bg-white border-t border-slate-200 mt-auto py-6">
            <div class="max-w-7xl mx-auto px-4 text-center">
                <p class="text-xs text-slate-400">
                    &copy; {{ date('Y') }} Estrategia e Innovación. Departamento de Sistemas.
                </p>
            </div>
        </footer>
    </div>

    @stack('scripts')
</body>
</html>