# Módulo Proyectos — Documentación Maestra
> **ERP Estrategia e Innovación** · Versión documental: Abril 2026  
> Audiencia: Coordinadores RH, responsables de proyecto, equipo TI, desarrolladores

---

## Tabla de Contenido

1. [Visión General](#1-visión-general)
2. [Arquitectura del Módulo](#2-arquitectura-del-módulo)
3. [Modelo de Datos](#3-modelo-de-datos)
4. [Sistema de Roles y Visibilidad](#4-sistema-de-roles-y-visibilidad)
5. [Referencia de Métodos — `ProyectoController`](#5-referencia-de-métodos--proyectocontroller)
6. [Gestión del Equipo de Trabajo](#6-gestión-del-equipo-de-trabajo)
7. [Tablero de Actividades (Kanban)](#7-tablero-de-actividades-kanban)
8. [Ciclo de Vida del Proyecto](#8-ciclo-de-vida-del-proyecto)
9. [Reporte Post-Mortem y Métricas](#9-reporte-post-mortem-y-métricas)
10. [Cálculo de Próxima Junta](#10-cálculo-de-próxima-junta)
11. [Notificaciones de Asignación por Correo](#11-notificaciones-de-asignación-por-correo)
12. [Referencia de Rutas](#12-referencia-de-rutas)
13. [Historial de Migraciones](#13-historial-de-migraciones)
14. [Guía de Mantenimiento del Módulo](#14-guía-de-mantenimiento-del-módulo)

---

## 1. Visión General

El módulo de **Proyectos** es la herramienta de gestión de proyectos internos del ERP. Permite a los coordinadores de RH crear proyectos con equipo multidisciplinario (operativos + TI), asignar actividades en formato Kanban, calcular la próxima fecha de junta de seguimiento según la recurrencia configurada, y generar un reporte post-mortem con métricas de eficiencia al finalizar.

### Propósito de negocio

| Necesidad | Solución |
|---|---|
| Centralizar proyectos de la empresa | `Proyecto` con fechas, recurrencia y participantes |
| Ver solo los proyectos relevantes para cada usuario | `index()` — filtrado por rol, autoría y membresía |
| Asignar equipos operativos y de TI por separado | Tablas pivote `proyecto_usuarios` + `proyecto_responsable_ti` |
| Notificar automáticamente a los asignados | `ProyectoAsignado` Mailable enviado en `store()` / `asignarUsuarios()` |
| Gestionar tareas del proyecto en Kanban | `actividades()` — tablero con KPIs integrado |
| Calcular cuándo es la próxima junta | `siguienteFechaJunta()` — recurrencia semanal/quincenal/mensual |
| Archivar proyectos sin perder historial | `destroy()` — soft archive (`archivado = true`) |
| Eliminar permanentemente con limpieza | `forceDelete()` — cascade manual antes del delete |
| Generar reporte de eficiencia al cerrar | `reporte()` — usa `metricas()` del modelo |

---

## 2. Arquitectura del Módulo

```
┌────────────────────────────────────────────────────────────────────┐
│                         MÓDULO PROYECTOS                           │
│                                                                    │
│  Middleware: auth (verificado)                                      │
│  Prefijo URL: /proyectos                                           │
│  Nombre base de rutas: proyectos.                                  │
│  Controlador: app/Http/Controllers/ProyectoController.php          │
│                                                                    │
│  ProyectoController                                                │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │  [LECTURA — todos los participantes]                         │  │
│  │  index()               → Tarjetas de proyectos con filtrado  │  │
│  │  show($id)             → Dashboard del proyecto              │  │
│  │  actividades($id)      → Tablero Kanban de tareas            │  │
│  │  reporte($id)          → Reporte post-mortem imprimible      │  │
│  │                                                              │  │
│  │  [GESTIÓN — RH Coordinador exclusivamente]                   │  │
│  │  store()               → Crear proyecto + asignar equipo     │  │
│  │  update($id)           → Editar datos del proyecto           │  │
│  │  destroy($id)          → Archivar (soft archive)             │  │
│  │  restore($id)          → Restaurar desde archivados          │  │
│  │  forceDelete($id)      → Eliminar permanente con cascade     │  │
│  │  finalizar($id)        → Marcar fin real + generar reporte   │  │
│  │                                                              │  │
│  │  [EQUIPO — RH Coordinador]                                   │  │
│  │  asignarUsuarios()     → Sync operativos + enviar correos    │  │
│  │  quitarUsuario()       → Detach individual de operativo      │  │
│  │  asignarResponsablesTi()→ Sync TI + enviar correos           │  │
│  │  quitarResponsableTi() → Detach individual de TI             │  │
│  │                                                              │  │
│  │  [ACTIVIDADES — operativos y coordinadores]                  │  │
│  │  guardarActividad()    → Crea Activity con proyecto_id       │  │
│  │  editarActividad()     → JSON: form de edición               │  │
│  │  actualizarActividad() → Guarda cambios + auto fecha_final   │  │
│  │  eliminarActividad()   → Solo RH o creador del proyecto      │  │
│  │                                                              │  │
│  │  [API]                                                        │  │
│  │  edit($id)             → JSON: form de edición del proyecto  │  │
│  │  listaUsuarios()       → JSON: lista de empleados activos    │  │
│  └──────────────────────────────────────────────────────────────┘  │
│                                                                    │
│  Modelos                                                           │
│  ──────                                                             │
│  Proyecto        → Contenedor principal con SoftDeletes            │
│  Activity        → Tareas vinculadas por proyecto_id               │
│  User            → Integrantes operativos y de TI (M:N)            │
│                                                                    │
│  Correo                                                            │
│  ──────                                                             │
│  ProyectoAsignado (Mailable) → Notifica al ser asignado           │
└────────────────────────────────────────────────────────────────────┘
```

---

## 3. Modelo de Datos

### `Proyecto` — Tabla `proyectos`

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | `bigint` PK | |
| `nombre` | `varchar(255)` | Nombre del proyecto |
| `descripcion` | `text` nullable | Descripción larga |
| `usuario_id` | `bigint` FK | FK → `users.id` — creador/responsable principal |
| `fecha_inicio` | `date` | Fecha de arranque del proyecto |
| `fecha_fin` | `date` | Fecha de término planeada |
| `fecha_fin_real` | `date` nullable | Fecha de cierre real (se establece al finalizar) |
| `recurrencia` | `enum` | `semanal`, `quincenal`, `mensual` — cadencia de juntas |
| `notas` | `text` nullable | Notas adicionales del coordinador |
| `archivado` | `boolean` default `false` | Flag de soft archive |
| `finalizado` | `boolean` default `false` | Si el proyecto está cerrado formalmente |
| `deleted_at` | `timestamp` nullable | Soft delete de Laravel (`SoftDeletes`) |
| `created_at` / `updated_at` | `timestamp` | |

**Casts**:
```php
'fecha_inicio'  => 'date',
'fecha_fin'     => 'date',
'fecha_fin_real' => 'date',
'archivado'     => 'boolean',
'finalizado'    => 'boolean',
```

**Relaciones**:

| Método | Tipo | Descripción |
|---|---|---|
| `creador()` | `BelongsTo User` | El usuario que creó el proyecto |
| `usuarios()` | `BelongsToMany User` via `proyecto_usuarios` | Equipo operativo |
| `responsablesTi()` | `BelongsToMany User` via `proyecto_responsable_ti` | Equipo de TI |
| `actividades()` | `HasMany Activity` | Tareas del proyecto |

**Scopes**:
- `scopeActivos($query)` → `where('archivado', false)`
- `scopeArchivados($query)` → `where('archivado', true)`

### Tablas Pivote

**`proyecto_usuarios`**:

| Campo | Tipo |
|---|---|
| `proyecto_id` | `bigint` FK → `proyectos.id` (cascade delete) |
| `usuario_id` | `bigint` FK → `users.id` (cascade delete) |

**`proyecto_responsable_ti`**:

| Campo | Tipo |
|---|---|
| `proyecto_id` | `bigint` FK → `proyectos.id` |
| `usuario_id` | `bigint` FK → `users.id` |

### `Activity` (relación)

El módulo reutiliza el modelo `Activity` (del módulo de Actividades) con el campo `proyecto_id` que se añadió por migración. Esto permite que una actividad exista tanto en el módulo de Actividades general como dentro de un proyecto específico.

Campos relevantes para Proyectos:

| Campo | Descripción |
|---|---|
| `proyecto_id` | FK → `proyectos.id` (nullable, `SET NULL` on delete) |
| `user_id` | Responsable de la actividad |
| `nombre_actividad` | Descripción de la tarea |
| `estatus` | `Planeado`, `En proceso`, `Completado`, `Completado con retardo`, `Por Aprobar`, `Por Validar`, `Retardo`, `Rechazado` |
| `fecha_compromiso` | Fecha límite |
| `fecha_final` | Cuándo se completó (auto-asignado al marcar "Completado") |
| `porcentaje` | 0-100, para cálculo de eficiencia |
| `metrico` | Días planeados |
| `resultado_dias` | Días reales utilizados |

---

## 4. Sistema de Roles y Visibilidad

El módulo no usa middleware de área específico — la lógica de acceso se implementa internamente en cada método del controlador.

### Niveles de acceso

| Rol | Descripción | Permisos |
|---|---|---|
| **RH Coordinador** (`isRhCoordinador()`) | Jefe de RH | Ve todos los proyectos; crear, editar, archivar, restaurar, forceDelete, finalizar |
| **RH** (`isRh()`) | Personal de RH general | Ve todos sus proyectos + los de subordinados; asignar/quitar usuarios |
| **Coordinador de área** (`esCoordinador`) | Tiene subordinados en `empleados.supervisor_id` | Ve sus propios proyectos + los de sus subordinados |
| **Usuario operativo** | Miembro del proyecto | Solo ve los proyectos donde está asignado como usuario, responsable TI, o es creador |

### Lógica de filtrado en `index()`

```
Si es RH Coordinador → ve TODOS
Si es Coordinador de área → ve:
  - Proyectos propios (usuario_id = yo)
  - Proyectos donde mis subordinados son miembros
  - Proyectos donde soy responsable TI
Si es usuario regular → ve:
  - Proyectos propios (usuario_id = yo)
  - Proyectos donde soy miembro del equipo
  - Proyectos donde soy responsable TI
```

### Lógica de acceso en `show()`

La variable `$esResponsableProyecto` se calcula cuidadosamente para excluir a quienes **solo** son responsables de TI:
```php
// Es responsable si es creador O está en usuarios
$esResponsableProyecto = $proyecto->usuario_id === $user->id
    || $proyecto->usuarios()->where('users.id', $user->id)->exists();

// Pero si SOLO es responsable TI (sin ser creador ni miembro): false
if (!($proyecto->usuario_id === $user->id) &&
    !$proyecto->usuarios()->where('users.id', $user->id)->exists() &&
    $proyecto->responsablesTi()->where('users.id', $user->id)->exists()) {
    $esResponsableProyecto = false;
}
```

---

## 5. Referencia de Métodos — `ProyectoController`

**Archivo**: `app/Http/Controllers/ProyectoController.php`

---

### `index(Request $request): View`

**Ruta**: `GET /proyectos`

| Parámetro GET | Comportamiento |
|---|---|
| `archivado=1` | Muestra proyectos con `archivado = true` |
| *(omitido)* | Muestra proyectos activos (`archivado = false`) |

Calcula para cada proyecto un mapa de KPIs mediante `->map()`:
```php
$p->total_actividades = $p->actividades()->count();
$p->actividades_pendientes = $p->actividades()
    ->whereNotIn('estatus', ['Completado', 'Rechazado'])->count();
```

> **Problema de rendimiento**: Esto genera 2 queries por proyecto (N+1). Ver sección de mantenimiento.

**Variables pasadas a la vista**:
- `$proyectos` — colección filtrada por rol
- `$proyectosConActividades` — misma colección con atributos `total_actividades` y `actividades_pendientes`
- `$esRh`, `$esRhCoordinador`, `$esCoordinador` — flags de rol

---

### `show($id): View`

**Ruta**: `GET /proyectos/{id}`

Carga el proyecto con eager loading: `creador`, `usuarios.empleado`, `responsablesTi.empleado`, `actividades.user`.

Calcula la próxima fecha de junta: `$siguiente = $proyecto->siguienteFechaJunta()`.

Muestra las primeras 10 actividades ordenadas por `fecha_compromiso` (el resto están en el tablero de actividades).

---

### `store(Request $request): RedirectResponse`

**Ruta**: `POST /proyectos` — exclusivo `isRhCoordinador()`

Validación:
```php
'nombre'      => 'required|string|max:255',
'fecha_inicio' => 'required|date',
'fecha_fin'   => 'required|date|after:fecha_inicio',
'recurrencia' => 'required|in:semanal,quincenal,mensual',
```

Flujo:
1. `Proyecto::create([...])` con `usuario_id = Auth::id()`
2. Si `usuarios[]` → `sync()` + loop de correos (`ProyectoAsignado` con `tipo='usuario'`)
3. Si `responsables_ti[]` → `sync()` + loop de correos (`ProyectoAsignado` con `tipo='responsable_ti'`)

---

### `update(Request $request, $id): RedirectResponse`

**Ruta**: `PUT /proyectos/{id}` — exclusivo `isRhCoordinador()`

Actualiza todos los campos del proyecto. Si `usuarios[]` o `responsables_ti[]` están en el request, hace `sync()` **sin enviar correos** (a diferencia de `asignarUsuarios()` que sí los envía).

---

### `destroy($id): RedirectResponse`

**Ruta**: `DELETE /proyectos/{id}` — exclusivo `isRhCoordinador()`

Soft archive: `$proyecto->archivado = true; $proyecto->save()`. El proyecto sigue en BD y sus actividades intactas.

---

### `restore($id): RedirectResponse`

**Ruta**: `POST /proyectos/{id}/restore` — exclusivo `isRhCoordinador()`

`$proyecto->archivado = false; $proyecto->save()`. Redirige al listado de activos.

---

### `forceDelete($id): RedirectResponse`

**Ruta**: `DELETE /proyectos/{id}/force` — exclusivo `isRh()`

Limpieza explícita antes de borrar (el modelo usa `SoftDeletes` pero la limpieza es manual):
```php
$proyecto->actividades()->delete();     // Elimina todas las actividades
$proyecto->usuarios()->detach();         // Limpia tabla pivote proyecto_usuarios
$proyecto->responsablesTi()->detach();  // Limpia tabla pivote proyecto_responsable_ti
$proyecto->delete();                     // Soft delete del proyecto
```

> **Nota**: Usa `->delete()` (soft delete) y no `->forceDelete()`. Las actividades se borran permanentemente, pero el proyecto queda en `deleted_at`.

---

### `finalizar(Request $request, $id): RedirectResponse`

**Ruta**: `POST /proyectos/{id}/finalizar` — RH o responsables del proyecto

Validación: `fecha_fin_real` requerida (date).

```php
$proyecto->update([
    'fecha_fin_real' => $request->fecha_fin_real,
    'finalizado' => true,
]);
```

Redirige directamente a `proyectos.reporte` del mismo proyecto.

---

### `reporte($id): View`

**Ruta**: `GET /proyectos/{id}/reporte` — RH o miembros del proyecto

Construye la vista de reporte con:
- `$proyecto` (con relaciones eager)
- `$metricas` — del método `Proyecto::metricas()`
- `$actividades` — todas, ordenadas por `fecha_compromiso`

La vista incluye botón `window.print()` para exportar a PDF desde el navegador.

---

### `edit($id): JsonResponse`

**Ruta**: `GET /proyectos/{id}/edit` — solo `isRh()`

Retorna JSON con el HTML renderizado del form parcial `proyectos.partials.edit_form`. Usado para cargar el modal de edición dinámicamente (sin page reload).

---

### `listaUsuarios(): JsonResponse`

**Ruta**: `GET /proyectos/usuarios/lista` — solo `isRh()`

```json
{
  "usuarios": [
    { "id": 5, "name": "Ana García", "email": "ana@empresa.com" },
    ...
  ]
}
```

> **Advertencia**: Este endpoint existe pero no es consumido internamente por el frontend (el modal de asignación hace queries Blade directamente). Ver sección de mantenimiento.

---

## 6. Gestión del Equipo de Trabajo

### Asignación operativa (`asignarUsuarios`)

**Ruta**: `POST /proyectos/{id}/usuarios` — solo `isRh()`

```php
$proyecto->usuarios()->sync($request->usuarios); // sync reemplaza toda la lista
foreach ($request->usuarios as $usuarioId) {
    Mail::to($usuario->email)->send(new ProyectoAsignado($proyecto, $usuario, 'usuario', $user));
}
```

**`sync()` es destructivo**: Si el proyecto tenía 5 usuarios y el request trae 3, los 2 que no vienen son removidos de la tabla pivote. El usuario no ve una advertencia de esto.

### Asignación TI (`asignarResponsablesTi`)

**Ruta**: `POST /proyectos/{id}/responsables-ti` — solo `isRh()`

Idéntico al anterior pero usa la tabla `proyecto_responsable_ti` y envía `ProyectoAsignado` con `tipo='responsable_ti'`.

### Quitar usuarios individualmente

- `DELETE /proyectos/{id}/usuarios/{userId}` → `detach($userId)` de `proyecto_usuarios`
- `DELETE /proyectos/{id}/responsables-ti/{userId}` → `detach($userId)` de `proyecto_responsable_ti`

Ambos solo requieren `isRh()`. No envían correo de notificación al removido.

---

## 7. Tablero de Actividades (Kanban)

**Ruta**: `GET /proyectos/{proyecto}/actividades`

Vista con tablero de tareas del proyecto. Visible para RH y miembros del equipo (no para responsables TI que no son miembros).

**KPIs calculados en el controlador**:

| KPI | Cálculo |
|---|---|
| `total` | `count()` de actividades |
| `completadas` | `where('estatus', 'Completado')` |
| `enProceso` | `whereIn(['En proceso', 'Planeado'])` |
| `pendientes` | `whereIn(['Por Aprobar', 'Por Validar'])` |

**Usuarios asignables**: El selector de "asignar a" carga primero los usuarios del proyecto. Si el proyecto no tiene usuarios asignados, carga todos los empleados activos como fallback.

**Áreas**: El selector de área viene de `Empleado::distinct()->pluck('posicion')`. Si la tabla de empleados está vacía, usa `['General', 'Operativo', 'Administrativo']` como fallback.

---

### `guardarActividad(Request $request, $proyectoId): RedirectResponse`

**Ruta**: `POST /proyectos/{proyecto}/actividades`

Crea una `Activity` forzando `proyecto_id`:
```php
Activity::create([
    'user_id'     => $request->asignado_a ?? $user->id,
    'proyecto_id' => $proyecto->id,
    'estatus'     => 'Planeado',
    'metrico'     => 1,
    // ...
]);
```

---

### `actualizarActividad(Request $request, $proyectoId, $actividadId): RedirectResponse`

**Ruta**: `PUT /proyectos/{proyecto}/actividades/{actividad}`

Auto-asigna `fecha_final` cuando el estatus cambia a "Completado":
```php
if ($request->estatus === 'Completado' && !$actividad->fecha_final) {
    $actividad->fecha_final = now();
}
```

---

### `eliminarActividad($proyectoId, $actividadId): RedirectResponse`

**Ruta**: `DELETE /proyectos/{proyecto}/actividades/{actividad}`

Requiere `isRh()` O ser el creador del proyecto. Los miembros regulares no pueden eliminar actividades.

---

## 8. Ciclo de Vida del Proyecto

```
CREACIÓN (store)
  → Proyecto::create()
  → usuarios()->sync() si los hay
  → ProyectoAsignado email a cada usuario asignado
  → Estado: archivado=false, finalizado=false

EDICIÓN (update)
  → Actualiza campos
  → sync() de usuarios/TI si vienen en el request (sin correo)

ARCHIVADO (destroy) — reversible
  → archivado = true
  → El proyecto desaparece del listado activo
  → Actividades intactas

RESTAURACIÓN (restore) — desde listado de archivados
  → archivado = false

FINALIZACIÓN (finalizar)
  → fecha_fin_real = (fecha ingresada)
  → finalizado = true
  → Redirige al reporte

ELIMINACIÓN PERMANENTE (forceDelete) — irreversible
  → actividades()->delete()
  → usuarios()->detach()
  → responsablesTi()->detach()
  → delete() (soft delete de Proyecto)

┌──────────────┐    destroy()     ┌─────────────┐    restore()    ┌──────────────┐
│   ACTIVO     │ ──────────────► │  ARCHIVADO  │ ──────────────► │    ACTIVO    │
│archivado=false│                 │archivado=true│                 │archivado=false│
└──────────────┘                 └─────────────┘                 └──────────────┘
        │                                │
        │ finalizar()                    │ forceDelete()
        ▼                                ▼
┌──────────────┐                 ┌─────────────┐
│  FINALIZADO  │                 │  ELIMINADO  │
│finalizado=true│                │ (permanente)│
└──────────────┘                 └─────────────┘
        │
        ▼
    reporte()
```

---

## 9. Reporte Post-Mortem y Métricas

Al finalizar un proyecto, se genera automáticamente el reporte. También puede accederse si el proyecto ya está finalizado desde la vista de detalle.

**Métricas calculadas por `Proyecto::metricas()`**:

```php
$actividades = $this->actividades()->get();

return [
    'total'                => count de actividades,
    'completadas'          => con estatus Completado o Completado con retardo,
    'en_proceso'           => con estatus En proceso, Planeado, Por Aprobar, Por Validar,
    'rechazadas'           => con estatus Rechazado,
    'a_tiempo'             => actividades con porcentaje = 100,
    'con_retraso'          => con estatus Completado con retardo,
    'promedio_eficiencia'  => avg de porcentaje (redondeado a 1 decimal),
    'porcentaje_completado' => (completadas / total) * 100,
    'dias_planeados'       => avg de campo metrico,
    'dias_reales'          => avg de campo resultado_dias,
];
```

El reporte es una vista Blade imprimible con CSS `@media print` embebido. Incluye:
- Encabezado con fechas (inicio, fin planeado, fin real) y equipo
- 4 tarjetas de métricas (total, completadas, a tiempo, con retraso)
- 3 tarjetas secundarias (% completado, % eficiencia, pendientes)
- Tabla completa de actividades con estatus, responsable, días planeados vs reales, eficiencia

---

## 10. Cálculo de Próxima Junta

**Método**: `Proyecto::siguienteFechaJunta($desde = null): Carbon`

Avanza fecha_inicio por la recurrencia hasta encontrar la primera fecha futura:

```php
$inicio = Carbon::parse($this->fecha_inicio);
$hoy = Carbon::now();

while ($inicio->lte($hoy)) {
    match($this->recurrencia) {
        'semanal'   => $inicio->addWeek(),
        'quincenal' => $inicio->addDays(15),
        'mensual'   => $inicio->addMonth(),
    }
}

return $inicio;
```

> **Nota**: `$desde` es opcional. Si se pasa, usa esa fecha como punto de partida en lugar de `fecha_inicio`. Útil para calcular la segunda o tercera junta futura.

---

## 11. Notificaciones de Asignación por Correo

**Mailable**: `App\Mail\ProyectoAsignado`  
**Archivo**: `app/Mail/ProyectoAsignado.php`

Se envía cuando:
- Se crea un proyecto con usuarios asignados (`store()`)
- Se asignan usuarios a un proyecto existente (`asignarUsuarios()`)
- Se asignan responsables TI (`asignarResponsablesTi()`)

**Argumentos del constructor**:
```php
new ProyectoAsignado($proyecto, $usuario, $tipo, $asignadoPor)
// $tipo: 'usuario' | 'responsable_ti'
```

La plantilla del correo varía el mensaje según `$tipo` (operativo vs soporte TI).

> **Advertencia de rendimiento**: Los correos se envían **síncronamente** en un loop `foreach`. Con muchos usuarios, el request puede tardar varios segundos o alcanzar el timeout HTTP. Ver sección de mantenimiento.

---

## 12. Referencia de Rutas

**Middleware**: `auth`  
**Prefijo**: `/proyectos`  
**Nombre base**: `proyectos.`

### Gestión del Proyecto

| Método | URI | Nombre | Auth requerido | Descripción |
|---|---|---|---|---|
| `GET` | `/proyectos` | `proyectos.index` | Cualquier usuario | Listado (filtrado por rol) |
| `POST` | `/proyectos` | `proyectos.store` | `isRhCoordinador()` | Crear proyecto |
| `GET` | `/proyectos/{id}` | `proyectos.show` | Miembro del proyecto | Dashboard del proyecto |
| `GET` | `/proyectos/{id}/edit` | `proyectos.edit` | `isRh()` | JSON: form de edición |
| `PUT` | `/proyectos/{id}` | `proyectos.update` | `isRhCoordinador()` | Guardar cambios |
| `DELETE` | `/proyectos/{id}` | `proyectos.destroy` | `isRhCoordinador()` | Archivar |
| `POST` | `/proyectos/{id}/restore` | `proyectos.restore` | `isRhCoordinador()` | Restaurar archivado |
| `DELETE` | `/proyectos/{id}/force` | `proyectos.forceDelete` | `isRh()` | Eliminar permanente |
| `POST` | `/proyectos/{id}/finalizar` | `proyectos.finalizar` | RH o responsable | Cerrar proyecto |
| `GET` | `/proyectos/{id}/reporte` | `proyectos.reporte` | Miembro del proyecto | Reporte post-mortem |

### Equipo de Trabajo

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `POST` | `/proyectos/{id}/usuarios` | `proyectos.asignarUsuarios` | Sync equipo operativo + correos |
| `DELETE` | `/proyectos/{id}/usuarios/{userId}` | `proyectos.quitarUsuario` | Quitar operativo individual |
| `POST` | `/proyectos/{id}/responsables-ti` | `proyectos.asignarResponsablesTi` | Sync equipo TI + correos |
| `DELETE` | `/proyectos/{id}/responsables-ti/{userId}` | `proyectos.quitarResponsableTi` | Quitar TI individual |
| `GET` | `/proyectos/usuarios/lista` | `proyectos.listaUsuarios` | JSON lista empleados activos |

### Actividades del Proyecto

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `GET` | `/proyectos/{proyecto}/actividades` | `proyectos.actividades` | Tablero Kanban |
| `POST` | `/proyectos/{proyecto}/actividades` | `proyectos.actividades.store` | Crear actividad |
| `GET` | `/proyectos/{proyecto}/actividades/{actividad}/edit` | `proyectos.actividades.edit` | JSON: form de edición |
| `PUT` | `/proyectos/{proyecto}/actividades/{actividad}` | `proyectos.actividades.update` | Actualizar actividad |
| `DELETE` | `/proyectos/{proyecto}/actividades/{actividad}` | `proyectos.actividades.destroy` | Eliminar actividad |

---

## 13. Historial de Migraciones

| Fecha | Archivo | Cambio |
|---|---|---|
| 2026-04-15 | `create_proyectos_tables.php` | Tablas `proyectos`, `proyecto_usuarios`; añade `proyecto_id` a `activities` |
| 2026-04-15 | `add_deleted_at_to_proyectos_table.php` | Columna `deleted_at` para `SoftDeletes` |
| 2026-04-15 | `add_finalizado_to_proyectos_table.php` | Columnas `fecha_fin_real` y `finalizado` |

**Nota sobre la tabla `proyecto_responsable_ti`**: No aparece en una migración separada encontrada. Si fue creada en `create_proyectos_tables.php` o en otra migración, verificar al hacer `php artisan migrate:status`.

---

## 14. Guía de Mantenimiento del Módulo

---

### 🔴 CRÍTICO: Envío síncrono de correos en `asignarUsuarios()`

```php
foreach ($request->usuarios as $usuarioId) {
    Mail::to($usuario->email)->send($correo); // Bloqueante
}
```

Si RH asigna 30 usuarios de golpe, el servidor intenta 30 conexiones SMTP consecutivas. El request puede tardar 60+ segundos y el usuario verá un "Gateway Timeout (504)".

**Solución obligatoria**: Cambiar `->send()` por `->queue()`:
```php
Mail::to($usuario->email)->queue($correo);
```
Requiere configurar el worker de colas (`php artisan queue:work`).

---

### 🔴 CRÍTICO: `asignarUsuarios()` con `sync()` es destructivo sin advertencia

`$proyecto->usuarios()->sync($request->usuarios)` **reemplaza toda la lista**. Si el form solo envía los nuevos usuarios y no los existentes, los anteriores son removidos silenciosamente.

**Uso correcto desde la vista**: El formulario de asignación debe cargar con todos los usuarios pre-seleccionados y la vista debe enviarlos como un array completo.

**Alternativa no destructiva**: Usar `syncWithoutDetaching()` si solo se quieren añadir sin quitar.

---

### 🔴 CRÍTICO: N+1 en `index()` — 2 queries por proyecto

```php
$proyectosConActividades = $proyectos->map(function ($p) {
    $p->total_actividades = $p->actividades()->count();        // Query 1
    $p->actividades_pendientes = $p->actividades()             // Query 2
        ->whereNotIn('estatus', ['Completado', 'Rechazado'])->count();
    return $p;
});
```

Con 50 proyectos → 100 queries adicionales a la BD.

**Solución**: Usar `withCount` en la query principal:
```php
$query->withCount([
    'actividades',
    'actividades as actividades_pendientes_count' => fn($q) =>
        $q->whereNotIn('estatus', ['Completado', 'Rechazado'])
]);
```

---

### 🟡 IMPORTANTE: `forceDelete()` hace soft delete del proyecto (no elimina físicamente)

El nombre implica eliminación permanente, pero `$proyecto->delete()` llama al soft delete de Laravel (solo llena `deleted_at`). El registro sigue en la BD.

Si se requiere eliminación física real: `$proyecto->forceDelete()` (método nativo de `SoftDeletes`).

---

### 🟡 IMPORTANTE: `listaUsuarios()` no es consumido por el frontend

El endpoint `GET /proyectos/usuarios/lista` devuelve JSON con empleados activos, pero la vista de asignación usa Blade para renderizar la lista directamente. El endpoint es código muerto.

**Opciones**: Eliminarlo para reducir la superficie de API, o migrar los modals de asignación a AJAX para consumirlo.

---

### 🟡 IMPORTANTE: `reporte()` carga todas las actividades en memoria

`$proyecto->actividades()->with('user')->orderBy('fecha_compromiso')->get()` — sin paginación. Un proyecto con 500 actividades cargará todas de golpe.

Para proyectos grandes, considerar paginación o chunking en el reporte.

---

### 🟢 SEGURO: Añadir un nuevo tipo de recurrencia (ej: `bimestral`)

1. Migración: ampliar el enum `recurrencia` en `proyectos`.
2. En `Proyecto::siguienteFechaJunta()`, añadir un caso al `switch`.
3. En `store()` y `update()`: añadir `bimestral` a la validación `in:semanal,quincenal,mensual,bimestral`.
4. En el formulario Blade, añadir la opción `<option value="bimestral">Bimestral</option>`.

---

### 🟢 SEGURO: Añadir campos al reporte post-mortem

En `reporte()`, los datos vienen de `$proyecto->metricas()`. Para añadir una nueva métrica:
1. Añadir la clave al array de retorno de `Proyecto::metricas()`.
2. Usar la clave en la vista `proyectos/reporte.blade.php`.
3. No se requiere migración.

---

### Checklist de deploy para cambios en Proyectos

- [ ] ¿Se añade campo a `Proyecto`? Migración + `$fillable` + `$casts` si es fecha/boolean + formulario de `store()` y `update()`.
- [ ] ¿Se activan colas de Laravel? Configurar `QUEUE_CONNECTION` en `.env` + proceso `queue:work` supervisado (Supervisor en Linux o tarea de Windows).
- [ ] ¿Se añade campo al reporte? Actualizar `Proyecto::metricas()` + `reporte.blade.php`.
- [ ] ¿Se añade tipo de recurrencia? Migración de enum + `siguienteFechaJunta()` + validaciones + vista.
- [ ] ¿Se cambia la lógica de visibilidad? Actualizar `index()` (filtrado de query) Y `show()` (validación de `$puedeVer`) — son independientes.
- [ ] ¿Se eliminan proyectos con actividades históricas importantes? `forceDelete()` borra las actividades de `activities`. Considerar `->update(['proyecto_id' => null])` en lugar de `->delete()` para preservar las actividades en el módulo general.

---

*Documentación generada el 27 de Abril de 2026 — ERP Estrategia e Innovación*
