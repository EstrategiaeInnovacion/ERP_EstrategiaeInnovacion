/**
 * Catálogos Maestros - JavaScript
 * Manejo de tabs, modales de edición y eliminación
 */

// ========================================
// SISTEMA DE MODALES REUTILIZABLES
// ========================================

/**
 * Muestra un modal de alerta (reemplazo de alert())
 * @param {string} message - Mensaje a mostrar
 * @param {string} type - Tipo: 'success', 'error', 'warning', 'info'
 * @param {string} title - Título opcional
 */
window.mostrarAlerta = function(message, type = 'info', title = '') {
    const modal = document.getElementById('modalAlert');
    const iconContainer = document.getElementById('modalAlertIcon');
    const titleElement = document.getElementById('modalAlertTitle');
    const messageElement = document.getElementById('modalAlertMessage');
    
    // Definir iconos y colores según el tipo
    const types = {
        success: {
            icon: `<svg class="w-12 h-12 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>`,
            title: title || 'Éxito'
        },
        error: {
            icon: `<svg class="w-12 h-12 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>`,
            title: title || 'Error'
        },
        warning: {
            icon: `<svg class="w-12 h-12 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>`,
            title: title || 'Advertencia'
        },
        info: {
            icon: `<svg class="w-12 h-12 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>`,
            title: title || 'Información'
        }
    };
    
    const config = types[type] || types.info;
    iconContainer.innerHTML = config.icon;
    titleElement.textContent = config.title;
    messageElement.textContent = message;
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
};

/**
 * Cierra el modal de alerta
 */
window.cerrarModalAlert = function() {
    const modal = document.getElementById('modalAlert');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
};

/**
 * Muestra un modal de confirmación (reemplazo de confirm())
 * @param {string} message - Mensaje a mostrar
 * @param {function} onConfirm - Callback cuando se confirma
 * @param {string} title - Título opcional
 * @param {string} confirmText - Texto del botón de confirmar
 */
window.mostrarConfirmacion = function(message, onConfirm, title = 'Confirmar acción', confirmText = 'Confirmar') {
    const modal = document.getElementById('modalConfirm');
    const titleElement = document.getElementById('modalConfirmTitle');
    const messageElement = document.getElementById('modalConfirmMessage');
    const confirmBtn = document.getElementById('modalConfirmBtn');
    
    titleElement.textContent = title;
    messageElement.textContent = message;
    confirmBtn.textContent = confirmText;
    
    // Remover listeners anteriores
    const newBtn = confirmBtn.cloneNode(true);
    confirmBtn.parentNode.replaceChild(newBtn, confirmBtn);
    
    // Agregar nuevo listener
    document.getElementById('modalConfirmBtn').addEventListener('click', function() {
        cerrarModalConfirm(true);
        if (typeof onConfirm === 'function') {
            onConfirm();
        }
    });
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
};

/**
 * Cierra el modal de confirmación
 */
window.cerrarModalConfirm = function(confirmed) {
    const modal = document.getElementById('modalConfirm');
    modal.classList.add('hidden');
    modal.classList.remove('flex');
};

// Event listener para cerrar modales con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        cerrarModalAlert();
        cerrarModalConfirm(false);
    }
});

// ========================================
// FIN SISTEMA DE MODALES
// ========================================

// Helper para obtener token CSRF
function getCsrfToken() {
    const token = document.querySelector('meta[name="csrf-token"]');
    if (!token) {
        return null;
    }
    return token.getAttribute('content');
}

// Helper para restaurar el scroll del body de manera segura
function restoreBodyScroll() {
    try {
        document.body.style.overflow = '';
        document.body.style.overflow = 'auto';
        document.body.style.overflowY = 'auto';
        document.body.style.position = '';
        // TambiÃ©n limpiar cualquier clase que pueda estar afectando el scroll
        document.body.classList.remove('modal-open', 'no-scroll');
    } catch (error) {
        // Error silencioso
    }
}

// Helper para mostrar loading en botÃ³n
function showButtonLoading(button, loadingText = 'Procesando...') {
    if (button) {
        button.disabled = true;
        const originalText = button.querySelector('.import-text, .save-text, .update-text');
        const loading = button.querySelector('.loading-text');
        
        if (originalText) originalText.classList.add('hidden');
        if (loading) {
            loading.classList.remove('hidden');
            loading.textContent = loadingText;
        }
    }
}

// Helper para ocultar loading en botÃ³n
function hideButtonLoading(button) {
    if (button) {
        button.disabled = false;
        const originalText = button.querySelector('.import-text, .save-text, .update-text');
        const loading = button.querySelector('.loading-text');
        
        if (originalText) originalText.classList.remove('hidden');
        if (loading) loading.classList.add('hidden');
    }
}

// Helper para actualizar progreso
function updateProgress(percentage, message, type = 'ADUANAS') {
    const progressContainer = document.getElementById(type === 'ADUANAS' ? 'importProgress' : 'importPedimentosProgress');
    const progressBar = document.getElementById(type === 'ADUANAS' ? 'progressBar' : 'progressPedimentosBar');
    const progressText = document.getElementById(type === 'ADUANAS' ? 'progressText' : 'progressPedimentosText');
    
    // TambiÃ©n actualizar modal si existe
    const modalContainer = document.getElementById(`import${type.charAt(0).toUpperCase() + type.slice(1)}ProgressModal`);
    const modalBar = document.getElementById(`progress${type.charAt(0).toUpperCase() + type.slice(1)}BarModal`);
    const modalText = document.getElementById(`progress${type.charAt(0).toUpperCase() + type.slice(1)}TextModal`);

    // Mostrar contenedores si estÃ¡n ocultos
    if (progressContainer) progressContainer.classList.remove('hidden');
    if (modalContainer) modalContainer.classList.remove('hidden');

    // Actualizar barras de progreso
    if (progressBar) progressBar.style.width = `${percentage}%`;
    if (modalBar) modalBar.style.width = `${percentage}%`;
    
    // Actualizar textos
    if (progressText) progressText.textContent = message;
    if (modalText) modalText.textContent = message;
}

// Helper para ocultar todos los progress de un tipo
function hideProgress(type = 'ADUANAS') {
    const progressContainer = document.getElementById(type === 'ADUANAS' ? 'importProgress' : 'importPedimentosProgress');
    const modalContainer = document.getElementById(`import${type.charAt(0).toUpperCase() + type.slice(1)}ProgressModal`);
    
    if (progressContainer) progressContainer.classList.add('hidden');
    if (modalContainer) modalContainer.classList.add('hidden');
}

// FunciÃ³n de emergencia para restaurar scroll automáticamente
function emergencyScrollRestore() {
    // Verificar si hay modales visibles
    const modals = document.querySelectorAll('.modal-overlay:not(.hidden)');
    
    if (modals.length === 0) {
        // No hay modales visibles, restaurar el scroll
        restoreBodyScroll();
    }
}

// Monitorear tecla Escape para cerrar modales y restaurar scroll
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        // Cerrar modal de confirmaciÃ³n si estÃ¡ abierto
        const confirmModal = document.getElementById('confirmModal');
        if (confirmModal && !confirmModal.classList.contains('hidden')) {
            closeConfirmModal();
            return;
        }
        
        // Restaurar scroll como medida de emergencia
        restoreBodyScroll();
    }
});

// Verificar cada 2 segundos si el scroll necesita ser restaurado
setInterval(emergencyScrollRestore, 2000);

// ========================================
// FUNCIONES PARA BÚSQUEDA DE EMPLEADOS
// ========================================

function openSearchEmployeeModal() {
    const modal = document.getElementById('searchEmployeeModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';
        
        setTimeout(() => {
            const modalContent = modal.querySelector('.modal-content');
            if (modalContent) {
                modalContent.style.transform = 'scale(1)';
            }
        }, 10);

        // Limpiar búsqueda anterior
        const searchInput = document.getElementById('employeeSearchInput');
        if (searchInput) {
            searchInput.value = '';
            searchInput.focus();
        }
        
        // Mostrar estado inicial
        showSearchState('initial');
    }
}

function closeSearchEmployeeModal() {
    const modal = document.getElementById('searchEmployeeModal');
    if (modal) {
        modal.classList.remove('show');
        const modalContent = modal.querySelector('.modal-content');
        if (modalContent) {
            modalContent.style.transform = 'scale(0.95)';
        }
        
        setTimeout(() => {
            modal.classList.add('hidden');
            restoreBodyScroll();
        }, 300);
    }
}

function showSearchState(state) {
    const initialState = document.getElementById('searchInitialState');
    const loading = document.getElementById('searchLoading');
    const results = document.getElementById('searchResults');

    if (initialState) initialState.classList.add('hidden');
    if (loading) loading.classList.add('hidden');
    if (results) results.classList.add('hidden');

    switch(state) {
        case 'initial':
            if (initialState) initialState.classList.remove('hidden');
            break;
        case 'loading':
            if (loading) loading.classList.remove('hidden');
            break;
        case 'results':
            if (results) results.classList.remove('hidden');
            break;
    }
}

async function searchEmployees(query) {
    if (query.length < 2) {
        showSearchState('initial');
        return;
    }

    showSearchState('loading');

    try {
        const response = await fetch(`/logistica/empleados/search?search=${encodeURIComponent(query)}`, {
            method: 'GET',
            headers: getAuthHeaders()
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            displaySearchResults(data.data);
        } else {
            showAlert(data.message || 'Error al buscar empleados', 'error');
            showSearchState('initial');
        }

    } catch (error) {
        showAlert('Error de conexiÃ³n al buscar empleados', 'error');
        showSearchState('initial');
    }
}

function displaySearchResults(empleados) {
    const resultsList = document.getElementById('searchResultsList');
    
    if (!resultsList) return;

    if (empleados.length === 0) {
        resultsList.innerHTML = `
            <div class="p-8 text-center">
                <svg class="w-12 h-12 mx-auto text-gray-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <p class="text-gray-500">No se encontraron empleados</p>
            </div>
        `;
    } else {
        resultsList.innerHTML = empleados.map(empleado => `
            <div class="p-4 border-b border-gray-200 hover:bg-gray-50 cursor-pointer" onclick="selectEmployee(${empleado.id})">
                <div class="flex justify-between items-center">
                    <div>
                        <h4 class="font-medium text-gray-900">${empleado.nombre}</h4>
                        <p class="text-sm text-gray-600">ID: ${empleado.id_empleado || 'N/A'}</p>
                        <p class="text-sm text-gray-600">Ãrea: ${empleado.area || 'Sin área'}</p>
                        <p class="text-sm text-gray-600">Email: ${empleado.correo || 'Sin email'}</p>
                    </div>
                    <button class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition-colors">
                        Agregar como Ejecutivo
                    </button>
                </div>
            </div>
        `).join('');
    }

    showSearchState('results');
}

async function selectEmployee(empleadoId) {
    try {
        const response = await fetch('/logistica/empleados/add-ejecutivo', {
            method: 'POST',
            headers: {
                ...getAuthHeaders(),
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                empleado_id: empleadoId
            })
        });

        if (!response.ok) {
            throw new Error(`HTTP error! status: ${response.status}`);
        }

        const data = await response.json();

        if (data.success) {
            showAlert(data.message, 'success');
            closeSearchEmployeeModal();
            
            // Recargar la página para mostrar el nuevo ejecutivo
            setTimeout(() => {
                sessionStorage.setItem('activeTab', 'ejecutivos');
                window.location.reload();
            }, 1000);
        } else {
            showAlert(data.message || 'Error al agregar ejecutivo', 'error');
        }

    } catch (error) {
        showAlert('Error de conexiÃ³n al agregar ejecutivo', 'error');
    }
}

