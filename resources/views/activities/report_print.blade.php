<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Mensual - {{ $cliente }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Estilos específicos para impresión */
        @media print {
            body { 
                -webkit-print-color-adjust: exact; 
                print-color-adjust: exact;
                background-color: white !important;
            }
            /* Ocultar elementos de interfaz al imprimir */
            .no-print { 
                display: none !important; 
            }
            /* Asegurar saltos de página limpios */
            tr { page-break-inside: avoid; }
        }
    </style>
</head>
<body class="bg-white text-slate-800 p-8 max-w-5xl mx-auto">

    {{-- BARRA DE ACCIONES (SOLO VISIBLE EN PANTALLA) --}}
    <div class="no-print mb-8 flex justify-end items-center bg-slate-50 p-4 rounded-xl border border-slate-200">
        <button onclick="window.print()" class="bg-indigo-600 text-white px-6 py-2 rounded-lg font-bold shadow hover:bg-indigo-700 flex items-center gap-2 transition-colors">
            <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            Imprimir / Guardar PDF
        </button>
    </div>

    {{-- ENCABEZADO DEL REPORTE --}}
    <div class="border-b-2 border-slate-800 pb-6 mb-8 flex justify-between items-start">
        <div>
            <h1 class="text-3xl font-black text-slate-900 uppercase tracking-tight">Reporte de Actividades</h1>
            <p class="text-slate-500 mt-1 text-sm font-medium">Informe de gestión mensual</p>
        </div>
        <div class="text-right">
            <h2 class="text-xl font-bold text-indigo-700">{{ $cliente }}</h2>
            <p class="text-slate-600 capitalize font-medium">{{ $fecha->translatedFormat('F Y') }}</p>
        </div>
    </div>

    {{-- RESUMEN EJECUTIVO (KPIs) --}}
    <div class="grid grid-cols-4 gap-6 mb-10">
        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
            <span class="block text-xs font-bold text-slate-400 uppercase">Total Actividades</span>
            <span class="text-2xl font-black text-slate-800">{{ $stats['total'] }}</span>
        </div>
        <div class="bg-emerald-50 p-4 rounded-lg border border-emerald-100">
            <span class="block text-xs font-bold text-emerald-600 uppercase">Completadas</span>
            <span class="text-2xl font-black text-emerald-800">{{ $stats['completadas'] }}</span>
        </div>
        <div class="bg-blue-50 p-4 rounded-lg border border-blue-100">
            <span class="block text-xs font-bold text-blue-600 uppercase">En Proceso/Pendiente</span>
            <span class="text-2xl font-black text-blue-800">{{ $stats['en_proceso'] }}</span>
        </div>
        <div class="bg-slate-50 p-4 rounded-lg border border-slate-200">
            <span class="block text-xs font-bold text-slate-400 uppercase">Tasa de Entrega</span>
            <span class="text-2xl font-black text-indigo-600">{{ $stats['efectividad'] }}%</span>
        </div>
    </div>

    {{-- TABLA DETALLADA --}}
    <table class="w-full text-sm text-left border-collapse">
        <thead>
            <tr class="border-b-2 border-slate-800 text-slate-600 uppercase text-[10px] tracking-wider">
                <th class="py-3 w-24">Fecha</th>
                <th class="py-3">Actividad / Descripción</th>
                <th class="py-3 w-32">Área</th>
                <th class="py-3 w-32">Responsable</th>
                <th class="py-3 w-24 text-center">Estado</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-200">
            @forelse($actividades as $act)
                <tr class="break-inside-avoid">
                    <td class="py-3 font-mono text-slate-500 text-xs align-top">
                        {{ $act->fecha_compromiso->format('d/m/Y') }}
                    </td>
                    <td class="py-3 pr-4 align-top">
                        <p class="font-bold text-slate-800">{{ $act->nombre_actividad }}</p>
                        @if($act->comentarios)
                            <p class="text-xs text-slate-500 mt-1 italic leading-relaxed">{{ Str::limit($act->comentarios, 150) }}</p>
                        @endif
                    </td>
                    <td class="py-3 text-slate-600 text-xs align-top">{{ $act->area }}</td>
                    <td class="py-3 text-slate-600 text-xs align-top">{{ strtok($act->user->name ?? 'N/A', ' ') }}</td>
                    <td class="py-3 text-center align-top">
                        <span class="px-2 py-1 rounded text-[10px] font-bold uppercase block w-fit mx-auto
                            {{ $act->estatus == 'Completado' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-600' }}">
                            {{ $act->estatus }}
                        </span>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="py-12 text-center text-slate-400 italic">No se encontraron actividades registradas para este periodo.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{-- PIE DE PÁGINA --}}
    <div class="mt-12 pt-6 border-t border-slate-200 flex justify-between items-center text-[10px] text-slate-400">
        <p>Generado por Sistema ERP Estrategia e Innovación</p>
        <p>Fecha de emisión: {{ now()->format('d/m/Y H:i') }}</p>
    </div>

</body>
</html>