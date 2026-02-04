<?php

use Illuminate\Support\Facades\Route;

// --- Controllers de Autenticación y Perfil ---
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ActivityController;

// --- Controllers de Sistemas IT ---
use App\Http\Controllers\Sistemas_IT\AdminController;
use App\Http\Controllers\Sistemas_IT\NotificationController;
use App\Http\Controllers\Sistemas_IT\TicketController;
use App\Http\Controllers\Sistemas_IT\MaintenanceController;
use App\Http\Controllers\Users\UsersController;

// --- Controllers de Recursos Humanos ---
use App\Http\Controllers\RH\ExpedienteController;
use App\Http\Controllers\RH\RelojChecadorImportController;
use App\Http\Controllers\RH\CapacitacionController;
use App\Http\Controllers\EvaluacionController;

// --- Controllers de Logística ---
use App\Http\Controllers\Logistica\OperacionLogisticaController;
use App\Http\Controllers\Logistica\ClienteController;
use App\Http\Controllers\Logistica\AgenteAduanalController;
use App\Http\Controllers\Logistica\TransporteController;
use App\Http\Controllers\Logistica\PostOperacionController;
use App\Http\Controllers\Logistica\ReporteController;
use App\Http\Controllers\Logistica\PedimentoController; // <--- AGREGADO
use App\Http\Controllers\Logistica\LogisticaCorreoCCController; // <--- AGREGADO
use App\Http\Controllers\Logistica\CatalogosController;


/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// 1. RUTAS PÚBLICAS
Route::get('/', function () {
    return view('welcome');
})->name('welcome');

// Consulta pública Logística
Route::controller(OperacionLogisticaController::class)->prefix('logistica/consulta-publica')->name('logistica.consulta-publica.')->group(function() {
    Route::get('/', 'consultaPublica')->name('index');
    Route::get('/buscar', 'buscarOperacionPublica')->name('buscar');
});


// 2. RUTAS DE AUTENTICACIÓN
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register'])->name('register.store');
});

Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');


