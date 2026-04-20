<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nuevo Proyecto Asignado</title>
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
        .badge-usuario {
            background-color: #dbeafe;
            color: #1e40af;
        }
        .badge-ti {
            background-color: #cffafe;
            color: #155e75;
        }
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin: 20px 0;
        }
        .info-item {
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 6px;
            border-left: 4px solid #3b82f6;
        }
        .info-item label {
            display: block;
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .info-item span {
            font-size: 14px;
            color: #1e293b;
        }
        .descripcion {
            background-color: #f8fafc;
            padding: 15px;
            border-radius: 6px;
            margin: 20px 0;
            border-left: 4px solid #3b82f6;
        }
        .descripcion label {
            display: block;
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
            margin-bottom: 8px;
        }
        .descripcion p {
            margin: 0;
            font-size: 14px;
            color: #1e293b;
        }
        .btn {
            display: inline-block;
            padding: 12px 30px;
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 6px;
            font-weight: 600;
            text-align: center;
            margin: 0 auto;
        }
        .btn-container {
            text-align: center;
            margin-top: 30px;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            color: #94a3b8;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Nuevo Proyecto Asignado</h1>
            @if($tipo === 'responsable_ti')
            <span class="badge badge-ti">Responsable de TI</span>
            @else
            <span class="badge badge-usuario">Usuario</span>
            @endif
        </div>

        <p>Hola <strong>{{ $usuario->name }}</strong>,</p>

        <p>Se te ha asignado al siguiente proyecto:</p>

        <h2 style="color: #1e40af; margin: 20px 0;">{{ $proyecto->nombre }}</h2>

        <div class="info-grid">
            <div class="info-item">
                <label>Fecha de Inicio</label>
                <span>{{ \Carbon\Carbon::parse($proyecto->fecha_inicio)->format('d/m/Y') }}</span>
            </div>
            <div class="info-item">
                <label>Fecha de Fin</label>
                <span>{{ \Carbon\Carbon::parse($proyecto->fecha_fin)->format('d/m/Y') }}</span>
            </div>
        </div>

        @if($proyecto->descripcion)
        <div class="descripcion">
            <label>Descripción</label>
            <p>{{ $proyecto->descripcion }}</p>
        </div>
        @endif

        <div class="btn-container">
            <a href="{{ route('proyectos.show', $proyecto) }}" class="btn">Ver Proyecto</a>
        </div>

        <div class="footer">
            <p>Este correo fue enviado automáticamente por el sistema ERP.</p>
        </div>
    </div>
</body>
</html>