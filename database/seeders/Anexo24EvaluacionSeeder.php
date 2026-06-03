<?php

namespace Database\Seeders;

use App\Models\CriterioEvaluacion;
use App\Models\Empleado;
use App\Models\Evaluacion;
use App\Models\EvaluacionDetalle;
use App\Models\EvaluacionVentana;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class Anexo24EvaluacionSeeder extends Seeder
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

    private array $comentariosAnexo24 = [
        'Cumplimiento de Actividades y Plan de Trabajo' => [
            'Cumple puntualmente con el plan de trabajo de pedimentos.',
            'Da seguimiento adecuado a las actividades del plan de trabajo.',
            'Realiza las actividades de pedimentos en tiempo y forma.',
        ],
        'Legislación Aplicable' => [
            'Aplica correctamente la legislación vigente en cada operación.',
            'Verifica el cumplimiento normativo en los registros del Anexo 24.',
            'Se mantiene actualizado en la normativa aplicable.',
        ],
        'Cumplimiento de Tiempos de Registro' => [
            'Registra las operaciones dentro de los tiempos establecidos.',
            'Cumple con los plazos de registro de manera consistente.',
            'Entrega los registros en los tiempos acordados.',
        ],
    ];

    private array $comentariosGenerales = [
        'Desempeño sobresaliente durante el periodo. Cumple con todos los objetivos establecidos y aporta valor al equipo.',
        'Buen desempeño, cumple con sus responsabilidades de manera consistente. Área de oportunidad en liderazgo de proyectos.',
        'Desempeño satisfactorio. Cumple con las tareas asignadas. Se recomienda fortalecer la comunicación con el equipo.',
    ];

    public function run(): void
    {
        $this->command->info('=== Anexo24EvaluacionSeeder ===');

        if (!Schema::hasColumn('evaluaciones', 'tipo')) {
            $this->command->warn('Columna tipo no existe, agregándola...');
            Schema::table('evaluaciones', function ($table) {
                $table->string('tipo', 20)->default('supervisor')->after('ventana_id');
            });
            $this->command->info('Columna tipo agregada.');
        }

        $this->updateAnexo24Criteria();

        $supervisor = $this->getAnexo24Supervisor();
        if (!$supervisor) {
            $this->command->warn('No se encontró supervisor de Anexo 24 con subordinados.');
            return;
        }

        $employees = $supervisor->subordinados->filter(fn($e) => $this->isAnexo24($e));
        if ($employees->isEmpty()) {
            $this->command->warn("El supervisor {$supervisor->nombre} no tiene subordinados en Anexo 24.");
            return;
        }

        $this->command->info("Supervisor: {$supervisor->nombre} (ID: {$supervisor->id})");
        $this->command->info("Empleados a evaluar: {$employees->pluck('nombre')->implode(', ')}");

        $periods = $this->getPeriods();
        $this->command->info("Periodos: " . implode(', ', $periods));

        $allCriteria = $this->getAllCriteria();

        foreach ($periods as $period) {
            $ventana = $this->getOrCreateVentana($period);
            foreach ($employees as $employee) {
                $this->createEvaluation($employee, $supervisor, $period, $ventana, $allCriteria);
            }
        }

        $this->command->info('=== Finalizado ===');
    }

    private function isAnexo24(Empleado $e): bool
    {
        return str_contains(mb_strtolower($e->posicion ?? '', 'UTF-8'), 'anexo 24')
            || str_contains(mb_strtolower($e->posicion ?? '', 'UTF-8'), 'anexo24');
    }

    private function updateAnexo24Criteria(): void
    {
        CriterioEvaluacion::where('area', 'Anexo 24')
            ->whereIn('criterio', [
                'Control de Inventarios (Anexo 24)',
                'Reporte de Descargos',
                'Conciliación de Saldos',
            ])
            ->delete();

        foreach ($this->anexo24HardSkills as $skill) {
            CriterioEvaluacion::updateOrCreate(
                ['area' => 'Anexo 24', 'criterio' => $skill['criterio']],
                ['descripcion' => $skill['descripcion'], 'peso' => $skill['peso']]
            );
        }

        $this->command->info('Criterios de Anexo 24 actualizados (3 nuevos, peso 20% c/u).');
    }

    private function getAnexo24Supervisor(): ?Empleado
    {
        return Empleado::whereHas('subordinados', function ($q) {
            $q->where('posicion', 'like', '%Anexo 24%')
              ->orWhere('posicion', 'like', '%anexo24%');
        })->first();
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

        $apertura = $isFirstSemester ? "{$periodYear}-01-01" : "{$periodYear}-07-01";
        $cierre = $isFirstSemester ? "{$periodYear}-06-30" : "{$periodYear}-12-31";

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

        foreach ($this->anexo24HardSkills as $hs) {
            $db = CriterioEvaluacion::where('area', 'Anexo 24')
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
            0 => [100, 75, 100, 75, 100, 75, 75, 100,   100, 100, 75],
            1 => [75, 100, 75, 50, 75, 75, 100, 75,    75, 75, 50],
            2 => [75, 50, 75, 75, 50, 100, 50, 75,    50, 75, 100],
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
                ?? $this->comentariosAnexo24[$criterioName]
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