// Event listener para la búsqueda en tiempo real
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('employeeSearchInput');
    if (searchInput) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(() => {
                searchEmployees(this.value);
            }, 300);
        });
    }

    // Event listeners para cerrar modal de empleados
    const modal = document.getElementById('searchEmployeeModal');
    if (modal) {
        // Cerrar con clic en el fondo
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeSearchEmployeeModal();
            }
        });

        // Cerrar con tecla Escape
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
                closeSearchEmployeeModal();
            }
        });
    }

    // Verificar existencia de datos al cargar la página
    checkDataExistenceAndUpdateButtons();
    
    // Event listener para el botÃ³n de eliminar todos los clientes
    const deleteAllClientsBtn = document.getElementById('deleteAllClientsBtn');
    if (deleteAllClientsBtn) {
        deleteAllClientsBtn.addEventListener('click', deleteAllClients);
    }
});

// ========================================
// GESTIÃ“N DE VISIBILIDAD DE BOTONES DE IMPORTACIÃ“N
// ========================================

async function checkDataExistenceAndUpdateButtons() {
    try {
        // Verificar ADUANAS
        const ADUANASResponse = await fetch('/logistica/aduanas/check', {
            method: 'GET',
            headers: getAuthHeaders()
        });
        
        let ADUANASExists = false;
        if (ADUANASResponse.ok) {
            const ADUANASData = await ADUANASResponse.json();
            ADUANASExists = ADUANASData.success ? ADUANASData.exists : false;
        }

        // Verificar pedimentos
        const pedimentosResponse = await fetch('/logistica/pedimentos/check', {
            method: 'GET',
            headers: getAuthHeaders()
        });
        
        let pedimentosExists = false;
        if (pedimentosResponse.ok) {
            const pedimentosData = await pedimentosResponse.json();
            pedimentosExists = pedimentosData.success ? pedimentosData.exists : false;
        }

        // Actualizar visibilidad de botones
        updateImportButtonsVisibility(ADUANASExists, pedimentosExists);

    } catch (error) {
        // En caso de error, mostrar los botones por defecto
        updateImportButtonsVisibility(false, false);
    }
}

function updateImportButtonsVisibility(ADUANASExist, pedimentosExist) {
    // Botones de importaciÃ³n en la parte superior de cada pestaÃ±a
    const importADUANASBtn = document.getElementById('importADUANASBtn');
    const importPedimentosBtn = document.getElementById('importPedimentosBtn');

    // Solo actualizar ADUANAS si no es null
    if (importADUANASBtn && ADUANASExist !== null) {
        if (ADUANASExist) {
            importADUANASBtn.style.display = 'none';
        } else {
            importADUANASBtn.style.display = 'inline-flex';
        }
    }

    // Solo actualizar pedimentos si no es null
    if (importPedimentosBtn && pedimentosExist !== null) {
        if (pedimentosExist) {
            importPedimentosBtn.style.display = 'none';
        } else {
            importPedimentosBtn.style.display = 'inline-flex';
        }
    }
}

// FunciÃ³n para mostrar botones de importaciÃ³n despuÃ©s de limpiar datos
function showImportButtons() {
    const importADUANASBtn = document.getElementById('importADUANASBtn');
    const importPedimentosBtn = document.getElementById('importPedimentosBtn');

    if (importADUANASBtn) {
        importADUANASBtn.style.display = 'inline-flex';
    }

    if (importPedimentosBtn) {
        importPedimentosBtn.style.display = 'inline-flex';
    }
}

// Helper para crear headers con CSRF
function getAuthHeaders() {
    const token = getCsrfToken();
    return token ? {
        'X-CSRF-TOKEN': token,
        'Accept': 'application/json'
    } : {
        'Accept': 'application/json'
    };
}

class CatalogosMaestros {
    constructor() {
        this.activeTab = 'clientes';
        this.currentEditId = null;
        this.currentEditType = null;
        this.init();
    }

    init() {
        this.initTabs();
        this.initModals();
        this.initEventListeners();
        this.initExecutiveAssignment();
        
        // Determinar qué© tab mostrar basado en URL o sessionStorage
        const urlParams = new URLSearchParams(window.location.search);
        const urlTab = urlParams.get('tab');
        const savedTab = sessionStorage.getItem('activeTab');
        
        // Prioridad: URL > sessionStorage > default
        const activeTab = urlTab || savedTab || 'clientes';
        
        // Si la URL tiene parámetros extraÃ±os, limpiarla
        if (window.location.search && (!urlTab || urlParams.toString() !== `tab=${activeTab}`)) {
            this.updateURL(activeTab);
        }
        
        this.showTabWithoutURLUpdate(activeTab);
        
        // Guardar el tab activo en sessionStorage para futuras navegaciones
        sessionStorage.setItem('activeTab', activeTab);

        // Escuchar cambios en la URL (navegaciÃ³n con botones del navegador)
        window.addEventListener('popstate', (event) => {
            const urlParams = new URLSearchParams(window.location.search);
            const newTab = urlParams.get('tab') || 'clientes';
            
            // Solo cambiar si el tab es diferente al actual para evitar bucles
            if (newTab !== this.activeTab) {
                this.showTabWithoutURLUpdate(newTab);
            }
        });
    }

