<?php

namespace Database\Seeders;

use App\Models\CriterioEvaluacion;
use App\Models\Empleado;
use App\Models\Evaluacion;
use App\Models\EvaluacionDetalle;
use App\Models\EvaluacionVentana;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class LegalEvaluacionSeeder extends Seeder
{
    private array $softSkills = [
        ['criterio' => 'Puntualidad y Asistencia', 'descripcion' => 'Cumple con horarios y asistencia.', 'peso' => 5],
        ['criterio' => 'Iniciativa y Proactividad', 'descripcion' => 'Actúa sin supervisión constante.', 'peso' => 5],
        ['criterio' => 'Trabajo en Equipo', 'descripcion' => 'Colabora para alcanzar objetivos comunes.', 'peso' => 5],
        ['criterio' => 'Comunicación Efectiva', 'descripcion' => 'Transmite ideas de forma clara y respetuosa.', 'peso' => 5],
        ['criterio' => 'Actitud de Servicio', 'descripcion' => 'Disposición amable y profesional.', 'peso' => 5],
        ['criterio' => 'Adaptabilidad', 'descripcion' => 'Se ajusta a cambios en el entorno laboral.', 'peso' => 5],
        ['criterio' => 'Resolución de Problemas', 'descripcion' => 'Encuentra soluciones prácticas.', 'peso' => 5],
        ['criterio' => 'Ética Profesional', 'descripcion' => 'Comportamiento íntegro y honesto.', 'peso' => 5],
    ];

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

    private array $comentariosSoft = [
        'Puntualidad y Asistencia' => [
            'Siempre puntual y con asistencia perfecta.',
            'Asiste regularmente, algunos retrasos menores.',
            'Buena asistencia, ocasionalmente falta por motivos justificados.',
        ],
        'Iniciativa y Proactividad' => [
            'Toma la iniciativa en todos los proyectos.',
            'Actúa cuando se le indica, sin necesidad de seguimiento constante.',
            'Requiere orientación ocasional para iniciar tareas.',
        ],
        'Trabajo en Equipo' => [
            'Excelente colaborador, fomenta la cohesión del equipo.',
            'Trabaja bien en equipo, se integra sin problemas.',
            'Colabora cuando se le solicita.',
        ],
        'Comunicación Efectiva' => [
            'Comunicación clara y asertiva en todo momento.',
            'Se comunica adecuadamente con el equipo.',
            'Comunica lo necesario de forma clara.',
        ],
        'Actitud de Servicio' => [
            'Disposición excepcional para ayudar a los demás.',
            'Siempre dispuesto a apoyar cuando se le solicita.',
            'Actitud de servicio adecuada.',
        ],
        'Adaptabilidad' => [
            'Se adapta rápidamente a los cambios del entorno.',
            'Acepta los cambios y se ajusta sin problema.',
            'Se adapta a los cambios con apoyo.',
        ],
        'Resolución de Problemas' => [
            'Resuelve problemas complejos con rapidez y eficacia.',
            'Encuentra soluciones prácticas a los problemas.',
            'Resuelve problemas básicos adecuadamente.',
        ],
        'Ética Profesional' => [
            'Conducta ética ejemplar, íntegro y honesto.',
            'Actúa con ética y profesionalismo.',
            'Comportamiento ético adecuado.',
        ],
    ];

    private array $comentariosLegales = [
        'Programas de Cumplimiento Normativo' => [
            'Diseña programas robustos y actualizados.',
            'Implementa los programas conforme a la normativa.',
            'Apoya en la implementación de los programas.',
        ],
        'Diagnósticos de Cumplimiento' => [
            'Diagnósticos exhaustivos y precisos.',
            'Realiza diagnósticos completos y oportunos.',
            'Colabora en los diagnósticos de cumplimiento.',
        ],
        'Representación ante Autoridades' => [
            'Representación impecable ante autoridades.',
            'Buena gestión en procedimientos administrativos.',
            'Apoya en la representación ante autoridades.',
        ],
        'Opiniones Legales y Reportes de Riesgo' => [
            'Opiniones legales sólidas y bien fundamentadas.',
            'Elabora reportes de riesgo claros y completos.',
            'Colabora en la elaboración de reportes.',
        ],
    ];

    private array $comentariosGenerales = [
        'Desempeño sobresaliente durante el periodo. Cumple con todos los objetivos establecidos y aporta valor al equipo.',
        'Buen desempeño, cumple con sus responsabilidades de manera consistente. Área de oportunidad en liderazgo de proyectos.',
        'Desempeño satisfactorio. Cumple con las tareas asignadas. Se recomienda fortalecer la comunicación con el equipo.',
    ];

    public function run(): void
    {
        $this->command->info('=== LegalEvaluacionSeeder ===');

        // 1. Actualizar criterios de Legal
        $this->updateLegalCriteria();

        // 2. Encontrar supervisor de Legal
        $supervisor = $this->getLegalSupervisor();
        if (!$supervisor) {
            $this->command->warn('No se encontró supervisor de Legal con subordinados.');
            return;
        }

        $employees = $supervisor->subordinados;
        if ($employees->isEmpty()) {
            $this->command->warn("El supervisor {$supervisor->nombre} no tiene subordinados.");
            return;
        }

        $this->command->info("Supervisor: {$supervisor->nombre} (ID: {$supervisor->id})");
        $this->command->info("Empleados a evaluar: {$employees->pluck('nombre')->implode(', ')}");

        // 3. Generar periodos
        $periods = $this->getPeriods();
        $this->command->info("Periodos: " . implode(', ', $periods));

        // 4. Obtener criterios (soft + legal)
        $allCriteria = $this->getAllCriteria();

        // 5. Por cada periodo, crear evaluaciones
        foreach ($periods as $period) {
            $ventana = $this->getOrCreateVentana($period);
            foreach ($employees as $employee) {
                $this->createEvaluation($employee, $supervisor, $period, $ventana, $allCriteria);
            }
        }

        $this->command->info('=== Finalizado ===');
    }

    private function updateLegalCriteria(): void
    {
        // Eliminar criterios viejos de Legal (los que tenían peso 20)
        CriterioEvaluacion::where('area', 'Legal')
            ->whereIn('criterio', ['Elaboración de Contratos', 'Normatividad Vigente', 'Gestión de Litigios'])
            ->delete();

        // Insertar los 4 nuevos criterios
        foreach ($this->legalHardSkills as $skill) {
            CriterioEvaluacion::updateOrCreate(
                ['area' => 'Legal', 'criterio' => $skill['criterio']],
                ['descripcion' => $skill['descripcion'], 'peso' => $skill['peso']]
            );
        }

        $this->command->info('Criterios de Legal actualizados (4 nuevos, peso 15% c/u).');
    }

    private function getLegalSupervisor(): ?Empleado
    {
        return Empleado::where(function ($q) {
            $q->where('posicion', 'like', '%Legal%')
              ->orWhere('posicion', 'like', '%legal%')
              ->orWhere('posicion', 'like', '%Abogado%')
              ->orWhere('posicion', 'like', '%abogado%');
        })
        ->whereHas('subordinados')
        ->first();
    }

    private function getPeriods(): array
    {
        $year = Carbon::now()->year;
        return [
            ($year + 1) . ' | Enero - Junio',
            "$year | Julio - Diciembre",
            "$year | Enero - Junio",
            ($year - 1) . ' | Julio - Diciembre',
            ($year - 1) . ' | Enero - Junio',
        ];
    }

    private function getOrCreateVentana(string $period): ?EvaluacionVentana
    {
        $year = Carbon::now()->year;

        $isFirstSemester = str_contains($period, 'Enero');
        preg_match('/(\d{4})/', $period, $m);
        $periodYear = (int) ($m[1] ?? $year);

        if ($isFirstSemester) {
            $apertura = "{$periodYear}-01-01";
            $cierre = "{$periodYear}-06-30";
        } else {
            $apertura = "{$periodYear}-07-01";
            $cierre = "{$periodYear}-12-31";
        }

        $isCurrent = $periodYear === $year && (
            ($isFirstSemester && Carbon::now()->month <= 6) ||
            (!$isFirstSemester && Carbon::now()->month > 6)
        );

        return EvaluacionVentana::firstOrCreate(
            ['nombre' => $period],
            [
                'fecha_apertura' => $apertura,
                'fecha_cierre' => $cierre,
                'activo' => $isCurrent,
                'creado_por' => 1,
            ]
        );
    }

    private function getAllCriteria(): array
    {
        $criteria = [];

        foreach ($this->softSkills as $ss) {
            $db = CriterioEvaluacion::where('area', 'Recursos Humanos')
                ->where('criterio', $ss['criterio'])
                ->first();
            if ($db) {
                $criteria[] = ['model' => $db, 'peso' => $ss['peso']];
            }
        }

        foreach ($this->legalHardSkills as $hs) {
            $db = CriterioEvaluacion::where('area', 'Legal')
                ->where('criterio', $hs['criterio'])
                ->first();
            if ($db) {
                $criteria[] = ['model' => $db, 'peso' => $hs['peso']];
            }
        }

        return $criteria;
    }

    private function createEvaluation(
        Empleado $employee,
        Empleado $supervisor,
        string $period,
        ?EvaluacionVentana $ventana,
        array $criteria
    ): void {
        $employeeIndex = $employee->id % 3;
        $scoreMap = [
            0 => [75, 100, 75, 100, 100, 75, 100, 75,   100, 100, 75, 100],
            1 => [50, 75, 75, 50, 75, 75, 50, 75,    75, 75, 50, 75],
            2 => [75, 50, 50, 75, 50, 50, 75, 100,   50, 75, 75, 50],
        ];
        $scores = $scoreMap[$employeeIndex % 3];

        $totalWeight = 0;
        $weightedSum = 0;
        $detalles = [];

        foreach ($criteria as $i => $c) {
            $cal = $scores[$i];
            $weightedSum += $cal * $c['peso'];
            $totalWeight += $c['peso'];

            $criterioName = $c['model']->criterio;
            $commentPool = $this->comentariosSoft[$criterioName]
                ?? $this->comentariosLegales[$criterioName]
                ?? ['Comentario estándar.'];
            $comment = $commentPool[$employeeIndex % count($commentPool)];

            $detalles[] = [
                'criterio_id' => $c['model']->id,
                'calificacion' => $cal,
                'observaciones' => $comment,
            ];
        }

        $promedio = $totalWeight > 0 ? round($weightedSum / $totalWeight, 2) : 0;

        $evalData = [
            'empleado_id' => $employee->id,
            'evaluador_id' => $supervisor->user_id,
            'periodo' => $period,
            'ventana_id' => $ventana?->id,
            'tipo' => 'supervisor',
            'promedio_final' => $promedio,
            'comentarios_generales' => $this->comentariosGenerales[$employeeIndex % count($this->comentariosGenerales)],
            'edit_count' => 1,
        ];

        $evaluacion = Evaluacion::firstOrCreate(
            [
                'empleado_id' => $employee->id,
                'evaluador_id' => $supervisor->user_id,
                'ventana_id' => $ventana?->id,
                'tipo' => 'supervisor',
            ],
            $evalData
        );

        if ($evaluacion->wasRecentlyCreated) {
            foreach ($detalles as $det) {
                EvaluacionDetalle::create([
                    'evaluacion_id' => $evaluacion->id,
                    'criterio_id' => $det['criterio_id'],
                    'calificacion' => $det['calificacion'],
                    'observaciones' => $det['observaciones'],
                ]);
            }
            $this->command->line("  ✓ Evaluación creada: {$employee->nombre} ({$period}) = {$promedio}");
        } else {
            $this->command->line("  · Ya existe: {$employee->nombre} ({$period})");
        }
    }
}
