# Módulo Logística — Post-Operaciones — Documentación Maestra
> **ERP Estrategia e Innovación** · Versión documental: Abril 2026  
> Audiencia: Ejecutivos de cuenta, administradores de logística, desarrolladores

---

## Tabla de Contenido

1. [Visión General](#1-visión-general)
2. [Arquitectura del Módulo](#2-arquitectura-del-módulo)
3. [Modelo de Datos](#3-modelo-de-datos)
4. [Estructura de dos niveles: Plantillas y Asignaciones](#4-estructura-de-dos-niveles-plantillas-y-asignaciones)
5. [Referencia de Métodos — `PostOperacionController`](#5-referencia-de-métodos--postoperacioncontroller)
6. [Comportamiento del `bulkUpdate`: Dos formatos](#6-comportamiento-del-bulkupdate-dos-formatos)
7. [Lógica: Pendiente borra el registro pivot](#7-lógica-pendiente-borra-el-registro-pivot)
8. [Overlay Pattern en `getByOperacion`](#8-overlay-pattern-en-getbyoperacion)
9. [Referencia de Rutas](#9-referencia-de-rutas)
10. [Guía de Mantenimiento del Módulo](#10-guía-de-mantenimiento-del-módulo)

---

## 1. Visión General

Las **Post-Operaciones** son una lista de tareas o verificaciones que deben realizarse **después de completar una operación logística**. El módulo implementa un sistema de plantillas reutilizables que se asignan automáticamente a cada operación, permitiendo rastrear su cumplimiento tarea por tarea.

### Propósito de negocio

| Necesidad | Solución |
|---|---|
| Definir qué tareas post-operación existen (catálogo global) | `PostOperacion` con `operacion_logistica_id = null` y `status = 'Plantilla'` |
| Asignar esas tareas a una operación específica | `PostOperacionOperacion` — tabla pivot |
| Ver qué tareas están pendientes/completadas por operación | `getByOperacion()` con overlay pattern |
| Actualizar el estado de múltiples tareas en un click | `bulkUpdate()` — dos formatos de request soportados |
| Eliminar una plantilla global y todas sus asignaciones | `destroyGlobal()` — elimina pivot antes que la plantilla |

---

## 2. Arquitectura del Módulo

```
┌─────────────────────────────────────────────────────────────────────┐
│                      POST-OPERACIONES                               │
│                                                                     │
│  Middleware: auth + area.logistica                                  │
│  PostOperacionController → /logistica/post-operaciones              │
│                                                                     │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │  index()          → Vista principal con plantillas globales   │  │
│  │  store()          → Crea plantilla global                     │  │
│  │  update()         → Edita nombre/descripción de plantilla     │  │
│  │  destroy()        → Elimina asignación individual de op.      │  │
│  │  destroyGlobal()  → Elimina plantilla + todas las asignaciones│  │
│  │  getByOperacion() → JSON: tareas con estado por operación     │  │
│  │  bulkUpdate()     → Actualiza estado de múltiples tareas      │  │
│  │  assign()         → Asigna plantilla a operación              │  │
│  └───────────────────────────────────────────────────────────────┘  │
│                                                                     │
│  Modelos                                                            │
│  ──────                                                              │
│  PostOperacion         → Catálogo de plantillas globales            │
│  PostOperacionOperacion → Pivot: asignación por operación           │
│  OperacionLogistica    → Operación que contiene las tareas          │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 3. Modelo de Datos

### `PostOperacion` — Tabla `post_operaciones`

Actúa como **catálogo de plantillas** cuando `operacion_logistica_id = null`.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | `bigint` PK | |
| `nombre` | `varchar(255)` | Nombre de la tarea post-operación |
| `descripcion` | `text` nullable | Descripción o instrucciones |
| `status` | `varchar(50)` | `Plantilla` (cuando es global) o `Pendiente`/`Completado` (cuando es asignación legacy) |
| `operacion_logistica_id` | `bigint` FK nullable | `null` = plantilla global; ID = asignación directa (legacy) |
| `orden` | `integer` default 0 | Orden de la tarea en el checklist |
| `created_at` / `updated_at` | `timestamp` | |

**Relaciones**:
```php
operacion()              → BelongsTo OperacionLogistica
asignaciones()           → HasMany PostOperacionOperacion
```

### `PostOperacionOperacion` — Tabla `post_operacion_operaciones`

Tabla pivot que registra el **estado real por operación**.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | `bigint` PK | |
| `post_operacion_id` | `bigint` FK | FK → `post_operaciones.id` |
| `operacion_logistica_id` | `bigint` FK | FK → `operaciones_logisticas.id` |
| `estado` | `varchar(50)` | `Pendiente` o `Completado` |
| `fecha_completado` | `timestamp` nullable | Cuándo se marcó como completado |
| `observacion` | `text` nullable | Nota opcional del ejecutivo |
| `created_at` / `updated_at` | `timestamp` | |

**Índice único**: `(post_operacion_id, operacion_logistica_id)` — Un estado por tarea por operación.

---

## 4. Estructura de dos niveles: Plantillas y Asignaciones

```
NIVEL 1: Plantillas Globales (post_operaciones con operacion_logistica_id = null)
─────────────────────────────────────────────────────────────────────────────────
PostOperacion ID=1: "Verificar factura comercial" | status=Plantilla
PostOperacion ID=2: "Comprobar pago de pedimento" | status=Plantilla
PostOperacion ID=3: "Archivar documentos en digital" | status=Plantilla

NIVEL 2: Asignaciones por Operación (post_operacion_operaciones)
────────────────────────────────────────────────────────────────
PostOperacionOperacion: post_operacion_id=1, operacion_logistica_id=42, estado=Completado ✅
PostOperacionOperacion: post_operacion_id=2, operacion_logistica_id=42, estado=Pendiente  ⏳
PostOperacionOperacion: post_operacion_id=3, operacion_logistica_id=42, [NO EXISTE]       ⏳ (sin registro = pendiente)
```

**Clave**: Si no existe registro en `post_operacion_operaciones` para una plantilla+operación, se asume `Pendiente`. Solo se crea el registro cuando alguien marca la tarea como Completada.

---

## 5. Referencia de Métodos — `PostOperacionController`

**Archivo**: `app/Http/Controllers/Logistica/PostOperacionController.php`

---

### `index(): View`

**Ruta**: `GET /logistica/post-operaciones`

Retorna la vista de administración de plantillas con todas las plantillas globales:
```php
$plantillas = PostOperacion::whereNull('operacion_logistica_id')
    ->where('status', 'Plantilla')
    ->orderBy('orden')
    ->get();
```

---

### `store(Request $request): JsonResponse`

**Ruta**: `POST /logistica/post-operaciones`

Crea una nueva plantilla global:
```php
PostOperacion::create([
    'nombre'                 => $request->nombre,
    'descripcion'            => $request->descripcion,
    'status'                 => 'Plantilla',
    'operacion_logistica_id' => null, // Plantilla global
    'orden'                  => $request->orden ?? 0
]);
```

---

### `getByOperacion($operacionId): JsonResponse`

**Ruta**: `GET /logistica/post-operaciones/operacion/{operacion_id}`

Método clave del módulo. Aplica el **overlay pattern** (ver sección 8):

```php
$plantillas = PostOperacion::whereNull('operacion_logistica_id')
    ->where('status', 'Plantilla')
    ->orderBy('orden')
    ->get();

$asignaciones = PostOperacionOperacion::where('operacion_logistica_id', $operacionId)
    ->get()
    ->keyBy('post_operacion_id');

return $plantillas->map(function($plantilla) use ($asignaciones) {
    $asignacion = $asignaciones->get($plantilla->id);
    return [
        'id'               => $plantilla->id,
        'nombre'           => $plantilla->nombre,
        'descripcion'      => $plantilla->descripcion,
        'estado'           => $asignacion?->estado ?? 'Pendiente',
        'fecha_completado' => $asignacion?->fecha_completado,
        'observacion'      => $asignacion?->observacion,
        'asignacion_id'    => $asignacion?->id,
    ];
});
```

---

### `bulkUpdate(Request $request): JsonResponse`

**Ruta**: `POST /logistica/post-operaciones/bulk-update`

Actualiza el estado de múltiples tareas de una operación. Soporta dos formatos de request (ver sección 6).

---

### `destroy($id): JsonResponse`

**Ruta**: `DELETE /logistica/post-operaciones/{id}`

Elimina una **asignación individual** (`PostOperacionOperacion`) sin tocar la plantilla global. Usado cuando se quiere "desmarcar" o limpiar el estado de una tarea en una operación específica.

---

### `destroyGlobal($id): JsonResponse`

**Ruta**: `DELETE /logistica/post-operaciones/{id}/global`

Elimina una **plantilla global** y **todas sus asignaciones**:

```php
// Primero eliminar todas las asignaciones (PostOperacionOperacion)
PostOperacionOperacion::where('post_operacion_id', $id)->delete();

// Luego eliminar la plantilla
PostOperacion::find($id)->delete();
```

El orden importa: si se borra la plantilla primero, los registros orphan en `post_operacion_operaciones` generan inconsistencias (a menos que haya FK con CASCADE).

---

### `assign(Request $request): JsonResponse`

**Ruta**: `POST /logistica/post-operaciones/asignar`

Asigna explícitamente una plantilla a una operación con estado inicial:

```php
PostOperacionOperacion::firstOrCreate(
    ['post_operacion_id' => $request->post_operacion_id, 'operacion_logistica_id' => $request->operacion_id],
    ['estado' => 'Pendiente']
);
```

---

## 6. Comportamiento del `bulkUpdate`: Dos formatos

El método `bulkUpdate()` fue diseñado para ser flexible y acepta dos formatos de payload:

### Formato A — Map directo `{id: status}`

```json
{
  "operacion_id": 42,
  "post_operaciones": {
    "1": "Completado",
    "2": "Pendiente",
    "3": "Completado"
  }
}
```

### Formato B — Array de objetos `{id: {estado: status}}`

```json
{
  "operacion_id": 42,
  "post_operaciones": [
    { "id": 1, "estado": "Completado", "observacion": "Factura verificada OK" },
    { "id": 2, "estado": "Pendiente" },
    { "id": 3, "estado": "Completado" }
  ]
}
```

El controlador detecta el formato automáticamente:

```php
foreach ($request->post_operaciones as $postOpId => $statusOrData) {
    if (is_array($statusOrData)) {
        // Formato B: objeto con estado y observacion
        $estado = $statusOrData['estado'];
        $observacion = $statusOrData['observacion'] ?? null;
    } else {
        // Formato A: string directo
        $estado = $statusOrData;
        $observacion = null;
    }
    // Procesar...
}
```

---

## 7. Lógica: Pendiente borra el registro pivot

Cuando una tarea se marca como `Pendiente`, el registro en `post_operacion_operaciones` **se elimina**:

```php
if ($estado === 'Pendiente') {
    // Eliminar la asignación → vuelve al estado por defecto (no existe = pendiente)
    PostOperacionOperacion::where('post_operacion_id', $postOpId)
        ->where('operacion_logistica_id', $request->operacion_id)
        ->delete();
} else {
    // Crear o actualizar la asignación
    PostOperacionOperacion::updateOrCreate(
        ['post_operacion_id' => $postOpId, 'operacion_logistica_id' => $request->operacion_id],
        ['estado' => $estado, 'fecha_completado' => now(), 'observacion' => $observacion]
    );
}
```

**Razón de diseño**: Mantiene la tabla pivot liviana (solo almacena los estados no-default). El default es Pendiente, así que solo los Completados se almacenan. Esto facilita las consultas de "cuántas tareas completadas" y reduce el crecimiento de la tabla.

---

## 8. Overlay Pattern en `getByOperacion`

El patrón de **overlay** (superposición) combina la lista maestra de plantillas con el estado real por operación:

```
Plantillas globales (fuente de verdad de QUÉ tareas existen)
     +
Asignaciones existentes (fuente de verdad del ESTADO actual)
     =
Vista consolidada (qué tareas hay y en qué estado están)
```

**Sin este patrón**: Para mostrar el checklist de una operación, habría que crear registros en `post_operacion_operaciones` para todas las plantillas al crear la operación. Con el overlay, los registros se crean solo cuando hay una acción (marcar completado).

**Ventaja**: Al añadir una nueva plantilla global, automáticamente aparece como Pendiente en todas las operaciones sin necesidad de migration o backfill.

---

## 9. Referencia de Rutas

**Middleware**: `auth`, `area.logistica`  
**Prefijo**: `/logistica/post-operaciones`  
**Nombre base**: `logistica.post-operaciones.`

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `GET` | `/logistica/post-operaciones` | `logistica.post-operaciones.index` | Vista de plantillas globales |
| `POST` | `/logistica/post-operaciones` | `logistica.post-operaciones.store` | Crear plantilla |
| `PUT` | `/logistica/post-operaciones/{id}` | `logistica.post-operaciones.update` | Editar plantilla |
| `DELETE` | `/logistica/post-operaciones/{id}` | `logistica.post-operaciones.destroy` | Eliminar asignación individual |
| `DELETE` | `/logistica/post-operaciones/{id}/global` | `logistica.post-operaciones.destroy-global` | Eliminar plantilla + asignaciones |
| `GET` | `/logistica/post-operaciones/operacion/{id}` | `logistica.post-operaciones.by-operacion` | JSON: checklist de una operación |
| `POST` | `/logistica/post-operaciones/bulk-update` | `logistica.post-operaciones.bulk-update` | Actualizar múltiples tareas |
| `POST` | `/logistica/post-operaciones/asignar` | `logistica.post-operaciones.assign` | Asignar plantilla a operación |

---

## 10. Guía de Mantenimiento del Módulo

---

### 🔴 CRÍTICO: `destroyGlobal()` sin verificación de operaciones en curso

Al eliminar una plantilla global, se eliminan **todas las asignaciones**, incluyendo las de operaciones activas. No hay confirmación de "esta plantilla tiene asignaciones en N operaciones activas".

**Recomendación**: Antes de eliminar, mostrar cuántas operaciones tienen esta tarea completada o pendiente:
```php
$conAsignaciones = PostOperacionOperacion::where('post_operacion_id', $id)->count();
// Advertir al usuario si conAsignaciones > 0
```

---

### 🟡 IMPORTANTE: Los dos formatos de `bulkUpdate` pueden confundir

El frontend debería estandarizar en un solo formato. Si la UI usa el Formato A y otra parte de la UI usa el Formato B, puede haber comportamientos inesperados si el código de detección falla.

**Recomendación**: Deprecar el Formato A y migrar todo al Formato B (más expresivo y extensible).

---

### 🟡 IMPORTANTE: Sin timestamp de cuándo se marcó Pendiente

Cuando se marca como Pendiente (y se borra el registro), no hay registro histórico de cuándo se desmarcó. El `fecha_completado` solo registra la última vez que se completó.

**Mejora**: Añadir un log de cambios:
```php
PostOperacionLog::create([
    'post_operacion_id' => $id,
    'operacion_id' => $operacionId,
    'estado_anterior' => $estadoAnterior,
    'estado_nuevo' => $estado,
    'usuario_id' => auth()->id()
]);
```

---

### 🟢 SEGURO: Añadir una nueva plantilla global

1. Navegar a `GET /logistica/post-operaciones`
2. Crear la plantilla con `POST /logistica/post-operaciones`
3. La nueva plantilla aparecerá automáticamente como Pendiente en todas las operaciones activas (por el overlay pattern).

No se requieren migraciones ni backfills.

---

### 🟢 SEGURO: Añadir un campo `categoria` a las plantillas

1. Crear migración: `$table->string('categoria')->nullable();`
2. Añadir `'categoria'` al `$fillable` del modelo `PostOperacion`
3. Actualizar `store()` y `update()`: `'categoria' => 'nullable|string|max:100'`
4. Actualizar `getByOperacion()` para incluir `categoria` en la respuesta
5. Actualizar la vista para mostrar y filtrar por categoría

---

### Checklist de deploy para cambios en Post-Operaciones

- [ ] ¿Se añade campo a `PostOperacion`? Migración + `$fillable` + actualizar `store()`, `update()`, `getByOperacion()`.
- [ ] ¿Se añade campo a `PostOperacionOperacion`? Migración + `$fillable` + actualizar `bulkUpdate()`, `getByOperacion()`.
- [ ] ¿Se añade un nuevo estado (ej: `En Proceso`)? Actualizar validación en `bulkUpdate()`, la lógica de borrado/creación, y la vista.
- [ ] ¿Se añaden nuevas plantillas en producción? Usar el panel de administración, no `seeder` (para no afectar las asignaciones existentes).

---

*Documentación generada el 27 de Abril de 2026 — ERP Estrategia e Innovación*
