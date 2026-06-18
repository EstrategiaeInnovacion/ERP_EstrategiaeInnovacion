<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Empleado;
use App\Models\Evaluacion;
use App\Models\EvaluacionVentana;
use App\Models\CriterioEvaluacion;
use Carbon\Carbon;

class EvaluacionTest extends TestCase
{
    use RefreshDatabase;

    protected User $evaluatorUser;
    protected Empleado $evaluatorEmpleado;
    protected User $activeUser;
    protected Empleado $activeEmpleado;
    protected User $inactiveUser;
    protected Empleado $inactiveEmpleado;
    protected EvaluacionVentana $ventanaActiva;

    protected function setUp(): void
    {
        parent::setUp();

        // 1. Crear usuario evaluador y su Empleado correspondiente
        $this->evaluatorUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'approved',
        ]);
        $this->evaluatorEmpleado = Empleado::create([
            'user_id' => $this->evaluatorUser->id,
            'nombre' => 'Admin',
            'apellido_paterno' => 'RH',
            'correo' => $this->evaluatorUser->email,
            'area' => 'Recursos Humanos',
            'posicion' => 'Administración RH',
            'es_activo' => true,
        ]);

        // 2. Crear usuarios y empleados correspondientes
        $this->activeUser = User::factory()->create([
            'email' => 'activo@example.com',
            'status' => 'approved',
        ]);
        $this->activeEmpleado = Empleado::create([
            'user_id' => $this->activeUser->id,
            'nombre' => 'ColaboradorActivo',
            'correo' => 'activo@example.com',
            'area' => 'TI',
            'posicion' => 'Programador',
            'es_activo' => true,
            'supervisor_id' => $this->evaluatorEmpleado->id,
        ]);

        $this->inactiveUser = User::factory()->create([
            'email' => 'inactivo@example.com',
            'status' => 'approved',
        ]);
        $this->inactiveEmpleado = Empleado::create([
            'user_id' => $this->inactiveUser->id,
            'nombre' => 'ColaboradorInactivo',
            'correo' => 'inactivo@example.com',
            'area' => 'TI',
            'posicion' => 'Programador',
            'es_activo' => false,
            'supervisor_id' => $this->evaluatorEmpleado->id,
        ]);

        // 3. Crear una ventana de evaluación activa
        $this->ventanaActiva = EvaluacionVentana::create([
            'nombre' => 'Evaluación Anual',
            'fecha_apertura' => Carbon::yesterday(),
            'fecha_cierre' => Carbon::tomorrow(),
            'activo' => true,
            'creado_por' => $this->evaluatorUser->id,
        ]);

        // 4. Crear criterios de evaluación
        CriterioEvaluacion::create([
            'area' => 'TI',
            'criterio' => 'Calidad de Código',
            'descripcion' => 'Calidad',
            'peso' => 100,
        ]);
    }

    public function test_index_only_lists_active_employees(): void
    {
        $response = $this->actingAs($this->evaluatorUser)
            ->get(route('rh.evaluacion.index'));

        $response->assertStatus(200);
        $response->assertSee('ColaboradorActivo');
        $response->assertDontSee('ColaboradorInactivo');
    }

    public function test_show_route_redirects_with_error_for_inactive_employee(): void
    {
        $response = $this->actingAs($this->evaluatorUser)
            ->get(route('rh.evaluacion.show', [
                'id' => $this->inactiveEmpleado->id,
                'periodo' => '2026 | Enero - Junio',
                'tipo' => 'supervisor'
            ]));

        $response->assertRedirect(route('rh.evaluacion.index'));
        $response->assertSessionHas('error', 'No es posible evaluar a un empleado dado de baja.');
    }

    public function test_show_route_allows_active_employee(): void
    {
        $response = $this->actingAs($this->evaluatorUser)
            ->get(route('rh.evaluacion.show', [
                'id' => $this->activeEmpleado->id,
                'periodo' => '2026 | Enero - Junio',
                'tipo' => 'supervisor'
            ]));

        $response->assertStatus(200);
    }

    public function test_store_route_fails_for_inactive_employee(): void
    {
        $response = $this->actingAs($this->evaluatorUser)
            ->from(route('rh.evaluacion.index'))
            ->post(route('rh.evaluacion.store'), [
                'empleado_id' => $this->inactiveEmpleado->id,
                'tipo' => 'supervisor',
                'periodo' => '2026 | Enero - Junio',
                'calificaciones' => [1 => 90],
                'observaciones' => [1 => 'Comentario prueba'],
                'comentarios_generales' => 'Comentarios de prueba generales',
            ]);

        $response->assertRedirect(route('rh.evaluacion.index'));
        $response->assertSessionHas('error', 'No es posible evaluar a un empleado dado de baja.');
        $this->assertDatabaseMissing('evaluaciones', [
            'empleado_id' => $this->inactiveEmpleado->id
        ]);
    }

    public function test_store_route_succeeds_for_active_employee(): void
    {
        $response = $this->actingAs($this->evaluatorUser)
            ->post(route('rh.evaluacion.store'), [
                'empleado_id' => $this->activeEmpleado->id,
                'tipo' => 'supervisor',
                'periodo' => '2026 | Enero - Junio',
                'calificaciones' => [1 => 90],
                'observaciones' => [1 => 'Comentario prueba'],
                'comentarios_generales' => 'Comentarios de prueba generales',
            ]);

        $response->assertRedirect(route('rh.evaluacion.index', ['periodo' => '2026 | Enero - Junio']));
        $this->assertDatabaseHas('evaluaciones', [
            'empleado_id' => $this->activeEmpleado->id,
            'promedio_final' => 90
        ]);
    }

    public function test_update_route_fails_if_employee_is_inactive(): void
    {
        // Crear evaluación previa para el empleado activo
        $evaluacion = Evaluacion::create([
            'empleado_id' => $this->activeEmpleado->id,
            'evaluador_id' => $this->evaluatorUser->id,
            'periodo' => '2026 | Enero - Junio',
            'ventana_id' => $this->ventanaActiva->id,
            'tipo' => 'supervisor',
            'promedio_final' => 80,
            'comentarios_generales' => 'Original comments',
            'edit_count' => 1
        ]);

        // Desactivar el empleado
        $this->activeEmpleado->update(['es_activo' => false]);

        $response = $this->actingAs($this->evaluatorUser)
            ->from(route('rh.evaluacion.index'))
            ->put(route('rh.evaluacion.update', ['id' => $evaluacion->id]), [
                'tipo' => 'supervisor',
                'calificaciones' => [1 => 95],
                'observaciones' => [1 => 'Updated comment'],
                'comentarios_generales' => 'Updated general comments',
            ]);

        $response->assertRedirect(route('rh.evaluacion.index'));
        $response->assertSessionHas('error', 'No es posible modificar la evaluación de un empleado dado de baja.');
        $this->assertDatabaseHas('evaluaciones', [
            'id' => $evaluacion->id,
            'promedio_final' => 80 // Permaneció sin cambios
        ]);
    }

    public function test_resultados_route_fails_for_inactive_employee(): void
    {
        $response = $this->actingAs($this->evaluatorUser)
            ->get(route('rh.evaluacion.resultados', [
                'id' => $this->inactiveEmpleado->id,
                'periodo' => '2026 | Enero - Junio'
            ]));

        $response->assertRedirect(route('rh.evaluacion.index'));
        $response->assertSessionHas('error', 'No es posible acceder a los resultados de un empleado dado de baja.');
    }

    public function test_resultados_excel_route_fails_for_inactive_employee(): void
    {
        $response = $this->actingAs($this->evaluatorUser)
            ->get(route('rh.evaluacion.resultados.excel', [
                'id' => $this->inactiveEmpleado->id,
                'periodo' => '2026 | Enero - Junio'
            ]));

        $response->assertRedirect(route('rh.evaluacion.index'));
        $response->assertSessionHas('error', 'No es posible acceder a los resultados de un empleado dado de baja.');
    }

    public function test_authorized_user_can_get_ventanas(): void
    {
        $response = $this->actingAs($this->evaluatorUser)
            ->get(route('rh.evaluacion.ventanas.index'));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'ventanas',
            'ventana_activa'
        ]);
    }

    public function test_unauthorized_user_cannot_get_ventanas(): void
    {
        $nonAdminUser = User::factory()->create([
            'role' => 'user',
            'status' => 'approved',
        ]);
        // No Empleado associated, or Empleado is not Admin RH

        $response = $this->actingAs($nonAdminUser)
            ->get(route('rh.evaluacion.ventanas.index'));

        $response->assertStatus(403);
    }

    public function test_authorized_user_can_save_ventana(): void
    {
        $response = $this->actingAs($this->evaluatorUser)
            ->post(route('rh.evaluacion.ventanas.store'), [
                'nombre' => 'Nueva Ventana Test',
                'fecha_apertura' => Carbon::today()->toDateString(),
                'fecha_cierre' => Carbon::tomorrow()->toDateString(),
                'activo' => true
            ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertDatabaseHas('evaluacion_ventanas', [
            'nombre' => 'Nueva Ventana Test',
            'activo' => true
        ]);
    }

    public function test_authorized_user_can_toggle_ventana(): void
    {
        $ventana = EvaluacionVentana::create([
            'nombre' => 'Ventana Toggle Test',
            'fecha_apertura' => Carbon::today(),
            'fecha_cierre' => Carbon::tomorrow(),
            'activo' => false,
            'creado_por' => $this->evaluatorUser->id,
        ]);

        $response = $this->actingAs($this->evaluatorUser)
            ->patch(route('rh.evaluacion.ventanas.toggle', ['id' => $ventana->id]));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $response->assertJsonPath('activo', true);

        $this->assertTrue($ventana->fresh()->activo);
    }

    public function test_authorized_user_can_delete_ventana(): void
    {
        $ventana = EvaluacionVentana::create([
            'nombre' => 'Ventana Delete Test',
            'fecha_apertura' => Carbon::today(),
            'fecha_cierre' => Carbon::tomorrow(),
            'activo' => false,
            'creado_por' => $this->evaluatorUser->id,
        ]);

        $response = $this->actingAs($this->evaluatorUser)
            ->delete(route('rh.evaluacion.ventanas.destroy', ['id' => $ventana->id]));

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
        $this->assertDatabaseMissing('evaluacion_ventanas', [
            'id' => $ventana->id
        ]);
    }

    public function test_unauthorized_user_cannot_delete_ventana(): void
    {
        $nonAdminUser = User::factory()->create([
            'role' => 'user',
            'status' => 'approved',
        ]);

        $ventana = EvaluacionVentana::create([
            'nombre' => 'Ventana Delete Test',
            'fecha_apertura' => Carbon::today(),
            'fecha_cierre' => Carbon::tomorrow(),
            'activo' => false,
            'creado_por' => $this->evaluatorUser->id,
        ]);

        $response = $this->actingAs($nonAdminUser)
            ->delete(route('rh.evaluacion.ventanas.destroy', ['id' => $ventana->id]));

        $response->assertStatus(403);
        $this->assertDatabaseHas('evaluacion_ventanas', [
            'id' => $ventana->id
        ]);
    }
}
