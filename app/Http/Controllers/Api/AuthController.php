<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BlockedEmail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

/**
 * Controller para autenticación API
 * 
 * Maneja el inicio de sesión desde proyectos externos
 * utilizando tokens de Sanctum.
 */
class AuthController extends Controller
{
    /**
     * Iniciar sesión y obtener token de acceso
     * 
     * @param Request $request
     * @return JsonResponse
     * 
     * @bodyParam email string required Email del usuario. Example: usuario@ejemplo.com
     * @bodyParam password string required Contraseña del usuario. Example: password123
     * @bodyParam device_name string Nombre del dispositivo/aplicación. Example: app-movil
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Inicio de sesión exitoso",
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "Usuario Ejemplo",
     *       "email": "usuario@ejemplo.com",
     *       "role": "user",
     *       "area": "logistica"
     *     },
     *     "token": "1|abc123...",
     *     "token_type": "Bearer",
     *     "expires_at": null
     *   }
     * }
     * 
     * @response 401 {
     *   "success": false,
     *   "message": "Credenciales incorrectas",
     *   "errors": {}
     * }
     */
    public function login(Request $request): JsonResponse
    {
        // Validar datos de entrada
        $validator = Validator::make($request->all(), [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['sometimes', 'string', 'max:255'],
        ], [
            'email.required' => 'El correo electrónico es requerido',
            'email.email' => 'El correo electrónico debe ser válido',
            'password.required' => 'La contraseña es requerida',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        $email = $request->input('email');
        $password = $request->input('password');
        $deviceName = $request->input('device_name', 'api-client');

        // Verificar si el email está bloqueado
        if (BlockedEmail::where('email', $email)->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Este correo ha sido bloqueado',
                'errors' => ['email' => ['Este correo ha sido bloqueado']]
            ], 403);
        }

        // Buscar usuario
        $user = User::where('email', $email)->first();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas',
                'errors' => ['email' => ['No se encontró una cuenta con este correo']]
            ], 401);
        }

        // Verificar estado del usuario
        if ($user->status === User::STATUS_PENDING) {
            return response()->json([
                'success' => false,
                'message' => 'Tu cuenta está pendiente de aprobación',
                'errors' => ['account' => ['Tu cuenta está en revisión por un administrador']]
            ], 403);
        }

        if ($user->status === User::STATUS_REJECTED) {
            return response()->json([
                'success' => false,
                'message' => 'Tu solicitud de cuenta fue rechazada',
                'errors' => ['account' => ['Tu solicitud fue rechazada']]
            ], 403);
        }

        // Verificar contraseña
        if (!Hash::check($password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales incorrectas',
                'errors' => ['password' => ['La contraseña es incorrecta']]
            ], 401);
        }

        // Revocar tokens anteriores del mismo dispositivo (opcional)
        // $user->tokens()->where('name', $deviceName)->delete();

        // Crear nuevo token
        $token = $user->createToken($deviceName);

        // Obtener área del empleado relacionado
        $area = optional($user->empleado)->area;

        return response()->json([
            'success' => true,
            'message' => 'Inicio de sesión exitoso',
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status,
                    'area' => $area,
                ],
                'token' => $token->plainTextToken,
                'token_type' => 'Bearer',
                'expires_at' => null, // Sanctum no expira tokens por defecto
            ]
        ], 200);
    }

    /**
     * Cerrar sesión y revocar token actual
     * 
     * @param Request $request
     * @return JsonResponse
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Sesión cerrada exitosamente"
     * }
     */
    public function logout(Request $request): JsonResponse
    {
        // Revocar token actual
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada exitosamente'
        ], 200);
    }

    /**
     * Obtener información del usuario autenticado
     * 
     * @param Request $request
     * @return JsonResponse
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "user": {
     *       "id": 1,
     *       "name": "Usuario Ejemplo",
     *       "email": "usuario@ejemplo.com",
     *       "role": "user",
     *       "area": "logistica",
     *       "empleado": {...}
     *     }
     *   }
     * }
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $empleado = $user->empleado;

        return response()->json([
            'success' => true,
            'data' => [
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'status' => $user->status,
                    'area' => optional($empleado)->area,
                    'empleado' => $empleado ? [
                        'id' => $empleado->id,
                        'nombre_completo' => $empleado->nombre_completo ?? $empleado->nombre,
                        'puesto' => $empleado->puesto,
                        'departamento' => $empleado->departamento,
                    ] : null,
                ]
            ]
        ], 200);
    }

    /**
     * Validar si un token es válido
     * 
     * @param Request $request
     * @return JsonResponse
     * 
     * @bodyParam token string required Token a validar. Example: 1|abc123...
     * 
     * @response 200 {
     *   "success": true,
     *   "valid": true,
     *   "data": {
     *     "user": {...}
     *   }
     * }
     * 
     * @response 200 {
     *   "success": true,
     *   "valid": false,
     *   "message": "Token inválido o expirado"
     * }
     */
    public function validateToken(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'token' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Token es requerido',
                'errors' => $validator->errors()
            ], 422);
        }

        $token = $request->input('token');

        // Intentar autenticar con el token
        $request->headers->set('Authorization', 'Bearer ' . $token);
        
        // Usar Sanctum para validar
        $guard = Auth::guard('sanctum');
        
        if ($guard->check()) {
            $user = $guard->user();
            return response()->json([
                'success' => true,
                'valid' => true,
                'data' => [
                    'user' => [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'role' => $user->role,
                        'status' => $user->status,
                    ]
                ]
            ], 200);
        }

        return response()->json([
            'success' => true,
            'valid' => false,
            'message' => 'Token inválido o expirado'
        ], 200);
    }

    /**
     * Renovar token (revocar actual y crear uno nuevo)
     * 
     * @param Request $request
     * @return JsonResponse
     * 
     * @authenticated
     * 
     * @response 200 {
     *   "success": true,
     *   "message": "Token renovado exitosamente",
     *   "data": {
     *     "token": "2|xyz789...",
     *     "token_type": "Bearer"
     *   }
     * }
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $request->user()->currentAccessToken();
        $tokenName = $currentToken->name ?? 'api-client';

        // Revocar token actual
        $currentToken->delete();

        // Crear nuevo token
        $newToken = $user->createToken($tokenName);

        return response()->json([
            'success' => true,
            'message' => 'Token renovado exitosamente',
            'data' => [
                'token' => $newToken->plainTextToken,
                'token_type' => 'Bearer',
            ]
        ], 200);
    }
}
