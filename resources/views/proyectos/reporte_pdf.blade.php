<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page {
            size: A4;
            margin: 2.5cm 2cm 2cm 2cm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 10px;
            line-height: 1.5;
            color: #1a1a2e;
            margin: 0;
            padding: 0;
        }

        .header {
            background: #1e3a8a;
            background: linear-gradient(135deg, #1e3a8a 0%, #3730a3 50%, #4338ca 100%);
            padding: 25px 30px;
            color: white;
            border-radius: 8px 8px 0 0;
            margin-bottom: 0;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .header h1 {
            font-size: 22px;
            font-weight: bold;
            margin: 8px 0 4px 0;
            letter-spacing: -0.5px;
        }

        .badge {
            display: inline-block;
            background: #10b981;
            color: white;
            font-size: 9px;
            font-weight: bold;
            padding: 3px 10px;
            border-radius: 3px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .subtitle {
            color: #c7d2fe;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .content {
            background: #ffffff;
            border: 1px solid #e0e7ff;
            border-top: none;
            border-radius: 0 0 8px 8px;
            padding: 25px 30px;
        }

        .info-grid {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .info-grid td {
            padding: 0 8px;
            vertical-align: top;
        }

        .info-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 15px;
        }

        .info-box.green {
            background: #ecfdf5;
            border-color: #a7f3d0;
        }

        .info-label {
            color: #64748b;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 4px;
        }

        .info-value {
            color: #0f172a;
            font-weight: bold;
            font-size: 13px;
        }

        .info-box.green .info-label { color: #047857; }
        .info-box.green .info-value { color: #065f46; }

        .team-section {
            margin-top: 18px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
        }

        .team-label {
            color: #64748b;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
        }

        .team-member {
            display: inline-block;
            background: #eef2ff;
            color: #3730a3;
            font-size: 9px;
            padding: 4px 10px;
            border-radius: 12px;
            border: 1px solid #c7d2fe;
            margin: 2px 3px 2px 0;
        }

        .section {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .section-title {
            font-size: 12px;
            font-weight: bold;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 15px;
            padding-left: 10px;
            border-left: 4px solid #4338ca;
        }

        .metrics-row {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 12px;
        }

        .metrics-row td {
            padding: 0 5px;
            vertical-align: top;
        }

        .metric-box {
            background: #1e293b;
            color: white;
            border-radius: 6px;
            padding: 12px;
            text-align: center;
        }

        .metric-box.green { background: #059669; }
        .metric-box.blue { background: #2563eb; }
        .metric-box.red { background: #dc2626; }

        .metric-value {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .metric-label {
            font-size: 9px;
            opacity: 0.85;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .metric-small {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 6px;
            padding: 12px;
            text-align: center;
            width: 32%;
            display: inline-block;
        }

        .metric-small.purple {
            background: #f5f3ff;
            border-color: #c4b5fd;
        }

        .metric-small.indigo {
            background: #eef2ff;
            border-color: #c7d2fe;
        }

        .metric-small-value {
            font-size: 18px;
            font-weight: bold;
            color: #d97706;
            margin-bottom: 2px;
        }

        .metric-small.purple .metric-small-value { color: #7c3aed; }
        .metric-small.indigo .metric-small-value { color: #4338ca; }

        .metric-small-label {
            font-size: 9px;
            color: #92400e;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .metric-small.purple .metric-small-label { color: #6d28d9; }
        .metric-small.indigo .metric-small-label { color: #3730a3; }

        .table-section {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
            page-break-inside: avoid;
        }

        .table-header {
            background: #1e293b;
            color: white;
            padding: 10px 20px;
            font-weight: bold;
            font-size: 11px;
        }

        table.activities {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }

        table.activities thead {
            background: #f1f5f9;
        }

        table.activities th {
            padding: 8px 10px;
            text-align: left;
            font-size: 8px;
            text-transform: uppercase;
            color: #475569;
            font-weight: bold;
            letter-spacing: 0.5px;
            border-bottom: 2px solid #cbd5e1;
        }

        table.activities th.center { text-align: center; }

        table.activities td {
            padding: 7px 10px;
            border-bottom: 1px solid #f1f5f9;
        }

        table.activities tr:hover { background: #fafbfc; }

        .activity-name {
            font-weight: 500;
            color: #1e293b;
            font-size: 10px;
        }

        .activity-client {
            color: #4338ca;
            font-size: 8px;
            margin-top: 2px;
        }

        .status {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 8px;
            font-weight: 600;
        }

        .status.green { background: #d1fae5; color: #065f46; }
        .status.red { background: #fee2e2; color: #991b1b; }
        .status.blue { background: #dbeafe; color: #1e40af; }
        .status.gray { background: #f1f5f9; color: #475569; }
        .status.amber { background: #fef3c7; color: #92400e; }
        .status.purple { background: #ede9fe; color: #5b21b6; }

        .efficiency {
            font-weight: bold;
            font-size: 9px;
        }

        .efficiency.green { color: #059669; }
        .efficiency.amber { color: #d97706; }
        .efficiency.red { color: #dc2626; }

        .notes {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 8px;
            padding: 15px 20px;
            margin-bottom: 20px;
        }

        .notes-title {
            color: #92400e;
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 6px;
        }

        .notes-content {
            color: #78350f;
            font-size: 10px;
            line-height: 1.6;
        }

        .footer {
            text-align: center;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 8px;
        }

        .page-break { page-break-after: always; }

        .description {
            color: #475569;
            font-size: 10px;
            line-height: 1.6;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    {{-- Header --}}
    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 75%;">
                    <div>
                        <span class="badge">Finalizado</span>
                        <span class="subtitle" style="margin-left: 10px;">Reporte Oficial</span>
                    </div>
                    <h1>{{ $proyecto->nombre }}</h1>
                </td>
            </tr>
        </table>
    </div>

    <div class="content">
        @if($proyecto->descripcion)
        <div class="description">{{ $proyecto->descripcion }}</div>
        @endif

        {{-- Información básica --}}
        <table class="info-grid">
            <tr>
                <td style="width: 25%;">
                    <div class="info-box">
                        <div class="info-label">Fecha Inicio</div>
                        <div class="info-value">{{ \Carbon\Carbon::parse($proyecto->fecha_inicio)->format('d/m/Y') }}</div>
                    </div>
                </td>
                <td style="width: 25%;">
                    <div class="info-box">
                        <div class="info-label">Fecha Fin Planeada</div>
                        <div class="info-value">{{ \Carbon\Carbon::parse($proyecto->fecha_fin)->format('d/m/Y') }}</div>
                    </div>
                </td>
                <td style="width: 25%;">
                    <div class="info-box green">
                        <div class="info-label">Fecha Fin Real</div>
                        <div class="info-value">{{ \Carbon\Carbon::parse($proyecto->fecha_fin_real)->format('d/m/Y') }}</div>
                    </div>
                </td>
                <td style="width: 25%;">
                    <div class="info-box">
                        <div class="info-label">Responsable</div>
                        <div class="info-value">{{ $proyecto->creador->name ?? 'N/A' }}</div>
                    </div>
                </td>
            </tr>
        </table>

        @if($proyecto->usuarios->count() > 0)
        <div class="team-section">
            <div class="team-label">Equipo de Trabajo</div>
            @foreach($proyecto->usuarios as $usu)
                <span class="team-member">{{ $usu->name }}</span>
            @endforeach
        </div>
        @endif

        {{-- Métricas --}}
        <div class="section">
            <div class="section-title">Resumen de Métricas</div>

            <table class="metrics-row">
                <tr>
                    <td style="width: 24%;">
                        <div class="metric-box">
                            <div class="metric-value">{{ $metricas['total'] }}</div>
                            <div class="metric-label">Total</div>
                        </div>
                    </td>
                    <td style="width: 24%;">
                        <div class="metric-box green">
                            <div class="metric-value">{{ $metricas['completadas'] }}</div>
                            <div class="metric-label">Completadas</div>
                        </div>
                    </td>
                    <td style="width: 24%;">
                        <div class="metric-box blue">
                            <div class="metric-value">{{ $metricas['a_tiempo'] }}</div>
                            <div class="metric-label">A Tiempo</div>
                        </div>
                    </td>
                    <td style="width: 24%;">
                        <div class="metric-box red">
                            <div class="metric-value">{{ $metricas['con_retraso'] }}</div>
                            <div class="metric-label">Con Retraso</div>
                        </div>
                    </td>
                </tr>
            </table>

            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="width: 32%; padding: 0 5px 0 0;">
                        <div class="metric-small">
                            <div class="metric-small-value">{{ $metricas['porcentaje_completado'] }}%</div>
                            <div class="metric-small-label">Completado</div>
                        </div>
                    </td>
                    <td style="width: 32%; padding: 0 5px;">
                        <div class="metric-small purple">
                            <div class="metric-small-value">{{ $metricas['promedio_eficiencia'] }}%</div>
                            <div class="metric-small-label">Eficiencia</div>
                        </div>
                    </td>
                    <td style="width: 32%; padding: 0 0 0 5px;">
                        <div class="metric-small indigo">
                            <div class="metric-small-value">{{ $metricas['en_proceso'] }}</div>
                            <div class="metric-small-label">Pendientes</div>
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        {{-- Tabla de Actividades --}}
        <div class="table-section">
            <div class="table-header">📋 Detalle de Actividades</div>

            @if($actividades->isEmpty())
                <div style="padding: 30px; text-align: center; color: #94a3b8; font-size: 11px;">No hay actividades registradas</div>
            @else
                <table class="activities">
                    <thead>
                        <tr>
                            <th style="width: 5%;">#</th>
                            <th style="width: 30%;">Actividad</th>
                            <th style="width: 20%;">Responsable</th>
                            <th class="center" style="width: 15%;">Estatus</th>
                            <th class="center" style="width: 10%;">Días Plan.</th>
                            <th class="center" style="width: 10%;">Días Reales</th>
                            <th class="center" style="width: 10%;">Eficiencia</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($actividades as $index => $act)
                        <tr>
                            <td style="color: #94a3b8;">{{ $index + 1 }}</td>
                            <td>
                                <div class="activity-name">{{ $act->nombre_actividad }}</div>
                                @if($act->cliente)<div class="activity-client">{{ $act->cliente }}</div>@endif
                            </td>
                            <td style="color: #475569;">{{ $act->user->name ?? 'N/A' }}</td>
                            <td style="text-align: center;">
                                @php
                                    $colors = [
                                        'Completado' => 'green',
                                        'Completado con retraso' => 'red',
                                        'En proceso' => 'blue',
                                        'Planeado' => 'gray',
                                        'Por Aprobar' => 'amber',
                                        'Por Validar' => 'purple',
                                        'Retraso' => 'red',
                                        'Rechazado' => 'red',
                                    ];
                                    $color = $colors[$act->estatus] ?? 'gray';
                                @endphp
                                <span class="status {{ $color }}">{{ $act->estatus }}</span>
                            </td>
                            <td style="text-align: center; color: #475569;">{{ $act->metrico ?? '-' }}</td>
                            <td style="text-align: center; color: #475569;">{{ $act->resultado_dias ?? '-' }}</td>
                            <td style="text-align: center;">
                                @if($act->porcentaje)
                                    @if($act->porcentaje == 100)
                                        <span class="efficiency green">✓ {{ $act->porcentaje }}%</span>
                                    @elseif($act->porcentaje >= 50)
                                        <span class="efficiency amber">{{ $act->porcentaje }}%</span>
                                    @else
                                        <span class="efficiency red">{{ $act->porcentaje }}%</span>
                                    @endif
                                @else
                                    <span style="color: #94a3b8;">-</span>
                                @endif
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        {{-- Notas --}}
        @if($proyecto->notas)
        <div class="notes">
            <div class="notes-title">📝 Notas</div>
            <div class="notes-content">{{ $proyecto->notas }}</div>
        </div>
        @endif

        {{-- Footer --}}
        <div class="footer">
            Reporte generado el {{ now()->format('d/m/Y H:i') }} | Estrategia e Innovación
        </div>
    </div>
</body>
</html>
