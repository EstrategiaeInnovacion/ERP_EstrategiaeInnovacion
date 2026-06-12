<?php

namespace Database\Seeders;

use App\Models\CriterioEvaluacion;
use App\Models\EvaluacionDetalle;
use Illuminate\Database\Seeder;

class GestionRHEvaluacionSeeder extends Seeder
{
    private array $newCriteria = [
        [
            'criterio' => 'Control de vacaciones y permisos',
            'descripcion' => 'Gestión y control de solicitudes de vacaciones, permisos e incidencias del personal.',
            'peso' => 12,
        ],
        [
            'criterio' => 'Tiempo para cubrir la vacante',
            'descripcion' => 'Velocidad y eficacia en la cobertura de vacantes.',
            'peso' => 10,
        ],
        [
            'criterio' => 'Onboarding',
            'descripcion' => 'Proceso de integración y bienvenida de nuevo personal.',
            'peso' => 10,
        ],
        [
            'criterio' => 'Integración de expedientes',
            'descripcion' => 'Integración completa y ordenada del expediente del colaborador.',
            'peso' => 9,
        ],
        [
            'criterio' => 'Expediente virtual de personal de baja',
            'descripcion' => 'Gestión y resguardo del expediente virtual del personal dado de baja.',
            'peso' => 8,
        ],
        [
            'criterio' => 'Actividades sociales y de comunidad',
            'descripcion' => 'Organización y participación en eventos sociales y comunitarios.',
            'peso' => 6,
        ],
        [
            'criterio' => 'Incumplimiento de normas sanitarias',
            'descripcion' => 'Supervisión del cumplimiento de las normas de salud e higiene.',
            'peso' => 5,
        ],
    ];

    private array $oldCriteria = [
        'Reclutamiento Efectivo',
        'Administración de Personal',
        'Desarrollo Organizacional',
    ];

    public function run(): void
    {
        $this->command->info('=== GestionRHEvaluacionSeeder ===');

        $this->replaceCriteria();

        $this->command->info('=== Finalizado ===');
    }

    private function replaceCriteria(): void
    {
        $oldIds = CriterioEvaluacion::where('area', 'Gestion RH')
            ->whereIn('criterio', $this->oldCriteria)
            ->pluck('id');

        if ($oldIds->isNotEmpty()) {
            EvaluacionDetalle::whereIn('criterio_id', $oldIds)->delete();
            CriterioEvaluacion::whereIn('id', $oldIds)->delete();
            $this->command->info('Criterios viejos de Gestion RH eliminados.');
        }

        foreach ($this->newCriteria as $criterio) {
            CriterioEvaluacion::updateOrCreate(
                ['area' => 'Gestion RH', 'criterio' => $criterio['criterio']],
                ['descripcion' => $criterio['descripcion'], 'peso' => $criterio['peso']]
            );
        }

        $this->command->info('7 nuevos criterios de Gestion RH creados (60%).');
    }
}
