<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Compara las contraseñas del módulo "Contraseñas y Equipos" (BD ERP) contra la tabla
 * `credentials` de la BD de Activos.
 *
 * Regla de prevalencia: ERP gana. Si hay diferencia, se actualiza Activos con el valor del ERP.
 *
 * Uso:
 *   php artisan activos:sincronizar-contrasenas          <- solo reporte (dry-run)
 *   php artisan activos:sincronizar-contrasenas --apply  <- aplica los cambios en Activos
 */
class SincronizarContraseniasActivos extends Command
{
    protected $signature   = 'activos:sincronizar-contrasenas {--apply : Aplica los cambios en la BD de Activos} {--crear-faltantes : Crea en Activos los equipos que no tienen credencial (username sin contraseña)}';
    protected $description = 'Compara contraseñas ERP vs Activos. Actualiza Activos cuando difieren (ERP prevalece).';

    private int $totalEquipos   = 0;
    private int $sinDispActivos = 0;
    private int $iguales        = 0;
    private int $actualizados   = 0;
    private int $soloEnErp      = 0;  // Activos no tenía credencial → se crea
    private int $errores        = 0;

    // -----------------------------------------------------------------------

    public function handle(): int
    {
        $apply = $this->option('apply');

        $this->line('');
        $this->info('══════════════════════════════════════════════════════════');
        $this->info('  Sincronización de contraseñas: ERP → Activos');
        $this->info('══════════════════════════════════════════════════════════');

        if (! $apply) {
            $this->warn('  Modo DRY-RUN  →  no se modifica nada en la BD.');
            $this->warn('  Para aplicar cambios: --apply');
        } else {
            $this->error('  Modo APPLY  →  se escribirán cambios en la BD de Activos.');
        }

        $this->line('');

        if (! $this->confirmar($apply)) {
            $this->info('Operación cancelada.');
            return 0;
        }

        // Verificar conexión a activos
        try {
            DB::connection('activos')->getPdo();
        } catch (\Exception $e) {
            $this->error('No se puede conectar a la BD de Activos: ' . $e->getMessage());
            return 1;
        }

        $rows = [];
        $this->procesarEquipos($apply, $rows);

        $this->imprimirTabla($rows);
        $this->imprimirResumen();

        return 0;
    }

    // -----------------------------------------------------------------------

    private function confirmar(bool $apply): bool
    {
        if (! $apply) {
            return true; // dry-run siempre pasa
        }

        return $this->confirm('¿Confirmas que deseas aplicar los cambios en la BD de Activos?', false);
    }

    // -----------------------------------------------------------------------

    private function procesarEquipos(bool $apply, array &$rows): void
    {
        // Verificar si la columna contrasena_equipo aún existe en el ERP
        $columnaExiste = \Illuminate\Support\Facades\Schema::hasColumn('it_equipos_asignados', 'contrasena_equipo');

        if (! $columnaExiste) {
            $this->auditarSinColumna($rows);
            return;
        }

        // Cargar todos los equipos del ERP (principales y secundarios)
        $equipos = DB::table('it_equipos_asignados')
            ->whereNotNull('uuid_activos')
            ->get(['id', 'uuid_activos', 'nombre_usuario_pc', 'contrasena_equipo']);

        $this->totalEquipos = $equipos->count();
        $this->line("Equipos en ERP con uuid_activos: <info>{$this->totalEquipos}</info>");
        $this->line('');

        foreach ($equipos as $equipo) {
            $this->procesarUnEquipo($equipo, $apply, $rows);
        }
    }

    // -----------------------------------------------------------------------

