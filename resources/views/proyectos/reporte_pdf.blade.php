<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <style>
        @page {
            size: A4;
            margin: 2cm 1.5cm;
        }

        body {
            font-family: Helvetica, Arial, sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #1e293b;
            margin: 0;
            padding: 0;
        }

        .header {
            background: #4338ca;
            background: linear-gradient(135deg, #4338ca 0%, #3730a3 100%);
            padding: 20px 30px;
            color: white;
            margin-bottom: 20px;
        }

        .header h1 {
            font-size: 20px;
            font-weight: bold;
            margin: 8px 0 0 0;
        }

        .badge {
            display: inline-block;
            background: #34d399;
            color: white;
            font-size: 10px;
            font-weight: bold;
            padding: 2px 8px;
            border-radius: 3px;
            text-transform: uppercase;
        }

        .subtitle {
            color: #c7d2fe;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .info-box {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 12px 15px;
            margin-bottom: 10px;
        }

        .info-label {
            color: #94a3b8;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
        }

        .info-value {
            color: #1e293b;
            font-weight: bold;
            font-size: 12px;
        }

        .grid-4 {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }

        .grid-4 td {
            width: 25%;
            padding: 0 5px;
            vertical-align: top;
        }

        .team-section {
            margin-top: 15px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
        }

        .team-label {
            color: #94a3b8;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 6px;
        }

        .team-member {
            display: inline-block;
            background: #eef2ff;
            color: #4338ca;
            font-size: 10px;
            padding: 3px 8px;
            border-radius: 10px;
            border: 1px solid #c7d2fe;
            margin: 2px;
        }

        .metrics-section {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 15px;
            margin-bottom: 15px;
        }

        .metrics-title {
            font-size: 11px;
            font-weight: bold;
            color: #1e293b;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 12px;
            border-left: 3px solid #4338ca;
            padding-left: 8px;
        }

        .metric-box {
            background: #334155;
            color: white;
            border-radius: 6px;
            padding: 10px;
            text-align: center;
            width: 23%;
            display: inline-block;
            margin: 0 1%;
        }

        .metric-box.green { background: #059669; }
        .metric-box.blue { background: #2563eb; }
        .metric-box.red { background: #dc2626; }

        .metric-value {
            font-size: 20px;
            font-weight: bold;
            margin-bottom: 3px;
        }

        .metric-label {
            font-size: 9px;
            opacity: 0.8;
        }

        .metric-small-box {
            background: #fef3c7;
            border: 1px solid #fcd34d;
            border-radius: 6px;
            padding: 10px;
            text-align: center;
            width: 31%;
            display: inline-block;
            margin: 0 1%;
        }

        .metric-small-box.purple { background: #f5f3ff; border-color: #c4b5fd; }
        .metric-small-box.indigo { background: #eef2ff; border-color: #c7d2fe; }

        .metric-small-value {
            font-size: 16px;
            font-weight: bold;
            color: #d97706;
        }

        .metric-small-box.purple .metric-small-value { color: #7c3aed; }
        .metric-small-box.indigo .metric-small-value { color: #4338ca; }

        .metric-small-label {
            font-size: 9px;
            color: #92400e;
        }

        .metric-small-box.purple .metric-small-label { color: #6d28d9; }
        .metric-small-box.indigo .metric-small-label { color: #4338ca; }

        .table-section {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
            margin-bottom: 15px;
        }

        .table-header {
            background: #1e293b;
            color: white;
            padding: 8px 15px;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }

        th {
            background: #f1f5f9;
            border-bottom: 2px solid #cbd5e1;
            padding: 8px 10px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            color: #475569;
            font-weight: bold;
        }

        th.center { text-align: center; }

        td {
            padding: 6px 10px;
            border-bottom: 1px solid #e2e8f0;
        }

        tr:hover { background: #f8fafc; }

        .status {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: medium;
        }

        .status.green { background: #d1fae5; color: #065f46; }
        .status.red { background: #fee2e2; color: #991b1b; }
        .status.blue { background: #dbeafe; color: #1e40af; }
        .status.gray { background: #e2e8f0; color: #475569; }
        .status.amber { background: #fef3c7; color: #92400e; }
        .status.purple { background: #ede9fe; color: #5b21b6; }

        .efficiency {
            font-weight: bold;
        }

        .efficiency.green { color: #059669; }
        .efficiency.amber { color: #d97706; }
        .efficiency.red { color: #dc2626; }

        .notes {
            background: #fefce8;
            border: 1px solid #fcd34d;
            border-radius: 6px;
            padding: 12px 15px;
            margin-bottom: 15px;
        }

        .notes-title {
            color: #92400e;
            font-weight: bold;
            font-size: 11px;
            margin-bottom: 5px;
        }

        .notes-content {
            color: #78350f;
            font-size: 10px;
        }

        .footer {
            text-align: center;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 9px;
        }

        .page-break { page-break-after: always; }
    </style>
</head>
<body>
    {{-- Encabezado --}}
    <div class="header">
        <div style="display: flex; justify-content: space-between; align-items: center;">
            <div>
                <div style="margin-bottom: 5px;">
                    <span class="badge">Finalizado</span>
                    <span class="subtitle" style="margin-left: 8px;">Reporte Oficial</span>
                </div>
                <h1>{{ $proyecto->nombre }}</h1>
            </div>
        </div>
    </div>

    @if($proyecto->descripcion)
    <p style="color: #475569; font-size: 10px; margin: 0 0 15px 0;">{{ $proyecto->descripcion }}</p>
    @endif

    {{-- Información básica --}}
    <table class="grid-4">
        <tr>
            <td>
                <div class="info-box">
                    <div class="info-label">Fecha Inicio</div>
                    <div class="info-value">{{ \Carbon\Carbon::parse($proyecto->fecha_inicio)->format('d/m/Y') }}</div>
                </div>
            </td>
            <td>
                <div class="info-box">
                    <div class="info-label">Fecha Fin Planeada</div>
                    <div class="info-value">{{ \Carbon\Carbon::parse($proyecto->fecha_fin)->format('d/m/Y') }}</div>
                </div>
            </td>
            <td>
                <div style="background: #ecfdf5; border: 1px solid #6ee7b7; border-radius: 6px; padding: 12px 15px;">
                    <div style="color: #047857; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 3px;">Fecha Fin Real</div>
                    <div style="color: #065f46; font-weight: bold; font-size: 12px;">{{ \Carbon\Carbon::parse($proyecto->fecha_fin_real)->format('d/m/Y') }}</div>
                </div>
            </td>
            <td>
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
    <div class="metrics-section">
        <div class="metrics-title">Resumen de Métricas</div>

        <div style="margin-bottom: 12px;">
            <div class="metric-box" style="margin-right: 1%;">
                <div class="metric-value">{{ $metricas['total'] }}</div>
                <div class="metric-label">Total</div>
            </div>
            <div class="metric-box green" style="margin: 0 1%;">
                <div class="metric-value">{{ $metricas['completadas'] }}</div>
                <div class="metric-label">Completadas</div>
            </div>
            <div class="metric-box blue" style="margin: 0 1%;">
                <div class="metric-value">{{ $metricas['a_tiempo'] }}</div>
                <div class="metric-label">A Tiempo</div>
            </div>
            <div class="metric-box red" style="margin-left: 1%;">
                <div class="metric-value">{{ $metricas['con_retraso'] }}</div>
                <div class="metric-label">Con Retraso</div>
            </div>
        </div>

        <div>
            <div class="metric-small-box" style="margin-right: 1%;">
                <div class="metric-small-value">{{ $metricas['porcentaje_completado'] }}%</div>
                <div class="metric-small-label">Completado</div>
            </div>
            <div class="metric-small-box purple" style="margin: 0 1%;">
                <div class="metric-small-value">{{ $metricas['promedio_eficiencia'] }}%</div>
                <div class="metric-small-label">Eficiencia</div>
            </div>
            <div class="metric-small-box indigo" style="margin-left: 1%;">
                <div class="metric-small-value">{{ $metricas['en_proceso'] }}</div>
                <div class="metric-small-label">Pendientes</div>
            </div>
        </div>
    </div>

    {{-- Tabla de Actividades --}}
    <div class="table-section">
        <div class="table-header">Detalle de Actividades</div>

        @if($actividades->isEmpty())
            <div style="padding: 20px; text-align: center; color: #94a3b8;">No hay actividades registradas</div>
        @else
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Actividad</th>
                        <th>Responsable</th>
                        <th class="center">Estatus</th>
                        <th class="center">Días Plan.</th>
                        <th class="center">Días Reales</th>
                        <th class="center">Eficiencia</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($actividades as $index => $act)
                    <tr>
                        <td style="color: #94a3b8;">{{ $index + 1 }}</td>
                        <td>
                            <div style="font-weight: 500; color: #1e293b;">{{ $act->nombre_actividad }}</div>
                            @if($act->cliente)<div style="color: #4338ca; font-size: 9px;">{{ $act->cliente }}</div>@endif
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
</body>
</html>