// 3. RUTAS GENERALES (Autenticadas)
Route::middleware('auth')->group(function () {
    // Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Actividades
    Route::resource('activities', ActivityController::class);
    Route::post('/activities/batch', [ActivityController::class, 'storeBatch'])->name('activities.storeBatch');
    Route::put('/activities/{id}/approve', [ActivityController::class, 'approve'])->name('activities.approve');
    Route::put('/activities/{id}/reject', [ActivityController::class, 'reject'])->name('activities.reject');
    Route::put('/activities/{id}/start', [ActivityController::class, 'start'])->name('activities.start');

    // Tickets (Usuario)
    Route::controller(TicketController::class)->prefix('ticket')->name('tickets.')->group(function() {
        Route::get('/create/{tipo}', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/mis-tickets', 'misTickets')->name('mis-tickets');
        Route::delete('/{id}', 'destroy')->name('destroy');
        Route::get('/{id}/can-cancel', 'canCancel')->name('can-cancel');
        Route::post('/{id}/acknowledge-update', 'acknowledgeUpdate')->name('acknowledge');
        Route::post('/acknowledge-all', 'acknowledgeAllUpdates')->name('acknowledge-all');
    });

    // Mantenimiento (Usuario)
    Route::get('/maintenance/availability', [MaintenanceController::class, 'availability'])->name('maintenance.availability');
    Route::get('/maintenance/slots', [MaintenanceController::class, 'slots'])->name('maintenance.slots');

    // Capacitación (Usuario)
    Route::prefix('capacitacion')->name('capacitacion.')->group(function () {
        Route::get('/', [CapacitacionController::class, 'index'])->name('index');
        Route::get('/ver/{id}', [CapacitacionController::class, 'show'])->name('show');
    });

    // Evaluación
    Route::prefix('capital-humano')->name('rh.')->controller(EvaluacionController::class)->group(function () {
        Route::get('/evaluacion', 'index')->name('evaluacion.index');
        Route::get('/evaluacion/{id}', 'show')->name('evaluacion.show');
        Route::post('/evaluacion', 'store')->name('evaluacion.store');
        Route::put('/evaluacion/{id}', 'update')->name('evaluacion.update');
        Route::get('/evaluacion/{id}/resultados', 'resultados')->name('evaluacion.resultados');
    });
});


// 4. MÓDULO LOGÍSTICA
Route::middleware(['auth', 'area.logistica'])->prefix('logistica')->name('logistica.')->group(function () {
    
    // Dashboard
    Route::get('/', function () { return view('Logistica.index'); })->name('index');
    Route::get('/matriz-seguimiento', [OperacionLogisticaController::class, 'index'])->name('matriz-seguimiento');

    // Operaciones
    Route::resource('operaciones', OperacionLogisticaController::class);
    Route::post('operaciones/recalcular-status', [OperacionLogisticaController::class, 'recalcularStatus'])->name('operaciones.recalcular');
    Route::put('operaciones/{id}/status', [OperacionLogisticaController::class, 'updateStatus'])->name('operaciones.status');
    Route::get('operaciones/{id}/historial', [OperacionLogisticaController::class, 'obtenerHistorial'])->name('operaciones.historial');

    // Catálogos Básicos
    Route::resource('clientes', ClienteController::class);
    Route::post('clientes/importar', [ClienteController::class, 'import'])->name('clientes.import');
    Route::post('clientes/asignar-ejecutivo', [ClienteController::class, 'asignarEjecutivo'])->name('clientes.asignar-ejecutivo');
    Route::delete('clientes/all/delete', [ClienteController::class, 'deleteAll'])->middleware('admin')->name('clientes.delete-all');

    Route::resource('agentes', AgenteAduanalController::class)->except(['index', 'create', 'edit', 'show']);
    
    Route::resource('transportes', TransporteController::class)->except(['index', 'create', 'edit', 'show']);
    Route::get('transportes/por-tipo', [TransporteController::class, 'getByType'])->name('transportes.by-type');

    // --- PEDIMENTOS (AGREGADO) ---
    Route::controller(PedimentoController::class)->prefix('pedimentos')->name('pedimentos.')->group(function() {
        Route::get('/', 'index')->name('index');
        Route::post('/', 'store')->name('store');
        Route::get('/{id}', 'show')->name('show');
        Route::delete('/{id}', 'destroy')->name('destroy');
        Route::put('/{id}/estado-pago', 'updateEstadoPago')->name('update-estado');
        Route::post('/marcar-pagados', 'marcarPagados')->name('marcar-pagados');
        Route::get('/clave/{clave}', 'getPedimentosPorClave')->name('por-clave');
        Route::post('/actualizar-individual', 'actualizarPedimento')->name('actualizar-individual');
        Route::get('/monedas/list', 'getMonedas')->name('monedas');
    });

    // --- IMPORTACIÓN Y API DE PEDIMENTOS (PedimentoImportController) ---
    Route::controller(\App\Http\Controllers\Logistica\PedimentoImportController::class)
        ->prefix('pedimentos/gestion') // Prefijo URL
        ->name('pedimentos.import.')   // Prefijo Nombre: logistica.pedimentos.import.
        ->group(function() {
            
            // Esta es la ruta que te daba error: 'logistica.pedimentos.import.legacy'
            Route::post('/importar-legacy', 'import')->name('legacy');

            // Rutas API para gestión del catálogo (CRUD AJAX)
            Route::get('/', 'index')->name('index');       // Listar JSON
            Route::post('/', 'store')->name('store');      // Crear
            Route::put('/{id}', 'update')->name('update'); // Editar
            Route::delete('/{id}', 'destroy')->name('destroy'); // Eliminar
            Route::delete('/limpiar-todo', 'clear')->name('clear'); // Truncate
            
            // Selects dinámicos
            Route::get('/categorias-list', 'getCategorias')->name('categorias');
            Route::get('/subcategorias-list', 'getSubcategorias')->name('subcategorias');
    });

    // --- CORREOS CC ---
    // 1. Ruta API específica (La que faltaba)
    // Apuntamos al método index, asumiendo que detecta si es AJAX para devolver JSON
    Route::get('correos-cc/api', [LogisticaCorreoCCController::class, 'index'])
        ->name('correos-cc.api');

    // 2. CRUD Estándar
    Route::resource('correos-cc', LogisticaCorreoCCController::class);

    // Post-Operaciones
    Route::controller(PostOperacionController::class)->prefix('post-operaciones')->name('post-operaciones.')->group(function() {
        Route::get('globales', 'indexGlobales')->name('globales');
        Route::post('globales', 'storeGlobal')->name('store-global');
        Route::get('operaciones/{id}', 'getByOperacion')->name('get-by-operacion');
        Route::put('operaciones/{id}/actualizar', 'bulkUpdate')->name('bulk-update');

        
    });

    // Reportes
    Route::controller(ReporteController::class)->prefix('reportes')->name('reportes.')->group(function() {
        Route::get('/', 'index')->name('index');
        Route::get('/export', 'exportCSV')->name('export');
        Route::get('/exportar-matriz', 'exportMatrizSeguimiento')->name('export-matriz');
        Route::get('/export-excel', 'exportExcelProfesional')->name('export-excel');
        Route::get('/resumen/exportar', 'exportResumenEjecutivo')->name('resumen.export');
        Route::get('/pedimentos/exportar', [\App\Http\Controllers\Logistica\PedimentoController::class, 'exportCSV'])
            ->name('pedimentos.export');
        Route::post('/enviar-correo', 'enviarCorreo')->name('enviar-correo');
    });

    // --- VISTA GENERAL DE CATÁLOGOS (La que faltaba) ---
    Route::get('/catalogos', [\App\Http\Controllers\Logistica\CatalogosController::class, 'index'])
        ->name('catalogos');

    // --- CAMPOS PERSONALIZADOS (Configuración) ---
    Route::controller(\App\Http\Controllers\Logistica\CampoPersonalizadoController::class)
        ->prefix('campos-personalizados')
        ->name('campos-personalizados.')
        ->group(function() {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::delete('/{id}', 'destroy')->name('destroy');
            
            // ESTA ES LA RUTA CRÍTICA QUE FALTA O FALLA:
            Route::post('/valor', 'storeValor')->name('store-valor'); 
            
            Route::get('/activos', 'getCamposActivos');
            Route::get('/operacion/{id}/valores', 'getValoresOperacion');
        });
    });


// 5. MÓDULO RECURSOS HUMANOS
Route::middleware(['auth', 'area.rh'])->group(function () {
    Route::get('/recursos-humanos', function () { return view('Recursos_Humanos.index'); })->name('recursos-humanos.index');

    // Reloj Checador
    Route::controller(RelojChecadorImportController::class)->prefix('recursos-humanos/reloj')->name('rh.reloj.')->group(function() {
        Route::get('/', 'index')->name('index');
        Route::post('/start', 'start')->name('start');
        Route::get('/progreso/{key}', 'progress')->name('import.progress');
        Route::post('/store', 'store')->name('store');
        Route::put('/update/{id}', 'update')->name('update');
        Route::delete('/clear', 'clear')->name('clear');
    });

    // Expedientes
    Route::prefix('recursos-humanos/expedientes')->name('rh.expedientes.')->controller(ExpedienteController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::post('/refresh', 'refresh')->name('refresh');
        Route::get('/{empleado}', 'show')->name('show');
        Route::get('/{empleado}/editar', 'edit')->name('edit');
        Route::put('/{empleado}', 'update')->name('update');
        Route::delete('/{empleado}', 'destroy')->name('destroy');
        Route::post('/{id}/upload', 'uploadDocument')->name('upload');
        Route::delete('/documento/{id}', 'deleteDocument')->name('delete-doc');
        Route::post('/{id}/import-excel', 'importFormatoId')->name('import-excel');
        Route::get('/documento/{id}/descargar', 'downloadDocument')->name('download');
    });

    // Capacitación (Gestión)
    Route::prefix('recursos-humanos/capacitacion')->name('rh.capacitacion.')->controller(CapacitacionController::class)->group(function () {
        Route::get('/gestion', 'manage')->name('manage');
        Route::post('/subir', 'store')->name('store');
        Route::get('/{id}/editar', 'edit')->name('edit');
        Route::put('/{id}', 'update')->name('update');
        Route::delete('/{id}', 'destroy')->name('destroy');
        Route::delete('/adjunto/{id}', 'destroyAdjunto')->name('destroyAdjunto');
    });

    
});


