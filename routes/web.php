<?php

use App\Http\Controllers\ActivityController;
// --- Controllers de Autenticación y Perfil ---
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\EvaluacionController;
use App\Http\Controllers\Legal\CategoriaLegalController;
// --- Controllers de Sistemas IT ---
use App\Http\Controllers\Legal\DigitalizacionController;
use App\Http\Controllers\Legal\LegalController;
use App\Http\Controllers\Legal\MatrizConsultaController;
use App\Http\Controllers\Legal\PaginaLegalController;
use App\Http\Controllers\Logistica\ClienteController;
use App\Http\Controllers\Logistica\MatrizSeguimientoController;
// --- Controllers de Legal ---
use App\Http\Controllers\Logistica\MatrizApoyoController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RH\CapacitacionController;
use App\Http\Controllers\RH\ExpedienteController;
use App\Http\Controllers\RH\RelojChecadorImportController;
// --- Controllers de Logística ---
use App\Http\Controllers\Sistemas_IT\ActivosApiController;
use App\Http\Controllers\Sistemas_IT\ActivosController;
use App\Http\Controllers\Sistemas_IT\AdminController;
use App\Http\Controllers\Sistemas_IT\CredencialEquipoController;
use App\Http\Controllers\Sistemas_IT\MaintenanceController;
use App\Http\Controllers\Sistemas_IT\NotificationController;
use App\Http\Controllers\Sistemas_IT\TicketController; // <--- AGREGADO
use App\Http\Controllers\Users\UsersController; // <--- AGREGADO
use App\Http\Controllers\Administracion\AdministracionController;
use App\Http\Controllers\Administracion\ClienteAdminController;
use App\Http\Controllers\Administracion\PerfilClienteController;
use App\Http\Controllers\Anexo24\Anexo24Controller;
use App\Http\Controllers\PostOperaciones\PostOperacionesPanelController;
use App\Http\Controllers\Auditoria\AuditoriaController;
use App\Http\Controllers\Shared\ClientesReadonlyController;
use Illuminate\Support\Facades\Route;

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
    // Digitalización de documentos (herramientas PDF) — disponible para todos
    Route::get('/digitalizacion', [DigitalizacionController::class, 'index'])->name('digitalizacion.index');
    Route::post('/digitalizacion/convertir', [DigitalizacionController::class, 'convert'])->name('digitalizacion.convert');
    Route::post('/digitalizacion/validar', [DigitalizacionController::class, 'validatePdf'])->name('digitalizacion.validate');
    Route::post('/digitalizacion/comprimir', [DigitalizacionController::class, 'compress'])->name('digitalizacion.compress');
    Route::post('/digitalizacion/combinar', [DigitalizacionController::class, 'merge'])->name('digitalizacion.merge');
    Route::post('/digitalizacion/extraer', [DigitalizacionController::class, 'extractImages'])->name('digitalizacion.extract');

    // Perfil
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Marcar aviso de asistencia como leído (cualquier empleado)
    Route::post('/aviso-leido/{id}', function (\Illuminate\Http\Request $request, $id) {
        $aviso = \App\Models\AvisoAsistencia::findOrFail($id);
        // Solo el destinatario puede marcarlo
        if ($aviso->empleado_id !== \Auth::user()->empleado?->id) {
            abort(403);
        }
        $aviso->update(['leido' => true, 'leido_at' => now()]);

        return redirect()->route('welcome');
    })->name('rh.reloj.aviso_leido');

    // Actividades
    Route::put('/activities/{activity}/validate', [App\Http\Controllers\ActivityController::class, 'validateCompletion'])->name('activities.validate');
    Route::get('/activities/client-report', [App\Http\Controllers\ActivityController::class, 'generateClientReport'])->name('activities.client_report');
    Route::get('/activities/export-excel', [App\Http\Controllers\ActivityController::class, 'exportExcel'])->name('activities.export_excel');
    Route::post('/activities/import', [App\Http\Controllers\ActivityController::class, 'import'])->name('activities.import');
    Route::post('/activities/import-preview', [App\Http\Controllers\ActivityController::class, 'previewImport'])->name('activities.import_preview');
    Route::get('/activities/import-template', [App\Http\Controllers\ActivityController::class, 'downloadImportTemplate'])->name('activities.import_template');
    // Rutas con segmentos fijos deben ir ANTES del resource para evitar que {activity} las intercepte
    Route::delete('/activities/bulk-destroy', [ActivityController::class, 'bulkDestroy'])->name('activities.bulk_destroy');
    Route::get('/activities/planeacion-ventanas', [ActivityController::class, 'getPlaneacionVentanas'])->name('activities.planeacion.ventanas');
    Route::post('/activities/planeacion-ventanas', [ActivityController::class, 'savePlaneacionVentana'])->name('activities.planeacion.save');
    Route::delete('/activities/planeacion-ventanas/{id}', [ActivityController::class, 'deletePlaneacionVentana'])->name('activities.planeacion.delete');
    Route::resource('activities', ActivityController::class);
    Route::post('/activities/batch', [ActivityController::class, 'storeBatch'])->name('activities.storeBatch');
    Route::put('/activities/{id}/approve', [ActivityController::class, 'approve'])->name('activities.approve');
    Route::put('/activities/{id}/reject', [ActivityController::class, 'reject'])->name('activities.reject');
    Route::put('/activities/{id}/start', [ActivityController::class, 'start'])->name('activities.start');

    // Proyectos
    Route::get('/proyectos', [App\Http\Controllers\ProyectoController::class, 'index'])->name('proyectos.index');
    Route::post('/proyectos', [App\Http\Controllers\ProyectoController::class, 'store'])->name('proyectos.store');
    Route::get('/proyectos/{id}', [App\Http\Controllers\ProyectoController::class, 'show'])->name('proyectos.show');
    Route::get('/proyectos/{id}/edit', [App\Http\Controllers\ProyectoController::class, 'edit'])->name('proyectos.edit');
    Route::put('/proyectos/{id}', [App\Http\Controllers\ProyectoController::class, 'update'])->name('proyectos.update');
    Route::delete('/proyectos/{id}', [App\Http\Controllers\ProyectoController::class, 'destroy'])->name('proyectos.destroy');
    Route::post('/proyectos/{id}/restore', [App\Http\Controllers\ProyectoController::class, 'restore'])->name('proyectos.restore');
    Route::delete('/proyectos/{id}/force', [App\Http\Controllers\ProyectoController::class, 'forceDelete'])->name('proyectos.forceDelete');
    Route::post('/proyectos/{id}/usuarios', [App\Http\Controllers\ProyectoController::class, 'asignarUsuarios'])->name('proyectos.asignarUsuarios');
    Route::delete('/proyectos/{id}/usuarios/{userId}', [App\Http\Controllers\ProyectoController::class, 'quitarUsuario'])->name('proyectos.quitarUsuario');
    Route::post('/proyectos/{id}/responsables-ti', [App\Http\Controllers\ProyectoController::class, 'asignarResponsablesTi'])->name('proyectos.asignarResponsablesTi');
    Route::delete('/proyectos/{id}/responsables-ti/{userId}', [App\Http\Controllers\ProyectoController::class, 'quitarResponsableTi'])->name('proyectos.quitarResponsableTi');
    Route::get('/proyectos/usuarios/lista', [App\Http\Controllers\ProyectoController::class, 'listaUsuarios'])->name('proyectos.listaUsuarios');
    Route::post('/proyectos/{id}/finalizar', [App\Http\Controllers\ProyectoController::class, 'finalizar'])->name('proyectos.finalizar');
    Route::get('/proyectos/{id}/reporte', [App\Http\Controllers\ProyectoController::class, 'reporte'])->name('proyectos.reporte');
    Route::get('/proyectos/{id}/reporte/pdf', [App\Http\Controllers\ProyectoController::class, 'reportePdf'])->name('proyectos.reporte.pdf');

    // Actividades del Proyecto (vista dedicada)
    Route::get('/proyectos/{proyecto}/actividades', [App\Http\Controllers\ProyectoController::class, 'actividades'])->name('proyectos.actividades');
    Route::post('/proyectos/{proyecto}/actividades', [App\Http\Controllers\ProyectoController::class, 'guardarActividad'])->name('proyectos.actividades.store');
    Route::get('/proyectos/{proyecto}/actividades/{actividad}/edit', [App\Http\Controllers\ProyectoController::class, 'editarActividad'])->name('proyectos.actividades.edit');
    Route::put('/proyectos/{proyecto}/actividades/{actividad}', [App\Http\Controllers\ProyectoController::class, 'actualizarActividad'])->name('proyectos.actividades.update');
    Route::delete('/proyectos/{proyecto}/actividades/{actividad}', [App\Http\Controllers\ProyectoController::class, 'eliminarActividad'])->name('proyectos.actividades.destroy');

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
    Route::get('/maintenance/availability', [MaintenanceController::class, 'availability'])->name('maintenance.availability');
    Route::get('/maintenance/slots', [MaintenanceController::class, 'slots'])->name('maintenance.slots');
    Route::get('/maintenance/check-availability', [MaintenanceController::class, 'checkAvailability'])->name('maintenance.check-availability');

    // Capacitación (Usuario)
    Route::prefix('capacitacion')->name('capacitacion.')->group(function () {
        Route::get('/', [CapacitacionController::class, 'index'])->name('index');
        Route::get('/ver/{id}', [CapacitacionController::class, 'show'])->name('show');
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
    // Administrar Clientes — rutas estáticas primero para no colisionar con el resource
    Route::post('clientes/importar', [ClienteController::class, 'import'])->name('clientes.import');
    Route::post('clientes/asignar-ejecutivo', [ClienteController::class, 'asignarEjecutivo'])->name('clientes.asignar-ejecutivo');
    Route::delete('clientes/all/delete', [ClienteController::class, 'deleteAll'])->middleware('admin')->name('clientes.delete-all');
    Route::resource('clientes', ClienteController::class)->only(['index', 'store', 'update', 'destroy']);

    // Cuestionario clientes (solo lectura) — URL distinta para no colisionar con el resource
    Route::get('/clientes/perfil', [ClientesReadonlyController::class, 'index'])->name('clientes.perfil');

    // Matriz de Seguimiento
    Route::get('/matriz-seguimiento', [MatrizSeguimientoController::class, 'index'])->name('matriz-seguimiento');
    Route::post('/matriz-seguimiento', [MatrizSeguimientoController::class, 'store'])->name('matriz-seguimiento.store');
    Route::put('/matriz-seguimiento/{seguimiento}', [MatrizSeguimientoController::class, 'update'])->name('matriz-seguimiento.update');
    Route::patch('/matriz-seguimiento/{seguimiento}/completar', [MatrizSeguimientoController::class, 'completar'])->name('matriz-seguimiento.completar');
    Route::delete('/matriz-seguimiento/{seguimiento}', [MatrizSeguimientoController::class, 'destroy'])->name('matriz-seguimiento.destroy');
    Route::get('/matriz-seguimiento/{seguimiento}/comentarios', [MatrizSeguimientoController::class, 'getComentarios'])->name('matriz-seguimiento.comentarios');
    Route::post('/matriz-seguimiento/{seguimiento}/comentarios', [MatrizSeguimientoController::class, 'storeComentario'])->name('matriz-seguimiento.comentarios.store');

    // Reportes
    Route::get('/reportes', [MatrizSeguimientoController::class, 'reportes'])->name('reportes');

    // Matriz de Apoyo Operativo
    Route::get('/matriz-apoyo', [MatrizApoyoController::class, 'index'])->name('matriz-apoyo');
    Route::get('/matriz-apoyo/calificaciones', [MatrizApoyoController::class, 'calificaciones'])->name('matriz-apoyo.calificaciones');
    Route::post('/matriz-apoyo/agentes', [MatrizApoyoController::class, 'storeAgente'])->name('matriz-apoyo.agentes.store');
    Route::put('/matriz-apoyo/agentes/{agente}', [MatrizApoyoController::class, 'updateAgente'])->name('matriz-apoyo.agentes.update');
    Route::delete('/matriz-apoyo/agentes/{agente}', [MatrizApoyoController::class, 'destroyAgente'])->name('matriz-apoyo.agentes.destroy');
    Route::post('/matriz-apoyo/forwarders', [MatrizApoyoController::class, 'storeForwarder'])->name('matriz-apoyo.forwarders.store');
    Route::put('/matriz-apoyo/forwarders/{forwarder}', [MatrizApoyoController::class, 'updateForwarder'])->name('matriz-apoyo.forwarders.update');
    Route::delete('/matriz-apoyo/forwarders/{forwarder}', [MatrizApoyoController::class, 'destroyForwarder'])->name('matriz-apoyo.forwarders.destroy');
    Route::post('/matriz-apoyo/navieras', [MatrizApoyoController::class, 'storeNaviera'])->name('matriz-apoyo.navieras.store');
    Route::put('/matriz-apoyo/navieras/{naviera}', [MatrizApoyoController::class, 'updateNaviera'])->name('matriz-apoyo.navieras.update');
    Route::delete('/matriz-apoyo/navieras/{naviera}', [MatrizApoyoController::class, 'destroyNaviera'])->name('matriz-apoyo.navieras.destroy');
    Route::post('/matriz-apoyo/arrastres', [MatrizApoyoController::class, 'storeArrastre'])->name('matriz-apoyo.arrastres.store');
    Route::put('/matriz-apoyo/arrastres/{arrastre}', [MatrizApoyoController::class, 'updateArrastre'])->name('matriz-apoyo.arrastres.update');
    Route::delete('/matriz-apoyo/arrastres/{arrastre}', [MatrizApoyoController::class, 'destroyArrastre'])->name('matriz-apoyo.arrastres.destroy');
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
        Route::get('/', [ActivosController::class, 'index'])->name('index');
        Route::get('/{uuid}', [ActivosController::class, 'show'])->name('show');
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
        Route::get('/equipo', 'equipo')->name('equipo');
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

    // Días Festivos
    Route::prefix('recursos-humanos/dias-festivos')->name('rh.dias-festivos.')->controller(\App\Http\Controllers\RH\DiaFestivoController::class)->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/crear', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{diaFestivo}/editar', 'edit')->name('edit');
        Route::put('/{diaFestivo}', 'update')->name('update');
        Route::delete('/{diaFestivo}', 'destroy')->name('destroy');
        Route::patch('/{diaFestivo}/toggle', 'toggle')->name('toggle');
        Route::post('/{diaFestivo}/enviar-notificacion', 'enviarNotificacion')->name('enviar-notificacion');
        Route::post('/{diaFestivo}/crear-recordatorio', 'crearRecordatorio')->name('crear-recordatorio');
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

    // Cuestionario clientes (solo lectura)
    Route::get('/clientes', [ClientesReadonlyController::class, 'index'])->name('clientes');
});

