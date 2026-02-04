<?php

namespace App\Http\Controllers\Sistemas_IT;

use App\Http\Controllers\Controller;
use App\Models\Sistemas_IT\ComputerProfile;
use App\Models\Sistemas_IT\MaintenanceSlot;
use App\Models\Sistemas_IT\Ticket;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class MaintenanceController extends Controller
{
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

        $slots = MaintenanceSlot::active()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->get()
            ->groupBy(fn ($slot) => $slot->date->toDateString())
            ->map(function ($daySlots) use ($now) {
                $date = $daySlots->first()->date->toDateString();
                $dayDate = Carbon::parse($date, 'America/Mexico_City');

                $futureSlots = $daySlots->filter(fn (MaintenanceSlot $slot) => $slot->start_date_time->greaterThan($now));

                $totalCapacity = $futureSlots->sum('capacity');
                $booked = $futureSlots->sum('booked_count');
                $available = max(0, $totalCapacity - $booked);
                $availableSlots = $futureSlots->filter(fn (MaintenanceSlot $slot) => $slot->available_capacity > 0)->count();

                if ($dayDate->copy()->endOfDay()->lessThanOrEqualTo($now) || $futureSlots->isEmpty()) {
                    $status = 'past';
                    $available = 0;
                } elseif ($available === 0) {
                    $status = 'full';
                } elseif ($booked === 0) {
                    $status = 'available';
                } else {
                    $status = 'partial';
                }

                return [
                    'date' => $date,
                    'total_slots' => $daySlots->count(),
                    'total_capacity' => $totalCapacity,
                    'booked' => $booked,
                    'available' => $available,
                    'available_slots' => $availableSlots,
                    'is_past' => $status === 'past',
                    'status' => $status,
                ];
            })
            ->values();

        return response()->json([
            'month' => $start->format('Y-m'),
            'days' => $slots,
        ]);
    }

    public function slots(Request $request): JsonResponse
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $now = Carbon::now('America/Mexico_City');

        $slots = MaintenanceSlot::active()
            ->whereDate('date', $request->query('date'))
            ->orderBy('start_time')
            ->get()
            ->map(function (MaintenanceSlot $slot) use ($now) {
                $start = $slot->start_date_time;
                $end = $slot->end_date_time;

                $isPast = $start->lessThanOrEqualTo($now);
                $availableCapacity = $isPast ? 0 : $slot->available_capacity;

                if ($isPast) {
                    $status = 'past';
                } elseif ($availableCapacity === 0) {
                    $status = 'full';
                } elseif ($availableCapacity === $slot->capacity) {
                    $status = 'available';
                } else {
                    $status = 'partial';
                }

                return [
                    'id' => $slot->id,
                    'start' => $start->format('H:i'),
                    'end' => $end->format('H:i'),
                    'label' => $start->format('H:i') . ' - ' . $end->format('H:i'),
                    'available' => $availableCapacity,
                    'capacity' => $slot->capacity,
                    'status' => $status,
                    'is_past' => $isPast,
                ];
            });

        return response()->json([
            'date' => $request->query('date'),
            'slots' => $slots,
        ]);
    }

    // Admin sub-funciones (no expuestas por ruta actualmente)
    public function adminIndex(): View
    {
        $slots = MaintenanceSlot::orderBy('date')->orderBy('start_time')->get();
        $groupedSlots = $slots->groupBy(fn ($slot) => Carbon::parse($slot->date)->format('Y-m-d'));
        
        // Tickets sin ficha técnica asociada para el seguimiento
        $ticketsWithoutProfile = Ticket::query()
            ->where('tipo_problema', 'mantenimiento')
            ->whereNull('computer_profile_id')
            ->where(function ($query) {
                $query->whereNull('closed_by_user')
                      ->orWhere('closed_by_user', false);
            })
            ->with(['maintenanceSlot'])
            ->orderByDesc('created_at')
            ->get();

        $maintenanceTickets = Ticket::query()
            ->where('tipo_problema', 'mantenimiento')
            ->with(['computerProfile', 'maintenanceSlot'])
            ->orderByDesc('created_at')
            ->limit(15)
            ->get();

        // Perfiles de computadoras para expedientes
        $profiles = ComputerProfile::with(['ticket'])
            ->orderByDesc('updated_at')
            ->get();

        return view('admin.maintenance.index', [
            'groupedSlots' => $groupedSlots,
            'componentOptions' => $this->getReplacementComponentOptions(),
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
            'maintenanceTickets' => $maintenanceTickets,
            'ticketsWithoutProfile' => $ticketsWithoutProfile,
            'profiles' => $profiles,
        ]);
    }

    public function showComputer(ComputerProfile $computerProfile): View
    {
        $tickets = Ticket::query()
            ->where('tipo_problema', 'mantenimiento')
            ->where('computer_profile_id', $computerProfile->id)
            ->with(['maintenanceSlot'])
            ->orderByDesc('created_at')
            ->get();

        // Obtener el último ticket asociado
        $latestTicket = $tickets->first();
        
        // Obtener tickets históricos (excluyendo el último)
        $historyTickets = $tickets->skip(1);

        // Obtener el empleado asociado al equipo prestado
        $empleado = null;
        if ($computerProfile->is_loaned && $computerProfile->loaned_to_email) {
            $empleado = \App\Models\Empleado::where('correo', $computerProfile->loaned_to_email)->first();
        }

        return view('admin.maintenance.computers.show', [
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

    // ================== ADMIN CRUD FOR MAINTENANCE SLOTS ==================
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'date' => ['required', 'date_format:Y-m-d'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'capacity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $date = Carbon::createFromFormat('Y-m-d', $data['date']);
        $start = Carbon::createFromFormat('H:i', $data['start_time']);
        $end = Carbon::createFromFormat('H:i', $data['end_time']);

        MaintenanceSlot::firstOrCreate([
            'date' => $date->format('Y-m-d'),
            'start_time' => $start->format('H:i:s'),
            'end_time' => $end->format('H:i:s'),
        ], [
            'capacity' => $data['capacity'],
            'booked_count' => 0,
            'is_active' => true,
        ]);

        return back()->with('success', 'Horario creado correctamente.');
    }

    // Store a new computer profile (ficha técnica)
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
            'loaned_to_name' => ['nullable', 'string', 'max:255'],
        ]);

        $profile = \App\Models\Sistemas_IT\ComputerProfile::create([
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

    public function storeBulk(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date_format:Y-m-d'],
            'end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'days' => ['required', 'array', 'min:1'],
            'days.*' => ['in:monday,tuesday,wednesday,thursday,friday,saturday,sunday'],
            'bulk_start_time' => ['required', 'date_format:H:i'],
            'bulk_end_time' => ['required', 'date_format:H:i', 'after:bulk_start_time'],
            'total_capacity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $startDate = Carbon::createFromFormat('Y-m-d', $data['start_date']);
        $endDate = Carbon::createFromFormat('Y-m-d', $data['end_date']);
        $startTime = Carbon::createFromFormat('H:i', $data['bulk_start_time']);
        $endTime = Carbon::createFromFormat('H:i', $data['bulk_end_time']);
        $diffMinutes = $startTime->diffInMinutes($endTime);
        $segments = (int) $data['total_capacity'];

        if ($diffMinutes < $segments) {
            return back()->withErrors(['total_capacity' => 'La capacidad excede la duración disponible.'])->withInput();
        }

        $slotMinutes = intdiv($diffMinutes, $segments);
        if ($slotMinutes < 1) {
            return back()->withErrors(['bulk_start_time' => 'Duración por horario inválida. Ajusta los valores.'])->withInput();
        }

        $daysMap = [
            'sunday' => 0,
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
        ];

        $selectedDaysNumbers = collect($data['days'])->map(fn ($d) => $daysMap[$d])->all();
        $created = 0;

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            if (!in_array($date->dayOfWeek, $selectedDaysNumbers, true)) {
                continue;
            }
            for ($i = 0; $i < $segments; $i++) {
                $slotStart = $startTime->copy()->addMinutes($i * $slotMinutes);
                $slotEnd = $i === $segments - 1 ? $endTime->copy() : $slotStart->copy()->addMinutes($slotMinutes);
                if ($slotEnd->gt($endTime)) {
                    break;
                }
                $exists = MaintenanceSlot::where('date', $date->format('Y-m-d'))
                    ->where('start_time', $slotStart->format('H:i:s'))
                    ->where('end_time', $slotEnd->format('H:i:s'))
                    ->exists();
                if ($exists) {
                    continue;
                }
                MaintenanceSlot::create([
                    'date' => $date->format('Y-m-d'),
                    'start_time' => $slotStart->format('H:i:s'),
                    'end_time' => $slotEnd->format('H:i:s'),
                    'capacity' => 1,
                    'booked_count' => 0,
                    'is_active' => true,
                ]);
                $created++;
            }
        }

        return back()->with('success', "Se crearon {$created} horarios en lote correctamente.");
    }

    public function updateSlot(Request $request, MaintenanceSlot $slot): RedirectResponse
    {
        $data = $request->validate([
            'capacity' => ['required', 'integer', 'min:1', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
        ]);
        $slot->update([
            'capacity' => $data['capacity'],
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : false,
        ]);
        return back()->with('success', 'Horario actualizado.');
    }

    public function destroySlot(MaintenanceSlot $slot): RedirectResponse
    {
        $slot->delete();
        return back()->with('success', 'Horario eliminado.');
    }

    public function destroyPastSlots(): RedirectResponse
    {
        $now = Carbon::now('America/Mexico_City');
        $today = $now->toDateString();
        $timeNow = $now->format('H:i:s');

        $toDelete = MaintenanceSlot::where(function ($q) use ($today, $timeNow) {
            $q->where('date', '<', $today)
              ->orWhere(function ($qq) use ($today, $timeNow) {
                  $qq->where('date', $today)->where('end_time', '<=', $timeNow);
              });
        })->get();

        $count = $toDelete->count();
        if ($count > 0) {
            MaintenanceSlot::whereIn('id', $toDelete->pluck('id'))->delete();
        }
        return back()->with('success', "Se eliminaron {$count} horarios pasados.");
    }

    public function editComputer(ComputerProfile $computerProfile): View
    {
        return view('admin.maintenance.computers.edit', [
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
