<?php

namespace Database\Seeders;

use App\Models\CriterioEvaluacion;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

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

    public function run(): void
    {
        $this->command->info('=== LegalEvaluacionSeeder (solo criterios) ===');

        if (!Schema::hasColumn('evaluaciones', 'tipo')) {
            Schema::table('evaluaciones', function ($table) {
                $table->string('tipo', 20)->default('supervisor')->after('ventana_id');
            });
        }

        $this->updateLegalCriteria();

        $this->command->info('=== Finalizado ===');
    }

    private function updateLegalCriteria(): void
    {
        CriterioEvaluacion::where('area', 'Legal')
            ->whereIn('criterio', ['Elaboración de Contratos', 'Normatividad Vigente', 'Gestión de Litigios'])
            ->orWhere(function ($q) {
                $q->where('area', 'Legal')->where('criterio', 'like', 'Programas%');
            })
            ->delete();

        foreach ($this->legalHardSkills as $skill) {
            CriterioEvaluacion::updateOrCreate(
                ['area' => 'Legal', 'criterio' => $skill['criterio']],
                ['descripcion' => $skill['descripcion'], 'peso' => $skill['peso']]
            );
        }

        $this->command->info('Criterios de Legal actualizados (4 nuevos, peso 15% c/u).');
    }
}
