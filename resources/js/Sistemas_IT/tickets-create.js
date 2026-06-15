// Funcionalidad para la página "Crear Ticket"
// JavaScript para manejo de formulario, calendario de mantenimiento, imágenes y validaciones

document.addEventListener('DOMContentLoaded', function() {
    initializeTicketCreate();
});

function initializeTicketCreate() {
    const ticketType = getTicketType();
    
    // Inicializar funcionalidades básicas
    initializeFormHandling();
    initializeImageUpload();
    initializeProgramSelection();
    
    // Solo inicializar calendario si es mantenimiento
    if (ticketType === 'mantenimiento') {
        initializeSimpleCalendar();
        // addCalendarDebugButton(); // Comentado temporalmente
    }
}

// Función del calendario con disponibilidad
function initializeSimpleCalendar() {
    const scheduling = document.getElementById('maintenanceScheduling');
    if (!scheduling) {
        return;
    }
    
    const calendar = document.getElementById('calendarGrid');
    const monthLabel = document.getElementById('calendarMonthLabel');
    const prevBtn = document.getElementById('calendarPrev');
    const nextBtn = document.getElementById('calendarNext');
    
    // URLs para APIs
    const availabilityUrl = scheduling.getAttribute('data-availability-url');
    const slotsUrl = scheduling.getAttribute('data-slots-url');
    
    if (!calendar || !monthLabel) {
        console.error('❌ Elementos del calendario no encontrados');
        return;
    }
    
    if (!availabilityUrl || !slotsUrl) {
        console.error('❌ URLs de API no configuradas');
        return;
    }
    

    
    let currentDate = new Date();
    let availabilityData = {};
    
    async function loadAvailability() {
        try {
            const month = currentDate.toISOString().substr(0, 7);
            const url = `${availabilityUrl}?month=${month}`;
            
            const response = await fetch(url);
            
            if (!response.ok) {
                showNotification('Error al cargar disponibilidad del calendario', 'error');
                return;
            }
            
            const apiData = await response.json();
            availabilityData = {};
            if (apiData.days && Array.isArray(apiData.days)) {
                apiData.days.forEach(day => {
                    availabilityData[day.date] = day;
                });
            }
            
            renderMonth();
        } catch (error) {
            showNotification('Error de conexión al cargar disponibilidad', 'error');
        }
    }
    
    function renderMonth() {
        
        // Actualizar título
        const monthName = currentDate.toLocaleDateString('es-ES', { 
            month: 'long', 
            year: 'numeric' 
        });
        monthLabel.textContent = monthName.charAt(0).toUpperCase() + monthName.slice(1);
        
        // Limpiar calendario
        calendar.innerHTML = '';
        
        // Calcular fechas
        const firstDay = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
        const startDate = new Date(firstDay);
        startDate.setDate(startDate.getDate() - firstDay.getDay());
        
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        // Generar días
        for (let i = 0; i < 35; i++) {
            const cellDate = new Date(startDate);
            cellDate.setDate(startDate.getDate() + i);
            
            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = cellDate.getDate();
            button.className = 'h-10 w-full rounded-lg text-sm font-medium transition-colors';
            
            const dateKey = cellDate.toISOString().split('T')[0];
            const availability = availabilityData[dateKey];
            const isCurrentMonth = cellDate.getMonth() === currentDate.getMonth();
            const isPast = cellDate < today;
            
            // Aplicar estilos según disponibilidad
            if (!isCurrentMonth) {
                // Días de otros meses
                button.className += ' text-gray-300 bg-gray-50 cursor-not-allowed';
                button.disabled = true;
            } else if (isPast) {
                // Días pasados
                button.className += ' text-gray-400 bg-gray-100 cursor-not-allowed';
                button.disabled = true;
            } else if (availability) {
                // Días con datos de disponibilidad
                if (availability.available_slots > 0) {
                    // Verde: Disponible
                    button.className += ' bg-green-100 text-green-800 hover:bg-green-200 border border-green-200';
                    button.addEventListener('click', () => selectDate(dateKey, cellDate));
                } else if (availability.total_slots > 0 && availability.booked > 0 && availability.booked < availability.total_capacity) {
                    // Amarillo: Día parcialmente reservado (algunos slots ocupados, otros disponibles)
                    button.className += ' bg-yellow-100 text-yellow-800 hover:bg-yellow-200 border border-yellow-200';
                    button.addEventListener('click', () => selectDate(dateKey, cellDate));
                } else if (availability.total_slots > 0) {
                    // Azul: Completamente ocupado (sin espacios disponibles)
                    button.className += ' bg-blue-100 text-blue-800 cursor-not-allowed border border-blue-200';
                    button.disabled = true;
                } else {
                    // Gris: Sin slots configurados
                    button.className += ' text-gray-400 cursor-not-allowed bg-gray-100';
                    button.disabled = true;
                }
            } else {
                // Rojo: Sin disponibilidad configurada
                button.className += ' bg-red-100 text-red-800 cursor-not-allowed border border-red-200';
                button.disabled = true;
            }
            
            calendar.appendChild(button);
        }
    }
    
    async function selectDate(dateKey, cellDate) {
        try {
            
            const response = await fetch(`${slotsUrl}?date=${dateKey}`);
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const slotsData = await response.json();
            
            // Extraer el array de slots del objeto de respuesta
            const slots = slotsData.slots || [];
            
            displayTimeSlots(slots, dateKey, cellDate);
        } catch (error) {
            console.error('❌ Error cargando horarios:', error);
            showNotification('Error al cargar los horarios disponibles', 'error');
        }
    }
    
    function displayTimeSlots(slots, dateKey, cellDate) {
        // Buscar elementos de horarios
        const timeSlotsWrapper = document.getElementById('timeSlotsWrapper');
        const timeSlotsList = document.getElementById('timeSlotsList');
        const selectedDateLabel = document.getElementById('selectedDateLabel');
        const noSlotsMessage = document.getElementById('noSlotsMessage');
        
        if (!timeSlotsWrapper || !timeSlotsList) {
            console.error('❌ Elementos de horarios no encontrados');
            return;
        }
        
        // Mostrar fecha seleccionada
        const dateStr = cellDate.toLocaleDateString('es-ES', { 
            weekday: 'long', 
            year: 'numeric', 
            month: 'long', 
            day: 'numeric' 
        });
        
        if (selectedDateLabel) {
            selectedDateLabel.textContent = dateStr.charAt(0).toUpperCase() + dateStr.slice(1);
        }
        
        // Limpiar lista de horarios
        timeSlotsList.innerHTML = '';
        
        if (slots.length === 0) {
            if (noSlotsMessage) {
                noSlotsMessage.classList.remove('hidden');
            }
            timeSlotsWrapper.classList.add('hidden');
            return;
        }
        
        if (noSlotsMessage) {
            noSlotsMessage.classList.add('hidden');
        }
        
        // Verificar que slots sea un array válido
        if (!Array.isArray(slots)) {
            console.error('❌ slots no es un array:', typeof slots, slots);
            if (noSlotsMessage) {
                noSlotsMessage.classList.remove('hidden');
            }
            timeSlotsWrapper.classList.add('hidden');
            return;
        }
        
        // Crear botones de horarios
        slots.forEach(slot => {
        const slotButton = document.createElement('button');
        slotButton.type = 'button';
        
        // Usar estructura correcta del slot
        const available = slot.available > 0 || slot.status === 'available';
        const startTime = slot.start || slot.start_time || 'N/D';
        const endTime = slot.end || slot.end_time || 'N/D';
        
        // Determinar estado y color inline
        let statusText = 'Desconocido';
        let statusColor = 'text-red-600';
        
        switch(slot.status) {
            case 'available':
                statusText = 'Disponible';
                statusColor = 'text-green-600';
                break;
            case 'partial':
                statusText = 'Parcial';
                statusColor = 'text-yellow-600';
                break;
            case 'full':
                statusText = 'Ocupado';
                statusColor = 'text-blue-600';
                break;
            case 'past':
                statusText = 'Pasado';
                statusColor = 'text-gray-600';
                break;
        }
        
        slotButton.className = `p-4 rounded-2xl border-2 transition-all text-left hover:scale-105 ${
                available 
                    ? 'border-green-200 bg-green-50 hover:border-green-300 hover:bg-green-100' 
                    : 'border-gray-200 bg-gray-50 cursor-not-allowed opacity-50'
            }`;
            
            slotButton.innerHTML = `
                <div class="font-semibold text-slate-900">${startTime} - ${endTime}</div>
                <div class="text-sm text-slate-600 mt-1">
                    Disponibles: ${slot.available || 0}/${slot.capacity || 1}
                </div>
                <div class="text-xs mt-1">
                    Estado: <span class="${statusColor}">${statusText}</span>
                </div>
            `;
            
            if (available) {
                slotButton.addEventListener('click', () => selectTimeSlot(slot, dateKey));
            } else {
                slotButton.disabled = true;
            }
            
            timeSlotsList.appendChild(slotButton);
        });
        
        timeSlotsWrapper.classList.remove('hidden');
    }
    
    function selectTimeSlot(slot, dateKey) {
        
        // Actualizar inputs ocultos
        const slotIdInput = document.getElementById('maintenance_slot_id');
        const selectedDateInput = document.getElementById('maintenance_selected_date');
        const selectedSlotLabel = document.getElementById('selectedSlotLabel');
        
        if (slotIdInput) slotIdInput.value = slot.id;
        if (selectedDateInput) selectedDateInput.value = dateKey;
        if (selectedSlotLabel) {
            const startTime = slot.start || slot.start_time || 'N/A';
            const endTime = slot.end || slot.end_time || 'N/A';
            selectedSlotLabel.textContent = `Horario: ${startTime} - ${endTime}`;
        }
        
        // Actualizar estilos de botones
        document.querySelectorAll('#timeSlotsList button').forEach(btn => {
            btn.classList.remove('border-blue-300', 'bg-blue-100', 'ring-2', 'ring-blue-200');
            btn.classList.add('border-green-200', 'bg-green-50');
        });
        
        event.target.closest('button').classList.remove('border-green-200', 'bg-green-50');
        event.target.closest('button').classList.add('border-blue-300', 'bg-blue-100', 'ring-2', 'ring-blue-200');
        
        showNotification(`Horario seleccionado: ${slot.start_time} - ${slot.end_time}`, 'success');
    }
    
    // Event listeners para navegación
    if (prevBtn) {
        prevBtn.addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() - 1);
            renderMonth();
            hideTimeSlots();
        });
    }
    
    if (nextBtn) {
        nextBtn.addEventListener('click', () => {
            currentDate.setMonth(currentDate.getMonth() + 1);
            renderMonth();
            hideTimeSlots();
        });
    }
    
    function hideTimeSlots() {
        const timeSlotsWrapper = document.getElementById('timeSlotsWrapper');
        const selectedSlotLabel = document.getElementById('selectedSlotLabel');
        const slotIdInput = document.getElementById('maintenance_slot_id');
        const selectedDateInput = document.getElementById('maintenance_selected_date');
        
        if (timeSlotsWrapper) timeSlotsWrapper.classList.add('hidden');
        if (selectedSlotLabel) selectedSlotLabel.textContent = '';
        if (slotIdInput) slotIdInput.value = '';
        if (selectedDateInput) selectedDateInput.value = '';
    }
    
    // Inicializar: cargar disponibilidad y renderizar
    loadAvailability();
}

