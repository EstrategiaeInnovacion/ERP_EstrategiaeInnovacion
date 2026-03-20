@extends('layouts.erp')
@php use App\Models\Recordatorio; @endphp

@section('title', 'Recordatorios - Recursos Humanos')

@section('content')
<div class="min-h-screen bg-slate-50 pb-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
        
        <div class="bg-white rounded-3xl shadow-sm border border-slate-200 p-8 relative overflow-hidden">
            <div class="absolute right-0 top-0 h-full w-1/2 bg-gradient-to-l from-amber-50/80 to-transparent pointer-events-none"></div>
            
            <div class="relative z-10 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3 mb-2">
                        <span class="px-3 py-1 rounded-full bg-amber-100 text-amber-700 text-xs font-bold uppercase tracking-wider border border-amber-200">
                            Alertas y Recordatorios
                        </span>
                    </div>
                    <h3 class="text-3xl font-bold text-slate-900 tracking-tight">Centro de Recordatorios</h3>
                    <p class="mt-2 text-slate-500 max-w-2xl text-lg leading-relaxed">
                        Mantén un seguimiento de cumpleaños, aniversarios laborales, vencimientos de documentos y más.
                    </p>
                </div>
                
                <div class="flex items-center gap-3">
                    <a href="{{ route('rh.recordatorios.calendario') }}" class="bg-indigo-600 hover:bg-indigo-700 text-white px-4 py-2.5 rounded-xl font-bold text-sm transition-all flex items-center gap-2">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                        Calendario
                    </a>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2" />
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-slate-500">Total</span>
                </div>
                <p class="text-3xl font-bold text-slate-900">{{ $estadisticas['total'] }}</p>
            </div>
            
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-slate-500">Sin Leer</span>
                </div>
                <p class="text-3xl font-bold text-blue-600">{{ $estadisticas['no_leidos'] }}</p>
            </div>
            
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-orange-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-slate-500">Urgentes</span>
                </div>
                <p class="text-3xl font-bold text-orange-600">{{ $estadisticas['urgentes'] }}</p>
            </div>
            
            <div class="bg-white rounded-2xl p-6 shadow-sm border border-slate-200">
                <div class="flex items-center gap-3 mb-3">
                    <div class="w-10 h-10 rounded-xl bg-red-100 flex items-center justify-center">
                        <svg class="w-5 h-5 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                    <span class="text-sm font-medium text-slate-500">Vencidos</span>
                </div>
                <p class="text-3xl font-bold text-red-600">{{ $estadisticas['vencidos'] }}</p>
            </div>
        </div>

        <div class="flex flex-col lg:flex-row gap-6">
            <div class="lg:w-64 flex-shrink-0">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-4 sticky top-4">
                    <h4 class="font-bold text-slate-800 mb-4">Por Tipo</h4>
                    <nav class="space-y-1">
                        <a href="{{ route('rh.recordatorios.index', ['tipo' => 'todos', 'estado' => $filtros['estado']]) }}"
                           class="flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ $filtros['tipo'] === 'todos' ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50' }}">
                            <span>Todos</span>
                            <span class="bg-slate-100 px-2 py-0.5 rounded-full text-xs">{{ $estadisticas['total'] }}</span>
                        </a>
                        @foreach (Recordatorio::TIPOS as $key => $nombre)
                            @if($porTipo[$key]['cantidad'] > 0)
                            <a href="{{ route('rh.recordatorios.index', ['tipo' => $key, 'estado' => $filtros['estado']]) }}"
                               class="flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ $filtros['tipo'] === $key ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50' }}">
                                <span>{{ $nombre }}</span>
                                <span class="bg-slate-100 px-2 py-0.5 rounded-full text-xs">{{ $porTipo[$key]['cantidad'] }}</span>
                            </a>
                            @endif
                        @endforeach
                    </nav>
                    
                    <h4 class="font-bold text-slate-800 mb-4 mt-6">Estado</h4>
                    <nav class="space-y-1">
                        <a href="{{ route('rh.recordatorios.index', ['tipo' => $filtros['tipo'], 'estado' => 'todos']) }}"
                           class="flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ $filtros['estado'] === 'todos' ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50' }}">
                            <span>Todos</span>
                        </a>
                        <a href="{{ route('rh.recordatorios.index', ['tipo' => $filtros['tipo'], 'estado' => 'urgentes']) }}"
                           class="flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ $filtros['estado'] === 'urgentes' ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50' }}">
                            <span>Próximos 7 días</span>
                        </a>
                        <a href="{{ route('rh.recordatorios.index', ['tipo' => $filtros['tipo'], 'estado' => 'no_leidos']) }}"
                           class="flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ $filtros['estado'] === 'no_leidos' ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50' }}">
                            <span>Sin Leer</span>
                        </a>
                        <a href="{{ route('rh.recordatorios.index', ['tipo' => $filtros['tipo'], 'estado' => 'vencidos']) }}"
                           class="flex items-center justify-between px-3 py-2 rounded-lg text-sm font-medium transition-colors {{ $filtros['estado'] === 'vencidos' ? 'bg-indigo-50 text-indigo-700' : 'text-slate-600 hover:bg-slate-50' }}">
                            <span>Vencidos</span>
                        </a>
                    </nav>
                    
                    @if($estadisticas['no_leidos'] > 0)
                    <form action="{{ route('rh.recordatorios.marcar-todos') }}" method="POST" class="mt-6">
                        @csrf
                        <button type="submit" class="w-full bg-slate-100 hover:bg-slate-200 text-slate-700 px-4 py-2 rounded-xl font-bold text-sm transition-all">
                            Marcar todos leídos
                        </button>
                    </form>
                    @endif
                </div>
            </div>
            
            <div class="flex-1">
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
            </div>
        </div>

        <div class="text-center pt-4">
            <form action="{{ route('rh.recordatorios.generar') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="text-xs text-slate-400 hover:text-indigo-600 transition-colors flex items-center gap-1 mx-auto">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    Sincronizar recordatorios
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
