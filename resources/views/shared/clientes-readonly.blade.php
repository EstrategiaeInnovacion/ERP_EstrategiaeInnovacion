@extends('layouts.master')
@section('title', 'Perfil de Clientes — ' . $panel)

@section('content')
<div class="min-h-screen bg-slate-50 pb-12">

    {{-- ENCABEZADO --}}
    <div class="bg-white border-b border-slate-200 mb-8">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6">
            <div class="flex flex-col md:flex-row justify-between items-center gap-4">
                <div>
                    <p class="text-xs font-bold text-indigo-500 uppercase tracking-widest mb-1">
                        <a href="{{ $panelRoute }}" class="hover:underline">{{ $panel }}</a>
                        <span class="mx-1 text-slate-300">/</span> Perfil de Clientes
                    </p>
                    <h1 class="text-3xl font-bold text-slate-900 tracking-tight">Perfil de Clientes</h1>
                    <p class="text-slate-500 mt-1">
                        {{ $clientes->count() }} cliente{{ $clientes->count() !== 1 ? 's' : '' }} registrado{{ $clientes->count() !== 1 ? 's' : '' }}
                        <span class="ml-2 inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-xs font-semibold border border-slate-200">
                            Solo lectura
                        </span>
                    </p>
                </div>
                <a href="{{ $panelRoute }}"
                   class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 text-slate-600 font-bold text-sm rounded-xl hover:bg-slate-50 hover:text-indigo-600 hover:border-indigo-200 transition shadow-sm group">
                    <svg class="w-4 h-4 mr-2 group-hover:-translate-x-1 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                    </svg>
                    Volver al Panel
                </a>
            </div>
        </div>
    </div>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        @if(session('success'))
            <div class="flex items-center gap-3 p-4 rounded-xl border bg-emerald-50 border-emerald-100 text-emerald-700">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
        @endif

        <div class="relative max-w-sm">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" id="buscador" placeholder="Buscar cliente..."
                   class="pl-9 pr-4 py-2 text-sm border border-slate-200 rounded-xl bg-white shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-300 w-full transition">
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            @if($clientes->isEmpty())
                <div class="py-20 text-center">
                    <svg class="mx-auto w-12 h-12 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <p class="text-slate-400 font-medium">No hay clientes registrados.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200">
                                <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">#</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Nombre de Cliente</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Contacto</th>
                                <th class="px-6 py-3 text-left text-xs font-bold text-slate-500 uppercase tracking-wider">Cuestionario</th>
                                <th class="px-6 py-3 text-right text-xs font-bold text-slate-500 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @foreach($clientes as $i => $c)
                                @php $clienteJson = json_encode($c->toArray() + ['perfil' => $c->perfil?->toArray()]); @endphp
                                <tr class="hover:bg-slate-50 transition-colors fila" data-nombre="{{ strtolower($c->nombre) }}">
                                    <td class="px-6 py-4 text-slate-400 font-mono text-xs">{{ $i + 1 }}</td>
                                    <td class="px-6 py-4 font-semibold text-slate-800">{{ $c->nombre }}</td>
                                    <td class="px-6 py-4 text-slate-600 text-sm">{{ $c->perfil?->informante_nombre ?? '—' }}</td>
                                    <td class="px-6 py-4">
                                        @if($c->perfil)
                                            <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold border border-emerald-100">
                                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                                                Completo
                                            </span>
                                        @else
                                            <span class="px-2 py-0.5 rounded-full bg-amber-50 text-amber-700 text-xs font-semibold border border-amber-100">Pendiente</span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-right">
                                        <button type="button"
                                                data-action="ver"
                                                data-cliente="{{ $clienteJson }}"
                                                class="p-1.5 rounded-lg text-slate-400 hover:text-sky-600 hover:bg-sky-50 transition" title="Visualizar información">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                            </svg>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="px-6 py-3 bg-slate-50 border-t border-slate-100">
                    <p class="text-xs text-slate-400">
                        Mostrando <span id="num-visibles">{{ $clientes->count() }}</span> de {{ $clientes->count() }} clientes
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- MODAL — VISUALIZAR INFORMACIÓN COMPLETA --}}
<div id="modal-ver" class="fixed inset-0 z-50 hidden overflow-y-auto" aria-modal="true">
    <div class="flex min-h-full items-start justify-center p-4 pt-6">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="cerrarVerModal()"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl z-10 mb-10 overflow-hidden flex flex-col" style="max-height:92vh">
            <div class="shrink-0 bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
                <div>
                    <h3 class="text-lg font-bold text-slate-900" id="ver-titulo"></h3>
                    <p class="text-xs text-slate-500 mt-0.5">Perfil de Clientes</p>
                </div>
                <button onclick="cerrarVerModal()" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <div class="overflow-y-auto flex-1 p-6 space-y-6" id="ver-cuerpo"></div>
            <div class="shrink-0 bg-white border-t border-slate-200 px-6 py-4 flex justify-end">
                <button onclick="cerrarVerModal()" class="px-4 py-2 text-sm font-semibold text-slate-600 bg-slate-100 hover:bg-slate-200 rounded-xl transition">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script>
