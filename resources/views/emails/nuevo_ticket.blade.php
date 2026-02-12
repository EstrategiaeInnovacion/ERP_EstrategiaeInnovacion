<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Ticket - {{ $ticket->folio }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background-color: #ffffff;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .header {
            text-align: center;
            border-bottom: 3px solid #3b82f6;
            padding-bottom: 20px;
            margin-bottom: 25px;
        }
        .header h1 {
            color: #1e40af;
            margin: 0;
            font-size: 24px;
        }
        .badge {
            display: inline-block;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
            margin-top: 10px;
        }
        .badge-software {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .badge-hardware {
            background-color: #fef3c7;
            color: #92400e;
        }
        .badge-mantenimiento {
            background-color: #d1fae5;
            color: #065f46;
        }
        .info-section {
            margin-bottom: 20px;
        }
        .info-section h3 {
            color: #374151;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            border-bottom: 1px solid #e5e7eb;
            padding-bottom: 5px;
        }
        .info-row {
            display: flex;
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        .info-label {
            font-weight: 600;
            color: #6b7280;
            width: 140px;
            flex-shrink: 0;
        }
        .info-value {
            color: #111827;
        }
        .description-box {
            background-color: #f9fafb;
            border-left: 4px solid #3b82f6;
            padding: 15px;
            margin-top: 10px;
            border-radius: 0 8px 8px 0;
        }
        .folio-box {
            text-align: center;
            background-color: #eff6ff;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .folio-box .folio {
            font-size: 28px;
            font-weight: 700;
            color: #1e40af;
            font-family: monospace;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e5e7eb;
            color: #6b7280;
            font-size: 12px;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background-color: #3b82f6;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            margin-top: 15px;
        }
        .btn:hover {
            background-color: #2563eb;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Nuevo Ticket de Soporte</h1>
            <span class="badge badge-{{ $ticket->tipo_problema }}">
                {{ $ticket->tipo_problema }}
            </span>
        </div>

        <div class="folio-box">
            <div style="font-size: 12px; color: #6b7280; text-transform: uppercase;">Número de Ticket</div>
            <div class="folio">{{ $ticket->folio }}</div>
        </div>

        <div class="info-section">
            <h3>Información del Solicitante</h3>
            <div class="info-row">
                <span class="info-label">Nombre:</span>
                <span class="info-value">{{ $ticket->nombre_solicitante }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Correo:</span>
                <span class="info-value">{{ $ticket->correo_solicitante }}</span>
            </div>
            <div class="info-row">
                <span class="info-label">Fecha:</span>
                <span class="info-value">{{ $ticket->created_at->setTimezone('America/Mexico_City')->format('d/m/Y H:i') }}</span>
            </div>
        </div>

        <div class="info-section">
            <h3>Detalles del Ticket</h3>
            @if($ticket->tipo_problema !== 'mantenimiento' && $ticket->nombre_programa)
            <div class="info-row">
                <span class="info-label">Programa/Equipo:</span>
                <span class="info-value">{{ $ticket->nombre_programa }}</span>
            </div>
            @endif
            
            @if($ticket->tipo_problema === 'mantenimiento' && $ticket->maintenance_scheduled_at)
            <div class="info-row">
                <span class="info-label">Cita Programada:</span>
                <span class="info-value">
                    {{ \Carbon\Carbon::parse($ticket->maintenance_scheduled_at)->setTimezone('America/Mexico_City')->format('d/m/Y \a \l\a\s H:i') }}
                </span>
            </div>
            @endif

            <div class="info-row">
                <span class="info-label">Estado:</span>
                <span class="info-value" style="color: #059669; font-weight: 600;">{{ ucfirst($ticket->estado) }}</span>
            </div>
        </div>

        @if($ticket->descripcion_problema)
        <div class="info-section">
            <h3>Descripción del Problema</h3>
            <div class="description-box">
                {!! nl2br(e($ticket->descripcion_problema)) !!}
            </div>
        </div>
        @endif

        <div style="text-align: center; margin-top: 25px;">
            <a href="{{ config('app.url') }}/sistemas-it/admin/tickets" class="btn">
                Ver en el Sistema
            </a>
        </div>

        <div class="footer">
            <p>Este correo fue generado automáticamente por el sistema ERP de Estrategia e Innovación.</p>
            <p>© {{ date('Y') }} Estrategia e Innovación. Todos los derechos reservados.</p>
        </div>
    </div>
</body>
</html>
