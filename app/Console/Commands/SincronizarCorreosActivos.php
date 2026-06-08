<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Migra los correos y sus contraseñas de la BD del ERP hacia la tabla
 * `credentials` de la BD de Activos.
 *
 * Reglas:
 *  - 0 correos   → sin acción
 *  - 1 correo    → migra directamente
 *  - 2+ correos  → muestra lista; con --apply migra solo el primero y avisa
 *
 * Uso:
 *   php artisan activos:sincronizar-correos            ← solo reporte (dry-run)
 *   php artisan activos:sincronizar-correos --apply    ← aplica cambios en Activos
 */
class SincronizarCorreosActivos extends Command
{
    protected $signature   = 'activos:sincronizar-correos {--apply : Aplica los cambios en la BD de Activos}';
    protected $description = 'Migra correos y contraseñas de correo del ERP → Activos. Identifica equipos con múltiples correos.';

    // -----------------------------------------------------------------------

    public function handle(): int
    {
        $apply = $this->option('apply');

        $this->line('');
        $this->info('══════════════════════════════════════════════════════════');
        $this->info('  Migración de correos: ERP → Activos');
        $this->info('══════════════════════════════════════════════════════════');

        if (! $apply) {
            $this->warn('  Modo DRY-RUN  →  no se modifica nada en la BD.');
            $this->warn('  Para aplicar cambios: --apply');
        } else {
            $this->warn('  Modo APPLY  →  se escribirán cambios en la BD de Activos.');
        }

        $this->line('');

        try {
            DB::connection('activos')->getPdo();
        } catch (\Exception $e) {
            $this->error('No se puede conectar a la BD de Activos: ' . $e->getMessage());
            return 1;
        }

        $this->procesar($apply);

        return 0;
    }

    // -----------------------------------------------------------------------

    private function procesar(bool $apply): void
    {
        $equipos = DB::table('it_equipos_asignados')
            ->whereNotNull('uuid_activos')
            ->get(['id', 'uuid_activos', 'nombre_equipo', 'nombre_usuario_pc']);

        if ($equipos->isEmpty()) {
            $this->line('No hay equipos con uuid_activos en el ERP.');
            return;
        }

        // ── Separar por cantidad de correos ──────────────────────────────────
        $sinCorreo   = [];
        $unCorreo    = [];
        $masDeUno    = [];

        foreach ($equipos as $equipo) {
            $correos = DB::table('it_equipos_correos')
                ->where('equipo_asignado_id', $equipo->id)
                ->orderBy('id')
                ->get(['id', 'correo']);

            $equipo->correos = $correos;

            if ($correos->count() === 0) {
                $sinCorreo[] = $equipo;
            } elseif ($correos->count() === 1) {
                $unCorreo[] = $equipo;
            } else {
                $masDeUno[] = $equipo;
            }
        }

        // ── Resumen de distribución ─────────────────────────────────────────
        $this->line('Distribución de correos por equipo:');
        $this->line("  Sin correo     : <comment>" . count($sinCorreo) . "</comment>");
        $this->line("  Un correo      : <info>"    . count($unCorreo)  . "</info>");
        $this->line("  Múltiples (2+) : <comment>" . count($masDeUno)  . "</comment>");
        $this->line('');

        // ── Reporte de equipos con múltiples correos ────────────────────────
        if (! empty($masDeUno)) {
            $this->warn('  Equipos con MÁS DE UN correo (se migrará solo el primero):');
            $this->line('');

            $rowsMulti = [];
            foreach ($masDeUno as $equipo) {
                foreach ($equipo->correos as $i => $correo) {
                    $rowsMulti[] = [
                        'equipo'  => $i === 0 ? $equipo->nombre_equipo : '',
                        'num'     => '#' . ($i + 1),
                        'correo'  => $correo->correo,
                        'migrar'  => $i === 0 ? '✓ Se migrará' : '✗ Se omitirá',
                    ];
                }
            }

            $this->table(
                ['Equipo', '#', 'Correo', 'Acción'],
                $rowsMulti
            );
            $this->line('');
        }

        // ── Procesar equipos con un solo correo ─────────────────────────────
        $this->line('Estado de equipos con un correo:');
        $rowsUno = [];
        $stats   = ['igual' => 0, 'distinto' => 0, 'nuevo' => 0, 'sin_device' => 0, 'error' => 0];

        foreach (array_merge($unCorreo, $masDeUno) as $equipo) {
            $correo = $equipo->correos->first();  // siempre el primero

            $result = $this->procesarEquipo($equipo, $correo, $apply, $stats);
            $rowsUno[] = $result;
        }

        if (! empty($rowsUno)) {
            $this->table(
                ['Equipo ERP', 'Correo ERP', 'Estado en Activos', 'Acción'],
                $rowsUno
            );
        }

        $this->line('');
        $this->info('──────────── RESUMEN ────────────');
        $this->line("Iguales (sin cambio)    : <info>{$stats['igual']}</info>");
        $this->line("Diferentes → actualizados: <comment>{$stats['distinto']}</comment>");
        $this->line("Nuevos (no existían)    : <comment>{$stats['nuevo']}</comment>");
        $this->line("Sin device en Activos   : <comment>{$stats['sin_device']}</comment>");
        if ($stats['error'] > 0) {
            $this->line("Errores                 : <error>{$stats['error']}</error>");
        }
        $this->line('─────────────────────────────────');

        $totalCambios = $stats['distinto'] + $stats['nuevo'];
        if ($totalCambios > 0 && ! $apply) {
            $this->line('');
            $this->warn("  {$totalCambios} registro(s) se actualizarían en Activos.");
            $this->warn('  Ejecuta con --apply para aplicar:');
            $this->line('  <info>php artisan activos:sincronizar-correos --apply</info>');
        } elseif ($totalCambios === 0 && $apply) {
            $this->info('  Todos los correos ya estaban sincronizados.');
        }

        $this->line('');
    }

