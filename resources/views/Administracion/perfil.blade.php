@extends('layouts.master')
@section('title', 'Perfil Comercio Exterior — ' . $cliente->nombre)

@php
    $yn = fn($v) => $v ? 'Sí' : 'No';
    $old = fn(string $k) => old($k, $perfil->$k ?? null);
    $chk = fn(string $k) => old($k, $perfil->$k ?? false) ? 'checked' : '';
    $date = fn(string $k) => $perfil->$k ? \Carbon\Carbon::parse($perfil->$k)->format('Y-m-d') : '';
@endphp

@section('content')
<div class="min-h-screen bg-slate-50 pb-16">

    {{-- HEADER --}}
    <div class="bg-white border-b border-slate-200 mb-8">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 py-6 flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <p class="text-xs font-bold text-indigo-500 uppercase tracking-widest mb-1">
                    <a href="{{ route('administracion.dashboard') }}" class="hover:underline">Administración</a>
                    <span class="mx-1 text-slate-300">/</span>
                    <a href="{{ route('administracion.clientes.index') }}" class="hover:underline">Clientes</a>
                    <span class="mx-1 text-slate-300">/</span> Perfil
                </p>
                <h1 class="text-2xl font-bold text-slate-900">{{ $cliente->nombre }}</h1>
                <p class="text-slate-500 text-sm mt-0.5">Cuestionario de Perfil de Comercio Exterior</p>
            </div>
            <a href="{{ route('administracion.clientes.index') }}"
               class="inline-flex items-center px-4 py-2 bg-white border border-slate-200 text-slate-600 font-bold text-sm rounded-xl hover:bg-slate-50 hover:text-indigo-600 hover:border-indigo-200 transition shadow-sm group">
                <svg class="w-4 h-4 mr-2 group-hover:-translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Volver
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 mb-6">
            <div class="flex items-center gap-3 p-4 rounded-xl border bg-emerald-50 border-emerald-100 text-emerald-700">
                <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span class="text-sm font-medium">{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <form action="{{ route('administracion.clientes.perfil.guardar', $cliente) }}" method="POST"
          class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
        @csrf

        {{-- ══ 1. DATOS GENERALES ══════════════════════════════════════════ --}}
        <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 bg-red-700 flex items-center gap-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <h2 class="text-white font-bold text-sm uppercase tracking-wider">Datos Generales de la Empresa</h2>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                <x-perfil-field label="Nombre Legal de la Empresa" name="nombre_legal" :value="$old('nombre_legal')" class="md:col-span-2"/>
                <x-perfil-field label="Sectores Productivos Usados" name="sectores_productivos" :value="$old('sectores_productivos')" class="md:col-span-2"/>
                <x-perfil-field label="Fecha de Inicio de Operaciones" name="fecha_inicio_operaciones" type="date" :value="$date('fecha_inicio_operaciones')"/>
                <x-perfil-yn    label="Realiza operaciones con partes relacionadas en el extranjero" name="partes_relacionadas_extranjero" :checked="$chk('partes_relacionadas_extranjero')"/>
                <x-perfil-field label="Nombre del Corporativo" name="nombre_corporativo" :value="$old('nombre_corporativo')"/>
                <x-perfil-field label="Ciudad, Estado y País del Corporativo" name="ciudad_estado_pais_corporativo" :value="$old('ciudad_estado_pais_corporativo')"/>
            </div>
        </section>

        {{-- ══ 2. PROGRAMAS / CERTIF. ══════════════════════════════════════ --}}
        <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 bg-red-700 flex items-center gap-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <h2 class="text-white font-bold text-sm uppercase tracking-wider">Programas y Certificaciones</h2>
            </div>
            <div class="p-6 space-y-4">
                @php
                    $programas = [
                        ['yn' => 'tiene_immex',          'label' => 'Cuentan con Programa IMMEX',           'fecha' => 'immex_fecha'],
                        ['yn' => 'es_maquiladora',       'label' => 'Maquiladora',                          'fecha' => 'maquiladora_fecha'],
                        ['yn' => 'maquiladora_servicios','label' => 'Maquiladora de Servicios',             'fecha' => 'maquiladora_servicios_fecha'],
                        ['yn' => 'tiene_prosec',         'label' => 'PROSEC',                               'fecha' => 'prosec_fecha'],
                    ];
                @endphp
                @foreach($programas as $p)
                    <div class="flex flex-wrap items-center gap-4 py-3 border-b border-slate-100 last:border-0">
                        <span class="text-sm text-slate-700 w-64 flex-shrink-0">{{ $p['label'] }}</span>
                        <x-perfil-yn-inline name="{{ $p['yn'] }}" :checked="$chk($p['yn'])"/>
                        <div class="flex items-center gap-2 ml-auto">
                            <label class="text-xs text-slate-500 font-medium">Desde:</label>
                            <input type="date" name="{{ $p['fecha'] }}" value="{{ $date($p['fecha']) }}"
                                   class="text-sm border border-slate-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                        </div>
                    </div>
                @endforeach

                {{-- Sin fecha --}}
                @php
                    $sinFecha = [
                        ['yn' => 'transferencias_otras_immex', 'label' => 'Realiza transferencias con otras IMMEX'],
                        ['yn' => 'empresa_certificada_oea',    'label' => 'Empresa Certificada OEA'],
                        ['yn' => 'utiliza_regla_octava',       'label' => 'Utiliza Regla Octava'],
                        ['yn' => 'automotriz_deposito_fiscal', 'label' => 'Autorización Ind. Automotriz Terminal (Depósito Fiscal Automotriz)'],
                        ['yn' => 'proveedor_autopartes',       'label' => 'Es Proveedor Nacional o Industria de Autopartes'],
                    ];
                @endphp
                @foreach($sinFecha as $s)
                    <div class="flex flex-wrap items-center gap-4 py-3 border-b border-slate-100 last:border-0">
                        <span class="text-sm text-slate-700 flex-1">{{ $s['label'] }}</span>
                        <x-perfil-yn-inline name="{{ $s['yn'] }}" :checked="$chk($s['yn'])"/>
                    </div>
                @endforeach

                {{-- IVA/EPS con modalidad --}}
                <div class="flex flex-wrap items-center gap-4 py-3 border-b border-slate-100">
                    <span class="text-sm text-slate-700 flex-1">Empresa Certificada IVA/EPS</span>
                    <x-perfil-yn-inline name="empresa_certificada_iva_eps" :checked="$chk('empresa_certificada_iva_eps')"/>
                    <div class="flex items-center gap-2">
                        <label class="text-xs text-slate-500 font-medium">Modalidad:</label>
                        <input type="text" name="iva_eps_modalidad" value="{{ $old('iva_eps_modalidad') }}" placeholder="Modalidad"
                               class="text-sm border border-slate-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-300 w-40">
                    </div>
                </div>
            </div>
        </section>

        {{-- ══ 3. PERFIL DE LA EMPRESA ════════════════════════════════════ --}}
        <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 bg-red-700 flex items-center gap-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17V7m0 10a2 2 0 01-2 2H5a2 2 0 01-2-2V7a2 2 0 012-2h2a2 2 0 012 2m0 10a2 2 0 002 2h2a2 2 0 002-2M9 7a2 2 0 012-2h2a2 2 0 012 2m0 10V7"/></svg>
                <h2 class="text-white font-bold text-sm uppercase tracking-wider">Perfil de la Empresa</h2>
            </div>
            <div class="p-6 space-y-3">
                @php
                    $perfilRows = [
                        ['yn' => 'utiliza_almacen_fiscal',          'label' => 'Utilizan Almacén Fiscal'],
                        ['yn' => 'utiliza_regla_2',                 'label' => 'Utilizan la Regla 2ª para la clasificación de mercancías'],
                        ['yn' => 'estudio_precios_transferencia',   'label' => 'Cuenta con Estudio de Precios de Transferencia'],
                        ['yn' => 'estudio_valoracion_aduanera',     'label' => 'Cuenta con Estudio de Valoración Aduanera'],
                        ['yn' => 'importa_mercancias_nom',          'label' => 'Importa Mercancías Sujetas a NOM'],
                        ['yn' => 'proveedores_sub_maquila',         'label' => 'Cuenta con proveedores de Sub Maquila'],
                        ['yn' => 'importa_precios_estimados',       'label' => 'Importa Mercancías Sujetas a Precios Estimados'],
                        ['yn' => 'importa_permisos_avisos',         'label' => 'Importa Mercancías Sujetas a Permisos o Avisos de Importación'],
                        ['yn' => 'certificados_origen_tlcan',       'label' => 'Utiliza Certificados de Origen TLCAN para Importar mercancías'],
                        ['yn' => 'certificados_origen_tlcue',       'label' => 'Utiliza Certificados de Origen TLCUE para Importar mercancías'],
                        ['yn' => 'exporta_eua_canada',              'label' => 'Exporta a EUA y Canadá mercancías manufacturadas'],
                        ['yn' => 'exporta_union_europea',           'label' => 'Exporta a la Unión Europea mercancías manufacturadas'],
                        ['yn' => 'emite_certificados_eua_canada',   'label' => 'Emite Certificados de Origen a sus clientes en EUA y Canadá'],
                        ['yn' => 'emite_certificados_union_europea','label' => 'Emite Certificados de Origen a sus clientes en la Unión Europea'],
                    ];
                @endphp
                @foreach($perfilRows as $r)
                    <div class="flex flex-wrap items-center gap-4 py-3 border-b border-slate-100 last:border-0">
                        <span class="text-sm text-slate-700 flex-1">{{ $r['label'] }}</span>
                        <x-perfil-yn-inline name="{{ $r['yn'] }}" :checked="$chk($r['yn'])"/>
                    </div>
                @endforeach

                <div class="pt-2">
                    <label class="block text-xs font-bold text-slate-600 uppercase tracking-wide mb-1.5">
                        ¿Cuál es el destino de los desperdicios?
                    </label>
                    <input type="text" name="destino_desperdicios" value="{{ $old('destino_desperdicios') }}"
                           class="w-full text-sm border border-slate-200 rounded-xl px-3 py-2 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                </div>
            </div>
        </section>

        {{-- ══ 4. SISTEMAS DE INFORMACIÓN ═════════════════════════════════ --}}
        <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 bg-red-700 flex items-center gap-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                <h2 class="text-white font-bold text-sm uppercase tracking-wider">Sistemas de Información</h2>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                <x-perfil-field label="Nombre de su sistema de Manufactura (ERP)" name="sistema_manufactura_erp" :value="$old('sistema_manufactura_erp')"/>
                <x-perfil-field label="Nombre de su sistema de Anexo 24" name="sistema_anexo_24" :value="$old('sistema_anexo_24')"/>
                <div class="flex items-center gap-4 py-2 md:col-span-2">
                    <span class="text-sm text-slate-700 flex-1">Recibe información electrónica de sus Agentes Aduanales</span>
                    <x-perfil-yn-inline name="recibe_info_agentes_aduanales" :checked="$chk('recibe_info_agentes_aduanales')"/>
                </div>
            </div>
        </section>

        {{-- ══ 5. MANUALES ════════════════════════════════════════════════ --}}
        <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 bg-red-700 flex items-center gap-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
                <h2 class="text-white font-bold text-sm uppercase tracking-wider">Manuales</h2>
            </div>
            <div class="p-6">
                <div class="flex items-center gap-4">
                    <span class="text-sm text-slate-700 flex-1">Tienen algún manual de Procedimientos de Comercio Exterior</span>
                    <x-perfil-yn-inline name="manual_procedimientos_ce" :checked="$chk('manual_procedimientos_ce')"/>
                </div>
            </div>
        </section>

        {{-- ══ 6. ANTECEDENTES ════════════════════════════════════════════ --}}
        <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 bg-red-700 flex items-center gap-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                <h2 class="text-white font-bold text-sm uppercase tracking-wider">Antecedentes</h2>
            </div>
            <div class="p-6 space-y-5">
                <x-perfil-field label="Fecha de la última auditoría interna y responsable de la realización" name="ultima_auditoria_interna" :value="$old('ultima_auditoria_interna')" class="md:col-span-2"/>
                <x-perfil-field label="Fecha de la última auditoría externa y firma auditora" name="ultima_auditoria_externa" :value="$old('ultima_auditoria_externa')"/>
                <x-perfil-textarea label="Principales Hallazgos" name="principales_hallazgos" :value="$old('principales_hallazgos')"/>
                <div class="flex flex-wrap items-center gap-4 py-2 border-t border-slate-100">
                    <span class="text-sm text-slate-700 flex-1">Han sido auditadas por la SHCP y SE en operaciones de Com. Exterior</span>
                    <x-perfil-yn-inline name="auditado_shcp_se" :checked="$chk('auditado_shcp_se')"/>
                    <div class="flex items-center gap-2">
                        <label class="text-xs text-slate-500 font-medium">Fecha:</label>
                        <input type="date" name="auditado_shcp_se_fecha" value="{{ $date('auditado_shcp_se_fecha') }}"
                               class="text-sm border border-slate-200 rounded-lg px-3 py-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-300">
                    </div>
                </div>
                <x-perfil-textarea label="Observaciones y en su caso, multas fincadas y/o pagadas" name="observaciones_multas" :value="$old('observaciones_multas')"/>
            </div>
        </section>

        {{-- ══ 7. VOLUMEN DE OPERACIONES ══════════════════════════════════ --}}
        <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 bg-red-700 flex items-center gap-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                <h2 class="text-white font-bold text-sm uppercase tracking-wider">Volumen de Operaciones</h2>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                <x-perfil-field label="Cantidad de Pedimentos Anuales de Importación" name="pedimentos_anuales_importacion" type="number" :value="$old('pedimentos_anuales_importacion')"/>
                <x-perfil-field label="Cantidad de Pedimentos Anuales de Exportación" name="pedimentos_anuales_exportacion" type="number" :value="$old('pedimentos_anuales_exportacion')"/>
                <x-perfil-field label="Principal Aduana de Importación" name="aduana_principal_importacion" :value="$old('aduana_principal_importacion')"/>
                <x-perfil-field label="Principal Aduana de Exportación" name="aduana_principal_exportacion" :value="$old('aduana_principal_exportacion')"/>
            </div>
        </section>

        {{-- ══ 8. PROVEEDORES Y CLIENTES ══════════════════════════════════ --}}
        <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 bg-red-700 flex items-center gap-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <h2 class="text-white font-bold text-sm uppercase tracking-wider">Proveedores y Clientes</h2>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-2 gap-5">
                <x-perfil-field label="Cantidad de Proveedores Extranjeros durante el ejercicio" name="proveedores_extranjeros_cantidad" type="number" :value="$old('proveedores_extranjeros_cantidad')"/>
                <x-perfil-field label="País de origen más representativo de las importaciones" name="pais_origen_importaciones" :value="$old('pais_origen_importaciones')"/>

                <div class="flex items-center gap-4 py-2 md:col-span-2 border-t border-slate-100">
                    <span class="text-sm text-slate-700 flex-1">Importa materiales de la región fuera del TLCAN y TLCUEM</span>
                    <x-perfil-yn-inline name="importa_fuera_tlcan" :checked="$chk('importa_fuera_tlcan')"/>
                </div>

                <x-perfil-field label="Cantidad de Clientes Extranjeros durante el ejercicio" name="clientes_extranjeros_cantidad" type="number" :value="$old('clientes_extranjeros_cantidad')"/>
                <x-perfil-field label="País de Destino más frecuente de sus exportaciones" name="pais_destino_exportaciones" :value="$old('pais_destino_exportaciones')"/>
                <x-perfil-textarea label="Insumos de Importación más importantes" name="insumos_importacion_importantes" :value="$old('insumos_importacion_importantes')"/>
                <x-perfil-textarea label="Productos de Exportación más representativos" name="productos_exportacion_representativos" :value="$old('productos_exportacion_representativos')"/>
            </div>
        </section>

        {{-- ══ 9. INFORMANTE ══════════════════════════════════════════════ --}}
        <section class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
            <div class="px-6 py-4 bg-slate-700 flex items-center gap-3">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <h2 class="text-white font-bold text-sm uppercase tracking-wider">Información Proporcionada Por</h2>
            </div>
            <div class="p-6 grid grid-cols-1 md:grid-cols-3 gap-5">
                <x-perfil-field label="Nombre" name="informante_nombre" :value="$old('informante_nombre')"/>
                <x-perfil-field label="Puesto" name="informante_puesto" :value="$old('informante_puesto')"/>
                <x-perfil-field label="Fecha" name="informante_fecha" type="date" :value="$date('informante_fecha')"/>
            </div>
        </section>

        {{-- GUARDAR --}}
        <div class="flex justify-end gap-3 pb-4">
            <a href="{{ route('administracion.clientes.index') }}"
               class="px-5 py-2.5 text-sm font-semibold text-slate-600 bg-white border border-slate-200 rounded-xl hover:bg-slate-50 transition shadow-sm">
                Cancelar
            </a>
            <button type="submit"
                    class="px-6 py-2.5 text-sm font-bold text-white bg-indigo-600 hover:bg-indigo-700 rounded-xl transition shadow-lg shadow-indigo-200 hover:-translate-y-0.5">
                Guardar Perfil
            </button>
        </div>
    </form>
</div>
@endsection
