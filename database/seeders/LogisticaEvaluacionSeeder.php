<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\CriterioEvaluacion; // IMPORTANTE: Importar el modelo
use Carbon\Carbon;

class LogisticaEvaluacionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // ==========================================
        // 1. SOFT SKILLS (COMPETENCIAS BLANDAS - RH)
        // ==========================================
        $softSkills = [
            ['criterio' => 'Puntualidad y Asistencia', 'descripcion' => 'Cumple con horarios y asistencia.', 'peso' => 5],
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
            'Logistica' => [ 
                ['criterio' => 'Operatividad Import/Export', 'descripcion' => 'Seguimiento puntual de operaciones logísticas.', 'peso' => 20],
                ['criterio' => 'Trato con Proveedores', 'descripcion' => 'Negociación efectiva con transportistas y agentes.', 'peso' => 20],
                ['criterio' => 'Mejora de Rutas', 'descripcion' => 'Optimización de costos y tiempos de entrega.', 'peso' => 20],
            ],
            'Legal' => [ 
                ['criterio' => 'Elaboración de Contratos', 'descripcion' => 'Redacción precisa y legalmente blindada.', 'peso' => 20],
                ['criterio' => 'Normatividad Vigente', 'descripcion' => 'Aplicación correcta de leyes actuales.', 'peso' => 20],
                ['criterio' => 'Gestión de Litigios', 'descripcion' => 'Seguimiento oportuno a casos legales.', 'peso' => 20],
            ],
            'Anexo 24' => [ 
                ['criterio' => 'Control de Inventarios (Anexo 24)', 'descripcion' => 'Exactitud en el registro de entradas y salidas.', 'peso' => 20],
                ['criterio' => 'Reporte de Descargos', 'descripcion' => 'Generación correcta de reportes mensuales.', 'peso' => 20],
                ['criterio' => 'Conciliación de Saldos', 'descripcion' => 'Validación de datos contra glosa (DataStage).', 'peso' => 20],
            ],
            'Post-Operacion' => [ 
                ['criterio' => 'Integración de Expedientes', 'descripcion' => 'Expedientes digitales y físicos completos.', 'peso' => 20],
                ['criterio' => 'Auditoría Preventiva', 'descripcion' => 'Revisión de pedimentos post-despacho.', 'peso' => 20],
                ['criterio' => 'Atención al Cliente', 'descripcion' => 'Resolución de dudas sobre operaciones cerradas.', 'peso' => 20],
            ],
            'TI' => [ 
                ['criterio' => 'Soporte a Usuarios', 'descripcion' => 'Atención rápida y efectiva a tickets.', 'peso' => 20],
                ['criterio' => 'Mantenimiento de Redes', 'descripcion' => 'Estabilidad y seguridad de la infraestructura.', 'peso' => 20],
                ['criterio' => 'Desarrollo e Innovación', 'descripcion' => 'Implementación de nuevas herramientas tecnológicas.', 'peso' => 20],
            ],
            'Auditoria' => [ 
                ['criterio' => 'Detección de Riesgos', 'descripcion' => 'Identificación proactiva de irregularidades.', 'peso' => 20],
                ['criterio' => 'Calidad de Informes', 'descripcion' => 'Reportes claros, objetivos y basados en evidencia.', 'peso' => 20],
                ['criterio' => 'Seguimiento a Hallazgos', 'descripcion' => 'Verificación del cierre de no conformidades.', 'peso' => 20],
            ],
            'Pedimentos' => [ 
                ['criterio' => 'Captura de Pedimentos', 'descripcion' => 'Velocidad y precisión en la captura de datos.', 'peso' => 20],
                ['criterio' => 'Clasificación Arancelaria', 'descripcion' => 'Asignación correcta de fracciones.', 'peso' => 20],
                ['criterio' => 'Validación Previa', 'descripcion' => 'Revisión de documentos antes del pago.', 'peso' => 20],
            ],
            'Gestion RH' => [ // Competencias TÉCNICAS para el personal de RH (Liliana, Mariana)
                ['criterio' => 'Reclutamiento Efectivo', 'descripcion' => 'Cobertura de vacantes en tiempo y forma.', 'peso' => 20],
                ['criterio' => 'Administración de Personal', 'descripcion' => 'Manejo impecable de incidencias y nómina.', 'peso' => 20],
                ['criterio' => 'Desarrollo Organizacional', 'descripcion' => 'Ejecución de planes de capacitación y clima.', 'peso' => 20],
            ],
            'General' => [ // Fallback para puestos no especificados
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
        // PROCESO DE INSERCIÓN SEGURA (UpdateOrCreate)
        // ==========================================
        
        // 1. Soft Skills (RH)
        foreach ($softSkills as $skill) {
            CriterioEvaluacion::updateOrCreate(
                ['area' => 'Recursos Humanos', 'criterio' => $skill['criterio']], 
                ['descripcion' => $skill['descripcion'], 'peso' => $skill['peso']]
            );
        }

        // 2. Hard Skills (Técnicas)
        foreach ($areasTecnicas as $area => $criterios) {
            foreach ($criterios as $skill) {
                CriterioEvaluacion::updateOrCreate(
                    ['area' => $area, 'criterio' => $skill['criterio']],
                    ['descripcion' => $skill['descripcion'], 'peso' => $skill['peso']]
                );
            }
        }

        // 3. Supervisor Skills
        foreach ($supervisorSkills as $skill) {
            CriterioEvaluacion::updateOrCreate(
                ['area' => 'Evaluación Supervisor', 'criterio' => $skill['criterio']],
                ['descripcion' => $skill['descripcion'], 'peso' => $skill['peso']]
            );
        }
    }
}