    // -----------------------------------------------------------------------

    private function procesarEquipo(object $equipo, object $correo, bool $apply, array &$stats): array
    {
        // Buscar device en Activos
        try {
            $device = DB::connection('activos')
                ->table('devices')
                ->where('uuid', $equipo->uuid_activos)
                ->first(['id', 'name']);
        } catch (\Exception) {
            $stats['error']++;
            return [$equipo->nombre_equipo, $correo->correo, 'Error de conexión', '✗ Error'];
        }

        if (! $device) {
            $stats['sin_device']++;
            return [$equipo->nombre_equipo, $correo->correo, '— Sin device en Activos —', '✗ Sin device'];
        }

        // Credencial actual en Activos
        try {
            $credActivos = DB::connection('activos')
                ->table('credentials')
                ->where('device_id', $device->id)
                ->first(['id', 'email', 'email_password']);
        } catch (\Exception) {
            $stats['error']++;
            return [$equipo->nombre_equipo, $correo->correo, $device->name, '✗ Error al leer Activos'];
        }

        // Solo comparar dirección de correo (contrasena_correo ya no existe en ERP)
        $emailActivos = $credActivos?->email ?? null;
        $hayDiff      = ($correo->correo !== $emailActivos);

        if (! $hayDiff) {
            $stats['igual']++;
            return [$equipo->nombre_equipo, $correo->correo, $device->name, '✓ Igual'];
        }

        // Hay diferencia o es nuevo
        if ($credActivos === null) {
            $stats['nuevo']++;
            $accion = $apply ? '⚡ Creado' : '+ Crear';
        } else {
            $stats['distinto']++;
            $accion = $apply ? '⚡ Actualizado' : '≠ Actualizar';
        }

        if ($apply) {
            $this->aplicarCambio($device->id, $credActivos, $correo->correo);
        }

        return [$equipo->nombre_equipo, $correo->correo, $device->name, $accion];
    }

    // -----------------------------------------------------------------------

    private function aplicarCambio(int $deviceId, ?object $credActivos, string $email): void
    {
        try {
            // Solo actualiza el campo email; la contraseña de correo ya está en Activos
            // y no se puede obtener del ERP (columna eliminada)
            $data = [
                'email'      => $email,
                'updated_at' => now(),
            ];

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
            Log::error("SincronizarCorreos: error en device_id={$deviceId} — " . $e->getMessage());
            $this->warn("  Error al actualizar device_id={$deviceId}: " . $e->getMessage());
        }
    }

    // -----------------------------------------------------------------------

    private function descifrarErp(?string $valor): ?string
    {
        if ($valor === null || $valor === '') {
            return null;
        }
        try {
            return Crypt::decryptString($valor);
        } catch (\Exception) {
            return $valor; // texto plano (registros anteriores al mutador)
        }
    }
}
