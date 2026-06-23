@extends('layouts.master')
@section('title', 'Proyecto de Auditoría - ' . $proyecto->nombre_cliente)
 
@section('content')
<div class="min-h-screen bg-slate-50/50 pb-16" 
     x-data="{
        tab: 'matriz',
        openEditModal: false,
        openReviewModal: false,
        openCreateActModal: false,
        openReportModal: false,
        openCommentsModal: false,
        
        // Variables para crear actividad
        createPadreId: '',
        createEsProceso: true,
        
        // Variables para reportar avance (analista)
        reportActId: '',
        reportActNombre: '',
        reportPorcentaje: 0,
        reportEstatus: 'pendiente',
        reportComentario: '',
        reportVisibleCliente: false,
        
        // Variables para comentarios
        commentsActId: '',
        commentsActNombre: '',
        commentsList: [],
        newCommentText: '',
        newCommentVisible: false,
        expandedComments: JSON.parse(localStorage.getItem('auditoria_expanded_comments_' + {{ $proyecto->id }}) || '{}'),
        
        // Filtros de la matriz
        filtroResponsable: '',
        filtroEstatus: '',
        filtroBusqueda: '',
        filtroMisActividades: false,
        currentUserId: {{ auth()->id() }},
 
        // Variables para rechazo/ajuste
        revisarCambioId: '',
        revisarAccion: '',
        openRechazoModal: false,
        motivoRechazo: '',
        
        // Estados expandidos de los procesos principales
        expandedProcesos: {},
 
        toggleProceso(id) {
            this.expandedProcesos[id] = !this.expandedProcesos[id];
        },
 
        isExpanded(id) {
            return this.expandedProcesos[id] !== false; // Abierto por defecto
        },

        toggleComments(id, hasComments = false) {
            if (this.expandedComments[id] === undefined) {
                this.expandedComments[id] = !hasComments;
            } else {
                this.expandedComments[id] = !this.expandedComments[id];
            }
            localStorage.setItem('auditoria_expanded_comments_' + {{ $proyecto->id }}, JSON.stringify(this.expandedComments));
        },

        isCommentsExpanded(id, hasComments = false) {
            if (this.expandedComments[id] === undefined) {
                return hasComments;
            }
            return this.expandedComments[id];
        },
 
        updateFase(faseNum) {
            if (!{{ $esCoordinador ? 'true' : 'false' }}) return;
            if (confirm('¿Deseas cambiar la fase actual del proyecto a la Fase ' + faseNum + '?')) {
                fetch('{{ route('auditoria.proyectos.update_fase', $proyecto->id) }}', {
                    method: 'PATCH',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ fase: faseNum })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Error al actualizar la fase.');
                    }
                });
            }
        },
 
        subirOrden(actId) {
            // Lógica de ordenación si se requiere
        },
 
        abrirReporte(id, nombre, porcentaje, estatus, comentario, visible) {
            this.reportActId = id;
            this.reportActNombre = nombre;
            this.reportPorcentaje = porcentaje;
            this.reportEstatus = estatus;
            this.reportComentario = comentario || '';
            this.reportVisibleCliente = visible || false;
            this.openReportModal = true;
        },
 
        enviarReporte(enviarFlag) {
            fetch('{{ route('auditoria.proyectos.cambios.store', $proyecto->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    actividad_id: this.reportActId,
                    porcentaje_propuesto: this.reportPorcentaje,
                    estatus_propuesto: this.reportEstatus,
                    comentario_propuesto: this.reportComentario,
                    comentario_visible_cliente: this.reportVisibleCliente,
                    enviar: enviarFlag
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error: ' + data.error);
                }
            });
        },
 
        enviarBorradorExistente(cambioId) {
            if (confirm('¿Deseas enviar este borrador de avance a revisión del coordinador?')) {
                fetch('{{ route('auditoria.proyectos.cambios.enviar', $proyecto->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({ cambio_id: cambioId })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
            }
        },

        enviarTodosBorradores() {
            if (confirm('¿Deseas enviar todos tus borradores de cambio y sugerencias a revisión del coordinador?')) {
                fetch('{{ route('auditoria.proyectos.cambios.enviar_todos', $proyecto->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({})
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Error: ' + data.error);
                    }
                });
            }
        },
 
        abrirRechazo(cambioId, accion) {
            this.revisarCambioId = cambioId;
            this.revisarAccion = accion;
            this.motivoRechazo = '';
            this.openRechazoModal = true;
        },
 
        procesarRevision(cambioId, accion) {
            if (accion !== 'aprobar' && !this.motivoRechazo.trim()) {
                alert('Es obligatorio ingresar un comentario o motivo.');
                return;
            }
            fetch('{{ route('auditoria.proyectos.cambios.revisar', $proyecto->id) }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    cambio_id: cambioId,
                    accion: accion,
                    motivo_rechazo: this.motivoRechazo
                })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.reload();
                } else {
                    alert('Error al procesar la revisión.');
                }
            });
        },
 
        aprobarTodoPaquete() {
            if (confirm('¿Estás seguro de que deseas aprobar todos los cambios pendientes de este paquete?')) {
                fetch('{{ route('auditoria.proyectos.cambios.revisar_paquete', $proyecto->id) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}'
                    },
                    body: JSON.stringify({})
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        window.location.reload();
                    } else {
                        alert('Error al aprobar el paquete.');
                    }
                });
            }
        },
 
        abrirComentarios(actId, nombre, comentariosStr) {
            this.commentsActId = actId;
            this.commentsActNombre = nombre;
            this.commentsList = JSON.parse(comentariosStr);
            this.newCommentText = '';
            this.newCommentVisible = false;
            this.openCommentsModal = true;
        }
     }">
 
    {{-- ENCABEZADO Y BADGES --}}
    <div class="bg-white border-b border-slate-200 shadow-sm mb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-6">
                <div>
                    <div class="flex flex-wrap items-center gap-2 text-xs font-bold text-indigo-600 uppercase tracking-widest mb-2">
                        <a href="{{ route('auditoria.dashboard') }}" class="hover:underline flex items-center">
                            Dashboard
                        </a>
                        <span>/</span>
                        <span>Proyecto Detalle</span>
                    </div>
                    
                    <h1 class="text-3xl font-extrabold text-slate-900 tracking-tight">{{ $proyecto->nombre_cliente }}</h1>
                    
                    <div class="flex flex-wrap items-center gap-3 mt-3">
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            Fiscal: {{ $proyecto->periodo_fiscal }}
                        </span>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-700 border border-slate-200">
                            Expedientes: {{ $proyecto->cantidad_expedientes }}
                        </span>

                        
                        @if($proyecto->ultima_publicacion_at)
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-sky-50 text-sky-700 border border-sky-200"
                                  title="Publicado por {{ $proyecto->publicador?->name }}">
                                Última Publicación: {{ $proyecto->ultima_publicacion_at->format('d/m/Y H:i') }}
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                Sin publicar al cliente
                            </span>
                        @endif
                    </div>
                </div>
 
                <div class="flex flex-wrap items-center gap-3 shrink-0">
                    {{-- Botón Vista Cliente (Previsualizar) --}}
                    <a href="{{ route('auditoria.publico.show', $proyecto->token_publico) }}" target="_blank"
                       class="inline-flex items-center justify-center px-4 py-2.5 bg-white border border-slate-200 text-slate-700 font-bold text-sm rounded-xl hover:bg-slate-50 hover:text-indigo-600 hover:border-indigo-200 shadow-sm active:scale-95 transition-all">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                        Vista Cliente
                    </a>
 
                    @if($esCoordinador)
                        {{-- Publicar Avance --}}
                        <form action="{{ route('auditoria.proyectos.publicar', $proyecto->id) }}" method="POST">
                            @csrf
                            <button type="submit" 
                                    class="inline-flex items-center justify-center px-4 py-2.5 bg-sky-600 hover:bg-sky-700 text-white font-bold text-sm rounded-xl shadow-md shadow-sky-100 active:scale-95 transition-all">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8.684 10.742l1.316 2.632 4-8M17.5 12v6.5a2.5 2.5 0 01-2.5 2.5H6a2.5 2.5 0 01-2.5-2.5V8A2.5 2.5 0 016 5.5h6"/>
                                </svg>
                                Publicar Avance
                            </button>
                        </form>
 
                        {{-- Configurar/Editar --}}
                        <button @click="openEditModal = true"
                                class="inline-flex items-center justify-center px-4 py-2.5 bg-slate-800 hover:bg-slate-900 text-white font-bold text-sm rounded-xl shadow-md active:scale-95 transition-all">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            </svg>
                            Editar Proyecto
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </div>
 
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        @if(session('success'))
            <div class="mb-6 p-4 rounded-xl border bg-emerald-50/50 border-emerald-200/80 text-emerald-800 flex items-center gap-3 animate-fade-in-up">
                <div class="p-1.5 bg-emerald-100 text-emerald-600 rounded-lg">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <span class="text-sm font-semibold">{{ session('success') }}</span>
            </div>
        @endif
 
        {{-- CARDS RESUMEN DE AVANCE --}}
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            {{-- Avance Oficial --}}
            <div class="bg-white p-6 rounded-3xl border border-slate-200/60 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Avance Aprobado (Oficial)</span>
                    <h3 class="text-3xl font-extrabold text-slate-900 mt-2">{{ round($proyecto->porcentaje_general_aprobado) }}%</h3>
                    <p class="text-xs text-slate-400 mt-1">Avance oficial registrado tras aprobaciones.</p>
                </div>
                <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden mt-4">
                    <div class="bg-indigo-600 h-full rounded-full" style="width: {{ $proyecto->porcentaje_general_aprobado }}%"></div>
                </div>
            </div>
 
            {{-- Avance Interno --}}
            <div class="bg-white p-6 rounded-3xl border border-slate-200/60 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block flex items-center gap-1.5">
                        Avance Interno (Con Borradores)
                        @if(round($proyecto->porcentaje_general_aprobado) != round($proyecto->porcentaje_general_interno))
                            <span class="w-2 h-2 bg-indigo-500 rounded-full animate-pulse"></span>
                        @endif
                    </span>
                    <h3 class="text-3xl font-extrabold text-indigo-600 mt-2">{{ round($proyecto->porcentaje_general_interno) }}%</h3>
                    <p class="text-xs text-slate-400 mt-1">Incluye cambios guardados y en revisión.</p>
                </div>
                <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden mt-4">
                    <div class="bg-indigo-500 h-full rounded-full" style="width: {{ $proyecto->porcentaje_general_interno }}%"></div>
                </div>
            </div>
 
            {{-- Avance Publicado --}}
            <div class="bg-white p-6 rounded-3xl border border-slate-200/60 shadow-sm flex flex-col justify-between">
                <div>
                    <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Avance Publicado al Cliente</span>
                    <h3 class="text-3xl font-extrabold text-sky-600 mt-2">{{ round($proyecto->porcentaje_general_publicado) }}%</h3>
                    <p class="text-xs text-slate-400 mt-1">Porcentaje visible para el cliente externo.</p>
                </div>
                <div class="w-full bg-slate-100 h-2.5 rounded-full overflow-hidden mt-4">
                    <div class="bg-sky-500 h-full rounded-full" style="width: {{ $proyecto->porcentaje_general_publicado }}%"></div>
                </div>
            </div>
        </div>
 
        {{-- ADVERTENCIA CAMBIOS SIN APROBAR --}}
        @if($tieneCambiosInternosSinAprobar)
            <div class="mb-6 p-4 rounded-xl border bg-amber-50/50 border-amber-200/80 text-amber-900 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 animate-pulse">
                <div class="flex items-center gap-3">
                    <div class="p-1.5 bg-amber-100 text-amber-600 rounded-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-sm font-bold">Cambios Internos Pendientes de Aprobación</span>
                        <p class="text-xs text-amber-700/90 mt-0.5">Existen propuestas de avance y subprocesos sugeridos que no han sido validados.</p>
                    </div>
                </div>
                @if($esCoordinador)
                    <button @click="openReviewModal = true"
                            class="px-4 py-2 bg-amber-600 hover:bg-amber-700 text-white font-bold text-xs rounded-xl shadow-md active:scale-95 transition-all w-full sm:w-auto text-center shrink-0">
                        Revisar Cambios ({{ $cambiosPendientesCount }})
                    </button>
                @endif
            </div>
        @endif

        {{-- ALERTA BORRADORES SIN ENVIAR (ANALISTA) --}}
        @if(!$esCoordinador && isset($misBorradoresTodos) && $misBorradoresTodos->isNotEmpty())
            <div class="mb-6 p-4 rounded-xl border bg-amber-50/50 border-amber-200/80 text-amber-900 flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4">
                <div class="flex items-center gap-3">
                    <div class="p-1.5 bg-amber-100 text-amber-600 rounded-lg">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                    </div>
                    <div>
                        <span class="text-sm font-bold">Borradores Guardados Pendientes de Enviar</span>
                        <p class="text-xs text-amber-700/90 mt-0.5">Tienes {{ $misBorradoresTodos->count() }} borrador(es) de cambio o sugerencias de procesos. Una vez hechos todos los cambios, mándalos a revisar.</p>
                    </div>
                </div>
                <button @click="enviarTodosBorradores()"
                        class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-xs rounded-xl shadow-md active:scale-95 transition-all w-full sm:w-auto text-center shrink-0">
                    Enviar a Revisión
                </button>
            </div>
        @endif
 
        {{-- LÍNEA VISUAL DE FASES (8 FASES) --}}
        <div class="bg-white p-6 rounded-3xl border border-slate-200/60 shadow-sm mb-8">
            <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-5">Línea de Fases del Proyecto</h3>
            
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-4 relative">
                @foreach($proyecto->fases_config as $index => $fase)
                    @php
                        $faseNum = $index + 1;
                        $isCompleted = $faseNum < $proyecto->fase_actual;
                        $isCurrent = $faseNum === $proyecto->fase_actual;
                        
                        $faseClass = $isCompleted 
                            ? 'bg-emerald-50 border-emerald-200 text-emerald-800' 
                            : ($isCurrent 
                                ? 'bg-indigo-50 border-indigo-300 text-indigo-800 ring-2 ring-indigo-300 ring-offset-2' 
                                : 'bg-slate-50 border-slate-200 text-slate-400');
                    @endphp
                    <div @click="{{ $esCoordinador ? 'updateFase(' . $faseNum . ')' : '' }}"
                         class="flex flex-col items-center p-3 rounded-2xl border text-center transition-all duration-200 {{ $faseClass }} {{ $esCoordinador ? 'cursor-pointer hover:border-indigo-400 hover:shadow-sm' : 'cursor-default' }}">
                        
                        {{-- Icono/Número --}}
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold mb-2 shadow-sm
                                    {{ $isCompleted ? 'bg-emerald-500 text-white' : ($isCurrent ? 'bg-indigo-600 text-white' : 'bg-slate-200 text-slate-600') }}">
                            @if($isCompleted)
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            @else
                                {{ $faseNum }}
                            @endif
                        </div>
                        
                        <span class="text-[10px] font-extrabold tracking-tight leading-tight line-clamp-2">{{ $fase }}</span>
                    </div>
                @endforeach
            </div>
        </div>
 
        {{-- TABS --}}
        <div class="flex border-b border-slate-200 mb-6 gap-6">
            <button @click="tab = 'matriz'"
                    class="py-3 px-1 border-b-2 font-bold text-sm transition-all flex items-center gap-2"
                    :class="tab === 'matriz' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600'">
                Matriz de Actividades
                <span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-xs font-bold border">{{ $proyecto->actividades()->count() }}</span>
            </button>
            <button @click="tab = 'bitacora'"
                    class="py-3 px-1 border-b-2 font-bold text-sm transition-all flex items-center gap-2"
                    :class="tab === 'bitacora' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600'">
                Historial y Bitácora
            </button>
        </div>
 
        {{-- CONTENIDO TABS --}}
        <div x-show="tab === 'matriz'" class="space-y-6">
            {{-- Filtros y Buscador de Actividades --}}
            <div class="bg-white p-4 rounded-2xl border border-slate-200/60 shadow-sm flex flex-col lg:flex-row items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                    {{-- Buscador --}}
                    <div class="relative w-full sm:w-64">
                        <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" x-model="filtroBusqueda" placeholder="Buscar actividad..."
                               class="pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-xl bg-slate-50/50 focus:outline-none focus:ring-2 focus:ring-indigo-300 w-full transition focus:bg-white">
                    </div>
 
                    {{-- Filtro Responsable --}}
                    <select x-model="filtroResponsable"
                            class="text-sm border border-slate-200 rounded-xl bg-slate-50/50 py-2 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:bg-white transition w-full sm:w-auto">
                        <option value="">Todos los responsables</option>
                        <option value="E&I">E&I</option>
                        <option value="{{ $proyecto->nombre_cliente }}">{{ $proyecto->nombre_cliente }}</option>
                    </select>
 
                    {{-- Filtro Estatus --}}
                    <select x-model="filtroEstatus"
                            class="text-sm border border-slate-200 rounded-xl bg-slate-50/50 py-2 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:bg-white transition w-full sm:w-auto">
                        <option value="">Todos los estatus</option>
                        <option value="pendiente">Pendiente</option>
                        <option value="en proceso">En proceso</option>
                        <option value="parcial">Parcial</option>
                        <option value="retrasado">Retrasado</option>
                        <option value="cerrado">Cerrado</option>
                    </select>
 
                    {{-- Check Mis Actividades --}}
                    <label class="inline-flex items-center cursor-pointer select-none">
                        <input type="checkbox" x-model="filtroMisActividades" class="sr-only peer">
                        <div class="relative w-9 h-5 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-indigo-600"></div>
                        <span class="ms-2 text-xs font-bold text-slate-500 uppercase tracking-wider">Mis asignaciones</span>
                    </label>
                </div>
 
                @if($esCoordinador || $proyecto->analista_id === auth()->id())
                    <button @click="createPadreId = ''; createEsProceso = true; openCreateActModal = true"
                            class="inline-flex items-center justify-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-bold text-sm rounded-xl shadow-md transition active:scale-95 w-full lg:w-auto">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                        </svg>
                        {{ $esCoordinador ? 'Agregar Proceso' : 'Sugerir Proceso' }}
                    </button>
                @endif
            </div>
 
            {{-- TABLA MATRIZ --}}
            <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold">
                                <th class="px-6 py-4 text-left text-xs uppercase tracking-wider w-10"></th>
                                <th class="px-6 py-4 text-left text-xs uppercase tracking-wider">Actividad / Proceso</th>
                                <th class="px-6 py-4 text-left text-xs uppercase tracking-wider w-44">Responsable</th>
                                <th class="px-6 py-4 text-left text-xs uppercase tracking-wider w-32">Plazo</th>
                                <th class="px-6 py-4 text-center text-xs uppercase tracking-wider w-48">Avance</th>
                                <th class="px-6 py-4 text-center text-xs uppercase tracking-wider w-32">Revisión</th>
                                <th class="px-6 py-4 text-right text-xs uppercase tracking-wider w-40">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($actividades as $proceso)
                                {{-- Fila Proceso Principal --}}
                                <tr class="bg-slate-50/40 hover:bg-slate-50 transition-colors"
                                    x-show="
                                        (!filtroResponsable || '{{ $proceso->responsable }}' == filtroResponsable) &&
                                        (!filtroEstatus || '{{ $proceso->estatus_oficial }}' == filtroEstatus) &&
                                        (!filtroBusqueda || '{{ strtolower($proceso->actividad) }}'.includes(filtroBusqueda.toLowerCase())) &&
                                        (!filtroMisActividades || ('{{ $proyecto->analista_id }}' == currentUserId && '{{ $proceso->responsable }}' == 'E&I'))
                                    ">
                                    <td class="px-6 py-4 text-center">
                                        @if($proceso->subprocesos->isNotEmpty())
                                            <button @click="toggleProceso('{{ $proceso->id }}')" class="p-1 rounded bg-slate-100 text-slate-500 hover:bg-slate-200 hover:text-slate-700 transition">
                                                <svg class="w-4 h-4 transform transition-transform" :class="isExpanded('{{ $proceso->id }}') ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                                </svg>
                                            </button>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="font-bold text-slate-800 leading-snug">{{ $proceso->actividad }}</span>
                                        @if($proceso->subprocesos->isNotEmpty())
                                            <span class="block text-[10px] text-slate-400 font-semibold mt-0.5 tracking-wide uppercase">
                                                {{ $proceso->subprocesos->count() }} {{ $proceso->subprocesos->count() === 1 ? 'subproceso' : 'subprocesos' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-slate-500 font-semibold">{{ $proceso->responsable ?? '—' }}</td>
                                    <td class="px-6 py-4 text-slate-500 font-mono text-xs">{{ $proceso->plazo ? $proceso->plazo->format('d/m/Y') : '—' }}</td>
                                    <td class="px-6 py-4 text-center">
                                        @php
                                            $procesoEstatusColors = match($proceso->estatus_oficial) {
                                                'pendiente' => 'bg-slate-100 text-slate-700',
                                                'en proceso' => 'bg-emerald-50 text-emerald-700',
                                                'parcial' => 'bg-sky-50 text-sky-700',
                                                'retrasado' => 'bg-rose-50 text-rose-700',
                                                'cerrado' => 'bg-indigo-50 text-indigo-700',
                                                default => 'bg-slate-100 text-slate-700',
                                            };
                                        @endphp
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold {{ $procesoEstatusColors }}">
                                            <span class="capitalize">{{ $proceso->estatus_oficial }}</span>
                                            <span>·</span>
                                            <span>{{ round($proceso->porcentaje_oficial) }}%</span>
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-center">
                                        @if($proceso->es_propuesta)
                                            @if($proceso->propuesta_cambio->estatus_revision === 'borrador')
                                                <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-100 text-xs font-bold">Propuesta Borrador</span>
                                            @elseif($proceso->propuesta_cambio->estatus_revision === 'pendiente')
                                                <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-100 text-xs font-bold animate-pulse">Propuesta en revisión</span>
                                            @elseif($proceso->propuesta_cambio->estatus_revision === 'ajuste_solicitado')
                                                <span class="px-2 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-100 text-xs font-bold">Ajuste Solicitado</span>
                                            @endif
                                        @else
                                            {{-- No aplica estado de revisión directa para procesos con hijos --}}
                                            @if($proceso->subprocesos->isEmpty())
                                                @if(isset($misCambiosEnRevision[$proceso->id]))
                                                    <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-100 text-xs font-bold animate-pulse">En revisión</span>
                                                @elseif(isset($misBorradores[$proceso->id]))
                                                    <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-100 text-xs font-bold">Borrador</span>
                                                @endif
                                            @endif
                                        @endif
                                    </td>
                                                                   <td class="px-6 py-4 text-right">
                                        <div class="flex justify-end items-center gap-2">
                                            @if($proceso->es_propuesta)
                                                @if($proceso->propuesta_cambio->user_id === auth()->id())
                                                    <form action="{{ route('auditoria.proyectos.cambios.destroy', [$proyecto->id, $proceso->propuesta_cambio->id]) }}" method="POST" onsubmit="return confirm('¿Cancelar esta propuesta de proceso?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition" title="Cancelar propuesta">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                @endif
                                            @else
                                                {{-- Comentarios --}}
                                                 @php $hasComments = $proceso->comentariosList->isNotEmpty() ? 'true' : 'false'; @endphp
                                                 <button @click="toggleComments('{{ $proceso->id }}', {{ $hasComments }})"
                                                        class="p-1.5 rounded-lg transition relative"
                                                        :class="isCommentsExpanded('{{ $proceso->id }}', {{ $hasComments }}) ? 'text-indigo-600 bg-indigo-50' : 'text-slate-400 hover:text-slate-600 hover:bg-slate-100'">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                                    </svg>
                                                    @if($proceso->comentariosList->isNotEmpty())
                                                        <span class="absolute top-0 right-0 w-2 h-2 bg-indigo-500 rounded-full" :class="isCommentsExpanded('{{ $proceso->id }}', {{ $hasComments }}) ? 'hidden' : ''"></span>
                                                    @endif
                                                </button>
                                                
                                                @if($esCoordinador || $proyecto->analista_id === auth()->id())
                                                    {{-- Agregar subproceso --}}
                                                    <button @click="createPadreId = '{{ $proceso->id }}'; createEsProceso = false; openCreateActModal = true"
                                                            class="p-1.5 rounded-lg text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 transition" title="{{ $esCoordinador ? 'Agregar subproceso' : 'Sugerir subproceso' }}">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                        </svg>
                                                    </button>
                                                @endif
                                                
                                                @if($esCoordinador)
                                                    {{-- Reportar (Coordinador) si no tiene subprocesos --}}
                                                    @if($proceso->subprocesos->isEmpty())
                                                        @php
                                                            $cambioRevision = $misCambiosEnRevision[$proceso->id] ?? null;
                                                            $borrador = $misBorradores[$proceso->id] ?? null;
                                                            $isLocked = !is_null($cambioRevision);
                                                        @endphp
                                                        <button @click="abrirReporte('{{ $proceso->id }}', '{{ addslashes($proceso->actividad) }}', {{ $borrador ? $borrador->porcentaje_propuesto : $proceso->porcentaje_oficial }}, '{{ $borrador ? $borrador->estatus_propuesto : $proceso->estatus_oficial }}', '{{ $borrador ? addslashes($borrador->comentario_propuesto) : '' }}', {{ $borrador && $borrador->comentario_visible_cliente ? 'true' : 'false' }})"
                                                                :disabled="{{ $isLocked ? 'true' : 'false' }}"
                                                                 class="px-2.5 py-1 bg-indigo-50 border border-indigo-200 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold disabled:opacity-50 transition active:scale-95">
                                                            {{ $borrador ? 'Editar Borrador' : 'Reportar' }}
                                                        </button>
                                                        
                                                        @if($borrador)
                                                            <button @click="enviarBorradorExistente({{ $borrador->id }})"
                                                                    class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition active:scale-95">
                                                                Enviar
                                                            </button>
                                                        @endif
                                                    @endif
                                                    
                                                    {{-- Eliminar --}}
                                                    <form action="{{ route('auditoria.proyectos.actividades.destroy', [$proyecto->id, $proceso->id]) }}" method="POST" onsubmit="return confirm('¿Eliminar esta actividad y todos sus subprocesos?');">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition">
                                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                            </svg>
                                                        </button>
                                                    </form>
                                                @else
                                                    {{-- Analista --}}
                                                    @if($proceso->subprocesos->isEmpty() && $proyecto->analista_id === auth()->id())
                                                        @php
                                                            $cambioRevision = $misCambiosEnRevision[$proceso->id] ?? null;
                                                            $borrador = $misBorradores[$proceso->id] ?? null;
                                                            $isLocked = !is_null($cambioRevision);
                                                        @endphp
                                                        <button @click="abrirReporte('{{ $proceso->id }}', '{{ addslashes($proceso->actividad) }}', {{ $borrador ? $borrador->porcentaje_propuesto : $proceso->porcentaje_oficial }}, '{{ $borrador ? $borrador->estatus_propuesto : $proceso->estatus_oficial }}', '{{ $borrador ? addslashes($borrador->comentario_propuesto) : '' }}', {{ $borrador && $borrador->comentario_visible_cliente ? 'true' : 'false' }})"
                                                                :disabled="{{ $isLocked ? 'true' : 'false' }}"
                                                                class="px-2.5 py-1 bg-indigo-50 border border-indigo-200 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold disabled:opacity-50 transition active:scale-95">
                                                            {{ $borrador ? 'Editar Borrador' : 'Reportar' }}
                                                        </button>
                                                        
                                                        @if($borrador)
                                                            <button @click="enviarBorradorExistente({{ $borrador->id }})"
                                                                    class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition active:scale-95">
                                                                Enviar
                                                            </button>
                                                        @endif
                                                    @endif
                                                @endif
                                            @endif
                                        </div>
                                    </td>
                                </tr>
 
                                {{-- Comentarios Inline del Proceso Principal --}}
                                 <tr x-show="isCommentsExpanded('{{ $proceso->id }}', {{ $hasComments }})" class="bg-indigo-50/10" x-cloak x-transition>
                                    <td colspan="6" class="px-6 py-4">
                                        <div class="pl-12 space-y-3">
                                            <h4 class="text-xs font-bold text-indigo-850 flex items-center gap-1.5 mb-2">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                                </svg>
                                                Historial de Observaciones (Proceso)
                                            </h4>
                                            @if($proceso->comentariosList->isEmpty())
                                                <p class="text-xs text-slate-400 italic">No hay comentarios ni observaciones registradas para esta actividad.</p>
                                            @else
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                    @foreach($proceso->comentariosList as $c)
                                                        <div class="bg-white border border-slate-200/60 p-3.5 rounded-2xl shadow-sm space-y-1.5">
                                                             <div class="flex justify-between items-center text-[10px] text-slate-400 font-bold border-b border-slate-50 pb-1.5">
                                                                 <span class="text-slate-600">{{ $c->autor->name }}</span>
                                                                 <div class="flex items-center gap-1.5">
                                                                     <span>{{ $c->created_at->format('d/m/Y H:i') }}</span>
                                                                     @if($c->visible_cliente)
                                                                         <span class="px-1.5 py-0.5 bg-sky-50 text-sky-600 rounded border border-sky-100 text-[8px] uppercase tracking-wider font-extrabold">Cliente</span>
                                                                     @endif
                                                                 </div>
                                                             </div>
                                                             <p class="text-xs text-slate-700 leading-relaxed">{{ $c->comentario }}</p>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
 
                                {{-- Subprocesos --}}
                                @foreach($proceso->subprocesos as $sub)
                                    <tr class="hover:bg-slate-50 transition-colors"
                                        x-show="
                                            isExpanded('{{ $proceso->id }}') &&
                                            (!filtroResponsable || '{{ $sub->responsable }}' == filtroResponsable) &&
                                            (!filtroEstatus || '{{ $sub->estatus_oficial }}' == filtroEstatus) &&
                                            (!filtroBusqueda || '{{ strtolower($sub->actividad) }}'.includes(filtroBusqueda.toLowerCase())) &&
                                            (!filtroMisActividades || ('{{ $proyecto->analista_id }}' == currentUserId && '{{ $sub->responsable }}' == 'E&I'))
                                        ">
                                        <td class="px-6 py-4"></td>
                                        <td class="px-6 py-4 text-slate-700 pl-10 flex items-center gap-2">
                                            <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                                            {{ $sub->actividad }}
                                        </td>
                                        <td class="px-6 py-4 text-slate-500 font-semibold">{{ $sub->responsable ?? '—' }}</td>
                                        <td class="px-6 py-4 text-slate-500 font-mono text-xs">{{ $sub->plazo ? $sub->plazo->format('d/m/Y') : '—' }}</td>
                                        <td class="px-6 py-4 text-center">
                                            @php
                                                $subEstatusColors = match($sub->estatus_oficial) {
                                                    'pendiente' => 'bg-slate-100 text-slate-700',
                                                    'en proceso' => 'bg-emerald-50 text-emerald-700',
                                                    'parcial' => 'bg-sky-50 text-sky-700',
                                                    'retrasado' => 'bg-rose-50 text-rose-700',
                                                    'cerrado' => 'bg-indigo-50 text-indigo-700',
                                                    default => 'bg-slate-100 text-slate-700',
                                                };
                                            @endphp
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold {{ $subEstatusColors }}">
                                                <span class="capitalize">{{ $sub->estatus_oficial }}</span>
                                                <span>·</span>
                                                <span>{{ round($sub->porcentaje_oficial) }}%</span>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            @if($sub->es_propuesta)
                                                @if($sub->propuesta_cambio->estatus_revision === 'borrador')
                                                    <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-100 text-xs font-bold">Propuesta Borrador</span>
                                                @elseif($sub->propuesta_cambio->estatus_revision === 'pendiente')
                                                    <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-100 text-xs font-bold animate-pulse">Propuesta en revisión</span>
                                                @elseif($sub->propuesta_cambio->estatus_revision === 'ajuste_solicitado')
                                                    <span class="px-2 py-0.5 rounded bg-rose-50 text-rose-700 border border-rose-100 text-xs font-bold">Ajuste Solicitado</span>
                                                @endif
                                            @else
                                                @if(isset($misCambiosEnRevision[$sub->id]))
                                                    <span class="px-2 py-0.5 rounded bg-blue-50 text-blue-700 border border-blue-100 text-xs font-bold animate-pulse">En revisión</span>
                                                @elseif(isset($misBorradores[$sub->id]))
                                                    <span class="px-2 py-0.5 rounded bg-amber-50 text-amber-700 border border-amber-100 text-xs font-bold">Borrador</span>
                                                @endif
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            <div class="flex justify-end items-center gap-2">
                                                @if($sub->es_propuesta)
                                                    @if($sub->propuesta_cambio->user_id === auth()->id())
                                                        <form action="{{ route('auditoria.proyectos.cambios.destroy', [$proyecto->id, $sub->propuesta_cambio->id]) }}" method="POST" onsubmit="return confirm('¿Cancelar esta propuesta de subproceso?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition" title="Cancelar propuesta">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    @endif
                                                @else
                                                    {{-- Comentarios --}}
                                                    @php $subHasComments = $sub->comentariosList->isNotEmpty() ? 'true' : 'false'; @endphp
                                                    <button @click="toggleComments('{{ $sub->id }}', {{ $subHasComments }})"
                                                            class="p-1.5 rounded-lg transition relative"
                                                            :class="isCommentsExpanded('{{ $sub->id }}', {{ $subHasComments }}) ? 'text-indigo-600 bg-indigo-50' : 'text-slate-400 hover:text-slate-600 hover:bg-slate-100'">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                                        </svg>
                                                        @if($sub->comentariosList->isNotEmpty())
                                                            <span class="absolute top-0 right-0 w-2 h-2 bg-indigo-500 rounded-full" :class="isCommentsExpanded('{{ $sub->id }}', {{ $subHasComments }}) ? 'hidden' : ''"></span>
                                                        @endif
                                                    </button>
                                                    
                                                    @if($esCoordinador)
                                                        {{-- Reportar (Coordinador) --}}
                                                        @php
                                                            $subCambioRevision = $misCambiosEnRevision[$sub->id] ?? null;
                                                            $subBorrador = $misBorradores[$sub->id] ?? null;
                                                            $subIsLocked = !is_null($subCambioRevision);
                                                        @endphp
                                                        <button @click="abrirReporte('{{ $sub->id }}', '{{ addslashes($sub->actividad) }}', {{ $subBorrador ? $subBorrador->porcentaje_propuesto : $sub->porcentaje_oficial }}, '{{ $subBorrador ? $subBorrador->estatus_propuesto : $sub->estatus_oficial }}', '{{ $subBorrador ? addslashes($subBorrador->comentario_propuesto) : '' }}', {{ $subBorrador && $subBorrador->comentario_visible_cliente ? 'true' : 'false' }})"
                                                                :disabled="{{ $subIsLocked ? 'true' : 'false' }}"
                                                                class="px-2.5 py-1 bg-indigo-50 border border-indigo-200 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold disabled:opacity-50 transition active:scale-95">
                                                            {{ $subBorrador ? 'Editar Borrador' : 'Reportar' }}
                                                        </button>
                                                        
                                                        @if($subBorrador)
                                                            <button @click="enviarBorradorExistente({{ $subBorrador->id }})"
                                                                    class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition active:scale-95">
                                                                Enviar
                                                            </button>
                                                        @endif

                                                        {{-- Eliminar --}}
                                                        <form action="{{ route('auditoria.proyectos.actividades.destroy', [$proyecto->id, $sub->id]) }}" method="POST" onsubmit="return confirm('¿Eliminar este subproceso?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="p-1.5 rounded-lg text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition">
                                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                                                </svg>
                                                            </button>
                                                        </form>
                                                    @else
                                                        {{-- Analista --}}
                                                        @if($proyecto->analista_id === auth()->id())
                                                            @php
                                                                $subCambioRevision = $misCambiosEnRevision[$sub->id] ?? null;
                                                                $subBorrador = $misBorradores[$sub->id] ?? null;
                                                                $subIsLocked = !is_null($subCambioRevision);
                                                            @endphp
                                                            <button @click="abrirReporte('{{ $sub->id }}', '{{ addslashes($sub->actividad) }}', {{ $subBorrador ? $subBorrador->porcentaje_propuesto : $sub->porcentaje_oficial }}, '{{ $subBorrador ? $subBorrador->estatus_propuesto : $sub->estatus_oficial }}', '{{ $subBorrador ? addslashes($subBorrador->comentario_propuesto) : '' }}', {{ $subBorrador && $subBorrador->comentario_visible_cliente ? 'true' : 'false' }})"
                                                                    :disabled="{{ $subIsLocked ? 'true' : 'false' }}"
                                                                    class="px-2.5 py-1 bg-indigo-50 border border-indigo-200 hover:bg-indigo-100 text-indigo-700 rounded-lg text-xs font-bold disabled:opacity-50 transition active:scale-95">
                                                                {{ $subBorrador ? 'Editar Borrador' : 'Reportar' }}
                                                            </button>
                                                            
                                                            @if($subBorrador)
                                                                <button @click="enviarBorradorExistente({{ $subBorrador->id }})"
                                                                        class="px-2.5 py-1 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-bold transition active:scale-95">
                                                                Enviar
                                                                </button>
                                                            @endif
                                                        @endif
                                                    @endif
                                                @endif
                                            </div>
                                        </td>
                                    </tr>

                                    {{-- Comentarios Inline del Subproceso --}}
                                     <tr x-show="isExpanded('{{ $proceso->id }}') && isCommentsExpanded('{{ $sub->id }}', {{ $subHasComments }})" class="bg-indigo-50/5" x-cloak x-transition>
                                        <td class="px-6 py-4"></td>
                                        <td colspan="5" class="px-6 py-4">
                                            <div class="space-y-3">
                                                <h5 class="text-xs font-bold text-indigo-850 flex items-center gap-1.5 mb-2">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                                    </svg>
                                                    Historial de Observaciones (Subproceso)
                                                </h5>
                                                @if($sub->comentariosList->isEmpty())
                                                    <p class="text-xs text-slate-400 italic">No hay comentarios ni observaciones registradas para este subproceso.</p>
                                                @else
                                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                                        @foreach($sub->comentariosList as $c)
                                                            <div class="bg-white border border-slate-200/50 p-3.5 rounded-2xl shadow-sm space-y-1.5">
                                                                 <div class="flex justify-between items-center text-[10px] text-slate-400 font-bold border-b border-slate-50 pb-1.5">
                                                                     <span class="text-slate-600">{{ $c->autor->name }}</span>
                                                                     <div class="flex items-center gap-1.5">
                                                                         <span>{{ $c->created_at->format('d/m/Y H:i') }}</span>
                                                                         @if($c->visible_cliente)
                                                                             <span class="px-1.5 py-0.5 bg-sky-50 text-sky-600 rounded border border-sky-100 text-[8px] uppercase tracking-wider font-extrabold">Cliente</span>
                                                                         @endif
                                                                     </div>
                                                                 </div>
                                                                 <p class="text-xs text-slate-700 leading-relaxed">{{ $c->comentario }}</p>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
 
        {{-- BITÁCORA / HISTORIAL --}}
        <div x-show="tab === 'bitacora'" class="space-y-6">
            <div class="bg-white p-6 rounded-3xl border border-slate-200/60 shadow-sm">
                <h3 class="text-lg font-bold text-slate-900 mb-6">Bitácora de Trazabilidad del Proyecto</h3>
                
                @if($bitacora->isEmpty())
                    <p class="text-slate-400 text-sm text-center py-8">No se han registrado modificaciones aún.</p>
                @else
                    <div class="flow-root">
                        <ul role="list" class="-mb-8">
                            @foreach($bitacora as $index => $log)
                                <li>
                                    <div class="relative pb-8">
                                        @if($index !== $bitacora->count() - 1)
                                            <span class="absolute top-4 left-4 -ml-px h-full w-0.5 bg-slate-200" aria-hidden="true"></span>
                                        @endif
                                        <div class="relative flex space-x-3">
                                            <div>
                                                <span class="h-8 w-8 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-slate-500 text-xs font-semibold">
                                                    {{ $log->usuario->name[0] }}
                                                </span>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <div class="text-sm text-slate-800">
                                                    <span class="font-bold text-slate-900">{{ $log->usuario->name }}</span>
                                                    realizó la acción: <span class="font-bold text-indigo-600 uppercase tracking-wide text-[10px] bg-indigo-50 border border-indigo-100 rounded px-1.5 py-0.5">{{ str_replace('_', ' ', $log->accion) }}</span>
                                                    @if($log->actividad)
                                                        en la actividad <span class="font-semibold text-slate-700">"{{ $log->actividad->actividad }}"</span>
                                                    @endif
                                                </div>
                                                @if($log->comentario || ($log->valor_anterior !== null || $log->valor_nuevo !== null))
                                                    <div class="text-xs text-slate-500 mt-1 bg-slate-50 p-2 rounded-xl border border-slate-100 max-w-2xl">
                                                        @if($log->valor_anterior !== null || $log->valor_nuevo !== null)
                                                            <div class="font-mono text-[10px] mb-1">
                                                                Valor anterior: <span class="text-slate-400">{{ $log->valor_anterior }}</span> |
                                                                Propuesto/Nuevo: <span class="text-slate-700 font-bold">{{ $log->valor_nuevo }}</span>
                                                            </div>
                                                        @endif
                                                        @if($log->comentario)
                                                            <p class="italic">"{{ $log->comentario }}"</p>
                                                        @endif
                                                    </div>
                                                @endif
                                                <p class="text-[10px] text-slate-400 mt-1.5">{{ $log->created_at->format('d/m/Y H:i:s') }}</p>
                                            </div>
                                        </div>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
 
    {{-- MODAL: EDITAR DATOS GENERALES (COORDINADOR) --}}
    @if($esCoordinador)
        <div id="modal-edit" 
             x-show="openEditModal" 
             class="fixed inset-0 z-50 overflow-y-auto" 
             x-cloak>
            <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="openEditModal = false"></div>
 
                <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg animate-scale-up"
                     x-show="openEditModal">
                    
                    <form action="{{ route('auditoria.proyectos.update', $proyecto->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        <div class="bg-white px-6 py-6 border-b border-slate-100 flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-bold text-slate-900">Configurar Proyecto</h3>
                                <p class="text-xs text-slate-400 mt-0.5">Modifica los detalles generales del proyecto y el acceso público.</p>
                            </div>
                            <button type="button" @click="openEditModal = false" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
 
                        <div class="bg-white px-6 py-6 space-y-4">
                            <div>
                                <label for="edit_cliente_nombre" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Cliente</label>
                                <input type="text" name="cliente_nombre" id="edit_cliente_nombre" required value="{{ $proyecto->nombre_cliente }}"
                                       class="w-full text-sm border border-slate-200 rounded-xl bg-slate-50/50 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:bg-white transition">
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="edit_periodo" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Periodo Fiscal</label>
                                    <input type="text" name="periodo_fiscal" id="edit_periodo" required value="{{ $proyecto->periodo_fiscal }}"
                                           class="w-full text-sm border border-slate-200 rounded-xl bg-slate-50/50 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:bg-white transition">
                                </div>
                                <div>
                                    <label for="edit_expedientes" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Cantidad Expedientes</label>
                                    <input type="number" name="cantidad_expedientes" id="edit_expedientes" required min="1" value="{{ $proyecto->cantidad_expedientes }}"
                                           class="w-full text-sm border border-slate-200 rounded-xl bg-slate-50/50 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:bg-white transition">
                                </div>
                            </div>
 
                            <div>
                                <label for="edit_analista" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Analista Responsable</label>
                                <select name="analista_id" id="edit_analista" required
                                        class="w-full text-sm border border-slate-200 rounded-xl bg-slate-50/50 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:bg-white transition">
                                    @foreach($analistas as $a)
                                        <option value="{{ $a->id }}" {{ $proyecto->analista_id == $a->id ? 'selected' : '' }}>{{ $a->name }}</option>
                                    @endforeach
                                </select>
                            </div>
 
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label for="edit_fecha_inicio" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Fecha Inicio</label>
                                    <input type="date" name="fecha_inicio" id="edit_fecha_inicio" required value="{{ $proyecto->fecha_inicio->format('Y-m-d') }}"
                                           class="w-full text-sm border border-slate-200 rounded-xl bg-slate-50/50 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:bg-white transition">
                                </div>
                                <div>
                                    <label for="edit_fecha_entrega" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Entrega Estimada</label>
                                    <input type="date" name="fecha_entrega_estimada" id="edit_fecha_entrega" required value="{{ $proyecto->fecha_entrega_estimada->format('Y-m-d') }}"
                                           class="w-full text-sm border border-slate-200 rounded-xl bg-slate-50/50 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:bg-white transition">
                                </div>
                            </div>
 
                            <div>
                                <label for="edit_expira" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Expiración Link Público</label>
                                <input type="date" name="publico_expira_at" id="edit_expira" value="{{ $proyecto->publico_expira_at ? $proyecto->publico_expira_at->format('Y-m-d') : '' }}"
                                       class="w-full text-sm border border-slate-200 rounded-xl bg-slate-50/50 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:bg-white transition">
                            </div>
 
                            <div>
                                <label for="edit_password" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Contraseña Link Público (Opcional)</label>
                                <input type="password" name="publico_password" id="edit_password" placeholder="Ingresar contraseña para proteger..."
                                       class="w-full text-sm border border-slate-200 rounded-xl bg-slate-50/50 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:bg-white transition">
                                @if(!empty($proyecto->publico_password))
                                    <div class="mt-2 flex items-center gap-2">
                                        <input type="checkbox" name="remove_password" id="remove_password" value="1" class="rounded text-indigo-600 focus:ring-indigo-500">
                                        <label for="remove_password" class="text-xs text-slate-500 font-semibold">Remover protección por contraseña existente</label>
                                    </div>
                                @endif
                            </div>
 
                            <div class="flex items-center gap-2 pt-2">
                                <input type="checkbox" name="mostrar_detalle_cliente" id="edit_mostrar_detalle" value="1" {{ $proyecto->mostrar_detalle_cliente ? 'checked' : '' }} class="rounded text-indigo-600 focus:ring-indigo-500">
                                <label for="edit_mostrar_detalle" class="text-xs font-bold text-slate-500 uppercase tracking-wider">Permitir al cliente ver el detalle de la matriz</label>
                            </div>
                        </div>
 
                        <div class="bg-slate-50 px-6 py-4 flex items-center justify-between border-t border-slate-100 rounded-b-3xl">
                            <button type="button" 
                                    onclick="if(confirm('¿Estás seguro de que deseas eliminar permanentemente este proyecto de auditoría? Esta acción no se puede deshacer y borrará toda la matriz y avances relacionados.')) { document.getElementById('delete-project-form').submit(); }"
                                    class="px-4 py-2.5 text-sm font-semibold text-white bg-rose-600 hover:bg-rose-700 rounded-xl transition shadow-md active:scale-95">
                                Eliminar Proyecto
                            </button>

                            <div class="flex items-center gap-3">
                                <button type="button" @click="openEditModal = false"
                                        class="px-4 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition">
                                    Cancelar
                                </button>
                                <button type="submit"
                                        class="px-5 py-2.5 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-md transition active:scale-95">
                                    Guardar Cambios
                                </button>
                            </div>
                        </div>
                    </form>

                    <form id="delete-project-form" action="{{ route('auditoria.proyectos.destroy', $proyecto->id) }}" method="POST" class="hidden">
                        @csrf
                        @method('DELETE')
                    </form>
                </div>
            </div>
        </div>
    @endif
 
    {{-- MODAL: BANDERA DE REVISIÓN DE CAMBIOS (COORDINADOR) --}}
    @if($esCoordinador)
        <div id="modal-review" 
             x-show="openReviewModal" 
             class="fixed inset-0 z-50 overflow-y-auto" 
             x-cloak>
            <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="openReviewModal = false"></div>
 
                <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-4xl animate-scale-up"
                     x-show="openReviewModal">
                    
                    <div class="bg-white px-6 py-6 border-b border-slate-200 flex items-center justify-between">
                        <div>
                            <h3 class="text-xl font-bold text-slate-900">Bandeja de Cambios Pendientes</h3>
                            <p class="text-xs text-slate-400 mt-0.5">Analiza y aprueba o rechaza los avances propuestos por los analistas.</p>
                        </div>
                        <div class="flex items-center gap-3">
                            <button type="button" @click="aprobarTodoPaquete()"
                                    class="px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md active:scale-95 transition-all">
                                Aprobar Todo el Paquete
                            </button>
                            <button type="button" @click="openReviewModal = false" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                            </button>
                        </div>
                    </div>
 
                    <div class="bg-white px-6 py-6 space-y-6 max-height-[60vh] overflow-y-auto" style="max-height: 60vh">
                        @if($cambiosPendientes->isEmpty())
                            <p class="text-center text-slate-400 text-sm py-12">No hay cambios pendientes de revisión.</p>
                        @else
                            <div class="space-y-4">
                                @foreach($cambiosPendientes as $cambio)
                                    <div class="bg-slate-50 border border-slate-200 rounded-2xl p-5 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
                                        <div class="flex-1 space-y-2">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span class="text-xs font-bold text-indigo-600 uppercase bg-indigo-50 border border-indigo-100 rounded-md px-2 py-0.5">
                                                    {{ $cambio->tipo_cambio === 'create_subprocess' ? 'Nuevo Subproceso' : 'Avance Propuesto' }}
                                                </span>
                                                <span class="text-xs text-slate-400">Enviado por: <strong class="text-slate-700">{{ $cambio->proponente->name }}</strong></span>
                                                <span class="text-xs text-slate-400">Fecha: {{ $cambio->created_at->format('d/m/Y H:i') }}</span>
                                            </div>
                                            
                                            <h4 class="text-sm font-bold text-slate-800">
                                                @if($cambio->tipo_cambio === 'create_subprocess')
                                                    Sugerencia: <span class="italic text-indigo-700">"{{ $cambio->actividad_nombre_propuesto }}"</span> bajo el proceso principal <strong class="text-slate-800">"{{ $cambio->padre?->actividad ?? 'Proceso eliminado' }}"</strong>
                                                @else
                                                    Actividad: <strong class="text-slate-800">"{{ $cambio->actividad?->actividad ?? 'Actividad eliminada' }}"</strong>
                                                @endif
                                            </h4>
                                            
                                            @if($cambio->tipo_cambio === 'update_activity')
                                                <div class="flex items-center gap-3 text-xs font-bold font-mono">
                                                    <span class="text-slate-400">Actual: {{ $cambio->actividad?->porcentaje_oficial ?? '?' }}% ({{ $cambio->actividad?->estatus_oficial ?? '—' }})</span>
                                                    <span class="text-slate-400">→</span>
                                                    <span class="text-indigo-600">Propuesto: {{ $cambio->porcentaje_propuesto }}% ({{ $cambio->estatus_propuesto }})</span>
                                                </div>
                                            @endif
                                            
                                            @if($cambio->tipo_cambio !== 'create_subprocess' && $cambio->comentario_propuesto)
                                                <p class="text-xs text-slate-600 italic bg-white border border-slate-100 p-2.5 rounded-xl">
                                                    "{{ $cambio->comentario_propuesto }}"
                                                </p>
                                            @endif
                                            
                                            @if($cambio->comentario_visible_cliente)
                                                <span class="inline-flex items-center gap-1 text-[10px] font-bold text-sky-600 uppercase tracking-wider">
                                                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M10 12a2 2 0 100-4 2 2 0 000 4z"/>
                                                        <path fill-rule="evenodd" d="M.458 10C1.732 5.943 5.522 3 10 3s8.268 2.943 9.542 7c-1.274 4.057-5.064 7-9.542 7S1.732 14.057.458 10zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd"/>
                                                    </svg>
                                                    Visible para cliente al aprobar
                                                </span>
                                            @endif
                                        </div>
                                        
                                        <div class="flex md:flex-col gap-2 w-full md:w-auto shrink-0">
                                            <button @click="procesarRevision({{ $cambio->id }}, 'aprobar')"
                                                    class="flex-1 md:w-32 py-2 bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-xs rounded-xl shadow-md transition active:scale-95">
                                                Aprobar
                                            </button>
                                            <button @click="abrirRechazo({{ $cambio->id }}, 'ajuste')"
                                                    class="flex-1 md:w-32 py-2 bg-amber-500 hover:bg-amber-600 text-white font-bold text-xs rounded-xl shadow-md transition active:scale-95">
                                                Solicitar Ajuste
                                            </button>
                                            <button @click="abrirRechazo({{ $cambio->id }}, 'rechazar')"
                                                    class="flex-1 md:w-32 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs rounded-xl shadow-md transition active:scale-95">
                                                Rechazar
                                            </button>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
 
                    <div class="bg-slate-50 px-6 py-4 flex justify-end border-t border-slate-100 rounded-b-3xl">
                        <button type="button" @click="openReviewModal = false"
                                class="px-4 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition">
                            Cerrar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
 
    {{-- MODAL: RECHAZO / AJUSTE (COMENTARIO OBLIGATORIO) --}}
    @if($esCoordinador)
        <div id="modal-rechazo" 
             x-show="openRechazoModal" 
             class="fixed inset-0 z-50 overflow-y-auto" 
             x-cloak>
            <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="openRechazoModal = false"></div>
 
                <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-md animate-scale-up"
                     x-show="openRechazoModal">
                    
                    <div class="bg-white px-6 py-6 border-b border-slate-100">
                        <h3 class="text-lg font-bold text-slate-900" 
                            x-text="revisarAccion === 'rechazar' ? 'Confirmar Rechazo' : 'Solicitar Ajustes'"></h3>
                        <p class="text-xs text-slate-400 mt-0.5">Ingresa el motivo obligatorio para notificar al analista.</p>
                    </div>
 
                    <div class="bg-white px-6 py-6 space-y-4">
                        <div>
                            <label for="motivo_rechazo" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Comentario / Motivo</label>
                            <textarea id="motivo_rechazo" x-model="motivoRechazo" required rows="4" placeholder="Especificar el porqué del rechazo o detallar los ajustes requeridos..."
                                      class="w-full text-sm border border-slate-200 rounded-xl bg-slate-50/50 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:bg-white transition"></textarea>
                        </div>
                    </div>
 
                    <div class="bg-slate-50 px-6 py-4 flex justify-end gap-3 border-t border-slate-100 rounded-b-3xl">
                        <button type="button" @click="openRechazoModal = false"
                                class="px-4 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition">
                            Cancelar
                        </button>
                        <button type="button" @click="procesarRevision(revisarCambioId, revisarAccion)"
                                class="px-5 py-2.5 text-sm font-bold text-white rounded-xl shadow-md transition active:scale-95"
                                :class="revisarAccion === 'rechazar' ? 'bg-rose-600 hover:bg-rose-700 shadow-rose-100' : 'bg-amber-500 hover:bg-amber-600 shadow-amber-100'">
                            Enviar Respuesta
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
 
    {{-- MODAL: REPORTAR AVANCE (COORDINADOR/ANALISTA) --}}
    <div id="modal-report" 
         x-show="openReportModal" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         x-cloak>
        <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="openReportModal = false"></div>

            <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg animate-scale-up"
                 x-show="openReportModal">
                
                <div class="bg-white px-6 py-6 border-b border-slate-100">
                    <h3 class="text-lg font-bold text-slate-900" x-text="'Reportar Avance: ' + reportActNombre"></h3>
                    <p class="text-xs text-slate-400 mt-0.5">Configura el progreso y agrega un comentario justificativo.</p>
                </div>

                <div class="bg-white px-6 py-6 space-y-4">
                    {{-- Slider de Porcentaje --}}
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label for="porcentaje_range" class="block text-xs font-bold text-slate-500 uppercase tracking-wider">Porcentaje</label>
                            <span class="text-sm font-extrabold text-indigo-600" x-text="reportPorcentaje + '%'"></span>
                        </div>
                        <input type="range" id="porcentaje_range" x-model="reportPorcentaje" min="0" max="100" step="5"
                               class="w-full h-2 bg-slate-100 rounded-lg appearance-none cursor-pointer accent-indigo-600">
                    </div>



                    {{-- Comentario Justificativo --}}
                    <div>
                        <label for="report_comentario" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Comentario de Justificación (Opcional)</label>
                        <textarea id="report_comentario" x-model="reportComentario" rows="4" placeholder="Explica detalladamente qué actividades realizaste, plazos cumplidos, o justificaciones de retraso..."
                                  class="w-full text-sm border border-slate-200 rounded-xl bg-slate-50/50 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:bg-white transition"></textarea>
                    </div>

                    {{-- Checkbox visible cliente --}}
                    <div class="flex items-center gap-2 pt-2">
                        <input type="checkbox" id="report_visible" x-model="reportVisibleCliente" class="rounded text-indigo-600 focus:ring-indigo-500">
                        <label for="report_visible" class="text-xs font-bold text-slate-500 uppercase tracking-wider cursor-pointer select-none">
                            {{ $esCoordinador ? 'Hacer este comentario visible al cliente' : 'Hacer este comentario visible al cliente al ser aprobado' }}
                        </label>
                    </div>
                </div>

                <div class="bg-slate-50 px-6 py-4 flex justify-between gap-3 border-t border-slate-100 rounded-b-3xl">
                    <button type="button" @click="openReportModal = false"
                            class="px-4 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition">
                        Cancelar
                    </button>
                    <div class="flex gap-2">
                        <button type="button" @click="enviarReporte(false)"
                                class="px-4 py-2.5 text-sm font-semibold text-slate-700 bg-slate-200 hover:bg-slate-300 rounded-xl transition active:scale-95">
                            Guardar Borrador
                        </button>
                        <button type="button" @click="enviarReporte(true)"
                                class="px-5 py-2.5 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-md transition active:scale-95">
                            {{ $esCoordinador ? 'Aplicar Avance' : 'Enviar a Revisión' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
 
    {{-- MODAL: AGREGAR ACTIVIDAD / SUBPROCESO (COORDINADOR O ANALISTA) --}}
    @if($esCoordinador || $proyecto->analista_id === auth()->id())
        <div id="modal-create-act" 
             x-show="openCreateActModal" 
             class="fixed inset-0 z-50 overflow-y-auto" 
             x-cloak>
            <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
                <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="openCreateActModal = false"></div>
 
                <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg animate-scale-up"
                     x-show="openCreateActModal">
                    
                    <form action="{{ route('auditoria.proyectos.actividades.store', $proyecto->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="padre_id" x-model="createPadreId">
                        
                        <div class="bg-white px-6 py-6 border-b border-slate-100">
                            <h3 class="text-xl font-bold text-slate-900" x-text="createPadreId ? '{{ $esCoordinador ? 'Agregar Subproceso' : 'Sugerir Subproceso' }}' : '{{ $esCoordinador ? 'Agregar Proceso Principal' : 'Sugerir Proceso Principal' }}'"></h3>
                            <p class="text-xs text-slate-400 mt-0.5">{{ $esCoordinador ? 'Ingresa los detalles oficiales de la actividad.' : 'Ingresa los detalles de tu sugerencia. Se guardará como borrador y deberás enviarla a revisión.' }}</p>
                        </div>
 
                        <div class="bg-white px-6 py-6 space-y-4">
                            <div>
                                <label for="new_actividad" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Nombre de Actividad</label>
                                <input type="text" name="actividad" id="new_actividad" required placeholder="Ej: Revisión de Glosa del Pedimento"
                                       class="w-full text-sm border border-slate-200 rounded-xl bg-slate-50/50 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:bg-white transition">
                            </div>
 
                            <div>
                                <label for="new_responsable" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Responsable Asignado</label>
                                <select name="responsable" id="new_responsable" required
                                        class="w-full text-sm border border-slate-200 rounded-xl bg-slate-50/50 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:bg-white transition">
                                    <option value="E&I">E&I</option>
                                    <option value="{{ $proyecto->nombre_cliente }}">{{ $proyecto->nombre_cliente }}</option>
                                </select>
                            </div>
 
                            <div>
                                <label for="new_plazo" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Fecha Límite / Plazo</label>
                                <input type="date" name="plazo" id="new_plazo" value="{{ $proyecto->fecha_entrega_estimada->format('Y-m-d') }}"
                                       class="w-full text-sm border border-slate-200 rounded-xl bg-slate-50/50 py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-indigo-300 focus:bg-white transition">
                            </div>
                        </div>
 
                        <div class="bg-slate-50 px-6 py-4 flex justify-end gap-3 border-t border-slate-100 rounded-b-3xl">
                            <button type="button" @click="openCreateActModal = false"
                                    class="px-4 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition">
                                Cancelar
                            </button>
                            <button type="submit"
                                    class="px-5 py-2.5 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl shadow-md transition active:scale-95">
                                {{ $esCoordinador ? 'Agregar Actividad' : 'Sugerir Actividad' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
 
    {{-- MODAL: COMENTARIOS Y LOGS POR ACTIVIDAD --}}
    <div id="modal-comments" 
         x-show="openCommentsModal" 
         class="fixed inset-0 z-50 overflow-y-auto" 
         x-cloak>
        <div class="flex min-h-screen items-center justify-center p-4 text-center sm:p-0">
            <div class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm transition-opacity" @click="openCommentsModal = false"></div>
 
            <div class="relative transform overflow-hidden rounded-3xl bg-white text-left shadow-2xl transition-all sm:my-8 sm:w-full sm:max-w-lg animate-scale-up"
                 x-show="openCommentsModal">
                
                <div class="bg-white px-6 py-6 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-slate-900">Comentarios e Historial</h3>
                        <p class="text-xs text-slate-400 mt-0.5 x-text" x-text="commentsActNombre"></p>
                    </div>
                    <button type="button" @click="openCommentsModal = false" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
 
                <div class="bg-white px-6 py-6 space-y-4 max-height-[40vh] overflow-y-auto" style="max-height: 40vh">
                    <template x-if="commentsList.length === 0">
                        <p class="text-center text-slate-400 text-sm py-8">No hay comentarios en esta actividad.</p>
                    </template>
                    <div class="space-y-3">
                        <template x-for="c in commentsList" :key="c.id">
                            <div class="bg-slate-50 border border-slate-100 p-3 rounded-2xl space-y-1">
                                <div class="flex justify-between items-center text-[10px] text-slate-400 font-bold">
                                    <span x-text="c.autor"></span>
                                    <div class="flex items-center gap-2">
                                        <span x-text="c.fecha"></span>
                                        <template x-if="c.visible">
                                            <span class="px-1 py-0.2 bg-sky-50 text-sky-600 rounded border border-sky-100 text-[8px] uppercase tracking-wider">Cliente</span>
                                        </template>
                                    </div>
                                </div>
                                <p class="text-xs text-slate-700" x-text="c.comentario"></p>
                            </div>
                        </template>
                    </div>
                </div>
 
                <div class="bg-slate-50 px-6 py-4 flex justify-end border-t border-slate-100 rounded-b-3xl">
                    <button type="button" @click="openCommentsModal = false"
                            class="px-4 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 hover:bg-slate-50 rounded-xl transition">
                        Cerrar
                    </button>
                </div>
            </div>
        </div>
    </div>
 
</div>
@endsection
