@extends('layouts.master')

@section('title', 'Mantenimiento ' . $mantenimiento->folio)

@section('content')
<div class="min-h-screen bg-slate-50 pb-16">

    {{-- Header --}}
    <div class="bg-white border-b border-slate-200 mb-6 no-print">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-5">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <a href="{{ route('admin.expedientes.show', $expediente) }}"
                       class="p-2 rounded-lg hover:bg-slate-100 text-slate-500 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                    </a>
                    <div>
                        <h1 class="text-xl font-bold text-slate-900">{{ $mantenimiento->folio }}</h1>
                        <p class="text-sm text-slate-500">
                            {{ $mantenimiento->tipo_label }} — {{ $mantenimiento->estado_label }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a href="{{ route('admin.expedientes.mantenimiento.edit', [$expediente, $mantenimiento]) }}"
                       class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-slate-200 text-slate-600 text-sm font-semibold rounded-xl hover:bg-slate-50 transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        Editar
                    </a>
                    <button onclick="window.print()"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-xl hover:bg-blue-700 transition shadow-sm">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                        </svg>
                        Imprimir / PDF
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- Printable document --}}
    <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 print:px-0 print:max-w-full">
        <div class="bg-white border border-slate-200 rounded-xl shadow-sm print:border-0 print:shadow-none print:rounded-none">

            {{-- Encabezado del documento --}}
            <div class="border-b border-slate-200 p-6 flex items-start justify-between">
                <div>
                    <p class="text-xs text-slate-400 uppercase tracking-wide font-semibold">Reporte de Mantenimiento</p>
                    <h2 class="text-2xl font-bold text-slate-900 mt-0.5">{{ $mantenimiento->folio }}</h2>
                    <div class="flex flex-wrap gap-2 mt-2">
                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $mantenimiento->tipo_badge }}">
                            {{ $mantenimiento->tipo_label }}
                        </span>
                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $mantenimiento->estado_badge }}">
                            {{ $mantenimiento->estado_label }}
                        </span>
                        <span class="inline-flex px-2.5 py-0.5 rounded-full text-xs font-semibold {{ $mantenimiento->prioridad_badge }}">
                            Prioridad: {{ ucfirst($mantenimiento->prioridad) }}
                        </span>
                    </div>
                </div>
                <div class="text-right text-sm text-slate-500">
                    <p>Creado: {{ $mantenimiento->created_at->format('d/m/Y H:i') }}</p>
                    @if($mantenimiento->tecnico)
                    <p class="mt-0.5">Técnico: <span class="font-semibold text-slate-700">{{ $mantenimiento->tecnico->name }}</span></p>
                    @endif
                </div>
            </div>

            {{-- Datos del equipo y usuario --}}
            <div class="p-6 border-b border-slate-100">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Datos del equipo</h3>
                @php $ea = $mantenimiento->expediente->equipoAsignado; @endphp
                <dl class="grid grid-cols-2 sm:grid-cols-4 gap-x-6 gap-y-2 text-sm">
                    <div>
                        <dt class="text-xs text-slate-400 font-semibold">Nombre del equipo</dt>
                        <dd class="text-slate-800 font-medium">{{ $ea->nombre_equipo ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-400 font-semibold">Modelo</dt>
                        <dd class="text-slate-800">{{ $ea->modelo ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-400 font-semibold">No. de serie</dt>
                        <dd class="text-slate-800 font-mono text-xs">{{ $ea->numero_serie ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-400 font-semibold">Usuario al momento</dt>
                        <dd class="text-slate-800 font-medium">{{ $mantenimiento->usuario_al_momento ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-400 font-semibold">Área al momento</dt>
                        <dd class="text-slate-800">{{ $mantenimiento->area_al_momento ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-400 font-semibold">Inicio</dt>
                        <dd class="text-slate-800">{{ $mantenimiento->fecha_inicio ? $mantenimiento->fecha_inicio->format('d/m/Y H:i') : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-400 font-semibold">Fin</dt>
                        <dd class="text-slate-800">{{ $mantenimiento->fecha_fin ? $mantenimiento->fecha_fin->format('d/m/Y H:i') : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="text-xs text-slate-400 font-semibold">Duración</dt>
                        <dd class="text-slate-800">{{ $mantenimiento->duracion ?? '—' }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Descripción del problema --}}
            @if($mantenimiento->descripcion_problema)
            <div class="p-6 border-b border-slate-100">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Descripción del problema / motivo</h3>
                <p class="text-sm text-slate-700 leading-relaxed">{{ $mantenimiento->descripcion_problema }}</p>
            </div>
            @endif

            {{-- Checklist de actividades --}}
            @if(!empty($mantenimiento->actividades))
            <div class="p-6 border-b border-slate-100">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Checklist de actividades</h3>
                @php
                    $categorias = collect($mantenimiento->actividades)->groupBy('categoria');
                @endphp
                <div class="space-y-4">
                    @foreach($categorias as $cat => $items)
                    <div>
                        <p class="text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">{{ $cat }}</p>
                        <table class="min-w-full text-sm border border-slate-100 rounded-lg overflow-hidden">
                            <tbody class="divide-y divide-slate-100">
                                @foreach($items as $item)
                                <tr class="@if($item['estado'] === 'completado') bg-green-50 @elseif($item['estado'] === 'no_aplica') bg-slate-50 @else bg-white @endif">
                                    <td class="py-2 px-3 text-slate-700">{{ $item['actividad'] }}</td>
                                    <td class="py-2 px-3 w-28">
                                        @if($item['estado'] === 'completado')
                                            <span class="inline-flex items-center gap-1 text-green-700 text-xs font-semibold">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                                                Completado
                                            </span>
                                        @elseif($item['estado'] === 'no_aplica')
                                            <span class="text-slate-400 text-xs">N/A</span>
                                        @else
                                            <span class="text-amber-600 text-xs font-semibold">Pendiente</span>
                                        @endif
                                    </td>
                                    <td class="py-2 px-3 text-xs text-slate-500">{{ $item['observaciones'] ?? '' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Hallazgos --}}
            @if(!empty($mantenimiento->hallazgos))
            <div class="p-6 border-b border-slate-100">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Hallazgos y recomendaciones</h3>
                <div class="space-y-2">
                    @foreach($mantenimiento->hallazgos as $hallazgo)
                    @php
                        $riesgoColor = match($hallazgo['nivel_riesgo'] ?? 'bajo') {
                            'bajo'    => 'border-green-200 bg-green-50',
                            'medio'   => 'border-yellow-200 bg-yellow-50',
                            'alto'    => 'border-orange-200 bg-orange-50',
                            'critico' => 'border-red-200 bg-red-50',
                            default   => 'border-slate-200 bg-white',
                        };
                        $riesgoLabel = match($hallazgo['nivel_riesgo'] ?? 'bajo') {
                            'bajo' => 'Bajo', 'medio' => 'Medio', 'alto' => 'Alto', 'critico' => 'Crítico', default => '—'
                        };
                    @endphp
                    <div class="border rounded-lg p-3 {{ $riesgoColor }}">
                        <div class="flex items-center justify-between gap-2 mb-0.5">
                            <p class="text-sm font-semibold text-slate-800">{{ $hallazgo['descripcion'] }}</p>
                            <span class="text-xs font-bold uppercase tracking-wide flex-shrink-0">Riesgo: {{ $riesgoLabel }}</span>
                        </div>
                        @if(!empty($hallazgo['recomendacion']))
                        <p class="text-xs text-slate-600 mt-1">Recomendación: {{ $hallazgo['recomendacion'] }}</p>
                        @endif
                    </div>
                    @endforeach
                </div>
            </div>
            @endif

            {{-- Observaciones --}}
            @if($mantenimiento->observaciones)
            <div class="p-6 border-b border-slate-100">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-2">Observaciones generales</h3>
                <p class="text-sm text-slate-700 leading-relaxed">{{ $mantenimiento->observaciones }}</p>
            </div>
            @endif

            {{-- Próximo mantenimiento --}}
            @if($mantenimiento->proximo_mantenimiento)
            <div class="p-6 border-b border-slate-100 bg-blue-50">
                <h3 class="text-xs font-semibold text-blue-500 uppercase tracking-wide mb-1">Próximo mantenimiento programado</h3>
                <p class="text-sm font-bold text-blue-800">
                    {{ $mantenimiento->proximo_mantenimiento->format('d/m/Y') }}
                    @if($mantenimiento->frecuencia_siguiente)
                    <span class="font-normal text-blue-600 ml-2">(Frecuencia: {{ ucfirst($mantenimiento->frecuencia_siguiente) }})</span>
                    @endif
                </p>
            </div>
            @endif

            {{-- Archivos --}}
            @if($mantenimiento->archivos->isNotEmpty())
            <div class="p-6 border-b border-slate-100 no-print">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-3">Archivos adjuntos</h3>
                @php $grupos = $mantenimiento->archivos->groupBy('momento'); @endphp
                @foreach(['antes' => 'Antes', 'despues' => 'Después', 'documento' => 'Documentos'] as $momento => $label)
                    @if(isset($grupos[$momento]))
                    <div class="mb-3">
                        <p class="text-xs font-semibold text-slate-500 mb-2">{{ $label }}</p>
                        <div class="flex flex-wrap gap-3">
                            @foreach($grupos[$momento] as $archivo)
                            @if($archivo->es_imagen)
                            <a href="{{ $archivo->url }}" target="_blank" class="block">
                                <img src="{{ $archivo->url }}" alt="{{ $archivo->nombre_original }}"
                                     class="w-24 h-24 object-cover rounded-lg border border-slate-200 hover:opacity-80 transition">
                            </a>
                            @else
                            <a href="{{ $archivo->url }}" target="_blank"
                               class="flex items-center gap-2 px-3 py-2 bg-slate-50 border border-slate-200 rounded-lg text-xs font-medium text-blue-600 hover:bg-slate-100 transition">
                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                </svg>
                                {{ Str::limit($archivo->nombre_original ?? 'Archivo', 30) }}
                            </a>
                            @endif
                            @endforeach
                        </div>
                    </div>
                    @endif
                @endforeach
            </div>
            @endif

            {{-- Firmas --}}
            <div class="p-6">
                <h3 class="text-xs font-semibold text-slate-400 uppercase tracking-wide mb-4">Firmas</h3>
                <div class="grid grid-cols-2 gap-8">
                    <div class="text-center">
                        @if($mantenimiento->firma_tecnico && str_starts_with($mantenimiento->firma_tecnico, 'data:image'))
                        <img src="{{ $mantenimiento->firma_tecnico }}" alt="Firma técnico"
                             class="max-h-24 mx-auto mb-2 border border-slate-200 rounded">
                        @else
                        <div class="h-16 border-b border-slate-300 mb-2"></div>
                        @endif
                        <p class="text-xs font-semibold text-slate-600">Técnico de TI</p>
                        <p class="text-xs text-slate-500">{{ $mantenimiento->tecnico?->name ?? '___________________' }}</p>
                    </div>
                    <div class="text-center">
                        @if($mantenimiento->firma_usuario && str_starts_with($mantenimiento->firma_usuario, 'data:image'))
                        <img src="{{ $mantenimiento->firma_usuario }}" alt="Firma usuario"
                             class="max-h-24 mx-auto mb-2 border border-slate-200 rounded">
                        @else
                        <div class="h-16 border-b border-slate-300 mb-2"></div>
                        @endif
                        <p class="text-xs font-semibold text-slate-600">Usuario / Receptor</p>
                        <p class="text-xs text-slate-500">{{ $mantenimiento->nombre_firma_usuario ?? '___________________' }}</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
@media print {
    .no-print { display: none !important; }
    body { background: white !important; }
    .bg-slate-50 { background: white !important; }
}
</style>
@endsection
