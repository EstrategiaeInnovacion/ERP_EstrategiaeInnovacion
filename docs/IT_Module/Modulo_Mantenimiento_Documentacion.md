# Módulo IT — Mantenimiento de Equipos — Documentación Maestra
> **ERP Estrategia e Innovación** · Versión documental: Abril 2026  
> Audiencia: Desarrolladores de mantenimiento, administradores de TI

---

## Tabla de Contenido

1. [Visión General](#1-visión-general)
2. [Arquitectura del Sistema de Mantenimiento](#2-arquitectura-del-sistema-de-mantenimiento)
3. [Flujo Completo de una Solicitud de Mantenimiento](#3-flujo-completo-de-una-solicitud-de-mantenimiento)
4. [Sistema de Calendario — Slots y Disponibilidad](#4-sistema-de-calendario--slots-y-disponibilidad)
5. [Bloqueo de Horarios (`MaintenanceBlockedSlot`)](#5-bloqueo-de-horarios-maintenanceblockedslot)
6. [Ficha Técnica del Equipo (`ComputerProfile`)](#6-ficha-técnica-del-equipo-computerprofile)
7. [Reporte Técnico — Campos del Ticket de Mantenimiento](#7-reporte-técnico--campos-del-ticket-de-mantenimiento)
8. [Referencia de Métodos — `MaintenanceController`](#8-referencia-de-métodos--maintenancecontroller)
9. [Modelos Eloquent — Mantenimiento](#9-modelos-eloquent--mantenimiento)
10. [Panel Administrativo](#10-panel-administrativo)
11. [Referencia de Rutas](#11-referencia-de-rutas)
12. [Historial de Migraciones](#12-historial-de-migraciones)
13. [Guía de Mantenimiento del Módulo](#13-guía-de-mantenimiento-del-módulo)

---

## 1. Visión General

El submódulo de **Mantenimiento** permite a los empleados solicitar citas para que TI revise, limpie o repare su equipo de cómputo. El sistema gestiona un calendario de disponibilidad de 7 slots horarios diarios, bloqueos administrativos y la documentación técnica del trabajo realizado.

A diferencia de los tickets de software y hardware, el ticket de mantenimiento:
- Requiere seleccionar una fecha y hora disponible (cita)
- Genera una **ficha técnica del equipo** (`ComputerProfile`) al cerrarse
- Tiene campos adicionales de reporte técnico llenados por TI
- Vincula el ticket con el historial del equipo para mantenimientos futuros

### Propósito de Negocio

| Necesidad | Solución |
|---|---|
| Agenda de citas sin doble reservación | Verificación en tiempo real con `checkAvailability()` |
| Control de disponibilidad mensual | API `availability()` con mapa de estados por día |
| Bloquear días festivos o vacaciones de TI | `MaintenanceBlockedSlot` con rango de fechas |
| Documentar el trabajo técnico realizado | Campos de reporte en el ticket + `ComputerProfile` |
| Historial de mantenimientos por equipo | `ComputerProfile.last_maintenance_at` + `next_maintenance_at` |
| Recordatorios de mantenimiento preventivo | Campo `maintenance_reminder_sent_at` |

---

## 2. Arquitectura del Sistema de Mantenimiento

```
┌────────────────────────────────────────────────────────────────┐
│              SISTEMA DE MANTENIMIENTO IT                       │
│                                                                │
│  Lado del Empleado (rutas /maintenance/ y /ticket/)           │
│    ├── GET /maintenance/availability → Mapa mensual de días    │
│    ├── GET /maintenance/slots        → Slots de un día         │
│    ├── GET /maintenance/check-availability → Verificación spot │
│    └── GET /ticket/create/mantenimiento → Formulario de cita   │
│                                                                │
│  Lado del Administrador (rutas /admin/)                        │
│    ├── GET /admin/maintenance        → Panel con agenda        │
│    ├── GET /admin/maintenance/week   → Vista semanal JSON      │
│    ├── GET /admin/maintenance/calendar → Datos del calendario  │
│    ├── POST /admin/maintenance/block-slot → Bloquear horarios  │
│    └── DELETE /admin/maintenance/block-slot/{id} → Desbloquear │
│                                                                │
│  MaintenanceController                                         │
│    ├── Constante TIME_SLOTS (7 slots: 09:00 - 16:00)           │
│    ├── availability() → JSON por día del mes                   │
│    ├── slots()        → JSON por hora del día                  │
│    ├── checkAvailability() → JSON: ¿disponible este slot?      │
│    ├── adminIndex()   → Vista principal admin                  │
│    ├── getWeekMaintenances() → JSON semana actual              │
│    ├── getCalendarData()    → JSON para FullCalendar           │
│    ├── blockSlot()    → Crea bloqueo en BD                     │
│    └── unblockSlot()  → Elimina bloqueo                        │
│                                                                │
│  Modelos                                                       │
│    ├── MaintenanceBlockedSlot → Horarios bloqueados por TI     │
│    ├── ComputerProfile        → Ficha técnica del equipo       │
│    ├── MaintenanceBooking     → Reserva ligada a Ticket (legado)│
│    └── MaintenanceSlot        → Slot disponible (legado)       │
└────────────────────────────────────────────────────────────────┘
```

---

## 3. Flujo Completo de una Solicitud de Mantenimiento

```
Empleado accede a /ticket/create/mantenimiento
              │
              ▼
  Calendario mensual se carga en el frontend
  GET /maintenance/availability?month=2026-04
  ← Retorna mapa de días (available/partial/full/blocked/past)
              │
  Empleado hace clic en un día disponible
              ▼
  GET /maintenance/slots?date=2026-04-28
  ← Retorna los 7 slots horarios con su estado
  (available / booked / blocked / past)
              │
  Empleado selecciona un slot disponible
              ▼
  Verificación spot en tiempo real:
  GET /maintenance/check-availability?date=2026-04-28&time=10:00
  ← { available: true, message: "Horario disponible." }
              │
  Empleado llena el formulario y envía
              ▼
        POST /ticket
  body: {
    tipo_problema: 'mantenimiento',
    fecha_requerida: '2026-04-28',
    hora_requerida: '10:00',
    maintenance_slot_id: 5  (legado)
  }
              │
  TicketController::store()
  └── Construye maintenance_scheduled_at:
      Carbon::parse('2026-04-28 10:00', 'America/Mexico_City')
  └── Crea Ticket con estado='abierto'
              │
              ▼
  Notificaciones: n8n + correo → Admin TI

──────── Lado TI (admin) ──────────────────────────────────────

  Admin ve en /admin/maintenance:
  - Tickets de mantenimiento pendientes
  - Computadoras sin ficha técnica
  - Bloqueos activos del calendario
              │
  Admin abre el ticket de mantenimiento
              │
  El técnico recibe el equipo → llena el reporte:
  PATCH /admin/tickets/{ticket}
  body: {
    estado: 'en_proceso',
    equipment_identifier: 'PC-JUAN-001',
    equipment_brand: 'HP',
    equipment_model: 'EliteBook 840',
    disk_type: 'SSD 256GB',
    ram_capacity: '8GB DDR4',
    battery_status: 'functional',
    aesthetic_observations: 'Teclado con desgaste leve',
    replacement_components: ['ram'],
    maintenance_report: 'Se aumentó RAM de 4GB a 8GB...',
    imagenes_admin: [fotos del proceso]
  }
              │
  Al cambiar a cerrado:
  PATCH /admin/tickets/{ticket}
  body: { estado: 'cerrado', closure_observations: '...' }
              │
  Sistema automáticamente:
  ├── Registra fecha_cierre en Ticket
  ├── Crea/actualiza ComputerProfile
  │   └── last_maintenance_at = now()
  │   └── Todos los datos técnicos del ticket
  └── Notifica al empleado (user_has_updates = true)
```

---

## 4. Sistema de Calendario — Slots y Disponibilidad

### Slots horarios predefinidos

El sistema tiene 7 slots fijos de 1 hora cada uno, definidos como constante en el controlador:

```php
private const TIME_SLOTS = [
    ['start' => '09:00', 'end' => '10:00', 'label' => '09:00 AM'],
    ['start' => '10:00', 'end' => '11:00', 'label' => '10:00 AM'],
    ['start' => '11:00', 'end' => '12:00', 'label' => '11:00 AM'],
    ['start' => '12:00', 'end' => '13:00', 'label' => '12:00 PM'],
    ['start' => '13:00', 'end' => '14:00', 'label' => '01:00 PM'],
    ['start' => '14:00', 'end' => '15:00', 'label' => '02:00 PM'],
    ['start' => '15:00', 'end' => '16:00', 'label' => '03:00 PM'],
];
```

**Horario de servicio**: Lunes a viernes, 9:00 AM - 4:00 PM  
**Fines de semana**: Sin slots disponibles  
**Días anteriores a hoy**: Marcados como `past`, no reservables  
**Horarios ya pasados del día actual**: Verificados con `now('America/Mexico_City')`

### Estados de un día en el calendario mensual

| Estado | Descripción | Color sugerido |
|---|---|---|
| `available` | Todos los slots libres | Verde |
| `partial` | Algunos slots libres, otros ocupados | Amarillo |
| `full` | Todos los slots ocupados (con tickets activos) | Rojo |
| `blocked` | Día completamente bloqueado por TI | Gris oscuro |
| `past` | Fecha pasada | Gris claro |
| `unavailable` | Fin de semana | Gris |

### Estados de un slot horario

| Estado | Descripción |
|---|---|
| `available` | Libre para reservar |
| `booked` | Ya tiene un ticket de mantenimiento activo (`abierto` o `en_proceso`) |
| `blocked` | Bloqueado manualmente por TI |
| `past` | La hora ya pasó |

> **Nota sobre `booked`**: Un slot se considera ocupado si existe un ticket `tipo_problema = 'mantenimiento'` con `maintenance_scheduled_at` en esa fecha y hora, y `estado` en `['abierto', 'en_proceso']`. Tickets `cerrados` o `cancelados` liberan el slot.

---

## 5. Bloqueo de Horarios (`MaintenanceBlockedSlot`)

**Modelo**: `app/Models/Sistemas_IT/MaintenanceBlockedSlot.php`  
**Tabla**: `maintenance_blocked_slots`

Permite a TI marcar períodos en los que no habrá servicio de mantenimiento (días festivos, vacaciones, capacitaciones internas).

### Campos del modelo

| Campo | Tipo | Descripción |
|---|---|---|
| `date_start` | `date` | Inicio del bloqueo |
| `date_end` | `date` | Fin del bloqueo (null = solo un día) |
| `time_slot` | `varchar` | Hora específica bloqueada (null = día completo) |
| `reason` | `varchar` | Motivo del bloqueo visible para el admin |
| `blocked_by` | `bigint` | FK → `users.id` — quién lo bloqueó |

### Tipos de bloqueo

| Configuración | Efecto |
|---|---|
| `date_start = '2026-05-01'`, `date_end = null`, `time_slot = null` | Bloquea el día completo 1 de mayo |
| `date_start = '2026-05-01'`, `date_end = '2026-05-05'`, `time_slot = null` | Bloquea todos los días del 1 al 5 de mayo |
| `date_start = '2026-04-28'`, `date_end = null`, `time_slot = '10:00:00'` | Bloquea solo el slot de 10:00 AM del 28 de abril |

### Método `isBlocked(string $date, ?string $timeSlot): bool`

Verifica si un slot específico está bloqueado. La lógica:

```
¿Existe algún bloqueo donde:
    (date_start = $date AND date_end IS NULL)  -- día exacto
    OR (date_start <= $date AND date_end >= $date)  -- dentro de rango

    AND (time_slot IS NULL  -- bloqueo de día completo
         OR time_slot = $timeSlot)  -- o bloqueo de hora específica
?
```

### Método `getBlockedForRange(string $startDate, string $endDate): array`

Retorna todos los bloqueos que intersectan el rango dado, en formato:
```php
[
    '2026-05-01' => 'all',           // Día completo bloqueado
    '2026-04-28' => ['10:00', '11:00'],  // Solo horas específicas
]
```

---

## 6. Ficha Técnica del Equipo (`ComputerProfile`)

**Modelo**: `app/Models/Sistemas_IT/ComputerProfile.php`  
**Tabla**: `computer_profiles`

El `ComputerProfile` es el **expediente técnico permanente** de un equipo físico. Se crea automáticamente cuando TI llena datos técnicos en un ticket de mantenimiento y persiste entre diferentes tickets/visitas.

### Campos del modelo

| Campo | Tipo | Descripción | Cuándo se llena |
|---|---|---|---|
| `identifier` | `varchar` | ID único del equipo (ej: `PC-JUAN-001`) | Al registrar el equipo |
| `brand` | `varchar` | Marca del equipo | Al registrar |
| `model` | `varchar` | Modelo | Al registrar |
| `disk_type` | `varchar` | Tipo de disco (SSD/HDD + capacidad) | Al registrar |
| `ram_capacity` | `varchar` | Capacidad RAM | Al registrar |
| `battery_status` | `enum` | `functional`, `partially_functional`, `damaged` | Al revisar |
| `aesthetic_observations` | `text` | Estado físico (rayones, daños) | Al revisar |
| `replacement_components` | `json` | Array de componentes cambiados: `['ram', 'disco_duro', ...]` | Al cerrar ticket |
| `last_maintenance_at` | `datetime` | Fecha del último mantenimiento | Auto al cerrar ticket |
| `next_maintenance_at` | `datetime` | Fecha programada del próximo mantenimiento | Admin |
| `maintenance_reminder_sent_at` | `date` | Cuándo se envió el recordatorio | Sistema |
| `is_loaned` | `boolean` | Si el equipo está prestado a alguien | Al marcar préstamo |
| `loaned_to_name` | `varchar` | Nombre del usuario con el préstamo | Al marcar préstamo |
| `loaned_to_email` | `varchar` | Correo del usuario con el préstamo | Al marcar préstamo |
| `last_ticket_id` | `bigint` | FK → `tickets.id` último ticket de mantenimiento | Auto al cerrar |
| `equipo_asignado_id` | `bigint` | FK → `it_equipos_asignados.id` | Vinculación con credenciales |

### Relaciones

| Método | Tipo | Destino |
|---|---|---|
| `ticket()` | `BelongsTo` | `Ticket` — El último ticket de mantenimiento |
| `equipoAsignado()` | `BelongsTo` | `EquipoAsignado` — El expediente de credenciales |

### Ciclo de vida del ComputerProfile

```
Primera visita:
  Ticket de mantenimiento abierto
  TI llena datos técnicos al procesar el ticket
  update() → ComputerProfile::firstOrNew(['identifier' => $id])
            → Se crea el perfil con los datos técnicos
            → Se vincula: Ticket.computer_profile_id = perfil.id

Visitas posteriores:
  Nuevo ticket de mantenimiento del mismo equipo
  update() → ComputerProfile::firstOrNew(['identifier' => $id])
            → Se ACTUALIZA el perfil existente
            → Se actualiza last_maintenance_at, last_ticket_id
            → Los replacement_components se sobrescriben con los de esta visita
```

### Valores válidos de `replacement_components`

```php
// Validación en TicketController::update()
'replacement_components.*' => 'in:disco_duro,ram,bateria,pantalla,conectores,teclado,mousepad,cargador',
```

---

## 7. Reporte Técnico — Campos del Ticket de Mantenimiento

Cuando TI actualiza un ticket de tipo `mantenimiento`, puede llenar los siguientes campos específicos:

| Campo | Descripción | Quién lo llena |
|---|---|---|
| `equipment_identifier` | ID o código del equipo (ej: `PC-SALA-JUNTAS`) | Técnico TI |
| `equipment_brand` | Marca del equipo | Técnico TI |
| `equipment_model` | Modelo exacto | Técnico TI |
| `equipment_password` | Contraseña del equipo al momento del servicio | Técnico TI |
| `disk_type` | Tipo y capacidad del disco (ej: `SSD 256GB`) | Técnico TI |
| `ram_capacity` | Memoria RAM (ej: `8GB DDR4`) | Técnico TI |
| `battery_status` | Estado de la batería | Técnico TI |
| `aesthetic_observations` | Condición física del equipo | Técnico TI |
| `replacement_components` | Array de piezas reemplazadas | Técnico TI |
| `maintenance_report` | Reporte técnico narrativo del trabajo realizado | Técnico TI |
| `closure_observations` | Observaciones finales al cerrar | Técnico TI |
| `imagenes_admin` | Fotos del proceso de mantenimiento (max. 5) | Técnico TI |
| `mark_as_loaned` | Si el equipo se quedará prestado | Técnico TI |

### Sincronización automática con `ComputerProfile`

Al actualizar un ticket de mantenimiento con datos técnicos, el controlador sincroniza automáticamente el `ComputerProfile`:

```
¿Se enviaron campos técnicos? ($technicalDataProvided = true)
    → ComputerProfile::firstOrNew(['identifier' => $ticket->equipment_identifier])
    → Actualizar: identifier, brand, model, disk_type, ram_capacity,
                  battery_status, aesthetic_observations

¿Se cambió estado a 'cerrado'?
    → $profile->last_maintenance_at = now()
    → $profile->last_ticket_id = $ticket->id

¿Se enviaron replacement_components?
    → $profile->replacement_components = [...] (sobrescribe)

¿Se marcó mark_as_loaned?
    → $profile->is_loaned = true/false
    → Si true: loaned_to_name, loaned_to_email del solicitante
```

---

## 8. Referencia de Métodos — `MaintenanceController`

**Archivo**: `app/Http/Controllers/Sistemas_IT/MaintenanceController.php`

---

### `availability(Request $request): JsonResponse`

**Ruta**: `GET /maintenance/availability?month=2026-04`

Genera el mapa mensual de disponibilidad. Para cada día hábil del mes:

1. Obtiene bloqueos del mes via `MaintenanceBlockedSlot::getBlockedForRange()`
2. Obtiene tickets de mantenimiento activos agrupados por fecha y hora
3. Para cada día, recorre los 7 `TIME_SLOTS` y cuenta cuántos están disponibles
4. Asigna el estado del día: `available`, `partial`, `full`, `blocked`, `past`, `unavailable`

**Respuesta**:
```json
{
  "month": "2026-04",
  "days": [
    {
      "date": "2026-04-28",
      "total_slots": 7,
      "available": 5,
      "booked": 1,
      "blocked_slots": 1,
      "is_past": false,
      "status": "partial"
    },
    {
      "date": "2026-04-27",
      "total_slots": 7,
      "available": 0,
      "is_past": true,
      "status": "past"
    }
  ]
}
```

---

### `slots(Request $request): JsonResponse`

**Ruta**: `GET /maintenance/slots?date=2026-04-28`

Retorna los 7 slots de un día específico con su estado detallado:

**Respuesta**:
```json
{
  "date": "2026-04-28",
  "is_full_day_blocked": false,
  "slots": [
    {
      "start": "09:00",
      "end": "10:00",
      "label": "09:00 AM",
      "status": "available",
      "is_past": false,
      "is_blocked": false,
      "is_booked": false,
      "booked_by": null
    },
    {
      "start": "10:00",
      "end": "11:00",
      "label": "10:00 AM",
      "status": "booked",
      "is_past": false,
      "is_blocked": false,
      "is_booked": true,
      "booked_by": "Juan García"
    }
  ]
}
```

---

### `checkAvailability(Request $request): JsonResponse`

**Ruta**: `GET /maintenance/check-availability?date=2026-04-28&time=10:00`

Verificación en tiempo real antes de que el empleado envíe el formulario. Verifica 3 condiciones en orden:

1. `MaintenanceBlockedSlot::isBlocked($date, $time)` → `reason: 'blocked'`
2. Ticket existente en esa fecha/hora con estado activo → `reason: 'booked'`
3. La hora ya pasó → `reason: 'past'`

**Respuestas posibles**:
```json
// Disponible
{ "available": true, "message": "Horario disponible." }

// Bloqueado por TI
{ "available": false, "reason": "blocked", "message": "Este horario está bloqueado por el administrador." }

// Ya ocupado
{ "available": false, "reason": "booked", "message": "Este horario ya fue reservado por otro usuario." }

// Hora pasada
{ "available": false, "reason": "past", "message": "Este horario ya pasó." }
```

---

### `adminIndex(): View`

**Ruta**: `GET /admin/maintenance`

Panel principal del administrador. Carga:

| Variable | Descripción |
|---|---|
| `$maintenanceTickets` | Últimos 15 tickets de mantenimiento con su `ComputerProfile` |
| `$ticketsWithoutProfile` | Tickets de mantenimiento sin ficha técnica asignada aún |
| `$profiles` | Todos los `ComputerProfile` ordenados por última actualización |
| `$blockedSlots` | Bloqueos activos (desde hoy en adelante) |
| `$timeSlots` | Constante `TIME_SLOTS` para el UI |
| `$componentOptions` | Opciones para el selector de componentes reemplazados |
| `$users` | Lista de usuarios para filtros |

---

### `getWeekMaintenances(Request $request): JsonResponse`

**Ruta**: `GET /admin/maintenance/week?week_start=2026-04-27`

Retorna todos los tickets de mantenimiento de la semana en formato JSON para el calendario semanal del panel admin. Los tickets se agrupan por día (`Y-m-d`).

---

### `blockSlot(Request $request): JsonResponse`

**Ruta**: `POST /admin/maintenance/block-slot`

Crea un nuevo bloqueo de horario. Validaciones:

```php
$request->validate([
    'date_start'  => 'required|date',
    'date_end'    => 'nullable|date|after_or_equal:date_start',
    'time_slot'   => 'nullable|in:09:00,10:00,11:00,12:00,13:00,14:00,15:00',
    'reason'      => 'nullable|string|max:500',
]);
```

---

### `unblockSlot(int $id): JsonResponse`

**Ruta**: `DELETE /admin/maintenance/block-slot/{id}`

Elimina un bloqueo por su ID. Solo el autor del bloqueo o un admin puede eliminarlo.

---

## 9. Modelos Eloquent — Mantenimiento

### `MaintenanceBlockedSlot`

**Tabla**: `maintenance_blocked_slots`  
Ver campos en [Sección 5](#5-bloqueo-de-horarios-maintenanceblockedslot).

**Métodos estáticos clave**:
- `isBlocked(string $date, ?string $timeSlot): bool`
- `getBlockedForRange(string $startDate, string $endDate): array`

---

### `ComputerProfile`

**Tabla**: `computer_profiles`  
Ver campos y ciclo de vida en [Sección 6](#6-ficha-técnica-del-equipo-computerprofile).

---

### `MaintenanceBooking` (Legado)

**Tabla**: `maintenance_bookings`  
**Nota**: Este modelo es **legado**. La arquitectura actual usa `maintenance_scheduled_at` directamente en el ticket. `MaintenanceBooking` puede estar presente en tickets más antiguos pero no se crea en nuevas reservas.

---

### `MaintenanceSlot` (Legado)

**Tabla**: `maintenance_slots`  
**Nota**: Modelo **legado**. Los slots actuales son la constante `TIME_SLOTS` del controlador. `MaintenanceSlot` puede tener datos históricos pero no se usa para nuevas operaciones.

---

## 10. Panel Administrativo

### Vista principal (`/admin/maintenance`)

El panel tiene tres secciones principales:

**1. Tickets pendientes de revisión**
Lista de tickets de mantenimiento activos ordenados por fecha de cita. El técnico puede hacer clic para ver el detalle y llenar el reporte.

**2. Equipos sin ficha técnica (`$ticketsWithoutProfile`)**
Tickets de mantenimiento que no tienen un `ComputerProfile` vinculado. Esto ocurre cuando:
- El técnico no llenó datos del equipo en el ticket
- El ticket fue creado antes de que existiera la funcionalidad de perfiles

**3. Calendario de bloqueos**
Listado de `MaintenanceBlockedSlot` activos o futuros, con controles para agregar nuevos bloqueos.

### Vista de agenda semanal

La vista de semana carga dinámicamente los mantenimientos via `getWeekMaintenances()` y los renderiza en un layout de 5 columnas (lunes a viernes) con los slots horarios en filas.

---

## 11. Referencia de Rutas

**Middleware empleado**: `auth`, `verified`  
**Middleware admin**: `sistemas_admin`

### Rutas del Empleado

| Método | URI | Descripción |
|---|---|---|
| `GET` | `/maintenance/availability` | Mapa mensual de disponibilidad (`?month=YYYY-MM`) |
| `GET` | `/maintenance/slots` | Slots horarios de un día (`?date=YYYY-MM-DD`) |
| `GET` | `/maintenance/check-availability` | Verificación spot (`?date=...&time=HH:MM`) |
| `GET` | `/ticket/create/mantenimiento` | Formulario de nueva solicitud |
| `POST` | `/ticket` | Enviar solicitud (mismo endpoint que otros tipos) |

### Rutas del Administrador TI

| Método | URI | Descripción |
|---|---|---|
| `GET` | `/admin/maintenance` | Panel principal de mantenimientos |
| `GET` | `/admin/maintenance/week` | Vista semanal JSON (`?week_start=YYYY-MM-DD`) |
| `GET` | `/admin/maintenance/calendar` | Datos para FullCalendar |
| `GET` | `/admin/maintenance/computers/{computerProfile}` | Panel de un equipo específico |
| `POST` | `/admin/maintenance/block-slot` | Crear bloqueo de horario |
| `DELETE` | `/admin/maintenance/block-slot/{id}` | Eliminar bloqueo |
| `GET` | `/admin/maintenance-slots/available` | Lista de slots admin disponibles |

---

## 12. Historial de Migraciones

| Fecha | Archivo | Cambio |
|---|---|---|
| 2025-12-09 | `create_sistemas_it_tables.php` | Tablas base: `tickets`, `maintenance_slots`, `maintenance_bookings`, `computer_profiles` |
| 2026-02-12 | `create_maintenance_blocked_slots_table.php` | ⭐ Añade `maintenance_blocked_slots` para bloqueos flexibles de rango y hora |
| 2026-04-17 | `add_equipo_asignado_id_to_computer_profiles.php` | Vincula `computer_profiles` con `it_equipos_asignados` (FK nueva) |
| 2026-04-17 | `add_maintenance_schedule_to_computer_profiles.php` | Añade `next_maintenance_at` y `maintenance_reminder_sent_at` para recordatorios preventivos |

---

## 13. Guía de Mantenimiento del Módulo

---

### 🔴 CRÍTICO: Zona horaria en el calendario

Todo el cálculo de disponibilidad usa `America/Mexico_City` explícitamente. Nunca usar `now()` sin zona horaria en este módulo. Si el servidor está en UTC, `now()` sin TZ devolvería las 6 horas previas a lo que es en México.

```php
// ✅ Correcto
$now = Carbon::now('America/Mexico_City');
$scheduledAt = Carbon::parse($fecha . ' ' . $hora, 'America/Mexico_City');

// ❌ Incorrecto (puede provocar slots disponibles que ya pasaron)
$now = now();
```

---

### 🔴 CRÍTICO: Condición de carrera en reservas simultáneas

Si dos empleados abren el formulario al mismo tiempo y seleccionan el mismo slot, ambos verán `checkAvailability → available`. La reserva se resuelve al hacer `POST /ticket`: el segundo ticket que llega a `store()` no falla, porque no hay un constraint único en BD.

**Solución actual**: El `checkAvailability` es best-effort. El admin puede detectar la doble reserva en su panel y reagendar manualmente via `POST /admin/tickets/{ticket}/change-maintenance-date`.

**Solución a largo plazo recomendada**: Agregar un lock pesimista o un constraint único `(maintenance_scheduled_at)` en la tabla `tickets` con filtro por `tipo_problema = 'mantenimiento'` y `estado IN ('abierto', 'en_proceso')`.

---

### 🔴 CRÍTICO: `replacement_components` se sobrescribe en cada update

Al cerrar un ticket de mantenimiento, el array de `replacement_components` del `ComputerProfile` se **reemplaza completamente** con lo que viene en el ticket actual. No hay historial acumulado.

Si se necesita historial de todas las piezas reemplazadas en todos los mantenimientos, debe guardarse en una tabla separada de historial o acumular en el array en lugar de sobrescribir.

---

### 🟡 IMPORTANTE: Cambiar los horarios de disponibilidad

Los slots están hardcodeados en `TIME_SLOTS`. Para cambiar el horario de servicio:

```php
// MaintenanceController.php — constante TIME_SLOTS
private const TIME_SLOTS = [
    ['start' => '08:00', 'end' => '09:00', 'label' => '08:00 AM'],  // ← agregar
    // ... slots existentes ...
    ['start' => '16:00', 'end' => '17:00', 'label' => '04:00 PM'],  // ← agregar
];
```

**Efecto**: Los nuevos slots aparecerán automáticamente en `availability()`, `slots()` y el panel admin. No se requieren cambios en BD.

**Precaución**: Al agregar slots antes de las 9:00 o después de las 16:00, verificar que el formulario del empleado los muestre correctamente y que la validación en `blockSlot()` incluya las nuevas horas en la lista `in:09:00,...`.

---

### 🟡 IMPORTANTE: Agregar un nuevo componente reemplazable

```php
// En TicketController::update(), cambiar:
'replacement_components.*' => 'in:disco_duro,ram,bateria,pantalla,conectores,teclado,mousepad,cargador',

// Por (ejemplo: agregar 'fuente_poder'):
'replacement_components.*' => 'in:disco_duro,ram,bateria,pantalla,conectores,teclado,mousepad,cargador,fuente_poder',
```

Actualizar también el array `getReplacementComponentOptions()` en el controlador para que aparezca en el selector del admin.

---

### 🟢 SEGURO: Agregar el recordatorio automático de mantenimiento preventivo

Los campos `next_maintenance_at` y `maintenance_reminder_sent_at` ya existen en `ComputerProfile`. Para implementar el recordatorio:

1. Crear un job o comando artisan que consulte perfiles con `next_maintenance_at` próxima.
2. Enviar correo al empleado y al admin.
3. Registrar `maintenance_reminder_sent_at = today()` para no re-enviar.

```bash
php artisan make:command NotificarMantenimientoPreventivoCommand
```

No se requieren migraciones adicionales.

---

### Checklist antes de un deploy con cambios al módulo de Mantenimiento

- [ ] ¿Cambiaron los `TIME_SLOTS`? Verificar que `blockSlot()` valida las nuevas horas en su regla `in:`.
- [ ] ¿Cambió la lógica de `isBlocked()`? Probar los 3 escenarios: día exacto, rango de fechas, hora específica.
- [ ] ¿Cambió `availability()`? Verificar que los estados `past`, `blocked`, `full`, `partial` se calculan correctamente con la TZ de México.
- [ ] ¿Se modificó la sincronización de `ComputerProfile` en `update()`? Probar el flujo completo: ticket abierto → datos técnicos → cierre → perfil creado/actualizado.
- [ ] ¿Cambió el formato de `maintenance_scheduled_at`? Verificar que `slots()` y `checkAvailability()` siguen detectando correctamente la doble reserva.

---

*Documentación generada el 27 de Abril de 2026 — ERP Estrategia e Innovación*
