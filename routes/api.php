<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Aquí se registran todas las rutas de la API del sistema.
| Todas las rutas tienen el prefijo /api automáticamente.
|
| Documentación completa: docs/API_DOCUMENTATION.md
|
*/

// ============================================================================
// RUTAS DE CONSULTA EXTERNA (Solo requieren X-API-Key header)
// ============================================================================

Route::prefix('v1')->middleware('api.key')->group(function () {

    // Obtener usuarios activos del sistema
    Route::get('/users', [UserController::class, 'index'])
        ->name('api.users.index');
});

// ============================================================================
// RUTAS PÚBLICAS (Sin autenticación)
// ============================================================================

Route::prefix('v1')->group(function () {
    
    // --- Autenticación ---
    Route::prefix('auth')->group(function () {
        // Login desde proyecto externo
        Route::post('/login', [AuthController::class, 'login'])
            ->name('api.auth.login');
        
        // Validar token existente
        Route::post('/validate-token', [AuthController::class, 'validateToken'])
            ->name('api.auth.validate-token');
    });
});

// ============================================================================
// RUTAS PROTEGIDAS (Requieren autenticación con token)
// ============================================================================

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    
    // --- Autenticación ---
    Route::prefix('auth')->group(function () {
        // Cerrar sesión (revocar token)
        Route::post('/logout', [AuthController::class, 'logout'])
            ->name('api.auth.logout');
        
        // Obtener información del usuario autenticado
        Route::get('/me', [AuthController::class, 'me'])
            ->name('api.auth.me');
        
        // Renovar token
        Route::post('/refresh', [AuthController::class, 'refresh'])
            ->name('api.auth.refresh');
    });
    


    // --- Aquí puedes agregar más rutas protegidas ---
    // Ejemplo:
    // Route::apiResource('usuarios', UserApiController::class);
    // Route::get('/empleados', [EmpleadoApiController::class, 'index']);
});