// 8. MÓDULO ANEXO 24
Route::middleware(['auth', 'verified', 'area.anexo24'])->prefix('anexo24')->name('anexo24.')->group(function () {
    Route::get('/', [Anexo24Controller::class, 'dashboard'])->name('dashboard');
    Route::get('/clientes', [ClientesReadonlyController::class, 'index'])->name('clientes');
});

// 9. MÓDULO POST-OPERACIONES
Route::middleware(['auth', 'verified', 'area.postoperaciones'])->prefix('postoperaciones')->name('postoperaciones.')->group(function () {
    Route::get('/', [PostOperacionesPanelController::class, 'dashboard'])->name('dashboard');
    Route::get('/clientes', [ClientesReadonlyController::class, 'index'])->name('clientes');
});

// 10. MÓDULO AUDITORÍA
Route::middleware(['auth', 'verified', 'area.auditoria'])->prefix('auditoria')->name('auditoria.')->group(function () {
    Route::get('/', [AuditoriaController::class, 'dashboard'])->name('dashboard');
    Route::get('/clientes', [ClientesReadonlyController::class, 'index'])->name('clientes');
});

// 6. MÓDULO ADMINISTRACIÓN
Route::middleware(['auth', 'verified', 'admin'])->prefix('administracion')->name('administracion.')->group(function () {
    Route::get('/', [AdministracionController::class, 'dashboard'])->name('dashboard');

    // Clientes de Administración
    Route::prefix('clientes')->name('clientes.')->group(function () {
        Route::get('/', [ClienteAdminController::class, 'index'])->name('index');
        Route::post('/', [ClienteAdminController::class, 'store'])->name('store');
        Route::put('/{cliente}', [ClienteAdminController::class, 'update'])->name('update');
        Route::delete('/{cliente}', [ClienteAdminController::class, 'destroy'])->name('destroy');

        // Perfil / cuestionario
        Route::get('/{cliente}/perfil', [PerfilClienteController::class, 'show'])->name('perfil');
        Route::post('/{cliente}/perfil', [PerfilClienteController::class, 'upsert'])->name('perfil.guardar');
    });
});

