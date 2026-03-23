<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Controller para consulta de usuarios del sistema
 *
 * Permite a sistemas externos obtener el listado de usuarios
 * activos (aprobados) para reutilizarlos sin necesidad de
 * volver a registrarlos.
 */
class UserController extends Controller
{
    /**
     * Retorna todos los usuarios con status "approved" (activos).
     *
     * @param  Request  $request
     * @return JsonResponse
     *
     * @queryParam search string Filtrar por nombre o correo. Example: juan
     * @queryParam role string Filtrar por rol (admin, user, etc.). Example: user
     *
     * @response 200 {
     *   "success": true,
     *   "total": 25,
     *   "data": [
     *     {
     *       "id": 1,
     *       "name": "Juan Pérez",
     *       "email": "juan@ejemplo.com",
     *       "role": "user",
     *       "status": "approved",
     *       "approved_at": "2025-01-10T12:00:00.000000Z",
     *       "empleado": {
     *         "id_empleado": "EMP001",
     *         "nombre": "Juan Pérez García",
     *         "area": "Logística",
     *         "posicion": "Coordinador",
     *         "es_activo": true
     *       }
     *     }
     *   ]
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::query()
            ->where('status', User::STATUS_APPROVED)
            ->with(['empleado:id,user_id,id_empleado,nombre,area,posicion,es_activo']);

        // Filtro opcional por nombre o correo
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // Filtro opcional por rol
        if ($request->filled('role')) {
            $query->where('role', $request->input('role'));
        }

        $users = $query
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'role', 'status', 'approved_at']);

        return response()->json([
            'success' => true,
            'total'   => $users->count(),
            'data'    => $users,
        ]);
    }
}
