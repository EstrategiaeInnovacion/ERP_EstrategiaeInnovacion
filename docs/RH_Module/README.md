# Documentación General del Módulo de Recursos Humanos (RH)

El módulo de RH del ERP Estrategia e Innovación centraliza la administración del personal. Está estructurado para automatizar los procesos de asistencia, administración de expedientes, envío de notificaciones y la capacitación continua de los empleados.

## Estructura del Módulo

Este módulo se compone de los siguientes elementos principales:

1. **Gestión de Expedientes (`ExpedienteController`)**: Permite la visualización, actualización, baja y reactivación de empleados. Soporta la subida de documentos y extracción automática de información personal desde un documento Excel ("Formato ID").
2. **Reloj Checador y Asistencias (`RelojChecadorImportController` y `AvisoAsistenciaController`)**: Administra la asistencia diaria. Cuenta con importación asíncrona de datos desde relojes biométricos, paneles de KPls (retrasos, faltas) y un sistema para notificar incidencias.
3. **Capacitaciones (`CapacitacionController`)**: Plataforma para administrar cursos, links de YouTube, documentos adjuntos y asignaciones dirigidas a puestos específicos o a todo el personal.
4. **Recordatorios y Días Festivos (`RecordatorioController`, `DiaFestivoController`)**: Automatiza alertas de cumpleaños y aniversarios, así como la configuración del calendario oficial de descanso de la empresa.

En esta carpeta puedes encontrar documentación detallada sobre:
- [Controladores (Controllers)](Controllers.md)
- [Modelos y Migraciones](Models_Migrations.md)
- [Endpoints y Rutas](Endpoints.md)
- [Revisión de Seguridad y Limpieza de Código](Security_Code_Review.md)
