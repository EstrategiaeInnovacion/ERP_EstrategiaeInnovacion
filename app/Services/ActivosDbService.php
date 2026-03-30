<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Consulta y escribe directamente en la BD de AuditoriaActivos (conexión 'activos').
 *
 * Esquema real de AuditoriaActivos:
 *   devices       → id, uuid, name, brand, model, serial_number,
 *                    type (enum: computer|peripheral|printer|other),
 *                    status (enum: available|assigned|maintenance|broken)
 *   assignments   → device_id, employee_id (nullable), user_id (nullable),
 *                    assigned_to (texto libre, nullable),
 *                    assigned_at, returned_at (NULL = asignación vigente)
 *   employees     → id, name, employee_id (badge/ID del ERP, unique),
 *                    department, position, phone, is_active
 *   device_photos → id, device_id, file_path, caption
 *
 * Correlación con el ERP: empleados.id_empleado ↔ employees.employee_id
 *
 * Para servir fotos, el ERP necesita que ACTIVOS_STORAGE_PATH en .env
 * apunte al directorio storage/app/private de AuditoriaActivos.
 */
class ActivosDbService
{
    private function conn()
    {
        return DB::connection('activos');
    }

    // ---------------------------------------------------------------
    // Consultas de dispositivos
    // ---------------------------------------------------------------

