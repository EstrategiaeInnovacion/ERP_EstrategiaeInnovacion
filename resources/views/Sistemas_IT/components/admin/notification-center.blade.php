<div
    {{ $attributes->merge(['class' => 'relative']) }}
    x-data="adminNotificationCenter()"
    x-init="init()"
>
    <button
        type="button"
        @click="toggle()"
        @click.outside="close()"
        class="relative inline-flex items-center justify-center w-10 h-10 rounded-lg bg-white border border-gray-200 shadow-sm hover:bg-blue-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-blue-500 focus-visible:ring-offset-2"
        aria-label="Abrir notificaciones"
    >
        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
        </svg>

        <template x-if="unreadCount > 0">
            <span
                class="absolute -top-1 -right-1 inline-flex items-center justify-center h-5 min-w-[1.25rem] px-1 text-xs font-semibold text-white rounded-full bg-blue-600"
                x-text="unreadCount > 99 ? '99+' : unreadCount"
            ></span>
        </template>
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
        class="absolute right-0 mt-3 w-80 sm:w-96 max-h-96 bg-white border border-gray-200 rounded-xl shadow-xl z-50 overflow-hidden"
    >
        <div class="px-4 py-3 border-b border-gray-100 flex items-center justify-between bg-gray-50">
            <div>
                <p class="text-sm font-semibold text-gray-900">Notificaciones</p>
                <p class="text-xs text-gray-500">Actualizaciones de tickets entrantes</p>
            </div>
            <div class="flex items-center space-x-2">
                <span class="text-xs font-semibold text-white px-2 py-1 rounded-full bg-blue-600" x-text="unreadCount"></span>
                <button
                    type="button"
                    class="text-xs font-medium text-blue-600 hover:text-blue-800"
                    x-show="unreadCount > 0"
                    @click.prevent="markAllAsRead()"
                >
                    Marcar todas
                </button>
            </div>
        </div>

        <div class="max-h-72 overflow-y-auto">
            <template x-if="loading">
                <div class="px-4 py-8 text-center text-gray-500">
                    <svg class="w-10 h-10 mx-auto mb-3 animate-spin text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6l4 2" />
                    </svg>
                    <p class="text-sm">Cargando notificaciones...</p>
                </div>
            </template>

            <template x-if="!loading && notifications.length === 0">
                <div class="px-4 py-8 text-center text-gray-500">
                    <svg class="w-10 h-10 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <p class="text-sm">No tienes notificaciones nuevas</p>
                </div>
            </template>

            <template x-for="notification in notifications" :key="notification.id">
                <div
                    class="px-4 py-3 border-b border-gray-100 hover:bg-gray-50 transition-colors duration-150 cursor-pointer"
                    @click="openTicket(notification)"
                >
                    <div class="flex items-start space-x-3">
                        <div class="flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center bg-blue-100 text-blue-600">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-semibold text-gray-900" x-text="'Ticket #' + notification.folio"></p>
                                <p class="text-xs text-gray-500" x-text="notification.fecha"></p>
                            </div>
                            <p class="text-xs text-gray-600 mt-1" x-text="notification.solicitante"></p>
                            <p class="text-sm text-gray-800 mt-1" x-text="notification.descripcion"></p>
                            <div class="mt-2 inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium"
                                 :class="{
                                    'bg-blue-100 text-blue-800': notification.tipo === 'Software',
                                    'bg-orange-100 text-orange-800': notification.tipo === 'Hardware',
                                    'bg-green-100 text-green-800': notification.tipo === 'Mantenimiento'
                                 }"
                                 x-text="notification.tipo"
                            ></div>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        <div class="px-4 py-3 bg-gray-50 border-t border-gray-100">
            <a href="{{ route('admin.tickets.index') }}" class="block text-center text-sm text-blue-600 hover:text-blue-800 font-medium">
                Ver todos los tickets
            </a>
        </div>
    </div>
</div>

{{-- Script now imported globally via resources/js/app.js --}}
