# Módulo IT — Tickets de Software — Documentación Maestra
> **ERP Estrategia e Innovación** · Versión documental: Abril 2026  
> Audiencia: Desarrolladores de mantenimiento, administradores de TI

---

## Tabla de Contenido

1. [Visión General](#1-visión-general)
2. [¿Qué es un Ticket de Software?](#2-qué-es-un-ticket-de-software)
3. [Flujo Completo del Ticket de Software](#3-flujo-completo-del-ticket-de-software)
4. [Formulario de Creación](#4-formulario-de-creación)
5. [Campos del Modelo para Tipo Software](#5-campos-del-modelo-para-tipo-software)
6. [Validación del Formulario](#6-validación-del-formulario)
7. [Catálogo de Programas Soportados](#7-catálogo-de-programas-soportados)
8. [Sistema de Prioridad](#8-sistema-de-prioridad)
9. [Panel Administrativo — Gestión de Tickets Software](#9-panel-administrativo--gestión-de-tickets-software)
10. [Estados y Transiciones](#10-estados-y-transiciones)
11. [Referencia de Rutas](#11-referencia-de-rutas)
12. [Guía de Mantenimiento](#12-guía-de-mantenimiento)

---

## 1. Visión General

El ticket de **software** es el tipo de incidencia más frecuente del módulo IT. Permite a los empleados reportar cualquier problema con aplicaciones, programas o servicios digitales que impidan su trabajo.

A diferencia del ticket de hardware o mantenimiento, el ticket de software:
- No requiere cita ni calendario
- No genera una ficha técnica (`ComputerProfile`)
- Tiene soporte de prioridad configurable por el administrador
- Permite adjuntar imágenes (capturas de pantalla del error)

---

## 2. ¿Qué es un Ticket de Software?

Un ticket de software cubre cualquier incidencia relacionada con:

| Categoría | Ejemplos |
|---|---|
| Aplicaciones de oficina | Word no abre, Excel muestra error de licencia |
| Correo electrónico | Outlook no sincroniza, no puede enviar adjuntos |
| Accesos y permisos | No puede entrar al ERP, contraseña de sistema expirada |
| Aplicaciones empresariales | El módulo de logística arroja error 500 |
| Conectividad de sistema | VPN no conecta, impresora no aparece en red |
| Instalaciones | Necesita que TI instale un programa específico |

El campo `nombre_programa` identifica el software afectado. El empleado puede seleccionar de una lista predefinida o escribir un nombre libre ("Otro").

---

## 3. Flujo Completo del Ticket de Software

```
Empleado accede a /ticket/create/software
              │
              ▼
    Selecciona el programa afectado
    + Describe el problema
    + Adjunta capturas (opcional, máx. 5)
              │
              ▼
         POST /ticket
              │
         Validación ──► ERROR: Redirige con mensajes
              │
              ▼ (éxito)
    DB::transaction
    └── Ticket::create({tipo_problema: 'software', estado: 'abierto'})
              │
              ▼ (fuera de transacción — DB::afterCommit)
    ┌─────────────────────────────┐
    │ Webhook n8n (async, 10s)    │
    │ + Correo Microsoft Graph    │
    └─────────────────────────────┘
              │
              ▼
    Redirect → /ticket/mis-tickets
    Flash: "¡Ticket creado! Folio: TK2026040042"

───── Lado TI (admin) ─────────────────────────────

    GET /admin/tickets
    └── Ve el ticket nuevo en la lista

    GET /admin/tickets/{ticket}
    └── Ve el detalle: descripción, imágenes, programa

    PATCH /admin/tickets/{ticket}
    └── Cambia estado: abierto → en_proceso → cerrado
    └── Agrega observaciones
    └── Asigna prioridad si aplica

    Sistema detecta cambios → user_has_updates = true
    └── Empleado ve indicador en mis-tickets
```

---

## 4. Formulario de Creación

**Ruta**: `GET /ticket/create/software`  
**Vista**: `resources/views/tickets/create.blade.php` (compartida con los 3 tipos)

### Campos visibles para tipo `software`

| Campo HTML | Nombre en DB | Tipo | Requerido |
|---|---|---|---|
| Selector de programa | `nombre_programa` | `<select>` + campo libre | ✅ |
| Nombre libre (si "Otro") | `otro_programa_nombre` | `<input text>` | Solo si elige "Otro" |
| Descripción del problema | `descripcion_problema` | `<textarea>` | ✅ |
| Imágenes | `imagenes[]` | `<input file multiple>` | No (máx. 5, 2MB c/u) |

### Campos NO visibles para tipo `software`

- Calendario de mantenimiento (`maintenance_slot_id`)
- Datos del equipo (`equipment_brand`, `equipment_model`, etc.)
- Fecha de cita

---

## 5. Campos del Modelo para Tipo Software

De todos los campos `fillable` del modelo `Ticket`, los relevantes para software son:

| Campo | Descripción | Llenado por |
|---|---|---|
| `folio` | Auto-generado (ej: `TK2026040042`) | Sistema |
| `user_id` | ID del empleado que abre el ticket | Sistema (auth) |
| `nombre_solicitante` | Nombre del empleado | Sistema (auth) |
| `correo_solicitante` | Correo del empleado | Sistema (auth) |
| `nombre_programa` | Programa afectado | Empleado |
| `descripcion_problema` | Descripción del error | Empleado |
| `imagenes` | Array JSON de capturas en base64 | Empleado (opcional) |
| `tipo_problema` | Siempre `'software'` | Sistema |
| `estado` | `abierto` al crear | Sistema |
| `prioridad` | `baja/media/alta/critica` | Admin TI (al actualizar) |
| `observaciones` | Comentarios técnicos del admin | Admin TI |
| `fecha_cierre` | Cuándo se resolvió | Sistema (al cerrar) |
| `is_read` | Si el admin ya revisó | Sistema |
| `user_has_updates` | Si hay respuesta nueva para el empleado | Sistema |

### Campos que **NO** se usan en tickets de software

```
maintenance_scheduled_at    // Solo mantenimiento
equipment_identifier        // Solo mantenimiento
equipment_brand             // Solo mantenimiento
equipment_model             // Solo mantenimiento
disk_type, ram_capacity     // Solo mantenimiento
battery_status              // Solo mantenimiento
replacement_components      // Solo mantenimiento
computer_profile_id         // Solo mantenimiento
imagenes_admin              // Solo mantenimiento
maintenance_report          // Solo mantenimiento
closure_observations        // Solo mantenimiento
```

---

## 6. Validación del Formulario

```php
// store() — reglas aplicables a software
$validated = $request->validate([
    'nombre_programa' => 'nullable|string|max:255',
    'otro_programa_nombre' => 'nullable|string|max:255',
    'descripcion_problema' => 'required|string',  // REQUERIDO para software
    'tipo_problema' => 'required|in:software,hardware,mantenimiento',
    'imagenes' => 'nullable|array|max:5',
    'imagenes.*' => 'nullable|image|max:2048',
    // 'maintenance_slot_id' NO está en las reglas para software
]);
```

**Resolución de nombre de programa**:
```php
// Si el empleado eligió "Otro" y escribió un nombre libre
$nombrePrograma = $validated['nombre_programa'] ?? null;
if ($nombrePrograma === 'Otro' && !empty($validated['otro_programa_nombre'])) {
    $nombrePrograma = $validated['otro_programa_nombre'];
}
```

---

## 7. Catálogo de Programas Soportados

El selector del formulario muestra un catálogo de programas predefinidos. Este catálogo está hardcodeado en la vista Blade `tickets/create.blade.php`. Los valores comunes esperados incluyen:

| Categoría | Programas típicos |
|---|---|
| Productividad | Microsoft Word, Excel, PowerPoint, Outlook |
| Navegación | Chrome, Edge, Firefox |
| Sistema | Windows Update, Antivirus, Impresoras |
| ERP | Módulo del sistema ERP |
| Comunicación | Teams, Zoom, WhatsApp Web |
| Otro | Campo de texto libre |

> **Para agregar un nuevo programa al catálogo**: Editar el `<select>` en la vista `resources/views/tickets/create.blade.php`. No se requieren cambios en BD ni en el controlador.

---

## 8. Sistema de Prioridad

Solo los tickets de tipo `software` y `hardware` tienen campo `prioridad`. El administrador TI lo asigna al actualizar el ticket.

| Prioridad | Descripción | Badge Color |
|---|---|---|
| `baja` | No bloquea el trabajo, puede esperar | Gris |
| `media` | Afecta productividad pero hay alternativa | Amarillo |
| `alta` | Bloquea trabajo importante, urgente | Naranja |
| `critica` | Sistema caído, impacto en producción | Rojo |

**Validación al actualizar**:
```php
// Solo en update() cuando tipo ≠ mantenimiento
if ($ticket->tipo_problema !== 'mantenimiento') {
    $rules['prioridad'] = 'nullable|in:baja,media,alta,critica';
}
```

---

## 9. Panel Administrativo — Gestión de Tickets Software

**Ruta**: `GET /admin/tickets`  
**Middleware**: `sistemas_admin`

### Vista de lista (`admin/tickets/index`)

Muestra todos los tickets paginados (15 por página). Los tickets de software se identifican con el tipo `'software'` en el listado.

**Filtros disponibles desde la UI**:
- Por estado (`abierto`, `en_proceso`, `cerrado`)
- Por tipo (`software`, `hardware`, `mantenimiento`)
- Búsqueda por folio, nombre del solicitante o programa

### Vista de detalle (`admin/tickets/show`)

El admin ve:
- Información del solicitante
- Programa afectado y descripción
- Imágenes adjuntas (se renderizan desde base64)
- Historial de cambios de estado
- Campo de observaciones para escribir la solución

### Actualización de ticket software (`PATCH /admin/tickets/{ticket}`)

```php
// Campos válidos para update en software
$rules = [
    'estado'       => 'required|in:abierto,en_proceso,cerrado',
    'observaciones' => 'nullable|string',
    'prioridad'    => 'nullable|in:baja,media,alta,critica',
];
```

Al cambiar a estado `cerrado`:
- Se registra `fecha_cierre = now()`
- Se genera `user_notification_summary` con el cambio
- Se pone `user_has_updates = true` para que el empleado vea la resolución

---

## 10. Estados y Transiciones

```
ABIERTO ──────────────────► EN_PROCESO ──────► CERRADO
   │                              │
   │ (empleado cancela)           │ (empleado cancela)
   ▼                              ▼
CANCELADO                    CANCELADO
```

### Reglas de cancelación para el empleado

El endpoint `GET /ticket/{id}/can-cancel` devuelve JSON indicando si el empleado puede cancelar su propio ticket. Las reglas típicas son:
- Solo puede cancelar si el estado es `abierto`
- Si ya está `en_proceso`, no puede cancelar (TI ya lo tomó)

---

## 11. Referencia de Rutas

| Método | URI | Quien accede | Descripción |
|---|---|---|---|
| `GET` | `/ticket/create/software` | Empleado | Formulario de nuevo ticket de software |
| `POST` | `/ticket` | Empleado | Guarda el ticket (mismo endpoint para los 3 tipos) |
| `GET` | `/ticket/mis-tickets` | Empleado | Lista sus tickets de software (y otros tipos) |
| `DELETE` | `/ticket/{id}` | Empleado | Cancela el ticket si está abierto |
| `GET` | `/admin/tickets` | Admin TI | Lista todos los tickets con filtros |
| `GET` | `/admin/tickets/{ticket}` | Admin TI | Detalle del ticket |
| `PATCH` | `/admin/tickets/{ticket}` | Admin TI | Actualiza estado, observaciones, prioridad |

---

## 12. Guía de Mantenimiento

---

### 🟡 Agregar un programa al catálogo del selector

El catálogo está en la vista Blade del formulario. Localizar el `<select name="nombre_programa">` y agregar una nueva opción `<option>`:

```html
<!-- resources/views/tickets/create.blade.php -->
<option value="Nuevo Programa">Nuevo Programa</option>
```

No se requieren cambios en el controlador ni en BD.

---

### 🟡 Cambiar el límite de imágenes por ticket

En `store()`, modificar:
```php
// Actualmente: máx. 5 imágenes de 2MB cada una
'imagenes' => 'nullable|array|max:5',
'imagenes.*' => 'nullable|image|max:2048',
```

Si se aumenta el límite, considerar el impacto en el tamaño de las filas de la tabla `tickets` (las imágenes van en base64 en JSON).

---

### 🟢 Agregar un nuevo nivel de prioridad

```php
// En store() y update(), cambiar:
'prioridad' => 'nullable|in:baja,media,alta,critica',
// Por ejemplo, agregar 'urgente':
'prioridad' => 'nullable|in:baja,media,alta,critica,urgente',
```

Actualizar también el accessor `getPrioridadBadgeAttribute()` en el modelo `Ticket` para agregar el color del badge.

---

### 🟢 Agregar campos de resolución específicos para software

Actualmente el campo `observaciones` es texto libre. Si se necesitan campos estructurados (causa raíz, solución aplicada, tiempo de resolución), agregar una migración:

```bash
php artisan make:migration add_resolution_fields_to_tickets_table
```

Y actualizar el `$fillable` del modelo y la vista de detalle del admin.

---

*Documentación generada el 27 de Abril de 2026 — ERP Estrategia e Innovación*