    private function procesarUnEquipo(object $equipo, bool $apply, array &$rows): void
    {
        $uuid = $equipo->uuid_activos;

        // ── 1. Descifrar contraseña del ERP ──────────────────────────────
        $passErp = $this->descifrarErp($equipo->contrasena_equipo);

        // ── 2. Primer correo del equipo en ERP ───────────────────────────
        $correoErp = DB::table('it_equipos_correos')
            ->where('equipo_asignado_id', $equipo->id)
            ->orderBy('id')
            ->first(['correo', 'contrasena_correo']);

        $emailErp      = $correoErp?->correo ?? null;
        $emailPassErp  = $correoErp ? $this->descifrarErp($correoErp->contrasena_correo) : null;

        // ── 3. Buscar device en Activos por UUID ─────────────────────────
        $device = DB::connection('activos')
            ->table('devices')
            ->where('uuid', $uuid)
            ->first(['id', 'name']);

        if (! $device) {
            $this->sinDispActivos++;
            $rows[] = [
                'uuid'    => substr($uuid, 0, 12) . '…',
                'equipo'  => '(ID ERP: ' . $equipo->id . ')',
                'estado'  => 'SIN_DEVICE_EN_ACTIVOS',
                'detalle' => 'UUID no existe en Activos',
            ];
            return;
        }

        // ── 4. Credencial actual en Activos ──────────────────────────────
        $credActivos = DB::connection('activos')
            ->table('credentials')
            ->where('device_id', $device->id)
            ->first();

        $passActivos      = null;
        $emailActivos     = null;
        $emailPassActivos = null;

        if ($credActivos) {
            $passActivos      = $this->descifrarActivos($credActivos->password ?? null);
            $emailActivos     = $credActivos->email ?? null;
            $emailPassActivos = $this->descifrarActivos($credActivos->email_password ?? null);
        }

        // ── 5. Comparar ──────────────────────────────────────────────────
        $diffPass      = ($passErp !== $passActivos);
        $diffEmail     = ($emailErp !== $emailActivos);
        $diffEmailPass = ($emailPassErp !== $emailPassActivos);
        $diffUser      = (trim($equipo->nombre_usuario_pc ?? '') !== trim($credActivos->username ?? ''));

        $hayDiferencia = $diffPass || $diffEmail || $diffEmailPass || $diffUser;

        if (! $hayDiferencia && $credActivos) {
            $this->iguales++;
            $rows[] = [
                'uuid'    => substr($uuid, 0, 12) . '…',
                'equipo'  => $device->name,
                'estado'  => 'IGUAL',
                'detalle' => 'Sin diferencias',
            ];
            return;
        }

        // ── 6. Construir detalle de diferencias ──────────────────────────
        $diffs = [];
        if ($diffUser)      $diffs[] = 'usuario_pc';
        if ($diffPass)      $diffs[] = 'contrasena_equipo';
        if ($diffEmail)     $diffs[] = 'email';
        if ($diffEmailPass) $diffs[] = 'contrasena_correo';

        $estado = $credActivos ? 'DIFERENTE' : 'SOLO_EN_ERP';

        if (! $credActivos) {
            $this->soloEnErp++;
        } else {
            $this->actualizados++;
        }

        $rows[] = [
            'uuid'    => substr($uuid, 0, 12) . '…',
            'equipo'  => $device->name,
            'estado'  => $estado,
            'detalle' => implode(', ', $diffs),
        ];

        // ── 7. Aplicar cambio si --apply ─────────────────────────────────
        if ($apply) {
            $this->aplicarCambio($device->id, $credActivos, $equipo, $passErp, $emailErp, $emailPassErp);
        }
    }

    // -----------------------------------------------------------------------

    private function aplicarCambio(
        int $deviceId,
        ?object $credActivos,
        object $equipo,
        ?string $passErp,
        ?string $emailErp,
        ?string $emailPassErp
    ): void {
        try {
            $data = [
                'username'   => $equipo->nombre_usuario_pc ?? null,
                'email'      => $emailErp,
                'updated_at' => now(),
            ];

            if ($passErp !== null) {
                $data['password'] = encrypt($passErp);
            }
            if ($emailPassErp !== null) {
                $data['email_password'] = encrypt($emailPassErp);
            }

            if ($credActivos) {
                DB::connection('activos')
                    ->table('credentials')
                    ->where('id', $credActivos->id)
                    ->update($data);
            } else {
                $data['device_id']  = $deviceId;
                $data['created_at'] = now();
                DB::connection('activos')
                    ->table('credentials')
                    ->insert($data);
            }
        } catch (\Exception $e) {
            $this->errores++;
            Log::error("SincronizarContrasenas: error en device_id={$deviceId} — " . $e->getMessage());
            $this->warn("  Error al actualizar device_id={$deviceId}: " . $e->getMessage());
        }
    }

