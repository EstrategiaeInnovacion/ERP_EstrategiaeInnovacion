@extends('layouts.erp')

@section('title', 'Proyectos')

@section('content')
@php
    $esRh = $esRh ?? false;
    $esCoordinador = $esCoordinador ?? false;
@endphp

<div class="min-h-screen bg-slate-50/50 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        {{-- Header --}}
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4 mb-8">
            <div>
                <h1 class="text-2xl font-bold text-slate-800">Proyectos</h1>
                <p class="text-sm text-slate-500 mt-1">Gestión de proyectos y asignación de actividades</p>
            </div>
            <div class="flex gap-3">
                <a href="{{ route('proyectos.index', ['archivado' => request('archivado') == '1' ? '0' : '1']) }}" 
                   class="px-4 py-2 rounded-lg text-sm font-medium border border-slate-300 bg-white text-slate-700 hover:bg-slate-50 transition">
                    {{ request('archivado') == '1' ? 'Ver activos' : 'Ver archivados' }}
                </a>
                @if($esRh)
                <button onclick="document.getElementById('createModal').classList.remove('hidden')" 
                        class="px-4 py-2 rounded-lg text-sm font-bold bg-indigo-600 text-white hover:bg-indigo-700 transition shadow-md flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    Nuevo Proyecto
                </button>
                @endif
            </div>
        </div>

        {{-- Mensajes --}}
        @if(session('success'))
            <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg flex items-center gap-2">
                <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                {{ session('success') }}
            </div>
        @endif

        {{-- Filtros --}}
        @if(request('archivado') != '1')
        <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-4 mb-6">
            <div class="flex flex-wrap items-center gap-4">
                <div class="text-sm font-medium text-slate-600">Total:</div>
                <div class="px-3 py-1 bg-indigo-100 text-indigo-700 rounded-full text-sm font-bold">{{ $proyectos->count() }}</div>
                <div class="text-sm font-medium text-slate-600 ml-4">Con actividades pendientes:</div>
                <div class="px-3 py-1 bg-amber-100 text-amber-700 rounded-full text-sm font-bold">{{ $proyectosConActividades->where('actividades_pendientes', '>', 0)->count() }}</div>
            </div>
        </div>
        @endif

        {{-- Lista de Proyectos --}}
        @if($proyectos->isEmpty())
            <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-12 text-center">
                <svg class="w-16 h-16 text-slate-300 mx-auto mb-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-6l-2-2H5a2 2 0 00-2 2z"/></svg>
                <p class="text-slate-500 font-medium">No hay proyectos {{ request('archivado') == '1' ? 'archivados' : 'activos' }}</p>
                @if($esRh && request('archivado') != '1')
                    <button onclick="document.getElementById('createModal').classList.remove('hidden')" class="mt-4 text-indigo-600 font-medium hover:underline">Crear el primer proyecto</button>
                @endif
            </div>
        @else
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($proyectos as $proyecto)
                    <div class="bg-white rounded-xl shadow-sm border border-slate-100 p-6 hover:shadow-md transition">
                        <div class="flex justify-between items-start mb-4">
                            <h3 class="text-lg font-bold text-slate-800 truncate">{{ $proyecto->nombre }}</h3>
                            @if(request('archivado') == '1')
                                <div class="relative" x-data="{ open: false }">
                                    <button @click="open = !open" @click.outside="open = false" class="px-2 py-1 bg-slate-100 text-slate-500 text-xs rounded-full font-medium hover:bg-slate-200 transition">
                                        Archivado ▾
                                    </button>
                                    <div x-show="open" class="absolute right-0 mt-1 w-40 bg-white rounded-lg shadow-lg border border-slate-200 z-10" style="display: none;">
                                        <form action="{{ route('proyectos.restore', $proyecto->id) }}" method="POST" class="p-1">
                                            @csrf
                                            <button type="submit" class="w-full text-left px-3 py-2 text-sm text-slate-700 hover:bg-emerald-50 rounded">
                                                Restaurar
                                            </button>
                                        </form>
                                        <form action="{{ route('proyectos.forceDelete', $proyecto->id) }}" method="POST" class="p-1" onsubmit="return confirm('¿Eliminar permanentemente este proyecto y todas sus actividades?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="w-full text-left px-3 py-2 text-sm text-red-600 hover:bg-red-50 rounded">
                                                Eliminar
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            @endif
                        </div>
                        
                        @if($proyecto->descripcion)
                            <p class="text-sm text-slate-500 mb-4 line-clamp-2">{{ $proyecto->descripcion }}</p>
                        @endif

                        <div class="flex flex-wrap gap-2 mb-4">
                            <span class="px-2 py-1 bg-blue-50 text-blue-600 text-xs rounded font-medium">
                                {{ ucfirst($proyecto->recurrencia) }}
                            </span>
                            <span class="px-2 py-1 bg-emerald-50 text-emerald-600 text-xs rounded font-medium">
                                {{ $proyecto->usuarios()->count() }} usuarios
                            </span>
                            <span class="px-2 py-1 bg-purple-50 text-purple-600 text-xs rounded font-medium">
                                {{ $proyecto->actividades()->count() }} actividades
                            </span>
                        </div>

                        <div class="text-xs text-slate-400 mb-4">
                            <div>Inicio: {{ \Carbon\Carbon::parse($proyecto->fecha_inicio)->format('d/m/Y') }}</div>
                            <div>Fin: {{ \Carbon\Carbon::parse($proyecto->fecha_fin)->format('d/m/Y') }}</div>
                        </div>

                        <div class="flex gap-2 pt-4 border-t border-slate-100">
                            <a href="{{ route('proyectos.show', $proyecto) }}" class="flex-1 text-center px-3 py-2 bg-indigo-50 text-indigo-600 rounded-lg text-sm font-medium hover:bg-indigo-100 transition">
                                Ver detalles
                            </a>
                            @if($esRh)
                                <button onclick="openEditModal({{ $proyecto->id }})" class="px-3 py-2 text-slate-400 hover:text-slate-600 transition">
                                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                </button>
                            @endif
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

    </div>
