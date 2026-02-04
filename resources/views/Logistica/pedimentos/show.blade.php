@extends('layouts.erp')

@section('title', 'Detalle de Pedimento - ' . $clave)

@push('scripts')
    <script>
        // Lógica simple para cambiar estado de pago vía AJAX
        function toggleEstadoPago(id, nuevoEstado, btn) {
            const token = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            // Estado visual de carga
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<svg class="animate-spin h-4 w-4 text-current" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>';
            btn.disabled = true;

            fetch(`/logistica/pedimentos/${id}/estado-pago`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': token
                },
                body: JSON.stringify({ estado: nuevoEstado })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Recargar la página para actualizar totales y colores correctamente
                    window.location.reload();
                } else {
                    alert('Error al actualizar');
                    btn.innerHTML = originalHtml;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Error:', error);
                btn.innerHTML = originalHtml;
                btn.disabled = false;
            });
        }
    </script>
@endpush

@section('content')
    <div class="min-h-screen bg-slate-50 pb-12">
        <div class="w-full px-4 sm:px-6 lg:px-8 space-y-6">

            {{-- 1. ENCABEZADO Y NAVEGACIÓN --}}
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 pt-6">
                <div class="flex items-center gap-4">
                    <a href="{{ route('logistica.pedimentos.index') }}" class="p-2 rounded-lg bg-white border border-slate-200 text-slate-500 hover:text-indigo-600 hover:border-indigo-200 transition-all shadow-sm">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </a>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-bold text-slate-900 tracking-tight">Detalle Clave: <span class="text-indigo-600">{{ $clave }}</span></h1>
                            <span class="px-2.5 py-0.5 rounded-full text-xs font-medium bg-slate-100 text-slate-600 border border-slate-200">
                                {{ $operaciones->total() }} Operaciones
                            </span>
                        </div>
                        <p class="text-slate-500 text-sm mt-1">Desglose individual de pedimentos y seguimiento de pagos.</p>
                    </div>
                </div>
                
                {{-- Acciones Globales para esta Clave --}}
                <div class="flex flex-wrap gap-3">
                    {{-- Aquí podrías poner un botón para "Pagar Todo" si quisieras --}}
                </div>
            </div>

            {{-- 2. TABLA DE DETALLES --}}
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-slate-600">
                        <thead class="bg-slate-50 text-slate-700 uppercase font-bold text-xs tracking-wider border-b border-slate-200">
                            <tr>
                                <th class="px-6 py-4">No. Pedimento</th>
                                <th class="px-6 py-4">Cliente</th>
                                <th class="px-6 py-4">Ejecutivo</th>
                                <th class="px-6 py-4">Fecha Embarque</th>
                                <th class="px-6 py-4 text-right">Monto</th>
                                <th class="px-6 py-4 text-center">Estado Pago</th>
                                <th class="px-6 py-4 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse ($operaciones as $op)
                                @php
                                    // Obtenemos el estado desde la relación o default
                                    $estadoPago = optional($op->pedimentoStatus)->estado_pago ?? 'pendiente';
                                    $fechaPago = optional($op->pedimentoStatus)->fecha_pago;
                                @endphp
                                <tr class="hover:bg-slate-50 transition-colors group">
                                    {{-- Pedimento --}}
                                    <td class="px-6 py-4 font-mono font-medium text-slate-900">
                                        {{ $op->no_pedimento }}
                                        <div class="text-[10px] text-slate-400 font-sans">ID: #{{ $op->id }}</div>
                                    </td>

                                    {{-- Cliente --}}
                                    <td class="px-6 py-4">
                                        <div class="font-medium text-slate-800">{{ $op->cliente }}</div>
                                    </td>

                                    {{-- Ejecutivo --}}
                                    <td class="px-6 py-4 text-xs">
                                        <div class="flex items-center gap-2">
                                            <div class="w-6 h-6 rounded-full bg-slate-100 text-slate-500 flex items-center justify-center font-bold">
                                                {{ substr($op->ejecutivo ?? 'U', 0, 1) }}
                                            </div>
                                            {{ $op->ejecutivo }}
                                        </div>
                                    </td>

                                    {{-- Fecha --}}
                                    <td class="px-6 py-4 text-xs">
                                        {{ $op->fecha_embarque ? $op->fecha_embarque->format('d/m/Y') : '--' }}
                                    </td>

                                    {{-- Monto (Placeholder si no tienes campo monto, ajusta según tu modelo) --}}
                                    <td class="px-6 py-4 text-right font-mono text-slate-700">
                                        ${{ number_format($op->monto_pedimento ?? 0, 2) }}
                                    </td>

                                    {{-- Estado Pago (Interactivo) --}}
                                    <td class="px-6 py-4 text-center">
                                        @if($estadoPago === 'pagado')
                                            <button onclick="toggleEstadoPago({{ $op->id }}, 'pendiente', this)" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 border border-emerald-200 hover:bg-emerald-200 transition-colors" title="Clic para marcar como pendiente">
                                                <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                                PAGADO
                                            </button>
                                            @if($fechaPago)
                                                <div class="text-[10px] text-slate-400 mt-1">
                                                    {{ \Carbon\Carbon::parse($fechaPago)->format('d/m/y') }}
                                                </div>
                                            @endif
                                        @else
                                            <button onclick="toggleEstadoPago({{ $op->id }}, 'pagado', this)" class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-700 border border-rose-200 hover:bg-rose-200 transition-colors" title="Clic para marcar como pagado">
                                                <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                                                PENDIENTE
                                            </button>
                                        @endif
                                    </td>

                                    {{-- Acciones --}}
                                    <td class="px-6 py-4 text-right">
                                        {{-- Link a la operación completa en la matriz --}}
                                        {{-- Usamos un modal simulado o link directo si existe la ruta show individual --}}
                                        <button onclick="alert('Funcionalidad para ver detalles completos de la operación ID: {{ $op->id }}')" class="text-slate-400 hover:text-indigo-600 transition-colors">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-slate-400">
                                        No hay operaciones registradas para esta clave.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                {{-- Paginación --}}
                <div class="bg-slate-50 px-6 py-4 border-t border-slate-200">
                    {{ $operaciones->links() }}
                </div>
            </div>
        </div>
    </div>
@endsection