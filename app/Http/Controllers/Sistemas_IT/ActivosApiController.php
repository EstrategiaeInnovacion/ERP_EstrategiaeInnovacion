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
     * Consulta los dispositivos asignados al usuario desde la BD de activos.
     *
     * Usa `empleados.id_empleado` (badge del ERP) como llave primaria de correlación
     * contra `employees.employee_id` en AuditoriaActivos; cae en nombre si no hay badge.
     */
    public function devicesByUser(int $userId)
    {
        $user = User::with('empleado')->findOrFail($userId);

        if (!$this->activos->isConfigured()) {
            return response()->json([
                'error'      => 'No se pudo conectar a la BD de activos. Verifica las credenciales en .env (DB_ACTIVOS_*).',
                'has_device' => false,
                'devices'    => [],
            ], 503);
        }

        $empleado = $user->empleado;
        $badge    = $empleado?->id_empleado ?: null;
        $nombre   = $empleado?->nombre ?? $user->name;

        $devices   = $this->activos->getAssignedDevices($nombre, $badge);
        $hasDevice = count($devices) > 0;

        return response()->json([
            'user'       => ['id' => $user->id, 'name' => $nombre],
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

