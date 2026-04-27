# Módulo Logística — Matriz de Seguimiento — Documentación Maestra
> **ERP Estrategia e Innovación** · Versión documental: Abril 2026  
> Audiencia: Ejecutivos de cuenta, administradores de logística, desarrolladores

---

## Tabla de Contenido

1. [Visión General](#1-visión-general)
2. [Arquitectura del Módulo](#2-arquitectura-del-módulo)
3. [Modelo de Datos](#3-modelo-de-datos)
4. [Sistema de Status (Manual vs Calculado)](#4-sistema-de-status-manual-vs-calculado)
5. [Referencia de Métodos — `OperacionLogisticaController`](#5-referencia-de-métodos--operacionlogisticacontroller)
6. [Filtros de la Matriz (Spatie Query Builder)](#6-filtros-de-la-matriz-spatie-query-builder)
7. [Historial SGM (Bitácora de Cambios)](#7-historial-sgm-bitácora-de-cambios)
8. [Consulta Pública para Clientes](#8-consulta-pública-para-clientes)
9. [Control de Acceso por Rol](#9-control-de-acceso-por-rol)
10. [Referencia de Rutas](#10-referencia-de-rutas)
11. [Historial de Migraciones](#11-historial-de-migraciones)
12. [Guía de Mantenimiento del Módulo](#12-guía-de-mantenimiento-del-módulo)

---

## 1. Visión General

La **Matriz de Seguimiento** es el núcleo operativo del módulo de Logística. Centraliza el ciclo de vida completo de las operaciones de importación y exportación: desde el alta del expediente hasta la marcación como completado (Done).

### Propósito de negocio

| Necesidad | Solución |
|---|---|
| Visibilidad centralizada de todas las operaciones | Matriz paginada con filtros avanzados |
| Alertas de retraso automáticas | `status_calculado` basado en días transcurridos vs target |
| Seguimiento por ejecutivo de cuenta | Filtro de visibilidad por rol (Admin ve todo; ejecutivo solo lo suyo) |
| Historial de cambios de estado en el tiempo | `HistoricoMatrizSgm` — bitácora SGM |
| Consulta de estado para clientes externos | Endpoints públicos sin autenticación |
| Campos adicionales sin migración BD | Sistema EAV de campos personalizados |

---

## 2. Arquitectura del Módulo

```
┌──────────────────────────────────────────────────────────────────────┐
│                    MATRIZ DE SEGUIMIENTO                             │
│                                                                      │
│  Rutas públicas (sin middleware)                                      │
│  ─────────────────────────────                                        │
│  GET  /logistica/consulta-publica                                    │
│  GET  /logistica/consulta-publica/buscar                             │
│                                                                      │
│  Rutas protegidas: auth + area.logistica                             │
│  ───────────────────────────────────                                  │
│  OperacionLogisticaController                                        │
│  ┌──────────────────────────────────────────────────────────────┐   │
│  │  index()            → Matriz paginada con filtros Spatie     │   │
│  │  create()           → JSON: catálogos para formulario alta   │   │
│  │  store()            → Crea operación + historial SGM inicial │   │
│  │  show()             → Detalle de una operación               │   │
│  │  update()           → Actualiza campos + historial SGM       │   │
│  │  destroy()          → Elimina operación                      │   │
│  │  updateStatus()     → Marca como Done/Out of Metric (AJAX)   │   │
│  │  recalcularStatus() → Recálculo masivo de status_calculado   │   │
│  │  obtenerHistorial() → JSON: bitácora SGM de una operación    │   │
│  │  consultaPublica()  → Vista para clientes externos           │   │
│  │  buscarOperacionPublica() → JSON con estado y historial SGM  │   │
│  └──────────────────────────────────────────────────────────────┘   │
│                                                                      │
│  Dependencias                                                        │
│  ────────────                                                         │
│  Spatie\QueryBuilder  → Filtros avanzados en index()                 │
│  StoreOperacionRequest / UpdateOperacionRequest → Form Requests       │
│  OperacionLogistica   → Modelo principal                              │
│  HistoricoMatrizSgm   → Bitácora de cambios                          │
└──────────────────────────────────────────────────────────────────────┘
```

---

## 3. Modelo de Datos

### `OperacionLogistica` — Tabla `operaciones_logisticas`

| Campo | Descripción |
|---|---|
| `id` | PK |
| `operacion` | Número/código de la operación |
| `cliente` | Nombre del cliente (string desnormalizado) |
| `ejecutivo` | Nombre del ejecutivo asignado (string desnormalizado) |
| `no_pedimento` | Número de pedimento aduanal |
| `clave` | Clave del pedimento |
| `referencia_cliente` | Referencia interna del cliente |
| `tipo_operacion` | `Aerea`, `Terrestre`, `Maritima`, `Ferrocarril` |
| `tipo` | `Exportacion`, `Importacion` |
| `status_manual` | Status asignado manualmente: `In Process`, `Done`, `Out of Metric` |
| `status_calculado` | Status derivado del algoritmo de días: `In Process`, `Done`, `Out of Metric` |
| `target` | Días objetivo para completar la operación |
| `fecha_arribo` | Fecha de arribo esperada |
| `fecha_embarque` | Fecha de embarque |
| `dias_transcurridos_calculados` | Accessor calculado desde fecha_arribo |
| `created_at` / `updated_at` | Timestamps estándar |

**Relaciones**:
```php
ejecutivo()                     → BelongsTo Empleado
postOperaciones()               → HasMany PostOperacion
valoresCamposPersonalizados()   → HasMany ValorCampoPersonalizado
```

### `HistoricoMatrizSgm` — Tabla `historico_matriz_sgm`

Bitácora inmutable de eventos. Cada cambio de estado o fecha crítica genera un registro.

| Campo | Descripción |
|---|---|
| `id` | PK |
| `operacion_logistica_id` | FK → `operaciones_logisticas.id` |
| `status` | Status vigente en el momento del registro |
| `fecha_registro` | Timestamp del evento |
| `observacion` | Descripción del cambio |

### `OperacionComentario`

Comentarios libres adicionales al historial SGM, ingresados manualmente por ejecutivos.

---

## 4. Sistema de Status (Manual vs Calculado)

Cada operación tiene dos campos de status independientes:

| Campo | Quién lo establece | Cuándo |
|---|---|---|
| `status_manual` | El ejecutivo, vía UI | Al crear o con `updateStatus()` |
| `status_calculado` | El algoritmo, vía `recalcularStatus()` | Al recalcular o automáticamente |

**Status disponibles**:
- `In Process` — Operación activa dentro del target de días
- `Done` / `Completado` — Operación finalizada
- `Out of Metric` — Superó el target de días sin completarse

### Lógica del status calculado

```php
// OperacionLogistica::calcularStatusPorDias()
$diasTranscurridos = $this->dias_transcurridos_calculados;
$target = $this->target ?? 30;
$retraso = max(0, $diasTranscurridos - $target);

if ($this->status_manual === 'Done') → status_calculado = 'Done'
elseif ($retraso > 0)               → status_calculado = 'Out of Metric'
else                                 → status_calculado = 'In Process'
```

### Vista principal: ocultar completadas por defecto

```php
// index(): Sin ?ver_completadas=true, se ocultan las Done/Completado
->when(!$verCompletadas, function ($q) {
    $q->where(function($sub) {
        $sub->whereNull('status_manual')
            ->orWhereNotIn('status_manual', ['Done', 'Completado']);
    });
})
```

Para verlas: `GET /logistica/matriz-seguimiento?ver_completadas=true`

---

## 5. Referencia de Métodos — `OperacionLogisticaController`

**Archivo**: `app/Http/Controllers/Logistica/OperacionLogisticaController.php`

---

### `index(Request $request): View`

**Ruta**: `GET /logistica/matriz-seguimiento`

Renderiza la Matriz de Seguimiento con paginación de 10 registros.

Variables enviadas a la vista:

| Variable | Descripción |
|---|---|
| `$operaciones` | Paginator de `OperacionLogistica` con relaciones eager-loaded |
| `$empleadoActual` | Empleado autenticado (puede ser `null` si no hay registro en tabla empleados) |
| `$esAdmin` | Boolean — si tiene rol `admin` |
| `$modoPreview` | Boolean — Admin viendo como otro ejecutivo |
| `$empleadoPreview` | Empleado simulado (solo cuando `$modoPreview = true`) |
| `$verCompletadas` | Boolean desde `?ver_completadas=true` |
| `$conteoCompletadas` | Número de operaciones Done del ejecutivo actual |
| `$ejecutivos` | Lista de empleados (para filtro de ejecutivo) |
| Datos de vista | Clientes, catálogos, campos personalizados para columnas |

**Preview Mode** (solo Admin): `?preview_as={empleado_id}` simula la vista de ese ejecutivo.

---

### `create(): JsonResponse`

**Ruta**: `GET /logistica/operaciones/create`

Retorna JSON con los catálogos necesarios para poblar el formulario de alta:

```json
{
  "clientes": [{"id": 1, "cliente": "Importadora XYZ"}],
  "agentesAduanales": [{"id": 1, "agente_aduanal": "Agente 1"}],
  "ejecutivos": [{"id": 5, "nombre": "Juan Pérez"}],
  "tipos_operacion": ["Aerea", "Terrestre", "Maritima", "Ferrocarril"],
  "operaciones": ["Exportacion", "Importacion"],
  "status_options": ["In Process", "Done", "Out of Metric"]
}
```

---

### `store(StoreOperacionRequest $request): JsonResponse`

**Ruta**: `POST /logistica/operaciones`

Crea una operación nueva. Flujo:
1. Validar con `StoreOperacionRequest`
2. Si `status_manual` viene vacío → asignar `'In Process'`
3. `OperacionLogistica::create($data)`
4. `generarHistorialInicial($operacion)` → primer registro SGM
5. `guardarCamposPersonalizados($request, $operacion->id)` → EAV

Retorna: `{ "success": true, "message": "Operación creada exitosamente. Folio: #N" }`

---

### `updateStatus(Request $request, $id): JsonResponse`

**Ruta**: `PUT /logistica/operaciones/{id}/status`

Actualización asíncrona del status manual desde la fila de la matriz.

```php
$operacion->status_manual = $request->status; // 'Done', 'In Process', 'Out of Metric'
$operacion->save();
// Registra el cambio en HistoricoMatrizSgm
```

---

### `recalcularStatus(): JsonResponse`

**Ruta**: `POST /logistica/operaciones/recalcular-status`

Dispara el recálculo masivo de `status_calculado` para todas las operaciones activas, basándose en la fecha actual vs el target de días.

---

### `obtenerHistorial($id): JsonResponse`

**Ruta**: `GET /logistica/operaciones/{id}/historial`

Retorna la bitácora SGM de una operación:

```json
{
  "historial": [
    { "status": "In Process", "fecha_registro": "2026-02-15 10:30:00", "observacion": "Operación creada" },
    { "status": "Out of Metric", "fecha_registro": "2026-03-20 09:00:00", "observacion": "Recálculo automático" }
  ]
}
```

---

### `consultaPublica(): View`

**Ruta**: `GET /logistica/consulta-publica` (sin autenticación)

Vista de consulta para clientes externos. No requiere `auth` ni `area.logistica`.

---

### `buscarOperacionPublica(Request $request): JsonResponse`

**Ruta**: `GET /logistica/consulta-publica/buscar` (sin autenticación)

Busca por número de pedimento o factura y retorna el estado y el historial SGM de la operación.

> **Nota de seguridad**: Este endpoint es público. Solo expone datos que el cliente necesita ver (status, fechas). No expone datos internos como ejecutivos o costos.

---

## 6. Filtros de la Matriz (Spatie Query Builder)

El método `index()` usa `spatie/laravel-query-builder` para filtros URL:

| Parámetro GET | Tipo | Comportamiento |
|---|---|---|
| `filter[cliente]` | ID de cliente | Busca el nombre del cliente por ID, luego filtra por nombre |
| `filter[ejecutivo]` | Texto parcial | `LIKE '%ejecutivo%'` |
| `filter[status]` | Texto exacto | Busca en `status_manual` OR `status_calculado`. `todos` = sin filtro |
| `filter[fecha_creacion_desde]` | Fecha `Y-m-d` | `WHERE created_at >= fecha` |
| `filter[fecha_creacion_hasta]` | Fecha `Y-m-d` | `WHERE created_at <= fecha` |
| `filter[search]` | Texto libre | LIKE en `operacion`, `no_pedimento`, `referencia_cliente` |
| `sort` | Campo | `created_at`, `fecha_arribo`, `cliente`, `operacion`. Prefijo `-` = DESC |
| `ver_completadas` | Boolean | `true` = ver solo completadas; sin él = ocultar completadas |

### Ejemplo de URL con filtros combinados

```
/logistica/matriz-seguimiento?filter[cliente]=3&filter[status]=Out of Metric&sort=-created_at
```

---

## 7. Historial SGM (Bitácora de Cambios)

El historial SGM (`HistoricoMatrizSgm`) registra el ciclo de vida completo de cada operación:

```
Alta de operación
    → generarHistorialInicial() → Registro SGM: "Operación creada"
    
Cambio de status manual
    → updateStatus() → Registro SGM: "Status actualizado a Done"
    
Recálculo masivo
    → recalcularStatus() → Registro SGM para cada operación que cambia de status
    
Consulta
    → obtenerHistorial({id}) → JSON con todos los registros ordenados por fecha
```

El historial es **inmutable**: los registros no se eliminan ni editan. Solo se añaden nuevos.

---

## 8. Consulta Pública para Clientes

Las rutas bajo `/logistica/consulta-publica` son accesibles sin autenticación:

```
GET  /logistica/consulta-publica
    → Vista con formulario de búsqueda
    
GET  /logistica/consulta-publica/buscar?pedimento=25 24 3023 2000001
    → JSON: { operacion: {...}, historial: [...] }
```

**Datos expuestos al público**:
- Status actual de la operación
- Fecha de arribo / embarque
- Historial SGM (fechas y observaciones de estado)

**Datos NO expuestos**:
- Nombres de ejecutivos, costos, datos internos

---

## 9. Control de Acceso por Rol

| Usuario | Acceso en index() |
|---|---|
| `admin` | Ve todas las operaciones de todos los ejecutivos |
| `admin` con `?preview_as={id}` | Simula la vista de un ejecutivo específico |
| Ejecutivo con empleado vinculado | Solo ve operaciones donde `ejecutivo LIKE '%nombre%'` |
| Usuario sin empleado vinculado | `WHERE id = 0` — No ve ninguna operación (bloqueo seguro) |

```php
// Vinculación usuario ↔ empleado
$empleadoActual = Empleado::where('correo', $usuarioActual->email)
    ->orWhere('nombre', 'like', '%' . $usuarioActual->name . '%')
    ->first();
```

> **Riesgo**: La búsqueda por `nombre LIKE '%name%'` puede retornar múltiples empleados si hay homónimos. Se usa `->first()`, lo que tomaría el primero en BD.

---

## 10. Referencia de Rutas

**Middleware rutas protegidas**: `auth`, `area.logistica`  
**Prefijo**: `/logistica`  
**Nombre base**: `logistica.`

### Rutas públicas (sin autenticación)

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `GET` | `/logistica/consulta-publica` | `logistica.consulta-publica.index` | Vista de consulta para clientes |
| `GET` | `/logistica/consulta-publica/buscar` | `logistica.consulta-publica.buscar` | JSON: estado + historial por pedimento/factura |

### Rutas protegidas

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `GET` | `/logistica/matriz-seguimiento` | `logistica.matriz-seguimiento` | Matriz principal paginada |
| `GET` | `/logistica/operaciones` | `logistica.operaciones.index` | Listado (resource) |
| `GET` | `/logistica/operaciones/create` | `logistica.operaciones.create` | JSON: catálogos para formulario |
| `POST` | `/logistica/operaciones` | `logistica.operaciones.store` | Crear operación |
| `GET` | `/logistica/operaciones/{id}` | `logistica.operaciones.show` | Detalle |
| `PUT/PATCH` | `/logistica/operaciones/{id}` | `logistica.operaciones.update` | Actualizar |
| `DELETE` | `/logistica/operaciones/{id}` | `logistica.operaciones.destroy` | Eliminar |
| `POST` | `/logistica/operaciones/recalcular-status` | `logistica.operaciones.recalcular` | Recalcular status masivo |
| `PUT` | `/logistica/operaciones/{id}/status` | `logistica.operaciones.status` | Cambiar status individual |
| `GET` | `/logistica/operaciones/{id}/historial` | `logistica.operaciones.historial` | JSON: bitácora SGM |

---

## 11. Historial de Migraciones

| Fecha | Archivo | Cambio |
|---|---|---|
| 2025-12-09 | `create_logistica_tables.php` | Migración consolidada: `operaciones_logisticas`, `historico_matriz_sgm`, catálogos base, campos personalizados, columnas visibles, pedimentos, post-operaciones, correos CC |
| 2026-02-04 | `add_mostrar_despues_de_to_columnas_visibles_ejecutivo.php` | Añade `mostrar_despues_de` para ordenar horizontalmente columnas dinámicas |
| 2026-02-05 | `make_empleado_id_nullable_in_columnas_visibles.php` | Permite configuraciones globales (sin ejecutivo específico) |

---

## 12. Guía de Mantenimiento del Módulo

---

### 🔴 CRÍTICO: Vinculación usuario ↔ empleado por nombre (ambigua)

Si un usuario tiene un nombre genérico (ej: "Ana López") y hay dos empleados con ese nombre, `->first()` tomaría el primero. El ejecutivo A podría ver operaciones del ejecutivo B.

**Solución**: Vincular por `correo` únicamente (ya es el primer criterio). Eliminar el fallback `orWhere nombre LIKE`:
```php
$empleadoActual = Empleado::where('correo', $usuarioActual->email)->first();
```

---

### 🔴 CRÍTICO: Consulta pública sin rate limiting

`GET /logistica/consulta-publica/buscar` es accesible por cualquiera sin autenticación y sin límite de peticiones. Alguien podría iterar pedimentos para extraer información.

**Solución**: Añadir middleware de throttle:
```php
Route::get('/buscar', 'buscarOperacionPublica')->middleware('throttle:20,1');
```

---

### 🟡 IMPORTANTE: Campos desnormalizados (`cliente`, `ejecutivo`)

Los campos `cliente` y `ejecutivo` en `operaciones_logisticas` almacenan el **nombre como string**, no como FK. Si un cliente cambia de nombre en el catálogo, las operaciones antiguas no se actualizan.

**Impacto**: El filtro por cliente en `index()` busca al cliente por ID en el catálogo y luego filtra por el nombre almacenado. Si el nombre cambió, el filtro puede no encontrar operaciones antiguas.

---

### 🟡 IMPORTANTE: `recalcularStatus()` puede ser lento con muchas operaciones

El recálculo itera sobre todas las operaciones activas en PHP (no en BD). Con miles de operaciones, puede provocar timeout.

**Mejora**: Usar un Job en cola para el recálculo masivo:
```php
RecalcularStatusMasivoJob::dispatch();
return response()->json(['success' => true, 'message' => 'Recálculo encolado']);
```

---

### 🟢 SEGURO: Añadir un nuevo tipo de operación (ej: `Multimodal`)

1. En `create()`, añadir a `tipos_operacion`: `['Aerea', 'Terrestre', 'Maritima', 'Ferrocarril', 'Multimodal']`
2. En la validación `StoreOperacionRequest`: actualizar `'tipo_operacion' => 'in:Aerea,Terrestre,...,Multimodal'`
3. Verificar que la vista del formulario y los filtros muestren la nueva opción.

---

### 🟢 SEGURO: Cambiar el target de días por defecto

Actualmente: `$target = $this->target ?? 30`. Para cambiar a 45 días:
```php
$target = $this->target ?? 45;
```
También en `recalcularStatus()` si el cálculo está duplicado.

---

### Checklist de deploy para cambios en Matriz de Seguimiento

- [ ] ¿Se añade un campo nuevo a `OperacionLogistica`? Migración + `$fillable` + `StoreOperacionRequest` + `UpdateOperacionRequest` + vista.
- [ ] ¿Se cambia la lógica de status? Actualizar `calcularStatusPorDias()` y verificar `recalcularStatus()`.
- [ ] ¿Se añade un nuevo filtro? Registrar en `allowedFilters()` del `index()` y en el mismo helper de `ReporteController`.
- [ ] ¿Se despliega en servidor nuevo? Instalar `spatie/laravel-query-builder` via Composer.
- [ ] ¿Se activan las rutas públicas? Verificar que NO tengan middleware `auth`.

---

*Documentación generada el 27 de Abril de 2026 — ERP Estrategia e Innovación*
