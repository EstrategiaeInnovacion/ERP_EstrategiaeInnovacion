@extends('layouts.erp')

@section('title', $proyecto->nombre)

@section('content')
@php
    $esRh = $esRh ?? false;
@endphp

<div class="min-h-screen bg-slate-50/50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start gap-4 mb-6">
            <div class="flex items-center gap-4">
                <a href="{{ route('proyectos.index') }}" class="p-2 text-slate-400 hover:text-slate-600 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">{{ $proyecto->nombre }}</h1>
                    @if($proyecto->descripcion)
                        <p class="text-sm text-slate-500 mt-1">{{ $proyecto->descripcion }}</p>
                    @endif
                </div>
            </div>
            @if($esRh)
            <div class="flex gap-3">
                @if(!$proyecto->finalizado)
                <button onclick="document.getElementById('finalizarModal').classList.remove('hidden')" class="px-4 py-2 rounded-lg text-sm font-bold bg-emerald-600 text-white hover:bg-emerald-700 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    Finalizar Proyecto
                </button>
                @else
                <a href="{{ route('proyectos.reporte', $proyecto) }}" class="px-4 py-2 rounded-lg text-sm font-bold bg-indigo-600 text-white hover:bg-indigo-700 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    Ver Reporte
                </a>
                @endif
                <button onclick="document.getElementById('editModal').classList.remove('hidden')" class="px-4 py-2 rounded-lg text-sm font-medium border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    Editar
                </button>
                <form action="{{ route('proyectos.destroy', $proyecto) }}" method="POST" onsubmit="return confirm('¿Archivar este proyecto?')">
                    @csrf @method('DELETE')
                    <button type="submit" class="px-4 py-2 rounded-lg text-sm font-medium border border-red-200 bg-red-50 text-red-600 hover:bg-red-100 transition flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
                        Archivar
                    </button>
                </form>
            </div>
            @endif
        </div>

        {{-- Información del Proyecto --}}
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Información</h3>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-500">Recurrencia</span>
                        <span class="text-sm font-medium text-slate-800 capitalize">{{ $proyecto->recurrencia }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-500">Fecha inicio</span>
                        <span class="text-sm font-medium text-slate-800">{{ \Carbon\Carbon::parse($proyecto->fecha_inicio)->format('d/m/Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-500">Fecha fin</span>
                        <span class="text-sm font-medium text-slate-800">{{ \Carbon\Carbon::parse($proyecto->fecha_fin)->format('d/m/Y') }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-slate-500">Próxima junta</span>
                        <span class="text-sm font-medium text-indigo-600">{{ $siguienteJunta->format('d/m/Y') }}</span>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Actividades</h3>
                <div class="grid grid-cols-2 gap-4">
                    <div class="text-center p-3 bg-slate-50 rounded-lg">
                        <div class="text-2xl font-bold text-slate-800">{{ $proyecto->actividades()->count() }}</div>
                        <div class="text-xs text-slate-500">Total</div>
                    </div>
                    <div class="text-center p-3 bg-emerald-50 rounded-lg">
                        <div class="text-2xl font-bold text-emerald-600">{{ $proyecto->actividades()->where('estatus', 'Completado')->count() }}</div>
                        <div class="text-xs text-emerald-600">Completadas</div>
                    </div>
                    <div class="text-center p-3 bg-amber-50 rounded-lg">
                        <div class="text-2xl font-bold text-amber-600">{{ $proyecto->actividades()->whereIn('estatus', ['En proceso', 'Planeado'])->count() }}</div>
                        <div class="text-xs text-amber-600">En proceso</div>
                    </div>
                    <div class="text-center p-3 bg-blue-50 rounded-lg">
                        <div class="text-2xl font-bold text-blue-600">{{ $proyecto->usuarios()->count() }}</div>
                        <div class="text-xs text-blue-600">Usuarios</div>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider mb-4">Notas</h3>
                <p class="text-sm text-slate-600">{{ $proyecto->notas ?: 'Sin notas' }}</p>
                
                @if($proyecto->creador)
                <div class="mt-4 pt-4 border-t border-slate-100">
                    <span class="text-xs text-slate-400">Creado por</span>
                    <p class="text-sm font-medium text-slate-800">{{ $proyecto->creador->name }}</p>
                </div>
                @endif
            </div>
        </div>

        {{-- Lista de Usuarios Asignados --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 mb-8">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Usuarios Asignados</h3>
                @if($esRh)
                <button onclick="document.getElementById('usersModal').classList.remove('hidden')" class="text-xs text-indigo-600 hover:underline font-medium">+ Agregar</button>
                @endif
            </div>
            @if($proyecto->usuarios->count() > 0)
                <div class="flex flex-wrap gap-2">
                    @foreach($proyecto->usuarios as $usu)
                        <div class="flex items-center gap-2 px-3 py-2 bg-slate-50 rounded-lg border border-slate-200">
                            <div class="w-8 h-8 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-sm font-bold">
                                {{ substr($usu->name, 0, 2) }}
                            </div>
                            <span class="text-sm font-medium text-slate-700">{{ $usu->name }}</span>
                            @if($esRh)
                            <form action="{{ route('proyectos.quitarUsuario', [$proyecto, $usu->id]) }}" method="POST" class="ml-1">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-slate-400 hover:text-red-500">
                                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                </button>
                            </form>
                            @endif
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-slate-400">No hay usuarios asignados</p>
            @endif
        </div>

        {{-- Actividades del Proyecto --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider">Actividades</h3>
                <a href="{{ route('proyectos.actividades', $proyecto) }}" class="text-xs text-indigo-600 hover:underline font-medium">Ver todas las actividades →</a>
            </div>
            
            @php
                $actividades = $proyecto->actividades()->orderBy('fecha_compromiso')->limit(10)->get();
            @endphp
            
            @if($actividades->count() > 0)
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-slate-100">
                                <th class="text-left py-3 text-xs font-bold text-slate-400 uppercase">Actividad</th>
                                <th class="text-left py-3 text-xs font-bold text-slate-400 uppercase">Responsable</th>
                                <th class="text-left py-3 text-xs font-bold text-slate-400 uppercase">Estatus</th>
                                <th class="text-left py-3 text-xs font-bold text-slate-400 uppercase">Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($actividades as $act)
                            <tr class="border-b border-slate-50 hover:bg-slate-50">
                                <td class="py-3 font-medium text-slate-800">{{ $act->nombre_actividad }}</td>
                                <td class="py-3 text-slate-600">{{ $act->user->name }}</td>
                                <td class="py-3">
                                    @php
                                        $colors = ['Completado' => 'bg-emerald-100 text-emerald-700', 'En proceso' => 'bg-blue-100 text-blue-700', 'Planeado' => 'bg-slate-100 text-slate-600', 'Retardo' => 'bg-red-100 text-red-700', 'Por Aprobar' => 'bg-amber-100 text-amber-700'];
                                    @endphp
                                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $colors[$act->estatus] ?? 'bg-slate-100' }}">{{ $act->estatus }}</span>
                                </td>
                                <td class="py-3 text-slate-600">{{ \Carbon\Carbon::parse($act->fecha_compromiso)->format('d/m/Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-sm text-slate-400">No hay actividades en este proyecto</p>
            @endif
        </div>

    </div>
</div>

{{-- Modal Editar Proyecto --}}
<div id="editModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm" onclick="document.getElementById('editModal').classList.add('hidden')"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:w-full sm:max-w-lg">
                <form action="{{ route('proyectos.update', $proyecto) }}" method="POST">
                    @csrf @method('PUT')
                    <div class="bg-white px-6 py-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-bold text-slate-800">Editar Proyecto</h3>
                            <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Nombre *</label>
                                <input type="text" name="nombre" value="{{ $proyecto->nombre }}" class="w-full rounded-lg border-slate-300 text-sm py-2.5" required>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Descripción</label>
                                <textarea name="descripcion" rows="2" class="w-full rounded-lg border-slate-300 text-sm py-2.5">{{ $proyecto->descripcion }}</textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Fecha Inicio *</label>
                                    <input type="date" name="fecha_inicio" value="{{ $proyecto->fecha_inicio }}" class="w-full rounded-lg border-slate-300 text-sm py-2.5" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Fecha Fin *</label>
                                    <input type="date" name="fecha_fin" value="{{ $proyecto->fecha_fin }}" class="w-full rounded-lg border-slate-300 text-sm py-2.5" required>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Recurrencia *</label>
                                <select name="recurrencia" class="w-full rounded-lg border-slate-300 text-sm py-2.5">
                                    <option value="mensual" {{ $proyecto->recurrencia == 'mensual' ? 'selected' : '' }}>Mensual</option>
                                    <option value="quincenal" {{ $proyecto->recurrencia == 'quincenal' ? 'selected' : '' }}>Quincenal</option>
                                    <option value="semanal" {{ $proyecto->recurrencia == 'semanal' ? 'selected' : '' }}>Semanal</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Notas</label>
                                <textarea name="notas" rows="2" class="w-full rounded-lg border-slate-300 text-sm py-2.5">{{ $proyecto->notas }}</textarea>
                            </div>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse gap-3 border-t border-slate-200">
                        <button type="submit" class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-indigo-700 transition">Guardar</button>
                        <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="px-5 py-2.5 text-slate-600 font-medium hover:bg-slate-100 rounded-xl transition">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Modal Agregar Usuarios --}}
<div id="usersModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm" onclick="document.getElementById('usersModal').classList.add('hidden')"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:w-full sm:max-w-md">
                <form action="{{ route('proyectos.asignarUsuarios', $proyecto) }}" method="POST">
                    @csrf
                    <div class="bg-white px-6 py-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-bold text-slate-800">Agregar Usuarios</h3>
                            <button type="button" onclick="document.getElementById('usersModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        
                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Seleccionar usuarios</label>
                            <select name="usuarios[]" multiple class="w-full rounded-lg border-slate-300 text-sm py-2.5" size="6">
                                @php
                                    $usuariosAsignados = $proyecto->usuarios()->pluck('users.id')->toArray();
                                    $usuarios = \App\Models\User::whereHas('empleado', fn($q) => $q->where('es_activo', true))->orderBy('name')->get();
                                @endphp
                                @foreach($usuarios as $u)
                                    <option value="{{ $u->id }}" {{ in_array($u->id, $usuariosAsignados) ? 'selected' : '' }}>{{ $u->name }}</option>
                                @endforeach
                            </select>
                            <p class="text-xs text-slate-400 mt-1">Mantén presionado Ctrl/Cmd para seleccionar varios</p>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse gap-3 border-t border-slate-200">
                        <button type="submit" class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-indigo-700 transition">Agregar</button>
                        <button type="button" onclick="document.getElementById('usersModal').classList.add('hidden')" class="px-5 py-2.5 text-slate-600 font-medium hover:bg-slate-100 rounded-xl transition">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Modal Finalizar Proyecto --}}
<div id="finalizarModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm" onclick="document.getElementById('finalizarModal').classList.add('hidden')"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:w-full sm:max-w-md">
                <form action="{{ route('proyectos.finalizar', $proyecto) }}" method="POST">
                    @csrf
                    <div class="bg-white px-6 py-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-bold text-slate-800">Finalizar Proyecto</h3>
                            <button type="button" onclick="document.getElementById('finalizarModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-4 mb-4">
                            <p class="text-sm text-amber-800">
                                <strong>Nota:</strong> Al finalizar el proyecto se generará un reporte con las métricas de todas las actividades.
                            </p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Fecha Fin Real *</label>
                            <input type="date" name="fecha_fin_real" value="{{ now()->format('Y-m-d') }}" class="w-full rounded-lg border-slate-300 text-sm py-2.5" required>
                            <p class="text-xs text-slate-400 mt-1">Selecciona la fecha en que realmente terminó el proyecto</p>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse gap-3 border-t border-slate-200">
                        <button type="submit" class="bg-emerald-600 text-white px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-emerald-700 transition">Finalizar y Generar Reporte</button>
                        <button type="button" onclick="document.getElementById('finalizarModal').classList.add('hidden')" class="px-5 py-2.5 text-slate-600 font-medium hover:bg-slate-100 rounded-xl transition">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection