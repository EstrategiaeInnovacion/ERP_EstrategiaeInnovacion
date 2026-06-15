<?php

namespace Database\Seeders;

use App\Models\CriterioEvaluacion;
use App\Models\EvaluacionDetalle;
use Illuminate\Database\Seeder;

class AuditoriaEvaluacionSeeder extends Seeder
{
    private array $auditoriaHardSkills = [
        [
            'criterio' => 'Cumplimiento de Actividades y Plan de Trabajo',
            'descripcion' => 'Cumplimiento, seguimiento en tiempo y forma de las actividades y plan de trabajo de pedimentos de importación y exportación.',
            'peso' => 20,
        ],
        [
            'criterio' => 'Legislación Aplicable',
            'descripcion' => 'Cumplimiento de la legislación aplicable y vigente por cada una de las operaciones.',
            'peso' => 20,
        ],
        [
            'criterio' => 'Cumplimiento de Tiempos de Registro',
            'descripcion' => 'Cumplimiento de tiempos de registro de operaciones.',
            'peso' => 20,
        ],
    ];

    private array $oldCriteria = [
        'Detección de Riesgos',
        'Calidad de Informes',
        'Seguimiento a Hallazgos',
    ];

    public function run(): void
    {
        $this->command->info('=== AuditoriaEvaluacionSeeder ===');

        $this->replaceCriteria();

        $this->command->info('=== Finalizado ===');
    }

    private function replaceCriteria(): void
    {
        $oldIds = CriterioEvaluacion::where('area', 'Auditoria')
            ->whereIn('criterio', $this->oldCriteria)
            ->pluck('id');

        if ($oldIds->isNotEmpty()) {
            EvaluacionDetalle::whereIn('criterio_id', $oldIds)->delete();
            CriterioEvaluacion::whereIn('id', $oldIds)->delete();
        }

        foreach ($this->auditoriaHardSkills as $skill) {
            CriterioEvaluacion::updateOrCreate(
                ['area' => 'Auditoria', 'criterio' => $skill['criterio']],
                ['descripcion' => $skill['descripcion'], 'peso' => $skill['peso']]
            );
        }

        $this->command->info('Criterios de Auditoria actualizados (3 nuevos, peso 20% c/u).');
    }
}
