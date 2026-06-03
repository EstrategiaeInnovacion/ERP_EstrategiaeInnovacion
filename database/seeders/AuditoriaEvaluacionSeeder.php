<?php

namespace Database\Seeders;

use App\Models\CriterioEvaluacion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

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

    public function run(): void
    {
        $this->command->info('=== AuditoriaEvaluacionSeeder (solo criterios) ===');

        if (!Schema::hasColumn('evaluaciones', 'tipo')) {
            Schema::table('evaluaciones', function ($table) {
                $table->string('tipo', 20)->default('supervisor')->after('ventana_id');
            });
        }

        $this->updateAuditoriaCriteria();

        $this->command->info('=== Finalizado ===');
    }

    private function updateAuditoriaCriteria(): void
    {
        CriterioEvaluacion::where('area', 'Auditoria')
            ->whereIn('criterio', [
                'Detección de Riesgos',
                'Calidad de Informes',
                'Seguimiento a Hallazgos',
            ])
            ->orWhere(function ($q) {
                $q->where('area', 'Auditoria')->where('criterio', 'like', 'Cumplimiento%');
            })
            ->delete();

        foreach ($this->auditoriaHardSkills as $skill) {
            CriterioEvaluacion::updateOrCreate(
                ['area' => 'Auditoria', 'criterio' => $skill['criterio']],
                ['descripcion' => $skill['descripcion'], 'peso' => $skill['peso']]
            );
        }

        $this->command->info('Criterios de Auditoria actualizados (3 nuevos, peso 20% c/u).');
    }
}
