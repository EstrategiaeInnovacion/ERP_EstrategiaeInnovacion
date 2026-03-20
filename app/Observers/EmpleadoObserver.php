<?php

namespace App\Observers;

use App\Models\Empleado;
use App\Models\Recordatorio;

class EmpleadoObserver
{
    public function updated(Empleado $empleado): void
    {
        $this->actualizarRecordatorios($empleado);
    }

    private function actualizarRecordatorios(Empleado $empleado): void
    {
        $this->eliminarRecordatoriosAntiguos($empleado);

        if (!$empleado->es_activo) {
            return;
        }

        if ($empleado->fecha_nacimiento) {
            Recordatorio::generarCumpleaños($empleado);
        }

        if ($empleado->fecha_ingreso) {
            Recordatorio::generarAniversario($empleado);
        }

        if ($empleado->fecha_fin_contrato && $empleado->tipo_contrato !== 'Indeterminado') {
            Recordatorio::generarRecordatorioContrato($empleado);
        }
    }

    private function eliminarRecordatoriosAntiguos(Empleado $empleado): void
    {
        Recordatorio::where('empleado_id', $empleado->id)
            ->whereIn('tipo', [
                Recordatorio::TIPO_CUMPLEAÑOS,
                Recordatorio::TIPO_ANIVERSARIO,
            ])
            ->delete();

        Recordatorio::where('tabla_relacionada', 'empleados_contrato')
            ->where('registro_id', $empleado->id)
            ->delete();
    }
}