// 6. MÓDULO SISTEMAS (ADMIN)
Route::middleware(['auth', 'verified', 'sistemas_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');

    // Tickets
    Route::controller(TicketController::class)->group(function() {
        Route::get('/tickets', 'index')->name('tickets.index');
        Route::get('/tickets/{ticket}', 'show')->name('tickets.show');
        Route::patch('/tickets/{ticket}', 'update')->name('tickets.update');
        Route::post('/tickets/{ticket}/change-maintenance-date', 'changeMaintenanceDate')->name('tickets.change-maintenance-date');
        Route::get('/maintenance-slots/available', 'getAvailableMaintenanceSlots')->name('maintenance-slots.available');
    });

    // Mantenimiento
    Route::controller(MaintenanceController::class)->name('maintenance.')->group(function() {
        Route::get('/maintenance', 'adminIndex')->name('index');
        Route::get('/maintenance/computers', function () { return redirect()->route('admin.maintenance.index'); })->name('computers.index');
        
        Route::post('/maintenance/computers', 'storeComputer')->name('computers.store');
        Route::get('/maintenance/computers/{computerProfile}', 'showComputer')->name('computers.show');
        Route::get('/maintenance/computers/{computerProfile}/edit', 'editComputer')->name('computers.edit');
        Route::put('/maintenance/computers/{computerProfile}', 'updateComputer')->name('computers.update');
        Route::delete('/maintenance/computers/{computerProfile}', 'destroyComputer')->name('computers.destroy');

        Route::post('/maintenance/slots', 'store')->name('slots.store');
        Route::post('/maintenance/slots/bulk', 'storeBulk')->name('slots.store-bulk');
        Route::put('/maintenance/slots/{slot}', 'updateSlot')->name('slots.update');
        Route::delete('/maintenance/slots/{slot}', 'destroySlot')->name('slots.destroy');
        Route::delete('/maintenance/slots/destroy-past', 'destroyPastSlots')->name('slots.destroy-past');
    });

    // Usuarios
    Route::controller(UsersController::class)->prefix('users')->name('users.')->group(function() {
        Route::get('/', 'index')->name('index');
        Route::get('/create', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{user}', 'show')->name('show');
        Route::get('/{user}/edit', 'edit')->name('edit');
        Route::put('/{user}', 'update')->name('update');
        Route::delete('/{user}', 'destroy')->name('destroy');
        Route::post('/{user}/approve', 'approve')->name('approve');
        Route::post('/{user}/reject', 'reject')->name('reject');
        Route::delete('/{user}/rejection', 'destroyRejected')->name('rejections.destroy');
        Route::delete('/blocked-emails/{blockedEmail}', 'destroyBlockedEmail')->name('blocked-emails.destroy');
    });
});

// API Notificaciones Admin
Route::middleware(['auth', 'admin'])->prefix('api/notifications')->controller(NotificationController::class)->group(function () {
    Route::get('/count', 'getUnreadCount');
    Route::get('/unread', 'getUnreadTickets');
    Route::post('/{ticket}/read', 'markAsRead');
    Route::post('/mark-all-read', 'markAllAsRead');
    Route::get('/stats', 'getStats');
});

require __DIR__.'/auth.php';