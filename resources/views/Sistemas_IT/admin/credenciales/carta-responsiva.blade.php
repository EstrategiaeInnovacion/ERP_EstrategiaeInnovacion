<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Carta Responsiva — {{ $user->name }}</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
    <script defer src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        /* ── Base ── */
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            color: #1a1a1a;
            background: #f0f0f0;
        }

        /* ── Screen wrapper ── */
        .screen-wrapper {
            min-height: 100vh;
            padding: 24px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 32px;
        }

        /* ── Toolbar (screen only) ── */
        .toolbar {
            position: sticky;
            top: 0;
            z-index: 100;
            width: 100%;
            max-width: 900px;
            background: #1e293b;
            border-radius: 12px;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,.3);
        }
        .toolbar-title {
            color: #f1f5f9;
            font-family: system-ui, sans-serif;
            font-size: 13px;
            font-weight: 600;
        }
        .toolbar-actions { display: flex; gap: 8px; }
        .btn-toolbar {
            font-family: system-ui, sans-serif;
            font-size: 12px;
            font-weight: 700;
            padding: 7px 18px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
            transition: opacity .15s;
        }
        .btn-toolbar:hover { opacity: .85; }
        .btn-print  { background: #10b981; color: #fff; }
        .btn-back   { background: #475569; color: #fff; }
        .btn-sign   { background: #6366f1; color: #fff; }

        /* ── Page ── */
        .page {
            width: 216mm;
            min-height: 279mm;
            background: #fff;
            padding: 18mm 20mm 18mm 20mm;
            box-shadow: 0 4px 24px rgba(0,0,0,.15);
            border-radius: 4px;
            position: relative;
        }

        /* ── Logo header table ── */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 14pt;
            font-size: 9pt;
        }
        .header-table td, .header-table th {
            border: 1px solid #000;
            padding: 4pt 6pt;
            vertical-align: middle;
        }
        .header-table .logo-cell {
            width: 22%;
            text-align: center;
            padding: 6pt;
        }
        .header-logo-img {
            max-width: 54pt;
            max-height: 54pt;
            object-fit: contain;
            display: block;
            margin: 0 auto;
        }
        .header-table .title-cell {
            text-align: center;
            font-size: 10pt;
            font-weight: bold;
        }
        .header-table .meta-cell {
            width: 26%;
            font-size: 8.5pt;
        }
        .meta-label { font-weight: bold; display: block; }

        /* ── Document body ── */
        .lugar-fecha {
            text-align: right;
            margin-bottom: 12pt;
            font-size: 10.5pt;
        }
        .carta-titulo {
            text-align: center;
            font-size: 13pt;
            font-weight: bold;
            margin-bottom: 16pt;
            text-decoration: underline;
        }
        p { margin-bottom: 10pt; line-height: 1.55; text-align: justify; }
        .blank { border-bottom: 1px solid #000; display: inline-block; min-width: 200pt; }
        .blank-sm { border-bottom: 1px solid #000; display: inline-block; min-width: 120pt; }

        /* ── Commitments list ── */
        .section-title {
            font-weight: bold;
            font-size: 10.5pt;
            margin: 14pt 0 8pt;
            text-transform: uppercase;
        }
        ol.commitments {
            padding-left: 18pt;
            margin-bottom: 10pt;
        }
        ol.commitments li {
            margin-bottom: 6pt;
            line-height: 1.5;
            text-align: justify;
        }

        /* ── Page 2 ── */
        .section-header {
            background: #1e3a5f;
            color: #fff;
            font-weight: bold;
            font-size: 10pt;
            padding: 5pt 10pt;
            margin: 14pt 0 8pt;
            letter-spacing: .03em;
        }
        .spec-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9.5pt;
            margin-bottom: 10pt;
        }
        .spec-table th {
            background: #e8edf3;
            border: 1px solid #93a3b8;
            padding: 4pt 7pt;
            text-align: left;
            font-weight: bold;
        }
        .spec-table td {
            border: 1px solid #93a3b8;
            padding: 4pt 7pt;
            vertical-align: top;
        }
        .spec-table tr:nth-child(even) td { background: #f8fafc; }
        .badge {
            display: inline-block;
            font-size: 7.5pt;
            font-weight: bold;
            padding: 1pt 5pt;
            border-radius: 3pt;
            background: #dbeafe;
            color: #1d4ed8;
        }
        .badge-amber { background: #fef3c7; color: #92400e; }
        .badge-violet { background: #ede9fe; color: #5b21b6; }

        /* ── Signature area ── */
        .sig-section {
            margin-top: 28pt;
            display: flex;
            gap: 48pt;
            justify-content: center;
        }
        .sig-box {
            text-align: center;
            width: 160pt;
        }
        .sig-solo {
            margin: 28pt auto 0;
            width: 220pt;
            text-align: center;
        }
        .sig-solo .sig-img-wrap {
            width: 220pt;
        }
        .sig-line {
            border-top: 1.5px solid #000;
            margin-bottom: 4pt;
        }
        .sig-label { font-size: 8.5pt; }
        .sig-canvas-wrap {
            border: 1px dashed #94a3b8;
            border-radius: 4pt;
            margin-bottom: 4pt;
            overflow: hidden;
            cursor: crosshair;
            background: #fafafa;
            width: 160pt;
            height: 60pt;
            position: relative;
        }
        .sig-canvas-wrap canvas { display: block; }
        .sig-clear {
            font-size: 8pt;
            color: #64748b;
            cursor: pointer;
            border: none;
            background: none;
            text-decoration: underline;
            font-family: system-ui, sans-serif;
            margin-bottom: 4pt;
        }

        /* ── Signature image (after signing) ── */
        .sig-img-wrap {
            height: 60pt;
            display: flex;
            align-items: flex-end;
            justify-content: center;
            padding-bottom: 2pt;
        }
        .sig-img {
            max-height: 55pt;
            max-width: 160pt;
            object-fit: contain;
        }
        .btn-save { background: #059669; color: #fff; }
        [x-cloak] { display: none !important; }

        /* ── Watermark ── */
        .watermark {
            position: absolute;
            bottom: 20mm;
            right: 16mm;
            opacity: .06;
            font-size: 64pt;
            font-weight: 900;
            color: #1e3a5f;
            transform: rotate(-30deg);
            pointer-events: none;
            user-select: none;
            letter-spacing: -.02em;
        }

        /* ── Print styles ── */
        @media print {
            body { background: #fff !important; }
            .toolbar { display: none !important; }
            .screen-wrapper { padding: 0 !important; gap: 0 !important; background: none !important; }
            .page {
                box-shadow: none !important;
                border-radius: 0 !important;
                width: 210mm !important;
                min-height: 297mm !important;
                padding: 15mm 18mm !important;
            }
            @page { size: letter; margin: 0; }
        }
    </style>
</head>
<body x-data="cartaFirma()">

<div class="screen-wrapper">

    {{-- ── Toolbar ── --}}
    <div class="toolbar">
        <span class="toolbar-title">
            Carta Responsiva — {{ $user->name }}
        </span>
        <div class="toolbar-actions">
            <a href="{{ route('admin.credenciales.index') }}"
               class="btn-toolbar btn-back">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                Volver
            </a>
            <button class="btn-toolbar btn-sign" @click="abrirModal()">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
                <span x-text="signed ? 'Cambiar firma' : 'Firmar digitalmente'"></span>
            </button>
            <button x-show="signed" x-cloak class="btn-toolbar btn-save"
                    @click="guardarCarta()" :disabled="guardando || guardado">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/>
                </svg>
                <span x-text="guardado ? '\u2713 Guardado en RH' : (guardando ? 'Guardando...' : 'Guardar en Expediente RH')"></span>
            </button>
            <button class="btn-toolbar btn-print" onclick="window.print()">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Imprimir / PDF
            </button>
        </div>
    </div>

    {{-- ════════════════════════════════════════ --}}
    {{-- PÁGINA 1: Carta Responsiva --}}
    {{-- ════════════════════════════════════════ --}}
    <div class="page">

        {{-- Header institucional --}}
        <table class="header-table">
            <tr>
                <td class="logo-cell" rowspan="2">
                    <img src="{{ asset('images/logo-ei.png') }}" class="header-logo-img" alt="E&amp;I">
                </td>
                <td class="title-cell" colspan="2">
                    Carta Responsiva de Custodia de Equipo
                </td>
                <td class="meta-cell">
                    <span class="meta-label">Revisión</span>01
                </td>
            </tr>
            <tr>
                <td class="meta-cell">
                    <span class="meta-label">Área Responsable</span>Administración
                </td>
                <td class="meta-cell">
                    <span class="meta-label">Página</span>1 de 2
                </td>
                <td class="meta-cell">
                    <span class="meta-label">Fecha de emisión</span>
                    {{ $fechaCarta->format('d/m/Y') }}
                </td>
            </tr>
        </table>

        {{-- Lugar y fecha --}}
        @php $meses = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre']; @endphp
        <p class="lugar-fecha">
            San Luis Potosí, S.L.P., a {{ $fechaCarta->day }} de {{ $meses[$fechaCarta->month - 1] }} de {{ $fechaCarta->year }}
        </p>

        {{-- Título --}}
        <p class="carta-titulo">Carta Responsiva</p>

        {{-- Cuerpo --}}
        <p>
            Por medio del presente, yo,
            <span class="blank">{{ $user->empleado?->nombre ?? $user->name }}</span>,
            con número de empleado
            <span class="blank-sm">{{ $user->empleado?->id_empleado ?? '____________' }}</span>,
            manifiesto que he recibido en calidad de préstamo para el desempeño de mis funciones laborales, el equipo
            que se describe en el apartado marcado como inciso <strong>B) ESPECIFICACIONES TÉCNICAS DEL EQUIPO</strong>,
            propiedad de <strong>GLOBAL TRADE COMPLIANCE, S.C. (Estrategia e Innovación)</strong>:
        </p>

        <p>
            Me comprometo a hacer uso responsable, adecuado, y exclusivo para fines laborales del equipo proporcionado,
            así como mantenerlo en buen estado, evitar su mal uso, daño intencional, pérdida o extravío.
        </p>

        <p>Asimismo, me comprometo a lo siguiente:</p>

        <p class="section-title">
            A. Carta Responsiva para la Custodia del Equipo de Cómputo y/o Celular:
        </p>

        <ol class="commitments">
            <li>Mantener el equipo recibido en condiciones de operación y limpieza.</li>
            <li>No permitir el acceso de terceros al uso del equipo.</li>
            <li>No compartir contraseñas.</li>
            <li>No modificar la configuración del equipo a menos que esté autorizado para ello.</li>
            <li>Utilizar el equipo para los propósitos de negocio establecido por la empresa.</li>
            <li>No instalar ningún tipo de software sin ser expresamente autorizado por la empresa.</li>
            <li>Cumplir con las normas de seguridad relativas a la custodia del equipo aun fuera del horario laboral.</li>
            <li>No ejecutar servicios de mantenimiento en el equipo, en caso de ser necesario solicitarlo al área de
                sistemas para que dicho servicio sea canalizado por la empresa.</li>
            <li>Entrega del equipo con todos sus accesorios en caso de terminación de relación laboral por cualquiera
                que sea el motivo.</li>
            <li>En caso de daño o pérdida del equipo por negligencia, el valor será descontado por medio de nómina.</li>
            <li>El usuario es responsable de respaldar su información en el DRIVE correspondiente.</li>
            <li>Se prohíbe la instalación de aplicaciones para uso personal o ajenas a las del negocio.</li>
            <li>El equipo sigue siendo propiedad de la empresa y debe ser devuelto en las mismas condiciones en que fue
                recibido, salvo el desgaste natural por su uso.</li>
        </ol>

        <div class="watermark">E&amp;I</div>
    </div>

    {{-- ════════════════════════════════════════ --}}
    {{-- PÁGINA 2: Especificaciones Técnicas --}}
    {{-- ════════════════════════════════════════ --}}
    <div class="page">

        {{-- Header institucional --}}
        <table class="header-table">
            <tr>
                <td class="logo-cell" rowspan="2">
                    <img src="{{ asset('images/logo-ei.png') }}" class="header-logo-img" alt="E&amp;I">
                </td>
                <td class="title-cell" colspan="2">
                    Carta Responsiva de Custodia de Equipo
                </td>
                <td class="meta-cell">
                    <span class="meta-label">Revisión</span>01
                </td>
            </tr>
            <tr>
                <td class="meta-cell">
                    <span class="meta-label">Área Responsable</span>Administración
                </td>
                <td class="meta-cell">
                    <span class="meta-label">Página</span>2 de 2
                </td>
                <td class="meta-cell">
                    <span class="meta-label">Fecha de emisión</span>
                    {{ $fechaCarta->format('d/m/Y') }}
                </td>
            </tr>
        </table>

        <p class="section-title" style="margin-top:10pt;">
            B. Especificaciones Técnicas del Equipo
        </p>

        {{-- ── Datos del empleado ── --}}
        <div class="section-header">DATOS DEL EMPLEADO</div>
        <table class="spec-table">
            <tr>
                <th style="width:30%">Nombre completo</th>
                <td>{{ $user->empleado?->nombre ?? $user->name }}</td>
                <th style="width:20%">No. Empleado</th>
                <td>{{ $user->empleado?->id_empleado ?? '—' }}</td>
            </tr>
            <tr>
                <th>Puesto / Posición</th>
                <td>{{ $user->empleado?->posicion ?? '—' }}</td>
                <th>Área</th>
                <td>{{ $user->empleado?->area ?? '—' }}</td>
            </tr>
            <tr>
                <th>Correo corporativo</th>
                <td colspan="3">{{ $user->email }}</td>
            </tr>
        </table>

        @if($equipoPrincipal)
        {{-- ── Equipo principal ── --}}
        <div class="section-header">EQUIPO DE CÓMPUTO ASIGNADO (PRINCIPAL)</div>
        <table class="spec-table">
            <tr>
                <th style="width:30%">Nombre del equipo</th>
                <td>{{ $equipoPrincipal->nombre_equipo }}</td>
                <th style="width:20%">Modelo</th>
                <td>{{ $equipoPrincipal->modelo ?? '—' }}</td>
            </tr>
            <tr>
                <th>Número de serie</th>
                <td>{{ $equipoPrincipal->numero_serie ?? '—' }}</td>
                <th>UUID Activos</th>
                <td style="font-family:monospace;font-size:8pt;">{{ $equipoPrincipal->uuid_activos }}</td>
            </tr>
            <tr>
                <th>Usuario de PC</th>
                <td>{{ $equipoPrincipal->nombre_usuario_pc }}</td>
                <th>Contraseña de equipo</th>
                <td style="font-family:monospace;">{{ $equipoPrincipal->contrasena_descifrada }}</td>
            </tr>
            @if($equipoPrincipal->notas)
            <tr>
                <th>Notas</th>
                <td colspan="3">{{ $equipoPrincipal->notas }}</td>
            </tr>
            @endif
        </table>

        {{-- Correos del equipo principal --}}
        @if($equipoPrincipal->correos->isNotEmpty())
        <div class="section-header" style="background:#2d5a8e;">CORREOS ELECTRÓNICOS</div>
        <table class="spec-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Correo electrónico</th>
                    <th>Contraseña</th>
                </tr>
            </thead>
            <tbody>
                @foreach($equipoPrincipal->correos as $i => $correo)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $correo->correo }}</td>
                    <td style="font-family:monospace;">{{ $correo->contrasena_descifrada ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        {{-- Periféricos del equipo principal --}}
        @if($equipoPrincipal->perifericos->isNotEmpty())
        <div class="section-header" style="background:#5b21b6;">OTROS (Periféricos / Accesorios)</div>
        <table class="spec-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Número de serie</th>
                </tr>
            </thead>
            <tbody>
                @foreach($equipoPrincipal->perifericos as $i => $per)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $per->nombre }}</td>
                    <td>{{ $per->tipo ?? '—' }}</td>
                    <td style="font-family:monospace;font-size:8.5pt;">{{ $per->numero_serie ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        @else
        <p style="color:#64748b;font-style:italic;margin-bottom:10pt;">No hay equipo principal registrado para este usuario.</p>
        @endif

        {{-- ── Equipos secundarios ── --}}
        @if($equiposSecundarios->isNotEmpty())
        @foreach($equiposSecundarios as $si => $sec)
        <div class="section-header" style="background:#92400e;">
            EQUIPO SECUNDARIO / CLIENTE #{{ $si + 1 }}
        </div>
        <table class="spec-table">
            <tr>
                <th style="width:30%">Nombre del equipo</th>
                <td>{{ $sec->nombre_equipo }}</td>
                <th style="width:20%">Modelo</th>
                <td>{{ $sec->modelo ?? '—' }}</td>
            </tr>
            <tr>
                <th>Número de serie</th>
                <td>{{ $sec->numero_serie ?? '—' }}</td>
                <th>UUID Activos</th>
                <td style="font-family:monospace;font-size:8pt;">{{ $sec->uuid_activos }}</td>
            </tr>
            <tr>
                <th>Usuario de PC</th>
                <td>{{ $sec->nombre_usuario_pc }}</td>
                <th>Contraseña de equipo</th>
                <td style="font-family:monospace;">{{ $sec->contrasena_descifrada }}</td>
            </tr>
            @if($sec->notas)
            <tr>
                <th>Notas</th>
                <td colspan="3">{{ $sec->notas }}</td>
            </tr>
            @endif
        </table>

        @if($sec->correos->isNotEmpty())
        <table class="spec-table" style="margin-top:-9pt;">
            <thead>
                <tr>
                    <th colspan="3" style="background:#e8f4e8;color:#166534;">Correos — Equipo Secundario #{{ $si + 1 }}</th>
                </tr>
                <tr>
                    <th>#</th><th>Correo</th><th>Contraseña</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sec->correos as $ci => $correo)
                <tr>
                    <td>{{ $ci + 1 }}</td>
                    <td>{{ $correo->correo }}</td>
                    <td style="font-family:monospace;">{{ $correo->contrasena_descifrada ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif

        @if($sec->perifericos->isNotEmpty())
        <table class="spec-table" style="margin-top:-9pt;">
            <thead>
                <tr>
                    <th colspan="4" style="background:#ede9fe;color:#5b21b6;">Periféricos — Equipo Secundario #{{ $si + 1 }}</th>
                </tr>
                <tr>
                    <th>#</th><th>Nombre</th><th>Tipo</th><th>N° Serie</th>
                </tr>
            </thead>
            <tbody>
                @foreach($sec->perifericos as $pi => $per)
                <tr>
                    <td>{{ $pi + 1 }}</td>
                    <td>{{ $per->nombre }}</td>
                    <td>{{ $per->tipo ?? '—' }}</td>
                    <td style="font-family:monospace;font-size:8.5pt;">{{ $per->numero_serie ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
        @endforeach
        @endif

        {{-- ── Firma (solo última hoja) ── --}}
        <div class="sig-solo">
            <div class="sig-img-wrap">
                <div x-show="!signed" style="width:220pt;height:55pt;"></div>
                <img x-show="signed" :src="sigData" class="sig-img" alt="">
            </div>
            <div class="sig-line"></div>
            <p class="sig-label"><strong>{{ $user->empleado?->nombre ?? $user->name }}</strong></p>
            <p class="sig-label">{{ $user->empleado?->posicion ?? 'Colaborador' }}</p>
        </div>

        <div class="watermark">E&amp;I</div>
    </div>

</div>{{-- end screen-wrapper --}}

<script>
function cartaFirma() {
    return {
        // Modal
        mostrarModal: false,
        modalCanvas: null,
        modalCtx: null,
        drawing: false,
        lastPos: { x: 0, y: 0 },

        // Firma
        signed: false,
        sigData: null,

        // Guardar
        guardando: false,
        guardado: false,

        abrirModal() {
            this.mostrarModal = true;
            this.$nextTick(() => this.initModal());
        },

        cerrarModal() {
            this.mostrarModal = false;
        },

        initModal() {
            this.modalCanvas = document.getElementById('sig-modal');
            if (!this.modalCanvas) return;
            this.modalCtx = this.modalCanvas.getContext('2d');
            this.modalCtx.strokeStyle = '#1e293b';
            this.modalCtx.lineWidth = 2.5;
            this.modalCtx.lineCap = 'round';
            this.modalCtx.lineJoin = 'round';
        },

        getPos(event) {
            const canvas = this.modalCanvas;
            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            const src = event.touches ? event.touches[0] : event;
            return {
                x: (src.clientX - rect.left) * scaleX,
                y: (src.clientY - rect.top) * scaleY,
            };
        },

        startDraw(event) {
            if (!this.modalCtx) this.initModal();
            this.drawing = true;
            const pos = this.getPos(event);
            this.lastPos = pos;
            this.modalCtx.beginPath();
            this.modalCtx.arc(pos.x, pos.y, 1.5, 0, Math.PI * 2);
            this.modalCtx.fillStyle = '#1e293b';
            this.modalCtx.fill();
        },

        draw(event) {
            if (!this.drawing || !this.modalCtx) return;
            const pos = this.getPos(event);
            this.modalCtx.beginPath();
            this.modalCtx.moveTo(this.lastPos.x, this.lastPos.y);
            this.modalCtx.lineTo(pos.x, pos.y);
            this.modalCtx.stroke();
            this.lastPos = pos;
        },

        stopDraw() {
            this.drawing = false;
        },

        limpiarModal() {
            if (!this.modalCtx || !this.modalCanvas) return;
            this.modalCtx.clearRect(0, 0, this.modalCanvas.width, this.modalCanvas.height);
        },

        aplicarFirma() {
            if (!this.modalCanvas) return;
            this.sigData = this.modalCanvas.toDataURL('image/png');
            this.signed = true;
            this.guardado = false;
            this.mostrarModal = false;
        },

        async guardarCarta() {
            if (!this.signed) { alert('Primero debes firmar la carta.'); return; }
            this.guardando = true;
            try {
                // Clonar las páginas (excluye toolbar) para html2pdf
                const cont = document.createElement('div');
                cont.style.cssText = 'background:#fff;';
                document.querySelectorAll('.page').forEach(p => cont.appendChild(p.cloneNode(true)));

                const opt = {
                    margin: 0,
                    filename: 'carta-responsiva.pdf',
                    image: { type: 'jpeg', quality: 0.95 },
                    html2canvas: { scale: 2, useCORS: true, logging: false },
                    jsPDF: { unit: 'mm', format: 'letter', orientation: 'portrait' },
                };

                const pdfBlob = await html2pdf().from(cont).set(opt).outputPdf('blob');

                const base64 = await new Promise((resolve, reject) => {
                    const reader = new FileReader();
                    reader.onload = e => resolve(e.target.result);
                    reader.onerror = reject;
                    reader.readAsDataURL(pdfBlob);
                });

                const resp = await fetch('{{ route("admin.credenciales.carta-responsiva.guardar", $user) }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({ pdf_base64: base64 }),
                });

                const data = await resp.json();
                if (data.success) {
                    this.guardado = true;
                } else {
                    alert(data.message || 'Error al guardar.');
                }
            } catch (e) {
                console.error(e);
                alert('Error al generar el PDF: ' + e.message);
            } finally {
                this.guardando = false;
            }
        },
    };
}
</script>

{{-- ══ MODAL DE FIRMA HORIZONTAL ══ --}}
<div x-show="mostrarModal" x-cloak
     style="position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,.75);display:flex;align-items:center;justify-content:center;padding:16px;"
     @keydown.escape.window="cerrarModal()">
    <div style="background:#fff;border-radius:16px;padding:24px;width:min(840px,96vw);box-shadow:0 20px 60px rgba(0,0,0,.5);"
         @click.stop>
        <div style="display:flex;justify-content:space-between;align-items:flex-start;margin-bottom:16px;font-family:system-ui,sans-serif;">
            <div>
                <h3 style="font-size:16px;font-weight:700;color:#1e293b;margin:0;">Firma Digital</h3>
                <p style="font-size:12px;color:#64748b;margin:6px 0 0;">Firme con el mouse o con el dedo en el área de abajo. Presione <strong>Aplicar firma</strong> al terminar.</p>
            </div>
            <button @click="cerrarModal()"
                    style="font-size:20px;line-height:1;padding:4px 10px;background:#f1f5f9;border:none;border-radius:8px;cursor:pointer;color:#64748b;font-family:system-ui,sans-serif;flex-shrink:0;margin-left:12px;">×</button>
        </div>
        <div style="border:2px solid #e2e8f0;border-radius:8px;overflow:hidden;background:#fafafa;touch-action:none;">
            <canvas id="sig-modal" width="800" height="220"
                    style="display:block;width:100%;height:220px;cursor:crosshair;"
                    @mousedown.prevent="startDraw($event)"
                    @mousemove.prevent="draw($event)"
                    @mouseup="stopDraw()"
                    @mouseleave="stopDraw()"
                    @touchstart.prevent="startDraw($event)"
                    @touchmove.prevent="draw($event)"
                    @touchend="stopDraw()"></canvas>
        </div>
        <div style="display:flex;justify-content:space-between;align-items:center;margin-top:16px;font-family:system-ui,sans-serif;gap:12px;flex-wrap:wrap;">
            <button @click="limpiarModal()"
                    style="padding:8px 20px;background:#f1f5f9;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;font-weight:600;color:#64748b;cursor:pointer;">
                Limpiar
            </button>
            <div style="display:flex;gap:10px;">
                <button @click="cerrarModal()"
                        style="padding:8px 20px;background:#fff;border:1px solid #e2e8f0;border-radius:8px;font-size:13px;font-weight:600;color:#64748b;cursor:pointer;">
                    Cancelar
                </button>
                <button @click="aplicarFirma()"
                        style="padding:8px 24px;background:#6366f1;border:none;border-radius:8px;font-size:13px;font-weight:700;color:#fff;cursor:pointer;">
                    Aplicar firma ✓
                </button>
            </div>
        </div>
    </div>
</div>
{{-- ══ fin modal ══ --}}

</body>
</html>
