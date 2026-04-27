# Módulo de Evaluación 180° — Documentación Maestra
> **ERP Estrategia e Innovación** · Versión documental: Abril 2026  
> Audiencia: Desarrolladores de mantenimiento, líderes técnicos, administradores de RH

---

## Tabla de Contenido

1. [Visión General del Módulo](#1-visión-general-del-módulo)
2. [Arquitectura y Componentes](#2-arquitectura-y-componentes)
3. [Metodología 180° — Flujo de Evaluación](#3-metodología-180--flujo-de-evaluación)
4. [Esquema de Base de Datos](#4-esquema-de-base-de-datos)
5. [Modelos Eloquent — Referencia Completa](#5-modelos-eloquent--referencia-completa)
6. [Sistema de Ventanas de Evaluación](#6-sistema-de-ventanas-de-evaluación)
7. [Motor de Selección de Criterios (`getTechnicalArea` + `show`)](#7-motor-de-selección-de-criterios-gettechnicalarea--show)
8. [Sistema de Promedio Ponderado](#8-sistema-de-promedio-ponderado)
9. [Control de Edición y Bloqueo (`edit_count`)](#9-control-de-edición-y-bloqueo-edit_count)
10. [Jerarquía de Roles y Visibilidad](#10-jerarquía-de-roles-y-visibilidad)
11. [Referencia de Endpoints y Rutas](#11-referencia-de-endpoints-y-rutas)
12. [Referencia de Métodos del Controlador](#12-referencia-de-métodos-del-controlador)
13. [Catálogo de Criterios — Áreas conocidas](#13-catálogo-de-criterios--áreas-conocidas)
14. [Historial de Migraciones](#14-historial-de-migraciones)
15. [Guía de Mantenimiento y Funciones Críticas](#15-guía-de-mantenimiento-y-funciones-críticas)
16. [Bugs Conocidos y Deuda Técnica](#16-bugs-conocidos-y-deuda-técnica)

---

## 1. Visión General del Módulo

El **Módulo de Evaluación 180°** es el sistema de desempeño de Recursos Humanos. Permite medir las competencias del personal mediante retroalimentación cruzada: el jefe evalúa a sus colaboradores, los colaboradores evalúan a su jefe, RH evalúa habilidades blandas y cada empleado puede autoevaluarse.

### Propósito de Negocio

| Necesidad | Solución implementada |
|---|---|
| Medir el desempeño con retroalimentación cruzada | Ciclo 180°: jefe↓subordinado, subordinado↑jefe, RH↔todos |
| Adaptar las preguntas al área técnica de cada empleado | Motor `getTechnicalArea()` mapea el puesto a un banco de criterios |
| Controlar cuándo abren y cierran las evaluaciones | Sistema de Ventanas configurado por RH desde la UI |
| Ponderar preguntas de distinta importancia | Cada criterio tiene un campo `peso` que afecta el `promedio_final` |
| Evitar evaluaciones duplicadas | Unique constraint en BD: `(empleado_id, evaluador_id, ventana_id)` |
| Prevenir ediciones una vez entregado | Campo `edit_count` bloquea modificaciones tras el primer envío |
| Portal analítico para Dirección y RH | Endpoint `resultados()` consolida las 3 evaluaciones de 180° |

### Las Cuatro Dimensiones de Evaluación

```
         DIRECCIÓN / RH
               │
               │ Vista completa
               ▼
   ┌─────────────────────────┐
   │  Matriz de Empleados    │
   └──────────┬──────────────┘
              │
    ┌─────────┴──────────┐
    ▼                    ▼
  JEFE               EMPLEADO
  Evalúa ──────────► (Desempeño Técnico
  hacia abajo          + Soft Skills RH)
                         │
                         │ Evalúa hacia arriba
                         ▼
                      SU JEFE
                    (Liderazgo y
                     Gestión de equipo)
                         │
                         │ Autoevaluación
                         ▼
                    EMPLEADO MISMO
                    (Soft Skills)
```

---

## 2. Arquitectura y Componentes

```
┌─────────────────────────────────────────────────────────────┐
│               MÓDULO DE EVALUACIÓN 180°                     │
│                                                             │
│  routes/web.php                                             │
│    └── /capital-humano  (middleware: auth + verified)       │
│          ├── /evaluacion        → Vistas empleados y RH     │
│          └── /evaluacion-ventanas → CRUD JSON de temporadas │
│                            │                                │
│                            ▼                                │
│  EvaluacionController.php  (único controlador)              │
│    ├── index()            → Matriz de "a quién me toca"     │
│    ├── show($id)          → Cuestionario adaptado por rol   │
│    ├── store()            → Guardar + calcular promedio      │
│    ├── update($id)        → Re-evaluar (si edit_count < 1)  │
│    ├── destroy($id)       → Borrar (solo RH/Admin)          │
│    ├── resultados($id)    → Consolidado analítico 180°      │
│    ├── getVentanas()      → JSON: listar temporadas         │
│    ├── saveVentana()      → JSON: crear temporada           │
│    └── toggleVentana($id) → JSON: activar/desactivar        │
│                                                             │
│  Métodos Privados (helpers internos)                        │
│    ├── isEvaluationWindowOpen() → ¿Está abierta la ventana?│
│    ├── isAdminRH($empleado)     → Detección de rol RH       │
│    ├── hasFullVisibility($user) → Detección Dirección/RH    │
│    └── getTechnicalArea($pos)   → Mapeo puesto → área BD    │
│                                                             │
│  Modelos Eloquent                                           │
│    ├── Evaluacion.php          (cabecera de la evaluación)  │
│    ├── EvaluacionDetalle.php   (respuestas por criterio)    │
│    ├── CriterioEvaluacion.php  (catálogo del examen)        │
│    └── EvaluacionVentana.php   (control de temporadas)      │
│                                                             │
│  Vistas Blade                                               │
│    ├── Recursos_Humanos/evaluacion/index.blade.php          │
│    ├── Recursos_Humanos/evaluacion/show.blade.php           │
│    └── Recursos_Humanos/evaluacion/resultados.blade.php     │
└─────────────────────────────────────────────────────────────┘
```

### Dependencias clave

| Dependencia | Uso |
|---|---|
| `Carbon\Carbon` | Validación de fechas de ventanas y cálculo del período semestral por defecto |
| `Illuminate\Support\Facades\DB` | Transacciones atómicas en `store()` y `update()` |
| `Illuminate\Support\Facades\Auth` | Identificar al evaluador actual en cada transacción |

---

## 3. Metodología 180° — Flujo de Evaluación

La evaluación recorre tres vectores formales más la autoevaluación, siendo el cuestionario diferente en cada caso:

### Vector 1: Evaluación Hacia Abajo (Jefe → Subordinado)

- **Quién evalúa**: El jefe directo (`$me->id == $target->supervisor_id`)
- **Cuestionario**: Criterios del **área técnica** del subordinado + criterios de **Recursos Humanos** (Soft Skills)
- **Etiqueta en vista**: `"Evaluación de Desempeño ({área técnica} + Soft Skills)"`

### Vector 2: Evaluación Hacia Arriba (Subordinado → Jefe)

- **Quién evalúa**: El colaborador cuyo `supervisor_id` es el evaluado (`$me->supervisor_id == $target->id`)
- **Cuestionario**: Solo criterios del área **`'Evaluacion Supervisor'`** (liderazgo, comunicación, gestión)
- **Etiqueta en vista**: `"Evaluación de Liderazgo (A tu Supervisor)"`

### Vector 3: Evaluación de RH (Admin RH → Cualquier empleado)

- **Quién evalúa**: Usuario con puesto/área de Administración RH (no es jefe directo)
- **Cuestionario**: Solo criterios del área **`'Administracion RH'`** (habilidades blandas y valores)
- **Etiqueta en vista**: `"Evaluación de Habilidades Blandas y Valores (RH)"`

### Vector 4: Autoevaluación

- **Quién evalúa**: El propio empleado (`$me->id == $target->id`)
- **Cuestionario**: Igual que la evaluación hacia abajo — área técnica + Soft Skills RH
- **Etiqueta en vista**: `"Autoevaluación ({área técnica} + Soft Skills)"`

> **⚠️ Bug Activo**: La autoevaluación no aparece en la lista del método `index()` para empleados sin subordinados. Ver [Bug #1](#16-bugs-conocidos-y-deuda-técnica).

---

## 4. Esquema de Base de Datos

### Tabla: `criterios_evaluacion`

El catálogo maestro de preguntas/criterios del examen. Gestionado por RH desde el seeder o directamente en BD.

| Columna | Tipo | Nullable | Descripción |
|---|---|---|---|
| `id` | `bigint unsigned` | NO | PK auto-increment |
| `area` | `varchar` | NO | Banco al que pertenece el criterio. Valores conocidos: `'Logistica'`, `'Legal'`, `'Anexo 24'`, `'TI'`, `'Pedimentos'`, `'Auditoria'`, `'Post-Operacion'`, `'Gestion RH'`, `'Evaluacion Supervisor'`, `'Recursos Humanos'`, `'Administracion RH'`, `'General'` |
| `criterio` | `varchar` | NO | La pregunta o competencia a evaluar |
| `descripcion` | `text` | SÍ | Descripción ampliada del criterio (guía para el evaluador) |
| `peso` | `integer` | NO | Peso porcentual (0–100) del criterio en el promedio ponderado final |
| `created_at` / `updated_at` | `timestamp` | SÍ | Timestamps estándar |

> **IMPORTANTE**: El campo `peso` no suma necesariamente 100 por área. El sistema normaliza el promedio dividiendo `totalPuntos / totalPeso`. Si todos los criterios de un cuestionario suman 100, el resultado es un porcentaje directo. Si suman más o menos, el sistema lo normaliza automáticamente.

---

### Tabla: `evaluaciones`

La cabecera maestra de cada evaluación. Registra quién evalúa a quién, en qué temporada, con qué promedio final.

| Columna | Tipo | Nullable | Descripción |
|---|---|---|---|
| `id` | `bigint unsigned` | NO | PK auto-increment |
| `empleado_id` | `bigint unsigned` | NO | FK → `empleados.id` (cascade delete). **El evaluado** |
| `evaluador_id` | `bigint unsigned` | NO | FK → `users.id`. **Quien califica** |
| `periodo` | `varchar` | NO | Texto de período (ej: `"2026 \| Enero - Junio"`) — campo legado, se mantiene por compatibilidad histórica |
| `ventana_id` | `bigint unsigned` | SÍ | FK → `evaluacion_ventanas.id` (set null on delete). Ventana activa al momento de la evaluación |
| `promedio_final` | `decimal(5,2)` | SÍ | Resultado calculado (promedio ponderado de todos los criterios) |
| `comentarios_generales` | `text` | SÍ | Observación libre del evaluador al finalizar |
| `edit_count` | `integer` | NO | Contador de ediciones. `0` = no enviado. `≥1` = evaluación bloqueada |
| `fecha_firma_empleado` | `datetime` | SÍ | Campo reservado para firma digital futura (actualmente no se usa en lógica) |
| `created_at` / `updated_at` | `timestamp` | SÍ | Timestamps estándar |

**Constraint único activo**: `eval_unica_ventana (empleado_id, evaluador_id, ventana_id)` — evita que un mismo evaluador califique dos veces al mismo empleado en la misma ventana.

> **Constraint legado reemplazado**: El constraint original `eval_unica_par (empleado_id, evaluador_id, periodo)` fue eliminado en la migración `2026_04_08` y reemplazado por `eval_unica_ventana`.

---

### Tabla: `evaluacion_detalles`

Las filas hijas. Una fila por cada criterio respondido en una evaluación.

| Columna | Tipo | Nullable | Descripción |
|---|---|---|---|
| `id` | `bigint unsigned` | NO | PK |
| `evaluacion_id` | `bigint unsigned` | NO | FK → `evaluaciones.id` (cascade delete) |
| `criterio_id` | `bigint unsigned` | NO | FK → `criterios_evaluacion.id` |
| `calificacion` | `decimal(5,2)` | NO | Calificación numérica del 1 al 5 |
| `observaciones` | `text` | SÍ | Comentario libre del evaluador sobre este criterio específico |
| `created_at` / `updated_at` | `timestamp` | SÍ | Timestamps estándar |

---

### Tabla: `evaluacion_ventanas`

Control de temporadas de evaluación, gestionadas desde la UI por RH.

| Columna | Tipo | Nullable | Descripción |
|---|---|---|---|
| `id` | `bigint unsigned` | NO | PK |
| `nombre` | `varchar` | NO | Nombre descriptivo (ej: `"Evaluación Semestre 1 2026"`) |
| `fecha_apertura` | `date` | NO | Primer día en que los empleados pueden evaluar |
| `fecha_cierre` | `date` | NO | Último día del período de evaluación |
| `activo` | `boolean` | NO | Solo debería haber una ventana `activo=true` a la vez |
| `creado_por` | `bigint unsigned` | SÍ | ID del usuario de RH que creó la ventana |
| `created_at` / `updated_at` | `timestamp` | SÍ | Timestamps estándar |

---

## 5. Modelos Eloquent — Referencia Completa

### `App\Models\Evaluacion`

**Archivo**: `app/Models/Evaluacion.php`

```php
protected $casts = [
    'fecha_firma_empleado' => 'datetime',
];
```

#### Relaciones

| Método | Tipo | Descripción |
|---|---|---|
| `ventana()` | `BelongsTo(EvaluacionVentana)` | Temporada a la que pertenece |
| `detalles()` | `HasMany(EvaluacionDetalle)` | Todas las calificaciones individuales |
| `empleado()` | `BelongsTo(Empleado)` | El empleado evaluado |
| `evaluador()` | `BelongsTo(User, 'evaluador_id')` | El usuario que realizó la evaluación |

---

### `App\Models\EvaluacionDetalle`

**Archivo**: `app/Models/EvaluacionDetalle.php`

Modelo ligero sin lógica de negocio.

| Relación | Tipo |
|---|---|
| `criterio()` | `BelongsTo(CriterioEvaluacion, 'criterio_id')` |

---

### `App\Models\CriterioEvaluacion`

**Archivo**: `app/Models/CriterioEvaluacion.php`

Sin relaciones propias. Es el catálogo consultado por el controlador. Debe administrarse directamente en BD o via seeder.

```php
protected $fillable = ['area', 'criterio', 'descripcion', 'peso'];
```

---

### `App\Models\EvaluacionVentana`

**Archivo**: `app/Models/EvaluacionVentana.php`

```php
protected $casts = [
    'fecha_apertura' => 'date',
    'fecha_cierre'   => 'date',
    'activo'         => 'boolean',
];
```

#### Métodos Estáticos Clave

| Método | Retorno | Descripción |
|---|---|---|
| `estaAbierta(): bool` | `bool` | `true` si hoy está entre `fecha_apertura` y `fecha_cierre` de una ventana activa |
| `ventanaActual(): ?self` | `?EvaluacionVentana` | Retorna el objeto de la ventana vigente, o `null` si no hay ninguna activa hoy |

**Lógica de `estaAbierta()`**:
```
hoy = Carbon::today()
→ SELECT WHERE activo=true AND fecha_apertura <= hoy AND fecha_cierre >= hoy
→ EXISTS? true : false
```

---

## 6. Sistema de Ventanas de Evaluación

Controla cuándo los empleados pueden ingresar y enviar evaluaciones.

### Prioridad: BD vs. Fallback hardcodeado

El método privado `isEvaluationWindowOpen()` del controlador sigue esta lógica:

```
¿Existe alguna ventana activa en BD?
    SÍ → Delegar a EvaluacionVentana::estaAbierta()
           (verifica fecha_apertura ≤ hoy ≤ fecha_cierre)
    NO → Fallback semestral hardcodeado:
           Junio 21-30 o Diciembre 1-31
```

> **Crítico**: El fallback solo aplica si NO hay ninguna ventana en la tabla con `activo=true`. Si se crea una ventana inactiva (`activo=false`), el fallback **no** se activa porque `EvaluacionVentana::where('activo', true)->exists()` devuelve `false` y el sistema cae al fallback. Solo si no hay absolutamente ninguna ventana activa en BD.

### Flujo Completo de Gestión de Ventanas

```
1. RH accede al panel de evaluaciones (index)
2. Frontend llama GET /capital-humano/evaluacion-ventanas → lista JSON
3. RH crea una nueva ventana: nombre, fecha_apertura, fecha_cierre
4. POST /capital-humano/evaluacion-ventanas
   → Sistema desactiva todas las ventanas previas (activo=false)
   → Crea la nueva ventana con activo=true
5. Durante el período: todos los empleados pueden evaluar
6. RH puede cerrar prematuramente con PATCH /{id}/toggle
   → Invierte el campo activo de la ventana
   → Si se activa otra, desactiva las demás primero
```

### Gestión de Ventanas — API

**Crear nueva temporada**:
```http
POST /capital-humano/evaluacion-ventanas
Content-Type: application/json

{
  "nombre": "Evaluación Semestre 1 2026",
  "fecha_apertura": "2026-06-21",
  "fecha_cierre": "2026-06-30",
  "activo": true
}
```
Responde: `{ success: true, ventana: {...}, message: "..." }`

**Toggle activar/desactivar**:
```http
PATCH /capital-humano/evaluacion-ventanas/{id}/toggle
```
Responde: `{ success: true, activo: false }`

---

## 7. Motor de Selección de Criterios (`getTechnicalArea` + `show`)

Este es el núcleo inteligente del cuestionario. El método `show()` determina qué banco de preguntas mostrar según la relación entre el evaluador y el evaluado.

### Paso 1: Determinar la relación jerárquica

```php
$isDirectSupervisor = ($target->supervisor_id == $me->id);  // ¿Soy su jefe?
$isBoss             = ($me->supervisor_id == $target->id);  // ¿Es mi jefe?
$isSelf             = ($me->id == $target->id);             // ¿Soy yo mismo?
$isAdminRH          = $this->isAdminRH($me);               // ¿Soy RH?
```

### Paso 2: Árbol de decisión de criterios

```
¿Es mi jefe directo? ($isBoss)
    → Criterios: area = 'Evaluacion Supervisor'
    → Título: "Evaluación de Liderazgo (A tu Supervisor)"

¿Es mi subordinado directo? ($isDirectSupervisor)
    → Detectar área técnica del EVALUADO con getTechnicalArea()
    → Criterios: area = {areaTecnica} OR area = 'Recursos Humanos'
    → Título: "Evaluación de Desempeño ({área} + Soft Skills)"

¿Es mi propia ficha? ($isSelf)
    → Mismo cuestionario que supervisor hacia subordinado
    → Título: "Autoevaluación ({área} + Soft Skills)"

¿Soy Admin RH? ($isAdminRH)
    → Criterios: area = 'Administracion RH'
    → Título: "Evaluación de Habilidades Blandas y Valores (RH)"

Default (sin relación clara)
    → Criterios: area = 'Recursos Humanos'
    → Título: "Evaluación General"
```

### Paso 3: `getTechnicalArea($posicion)` — Mapa de Palabras Clave

**Archivo**: `app/Http/Controllers/EvaluacionController.php`, método privado.

| Palabra clave en el puesto (lowercase) | Área en `criterios_evaluacion` |
|---|---|
| `logistica`, `logística` | `'Logistica'` |
| `legal`, `abogado` | `'Legal'` |
| `anexo 24`, `anexo 31` | `'Anexo 24'` |
| `ti`, `sistemas`, `programador`, `soporte` | `'TI'` |
| `pedimentos`, `glosa` | `'Pedimentos'` |
| `auditoria`, `auditor` | `'Auditoria'` |
| `post-operacion`, `post operacion`, `postoperacion` | `'Post-Operacion'` |
| `administracion rh`, `recursos humanos` | `'Gestion RH'` |
| _(ninguna coincidencia)_ | `'General'` ← fallback |

> **Crítico**: La comparación se hace con `str_contains()` sin word boundary. El puesto `"Soporte Logístico"` coincidirá tanto con `'TI'` (por "soporte") como con `'Logistica'` (por "logística"). Gana la primera coincidencia que encuentre el `foreach`. El orden del array `$mapa` define la prioridad.

---

## 8. Sistema de Promedio Ponderado

El `promedio_final` no es una simple media aritmética. Cada criterio tiene un `peso` que pondera su importancia.

### Fórmula

$$\text{promedio\_final} = \frac{\sum_{i=1}^{n} (\text{calificacion}_i \times \text{peso}_i)}{\sum_{i=1}^{n} \text{peso}_i}$$

### Implementación en `store()` y `update()`

```php
$criteriosDb = CriterioEvaluacion::whereIn('id', array_keys($request->calificaciones))->get();
$totalPuntos = 0;
$totalPeso   = 0;

foreach ($criteriosDb as $criterio) {
    $calificacion  = $request->calificaciones[$criterio->id] ?? 0;
    $peso          = $criterio->peso ?? 0;
    $totalPuntos  += ($calificacion * $peso);
    $totalPeso    += $peso;
}

$promedio = ($totalPeso > 0) ? ($totalPuntos / $totalPeso) : 0;
```

### Ejemplo práctico

| Criterio | Calificación | Peso | Puntos |
|---|---|---|---|
| Cumplimiento de objetivos | 4 | 30 | 120 |
| Trabajo en equipo | 3 | 20 | 60 |
| Puntualidad | 5 | 10 | 50 |
| **Total** | — | **60** | **230** |

$$\text{promedio\_final} = \frac{230}{60} = 3.83$$

### Rango de calificación

Las calificaciones individuales por criterio van del **1 al 5** (escala Likert enviada desde radios del formulario). El `promedio_final` es un `decimal(5,2)`, lo que permite almacenar resultados de hasta `999.99` — suficiente para cualquier ponderación posible.

---

## 9. Control de Edición y Bloqueo (`edit_count`)

El campo `edit_count` en `evaluaciones` controla si una evaluación ya fue "entregada definitivamente".

| `edit_count` | Significado | ¿Puede editarse? |
|---|---|---|
| `0` | Borrador / no enviado (no debería existir con la lógica actual) | SÍ |
| `1` | Enviado una vez (valor al crear con `store()`) | NO — bloqueado |
| `≥2` | Editado por RH o en condición especial | NO |

### Regla de bloqueo en `show()`

```php
$isFinalized = ($evaluacion && $evaluacion->edit_count >= 1);
$canEdit = $isWindowOpen && !$isFinalized;
// Se pasa a la vista como: 'is_locked' => !$canEdit
```

La vista usa `is_locked` para deshabilitar los campos del formulario y ocultar el botón de enviar.

### ¿Cuándo se puede "desbloquear"?

Solo RH puede hacerlo, borrando la evaluación completa con `destroy()` y pidiendo al evaluador que vuelva a llenar el formulario. No hay mecanismo de reset de `edit_count`.

---

## 10. Jerarquía de Roles y Visibilidad

El módulo maneja 3 niveles de visibilidad, controlados por métodos privados del controlador.

### Métodos de detección de roles

#### `isAdminRH($empleado): bool`

Busca las siguientes cadenas en `posicion` o `area` del empleado (case-insensitive, UTF-8):
```
'administración rh' | 'administracion rh' | 'administracion de rh' | 'administración de rh'
(en area:) 'recursos humanos' | 'administracion rh' | 'administración rh'
```

#### `hasFullVisibility($user): bool`

Retorna `true` si el usuario es Dirección O Admin RH:
```
posicion contiene 'dirección' o 'direccion'
ó
isAdminRH() retorna true
ó
area contiene 'recursos humanos'
```

### Matriz de permisos

| Acción | Empleado normal | Supervisor | Admin RH | Dirección |
|---|---|---|---|---|
| Ver lista de empleados a evaluar | Solo su jefe + sus subordinados | Sus subordinados + su jefe | **Todos** | **Todos** |
| Ver autoevaluación en lista | ⚠️ Bug activo — no aparece | ⚠️ Bug activo | ✅ | ✅ |
| Abrir cuestionario de subordinado | ✅ | ✅ | ✅ (criterios RH) | ✅ |
| Abrir cuestionario de su jefe | ✅ | ✅ | ✅ | ✅ |
| Filtrar por área en la matriz | ❌ | ❌ | ✅ | ✅ |
| Ver portal de `resultados()` | ❌ | ❌ | ✅ | ✅ |
| Eliminar una evaluación | ❌ | ❌ | ✅ | ✅ |
| Gestionar ventanas de evaluación | ❌ | ❌ | ✅ (`es_coordinador` implícito) | ✅ |

---

## 11. Referencia de Endpoints y Rutas

**Middleware**: `auth`, `verified`  
**Prefijo URI**: `/capital-humano`  
**Prefijo nombre**: `rh.`  
**Controlador**: `App\Http\Controllers\EvaluacionController`

### Endpoints de Evaluación

| Método | URI | Nombre de Ruta | Método Controlador | Descripción |
|---|---|---|---|---|
| `GET` | `/capital-humano/evaluacion` | `rh.evaluacion.index` | `index()` | Matriz de empleados a evaluar |
| `GET` | `/capital-humano/evaluacion/{id}` | `rh.evaluacion.show` | `show($id)` | Cuestionario adaptado |
| `POST` | `/capital-humano/evaluacion` | `rh.evaluacion.store` | `store()` | Guardar nueva evaluación |
| `PUT` | `/capital-humano/evaluacion/{id}` | `rh.evaluacion.update` | `update($id)` | Editar evaluación existente |
| `DELETE` | `/capital-humano/evaluacion/{id}` | `rh.evaluacion.destroy` | `destroy($id)` | Eliminar (solo RH/Admin) |
| `GET` | `/capital-humano/evaluacion/{id}/resultados` | `rh.evaluacion.resultados` | `resultados($id)` | Portal analítico 180° |

### Endpoints de Ventanas (JSON)

| Método | URI | Nombre de Ruta | Método Controlador | Respuesta |
|---|---|---|---|---|
| `GET` | `/capital-humano/evaluacion-ventanas` | `rh.evaluacion.ventanas.index` | `getVentanas()` | `JSON { ventanas, ventana_activa }` |
| `POST` | `/capital-humano/evaluacion-ventanas` | `rh.evaluacion.ventanas.store` | `saveVentana()` | `JSON { success, ventana, message }` |
| `PATCH` | `/capital-humano/evaluacion-ventanas/{id}/toggle` | `rh.evaluacion.ventanas.toggle` | `toggleVentana($id)` | `JSON { success, activo }` |

### Parámetros GET opcionales para `index()`

| Parámetro | Descripción |
|---|---|
| `periodo` | Período legado para ver evaluaciones históricas (ej: `"2025 \| Julio - Diciembre"`) |
| `area` | Filtrar empleados por posición (solo disponible con `hasFullVisibility`) |

---

## 12. Referencia de Métodos del Controlador

**Archivo**: `app/Http/Controllers/EvaluacionController.php`

---

### `index(Request $request)`

Arma la lista de empleados que el usuario autenticado **debe** evaluar en la temporada activa.

**Lógica de visibilidad**:
```
¿hasFullVisibility?
    SÍ → Todos los empleados (con filtro opcional por área)
    NO → Solo: supervisor_id = $me->id (mis subordinados)
              + id = $me->supervisor_id (mi jefe)
    ⚠️ FALTA: + id = $me->id (yo mismo — bug de autoevaluación)
```

**Variables enviadas a la vista** (`evaluacion.index`):

| Variable | Descripción |
|---|---|
| `$empleados` | Collection de empleados con el campo `evaluacion_actual` adjuntado (evaluación de esta ventana o `null`) |
| `$periodos` | Array de 5 períodos textuales para el selector histórico |
| `$selectedPeriod` | Período seleccionado actualmente |
| `$isWindowOpen` | `bool` — Si la ventana está abierta |
| `$hasFullVisibility` | `bool` — Si el usuario ve a todos |
| `$isAdminRH` | `bool` — Si es administrador de RH |
| `$puedeGestionarVentanas` | `bool` — Si puede administrar las temporadas |
| `$ventanaActiva` | Objeto `EvaluacionVentana` o `null` |

---

### `show(Request $request, $id)`

El orquestador central del cuestionario. Determina qué evaluación mostrar y con qué criterios.

**Parámetro requerido**: `?periodo=` en query string (requerido para compatibilidad histórica).

**Validaciones antes de mostrar el formulario**:
1. `¿La ventana está abierta?` → Si no, el formulario se muestra en modo solo-lectura (`is_locked = true`).
2. `¿El usuario tiene relación válida con el evaluado?` → Si no, `redirect` con error `'No autorizado.'`
3. `¿Ya evaluó a esta persona en esta ventana?` → Carga la evaluación existente en `$respuestas` para pre-llenar.

**Carga de respuestas previas** (para re-llenado del formulario):
```php
$respuestas    = [];  // [criterio_id => calificacion]
$observaciones = [];  // [criterio_id => texto]
foreach ($evaluacion->detalles as $detalle) {
    $respuestas[$detalle->criterio_id]    = $detalle->calificacion;
    $observaciones[$detalle->criterio_id] = $detalle->observaciones;
}
```

---

### `store(Request $request)`

Guarda una nueva evaluación. Opera dentro de una `DB::transaction()`.

**Validaciones previas** (en este orden):
1. ¿Ventana abierta? → Error si no.
2. ¿Hay ventana activa en BD? → Error si no (requiere ventana registrada).
3. ¿Ya existe una evaluación de este evaluador para este empleado en esta ventana? → Error de duplicado.

**Estructura del payload POST esperado**:
```
empleado_id:           int
evaluador_id:          int (se sobreescribe con Auth::id() en el controlador)
periodo:               string  (ej: "2026 | Enero - Junio")
comentarios_generales: string  (opcional)
calificaciones:        { criterio_id: valor, criterio_id: valor, ... }
observaciones:         { criterio_id: texto, criterio_id: texto, ... }
```

**Al guardar**: `edit_count` se inicializa en `1`, marcando la evaluación como bloqueada para edición futura del evaluador.

---

### `update(Request $request, $id)`

Permite a RH o al propio evaluador (si `edit_count < 1`) re-enviar el formulario.

**Validaciones**:
- El `evaluador_id` de la evaluación debe coincidir con `Auth::id()` (o el usuario debe ser RH/Admin).
- La lógica en `show()` bloquea la UI si `edit_count >= 1`, pero `update()` no revalida `edit_count` en backend — confía en el bloqueo de la UI.

**Operación de actualización de detalles**:
```php
$evaluacion->detalles()->delete();  // Borra todos los detalles anteriores
// Luego re-crea cada detalle con los nuevos valores
```

---

### `destroy($id)`

Solo accesible por Admin RH o Admins del sistema.

**Efecto**: `$evaluacion->delete()` — El cascade en la FK de `evaluacion_detalles` elimina automáticamente todas las filas hijas.

Úsese para invalidar evaluaciones hechas de mala fe y forzar al evaluador a repetirlas.

---

### `resultados(Request $request, $id)`

Portal analítico disponible únicamente para usuarios con `hasFullVisibility`.

**Parámetro requerido**: `?periodo=` en query string.

**Lógica de consolidación**:
1. Carga todas las evaluaciones del empleado `$id` en el período dado.
2. Calcula `$promedioGeneral = $evaluaciones->avg('promedio_final')`.
3. Clasifica el rol de cada evaluador:
   - `'Supervisor Directo'` si `empleado->supervisor_id == evaluador->id`
   - `'Subordinado'` si `evaluador->supervisor_id == empleado->id`
   - `'Administración RH'` si el evaluador tiene posición de RH
   - `'Colaborador'` como fallback

**Variables enviadas a la vista** (`evaluacion.resultados`):

| Variable | Descripción |
|---|---|
| `$empleado` | El empleado evaluado |
| `$periodo` | Período del reporte |
| `$promedioGeneral` | Promedio de todos los `promedio_final` de sus evaluaciones |
| `$desglose` | Collection de evaluaciones enriquecidas con `rol_evaluador` y `nombre_evaluador` |

---

## 13. Catálogo de Criterios — Áreas Conocidas

El catálogo de criterios se gestiona mediante seeders o directamente en BD. Las áreas conocidas que el motor de selección consulta son:

| Área en BD | Descripción | Cuándo se usa |
|---|---|---|
| `'Logistica'` | Criterios técnicos del área de logística | Puesto contiene "logistica/logística" |
| `'Legal'` | Criterios para abogados y área legal | Puesto contiene "legal" o "abogado" |
| `'Anexo 24'` | Criterios para Anexo 24 y Anexo 31 | Puesto contiene "anexo 24" o "anexo 31" |
| `'TI'` | Criterios para tecnología de información | Puesto contiene "ti", "sistemas", "programador", "soporte" |
| `'Pedimentos'` | Criterios para pedimentos y glosa | Puesto contiene "pedimentos" o "glosa" |
| `'Auditoria'` | Criterios para auditores | Puesto contiene "auditoria" o "auditor" |
| `'Post-Operacion'` | Criterios para post-operaciones | Puesto contiene "post-operacion/post operacion/postoperacion" |
| `'Gestion RH'` | Parte técnica para personal de RH | Puesto contiene "administracion rh" o "recursos humanos" |
| `'General'` | Fallback para puestos no mapeados | Cuando ninguna palabra clave coincide |
| `'Recursos Humanos'` | Soft Skills / Habilidades blandas | Siempre se combina con el área técnica del evaluado |
| `'Administracion RH'` | Criterios exclusivos del evaluador RH | Cuando RH evalúa sin ser jefe directo |
| `'Evaluacion Supervisor'` | Criterios de liderazgo | Cuando el evaluado es el jefe del evaluador |

> **IMPORTANTE para agregar nuevos departamentos**: Si nace un nuevo área técnica, se debe:  
> 1. Insertar criterios en BD con el nuevo valor en `area`.  
> 2. Agregar la palabra clave → área en el array `$mapa` de `getTechnicalArea()` en el controlador.

---

## 14. Historial de Migraciones

| Fecha | Archivo | Cambio |
|---|---|---|
| 2025-12-16 | `create_criterios_evaluacion_table.php` | Crea tabla `criterios_evaluacion` con `area`, `criterio`, `descripcion` y `peso` |
| 2025-12-18 | `create_evaluaciones_tables.php` | Crea tablas `evaluaciones` y `evaluacion_detalles`. Constraint único original: `(empleado_id, evaluador_id, periodo)` |
| 2026-04-06 | `create_evaluacion_ventanas_table.php` | ⭐ Crea tabla `evaluacion_ventanas` para gestionar temporadas desde la UI en lugar de fechas hardcodeadas en código |
| 2026-04-08 | `add_ventana_id_to_evaluaciones.php` | ⭐ Agrega `ventana_id` a `evaluaciones` (nullable FK → set null on delete). Reemplaza el constraint `eval_unica_par` por `eval_unica_ventana (empleado_id, evaluador_id, ventana_id)`. Migración idempotente con verificación `SHOW INDEX` antes de agregar/quitar constraints |

---

## 15. Guía de Mantenimiento y Funciones Críticas

---

### 🔴 CRÍTICO: No tocar sin leer esto primero

#### 1. `getTechnicalArea()` — El árbol de selección de cuestionario

**Archivo**: `app/Http/Controllers/EvaluacionController.php`

Es el método más delicado del módulo. Determina qué preguntas ve el empleado. Un error aquí resulta en que un trabajador de Logística vea el examen de Legal, o viceversa.

**Reglas al modificar**:
- Siempre probar con puestos reales del catálogo de la empresa.
- El `str_contains()` sin word boundary significa que el **orden importa**: si un puesto contiene dos palabras clave, gana la que aparezca primero en el array `$mapa`.
- Agregar siempre la versión sin acento Y con acento del término (ej: `'logistica'` y `'logística'`).
- Si se agrega una nueva área, también crear los criterios en `criterios_evaluacion` con el mismo valor exacto de `area` antes de hacer el deploy.

---

#### 2. El constraint único `eval_unica_ventana`

**Tabla**: `evaluaciones`  
**Constraint**: `UNIQUE (empleado_id, evaluador_id, ventana_id)`

Este constraint previene duplicados. Si se necesita que un evaluador pueda evaluar al mismo empleado **dos veces** en la misma ventana (escenario no previsto), el constraint debe modificarse.

Si se borra la ventana activa (no recomendado), el campo `ventana_id` se pone en `NULL` por el `set null on delete`. Dos evaluaciones del mismo par en `ventana_id = NULL` **no** violan el unique porque `NULL != NULL` en SQL. Esto podría generar duplicados en BD si se borran ventanas.

---

#### 3. `isEvaluationWindowOpen()` — Fallback semestral

**Archivo**: `EvaluacionController.php`, método privado.

El fallback hardcodeado (junio 21-30 y diciembre 1-31) se activa **solo si no hay ninguna ventana con `activo=true` en BD**. Mientras haya aunque sea una ventana activa, el fallback nunca se ejecuta.

**Situación peligrosa**: Si RH crea una ventana con `activo=true` pero con `fecha_cierre` en el pasado, `EvaluacionVentana::estaAbierta()` retornará `false` (porque hoy es mayor que la fecha de cierre), pero el fallback tampoco se ejecutará (porque existe una ventana activa). Resultado: el sistema queda bloqueado aunque el fallback temporal lo permitiría. La solución es que RH haga toggle de esa ventana para desactivarla.

---

### 🟡 IMPORTANTE: Puntos de extensión frecuentes

#### Agregar un nuevo departamento o área técnica

1. Insertar criterios en `criterios_evaluacion` con el nuevo valor en `area`:
   ```sql
   INSERT INTO criterios_evaluacion (area, criterio, descripcion, peso)
   VALUES ('NuevaDepartamento', 'Criterio 1', 'Descripción', 25);
   ```
2. Agregar la palabra clave en `getTechnicalArea()`:
   ```php
   'nuevadept' => 'NuevaDepartamento',
   ```
3. No se requieren cambios en modelos ni migraciones.

---

#### Permitir más de una edición por evaluador

Modificar la condición de bloqueo en `show()`:
```php
// Actualmente:
$isFinalized = ($evaluacion && $evaluacion->edit_count >= 1);

// Para permitir hasta 2 ediciones:
$isFinalized = ($evaluacion && $evaluacion->edit_count >= 2);
```
También actualizar el mensaje en la vista que informa al empleado cuántos intentos tiene.

---

#### Agregar un nuevo criterio a un área existente

Directamente en BD o via seeder:
```sql
INSERT INTO criterios_evaluacion (area, criterio, descripcion, peso)
VALUES ('TI', 'Nuevo criterio técnico', 'Descripción del criterio', 20);
```
No se requiere cambio de código. El controlador carga todos los criterios del área dinámicamente.

---

#### Agregar el período al historial de búsqueda

El array `$periodos` en `index()` se construye manualmente. Si se necesita un tercer período en el mismo año o un rango diferente, agregar el string al array:
```php
$periodos = [
    // ... períodos existentes
    "$currentYear | Enero - Marzo",  // ← agregar aquí
];
```

---

#### Cambiar el cálculo del promedio a media aritmética simple

En `store()` y `update()`, reemplazar el cálculo ponderado:
```php
// En lugar de:
$promedio = ($totalPeso > 0) ? ($totalPuntos / $totalPeso) : 0;

// Por:
$promedio = $criteriosDb->avg(fn($c) => $request->calificaciones[$c->id] ?? 0);
```

---

### 🟢 SEGURO: Cambios de bajo riesgo

- Modificar textos de `redirect()->with('success', '...')` o `->with('error', '...')`.
- Cambiar el rango del fallback hardcodeado (fechas de junio/diciembre).
- Modificar el ordenamiento de `EvaluacionVentana::orderByDesc('fecha_apertura')` en `getVentanas()`.
- Agregar campos de solo lectura a las vistas (sin lógica de negocio).

---

### Checklist antes de un deploy con cambios al módulo

- [ ] ¿Cambió `getTechnicalArea()`? Verificar con puestos reales de la empresa que el mapeo es correcto.
- [ ] ¿Se agregó un área nueva en el mapa? Verificar que existe en `criterios_evaluacion` antes del deploy.
- [ ] ¿Cambió `isEvaluationWindowOpen()`? Verificar escenario de ventana expirada con `activo=true`.
- [ ] ¿Cambió el constraint de unicidad? Revisar registros existentes en `evaluaciones` para evitar violaciones.
- [ ] ¿El nuevo endpoint está dentro del grupo con `middleware(['auth', 'verified'])`?
- [ ] ¿Cambió la lógica de `resultados()`? Verificar que `hasFullVisibility` sigue siendo la única puerta de acceso.
- [ ] ¿Se modificó el `edit_count`? Verificar que `update()` en backend no permite editar evaluaciones bloqueadas.

---

## 16. Bugs Conocidos y Deuda Técnica

### 🔴 Alta Prioridad (Bug Funcional)

#### Bug #1: La autoevaluación no aparece en la lista del `index()`

**Archivo**: `EvaluacionController.php`, método `index()`  
**Descripción**: La query de empleados a evaluar incluye subordinados y jefe, pero olvida incluir al propio empleado. Un analista sin subordinados solo ve a su jefe en la lista y nunca puede verse a sí mismo para hacer la autoevaluación.

**Código actual**:
```php
$query->where(function ($q) use ($me) {
    $q->where('supervisor_id', $me->id)  // mis subordinados
        ->orWhere('id', $me->supervisor_id); // mi jefe
});
```

**Solución**: Agregar `->orWhere('id', $me->id)`:
```php
$query->where(function ($q) use ($me) {
    $q->where('supervisor_id', $me->id)
        ->orWhere('id', $me->supervisor_id)
        ->orWhere('id', $me->id);  // ← me incluyo a mí mismo
});
```

---

### 🟡 Media Prioridad (Deuda Técnica)

#### Búsqueda de empleado por correo en lugar de relación Eloquent

**Archivo**: `EvaluacionController.php`, múltiples métodos  
**Problema**: El controlador repite `Empleado::where('correo', $user->email)->first()` en lugar de usar `Auth::user()->empleado`. Esto es frágil ante diferencias de mayúsculas o espacios en el correo.

**Solución**: Reemplazar todas las ocurrencias por:
```php
$me = Auth::user()->empleado;  // Usa la relación Eloquent directa
```

---

#### Funciones `isAdminRH()` y `hasFullVisibility()` duplican lógica de `User::isRh()`

**Archivo**: `EvaluacionController.php` vs `app/Models/User.php`  
**Problema**: Las palabras clave de "Recursos Humanos" están definidas en el controlador Y en el modelo `User`. Si se agrega un nuevo alias de RH solo en uno de los dos, el otro quedará desactualizado y generará inconsistencias.

**Solución**: Eliminar los métodos privados del controlador y usar `Auth::user()->isRh()`:
```php
// En lugar de:
$isAdminRH = $this->isAdminRH($me);

// Usar:
$isAdminRH = Auth::user()->isRh();
```

---

#### `getTechnicalArea()` usa `str_contains` sin word boundary

**Problema**: `str_contains($pos, 'ti')` puede coincidir con cualquier puesto que contenga las letras "ti" juntas (ej: "Asis**ti**r", "Capaci**taci**ón"). Si bien en la práctica actual los puestos de la empresa no presentan este problema, es una bomba de tiempo ante nuevos puestos.

**Solución recomendada a largo plazo**: Agregar un campo `area_evaluacion` a la tabla `empleados` o `departamentos`, eliminando la necesidad de inferir el área por palabras clave del puesto.

---

#### `update()` en backend no revalida `edit_count`

**Problema**: Si alguien modifica manualmente la URL y llama `PUT /capital-humano/evaluacion/{id}` con `edit_count >= 1`, el controlador lo procesa igualmente. Solo la UI bloquea la edición.

**Solución**: Agregar validación en backend en `update()`:
```php
if ($evaluacion->edit_count >= 1 && !$this->isAdminRH(Auth::user()->empleado)) {
    return back()->with('error', 'Esta evaluación ya fue finalizada.');
}
```

---

*Documentación generada el 27 de Abril de 2026 — ERP Estrategia e Innovación*
