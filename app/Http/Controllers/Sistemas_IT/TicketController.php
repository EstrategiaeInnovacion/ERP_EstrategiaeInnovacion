<?php

namespace App\Http\Controllers\Sistemas_IT;

use App\Http\Controllers\Controller;
use App\Models\Sistemas_IT\ComputerProfile;
use App\Models\Sistemas_IT\MaintenanceBooking;
use App\Models\Sistemas_IT\MaintenanceSlot;
use App\Models\Sistemas_IT\Ticket;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class TicketController extends Controller
{
    public function create($tipo)
    {
        $tipos_validos = ['software', 'hardware', 'mantenimiento'];
        if (!in_array($tipo, $tipos_validos)) {
            abort(404);
        }

        $assignedComputerLoan = null;
        $assignedComputerProfile = null;
        $assignedPrinterLoan = null;

        if ($tipo === 'hardware' && auth()->check()) {
            $user = auth()->user();

            $assignedComputerProfile = ComputerProfile::where('is_loaned', true)
                ->where('loaned_to_email', $user->email)
                ->first();
        }

        // --- CORRECCIÓN VITAL: Obtener hora exacta del servidor (México) ---
        // Esto se envía a la vista para forzar el calendario al año y día correctos
        $serverTime = now('America/Mexico_City');

        return view('tickets.create', compact(
            'tipo',
            'assignedComputerLoan',
            'assignedComputerProfile',
            'assignedPrinterLoan',
            'serverTime' // <--- Variable necesaria para el calendario
        ));
    }

    public function store(Request $request)
    {
        \Log::info('TicketController::store - Datos recibidos', [
            'tipo_problema' => $request->input('tipo_problema'),
            'maintenance_slot_id' => $request->input('maintenance_slot_id'),
            'all_input' => $request->all()
        ]);

        $validated = $request->validate([
            'nombre_programa' => [
                Rule::requiredIf(fn () => $request->input('tipo_problema') === 'hardware'),
                'nullable',
                'string',
                'max:255',
            ],
            'otro_programa_nombre' => 'nullable|string|max:255',
            'descripcion_problema' => [
                Rule::requiredIf(fn () => $request->input('tipo_problema') !== 'mantenimiento'),
                'string',
            ],
            'tipo_problema' => 'required|in:software,hardware,mantenimiento',
            'imagenes' => 'nullable|array|max:5',
            'imagenes.*' => 'nullable|image|max:2048',
            'maintenance_slot_id' => 'required_if:tipo_problema,mantenimiento',
        ]);

        \Log::info('TicketController::store - Datos validados', $validated);

        $imagenes = [];
        if ($request->hasFile('imagenes')) {
            foreach ($request->file('imagenes') as $imagen) {
                $imageData = base64_encode(file_get_contents($imagen->getRealPath()));
                $mimeType = $imagen->getMimeType();
                $imagenes[] = [
                    'data' => $imageData,
                    'mime' => $mimeType,
                    'name' => $imagen->getClientOriginalName()
                ];
            }
        }

        $ticket = null;

        DB::transaction(function () use ($request, $imagenes, &$ticket, $validated) {
            $nombrePrograma = $validated['nombre_programa'] ?? null;
            if ($nombrePrograma === 'Otro' && !empty($validated['otro_programa_nombre'])) {
                $nombrePrograma = $validated['otro_programa_nombre'];
            }

            $ticketData = [
                'user_id' => auth()->id(),
                'nombre_solicitante' => auth()->user()->name,
                'correo_solicitante' => auth()->user()->email,
                'nombre_programa' => $nombrePrograma,
                'descripcion_problema' => $validated['descripcion_problema'] ?? '',
                'tipo_problema' => $validated['tipo_problema'],
                'imagenes' => $imagenes,
                'estado' => 'abierto',
                'is_read' => false,
                'notified_at' => now('America/Mexico_City'),
            ];

            if ($validated['tipo_problema'] === 'mantenimiento') {
                // NOTA: Si usas slots de BD real, descomenta la validación de capacidad aquí.
                /*
                $slot = MaintenanceSlot::lockForUpdate()->find($validated['maintenance_slot_id'] ?? null);
                if (!$slot || !$slot->is_active || $slot->available_capacity <= 0) {
                     throw ValidationException::withMessages(['maintenance_slot_id' => 'Horario no disponible.']);
                }
                $ticketData['maintenance_slot_id'] = $slot->id;
                $ticketData['maintenance_scheduled_at'] = $slot->start_date_time;
                */

                $ticket = Ticket::create($ticketData);

                MaintenanceBooking::create([
                    'maintenance_slot_id' => $validated['maintenance_slot_id'],
                    'ticket_id' => $ticket->id,
                    'additional_details' => $validated['descripcion_problema'] ?? '',
                ]);

                // $slot->increment('booked_count'); // Descomentar si usas BD real

                \Log::info('TicketController::store - Ticket de mantenimiento creado exitosamente', [
                    'ticket_id' => $ticket->id,
                    'folio' => $ticket->folio
                ]);
            } else {
                $ticket = Ticket::create($ticketData);
                \Log::info('TicketController::store - Ticket regular creado exitosamente', [
                    'ticket_id' => $ticket->id,
                    'folio' => $ticket->folio,
                    'tipo' => $ticket->tipo_problema
                ]);
            }
        });

        if ($ticket) {
            $this->notifyN8nTicketCreated($ticket);
        }

        return redirect()->route('tickets.mis-tickets')->with('success',
            "¡Ticket creado exitosamente! Folio: {$ticket->folio}.");
    }

    protected function notifyN8nTicketCreated(Ticket $ticket): void
    {
        $webhookUrl = config('services.n8n.webhook_url');

        if (empty($webhookUrl)) {
            \Log::info('Webhook de n8n no configurado. Se omite notificación.', [
                'ticket_id' => $ticket->id,
            ]);
            return;
        }

        DB::afterCommit(function () use ($ticket, $webhookUrl) {
            try {
                $ticket->loadMissing(['maintenanceSlot', 'maintenanceBooking', 'user']);

                $payload = [
                    'ticket' => [
                        'id' => $ticket->id,
                        'folio' => $ticket->folio,
                        'tipo_problema' => $ticket->tipo_problema,
                        'estado' => $ticket->estado,
                        'prioridad' => $ticket->prioridad,
                        'descripcion_problema' => $ticket->descripcion_problema,
                        'nombre_programa' => $ticket->nombre_programa,
                        'fecha_creacion' => optional($ticket->created_at)->toIso8601String(),
                        'fecha_actualizacion' => optional($ticket->updated_at)->toIso8601String(),
                        'imagenes' => $ticket->imagenes,
                        'maintenance_slot' => $ticket->maintenanceSlot ? [
                            'id' => $ticket->maintenanceSlot->id,
                            'date' => optional($ticket->maintenanceSlot->date)->toDateString(),
                            'start' => optional($ticket->maintenanceSlot->start_date_time)->toIso8601String(),
                            'end' => optional($ticket->maintenanceSlot->end_date_time)->toIso8601String(),
                        ] : null,
                        'maintenance_booking' => $ticket->maintenanceBooking ? [
                            'id' => $ticket->maintenanceBooking->id,
                            'additional_details' => $ticket->maintenanceBooking->additional_details,
                        ] : null,
                    ],
                    'user' => [
                        'id' => $ticket->user_id,
                        'nombre' => $ticket->nombre_solicitante,
                        'correo' => $ticket->correo_solicitante,
                    ],
                ];

                $response = Http::timeout(10)->post($webhookUrl, $payload);

                if ($response->failed()) {
                    \Log::error('No se pudo enviar el webhook de n8n.', [
                        'ticket_id' => $ticket->id,
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                } else {
                    \Log::info('Webhook de n8n enviado correctamente.', [
                        'ticket_id' => $ticket->id,
                    ]);
                }
            } catch (\Throwable $exception) {
                \Log::error('Error al enviar el webhook de n8n.', [
                    'ticket_id' => $ticket->id,
                    'error' => $exception->getMessage(),
                ]);
            }
        });
    }

    public function index()
    {
        $tickets = Ticket::orderBy('created_at', 'desc')->paginate(15);
        return view('admin.tickets.index', compact('tickets'));
    }

    public function show(Ticket $ticket)
    {
        return view('admin.tickets.show', compact('ticket'));
    }

    public function update(Request $request, Ticket $ticket)
    {
        $rules = [
            'estado' => 'required|in:abierto,en_proceso,cerrado',
            'observaciones' => 'nullable|string',
        ];

        if ($ticket->tipo_problema === 'mantenimiento') {
            $rules = array_merge($rules, [
                'equipment_identifier' => 'nullable|string|max:255',
                'equipment_brand' => 'nullable|string|max:255',
                'equipment_model' => 'nullable|string|max:255',
                'equipment_password' => 'nullable|string|max:255',
                'disk_type' => 'nullable|string|max:255',
                'ram_capacity' => 'nullable|string|max:255',
                'battery_status' => 'nullable|in:functional,partially_functional,damaged',
                'aesthetic_observations' => 'nullable|string',
                'maintenance_report' => 'nullable|string',
                'closure_observations' => 'nullable|string',
                'replacement_components' => 'nullable|array',
                'replacement_components.*' => 'in:disco_duro,ram,bateria,pantalla,conectores,teclado,mousepad,cargador',
                'mark_as_loaned' => 'nullable|boolean',
                'imagenes_admin' => 'nullable|array',
                'imagenes_admin.*' => 'image|mimes:jpeg,png,jpg,gif|max:2048',
                'removed_admin_images' => 'nullable|array',
                'removed_admin_images.*' => 'integer',
            ]);
        }

        if ($ticket->tipo_problema !== 'mantenimiento') {
            $rules['prioridad'] = 'nullable|in:baja,media,alta,critica';
        }

        $validated = $request->validate($rules);

        $data = collect($validated)->only(['estado', 'observaciones', 'prioridad'])->toArray();

        $originalEstado = $ticket->estado;
        $originalObservaciones = $ticket->observaciones;
        $originalMaintenanceReport = $ticket->maintenance_report;
        $originalClosureObservations = $ticket->closure_observations;

        if ($validated['estado'] === 'cerrado' && $ticket->estado !== 'cerrado') {
            $data['fecha_cierre'] = now('America/Mexico_City');
        }

        if ($validated['estado'] !== 'cerrado') {
            $data['closed_by_user'] = false;
            $data['closed_by_user_at'] = null;
        } elseif ($originalEstado !== 'cerrado') {
            $data['closed_by_user'] = false;
            $data['closed_by_user_at'] = null;
        }

        $maintenanceData = [];

        if ($ticket->tipo_problema === 'mantenimiento') {
            $technicalDataProvided = false;
            $replacementComponentsProvided = false;
            $markAsLoanedProvided = $request->has('mark_as_loaned');

            $maintenanceFields = [
                'equipment_identifier',
                'equipment_brand',
                'equipment_model',
                'equipment_password',
                'disk_type',
                'ram_capacity',
                'battery_status',
                'aesthetic_observations',
                'maintenance_report',
                'closure_observations',
            ];

            foreach ($maintenanceFields as $field) {
                if (array_key_exists($field, $validated)) {
                    $maintenanceData[$field] = $validated[$field];
                    if (in_array($field, ['equipment_identifier', 'equipment_brand', 'equipment_model', 'equipment_password', 'disk_type', 'ram_capacity', 'battery_status', 'aesthetic_observations'], true)) {
                        $technicalDataProvided = true;
                    }
                }
            }

            if ($request->has('replacement_components')) {
                $maintenanceData['replacement_components'] = $validated['replacement_components'] ?? [];
                $replacementComponentsProvided = true;
            }

            $existingImages = $ticket->imagenes_admin ?? [];
            if ($request->has('removed_admin_images')) {
                $removedIndices = $request->input('removed_admin_images');
                foreach ($removedIndices as $index) {
                    unset($existingImages[$index]);
                }
                $existingImages = array_values($existingImages);
            }

            if ($request->hasFile('imagenes_admin')) {
                foreach ($request->file('imagenes_admin') as $imagen) {
                    if ($imagen->isValid()) {
                        $imageData = base64_encode(file_get_contents($imagen->getPathname()));
                        $existingImages[] = $imageData;
                    }
                }
            }

            $maintenanceData['imagenes_admin'] = $existingImages;
            $data = array_merge($data, $maintenanceData);
        }

        $ticket->fill($data);
        $ticket->save();

        if ($ticket->tipo_problema === 'mantenimiento') {
            $shouldUpdateMaintenanceDate = $ticket->computerProfile && $validated['estado'] === 'cerrado';
            $shouldManageProfile = $technicalDataProvided || $replacementComponentsProvided || $markAsLoanedProvided || $shouldUpdateMaintenanceDate;

            if ($shouldManageProfile) {
                $profile = $ticket->computerProfile;

                if (!$profile && $technicalDataProvided && $ticket->equipment_identifier) {
                    $profile = ComputerProfile::firstOrNew(['identifier' => $ticket->equipment_identifier]);
                }

                if (!$profile && $technicalDataProvided) {
                    $profile = new ComputerProfile();
                }

                if ($profile) {
                    $profileData = [
                        'last_ticket_id' => $ticket->id,
                    ];

                    if ($technicalDataProvided) {
                        $profileData = array_merge($profileData, [
                            'identifier' => $ticket->equipment_identifier,
                            'brand' => $ticket->equipment_brand,
                            'model' => $ticket->equipment_model,
                            'disk_type' => $ticket->disk_type,
                            'ram_capacity' => $ticket->ram_capacity,
                            'battery_status' => $ticket->battery_status,
                            'aesthetic_observations' => $ticket->aesthetic_observations,
                        ]);
                    }

                    if ($validated['estado'] === 'cerrado') {
                        $profile->last_maintenance_at = now('America/Mexico_City');
                    }

                    $profile->fill(array_filter($profileData, fn ($value) => !is_null($value)));

                    if ($replacementComponentsProvided) {
                        $profile->replacement_components = $ticket->replacement_components ?? [];
                    }

                    if ($markAsLoanedProvided) {
                        $markAsLoaned = $request->boolean('mark_as_loaned');
                        $profile->is_loaned = $markAsLoaned;
                        if ($markAsLoaned) {
                            $profile->loaned_to_name = $ticket->nombre_solicitante;
                            $profile->loaned_to_email = $ticket->correo_solicitante;
                        } else {
                            $profile->loaned_to_name = null;
                            $profile->loaned_to_email = null;
                        }
                    }

                    $profile->save();

                    if ($ticket->computer_profile_id !== $profile->id) {
                        $ticket->computer_profile_id = $profile->id;
                        $ticket->save();
                    }
                }
            }
        }

        $userNotificationMessages = [];

        if ($originalEstado !== $ticket->estado) {
            $estadoLabel = ucfirst(str_replace('_', ' ', $ticket->estado));
            if ($ticket->estado === 'cerrado') {
                $userNotificationMessages[] = 'Tu ticket fue marcado como cerrado por el equipo de TI.';
            } else {
                $userNotificationMessages[] = "El estado del ticket cambió a \"{$estadoLabel}\".";
            }
        }

        if (!empty($ticket->observaciones) && $ticket->observaciones !== $originalObservaciones) {
            $userNotificationMessages[] = 'El administrador dejó un nuevo comentario en tu ticket.';
        }

        if ($ticket->tipo_problema === 'mantenimiento') {
            if (!empty($ticket->maintenance_report) && $ticket->maintenance_report !== $originalMaintenanceReport) {
                $userNotificationMessages[] = 'Se actualizó el reporte de mantenimiento del equipo.';
            }

            if (!empty($ticket->closure_observations) && $ticket->closure_observations !== $originalClosureObservations) {
                $userNotificationMessages[] = 'Se agregaron nuevas observaciones de cierre.';
            }
        }

        if (!empty($userNotificationMessages)) {
            $ticket->forceFill([
                'user_has_updates' => true,
                'user_notified_at' => now('America/Mexico_City'),
                'user_notification_summary' => implode(' ', $userNotificationMessages),
            ])->save();
        }

        $redirectTo = $request->input('redirect_to');
        $targetTicketId = $request->input('target_ticket_id', $ticket->id);

        $redirectResponse = $redirectTo
            ? redirect()->to($redirectTo)
            : redirect()->route('admin.tickets.index');

        return $redirectResponse
            ->with('success', 'Ticket actualizado exitosamente')
            ->with('active_ticket_form', $targetTicketId);
    }

    public function misTickets(Request $request)
    {
        $tickets = Ticket::where('user_id', auth()->id())
                        ->orderBy('created_at', 'desc')
                        ->get();

        $notificationsCount = $tickets->where('user_has_updates', true)->count();
        $supportEmail = config('support.contact_email');
        $supportTeamsUrl = config('support.teams_url');

        return view('tickets.mis-tickets', compact(
            'tickets',
            'notificationsCount',
            'supportEmail',
            'supportTeamsUrl'
        ));
    }

    public function acknowledgeUpdate(Request $request, $ticketId)
    {
        $user = auth()->user();
        $ticket = Ticket::where('id', $ticketId)
                        ->where('user_id', $user->id)
                        ->first();
                        
        if (!$ticket) {
            if ($request->wantsJson()) {
                return response()->json(['error' => 'Ticket no encontrado'], 404);
            }
            abort(404);
        }

        $ticket->forceFill([
            'user_has_updates' => false,
            'user_last_read_at' => now('America/Mexico_City'),
        ])->save();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Actualización marcada como revisada.',
            ]);
        }

        return redirect()->route('tickets.mis-tickets')->with('success', 'Actualización marcada como revisada.');
    }

    public function acknowledgeAllUpdates(Request $request)
    {
        $updated = Ticket::where('user_id', auth()->id())
            ->where('user_has_updates', true)
            ->update([
                'user_has_updates' => false,
                'user_last_read_at' => now('America/Mexico_City'),
            ]);

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Todas las actualizaciones fueron marcadas como revisadas.',
                'updated' => $updated,
            ]);
        }

        return redirect()
            ->back()
            ->with('success', $updated > 0
                ? 'Todas las notificaciones fueron marcadas como revisadas.'
                : 'No tienes notificaciones pendientes.');
    }

    public function destroy($ticketId)
    {
        $user = auth()->user();
        
        $isAdmin = $user && method_exists($user, 'isAdmin') ? $user->isAdmin() : false;
        
        if ($isAdmin) {
            $ticket = Ticket::find($ticketId);
        } else {
            $ticket = Ticket::where('id', $ticketId)
                           ->where('user_id', $user->id)
                           ->first();
        }
        
        if (!$ticket) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'Ticket no encontrado o sin permisos'], 404);
            }
            return redirect()->back()->with('error', 'Ticket no encontrado o sin permisos para eliminarlo.');
        }
        
        $folio = $ticket->folio;
        $solicitante = $ticket->nombre_solicitante;

        if ($ticket->tipo_problema === 'mantenimiento' && $ticket->maintenance_slot_id) {
            $this->releaseMaintenanceSlot($ticket);
        }

        if ($isAdmin) {
            $ticket->delete();
            return redirect()->back()->with(
                'success',
                "Ticket {$folio} de {$solicitante} eliminado exitosamente desde el panel administrativo." .
                ($ticket->tipo_problema === 'mantenimiento' ? ' El horario de mantenimiento ha sido liberado.' : '')
            );
        }

        if (!$user || $ticket->user_id !== $user->id) {
            if (request()->expectsJson()) {
                return response()->json(['error' => 'No tienes permiso para cancelar este ticket.'], 403);
            }
            return redirect()->back()->with('error', 'No tienes permiso para cancelar este ticket.');
        }

        if ($ticket->estado === 'cerrado' && $ticket->closed_by_user) {
            $message = "El ticket {$folio} ya había sido cancelado anteriormente.";
            if (request()->expectsJson()) {
                return response()->json(['error' => $message], 409);
            }
            return redirect()->back()->with('info', $message);
        }

        if ($ticket->tipo_problema === 'mantenimiento' && $ticket->maintenance_slot_id) {
            $slot = MaintenanceSlot::find($ticket->maintenance_slot_id);
            if ($slot) {
                $nowMexico = now('America/Mexico_City');
                $slotDateTime = $slot->start_date_time;
                
                if ($slotDateTime && $slotDateTime->lessThanOrEqualTo($nowMexico)) {
                    $message = "No se puede cancelar el ticket {$folio} porque la fecha de mantenimiento ({$slotDateTime->format('d/m/Y H:i')}) ya ha pasado.";
                    if (request()->expectsJson()) {
                        return response()->json(['error' => $message], 422);
                    }
                    return redirect()->back()->with('error', $message);
                }
            }
        }

        $nowMexico = now('America/Mexico_City');

        if ($ticket->tipo_problema === 'mantenimiento' && $ticket->maintenance_slot_id) {
            $this->releaseMaintenanceSlot($ticket);
        }

        $ticket->forceFill([
            'estado' => 'cerrado',
            'fecha_cierre' => $nowMexico,
            'closed_by_user' => true,
            'closed_by_user_at' => $nowMexico,
            'is_read' => false,
            'notified_at' => $nowMexico,
        ])->save();

        $message = "Ticket {$folio} cancelado exitosamente. El equipo de TI ha sido notificado.";
        if ($ticket->tipo_problema === 'mantenimiento') {
            $message .= " El horario de mantenimiento ha sido liberado para que otros usuarios puedan agendarlo.";
        }

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'ticket_id' => $ticketId,
                'folio' => $folio
            ]);
        }

        return redirect()->back()->with('success', $message);
    }

    public function canCancel($ticketId)
    {
        $user = auth()->user();
        
        if (!$user) {
            return response()->json(['can_cancel' => false, 'reason' => 'Usuario no autenticado'], 401);
        }
        
        try {
            $ticket = \DB::table('tickets')->where('id', $ticketId)->first();
            
            if (!$ticket) {
                return response()->json([
                    'can_cancel' => false, 
                    'reason' => 'Ticket no encontrado en base de datos',
                    'debug' => ['query_table' => 'tickets', 'searched_id' => $ticketId]
                ], 404);
            }
            
            $ticket = Ticket::find($ticketId);
            
        } catch (\Exception $e) {
            return response()->json([
                'can_cancel' => false, 
                'reason' => 'Error de base de datos: ' . $e->getMessage()
            ], 500);
        }
        
        $isAdmin = method_exists($user, 'isAdmin') ? $user->isAdmin() : false;
        
        if (!$isAdmin && $ticket->user_id !== $user->id) {
            return response()->json([
                'can_cancel' => false, 
                'reason' => 'No tienes permisos para cancelar este ticket. Solo el propietario o un administrador pueden hacerlo.',
                'debug' => [
                    'ticket_owner_id' => $ticket->user_id,
                    'current_user_id' => $user->id,
                    'is_admin' => $isAdmin,
                    'ticket_folio' => $ticket->folio
                ]
            ], 403);
        }
        
        $nowMexico = now('America/Mexico_City');
        
        if ($ticket->estado === 'cerrado' && $ticket->closed_by_user) {
            return response()->json(['can_cancel' => false, 'reason' => 'Ya cancelado']);
        }
        
        if ($ticket->tipo_problema === 'mantenimiento' && $ticket->maintenance_slot_id) {
            $slot = MaintenanceSlot::find($ticket->maintenance_slot_id);
            if ($slot) {
                $slotDateTime = $slot->start_date_time;
                if ($slotDateTime && $slotDateTime->lessThanOrEqualTo($nowMexico)) {
                    return response()->json([
                        'can_cancel' => false, 
                        'reason' => 'Fecha ya pasó',
                        'maintenance_date' => $slotDateTime->format('d/m/Y H:i')
                    ]);
                }
            }
        }
        
        return response()->json(['can_cancel' => true]);
    }

    private function releaseMaintenanceSlot(Ticket $ticket)
    {
        try {
            $nowMexico = now('America/Mexico_City');
            
            $booking = MaintenanceBooking::where('ticket_id', $ticket->id)->first();
            if ($booking) {
                $slot = $booking->slot;
                if ($slot) {
                    $slotDateTime = $slot->start_date_time;
                    
                    if ($slotDateTime && $slotDateTime->greaterThan($nowMexico)) {
                        if ($slot->booked_count > 0) {
                            $slot->decrement('booked_count');
                        }
                        
                        \Log::info('MaintenanceSlot liberado via booking', [
                            'ticket_id' => $ticket->id,
                            'slot_id' => $slot->id,
                            'fecha' => $slot->date->format('Y-m-d'),
                            'hora' => $slot->start_time,
                            'fecha_mantenimiento' => $slotDateTime->format('Y-m-d H:i:s'),
                            'ahora' => $nowMexico->format('Y-m-d H:i:s'),
                            'booked_count_despues' => $slot->booked_count
                        ]);
                    } else {
                        \Log::info('MaintenanceSlot NO liberado - fecha ya pasó', [
                            'ticket_id' => $ticket->id,
                            'slot_id' => $slot->id,
                            'fecha_mantenimiento' => $slotDateTime?->format('Y-m-d H:i:s') ?? 'null',
                            'ahora' => $nowMexico->format('Y-m-d H:i:s')
                        ]);
                    }
                }
                $booking->delete();
                return;
            }
            
            if ($ticket->maintenance_slot_id) {
                $slot = MaintenanceSlot::find($ticket->maintenance_slot_id);
                if ($slot) {
                    $slotDateTime = $slot->start_date_time;
                    
                    if ($slotDateTime && $slotDateTime->greaterThan($nowMexico)) {
                        if ($slot->booked_count > 0) {
                            $slot->decrement('booked_count');
                        }
                        
                        \Log::info('MaintenanceSlot liberado via slot_id directo', [
                            'ticket_id' => $ticket->id,
                            'slot_id' => $slot->id,
                            'fecha' => $slot->date->format('Y-m-d'),
                            'hora' => $slot->start_time,
                            'fecha_mantenimiento' => $slotDateTime->format('Y-m-d H:i:s'),
                            'ahora' => $nowMexico->format('Y-m-d H:i:s'),
                            'booked_count_despues' => $slot->booked_count
                        ]);
                    } else {
                        \Log::info('MaintenanceSlot NO liberado - fecha ya pasó (directo)', [
                            'ticket_id' => $ticket->id,
                            'slot_id' => $slot->id,
                            'fecha_mantenimiento' => $slotDateTime?->format('Y-m-d H:i:s') ?? 'null',
                            'ahora' => $nowMexico->format('Y-m-d H:i:s')
                        ]);
                    }
                }
            }
            
        } catch (\Exception $e) {
            \Log::error('Error al liberar MaintenanceSlot', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    private function enviarEmailConfirmacion(Ticket $ticket)
    {
        try {
            if (config('mail.default') && config('mail.mailers.smtp.host')) {
                Mail::send('emails.ticket-confirmacion', ['ticket' => $ticket], function($message) use ($ticket) {
                    $message->to($ticket->correo_solicitante, $ticket->nombre_solicitante)
                            ->subject("Ticket Creado - Folio: {$ticket->folio}");
                });
            }
        } catch (\Exception $e) {
            \Log::error('Error enviando email: ' . $e->getMessage());
        }
    }

    public function changeMaintenanceDate(Request $request, Ticket $ticket)
    {
        if (!auth()->user()->isAdmin()) {
            return redirect()->back()->with('error', 'No tienes permisos para realizar esta acción.');
        }

        if ($ticket->tipo_problema !== 'mantenimiento') {
            return redirect()->back()->with('error', 'Solo se puede cambiar la fecha de tickets de mantenimiento.');
        }

        $request->validate([
            'new_maintenance_slot_id' => 'required|exists:maintenance_slots,id',
        ], [
            'new_maintenance_slot_id.required' => 'Debes seleccionar un nuevo horario.',
            'new_maintenance_slot_id.exists' => 'El horario seleccionado no es válido.',
        ]);

        $newSlot = MaintenanceSlot::find($request->new_maintenance_slot_id);
        if ($newSlot->available_capacity <= 0) {
            return redirect()->back()->with('error', 'El horario seleccionado no tiene capacidad disponible.');
        }

        try {
            DB::transaction(function () use ($ticket, $newSlot) {
                if ($ticket->maintenance_slot_id) {
                    $this->releaseMaintenanceSlot($ticket);
                }

                $booking = MaintenanceBooking::create([
                    'maintenance_slot_id' => $newSlot->id,
                    'ticket_id' => $ticket->id,
                    'additional_details' => 'Fecha cambiada por administrador',
                ]);

                $newSlot->increment('booked_count');

                $ticket->update([
                    'maintenance_slot_id' => $newSlot->id,
                    'is_read' => false,
                    'user_has_updates' => true,
                    'notified_at' => now('America/Mexico_City'),
                ]);

                \Log::info('Fecha de mantenimiento cambiada por administrador', [
                    'ticket_id' => $ticket->id,
                    'folio' => $ticket->folio,
                    'new_slot_id' => $newSlot->id,
                    'new_date' => $newSlot->date,
                    'new_time' => $newSlot->start_time,
                    'admin_id' => auth()->id(),
                ]);
            });

            return redirect()->back()->with('success', 
                'La fecha del mantenimiento ha sido cambiada exitosamente. El usuario será notificado del cambio.'
            );

        } catch (\Exception $e) {
            \Log::error('Error al cambiar fecha de mantenimiento', [
                'ticket_id' => $ticket->id,
                'error' => $e->getMessage()
            ]);
            return redirect()->back()->with('error', 'Hubo un error al cambiar la fecha del mantenimiento.');
        }
    }

    public function getAvailableMaintenanceSlots(Request $request)
    {
        // Se permite acceso público (usuarios auth) para que el front-end consuma los horarios
        $slots = MaintenanceSlot::active()
            ->where('date', '>=', now('America/Mexico_City')->toDateString())
            ->where('capacity', '>', DB::raw('booked_count'))
            ->orderBy('date')
            ->orderBy('start_time')
            ->get(['id', 'date', 'start_time', 'end_time', 'capacity', 'booked_count'])
            ->map(function ($slot) {
                return [
                    'id' => $slot->id,
                    'date' => $slot->date->format('Y-m-d'),
                    'date_formatted' => $slot->date->format('d/m/Y'),
                    'start_time' => $slot->start_time,
                    'end_time' => $slot->end_time,
                    'available_capacity' => $slot->available_capacity,
                    'display_text' => $slot->date->format('d/m/Y') . ' de ' . 
                                    $slot->start_time . ' a ' . $slot->end_time . 
                                    ' (' . $slot->available_capacity . ' disponibles)'
                ];
            });

        return response()->json($slots);
    }
}