# Documentación General del Módulo de Sistemas (IT)

El módulo de Sistemas IT del ERP Estrategia e Innovación está diseñado para gestionar todas las operaciones del departamento de tecnologías de la información, incluyendo la gestión de activos, el control de credenciales, reservas de mantenimientos, control de inventario mediante códigos QR y un sistema de tickets para soporte técnico.

## Estructura del Módulo

Este módulo se compone principalmente de los siguientes elementos:

1. **Gestión de Tickets (`TicketController`)**: Permite a los usuarios solicitar soporte (hardware, software, mantenimiento).
2. **Gestión de Activos (`ActivosController` y `ActivosApiController`)**: Permite el inventario y administración de equipos de cómputo y periféricos. Integración con códigos QR.
3. **Control de Credenciales y Asignaciones (`CredencialEquipoController`)**: Lleva el registro de a quién pertenece cada equipo (principal y secundarios), correos electrónicos configurados y firma de cartas responsivas.
4. **Mantenimientos (`MaintenanceController`)**: Agenda para que los usuarios reserven mantenimientos, así como bloqueos de horarios por parte de los administradores.
5. **Notificaciones (`NotificationController`)**: API para avisar a los administradores de los nuevos tickets.

En la carpeta actual puedes encontrar documentación detallada sobre:
- [Controladores (Controllers)](Controllers.md)
- [Modelos y Migraciones](Models_Migrations.md)
- [Endpoints y Rutas](Endpoints.md)
- [Revisión de Seguridad y Limpieza de Código](Security_Code_Review.md)
