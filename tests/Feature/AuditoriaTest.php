<?php
 
namespace Tests\Feature;
 
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Empleado;
use App\Models\Administracion\Cliente;
use App\Models\Administracion\PerfilCliente;
use App\Models\Auditoria\ProyectoAuditoria;
use App\Models\Auditoria\ActividadAuditoria;
use App\Models\Auditoria\CambioPropuesto;
 
class AuditoriaTest extends TestCase
{
    use RefreshDatabase;
 
    protected User $coordinator;
    protected User $analyst;
    protected Cliente $cliente;
 
    protected function setUp(): void
    {
        parent::setUp();
 
        // 1. Crear usuarios y empleados
        $this->coordinator = User::factory()->create([
            'role' => 'admin',
            'status' => 'approved',
        ]);
        $empCoord = Empleado::create([
            'user_id' => $this->coordinator->id,
            'nombre' => 'Coordinador Auditoria',
            'correo' => $this->coordinator->email,
            'area' => 'Auditoría',
            'posicion' => 'Coordinador',
            'es_coordinador' => true,
        ]);
 
        $this->analyst = User::factory()->create([
            'role' => 'user',
            'status' => 'approved',
        ]);
        $empAnalyst = Empleado::create([
            'user_id' => $this->analyst->id,
            'nombre' => 'Analista Auditoria',
            'correo' => $this->analyst->email,
            'area' => 'Auditoría',
            'posicion' => 'Analista',
        ]);
 
        // 2. Crear Cliente y Perfil
        $this->cliente = Cliente::create([
            'nombre' => 'Cliente de Prueba S.A.',
            'empresa' => 'Cliente de Prueba S.A.',
            'contacto' => 'Juan Pérez',
            'correo' => 'juan@prueba.com',
            'telefono' => '1234567890',
        ]);
        PerfilCliente::create([
            'cliente_id' => $this->cliente->id,
            'informante_nombre' => 'Juan Pérez',
        ]);
    }
 
    /**
     * Prueba que el coordinador puede crear un proyecto con procesos base.
     */
    public function test_coordinator_can_create_project(): void
    {
        $response = $this->actingAs($this->coordinator)->post(route('auditoria.proyectos.store'), [
            'cliente_id' => $this->cliente->id,
            'cliente_nombre' => $this->cliente->nombre,
            'periodo_fiscal' => '2025',
            'analista_id' => $this->analyst->id,
            'cantidad_expedientes' => 120,
            'fecha_inicio' => '2026-06-10',
            'fecha_entrega_estimada' => '2026-12-10',
        ]);
 
        $response->assertRedirect();
        
        $this->assertDatabaseHas('auditoria_proyectos', [
            'cliente_id' => $this->cliente->id,
            'cliente_nombre' => $this->cliente->nombre,
            'periodo_fiscal' => '2025',
            'analista_id' => $this->analyst->id,
            'coordinador_id' => $this->coordinator->id,
            'estatus_general' => 'pendiente',
        ]);
 
        // Verificar que no se crearon procesos base por defecto
        $proyecto = ProyectoAuditoria::first();
        $this->assertCount(0, $proyecto->actividades);
    }
 
