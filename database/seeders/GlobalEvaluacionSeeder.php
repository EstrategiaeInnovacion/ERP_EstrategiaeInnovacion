<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CriterioEvaluacion;

class GlobalEvaluacionSeeder extends Seeder
{
    public function run()
    {
        // ==========================================
        // 1. SOFT SKILLS (COMPETENCIAS BLANDAS - RH)
        // ==========================================
        $softSkills = [
            ['criterio' => 'Iniciativa y Proactividad', 'descripcion' => 'Actúa sin supervisión constante.', 'peso' => 5],
            ['criterio' => 'Trabajo en Equipo', 'descripcion' => 'Colabora para alcanzar objetivos comunes.', 'peso' => 5],
            ['criterio' => 'Comunicación Efectiva', 'descripcion' => 'Transmite ideas de forma clara y respetuosa.', 'peso' => 5],
            ['criterio' => 'Actitud de Servicio', 'descripcion' => 'Disposición amable y profesional.', 'peso' => 5],
            ['criterio' => 'Adaptabilidad', 'descripcion' => 'Se ajusta a cambios en el entorno laboral.', 'peso' => 5],
            ['criterio' => 'Resolución de Problemas', 'descripcion' => 'Encuentra soluciones prácticas.', 'peso' => 5],
            ['criterio' => 'Ética Profesional', 'descripcion' => 'Comportamiento íntegro y honesto.', 'peso' => 5],
        ];

        // ==========================================
        // 2. HARD SKILLS (COMPETENCIAS TÉCNICAS POR ÁREA)
        // ==========================================
        $areasTecnicas = [
            'Logistica' => [], // Movido a LogisticaEvaluacionSeeder
            'Legal' => [], // Movido a LegalEvaluacionSeeder
            'Anexo 24' => [], // Movido a Anexo24EvaluacionSeeder
            'Post-Operacion' => [], // Movido a PostOperacionEvaluacionSeeder
            'TI' => [], // Movido a TIEvaluacionSeeder
            'Auditoria' => [], // Movido a AuditoriaEvaluacionSeeder
            'Pedimentos' => [
                ['criterio' => 'Captura de Pedimentos', 'descripcion' => 'Velocidad y precisión en la captura de datos.', 'peso' => 20],
                ['criterio' => 'Clasificación Arancelaria', 'descripcion' => 'Asignación correcta de fracciones.', 'peso' => 20],
                ['criterio' => 'Validación Previa', 'descripcion' => 'Revisión de documentos antes del pago.', 'peso' => 20],
            ],
            'Gestion RH' => [], // Movido a GestionRHEvaluacionSeeder
            'General' => [
                ['criterio' => 'Cumplimiento de Metas', 'descripcion' => 'Logro de los objetivos asignados al puesto.', 'peso' => 20],
                ['criterio' => 'Calidad en el Trabajo', 'descripcion' => 'Entregables libres de errores.', 'peso' => 20],
                ['criterio' => 'Organización', 'descripcion' => 'Orden y gestión adecuada del tiempo.', 'peso' => 20],
            ],
        ];

        // ==========================================
        // 3. EVALUACIÓN DE SUPERVISOR (Upward Feedback)
        // ==========================================
        $supervisorSkills = [
            ['criterio' => 'Liderazgo y Motivación', 'descripcion' => 'Inspira al equipo y reconoce logros.', 'peso' => 25],
            ['criterio' => 'Comunicación Clara', 'descripcion' => 'Da instrucciones precisas y escucha.', 'peso' => 25],
            ['criterio' => 'Apoyo al Desarrollo', 'descripcion' => 'Fomenta el crecimiento profesional del equipo.', 'peso' => 25],
            ['criterio' => 'Toma de Decisiones', 'descripcion' => 'Resuelve conflictos de manera justa y oportuna.', 'peso' => 25],
        ];

        // ==========================================
        // 4. EVALUACIÓN TRANSVERSAL (Administración RH)
        // ==========================================
        $evaluacionRH = [
            ['criterio' => 'Iniciativa y Proactividad', 'descripcion' => 'Actúa sin supervisión constante.', 'peso' => 12.5],
            ['criterio' => 'Trabajo en Equipo', 'descripcion' => 'Colabora para alcanzar objetivos comunes.', 'peso' => 12.5],
            ['criterio' => 'Comunicación Efectiva', 'descripcion' => 'Transmite ideas de forma clara y respetuosa.', 'peso' => 12.5],
            ['criterio' => 'Actitud de Servicio', 'descripcion' => 'Disposición amable y profesional.', 'peso' => 12.5],
            ['criterio' => 'Adaptabilidad', 'descripcion' => 'Se ajusta a cambios en el entorno laboral.', 'peso' => 12.5],
            ['criterio' => 'Resolución de Problemas', 'descripcion' => 'Encuentra soluciones prácticas.', 'peso' => 12.5],
            ['criterio' => 'Ética Profesional', 'descripcion' => 'Comportamiento íntegro y honesto.', 'peso' => 12.5],
        ];

        foreach ($softSkills as $skill) {
            CriterioEvaluacion::updateOrCreate(
                ['area' => 'Recursos Humanos', 'criterio' => $skill['criterio']],
                ['descripcion' => $skill['descripcion'], 'peso' => $skill['peso']]
            );
        }

        foreach ($areasTecnicas as $area => $criterios) {
            foreach ($criterios as $skill) {
                CriterioEvaluacion::updateOrCreate(
                    ['area' => $area, 'criterio' => $skill['criterio']],
                    ['descripcion' => $skill['descripcion'], 'peso' => $skill['peso']]
                );
            }
        }

        foreach ($supervisorSkills as $skill) {
            CriterioEvaluacion::updateOrCreate(
                ['area' => 'Evaluacion Supervisor', 'criterio' => $skill['criterio']],
                ['descripcion' => $skill['descripcion'], 'peso' => $skill['peso']]
            );
        }

        foreach ($evaluacionRH as $skill) {
            CriterioEvaluacion::updateOrCreate(
                ['area' => 'Administracion RH', 'criterio' => $skill['criterio']],
                ['descripcion' => $skill['descripcion'], 'peso' => $skill['peso']]
            );
        }
    }
}
