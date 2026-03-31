<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Carta Responsiva - {{ $user->name }}</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 11pt;
            color: #1a1a1a;
            line-height: 1.5;
        }
        .page {
            width: 216mm;
            min-height: 279mm;
            padding: 18mm 20mm;
            page-break-after: always;
        }
        .page:last-child { page-break-after: avoid; }
        
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
        .header-table .logo-cell { width: 22%; text-align: center; }
        .header-logo-img { max-width: 54pt; max-height: 54pt; }
        .header-table .title-cell { text-align: center; font-size: 10pt; font-weight: bold; }
        .header-table .meta-cell { width: 26%; font-size: 8.5pt; }
        .meta-label { font-weight: bold; display: block; }

        .lugar-fecha { text-align: right; margin-bottom: 12pt; font-size: 10.5pt; }
        .carta-titulo { text-align: center; font-size: 13pt; font-weight: bold; margin-bottom: 16pt; text-decoration: underline; }
        p { margin-bottom: 10pt; text-align: justify; }
        .blank { border-bottom: 1px solid #000; display: inline-block; min-width: 200pt; }
        .blank-sm { border-bottom: 1px solid #000; display: inline-block; min-width: 120pt; }

        .section-title { font-weight: bold; font-size: 10.5pt; margin: 14pt 0 8pt; text-transform: uppercase; }
        
        ol.commitments { padding-left: 18pt; margin-bottom: 10pt; }
        ol.commitments li { margin-bottom: 6pt; text-align: justify; }

        .section-header {
            background: #1e3a5f;
            color: #fff;
            font-weight: bold;
            font-size: 10pt;
            padding: 5pt 10pt;
            margin: 14pt 0 8pt;
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

        .sig-section { margin-top: 28pt; text-align: center; }
        .sig-line { border-top: 1.5px solid #000; width: 220pt; margin: 0 auto 4pt; }
        .sig-label { font-size: 8.5pt; }
        
        .watermark {
            position: fixed;
            bottom: 20mm;
            right: 16mm;
            opacity: .06;
            font-size: 64pt;
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
                <td class="meta-cell"><span class="meta-label">Área Responsable</span>Administración</td>
                <td class="meta-cell"><span class="meta-label">Página</span>1 de 2</td>
                <td class="meta-cell"><span class="meta-label">Fecha de emisión</span>{{ $fechaCarta->format('d/m/Y') }}</td>
            </tr>
        </table>

        <p class="lugar-fecha">
            San Luis Potosí, S.L.P., a {{ $fechaCarta->day }} de {{ $meses[$fechaCarta->month - 1] }} de {{ $fechaCarta->year }}
        </p>

        <p class="carta-titulo">Carta Responsiva</p>

        <p>Por medio del presente, yo, <span class="blank">{{ $user->empleado?->nombre ?? $user->name }}</span>, con número de empleado <span class="blank-sm">{{ $user->empleado?->id_empleado ?? '____________' }}</span>, manifiesto que he recibido en calidad de préstamo para el desempeño de mis funciones laborales, el equipo que se describe en el apartado marcado como inciso <strong>B) ESPECIFICACIONES TÉCNICAS DEL EQUIPO</strong>, propiedad de <strong>GLOBAL TRADE COMPLIANCE, S.C. (Estrategia e Innovación)</strong>:</p>

        <p>Me comprometo a hacer uso responsable, adecuado, y exclusivo para fines laborales del equipo proporcionado, así como mantenerlo en buen estado, evitar su mal uso, daño intencional, pérdida o extravío.</p>

        <p>Asimismo, me comprometo a lo siguiente:</p>

        <p class="section-title">A. Carta Responsiva para la Custodia del Equipo de Cómputo y/o Celular:</p>

        <ol class="commitments">
            <li>Mantener el equipo recibido en condiciones de operación y limpieza.</li>
            <li>No permitir el acceso de terceros al uso del equipo.</li>
            <li>No compartir contraseñas.</li>
            <li>No modificar la configuración del equipo a menos que esté autorizado para ello.</li>
            <li>Utilizar el equipo para los propósitos de negocio establecido por la empresa.</li>
            <li>No instalar ningún tipo de software sin ser expresamente autorizado por la empresa.</li>
            <li>Cumplir con las normas de seguridad relativas a la custodia del equipo aun fuera del horario laboral.</li>
            <li>No ejecutar servicios de mantenimiento en el equipo, en caso de ser necesario solicitarlo al área de sistemas para que dicho servicio sea canalizado por la empresa.</li>
            <li>Entrega del equipo con todos sus accesorios en caso de terminación de relación laboral por cualquiera que sea el motivo.</li>
            <li>En caso de daño o pérdida del equipo por negligencia, el valor será descontado por medio de nómina.</li>
            <li>El usuario es responsable de respaldar su información en el DRIVE correspondiente.</li>
            <li>Se prohíbe la instalación de aplicaciones para uso personal o ajenas a las del negocio.</li>
            <li>El equipo sigue siendo propiedad de la empresa y debe ser devuelto en las mismas condiciones en que fue recibido, salvo el desgaste natural por su uso.</li>
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
                <td class="meta-cell"><span class="meta-label">Área Responsable</span>Administración</td>
                <td class="meta-cell"><span class="meta-label">Página</span>2 de 2</td>
                <td class="meta-cell"><span class="meta-label">Fecha de emisión</span>{{ $fechaCarta->format('d/m/Y') }}</td>
            </tr>
        </table>

        <p class="section-title" style="margin-top:10pt;">B. Especificaciones Técnicas del Equipo</p>

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

        @if($equipoPrincipal->correos->isNotEmpty())
        <div class="section-header">CORREOS ELECTRÓNICOS</div>
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

        @if($equipoPrincipal->perifericos->isNotEmpty())
        <div class="section-header">OTROS (Periféricos / Accesorios)</div>
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
        <p style="color:#64748b;font-style:italic;">No hay equipo principal registrado para este usuario.</p>
        @endif

        {{-- Equipos secundarios --}}
        @if($equiposSecundarios->isNotEmpty())
        @foreach($equiposSecundarios as $si => $sec)
        <div class="section-header" style="background:#92400e;">EQUIPO SECUNDARIO / CLIENTE #{{ $si + 1 }}</div>
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
        </table>
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