// ===== UTILIDADES =====
function getTicketType() {
    const mainElement = document.querySelector('[data-ticket-type]');
    return mainElement ? mainElement.getAttribute('data-ticket-type') : 'unknown';
}

function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    const bgColor = type === 'success' ? 'bg-green-500' : 
                   type === 'error' ? 'bg-red-500' : 
                   type === 'warning' ? 'bg-yellow-500' : 'bg-blue-500';
    
    notification.className = `fixed top-4 right-4 ${bgColor} text-white px-6 py-3 rounded-lg shadow-lg z-50 transform transition-all duration-300 translate-x-full opacity-0`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Animar entrada
    setTimeout(() => {
        notification.classList.remove('translate-x-full', 'opacity-0');
    }, 100);
    
    // Remover después de 4 segundos
    setTimeout(() => {
        notification.classList.add('translate-x-full', 'opacity-0');
        setTimeout(() => {
            if (notification.parentNode) {
                notification.parentNode.removeChild(notification);
            }
        }, 300);
    }, 4000);
}

// ===== MANEJO DE FORMULARIO =====
function initializeFormHandling() {
    const form = document.querySelector('[data-ticket-create] form');
    if (!form) return;
    
    // Validación antes de envío
    form.addEventListener('submit', function(e) {
        if (!validateForm()) {
            e.preventDefault();
            return false;
        }
        
        // Mostrar loading en botón de envío
        const submitButton = form.querySelector('button[type="submit"]');
        if (submitButton) {
            const originalText = submitButton.textContent;
            submitButton.disabled = true;
            submitButton.innerHTML = `
                <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                Creando ticket...
            `;
            
            // Restaurar después de 10 segundos por si hay error
            setTimeout(() => {
                submitButton.disabled = false;
                submitButton.textContent = originalText;
            }, 10000);
        }
    });
}

