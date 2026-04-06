<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\BlockedEmail;
use App\Models\User;
use App\Models\Empleado;
use App\Models\EmpleadoBaja;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UsersController extends Controller
{
    public function index()
    {
        $approvedUsers = User::where('status', User::STATUS_APPROVED)
            ->orderBy('created_at', 'desc')
            ->paginate(15, ['*'], 'approved_page');

        $pendingUsers = User::where('status', User::STATUS_PENDING)
            ->orderBy('created_at')
            ->get();

        $rejectedUsers = User::where('status', User::STATUS_REJECTED)
            ->orderByDesc('rejected_at')
            ->get();
        
        // Cargar información de bajas para usuarios rechazados
        $empleadosBaja = EmpleadoBaja::all()->keyBy('correo');

        $blockedEmails = BlockedEmail::orderByDesc('created_at')->get();

        return view('admin.users.index', compact('approvedUsers', 'pendingUsers', 'rejectedUsers', 'blockedEmails', 'empleadosBaja'));
    }

    public function create()
    {
        $subdepartamentosCE = \App\Models\Subdepartamento::where('area', 'Comercio Exterior')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        // Solo coordinadores y director para el select de Jefe Directo
        $jefes = Empleado::where('es_activo', true)
            ->where(function ($q) {
                $q->where('es_coordinador', true)
                  ->orWhere('posicion', 'Direccion');
            })
            ->orderBy('nombre')
            ->get();

        return view('admin.users.create', [
            'subdepartamentosCE' => $subdepartamentosCE,
            'jefes' => $jefes,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required','string','email','max:255',
                Rule::unique('users', 'email')->where(fn ($q) => $q->whereNot('status', User::STATUS_REJECTED)),
            ],
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:user,admin',
            'area' => 'required|string|max:255',
            'subdepartamento_id' => 'nullable|integer|exists:subdepartamentos,id',
            'id_empleado' => 'nullable|string|max:30|unique:empleados,id_empleado',
            'posicion' => 'required|string|max:255',
            'es_coordinador' => 'nullable|boolean',
            'supervisor_id' => 'nullable|exists:empleados,id',
        ]);

        if ($request->area === 'Comercio Exterior' && !$request->filled('subdepartamento_id')) {
            return back()->withErrors(['subdepartamento_id' => 'Debes seleccionar un subdepartamento para Comercio Exterior'])->withInput();
        }

        // Si existe un usuario dado de baja con el mismo correo, eliminarlo para poder reutilizar el correo
        $usuarioBajaAnterior = User::where('email', $request->email)
            ->where('status', User::STATUS_REJECTED)
            ->first();
        if ($usuarioBajaAnterior) {
            EmpleadoBaja::where('user_id', $usuarioBajaAnterior->id)->delete();
            $usuarioBajaAnterior->delete();
        }

        // 1. Crear Usuario (Login)
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => $request->role,
            'email_verified_at' => now(),
            'status' => User::STATUS_APPROVED,
            'approved_at' => now(),
        ]);

        // 2. Crear Empleado (Perfil RH)
        Empleado::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nombre' => $user->name,
                'correo' => $user->email,
                'area' => $request->area,
                'subdepartamento_id' => $request->area === 'Comercio Exterior' ? $request->subdepartamento_id : null,
                'id_empleado' => $request->id_empleado,
                'posicion' => $request->posicion,
                'es_coordinador' => $request->boolean('es_coordinador'),
                'supervisor_id' => $request->supervisor_id,
                'es_activo' => true,
            ]
        );

        return redirect()->route('admin.users')->with('success', 'Usuario y empleado creados exitosamente.');
    }

    public function show(User $user)
    {
        $tickets = $user->tickets()->orderBy('created_at', 'desc')->get();

        $stats = [
            'total_tickets' => $tickets->count(),
            'tickets_abiertos' => $tickets->where('estado', 'abierto')->count(),
            'tickets_en_proceso' => $tickets->where('estado', 'en_proceso')->count(),
            'tickets_cerrados' => $tickets->whereIn('estado', ['cerrado', 'cerrados'])->count(),
        ];

        return view('admin.users.show', compact('user', 'tickets', 'stats'));
    }

    public function edit(User $user)
    {
        $subdepartamentosCE = \App\Models\Subdepartamento::where('area', 'Comercio Exterior')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();
            
        // Solo coordinadores y director para el select de Jefe Directo
        $jefes = Empleado::where('es_activo', true)
            ->where(function ($q) {
                $q->where('es_coordinador', true)
                  ->orWhere('posicion', 'Direccion');
            })
            ->where('user_id', '!=', $user->id)
            ->orderBy('nombre')
            ->get();

        // Cargamos el modelo empleado asociado para llenar los campos
        $empleado = Empleado::where('user_id', $user->id)->first();

        return view('admin.users.edit', [
            'user' => $user,
            'empleado' => $empleado,
            'subdepartamentosCE' => $subdepartamentosCE,
            'jefes' => $jefes,
        ]);
    }

    public function update(Request $request, User $user)
    {
        // Obtenemos el ID del empleado asociado para la validación unique
        $empleado = Empleado::where('user_id', $user->id)->first();
        $empleadoId = $empleado ? $empleado->id : null;

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required','string','email','max:255'],
            'password' => 'nullable|string|min:8|confirmed',
            'role' => 'required|in:user,admin',
            'area' => 'required|string|max:255',
            'subdepartamento_id' => 'nullable|integer|exists:subdepartamentos,id',
            'id_empleado' => 'nullable|string|max:30|unique:empleados,id_empleado,' . $empleadoId,
            'posicion' => 'required|string|max:255',
            'es_coordinador' => 'nullable|boolean',
            'supervisor_id' => 'nullable|exists:empleados,id',
        ]);

        if ($request->area === 'Comercio Exterior' && !$request->filled('subdepartamento_id')) {
            return back()->withErrors(['subdepartamento_id' => 'Debes seleccionar un subdepartamento para Comercio Exterior'])->withInput();
        }

        if ($request->email !== $user->email && User::where('email', $request->email)->exists()) {
            return back()->withErrors(['email' => 'Este correo electrónico ya está registrado.'])->withInput();
        }

        $data = ['name' => $request->name, 'email' => $request->email, 'role' => $request->role];
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        // Actualizar registro empleado
        $empleadoData = [
            'nombre' => $user->name,
            'correo' => $user->email,
            'area' => $request->area,
            'subdepartamento_id' => $request->area === 'Comercio Exterior' ? $request->subdepartamento_id : null,
            'posicion' => $request->posicion,
            'es_coordinador' => $request->boolean('es_coordinador'),
            'supervisor_id' => $request->supervisor_id,
        ];

        // Solo actualizar id_empleado si viene explícitamente en el request
        // para evitar borrarlo cuando el formulario no incluye ese campo.
        if ($request->has('id_empleado')) {
            $empleadoData['id_empleado'] = $request->id_empleado;
        }

        Empleado::updateOrCreate(
            ['user_id' => $user->id],
            $empleadoData
        );

        return redirect()->route('admin.users.show', $user)->with('success', 'Usuario actualizado exitosamente.');
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return redirect()->back()->with('error', 'No puedes eliminarte a ti mismo.');
        }

        $user->delete();
        return redirect()->route('admin.users')->with('success', 'Usuario eliminado exitosamente.');
    }

    public function destroyRejected(User $user)
    {
        if ($user->status !== User::STATUS_REJECTED) {
            return redirect()->route('admin.users')->with('error', 'Solo puedes eliminar solicitudes rechazadas.');
        }
        $user->delete();
        return redirect()->route('admin.users')->with('success', 'Solicitud rechazada eliminada correctamente.');
    }

    public function destroyBlockedEmail(BlockedEmail $blockedEmail)
    {
        $blockedEmail->delete();
        return redirect()->route('admin.users')->with('success', 'Correo desbloqueado correctamente.');
    }

    public function approve(User $user)
    {
        if ($user->status !== User::STATUS_PENDING) {
            return redirect()->route('admin.users')->with('info', 'Este usuario ya fue procesado.');
        }

        $user->update([
            'status' => User::STATUS_APPROVED,
            'approved_at' => now(),
            'rejected_at' => null,
            'email_verified_at' => $user->email_verified_at ?? now(),
        ]);

        return redirect()->route('admin.users')->with('success', 'Usuario aprobado y habilitado para acceder al sistema.');
    }

    public function reject(Request $request, User $user)
    {
        if ($user->status !== User::STATUS_PENDING) {
            return redirect()->route('admin.users')->with('info', 'Este usuario ya fue procesado.');
        }

        $data = $request->validate(['reason' => 'nullable|string|max:255']);

        BlockedEmail::updateOrCreate(
            ['email' => $user->email],
            ['reason' => $data['reason'] ?? null, 'blocked_by' => auth()->id()]
        );

        $user->update([
            'status' => User::STATUS_REJECTED,
            'rejected_at' => now(),
            'approved_at' => null,
            'email_verified_at' => null,
        ]);

        return redirect()->route('admin.users')->with('success', 'Solicitud rechazada y correo marcado como no permitido.');
    }

    public function darDeBaja(Request $request, User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No puedes darte de baja a ti mismo.');
        }

        if ($user->status !== User::STATUS_APPROVED) {
            return back()->with('error', 'Solo puedes dar de baja a usuarios activos.');
        }

        $data = $request->validate([
            'motivo_baja' => 'required|string|max:255',
            'observaciones' => 'nullable|string|max:1000',
        ]);

        $empleado = Empleado::where('user_id', $user->id)->first();

        EmpleadoBaja::create([
            'empleado_id' => $empleado?->id,
            'user_id' => $user->id,
            'nombre' => $empleado?->nombre ?? $user->name,
            'correo' => $user->email,
            'motivo_baja' => $data['motivo_baja'],
            'fecha_baja' => now()->toDateString(),
            'observaciones' => $data['observaciones'],
        ]);

        if ($empleado) {
            $empleado->update(['es_activo' => false]);
        }

        $user->update([
            'status' => User::STATUS_REJECTED,
            'rejected_at' => now(),
        ]);

        return redirect()->route('admin.users')->with('success', 'El usuario ' . $user->name . ' ha sido dado de baja exitosamente.');
    }

    public function reactivar(User $user)
    {
        if ($user->status !== User::STATUS_REJECTED) {
            return back()->with('error', 'Solo puedes reactivar usuarios dados de baja.');
        }

        // Eliminar registro de baja
        EmpleadoBaja::where('user_id', $user->id)->delete();

        // Reactivar empleado si existe
        $empleado = Empleado::where('user_id', $user->id)->first();
        if ($empleado) {
            $empleado->update(['es_activo' => true]);
        }

        // Restaurar estado del usuario
        $user->update([
            'status'      => User::STATUS_APPROVED,
            'rejected_at' => null,
        ]);

        return redirect()->route('admin.users')->with('success', 'El usuario ' . $user->name . ' ha sido reactivado exitosamente.');
    }
}