    /**
     * Prueba de recálculo de avance (rollup) en subprocesos y proyectos.
     */
    public function test_progress_recalculation_rollup(): void
    {
        // Crear proyecto manual
        $proyecto = ProyectoAuditoria::create([
            'cliente_id' => $this->cliente->id,
            'periodo_fiscal' => '2025',
            'coordinador_id' => $this->coordinator->id,
            'analista_id' => $this->analyst->id,
            'fecha_inicio' => '2026-06-10',
            'fecha_entrega_estimada' => '2026-12-10',
            'fases_config' => ['Fase 1'],
        ]);
 
        // Proceso 1 (con subprocesos)
        $proceso1 = ActividadAuditoria::create([
            'proyecto_id' => $proyecto->id,
            'padre_id' => null,
            'actividad' => 'Proceso Principal 1',
            'es_proceso_principal' => true,
        ]);
        $sub1 = ActividadAuditoria::create([
            'proyecto_id' => $proyecto->id,
            'padre_id' => $proceso1->id,
            'actividad' => 'Subproceso 1.1',
            'porcentaje_oficial' => 80,
            'es_proceso_principal' => false,
        ]);
        $sub2 = ActividadAuditoria::create([
            'proyecto_id' => $proyecto->id,
            'padre_id' => $proceso1->id,
            'actividad' => 'Subproceso 1.2',
            'porcentaje_oficial' => 40,
            'es_proceso_principal' => false,
        ]);
 
        // Proceso 2 (sin subprocesos)
        $proceso2 = ActividadAuditoria::create([
            'proyecto_id' => $proyecto->id,
            'padre_id' => null,
            'actividad' => 'Proceso Principal 2',
            'porcentaje_oficial' => 30,
            'es_proceso_principal' => true,
        ]);
 
        // Asignar estatus iniciales a los hijos
        $sub1->update(['estatus_oficial' => 'en proceso']);
        $sub2->update(['estatus_oficial' => 'pendiente']);

        // Recalcular
        $proyecto->recalcularPorcentajes();
  
        // Proceso 1 debe promediar sus hijos: (80 + 40) / 2 = 60%
        $proceso1->refresh();
        $this->assertEquals(60, $proceso1->porcentaje_oficial);
        $this->assertEquals('en proceso', $proceso1->estatus_oficial);
  
        // Proyecto debe promediar procesos de nivel superior: (60 + 30) / 2 = 45%
        $proyecto->refresh();
        $this->assertEquals(45.00, $proyecto->porcentaje_general_aprobado);

        // Ahora cerramos todos los hijos
        $sub1->update(['estatus_oficial' => 'cerrado', 'porcentaje_oficial' => 100]);
        $sub2->update(['estatus_oficial' => 'cerrado', 'porcentaje_oficial' => 100]);

        $proyecto->recalcularPorcentajes();
        $proceso1->refresh();

        // El proceso principal debe tener estatus_oficial como 'cerrado'
        $this->assertEquals('cerrado', $proceso1->estatus_oficial);
        $this->assertEquals(100, $proceso1->porcentaje_oficial);
    }
 
    /**
     * Prueba que el analista puede proponer cambios y guardarlos como borrador o enviarlos.
     */
    public function test_analyst_can_propose_changes(): void
    {
        $proyecto = ProyectoAuditoria::create([
            'cliente_id' => $this->cliente->id,
            'periodo_fiscal' => '2025',
            'coordinador_id' => $this->coordinator->id,
            'analista_id' => $this->analyst->id,
            'fecha_inicio' => '2026-06-10',
            'fecha_entrega_estimada' => '2026-12-10',
            'fases_config' => ['Fase 1'],
        ]);
 
        $actividad = ActividadAuditoria::create([
            'proyecto_id' => $proyecto->id,
            'padre_id' => null,
            'actividad' => 'Proceso 1',
            'porcentaje_oficial' => 10,
            'estatus_oficial' => 'pendiente',
            'es_proceso_principal' => true,
        ]);
 
        // Guardar Borrador
        $response1 = $this->actingAs($this->analyst)->post(route('auditoria.proyectos.cambios.store', $proyecto->id), [
            'actividad_id' => $actividad->id,
            'porcentaje_propuesto' => 50,
            'estatus_propuesto' => 'en proceso',
            'comentario_propuesto' => 'Explicación del borrador',
            'enviar' => false, // borrador
        ]);
        $response1->assertJson(['success' => true]);
 
        $this->assertDatabaseHas('auditoria_cambios_propuestos', [
            'actividad_id' => $actividad->id,
            'porcentaje_propuesto' => 50,
            'estatus_revision' => 'borrador',
        ]);
 
        // Enviar a Revisión
        $propuesta = CambioPropuesto::first();
        $response2 = $this->actingAs($this->analyst)->post(route('auditoria.proyectos.cambios.enviar', $proyecto->id), [
            'cambio_id' => $propuesta->id,
        ]);
        $response2->assertJson(['success' => true]);
 
        $this->assertDatabaseHas('auditoria_cambios_propuestos', [
            'id' => $propuesta->id,
            'estatus_revision' => 'pendiente',
        ]);
 
        // Verificar que el avance oficial sigue siendo 10%, pero el interno es 50%
        $proyecto->refresh();
        $actividad->refresh();
        $this->assertEquals(10, $actividad->porcentaje_oficial);
        $this->assertEquals(10.00, $proyecto->porcentaje_general_aprobado);
        $this->assertEquals(50.00, $proyecto->porcentaje_general_interno);
    }
 