function validateForm() {
    const ticketType = getTicketType();
    let isValid = true;
    
    // Validar descripción
    const descripcion = document.getElementById('descripcion_problema');
    if (descripcion && ticketType !== 'mantenimiento') {
        if (!descripcion.value.trim()) {
            showNotification('La descripción del problema es obligatoria', 'error');
            descripcion.focus();
            isValid = false;
        }
    }
    
    // Validar programa para software
    if (ticketType === 'software') {
        const programa = document.getElementById('nombre_programa');
        if (programa && !programa.value) {
            showNotification('Debes seleccionar un programa', 'error');
            programa.focus();
            isValid = false;
        }
    }
    
    // Validar slot de mantenimiento
    if (ticketType === 'mantenimiento') {
        const slotId = document.getElementById('maintenance_slot_id');
        if (slotId && !slotId.value) {
            showNotification('Debes seleccionar una fecha y horario para el mantenimiento', 'error');
            isValid = false;
        }
    }
    
    return isValid;
}

// ===== SELECCIÓN DE PROGRAMA =====
function initializeProgramSelection() {
    const programSelect = document.getElementById('nombre_programa');
    const otroInput = document.getElementById('otro_programa_nombre');
    
    if (!programSelect || !otroInput) return;
    
    // Mostrar/ocultar campo "Otro" basado en selección
    function toggleOtroField() {
        const isOtro = programSelect.value === 'Otro';
        const container = otroInput.closest('div');
        
        if (container) {
            if (isOtro) {
                container.style.display = 'block';
                otroInput.required = true;
                setTimeout(() => otroInput.focus(), 100);
            } else {
                container.style.display = 'none';
                otroInput.required = false;
                otroInput.value = '';
            }
        }
    }

    programSelect.addEventListener('change', toggleOtroField);
    toggleOtroField();
}

