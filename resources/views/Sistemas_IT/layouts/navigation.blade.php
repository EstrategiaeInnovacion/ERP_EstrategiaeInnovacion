@php
    $user = Auth::user();
    $area = optional($user?->empleado)->area;
    $isRHContext = request()->routeIs('recursos-humanos.index');
    $isLogisticaContext = request()->routeIs('logistica.index');

    // Determinar ruta de regreso al panel del área (si aplica)
    $backToPanelRoute = null;
    if ($area) {
        $areaNorm = mb_strtolower(preg_replace('/\s+/u', ' ', $area), 'UTF-8');
        if ($areaNorm === 'rh' || $areaNorm === 'recursos humanos') {
            $backToPanelRoute = route('recursos-humanos.index');
        } elseif ($areaNorm === 'logistica' || $areaNorm === 'logística') {
            $backToPanelRoute = route('logistica.index');
        }
    }

    if ($isRHContext || $isLogisticaContext) {
        // Contexto RH/Logística: solo soporte técnico
        $navItems = [
            [
                'label' => 'Soporte Técnico',
                'route' => route('tickets.mis-tickets'),
                'active' => request()->routeIs('tickets.*'),
                'visible' => true,
            ],
        ];
    } else {
        // Contexto General / IT
        $navItems = [
            [
                'label' => 'Regresar a Panel',
                'route' => $backToPanelRoute ?? '#',
                'active' => false,
                'visible' => !$isRHContext && !$isLogisticaContext && !is_null($backToPanelRoute),
            ],
            [
                'label' => 'Inicio',
                'route' => route('welcome', ['from' => 'tickets']),
                'active' => request()->routeIs('welcome'),
                'visible' => true,
            ],
            [
                'label' => $user ? ($user->getPanelInfo()['label'] ?? 'Panel Admin') : 'Panel Admin',
                'route' => $user && $user->getPanelInfo()['available'] ? $user->getPanelInfo()['route'] : route('admin.dashboard'),
                'active' => request()->routeIs('admin.*') || request()->routeIs('recursos-humanos.*') || request()->routeIs('logistica.*'),
                'visible' => $user && $user->getPanelInfo()['available'],
            ],
            [
                'label' => 'Mis Tickets',
                'route' => route('tickets.mis-tickets'),
                'active' => request()->routeIs('tickets.*'),
                'visible' => true,
            ],
        ];
    }

    $filteredItems = array_filter($navItems, fn ($item) => $item['visible']);
    $initials = $user ? strtoupper(mb_substr($user->name, 0, 1, 'UTF-8')) : 'U';
    
    if ($user && method_exists($user, 'isAdmin') && $user->isAdmin()) {
        $roleLabel = match ($area) {
            'Sistemas' => 'Admin Sistemas',
            'Logistica' => 'Admin Logística',
            'RH' => 'Admin RH',
            default => 'Administrador',
        };
    } else {
        $roleLabel = 'Usuario';
    }
@endphp

