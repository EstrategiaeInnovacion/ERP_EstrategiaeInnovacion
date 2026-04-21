# Modelos y Migraciones del Módulo IT

## 1. Modelos

Los modelos se encuentran en `app/Models/Sistemas_IT/`.

- **`ComputerProfile.php`**: Perfil técnico o "expediente" de la máquina (marca, modelo, tipo de disco, capacidad RAM, observaciones estéticas).
- **`EquipoAsignado.php`**: Representa la asignación de un equipo o dispositivo a un empleado. Maneja el concepto de `es_principal` para separar equipos de trabajo base vs secundarios o de préstamo.
- **`EquipoCorreo.php`**: Relación `1:N` para los correos y contraseñas que están dados de alta en un `EquipoAsignado`.
- **`EquipoPeriferico.php`**: Relación `1:N` de los accesorios y periféricos ligados a un `EquipoAsignado`.
- **`MaintenanceBlockedSlot.php`**: Almacena los rangos de fechas u horas en las que TI no está disponible, bloqueando el calendario para el usuario.
- **`MaintenanceBooking.php`**: Reserva efectiva que liga un `Ticket` con un `MaintenanceSlot`.
- **`MaintenanceSlot.php`**: Un horario habilitado o posible en el calendario donde se pueden agendar mantenimientos.
- **`Ticket.php`**: Modelo central de incidencias. Contiene datos del solicitante, problemas, estado, tipo (hardware, software, mantenimiento), y campos extendidos para reportes.
- **`CredencialEquipo.php`**: (Uso limitado/histórico, se prefiere `EquipoAsignado`).

## 2. Migraciones

Ubicadas en `database/migrations/`:

- **`2025_12_09_190612_create_sistemas_it_tables.php`**: Crea las tablas fundamentales `tickets`, `maintenance_slots`, `maintenance_bookings` y `computer_profiles`.
- **`2026_02_12_000001_create_maintenance_blocked_slots_table.php`**: Añade tabla de exclusión de horarios de mantenimiento.
- **`2026_03_24_120000_create_it_credenciales_equipos_table.php`**: Crea `it_equipos_asignados`.
- **`2026_03_24_120001_create_it_equipos_correos_table.php`**: Crea la tabla relacional de correos ligados al equipo.
- **`2026_03_24_120002_create_it_equipos_perifericos_table.php`**: Crea la tabla relacional de periféricos.
- **`2026_03_27_134325_add_es_principal_to_it_equipos_asignados_table.php`**: Agrega el flag de equipo secundario.
- **`2026_04_17_000002_add_equipo_asignado_id_to_computer_profiles.php`**: Liga los perfiles técnicos de `computer_profiles` directamente a la tabla de `it_equipos_asignados`.
- **`2026_04_17_100000_add_maintenance_schedule_to_computer_profiles.php`**: Fechas e historial de mantenimientos en perfiles de equipo.

### Base de Datos Externa de Activos (Ubicadas en `database/migrations/Activos/`)
El módulo también cuenta con una base de datos secundaria/externa para la gestión independiente de activos. Sus migraciones asociadas son:

- **`2026_02_18_184711_create_devices_table.php`**: Crea la tabla de dispositivos (`devices`).
- **`2026_02_18_184712_create_credentials_table.php`**: Crea la tabla de credenciales de los dispositivos (`credentials`).
- **`2026_02_18_184716_create_assignments_table.php`**: Crea la tabla de historiales de asignaciones de dispositivos (`assignments`).
- **`2026_02_23_180000_create_device_photos_table.php`**: Crea la tabla para almacenar las fotos subidas de los dispositivos (`device_photos`).
- **`2026_02_23_180001_create_device_documents_table.php`**: Crea la tabla para guardar documentos asociados a los dispositivos (`device_documents`).
- **`2026_03_19_142448_add_performance_indexes.php`**: Añade índices de rendimiento para optimizar las consultas a la base de datos de activos.
- **`2026_03_23_173400_create_employees_table.php`**: Crea la tabla de copia local de empleados dentro de la base de activos (`employees`).
- **`2026_03_23_173538_add_employee_id_to_assignments_table.php`**: Relaciona el historial de asignaciones directamente con el empleado de forma estructural.
