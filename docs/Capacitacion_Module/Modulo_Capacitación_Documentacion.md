# Módulo de Capacitación — Documentación Maestra
> **ERP Estrategia e Innovación** · Versión documental: Abril 2026  
> Audiencia: Desarrolladores de mantenimiento, líderes técnicos, administradores de RH

---

## Tabla de Contenido

1. [Visión General del Módulo](#1-visión-general-del-módulo)
2. [Arquitectura y Componentes](#2-arquitectura-y-componentes)
3. [Esquema de Base de Datos](#3-esquema-de-base-de-datos)
4. [Modelos Eloquent — Referencia Completa](#4-modelos-eloquent--referencia-completa)
5. [Sistema de Control de Acceso (`isVisibleFor`)](#5-sistema-de-control-de-acceso-isvisiblefor)
6. [Soporte Híbrido de Video (MP4 vs YouTube)](#6-soporte-híbrido-de-video-mp4-vs-youtube)
7. [Gestión de Archivos en Disco](#7-gestión-de-archivos-en-disco)
8. [Referencia de Endpoints y Rutas](#8-referencia-de-endpoints-y-rutas)
9. [Referencia de Métodos del Controlador](#9-referencia-de-métodos-del-controlador)
10. [Vistas Blade](#10-vistas-blade)
11. [Historial de Migraciones](#11-historial-de-migraciones)
12. [Guía de Mantenimiento y Funciones Críticas](#12-guía-de-mantenimiento-y-funciones-críticas)
13. [Deuda Técnica Conocida y Mejoras Pendientes](#13-deuda-técnica-conocida-y-mejoras-pendientes)

---

## 1. Visión General del Módulo

El **Módulo de Capacitación** es la biblioteca de conocimiento institucional del ERP. Funciona como una plataforma de video tipo "Netflix interno" donde RH publica cursos de onboarding, tutoriales operativos y documentos de referencia, controlando con precisión qué empleados pueden ver qué contenido.

### Propósito de Negocio

| Necesidad | Solución implementada |
|---|---|
| Centralizar material de entrenamiento | Catálogo con categorías, filtrado por puesto |
| Evitar que todos vean todo | Sistema de permisos por puesto y/o usuario específico |
| Soportar videos pesados sin saturar el servidor | Soporte nativo de links de YouTube (embedding) |
| Acompañar videos con material descargable | Multi-adjuntos PDF/Word por capacitación |
| Evitar archivos huérfanos en disco | Limpieza automática al eliminar o reemplazar archivos |
| Doble seguridad (UI + Backend) | `isVisibleFor()` se valida tanto en el listado como en el reproductor |

### Características Principales

- **Biblioteca agrupada por categorías**: Vista tipo galería de tarjetas agrupadas por categoría. Si no hay categoría definida, agrupa en `'General'`.
- **Control de visibilidad granular**: Dos niveles de restricción independientes — por **puesto** (regex con word boundary) y por **usuario específico** (array de IDs).
- **Soporte híbrido MP4/YouTube**: Un video puede ser un archivo físico MP4 subido al servidor o un link de YouTube. Si ambos existen, el sistema prioriza el cambio al actualizar.
- **Multi-adjuntos**: Cada capacitación puede tener `N` archivos PDF/Word descargables, gestionados de forma independiente.
- **Doble validación de seguridad**: El acceso se filtra en el listado (PHP en memoria) y se re-valida en el endpoint `show()` con `abort(403)`.
- **Limpieza de disco garantizada**: Toda operación de reemplazar o eliminar archivos invoca `Storage::disk('public')->delete()` explícitamente.

---

## 2. Arquitectura y Componentes

```
┌──────────────────────────────────────────────────────────────┐
│                   MÓDULO DE CAPACITACIÓN                     │
│                                                              │
│  routes/web.php                                              │
│    ├── /capacitacion  (middleware: auth + verified)          │
│    │     └── Vistas públicas de empleados                    │
│    └── /recursos-humanos/capacitacion (middleware: area.rh)  │
│          └── Panel de administración RH                      │
│                            │                                 │
│                            ▼                                 │
│  CapacitacionController  (RH\CapacitacionController.php)     │
│    ├── index()     → Galería filtrada para empleados         │
│    ├── show($id)   → Reproductor + adjuntos + re-check 403   │
│    ├── manage()    → Panel CRUD de RH                        │
│    ├── edit($id)   → Formulario de edición pre-llenado       │
│    ├── store()     → Subir nuevo video/link + adjuntos       │
│    ├── update($id) → Actualizar metadatos + reemplazar video │
│    ├── destroy($id)→ Eliminar todo (disco + BD)              │
│    └── destroyAdjunto($id) → Eliminar solo 1 PDF adjunto     │
│                                                              │
│  Modelos Eloquent                                            │
│    ├── Capacitacion.php        (video/curso + lógica permisos)│
│    └── CapacitacionAdjunto.php (archivos descargables)       │
│                                                              │
│  Storage (disco público)                                     │
│    ├── storage/app/public/capacitacion/       (MP4s)         │
│    └── storage/app/public/capacitacion_docs/  (PDFs/Docs)    │
│                                                              │
│  Vistas Blade                                                │
│    ├── Recursos_Humanos/capacitacion/index.blade.php         │
│    ├── Recursos_Humanos/capacitacion/show.blade.php          │
│    ├── Recursos_Humanos/capacitacion/manage.blade.php        │
│    └── Recursos_Humanos/capacitacion/edit.blade.php          │
└──────────────────────────────────────────────────────────────┘
```

### Dependencias clave

| Dependencia | Uso |
|---|---|
| `Illuminate\Support\Facades\Storage` | Almacenamiento y eliminación de MP4s y PDFs en disco `public` |
| `Illuminate\Support\Facades\Auth` | Obtener el usuario actual para filtros de visibilidad |
| `Illuminate\Support\Facades\Log` | Registro de errores en `update()` y `destroy()` |
| `preg_match` / `preg_quote` | Comparación segura de puestos con word boundary regex |

---

## 3. Esquema de Base de Datos

### Tabla: `capacitaciones`

| Columna | Tipo | Nullable | Descripción |
|---|---|---|---|
| `id` | `bigint unsigned` | NO | PK auto-increment |
| `titulo` | `varchar(255)` | NO | Nombre del curso o video |
| `descripcion` | `text` | SÍ | Descripción larga del contenido |
| `categoria` | `varchar(255)` | SÍ | Agrupación visual (ej: "Onboarding", "IT", "Logística"). Si `null`, se muestra como "General" |
| `puestos_permitidos` | `json` | SÍ | Array de strings con nombres de puestos que pueden ver el video. `null` o `[]` = sin restricción de puesto |
| `usuarios_permitidos` | `json` | SÍ | Array de IDs enteros de usuarios específicos que pueden ver el video. `null` o `[]` = sin restricción de usuario |
| `archivo_path` | `varchar` | SÍ | Ruta relativa del MP4 en `storage/app/public/capacitacion/`. `null` si el video es de YouTube |
| `thumbnail_path` | `varchar` | SÍ | Ruta de la miniatura (campo reservado, uso futuro) |
| `youtube_url` | `varchar` | SÍ | URL completa del video de YouTube. `null` si el video es un MP4 local |
| `subido_por` | `bigint unsigned` | NO | FK → `users.id`. Quién publicó el contenido |
| `activo` | `boolean` | NO | `true` = visible en catálogo. `false` = oculto sin eliminarse |
| `created_at` / `updated_at` | `timestamp` | SÍ | Timestamps estándar Laravel |

> **Regla de exclusividad**: `archivo_path` y `youtube_url` son mutuamente excluyentes en la práctica. Si hay `youtube_url`, `archivo_path` debe ser `null` (y el MP4 debe haberse borrado del disco). El sistema garantiza esto en `update()`.

---

### Tabla: `capacitacion_adjuntos`

| Columna | Tipo | Nullable | Descripción |
|---|---|---|---|
| `id` | `bigint unsigned` | NO | PK auto-increment |
| `capacitacion_id` | `bigint unsigned` | NO | FK → `capacitaciones.id` con `onDelete('cascade')` |
| `titulo` | `varchar(255)` | NO | Nombre del archivo tal como lo subió el usuario (nombre original) |
| `archivo_path` | `varchar(255)` | NO | Ruta relativa en `storage/app/public/capacitacion_docs/` |
| `created_at` / `updated_at` | `timestamp` | SÍ | Timestamps estándar |

> **IMPORTANTE — Cascade en BD**: La FK de `capacitacion_id` tiene `onDelete('cascade')`. Esto significa que al borrar un registro de `capacitaciones`, MySQL elimina automáticamente las filas de `capacitacion_adjuntos`. **Sin embargo**, esto no borra los archivos físicos del disco. Por eso el controlador itera manualmente sobre los adjuntos para borrar los archivos antes de llamar a `$video->delete()`. Ver [Deuda Técnica #1](#13-deuda-técnica-conocida-y-mejoras-pendientes).

---

## 4. Modelos Eloquent — Referencia Completa

### `App\Models\Capacitacion`

**Archivo**: `app/Models/Capacitacion.php`

#### Traits y Casts

```php
use HasFactory;

protected $casts = [
    'puestos_permitidos'  => 'array',  // JSON → array PHP automáticamente
    'usuarios_permitidos' => 'array',  // JSON → array PHP automáticamente
];
```

El cast `'array'` en los campos JSON permite trabajar directamente con arrays PHP en el código, sin necesidad de `json_decode()` o `json_encode()` manual.

#### Relaciones

| Método | Tipo | Descripción |
|---|---|---|
| `uploader()` | `BelongsTo(User, 'subido_por')` | El usuario de RH que publicó el contenido |
| `adjuntos()` | `HasMany(CapacitacionAdjunto)` | Todos los archivos PDF/Word descargables |

#### Métodos del Modelo

| Método | Retorno | Descripción |
|---|---|---|
| `isVisibleFor(User $user)` | `bool` | Núcleo del sistema de permisos. Ver [sección 5](#5-sistema-de-control-de-acceso-isvisiblefor) |
| `isYoutube()` | `bool` | `true` si el campo `youtube_url` no está vacío |
| `getYoutubeId()` | `?string` | Extrae el ID de 11 caracteres de una URL de YouTube. `null` si no es YouTube o no se puede parsear |

---

### `App\Models\CapacitacionAdjunto`

**Archivo**: `app/Models/CapacitacionAdjunto.php`

Modelo ligero sin lógica de negocio. Solo define la relación inversa con su capacitación padre.

```php
protected $fillable = ['capacitacion_id', 'titulo', 'archivo_path'];
```

| Relación | Tipo |
|---|---|
| `capacitacion()` | `BelongsTo(Capacitacion)` |

---

## 5. Sistema de Control de Acceso (`isVisibleFor`)

Esta es la función más importante del módulo. Centraliza toda la lógica de visibilidad en el modelo.

**Archivo**: `app/Models/Capacitacion.php`, método `isVisibleFor(User $user): bool`

### Árbol de Decisión

```
isVisibleFor($user)
│
├── ¿$user->isAdmin()?
│       └── SÍ → return true (admin ve todo)
│
├── Limpiar arrays de permisos (filtrar nulls y vacíos)
│
├── ¿puestos_permitidos está vacío Y usuarios_permitidos está vacío?
│       └── SÍ → return true (sin restricciones = público para todos)
│
├── ¿usuarios_permitidos no está vacío?
│       └── ¿$user->id está en el array?
│               └── SÍ → return true (acceso explícito por usuario)
│
├── ¿puestos_permitidos no está vacío?
│       └── ¿$user->empleado->posicion no está vacío?
│               └── Para cada puesto en puestos_permitidos:
│                       └── ¿preg_match con word boundary?
│                               └── SÍ → return true (coincidencia de puesto)
│
└── return false (sin ninguna coincidencia)
```

### La Regex con Word Boundary

El sistema usa expresiones regulares con `\b` (word boundary) para evitar falsos positivos:

```php
$pattern = '/\b' . preg_quote($puestoPermitido, '/') . '\b/ui';
preg_match($pattern, $posicionEmpleado);
```

**Flags utilizados**:
- `u` → Modo Unicode (para soportar acentos y caracteres del español)
- `i` → Case-insensitive (ignora mayúsculas/minúsculas)

**¿Por qué es crítico el `\b`?**

| Sin `\b` (peligroso) | Con `\b` (correcto) |
|---|---|
| Puesto "TI" coincide con "Logís**ti**ca" | Puesto "TI" solo coincide con exactamente "TI" |
| Puesto "Operador" coincide con "Superoperador" | Puesto "Operador" no coincide con "Superoperador" |

**`preg_quote()`** escapa caracteres especiales de regex en los nombres de puestos, evitando que un puesto como "Analista (Junior)" rompa el patrón.

### Escenarios de Visibilidad

| `puestos_permitidos` | `usuarios_permitidos` | ¿Quién ve el video? |
|---|---|---|
| `null` o `[]` | `null` o `[]` | **Todos** los empleados activos |
| `['Logística']` | `null` o `[]` | Solo empleados cuyo puesto coincida con "Logística" |
| `null` o `[]` | `[5, 12, 88]` | Solo los usuarios con ID 5, 12 y 88 |
| `['Logística']` | `[5]` | Empleados de Logística + el usuario ID 5 (sin importar su puesto) |
| Admins (`isAdmin()`) | Cualquiera | Siempre ven todo |

---

## 6. Soporte Híbrido de Video (MP4 vs YouTube)

El módulo soporta dos tipos de fuente de video que son **mutuamente excluyentes**.

### Modo MP4 Local

- El archivo se almacena en `storage/app/public/capacitacion/`.
- El reproductor en la vista usa HTML5 `<video>`.
- Límite de tamaño: `200,000 KB` (≈ 195 MB) por validación en `store()`.
- Formatos aceptados: `mp4`, `mov`, `ogg`, `qt`.

### Modo YouTube

- Se almacena únicamente la URL de YouTube en `youtube_url`.
- El reproductor en la vista usa un `<iframe>` de YouTube embed.
- Para obtener el ID del video (necesario para el embed), se usa `getYoutubeId()`.

### Extractor de ID de YouTube (`getYoutubeId()`)

```php
$pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?|shorts)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
```

Formatos de URL de YouTube soportados:

| Formato de URL | Soportado |
|---|---|
| `https://www.youtube.com/watch?v=XXXXXXXXXXX` | ✅ |
| `https://youtu.be/XXXXXXXXXXX` | ✅ |
| `https://www.youtube.com/embed/XXXXXXXXXXX` | ✅ |
| `https://www.youtube.com/shorts/XXXXXXXXXXX` | ✅ |
| `https://www.youtube.com/v/XXXXXXXXXXX` | ✅ |

### Flujo al Cambiar de MP4 a YouTube (en `update()`)

```
1. Admin sube nuevo archivo MP4
   → Borra el MP4 anterior del disco
   → Guarda el nuevo MP4, limpia youtube_url

ó

2. Admin ingresa una URL de YouTube
   → Borra el MP4 del disco (si existía)
   → Limpia archivo_path = null
   → Guarda la youtube_url
```

---

## 7. Gestión de Archivos en Disco

El módulo gestiona dos rutas en el disco `public` de Laravel Storage:

| Carpeta | Contenido | Operaciones |
|---|---|---|
| `capacitacion/` | Archivos MP4 de video | store, update (reemplazar), destroy |
| `capacitacion_docs/` | PDFs, Word y otros adjuntos | store, update (agregar), destroyAdjunto |

### Acceso público a los archivos

Los archivos se almacenan en `storage/app/public/`, que se expone vía el symlink `public/storage`. Las URLs de acceso siguen el patrón:

```
/storage/capacitacion/{nombre_archivo}.mp4
/storage/capacitacion_docs/{nombre_archivo}.pdf
```

### Política de limpieza

El módulo implementa una política estricta de "no dejar huérfanos":

| Acción | Limpieza de disco |
|---|---|
| Reemplazar MP4 por nuevo MP4 | Borra el MP4 viejo antes de guardar el nuevo |
| Cambiar MP4 por YouTube | Borra el MP4 del disco, limpia `archivo_path` |
| Eliminar capacitación completa | Borra todos los adjuntos del disco + el MP4 |
| Eliminar adjunto individual | Borra el PDF del disco antes de borrar el registro |

Todos los borrados de disco se hacen con verificación de existencia:
```php
if (Storage::disk('public')->exists($path)) {
    Storage::disk('public')->delete($path);
}
```

---

## 8. Referencia de Endpoints y Rutas

### Grupo 1: Vistas de Empleado

**Middleware**: `auth`, `verified`  
**Prefijo URI**: `/capacitacion`  
**Prefijo nombre**: `capacitacion.`

| Método | URI | Nombre de Ruta | Método Controlador | Descripción |
|---|---|---|---|---|
| `GET` | `/capacitacion` | `capacitacion.index` | `index()` | Galería de cursos filtrada por permisos |
| `GET` | `/capacitacion/ver/{id}` | `capacitacion.show` | `show($id)` | Reproductor del video + descarga de adjuntos |

---

### Grupo 2: Panel de Administración RH

**Middleware**: `auth`, `area.rh`  
**Prefijo URI**: `/recursos-humanos/capacitacion`  
**Prefijo nombre**: `rh.capacitacion.`  
**Controlador**: `App\Http\Controllers\RH\CapacitacionController`

| Método | URI | Nombre de Ruta | Método Controlador | Descripción |
|---|---|---|---|---|
| `GET` | `/recursos-humanos/capacitacion/gestion` | `rh.capacitacion.manage` | `manage()` | Panel CRUD con listado completo |
| `POST` | `/recursos-humanos/capacitacion/subir` | `rh.capacitacion.store` | `store()` | Crear nuevo curso (subir MP4 o ingresar URL YouTube) |
| `GET` | `/recursos-humanos/capacitacion/{id}/editar` | `rh.capacitacion.edit` | `edit($id)` | Formulario de edición pre-llenado |
| `PUT` | `/recursos-humanos/capacitacion/{id}` | `rh.capacitacion.update` | `update($id)` | Actualizar metadatos / reemplazar video |
| `DELETE` | `/recursos-humanos/capacitacion/{id}` | `rh.capacitacion.destroy` | `destroy($id)` | Eliminar capacitación completa (disco + BD) |
| `DELETE` | `/recursos-humanos/capacitacion/adjunto/{id}` | `rh.capacitacion.destroyAdjunto` | `destroyAdjunto($id)` | Eliminar solo un adjunto |

---

## 9. Referencia de Métodos del Controlador

**Archivo**: `app/Http/Controllers/RH/CapacitacionController.php`  
**Namespace**: `App\Http\Controllers\RH`

---

### `index()`

Vista pública del catálogo de capacitaciones para todos los empleados.

**Lógica**:
1. Carga **todas** las capacitaciones activas (`activo = true`) de la BD, ordenadas por `created_at DESC`.
2. Filtra en memoria PHP con `Collection::filter()` usando `isVisibleFor($user)` por cada video.
3. Agrupa el resultado por `categoria` (si `null`, agrupa bajo `'General'`).
4. Retorna la vista con `$groupedVideos` (Collection de Collections).

> **Nota de rendimiento**: El filtrado se hace en PHP (no en SQL) porque la lógica de permisos con regex no puede expresarse en una cláusula `WHERE`. En empresas con catálogos grandes (>500 videos), considerar cachear la colección filtrada por usuario.

---

### `show($id)`

Reproductor de video para empleados.

**Lógica**:
1. Carga la capacitación con `->with('adjuntos')` (eager loading, evita N+1).
2. **Re-valida** los permisos con `isVisibleFor($user)`. Si falla → `abort(403)`.
3. Retorna la vista del reproductor con la variable `$video`.

**Seguridad**: Este doble-check es crítico. La vista `index` puede ocultar un video de la galería, pero si alguien copia la URL directa (`/capacitacion/ver/42`), el controlador bloquea el acceso igualmente.

---

### `manage()`

Panel de administración exclusivo de RH.

**Lógica**:
1. Carga **todas** las capacitaciones (sin filtro de `activo`), ordenadas por `created_at DESC`.
2. Extrae dinámicamente todos los puestos únicos: `Empleado::distinct()->pluck('posicion')`.
3. Carga todos los usuarios activos para el selector de permisos individuales.

> ⚠️ **Problema conocido**: La consulta de puestos incluye ex-empleados y puestos con errores tipográficos. Ver [Deuda Técnica](#13-deuda-técnica-conocida-y-mejoras-pendientes).

---

### `store(Request $request)`

Crea una nueva capacitación.

**Validaciones**:

| Campo | Regla |
|---|---|
| `titulo` | `required\|string\|max:255` |
| `descripcion` | `nullable\|string` |
| `categoria` | `nullable\|string\|max:255` |
| `puestos_permitidos` | `nullable\|array` |
| `puestos_permitidos.*` | `string` |
| `usuarios_permitidos` | `nullable\|array` |
| `usuarios_permitidos.*` | `integer` |
| `youtube_url` | `nullable\|url` |
| `video` | `nullable\|mimes:mp4,mov,ogg,qt\|max:200000` |
| `adjuntos.*` | `nullable\|file\|max:10240` (10 MB por adjunto) |

**Flujo de creación**:
```
1. Si se subió un archivo video → guardarlo en Storage::disk('public') bajo 'capacitacion/'
2. Crear registro Capacitacion con todos los campos
3. Por cada adjunto recibido:
   a. Guardar en 'capacitacion_docs/'
   b. Crear CapacitacionAdjunto relacionado con el nombre original del archivo
4. Redirect a manage con mensaje de éxito
```

---

### `edit($id)`

Carga el formulario de edición con los datos actuales.

Usa `->with('adjuntos')` para mostrar la lista de adjuntos existentes. Carga el mismo array de `$puestos` y `$usuarios` que `manage()` para pre-llenar los selectores.

---

### `update(Request $request, $id)`

Actualiza una capacitación existente.

**Flujo de actualización** (en orden):

```
1. Actualizar campos de metadatos (titulo, descripcion, categoria, puestos, usuarios, youtube_url)

2. ¿Se subió un nuevo archivo de video MP4?
   → Borrar el MP4 anterior del disco (si existe)
   → Guardar el nuevo MP4
   → Limpiar youtube_url = null
   → Guardar

3. ¿Se ingresó una youtube_url (sin subir MP4)?
   → Borrar el MP4 del disco (si existe)
   → Limpiar archivo_path = null
   → Guardar

4. ¿Se subieron nuevos adjuntos?
   → Guardar cada adjunto en 'capacitacion_docs/'
   → Crear CapacitacionAdjunto por cada uno

5. Redirect a manage con mensaje de éxito
```

**Manejo de errores**: Todo el método está envuelto en `try/catch`. Los errores se loguean con `Log::error()` y se redirigen de vuelta al formulario con el error visible.

---

### `destroy($id)`

Elimina completamente una capacitación.

**Flujo de eliminación** (en orden):
```
1. foreach ($video->adjuntos as $adjunto):
   → Si existe el archivo físico en disco → Storage::delete()
   [NO llama $adjunto->delete() explícitamente — confía en cascade de BD]

2. Si existe el archivo MP4 en disco → Storage::delete()

3. $video->delete() → borra el registro de la BD
   [MySQL borra las filas de capacitacion_adjuntos por cascade]
```

**Manejo de errores**: Envuelto en `try/catch` con log de errores y mensaje de error al usuario.

> ⚠️ **Riesgo conocido**: Si `onDelete('cascade')` no está configurado en la migración, los registros de `capacitacion_adjuntos` quedarían huérfanos en BD. Ver [Deuda Técnica #1](#13-deuda-técnica-conocida-y-mejoras-pendientes).

---

### `destroyAdjunto($id)`

Elimina solo un archivo adjunto individual, sin afectar el video principal.

**Flujo**:
```
1. Cargar CapacitacionAdjunto por ID
2. Si existe el archivo en disco → Storage::delete()
3. $adjunto->delete()
4. Redirect atrás con mensaje de éxito
```

Útil cuando RH sube el PDF equivocado y necesita corregirlo sin tocar el resto del curso.

---

## 10. Vistas Blade

| Vista | Ruta Blade | Propósito | Variables disponibles |
|---|---|---|---|
| Galería pública | `Recursos_Humanos/capacitacion/index` | Catálogo tipo Netflix para empleados | `$groupedVideos` (Collection agrupada por categoría) |
| Reproductor | `Recursos_Humanos/capacitacion/show` | Reproducir video + descargar adjuntos | `$video` (con relación `adjuntos` cargada) |
| Panel RH | `Recursos_Humanos/capacitacion/manage` | CRUD completo para RH | `$videos`, `$puestos`, `$usuarios` |
| Formulario edición | `Recursos_Humanos/capacitacion/edit` | Editar capacitación existente | `$video` (con adjuntos), `$puestos`, `$usuarios` |

### Decisión de tipo de reproductor en `show.blade.php`

La vista determina qué reproductor mostrar usando los métodos del modelo:

```blade
@if ($video->isYoutube())
    {{-- Embed de YouTube --}}
    <iframe src="https://www.youtube.com/embed/{{ $video->getYoutubeId() }}" ...></iframe>
@else
    {{-- Reproductor HTML5 --}}
    <video controls>
        <source src="{{ Storage::url($video->archivo_path) }}" type="video/mp4">
    </video>
@endif
```

---

## 11. Historial de Migraciones

| Fecha | Archivo | Cambio |
|---|---|---|
| 2025-12-30 | `create_capacitacions_table.php` | Crea tabla `capacitaciones` con campos base (`titulo`, `descripcion`, `archivo_path`, `thumbnail_path`, `subido_por`, `activo`) |
| 2025-12-30 | `create_capacitacion_adjuntos_table.php` | Crea tabla `capacitacion_adjuntos` con FK en cascada hacia `capacitaciones` |
| 2026-02-17 | `add_youtube_url_to_capacitaciones_table.php` | ⭐ Agrega `youtube_url` y convierte `archivo_path` a `nullable` (soporte híbrido) |
| 2026-02-19 | `add_categoria_and_puestos_to_capacitaciones_table.php` | Agrega `categoria` (varchar) y `puestos_permitidos` (json) para categorización y control de acceso por puesto |
| 2026-04-20 | `add_usuarios_permitidos_to_capacitaciones_table.php` | ⭐ Agrega `usuarios_permitidos` (json) para control de acceso individual, sin depender del puesto |

---

## 12. Guía de Mantenimiento y Funciones Críticas

Esta sección es la **referencia primaria** para cualquier desarrollador que deba modificar el módulo.

---

### 🔴 CRÍTICO: No tocar sin leer esto primero

#### 1. `Capacitacion::isVisibleFor()` — Núcleo de seguridad

**Archivo**: `app/Models/Capacitacion.php`

Esta función controla **qué empleados pueden ver qué videos**. Cualquier cambio en ella afecta la seguridad del módulo entero.

**Reglas inviolables al modificar**:
- Siempre limpiar los arrays de permisos antes de evaluarlos (`array_filter` con trim).
- Siempre usar `preg_quote()` al construir el patrón regex para puestos.
- Siempre mantener el flag `u` en la regex para soporte Unicode (español con acentos).
- La comprobación `isAdmin()` siempre debe estar **primero** como cortocircuito.

**Si se agrega un nuevo nivel de permiso** (por ejemplo, por departamento), agregarlo como un bloque `if` adicional siguiendo el mismo patrón de limpieza → evaluación → `return true`.

---

#### 2. `destroy()` — Orden de eliminación

**Archivo**: `app/Http/Controllers/RH/CapacitacionController.php`

El orden de operaciones en `destroy()` es intencionado. Siempre debe ser:
```
1. Borrar archivos físicos de adjuntos del disco
2. Borrar archivo MP4 del disco
3. Borrar registro de la BD ($video->delete())
```

**Nunca** invertir este orden. Si se borra el registro de BD primero y luego falla la eliminación del disco (por error de permisos de filesystem), los archivos quedan huérfanos sin forma de recuperar la referencia.

---

#### 3. La exclusividad `archivo_path` vs `youtube_url`

En `update()`, si se recibe un nuevo MP4, se debe limpiar `youtube_url = null`. Si se recibe una `youtube_url`, se debe limpiar `archivo_path = null` y borrar el MP4. Si algún mantenimiento omite esta limpieza, el modelo quedaría en estado inconsistente.

**Verificación rápida de consistencia en BD**:
```sql
-- Capacitaciones con ambos campos llenos (inconsistente)
SELECT id, titulo, archivo_path, youtube_url
FROM capacitaciones
WHERE archivo_path IS NOT NULL AND youtube_url IS NOT NULL;
```

---

### 🟡 IMPORTANTE: Puntos de extensión frecuentes

#### Agregar un nuevo tipo de permiso (ej: por área/departamento)

1. Agregar migración con el nuevo campo JSON en `capacitaciones`.
2. Agregar el campo a `$fillable` y `$casts` en `Capacitacion.php`.
3. Agregar el bloque de evaluación en `isVisibleFor()` siguiendo el patrón existente.
4. Actualizar los formularios de `manage` y `edit` para mostrar el nuevo selector.
5. Actualizar las validaciones en `store()` y `update()`.

---

#### Agregar soporte a nuevos formatos de video (ej: Vimeo)

1. Crear un nuevo método en el modelo: `isVimeo()` y `getVimeoId()` siguiendo el patrón de `isYoutube()` / `getYoutubeId()`.
2. Agregar un campo `vimeo_url` en la tabla via migración.
3. Actualizar la vista `show.blade.php` para añadir el nuevo bloque `@elseif ($video->isVimeo())`.
4. Actualizar `update()` para gestionar la exclusividad del nuevo formato con MP4 y YouTube.

---

#### Activar/Desactivar un video sin borrarlo

El campo `activo` en la tabla está preparado para esto. Actualmente **no hay un endpoint dedicado** para cambiar `activo` sin editar todo el formulario.

Para agregar este toggle rápido:
1. Agregar una ruta: `PATCH /recursos-humanos/capacitacion/{id}/toggle`.
2. Agregar un método `toggle($id)` en el controlador: `$video->update(['activo' => !$video->activo])`.

---

#### Cambiar el límite de tamaño de archivos subidos

**Para videos MP4** → en `store()` y `update()`, cambiar la regla:
```php
'video' => 'nullable|mimes:mp4,mov,ogg,qt|max:200000'
//                                                 ↑ en KB. 200000 = ~195 MB
```

**Para adjuntos PDF** → cambiar:
```php
'adjuntos.*' => 'nullable|file|max:10240'
//                                  ↑ en KB. 10240 = 10 MB
```

También verificar el límite de `upload_max_filesize` y `post_max_size` en `php.ini`.

---

#### Cambiar la carpeta de almacenamiento

Si se necesita mover los archivos a otro disco (ej: S3), solo hay que cambiar:
```php
// store() y update()
$request->file('video')->store('capacitacion', 'public');
// Cambiar 'public' por el nombre del disco configurado en config/filesystems.php
```

Y en el borrado:
```php
Storage::disk('public')->delete($path);
// Cambiar 'public' por el nuevo disco
```

---

### 🟢 SEGURO: Cambios de bajo riesgo

- Cambiar mensajes de `redirect()->with('success', '...')` o `->with('error', '...')`.
- Agregar nuevos campos de texto a `$fillable` que no afecten la lógica de permisos ni de archivos.
- Modificar el ordenamiento en `manage()` (actualmente `orderBy('created_at', 'desc')`).
- Agregar validaciones adicionales de formato en `store()` y `update()` sin cambiar las reglas de archivo.
- Modificar el filtro y agrupación en `index()` (por ejemplo, cambiar `'General'` por `'Sin Categoría'`).

---

### Checklist antes de un deploy con cambios al módulo

- [ ] ¿Cambió `isVisibleFor()`? Verificar que los 4 escenarios de visibilidad siguen funcionando (admin / sin restricciones / por usuario / por puesto).
- [ ] ¿Se agregó un campo de restricción nuevo? Verificar que `isVisibleFor()` lo evalúa y que los formularios de RH lo exponen.
- [ ] ¿Cambió la lógica de `destroy()`? Verificar el orden: disco → BD, no al revés.
- [ ] ¿Se modificó `update()`? Verificar que la exclusividad `archivo_path` / `youtube_url` se mantiene.
- [ ] ¿Hay nuevos tipos de archivo? Agregar la extensión a las reglas `mimes:` en `store()` y `update()`.
- [ ] ¿El nuevo endpoint de RH está dentro del grupo con middleware `area.rh`?

---

## 13. Deuda Técnica Conocida y Mejoras Pendientes

### Alta Prioridad

#### Bug potencial: `destroy()` no llama `$adjunto->delete()` explícitamente

**Archivo**: `CapacitacionController.php`, método `destroy()`  
**Problema**: El código itera los adjuntos para borrar sus archivos de disco, pero luego solo llama `$video->delete()`, confiando en que el `onDelete('cascade')` de MySQL borre las filas. Si la migración no tiene cascade correctamente configurado, las filas de `capacitacion_adjuntos` quedan en BD apuntando a un video eliminado.

**Solución**: Agregar una línea explícita antes de borrar el padre:
```php
// ANTES de $video->delete():
$video->adjuntos()->delete();  // Elimina los registros de BD explícitamente
$video->delete();
```

---

### Media Prioridad

#### Rendimiento: `manage()` y `edit()` consultan empleados inactivos

**Archivo**: `CapacitacionController.php`, métodos `manage()` y `edit()`  
**Problema**:
```php
$puestos = \App\Models\Empleado::distinct()->pluck('posicion')->filter()->sort()->values();
```
Esta consulta no filtra por `es_activo = true`. Incluye puestos de ex-empleados y puestos con errores ortográficos históricos en el selector de permisos.

**Solución**:
```php
$puestos = \App\Models\Empleado::where('es_activo', true)
    ->whereNotNull('posicion')
    ->where('posicion', '!=', '')
    ->distinct()
    ->orderBy('posicion')
    ->pluck('posicion');
```

---

#### Rendimiento: `index()` filtra en memoria PHP

**Problema**: El listado de videos se carga completo de la BD y se filtra en PHP. Con un catálogo grande esto puede ser ineficiente.  
**Solución a largo plazo**: Agregar un scope de Eloquent que construya una query SQL con los IDs de usuario y puestos permitidos, evitando traer registros que definitivamente no son accesibles.

---

### Baja Prioridad (Mejoras futuras)

- **Toggle rápido de `activo`**: No hay endpoint para activar/desactivar un video sin abrir el formulario completo de edición. Un `PATCH /{id}/toggle` simplificaría la gestión de RH.
- **Campo `thumbnail_path` sin uso**: El campo existe en la migración y el modelo pero no se usa en ningún formulario ni vista. Implementarlo daría una experiencia visual más rica en la galería.
- **Validación `required_without`**: La documentación anterior menciona `required_without:youtube_url` para forzar un MP4 si no hay URL. Verificar que esta validación esté activa en `store()` para evitar crear una capacitación vacía sin video ni URL.
- **Sin paginación en `manage()`**: El panel de RH carga todas las capacitaciones en una sola tabla. Agregar `->paginate(20)` si el catálogo crece.
- **No hay búsqueda en la galería de empleados**: Los empleados no pueden buscar por título o categoría en `index()`. Agregar un campo de búsqueda con `request->search` similar al módulo de Actividades.

---

*Documentación generada el 27 de Abril de 2026 — ERP Estrategia e Innovación*