// 7. MÓDULO SISTEMAS (ADMIN)
Route::middleware(['auth', 'verified', 'sistemas_admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');

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
        Route::patch('/maintenance/computers/{computerProfile}/equipo', 'setEquipoAsignado')->name('computers.setEquipo');
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
    Route::get('/users-list', [UsersController::class, 'index'])->name('users');

    // Activos IT
    Route::controller(ActivosController::class)->prefix('activos')->name('activos.')->group(function () {
        Route::get('/', 'index')->name('index');
        Route::get('/escaner-qr', 'qrScanner')->name('qr-scanner');
        Route::get('/crear', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('/{uuid}', 'show')->name('show');
        Route::get('/{uuid}/editar', 'edit')->name('edit');
        Route::put('/{uuid}', 'update')->name('update');
        Route::delete('/{uuid}', 'destroy')->name('destroy');
        Route::post('/{uuid}/asignar', 'assign')->name('assign');
        Route::post('/{uuid}/devolver', 'returnDevice')->name('return');
    });

    // Contraseñas y Equipos IT
    Route::prefix('activos-api')->name('activos.')->group(function () {
        Route::get('/usuario/{userId}/equipo', [ActivosApiController::class, 'devicesByUser'])->name('devices-by-user');
        Route::get('/equipos-disponibles', [ActivosApiController::class, 'availableDevices'])->name('available-devices');
        Route::get('/fotos/{id}', [ActivosApiController::class, 'photo'])->name('photo');
        Route::delete('/fotos/{id}', [ActivosApiController::class, 'deletePhoto'])->name('photo.delete');
        Route::get('/dispositivo/{uuid}', [ActivosApiController::class, 'lookupByUuid'])->name('lookup');
        Route::post('/qr-asignar/{uuid}', [ActivosApiController::class, 'assignViaQr'])->name('qr-assign');
        Route::post('/qr-devolver/{uuid}', [ActivosApiController::class, 'returnViaQr'])->name('qr-return');
        Route::post('/qr-danado/{uuid}', [ActivosApiController::class, 'markBrokenViaQr'])->name('qr-broken');
    });
    // Rutas estáticas ANTES del resource para evitar que {credencial} las capture
    Route::get('credenciales/exportar-excel', [CredencialEquipoController::class, 'exportExcel'])
        ->name('credenciales.export-excel');
    Route::get('credenciales/carta-responsiva/{user}', [CredencialEquipoController::class, 'cartaResponsiva'])
        ->name('credenciales.carta-responsiva');
    Route::post('credenciales/carta-responsiva/{user}/guardar', [CredencialEquipoController::class, 'guardarCartaResponsiva'])
        ->name('credenciales.carta-responsiva.guardar');

    Route::resource('credenciales', CredencialEquipoController::class)
        ->parameters(['credenciales' => 'credencial']);

    // Equipos secundarios
    Route::post('credenciales/{credencial}/secundarios', [CredencialEquipoController::class, 'storeSecundario'])
        ->name('credenciales.secundarios.store');
    Route::delete('credenciales/{credencial}/secundarios/{secundario}', [CredencialEquipoController::class, 'destroySecundario'])
        ->name('credenciales.secundarios.destroy');
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
