<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carta Responsiva — {{ $user->name }}</title>
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
        .header-logo-circle {
            width: 54pt;
            height: 54pt;
            border-radius: 50%;
            background: #1e3a5f;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 20pt;
            font-weight: 900;
            margin: 0 auto;
            font-family: serif;
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
                page-break-after: always;
                break-after: page;
            }
            .page:last-of-type { page-break-after: auto; break-after: auto; }
            .sig-canvas-wrap { border-style: solid !important; }
            .sig-clear { display: none !important; }
            .btn-sign { display: none !important; }
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
            <button class="btn-toolbar btn-sign" @click="toggleSign()">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"/>
                </svg>
                <span x-text="showSign ? 'Ocultar firma' : 'Firmar digitalmente'"></span>
            </button>
            <button class="btn-toolbar btn-print" onclick="window.print()">
                <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                </svg>
                Imprimir / Guardar PDF
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
                    <div class="header-logo-circle">E&amp;I</div>
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
                    {{ now()->format('d/m/Y') }}
                </td>
            </tr>
        </table>

        {{-- Lugar y fecha --}}
        <p class="lugar-fecha">
            San Luis Potosí, S.L.P., a <span class="blank"></span>
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

        {{-- Firma página 1 --}}
        <div class="sig-section">
            <div class="sig-box" x-show="showSign">
                <div class="sig-canvas-wrap"
                     @mousedown="startDraw($event, 'sig1')"
                     @mousemove="draw($event, 'sig1')"
                     @mouseup="stopDraw('sig1')"
                     @mouseleave="stopDraw('sig1')"
                     @touchstart.prevent="startDraw($event, 'sig1')"
                     @touchmove.prevent="draw($event, 'sig1')"
                     @touchend="stopDraw('sig1')">
                    <canvas id="sig1" width="213" height="80"></canvas>
                </div>
                <button class="sig-clear" @click="clearCanvas('sig1')">Limpiar firma</button>
                <div class="sig-line"></div>
                <p class="sig-label"><strong>{{ $user->empleado?->nombre ?? $user->name }}</strong></p>
                <p class="sig-label">{{ $user->empleado?->posicion ?? 'Colaborador' }}</p>
            </div>
            <div class="sig-box" x-show="!showSign">
                <div style="height:60pt;"></div>
                <div class="sig-line"></div>
                <p class="sig-label"><strong>{{ $user->empleado?->nombre ?? $user->name }}</strong></p>
                <p class="sig-label">{{ $user->empleado?->posicion ?? 'Colaborador' }}</p>
            </div>
            <div class="sig-box">
                <div style="height:60pt;"></div>
                <div class="sig-line"></div>
                <p class="sig-label"><strong>Área de Sistemas / IT</strong></p>
                <p class="sig-label">Global Trade Compliance, S.C.</p>
            </div>
        </div>

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
                    <div class="header-logo-circle">E&amp;I</div>
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
                    {{ now()->format('d/m/Y') }}
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

        {{-- ── Firma página 2 ── --}}
        <div class="sig-section">
            <div class="sig-box" x-show="showSign">
                <div class="sig-canvas-wrap"
                     @mousedown="startDraw($event, 'sig2')"
                     @mousemove="draw($event, 'sig2')"
                     @mouseup="stopDraw('sig2')"
                     @mouseleave="stopDraw('sig2')"
                     @touchstart.prevent="startDraw($event, 'sig2')"
                     @touchmove.prevent="draw($event, 'sig2')"
                     @touchend="stopDraw('sig2')">
                    <canvas id="sig2" width="213" height="80"></canvas>
                </div>
                <button class="sig-clear" @click="clearCanvas('sig2')">Limpiar firma</button>
                <div class="sig-line"></div>
                <p class="sig-label"><strong>{{ $user->empleado?->nombre ?? $user->name }}</strong></p>
                <p class="sig-label">{{ $user->empleado?->posicion ?? 'Colaborador' }}</p>
            </div>
            <div class="sig-box" x-show="!showSign">
                <div style="height:60pt;"></div>
                <div class="sig-line"></div>
                <p class="sig-label"><strong>{{ $user->empleado?->nombre ?? $user->name }}</strong></p>
                <p class="sig-label">{{ $user->empleado?->posicion ?? 'Colaborador' }}</p>
            </div>
            <div class="sig-box">
                <div style="height:60pt;"></div>
                <div class="sig-line"></div>
                <p class="sig-label"><strong>Área de Sistemas / IT</strong></p>
                <p class="sig-label">Global Trade Compliance, S.C.</p>
            </div>
        </div>

        <div class="watermark">E&amp;I</div>
    </div>

</div>{{-- end screen-wrapper --}}

<script>
function cartaFirma() {
    return {
        showSign: false,
        drawing: {},
        lastPos: {},
        canvases: {},

        toggleSign() {
            this.showSign = !this.showSign;
            if (this.showSign) {
                this.$nextTick(() => this.initCanvases());
            }
        },

        initCanvases() {
            ['sig1', 'sig2'].forEach(id => {
                const canvas = document.getElementById(id);
                if (canvas && !this.canvases[id]) {
                    this.canvases[id] = canvas.getContext('2d');
                    this.canvases[id].strokeStyle = '#1e293b';
                    this.canvases[id].lineWidth = 2;
                    this.canvases[id].lineCap = 'round';
                    this.canvases[id].lineJoin = 'round';
                }
            });
        },

        getPos(event, canvas) {
            const rect = canvas.getBoundingClientRect();
            const scaleX = canvas.width / rect.width;
            const scaleY = canvas.height / rect.height;
            const src = event.touches ? event.touches[0] : event;
            return {
                x: (src.clientX - rect.left) * scaleX,
                y: (src.clientY - rect.top) * scaleY
            };
        },

        startDraw(event, id) {
            const canvas = document.getElementById(id);
            if (!canvas) return;
            this.drawing[id] = true;
            const pos = this.getPos(event, canvas);
            this.lastPos[id] = pos;
            const ctx = this.canvases[id] || (() => {
                const c = canvas.getContext('2d');
                c.strokeStyle = '#1e293b'; c.lineWidth = 2;
                c.lineCap = 'round'; c.lineJoin = 'round';
                this.canvases[id] = c; return c;
            })();
            ctx.beginPath();
            ctx.arc(pos.x, pos.y, 1, 0, Math.PI * 2);
            ctx.fillStyle = '#1e293b';
            ctx.fill();
        },

        draw(event, id) {
            if (!this.drawing[id]) return;
            const canvas = document.getElementById(id);
            if (!canvas) return;
            const ctx = this.canvases[id];
            if (!ctx) return;
            const pos = this.getPos(event, canvas);
            ctx.beginPath();
            ctx.moveTo(this.lastPos[id].x, this.lastPos[id].y);
            ctx.lineTo(pos.x, pos.y);
            ctx.stroke();
            this.lastPos[id] = pos;
        },

        stopDraw(id) {
            this.drawing[id] = false;
        },

        clearCanvas(id) {
            const canvas = document.getElementById(id);
            if (!canvas) return;
            const ctx = this.canvases[id] || canvas.getContext('2d');
            ctx.clearRect(0, 0, canvas.width, canvas.height);
        },
    };
}
</script>
</body>
</html>
