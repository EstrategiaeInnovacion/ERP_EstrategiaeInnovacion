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

        <div class="grid md:grid-cols-2 gap-6 mt-8">
            @if($rejectedUsers->count() > 0)
                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 bg-red-50/50 border-b border-red-100">
                        <h3 class="font-bold text-red-900 text-sm uppercase tracking-wide">Solicitudes Rechazadas</h3>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @foreach($rejectedUsers as $user)
                            <div class="px-6 py-4 flex justify-between items-center hover:bg-slate-50 transition-colors">
                                <div>
                                    <p class="text-sm font-bold text-slate-700">{{ $user->name }}</p>
                                    <p class="text-xs text-slate-400">{{ $user->email }}</p>
                                </div>
                                <form method="POST" action="{{ route('admin.users.rejections.destroy', $user) }}"
                                      onsubmit="return confirm('¿Eliminar permanentemente?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-red-500 hover:text-red-700 font-bold hover:underline">Eliminar</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif

            @if($blockedEmails->count() > 0)
                <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200 overflow-hidden">
                    <div class="px-6 py-4 bg-slate-50/80 border-b border-slate-200">
                        <h3 class="font-bold text-slate-700 text-sm uppercase tracking-wide">Correos Bloqueados</h3>
                    </div>
                    <div class="divide-y divide-slate-100">
                        @foreach($blockedEmails as $blocked)
                            <div class="px-6 py-4 flex justify-between items-center hover:bg-slate-50 transition-colors">
                                <div>
                                    <p class="text-sm font-bold text-slate-700">{{ $blocked->email }}</p>
                                    <p class="text-xs text-slate-400">{{ $blocked->reason ?? 'Sin motivo' }}</p>
                                </div>
                                <form method="POST" action="{{ route('admin.blocked-emails.destroy', $blocked) }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-xs text-indigo-500 hover:text-indigo-700 font-bold hover:underline">Desbloquear</button>
                                </form>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

    </div>
</div>
@endsection