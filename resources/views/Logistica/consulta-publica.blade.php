<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Consulta de Operaciones - Logística</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .status-verde { background-color: #10b981; color: white; }
        .status-amarillo { background-color: #f59e0b; color: white; }
        .status-rojo { background-color: #ef4444; color: white; }
    </style>
</head>
<body class="bg-gradient-to-br from-blue-50 via-white to-blue-100 min-h-screen">
    <div class="relative overflow-hidden">
        <!-- Efectos de fondo -->
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute -top-32 -left-20 w-96 h-96 bg-blue-200/40 blur-3xl rounded-full"></div>
            <div class="absolute top-40 -right-24 w-96 h-96 bg-blue-300/30 blur-3xl rounded-full"></div>
        </div>

        <div class="relative max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
            <!-- Header -->
            <div class="text-center mb-12">
                <h1 class="text-4xl font-bold text-slate-900 mb-4 flex items-center justify-center gap-3">
                    <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    Consulta de Operaciones Logísticas
                </h1>
                <p class="text-lg text-slate-600">
                    Rastrea tu operación por Número de Pedimento o Factura
                </p>
            </div>

            <!-- Formulario de Búsqueda -->
            <div class="max-w-2xl mx-auto mb-8">
                <div class="bg-white rounded-2xl shadow-xl p-8 border border-blue-100">
                    <form id="formBusqueda" class="space-y-6">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-3">
                                Tipo de Búsqueda
                            </label>
                            <div class="grid grid-cols-2 gap-4">
                                <label class="relative flex items-center justify-center p-4 border-2 border-slate-200 rounded-xl cursor-pointer hover:border-blue-500 transition-all">
                                    <input type="radio" name="tipo_busqueda" value="pedimento" class="sr-only peer" checked>
                                    <div class="text-center peer-checked:text-blue-600">
                                        <div class="mb-2">
                                            <svg class="w-8 h-8 mx-auto text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                        <div class="font-semibold">N° Pedimento</div>
                                    </div>
                                    <div class="absolute inset-0 border-2 border-blue-600 rounded-xl opacity-0 peer-checked:opacity-100 transition-opacity"></div>
                                </label>
                                <label class="relative flex items-center justify-center p-4 border-2 border-slate-200 rounded-xl cursor-pointer hover:border-blue-500 transition-all">
                                    <input type="radio" name="tipo_busqueda" value="factura" class="sr-only peer">
                                    <div class="text-center peer-checked:text-blue-600">
                                        <div class="mb-2">
                                            <svg class="w-8 h-8 mx-auto text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                        </div>
                                        <div class="font-semibold">N° Factura</div>
                                    </div>
                                    <div class="absolute inset-0 border-2 border-blue-600 rounded-xl opacity-0 peer-checked:opacity-100 transition-opacity"></div>
                                </label>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">
                                Número a Buscar
                            </label>
                            <input 
                                type="text" 
                                name="valor" 
                                id="valorBusqueda"
                                required
                                class="w-full px-4 py-3 text-lg border-2 border-slate-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all"
                                placeholder="Ingrese el número...">
                        </div>

                        <button 
                            type="submit" 
                            class="w-full bg-gradient-to-r from-blue-600 to-blue-700 text-white font-bold py-4 px-6 rounded-xl hover:from-blue-700 hover:to-blue-800 transform hover:scale-105 transition-all shadow-lg">
                            <svg class="w-5 h-5 mr-2 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                            Buscar Operación
                        </button>
                    </form>
                </div>
            </div>

            <!-- Área de Resultados -->
            <div id="resultados" class="hidden">
                <!-- Información de la Operación -->
                <div class="bg-white rounded-2xl shadow-xl p-8 mb-8 border border-blue-100">
                    <h2 class="text-2xl font-bold text-slate-900 mb-6 flex items-center">
                        <span class="text-3xl mr-3">📦</span>
                        Información de la Operación
                    </h2>
                    <div id="infoOperacion" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"></div>
                </div>

                <!-- Tabs -->
                <div class="bg-white rounded-2xl shadow-xl border border-blue-100 overflow-hidden">
                    <div class="flex border-b border-slate-200">
                        <button onclick="cambiarTab('historial')" id="tab-historial" class="tab-button flex-1 px-6 py-4 font-semibold text-slate-700 hover:bg-blue-50 transition-colors border-b-4 border-blue-600">
                            📊 Historial
                        </button>
                        <button onclick="cambiarTab('comentarios')" id="tab-comentarios" class="tab-button flex-1 px-6 py-4 font-semibold text-slate-700 hover:bg-blue-50 transition-colors border-b-4 border-transparent">
                            💬 Comentarios
                        </button>
                        <button onclick="cambiarTab('post-operaciones')" id="tab-post-operaciones" class="tab-button flex-1 px-6 py-4 font-semibold text-slate-700 hover:bg-blue-50 transition-colors border-b-4 border-transparent">
                            📋 Post-Operaciones
                        </button>
                    </div>

                    <div class="p-8">
                        <!-- Historial -->
                        <div id="content-historial" class="tab-content">
                            <div id="listaHistorial"></div>
                        </div>

                        <!-- Comentarios -->
                        <div id="content-comentarios" class="tab-content hidden">
                            <div id="listaComentarios"></div>
                        </div>

                        <!-- Post-Operaciones -->
                        <div id="content-post-operaciones" class="tab-content hidden">
                            <div id="listaPostOperaciones"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Mensaje de Error -->
            <div id="mensajeError" class="hidden max-w-2xl mx-auto">
                <div class="bg-red-50 border-2 border-red-200 rounded-2xl p-6 text-center">
                    <div class="mb-4">
                        <svg class="w-16 h-16 mx-auto text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.732-.833-2.5 0L4.232 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                        </svg>
                    </div>
                    <p class="text-red-800 font-semibold text-lg" id="textoError"></p>
                </div>
            </div>

            <!-- Loading -->
            <div id="loading" class="hidden max-w-2xl mx-auto">
                <div class="bg-white rounded-2xl shadow-xl p-12 text-center border border-blue-100">
                    <div class="animate-spin rounded-full h-16 w-16 border-b-4 border-blue-600 mx-auto mb-4"></div>
                    <p class="text-slate-600 font-semibold">Buscando operación...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        function cambiarTab(tab) {
            // Ocultar todos los contenidos
            document.querySelectorAll('.tab-content').forEach(el => el.classList.add('hidden'));
            document.querySelectorAll('.tab-button').forEach(el => {
                el.classList.remove('border-blue-600', 'text-blue-600');
                el.classList.add('border-transparent', 'text-slate-700');
            });

            // Mostrar el tab seleccionado
            document.getElementById('content-' + tab).classList.remove('hidden');
            document.getElementById('tab-' + tab).classList.add('border-blue-600', 'text-blue-600');
            document.getElementById('tab-' + tab).classList.remove('border-transparent', 'text-slate-700');
        }

        document.getElementById('formBusqueda').addEventListener('submit', async function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const tipo = formData.get('tipo_busqueda');
            const valor = formData.get('valor');

            // Mostrar loading
            document.getElementById('resultados').classList.add('hidden');
            document.getElementById('mensajeError').classList.add('hidden');
            document.getElementById('loading').classList.remove('hidden');

            try {
                const response = await fetch('{{ route("logistica.consulta-publica.buscar") }}?' + new URLSearchParams({
                    tipo_busqueda: tipo,
                    valor: valor
                }));

                const data = await response.json();

                document.getElementById('loading').classList.add('hidden');

                if (data.success) {
                    mostrarResultados(data);
                } else {
                    mostrarError(data.message);
                }
            } catch (error) {
                document.getElementById('loading').classList.add('hidden');
                mostrarError('Error al realizar la búsqueda');
            }
        });

        function mostrarResultados(data) {
            const operacion = data.operacion;

            // Información de la operación
            const infoHTML = `
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-sm font-semibold text-slate-600 mb-1">Tipo</div>
                    <div class="text-lg font-bold text-slate-900">${operacion.operacion || '-'}</div>
                </div>
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-sm font-semibold text-slate-600 mb-1">Cliente</div>
                    <div class="text-lg font-bold text-slate-900">${operacion.cliente || '-'}</div>
                </div>
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-sm font-semibold text-slate-600 mb-1">N° Factura</div>
                    <div class="text-lg font-bold text-slate-900">${operacion.no_factura || '-'}</div>
                </div>
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-sm font-semibold text-slate-600 mb-1">N° Pedimento</div>
                    <div class="text-lg font-bold text-slate-900">${operacion.no_pedimento || '-'}</div>
                </div>
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-sm font-semibold text-slate-600 mb-1">Status</div>
                    <div class="text-lg font-bold">
                        <span class="status-${operacion.color_status} px-3 py-1 rounded-lg">
                            ${operacion.status_manual || operacion.status_calculado}
                        </span>
                    </div>
                </div>
                <div class="bg-blue-50 p-4 rounded-lg">
                    <div class="text-sm font-semibold text-slate-600 mb-1">Fecha Embarque</div>
                    <div class="text-lg font-bold text-slate-900">${operacion.fecha_embarque || '-'}</div>
                </div>
            `;
            document.getElementById('infoOperacion').innerHTML = infoHTML;

            // Historial
            let historialHTML = '';
            if (data.historial && data.historial.length > 0) {
                historialHTML = data.historial.map(h => `
                    <div class="border-l-4 border-blue-500 bg-slate-50 p-4 rounded-lg mb-4">
                        <div class="flex justify-between items-start mb-3">
                            <span class="status-${h.color} px-3 py-1 rounded-lg text-sm font-semibold">
                                ${h.status}
                            </span>
                            <span class="text-sm text-slate-600">${h.fecha}</span>
                        </div>
                        <div class="grid grid-cols-2 gap-2 mb-2 text-sm">
                            ${h.fecha_arribo_aduana ? `<div><span class="text-slate-600">Arribo Aduana:</span> <span class="font-medium">${h.fecha_arribo_aduana}</span></div>` : ''}
                            ${h.fecha_registro ? `<div><span class="text-slate-600">Fecha Registro:</span> <span class="font-medium">${h.fecha_registro}</span></div>` : ''}
                            ${h.dias_transcurridos ? `<div><span class="text-slate-600">Días Transcurridos:</span> <span class="font-medium">${h.dias_transcurridos}</span></div>` : ''}
                            ${h.target_dias ? `<div><span class="text-slate-600">Target:</span> <span class="font-medium">${h.target_dias} días</span></div>` : ''}
                        </div>
                        <p class="text-slate-700 text-sm">${h.descripcion}</p>
                    </div>
                `).join('');
            } else {
                historialHTML = '<p class="text-slate-500 text-center py-8">No hay historial disponible</p>';
            }
            document.getElementById('listaHistorial').innerHTML = historialHTML;

            // Comentarios de la matriz de seguimiento
            let comentariosHTML = '';
            if (data.comentarios_matriz && data.comentarios_matriz.length > 0) {
                comentariosHTML = data.comentarios_matriz.map(c => `
                    <div class="border-l-4 border-green-500 bg-slate-50 p-4 rounded-lg mb-4">
                        <div class="flex justify-between items-start mb-3">
                            <span class="bg-green-100 text-green-800 px-3 py-1 rounded-lg text-sm font-semibold">
                                ${c.usuario}
                            </span>
                            <span class="text-sm text-slate-600">${c.fecha}</span>
                        </div>
                        ${c.status_en_momento ? `<div class="text-xs text-slate-600 mb-2">Status en ese momento: <span class="font-medium">${c.status_en_momento}</span></div>` : ''}
                        <p class="text-slate-700 whitespace-pre-wrap">${c.comentario}</p>
                    </div>
                `).join('');
            } else if (operacion.comentarios) {
                // Mostrar comentario básico si no hay comentarios de matriz
                comentariosHTML = `
                    <div class="bg-slate-50 p-4 rounded-lg">
                        <p class="text-slate-800 whitespace-pre-wrap">${operacion.comentarios}</p>
                    </div>
                `;
            } else {
                comentariosHTML = '<p class="text-slate-500 text-center py-8">No hay comentarios disponibles</p>';
            }
            document.getElementById('listaComentarios').innerHTML = comentariosHTML;

            // Post-Operaciones
            let postOpHTML = '';
            if (data.post_operaciones && data.post_operaciones.length > 0) {
                postOpHTML = data.post_operaciones.map(p => `
                    <div class="bg-slate-50 p-4 rounded-lg mb-4">
                        <div class="flex justify-between items-center mb-2">
                            <span class="font-semibold text-slate-900">${p.nombre}</span>
                            <span class="px-3 py-1 rounded-lg text-sm ${p.status === 'Completado' ? 'bg-green-100 text-green-800' : p.status === 'Pendiente' ? 'bg-yellow-100 text-yellow-800' : 'bg-gray-100 text-gray-800'}">
                                ${p.status}
                            </span>
                        </div>
                        ${p.descripcion ? `<p class="text-sm text-slate-700 mb-2">${p.descripcion}</p>` : ''}
                        ${p.notas_especificas ? `<p class="text-sm text-slate-600 mb-1"><strong>Notas:</strong> ${p.notas_especificas}</p>` : ''}
                        <div class="flex gap-4 text-sm text-slate-600 mt-2">
                            ${p.fecha_asignacion ? `<span>Asignado: ${p.fecha_asignacion}</span>` : ''}
                            ${p.fecha_completado ? `<span>Completado: ${p.fecha_completado}</span>` : ''}
                        </div>
                    </div>
                `).join('');
            } else {
                postOpHTML = '<p class="text-slate-500 text-center py-8">No hay post-operaciones disponibles</p>';
            }
            document.getElementById('listaPostOperaciones').innerHTML = postOpHTML;

            document.getElementById('resultados').classList.remove('hidden');
        }

        function mostrarError(mensaje) {
            document.getElementById('textoError').textContent = mensaje;
            document.getElementById('mensajeError').classList.remove('hidden');
        }
    </script>
</body>
</html>
