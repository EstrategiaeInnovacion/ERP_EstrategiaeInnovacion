@extends('layouts.erp')

@section('content')
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h2 class="font-bold text-2xl text-slate-800 leading-tight tracking-tight">
                    {{ __('Evaluación de Desempeño') }}
                </h2>
                <p class="text-xs text-slate-500 mt-1">Gestión del talento y medición de competencias por área.</p>
            </div>
            
            <div class="flex items-center gap-3 flex-wrap">
                {{-- Selector de periodo --}}
                <div class="flex items-center gap-2 bg-white p-2 rounded-lg shadow-sm border border-slate-200">
                    <span class="text-xs font-bold text-slate-500 uppercase px-2">Periodo:</span>
                    <form method="GET" action="{{ route('rh.evaluacion.index') }}">
                        @if(request('area'))
                            <input type="hidden" name="area" value="{{ request('area') }}">
                        @endif
                        <select name="periodo" onchange="this.form.submit()" class="text-sm border-none bg-slate-50 rounded-md focus:ring-indigo-500 text-slate-700 font-semibold cursor-pointer py-1 pl-3 pr-8">
                            @foreach($periodos as $periodoOption)
                                <option value="{{ $periodoOption }}" {{ $selectedPeriod == $periodoOption ? 'selected' : '' }}>
                                    {{ $periodoOption }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                </div>

                {{-- Botón Admin RH: Gestionar Ventana de Evaluaciones --}}
                @if(!empty($puedeGestionarVentanas))
                    <button onclick="document.getElementById('modalVentana').classList.remove('hidden')"
                        class="flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-lg shadow-sm transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        Gestionar Periodo
                    </button>
                @endif
            </div>
        </div>
    </x-slot>

    @php
        $categoriasPrincipales = ['Logistica', 'Legal', 'Anexo 24', 'Auditoria', 'TI'];
        $todosLosPuestos = $empleados->pluck('posicion')->unique()->values()->toArray();
        $todasLasCategorias = array_unique(array_merge($categoriasPrincipales, $todosLosPuestos));
    @endphp

    <div class="py-12 bg-slate-50 min-h-screen" x-data="{ activeTab: 'Logistica' }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            
            {{-- BANNER DE ESTADO DEL PERIODO --}}
            @if(!$isWindowOpen)
                <div class="bg-amber-50 border-l-4 border-amber-500 p-4 rounded-r shadow-sm">
                    <div class="flex items-center">
                        <div class="flex-shrink-0">
                            <svg class="h-8 w-8 text-amber-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <div class="ml-4">
                            <h3 class="text-lg leading-6 font-bold text-amber-800">Periodo de Evaluaciones Cerrado</h3>
                            <p class="text-sm text-amber-700 mt-1">
                                Las evaluaciones solo se pueden crear o editar durante los <strong>últimos 10 días</strong> del semestre. Actualmente modo <strong>solo lectura</strong>.
                            </p>
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex items-center justify-end px-2">
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                    Evaluando: {{ $selectedPeriod }}
                </span>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-2">
                <nav class="flex space-x-1 overflow-x-auto custom-scrollbar pb-2 md:pb-0" aria-label="Tabs">
                    @foreach($todasLasCategorias as $categoria)
                        @if(!empty($categoria))
                            <button 
                                @click="activeTab = '{{ $categoria }}'"
                                :class="activeTab === '{{ $categoria }}' ? 'bg-indigo-50 text-indigo-700 shadow-sm ring-1 ring-indigo-200' : 'text-slate-500 hover:text-slate-700 hover:bg-slate-50'"
                                class="whitespace-nowrap px-5 py-2.5 rounded-xl font-semibold text-sm transition-all duration-200 ease-in-out flex-shrink-0">
                                {{ $categoria }}
                            </button>
                        @endif
                    @endforeach
                </nav>
            </div>

            <div class="space-y-6">
                @foreach($todasLasCategorias as $categoria)
                    @if(!empty($categoria))
                        <div x-show="activeTab === '{{ $categoria }}'" style="display: none;">
                            <div class="flex items-center justify-between mb-6 px-2">
                                <div class="flex items-center gap-3">
                                    <div class="p-2 bg-indigo-100 text-indigo-600 rounded-lg">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                    </div>
                                    <h4 class="text-xl font-bold text-slate-800">{{ $categoria }}</h4>
                                </div>
                                <span class="bg-white text-slate-600 text-xs font-bold px-3 py-1 rounded-full border border-slate-200 shadow-sm">
                                    {{ $empleados->filter(fn($e) => str_contains($e->posicion, $categoria) || $e->posicion == $categoria)->count() }} Miembros
                                </span>
                            </div>

                            @php $empleadosCategoria = $empleados->filter(fn($e) => $e->posicion === $categoria || str_contains($e->posicion, $categoria)); @endphp

                            @if($empleadosCategoria->isEmpty())
                                <div class="flex flex-col items-center justify-center py-16 bg-white rounded-3xl border border-dashed border-slate-300">
                                    <h3 class="text-slate-900 font-semibold">Sin colaboradores asignados</h3>
                                </div>
                            @else
                                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
                                    @foreach($empleadosCategoria as $empleado)
                                        <div class="group bg-white rounded-2xl p-5 border border-slate-200 shadow-sm hover:shadow-lg hover:-translate-y-1 transition-all duration-300 relative overflow-hidden">
                                            <div class="relative z-10 flex flex-col h-full">
                                                <div class="flex items-start justify-between mb-4">
                                                    <div class="h-14 w-14 rounded-2xl bg-indigo-50 border border-indigo-100 flex items-center justify-center text-indigo-600 font-bold text-xl shadow-sm overflow-hidden">
                                                        @if(isset($empleado->foto_path) && $empleado->foto_path)
                                                            <img src="{{ asset('storage/' . $empleado->foto_path) }}" class="w-full h-full object-cover">
                                                        @else
                                                            {{ substr($empleado->nombre, 0, 1) }}
                                                        @endif
                                                    </div>
                                                    @if(isset($empleado->evaluacion_actual))
                                                        <span class="inline-flex items-center px-2 py-1 rounded-md text-[10px] font-bold {{ $empleado->evaluacion_actual->edit_count >= 1 ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                                            {{ $empleado->evaluacion_actual->edit_count >= 1 ? 'FINALIZADA' : 'EN REVISIÓN' }}
                                                        </span>
                                                    @else
                                                        <span class="inline-flex items-center px-2 py-1 rounded-md text-[10px] font-bold bg-slate-50 text-slate-500">PENDIENTE</span>
                                                    @endif
                                                </div>

                                                <div class="flex-1">
                                                    <h5 class="text-lg font-bold text-slate-800 truncate">{{ $empleado->nombre }}</h5>
                                                    <p class="text-xs text-slate-500 font-medium uppercase truncate">{{ $empleado->apellido_paterno }}</p>
                                                    <div class="mt-3 flex items-center text-xs text-slate-500">
                                                        <span class="truncate">{{ $empleado->posicion }}</span>
                                                    </div>
                                                </div>

                                                <div class="mt-5 pt-4 border-t border-slate-100">
                                                    @if(!$isWindowOpen)
                                                        @if(isset($empleado->evaluacion_actual))
                                                            <a href="{{ route('rh.evaluacion.show', ['id' => $empleado->id, 'periodo' => $selectedPeriod]) }}" class="flex items-center justify-center w-full px-4 py-2 bg-slate-200 hover:bg-slate-300 text-slate-600 text-xs font-bold uppercase tracking-wider rounded-lg transition-colors">
                                                                Ver Evaluación (Cerrado)
                                                            </a>
                                                        @else
                                                            <button disabled class="flex items-center justify-center w-full px-4 py-2 bg-slate-100 text-slate-400 text-xs font-bold uppercase tracking-wider rounded-lg cursor-not-allowed">
                                                                Fuera de Fecha
                                                            </button>
                                                        @endif
                                                    @else
                                                        @if(isset($empleado->evaluacion_actual))
                                                            @if($empleado->evaluacion_actual->edit_count >= 1)
                                                                <a href="{{ route('rh.evaluacion.show', ['id' => $empleado->id, 'periodo' => $selectedPeriod]) }}" class="flex items-center justify-center w-full px-4 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-bold uppercase tracking-wider rounded-lg transition-colors">
                                                                    Ver Resultados
                                                                </a>
                                                            @else
                                                                <a href="{{ route('rh.evaluacion.show', ['id' => $empleado->id, 'periodo' => $selectedPeriod]) }}" class="flex items-center justify-center w-full px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white text-xs font-bold uppercase tracking-wider rounded-lg transition-colors">
                                                                    Editar Evaluación
                                                                </a>
                                                            @endif
                                                        @else
                                                            <div class="mt-4 flex gap-2">
                                                                <a href="{{ route('rh.evaluacion.show', ['id' => $empleado->id, 'periodo' => $selectedPeriod]) }}" 
                                                                class="flex-1 text-center px-4 py-2 bg-indigo-50 text-indigo-700 rounded-lg text-sm font-bold hover:bg-indigo-100 transition">
                                                                Evaluar
                                                                </a>

                                                                @if(isset($hasFullVisibility) && $hasFullVisibility)
                                                                    <a href="{{ route('rh.evaluacion.resultados', ['id' => $empleado->id, 'periodo' => $selectedPeriod]) }}" 
                                                                    class="px-4 py-2 bg-white border border-slate-200 text-slate-600 rounded-lg text-sm font-bold hover:bg-slate-50 transition" 
                                                                    title="Ver Resultados Consolidados">
                                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                                                                    </a>
                                                                @endif
                                                            </div>
                                                        @endif
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif
                @endforeach
            </div>
        </div>
    </div>

    {{-- ============================================================ --}}
    {{-- MODAL: GESTIÓN DE VENTANA DE EVALUACIONES (Solo Admin RH)   --}}
    {{-- ============================================================ --}}
    @if(!empty($puedeGestionarVentanas))
    <div id="modalVentana" class="hidden fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm px-4">
        <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">

            {{-- Header del modal --}}
            <div class="flex items-center justify-between p-6 border-b border-slate-100">
                <div class="flex items-center gap-3">
                    <div class="p-2.5 bg-indigo-100 text-indigo-600 rounded-xl">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">Gestionar Periodo de Evaluaciones</h3>
                        <p class="text-xs text-slate-500">Define cuándo se abre y cierra la ventana de evaluaciones.</p>
                    </div>
                </div>
                <button onclick="document.getElementById('modalVentana').classList.add('hidden')"
                    class="p-2 rounded-lg text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>

            {{-- Estado actual --}}
            <div class="px-6 pt-5">
                @if($ventanaActiva)
                    <div class="flex items-center gap-3 p-4 bg-emerald-50 border border-emerald-200 rounded-xl mb-5">
                        <span class="flex-shrink-0 h-3 w-3 rounded-full bg-emerald-500 animate-pulse"></span>
                        <div>
                            <p class="text-sm font-bold text-emerald-800">Ventana ABIERTA: <span class="font-semibold">{{ $ventanaActiva->nombre }}</span></p>
                            <p class="text-xs text-emerald-700 mt-0.5">
                                Del {{ \Carbon\Carbon::parse($ventanaActiva->fecha_apertura)->format('d/m/Y') }}
                                al {{ \Carbon\Carbon::parse($ventanaActiva->fecha_cierre)->format('d/m/Y') }}
                            </p>
                        </div>
                    </div>
                @else
                    <div class="flex items-center gap-3 p-4 bg-amber-50 border border-amber-200 rounded-xl mb-5">
                        <span class="flex-shrink-0 h-3 w-3 rounded-full bg-amber-400"></span>
                        <p class="text-sm font-bold text-amber-800">Sin ventana activa actualmente — evaluaciones cerradas.</p>
                    </div>
                @endif
            </div>

            {{-- Formulario nueva ventana --}}
            <div class="px-6 pb-2">
                <h4 class="text-sm font-bold text-slate-700 uppercase tracking-wider mb-3">Programar Nueva Ventana</h4>
                <form id="formVentana" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-semibold text-slate-600 mb-1">Nombre del Periodo</label>
                        <input type="text" id="v_nombre" name="nombre" placeholder="Ej: 2026 | Enero - Junio"
                            class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent"
                            value="{{ $selectedPeriod }}" required>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Fecha de Apertura</label>
                            <input type="date" id="v_apertura" name="fecha_apertura"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent" required>
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-600 mb-1">Fecha de Cierre</label>
                            <input type="date" id="v_cierre" name="fecha_cierre"
                                class="w-full rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm text-slate-800 focus:outline-none focus:ring-2 focus:ring-indigo-400 focus:border-transparent" required>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 p-3 bg-slate-50 rounded-xl">
                        <input type="checkbox" id="v_activo" name="activo" value="1" checked
                            class="w-4 h-4 text-indigo-600 border-slate-300 rounded focus:ring-indigo-500">
                        <label for="v_activo" class="text-sm text-slate-700 font-medium cursor-pointer">
                            Activar inmediatamente 
                            <span class="text-xs text-slate-400">(desactiva cualquier otra ventana vigente)</span>
                        </label>
                    </div>

                    <div id="ventanaMsg" class="hidden text-sm px-4 py-3 rounded-xl font-medium"></div>

                    <div class="flex gap-3 pt-1">
                        <button type="submit"
                            class="flex-1 flex items-center justify-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-bold rounded-xl transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                            </svg>
                            Guardar Ventana
                        </button>
                        <button type="button" onclick="document.getElementById('modalVentana').classList.add('hidden')"
                            class="px-5 py-2.5 border border-slate-200 text-slate-600 text-sm font-bold rounded-xl hover:bg-slate-50 transition-colors">
                            Cancelar
                        </button>
                    </div>
                </form>
            </div>

            {{-- Historial de ventanas --}}
            <div class="px-6 pt-4 pb-6">
                <h4 class="text-sm font-bold text-slate-700 uppercase tracking-wider mb-3">Ventanas Programadas</h4>
                <div id="listaVentanas" class="space-y-2">
                    <p class="text-xs text-slate-400 text-center py-4">Cargando...</p>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function () {
        const modal    = document.getElementById('modalVentana');
        const form     = document.getElementById('formVentana');
        const msg      = document.getElementById('ventanaMsg');
        const lista    = document.getElementById('listaVentanas');

        // Cerrar modal al hacer click fuera
        modal.addEventListener('click', function(e) {
            if (e.target === modal) modal.classList.add('hidden');
        });

        // Cargar lista cuando se abre el modal
        document.querySelector('[onclick*="modalVentana"]')?.addEventListener('click', cargarVentanas);

        function cargarVentanas() {
            fetch('{{ route("rh.evaluacion.ventanas.index") }}', {
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
            })
            .then(r => r.json())
            .then(data => {
                if (!data.ventanas || data.ventanas.length === 0) {
                    lista.innerHTML = '<p class="text-xs text-slate-400 text-center py-4">Sin ventanas registradas aún.</p>';
                    return;
                }
                lista.innerHTML = data.ventanas.map(v => `
                    <div class="flex items-center justify-between p-3 rounded-xl border ${v.activo ? 'border-emerald-200 bg-emerald-50' : 'border-slate-100 bg-white'}">
                        <div>
                            <p class="text-sm font-bold ${v.activo ? 'text-emerald-800' : 'text-slate-700'}">${v.nombre}</p>
                            <p class="text-xs ${v.activo ? 'text-emerald-600' : 'text-slate-400'} mt-0.5">
                                ${formatDate(v.fecha_apertura)} → ${formatDate(v.fecha_cierre)}
                            </p>
                        </div>
                        <button onclick="toggleVentana(${v.id}, this)"
                            class="text-xs font-bold px-3 py-1.5 rounded-lg transition-colors ${v.activo
                                ? 'bg-red-50 text-red-600 hover:bg-red-100'
                                : 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100'}">
                            ${v.activo ? 'Desactivar' : 'Activar'}
                        </button>
                    </div>
                `).join('');
            })
            .catch(() => {
                lista.innerHTML = '<p class="text-xs text-red-400 text-center py-4">Error al cargar ventanas.</p>';
            });
        }

        function formatDate(d) {
            if (!d) return '';
            const [y, m, day] = d.split('-');
            return `${day}/${m}/${y}`;
        }

        function toggleVentana(id, btn) {
            btn.disabled = true;
            fetch(`{{ url('capital-humano/evaluacion-ventanas') }}/${id}/toggle`, {
                method: 'PATCH',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            })
            .then(r => r.json())
            .then(() => cargarVentanas())
            .catch(() => { btn.disabled = false; });
        }

        form.addEventListener('submit', function(e) {
            e.preventDefault();
            msg.classList.add('hidden');

            const data = {
                nombre: document.getElementById('v_nombre').value,
                fecha_apertura: document.getElementById('v_apertura').value,
                fecha_cierre: document.getElementById('v_cierre').value,
                activo: document.getElementById('v_activo').checked ? 1 : 0,
            };

            fetch('{{ route("rh.evaluacion.ventanas.store") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    msg.textContent = res.message;
                    msg.className = 'text-sm px-4 py-3 rounded-xl font-medium bg-emerald-50 text-emerald-800 border border-emerald-200';
                    msg.classList.remove('hidden');
                    form.reset();
                    document.getElementById('v_nombre').value = '{{ $selectedPeriod }}';
                    document.getElementById('v_activo').checked = true;
                    cargarVentanas();
                    setTimeout(() => location.reload(), 1800);
                } else {
                    msg.textContent = res.message ?? 'Error al guardar.';
                    msg.className = 'text-sm px-4 py-3 rounded-xl font-medium bg-red-50 text-red-800 border border-red-200';
                    msg.classList.remove('hidden');
                }
            })
            .catch(() => {
                msg.textContent = 'Error de conexión.';
                msg.className = 'text-sm px-4 py-3 rounded-xl font-medium bg-red-50 text-red-800 border border-red-200';
                msg.classList.remove('hidden');
            });
        });

        // Auto-cargar si el modal ya está visible al abrir la página (por si acaso)
        if (!modal.classList.contains('hidden')) cargarVentanas();
    })();
    </script>
    @endif
@endsection