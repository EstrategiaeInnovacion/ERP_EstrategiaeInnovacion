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
use App\Http\Controllers\Sistemas_IT\CredencialEquipoController;
use App\Http\Controllers\Sistemas_IT\ActivosApiController;
use App\Http\Controllers\Sistemas_IT\ActivosController;
use App\Http\Controllers\Users\UsersController;

// --- Controllers de Recursos Humanos ---
use App\Http\Controllers\RH\ExpedienteController;
use App\Http\Controllers\RH\RelojChecadorImportController;
use App\Http\Controllers\RH\CapacitacionController;
use App\Http\Controllers\EvaluacionController;

// --- Controllers de Legal ---
use App\Http\Controllers\Legal\LegalController;
use App\Http\Controllers\Legal\PaginaLegalController;
use App\Http\Controllers\Legal\MatrizConsultaController;
use App\Http\Controllers\Legal\CategoriaLegalController;
use App\Http\Controllers\Legal\DigitalizacionController;

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
use App\Http\Controllers\Logistica\ColumnaVisibleController;


/* |-------------------------------------------------------------------------- | Web Routes |-------------------------------------------------------------------------- */

// 1. RUTAS PÚBLICAS
Route::get('/', function () {
    if (auth()->check()) {
        $empleado = auth()->user()->empleado;
        $avisosPendientes = [];
        if ($empleado) {
            $avisosPendientes = \App\Models\AvisoAsistencia::where('empleado_id', $empleado->id)
                ->where('leido', false)
                ->get();
        }
        return view('welcome', compact('avisosPendientes'));
    }
    return view('welcome');
})->name('welcome');

// Consulta pública Logística
Route::controller(OperacionLogisticaController::class)->prefix('logistica/consulta-publica')->name('logistica.consulta-publica.')->group(function () {
    Route::get('/', 'consultaPublica')->name('index');
    Route::get('/buscar', 'buscarOperacionPublica')->name('buscar');
});


// 2. RUTAS DE AUTENTICACIÓN
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class , 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class , 'login']);
    Route::get('/register', [AuthController::class , 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class , 'register'])->name('register.store');
});

Route::post('/logout', [AuthController::class , 'logout'])->middleware('auth')->name('logout');


