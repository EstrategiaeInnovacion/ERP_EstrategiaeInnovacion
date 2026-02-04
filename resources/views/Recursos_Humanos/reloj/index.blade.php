@extends('layouts.erp')

@section('title', 'Reloj Checador - Recursos Humanos')

@section('content')
    <section class="relative overflow-hidden bg-gradient-to-br from-white via-blue-50 to-blue-100">
        <div class="absolute inset-0 pointer-events-none">
            <div class="absolute -top-24 -left-16 w-64 h-64 bg-blue-200/40 blur-3xl rounded-full"></div>
            <div class="absolute top-40 -right-24 w-72 h-72 bg-blue-300/30 blur-3xl rounded-full"></div>
            <div class="absolute bottom-0 left-1/2 -translate-x-1/2 w-full h-32 bg-gradient-to-t from-white"></div>
        </div>
        <div class="relative max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-10">
            <div class="grid gap-8 lg:grid-cols-2">
                <div class="rounded-3xl border border-blue-100/80 bg-white/90 p-8 shadow-xl shadow-blue-500/10 backdrop-blur">
                    <p class="text-sm font-semibold text-blue-600 uppercase tracking-wide mb-3">Recursos Humanos</p>
                    <h1 class="text-3xl font-bold text-slate-900 mb-4">Reloj Checador</h1>
                    <p class="text-slate-600 leading-relaxed">
                        Carga el archivo Excel exportado desde el reloj SecureCore/ZKTeco para obtener el detalle de entradas y
                        salidas por colaborador. El sistema valida el periodo y agrupa cada registro del reloj para que puedas
                        revisarlos antes de integrarlos al control interno.
                    </p>
                    <ul class="mt-6 space-y-3 text-sm text-slate-600">
                        <li class="flex items-start gap-3">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-50 text-blue-600 text-xs font-semibold">1</span>
                            Selecciona únicamente archivos .xls o .xlsx descargados del reloj.
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-50 text-blue-600 text-xs font-semibold">2</span>
                            El sistema identifica automáticamente el periodo, empleados y checadas.
                        </li>
                        <li class="flex items-start gap-3">
                            <span class="flex h-6 w-6 items-center justify-center rounded-full bg-blue-50 text-blue-600 text-xs font-semibold">3</span>
                            Visualiza cada registro agrupado por colaborador para validar la información.
                        </li>
                    </ul>
                </div>
                <div class="rounded-3xl border border-blue-100/80 bg-white/95 p-8 shadow-xl shadow-blue-500/10 backdrop-blur">
                    <h2 class="text-xl font-semibold text-slate-900 mb-1">Sube tu archivo</h2>
                    <p class="text-sm text-slate-600 mb-5">Solo se permite formato de hoja de cálculo (.xls, .xlsx).</p>
                    @if ($errors->any())
                        <div class="mb-5 rounded-2xl border border-red-200 bg-red-50/80 p-4 text-sm text-red-700">
                            <p class="font-semibold">Por favor corrige los siguientes errores:</p>
                            <ul class="mt-2 list-disc pl-5 space-y-1">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    <form action="{{ route('rh.reloj.procesar') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                        @csrf
                        <label for="archivo" class="block">
                            <span class="sr-only">Archivo del reloj</span>
                            <div class="group flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-blue-200 bg-blue-50/40 px-6 py-10 text-center text-sm text-slate-500 transition hover:border-blue-400 hover:bg-white">
                                <svg class="h-10 w-10 text-blue-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 15a4 4 0 014-4h10a4 4 0 014 4m-7-8V3m0 0l-3 3m3-3l3 3" />
                                </svg>
                                <p class="font-semibold text-slate-700">Arrastra tu archivo o haz clic para seleccionarlo</p>
                                <p class="mt-1 text-xs text-slate-500">Solo .xls o .xlsx — tamaño máximo 10 MB.</p>
                                <input id="archivo" name="archivo" type="file" accept=".xls,.xlsx,.xlsm" class="sr-only" required>
                            </div>
                        </label>
                        <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-500/30 transition hover:from-blue-700 hover:to-blue-800">
                            Procesar archivo
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M5 12h14m0 0l-4 4m4-4l-4-4" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>

            @if(isset($resultado))
                <div class="mt-12 space-y-10">
                    <div class="rounded-3xl border border-blue-100/80 bg-white/95 p-6 shadow-lg shadow-blue-500/10 backdrop-blur">
                        <div class="flex flex-wrap gap-6">
                            @php $periodo = $resultado['periodo'] ?? null; @endphp
                            <div class="flex-1 min-w-[200px]">
                                <p class="text-xs uppercase tracking-wide text-slate-500">Periodo detectado</p>
                                <p class="text-lg font-semibold text-slate-900 mt-1">
                                    @if($periodo)
                                        {{ optional($periodo['inicio'])->format('d/m/Y') }} — {{ optional($periodo['fin'])->format('d/m/Y') }}
                                    @else
                                        No identificado
                                    @endif
                                </p>
                            </div>
                            <div class="flex-1 min-w-[160px]">
                                <p class="text-xs uppercase tracking-wide text-slate-500">Empleados detectados</p>
                                <p class="text-3xl font-bold text-blue-600">{{ $resultado['empleados_procesados'] ?? 0 }}</p>
                            </div>
                            <div class="flex-1 min-w-[160px]">
                                <p class="text-xs uppercase tracking-wide text-slate-500">Registros del reloj</p>
                                <p class="text-3xl font-bold text-blue-600">{{ $resultado['total_registros'] ?? 0 }}</p>
                            </div>
                            <div class="flex-1 min-w-[160px]">
                                <p class="text-xs uppercase tracking-wide text-slate-500">Hojas procesadas</p>
                                <p class="text-3xl font-bold text-blue-600">{{ $resultado['hojas_procesadas'] ?? 0 }}</p>
                            </div>
                        </div>
                    </div>

                    @if(isset($grupos) && $grupos->count())
                        <div class="space-y-8">
                            @foreach($grupos as $grupo)
                                <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-lg shadow-slate-200/60">
                                    <div class="flex flex-wrap items-center justify-between gap-4 border-b border-slate-100 pb-4">
                                        <div>
                                            <p class="text-sm text-slate-500">Empleado</p>
                                            <h3 class="text-xl font-semibold text-slate-900">{{ $grupo['nombre'] ?? 'DESCONOCIDO' }}</h3>
                                            <p class="text-xs text-slate-500">No. {{ $grupo['empleado_no'] ?: 'N/D' }}</p>
                                        </div>
                                        <div class="rounded-2xl bg-blue-50 px-4 py-2 text-sm font-semibold text-blue-700">
                                            {{ $grupo['total'] }} registros detectados
                                        </div>
                                    </div>
                                    <div class="mt-4 overflow-x-auto">
                                        <table class="min-w-full divide-y divide-slate-100 text-sm">
                                            <thead class="text-xs uppercase tracking-wide text-slate-500">
                                                <tr>
                                                    <th class="py-3 text-left">Fecha</th>
                                                    <th class="py-3 text-left">Entrada</th>
                                                    <th class="py-3 text-left">Salida</th>
                                                    <th class="py-3 text-left">Checadas detectadas</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-slate-100 text-slate-700">
                                                @foreach($grupo['registros'] as $registro)
                                                    <tr>
                                                        <td class="py-3">{{ \Carbon\Carbon::parse($registro['fecha'])->format('d/m/Y') }}</td>
                                                        <td class="py-3 font-semibold text-green-600">{{ $registro['entrada'] ?? '—' }}</td>
                                                        <td class="py-3 font-semibold text-amber-600">{{ $registro['salida'] ?? '—' }}</td>
                                                        <td class="py-3">
                                                            <div class="flex flex-wrap gap-2">
                                                                @foreach($registro['checadas'] as $checado)
                                                                    <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-medium text-slate-700">{{ $checado }}</span>
                                                                @endforeach
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <div class="rounded-3xl border border-dashed border-slate-200 bg-white/70 p-8 text-center text-slate-500">
                            No se detectaron registros en el archivo proporcionado.
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </section>
@endsection
