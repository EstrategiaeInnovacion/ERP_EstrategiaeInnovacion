@extends('Sistemas_IT.layouts.master')

@section('title', 'Mi Perfil')

@section('content')
    <div class="py-6">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            <div class="flex flex-col md:flex-row justify-between items-start md:items-end gap-4 px-2">
                <div>
                    <h2 class="font-bold text-3xl text-slate-900 leading-tight tracking-tight">
                        {{ __('Mi Perfil') }}
                    </h2>
                    <p class="text-sm text-slate-500 mt-1">Consulta tu expediente y administra tu cuenta.</p>
                </div>
            </div>

            @if(isset($empleado) && $empleado)
                {{-- TARJETA 1: DATOS DEL EMPLEADO --}}
                <div class="p-4 sm:p-8 bg-white shadow-sm border border-indigo-100 rounded-3xl relative overflow-hidden">
                    <div class="absolute top-0 right-0 p-6 opacity-5 pointer-events-none">
                        <svg class="w-32 h-32 text-indigo-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"></path></svg>
                    </div>
                    
                    <header class="mb-6 relative z-10">
                        <h2 class="text-lg font-bold text-indigo-900">
                            {{ __('Información de Empleado') }}
                        </h2>
                        <p class="mt-1 text-sm text-slate-600">
                            Estos datos son gestionados por RH. Si hay un error, notifícalo.
                        </p>
                    </header>

                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 relative z-10">
                        {{-- Bloque Personal --}}
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <h4 class="text-xs font-bold text-indigo-500 uppercase mb-3">Datos Generales</h4>
                            <div class="space-y-2">
                                <div>
                                    <label class="text-[10px] text-slate-400 uppercase font-bold">Nombre</label>
                                    <p class="text-sm font-bold text-slate-800">{{ $empleado->nombre }} {{ $empleado->apellido_paterno }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] text-slate-400 uppercase font-bold">Puesto / Área</label>
                                    <p class="text-sm text-slate-700">{{ $empleado->posicion ?? 'N/A' }} - {{ $empleado->area ?? 'N/A' }}</p>
                                </div>
                                <div>
                                    <label class="text-[10px] text-slate-400 uppercase font-bold">No. Empleado</label>
                                    <p class="text-sm text-slate-700">{{ $empleado->id_empleado ?? 'S/N' }}</p>
                                </div>
                            </div>
                        </div>

                        {{-- Bloque Contacto --}}
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <h4 class="text-xs font-bold text-indigo-500 uppercase mb-3">Contacto y Domicilio</h4>
                            <div class="space-y-2">
                                <div>
                                    <label class="text-[10px] text-slate-400 uppercase font-bold">Dirección</label>
                                    <p class="text-sm text-slate-700">{{ $empleado->direccion ?? '--' }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ $empleado->ciudad ?? '' }} {{ $empleado->estado_federativo ?? '' }} CP: {{ $empleado->codigo_postal ?? '' }}
                                    </p>
                                </div>
                                <div class="flex gap-4">
                                    <div>
                                        <label class="text-[10px] text-slate-400 uppercase font-bold">Celular</label>
                                        <p class="text-sm text-slate-700">{{ $empleado->telefono ?? '--' }}</p>
                                    </div>
                                    <div>
                                        <label class="text-[10px] text-slate-400 uppercase font-bold">Tel. Casa</label>
                                        <p class="text-sm text-slate-700">{{ $empleado->telefono_casa ?? '--' }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        {{-- Bloque Salud y Emergencia --}}
                        <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                            <h4 class="text-xs font-bold text-red-400 uppercase mb-3">Salud y Emergencia</h4>
                            <div class="space-y-2">
                                <div>
                                    <label class="text-[10px] text-slate-400 uppercase font-bold">Alergias</label>
                                    <p class="text-sm {{ $empleado->alergias && strtolower($empleado->alergias) != 'no' ? 'text-red-600 font-bold' : 'text-slate-700' }}">
                                        {{ $empleado->alergias ?? 'No registradas' }}
                                    </p>
                                </div>
                                <div class="pt-2 border-t border-slate-200">
                                    <label class="text-[10px] text-slate-400 uppercase font-bold">Contacto Emergencia</label>
                                    <p class="text-sm font-bold text-slate-800">{{ $empleado->contacto_emergencia_nombre ?? '--' }}</p>
                                    <p class="text-xs text-slate-500">
                                        {{ $empleado->contacto_emergencia_numero ?? '--' }} 
                                        @if($empleado->contacto_emergencia_parentesco)
                                            ({{ $empleado->contacto_emergencia_parentesco }})
                                        @endif
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- TARJETA 2: CALENDARIO DE ASISTENCIA --}}
                <div class="p-4 sm:p-8 bg-white shadow-sm border border-slate-200 rounded-3xl relative overflow-hidden"
                     x-data="{ 
                        selectedDay: null, 
                        calendar: {{ json_encode($calendarData) }}
                     }">
                    
                    <header class="mb-6 flex flex-col sm:flex-row justify-between items-center relative z-10 gap-4">
                        <div class="flex items-center gap-4">
                            <div>
                                <h2 class="text-lg font-bold text-slate-900">Mi Asistencia</h2>
                                <p class="mt-1 text-sm text-slate-600">Selecciona un día para ver detalles.</p>
                            </div>
                            
                            {{-- SELECTOR DE MES --}}
                            <form method="GET" action="{{ route('profile.edit') }}">
                                <input type="month" name="periodo" 
                                    value="{{ $periodoActual }}" 
                                    class="rounded-lg border-slate-300 text-sm font-bold text-slate-700 focus:ring-indigo-500 focus:border-indigo-500 shadow-sm cursor-pointer hover:bg-slate-50"
                                    onchange="this.form.submit()">
                            </form>
                        </div>

                        {{-- KPIs --}}
                        <div class="flex flex-wrap gap-2 justify-end">
                            <div class="text-center px-3 py-1 bg-blue-50 rounded-lg border border-blue-100">
                                <span class="block text-[10px] font-bold text-blue-400 uppercase">Horas</span>
                                <span class="text-lg font-bold text-blue-700">{{ $kpis['horas'] }}</span>
                            </div>
                            <div class="text-center px-3 py-1 bg-amber-50 rounded-lg border border-amber-100">
                                <span class="block text-[10px] font-bold text-amber-400 uppercase">Retardos</span>
                                <span class="text-lg font-bold text-amber-700">{{ $kpis['retardos'] }}</span>
                            </div>
                            <div class="text-center px-3 py-1 bg-red-50 rounded-lg border border-red-100">
                                <span class="block text-[10px] font-bold text-red-400 uppercase">Faltas</span>
                                <span class="text-lg font-bold text-red-700">{{ $kpis['faltas'] }}</span>
                            </div>
                            <div class="text-center px-3 py-1 bg-indigo-50 rounded-lg border border-indigo-100">
                                <span class="block text-[10px] font-bold text-indigo-400 uppercase">Vacaciones</span>
                                <span class="text-lg font-bold text-indigo-700">{{ $diasVacaciones }}/{{ $totalVacaciones }}</span>
                            </div>
                            <button x-data @click="$dispatch('open-modal', 'modal-vacaciones')" class="ml-2 inline-flex items-center justify-center bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold px-4 py-2 rounded-lg transition-colors">
                                Solicitar Ausencia / Permiso
                            </button>
                        </div>
                    </header>

                    <div class="flex flex-col lg:flex-row gap-8 relative z-10">
                        
                        {{-- COLUMNA IZQUIERDA: CALENDARIO --}}
                        <div class="flex-1">
                            {{-- Encabezados de días --}}
                            <div class="grid grid-cols-7 gap-1 mb-2 text-center">
                                @foreach(['Lun','Mar','Mie','Jue','Vie','Sab','Dom'] as $dayName)
                                    <div class="text-xs font-bold text-slate-400 uppercase">{{ $dayName }}</div>
                                @endforeach
                            </div>

                            {{-- Grid de días --}}
                            <div class="grid grid-cols-7 gap-2">
                                {{-- Días vacíos del inicio --}}
                                @for($i = 0; $i < $blankDays; $i++)
                                    <div class="h-10 sm:h-12"></div>
                                @endfor

                                {{-- Días del mes --}}
                                @foreach($calendarData as $day)
                                    <button 
                                        @click="selectedDay = {{ json_encode($day) }}"
                                        :class="{ 'ring-2 ring-indigo-500 ring-offset-2': selectedDay && selectedDay.day === {{ $day['day'] }} }"
                                        class="h-10 sm:h-12 rounded-lg border flex flex-col items-center justify-center transition-all hover:shadow-md hover:scale-105 {{ $day['color_class'] }}">
                                        
                                        <span class="text-sm font-bold">{{ $day['day'] }}</span>
                                        
                                        {{-- Puntito indicador si tiene registro --}}
                                        @if($day['has_record'])
                                            <span class="w-1 h-1 rounded-full bg-current mt-1 opacity-50"></span>
                                        @endif
                                    </button>
                                @endforeach
                            </div>
                        </div>

                        {{-- COLUMNA DERECHA: DETALLE DEL DÍA SELECCIONADO --}}
                        <div class="lg:w-1/3 bg-slate-50 rounded-2xl border border-slate-100 p-6 flex flex-col justify-center min-h-[250px]">
                            
                            {{-- ESTADO: SI HAY DÍA SELECCIONADO --}}
                            <template x-if="selectedDay">
                                <div class="text-center space-y-4">
                                    <div>
                                        <p class="text-xs font-bold text-indigo-500 uppercase tracking-wide" x-text="selectedDay.weekday_name"></p>
                                        <h3 class="text-4xl font-bold text-slate-800" x-text="selectedDay.day"></h3>
                                        <p class="text-xs text-slate-400" x-text="selectedDay.full_date"></p>
                                    </div>

                                    <template x-if="selectedDay.details">
                                        <div class="space-y-4">
                                            <div class="inline-block px-3 py-1 rounded-full text-sm font-bold bg-white border shadow-sm"
                                                 :class="{
                                                    'text-emerald-600 border-emerald-200': selectedDay.details.tipo === 'Asistencia',
                                                    'text-red-600 border-red-200': selectedDay.details.tipo === 'Falta',
                                                    'text-amber-600 border-amber-200': selectedDay.details.tipo === 'Retardo',
                                                    'text-blue-600 border-blue-200': ['Vacaciones','Incapacidad','Descanso'].includes(selectedDay.details.tipo)
                                                 }"
                                                 x-text="selectedDay.details.estado_texto">
                                            </div>

                                            <div class="grid grid-cols-2 gap-4 bg-white p-3 rounded-xl border border-slate-200 shadow-sm">
                                                <div>
                                                    <p class="text-[10px] text-slate-400 uppercase font-bold">Entrada</p>
                                                    <p class="text-lg font-mono font-bold text-slate-700" x-text="selectedDay.details.entrada"></p>
                                                </div>
                                                <div>
                                                    <p class="text-[10px] text-slate-400 uppercase font-bold">Salida</p>
                                                    <p class="text-lg font-mono font-bold text-slate-700" x-text="selectedDay.details.salida"></p>
                                                </div>
                                            </div>

                                            <div x-show="selectedDay.details.comentarios" class="text-left bg-yellow-50 p-3 rounded-lg border border-yellow-100">
                                                <p class="text-[10px] text-yellow-600 font-bold uppercase mb-1">Observaciones:</p>
                                                <p class="text-xs text-slate-700 italic" x-text="selectedDay.details.comentarios"></p>
                                            </div>
                                        </div>
                                    </template>

                                    <template x-if="!selectedDay.details">
                                        <div class="py-4">
                                            <div class="w-12 h-12 bg-slate-200 rounded-full flex items-center justify-center mx-auto mb-2 text-slate-400">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            </div>
                                            <p class="text-sm font-medium text-slate-500">No hay registros para este día.</p>
                                            <p class="text-xs text-slate-400">Posible descanso o fin de semana.</p>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            {{-- ESTADO: SIN SELECCIÓN --}}
                            <template x-if="!selectedDay">
                                <div class="text-center text-slate-400">
                                    <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                    <p class="font-medium">Selecciona un día</p>
                                    <p class="text-xs mt-1">Haz clic en el calendario para ver el detalle.</p>
                                </div>
                            </template>

                        </div>
                    </div>

                    {{-- MIS SOLICITUDES DE VACACIONES --}}
                    @if($solicitudesVacaciones->count() > 0)
                    <div class="mt-8 border-t border-slate-200 pt-6">
                        <h3 class="text-sm font-bold text-slate-700 mb-4">Mis Solicitudes de Vacaciones Recientes</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm text-slate-600">
                                <thead>
                                    <tr class="border-b border-slate-200">
                                        <th class="py-2">Fechas</th>
                                        <th class="py-2">Días Hábiles</th>
                                        <th class="py-2">Estado</th>
                                        <th class="py-2">Motivo</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($solicitudesVacaciones as $solicitud)
                                        <tr class="border-b border-slate-100 last:border-0">
                                            <td class="py-2">{{ $solicitud->fecha_inicio->format('d/m/Y') }} al {{ $solicitud->fecha_fin->format('d/m/Y') }}</td>
                                            <td class="py-2 font-bold">{{ $solicitud->dias_solicitados }}</td>
                                            <td class="py-2">
                                                @if($solicitud->estado == 'pendiente')
                                                    <span class="bg-yellow-100 text-yellow-800 text-xs font-bold px-2 py-1 rounded-full">Pendiente</span>
                                                @elseif($solicitud->estado == 'aprobado_supervisor')
                                                    <span class="bg-blue-100 text-blue-800 text-xs font-bold px-2 py-1 rounded-full">En revisión RH</span>
                                                @elseif($solicitud->estado == 'aprobado')
                                                    <span class="bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded-full">Aprobado</span>
                                                @else
                                                    <span class="bg-red-100 text-red-800 text-xs font-bold px-2 py-1 rounded-full">Rechazado</span>
                                                @endif
                                            </td>
                                            <td class="py-2 truncate max-w-[150px]" title="{{ $solicitud->motivo }}">{{ $solicitud->motivo ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    {{-- MIS SOLICITUDES DE PERMISOS --}}
                    @if(isset($solicitudesPermisos) && $solicitudesPermisos->count() > 0)
                    <div class="mt-8 border-t border-slate-200 pt-6">
                        <h3 class="text-sm font-bold text-slate-700 mb-4">Mis Permisos y Ausencias Recientes</h3>
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm text-slate-600">
                                <thead>
                                    <tr class="border-b border-slate-200">
                                        <th class="py-2">Fechas/Horas</th>
                                        <th class="py-2">Tipo</th>
                                        <th class="py-2">Estado</th>
                                        <th class="py-2">Detalles</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($solicitudesPermisos as $permiso)
                                        <tr class="border-b border-slate-100 last:border-0 hover:bg-slate-50 transition-colors">
                                            <td class="py-2">
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
                                            <td class="py-2">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-slate-100 text-slate-800 uppercase">
                                                    {{ $permiso->tipo_permiso }}
                                                </span>
                                            </td>
                                            <td class="py-2">
                                                @if($permiso->estado == 'pendiente')
                                                    <span class="bg-yellow-100 text-yellow-800 text-xs font-bold px-2 py-1 rounded-full">Pendiente</span>
                                                @elseif($permiso->estado == 'aprobado_supervisor')
                                                    <span class="bg-blue-100 text-blue-800 text-xs font-bold px-2 py-1 rounded-full">En revisión RH</span>
                                                @elseif($permiso->estado == 'aprobado')
                                                    <span class="bg-green-100 text-green-800 text-xs font-bold px-2 py-1 rounded-full">Aprobado</span>
                                                @else
                                                    <span class="bg-red-100 text-red-800 text-xs font-bold px-2 py-1 rounded-full">Rechazado</span>
                                                @endif
                                            </td>
                                            <td class="py-2">
                                                <div class="truncate max-w-[150px]" title="{{ $permiso->motivo_detalle }}">
                                                    {{ $permiso->motivo_detalle ?? '-' }}
                                                </div>
                                                @if($permiso->tipo_permiso === 'legal' && !$permiso->comprobante_path)
                                                    <div class="mt-2" x-data="{ openUpload: false }">
                                                        <button @click="openUpload = !openUpload" class="text-xs text-indigo-600 font-bold hover:text-indigo-800 flex items-center gap-1">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                                            Subir Comprobante
                                                        </button>
                                                        <div x-show="openUpload" class="mt-2 p-3 bg-indigo-50 rounded-lg border border-indigo-100 min-w-[200px]" style="display: none;">
                                                            <form action="{{ route('permisos.subir_comprobante', $permiso->id) }}" method="POST" enctype="multipart/form-data" class="flex flex-col gap-2">
                                                                @csrf
                                                                <input type="file" name="comprobante" accept=".pdf,.jpg,.jpeg,.png" required class="text-xs w-full text-slate-500 file:mr-2 file:py-1 file:px-2 file:rounded-full file:border-0 file:text-[10px] file:font-bold file:bg-indigo-100 file:text-indigo-700 hover:file:bg-indigo-200" />
                                                                <button type="submit" class="self-end px-3 py-1 bg-indigo-600 text-white text-xs font-bold rounded hover:bg-indigo-700 shadow-sm">Guardar</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                @elseif($permiso->comprobante_path)
                                                    <div class="mt-2">
                                                        <a href="{{ asset('storage/' . $permiso->comprobante_path) }}" target="_blank" class="text-[10px] text-emerald-600 font-bold flex items-center gap-1 hover:underline">
                                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                                            Documento Adjunto
                                                        </a>
                                                    </div>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif
                </div>
                
                {{-- MODAL SOLICITUD VACACIONES Y PERMISOS --}}
                <x-modal name="modal-vacaciones" focusable>
                    <div class="p-6" x-data="{
                        tab: 'vacaciones',
                        fechaInicio: '',
                        fechaFin: '',
                        diasCalculados: null,
                        calculando: false,
                        maxDias: {{ $diasVacaciones }},
                        tipoPermiso: 'corto',
                        calcularDias() {
                            if(this.fechaInicio && this.fechaFin && this.fechaInicio <= this.fechaFin) {
                                this.calculando = true;
                                fetch('{{ route('vacaciones.calcular-dias') }}?fecha_inicio=' + this.fechaInicio + '&fecha_fin=' + this.fechaFin)
                                    .then(res => res.json())
                                    .then(data => {
                                        this.diasCalculados = data.dias;
                                        this.calculando = false;
                                    })
                                    .catch(() => this.calculando = false);
                            } else {
                                this.diasCalculados = null;
                            }
                        }
                    }">
                        <div class="flex items-center gap-4 border-b border-slate-200 pb-4 mb-6">
                            <button @click="tab = 'vacaciones'" :class="tab === 'vacaciones' ? 'text-indigo-600 border-indigo-600' : 'text-slate-500 border-transparent hover:text-slate-700'" class="pb-2 border-b-2 font-bold text-sm transition-colors">Vacaciones</button>
                            <button @click="tab = 'permisos'" :class="tab === 'permisos' ? 'text-indigo-600 border-indigo-600' : 'text-slate-500 border-transparent hover:text-slate-700'" class="pb-2 border-b-2 font-bold text-sm transition-colors">Permisos y Ausencias</button>
                        </div>

                        {{-- TAB: VACACIONES --}}
                        <div x-show="tab === 'vacaciones'">
                            <h2 class="text-lg font-bold text-slate-900 mb-2">Solicitar Vacaciones</h2>
                            <p class="text-sm text-slate-600 mb-6">Selecciona el rango de fechas. Solo se te descontarán los días hábiles (lunes a viernes, sin contar días festivos).</p>
                            
                            <form method="POST" action="{{ route('vacaciones.solicitar') }}">
                                @csrf
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div>
                                        <x-input-label for="fecha_inicio_v" value="Fecha de Inicio" />
                                        <x-text-input id="fecha_inicio_v" name="fecha_inicio" type="date" class="mt-1 block w-full" x-model="fechaInicio" @change="calcularDias" required />
                                    </div>
                                    <div>
                                        <x-input-label for="fecha_fin_v" value="Fecha de Fin" />
                                        <x-text-input id="fecha_fin_v" name="fecha_fin" type="date" class="mt-1 block w-full" x-model="fechaFin" @change="calcularDias" required />
                                    </div>
                                </div>

                                <div x-show="diasCalculados !== null" class="mb-4 p-3 rounded-lg border flex items-center gap-3"
                                     :class="diasCalculados > maxDias ? 'bg-red-50 border-red-200 text-red-700' : 'bg-indigo-50 border-indigo-200 text-indigo-700'">
                                    <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    <div>
                                        <p class="text-sm font-bold">Días hábiles a descontar: <span x-text="diasCalculados"></span></p>
                                        <p class="text-xs" x-show="diasCalculados > maxDias">No tienes suficientes días disponibles.</p>
                                        <p class="text-xs" x-show="diasCalculados <= maxDias">Te quedarán <span x-text="maxDias - diasCalculados"></span> días disponibles después de esta solicitud.</p>
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <x-input-label for="motivo_v" value="Motivo / Observaciones (Opcional)" />
                                    <textarea id="motivo_v" name="motivo" rows="2" class="mt-1 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"></textarea>
                                </div>

                                <div class="mt-6 flex justify-end gap-3">
                                    <x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                                    <x-primary-button x-bind:disabled="calculando || (diasCalculados !== null && diasCalculados > maxDias) || diasCalculados === 0" 
                                                      x-bind:class="{ 'opacity-50 cursor-not-allowed': calculando || (diasCalculados !== null && diasCalculados > maxDias) || diasCalculados === 0 }">
                                        Enviar Solicitud
                                    </x-primary-button>
                                </div>
                            </form>
                        </div>

                        {{-- TAB: PERMISOS --}}
                        <div x-show="tab === 'permisos'" style="display: none;">
                            <h2 class="text-lg font-bold text-slate-900 mb-2">Solicitar Ausencia / Permiso</h2>
                            <p class="text-sm text-slate-600 mb-6">Completa los datos para justificar una ausencia (retardos, citas, incapacidades).</p>
                            
                            <form method="POST" action="{{ route('permisos.solicitar') }}" enctype="multipart/form-data">
                                @csrf
                                
                                <div class="mb-4">
                                    <x-input-label for="tipo_permiso" value="Tipo de Permiso" />
                                    <select id="tipo_permiso" name="tipo_permiso" x-model="tipoPermiso" class="mt-1 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required>
                                        <option value="corto">Ausencia Corta (< 4 horas)</option>
                                        <option value="legal">Permiso Legal / Incapacidad (Requiere Justificante)</option>
                                        <option value="especial">Permiso Especial (Sin goce de sueldo)</option>
                                    </select>
                                </div>

                                <div class="grid gap-4 mb-4" :class="tipoPermiso === 'corto' ? 'grid-cols-1' : 'grid-cols-2'">
                                    <div>
                                        <x-input-label for="fecha_inicio_p" value="Fecha" x-text="tipoPermiso === 'corto' ? 'Fecha de Ausencia' : 'Fecha de Inicio'" />
                                        <x-text-input id="fecha_inicio_p" name="fecha_inicio" type="date" class="mt-1 block w-full" required />
                                    </div>
                                    <div x-show="tipoPermiso !== 'corto'">
                                        <x-input-label for="fecha_fin_p" value="Fecha de Fin" />
                                        <x-text-input id="fecha_fin_p" name="fecha_fin" type="date" class="mt-1 block w-full" x-bind:required="tipoPermiso !== 'corto'" />
                                    </div>
                                </div>

                                <div x-show="tipoPermiso === 'corto'" class="grid grid-cols-2 gap-4 mb-4" style="display: none;">
                                    <div>
                                        <x-input-label for="hora_inicio" value="Hora de Salida" />
                                        <x-text-input id="hora_inicio" name="hora_inicio" type="time" class="mt-1 block w-full" x-bind:required="tipoPermiso === 'corto'" />
                                    </div>
                                    <div>
                                        <x-input-label for="hora_fin" value="Hora de Regreso" />
                                        <x-text-input id="hora_fin" name="hora_fin" type="time" class="mt-1 block w-full" x-bind:required="tipoPermiso === 'corto'" />
                                    </div>
                                </div>

                                <div x-show="tipoPermiso === 'corto'" class="mb-4" style="display: none;">
                                    <x-input-label for="reposicion_tipo" value="Forma de Reposición" />
                                    <select id="reposicion_tipo" name="reposicion_tipo" class="mt-1 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" x-bind:required="tipoPermiso === 'corto'">
                                        <option value="">Selecciona...</option>
                                        <option value="tiempo_por_tiempo">Tiempo por Tiempo</option>
                                        <option value="descuento_nomina">Descuento de Nómina</option>
                                    </select>
                                </div>

                                <div x-show="tipoPermiso === 'legal'" class="mb-4 p-4 bg-amber-50 border border-amber-200 rounded-lg" style="display: none;">
                                    <x-input-label for="comprobante" value="Comprobante Oficial (IMSS, Acta, Citatorio) - Opcional al solicitar" class="text-amber-800" />
                                    <input type="file" id="comprobante" name="comprobante" accept=".pdf,.jpg,.jpeg,.png" class="mt-2 block w-full text-sm text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-amber-100 file:text-amber-700 hover:file:bg-amber-200" />
                                    <p class="text-xs text-amber-600 mt-2">Formatos permitidos: PDF, JPG, PNG. Max 5MB.</p>
                                    <p class="text-xs font-bold text-amber-700 mt-1">Nota: Puedes adjuntarlo después, pero recuerda que tienes un plazo máximo de 48 horas para hacerlo.</p>
                                </div>

                                <div class="mb-4">
                                    <x-input-label for="motivo_detalle" value="Motivo Detallado" />
                                    <textarea id="motivo_detalle" name="motivo_detalle" rows="2" class="mt-1 block w-full border-slate-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" required></textarea>
                                </div>

                                <div class="mt-6 flex justify-end gap-3">
                                    <x-secondary-button x-on:click="$dispatch('close')">Cancelar</x-secondary-button>
                                    <x-primary-button>Enviar Solicitud</x-primary-button>
                                </div>
                            </form>
                        </div>
                    </div>
                </x-modal>

            @else
                <div class="bg-amber-50 border-l-4 border-amber-400 p-4 mb-4 rounded-r shadow-sm">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-amber-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm text-amber-700">
                                Tu usuario no tiene un expediente de empleado asociado. Contacta a RH para vincular tus datos.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            {{-- FORMULARIOS ORIGINALES DE BREEZE --}}
            <div class="p-4 sm:p-8 bg-white shadow-sm border border-slate-200 rounded-3xl relative overflow-hidden">
                <div class="absolute top-0 right-0 p-6 opacity-10 pointer-events-none">
                    <svg class="w-24 h-24 text-indigo-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"></path></svg>
                </div>
                <div class="max-w-xl relative z-10">
                    @include('Sistemas_IT.profile.partials.update-profile-information-form')
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow-sm border border-slate-200 rounded-3xl relative overflow-hidden">
                <div class="absolute top-0 right-0 p-6 opacity-10 pointer-events-none">
                    <svg class="w-24 h-24 text-emerald-600" fill="currentColor" viewBox="0 0 24 24"><path d="M12.65 10C11.83 7.67 9.61 6 7 6c-3.31 0-6 2.69-6 6s2.69 6 6 6c2.61 0 4.83-1.67 5.65-4H17v4h4v-4h2v-4H12.65zM7 14c-1.1 0-2-.9-2-2s.9-2 2-2 2 .9 2 2-.9 2-2 2z"></path></svg>
                </div>
                <div class="max-w-xl relative z-10">
                    @include('Sistemas_IT.profile.partials.update-password-form')
                </div>
            </div>



        </div>
    </div>
@endsection