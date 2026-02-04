<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'ERP E&I') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700,800" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-slate-600 bg-slate-50">
        
        <div class="min-h-screen flex flex-col justify-center items-center py-6 sm:pt-0 bg-gradient-to-br from-slate-50 via-white to-indigo-50 relative overflow-hidden">
            
            {{-- Decoración de fondo (esferas difuminadas) --}}
            <div class="absolute top-0 left-0 -mt-20 -ml-20 w-96 h-96 bg-indigo-100 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob"></div>
            <div class="absolute top-0 right-0 -mt-20 -mr-20 w-96 h-96 bg-blue-100 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-2000"></div>
            <div class="absolute -bottom-32 left-20 w-96 h-96 bg-purple-100 rounded-full mix-blend-multiply filter blur-3xl opacity-30 animate-blob animation-delay-4000"></div>

            <div class="w-full sm:max-w-md mt-6 px-6 relative z-10">
                {{-- Logo o Título Superior --}}
                <div class="flex justify-center mb-8">
                    <div class="bg-white p-3 rounded-2xl shadow-lg shadow-indigo-100 border border-slate-100">
                        <img src="{{ asset('images/logo-ei.png') }}" alt="Logo" class="h-12 w-auto">
                    </div>
                </div>

                {{-- Tarjeta Principal --}}
                <div class="bg-white shadow-2xl shadow-slate-200/50 overflow-hidden sm:rounded-3xl border border-slate-100 relative">
                    <div class="absolute top-0 left-0 w-full h-1.5 bg-gradient-to-r from-indigo-500 via-purple-500 to-blue-500"></div>
                    <div class="px-8 py-10">
                        {{ $slot }}
                    </div>
                </div>

                {{-- Footer pequeño --}}
                <div class="mt-8 text-center text-xs text-slate-400 font-medium">
                    &copy; {{ date('Y') }} Estrategia e Innovación. <br>Todos los derechos reservados.
                </div>
            </div>
        </div>
    </body>
</html>