    initTabs() {
        const tabButtons = document.querySelectorAll('.tab-button');
        tabButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                const tabId = button.getAttribute('data-tab');
                this.showTab(tabId);
            });
        });
    }

    showTab(tabId) {
        // Ocultar todos los contenidos de tabs
        const tabContents = document.querySelectorAll('.tab-content');
        tabContents.forEach(content => {
            content.classList.add('hidden');
        });

        // Remover clase active de todos los botones
        const tabButtons = document.querySelectorAll('.tab-button');
        tabButtons.forEach(button => {
            button.classList.remove('active');
        });

        // Mostrar el tab seleccionado
        const activeContent = document.getElementById(`${tabId}-content`);
        const activeButton = document.querySelector(`[data-tab="${tabId}"]`);

        if (activeContent && activeButton) {
            activeContent.classList.remove('hidden');
            activeButton.classList.add('active');
            this.activeTab = tabId;
            
            // Guardar el tab activo en sessionStorage
            sessionStorage.setItem('activeTab', tabId);
            
            // Actualizar la URL limpiamente solo con el tab
            this.updateURL(tabId);
        }
    }

    updateURL(tabId) {
        // Crear una URL limpia solo con el parámetro tab
        const baseUrl = window.location.pathname;
        const newUrl = `${baseUrl}?tab=${tabId}`;
        
        // Actualizar la URL sin recargar la página
        window.history.pushState({ tab: tabId }, '', newUrl);
    }

    showTabWithoutURLUpdate(tabId) {
        // Ocultar todos los contenidos de tabs
        const tabContents = document.querySelectorAll('.tab-content');
        tabContents.forEach(content => {
            content.classList.add('hidden');
        });

        // Remover clase active de todos los botones
        const tabButtons = document.querySelectorAll('.tab-button');
        tabButtons.forEach(button => {
            button.classList.remove('active');
        });

        // Mostrar el tab seleccionado
        const activeContent = document.getElementById(`${tabId}-content`);
        const activeButton = document.querySelector(`[data-tab="${tabId}"]`);

        if (activeContent && activeButton) {
            activeContent.classList.remove('hidden');
            activeButton.classList.add('active');
            this.activeTab = tabId;
            
            // Guardar el tab activo en sessionStorage
            sessionStorage.setItem('activeTab', tabId);
            // NO actualizar URL aqué­ para evitar bucles
        }
    }

    initModals() {
        // Modal de ediciÃ³n
        const editModal = document.getElementById('editModal');
        const deleteModal = document.getElementById('deleteModal');
        const assignModal = document.getElementById('assignExecutiveModal');

        // Cerrar modales al hacer clic en overlay
        if (editModal) {
            editModal.addEventListener('click', (e) => {
                if (e.target === editModal) {
                    this.closeEditModal();
                }
            });
        }

        if (deleteModal) {
            deleteModal.addEventListener('click', (e) => {
                if (e.target === deleteModal) {
                    this.closeDeleteModal();
                }
            });
        }

        if (assignModal) {
            assignModal.addEventListener('click', (e) => {
                if (e.target === assignModal) {
                    this.closeAssignModal();
                }
            });
        }

        // Cerrar modales con ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                this.closeEditModal();
                this.closeDeleteModal();
                this.closeAssignModal();
            }
        });
    }

    initEventListeners() {
        // Botones de agregar
        const addButtons = document.querySelectorAll('.btn-add');
        addButtons.forEach(button => {
            button.addEventListener('click', (e) => {
                const type = button.getAttribute('data-type');
                this.openAddModal(type);
            });
        });

        // Botones de editar
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-edit')) {
                const id = e.target.getAttribute('data-id');
                const type = e.target.getAttribute('data-type');
                const name = e.target.getAttribute('data-name');
                const ejecutivoId = e.target.getAttribute('data-ejecutivo-id');
                const periodicidad = e.target.getAttribute('data-periodicidad');
                const correos = e.target.getAttribute('data-correos');
                this.openEditModal(id, type, name, ejecutivoId, periodicidad, correos);
            }
        });

        // Botones de eliminar
        document.addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-delete')) {
                const id = e.target.getAttribute('data-id');
                const type = e.target.getAttribute('data-type');
                const name = e.target.getAttribute('data-name');
                this.openDeleteModal(id, type, name);
            }
        });

        // Formulario de ediciÃ³n
        const editForm = document.getElementById('editForm');
        if (editForm) {
            editForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitEdit();
            });
        }

        // Confirmación de eliminación
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        if (confirmDeleteBtn) {
            confirmDeleteBtn.addEventListener('click', () => {
                this.confirmDelete();
            });
        }

        // Botones de cancelar
        const cancelButtons = document.querySelectorAll('.btn-cancel');
        cancelButtons.forEach(button => {
            button.addEventListener('click', () => {
                this.closeEditModal();
                this.closeDeleteModal();
                this.closeAssignModal();
            });
        });

        // Formulario de asignaciÃ³n de ejecutivo
        const assignForm = document.getElementById('assignExecutiveForm');
        if (assignForm) {
            assignForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.submitExecutiveAssignment();
            });
        }
    }

    initExecutiveAssignment() {
        // BotÃ³n de asignar ejecutivo
        const assignBtn = document.getElementById('assignExecutiveBtn');
        if (assignBtn) {
            assignBtn.addEventListener('click', () => {
                this.openAssignModal();
            });
        }

        // Checkbox de seleccionar todos
        const selectAllCheckbox = document.getElementById('selectAllClientes');
        if (selectAllCheckbox) {
            selectAllCheckbox.addEventListener('change', (e) => {
                this.selectAllClients(e.target.checked);
            });
        }

        // Checkboxes individuales
        document.addEventListener('change', (e) => {
            if (e.target.classList.contains('cliente-checkbox')) {
                this.updateAssignButtonState();
            }
        });
    }

    openAddModal(type) {
        this.currentEditId = null;
        this.currentEditType = type;

        const modal = document.getElementById('editModal');
        const title = document.getElementById('modalTitle');
        const nameInput = document.getElementById('editName');
        const clienteFieldsGroup = document.getElementById('clienteFieldsGroup');
        const ejecutivoSelect = document.getElementById('editEjecutivo');
        const periodicidadSelect = document.getElementById('editPeriodicidad');
        const correosTextarea = document.getElementById('editCorreos');

        title.textContent = `Agregar ${this.getTypeLabel(type)}`;
        nameInput.value = '';
        nameInput.focus();

        // Mostrar campos adicionales solo para clientes
        if (type === 'clientes' && clienteFieldsGroup) {
            clienteFieldsGroup.classList.remove('hidden');
            if (ejecutivoSelect) ejecutivoSelect.value = '';
            if (periodicidadSelect) {
                periodicidadSelect.value = 'Diario';
                togglePeriodicidadOptions();
            }
            if (correosTextarea) correosTextarea.value = '';
        } else if (clienteFieldsGroup) {
            clienteFieldsGroup.classList.add('hidden');
        }

        this.showModal(modal);
    }

    openEditModal(id, type, name, ejecutivoId = null, periodicidad = null, correos = null) {
        this.currentEditId = id;
        this.currentEditType = type;

        const modal = document.getElementById('editModal');
        const title = document.getElementById('modalTitle');
        const nameInput = document.getElementById('editName');
        const clienteFieldsGroup = document.getElementById('clienteFieldsGroup');
        const ejecutivoSelect = document.getElementById('editEjecutivo');
        const periodicidadSelect = document.getElementById('editPeriodicidad');
        const correosTextarea = document.getElementById('editCorreos');

        title.textContent = `Editar ${this.getTypeLabel(type)}`;
        nameInput.value = name;
        nameInput.focus();
        nameInput.select();

        // Mostrar campos adicionales solo para clientes
        if (type === 'clientes' && clienteFieldsGroup) {
            clienteFieldsGroup.classList.remove('hidden');
            
            if (ejecutivoSelect) {
                ejecutivoSelect.value = ejecutivoId || '';
            }
            
            if (periodicidadSelect) {
                setPeriodicidadValues(periodicidad || 'Diario');
            }
            
            if (correosTextarea) {
                // Convertir array de correos a string separado por comas
                if (Array.isArray(correos)) {
                    correosTextarea.value = correos.join(', ');
                } else {
                    correosTextarea.value = correos || '';
                }
            }
        } else if (clienteFieldsGroup) {
            clienteFieldsGroup.classList.add('hidden');
        }

        this.showModal(modal);
    }

    openDeleteModal(id, type, name) {
        this.currentEditId = id;
        this.currentEditType = type;

        const modal = document.getElementById('deleteModal');
        const message = document.getElementById('deleteMessage');

        const typeLabel = this.getTypeLabel(type) || type || 'elemento';
        message.innerHTML = `¿Estás seguro de que deseas eliminar ${typeLabel.toLowerCase()} <strong>"${name}"</strong>?<br><span class="text-sm text-gray-500">Esta acción no se puede deshacer.</span>`;

        this.showModal(modal);
    }

    closeEditModal() {
        const modal = document.getElementById('editModal');
        this.hideModal(modal);
        this.resetForm();
    }

    closeDeleteModal() {
        const modal = document.getElementById('deleteModal');
        this.hideModal(modal);
    }

    showModal(modal) {
        if (modal) {
            modal.classList.remove('hidden');
            modal.classList.add('show');
            document.body.style.overflow = 'hidden';

            setTimeout(() => {
                modal.querySelector('.modal-content').style.transform = 'scale(1)';
            }, 10);
        }
    }

    hideModal(modal) {
        if (modal) {
            modal.classList.remove('show');
            modal.querySelector('.modal-content').style.transform = 'scale(0.95)';

            setTimeout(() => {
                modal.classList.add('hidden');
                document.body.style.overflow = 'auto';
            }, 300);
        }
    }

    resetForm() {
        const form = document.getElementById('editForm');
        if (form) {
            form.reset();
        }
        this.currentEditId = null;
        this.currentEditType = null;
    }

    async submitEdit() {
        const nameInput = document.getElementById('editName');
        const name = nameInput.value.trim();

        if (!name) {
            this.showAlert('Por favor, ingresa un nombre válido.', 'error');
            return;
        }

        const isEditing = this.currentEditId !== null;
        const url = this.getApiUrl(this.currentEditType, isEditing);
        const method = isEditing ? 'PUT' : 'POST';

        const formData = new FormData();
        formData.append(this.getFieldName(this.currentEditType), name);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

        // Agregar campos adicionales si es un cliente
        if (this.currentEditType === 'clientes') {
            const ejecutivoSelect = document.getElementById('editEjecutivo');
            const periodicidadSelect = document.getElementById('editPeriodicidad');
            const correosTextarea = document.getElementById('editCorreos');
            
            if (ejecutivoSelect) {
                formData.append('ejecutivo_asignado_id', ejecutivoSelect.value || '');
            }
            
            if (periodicidadSelect) {
                const periodicidadCompleta = buildPeriodicidadString();
                formData.append('periodicidad_reporte', periodicidadCompleta);
            }
            
            if (correosTextarea && correosTextarea.value.trim()) {
                // Convertir string de correos separados por comas a array
                const correosArray = correosTextarea.value
                    .split(',')
                    .map(email => email.trim())
                    .filter(email => email.length > 0);
                
                // Enviar como JSON string
                formData.append('correos', JSON.stringify(correosArray));
            }
        }

        if (isEditing) {
            formData.append('_method', 'PUT');
        }

        try {
            this.showLoading(true);

            const response = await fetch(url, {
                method: 'POST', // Siempre POST debido a Laravel form method spoofing
                headers: getAuthHeaders(),
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showAlert(data.message, 'success');
                this.closeEditModal();
                this.refreshTable(this.currentEditType);
            } else {
                this.showAlert(data.message || 'Error al procesar la solicitud.', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('Error de conexiÃ³n. Por favor, intenta nuevamente.', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    async confirmDelete() {
        const url = this.getApiUrl(this.currentEditType, true);

        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
        formData.append('_method', 'DELETE');

        try {
            this.showLoading(true);

            const response = await fetch(url, {
                method: 'POST',
                headers: getAuthHeaders(),
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showAlert(data.message, 'success');
                this.closeDeleteModal();
                this.refreshTable(this.currentEditType);
            } else {
                this.showAlert(data.message || 'Error al eliminar el registro.', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('Error de conexiÃ³n. Por favor, intenta nuevamente.', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    getApiUrl(type, isEditing) {
        const baseUrls = {
            'clientes': '/logistica/clientes',
            'agentes': '/logistica/agentes',
            'transportes': '/logistica/transportes',
            'ejecutivos': '/logistica/ejecutivos'
        };

        let url = baseUrls[type];
        if (isEditing && this.currentEditId) {
            url += `/${this.currentEditId}`;
        }

        return url;
    }

    getFieldName(type) {
        const fieldNames = {
            'clientes': 'cliente',
            'agentes': 'agente_aduanal',
            'transportes': 'transporte',
            'ejecutivos': 'nombre'
        };

        return fieldNames[type];
    }

    getTypeLabel(type) {
        const labels = {
            'clientes': 'Cliente',
            'agentes': 'Agente Aduanal',
            'transportes': 'Transporte',
            'ejecutivos': 'Ejecutivo',
            'ADUANAS': 'Aduana',
            'pedimentos': 'Pedimento'
        };

        return labels[type] || type || 'elemento';
    }

    refreshTable(type) {
        // Para ADUANAS, usar la funciÃ³n especÃ­fica
        if (type === 'ADUANAS') {
            refreshADUANASTable();
        } else if (type === 'pedimentos') {
            refreshPedimentosTable();
        } else {
            // Para otros Catálogos, recargar con el parámetro tab en la URL
            const currentTab = this.activeTab;
            const currentUrl = new URL(window.location);
            currentUrl.searchParams.set('tab', currentTab);
            window.location.href = currentUrl.toString();
        }
    }

    showLoading(show) {
        const submitBtn = document.getElementById('submitEditBtn');
        const deleteBtn = document.getElementById('confirmDeleteBtn');

        if (submitBtn) {
            submitBtn.disabled = show;
            submitBtn.innerHTML = show ?
                '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Procesando...' :
                'Guardar';
        }

        if (deleteBtn) {
            deleteBtn.disabled = show;
            deleteBtn.innerHTML = show ?
                '<svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>Eliminando...' :
                'Eliminar';
        }
    }

    showAlert(message, type = 'info') {
        // Crear o actualizar el alert
        let alertContainer = document.getElementById('alertContainer');
        if (!alertContainer) {
            alertContainer = document.createElement('div');
            alertContainer.id = 'alertContainer';
            alertContainer.className = 'fixed top-4 right-4 z-50';
            document.body.appendChild(alertContainer);
        }

        const alertColors = {
            'success': 'bg-green-500',
            'error': 'bg-red-500',
            'info': 'bg-blue-500',
            'warning': 'bg-yellow-500'
        };

        const alert = document.createElement('div');
        alert.className = `${alertColors[type]} text-white px-6 py-3 rounded-lg shadow-lg mb-2 transform translate-x-full transition-transform duration-300`;
        alert.innerHTML = `
            <div class="flex items-center">
                <span>${message}</span>
                <button class="ml-4 text-white hover:text-gray-200" onclick="this.parentElement.parentElement.remove()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;

        alertContainer.appendChild(alert);

        // Animar entrada
        setTimeout(() => {
            alert.classList.remove('translate-x-full');
        }, 100);

        // Auto remover despuÃ©s de 5 segundos
        setTimeout(() => {
            alert.classList.add('translate-x-full');
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    }

    // MÃ©todos para asignaciÃ³n de ejecutivos
    selectAllClients(checked) {
        const checkboxes = document.querySelectorAll('.cliente-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = checked;
        });
        this.updateAssignButtonState();
    }

    updateAssignButtonState() {
        const checkboxes = document.querySelectorAll('.cliente-checkbox');
        const checkedBoxes = document.querySelectorAll('.cliente-checkbox:checked');
        const assignBtn = document.getElementById('assignExecutiveBtn');

        if (assignBtn) {
            assignBtn.disabled = checkedBoxes.length === 0;
            assignBtn.textContent = checkedBoxes.length > 0
                ? `Asignar Ejecutivo (${checkedBoxes.length})`
                : 'Asignar Ejecutivo';
        }

        // Actualizar estado del checkbox principal
        const selectAll = document.getElementById('selectAllClientes');
        if (selectAll) {
            selectAll.checked = checkboxes.length > 0 && checkedBoxes.length === checkboxes.length;
            selectAll.indeterminate = checkedBoxes.length > 0 && checkedBoxes.length < checkboxes.length;
        }
    }

    openAssignModal() {
        const checkedBoxes = document.querySelectorAll('.cliente-checkbox:checked');
        if (checkedBoxes.length === 0) {
            this.showAlert('Por favor, selecciona al menos un cliente.', 'warning');
            return;
        }

        const modal = document.getElementById('assignExecutiveModal');
        const countElement = document.getElementById('selectedClientsCount');
        const ejecutivoSelect = document.getElementById('selectEjecutivo');

        if (countElement) {
            countElement.textContent = checkedBoxes.length;
        }

        if (ejecutivoSelect) {
            ejecutivoSelect.value = '';
        }

        this.showModal(modal);
    }

    closeAssignModal() {
        const modal = document.getElementById('assignExecutiveModal');
        this.hideModal(modal);
    }

    async submitExecutiveAssignment() {
        const checkedBoxes = document.querySelectorAll('.cliente-checkbox:checked');
        const ejecutivoSelect = document.getElementById('selectEjecutivo');

        if (checkedBoxes.length === 0) {
            this.showAlert('No hay clientes seleccionados.', 'error');
            return;
        }

        if (!ejecutivoSelect || !ejecutivoSelect.value) {
            this.showAlert('Por favor, selecciona un ejecutivo.', 'error');
            return;
        }

        const clienteIds = Array.from(checkedBoxes).map(checkbox => checkbox.value);

        const formData = new FormData();
        clienteIds.forEach(id => {
            formData.append('cliente_ids[]', id);
        });
        formData.append('ejecutivo_id', ejecutivoSelect.value);
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

        try {
            this.showLoading(true);

            const response = await fetch('/logistica/clientes/asignar-ejecutivo', {
                method: 'POST',
                headers: getAuthHeaders(),
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                this.showAlert(data.message, 'success');
                this.closeAssignModal();
                this.clearSelection();
                this.refreshTable('clientes');
            } else {
                this.showAlert(data.message || 'Error al asignar ejecutivo.', 'error');
            }
        } catch (error) {
            console.error('Error:', error);
            this.showAlert('Error de conexiÃ³n. Por favor, intenta nuevamente.', 'error');
        } finally {
            this.showLoading(false);
        }
    }

    clearSelection() {
        const checkboxes = document.querySelectorAll('.cliente-checkbox');
        const selectAll = document.getElementById('selectAllClientes');

        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
        });

        if (selectAll) {
            selectAll.checked = false;
            selectAll.indeterminate = false;
        }

        this.updateAssignButtonState();
    }
}

// Inicializar cuando el DOM estÃ© listo
document.addEventListener('DOMContentLoaded', function() {
    window.catalogosMaestros = new CatalogosMaestros();
});

// Funciones globales para compatibilidad
function showTab(tabId) {
    if (window.catalogosMaestros) {
        window.catalogosMaestros.showTab(tabId);
    }
}

function openEditModal(id, type, name) {
    if (window.catalogosMaestros) {
        window.catalogosMaestros.openEditModal(id, type, name);
    }
}

function openDeleteModal(id, type, name) {
    if (window.catalogosMaestros) {
        window.catalogosMaestros.openDeleteModal(id, type, name);
    }
}

// ===================================
// FUNCIONES ESPECÃFICAS PARA ADUANAS
// ===================================

// Abrir modal de importaciÃ³n
function openImportADUANASModal() {
    const modal = document.getElementById('importADUANASModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';

        setTimeout(() => {
            modal.querySelector('.modal-content').style.transform = 'scale(1)';
        }, 10);
    }
}

// Cerrar modal de importaciÃ³n
function closeImportADUANASModal() {
    const modal = document.getElementById('importADUANASModal');
    if (modal) {
        modal.classList.remove('show');
        modal.querySelector('.modal-content').style.transform = 'scale(0.95)';

        setTimeout(() => {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }, 300);

        // Limpiar formulario
        const form = document.getElementById('importADUANASForm');
        if (form) {
            form.reset();
            document.getElementById('selectedFileName').classList.add('hidden');
        }
    }
}

// Manejar selecciÃ³n de archivo
function initFileHandling() {
    const fileInput = document.getElementById('ADUANASFile');
    const fileName = document.getElementById('selectedFileName');

    if (fileInput && fileName) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                fileName.textContent = `Archivo seleccionado: ${file.name}`;
                fileName.classList.remove('hidden');
            } else {
                fileName.classList.add('hidden');
            }
        });
    }
}

// Importar ADUANAS
async function importADUANAS() {
    const form = document.getElementById('importADUANASForm');
    const fileInput = document.getElementById('ADUANASFile');
    const importBtn = document.getElementById('importADUANASBtn');

    if (!fileInput.files[0]) {
        showAlert('Por favor, selecciona un archivo.', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('file', fileInput.files[0]);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

    // Elementos de progreso
    const progressContainer = document.getElementById('importProgress');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');

    try {
        // Deshabilitar botÃ³n y mostrar estado de carga
        if (importBtn) {
            importBtn.disabled = true;
            const importText = importBtn.querySelector('.import-text');
            const loadingText = importBtn.querySelector('.loading-text');
            if (importText) importText.classList.add('hidden');
            if (loadingText) loadingText.classList.remove('hidden');
        }

        // Mostrar barra de progreso
        if (progressContainer) {
            progressContainer.classList.remove('hidden');
        }
        if (progressBar) {
            progressBar.style.width = '10%';
        }
        if (progressText) {
            progressText.textContent = 'Subiendo archivo...';
        }

        // Simular progreso durante la subida
        if (progressBar && progressText) {
            progressBar.style.width = '30%';
            progressText.textContent = 'Procesando archivo...';
        }

        const response = await fetch('/logistica/aduanas/import', {
            method: 'POST',
            headers: getAuthHeaders(),
            body: formData
        });

        // Progreso mientras procesa la respuesta
        if (progressBar && progressText) {
            progressBar.style.width = '80%';
            progressText.textContent = 'Analizando datos...';
        }

        // Verificar si la respuesta es JSON vÃ¡lida
        const contentType = response.headers.get('content-type');
        let data;
        
        if (response.status === 419) {
            throw new Error('SesiÃ³n expirada. Por favor, recarga la página e intÃ©ntalo de nuevo.');
        }
        
        if (contentType && contentType.includes('application/json')) {
            data = await response.json();
        } else {
            // Si no es JSON, probablemente es una página de error HTML
            const text = await response.text();
            console.error('Respuesta no JSON:', text);
            
            if (text.includes('Page Expired')) {
                throw new Error('SesiÃ³n expirada (CSRF). Por favor, recarga la página e intÃ©ntalo de nuevo.');
            }
            
            throw new Error('El servidor devolviÃ³ una respuesta invÃ¡lida (no JSON)');
        }

        if (response.ok && data.success) {
            // Progreso completado
            if (progressBar && progressText) {
                progressBar.style.width = '100%';
                progressText.textContent = 'ImportaciÃ³n completada exitosamente!';
            }
            
            showAlert(`ImportaciÃ³n exitosa: ${data.total_imported} ADUANAS importadas, ${data.total_skipped || 0} omitidas.`, 'success');
            
            // Recargar página despuÃ©s del Ã©xito para actualizar todo
            setTimeout(() => {
                if (progressContainer) {
                    progressContainer.classList.add('hidden');
                }
                // Mantener el tab activo de ADUANAS
                sessionStorage.setItem('activeTab', 'ADUANAS');
                window.location.reload();
            }, 1500);
        } else {
            showAlert(data.message || 'Error en la importaciÃ³n.', 'error');
            // Ocultar progreso y cerrar modal cuando hay error
            setTimeout(() => {
                if (progressContainer) {
                    progressContainer.classList.add('hidden');
                }
                closeImportADUANASModal();
            }, 1500);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error de conexiÃ³n durante la importaciÃ³n.', 'error');
        // Ocultar progreso y cerrar modal en caso de excepciÃ³n
        setTimeout(() => {
            if (progressContainer) {
                progressContainer.classList.add('hidden');
            }
            closeImportADUANASModal();
        }, 1500);
    } finally {
        // Habilitar botÃ³n
        if (importBtn) {
            importBtn.disabled = false;
            const importText = importBtn.querySelector('.import-text');
            const loadingText = importBtn.querySelector('.loading-text');
            if (importText) importText.classList.remove('hidden');
            if (loadingText) loadingText.classList.add('hidden');
        }
    }
}

// Eliminar aduana individual
async function eliminarAduana(id) {
    openConfirmModal(
        'Eliminar Aduana',
        '¿Estás seguro de que deseas eliminar esta aduana? Esta acción no se puede deshacer.',
        'Eliminar',
        async () => {
            await executeEliminarAduana(id);
        }
    );
}

// Función auxiliar para ejecutar la eliminación
async function executeEliminarAduana(id) {
    try {
        const response = await fetch(`/logistica/aduanas/${id}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        const data = await response.json();

        if (data.success) {
            showAlert(data.message, 'success');
            // Asegurar que permanezca en la pestaÃ±a de ADUANAS
            sessionStorage.setItem('activeTab', 'ADUANAS');
            refreshADUANASTable();
        } else {
            showAlert(data.message || 'Error al eliminar la aduana.', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error de conexiÃ³n al eliminar la aduana.', 'error');
    }
}

// Limpiar todas las ADUANAS
async function clearAllADUANAS() {
    const totalADUANAS = document.getElementById('totalADUANAS').textContent;
    
    openConfirmModal(
        'Limpiar Todas las ADUANAS',
        `¿Estás seguro de que deseas eliminar TODAS las ${totalADUANAS} ADUANAS? Esta acción no se puede deshacer.`,
        'Eliminar Todas',
        async () => {
            await executeClearAllADUANAS();
        }
    );
}

// FunciÃ³n auxiliar para ejecutar la limpieza
async function executeClearAllADUANAS() {
    try {
        const response = await fetch('/logistica/aduanas', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        const data = await response.json();

        if (data.success) {
            showAlert(data.message, 'success');
            // Asegurar que permanezca en la pestaÃ±a de ADUANAS
            sessionStorage.setItem('activeTab', 'ADUANAS');
            updateADUANASStats({ total_imported: 0 });
            refreshADUANASTable();
            // Mostrar botÃ³n de importaciÃ³n de ADUANAS ya que se limpiaron los datos
            updateImportButtonsVisibility(false, null);
        } else {
            showAlert(data.message || 'Error al limpiar las ADUANAS.', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error de conexiÃ³n al limpiar las ADUANAS.', 'error');
    }
}

// Actualizar estadÃ­sticas de ADUANAS
function updateADUANASStats(data) {
    const totalElement = document.getElementById('totalADUANAS');
    const ultimaElement = document.getElementById('ultimaImportacion');
    const estadoElement = document.getElementById('estadoImportacion');

    if (totalElement && data.total_imported !== undefined) {
        totalElement.textContent = data.total_imported;
    }

    if (ultimaElement) {
        ultimaElement.textContent = new Date().toLocaleString('es-ES', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    }

    if (estadoElement) {
        estadoElement.textContent = data.success ? 'Actualizado' : 'Error en importaciÃ³n';
    }
}

// FunciÃ³n helper para obtener la página actual de ADUANAS
function getCurrentADUANASPage() {
    // Buscar el enlace de página activo en la paginaciÃ³n de ADUANAS
    const activePage = document.querySelector('#ADUANAS-content .pagination .page-item.active .page-link');
    if (activePage) {
        const pageText = activePage.textContent.trim();
        const pageNum = parseInt(pageText);
        return isNaN(pageNum) ? 1 : pageNum;
    }
    
    // TambiÃ©n intentar obtener de la URL actual
    const urlParams = new URLSearchParams(window.location.search);
    const pageFromUrl = urlParams.get('ADUANAS_page');
    return pageFromUrl ? parseInt(pageFromUrl) : 1;
}

// Refrescar tabla de ADUANAS
async function refreshADUANASTable() {
    try {
        // Obtener la página actual de la paginaciÃ³n
        const currentPage = getCurrentADUANASPage();
        let url = '/logistica/catalogos?tab=ADUANAS';
        
        // Si hay una página especÃ­fica, agregarla a la URL
        if (currentPage > 1) {
            url += `&ADUANAS_page=${currentPage}`;
        }
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        });

        if (response.ok) {
            const html = await response.text();
            
            // Crear un elemento temporal para parsear el HTML
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            
            // Extraer solo la tabla de ADUANAS del HTML recibido
            const newTableContainer = tempDiv.querySelector('#ADUANAS-content .overflow-x-auto');
            const currentTableContainer = document.querySelector('#ADUANAS-content .overflow-x-auto');
            
            if (newTableContainer && currentTableContainer) {
                currentTableContainer.innerHTML = newTableContainer.innerHTML;
            }

            // Actualizar estadÃ­sticas tambiÃ©n
            const newStats = tempDiv.querySelector('#ADUANASStats');
            const currentStats = document.querySelector('#ADUANASStats');
            
            if (newStats && currentStats) {
                currentStats.innerHTML = newStats.innerHTML;
            }

            // Actualizar paginaciÃ³n si existe
            const newPagination = tempDiv.querySelector('#ADUANAS-content .flex.justify-center');
            const currentPagination = document.querySelector('#ADUANAS-content .flex.justify-center');
            
            if (newPagination && currentPagination) {
                currentPagination.innerHTML = newPagination.innerHTML;
            } else if (newPagination && !currentPagination) {
                // Si hay nueva paginaciÃ³n pero no existÃ­a antes, agregarla
                const ADUANASContent = document.querySelector('#ADUANAS-content .p-6');
                if (ADUANASContent) {
                    const paginationDiv = document.createElement('div');
                    paginationDiv.className = 'mt-6 flex justify-center';
                    paginationDiv.innerHTML = newPagination.innerHTML;
                    ADUANASContent.appendChild(paginationDiv);
                }
            }

        } else {
            console.warn('No se pudo actualizar la tabla, recargando página...');
            // Forzar recarga con parámetro tab y página para mantener en ADUANAS
            const currentUrl = new URL(window.location);
            const currentPage = getCurrentADUANASPage();
            currentUrl.searchParams.set('tab', 'ADUANAS');
            if (currentPage > 1) {
                currentUrl.searchParams.set('ADUANAS_page', currentPage);
            }
            sessionStorage.setItem('activeTab', 'ADUANAS');
            window.location.href = currentUrl.toString();
        }
    } catch (error) {
        console.error('Error al actualizar tabla:', error);
        // Como fallback, recargar la página manteniendo el tab y página activa
        const currentUrl = new URL(window.location);
        const currentPage = getCurrentADUANASPage();
        currentUrl.searchParams.set('tab', 'ADUANAS');
        if (currentPage > 1) {
            currentUrl.searchParams.set('ADUANAS_page', currentPage);
        }
        sessionStorage.setItem('activeTab', 'ADUANAS');
        window.location.href = currentUrl.toString();
    }
}

// FunciÃ³n auxiliar para mostrar alertas
function showAlert(message, type = 'info') {
    if (window.catalogosMaestros) {
        window.catalogosMaestros.showAlert(message, type);
    } else {
        alert(message); // Fallback
    }
}

// ===============================================
// MODAL DE CONFIRMACIÃ“N REUTILIZABLE
// ===============================================

let confirmModalCallback = null;

// Abrir modal de confirmaciÃ³n
function openConfirmModal(title, message, confirmText = 'Eliminar', callback = null) {
    const modal = document.getElementById('confirmModal');
    const titleElement = document.getElementById('confirmModalTitle');
    const messageElement = document.getElementById('confirmModalMessage');
    const confirmBtn = document.getElementById('confirmModalBtn');
    
    if (!modal || !titleElement || !messageElement || !confirmBtn) {
        alert(message); // Fallback
        if (callback) callback();
        return;
    }
    
    const confirmTextElement = confirmBtn.querySelector('.confirm-text');
    
    if (titleElement) titleElement.textContent = title;
    if (messageElement) messageElement.textContent = message;
    if (confirmTextElement) confirmTextElement.textContent = confirmText;
    
    confirmModalCallback = callback;
    
    // Asegurar que el modal estÃ© visible
    modal.classList.remove('hidden');
    modal.classList.add('show');
    document.body.style.overflow = 'hidden';
    
    // Animar la apariciÃ³n
    setTimeout(() => {
        const modalContent = modal.querySelector('.modal-content');
        if (modalContent) {
            modalContent.style.transform = 'scale(1)';
        }
    }, 10);
}

// Cerrar modal de confirmaciÃ³n
function closeConfirmModal() {
    const modal = document.getElementById('confirmModal');
    
    if (modal) {
        modal.classList.remove('show');
        const modalContent = modal.querySelector('.modal-content');
        if (modalContent) {
            modalContent.style.transform = 'scale(0.95)';
        }
        
        setTimeout(() => {
            modal.classList.add('hidden');
            restoreBodyScroll();
            confirmModalCallback = null;
        }, 300);
    } else {
        // Como medida de emergencia, restaurar scroll inmediatamente
        restoreBodyScroll();
        confirmModalCallback = null;
    }
}

// Manejar confirmaciÃ³n
function handleConfirm() {
    if (confirmModalCallback && typeof confirmModalCallback === 'function') {
        try {
            confirmModalCallback();
        } catch (error) {
            showAlert('Error al ejecutar la acción.', 'error');
        } finally {
            // Asegurar que el modal se cierre siempre
            setTimeout(() => {
                closeConfirmModal();
            }, 100);
        }
    } else {
        closeConfirmModal();
    }
}

// Agregar evento al botÃ³n de confirmaciÃ³n y mecanismo de emergencia
document.addEventListener('DOMContentLoaded', function() {
    const confirmBtn = document.getElementById('confirmModalBtn');
    if (confirmBtn) {
        confirmBtn.addEventListener('click', handleConfirm);
    }
    
    // Mecanismo de emergencia para restaurar scroll
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            // Al presionar ESC, asegurar que se restaure el scroll
            restoreBodyScroll();
        }
    });
    
    // Detectar si no hay modales abiertos y restaurar scroll
    setInterval(function() {
        const openModals = document.querySelectorAll('.modal-overlay:not(.hidden)');
        if (openModals.length === 0 && document.body.style.overflow === 'hidden') {
            restoreBodyScroll();
        }
    }, 2000);
});

// Inicializar eventos especÃ­ficos de ADUANAS cuando se cargue el DOM
document.addEventListener('DOMContentLoaded', function() {
    // BotÃ³n de importar ADUANAS
    const importADUANASBtn = document.getElementById('importADUANASBtn');
    if (importADUANASBtn) {
        importADUANASBtn.addEventListener('click', openImportADUANASModal);
    }

    // BotÃ³n de limpiar ADUANAS
    const clearADUANASBtn = document.getElementById('clearADUANASBtn');
    if (clearADUANASBtn) {
        clearADUANASBtn.addEventListener('click', clearAllADUANAS);
    }

    // Formulario de importaciÃ³n
    const importForm = document.getElementById('importADUANASForm');
    if (importForm) {
        importForm.addEventListener('submit', function(e) {
            e.preventDefault();
            importADUANAS();
        });
    }

    // BotÃ³n de aÃ±adir aduana
    const addAduanaBtn = document.getElementById('addAduanaBtn');
    if (addAduanaBtn) {
        addAduanaBtn.addEventListener('click', openAddAduanaModal);
    }

    // Formulario de aÃ±adir aduana
    const addAduanaForm = document.getElementById('addAduanaForm');
    if (addAduanaForm) {
        addAduanaForm.addEventListener('submit', function(e) {
            e.preventDefault();
            guardarNuevaAduanaCatalogo();
        });
    }

    // Formulario de editar aduana
    const editAduanaForm = document.getElementById('editAduanaForm');
    if (editAduanaForm) {
        editAduanaForm.addEventListener('submit', function(e) {
            e.preventDefault();
            actualizarAduana();
        });
    }

    // Cerrar modales con botones cancel
    const cancelButtons = document.querySelectorAll('.btn-cancel');
    cancelButtons.forEach(button => {
        button.addEventListener('click', function() {
            closeImportADUANASModal();
            closeAddAduanaModal();
            closeEditAduanaModal();
        });
    });

    // Inicializar manejo de archivos
    initFileHandling();

    // ======================================
    // INICIALIZACIÃ“N DE PEDIMENTOS
    // ======================================
    
    // BotÃ³n de importar pedimentos
    const importPedimentosBtn = document.getElementById('importPedimentosBtn');
    if (importPedimentosBtn) {
        importPedimentosBtn.addEventListener('click', openImportPedimentosModal);
    }

    // Formulario de importar pedimentos - usando envÃ­o normal HTML
    // const importPedimentosForm = document.getElementById('importPedimentosForm');
    // Event listener deshabilitado - usando envÃ­o normal del formulario

    // BotÃ³n de limpiar pedimentos
    const clearPedimentosBtn = document.getElementById('clearPedimentosBtn');
    if (clearPedimentosBtn) {
        clearPedimentosBtn.addEventListener('click', clearAllPedimentos);
    }

    // BotÃ³n de aÃ±adir nuevo pedimento
    const addPedimentoBtn = document.getElementById('addPedimentoBtn');
    if (addPedimentoBtn) {
        addPedimentoBtn.addEventListener('click', openAddPedimentoModal);
    }

    // Formulario de aÃ±adir pedimento
    const addPedimentoForm = document.getElementById('addPedimentoForm');
    if (addPedimentoForm) {
        addPedimentoForm.addEventListener('submit', function(e) {
            e.preventDefault();
            guardarNuevoPedimentoCatalogo();
        });
    }

    // Formulario de editar pedimento
    const editPedimentoForm = document.getElementById('editPedimentoForm');
    if (editPedimentoForm) {
        editPedimentoForm.addEventListener('submit', function(e) {
            e.preventDefault();
            actualizarPedimento();
        });
    }

    // Inicializar el manejo de archivos para pedimentos
    initPedimentosFileHandling();
});

// ===================================
// FUNCIONES PARA AÃ‘ADIR NUEVA ADUANA
// ===================================

// Abrir modal de aÃ±adir aduana
function openAddAduanaModal() {
    const modal = document.getElementById('addAduanaModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';

        setTimeout(() => {
            modal.querySelector('.modal-content').style.transform = 'scale(1)';
        }, 10);

        // Limpiar formulario
        const form = document.getElementById('addAduanaForm');
        if (form) {
            form.reset();
            document.getElementById('ADUANASeccion').value = '0';
        }
    }
}

// Cerrar modal de aÃ±adir aduana
function closeAddAduanaModal() {
    const modal = document.getElementById('addAduanaModal');
    if (modal) {
        modal.classList.remove('show');
        modal.querySelector('.modal-content').style.transform = 'scale(0.95)';

        setTimeout(() => {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }, 300);

        // Limpiar formulario
        const form = document.getElementById('addAduanaForm');
        if (form) {
            form.reset();
        }
    }
}

// Guardar nueva aduana desde el catÃ¡logo
async function guardarNuevaAduanaCatalogo() {
    const saveBtn = document.getElementById('saveAduanaBtn');
    const form = document.getElementById('addAduanaForm');
    const formData = new FormData(form);

    try {
        // Mostrar estado de carga
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.querySelector('.save-text').classList.add('hidden');
            saveBtn.querySelector('.loading-text').classList.remove('hidden');
        }

        const response = await fetch('/logistica/aduanas', {
            method: 'POST',
            headers: getAuthHeaders(),
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showAlert(data.message, 'success');
            closeAddAduanaModal();
            // Asegurar que permanezca en la pestaÃ±a de ADUANAS
            sessionStorage.setItem('activeTab', 'ADUANAS');
            refreshADUANASTable();
            updateADUANASStats({ total_imported: 1 });
        } else {
            showAlert(data.message || 'Error al crear la aduana.', 'error');
        }

    } catch (error) {
        console.error('Error:', error);
        showAlert('Error de conexiÃ³n al crear la aduana.', 'error');
    } finally {
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.querySelector('.save-text').classList.remove('hidden');
            saveBtn.querySelector('.loading-text').classList.add('hidden');
        }
    }
}

// =======================================
// FUNCIONES PARA EDITAR ADUANA
// =======================================

// Abrir modal de editar aduana
function editarAduana(id, codigo, seccion, denominacion, patente, pais) {
    const modal = document.getElementById('editAduanaModal');
    if (modal) {
        // Llenar los campos con los datos actuales
        document.getElementById('editAduanaId').value = id;
        document.getElementById('editAduanaCodigo').value = codigo;
        document.getElementById('editAduanaSeccion').value = seccion || '0';
        document.getElementById('editAduanaDenominacion').value = denominacion;
        document.getElementById('editAduanaPatente').value = patente || '';
        document.getElementById('editAduanaPais').value = pais || 'MX';

        // Mostrar modal
        modal.classList.remove('hidden');
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';

        setTimeout(() => {
            modal.querySelector('.modal-content').style.transform = 'scale(1)';
        }, 10);
    }
}

// Cerrar modal de editar aduana
function closeEditAduanaModal() {
    const modal = document.getElementById('editAduanaModal');
    if (modal) {
        modal.classList.remove('show');
        modal.querySelector('.modal-content').style.transform = 'scale(0.95)';

        setTimeout(() => {
            modal.classList.add('hidden');
            document.body.style.overflow = 'auto';
        }, 300);

        // Limpiar formulario
        const form = document.getElementById('editAduanaForm');
        if (form) {
            form.reset();
        }
    }
}

// Actualizar aduana
async function actualizarAduana() {
    const updateBtn = document.getElementById('updateAduanaBtn');
    const form = document.getElementById('editAduanaForm');
    const formData = new FormData(form);
    const aduanaId = document.getElementById('editAduanaId').value;

    try {
        // Mostrar estado de carga
        if (updateBtn) {
            updateBtn.disabled = true;
            updateBtn.querySelector('.update-text').classList.add('hidden');
            updateBtn.querySelector('.loading-text').classList.remove('hidden');
        }

        const response = await fetch(`/logistica/aduanas/${aduanaId}`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                aduana: formData.get('aduana'),
                seccion: formData.get('seccion'),
                denominacion: formData.get('denominacion'),
                patente: formData.get('patente'),
                pais: formData.get('pais')
            })
        });

        const data = await response.json();

        if (data.success) {
            showAlert(data.message, 'success');
            closeEditAduanaModal();
            // Asegurar que permanezca en la pestaÃ±a de ADUANAS
            sessionStorage.setItem('activeTab', 'ADUANAS');
            refreshADUANASTable();
        } else {
            showAlert(data.message || 'Error al actualizar la aduana.', 'error');
        }

    } catch (error) {
        console.error('Error:', error);
        showAlert('Error de conexiÃ³n al actualizar la aduana.', 'error');
    } finally {
        if (updateBtn) {
            updateBtn.disabled = false;
            updateBtn.querySelector('.update-text').classList.remove('hidden');
            updateBtn.querySelector('.loading-text').classList.add('hidden');
        }
    }
}

// ===============================================
// FUNCIONES ESPECÃFICAS PARA PEDIMENTOS
// ===============================================

// Abrir modal de importaciÃ³n de pedimentos
function openImportPedimentosModal() {
    const modal = document.getElementById('importPedimentosModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';

        setTimeout(() => {
            modal.querySelector('.modal-content').style.transform = 'scale(1)';
        }, 10);
    }
}

// Cerrar modal de importaciÃ³n de pedimentos
function closeImportPedimentosModal() {
    const modal = document.getElementById('importPedimentosModal');
    if (modal) {
        modal.classList.remove('show');
        const modalContent = modal.querySelector('.modal-content');
        if (modalContent) {
            modalContent.style.transform = 'scale(0.95)';
        }

        setTimeout(() => {
            modal.classList.add('hidden');
            restoreBodyScroll();
        }, 300);

        // Limpiar formulario
        const form = document.getElementById('importPedimentosForm');
        if (form) {
            form.reset();
            document.getElementById('selectedPedimentosFileName').classList.add('hidden');
        }
    }
}

// Manejar selecciÃ³n de archivo de pedimentos
function initPedimentosFileHandling() {
    const fileInput = document.getElementById('pedimentosFile');
    const fileName = document.getElementById('selectedPedimentosFileName');

    if (fileInput && fileName) {
        fileInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                fileName.textContent = `Archivo seleccionado: ${file.name}`;
                fileName.classList.remove('hidden');
            } else {
                fileName.classList.add('hidden');
            }
        });
    }
}

// Importar pedimentos - DESHABILITADA (usando envÃ­o normal del formulario)
/*
async function importPedimentos() {
    const form = document.getElementById('importPedimentosForm');
    const fileInput = document.getElementById('pedimentosFile');
    const importBtn = document.getElementById('importPedimentosBtn');
    
    // Elementos de progreso de pedimentos
    const progressContainer = document.getElementById('importPedimentosProgress');
    const progressBar = document.getElementById('progressPedimentosBar');
    const progressText = document.getElementById('progressPedimentosText');

    if (!fileInput.files[0]) {
        showAlert('Por favor, selecciona un archivo.', 'error');
        return;
    }

    const formData = new FormData();
    formData.append('pedimentos_file', fileInput.files[0]);
    formData.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));

    try {
        // Deshabilitar botÃ³n y mostrar estado de carga
        if (importBtn) {
            importBtn.disabled = true;
            const importText = importBtn.querySelector('.import-text');
            const loadingText = importBtn.querySelector('.loading-text');
            if (importText) importText.classList.add('hidden');
            if (loadingText) loadingText.classList.remove('hidden');
        }

        // Mostrar barras de progreso (tanto en página como en modal)
        const progressModalContainer = document.getElementById('importPedimentosProgressModal');
        const progressModalBar = document.getElementById('progressPedimentosBarModal');
        const progressModalText = document.getElementById('progressPedimentosTextModal');

        // Mostrar progreso en página
        if (progressContainer) {
            progressContainer.classList.remove('hidden');
        }
        if (progressBar) {
            progressBar.style.width = '10%';
        }
        if (progressText) {
            progressText.textContent = 'Subiendo archivo de pedimentos...';
        }

        // Mostrar progreso en modal
        if (progressModalContainer) {
            progressModalContainer.classList.remove('hidden');
        }
        if (progressModalBar) {
            progressModalBar.style.width = '10%';
        }
        if (progressModalText) {
            progressModalText.textContent = 'Subiendo archivo...';
        }

        // Simular progreso durante la subida
        if (progressBar && progressText) {
            progressBar.style.width = '30%';
            progressText.textContent = 'Procesando archivo de pedimentos...';
        }
        if (progressModalBar && progressModalText) {
            progressModalBar.style.width = '30%';
            progressModalText.textContent = 'Procesando archivo...';
        }

        const response = await fetch('/logistica/pedimentos/import', {
            method: 'POST',
            headers: getAuthHeaders(),
            body: formData
        });

        // Progreso mientras procesa la respuesta
        if (progressBar && progressText) {
            progressBar.style.width = '70%';
            progressText.textContent = 'Analizando categorÃ­as y cÃ³digos...';
        }
        if (progressModalBar && progressModalText) {
            progressModalBar.style.width = '70%';
            progressModalText.textContent = 'Analizando datos...';
        }

        const contentType = response.headers.get('content-type');
        let data;
        
        if (response.status === 419) {
            throw new Error('SesiÃ³n expirada. Por favor, recarga la página e intÃ©ntalo de nuevo.');
        }
        
        if (contentType && contentType.includes('application/json')) {
            data = await response.json();
        } else {
            const text = await response.text();
            console.error('Respuesta no JSON:', text);
            
            if (text.includes('Page Expired')) {
                throw new Error('SesiÃ³n expirada (CSRF). Por favor, recarga la página e intÃ©ntalo de nuevo.');
            }
            
            throw new Error('El servidor devolviÃ³ una respuesta invÃ¡lida (no JSON)');
        }

        if (response.ok && data.success) {
            // Progreso completado
            if (progressBar && progressText) {
                progressBar.style.width = '100%';
                progressText.textContent = 'ImportaciÃ³n de pedimentos completada exitosamente!';
            }
            if (progressModalBar && progressModalText) {
                progressModalBar.style.width = '100%';
                progressModalText.textContent = 'Completado!';
            }
            
            showAlert(`ImportaciÃ³n exitosa: ${data.total_imported} pedimentos importados, ${data.total_skipped || 0} omitidos.`, 'success');
            
            // Recargar página despuÃ©s del Ã©xito para actualizar todo
            setTimeout(() => {
                if (progressContainer) {
                    progressContainer.classList.add('hidden');
                }
                if (progressModalContainer) {
                    progressModalContainer.classList.add('hidden');
                }
                // Mantener el tab activo de pedimentos
                sessionStorage.setItem('activeTab', 'pedimentos');
                window.location.reload();
            }, 1500);
        } else {
            showAlert(data.message || 'Error en la importaciÃ³n.', 'error');
            setTimeout(() => {
                if (progressContainer) {
                    progressContainer.classList.add('hidden');
                }
                if (progressModalContainer) {
                    progressModalContainer.classList.add('hidden');
                }
                closeImportPedimentosModal();
            }, 1500);
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error de conexiÃ³n durante la importaciÃ³n.', 'error');
        setTimeout(() => {
            if (progressContainer) {
                progressContainer.classList.add('hidden');
            }
            if (progressModalContainer) {
                progressModalContainer.classList.add('hidden');
            }
            closeImportPedimentosModal();
        }, 1500);
    } finally {
        if (importBtn) {
            importBtn.disabled = false;
            const importText = importBtn.querySelector('.import-text');
            const loadingText = importBtn.querySelector('.loading-text');
            if (importText) importText.classList.remove('hidden');
            if (loadingText) loadingText.classList.add('hidden');
        }
    }
}
*/

// Eliminar pedimento individual
async function eliminarPedimento(id) {
    openConfirmModal(
        'Eliminar Pedimento',
        '¿Estás seguro de que deseas eliminar este pedimento? Esta acción no se puede deshacer.',
        'Eliminar',
        async () => {
            await executeEliminarPedimento(id);
        }
    );
}

// Función auxiliar para ejecutar la eliminación
async function executeEliminarPedimento(id) {
    try {
        const response = await fetch(`/logistica/pedimentos/${id}`, {
            method: 'DELETE',
            headers: getAuthHeaders()
        });

        const data = await response.json();

        if (data.success) {
            showAlert(data.message, 'success');
            sessionStorage.setItem('activeTab', 'pedimentos');
            refreshPedimentosTable();
        } else {
            showAlert(data.message || 'Error al eliminar el pedimento.', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error de conexiÃ³n al eliminar el pedimento.', 'error');
    }
}

// Limpiar todos los pedimentos
async function clearAllPedimentos() {
    const totalPedimentos = document.getElementById('totalPedimentos').textContent;
    
    openConfirmModal(
        'Limpiar Todos los Pedimentos',
        `¿Estás seguro de que deseas eliminar TODOS los ${totalPedimentos} pedimentos? Esta acción no se puede deshacer.`,
        'Eliminar Todos',
        async () => {
            try {
                await executeClearAllPedimentos();
            } finally {
                // Asegurar que el scroll se restaure siempre
                restoreBodyScroll();
            }
        }
    );
}

// FunciÃ³n auxiliar para ejecutar la limpieza
async function executeClearAllPedimentos() {

    try {
        const response = await fetch('/logistica/pedimentos', {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
            }
        });

        const data = await response.json();

        if (data.success) {
            showAlert(data.message, 'success');
            updatePedimentosStats({ 
                success: true, 
                total_imported: 0, 
                deleted_count: data.deleted_count 
            });
            refreshPedimentosTable();
            // Mostrar botÃ³n de importaciÃ³n de pedimentos ya que se limpiaron los datos
            updateImportButtonsVisibility(null, false);
        } else {
            showAlert(data.message || 'Error al limpiar los pedimentos.', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error de conexiÃ³n al limpiar pedimentos.', 'error');
    }
}

// Actualizar estadÃ­sticas de pedimentos
function updatePedimentosStats(data) {
    const totalElement = document.getElementById('totalPedimentos');
    const ultimaElement = document.getElementById('ultimaImportacionPedimentos');
    const estadoElement = document.getElementById('estadoImportacionPedimentos');
    
    if (totalElement && data.total_imported !== undefined) {
        // Si es una operaciÃ³n de limpieza (deleted_count existe), usar el count devuelto del servidor
        if (data.deleted_count !== undefined) {
            totalElement.textContent = '0';
        } else {
            // Para importaciones normales, sumar al total actual
            const currentTotal = parseInt(totalElement.textContent) || 0;
            totalElement.textContent = currentTotal + data.total_imported;
        }
    }
    
    if (ultimaElement) {
        ultimaElement.textContent = new Date().toLocaleString('es-MX');
    }
    
    if (estadoElement) {
        if (data.deleted_count !== undefined) {
            estadoElement.textContent = 'Limpiado';
        } else {
            estadoElement.textContent = data.success ? 'Actualizado' : 'Error en importaciÃ³n';
        }
    }
}

// FunciÃ³n helper para obtener la página actual de pedimentos
function getCurrentPedimentosPage() {
    // Buscar el enlace de página activo en la paginaciÃ³n de pedimentos
    const activePage = document.querySelector('#pedimentos-content .pagination .page-item.active .page-link');
    if (activePage) {
        const pageText = activePage.textContent.trim();
        const pageNum = parseInt(pageText);
        return isNaN(pageNum) ? 1 : pageNum;
    }
    
    // TambiÃ©n intentar obtener de la URL actual
    const urlParams = new URLSearchParams(window.location.search);
    const pageFromUrl = urlParams.get('pedimentos_page');
    return pageFromUrl ? parseInt(pageFromUrl) : 1;
}

// Refrescar tabla de pedimentos
async function refreshPedimentosTable() {
    try {
        // Obtener la página actual de la paginaciÃ³n
        const currentPage = getCurrentPedimentosPage();
        let url = '/logistica/catalogos?tab=pedimentos';
        
        // Si hay una página especÃ­fica, agregarla a la URL
        if (currentPage > 1) {
            url += `&pedimentos_page=${currentPage}`;
        }
        
        const response = await fetch(url, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'text/html'
            }
        });

        if (response.ok) {
            const html = await response.text();
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = html;
            
            const newTableContainer = tempDiv.querySelector('#pedimentos-content .overflow-x-auto');
            const currentTableContainer = document.querySelector('#pedimentos-content .overflow-x-auto');
            
            if (newTableContainer && currentTableContainer) {
                currentTableContainer.innerHTML = newTableContainer.innerHTML;
            }

            const newStats = tempDiv.querySelector('#pedimentosStats');
            const currentStats = document.querySelector('#pedimentosStats');
            
            if (newStats && currentStats) {
                currentStats.innerHTML = newStats.innerHTML;
            }

            const newPagination = tempDiv.querySelector('#pedimentos-content .flex.justify-center');
            const currentPagination = document.querySelector('#pedimentos-content .flex.justify-center');
            
            if (newPagination && currentPagination) {
                currentPagination.innerHTML = newPagination.innerHTML;
            }

        } else {
            console.warn('No se pudo actualizar la tabla, recargando página...');
            // Forzar recarga con parámetro tab y página para mantener en pedimentos
            const currentUrl = new URL(window.location);
            const currentPage = getCurrentPedimentosPage();
            currentUrl.searchParams.set('tab', 'pedimentos');
            if (currentPage > 1) {
                currentUrl.searchParams.set('pedimentos_page', currentPage);
            }
            sessionStorage.setItem('activeTab', 'pedimentos');
            window.location.href = currentUrl.toString();
        }
    } catch (error) {
        console.error('Error al actualizar tabla:', error);
        // Como fallback, recargar la página manteniendo el tab y página activa
        const currentUrl = new URL(window.location);
        const currentPage = getCurrentPedimentosPage();
        currentUrl.searchParams.set('tab', 'pedimentos');
        if (currentPage > 1) {
            currentUrl.searchParams.set('pedimentos_page', currentPage);
        }
        sessionStorage.setItem('activeTab', 'pedimentos');
        window.location.href = currentUrl.toString();
    }
}

// =======================================
// FUNCIONES PARA AÃ‘ADIR NUEVO PEDIMENTO
// =======================================

// Abrir modal de aÃ±adir pedimento
function openAddPedimentoModal() {
    const modal = document.getElementById('addPedimentoModal');
    if (modal) {
        modal.classList.remove('hidden');
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';

        setTimeout(() => {
            modal.querySelector('.modal-content').style.transform = 'scale(1)';
        }, 10);

        const form = document.getElementById('addPedimentoForm');
        if (form) {
            form.reset();
        }
    }
}

// Cerrar modal de aÃ±adir pedimento
function closeAddPedimentoModal() {
    const modal = document.getElementById('addPedimentoModal');
    if (modal) {
        modal.classList.remove('show');
        const modalContent = modal.querySelector('.modal-content');
        if (modalContent) {
            modalContent.style.transform = 'scale(0.95)';
        }

        setTimeout(() => {
            modal.classList.add('hidden');
            restoreBodyScroll();
        }, 300);

        const form = document.getElementById('addPedimentoForm');
        if (form) {
            form.reset();
        }
    }
}

// Guardar nuevo pedimento desde el catÃ¡logo
async function guardarNuevoPedimentoCatalogo() {
    const saveBtn = document.getElementById('savePedimentoBtn');
    const form = document.getElementById('addPedimentoForm');
    const formData = new FormData(form);

    try {
        if (saveBtn) {
            saveBtn.disabled = true;
            saveBtn.querySelector('.save-text').classList.add('hidden');
            saveBtn.querySelector('.loading-text').classList.remove('hidden');
        }

        const response = await fetch('/logistica/pedimentos', {
            method: 'POST',
            headers: getAuthHeaders(),
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            showAlert(data.message, 'success');
            closeAddPedimentoModal();
            refreshPedimentosTable();
            updatePedimentosStats({ total_imported: 1 });
        } else {
            showAlert(data.message || 'Error al crear el pedimento.', 'error');
        }

    } catch (error) {
        console.error('Error:', error);
        showAlert('Error de conexiÃ³n al crear el pedimento.', 'error');
    } finally {
        if (saveBtn) {
            saveBtn.disabled = false;
            saveBtn.querySelector('.save-text').classList.remove('hidden');
            saveBtn.querySelector('.loading-text').classList.add('hidden');
        }
    }
}

// =======================================
// FUNCIONES PARA EDITAR PEDIMENTO
// =======================================

// Abrir modal de editar pedimento
function editarPedimento(id, clave, descripcion, categoria = '') {
    const modal = document.getElementById('editPedimentoModal');
    if (modal) {
        const idField = document.getElementById('editPedimentoId');
        const claveField = document.getElementById('editPedimentoClave');
        const descripcionField = document.getElementById('editPedimentoDescripcion');
        const categoriaField = document.getElementById('editPedimentoCategoria');
        
        if (idField) idField.value = id;
        if (claveField) claveField.value = clave;
        if (descripcionField) descripcionField.value = descripcion;
        if (categoriaField) categoriaField.value = categoria;

        modal.classList.remove('hidden');
        modal.classList.add('show');
        document.body.style.overflow = 'hidden';

        setTimeout(() => {
            const modalContent = modal.querySelector('.modal-content');
            if (modalContent) {
                modalContent.style.transform = 'scale(1)';
            }
        }, 10);
    }
}

// Cerrar modal de editar pedimento
function closeEditPedimentoModal() {
    const modal = document.getElementById('editPedimentoModal');
    if (modal) {
        modal.classList.remove('show');
        const modalContent = modal.querySelector('.modal-content');
        if (modalContent) {
            modalContent.style.transform = 'scale(0.95)';
        }

        setTimeout(() => {
            modal.classList.add('hidden');
            restoreBodyScroll();
        }, 300);

        const form = document.getElementById('editPedimentoForm');
        if (form) {
            form.reset();
        }
    }
}

// Actualizar pedimento
async function actualizarPedimento() {
    const updateBtn = document.getElementById('updatePedimentoBtn');
    const form = document.getElementById('editPedimentoForm');
    const formData = new FormData(form);
    const pedimentoId = document.getElementById('editPedimentoId').value;

    try {
        if (updateBtn) {
            updateBtn.disabled = true;
            updateBtn.querySelector('.update-text').classList.add('hidden');
            updateBtn.querySelector('.loading-text').classList.remove('hidden');
        }

        const response = await fetch(`/logistica/pedimentos/${pedimentoId}`, {
            method: 'PUT',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                clave: formData.get('clave'),
                descripcion: formData.get('descripcion'),
                categoria: formData.get('categoria'),
                subcategoria: formData.get('subcategoria')
            })
        });

        const data = await response.json();

        if (data.success) {
            showAlert(data.message, 'success');
            closeEditPedimentoModal();
            refreshPedimentosTable();
        } else {
            showAlert(data.message || 'Error al actualizar el pedimento.', 'error');
        }

    } catch (error) {
        console.error('Error:', error);
        showAlert('Error de conexiÃ³n al actualizar el pedimento.', 'error');
    } finally {
        if (updateBtn) {
            updateBtn.disabled = false;
            updateBtn.querySelector('.update-text').classList.remove('hidden');
            updateBtn.querySelector('.loading-text').classList.add('hidden');
        }
    }
}




// Eliminar todos los clientes
async function deleteAllClients() {
    openConfirmModal(
        'Eliminar Todos los Clientes',
        '¿Estás completamente seguro de que deseas eliminar TODOS los clientes de la base de datos?\n\nEsta acción NO se puede deshacer y eliminará permanentemente todos los registros de clientes.',
        'Sí, eliminar todos',
        async () => {
            await executeDeleteAllClientes();
        }
    );
}

// Función auxiliar para ejecutar la eliminación
async function executeDeleteAllClientes() {
    try {
        showAlert('Eliminando todos los clientes...', 'info');
        
        const response = await fetch('/logistica/clientes/all', {
            method: 'DELETE',
            headers: getAuthHeaders()
        });
        
        const data = await response.json();
        
        if (data.success) {
            showAlert(data.message + '. La página se recargará.', 'success');
            
            // Recargar la página después de 2 segundos
            setTimeout(() => {
                sessionStorage.setItem('activeTab', 'clientes');
                window.location.reload();
            }, 2000);
        } else {
            showAlert(data.message || 'Error al eliminar los clientes', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showAlert('Error de conexión al eliminar los clientes', 'error');
    }
}

// === FUNCIONES PARA PERIODICIDAD AVANZADA ===

function togglePeriodicidadOptions() {
    const periodicidadSelect = document.getElementById('editPeriodicidad');
    const opcionesSemanal = document.getElementById('opciones-semanal');
    const helpText = document.getElementById('periodicidad-help');
    
    if (!periodicidadSelect) return;
    
    const valor = periodicidadSelect.value;
    
    // Ocultar opciones semanal por defecto
    if (opcionesSemanal) opcionesSemanal.classList.add('hidden');
    
    // Mostrar opciones según la selección
    switch(valor) {
        case 'Semanal':
            if (opcionesSemanal) opcionesSemanal.classList.remove('hidden');
            if (helpText) helpText.textContent = 'Reporte semanal los lunes';
            break;
            
        case 'Tri-semanal':
            if (helpText) helpText.textContent = 'Reportes los lunes, miércoles y viernes';
            break;
            
        case 'Diario':
        default:
            if (helpText) helpText.textContent = 'Reportes diarios de lunes a viernes';
            break;
    }
}

function buildPeriodicidadString() {
    const periodicidadSelect = document.getElementById('editPeriodicidad');
    if (!periodicidadSelect) return 'Diario';
    
    const tipo = periodicidadSelect.value;
    let resultado = tipo;
    
    if (tipo === 'Semanal') {
        const diaSemanal = document.getElementById('dia-semanal');
        if (diaSemanal && diaSemanal.value) {
            resultado = `Semanal-${diaSemanal.value}`;
        }
    }
    
    return resultado;
}

function parsePeriodicidadString(periodicidadString) {
    if (!periodicidadString) return { tipo: 'Diario', opcion: null };
    
    const partes = periodicidadString.split('-');
    const tipo = partes[0];
    const opcion = partes[1] || null;
    
    return { tipo, opcion };
}

function setPeriodicidadValues(periodicidadString) {
    const { tipo, opcion } = parsePeriodicidadString(periodicidadString);
    
    const periodicidadSelect = document.getElementById('editPeriodicidad');
    if (periodicidadSelect) {
        // Asegurar compatibilidad con valores antiguos
        let tipoFinal = tipo;
        if (tipo === 'Quincenal' || tipo === 'Mensual') {
            tipoFinal = 'Diario'; // Convertir valores antiguos a Diario
        }
        periodicidadSelect.value = tipoFinal || 'Diario';
    }
    
    // Trigger change para mostrar opciones apropiadas
    togglePeriodicidadOptions();
    
    // Set valores específicos para semanal
    if (tipo === 'Semanal' && opcion) {
        const diaSemanal = document.getElementById('dia-semanal');
        if (diaSemanal) {
            diaSemanal.value = opcion;
        }
    }
}