// 3. RUTAS GENERALES (Autenticadas)
Route::middleware('auth')->group(function () {
    // Perfil
    Route::get('/profile', [ProfileController::class , 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class , 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class , 'destroy'])->name('profile.destroy');

    // Marcar aviso de asistencia como leído (cualquier empleado)
    Route::post('/aviso-leido/{id}', function(\Illuminate\Http\Request $request, $id) {
        $aviso = \App\Models\AvisoAsistencia::findOrFail($id);
        // Solo el destinatario puede marcarlo
        if ($aviso->empleado_id !== \Auth::user()->empleado?->id) abort(403);
        $aviso->update(['leido' => true, 'leido_at' => now()]);
        return redirect()->route('welcome');
    })->name('rh.reloj.aviso_leido');

    // Actividades
    Route::put('/activities/{activity}/validate', [App\Http\Controllers\ActivityController::class , 'validateCompletion'])->name('activities.validate');
    Route::get('/activities/client-report', [App\Http\Controllers\ActivityController::class , 'generateClientReport'])->name('activities.client_report');
    // Ventanas de planeación (antes del resource para evitar que {activity} lo intercepte)
    Route::get('/activities/planeacion-ventanas', [ActivityController::class, 'getPlaneacionVentanas'])->name('activities.planeacion.ventanas');
    Route::post('/activities/planeacion-ventanas', [ActivityController::class, 'savePlaneacionVentana'])->name('activities.planeacion.save');
    Route::delete('/activities/planeacion-ventanas/{id}', [ActivityController::class, 'deletePlaneacionVentana'])->name('activities.planeacion.delete');
    Route::resource('activities', ActivityController::class);
    Route::post('/activities/batch', [ActivityController::class , 'storeBatch'])->name('activities.storeBatch');
    Route::put('/activities/{id}/approve', [ActivityController::class , 'approve'])->name('activities.approve');
    Route::put('/activities/{id}/reject', [ActivityController::class , 'reject'])->name('activities.reject');
    Route::put('/activities/{id}/start', [ActivityController::class , 'start'])->name('activities.start');

    // Tickets (Usuario)
    Route::controller(TicketController::class)->prefix('ticket')->name('tickets.')->group(function () {
            Route::get('/create/{tipo}', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/mis-tickets', 'misTickets')->name('mis-tickets');
            Route::delete('/{id}', 'destroy')->name('destroy');
            Route::get('/{id}/can-cancel', 'canCancel')->name('can-cancel');
            Route::post('/{id}/acknowledge-update', 'acknowledgeUpdate')->name('acknowledge');
            Route::post('/acknowledge-all', 'acknowledgeAllUpdates')->name('acknowledge-all');
        }
        );

        // Mantenimiento (Usuario)
        Route::get('/maintenance/availability', [MaintenanceController::class , 'availability'])->name('maintenance.availability');
        Route::get('/maintenance/slots', [MaintenanceController::class , 'slots'])->name('maintenance.slots');
        Route::get('/maintenance/check-availability', [MaintenanceController::class , 'checkAvailability'])->name('maintenance.check-availability');

        // Capacitación (Usuario)
        Route::prefix('capacitacion')->name('capacitacion.')->group(function () {
            Route::get('/', [CapacitacionController::class , 'index'])->name('index');
            Route::get('/ver/{id}', [CapacitacionController::class , 'show'])->name('show');
        }
        );

        // Evaluación
        Route::prefix('capital-humano')->name('rh.')->controller(EvaluacionController::class)->group(function () {
            Route::get('/evaluacion', 'index')->name('evaluacion.index');
            Route::get('/evaluacion/{id}', 'show')->name('evaluacion.show');
            Route::post('/evaluacion', 'store')->name('evaluacion.store');
            Route::put('/evaluacion/{id}', 'update')->name('evaluacion.update');
            Route::delete('/evaluacion/{id}', 'destroy')->name('evaluacion.destroy');
            Route::get('/evaluacion/{id}/resultados', 'resultados')->name('evaluacion.resultados');
            // Gestión de ventanas de evaluación (Admin RH)
            Route::get('/evaluacion-ventanas', 'getVentanas')->name('evaluacion.ventanas.index');
            Route::post('/evaluacion-ventanas', 'saveVentana')->name('evaluacion.ventanas.store');
            Route::patch('/evaluacion-ventanas/{id}/toggle', 'toggleVentana')->name('evaluacion.ventanas.toggle');
        }
        );
    });


// 4. MÓDULO LOGÍSTICA
Route::middleware(['auth', 'area.logistica'])->prefix('logistica')->name('logistica.')->group(function () {

    // Dashboard
    Route::get('/', function () {
            return view('Logistica.index');
        }
        )->name('index');
        Route::get('/matriz-seguimiento', [OperacionLogisticaController::class , 'index'])->name('matriz-seguimiento');

        // --- API Configuración de Columnas por Ejecutivo ---
        Route::prefix('columnas-config')->name('columnas-config.')->group(function () {
            Route::get('/ejecutivos', [ColumnaVisibleController::class , 'getEjecutivos'])->name('ejecutivos');
            Route::get('/ejecutivo/{empleadoId}', [ColumnaVisibleController::class , 'getConfiguracion'])->name('get');
            Route::post('/guardar', [ColumnaVisibleController::class , 'guardarConfiguracion'])->name('guardar');
            Route::post('/guardar-completa', [ColumnaVisibleController::class , 'guardarConfiguracionCompleta'])->name('guardar-completa');
            // Rutas para configuración global (mostrar a todos)
            Route::get('/global', [ColumnaVisibleController::class , 'getConfiguracionGlobal'])->name('global');
            Route::post('/guardar-global', [ColumnaVisibleController::class , 'guardarConfiguracionGlobal'])->name('guardar-global');
        }
        );

        // Operaciones
        Route::resource('operaciones', OperacionLogisticaController::class);
        Route::post('operaciones/recalcular-status', [OperacionLogisticaController::class , 'recalcularStatus'])->name('operaciones.recalcular');
        Route::put('operaciones/{id}/status', [OperacionLogisticaController::class , 'updateStatus'])->name('operaciones.status');
        Route::get('operaciones/{id}/historial', [OperacionLogisticaController::class , 'obtenerHistorial'])->name('operaciones.historial');

        // Catálogos Básicos
        Route::resource('clientes', ClienteController::class);
        Route::post('clientes/importar', [ClienteController::class , 'import'])->name('clientes.import');
        Route::post('clientes/asignar-ejecutivo', [ClienteController::class , 'asignarEjecutivo'])->name('clientes.asignar-ejecutivo');
        Route::delete('clientes/all/delete', [ClienteController::class , 'deleteAll'])->middleware('admin')->name('clientes.delete-all');

        Route::resource('agentes', AgenteAduanalController::class)->except(['index', 'create', 'edit', 'show']);

        Route::resource('transportes', TransporteController::class)->except(['index', 'create', 'edit', 'show']);
        Route::get('transportes/por-tipo', [TransporteController::class , 'getByType'])->name('transportes.by-type');

        // --- PEDIMENTOS (AGREGADO) ---
        Route::controller(PedimentoController::class)->prefix('pedimentos')->name('pedimentos.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::get('/{id}', 'show')->name('show');
            Route::delete('/{id}', 'destroy')->name('destroy');
            Route::put('/{id}/estado-pago', 'updateEstadoPago')->name('update-estado');
            Route::post('/marcar-pagados', 'marcarPagados')->name('marcar-pagados');
            Route::get('/clave/{clave}', 'getPedimentosPorClave')->name('por-clave');
            Route::post('/actualizar-individual', 'actualizarPedimento')->name('actualizar-individual');
            Route::get('/monedas/list', 'getMonedas')->name('monedas');
        }
        );

        // --- IMPORTACIÓN Y API DE PEDIMENTOS (PedimentoImportController) ---
        Route::controller(\App\Http\Controllers\Logistica\PedimentoImportController::class)
            ->prefix('pedimentos/gestion') // Prefijo URL
            ->name('pedimentos.import.') // Prefijo Nombre: logistica.pedimentos.import.
            ->group(function () {

            // Esta es la ruta que te daba error: 'logistica.pedimentos.import.legacy'
            Route::post('/importar-legacy', 'import')->name('legacy');

            // Rutas API para gestión del catálogo (CRUD AJAX)
            Route::get('/', 'index')->name('index'); // Listar JSON
            Route::post('/', 'store')->name('store'); // Crear
            Route::put('/{id}', 'update')->name('update'); // Editar
            Route::delete('/{id}', 'destroy')->name('destroy'); // Eliminar
            Route::delete('/limpiar-todo', 'clear')->name('clear'); // Truncate
    
            // Selects dinámicos
            Route::get('/categorias-list', 'getCategorias')->name('categorias');
            Route::get('/subcategorias-list', 'getSubcategorias')->name('subcategorias');
        }
        );

        // --- CORREOS CC ---
        // 1. Ruta API específica (La que faltaba)
        // Apuntamos al método index, asumiendo que detecta si es AJAX para devolver JSON
        Route::get('correos-cc/api', [LogisticaCorreoCCController::class , 'index'])
            ->name('correos-cc.api');

        // 2. CRUD Estándar
        Route::resource('correos-cc', LogisticaCorreoCCController::class);

        // Post-Operaciones
        Route::controller(PostOperacionController::class)->prefix('post-operaciones')->name('post-operaciones.')->group(function () {
            Route::get('globales', 'indexGlobales')->name('globales');
            Route::post('globales', 'storeGlobal')->name('store-global');
            Route::put('globales/{id}', 'updateGlobal')->name('update-global');
            Route::delete('globales/{id}', 'destroyGlobal')->name('destroy-global');
            Route::get('operaciones/{id}', 'getByOperacion')->name('get-by-operacion');
            Route::put('operaciones/{id}/actualizar', 'bulkUpdate')->name('bulk-update');
        }
        );

        // Reportes
        Route::controller(ReporteController::class)->prefix('reportes')->name('reportes.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/export', 'exportCSV')->name('export');
            Route::get('/exportar-matriz', 'exportMatrizSeguimiento')->name('export-matriz');
            Route::get('/export-excel', 'exportExcelProfesional')->name('export-excel');
            Route::get('/resumen/exportar', 'exportResumenEjecutivo')->name('resumen.export');
            Route::get('/pedimentos/exportar', [\App\Http\Controllers\Logistica\PedimentoController::class , 'exportCSV'])
                ->name('pedimentos.export');
            Route::post('/enviar-correo', 'enviarCorreo')->name('enviar-correo');
        }
        );

        // --- VISTA GENERAL DE CATÁLOGOS (La que faltaba) ---
        Route::get('/catalogos', [\App\Http\Controllers\Logistica\CatalogosController::class , 'index'])
            ->name('catalogos');

        // --- CAMPOS PERSONALIZADOS (Configuración) ---
        Route::controller(\App\Http\Controllers\Logistica\CampoPersonalizadoController::class)
            ->prefix('campos-personalizados')
            ->name('campos-personalizados.')
            ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::put('/{id}', 'update')->name('update');
            Route::delete('/{id}', 'destroy')->name('destroy');
            Route::put('/{id}/toggle-activo', 'toggleActivo')->name('toggle-activo');

            // ESTA ES LA RUTA CRÍTICA QUE FALTA O FALLA:
            Route::post('/valor', 'storeValor')->name('store-valor');

            Route::get('/activos', 'getCamposActivos');
            Route::get('/operacion/{id}/valores', 'getValoresOperacion');
        }
        );

        // --- GESTIÓN DE EQUIPO (SUPERVISORES) ---
        Route::controller(\App\Http\Controllers\Logistica\EquipoController::class)
            ->prefix('equipo')->name('equipo.')
            ->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/', 'store')->name('store');
            Route::delete('/{id}', 'destroy')->name('destroy');
        }
        );
    });


