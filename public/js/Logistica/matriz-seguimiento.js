document.addEventListener('DOMContentLoaded', function() {
    const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    let operacionActualId = null;
    let cambiosPostOp = {};

    // =========================================================
    // 1. MODAL OPERACIONES (CREAR / EDITAR) - CORREGIDO
    // =========================================================
    window.abrirModal = function() {
        const form = document.getElementById('formOperacion');
        if (form) form.reset();
        document.getElementById('operacionId').value = '';
        document.getElementById('isEditing').value = '';
        document.getElementById('modalTitle').innerText = 'Nueva Operación';
        document.getElementById('modalOperacion').classList.remove('hidden');
    };

    window.cerrarModal = function() {
        document.getElementById('modalOperacion').classList.add('hidden');
    };

    window.editarOperacion = function(id) {
        fetch(`/logistica/operaciones/${id}/historial`)
            .then(res => res.json())
            .then(data => {
                if(data.success && data.operacion) {
                    const op = data.operacion;
                    const form = document.getElementById('formOperacion');
                    
                    document.getElementById('operacionId').value = op.id;
                    document.getElementById('isEditing').value = 'PUT';
                    document.getElementById('modalTitle').innerText = 'Editar Operación #' + op.id;
                    
                    // --- MAPEO DE TODOS LOS CAMPOS ---
                    const fields = [
                        // Información Principal
                        'operacion', 'tipo_operacion_enum', 'ejecutivo', 'cliente', 
                        'proveedor_o_cliente', 'proveedor',
                        // Referencias y Documentos
                        'referencia_cliente', 'referencia_interna', 'clave', 'no_factura',
                        'no_pedimento', 'guia_bl', 'mail_subject',
                        // Aduana y Agente
                        'aduana', 'agente_aduanal', 'referencia_aa', 'transporte',
                        // Fechas
                        'fecha_etd', 'fecha_zarpe', 'fecha_embarque', 'fecha_arribo_aduana',
                        'fecha_modulacion', 'fecha_arribo_planta',
                        // Información Adicional
                        'tipo_carga', 'tipo_incoterm', 'puerto_salida', 'in_charge',
                        'tipo_previo', 'target', 'dias_transito', 'resultado',
                        // Status y Comentarios
                        'status_manual', 'comentarios'
                    ];

                    fields.forEach(field => {
                        const input = form.querySelector(`[name="${field}"]`);
                        if(input) {
                            let value = op[field] || '';
                            
                            // Corrección Fechas
                            if(input.type === 'date' && value) {
                                value = value.split('T')[0].split(' ')[0];
                            }
                            
                            input.value = value;

                            // Corrección para Selects
                            if (input.tagName === 'SELECT' && input.value !== value) {
                                for (let option of input.options) {
                                    if (option.value.trim() === String(value).trim()) {
                                        input.value = option.value;
                                        break;
                                    }
                                }
                            }
                        }
                    });

                    // Manejar checkbox pedimento_en_carpeta
                    const checkPedimento = form.querySelector('[name="pedimento_en_carpeta"]');
                    if (checkPedimento) {
                        checkPedimento.checked = op.pedimento_en_carpeta == 1 || op.pedimento_en_carpeta === true;
                    }
                    
                    document.getElementById('modalOperacion').classList.remove('hidden');
                }
            });
    };

    const formOperacion = document.getElementById('formOperacion');
    if(formOperacion) {
        formOperacion.addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const isPut = document.getElementById('isEditing').value === 'PUT';
            const id = document.getElementById('operacionId').value;
            const url = isPut ? `/logistica/operaciones/${id}` : '/logistica/operaciones';
            
            fetch(url, { 
                method: 'POST', // Laravel usa POST simulando PUT si enviamos _method
                headers: {'X-CSRF-TOKEN': token}, 
                body: formData 
            })
            .then(res => {
                if(res.ok) window.location.reload();
                else alert('Error al guardar. Verifica los datos.');
            })
            .catch(err => alert('Error de conexión'));
        });
    }

    // =========================================================
    // 2. MODAL POST-OPERACIONES (CHECKLIST)
    // =========================================================
    window.verPostOperaciones = function(id) {
        operacionActualId = id;
        cambiosPostOp = {};
        const modal = document.getElementById('modalPostOperaciones');
        const lista = document.getElementById('listaPostOperaciones');
        const loader = document.getElementById('loaderPostOp');
        const empty = document.getElementById('emptyPostOp');

        modal.classList.remove('hidden');
        loader.classList.remove('hidden');
        lista.innerHTML = '';
        empty.classList.add('hidden');

        fetch(`/logistica/post-operaciones/operaciones/${id}`)
            .then(res => res.json())
            .then(data => {
                loader.classList.add('hidden');
                // Renderizar lista si hay datos, sino mostrar mensaje vacío
                if(data.success && data.postOperaciones && data.postOperaciones.length > 0) {
                    renderizarListaPostOp(data.postOperaciones);
                } else {
                    empty.classList.remove('hidden');
                }
                
                // Actualizar título
                const pedimento = data.operacion_info?.no_pedimento || 'S/N';
                document.getElementById('tituloPostOp').innerText = `Folio #${id} | Pedimento: ${pedimento}`;
            })
            .catch(err => {
                loader.classList.add('hidden');
                lista.innerHTML = '<p class="text-red-500 text-center">Error al cargar datos.</p>';
            });
    };

    /**
     * Lógica para Campos Personalizados
     * Optimización: No recarga la página, solo actualiza el DOM.
     */
    window.editarCampoPersonalizado = function(operacionId, campoId, tipo, nombreCampo) {
        let valorActual = document.querySelector(`td[data-operacion-id="${operacionId}"][data-campo-id="${campoId}"] .valor-campo`).innerText.trim();
        if(valorActual === '-') valorActual = '';

        let nuevoValor = null;

        // 1. Manejo de Inputs según el tipo
        if (tipo === 'booleano') {
            // Lógica simple para SI/NO
            const confirmar = confirm(`¿Cambiar "${nombreCampo}" a ${valorActual === 'Sí' ? 'NO' : 'SÍ'}?`);
            if (!confirmar) return;
            nuevoValor = (valorActual === 'Sí') ? '0' : '1'; // Invertir
        } 
        else if (tipo === 'fecha') {
            // Usar un prompt simple o integrar un datepicker modal si se prefiere
            // Para rapidez, usamos prompt validando formato YYYY-MM-DD
            nuevoValor = prompt(`Ingrese fecha para ${nombreCampo} (YYYY-MM-DD):`, valorActual);
        } 
        else {
            // Texto, Número, Decimal
            nuevoValor = prompt(`Editar ${nombreCampo}:`, valorActual);
        }

        // Si el usuario canceló el prompt
        if (nuevoValor === null) return;

        // 2. Mostrar indicador de carga en la celda
        const celda = document.querySelector(`td[data-operacion-id="${operacionId}"][data-campo-id="${campoId}"]`);
        const spanValor = celda.querySelector('.valor-campo');
        const valorAnterior = spanValor.innerText;
        
        spanValor.innerHTML = '<span class="text-blue-500 text-xs">Guardando...</span>';

        // 3. Enviar al Servidor
        fetch('/logistica/campos-personalizados/valor', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': window.token // Asegúrate de tener el token CSRF global
            },
            body: JSON.stringify({
                operacion_id: operacionId,
                campo_id: campoId,
                valor: nuevoValor
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // 4. Actualizar vista con el nuevo valor (Formateo básico)
                let textoMostrar = nuevoValor;
                if(tipo === 'booleano') textoMostrar = (nuevoValor == '1' || nuevoValor === 'true') ? 'Sí' : 'No';
                if(!nuevoValor) textoMostrar = '-';
                
                spanValor.innerText = textoMostrar;
                
                // Efecto visual de éxito
                celda.classList.add('bg-green-50');
                setTimeout(() => celda.classList.remove('bg-green-50'), 1000);
            } else {
                alert('Error al guardar: ' + data.message);
                spanValor.innerText = valorAnterior; // Revertir
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error de conexión');
            spanValor.innerText = valorAnterior; // Revertir
        });
    };

    window.cerrarModalPostOperaciones = function() {
        document.getElementById('modalPostOperaciones').classList.add('hidden');
    };

    function renderizarListaPostOp(tareas) {
        const container = document.getElementById('listaPostOperaciones');
        container.innerHTML = tareas.map(t => {
            const checked = t.status === 'Completado';
            const isNA = t.status === 'No Aplica';
            // Estilos dinámicos
            const bgClass = checked ? 'bg-green-50 border-green-200' : (isNA ? 'bg-slate-100 opacity-60' : 'bg-white border-slate-200');
            const textClass = checked ? 'line-through text-slate-400' : 'text-slate-800';

            return `
            <div class="flex items-center justify-between p-3 rounded-lg border ${bgClass} mb-2 transition-all">
                <div class="flex items-center gap-3 flex-1">
                    <input type="checkbox" id="task_${t.id_asignacion}" ${checked ? 'checked' : ''} ${isNA ? 'disabled' : ''} 
                        onchange="registrarCambioPostOp(${t.id_asignacion}, this.checked)" 
                        class="w-5 h-5 text-indigo-600 rounded border-slate-300 focus:ring-indigo-500 cursor-pointer">
                    
                    <div class="flex flex-col">
                        <label for="task_${t.id_asignacion}" class="font-semibold cursor-pointer select-none ${textClass}">${t.nombre}</label>
                        ${t.descripcion ? `<span class="text-xs text-slate-500">${t.descripcion}</span>` : ''}
                    </div>
                </div>
                
                <button onclick="toggleNAPostOp(${t.id_asignacion}, '${t.status}')" 
                    class="text-xs px-2 py-1 rounded border ml-2 ${isNA ? 'bg-slate-300 text-slate-700' : 'bg-white text-slate-500 hover:bg-slate-50'}">
                    ${isNA ? 'Habilitar' : 'No Aplica'}
                </button>
            </div>`;
        }).join('');
    }

    window.registrarCambioPostOp = function(id, checked) {
        cambiosPostOp[id] = checked ? 'Completado' : 'Pendiente';
        // Feedback visual inmediato
        const label = document.querySelector(`label[for="task_${id}"]`);
        if(checked) label.classList.add('line-through', 'text-slate-400');
        else label.classList.remove('line-through', 'text-slate-400');
    };

    window.toggleNAPostOp = function(id, currentStatus) {
        // Toggle estado No Aplica
        const nuevoEstado = currentStatus === 'No Aplica' ? 'Pendiente' : 'No Aplica';
        cambiosPostOp[id] = nuevoEstado;
        guardarCambiosPostOp(); // Guardado inmediato para refrescar la UI compleja
    };

    window.guardarCambiosPostOp = function() {
        if(Object.keys(cambiosPostOp).length === 0) {
            cerrarModalPostOperaciones();
            return;
        }
        
        // Llamada al backend para guardar cambios masivos
        fetch(`/logistica/post-operaciones/operaciones/${operacionActualId}/actualizar-estados`, {
            method: 'PUT',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': token},
            body: JSON.stringify({ cambios: cambiosPostOp })
        }).then(res => res.json()).then(data => {
            if(data.success) {
                // Éxito
                cerrarModalPostOperaciones();
                window.location.reload(); // Recargar para actualizar barra de progreso
            }
        });
    };

    // =========================================================
    // 3. CONFIGURACIÓN (ADMIN): CAMPOS Y PLANTILLAS POST-OP
    // =========================================================
    window.abrirModalCamposPersonalizados = function() {
        document.getElementById('modalCamposPersonalizados').classList.remove('hidden');
        cargarConfiguracion(); 
    };

    window.cerrarModalCamposPersonalizados = function() {
        document.getElementById('modalCamposPersonalizados').classList.add('hidden');
    };

    function cargarConfiguracion() {
        // Cargar Campos Personalizados
        fetch('/logistica/campos-personalizados')
            .then(r => r.json())
            .then(data => {
                const lista = document.getElementById('listaCamposConfig');
                // Aseguramos que data sea el array, a veces viene directo o dentro de una propiedad
                const campos = Array.isArray(data) ? data : (data.campos || []); 
                
                lista.innerHTML = campos.map(c => `
                    <div class="flex justify-between items-center p-2 border-b text-sm">
                        <span>${c.nombre} <small class="text-gray-400">(${c.tipo})</small></span>
                        <button onclick="eliminarCampo(${c.id})" class="text-red-500 hover:text-red-700">×</button>
                    </div>
                `).join('') || '<p class="text-gray-400 text-sm text-center py-2">Sin campos extra.</p>';
            });

        // Cargar Plantillas Post-Operación (CORREGIDO)
        fetch('/logistica/post-operaciones/globales')
            .then(r => r.json())
            .then(data => {
                const lista = document.getElementById('listaPlantillasConfig');
                if(data.success) {
                    // CORRECCIÓN AQUÍ: Usar 'postOperaciones' (CamelCase) tal como lo envía el Controller
                    lista.innerHTML = data.postOperaciones.map(p => ` 
                        <div class="flex justify-between items-center p-2 border-b text-sm">
                            <span>${p.nombre}</span>
                            <button onclick="eliminarPlantilla(${p.id})" class="text-red-500 hover:text-red-700">×</button>
                        </div>
                    `).join('') || '<p class="text-gray-400 text-sm text-center py-2">Sin tareas globales.</p>';
                }
            });
    }

    // Guardar Nuevo Campo Personalizado (CORREGIDO EL REFRESCO)
    document.getElementById('formNuevoCampo')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const nombre = document.getElementById('newCampoNombre').value;
        if(!nombre) return;

        // Añadimos indicador visual de carga
        const btn = this.querySelector('button');
        const txtOriginal = btn.innerText;
        btn.innerText = 'Guardando...';
        btn.disabled = true;

        fetch('/logistica/campos-personalizados', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': token},
            body: JSON.stringify({ nombre: nombre, tipo: 'texto', activo: 1, orden: 99 })
        }).then(res => res.json())
        .then(data => {
            if(data.success) {
                document.getElementById('newCampoNombre').value = '';
                // CORRECCIÓN CRÍTICA: Recargar la página para ver la nueva columna en la tabla
                // Como es un cambio estructural de la tabla, necesitamos recargar.
                alert('Campo agregado. La página se recargará para mostrar la nueva columna.');
                window.location.reload(); 
            }
        })
        .finally(() => {
             btn.innerText = txtOriginal;
             btn.disabled = false;
        });
    });

    // Guardar Nueva Plantilla Post-Op (Tarea Global)
    document.getElementById('formNuevaPlantilla')?.addEventListener('submit', function(e) {
        e.preventDefault();
        const nombre = document.getElementById('newPlantillaNombre').value;
        if(!nombre) return;

        fetch('/logistica/post-operaciones/globales', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': token},
            body: JSON.stringify({ nombre: nombre, descripcion: 'Tarea estándar' })
        }).then(res => res.json())
        .then(data => {
            if(data.success) {
                document.getElementById('newPlantillaNombre').value = '';
                cargarConfiguracion(); // Aquí no hace falta recargar la página, solo la lista
            }
        });
    });

    window.eliminarCampo = function(id) {
        if(confirm('¿Borrar este campo y sus datos? Se recargará la página.')) {
            fetch(`/logistica/campos-personalizados/${id}`, { 
                method: 'DELETE', headers: {'X-CSRF-TOKEN': token} 
            }).then(() => {
                window.location.reload(); // También recargamos al borrar columna
            });
        }
    };

    // =========================================================
    // 4. OTROS MODALES (HISTORIAL / COMENTARIOS)
    // =========================================================
    window.verHistorial = function(id) {
        document.getElementById('modalHistorial').classList.remove('hidden');
        const container = document.getElementById('historialContent');
        container.innerHTML = 'Cargando...';
        
        fetch(`/logistica/operaciones/${id}/historial`).then(r=>r.json()).then(d => {
            if(d.success) {
                container.innerHTML = d.historial.map(h => `
                    <div class="mb-3 pl-3 border-l-4 border-blue-400">
                        <div class="text-xs text-gray-500">${h.fecha_registro || h.created_at}</div>
                        <div class="font-bold text-sm">${h.operacion_status}</div>
                        <div class="text-sm text-gray-700">${h.observaciones || ''}</div>
                    </div>
                `).join('');
            }
        });
    };
    window.cerrarModalHistorial = function() { document.getElementById('modalHistorial').classList.add('hidden'); };

    window.verComentarios = function(id) {
        operacionActualId = id; // Guardar ID para enviar
        document.getElementById('modalComentarios').classList.remove('hidden');
        // Implementar carga de comentarios aquí...
    };
    window.cerrarModalComentarios = function() { document.getElementById('modalComentarios').classList.add('hidden'); };

    // =========================================================
    // 5. CONFIGURACIÓN DE COLUMNAS POR EJECUTIVO
    // =========================================================
    let configColumnasPorEjecutivo = {};
    let ejecutivoSeleccionadoId = null;
    let columnasPredeterminadas = [];

    window.cargarConfiguracionEjecutivo = function(empleadoId) {
        if (!empleadoId) {
            document.getElementById('contenedorColumnasOpcionales').classList.add('hidden');
            document.getElementById('mensajeSeleccionarEjecutivo').classList.remove('hidden');
            return;
        }

        ejecutivoSeleccionadoId = empleadoId;
        
        fetch(`/logistica/columnas-config/ejecutivo/${empleadoId}`)
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    configColumnasPorEjecutivo = data.configuracion;
                    columnasPredeterminadas = Object.keys(data.columnasPredeterminadas);
                    renderizarColumnasOpcionales(data.configuracion, data.columnasPredeterminadas);
                    document.getElementById('contenedorColumnasOpcionales').classList.remove('hidden');
                    document.getElementById('mensajeSeleccionarEjecutivo').classList.add('hidden');
                }
            })
            .catch(err => {
                console.error('Error cargando configuración:', err);
            });
    };

    function renderizarColumnasOpcionales(configuracion, columnasPred) {
        const container = document.getElementById('listaColumnasOpcionales');
        container.innerHTML = '';

        Object.entries(configuracion).forEach(([columna, config]) => {
            const isActive = config.visible;
            const despuesDe = config.mostrar_despues_de || 'comentarios';

            const item = document.createElement('div');
            item.className = `p-4 rounded-xl border ${isActive ? 'bg-green-50 border-green-200' : 'bg-white border-slate-200'}`;
            item.innerHTML = `
                <div class="flex items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="font-medium text-slate-800">${config.nombre_es}</div>
                        <div class="text-xs text-slate-500">${config.nombre_en}</div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-xs">
                            <label class="text-slate-500">Mostrar después de:</label>
                            <select 
                                id="pos_${columna}" 
                                class="ml-1 text-xs rounded border-slate-300 py-1 px-2"
                                onchange="actualizarPosicionColumna('${columna}', this.value)"
                                ${!isActive ? 'disabled' : ''}
                            >
                                ${Object.entries(columnasPred).map(([key, nombre]) => 
                                    `<option value="${key}" ${despuesDe === key ? 'selected' : ''}>${nombre}</option>`
                                ).join('')}
                            </select>
                        </div>
                        <button 
                            type="button"
                            onclick="toggleColumnaOpcional('${columna}')"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${isActive ? 'bg-green-500' : 'bg-slate-300'}"
                        >
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${isActive ? 'translate-x-6' : 'translate-x-1'}"></span>
                        </button>
                    </div>
                </div>
            `;
            container.appendChild(item);
        });
    }

    window.toggleColumnaOpcional = function(columna) {
        configColumnasPorEjecutivo[columna].visible = !configColumnasPorEjecutivo[columna].visible;
        
        // Re-renderizar para actualizar UI
        fetch(`/logistica/columnas-config/ejecutivo/${ejecutivoSeleccionadoId}`)
            .then(res => res.json())
            .then(data => {
                renderizarColumnasOpcionales(configColumnasPorEjecutivo, data.columnasPredeterminadas);
            });
    };

    window.actualizarPosicionColumna = function(columna, despuesDe) {
        configColumnasPorEjecutivo[columna].mostrar_despues_de = despuesDe;
    };

    window.guardarConfiguracionColumnas = function() {
        if (!ejecutivoSeleccionadoId) {
            alert('Selecciona un ejecutivo primero');
            return;
        }

        const columnas = Object.entries(configColumnasPorEjecutivo).map(([columna, config]) => ({
            columna: columna,
            visible: config.visible,
            mostrar_despues_de: config.mostrar_despues_de
        }));

        fetch('/logistica/columnas-config/guardar-completa', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({
                empleado_id: ejecutivoSeleccionadoId,
                columnas: columnas
            })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                alert('Configuración guardada correctamente');
                // Recargar para ver los cambios en la matriz
                window.location.reload();
            } else {
                alert('Error al guardar la configuración');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error al guardar');
        });
    };

});
