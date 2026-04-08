@extends('layouts.master')

@section('title', 'Nuevo Activo IT')

@section('content')
<div class="min-h-screen bg-slate-50 pb-12">

    {{-- Header --}}
    <div class="bg-white border-b border-slate-200 mb-8">
        <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex items-center gap-3 mb-1">
                <a href="{{ route('admin.activos.index') }}"
                   class="text-slate-400 hover:text-indigo-600 transition-colors text-sm font-medium">
                    Activos IT
                </a>
                <svg class="w-4 h-4 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
                <span class="text-slate-600 text-sm font-medium">Nuevo dispositivo</span>
            </div>
            <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Nuevo Dispositivo</h1>
            <p class="text-slate-500 mt-1">Registra un nuevo equipo en el inventario de activos IT.</p>
        </div>
    </div>

    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8">

        @if(session('error'))
        <div class="mb-6 bg-red-50 border border-red-200 rounded-2xl px-5 py-4 text-red-700 text-sm font-medium">
            {{ session('error') }}
        </div>
        @endif

        <form method="POST" action="{{ route('admin.activos.store') }}"
              enctype="multipart/form-data"
              class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden divide-y divide-slate-100">
            @csrf

            {{-- Datos del dispositivo --}}
            <div class="p-8">
                <h2 class="text-base font-bold text-slate-700 mb-5 flex items-center gap-2">
                    <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/>
                    </svg>
                    Información del equipo
                </h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div class="sm:col-span-2">
                        <label for="name" class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Nombre <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="name" name="name" value="{{ old('name') }}"
                               placeholder="ej. Laptop Recursos Humanos 01"
                               class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition @error('name') border-red-400 @enderror">
                        @error('name') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="brand" class="block text-sm font-semibold text-slate-700 mb-1.5">Marca</label>
                        <input type="text" id="brand" name="brand" value="{{ old('brand') }}"
                               placeholder="ej. Dell, HP, Lenovo"
                               class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
                    </div>

                    <div>
                        <label for="model" class="block text-sm font-semibold text-slate-700 mb-1.5">Modelo</label>
                        <input type="text" id="model" name="model" value="{{ old('model') }}"
                               placeholder="ej. Latitude 5420"
                               class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
                    </div>

                    <div>
                        <label for="serial_number" class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Número de serie <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="serial_number" name="serial_number" value="{{ old('serial_number') }}"
                               placeholder="ej. SN123456789"
                               class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-mono text-slate-700 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition @error('serial_number') border-red-400 @enderror">
                        @error('serial_number') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="type" class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Tipo <span class="text-red-500">*</span>
                        </label>
                        <select id="type" name="type"
                                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition @error('type') border-red-400 @enderror">
                            <option value="">— Seleccionar —</option>
                            <option value="computer"   @selected(old('type') === 'computer')>Computadora</option>
                            <option value="peripheral" @selected(old('type') === 'peripheral')>Periférico</option>
                            <option value="printer"    @selected(old('type') === 'printer')>Impresora</option>
                            <option value="other"      @selected(old('type') === 'other')>Otro</option>
                        </select>
                        @error('type') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="status" class="block text-sm font-semibold text-slate-700 mb-1.5">
                            Estado <span class="text-red-500">*</span>
                        </label>
                        <select id="status" name="status"
                                class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition @error('status') border-red-400 @enderror">
                            <option value="available"   @selected(old('status', 'available') === 'available')>Disponible</option>
                            <option value="assigned"    @selected(old('status') === 'assigned')>Asignado</option>
                            <option value="maintenance" @selected(old('status') === 'maintenance')>Mantenimiento</option>
                            <option value="broken"      @selected(old('status') === 'broken')>Dañado</option>
                        </select>
                        @error('status') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label for="purchase_date" class="block text-sm font-semibold text-slate-700 mb-1.5">Fecha de compra</label>
                        <input type="date" id="purchase_date" name="purchase_date" value="{{ old('purchase_date') }}"
                               class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
                    </div>

                    <div>
                        <label for="warranty_expiration" class="block text-sm font-semibold text-slate-700 mb-1.5">Vencimiento de garantía</label>
                        <input type="date" id="warranty_expiration" name="warranty_expiration" value="{{ old('warranty_expiration') }}"
                               class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition">
                    </div>

                    <div class="sm:col-span-2">
                        <label for="notes" class="block text-sm font-semibold text-slate-700 mb-1.5">Notas</label>
                        <textarea id="notes" name="notes" rows="3"
                                  placeholder="Observaciones adicionales del equipo…"
                                  class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-amber-500 focus:border-amber-500 outline-none transition resize-none">{{ old('notes') }}</textarea>
                    </div>
                </div>
            </div>

            {{-- Fotos del dispositivo --}}
            <div class="p-8">
                <h2 class="text-base font-bold text-slate-700 mb-1 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Fotos del dispositivo
                    <span class="text-xs font-normal text-slate-400">(opcional, máx. 5 imágenes)</span>
                </h2>
                <p class="text-xs text-slate-400 mb-5">JPG, PNG, WEBP o GIF · máx. 8 MB por imagen.</p>

                {{-- Área de carga --}}
                <div id="foto-dropzone"
                     class="relative border-2 border-dashed border-slate-300 rounded-2xl p-6 text-center cursor-pointer hover:border-emerald-400 hover:bg-emerald-50 transition-colors"
                     onclick="document.getElementById('photos-input').click()">
                    <svg class="mx-auto h-10 w-10 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                              d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <p class="text-sm text-slate-500 font-medium">Arrastra imágenes aquí o haz clic para seleccionar</p>
                    <p class="text-xs text-slate-400 mt-1">También puedes tomar una foto desde tu dispositivo móvil</p>
                </div>

                {{-- Botones de acción rápida --}}
                <div class="flex flex-wrap gap-3 mt-4">
                    {{-- Seleccionar archivos --}}
                    <label class="inline-flex items-center gap-2 px-4 py-2 bg-slate-100 hover:bg-slate-200 text-slate-700 text-sm font-semibold rounded-xl cursor-pointer transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                        </svg>
                        Seleccionar archivos
                        <input id="photos-input" type="file" name="photos[]"
                               accept="image/*" multiple class="hidden"
                               onchange="previewFotos(this)">
                    </label>

                    {{-- Tomar foto (cámara) — funciona en móvil y algunos navegadores de escritorio --}}
                    <label class="inline-flex items-center gap-2 px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-sm font-semibold rounded-xl cursor-pointer transition shadow-sm shadow-emerald-200">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"/>
                        </svg>
                        Tomar foto
                        <input id="camera-input" type="file" name="photos[]"
                               accept="image/*" capture="environment" class="hidden"
                               onchange="previewFotos(this)">
                    </label>
                </div>

                @error('photos') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror
                @error('photos.*') <p class="mt-2 text-xs text-red-600">{{ $message }}</p> @enderror

                {{-- Previsualización --}}
                <div id="foto-preview" class="mt-5 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3 hidden"></div>
            </div>

            {{-- Credenciales --}}
            <div class="p-8">
                <h2 class="text-base font-bold text-slate-700 mb-1 flex items-center gap-2">
                    <svg class="w-5 h-5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    Credenciales del equipo
                    <span class="text-xs font-normal text-slate-400">(opcional)</span>
                </h2>
                <p class="text-xs text-slate-400 mb-5">Las contraseñas se almacenan cifradas.</p>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    <div>
                        <label for="cred_username" class="block text-sm font-semibold text-slate-700 mb-1.5">Usuario del SO</label>
                        <input type="text" id="cred_username" name="cred_username" value="{{ old('cred_username') }}"
                               placeholder="ej. administrador"
                               class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm font-mono text-slate-700 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition">
                    </div>
                    <div>
                        <label for="cred_password" class="block text-sm font-semibold text-slate-700 mb-1.5">Contraseña del SO</label>
                        <div class="relative">
                            <input type="password" id="cred_password" name="cred_password"
                                   placeholder="••••••••"
                                   class="w-full border border-slate-200 rounded-xl px-4 py-2.5 pr-10 text-sm font-mono text-slate-700 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition">
                            <button type="button" onclick="togglePass('cred_password')"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                    <div>
                        <label for="cred_email" class="block text-sm font-semibold text-slate-700 mb-1.5">Correo del equipo</label>
                        <input type="email" id="cred_email" name="cred_email" value="{{ old('cred_email') }}"
                               placeholder="ej. equipo@empresa.com"
                               class="w-full border border-slate-200 rounded-xl px-4 py-2.5 text-sm text-slate-700 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition @error('cred_email') border-red-400 @enderror">
                        @error('cred_email') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="cred_email_password" class="block text-sm font-semibold text-slate-700 mb-1.5">Contraseña del correo</label>
                        <div class="relative">
                            <input type="password" id="cred_email_password" name="cred_email_password"
                                   placeholder="••••••••"
                                   class="w-full border border-slate-200 rounded-xl px-4 py-2.5 pr-10 text-sm font-mono text-slate-700 focus:ring-2 focus:ring-violet-500 focus:border-violet-500 outline-none transition">
                            <button type="button" onclick="togglePass('cred_email_password')"
                                    class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Acciones --}}
            <div class="px-8 py-5 bg-slate-50 flex items-center justify-end gap-3">
                <a href="{{ route('admin.activos.index') }}"
                   class="px-5 py-2.5 bg-white border border-slate-200 text-slate-600 font-semibold text-sm rounded-xl hover:bg-slate-50 transition shadow-sm">
                    Cancelar
                </a>
                <button type="submit"
                        class="px-6 py-2.5 bg-amber-600 text-white font-bold text-sm rounded-xl hover:bg-amber-700 transition shadow-lg shadow-amber-200">
                    Registrar dispositivo
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function togglePass(id) {
    const el = document.getElementById(id);
    el.type = el.type === 'password' ? 'text' : 'password';
}

