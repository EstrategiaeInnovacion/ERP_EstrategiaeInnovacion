# Módulo IT — Hardware e Inventario de Activos — Documentación Maestra
> **ERP Estrategia e Innovación** · Versión documental: Abril 2026  
> Audiencia: Desarrolladores de mantenimiento, administradores de TI

---

## Tabla de Contenido

1. [Visión General](#1-visión-general)
2. [Arquitectura Dual — ERP + AuditoriaActivos](#2-arquitectura-dual--erp--auditoriaactivos)
3. [Flujo de un Ticket de Hardware](#3-flujo-de-un-ticket-de-hardware)
4. [Gestión de Inventario — `ActivosController`](#4-gestión-de-inventario--activoscontroller)
5. [API de Activos — `ActivosApiController`](#5-api-de-activos--activosapicontroller)
6. [Escáner QR — Flujo Completo](#6-escáner-qr--flujo-completo)
7. [Credenciales de Equipos — `CredencialEquipoController`](#7-credenciales-de-equipos--credencialequipocontroller)
8. [Modelos Eloquent — Hardware](#8-modelos-eloquent--hardware)
9. [Servicio `ActivosDbService`](#9-servicio-activosdbservice)
10. [Carta Responsiva IT](#10-carta-responsiva-it)
11. [Exportación Excel](#11-exportación-excel)
12. [Esquema de la Base de Datos de Activos (Externa)](#12-esquema-de-la-base-de-datos-de-activos-externa)
13. [Referencia de Rutas](#13-referencia-de-rutas)
14. [Guía de Mantenimiento](#14-guía-de-mantenimiento)

---

## 1. Visión General

El submódulo de **Hardware** cubre dos sistemas complementarios:

1. **Inventario de activos** (`ActivosController` + `ActivosApiController`): Gestión del ciclo de vida completo de dispositivos físicos — computadoras, periféricos, impresoras — con asignación por QR, historial de movimientos y fotos.

2. **Expediente de credenciales** (`CredencialEquipoController`): Registro interno del ERP que liga un equipo físico a un empleado, almacenando usuario de Windows, contraseñas (cifradas) y correos configurados, con soporte para carta responsiva PDF.

### Propósito de Negocio

| Necesidad | Solución |
|---|---|
| Saber qué equipo tiene cada empleado | `EquipoAsignado` + `devices` en BD externa |
| Historial de movimientos de un dispositivo | Tabla `assignments` en BD AuditoriaActivos |
| Asignar equipos rápidamente en campo | Escáner QR del navegador |
| Proteger contraseñas de equipos | Cifrado con `Crypt::encryptString()` en `EquipoAsignado` |
| Generar carta responsiva PDF firmada | Generación + upload en base64 |
| Ver inventario disponible para asignación | API `availableDevices()` |
| Tickets de falla de hardware | Tipo `hardware` en `TicketController` |

---

## 2. Arquitectura Dual — ERP + AuditoriaActivos

El módulo de Hardware opera sobre **dos bases de datos**:

```
┌─────────────────────────────────────────────────────┐
│         BASE DE DATOS PRINCIPAL (ERP)               │
│                                                     │
│  it_equipos_asignados   → EquipoAsignado model      │
│  it_equipos_correos     → EquipoCorreo model         │
│  it_equipos_perifericos → EquipoPeriferico model     │
│                                                     │
│  Datos del equipo visto desde el empleado:          │
│  usuario de Windows, contraseña (cifrada), correos  │
│  configurados, periféricos asignados, carta         │
│  responsiva firmada                                 │
└─────────────────┬───────────────────────────────────┘
                  │ ActivosDbService
                  │ (conexión via env DB_ACTIVOS_*)
                  ▼
┌─────────────────────────────────────────────────────┐
│       BASE DE DATOS EXTERNA (AuditoriaActivos)      │
│                                                     │
│  devices          → Catálogo de activos físicos     │
│  assignments      → Historial de asignaciones       │
│  employees        → Copia local de empleados        │
│  device_photos    → Fotos de los dispositivos       │
│  device_documents → Documentos adjuntos             │
│  credentials      → Credenciales básicas del device │
│                                                     │
│  Datos del activo físico: marca, modelo, serie,     │
│  estado (available/assigned/maintenance/broken),    │
│  UUID para QR, historial de quién tuvo cada equipo  │
└─────────────────────────────────────────────────────┘
```

### ¿Cuándo se usa cada BD?

| Operación | BD usada |
|---|---|
| Ver credenciales y contraseña de Windows del empleado | ERP (EquipoAsignado) |
| Ver inventario de dispositivos disponibles | AuditoriaActivos |
| Asignar/devolver por QR | AuditoriaActivos (via ActivosDbService) |
| Exportar Excel de credenciales | ERP |
| Generar carta responsiva | ERP (EmpleadoDocumento) |
| Ver historial de quién tuvo un equipo | AuditoriaActivos |

---

## 3. Flujo de un Ticket de Hardware

Un ticket de tipo `hardware` sigue el mismo flujo general de tickets (ver [Modulo_Tickets_Documentacion.md](Modulo_Tickets_Documentacion.md)), con estas diferencias:

| Aspecto | Ticket Software | Ticket Hardware |
|---|---|---|
| Nombre del programa | Software afectado | Nombre del equipo con falla |
| Descripción | Error de la app | Síntoma de la falla física |
| Imágenes | Capturas de pantalla | Fotos del daño físico |
| Prioridad | Configurable | Configurable |
| Reporte técnico | No | No (eso es de mantenimiento) |
| `ComputerProfile` | No se vincula | No se vincula |

El campo `nombre_programa` en tickets de hardware contiene el **nombre descriptivo del equipo** con falla (ej: "Laptop de sala de juntas" o "PC-001").

### Lógica especial: equipo en préstamo

Al cargar el formulario de ticket de hardware, el controlador verifica si el empleado tiene un equipo prestado registrado:

```php
// create('hardware')
$assignedComputerProfile = ComputerProfile::where('is_loaned', true)
    ->where('loaned_to_email', $user->email)
    ->first();
```

Si existe un `ComputerProfile` marcado como prestado al correo del usuario, la vista lo pre-selecciona en el formulario para agilizar el reporte.

---

## 4. Gestión de Inventario — `ActivosController`

**Archivo**: `app/Http/Controllers/Sistemas_IT/ActivosController.php`  
**Dependencia**: `ActivosDbService` (inyectado via constructor)

### Flujo general: verificación de conexión

Todos los métodos inician verificando `$this->activos->isConfigured()`. Si la BD externa no está disponible:
- Los métodos de vista retornan la vista con `noConexion = true` (banner de advertencia).
- Los métodos de acción hacen `redirect()` con error.

Este diseño hace que el sistema **degrade graciosamente** si AuditoriaActivos no responde.

---

### `index(Request $request): View`

**Ruta**: `GET /admin/activos`

Muestra la lista paginada de dispositivos con filtros:

| Parámetro GET | Descripción |
|---|---|
| `search` | Busca por nombre, modelo, serie |
| `type` | Filtra por tipo: `computer`, `peripheral`, `printer`, `other` |
| `status` | Filtra por estado. **Default: `available`**. Pasar vacío para ver todos |

**Variables enviadas a la vista**:
- `$dispositivos` — Colección paginada (15 por página)
- `$stats` — Estadísticas: total, asignados, disponibles, en mantenimiento
- `$todasEtiquetas` — Todos los dispositivos para impresión de etiquetas QR
- `$soloLectura` — `true` si accede desde rutas de RH (`rh.inventario.*`)

---

### `store(Request $request): RedirectResponse`

**Ruta**: `POST /admin/activos`

Crea un dispositivo en AuditoriaActivos. El `uuid` se genera en `ActivosDbService::createDevice()`.

**Campos de validación**:

| Campo | Regla | Descripción |
|---|---|---|
| `name` | `required\|max:255` | Nombre descriptivo del activo |
| `brand` | `nullable\|max:255` | Marca (Dell, HP, Lenovo...) |
| `model` | `nullable\|max:255` | Modelo específico |
| `serial_number` | `required\|max:255` | Número de serie físico |
| `type` | `required\|in:computer,peripheral,printer,other` | Tipo de dispositivo |
| `status` | `required\|in:available,assigned,maintenance,broken` | Estado actual |
| `purchase_date` | `nullable\|date` | Fecha de compra |
| `warranty_expiration` | `nullable\|date` | Vencimiento de garantía |
| `notes` | `nullable\|max:2000` | Observaciones libres |
| `cred_username` | `nullable\|max:255` | Usuario de Windows |
| `cred_password` | `nullable\|max:255` | Contraseña (se almacena en `credentials`) |
| `cred_email` | `nullable\|email` | Correo configurado |
| `photos.*` | `image\|mimes:jpg,jpeg,png,webp,gif\|max:8192` | Fotos (máx. 5, 8MB c/u) |

**Flujo de fotos**:
```php
// Las fotos se guardan localmente en storage/app/private/activos-fotos/
$filename = $uuid . '-' . uniqid() . '.' . $ext;
$filePath = $foto->storeAs('activos-fotos', $filename, 'local');
// Luego se registra en device_photos de la BD externa
$this->activos->addDevicePhoto($uuid, $filePath);
```

---

### `show(string $uuid): View`

**Ruta**: `GET /admin/activos/{uuid}`

Vista de detalle completo del activo. Carga en paralelo:
- Datos del dispositivo (`getDeviceByUuid`)
- Fotos (`getDevicePhotos`)
- Historial de asignaciones (`getAssignmentHistory`)
- Documentos adjuntos (`getDeviceDocuments`)
- Credencial almacenada (`getDeviceCredential`)
- Lista de empleados activos (para el selector de asignación)

---

### `assign(Request $request, string $uuid): RedirectResponse`

**Ruta**: `POST /admin/activos/{uuid}/asignar`

Asigna el dispositivo a un empleado del ERP. Usa el `nombre` del empleado y su `id_empleado` (badge) como claves de correlación en la BD de activos:

```php
$empleado = Empleado::findOrFail($request->empleado_id);
$ok = $this->activos->assignDeviceInActivos(
    uuid:       $uuid,
    assignedTo: $empleado->nombre,
    badge:      $empleado->id_empleado ?: null,
    notes:      $request->input('notes')
);
```

---

### `returnDevice(string $uuid): RedirectResponse`

**Ruta**: `POST /admin/activos/{uuid}/devolver`

Cierra la asignación activa en AuditoriaActivos y cambia el estado del dispositivo a `available`.

---

### `destroy(string $uuid): RedirectResponse`

**Ruta**: `DELETE /admin/activos/{uuid}`

**Solo permite eliminar dispositivos con `status = 'broken'`**. Esta restricción está en el controlador (no en la BD). Si el dispositivo tiene otro estado, retorna un error.

---

## 5. API de Activos — `ActivosApiController`

**Archivo**: `app/Http/Controllers/Sistemas_IT/ActivosApiController.php`  
**Respuestas**: JSON en todos los métodos  
**Dependencia**: `ActivosDbService`

---

### `devicesByUser(int $userId): JsonResponse`

**Ruta**: `GET /admin/activos-api/usuario/{userId}/equipo`

Retorna todos los dispositivos asignados a un usuario del ERP. La búsqueda usa dos estrategias de correlación:

```
1. Por badge (id_empleado del ERP ↔ employee_id de AuditoriaActivos)
2. Por nombre del empleado
3. Si el nombre del empleado ≠ nombre del usuario, hace una segunda búsqueda
   con el nombre del usuario y fusiona resultados deduplicando por uuid
```

**Respuesta JSON**:
```json
{
  "user": {"id": 15, "name": "Juan García"},
  "has_device": true,
  "devices": [
    {
      "device": {"uuid": "abc-123", "name": "Laptop Dell", "type": "computer", ...},
      "assignment": {"assigned_at": "2026-03-01", ...}
    }
  ],
  "peripherals": [
    {
      "device": {"uuid": "def-456", "name": "Mouse HP", "type": "peripheral", ...}
    }
  ]
}
```

**Separación computers/peripherals**: Solo los dispositivos con `type = 'computer'` se cuentan como equipo principal (`has_device`). Los periféricos y otros tipos van en el array `peripherals`.

---

### `lookupByUuid(string $uuid): JsonResponse`

**Ruta**: `GET /admin/activos-api/dispositivo/{uuid}`

Usado por el escáner QR para identificar un activo. Retorna datos del dispositivo más etiquetas legibles para el usuario:

```json
{
  "uuid": "abc-123-...",
  "name": "Laptop Dell Inspiron",
  "brand": "Dell",
  "model": "Inspiron 15",
  "serial": "ABC123456",
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

Asignación rápida desde el escáner QR. Soporta dos tipos de movimiento:

| `tipo_movimiento` | Descripción | Campo adicional |
|---|---|---|
| `asignacion_fija` | Asignación permanente al empleado | — |
| `prestamo_temporal` | Préstamo con fecha de devolución | `fecha_devolucion` |

Para préstamos temporales, el sistema prepend una nota descriptiva:
```
"[Préstamo temporal — Devolución: 15/05/2026 00:00] {notas_del_admin}"
```

---

### `returnViaQr(string $uuid): JsonResponse`

**Ruta**: `POST /admin/activos-api/qr-devolver/{uuid}`

Devolución rápida desde el escáner. Llama a `ActivosDbService::returnDeviceInActivos()`.

---

### `markBrokenViaQr(Request $request, string $uuid): JsonResponse`

**Ruta**: `POST /admin/activos-api/qr-danado/{uuid}`

Marca un dispositivo como dañado con un motivo obligatorio. Cambia su estado a `broken` en AuditoriaActivos.

---

### `photo(int $id): Response` — Proxy de Fotos

**Ruta**: `GET /admin/activos-api/fotos/{id}`

Proxy de seguridad para servir fotos privadas. Las fotos NO son accesibles directamente desde la URL de storage. El controlador las sirve después de validar la ruta con `realpath()` para prevenir path traversal.

**Dos orígenes de fotos**:
1. **AuditoriaActivos**: Usando la variable de entorno `ACTIVOS_STORAGE_PATH`
2. **ERP (fallback)**: En `storage/app/private/activos-fotos/`

```php
// Verificación de seguridad (path traversal prevention)
$realStorage = realpath($storagePath);
$realFile    = realpath($fullPath);
if ($realStorage && $realFile && str_starts_with($realFile, $realStorage)) {
    return response()->file($realFile);  // ← Solo sirve si está dentro del directorio permitido
}
```

---

## 6. Escáner QR — Flujo Completo

El escáner QR del navegador (`/admin/activos/escaner-qr`) es una interfaz JavaScript que usa la cámara del dispositivo para leer QR codes y ejecutar operaciones sin formularios tradicionales.

```
Admin abre /admin/activos/escaner-qr
              │
              ▼
    JavaScript activa la cámara del navegador
              │
              ▼
    QR escaneado → se extrae el UUID del activo
              │
              ▼
    GET /admin/activos-api/dispositivo/{uuid}
    ← Retorna: nombre, estado actual, asignado a (si aplica)
              │
              ▼
    Interfaz muestra las opciones disponibles:
    ┌─────────────────────────────────────┐
    │ [Asignar]  [Devolver]  [Marcar Dañado] │
    └─────────────────────────────────────┘
              │
    El admin elige una acción y confirma
              │
     ┌────────┼────────────┐
     ▼        ▼            ▼
  qr-asignar qr-devolver qr-danado
    │
    ▼ (si asignación)
  Admin selecciona empleado del dropdown
  + tipo de movimiento (fijo/préstamo)
  POST /admin/activos-api/qr-asignar/{uuid}
    │
    ▼
  Respuesta JSON → interfaz muestra confirmación
```

---

## 7. Credenciales de Equipos — `CredencialEquipoController`

**Archivo**: `app/Http/Controllers/Sistemas_IT/CredencialEquipoController.php`

Este controlador gestiona el **expediente interno** de un equipo asignado a un empleado. Vive en la BD del ERP (no en AuditoriaActivos) y almacena credenciales sensibles.

### `index(Request $request): View`

**Ruta**: `GET /admin/credenciales`

Lista los equipos principales (`es_principal = true`) con paginación. Incluye búsqueda por nombre de equipo, modelo, serie o nombre del usuario.

**Datos cargados por equipo**:
- Equipos secundarios del mismo usuario (agrupados por `user_id`)
- Si el usuario tiene carta responsiva IT firmada (via `EmpleadoDocumento`)

---

### `store(Request $request): JsonResponse`

**Ruta**: `POST /admin/credenciales`  
**Responde**: JSON (no redirect)

Crea un expediente de equipo en la BD del ERP y sincroniza en AuditoriaActivos:

```
1. Crea EquipoAsignado en it_equipos_asignados
2. Crea EquipoCorreo por cada correo configurado
3. Crea EquipoPeriferico por cada periférico
4. Si assign_new = true → assignDeviceInActivos() en AuditoriaActivos
5. Para cada periférico → assignDeviceInActivos() en AuditoriaActivos
```

**Nota sobre contraseñas**: El campo `contrasena_equipo` se cifra automáticamente en el modelo via mutator:
```php
public function setContrasenaEquipoAttribute(string $value): void
{
    $this->attributes['contrasena_equipo'] = Crypt::encryptString($value);
}
```

Y se descifra con el accessor:
```php
public function getContrasenaDescifradaAttribute(): string
{
    return Crypt::decryptString($this->attributes['contrasena_equipo']);
}
```

---

### `show(EquipoAsignado $credencial): View`

**Ruta**: `GET /admin/credenciales/{credencial}`

Vista de detalle del expediente. Detecta si el activo principal es una computadora consultando `ActivosDbService::getDeviceTypeByUuid()`. Si la BD de activos no está disponible, asume que es computadora.

---

### `update(Request $request, EquipoAsignado $credencial): JsonResponse`

**Ruta**: `PUT /admin/credenciales/{credencial}`

Actualiza el expediente. La lógica de periféricos es cuidadosa:
1. Identifica periféricos removidos (los que ya no vienen en el payload)
2. Hace `returnDeviceInActivos()` en AuditoriaActivos para los removidos
3. Hace `assignDeviceInActivos()` en AuditoriaActivos para los nuevos
4. Actualiza o recrea los registros de `EquipoCorreo`

---

## 8. Modelos Eloquent — Hardware

### `EquipoAsignado`

**Archivo**: `app/Models/Sistemas_IT/EquipoAsignado.php`  
**Tabla**: `it_equipos_asignados`

| Campo | Tipo | Descripción |
|---|---|---|
| `user_id` | `bigint` | FK → `users.id` |
| `uuid_activos` | `varchar` | UUID del dispositivo en AuditoriaActivos (llave de correlación) |
| `nombre_equipo` | `varchar` | Nombre descriptivo del equipo |
| `modelo` | `varchar` | Modelo del equipo |
| `numero_serie` | `varchar` | Número de serie |
| `photo_id` | `bigint` | ID de la foto principal en AuditoriaActivos |
| `nombre_usuario_pc` | `varchar` | Usuario de Windows configurado |
| `contrasena_equipo` | `varchar` | Contraseña **cifrada** con `Crypt::encryptString()` |
| `notas` | `text` | Observaciones libres |
| `es_principal` | `boolean` | `true` = equipo de trabajo principal; `false` = secundario/prestado |

**Relaciones**:

| Método | Tipo | Destino |
|---|---|---|
| `user()` | `BelongsTo` | `User` — Empleado asignado |
| `correos()` | `HasMany` | `EquipoCorreo` — Correos configurados en el equipo |
| `perifericos()` | `HasMany` | `EquipoPeriferico` — Periféricos asignados |

> **⚠️ IMPORTANTE**: `contrasena_equipo` está en `$hidden`. No sale en serialización JSON por defecto. Para acceder a ella, usar el accessor `$equipo->contrasena_descifrada`.

---

### `EquipoCorreo`

**Tabla**: `it_equipos_correos`

| Campo | Tipo | Descripción |
|---|---|---|
| `equipo_asignado_id` | `bigint` | FK → `it_equipos_asignados.id` |
| `correo` | `varchar` | Dirección de correo |
| `contrasena_correo` | `varchar` | Contraseña del correo (almacenada en texto plano — ver deuda técnica) |

---

### `EquipoPeriferico`

**Tabla**: `it_equipos_perifericos`

| Campo | Tipo | Descripción |
|---|---|---|
| `equipo_asignado_id` | `bigint` | FK → `it_equipos_asignados.id` |
| `uuid_activos` | `varchar` | UUID del periférico en AuditoriaActivos |
| `nombre` | `varchar` | Nombre del periférico |
| `tipo` | `varchar` | Tipo (mouse, teclado, monitor...) |
| `numero_serie` | `varchar` | Número de serie del periférico |

---

## 9. Servicio `ActivosDbService`

**Archivo**: `app/Services/ActivosDbService.php`

Es la capa de abstracción entre los controladores IT y la BD externa AuditoriaActivos. Todos los controladores lo inyectan via constructor.

### Configuración de conexión

La BD externa se configura via variables de entorno:

```env
DB_ACTIVOS_HOST=localhost
DB_ACTIVOS_PORT=3306
DB_ACTIVOS_DATABASE=AuditoriaActivos
DB_ACTIVOS_USERNAME=user_activos
DB_ACTIVOS_PASSWORD=password
ACTIVOS_STORAGE_PATH=/var/www/AuditoriaActivos/storage/app/private
```

### `isConfigured(): bool`

Verifica si las variables de entorno están definidas. **Siempre llamar antes de cualquier operación**. Si retorna `false`, mostrar mensaje de error sin intentar consultas.

### Métodos principales

| Método | Descripción |
|---|---|
| `getAllDevicesPaginated($search, $type, $status, $perPage)` | Lista paginada con filtros |
| `getDeviceByUuid(string $uuid)` | Busca un dispositivo por su UUID |
| `createDevice(array $data): ?string` | Crea un dispositivo, retorna su UUID |
| `updateDevice(string $uuid, array $data): bool` | Actualiza datos del dispositivo |
| `deleteDevice(string $uuid): bool` | Elimina permanentemente |
| `assignDeviceInActivos(string $uuid, string $assignedTo, ?string $badge, ?string $notes): bool` | Crea un registro de asignación |
| `returnDeviceInActivos(string $uuid): bool` | Cierra la asignación activa |
| `markDeviceBroken(string $uuid, string $motivo): bool` | Cambia estado a `broken` |
| `getAssignedDevices(string $nombre, ?string $badge, string $email)` | Dispositivos asignados a un empleado |
| `getAvailableDevices(): array` | Lista dispositivos con `status = 'available'` |
| `getDeviceStats(): array` | Estadísticas de inventario |
| `getDevicePhotos(int $deviceId): array` | Fotos del dispositivo |
| `addDevicePhoto(string $uuid, string $filePath): bool` | Registra una foto |
| `deleteDevicePhoto(int $photoId): bool` | Elimina una foto |
| `getDeviceCredential(int $deviceId)` | Credenciales del dispositivo |
| `getAssignmentHistory(int $deviceId)` | Historial de asignaciones |
| `getDeviceDocuments(int $deviceId)` | Documentos adjuntos |
| `getDeviceTypeByUuid(string $uuid): ?string` | Solo el tipo del dispositivo |
| `getAllDevicesForPrint(): array` | Todos los dispositivos para etiquetas QR |

---

## 10. Carta Responsiva IT

La carta responsiva es un PDF firmado digitalmente por el empleado que certifica la recepción del equipo asignado.

### Flujo de generación

```
Admin accede a GET /admin/credenciales/carta-responsiva/{user}
         │
         ▼
Vista muestra: lista de equipos del usuario + canvas de firma digital
         │
Usuario firma en el canvas (JavaScript)
         │
         ▼
POST /admin/credenciales/carta-responsiva/{user}/guardar
    body: { firma_base64: "data:image/png;base64,...", equipo_id: 5 }
         │
         ▼
Controlador:
  1. Renderiza la vista del PDF con los datos del equipo
  2. Genera PDF con dompdf o similar
  3. Sube el PDF como base64 a EmpleadoDocumento
     categoria: 'Sistema IT'
     nombre: 'Carta Responsiva IT {fecha}'
         │
         ▼
Redirección con éxito
```

### Verificación en el listado

El `index()` de credenciales carga los IDs de usuarios que ya tienen carta responsiva para mostrar un indicador visual:
```php
$usersConCarta = Empleado::whereHas('documentos', function ($q) {
    $q->where('categoria', 'Sistema IT')
      ->where('nombre', 'like', 'Carta Responsiva IT%');
})->join('users', ...)->pluck('users.id')->flip()->all();
```

---

## 11. Exportación Excel

**Ruta**: `GET /admin/credenciales/exportar-excel`

Exporta todos los expedientes de equipos con sus credenciales usando `PhpOffice\PhpSpreadsheet`.

### Columnas del reporte

| Columna | Fuente |
|---|---|
| Empleado | `user.name` |
| Correo empresarial | `user.email` |
| Equipo | `EquipoAsignado.nombre_equipo` |
| Modelo | `EquipoAsignado.modelo` |
| Serie | `EquipoAsignado.numero_serie` |
| Usuario Windows | `EquipoAsignado.nombre_usuario_pc` |
| Contraseña | `EquipoAsignado.contrasena_descifrada` ← descifrada al exportar |
| Correos configurados | Cada `EquipoCorreo.correo` en columna separada |
| Contraseña de correo | `EquipoCorreo.contrasena_correo` |

> **⚠️ Seguridad**: Esta exportación contiene credenciales en texto plano. El endpoint está restringido a `sistemas_admin`. Si se expone accidentalmente, cambiar inmediatamente las contraseñas de los equipos afectados.

---

## 12. Esquema de la Base de Datos de Activos (Externa)

**BD**: `AuditoriaActivos` — Conexión configurada via `DB_ACTIVOS_*` en `.env`

### Tabla: `devices`

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | `bigint` | PK |
| `uuid` | `varchar(36)` | UUID único — impreso en el QR |
| `name` | `varchar` | Nombre descriptivo |
| `brand` | `varchar` | Marca |
| `model` | `varchar` | Modelo |
| `serial_number` | `varchar` | Número de serie |
| `type` | `enum` | `computer`, `peripheral`, `printer`, `other` |
| `status` | `enum` | `available`, `assigned`, `maintenance`, `broken` |
| `purchase_date` | `date` | Fecha de compra |
| `warranty_expiration` | `date` | Fin de garantía |
| `notes` | `text` | Observaciones |
| `employee_name` | `varchar` | Nombre del empleado actual (desnormalizado para consultas rápidas) |

### Tabla: `assignments`

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | `bigint` | PK |
| `device_id` | `bigint` | FK → `devices.id` |
| `employee_id` | `bigint` | FK → `employees.id` (opcional) |
| `assigned_to` | `varchar` | Nombre del asignado |
| `badge` | `varchar` | ID del empleado (badge del ERP) |
| `notes` | `text` | Notas del movimiento |
| `assigned_at` | `datetime` | Inicio de la asignación |
| `returned_at` | `datetime` | Fin de la asignación (null = asignación activa) |

### Tabla: `device_photos`

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | `bigint` | PK |
| `device_id` | `bigint` | FK → `devices.id` |
| `file_path` | `varchar` | Ruta relativa al storage (`activos-fotos/{uuid}-{uniqid}.ext`) |
| `created_at` | `timestamp` | — |

---

## 13. Referencia de Rutas

**Middleware**: `sistemas_admin` (todos los endpoints de admin)

### Inventario de Activos (Web)

| Método | URI | Controlador | Descripción |
|---|---|---|---|
| `GET` | `/admin/activos` | `ActivosController@index` | Lista con filtros |
| `GET` | `/admin/activos/escaner-qr` | `ActivosController@qrScanner` | Interfaz QR |
| `GET` | `/admin/activos/crear` | `ActivosController@create` | Formulario de nuevo activo |
| `POST` | `/admin/activos` | `ActivosController@store` | Guardar activo |
| `GET` | `/admin/activos/{uuid}` | `ActivosController@show` | Detalle del activo |
| `GET` | `/admin/activos/{uuid}/editar` | `ActivosController@edit` | Formulario de edición |
| `PUT` | `/admin/activos/{uuid}` | `ActivosController@update` | Actualizar activo |
| `POST` | `/admin/activos/{uuid}/asignar` | `ActivosController@assign` | Asignar a empleado |
| `POST` | `/admin/activos/{uuid}/devolver` | `ActivosController@returnDevice` | Devolver a inventario |
| `DELETE` | `/admin/activos/{uuid}` | `ActivosController@destroy` | Eliminar (solo si `broken`) |

### API de Activos (JSON)

| Método | URI | Controlador | Descripción |
|---|---|---|---|
| `GET` | `/admin/activos-api/usuario/{userId}/equipo` | `ActivosApiController@devicesByUser` | Equipos de un empleado |
| `GET` | `/admin/activos-api/equipos-disponibles` | `ActivosApiController@availableDevices` | Inventario libre |
| `GET` | `/admin/activos-api/dispositivo/{uuid}` | `ActivosApiController@lookupByUuid` | Datos por UUID/QR |
| `POST` | `/admin/activos-api/qr-asignar/{uuid}` | `ActivosApiController@assignViaQr` | Asignación por QR |
| `POST` | `/admin/activos-api/qr-devolver/{uuid}` | `ActivosApiController@returnViaQr` | Devolución por QR |
| `POST` | `/admin/activos-api/qr-danado/{uuid}` | `ActivosApiController@markBrokenViaQr` | Marcar dañado por QR |
| `GET` | `/admin/activos-api/fotos/{id}` | `ActivosApiController@photo` | Proxy de fotos |
| `DELETE` | `/admin/activos-api/fotos/{id}` | `ActivosApiController@deletePhoto` | Eliminar foto |

### Credenciales de Equipos

| Método | URI | Controlador | Descripción |
|---|---|---|---|
| `GET` | `/admin/credenciales` | `CredencialEquipoController@index` | Lista de expedientes |
| `POST` | `/admin/credenciales` | `CredencialEquipoController@store` | Crear expediente (JSON) |
| `GET` | `/admin/credenciales/{credencial}` | `CredencialEquipoController@show` | Ver expediente |
| `PUT` | `/admin/credenciales/{credencial}` | `CredencialEquipoController@update` | Actualizar expediente |
| `DELETE` | `/admin/credenciales/{credencial}` | `CredencialEquipoController@destroy` | Eliminar expediente |
| `GET` | `/admin/credenciales/exportar-excel` | `CredencialEquipoController@exportExcel` | Exportar XLSX |
| `GET` | `/admin/credenciales/carta-responsiva/{user}` | `CredencialEquipoController@cartaResponsiva` | Vista para firma |
| `POST` | `/admin/credenciales/carta-responsiva/{user}/guardar` | `CredencialEquipoController@guardarCartaResponsiva` | Guardar carta PDF |

---

## 14. Guía de Mantenimiento

---

### 🔴 CRÍTICO: `ActivosDbService::isConfigured()` — Siempre verificar

Si la variable `DB_ACTIVOS_*` no está configurada en producción, **todos los métodos de activos fallarán silenciosamente** (devuelven `null` o `false`). El patrón correcto:

```php
if (! $this->activos->isConfigured()) {
    return redirect()->route('admin.activos.index')
        ->with('error', 'No se pudo conectar a la base de datos de activos.');
}
```

**Al hacer deploy**: Verificar que las variables `DB_ACTIVOS_*` y `ACTIVOS_STORAGE_PATH` están correctamente configuradas.

---

### 🔴 CRÍTICO: Contraseñas de correo en texto plano

El modelo `EquipoCorreo` almacena `contrasena_correo` **sin cifrar**. Esto es una vulnerabilidad conocida. Si alguien accede directamente a la BD, verá las contraseñas de correo en texto plano.

**Solución recomendada**:
1. Agregar mutator en `EquipoCorreo`:
```php
public function setContrasenaCorreoAttribute(?string $value): void
{
    $this->attributes['contrasena_correo'] = $value ? Crypt::encryptString($value) : null;
}
```
2. Agregar accessor para descifrar.
3. Actualizar el exportador Excel para usar el accessor.

---

### 🔴 CRÍTICO: Proxy de fotos — Verificación de path traversal

El método `photo()` usa `realpath()` + `str_starts_with()` para prevenir path traversal. **No modificar esta lógica de seguridad** sin un review cuidadoso.

---

### 🟡 IMPORTANTE: Sincronización bidireccional ERP ↔ AuditoriaActivos

Las operaciones de asignación y devolución deben ejecutarse en **ambas BDs** para mantener consistencia:
- ERP: registro en `it_equipos_asignados`
- AuditoriaActivos: registro en `assignments`

Si una operación falla en una BD pero no en la otra, los inventarios quedan desincronizados. El controlador usa `DB::beginTransaction()` para el ERP pero no puede hacer transacción cross-DB. Si la llamada a `ActivosDbService` falla después de una operación en el ERP, hay que manejar el rollback manualmente o implementar compensación.

---

### 🟡 Agregar un nuevo tipo de dispositivo

1. Actualizar la validación `in:computer,peripheral,printer,other` en `store()` y `update()` de `ActivosController`.
2. Agregar el valor al enum equivalente en la migración de `devices` en AuditoriaActivos.
3. Actualizar el array `$typeLabels` en `ActivosApiController@lookupByUuid`.
4. Actualizar los filtros de la vista.

---

### 🟢 Agregar un campo nuevo al expediente de credenciales

1. Agregar migración: `add_nuevo_campo_to_it_equipos_asignados_table`.
2. Agregar el campo a `$fillable` en `EquipoAsignado`.
3. Actualizar las reglas de validación en `store()` y `update()` de `CredencialEquipoController`.
4. Actualizar la vista.

---

*Documentación generada el 27 de Abril de 2026 — ERP Estrategia e Innovación*
