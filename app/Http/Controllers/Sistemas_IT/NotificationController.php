<?php

namespace App\Http\Controllers\Sistemas_IT;

use App\Http\Controllers\Controller;
use App\Models\Sistemas_IT\Ticket;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function getUnreadCount(): JsonResponse
    {
        $count = Ticket::where('is_read', false)->count();
        return response()->json(['count' => $count]);
    }

    public function getUnreadTickets(): JsonResponse
    {
        $tickets = Ticket::where('is_read', false)
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->select(['id', 'folio', 'nombre_solicitante', 'tipo_problema', 'descripcion_problema', 'created_at'])
            ->get()
            ->map(function ($ticket) {
                $descripcion = $ticket->closed_by_user
                    ? 'Ticket cerrado por el usuario.'
                    : \Str::limit($ticket->descripcion_problema, 50);

                return [
                    'id' => $ticket->id,
                    'folio' => $ticket->folio,
                    'solicitante' => $ticket->nombre_solicitante,
                    'tipo' => ucfirst($ticket->tipo_problema),
                    'descripcion' => $descripcion,
                    'fecha' => $ticket->created_at->diffForHumans(),
                    'url' => route('admin.tickets.show', $ticket)
                ];
            });

        return response()->json([
            'tickets' => $tickets,
            'count' => $tickets->count()
        ]);
    }

    public function markAsRead(Request $request, Ticket $ticket): JsonResponse
    {
        $ticket->update(['is_read' => true, 'read_at' => now()]);
        return response()->json(['success' => true, 'message' => 'Ticket marcado como leído']);
    }

    public function markAllAsRead(): JsonResponse
    {
        Ticket::where('is_read', false)->update(['is_read' => true, 'read_at' => now()]);
        return response()->json(['success' => true, 'message' => 'Todos los tickets marcados como leídos']);
    }

    public function getStats(): JsonResponse
    {
        $stats = [
            'total_unread' => Ticket::where('is_read', false)->count(),
            'unread_by_type' => [
                'software' => Ticket::where('is_read', false)->where('tipo_problema', 'software')->count(),
                'hardware' => Ticket::where('is_read', false)->where('tipo_problema', 'hardware')->count(),
                'mantenimiento' => Ticket::where('is_read', false)->where('tipo_problema', 'mantenimiento')->count(),
            ],
            'today_tickets' => Ticket::whereDate('created_at', today())->count(),
            'pending_tickets' => Ticket::where('estado', 'abierto')->count()
        ];
        return response()->json($stats);
    }
}