// Almacena todos los archivos seleccionados (de ambos inputs: gallery + camera)
let selectedFiles = [];

function previewFotos(input) {
    const newFiles = Array.from(input.files);
    if (newFiles.length === 0) return;

    // Agregar nuevos archivos sin superar 5 en total
    for (const file of newFiles) {
        if (selectedFiles.length >= 5) break;
        selectedFiles.push(file);
    }

    // Reconstruir los inputs hidden con DataTransfer
    syncFileInputs();
    renderPreviews();

    // Limpiar el input original para que onChange vuelva a disparar si se agregan más
    input.value = '';
}

function syncFileInputs() {
    // Usar el input principal (photos-input) para llevar todos los archivos
    const mainInput = document.getElementById('photos-input');
    const dt = new DataTransfer();
    for (const f of selectedFiles) dt.items.add(f);
    mainInput.files = dt.files;

    // Vaciar el input de cámara (ya agregamos sus archivos a selectedFiles)
    const camInput = document.getElementById('camera-input');
    camInput.value = '';
}

function renderPreviews() {
    const container = document.getElementById('foto-preview');
    container.innerHTML = '';

    if (selectedFiles.length === 0) {
        container.classList.add('hidden');
        return;
    }

    container.classList.remove('hidden');

    selectedFiles.forEach((file, idx) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const wrapper = document.createElement('div');
            wrapper.className = 'relative group';
            wrapper.innerHTML = `
                <img src="${e.target.result}" alt="${file.name}"
                     class="w-full h-24 object-cover rounded-xl border border-slate-200">
                <button type="button" onclick="removeFoto(${idx})"
                        class="absolute top-1 right-1 bg-red-600 text-white rounded-full w-5 h-5 flex items-center justify-center text-xs opacity-0 group-hover:opacity-100 transition-opacity">
                    ✕
                </button>
                <p class="text-xs text-slate-400 mt-1 truncate">${file.name}</p>`;
            container.appendChild(wrapper);
        };
        reader.readAsDataURL(file);
    });
}

function removeFoto(idx) {
    selectedFiles.splice(idx, 1);
    syncFileInputs();
    renderPreviews();
}

// Drag & drop en el área
const dropzone = document.getElementById('foto-dropzone');
dropzone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropzone.classList.add('border-emerald-400', 'bg-emerald-50');
});
dropzone.addEventListener('dragleave', () => {
    dropzone.classList.remove('border-emerald-400', 'bg-emerald-50');
});
dropzone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropzone.classList.remove('border-emerald-400', 'bg-emerald-50');
    const fakeInput = { files: e.dataTransfer.files };
    const newFiles = Array.from(fakeInput.files).filter(f => f.type.startsWith('image/'));
    for (const file of newFiles) {
        if (selectedFiles.length >= 5) break;
        selectedFiles.push(file);
    }
    syncFileInputs();
    renderPreviews();
});
</script>
@endpush
@endsection
