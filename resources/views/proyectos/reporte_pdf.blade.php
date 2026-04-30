<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>Reporte - {{ $proyecto->nombre }}</title>
    <style>
        @page {
            size: A4;
            margin: 2.5cm 2cm 2.5cm 2cm;
        }

        body {
            font-family: 'Helvetica', 'Arial', sans-serif;
            font-size: 11px;
            line-height: 1.4;
            color: #334155;
            margin: 0;
            padding: 0;
        }

        /* --- Header --- */
        .header {
            width: 100%;
            border-bottom: 2px solid #4f46e5;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
        }

        .logo {
            width: 180px;
            max-height: 60px;
            object-fit: contain;
        }

        .header-title {
            text-align: right;
            vertical-align: middle;
        }

        .badge-finalizado {
            display: inline-block;
            background-color: #10b981;
            color: #ffffff;
            font-size: 10px;
            font-weight: bold;
            padding: 4px 8px;
            border-radius: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 5px;
        }

        h1 {
            color: #1e1b4b;
            font-size: 20px;
            font-weight: bold;
            margin: 0 0 5px 0;
        }

        .subtitle {
            color: #64748b;
            font-size: 11px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* --- Project Description --- */
        .description-box {
            background-color: #f8fafc;
            border-left: 4px solid #4f46e5;
            padding: 12px 15px;
            margin-bottom: 20px;
            color: #475569;
            font-size: 11px;
        }

        /* --- Grid Information --- */
        table.grid-info {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
            margin-left: -10px;
            margin-right: -10px;
            margin-bottom: 20px;
        }

        table.grid-info td {
            width: 25%;
            vertical-align: top;
            background-color: #f1f5f9;
            padding: 10px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }

        .info-label {
            color: #64748b;
            font-size: 9px;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .info-value {
            color: #0f172a;
            font-size: 13px;
            font-weight: bold;
        }

        /* --- Team --- */
        .team-box {
            margin-bottom: 25px;
        }
        .team-title {
            font-size: 10px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: bold;
            margin-bottom: 8px;
        }
        .team-member {
            display: inline-block;
            background-color: #eef2ff;
            color: #4338ca;
            padding: 3px 8px;
            border-radius: 4px;
            border: 1px solid #c7d2fe;
            margin-right: 5px;
            margin-bottom: 5px;
            font-size: 10px;
            font-weight: bold;
        }

        /* --- Section Titles --- */
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #1e293b;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 5px;
            margin-top: 30px;
            margin-bottom: 15px;
            text-transform: uppercase;
        }

        /* --- Metrics --- */
        table.metrics-primary {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
            margin-left: -10px;
            margin-right: -10px;
            margin-bottom: 15px;
        }

        table.metrics-primary td {
            width: 25%;
            text-align: center;
            padding: 15px 10px;
            border-radius: 8px;
            color: #ffffff;
        }

        .bg-total { background-color: #334155; }
        .bg-completadas { background-color: #10b981; }
        .bg-atiempo { background-color: #3b82f6; }
        .bg-retraso { background-color: #ef4444; }

        .metric-big-value {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .metric-big-label {
            font-size: 10px;
            text-transform: uppercase;
            opacity: 0.9;
        }

        table.metrics-secondary {
            width: 100%;
            border-collapse: separate;
            border-spacing: 10px 0;
            margin-left: -10px;
            margin-right: -10px;
            margin-bottom: 30px;
        }

        table.metrics-secondary td {
            width: 33.33%;
            text-align: center;
            padding: 12px 10px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }

        .metric-sec-value {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 3px;
        }
        .metric-sec-label {
            font-size: 10px;
            text-transform: uppercase;
            font-weight: bold;
        }

        .border-amber { border-color: #fcd34d !important; background-color: #fffbeb !important; }
        .text-amber { color: #d97706; }
        
        .border-purple { border-color: #c4b5fd !important; background-color: #f5f3ff !important; }
        .text-purple { color: #7c3aed; }
        
        .border-indigo { border-color: #c7d2fe !important; background-color: #eef2ff !important; }
        .text-indigo { color: #4338ca; }

        /* --- Activities Table --- */
        table.activities {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 10px;
        }

        table.activities th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 9px;
            padding: 8px 10px;
            text-align: left;
            border-bottom: 2px solid #cbd5e1;
        }

        table.activities td {
            padding: 8px 10px;
            border-bottom: 1px solid #e2e8f0;
            vertical-align: middle;
        }

        table.activities tr:nth-child(even) {
            background-color: #f8fafc;
        }

        .act-name {
            font-weight: bold;
            color: #1e293b;
        }
        
        .act-client {
            color: #4f46e5;
            font-size: 9px;
        }

        .status-badge {
            display: inline-block;
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: bold;
        }

        .status-Completado { background-color: #d1fae5; color: #047857; }
        .status-Completado-con-retardo { background-color: #fee2e2; color: #b91c1c; }
        .status-En-proceso { background-color: #dbeafe; color: #1d4ed8; }
        .status-Planeado { background-color: #f1f5f9; color: #475569; }
        .status-Por-Aprobar { background-color: #fef3c7; color: #b45309; }
        .status-Por-Validar { background-color: #f3e8ff; color: #7e22ce; }
        .status-Retardo { background-color: #fee2e2; color: #b91c1c; }
        .status-Rechazado { background-color: #fee2e2; color: #b91c1c; }
        
        .status-default { background-color: #f1f5f9; color: #475569; }

        .efficiency {
            font-weight: bold;
        }
        .eff-green { color: #059669; }
        .eff-amber { color: #d97706; }
        .eff-red { color: #dc2626; }

        /* --- Notes --- */
        .notes-box {
            background-color: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 6px;
            padding: 15px;
            margin-top: 20px;
            page-break-inside: avoid;
        }
        .notes-title {
            color: #b45309;
            font-size: 11px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }
        .notes-content {
            color: #78350f;
            font-size: 10px;
        }

        /* --- Footer (Page Numbers) --- */
        footer {
            position: fixed;
            bottom: -1.5cm;
            left: 0;
            right: 0;
            height: 1.5cm;
            color: #94a3b8;
            font-size: 9px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
            padding-top: 5px;
        }

        footer .page-number:after {
            content: counter(page);
        }

    </style>
</head>
<body>

    @php
        $logoPath = public_path('images/logo-ei.png');
        $logoSrc = '';
        if (file_exists($logoPath)) {
            $logoData = base64_encode(file_get_contents($logoPath));
            $logoSrc = 'data:image/png;base64,' . $logoData;
        }
    @endphp

    <footer>
        Reporte generado el {{ now()->format('d/m/Y H:i') }} | Estrategia e Innovación | Página <span class="page-number"></span>
    </footer>

    <div class="header">
        <table class="header-table">
            <tr>
                <td style="width: 50%; vertical-align: bottom;">
                    @if($logoSrc)
                        <img src="{{ $logoSrc }}" class="logo" alt="Logo">
                    @else
                        <h2 style="color: #4f46e5; margin: 0;">Estrategia e Innovación</h2>
                    @endif
                </td>
                <td class="header-title" style="width: 50%;">
                    @if($proyecto->finalizado || $proyecto->fecha_fin_real)
                        <div class="badge-finalizado">Finalizado</div>
                    @endif
                    <div class="subtitle">Reporte Oficial de Proyecto</div>
                    <h1>{{ $proyecto->nombre }}</h1>
                </td>
            </tr>
        </table>
    </div>

    @if($proyecto->descripcion)
    <div class="description-box">
        {{ $proyecto->descripcion }}
    </div>
    @endif

    <table class="grid-info">
        <tr>
            <td>
                <div class="info-label">Fecha Inicio</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($proyecto->fecha_inicio)->format('d/m/Y') }}</div>
            </td>
            <td>
                <div class="info-label">Fecha Fin Planeada</div>
                <div class="info-value">{{ \Carbon\Carbon::parse($proyecto->fecha_fin)->format('d/m/Y') }}</div>
            </td>
            <td style="background-color: #ecfdf5; border-color: #a7f3d0;">
                <div class="info-label" style="color: #047857;">Fecha Fin Real</div>
                <div class="info-value" style="color: #065f46;">
                    {{ $proyecto->fecha_fin_real ? \Carbon\Carbon::parse($proyecto->fecha_fin_real)->format('d/m/Y') : 'Pendiente' }}
                </div>
            </td>
            <td>
                <div class="info-label">Responsable</div>
                <div class="info-value">{{ $proyecto->creador->name ?? 'N/A' }}</div>
            </td>
        </tr>
    </table>

    @if($proyecto->usuarios->count() > 0)
    <div class="team-box">
        <div class="team-title">Equipo de Trabajo Asignado</div>
        <div>
            @foreach($proyecto->usuarios as $usu)
                <span class="team-member">{{ $usu->name }}</span>
            @endforeach
        </div>
    </div>
    @endif

    <div class="section-title">Resumen de Métricas</div>

    <table class="metrics-primary">
        <tr>
            <td class="bg-total">
                <div class="metric-big-value">{{ $metricas['total'] }}</div>
                <div class="metric-big-label">Total</div>
            </td>
            <td class="bg-completadas">
                <div class="metric-big-value">{{ $metricas['completadas'] }}</div>
                <div class="metric-big-label">Completadas</div>
            </td>
            <td class="bg-atiempo">
                <div class="metric-big-value">{{ $metricas['a_tiempo'] }}</div>
                <div class="metric-big-label">A Tiempo</div>
            </td>
            <td class="bg-retraso">
                <div class="metric-big-value">{{ $metricas['con_retraso'] }}</div>
                <div class="metric-big-label">Con Retraso</div>
            </td>
        </tr>
    </table>

    <table class="metrics-secondary">
        <tr>
            <td class="border-amber">
                <div class="metric-sec-value text-amber">{{ $metricas['porcentaje_completado'] }}%</div>
                <div class="metric-sec-label text-amber">Completado</div>
            </td>
            <td class="border-purple">
                <div class="metric-sec-value text-purple">{{ $metricas['promedio_eficiencia'] }}%</div>
                <div class="metric-sec-label text-purple">Eficiencia Promedio</div>
            </td>
            <td class="border-indigo">
                <div class="metric-sec-value text-indigo">{{ $metricas['en_proceso'] }}</div>
                <div class="metric-sec-label text-indigo">Actividades Pendientes</div>
            </td>
        </tr>
    </table>

    <div class="section-title" style="page-break-before: auto;">Detalle de Actividades</div>

    @if($actividades->isEmpty())
        <div style="text-align: center; padding: 20px; color: #94a3b8; border: 1px dashed #cbd5e1;">
            No hay actividades registradas en este proyecto.
        </div>
    @else
        <table class="activities">
            <thead>
                <tr>
                    <th style="width: 4%;">#</th>
                    <th style="width: 32%;">Actividad</th>
                    <th style="width: 20%;">Responsable</th>
                    <th style="width: 14%; text-align: center;">Estatus</th>
                    <th style="width: 10%; text-align: center;">Días Plan.</th>
                    <th style="width: 10%; text-align: center;">Días Reales</th>
                    <th style="width: 10%; text-align: center;">Eficiencia</th>
                </tr>
            </thead>
            <tbody>
                @foreach($actividades as $index => $act)
                <tr>
                    <td style="color: #94a3b8;">{{ $index + 1 }}</td>
                    <td>
                        <div class="act-name">{{ $act->nombre_actividad }}</div>
                        @if($act->cliente)<div class="act-client">{{ $act->cliente }}</div>@endif
                    </td>
                    <td style="color: #475569;">{{ $act->user->name ?? 'N/A' }}</td>
                    <td style="text-align: center;">
                        @php
                            $statusClass = 'status-' . str_replace(' ', '-', $act->estatus);
                        @endphp
                        <span class="status-badge {{ $statusClass }}">{{ $act->estatus }}</span>
                    </td>
                    <td style="text-align: center; color: #64748b;">{{ $act->metrico ?? '-' }}</td>
                    <td style="text-align: center; color: #64748b;">{{ $act->resultado_dias ?? '-' }}</td>
                    <td style="text-align: center;">
                        @if($act->porcentaje !== null)
                            @if($act->porcentaje == 100)
                                <span class="efficiency eff-green">{{ $act->porcentaje }}%</span>
                            @elseif($act->porcentaje >= 50)
                                <span class="efficiency eff-amber">{{ $act->porcentaje }}%</span>
                            @else
                                <span class="efficiency eff-red">{{ $act->porcentaje }}%</span>
                            @endif
                        @else
                            <span style="color: #cbd5e1;">-</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($proyecto->notas)
    <div class="notes-box">
        <div class="notes-title">Notas del Proyecto</div>
        <div class="notes-content">
            {!! nl2br(e($proyecto->notas)) !!}
        </div>
    </div>
    @endif

</body>
</html>
