<?php
// Patch: agrega createDevice y updateDevice a ActivosDbService

$file    = 'app/Services/ActivosDbService.php';
$content = file_get_contents($file);

// Insertar antes del cierre de clase — buscar la última '}'
$lastBrace = strrpos($content, "\n}");

$methods = <<<'PHPCODE'

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

PHPCODE;

$newContent = substr($content, 0, $lastBrace) . $methods . "\n}";
file_put_contents($file, $newContent);
echo "OK\n";
