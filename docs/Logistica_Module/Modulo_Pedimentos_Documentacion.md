# Módulo Logística — Pedimentos — Documentación Maestra
> **ERP Estrategia e Innovación** · Versión documental: Abril 2026  
> Audiencia: Ejecutivos aduanales, área contable, desarrolladores

---

## Tabla de Contenido

1. [Visión General](#1-visión-general)
2. [Arquitectura del Módulo](#2-arquitectura-del-módulo)
3. [Modelo de Datos](#3-modelo-de-datos)
4. [Conceptos: Clave, Pedimento y Pago](#4-conceptos-clave-pedimento-y-pago)
5. [Referencia de Métodos — `PedimentoController`](#5-referencia-de-métodos--pedimentocontroller)
6. [Referencia de Métodos — `PedimentoImportController`](#6-referencia-de-métodos--pedimentoimportcontroller)
7. [Semáforo de Estado de Pago](#7-semáforo-de-estado-de-pago)
8. [Exportación CSV de Pedimentos](#8-exportación-csv-de-pedimentos)
9. [Compatibilidad SQLite / MySQL](#9-compatibilidad-sqlite--mysql)
10. [Referencia de Rutas](#10-referencia-de-rutas)
11. [Guía de Mantenimiento del Módulo](#11-guía-de-mantenimiento-del-módulo)

---

## 1. Visión General

El módulo de **Pedimentos** gestiona los documentos aduanales asociados a las operaciones logísticas. Un pedimento es el comprobante oficial de una operación de comercio exterior ante la aduana mexicana. El módulo agrupa pedimentos por **clave**, permite controlar su estado contable (pagado/pendiente), y exportar el listado para facturación.

### Propósito de negocio

| Necesidad | Solución |
|---|---|
| Ver todos los pedimentos agrupados por tipo/clave | `index()` — agrupación por `clave` con agregados SQL |
| Controlar si un pedimento fue cobrado al cliente | `PedimentoOperacion.estado_pago` (pagado/pendiente) |
| Marcar múltiples pedimentos pagados en bloque | `marcarPagados()` — bulk update |
| Exportar pedimentos pagados para facturación | `exportCSV()` — con `chunk()` para eficiencia |
| Gestionar el catálogo de claves de pedimento | `PedimentoImportController` CRUD |
| Importar pedimentos en masa desde archivo | `PedimentoImportController::import()` |

---

## 2. Arquitectura del Módulo

```
┌──────────────────────────────────────────────────────────────────┐
│                       PEDIMENTOS                                 │
│                                                                  │
│  Middleware: auth + area.logistica                               │
│  Prefijo URL: /logistica/pedimentos                              │
│                                                                  │
│  PedimentoController                                             │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  index()              → Agrupado por clave con semáforo    │ │
│  │  store()              → Alta de clave de pedimento         │ │
│  │  show({id})           → Detalle de todas las ops de clave  │ │
│  │  destroy({id})        → Elimina la clave                   │ │
│  │  updateEstadoPago()   → Marca pagado/pendiente individual  │ │
│  │  marcarPagados()      → Bulk: marcar múltiples como pagado │ │
│  │  getPedimentosPorClave()→ JSON para modal de detalle       │ │
│  │  actualizarPedimento()→ Edición individual via AJAX        │ │
│  │  getMonedas()         → Lista de monedas disponibles       │ │
│  │  exportCSV()          → Exporta pedimentos cobrados a CSV  │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                  │
│  PedimentoImportController                                       │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  Prefijo URL: /logistica/pedimentos/gestion                │ │
│  │  index()   → Lista JSON del catálogo de pedimentos         │ │
│  │  store()   → Alta individual en catálogo                   │ │
│  │  update()  → Edición en catálogo                           │ │
│  │  destroy() → Elimina del catálogo                          │ │
│  │  clear()   → Trunca el catálogo completo                   │ │
│  │  import()  → Importa desde CSV/Excel (legacy)              │ │
│  │  getCategorias()    → Selector dinámico                    │ │
│  │  getSubcategorias() → Selector dinámico                    │ │
│  └────────────────────────────────────────────────────────────┘ │
│                                                                  │
│  Modelos                                                         │
│  ──────                                                           │
│  Pedimento             → Catálogo de claves                      │
│  PedimentoOperacion    → Pivot: operación + pedimento + pago     │
│  OperacionLogistica    → Fuente de datos de operaciones          │
└──────────────────────────────────────────────────────────────────┘
```

---

## 3. Modelo de Datos

### `Pedimento` — Tabla `pedimentos` (o `logistica_pedimentos`)

Catálogo de claves de pedimento.

| Campo | Descripción |
|---|---|
| `id` | PK |
| `clave` | Código alfanumérico de la clave (ej: `A1`, `IN`, `EX`) |
| `descripcion` | Descripción del tipo de operación |
| `categoria` | Categoría del pedimento |
| `subcategoria` | Subcategoría |

### `PedimentoOperacion` — Tabla `pedimento_operaciones`

Tabla pivot que asocia una operación logística con su estado de pago de pedimento.

| Campo | Descripción |
|---|---|
| `id` | PK |
| `no_pedimento` | Número del pedimento (desnormalizado de `OperacionLogistica`) |
| `operacion_logistica_id` | FK → `operaciones_logisticas.id` |
| `clave` | Clave del pedimento (desnormalizado) |
| `estado_pago` | `pagado` o `pendiente` |
| `fecha_pago` | Timestamp cuando se marcó como pagado |

**Nota**: `PedimentoOperacion` se crea automáticamente en `index()` via `firstOrCreate()` cuando se listan los pedimentos. Si una operación tiene `no_pedimento` pero no tiene registro en esta tabla, se crea con `estado_pago = 'pendiente'`.

---

## 4. Conceptos: Clave, Pedimento y Pago

Un **pedimento** es el documento de comercio exterior. Cada pedimento tiene:
- Un **número de pedimento** (`no_pedimento`) — único por operación
- Una **clave** (`clave`) — tipo de operación aduanal (importación, exportación, etc.)

La vista de `index()` agrupa por `clave` para dar una vista consolidada:

```
CLAVE A1 (Importación definitiva)
  ├── Pedimento 25 24 3023 2000001 — Op #101 — Cliente XYZ — PENDIENTE
  ├── Pedimento 25 24 3023 2000002 — Op #102 — Cliente ABC — PAGADO
  └── Pedimento 25 24 3023 2000003 — Op #103 — Cliente XYZ — PENDIENTE
  ├── Total: 3 pedimentos | 2 pendientes | 1 pagado → Estado: PENDIENTE 🔴

CLAVE EX (Exportación)
  └── Pedimento 25 24 3024 2000001 — Op #104 — Cliente XYZ — PAGADO
  └── Total: 1 pedimentos | 0 pendientes | 1 pagado → Estado: PAGADO 🟢
```

---

## 5. Referencia de Métodos — `PedimentoController`

**Archivo**: `app/Http/Controllers/Logistica/PedimentoController.php`

---

### `index(Request $request): View`

**Ruta**: `GET /logistica/pedimentos`

El método más complejo del módulo. Pasos:

1. **Query base**: Filtra `OperacionLogistica` con `no_pedimento` y `clave` no nulos/vacíos
2. **Filtro de búsqueda**: `?buscar=XYZ` → LIKE en `clave`, `cliente`, `no_pedimento`
3. **Agrupación**: `GROUP BY clave` con agregados (`COUNT`, `GROUP_CONCAT`, `MIN/MAX fecha`)
4. **Detección de BD**: SQLite vs MySQL cambia la sintaxis de `GROUP_CONCAT`
5. **Procesamiento de pagos**: Por cada clave, crea/obtiene `PedimentoOperacion` via `firstOrCreate()`
6. **Semáforo**: `pendientes > 0` → PENDIENTE; todos pagados → PAGADO
7. **Filtro post-proceso**: `?estado_pago=pendiente` filtra la colección ya procesada
8. **Re-paginación manual**: Necesaria porque el filtro de estado se aplica en PHP, no en BD

**Filtros GET**:

| Parámetro | Efecto |
|---|---|
| `buscar` | LIKE en `clave`, `cliente`, `no_pedimento` |
| `estado_pago` | `pendiente` o `pagado` — filtrado post-proceso en colección PHP |

**Variables de vista**:
```php
compact('pedimentos', 'stats')
// stats = { total_claves, total_pedimentos, pagados, pendientes }
```

---

### `updateEstadoPago(Request $request, $id): JsonResponse`

**Ruta**: `PUT /logistica/pedimentos/{id}/estado-pago`

Cambia el `estado_pago` de una `PedimentoOperacion` individual.

```php
$registro->estado_pago = $request->estado_pago; // 'pagado' o 'pendiente'
if ($request->estado_pago === 'pagado') {
    $registro->fecha_pago = now();
}
$registro->save();
```

---

### `marcarPagados(Request $request): JsonResponse`

**Ruta**: `POST /logistica/pedimentos/marcar-pagados`

Bulk action: marca múltiples `PedimentoOperacion` como pagadas en una sola petición.

```php
// Espera: { "ids": [1, 2, 3, 4] }
PedimentoOperacion::whereIn('id', $request->ids)
    ->update(['estado_pago' => 'pagado', 'fecha_pago' => now()]);
```

---

### `getPedimentosPorClave(Request $request, $clave): JsonResponse`

**Ruta**: `GET /logistica/pedimentos/clave/{clave}`

Retorna todas las operaciones de una clave para poblar el modal de detalle:

```json
{
  "pedimentos": [
    {
      "id": 12,
      "no_pedimento": "25 24 3023 2000001",
      "cliente": "Importadora XYZ",
      "ejecutivo": "Juan Pérez",
      "fecha_embarque": "2026-02-15",
      "estado_pago": "pendiente"
    }
  ]
}
```

---

### `exportCSV(): StreamedResponse`

**Ruta**: `GET /logistica/reportes/pedimentos/exportar`

Exporta pedimentos cobrados (pagados) a CSV. Usa `chunk(200)` para procesar en lotes y evitar consumo excesivo de RAM:

```php
OperacionLogistica::whereHas('pedimentoOperaciones', function($q) {
    $q->where('estado_pago', 'pagado');
})->chunk(200, function ($operaciones) use (&$csv) {
    foreach ($operaciones as $op) {
        fputcsv($csv, [...]);
    }
});
```

---

## 6. Referencia de Métodos — `PedimentoImportController`

**Archivo**: `app/Http/Controllers/Logistica/PedimentoImportController.php`  
**Prefijo URL**: `/logistica/pedimentos/gestion`

Este controlador gestiona el **catálogo de claves de pedimento** (no los pedimentos de operaciones individuales).

| Método | Ruta | Descripción |
|---|---|---|
| `index()` | `GET /gestion` | Lista JSON del catálogo |
| `store()` | `POST /gestion` | Alta individual |
| `update($id)` | `PUT /gestion/{id}` | Edición |
| `destroy($id)` | `DELETE /gestion/{id}` | Elimina una clave |
| `clear()` | `DELETE /gestion/limpiar-todo` | Trunca el catálogo completo |
| `import()` | `POST /gestion/importar-legacy` | Importa desde CSV/Excel |
| `getCategorias()` | `GET /gestion/categorias-list` | JSON: lista de categorías únicas |
| `getSubcategorias()` | `GET /gestion/subcategorias-list` | JSON: subcategorías filtradas por categoría |

### `import()` — Importación legacy

Acepta un archivo CSV o Excel con el formato de pedimentos. Procesa fila por fila con `upsert` para evitar duplicados:

```php
// Ejemplo de formato esperado:
// CLAVE | DESCRIPCION | CATEGORIA | SUBCATEGORIA
// A1    | Importación | Definitivos | Normal
```

---

## 7. Semáforo de Estado de Pago

El estado del grupo de una clave se determina en PHP después de procesar todos los `PedimentoOperacion`:

```php
'estado_pago' => $pedimentosPorPagar > 0 
    ? 'pendiente'   // Al menos uno pendiente → todo el grupo es PENDIENTE
    : ($pedimentosPagados > 0 
        ? 'pagado'  // Todos pagados
        : 'pendiente') // No hay registros → se asume pendiente
```

**Lógica del semáforo**:
- 🔴 `pendiente` — Hay al menos un pedimento por cobrar en la clave
- 🟢 `pagado` — Todos los pedimentos de la clave están cobrados

Esta lógica es **conservadora**: una sola operación pendiente hace que todo el grupo sea pendiente, lo que evita olvidar cobros.

---

## 8. Exportación CSV de Pedimentos

El CSV de pedimentos exporta solo los **cobrados** (estado_pago = 'pagado') y se genera como `StreamedResponse` para no cargar todo en memoria.

**Campos en el CSV**:
- No. Pedimento, Clave, Cliente, Ejecutivo, Fecha Embarque, Fecha Pago, Monto (si aplica)

**Acceso**: `GET /logistica/reportes/pedimentos/exportar` (dentro del grupo de reportes)

---

## 9. Compatibilidad SQLite / MySQL

El controlador detecta el driver de BD y ajusta la sintaxis de `GROUP_CONCAT`:

```php
$driver = DB::connection()->getDriverName();
$isSqlite = $driver === 'sqlite';

// MySQL: DISTINCT + ORDER BY + SEPARATOR
$sqlClientes = 'GROUP_CONCAT(DISTINCT cliente ORDER BY cliente SEPARATOR ", ")';

// SQLite: Sintaxis básica (sin DISTINCT en GROUP_CONCAT)
$sqlClientes = 'GROUP_CONCAT(cliente)';
```

Si la BD es SQLite, se realiza una deduplicación manual en PHP:
```php
$claveData->clientes = implode(', ', array_unique(explode(',', $claveData->clientes)));
```

Esta compatibilidad permite que el módulo funcione en entornos de desarrollo con SQLite y producción con MySQL sin cambios de código.

---

## 10. Referencia de Rutas

**Middleware**: `auth`, `area.logistica`  
**Prefijo**: `/logistica`  
**Nombre base**: `logistica.`

### PedimentoController — `/logistica/pedimentos`

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `GET` | `/pedimentos` | `logistica.pedimentos.index` | Listado agrupado por clave |
| `POST` | `/pedimentos` | `logistica.pedimentos.store` | Alta de clave |
| `GET` | `/pedimentos/{id}` | `logistica.pedimentos.show` | Detalle de clave |
| `DELETE` | `/pedimentos/{id}` | `logistica.pedimentos.destroy` | Eliminar clave |
| `PUT` | `/pedimentos/{id}/estado-pago` | `logistica.pedimentos.update-estado` | Cambiar estado de pago individual |
| `POST` | `/pedimentos/marcar-pagados` | `logistica.pedimentos.marcar-pagados` | Bulk: marcar como pagados |
| `GET` | `/pedimentos/clave/{clave}` | `logistica.pedimentos.por-clave` | JSON: operaciones de una clave |
| `POST` | `/pedimentos/actualizar-individual` | `logistica.pedimentos.actualizar-individual` | AJAX: actualizar un pedimento |
| `GET` | `/pedimentos/monedas/list` | `logistica.pedimentos.monedas` | JSON: lista de monedas |
| `GET` | `/reportes/pedimentos/exportar` | `logistica.reportes.pedimentos.export` | CSV: pedimentos cobrados |

### PedimentoImportController — `/logistica/pedimentos/gestion`

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `GET` | `/gestion` | `logistica.pedimentos.import.index` | Lista catálogo JSON |
| `POST` | `/gestion` | `logistica.pedimentos.import.store` | Alta en catálogo |
| `PUT` | `/gestion/{id}` | `logistica.pedimentos.import.update` | Editar en catálogo |
| `DELETE` | `/gestion/{id}` | `logistica.pedimentos.import.destroy` | Eliminar del catálogo |
| `DELETE` | `/gestion/limpiar-todo` | `logistica.pedimentos.import.clear` | Truncar catálogo |
| `POST` | `/gestion/importar-legacy` | `logistica.pedimentos.import.legacy` | Importar CSV/Excel |
| `GET` | `/gestion/categorias-list` | `logistica.pedimentos.import.categorias` | Selector de categorías |
| `GET` | `/gestion/subcategorias-list` | `logistica.pedimentos.import.subcategorias` | Selector de subcategorías |

---

## 11. Guía de Mantenimiento del Módulo

---

### 🔴 CRÍTICO: El filtro por `estado_pago` se hace en PHP, no en BD

El filtro `?estado_pago=pendiente` se aplica **después** de la consulta SQL, sobre la colección ya en memoria. Con miles de pedimentos, esto puede ser lento y consumir mucha RAM.

**Síntoma**: La página de pedimentos tarda varios segundos en cargar.

**Solución**: Mover el filtro de estado_pago a la consulta SQL. Requiere hacer un JOIN de `PedimentoOperacion`:
```php
$operacionesQuery->whereHas('pedimentoOperacion', function($q) use ($request) {
    $q->where('estado_pago', $request->estado_pago);
});
```

---

### 🔴 CRÍTICO: `firstOrCreate` en el listado crea registros automáticamente

En `index()`, se llama `PedimentoOperacion::firstOrCreate(...)` para cada operación con pedimento. Esto significa que **cada visita al listado de pedimentos puede crear registros en `pedimento_operaciones`** si aún no existen.

**Impacto**: El log de la BD registra escrituras en cada lectura de la vista. En producción con auditoría esto es problemático.

**Solución**: Usar `firstOrNew()` para no persistir hasta que el usuario tome una acción explícita, o pre-crear los registros en el `store()` de `OperacionLogistica`.

---

### 🟡 IMPORTANTE: Re-paginación manual pierde el total exacto

Al filtrar por `estado_pago` en PHP y re-paginar manualmente, el `total()` del paginador usa el total pre-filtro (count SQL), no el count post-filtro. El paginador puede mostrar "50 resultados" cuando solo hay 20 pendientes.

**Fix**: Usar el count de la colección filtrada:
```php
$paginatedPedimentos = new LengthAwarePaginator(
    $pedimentosConEstado,
    $pedimentosConEstado->count(), // ← usar count post-filtro
    ...
);
```

---

### 🟡 IMPORTANTE: `clear()` en PedimentoImportController borra sin confirmación server-side

`DELETE /logistica/pedimentos/gestion/limpiar-todo` ejecuta un `TRUNCATE` sin validación adicional. Un request malicioso o un error del usuario puede borrar todo el catálogo.

**Recomendación**: Añadir middleware `admin` a esta ruta:
```php
Route::delete('/limpiar-todo', 'clear')->name('clear')->middleware('admin');
```

---

### 🟢 SEGURO: Añadir un nuevo estado de pago (ej: `en_proceso`)

1. Actualizar la validación en `updateEstadoPago()`: `'estado_pago' => 'in:pagado,pendiente,en_proceso'`
2. Actualizar `marcarPagados()` para soportar el nuevo estado.
3. Actualizar el semáforo en `index()` para el nuevo estado.
4. Actualizar la vista para mostrar el nuevo color/ícono.

---

### Checklist de deploy para cambios en Pedimentos

- [ ] ¿Se añade campo a `PedimentoOperacion`? Crear migración + actualizar `$fillable` + actualizar `updateEstadoPago()`.
- [ ] ¿Se cambia el formato del CSV de exportación? Actualizar `exportCSV()` y la documentación del CSV.
- [ ] ¿Se añade estado de pago? Actualizar validaciones, semáforo y vista.
- [ ] ¿Se despliega en servidor nuevo con MySQL? Verificar que `GROUP_CONCAT DISTINCT` funciona correctamente.

---

*Documentación generada el 27 de Abril de 2026 — ERP Estrategia e Innovación*
