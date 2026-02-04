@props([
    'theme' => 'blue',
    'notifications' => collect(),
    'notificationsCount' => null,
])

@php
    $notifications = collect($notifications ?? []);
    $notificationsCount = $notificationsCount ?? $notifications->count();

    $themeStyles = [
        'blue' => [
            'hover' => 'hover:bg-blue-50',
            'accent' => 'text-blue-600',
            'avatar' => 'bg-blue-600',
            'badge' => 'bg-blue-600',
            'ring' => 'focus-visible:ring-blue-500',
        ],
        'green' => [
            'hover' => 'hover:bg-green-50',
            'accent' => 'text-green-600',
            'avatar' => 'bg-green-600',
            'badge' => 'bg-green-600',
            'ring' => 'focus-visible:ring-green-500',
        ],
        'orange' => [
            'hover' => 'hover:bg-orange-50',
            'accent' => 'text-orange-600',
            'avatar' => 'bg-orange-500',
            'badge' => 'bg-orange-500',
            'ring' => 'focus-visible:ring-orange-500',
        ],
        'purple' => [
            'hover' => 'hover:bg-purple-50',
            'accent' => 'text-purple-600',
            'avatar' => 'bg-purple-600',
            'badge' => 'bg-purple-600',
            'ring' => 'focus-visible:ring-purple-500',
        ],
    ];

    $styles = $themeStyles[$theme] ?? $themeStyles['blue'];
@endphp

