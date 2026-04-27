# Módulo IT — Activos (Inventario de Dispositivos) — Documentación Maestra
> **ERP Estrategia e Innovación** · Versión documental: Abril 2026  
> Audiencia: Administradores de TI, desarrolladores de integración

---

## Tabla de Contenido

1. [Visión General](#1-visión-general)
2. [Arquitectura — Base de Datos Dual](#2-arquitectura--base-de-datos-dual)
3. [Esquema de la BD Externa `AuditoriaActivos`](#3-esquema-de-la-bd-externa-auditoriaactivos)
4. [Referencia de Métodos — `ActivosDbService`](#4-referencia-de-métodos--activosdbservice)
5. [Referencia de Métodos — `ActivosController` (Panel Web)](#5-referencia-de-métodos--activoscontroller-panel-web)
6. [Referencia de Métodos — `ActivosApiController` (JSON API)](#6-referencia-de-métodos--activosApicontroller-json-api)
7. [Flujo de Alta de un Dispositivo](#7-flujo-de-alta-de-un-dispositivo)
8. [Flujo de Asignación y Devolución](#8-flujo-de-asignación-y-devolución)
9. [Escáner QR — Flujo Completo](#9-escáner-qr--flujo-completo)
10. [Proxy de Fotos — Seguridad y Configuración](#10-proxy-de-fotos--seguridad-y-configuración)
11. [Referencia de Rutas](#11-referencia-de-rutas)
12. [Variables de Entorno Requeridas](#12-variables-de-entorno-requeridas)
13. [Guía de Mantenimiento del Módulo](#13-guía-de-mantenimiento-del-módulo)

---

## 1. Visión General

El submódulo de **Activos IT** gestiona el inventario físico de dispositivos de la empresa: computadoras, periféricos, impresoras y otros. Es el único módulo del ERP que opera contra dos bases de datos simultáneas:

- **ERP principal** (`mysql` / conexión por defecto): Guarda los expedientes de credenciales, los registros de usuarios, los tickets IT y los documentos de empleados.
- **AuditoriaActivos** (`activos` / conexión externa): Almacena el inventario físico real — dispositivos, asignaciones históricas, fotos y empleados del sistema de activos.

El `ActivosDbService` es el único punto de acceso a la BD externa y **nunca debe usarse sin verificar primero `isConfigured()`**.

### Propósito de Negocio

| Necesidad | Solución |
|---|---|
| Inventario centralizado de todos los dispositivos | Panel `/admin/activos` con paginación y filtros |
| Asignar un dispositivo a un empleado | `assign()` → sincroniza en AuditoriaActivos |
| Devolver un dispositivo | `returnDevice()` → cierra asignación en AuditoriaActivos |
| Rastrear quién tiene qué equipo | `getAssignedDevices()` con búsqueda multi-criterio |
| Fotografiar los dispositivos | Proxy de fotos con path traversal prevention |
| Escanear QR para movimientos rápidos | Escáner de cámara + 3 endpoints JSON (asignar/devolver/dañado) |
| Acceso de solo lectura para RH | Rutas `/rh/inventario/*` con `soloLectura = true` |

---

## 2. Arquitectura — Base de Datos Dual

```
┌─────────────────────────────────────────────────────────────────────┐
│                  MÓDULO ACTIVOS IT                                  │
│                                                                     │
│  ERP (BD principal)          AuditoriaActivos (BD externa)          │
│  ─────────────────           ─────────────────────────────          │
│  empleados                   devices (inventario)                   │
│    └─ id_empleado  ←──────→  employees.employee_id                  │
│  users                       assignments (historial)                │
│    └─ email        ←──────→  users.email                            │
│  it_equipos_asignados        device_photos (fotos)                  │
│    └─ uuid_activos ←──────→  devices.uuid                           │
│                                                                     │
│  ActivosDbService ──── única capa de acceso a AuditoriaActivos      │
│  ┌──────────────────────────────────────────────────────────┐       │
│  │  isConfigured() ← SIEMPRE llamar antes de cualquier op  │       │
│  │  getAvailableDevices()    getAllDevicesPaginated()        │       │
│  │  getAssignedDevices()     getDeviceStats()               │       │
│  │  getDeviceByUuid()        getDeviceTypeByUuid()          │       │
│  │  getAssignmentHistory()   getDeviceDocuments()           │       │
│  │  getDeviceCredential()    getAllDevicesForPrint()         │       │
│  │  assignDeviceInActivos()  returnDeviceInActivos()        │       │
│  │  markDeviceBroken()       createDevice() / updateDevice()│       │
│  │  addDevicePhoto()         getDevicePhotos()              │       │
│  │  getPhotoPath()           deleteDevicePhoto()            │       │
│  │  deleteDevice()           mapRow()  (privado)            │       │
│  └──────────────────────────────────────────────────────────┘       │
│                                                                     │
│  ActivosController ──── Panel web del inventario                    │
│  ActivosApiController ── JSON API para escáner QR y frontend        │
└─────────────────────────────────────────────────────────────────────┘
```

### Correlación de identidades entre bases de datos

| Dato en el ERP | Dato en AuditoriaActivos | Uso |
|---|---|---|
| `empleados.id_empleado` (badge) | `employees.employee_id` | Correlación primaria al asignar |
| `users.email` | `users.email` | Correlación secundaria |
| `empleado.nombre` / `user.name` | `employees.name`, `assignments.assigned_to` | Fallback por nombre |
| `it_equipos_asignados.uuid_activos` | `devices.uuid` | Vínculo entre expediente ERP y activo físico |

---

## 3. Esquema de la BD Externa `AuditoriaActivos`

Conexión configurada como `'activos'` en `config/database.php`. Las credenciales se leen de las variables `DB_ACTIVOS_*` en `.env`.

### Tabla `devices`

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | `bigint` PK | ID numérico interno |
| `uuid` | `varchar(36)` unique | UUID del dispositivo (impreso en etiqueta QR) |
| `name` | `varchar` | Nombre descriptivo (ej: `Laptop HP EliteBook 001`) |
| `brand` | `varchar` nullable | Marca |
| `model` | `varchar` nullable | Modelo |
| `serial_number` | `varchar` nullable | Número de serie |
| `type` | `enum` | `computer`, `peripheral`, `printer`, `other` |
| `status` | `enum` | `available`, `assigned`, `maintenance`, `broken` |
| `purchase_date` | `date` nullable | Fecha de compra |
| `warranty_expiration` | `date` nullable | Vencimiento de garantía |
| `notes` | `text` nullable | Notas adicionales |

### Tabla `assignments`

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | `bigint` PK | |
| `device_id` | `bigint` FK → `devices.id` | Dispositivo asignado |
| `employee_id` | `bigint` nullable FK → `employees.id` | Empleado receptor |
| `user_id` | `bigint` nullable FK → `users.id` | Usuario de Activos |
| `assigned_to` | `varchar` nullable | Texto libre del responsable |
| `assigned_at` | `datetime` | Cuándo se asignó |
| `returned_at` | `datetime` nullable | NULL = asignación vigente |
| `notes` | `text` nullable | Notas de la asignación |

> Asignación vigente = `returned_at IS NULL`  
> Historial completo = todos los registros del `device_id`

### Tabla `employees`

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | `bigint` PK | |
| `name` | `varchar` | Nombre completo |
| `employee_id` | `varchar` unique | Badge/ID del empleado en el ERP |
| `department` | `varchar` nullable | Departamento |
| `position` | `varchar` nullable | Puesto |
| `is_active` | `boolean` | Si sigue activo |

### Tabla `device_photos`

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | `bigint` PK | |
| `device_id` | `bigint` FK → `devices.id` | |
| `file_path` | `varchar` | Ruta relativa al storage (del ERP o de AuditoriaActivos) |
| `caption` | `varchar` nullable | Descripción de la foto |

---

## 4. Referencia de Métodos — `ActivosDbService`

**Namespace**: `App\Services\ActivosDbService`  
**Inyección**: Via constructor en `ActivosController` y `ActivosApiController`

---

### `isConfigured(): bool`

Intenta obtener el PDO de la conexión `'activos'`. Retorna `false` si la BD no está disponible.

**Regla**: **Llamar siempre antes de cualquier otro método**. Si retorna `false`, mostrar el estado `noConexion` en la vista o responder `503`.

```php
if (! $this->activos->isConfigured()) {
    return response()->json(['error' => 'BD de activos no disponible.'], 503);
}
```

---

### `getAvailableDevices(): array`

Retorna todos los dispositivos disponibles para asignar. Incluye también dispositivos en estado `'assigned'` que no tienen una asignación activa en la tabla `assignments` (estado huérfano).

**Formato de retorno**: Array de arrays `mapRow()` con campos: `uuid`, `name`, `brand`, `model`, `serial_number`, `type`, `assignment`, `photos`.

---

### `getAssignedDevices(string $nombre, ?string $badge, ?string $email): array`

Busca dispositivos actualmente asignados a un empleado. La búsqueda es multi-criterio en orden de confiabilidad:

1. `employees.employee_id = $badge` (badge exacto — más confiable)
2. `users.email = $email` (email exacto del usuario en Activos)
3. `employees.name LIKE "%$nombre%"` (nombre completo)
4. `users.name LIKE "%$nombre%"` (nombre del usuario de Activos)
5. `assignments.assigned_to LIKE "%$nombre%"` (texto libre)
6. Palabras del nombre (>5 caracteres) con búsqueda LIKE individual

**Formato de retorno**: Array de `['device' => mapRow()]` para compatibilidad con `d.device ?? d` en el frontend.

> **Umbral de palabras**: Solo se buscan palabras con más de **5 caracteres** para evitar falsos positivos. "Ana" (3 chars) no se busca individualmente.

---

### `getAllDevicesPaginated(?string $search, ?string $type, ?string $status, int $perPage): LengthAwarePaginator`

Paginación manual de todos los dispositivos con filtros opcionales. Se usa en el panel `/admin/activos`.

| Parámetro | Descripción |
|---|---|
| `$search` | Busca en nombre, marca, modelo, serie, nombre del empleado asignado |
| `$type` | `computer`, `peripheral`, `printer`, `other` — null = todos |
| `$status` | `available`, `assigned`, `maintenance`, `broken` — null = todos; por defecto = `'available'` |
| `$perPage` | Registros por página (default 15) |

---

### `getDeviceStats(): array`

Retorna estadísticas del inventario:

```php
[
    'total'      => 125,
    'by_status'  => ['available' => 40, 'assigned' => 80, 'maintenance' => 3, 'broken' => 2],
    'by_type'    => ['computer' => 65, 'peripheral' => 45, 'printer' => 10, 'other' => 5],
]
```

---

### `getDeviceByUuid(string $uuid): ?object`

Retorna el registro completo de un dispositivo por UUID, incluyendo el nombre del empleado asignado actualmente. Retorna `null` si no existe.

---

### `createDevice(array $data): ?string`

Crea un nuevo dispositivo en AuditoriaActivos. Genera un UUID v4 automáticamente.

| Campo en `$data` | Tipo | Requerido |
|---|---|---|
| `name` | string | ✅ |
| `brand` | string | No |
| `model` | string | No |
| `serial_number` | string | ✅ |
| `type` | enum | ✅ |
| `status` | enum | ✅ |
| `purchase_date` | date string | No |
| `warranty_expiration` | date string | No |
| `notes` | string | No |
| `cred_username` | string | No (se guarda en tabla `credentials` de Activos) |
| `cred_password` | string | No |
| `cred_email` | email | No |
| `cred_email_password` | string | No |

Retorna el UUID del dispositivo creado, o `null` en caso de error.

---

### `updateDevice(string $uuid, array $data): bool`

Actualiza un dispositivo existente. Mismos campos que `createDevice()`.

---

### `assignDeviceInActivos(string $uuid, string $assignedTo, ?string $badge, ?string $notes): bool`

Crea una asignación activa y marca el dispositivo como `'assigned'`. Si el dispositivo ya tenía una asignación abierta, la cierra primero.

La operación se ejecuta en una transacción DB.

---

### `returnDeviceInActivos(string $uuid): bool`

Cierra la asignación activa del dispositivo y lo marca como `'available'`. Operación en transacción.

---

### `markDeviceBroken(string $uuid, string $reason): bool`

Marca el dispositivo como `'broken'`, cierra la asignación activa si existe y registra el motivo en `assignments.notes`.

---

### `addDevicePhoto(string $uuid, string $filePath, ?string $caption): bool`

Inserta un registro en `device_photos`. `$filePath` es relativo al `storage/app/private` del ERP.

---

### `getPhotoPath(int $photoId): ?string`

Retorna el `file_path` de una foto para servir via proxy. Retorna `null` si no existe.

---

### `deleteDevice(string $uuid): bool`

Elimina permanentemente un dispositivo y todos sus registros relacionados (`device_photos`, `assignments`, `device_documents`, `credentials`). Solo se permite si el `status = 'broken'`.

---

## 5. Referencia de Métodos — `ActivosController` (Panel Web)

**Archivo**: `app/Http/Controllers/Sistemas_IT/ActivosController.php`  
**Ruta base**: `/admin/activos`

| Método | Ruta | Descripción |
|---|---|---|
| `index()` | `GET /admin/activos` | Listado paginado con filtros. Si `!isConfigured()` → vista con `noConexion=true` |
| `qrScanner()` | `GET /admin/activos/escaner-qr` | Página del escáner QR con lista de empleados activos |
| `create()` | `GET /admin/activos/crear` | Formulario de alta de nuevo dispositivo |
| `store()` | `POST /admin/activos` | Crea el dispositivo + guarda fotos en storage local |
| `show()` | `GET /admin/activos/{uuid}` | Detalle: datos, fotos, asignación activa, historial, documentos, credencial |
| `edit()` | `GET /admin/activos/{uuid}/editar` | Formulario de edición |
| `update()` | `PUT /admin/activos/{uuid}` | Actualiza datos + guarda fotos nuevas |
| `assign()` | `POST /admin/activos/{uuid}/asignar` | Asigna a un empleado ERP → sincroniza AuditoriaActivos |
| `returnDevice()` | `POST /admin/activos/{uuid}/devolver` | Devuelve el dispositivo → sincroniza AuditoriaActivos |
| `destroy()` | `DELETE /admin/activos/{uuid}` | Elimina permanentemente (solo si `status='broken'`) |

### Detalle de `show()`

Carga las siguientes variables para la vista:

| Variable | Descripción |
|---|---|
| `$dispositivo` | Datos del dispositivo desde AuditoriaActivos |
| `$fotos` | Array de fotos con IDs y URLs del proxy |
| `$historial` | Array de asignaciones pasadas (incluyendo fechas devueltas) |
| `$documentos` | Documentos adjuntos en AuditoriaActivos |
| `$credencial` | Credenciales del dispositivo (tabla `credentials` de Activos) |
| `$empleados` | Empleados activos del ERP para el selector de asignación |
| `$soloLectura` | `true` si la ruta es `/rh/inventario/*` |

### Validación en `store()` y `update()`

```php
'name'                => 'required|string|max:255',
'brand'               => 'nullable|string|max:255',
'model'               => 'nullable|string|max:255',
'serial_number'       => 'required|string|max:255',
'type'                => 'required|in:computer,peripheral,printer,other',
'status'              => 'required|in:available,assigned,maintenance,broken',
'purchase_date'       => 'nullable|date',
'warranty_expiration' => 'nullable|date',
'notes'               => 'nullable|string|max:2000',
'cred_username'       => 'nullable|string|max:255',
'cred_password'       => 'nullable|string|max:255',
'cred_email'          => 'nullable|email|max:255',
'cred_email_password' => 'nullable|string|max:255',
'photos'              => 'nullable|array|max:5',
'photos.*'            => 'image|mimes:jpg,jpeg,png,webp,gif|max:8192',
```

Fotos se almacenan en `storage/app/private/activos-fotos/{uuid}-{uniqid}.{ext}`.

---

## 6. Referencia de Métodos — `ActivosApiController` (JSON API)

**Archivo**: `app/Http/Controllers/Sistemas_IT/ActivosApiController.php`  
**Ruta base**: `/admin/activos-api`

---

### `devicesByUser(int $userId): JsonResponse`

**Ruta**: `GET /admin/activos-api/usuario/{userId}/equipo`

Retorna los dispositivos asignados al usuario ERP especificado. Separados en `devices` (computadoras) y `peripherals` (otros).

**Respuesta**:
```json
{
  "user": { "id": 42, "name": "Juan García" },
  "has_device": true,
  "devices": [
    {
      "device": {
        "uuid": "550e8400-e29b-...",
        "name": "HP EliteBook 840",
        "brand": "HP",
        "model": "EliteBook 840 G8",
        "type": "computer",
        "assignment": "Juan García",
        "photos": [{ "id": 12, "url": "https://erp.com/admin/activos-api/fotos/12" }]
      }
    }
  ],
  "peripherals": []
}
```

**Búsqueda dual de nombre**: Si `empleado.nombre ≠ user.name`, se ejecutan dos búsquedas y se deduplican por `device.uuid`.

---

### `availableDevices(): JsonResponse`

**Ruta**: `GET /admin/activos-api/equipos-disponibles`

Lista de todos los dispositivos disponibles. Usado por el formulario de alta de credenciales para poblar el selector de equipos.

---

### `lookupByUuid(string $uuid): JsonResponse`

**Ruta**: `GET /admin/activos-api/dispositivo/{uuid}`

Busca un dispositivo por su UUID (escaneado desde un código QR). Retorna datos completos con etiquetas legibles.

**Respuesta**:
```json
{
  "uuid": "550e8400-...",
  "name": "Laptop HP 001",
  "brand": "HP",
  "model": "EliteBook 840",
  "serial": "CNU123456",
  "type": "computer",
  "type_label": "Computadora",
  "status": "available",
  "status_label": "Disponible",
  "assigned_to": null
}
```

---

### `assignViaQr(Request $request, string $uuid): JsonResponse`

**Ruta**: `POST /admin/activos-api/qr-asignar/{uuid}`

Asigna un dispositivo vía escáner QR. Soporta asignaciones fijas y préstamos temporales.

**Validación**:
```php
'empleado_id'      => 'required|exists:empleados,id',
'tipo_movimiento'  => 'required|in:asignacion_fija,prestamo_temporal',
'fecha_devolucion' => 'nullable|date|after:now',
'notas'            => 'nullable|string|max:1000',
```

Si el tipo es `prestamo_temporal`, la nota incluye automáticamente: `[Préstamo temporal — Devolución: dd/mm/YYYY HH:MM]`.

---

### `returnViaQr(string $uuid): JsonResponse`

**Ruta**: `POST /admin/activos-api/qr-devolver/{uuid}`

Cierra la asignación activa del dispositivo escaneado.

---

### `markBrokenViaQr(Request $request, string $uuid): JsonResponse`

**Ruta**: `POST /admin/activos-api/qr-danado/{uuid}`

Marca el dispositivo como dañado con un motivo requerido.

---

### `photo(int $id): Response`

**Ruta**: `GET /admin/activos-api/fotos/{id}`

Proxy de fotos. Ver [Sección 10](#10-proxy-de-fotos--seguridad-y-configuración).

---

### `deletePhoto(int $id): JsonResponse`

**Ruta**: `DELETE /admin/activos-api/fotos/{id}`

Elimina un registro de foto de AuditoriaActivos y, si el archivo fue subido desde el ERP (en `storage/app/private`), lo borra del disco.

---

## 7. Flujo de Alta de un Dispositivo

```
Admin abre GET /admin/activos/crear
             │
             ▼
Admin llena el formulario:
  - name, brand, model, serial_number (obligatorios)
  - type: computer / peripheral / printer / other
  - status: available (por defecto para equipos nuevos)
  - purchase_date, warranty_expiration (opcionales)
  - notes (hasta 2000 chars)
  - cred_username, cred_password, cred_email, cred_email_password
  - Hasta 5 fotos (jpg/png/webp/gif, máx 8 MB c/u)
             │
             ▼
POST /admin/activos
  → $data = $request->validate(...)
  → $uuid = $this->activos->createDevice($data)
    └── BD de Activos: INSERT INTO devices (uuid = Str::uuid())
  → Si hay fotos:
      foreach foto:
        $filename = "{$uuid}-{uniqid()}.{ext}"
        $filePath = "activos-fotos/{$filename}"
        Storage::disk('local')->put($filePath, ...)
        $this->activos->addDevicePhoto($uuid, $filePath)
             │
             ▼
redirect → GET /admin/activos/{uuid}
  (vista de detalle del nuevo dispositivo)
```

---

## 8. Flujo de Asignación y Devolución

### Asignación desde el panel web

```
Admin está en /admin/activos/{uuid}
              │
Admin selecciona un empleado del selector
              ▼
POST /admin/activos/{uuid}/asignar
  body: { empleado_id: 15, notes: '...' }
              │
  $empleado = Empleado::findOrFail(15)
  $this->activos->assignDeviceInActivos(
      uuid: $uuid,
      assignedTo: $empleado->nombre,
      badge: $empleado->id_empleado,
      notes: $request->notes
  )
  Operación en transacción:
    1. assignments.returned_at = now() (cierra asignación previa)
    2. INSERT INTO assignments (returned_at = NULL)
    3. UPDATE devices SET status = 'assigned'
              │
redirect → /admin/activos/{uuid}
```

### Devolución

```
POST /admin/activos/{uuid}/devolver
  $this->activos->returnDeviceInActivos($uuid)
  Operación en transacción:
    1. assignments.returned_at = now()
    2. UPDATE devices SET status = 'available'
```

---

## 9. Escáner QR — Flujo Completo

La página `/admin/activos/escaner-qr` activa la cámara del dispositivo del técnico para escanear códigos QR impresos en los activos.

```
Técnico abre /admin/activos/escaner-qr
                 │
  Frontend activa camera API → lee QR → obtiene UUID del dispositivo
                 │
  GET /admin/activos-api/dispositivo/{uuid}
  ← { name, type_label, status_label, assigned_to }
                 │
  UI muestra el dispositivo encontrado con opciones:
  ┌─────────────────────────────────────────────┐
  │ Laptop HP EliteBook 840                      │
  │ Estado: Disponible                           │
  │ [Asignar a empleado] [Marcar como dañado]   │
  └─────────────────────────────────────────────┘
                 │
  ┌─ Si el técnico elige "Asignar a empleado":
  │  POST /admin/activos-api/qr-asignar/{uuid}
  │  body: { empleado_id, tipo_movimiento, fecha_devolucion?, notas }
  │
  ├─ Si el técnico elige "Devolver":
  │  POST /admin/activos-api/qr-devolver/{uuid}
  │
  └─ Si el técnico elige "Marcar como dañado":
     POST /admin/activos-api/qr-danado/{uuid}
     body: { motivo: '...' }
```

---

## 10. Proxy de Fotos — Seguridad y Configuración

El endpoint `GET /admin/activos-api/fotos/{id}` sirve fotos de dos fuentes posibles:

### Fuente 1: AuditoriaActivos (`ACTIVOS_STORAGE_PATH`)

```
.env:
ACTIVOS_STORAGE_PATH=/var/www/AuditoriaActivos/storage/app/private

Flujo:
$storagePath = env('ACTIVOS_STORAGE_PATH')
$fullPath = $storagePath . '/' . $filePath
realpath($storagePath) vs realpath($fullPath)
→ Si str_starts_with($realFile, $realStorage): servir archivo
```

### Fuente 2: ERP local (fallback)

```
Si no está en ACTIVOS_STORAGE_PATH o no se configuró:
$localBase = storage_path('app/private')
$localPath = $localBase . '/' . $filePath
realpath($localBase) vs realpath($localPath)
→ Si str_starts_with($realFile, $realBase): servir archivo
```

### Prevención de Path Traversal

El proxy usa `realpath()` + `str_starts_with()` para confirmar que el archivo resuelto está **dentro del directorio esperado**. Esto bloquea rutas como `../../../etc/passwd`.

**NUNCA modificar este patrón de seguridad**. Si se cambia la lógica de validación de rutas, se introduce una vulnerabilidad de path traversal.

---

## 11. Referencia de Rutas

### Admin IT — Inventario de Activos

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `GET` | `/admin/activos` | `activos.index` | Listado paginado con filtros |
| `GET` | `/admin/activos/escaner-qr` | `activos.qr-scanner` | Página del escáner QR |
| `GET` | `/admin/activos/crear` | `activos.create` | Formulario de alta |
| `POST` | `/admin/activos` | `activos.store` | Crear dispositivo |
| `GET` | `/admin/activos/{uuid}` | `activos.show` | Detalle del dispositivo |
| `GET` | `/admin/activos/{uuid}/editar` | `activos.edit` | Formulario de edición |
| `PUT` | `/admin/activos/{uuid}` | `activos.update` | Actualizar dispositivo |
| `DELETE` | `/admin/activos/{uuid}` | `activos.destroy` | Eliminar (solo `broken`) |
| `POST` | `/admin/activos/{uuid}/asignar` | `activos.assign` | Asignar a empleado |
| `POST` | `/admin/activos/{uuid}/devolver` | `activos.return` | Devolver dispositivo |

### Admin IT — API JSON

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `GET` | `/admin/activos-api/usuario/{userId}/equipo` | `activos.devices-by-user` | Equipos de un usuario ERP |
| `GET` | `/admin/activos-api/equipos-disponibles` | `activos.available-devices` | Dispositivos disponibles |
| `GET` | `/admin/activos-api/fotos/{id}` | `activos.photo` | Proxy de foto |
| `DELETE` | `/admin/activos-api/fotos/{id}` | `activos.photo.delete` | Eliminar foto |
| `GET` | `/admin/activos-api/dispositivo/{uuid}` | `activos.lookup` | Buscar por UUID (QR) |
| `POST` | `/admin/activos-api/qr-asignar/{uuid}` | `activos.qr-assign` | Asignar vía QR |
| `POST` | `/admin/activos-api/qr-devolver/{uuid}` | `activos.qr-return` | Devolver vía QR |
| `POST` | `/admin/activos-api/qr-danado/{uuid}` | `activos.qr-broken` | Marcar dañado vía QR |

### RH — Inventario (Solo Lectura)

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `GET` | `/rh/inventario/fotos/{id}` | `rh.inventario.photo` | Proxy de foto |
| `GET` | `/rh/inventario` | `rh.inventario.index` | Listado (solo lectura) |
| `GET` | `/rh/inventario/{uuid}` | `rh.inventario.show` | Detalle (solo lectura) |

---

## 12. Variables de Entorno Requeridas

```ini
# Conexión a la BD de AuditoriaActivos
DB_ACTIVOS_CONNECTION=mysql
DB_ACTIVOS_HOST=127.0.0.1
DB_ACTIVOS_PORT=3306
DB_ACTIVOS_DATABASE=AuditoriaActivos
DB_ACTIVOS_USERNAME=erp_user
DB_ACTIVOS_PASSWORD=secret

# Ruta al storage de AuditoriaActivos para servir sus fotos como proxy
# Debe apuntar al directorio storage/app/private de AuditoriaActivos
ACTIVOS_STORAGE_PATH=/var/www/AuditoriaActivos/storage/app/private
```

Si las variables `DB_ACTIVOS_*` no están configuradas o la BD no responde, `isConfigured()` retorna `false` y el módulo muestra el estado de sin conexión.

---

## 13. Guía de Mantenimiento del Módulo

---

### 🔴 CRÍTICO: No llamar métodos de `ActivosDbService` sin verificar `isConfigured()`

Si la BD de AuditoriaActivos no está disponible (mantenimiento, red caída), cualquier método del servicio arrojará una excepción que `try/catch` convierte en `false` o array vacío. Sin embargo, el controlador debe verificar primero para dar una respuesta apropiada al usuario, no una pantalla en blanco.

```php
// ✅ Correcto
if (! $this->activos->isConfigured()) {
    return view('...', ['noConexion' => true]);
}

// ❌ Incorrecto — puede causar respuesta vacía o 500 si hay problema de red
$dispositivos = $this->activos->getAllDevicesPaginated();
```

---

### 🔴 CRÍTICO: No modificar la validación de path traversal en `photo()`

El proxy de fotos tiene validación de `realpath()` + `str_starts_with()` para prevenir path traversal. Cualquier cambio que elimine esa validación introduce una vulnerabilidad de lectura de archivos arbitrarios del servidor.

---

### 🔴 CRÍTICO: La sincronización de BD no es atómica entre ERP y Activos

Cuando se elimina un `EquipoAsignado` desde el panel de credenciales, el `destroy()` de `CredencialEquipoController` primero llama `returnDeviceInActivos()` (en AuditoriaActivos) y luego hace `$credencial->delete()` (en el ERP). Si la segunda operación falla, el dispositivo queda marcado como `available` en AuditoriaActivos pero el expediente sigue en el ERP.

**Solución actual**: Log de errores. Detectar y corregir manualmente en `/admin/activos`.

---

### 🟡 IMPORTANTE: Añadir un nuevo tipo de dispositivo

Actualmente los tipos son: `computer`, `peripheral`, `printer`, `other`.

Para añadir un nuevo tipo (ej: `tablet`):

1. Ejecutar migration en **AuditoriaActivos** para modificar el `enum` en la tabla `devices`:
   ```sql
   ALTER TABLE devices MODIFY COLUMN type ENUM('computer','peripheral','printer','other','tablet');
   ```
2. Actualizar las validaciones en `ActivosController::store()` y `update()`:
   ```php
   'type' => 'required|in:computer,peripheral,printer,other,tablet',
   ```
3. Actualizar el array `$typeLabels` en `ActivosApiController::lookupByUuid()`.
4. Actualizar el filtro de separación `computers` vs `peripherals` en `devicesByUser()` si los tablets deben aparecer como equipo principal.

---

### 🟡 IMPORTANTE: La búsqueda de empleados por nombre puede dar falsos positivos

`getAssignedDevices()` usa LIKE `%nombre%` contra varias columnas. En empresas con empleados con nombres similares, puede retornar dispositivos de otra persona. La búsqueda por badge (`employee_id`) es la más confiable; asegurarse de que todos los empleados tengan `id_empleado` configurado en el ERP.

---

### 🟢 SEGURO: Agregar un campo al formulario del dispositivo

Para añadir un nuevo campo (ej: `location` — ubicación física del dispositivo):

1. Ejecutar migration en AuditoriaActivos: `ALTER TABLE devices ADD COLUMN location VARCHAR(255) NULL`.
2. Añadir `'location' => 'nullable|string|max:255'` a la validación en `store()` y `update()`.
3. Actualizar `createDevice()` y `updateDevice()` en `ActivosDbService` para incluir `location`.
4. Añadir el campo en la vista `create.blade.php` y `edit.blade.php`.
5. Mostrar en `show.blade.php`.

---

### Checklist de deploy para cambios en el módulo de Activos

- [ ] ¿Cambió el schema de AuditoriaActivos? Ejecutar las migrations en esa BD, no en la del ERP.
- [ ] ¿Se agregó un nuevo tipo o status de dispositivo? Actualizar todos los `in:` de validación.
- [ ] ¿Se modificó `mapRow()`? Verificar que el frontend siga recibiendo `d.device ?? d` correctamente.
- [ ] ¿Se modificó la validación del proxy de fotos? Revisar con un path traversal de prueba.
- [ ] ¿Se cambiaron las variables `.env`? Verificar en servidor que `isConfigured()` retorna `true`.
- [ ] ¿Se modificó `getAssignedDevices()`? Probar con badge, sin badge, con nombres similares.

---

*Documentación generada el 27 de Abril de 2026 — ERP Estrategia e Innovación*
