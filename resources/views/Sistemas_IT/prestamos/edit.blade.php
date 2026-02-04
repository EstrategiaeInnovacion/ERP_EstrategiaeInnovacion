<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            Editar préstamo #{{ $prestamoId }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <form method="POST" action="{{ route('prestamos.update', $prestamoId) }}" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div>
                            <x-input-label for="solicitante" value="Nombre del solicitante" />
                            <x-text-input
                                id="solicitante"
                                name="solicitante"
                                type="text"
                                class="mt-1 block w-full"
                                value="{{ old('solicitante', 'Dato demostrativo') }}"
                                required
                            />
                            <x-input-error :messages="$errors->get('solicitante')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="descripcion" value="Descripción del préstamo" />
                            <textarea
                                id="descripcion"
                                name="descripcion"
                                rows="4"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                            >{{ old('descripcion', 'Detalle demostrativo del préstamo registrado.') }}</textarea>
                            <x-input-error :messages="$errors->get('descripcion')" class="mt-2" />
                        </div>

                        <div>
                            <x-input-label for="fecha_devolucion_estimada" value="Fecha de devolución estimada" />
                            <x-text-input
                                id="fecha_devolucion_estimada"
                                name="fecha_devolucion_estimada"
                                type="date"
                                class="mt-1 block w-full"
                                value="{{ old('fecha_devolucion_estimada') }}"
                            />
                            <x-input-error :messages="$errors->get('fecha_devolucion_estimada')" class="mt-2" />
                        </div>

                        <div class="flex items-center justify-between gap-3">
                            <a
                                href="{{ route('prestamos.show', $prestamoId) }}"
                                class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-gray-700 bg-gray-100 hover:bg-gray-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-400"
                            >
                                Volver a detalles
                            </a>

                            <x-primary-button>
                                Guardar cambios
                            </x-primary-button>
                        </div>
                    </form>

                    <div class="flex items-center justify-end mt-6">
                        <form method="POST" action="{{ route('prestamos.destroy', $prestamoId) }}">
                            @csrf
                            @method('DELETE')

                            <x-danger-button onclick="return confirm('¿Deseas eliminar este préstamo?')">
                                Eliminar
                            </x-danger-button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
