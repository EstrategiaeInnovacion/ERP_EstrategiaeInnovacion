@extends('layouts.erp')
@php use App\Models\Recordatorio; @endphp

@section('title', 'Recordatorio - ' . $recordatorio->titulo)

@section('content')
<div class="min-h-screen bg-slate-50 pb-12">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8">
        <div class="mb-6">
            <a href="{{ route('rh.recordatorios.index') }}" class="inline-flex items-center text-sm text-slate-600 hover:text-indigo-600 transition-colors">
                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
                </svg>
                Volver a recordatorios
            </a>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="p-8">
                <div class="flex items-start gap-4 mb-6">
                    <div class="w-16 h-16 rounded-2xl {{ $recordatorio->color_urgencia['bg'] }} flex items-center justify-center text-3xl">
                        {{ $recordatorio->icono_tipo }}
                    </div>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $recordatorio->color_urgencia['badge'] }}">
                                {{ Recordatorio::TIPOS[$recordatorio->tipo] ?? $recordatorio->tipo }}
                            </span>
                            @if(!$recordatorio->leido)
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-700">
                                    Nuevo
                                </span>
                            @endif
                        </div>
                        <h1 class="text-2xl font-bold text-slate-900">{{ $recordatorio->titulo }}</h1>
                    </div>
                </div>

                <div class="space-y-4 mb-8">
                    @if($recordatorio->descripcion)
                    <div>
                        <h4 class="text-sm font-medium text-slate-500 mb-1">Descripción</h4>
                        <p class="text-slate-700">{{ $recordatorio->descripcion }}</p>
                    </div>
                    @endif

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <h4 class="text-sm font-medium text-slate-500 mb-1">Fecha del Evento</h4>
                            <p class="text-slate-900 font-medium">
                                {{ $recordatorio->fecha_evento->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}
                            </p>
                        </div>
                        <div>
                            <h4 class="text-sm font-medium text-slate-500 mb-1">Días Restantes</h4>
                            <p class="text-slate-900 font-medium">
                                @if($recordatorio->dias_restantes < 0)
                                    <span class="text-red-600">Vencido hace {{ abs($recordatorio->dias_restantes) }} días</span>
                                @elseif($recordatorio->dias_restantes == 0)
                                    <span class="text-amber-600">Hoy</span>
                                @else
                                    {{ $recordatorio->dias_restantes }} días
                                @endif
                            </p>
                        </div>
                    </div>

                    @if($recordatorio->empleado)
                    <div>
                        <h4 class="text-sm font-medium text-slate-500 mb-1">Empleado Relacionado</h4>
                        <a href="{{ route('rh.expedientes.show', $recordatorio->empleado->id) }}" class="text-indigo-600 hover:text-indigo-800 font-medium">
                            {{ $recordatorio->empleado->nombre }}
                        </a>
                    </div>
                    @endif

                    @if($recordatorio->creador)
                    <div>
                        <h4 class="text-sm font-medium text-slate-500 mb-1">Creado por</h4>
                        <p class="text-slate-700">{{ $recordatorio->creador->name }}</p>
                    </div>
                    @endif
                </div>

                <div class="flex items-center gap-4 pt-6 border-t border-slate-200">
                    @if(!$recordatorio->leido)
                        <form action="{{ route('rh.recordatorios.marcar-leido', $recordatorio->id) }}" method="POST">
                            @csrf
                            <button type="submit" class="bg-indigo-600 hover:bg-indigo-700 text-white px-6 py-2.5 rounded-xl font-bold text-sm transition-all">
                                Marcar como leído
                            </button>
                        </form>
                    @endif
                    <form action="{{ route('rh.recordatorios.destruir', $recordatorio->id) }}" method="POST" onsubmit="return confirm('¿Eliminar este recordatorio?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-50 hover:bg-red-100 text-red-600 px-6 py-2.5 rounded-xl font-bold text-sm transition-all">
                            Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
