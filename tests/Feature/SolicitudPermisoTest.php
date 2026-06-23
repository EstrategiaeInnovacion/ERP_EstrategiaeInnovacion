<?php

namespace Tests\Feature;

use App\Models\Empleado;
use App\Models\SolicitudPermiso;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SolicitudPermisoTest extends TestCase
{
    use RefreshDatabase;

    private $user;
    private $empleado;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->user = User::factory()->create(['role' => 'user']);
        $this->empleado = Empleado::create([
            'user_id' => $this->user->id,
            'nombre' => 'Test Employee',
            'correo' => $this->user->email,
            'estado' => 'Activo',
            'posicion' => 'Analista',
            'fecha_ingreso' => now()->subYears(2),
            'dias_vacaciones' => 12,
        ]);
    }

    /** @test */
    public function no_puede_crear_permiso_retroactivo_mayor_a_24_horas()
    {
        $fechaPasada = Carbon::now()->subDays(2)->format('Y-m-d');

        $response = $this->actingAs($this->user)->post(route('permisos.solicitar'), [
            'tipo_permiso' => 'corto',
            'fecha_inicio' => $fechaPasada,
        ]);

        $response->assertSessionHas('error');
        $this->assertDatabaseMissing('solicitudes_permiso', [
            'empleado_id' => $this->empleado->id,
        ]);
    }

    /** @test */
    public function puede_crear_permiso_corto_y_autoasigna_fecha_fin()
    {
        $fecha = Carbon::now()->format('Y-m-d');

        $response = $this->actingAs($this->user)->post(route('permisos.solicitar'), [
            'tipo_permiso' => 'corto',
            'fecha_inicio' => $fecha,
            'hora_inicio' => '10:00',
            'hora_fin' => '12:00',
            'motivo_detalle' => 'Cita al dentista',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('solicitudes_permiso', [
            'empleado_id' => $this->empleado->id,
            'tipo_permiso' => 'corto',
            'fecha_inicio' => $fecha . ' 00:00:00',
            'fecha_fin' => $fecha . ' 00:00:00', // Fallback automático
        ]);
    }

    /** @test */
    public function puede_crear_permiso_legal_sin_comprobante_inicial()
    {
        $fecha = Carbon::now()->format('Y-m-d');

        $response = $this->actingAs($this->user)->post(route('permisos.solicitar'), [
            'tipo_permiso' => 'legal',
            'fecha_inicio' => $fecha,
            'motivo_detalle' => 'Urgencia médica',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('solicitudes_permiso', [
            'tipo_permiso' => 'legal',
            'comprobante_path' => null, // Opcional al inicio
            'estado' => 'aprobado_supervisor', // Se auto-aprueba si no tiene supervisor
        ]);
    }

    /** @test */
    public function puede_subir_comprobante_desfasado_despues_de_crearlo()
    {
        Storage::fake('public');

        $permiso = SolicitudPermiso::create([
            'empleado_id' => $this->empleado->id,
            'tipo_permiso' => 'legal',
            'fecha_inicio' => Carbon::now()->format('Y-m-d'),
            'fecha_fin' => Carbon::now()->format('Y-m-d'),
            'estado' => 'pendiente',
        ]);

        $file = UploadedFile::fake()->image('receta.jpg');

        $response = $this->actingAs($this->user)->post(route('permisos.subir_comprobante', $permiso->id), [
            'comprobante' => $file,
        ]);

        $response->assertSessionHas('success');
        
        $permiso->refresh();
        $this->assertNotNull($permiso->comprobante_path);
        Storage::disk('public')->assertExists($permiso->comprobante_path);
    }
}
