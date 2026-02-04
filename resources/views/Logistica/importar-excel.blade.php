@extends('layouts.erp')

@section('title', 'Importar Excel - Matriz de Operación')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-white via-blue-50 to-blue-100 py-8">
    <div class="container mx-auto px-4 max-w-4xl">
        
        {{-- Header --}}
        <div class="mb-8">
            <div class="flex items-center gap-3 mb-2">
                <div class="p-3 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl shadow-lg">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                    </svg>
                </div>
                <div>
                    <h1 class="text-3xl font-bold text-gray-800">Importar Excel</h1>
                    <p class="text-gray-500">Matriz de Operación - Importación Masiva</p>
                </div>
            </div>
            <a href="{{ route('logistica.matriz-seguimiento') }}" class="inline-flex items-center text-blue-600 hover:text-blue-800 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                Volver a la Matriz
            </a>
        </div>

        {{-- Alerta de advertencia --}}
        <div class="bg-amber-50 border border-amber-300 rounded-xl p-4 mb-6">
            <div class="flex items-start gap-3">
                <svg class="w-6 h-6 text-amber-500 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <div>
                    <h4 class="text-amber-700 font-semibold">⚠️ Página de uso restringido</h4>
                    <p class="text-amber-600 text-sm mt-1">
                        Esta herramienta está diseñada para importación masiva de datos. Úsala con precaución.
                        Los registros existentes se actualizarán basándose en el número de folio.
                    </p>
                </div>
            </div>
        </div>

        {{-- Formulario de importación --}}
        <div class="bg-white rounded-2xl border border-gray-200 shadow-xl overflow-hidden">
            <div class="p-6 border-b border-gray-200 bg-gradient-to-r from-blue-600 to-blue-700">
                <h2 class="text-xl font-semibold text-white flex items-center gap-2">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Cargar Archivo Excel
                </h2>
            </div>
            
            <form id="formImportarExcel" enctype="multipart/form-data" class="p-6 space-y-6">
                @csrf
                
                {{-- Selector de ejecutivo --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        <span class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Asignar vista a Ejecutivo
                            <span class="text-red-500">*</span>
                        </span>
                    </label>
                    <select name="ejecutivo_id" id="ejecutivo_id" required
                            class="w-full bg-white border border-gray-300 rounded-lg px-4 py-3 text-gray-800 focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                        <option value="">-- Seleccione un ejecutivo --</option>
                        @foreach($ejecutivos as $ejecutivo)
                            <option value="{{ $ejecutivo->id }}">{{ $ejecutivo->nombre }} {{ $ejecutivo->apellido_paterno ?? '' }} - {{ $ejecutivo->correo }}</option>
                        @endforeach
                    </select>
                    <p class="text-xs text-gray-500 mt-1">
                        <strong>Importante:</strong> Las columnas personalizadas detectadas en el Excel (ej. "FECHA PEDIMENTO PAGADO") se activarán automáticamente y solo serán visibles para el ejecutivo seleccionado.
                    </p>
                </div>

                {{-- Zona de arrastrar archivo --}}
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Archivo Excel <span class="text-red-500">*</span>
                    </label>
                    <div id="dropZone" 
                         class="relative border-2 border-dashed border-gray-300 rounded-xl p-8 text-center cursor-pointer hover:border-blue-500 hover:bg-blue-50 transition-all duration-300">
                        <input type="file" name="archivo_excel" id="archivo_excel" 
                               accept=".xlsx,.xls,.csv" 
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer" />
                        
                        <div id="dropZoneContent">
                            <div class="mx-auto w-16 h-16 bg-blue-100 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                                </svg>
                            </div>
                            <p class="text-gray-600 mb-2">Arrastra tu archivo aquí o <span class="text-blue-600 font-medium">haz clic para seleccionar</span></p>
                            <p class="text-xs text-gray-400">Formatos: .xlsx, .xls, .csv (máx. 10MB)</p>
                        </div>
                        
                        <div id="fileSelected" class="hidden">
                            <div class="mx-auto w-16 h-16 bg-green-100 rounded-full flex items-center justify-center mb-4">
                                <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                            </div>
                            <p class="text-green-600 font-medium mb-1" id="fileName">archivo.xlsx</p>
                            <p class="text-xs text-gray-500" id="fileSize">0 KB</p>
                        </div>
                    </div>
                </div>

                {{-- Información del mapeo --}}
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                    <h4 class="text-sm font-medium text-gray-700 mb-3 flex items-center gap-2">
                        <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        Mapeo de columnas
                    </h4>
                    <div class="text-xs text-gray-600 space-y-1">
                        <p>El sistema reconoce automáticamente las siguientes columnas del Excel:</p>
                        <div class="grid grid-cols-2 md:grid-cols-3 gap-2 mt-2">
                            <span class="bg-white border border-gray-200 px-2 py-1 rounded text-gray-700">No. Folio</span>
                            <span class="bg-white border border-gray-200 px-2 py-1 rounded text-gray-700">Process/Operación</span>
                            <span class="bg-white border border-gray-200 px-2 py-1 rounded text-gray-700">Customer/Cliente</span>
                            <span class="bg-white border border-gray-200 px-2 py-1 rounded text-gray-700">Invoice Number</span>
                            <span class="bg-white border border-gray-200 px-2 py-1 rounded text-gray-700">Supplier Name</span>
                            <span class="bg-white border border-gray-200 px-2 py-1 rounded text-gray-700">Customs MX</span>
                            <span class="bg-white border border-gray-200 px-2 py-1 rounded text-gray-700">In Charge</span>
                            <span class="bg-white border border-gray-200 px-2 py-1 rounded text-gray-700">Freight</span>
                            <span class="bg-white border border-gray-200 px-2 py-1 rounded text-gray-700">Tracking/BL</span>
                            <span class="bg-white border border-gray-200 px-2 py-1 rounded text-gray-700">Shipp Date ETD</span>
                            <span class="bg-white border border-gray-200 px-2 py-1 rounded text-gray-700">Arriving Date</span>
                            <span class="bg-white border border-gray-200 px-2 py-1 rounded text-gray-700">Status</span>
                            <span class="bg-white border border-gray-200 px-2 py-1 rounded text-gray-700">Pedimento</span>
                            <span class="bg-white border border-gray-200 px-2 py-1 rounded text-gray-700">REF</span>
                            <span class="bg-white border border-gray-200 px-2 py-1 rounded text-gray-700">Mail Subject</span>
                        </div>
                        <p class="mt-3 text-amber-600 font-medium">
                            <strong>Nota:</strong> Las columnas no reconocidas se crearán como campos personalizados.
                        </p>
                    </div>
                </div>

                {{-- Botones --}}
                <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-200">
                    <a href="{{ route('logistica.matriz-seguimiento') }}" 
                       class="px-6 py-3 bg-gray-200 hover:bg-gray-300 text-gray-700 rounded-lg transition-colors">
                        Cancelar
                    </a>
                    <button type="submit" id="btnImportar"
                            class="px-6 py-3 bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white rounded-lg font-medium transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                            disabled>
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                        </svg>
                        <span id="btnText">Importar Excel</span>
                    </button>
                </div>
            </form>
        </div>

        {{-- Resultado de la importación --}}
        <div id="resultadoImportacion" class="hidden mt-6">
            <div id="resultadoExito" class="hidden bg-green-50 border border-green-300 rounded-xl p-6">
                <div class="flex items-start gap-4">
                    <div class="p-2 bg-green-100 rounded-full">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-green-700 font-semibold text-lg">¡Importación exitosa!</h4>
                        <p class="text-green-600 mt-1" id="mensajeExito"></p>
                        
                        <div id="detallesExito" class="mt-4 space-y-3">
                            <div id="columnasActivadasInfo" class="hidden">
                                <h5 class="text-sm font-medium text-gray-600 mb-2">Columnas activadas:</h5>
                                <div id="listaColumnasActivadas" class="flex flex-wrap gap-2"></div>
                            </div>
                            <div id="camposCreadosInfo" class="hidden">
                                <h5 class="text-sm font-medium text-gray-600 mb-2">Campos personalizados creados:</h5>
                                <div id="listaCamposCreados" class="flex flex-wrap gap-2"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div id="resultadoError" class="hidden bg-red-50 border border-red-300 rounded-xl p-6">
                <div class="flex items-start gap-4">
                    <div class="p-2 bg-red-100 rounded-full">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <div class="flex-1">
                        <h4 class="text-red-700 font-semibold text-lg">Error en la importación</h4>
                        <p class="text-red-600 mt-1" id="mensajeError"></p>
                        <div id="listaErrores" class="mt-3 text-sm text-red-500"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('formImportarExcel');
    const fileInput = document.getElementById('archivo_excel');
    const dropZone = document.getElementById('dropZone');
    const dropZoneContent = document.getElementById('dropZoneContent');
    const fileSelected = document.getElementById('fileSelected');
    const btnImportar = document.getElementById('btnImportar');
    const btnText = document.getElementById('btnText');

    // Drag and drop
    ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, preventDefaults, false);
    });

    function preventDefaults(e) {
        e.preventDefault();
        e.stopPropagation();
    }

    ['dragenter', 'dragover'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.classList.add('border-blue-500', 'bg-blue-50');
        });
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropZone.addEventListener(eventName, () => {
            dropZone.classList.remove('border-blue-500', 'bg-blue-50');
        });
    });

    dropZone.addEventListener('drop', (e) => {
        const files = e.dataTransfer.files;
        if (files.length) {
            fileInput.files = files;
            handleFileSelect(files[0]);
        }
    });

    fileInput.addEventListener('change', (e) => {
        if (e.target.files.length) {
            handleFileSelect(e.target.files[0]);
        }
    });

    function handleFileSelect(file) {
        const validTypes = ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 
                           'application/vnd.ms-excel', 
                           'text/csv'];
        
        if (!validTypes.includes(file.type) && !file.name.match(/\.(xlsx|xls|csv)$/i)) {
            alert('Por favor selecciona un archivo Excel válido (.xlsx, .xls, .csv)');
            return;
        }

        document.getElementById('fileName').textContent = file.name;
        document.getElementById('fileSize').textContent = formatFileSize(file.size);
        
        dropZoneContent.classList.add('hidden');
        fileSelected.classList.remove('hidden');
        btnImportar.disabled = false;
    }

    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }

    // Submit del formulario
    form.addEventListener('submit', async function(e) {
        e.preventDefault();

        const formData = new FormData(form);
        
        btnImportar.disabled = true;
        btnText.textContent = 'Importando...';
        btnImportar.querySelector('svg').classList.add('animate-spin');

        // Ocultar resultados anteriores
        document.getElementById('resultadoImportacion').classList.add('hidden');
        document.getElementById('resultadoExito').classList.add('hidden');
        document.getElementById('resultadoError').classList.add('hidden');

        try {
            const response = await fetch('{{ route("logistica.importar-excel.procesar") }}', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                }
            });

            const data = await response.json();

            document.getElementById('resultadoImportacion').classList.remove('hidden');

            if (data.success) {
                document.getElementById('resultadoExito').classList.remove('hidden');
                document.getElementById('mensajeExito').textContent = data.message;

                // Mostrar columnas activadas
                if (data.columnas_activadas && data.columnas_activadas.length > 0) {
                    document.getElementById('columnasActivadasInfo').classList.remove('hidden');
                    const listaColumnas = document.getElementById('listaColumnasActivadas');
                    listaColumnas.innerHTML = data.columnas_activadas.map(col => 
                        `<span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-sm">${col}</span>`
                    ).join('');
                }

                // Mostrar campos creados
                if (data.campos_creados && data.campos_creados.length > 0) {
                    document.getElementById('camposCreadosInfo').classList.remove('hidden');
                    const listaCampos = document.getElementById('listaCamposCreados');
                    listaCampos.innerHTML = data.campos_creados.map(campo => 
                        `<span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm">${campo.nombre} (${campo.tipo})</span>`
                    ).join('');
                }

                // Limpiar formulario
                form.reset();
                dropZoneContent.classList.remove('hidden');
                fileSelected.classList.add('hidden');

            } else {
                document.getElementById('resultadoError').classList.remove('hidden');
                document.getElementById('mensajeError').textContent = data.message;
                
                if (data.errores && data.errores.length > 0) {
                    document.getElementById('listaErrores').innerHTML = 
                        '<ul class="list-disc list-inside space-y-1">' + 
                        data.errores.map(err => `<li>${err}</li>`).join('') + 
                        '</ul>';
                }
            }

        } catch (error) {
            document.getElementById('resultadoImportacion').classList.remove('hidden');
            document.getElementById('resultadoError').classList.remove('hidden');
            document.getElementById('mensajeError').textContent = 'Error de conexión: ' + error.message;
        } finally {
            btnImportar.disabled = false;
            btnText.textContent = 'Importar Excel';
            btnImportar.querySelector('svg').classList.remove('animate-spin');
        }
    });
});
</script>
@endpush
@endsection
