<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carta Responsiva - {{ $user->name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 10pt;
            color: #1a1a1a;
            line-height: 1.3;
        }
        .page {
            width: 216mm;
            min-height: 279mm;
            padding: 12mm 15mm;
            page-break-after: always;
        }
        .page:last-child { page-break-after: avoid; }
        
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8pt;
            font-size: 8pt;
        }
        .header-table td, .header-table th {
            border: 1px solid #000;
            padding: 2pt 4pt;
        }
        .header-table .logo-cell { width: 18%; text-align: center; }
        .header-logo-img { max-width: 40pt; max-height: 40pt; }
        .header-table .title-cell { text-align: center; font-size: 9pt; font-weight: bold; }
        .header-table .meta-cell { width: 22%; font-size: 7pt; }
        .meta-label { font-weight: bold; display: block; }

        .lugar-fecha { text-align: right; margin-bottom: 6pt; font-size: 9pt; }
        .carta-titulo { text-align: center; font-size: 12pt; font-weight: bold; margin-bottom: 8pt; text-decoration: underline; }
        p { margin-bottom: 5pt; text-align: justify; }
        .blank { border-bottom: 1px solid #000; display: inline-block; min-width: 120pt; }
        .blank-sm { border-bottom: 1px solid #000; display: inline-block; min-width: 80pt; }

        .section-title { font-weight: bold; font-size: 9pt; margin: 8pt 0 4pt; text-transform: uppercase; }
        
        ol.commitments { padding-left: 14pt; margin-bottom: 6pt; font-size: 9pt; }
        ol.commitments li { margin-bottom: 2pt; }

        .section-header {
            background: #1e3a5f;
            color: #fff;
            font-weight: bold;
            font-size: 8pt;
            padding: 2pt 6pt;
            margin: 6pt 0 3pt;
        }
        
        /* Grid compacto tipo Bento */
        .bento-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 3pt;
            font-size: 8pt;
            margin-bottom: 6pt;
        }
        .bento-item {
            border: 1px solid #93a3b8;
            padding: 3pt 5pt;
            background: #f8fafc;
        }
        .bento-item.full { grid-column: span 4; }
        .bento-item.half { grid-column: span 2; }
        .bento-item.third { grid-column: span 2; }
        .bento-label { font-weight: bold; font-size: 7pt; color: #475569; display: block; }
        .bento-value { font-size: 8pt; }

        .spec-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8pt;
            margin-bottom: 6pt;
        }
        .spec-table th {
            background: #e8edf3;
            border: 1px solid #93a3b8;
            padding: 2pt 4pt;
            text-align: left;
            font-weight: bold;
            font-size: 7.5pt;
        }
        .spec-table td {
            border: 1px solid #93a3b8;
            padding: 2pt 4pt;
            font-size: 8pt;
        }
        .spec-table tr:nth-child(even) td { background: #f8fafc; }

        .sig-section { margin-top: 12pt; text-align: center; }
        .sig-line { border-top: 1px solid #000; width: 180pt; margin: 0 auto 2pt; }
        .sig-label { font-size: 7.5pt; }
        
        .watermark {
            position: fixed;
            bottom: 12mm;
            right: 12mm;
            opacity: .06;
            font-size: 48pt;
            font-weight: 900;
            color: #1e3a5f;
            transform: rotate(-30deg);
            pointer-events: none;
        }
    </style>
</head>
<body>
    {{-- PÁGINA 1 --}}
    <div class="page">
        <table class="header-table">
            <tr>
                <td class="logo-cell" rowspan="2">
                    <img src="{{ public_path('images/logo-ei.png') }}" class="header-logo-img" alt="E&I">
                </td>
                <td class="title-cell" colspan="2">Carta Responsiva de Custodia de Equipo</td>
                <td class="meta-cell"><span class="meta-label">Revisión</span>01</td>
            </tr>
            <tr>
                <td class="meta-cell"><span class="meta-label">Área</span>Administración</td>
                <td class="meta-cell"><span class="meta-label">Página</span>1 de 2</td>
                <td class="meta-cell"><span class="meta-label">Fecha</span>{{ $fechaCarta->format('d/m/Y') }}</td>
            </tr>
        </table>

        <p class="lugar-fecha">San Luis Potosí, S.L.P., a {{ $fechaCarta->day }} de {{ $meses[$fechaCarta->month - 1] }} de {{ $fechaCarta->year }}</p>
        <p class="carta-titulo">Carta Responsiva</p>

        <p>Por medio del presente, yo, <span class="blank">{{ $user->empleado?->nombre ?? $user->name }}</span>, con número de empleado <span class="blank-sm">{{ $user->empleado?->id_empleado ?? '____________' }}</span>, manifesto que he recibido en calidad de préstamo para el desempeño de mis funciones laborales, el equipo que se describe en el apartado marcado como inciso <strong>B) ESPECIFICACIONES TÉCNICAS DEL EQUIPO</strong>, propiedad de <strong>GLOBAL TRADE COMPLIANCE, S.C. (Estrategia e Innovación)</strong>:</p>

        <p>Me comprometo a hacer uso responsable, adecuado, y exclusivo para fines laborales del equipo proporcionado, así como mantenerlo en buen estado, evitar su mal uso, daño intencional, pérdida o extravío.</p>
        <p>Asimismo, me comprometo a lo siguiente:</p>

        <p class="section-title">A. Carta Responsiva para la Custodia del Equipo de Cómputo y/o Celular:</p>
        <ol class="commitments">
            <li>Mantener el equipo recibido en condiciones de operación y limpieza.</li>
            <li>No permitir el acceso de terceros al uso del equipo.</li>
            <li>No compartir contraseñas.</li>
            <li>No modificar la configuración del equipo a menos que esté autorizado.</li>
            <li>Utilizar el equipo para los propósitos de negocio establecido por la empresa.</li>
            <li>No instalar software sin autorización expresa.</li>
            <li>Cumplir con las normas de seguridad fuera del horario laboral.</li>
            <li>Solicitar mantenimiento al área de sistemas.</li>
            <li>Entrega del equipo con todos sus accesorios al terminar la relación laboral.</li>
            <li>En caso de daño o pérdida por negligencia, el valor será descontado por nómina.</li>
            <li>El usuario es responsable de respaldar su información en el DRIVE.</li>
            <li>Se prohíbe la instalación de aplicaciones para uso personal.</li>
            <li>El equipo es propiedad de la empresa y debe ser devuelto en las mismas condiciones.</li>
        </ol>

        <div class="watermark">E&I</div>
    </div>

    {{-- PÁGINA 2 --}}
    <div class="page">
        <table class="header-table">
            <tr>
                <td class="logo-cell" rowspan="2">
                    <img src="{{ public_path('images/logo-ei.png') }}" class="header-logo-img" alt="E&I">
                </td>
                <td class="title-cell" colspan="2">Carta Responsiva de Custodia de Equipo</td>
                <td class="meta-cell"><span class="meta-label">Revisión</span>01</td>
            </tr>
            <tr>
                <td class="meta-cell"><span class="meta-label">Área</span>Administración</td>
                <td class="meta-cell"><span class="meta-label">Página</span>2 de 2</td>
                <td class="meta-cell"><span class="meta-label">Fecha</span>{{ $fechaCarta->format('d/m/Y') }}</td>
            </tr>
        </table>

        <p class="section-title" style="margin-top:6pt;">B. Especificaciones Técnicas del Equipo</p>

        {{-- Datos empleado en grid --}}
        <div class="bento-grid">
            <div class="bento-item half">
                <span class="bento-label">Nombre completo</span>
                <span class="bento-value">{{ $user->empleado?->nombre ?? $user->name }}</span>
            </div>
            <div class="bento-item">
                <span class="bento-label">No. Empleado</span>
                <span class="bento-value">{{ $user->empleado?->id_empleado ?? '—' }}</span>
            </div>
            <div class="bento-item">
                <span class="bento-label">Puesto</span>
                <span class="bento-value">{{ $user->empleado?->posicion ?? '—' }}</span>
            </div>
            <div class="bento-item">
                <span class="bento-label">Área</span>
                <span class="bento-value">{{ $user->empleado?->area ?? '—' }}</span>
            </div>
            <div class="bento-item full">
                <span class="bento-label">Correo corporativo</span>
                <span class="bento-value">{{ $user->email }}</span>
            </div>
        </div>

        @if($equipoPrincipal)
        <div class="section-header">EQUIPO DE CÓMPUTO ASIGNADO (PRINCIPAL)</div>
        <div class="bento-grid">
            <div class="bento-item">
                <span class="bento-label">Nombre equipo</span>
                <span class="bento-value">{{ $equipoPrincipal->nombre_equipo }}</span>
            </div>
            <div class="bento-item">
                <span class="bento-label">Modelo</span>
                <span class="bento-value">{{ $equipoPrincipal->modelo ?? '—' }}</span>
            </div>
            <div class="bento-item">
                <span class="bento-label">No. Serie</span>
                <span class="bento-value">{{ $equipoPrincipal->numero_serie ?? '—' }}</span>
            </div>
            <div class="bento-item">
                <span class="bento-label">UUID Activos</span>
                <span class="bento-value" style="font-family:monospace;font-size:7pt;">{{ $equipoPrincipal->uuid_activos }}</span>
            </div>
            <div class="bento-item">
                <span class="bento-label">Usuario PC</span>
                <span class="bento-value">{{ $equipoPrincipal->nombre_usuario_pc }}</span>
            </div>
            <div class="bento-item">
                <span class="bento-label">Contraseña</span>
                <span class="bento-value" style="font-family:monospace;">{{ $equipoPrincipal->contrasena_descifrada }}</span>
            </div>
            @if($equipoPrincipal->notas)
            <div class="bento-item full">
                <span class="bento-label">Notas</span>
                <span class="bento-value">{{ $equipoPrincipal->notas }}</span>
            </div>
            @endif
        </div>

        @if($equipoPrincipal->correos->isNotEmpty())
        <div class="section-header">CORREOS ELECTRÓNICOS</div>
        <table class="spec-table">
            <thead>
                <tr><th>#</th><th>Correo</th><th>Contraseña</th></tr>
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

        @if($equipoPrincipal->perifericos->isNotEmpty())
        <div class="section-header">PERIFÉRICOS / ACCESORIOS</div>
        <table class="spec-table">
            <thead>
                <tr><th>#</th><th>Nombre</th><th>Tipo</th><th>No. Serie</th></tr>
            </thead>
            <tbody>
                @foreach($equipoPrincipal->perifericos as $i => $per)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $per->nombre }}</td>
                    <td>{{ $per->tipo ?? '—' }}</td>
                    <td style="font-family:monospace;">{{ $per->numero_serie ?? '—' }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
        @else
        <p style="color:#64748b;font-style:italic;">No hay equipo principal registrado.</p>
        @endif

        {{-- Equipos secundarios --}}
        @if($equiposSecundarios->isNotEmpty())
        @foreach($equiposSecundarios as $si => $sec)
        <div class="section-header" style="background:#92400e;">EQUIPO SECUNDARIO #{{ $si + 1 }}</div>
        <div class="bento-grid">
            <div class="bento-item">
                <span class="bento-label">Nombre equipo</span>
                <span class="bento-value">{{ $sec->nombre_equipo }}</span>
            </div>
            <div class="bento-item">
                <span class="bento-label">Modelo</span>
                <span class="bento-value">{{ $sec->modelo ?? '—' }}</span>
            </div>
            <div class="bento-item">
                <span class="bento-label">No. Serie</span>
                <span class="bento-value">{{ $sec->numero_serie ?? '—' }}</span>
            </div>
            <div class="bento-item">
                <span class="bento-label">UUID</span>
                <span class="bento-value" style="font-family:monospace;font-size:7pt;">{{ $sec->uuid_activos }}</span>
            </div>
        </div>
        @endforeach
        @endif

        <div class="sig-section">
            <div class="sig-line"></div>
            <p class="sig-label"><strong>{{ $user->empleado?->nombre ?? $user->name }}</strong></p>
            <p class="sig-label">{{ $user->empleado?->posicion ?? 'Colaborador' }}</p>
        </div>

        <div class="watermark">E&I</div>
    </div>
</body>
</html>
