// Variables globales
let tipoReporteSeleccionado = null;

// Inicializar cuando se carga la página
document.addEventListener('DOMContentLoaded', () => {
    cargarClavesPedimento();
    cargarClientes();
    establecerFechasDefecto();
});

// Función para seleccionar tipo de reporte
function seleccionarReporte(tipo) {
    tipoReporteSeleccionado = tipo;

    // Ocultar todos los paneles
    document.getElementById('filtros-matriz').classList.add('hidden');
    document.getElementById('filtros-pedimentos').classList.add('hidden');

    // Mostrar el panel correspondiente
    if (tipo === 'matriz') {
        document.getElementById('filtros-matriz').classList.remove('hidden');
    } else if (tipo === 'pedimentos') {
        document.getElementById('filtros-pedimentos').classList.remove('hidden');
    }

    // Scroll suave al panel de filtros
    setTimeout(() => {
        const panel = document.getElementById(`filtros-${tipo}`);
        if (panel) {
            panel.scrollIntoView({ behavior: 'smooth' });
        }
    }, 100);
}

// Función para cancelar reporte
function cancelarReporte() {
    tipoReporteSeleccionado = null;
    document.getElementById('filtros-matriz').classList.add('hidden');
    document.getElementById('filtros-pedimentos').classList.add('hidden');

    // Scroll al inicio
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Cargar claves de pedimento
async function cargarClavesPedimento() {
    try {
        const response = await fetch('/logistica/pedimentos/claves');
        const claves = await response.json();

        const select = document.getElementById('ped-clave');
        if (select) {
            select.innerHTML = '<option value="">Todas las claves</option>';

            claves.forEach(clave => {
                const option = document.createElement('option');
                option.value = clave.clave;
                option.textContent = `${clave.clave} - ${clave.descripcion || 'Sin descripción'}`;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error cargando claves:', error);
    }
}

// Cargar clientes
async function cargarClientes() {
    try {
        const response = await fetch('/logistica/clientes');
        const clientes = await response.json();

        const select = document.getElementById('matriz-cliente');
        if (select) {
            select.innerHTML = '<option value="">Todos los clientes</option>';

            clientes.forEach(cliente => {
                const option = document.createElement('option');
                option.value = cliente.nombre;
                option.textContent = cliente.nombre;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error cargando clientes:', error);
    }
}

// Generar Excel Matriz de Seguimiento
async function generarExcelMatriz() {
    const form = document.getElementById('form-matriz');
    const formData = new FormData(form);
    const params = new URLSearchParams(formData);

    try {
        mostrarLoading();

        const response = await fetch(`/reportes/matriz/excel?${params.toString()}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;
            a.download = `matriz_seguimiento_${new Date().toISOString().slice(0, 10)}.xlsx`;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            mostrarMensaje('✅ Reporte de Matriz generado exitosamente', 'success');
        } else {
            throw new Error('Error al generar el reporte');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarMensaje('❌ Error al generar el reporte de matriz', 'error');
    } finally {
        ocultarLoading();
    }
}

// Generar Excel Pedimentos
async function generarExcelPedimentos() {
    const form = document.getElementById('form-pedimentos');
    const formData = new FormData(form);

    // Agregar checkboxes manualmente si están marcados
    formData.set('incluir_tiempos', document.getElementById('ped-incluir-tiempos').checked ? '1' : '0');
    formData.set('agrupar_cliente', document.getElementById('ped-agrupar-cliente').checked ? '1' : '0');

    const params = new URLSearchParams(formData);

    try {
        mostrarLoading();

        const response = await fetch(`/reportes/pedimentos/excel?${params.toString()}`, {
            method: 'GET',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        if (response.ok) {
            const blob = await response.blob();
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.style.display = 'none';
            a.href = url;

            // Nombre dinámico basado en filtros
            let nombreArchivo = 'reporte_pedimentos_';
            const estadoPago = document.getElementById('ped-estado-pago').value;
            const tipoOperacion = document.getElementById('ped-tipo-operacion').value;

            if (estadoPago) {
                nombreArchivo += `${estadoPago}_`;
            }
            if (tipoOperacion) {
                nombreArchivo += `${tipoOperacion}_`;
            }

            nombreArchivo += `${new Date().toISOString().slice(0, 10)}.xlsx`;

            a.download = nombreArchivo;
            document.body.appendChild(a);
            a.click();
            window.URL.revokeObjectURL(url);
            document.body.removeChild(a);

            mostrarMensaje('✅ Reporte de Pedimentos generado exitosamente', 'success');
        } else {
            throw new Error('Error al generar el reporte');
        }
    } catch (error) {
        console.error('Error:', error);
        mostrarMensaje('❌ Error al generar el reporte de pedimentos', 'error');
    } finally {
        ocultarLoading();
    }
}

// Mostrar loading
function mostrarLoading() {
    const loading = document.getElementById('loading-reportes');
    if (loading) {
        loading.classList.remove('hidden');
    }
}

// Ocultar loading
function ocultarLoading() {
    const loading = document.getElementById('loading-reportes');
    if (loading) {
        loading.classList.add('hidden');
    }
}

// Mostrar mensaje
function mostrarMensaje(mensaje, tipo = 'info') {
    const notification = document.createElement('div');
    notification.className = 'fixed top-4 right-4 p-4 rounded-lg shadow-lg z-50 transform transition-all duration-300 translate-x-full';

    if (tipo === 'success') {
        notification.className += ' bg-green-500 text-white';
    } else if (tipo === 'error') {
        notification.className += ' bg-red-500 text-white';
    } else {
        notification.className += ' bg-blue-500 text-white';
    }

    notification.textContent = mensaje;
    document.body.appendChild(notification);

    setTimeout(() => {
        notification.classList.remove('translate-x-full');
    }, 100);

    setTimeout(() => {
        notification.classList.add('translate-x-full');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 3000);
}

// Establecer fechas por defecto
function establecerFechasDefecto() {
    const hoy = new Date();
    const hace30dias = new Date();
    hace30dias.setDate(hoy.getDate() - 30);

    if (document.getElementById('matriz-fecha-inicio')) {
        document.getElementById('matriz-fecha-inicio').value = hace30dias.toISOString().slice(0, 10);
        document.getElementById('matriz-fecha-fin').value = hoy.toISOString().slice(0, 10);
    }

    if (document.getElementById('ped-fecha-pago-inicio')) {
        document.getElementById('ped-fecha-pago-inicio').value = hace30dias.toISOString().slice(0, 10);
        document.getElementById('ped-fecha-pago-fin').value = hoy.toISOString().slice(0, 10);
    }
}
