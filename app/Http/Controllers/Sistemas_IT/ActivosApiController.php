<?php

namespace App\Http\Controllers\Sistemas_IT;

use App\Http\Controllers\Controller;
use App\Models\Empleado;
use App\Models\User;
use App\Services\ActivosDbService;
use Illuminate\Http\Request;
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

        // Periféricos y otros dispositivos (impresoras, tablets, etc.) asignados al usuario.
        $peripherals = array_values(array_filter(
            $allDevices,
            fn ($d) => ($d['device']['type'] ?? '') !== 'computer'
        ));

        $hasDevice = count($computers) > 0;

        return response()->json([
            'user'        => ['id' => $user->id, 'name' => $nombre],
            'has_device'  => $hasDevice,
            'devices'     => $computers,
            'peripherals' => $peripherals,
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
     * GET /admin/activos-api/dispositivo/{uuid}
     * Busca un dispositivo por UUID (scaneado desde un QR) y retorna sus datos+estado en JSON.
     */
    public function lookupByUuid(string $uuid)
    {
        if (! $this->activos->isConfigured()) {
            return response()->json(['error' => 'BD de activos no disponible.'], 503);
        }

        $d = $this->activos->getDeviceByUuid($uuid);

        if (! $d) {
            return response()->json(['error' => 'Dispositivo no encontrado en el sistema.'], 404);
        }

        $statusLabels = [
            'available'   => 'Disponible',
            'assigned'    => 'Asignado',
            'maintenance' => 'En mantenimiento',
            'broken'      => 'Dañado',
        ];
        $typeLabels = [
            'computer'   => 'Computadora',
            'peripheral' => 'Periférico',
            'printer'    => 'Impresora',
            'other'      => 'Otro',
        ];

        return response()->json([
            'uuid'         => $d->uuid,
            'name'         => $d->name,
            'brand'        => $d->brand ?? '',
            'model'        => $d->model ?? '',
            'serial'       => $d->serial_number ?? '',
            'type'         => $d->type,
            'type_label'   => $typeLabels[$d->type] ?? $d->type,
            'status'       => $d->status,
            'status_label' => $statusLabels[$d->status] ?? $d->status,
            'assigned_to'  => $d->employee_name ?? $d->assigned_to ?? null,
        ]);
    }

    /**
     * POST /admin/activos-api/qr-asignar/{uuid}
     * Asigna un dispositivo vía QR (responde JSON para el escáner).
     */
    public function assignViaQr(Request $request, string $uuid)
    {
        $data = $request->validate([
            'empleado_id'     => 'required|exists:empleados,id',
            'tipo_movimiento' => 'required|in:asignacion_fija,prestamo_temporal',
            'fecha_devolucion' => 'nullable|date|after:now',
            'notas'           => 'nullable|string|max:1000',
        ]);

        $empleado = Empleado::findOrFail($data['empleado_id']);

        $notes = $data['notas'] ?? null;
        if ($data['tipo_movimiento'] === 'prestamo_temporal' && ! empty($data['fecha_devolucion'])) {
            $labelFecha = \Carbon\Carbon::parse($data['fecha_devolucion'])->format('d/m/Y H:i');
            $notes = trim("[Préstamo temporal — Devolución: {$labelFecha}] " . ($notes ?? ''));
        }

        $ok = $this->activos->assignDeviceInActivos(
            uuid:       $uuid,
            assignedTo: $empleado->nombre,
            badge:      $empleado->id_empleado ?: null,
            notes:      $notes,
        );

        if (! $ok) {
            return response()->json(['error' => 'No se pudo registrar la asignación.'], 422);
        }

        return response()->json([
            'success' => true,
            'message' => "Dispositivo asignado a {$empleado->nombre}.",
        ]);
    }

    /**
     * POST /admin/activos-api/qr-devolver/{uuid}
     * Registra la devolución de un dispositivo vía QR (responde JSON para el escáner).
     */
    public function returnViaQr(string $uuid)
    {
        $ok = $this->activos->returnDeviceInActivos($uuid);

        if (! $ok) {
            return response()->json(['error' => 'No se pudo registrar la devolución.'], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Dispositivo devuelto y marcado como disponible.',
        ]);
    }

    /**
     * POST /admin/activos-api/qr-danado/{uuid}
     * Marca un dispositivo como dañado con motivo (responde JSON para el escáner).
     */
    public function markBrokenViaQr(Request $request, string $uuid)
    {
        $data = $request->validate([
            'motivo' => 'required|string|max:500',
        ]);

        $ok = $this->activos->markDeviceBroken($uuid, $data['motivo']);

        if (! $ok) {
            return response()->json(['error' => 'No se pudo registrar el estado del dispositivo.'], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Dispositivo marcado como dañado.',
        ]);
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

        // 1. Intentar servir desde ACTIVOS_STORAGE_PATH (fotos de AuditoriaActivos)
        $storagePath = rtrim(env('ACTIVOS_STORAGE_PATH', ''), '\/ ');
        if (! empty($storagePath)) {
            $fullPath = $storagePath . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $filePath), '\/ ');

            if (file_exists($fullPath) && is_file($fullPath)) {
                $realStorage = realpath($storagePath);
                $realFile    = realpath($fullPath);
                if ($realStorage && $realFile && str_starts_with($realFile, $realStorage)) {
                    return response()->file($realFile);
                }
            }
        }

        // 2. Fallback: fotos subidas directamente desde el ERP (storage/app/private)
        $localBase = storage_path('app/private');
        $localPath = $localBase . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $filePath), '\/ ');

        if (file_exists($localPath) && is_file($localPath)) {
            $realBase  = realpath($localBase);
            $realFile  = realpath($localPath);
            if ($realBase && $realFile && str_starts_with($realFile, $realBase)) {
                return response()->file($realFile);
            }
        }

        abort(404);
    }
}

