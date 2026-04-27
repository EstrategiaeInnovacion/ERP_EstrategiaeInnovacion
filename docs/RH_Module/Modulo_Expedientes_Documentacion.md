# Módulo RH — Expedientes de Empleados — Documentación Maestra
> **ERP Estrategia e Innovación** · Versión documental: Abril 2026  
> Audiencia: Área de Recursos Humanos, desarrolladores

---

## Tabla de Contenido

1. [Visión General](#1-visión-general)
2. [Arquitectura del Módulo](#2-arquitectura-del-módulo)
3. [Modelo de Datos](#3-modelo-de-datos)
4. [Checklist de Documentos por Tipo de Empleado](#4-checklist-de-documentos-por-tipo-de-empleado)
5. [Referencia de Métodos — `ExpedienteController`](#5-referencia-de-métodos--expedientecontroller)
6. [Importación Inteligente del Formato ID (Excel)](#6-importación-inteligente-del-formato-id-excel)
7. [Ciclo de Vida del Empleado: Alta, Baja y Reactivación](#7-ciclo-de-vida-del-empleado-alta-baja-y-reactivación)
8. [Almacenamiento de Documentos](#8-almacenamiento-de-documentos)
9. [Referencia de Rutas](#9-referencia-de-rutas)
10. [Historial de Migraciones](#10-historial-de-migraciones)
11. [Guía de Mantenimiento del Módulo](#11-guía-de-mantenimiento-del-módulo)

---

## 1. Visión General

El módulo de **Expedientes** es el registro maestro de empleados del ERP. Gestiona toda la información personal, laboral y fiscal de cada colaborador, con soporte para documentos digitales (INE, CURP, contratos, etc.) y una función especial de extracción automática de datos desde el "Formato ID" (un Excel estándar de onboarding).

### Propósito de negocio

| Necesidad | Solución |
|---|---|
| Centralizar información de cada empleado | Modelo `Empleado` con 30+ campos, vínculo a `User` del sistema |
| Controlar documentos de cumplimiento (INE, NSS, CURP...) | `EmpleadoDocumento` con categorías y vencimientos |
| Poblar datos desde el Excel de onboarding | `importFormatoId()` — PhpSpreadsheet con mapeo inteligente |
| Registrar y rastrear bajas laborales | `darDeBaja()` + `EmpleadoBaja` para reporte de rotación |
| Reactivar empleados que regresan | `reactivar()` — restaura acceso y elimina registro de baja |
| Filtrar empleados activos vs dados de baja | `?status=activos|bajas|todos` en listado |

---

## 2. Arquitectura del Módulo

```
┌─────────────────────────────────────────────────────────────────────┐
│                    EXPEDIENTES DE EMPLEADOS                         │
│                                                                     │
│  Middleware: auth, area.rh                                          │
│  Prefijo URL: /recursos-humanos/expedientes                         │
│  Nombre base: rh.expedientes.                                       │
│                                                                     │
│  ExpedienteController                                               │
│  ┌───────────────────────────────────────────────────────────────┐  │
│  │  index()           → Listado paginado (12/pág) + buscador     │  │
│  │  show($id)         → Detalle + checklist documentos           │  │
│  │  edit($id)         → Formulario de edición                    │  │
│  │  update($id)       → Guarda cambios de perfil                 │  │
│  │  destroy($id)      → Eliminación (si no tiene dependencias)   │  │
│  │  uploadDocument()  → Sube archivo a storage/local             │  │
│  │  deleteDocument()  → Elimina archivo de disco y BD            │  │
│  │  downloadDocument()→ Descarga/visualiza desde storage privado │  │
│  │  importFormatoId() → Sube Excel + extrae datos automáticamente│  │
│  │  darDeBaja()       → Baja laboral + desactiva User            │  │
│  │  reactivar()       → Reactiva + restaura User                 │  │
│  │  refresh()         → Regenera datos calculados                │  │
│  └───────────────────────────────────────────────────────────────┘  │
│                                                                     │
│  Dependencias                                                       │
│  ────────────                                                        │
│  PhpOffice\PhpSpreadsheet  → importFormatoId()                      │
│  Illuminate\Support\Str    → Mapeo inteligente de etiquetas         │
│  Storage::disk('local')    → Almacén privado de documentos          │
└─────────────────────────────────────────────────────────────────────┘
```

---

## 3. Modelo de Datos

### `Empleado` — Tabla `empleados`

Modelo principal. Columnas agrupadas por propósito:

**Identidad**:
| Campo | Descripción |
|---|---|
| `id` | PK |
| `id_empleado` | Número de nómina |
| `nombre` | Primer nombre |
| `apellido_paterno` | Apellido paterno |
| `apellido_materno` | Apellido materno |
| `correo` | Correo corporativo (usado para vincular con `User`) |
| `fecha_nacimiento` | Para recordatorios de cumpleaños |
| `foto` | Ruta a foto de perfil |

**Datos laborales**:
| Campo | Descripción |
|---|---|
| `posicion` | Puesto/cargo |
| `area` | Área departamental |
| `fecha_ingreso` | Fecha de alta en nómina |
| `supervisor_id` | FK → `empleados.id` (auto-referencial) |
| `es_activo` | Boolean — activo o dado de baja |
| `es_coordinador` | Boolean — jefe de equipo |
| `es_practicante` | Boolean — cambia la checklist de documentos requeridos |

**Datos personales (extraídos del Formato ID)**:
| Campo | Descripción |
|---|---|
| `direccion` | Calle y número |
| `ciudad` | Ciudad de residencia |
| `estado_federativo` | Estado de la república |
| `codigo_postal` | CP |
| `telefono` | Celular/WhatsApp |
| `telefono_casa` | Teléfono fijo |
| `alergias` | Alergias conocidas |
| `enfermedades_cronicas` | Condiciones médicas |
| `contacto_emergencia_nombre` | Nombre del contacto de emergencia |
| `contacto_emergencia_numero` | Teléfono del contacto |
| `contacto_emergencia_parentesco` | Parentesco |

**Datos fiscales**:
| Campo | Descripción |
|---|---|
| `rfc` | RFC del empleado |
| `curp` | CURP |
| `nss` | Número de Seguridad Social |
| `banco` | Banco para depósito de nómina |
| `numero_cuenta` | Cuenta bancaria |
| `clabe` | CLABE interbancaria |

**Vínculo con sistema**:
| Campo | Descripción |
|---|---|
| `user_id` | FK nullable → `users.id` |

### `EmpleadoDocumento` — Tabla `empleado_documentos`

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | `bigint` PK | |
| `empleado_id` | `bigint` FK | FK → `empleados.id` |
| `nombre` | `varchar(255)` | Nombre del documento (INE, CURP, Contrato...) |
| `categoria` | `varchar(255)` | Agrupador en la vista de expediente |
| `ruta_archivo` | `varchar(500)` | Ruta relativa en `storage/app/` (disco `local`) |
| `fecha_vencimiento` | `date` nullable | Para documentos con vigencia |
| `created_at` / `updated_at` | `timestamp` | |

### `EmpleadoBaja` — Tabla `empleados_baja`

Registro histórico de bajas para análisis de rotación.

| Campo | Descripción |
|---|---|
| `id` | PK |
| `empleado_id` | FK → `empleados.id` |
| `user_id` | FK → `users.id` |
| `nombre` | Nombre capturado al momento de la baja |
| `correo` | Correo al momento de la baja |
| `motivo_baja` | Motivo (renuncia, término de contrato, despido...) |
| `fecha_baja` | Fecha efectiva de baja |
| `observaciones` | Notas adicionales |

---

## 4. Checklist de Documentos por Tipo de Empleado

La checklist se obtiene del método estático `Empleado::getRequisitos($esPracticante)`:

**Empleado regular**:
- INE, CURP, Comprobante de Domicilio, NSS, Título, Constancia de Situación Fiscal, Formato ID, Contrato

**Practicante** (sin prestaciones de ley completas):
- INE, CURP, Comprobante de Domicilio, Estado de Cuenta, Formato ID, Contrato

El porcentaje de completitud del expediente se calcula en `index()` dividiendo los documentos entregados entre los requeridos. Este cálculo usa `Empleado::with('documentos')` (eager loading) para evitar N+1.

---

## 5. Referencia de Métodos — `ExpedienteController`

**Archivo**: `app/Http/Controllers/RH/ExpedienteController.php`

---

### `index(Request $request): View`

**Ruta**: `GET /recursos-humanos/expedientes`

Listado con buscador y filtro de estado:

| Parámetro GET | Comportamiento |
|---|---|
| `search` | LIKE en `nombre`, `id_empleado`, `posicion` |
| `status` | `activos` / `bajas` / `todos` (default) |

Usa `->with('documentos')` + `->paginate(12)` para mostrar barra de progreso del expediente.

---

### `show($id): View`

**Ruta**: `GET /recursos-humanos/expedientes/{id}`

Retorna el detalle del expediente con:
- Documentos agrupados por `categoria` (`groupBy`)
- Checklist de documentos requeridos (vs entregados)
- Datos del perfil completo

---

### `update(Request $request, $id): RedirectResponse`

**Ruta**: `PUT /recursos-humanos/expedientes/{id}`

**Caso especial — Toggle practicante**:
```php
if ($request->has('toggle_practicante')) {
    $empleado->es_practicante = $request->boolean('es_practicante');
    $empleado->save();
    return back()->with('success', ...);
}
```

**Caso estándar**: Actualiza todos los campos excepto `user_id` (para no romper el vínculo):
```php
$empleado->update($request->except(['user_id', '_token', '_method']));
```

Si `id_empleado` viene vacío, mantiene el valor actual (previene accidental blanqueado).

---

### `uploadDocument(Request $request, $empleadoId): RedirectResponse`

**Ruta**: `POST /recursos-humanos/expedientes/{id}/upload`

Validación de tipos: `pdf,jpg,png,jpeg,xlsx,xls,csv,doc,docx`. Máximo 10 MB.

Soporte para nombre "Otro": el usuario escribe el nombre manualmente en un campo secundario.

Almacena en `storage/app/expedientes/{empleado_id}/{slug_nombre}_{timestamp}.ext` en el disco `local` (privado, no accesible por URL pública).

---

### `downloadDocument($id): Response`

**Ruta**: `GET /recursos-humanos/expedientes/documento/{id}/descargar`

Sirve el archivo desde el disco privado via `Storage::disk('local')->response()`. Si el archivo no existe, retorna 404.

---

### `deleteDocument($id): RedirectResponse`

**Ruta**: `DELETE /recursos-humanos/expedientes/documento/{id}`

Elimina el archivo físico del disco y el registro de `EmpleadoDocumento`.

---

### `darDeBaja(Request $request, $id): RedirectResponse`

**Ruta**: `POST /recursos-humanos/expedientes/{id}/baja`

Flujo completo:
1. Validar `motivo_baja` (requerido) y `observaciones` (opcional)
2. Verificar que el empleado no esté ya dado de baja
3. Crear registro en `EmpleadoBaja` con datos del momento
4. `$empleado->update(['es_activo' => false])`
5. Si tiene `user_id` → `User::update(['status' => STATUS_REJECTED, 'rejected_at' => now()])`

---

### `reactivar($id): RedirectResponse`

**Ruta**: `POST /recursos-humanos/expedientes/{id}/reactivar`

Flujo inverso a `darDeBaja()`:
1. Verificar que no esté activo ya
2. `$empleado->update(['es_activo' => true])`
3. Si tiene `user_id` → `User::update(['status' => STATUS_APPROVED, 'approved_at' => now(), 'rejected_at' => null])`
4. `EmpleadoBaja::where('empleado_id', $id)->delete()` — elimina el historial de baja

---

## 6. Importación Inteligente del Formato ID (Excel)

**Ruta**: `POST /recursos-humanos/expedientes/{id}/import-excel`  
**Método**: `importFormatoId(Request $request, $id)`

Esta es la funcionalidad más compleja del módulo. Combina dos pasos en un solo request:

### Paso A: Guardar el archivo como documento

El Excel se guarda en el expediente del empleado como documento de tipo "Formato ID", sumando a la checklist.

### Paso B: Extraer datos y actualizar el perfil

PhpSpreadsheet lee el Excel y convierte cada fila a un par `[etiqueta, valor]`. Las etiquetas se normalizan con `Str::slug()` y se mapean a campos de `Empleado` mediante `Str::contains()`:

| Slug detectado | Campo actualizado |
|---|---|
| `direccion`, `domicilio`, `calle` | `direccion` |
| `ciudad`, `municipio` | `ciudad` |
| `estado`, `entidad` | `estado_federativo` |
| `postal`, `cp`, `zip` | `codigo_postal` |
| `celular`, `movil`, `whatsapp` | `telefono` |
| `casa`, `fijo`, `hogar` | `telefono_casa` |
| `alergia` | `alergias` |
| `enfermedad`, `cronica`, `padecimiento` | `enfermedades_cronicas` |
| `emergencia` (sin número) | `contacto_emergencia_nombre` |
| `emergencia` + `numero`/`telefono` | `contacto_emergencia_numero` |
| `parentesco`, `relacion` | `contacto_emergencia_parentesco` |

**Valores ignorados**: si el valor contiene "No llenar", "RH" o "Administracion", se omite.

**Comportamiento ante errores**: Si falla la lectura del Excel, el archivo ya está guardado. El usuario recibe un `warning` indicando que el documento se archivó pero no se pudieron leer los datos.

**Configuración necesaria** para archivos grandes:
```php
ini_set('memory_limit', '-1');
ini_set('max_execution_time', 300);
```

---

## 7. Ciclo de Vida del Empleado: Alta, Baja y Reactivación

```
ALTA
  → Expediente creado (generalmente por importación o manual)
  → es_activo = true
  → user_id vinculado al cuenta del sistema
  → status de User = 'approved'

DAR DE BAJA
  → darDeBaja(motivo_baja, observaciones)
  → EmpleadoBaja creado (historial de rotación)
  → es_activo = false
  → status de User = 'rejected', rejected_at = now()
  
REACTIVAR
  → reactivar()
  → es_activo = true
  → status de User = 'approved', approved_at = now(), rejected_at = null
  → EmpleadoBaja eliminado (historial limpiado)
```

> **Nota**: `EmpleadoBaja::delete()` al reactivar implica que si el mismo empleado se da de baja y reactiva múltiples veces, solo queda el historial de la última baja activa. Si se necesita histórico completo, cambiar a soft-delete o no eliminar el registro.

---

## 8. Almacenamiento de Documentos

Los documentos se almacenan en el **disco `local`** (privado, no expuesto públicamente):

```
storage/app/expedientes/{empleado_id}/{slug_nombre}_{timestamp}.{ext}
```

**Para servir el archivo**: `Storage::disk('local')->response($path)` genera la respuesta HTTP correcta.

**Para eliminar**: `Storage::disk('local')->delete($path)`.

> **Importante**: Los documentos NO se almacenan en `public/` ni en `storage/app/public/`. Son archivos privados que solo se sirven a través del controlador con autenticación.

---

## 9. Referencia de Rutas

**Middleware**: `auth`, `area.rh`  
**Prefijo**: `/recursos-humanos/expedientes`  
**Nombre base**: `rh.expedientes.`

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `GET` | `/` | `rh.expedientes.index` | Listado con buscador y filtro de estado |
| `POST` | `/refresh` | `rh.expedientes.refresh` | Regenera datos calculados |
| `GET` | `/{id}` | `rh.expedientes.show` | Detalle del expediente |
| `GET` | `/{id}/editar` | `rh.expedientes.edit` | Formulario de edición |
| `PUT` | `/{id}` | `rh.expedientes.update` | Guardar cambios |
| `DELETE` | `/{id}` | `rh.expedientes.destroy` | Eliminar expediente |
| `POST` | `/{id}/upload` | `rh.expedientes.upload` | Subir documento |
| `DELETE` | `/documento/{id}` | `rh.expedientes.delete-doc` | Eliminar documento |
| `GET` | `/documento/{id}/descargar` | `rh.expedientes.download` | Descargar documento |
| `POST` | `/{id}/import-excel` | `rh.expedientes.import-excel` | Importar Formato ID |
| `POST` | `/{id}/baja` | `rh.expedientes.baja` | Dar de baja |
| `POST` | `/{id}/reactivar` | `rh.expedientes.reactivar` | Reactivar |

---

## 10. Historial de Migraciones

| Fecha | Archivo | Cambio |
|---|---|---|
| 2025-12-09 | `create_rh_tables.php` | Estructura inicial: `empleados`, `asistencias` |
| 2026-01-05 | `create_empleado_documentos_table.php` | Expedientes digitales |
| 2026-01-05 | `add_extra_info_to_empleados_table.php` | Campos extraídos por parser del Formato ID (dirección, teléfonos, contacto emergencia, médico) |
| 2026-01-06 | `create_empleados_baja_table.php` | Historial de bajas laborales |
| 2026-01-08 | `add_fiscal_data_to_empleados_table.php` | RFC, CURP, NSS, banco, cuenta, CLABE |
| 2026-02-11 | `add_es_coordinador_to_empleados_table.php` | Estructura organizacional (supervisor_id, es_coordinador) |

---

## 11. Guía de Mantenimiento del Módulo

---

### 🔴 CRÍTICO: `reactivar()` borra el historial de baja

`EmpleadoBaja::where('empleado_id', $id)->delete()` elimina el registro de baja al reactivar. Si el empleado salió y regresó 3 veces, solo habrá historial de las dos primeras bajas si la tercera se revirtió.

**Impacto**: Reportes de rotación/turnover pueden ser inexactos.

**Fix**: No eliminar el registro, solo marcarlo como reactivado:
```php
EmpleadoBaja::where('empleado_id', $id)->latest()->update(['reactivado_en' => now()]);
```

---

### 🔴 CRÍTICO: `importFormatoId()` desactiva los límites de PHP (`memory_limit = -1`)

Con `ini_set('memory_limit', '-1')`, archivos Excel muy grandes pueden agotar la RAM del servidor y afectar otros procesos concurrentes.

**Solución**: Limitar a un valor alto razonable y procesar en background:
```php
ini_set('memory_limit', '512M');
// O usar job en cola:
ImportarFormatoIdJob::dispatch($path, $empleado->id);
```

---

### 🟡 IMPORTANTE: El mapeo de campos del Formato ID es frágil

El mapeo usa `Str::contains($slug, ['direccion', 'domicilio'])`. Si el Excel tiene una etiqueta en inglés, con typo, o en formato distinto, el campo no se mapea.

**Recomendación**: Implementar un sistema de fallback que log-gue las etiquetas no reconocidas para iterar el mapeo con el tiempo:
```php
Log::info("FormatoID sin mapeo: [{$slug}] = [{$value}]", ['empleado_id' => $empleado->id]);
```

---

### 🟡 IMPORTANTE: `update()` acepta cualquier campo del request

```php
$empleado->update($request->except(['user_id', '_token', '_method']));
```

Si el formulario tuviera campos adicionales (inyectados maliciosamente o por error), se guardarían en BD si están en `$fillable`.

**Mitigación**: Usar `$request->only([...])` con lista explícita de campos editables, o un Form Request con validación completa.

---

### 🟢 SEGURO: Añadir un nuevo documento a la checklist

1. En `Empleado::getRequisitos($esPracticante)`, añadir el nombre del documento al array correspondiente.
2. Los expedientes existentes mostrarán el nuevo documento como "pendiente" automáticamente.
3. No se requiere migración.

---

### 🟢 SEGURO: Añadir un nuevo campo extraído del Formato ID

En `importFormatoId()`, añadir un nuevo bloque `elseif`:
```php
elseif (Str::contains($slug, ['mi-nuevo-campo'])) {
    $data['nombre_campo_en_bd'] = $value;
}
```
Asegurarse de que el campo exista en `$fillable` del modelo `Empleado` y en la BD.

---

### Checklist de deploy para cambios en Expedientes

- [ ] ¿Se añade campo a `Empleado`? Migración + `$fillable` + actualizar formulario de edición + `update()`.
- [ ] ¿Se añade tipo de documento? Actualizar la lista del select en la vista de upload + `getRequisitos()`.
- [ ] ¿Se cambia el disco de almacenamiento de `local` a `s3`? Cambiar `Storage::disk('local')` a `Storage::disk('s3')` en `uploadDocument()`, `deleteDocument()`, `downloadDocument()`, e `importFormatoId()`.
- [ ] ¿Se añade campo al Formato ID? Añadir bloque `elseif` en `importFormatoId()` + migración si el campo no existe en `empleados`.

---

*Documentación generada el 27 de Abril de 2026 — ERP Estrategia e Innovación*