    /**
     * Prueba que el coordinador puede aprobar propuestas y actualizan la matriz oficial.
     */
    public function test_coordinator_can_approve_changes(): void
    {
        $proyecto = ProyectoAuditoria::create([
            'cliente_id' => $this->cliente->id,
            'periodo_fiscal' => '2025',
            'coordinador_id' => $this->coordinator->id,
            'analista_id' => $this->analyst->id,
            'fecha_inicio' => '2026-06-10',
            'fecha_entrega_estimada' => '2026-12-10',
            'fases_config' => ['Fase 1'],
        ]);
 
        $actividad = ActividadAuditoria::create([
            'proyecto_id' => $proyecto->id,
            'padre_id' => null,
            'actividad' => 'Proceso 1',
            'porcentaje_oficial' => 10,
            'estatus_oficial' => 'pendiente',
            'es_proceso_principal' => true,
        ]);
 
        $propuesta = CambioPropuesto::create([
            'actividad_id' => $actividad->id,
            'proyecto_id' => $proyecto->id,
            'user_id' => $this->analyst->id,
            'tipo_cambio' => 'update_activity',
            'estatus_propuesto' => 'cerrado',
            'porcentaje_propuesto' => 100,
            'comentario_propuesto' => 'Completado todo el análisis',
            'estatus_revision' => 'pendiente',
        ]);
 
        // Aprobar cambio
        $response = $this->actingAs($this->coordinator)->post(route('auditoria.proyectos.cambios.revisar', $proyecto->id), [
            'cambio_id' => $propuesta->id,
            'accion' => 'aprobar',
        ]);
        $response->assertJson(['success' => true]);
 
        // Verificar cambios oficiales
        $actividad->refresh();
        $proyecto->refresh();
        $this->assertEquals(100, $actividad->porcentaje_oficial);
        $this->assertEquals('cerrado', $actividad->estatus_oficial);
        $this->assertEquals(100.00, $proyecto->porcentaje_general_aprobado);
 
        // Verificar comentarios del historial
        $this->assertDatabaseHas('auditoria_comentarios', [
            'actividad_id' => $actividad->id,
            'comentario' => 'Completado todo el análisis',
        ]);
    }

    /**
     * Prueba que el coordinador puede actualizar directamente el porcentaje sin pasar por revisión (auto-aprobación).
     */
    public function test_coordinator_can_directly_update_percentage(): void
    {
        $proyecto = ProyectoAuditoria::create([
            'cliente_id' => $this->cliente->id,
            'periodo_fiscal' => '2025',
            'coordinador_id' => $this->coordinator->id,
            'analista_id' => $this->analyst->id,
            'fecha_inicio' => '2026-06-10',
            'fecha_entrega_estimada' => '2026-12-10',
            'fases_config' => ['Fase 1'],
        ]);

        $actividad = ActividadAuditoria::create([
            'proyecto_id' => $proyecto->id,
            'padre_id' => null,
            'actividad' => 'Proceso 1',
            'porcentaje_oficial' => 10,
            'estatus_oficial' => 'pendiente',
            'es_proceso_principal' => true,
        ]);

        // Guardar y Enviar como Coordinador
        $response = $this->actingAs($this->coordinator)->post(route('auditoria.proyectos.cambios.store', $proyecto->id), [
            'actividad_id' => $actividad->id,
            'porcentaje_propuesto' => 90,
            'estatus_propuesto' => 'en proceso',
            'comentario_propuesto' => 'Actualización directa del Coordinador',
            'enviar' => true, // Enviar (debería auto-aprobarse)
        ]);

        $response->assertJson(['success' => true]);

        // El cambio debe estar registrado como 'aprobado'
        $this->assertDatabaseHas('auditoria_cambios_propuestos', [
            'actividad_id' => $actividad->id,
            'porcentaje_propuesto' => 90,
            'estatus_revision' => 'aprobado',
            'revisado_por' => $this->coordinator->id,
        ]);

        // Y la actividad debe haberse actualizado directamente
        $actividad->refresh();
        $this->assertEquals(90, $actividad->porcentaje_oficial);
        $this->assertEquals('en proceso', $actividad->estatus_oficial);

        $proyecto->refresh();
        $this->assertEquals(90.00, $proyecto->porcentaje_general_aprobado);
    }

