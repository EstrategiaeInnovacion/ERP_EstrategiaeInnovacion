@extends('layouts.erp')

@section('title', 'Aprobación de Vacaciones')

@section('content')
<div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-slate-900">Aprobación de Vacaciones</h1>
        <p class="text-slate-600 mt-2">Gestiona las solicitudes de vacaciones pendientes de revisión.</p>
    </div>

    @if(session('success'))
        <div class="mb-6 p-4 bg-green-50 border-l-4 border-green-500 text-green-700 rounded-r shadow-sm">
            {{ session('success') }}
        </div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-50 border-l-4 border-red-500 text-red-700 rounded-r shadow-sm">
            {{ session('error') }}
        </div>
    @endif

    <div x-data="{ activeTab: 'vacaciones' }" class="space-y-8">
        
        {{-- TABS DE NAVEGACIÓN --}}
        <div class="border-b border-slate-200">
            <nav class="-mb-px flex gap-6" aria-label="Tabs">
                <button @click="activeTab = 'vacaciones'" :class="activeTab === 'vacaciones' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    Vacaciones
                </button>
                <button @click="activeTab = 'permisos'" :class="activeTab === 'permisos' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300'" class="whitespace-nowrap py-4 px-1 border-b-2 font-medium text-sm transition-colors">
                    Permisos y Ausencias
                </button>
            </nav>
        </div>

        {{-- CONTENEDOR VACACIONES --}}
        <div x-show="activeTab === 'vacaciones'">
            
        {{-- SECCIÓN SUPERVISOR --}}
        @if($solicitudesSupervisor->count() > 0)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="bg-indigo-50 border-b border-indigo-100 px-6 py-4">
                <h2 class="text-lg font-bold text-indigo-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                    Peticiones de mi equipo (Como Supervisor)
                </h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Colaborador</th>
                            <th class="px-6 py-3 font-semibold">Fechas Solicitadas</th>
                            <th class="px-6 py-3 font-semibold">Días Hábiles</th>
                            <th class="px-6 py-3 font-semibold">Motivo</th>
                            <th class="px-6 py-3 font-semibold text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($solicitudesSupervisor as $solicitud)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-800">{{ $solicitud->empleado->nombre }}</div>
                                    <div class="text-xs text-slate-500">{{ $solicitud->empleado->posicion }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-block px-2 py-1 bg-slate-100 rounded text-slate-700 font-medium text-xs">
                                        {{ $solicitud->fecha_inicio->format('d/m/Y') }} al {{ $solicitud->fecha_fin->format('d/m/Y') }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 font-bold text-indigo-600">{{ $solicitud->dias_solicitados }}</td>
                                <td class="px-6 py-4 text-slate-600 max-w-xs truncate" title="{{ $solicitud->motivo }}">{{ $solicitud->motivo ?? 'Sin observaciones' }}</td>
                                <td class="px-6 py-4 text-right" x-data="{ modalOpen: false, action: '' }">
                                    <div class="flex justify-end gap-2">
                                        <button @click="modalOpen = true; action = 'aprobar'" class="px-3 py-1 bg-green-100 text-green-700 hover:bg-green-200 font-bold rounded-lg text-xs transition">Autorizar</button>
                                        <button @click="modalOpen = true; action = 'rechazar'" class="px-3 py-1 bg-red-100 text-red-700 hover:bg-red-200 font-bold rounded-lg text-xs transition">Rechazar</button>
                                    </div>

                                    {{-- Modal de Comentarios --}}
                                    <div x-show="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm" style="display: none;">
                                        <div @click.away="modalOpen = false" class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl text-left">
                                            <h3 class="text-lg font-bold mb-2 text-slate-900">
                                                <span x-show="action == 'aprobar'">Autorizar Vacaciones</span>
                                                <span x-show="action == 'rechazar'">Rechazar Vacaciones</span>
                                            </h3>
                                            <p class="text-sm text-slate-600 mb-4">Añade un comentario u observación para el colaborador y Recursos Humanos (Opcional si apruebas, obligatorio si rechazas).</p>
                                            
                                            <form :action="action == 'aprobar' ? '{{ route('vacaciones.aprobar', $solicitud->id) }}' : '{{ route('vacaciones.rechazar', $solicitud->id) }}'" method="POST">
                                                @csrf
                                                <textarea name="comentarios" rows="3" class="w-full rounded-lg border-slate-300 text-sm focus:ring-indigo-500 mb-4" placeholder="Comentarios..."></textarea>
                                                <div class="flex justify-end gap-3">
                                                    <button type="button" @click="modalOpen = false" class="px-4 py-2 border border-slate-200 rounded-lg text-slate-600 font-medium hover:bg-slate-50">Cancelar</button>
                                                    <button type="submit" class="px-4 py-2 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 shadow-lg">Confirmar</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- SECCIÓN RECURSOS HUMANOS --}}
        @if(Auth::user()->isRh())
        
        @if($solicitudesRH->count() > 0)
        <div class="bg-white rounded-2xl shadow-sm border border-emerald-200 overflow-hidden mb-8 ring-1 ring-emerald-500/20">
            <div class="bg-emerald-50 border-b border-emerald-100 px-6 py-4">
                <h2 class="text-lg font-bold text-emerald-900 flex items-center gap-2">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Requieren Visto Bueno Final (Recursos Humanos)
                </h2>
                <p class="text-xs text-emerald-600 mt-1">Estas solicitudes ya fueron autorizadas por el supervisor del empleado.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50 border-b border-slate-200 text-slate-600">
                        <tr>
                            <th class="px-6 py-3 font-semibold">Colaborador</th>
                            <th class="px-6 py-3 font-semibold">Fechas Solicitadas</th>
                            <th class="px-6 py-3 font-semibold">Autorizado por</th>
                            <th class="px-6 py-3 font-semibold text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @foreach($solicitudesRH as $solicitud)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-800">{{ $solicitud->empleado->nombre }}</div>
                                    <div class="text-xs text-slate-500">{{ $solicitud->empleado->posicion }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="inline-block px-2 py-1 bg-slate-100 rounded text-slate-700 font-medium text-xs">
                                        {{ $solicitud->fecha_inicio->format('d/m/Y') }} al {{ $solicitud->fecha_fin->format('d/m/Y') }}
                                    </span>
                                    <span class="block text-xs text-emerald-600 font-bold mt-1">{{ $solicitud->dias_solicitados }} días hábiles</span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="text-sm font-medium text-slate-700">{{ $solicitud->supervisor->nombre ?? 'N/A' }}</div>
                                    @if($solicitud->comentarios_supervisor)
                                        <div class="text-xs text-slate-500 italic mt-1">"{{ $solicitud->comentarios_supervisor }}"</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right" x-data="{ modalRhOpen: false, action: '' }">
                                    <div class="flex justify-end gap-2">
                                        <button @click="modalRhOpen = true; action = 'aprobar'" class="px-3 py-1 bg-emerald-100 text-emerald-700 hover:bg-emerald-200 font-bold rounded-lg text-xs transition">Aprobar Final</button>
                                        <button @click="modalRhOpen = true; action = 'rechazar'" class="px-3 py-1 bg-red-100 text-red-700 hover:bg-red-200 font-bold rounded-lg text-xs transition">Rechazar</button>
                                    </div>

                                    {{-- Modal de Comentarios RH --}}
                                    <div x-show="modalRhOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm" style="display: none;">
                                        <div @click.away="modalRhOpen = false" class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl text-left">
                                            <h3 class="text-lg font-bold mb-2 text-slate-900">
                                                <span x-show="action == 'aprobar'">Aprobación Definitiva</span>
                                                <span x-show="action == 'rechazar'">Rechazo de Recursos Humanos</span>
                                            </h3>
                                            <p class="text-sm text-slate-600 mb-4">Añade observaciones finales que verá el colaborador (Opcional si apruebas, obligatorio si rechazas).</p>
                                            
                                            <form :action="action == 'aprobar' ? '{{ route('vacaciones.aprobar', $solicitud->id) }}' : '{{ route('vacaciones.rechazar', $solicitud->id) }}'" method="POST">
                                                @csrf
                                                <textarea name="comentarios" rows="3" class="w-full rounded-lg border-slate-300 text-sm focus:ring-emerald-500 mb-4" placeholder="Comentarios..."></textarea>
                                                <div class="flex justify-end gap-3">
                                                    <button type="button" @click="modalRhOpen = false" class="px-4 py-2 border border-slate-200 rounded-lg text-slate-600 font-medium hover:bg-slate-50">Cancelar</button>
                                                    <button type="submit" class="px-4 py-2 bg-emerald-600 text-white font-bold rounded-lg hover:bg-emerald-700 shadow-lg">Confirmar</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @else
        <div class="bg-emerald-50 border border-emerald-200 border-dashed rounded-2xl p-8 text-center mb-8">
            <svg class="w-12 h-12 mx-auto text-emerald-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <h3 class="text-base font-bold text-emerald-800">Cero pendientes para RH</h3>
            <p class="text-emerald-600 text-sm mt-1">No hay solicitudes esperando tu visto bueno final en este momento.</p>
        </div>
        @endif

        {{-- MONITOREO DE RH: Solicitudes que están atoradas con el supervisor --}}
        @if($solicitudesGlobalesPendientes->count() > 0)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="bg-slate-50 border-b border-slate-100 px-6 py-4 flex justify-between items-center">
                <div>
                    <h2 class="text-sm font-bold text-slate-700 flex items-center gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        Monitoreo: En espera de Supervisor
                    </h2>
                    <p class="text-xs text-slate-500 mt-1">Estas solicitudes ya fueron creadas, pero su supervisor directo aún no las ha autorizado. Llegarán a tu bandeja de Visto Bueno Final cuando el supervisor las apruebe.</p>
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm opacity-75">
                    <thead class="bg-slate-50/50 border-b border-slate-100 text-slate-500">
                        <tr>
                            <th class="px-6 py-2 font-medium text-xs">Colaborador</th>
                            <th class="px-6 py-2 font-medium text-xs">Días</th>
                            <th class="px-6 py-2 font-medium text-xs">Esperando respuesta de...</th>
                            <th class="px-6 py-2 font-medium text-xs">Fecha Solicitud</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($solicitudesGlobalesPendientes as $solicitud)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-3">
                                    <div class="font-medium text-slate-700">{{ $solicitud->empleado->nombre }}</div>
                                </td>
                                <td class="px-6 py-3 text-xs text-slate-600">
                                    {{ $solicitud->dias_solicitados }} días ({{ $solicitud->fecha_inicio->format('d/m') }} - {{ $solicitud->fecha_fin->format('d/m') }})
                                </td>
                                <td class="px-6 py-3">
                                    <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        {{ $solicitud->supervisor->nombre ?? 'Sin supervisor asignado' }}
                                    </span>
                                </td>
                                <td class="px-6 py-3 text-xs text-slate-500">
                                    {{ $solicitud->created_at->diffForHumans() }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        {{-- HISTÓRICO DE APROBADAS POR RH --}}
        @if($historialRH->count() > 0)
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mt-8">
            <div class="bg-slate-50 border-b border-slate-100 px-6 py-4">
                <h2 class="text-sm font-bold text-slate-700 flex items-center gap-2">
                    <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Historial de Vacaciones Aprobadas (Últimas 50)
                </h2>
                <p class="text-xs text-slate-500 mt-1">Registro de las solicitudes que ya completaron todo el flujo y fueron aprobadas por Recursos Humanos.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="bg-slate-50/50 border-b border-slate-100 text-slate-500">
                        <tr>
                            <th class="px-6 py-2 font-medium text-xs">Colaborador</th>
                            <th class="px-6 py-2 font-medium text-xs">Fechas</th>
                            <th class="px-6 py-2 font-medium text-xs">Supervisor</th>
                            <th class="px-6 py-2 font-medium text-xs">Fecha de Aprobación RH</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-50">
                        @foreach($historialRH as $solicitud)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-3">
                                    <div class="font-medium text-slate-700">{{ $solicitud->empleado->nombre }}</div>
                                </td>
                                <td class="px-6 py-3 text-xs text-slate-600">
                                    <span class="font-bold">{{ $solicitud->dias_solicitados }} días</span> 
                                    ({{ $solicitud->fecha_inicio->format('d/m/Y') }} al {{ $solicitud->fecha_fin->format('d/m/Y') }})
                                </td>
                                <td class="px-6 py-3 text-xs text-slate-500">
                                    {{ $solicitud->supervisor->nombre ?? 'N/A' }}
                                </td>
                                <td class="px-6 py-3">
                                    @if($solicitud->estado == 'aprobado')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            {{ $solicitud->aprobado_rh_at ? \Carbon\Carbon::parse($solicitud->aprobado_rh_at)->format('d/m/Y h:i A') : 'Aprobado' }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                                            {{ $solicitud->aprobado_rh_at ? \Carbon\Carbon::parse($solicitud->aprobado_rh_at)->format('d/m/Y h:i A') : 'Rechazado' }}
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
        @endif

        @endif

        @if($solicitudesSupervisor->count() == 0 && !Auth::user()->isRh())
            <div class="bg-slate-50 border border-slate-200 border-dashed rounded-2xl p-12 text-center">
                <svg class="w-16 h-16 mx-auto text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <h3 class="text-lg font-bold text-slate-700">Todo al día</h3>
                <p class="text-slate-500 mt-1">No tienes solicitudes de vacaciones pendientes por revisar de tu equipo.</p>
            </div>
        @endif

        </div> {{-- FIN CONTENEDOR VACACIONES --}}

        {{-- CONTENEDOR PERMISOS Y AUSENCIAS --}}
        <div x-show="activeTab === 'permisos'" style="display: none;">
            
            {{-- SECCIÓN SUPERVISOR PERMISOS --}}
            @if($permisosSupervisor->count() > 0)
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
                <div class="bg-indigo-50 border-b border-indigo-100 px-6 py-4">
                    <h2 class="text-lg font-bold text-indigo-900 flex items-center gap-2">
                        <svg class="w-5 h-5 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        Ausencias de mi equipo (Como Supervisor)
                    </h2>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead class="bg-slate-50 border-b border-slate-200 text-slate-600">
                            <tr>
                                <th class="px-6 py-3 font-semibold">Colaborador</th>
                                <th class="px-6 py-3 font-semibold">Fechas/Horas</th>
                                <th class="px-6 py-3 font-semibold">Tipo</th>
                                <th class="px-6 py-3 font-semibold">Detalle</th>
                                <th class="px-6 py-3 font-semibold text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($permisosSupervisor as $permiso)
                                <tr class="hover:bg-slate-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="font-bold text-slate-800">{{ $permiso->empleado->nombre }}</div>
                                        <div class="text-xs text-slate-500">{{ $permiso->empleado->posicion }}</div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-slate-700">
                                            {{ $permiso->fecha_inicio->format('d/m/Y') }}
                                            @if($permiso->fecha_inicio != $permiso->fecha_fin)
                                                al {{ $permiso->fecha_fin->format('d/m/Y') }}
                                            @endif
                                        </div>
                                        @if($permiso->hora_inicio && $permiso->hora_fin)
                                        <div class="text-xs text-slate-500">
                                            {{ \Carbon\Carbon::parse($permiso->hora_inicio)->format('H:i') }} - {{ \Carbon\Carbon::parse($permiso->hora_fin)->format('H:i') }}
                                        </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800 uppercase">
                                            {{ $permiso->tipo_permiso }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-slate-600 max-w-xs truncate" title="{{ $permiso->motivo_detalle }}">
                                        {{ $permiso->motivo_detalle }}
                                        @if($permiso->reposicion_tipo)
                                            <div class="mt-1 text-xs text-indigo-600 font-semibold">
                                                Reposición: {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $permiso->reposicion_tipo)) }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right" x-data="{ modalP_Open: false, action: '' }">
                                        <div class="flex justify-end gap-2 items-center" x-data="{ openUploadSup: false }">
                                            @if($permiso->tipo_permiso === 'legal' && !$permiso->comprobante_path)
                                                <button @click="openUploadSup = !openUploadSup" class="text-xs text-indigo-600 font-bold hover:text-indigo-800 flex items-center gap-1 mr-2 bg-indigo-50 px-2 py-1 rounded">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                                    Subir
                                                </button>
                                                <span class="text-xs text-amber-600 font-bold mr-2 self-center">Falta Comprobante</span>
                                            @endif
                                            <button @click="modalP_Open = true; action = 'aprobar'" class="px-3 py-1 bg-green-100 text-green-700 hover:bg-green-200 font-bold rounded-lg text-xs transition" @if($permiso->tipo_permiso === 'legal' && !$permiso->comprobante_path) disabled title="Falta el comprobante oficial" style="opacity: 0.5; cursor: not-allowed;" @endif>Autorizar</button>
                                            <button @click="modalP_Open = true; action = 'rechazar'" class="px-3 py-1 bg-red-100 text-red-700 hover:bg-red-200 font-bold rounded-lg text-xs transition">Rechazar</button>

                                            <div x-show="openUploadSup" class="absolute right-32 mt-10 p-3 bg-white rounded-lg border border-slate-200 shadow-xl z-10 w-64 text-left" style="display: none;" @click.away="openUploadSup = false">
                                                <p class="text-xs font-bold text-slate-700 mb-2">Adjuntar comprobante recibido físicamente:</p>
                                                <form action="{{ route('permisos.subir_comprobante', $permiso->id) }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-2">
                                                    @csrf
                                                    <input type="file" name="comprobante" accept=".pdf,.jpg,.jpeg,.png" required class="text-xs w-full text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-full file:border-0 file:text-[10px] file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                                    <div class="flex justify-end gap-2 mt-1">
                                                        <button type="button" @click="openUploadSup = false" class="px-2 py-1 text-[10px] text-slate-500 hover:text-slate-700">Cancelar</button>
                                                        <button type="submit" class="px-3 py-1 bg-indigo-600 text-white text-[10px] font-bold rounded hover:bg-indigo-700">Guardar</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                        {{-- Modal de Comentarios Permiso Supervisor --}}
                                        <div x-show="modalP_Open" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm" style="display: none;">
                                            <div @click.away="modalP_Open = false" class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl text-left">
                                                <h3 class="text-lg font-bold mb-2 text-slate-900">
                                                    <span x-show="action == 'aprobar'">Autorizar Permiso</span>
                                                    <span x-show="action == 'rechazar'">Rechazar Permiso</span>
                                                </h3>
                                                <form :action="action == 'aprobar' ? '{{ route('permisos.aprobar', $permiso->id) }}' : '{{ route('permisos.rechazar', $permiso->id) }}'" method="POST">
                                                    @csrf
                                                    <textarea name="comentarios" rows="3" class="w-full rounded-lg border-slate-300 text-sm focus:ring-indigo-500 mb-4" placeholder="Comentarios (Obligatorio si rechazas)"></textarea>
                                                    <div class="flex justify-end gap-3">
                                                        <button type="button" @click="modalP_Open = false" class="px-4 py-2 border border-slate-200 rounded-lg text-slate-600 font-medium hover:bg-slate-50">Cancelar</button>
                                                        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white font-bold rounded-lg hover:bg-indigo-700 shadow-lg">Confirmar</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            @endif

            {{-- SECCIÓN RH PERMISOS --}}
            @if(Auth::user()->isRh())
                @if($permisosRH->count() > 0)
                <div class="bg-white rounded-2xl shadow-sm border border-emerald-200 overflow-hidden mb-8 ring-1 ring-emerald-500/20">
                    <div class="bg-emerald-50 border-b border-emerald-100 px-6 py-4">
                        <h2 class="text-lg font-bold text-emerald-900 flex items-center gap-2">
                            <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Permisos para Visto Bueno Final (Recursos Humanos)
                        </h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 border-b border-slate-200 text-slate-600">
                                <tr>
                                    <th class="px-6 py-3 font-semibold">Colaborador</th>
                                    <th class="px-6 py-3 font-semibold">Fechas/Horas</th>
                                    <th class="px-6 py-3 font-semibold">Detalles</th>
                                    <th class="px-6 py-3 font-semibold text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($permisosRH as $permiso)
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-4">
                                            <div class="font-bold text-slate-800">{{ $permiso->empleado->nombre }}</div>
                                            <div class="text-xs text-slate-500">Supervisor: {{ $permiso->supervisor->nombre ?? 'N/A' }}</div>
                                        </td>
                                        <td class="px-6 py-4">
                                            <div class="font-medium text-slate-700">
                                                {{ $permiso->fecha_inicio->format('d/m/Y') }}
                                            </div>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800 uppercase mt-1">
                                                {{ $permiso->tipo_permiso }}
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-xs text-slate-600">
                                            {{ $permiso->motivo_detalle }}
                                            @if($permiso->reposicion_tipo)
                                                <div class="mt-1 font-semibold text-emerald-700">
                                                    Reposición: {{ \Illuminate\Support\Str::title(str_replace('_', ' ', $permiso->reposicion_tipo)) }}
                                                </div>
                                            @endif
                                            @if($permiso->comprobante_path)
                                                <div class="mt-2">
                                                    <a href="{{ asset('storage/' . $permiso->comprobante_path) }}" target="_blank" class="text-indigo-600 hover:text-indigo-800 font-bold flex items-center gap-1">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                                        Ver Comprobante
                                                    </a>
                                                </div>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-right" x-data="{ modalPRhOpen: false, action: '' }">
                                        <div class="flex justify-end gap-2 items-center" x-data="{ openUploadRh: false }">
                                            @if($permiso->tipo_permiso === 'legal' && !$permiso->comprobante_path)
                                                <button @click="openUploadRh = !openUploadRh" class="text-xs text-indigo-600 font-bold hover:text-indigo-800 flex items-center gap-1 mr-2 bg-indigo-50 px-2 py-1 rounded">
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                                    Subir
                                                </button>
                                                <span class="text-xs text-amber-600 font-bold mr-2 self-center">Falta Comprobante</span>
                                            @endif
                                            <button @click="modalPRhOpen = true; action = 'aprobar'" class="px-3 py-1 bg-emerald-100 text-emerald-700 hover:bg-emerald-200 font-bold rounded-lg text-xs transition" @if($permiso->tipo_permiso === 'legal' && !$permiso->comprobante_path) disabled style="opacity: 0.5; cursor: not-allowed;" @endif>Aprobar Final</button>
                                            <button @click="modalPRhOpen = true; action = 'rechazar'" class="px-3 py-1 bg-red-100 text-red-700 hover:bg-red-200 font-bold rounded-lg text-xs transition">Rechazar</button>

                                            <div x-show="openUploadRh" class="absolute right-32 mt-10 p-3 bg-white rounded-lg border border-slate-200 shadow-xl z-10 w-64 text-left" style="display: none;" @click.away="openUploadRh = false">
                                                <p class="text-xs font-bold text-slate-700 mb-2">Adjuntar comprobante físico (Recursos Humanos):</p>
                                                <form action="{{ route('permisos.subir_comprobante', $permiso->id) }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-2">
                                                    @csrf
                                                    <input type="file" name="comprobante" accept=".pdf,.jpg,.jpeg,.png" required class="text-xs w-full text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-full file:border-0 file:text-[10px] file:font-bold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100" />
                                                    <div class="flex justify-end gap-2 mt-1">
                                                        <button type="button" @click="openUploadRh = false" class="px-2 py-1 text-[10px] text-slate-500 hover:text-slate-700">Cancelar</button>
                                                        <button type="submit" class="px-3 py-1 bg-indigo-600 text-white text-[10px] font-bold rounded hover:bg-indigo-700">Guardar</button>
                                                    </div>
                                                </form>
                                            </div>
                                        </div>

                                        {{-- Modal de Comentarios Permisos RH --}}
                                            <div x-show="modalPRhOpen" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm" style="display: none;">
                                                <div @click.away="modalPRhOpen = false" class="bg-white rounded-2xl p-6 w-full max-w-md shadow-xl text-left">
                                                    <h3 class="text-lg font-bold mb-2 text-slate-900">
                                                        <span x-show="action == 'aprobar'">Aprobación Definitiva Permiso</span>
                                                        <span x-show="action == 'rechazar'">Rechazo de Recursos Humanos</span>
                                                    </h3>
                                                    <form :action="action == 'aprobar' ? '{{ route('permisos.aprobar', $permiso->id) }}' : '{{ route('permisos.rechazar', $permiso->id) }}'" method="POST">
                                                        @csrf
                                                        <textarea name="comentarios" rows="3" class="w-full rounded-lg border-slate-300 text-sm focus:ring-emerald-500 mb-4" placeholder="Comentarios finales..."></textarea>
                                                        <div class="flex justify-end gap-3">
                                                            <button type="button" @click="modalPRhOpen = false" class="px-4 py-2 border border-slate-200 rounded-lg text-slate-600 font-medium hover:bg-slate-50">Cancelar</button>
                                                            <button type="submit" class="px-4 py-2 bg-emerald-600 text-white font-bold rounded-lg hover:bg-emerald-700 shadow-lg">Confirmar</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @else
                <div class="bg-emerald-50 border border-emerald-200 border-dashed rounded-2xl p-8 text-center mb-8">
                    <svg class="w-12 h-12 mx-auto text-emerald-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <h3 class="text-base font-bold text-emerald-800">Cero pendientes para RH</h3>
                    <p class="text-emerald-600 text-sm mt-1">No hay permisos esperando tu visto bueno final.</p>
                </div>
                @endif

                {{-- MONITOREO DE RH PERMISOS: En espera de supervisor --}}
                @if($permisosGlobalesPendientes->count() > 0)
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
                    <div class="bg-slate-50 border-b border-slate-100 px-6 py-4 flex justify-between items-center">
                        <div>
                            <h2 class="text-sm font-bold text-slate-700 flex items-center gap-2">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Monitoreo de Permisos: En espera de Supervisor
                            </h2>
                            <p class="text-xs text-slate-500 mt-1">Estas solicitudes ya fueron creadas, pero su supervisor directo aún no las ha autorizado.</p>
                        </div>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm opacity-75">
                            <thead class="bg-slate-50/50 border-b border-slate-100 text-slate-500">
                                <tr>
                                    <th class="px-6 py-2 font-medium text-xs">Colaborador</th>
                                    <th class="px-6 py-2 font-medium text-xs">Fechas/Tipo</th>
                                    <th class="px-6 py-2 font-medium text-xs">Esperando respuesta de...</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @foreach($permisosGlobalesPendientes as $permiso)
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-3">
                                            <div class="font-medium text-slate-700">{{ $permiso->empleado->nombre }}</div>
                                        </td>
                                        <td class="px-6 py-3 text-xs text-slate-600">
                                            <span class="font-bold">{{ $permiso->tipo_permiso }}</span>: 
                                            {{ $permiso->fecha_inicio->format('d/m/Y') }}
                                        </td>
                                        <td class="px-6 py-3">
                                            <span class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                                {{ $permiso->supervisor->nombre ?? 'Sin supervisor' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif

                {{-- HISTORIAL DE PERMISOS --}}
                @if($historialPermisosRH->count() > 0)
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden mb-8">
                    <div class="bg-slate-50 border-b border-slate-100 px-6 py-4">
                        <h2 class="text-sm font-bold text-slate-700 flex items-center gap-2">
                            <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            Historial de Permisos Aprobados/Rechazados
                        </h2>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50/50 border-b border-slate-100 text-slate-500">
                                <tr>
                                    <th class="px-6 py-2 font-medium text-xs">Colaborador</th>
                                    <th class="px-6 py-2 font-medium text-xs">Fecha/Tipo</th>
                                    <th class="px-6 py-2 font-medium text-xs">Estado Final</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-50">
                                @foreach($historialPermisosRH as $permiso)
                                    <tr class="hover:bg-slate-50 transition-colors">
                                        <td class="px-6 py-3">
                                            <div class="font-medium text-slate-700">{{ $permiso->empleado->nombre }}</div>
                                        </td>
                                        <td class="px-6 py-3 text-xs text-slate-600">
                                            <span class="font-bold">{{ $permiso->tipo_permiso }}</span> - {{ $permiso->fecha_inicio->format('d/m/Y') }}
                                        </td>
                                        <td class="px-6 py-3">
                                            @if($permiso->estado == 'aprobado')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800">Aprobado</span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">Rechazado</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            @endif

            @if($permisosSupervisor->count() == 0 && !Auth::user()->isRh())
                <div class="bg-slate-50 border border-slate-200 border-dashed rounded-2xl p-12 text-center">
                    <svg class="w-16 h-16 mx-auto text-slate-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <h3 class="text-lg font-bold text-slate-700">Todo al día</h3>
                    <p class="text-slate-500 mt-1">No tienes solicitudes de permisos pendientes por revisar de tu equipo.</p>
                </div>
            @endif
        </div> {{-- FIN CONTENEDOR PERMISOS --}}

    </div>
</div>
@endsection
