@extends('layouts.master')

@php
    $editing = isset($mantenimiento);
    $title = $editing ? 'Editar Mantenimiento — ' . $mantenimiento->folio : 'Nuevo Mantenimiento';
@endphp

@section('title', $title)

@section('content')
<div class="min-h-screen bg-slate-50 pb-16">

    {{-- Header --}}
    <div class="bg-white border-b border-slate-200 mb-8">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
            <div class="flex items-center gap-3">
                <a href="{{ route('admin.expedientes.show', $expediente) }}"
                   class="p-2 rounded-lg hover:bg-slate-100 text-slate-500 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                </a>
                <div>
                    <h1 class="text-xl font-bold text-slate-900">{{ $title }}</h1>
                    <p class="text-slate-500 text-sm">
                        Equipo: {{ $expediente->equipoAsignado->nombre_equipo ?? '—' }}
                        @if($expediente->equipoAsignado->modelo) &mdash; {{ $expediente->equipoAsignado->modelo }} @endif
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">

        @if($errors->any())
        <div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
            <ul class="text-sm text-red-700 space-y-1 list-disc list-inside">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST"
              action="{{ $editing
                ? route('admin.expedientes.mantenimiento.update', [$expediente, $mantenimiento])
                : route('admin.expedientes.mantenimiento.store', $expediente) }}"
              enctype="multipart/form-data"
              x-data="mantenimientoForm()"
              @submit="prepareSubmit">
            @csrf
            @if($editing) @method('PUT') @endif

            {{-- ── Sección 1: Datos generales ────────────────────────────── --}}
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 mb-6 space-y-5">
                <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wide">Datos generales</h2>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Tipo <span class="text-red-500">*</span></label>
                        <select name="tipo" x-model="tipo" required
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="preventivo" @selected(old('tipo', $mantenimiento->tipo ?? 'preventivo') === 'preventivo')>Preventivo</option>
                            <option value="correctivo" @selected(old('tipo', $mantenimiento->tipo ?? '') === 'correctivo')>Correctivo</option>
                            <option value="emergente" @selected(old('tipo', $mantenimiento->tipo ?? '') === 'emergente')>Emergente</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Prioridad <span class="text-red-500">*</span></label>
                        <select name="prioridad" required
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="baja" @selected(old('prioridad', $mantenimiento->prioridad ?? '') === 'baja')>Baja</option>
                            <option value="media" @selected(old('prioridad', $mantenimiento->prioridad ?? 'media') === 'media')>Media</option>
                            <option value="alta" @selected(old('prioridad', $mantenimiento->prioridad ?? '') === 'alta')>Alta</option>
                            <option value="critica" @selected(old('prioridad', $mantenimiento->prioridad ?? '') === 'critica')>Crítica</option>
                        </select>
                    </div>
                    @if($editing)
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Estado</label>
                        <select name="estado" required
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="pendiente" @selected(old('estado', $mantenimiento->estado) === 'pendiente')>Pendiente</option>
                            <option value="en_proceso" @selected(old('estado', $mantenimiento->estado) === 'en_proceso')>En proceso</option>
                            <option value="completado" @selected(old('estado', $mantenimiento->estado) === 'completado')>Completado</option>
                            <option value="cancelado" @selected(old('estado', $mantenimiento->estado) === 'cancelado')>Cancelado</option>
                        </select>
                    </div>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Técnico asignado</label>
                        <select name="tecnico_id"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">— Sin asignar —</option>
                            @foreach($tecnicos as $tec)
                            <option value="{{ $tec->id }}" @selected(old('tecnico_id', $mantenimiento->tecnico_id ?? '') == $tec->id)>
                                {{ $tec->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Ticket relacionado</label>
                        <select name="ticket_id"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">— Ninguno —</option>
                            @foreach($ticketsDisponibles as $tkt)
                            <option value="{{ $tkt->id }}" @selected(old('ticket_id', $mantenimiento->ticket_id ?? '') == $tkt->id)>
                                {{ $tkt->folio }} ({{ ucfirst($tkt->estado) }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Fecha / hora inicio</label>
                        <input type="datetime-local" name="fecha_inicio"
                               value="{{ old('fecha_inicio', isset($mantenimiento->fecha_inicio) ? $mantenimiento->fecha_inicio->format('Y-m-d\TH:i') : '') }}"
                               class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Fecha / hora fin</label>
                        <input type="datetime-local" name="fecha_fin" id="fecha_fin"
                               value="{{ old('fecha_fin', isset($mantenimiento->fecha_fin) ? $mantenimiento->fecha_fin->format('Y-m-d\TH:i') : '') }}"
                               class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Descripción del problema / motivo</label>
                    <textarea name="descripcion_problema" rows="3" maxlength="2000"
                              placeholder="Describe el problema o motivo del mantenimiento…"
                              class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-y">{{ old('descripcion_problema', $mantenimiento->descripcion_problema ?? '') }}</textarea>
                </div>
            </div>

            {{-- ── Sección 2: Checklist de actividades ───────────────────── --}}
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wide">Checklist de actividades</h2>
                    <button type="button" @click="addActividad"
                            class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition">
                        + Agregar actividad
                    </button>
                </div>

                <div class="space-y-3" id="checklist-container">
                    <template x-for="(item, idx) in actividades" :key="idx">
                        <div class="grid grid-cols-12 gap-2 items-start bg-slate-50 rounded-lg p-3">
                            <div class="col-span-3">
                                <input type="text" :name="`actividades[${idx}][categoria]`" x-model="item.categoria"
                                       placeholder="Categoría"
                                       class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-400">
                            </div>
                            <div class="col-span-4">
                                <input type="text" :name="`actividades[${idx}][actividad]`" x-model="item.actividad"
                                       placeholder="Actividad"
                                       class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-400">
                            </div>
                            <div class="col-span-2">
                                <select :name="`actividades[${idx}][estado]`" x-model="item.estado"
                                        class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-400">
                                    <option value="pendiente">Pendiente</option>
                                    <option value="completado">Completado</option>
                                    <option value="no_aplica">N/A</option>
                                </select>
                            </div>
                            <div class="col-span-2">
                                <input type="text" :name="`actividades[${idx}][observaciones]`" x-model="item.observaciones"
                                       placeholder="Obs."
                                       class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-400">
                            </div>
                            <div class="col-span-1 flex justify-center pt-1.5">
                                <button type="button" @click="removeActividad(idx)"
                                        class="text-red-400 hover:text-red-600 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            {{-- ── Sección 3: Hallazgos ──────────────────────────────────── --}}
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 mb-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wide">Hallazgos y recomendaciones</h2>
                    <button type="button" @click="addHallazgo"
                            class="text-xs font-semibold text-blue-600 hover:text-blue-800 transition">
                        + Agregar hallazgo
                    </button>
                </div>

                <div class="space-y-3">
                    <template x-for="(h, idx) in hallazgos" :key="idx">
                        <div class="grid grid-cols-12 gap-2 items-start bg-slate-50 rounded-lg p-3">
                            <div class="col-span-5">
                                <input type="text" :name="`hallazgos[${idx}][descripcion]`" x-model="h.descripcion"
                                       placeholder="Descripción del hallazgo"
                                       class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-400">
                            </div>
                            <div class="col-span-2">
                                <select :name="`hallazgos[${idx}][nivel_riesgo]`" x-model="h.nivel_riesgo"
                                        class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-400">
                                    <option value="bajo">Bajo</option>
                                    <option value="medio">Medio</option>
                                    <option value="alto">Alto</option>
                                    <option value="critico">Crítico</option>
                                </select>
                            </div>
                            <div class="col-span-4">
                                <input type="text" :name="`hallazgos[${idx}][recomendacion]`" x-model="h.recomendacion"
                                       placeholder="Recomendación"
                                       class="w-full border border-slate-200 rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-blue-400">
                            </div>
                            <div class="col-span-1 flex justify-center pt-1.5">
                                <button type="button" @click="removeHallazgo(idx)"
                                        class="text-red-400 hover:text-red-600 transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        </div>
                    </template>
                    <template x-if="hallazgos.length === 0">
                        <p class="text-xs text-slate-400 text-center py-2">Sin hallazgos registrados.</p>
                    </template>
                </div>
            </div>

            {{-- ── Sección 4: Programación y observaciones ───────────────── --}}
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 mb-6 space-y-4">
                <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wide">Programación y cierre</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Próximo mantenimiento</label>
                        <input type="date" name="proximo_mantenimiento" id="proximo_mantenimiento"
                               value="{{ old('proximo_mantenimiento', isset($mantenimiento->proximo_mantenimiento) ? $mantenimiento->proximo_mantenimiento->format('Y-m-d') : '') }}"
                               class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-1">Frecuencia sugerida</label>
                        <select name="frecuencia_siguiente" id="frecuencia_siguiente"
                                class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">— No definido —</option>
                            <option value="mensual" @selected(old('frecuencia_siguiente', $mantenimiento->frecuencia_siguiente ?? '') === 'mensual')>Mensual</option>
                            <option value="trimestral" @selected(old('frecuencia_siguiente', $mantenimiento->frecuencia_siguiente ?? '') === 'trimestral')>Trimestral</option>
                            <option value="semestral" @selected(old('frecuencia_siguiente', $mantenimiento->frecuencia_siguiente ?? 'semestral') === 'semestral')>Semestral</option>
                            <option value="anual" @selected(old('frecuencia_siguiente', $mantenimiento->frecuencia_siguiente ?? '') === 'anual')>Anual</option>
                        </select>
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-1">Observaciones generales</label>
                    <textarea name="observaciones" rows="3" maxlength="2000"
                              class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-y">{{ old('observaciones', $mantenimiento->observaciones ?? '') }}</textarea>
                </div>
            </div>

            {{-- ── Sección 5: Firmas ─────────────────────────────────────── --}}
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 mb-6"
                 x-data="firmasForm()">
                <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wide mb-4">Firmas</h2>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-6">
                    {{-- Firma técnico --}}
                    <div>
                        <p class="text-sm font-semibold text-slate-700 mb-2">Firma del técnico</p>
                        <canvas id="canvas-tecnico" width="400" height="160"
                                class="w-full border-2 border-dashed border-slate-300 rounded-lg bg-white touch-none cursor-crosshair"
                                @mousedown="startDraw($event, 'tecnico')"
                                @mousemove="draw($event, 'tecnico')"
                                @mouseup="stopDraw('tecnico')"
                                @mouseleave="stopDraw('tecnico')"
                                @touchstart.prevent="startDraw($event, 'tecnico')"
                                @touchmove.prevent="draw($event, 'tecnico')"
                                @touchend="stopDraw('tecnico')">
                        </canvas>
                        <div class="flex items-center gap-2 mt-2">
                            <button type="button" @click="clearCanvas('tecnico')"
                                    class="text-xs text-slate-500 hover:text-red-500 transition">Limpiar</button>
                        </div>
                        <input type="hidden" name="firma_tecnico" x-ref="firmaTecnicoInput"
                               value="{{ old('firma_tecnico', $mantenimiento->firma_tecnico ?? '') }}">
                    </div>

                    {{-- Firma usuario --}}
                    <div>
                        <p class="text-sm font-semibold text-slate-700 mb-2">Firma del usuario / receptor</p>
                        <canvas id="canvas-usuario" width="400" height="160"
                                class="w-full border-2 border-dashed border-slate-300 rounded-lg bg-white touch-none cursor-crosshair"
                                @mousedown="startDraw($event, 'usuario')"
                                @mousemove="draw($event, 'usuario')"
                                @mouseup="stopDraw('usuario')"
                                @mouseleave="stopDraw('usuario')"
                                @touchstart.prevent="startDraw($event, 'usuario')"
                                @touchmove.prevent="draw($event, 'usuario')"
                                @touchend="stopDraw('usuario')">
                        </canvas>
                        <div class="flex items-center gap-2 mt-2">
                            <button type="button" @click="clearCanvas('usuario')"
                                    class="text-xs text-slate-500 hover:text-red-500 transition">Limpiar</button>
                        </div>
                        <input type="hidden" name="firma_usuario" x-ref="firmaUsuarioInput"
                               value="{{ old('firma_usuario', $mantenimiento->firma_usuario ?? '') }}">
                        <div class="mt-2">
                            <input type="text" name="nombre_firma_usuario"
                                   value="{{ old('nombre_firma_usuario', $mantenimiento->nombre_firma_usuario ?? '') }}"
                                   placeholder="Nombre completo del receptor"
                                   class="w-full border border-slate-200 rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        </div>
                    </div>
                </div>
            </div>

            {{-- ── Sección 6: Archivos adjuntos ─────────────────────────── --}}
            <div class="bg-white border border-slate-200 rounded-xl shadow-sm p-6 mb-6 space-y-4">
                <h2 class="text-sm font-semibold text-slate-600 uppercase tracking-wide">Archivos adjuntos</h2>
                <p class="text-xs text-slate-400">Imágenes o documentos del equipo. Máx. 5 MB por archivo.</p>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    @foreach(['antes' => 'Antes del mantenimiento', 'despues' => 'Después del mantenimiento', 'documento' => 'Documentos adicionales'] as $momento => $label)
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">{{ $label }}</label>
                        <input type="file" name="archivos_{{ $momento }}[]" multiple
                               accept="image/jpeg,image/png,image/gif,application/pdf"
                               class="w-full text-xs text-slate-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:bg-slate-100 file:text-slate-700 hover:file:bg-slate-200 transition">
                    </div>
                    @endforeach
                </div>

                @if($editing && $mantenimiento->archivos->isNotEmpty())
                <div>
                    <p class="text-xs font-semibold text-slate-500 mb-2">Archivos actuales</p>
                    <div class="flex flex-wrap gap-2">
                        @foreach($mantenimiento->archivos as $archivo)
                        <div class="flex items-center gap-2 bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 text-xs">
                            <a href="{{ Storage::url($archivo->ruta) }}" target="_blank"
                               class="text-blue-600 hover:underline">
                                {{ $archivo->momento_label }}: {{ Str::limit($archivo->nombre_original ?? 'archivo', 25) }}
                            </a>
                            <form action="{{ route('admin.expedientes.archivo.destroy', $archivo) }}"
                                  method="POST" class="inline"
                                  onsubmit="return confirm('¿Eliminar archivo?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="text-red-400 hover:text-red-600 transition">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                        @endforeach
                    </div>
                </div>
                @endif
            </div>

            {{-- Botones --}}
            <div class="flex items-center justify-end gap-3 pb-4">
                <a href="{{ route('admin.expedientes.show', $expediente) }}"
                   class="px-5 py-2.5 bg-white border border-slate-200 text-slate-600 text-sm font-semibold rounded-xl hover:bg-slate-50 transition">
                    Cancelar
                </a>
                <button type="submit"
                        class="px-6 py-2.5 bg-blue-600 text-white text-sm font-semibold rounded-xl hover:bg-blue-700 transition shadow-sm shadow-blue-200">
                    {{ $editing ? 'Guardar cambios' : 'Registrar mantenimiento' }}
                </button>
            </div>
        </form>
    </div>
</div>

@push('scripts')
<script>
function mantenimientoForm() {
    return {
        tipo: '{{ old('tipo', $mantenimiento->tipo ?? 'preventivo') }}',
        actividades: @json(old('actividades', $checklist)),
        hallazgos: @json(old('hallazgos', $mantenimiento->hallazgos ?? [])),

        addActividad() {
            this.actividades.push({ categoria: '', actividad: '', estado: 'pendiente', observaciones: '' });
        },
        removeActividad(idx) {
            this.actividades.splice(idx, 1);
        },
        addHallazgo() {
            this.hallazgos.push({ descripcion: '', nivel_riesgo: 'bajo', recomendacion: '' });
        },
        removeHallazgo(idx) {
            this.hallazgos.splice(idx, 1);
        },
        prepareSubmit() {
            // Firmas handled separately in firmasForm
        },
    };
}

function firmasForm() {
    return {
        drawing: { tecnico: false, usuario: false },
        ctxs: {},

        init() {
            ['tecnico', 'usuario'].forEach(who => {
                const canvas = document.getElementById(`canvas-${who}`);
                if (!canvas) return;
                const ctx = canvas.getContext('2d');
                ctx.strokeStyle = '#1e293b';
                ctx.lineWidth = 2;
                ctx.lineCap = 'round';
                ctx.lineJoin = 'round';
                this.ctxs[who] = ctx;

                // Restore existing signature if editing
                const existing = this.$refs[`firma${who.charAt(0).toUpperCase() + who.slice(1)}Input`]?.value;
                if (existing && existing.startsWith('data:image')) {
                    const img = new Image();
                    img.onload = () => ctx.drawImage(img, 0, 0);
                    img.src = existing;
                }
            });
        },

        getPos(event, canvas) {
            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            const src = event.touches ? event.touches[0] : event;
            return {
                x: (src.clientX - rect.left) * scaleX,
                y: (src.clientY - rect.top) * scaleY,
            };
        },

        startDraw(event, who) {
            this.drawing[who] = true;
            const canvas = document.getElementById(`canvas-${who}`);
            const pos = this.getPos(event, canvas);
            const ctx = this.ctxs[who];
            ctx.beginPath();
            ctx.moveTo(pos.x, pos.y);
        },

        draw(event, who) {
            if (!this.drawing[who]) return;
            const canvas = document.getElementById(`canvas-${who}`);
            const pos = this.getPos(event, canvas);
            const ctx = this.ctxs[who];
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
        },

        stopDraw(who) {
            if (!this.drawing[who]) return;
            this.drawing[who] = false;
            const canvas = document.getElementById(`canvas-${who}`);
            const ref = this.$refs[`firma${who.charAt(0).toUpperCase() + who.slice(1)}Input`];
            if (ref) ref.value = canvas.toDataURL('image/png');
        },

        clearCanvas(who) {
            const canvas = document.getElementById(`canvas-${who}`);
            this.ctxs[who].clearRect(0, 0, canvas.width, canvas.height);
            const ref = this.$refs[`firma${who.charAt(0).toUpperCase() + who.slice(1)}Input`];
            if (ref) ref.value = '';
        },
    };
}

// ── Cálculo automático de próximo mantenimiento ──────────────────────────
(function () {
    const fechaFinEl      = document.getElementById('fecha_fin');
    const frecuenciaEl    = document.getElementById('frecuencia_siguiente');
    const proximoEl       = document.getElementById('proximo_mantenimiento');

    const mesesPorFrecuencia = { mensual: 1, trimestral: 3, semestral: 6, anual: 12 };

    function calcularProximo() {
        if (!fechaFinEl.value || !frecuenciaEl.value) return;
        if (proximoEl.dataset.manualEdit === '1') return;

        const base   = new Date(fechaFinEl.value);
        const meses  = mesesPorFrecuencia[frecuenciaEl.value];
        if (!meses) return;

        base.setMonth(base.getMonth() + meses);
        proximoEl.value = base.toISOString().slice(0, 10);
    }

    fechaFinEl?.addEventListener('change', calcularProximo);
    frecuenciaEl?.addEventListener('change', calcularProximo);

    // Si el usuario edita la fecha manualmente, no sobreescribir
    proximoEl?.addEventListener('input', function () {
        this.dataset.manualEdit = '1';
    });

    // Al cargar: calcular solo si el campo está vacío
    if (proximoEl && !proximoEl.value) calcularProximo();
}());
</script>
@endpush
@endsection
