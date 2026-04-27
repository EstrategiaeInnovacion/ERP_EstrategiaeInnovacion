# Módulo RH — Recordatorios y Días Festivos — Documentación Maestra
> **ERP Estrategia e Innovación** · Versión documental: Abril 2026  
> Audiencia: Recursos Humanos, desarrolladores

---

## Tabla de Contenido

1. [Visión General](#1-visión-general)
2. [Arquitectura del Módulo](#2-arquitectura-del-módulo)
3. [Modelos de Datos](#3-modelos-de-datos)
4. [Referencia de Métodos — `RecordatorioController`](#4-referencia-de-métodos--recordatoriocontroller)
5. [Tipos de Recordatorio (TIPOS Constant)](#5-tipos-de-recordatorio-tipos-constant)
6. [Generación Automática: Artisan Command](#6-generación-automática-artisan-command)
7. [Integración con FullCalendar](#7-integración-con-fullcalendar)
8. [Referencia de Métodos — `DiaFestivoController`](#8-referencia-de-métodos--diafestivocontroller)
9. [Integración entre Días Festivos y Recordatorios](#9-integración-entre-días-festivos-y-recordatorios)
10. [Referencia de Rutas](#10-referencia-de-rutas)
11. [Historial de Migraciones](#11-historial-de-migraciones)
12. [Guía de Mantenimiento del Módulo](#12-guía-de-mantenimiento-del-módulo)

---

## 1. Visión General

Este documento cubre dos sub-módulos estrechamente relacionados:

- **Recordatorios**: Agenda inteligente que centraliza alertas de RH (cumpleaños, aniversarios laborales, vencimientos de documentos, evaluaciones pendientes). Puede generar recordatorios automáticamente o manualmente.
- **Días Festivos**: Calendarios de días festivos y días inhábiles de la empresa, con notificaciones automáticas al personal y vinculación con el módulo de Recordatorios.

### Propósito de negocio conjunto

| Necesidad | Solución |
|---|---|
| Alertar sobre cumpleaños y aniversarios | Artisan `rh:generar-recordatorios` — generación automática |
| Notificar documentos próximos a vencer | `documento_por_vencer` + `documento_vencido` auto-generados |
| Vista de calendario para RH | FullCalendar via `calendario()` endpoint |
| Marcar recordatorios como leídos | `marcarLeido()` individual + `marcarTodosLeidos()` bulk |
| Publicar días festivos de la empresa | `DiaFestivo` con `tipo=festivo|inhabil` |
| Avisar a todos cuando hay día festivo | `enviarNotificacion()` — con flag anti-duplicado |
| Recordatorio automático al crear día festivo | `$diaFestivo->crearRecordatorio()` — vínculo directo |

---

## 2. Arquitectura del Módulo

```
┌─────────────────────────────────────────────────────────────────────┐
│          RECORDATORIOS + DÍAS FESTIVOS                              │
│                                                                     │
│  Middleware: auth, area.rh                                          │
│  Prefijo URL A: /recursos-humanos/recordatorios                     │
│  Prefijo URL B: /recursos-humanos/dias-festivos                     │
│                                                                     │
│  RecordatorioController                                             │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │  index()             → Lista con filtros y KPIs               │  │
│  │  show($id)           → Detalle + auto-marca leído             │  │
│  │  marcarLeido($id)    → Marcar leído (AJAX o redirect)         │  │
│  │  marcarTodosLeidos() → Bulk update                            │  │
│  │  generarManual()     → Ejecuta Artisan command                │  │
│  │  calendario()        → JSON para FullCalendar                  │  │
│  │  crearEventoManual() → Crear recordatorio de tipo manual      │  │
│  │  destruir($id)       → Eliminar recordatorio                  │  │
│  └───────────────────────────────────────────────────────────────┘  │
│                                                                     │
│  DiaFestivoController                                               │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │  index()               → Listado + KPIs + próximos            │  │
│  │  create() / store()    → Crear día festivo                    │  │
│  │  edit() / update()     → Editar día festivo                   │  │
│  │  destroy($id)          → Soft delete (activo=false)           │  │
│  │  toggle($id)           → Activar/desactivar                   │  │
│  │  enviarNotificacion()  → Email masivo al personal             │  │
│  │  crearRecordatorio()   → Vincula al módulo de recordatorios   │  │
│  └───────────────────────────────────────────────────────────────┘  │
│                                                                     │
│  Dependencias                                                       │
│  ────────────                                                        │
│  Artisan::call('rh:generar-recordatorios') → auto-generación        │
│  NotificarDiaFestivoJob / enviarNotificaciones() → masivo           │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 3. Modelos de Datos

### `Recordatorio` — Tabla `recordatorios`

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | `bigint` PK | |
| `tipo` | `enum` | Uno de los 7 valores de `TIPOS` (ver sección 5) |
| `titulo` | `varchar(255)` | Título legible del recordatorio |
| `descripcion` | `text` nullable | Detalle adicional |
| `fecha_evento` | `date` | Fecha del evento al que refiere |
| `dias_anticipacion` | `int` default 0 | Días antes del evento para generar el recordatorio |
| `tabla_relacionada` | `varchar(100)` nullable | Tabla fuente (ej: `dias_festivos`, `empleados`) |
| `registro_id` | `bigint` nullable | ID del registro en la tabla fuente |
| `empleado_id` | `bigint` FK nullable | FK → `empleados.id` |
| `creado_por` | `bigint` FK nullable | FK → `users.id` |
| `leido` | `boolean` default false | Si fue visto |
| `leido_at` | `timestamp` nullable | Cuándo fue visto |
| `activo` | `boolean` default true | Si aparece en la agenda |
| `color_evento` | `varchar(50)` nullable | Color para FullCalendar (hex o nombre CSS) |
| `es_manual` | `boolean` default false | Distingue auto-generados de manuales |
| `created_at` / `updated_at` | `timestamp` | |

**Métodos del modelo**:
- `marcarLeido()`: `$this->update(['leido' => true, 'leido_at' => now()])`

### `DiaFestivo` — Tabla `dias_festivos`

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | `bigint` PK | |
| `nombre` | `varchar(255)` | Nombre del día festivo (ej: "Día del Trabajo") |
| `fecha` | `date` | Fecha del día festivo |
| `tipo` | `enum` | `festivo` o `inhabil` |
| `es_anual` | `boolean` | Si se repite cada año automáticamente |
| `descripcion` | `text` nullable | Descripción adicional |
| `activo` | `boolean` default true | Si está activo en el calendario |
| `notificacion_enviada` | `boolean` default false | Previene duplicación de notificaciones |
| `created_at` / `updated_at` | `timestamp` | |

**Métodos del modelo**:
- `crearRecordatorio(User $user)`: Crea un `Recordatorio` vinculado al día festivo si no existe uno previo.
- `enviarNotificaciones()`: Envía email/notificación a todos los empleados activos. Retorna `count` de envíos.

---

## 4. Referencia de Métodos — `RecordatorioController`

**Archivo**: `app/Http/Controllers/RH/RecordatorioController.php`

---

### `index(Request $request): View`

**Ruta**: `GET /recursos-humanos/recordatorios`

**Ventana de tiempo**: Solo muestra recordatorios en el rango `today - 7 días` hasta `today + 30 días`. Esto previene que recordatorios muy antiguos sigan apareciendo.

**Filtros opcionales**:

| Parámetro GET | Comportamiento |
|---|---|
| `tipo` | Filtra por tipo (uno de los 7 en `TIPOS`) |
| `estado=vencidos` | `fecha_evento < today AND leido = false` |
| `estado=no_leidos` | `leido = false` |
| `estado=urgentes` | `fecha_evento BETWEEN today AND today + 7 días` |

**KPIs que retorna la vista**:

| KPI | Descripción |
|---|---|
| `total` | Total de recordatorios en la ventana |
| `no_leidos` | `leido = false` |
| `urgentes` | Próximos ≤7 días |
| `vencidos` | `fecha_evento < today` |
| `porTipo` | Conteo agrupado por cada tipo del enum |

---

### `show($id): View`

**Ruta**: `GET /recursos-humanos/recordatorios/{id}`

Al cargar el detalle, auto-marca el recordatorio como leído:
```php
$recordatorio = Recordatorio::findOrFail($id);
$recordatorio->marcarLeido(); // leido = true, leido_at = now()
return view('...', compact('recordatorio'));
```

---

### `marcarLeido($id): JsonResponse|RedirectResponse`

**Ruta**: `POST /recursos-humanos/recordatorios/{id}/marcar-leido`

Soporta requests AJAX (retorna `{ success: true }`) y requests normales (redirect `back()`).

---

### `marcarTodosLeidos(): RedirectResponse`

**Ruta**: `POST /recursos-humanos/recordatorios/marcar-todos`

Bulk update:
```php
Recordatorio::where('leido', false)->update(['leido' => true, 'leido_at' => now()]);
```

---

### `generarManual(): RedirectResponse`

**Ruta**: `POST /recursos-humanos/recordatorios/generar`

Dispara el Artisan command:
```php
Artisan::call('rh:generar-recordatorios');
```

Útil después de añadir empleados o actualizar fechas de contratos, para que los nuevos recordatorios aparezcan sin esperar al schedule.

---

### `calendario(): JsonResponse`

**Ruta**: `GET /recursos-humanos/recordatorios/calendario`

Retorna un array JSON compatible con FullCalendar v5+, en el rango `-1 mes → +2 meses`:
```json
[
  {
    "id": 42,
    "title": "Cumpleaños - Ana García",
    "start": "2026-05-10",
    "color": "#4CAF50",
    "tipo": "cumpleaños",
    "url": "/recursos-humanos/recordatorios/42"
  }
]
```

Cada evento incluye el campo `color_evento` del recordatorio para diferenciación visual.

---

### `crearEventoManual(Request $request): RedirectResponse`

**Ruta**: `POST /recursos-humanos/recordatorios/evento-manual`

Crea un `Recordatorio` con `es_manual = true` y `creado_por = Auth::id()`. Los campos editables son: `titulo`, `descripcion`, `fecha_evento`, `tipo` (debe ser `evento_personal`), `color_evento`, `empleado_id` (opcional).

---

### `destruir($id): RedirectResponse`

**Ruta**: `DELETE /recursos-humanos/recordatorios/{id}`

`Recordatorio::findOrFail($id)->delete()`. Elimina permanentemente (no hay soft-delete en esta tabla).

---

## 5. Tipos de Recordatorio (TIPOS Constant)

Definida en `App\Models\Recordatorio::TIPOS`:

| Clave (key) | Etiqueta visible | Origen |
|---|---|---|
| `cumpleaños` | Cumpleaños | Auto (Artisan) — de `empleados.fecha_nacimiento` |
| `aniversario_laboral` | Aniversario Laboral | Auto (Artisan) — de `empleados.fecha_ingreso` |
| `documento_por_vencer` | Documento por Vencer | Auto (Artisan) — de `empleado_documentos.fecha_vencimiento` |
| `documento_vencido` | Documento Vencido | Auto (Artisan) — cuando `fecha_vencimiento < today` |
| `contrato_por_vencer` | Fin de Contrato | Auto (Artisan) — de campo de fin de contrato en empleado |
| `evaluacion_pendiente` | Evaluación Pendiente | Auto — desde módulo de Evaluaciones |
| `evento_personal` | Evento Personal | Manual — creado por RH |

> **Nota**: Al crear un Día Festivo, se genera automáticamente un recordatorio de tipo `evento_personal` o similar (vinculado via `tabla_relacionada = 'dias_festivos'`, `registro_id = $diaFestivo->id`).

---

## 6. Generación Automática: Artisan Command

**Comando**: `php artisan rh:generar-recordatorios`  
**Archivo**: `app/Console/Commands/` (comando específico de RH)

El comando automatiza:

1. **Cumpleaños**: Para cada empleado activo cuyo `fecha_nacimiento` coincide con el mes actual (con X días de anticipación), crea un `Recordatorio` de tipo `cumpleaños`.

2. **Aniversarios laborales**: Empleados que cumplen N años en la empresa este mes.

3. **Documentos por vencer**: Revisa `EmpleadoDocumento.fecha_vencimiento` y crea recordatorio si vence en los próximos 30 días.

4. **Documentos vencidos**: Si `fecha_vencimiento < today` y no existe recordatorio de vencido, crea uno.

5. **Contratos por vencer**: Si el empleado tiene campo de fin de contrato próximo.

**Idempotencia**: Antes de crear, verifica si ya existe un recordatorio del mismo tipo para el mismo empleado en la misma fecha (`tabla_relacionada` + `registro_id` o `empleado_id` + `tipo` + `fecha_evento`). Evita duplicados.

**Schedule recomendado** (en `app/Console/Kernel.php`):
```php
$schedule->command('rh:generar-recordatorios')->dailyAt('06:00');
```

---

## 7. Integración con FullCalendar

La vista de calendario (`/recordatorios/calendario`) usa FullCalendar v5 o superior.

**Configuración del frontend**:
```javascript
const calendar = new FullCalendar.Calendar(el, {
    initialView: 'dayGridMonth',
    locale: 'es',
    events: '/recursos-humanos/recordatorios/calendario',
    eventColor: '#378006', // fallback si color_evento es null
    eventClick: function(info) {
        window.location.href = info.event.url; // navega al detalle
    }
});
```

La respuesta del endpoint ya incluye `color` por evento, permitiendo diferenciación visual automática de tipos (verde para cumpleaños, amarillo para aniversarios, rojo para vencidos, etc.).

---

## 8. Referencia de Métodos — `DiaFestivoController`

**Archivo**: `app/Http/Controllers/RH/DiaFestivoController.php`

---

### `index(): View`

**Ruta**: `GET /recursos-humanos/dias-festivos`

KPIs mostrados:

| KPI | Descripción |
|---|---|
| `total` | Total de días festivos (activos + inactivos) |
| `activos` | `activo = true` |
| `festivos` | `tipo = 'festivo'` |
| `inhabiles` | `tipo = 'inhabil'` |
| `proximos` | Próximos 30 días (scopes `activos()` + `proximos(30)`) |

---

### `store(Request $request): RedirectResponse`

**Ruta**: `POST /recursos-humanos/dias-festivos`

Flujo:
1. Validar: `nombre`, `fecha`, `tipo` (required), `es_anual` (boolean), `descripcion` (optional).
2. Crear el `DiaFestivo`.
3. **Auto-crear recordatorio**: `$diaFestivo->crearRecordatorio(auth()->user())`

La creación del recordatorio es automática e inmediata. No requiere acción adicional del usuario.

---

### `destroy($id): RedirectResponse`

**Ruta**: `DELETE /recursos-humanos/dias-festivos/{id}`

**Soft delete** — NO elimina el registro:
```php
$diaFestivo->update(['activo' => false]);
```

Para eliminar físicamente, se debería llamar `$diaFestivo->delete()` directamente. La ruta actual solo desactiva.

---

### `toggle($id): RedirectResponse`

**Ruta**: `PATCH /recursos-humanos/dias-festivos/{id}/toggle`

Invierte el campo `activo`:
```php
$diaFestivo->update(['activo' => !$diaFestivo->activo]);
```

---

### `enviarNotificacion($id): RedirectResponse`

**Ruta**: `POST /recursos-humanos/dias-festivos/{id}/enviar-notificacion`

1. Verifica que `notificacion_enviada = false`. Si ya se envió, retorna error/warning.
2. Llama a `$diaFestivo->enviarNotificaciones()`.
3. Setea `notificacion_enviada = true` para prevenir reenvíos accidentales.
4. Retorna `back()->with('success', "Notificación enviada a {$count} empleados")`.

---

### `crearRecordatorio($id): RedirectResponse`

**Ruta**: `POST /recursos-humanos/dias-festivos/{id}/crear-recordatorio`

Endpoint manual para el caso en que el recordatorio automático no se creó o fue eliminado. Llama al mismo método del modelo:
```php
$diaFestivo = DiaFestivo::findOrFail($id);
$diaFestivo->crearRecordatorio(auth()->user());
```

El método del modelo verifica si ya existe antes de crear:
```php
// En el modelo DiaFestivo:
public function crearRecordatorio(User $user): void
{
    $existe = Recordatorio::where('tabla_relacionada', 'dias_festivos')
                          ->where('registro_id', $this->id)
                          ->exists();
    if ($existe) return;
    
    Recordatorio::create([...]);
}
```

---

## 9. Integración entre Días Festivos y Recordatorios

```
DiaFestivo::store()
    │
    └─► DiaFestivo::crearRecordatorio($user)
            │
            ├─ Verifica si ya existe Recordatorio con:
            │     tabla_relacionada = 'dias_festivos'
            │     registro_id       = $diaFestivo->id
            │
            └─ Si NO existe → Recordatorio::create([
                  tipo              => 'evento_personal',
                  titulo            => "Día Festivo: {$diaFestivo->nombre}",
                  fecha_evento      => $diaFestivo->fecha,
                  tabla_relacionada => 'dias_festivos',
                  registro_id       => $diaFestivo->id,
                  creado_por        => $user->id,
                  es_manual         => false,
              ])
```

Esta integración permite que los días festivos aparezcan automáticamente en el calendario de recordatorios de RH, sin configuración adicional.

---

## 10. Referencia de Rutas

### Recordatorios

**Middleware**: `auth`, `area.rh`  
**Prefijo**: `/recursos-humanos/recordatorios`  
**Nombre base**: `rh.recordatorios.`

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `GET` | `/` | `rh.recordatorios.index` | Lista con filtros y KPIs |
| `GET` | `/calendario` | `rh.recordatorios.calendario` | JSON para FullCalendar |
| `GET` | `/{id}` | `rh.recordatorios.show` | Detalle (auto-marca leído) |
| `POST` | `/{id}/marcar-leido` | `rh.recordatorios.marcar-leido` | Marcar leído (AJAX) |
| `POST` | `/marcar-todos` | `rh.recordatorios.marcar-todos` | Bulk: marcar todos leídos |
| `POST` | `/generar` | `rh.recordatorios.generar` | Ejecutar Artisan command |
| `POST` | `/evento-manual` | `rh.recordatorios.crear-manual` | Crear evento manual |
| `DELETE` | `/{id}` | `rh.recordatorios.destruir` | Eliminar recordatorio |

### Días Festivos

**Middleware**: `auth`, `area.rh`  
**Prefijo**: `/recursos-humanos/dias-festivos`  
**Nombre base**: `rh.dias-festivos.`

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `GET` | `/` | `rh.dias-festivos.index` | Listado + KPIs |
| `GET` | `/crear` | `rh.dias-festivos.create` | Formulario de creación |
| `POST` | `/` | `rh.dias-festivos.store` | Guardar nuevo día festivo |
| `GET` | `/{id}/editar` | `rh.dias-festivos.edit` | Formulario de edición |
| `PUT` | `/{id}` | `rh.dias-festivos.update` | Guardar cambios |
| `DELETE` | `/{id}` | `rh.dias-festivos.destroy` | Desactivar (soft delete) |
| `PATCH` | `/{id}/toggle` | `rh.dias-festivos.toggle` | Activar/desactivar |
| `POST` | `/{id}/enviar-notificacion` | `rh.dias-festivos.enviar-notificacion` | Enviar notificación masiva |
| `POST` | `/{id}/crear-recordatorio` | `rh.dias-festivos.crear-recordatorio` | Crear recordatorio manual |

---

## 11. Historial de Migraciones

| Fecha | Archivo | Cambio |
|---|---|---|
| 2026-04-13 | `create_dias_festivos_table.php` | Tabla `dias_festivos` |
| 2026-04-14 | `create_recordatorios_table.php` | Tabla `recordatorios` con todos sus campos |

---

## 12. Guía de Mantenimiento del Módulo

---

### 🔴 CRÍTICO: `marcarTodosLeidos()` actualiza TODOS los recordatorios del sistema

```php
Recordatorio::where('leido', false)->update(['leido' => true, 'leido_at' => now()]);
```

No está filtrado por usuario ni por empleado. Marca como leídos los recordatorios de todos los empleados.

**Impacto**: Un usuario de RH que hace "marcar todos" limpia las alertas no leídas de todos los demás en el sistema.

**Fix**: Añadir filtro por el usuario actual:
```php
Recordatorio::where('leido', false)
    ->where('creado_por', Auth::id())  // o por empleado_id del usuario
    ->update(['leido' => true, 'leido_at' => now()]);
```

---

### 🔴 CRÍTICO: `destruir()` elimina el recordatorio permanentemente

No hay soft-delete en `Recordatorio`. Si se elimina un recordatorio de cumpleaños que era generado automáticamente, el Artisan command lo volverá a crear al siguiente `daily` run.

**Si se desea silenciar**: Cambiar `activo = false` en lugar de `delete()`.

---

### 🟡 IMPORTANTE: `enviarNotificacion()` no puede revertirse

Una vez que `notificacion_enviada = true`, no hay botón de "reenviar" (aunque el flag puede cambiarse manualmente en BD). El correo masivo ya salió.

**Verificación antes de enviar**: La vista debería mostrar una confirmación clara con el número de empleados que recibirán el correo.

---

### 🟡 IMPORTANTE: `destroy()` en DiaFestivo no elimina el Recordatorio vinculado

Al desactivar un día festivo, el `Recordatorio` vinculado (`tabla_relacionada='dias_festivos'`, `registro_id=X`) permanece activo en la agenda.

**Fix recomendado**: En `destroy()`, desactivar también el recordatorio:
```php
$diaFestivo->update(['activo' => false]);
Recordatorio::where('tabla_relacionada', 'dias_festivos')
            ->where('registro_id', $diaFestivo->id)
            ->update(['activo' => false]);
```

---

### 🟡 IMPORTANTE: La ventana `today-7 → today+30` puede perder recordatorios

Si un recordatorio de "documento vencido" lleva más de 7 días sin leerse, desaparece del `index()`.

**Impacto**: El área de RH podría creer que no hay documentos vencidos cuando en realidad están fuera de la ventana.

**Alternativa**: Mostrar vencidos siempre que `leido = false`, independientemente de la fecha.

---

### 🟢 SEGURO: Añadir un nuevo tipo de recordatorio

1. En `Recordatorio::TIPOS`, añadir el nuevo par `'clave' => 'Etiqueta'`.
2. Actualizar el Artisan command `rh:generar-recordatorios` para generar el nuevo tipo.
3. Actualizar el selector de filtros en la vista `index()`.
4. Añadir un color por defecto en el frontend de FullCalendar.

---

### 🟢 SEGURO: Cambiar la ventana de anticipación de recordatorios

En el Artisan command, la anticipación (ej: 15 días antes del cumpleaños) es configurable por tipo. Puede centralizarse en `config/rh.php`:
```php
'dias_anticipacion' => [
    'cumpleaños'            => 7,
    'aniversario_laboral'   => 3,
    'documento_por_vencer'  => 30,
    'contrato_por_vencer'   => 30,
],
```

---

### Checklist de deploy para cambios en Recordatorios/Días Festivos

- [ ] ¿Se añade un nuevo `TIPO` al enum de Recordatorio? Migración para ampliar el enum + actualizar `TIPOS` en el modelo + actualizar filtros en `index()`.
- [ ] ¿Se activa el Schedule de generación automática? Verificar `app/Console/Kernel.php` incluye `rh:generar-recordatorios` en el scheduler, y que `php artisan schedule:run` esté en cron del servidor.
- [ ] ¿Se cambia el sistema de notificaciones? Actualizar `DiaFestivo::enviarNotificaciones()` + probar `enviarNotificacion()` con un solo empleado antes de envío masivo.
- [ ] ¿Se añade campo a `DiaFestivo`? Migración + `$fillable` + `store()` + `update()`.
- [ ] ¿Se añade campo a `Recordatorio`? Migración + `$fillable` + Artisan command + `crearEventoManual()`.

---

*Documentación generada el 27 de Abril de 2026 — ERP Estrategia e Innovación*
