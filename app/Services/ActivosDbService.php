<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio que consulta directamente la BD del sistema de Activos (Auditoria_Activos).
 * Reemplaza a ActivosApiService eliminando la dependencia de la API REST externa.
 *
 * ============================================================
 * CONFIGURACIÓN DE ESQUEMA
 * Ajusta las constantes de abajo si el nombre real de la tabla
 * o las columnas difieren en la BD de producción.
 * ============================================================
 */
class ActivosDbService
{
    // ---------------------------------------------------------------
    // Schema — cambia estos valores según el esquema real de la BD
    // ---------------------------------------------------------------

    /** Tabla principal de dispositivos */
    private const TABLE = 'dispositivos';

    /** Columna identificador único (uuid o id) */
    private const COL_UUID   = 'uuid';

    /** Columna nombre/descripción del dispositivo */
    private const COL_NOMBRE = 'nombre';

    /** Columna marca */
    private const COL_MARCA  = 'marca';

    /** Columna modelo */
    private const COL_MODELO = 'modelo';

    /** Columna número de serie */
    private const COL_SERIE  = 'numero_serie';

    /**
     * Columna tipo/categoría del dispositivo.
     * Valores esperados: palabras que contengan los fragmentos definidos abajo.
     */
    private const COL_TIPO   = 'tipo';

    /**
     * Columna que indica a quién está asignado el dispositivo.
     * NULL o cadena vacía = disponible.
     */
    private const COL_ASIG   = 'asignado_a';

    // Fragmentos para detectar computadoras (case-insensitive)
    private const TIPO_COMPUTER    = ['computadora', 'laptop', 'desktop', 'computer', 'pc', 'notebook'];

    // Fragmentos para detectar periféricos (todo lo que no sea computadora)
    private const TIPO_PERIPHERAL  = ['periferico', 'periférico', 'peripheral', 'monitor', 'teclado', 'mouse', 'impresora', 'scanner'];

    // ---------------------------------------------------------------

    private function conn()
    {
        return DB::connection('activos');
    }

    /**
     * Normaliza el tipo de dispositivo de la BD al formato que espera
     * el frontend: 'computer' o 'peripheral'.
     */
    private function normalizeTipo(?string $tipo): string
    {
        $tipo = mb_strtolower($tipo ?? '');

        foreach (self::TIPO_COMPUTER as $fragment) {
            if (str_contains($tipo, $fragment)) {
                return 'computer';
            }
        }

        return 'peripheral';
    }

    /**
     * Convierte una fila de la BD al formato que espera el frontend.
     * La estructura imita la respuesta original de la API.
     */
    private function mapRow(object $row): array
    {
        // Soporte para COL_UUID o bien caer en 'id'
        $uuid = $row->{self::COL_UUID}
            ?? $row->id
            ?? '';

        return [
            'uuid'          => (string) $uuid,
            'name'          => $row->{self::COL_NOMBRE} ?? '',
            'brand'         => $row->{self::COL_MARCA}  ?? '',
            'model'         => $row->{self::COL_MODELO} ?? '',
            'serial_number' => $row->{self::COL_SERIE}  ?? '',
            'type'          => $this->normalizeTipo($row->{self::COL_TIPO} ?? null),
            'assignment'    => $row->{self::COL_ASIG}   ?? null,
            'photos'        => [],   // Sin proxy de fotos — el frontend muestra ícono SVG
        ];
    }

    /**
     * Retorna todos los dispositivos sin asignar (disponibles).
     * Equivale a GET /api/v1/devices filtrando assignment === null.
     */
    public function getAvailableDevices(): array
    {
        try {
            $rows = $this->conn()
                ->table(self::TABLE)
                ->where(function ($q) {
                    $q->whereNull(self::COL_ASIG)
                      ->orWhere(self::COL_ASIG, '');
                })
                ->orderBy(self::COL_NOMBRE)
                ->get();

            return $rows->map(fn ($r) => $this->mapRow($r))->values()->all();

        } catch (\Exception $e) {
            Log::error('ActivosDb: getAvailableDevices — ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Retorna los dispositivos asignados a un usuario/nombre.
     * Equivale a GET /api/v1/assigned-devices/{username}.
     *
     * Devuelve array de [ 'device' => [...] ] para mantener compatibilidad
     * con la función `mapDevice()` del frontend que hace `d.device ?? d`.
     */
    public function getAssignedDevices(string $username): array
    {
        try {
            $rows = $this->conn()
                ->table(self::TABLE)
                ->where(self::COL_ASIG, $username)
                ->get();

            return $rows->map(fn ($r) => ['device' => $this->mapRow($r)])->values()->all();

        } catch (\Exception $e) {
            Log::error('ActivosDb: getAssignedDevices — ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Indica si el servicio puede conectarse a la BD de activos.
     * Siempre true (la conexión existe en config/database.php),
     * pero devuelve false si la conexión falla.
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
}
