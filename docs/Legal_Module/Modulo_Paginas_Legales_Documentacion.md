# Módulo Legal — Páginas y Programas Legales — Documentación Maestra
> **ERP Estrategia e Innovación** · Versión documental: Abril 2026  
> Audiencia: Administradores del área legal, desarrolladores

---

## Tabla de Contenido

1. [Visión General](#1-visión-general)
2. [Arquitectura del Módulo](#2-arquitectura-del-módulo)
3. [Modelo de Datos](#3-modelo-de-datos)
4. [Referencia de Métodos — `PaginaLegalController`](#4-referencia-de-métodos--paginalegalcontroller)
5. [Flujos de Operación](#5-flujos-de-operación)
6. [Referencia de Rutas](#6-referencia-de-rutas)
7. [Historial de Migraciones](#7-historial-de-migraciones)
8. [Guía de Mantenimiento del Módulo](#8-guía-de-mantenimiento-del-módulo)

---

## 1. Visión General

El módulo de **Páginas y Programas Legales** es un catálogo simple de enlazadores externos que el equipo Legal consulta frecuentemente. Cada entrada (`LegalPagina`) es un par **nombre + URL** que representa un sistema, portal gubernamental, o herramienta online relevante para el trabajo jurídico (ej: VUCEM, DOF, SAT, sistemas del IMSS, etc.).

> **Nota de nomenclatura**: En el código y las rutas, el recurso se llama `programas` (prefijo de ruta: `/legal/programas`). El modelo y tabla se llaman `LegalPagina` / `legal_paginas`. Ambos nombres se refieren al mismo concepto.

### Propósito de negocio

| Necesidad | Solución |
|---|---|
| Acceso rápido a portales y sistemas externos | Lista de `LegalPagina` con nombre y URL en la vista |
| Agregar nuevos sistemas sin ayuda de IT | CRUD completo disponible para usuarios con `area.legal` |
| Mantener URLs actualizadas | Endpoint `update()` para editar nombre y URL |
| Eliminar sistemas ya no utilizados | Endpoint `destroy()` |

---

## 2. Arquitectura del Módulo

```
┌──────────────────────────────────────────────────────────────┐
│              PÁGINAS Y PROGRAMAS LEGALES                     │
│                                                              │
│  Middleware: auth + verified + area.legal                    │
│  Prefijo URL: /legal/programas                               │
│                                                              │
│  PaginaLegalController                                       │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  index()   → Lista todos los programas/páginas         │ │
│  │  store()   → Alta: nombre + URL                        │ │
│  │  update()  → Edita nombre y/o URL de un registro       │ │
│  │  destroy() → Elimina el registro                       │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                              │
│  LegalPagina                                                 │
│  ┌────────────────────────────────────────────────────────┐ │
│  │  Tabla: legal_paginas                                  │ │
│  │  Campos: id, nombre, url, timestamps                   │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                              │
│  Sin relaciones con otros modelos del módulo Legal           │
└──────────────────────────────────────────────────────────────┘
```

---

## 3. Modelo de Datos

### `LegalPagina` — Tabla `legal_paginas`

| Campo | Tipo | Nullable | Descripción |
|---|---|---|---|
| `id` | `bigint` PK | No | |
| `nombre` | `varchar(255)` | No | Nombre descriptivo del sistema o portal |
| `url` | `varchar(2048)` | No | URL completa del sistema externo (requiere `http://` o `https://`) |
| `created_at` | `timestamp` | | |
| `updated_at` | `timestamp` | | |

**Fillable**:
```php
protected $fillable = ['nombre', 'url'];
```

**Sin relaciones** — `LegalPagina` es un modelo independiente sin FK hacia otros modelos.

### Ejemplos de uso

| nombre | url |
|---|---|
| `VUCEM` | `https://www.ventanillaunica.gob.mx` |
| `Portal SAT` | `https://www.sat.gob.mx/tramitesyservicios` |
| `DOF Online` | `https://www.dof.gob.mx` |
| `IMSS Empresa` | `https://serviciosdigitales.imss.gob.mx` |

---

## 4. Referencia de Métodos — `PaginaLegalController`

**Archivo**: `app/Http/Controllers/Legal/PaginaLegalController.php`

---

### `index(): View`

**Ruta**: `GET /legal/programas`

Carga todos los registros ordenados alfabéticamente por nombre:

```php
$paginas = LegalPagina::orderBy('nombre')->get();
return view('Legal.programas.index', compact('paginas'));
```

Vista: `resources/views/Legal/programas/index.blade.php`

---

### `store(Request $request): RedirectResponse`

**Ruta**: `POST /legal/programas`

Crea un nuevo programa/página.

**Validación**:
```php
'nombre' => 'required|string|max:255',
'url'    => 'required|url|max:2048',
```

**Mensajes de error personalizados**:
- `nombre.required`: `"El nombre del sistema es obligatorio."`
- `url.required`: `"La URL es obligatoria."`
- `url.url`: `"Ingresa una URL válida (debe incluir http:// o https://)."`

**Flujo**:
```
POST /legal/programas
    → Validar nombre y url
    → LegalPagina::create({nombre, url})
    → redirect → /legal/programas
    → with('success', 'Programa/Página "{nombre}" agregado correctamente.')
```

---

### `update(Request $request, $id): RedirectResponse`

**Ruta**: `PUT /legal/programas/{id}`

Actualiza el nombre y/o URL de un registro existente.

**Validación**: Idéntica a `store()`:
```php
'nombre' => 'required|string|max:255',
'url'    => 'required|url|max:2048',
```

```php
$pagina = LegalPagina::findOrFail($id);
$pagina->update($request->only('nombre', 'url'));
// → redirect → /legal/programas with('success', 'Actualizado correctamente.')
```

---

### `destroy($id): RedirectResponse`

**Ruta**: `DELETE /legal/programas/{id}`

Elimina un registro. No hay archivos físicos que limpiar.

```php
$pagina = LegalPagina::findOrFail($id);
$nombre = $pagina->nombre;
$pagina->delete();
// → redirect → /legal/programas with('success', '"{nombre}" eliminado correctamente.')
```

---

## 5. Flujos de Operación

### Alta de nuevo programa

```
Usuario en /legal/programas → botón "Agregar programa"
    │
    ▼
Modal o formulario: nombre + URL
    │
POST /legal/programas
    → Validar (nombre required, url required|url)
    → LegalPagina::create(...)
    │
redirect → /legal/programas + mensaje de éxito
```

### Edición de URL desactualizada

```
Usuario en listado → botón "Editar" en la fila
    │
    ▼
Formulario pre-poblado con nombre y URL actuales
    │
PUT /legal/programas/{id}
    → LegalPagina::findOrFail($id)
    → Validar
    → $pagina->update(...)
    │
redirect → /legal/programas + "Actualizado correctamente."
```

### Eliminación

```
Usuario en listado → botón "Eliminar"
    │
DELETE /legal/programas/{id}
    → LegalPagina::findOrFail($id)
    → $pagina->delete()
    │
redirect → /legal/programas + '"{nombre}" eliminado correctamente.'
```

---

## 6. Referencia de Rutas

**Middleware**: `auth`, `verified`, `area.legal`  
**Prefijo**: `/legal`  
**Nombre base**: `legal.`

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `GET` | `/legal/programas` | `legal.programas.index` | Listado de programas/páginas |
| `POST` | `/legal/programas` | `legal.programas.store` | Crear nuevo programa |
| `PUT` | `/legal/programas/{id}` | `legal.programas.update` | Actualizar programa existente |
| `DELETE` | `/legal/programas/{id}` | `legal.programas.destroy` | Eliminar programa |

---

## 7. Historial de Migraciones

| Fecha | Archivo | Cambio |
|---|---|---|
| 2026-03-26 | `create_legal_paginas_table.php` | Crea la tabla `legal_paginas` con `id`, `nombre` (string), `url` (string), `timestamps` |

---

## 8. Guía de Mantenimiento del Módulo

---

### 🟡 IMPORTANTE: La URL no se valida como accesible

La validación `url` de Laravel solo verifica que el string tenga el formato correcto de URL (protocolo, dominio). No comprueba que el destino esté activo o accesible.

Si una URL cambia de dominio o se da de baja, el registro queda con una URL rota hasta que un usuario la detecte manualmente.

**Mejora posible**: Añadir un comando de consola periódico que haga HEAD requests a todas las URLs y notifique si alguna falla:
```php
// app/Console/Commands/CheckLegalPaginasHealth.php
$paginas = LegalPagina::all();
foreach ($paginas as $pagina) {
    $response = Http::timeout(10)->head($pagina->url);
    if (!$response->successful()) {
        // Notificar al equipo Legal
    }
}
```

---

### 🟡 IMPORTANTE: No hay validación de URLs duplicadas

Es posible agregar el mismo sistema dos veces con nombres ligeramente diferentes. No hay restricción `UNIQUE` en la columna `url`.

**Fix**: Agregar restricción `unique:legal_paginas,url` en la validación:
```php
'url' => 'required|url|max:2048|unique:legal_paginas,url',
```
Y para el `update()`:
```php
'url' => 'required|url|max:2048|unique:legal_paginas,url,' . $id,
```

---

### 🟢 SEGURO: Añadir descripción o categoría a los programas

Si el número de programas crece, puede ser útil categorizarlos (ej: "SAT", "IMSS", "VUCEM", "Interne"):

1. Añadir migración: `$table->string('categoria')->nullable();`
2. Añadir `'categoria'` al `$fillable` del modelo.
3. Añadir la validación en `store()` y `update()`.
4. Actualizar la vista para mostrar el filtro por categoría.

---

### 🟢 SEGURO: Cambiar el ordenamiento

Actualmente se ordena alfabéticamente por `nombre`. Para cambiar a más reciente primero:
```php
$paginas = LegalPagina::orderBy('created_at', 'desc')->get();
```

---

### Checklist de deploy para cambios en Páginas Legales

- [ ] Si se añade campo `categoria`: Crear migración, actualizar `$fillable`, actualizar validación y vistas.
- [ ] Si se añade validación unique en `url`: Verificar datos existentes que puedan estar duplicados antes de aplicar la migración.
- [ ] Si se cambia el nombre de la ruta de `programas` a `paginas`: Actualizar todas las referencias en vistas y controladores.

---

*Documentación generada el 27 de Abril de 2026 — ERP Estrategia e Innovación*
