<?php

namespace App\Http\Controllers\Sistemas_IT;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivosApiService;

class ActivosApiController extends Controller
{
    public function __construct(protected ActivosApiService $activos) {}

    /**
     * GET /admin/activos-api/usuario/{userId}/equipo
     * Devuelve los equipos asignados al usuario en el sistema de activos.
     */
    public function devicesByUser(int $userId)
    {
        $user = User::findOrFail($userId);

        if (!$this->activos->isConfigured()) {
            return response()->json([
                'error'      => 'API de activos no configurada (ACTIVOS_API_URL).',
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
     * Lista todos los equipos sin asignación en el sistema de activos.
     */
    public function availableDevices()
    {
        if (!$this->activos->isConfigured()) {
            return response()->json(['error' => 'API de activos no configurada.'], 503);
        }

        return response()->json($this->activos->getAvailableDevices());
    }

    /**
     * GET /admin/activos-api/fotos/{id}
     * Sirve la imagen de un equipo desde el sistema de activos.
     */
    public function photo(int $id)
    {
        $imageData = $this->activos->getDevicePhoto($id);

        if (!$imageData) {
            return response()->json(['error' => 'Foto no encontrada.'], 404);
        }

        return response($imageData)
            ->header('Content-Type', 'image/jpeg')
            ->header('Cache-Control', 'public, max-age=3600');
    }
}
