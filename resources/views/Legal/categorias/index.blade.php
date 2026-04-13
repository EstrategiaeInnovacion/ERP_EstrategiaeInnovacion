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
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <h2 class="text-lg font-bold text-slate-900">Categorías existentes</h2>
            <span class="text-xs text-slate-400 bg-slate-100 rounded-full px-2.5 py-1 font-semibold">
                {{ $categorias->count() }} categoría(s)
            </span>
        </div>

        @if($categorias->isEmpty())
            <div class="text-center py-16 text-slate-400">
                <svg class="w-10 h-10 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                </svg>
                <p class="text-sm">No hay categorías creadas aún.</p>
            </div>
        @else
            <ul class="divide-y divide-slate-100">
                @foreach($categorias as $cat)
                <li class="px-6 py-3">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-sm font-semibold bg-amber-50 text-amber-800">
                                {{ $cat->nombre }}
                            </span>
                            <span class="text-xs text-slate-400">
                                {{ $cat->proyectos->count() }} proyecto(s)
                            </span>
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
@endsection
