<?php

namespace Database\Seeders;

use App\Models\CriterioEvaluacion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class PostOperacionEvaluacionSeeder extends Seeder
{
    private array $postOpHardSkills = [
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
            'criterio' => 'Cumplimiento de Tiempos Operativos',
            'descripcion' => 'Cumplimiento de tiempos operaciones (virtuales, cambios de régimen, proyectos).',
            'peso' => 20,
        ],
    ];

    public function run(): void
    {
        $this->command->info('=== PostOperacionEvaluacionSeeder (solo criterios) ===');

        if (!Schema::hasColumn('evaluaciones', 'tipo')) {
            Schema::table('evaluaciones', function ($table) {
                $table->string('tipo', 20)->default('supervisor')->after('ventana_id');
            });
        }

        $this->updatePostOpCriteria();

        $this->command->info('=== Finalizado ===');
    }

    private function updatePostOpCriteria(): void
    {
        CriterioEvaluacion::where('area', 'Post-Operacion')
            ->whereIn('criterio', [
                'Integración de Expedientes',
                'Auditoría Preventiva',
                'Atención al Cliente',
            ])
            ->delete();

        foreach ($this->postOpHardSkills as $skill) {
            CriterioEvaluacion::updateOrCreate(
                ['area' => 'Post-Operacion', 'criterio' => $skill['criterio']],
                ['descripcion' => $skill['descripcion'], 'peso' => $skill['peso']]
            );
        }

        $this->command->info('Criterios de Post-Operación actualizados (3 nuevos, peso 20% c/u).');
    }
}
