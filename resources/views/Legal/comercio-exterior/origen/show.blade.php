@extends('layouts.erp')

@section('title', 'Análisis de Origen – ' . $bom->clave)

@section('content')
<div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8" x-data="origenChat({{ $bom->id }})">

    {{-- Header --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2 text-sm text-slate-400 mb-1">
                <a href="{{ route('legal.dashboard') }}" class="hover:text-indigo-600">Legal</a>
                <span>/</span>
                <a href="{{ route('legal.ce.bom.index') }}" class="hover:text-indigo-600">BOMs</a>
                <span>/</span>
                <a href="{{ route('legal.ce.bom.show', $bom) }}" class="hover:text-indigo-600">{{ $bom->clave }}</a>
                <span>/</span>
                <span class="text-slate-600">Análisis</span>
            </div>
            <h1 class="text-2xl font-bold text-slate-900">Análisis de Origen T-MEC</h1>
            <p class="text-sm text-slate-500 mt-0.5 font-mono">{{ $bom->clave }} {{ $bom->nombre ? '– ' . $bom->nombre : '' }}</p>
        </div>
        <div class="flex gap-3 shrink-0">
            @if($analysis)
            <a href="{{ route('legal.ce.origen.export', $bom) }}"
               class="inline-flex items-center px-4 py-2 bg-emerald-600 text-white text-sm font-semibold rounded-xl hover:bg-emerald-700 transition shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                </svg>
                Exportar Excel
            </a>
            @if($analysis->qualifies)
            <a href="{{ route('legal.ce.origen.cert', $bom) }}"
               class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-semibold rounded-xl hover:bg-blue-700 transition shadow-sm">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Certificado USMCA
            </a>
            @endif
            @endif
            <form method="POST" action="{{ route('legal.ce.origen.store', $bom) }}">
                @csrf
                <button type="submit"
                        class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition shadow-sm">
                    Re-analizar
                </button>
            </form>
        </div>
    </div>

    @if($analysis)
    {{-- ── Dictamen principal ──────────────────────────────────────────── --}}
    <div class="mb-6 p-5 rounded-2xl border-2 shadow-sm {{ $analysis->qualifies ? 'bg-emerald-50 border-emerald-200' : 'bg-red-50 border-red-200' }}">
        <div class="flex items-center gap-4 mb-4">
            <div class="w-12 h-12 rounded-2xl flex items-center justify-center shadow-sm {{ $analysis->qualifies ? 'bg-emerald-500 text-white' : 'bg-red-500 text-white' }}">
                @if($analysis->qualifies)
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/></svg>
                @else
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                @endif
            </div>
            <div>
                <h2 class="text-xl font-bold {{ $analysis->qualifies ? 'text-emerald-800' : 'text-red-800' }}">
                    {{ $analysis->qualifies ? 'CALIFICA COMO ORIGINARIO T-MEC' : 'NO CALIFICA COMO ORIGINARIO T-MEC' }}
                </h2>
                <p class="text-sm {{ $analysis->qualifies ? 'text-emerald-600' : 'text-red-600' }}">
                    Criterio {{ $analysis->origin_criterion }} &bull;
                    VCR {{ $analysis->rvc_percentage }}% vs umbral {{ $analysis->rvc_threshold }}% &bull;
                    Vigente hasta {{ $analysis->valid_until?->format('d/m/Y') }}
                </p>
            </div>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
            @php $cr = $analysis->copilot_response ?? []; @endphp
            @foreach([
                ['label' => 'Col P – Cambio de fracción', 'value' => $cr['col_p'] ?? null],
                ['label' => 'Col Q – Cumple requisitos',  'value' => $cr['col_q'] ?? null],
                ['label' => 'Col R – Califica originario','value' => $cr['col_r'] ?? null],
                ['label' => 'Col V – Criterio de origen', 'value' => $cr['col_v'] ?? null],
            ] as $col)
            @if($col['value'])
            <div class="bg-white/60 rounded-xl p-3">
                <p class="text-xs font-semibold text-slate-500 mb-1">{{ $col['label'] }}</p>
                <p class="text-slate-800 text-xs">{{ $col['value'] }}</p>
            </div>
            @endif
            @endforeach
        </div>

        @if(!empty($cr['col_s']))
        <div class="mt-4 bg-white/60 rounded-xl p-3">
            <p class="text-xs font-semibold text-slate-500 mb-1">Col S – Regla de origen aplicada</p>
            <p class="text-slate-700 text-xs leading-relaxed">{{ $cr['col_s'] }}</p>
        </div>
        @endif
    </div>

    {{-- ── Métricas cálculo ─────────────────────────────────────────────── --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-6">
        <div class="bg-white rounded-2xl border border-slate-200 p-4">
            <p class="text-xs text-slate-500 mb-1">Fracción PT</p>
            <p class="font-mono font-bold text-slate-900 text-sm">{{ $analysis->fg_fraction ?: $calc['fg_fraction'] }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4">
            <p class="text-xs text-slate-500 mb-1">Precio PT (CN)</p>
            <p class="font-bold text-slate-900">${{ number_format($analysis->fg_price_usd, 2) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4">
            <p class="text-xs text-slate-500 mb-1">Costo No Orig.</p>
            <p class="font-bold text-slate-900">${{ number_format($analysis->non_orig_cost_usd, 2) }}</p>
        </div>
        <div class="bg-white rounded-2xl border border-slate-200 p-4">
            <p class="text-xs text-slate-500 mb-1">CC cumple</p>
            <p class="font-bold {{ $analysis->cc_complies ? 'text-emerald-600' : 'text-red-600' }}">
                {{ $analysis->cc_complies ? 'SÍ' : 'NO' }}
            </p>
        </div>
    </div>

    {{-- ── Regla aplicada ───────────────────────────────────────────────── --}}
    @if($ruleDetails)
    <div class="mb-6 bg-white rounded-2xl border border-slate-200 p-5 shadow-sm">
        <h3 class="text-sm font-semibold text-slate-700 mb-2">Regla de Origen Aplicada</h3>
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-3 text-xs">
            <div>
                <p class="text-slate-400">Fracción Excel</p>
                <p class="font-mono font-semibold text-indigo-700">{{ $ruleDetails['fraccion'] }}</p>
            </div>
            <div>
                <p class="text-slate-400">Fuente</p>
                <p class="font-semibold text-slate-700">{{ $ruleDetails['from_apendice'] ? 'Apéndice Automotriz' : 'Sección B' }}</p>
            </div>
            @if($ruleDetails['tipo_vehiculo_pt'])
            <div>
                <p class="text-slate-400">Tipo Vehículo</p>
                <p class="font-semibold text-slate-700">{{ $ruleDetails['tipo_vehiculo_pt'] }}</p>
            </div>
            @endif
            @if($ruleDetails['vcr_umbral_pct'])
            <div>
                <p class="text-slate-400">Umbral VCR</p>
                <p class="font-semibold text-slate-700">{{ $ruleDetails['vcr_umbral_pct'] }}% ({{ $ruleDetails['vcr_metodo'] ?? 'VT' }})</p>
            </div>
            @endif
        </div>
        <p class="text-xs text-slate-600 leading-relaxed bg-slate-50 p-3 rounded-xl border border-slate-100">{{ $ruleDetails['regla_texto'] }}</p>
    </div>
    @endif

    {{-- ── Chat IA ──────────────────────────────────────────────────────── --}}
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm">
        <div class="px-6 py-4 border-b border-slate-100 flex items-center justify-between">
            <div>
                <h3 class="text-sm font-semibold text-slate-900">Asistente de Corrección IA</h3>
                <p class="text-xs text-slate-400 mt-0.5">Consulta dudas o solicita re-análisis con parámetros distintos. Requiere GROQ_API_KEY.</p>
            </div>
        </div>

        {{-- Mensajes --}}
        <div class="h-72 overflow-y-auto p-4 space-y-3" id="chat-messages">
            <template x-for="(msg, idx) in historial" :key="idx">
                <div>
                    <div x-show="msg.user" class="flex justify-end">
                        <div class="max-w-sm bg-indigo-600 text-white text-xs rounded-2xl rounded-br-sm px-4 py-2" x-text="msg.user"></div>
                    </div>
                    <div x-show="msg.assistant" class="flex justify-start mt-1">
                        <div class="max-w-lg bg-slate-100 text-slate-800 text-xs rounded-2xl rounded-bl-sm px-4 py-2 whitespace-pre-wrap" x-html="msg.assistant"></div>
                    </div>
                </div>
            </template>
            <div x-show="cargando" class="flex justify-start">
                <div class="bg-slate-100 rounded-2xl px-4 py-2">
                    <div class="flex gap-1">
                        <span class="w-2 h-2 bg-slate-400 rounded-full animate-bounce" style="animation-delay:0ms"></span>
                        <span class="w-2 h-2 bg-slate-400 rounded-full animate-bounce" style="animation-delay:150ms"></span>
                        <span class="w-2 h-2 bg-slate-400 rounded-full animate-bounce" style="animation-delay:300ms"></span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Input --}}
        <div class="px-4 py-3 border-t border-slate-100 flex gap-2">
            <input type="text" x-model="mensaje" @keydown.enter="enviarMensaje()"
                   placeholder="Ej: '¿Qué pasa si el umbral es 62.5%?' o 'La fracción es incorrecta, usa 8708.94'"
                   class="flex-1 rounded-xl border-slate-300 text-sm focus:border-indigo-400 focus:ring-indigo-400"
                   :disabled="cargando">
            <button @click="enviarMensaje()" :disabled="cargando || !mensaje.trim()"
                    class="px-4 py-2 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 disabled:opacity-40 transition">
                Enviar
            </button>
        </div>
    </div>

    @else
    {{-- Sin análisis --}}
    <div class="bg-white rounded-2xl border border-slate-200 p-16 text-center shadow-sm">
        <div class="w-14 h-14 bg-slate-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
            <svg class="w-7 h-7 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
            </svg>
        </div>
        <h3 class="text-lg font-semibold text-slate-800 mb-1">Sin análisis registrado</h3>
        <p class="text-sm text-slate-500 mb-6">Ejecuta el análisis para obtener el dictamen de origen T-MEC.</p>
        <form method="POST" action="{{ route('legal.ce.origen.store', $bom) }}" class="inline">
            @csrf
            <button type="submit"
                    class="inline-flex items-center px-5 py-2.5 bg-indigo-600 text-white text-sm font-semibold rounded-xl hover:bg-indigo-700 transition shadow-sm">
                Ejecutar análisis
            </button>
        </form>
    </div>
    @endif

</div>
@endsection

@push('scripts')
<script>
function origenChat(bomId) {
    return {
        mensaje: '',
        historial: [],
        cargando: false,

        async enviarMensaje() {
            const msg = this.mensaje.trim();
            if (!msg || this.cargando) return;

            this.historial.push({ user: msg, assistant: null });
            this.mensaje = '';
            this.cargando = true;
            this.$nextTick(() => this.scrollChat());

            try {
                const res = await fetch(`{{ url('legal/comercio-exterior/origen') }}/${bomId}/chat`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        message: msg,
                        history: this.historial.slice(-6),
                    }),
                });
                const data = await res.json();
                if (data.assistant) {
                    this.historial[this.historial.length - 1].assistant = data.assistant.replace(/\n/g, '<br>');
                } else if (data.error) {
                    this.historial[this.historial.length - 1].assistant = '⚠ ' + data.error;
                }
            } catch (e) {
                this.historial[this.historial.length - 1].assistant = '⚠ Error de conexión.';
            } finally {
                this.cargando = false;
                this.$nextTick(() => this.scrollChat());
            }
        },

        scrollChat() {
            const el = document.getElementById('chat-messages');
            if (el) el.scrollTop = el.scrollHeight;
        },
    };
}
</script>
@endpush
