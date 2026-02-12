<?php

namespace App\Http\Controllers\Sistemas_IT;

use App\Http\Controllers\Controller;
use App\Models\Sistemas_IT\ComputerProfile;
use App\Models\Sistemas_IT\MaintenanceBlockedSlot;
use App\Models\Sistemas_IT\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MaintenanceController extends Controller
{
    /**
     * Horarios predefinidos de mantenimiento (9am - 4pm)
     * Cada slot es de 1 hora
     */
    private const TIME_SLOTS = [
        ['start' => '09:00', 'end' => '10:00', 'label' => '09:00 AM'],
        ['start' => '10:00', 'end' => '11:00', 'label' => '10:00 AM'],
        ['start' => '11:00', 'end' => '12:00', 'label' => '11:00 AM'],
        ['start' => '12:00', 'end' => '13:00', 'label' => '12:00 PM'],
        ['start' => '13:00', 'end' => '14:00', 'label' => '01:00 PM'],
        ['start' => '14:00', 'end' => '15:00', 'label' => '02:00 PM'],
        ['start' => '15:00', 'end' => '16:00', 'label' => '03:00 PM'],
    ];

    /**
     * Obtener horarios predefinidos
     */
    public static function getTimeSlots(): array
    {
        return self::TIME_SLOTS;
    }

    /**
     * API: Disponibilidad del mes para el calendario
     */
    public function availability(Request $request): JsonResponse
    {
        $month = $request->query('month');
        try {
            $start = $month ? Carbon::createFromFormat('Y-m', $month)->startOfMonth() : now()->startOfMonth();
        } catch (\Exception $e) {
            $start = now()->startOfMonth();
        }

        $end = $start->copy()->endOfMonth();
        $now = Carbon::now('America/Mexico_City');

        // Obtener bloqueos para el mes
        $blockedSlots = MaintenanceBlockedSlot::getBlockedForRange(
            $start->toDateString(),
            $end->toDateString()
        );

        // Obtener tickets de mantenimiento para el mes
        $bookedTickets = Ticket::where('tipo_problema', 'mantenimiento')
            ->whereBetween('maintenance_scheduled_at', [$start->startOfDay(), $end->endOfDay()])
            ->whereIn('estado', ['abierto', 'en_proceso'])
            ->get()
            ->groupBy(function ($ticket) {
                return Carbon::parse($ticket->maintenance_scheduled_at)->format('Y-m-d');
            })
            ->map(function ($dayTickets) {
                return $dayTickets->map(function ($ticket) {
                    return Carbon::parse($ticket->maintenance_scheduled_at)->format('H:i');
                })->toArray();
            })
            ->toArray();

        $days = [];
        
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
            $isWeekend = $date->isWeekend();
            $isPast = $date->copy()->endOfDay()->lessThanOrEqualTo($now);

            if ($isWeekend || $isPast) {
                $days[] = [
                    'date' => $dateStr,
                    'total_slots' => 0,
                    'available' => 0,
                    'booked' => 0,
                    'is_past' => $isPast,
                    'is_weekend' => $isWeekend,
                    'status' => $isPast ? 'past' : 'unavailable',
                ];
                continue;
            }

            // Verificar si el día completo está bloqueado
            $isFullDayBlocked = isset($blockedSlots[$dateStr]) && $blockedSlots[$dateStr] === 'all';

            if ($isFullDayBlocked) {
                $days[] = [
                    'date' => $dateStr,
                    'total_slots' => count(self::TIME_SLOTS),
                    'available' => 0,
                    'booked' => 0,
                    'blocked' => true,
                    'is_past' => false,
                    'status' => 'blocked',
                ];
                continue;
            }

            // Calcular disponibilidad
            $blockedHours = isset($blockedSlots[$dateStr]) && is_array($blockedSlots[$dateStr]) 
                ? $blockedSlots[$dateStr] 
                : [];
            $bookedHours = $bookedTickets[$dateStr] ?? [];
            
            $isToday = $date->isToday();
            $availableCount = 0;
            $bookedCount = count($bookedHours);

            foreach (self::TIME_SLOTS as $slot) {
                $slotTime = $slot['start'];
                $isBlocked = in_array($slotTime, $blockedHours);
                $isBooked = in_array($slotTime, $bookedHours);
                
                // Si es hoy, verificar si la hora ya pasó
                $slotPast = false;
                if ($isToday) {
                    $slotDateTime = Carbon::parse($dateStr . ' ' . $slotTime, 'America/Mexico_City');
                    $slotPast = $slotDateTime->lessThanOrEqualTo($now);
                }

                if (!$isBlocked && !$isBooked && !$slotPast) {
                    $availableCount++;
                }
            }

            $status = 'available';
            if ($availableCount === 0 && $bookedCount > 0) {
                $status = 'full';
            } elseif ($availableCount < count(self::TIME_SLOTS) && $availableCount > 0) {
                $status = 'partial';
            } elseif ($availableCount === 0) {
                $status = 'blocked';
            }

            $days[] = [
                'date' => $dateStr,
                'total_slots' => count(self::TIME_SLOTS),
                'available' => $availableCount,
                'booked' => $bookedCount,
                'blocked_slots' => count($blockedHours),
                'is_past' => false,
                'status' => $status,
            ];
        }

        return response()->json([
            'month' => $start->format('Y-m'),
            'days' => $days,
        ]);
    }

    /**
     * API: Slots disponibles para una fecha específica
     */
    public function slots(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $dateStr = $request->query('date');
        $date = Carbon::parse($dateStr, 'America/Mexico_City');
        $now = Carbon::now('America/Mexico_City');

        // Si es fin de semana, no hay slots
        if ($date->isWeekend()) {
            return response()->json([
                'date' => $dateStr,
                'slots' => [],
                'message' => 'No hay horarios disponibles en fin de semana.',
            ]);
        }

        // Obtener bloqueos para esta fecha
        $blockedSlots = MaintenanceBlockedSlot::getBlockedForRange($dateStr, $dateStr);
        $isFullDayBlocked = isset($blockedSlots[$dateStr]) && $blockedSlots[$dateStr] === 'all';
        $blockedHours = isset($blockedSlots[$dateStr]) && is_array($blockedSlots[$dateStr]) 
            ? $blockedSlots[$dateStr] 
            : [];

        // Obtener tickets reservados para esta fecha
        $bookedTickets = Ticket::where('tipo_problema', 'mantenimiento')
            ->whereDate('maintenance_scheduled_at', $dateStr)
            ->whereIn('estado', ['abierto', 'en_proceso'])
            ->get()
            ->map(function ($ticket) {
                return [
                    'time' => Carbon::parse($ticket->maintenance_scheduled_at)->format('H:i'),
                    'user' => $ticket->nombre_solicitante,
                ];
            })
            ->keyBy('time')
            ->toArray();

        $isToday = $date->isToday();
        $isPast = $date->copy()->endOfDay()->lessThanOrEqualTo($now);
        $slots = [];

        foreach (self::TIME_SLOTS as $slot) {
            $slotStart = $slot['start'];
            $slotEnd = $slot['end'];
            $label = $slot['label'];

            // Determinar estado del slot
            $slotDateTime = Carbon::parse($dateStr . ' ' . $slotStart, 'America/Mexico_City');
            $slotPast = $isPast || ($isToday && $slotDateTime->lessThanOrEqualTo($now));
            $isBlocked = $isFullDayBlocked || in_array($slotStart, $blockedHours);
            $isBooked = isset($bookedTickets[$slotStart]);

            $status = 'available';
            if ($slotPast) {
                $status = 'past';
            } elseif ($isBlocked) {
                $status = 'blocked';
            } elseif ($isBooked) {
                $status = 'booked';
            }

            $slots[] = [
                'start' => $slotStart,
                'end' => $slotEnd,
                'label' => $label,
                'status' => $status,
                'is_past' => $slotPast,
                'is_blocked' => $isBlocked,
                'is_booked' => $isBooked,
                'booked_by' => $isBooked ? $bookedTickets[$slotStart]['user'] : null,
            ];
        }

        return response()->json([
            'date' => $dateStr,
            'is_full_day_blocked' => $isFullDayBlocked,
            'slots' => $slots,
        ]);
    }

    /**
     * API: Verificar disponibilidad en tiempo real (para evitar doble reservación)
     */
    public function checkAvailability(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'time' => 'required|date_format:H:i',
        ]);

        $dateStr = $request->query('date');
        $timeStr = $request->query('time');
        
        // Verificar si está bloqueado
        $isBlocked = MaintenanceBlockedSlot::isBlocked($dateStr, $timeStr);
        
        if ($isBlocked) {
            return response()->json([
                'available' => false,
                'reason' => 'blocked',
                'message' => 'Este horario está bloqueado por el administrador.',
            ]);
        }

        // Verificar si ya hay reservación
        $isBooked = Ticket::where('tipo_problema', 'mantenimiento')
            ->whereDate('maintenance_scheduled_at', $dateStr)
            ->whereTime('maintenance_scheduled_at', $timeStr . ':00')
            ->whereIn('estado', ['abierto', 'en_proceso'])
            ->exists();

        if ($isBooked) {
            return response()->json([
                'available' => false,
                'reason' => 'booked',
                'message' => 'Este horario ya fue reservado por otro usuario.',
            ]);
        }

        // Verificar si la hora ya pasó
        $now = Carbon::now('America/Mexico_City');
        $slotDateTime = Carbon::parse($dateStr . ' ' . $timeStr, 'America/Mexico_City');
        
        if ($slotDateTime->lessThanOrEqualTo($now)) {
            return response()->json([
                'available' => false,
                'reason' => 'past',
                'message' => 'Este horario ya pasó.',
            ]);
        }

        return response()->json([
            'available' => true,
            'message' => 'Horario disponible.',
        ]);
    }

    /**
     * Admin: Vista principal de agenda de mantenimientos
     */
    public function adminIndex(): View
    {
        // Tickets sin ficha técnica asociada
        $ticketsWithoutProfile = Ticket::query()
            ->where('tipo_problema', 'mantenimiento')
            ->whereNull('computer_profile_id')
            ->where(function ($query) {
                $query->whereNull('closed_by_user')
                      ->orWhere('closed_by_user', false);
            })
            ->with(['user'])
            ->orderByDesc('created_at')
            ->get();

        $maintenanceTickets = Ticket::query()
            ->where('tipo_problema', 'mantenimiento')
            ->with(['computerProfile', 'user'])
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        // Perfiles de computadoras
        $profiles = ComputerProfile::with(['ticket'])
            ->orderByDesc('updated_at')
            ->get();

        // Bloqueos activos
        $blockedSlots = MaintenanceBlockedSlot::where('date_start', '>=', now()->toDateString())
            ->orWhere(function ($q) {
                $q->whereNotNull('date_end')
                  ->where('date_end', '>=', now()->toDateString());
            })
            ->orderBy('date_start')
            ->get();

        return view('Sistemas_IT.admin.maintenance.index', [
            'componentOptions' => $this->getReplacementComponentOptions(),
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
            'maintenanceTickets' => $maintenanceTickets,
            'ticketsWithoutProfile' => $ticketsWithoutProfile,
            'profiles' => $profiles,
            'blockedSlots' => $blockedSlots,
            'timeSlots' => self::TIME_SLOTS,
        ]);
    }

    /**
     * API: Obtener mantenimientos de la semana
     */
    public function getWeekMaintenances(Request $request): JsonResponse
    {
        $weekStart = $request->query('week_start');
        
        try {
            $startDate = $weekStart 
                ? Carbon::parse($weekStart)->startOfWeek(Carbon::MONDAY)
                : Carbon::now()->startOfWeek(Carbon::MONDAY);
        } catch (\Exception $e) {
            $startDate = Carbon::now()->startOfWeek(Carbon::MONDAY);
        }

        $endDate = $startDate->copy()->endOfWeek(Carbon::FRIDAY);

        // Obtener tickets de mantenimiento de la semana
        $tickets = Ticket::where('tipo_problema', 'mantenimiento')
            ->whereBetween('maintenance_scheduled_at', [$startDate->startOfDay(), $endDate->endOfDay()])
            ->whereIn('estado', ['abierto', 'en_proceso'])
            ->with(['user', 'computerProfile'])
            ->orderBy('maintenance_scheduled_at')
            ->get()
            ->map(function ($ticket) {
                $scheduledAt = Carbon::parse($ticket->maintenance_scheduled_at);
                return [
                    'id' => $ticket->id,
                    'folio' => $ticket->folio,
                    'solicitante' => $ticket->nombre_solicitante,
                    'correo' => $ticket->correo_solicitante,
                    'asunto' => $ticket->nombre_programa,
                    'estado' => $ticket->estado,
                    'fecha' => $scheduledAt->format('Y-m-d'),
                    'hora' => $scheduledAt->format('H:i'),
                    'hora_label' => $scheduledAt->format('h:i A'),
                    'dia_semana' => $scheduledAt->translatedFormat('l'),
                    'dia_numero' => $scheduledAt->day,
                    'profile_id' => $ticket->computer_profile_id,
                    'descripcion' => $ticket->descripcion_problema,
                ];
            })
            ->groupBy('fecha');

        // Obtener bloqueos de la semana
        $blockedSlots = MaintenanceBlockedSlot::getBlockedForRange(
            $startDate->toDateString(),
            $endDate->toDateString()
        );

        // Generar estructura de la semana
        $weekDays = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
            $weekDays[] = [
                'date' => $dateStr,
                'day_name' => $date->translatedFormat('l'),
                'day_number' => $date->day,
                'month' => $date->translatedFormat('F'),
                'is_today' => $date->isToday(),
                'maintenances' => $tickets[$dateStr] ?? [],
                'blocked' => $blockedSlots[$dateStr] ?? [],
            ];
        }

        return response()->json([
            'week_start' => $startDate->format('Y-m-d'),
            'week_end' => $endDate->format('Y-m-d'),
            'week_label' => $startDate->translatedFormat('d M') . ' - ' . $endDate->translatedFormat('d M, Y'),
            'days' => $weekDays,
        ]);
    }

    /**
     * API: Obtener días con mantenimientos para el calendario
     */
    public function getCalendarData(Request $request): JsonResponse
    {
        $month = $request->query('month');
        
        try {
            $start = $month ? Carbon::createFromFormat('Y-m', $month)->startOfMonth() : now()->startOfMonth();
        } catch (\Exception $e) {
            $start = now()->startOfMonth();
        }

        $end = $start->copy()->endOfMonth();

        // Obtener tickets con mantenimientos programados
        $maintenances = Ticket::where('tipo_problema', 'mantenimiento')
            ->whereBetween('maintenance_scheduled_at', [$start->startOfDay(), $end->endOfDay()])
            ->whereIn('estado', ['abierto', 'en_proceso'])
            ->select('maintenance_scheduled_at', DB::raw('COUNT(*) as count'))
            ->groupBy('maintenance_scheduled_at')
            ->get()
            ->mapWithKeys(function ($item) {
                $date = Carbon::parse($item->maintenance_scheduled_at)->format('Y-m-d');
                return [$date => ($item->count ?? 1)];
            })
            ->toArray();

        // Agrupar por fecha
        $daysWithMaintenances = [];
        foreach ($maintenances as $date => $count) {
            if (!isset($daysWithMaintenances[$date])) {
                $daysWithMaintenances[$date] = 0;
            }
            $daysWithMaintenances[$date] += $count;
        }

        return response()->json([
            'month' => $start->format('Y-m'),
            'days_with_maintenances' => $daysWithMaintenances,
        ]);
    }

    /**
     * Admin: Bloquear un horario o rango de fechas
     */
    public function blockSlot(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate([
            'date_start' => ['required', 'date_format:Y-m-d'],
            'date_end' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:date_start'],
            'time_slot' => ['nullable', 'date_format:H:i'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $block = MaintenanceBlockedSlot::create([
            'date_start' => $data['date_start'],
            'date_end' => $data['date_end'] ?? null,
            'time_slot' => $data['time_slot'] ?? null,
            'reason' => $data['reason'] ?? null,
            'blocked_by' => auth()->id(),
        ]);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Horario bloqueado correctamente.',
                'block' => $block,
            ]);
        }

        return back()->with('success', 'Horario bloqueado correctamente.');
    }

    /**
     * Admin: Desbloquear un horario
     */
    public function unblockSlot(MaintenanceBlockedSlot $block): RedirectResponse|JsonResponse
    {
        $block->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Bloqueo eliminado correctamente.',
            ]);
        }

        return back()->with('success', 'Bloqueo eliminado correctamente.');
    }

    // ================== COMPUTER PROFILES ==================

    public function showComputer(ComputerProfile $computerProfile): View
    {
        $tickets = Ticket::query()
            ->where('tipo_problema', 'mantenimiento')
            ->where('computer_profile_id', $computerProfile->id)
            ->with(['user'])
            ->orderByDesc('created_at')
            ->get();

        $latestTicket = $tickets->first();
        $historyTickets = $tickets->skip(1);

        $empleado = null;
        if ($computerProfile->is_loaned && $computerProfile->loaned_to_email) {
            $empleado = \App\Models\Empleado::where('correo', $computerProfile->loaned_to_email)->first();
        }

        return view('Sistemas_IT.admin.maintenance.computers.show', [
            'profile' => $computerProfile,
            'computerProfile' => $computerProfile,
            'tickets' => $tickets,
            'latestTicket' => $latestTicket,
            'historyTickets' => $historyTickets,
            'empleado' => $empleado,
            'componentOptions' => $this->getReplacementComponentOptions(),
        ]);
    }

    private function getReplacementComponentOptions(): array
    {
        return [
            'disco_duro' => 'Disco duro',
            'ram' => 'RAM',
            'bateria' => 'Batería',
            'pantalla' => 'Pantalla',
            'conectores' => 'Conectores',
            'teclado' => 'Teclado',
            'mousepad' => 'Mousepad',
            'cargador' => 'Cargador',
        ];
    }

    public function storeComputer(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'identifier' => ['nullable', 'string', 'max:255'],
            'maintenance_ticket_id' => ['nullable', 'integer', 'exists:tickets,id'],
            'brand' => ['nullable', 'string', 'max:255'],
            'model' => ['nullable', 'string', 'max:255'],
            'disk_type' => ['nullable', 'string', 'max:255'],
            'ram_capacity' => ['nullable', 'string', 'max:255'],
            'battery_status' => ['nullable', 'in:functional,partially_functional,damaged'],
            'aesthetic_observations' => ['nullable', 'string'],
            'replacement_components' => ['nullable', 'array'],
            'replacement_components.*' => ['string'],
            'last_maintenance_at' => ['nullable', 'date'],
            'is_loaned' => ['nullable', 'boolean'],
            'loaned_to_name' => ['nullable', 'string', 'max:255'],
            'loaned_to_email' => ['nullable', 'email', 'max:255'],
        ]);

        $profile = ComputerProfile::create([
            'identifier' => $data['identifier'] ?? null,
            'brand' => $data['brand'] ?? null,
            'model' => $data['model'] ?? null,
            'disk_type' => $data['disk_type'] ?? null,
            'ram_capacity' => $data['ram_capacity'] ?? null,
            'battery_status' => $data['battery_status'] ?? null,
            'aesthetic_observations' => $data['aesthetic_observations'] ?? null,
            'replacement_components' => $data['replacement_components'] ?? null,
            'last_maintenance_at' => $data['last_maintenance_at'] ?? null,
            'is_loaned' => isset($data['is_loaned']) ? (bool)$data['is_loaned'] : false,
            'loaned_to_name' => $data['loaned_to_name'] ?? null,
            'loaned_to_email' => $data['loaned_to_email'] ?? null,
            'last_ticket_id' => $data['maintenance_ticket_id'] ?? null,
        ]);

        if (!empty($data['maintenance_ticket_id'])) {
            Ticket::where('id', $data['maintenance_ticket_id'])->update([
                'computer_profile_id' => $profile->id,
            ]);
        }

        return back()->with('success', 'Ficha técnica registrada correctamente.');
    }

    public function editComputer(ComputerProfile $computerProfile): View
    {
        return view('Sistemas_IT.admin.maintenance.computers.edit', [
            'profile' => $computerProfile,
            'componentOptions' => $this->getReplacementComponentOptions(),
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
            'maintenanceTickets' => Ticket::where('tipo_problema', 'mantenimiento')
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }

    public function updateComputer(Request $request, ComputerProfile $computerProfile): RedirectResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:100', Rule::unique('computer_profiles', 'identifier')->ignore($computerProfile->id)],
            'brand' => ['nullable', 'string', 'max:100'],
            'model' => ['nullable', 'string', 'max:100'],
            'disk_type' => ['nullable', 'string', 'max:100'],
            'ram_capacity' => ['nullable', 'string', 'max:50'],
            'battery_status' => ['nullable', 'in:functional,partially_functional,damaged'],
            'last_maintenance_at' => ['nullable', 'date'],
            'aesthetic_observations' => ['nullable', 'string'],
            'replacement_components' => ['nullable', 'array'],
            'replacement_components.*' => ['string'],
            'is_loaned' => ['nullable', 'boolean'],
            'loaned_to_name' => ['nullable', 'required_if:is_loaned,1', 'string', 'max:255'],
            'loaned_to_email' => ['nullable', 'required_if:is_loaned,1', 'email', 'max:255'],
            'maintenance_ticket_id' => ['nullable', 'exists:tickets,id'],
        ]);

        $data['is_loaned'] = $request->has('is_loaned');
        if (!$data['is_loaned']) {
            $data['loaned_to_name'] = null;
            $data['loaned_to_email'] = null;
        }

        if (!empty($data['maintenance_ticket_id'])) {
            $data['last_ticket_id'] = $data['maintenance_ticket_id'];
        }

        $computerProfile->update($data);

        return redirect()
            ->route('admin.maintenance.computers.show', $computerProfile)
            ->with('success', 'Ficha técnica actualizada correctamente.');
    }

    public function destroyComputer(ComputerProfile $computerProfile): RedirectResponse
    {
        $computerProfile->delete();

        return redirect()
            ->route('admin.maintenance.index')
            ->with('success', 'Ficha técnica eliminada correctamente.');
    }
}