    /**
     * Prueba que el analista puede proponer procesos/subprocesos en borrador y luego enviarlos todos en masa a revisión.
     */
    public function test_analyst_can_bulk_submit_drafts(): void
    {
        $proyecto = ProyectoAuditoria::create([
            'cliente_id' => $this->cliente->id,
            'periodo_fiscal' => '2025',
            'coordinador_id' => $this->coordinator->id,
            'analista_id' => $this->analyst->id,
            'fecha_inicio' => '2026-06-10',
            'fecha_entrega_estimada' => '2026-12-10',
            'fases_config' => ['Fase 1'],
        ]);

        $actividad = ActividadAuditoria::create([
            'proyecto_id' => $proyecto->id,
            'padre_id' => null,
            'actividad' => 'Proceso Existente',
            'porcentaje_oficial' => 10,
            'estatus_oficial' => 'pendiente',
            'es_proceso_principal' => true,
        ]);

        $actividad2 = ActividadAuditoria::create([
            'proyecto_id' => $proyecto->id,
            'padre_id' => null,
            'actividad' => 'Otro Proceso Existente',
            'porcentaje_oficial' => 0,
            'estatus_oficial' => 'pendiente',
            'es_proceso_principal' => true,
        ]);

        // 1. Crear un borrador de actualización de porcentaje
        $this->actingAs($this->analyst)->post(route('auditoria.proyectos.cambios.store', $proyecto->id), [
            'actividad_id' => $actividad->id,
            'porcentaje_propuesto' => 50,
            'estatus_propuesto' => 'en proceso',
            'comentario_propuesto' => 'Avanzando el proceso existente',
            'enviar' => false,
        ]);

        // 2. Crear un borrador de sugerir proceso
        $this->actingAs($this->analyst)->post(route('auditoria.proyectos.actividades.store', $proyecto->id), [
            'actividad' => 'Sugerencia de Proceso Principal',
            'responsable' => 'E&I',
            'plazo' => '2026-12-10',
        ]);

        // 3. Crear un borrador de sugerir subproceso bajo el segundo proceso
        $this->actingAs($this->analyst)->post(route('auditoria.proyectos.actividades.store', $proyecto->id), [
            'actividad' => 'Sugerencia de Subproceso',
            'responsable' => $this->cliente->nombre,
            'padre_id' => $actividad2->id,
            'plazo' => '2026-12-10',
        ]);

        // Verificar que hay 3 borradores en la base de datos
        $this->assertDatabaseCount('auditoria_cambios_propuestos', 3);
        $this->assertEquals(3, CambioPropuesto::where('estatus_revision', 'borrador')->count());

        // 4. Enviar todos los borradores a revisión
        $response = $this->actingAs($this->analyst)->post(route('auditoria.proyectos.cambios.enviar_todos', $proyecto->id));
        $response->assertJson(['success' => true]);

        // Verificar que todos los borradores del analista cambiaron a pendiente
        $this->assertEquals(0, CambioPropuesto::where('estatus_revision', 'borrador')->count());
        $this->assertEquals(3, CambioPropuesto::where('estatus_revision', 'pendiente')->count());

        // 5. El coordinador aprueba todo el paquete
        $responseApprove = $this->actingAs($this->coordinator)->post(route('auditoria.proyectos.cambios.revisar_paquete', $proyecto->id));
        $responseApprove->assertJson(['success' => true]);

        // Todos los cambios deben estar aprobados
        $this->assertEquals(3, CambioPropuesto::where('estatus_revision', 'aprobado')->count());

        // Verificar que se crearon los nuevos procesos en la tabla oficial
        $this->assertDatabaseHas('auditoria_actividades', [
            'proyecto_id' => $proyecto->id,
            'actividad' => 'Sugerencia de Proceso Principal',
            'responsable' => 'E&I',
            'es_proceso_principal' => true,
        ]);

        $this->assertDatabaseHas('auditoria_actividades', [
            'proyecto_id' => $proyecto->id,
            'padre_id' => $actividad2->id,
            'actividad' => 'Sugerencia de Subproceso',
            'responsable' => $this->cliente->nombre,
            'es_proceso_principal' => false,
        ]);

        // El porcentaje de la actividad original debe ser ahora 50%
        $actividad->refresh();
        $this->assertEquals(50, $actividad->porcentaje_oficial);
    }

    /**
     * Prueba que el coordinador puede eliminar un proyecto y es redirigido al dashboard.
     */
    public function test_coordinator_can_delete_project(): void
    {
        $proyecto = ProyectoAuditoria::create([
            'cliente_id' => $this->cliente->id,
            'periodo_fiscal' => '2025',
            'coordinador_id' => $this->coordinator->id,
            'analista_id' => $this->analyst->id,
            'fecha_inicio' => '2026-06-10',
            'fecha_entrega_estimada' => '2026-12-10',
            'fases_config' => ['Fase 1'],
        ]);

        $response = $this->actingAs($this->coordinator)->delete(route('auditoria.proyectos.destroy', $proyecto->id));
        
        $response->assertRedirect(route('auditoria.dashboard'));
        $this->assertSoftDeleted($proyecto); // Or assertDatabaseMissing depending on soft deletes configuration, let's check
    }

