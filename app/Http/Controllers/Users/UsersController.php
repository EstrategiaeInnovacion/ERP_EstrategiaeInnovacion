<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\BlockedEmail;
use App\Models\User;
use App\Models\Empleado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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

        $blockedEmails = BlockedEmail::orderByDesc('created_at')->get();

        return view('admin.users.index', compact('approvedUsers', 'pendingUsers', 'rejectedUsers', 'blockedEmails'));
    }

    public function create()
    {
        $subdepartamentosCE = \App\Models\Subdepartamento::where('area', 'Comercio Exterior')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        // Enviamos la lista de empleados para seleccionar al Supervisor
        $empleados = Empleado::where('es_activo', true)
            ->orderBy('nombre')
            ->get();

        return view('admin.users.create', [
            'subdepartamentosCE' => $subdepartamentosCE,
            'empleados' => $empleados,
        ]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required','string','email','max:255','unique:users'],
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:user,admin,invitado,colaborador',
            'area' => 'required|in:Legal,Logistica,RH,Comercio Exterior,Sistemas,Socio',
            'subdepartamento_id' => 'nullable|integer|exists:subdepartamentos,id',
            
            // --- NUEVOS CAMPOS AGREGADOS ---
            'id_empleado' => 'nullable|string|max:30|unique:empleados,id_empleado', // ID Nómina/Reloj
            'posicion' => 'required|string|max:255',
            'supervisor_id' => 'nullable|exists:empleados,id',
        ]);

        if ($request->area === 'Comercio Exterior' && !$request->filled('subdepartamento_id')) {
            return back()->withErrors(['subdepartamento_id' => 'Debes seleccionar un subdepartamento para Comercio Exterior'])->withInput();
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
                
                // Guardamos los campos adicionales
                'id_empleado' => $request->id_empleado, // <--- AQUÍ SE GUARDA EL ID DE EMPLEADO
                'posicion' => $request->posicion,
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
            
        // También necesitamos empleados en la edición para cambiar supervisor
        $empleados = Empleado::where('es_activo', true)
            ->where('user_id', '!=', $user->id) // Evitar que se seleccione a sí mismo como jefe
            ->orderBy('nombre')
            ->get();
            
        // Cargamos el modelo empleado asociado para llenar los campos
        $empleado = Empleado::where('user_id', $user->id)->first();

        return view('admin.users.edit', [
            'user' => $user,
            'empleado' => $empleado, // Pasamos los datos del empleado a la vista
            'subdepartamentosCE' => $subdepartamentosCE,
            'empleados' => $empleados,
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
            'role' => 'required|in:user,admin,invitado,colaborador',
            'area' => 'required|in:Legal,Logistica,RH,Comercio Exterior,Sistemas,Socio',
            'subdepartamento_id' => 'nullable|integer|exists:subdepartamentos,id',
            
            // Validación en Update (ignorando el propio registro)
            'id_empleado' => 'nullable|string|max:30|unique:empleados,id_empleado,' . $empleadoId,
            'posicion' => 'required|string|max:255',
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
        Empleado::updateOrCreate(
            ['user_id' => $user->id],
            [
                'nombre' => $user->name,
                'correo' => $user->email,
                'area' => $request->area,
                'subdepartamento_id' => $request->area === 'Comercio Exterior' ? $request->subdepartamento_id : null,
                
                // Actualizamos los campos nuevos
                'id_empleado' => $request->id_empleado,
                'posicion' => $request->posicion,
                'supervisor_id' => $request->supervisor_id,
            ]
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
}