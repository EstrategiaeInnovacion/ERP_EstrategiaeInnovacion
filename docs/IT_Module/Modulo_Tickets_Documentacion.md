# Módulo IT — Sistema de Tickets — Documentación Maestra
> **ERP Estrategia e Innovación** · Versión documental: Abril 2026  
> Audiencia: Desarrolladores de mantenimiento, administradores de TI

---

## Tabla de Contenido

1. [Visión General](#1-visión-general)
2. [Arquitectura del Sistema de Tickets](#2-arquitectura-del-sistema-de-tickets)
3. [Ciclo de Vida de un Ticket](#3-ciclo-de-vida-de-un-ticket)
4. [Tipos de Ticket](#4-tipos-de-ticket)
5. [Modelo Eloquent — `Ticket`](#5-modelo-eloquent--ticket)
6. [Generación de Folio](#6-generación-de-folio)
7. [Sistema de Notificaciones](#7-sistema-de-notificaciones)
8. [Referencia de Métodos — `TicketController`](#8-referencia-de-métodos--ticketcontroller)
9. [Campos del Modelo por Tipo de Ticket](#9-campos-del-modelo-por-tipo-de-ticket)
10. [Sistema de Notificaciones al Usuario](#10-sistema-de-notificaciones-al-usuario)
11. [Referencia de Rutas](#11-referencia-de-rutas)
12. [Guía de Mantenimiento](#12-guía-de-mantenimiento)

---

## 1. Visión General

El **Sistema de Tickets** es el núcleo operativo del módulo IT. Permite a cualquier empleado de la empresa reportar una incidencia tecnológica (falla de software, problema de hardware, o solicitar mantenimiento de equipo) y da seguimiento desde la apertura hasta el cierre con reporte técnico.

### Propósito de Negocio

| Necesidad | Solución |
|---|---|
| Centralizar solicitudes de soporte técnico | Un único formulario con 3 tipos de ticket |
| Notificar a TI inmediatamente al abrir un ticket | Webhook n8n + correo Microsoft Graph API |
| Documentar el trabajo técnico realizado | Campos de reporte técnico en tickets de mantenimiento |
| Mantener historial por equipo | Vinculación ticket → `ComputerProfile` al cierre |
| Notificar al empleado sobre cambios en su ticket | Sistema de `user_has_updates` + `user_notification_summary` |
| Controlar prioridades | Campo `prioridad` con 4 niveles (baja/media/alta/critica) |

---

## 2. Arquitectura del Sistema de Tickets

```
┌────────────────────────────────────────────────────────────────┐
│                   SISTEMA DE TICKETS IT                        │
│                                                                │
│  routes/web.php                                                │
│    ├── /ticket/*           → Rutas del empleado               │
│    └── /admin/tickets/*    → Rutas del administrador TI        │
│                     │                                          │
│                     ▼                                          │
│  TicketController (app/Http/Controllers/Sistemas_IT/)          │
│    ├── create($tipo)      → Formulario con tipo seleccionado   │
│    ├── store()            → Guarda + notifica (n8n + email)    │
│    ├── index()            → Panel admin con lista paginada     │
│    ├── show()             → Vista detalle del ticket           │
│    ├── update()           → Actualiza estado y reporte técnico │
│    ├── misTickets()       → Vista del empleado (sus tickets)   │
│    └── destroy()          → Cancelar / eliminar               │
│                                                                │
│  Ticket Model (app/Models/Sistemas_IT/Ticket.php)              │
│    ├── Scopes: byEstado(), byTipo()                            │
│    ├── Accessors: estadoBadge, prioridadBadge                  │
│    ├── Relations: user(), computerProfile(), maintenanceSlot() │
│    └── Boot: generateFolio() automático al crear              │
│                                                                │
│  Integraciones externas                                        │
│    ├── n8n webhook (HTTP POST con payload completo del ticket)  │
│    └── MicrosoftGraphMailService → correo al admin TI         │
│                                                                │
│  Vistas Blade                                                  │
│    ├── tickets/create.blade.php   → Formulario unificado       │
│    ├── tickets/mis-tickets.blade.php → Historial del empleado  │
│    ├── admin/tickets/index.blade.php → Lista admin             │
│    └── admin/tickets/show.blade.php → Detalle admin           │
└────────────────────────────────────────────────────────────────┘
```

---

## 3. Ciclo de Vida de un Ticket

```
    Empleado abre ticket
           │
           ▼
     ┌─────────┐         Webhook n8n
     │ ABIERTO │ ──────► + Correo → Admin TI
     └────┬────┘
          │ Admin cambia estado
          ▼
    ┌────────────┐
    │ EN_PROCESO │  ← TI está trabajando en el ticket
    └─────┬──────┘
          │ Admin cierra
          ▼
     ┌────────┐        Se registra fecha_cierre
     │ CERRADO │ ──►  Se actualiza ComputerProfile (si mantenimiento)
     └────────┘        Se notifica al empleado

    (Alternativa) Empleado puede cancelar si el ticket está abierto
           ▼
     ┌───────────┐
     │ CANCELADO │  ← Libera el slot de mantenimiento si aplica
     └───────────┘
```

### Estados válidos

| Estado | Descripción | Quién puede aplicarlo |
|---|---|---|
| `abierto` | Ticket recién creado, sin atender | Sistema (al crear) |
| `en_proceso` | TI está trabajando en ello | Administrador TI |
| `cerrado` | Resuelto, con o sin reporte técnico | Administrador TI |
| `cancelado` | Cancelado antes de ser atendido | Empleado (si está abierto) / Admin |

---

## 4. Tipos de Ticket

El campo `tipo_problema` determina el flujo, los campos disponibles y la validación:

| Tipo | Descripción | Campos exclusivos | Reporte técnico |
|---|---|---|---|
| `software` | Fallas en aplicaciones, instalaciones, errores de sistema | `nombre_programa`, `descripcion_problema`, `imagenes` | No |
| `hardware` | Problemas físicos con el equipo de cómputo | `nombre_programa` (nombre del equipo), `descripcion_problema`, `imagenes` | No |
| `mantenimiento` | Solicitud de mantenimiento preventivo/correctivo con cita | `maintenance_slot_id`, `maintenance_scheduled_at` | Sí — campos técnicos completos |

> Para la documentación específica de cada tipo ver:
> - [Modulo_Software_Documentacion.md](Modulo_Software_Documentacion.md)
> - [Modulo_Hardware_Documentacion.md](Modulo_Hardware_Documentacion.md)
> - [Modulo_Mantenimiento_Documentacion.md](Modulo_Mantenimiento_Documentacion.md)

---

## 5. Modelo Eloquent — `Ticket`

**Archivo**: `app/Models/Sistemas_IT/Ticket.php`  
**Tabla**: `tickets`

### Campos `fillable` completos

| Campo | Tipo | Descripción |
|---|---|---|
| `folio` | `varchar` | Generado automáticamente (ver Sección 6) |
| `user_id` | `bigint` | FK → `users.id`. Empleado solicitante |
| `nombre_solicitante` | `varchar` | Nombre capturado al momento de crear el ticket |
| `correo_solicitante` | `varchar` | Correo del empleado al momento de crear |
| `nombre_programa` | `varchar` | Software afectado (software) o nombre del equipo (hardware/mant.) |
| `descripcion_problema` | `text` | Descripción libre del problema |
| `imagenes` | `json` | Array de `{data: base64, mime: string, name: string}` |
| `estado` | `enum` | `abierto`, `en_proceso`, `cerrado`, `cancelado` |
| `fecha_apertura` | `datetime` | Cuándo se abrió (igual a `created_at`) |
| `fecha_cierre` | `datetime` | Cuándo TI marcó como cerrado |
| `observaciones` | `text` | Observaciones generales del administrador |
| `tipo_problema` | `enum` | `software`, `hardware`, `mantenimiento` |
| `prioridad` | `enum` | `baja`, `media`, `alta`, `critica` (solo en sw/hw) |
| `is_read` | `boolean` | Si el admin ya leyó el ticket |
| `notified_at` | `datetime` | Cuándo se envió la notificación al admin |
| `closed_by_user` | `boolean` | Si el empleado lo cerró (cancelación) |
| `closed_by_user_at` | `datetime` | Timestamp de cancelación por empleado |
| `maintenance_slot_id` | `bigint` | FK → slot de mantenimiento reservado (legado) |
| `maintenance_scheduled_at` | `datetime` | Fecha y hora exacta de la cita de mantenimiento |
| `equipment_identifier` | `varchar` | ID del equipo (solo mantenimiento) |
| `equipment_brand` | `varchar` | Marca del equipo (solo mantenimiento) |
| `equipment_model` | `varchar` | Modelo del equipo (solo mantenimiento) |
| `equipment_password` | `varchar` | Contraseña del equipo (solo mantenimiento) |
| `disk_type` | `varchar` | Tipo de disco (SSD/HDD) |
| `ram_capacity` | `varchar` | Capacidad RAM |
| `battery_status` | `enum` | `functional`, `partially_functional`, `damaged` |
| `aesthetic_observations` | `text` | Condición física del equipo |
| `replacement_components` | `json` | Array de componentes reemplazados |
| `computer_profile_id` | `bigint` | FK → `computer_profiles.id` |
| `imagenes_admin` | `json` | Imágenes subidas por el admin durante el mantenimiento |
| `user_has_updates` | `boolean` | Si hay actualizaciones que el empleado no ha visto |
| `user_notified_at` | `datetime` | Última notificación al empleado |
| `user_last_read_at` | `datetime` | Última vez que el empleado vio su ticket |
| `user_notification_summary` | `text` | Resumen de los cambios para el empleado |

### Relaciones

| Método | Tipo | Destino |
|---|---|---|
| `user()` | `BelongsTo` | `User` — El empleado solicitante |
| `computerProfile()` | `BelongsTo` | `ComputerProfile` — Ficha técnica del equipo |
| `maintenanceSlot()` | `BelongsTo` | `MaintenanceSlot` — Slot reservado (legado) |

### Scopes y Accessors

```php
// Scopes
Ticket::byEstado('abierto')->get();
Ticket::byTipo('mantenimiento')->get();

// Accessors (retornan clases CSS Tailwind)
$ticket->estado_badge;     // → 'bg-green-100 text-green-800'
$ticket->prioridad_badge;  // → 'bg-red-100 text-red-800'
```

---

## 6. Generación de Folio

El folio se genera automáticamente en el evento `creating` del modelo. Formato:

```
TK{AÑO}{MES}{CORRELATIVO:4}
```

**Ejemplos**:
- Primer ticket de abril 2026: `TK2026040001`
- Ticket #15 de diciembre 2025: `TK2025120015`

**Lógica**:
```php
$year  = date('Y');
$month = date('m');
// Busca el último ticket del mismo mes/año
$lastTicket = Ticket::whereYear('created_at', $year)
    ->whereMonth('created_at', $month)
    ->orderBy('id', 'desc')
    ->first();

$number = $lastTicket ? (intval(substr($lastTicket->folio, -4)) + 1) : 1;
$folio  = sprintf('TK%s%s%04d', $year, $month, $number);
```

> **⚠️ Riesgo de concurrencia**: En alta carga simultánea, dos tickets creados en el mismo instante podrían obtener el mismo correlativo. Si se necesita unicidad garantizada, convertir a una secuencia de BD o aplicar un lock pesimista.

---

## 7. Sistema de Notificaciones

Cuando se crea un ticket, el sistema dispara **dos canales de notificación en paralelo** para el administrador de TI.

### Canal 1: Webhook n8n

**Método**: `notifyN8nTicketCreated(Ticket $ticket)`

- Se ejecuta con `DB::afterCommit()` para garantizar que el ticket ya existe en BD antes de llamar al webhook.
- El payload enviado es un JSON completo:

```json
{
  "ticket": {
    "id": 42,
    "folio": "TK2026040042",
    "tipo_problema": "software",
    "estado": "abierto",
    "prioridad": null,
    "descripcion_problema": "No abre Word",
    "nombre_programa": "Microsoft Word",
    "fecha_creacion": "2026-04-27T10:30:00+00:00",
    "imagenes": [...],
    "maintenance_slot": null,
    "maintenance_booking": null
  },
  "user": {
    "id": 15,
    "nombre": "Juan García",
    "correo": "juan.garcia@empresa.com"
  }
}
```

- Configurado via `config('services.n8n.webhook_url')` → variable de entorno `N8N_WEBHOOK_URL`.
- Timeout: **10 segundos**. Si el webhook falla, solo se registra en `Log::error()`. El ticket sigue guardándose.

### Canal 2: Correo Microsoft Graph API

**Método**: `enviarCorreoNotificacion(Ticket $ticket)`

- Destinatario: `config('app.admin_sistemas_email')` → variable de entorno `ADMIN_SISTEMAS_EMAIL`.
- Renderiza la vista `emails.nuevo_ticket` a HTML y la envía via `MicrosoftGraphMailService`.
- Asunto: `[TK2026040042] Nuevo Ticket de Software`
- Si falla, solo se loguea el error — **no interrumpe el flujo del usuario**.

### Configuración requerida en `.env`

```env
N8N_WEBHOOK_URL=https://n8n.empresa.com/webhook/tickets
ADMIN_SISTEMAS_EMAIL=sistemas@estrategiainnovacion.com.mx
# Credenciales de Microsoft Graph (en config/services.php)
GRAPH_TENANT_ID=...
GRAPH_CLIENT_ID=...
GRAPH_CLIENT_SECRET=...
GRAPH_FROM_EMAIL=notificaciones@empresa.com
```

---

## 8. Referencia de Métodos — `TicketController`

**Archivo**: `app/Http/Controllers/Sistemas_IT/TicketController.php`

---

### `create(string $tipo): View`

Muestra el formulario de nuevo ticket. Valida que `$tipo` sea uno de: `software`, `hardware`, `mantenimiento`.

**Lógica adicional para tipo `hardware`**:
```php
// Verifica si el empleado tiene un equipo prestado registrado en ComputerProfile
$assignedComputerProfile = ComputerProfile::where('is_loaned', true)
    ->where('loaned_to_email', $user->email)
    ->first();
```
Si existe un equipo en préstamo, pre-llena el formulario con sus datos.

**Variable `serverTime`**: Se pasa a la vista para forzar el calendario de mantenimiento a la zona horaria correcta de México (`America/Mexico_City`), evitando desfases por diferencia de TZ entre el servidor y el navegador.

---

### `store(Request $request): RedirectResponse`

Guarda el ticket dentro de una `DB::transaction()`.

**Validaciones por tipo**:

| Campo | Regla |
|---|---|
| `tipo_problema` | `required\|in:software,hardware,mantenimiento` |
| `descripcion_problema` | `required` solo si tipo ≠ mantenimiento |
| `nombre_programa` | `required` solo si tipo = hardware |
| `maintenance_slot_id` | `required_if:tipo_problema,mantenimiento` |
| `imagenes.*` | `image\|max:2048 kb` (máx. 5 archivos) |

**Procesamiento de imágenes**:
```php
// Las imágenes se convierten a base64 y se guardan en el campo JSON `imagenes`
$imageData = base64_encode(file_get_contents($imagen->getRealPath()));
$imagenes[] = ['data' => $imageData, 'mime' => $mimeType, 'name' => $filename];
```

**Para tickets de mantenimiento**: Se construye el `maintenance_scheduled_at` combinando `fecha_requerida` + `hora_requerida` del formulario y se parsea en `America/Mexico_City`.

---

### `update(Request $request, Ticket $ticket): RedirectResponse`

El método más complejo del controlador. Maneja la actualización de estado y, para tickets de mantenimiento, la actualización simultánea del `ComputerProfile`.

**Lógica de `ComputerProfile` en tickets de mantenimiento**:

```
¿Se enviaron datos técnicos (marca, modelo, RAM, etc.)?
    → Busca o crea un ComputerProfile por `identifier`
    → Actualiza los datos del perfil

¿Se marcó `mark_as_loaned = true`?
    → Registra el equipo como prestado con nombre y correo del solicitante

¿El estado cambió a `cerrado`?
    → Registra `last_maintenance_at = now()`
    → Vincula el ticket al perfil con `last_ticket_id`
```

**Generación de notificaciones para el empleado**:
El método detecta qué cambió y construye un `user_notification_summary` descriptivo:
- Cambio de estado → `"El estado del ticket cambió a "En proceso"."`
- Nuevo comentario → `"El administrador dejó un nuevo comentario en tu ticket."`
- Reporte de mantenimiento → `"Se actualizó el reporte de mantenimiento del equipo."`

---

### `destroy(string $ticketId): RedirectResponse`

**Dos modos**:
1. **Admin**: Puede eliminar cualquier ticket permanentemente.
2. **Usuario**: Solo puede cancelar si el ticket está en estado `abierto` o `en_proceso` reciente. Si libera un slot de mantenimiento reservado, actualiza el estado del `MaintenanceBooking`.

---

### `misTickets(Request $request): View`

Vista del empleado para ver sus propios tickets. Filtra por `user_id = auth()->id()` ordenados descendentemente.

---

## 9. Campos del Modelo por Tipo de Ticket

### Campos activos según tipo

| Campo | software | hardware | mantenimiento |
|---|---|---|---|
| `nombre_programa` | ✅ Nombre de la app | ✅ Nombre del equipo | — |
| `descripcion_problema` | ✅ | ✅ | — (opcional en form) |
| `imagenes` | ✅ | ✅ | ✅ |
| `prioridad` | ✅ | ✅ | ❌ no aplica |
| `maintenance_scheduled_at` | ❌ | ❌ | ✅ |
| `equipment_*` (brand/model/etc.) | ❌ | ❌ | ✅ llenado por TI |
| `replacement_components` | ❌ | ❌ | ✅ |
| `maintenance_report` | ❌ | ❌ | ✅ llenado por TI |
| `closure_observations` | ❌ | ❌ | ✅ llenado por TI |
| `computer_profile_id` | ❌ | ❌ | ✅ se vincula al cerrar |
| `imagenes_admin` | ❌ | ❌ | ✅ fotos del técnico |

---

## 10. Sistema de Notificaciones al Usuario

El empleado puede ver el estado de su ticket en `/ticket/mis-tickets`. El sistema detecta cambios usando estos campos:

| Campo | Función |
|---|---|
| `user_has_updates` | `true` si hay cambios que el empleado no vio |
| `user_notification_summary` | Texto descripción de los cambios |
| `user_notified_at` | Timestamp de la última notificación |
| `user_last_read_at` | Última vez que el empleado visitó su ticket |

**Limpieza de notificaciones**: Al hacer `POST /ticket/{id}/acknowledge-update`, se pone `user_has_updates = false` y se registra `user_last_read_at = now()`.

---

## 11. Referencia de Rutas

**Middleware de usuario**: `auth`, `verified`  
**Middleware de admin**: `sistemas_admin` (middleware personalizado)

### Rutas del Empleado

| Método | URI | Descripción |
|---|---|---|
| `GET` | `/ticket/create/{tipo}` | Formulario de nuevo ticket (`software`, `hardware`, `mantenimiento`) |
| `POST` | `/ticket` | Guardar ticket + notificar |
| `GET` | `/ticket/mis-tickets` | Historial de tickets del empleado |
| `DELETE` | `/ticket/{id}` | Cancelar ticket propio |
| `GET` | `/ticket/{id}/can-cancel` | JSON: ¿Puede cancelar este ticket? |
| `POST` | `/ticket/{id}/acknowledge-update` | Marcar actualizaciones como leídas |
| `POST` | `/ticket/acknowledge-all` | Marcar todas las actualizaciones como leídas |

### Rutas del Administrador TI

| Método | URI | Descripción |
|---|---|---|
| `GET` | `/admin/tickets` | Lista paginada de todos los tickets |
| `GET` | `/admin/tickets/{ticket}` | Vista de detalle técnico |
| `PATCH` | `/admin/tickets/{ticket}` | Actualizar estado y campos técnicos |
| `POST` | `/admin/tickets/{ticket}/change-maintenance-date` | Reagendar cita de mantenimiento |

---

## 12. Guía de Mantenimiento

---

### 🔴 CRÍTICO: Transacción + Notificación

El método `store()` guarda el ticket en `DB::transaction()`. Las notificaciones (n8n y Graph) se llaman **fuera** de la transacción con `DB::afterCommit()`. **Nunca mover las notificaciones dentro de la transacción**: si el webhook tarda mucho, el lock de transacción se extendería, bloqueando la tabla.

---

### 🔴 CRÍTICO: `update()` y la sincronización de `ComputerProfile`

Al cerrar un ticket de mantenimiento, el controlador actualiza o crea automáticamente el `ComputerProfile`. Si se modifica la lógica de actualización de perfiles, verificar estos escenarios:

- [ ] ¿Qué pasa si el ticket no tiene `equipment_identifier`? → No se crea perfil (lógica correcta)
- [ ] ¿Qué pasa si ya existe un perfil con ese identifier? → `firstOrNew()` lo reutiliza (evita duplicados)
- [ ] ¿El `last_maintenance_at` se actualiza? → Solo si `estado = 'cerrado'`

---

### 🟡 IMPORTANTE: Base64 en campo `imagenes`

Las imágenes se almacenan como base64 en un campo JSON del ticket. Para tickets con muchas imágenes esto puede inflar significativamente el tamaño de la fila. Si el rendimiento es un problema:

1. Migrar a almacenamiento en disco (`Storage::put()`) y guardar la ruta.
2. Actualizar el campo `imagenes` para almacenar paths en lugar de base64.
3. Agregar un proxy de fotos similar al de activos (`ActivosApiController@photo`).

---

### 🟡 IMPORTANTE: Folio no es atómicamente único

La generación de folio con `MAX() + 1` puede colisionar en alta concurrencia. Si hay riesgo de múltiples tickets simultáneos:

```php
// Solución: usar un campo AUTO_INCREMENT separado o una secuencia DB
// O agregar unique constraint + retry en el modelo
```

---

### 🟢 SEGURO: Agregar un nuevo tipo de ticket

1. Agregar el valor al `in:` del validate de `tipo_problema` en `store()`.
2. Agregar la condición en `create()` para mostrar el formulario correcto.
3. Agregar la lógica de campos en `update()` si el nuevo tipo tiene campos técnicos propios.
4. No se requieren migraciones si se reutilizan los campos existentes.

---

### Checklist antes de un deploy con cambios al módulo de Tickets

- [ ] ¿Cambió la validación de `store()`? Verificar que los 3 tipos siguen funcionando.
- [ ] ¿Se modificó `update()` para mantenimiento? Probar el flujo completo: cerrar ticket → verificar que `ComputerProfile` se actualiza.
- [ ] ¿Cambió el payload del webhook? Avisar al equipo que mantiene el workflow en n8n.
- [ ] ¿Cambió la vista del correo (`emails.nuevo_ticket`)? Probar el correo con datos reales.
- [ ] ¿Se agregó un nuevo estado? Actualizar los scopes, los badges y la lógica de cancelación.

---

*Documentación generada el 27 de Abril de 2026 — ERP Estrategia e Innovación*