// ===== MANEJO DE IMÁGENES =====
function initializeImageUpload() {
    const imageInput = document.getElementById('imageInput');
    const imagePreview = document.getElementById('imagePreview');
    const uploadButton = document.getElementById('uploadButton');
    const imageCountSpan = document.getElementById('imageCount');
    
    if (!imageInput || !imagePreview) {
        console.log('No se encontraron elementos de imagen, posiblemente no es un ticket de software/hardware');
        return;
    }
    
    let selectedFiles = [];
    const maxImages = 5;
    
    // Conectar el botón con el input de archivo
    if (uploadButton) {
        uploadButton.addEventListener('click', function(e) {
            e.preventDefault();
            imageInput.click();
        });
    }
    
    // Manejar selección de archivos
    imageInput.addEventListener('change', function(e) {
        const files = Array.from(e.target.files);
        
        // Validar archivos
        const validFiles = files.filter(file => {
            if (file.size > 10 * 1024 * 1024) { // 10MB
                showNotification(`La imagen "${file.name}" es muy grande (máximo 10MB)`, 'warning');
                return false;
            }
            
            if (!file.type.startsWith('image/')) {
                showNotification(`"${file.name}" no es una imagen válida`, 'warning');
                return false;
            }
            
            return true;
        });
        
        // Agregar archivos válidos
        selectedFiles = [...selectedFiles, ...validFiles];
        
        if (selectedFiles.length > maxImages) {
            selectedFiles = selectedFiles.slice(0, maxImages);
            showNotification(`Máximo ${maxImages} imágenes permitidas`, 'warning');
        }
        
        updateImagePreview();
        updateFileInput();
        updateImageCount();
    });
    
    function updateImageCount() {
        if (imageCountSpan) {
            imageCountSpan.textContent = `${selectedFiles.length}/${maxImages} imágenes`;
        }
    }
    
    function updateImagePreview() {
        if (selectedFiles.length === 0) {
            imagePreview.innerHTML = '<p class="text-sm text-slate-400 text-center py-8 col-span-full">Las imágenes seleccionadas aparecerán aquí</p>';
            return;
        }
        
        imagePreview.innerHTML = '';
        
        selectedFiles.forEach((file, index) => {
            const reader = new FileReader();
            reader.onload = function(e) {
                const imageContainer = document.createElement('div');
                imageContainer.className = 'relative group';
                
                imageContainer.innerHTML = `
                    <img src="${e.target.result}" alt="Preview ${index + 1}" 
                         class="h-24 w-24 rounded-xl object-cover shadow-md transition group-hover:shadow-lg">
                    <button type="button" 
                            onclick="removeImage(${index})"
                            class="absolute -top-2 -right-2 flex h-6 w-6 items-center justify-center rounded-full bg-red-500 text-white shadow-lg transition hover:bg-red-600 hover:scale-110">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                    <div class="absolute inset-0 bg-black/20 opacity-0 group-hover:opacity-100 transition rounded-xl flex items-center justify-center">
                        <span class="text-white text-xs font-medium">Ver</span>
                    </div>
                `;
                
                // Expandir imagen al hacer clic
                const img = imageContainer.querySelector('img');
                img.addEventListener('click', () => expandImage(e.target.result, `Imagen ${index + 1}`));
                
                imagePreview.appendChild(imageContainer);
            };
            reader.readAsDataURL(file);
        });
    }
    
    function updateFileInput() {
        const dt = new DataTransfer();
        selectedFiles.forEach(file => {
            if (file) dt.items.add(file);
        });
        imageInput.files = dt.files;
    }
    
    // Función global para remover imagen
    window.removeImage = function(index) {
        selectedFiles.splice(index, 1);
        updateImagePreview();
        updateFileInput();
        updateImageCount();
        
        if (selectedFiles.length === 0) {
            showNotification('Imagen eliminada', 'info');
        }
    };
}

