# Módulo Logística — Campos Personalizados y Visibilidad de Columnas — Documentación Maestra
> **ERP Estrategia e Innovación** · Versión documental: Abril 2026  
> Audiencia: Administradores de logística, desarrolladores

---

## Tabla de Contenido

1. [Visión General](#1-visión-general)
2. [Arquitectura del Módulo](#2-arquitectura-del-módulo)
3. [Patrón EAV: Cómo funciona](#3-patrón-eav-cómo-funciona)
4. [Modelo de Datos](#4-modelo-de-datos)
5. [Los 12 Tipos de Campo Disponibles](#5-los-12-tipos-de-campo-disponibles)
6. [Referencia de Métodos — `CampoPersonalizadoController`](#6-referencia-de-métodos--campopersonalizadocontroller)
7. [Referencia de Métodos — `ColumnaVisibleController`](#7-referencia-de-métodos--columnavisiblecontroller)
8. [Visibilidad por Ejecutivo vs Global](#8-visibilidad-por-ejecutivo-vs-global)
9. [Integración con la Matriz de Seguimiento](#9-integración-con-la-matriz-de-seguimiento)
10. [Referencia de Rutas](#10-referencia-de-rutas)
11. [Guía de Mantenimiento del Módulo](#11-guía-de-mantenimiento-del-módulo)

---

## 1. Visión General

El módulo de **Campos Personalizados** permite añadir columnas dinámicas a la Matriz de Seguimiento sin necesidad de modificar la base de datos ni hacer una migración. Implementa el patrón **EAV (Entity-Attribute-Value)**: en lugar de añadir columnas a `operaciones_logisticas`, se almacenan en dos tablas separadas.

El módulo de **Visibilidad de Columnas** complementa a los campos personalizados, permitiendo a cada ejecutivo configurar qué columnas quiere ver en su vista de la matriz, tanto columnas nativas como campos personalizados.

### Propósito de negocio

| Necesidad | Solución |
|---|---|
| Añadir datos específicos sin migración | Campos personalizados EAV |
| 12 tipos de dato distintos (fechas, moneda, etc.) | `tipo` en `CampoPersonalizadoMatriz` |
| Editar celdas de campos personalizados en línea | `storeValor()` — updateOrCreate AJAX |
| Cada ejecutivo ve las columnas que necesita | `ColumnaVisibleEjecutivo` por empleado |
| Configuración global de columnas para todos | `empleado_id = null` en la tabla |
| Ordenar las columnas horizontalmente | Campo `mostrar_despues_de` |

---

## 2. Arquitectura del Módulo

```
┌──────────────────────────────────────────────────────────────────┐
│             CAMPOS PERSONALIZADOS + VISIBILIDAD                  │
│                                                                  │
│  CampoPersonalizadoController → /logistica/campos-personalizados │
│  ┌────────────────────────────────────────────────────────────┐  │
│  │  index()        → Lista JSON de todos los campos           │  │
│  │  store()        → Crea definición de campo                 │  │
│  │  update()       → Edita nombre/tipo/opciones               │  │
│  │  destroy()      → Elimina campo + todos sus valores        │  │
│  │  toggleActivo() → Activa/desactiva globalmente             │  │
│  │  getCamposActivos()      → JSON: campos activos ordenados  │  │
│  │  storeValor()            → updateOrCreate valor por celda  │  │
│  │  getValoresOperacion()   → JSON: pluck de valores por op.  │  │
│  └────────────────────────────────────────────────────────────┘  │
│                                                                  │
│  ColumnaVisibleController → /logistica/columnas-visibles         │
│  ┌────────────────────────────────────────────────────────────┐  │
│  │  getEjecutivos()            → Lista de ejecutivos           │  │
│  │  getConfiguracion($id)      → Config de un ejecutivo       │  │
│  │  guardarConfiguracion()     → Guarda config individual     │  │
│  │  guardarConfiguracionCompleta() → Config completa + orden  │  │
│  │  getConfiguracionGlobal()   → Config global (null emp_id)  │  │
│  │  guardarConfiguracionGlobal()→ Guarda config global        │  │
│  └────────────────────────────────────────────────────────────┘  │
│                                                                  │
│  Modelos                                                         │
│  ──────                                                           │
│  CampoPersonalizadoMatriz   → Definición del campo              │
│  ValorCampoPersonalizado    → Valor por operación               │
│  ColumnaVisibleEjecutivo    → Config de columnas por ejecutivo   │
└──────────────────────────────────────────────────────────────────┘
```

---

## 3. Patrón EAV: Cómo funciona

**EAV = Entity-Attribute-Value** (Entidad-Atributo-Valor)

En lugar de tener una columna para cada dato extra en `operaciones_logisticas`, se usan dos tablas:

```
Tabla: campo_personalizado_matriz (Atributo)
┌────┬──────────────┬──────────────┬──────────┬──────────┐
│ id │    nombre    │     tipo     │  activo  │  orden   │
├────┼──────────────┼──────────────┼──────────┼──────────┤
│  1 │ Factura FTA  │ texto        │   true   │    1     │
│  2 │ Valor Aduana │ moneda       │   true   │    2     │
│  3 │ Requiere OG  │ booleano     │   true   │    3     │
└────┴──────────────┴──────────────┴──────────┴──────────┘

Tabla: valor_campo_personalizado (Valor)
┌────┬──────────────────────┬──────────────────────┬──────────────────┐
│ id │ operacion_logistica_id │ campo_personalizado_id │     valor        │
├────┼──────────────────────┼──────────────────────┼──────────────────┤
│  1 │         42           │          1           │ FAC-2026-00123   │
│  2 │         42           │          2           │ 150000.00        │
│  3 │         42           │          3           │ 1                │
│  4 │         43           │          1           │ FAC-2026-00124   │
└────┴──────────────────────┴──────────────────────┴──────────────────┘
```

**Ventajas**:
- Sin migraciones al añadir un nuevo campo
- Sin downtime en producción
- Activación/desactivación global sin borrar datos

**Desventajas**:
- Queries más complejos (require JOINs o múltiples queries)
- Sin tipado estricto en BD (todos los valores son `varchar`)
- No se puede hacer `WHERE valor_moneda > 100` de forma eficiente

---

## 4. Modelo de Datos

### `CampoPersonalizadoMatriz` — Tabla `campo_personalizado_matriz`

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | `bigint` PK | |
| `nombre` | `varchar(255)` | Nombre visible de la columna |
| `tipo` | `varchar(50)` | Tipo de dato (ver sección 5) |
| `opciones` | `json` nullable | Para tipos `selector` y `multiple`: lista de opciones |
| `activo` | `boolean` default true | Si se muestra en la matriz |
| `orden` | `integer` default 0 | Orden de aparición entre campos personalizados |
| `placeholder` | `varchar(255)` nullable | Texto de ayuda para el input |
| `requerido` | `boolean` default false | Si el campo es obligatorio al crear operación |
| `created_at` / `updated_at` | `timestamp` | |

**Scopes del modelo**:
```php
scopeActivos($query)  → WHERE activo = 1
scopeOrdenado($query) → ORDER BY orden ASC, nombre ASC
```

### `ValorCampoPersonalizado` — Tabla `valor_campo_personalizado`

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | `bigint` PK | |
| `operacion_logistica_id` | `bigint` FK | FK → `operaciones_logisticas.id` |
| `campo_personalizado_id` | `bigint` FK | FK → `campo_personalizado_matriz.id` |
| `valor` | `text` nullable | Valor almacenado como texto |
| `created_at` / `updated_at` | `timestamp` | |

**Índice único**: `(operacion_logistica_id, campo_personalizado_id)` — Garantiza un valor por campo por operación.

### `ColumnaVisibleEjecutivo` — Tabla `columnas_visibles_ejecutivo`

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | `bigint` PK | |
| `empleado_id` | `bigint` FK nullable | `null` = configuración global |
| `columna_key` | `varchar(255)` | Identificador de la columna |
| `visible` | `boolean` | Si se muestra o no |
| `mostrar_despues_de` | `varchar(255)` nullable | Key de la columna anterior (para ordenar) |
| `created_at` / `updated_at` | `timestamp` | |

**Columnas nativas disponibles** (su `columna_key`):
`operacion`, `cliente`, `ejecutivo`, `no_pedimento`, `clave`, `referencia_cliente`, `tipo_operacion`, `tipo`, `status`, `fecha_arribo`, `fecha_embarque`, `target`, `dias_transcurridos`

**Columnas personalizadas** (su `columna_key`):
`campo_personalizado_{id}` — ej: `campo_personalizado_1` para el campo con id=1

---

## 5. Los 12 Tipos de Campo Disponibles

| Tipo | Descripción | Renderizado en vista |
|---|---|---|
| `texto` | Texto libre corto | `<input type="text">` |
| `descripcion` | Texto largo | `<textarea>` |
| `fecha` | Fecha | `<input type="date">` |
| `numero` | Entero | `<input type="number" step="1">` |
| `decimal` | Número con decimales | `<input type="number" step="0.01">` |
| `moneda` | Cantidad monetaria | `<input>` con formato `$X,XXX.XX` |
| `booleano` | Sí/No | `<checkbox>` o toggle |
| `selector` | Selección única de lista | `<select>` (opciones en `opciones` JSON) |
| `multiple` | Selección múltiple | `<select multiple>` (opciones en `opciones` JSON) |
| `email` | Correo electrónico | `<input type="email">` |
| `telefono` | Número telefónico | `<input type="tel">` |
| `url` | URL / enlace | `<input type="url">` |

**Para campos `selector` y `multiple`**, el campo `opciones` del modelo contiene:
```json
{ "opciones": ["Opción A", "Opción B", "Opción C"] }
```

---

## 6. Referencia de Métodos — `CampoPersonalizadoController`

**Archivo**: `app/Http/Controllers/Logistica/CampoPersonalizadoController.php`

---

### `index(): JsonResponse`

**Ruta**: `GET /logistica/campos-personalizados`

Retorna todos los campos (activos e inactivos) para el panel de administración:

```json
{
  "campos": [
    { "id": 1, "nombre": "Factura FTA", "tipo": "texto", "activo": true, "orden": 1 },
    { "id": 2, "nombre": "Valor Aduana", "tipo": "moneda", "activo": true, "orden": 2 }
  ]
}
```

---

### `store(Request $request): JsonResponse`

**Ruta**: `POST /logistica/campos-personalizados`

Crea una nueva definición de campo. La validación incluye:
```php
'nombre'    => 'required|string|max:255',
'tipo'      => 'required|in:texto,descripcion,fecha,numero,decimal,moneda,booleano,selector,multiple,email,telefono,url',
'opciones'  => 'nullable|array',  // Solo para selector/multiple
'orden'     => 'nullable|integer',
'requerido' => 'nullable|boolean'
```

---

### `toggleActivo($id): JsonResponse`

**Ruta**: `PUT /logistica/campos-personalizados/{id}/toggle`

Invierte el estado `activo` del campo. Cuando se desactiva, el campo deja de aparecer en la Matriz pero sus valores **no se borran**.

```php
$campo->activo = !$campo->activo;
$campo->save();
// Los ValorCampoPersonalizado existentes se preservan
```

---

### `getCamposActivos(): JsonResponse`

**Ruta**: `GET /logistica/campos-personalizados/activos`

Retorna solo los campos activos, en orden, para renderizar columnas en la Matriz:

```php
return CampoPersonalizadoMatriz::activos()->ordenado()->get();
```

---

### `storeValor(Request $request): JsonResponse`

**Ruta**: `POST /logistica/campos-personalizados/valor`

El corazón de la edición en línea. Actualiza o crea el valor de un campo para una operación específica:

```php
ValorCampoPersonalizado::updateOrCreate(
    [
        'operacion_logistica_id' => $request->operacion_id,
        'campo_personalizado_id' => $request->campo_id,
    ],
    ['valor' => $request->valor]
);
```

Retorna: `{ "success": true, "valor": "valor_guardado" }`

---

### `getValoresOperacion($id): JsonResponse`

**Ruta**: `GET /logistica/campos-personalizados/valores/{operacion_id}`

Retorna todos los valores de campos personalizados para una operación específica, indexados por `campo_id` para acceso rápido en el frontend:

```php
$valores = ValorCampoPersonalizado::where('operacion_logistica_id', $id)
    ->pluck('valor', 'campo_personalizado_id');

// Resultado: { "1": "FAC-2026-00123", "2": "150000.00", "3": "1" }
```

---

### `destroy($id): JsonResponse`

**Ruta**: `DELETE /logistica/campos-personalizados/{id}`

Elimina la definición del campo **y todos sus valores asociados** en cascada:

```php
$campo->valores()->delete();  // Elimina todos los ValorCampoPersonalizado
$campo->delete();              // Elimina la definición
```

---

## 7. Referencia de Métodos — `ColumnaVisibleController`

**Archivo**: `app/Http/Controllers/Logistica/ColumnaVisibleController.php`

---

### `getEjecutivos(): JsonResponse`

**Ruta**: `GET /logistica/columnas-visibles/ejecutivos`

Retorna la lista de ejecutivos de logística para el selector del panel de administración:

```php
$ejecutivos = Empleado::where(function($q) {
    $q->where('posicion', 'like', '%logistic%')
        ->orWhere('area', 'like', '%logistic%');
})->where('es_activo', true)->get();
```

---

### `getConfiguracion($empleadoId): JsonResponse`

**Ruta**: `GET /logistica/columnas-visibles/{empleadoId}`

Retorna la configuración de columnas de un ejecutivo específico. Si no tiene configuración propia, cae al global:

```php
// Columnas nativas del sistema
$columnasNativas = $this->getColumnasNativas(); // Lista hardcoded

// Columnas personalizadas
$camposPersonalizados = CampoPersonalizadoMatriz::activos()->ordenado()->get()
    ->map(fn($c) => ['key' => "campo_personalizado_{$c->id}", 'nombre' => $c->nombre]);

// Configuración guardada del ejecutivo
$config = ColumnaVisibleEjecutivo::where('empleado_id', $empleadoId)->get()
    ->keyBy('columna_key');

// Merge: columna nativa + estado visible + mostrar_despues_de
```

---

### `guardarConfiguracion(Request $request): JsonResponse`

**Ruta**: `POST /logistica/columnas-visibles/guardar`

Guarda la visibilidad de una columna individual para un ejecutivo:

```php
ColumnaVisibleEjecutivo::updateOrCreate(
    ['empleado_id' => $request->empleado_id, 'columna_key' => $request->columna_key],
    ['visible' => $request->visible]
);
```

---

### `guardarConfiguracionCompleta(Request $request): JsonResponse`

**Ruta**: `POST /logistica/columnas-visibles/guardar-completa`

Recibe el objeto completo de configuración (todas las columnas con su visibilidad y orden) y lo persiste:

```php
// Espera array: [{ columna_key, visible, mostrar_despues_de }, ...]
foreach ($request->columnas as $columna) {
    ColumnaVisibleEjecutivo::updateOrCreate(
        ['empleado_id' => $request->empleado_id, 'columna_key' => $columna['key']],
        ['visible' => $columna['visible'], 'mostrar_despues_de' => $columna['mostrar_despues_de']]
    );
}
```

---

### `getConfiguracionGlobal(): JsonResponse`

**Ruta**: `GET /logistica/columnas-visibles/global`

Retorna la configuración global (registros con `empleado_id = null`). Esta configuración aplica a todos los ejecutivos que no tienen configuración personal.

---

### `guardarConfiguracionGlobal(Request $request): JsonResponse`

**Ruta**: `POST /logistica/columnas-visibles/guardar-global`

Guarda la configuración para `empleado_id = null`:

```php
ColumnaVisibleEjecutivo::updateOrCreate(
    ['empleado_id' => null, 'columna_key' => $columna['key']],
    ['visible' => $columna['visible'], 'mostrar_despues_de' => $columna['mostrar_despues_de']]
);
```

---

## 8. Visibilidad por Ejecutivo vs Global

```
JERARQUÍA DE CONFIGURACIÓN DE COLUMNAS

1. Configuración personal del ejecutivo (empleado_id = X)
   → Tiene precedencia sobre la configuración global
   
2. Configuración global (empleado_id = null)
   → Aplica cuando el ejecutivo no tiene configuración personal

3. Defaults del sistema (si no hay config global)
   → Columnas nativas: todas visibles
   → Campos personalizados: según campo `activo`
```

**Panel de administración** (solo admin/supervisor):
- Puede configurar las columnas de cualquier ejecutivo individualmente
- Puede configurar la configuración global que aplica a todos

**El ejecutivo mismo** puede ajustar su propia configuración desde la Matriz.

---

## 9. Integración con la Matriz de Seguimiento

Los campos personalizados se integran en la Matriz de Seguimiento de dos formas:

### Al cargar la Matriz (`index()` de `OperacionLogisticaController`)

```php
// Se pasan a la vista para renderizar columnas adicionales
$camposPersonalizados = CampoPersonalizadoMatriz::activos()->ordenado()->get();

// Se cargan los valores de todos los campos para todas las operaciones de la página
$valoresCampos = ValorCampoPersonalizado::whereIn('operacion_logistica_id', $operacionIds)
    ->get()
    ->groupBy('operacion_logistica_id');
```

### Al guardar una operación (`store()` y `update()`)

```php
// Los campos personalizados vienen en el request como campo_personalizado_1, campo_personalizado_2
private function guardarCamposPersonalizados(Request $request, int $operacionId): void
{
    $campos = CampoPersonalizadoMatriz::activos()->get();
    foreach ($campos as $campo) {
        $key = "campo_personalizado_{$campo->id}";
        if ($request->has($key)) {
            ValorCampoPersonalizado::updateOrCreate(
                ['operacion_logistica_id' => $operacionId, 'campo_personalizado_id' => $campo->id],
                ['valor' => $request->input($key)]
            );
        }
    }
}
```

---

## 10. Referencia de Rutas

**Middleware**: `auth`, `area.logistica`  
**Prefijo**: `/logistica`  
**Nombre base**: `logistica.`

### Campos Personalizados — `/logistica/campos-personalizados`

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `GET` | `/campos-personalizados` | `logistica.campos.index` | Lista todos los campos |
| `POST` | `/campos-personalizados` | `logistica.campos.store` | Crear campo |
| `PUT` | `/campos-personalizados/{id}` | `logistica.campos.update` | Editar campo |
| `DELETE` | `/campos-personalizados/{id}` | `logistica.campos.destroy` | Eliminar campo + valores |
| `PUT` | `/campos-personalizados/{id}/toggle` | `logistica.campos.toggle` | Activar/desactivar |
| `GET` | `/campos-personalizados/activos` | `logistica.campos.activos` | Solo campos activos |
| `POST` | `/campos-personalizados/valor` | `logistica.campos.valor.store` | Guardar valor (AJAX) |
| `GET` | `/campos-personalizados/valores/{id}` | `logistica.campos.valores` | Valores de una operación |

### Columnas Visibles — `/logistica/columnas-visibles`

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `GET` | `/columnas-visibles/ejecutivos` | `logistica.columnas.ejecutivos` | Lista ejecutivos |
| `GET` | `/columnas-visibles/{empleadoId}` | `logistica.columnas.config` | Config de ejecutivo |
| `POST` | `/columnas-visibles/guardar` | `logistica.columnas.guardar` | Guardar una columna |
| `POST` | `/columnas-visibles/guardar-completa` | `logistica.columnas.guardar-completa` | Config completa |
| `GET` | `/columnas-visibles/global` | `logistica.columnas.global` | Config global |
| `POST` | `/columnas-visibles/guardar-global` | `logistica.columnas.guardar-global` | Guardar config global |

---

## 11. Guía de Mantenimiento del Módulo

---

### 🔴 CRÍTICO: `destroy()` elimina todos los valores históricamente acumulados

Al eliminar un `CampoPersonalizadoMatriz`, se eliminan también todos los `ValorCampoPersonalizado` de todas las operaciones. No hay recuperación. Si el campo tenía datos de 500 operaciones, todos se pierden.

**Recomendación**: Añadir una confirmación adicional en el frontend ("Este campo tiene N valores asociados que serán eliminados permanentemente") y considerar una papelera lógica (`deleted_at`) en vez de borrado físico.

---

### 🟡 IMPORTANTE: Los valores EAV son siempre texto (`varchar`)

El campo `valor` en `ValorCampoPersonalizado` es `text` sin distinción de tipo. Para un campo tipo `moneda`, el valor `"150000.00"` es un string. El formateo y la conversión ocurren en el **frontend**.

**Impacto**: No se puede hacer `WHERE valor > 100` para filtrar por rango de valor monetario en SQL.

**Mitigación**: Para campos que requieran filtrado, convertir en el frontend o crear columnas reales en `operaciones_logisticas`.

---

### 🟡 IMPORTANTE: Sin límite de campos personalizados activos

Un admin puede crear 50 campos personalizados activos, lo que agrega 50 JOINs o subqueries al cargar la Matriz.

**Recomendación**: Limitar en `store()`:
```php
if (CampoPersonalizadoMatriz::activos()->count() >= 20) {
    return response()->json(['success' => false, 'message' => 'Máximo 20 campos activos'], 422);
}
```

---

### 🟢 SEGURO: Añadir un nuevo tipo de campo (ej: `color`)

1. Añadir `color` al `in:` de la validación en `store()` y `update()`
2. Añadir el renderizado correspondiente en la vista de la Matriz (ej: `<input type="color">`)
3. Documentar el nuevo tipo en esta tabla

---

### 🟢 SEGURO: Reordenar los campos personalizados

La columna `orden` determina la posición. Para reordenar:
1. Desde el frontend: arrastrar columnas y enviar el nuevo orden via `PUT /logistica/campos-personalizados/{id}` con `{ orden: N }`
2. O directamente en BD: `UPDATE campo_personalizado_matriz SET orden = N WHERE id = X`

---

### Checklist de deploy para cambios en Campos Personalizados

- [ ] ¿Se añade un nuevo tipo de campo? Actualizar validación en `store()`, añadir caso en la vista (blade), probar `storeValor()`.
- [ ] ¿Se añade campo a `CampoPersonalizadoMatriz`? Migración + `$fillable` + actualizar respuesta de `index()` y `getCamposActivos()`.
- [ ] ¿Se añade campo a `ColumnaVisibleEjecutivo`? Migración + `$fillable` + actualizar `getConfiguracion()` y `guardarConfiguracionCompleta()`.
- [ ] ¿Se cambia el nombre de una columna nativa? Actualizar la lista hardcoded de columnas nativas en `ColumnaVisibleController`.

---

*Documentación generada el 27 de Abril de 2026 — ERP Estrategia e Innovación*
