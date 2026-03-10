@extends('layouts.master')

@section('title', 'Gestión de Usuarios - Admin')

@section('content')
<div class="min-h-screen bg-slate-50 pb-12">
    <div class="bg-white border-b border-slate-200 mb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Usuarios del Sistema</h1>
                    <p class="text-slate-500 mt-1 text-lg">Control de acceso y administración de roles.</p>
                </div>
                <a href="{{ route('admin.users.create') }}"
                   class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white font-bold text-sm rounded-xl hover:bg-indigo-700 transition shadow-lg shadow-indigo-200">
                    <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                    </svg>
                    Crear Usuario
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        @foreach(['success' => 'emerald', 'error' => 'red', 'info' => 'blue'] as $key => $color)
            @if(session($key))
                <div class="mb-6 flex items-center p-4 bg-{{ $color }}-50 border border-{{ $color }}-200 rounded-2xl shadow-sm">
                    <div class="p-2 bg-{{ $color }}-100 rounded-full text-{{ $color }}-600 mr-3">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            @if($key == 'success') <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            @elseif($key == 'error') <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            @else <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 18a9 9 0 110-18 9 9 0 010 18z"></path> @endif
                        </svg>
                    </div>
                    <p class="text-{{ $color }}-800 font-medium">{{ session($key) }}</p>
                </div>
            @endif
        @endforeach

        @if($pendingUsers->count() > 0)
            <div class="bg-white rounded-[2rem] shadow-sm border border-amber-200 mb-10 overflow-hidden relative">
                <div class="absolute top-0 left-0 w-1 h-full bg-amber-400"></div>
                <div class="px-8 py-6 bg-amber-50/50 border-b border-amber-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                            <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                            Solicitudes Pendientes
                        </h3>
                        <p class="text-sm text-slate-500 mt-1">{{ $pendingUsers->count() }} usuario(s) esperando aprobación de acceso.</p>
                    </div>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <tbody class="divide-y divide-amber-100/50">
                            @foreach($pendingUsers as $user)
                                <tr class="hover:bg-amber-50/30 transition-colors">
                                    <td class="px-8 py-5">
                                        <div class="flex flex-col">
                                            <span class="font-bold text-slate-900">{{ $user->name }}</span>
                                            <span class="text-sm text-slate-500">{{ $user->email }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-5 text-sm text-slate-500">
                                        Solicitado <span class="font-bold">{{ $user->created_at->diffForHumans() }}</span>
                                    </td>
                                    <td class="px-8 py-5 text-right">
                                        <div class="flex justify-end gap-3">
                                            <form method="POST" action="{{ route('admin.users.approve', $user) }}">
                                                @csrf
                                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-xs font-bold uppercase tracking-wide rounded-xl hover:bg-emerald-700 transition shadow-sm">
                                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                    Aprobar
                                                </button>
                                            </form>

                                            <form method="POST" action="{{ route('admin.users.reject', $user) }}" class="flex items-center gap-2">
                                                @csrf
                                                <input type="text" name="reason" placeholder="Motivo (opcional)" class="hidden sm:block px-3 py-2 border border-slate-200 rounded-lg text-xs focus:ring-red-500 focus:border-red-500">
                                                <button type="submit" class="inline-flex items-center px-4 py-2 bg-white border border-red-200 text-red-600 text-xs font-bold uppercase tracking-wide rounded-xl hover:bg-red-50 transition">
                                                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                                    Rechazar
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                <div>
                    <h3 class="text-lg font-bold text-slate-900">Usuarios Activos</h3>
                    <p class="text-sm text-slate-500">{{ $approvedUsers->total() }} usuarios registrados.</p>
                </div>
            </div>

            @if($approvedUsers->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50 border-b border-slate-100">
                                <th class="px-8 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Usuario</th>
                                <th class="px-8 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Rol</th>
                                <th class="px-8 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider">Actividad</th>
                                <th class="px-8 py-4 text-xs font-bold text-slate-400 uppercase tracking-wider text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($approvedUsers as $user)
                                <tr class="hover:bg-slate-50/80 transition-colors">
                                    <td class="px-8 py-4">
                                        <div class="flex items-center">
                                            <div class="w-10 h-10 bg-indigo-100 text-indigo-600 rounded-full flex items-center justify-center mr-4 text-sm font-bold border border-indigo-200">
                                                {{ substr($user->name, 0, 1) }}
                                            </div>
                                            <div>
                                                <div class="font-bold text-slate-900">{{ $user->name }}</div>
                                                <div class="text-xs text-slate-500">{{ $user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-8 py-4">
                                        @if($user->role === 'admin')
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold uppercase bg-purple-50 text-purple-700 border border-purple-100">
                                                <span class="w-1.5 h-1.5 rounded-full bg-purple-500"></span> Admin
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-[10px] font-bold uppercase bg-slate-100 text-slate-600 border border-slate-200">
                                                Usuario
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-8 py-4">
                                        <div class="flex flex-col">
                                            <span class="text-xs font-bold text-slate-700">{{ $user->tickets()->count() }} tickets</span>
                                            <span class="text-[10px] text-slate-400">Reg: {{ $user->created_at->format('d/m/Y') }}</span>
                                        </div>
                                    </td>
                                    <td class="px-8 py-4 text-right">
                                        <div class="flex justify-end gap-2">
                                            <a href="{{ route('admin.users.edit', $user) }}" class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-indigo-600 hover:border-indigo-200 hover:shadow-sm transition-all" title="Editar">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                            </a>
                                            @if($user->id !== auth()->id())
                                                <button class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-white border border-amber-200 text-amber-400 hover:text-amber-600 hover:border-amber-300 hover:shadow-sm transition-all"
                                                        onclick="abrirModalBaja({{ $user->id }}, '{{ addslashes($user->name) }}')"
                                                        title="Dar de Baja">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                                </button>
                                                <button class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-red-600 hover:border-red-200 hover:shadow-sm transition-all"
                                                        data-delete-user
                                                        data-user-id="{{ $user->id }}"
                                                        data-user-name="{{ $user->name }}" title="Eliminar">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if($approvedUsers->hasPages())
                    <div class="px-8 py-6 border-t border-slate-100">
                        {{ $approvedUsers->links() }}
                    </div>
                @endif
            @else
                <div class="py-12 text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-slate-50 mb-4">
                        <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197m13.5-9a2.5 2.5 0 11-5 0 2.5 2.5 0 015 0z"></path></svg>
                    </div>
                    <h3 class="text-lg font-bold text-slate-900">No hay usuarios</h3>
                    <p class="text-slate-500 mt-1">Comienza creando el primer usuario del sistema.</p>
                </div>
            @endif
        </div>

        @if($rejectedUsers->count() > 0 || $blockedEmails->count() > 0)
            <div class="mt-10 space-y-6">
                @if($rejectedUsers->count() > 0)
                    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 overflow-hidden">
                        <div class="absolute top-0 left-0 w-1 h-full bg-red-400 rounded-l-[2rem]"></div>
                        <div class="px-8 py-5 bg-red-50/40 border-b border-red-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                    Solicitudes Rechazadas / Bajas
                                </h3>
                                <p class="text-sm text-slate-500 mt-0.5">{{ $rejectedUsers->count() }} registro(s) de usuarios dados de baja o rechazados.</p>
                            </div>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-slate-50/50 border-b border-slate-100">
                                        <th class="px-8 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Empleado</th>
                                        <th class="px-8 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Motivo</th>
                                        <th class="px-8 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider">Fecha</th>
                                        <th class="px-8 py-3 text-xs font-bold text-slate-400 uppercase tracking-wider text-right">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($rejectedUsers as $user)
                                        @php
                                            $baja = $empleadosBaja[$user->email] ?? null;
                                        @endphp
                                        <tr class="hover:bg-red-50/30 transition-colors">
                                            <td class="px-8 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-9 h-9 bg-red-100 text-red-600 rounded-full flex items-center justify-center text-xs font-bold border border-red-200 flex-shrink-0">
                                                        {{ strtoupper(substr($baja->nombre ?? $user->name, 0, 1)) }}
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-bold text-slate-800">{{ $baja->nombre ?? $user->name }}</p>
                                                        <p class="text-xs text-slate-400">{{ $user->email }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-8 py-4">
                                                @if($baja && $baja->motivo_baja)
                                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase bg-red-50 text-red-700 border border-red-100">
                                                        {{ $baja->motivo_baja }}
                                                    </span>
                                                    @if($baja->observaciones)
                                                        <p class="text-[11px] text-slate-400 mt-1 italic">{{ Str::limit($baja->observaciones, 50) }}</p>
                                                    @endif
                                                @else
                                                    <span class="text-xs text-slate-400">Confirmado por usuario - baja de empleado</span>
                                                @endif
                                            </td>
                                            <td class="px-8 py-4">
                                                <div class="flex flex-col">
                                                    @if($baja && $baja->fecha_baja)
                                                        <span class="text-xs font-bold text-slate-700">{{ $baja->fecha_baja->format('d/m/Y') }}</span>
                                                    @elseif($user->rejected_at)
                                                        <span class="text-xs font-bold text-slate-700">{{ $user->rejected_at->format('d/m/Y') }}</span>
                                                    @else
                                                        <span class="text-xs text-slate-400">N/A</span>
                                                    @endif
                                                </div>
                                            </td>
                                            <td class="px-8 py-4 text-right">
                                                <div class="flex justify-end gap-2">
                                                    @if($baja)
                                                        <form method="POST" action="{{ route('admin.users.reactivar', $user) }}">
                                                            @csrf
                                                            <button type="submit" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-emerald-600 bg-emerald-50 border border-emerald-200 rounded-xl hover:bg-emerald-100 transition-colors" title="Reactivar empleado" onclick="return confirm('¿Reactivar a {{ addslashes($baja->nombre ?? $user->name) }}? Volverá a aparecer como usuario activo.')">
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                                                Reactivar
                                                            </button>
                                                        </form>
                                                    @endif
                                                    <form method="POST" action="{{ route('admin.users.rejections.destroy', $user) }}"
                                                          onsubmit="return confirm('¿Eliminar permanentemente a {{ addslashes($baja->nombre ?? $user->name) }}? Esta acción no se puede deshacer.');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="inline-flex items-center justify-center w-8 h-8 rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-red-600 hover:border-red-200 hover:shadow-sm transition-all" title="Eliminar permanentemente">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                                        </button>
                                                    </form>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif

                @if($blockedEmails->count() > 0)
                    <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 overflow-hidden">
                        <div class="px-8 py-5 bg-slate-50/80 border-b border-slate-200 flex items-center justify-between">
                            <div>
                                <h3 class="text-lg font-bold text-slate-900 flex items-center gap-2">
                                    <span class="w-2 h-2 rounded-full bg-slate-500"></span>
                                    Correos Bloqueados
                                </h3>
                                <p class="text-sm text-slate-500 mt-0.5">{{ $blockedEmails->count() }} correo(s) con acceso restringido.</p>
                            </div>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left border-collapse">
                                <tbody class="divide-y divide-slate-100">
                                    @foreach($blockedEmails as $blocked)
                                        <tr class="hover:bg-slate-50/80 transition-colors">
                                            <td class="px-8 py-4">
                                                <div class="flex items-center gap-3">
                                                    <div class="w-9 h-9 bg-slate-100 text-slate-500 rounded-full flex items-center justify-center flex-shrink-0">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                                    </div>
                                                    <div>
                                                        <p class="text-sm font-bold text-slate-700">{{ $blocked->email }}</p>
                                                        <p class="text-xs text-slate-400">{{ $blocked->reason ?? 'Sin motivo especificado' }}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="px-8 py-4 text-right">
                                                <form method="POST" action="{{ route('admin.blocked-emails.destroy', $blocked) }}">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="inline-flex items-center px-3 py-1.5 text-xs font-bold text-indigo-600 bg-indigo-50 border border-indigo-100 rounded-lg hover:bg-indigo-100 transition-colors">
                                                        Desbloquear
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        @endif

    </div>
</div>

{{-- MODAL DAR DE BAJA --}}
<div id="modalBaja" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" onclick="cerrarModalBaja()"></div>
        <div class="relative bg-white rounded-2xl shadow-xl max-w-md w-full z-10">
            <form id="formBaja" method="POST">
                @csrf
                <div class="px-6 pt-6 pb-4">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="p-2 bg-amber-100 rounded-full">
                            <svg class="w-6 h-6 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-slate-900">Dar de Baja</h3>
                            <p class="text-sm text-slate-500">Usuario: <span id="bajaUserName" class="font-bold text-slate-700"></span></p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Motivo de Baja *</label>
                            <select name="motivo_baja" required class="w-full rounded-xl border-slate-300 text-sm focus:ring-amber-500 focus:border-amber-500">
                                <option value="">-- Seleccionar motivo --</option>
                                <option value="Renuncia voluntaria">Renuncia voluntaria</option>
                                <option value="Despido">Despido</option>
                                <option value="Fin de contrato">Fin de contrato</option>
                                <option value="Fin de prácticas">Fin de prácticas</option>
                                <option value="Mutuo acuerdo">Mutuo acuerdo</option>
                                <option value="Otro">Otro</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-sm font-bold text-slate-700 mb-1">Observaciones</label>
                            <textarea name="observaciones" rows="3" class="w-full rounded-xl border-slate-300 text-sm focus:ring-amber-500 focus:border-amber-500" placeholder="Detalles adicionales (opcional)..."></textarea>
                        </div>
                    </div>
                </div>
                <div class="bg-slate-50 px-6 py-4 rounded-b-2xl flex justify-end gap-3">
                    <button type="button" onclick="cerrarModalBaja()" class="px-4 py-2 text-sm font-medium text-slate-700 bg-white border border-slate-300 rounded-xl hover:bg-slate-50 transition">Cancelar</button>
                    <button type="submit" class="px-4 py-2 text-sm font-bold text-white bg-amber-600 rounded-xl hover:bg-amber-700 transition shadow-sm">Confirmar Baja</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // === Scroll Preservation ===
    (function() {
        const KEY = 'users_scroll';
        const saved = sessionStorage.getItem(KEY);
        if (saved) {
            window.scrollTo(0, parseInt(saved));
            sessionStorage.removeItem(KEY);
        }
        document.querySelectorAll('form').forEach(function(form) {
            form.addEventListener('submit', function() {
                sessionStorage.setItem(KEY, window.scrollY);
            });
        });
    })();

    function abrirModalBaja(userId, userName) {
        document.getElementById('formBaja').action = `/admin/users/${userId}/baja`;
        document.getElementById('bajaUserName').textContent = userName;
        document.getElementById('modalBaja').classList.remove('hidden');
    }
    function cerrarModalBaja() {
        document.getElementById('modalBaja').classList.add('hidden');
    }
</script>
@endsection