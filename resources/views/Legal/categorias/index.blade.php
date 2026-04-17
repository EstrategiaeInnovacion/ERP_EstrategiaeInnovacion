@extends('layouts.master')

@section('title', 'Gestionar Categorías - Legal')

@section('content')

{{-- HEADER --}}
<div class="bg-white border-b border-slate-200 -mx-4 sm:-mx-6 lg:-mx-8 px-4 sm:px-6 lg:px-8 py-6 mb-8">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-500 mb-1">
                <a href="{{ route('legal.dashboard') }}" class="hover:text-amber-600 transition-colors">Panel Legal</a>
                <span>/</span>
                <a href="{{ route('legal.matriz.index') }}" class="hover:text-amber-600 transition-colors">Matriz de Consulta</a>
                <span>/</span>
                <span class="text-slate-700 font-medium">Categorías</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">Gestionar Categorías</h1>
            <p class="text-slate-500 mt-1 text-sm">Administra las categorías de la Matriz de Consulta.</p>
        </div>
        <a href="{{ route('legal.matriz.index') }}"
           class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 text-slate-600 font-bold text-sm rounded-xl hover:bg-slate-50 hover:border-amber-200 hover:text-amber-600 transition shadow-sm group">
            <svg class="w-4 h-4 mr-2 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            Volver a la Matriz
        </a>
    </div>
</div>

{{-- ALERTAS --}}
@if(session('success'))
    <div class="mb-6 flex items-center gap-3 p-4 rounded-xl bg-emerald-50 border border-emerald-100 text-emerald-700">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        <span class="text-sm font-medium">{{ session('success') }}</span>
    </div>
