# Módulo Logística — Catálogos Base — Documentación Maestra
> **ERP Estrategia e Innovación** · Versión documental: Abril 2026  
> Audiencia: Administradores de logística, desarrolladores

---

## Tabla de Contenido

1. [Visión General](#1-visión-general)
2. [Arquitectura del Módulo](#2-arquitectura-del-módulo)
3. [Modelo de Datos](#3-modelo-de-datos)
4. [Catálogo de Clientes](#4-catálogo-de-clientes)
5. [Catálogo de Agentes Aduanales](#5-catálogo-de-agentes-aduanales)
6. [Catálogo de Transportes](#6-catálogo-de-transportes)
7. [Vista Unificada de Catálogos](#7-vista-unificada-de-catálogos)
8. [Control de Acceso por Rol en Clientes](#8-control-de-acceso-por-rol-en-clientes)
9. [Importación de Clientes desde CSV/Excel](#9-importación-de-clientes-desde-csvexcel)
10. [Referencia de Rutas](#10-referencia-de-rutas)
11. [Guía de Mantenimiento del Módulo](#11-guía-de-mantenimiento-del-módulo)

---

## 1. Visión General

Los **Catálogos Base** son las entidades de referencia sobre las que se construyen las operaciones logísticas. Tres catálogos principales:

- **Clientes**: Empresas para quienes se gestiona la operación logística. Pueden tener un ejecutivo asignado, correos de notificación y periodicidad de reporte.
- **Agentes Aduanales**: Despachadores de aduana que tramitan los pedimentos.
- **Transportes**: Empresas de transporte, clasificadas por tipo de operación (Aérea, Terrestre, Marítima, Ferrocarril).

Un cuarto controlador (`CatalogosController`) provee la vista unificada que muestra los tres catálogos en una sola pantalla con pestañas.

### Propósito de negocio

| Necesidad | Solución |
|---|---|
| Registrar clientes con ejecutivo asignado | `Cliente` + `ejecutivo_asignado_id` → FK a `Empleado` |
| Importar clientes en masa desde CSV/Excel | `ClienteController::import()` via `ClienteImportService` |
| Evitar clientes duplicados (case-insensitive) | Validación manual `UPPER(cliente) = ?` |
| Asociar transportistas por tipo de ruta | `Transporte.tipo_operacion` in: Aerea/Terrestre/Maritima/Ferrocarril |
| Proteger eliminación de entidades en uso | Verificación de `operaciones()->count() > 0` antes de borrar |
| Ver clientes disponibles para selects | `ClienteController::index()` responde JSON si `wantsJson()` |

---

## 2. Arquitectura del Módulo

```
┌──────────────────────────────────────────────────────────────────┐
│                     CATÁLOGOS BASE                               │
│                                                                  │
│  Middleware: auth + area.logistica                               │
│                                                                  │
│  CatalogosController                                             │
│  └── GET /logistica/catalogos → Vista unificada (3 catálogos)   │
│                                                                  │
│  ClienteController         → /logistica/clientes                 │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │  index()       → Lista + control de acceso por rol        │  │
│  │  store()       → Alta (unique case-insensitive)           │  │
│  │  update()      → Edición                                  │  │
│  │  destroy()     → Elimina si sin operaciones               │  │
│  │  import()      → Importa CSV/Excel via ClienteImportService│  │
│  │  asignarEjecutivo() → Vincula cliente ↔ ejecutivo         │  │
│  │  deleteAll()   → Trunca (solo admin)                      │  │
│  └───────────────────────────────────────────────────────────┘  │
│                                                                  │
│  AgenteAduanalController   → /logistica/agentes                  │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │  store()   → Alta (unique por nombre)                     │  │
│  │  update()  → Edición                                      │  │
│  │  destroy() → Elimina si sin operaciones                   │  │
│  └───────────────────────────────────────────────────────────┘  │
│                                                                  │
│  TransporteController      → /logistica/transportes              │
│  ┌───────────────────────────────────────────────────────────┐  │
│  │  store()     → Alta con tipo_operacion                    │  │
│  │  update()    → Edición                                    │  │
│  │  destroy()   → Elimina si sin operaciones                 │  │
│  │  getByType() → JSON: transportes filtrados por tipo       │  │
│  └───────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────┘
```

---

## 3. Modelo de Datos

### `Cliente` — Tabla `logistica_clientes`

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | `bigint` PK | |
| `cliente` | `varchar(255)` | Nombre del cliente (único, validado case-insensitive) |
| `ejecutivo_asignado_id` | `bigint` FK nullable | FK → `empleados.id` |
| `correos` | `json` nullable | Array JSON de correos de notificación |
| `periodicidad_reporte` | `varchar(50)` nullable | `semanal`, `mensual`, `quincenal`, etc. |
| `created_at` / `updated_at` | `timestamp` | |

**Relaciones**:
```php
ejecutivoAsignado() → BelongsTo Empleado
operaciones()       → HasMany OperacionLogistica (por nombre desnormalizado)
```

### `AgenteAduanal` — Tabla `agentes_aduanales` (o `logistica_agentes_aduanales`)

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | `bigint` PK | |
| `agente_aduanal` | `varchar(255)` | Nombre del agente (único) |
| `created_at` / `updated_at` | `timestamp` | |

**Relaciones**:
```php
operaciones() → HasMany OperacionLogistica
```

### `Transporte` — Tabla `logistica_transportes`

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | `bigint` PK | |
| `transporte` | `varchar(255)` | Nombre de la empresa transportista |
| `tipo_operacion` | `varchar` | `Aerea`, `Terrestre`, `Maritima`, `Ferrocarril` |
| `created_at` / `updated_at` | `timestamp` | |

**Relaciones**:
```php
operaciones() → HasMany OperacionLogistica
```

---

## 4. Catálogo de Clientes

### `ClienteController::index()`

**Ruta**: `GET /logistica/clientes`

Comportamiento dual según el contexto de la petición:

**Si `wantsJson()`** → Retorna JSON con la lista de clientes:
```json
{ "success": true, "clientes": [...] }
```

**Si es una vista** → Retorna la vista unificada de catálogos con paginación de 15.

### Control de visibilidad por rol

| Rol | Clientes visibles |
|---|---|
| Admin | Todos |
| Supervisor de Logística/Sistemas/Dirección | Todos (con datos del equipo también) |
| Ejecutivo normal | Solo los que tienen `ejecutivo_asignado_id = empleado.id` o sin ejecutivo asignado |

```php
// Detección de supervisor logística
$esSupervisorLogistica = $empleadoActual->es_coordinador 
    && (str_contains($area, 'logistica') || str_contains($area, 'sistemas') || ...);

if (!$esAdmin && !$esSupervisorLogistica) {
    $query->where(function ($q) use ($empleadoActual) {
        $q->where('ejecutivo_asignado_id', $empleadoActual->id)
            ->orWhereNull('ejecutivo_asignado_id');
    });
}
```

### `ClienteController::store()`

**Ruta**: `POST /logistica/clientes`

Validación con unicidad case-insensitive:
```php
$nombreCliente = strtoupper($request->cliente);

// Validación manual (Laravel unique no es case-insensitive por defecto)
if (Cliente::whereRaw('UPPER(cliente) = ?', [$nombreCliente])->exists()) {
    return response()->json(['success' => false, 'message' => 'El cliente ya existe.'], 422);
}
```

Los correos se almacenan como JSON:
```php
$correosArray = $request->correos ? json_decode($request->correos, true) : null;
```

Si no se especifica ejecutivo, se asigna automáticamente al ejecutivo que está creando el cliente.

### `ClienteController::asignarEjecutivo()`

**Ruta**: `POST /logistica/clientes/asignar-ejecutivo`

Reasigna el `ejecutivo_asignado_id` de un cliente a un empleado diferente. Útil para redistribuir carga entre ejecutivos.

### `ClienteController::deleteAll()`

**Ruta**: `DELETE /logistica/clientes/all/delete` (middleware: `admin`)

Trunca toda la tabla de clientes. Solo accesible para administradores.

---

## 5. Catálogo de Agentes Aduanales

### `AgenteAduanalController`

Controlador simple con solo 3 métodos. No tiene `index` propio (se muestra en la vista unificada de catálogos).

**Alta** (`POST /logistica/agentes`):
```php
$request->validate(['agente_aduanal' => 'required|unique:agentes_aduanales']);
$agente = AgenteAduanal::create($request->only('agente_aduanal'));
```

**Eliminación con protección**:
```php
if ($agente->operaciones()->count() > 0) {
    return response()->json(['success' => false, 'message' => 'Tiene operaciones asociadas'], 400);
}
```

---

## 6. Catálogo de Transportes

### `TransporteController`

Tres métodos: `store`, `update`, `destroy`, más `getByType()`.

**Alta** (`POST /logistica/transportes`):
```php
$request->validate([
    'transporte'     => 'required|string|max:255',
    'tipo_operacion' => 'required|in:Aerea,Terrestre,Maritima,Ferrocarril'
]);
```

**Consulta por tipo** (`GET /logistica/transportes/por-tipo?tipo=Aerea`):
```php
$transportes = Transporte::where('tipo_operacion', $request->tipo)->orderBy('transporte')->get();
return response()->json($transportes);
```

Usado por el formulario de alta de operaciones para mostrar solo transportistas del tipo seleccionado.

**Eliminación con protección** — idéntica a `AgenteAduanal`.

---

## 7. Vista Unificada de Catálogos

**Ruta**: `GET /logistica/catalogos`  
**Controller**: `CatalogosController::index()`

Carga los tres catálogos en una sola vista con paginación de 15 por catálogo:

```php
return view('Logistica.catalogos', [
    'clientes'          => Cliente::with('ejecutivoAsignado')->orderBy('cliente')->paginate(15),
    'agentesAduanales'  => AgenteAduanal::orderBy('agente_aduanal')->paginate(15),
    'transportes'       => Transporte::orderBy('transporte')->paginate(15),
    'todosEjecutivos'   => ($esAdmin || $esSupervisorLogistica) ? Empleado::where('es_activo', true)->get() : [],
    'esAdmin'           => $esAdmin,
    'esSupervisorLogistica' => $esSupervisorLogistica,
    'equipo'            => $equipo
]);
```

---

## 8. Control de Acceso por Rol en Clientes

El módulo implementa tres niveles de acceso:

```
ADMIN
  → Ve todos los clientes
  → Puede deleteAll()
  
SUPERVISOR (es_coordinador = true, área Logística/Sistemas/Dirección)
  → Ve todos los clientes
  → Ve su equipo de subordinados para asignarlos
  → Puede asignar ejecutivos a clientes
  
EJECUTIVO normal
  → Solo ve clientes donde ejecutivo_asignado_id = su empleado.id
  → También ve clientes sin ejecutivo asignado (ejecutivo_asignado_id = null)
```

---

## 9. Importación de Clientes desde CSV/Excel

**Ruta**: `POST /logistica/clientes/importar`  
**Service**: `app/Services/ClienteImportService.php`

El proceso de importación:
1. Usuario sube archivo CSV o Excel desde la vista de catálogos
2. `ClienteController::import()` delega al `ClienteImportService`
3. El service itera filas y usa `updateOrCreate` para evitar duplicados
4. Retorna un reporte de: registros creados, actualizados, errores

**Formato esperado del CSV**:

| Columna | Obligatorio | Descripción |
|---|---|---|
| `cliente` | Sí | Nombre del cliente |
| `ejecutivo` | No | Nombre o correo del ejecutivo a asignar |
| `correos` | No | Correos separados por coma |
| `periodicidad_reporte` | No | `semanal`, `mensual`, etc. |

---

## 10. Referencia de Rutas

**Middleware**: `auth`, `area.logistica`  
**Prefijo**: `/logistica`  
**Nombre base**: `logistica.`

### Catálogos — Vista unificada

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `GET` | `/logistica/catalogos` | `logistica.catalogos` | Vista unificada de todos los catálogos |

### Clientes — `/logistica/clientes`

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `GET` | `/clientes` | `logistica.clientes.index` | Listado (JSON o vista) |
| `POST` | `/clientes` | `logistica.clientes.store` | Crear cliente |
| `PUT` | `/clientes/{id}` | `logistica.clientes.update` | Editar |
| `DELETE` | `/clientes/{id}` | `logistica.clientes.destroy` | Eliminar (protegido) |
| `POST` | `/clientes/importar` | `logistica.clientes.import` | Importar CSV/Excel |
| `POST` | `/clientes/asignar-ejecutivo` | `logistica.clientes.asignar-ejecutivo` | Reasignar ejecutivo |
| `DELETE` | `/clientes/all/delete` | `logistica.clientes.delete-all` | Truncar (solo admin) |

### Agentes Aduanales — `/logistica/agentes`

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `POST` | `/agentes` | `logistica.agentes.store` | Crear agente |
| `PUT` | `/agentes/{id}` | `logistica.agentes.update` | Editar |
| `DELETE` | `/agentes/{id}` | `logistica.agentes.destroy` | Eliminar (protegido) |

### Transportes — `/logistica/transportes`

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `POST` | `/transportes` | `logistica.transportes.store` | Crear transporte |
| `PUT` | `/transportes/{id}` | `logistica.transportes.update` | Editar |
| `DELETE` | `/transportes/{id}` | `logistica.transportes.destroy` | Eliminar (protegido) |
| `GET` | `/transportes/por-tipo` | `logistica.transportes.by-type` | JSON filtrado por tipo |

---

## 11. Guía de Mantenimiento del Módulo

---

### 🔴 CRÍTICO: Clientes desnormalizados en `operaciones_logisticas`

El campo `cliente` en `OperacionLogistica` almacena el **nombre como string**, no el ID. Esto significa:

- Si un cliente cambia de nombre en el catálogo, las operaciones históricas no se actualizan.
- El filtro por cliente en la Matriz usa `Cliente::find($id)->cliente` y luego filtra por nombre. Si hay un cambio, el filtro puede no encontrar operaciones antiguas.

**Recomendación a largo plazo**: Añadir `cliente_id` FK a `operaciones_logisticas` y usar el campo de texto solo para display.

---

### 🟡 IMPORTANTE: La protección anti-eliminación es a nivel de aplicación, no de BD

La verificación `$agente->operaciones()->count() > 0` se hace en PHP. No hay `FOREIGN KEY` con `ON DELETE RESTRICT` en la BD. Si alguien elimina directamente en BD, las operaciones quedan sin agente/transporte válido.

**Solución**: Añadir FK con restricciones en la migración:
```php
$table->foreignId('agente_aduanal_id')->constrained('agentes_aduanales')->restrictOnDelete();
```
(Requiere que `OperacionLogistica` use ID en lugar de string desnormalizado)

---

### 🟡 IMPORTANTE: Los correos de clientes se almacenan como JSON sin validación

El campo `correos` en `Cliente` acepta cualquier JSON. No se valida que sean emails válidos al guardar.

**Fix en `store()` y `update()`**:
```php
'correos.*' => 'email', // Validar cada email del array
```

---

### 🟢 SEGURO: Añadir un nuevo tipo de operación para transportes

Actualmente: `in:Aerea,Terrestre,Maritima,Ferrocarril`. Para añadir `Multimodal`:

1. `TransporteController::store()` y `update()`: añadir `Multimodal` al `in:...`
2. `OperacionLogisticaController::create()`: añadir a `tipos_operacion`
3. `StoreOperacionRequest`: actualizar la validación de `tipo_operacion`

---

### 🟢 SEGURO: Añadir campo al catálogo de clientes (ej: RFC)

1. Crear migración: `$table->string('rfc', 13)->nullable();`
2. Añadir `'rfc'` al `$fillable` del modelo `Cliente`
3. Añadir validación en `store()` y `update()`: `'rfc' => 'nullable|string|max:13'`
4. Actualizar la vista y la importación CSV

---

### Checklist de deploy para cambios en Catálogos Base

- [ ] ¿Se añade campo a `Cliente`? Migración + `$fillable` + validación + actualizar `ClienteImportService` + vista.
- [ ] ¿Se añade tipo de operación? Actualizar `TransporteController`, `StoreOperacionRequest`, y vista del formulario de alta de operaciones.
- [ ] ¿Se cambia el formato de importación CSV? Actualizar `ClienteImportService` y la documentación del formato.
- [ ] ¿Se añade un nuevo catálogo? Crear modelo, migración, controlador, ruta, y añadirlo a `CatalogosController::index()`.

---

*Documentación generada el 27 de Abril de 2026 — ERP Estrategia e Innovación*