@auth
<div {{ $attributes->merge(['class' => 'w-full']) }}>
    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-end lg:gap-6">
        <x-nav-links :theme="$theme" class="w-full justify-center lg:w-auto lg:justify-end lg:ml-6" />

        <div class="flex flex-wrap items-center justify-center gap-3 lg:justify-end lg:gap-4">
            <!-- Notifications -->
            <div
                class="relative"
                x-data="{ open: false }"
                @click.outside="open = false"
                @keydown.escape.window="open = false"
            >
                <button
                    type="button"
                    @click="open = !open"
                    class="relative inline-flex items-center justify-center w-10 h-10 rounded-lg bg-white border border-gray-200 shadow-sm {{ $styles['hover'] }} focus:outline-none focus-visible:ring-2 {{ $styles['ring'] }} focus-visible:ring-offset-2"
                    aria-label="Abrir notificaciones"
                    :aria-expanded="open"
                >
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                    </svg>
                    @if($notificationsCount > 0)
                        <span class="absolute -top-1 -right-1 inline-flex items-center justify-center h-5 min-w-[1.25rem] px-1 text-xs font-semibold text-white rounded-full {{ $styles['badge'] }}">
                            {{ $notificationsCount }}
                        </span>
                    @endif
                </button>

                <div
                    x-cloak
                    x-show="open"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute right-0 mt-3 w-80 max-h-96 overflow-hidden bg-white border border-gray-200 rounded-xl shadow-xl z-50"
                >
                    <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                        <div>
                            <p class="text-sm font-semibold text-gray-900">Notificaciones</p>
                            <p class="text-xs text-gray-500">Actualizaciones de tus tickets</p>
                        </div>
                        <span class="text-xs font-semibold text-white px-2 py-1 rounded-full {{ $styles['badge'] }}">
                            {{ $notificationsCount }}
                        </span>
                    </div>

                    <div class="max-h-72 overflow-y-auto">
                        @if($notificationsCount === 0)
                            <div class="px-4 py-8 text-center text-gray-500">
                                <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                                <p class="text-sm">No tienes notificaciones pendientes</p>
                            </div>
                        @else
                            <div class="px-4 py-2 border-b border-gray-100 bg-white">
                                <form method="POST" action="{{ route('tickets.acknowledge-all') }}">
                                    @csrf
                                    <button type="submit" class="w-full inline-flex items-center justify-center text-xs font-medium text-white px-3 py-2 rounded-lg {{ $styles['badge'] }} hover:opacity-90 focus:outline-none focus-visible:ring-2 {{ $styles['ring'] }} focus-visible:ring-offset-2">
                                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                        </svg>
                                        Marcar todas como revisadas
                                    </button>
                                </form>
                            </div>

                            <ul class="divide-y divide-gray-100">
                                @foreach($notifications as $notification)
                                    <li class="px-4 py-3 hover:bg-gray-50">
                                        <div class="flex items-start space-x-3">
                                            <div class="flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center bg-gray-100 text-gray-500">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="flex items-start justify-between">
                                                    <div>
                                                        <p class="text-sm font-semibold text-gray-900">
                                                            Ticket {{ $notification['folio'] ?? '#' . $notification['id'] }}
                                                        </p>
                                                        <p class="text-xs text-gray-500 mt-0.5">{{ $notification['timestamp'] ?? '' }}</p>
                                                    </div>
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-700">
                                                        {{ $notification['estado'] ?? 'Actualización' }}
                                                    </span>
                                                </div>
                                                <p class="mt-2 text-sm text-gray-700 leading-snug">
                                                    {{ $notification['summary'] ?? 'Este ticket tiene nuevas actualizaciones del equipo de TI.' }}
                                                </p>
                                                <div class="mt-3 flex flex-wrap items-center gap-2">
                                                    <a href="{{ $notification['link'] ?? route('tickets.mis-tickets') }}" class="inline-flex items-center text-xs font-medium text-gray-600 {{ $styles['hover'] }} px-2.5 py-1 rounded-lg border border-gray-200">
                                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 7l5 5m0 0l-5 5m5-5H6" />
                                                        </svg>
                                                        Ver ticket
                                                    </a>
                                                    <form method="POST" action="{{ $notification['acknowledgeUrl'] ?? '#' }}" class="inline">
                                                        @csrf
                                                        <button type="submit" class="inline-flex items-center text-xs font-medium text-white px-2.5 py-1 rounded-lg {{ $styles['badge'] }} hover:opacity-90 focus:outline-none focus-visible:ring-2 {{ $styles['ring'] }} focus-visible:ring-offset-2">
                                                            <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                                            </svg>
                                                            Marcar leído
                                                        </button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </li>
                                @endforeach
                            </ul>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Profile Dropdown -->
            <div class="relative" x-data="{ open: false }">
                <button
                    type="button"
                    @click="open = !open"
                    @click.outside="open = false"
                    class="flex items-center space-x-3 px-3 py-2 rounded-lg bg-white border border-gray-200 shadow-sm {{ $styles['hover'] }} focus:outline-none focus-visible:ring-2 {{ $styles['ring'] }} focus-visible:ring-offset-2"
                >
                    <div class="w-9 h-9 rounded-full flex items-center justify-center text-white {{ $styles['avatar'] }}">
                        <span class="text-sm font-semibold">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
                    </div>
                    <div class="text-left">
                        <p class="text-sm font-semibold text-gray-900 leading-none">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
                    </div>
                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </button>

                <div
                    x-cloak
                    x-show="open"
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="transform opacity-0 scale-95"
                    x-transition:enter-end="transform opacity-100 scale-100"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="transform opacity-100 scale-100"
                    x-transition:leave-end="transform opacity-0 scale-95"
                    class="absolute right-0 mt-3 w-64 bg-white border border-gray-200 rounded-xl shadow-xl z-50"
                >
                    <div class="px-4 py-3 border-b border-gray-100">
                        <p class="text-sm font-semibold text-gray-900">{{ auth()->user()->name }}</p>
                        <p class="text-xs text-gray-500">{{ auth()->user()->email }}</p>
                        <span class="mt-2 inline-flex items-center px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600">
                            {{ auth()->user()->isAdmin() ? 'Administrador TI' : 'Usuario' }}
                        </span>
                    </div>

                    <div class="py-2">
                        <a href="{{ route('profile.edit') }}" class="flex items-center px-4 py-2 text-sm text-gray-600 hover:bg-gray-50 {{ $styles['hover'] }} rounded-lg mx-2">
                            <svg class="w-5 h-5 mr-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5h2m-1 0v2m0 4v6m4-6h2m-6 0H7m6 6h-2m0 0v-2m0-4V5m6 14H7a2 2 0 01-2-2V7a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V17a2 2 0 01-2 2z" />
                            </svg>
                            Mi perfil
                        </a>
                        <form method="POST" action="{{ route('logout') }}" class="mt-1">
                            @csrf
                            <button type="submit" class="w-full flex items-center px-4 py-2 text-sm text-red-600 hover:bg-red-50 rounded-lg transition-colors duration-150">
                                <svg class="w-5 h-5 mr-3 text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4" />
                                </svg>
                                Cerrar sesión
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endauth