</div>

{{-- Modal Crear Proyecto --}}
<div id="createModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm" onclick="document.getElementById('createModal').classList.add('hidden')"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:w-full sm:max-w-lg">
                <form action="{{ route('proyectos.store') }}" method="POST">
                    @csrf
                    <div class="bg-white px-6 py-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-bold text-slate-800">Nuevo Proyecto</h3>
                            <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        
                        <div class="space-y-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Nombre *</label>
                                <input type="text" name="nombre" class="w-full rounded-lg border-slate-300 text-sm focus:ring-indigo-500 py-2.5" required>
                            </div>
                            
                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Descripción</label>
                                <textarea name="descripcion" rows="2" class="w-full rounded-lg border-slate-300 text-sm focus:ring-indigo-500 py-2.5"></textarea>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Fecha Inicio *</label>
                                    <input type="date" name="fecha_inicio" class="w-full rounded-lg border-slate-300 text-sm py-2.5" required>
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Fecha Fin *</label>
                                    <input type="date" name="fecha_fin" class="w-full rounded-lg border-slate-300 text-sm py-2.5" required>
                                </div>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Recurrencia de Juntas *</label>
                                <select name="recurrencia" class="w-full rounded-lg border-slate-300 text-sm py-2.5" required>
                                    <option value="mensual">Mensual</option>
                                    <option value="quincenal">Quincenal</option>
                                    <option value="semanal">Semanal</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Notas</label>
                                <textarea name="notas" rows="2" class="w-full rounded-lg border-slate-300 text-sm py-2.5"></textarea>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-slate-500 uppercase mb-1.5">Asignar Usuarios</label>
                                @php
                                    $usuarios = \App\Models\User::whereHas('empleado', fn($q) => $q->where('es_activo', true))->orderBy('name')->get();
                                @endphp
                                @if($usuarios->count() > 0)
                                    <div class="max-h-32 overflow-y-auto border border-slate-200 rounded-lg p-2 space-y-1 bg-slate-50">
                                        @foreach($usuarios as $u)
                                            <label class="flex items-center gap-2 p-1 hover:bg-slate-100 rounded cursor-pointer">
                                                <input type="checkbox" name="usuarios[]" value="{{ $u->id }}" class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500">
                                                <span class="text-sm text-slate-700">{{ $u->name }}</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-xs text-slate-400">No hay usuarios disponibles</p>
                                @endif
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-cyan-600 uppercase mb-1.5">Asignar Responsables de TI</label>
                                @php
                                    $usuariosTi = \App\Models\User::ti()->whereHas('empleado', fn($q) => $q->where('es_activo', true))->orderBy('name')->get();
                                @endphp
                                @if($usuariosTi->count() > 0)
                                    <div class="max-h-32 overflow-y-auto border border-cyan-200 rounded-lg p-2 space-y-1 bg-cyan-50">
                                        @foreach($usuariosTi as $u)
                                            <label class="flex items-center gap-2 p-1 hover:bg-cyan-100 rounded cursor-pointer">
                                                <input type="checkbox" name="responsables_ti[]" value="{{ $u->id }}" class="rounded border-cyan-300 text-cyan-600 focus:ring-cyan-500">
                                                <span class="text-sm text-slate-700">{{ $u->name }}</span>
                                                <span class="text-xs text-cyan-500">({{ $u->empleado->posicion ?? 'TI' }})</span>
                                            </label>
                                        @endforeach
                                    </div>
                                @else
                                    <p class="text-xs text-slate-400">No hay usuarios de TI disponibles</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse gap-3 border-t border-slate-200">
                        <button type="submit" class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-indigo-700 transition">Crear Proyecto</button>
                        <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" class="px-5 py-2.5 text-slate-600 font-medium hover:bg-slate-100 rounded-xl transition">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Modal Editar Proyecto (dinámico) --}}
<div id="editModal" class="fixed inset-0 z-50 hidden" role="dialog" aria-modal="true">
    <div class="fixed inset-0 bg-slate-900/80 backdrop-blur-sm" onclick="document.getElementById('editModal').classList.add('hidden')"></div>
    <div class="fixed inset-0 z-10 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:w-full sm:max-w-lg">
                <form id="editForm" method="POST">
                    @csrf
                    @method('PUT')
                    <div class="bg-white px-6 py-6">
                        <div class="flex justify-between items-center mb-6">
                            <h3 class="text-xl font-bold text-slate-800">Editar Proyecto</h3>
                            <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                                <svg class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div id="editFormContent" class="space-y-4">
                            {{-- Contenido cargado dinámicamente --}}
                        </div>
                    </div>
                    <div class="bg-slate-50 px-6 py-4 flex flex-row-reverse gap-3 border-t border-slate-200">
                        <button type="submit" class="bg-indigo-600 text-white px-5 py-2.5 rounded-xl text-sm font-bold hover:bg-indigo-700 transition">Guardar Cambios</button>
                        <button type="button" onclick="document.getElementById('editModal').classList.add('hidden')" class="px-5 py-2.5 text-slate-600 font-medium hover:bg-slate-100 rounded-xl transition">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function openEditModal(id) {
    fetch('/proyectos/' + id + '/edit')
        .then(response => response.json())
        .then(data => {
            document.getElementById('editForm').action = '/proyectos/' + id;
            document.getElementById('editFormContent').innerHTML = data.form;
            document.getElementById('editModal').classList.remove('hidden');
        });
}
</script>
@endsection