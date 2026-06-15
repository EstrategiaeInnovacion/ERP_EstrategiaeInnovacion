<?php

namespace App\Http\Controllers\Auditoria;

trait AuditoriaCoordinadorTrait
{
    private function esCoordinador($user): bool
    {
        if ($user->isAdmin()) {
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
        if (str_contains($posicion, 'supervisor') || str_contains($posicion, 'coordinador') || str_contains($posicion, 'jefe')) {
            return true;
        }

        return false;
    }
}
