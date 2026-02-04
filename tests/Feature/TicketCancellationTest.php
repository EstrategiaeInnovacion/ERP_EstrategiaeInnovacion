<?php

namespace Tests\Feature;

use App\Models\Sistemas_IT\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketCancellationTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_user_can_cancel_their_ticket_and_notify_admins(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $ticket = Ticket::create([
            'user_id' => $user->id,
            'nombre_solicitante' => $user->name,
            'correo_solicitante' => $user->email,
            'nombre_programa' => 'Programa de Prueba',
            'descripcion_problema' => 'Mi equipo presenta fallas intermitentes.',
            'tipo_problema' => 'software',
            'imagenes' => [],
        ]);

        $ticket->forceFill([
            'is_read' => true,
            'read_at' => now(),
        ])->save();

        $response = $this->actingAs($user)->delete(route('tickets.destroy', $ticket));

        $response->assertRedirect();

        $ticket->refresh();

        $this->assertEquals('cerrado', $ticket->estado);
        $this->assertTrue($ticket->closed_by_user);
        $this->assertNotNull($ticket->closed_by_user_at);
        $this->assertFalse($ticket->is_read);
        $this->assertNotNull($ticket->notified_at);
    }

    /** @test */
    public function admins_receive_a_specific_notification_message_when_a_ticket_is_cancelled_by_a_user(): void
    {
        $user = User::factory()->create([
            'role' => 'user',
        ]);

        $admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $ticket = Ticket::create([
            'user_id' => $user->id,
            'nombre_solicitante' => $user->name,
            'correo_solicitante' => $user->email,
            'nombre_programa' => 'Programa de Prueba',
            'descripcion_problema' => 'Mi equipo presenta fallas intermitentes.',
            'tipo_problema' => 'software',
            'imagenes' => [],
        ]);

        $ticket->forceFill([
            'is_read' => true,
            'read_at' => now(),
        ])->save();

        $this->actingAs($user)->delete(route('tickets.destroy', $ticket));

        $response = $this->actingAs($admin)->getJson('/api/notifications/unread');

        $response->assertOk();
        $data = $response->json('tickets');

        $this->assertNotEmpty($data);
        $this->assertSame($ticket->folio, $data[0]['folio']);
        $this->assertStringContainsString('Ticket cerrado por el usuario', $data[0]['descripcion']);
    }
}