// 5. MÓDULO RECURSOS HUMANOS
Route::middleware(['auth', 'area.rh'])->group(function () {
    Route::get('/recursos-humanos', function () {
            return view('Recursos_Humanos.index');
        }
        )->name('recursos-humanos.index');

    // Inventario IT (activos, solo lectura para RH)
    Route::prefix('recursos-humanos/inventario')->name('rh.inventario.')->group(function () {
        Route::get('/fotos/{id}', [ActivosApiController::class, 'photo'])->name('photo');
        Route::get('/',          [ActivosController::class, 'index'])->name('index');
        Route::get('/{uuid}',    [ActivosController::class, 'show'])->name('show');
    });

        // Reloj Checador
        Route::controller(RelojChecadorImportController::class)->prefix('recursos-humanos/reloj')->name('rh.reloj.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/start', 'start')->name('start');
            Route::get('/progreso/{key}', 'progress')->name('import.progress');
            Route::post('/store', 'store')->name('store');
            Route::put('/update/{id}', 'update')->name('update');
            Route::post('/store-manual', 'storeManual')->name('storeManual');
            Route::delete('/revertir/{id}', 'revertir')->name('revertir');
            Route::delete('/revertir-rango', 'revertirRango')->name('revertirRango');
            Route::delete('/clear', 'clear')->name('clear');
            Route::delete('/clear-rango', 'clearRango')->name('clearRango');
            Route::post('/aviso', 'enviarAviso')->name('aviso');
        }
        );

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
            Route::post('/{id}/baja', 'darDeBaja')->name('baja');
            Route::post('/{id}/reactivar', 'reactivar')->name('reactivar');
        }
        );

        // Capacitación (Gestión)
        Route::prefix('recursos-humanos/capacitacion')->name('rh.capacitacion.')->controller(CapacitacionController::class)->group(function () {
            Route::get('/gestion', 'manage')->name('manage');
            Route::post('/subir', 'store')->name('store');
            Route::get('/{id}/editar', 'edit')->name('edit');
            Route::put('/{id}', 'update')->name('update');
            Route::delete('/{id}', 'destroy')->name('destroy');
            Route::delete('/adjunto/{id}', 'destroyAdjunto')->name('destroyAdjunto');
        }
        );

        // Recordatorios
        Route::prefix('recursos-humanos/recordatorios')->name('rh.recordatorios.')->controller(\App\Http\Controllers\RH\RecordatorioController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/calendario', 'calendario')->name('calendario');
            Route::get('/{id}', 'show')->name('show');
            Route::post('/{id}/marcar-leido', 'marcarLeido')->name('marcar-leido');
            Route::post('/marcar-todos', 'marcarTodosLeidos')->name('marcar-todos');
            Route::delete('/{id}', 'destruir')->name('destruir');
            Route::post('/generar', 'generarManual')->name('generar');
            Route::post('/evento-manual', 'crearEventoManual')->name('crear-manual');
        }
        );






    
});


