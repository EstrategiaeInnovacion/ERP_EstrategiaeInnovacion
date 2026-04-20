@extends('layouts.erp')

@section('title', "Actividades - {$proyecto->nombre}")

@section('content')
@php
    $esRh = $esRhCoordinador ?? $esRh ?? false;
@endphp

<div class="min-h-screen bg-slate-50/50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start gap-4 mb-6">
            <div class="flex items-center gap-4">
                <a href="{{ route('proyectos.show', $proyecto) }}" class="p-2 text-slate-400 hover:text-slate-600 transition">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </a>
                <div>
                    <h1 class="text-2xl font-bold text-slate-800">Actividades del Proyecto</h1>
                    <p class="text-sm text-slate-500 mt-1">{{ $proyecto->nombre }}</p>
                </div>
            </div>
            <button onclick="document.getElementById('createModal').classList.remove('hidden')" 
                    class="px-4 py-2 rounded-lg text-sm font-bold bg-indigo-600 text-white hover:bg-indigo-700 transition shadow-md flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                Nueva Actividad
            </button>
        </div>

        {{-- Mensajes --}}
        @if(session('success'))
            <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- KPIs --}}
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
            <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 text-center">
                <div class="text-2xl font-bold text-slate-800">{{ $kpis['total'] }}</div>
                <div class="text-xs text-slate-500 uppercase">Total</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 text-center">
                <div class="text-2xl font-bold text-emerald-600">{{ $kpis['completadas'] }}</div>
                <div class="text-xs text-emerald-600 uppercase">Completadas</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 text-center">
                <div class="text-2xl font-bold text-blue-600">{{ $kpis['enProceso'] }}</div>
                <div class="text-xs text-blue-600 uppercase">En Proceso</div>
            </div>
            <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 text-center">
                <div class="text-2xl font-bold text-amber-600">{{ $kpis['pendientes'] }}</div>
                <div class="text-xs text-amber-600 uppercase">Pendientes</div>
            </div>
        </div>

        {{-- Tabla de Actividades --}}
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 overflow-hidden">
            @if($actividades->isEmpty())
                <div class="p-12 text-center">
                    <svg class="w-16 h-16 text-slate-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                    <p class="text-slate-500 font-medium">No hay actividades en este proyecto</p>
                    <button onclick="document.getElementById('createModal').classList.remove('hidden')" class="mt-4 text-indigo-600 font-medium hover:underline">Crear primera actividad</button>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50 border-b border-slate-200">
                            <tr>
                                <th class="text-left py-3 px-4 text-xs font-bold text-slate-500 uppercase">Actividad</th>
                                <th class="text-left py-3 px-4 text-xs font-bold text-slate-500 uppercase">Responsable</th>
                                <th class="text-left py-3 px-4 text-xs font-bold text-slate-500 uppercase">Área</th>
                                <th class="text-left py-3 px-4 text-xs font-bold text-slate-500 uppercase">Fecha</th>
                                <th class="text-left py-3 px-4 text-xs font-bold text-slate-500 uppercase">Estatus</th>
                                <th class="text-center py-3 px-4 text-xs font-bold text-slate-500 uppercase">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($actividades as $act)
                            <tr class="hover:bg-slate-50 transition">
                                <td class="py-3 px-4">
                                    <div class="font-medium text-slate-800">{{ $act->nombre_actividad }}</div>
                                    @if($act->cliente)
                                        <div class="text-xs text-indigo-600 mt-0.5">{{ $act->cliente }}</div>
                                    @endif
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-6 h-6 rounded-full bg-indigo-100 text-indigo-600 flex items-center justify-center text-xs font-bold">
                                            {{ substr($act->user->name, 0, 2) }}
                                        </div>
                                        <span class="text-sm text-slate-600">{{ $act->user->name }}</span>
                                    </div>
                                </td>
                                <td class="py-3 px-4 text-sm text-slate-600">{{ $act->area }}</td>
                                <td class="py-3 px-4 text-sm text-slate-600">
                                    {{ \Carbon\Carbon::parse($act->fecha_compromiso)->format('d/m/Y') }}
                                </td>
                                <td class="py-3 px-4">
                                    @php
                                        $estilos = [
                                            'Completado' => 'bg-emerald-100 text-emerald-700',
                                            'En proceso' => 'bg-blue-100 text-blue-700',
                                            'Planeado' => 'bg-slate-100 text-slate-600',
                                            'Por Aprobar' => 'bg-amber-100 text-amber-700',
                                            'Por Validar' => 'bg-purple-100 text-purple-700',
                                            'Retardo' => 'bg-red-100 text-red-700',
                                            'Rechazado' => 'bg-red-100 text-red-700',
                                        ];
                                    @endphp
                                    <span class="px-2 py-1 rounded-full text-xs font-medium {{ $estilos[$act->estatus] ?? 'bg-slate-100' }}">
                                        {{ $act->estatus }}
                                    </span>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex items-center justify-center gap-2">
                                        <button onclick="openEditModal({{ $act->id }})" class="p-1.5 text-slate-400 hover:text-indigo-600 transition" title="Editar">
                                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        @if($esRh || $proyecto->usuario_id === auth()->id())
                                        <form action="{{ route('proyectos.actividades.destroy', [$proyecto, $act]) }}" method="POST" onsubmit="return confirm('¿Eliminar esta actividad?')">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="p-1.5 text-slate-400 hover:text-red-600 transition" title="Eliminar">
                                                <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </form>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>

    </div>