// ===== MODAL DE IMAGEN EXPANDIDA =====
function expandImage(src, alt) {
    let modal = document.getElementById('imageExpandModal');
    
    if (!modal) {
        modal = createImageModal();
    }
    
    const modalImage = modal.querySelector('#expandedImage');
    const modalTitle = modal.querySelector('#expandedImageTitle');
    
    modalImage.src = src;
    modalImage.alt = alt;
    modalTitle.textContent = alt;
    
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    document.body.style.overflow = 'hidden';
}

function createImageModal() {
    const modal = document.createElement('div');
    modal.id = 'imageExpandModal';
    modal.className = 'fixed inset-0 z-50 hidden items-center justify-center bg-black/80 backdrop-blur-sm p-4';
    
    modal.innerHTML = `
        <div class="relative max-w-[95vw] max-h-[95vh] flex items-center justify-center">
            <button onclick="closeExpandedImage()" 
                    class="absolute -top-4 -right-4 z-10 h-10 w-10 rounded-full bg-white/90 text-gray-800 shadow-lg hover:bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all">
                <svg class="h-6 w-6 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
            <img id="expandedImage" src="" alt="" 
                 class="max-w-[90vw] max-h-[85vh] object-contain rounded-lg shadow-2xl bg-white">
            <div class="absolute bottom-0 left-0 right-0 bg-black/50 text-white p-3 rounded-b-lg">
                <p id="expandedImageTitle" class="text-sm font-medium text-center"></p>
            </div>
        </div>
    `;
    
    // Cerrar con ESC o click fuera
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && !modal.classList.contains('hidden')) {
            closeExpandedImage();
        }
    });
    
    modal.addEventListener('click', function(e) {
        if (e.target === modal) {
            closeExpandedImage();
        }
    });
    
    document.body.appendChild(modal);
    return modal;
}

function closeExpandedImage() {
    const modal = document.getElementById('imageExpandModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
        document.body.style.overflow = '';
    }
}

// Exportar funciones principales
export {
    initializeTicketCreate,
    showNotification,
    closeExpandedImage
};

// Hacer funciones disponibles globalmente para compatibilidad con Blade
window.initializeTicketCreate = initializeTicketCreate;
window.showNotification = showNotification;
window.closeExpandedImage = closeExpandedImage;