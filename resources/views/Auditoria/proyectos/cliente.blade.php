<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Seguimiento de Auditoría — {{ $proyecto->cliente->nombre }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,850&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50/50 text-slate-600 min-h-screen flex flex-col justify-between"
     x-data="{
        openCommentsModal: false,
        commentsActNombre: '',
        commentsList: [],
        expandedProcesos: {},
 
        toggleProceso(id) {
            this.expandedProcesos[id] = !this.expandedProcesos[id];
        },
 
        isExpanded(id) {
            return this.expandedProcesos[id] !== false; // Abierto por defecto
        },
 
        abrirComentarios(nombre, comentariosStr) {
            this.commentsActNombre = nombre;
            this.commentsList = JSON.parse(comentariosStr);
            this.openCommentsModal = true;
        }
     }">
 
    {{-- Fondo Decorativo --}}
    <div class="absolute inset-0 pointer-events-none overflow-hidden z-0">
        <div class="absolute -top-24 -right-24 w-96 h-96 bg-indigo-50/40 blur-3xl rounded-full mix-blend-multiply"></div>
        <div class="absolute top-24 -left-24 w-72 h-72 bg-sky-50/40 blur-3xl rounded-full mix-blend-multiply"></div>
    </div>
 
    {{-- CONTENEDOR PRINCIPAL --}}
    <div class="relative z-10 flex-grow pb-16">
        {{-- Encabezado --}}
        <div class="bg-white border-b border-slate-200 shadow-sm mb-8">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col sm:flex-row justify-between items-center gap-4">
                <div class="flex items-center gap-4">
                    <img src="{{ asset('images/logo-ei.png') }}" alt="E&I Logo" class="h-10 w-auto">
                    <div class="border-l border-slate-200 pl-4">
                        <span class="text-[10px] font-extrabold uppercase tracking-widest text-indigo-600">Seguimiento de Auditoría</span>
                        <h1 class="text-xl font-black text-slate-800 tracking-tight">{{ $proyecto->cliente->nombre }}</h1>
                    </div>
                </div>
                
                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-bold bg-indigo-50 text-indigo-700 border border-indigo-200">
                    <span class="w-2 h-2 bg-indigo-500 rounded-full animate-pulse"></span>
                    Portal Cliente
                </span>
            </div>
        </div>
 
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            {{-- Cards de Información --}}
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                {{-- Card Avance --}}
                <div class="bg-white p-6 rounded-3xl border border-slate-200/60 shadow-sm flex flex-col justify-between md:col-span-2">
                    <div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Progreso de la Auditoría</span>
                        <h3 class="text-4xl font-black text-indigo-600 mt-2">{{ round($proyecto->porcentaje_general_publicado) }}%</h3>
                        <p class="text-xs text-slate-400 mt-1">Avance global publicado y verificado.</p>
                    </div>
                    <div class="w-full bg-slate-100 h-3 rounded-full overflow-hidden mt-4">
                        <div class="bg-indigo-600 h-full rounded-full" style="width: {{ $proyecto->porcentaje_general_publicado }}%"></div>
                    </div>
                </div>
 
                {{-- Card Fiscal y Expedientes --}}
                <div class="bg-white p-6 rounded-3xl border border-slate-200/60 shadow-sm flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Ficha Técnica</span>
                        <div class="mt-3 space-y-2 text-sm">
                            <div class="flex justify-between border-b border-slate-50 pb-1.5">
                                <span class="text-slate-400">Periodo Fiscal:</span>
                                <strong class="text-slate-800 font-extrabold">{{ $proyecto->periodo_fiscal }}</strong>
                            </div>
                            <div class="flex justify-between border-b border-slate-50 pb-1.5">
                                <span class="text-slate-400">Expedientes:</span>
                                <strong class="text-slate-800 font-extrabold">{{ $proyecto->cantidad_expedientes }}</strong>
                            </div>
                        </div>
                    </div>
                    <p class="text-[10px] text-slate-400">Fecha inicio: {{ $proyecto->fecha_inicio->format('d/m/Y') }}</p>
                </div>
 
                {{-- Card Estatus y Entrega --}}
                <div class="bg-white p-6 rounded-3xl border border-slate-200/60 shadow-sm flex flex-col justify-between">
                    <div>
                        <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Estatus General</span>
                        
                        @php
                            $statusColors = match($proyecto->estatus_general) {
                                'pendiente' => 'bg-slate-100 text-slate-700 border-slate-200',
                                'en proceso' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
                                'retrasado' => 'bg-rose-50 text-rose-700 border-rose-200',
                                'cerrado' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
                                default => 'bg-slate-100 text-slate-700 border-slate-200',
                            };
                        @endphp
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 mt-3 rounded-full text-xs font-extrabold border capitalize {{ $statusColors }}">
                            {{ $proyecto->estatus_general }}
                        </span>
                    </div>
                    <div>
                        <p class="text-[10px] text-slate-400">Entrega Estimada:</p>
                        <p class="text-sm font-bold text-slate-800 mt-0.5">{{ $proyecto->fecha_entrega_estimada->format('d/m/Y') }}</p>
                    </div>
                </div>
            </div>
 
            {{-- LÍNEA DE FASES (8 FASES) --}}
            <div class="bg-white p-6 rounded-3xl border border-slate-200/60 shadow-sm">
                <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider mb-5">Fases de la Auditoría</h3>
                
                <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-8 gap-4">
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
                        <div class="flex flex-col items-center p-3 rounded-2xl border text-center transition-all duration-200 {{ $faseClass }}">
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
 
            {{-- DETALLE DE LA MATRIZ --}}
            @if($proyecto->mostrar_detalle_cliente)
                <div class="bg-white rounded-3xl border border-slate-200/60 shadow-sm overflow-hidden">
                    <div class="px-6 py-5 border-b border-slate-100">
                        <h3 class="text-sm font-bold text-slate-500 uppercase tracking-wider">Detalle del Proceso de Auditoría</h3>
                    </div>
                    
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-slate-500 font-bold">
                                    <th class="px-6 py-4 text-left text-xs uppercase tracking-wider w-10"></th>
                                    <th class="px-6 py-4 text-left text-xs uppercase tracking-wider">Actividad / Proceso</th>
                                    <th class="px-6 py-4 text-left text-xs uppercase tracking-wider w-44">Responsable</th>
                                    <th class="px-6 py-4 text-left text-xs uppercase tracking-wider w-32">Plazo</th>
                                    <th class="px-6 py-4 text-center text-xs uppercase tracking-wider w-48">Avance</th>
                                    <th class="px-6 py-4 text-right text-xs uppercase tracking-wider w-24">Notas</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100">
                                @foreach($actividades as $proceso)
                                    {{-- Proceso Principal --}}
                                    <tr class="bg-slate-50/40 hover:bg-slate-50/80 transition-colors">
                                        <td class="px-6 py-4 text-center">
                                            @if($proceso->subprocesos->isNotEmpty())
                                                <button @click="toggleProceso({{ $proceso->id }})" class="p-1 rounded bg-slate-100 text-slate-500 hover:bg-slate-200 hover:text-slate-700 transition">
                                                    <svg class="w-4 h-4 transform transition-transform" :class="isExpanded({{ $proceso->id }}) ? 'rotate-90' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"/>
                                                    </svg>
                                                </button>
                                            @endif
                                        </td>
                                        <td class="px-6 py-4 font-bold text-slate-800">{{ $proceso->actividad }}</td>
                                        <td class="px-6 py-4 text-slate-500 font-semibold">{{ $proceso->responsable ?? '—' }}</td>
                                        <td class="px-6 py-4 text-slate-500 font-mono text-xs">{{ $proceso->plazo ? $proceso->plazo->format('d/m/Y') : '—' }}</td>
                                        <td class="px-6 py-4 text-center">
                                            @php
                                                $procesoEstatusColors = match($proceso->estatus_published) {
                                                    'pendiente' => 'bg-slate-100 text-slate-700',
                                                    'en proceso' => 'bg-emerald-50 text-emerald-700',
                                                    'parcial' => 'bg-sky-50 text-sky-700',
                                                    'retrasado' => 'bg-rose-50 text-rose-700',
                                                    'cerrado' => 'bg-indigo-50 text-indigo-700',
                                                    default => 'bg-slate-100 text-slate-700',
                                                };
                                            @endphp
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold {{ $procesoEstatusColors }}">
                                                <span class="capitalize">{{ $proceso->estatus_published }}</span>
                                                <span>·</span>
                                                <span>{{ round($proceso->porcentaje_published) }}%</span>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-right">
                                            @if($proceso->comentariosList->isNotEmpty())
                                                <button @click="abrirComentarios('{{ addslashes($proceso->actividad) }}', '{{ $proceso->comentariosList->map(fn($c) => ['id'=>$c->id,'comentario'=>$c->comentario,'autor'=>$c->autor->name,'fecha'=>$c->created_at->format('d/m H:i')])->toJson() }}')"
                                                        class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition relative">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                                    </svg>
                                                    <span class="absolute top-0 right-0 w-2 h-2 bg-indigo-500 rounded-full"></span>
                                                </button>
                                            @endif
                                        </td>
                                    </tr>
 
                                    {{-- Subprocesos --}}
                                    @foreach($proceso->subprocesos as $sub)
                                        <tr class="hover:bg-slate-50 transition-colors" x-show="isExpanded({{ $proceso->id }})">
                                            <td class="px-6 py-4"></td>
                                            <td class="px-6 py-4 text-slate-700 pl-10 flex items-center gap-2">
                                                <span class="w-1.5 h-1.5 rounded-full bg-slate-300"></span>
                                                {{ $sub->actividad }}
                                            </td>
                                            <td class="px-6 py-4 text-slate-500 font-semibold">{{ $sub->responsable ?? '—' }}</td>
                                            <td class="px-6 py-4 text-slate-500 font-mono text-xs">{{ $sub->plazo ? $sub->plazo->format('d/m/Y') : '—' }}</td>
                                            <td class="px-6 py-4 text-center">
                                                @php
                                                    $subEstatusColors = match($sub->estatus_published) {
                                                        'pendiente' => 'bg-slate-100 text-slate-700',
                                                        'en proceso' => 'bg-emerald-50 text-emerald-700',
                                                        'parcial' => 'bg-sky-50 text-sky-700',
                                                        'retrasado' => 'bg-rose-50 text-rose-700',
                                                        'cerrado' => 'bg-indigo-50 text-indigo-700',
                                                        default => 'bg-slate-100 text-slate-700',
                                                    };
                                                @endphp
                                                <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold {{ $subEstatusColors }}">
                                                    <span class="capitalize">{{ $sub->estatus_published }}</span>
                                                    <span>·</span>
                                                    <span>{{ round($sub->porcentaje_published) }}%</span>
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 text-right">
                                                @if($sub->comentariosList->isNotEmpty())
                                                    <button @click="abrirComentarios('{{ addslashes($sub->actividad) }}', '{{ $sub->comentariosList->map(fn($c) => ['id'=>$c->id,'comentario'=>$c->comentario,'autor'=>$c->autor->name,'fecha'=>$c->created_at->format('d/m H:i')])->toJson() }}')"
                                                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition relative">
                                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/>
                                                        </svg>
                                                        <span class="absolute top-0 right-0 w-2 h-2 bg-indigo-500 rounded-full"></span>
                                                    </button>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="bg-white p-12 rounded-3xl border border-slate-200/60 shadow-sm text-center">
                    <svg class="w-12 h-12 text-slate-300 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-sm font-bold text-slate-700">Detalle de Matriz Privado</p>
                    <p class="text-xs text-slate-400 mt-1">El coordinador ha desactivado la vista detallada para este proyecto. Solo se comparte el resumen general de avance y fases.</p>
                </div>
            @endif
        </div>
    </div>
 
    {{-- FOOTER --}}
    <footer class="py-6 border-t border-slate-200/80 bg-white text-center text-xs text-slate-400">
        &copy; {{ date('Y') }} Estrategia e Innovación. Plataforma ERP.
    </footer>
 
    {{-- MODAL: COMENTARIOS PÚBLICOS --}}
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
                        <h3 class="text-lg font-bold text-slate-900">Bitácora de Notas</h3>
                        <p class="text-xs text-slate-400 mt-0.5 x-text" x-text="commentsActNombre"></p>
                    </div>
                    <button type="button" @click="openCommentsModal = false" class="p-1.5 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
 
                <div class="bg-white px-6 py-6 space-y-4 max-height-[40vh] overflow-y-auto" style="max-height: 40vh">
                    <div class="space-y-3">
                        <template x-for="c in commentsList" :key="c.id">
                            <div class="bg-slate-50 border border-slate-100 p-4 rounded-2xl space-y-1">
                                <div class="flex justify-between items-center text-[10px] text-slate-400 font-bold">
                                    <span x-text="c.autor"></span>
                                    <span x-text="c.fecha"></span>
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
 
</body>
</html>