<nav x-data="{ open: false }" class="bg-white border-b border-slate-200 sticky top-0 z-50 shadow-sm/50">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center gap-3">
                    <a href="{{ route('welcome', ['from' => 'tickets']) }}" class="group flex items-center gap-3">
                        
                        {{-- LOGO ORIGINAL RESTAURADO --}}
                        <div class="group-hover:scale-105 transition-transform duration-300">
                            <img src="{{ asset('images/logo-ei.png') }}?v={{ filemtime(public_path('images/logo-ei.png')) }}" alt="E&I Logo" class="h-10 w-auto">
                        </div>

                        <div class="hidden md:block leading-tight border-l border-slate-200 pl-3 ml-1">
                            @if($isRHContext)
                                <h1 class="text-sm font-bold text-slate-800">Recursos Humanos</h1>
                                <p class="text-[10px] text-slate-400 font-medium uppercase tracking-wider">Gestión de Personal</p>
                            @elseif($isLogisticaContext)
                                <h1 class="text-sm font-bold text-slate-800">Logística</h1>
                                <p class="text-[10px] text-slate-400 font-medium uppercase tracking-wider">Operaciones</p>
                            @else
                                <h1 class="text-sm font-bold text-slate-800">Sistemas IT</h1>
                                <p class="text-[10px] text-slate-400 font-medium uppercase tracking-wider">Help Desk</p>
                            @endif
                        </div>
                    </a>
                </div>

                <div class="hidden space-x-8 sm:-my-px sm:ml-10 sm:flex">
                    @foreach ($filteredItems as $item)
                        @php
                            $classes = ($item['active'] ?? false)
                                        ? 'inline-flex items-center px-1 pt-1 border-b-2 border-indigo-500 text-sm font-medium text-slate-900 focus:outline-none focus:border-indigo-700 transition duration-150 ease-in-out'
                                        : 'inline-flex items-center px-1 pt-1 border-b-2 border-transparent text-sm font-medium text-slate-500 hover:text-indigo-600 hover:border-indigo-300 focus:outline-none focus:text-slate-700 focus:border-slate-300 transition duration-150 ease-in-out';
                        @endphp
                        <a href="{{ $item['route'] }}" class="{{ $classes }}">
                            {{ $item['label'] }}
                        </a>
                    @endforeach
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ml-6">
                
                @if (!$isRHContext && !$isLogisticaContext && $user && method_exists($user, 'isAdmin') && $user->isAdmin() && (
                    optional($user->empleado)->area === 'Sistemas' || 
                    optional($user->empleado)->posicion === 'TI' || 
                    optional($user->empleado)->posicion === 'IT'
                ))
                    <div class="mr-4">
                        <x-admin.notification-center />
                    </div>
                @endif

                @auth
                    <x-dropdown align="right" width="48">
                        <x-slot name="trigger">
                            <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-slate-500 bg-white hover:text-indigo-600 focus:outline-none transition ease-in-out duration-150">
                                <div class="text-right mr-3">
                                    <div class="font-bold text-slate-700">{{ Auth::user()->name }}</div>
                                    <div class="text-[10px] uppercase tracking-wider text-indigo-500 font-bold">{{ $roleLabel }}</div>
                                </div>
                                <div class="h-9 w-9 rounded-full bg-indigo-50 flex items-center justify-center text-indigo-600 font-bold border border-indigo-100 shadow-sm">
                                    {{ $initials }}
                                </div>
                                <div class="ml-1">
                                    <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                    </svg>
                                </div>
                            </button>
                        </x-slot>

                        <x-slot name="content">
                            <div class="block px-4 py-2 text-xs text-slate-400 font-bold uppercase tracking-wider">
                                {{ __('Mi Cuenta') }}
                            </div>

                            <x-dropdown-link :href="route('profile.edit')" class="hover:bg-indigo-50 hover:text-indigo-600">
                                {{ __('Perfil') }}
                            </x-dropdown-link>

                            <div class="border-t border-slate-100"></div>

                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <x-dropdown-link :href="route('logout')"
                                        onclick="event.preventDefault(); this.closest('form').submit();"
                                        class="text-red-600 hover:bg-red-50 hover:text-red-700">
                                    {{ __('Cerrar Sesión') }}
                                </x-dropdown-link>
                            </form>
                        </x-slot>
                    </x-dropdown>
                @endauth
            </div>

            <div class="-mr-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 focus:outline-none transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden bg-slate-50 border-t border-slate-200">
        <div class="pt-2 pb-3 space-y-1">
            @foreach ($filteredItems as $item)
                @php
                    $responsiveClasses = ($item['active'] ?? false)
                        ? 'block w-full pl-3 pr-4 py-2 border-l-4 border-indigo-400 text-left text-base font-medium text-indigo-700 bg-indigo-50 focus:outline-none focus:text-indigo-800 focus:bg-indigo-100 focus:border-indigo-700 transition duration-150 ease-in-out'
                        : 'block w-full pl-3 pr-4 py-2 border-l-4 border-transparent text-left text-base font-medium text-slate-600 hover:text-indigo-800 hover:bg-indigo-50 hover:border-indigo-300 focus:outline-none focus:text-indigo-800 focus:bg-indigo-50 focus:border-indigo-300 transition duration-150 ease-in-out';
                @endphp
                <a href="{{ $item['route'] }}" class="{{ $responsiveClasses }}">
                    {{ $item['label'] }}
                </a>
            @endforeach
        </div>

        @auth
            <div class="pt-4 pb-1 border-t border-slate-200">
                <div class="px-4 flex items-center">
                    <div class="flex-shrink-0">
                        <div class="h-10 w-10 rounded-full bg-indigo-100 flex items-center justify-center text-indigo-600 font-bold border border-indigo-200">
                            {{ $initials }}
                        </div>
                    </div>
                    <div class="ml-3">
                        <div class="font-medium text-base text-slate-800">{{ Auth::user()->name }}</div>
                        <div class="font-medium text-sm text-slate-500">{{ Auth::user()->email }}</div>
                    </div>
                </div>

                <div class="mt-3 space-y-1">
                    <x-responsive-nav-link :href="route('profile.edit')" class="text-slate-600">
                        {{ __('Perfil') }}
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