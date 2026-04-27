# Módulo de Actividades — Documentación Maestra
> **ERP Estrategia e Innovación** · Versión documental: Abril 2026  
> Audiencia: Desarrolladores de mantenimiento, líderes técnicos y arquitectos de software

---

## Tabla de Contenido

1. [Visión General del Módulo](#1-visión-general-del-módulo)
2. [Arquitectura y Componentes](#2-arquitectura-y-componentes)
3. [Esquema de Base de Datos](#3-esquema-de-base-de-datos)
4. [Modelos Eloquent — Referencia Completa](#4-modelos-eloquent--referencia-completa)
5. [Máquina de Estados (Ciclo de Vida de una Actividad)](#5-máquina-de-estados-ciclo-de-vida-de-una-actividad)
6. [Motor de Cálculo Automático (KPIs y Eficiencia)](#6-motor-de-cálculo-automático-kpis-y-eficiencia)
7. [Jerarquía de Roles y Permisos](#7-jerarquía-de-roles-y-permisos)
8. [Referencia de Endpoints y Rutas](#8-referencia-de-endpoints-y-rutas)
9. [Referencia de Métodos del Controlador](#9-referencia-de-métodos-del-controlador)
10. [Sistema de Ventanas de Planeación](#10-sistema-de-ventanas-de-planeación)
11. [Exportaciones y Reportes](#11-exportaciones-y-reportes)
12. [Historial de Auditoría](#12-historial-de-auditoría)
13. [Historial de Migraciones](#13-historial-de-migraciones)
14. [Guía de Mantenimiento y Funciones Críticas](#14-guía-de-mantenimiento-y-funciones-críticas)
15. [Deuda Técnica Conocida y Mejoras Pendientes](#15-deuda-técnica-conocida-y-mejoras-pendientes)

---

## 1. Visión General del Módulo

El **Módulo de Actividades** es el núcleo operativo de planeación y seguimiento de tareas del ERP. Permite a cualquier empleado de la empresa registrar, planificar y cerrar formalmente sus actividades diarias/semanales, con trazabilidad completa de cada cambio realizado.

### Propósito de Negocio

| Necesidad | Solución implementada |
|---|---|
| Visibilidad de cargas de trabajo por equipo | Dashboard jerárquico con filtros por usuario, rango de fechas y proyecto |
| Control de cumplimiento de compromisos | Ciclo de aprobación de 6 estatus con transiciones controladas |
| Medición de eficiencia individual | Cálculo automático de KPIs: días planeados vs días reales |
| Prevenir manipulación de métricas | Bitácora inmutable en `activity_histories` con `getDirty()` |
| Planeación semanal masiva | Planeador de lunes (storeBatch) con ventanas de tiempo configurables |
| Justificación de horas a clientes | Reporte PDF por cliente con estadísticas de efectividad |
| Integración con presupuestos | Vinculación opcional a un `Proyecto` activo del módulo de proyectos |

### Características Principales

- **Delegación jerárquica**: Directores → cualquier empleado. Supervisores → su equipo. Empleados → a sí mismos.
- **Ciclo de aprobación estricto**: Un empleado no puede auto-completar una tarea; debe esperar la validación de su jefe.
- **Cálculo automático de eficiencia**: El modelo `Activity` recalcula métricas en cada `save()` via observer en `boot()`.
- **Auditoría total e inmutable**: Cada cambio de campo relevante genera un registro en `activity_histories`.
- **Ventanas de planeación configurables**: El coordinador define desde la UI el día y horario en que se permite el planeador semanal.

---

## 2. Arquitectura y Componentes

```
┌─────────────────────────────────────────────────────────┐
│                   MÓDULO DE ACTIVIDADES                 │
│                                                         │
│  routes/web.php                                         │
│    └── /activities  ──────────────────────────────────┐ │
│                                                       ↓ │
│  ActivityController.php  (único controlador)          │ │
│    ├── index()          → Vista: activities.index     │ │
│    ├── store()          → Creación unitaria           │ │
│    ├── storeBatch()     → Planeación semanal masiva   │ │
│    ├── update()         → Edición + log automático    │ │
│    ├── destroy()        → Eliminación jerarquizada    │ │
│    ├── start()          → Transición: Planeado→Proceso│ │
│    ├── approve()        → Transición: PorAprobar→Plan.│ │
│    ├── reject()         → Rechazo con motivo          │ │
│    ├── validateCompletion() → Cierre supervisado      │ │
│    ├── exportExcel()    → Descarga .xlsx multi-hoja   │ │
│    ├── generateClientReport() → Vista PDF imprimible  │ │
│    ├── getPlaneacionVentanas()  → JSON: listado       │ │
│    ├── savePlaneacionVentana()  → JSON: crear/editar  │ │
│    └── deletePlaneacionVentana() → JSON: eliminar     │ │
│                                                       │ │
│  Modelos Eloquent                                     │ │
│    ├── Activity.php          (pilar central + boot()) │ │
│    ├── ActivityHistory.php   (log de auditoría)       │ │
│    └── PlaneacionVentana.php (configuración horaria)  │ │
│                                                       │ │
│  Vistas Blade                                         │ │
│    ├── resources/views/activities/index.blade.php     │ │
│    └── resources/views/activities/report_print.blade.php│
└─────────────────────────────────────────────────────────┘
```

### Dependencias externas clave

| Dependencia | Uso |
|---|---|
| `Carbon\Carbon` | Todos los cálculos de fechas: días, semanas, trimestres, ventanas horarias |
| `PhpOffice\PhpSpreadsheet` | Generación de archivos `.xlsx` con estilos y múltiples hojas |
| `Illuminate\Support\Str` | Detección de palabras clave en posición jerárquica (`direcc`, `anexo 24`) |
| `Illuminate\Support\Facades\Storage` | Gestión de archivos de evidencia en disco `public` |
| `Illuminate\Support\Facades\DB` | Transacción atómica en `storeBatch()` |

---

## 3. Esquema de Base de Datos

### Tabla: `activities`

| Columna | Tipo | Nullable | Descripción |
|---|---|---|---|
| `id` | `bigint unsigned` | NO | PK auto-increment |
| `user_id` | `bigint unsigned` | NO | FK → `users.id`. **Quién ejecuta** la tarea |
| `asignado_por` | `bigint unsigned` | SÍ | FK → `users.id`. **Quién ordenó** la tarea (agregado en migración `2026_01_27`) |
| `area` | `varchar` | SÍ | Área organizacional (ej: "Logística", "IT") |
| `cliente` | `varchar` | SÍ | Cliente asociado (para reportes PDF) |
| `tipo_actividad` | `varchar` | SÍ | Clasificación: "Operativo", "Estratégico", etc. |
| `nombre_actividad` | `varchar(255)` | NO | Nombre descriptivo de la tarea |
| `comentarios` | `text` | SÍ | Bitácora de texto libre del responsable |
| `fecha_inicio` | `date` | SÍ | Fecha en que inició oficialmente |
| `fecha_compromiso` | `date` | NO | Fecha límite de entrega pactada |
| `fecha_final` | `date` | SÍ | Fecha real de cierre (`null` = aún abierta) |
| `prioridad` | `varchar` | NO | `'Alta'`, `'Media'`, `'Baja'` |
| `estatus` | `varchar` | NO | Ver [Ciclo de Vida](#5-máquina-de-estados-ciclo-de-vida-de-una-actividad) |
| `metrico` | `integer` | NO | **Días planeados** (calculado automáticamente) |
| `resultado_dias` | `integer` | SÍ | **Días reales** transcurridos hasta cierre |
| `porcentaje` | `decimal(10,2)` | SÍ | **% de eficiencia** (calculado automáticamente) |
| `evidencia_path` | `varchar` | SÍ | Ruta relativa al archivo adjunto en `storage/public` |
| `motivo_rechazo` | `varchar` | SÍ | Texto del supervisor al rechazar |
| `hora_inicio_programada` | `time` | SÍ | Hora de inicio del bloque de trabajo (planeador) |
| `hora_fin_programada` | `time` | SÍ | Hora de fin del bloque de trabajo (planeador) |
| `proyecto_id` | `bigint unsigned` | SÍ | FK → `proyectos.id` (nullable, agregado en 2026-04-15) |
| `created_at` / `updated_at` | `timestamp` | SÍ | Timestamps estándar Laravel |
| `deleted_at` | `timestamp` | SÍ | Soft delete (el registro no se borra físicamente) |

> **IMPORTANTE — Doble identidad**: `user_id` es quien **realiza** la tarea. `asignado_por` es quien la **encargó**. Cuando un empleado se auto-asigna, ambos campos tienen el mismo valor. Filtrar solo por `user_id` puede dar resultados incompletos para un supervisor que quiera ver sus delegaciones.

---

### Tabla: `activity_histories`

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | `bigint` | PK |
| `activity_id` | `bigint` | FK → `activities.id` (cascade delete) |
| `user_id` | `bigint` | FK → `users.id`. Quién realizó la acción |
| `action` | `varchar` | Tipo de evento: `'created'`, `'updated'`, `'approved'`, `'rejected'`, `'validated'`, `'file'` |
| `field` | `varchar` | Campo técnico modificado (uso futuro) |
| `old_value` | `text` | Valor anterior del campo |
| `new_value` | `text` | Valor nuevo del campo |
| `details` | `text` | Mensaje legible en español (ej: `"Cambió Estatus: 'Planeado' ➝ 'En proceso'"`) |
| `comentario` | `text` | Comentario adicional opcional |
| `created_at` | `timestamp` | Sello temporal del evento |

---

### Tabla: `planeacion_ventanas`

| Columna | Tipo | Descripción |
|---|---|---|
| `id` | `bigint` | PK |
| `dia_semana` | `tinyint` | ISO: `1=Lunes`, `2=Martes`, ..., `7=Domingo` |
| `hora_apertura` | `time` | Hora en que se habilita el planeador |
| `hora_cierre` | `time` | Hora en que se deshabilita el planeador |
| `activo` | `boolean` | Solo una ventana activa por día |
| `creado_por` | `bigint` | ID del coordinador que configuró la ventana |
| `created_at` / `updated_at` | `timestamp` | Timestamps estándar |

---

## 4. Modelos Eloquent — Referencia Completa

### `App\Models\Activity`

**Archivo**: `app/Models/Activity.php`

#### Traits y Casts

```php
use SoftDeletes;  // Eliminación lógica, no física

protected $casts = [
    'fecha_inicio'        => 'datetime',
    'fecha_compromiso'    => 'datetime',
    'fecha_final'         => 'datetime',
    'metrico'             => 'integer',
    'hora_inicio_programada' => 'datetime:H:i',
    'hora_fin_programada'    => 'datetime:H:i',
    'proyecto_id'         => 'integer',
];
```

#### Relaciones

| Método | Tipo | Descripción |
|---|---|---|
| `user()` | `BelongsTo(User)` | El empleado **responsable** de ejecutar la actividad |
| `asignador()` | `BelongsTo(User, 'asignado_por')` | El usuario que la **encargó/delegó** |
| `historial()` | `HasMany(ActivityHistory)` | Todos los eventos de auditoría, ordenados por `created_at DESC` |
| `proyecto()` | `BelongsTo(Proyecto)` | Proyecto asociado (opcional) |

#### ⚡ Observer en `boot()` — El Motor Central

Esta es la función más crítica del modelo. Se dispara en cada evento `saving` (antes de `INSERT` o `UPDATE`).

```
Evento saving → boot() calcula:
  1. metrico         = diffInDays(fecha_inicio, fecha_compromiso)
  2. resultado_dias  = diffInDays(fecha_inicio, fecha_final)  [solo si fecha_final existe]
  3. porcentaje      = (metrico / resultado_dias) × 100       [capeado a 100 si entregó a tiempo]
  4. estatus         = Auto-transiciona a 'Retardo' si hoy > fecha_compromiso y estatus = 'En proceso'
```

> **Regla de oro**: Nunca calcular manualmente `metrico`, `resultado_dias` ni `porcentaje` en el controlador. El modelo lo hace solo. Ver sección [Motor de Cálculo](#6-motor-de-cálculo-automático-kpis-y-eficiencia).

---

### `App\Models\ActivityHistory`

**Archivo**: `app/Models/ActivityHistory.php`

Modelo ligero de sólo lectura/escritura. No tiene lógica de negocio propia. Actúa como un log estructurado.

| Relación | Tipo |
|---|---|
| `user()` | `BelongsTo(User)` — Quién hizo el cambio |
| `activity()` | `BelongsTo(Activity)` — A qué tarea pertenece |

**Valores conocidos del campo `action`**:

| Valor | Cuándo se usa |
|---|---|
| `'created'` | Creación inicial de la actividad |
| `'updated'` | Cualquier cambio de campo |
| `'approved'` | Supervisor aprueba una solicitud `Por Aprobar` |
| `'rejected'` | Supervisor rechaza |
| `'validated'` | Supervisor valida el cierre `Por Validar` |
| `'file'` | Se adjunta un archivo de evidencia |

---

### `App\Models\PlaneacionVentana`

**Archivo**: `app/Models/PlaneacionVentana.php`

#### Métodos Estáticos Importantes

```php
// Verificar si AHORA MISMO la planeación está habilitada
PlaneacionVentana::estaAbierta(): bool

// Obtener la ventana activa del día actual
PlaneacionVentana::ventanaActual(): ?PlaneacionVentana
```

**Lógica de `estaAbierta()`**:
1. Busca una ventana activa en BD para el `isoWeekday()` actual.
2. Si existe, compara la hora actual contra `hora_apertura` y `hora_cierre`.
3. Si **no existe** ninguna configuración, aplica el **fallback hardcodeado**: Lunes de 09:00 a 11:00.

**Array `$diasNombres`** (útil para la UI):
```php
PlaneacionVentana::$diasNombres = [1=>'Lunes', 2=>'Martes', 3=>'Miércoles', 4=>'Jueves', 5=>'Viernes', 6=>'Sábado', 7=>'Domingo']
```

---

## 5. Máquina de Estados (Ciclo de Vida de una Actividad)

### Diagrama de Transiciones

```
                         ┌──────────────┐
         [Auto-asignada] │   En proceso │◄──────────────────────────────────┐
                         └──────┬───────┘                                   │
                                │ Usuario                                    │
                                │ completa                             [Rechazada:
                                ▼                                      usuario reabre]
                         ┌──────────────┐       ┌────────────┐
                         │ Por Validar  │──────►│  Rechazado │
                         └──────┬───────┘  Sup. └────────────┘
                                │ rechaza
                                │ Sup. valida
                                ▼
                         ┌──────────────────────┐
                         │ Completado           │
                         │ Completado con retardo│
                         └──────────────────────┘

  [Jefe asigna]    [Empleado propone]
       │                  │
       ▼                  ▼
  ┌──────────┐     ┌────────────┐
  │ Planeado │     │ Por Aprobar│
  └────┬─────┘     └─────┬──────┘
       │                 │ Sup. aprueba
       │                 ▼
       └──────────► ┌──────────┐
                    │ Planeado │
                    └────┬─────┘
                         │ Usuario inicia (start)
                         ▼
                    ┌──────────┐
                    │En proceso│ ◄─── (ver arriba)
                    └──────────┘
                         │ [Si hoy > fecha_compromiso y estatus=En proceso]
                         ▼
                    ┌──────────┐
                    │ Retardo  │
                    └──────────┘
                         │ Usuario completa igual
                         ▼
                  ┌─────────────────────┐
                  │ Completado con ret. │ (automático por el observer)
                  └─────────────────────┘
```

### Tabla Completa de Transiciones Permitidas

| Estado Origen | Quién puede transicionar | Estado Destino | Mecanismo |
|---|---|---|---|
| `Planeado` | Responsable | `En proceso` | Botón `start()` |
| `Por Aprobar` | Supervisor / Dirección | `Planeado` | Botón `approve()` |
| `Por Aprobar` | Supervisor / Dirección | `Rechazado` | Botón `reject()` |
| `En proceso` | Responsable | `Por Validar` | `update()` con estatus `Completado` (analistas van a `Por Validar`) |
| `En proceso` | Supervisor / Dirección | `Completado` | `update()` con estatus `Completado` (jefes cierran directo) |
| `En proceso` | Modelo (auto) | `Retardo` | Observer en `boot()` detecta `hoy > fecha_compromiso` |
| `Por Validar` | Supervisor / Dirección | `Completado` | `validateCompletion()` |
| `Por Validar` | Supervisor / Dirección | `Rechazado` | `reject()` |
| `Rechazado` | Responsable | `En proceso` | `update()` con estatus `En proceso` (reapertura) |

---

## 6. Motor de Cálculo Automático (KPIs y Eficiencia)

Este es el corazón del módulo. Toda la matemática de negocio vive **exclusivamente** en `Activity::boot()`.

### Cálculo de `metrico` (Días Planeados)

```
metrico = max(0, diffInDays(fecha_inicio, fecha_compromiso))
```

Representa cuántos días de trabajo se presupuestaron originalmente. Se recalcula en cada `save()`.

### Cálculo de `resultado_dias` (Días Reales)

```
si (fecha_final existe):
    resultado_dias = diffInDays(fecha_inicio, fecha_final)
sino:
    resultado_dias = null   ← tarea aún abierta
```

### Cálculo de `porcentaje` (Eficiencia %)

```
si fecha_final ≤ fecha_compromiso:
    porcentaje = 100   ← Entregó a tiempo o antes
sino:
    si resultado_dias > 0 y metrico > 0:
        porcentaje = (metrico / resultado_dias) × 100
    sino:
        porcentaje = 0
```

**Interpretación de valores**:

| Porcentaje | Significado |
|---|---|
| `100%` | Entregó en tiempo o antes. Eficiencia perfecta. |
| `50% – 99%` | Entregó con retardo. Usó más días de los planeados. |
| `< 50%` | Retardo severo. Los días reales casi duplican los planeados. |
| `null` | La actividad no ha sido cerrada. |

### Auto-transición de Estatus por Retardo

El observer también mueve el estatus automáticamente cuando:
- La actividad está en `'En proceso'`
- `hoy > fecha_compromiso`
- No está en ningún estado bloqueado (`Completado`, `Por Validar`, `Rechazado`, etc.)

En ese caso, el estatus cambia a `'Retardo'` al siguiente `save()`.

---

## 7. Jerarquía de Roles y Permisos

El sistema no usa Laravel Gates ni Policies formales. La autorización se determina **en tiempo de ejecución** dentro del controlador, evaluando la posición del empleado en la tabla `empleados`.

### Detección de Roles

```php
// Detección de Dirección
$esDireccion = Str::contains(mb_strtolower($empleado->posicion), 'direcc');

// Detección de Supervisor (tiene subordinados activos)
$subordinados = Empleado::where('supervisor_id', $miEmpleado->id)->pluck('user_id');
$esSupervisor = count($subordinados) > 0;

// Detección de Puesto Planificador (puede usar el planeador semanal)
$esPuestoPlanificador = Str::contains($posicion, ['anexo 24', 'anexo24', 'post-operacion', 'auditoria']);
```

### Matriz de Permisos por Acción

| Acción | Empleado | Supervisor | Dirección |
|---|---|---|---|
| Ver sus propias actividades | ✅ | ✅ | ✅ |
| Ver actividades de su equipo | ❌ | ✅ (solo subordinados directos) | ✅ (todos) |
| Crear tarea para otro | Solo → `Por Aprobar` | → `Planeado` (a subordinados) | → `Planeado` (a cualquiera) |
| Editar cualquier campo | Solo comentarios y estatus | ✅ + cambios completos | ✅ + cambios completos |
| Eliminar actividad | ❌ | ✅ (propias + subordinados + delegadas) | ✅ (todas) |
| Aprobar (`Por Aprobar`) | ❌ | ✅ (solo sus subordinados) | ✅ |
| Rechazar | ❌ | ✅ | ✅ |
| Validar cierre | ❌ | ✅ | ✅ |
| Exportar Excel | Solo propias | Propias + equipo | Todos los usuarios |
| Gestionar ventanas de planeación | ❌ | ❌ (solo `es_coordinador=true`) | ✅ |

### Caso Especial: Supervisor → Supervisor

Cuando un **supervisor asigna una tarea a otro supervisor** (coordinador → coordinador), la lógica de aprobación escala:

```php
$esCasoSupervisorASupervisor = $assignerIsSupervisor && $targetIsSupervisor;
if ($esCasoSupervisorASupervisor) {
    // Solo Dirección puede aprobar
    if (! $soyDireccion) abort(403);
}
```

---

## 8. Referencia de Endpoints y Rutas

**Todos los endpoints** requieren los middlewares `auth` y `verified`. Se registran en `routes/web.php`.

### Endpoints CRUD Estándar (Resource)

| Método HTTP | URI | Nombre de Ruta | Método Controlador | Descripción |
|---|---|---|---|---|
| `GET` | `/activities` | `activities.index` | `index()` | Dashboard principal |
| `POST` | `/activities` | `activities.store` | `store()` | Crear actividad singular |
| `GET` | `/activities/{id}` | `activities.show` | `show()` | Redirige a `index` (modales) |
| `PUT` | `/activities/{id}` | `activities.update` | `update()` | Editar actividad |
| `DELETE` | `/activities/{id}` | `activities.destroy` | `destroy()` | Eliminar (con permisos) |

### Endpoints de Transición de Estado

| Método HTTP | URI | Nombre de Ruta | Método Controlador |
|---|---|---|---|
| `PUT` | `/activities/{id}/start` | `activities.start` | `start()` |
| `PUT` | `/activities/{id}/approve` | `activities.approve` | `approve()` |
| `PUT` | `/activities/{id}/reject` | `activities.reject` | `reject()` |
| `PUT` | `/activities/{activity}/validate` | `activities.validate` | `validateCompletion()` |

### Endpoints Especiales

| Método HTTP | URI | Nombre de Ruta | Método Controlador | Respuesta |
|---|---|---|---|---|
| `POST` | `/activities/batch` | `activities.storeBatch` | `storeBatch()` | Redirect |
| `GET` | `/activities/export-excel` | `activities.export_excel` | `exportExcel()` | Descarga `.xlsx` |
| `GET` | `/activities/client-report` | `activities.client_report` | `generateClientReport()` | Vista blade imprimible |

### Endpoints de Ventanas de Planeación (JSON)

| Método HTTP | URI | Nombre de Ruta | Respuesta |
|---|---|---|---|
| `GET` | `/activities/planeacion-ventanas` | `activities.planeacion.ventanas` | `JSON { ventanas: [...] }` |
| `POST` | `/activities/planeacion-ventanas` | `activities.planeacion.save` | `JSON { success, ventana }` |
| `DELETE` | `/activities/planeacion-ventanas/{id}` | `activities.planeacion.delete` | `JSON { success }` |

---

## 9. Referencia de Métodos del Controlador

**Archivo**: `app/Http/Controllers/ActivityController.php`

---

### `index(Request $request)`

El método más complejo del módulo. Prepara todo el estado necesario para renderizar el dashboard.

**Parámetros GET opcionales**:

| Parámetro | Tipo | Descripción |
|---|---|---|
| `range` | `string` | `'week'` (default), `'month'`, `'quarter'` |
| `ref_date` | `date` | Fecha de referencia para el rango seleccionado |
| `date_start` / `date_end` | `date` | Rango personalizado (activa modo `'custom'`) |
| `user_id` | `int` | Ver actividades de otro usuario (solo supervisores/dirección) |
| `filter_origin` | `string` | `'todos'`, `'propias'`, `'delegadas'`, `'recibidas'` |
| `proyecto_id` | `int\|'sin_proyecto'` | Filtrar por proyecto asociado |
| `search` | `string` | Búsqueda libre por nombre, cliente o área |
| `ver_historial` | `'1'` | Mostrar tareas completadas/rechazadas del período |

**Variables enviadas a la vista** (`activities.index`):

| Variable | Tipo | Descripción |
|---|---|---|
| `$mainActivities` | `Collection<Activity>` | Actividades filtradas y ordenadas |
| `$kpis` | `array` | Total, completadas, en proceso, planeadas, retardos |
| `$esDireccion` / `$esSupervisor` | `bool` | Contexto del rol del usuario actual |
| `$puedePlanificar` | `bool` | Si puede acceder al planeador semanal en este momento |
| `$globalPendingCount` | `int` | Número de actividades de subordinados en `Por Aprobar`/`Por Validar` |
| `$usersWithPending` | `array` | IDs de usuarios con pendientes (para alertas visuales) |
| `$misRechazos` | `Collection` | Actividades propias rechazadas (para alertas) |
| `$teamUsers` | `Collection<User>` | Usuarios del equipo para el selector de cambio de vista |
| `$proyectos` | `Collection<Proyecto>` | Proyectos accesibles por el usuario actual |

**Ordenamiento de actividades** (en ese orden de prioridad):
1. Actividades propias primero (`user_id = $user->id`)
2. Las no-completadas primero
3. Por prioridad: `Alta` → `Media` → `Baja`
4. Por `fecha_compromiso` ascendente
5. Por `created_at` descendente
6. Por `hora_inicio_programada`

---

### `store(Request $request)`

Crea una actividad individual.

**Validaciones requeridas**:
```
nombre_actividad: required|max:255
fecha_compromiso: required|date
area:             required|string
```

**Regla de estatus inicial** (función crítica para mantenimiento):

```php
if ($targetUserId == $currentUser->id) {
    $estatus = 'En proceso';          // Auto-asignación
} elseif ($soyDireccion) {
    $estatus = 'Planeado';            // Dirección asigna
} elseif ($soySuJefeDirecto) {
    $estatus = 'Planeado';            // Jefe directo asigna
} else {
    $estatus = 'Por Aprobar';         // Cualquier otro caso
}
```

---

### `storeBatch(Request $request)`

Planeador semanal masivo. **Sólo funciona dentro de la ventana de planeación activa**.

**Validaciones requeridas**:
```
semana_inicio: required|date
plan:          array
```

**Estructura esperada del parámetro `plan`** (POST body):
```json
{
  "semana_inicio": "2026-04-27",
  "plan": {
    "0": [  // Lunes (índice 0 = +0 días de semana_inicio)
      { "actividad": "Auditar facturas", "area": "Logística", "cliente": "ABC", "tipo": "Operativo", "start_time": "09:00", "end_time": "11:00" }
    ],
    "1": [  // Martes
      { "actividad": "Reunión técnica", "area": "IT", "start_time": "10:00", "end_time": "11:30" }
    ]
  }
}
```

Todas las actividades creadas por este método nacen con estatus `'Por Aprobar'`.  
La operación es atómica (envuelta en `DB::transaction`).

> ⚠️ **Bug conocido**: Este método tiene su propia validación hardcodeada (`isMonday() && hour >= 9 && hour < 11`) **adicional** a la verificación de `PlaneacionVentana::estaAbierta()` que hace `index()`. Ver [Deuda Técnica](#15-deuda-técnica-conocida-y-mejoras-pendientes).

---

### `update(Request $request, $id)`

El método más complejo de escritura. Aplica lógica diferencial según el rol del usuario.

**Lógica de permisos de edición**:
```
¿Es Dirección o Supervisor directo o Asignador?
    Sí → Puede editar todos los campos (fill completo)
    No → Solo puede editar comentarios + transición de estatus
```

**Sistema de log automático** (crítico para auditoría):
```php
foreach ($activity->getDirty() as $campo => $nuevoValor) {
    // Si el campo está en $mapaCampos, crear ActivityHistory
    ActivityHistory::create([...]);
}
```

Campos auditados automáticamente: `nombre_actividad`, `estatus`, `prioridad`, `fecha_compromiso`, `hora_inicio_programada`, `hora_fin_programada`, `comentarios`, `cliente`, `area`, `proyecto_id`.

---

### `approve(Request $request, $id)`

Aprueba una actividad en estado `'Por Aprobar'`.

**Validaciones de jerarquía**:
1. Detecta si es caso `Supervisor → Supervisor` (requiere Dirección para aprobar).
2. En caso normal, solo el jefe directo del responsable puede aprobar.

**Efecto**: `estatus = 'Planeado'`, limpia `motivo_rechazo`.

---

### `reject(Request $request, $id)`

**Parámetro**: `motivo` (texto libre del rechazo).  
**Efecto**: `estatus = 'Rechazado'`, guarda `motivo_rechazo`.  
**Requiere**: Ser Dirección o Supervisor directo.

---

### `start($id)`

**Requiere**: Ser el `user_id` de la actividad (el responsable).  
**Efecto**: `estatus = 'En proceso'`, actualiza `fecha_inicio = now()`.

---

### `validateCompletion(Request $request, $id)`

**Requiere**: Ser Dirección o Supervisor directo.  
**Efecto**: `estatus = 'Completado'`, `fecha_final = now()`.  
El observer en `boot()` calculará los días y porcentaje al guardar.

---

### `destroy($id)`

**Implementa soft delete** (el registro queda en BD con `deleted_at` no nulo).

**Condiciones para poder eliminar** (debe cumplirse alguna):
- Dirección → puede borrar propias, de subordinados o delegadas por él.
- Supervisor → igual que Dirección pero solo dentro de su equipo.
- Cualquier otro → `abort(403)`.

---

### `exportExcel(Request $request)`

Genera un archivo `.xlsx` multi-hoja (una hoja por usuario seleccionado).

**Parámetros GET**:
```
date_start: date (default: inicio semana actual)
date_end:   date (default: fin semana actual)
user_ids[]: array de IDs (default: solo el usuario actual)
```

**Columnas del Excel** (en ese orden):
`#`, `Descripción`, `Prioridad`, `Cliente`, `Área`, `Responsable`, `Asignado Por`, `F. Asignación`, `F. Compromiso`, `H. Inicio`, `F. Final`, `Días`, `%`, `Estatus`, `Comentarios`

**Estilos aplicados**:
- Encabezados: fondo `#4F46E5` (índigo), texto blanco, negrita.
- Columna `Prioridad`: fondo por nivel (rojo=Alta, amarillo=Media, azul=Baja).
- Columna `Estatus`: fondo por color semántico, texto blanco.
- Auto-filtro en fila 1, auto-size en todas las columnas.

---

### `generateClientReport(Request $request)`

Genera una vista blade imprimible (PDF-friendly) con las actividades de un cliente en un mes.

**Parámetros GET requeridos**:
```
cliente_reporte: required|string
mes_reporte:     required|date_format:Y-m  (ej: "2026-03")
```

**Estadísticas calculadas**:
- Total de actividades encontradas
- Completadas vs. En proceso
- `efectividad` = (completadas / total) × 100

---

## 10. Sistema de Ventanas de Planeación

Permite configurar dinámicamente en qué día y horario los empleados del puesto planificador pueden llenar el planeador semanal.

### Flujo Completo

```
1. Coordinador accede a la UI de gestión de ventanas
2. Frontend llama GET /activities/planeacion-ventanas → lista de ventanas activas
3. Coordinador configura: dia_semana=1, hora_apertura=09:00, hora_cierre=11:00
4. Frontend llama POST /activities/planeacion-ventanas
5. Sistema desactiva cualquier ventana previa del mismo día antes de crear la nueva
6. Cuando un empleado intenta planificar:
   → index() llama PlaneacionVentana::estaAbierta()
   → Si true: $puedePlanificar = true (muestra el planeador en la UI)
   → Si false: el planeador está oculto
```

### Gestión de Ventanas

**Crear/Reemplazar ventana**:
```http
POST /activities/planeacion-ventanas
Content-Type: application/json

{
  "dia_semana": 1,
  "hora_apertura": "09:00",
  "hora_cierre": "11:00"
}
```
> Solo usuarios con `empleado.es_coordinador = true` pueden ejecutar esta acción.

**Eliminar ventana**:
```http
DELETE /activities/planeacion-ventanas/{id}
```

---

## 11. Exportaciones y Reportes

### Reporte Excel (`/activities/export-excel`)

- **Librería**: `PhpOffice\PhpSpreadsheet`
- **Formato**: `.xlsx`
- **Nombre del archivo**: `Actividades_{fecha_inicio}_{fecha_fin}.xlsx`
- **Entrega**: `response()->streamDownload()` — sin cargar el archivo en disco.

### Reporte de Cliente (`/activities/client-report`)

- **Vista Blade**: `resources/views/activities/report_print.blade.php`
- **Uso**: Justificar horas trabajadas para un cliente en un período mensual.
- **Optimizado para impresión**: La vista está diseñada para enviar a impresora o exportar como PDF desde el navegador.

---

## 12. Historial de Auditoría

El sistema de auditoría es uno de los pilares de seguridad del módulo. Funciona mediante el método `getDirty()` de Eloquent en `update()`.

### ¿Qué se registra automáticamente?

Cada vez que se llama a `update()`, el sistema recorre los campos "sucios" (modificados) y crea un `ActivityHistory` por cada cambio en los campos auditados:

```
nombre_actividad → "Actividad"
estatus          → "Estatus"
prioridad        → "Prioridad"
fecha_compromiso → "Fecha Compromiso"
hora_inicio_programada → "Hora Inicio"
hora_fin_programada    → "Hora Fin"
comentarios      → "Comentarios" (mensaje especial: "Actualizó comentarios")
cliente          → "Cliente"
area             → "Área"
proyecto_id      → "Proyecto"
```

### Mensaje de auditoría generado

Para campos normales:
```
"Cambió Estatus: 'Planeado' ➝ 'En proceso'"
```

Para comentarios:
```
"Actualizó comentarios / bitácora"
```

### Registros manuales adicionales

Además del sistema automático, se crean registros manuales en:
- `approve()` → `action='approved'`, details='Aprobó la actividad'
- `reject()` → `action='rejected'`, details='Rechazó: {motivo}'
- `validateCompletion()` → `action='validated'`, details='Validó el cierre de la actividad'
- `start()` → `action='updated'`, details='Inició ejecución'
- `update()` con evidencia → `action='file'`, details='Adjuntó evidencia'

---

## 13. Historial de Migraciones

| Fecha | Archivo | Cambio |
|---|---|---|
| 2025-12-22 | `create_activities_tables.php` | Creación de tablas `activities` y `activity_histories` |
| 2026-01-09 | `add_cliente_to_activities_table.php` | Agrega campo `cliente` para reportes por cliente |
| 2026-01-12 | `add_motivo_rechazo_to_activities_table.php` | Agrega `motivo_rechazo` para justificar rechazos |
| 2026-01-12 | `add_planned_times_to_activities_table.php` | Agrega `hora_inicio_programada` y `hora_fin_programada` (planeador) |
| 2026-01-27 | `add_asignado_por_to_activities_table.php` | ⭐ **Cambio de paradigma**: separa `user_id` (ejecutor) de `asignado_por` (delegador) |
| 2026-04-06 | `create_planeacion_ventanas_table.php` | Crea tabla `planeacion_ventanas` para configuración dinámica del planeador |
| 2026-04-15 | `create_proyectos_tables.php` | Agrega `proyecto_id` nullable a `activities` (FK → `proyectos`) |

---

## 14. Guía de Mantenimiento y Funciones Críticas

Esta sección es la **referencia primaria** para cualquier desarrollador que deba modificar el módulo.

---

### 🔴 CRÍTICO: No tocar sin leer esto primero

#### 1. El Observer `boot()` en `Activity.php`

**Función**: `Activity::boot()` → closure en `static::saving()`  
**Archivo**: `app/Models/Activity.php`, líneas ~68-130

Si necesitas **agregar un nuevo cálculo automático** (por ejemplo, calcular días hábiles en lugar de días calendario), hazlo **únicamente aquí**. Nunca dupliques esta lógica en el controlador.

Si necesitas **agregar un nuevo campo calculado**, sigue el patrón existente:
1. Agrega la migración para el campo.
2. Agrégalo a `$fillable` y `$casts`.
3. Añade su cálculo en el closure de `saving`.

**¿Cuándo NO modificar `boot()`?**: Si el cambio es solo de comportamiento del controlador (por ejemplo, enviar un correo al completar). Eso va en el controlador o en un Job/Listener.

---

#### 2. La Lógica de Jerarquía en `store()` y `approve()`

**Archivo**: `app/Http/Controllers/ActivityController.php`

La determinación de si alguien es "jefe directo" de otro se hace comparando:
```php
$targetUser->empleado->supervisor_id === $currentUser->empleado->id
```

Si en el futuro se cambia el esquema de jerarquía en la tabla `empleados` (por ejemplo, soporte para múltiples supervisores), **estas comparaciones deben actualizarse** en los métodos: `store()`, `update()`, `approve()`, `reject()`, `validateCompletion()`, `destroy()` y `index()`.

**Recomendación futura**: Centralizar esta lógica en una Laravel Policy (`ActivityPolicy`) para evitar actualizar 6 métodos al mismo tiempo.

---

#### 3. `PlaneacionVentana::estaAbierta()`

**Archivo**: `app/Models/PlaneacionVentana.php`

Este método controla el acceso al planeador. El **fallback hardcodeado** (lunes 9-11) se activa cuando no hay ninguna ventana configurada en BD.

Si se agrega soporte para múltiples ventanas en el mismo día, este método debe modificarse para iterar entre ellas en lugar de retornar en el primer resultado.

---

### 🟡 IMPORTANTE: Puntos de extensión frecuentes

#### Agregar un nuevo estatus

1. Actualizar la lógica de transiciones en `update()` (método `ActivityController::update()`).
2. Actualizar el observer en `Activity::boot()` si el nuevo estatus implica cálculos automáticos.
3. Actualizar el array `$estatusBloqueados` dentro de `boot()` para definir si el nuevo estatus puede ser sobreescrito por el observer.
4. Agregar el color correspondiente en `exportExcel()` (array `match ($act->estatus)`).
5. Actualizar los filtros en `index()` que usan `whereNotIn('estatus', [...])`.
6. Actualizar la vista `activities/index.blade.php` para mostrar el nuevo estatus con su estilo visual.

---

#### Agregar un nuevo campo auditable

En `ActivityController::update()`, agregar el campo al array `$mapaCampos`:
```php
$mapaCampos = [
    'nuevo_campo' => 'Nombre Legible',
    // ... campos existentes
];
```
El sistema lo auditará automáticamente sin código adicional.

---

#### Agregar un nuevo filtro en el Dashboard

En `ActivityController::index()`, extender la lógica de `$filterOrigin` con el nuevo caso:
```php
} elseif ($filterOrigin === 'mi_nuevo_filtro') {
    $query->where(/* lógica del filtro */);
}
```
También actualizar la vista para mostrar el nuevo botón de filtro.

---

#### Cambiar la columna de ordenamiento en el Dashboard

El ordenamiento se aplica justo antes del `->get()` en `index()`:
```php
$mainActivities = $query
    ->orderByRaw(...)  // ← aquí
    ->get();
```

---

#### Agregar una nueva hoja o columna al Excel

En `ActivityController::exportExcel()`, buscar el array `$headers` y agregar la columna. Luego agregar el `setCellValue` correspondiente en el loop de filas.

---

### 🟢 SEGURO: Cambios de bajo riesgo

- Modificar textos de mensajes `redirect()->with('success', '...')`.
- Cambiar los colores HEX en el Excel (arrays `$statusColor` y `$priorityColor`).
- Agregar nuevos campos al formulario del planeador sin cambiar su estructura base.
- Agregar validaciones de reglas adicionales en `store()` o `update()` sin cambiar el flujo de estatus.

---

### Checklist antes de hacer un deploy con cambios al módulo

- [ ] ¿El cambio afecta `Activity::boot()`? Verificar que los 3 cálculos (métrico, resultado_dias, porcentaje) siguen siendo correctos.
- [ ] ¿Se agregó o renombró un campo? Agregar migración y actualizar `$fillable` y `$casts` en el modelo.
- [ ] ¿Cambió la lógica de jerarquía? Revisar los 6 métodos que comparan `supervisor_id`.
- [ ] ¿Se añadió un nuevo estatus? Verificar el array `$estatusBloqueados` en `boot()`.
- [ ] ¿El nuevo endpoint requiere autenticación? Verificar que está dentro del grupo `middleware(['auth', 'verified'])` en `routes/web.php`.
- [ ] ¿El nuevo campo debe auditarse? Agregarlo al array `$mapaCampos` en `update()`.

---

## 15. Deuda Técnica Conocida y Mejoras Pendientes

### Alta Prioridad

#### Bug: `storeBatch()` ignora la configuración de ventanas

**Archivo**: `ActivityController.php`, método `storeBatch()`  
**Problema**: Tiene una validación hardcodeada `if (! (now()->isMonday() && ...))` que siempre sobreescribe lo que diga `planeacion_ventanas`.  
**Solución**: Reemplazar ese bloque por:
```php
if (! PlaneacionVentana::estaAbierta()) {
    return redirect()->back()->with('error', 'El periodo de planificación ha cerrado.');
}
```

---

### Media Prioridad

#### Fat Controller: `index()` supera las 200 líneas

**Problema**: `index()` maneja permisos, fechas, queries, KPIs y alertas en un solo método.  
**Solución recomendada**: Introducir Eloquent Scopes en el modelo y extraer la lógica de fechas a un helper:

```php
// En Activity.php, añadir scopes:
public function scopePorJerarquia($query, User $user, array $idsVisibles): Builder { ... }
public function scopeRangoFechas($query, Carbon $start, Carbon $end): Builder { ... }

// En un helper o clase DateRangeResolver:
public static function resolve(Request $request): array { ... }
```

---

#### Duplicidad de lógica de `fecha_final` entre controlador y modelo

**Problema**: `update()` asigna manualmente `fecha_final = now()` cuando el estatus es `'Completado'`. El observer en `boot()` hace lo mismo.  
**Solución**: Eliminar la asignación manual en el controlador. Dejar que el modelo sea la única fuente de verdad.

---

#### N+1 en detección de supervisores

**Problema**: Dentro de los métodos `store()`, `approve()` y `destroy()` se ejecuta `Empleado::where('supervisor_id', $id)->exists()` múltiples veces en el mismo request.  
**Solución**: Usar `with('empleado.subordinados')` al cargar el usuario, o centralizar la detección de roles en un servicio / Policy.

---

### Baja Prioridad (Mejoras futuras)

- Implementar `ActivityPolicy` de Laravel para centralizar toda la autorización.
- Convertir el ordenamiento multi-criterio de `index()` en un Eloquent Scope reutilizable.
- Agregar índices compuestos en BD para `(user_id, estatus)` y `(asignado_por, estatus)` para mejorar performance en empresas con muchos registros.
- El campo `tipo_actividad` no tiene un catálogo fijo; considerar una tabla `activity_types` o un enum en BD.

---

*Documentación generada el 27 de Abril de 2026 — ERP Estrategia e Innovación*