function verCliente(c) {
    const p = c.perfil ?? {};
    document.getElementById('ver-titulo').textContent = c.nombre;

    function bool(val) {
        return (val == 1 || val === true)
            ? '<span class="px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-700 text-xs font-semibold border border-emerald-100">Sí</span>'
            : '<span class="px-2 py-0.5 rounded-full bg-slate-100 text-slate-500 text-xs font-semibold">No</span>';
    }
    function txt(val) { return val ? String(val) : '<span class="text-slate-300">—</span>'; }
    function row(label, val) {
        return '<div class="bg-slate-50 rounded-xl p-3"><p class="text-xs text-slate-400 mb-1">' + label + '</p><div class="text-sm text-slate-800">' + val + '</div></div>';
    }
    function boolFecha(boolVal, fecha) {
        return bool(boolVal) + (fecha ? ' <span class="text-xs text-slate-400 ml-1">desde ' + fecha.substring(0,10) + '</span>' : '');
    }
    function section(title, rows) {
        return '<div><h4 class="text-xs font-bold text-slate-500 uppercase tracking-wider mb-3 pb-2 border-b border-slate-100">' + title + '</h4>'
             + '<div class="grid grid-cols-1 md:grid-cols-2 gap-3">' + rows.join('') + '</div></div>';
    }

    document.getElementById('ver-cuerpo').innerHTML = [
        section('Datos Generales', [
            row('Nombre Legal', txt(p.nombre_legal ?? c.nombre)),
            row('Sectores Productivos', txt(p.sectores_productivos)),
            row('Fecha Inicio de Operaciones', txt(p.fecha_inicio_operaciones ? p.fecha_inicio_operaciones.substring(0,10) : null)),
            row('Partes Relacionadas en Extranjero', bool(p.partes_relacionadas_extranjero)),
            row('Nombre del Corporativo', txt(p.nombre_corporativo)),
            row('Ciudad / Estado / País del Corporativo', txt(p.ciudad_estado_pais_corporativo)),
        ]),
        section('Perfil de la Empresa', [
            row('Programa IMMEX', boolFecha(p.tiene_immex, p.immex_fecha)),
            row('Maquiladora', boolFecha(p.es_maquiladora, p.maquiladora_fecha)),
            row('Maquiladora de Servicios', boolFecha(p.maquiladora_servicios, p.maquiladora_servicios_fecha)),
            row('PROSEC', boolFecha(p.tiene_prosec, p.prosec_fecha)),
            row('Transferencias otras IMMEX', bool(p.transferencias_otras_immex)),
            row('Empresa Certificada OEA', bool(p.empresa_certificada_oea)),
            row('Empresa Certificada IVA/EPS', bool(p.empresa_certificada_iva_eps) + (p.iva_eps_modalidad ? ' <span class="text-xs text-slate-500">(' + p.iva_eps_modalidad + ')</span>' : '')),
            row('Utiliza Regla Octava', bool(p.utiliza_regla_octava)),
            row('Automotriz Depósito Fiscal', bool(p.automotriz_deposito_fiscal)),
            row('Proveedor Autopartes', bool(p.proveedor_autopartes)),
            row('Almacén Fiscal', bool(p.utiliza_almacen_fiscal)),
            row('Regla 2ª Clasificación', bool(p.utiliza_regla_2)),
            row('Estudio Precios de Transferencia', bool(p.estudio_precios_transferencia)),
            row('Estudio Valoración Aduanera', bool(p.estudio_valoracion_aduanera)),
            row('Importa Mercancías NOM', bool(p.importa_mercancias_nom)),
            row('Proveedores Sub Maquila', bool(p.proveedores_sub_maquila)),
            row('Importa Precios Estimados', bool(p.importa_precios_estimados)),
            row('Importa con Permisos / Avisos', bool(p.importa_permisos_avisos)),
            row('Destino de Desperdicios', txt(p.destino_desperdicios)),
            row('Cert. Origen TLCAN (Importar)', bool(p.certificados_origen_tlcan)),
            row('Cert. Origen TLCUE (Importar)', bool(p.certificados_origen_tlcue)),
            row('Exporta a EUA / Canadá', bool(p.exporta_eua_canada)),
            row('Exporta a Unión Europea', bool(p.exporta_union_europea)),
            row('Emite Cert. Origen EUA / Canadá', bool(p.emite_certificados_eua_canada)),
            row('Emite Cert. Origen Unión Europea', bool(p.emite_certificados_union_europea)),
        ]),
        section('Sistemas de Información', [
            row('Sistema ERP (Manufactura)', txt(p.sistema_manufactura_erp)),
            row('Sistema Anexo 24', txt(p.sistema_anexo_24)),
            row('Recibe Info Electrónica Agentes Aduanales', bool(p.recibe_info_agentes_aduanales)),
        ]),
        section('Manuales', [
            row('Manual de Procedimientos de Comercio Exterior', bool(p.manual_procedimientos_ce)),
        ]),
        section('Antecedentes', [
            row('Última Auditoría Interna', txt(p.ultima_auditoria_interna)),
            row('Última Auditoría Externa', txt(p.ultima_auditoria_externa)),
            row('Principales Hallazgos', txt(p.principales_hallazgos)),
            row('Observaciones y Multas', txt(p.observaciones_multas)),
            row('Auditado SHCP / SE', boolFecha(p.auditado_shcp_se, p.auditado_shcp_se_fecha)),
        ]),
        section('Volumen de Operaciones', [
            row('Pedimentos Anuales Importación', txt(p.pedimentos_anuales_importacion)),
            row('Pedimentos Anuales Exportación', txt(p.pedimentos_anuales_exportacion)),
            row('Aduana Principal Importación', txt(p.aduana_principal_importacion)),
            row('Aduana Principal Exportación', txt(p.aduana_principal_exportacion)),
        ]),
        section('Proveedores y Clientes', [
            row('Cantidad Proveedores Extranjeros', txt(p.proveedores_extranjeros_cantidad)),
            row('País Origen más Representativo (Importación)', txt(p.pais_origen_importaciones)),
            row('Cantidad Clientes Extranjeros', txt(p.clientes_extranjeros_cantidad)),
            row('País Destino más Frecuente (Exportación)', txt(p.pais_destino_exportaciones)),
            row('Insumos de Importación más Importantes', txt(p.insumos_importacion_importantes)),
            row('Productos de Exportación más Representativos', txt(p.productos_exportacion_representativos)),
            row('Importa Fuera de TLCAN / TLCUEM', bool(p.importa_fuera_tlcan)),
        ]),
        section('Información del Informante', [
            row('Nombre', txt(p.informante_nombre)),
            row('Puesto', txt(p.informante_puesto)),
            row('Fecha', txt(p.informante_fecha ? p.informante_fecha.substring(0,10) : null)),
        ]),
    ].join('');

    document.getElementById('modal-ver').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function cerrarVerModal() {
    document.getElementById('modal-ver').classList.add('hidden');
    document.body.style.overflow = '';
}

document.addEventListener('click', function (e) {
    const btn = e.target.closest('[data-action="ver"]');
    if (!btn) return;
    verCliente(JSON.parse(btn.dataset.cliente));
});

document.getElementById('buscador')?.addEventListener('input', function () {
    const t = this.value.toLowerCase();
    let v = 0;
    document.querySelectorAll('.fila').forEach(f => {
        const show = f.dataset.nombre.includes(t);
        f.style.display = show ? '' : 'none';
        if (show) v++;
    });
    const el = document.getElementById('num-visibles');
    if (el) el.textContent = v;
});
</script>
@endsection
