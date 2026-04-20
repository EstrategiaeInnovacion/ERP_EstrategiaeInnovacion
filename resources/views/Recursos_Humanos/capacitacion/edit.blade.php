@extends('layouts.erp')

@section('content')
<div class="max-w-4xl mx-auto py-8 px-4">
    <div class="mb-6">
        <h2 class="text-2xl font-bold text-gray-800">Editar Capacitación</h2>
        <a href="{{ route('rh.capacitacion.manage') }}" class="text-indigo-600 hover:text-indigo-800">&larr; Volver</a>
    </div>

    <div class="bg-white rounded-lg shadow-md p-6">
        <form action="{{ route('rh.capacitacion.update', $video->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            {{-- Título y Descripción --}}
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Título</label>
                <input type="text" name="titulo" value="{{ $video->titulo }}" class="w-full border rounded px-3 py-2 text-gray-700 focus:outline-none focus:border-indigo-500">
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Descripción</label>
                <textarea name="descripcion" rows="4" class="w-full border rounded px-3 py-2 text-gray-700">{{ $video->descripcion }}</textarea>
            </div>

            {{-- Categoría y Puestos --}}
                <div>
                    <label class="block text-gray-700 font-bold mb-2">Categoría</label>
                    <select name="categoria" class="w-full border rounded px-3 py-2 text-gray-700 focus:outline-none focus:border-indigo-500">
                        <option value="Talleres Virtuales" {{ $video->categoria == 'Talleres Virtuales' ? 'selected' : '' }}>Talleres Virtuales</option>
                        <option value="Capacitación RH" {{ $video->categoria == 'Capacitación RH' ? 'selected' : '' }}>Capacitación RH</option>
                        <option value="Formatos" {{ $video->categoria == 'Formatos' ? 'selected' : '' }}>Formatos</option>
                        <option value="Otro" {{ $video->categoria == 'Otro' ? 'selected' : '' }}>Otro</option>
                    </select>
                </div>
                <div>
                    <label class="block text-gray-700 font-bold mb-2">Puestos Permitidos</label>
                    <div class="max-h-48 overflow-y-auto border border-gray-300 rounded p-2 bg-gray-50">
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
                                    <input id="puesto_edit_{{ $index }}" name="puestos_permitidos[]" value="{{ $puesto }}" type="checkbox" 
                                        {{ in_array($puesto, $video->puestos_permitidos ?? []) ? 'checked' : '' }}
                                        class="puesto-checkbox focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                                </div>
                                <div class="ml-2 text-sm">
                                    <label for="puesto_edit_{{ $index }}" class="font-medium text-gray-700 cursor-pointer">{{ $puesto }}</label>
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

            {{-- Enlace de YouTube --}}
            <div class="mb-4">
                <label class="block text-gray-700 font-bold mb-2">Enlace de YouTube</label>
                <input type="url" name="youtube_url" value="{{ $video->youtube_url }}" placeholder="https://www.youtube.com/watch?v=..." class="w-full border rounded px-3 py-2 text-gray-700 focus:outline-none focus:border-indigo-500">
                <p class="text-xs text-gray-500 mt-1">Si ingresas un enlace aquí, se eliminará el video subido anteriormente (si existe).</p>
            </div>

            <div class="flex items-center justify-center my-4">
                <span class="text-gray-400 text-sm font-medium bg-white px-2">--- O ---</span>
            </div>

            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1">Usuarios Específicos (Opcional)</label>
                <p class="text-xs text-gray-500 mb-2">Selecciona usuarios específicos que pueden ver este video.</p>
                <div class="max-h-48 overflow-y-auto border border-gray-300 rounded-md p-2 bg-gray-50">
                    <div class="flex items-start mb-2 pb-2 border-b border-gray-200">
                        <div class="flex items-center h-5">
                            <input id="select_all_usuarios_edit" type="checkbox" class="focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                        </div>
                        <div class="ml-2 text-xs">
                            <label for="select_all_usuarios_edit" class="font-bold text-gray-800 cursor-pointer">Seleccionar Todos</label>
                        </div>
                    </div>
                    @foreach($usuarios as $index => $usuario)
                        <div class="flex items-start mb-1">
                            <div class="flex items-center h-5">
                                <input id="usuario_edit_{{ $index }}" name="usuarios_permitidos[]" value="{{ $usuario->id }}" type="checkbox" 
                                    {{ in_array($usuario->id, $video->usuarios_permitidos ?? []) ? 'checked' : '' }}
                                    class="usuario-checkbox-edit focus:ring-indigo-500 h-4 w-4 text-indigo-600 border-gray-300 rounded">
                            </div>
                            <div class="ml-2 text-xs">
                                <label for="usuario_edit_{{ $index }}" class="font-medium text-gray-700 cursor-pointer">{{ $usuario->name }}</label>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <script>
                document.getElementById('select_all_usuarios_edit').addEventListener('change', function() {
                    const checkboxes = document.querySelectorAll('.usuario-checkbox-edit');
                    checkboxes.forEach(cb => cb.checked = this.checked);
                });
            </script>

            {{-- Reemplazar Video --}}
            <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded">
                <label class="block text-yellow-800 font-bold mb-2">Reemplazar con Archivo de Video</label>
                <p class="text-sm text-yellow-600 mb-2">Sube un archivo solo si quieres reemplazar el contenido actual con un video nuevo.</p>
                <input type="file" name="video" accept="video/*" class="w-full text-sm">
            </div>

            {{-- Gestión de Documentos --}}
            <div class="mb-6 border-t pt-6">
                <h3 class="text-lg font-bold text-gray-800 mb-4">Documentos Complementarios</h3>

                {{-- Lista de documentos existentes --}}
                @if($video->adjuntos->count() > 0)
                    <div class="mb-4 space-y-2">
                        @foreach($video->adjuntos as $adjunto)
                            <div class="flex justify-between items-center bg-gray-50 p-2 rounded border">
                                <span class="text-sm text-gray-600 flex items-center">
                                    📄 {{ $adjunto->titulo }}
                                </span>
                                {{-- Botón eliminar documento (usa JS o un form pequeño) --}}
                                <button type="button" onclick="confirmDeleteAdjunto('{{ route('rh.capacitacion.destroyAdjunto', $adjunto->id) }}')" class="text-red-500 hover:text-red-700 text-xs font-bold uppercase">
                                    Eliminar
                                </button>
                            </div>
                        @endforeach
                    </div>
                @endif

                {{-- Subir nuevos documentos --}}
                <label class="block text-gray-700 font-bold mb-2">Agregar Documentos (PDF, Word, Excel)</label>
                <input type="file" name="adjuntos[]" multiple class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:bg-indigo-50 file:text-indigo-700">
                <p class="text-xs text-gray-500 mt-1">Puedes seleccionar varios archivos a la vez.</p>
            </div>

            <div class="flex justify-end">
                <button type="submit" class="bg-indigo-600 text-white font-bold py-2 px-6 rounded hover:bg-indigo-700 transition">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

{{-- Script simple para borrar adjuntos --}}
<form id="delete-adjunto-form" action="" method="POST" class="hidden">
    @csrf @method('DELETE')
</form>
<script>
    function confirmDeleteAdjunto(url) {
        if(confirm('¿Seguro que quieres eliminar este documento?')) {
            document.getElementById('delete-adjunto-form').action = url;
            document.getElementById('delete-adjunto-form').submit();
        }
    }
</script>
@endsection