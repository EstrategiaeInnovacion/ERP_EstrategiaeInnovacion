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
                    
                    // Cargar valores de campos personalizados
                    cargarValoresCamposPersonalizados(id, form);
                    
                    document.getElementById('modalOperacion').classList.remove('hidden');
                }
            });
    };

    // Función para cargar valores de campos personalizados al editar
    function cargarValoresCamposPersonalizados(operacionId, form) {
        fetch(`/logistica/campos-personalizados/operacion/${operacionId}/valores`)
            .then(res => res.json())
            .then(valores => {
                // valores es un objeto {campo_id: valor}
                Object.entries(valores).forEach(([campoId, valor]) => {
                    const inputName = `campo_${campoId}`;
                    const input = form.querySelector(`[name="${inputName}"]`);
                    const inputMultiple = form.querySelectorAll(`[name="${inputName}[]"]`);
                    
                    if (input) {
                        if (input.type === 'checkbox' && inputMultiple.length === 0) {
                            // Checkbox simple (booleano)
                            input.checked = valor == '1' || valor === 'true' || valor === true;
                        } else if (input.type === 'date' && valor) {
                            // Fecha
                            input.value = valor.split('T')[0].split(' ')[0];
                        } else {
                            // Texto, select, etc.
                            input.value = valor || '';
                        }
                    }
                    
                    // Checkbox múltiple
                    if (inputMultiple.length > 0) {
                        try {
                            const valoresArray = typeof valor === 'string' ? JSON.parse(valor) : valor;
                            if (Array.isArray(valoresArray)) {
                                inputMultiple.forEach(cb => {
                                    cb.checked = valoresArray.includes(cb.value);
                                });
                            }
                        } catch(e) {
                            console.log('Error parsing multiple values:', e);
                        }
                    }
                });
            })
            .catch(err => console.log('Error cargando campos personalizados:', err));
    }

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

    // =========================================================
    // SISTEMA DE PESTAÑAS PARA CONFIGURACIÓN
    // =========================================================
    window.mostrarTabConfig = function(tab) {
        // Ocultar todos los paneles
        document.querySelectorAll('.tab-config-panel').forEach(panel => panel.classList.add('hidden'));
        // Desactivar todos los botones de tabs
        document.querySelectorAll('.tab-config-btn').forEach(btn => {
            btn.classList.remove('border-blue-500', 'text-blue-600');
            btn.classList.add('border-transparent', 'text-slate-500');
        });
        
        // Mostrar panel seleccionado y activar tab
        const panelMap = {
            'columnas': 'panelColumnas',
            'campos': 'panelCampos',
            'checklist': 'panelChecklist'
        };
        const tabMap = {
            'columnas': 'tabColumnas',
            'campos': 'tabCampos',
            'checklist': 'tabChecklist'
        };
        
        document.getElementById(panelMap[tab])?.classList.remove('hidden');
        const activeTab = document.getElementById(tabMap[tab]);
        if(activeTab) {
            activeTab.classList.remove('border-transparent', 'text-slate-500');
            activeTab.classList.add('border-blue-500', 'text-blue-600');
        }
    };

    // Mostrar/ocultar campo de opciones según tipo seleccionado
    window.mostrarOpcionesCampo = function(tipo) {
        const contenedor = document.getElementById('contenedorOpcionesCampo');
        if(tipo === 'selector' || tipo === 'multiple') {
            contenedor.classList.remove('hidden');
        } else {
            contenedor.classList.add('hidden');
        }
    };

    // Iconos para tipos de campo
    const TIPO_ICONOS = {
        'texto': '📝',
        'descripcion': '📄',
        'numero': '🔢',
        'decimal': '💲',
        'moneda': '💰',
        'fecha': '📅',
        'booleano': '✅',
        'selector': '📋',
        'multiple': '☑️',
        'email': '📧',
        'telefono': '📞',
        'url': '🔗'
    };

    function cargarConfiguracion() {
        // Cargar Campos Personalizados
        cargarCamposPersonalizados();

        // Cargar Plantillas Post-Operación (Checklist Estándar)
        fetch('/logistica/post-operaciones/globales')
            .then(r => r.json())
            .then(data => {
                const lista = document.getElementById('listaPlantillasConfig');
                if(data.success && data.postOperaciones.length > 0) {
                    lista.innerHTML = data.postOperaciones.map(p => ` 
                        <div class="flex justify-between items-center p-3 bg-slate-50 rounded-lg border border-slate-200 hover:bg-slate-100 transition-colors" id="plantilla-${p.id}">
                            <div class="flex-1">
                                <input type="text" value="${p.nombre}" 
                                    class="bg-transparent border-none text-sm font-medium text-slate-700 w-full focus:outline-none focus:bg-white focus:border focus:border-blue-300 rounded px-2 py-1"
                                    id="plantilla-nombre-${p.id}"
                                    onchange="actualizarPlantilla(${p.id})"
                                    onfocus="this.classList.add('bg-white', 'border', 'border-blue-300')"
                                    onblur="this.classList.remove('bg-white', 'border', 'border-blue-300')">
                            </div>
                            <div class="flex gap-1 ml-2">
                                <button onclick="confirmarEliminarPlantilla(${p.id}, '${p.nombre.replace(/'/g, "\\'")}')" 
                                    class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors" 
                                    title="Eliminar tarea">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    `).join('');
                } else {
                    lista.innerHTML = '<p class="text-gray-400 text-sm text-center py-4">No hay tareas configuradas. Agrega una nueva tarea arriba.</p>';
                }
            })
            .catch(err => {
                console.error('Error cargando plantillas:', err);
                document.getElementById('listaPlantillasConfig').innerHTML = '<p class="text-red-400 text-sm text-center py-4">Error al cargar tareas.</p>';
            });
    }

    // Actualizar nombre de plantilla (inline edit)
    window.actualizarPlantilla = function(id) {
        const input = document.getElementById(`plantilla-nombre-${id}`);
        const nuevoNombre = input.value.trim();
        
        if(!nuevoNombre) {
            alert('El nombre no puede estar vacío');
            cargarConfiguracion();
            return;
        }

        fetch(`/logistica/post-operaciones/globales/${id}`, {
            method: 'PUT',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': token},
            body: JSON.stringify({ nombre: nuevoNombre })
        })
        .then(r => r.json())
        .then(data => {
            if(data.success) {
                // Feedback visual sutil
                input.classList.add('bg-green-50');
                setTimeout(() => input.classList.remove('bg-green-50'), 1000);
            } else {
                alert('Error al actualizar');
                cargarConfiguracion();
            }
        })
        .catch(() => {
            alert('Error de conexión');
            cargarConfiguracion();
        });
    };

    // Confirmar eliminación de plantilla
    window.confirmarEliminarPlantilla = function(id, nombre) {
        if(confirm(`¿Eliminar la tarea "${nombre}"? Esta acción no se puede deshacer.`)) {
            eliminarPlantilla(id);
        }
    };

    // Eliminar plantilla
    window.eliminarPlantilla = function(id) {
        const elemento = document.getElementById(`plantilla-${id}`);
        if(elemento) {
            elemento.style.opacity = '0.5';
            elemento.style.pointerEvents = 'none';
        }

        fetch(`/logistica/post-operaciones/globales/${id}`, {
            method: 'DELETE',
            headers: {'X-CSRF-TOKEN': token}
        })
        .then(r => r.json())
        .then(data => {
            if(data.success) {
                cargarConfiguracion();
            } else {
                alert('Error al eliminar');
                if(elemento) {
                    elemento.style.opacity = '1';
                    elemento.style.pointerEvents = 'auto';
                }
            }
        })
        .catch(() => {
            alert('Error de conexión');
            cargarConfiguracion();
        });
    };

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

    // =========================================================
    // FORMULARIO COMPLETO PARA NUEVO CAMPO PERSONALIZADO
    // =========================================================
    document.getElementById('formNuevoCampoCompleto')?.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const nombre = document.getElementById('nuevoCampoNombre').value.trim();
        const tipo = document.getElementById('nuevoCampoTipo').value;
        const requerido = document.getElementById('nuevoCampoRequerido').checked;
        const activo = document.getElementById('nuevoCampoActivo').checked;
        const opcionesRaw = document.getElementById('nuevoCampoOpciones')?.value || '';
        
        if(!nombre) {
            alert('El nombre del campo es obligatorio');
            return;
        }

        // Procesar opciones para selector/multiple
        let opciones = null;
        if(tipo === 'selector' || tipo === 'multiple') {
            opciones = opcionesRaw.split('\n').map(o => o.trim()).filter(o => o);
            if(opciones.length === 0) {
                alert('Debes agregar al menos una opción para este tipo de campo');
                return;
            }
        }

        const btn = this.querySelector('button[type="submit"]');
        const txtOriginal = btn.innerHTML;
        btn.innerHTML = '<svg class="w-4 h-4 animate-spin" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg> Guardando...';
        btn.disabled = true;

        fetch('/logistica/campos-personalizados', {
            method: 'POST',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': token},
            body: JSON.stringify({ 
                nombre: nombre, 
                tipo: tipo, 
                requerido: requerido ? 1 : 0,
                activo: activo ? 1 : 0, 
                opciones: opciones,
                orden: 99 
            })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                // Limpiar formulario
                document.getElementById('nuevoCampoNombre').value = '';
                document.getElementById('nuevoCampoTipo').value = 'texto';
                document.getElementById('nuevoCampoRequerido').checked = false;
                document.getElementById('nuevoCampoActivo').checked = true;
                document.getElementById('nuevoCampoOpciones').value = '';
                document.getElementById('contenedorOpcionesCampo').classList.add('hidden');
                
                alert('Campo creado correctamente. La página se recargará para mostrar la nueva columna.');
                window.location.reload();
            } else {
                alert('Error al crear: ' + (data.message || 'Error desconocido'));
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error de conexión');
        })
        .finally(() => {
            btn.innerHTML = txtOriginal;
            btn.disabled = false;
        });
    });

    // Función para cargar y mostrar campos personalizados con todo el detalle
    function cargarCamposPersonalizados() {
        const lista = document.getElementById('listaCamposPersonalizados');
        const contador = document.getElementById('contadorCampos');
        
        if(!lista) return;
        
        fetch('/logistica/campos-personalizados')
            .then(r => r.json())
            .then(data => {
                const campos = Array.isArray(data) ? data : (data.campos || []);
                
                if(contador) {
                    contador.textContent = `${campos.length} campo${campos.length !== 1 ? 's' : ''}`;
                }
                
                if(campos.length === 0) {
                    lista.innerHTML = `
                        <div class="text-center py-8 text-slate-400">
                            <svg class="w-12 h-12 mx-auto mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m-9 1V7a2 2 0 012-2h6l2 2h6a2 2 0 012 2v8a2 2 0 01-2 2H5a2 2 0 01-2-2z"></path>
                            </svg>
                            <p class="text-sm">No hay campos personalizados.</p>
                            <p class="text-xs">Usa el formulario de arriba para crear uno.</p>
                        </div>
                    `;
                    return;
                }
                
                lista.innerHTML = campos.map(c => {
                    const icono = TIPO_ICONOS[c.tipo] || '📝';
                    const esActivo = c.activo == 1;
                    const esRequerido = c.requerido == 1;
                    
                    let opcionesHtml = '';
                    if((c.tipo === 'selector' || c.tipo === 'multiple') && c.opciones) {
                        try {
                            const opts = typeof c.opciones === 'string' ? JSON.parse(c.opciones) : c.opciones;
                            if(Array.isArray(opts) && opts.length > 0) {
                                opcionesHtml = `<div class="text-xs text-slate-400 mt-1">Opciones: ${opts.join(', ')}</div>`;
                            }
                        } catch(e) {}
                    }
                    
                    return `
                        <div class="p-3 rounded-lg border ${esActivo ? 'bg-white border-slate-200' : 'bg-slate-100 border-slate-200 opacity-60'} hover:shadow-sm transition-shadow" id="campo-item-${c.id}">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="text-lg">${icono}</span>
                                        <span class="font-medium text-slate-800">${c.nombre}</span>
                                        ${esRequerido ? '<span class="text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded">Obligatorio</span>' : ''}
                                        ${!esActivo ? '<span class="text-xs bg-slate-200 text-slate-600 px-1.5 py-0.5 rounded">Inactivo</span>' : ''}
                                    </div>
                                    <div class="text-xs text-slate-500 mt-1">Tipo: ${c.tipo}</div>
                                    ${opcionesHtml}
                                </div>
                                <div class="flex items-center gap-1">
                                    <button onclick="toggleActivoCampo(${c.id}, ${esActivo ? 0 : 1})" 
                                        class="p-1.5 ${esActivo ? 'text-green-600 hover:bg-green-50' : 'text-slate-400 hover:bg-slate-50'} rounded transition-colors" 
                                        title="${esActivo ? 'Desactivar' : 'Activar'}">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="${esActivo ? 'M15 12a3 3 0 11-6 0 3 3 0 016 0z M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z' : 'M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21'}"></path>
                                        </svg>
                                    </button>
                                    <button onclick="confirmarEliminarCampo(${c.id}, '${c.nombre.replace(/'/g, "\\'")}')" 
                                        class="p-1.5 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded transition-colors" 
                                        title="Eliminar campo">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('');
            })
            .catch(err => {
                console.error('Error cargando campos:', err);
                lista.innerHTML = '<p class="text-red-400 text-sm text-center py-4">Error al cargar campos.</p>';
            });
    }

    // Toggle activo/inactivo de campo
    window.toggleActivoCampo = function(id, nuevoEstado) {
        const elemento = document.getElementById(`campo-item-${id}`);
        if(elemento) {
            elemento.style.opacity = '0.5';
            elemento.style.pointerEvents = 'none';
        }
        
        fetch(`/logistica/campos-personalizados/${id}/toggle-activo`, {
            method: 'PUT',
            headers: {'Content-Type': 'application/json', 'X-CSRF-TOKEN': token},
            body: JSON.stringify({ activo: nuevoEstado })
        })
        .then(r => r.json())
        .then(data => {
            if(data.success) {
                // Recargar para mostrar cambios en la tabla
                window.location.reload();
            } else {
                alert('Error al actualizar');
                if(elemento) {
                    elemento.style.opacity = '1';
                    elemento.style.pointerEvents = 'auto';
                }
            }
        })
        .catch(() => {
            alert('Error de conexión');
            cargarCamposPersonalizados();
        });
    };

    // Confirmar eliminación de campo
    window.confirmarEliminarCampo = function(id, nombre) {
        if(confirm(`¿Eliminar el campo "${nombre}"?\n\nEsto eliminará todos los valores guardados de este campo. Esta acción no se puede deshacer.`)) {
            eliminarCampo(id);
        }
    };

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
    let configCamposPersonalizados = {};
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
                    configCamposPersonalizados = data.camposPersonalizados || {};
                    columnasPredeterminadas = Object.keys(data.columnasPredeterminadas);
                    renderizarColumnasOpcionales(data.configuracion, data.columnasPredeterminadas, data.camposPersonalizados);
                    document.getElementById('contenedorColumnasOpcionales').classList.remove('hidden');
                    document.getElementById('mensajeSeleccionarEjecutivo').classList.add('hidden');
                }
            })
            .catch(err => {
                console.error('Error cargando configuración:', err);
            });
    };

    function renderizarColumnasOpcionales(configuracion, columnasPred, camposPersonalizados = {}) {
        const container = document.getElementById('listaColumnasOpcionales');
        container.innerHTML = '';
        
        // Sección 1: Columnas Opcionales del Sistema
        const seccionColumnas = document.createElement('div');
        seccionColumnas.innerHTML = `
            <h5 class="font-semibold text-slate-700 mb-3 flex items-center gap-2">
                <svg class="w-4 h-4 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7m0 10a2 2 0 002 2h2a2 2 0 002-2V7a2 2 0 00-2-2h-2a2 2 0 00-2 2"></path></svg>
                Columnas del Sistema
            </h5>
        `;
        container.appendChild(seccionColumnas);

        Object.entries(configuracion).forEach(([columna, config]) => {
            const item = crearItemColumna(columna, config, columnasPred, false);
            container.appendChild(item);
        });
        
        // Sección 2: Campos Personalizados
        if (Object.keys(camposPersonalizados).length > 0) {
            const seccionCampos = document.createElement('div');
            seccionCampos.className = 'mt-6 pt-4 border-t border-slate-200';
            seccionCampos.innerHTML = `
                <h5 class="font-semibold text-slate-700 mb-3 flex items-center gap-2">
                    <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
                    Campos Personalizados
                    <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full">${Object.keys(camposPersonalizados).length}</span>
                </h5>
            `;
            container.appendChild(seccionCampos);
            
            Object.entries(camposPersonalizados).forEach(([columna, config]) => {
                const item = crearItemColumna(columna, config, columnasPred, true);
                container.appendChild(item);
            });
        }
    }
    
    function crearItemColumna(columna, config, columnasPred, esCampoPersonalizado) {
        const isActiveIndividual = config.visible;
        const isGlobal = config.es_global || false;
        const despuesDe = config.mostrar_despues_de || 'comentarios';
        const isVisibleForUser = isActiveIndividual || isGlobal;
        
        // Indicadores de tipo para campos personalizados
        const tipoIconos = {
            'texto': '📝', 'descripcion': '📄', 'numero': '🔢', 'decimal': '💲',
            'moneda': '💰', 'fecha': '📅', 'booleano': '✅', 'selector': '📋',
            'multiple': '☑️', 'email': '📧', 'telefono': '📞', 'url': '🔗'
        };
        const tipoIcono = esCampoPersonalizado && config.tipo ? (tipoIconos[config.tipo] || '📝') : '';
        const requeridoBadge = esCampoPersonalizado && config.requerido ? '<span class="text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded ml-2">Obligatorio</span>' : '';

        const item = document.createElement('div');
        item.className = `p-4 rounded-xl border mb-2 ${isGlobal ? 'bg-blue-50 border-blue-200' : (isActiveIndividual ? 'bg-green-50 border-green-200' : 'bg-white border-slate-200')}`;
        item.innerHTML = `
            <div class="flex flex-col gap-3">
                <div class="flex items-center justify-between gap-4">
                    <div class="flex-1">
                        <div class="font-medium text-slate-800">
                            ${tipoIcono ? `<span class="mr-1">${tipoIcono}</span>` : ''}${config.nombre_es}${requeridoBadge}
                        </div>
                        <div class="text-xs text-slate-500">${esCampoPersonalizado && config.tipo ? `Tipo: ${config.tipo}` : config.nombre_en}</div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="text-xs">
                            <label class="text-slate-500">Después de:</label>
                            <select 
                                id="pos_${columna}" 
                                class="ml-1 text-xs rounded border-slate-300 py-1 px-2"
                                onchange="actualizarPosicionColumna('${columna}', this.value, ${esCampoPersonalizado})"
                                ${!isVisibleForUser ? 'disabled' : ''}
                            >
                                ${Object.entries(columnasPred).map(([key, nombre]) => 
                                    `<option value="${key}" ${despuesDe === key ? 'selected' : ''}>${nombre}</option>`
                                ).join('')}
                            </select>
                        </div>
                    </div>
                </div>
                <div class="flex items-center justify-between border-t border-slate-200 pt-3">
                    <!-- Checkbox Mostrar a todos -->
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input 
                            type="checkbox" 
                            id="global_${columna}"
                            ${isGlobal ? 'checked' : ''}
                            onchange="toggleColumnaGlobal('${columna}', this.checked)"
                            class="rounded border-slate-300 text-blue-600 focus:ring-blue-500"
                        >
                        <span class="text-sm text-slate-600">Mostrar a todos</span>
                        ${isGlobal ? '<span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full">Activo global</span>' : ''}
                    </label>
                    
                    <!-- Toggle solo para este ejecutivo -->
                    <div class="flex items-center gap-2">
                        <span class="text-xs text-slate-500">Solo este ejecutivo:</span>
                        <button 
                            type="button"
                            onclick="toggleColumnaOpcional('${columna}', ${esCampoPersonalizado})"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors ${isActiveIndividual ? 'bg-green-500' : 'bg-slate-300'} ${isGlobal ? 'opacity-50' : ''}"
                            ${isGlobal ? 'disabled title="Ya está activo para todos"' : ''}
                        >
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform ${isActiveIndividual ? 'translate-x-6' : 'translate-x-1'}"></span>
                        </button>
                    </div>
                </div>
            </div>
        `;
        return item;
    }

    // Toggle para mostrar a todos (global)
    window.toggleColumnaGlobal = function(columna, checked) {
        const despuesDe = document.getElementById(`pos_${columna}`)?.value || 'comentarios';
        
        fetch('/logistica/columnas-config/guardar-global', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({
                columna: columna,
                visible: checked,
                mostrar_despues_de: despuesDe
            })
        })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                // Recargar la configuración para actualizar la UI
                cargarConfiguracionEjecutivo(ejecutivoSeleccionadoId);
            } else {
                alert('Error al guardar configuración global');
            }
        })
        .catch(err => {
            console.error('Error:', err);
            alert('Error de conexión');
        });
    };

    window.toggleColumnaOpcional = function(columna, esCampoPersonalizado = false) {
        // Actualizar en el objeto correspondiente
        if (esCampoPersonalizado) {
            configCamposPersonalizados[columna].visible = !configCamposPersonalizados[columna].visible;
        } else {
            configColumnasPorEjecutivo[columna].visible = !configColumnasPorEjecutivo[columna].visible;
        }
        
        // Re-renderizar para actualizar UI
        fetch(`/logistica/columnas-config/ejecutivo/${ejecutivoSeleccionadoId}`)
            .then(res => res.json())
            .then(data => {
                // Actualizar las configuraciones locales con los datos del servidor
                renderizarColumnasOpcionales(
                    data.configuracion, 
                    data.columnasPredeterminadas,
                    data.camposPersonalizados
                );
            });
    };

    window.actualizarPosicionColumna = function(columna, despuesDe, esCampoPersonalizado = false) {
        if (esCampoPersonalizado) {
            configCamposPersonalizados[columna].mostrar_despues_de = despuesDe;
        } else {
            configColumnasPorEjecutivo[columna].mostrar_despues_de = despuesDe;
        }
    };

    window.guardarConfiguracionColumnas = function() {
        if (!ejecutivoSeleccionadoId) {
            alert('Selecciona un ejecutivo primero');
            return;
        }

        // Columnas opcionales del sistema
        const columnas = Object.entries(configColumnasPorEjecutivo).map(([columna, config]) => ({
            columna: columna,
            visible: config.visible,
            mostrar_despues_de: config.mostrar_despues_de
        }));
        
        // Campos personalizados
        const camposPersonalizados = Object.entries(configCamposPersonalizados).map(([columna, config]) => ({
            columna: columna,
            visible: config.visible,
            mostrar_despues_de: config.mostrar_despues_de
        }));
        
        // Combinar ambos arrays
        const todasLasColumnas = [...columnas, ...camposPersonalizados];

        fetch('/logistica/columnas-config/guardar-completa', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': token
            },
            body: JSON.stringify({
                empleado_id: ejecutivoSeleccionadoId,
                columnas: todasLasColumnas
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
