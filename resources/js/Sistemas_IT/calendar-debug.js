// Script de depuración para el calendario de mantenimiento
export function initCalendarDebug() {
    console.log('🔧 Iniciando depuración del calendario...');
    
    // Verificar que los elementos existen
    const elements = {
        'maintenance-calendar': document.getElementById('maintenance-calendar'),
        'calendar-grid': document.getElementById('calendar-grid'),
        'timeSlotsWrapper': document.getElementById('timeSlotsWrapper'),
        'timeSlotsList': document.getElementById('timeSlotsList'),
        'selectedDateLabel': document.getElementById('selectedDateLabel'),
        'noSlotsMessage': document.getElementById('noSlotsMessage'),
        'monthLabel': document.getElementById('monthLabel')
    };
    
    console.log('📋 Verificando elementos del DOM:');
    Object.entries(elements).forEach(([name, element]) => {
        if (element) {
            console.log(`✅ ${name}: Encontrado`);
        } else {
            console.error(`❌ ${name}: NO encontrado`);
        }
    });
    
    // Mostrar información de disponibilidad si existe
    if (window.availabilityData) {
        console.log('📊 Datos de disponibilidad encontrados:', window.availabilityData);
    } else {
        console.warn('⚠️ No se encontraron datos de disponibilidad');
    }
    
    // Agregar botón de prueba
    addTestButton();
}

function addTestButton() {
    const testButton = document.createElement('button');
    testButton.textContent = '🧪 Probar Calendario';
    testButton.className = 'fixed top-4 right-4 bg-blue-500 text-white px-4 py-2 rounded shadow-lg z-50';
    testButton.onclick = () => {
        console.log('🧪 Ejecutando prueba del calendario...');
        
        // Simular datos de prueba
        const testData = {
            '2024-12-20': {
                available_slots: 2,
                total_slots: 4,
                slots: [
                    { id: 1, start_time: '09:00', end_time: '10:00', available: true },
                    { id: 2, start_time: '10:00', end_time: '11:00', available: false },
                    { id: 3, start_time: '14:00', end_time: '15:00', available: true },
                    { id: 4, start_time: '15:00', end_time: '16:00', available: false }
                ]
            }
        };
        
        // Asignar datos globales
        window.availabilityData = testData;
        
        // Buscar función de renderizado
        if (window.renderMonth) {
            window.renderMonth();
            console.log('✅ Calendar renderizado con datos de prueba');
        } else if (window.generateCalendarDays) {
            window.generateCalendarDays();
            console.log('✅ Calendar generado con datos de prueba');
        } else {
            console.error('❌ No se encontraron funciones de renderizado');
        }
    };
    
    document.body.appendChild(testButton);
}

// Auto-inicializar si estamos en la página correcta
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initCalendarDebug);
} else {
    initCalendarDebug();
}