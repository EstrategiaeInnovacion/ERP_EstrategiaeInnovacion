@php
    $user = Auth::user();
    
    // 1. Detección de Contexto (¿En qué módulo estoy navegando?)
    $isRH = request()->routeIs('rh.*') || request()->routeIs('recursos-humanos.*');
    $isLogistica = request()->routeIs('logistica.*');
    
    // 2. Definir ruta del Logo (Inicio Inteligente)
    $homeRoute = route('welcome'); // Por defecto
    
    if ($isRH) {
        $homeRoute = route('recursos-humanos.index');
    } elseif ($isLogistica) {
        $homeRoute = route('logistica.index');
    }

    // 3. Datos de usuario
    $initials = $user ? strtoupper(mb_substr($user->name, 0, 1, 'UTF-8')) : 'U';
    $roleLabel = ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) ? 'Administrador' : 'Colaborador';
@endphp

<nav x-data="{ open: false }" class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm/60 backdrop-blur-md bg-white/90">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            
            <div class="flex">
                <div class="shrink-0 flex items-center gap-4">
                    <a href="{{ $homeRoute }}" class="group flex items-center gap-3 transition-opacity hover:opacity-80">
                        <img src="{{ asset('images/logo-ei.png') }}?v={{ filemtime(public_path('images/logo-ei.png')) }}" alt="E&I Logo" class="h-9 w-auto group-hover:scale-105 transition-transform duration-300">
                        
                        <div class="hidden md:block leading-tight border-l-2 border-slate-200 pl-3">
                            @if($isRH)
                                <h1 class="text-sm font-bold text-slate-800 tracking-tight">Recursos Humanos</h1>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Capital Humano</p>
                            @elseif($isLogistica)
                                <h1 class="text-sm font-bold text-slate-800 tracking-tight">Logística y Aduanas</h1>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Operaciones</p>
                            @else
                                <h1 class="text-sm font-bold text-slate-800 tracking-tight">Portal Corporativo</h1>
                                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Estrategia e Innovación</p>
                            @endif
                        </div>
                    </a>
                </div>

                <div class="hidden space-x-2 sm:-my-px sm:ml-10 sm:flex items-center">
                    
                    {{-- MENÚ RH --}}
                    @if($isRH)
                        <x-nav-link :href="route('rh.expedientes.index')" :active="request()->routeIs('rh.expedientes.*')">
                            Expedientes
                        </x-nav-link>
                        <x-nav-link :href="route('rh.reloj.index')" :active="request()->routeIs('rh.reloj.*')">
                            Reloj Checador
                        </x-nav-link>
                        <x-nav-link :href="route('rh.evaluacion.index')" :active="request()->routeIs('rh.evaluacion.*')">
                            Evaluaciones
                        </x-nav-link>
                    
                    {{-- MENÚ LOGÍSTICA --}}
                    @elseif($isLogistica)
                        <x-nav-link :href="route('logistica.index')" :active="request()->routeIs('logistica.index')">
                            Dashboard
                        </x-nav-link>
                        <x-nav-link :href="route('logistica.matriz-seguimiento')" :active="request()->routeIs('logistica.matriz-seguimiento')">
                            Matriz
                        </x-nav-link>
                        <x-nav-link :href="route('logistica.pedimentos.index')" :active="request()->routeIs('logistica.pedimentos.*')">
                            Pedimentos
                        </x-nav-link>
                        <x-nav-link :href="route('logistica.reportes.index')" :active="request()->routeIs('logistica.reportes*')">
                            Reportes
                        </x-nav-link>
                    
                    {{-- MENÚ GENERAL --}}
                    @else
                        @can('ver_rh')
                            <x-nav-link :href="route('recursos-humanos.index')">Ir a RH</x-nav-link>
                        @endcan
                        @can('ver_logistica')
                            <x-nav-link :href="route('logistica.index')">Ir a Logística</x-nav-link>
                        @endcan
                    @endif

                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ml-6">
                @auth
                    <a href="{{ route('welcome', ['from' => 'tickets']) }}" class="mr-4 text-slate-400 hover:text-indigo-600 transition-colors" title="Reportar Problema IT">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636l-3.536 3.536m0 5.656l3.536 3.536M9.172 9.172L5.636 5.636m3.536 9.192l-3.536 3.536M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-5 0a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    </a>

                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="flex items-center gap-3 px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-full text-slate-600 bg-slate-50 hover:bg-white hover:shadow-md hover:text-indigo-600 transition-all duration-200 focus:outline-none ring-1 ring-slate-100">
                                <div class="text-right hidden md:block">
                                    <div class="font-bold text-slate-800">{{ Auth::user()->name }}</div>
                                    <div class="text-[10px] uppercase tracking-wider text-indigo-500 font-bold">{{ $roleLabel }}</div>
                                </div>
                                <div class="h-9 w-9 rounded-full bg-indigo-600 flex items-center justify-center text-white font-bold text-sm shadow-sm shadow-indigo-200">
                                    {{ $initials }}
                                </div>
                                <svg class="fill-current h-4 w-4 text-slate-400" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="px-4 py-3 border-b border-slate-100">
                                <p class="text-sm text-slate-500">Conectado como</p>
                                <p class="text-sm font-bold text-slate-900 truncate">{{ Auth::user()->email }}</p>
                            </div>

                            <x-dropdown-link :href="route('profile.edit')" class="group flex items-center">
                                <svg class="mr-2 h-4 w-4 text-slate-400 group-hover:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                {{ __('Mi Perfil') }}
                            </x-dropdown-link>

                            {{-- Enlace dinámico "Inicio" dentro del dropdown también --}}
                            <x-dropdown-link :href="$homeRoute" class="group flex items-center">
                                <svg class="mr-2 h-4 w-4 text-slate-400 group-hover:text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                {{ __('Inicio') }}
                            </x-dropdown-link>

                            <div class="border-t border-slate-100 my-1"></div>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();"
                                        class="text-red-600 hover:bg-red-50 hover:text-red-700 flex items-center">
                                    <svg class="mr-2 h-4 w-4 text-red-400 group-hover:text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                    {{ __('Cerrar Sesión') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @else
                    <a href="{{ route('login') }}" class="text-sm font-semibold text-slate-600 hover:text-indigo-600">Iniciar Sesión</a>
                @endauth
            </div>

            <div class="-mr-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 focus:outline-none transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-slate-50 border-t border-slate-200 shadow-inner">
        <div class="pt-2 pb-3 space-y-1">
            @if($isRH)
                <x-responsive-nav-link :href="route('recursos-humanos.index')" :active="request()->routeIs('recursos-humanos.index')">
                    Dashboard RH
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('rh.reloj.index')" :active="request()->routeIs('rh.reloj.*')">
                    Reloj Checador
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('rh.evaluacion.index')" :active="request()->routeIs('rh.evaluacion.*')">
                    Evaluaciones
                </x-responsive-nav-link>
            @elseif($isLogistica)
                <x-responsive-nav-link :href="route('logistica.index')" :active="request()->routeIs('logistica.index')">
                    Dashboard Logística
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('logistica.matriz-seguimiento')" :active="request()->routeIs('logistica.matriz-seguimiento')">
                    Matriz
                </x-responsive-nav-link>
            @else
                <x-responsive-nav-link :href="route('welcome')">Inicio</x-responsive-nav-link>
            @endif
        </div>

        @auth
        <div class="pt-4 pb-4 border-t border-slate-200 bg-white">
            <div class="px-4 flex items-center">
                <div class="shrink-0">
                    <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-700 font-bold border border-indigo-200">
                        {{ $initials }}
                    </div>
                </div>
                <div class="ml-3">
                    <div class="font-medium text-base text-slate-800">{{ Auth::user()->name }}</div>
                    <div class="font-medium text-sm text-slate-500">{{ Auth::user()->email }}</div>
                </div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Mi Perfil') }}
                </x-responsive-nav-link>
                
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <x-responsive-nav-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();"
                            class="text-red-600">
                        {{ __('Cerrar Sesión') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
        @endauth
    </div>
</nav>