    // -----------------------------------------------------------------------

    /**
     * Modo de auditoría cuando la columna contrasena_equipo ya fue eliminada del ERP.
     * Muestra qué equipos tienen contraseña en Activos y cuáles no.
     */
    private function auditarSinColumna(array &$rows): void
    {
        $crearFaltantes = $this->option('crear-faltantes');

        $this->warn('');
        $this->warn('  ATENCIÓN: la columna contrasena_equipo ya no existe en el ERP.');
        $this->warn('  No es posible comparar contraseñas — se muestra el estado en Activos.');
        if ($crearFaltantes) {
            $this->warn('  Modo --crear-faltantes: se crearán registros sin contraseña para los que faltan.');
        }
        $this->line('');

        $equipos = DB::table('it_equipos_asignados')
            ->whereNotNull('uuid_activos')
            ->get(['id', 'uuid_activos', 'nombre_equipo', 'nombre_usuario_pc']);

        $this->totalEquipos = $equipos->count();

        if ($this->totalEquipos === 0) {
            $this->line('No hay equipos con uuid_activos en el ERP.');
            return;
        }

        $sinCredencial = 0;
        $conCredencial = 0;
        $creados       = 0;
        $errCreacion   = 0;

        foreach ($equipos as $equipo) {
            $device = null;
            try {
                $device = DB::connection('activos')
                    ->table('devices')
                    ->where('uuid', $equipo->uuid_activos)
                    ->first(['id', 'name']);
            } catch (\Exception) {}

            if (! $device) {
                $this->sinDispActivos++;
                $rows[] = [
                    'equipo_erp'   => $equipo->nombre_equipo,
                    'usuario_pc'   => $equipo->nombre_usuario_pc ?? '—',
                    'activos_name' => '— Sin device en Activos —',
                    'estado'       => '✗ Sin device',
                ];
                continue;
            }

            $cred = null;
            try {
                $cred = DB::connection('activos')
                    ->table('credentials')
                    ->where('device_id', $device->id)
                    ->first(['id', 'username', 'password']);
            } catch (\Exception) {}

            $tienePass = $cred && ! empty($cred->password);

            if ($tienePass) {
                $conCredencial++;
                $rows[] = [
                    'equipo_erp'   => $equipo->nombre_equipo,
                    'usuario_pc'   => $cred->username ?? $equipo->nombre_usuario_pc ?? '—',
                    'activos_name' => $device->name,
                    'estado'       => '✓ Con contraseña',
                ];
                continue;
            }

            // Sin contraseña — intentar crear/actualizar si se pidió
            $sinCredencial++;

            if ($crearFaltantes) {
                try {
                    if ($cred) {
                        // Existe el registro pero sin password → actualizar username
                        DB::connection('activos')
                            ->table('credentials')
                            ->where('id', $cred->id)
                            ->update([
                                'username'   => $equipo->nombre_usuario_pc,
                                'updated_at' => now(),
                            ]);
                        $estado = '⚡ Creado (sin contraseña — ingresar en UI)';
                    } else {
                        // No existe ningún registro → crear
                        DB::connection('activos')
                            ->table('credentials')
                            ->insert([
                                'device_id'  => $device->id,
                                'username'   => $equipo->nombre_usuario_pc,
                                'password'   => null,
                                'created_at' => now(),
                                'updated_at' => now(),
                            ]);
                        $estado = '⚡ Creado (sin contraseña — ingresar en UI)';
                    }
                    $creados++;
                } catch (\Exception $e) {
                    $errCreacion++;
                    $estado = '✗ Error al crear: ' . $e->getMessage();
                    Log::error("SincronizarContrasenas audit: error creando credential device_id={$device->id} — " . $e->getMessage());
                }
            } else {
                $estado = '✗ SIN contraseña — usar --crear-faltantes para añadir';
            }

            $rows[] = [
                'equipo_erp'   => $equipo->nombre_equipo,
                'usuario_pc'   => $equipo->nombre_usuario_pc ?? '—',
                'activos_name' => $device->name,
                'estado'       => $estado,
            ];
        }

        $this->line("Equipos en ERP              : <info>{$this->totalEquipos}</info>");
        $this->line("Con contraseña en Activos   : <info>{$conCredencial}</info>");
        $this->line("SIN contraseña              : <comment>{$sinCredencial}</comment>");
        $this->line("Sin device en Activos       : <comment>{$this->sinDispActivos}</comment>");
        if ($crearFaltantes) {
            $this->line("Registros creados/actualizados: <info>{$creados}</info>");
            if ($errCreacion > 0) {
                $this->line("Errores al crear            : <error>{$errCreacion}</error>");
            }
        }
        $this->line('');

        if ($sinCredencial > 0 && ! $crearFaltantes) {
            $this->warn("  {$sinCredencial} equipos sin contraseña. Para crearlos en Activos ejecuta:");
            $this->line('  <info>php artisan activos:sincronizar-contrasenas --crear-faltantes</info>');
        } elseif ($creados > 0) {
            $this->info("  {$creados} registro(s) creados en Activos con username.");
            $this->warn('  Ahora entra a Contraseñas y Equipos → Editar → ingresa la contraseña de cada equipo.');
        } elseif ($sinCredencial === 0) {
            $this->info('  Todos los equipos tienen contraseña en Activos.');
        }
        $this->line('');
    }

