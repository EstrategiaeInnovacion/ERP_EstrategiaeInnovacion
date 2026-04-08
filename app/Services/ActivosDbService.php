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
     * Búsqueda (OR, de mayor a menor confiabilidad):
     *   1. employees.employee_id = $badge       (badge ERP — más exacta)
     *   2. users.email = $email                 (usuario de AuditoriaActivos con mismo email)
     *   3. employees.name LIKE '%nombre%'        (nombre en tabla employees)
     *   4. users.name LIKE '%nombre%'            (nombre del usuario de Activos que hizo la asignación)
     *   5. assignments.assigned_to LIKE '%nombre%' (texto libre)
     *   + Por cada palabra significativa (>3 chars) del nombre, condiciones LIKE
     *     adicionales sobre e.name y a.assigned_to.
     *
     * Devuelve array de [ 'device' => [...] ] para compatibilidad con mapDevice()
     * del frontend (`d.device ?? d`).
     */
    public function getAssignedDevices(string $nombre, ?string $badge = null, ?string $email = null): array
    {
        try {
            // Palabras significativas del nombre (más de 5 caracteres) para búsqueda parcial.
            // Se usa un umbral de 6+ chars para evitar falsos positivos: p. ej. buscar "Ana Karen"
            // no debe encontrar el equipo de "Karen Bonal" porque ambas contienen "Karen" (5 chars).
            $palabras = array_values(array_filter(
                explode(' ', $nombre),
                fn (string $w) => mb_strlen(trim($w)) > 5
            ));

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
                ->where(function ($q) use ($nombre, $badge, $email, $palabras) {
                    // 1. Badge exacto
                    if ($badge) {
                        $q->where('e.employee_id', $badge);
                    }
                    // 2. Email exacto del usuario en Activos
                    if ($email) {
                        $q->orWhere('u.email', $email);
                    }
                    // 3-5. Nombre completo con LIKE (detecta si el nombre ERP está dentro del campo o viceversa)
                    $q->orWhere('e.name', 'like', "%{$nombre}%")
                      ->orWhere('u.name', 'like', "%{$nombre}%")
                      ->orWhere('a.assigned_to', 'like', "%{$nombre}%");
                    // 6. Búsqueda palabra por palabra (nombres que no coinciden al 100%)
                    foreach ($palabras as $palabra) {
                        $q->orWhere('e.name', 'like', "%{$palabra}%")
                          ->orWhere('a.assigned_to', 'like', "%{$palabra}%");
                    }
                })
                ->distinct()
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
     * Inserta un registro en device_photos para un dispositivo identificado por UUID.
     * $filePath es relativo a storage/app/private del ERP (e.g. "activos-fotos/archivo.jpg").
     */
    public function addDevicePhoto(string $uuid, string $filePath, ?string $caption = null): bool
    {
        try {
            $device = $this->conn()->table('devices')->where('uuid', $uuid)->first();
            if (! $device) {
                Log::warning("ActivosDb: addDevicePhoto — dispositivo no encontrado: {$uuid}");
                return false;
            }

            $this->conn()->table('device_photos')->insert([
                'device_id'  => $device->id,
                'file_path'  => $filePath,
                'caption'    => $caption,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("ActivosDb: addDevicePhoto [{$uuid}] — " . $e->getMessage());
            return false;
        }
    }

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
            // Preservar el tipo real del dispositivo (computer|peripheral|printer|other)
            // para que el filtro del controlador funcione correctamente.
            'type'          => $row->type ?? 'other',
            'assignment'    => $row->employee_name ?? $row->assigned_to ?? null,
            'photos'        => $photos,
        ];
    }

    // ---------------------------------------------------------------
    // Inventario completo (Módulo Activos IT del ERP)
    // ---------------------------------------------------------------

    /**
     * Retorna una paginación manual de dispositivos con filtros opcionales
     * de búsqueda, tipo y estado.
     *
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getAllDevicesPaginated(
        ?string $search = null,
        ?string $type   = null,
        ?string $status = null,
        int $perPage    = 15
    ): \Illuminate\Pagination\LengthAwarePaginator {
        try {
            $query = $this->conn()
                ->table('devices as d')
                ->leftJoin('device_photos as dp', function ($join) {
                    $join->on('dp.device_id', '=', 'd.id')
                         ->whereRaw('dp.id = (SELECT MIN(id) FROM device_photos WHERE device_id = d.id)');
                })
                ->leftJoin('assignments as a', function ($join) {
                    $join->on('a.device_id', '=', 'd.id')
                         ->whereNull('a.returned_at');
                })
                ->leftJoin('employees as e', 'e.id', '=', 'a.employee_id')
                ->select(
                    'd.id', 'd.uuid', 'd.name', 'd.brand', 'd.model',
                    'd.serial_number', 'd.type', 'd.status',
                    'd.purchase_date', 'd.warranty_expiration', 'd.notes',
                    'd.created_at', 'd.updated_at',
                    'dp.id as photo_id',
                    'a.assigned_at', 'a.assigned_to',
                    'e.name as employee_name'
                );

            if ($search) {
                $query->where(function ($q) use ($search) {
                    $q->where('d.name', 'like', "%{$search}%")
                      ->orWhere('d.brand', 'like', "%{$search}%")
                      ->orWhere('d.model', 'like', "%{$search}%")
                      ->orWhere('d.serial_number', 'like', "%{$search}%")
                      ->orWhere('e.name', 'like', "%{$search}%")
                      ->orWhere('a.assigned_to', 'like', "%{$search}%");
                });
            }

            if ($type) {
                $query->where('d.type', $type);
            }

            if ($status) {
                $query->where('d.status', $status);
            }

            $query->orderBy('d.name');

            $total = $query->count();
            $page  = \Illuminate\Pagination\Paginator::resolveCurrentPage();
            $items = $query->forPage($page, $perPage)->get();

            return new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $page,
                ['path' => \Illuminate\Pagination\Paginator::resolveCurrentPath()]
            );

        } catch (\Exception $e) {
            Log::error('ActivosDb: getAllDevicesPaginated — ' . $e->getMessage());
            return new \Illuminate\Pagination\LengthAwarePaginator(collect(), 0, $perPage);
        }
    }

    /**
     * Retorna conteos de dispositivos agrupados por status y por tipo.
     */
    public function getDeviceStats(): array
    {
        try {
            $conn = $this->conn();

            $byStatus = $conn->table('devices')
                ->selectRaw('status, COUNT(*) as total')
                ->groupBy('status')
                ->pluck('total', 'status')
                ->toArray();

            $byType = $conn->table('devices')
                ->selectRaw('type, COUNT(*) as total')
                ->groupBy('type')
                ->pluck('total', 'type')
                ->toArray();

            return [
                'total'     => array_sum($byStatus),
                'by_status' => [
                    'available'   => (int) ($byStatus['available']   ?? 0),
                    'assigned'    => (int) ($byStatus['assigned']    ?? 0),
                    'maintenance' => (int) ($byStatus['maintenance'] ?? 0),
                    'broken'      => (int) ($byStatus['broken']      ?? 0),
                ],
                'by_type'   => [
                    'computer'   => (int) ($byType['computer']   ?? 0),
                    'peripheral' => (int) ($byType['peripheral'] ?? 0),
                    'printer'    => (int) ($byType['printer']    ?? 0),
                    'other'      => (int) ($byType['other']      ?? 0),
                ],
            ];

        } catch (\Exception $e) {
            Log::error('ActivosDb: getDeviceStats — ' . $e->getMessage());
            return [
                'total'     => 0,
                'by_status' => ['available' => 0, 'assigned' => 0, 'maintenance' => 0, 'broken' => 0],
                'by_type'   => ['computer' => 0, 'peripheral' => 0, 'printer' => 0, 'other' => 0],
            ];
        }
    }

    /**
     * Retorna todos los datos de un dispositivo por UUID, incluyendo
     * la asignación activa actual y nombre del empleado asignado.
     */
    public function getDeviceByUuid(string $uuid): ?object
    {
        try {
            return $this->conn()
                ->table('devices as d')
                ->leftJoin('assignments as a', function ($join) {
                    $join->on('a.device_id', '=', 'd.id')
                         ->whereNull('a.returned_at');
                })
                ->leftJoin('employees as e', 'e.id', '=', 'a.employee_id')
                ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
                ->select(
                    'd.*',
                    'a.id as assignment_id', 'a.assigned_at', 'a.assigned_to',
                    'a.notes as assignment_notes',
                    'e.name as employee_name', 'e.employee_id as employee_badge',
                    'e.department', 'e.position',
                    'u.name as activos_user_name'
                )
                ->where('d.uuid', $uuid)
                ->first();

        } catch (\Exception $e) {
            Log::error("ActivosDb: getDeviceByUuid [{$uuid}] — " . $e->getMessage());
            return null;
        }
    }

    /**
     * Retorna todas las fotos de un dispositivo (por device_id interno de activos).
     */
    public function getDevicePhotos(int $deviceId): array
    {
        try {
            return $this->conn()
                ->table('device_photos')
                ->where('device_id', $deviceId)
                ->orderBy('id')
                ->get(['id', 'caption', 'created_at'])
                ->map(fn ($p) => [
                    'id'      => $p->id,
                    'url'     => url("admin/activos-api/fotos/{$p->id}"),
                    'caption' => $p->caption ?? '',
                ])
                ->toArray();

        } catch (\Exception $e) {
            Log::error("ActivosDb: getDevicePhotos [{$deviceId}] — " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retorna el historial completo de asignaciones de un dispositivo,
     * de más reciente a más antigua.
     */
    public function getAssignmentHistory(int $deviceId): array
    {
        try {
            return $this->conn()
                ->table('assignments as a')
                ->leftJoin('employees as e', 'e.id', '=', 'a.employee_id')
                ->leftJoin('users as u', 'u.id', '=', 'a.user_id')
                ->where('a.device_id', $deviceId)
                ->orderByDesc('a.assigned_at')
                ->select(
                    'a.id', 'a.assigned_at', 'a.returned_at',
                    'a.assigned_to', 'a.notes',
                    'e.name as employee_name', 'e.employee_id as employee_badge',
                    'u.name as activos_user_name'
                )
                ->get()
                ->toArray();

        } catch (\Exception $e) {
            Log::error("ActivosDb: getAssignmentHistory [{$deviceId}] — " . $e->getMessage());
            return [];
        }
    }

    /**
     * Retorna los documentos de un dispositivo.
     */
    public function getDeviceDocuments(int $deviceId): array
    {
        try {
            return $this->conn()
                ->table('device_documents')
                ->where('device_id', $deviceId)
                ->orderBy('id')
                ->get(['id', 'original_name', 'type', 'created_at'])
                ->toArray();

        } catch (\Exception $e) {
            Log::error("ActivosDb: getDeviceDocuments [{$deviceId}] — " . $e->getMessage());
            return [];
        }
    }

    // ---------------------------------------------------------------
    // Escritura — crear y editar dispositivos
    // ---------------------------------------------------------------

    /**
     * Crea un nuevo dispositivo en la BD de activos.
     * Retorna el UUID generado o null en caso de error.
     */
    public function createDevice(array $data): ?string
    {
        try {
            $conn = $this->conn();
            $uuid = \Illuminate\Support\Str::uuid()->toString();

            $conn->table('devices')->insert([
                'uuid'                => $uuid,
                'name'                => $data['name'],
                'brand'               => $data['brand'] ?? null,
                'model'               => $data['model'] ?? null,
                'serial_number'       => $data['serial_number'],
                'type'                => $data['type'],
                'status'              => $data['status'] ?? 'available',
                'purchase_date'       => $data['purchase_date'] ?? null,
                'warranty_expiration' => $data['warranty_expiration'] ?? null,
                'notes'               => $data['notes'] ?? null,
                'created_at'          => now(),
                'updated_at'          => now(),
            ]);

            // Credenciales opcionales
            if (!empty($data['cred_username']) || !empty($data['cred_email'])) {
                $device = $conn->table('devices')->where('uuid', $uuid)->first();
                if ($device) {
                    $conn->table('credentials')->insert([
                        'device_id'      => $device->id,
                        'username'        => $data['cred_username'] ?? null,
                        'password'        => !empty($data['cred_password'])
                            ? encrypt($data['cred_password'])
                            : null,
                        'email'           => $data['cred_email'] ?? null,
                        'email_password'  => !empty($data['cred_email_password'])
                            ? encrypt($data['cred_email_password'])
                            : null,
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ]);
                }
            }

            return $uuid;

        } catch (\Exception $e) {
            Log::error('ActivosDb: createDevice — ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Actualiza los datos de un dispositivo en la BD de activos.
     * También crea/actualiza/elimina el registro de credenciales.
     */
    public function updateDevice(string $uuid, array $data): bool
    {
        try {
            $conn   = $this->conn();
            $device = $conn->table('devices')->where('uuid', $uuid)->first();

            if (! $device) {
                Log::warning("ActivosDb: updateDevice — dispositivo no encontrado: {$uuid}");
                return false;
            }

            $conn->table('devices')->where('id', $device->id)->update([
                'name'                => $data['name'],
                'brand'               => $data['brand'] ?? null,
                'model'               => $data['model'] ?? null,
                'serial_number'       => $data['serial_number'],
                'type'                => $data['type'],
                'status'              => $data['status'],
                'purchase_date'       => $data['purchase_date'] ?? null,
                'warranty_expiration' => $data['warranty_expiration'] ?? null,
                'notes'               => $data['notes'] ?? null,
                'updated_at'          => now(),
            ]);

            // Credenciales: upsert en tabla credentials
            $credencial = $conn->table('credentials')->where('device_id', $device->id)->first();

            $hasCredData = !empty($data['cred_username']) || !empty($data['cred_email']);

            if ($hasCredData) {
                $credData = [
                    'username'       => $data['cred_username'] ?? null,
                    'email'          => $data['cred_email'] ?? null,
                    'updated_at'     => now(),
                ];
                if (!empty($data['cred_password'])) {
                    $credData['password'] = encrypt($data['cred_password']);
                }
                if (!empty($data['cred_email_password'])) {
                    $credData['email_password'] = encrypt($data['cred_email_password']);
                }

                if ($credencial) {
                    $conn->table('credentials')->where('id', $credencial->id)->update($credData);
                } else {
                    $credData['device_id']  = $device->id;
                    $credData['created_at'] = now();
                    $conn->table('credentials')->insert($credData);
                }
            } elseif ($credencial) {
                // Si se borraron los datos de credenciales, eliminar el registro
                $conn->table('credentials')->where('id', $credencial->id)->delete();
            }

            return true;

        } catch (\Exception $e) {
            Log::error("ActivosDb: updateDevice [{$uuid}] — " . $e->getMessage());
            return false;
        }
    }

    /**
     * Retorna las credenciales de un dispositivo (contraseñas cifradas, ya descifradas).
     * Solo llamar cuando el usuario tenga permisos de administrador.
     */
    public function getDeviceCredential(int $deviceId): ?object
    {
        try {
            $cred = $this->conn()
                ->table('credentials')
                ->where('device_id', $deviceId)
                ->first();

            if (! $cred) {
                return null;
            }

            // Descifrar contraseñas usando el helper de Laravel
            if ($cred->password) {
                try { $cred->password = decrypt($cred->password); } catch (\Throwable) { $cred->password = null; }
            }
            if ($cred->email_password) {
                try { $cred->email_password = decrypt($cred->email_password); } catch (\Throwable) { $cred->email_password = null; }
            }

            return $cred;

        } catch (\Exception $e) {
            Log::error("ActivosDb: getDeviceCredential [{$deviceId}] — " . $e->getMessage());
            return null;
        }
    }

}