    /**
     * Retorna todos los dispositivos disponibles (status = 'available')
     * incluyendo la primera foto de cada uno.
     *
     * También incluye dispositivos con status = 'assigned' que NO tienen una
     * asignación activa (returnerd_at IS NULL) — estado huérfano que ocurre
     * cuando el status se cambia manualmente desde el formulario de edición
     * sin pasar por el flujo de asignación.
     */
    public function getAvailableDevices(): array
    {
        try {
            $rows = $this->conn()
                ->table('devices as d')
                ->leftJoin('device_photos as dp', function ($join) {
                    $join->on('dp.device_id', '=', 'd.id')
                         ->whereRaw('dp.id = (SELECT MIN(id) FROM device_photos WHERE device_id = d.id)');
                })
                ->select('d.*', 'dp.id as photo_id', 'dp.file_path as photo_path')
                ->where(function ($q) {
                    $q->where('d.status', 'available')
                      ->orWhere(function ($q2) {
                          // Dispositivo marcado como 'assigned' pero sin registro de asignación activa
                          $q2->where('d.status', 'assigned')
                             ->whereNotExists(function ($q3) {
                                 $q3->select('id')
                                    ->from('assignments')
                                    ->whereColumn('device_id', 'd.id')
                                    ->whereNull('returned_at');
                             });
                      });
                })
                ->orderBy('d.name')
                ->get();

            return $rows->map(fn ($r) => $this->mapRow($r))->values()->all();

        } catch (\Exception $e) {
            Log::error('ActivosDb: getAvailableDevices — ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Retorna los dispositivos asignados actualmente a un empleado
     * incluyendo la primera foto de cada uno.
     *
     * Búsqueda (OR):
     *   1. employees.employee_id = $badge   (correlación por badge del ERP — más exacta)
     *   2. users.email = $email             (asignación creada desde AuditoriaActivos con user_id)
     *   3. employees.name = $nombre         (fallback por nombre completo)
     *   4. assignments.assigned_to = $nombre (asignación libre sin employee)
     *
     * Devuelve array de [ 'device' => [...] ] para compatibilidad con mapDevice()
     * del frontend (`d.device ?? d`).
     */
    public function getAssignedDevices(string $nombre, ?string $badge = null, ?string $email = null): array
    {
        try {
            $rows = $this->conn()
                ->table('devices as d')
                ->join('assignments as a', function ($join) {
                    $join->on('a.device_id', '=', 'd.id')
                         ->whereNull('a.returned_at');
                })
                ->leftJoin('employees as e', 'e.id', '=', 'a.employee_id')
                ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
                ->leftJoin('device_photos as dp', function ($join) {
                    $join->on('dp.device_id', '=', 'd.id')
                         ->whereRaw('dp.id = (SELECT MIN(id) FROM device_photos WHERE device_id = d.id)');
                })
                ->select(
                    'd.*',
                    'a.assigned_at',
                    'a.notes as assignment_notes',
                    'e.name as employee_name',
                    'e.employee_id as employee_badge',
                    'e.department',
                    'e.position',
                    'dp.id as photo_id',
                    'dp.file_path as photo_path'
                )
                ->where(function ($q) use ($nombre, $badge, $email) {
                    if ($badge) {
                        $q->where('e.employee_id', $badge);
                    }
                    if ($email) {
                        $q->orWhere('u.email', $email);
                    }
                    $q->orWhere('e.name', $nombre)
                      ->orWhere('a.assigned_to', $nombre);
                })
                ->get();

            return $rows->map(fn ($r) => ['device' => $this->mapRow($r)])->values()->all();

        } catch (\Exception $e) {
            Log::error('ActivosDb: getAssignedDevices — ' . $e->getMessage());
            return [];
        }
    }

    // ---------------------------------------------------------------
    // Escritura — sincronización de asignaciones
    // ---------------------------------------------------------------

    /**
     * Crea una asignación activa en AuditoriaActivos y marca el dispositivo
     * como 'assigned'. Llamar al guardar un EquipoAsignado desde el ERP.
     *
     * @param string      $uuid       UUID del dispositivo en AuditoriaActivos
     * @param string      $assignedTo Nombre del empleado (texto visible en AuditoriaActivos)
     * @param string|null $badge      id_empleado del ERP (employees.employee_id en Activos)
     * @param string|null $notes      Notas opcionales
     */
    public function assignDeviceInActivos(
        string $uuid,
        string $assignedTo,
        ?string $badge = null,
        ?string $notes = null
    ): bool {
        try {
            $conn = $this->conn();

            $device = $conn->table('devices')->where('uuid', $uuid)->first();
            if (! $device) {
                Log::warning("ActivosDb: assignDevice — dispositivo no encontrado: {$uuid}");
                return false;
            }

            // Buscar employee por badge
            $employeeId = null;
            if ($badge) {
                $employee   = $conn->table('employees')->where('employee_id', $badge)->first();
                $employeeId = $employee?->id;
            }

            $conn->transaction(function () use ($conn, $device, $employeeId, $assignedTo, $notes) {
                // Cerrar cualquier asignación previa que hubiera quedado abierta
                $conn->table('assignments')
                    ->where('device_id', $device->id)
                    ->whereNull('returned_at')
                    ->update(['returned_at' => now(), 'updated_at' => now()]);

                $conn->table('assignments')->insert([
                    'device_id'   => $device->id,
                    'user_id'     => null,
                    'employee_id' => $employeeId,
                    'assigned_to' => $assignedTo,
                    'assigned_at' => now(),
                    'returned_at' => null,
                    'notes'       => $notes,
                    'created_at'  => now(),
                    'updated_at'  => now(),
                ]);

                $conn->table('devices')
                    ->where('id', $device->id)
                    ->update(['status' => 'assigned', 'updated_at' => now()]);
            });

            return true;

        } catch (\Exception $e) {
            Log::error("ActivosDb: assignDevice [{$uuid}] — " . $e->getMessage());
            return false;
        }
    }

    /**
     * Cierra la asignación activa de un dispositivo y lo marca como 'available'.
     * Llamar al eliminar un EquipoAsignado desde el ERP.
     *
     * @param string $uuid UUID del dispositivo en AuditoriaActivos
     */
    public function returnDeviceInActivos(string $uuid): bool
    {
        try {
            $conn = $this->conn();

            $device = $conn->table('devices')->where('uuid', $uuid)->first();
            if (! $device) {
                return false;
            }

            $conn->transaction(function () use ($conn, $device) {
                $conn->table('assignments')
                    ->where('device_id', $device->id)
                    ->whereNull('returned_at')
                    ->update(['returned_at' => now(), 'updated_at' => now()]);

                $conn->table('devices')
                    ->where('id', $device->id)
                    ->update(['status' => 'available', 'updated_at' => now()]);
            });

            return true;

        } catch (\Exception $e) {
            Log::error("ActivosDb: returnDevice [{$uuid}] — " . $e->getMessage());
            return false;
        }
    }

    // ---------------------------------------------------------------
    // Fotos
    // ---------------------------------------------------------------

    /**
     * Retorna el file_path de una foto de dispositivo para servir como proxy.
     * El ERP debe tener ACTIVOS_STORAGE_PATH en .env apuntando al
     * directorio storage/app/private de AuditoriaActivos.
     */
    public function getPhotoPath(int $photoId): ?string
    {
        try {
            $photo = $this->conn()
                ->table('device_photos')
                ->where('id', $photoId)
                ->select('file_path')
                ->first();

            return $photo?->file_path;

        } catch (\Exception $e) {
            Log::error("ActivosDb: getPhotoPath [{$photoId}] — " . $e->getMessage());
            return null;
        }
    }

    // ---------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------

    /**
     * Retorna el tipo de un dispositivo dado su UUID ('computer', 'peripheral', etc.)
     * o null si no se encuentra o hay error de conexión.
     */
    public function getDeviceTypeByUuid(string $uuid): ?string
    {
        try {
            $device = $this->conn()
                ->table('devices')
                ->where('uuid', $uuid)
                ->select('type')
                ->first();

            return $device?->type;

        } catch (\Exception $e) {
            Log::error("ActivosDb: getDeviceTypeByUuid [{$uuid}] — " . $e->getMessage());
            return null;
        }
    }

    /**
     * Indica si la conexión a la BD de activos está disponible.
     */
    public function isConfigured(): bool
    {
        try {
            $this->conn()->getPdo();
            return true;
        } catch (\Exception $e) {
            Log::warning('ActivosDb: no se pudo conectar — ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Mapea una fila de `devices` (con columnas opcionales de JOINs) al
     * formato que espera el frontend.
     * - `photos`: incluye la primera foto si existe, con URL al proxy del ERP.
     */
    private function mapRow(object $row): array
    {
        $photos = [];
        if (! empty($row->photo_id)) {
            $photos[] = [
                'id'  => $row->photo_id,
                'url' => url("admin/activos-api/fotos/{$row->photo_id}"),
            ];
        }

        return [
            'uuid'          => (string) ($row->uuid ?? $row->id ?? ''),
            'name'          => $row->name ?? '',
            'brand'         => $row->brand ?? '',
            'model'         => $row->model ?? '',
            'serial_number' => $row->serial_number ?? '',
            'type'          => ($row->type ?? '') === 'computer' ? 'computer' : 'peripheral',
            'assignment'    => $row->employee_name ?? $row->assigned_to ?? null,
            'photos'        => $photos,
        ];
    }
}