</div>

{{-- Modal Crear Actividad --}}
<div id="createModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm" onclick="document.getElementById('createModal').classList.add('hidden')"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:w-full sm:max-w-lg">
                <form action="{{ route('proyectos.actividades.store', $proyecto) }}" method="POST">
                    @csrf
                    <div class="bg-white px-6 py-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-bold text-slate-800">Nueva Actividad</h3>
                            <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Descripción *</label>
                                <input type="text" name="nombre_actividad" class="w-full rounded-lg border-slate-300 text-sm py-2.5" placeholder="¿Qué se debe hacer?" required>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Asignar a</label>
                                    <select name="asignado_a" class="w-full rounded-lg border-slate-300 text-sm py-2.5">
                                        <option value="{{ auth()->id() }}">Yo mismo</option>
                                        @foreach($usuariosAsignables as $u)
                                            @if($u->id !== auth()->id())
                                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Prioridad</label>
                                    <select name="prioridad" class="w-full rounded-lg border-slate-300 text-sm py-2.5">
                                        <option value="Media">Media</option>
                                        <option value="Alta">Alta</option>
                                        <option value="Baja">Baja</option>
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Área *</label>
                                    <select name="area" class="w-full rounded-lg border-slate-300 text-sm py-2.5">
                                        @foreach($areas as $area)
                                            <option value="{{ $area }}">{{ $area }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Fecha Compromiso *</label>
                                    <input type="date" name="fecha_compromiso" value="{{ now()->addWeek()->format('Y-m-d') }}" class="w-full rounded-lg border-slate-300 text-sm py-2.5" required>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Cliente (Opcional)</label>
                                <input type="text" name="cliente" class="w-full rounded-lg border-slate-300 text-sm py-2.5" placeholder="Nombre del cliente">
                            </div>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse gap-3 border-t border-slate-200">
                        <button type="submit" class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-indigo-700 transition">Crear Actividad</button>
                        <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" class="px-5 py-2.5 text-slate-600 font-medium hover:bg-slate-100 rounded-xl transition">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Modal Editar Actividad --}}
<div id="editModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm" onclick="document.getElementById('editModal').classList.add('hidden')"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:w-full sm:max-w-lg">
                <form id="editForm" method="POST">
                    @csrf @method('PUT')
                    <div class="bg-white px-6 py-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-bold text-slate-800">Editar Actividad</h3>
                            <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div id="editFormContent">
                            {{-- Contenido dinámico --}}
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

<script>
function openEditModal(actividadId) {
    fetch('/proyectos/{{ $proyecto->id }}/actividades/' + actividadId + '/edit')
        .then(response => response.json())
        .then(data => {
            document.getElementById('editForm').action = '/proyectos/{{ $proyecto->id }}/actividades/' + actividadId;
            document.getElementById('editFormContent').innerHTML = data.form;
            document.getElementById('editModal').classList.remove('hidden');
        });
}
</script>
@endsection