@endif

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

    {{-- FORMULARIO NUEVA CATEGORÍA --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6">
        <h2 class="text-lg font-bold text-slate-900 mb-4 flex items-center gap-2">
            <svg class="w-5 h-5 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
            </svg>
            Nueva Categoría
        </h2>
        <form action="{{ route('legal.categorias.store') }}" method="POST" class="space-y-4">
            @csrf

            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-2">Tipo *</label>
                <div class="flex rounded-xl overflow-hidden border border-slate-200">
                    <button type="button" id="btnCatConsulta" onclick="seleccionarTipoCat('consulta')"
                        class="flex-1 py-2.5 text-sm font-bold bg-amber-600 text-white transition">
                        Consulta
                    </button>
                    <button type="button" id="btnCatEscritos" onclick="seleccionarTipoCat('escritos')"
                        class="flex-1 py-2.5 text-sm font-semibold bg-white text-slate-500 hover:bg-slate-50 transition border-l border-slate-200">
                        Escritos
                    </button>
                </div>
                <input type="hidden" name="tipo" id="catTipoInput" value="consulta">
                @error('tipo')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">Nombre *</label>
                <input type="text" name="nombre" required placeholder="Ej: Derecho Corporativo"
                    class="block w-full rounded-xl border-slate-200 bg-slate-50 text-slate-800 focus:border-amber-500 focus:ring-amber-500 sm:text-sm py-2.5">
                @error('nombre')
                    <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                @enderror
            </div>

            <button type="submit"
                class="w-full py-3 bg-amber-600 hover:bg-amber-700 text-white font-bold text-sm rounded-xl shadow-lg shadow-amber-200 transition hover:-translate-y-0.5">
                Crear Categoría
            </button>
        </form>
    </div>

    {{-- LISTADO DE CATEGORÍAS --}}
    @php
        $catsConsulta = $categorias->filter(fn($c) => $c->tipo === 'consulta')->values();
        $catsEscritos = $categorias->filter(fn($c) => $c->tipo === 'escritos')->values();
    @endphp
    <div class="flex flex-col">
        {{-- Tabs nav --}}
        <div class="flex gap-1">
            <button type="button" onclick="cambiarTabCat('consulta')" id="tabcat-consulta"
                class="px-5 py-2.5 text-sm font-bold rounded-t-2xl border border-b-0 border-amber-300 bg-amber-50 text-amber-700 transition-all shadow-sm flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-3 3-3-3z"/>
                </svg>
                Consultas
                <span class="px-1.5 py-0.5 rounded-full text-xs bg-amber-100 text-amber-700 font-semibold">{{ $catsConsulta->count() }}</span>
            </button>
            <button type="button" onclick="cambiarTabCat('escritos')" id="tabcat-escritos"
                class="px-5 py-2.5 text-sm font-semibold rounded-t-2xl border border-b-0 border-slate-200 bg-white text-slate-500 hover:text-slate-700 transition-all flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Escritos
                <span class="px-1.5 py-0.5 rounded-full text-xs bg-slate-100 text-slate-600 font-semibold">{{ $catsEscritos->count() }}</span>
            </button>
        </div>

        {{-- Panel Consultas --}}
        <div id="panelcat-consulta" class="bg-white rounded-b-2xl rounded-tr-2xl border border-slate-200 shadow-sm overflow-hidden">
            @if($catsConsulta->isEmpty())
                <div class="text-center py-16 text-slate-400">
                    <svg class="w-10 h-10 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    <p class="text-sm">No hay categorías de consulta creadas aún.</p>
                </div>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach($catsConsulta as $cat)
                    <li class="px-6 py-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-semibold bg-amber-50 text-amber-800">
                                    {{ $cat->nombre }}
                                </span>
                                <span class="text-xs text-slate-400">{{ $cat->proyectos->count() }} proyecto(s)</span>
                            </div>
                            <form action="{{ route('legal.categorias.destroy', $cat->id) }}" method="POST"
                                  onsubmit="return confirm('¿Eliminar la categoría \'{{ addslashes($cat->nombre) }}\'?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </li>
                    @endforeach
                </ul>
            @endif
        </div>

        {{-- Panel Escritos --}}
        <div id="panelcat-escritos" class="hidden bg-white rounded-b-2xl rounded-tr-2xl border border-slate-200 shadow-sm overflow-hidden">
            @if($catsEscritos->isEmpty())
                <div class="text-center py-16 text-slate-400">
                    <svg class="w-10 h-10 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <p class="text-sm">No hay categorías de escritos creadas aún.</p>
                </div>
            @else
                <ul class="divide-y divide-slate-100">
                    @foreach($catsEscritos as $cat)
                    <li class="px-6 py-3">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-semibold bg-sky-50 text-sky-800">
                                    {{ $cat->nombre }}
                                </span>
                                <span class="text-xs text-slate-400">{{ $cat->proyectos->count() }} proyecto(s)</span>
                            </div>
                            <form action="{{ route('legal.categorias.destroy', $cat->id) }}" method="POST"
                                  onsubmit="return confirm('¿Eliminar la categoría \'{{ addslashes($cat->nombre) }}\'?')">
                                @csrf @method('DELETE')
                                <button type="submit" class="p-1.5 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-lg transition">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                    </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>

</div>

@push('scripts')
<script>
    function cambiarTabCat(tipo) {
        ['consulta', 'escritos'].forEach(t => {
            const tab   = document.getElementById('tabcat-' + t);
            const panel = document.getElementById('panelcat-' + t);
            if (t === tipo) {
                tab.className = 'px-5 py-2.5 text-sm font-bold rounded-t-2xl border border-b-0 border-amber-300 bg-amber-50 text-amber-700 transition-all shadow-sm flex items-center gap-1.5';
                panel.classList.remove('hidden');
            } else {
                tab.className = 'px-5 py-2.5 text-sm font-semibold rounded-t-2xl border border-b-0 border-slate-200 bg-white text-slate-500 hover:text-slate-700 transition-all flex items-center gap-1.5';
                panel.classList.add('hidden');
            }
        });
    }

    function seleccionarTipoCat(tipo) {
        document.getElementById('catTipoInput').value = tipo;
        const btnC = document.getElementById('btnCatConsulta');
        const btnE = document.getElementById('btnCatEscritos');
        if (tipo === 'consulta') {
            btnC.className = 'flex-1 py-2.5 text-sm font-bold bg-amber-600 text-white transition';
            btnE.className = 'flex-1 py-2.5 text-sm font-semibold bg-white text-slate-500 hover:bg-slate-50 transition border-l border-slate-200';
        } else {
            btnC.className = 'flex-1 py-2.5 text-sm font-semibold bg-white text-slate-500 hover:bg-slate-50 transition';
            btnE.className = 'flex-1 py-2.5 text-sm font-bold bg-amber-600 text-white transition border-l border-amber-600';
        }
        // Sincronizar con la pestaña del listado
        cambiarTabCat(tipo);
    }
</script>
@endpush
@endsection
