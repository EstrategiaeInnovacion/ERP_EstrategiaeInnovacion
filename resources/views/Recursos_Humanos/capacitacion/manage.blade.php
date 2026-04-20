@extends('layouts.erp')

@section('content')
<div class="max-w-7xl mx-auto py-6 sm:px-6 lg:px-8">
    <div class="mb-6 flex justify-between items-center">
        <h2 class="text-2xl font-bold text-gray-800">Gestión de Videos de Capacitación</h2>
        <a href="{{ route('capacitacion.index') }}" class="text-indigo-600 hover:text-indigo-900 font-bold">Ir a la Galería Pública &rarr;</a>
    </div>

    <div class="bg-white rounded-lg shadow-sm p-6 mb-8 border border-gray-200">
        <h3 class="text-lg font-semibold mb-4 text-gray-700">Subir Nuevo Video</h3>
        <form action="{{ route('rh.capacitacion.store') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Título</label>
                    <input type="text" name="titulo" required class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Enlace de YouTube</label>
                    <input type="url" name="youtube_url" placeholder="https://www.youtube.com/watch?v=..." class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <p class="text-xs text-gray-500 mt-1">Opcional. Si se llena, no subir archivo de video.</p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Categoría</label>
                    <select name="categoria" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="Talleres Virtuales">Talleres Virtuales</option>
                        <option value="Capacitación RH">Capacitación RH</option>
                        <option value="Formatos">Formatos</option>
                        <option value="Otro">Otro</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Puestos Permitidos</label>
                    <div class="max-h-48 overflow-y-auto border border-gray-300 rounded-md p-2 bg-gray-50">
                        <div class="flex items-start mb-2 pb-2 border-b border-gray-200">
                            <div class="flex items-center h-5">
                                <input id="select_all_puestos" type="checkbox" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                            </div>
                            <div class="ml-2 text-xs">
                                <label for="select_all_puestos" class="font-bold text-gray-800 cursor-pointer">Seleccionar Todos</label>
                            </div>
                        </div>
                        @foreach($puestos as $index => $puesto)
                            <div class="flex items-start mb-1">
                                <div class="flex items-center h-5">
                                    <input id="puesto_{{ $index }}" name="puestos_permitidos[]" value="{{ $puesto }}" type="checkbox" class="puesto-checkbox focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-2 text-xs">
                                    <label for="puesto_{{ $index }}" class="font-medium text-gray-700 cursor-pointer">{{ $puesto }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <p class="text-xs text-gray-500 mt-1">Selecciona los puestos que pueden ver este video. Dejar vacío para público.</p>
                </div>

                <script>
                    document.getElementById('select_all_puestos').addEventListener('change', function() {
                        const checkboxes = document.querySelectorAll('.puesto-checkbox');
                        checkboxes.forEach(cb => cb.checked = this.checked);
                    });
                </script>

                <div class="col-span-1 md:col-span-2 flex items-center justify-center my-2">
                    <span class="text-gray-400 text-sm font-medium bg-white px-2">--- O ---</span>
                </div>

                <div class="col-span-1 md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Usuarios Específicos (Opcional)</label>
                    <p class="text-xs text-gray-500 mb-2">Selecciona usuarios específicos que pueden ver este video. Deja vacío si seleccionaste puestos arriba.</p>
                    <div class="max-h-48 overflow-y-auto border border-gray-300 rounded-md p-2 bg-gray-50">
                        <div class="flex items-start mb-2 pb-2 border-b border-gray-200">
                            <div class="flex items-center h-5">
                                <input id="select_all_usuarios" type="checkbox" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                            </div>
                            <div class="ml-2 text-xs">
                                <label for="select_all_usuarios" class="font-bold text-gray-800 cursor-pointer">Seleccionar Todos</label>
                            </div>
                        </div>
                        @foreach($usuarios as $index => $usuario)
                            <div class="flex items-start mb-1">
                                <div class="flex items-center h-5">
                                    <input id="usuario_{{ $index }}" name="usuarios_permitidos[]" value="{{ $usuario->id }}" type="checkbox" class="usuario-checkbox focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-2 text-xs">
                                    <label for="usuario_{{ $index }}" class="font-medium text-gray-700 cursor-pointer">{{ $usuario->name }}</label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <script>
                    document.getElementById('select_all_usuarios').addEventListener('change', function() {
                        const checkboxes = document.querySelectorAll('.usuario-checkbox');
                        checkboxes.forEach(cb => cb.checked = this.checked);
                    });
                </script>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Archivo de Video (MP4)</label>
                    <input type="file" name="video" accept="video/*" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100">
                    <p class="text-xs text-gray-500 mt-1">Requerido si no hay enlace de YouTube. Recomendado < 50MB.</p>
                </div>
                <div class="md:col-span-2">
                    <label class="block text-sm font-medium text-gray-700 mb-1">Descripción</label>
                    <textarea name="descripcion" rows="2" class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Material de Apoyo (Opcional)</label>
                    <input type="file" name="adjuntos[]" multiple class="...">
                </div>
            </div>
            <div class="mt-4 flex justify-end">
                <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-md hover:bg-indigo-700 font-bold shadow-sm transition">
                    Subir Video
                </button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded-lg shadow overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Título</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Fecha Subida</th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @foreach($videos as $video)
                <tr>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="text-sm font-bold text-gray-900">{{ $video->titulo }}</div>
                        <div class="text-xs text-gray-500 truncate max-w-xs">{{ $video->descripcion }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                        {{ $video->created_at->format('d/m/Y H:i') }}
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <form action="{{ route('rh.capacitacion.destroy', $video->id) }}" method="POST" onsubmit="return confirm('¿Eliminar video permanentemente?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-red-600 hover:text-red-900">Eliminar</button>
                            <a href="{{ route('rh.capacitacion.edit', $video->id) }}" class="text-indigo-600 hover:text-indigo-900 mr-3">Editar</a>
                        </form>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection