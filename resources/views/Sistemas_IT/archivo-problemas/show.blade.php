@extends('layouts.master')

@section('title', $problema->titulo . ' - E&I Sistema de Tickets')

@section('content')


        <!-- Main Content -->
        <main class="max-w-4xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="flex items-center justify-between mb-6">
                        <h2 class="text-2xl font-bold">📋 Detalle del Problema</h2>
                    <div class="flex space-x-2">
                        @if(Auth::user()->role === 'admin')
                            <a href="{{ route('archivo-problemas.edit', $problema->id) }}" 
                               class="bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition duration-200">
                                ✏️ Editar
                            </a>
                        @endif
                        <a href="{{ route('archivo-problemas.index') }}" 
                           class="bg-gray-500 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded transition duration-200">
                            ← Volver
                        </a>
                    </div>
                </div>

                @if(session('success'))
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        {{ session('success') }}
                    </div>
                @endif

                <!-- Información del ticket relacionado -->
                <div class="bg-blue-50 border-l-4 border-blue-400 p-4 mb-6">
                    <h3 class="text-lg font-medium text-blue-800 mb-2">📎 Ticket Relacionado</h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-sm">
                        <div>
                            <span class="font-medium text-blue-700">Ticket:</span>
                            <span class="text-blue-600">#{{ $problema->ticket->id }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-blue-700">Usuario:</span>
                            <span class="text-blue-600">{{ $problema->ticket->user->name }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-blue-700">Asunto Original:</span>
                            <span class="text-blue-600">{{ $problema->ticket->asunto }}</span>
                        </div>
                        <div>
                            <span class="font-medium text-blue-700">Fecha del Ticket:</span>
                            <span class="text-blue-600">{{ $problema->ticket->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                    </div>
                    <div class="mt-2">
                        <a href="{{ route('admin.tickets.show', $problema->ticket->id) }}" 
                           class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            Ver ticket completo →
                        </a>
                    </div>
                </div>

                <!-- Información principal del problema -->
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-6">
                    <!-- Columna principal -->
                    <div class="lg:col-span-2 space-y-6">
                        <!-- Título y descripción -->
                        <div class="bg-white border rounded-lg p-4">
                            <h3 class="text-xl font-semibold text-gray-900 mb-3">
                                {{ $problema->titulo }}
                            </h3>
                            
                            <div class="mb-4">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">🔍 Descripción del Problema</h4>
                                <div class="bg-gray-50 p-3 rounded-md">
                                    <p class="text-gray-900 whitespace-pre-wrap">{{ $problema->descripcion_problema }}</p>
                                </div>
                            </div>

                            <div>
                                <h4 class="text-sm font-medium text-gray-700 mb-2">✅ Solución Implementada</h4>
                                <div class="bg-green-50 p-3 rounded-md border-l-4 border-green-400">
                                    <div class="text-gray-900 whitespace-pre-wrap">{!! $problema->solucion_imagenes ? 
                                        preg_replace_callback('/\[Figura (\d+)\]/', function($matches) use ($problema) {
                                            $figuraNum = intval($matches[1]);
                                            $imagen = collect($problema->solucion_imagenes)->firstWhere('figura', $figuraNum);
                                            if ($imagen) {
                                                return '<span class="inline-block bg-blue-100 text-blue-800 px-2 py-1 rounded text-sm font-medium cursor-pointer hover:bg-blue-200 transition-colors" onclick="showImage(\'' . $imagen['data'] . '\', \'' . $imagen['nombre'] . '\', ' . $figuraNum . ')">📸 Figura ' . $figuraNum . '</span>';
                                            }
                                            return $matches[0];
                                        }, e($problema->solucion)) : e($problema->solucion) !!}</div>
                                </div>
                                
                                @if($problema->solucion_imagenes && count($problema->solucion_imagenes) > 0)
                                    <div class="mt-4">
                                        <h5 class="text-sm font-medium text-gray-700 mb-3">🖼️ Imágenes de la Solución</h5>
                                        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                            @foreach($problema->solucion_imagenes as $imagen)
                                                <div class="border rounded-lg overflow-hidden hover:shadow-md transition-shadow cursor-pointer"
                                                     onclick="showImage('{{ $imagen['data'] }}', '{{ $imagen['nombre'] }}', {{ $imagen['figura'] }})">
                                                    <img src="{{ $imagen['data'] }}" 
                                                         alt="Figura {{ $imagen['figura'] }}"
                                                         class="w-full h-24 object-cover">
                                                    <div class="p-2 bg-gray-50">
                                                        <p class="text-xs font-medium text-gray-700">Figura {{ $imagen['figura'] }}</p>
                                                        <p class="text-xs text-gray-500 truncate">{{ $imagen['nombre'] }}</p>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <!-- Notas adicionales -->
                        @if($problema->notas_adicionales)
                            <div class="bg-white border rounded-lg p-4">
                                <h4 class="text-sm font-medium text-gray-700 mb-2">📝 Notas Adicionales</h4>
                                <div class="bg-yellow-50 p-3 rounded-md border-l-4 border-yellow-400">
                                    <p class="text-gray-900 whitespace-pre-wrap">{{ $problema->notas_adicionales }}</p>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Columna lateral con metadatos -->
                    <div class="space-y-4">
                        <!-- Información de clasificación -->
                        <div class="bg-white border rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 mb-3">📊 Clasificación</h4>
                            <div class="space-y-3">
                                <div>
                                    <span class="text-xs font-medium text-gray-500">Categoría</span>
                                    <p class="text-sm font-medium text-gray-900">{{ $problema->categoria }}</p>
                                </div>
                                <div>
                                    <span class="text-xs font-medium text-gray-500">Tipo de Problema</span>
                                    <p class="text-sm font-medium text-gray-900">{{ $problema->tipo_problema }}</p>
                                </div>
                                <div>
                                    <span class="text-xs font-medium text-gray-500">Frecuencia</span>
                                    @php
                                        $frecuenciaColors = [
                                            'unico' => 'bg-blue-100 text-blue-800',
                                            'ocasional' => 'bg-yellow-100 text-yellow-800',
                                            'frecuente' => 'bg-orange-100 text-orange-800',
                                            'critico' => 'bg-red-100 text-red-800'
                                        ];
                                        $frecuenciaEmojis = [
                                            'unico' => '🔵',
                                            'ocasional' => '🟡',
                                            'frecuente' => '🟠',
                                            'critico' => '🔴'
                                        ];
                                    @endphp
                                    <div class="mt-1">
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $frecuenciaColors[$problema->frecuencia] ?? 'bg-gray-100 text-gray-800' }}">
                                            {{ $frecuenciaEmojis[$problema->frecuencia] ?? '' }} {{ ucfirst($problema->frecuencia) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Palabras clave -->
                        @if($problema->palabras_clave && count($problema->palabras_clave) > 0)
                            <div class="bg-white border rounded-lg p-4">
                                <h4 class="font-medium text-gray-900 mb-3">🏷️ Palabras Clave</h4>
                                <div class="flex flex-wrap gap-2">
                                    @foreach($problema->palabras_clave as $palabra)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                                            {{ $palabra }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif

                        <!-- Información de archivo -->
                        <div class="bg-white border rounded-lg p-4">
                            <h4 class="font-medium text-gray-900 mb-3">📅 Información de Archivo</h4>
                            <div class="space-y-3">
                                <div>
                                    <span class="text-xs font-medium text-gray-500">Archivado por</span>
                                    <p class="text-sm font-medium text-gray-900">{{ $problema->archivadoPor->name }}</p>
                                </div>
                                <div>
                                    <span class="text-xs font-medium text-gray-500">Fecha de archivo</span>
                                    <p class="text-sm text-gray-900">{{ $problema->fecha_archivo->format('d/m/Y H:i') }}</p>
                                </div>
                                <div>
                                    <span class="text-xs font-medium text-gray-500">Última actualización</span>
                                    <p class="text-sm text-gray-900">{{ $problema->updated_at->format('d/m/Y H:i') }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Acciones -->
                        @if(Auth::user()->role === 'admin')
                            <div class="bg-white border rounded-lg p-4">
                                <h4 class="font-medium text-gray-900 mb-3">⚙️ Acciones</h4>
                                <div class="space-y-2">
                                    <a href="{{ route('archivo-problemas.edit', $problema->id) }}" 
                                       class="block w-full text-center bg-green-500 hover:bg-green-700 text-white font-bold py-2 px-4 rounded transition duration-200">
                                        ✏️ Editar
                                    </a>
                                    <form method="POST" 
                                          action="{{ route('archivo-problemas.destroy', $problema->id) }}" 
                                          onsubmit="return confirm('¿Estás seguro de que quieres eliminar este problema archivado? Esta acción no se puede deshacer.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" 
                                                class="block w-full bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded transition duration-200">
                                            🗑️ Eliminar
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Problemas similares -->
                @php
                    $problemasSimilares = \App\Models\ProblemaArchivo::where('id', '!=', $problema->id)
                        ->where(function($query) use ($problema) {
                            $query->where('categoria', $problema->categoria)
                                  ->orWhere('tipo_problema', $problema->tipo_problema);
                            
                            if ($problema->palabras_clave && count($problema->palabras_clave) > 0) {
                                foreach ($problema->palabras_clave as $palabra) {
                                    $query->orWhereJsonContains('palabras_clave', $palabra);
                                }
                            }
                        })
                        ->with(['ticket', 'archivadoPor'])
                        ->take(3)
                        ->get();
                @endphp

                @if($problemasSimilares->count() > 0)
                    <div class="bg-white border rounded-lg p-4">
                        <h4 class="font-medium text-gray-900 mb-3">🔗 Problemas Similares</h4>
                        <div class="space-y-3">
                            @foreach($problemasSimilares as $similar)
                                <div class="border-l-4 border-blue-400 pl-3 py-2 bg-blue-50">
                                    <h5 class="text-sm font-medium text-gray-900">
                                        <a href="{{ route('archivo-problemas.show', $similar->id) }}" 
                                           class="hover:text-blue-600 transition duration-200">
                                            {{ $similar->titulo }}
                                        </a>
                                    </h5>
                                    <p class="text-xs text-gray-600 mt-1">
                                        {{ $similar->categoria }} • {{ $similar->tipo_problema }} • 
                                        {{ $similar->fecha_archivo->format('d/m/Y') }}
                                    </p>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
                </div>
            </div>
        </main>

        <!-- Modal para mostrar imagen en tamaño completo -->
        <div id="imageModal" class="fixed inset-0 z-50 hidden overflow-y-auto bg-black bg-opacity-75">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <div class="inline-block w-full max-w-4xl px-6 py-6 my-8 overflow-hidden text-left align-middle transition-all transform bg-white shadow-xl rounded-lg">
                    <div class="flex items-center justify-between mb-4">
                        <h3 id="imageTitle" class="text-lg font-medium text-gray-900"></h3>
                        <button onclick="closeImageModal()" class="text-gray-400 hover:text-gray-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="text-center">
                        <img id="modalImage" src="" alt="" class="max-w-full max-h-96 mx-auto rounded-lg shadow-lg">
                        <p id="imageName" class="mt-2 text-sm text-gray-600"></p>
                    </div>
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
            function showImage(imageSrc, imageName, figuraNum) {
                document.getElementById('modalImage').src = imageSrc;
                document.getElementById('imageTitle').textContent = `Figura ${figuraNum}`;
                document.getElementById('imageName').textContent = imageName;
                document.getElementById('imageModal').classList.remove('hidden');
            }

            function closeImageModal() {
                document.getElementById('imageModal').classList.add('hidden');
            }

            // Cerrar modal con Escape
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeImageModal();
                }
            });
        </script>
@endsection