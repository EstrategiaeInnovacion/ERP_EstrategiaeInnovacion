/**
 * Gestión de Correos CC para Logística
 * Funciones JavaScript para gestionar correos CC de forma interactiva
 */

// Configuración global
const CorreosCCManager = {
    baseUrl: '/logistica/correos-cc',
    
    /**
     * Inicializar el manager
     */
    init() {
        this.setupEventListeners();
        this.setupCSRFToken();
    },

    /**
     * Configurar token CSRF
     */
    setupCSRFToken() {
        const token = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
        if (token) {
            axios.defaults.headers.common['X-CSRF-TOKEN'] = token;
        }
    },

    /**
     * Configurar event listeners
     */
    setupEventListeners() {
        // Interceptar formularios de toggle activo/inactivo
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-action="toggle-activo"]')) {
                e.preventDefault();
                const button = e.target.closest('[data-action="toggle-activo"]');
                const correoId = button.dataset.correoId;
                this.toggleActivo(correoId, button);
            }
        });

        // Interceptar formularios de eliminación
        document.addEventListener('click', (e) => {
            if (e.target.closest('[data-action="eliminar"]')) {
                e.preventDefault();
                const button = e.target.closest('[data-action="eliminar"]');
                const correoId = button.dataset.correoId;
                const nombre = button.dataset.nombre;
                this.confirmarEliminar(correoId, nombre);
            }
        });
    },

    /**
     * Toggle estado activo/inactivo
     */
    async toggleActivo(correoId, buttonElement) {
        try {
            this.showLoading(buttonElement, true);
            
            const response = await axios.patch(`${this.baseUrl}/${correoId}/toggle-activo`, {}, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });
            
            if (response.data.success) {
                this.updateToggleButton(buttonElement, response.data.correo);
                this.updateStatusBadge(correoId, response.data.correo.activo);
                this.showNotification(response.data.message, 'success');
            } else {
                this.showNotification('Error al cambiar el estado', 'error');
            }
        } catch (error) {
            console.error('Error al toggle activo:', error);
            console.error('Status:', error.response?.status);
            this.showNotification(`Error: ${error.response?.status || 'Conexión'} - ${error.response?.data?.message || error.message}`, 'error');
        } finally {
            this.showLoading(buttonElement, false);
        }
    },

    /**
     * Confirmar y eliminar correo CC
     */
    async confirmarEliminar(correoId, nombre) {
        const confirmed = await this.showConfirmDialog(
            'Confirmar Eliminación',
            `¿Estás seguro de que deseas eliminar el correo CC de "${nombre}"?`,
            'Esta acción no se puede deshacer.'
        );

        if (confirmed) {
            this.eliminarCorreo(correoId);
        }
    },

    /**
     * Eliminar correo CC
     */
    async eliminarCorreo(correoId) {
        try {
            console.log('Eliminando correo ID:', correoId);
            console.log('URL:', `${this.baseUrl}/${correoId}`);
            
            const response = await axios.delete(`${this.baseUrl}/${correoId}`, {
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                }
            });
            
            console.log('Response:', response);
            
            if (response.data.success) {
                this.removeRowFromTable(correoId);
                this.showNotification(response.data.message, 'success');
                this.updateEmptyState();
            } else {
                this.showNotification('Error al eliminar el correo', 'error');
            }
        } catch (error) {
            console.error('Error completo:', error);
            console.error('Status:', error.response?.status);
            console.error('Data:', error.response?.data);
            this.showNotification(`Error: ${error.response?.status || 'Conexión'} - ${error.response?.data?.message || error.message}`, 'error');
        }
    },

    /**
     * Actualizar botón de toggle
     */
    updateToggleButton(buttonElement, correo) {
        const icon = buttonElement.querySelector('i');
        const isActive = correo.activo;
        
        icon.className = `fas ${isActive ? 'fa-toggle-on' : 'fa-toggle-off'}`;
        buttonElement.title = isActive ? 'Desactivar' : 'Activar';
        buttonElement.className = buttonElement.className.replace(
            /(text-indigo-600|text-gray-600)/,
            isActive ? 'text-indigo-600' : 'text-gray-600'
        );
    },

    /**
     * Actualizar badge de estado
     */
    updateStatusBadge(correoId, isActive) {
        const row = document.querySelector(`[data-correo-id="${correoId}"]`);
        if (row) {
            const badge = row.querySelector('.status-badge');
            if (badge) {
                badge.className = `inline-flex px-2 py-1 text-xs font-semibold rounded-full status-badge ${
                    isActive ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'
                }`;
                badge.textContent = isActive ? 'Activo' : 'Inactivo';
            }
        }
    },

    /**
     * Remover fila de la tabla
     */
    removeRowFromTable(correoId) {
        const row = document.querySelector(`[data-correo-id="${correoId}"]`);
        if (row) {
            row.style.transition = 'opacity 0.3s ease';
            row.style.opacity = '0';
            setTimeout(() => {
                row.remove();
            }, 300);
        }
    },

    /**
     * Actualizar estado vacío si no hay registros
     */
    updateEmptyState() {
        const tbody = document.querySelector('table tbody');
        const rows = tbody.querySelectorAll('tr[data-correo-id]');
        
        if (rows.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="py-8 px-6 text-center text-gray-500">
                        <i class="fas fa-inbox text-4xl mb-4"></i>
                        <p class="text-lg">No hay correos CC configurados</p>
                        <p class="text-sm">Agrega el primer correo CC para comenzar</p>
                    </td>
                </tr>
            `;
        }
    },

    /**
     * Mostrar estado de carga
     */
    showLoading(element, isLoading) {
        if (isLoading) {
            element.disabled = true;
            element.style.opacity = '0.6';
            element.style.cursor = 'not-allowed';
        } else {
            element.disabled = false;
            element.style.opacity = '1';
            element.style.cursor = 'pointer';
        }
    },

    /**
     * Mostrar dialog de confirmación
     */
    showConfirmDialog(title, message, detail = null) {
        return new Promise((resolve) => {
            // Crear modal de confirmación dinámico
            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 z-50 overflow-y-auto';
            modal.innerHTML = `
                <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
                    <div class="fixed inset-0 transition-opacity" aria-hidden="true">
                        <div class="absolute inset-0 bg-gray-500 opacity-75"></div>
                    </div>
                    <div class="inline-block align-bottom bg-white rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                        <div class="bg-white px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                            <div class="sm:flex sm:items-start">
                                <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                                    <i class="fas fa-exclamation-triangle text-red-600"></i>
                                </div>
                                <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                                    <h3 class="text-lg leading-6 font-medium text-gray-900">${title}</h3>
                                    <div class="mt-2">
                                        <p class="text-sm text-gray-500">${message}</p>
                                        ${detail ? `<p class="text-xs text-gray-400 mt-1">${detail}</p>` : ''}
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="bg-gray-50 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                            <button type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 focus:outline-none sm:ml-3 sm:w-auto sm:text-sm confirm-btn">
                                Eliminar
                            </button>
                            <button type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm cancel-btn">
                                Cancelar
                            </button>
                        </div>
                    </div>
                </div>
            `;

            document.body.appendChild(modal);

            // Event listeners para los botones
            const confirmBtn = modal.querySelector('.confirm-btn');
            const cancelBtn = modal.querySelector('.cancel-btn');

            confirmBtn.addEventListener('click', () => {
                document.body.removeChild(modal);
                resolve(true);
            });

            cancelBtn.addEventListener('click', () => {
                document.body.removeChild(modal);
                resolve(false);
            });

            // Cerrar con ESC
            const escapeHandler = (e) => {
                if (e.key === 'Escape') {
                    document.body.removeChild(modal);
                    document.removeEventListener('keydown', escapeHandler);
                    resolve(false);
                }
            };
            document.addEventListener('keydown', escapeHandler);
        });
    },

    /**
     * Mostrar notificación
     */
    showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.className = `fixed top-4 right-4 z-50 max-w-xs shadow-lg rounded-lg pointer-events-auto transform transition-all duration-300 translate-x-full`;
        
        const colors = {
            success: 'bg-green-500 text-white',
            error: 'bg-red-500 text-white',
            info: 'bg-blue-500 text-white',
            warning: 'bg-yellow-500 text-black'
        };
        
        notification.className += ` ${colors[type] || colors.success}`;
        
        notification.innerHTML = `
            <div class="px-3 py-2">
                <div class="flex items-center text-sm">
                    <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle mr-2 text-xs"></i>
                    <p class="font-medium flex-1">${message}</p>
                    <button class="ml-2 text-current hover:text-opacity-75 text-xs" onclick="this.parentElement.parentElement.parentElement.remove()">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            </div>
        `;

        document.body.appendChild(notification);

        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 10);

        // Auto remove after 4 seconds
        setTimeout(() => {
            if (notification.parentElement) {
                notification.classList.add('translate-x-full');
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, 300);
            }
        }, 4000);
    }
};

// Inicializar cuando el DOM esté listo
document.addEventListener('DOMContentLoaded', () => {
    CorreosCCManager.init();
});

// Exportar para uso global
window.CorreosCCManager = CorreosCCManager;