# Módulo IT — Equipos Asignados (Expedientes de Activos) — Documentación Maestra
> **ERP Estrategia e Innovación** · Versión documental: Abril 2026  
> Audiencia: Administradores de TI, equipo técnico

---

## Tabla de Contenido

1. [Visión General](#1-visión-general)
2. [Arquitectura del Sistema de Equipos](#2-arquitectura-del-sistema-de-equipos)
3. [Modelo `EquipoAsignado` — Detalle Completo](#3-modelo-equipoasignado--detalle-completo)
4. [Modelo `EquipoCorreo` — Cuentas de Correo](#4-modelo-equipocorreo--cuentas-de-correo)
5. [Modelo `EquipoPeriferico` — Periféricos Vinculados](#5-modelo-equipoperiferico--periféricos-vinculados)
6. [Concepto de Equipo Principal vs Secundario](#6-concepto-de-equipo-principal-vs-secundario)
7. [Relación con AuditoriaActivos — UUID como Puente](#7-relación-con-auditoriaactivos--uuid-como-puente)
8. [Panel del Admin — Listado y Búsqueda](#8-panel-del-admin--listado-y-búsqueda)
9. [Vista de Detalle — Expediente Completo](#9-vista-de-detalle--expediente-completo)
10. [Consulta de Equipos por Usuario (API)](#10-consulta-de-equipos-por-usuario-api)
11. [Relación con el Módulo de Mantenimiento](#11-relación-con-el-módulo-de-mantenimiento)
12. [Historial de Migraciones](#12-historial-de-migraciones)
13. [Guía de Mantenimiento del Módulo](#13-guía-de-mantenimiento-del-módulo)

---

## 1. Visión General

El submódulo de **Equipos Asignados** es el expediente central de cada activo tecnológico asignado a un usuario del ERP. Almacena información operativa y técnica que no pertenece al inventario físico (AuditoriaActivos) sino al contexto laboral del empleado:

- **Identificación del equipo**: nombre, modelo, número de serie
- **Credenciales de acceso**: usuario y contraseña del SO (cifrados)
- **Cuentas de correo vinculadas**: una o varias (cifradas)
- **Periféricos asignados**: mouse, teclado, monitor, etc.
- **Foto de referencia**: imagen del dispositivo desde AuditoriaActivos

Este expediente es el que se usa para:
- La **carta responsiva IT** (documento legal de entrega)
- La **ficha técnica de mantenimiento** (`ComputerProfile`)
- El **panel de credenciales** del administrador de TI
- La **exportación Excel** con todas las contraseñas

---

## 2. Arquitectura del Sistema de Equipos

```
┌─────────────────────────────────────────────────────────────────────┐
│                  EXPEDIENTE DE EQUIPOS IT                           │
│                                                                     │
│  BD del ERP (MySQL / conexión principal)                            │
│                                                                     │
│  users                                                              │
│    id, name, email, status                                          │
│    └─── hasMany ──────────────────────────────────────────────────┐ │
│                                                                   │ │
│  it_equipos_asignados (EquipoAsignado)                            │ │
│    id, user_id, uuid_activos, nombre_equipo, modelo               │ │
│    numero_serie, photo_id, nombre_usuario_pc, contrasena_equipo   │ │
│    notas, es_principal                                            │ │
│    │                                                              │ │
│    ├─── hasMany ──────────────────────────────────────────────────┼─┘
│    │                                                              │
│    │  it_equipos_correos (EquipoCorreo)                           │
│    │    id, equipo_asignado_id, correo, contrasena_correo         │
│    │                                                              │
│    └─── hasMany ──────────────────────────────────────────────────┘
│                                                                     │
│       it_equipos_perifericos (EquipoPeriferico)                     │
│         id, equipo_asignado_id, uuid_activos, nombre, tipo          │
│         numero_serie                                                │
│                                                                     │
│  Referencia externa (AuditoriaActivos)                              │
│  ─────────────────────────────────────                              │
│  it_equipos_asignados.uuid_activos ──→ devices.uuid                 │
│  it_equipos_perifericos.uuid_activos → devices.uuid                 │
│  it_equipos_asignados.photo_id ──────→ device_photos.id             │
└─────────────────────────────────────────────────────────────────────┘
```

### Diagrama de relaciones Eloquent

```
User
 └── hasMany EquipoAsignado (where es_principal = true | null → principal)
               └── hasMany EquipoCorreo
               └── hasMany EquipoPeriferico

EquipoAsignado
 └── belongsTo User
 └── hasMany EquipoCorreo
 └── hasMany EquipoPeriferico

EquipoCorreo
 └── belongsTo EquipoAsignado

EquipoPeriferico
 └── belongsTo EquipoAsignado
```

---

## 3. Modelo `EquipoAsignado` — Detalle Completo

**Archivo**: `app/Models/Sistemas_IT/EquipoAsignado.php`  
**Tabla**: `it_equipos_asignados`

### Campos

| Campo | Tipo | Nullable | Descripción |
|---|---|---|---|
| `id` | `bigint` PK auto | No | |
| `user_id` | `bigint` FK | No | Usuario ERP responsable |
| `uuid_activos` | `varchar(255)` unique | No | UUID del dispositivo en AuditoriaActivos |
| `nombre_equipo` | `varchar(255)` | No | Nombre descriptivo del equipo |
| `modelo` | `varchar(255)` | Sí | Modelo del equipo (ej: `EliteBook 840 G8`) |
| `numero_serie` | `varchar(255)` | Sí | Número de serie del fabricante |
| `photo_id` | `integer` | Sí | ID de foto en `device_photos` de AuditoriaActivos |
| `nombre_usuario_pc` | `varchar(255)` | No | Nombre de usuario del SO (ej: `jgarcia`) |
| `contrasena_equipo` | `varchar` | No | Contraseña del SO — **cifrada** con `Crypt::encryptString()` |
| `notas` | `text` | Sí | Observaciones libres |
| `es_principal` | `boolean` | Sí | `true` = principal; `false` = secundario; `null` = principal (legado) |
| `created_at` | `timestamp` | | |
| `updated_at` | `timestamp` | | |

### Propiedades del modelo

```php
protected $table    = 'it_equipos_asignados';
protected $hidden   = ['contrasena_equipo'];  // No aparece en JSON/arrays por defecto
protected $casts    = ['es_principal' => 'boolean'];
```

### Atributos computados

| Accessor | Descripción |
|---|---|
| `$equipo->contrasena_descifrada` | Contraseña en texto claro. Retorna `''` si hay error de descifrado |

### Relaciones

| Método | Tipo | Target | Condición |
|---|---|---|---|
| `user()` | `BelongsTo` | `App\Models\User` | Por `user_id` |
| `correos()` | `HasMany` | `EquipoCorreo` | Por `equipo_asignado_id` |
| `perifericos()` | `HasMany` | `EquipoPeriferico` | Por `equipo_asignado_id` |

### Soft delete y cascade

El modelo **no usa `SoftDeletes`**. Al eliminar un `EquipoAsignado`, los `EquipoCorreo` y `EquipoPeriferico` relacionados se eliminan en cascade (configurado a nivel de FK en la migración o manualmente en el controlador vía `delete()`).

---

## 4. Modelo `EquipoCorreo` — Cuentas de Correo

**Archivo**: `app/Models/Sistemas_IT/EquipoCorreo.php`  
**Tabla**: `it_equipos_correos`

Almacena las cuentas de correo electrónico asociadas a un equipo. Un equipo puede tener múltiples cuentas de correo.

### Campos

| Campo | Tipo | Nullable | Descripción |
|---|---|---|---|
| `id` | `bigint` PK | No | |
| `equipo_asignado_id` | `bigint` FK | No | FK → `it_equipos_asignados.id` |
| `correo` | `varchar(255)` | No | Dirección de correo electrónico |
| `contrasena_correo` | `varchar` | Sí | Contraseña — **cifrada** con `Crypt::encryptString()`, `null` si no se proporcionó |
| `created_at` | `timestamp` | | |
| `updated_at` | `timestamp` | | |

### Propiedades del modelo

```php
protected $hidden = ['contrasena_correo'];  // No aparece en JSON automático
```

### Atributos computados

| Accessor | Descripción |
|---|---|
| `$correo->contrasena_descifrada` | Contraseña en texto claro. Retorna `''` si no hay contraseña o hay error de descifrado |

### Casos de uso típicos

```php
// Mostrar la contraseña de un correo
foreach ($equipo->correos as $correo) {
    echo $correo->correo . ' → ' . $correo->contrasena_descifrada;
}

// Agregar un correo con contraseña
$equipo->correos()->create([
    'correo'            => 'usuario@empresa.com',
    'contrasena_correo' => 'MiContraseña',  // el mutador la cifra
]);

// Actualizar solo si viene contraseña nueva
if (! empty($datos['contrasena_correo'])) {
    $correoExistente->update(['contrasena_correo' => $datos['contrasena_correo']]);
}
```

---

## 5. Modelo `EquipoPeriferico` — Periféricos Vinculados

**Archivo**: `app/Models/Sistemas_IT/EquipoPeriferico.php`  
**Tabla**: `it_equipos_perifericos`

Representa un dispositivo periférico asignado junto con el equipo principal (o secundario). Cada periférico tiene su propio UUID en AuditoriaActivos, por lo que su asignación se sincroniza independientemente.

### Campos

| Campo | Tipo | Nullable | Descripción |
|---|---|---|---|
| `id` | `bigint` PK | No | |
| `equipo_asignado_id` | `bigint` FK | No | FK → `it_equipos_asignados.id` |
| `uuid_activos` | `varchar` | No | UUID del periférico en AuditoriaActivos |
| `nombre` | `varchar(255)` | No | Nombre descriptivo (ej: `Mouse HP Wireless`) |
| `tipo` | `varchar(255)` | Sí | Tipo libre (ej: `Mouse`, `Teclado`, `Monitor`, `Headset`) |
| `numero_serie` | `varchar(255)` | Sí | Número de serie del periférico |
| `created_at` | `timestamp` | | |
| `updated_at` | `timestamp` | | |

### Relaciones

| Método | Tipo | Target |
|---|---|---|
| `equipo()` | `BelongsTo` | `EquipoAsignado` (por `equipo_asignado_id`) |

### Diferencias con `EquipoCorreo`

| Aspecto | `EquipoCorreo` | `EquipoPeriferico` |
|---|---|---|
| Tiene contraseña | Sí (cifrada) | No |
| Referencia a AuditoriaActivos | No | Sí (uuid_activos) |
| Sincroniza asignación en Activos | No | Sí (al crear/eliminar) |
| Tipo de datos | Email / credencial | Dispositivo físico |

---

## 6. Concepto de Equipo Principal vs Secundario

El campo `es_principal` diferencia el equipo de trabajo habitual del empleado de equipos adicionales.

### Reglas de negocio

| `es_principal` | Descripción |
|---|---|
| `true` | Equipo de trabajo habitual del empleado |
| `null` | Equipo principal (registros anteriores al campo; tratado igual que `true`) |
| `false` | Equipo secundario (sala de juntas, laptop de cliente, etc.) |

### Filtro en el listado

El `index()` de `CredencialEquipoController` solo carga equipos principales en la paginación:

```php
->where(function ($q) {
    $q->where('es_principal', true)->orWhereNull('es_principal');
})
```

Los secundarios se cargan en una consulta separada agrupados por `user_id`:

```php
$secundarios = EquipoAsignado::whereIn('user_id', $userIds)
    ->where('es_principal', false)
    ->get()
    ->groupBy('user_id');
```

### Nota en la carta responsiva

Al crear un equipo secundario, si las notas no ya indican su naturaleza, el sistema antepone automáticamente:

```php
$notas = '[Equipo Secundario / Cliente]' . ($notas ? ' ' . $notas : '');
```

### Vista de detalle (`show`)

La vista del equipo principal muestra una sección de "Equipos Secundarios" con todos los `EquipoAsignado` del mismo `user_id` donde `es_principal = false`.

---

## 7. Relación con AuditoriaActivos — UUID como Puente

El campo `uuid_activos` en `EquipoAsignado` y `EquipoPeriferico` es el vínculo entre el expediente del ERP y el inventario físico de AuditoriaActivos.

### Operaciones que sincronizan AuditoriaActivos

| Operación en ERP | Efecto en AuditoriaActivos |
|---|---|
| Alta equipo (con `assign_new=true`) | `devices.status = 'assigned'` + INSERT en `assignments` |
| Alta equipo (sin `assign_new`) | Sin cambio — el equipo ya estaba asignado |
| Alta periférico | `devices.status = 'assigned'` + INSERT en `assignments` |
| Baja equipo | `assignments.returned_at = now()` + `devices.status = 'available'` |
| Baja periférico | `assignments.returned_at = now()` + `devices.status = 'available'` |
| Editar (sin cambiar periféricos) | Sin cambio en Activos |
| Editar (quitar periférico) | `assignments.returned_at = now()` para ese UUID |
| Editar (agregar periférico) | Nueva asignación en Activos |

### Consulta inversa — ver expediente desde el activo

Desde el panel de activos (`/admin/activos/{uuid}`), se puede ver el expediente de credenciales asociado:

```php
// En ActivosController::show()
$credencial = $this->activos->getDeviceCredential($dispositivo->id);
```

`getDeviceCredential()` busca en la tabla `credentials` de AuditoriaActivos (si existe). Para el expediente ERP completo, se busca en `EquipoAsignado` por `uuid_activos`.

### Consulta desde el frontend de usuario

La ruta `GET /admin/activos-api/usuario/{userId}/equipo` ejecuta `devicesByUser()` en `ActivosApiController`, que consulta AuditoriaActivos y no el ERP. Por eso la búsqueda usa nombre y badge, no el `uuid_activos` del expediente.

---

## 8. Panel del Admin — Listado y Búsqueda

**Ruta**: `GET /admin/credenciales`

### Comportamiento del buscador

El campo `search` filtra:

```php
$query->where(function ($q) use ($search) {
    $q->where('nombre_equipo', 'like', "%{$search}%")
      ->orWhere('modelo', 'like', "%{$search}%")
      ->orWhere('numero_serie', 'like', "%{$search}%")
      ->orWhere('nombre_usuario_pc', 'like', "%{$search}%")
      ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
      ->orWhereIn('user_id',
          EquipoAsignado::where('es_principal', false)
              ->where(function ($sq) use ($search) {
                  $sq->where('nombre_equipo', 'like', "%{$search}%")
                     ->orWhere('modelo', 'like', "%{$search}%")
                     ->orWhere('numero_serie', 'like', "%{$search}%");
              })
              ->pluck('user_id')
      );
});
```

El buscador busca también en los **equipos secundarios** del usuario y muestra el registro principal correspondiente. Así, buscar el número de serie de un equipo secundario retorna el usuario dueño de ese registro.

### Indicadores visuales en el listado

| Indicador | Condición |
|---|---|
| "Tiene carta firmada" | `isset($usersConCarta[$equipo->user_id])` |
| Número de correos | `count($equipo->correos)` |
| Número de periféricos | `count($equipo->perifericos)` |
| Número de equipos secundarios | `count($secundarios[$equipo->user_id] ?? [])` |

---

## 9. Vista de Detalle — Expediente Completo

**Ruta**: `GET /admin/credenciales/{credencial}`

La vista de detalle muestra el expediente completo de un equipo en forma de tarjeta expandible:

### Sección 1: Datos del equipo

| Dato | Fuente |
|---|---|
| Nombre del equipo | `$credencial->nombre_equipo` |
| Modelo | `$credencial->modelo` |
| Número de serie | `$credencial->numero_serie` |
| Foto del dispositivo | Proxy: `/admin/activos-api/fotos/{$credencial->photo_id}` |
| Tipo de dispositivo | `$esComputadora` — detectado via `getDeviceTypeByUuid()` |
| Propietario | `$credencial->user->name` |
| Notas | `$credencial->notas` |

### Sección 2: Credenciales de acceso

| Dato | Fuente |
|---|---|
| Usuario PC | `$credencial->nombre_usuario_pc` |
| Contraseña | `$credencial->contrasena_descifrada` (mostrado con campo enmascarado + botón de visibilidad) |

### Sección 3: Cuentas de correo

```php
foreach ($credencial->correos as $correo):
    $correo->correo             // dirección
    $correo->contrasena_descifrada  // contraseña descifrada
endforeach;
```

### Sección 4: Periféricos

```php
foreach ($credencial->perifericos as $per):
    $per->nombre        // Mouse HP Wireless
    $per->tipo          // Mouse
    $per->numero_serie  // MX1234
    $per->uuid_activos  // para enlazar al panel de activos
endforeach;
```

### Sección 5: Equipos secundarios

Lista de `$equiposSecundarios` con la misma estructura que el equipo principal.

---

## 10. Consulta de Equipos por Usuario (API)

Para módulos como el perfil de usuario o el panel de RH, se puede consultar los equipos de un usuario via:

**Ruta**: `GET /admin/activos-api/usuario/{userId}/equipo`

Esta ruta consulta **AuditoriaActivos** (no el ERP), por lo que muestra el inventario físico asignado, independientemente de si tiene expediente de credenciales en `it_equipos_asignados`.

**Respuesta separada por tipo**:
- `devices` → Solo dispositivos de `type = 'computer'`
- `peripherals` → Todos los demás tipos asignados al usuario

Para acceder al expediente de credenciales desde este resultado, usar `devices[0].device.uuid` y buscarlo en `EquipoAsignado::where('uuid_activos', $uuid)`.

---

## 11. Relación con el Módulo de Mantenimiento

Cuando TI procesa un ticket de mantenimiento y llena los campos técnicos, el sistema crea o actualiza un `ComputerProfile`. Este perfil tiene un campo `equipo_asignado_id` que lo vincula con el expediente de credenciales.

```
Ticket de mantenimiento (tipo = 'mantenimiento')
  └── equipment_identifier → ComputerProfile.identifier
  └── equipment_brand, equipment_model, etc.

ComputerProfile
  └── equipo_asignado_id → EquipoAsignado.id
```

Esta vinculación permite:
- Mostrar el historial de mantenimiento del equipo en el expediente
- Pre-llenar el formulario de alta de credenciales con los datos técnicos del perfil
- Ver si un equipo tiene expediente desde el panel de mantenimiento

---

## 12. Historial de Migraciones

| Fecha | Archivo | Cambio |
|---|---|---|
| 2025-12-09 | `create_sistemas_it_tables.php` | Tablas base del módulo IT incluyendo `it_credenciales_equipos` (modelo legado `CredencialEquipo`) |
| 2026-02-10 | `create_equipo_asignado_tables.php` | ⭐ Creación de `it_equipos_asignados`, `it_equipos_correos`, `it_equipos_perifericos` con campo `uuid_activos` para sincronizar con AuditoriaActivos |
| 2026-02-15 | `add_es_principal_to_equipos_asignados.php` | Añade `es_principal` para distinguir equipo principal de secundario |
| 2026-03-01 | `add_photo_id_to_equipos_asignados.php` | Añade `photo_id` para referenciar foto en AuditoriaActivos |
| 2026-04-17 | `add_equipo_asignado_id_to_computer_profiles.php` | Vincula `computer_profiles` con `it_equipos_asignados` |

---

## 13. Guía de Mantenimiento del Módulo

---

### 🔴 CRÍTICO: `uuid_activos` debe ser único y existir en AuditoriaActivos

El campo `uuid_activos` tiene una constraint `UNIQUE` en la tabla `it_equipos_asignados`. Si se intenta registrar el mismo UUID dos veces, el store fallará con error de duplicado.

Esto puede ocurrir si:
- Se crea un expediente, se elimina, y se intenta crear de nuevo (el UUID ya no está en el ERP pero AuditoriaActivos lo tiene como disponible — correcto).
- Se importan datos históricos con UUIDs repetidos.

**Prevención**: Antes de guardar, verificar `EquipoAsignado::where('uuid_activos', $uuid)->exists()` si el flujo no garantiza unicidad.

---

### 🔴 CRÍTICO: Eliminación en cascada de correos y periféricos

Al eliminar un `EquipoAsignado`, el controlador elimina el registro Eloquent directamente con `$credencial->delete()`. Esto solo elimina el registro padre; los correos y periféricos deben eliminarse primero o estar configurados con `ON DELETE CASCADE` a nivel de BD.

**Verificar** que las FKs en `it_equipos_correos.equipo_asignado_id` e `it_equipos_perifericos.equipo_asignado_id` tienen `ON DELETE CASCADE`.

---

### 🔴 CRÍTICO: No acceder a `contrasena_equipo` directamente en vistas o APIs

El modelo tiene `protected $hidden = ['contrasena_equipo']`, por lo que no aparece en `toArray()` ni en `toJson()`. Pero si se accede por nombre directo (`$equipo->contrasena_equipo`), se obtiene el valor cifrado. Siempre usar `$equipo->contrasena_descifrada`.

```php
// ✅ Correcto
echo $equipo->contrasena_descifrada;

// ❌ Incorrecto — muestra el hash cifrado
echo $equipo->contrasena_equipo;
```

---

### 🟡 IMPORTANTE: Periféricos sin nombre o UUID vacío

El formulario valida `uuid_activos` como requerido al crear un periférico (`'perifericos.*.uuid' => 'required_with:perifericos.*|string'`). Sin embargo, si el periférico llega sin UUID (datos importados o migración de datos legacy), `uuid_activos` quedará en blanco y la sincronización con AuditoriaActivos no funcionará para ese periférico.

**Detección**: Buscar `EquipoPeriferico::where('uuid_activos', '')->orWhereNull('uuid_activos')->count()`.

---

### 🟡 IMPORTANTE: La búsqueda de secundarios en `index()` usa una sub-consulta

El buscador del listado lanza una sub-consulta adicional (`EquipoAsignado::where('es_principal', false)->where(...)`) para encontrar usuarios cuyo equipo secundario coincide con el término de búsqueda. En instancias con muchos equipos, esto puede ser lento.

**Optimización**: Añadir índices en `it_equipos_asignados` sobre `(user_id, es_principal)`, `nombre_equipo`, `modelo`, `numero_serie`.

---

### 🟢 SEGURO: Agregar un campo informativo al expediente

Para añadir un campo sin contraseña (ej: `departamento_uso`):

1. Migration: `ALTER TABLE it_equipos_asignados ADD COLUMN departamento_uso VARCHAR(255) NULL;`
2. Añadir al `$fillable` del modelo.
3. Añadir validación en `store()` y `update()`.
4. Mostrar en la vista `show` y en el formulario.
5. Agregar columna en la hoja Equipos de `exportExcel()`.

No se requieren cambios en la lógica de sincronización con AuditoriaActivos.

---

### 🟢 SEGURO: Agregar un tipo de periférico nuevo al catálogo

El campo `tipo` en `EquipoPeriferico` es texto libre. No requiere cambios en BD ni en el controlador para agregar nuevos tipos. Solo actualizar el selector en la vista Blade con la nueva opción.

---

### Checklist de deploy para cambios en Equipos Asignados

- [ ] ¿Se añadió un campo con contraseña a `EquipoAsignado` o `EquipoCorreo`? Verificar que tiene mutador de cifrado y accessor de descifrado, y que está en `$hidden`.
- [ ] ¿Se añadió o eliminó una relación Eloquent? Verificar que `load()` y `with()` se actualizan en los métodos del controlador.
- [ ] ¿Se modificó el filtro principal/secundario en `index()`? Verificar que `es_principal IS NULL` sigue tratándose como principal.
- [ ] ¿Se cambiaron las FKs en la migración? Confirmar que el cascade de eliminación sigue funcionando.
- [ ] ¿Se añadió un campo al Excel? Probar la generación con equipos reales, incluyendo equipos sin valor en el nuevo campo.
- [ ] ¿Se modificó la búsqueda en `index()`? Medir el tiempo de respuesta con el volumen real de registros.

---

*Documentación generada el 27 de Abril de 2026 — ERP Estrategia e Innovación*
