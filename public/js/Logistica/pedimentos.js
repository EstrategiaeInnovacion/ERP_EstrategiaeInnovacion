// Variables globales
let pedimentoActual = null;
let monedas = [];

// Funciones de selección múltiple
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAll');
    const checkboxes = document.querySelectorAll('.pedimento-checkbox');
    
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll.checked;
    });
    
    updateSelectAllButton();
}

function updateSelectAll() {
    const checkboxes = document.querySelectorAll('.pedimento-checkbox');
    const selectAll = document.getElementById('selectAll');
    
    const checkedBoxes = document.querySelectorAll('.pedimento-checkbox:checked');
    selectAll.checked = checkedBoxes.length === checkboxes.length;
    
    updateSelectAllButton();
}

function updateSelectAllButton() {
    const checkedBoxes = document.querySelectorAll('.pedimento-checkbox:checked');
    const btnMarcarPagados = document.getElementById('btnMarcarPagados');
    
    if (checkedBoxes.length > 0) {
        btnMarcarPagados.classList.remove('hidden');
    } else {
        btnMarcarPagados.classList.add('hidden');
    }
}

// Funciones de modales
function editarPedimento(id) {
    fetch(`/logistica/pedimentos/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const pedimento = data.pedimento;
                document.getElementById('pedimentoId').value = pedimento.id;
                document.getElementById('estadoPago').value = pedimento.estado_pago;
                document.getElementById('fechaPago').value = pedimento.fecha_pago || '';
                document.getElementById('monto').value = pedimento.monto || '';
                document.getElementById('fechaVencimiento').value = pedimento.fecha_vencimiento ? pedimento.fecha_vencimiento.split('T')[0] : '';
                document.getElementById('observaciones').value = pedimento.observaciones_pago || '';
                
                document.getElementById('modalEditar').classList.remove('hidden');
            } else {
                alert('Error al cargar los datos: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar los datos del pedimento');
        });
}

function cerrarModalEditar() {
    document.getElementById('modalEditar').classList.add('hidden');
    document.getElementById('formEditar').reset();
}

function verDetalles(id) {
    fetch(`/logistica/pedimentos/${id}`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                mostrarDetallesPedimento(data.pedimento, data.operaciones);
            } else {
                alert('Error al cargar los detalles: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al cargar los detalles');
        });
}

function mostrarDetallesPedimento(pedimento, operaciones) {
    const contenido = `
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div class="bg-gray-50 p-4 rounded-lg">
                <h4 class="font-semibold mb-3">Información del Pedimento</h4>
                <div class="space-y-2 text-sm">
                    <div><span class="font-medium">Número:</span> ${pedimento.clave}</div>
                    <div><span class="font-medium">Estado:</span> ${getEstadoHtml(pedimento)}</div>
                    <div><span class="font-medium">Fecha de Pago:</span> ${pedimento.fecha_pago ? new Date(pedimento.fecha_pago).toLocaleDateString('es-ES') : 'Sin fecha'}</div>
                    <div><span class="font-medium">Monto:</span> ${pedimento.monto ? '$' + parseFloat(pedimento.monto).toLocaleString('es-ES', {minimumFractionDigits: 2}) : 'Sin monto'}</div>
                    <div><span class="font-medium">Vencimiento:</span> ${pedimento.fecha_vencimiento ? new Date(pedimento.fecha_vencimiento).toLocaleDateString('es-ES') : 'Sin fecha'}</div>
                </div>
            </div>
            <div class="bg-gray-50 p-4 rounded-lg">
                <h4 class="font-semibold mb-3">Observaciones</h4>
                <p class="text-sm text-gray-600">${pedimento.observaciones_pago || 'Sin observaciones'}</p>
            </div>
        </div>
        
        <div class="bg-gray-50 p-4 rounded-lg">
            <h4 class="font-semibold mb-3">Operaciones Asociadas (${operaciones.length})</h4>
            <div class="overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead>
                        <tr class="border-b">
                            <th class="text-left py-2">ID</th>
                            <th class="text-left py-2">Cliente</th>
                            <th class="text-left py-2">Ejecutivo</th>
                            <th class="text-left py-2">Tipo</th>
                            <th class="text-left py-2">Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${operaciones.map(op => `
                            <tr class="border-b">
                                <td class="py-2">#${op.id}</td>
                                <td class="py-2">${op.cliente || '-'}</td>
                                <td class="py-2">${op.ejecutivo?.nombre || '-'}</td>
                                <td class="py-2">${op.tipo_operacion_enum || '-'}</td>
                                <td class="py-2">${new Date(op.created_at).toLocaleDateString('es-ES')}</td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            </div>
        </div>
    `;
    
    document.getElementById('contenidoDetalles').innerHTML = contenido;
    document.getElementById('modalDetalles').classList.remove('hidden');
}

function getEstadoHtml(pedimento) {
    const estados = {
        'pendiente': '<span class="px-2 py-1 bg-yellow-100 text-yellow-800 rounded-full text-xs">⏳ Pendiente</span>',
        'pagado': '<span class="px-2 py-1 bg-green-100 text-green-800 rounded-full text-xs">✅ Pagado</span>',
        'vencido': '<span class="px-2 py-1 bg-red-100 text-red-800 rounded-full text-xs">❌ Vencido</span>'
    };
    return estados[pedimento.estado_pago] || estados['pendiente'];
}

function cerrarModalDetalles() {
    document.getElementById('modalDetalles').classList.add('hidden');
}

// Funciones de acciones
function eliminarPedimento(id) {
    if (confirm('¿Estás seguro de que deseas eliminar este pedimento?')) {
        fetch(`/logistica/pedimentos/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Content-Type': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                location.reload();
            } else {
                alert('Error: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al eliminar el pedimento');
        });
    }
}

function marcarPagadosSeleccionados() {
    const checkboxes = document.querySelectorAll('.pedimento-checkbox:checked');
    if (checkboxes.length === 0) {
        alert('Selecciona al menos un pedimento');
        return;
    }
    
    const fechaPago = prompt('Ingresa la fecha de pago (YYYY-MM-DD):');
    if (!fechaPago) return;
    
    const monto = prompt('Ingresa el monto (opcional):');
    
    const pedimentoIds = Array.from(checkboxes).map(cb => cb.value);
    
    fetch('/logistica/pedimentos/marcar-pagados', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            pedimentos: pedimentoIds,
            fecha_pago: fechaPago,
            monto: monto || null
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            location.reload();
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error al procesar los pagos');
    });
}

// Cargar monedas al iniciar la página
document.addEventListener('DOMContentLoaded', function() {
    cargarMonedas();
    initializeEventListeners();
});

async function cargarMonedas() {
    try {
        const response = await fetch('https://api.appnexus.com/currency');
        const data = await response.json();
        
        // Extraer monedas de la respuesta de AppNexus
        const monedas = data.response?.currencies || [];
        
        const selectMonedas = document.getElementById('pedimento-moneda');
        if (selectMonedas) {
            selectMonedas.innerHTML = '';
            
            // Monedas prioritarias
            const monedasPrioritarias = ['USD', 'MXN', 'EUR', 'CAD', 'GBP'];
            
            monedasPrioritarias.forEach(codigo => {
                const moneda = monedas.find(m => m.code === codigo) || { code: codigo, name: getMonedaName(codigo) };
                const option = document.createElement('option');
                option.value = moneda.code;
                option.textContent = `${moneda.code} - ${moneda.name || getMonedaName(moneda.code)}`;
                if (moneda.code === 'MXN') option.selected = true;
                selectMonedas.appendChild(option);
            });
            
            // Agregar otras monedas
            monedas.forEach(moneda => {
                if (!monedasPrioritarias.includes(moneda.code)) {
                    const option = document.createElement('option');
                    option.value = moneda.code;
                    option.textContent = `${moneda.code} - ${moneda.name}`;
                    selectMonedas.appendChild(option);
                }
            });
        }
    } catch (error) {
        console.error('Error cargando monedas de AppNexus:', error);
        // Fallback monedas básicas
        const selectMonedas = document.getElementById('pedimento-moneda');
        if (selectMonedas) {
            selectMonedas.innerHTML = `
                <option value="MXN" selected>MXN - Mexican Peso</option>
                <option value="USD">USD - US Dollar</option>
                <option value="EUR">EUR - Euro</option>
                <option value="CAD">CAD - Canadian Dollar</option>
                <option value="GBP">GBP - British Pound</option>
            `;
        }
    }
}

function getMonedaName(codigo) {
    const nombres = {
        'USD': 'US Dollar',
        'MXN': 'Mexican Peso',
        'EUR': 'Euro',
        'CAD': 'Canadian Dollar',
        'GBP': 'British Pound',
        'JPY': 'Japanese Yen',
        'CNY': 'Chinese Yuan'
    };
    return nombres[codigo] || codigo;
}

async function togglePedimentosClave(clave) {
    const filaExpandible = document.getElementById(`pedimentos-${clave}`);
    const icono = document.getElementById(`icon-${clave}`);
    const texto = document.getElementById(`text-${clave}`);
    
    if (filaExpandible && filaExpandible.classList.contains('hidden')) {
        // Mostrar y cargar pedimentos
        filaExpandible.classList.remove('hidden');
        if (icono) icono.textContent = '👁️';
        if (texto) texto.textContent = 'Ocultar Pedimentos';
        
        await cargarPedimentosDeClave(clave);
    } else if (filaExpandible) {
        // Ocultar
        filaExpandible.classList.add('hidden');
        if (icono) icono.textContent = '👁️';
        if (texto) texto.textContent = 'Ver Pedimentos';
    }
}

async function cargarPedimentosDeClave(clave) {
    const loading = document.getElementById(`loading-${clave}`);
    const lista = document.getElementById(`pedimentos-lista-${clave}`);
    
    if (loading) loading.classList.remove('hidden');
    if (lista) lista.classList.add('hidden');
    
    try {
        const response = await fetch(`/logistica/pedimentos/clave/${clave}`);
        const pedimentos = await response.json();
        
        let html = `
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">No. Pedimento</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Cliente</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Ejecutivo</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha Embarque</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Estado</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Fecha Pago</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Monto</th>
                            <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 uppercase">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
        `;
        
        pedimentos.forEach(pedimento => {
            const estadoClass = pedimento.estado_pago === 'pagado' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800';
            const estadoTexto = pedimento.estado_pago === 'pagado' ? '✅ Pagado' : '⏳ Por Pagar';
            const monto = pedimento.monto ? `${pedimento.moneda || 'MXN'} $${parseFloat(pedimento.monto).toLocaleString()}` : '-';
            
            // Formatear fecha de embarque de forma legible
            let fechaEmbarque = '-';
            if (pedimento.fecha_embarque) {
                try {
                    const fecha = new Date(pedimento.fecha_embarque);
                    if (!isNaN(fecha.getTime())) {
                        fechaEmbarque = fecha.toLocaleDateString('es-MX', {
                            year: 'numeric',
                            month: '2-digit',
                            day: '2-digit'
                        });
                    }
                } catch (e) {
                    fechaEmbarque = pedimento.fecha_embarque;
                }
            }
            
            // Formatear fecha de pago
            let fechaPago = '-';
            if (pedimento.fecha_pago) {
                try {
                    const fecha = new Date(pedimento.fecha_pago);
                    if (!isNaN(fecha.getTime())) {
                        fechaPago = fecha.toLocaleDateString('es-MX', {
                            year: 'numeric',
                            month: '2-digit',
                            day: '2-digit'
                        });
                    }
                } catch (e) {
                    fechaPago = pedimento.fecha_pago;
                }
            }
            
            html += `
                <tr class="hover:bg-gray-50">
                    <td class="px-4 py-2 text-sm font-medium text-blue-600">${pedimento.no_pedimento}</td>
                    <td class="px-4 py-2 text-sm text-gray-900">${pedimento.cliente || '-'}</td>
                    <td class="px-4 py-2 text-sm text-gray-900">${pedimento.ejecutivo || '-'}</td>
                    <td class="px-4 py-2 text-sm text-gray-900">${fechaEmbarque}</td>
                    <td class="px-4 py-2">
                        <span class="px-2 py-1 text-xs font-semibold rounded-full ${estadoClass}">
                            ${estadoTexto}
                        </span>
                    </td>
                    <td class="px-4 py-2 text-sm text-gray-900">${fechaPago}</td>
                    <td class="px-4 py-2 text-sm text-gray-900">${monto}</td>
                    <td class="px-4 py-2">
                        <button onclick="editarPedimentoIndividual('${pedimento.no_pedimento}', '${pedimento.clave}', ${pedimento.operacion_id}, '${pedimento.estado_pago}', '${pedimento.fecha_pago || ''}', '${pedimento.monto || ''}', '${pedimento.observaciones || ''}', '${pedimento.cliente || ''}', '${pedimento.moneda || 'MXN'}')" 
                                class="text-blue-600 hover:text-blue-800 text-sm font-medium">
                            ✏️ Editar Pago
                        </button>
                    </td>
                </tr>
            `;
        });
        
        html += `
                    </tbody>
                </table>
            </div>
        `;
        
        if (lista) lista.innerHTML = html;
        if (loading) loading.classList.add('hidden');
        if (lista) lista.classList.remove('hidden');
        
    } catch (error) {
        console.error('Error cargando pedimentos:', error);
        if (lista) {
            lista.innerHTML = '<div class="text-center text-red-600 py-4">❌ Error al cargar pedimentos</div>';
        }
        if (loading) loading.classList.add('hidden');
        if (lista) lista.classList.remove('hidden');
    }
}

// Función para abrir modal de edición de clave de operación
function abrirModalPedimento(clave, datosPedimento) {
    const modal = document.getElementById('modalEditarPedimento');
    if (modal) {
        // Llenar campos del modal
        const claveOperacionHidden = document.getElementById('clave-operacion-hidden');
        const claveOperacionDisplay = document.getElementById('clave-operacion-display');
        const totalPedimentos = document.getElementById('total-pedimentos');
        const estadoPago = document.getElementById('pedimento-estado');
        const monto = document.getElementById('pedimento-monto');
        const moneda = document.getElementById('pedimento-moneda');
        const fechaTentativa = document.getElementById('pedimento-fecha-tentativa');
        const observaciones = document.getElementById('pedimento-observaciones');
        
        if (claveOperacionHidden) claveOperacionHidden.value = clave;
        if (claveOperacionDisplay) claveOperacionDisplay.value = clave;
        if (totalPedimentos) totalPedimentos.value = datosPedimento.total_pedimentos || 0;
        if (estadoPago) estadoPago.value = datosPedimento.estado_pago || 'pendiente';
        if (monto) monto.value = datosPedimento.monto || '';
        if (moneda) moneda.value = datosPedimento.moneda || 'MXN';
        if (fechaTentativa) fechaTentativa.value = datosPedimento.fecha_tentativa_pago || '';
        if (observaciones) observaciones.value = datosPedimento.observaciones_pago || '';
        
        // Cargar monedas si es necesario
        cargarMonedas();
        
        modal.classList.remove('hidden');
    }
}

// Función para cerrar modal
function cerrarModal() {
    const modal = document.getElementById('modalEditarPedimento');
    if (modal) {
        modal.classList.add('hidden');
    }
}

// Función para guardar cambios del pedimento
async function guardarPedimento(event) {
    event.preventDefault();
    
    const formData = new FormData(event.target);
    const data = Object.fromEntries(formData.entries());
    
    try {
        const response = await fetch('/logistica/pedimentos/actualizar', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            },
            body: JSON.stringify(data)
        });
        
        const result = await response.json();
        
        if (result.success) {
            alert('✅ Pedimento actualizado correctamente');
            cerrarModal();
            // Recargar la página para ver cambios
            window.location.reload();
        } else {
            alert('❌ Error: ' + result.message);
        }
    } catch (error) {
        console.error('Error:', error);
        alert('❌ Error al actualizar el pedimento');
    }
}

// Event listeners
function initializeEventListeners() {
    // Event listener para el form de editar
    const formEditar = document.getElementById('formEditar');
    if (formEditar) {
        formEditar.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const pedimentoId = document.getElementById('pedimentoId').value;
            const formData = {
                estado_pago: document.getElementById('estadoPago').value,
                fecha_pago: document.getElementById('fechaPago').value || null,
                monto: document.getElementById('monto').value || null,
                fecha_vencimiento: document.getElementById('fechaVencimiento').value || null,
                observaciones_pago: document.getElementById('observaciones').value || null
            };
            
            fetch(`/logistica/pedimentos/${pedimentoId}/estado-pago`, {
                method: 'PUT',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    cerrarModalEditar();
                    location.reload();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al actualizar el pedimento');
            });
        });
    }
}

// Función para editar pedimentos individuales
function editarPedimentoIndividual(noPedimento, clave, operacionId, estadoPago, fechaPago, monto, observaciones, cliente, moneda) {
    const modal = document.getElementById('modalEditarPedimentoIndividual');
    if (modal) {
        // Llenar campos ocultos
        document.getElementById('pedimento-individual-no').value = noPedimento;
        document.getElementById('pedimento-individual-operacion-id').value = operacionId;
        document.getElementById('pedimento-individual-clave').value = clave;
        
        // Llenar información de display
        document.getElementById('display-pedimento-no').textContent = noPedimento;
        document.getElementById('display-pedimento-clave').textContent = clave || 'No especificado';
        document.getElementById('display-pedimento-cliente').textContent = cliente || 'No especificado';
        
        // Llenar campos editables
        document.getElementById('pedimento-individual-estado').value = estadoPago || 'pendiente';
        document.getElementById('pedimento-individual-fecha-pago').value = fechaPago || '';
        document.getElementById('pedimento-individual-monto').value = monto || '';
        document.getElementById('pedimento-individual-moneda').value = moneda || 'MXN';
        document.getElementById('pedimento-individual-observaciones').value = observaciones || '';
        
        // Cargar monedas adicionales si es necesario
        cargarMonedasIndividuales();
        
        // Mostrar modal
        modal.classList.remove('hidden');
    }
}

// Función para cerrar modal de pedimento individual
function cerrarModalPedimentoIndividual() {
    const modal = document.getElementById('modalEditarPedimentoIndividual');
    if (modal) {
        modal.classList.add('hidden');
    }
}

// Función para cargar monedas en el modal individual
async function cargarMonedasIndividuales() {
    const selectMoneda = document.getElementById('pedimento-individual-moneda');
    if (!selectMoneda) return;
    
    try {
        // Intentar cargar desde API de AppNexus
        const response = await fetch('https://api.appnexus.com/currency');
        const data = await response.json();
        
        if (data.response && data.response.currencies) {
            const monedas = data.response.currencies;
            
            // Limpiar opciones existentes
            selectMoneda.innerHTML = '';
            
            // Monedas prioritarias con iconos
            const monedasPrioritarias = [
                { code: 'MXN', name: 'Peso Mexicano', icon: '💵' },
                { code: 'USD', name: 'Dólar Estadounidense', icon: '💰' },
                { code: 'EUR', name: 'Euro', icon: '💶' },
                { code: 'CAD', name: 'Dólar Canadiense', icon: '🍁' },
                { code: 'GBP', name: 'Libra Esterlina', icon: '💷' }
            ];
            
            // Agregar monedas prioritarias
            monedasPrioritarias.forEach(monedaPrior => {
                const monedaAPI = monedas.find(m => m.code === monedaPrior.code);
                const option = document.createElement('option');
                option.value = monedaPrior.code;
                option.textContent = `${monedaPrior.icon} ${monedaPrior.code} - ${monedaAPI?.name || monedaPrior.name}`;
                if (monedaPrior.code === 'MXN') option.selected = true;
                selectMoneda.appendChild(option);
            });
            
            // Agregar separador si hay más monedas
            const otrasMonedas = monedas.filter(m => !monedasPrioritarias.find(p => p.code === m.code));
            if (otrasMonedas.length > 0) {
                const separator = document.createElement('option');
                separator.disabled = true;
                separator.textContent = '--- Otras Monedas ---';
                selectMoneda.appendChild(separator);
                
                // Agregar otras monedas alfabéticamente
                otrasMonedas.sort((a, b) => a.code.localeCompare(b.code)).forEach(moneda => {
                    const option = document.createElement('option');
                    option.value = moneda.code;
                    option.textContent = `💱 ${moneda.code} - ${moneda.name}`;
                    selectMoneda.appendChild(option);
                });
            }
        }
    } catch (error) {
        console.log('Error cargando desde AppNexus, usando monedas por defecto:', error);
        // Fallback a monedas por defecto
        selectMoneda.innerHTML = `
            <option value="MXN" selected>💵 MXN - Peso Mexicano</option>
            <option value="USD">💰 USD - Dólar Estadounidense</option>
            <option value="EUR">💶 EUR - Euro</option>
            <option value="CAD">🍁 CAD - Dólar Canadiense</option>
            <option value="GBP">💷 GBP - Libra Esterlina</option>
        `;
    }
}

// Función para guardar pedimento individual
function guardarPedimentoIndividual(event) {
    event.preventDefault();
    
    const formData = {
        no_pedimento: document.getElementById('pedimento-individual-no').value,
        operacion_logistica_id: document.getElementById('pedimento-individual-operacion-id').value,
        estado_pago: document.getElementById('pedimento-individual-estado').value,
        fecha_pago: document.getElementById('pedimento-individual-fecha-pago').value,
        monto: document.getElementById('pedimento-individual-monto').value,
        moneda: document.getElementById('pedimento-individual-moneda').value,
        observaciones: document.getElementById('pedimento-individual-observaciones').value
    };
    
    const clave = document.getElementById('pedimento-individual-clave').value;
    
    // Enviar datos al servidor
    fetch('/logistica/pedimentos/actualizar', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify(formData)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            cerrarModalPedimentoIndividual();
            alert('✅ Pedimento actualizado correctamente');
            // Recargar la lista de pedimentos para esa clave
            togglePedimentosClave(clave, true);
        } else {
            alert('❌ Error: ' + (data.message || 'No se pudo actualizar el pedimento'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('❌ Error al actualizar el pedimento individual');
    });
    
    return false;
}