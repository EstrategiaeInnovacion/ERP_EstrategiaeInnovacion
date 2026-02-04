@extends('layouts.master')

@section('title', 'Nueva Sección - Manual de Ayuda')

@section('content')
<main class="max-w-4xl mx-auto py-10 px-4 sm:px-6 lg:px-8">
    <!-- Header -->
    <div class="mb-8">
        <div class="flex items-center gap-4 mb-4">
            <a href="{{ route('admin.help.index') }}" 
               class="text-blue-600 hover:text-blue-800">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                </svg>
            </a>
            <div>
                <h1 class="text-3xl font-bold text-gray-900">➕ Nueva Sección</h1>
                <p class="text-gray-600 mt-1">Crear una nueva sección para el manual de ayuda</p>
            </div>
        </div>
    </div>

    <!-- Formulario -->
    <div class="bg-white rounded-xl shadow-lg border border-gray-200">
        <div class="p-8">
            <form method="POST" action="{{ route('admin.help.store') }}" class="space-y-6" enctype="multipart/form-data">
                @csrf

                <!-- Título -->
                <div>
                    <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                        📝 Título de la Sección *
                    </label>
                    <input type="text" 
                           id="title" 
                           name="title" 
                           value="{{ old('title') }}" 
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('title') border-red-500 @enderror"
                           placeholder="Ej: Cómo crear un ticket de soporte">
                    @error('title')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Orden -->
                <div>
                    <label for="section_order" class="block text-sm font-medium text-gray-700 mb-2">
                        🔢 Orden de la Sección *
                    </label>
                    <input type="number" 
                           id="section_order" 
                           name="section_order" 
                           value="{{ old('section_order', 0) }}" 
                           min="0"
                           required
                           class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('section_order') border-red-500 @enderror">
                    @error('section_order')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <p class="mt-1 text-sm text-gray-500">
                        Las secciones se mostrarán ordenadas de menor a mayor número
                    </p>
                </div>

                <!-- Imágenes -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        🖼️ Imágenes de la Sección
                    </label>
                    <div class="border-2 border-dashed border-gray-300 rounded-lg p-6">
                        <div class="text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-400" stroke="currentColor" fill="none" viewBox="0 0 48 48">
                                <path d="M28 8H12a4 4 0 00-4 4v20m32-12v8m0 0v8a4 4 0 01-4 4H12a4 4 0 01-4-4v-4m32-4l-3.172-3.172a4 4 0 00-5.656 0L28 28M8 32l9.172-9.172a4 4 0 015.656 0L28 28m0 0l4 4m4-24h8m-4-4v8m-12 4h.02" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                            <div class="mt-4">
                                <label for="images" class="cursor-pointer">
                                    <span class="mt-2 block text-sm font-medium text-gray-900">
                                        Subir imágenes
                                    </span>
                                    <span class="mt-1 block text-sm text-gray-600">
                                        PNG, JPG, GIF hasta 100MB cada una
                                    </span>
                                </label>
                                <input id="images" name="images[]" type="file" class="sr-only" multiple accept="image/*">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Preview de imágenes -->
                    <div id="imagePreview" class="mt-4 grid grid-cols-2 md:grid-cols-4 gap-4" style="display: none;"></div>
                    
                    <!-- Referencias de imágenes -->
                    <div id="imageReferences" class="mt-4" style="display: none;">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">📋 Referencias para usar en el contenido:</h4>
                        <div id="referenceList" class="bg-gray-50 p-3 rounded-lg text-sm font-mono"></div>
                        <p class="mt-2 text-xs text-gray-500">
                            Copia y pega estas referencias en el contenido donde quieras que aparezcan las imágenes
                        </p>
                    </div>
                </div>

                <!-- Contenido -->
                <div>
                    <label for="content" class="block text-sm font-medium text-gray-700 mb-2">
                        📄 Contenido de la Sección *
                    </label>
                    <textarea id="content" 
                              name="content" 
                              rows="12"
                              required
                              class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent @error('content') border-red-500 @enderror"
                              placeholder="Escribe aquí el contenido de la sección del manual...

