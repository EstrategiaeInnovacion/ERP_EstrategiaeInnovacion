<?php

namespace App\Http\Controllers\Sistemas_IT;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ActivosDbService;
use Illuminate\Support\Facades\Log;

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

        // Si el empleado tiene nombre diferente al del usuario ERP, buscar con ambos.
        // getAssignedDevices acepta un nombre principal; pasamos el del empleado (más específico)
        // y agregamos el nombre del usuario como segundo intento si difiere.
        $allDevices = $this->activos->getAssignedDevices($nombre, $badge, $user->email);

        // Si el empleado tiene nombre y además el usuario tiene un nombre distinto,
        // hacer una segunda búsqueda con el nombre del usuario y fusionar resultados.
        if ($empleado && $empleado->nombre !== $user->name) {
            $byUserName = $this->activos->getAssignedDevices($user->name, $badge, $user->email);
            // Deduplicar por uuid del device
            $existing = array_column(array_column($allDevices, 'device'), 'uuid');
            foreach ($byUserName as $entry) {
                if (!in_array($entry['device']['uuid'] ?? '', $existing, true)) {
                    $allDevices[] = $entry;
                }
            }
        }

        // Solo los de tipo 'computer' cuentan como equipo principal.
        // Periféricos/cables/impresoras asignados NO deben mostrarse como computadora.
        $computers = array_values(array_filter(
            $allDevices,
            fn ($d) => ($d['device']['type'] ?? '') === 'computer'
        ));

        $hasDevice = count($computers) > 0;

        return response()->json([
            'user'       => ['id' => $user->id, 'name' => $nombre],
            'has_device' => $hasDevice,
            'devices'    => $computers,
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
     * Proxy de fotos: lee el archivo desde el storage privado de AuditoriaActivos
     * y lo devuelve como imagen.
     *
     * Requiere ACTIVOS_STORAGE_PATH en .env apuntando al directorio
     * storage/app/private de AuditoriaActivos (p.ej. /var/www/AuditoriaActivos/storage/app/private).
     */
    public function photo(int $id)
    {
        if (! $this->activos->isConfigured()) {
            abort(503, 'BD de activos no disponible.');
        }

        $filePath = $this->activos->getPhotoPath($id);
        if (! $filePath) {
            abort(404);
        }

        $storagePath = rtrim(env('ACTIVOS_STORAGE_PATH', ''), '\/ ');
        if (empty($storagePath)) {
            Log::warning('ActivosApi: ACTIVOS_STORAGE_PATH no configurado en .env');
            abort(503, 'Ruta de almacenamiento de activos no configurada.');
        }

        $fullPath = $storagePath . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $filePath), '\/ ');

        if (! file_exists($fullPath) || ! is_file($fullPath)) {
            abort(404);
        }

        // Seguridad: verificar que el archivo está dentro del directorio permitido
        $realStorage = realpath($storagePath);
        $realFile    = realpath($fullPath);
        if (! $realStorage || ! $realFile || ! str_starts_with($realFile, $realStorage)) {
            abort(403);
        }

        return response()->file($realFile);
    }
}

