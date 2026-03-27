<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Consulta directamente la BD de AuditoriaActivos (conexión 'activos').
 *
 * Esquema real de AuditoriaActivos:
 *   devices      → id, uuid, name, brand, model, serial_number,
 *                   type (enum: computer|peripheral|printer|other),
 *                   status (enum: available|assigned|maintenance|broken)
 *   assignments  → device_id (FK→devices), employee_id (FK→employees, nullable),
 *                   user_id (FK→users, nullable), assigned_to (texto libre, nullable),
 *                   assigned_at, returned_at (NULL = asignación vigente)
 *   employees    → id, name, employee_id (badge/ID del ERP, unique), department,
 *                   position, phone, is_active
 *
 * Correlación con el ERP: empleados.id_empleado ↔ employees.employee_id
 */
class ActivosDbService
{
    private function conn()
    {
        return DB::connection('activos');
    }

    /**
     * Retorna todos los dispositivos disponibles (status = 'available').
     */
    public function getAvailableDevices(): array
    {
        try {
            $rows = $this->conn()
                ->table('devices')
                ->where('status', 'available')
                ->orderBy('name')
                ->get();

            return $rows->map(fn ($r) => $this->mapRow($r))->values()->all();

        } catch (\Exception $e) {
            Log::error('ActivosDb: getAvailableDevices — ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Retorna los dispositivos asignados actualmente a un empleado.
     *
     * La búsqueda utiliza tres criterios (OR):
     *   1. employees.employee_id = $badge  → correlación por ID/badge del ERP (más exacta)
     *   2. employees.name = $nombre        → fallback por nombre completo del empleado
     *   3. assignments.assigned_to = $nombre → asignación libre sin vínculo a employees
     *
     * Una asignación es vigente cuando assignments.returned_at IS NULL.
     *
     * Devuelve array de [ 'device' => [...] ] para compatibilidad con mapDevice()
     * del frontend que hace `d.device ?? d`.
     *
     * @param string      $nombre  Nombre completo del empleado (Empleado::nombre en ERP)
     * @param string|null $badge   ID/badge del empleado  (Empleado::id_empleado en ERP)
     */
    public function getAssignedDevices(string $nombre, ?string $badge = null): array
    {
        try {
            $rows = $this->conn()
                ->table('devices as d')
                ->join('assignments as a', function ($join) {
                    $join->on('a.device_id', '=', 'd.id')
                         ->whereNull('a.returned_at');
                })
                ->leftJoin('employees as e', 'e.id', '=', 'a.employee_id')
                ->select(
                    'd.*',
                    'a.assigned_at',
                    'a.notes as assignment_notes',
                    'e.name as employee_name',
                    'e.employee_id as employee_badge',
                    'e.department',
                    'e.position'
                )
                ->where(function ($q) use ($nombre, $badge) {
                    if ($badge) {
                        $q->where('e.employee_id', $badge);
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
     * Mapea una fila de `devices` (con columnas opcionales del JOIN) al formato
     * que espera el frontend.
     * La BD usa tipo enum: computer | peripheral | printer | other.
     * El frontend espera: 'computer' o 'peripheral'.
     */
    private function mapRow(object $row): array
    {
        $type = $row->type ?? null;

        return [
            'uuid'          => (string) ($row->uuid ?? $row->id ?? ''),
            'name'          => $row->name ?? '',
            'brand'         => $row->brand ?? '',
            'model'         => $row->model ?? '',
            'serial_number' => $row->serial_number ?? '',
            'type'          => $type === 'computer' ? 'computer' : 'peripheral',
            'assignment'    => $row->employee_name ?? $row->assigned_to ?? null,
            'photos'        => [],
        ];
    }
}
