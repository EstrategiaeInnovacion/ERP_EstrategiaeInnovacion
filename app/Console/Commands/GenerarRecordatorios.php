<?php

namespace App\Console\Commands;

use App\Models\Empleado;
use App\Models\EmpleadoDocumento;
use App\Models\Recordatorio;
use Illuminate\Console\Command;
use Carbon\Carbon;

class GenerarRecordatorios extends Command
{
    protected $signature = 'rh:generar-recordatorios {--dias=30 : Días de anticipación}';
    protected $description = 'Genera recordatorios para cumpleaños, aniversarios laborales y vencimientos de documentos';

    public function handle(): int
    {
        $this->info('Generando recordatorios de RRHH...');
        $dias = (int) $this->option('dias');

        $this->info('Limpiando recordatorios antiguos...');
        $this->limpiarAntiguos();

        $contador = 0;

        $this->info('Procesando cumpleaños...');
        $contador += $this->procesarCumpleaños();

        $this->info('Procesando aniversarios laborales...');
        $contador += $this->procesarAniversarios();

        $this->info('Procesando documentos por vencer...');
        $contador += $this->procesarDocumentos();

        $this->info('Procesando vencimientos de contrato...');
        $contador += $this->procesarContratos();

        $this->info("Se generaron {$contador} recordatorios.");

        return Command::SUCCESS;
    }

    private function limpiarAntiguos(): void
    {
        $limiteFuturo = Carbon::today()->addDays(30);
        $limitePasado = Carbon::today()->subDays(30);

        Recordatorio::whereIn('tipo', [
                Recordatorio::TIPO_CUMPLEAÑOS,
                Recordatorio::TIPO_ANIVERSARIO,
            ])
            ->where(function ($q) use ($limiteFuturo, $limitePasado) {
                $q->where('fecha_evento', '>', $limiteFuturo)
                  ->orWhere('fecha_evento', '<', $limitePasado);
            })
            ->delete();

        Recordatorio::whereIn('tipo', [
                Recordatorio::TIPO_DOCUMENTO_VENCIDO,
                Recordatorio::TIPO_DOCUMENTO_VENCER,
                Recordatorio::TIPO_CONTRATO_VENCER,
            ])
            ->where('fecha_evento', '<', $limitePasado)
            ->delete();

        $this->info('  - Limpieza completada');
    }

    private function procesarCumpleaños(): int
    {
        $contador = 0;

        $empleados = Empleado::whereNotNull('fecha_nacimiento')
            ->where('es_activo', true)
            ->get();

        foreach ($empleados as $empleado) {
            try {
                $resultado = Recordatorio::generarCumpleaños($empleado);
                if ($resultado) {
                    $contador++;
                }
            } catch (\Exception $e) {
                $this->warn("Error con empleado {$empleado->id}: {$e->getMessage()}");
            }
        }

        $this->info("  - {$contador} recordatorios de cumpleaños");
        return $contador;
    }

    private function procesarAniversarios(): int
    {
        $contador = 0;

        $empleados = Empleado::whereNotNull('fecha_ingreso')
            ->where('es_activo', true)
            ->get();

        foreach ($empleados as $empleado) {
            try {
                $resultado = Recordatorio::generarAniversario($empleado);
                if ($resultado) {
                    $contador++;
                }
            } catch (\Exception $e) {
                $this->warn("Error con empleado {$empleado->id}: {$e->getMessage()}");
            }
        }

        $this->info("  - {$contador} recordatorios de aniversarios");
        return $contador;
    }

    private function procesarDocumentos(): int
    {
        $contador = 0;

        $hoy = Carbon::today();
        $limite = Carbon::today()->addDays(60);

        $documentos = EmpleadoDocumento::whereNotNull('fecha_vencimiento')
            ->whereDate('fecha_vencimiento', '>=', $hoy)
            ->whereDate('fecha_vencimiento', '<=', $limite)
            ->with('empleado')
            ->get();

        foreach ($documentos as $documento) {
            if (!$documento->empleado) {
                continue;
            }

            try {
                $resultado = Recordatorio::generarRecordatorioDocumento($documento);
                if ($resultado) {
                    $contador++;
                }
            } catch (\Exception $e) {
                $this->warn("Error con documento {$documento->id}: {$e->getMessage()}");
            }
        }

        $documentosVencidos = EmpleadoDocumento::whereNotNull('fecha_vencimiento')
            ->whereDate('fecha_vencimiento', '<', $hoy)
            ->with('empleado')
            ->get();

        foreach ($documentosVencidos as $documento) {
            if (!$documento->empleado) {
                continue;
            }

            try {
                $existe = Recordatorio::where('tabla_relacionada', 'empleado_documentos')
                    ->where('registro_id', $documento->id)
                    ->where('tipo', Recordatorio::TIPO_DOCUMENTO_VENCIDO)
                    ->first();

                if (!$existe) {
                    Recordatorio::generarRecordatorioDocumento($documento);
                    $contador++;
                }
            } catch (\Exception $e) {
                $this->warn("Error con documento vencido {$documento->id}: {$e->getMessage()}");
            }
        }

        $this->info("  - {$contador} recordatorios de documentos");
        return $contador;
    }

    private function procesarContratos(): int
    {
        $contador = 0;

        $hoy = Carbon::today();
        $limite = Carbon::today()->addDays(60);

        $empleados = Empleado::whereNotNull('fecha_fin_contrato')
            ->where('es_activo', true)
            ->where('tipo_contrato', '!=', 'Indeterminado')
            ->where(function ($query) use ($hoy, $limite) {
                $query->whereDate('fecha_fin_contrato', '>=', $hoy)
                    ->whereDate('fecha_fin_contrato', '<=', $limite);
            })
            ->get();

        foreach ($empleados as $empleado) {
            try {
                $resultado = Recordatorio::generarRecordatorioContrato($empleado);
                if ($resultado) {
                    $contador++;
                }
            } catch (\Exception $e) {
                $this->warn("Error con contrato de {$empleado->id}: {$e->getMessage()}");
            }
        }

        $contratosVencidos = Empleado::whereNotNull('fecha_fin_contrato')
            ->where('es_activo', true)
            ->where('tipo_contrato', '!=', 'Indeterminado')
            ->whereDate('fecha_fin_contrato', '<', $hoy)
            ->get();

        foreach ($contratosVencidos as $empleado) {
            try {
                $existe = Recordatorio::where('tabla_relacionada', 'empleados_contrato')
                    ->where('registro_id', $empleado->id)
                    ->where('tipo', Recordatorio::TIPO_DOCUMENTO_VENCIDO)
                    ->first();

                if (!$existe) {
                    Recordatorio::generarRecordatorioContrato($empleado);
                    $contador++;
                }
            } catch (\Exception $e) {
                $this->warn("Error con contrato vencido de {$empleado->id}: {$e->getMessage()}");
            }
        }

        $this->info("  - {$contador} recordatorios de contratos");
        return $contador;
    }
}
