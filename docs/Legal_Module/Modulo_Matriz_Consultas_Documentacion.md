# Módulo Legal — Matriz de Consultas y Escritos — Documentación Maestra
> **ERP Estrategia e Innovación** · Versión documental: Abril 2026  
> Audiencia: Abogados del área legal, administradores de contenido, desarrolladores

---

## Tabla de Contenido

1. [Visión General](#1-visión-general)
2. [Arquitectura del Módulo](#2-arquitectura-del-módulo)
3. [Modelo de Datos](#3-modelo-de-datos)
4. [Tipos de Proyecto: Consulta vs Escritos](#4-tipos-de-proyecto-consulta-vs-escritos)
5. [Referencia de Métodos — `MatrizConsultaController`](#5-referencia-de-métodos--matrizconsultacontroller)
6. [Flujo de Alta de un Proyecto](#6-flujo-de-alta-de-un-proyecto)
7. [Sistema de Archivos Adjuntos](#7-sistema-de-archivos-adjuntos)
8. [Búsqueda y Filtros](#8-búsqueda-y-filtros)
9. [Categorías en Vuelo (Alta Rápida)](#9-categorías-en-vuelo-alta-rápida)
10. [Proxy de Descarga Segura](#10-proxy-de-descarga-segura)
11. [Referencia de Rutas](#11-referencia-de-rutas)
12. [Historial de Migraciones](#12-historial-de-migraciones)
13. [Guía de Mantenimiento del Módulo](#13-guía-de-mantenimiento-del-módulo)

---

## 1. Visión General

La **Matriz de Consultas** es el núcleo documental del módulo Legal. Centraliza dos tipos de expedientes legales:

- **Consultas**: Preguntas o análisis legales solicitados por una empresa o cliente, con su resolución documentada.
- **Escritos**: Documentos legales formales (demandas, respuestas, recursos) organizados por empresa y categoría.

Cada expediente (`LegalProyecto`) puede tener múltiples archivos adjuntos (`LegalArchivo`): documentos PDF, Word, Excel, imágenes, o bien enlaces externos a sistemas gubernamentales.

### Propósito de negocio

| Necesidad | Solución |
|---|---|
| Centralizar expedientes legales por empresa | `LegalProyecto` con campo `empresa` y `tipo` |
| Clasificar consultas por materia | `LegalCategoria` jerárquica (categoría → subcategoría) |
| Adjuntar documentos de soporte | `LegalArchivo` con storage en `Storage::disk('public')` |
| Buscar por empresa, keyword o categoría | Filtros GET en `index()` |
| Descargar documentos de forma segura | Proxy `downloadArchivo()` |
| Consultar un expediente vía API | `show()` responde JSON si `expectsJson()` |

---

## 2. Arquitectura del Módulo

```
┌─────────────────────────────────────────────────────────────────┐
│                  MATRIZ DE CONSULTAS LEGAL                      │
│                                                                 │
│  Middleware: auth + verified + area.legal                       │
│  Prefijo URL: /legal/matriz                                     │
│                                                                 │
│  MatrizConsultaController                                       │
│  ┌───────────────────────────────────────────────────────┐     │
│  │  index()  → Listado con filtros (empresa/tipo/cat/kw) │     │
│  │  store()  → Alta + archivos + categoría al vuelo      │     │
│  │  show()   → Detalle (HTML o JSON según Accept header) │     │
│  │  update() → Edición de campos del proyecto            │     │
│  │  destroy()→ Elimina proyecto + archivos físicos       │     │
│  │  destroyArchivo() → Elimina un archivo individual     │     │
│  │  downloadArchivo()→ Descarga segura via proxy         │     │
│  └───────────────────────────────────────────────────────┘     │
│                                                                 │
│  Modelos (BD ERP principal)                                     │
│  ─────────────────────────                                      │
│  LegalProyecto ──── belongsTo ──→ LegalCategoria                │
│       └──── hasMany ──────────→ LegalArchivo                    │
│                                                                 │
│  Storage (disco 'public')                                       │
│  ─────────────────────────                                      │
│  storage/app/public/legal/archivos/{proyecto_id}/               │
└─────────────────────────────────────────────────────────────────┘
```

---

## 3. Modelo de Datos

### `LegalProyecto` — Tabla `legal_proyectos`

| Campo | Tipo | Nullable | Descripción |
|---|---|---|---|
| `id` | `bigint` PK | No | |
| `empresa` | `varchar(255)` | No | Nombre de la empresa o cliente principal |
| `tipo` | `enum/varchar` | No | `consulta` o `escritos` |
| `categoria_id` | `bigint` FK | Sí | FK → `legal_categorias.id` |
| `cliente` | `varchar(255)` | Sí | Cliente final (puede diferir de empresa) |
| `consulta` | `text` | Sí | Descripción o texto de la consulta/escrito |
| `resultado` | `text` | Sí | Resolución, respuesta o resultado del expediente |
| `detalles` | `text` | Sí | Información adicional del expediente |
| `created_at` | `timestamp` | | |
| `updated_at` | `timestamp` | | |

**Relaciones**:
- `categoria()` → `BelongsTo LegalCategoria`
- `archivos()` → `HasMany LegalArchivo`

### `LegalArchivo` — Tabla `legal_archivos`

| Campo | Tipo | Nullable | Descripción |
|---|---|---|---|
| `id` | `bigint` PK | No | |
| `proyecto_id` | `bigint` FK | No | FK → `legal_proyectos.id` |
| `nombre` | `varchar(255)` | No | Nombre descriptivo del archivo |
| `tipo` | `varchar` | No | `pdf`, `word`, `excel`, `imagen`, `otro` |
| `ruta` | `varchar` | No | Ruta relativa en `Storage::disk('public')` o URL externa si `es_url=true` |
| `es_url` | `boolean` | No | `false` = archivo subido; `true` = enlace externo |
| `mime_type` | `varchar` | Sí | MIME type del archivo (ej: `application/pdf`) |
| `created_at` | `timestamp` | | |
| `updated_at` | `timestamp` | | |

**Accessor**:

```php
// URL pública para el archivo
$archivo->url_publica
// Si es_url=true: retorna $archivo->ruta directamente (el enlace externo)
// Si es_url=false: retorna asset('storage/' . $archivo->ruta)
```

### Detección automática de tipo por extensión

```php
private function detectarTipo(string $ext): string
{
    return match ($ext) {
        'pdf'                    => 'pdf',
        'doc', 'docx'            => 'word',
        'xls', 'xlsx'            => 'excel',
        'jpg', 'jpeg', 'png',
        'gif', 'webp'            => 'imagen',
        default                  => 'otro',
    };
}
```

---

## 4. Tipos de Proyecto: Consulta vs Escritos

El campo `tipo` en `LegalProyecto` define la naturaleza del expediente y afecta cómo se presenta en la matriz.

| Aspecto | `consulta` | `escritos` |
|---|---|---|
| Propósito | Preguntas legales con respuesta | Documentos formales y trámites |
| Campo `empresa` | Empresa que consulta | Nombre del proyecto o parte |
| Selector en listado | `$empresas` (distinct de consultas) | `$proyectosNombres` (distinct de escritos) |
| Categorías disponibles | Solo categorías con `tipo = 'consulta'` | Solo categorías con `tipo = 'escritos'` |
| Filtro URL | `?tipo=consulta` | `?tipo=escritos` |

### Segregación de categorías en el índice

```php
$categoriasConsultas = $categorias->filter(fn($c) => $c->tipo === 'consulta')->values();
$categoriasEscritos  = $categorias->filter(fn($c) => $c->tipo === 'escritos')->values();
```

El frontend muestra el selector de categorías correspondiente al tipo seleccionado.

---

## 5. Referencia de Métodos — `MatrizConsultaController`

**Archivo**: `app/Http/Controllers/Legal/MatrizConsultaController.php`

---

### `index(Request $request): View`

**Ruta**: `GET /legal/matriz`

Carga todos los proyectos aplicando los filtros GET y envía a la vista:

| Variable | Descripción |
|---|---|
| `$proyectos` | Collection de `LegalProyecto` con `categoria.parent` y `archivos` |
| `$categorias` | Categorías raíz con subcategorías cargadas |
| `$categoriasConsultas` | Filtrado: solo categorías `tipo = 'consulta'` |
| `$categoriasEscritos` | Filtrado: solo categorías `tipo = 'escritos'` |
| `$empresas` | Empresas distintas que tienen proyectos de tipo `consulta` |
| `$proyectosNombres` | Empresas distintas que tienen proyectos de tipo `escritos` |

**Parámetros de filtro**:

| Parámetro GET | Efecto |
|---|---|
| `empresa` | `WHERE empresa LIKE '%valor%'` |
| `buscar` | `WHERE empresa LIKE '%kw%' OR consulta LIKE '%kw%'` |
| `categoria_id` | Incluye la categoría y todas sus subcategorías |
| `tipo` | `WHERE tipo = 'consulta'` o `WHERE tipo = 'escritos'` (`todos` = sin filtro) |

### Filtro de categoría con subcategorías

```php
if ($request->filled('categoria_id')) {
    $catId = $request->categoria_id;
    $subcatIds = LegalCategoria::where('parent_id', $catId)->pluck('id');
    $ids = $subcatIds->prepend($catId);
    $query->whereIn('categoria_id', $ids);
}
```

---

### `store(Request $request): RedirectResponse`

**Ruta**: `POST /legal/matriz`

Crea un proyecto con sus archivos adjuntos. Dos flujos para la categoría:

**Flujo 1: Categoría existente**
```
$categoriaId = $request->categoria_id  // ID numérico de categoría existente
```

**Flujo 2: Nueva categoría "al vuelo"**
```
$request->categoria_id === '__nueva__'  AND  $request->nueva_categoria_nombre
→ LegalCategoria::create(['nombre' => $request->nueva_categoria_nombre])
→ $categoriaId = $nuevaCategoria->id
```

**Validación**:
```php
'empresa'        => 'required|string|max:255',
'tipo'           => 'required|in:consulta,escritos',
'cliente'        => 'nullable|string|max:255',
'consulta'       => 'nullable|string',
'resultado'      => 'nullable|string',
'detalles'       => 'nullable|string',
'archivos_file.*'=> 'nullable|file|max:20480|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,webp',
```

**Almacenamiento de archivos**:
```php
$ruta = $file->store("legal/archivos/{$proyecto->id}", 'public');
// → storage/app/public/legal/archivos/{id}/{filename}.{ext}
// → URL pública: /storage/legal/archivos/{id}/{filename}.{ext}
```

Nombre del archivo en BD: usa `archivos_nombre.{index}` del request; si no se envió, usa el nombre original del archivo.

---

### `show($id): View|JsonResponse`

**Ruta**: `GET /legal/matriz/{id}`

Comportamiento dual según el header `Accept`:

```php
if (request()->expectsJson()) {
    return response()->json(['proyecto' => [...]]);
}
return view('Legal.matriz-consulta.show', compact('proyecto'));
```

**Respuesta JSON**:
```json
{
  "proyecto": {
    "id": 5,
    "empresa": "Importadora XYZ",
    "tipo": "consulta",
    "cliente": "Juan Pérez",
    "categoria_id": 3,
    "categoria": "Comercio Exterior",
    "consulta": "Texto de la consulta...",
    "resultado": "Resolución...",
    "detalles": "Información adicional...",
    "archivos": [
      {
        "id": 12,
        "nombre": "Dictamen VUCEM",
        "tipo": "pdf",
        "es_url": false,
        "url_publica": "https://erp.com/storage/legal/archivos/5/abc.pdf",
        "ruta": "legal/archivos/5/abc.pdf"
      }
    ]
  }
}
```

---

### `update(Request $request, $id): RedirectResponse`

**Ruta**: `PUT /legal/matriz/{id}`

Actualiza solo los campos textuales del proyecto. **No actualiza archivos adjuntos** (para eso, usar `destroyArchivo()` y el modal de archivos).

Diferencia con `store()`: La categoría es **requerida** en `update()` (`'categoria_id' => 'required|exists:legal_categorias,id'`) y no acepta `__nueva__`.

---

### `destroy($id): RedirectResponse`

**Ruta**: `DELETE /legal/matriz/{id}`

Elimina el proyecto y todos sus archivos físicos del storage:

```php
foreach ($proyecto->archivos as $archivo) {
    if (! $archivo->es_url && Storage::disk('public')->exists($archivo->ruta)) {
        Storage::disk('public')->delete($archivo->ruta);
    }
}
$proyecto->delete();
```

Los archivos marcados como `es_url = true` (enlaces externos) no se intenta eliminar del disco.

---

### `destroyArchivo($id): JsonResponse`

**Ruta**: `DELETE /legal/matriz/archivo/{id}`

Elimina un archivo individual de forma asíncrona (AJAX). Elimina el archivo físico si existe en el disco y el registro en BD.

Retorna: `{ "success": true }`

---

### `downloadArchivo($id): Response`

**Ruta**: `GET /legal/matriz/archivo/{id}/download`

Proxy de descarga segura. Ver [Sección 10](#10-proxy-de-descarga-segura).

---

## 6. Flujo de Alta de un Proyecto

```
Usuario accede a /legal/matriz → botón "Nuevo proyecto"
                │
                ▼
Modal de alta. El usuario llena:
  - empresa (obligatorio)
  - tipo: consulta / escritos (obligatorio)
  - cliente (opcional)
  - categoria_id: ID existente O '__nueva__' con nombre
  - consulta: texto libre del expediente
  - resultado: resolución
  - detalles: información adicional
  - archivos_file[]: hasta N archivos (PDF, Word, Excel, imágenes)
  - archivos_nombre[]: nombre descriptivo para cada archivo
                │
                ▼
POST /legal/matriz
  → Validar campos
  → Si categoria_id === '__nueva__':
      LegalCategoria::create({nombre, tipo = null}) → obtener ID
  → Validar que la categoría exista
  → LegalProyecto::create(...)
  → Foreach archivos_file:
      $ruta = $file->store("legal/archivos/{id}", 'public')
      LegalArchivo::create({proyecto_id, nombre, tipo, ruta, es_url=false, mime_type})
                │
redirect → /legal/matriz
  with('success', 'Proyecto "empresa" agregado correctamente.')
```

> **Riesgo actual**: No hay `DB::transaction()`. Si un archivo falla al guardarse en disco después de que el `LegalProyecto` ya se creó, el proyecto queda sin ese archivo pero sin notificación de error.

---

## 7. Sistema de Archivos Adjuntos

### Almacenamiento

Los archivos se guardan en `Storage::disk('public')` bajo la ruta:
```
storage/app/public/legal/archivos/{proyecto_id}/{nombre_unico}
```

URL pública:
```
https://tudominio.com/storage/legal/archivos/{proyecto_id}/{nombre_unico}
```

> **Requisito**: El enlace simbólico `public/storage` debe existir. Crearlo con:
> ```bash
> php artisan storage:link
> ```

### Tipos de archivo soportados

| Extensión | Tipo guardado | MIME esperado |
|---|---|---|
| `.pdf` | `pdf` | `application/pdf` |
| `.doc`, `.docx` | `word` | `application/msword`, `application/vnd.openxmlformats...` |
| `.xls`, `.xlsx` | `excel` | `application/vnd.ms-excel`, `application/vnd.openxmlformats...` |
| `.jpg`, `.jpeg`, `.png`, `.gif`, `.webp` | `imagen` | `image/*` |
| Otros | `otro` | Cualquier MIME válido |

Tamaño máximo por archivo: **20 MB** (`max:20480`)

### Archivos como URL externa

Si `es_url = true`, el campo `ruta` almacena una URL completa (ej: link a VUCEM, SIAT, etc.). La descarga redirige directamente a esa URL.

---

## 8. Búsqueda y Filtros

La vista del listado soporta los siguientes filtros acumulativos (todos vía GET):

| Parámetro | Input esperado | Comportamiento |
|---|---|---|
| `tipo` | `consulta`, `escritos`, `todos` | Filtra por tipo. `todos` = sin filtro de tipo |
| `empresa` | Texto libre | `LIKE '%empresa%'` en el campo `empresa` |
| `buscar` | Texto libre | `LIKE` en `empresa` y `consulta` simultáneamente |
| `categoria_id` | ID numérico | Incluye la categoría y sus subcategorías directas |

**Nota**: `empresa` y `buscar` son filtros independientes. Si se usan ambos, se aplican en AND (la query aplica ambas condiciones).

### Ordenamiento

Los proyectos se retornan ordenados por:
1. `empresa` ASC (alfabético)
2. `created_at` DESC (más recientes primero dentro de la misma empresa)

---

## 9. Categorías en Vuelo (Alta Rápida)

En el formulario de alta, el usuario puede crear una categoría nueva sin salir del modal de alta de proyecto:

```html
<!-- Selector especial con valor mágico -->
<option value="__nueva__">+ Crear nueva categoría</option>
```

```php
// En store()
if ($request->categoria_id === '__nueva__' && $request->filled('nueva_categoria_nombre')) {
    $nuevaCategoria = LegalCategoria::create([
        'nombre' => $request->nueva_categoria_nombre,
        // Nota: 'tipo' no se asigna aquí — queda null
    ]);
    $categoriaId = $nuevaCategoria->id;
}
```

> **Deuda técnica**: Las categorías creadas al vuelo desde `store()` no tienen `tipo` asignado (`null`). Esto puede causar que no aparezcan en el filtro segregado por tipo (`$categoriasConsultas` vs `$categoriasEscritos`) hasta que se editen desde el panel de categorías.

---

## 10. Proxy de Descarga Segura

**Ruta**: `GET /legal/matriz/archivo/{id}/download`

El archivo no es directamente accesible via URL pública a pesar de estar en `storage/public`. La descarga va a través del controlador para:
- Verificar que el usuario tiene sesión activa y permiso `area.legal`
- Preservar el nombre descriptivo original (no el nombre técnico de almacenamiento)

```php
public function downloadArchivo($id)
{
    $archivo = LegalArchivo::findOrFail($id);

    // Si es URL externa, redirigir directamente
    if ($archivo->es_url) {
        return redirect($archivo->ruta);
    }

    // Verificar que existe el archivo físico
    if (! Storage::disk('public')->exists($archivo->ruta)) {
        abort(404, 'Archivo no encontrado.');
    }

    // Calcular nombre de descarga preservando la extensión
    $extension = pathinfo($archivo->ruta, PATHINFO_EXTENSION);
    $nombreBase = pathinfo($archivo->nombre, PATHINFO_FILENAME) ?: $archivo->nombre;
    $nombreDescarga = $extension ? "{$nombreBase}.{$extension}" : $archivo->nombre;

    return Storage::disk('public')->download($archivo->ruta, $nombreDescarga);
}
```

> **Nota de seguridad**: La ruta usa `Storage::disk('public')->download()`, que internamente valida que el path esté dentro del disco público. No hay riesgo de path traversal si se usa correctamente el disco.

---

## 11. Referencia de Rutas

**Middleware**: `auth`, `verified`, `area.legal`  
**Prefijo**: `/legal`  
**Nombre base**: `legal.`

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `GET` | `/legal/matriz` | `legal.matriz.index` | Listado con filtros |
| `POST` | `/legal/matriz` | `legal.matriz.store` | Crear proyecto + archivos |
| `GET` | `/legal/matriz/{id}` | `legal.matriz.show` | Detalle (HTML o JSON) |
| `PUT` | `/legal/matriz/{id}` | `legal.matriz.update` | Actualizar campos textuales |
| `DELETE` | `/legal/matriz/{id}` | `legal.matriz.destroy` | Eliminar proyecto + archivos |
| `DELETE` | `/legal/matriz/archivo/{id}` | `legal.matriz.archivo.destroy` | Eliminar un archivo (AJAX) |
| `GET` | `/legal/matriz/archivo/{id}/download` | `legal.matriz.archivo.download` | Descargar archivo |

---

## 12. Historial de Migraciones

| Fecha | Archivo | Cambio |
|---|---|---|
| 2026-03-26 | `create_legal_categorias_table.php` | Crea `legal_categorias` con `parent_id` reflexivo |
| 2026-03-26 | `create_legal_proyectos_table.php` | Crea `legal_proyectos` con `empresa`, `categoria_id`, `consulta`, `resultado` |
| 2026-03-26 | `create_legal_archivos_table.php` | Crea `legal_archivos` con `proyecto_id`, `ruta`, `es_url`, `mime_type` |
| 2026-04-10 | `make_empresa_resultado_nullable_in_legal_proyectos.php` | Hace `empresa` y `resultado` nullable para mayor flexibilidad |
| 2026-04-13 | `add_tipo_to_legal_proyectos_table.php` | Añade `tipo` (consulta/escritos) al proyecto |
| 2026-04-13 | `add_cliente_detalles_to_legal_proyectos_table.php` | Añade `cliente` y `detalles` al proyecto |
| 2026-04-17 | `add_tipo_to_legal_categorias_table.php` | Añade `tipo` a las categorías para segregar entre consultas y escritos |

---

## 13. Guía de Mantenimiento del Módulo

---

### 🔴 CRÍTICO: No hay transacción atómica en `store()`

Si el servidor falla después de crear el `LegalProyecto` pero antes de guardar todos los archivos, quedan proyectos "huérfanos" sin archivos o archivos físicos sin registro en BD.

**Solución recomendada**:
```php
DB::transaction(function () use ($request, $categoriaId) {
    $proyecto = LegalProyecto::create([...]);
    foreach ($request->file('archivos_file') as $index => $file) {
        $ruta = $file->store("legal/archivos/{$proyecto->id}", 'public');
        LegalArchivo::create([...]);
    }
});
```

---

### 🔴 CRÍTICO: Categorías creadas al vuelo sin `tipo`

Al usar `__nueva__` en `store()`, la categoría se crea sin `tipo`. Esto la deja fuera de los selectores segregados (`$categoriasConsultas`, `$categoriasEscritos`) en el índice.

**Fix**: En `store()`, pasar el tipo del proyecto a la categoría creada al vuelo:
```php
$nuevaCategoria = LegalCategoria::create([
    'nombre' => $request->nueva_categoria_nombre,
    'tipo'   => $request->tipo,  // ← añadir este campo
]);
```

---

### 🟡 IMPORTANTE: Los archivos físicos no se limpian si se actualiza una ruta

En `update()`, no se actualiza ni elimina archivos. Si el desarrollador agrega la capacidad de reemplazar archivos sin eliminar el registro anterior primero, los archivos viejos quedarán en disco sin referencia en BD (archivos huérfanos).

**Práctica recomendada**: Siempre eliminar el registro via `destroyArchivo()` antes de subir el reemplazo.

---

### 🟡 IMPORTANTE: Agregar un nuevo tipo de archivo soportado

1. Añadir la extensión al `mimes:` en la validación de `store()`:
   ```php
   'archivos_file.*' => 'nullable|file|max:20480|mimes:pdf,doc,docx,...,pptx',
   ```
2. Añadir el caso en `detectarTipo()`:
   ```php
   'ppt', 'pptx' => 'presentacion',
   ```
3. Actualizar el ícono o representación en la vista del listado y detalle.

---

### 🟢 SEGURO: Cambiar el límite de tamaño de archivo

Actualmente 20 MB (`max:20480`). Para cambiar:

1. En la validación de `store()`: `'archivos_file.*' => 'nullable|file|max:51200|mimes:...'` (50 MB = 51200 KB).
2. Verificar que `upload_max_filesize` y `post_max_size` en `php.ini` sean al menos tan grandes.

---

### Checklist de deploy para cambios en Matriz de Consultas

- [ ] ¿Se cambia la ruta de storage? Actualizar la ruta en `store()` y en `downloadArchivo()`.
- [ ] ¿Se añade un campo a `LegalProyecto`? Agregar a `$fillable`, a la validación, al `update()` y a la respuesta JSON del `show()`.
- [ ] ¿Se añade un tipo de archivo? Actualizar `mimes:` en validación y `detectarTipo()`.
- [ ] ¿Se ejecuta `storage:link`? Verificar en el servidor de producción que el enlace simbólico existe.
- [ ] ¿Se añade filtro al `index()`? Verificar que el filtro se aplica en AND con los existentes.

---

*Documentación generada el 27 de Abril de 2026 — ERP Estrategia e Innovación*
