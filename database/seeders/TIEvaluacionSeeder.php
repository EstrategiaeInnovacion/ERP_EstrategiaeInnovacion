<?php

namespace Database\Seeders;

use App\Models\CriterioEvaluacion;
use App\Models\EvaluacionDetalle;
use Illuminate\Database\Seeder;

class TIEvaluacionSeeder extends Seeder
{
    private array $newCriteria = [
        [
            'criterio' => 'Liberación de Tickets',
            'descripcion' => 'Atención y resolución oportuna de tickets de soporte.',
            'peso' => 10,
        ],
        [
            'criterio' => 'ERP Proyectos',
            'descripcion' => 'Implementación y seguimiento de proyectos en el ERP.',
            'peso' => 10,
        ],
        [
            'criterio' => 'Programa de mantenimiento',
            'descripcion' => 'Ejecución del programa de mantenimiento preventivo y correctivo.',
            'peso' => 8,
        ],
        [
            'criterio' => 'Habilitación de equipo, periféricos y credenciales al personal de nuevo ingreso en tiempo y forma',
            'descripcion' => 'Entrega y configuración oportuna de equipo y accesos para nuevo personal.',
            'peso' => 8,
        ],
        [
            'criterio' => 'Control de altas y bajas de usuarios',
            'descripcion' => 'Gestión de cuentas de usuario, altas, bajas y cambios en sistemas.',
            'peso' => 8,
        ],
        [
            'criterio' => 'Plan de actividades',
            'descripcion' => 'Cumplimiento del plan de actividades y proyectos asignados.',
            'peso' => 6,
        ],
        [
            'criterio' => 'Página web',
            'descripcion' => 'Mantenimiento y actualización de la página web institucional.',
            'peso' => 5,
        ],
        [
            'criterio' => 'Control de Licencias',
            'descripcion' => 'Administración y renovación de licencias de software.',
            'peso' => 5,
        ],
    ];

    private array $oldCriteria = [
        'Soporte a Usuarios',
        'Mantenimiento de Redes',
        'Desarrollo e Innovación',
    ];

    public function run(): void
    {
        $this->command->info('=== TIEvaluacionSeeder ===');

        $this->replaceCriteria();

        $this->command->info('=== Finalizado ===');
    }

    private function replaceCriteria(): void
    {
        $oldIds = CriterioEvaluacion::where('area', 'TI')
            ->whereIn('criterio', $this->oldCriteria)
            ->pluck('id');

        if ($oldIds->isNotEmpty()) {
            EvaluacionDetalle::whereIn('criterio_id', $oldIds)->delete();
            CriterioEvaluacion::whereIn('id', $oldIds)->delete();
            $this->command->info('Criterios viejos de TI eliminados.');
        }

        foreach ($this->newCriteria as $criterio) {
            CriterioEvaluacion::updateOrCreate(
                ['area' => 'TI', 'criterio' => $criterio['criterio']],
                ['descripcion' => $criterio['descripcion'], 'peso' => $criterio['peso']]
            );
        }

        $this->command->info('8 nuevos criterios de TI creados (60%).');
    }
}
