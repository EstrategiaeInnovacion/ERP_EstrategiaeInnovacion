<?php

namespace App\Http\Controllers\Auditoria;

trait AuditoriaCoordinadorTrait
{
    private function esCoordinador($user): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        // Si es el coordinador asignado a cualquier proyecto de auditoría, se le trata como coordinador
        if (\App\Models\Auditoria\ProyectoAuditoria::where('coordinador_id', $user->id)->exists()) {
            return true;
        }

        $empleado = $user->empleado;
        if (!$empleado) {
            return false;
        }

        if ($empleado->es_coordinador) {
            return true;
        }

        $esSupervisor = \App\Models\Empleado::where('supervisor_id', $empleado->id)->exists();
        if ($esSupervisor) {
            return true;
        }

        $posicion = mb_strtolower($empleado->posicion ?? '');
        $terminosPermitidos = [
            'supervisor', 'supervisora', 'supervisión', 'supervision',
            'coordinador', 'coordinadora', 'coordinación', 'coordinacion',
            'jefe', 'jefa', 'gerente', 'director', 'directora', 'dirección', 'direccion',
            'lider', 'líder'
        ];

        foreach ($terminosPermitidos as $termino) {
            if (str_contains($posicion, $termino)) {
                return true;
            }
        }

        return false;
    }
}