Para incluir imágenes, usa las referencias que se generan automáticamente arriba.
Ejemplo: [img:figura1] mostrará la primera imagen que subas.">{{ old('content') }}</textarea>
                    @error('content')
                        <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                    <div class="mt-1 text-sm text-gray-500">
                        <p>💡 <strong>Consejos de formato:</strong></p>
                        <ul class="list-disc list-inside ml-4 mt-1 space-y-1">
                            <li>Usa <strong>[img:figura1]</strong> para insertar imágenes</li>
                            <li>Puedes usar HTML básico: &lt;strong&gt;, &lt;em&gt;, &lt;ul&gt;, &lt;ol&gt;, &lt;h3&gt;, etc.</li>
                            <li>Las imágenes se ajustarán automáticamente al ancho del container</li>
                        </ul>
                    </div>
                </div>

                <!-- Estado -->
                <div>
                    <div class="flex items-center">
                        <input type="checkbox" 
                               id="is_active" 
                               name="is_active" 
                               value="1"
                               {{ old('is_active', true) ? 'checked' : '' }}
                               class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
                        <label for="is_active" class="ml-2 block text-sm text-gray-900">
                            ✅ Sección activa (visible en el manual público)
                        </label>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">
                        Solo las secciones activas aparecerán en el manual público
                    </p>
                </div>

                <!-- Información Adicional -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <svg class="w-5 h-5 text-blue-400 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <h3 class="text-sm font-medium text-blue-800">💡 Consejos para crear contenido efectivo</h3>
                            <div class="mt-1 text-sm text-blue-700">
                                <ul class="list-disc list-inside space-y-1">
                                    <li>Usa títulos claros y descriptivos</li>
                                    <li>Incluye pasos numerados para procesos</li>
                                    <li>Añade ejemplos prácticos cuando sea posible</li>
                                    <li>Mantén el contenido actualizado y preciso</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Botones -->
                <div class="flex flex-col sm:flex-row gap-3 pt-6">
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-3 px-6 rounded-lg transition-colors duration-200 inline-flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                        </svg>
                        💾 Crear Sección
                    </button>
                    <a href="{{ route('admin.help.index') }}" 
                       class="bg-gray-500 hover:bg-gray-600 text-white font-medium py-3 px-6 rounded-lg transition-colors duration-200 inline-flex items-center justify-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                        ❌ Cancelar
                    </a>
                </div>
            </form>
        </div>
    </div>
</main>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Variables globales
    const textarea = document.getElementById('content');
    const imageInput = document.getElementById('images');
    const imagePreview = document.getElementById('imagePreview');
    const imageReferences = document.getElementById('imageReferences');
    const referenceList = document.getElementById('referenceList');
    let selectedFiles = [];
    
    // Auto-resize textarea
    function autoResize() {
        textarea.style.height = 'auto';
        textarea.style.height = textarea.scrollHeight + 'px';
    }
    
    textarea.addEventListener('input', autoResize);
    
    // Count characters
    const maxLength = 10000;
    const counter = document.createElement('div');
    counter.className = 'text-xs text-gray-500 mt-1';
    textarea.parentNode.appendChild(counter);
    
    function updateCounter() {
        const length = textarea.value.length;
        counter.textContent = `${length} caracteres`;
        
        if (length > maxLength * 0.9) {
            counter.className = 'text-xs text-orange-600 mt-1';
        } else {
            counter.className = 'text-xs text-gray-500 mt-1';
        }
    }
    
    textarea.addEventListener('input', updateCounter);
    updateCounter();
    
    // Manejar selección de imágenes
    imageInput.addEventListener('change', function(e) {
        selectedFiles = Array.from(e.target.files);
        updateImagePreview();
        updateImageReferences();
    });
    
    function updateImagePreview() {
        imagePreview.innerHTML = '';
        
        if (selectedFiles.length === 0) {
            imagePreview.style.display = 'none';
            imageReferences.style.display = 'none';
            return;
        }
        
        imagePreview.style.display = 'grid';
        imageReferences.style.display = 'block';
        
        selectedFiles.forEach((file, index) => {
            if (file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const div = document.createElement('div');
                    div.className = 'relative group';
                    div.innerHTML = `
                        <img src="${e.target.result}" alt="Preview ${index + 1}" class="w-full h-24 object-cover rounded-lg border border-gray-200">
                        <div class="absolute top-2 right-2 opacity-0 group-hover:opacity-100 transition-opacity">
                            <button type="button" onclick="removeImage(${index})" class="bg-red-500 hover:bg-red-600 text-white rounded-full p-1 text-xs">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>
                        <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-70 text-white text-xs p-2 rounded-b-lg">
                            ${file.name}
                        </div>
                    `;
                    imagePreview.appendChild(div);
                };
                reader.readAsDataURL(file);
            }
        });
    }
    
    function updateImageReferences() {
        let references = [];
        selectedFiles.forEach((file, index) => {
            const reference = `[img:figura${index + 1}]`;
            references.push(`<div class="mb-2"><strong>${reference}</strong> → ${file.name}</div>`);
        });
        
        referenceList.innerHTML = references.join('');
    }
    
    // Función global para remover imagen
    window.removeImage = function(index) {
        selectedFiles.splice(index, 1);
        updateFileInput();
        updateImagePreview();
        updateImageReferences();
    };
    
    function updateFileInput() {
        const dt = new DataTransfer();
        selectedFiles.forEach(file => dt.items.add(file));
        imageInput.files = dt.files;
    }
    
    // Función para insertar referencia en el textarea
    window.insertReference = function(reference) {
        const cursorPos = textarea.selectionStart;
        const textBefore = textarea.value.substring(0, cursorPos);
        const textAfter = textarea.value.substring(cursorPos);
        
        textarea.value = textBefore + reference + textAfter;
        textarea.focus();
        textarea.setSelectionRange(cursorPos + reference.length, cursorPos + reference.length);
        
        autoResize();
        updateCounter();
    };
});
</script>

@endsection