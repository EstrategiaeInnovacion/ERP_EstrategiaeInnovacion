<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ticket Creado - {{ $ticket->folio }}</title>
    <style>
        body {
            margin: 0;
            padding: 0;
            background-color: #f3f4f6;
            font-family: 'Inter', Arial, sans-serif;
        }
        
        .container {
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }
        
        .header-icon {
            width: 60px;
            height: 60px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 12px;
            margin: 0 auto 20px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 700;
        }
        
        .header p {
            margin: 10px 0 0;
            font-size: 16px;
            opacity: 0.9;
        }
        
        .content {
            padding: 40px 30px;
        }
        
        .ticket-info {
            background-color: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .ticket-folio {
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            padding: 12px 20px;
            border-radius: 8px;
            font-size: 18px;
            font-weight: 700;
            text-align: center;
            margin-bottom: 20px;
        }
        
        .codigo-seguridad {
            background: linear-gradient(135deg, #dc2626, #991b1b);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            text-align: center;
            margin-bottom: 20px;
            border: 2px dashed rgba(255, 255, 255, 0.3);
        }
        
        .codigo-seguridad-label {
            font-size: 12px;
            font-weight: 500;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        .codigo-seguridad-numero {
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 3px;
            font-family: 'Courier New', monospace;
        }
        
        .info-row {
            display: flex;
            margin-bottom: 15px;
            align-items: flex-start;
        }
        
        .info-label {
            font-weight: 600;
            color: #374151;
            min-width: 140px;
            margin-right: 15px;
        }
        
        .info-value {
            color: #6b7280;
            flex: 1;
        }
        
        .status-badge {
            display: inline-block;
            background-color: #fef2f2;
            color: #dc2626;
            padding: 6px 12px;
            border-radius: 6px;
            font-size: 14px;
            font-weight: 600;
            border: 1px solid #fecaca;
        }
        
        .description-box {
            background-color: #ffffff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 20px;
            margin-top: 10px;
            line-height: 1.6;
        }
        
        .next-steps {
            background-color: #f0f9ff;
            border: 2px solid #bae6fd;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 30px;
        }
        
        .next-steps h3 {
            margin: 0 0 15px;
            color: #1e40af;
            font-size: 18px;
            font-weight: 700;
        }
        
        .next-steps ul {
            margin: 0;
            padding-left: 20px;
            color: #475569;
        }
        
        .next-steps li {
            margin-bottom: 8px;
        }
        
        .footer {
            background-color: #f8fafc;
            padding: 30px;
            text-align: center;
            border-top: 1px solid #e2e8f0;
        }
        
        .footer p {
            margin: 5px 0;
            color: #6b7280;
            font-size: 14px;
        }
        
        .contact-info {
            background-color: #fff;
            border: 1px solid #d1d5db;
            border-radius: 8px;
            padding: 20px;
            margin-top: 20px;
        }
        
        .contact-info h4 {
            margin: 0 0 10px;
            color: #374151;
            font-size: 16px;
        }
        
        .tipo-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 6px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }
        
        .tipo-software { background-color: #dbeafe; color: #1e40af; }
        .tipo-hardware { background-color: #fef3c7; color: #d97706; }
        .tipo-mantenimiento { background-color: #d1fae5; color: #065f46; }
        
        @media only screen and (max-width: 600px) {
            .content, .header, .footer {
                padding: 20px;
            }
            
            .info-row {
                flex-direction: column;
            }
            
            .info-label {
                min-width: auto;
                margin-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="header">
            <div class="header-icon">
                <svg width="30" height="30" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                </svg>
            </div>
            <h1>¡Ticket Creado Exitosamente!</h1>
            <p>Tu solicitud ha sido registrada en nuestro sistema</p>
        </div>
        
        <!-- Content -->
        <div class="content">
            <div class="ticket-info">
                <div class="ticket-folio">
                    FOLIO: {{ $ticket->folio }}
                </div>
                
                <div class="codigo-seguridad">
                    <div class="codigo-seguridad-label">Código de Seguridad</div>
                    <div class="codigo-seguridad-numero">{{ $ticket->codigo_seguridad }}</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Solicitante:</div>
                    <div class="info-value">{{ $ticket->nombre_solicitante }}</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Correo:</div>
                    <div class="info-value">{{ $ticket->correo_solicitante }}</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Tipo:</div>
                    <div class="info-value">
                        <span class="tipo-badge tipo-{{ $ticket->tipo_problema }}">
                            {{ ucfirst($ticket->tipo_problema) }}
                        </span>
                    </div>
                </div>
                
                @if($ticket->nombre_programa)
                @php
                    $programLabel = match ($ticket->tipo_problema) {
                        'hardware' => 'Tipo de equipo',
                        'software' => 'Programa/Software',
                        default => 'Programa/Equipo',
                    };
                @endphp
                <div class="info-row">
                    <div class="info-label">{{ $programLabel }}:</div>
                    <div class="info-value">{{ $ticket->nombre_programa }}</div>
                </div>
                @endif
                
                <div class="info-row">
                    <div class="info-label">Estado:</div>
                    <div class="info-value">
                        <span class="status-badge">{{ ucfirst($ticket->estado) }}</span>
                    </div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Fecha de Apertura:</div>
                    <div class="info-value">{{ $ticket->fecha_apertura ? $ticket->fecha_apertura->format('d/m/Y H:i') : $ticket->created_at->format('d/m/Y H:i') }}</div>
                </div>
                
                <div class="info-row">
                    <div class="info-label">Descripción:</div>
                    <div class="info-value">
                        <div class="description-box">
                            {{ $ticket->descripcion_problema }}
                        </div>
                    </div>
                </div>
                
                @if($ticket->imagenes && count($ticket->imagenes) > 0)
                <div class="info-row">
                    <div class="info-label">Archivos adjuntos:</div>
                    <div class="info-value">{{ count($ticket->imagenes) }} imagen(es) adjuntada(s)</div>
                </div>
                @endif
            </div>
            
            <!-- Next Steps -->
            <div class="next-steps">
                <h3>¿Qué sigue?</h3>
                <ul>
                    <li><strong>Revisión inicial:</strong> Nuestro equipo técnico revisará tu solicitud en las próximas 2-4 horas hábiles</li>
                    <li><strong>Asignación de prioridad:</strong> Clasificaremos tu ticket según la urgencia y complejidad</li>
                    <li><strong>Seguimiento:</strong> Te contactaremos por correo o teléfono para brindarte actualizaciones</li>
                    <li><strong>Resolución:</strong> Trabajaremos en la solución y te notificaremos cuando esté lista</li>
                </ul>
            </div>
            
            <!-- Contact Info -->
            <div class="contact-info">
                <h4>¿Necesitas ayuda inmediata?</h4>
                <p><strong>Email:</strong> sistemas@estrategiaeinnovacion.com.mx</p>
                <p><strong>Horario de atención:</strong> Lunes a Viernes, 8:00 AM - 6:00 PM</p>
            </div>
        </div>
        
        <!-- Footer -->
        <div class="footer">
            <p><strong>Sistema de Tickets TI</strong></p>
            <p>Estrategia e Innovación</p>
            <p>&copy; {{ date('Y') }} Todos los derechos reservados</p>
            
            <p style="margin-top: 20px; font-size: 12px; color: #9ca3af;">
                Por favor, conserva este correo para tu referencia. 
                El folio <strong>{{ $ticket->folio }}</strong> te permitirá hacer seguimiento de tu solicitud.
                <br><br>
                <strong style="color: #dc2626;">IMPORTANTE:</strong> El código de seguridad <strong>{{ $ticket->codigo_seguridad }}</strong>
                es necesario si deseas cancelar este ticket. Manténlo en un lugar seguro.
            </p>
        </div>
    </div>
</body>
</html>