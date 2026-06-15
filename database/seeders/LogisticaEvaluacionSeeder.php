<?php

namespace Database\Seeders;

use App\Models\CriterioEvaluacion;
use App\Models\EvaluacionDetalle;
use Illuminate\Database\Seeder;

class LogisticaEvaluacionSeeder extends Seeder
{
    private array $logisticaHardSkills = [
        [
            'criterio' => 'Seguimiento de Operación por Prioridades',
            'descripcion' => 'Seguimiento de operación por prioridades.',
            'peso' => 10,
        ],
        [
            'criterio' => 'Optimización de Costos',
            'descripcion' => 'Optimización de costos de la operación.',
            'peso' => 10,
        ],
        [
            'criterio' => 'Prevención de Auditoría',
            'descripcion' => 'Prevención de Auditoria por la autoridad. Checklist documental.',
            'peso' => 10,
        ],
        [
            'criterio' => 'Conocimiento del Perfil del Cliente',
            'descripcion' => 'Conocer el perfil del cliente.',
            'peso' => 10,
        ],
        [
            'criterio' => 'Mejora Continua',
            'descripcion' => 'Implementación de metodología de mejora continua en la operación.',
            'peso' => 10,
        ],
        [
            'criterio' => 'Selección de Proveedores',
            'descripcion' => 'Seleccionar y mantener a los proveedores más eficientes y confiables para la operación.',
            'peso' => 10,
        ],
    ];

    private array $oldCriteria = [
        'Operatividad Import/Export',
        'Trato con Proveedores',
        'Mejora de Rutas',
    ];

    public function run(): void
    {
        $this->command->info('=== LogisticaEvaluacionSeeder ===');

        $this->replaceCriteria();

        $this->command->info('=== Finalizado ===');
    }

    private function replaceCriteria(): void
    {
        $oldIds = CriterioEvaluacion::where('area', 'Logistica')
            ->whereIn('criterio', $this->oldCriteria)
            ->pluck('id');

        if ($oldIds->isNotEmpty()) {
            EvaluacionDetalle::whereIn('criterio_id', $oldIds)->delete();
            CriterioEvaluacion::whereIn('id', $oldIds)->delete();
        }

        foreach ($this->logisticaHardSkills as $skill) {
            CriterioEvaluacion::updateOrCreate(
                ['area' => 'Logistica', 'criterio' => $skill['criterio']],
                ['descripcion' => $skill['descripcion'], 'peso' => $skill['peso']]
            );
        }

        $this->command->info('Criterios de Logística actualizados (6 nuevos, peso 10% c/u).');
    }
}
