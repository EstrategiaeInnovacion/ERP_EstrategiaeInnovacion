# Módulo Logística — Gestión de Equipo y Correos CC — Documentación Maestra
> **ERP Estrategia e Innovación** · Versión documental: Abril 2026  
> Audiencia: Coordinadores de logística, administradores, desarrolladores

---

## Tabla de Contenido

1. [Visión General](#1-visión-general)
2. [Arquitectura del Módulo](#2-arquitectura-del-módulo)
3. [Modelo de Datos](#3-modelo-de-datos)
4. [Gestión de Equipo (`EquipoController`)](#4-gestión-de-equipo-equipocontroller)
5. [Correos CC (`LogisticaCorreoCCController`)](#5-correos-cc-logisticacorreocccontroller)
6. [Sistema de Permisos: `checkPermission()`](#6-sistema-de-permisos-checkpermission)
7. [Tipos de Correo CC](#7-tipos-de-correo-cc)
8. [Integración con Envío de Reportes](#8-integración-con-envío-de-reportes)
9. [Referencia de Rutas](#9-referencia-de-rutas)
10. [Guía de Mantenimiento del Módulo](#10-guía-de-mantenimiento-del-módulo)

---

## 1. Visión General

Este módulo agrupa dos funcionalidades administrativas:

**Equipo de Logística**: Permite a un coordinador (supervisor) asignar empleados a su equipo de logística estableciendo una relación `supervisor_id`. Esto define quién supervisa a quién y qué ejecutivos forman el equipo visible para un coordinador.

**Correos CC**: Catálogo de correos electrónicos que reciben copias de los reportes automáticos. Organizados por tipo (administrador, supervisor, notificación) con activación/desactivación individual.

### Propósito de negocio

| Necesidad | Solución |
|---|---|
| Definir el equipo de un coordinador de logística | `EquipoController::store()` → `supervisor_id` en `empleados` |
| Remover a un empleado del equipo | `EquipoController::destroy()` → `supervisor_id = null` |
| Buscar empleado por correo o ID para añadirlo | Búsqueda dual en `store()` |
| Mantener lista de correos para CC en reportes | `LogisticaCorreoCC` con tipos y estado activo |
| Exponer correos CC via API | `LogisticaCorreoCCController::api()` → JSON público interno |

---

## 2. Arquitectura del Módulo

```
┌─────────────────────────────────────────────────────────────────────┐
│                   EQUIPO + CORREOS CC                               │
│                                                                     │
│  Middleware: auth + area.logistica                                  │
│                                                                     │
│  EquipoController → /logistica/equipo                               │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │  index()    → JSON/Vista del equipo del coordinador actual    │  │
│  │  store()    → Añade empleado al equipo (por correo o ID)      │  │
│  │  destroy()  → Remueve empleado del equipo (null supervisor)   │  │
│  └───────────────────────────────────────────────────────────────┘  │
│                                                                     │
│  LogisticaCorreoCCController → /logistica/correos-cc               │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │  index()   → Vista con lista de correos CC                    │  │
│  │  store()   → Alta de correo CC (Validator::make, no Request)  │  │
│  │  update()  → Editar correo CC                                 │  │
│  │  destroy() → Eliminar correo CC                               │  │
│  │  toggle()  → Activar/desactivar                               │  │
│  │  api()     → JSON: correos activos (endpoint interno)         │  │
│  └───────────────────────────────────────────────────────────────┘  │
│                                                                     │
│  Modelos                                                            │
│  ──────                                                              │
│  Empleado          → Campo supervisor_id para relación de equipo    │
│  LogisticaCorreoCC → Catálogo de correos con tipo + activo          │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 3. Modelo de Datos

### `Empleado` (campo relevante) — Tabla `empleados`

El módulo de equipo no tiene tabla propia. Usa el campo `supervisor_id` del modelo `Empleado` existente:

| Campo | Tipo | Descripción |
|---|---|---|
| `supervisor_id` | `bigint` FK nullable | Apunta al `Empleado` supervisor. `null` = sin supervisor |
| `es_coordinador` | `boolean` | Indica si el empleado es coordinador/supervisor |
| `correo` | `varchar(255)` | Usado para búsqueda al añadir al equipo |
| `id_empleado` | `varchar(50)` nullable | ID de nómina, alternativa de búsqueda |
| `posicion` | `varchar(255)` | Cargo del empleado |
| `area` | `varchar(255)` | Área del empleado |

**Relaciones usadas por el módulo**:
```php
// En Empleado:
supervisor()     → BelongsTo Empleado (self-referential)
subordinados()   → HasMany Empleado ('supervisor_id')
```

### `LogisticaCorreoCC` — Tabla `logistica_correos_cc`

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | `bigint` PK | |
| `nombre` | `varchar(255)` | Nombre descriptivo del destinatario |
| `correo` | `varchar(255)` | Dirección de correo electrónico (único) |
| `tipo` | `varchar(50)` | Tipo: `administrador`, `supervisor`, `notificacion` |
| `activo` | `boolean` default true | Si se incluye en los envíos |
| `created_at` / `updated_at` | `timestamp` | |

**Scopes**:
```php
scopeActivos($query) → WHERE activo = 1
```

---

## 4. Gestión de Equipo (`EquipoController`)

**Archivo**: `app/Http/Controllers/Logistica/EquipoController.php`

---

### `index(): JsonResponse|View`

**Ruta**: `GET /logistica/equipo`

Retorna el equipo del coordinador actual (los empleados con `supervisor_id = $coordinador->id`):

```php
$equipo = Empleado::where('supervisor_id', $coordinadorActual->id)
    ->select('id', 'nombre', 'correo', 'posicion', 'area', 'es_activo')
    ->get();
```

---

### `store(Request $request): JsonResponse`

**Ruta**: `POST /logistica/equipo`

Verifica permisos (`checkPermission()`), luego busca al empleado por correo **o** por ID de nómina:

```php
// Búsqueda dual: correo o ID de nómina
$empleado = Empleado::where('correo', $request->correo)
    ->orWhere('id_empleado', $request->correo) // Reutiliza el campo
    ->first();

if (!$empleado) {
    return response()->json(['success' => false, 'message' => 'Empleado no encontrado'], 404);
}

// Asignar supervisor
$empleado->supervisor_id = $coordinadorActual->id;
$empleado->save();
```

---

### `destroy($id): JsonResponse`

**Ruta**: `DELETE /logistica/equipo/{id}`

Remueve al empleado del equipo **sin eliminar al empleado**. Solo limpia el `supervisor_id`:

```php
$empleado = Empleado::findOrFail($id);
$empleado->supervisor_id = null;
$empleado->save();
```

---

## 5. Correos CC (`LogisticaCorreoCCController`)

**Archivo**: `app/Http/Controllers/Logistica/LogisticaCorreoCCController.php`

---

### `index(): View`

**Ruta**: `GET /logistica/correos-cc`

Vista con la lista paginada de correos CC, organizados por tipo.

---

### `store(Request $request): JsonResponse`

**Ruta**: `POST /logistica/correos-cc`

Usa `Validator::make()` en lugar de Form Request. Validación:

```php
$validator = Validator::make($request->all(), [
    'nombre'  => 'required|string|max:255',
    'correo'  => 'required|email|unique:logistica_correos_cc,correo',
    'tipo'    => 'required|in:administrador,supervisor,notificacion',
    'activo'  => 'boolean'
]);
```

Si el correo ya existe: retorna 422 con mensaje descriptivo.

---

### `update(Request $request, $id): JsonResponse`

**Ruta**: `PUT /logistica/correos-cc/{id}`

Actualiza nombre, tipo y estado activo. El correo no se puede cambiar (es el identificador único).

---

### `destroy($id): JsonResponse`

**Ruta**: `DELETE /logistica/correos-cc/{id}`

Eliminación directa sin verificaciones adicionales.

---

### `toggle($id): JsonResponse`

**Ruta**: `PUT /logistica/correos-cc/{id}/toggle`

Invierte el estado `activo`:

```php
$correo->activo = !$correo->activo;
$correo->save();
return response()->json([
    'success' => true,
    'activo'  => $correo->activo,
    'message' => $correo->activo ? 'Correo activado' : 'Correo desactivado'
]);
```

---

### `api(): JsonResponse`

**Ruta**: `GET /logistica/correos-cc/api`

Endpoint interno que retorna todos los correos activos. Usado por otros módulos para obtener la lista de destinatarios:

```php
$correos = LogisticaCorreoCC::activos()
    ->select('id', 'nombre', 'correo', 'tipo')
    ->orderBy('tipo')
    ->orderBy('nombre')
    ->get();

return response()->json(['correos' => $correos]);
```

---

## 6. Sistema de Permisos: `checkPermission()`

El `EquipoController` verifica permisos antes de cualquier operación:

```php
private function checkPermission(): ?JsonResponse
{
    $user = auth()->user();
    
    // Admins siempre tienen permiso
    if ($user->hasRole('admin')) {
        return null; // null = tiene permiso, continuar
    }
    
    // Buscar empleado vinculado
    $empleado = Empleado::where('correo', $user->email)->first();
    
    if (!$empleado) {
        return response()->json(['success' => false, 'message' => 'Sin acceso'], 403);
    }
    
    // Debe ser coordinador en área de logística/sistemas/dirección
    $esCoordinador = $empleado->es_coordinador;
    $areaValida = str_contains(strtolower($empleado->area ?? ''), 'logistic') 
        || str_contains(strtolower($empleado->area ?? ''), 'sistemas')
        || str_contains(strtolower($empleado->area ?? ''), 'direcci');
    $posicionValida = str_contains(strtolower($empleado->posicion ?? ''), 'logistic')
        || str_contains(strtolower($empleado->posicion ?? ''), 'coordinador')
        || str_contains(strtolower($empleado->posicion ?? ''), 'director');
    
    if (!$esCoordinador || (!$areaValida && !$posicionValida)) {
        return response()->json(['success' => false, 'message' => 'No tiene permisos de coordinador'], 403);
    }
    
    return null; // Tiene permiso
}
```

**Patrón de uso**:
```php
public function store(Request $request): JsonResponse
{
    if ($error = $this->checkPermission()) {
        return $error; // Early return con 403
    }
    // ... lógica del método
}
```

---

## 7. Tipos de Correo CC

| Tipo | Propósito |
|---|---|
| `administrador` | Gerentes o directores que reciben todos los reportes |
| `supervisor` | Coordinadores de logística que reciben reportes de su equipo |
| `notificacion` | Correos de servicio (CRM, ERP externo) que procesan reportes automáticamente |

El tipo no tiene efecto automático en el código actual — es solo un organizador visual. Sin embargo, `ReporteController::enviarCorreo()` puede filtrar por tipo para enviar a solo ciertos destinatarios:

```php
// Ejemplo de filtrado por tipo al enviar
$destinatarios = LogisticaCorreoCC::activos()
    ->where('tipo', $request->tipo_destinatario ?? 'administrador')
    ->pluck('correo');
```

---

## 8. Integración con Envío de Reportes

Los correos CC son consumidos principalmente por `ReporteController::enviarCorreo()`:

```
FLUJO DE ENVÍO DE REPORTE POR CORREO
─────────────────────────────────────
1. Usuario hace POST /logistica/reportes/enviar-correo
2. ReporteController::enviarCorreo() obtiene correos de LogisticaCorreoCC::activos()
3. Genera el Excel con PhpSpreadsheet
4. Envía Mail a todos los destinatarios activos
5. Retorna JSON con cantidad de destinatarios

ENDPOINT api() COMO FUENTE DE DATOS
─────────────────────────────────────
GET /logistica/correos-cc/api → JSON con todos los correos activos
↑ Usado por el frontend para mostrar la lista de destinatarios antes del envío
```

---

## 9. Referencia de Rutas

**Middleware**: `auth`, `area.logistica`  
**Prefijo**: `/logistica`  
**Nombre base**: `logistica.`

### Equipo — `/logistica/equipo`

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `GET` | `/logistica/equipo` | `logistica.equipo.index` | Lista el equipo del coordinador |
| `POST` | `/logistica/equipo` | `logistica.equipo.store` | Añadir empleado al equipo |
| `DELETE` | `/logistica/equipo/{id}` | `logistica.equipo.destroy` | Remover empleado del equipo |

### Correos CC — `/logistica/correos-cc`

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `GET` | `/logistica/correos-cc` | `logistica.correos-cc.index` | Vista con lista de correos |
| `POST` | `/logistica/correos-cc` | `logistica.correos-cc.store` | Crear correo CC |
| `PUT` | `/logistica/correos-cc/{id}` | `logistica.correos-cc.update` | Editar correo CC |
| `DELETE` | `/logistica/correos-cc/{id}` | `logistica.correos-cc.destroy` | Eliminar correo CC |
| `PUT` | `/logistica/correos-cc/{id}/toggle` | `logistica.correos-cc.toggle` | Activar/desactivar |
| `GET` | `/logistica/correos-cc/api` | `logistica.correos-cc.api` | JSON: correos activos |

---

## 10. Guía de Mantenimiento del Módulo

---

### 🔴 CRÍTICO: `destroy()` del equipo limpia `supervisor_id` sin verificar subordinados

Al remover a un empleado del equipo, si ese empleado era supervisor de otros, sus `subordinados` quedan huérfanos (`supervisor_id` de los subordinados sigue apuntando al ID del supervisor que ahora no tiene supervisor). No se produce un error inmediato pero la jerarquía queda inconsistente.

**Verificación previa recomendada**:
```php
// Antes de limpiar supervisor_id
$tieneSubordinados = Empleado::where('supervisor_id', $empleado->id)->count();
if ($tieneSubordinados > 0) {
    return response()->json([
        'success' => false,
        'message' => "No se puede remover: tiene {$tieneSubordinados} subordinados asignados"
    ], 400);
}
```

---

### 🔴 CRÍTICO: `checkPermission()` evalúa rol con búsqueda por `área` y `posición` en texto libre

La detección de "coordinador de logística" se hace con `str_contains` en los campos `area` y `posicion`. Si un empleado tiene typo en su área (ej: "Logistiica") o la nomenclatura cambia, perdería acceso o lo ganaría incorrectamente.

**Solución**: Añadir un flag explícito `es_supervisor_logistica` en `empleados` o usar roles del sistema (Spatie Permissions) en lugar de texto libre.

---

### 🟡 IMPORTANTE: `store()` en Correos CC usa `Validator::make()` sin Form Request

La inconsistencia en el estilo de validación dificulta el mantenimiento. El resto de controladores del módulo usan Form Requests.

**Refactorización recomendada**:
```
app/Http/Requests/Logistica/StoreLogisticaCorreoCCRequest.php
```

---

### 🟡 IMPORTANTE: El campo `tipo` en Correos CC es solo visual, no hay lógica diferenciada en el código

Actualmente, al enviar reportes se puede filtrar por tipo, pero la lógica de "administradores reciben todo, supervisores solo su equipo" no está implementada.

**Para implementar**: En `ReporteController::enviarCorreo()`, aplicar filtros por tipo y por rol del equipo.

---

### 🟢 SEGURO: Añadir un nuevo tipo de correo CC (ej: `cliente_vip`)

1. En `LogisticaCorreoCCController::store()`: añadir `cliente_vip` al `in:administrador,supervisor,notificacion,cliente_vip`
2. En `api()`: el nuevo tipo se incluye automáticamente
3. Opcional: Añadir lógica específica en `ReporteController::enviarCorreo()` para este tipo

---

### 🟢 SEGURO: Añadir campo de nombre/apellido al equipo mostrado

El `index()` retorna campos seleccionados del empleado. Para añadir más campos:
```php
->select('id', 'nombre', 'correo', 'posicion', 'area', 'es_activo', 'telefono') // Añadir campo
```

No requiere migración (el campo ya existe en `empleados`).

---

### Checklist de deploy para cambios en Equipo y Correos CC

- [ ] ¿Se añade un nuevo tipo de correo CC? Actualizar validación en `store()` y `update()`.
- [ ] ¿Se añade campo a `LogisticaCorreoCC`? Migración + `$fillable` + validación + respuesta de `api()`.
- [ ] ¿Se cambia la lógica de `checkPermission()`? Asegurarse de que el cambio no rompe el acceso de coordinadores existentes. Hacer prueba con un usuario coordinador de prueba.
- [ ] ¿Se añade lógica de filtro por tipo en el envío de correos? Actualizar `ReporteController::enviarCorreo()` y la vista del botón de envío para elegir el tipo.
- [ ] ¿Se migra a roles de Spatie Permissions para `checkPermission()`? Crear el rol `coordinador_logistica`, asignarlo a los coordinadores actuales, y actualizar `checkPermission()`.

---

*Documentación generada el 27 de Abril de 2026 — ERP Estrategia e Innovación*