// 7. MÓDULO LEGAL
Route::middleware(['auth', 'verified', 'area.legal'])->prefix('legal')->name('legal.')->group(function () {
    Route::get('/', [LegalController::class, 'dashboard'])->name('dashboard');

    // Matriz de Consulta
    Route::get('/matriz', [MatrizConsultaController::class, 'index'])->name('matriz.index');
    Route::post('/matriz', [MatrizConsultaController::class, 'store'])->name('matriz.store');
    Route::get('/matriz/{id}', [MatrizConsultaController::class, 'show'])->name('matriz.show');
    Route::put('/matriz/{id}', [MatrizConsultaController::class, 'update'])->name('matriz.update');
    Route::delete('/matriz/{id}', [MatrizConsultaController::class, 'destroy'])->name('matriz.destroy');
    Route::delete('/matriz/archivo/{id}', [MatrizConsultaController::class, 'destroyArchivo'])->name('matriz.archivo.destroy');
    Route::get('/matriz/archivo/{id}/download', [MatrizConsultaController::class, 'downloadArchivo'])->name('matriz.archivo.download');

    // Categorías
    Route::get('/categorias', [CategoriaLegalController::class, 'index'])->name('categorias.index');
    Route::post('/categorias', [CategoriaLegalController::class, 'store'])->name('categorias.store');
    Route::delete('/categorias/{id}', [CategoriaLegalController::class, 'destroy'])->name('categorias.destroy');

    // Programas y Páginas
    Route::get('/programas', [PaginaLegalController::class, 'index'])->name('programas.index');
    Route::post('/programas', [PaginaLegalController::class, 'store'])->name('programas.store');
    Route::put('/programas/{id}', [PaginaLegalController::class, 'update'])->name('programas.update');
    Route::delete('/programas/{id}', [PaginaLegalController::class, 'destroy'])->name('programas.destroy');

    // Digitalización de documentos (herramientas PDF)
    Route::get('/digitalizacion', [DigitalizacionController::class, 'index'])->name('digitalizacion.index');
    Route::post('/digitalizacion/convertir', [DigitalizacionController::class, 'convert'])->name('digitalizacion.convert');
    Route::post('/digitalizacion/validar', [DigitalizacionController::class, 'validatePdf'])->name('digitalizacion.validate');
    Route::post('/digitalizacion/comprimir', [DigitalizacionController::class, 'compress'])->name('digitalizacion.compress');
    Route::post('/digitalizacion/combinar', [DigitalizacionController::class, 'merge'])->name('digitalizacion.merge');
    Route::post('/digitalizacion/extraer', [DigitalizacionController::class, 'extractImages'])->name('digitalizacion.extract');
});