    private function imprimirTabla(array $rows): void
    {
        if (empty($rows)) {
            return;
        }

        // Detectar si es modo auditoría (sin columna) por las keys del primer row
        $isAudit = array_key_exists('equipo_erp', $rows[0]);

        if ($isAudit) {
            $this->table(
                ['Equipo ERP', 'Usuario PC', 'Nombre en Activos', 'Estado contraseña'],
                $rows
            );
        } else {
            $this->table(
                ['UUID (primeros 12)', 'Nombre en Activos', 'Estado', 'Campos con diferencia'],
                $rows
            );
        }
    }

    // -----------------------------------------------------------------------

    private function imprimirResumen(): void
    {
        // En modo auditoría (sin columna) el resumen ya se imprimió en auditarSinColumna()
        if (! \Illuminate\Support\Facades\Schema::hasColumn('it_equipos_asignados', 'contrasena_equipo')) {
            return;
        }

        $this->line('');
        $this->info('──────────── RESUMEN ────────────');
        $this->line("Equipos procesados   : <info>{$this->totalEquipos}</info>");
        $this->line("Sin device en Activos: <comment>{$this->sinDispActivos}</comment>");
        $this->line("Iguales (sin cambio) : <info>{$this->iguales}</info>");
        $this->line("Solo en ERP (crear)  : <comment>{$this->soloEnErp}</comment>");
        $this->line("Diferentes (actualiz): <comment>{$this->actualizados}</comment>");
        if ($this->errores > 0) {
            $this->line("Errores              : <error>{$this->errores}</error>");
        }
        $this->line('─────────────────────────────────');
        $this->line('');

        if ($this->sinDispActivos > 0) {
            $this->warn("  {$this->sinDispActivos} equipo(s) tienen uuid_activos que no existe en la BD de Activos.");
            $this->warn('  Revisa que los UUID sean correctos o que el dispositivo exista en Activos.');
        }

        $totalCambios = $this->soloEnErp + $this->actualizados;
        if ($totalCambios > 0 && ! $this->option('apply')) {
            $this->line('');
            $this->warn("  {$totalCambios} registro(s) serían actualizados/creados en Activos.");
            $this->warn('  Ejecuta con --apply para aplicar los cambios:');
            $this->line('  <info>php artisan activos:sincronizar-contrasenas --apply</info>');
        }

        if ($totalCambios === 0 && $this->totalEquipos > 0) {
            $this->info('  Ambas bases de datos están sincronizadas.');
        }
    }

    // -----------------------------------------------------------------------
    // Helpers de descifrado
    // -----------------------------------------------------------------------

    private function descifrarErp(?string $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        try {
            return Crypt::decryptString($valor);
        } catch (\Exception) {
            // Podría ser texto plano (registros anteriores al mutador)
            return $valor;
        }
    }

    private function descifrarActivos(?string $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        try {
            return decrypt($valor);
        } catch (\Exception) {
            return $valor;
        }
    }
}
