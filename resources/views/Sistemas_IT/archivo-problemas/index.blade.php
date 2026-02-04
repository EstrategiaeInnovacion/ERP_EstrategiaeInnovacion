@extends('layouts.master')

@section('title', 'Archivo de Problemas - E&I Sistema de Tickets')

@section('content')
        <main class="max-w-7xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex justify-between items-center mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">
                            <svg class="w-6 h-6 inline-block mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path>
                            </svg>
                            Archivo de Problemas
                        </h2>
                        <div class="flex flex-col sm:flex-row gap-2 sm:gap-3">
                            @if (Auth::user()->isAdmin())
                                <button onclick="toggleCreateTicketModal()" 
                                       class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 inline-flex items-center justify-center text-sm">
                                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                    </svg>
                                    Crear Ticket y Archivar
                                </button>
                            @endif
                            <a href="{{ route('archivo-problemas.create') }}" 
                               class="bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 inline-flex items-center justify-center text-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                Archivar Problema
                            </a>
                            <a href="{{ route('archivo-problemas.estadisticas') }}" 
                               class="bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200 inline-flex items-center justify-center text-sm">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                                </svg>
                                Estadísticas
                            </a>
                        </div>
                    </div>

                <!-- Filtros de búsqueda -->
                <div class="bg-gray-50 p-4 rounded-lg mb-6">
                    <form method="GET" action="{{ route('archivo-problemas.index') }}" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Buscar</label>
                            <input type="text" 
                                   name="busqueda" 
                                   value="{{ request('busqueda') }}"
                                   placeholder="Título, descripción, solución..."
                                   class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                            <select name="categoria" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todas las categorías</option>
                                @foreach($categorias as $categoria)
                                    <option value="{{ $categoria }}" {{ request('categoria') == $categoria ? 'selected' : '' }}>
                                        {{ $categoria }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tipo</label>
                            <select name="tipo_problema" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todos los tipos</option>
                                @foreach($tipos as $tipo)
                                    <option value="{{ $tipo }}" {{ request('tipo_problema') == $tipo ? 'selected' : '' }}>
                                        {{ $tipo }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Frecuencia</label>
                            <select name="frecuencia" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                <option value="">Todas</option>
                                <option value="unico" {{ request('frecuencia') == 'unico' ? 'selected' : '' }}>Único</option>
                                <option value="ocasional" {{ request('frecuencia') == 'ocasional' ? 'selected' : '' }}>Ocasional</option>
                                <option value="frecuente" {{ request('frecuencia') == 'frecuente' ? 'selected' : '' }}>Frecuente</option>
                                <option value="critico" {{ request('frecuencia') == 'critico' ? 'selected' : '' }}>Crítico</option>
                            </select>
                        </div>

                        <div class="flex items-end space-x-2">
                            <button type="submit" 
                                    class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-200">
                                🔍 Buscar
                            </button>
                            <a href="{{ route('archivo-problemas.index') }}" 
                               class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded transition duration-200">
                                🔄 Limpiar
                            </a>
                        </div>
                    </form>
                </div>

                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                @if(session('error'))
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        {{ session('error') }}
                    </div>
                @endif

                <!-- Resumen de resultados -->
                <div class="mb-4 text-sm text-gray-600">
                    Mostrando {{ $problemas->firstItem() ?? 0 }} - {{ $problemas->lastItem() ?? 0 }} de {{ $problemas->total() }} problemas
                </div>

                <!-- Lista de problemas -->
                @if($problemas->count() > 0)
                    <div class="grid gap-4">
                        @foreach($problemas as $problema)
                            <div class="bg-white border rounded-lg shadow-sm hover:shadow-md transition duration-200">
                                <div class="p-4">
                                    <div class="flex justify-between items-start mb-3">
                                        <div class="flex-1">
                                            <h3 class="text-lg font-semibold text-gray-900 mb-1">
                                                <a href="{{ route('archivo-problemas.show', $problema->id) }}" 
                                                   class="hover:text-blue-600 transition duration-200">
                                                    {{ $problema->titulo }}
                                                </a>
                                            </h3>
                                            <p class="text-sm text-gray-600">
                                                Ticket #{{ $problema->ticket->id }} - {{ $problema->ticket->asunto }}
                                            </p>
                                        </div>
                                        <div class="text-right">
                                            @php
                                                $frecuenciaColors = [
                                                    'unico' => 'bg-blue-100 text-blue-800',
                                                    'ocasional' => 'bg-yellow-100 text-yellow-800',
                                                    'frecuente' => 'bg-orange-100 text-orange-800',
                                                    'critico' => 'bg-red-100 text-red-800'
                                                ];
                                            @endphp
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $frecuenciaColors[$problema->frecuencia] ?? 'bg-gray-100 text-gray-800' }}">
                                                {{ ucfirst($problema->frecuencia) }}
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-3">
                                        <div>
                                            <span class="text-xs font-medium text-gray-500">Categoría:</span>
                                            <p class="text-sm text-gray-900">{{ $problema->categoria }}</p>
                                        </div>
                                        <div>
                                            <span class="text-xs font-medium text-gray-500">Tipo:</span>
                                            <p class="text-sm text-gray-900">{{ $problema->tipo_problema }}</p>
                                        </div>
                                        <div>
                                            <span class="text-xs font-medium text-gray-500">Archivado por:</span>
                                            <p class="text-sm text-gray-900">{{ $problema->archivadoPor->name }}</p>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <span class="text-xs font-medium text-gray-500">Descripción del problema:</span>
                                        <p class="text-sm text-gray-900 line-clamp-2">{{ Str::limit($problema->descripcion_problema, 150) }}</p>
                                    </div>

                                    @if($problema->palabras_clave && count($problema->palabras_clave) > 0)
                                        <div class="mb-3">
                                            <span class="text-xs font-medium text-gray-500">Palabras clave:</span>
                                            <div class="flex flex-wrap gap-1 mt-1">
                                                @foreach($problema->palabras_clave as $palabra)
                                                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-800">
                                                        {{ $palabra }}
                                                    </span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif

                                    <div class="flex justify-between items-center">
                                        <span class="text-xs text-gray-500">
                                            📅 {{ $problema->fecha_archivo->format('d/m/Y H:i') }}
                                        </span>
                                        <div class="flex space-x-2">
                                            <a href="{{ route('archivo-problemas.show', $problema->id) }}" 
                                               class="text-blue-600 hover:text-blue-900 text-sm font-medium">
                                                Ver detalles
                                            </a>
                                            @if(Auth::user()->role === 'admin')
                                                <a href="{{ route('archivo-problemas.edit', $problema->id) }}" 
                                                   class="text-green-600 hover:text-green-900 text-sm font-medium">
                                                    Editar
                                                </a>
                                                <form method="POST" 
                                                      action="{{ route('archivo-problemas.destroy', $problema->id) }}" 
                                                      style="display: inline;"
                                                      onsubmit="return confirm('¿Estás seguro de que quieres eliminar este problema archivado?')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" 
                                                            class="text-red-600 hover:text-red-900 text-sm font-medium">
                                                        Eliminar
                                                    </button>
                                                </form>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <!-- Paginación -->
                    <div class="mt-6">
                        {{ $problemas->links() }}
                    </div>
                @else
                    <div class="text-center py-12">
                        <div class="text-gray-500 text-lg mb-4">📚</div>
                        <h3 class="text-lg font-medium text-gray-900 mb-2">No se encontraron problemas</h3>
                        <p class="text-gray-600 mb-4">
                            @if(request()->hasAny(['busqueda', 'categoria', 'tipo_problema', 'frecuencia']))
                                No hay problemas que coincidan con los filtros seleccionados.
                            @else
                                Aún no hay problemas archivados en el sistema.
                            @endif
                        </p>
                        <a href="{{ route('archivo-problemas.create') }}" 
                           class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                            ➕ Agregar primer problema
                        </a>
                    </div>
                @endif
                </div>
            </div>
        </main>

        <!-- Modal para crear ticket y archivar -->
        <div id="createTicketModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="fixed inset-0 transition-opacity bg-gray-500 bg-opacity-75" onclick="toggleCreateTicketModal()"></div>

                <div class="inline-block w-full max-w-4xl px-6 py-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
                    <div class="flex items-center justify-between mb-6">
                        <h3 class="text-lg font-medium text-gray-900">
                            <svg class="w-6 h-6 inline-block mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Crear Ticket y Archivar Problema
                        </h3>
                        <button onclick="toggleCreateTicketModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <form action="{{ route('archivo-problemas.create-ticket-and-archive') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Información del Ticket -->
                            <div class="space-y-4">
                                <h4 class="text-md font-medium text-gray-900 border-b pb-2">📋 Información del Ticket</h4>
                                
                                <div>
                                    <label for="ticket_asunto" class="block text-sm font-medium text-gray-700 mb-1">Asunto del Ticket</label>
                                    <input type="text" 
                                           name="ticket_asunto" 
                                           id="ticket_asunto" 
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                           required>
                                </div>

                                <div>
                                    <label for="ticket_descripcion" class="block text-sm font-medium text-gray-700 mb-1">Descripción del Problema</label>
                                    <textarea name="ticket_descripcion" 
                                              id="ticket_descripcion" 
                                              rows="4"
                                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                              required></textarea>
                                </div>

                                <div>
                                    <label for="ticket_categoria" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Problema</label>
                                    <select name="ticket_categoria" id="ticket_categoria" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                        <option value="">Seleccionar tipo</option>
                                        <option value="hardware">Hardware</option>
                                        <option value="software">Software</option>
                                        <option value="mantenimiento">Mantenimiento</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="ticket_imagenes" class="block text-sm font-medium text-gray-700 mb-1">Imágenes del Ticket (opcional)</label>
                                    <input type="file" 
                                           name="ticket_imagenes[]" 
                                           id="ticket_imagenes" 
                                           multiple
                                           accept="image/*"
                                           class="w-full border border-gray-300 rounded-md p-2 focus:border-blue-500 focus:ring-blue-500">
                                    <div class="mt-1 flex items-center space-x-4">
                                        <p class="text-xs text-gray-500">
                                            <strong>💡 Tip:</strong> Mantén <kbd class="px-1 py-0.5 bg-gray-200 rounded text-xs">Ctrl</kbd> presionado para seleccionar múltiples archivos
                                        </p>
                                        <span id="ticket_count" class="text-xs text-blue-600 font-medium"></span>
                                    </div>
                                    <div id="preview_ticket" class="mt-2 grid grid-cols-3 gap-2"></div>
                                </div>
                            </div>

                            <!-- Información del Archivo -->
                            <div class="space-y-4">
                                <h4 class="text-md font-medium text-gray-900 border-b pb-2">📚 Información del Archivo</h4>
                                
                                <div>
                                    <label for="archivo_titulo" class="block text-sm font-medium text-gray-700 mb-1">Título del Problema Archivado</label>
                                    <input type="text" 
                                           name="archivo_titulo" 
                                           id="archivo_titulo" 
                                           class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                           required>
                                </div>

                                <div>
                                    <label for="archivo_categoria" class="block text-sm font-medium text-gray-700 mb-1">Categoría del Problema</label>
                                    <select name="archivo_categoria" id="archivo_categoria" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                        <option value="">Seleccionar categoría</option>
                                        <option value="hardware">Hardware</option>
                                        <option value="software">Software</option>
                                        <option value="red">Red</option>
                                        <option value="impresora">Impresora</option>
                                        <option value="sistema">Sistema</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="archivo_tipo" class="block text-sm font-medium text-gray-700 mb-1">Tipo de Problema</label>
                                    <select name="archivo_tipo" id="archivo_tipo" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                        <option value="">Seleccionar tipo</option>
                                        <option value="error">Error</option>
                                        <option value="falla">Falla</option>
                                        <option value="configuracion">Configuración</option>
                                        <option value="mantenimiento">Mantenimiento</option>
                                        <option value="actualizacion">Actualización</option>
                                        <option value="otro">Otro</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="archivo_frecuencia" class="block text-sm font-medium text-gray-700 mb-1">Frecuencia</label>
                                    <select name="archivo_frecuencia" id="archivo_frecuencia" class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500" required>
                                        <option value="">Seleccionar frecuencia</option>
                                        <option value="unico">Único</option>
                                        <option value="ocasional">Ocasional</option>
                                        <option value="frecuente">Frecuente</option>
                                        <option value="critico">Crítico</option>
                                    </select>
                                </div>

                                <div>
                                    <label for="archivo_solucion" class="block text-sm font-medium text-gray-700 mb-1">Solución Aplicada</label>
                                    <textarea name="archivo_solucion" 
                                              id="archivo_solucion" 
                                              rows="4"
                                              class="w-full rounded-md border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500"
                                              placeholder="Describe la solución que se aplicó... Puedes referenciar imágenes como [Figura 1], [Figura 2], etc."
                                              required></textarea>
                                </div>

                                <div>
                                    <label for="solucion_imagenes" class="block text-sm font-medium text-gray-700 mb-1">Imágenes de la Solución (opcional)</label>
                                    <input type="file" 
                                           name="solucion_imagenes[]" 
                                           id="solucion_imagenes" 
                                           multiple
                                           accept="image/*"
                                           class="w-full border border-gray-300 rounded-md p-2 focus:border-blue-500 focus:ring-blue-500">
                                    <div class="mt-1 flex items-center space-x-4">
                                        <p class="text-xs text-gray-500">
                                            <strong>💡 Tip:</strong> Mantén <kbd class="px-1 py-0.5 bg-gray-200 rounded text-xs">Ctrl</kbd> presionado para seleccionar múltiples archivos
                                        </p>
                                        <span id="solucion_count" class="text-xs text-green-600 font-medium"></span>
                                    </div>
                                    <div id="preview_solucion" class="mt-2 grid grid-cols-2 gap-2"></div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end space-x-3 mt-6 pt-6 border-t">
                            <button type="button" 
                                    onclick="toggleCreateTicketModal()"
                                    class="bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-4 rounded-lg transition-colors duration-200">
                                Cancelar
                            </button>
                            <button type="submit" 
                                    class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-6 rounded-lg transition-colors duration-200 flex items-center">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                                </svg>
                                Crear Ticket y Archivar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <footer class="bg-white border-t border-gray-200 mt-12">
            <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                <div class="text-center text-gray-500 text-sm">
                    <p>&copy; {{ date('Y') }} E&I - Comercio Exterior, Logística y Tecnología. Todos los derechos reservados.</p>
                </div>
            </div>
        </footer>

        <script>
            function toggleCreateTicketModal() {
                const modal = document.getElementById('createTicketModal');
                modal.classList.toggle('hidden');
            }

            // Sincronizar campos relacionados
            document.getElementById('ticket_asunto').addEventListener('input', function() {
                document.getElementById('archivo_titulo').value = this.value;
            });

            document.getElementById('ticket_categoria').addEventListener('change', function() {
                document.getElementById('archivo_categoria').value = this.value;
            });

            document.getElementById('ticket_descripcion').addEventListener('input', function() {
                const descripcion = this.value;
                const solucionField = document.getElementById('archivo_solucion');
                if (!solucionField.value) {
                    solucionField.placeholder = `Describe la solución para: ${descripcion.substring(0, 50)}...`;
                }
            });

            // Preview de imágenes del ticket
            document.getElementById('ticket_imagenes').addEventListener('change', function(e) {
                const previewContainer = document.getElementById('preview_ticket');
                const counterSpan = document.getElementById('ticket_count');
                previewContainer.innerHTML = '';
                
                const files = Array.from(e.target.files);
                console.log('Archivos seleccionados para ticket:', files.length);
                
                // Actualizar contador
                if (files.length > 0) {
                    counterSpan.textContent = `📸 ${files.length} archivo${files.length > 1 ? 's' : ''} seleccionado${files.length > 1 ? 's' : ''}`;
                    counterSpan.className = 'text-xs text-blue-600 font-medium bg-blue-50 px-2 py-1 rounded';
                } else {
                    counterSpan.textContent = '';
                    counterSpan.className = 'text-xs text-green-600 font-medium';
                }
                
                files.forEach((file, index) => {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const previewDiv = document.createElement('div');
                            previewDiv.className = 'relative border rounded-lg overflow-hidden bg-blue-50';
                            previewDiv.innerHTML = `
                                <img src="${e.target.result}" class="w-full h-16 object-cover" />
                                <div class="text-xs text-center p-1 bg-blue-100">
                                    <span class="font-medium text-blue-700">Imagen ${index + 1}</span>
                                </div>
                                <div class="text-xs text-gray-600 truncate px-1" title="${file.name}">${file.name}</div>
                            `;
                            previewContainer.appendChild(previewDiv);
                        };
                        reader.readAsDataURL(file);
                    }
                });
            });

            // Preview de imágenes de solución
            document.getElementById('solucion_imagenes').addEventListener('change', function(e) {
                const previewContainer = document.getElementById('preview_solucion');
                const counterSpan = document.getElementById('solucion_count');
                previewContainer.innerHTML = '';
                
                const files = Array.from(e.target.files);
                console.log('Archivos seleccionados para solución:', files.length);
                
                // Actualizar contador
                if (files.length > 0) {
                    counterSpan.textContent = `🖼️ ${files.length} figura${files.length > 1 ? 's' : ''} seleccionada${files.length > 1 ? 's' : ''}`;
                    counterSpan.className = 'text-xs text-green-600 font-medium bg-green-50 px-2 py-1 rounded';
                } else {
                    counterSpan.textContent = '';
                    counterSpan.className = 'text-xs text-green-600 font-medium';
                }
                
                files.forEach((file, index) => {
                    if (file.type.startsWith('image/')) {
                        const reader = new FileReader();
                        reader.onload = function(e) {
                            const figuraNum = index + 1;
                            const previewDiv = document.createElement('div');
                            previewDiv.className = 'relative border rounded-lg p-2 bg-green-50';
                            previewDiv.innerHTML = `
                                <img src="${e.target.result}" class="w-full h-20 object-cover rounded" />
                                <div class="text-xs text-center mt-1 text-gray-600">
                                    <span class="font-medium text-green-700">Figura ${figuraNum}</span>
                                    <button type="button" onclick="insertFigureReference(${figuraNum})" 
                                            class="ml-2 px-2 py-1 bg-blue-600 text-white text-xs rounded hover:bg-blue-700 transition-colors">
                                        Insertar
                                    </button>
                                </div>
                                <div class="text-xs text-gray-500 truncate mt-1" title="${file.name}">${file.name}</div>
                            `;
                            previewContainer.appendChild(previewDiv);
                        };
                        reader.readAsDataURL(file);
                    }
                });
            });

            // Función para insertar referencias de figuras en el texto
            function insertFigureReference(figuraNum) {
                const solucionTextarea = document.getElementById('archivo_solucion');
                const cursorPos = solucionTextarea.selectionStart;
                const currentText = solucionTextarea.value;
                const reference = `[Figura ${figuraNum}]`;
                
                const newText = currentText.slice(0, cursorPos) + reference + currentText.slice(cursorPos);
                solucionTextarea.value = newText;
                
                // Mover el cursor después de la referencia insertada
                solucionTextarea.selectionStart = solucionTextarea.selectionEnd = cursorPos + reference.length;
                solucionTextarea.focus();
            }

            // Hacer la función global para poder usarla desde onclick
            window.insertFigureReference = insertFigureReference;

            // Validación antes del envío
            document.querySelector('form').addEventListener('submit', function(e) {
                const ticketFiles = document.getElementById('ticket_imagenes').files;
                const solucionFiles = document.getElementById('solucion_imagenes').files;
                
                console.log('Enviando formulario:');
                console.log('- Imágenes del ticket:', ticketFiles.length);
                console.log('- Imágenes de solución:', solucionFiles.length);
                
                // Mostrar alert de confirmación si hay muchas imágenes
                const totalImages = ticketFiles.length + solucionFiles.length;
                if (totalImages > 5) {
                    if (!confirm(`Estás a punto de subir ${totalImages} imágenes. Esto puede tardar un momento. ¿Continuar?`)) {
                        e.preventDefault();
                        return false;
                    }
                }
                
                // Cambiar texto del botón de envío
                const submitBtn = document.querySelector('button[type="submit"]');
                if (submitBtn && totalImages > 0) {
                    submitBtn.innerHTML = `
                        <svg class="w-4 h-4 mr-2 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                        </svg>
                        Procesando ${totalImages} imagen${totalImages > 1 ? 'es' : ''}...
                    `;
                    submitBtn.disabled = true;
                }
            });
        </script>
@endsection