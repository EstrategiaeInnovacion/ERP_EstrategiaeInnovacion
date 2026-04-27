# Módulo RH — Capacitación — Documentación Maestra
> **ERP Estrategia e Innovación** · Versión documental: Abril 2026  
> Audiencia: Recursos Humanos, empleados, desarrolladores

---

## Tabla de Contenido

1. [Visión General](#1-visión-general)
2. [Arquitectura del Módulo](#2-arquitectura-del-módulo)
3. [Modelo de Datos](#3-modelo-de-datos)
4. [Sistema de Visibilidad: ¿Quién ve qué?](#4-sistema-de-visibilidad-quién-ve-qué)
5. [Referencia de Métodos — `CapacitacionController`](#5-referencia-de-métodos--capacitacioncontroller)
6. [Manejo de Adjuntos](#6-manejo-de-adjuntos)
7. [Soporte de Video: YouTube y Archivo Local](#7-soporte-de-video-youtube-y-archivo-local)
8. [Referencia de Rutas](#8-referencia-de-rutas)
9. [Historial de Migraciones](#9-historial-de-migraciones)
10. [Guía de Mantenimiento del Módulo](#10-guía-de-mantenimiento-del-módulo)

---

## 1. Visión General

El módulo de **Capacitación** es una plataforma interna de formación continua. Permite al área de RH publicar cursos en video (YouTube o archivo MP4), adjuntar materiales de estudio (PDF, presentaciones), y controlar qué empleados pueden ver cada curso mediante reglas de **puestos permitidos** o **usuarios específicos**.

### Propósito de negocio

| Necesidad | Solución |
|---|---|
| Publicar cursos en video para el personal | `Capacitacion` con `youtube_url` o `archivo_path` |
| Restringir cursos por puesto laboral | `puestos_permitidos` — array JSON de posiciones |
| Restringir cursos a usuarios individuales | `usuarios_permitidos` — array JSON de IDs de usuario |
| Subir materiales complementarios | `CapacitacionAdjunto` — PDFs, presentaciones |
| Empleados ven solo lo que les corresponde | `isVisibleFor($user)` en el modelo, filtrado en PHP |
| RH administra el catálogo completo | Panel `manage()` con acceso total |

---

## 2. Arquitectura del Módulo

```
┌──────────────────────────────────────────────────────────────────┐
│                       CAPACITACIÓN                               │
│                                                                  │
│  Rutas protegidas: auth + area.rh                                │
│  Prefijo URL: /recursos-humanos/capacitacion                     │
│  Nombre base: rh.capacitacion.                                   │
│                                                                  │
│  CapacitacionController                                          │
│  ┌────────────────────────────────────────────────────────────┐  │
│  │  [EMPLEADOS — usuarios autenticados]                       │  │
│  │  index()     → Galería filtrada por visibilidad del usuario │  │
│  │  show($id)   → Vista del curso (verifica permiso)          │  │
│  │                                                            │  │
│  │  [ADMINISTRACIÓN — área de RH]                             │  │
│  │  manage()    → Panel de gestión de todos los cursos        │  │
│  │  store()     → Crear curso (video + adjuntos)              │  │
│  │  edit($id)   → Formulario de edición                       │  │
│  │  update($id) → Actualizar curso (reemplazar video/adjuntos)│  │
│  │  destroy($id)→ Eliminar curso + archivos físicos           │  │
│  │  destroyAdjunto($id) → Eliminar un adjunto individual      │  │
│  └────────────────────────────────────────────────────────────┘  │
│                                                                  │
│  Modelos                                                         │
│  ──────                                                           │
│  Capacitacion        → Curso con video, audiencia, categoría     │
│  CapacitacionAdjunto → Archivos complementarios del curso        │
└──────────────────────────────────────────────────────────────────┘
```

---

## 3. Modelo de Datos

### `Capacitacion` — Tabla `capacitaciones`

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | `bigint` PK | |
| `titulo` | `varchar(255)` | Título del curso |
| `descripcion` | `text` nullable | Descripción larga |
| `categoria` | `varchar(255)` nullable | Agrupador (ej: "Seguridad", "Operaciones") |
| `puestos_permitidos` | `json` nullable | Array de nombres de posición (ej: `["Ejecutivo", "Coordinador"]`) |
| `usuarios_permitidos` | `json` nullable | Array de IDs de usuario (ej: `[5, 12, 34]`) |
| `archivo_path` | `varchar` nullable | Ruta al MP4 en disco `public` |
| `youtube_url` | `varchar` nullable | URL de YouTube (si es externo) |
| `subido_por` | `bigint` FK | FK → `users.id` |
| `activo` | `boolean` default true | Si aparece en el catálogo |
| `created_at` / `updated_at` | `timestamp` | |

**Relaciones**:
```php
adjuntos()  → HasMany CapacitacionAdjunto
subidoPor() → BelongsTo User
```

**Método clave del modelo**: `isVisibleFor(User $user): bool`
```php
// Lógica de visibilidad (simplificada):
public function isVisibleFor(User $user): bool
{
    // RH y admins ven todo
    if ($user->hasRole(['admin', 'rh'])) return true;
    
    // Sin restricciones → visible para todos
    if (empty($this->puestos_permitidos) && empty($this->usuarios_permitidos)) return true;
    
    // Si tiene usuarios_permitidos y el usuario está en la lista
    if (!empty($this->usuarios_permitidos) && in_array($user->id, $this->usuarios_permitidos)) return true;
    
    // Si tiene puestos_permitidos y el empleado tiene ese puesto
    if (!empty($this->puestos_permitidos) && $user->empleado) {
        return in_array($user->empleado->posicion, $this->puestos_permitidos);
    }
    
    return false;
}
```

### `CapacitacionAdjunto` — Tabla `capacitacion_adjuntos`

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | `bigint` PK | |
| `capacitacion_id` | `bigint` FK | FK → `capacitaciones.id` |
| `titulo` | `varchar(255)` | Nombre original del archivo |
| `archivo_path` | `varchar` | Ruta en disco `public` |
| `created_at` / `updated_at` | `timestamp` | |

---

## 4. Sistema de Visibilidad: ¿Quién ve qué?

El modelo `Capacitacion` tiene un método `isVisibleFor(User $user)` que implementa la lógica de acceso:

```
REGLAS DE VISIBILIDAD (en orden de prioridad):

1. Admin / RH → Ve TODOS los cursos (sin importar restricciones)

2. Curso sin restricciones (puestos_permitidos=null Y usuarios_permitidos=null)
   → TODOS los empleados lo ven

3. Curso con usuarios_permitidos no vacío
   → Solo los usuarios en el array (acceso individual por ID)

4. Curso con puestos_permitidos no vacío
   → Solo empleados cuyo campo `posicion` esté en el array

5. Si ninguna regla aplica → NO visible
```

**Uso en el listado** (`index()`):
```php
$videos = Capacitacion::where('activo', true)->get();
$filteredVideos = $videos->filter(fn($v) => $v->isVisibleFor($user));
```

> **Nota de rendimiento**: El filtrado se hace en PHP sobre toda la colección. Con muchos cursos, considerar filtrar en BD.

**Uso en el detalle** (`show()`):
```php
if (!$video->isVisibleFor(Auth::user())) {
    abort(403, 'No tienes permiso para ver esta capacitación.');
}
```

---

## 5. Referencia de Métodos — `CapacitacionController`

**Archivo**: `app/Http/Controllers/RH/CapacitacionController.php`

---

### `index(): View`

**Ruta**: `GET /recursos-humanos/capacitacion` (solo RH — la ruta está en el grupo `area.rh`)

Galería de cursos visible para el usuario autenticado. Los cursos se agrupan por `categoria`:

```php
$groupedVideos = $filteredVideos->groupBy(function ($item) {
    return $item->categoria ?: 'General'; // null/vacío → 'General'
});
```

Retorna: `view('Recursos_Humanos.capacitacion.index', compact('groupedVideos'))`

---

### `show($id): View`

**Ruta**: `GET /recursos-humanos/capacitacion/{id}`

Carga el curso con sus adjuntos (`with('adjuntos')`). Si el usuario no tiene permiso, retorna 403.

Distingue entre video de YouTube y archivo local para que la vista renderice el embed correcto:
- YouTube: usa `youtube_url` para el iframe
- Archivo local: usa `archivo_path` para el `<video>` HTML5

---

### `manage(): View`

**Ruta**: `GET /recursos-humanos/capacitacion/gestion`

Panel de administración solo para RH. Carga:
- Todos los cursos (sin filtro de visibilidad)
- Lista de posiciones únicas de empleados activos (para el selector de puestos)
- Lista de usuarios del sistema (para el selector de usuarios individuales)

---

### `store(Request $request): RedirectResponse`

**Ruta**: `POST /recursos-humanos/capacitacion/subir`

Validación:
```php
'titulo'              => 'required|string|max:255',
'youtube_url'         => 'nullable|url',
'video'               => 'nullable|mimes:mp4,mov,ogg,qt|max:200000', // 200 MB
'adjuntos.*'          => 'nullable|file|max:10240',
'puestos_permitidos'  => 'nullable|array',
'usuarios_permitidos' => 'nullable|array',
```

Flujo:
1. Si hay archivo de video: `store('capacitacion', 'public')` → `archivo_path`
2. `Capacitacion::create([...])` con `subido_por = Auth::id()`
3. Si hay adjuntos: loop → `store('capacitacion_docs', 'public')` + `adjuntos()->create()`

---

### `update(Request $request, $id): RedirectResponse`

**Ruta**: `PUT /recursos-humanos/capacitacion/{id}`

Casos especiales en la actualización del video:
- **Nuevo archivo de video**: Elimina el archivo anterior + guarda el nuevo + limpia `youtube_url`
- **Nueva URL de YouTube**: Elimina el archivo de video anterior (si existe) + limpia `archivo_path`
- **Sin cambios de video**: Solo actualiza metadatos (título, descripción, categoría, audiencia)

---

### `destroy($id): RedirectResponse`

**Ruta**: `DELETE /recursos-humanos/capacitacion/{id}`

Elimina el curso y todos sus archivos físicos:
1. Loop de `$video->adjuntos`: elimina cada archivo del disco `public`
2. Eliminar el video principal del disco `public` (si es archivo local)
3. `$video->delete()` — Laravel borra los `CapacitacionAdjunto` de BD si tiene cascade

---

### `destroyAdjunto($id): RedirectResponse|JsonResponse`

**Ruta**: `DELETE /recursos-humanos/capacitacion/adjunto/{id}`

Elimina un adjunto individual sin borrar el curso:
```php
$adjunto = CapacitacionAdjunto::findOrFail($id);
Storage::disk('public')->delete($adjunto->archivo_path);
$adjunto->delete();
```

---

## 6. Manejo de Adjuntos

Los adjuntos son materiales complementarios (PDFs, presentaciones, documentos) asociados a un curso.

**Almacenamiento**: Disco `public` en `storage/app/public/capacitacion_docs/`. Accesibles via URL pública `storage/capacitacion_docs/{filename}`.

**A diferencia de los documentos de expediente** (que son privados en disco `local`), los adjuntos de capacitación son públicos — cualquier usuario autenticado que tenga acceso al curso puede descargarlos.

**Al actualizar un curso**, no se eliminan los adjuntos anteriores automáticamente. Solo se añaden los nuevos. Para eliminar un adjunto específico, se usa `destroyAdjunto()`.

---

## 7. Soporte de Video: YouTube y Archivo Local

El módulo soporta dos tipos de video:

| Tipo | Campo | Almacenamiento | Render en Vista |
|---|---|---|---|
| YouTube | `youtube_url` | Externo (Google) | `<iframe>` embed |
| Archivo local | `archivo_path` | `storage/public/capacitacion/` | `<video>` HTML5 |

**Regla de exclusión mutua**: Un curso puede tener `youtube_url` O `archivo_path`, no ambos. Al actualizar:
- Si se sube un nuevo video MP4 → `youtube_url = null`
- Si se ingresa una URL de YouTube → `archivo_path = null` + se elimina el archivo físico

---

## 8. Referencia de Rutas

**Middleware**: `auth`, `area.rh`  
**Prefijo**: `/recursos-humanos/capacitacion`  
**Nombre base**: `rh.capacitacion.`

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `GET` | `/` | *(ruta faltante — usar `/gestion` para RH)* | Galería para empleados |
| `GET` | `/gestion` | `rh.capacitacion.manage` | Panel de administración |
| `POST` | `/subir` | `rh.capacitacion.store` | Crear nuevo curso |
| `GET` | `/{id}/editar` | `rh.capacitacion.edit` | Formulario de edición |
| `PUT` | `/{id}` | `rh.capacitacion.update` | Actualizar curso |
| `DELETE` | `/{id}` | `rh.capacitacion.destroy` | Eliminar curso + archivos |
| `DELETE` | `/adjunto/{id}` | `rh.capacitacion.destroyAdjunto` | Eliminar adjunto individual |

> **Nota**: La ruta de galería para empleados (`index()`) no está definida en el bloque `area.rh` de las rutas. Si existe en otro middleware group, verificar.

---

## 9. Historial de Migraciones

| Fecha | Archivo | Cambio |
|---|---|---|
| 2025-12-30 | `create_capacitacions_table.php` | Tabla `capacitaciones` inicial |
| 2025-12-30 | `create_capacitacion_adjuntos_table.php` | Tabla `capacitacion_adjuntos` |
| 2026-02-17 | Migración de YouTube URL | Añade `youtube_url` a `capacitaciones` |
| 2026-02-19 | Migración de puestos permitidos | Añade `puestos_permitidos` y `usuarios_permitidos` |
| 2026-04-20 | Migración adicional de visibilidad | Ajustes a la lógica de visibilidad por usuario |

---

## 10. Guía de Mantenimiento del Módulo

---

### 🔴 CRÍTICO: El filtrado de visibilidad se hace en PHP, no en BD

`index()` carga **todos** los cursos activos y los filtra en memoria:
```php
$videos = Capacitacion::where('activo', true)->get();
$filteredVideos = $videos->filter(fn($v) => $v->isVisibleFor($user));
```

Con 500+ cursos, esto puede ser lento y consumir memoria innecesaria.

**Solución**: Mover la lógica a la BD con raw queries o scopes:
```php
Capacitacion::activos()
    ->where(function($q) use ($user) {
        $q->whereNull('puestos_permitidos')  // Curso global
          ->orWhereJsonContains('usuarios_permitidos', $user->id)
          ->orWhereJsonContains('puestos_permitidos', $user->empleado?->posicion);
    })->get();
```

---

### 🔴 CRÍTICO: Los adjuntos son accesibles públicamente en `storage/public/`

Los archivos de `capacitacion_docs` están en el disco `public`, por lo que cualquier persona con la URL puede descargarlos, incluso sin estar autenticada.

**Si los documentos son sensibles**: Moverlos al disco `local` y servir mediante un controlador con auth, igual que los documentos de expedientes.

---

### 🟡 IMPORTANTE: `destroy()` asume que hay CASCADE a nivel de BD para adjuntos

El código hace `$video->delete()` esperando que los registros de `capacitacion_adjuntos` se eliminen automáticamente. Si la migración no tiene `onDelete('cascade')`, los adjuntos quedan huérfanos en BD (aunque los archivos físicos se eliminan en el loop anterior).

**Verificación**: En la migración `create_capacitacion_adjuntos_table.php`:
```php
$table->foreignId('capacitacion_id')->constrained('capacitaciones')->cascadeOnDelete();
```

---

### 🟡 IMPORTANTE: Video local de hasta 200 MB puede exceder tiempos de upload

`'video' => 'nullable|mimes:mp4,mov,ogg,qt|max:200000'` (200 MB). Si PHP o nginx tienen `upload_max_filesize` o `client_max_body_size` menores, el upload falla silenciosamente.

**Configuración recomendada en `php.ini`**:
```ini
upload_max_filesize = 200M
post_max_size = 210M
max_execution_time = 300
```

---

### 🟢 SEGURO: Añadir una nueva categoría de cursos

Las categorías son texto libre — no requieren tabla separada. Simplemente crear un curso con la nueva categoría y aparecerá automáticamente en la vista agrupada.

---

### 🟢 SEGURO: Restringir un curso a un puesto nuevo

1. En `manage()`, el selector de puestos se obtiene dinámicamente:
   ```php
   Empleado::distinct()->pluck('posicion')->filter()->sort()->values();
   ```
2. Al crear un empleado con el nuevo puesto, aparece automáticamente en el selector.

---

### Checklist de deploy para cambios en Capacitación

- [ ] ¿Se añade campo a `Capacitacion`? Migración + `$fillable` + actualizar `store()`, `update()`, `manage()` y `isVisibleFor()` si el campo afecta visibilidad.
- [ ] ¿Se cambia el tamaño máximo de video? Actualizar validación en `store()` y `update()` + `php.ini` + nginx config.
- [ ] ¿Se mueve el almacenamiento de adjuntos a disco `local`? Actualizar `store()`, `update()`, `destroy()`, `destroyAdjunto()` y el `<a href>` de descarga en la vista.
- [ ] ¿Se añade tipo de archivo permitido? Actualizar `'adjuntos.*' => 'file|mimes:...'` en `store()` y `update()`.

---

*Documentación generada el 27 de Abril de 2026 — ERP Estrategia e Innovación*