// 6. MÓDULO SISTEMAS (ADMIN)
Route::middleware(['auth', 'verified', 'sistemas_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class , 'dashboard'])->name('dashboard');

    // Tickets
    Route::controller(TicketController::class)->group(function () {
            Route::get('/tickets', 'index')->name('tickets.index');
            Route::get('/tickets/{ticket}', 'show')->name('tickets.show');
            Route::patch('/tickets/{ticket}', 'update')->name('tickets.update');
            Route::post('/tickets/{ticket}/change-maintenance-date', 'changeMaintenanceDate')->name('tickets.change-maintenance-date');
            Route::get('/maintenance-slots/available', 'getAvailableMaintenanceSlots')->name('maintenance-slots.available');
        }
        );

        // Mantenimiento
        Route::controller(MaintenanceController::class)->name('maintenance.')->group(function () {
            Route::get('/maintenance', 'adminIndex')->name('index');
            Route::get('/maintenance/computers', function () {
                    return redirect()->route('admin.maintenance.index');
                }
                )->name('computers.index');

                Route::post('/maintenance/computers', 'storeComputer')->name('computers.store');
                Route::get('/maintenance/computers/{computerProfile}', 'showComputer')->name('computers.show');
                Route::get('/maintenance/computers/{computerProfile}/edit', 'editComputer')->name('computers.edit');
                Route::put('/maintenance/computers/{computerProfile}', 'updateComputer')->name('computers.update');
                Route::delete('/maintenance/computers/{computerProfile}', 'destroyComputer')->name('computers.destroy');

                // API para agenda de mantenimientos
                Route::get('/maintenance/week-maintenances', 'getWeekMaintenances')->name('week-maintenances');
                Route::get('/maintenance/calendar-data', 'getCalendarData')->name('calendar-data');

                // Bloqueo de horarios
                Route::post('/maintenance/block-slot', 'blockSlot')->name('block-slot');
                Route::delete('/maintenance/unblock-slot/{block}', 'unblockSlot')->name('unblock-slot');
            }
            );

            // Usuarios
            Route::controller(UsersController::class)->prefix('users')->name('users.')->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{user}', 'show')->name('show');
            Route::get('/{user}/edit', 'edit')->name('edit');
            Route::put('/{user}', 'update')->name('update');
            Route::delete('/{user}', 'destroy')->name('destroy');
            Route::post('/{user}/approve', 'approve')->name('approve');
            Route::post('/{user}/reject', 'reject')->name('reject');
            Route::post('/{user}/baja', 'darDeBaja')->name('baja');
            Route::post('/{user}/reactivar', 'reactivar')->name('reactivar');
            Route::delete('/{user}/rejection', 'destroyRejected')->name('rejections.destroy');
            Route::delete('/blocked-emails/{blockedEmail}', 'destroyBlockedEmail')->name('blocked-emails.destroy');
        }
        );

        // Alias: admin.users apunta a admin.users.index
        Route::get('/users-list', [UsersController::class , 'index'])->name('users');

        // Activos IT
        Route::controller(ActivosController::class)->prefix('activos')->name('activos.')->group(function () {
            Route::get('/',                  'index')->name('index');
            Route::get('/escaner-qr',        'qrScanner')->name('qr-scanner');
            Route::get('/crear',             'create')->name('create');
            Route::post('/',                 'store')->name('store');
            Route::get('/{uuid}',            'show')->name('show');
            Route::get('/{uuid}/editar',     'edit')->name('edit');
            Route::put('/{uuid}',            'update')->name('update');
            Route::post('/{uuid}/asignar',   'assign')->name('assign');
            Route::post('/{uuid}/devolver',  'returnDevice')->name('return');
        });

        // Contraseñas y Equipos IT
        Route::prefix('activos-api')->name('activos.')->group(function () {
            Route::get('/usuario/{userId}/equipo',   [ActivosApiController::class, 'devicesByUser'])->name('devices-by-user');
            Route::get('/equipos-disponibles',        [ActivosApiController::class, 'availableDevices'])->name('available-devices');
            Route::get('/fotos/{id}',                 [ActivosApiController::class, 'photo'])->name('photo');
            Route::get('/dispositivo/{uuid}',         [ActivosApiController::class, 'lookupByUuid'])->name('lookup');
            Route::post('/qr-asignar/{uuid}',         [ActivosApiController::class, 'assignViaQr'])->name('qr-assign');
            Route::post('/qr-devolver/{uuid}',        [ActivosApiController::class, 'returnViaQr'])->name('qr-return');
            Route::post('/qr-danado/{uuid}',          [ActivosApiController::class, 'markBrokenViaQr'])->name('qr-broken');
        });
        Route::resource('credenciales', CredencialEquipoController::class)
            ->parameters(['credenciales' => 'credencial']);

        // Equipos secundarios (rutas explícitas antes del resource para evitar conflictos)
        Route::post('credenciales/{credencial}/secundarios', [CredencialEquipoController::class, 'storeSecundario'])
            ->name('credenciales.secundarios.store');
        Route::delete('credenciales/{credencial}/secundarios/{secundario}', [CredencialEquipoController::class, 'destroySecundario'])
            ->name('credenciales.secundarios.destroy');

        // Carta Responsiva
        Route::get('credenciales/exportar-excel', [CredencialEquipoController::class, 'exportExcel'])
            ->name('credenciales.export-excel');
        Route::get('credenciales/carta-responsiva/{user}', [CredencialEquipoController::class, 'cartaResponsiva'])
            ->name('credenciales.carta-responsiva');
        Route::post('credenciales/carta-responsiva/{user}/guardar', [CredencialEquipoController::class, 'guardarCartaResponsiva'])
            ->name('credenciales.carta-responsiva.guardar');
    });

// API Notificaciones Admin
Route::middleware(['auth', 'admin'])->prefix('api/notifications')->controller(NotificationController::class)->group(function () {
    Route::get('/count', 'getUnreadCount');
    Route::get('/unread', 'getUnreadTickets');
    Route::post('/{ticket}/read', 'markAsRead');
    Route::post('/mark-all-read', 'markAllAsRead');
    Route::get('/stats', 'getStats');
});

require __DIR__ . '/auth.php';
