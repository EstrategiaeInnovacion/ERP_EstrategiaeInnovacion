@extends('layouts.erp')
@php use App\Models\Recordatorio; @endphp

@section('title', 'Recordatorios - Recursos Humanos')

@section('content')
<div class="min-h-screen bg-slate-50 pb-12">
    <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">
        
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <h3 class="text-2xl font-bold text-slate-900">Centro de Recordatorios</h3>
                    <p class="text-slate-500 text-sm">Mantén un seguimiento de cumpleaños, aniversarios y vencimientos.</p>
                </div>
                <a href="{{ route('rh.recordatorios.calendario') }}" class="inline-flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2 rounded-xl font-bold text-sm transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                    </svg>
                    Calendario
                </a>
            </div>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('rh.recordatorios.index', ['tipo' => 'todos', 'estado' => 'todos']) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all {{ $filtros['tipo'] === 'todos' && $filtros['estado'] === 'todos' ? 'bg-slate-800 text-white' : 'bg-white text-slate-600 hover:bg-slate-100 border border-slate-200' }}">
                Todos
                <span class="bg-slate-200 px-2 py-0.5 rounded-full text-xs {{ $filtros['tipo'] === 'todos' && $filtros['estado'] === 'todos' ? 'bg-white/20 text-white' : 'text-slate-500' }}">
                    {{ $estadisticas['total'] }}
                </span>
            </a>
            
            <a href="{{ route('rh.recordatorios.index', ['tipo' => 'todos', 'estado' => 'no_leidos']) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all {{ $filtros['estado'] === 'no_leidos' ? 'bg-blue-600 text-white' : 'bg-white text-blue-600 hover:bg-blue-50 border border-blue-200' }}">
                Sin Leer
                @if($estadisticas['no_leidos'] > 0)
                <span class="bg-blue-200 px-2 py-0.5 rounded-full text-xs {{ $filtros['estado'] === 'no_leidos' ? 'bg-white/20 text-white' : 'text-blue-600' }}">
                    {{ $estadisticas['no_leidos'] }}
                </span>
                @endif
            </a>
            
            <a href="{{ route('rh.recordatorios.index', ['tipo' => 'todos', 'estado' => 'urgentes']) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all {{ $filtros['estado'] === 'urgentes' ? 'bg-orange-600 text-white' : 'bg-white text-orange-600 hover:bg-orange-50 border border-orange-200' }}">
                Urgentes
                @if($estadisticas['urgentes'] > 0)
                <span class="bg-orange-200 px-2 py-0.5 rounded-full text-xs {{ $filtros['estado'] === 'urgentes' ? 'bg-white/20 text-white' : 'text-orange-600' }}">
                    {{ $estadisticas['urgentes'] }}
                </span>
                @endif
            </a>
            
            <a href="{{ route('rh.recordatorios.index', ['tipo' => 'todos', 'estado' => 'vencidos']) }}"
               class="inline-flex items-center gap-2 px-4 py-2 rounded-xl text-sm font-medium transition-all {{ $filtros['estado'] === 'vencidos' ? 'bg-red-600 text-white' : 'bg-white text-red-600 hover:bg-red-50 border border-red-200' }}">
                Vencidos
                @if($estadisticas['vencidos'] > 0)
                <span class="bg-red-200 px-2 py-0.5 rounded-full text-xs {{ $filtros['estado'] === 'vencidos' ? 'bg-white/20 text-white' : 'text-red-600' }}">
                    {{ $estadisticas['vencidos'] }}
                </span>
                @endif
            </a>

            <div class="flex-1"></div>

            <div class="flex flex-wrap gap-1">
                @foreach (Recordatorio::TIPOS as $key => $nombre)
                    @if($porTipo[$key]['cantidad'] > 0)
                    <a href="{{ route('rh.recordatorios.index', ['tipo' => $key, 'estado' => 'todos']) }}"
                       class="inline-flex items-center gap-1 px-3 py-1.5 rounded-lg text-xs font-medium transition-all {{ $filtros['tipo'] === $key ? 'bg-indigo-100 text-indigo-700 ring-1 ring-indigo-300' : 'bg-white text-slate-500 hover:bg-slate-100 border border-slate-200' }}">
                        {{ $nombre }}
                        <span class="text-xs opacity-60">{{ $porTipo[$key]['cantidad'] }}</span>
                    </a>
                    @endif
                @endforeach
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            @if($recordatorios->isEmpty())
                <div class="p-12 text-center">
                    <div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">
                        <svg class="w-8 h-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <h4 class="text-lg font-bold text-slate-700 mb-2">No hay recordatorios</h4>
                    <p class="text-slate-500">No se encontraron recordatorios con los filtros seleccionados.</p>
                </div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach($recordatorios as $recordatorio)
                    <div class="p-4 hover:bg-slate-50 transition-colors {{ !$recordatorio->leido ? 'bg-amber-50/30' : '' }}">
                        <div class="flex items-start gap-4">
                            <div class="flex-shrink-0 mt-1">
                                <span class="text-2xl">{{ $recordatorio->icono_tipo }}</span>
                            </div>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-start justify-between gap-4">
                                    <div>
                                        @if(!$recordatorio->leido)
                                            <span class="inline-block w-2 h-2 rounded-full bg-amber-500 mb-1"></span>
                                        @endif
                                        <h5 class="font-bold text-slate-900 {{ !$recordatorio->leido ? 'text-indigo-700' : '' }}">
                                            {{ $recordatorio->titulo }}
                                        </h5>
                                    </div>
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $recordatorio->color_urgencia['badge'] }}">
                                        @if($recordatorio->dias_restantes < 0)
                                            {{ abs($recordatorio->dias_restantes) }} días vencido
                                        @elseif($recordatorio->dias_restantes == 0)
                                            Hoy
                                        @else
                                            En {{ $recordatorio->dias_restantes }} días
                                        @endif
                                    </span>
                                </div>
                                <p class="text-sm text-slate-500 mt-1">{{ $recordatorio->descripcion }}</p>
                                <div class="flex items-center gap-4 mt-2">
                                    @if($recordatorio->empleado)
                                        <a href="{{ route('rh.expedientes.show', $recordatorio->empleado->id) }}" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                                            {{ $recordatorio->empleado->nombre }}
                                        </a>
                                    @endif
                                    <span class="text-xs text-slate-400">
                                        {{ $recordatorio->fecha_evento->locale('es')->isoFormat('D [de] MMMM [de] YYYY') }}
                                    </span>
                                    <span class="text-xs text-slate-400 px-2 py-0.5 bg-slate-100 rounded">
                                        {{ Recordatorio::TIPOS[$recordatorio->tipo] ?? $recordatorio->tipo }}
                                    </span>
                                </div>
                            </div>
                            <div class="flex-shrink-0 flex items-center gap-2">
                                @if(!$recordatorio->leido)
                                    <form action="{{ route('rh.recordatorios.marcar-leido', $recordatorio->id) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="p-2 text-slate-400 hover:text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors" title="Marcar como leído">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                                            </svg>
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('rh.recordatorios.destruir', $recordatorio->id) }}" method="POST" onsubmit="return confirm('¿Eliminar este recordatorio?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Eliminar">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                    @endforeach
                </div>
                
                <div class="px-4 py-3 border-t border-slate-200 bg-slate-50">
                    {{ $recordatorios->withQueryString()->links() }}
                </div>
            @endif
        </div>

        <div class="text-center pt-4">
            @if($estadisticas['no_leidos'] > 0)
            <form action="{{ route('rh.recordatorios.marcar-todos') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="text-xs text-slate-400 hover:text-indigo-600 transition-colors flex items-center gap-1 mx-auto">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Marcar todos como leídos
                </button>
            </form>
            @endif
            <span class="text-slate-300 mx-3">|</span>
            <form action="{{ route('rh.recordatorios.generar') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="text-xs text-slate-400 hover:text-indigo-600 transition-colors flex items-center gap-1 mx-auto">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Sincronizar
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
