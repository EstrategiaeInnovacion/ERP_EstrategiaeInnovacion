<?php

namespace Database\Seeders;

use App\Models\CriterioEvaluacion;
use App\Models\EvaluacionDetalle;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class Anexo24EvaluacionSeeder extends Seeder
{
    private array $anexo24HardSkills = [
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
        'Control de Inventarios (Anexo 24)',
        'Reporte de Descargos',
        'Conciliación de Saldos',
    ];

    public function run(): void
    {
        $this->command->info('=== Anexo24EvaluacionSeeder ===');

        if (!Schema::hasColumn('evaluaciones', 'tipo')) {
            Schema::table('evaluaciones', function ($table) {
                $table->string('tipo', 20)->default('supervisor')->after('ventana_id');
            });
        }

        $this->replaceCriteria();

        $this->command->info('=== Finalizado ===');
    }

    private function replaceCriteria(): void
    {
        $oldIds = CriterioEvaluacion::where('area', 'Anexo 24')
            ->whereIn('criterio', $this->oldCriteria)
            ->pluck('id');

        if ($oldIds->isNotEmpty()) {
            EvaluacionDetalle::whereIn('criterio_id', $oldIds)->delete();
            CriterioEvaluacion::whereIn('id', $oldIds)->delete();
        }

        foreach ($this->anexo24HardSkills as $skill) {
            CriterioEvaluacion::updateOrCreate(
                ['area' => 'Anexo 24', 'criterio' => $skill['criterio']],
                ['descripcion' => $skill['descripcion'], 'peso' => $skill['peso']]
            );
        }

        $this->command->info('Criterios de Anexo 24 actualizados (3 nuevos, peso 20% c/u).');
    }
}
