<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ActivosApiService
{
    private string $baseUrl;
    private string $apiKey;

    public function __construct()
    {
        $this->baseUrl = rtrim(config('activos.api_url', ''), '/');
        $this->apiKey  = config('activos.api_key', '');
    }

    public function isConfigured(): bool
    {
        return !empty($this->baseUrl) && !empty($this->apiKey);
    }

    // ----------------------------------------------------------------
    // Helpers
    // ----------------------------------------------------------------

    private function client()
    {
        return Http::withHeaders([
            'X-API-Key' => $this->apiKey,
            'Accept'    => 'application/json',
        ])->timeout(15);
    }

    private function url(string $path): string
    {
        return $this->baseUrl . $path;
    }

    // ----------------------------------------------------------------
    // Public methods
    // ----------------------------------------------------------------

    /**
     * Equipos asignados a un usuario por nombre.
     * GET /api/v1/assigned-devices/{username}
     */
    public function getAssignedDevices(string $username): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        try {
            $response = $this->client()->get($this->url('/api/v1/assigned-devices/' . urlencode($username)));

            if ($response->successful()) {
                $data = $response->json();
                // API devuelve wrapper {success, data: [...]}
                return $data['data'] ?? (array_is_list($data ?? []) ? $data : []);
            }

            Log::warning('ActivosApi: assigned-devices no exitoso', [
                'status'   => $response->status(),
                'username' => $username,
            ]);
        } catch (\Exception $e) {
            Log::error('ActivosApi: getAssignedDevices error - ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Todos los equipos disponibles (sin asignación).
     * GET /api/v1/devices  →  filtra assignment === null
     */
    public function getAvailableDevices(): array
    {
        if (!$this->isConfigured()) {
            return [];
        }

        try {
            $response = $this->client()->get($this->url('/api/v1/devices'));

            if ($response->successful()) {
                $json = $response->json();
                // API devuelve wrapper {success, total, data: [...]}
                $all = $json['data'] ?? (array_is_list($json ?? []) ? $json : []);
                return array_values(
                    array_filter($all, fn ($d) => empty($d['assignment']))
                );
            }

            Log::warning('ActivosApi: devices no exitoso', ['status' => $response->status()]);
        } catch (\Exception $e) {
            Log::error('ActivosApi: getAvailableDevices error - ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Asignar un equipo o periférico a un usuario.
     * POST /api/v1/devices/{uuid}/assign
     */
    public function assignDevice(string $uuid, string $assignedTo, ?string $employeeId = null, string $notes = 'Asignado desde ERP'): array
    {
        if (!$this->isConfigured()) {
            return ['success' => false, 'message' => 'API de activos no configurada.'];
        }

        $payload = ['assigned_to' => $assignedTo, 'notes' => $notes];
        if ($employeeId) {
            $payload['employee_id'] = $employeeId;
        }

        try {
            $response = $this->client()->post($this->url("/api/v1/devices/{$uuid}/assign"), $payload);

            return [
                'success' => $response->successful(),
                'data'    => $response->json() ?? [],
                'status'  => $response->status(),
                'message' => $response->successful() ? 'OK' : ($response->json()['message'] ?? 'Error en API de activos'),
            ];
        } catch (\Exception $e) {
            Log::error('ActivosApi: assignDevice error - ' . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Descarga el binario de la foto de un equipo.
     * GET /api/v1/device-photos/{id}
     * Devuelve el cuerpo binario o null.
     */
    public function getDevicePhoto(int $id): ?string
    {
        if (!$this->isConfigured()) {
            return null;
        }

        try {
            $response = $this->client()->get($this->url("/api/v1/device-photos/{$id}"));
            if ($response->successful()) {
                return $response->body();
            }
        } catch (\Exception $e) {
            Log::error('ActivosApi: getDevicePhoto error - ' . $e->getMessage());
        }

        return null;
    }
}