    /**
     * Prueba que un analista no puede eliminar un proyecto (abort 403).
     */
    public function test_analyst_cannot_delete_project(): void
    {
        $proyecto = ProyectoAuditoria::create([
            'cliente_id' => $this->cliente->id,
            'periodo_fiscal' => '2025',
            'coordinador_id' => $this->coordinator->id,
            'analista_id' => $this->analyst->id,
            'fecha_inicio' => '2026-06-10',
            'fecha_entrega_estimada' => '2026-12-10',
            'fases_config' => ['Fase 1'],
        ]);

        $response = $this->actingAs($this->analyst)->delete(route('auditoria.proyectos.destroy', $proyecto->id));
        
        $response->assertStatus(403);
        $this->assertDatabaseHas('auditoria_proyectos', ['id' => $proyecto->id]);
    }

    /**
     * Prueba que se puede proponer un avance sin comentario (comentario opcional).
     */
    public function test_propose_change_without_comment_is_successful(): void
    {
        $proyecto = ProyectoAuditoria::create([
            'cliente_id' => $this->cliente->id,
            'periodo_fiscal' => '2025',
            'coordinador_id' => $this->coordinator->id,
            'analista_id' => $this->analyst->id,
            'fecha_inicio' => '2026-06-10',
            'fecha_entrega_estimada' => '2026-12-10',
            'fases_config' => ['Fase 1'],
        ]);

        $actividad = ActividadAuditoria::create([
            'proyecto_id' => $proyecto->id,
            'padre_id' => null,
            'actividad' => 'Proceso 1',
            'porcentaje_oficial' => 10,
            'estatus_oficial' => 'pendiente',
            'es_proceso_principal' => true,
        ]);

        $response = $this->actingAs($this->analyst)->post(route('auditoria.proyectos.cambios.store', $proyecto->id), [
            'actividad_id' => $actividad->id,
            'porcentaje_propuesto' => 50,
            'estatus_propuesto' => 'en proceso',
            'comentario_propuesto' => null, // Opcional
            'enviar' => true,
        ]);

        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('auditoria_cambios_propuestos', [
            'actividad_id' => $actividad->id,
            'porcentaje_propuesto' => 50,
            'comentario_propuesto' => null,
        ]);
    }

    /**
     * Prueba que proponer un avance con comentario marcado como importante guarda el flag y se propaga al comentario aprobado.
     */
    public function test_propose_change_with_important_comment_is_saved_and_highlighted(): void
    {
        $proyecto = ProyectoAuditoria::create([
            'cliente_id' => $this->cliente->id,
            'periodo_fiscal' => '2025',
            'coordinador_id' => $this->coordinator->id,
            'analista_id' => $this->analyst->id,
            'fecha_inicio' => '2026-06-10',
            'fecha_entrega_estimada' => '2026-12-10',
            'fases_config' => ['Fase 1'],
        ]);

        $actividad = ActividadAuditoria::create([
            'proyecto_id' => $proyecto->id,
            'padre_id' => null,
            'actividad' => 'Proceso 1',
            'porcentaje_oficial' => 10,
            'estatus_oficial' => 'pendiente',
            'es_proceso_principal' => true,
        ]);

        // 1. Enviar propuesta con es_importante = true
        $response = $this->actingAs($this->analyst)->post(route('auditoria.proyectos.cambios.store', $proyecto->id), [
            'actividad_id' => $actividad->id,
            'porcentaje_propuesto' => 60,
            'estatus_propuesto' => 'en proceso',
            'comentario_propuesto' => 'Comentario crucial de auditoria',
            'es_importante' => true,
            'enviar' => true,
        ]);

        $response->assertJson(['success' => true]);
        $this->assertDatabaseHas('auditoria_cambios_propuestos', [
            'actividad_id' => $actividad->id,
            'es_importante' => true,
        ]);

        $propuesta = CambioPropuesto::where('actividad_id', $actividad->id)->first();

        // 2. Coordinador aprueba y se crea el comentario con es_importante = true
        $responseApprove = $this->actingAs($this->coordinator)->post(route('auditoria.proyectos.cambios.revisar', $proyecto->id), [
            'cambio_id' => $propuesta->id,
            'accion' => 'aprobar',
        ]);

        $responseApprove->assertJson(['success' => true]);
        $this->assertDatabaseHas('auditoria_comentarios', [
            'actividad_id' => $actividad->id,
            'comentario' => 'Comentario crucial de auditoria',
            'es_importante' => true,
        ]);
    }
}

