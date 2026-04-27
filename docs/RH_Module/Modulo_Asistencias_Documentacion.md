# Módulo RH — Reloj Checador y Asistencias — Documentación Maestra
> **ERP Estrategia e Innovación** · Versión documental: Abril 2026  
> Audiencia: Recursos Humanos, coordinadores de área, desarrolladores

---

## Tabla de Contenido

1. [Visión General](#1-visión-general)
2. [Arquitectura del Módulo](#2-arquitectura-del-módulo)
3. [Modelo de Datos](#3-modelo-de-datos)
4. [Dashboard de KPIs de Asistencia](#4-dashboard-de-kpis-de-asistencia)
5. [Importación Asíncrona desde Reloj Biométrico](#5-importación-asíncrona-desde-reloj-biométrico)
6. [Gestión Manual de Registros](#6-gestión-manual-de-registros)
7. [Regla de Retardo: Las 8:40](#7-regla-de-retardo-las-840)
8. [Herramientas de Limpieza y Reversión](#8-herramientas-de-limpieza-y-reversión)
9. [Avisos de Asistencia (Actas Administrativas)](#9-avisos-de-asistencia-actas-administrativas)
10. [Vista de Equipo para Coordinadores](#10-vista-de-equipo-para-coordinadores)
11. [Referencia de Rutas](#11-referencia-de-rutas)
12. [Historial de Migraciones](#12-historial-de-migraciones)
13. [Guía de Mantenimiento del Módulo](#13-guía-de-mantenimiento-del-módulo)

---

## 1. Visión General

El módulo de **Reloj Checador** procesa y administra los registros de asistencia del personal, importados desde relojes biométricos (ZKTeco y similares). Provee un dashboard con KPIs en tiempo real, permite justificar incidencias, y envía avisos de sanción cuando se detectan retrasos o faltas recurrentes.

### Propósito de negocio

| Necesidad | Solución |
|---|---|
| Importar datos masivos del biométrico | `start()` + `ProcesarAsistenciaService` (async con Cache) |
| Ver progreso de la importación en vivo | `progress(key)` — consulta el Cache mientras el servidor procesa |
| KPIs de puntualidad del mes | `index()` — faltas, retardos, horas totales, % asistencia |
| Justificar vacaciones o incapacidades masivamente | `store()` — rango de fechas, todos los empleados o uno |
| Corrección manual de una asistencia individual | `update()` + `storeManual()` |
| Deshacer una importación por rango | `clearRango()` / `revertirRango()` |
| Notificar formalmente a un empleado | `enviarAviso()` + `AvisoAsistenciaMailable` |
| Coordinador ve solo su equipo | `equipo()` — filtra por `supervisor_id` |

---

## 2. Arquitectura del Módulo

```
┌──────────────────────────────────────────────────────────────────────┐
│              RELOJ CHECADOR Y ASISTENCIAS                            │
│                                                                      │
│  Middleware: auth, area.rh                                           │
│  Prefijo URL: /recursos-humanos/reloj                                │
│  Nombre base: rh.reloj.                                              │
│                                                                      │
│  RelojChecadorImportController                                       │
│  ┌────────────────────────────────────────────────────────────────┐  │
│  │  index()          → Dashboard KPIs del período                 │  │
│  │  equipo()         → Vista reducida solo subordinados           │  │
│  │  start()          → Recibe Excel del biométrico, inicia proceso│  │
│  │  progress(key)    → JSON: % avance de la importación           │  │
│  │  store()          → Justificaciones masivas (rango + empleados)│  │
│  │  storeManual()    → Registro manual de entrada/salida          │  │
│  │  update({id})     → Edición de un registro individual          │  │
│  │  revertir({id})   → Revierte un registro a su estado biométrico│  │
│  │  revertirRango()  → Revierte rango de un empleado              │  │
│  │  clear()          → TRUNCATE completo de asistencias           │  │
│  │  clearRango()     → DELETE por rango de fechas                 │  │
│  │  enviarAviso()    → Crea AvisoAsistencia + envía email         │  │
│  └────────────────────────────────────────────────────────────────┘  │
│                                                                      │
│  Dependencias                                                        │
│  ────────────                                                         │
│  ProcesarAsistenciaService → Parseo de Excel del biométrico          │
│  Cache::put/get            → Estado del progreso de importación      │
│  AvisoAsistenciaMailable   → Mailable de sanción                     │
│  Carbon                    → Manejo de fechas y horas                │
└──────────────────────────────────────────────────────────────────────┘
```

---

## 3. Modelo de Datos

### `Asistencia` — Tabla `asistencias`

Un registro por empleado por día laborable.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | `bigint` PK | |
| `empleado_id` | `bigint` FK nullable | FK → `empleados.id` |
| `empleado_no` | `varchar` | Número de nómina (desnormalizado) |
| `nombre` | `varchar` | Nombre del empleado (desnormalizado) |
| `fecha` | `date` | Fecha del registro |
| `entrada` | `time` nullable | Hora de entrada (formato HH:MM o HH:MM:SS) |
| `salida` | `time` nullable | Hora de salida |
| `checadas` | `json` | Array de todas las checadas del día del biométrico |
| `tipo_registro` | `enum` | `asistencia`, `falta`, `vacaciones`, `incapacidad`, `home_office`, `incompleto` |
| `es_retardo` | `boolean` | Entrada después de las 08:40 |
| `es_justificado` | `boolean` | Si el retardo/falta está justificado |
| `comentarios` | `varchar(255)` nullable | Justificación o nota |
| `created_at` / `updated_at` | `timestamp` | |

### `AvisoAsistencia` — Tabla `aviso_asistencias`

Actas administrativas leves vinculadas a incidencias de asistencia.

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | `bigint` PK | |
| `empleado_id` | `bigint` FK | FK → `empleados.id` |
| `enviado_por_id` | `bigint` FK | FK → `users.id` — quién lo emitió |
| `tipo_aviso` | `varchar` | Tipo de incidencia (retardo, falta...) |
| `motivo` | `text` | Descripción de la incidencia |
| `fecha_incidencia` | `date` | Fecha de la incidencia |
| `status` | `varchar` | `pendiente`, `resuelto` |
| `resuelto_en` | `timestamp` nullable | Cuándo se resolvió |

---

## 4. Dashboard de KPIs de Asistencia

**Ruta**: `GET /recursos-humanos/reloj?fecha_inicio=YYYY-MM-DD&fecha_fin=YYYY-MM-DD`

El `index()` calcula los siguientes indicadores para el período seleccionado:

| KPI | Cálculo |
|---|---|
| `totalRegistros` | `Asistencia::count()` en el rango |
| `asistenciasOk` | Registros con `es_retardo = false` |
| `retardos` | `es_retardo = true AND es_justificado = false` |
| `faltas` | `tipo_registro = 'falta'` |
| `horasTotales` | Suma de minutos entre `entrada` y `salida` de todos los registros con ambos tiempos, formateado como `H:MM` |
| `porcentajeAsistencia` | `(asistenciasOk / totalRegistros) * 100` |
| `topRetardos` | Top 3 empleados con más retardos injustificados del período |

**Período por defecto**: Mes actual (`startOfMonth()` → `endOfMonth()`).

**Vista matricial**: Los empleados se muestran en una grilla donde las columnas son los días hábiles del período (excluyendo sábados y domingos). La grilla usa eager-loading para cargar todas las asistencias del período en una sola query.

---

## 5. Importación Asíncrona desde Reloj Biométrico

La importación de asistencias es el proceso más crítico del módulo. Se realiza en dos pasos para no bloquear el navegador:

### Paso 1: Iniciar importación (`POST /start`)

```
Browser → POST /recursos-humanos/reloj/start
         { archivo: [Excel], progress_key: "uuid-unico" }
         
Controller → Guarda el archivo en storage/imports/reloj/
          → Instancia ProcesarAsistenciaService
          → Llama a $service->process($path, true, $callback, $filtroEmpleados)
          → El callback actualiza Cache con % de progreso
          → Retorna JSON { success: true } al terminar
```

**El `progress_key`** es un UUID generado por el frontend antes del request. Se usa para identificar el proceso en Cache.

### Paso 2: Consultar progreso (`GET /progress/{key}`)

El frontend hace polling a este endpoint cada X milisegundos:

```php
// Controller: progress(string $key): JsonResponse
return response()->json(Cache::get($key) ?? ['percent' => 0, 'finalizado' => false]);
```

**Estructura de la respuesta del Cache**:
```json
{
  "status": "procesando|completado|error",
  "percent": 75,
  "mensaje": "Procesando registros...",
  "finalizado": false
}
```

El Cache tiene TTL de 10 minutos (`now()->addMinutes(10)`).

### `ProcesarAsistenciaService`

**Archivo**: `app/Services/ProcesarAsistenciaService.php`

El servicio lee el Excel del biométrico (formato propio de ZKTeco: columnas `No.`, `Nombre`, `ID del Empleado`, y checadas de hora por día). Para cada empleado y fecha:
1. Identifica la primera checada como `entrada` y la última como `salida`
2. Detecta si la entrada es retardo (> 08:40)
3. Si entrada existe pero salida no → `tipo_registro = 'incompleto'`
4. Usa `updateOrCreate(['empleado_id', 'fecha'])` para no duplicar registros
5. La transacción está dentro del servicio (no bloquea la BD durante la lectura del Excel)

**Filtro de empleados opcional**: `$filtroEmpleados` — solo procesa los IDs indicados.

---

## 6. Gestión Manual de Registros

### `store(Request $request): RedirectResponse`

**Ruta**: `POST /recursos-humanos/reloj/store`

Registra incidencias (vacaciones, incapacidades, home office, faltas) para un rango de fechas. Soporta:
- Un empleado específico (`empleado_id = {id}`)
- Todos los empleados (`empleado_id = 'all'`)

**Con atomicidad (`DB::transaction`)**:
- Si falla el registro 99 de 200, se revierten los 98 anteriores.
- Usa `lockForUpdate()` para evitar condiciones de carrera en registros existentes.

**Lógica de upsert**:
- Si existe registro: `update()` con los nuevos datos
- Si no existe: `create()` con `checadas = '[]'` y horas nulas

---

### `storeManual(Request $request): RedirectResponse`

**Ruta**: `POST /recursos-humanos/reloj/store-manual`

Para un empleado individual y rango de fechas, registra `entrada` y `salida` manual. Calcula si hay retardo automáticamente:

```php
$horaEntrada = Carbon::createFromFormat('H:i', $entrada);
$limite = Carbon::createFromFormat('H:i', '08:40');
$esRetardo = $horaEntrada->gt($limite);
```

Salta fines de semana con `isWeekend()`. Usa `updateOrCreate` (no lanza error si ya existe).

---

### `update(Request $request, $id): JsonResponse|RedirectResponse`

**Ruta**: `PUT /recursos-humanos/reloj/update/{id}`

Edición rápida de un registro individual. Campos editables: `tipo_registro`, `comentarios`, `es_justificado`. Envuelto en `DB::transaction` por buena práctica. Soporta respuesta JSON si el request es `ajax()`.

---

## 7. Regla de Retardo: Las 8:40

La hora límite de entrada sin retardo es **08:40**. Esta regla se aplica en dos lugares:

1. **Importación desde biométrico** (`ProcesarAsistenciaService`): al calcular `es_retardo` para cada checada.
2. **Registro manual** (`storeManual()`): al calcular `es_retardo` del `entrada` capturado.

El umbral de **08:40** está hardcoded en ambos lugares. Para cambiarlo, debe actualizarse en ambos archivos.

---

## 8. Herramientas de Limpieza y Reversión

### `clear(): RedirectResponse`

**Ruta**: `DELETE /recursos-humanos/reloj/clear`

`Asistencia::truncate()` — borra **absolutamente todos** los registros. Sin confirmación server-side adicional. Solo debe ejecutarse para resetear el entorno (staging/desarrollo).

### `clearRango(Request $request): RedirectResponse`

**Ruta**: `DELETE /recursos-humanos/reloj/clear-rango`

Elimina todos los registros en un rango de fechas:
```php
Asistencia::whereBetween('fecha', [$inicio, $fin])->delete();
```

### `revertir($id): RedirectResponse`

**Ruta**: `DELETE /recursos-humanos/reloj/revertir/{id}`

Revierte un registro individual a su estado calculado original (como si viniera del biométrico). Si tiene `entrada` o `salida`, recalcula `es_retardo` y limpia `tipo_registro`. Si no tiene datos de tiempo, lo elimina.

### `revertirRango(Request $request): RedirectResponse`

**Ruta**: `DELETE /recursos-humanos/reloj/revertir-rango`

Igual que `revertir()` pero para todos los registros de un empleado en un rango. Maneja dos formatos de hora: `H:i:s` y `H:i` (con fallback via try/catch).

---

## 9. Avisos de Asistencia (Actas Administrativas)

**Ruta**: `POST /recursos-humanos/reloj/aviso`  
**Método**: `enviarAviso(Request $request)`

Genera un aviso formal de asistencia para un empleado:

```
1. Validar datos del aviso (empleado, tipo, motivo, fecha_incidencia)
2. Crear registro en AvisoAsistencia
3. Obtener correo del empleado
4. Mail::to($empleado->correo)->send(new AvisoAsistenciaMailable($aviso))
5. Crear notificación en el dashboard del empleado
6. Retornar JSON { success: true }
```

Los avisos se muestran en el dashboard de asistencia bajo cada empleado, mostrando la fecha, tipo y quién lo emitió (`enviadoPor` relation).

**`AvisoAsistenciaMailable`**: Archivo `app/Mail/AvisoAsistenciaMailable.php`. Envía un correo formal de llamado de atención al empleado.

---

## 10. Vista de Equipo para Coordinadores

**Ruta**: `GET /recursos-humanos/reloj/equipo`  
**Método**: `equipo(Request $request)`

Versión filtrada del dashboard de asistencia: muestra solo los subordinados directos del coordinador que inicia sesión (empleados con `supervisor_id = $coordinador->id`).

La misma lógica de KPIs aplica, pero restringida al equipo del coordinador.

---

## 11. Referencia de Rutas

**Middleware**: `auth`, `area.rh`  
**Prefijo**: `/recursos-humanos/reloj`  
**Nombre base**: `rh.reloj.`

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `GET` | `/` | `rh.reloj.index` | Dashboard de KPIs + grilla de asistencia |
| `GET` | `/equipo` | `rh.reloj.equipo` | Dashboard reducido para coordinador |
| `POST` | `/start` | `rh.reloj.start` | Iniciar importación Excel del biométrico |
| `GET` | `/progreso/{key}` | `rh.reloj.import.progress` | JSON: progreso de importación |
| `POST` | `/store` | `rh.reloj.store` | Justificaciones masivas |
| `POST` | `/store-manual` | `rh.reloj.storeManual` | Registro manual de entrada/salida |
| `PUT` | `/update/{id}` | `rh.reloj.update` | Editar registro individual |
| `DELETE` | `/revertir/{id}` | `rh.reloj.revertir` | Revertir registro individual |
| `DELETE` | `/revertir-rango` | `rh.reloj.revertirRango` | Revertir rango de un empleado |
| `DELETE` | `/clear` | `rh.reloj.clear` | Truncar toda la BD de asistencias |
| `DELETE` | `/clear-rango` | `rh.reloj.clearRango` | Eliminar registros por rango |
| `POST` | `/aviso` | `rh.reloj.aviso` | Crear aviso + enviar correo |

---

## 12. Historial de Migraciones

| Fecha | Archivo | Cambio |
|---|---|---|
| 2025-12-09 | `create_rh_tables.php` | Tabla `asistencias` inicial |
| 2026-03-10 | `add_incompleto_to_asistencias_tipo_registro.php` | Añade `incompleto` al enum `tipo_registro` (para checadas con entrada pero sin salida) |
| 2026-03-11 | `create_aviso_asistencias_table.php` | Tabla `aviso_asistencias` — actas administrativas |

---

## 13. Guía de Mantenimiento del Módulo

---

### 🔴 CRÍTICO: `clear()` hace TRUNCATE sin confirmación adicional

`Asistencia::truncate()` borra **todos** los registros de asistencia de todos los empleados de todos los tiempos. Es irreversible.

**Recomendación**: Añadir middleware `admin` a esta ruta y solicitar confirmación con un token CSRF adicional.

---

### 🔴 CRÍTICO: La hora de retardo (08:40) está hardcoded en dos lugares

Si la empresa cambia su horario de entrada, debe actualizarse en:
1. `RelojChecadorImportController::storeManual()` (línea con `'08:40'`)
2. `ProcesarAsistenciaService::process()` (lógica de detección de retardo)

**Solución**: Moverlo a `config/rh.php`:
```php
// config/rh.php
'hora_limite_retardo' => env('RH_HORA_LIMITE_RETARDO', '08:40'),
```

---

### 🔴 CRÍTICO: Importación síncrona bloquea el request HTTP

Aunque el frontend hace polling para el progreso, la importación se ejecuta sincrónicamente en el mismo proceso del servidor. Con archivos de Excel muy grandes (miles de registros), el servidor puede alcanzar el timeout de 300 segundos.

**Solución recomendada**: Mover la importación a un Job de Laravel en cola:
```php
ImportarRelojChecadorJob::dispatch($path, $progressKey, $filtroEmpleados)
    ->onQueue('importaciones');
```

---

### 🟡 IMPORTANTE: `store()` con `empleado_id = 'all'` no usa paginación

Al justificar vacaciones para "todos" los empleados en un rango largo, puede cargar cientos de empleados y miles de días en memoria.

**Mitigación**: Usar `chunk()`:
```php
Empleado::chunk(50, function($grupo) use ($inicio, $fin, $request) { ... });
```

---

### 🟡 IMPORTANTE: Los datos de asistencia son desnormalizados

`Asistencia.nombre` y `Asistencia.empleado_no` son strings copiados del empleado. Si un empleado cambia de nombre, sus registros históricos siguen con el nombre anterior.

**No es un bug**: Es intencional para preservar el dato histórico correcto al momento del registro.

---

### 🟢 SEGURO: Añadir un nuevo tipo de registro (ej: `permiso_sin_goce`)

1. En la migración: ampliar el enum `tipo_registro`
2. En `store()`: añadir al select del formulario
3. En el KPI del dashboard: definir cómo cuenta este tipo (¿falta? ¿justificado?)

---

### 🟢 SEGURO: Cambiar el TTL del progreso de importación

El Cache expira en 10 minutos:
```php
Cache::put($key, [...], now()->addMinutes(10));
```
Para importaciones largas, aumentar a 30 minutos. Sin impacto en el resto del sistema.

---

### Checklist de deploy para cambios en Reloj Checador

- [ ] ¿Se añade un tipo de registro nuevo? Migración de enum + actualizar validación en `store()` y `update()` + actualizar grilla en la vista.
- [ ] ¿Se cambia la hora de límite de retardo? Actualizar en `storeManual()` y en `ProcesarAsistenciaService`.
- [ ] ¿Se despliega en servidor con SQLite? Verificar que `Asistencia::truncate()` es compatible (SQLite no permite TRUNCATE directamente).
- [ ] ¿Se añade campo a `AvisoAsistencia`? Migración + `$fillable` + `AvisoAsistenciaMailable` + validación en `enviarAviso()`.
- [ ] ¿Se cambia el proveedor de correo? Actualizar `.env` (MAIL_MAILER, credenciales) y probar con `enviarAviso()`.

---

*Documentación generada el 27 de Abril de 2026 — ERP Estrategia e Innovación*
