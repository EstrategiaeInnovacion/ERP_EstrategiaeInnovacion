# Módulo Legal — Categorías Legales — Documentación Maestra
> **ERP Estrategia e Innovación** · Versión documental: Abril 2026  
> Audiencia: Administradores del área legal, desarrolladores

---

## Tabla de Contenido

1. [Visión General](#1-visión-general)
2. [Arquitectura del Módulo](#2-arquitectura-del-módulo)
3. [Modelo de Datos](#3-modelo-de-datos)
4. [Jerarquía de Categorías](#4-jerarquía-de-categorías)
5. [Segregación por Tipo](#5-segregación-por-tipo)
6. [Referencia de Métodos — `CategoriaLegalController`](#6-referencia-de-métodos--categorialegalcontroller)
7. [Interacción con la Matriz de Consultas](#7-interacción-con-la-matriz-de-consultas)
8. [Referencia de Rutas](#8-referencia-de-rutas)
9. [Historial de Migraciones](#9-historial-de-migraciones)
10. [Guía de Mantenimiento del Módulo](#10-guía-de-mantenimiento-del-módulo)

---

## 1. Visión General

El módulo de **Categorías Legales** gestiona la taxonomía que clasifica los expedientes de la Matriz de Consultas. Ofrece un sistema jerárquico de dos niveles (Categoría raíz → Subcategoría) con segregación por tipo (`consulta` vs `escritos`).

### Propósito de negocio

| Necesidad | Solución |
|---|---|
| Organizar consultas por materia legal | Categorías de tipo `consulta` |
| Organizar escritos por clase de documento | Categorías de tipo `escritos` |
| Agrupar categorías relacionadas | Relación padre-hijo (`parent_id`) |
| Crear categorías desde el flujo de alta | Alta "al vuelo" en `MatrizConsultaController::store()` |
| Eliminar categorías sin romper proyectos hijos | Promoción de subcategorías a raíz antes de borrar |

---

## 2. Arquitectura del Módulo

```
┌────────────────────────────────────────────────────────────────┐
│               CATEGORÍAS LEGALES                               │
│                                                                │
│  Middleware: auth + verified + area.legal                      │
│  Prefijo URL: /legal/categorias                                │
│                                                                │
│  CategoriaLegalController                                      │
│  ┌──────────────────────────────────────────────────────────┐ │
│  │  index()   → Listado jerárquico (raíces + subcategorías) │ │
│  │  store()   → Alta de categoría raíz con tipo obligatorio │ │
│  │  destroy() → Elimina; promueve subcategorías a raíz      │ │
│  └──────────────────────────────────────────────────────────┘ │
│                                                                │
│  LegalCategoria (auto-referencial)                             │
│  ┌──────────────────────────────────────────────────────────┐ │
│  │  Categoría raíz (parent_id = null, tipo = 'consulta')    │ │
│  │    └── Subcategoría (parent_id = ID raíz)                │ │
│  │  Categoría raíz (parent_id = null, tipo = 'escritos')    │ │
│  │    └── Subcategoría (parent_id = ID raíz)                │ │
│  └──────────────────────────────────────────────────────────┘ │
│                                                                │
│  Consumido por:                                                │
│  ─────────────                                                 │
│  MatrizConsultaController::index()  → filtro de proyectos      │
│  MatrizConsultaController::store()  → asignación de categoría  │
│  MatrizConsultaController::update() → reasignación             │
└────────────────────────────────────────────────────────────────┘
```

---

## 3. Modelo de Datos

### `LegalCategoria` — Tabla `legal_categorias`

| Campo | Tipo | Nullable | Descripción |
|---|---|---|---|
| `id` | `bigint` PK | No | |
| `nombre` | `varchar(255)` | No | Nombre descriptivo de la categoría |
| `tipo` | `varchar` | Sí | `consulta` o `escritos` — determina en qué selector aparece |
| `parent_id` | `bigint` FK | Sí | FK reflexiva → `legal_categorias.id`. `null` = categoría raíz |
| `created_at` | `timestamp` | | |
| `updated_at` | `timestamp` | | |

**Relaciones**:

```php
// Categoría padre
public function parent(): BelongsTo
{
    return $this->belongsTo(LegalCategoria::class, 'parent_id');
}

// Subcategorías hijas
public function subcategorias(): HasMany
{
    return $this->hasMany(LegalCategoria::class, 'parent_id');
}

// Proyectos asignados a esta categoría
public function proyectos(): HasMany
{
    return $this->hasMany(LegalProyecto::class, 'categoria_id');
}
```

---

## 4. Jerarquía de Categorías

El sistema soporta exactamente **dos niveles** de jerarquía:

```
Comercio Exterior (raíz, tipo='consulta', parent_id=null)
  ├── Aranceles (subcategoría, parent_id=1)
  ├── Fracciones arancelarias (subcategoría, parent_id=1)
  └── Reglas de origen (subcategoría, parent_id=1)

Contratos (raíz, tipo='escritos', parent_id=null)
  ├── Contratos de arrendamiento (subcategoría, parent_id=5)
  └── Contratos de servicios (subcategoría, parent_id=5)
```

> **Límite de diseño**: No se soportan niveles superiores al segundo (no hay bisnietos). El sistema no valida esto a nivel de BD, pero la UI solo permite crear categorías raíz desde el panel; las subcategorías se crean de forma diferente.

### Carga en `index()`

```php
// Solo las raíces, con sus hijos cargados (eager)
$categorias = LegalCategoria::with('subcategorias')
    ->whereNull('parent_id')
    ->orderBy('nombre')
    ->get();

// Todas las categorías (para otros selectores o vistas)
$todasCategorias = LegalCategoria::orderBy('nombre')->get();
```

### Filtro de proyectos por categoría (en `MatrizConsultaController`)

Al filtrar proyectos por `categoria_id`, el sistema incluye automáticamente todos los proyectos de las subcategorías:

```php
$subcatIds = LegalCategoria::where('parent_id', $catId)->pluck('id');
$ids = $subcatIds->prepend($catId);
$query->whereIn('categoria_id', $ids);
```

---

## 5. Segregación por Tipo

El campo `tipo` en `LegalCategoria` determina en qué selector de la UI aparece la categoría:

| `tipo` | Uso en UI |
|---|---|
| `consulta` | Selector de categorías para proyectos de tipo `consulta` |
| `escritos` | Selector de categorías para proyectos de tipo `escritos` |
| `null` | Categoría creada "al vuelo" sin tipo — no aparece en ningún selector hasta que se le asigna tipo |

En el panel de Matriz de Consultas, las categorías se separan así:

```php
$categoriasConsultas = $categorias->filter(fn($c) => $c->tipo === 'consulta')->values();
$categoriasEscritos  = $categorias->filter(fn($c) => $c->tipo === 'escritos')->values();
```

---

## 6. Referencia de Métodos — `CategoriaLegalController`

**Archivo**: `app/Http/Controllers/Legal/CategoriaLegalController.php`

---

### `index(): View`

**Ruta**: `GET /legal/categorias`

Carga y envía a la vista:

| Variable | Descripción |
|---|---|
| `$categorias` | Categorías raíz con subcategorías eager-loaded, ordenadas alfabéticamente |
| `$todasCategorias` | Todas las categorías (raíces y subcategorías), sin estructura jerárquica |

---

### `store(Request $request): RedirectResponse`

**Ruta**: `POST /legal/categorias`

Crea una nueva **categoría raíz** (siempre con `parent_id = null`).

**Validación**:
```php
'nombre' => 'required|string|max:255',
'tipo'   => 'required|in:consulta,escritos',
```

**Flujo**:
```
POST /legal/categorias
    → Validar nombre y tipo
    → LegalCategoria::create({nombre, tipo, parent_id: null})
    → redirect → /legal/matriz?nueva_categoria={id}&nueva_categoria_tipo={tipo}
    → with('success', 'Categoría "{nombre}" creada. Ahora puedes crear un proyecto con ella.')
```

La redirección lleva al usuario de vuelta a la Matriz de Consultas con parámetros que el frontend puede usar para preseleccionar la categoría recién creada.

---

### `destroy($id): RedirectResponse`

**Ruta**: `DELETE /legal/categorias/{id}`

Elimina una categoría aplicando **promoción de subcategorías** antes del borrado:

```php
// 1. Promover subcategorías a categorías raíz
LegalCategoria::where('parent_id', $id)->update(['parent_id' => null]);

// 2. Eliminar la categoría raíz
$categoria->delete();
```

> **Comportamiento importante**: Los proyectos asignados a la categoría eliminada **no se desvinculan automáticamente**. Su `categoria_id` apunta a un registro que ya no existe (FK sin cascade). Esto puede generar errores al cargar el proyecto si no se maneja el `null` en la vista.
>
> Los proyectos asignados a las **subcategorías promovidas** continúan funcionando correctamente ya que las subcategorías siguen existiendo como categorías raíz.

Redirección: `GET /legal/categorias` con mensaje de éxito.

---

## 7. Interacción con la Matriz de Consultas

El módulo de Categorías es un satélite del módulo de Matriz de Consultas. Su ciclo de vida típico:

```
Panel de Categorías
    → Admin crea "Comercio Exterior" (tipo: consulta)
    → Admin crea "Contratos" (tipo: escritos)

Matriz de Consultas — Alta de proyecto
    → Usuario selecciona categoría existente  ─┐
    → Usuario usa "__nueva__" al vuelo         ─┤→ Se asigna categoria_id al proyecto
    
Matriz de Consultas — Filtrado
    → ?categoria_id=3 incluye proyectos de
      la categoría 3 Y sus subcategorías
```

### Categorías creadas "al vuelo" vs. creadas desde el panel

| Aspecto | Panel de Categorías (`store()`) | Al vuelo (`MatrizConsultaController::store()`) |
|---|---|---|
| Tipo asignado | Obligatorio (`in:consulta,escritos`) | **No asignado** (queda `null`) |
| Aparece en filtros de la Matriz | Sí (en el selector correcto según tipo) | No (hasta que se edite desde el panel) |
| Nivel jerárquico | Siempre raíz (`parent_id = null`) | Siempre raíz (`parent_id = null`) |
| Redirección post-creación | A `/legal/categorias` | A `/legal/matriz` |

---

## 8. Referencia de Rutas

**Middleware**: `auth`, `verified`, `area.legal`  
**Prefijo**: `/legal`  
**Nombre base**: `legal.`

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `GET` | `/legal/categorias` | `legal.categorias.index` | Listado jerárquico |
| `POST` | `/legal/categorias` | `legal.categorias.store` | Crear categoría raíz |
| `DELETE` | `/legal/categorias/{id}` | `legal.categorias.destroy` | Eliminar y promover subcategorías |

> No existe ruta `update` — el nombre de una categoría no se puede editar desde la UI. Para renombrar, se debe hacer directamente en la base de datos o agregar el endpoint.

---

## 9. Historial de Migraciones

| Fecha | Archivo | Cambio |
|---|---|---|
| 2026-03-26 | `create_legal_categorias_table.php` | Crea la tabla con `id`, `nombre`, `parent_id` (nullable FK a sí misma), `timestamps` |
| 2026-04-17 | `add_tipo_to_legal_categorias_table.php` | Añade `tipo` (nullable `varchar`) para segregar categorías entre `consulta` y `escritos` |

---

## 10. Guía de Mantenimiento del Módulo

---

### 🔴 CRÍTICO: Proyectos huérfanos al eliminar una categoría

Cuando se elimina una categoría raíz, los proyectos asignados directamente a ella (`categoria_id = {id}`) quedan con una FK inválida. La tabla `legal_proyectos.categoria_id` no tiene `ON DELETE SET NULL` ni `ON DELETE CASCADE`.

**Síntoma**: Error al cargar un proyecto cuya categoría fue eliminada.

**Solución definitiva**: Agregar `ON DELETE SET NULL` a la FK en la migración:
```php
$table->foreignId('categoria_id')->nullable()->constrained('legal_categorias')->nullOnDelete();
```
O bien, en `destroy()` desasociar los proyectos antes de eliminar:
```php
LegalProyecto::where('categoria_id', $id)->update(['categoria_id' => null]);
```

---

### 🔴 CRÍTICO: Categorías al vuelo sin tipo

El método `MatrizConsultaController::store()` crea categorías sin asignar el campo `tipo`, lo que las deja fuera de los selectores segregados.

**Fix aplicable directamente**:  
En `MatrizConsultaController::store()`:
```php
$nuevaCategoria = LegalCategoria::create([
    'nombre' => $request->nueva_categoria_nombre,
    'tipo'   => $request->tipo,  // ← usar el tipo del proyecto
]);
```

---

### 🟡 IMPORTANTE: No existe endpoint de edición de categoría

Si un administrador necesita renombrar una categoría o cambiar su `tipo`, actualmente no hay endpoint ni vista para ello.

**Agregar `update()`**:
```php
// En CategoriaLegalController
public function update(Request $request, $id)
{
    $categoria = LegalCategoria::findOrFail($id);
    $request->validate([
        'nombre' => 'required|string|max:255',
        'tipo'   => 'required|in:consulta,escritos',
    ]);
    $categoria->update($request->only('nombre', 'tipo'));
    return redirect()->route('legal.categorias.index')->with('success', 'Categoría actualizada.');
}
```
Y registrar la ruta: `Route::put('/categorias/{id}', [CategoriaLegalController::class, 'update'])->name('categorias.update');`

---

### 🟡 IMPORTANTE: No hay soporte para subcategorías desde la UI

Actualmente solo se pueden crear categorías raíz desde el panel. Las subcategorías solo existen si se crean directamente en BD.

**Para añadir soporte en `store()`**:
```php
$request->validate([
    'nombre'    => 'required|string|max:255',
    'tipo'      => 'required|in:consulta,escritos',
    'parent_id' => 'nullable|exists:legal_categorias,id',
]);
LegalCategoria::create($request->only('nombre', 'tipo', 'parent_id'));
```

---

### 🟢 SEGURO: Agregar un nuevo tipo de categoría

Si el negocio necesita un tercer tipo (ej: `contratos`):

1. Actualizar la validación en `CategoriaLegalController::store()`:
   ```php
   'tipo' => 'required|in:consulta,escritos,contratos',
   ```
2. Actualizar la validación en `MatrizConsultaController::store()`:
   ```php
   'tipo' => 'required|in:consulta,escritos,contratos',
   ```
3. Agregar la segregación en `MatrizConsultaController::index()`:
   ```php
   $categoriasContratos = $categorias->filter(fn($c) => $c->tipo === 'contratos')->values();
   ```
4. Actualizar la vista para mostrar el selector del nuevo tipo.

---

### Checklist de deploy para cambios en Categorías

- [ ] Si se añade `parent_id` a `store()`: Actualizar validación para solo aceptar categorías raíz como padre.
- [ ] Si se añade endpoint de edición: Registrar ruta `PUT /legal/categorias/{id}`.
- [ ] Si se añade un nuevo tipo: Actualizar validaciones en ambos controladores.
- [ ] Si se migra la FK con `nullOnDelete()`: Verificar que los modelos que accedan a `$proyecto->categoria` manejen `null`.

---

*Documentación generada el 27 de Abril de 2026 — ERP Estrategia e Innovación*
