<?php

namespace Database\Seeders;

use App\Models\CriterioEvaluacion;
use App\Models\EvaluacionDetalle;
use Illuminate\Database\Seeder;

class LegalEvaluacionSeeder extends Seeder
{
    private array $legalHardSkills = [
        [
            'criterio' => 'Programas de Cumplimiento Normativo',
            'descripcion' => 'Diseñar e implementar programas de cumplimiento normativo para empresas con operaciones de importación/exportación.',
            'peso' => 15,
        ],
        [
            'criterio' => 'Diagnósticos de Cumplimiento',
            'descripcion' => 'Realizar diagnósticos de cumplimiento en materia de comercio exterior.',
            'peso' => 15,
        ],
        [
            'criterio' => 'Representación ante Autoridades',
            'descripcion' => 'Representar a clientes ante autoridades aduaneras y regulatorias en procedimientos administrativos o litigios.',
            'peso' => 15,
        ],
        [
            'criterio' => 'Opiniones Legales y Reportes de Riesgo',
            'descripcion' => 'Elaborar opiniones legales y reportes de riesgo.',
            'peso' => 15,
        ],
    ];

    private array $oldCriteria = [
        'Elaboración de Contratos',
        'Normatividad Vigente',
        'Gestión de Litigios',
    ];

    public function run(): void
    {
        $this->command->info('=== LegalEvaluacionSeeder ===');

        $this->replaceCriteria();

        $this->command->info('=== Finalizado ===');
    }

    private function replaceCriteria(): void
    {
        $oldIds = CriterioEvaluacion::where('area', 'Legal')
            ->whereIn('criterio', $this->oldCriteria)
            ->pluck('id');

        if ($oldIds->isNotEmpty()) {
            EvaluacionDetalle::whereIn('criterio_id', $oldIds)->delete();
            CriterioEvaluacion::whereIn('id', $oldIds)->delete();
        }

        foreach ($this->legalHardSkills as $skill) {
            CriterioEvaluacion::updateOrCreate(
                ['area' => 'Legal', 'criterio' => $skill['criterio']],
                ['descripcion' => $skill['descripcion'], 'peso' => $skill['peso']]
            );
        }

        $this->command->info('Criterios de Legal actualizados (4 nuevos, peso 15% c/u).');
    }
}
