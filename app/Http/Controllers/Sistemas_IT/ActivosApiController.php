<?php

namespace App\Http\Controllers\Sistemas_IT;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivosDbService;

class ActivosApiController extends Controller
{
    public function __construct(protected ActivosDbService $activos) {}

    /**
     * GET /admin/activos-api/usuario/{userId}/equipo
     * Consulta los dispositivos asignados al usuario directamente desde la BD de activos.
     */
    public function devicesByUser(int $userId)
    {
        $user = User::findOrFail($userId);

        if (!$this->activos->isConfigured()) {
            return response()->json([
                'error'      => 'No se pudo conectar a la BD de activos. Verifica las credenciales en .env (DB_ACTIVOS_*).',
                'has_device' => false,
                'devices'    => [],
            ], 503);
        }

        $devices   = $this->activos->getAssignedDevices($user->name);
        $hasDevice = count($devices) > 0;

        return response()->json([
            'user'       => ['id' => $user->id, 'name' => $user->name],
            'has_device' => $hasDevice,
            'devices'    => $devices,
        ]);
    }

    /**
     * GET /admin/activos-api/equipos-disponibles
     * Lista todos los dispositivos sin asignar desde la BD de activos.
     */
    public function availableDevices()
    {
        if (!$this->activos->isConfigured()) {
            return response()->json([
                'error' => 'No se pudo conectar a la BD de activos.',
            ], 503);
        }

        return response()->json($this->activos->getAvailableDevices());
    }

    /**
     * GET /admin/activos-api/fotos/{id}
     * Las fotos ya no se sirven desde esta app (no hay proxy a API externa).
     * El frontend muestra ícono SVG cuando photo_id es null.
     */
    public function photo(int $id)
    {
        return response()->json(['error' => 'Fotos no disponibles.'], 404);
    }
}

