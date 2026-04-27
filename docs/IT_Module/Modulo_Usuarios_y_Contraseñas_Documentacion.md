# Módulo IT — Usuarios y Contraseñas (Credenciales de Equipos) — Documentación Maestra
> **ERP Estrategia e Innovación** · Versión documental: Abril 2026  
> Audiencia: Administradores de TI, desarrolladores de seguridad

---

## Tabla de Contenido

1. [Visión General](#1-visión-general)
2. [Arquitectura del Sistema de Credenciales](#2-arquitectura-del-sistema-de-credenciales)
3. [Modelo de Datos — Credenciales](#3-modelo-de-datos--credenciales)
4. [Cifrado de Contraseñas](#4-cifrado-de-contraseñas)
5. [Referencia de Métodos — `CredencialEquipoController`](#5-referencia-de-métodos--credencialequipocontroller)
6. [Flujo de Alta de Credencial (Equipo Principal)](#6-flujo-de-alta-de-credencial-equipo-principal)
7. [Flujo de Actualización — Sincronización con AuditoriaActivos](#7-flujo-de-actualización--sincronización-con-auditoriaactivos)
8. [Carta Responsiva IT](#8-carta-responsiva-it)
9. [Exportación a Excel — Estructura y Seguridad](#9-exportación-a-excel--estructura-y-seguridad)
10. [Equipos Secundarios](#10-equipos-secundarios)
11. [Acceso de Solo Lectura para RH](#11-acceso-de-solo-lectura-para-rh)
12. [Referencia de Rutas](#12-referencia-de-rutas)
13. [Guía de Mantenimiento del Módulo](#13-guía-de-mantenimiento-del-módulo)

---

## 1. Visión General

El submódulo de **Usuarios y Contraseñas** (rutas `/admin/credenciales`) gestiona el expediente de credenciales de cada equipo asignado a un usuario del ERP. Incluye:

- **Usuario y contraseña del sistema operativo** (Windows/Linux) → cifrados con `Crypt::encryptString()`
- **Cuentas de correo del equipo** (`EquipoCorreo`) → cifradas con `Crypt::encryptString()`
- **Periféricos vinculados** (`EquipoPeriferico`) → sin contraseña
- **Carta responsiva IT** → PDF firmado digitalmente y guardado en el expediente del empleado
- **Exportación a Excel** → 3 hojas (Equipos, Correos, Periféricos) con contraseñas descifradas

Este submódulo es distinto del inventario físico (`/admin/activos`): mientras activos gestiona la existencia y asignación del dispositivo en AuditoriaActivos, este módulo gestiona **quién puede usarlo y con qué credenciales**.

### Relación con AuditoriaActivos

Cada registro `EquipoAsignado` tiene un campo `uuid_activos` que apunta a un `devices.uuid` en la BD externa. Al crear, actualizar o eliminar credenciales, el módulo sincroniza automáticamente el estado de asignación en AuditoriaActivos.

---

## 2. Arquitectura del Sistema de Credenciales

```
┌──────────────────────────────────────────────────────────────────┐
│            SISTEMA DE USUARIOS Y CONTRASEÑAS IT                  │
│                                                                  │
│  Capa de BD (ERP principal)                                      │
│  ─────────────────────────                                       │
│  users ─── EquipoAsignado ─┬─ EquipoCorreo                       │
│                             │    (correos + contraseñas cifradas) │
│                             └─ EquipoPeriferico                   │
│                                  (UUID → AuditoriaActivos)        │
│                                                                  │
│  CredencialEquipoController                                      │
│  ──────────────────────────                                      │
│  index()           → Listado paginado con buscador               │
│  store()           → Alta de equipo principal con transacción    │
│  show()            → Detalle con equipos secundarios             │
│  update()          → Actualización con sync Activos              │
│  destroy()         → Baja con devolución en Activos              │
│  storeSecundario() → Alta de equipo secundario                   │
│  destroySecundario() → Baja de equipo secundario                 │
│  cartaResponsiva() → Vista para imprimir/firmar PDF              │
│  guardarCartaResponsiva() → Guarda PDF en expediente del empleado│
│  exportExcel()     → XLSX con 3 hojas (contraseñas descifradas)  │
│                                                                  │
│  Capa de sync (AuditoriaActivos)                                 │
│  ─────────────────────────────                                   │
│  assignDeviceInActivos() ← al crear/agregar periférico           │
│  returnDeviceInActivos() ← al eliminar credencial/periférico     │
└──────────────────────────────────────────────────────────────────┘
```

---

## 3. Modelo de Datos — Credenciales

### `EquipoAsignado` — Tabla `it_equipos_asignados`

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | `bigint` PK | |
| `user_id` | `bigint` FK → `users.id` | Usuario ERP responsable del equipo |
| `uuid_activos` | `varchar(255)` unique | UUID del dispositivo en AuditoriaActivos |
| `nombre_equipo` | `varchar(255)` | Nombre descriptivo del equipo |
| `modelo` | `varchar(255)` nullable | Modelo del equipo |
| `numero_serie` | `varchar(255)` nullable | Número de serie |
| `photo_id` | `integer` nullable | ID de la foto en AuditoriaActivos |
| `nombre_usuario_pc` | `varchar(255)` | Nombre de usuario de Windows/SO |
| `contrasena_equipo` | `varchar` | **Contraseña cifrada** con `Crypt::encryptString()` |
| `notas` | `text` nullable | Notas adicionales |
| `es_principal` | `boolean` nullable | `true` = equipo principal; `false` = secundario; `null` = principal (legado) |

### `EquipoCorreo` — Tabla `it_equipos_correos`

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | `bigint` PK | |
| `equipo_asignado_id` | `bigint` FK → `it_equipos_asignados.id` | Equipo al que pertenece |
| `correo` | `varchar(255)` | Dirección de correo |
| `contrasena_correo` | `varchar` nullable | **Contraseña cifrada** con `Crypt::encryptString()`; `null` si no aplica |

### `EquipoPeriferico` — Tabla `it_equipos_perifericos`

| Campo | Tipo | Descripción |
|---|---|---|
| `id` | `bigint` PK | |
| `equipo_asignado_id` | `bigint` FK → `it_equipos_asignados.id` | Equipo al que pertenece |
| `uuid_activos` | `varchar` | UUID del periférico en AuditoriaActivos |
| `nombre` | `varchar(255)` | Nombre descriptivo (ej: "Mouse inalámbrico HP") |
| `tipo` | `varchar(255)` nullable | Tipo de periférico (ej: "Mouse", "Teclado", "Monitor") |
| `numero_serie` | `varchar(255)` nullable | Número de serie del periférico |

### `CredencialEquipo` — Tabla `it_credenciales_equipos` (Legado)

Modelo legado anterior al sistema de `EquipoAsignado`. Contiene credenciales de sistema en un formato diferente. No se crea en operaciones nuevas; se mantiene para datos históricos.

| Campo | Tipo |
|---|---|
| `user_id` | FK → `users.id` |
| `nombre_usuario_sistema` | varchar |
| `contrasena` | varchar cifrado |
| `equipo_asignado` | varchar |
| `tipo_equipo` | enum: `Laptop`, `Desktop`, `Tablet`, `Servidor`, `Otro` |
| `numero_serie` | varchar nullable |
| `sistema_operativo` | varchar nullable |
| `observaciones` | text nullable |

---

## 4. Cifrado de Contraseñas

### `EquipoAsignado.contrasena_equipo`

```php
// Mutador — se ejecuta automáticamente al asignar el atributo
public function setContrasenaEquipoAttribute(string $value): void
{
    $this->attributes['contrasena_equipo'] = Crypt::encryptString($value);
}

// Accessor de solo lectura — no es un cast automático, se accede explícitamente
public function getContrasenaDescifradaAttribute(): string
{
    try {
        return Crypt::decryptString($this->attributes['contrasena_equipo']);
    } catch (\Exception $e) {
        return '';  // Silencio si la clave cambió o el valor está corrupto
    }
}
```

**Uso**:
```php
// Al guardar (el mutador cifra automáticamente):
$equipo->contrasena_equipo = 'MiContraseña123';

// Al leer la contraseña en texto claro:
echo $equipo->contrasena_descifrada;  // MiContraseña123

// NUNCA acceder directamente (retorna el valor cifrado):
echo $equipo->contrasena_equipo;  // eyJpdiI6Ik...
```

### `EquipoCorreo.contrasena_correo`

```php
public function setContrasenaCorreoAttribute(?string $value): void
{
    $this->attributes['contrasena_correo'] = $value ? Crypt::encryptString($value) : null;
}

public function getContrasenaDescifradaAttribute(): string
{
    if (!$this->attributes['contrasena_correo']) {
        return '';
    }
    try {
        return Crypt::decryptString($this->attributes['contrasena_correo']);
    } catch (\Exception $e) {
        return '';
    }
}
```

### Implicaciones de la clave de cifrado

El cifrado usa `APP_KEY` de `.env`. **Si se rota `APP_KEY`, todas las contraseñas almacenadas quedarán indescifrables**. Antes de rotar la clave, exportar el Excel completo y descifrar todos los valores con la clave anterior.

---

## 5. Referencia de Métodos — `CredencialEquipoController`

**Archivo**: `app/Http/Controllers/Sistemas_IT/CredencialEquipoController.php`

---

### `index(Request $request): View`

**Ruta**: `GET /admin/credenciales`

Listado paginado de equipos principales (`es_principal = true` o `es_principal IS NULL`).

El buscador filtra por: `nombre_equipo`, `modelo`, `numero_serie`, `nombre_usuario_pc`, nombre del `user`, o `user_id` de equipos secundarios que coincidan con el search.

Variables enviadas a la vista:

| Variable | Descripción |
|---|---|
| `$equipos` | Paginator de `EquipoAsignado` con `user`, `correos`, `perifericos` |
| `$secundarios` | Collection de equipos secundarios agrupada por `user_id` |
| `$usuarios` | Todos los usuarios `approved` para el selector |
| `$soloLectura` | `true` si la ruta es `/rh/activos/*` |
| `$usersConCarta` | Mapa `[user_id => user_id]` de usuarios que ya tienen carta responsiva firmada |

---

### `store(Request $request): JsonResponse`

**Ruta**: `POST /admin/credenciales`

Crea el expediente completo de un equipo principal en una transacción:

1. Valida y crea `EquipoAsignado` (el mutador cifra la contraseña)
2. Crea los `EquipoCorreo` asociados (contraseña cifrada)
3. Crea los `EquipoPeriferico` asociados
4. Si `assign_new = true`: llama `assignDeviceInActivos()` para el equipo principal en AuditoriaActivos
5. Para cada periférico: llama `assignDeviceInActivos()` (los periféricos siempre se asignan)

Retorna JSON `{ success: true, redirect: url }` o `{ success: false, message: error }` con código 500.

---

### `show(EquipoAsignado $credencial): View`

**Ruta**: `GET /admin/credenciales/{credencial}`

Muestra el detalle del equipo principal con:
- `$credencial` cargado con `user`, `correos`, `perifericos`
- `$equiposSecundarios` — otros equipos del mismo `user_id` donde `es_principal = false`
- `$esComputadora` — detectado via `getDeviceTypeByUuid()` en AuditoriaActivos; `true` si no se puede consultar

---

### `update(Request $request, EquipoAsignado $credencial): JsonResponse`

**Ruta**: `PUT /admin/credenciales/{credencial}`

Actualiza el equipo principal con sincronización parcial de AuditoriaActivos:

```
1. Detectar periféricos que se van a eliminar ($removedPers)
2. Detectar periféricos nuevos ($newPers)
3. Transacción BD (ERP):
   a. Actualizar EquipoAsignado (solo actualiza contrasena si se envió)
   b. Sync correos: delete los que no vienen, update los que sí, create nuevos
   c. Sync periféricos: delete los eliminados, create los nuevos
4. Fuera de la transacción:
   a. returnDeviceInActivos() para cada periférico eliminado
   b. assignDeviceInActivos() para cada periférico nuevo
```

> **Nota de seguridad**: La contraseña del equipo solo se actualiza si `$request->filled('contrasena_equipo')`. Si el campo viene vacío, se mantiene la contraseña anterior. Esto permite editar otros campos sin sobrescribir la contraseña.

---

### `destroy(EquipoAsignado $credencial): RedirectResponse`

**Ruta**: `DELETE /admin/credenciales/{credencial}`

Antes de eliminar el registro local, libera en AuditoriaActivos:

```
$credencial->load('perifericos')
→ returnDeviceInActivos($credencial->uuid_activos)  // equipo principal
→ foreach $credencial->perifericos:
      returnDeviceInActivos($per->uuid_activos)     // cada periférico
→ $credencial->delete()  // elimina también correos y periféricos por cascade
```

---

### `cartaResponsiva(User $user): View`

**Ruta**: `GET /admin/credenciales/carta-responsiva/{user}`

Vista imprimible que lista:
- Datos del usuario y su empleado
- Equipo principal (con correos y periféricos)
- Equipos secundarios (con correos y periféricos)
- Fecha de emisión de la carta

Esta vista es para imprimir o capturar como PDF desde el navegador.

---

### `guardarCartaResponsiva(Request $request, User $user): JsonResponse`

**Ruta**: `POST /admin/credenciales/carta-responsiva/{user}/guardar`

Recibe el PDF en formato base64 desde el frontend, lo valida (`%PDF` magic bytes) y lo guarda en el expediente del empleado como `EmpleadoDocumento`.

```php
$pdfContent = base64_decode(preg_replace('/^data:[^;]+;base64,/', '', $request->pdf_base64), true);

// Valida que sea PDF real
if ($pdfContent === false || ! str_starts_with($pdfContent, '%PDF')) {
    return response()->json(['success' => false, 'message' => 'El archivo PDF no es válido.'], 422);
}

$path = "expedientes/{$empleado->id}/carta-responsiva-{fecha}.pdf";
Storage::disk('local')->put($path, $pdfContent);

EmpleadoDocumento::create([
    'empleado_id' => $empleado->id,
    'nombre'      => 'Carta Responsiva IT — dd/mm/YYYY',
    'categoria'   => 'Sistema IT',
    'ruta_archivo' => $path,
]);
```

Máximo permitido: `max:10485760` (10 MB base64 ≈ 7.5 MB de PDF real).

---

## 6. Flujo de Alta de Credencial (Equipo Principal)

```
Admin abre /admin/credenciales → botón "Nuevo registro"
                 │
                 ▼
Modal de alta. El admin:
  1. Selecciona el usuario ERP
  2. Elige el equipo disponible del selector (se consulta vía
     GET /admin/activos-api/equipos-disponibles — retorna equipos disponibles)
  3. Llena nombre_usuario_pc y contrasena_equipo
  4. Opcionalmente agrega correos electrónicos con sus contraseñas
  5. Opcionalmente agrega periféricos del selector
  6. Marca si es una asignación nueva (assign_new = true)
                 │
                 ▼
POST /admin/credenciales
  body: {
    user_id: 42,
    uuid_activos: '550e8400-...',
    nombre_equipo: 'HP EliteBook 840 - Juan G.',
    modelo: 'EliteBook 840 G8',
    numero_serie: 'CNU1234567',
    nombre_usuario_pc: 'jgarcia',
    contrasena_equipo: 'Pass@2026!',  ← se cifra automáticamente
    assign_new: true,
    correos: [
      { correo: 'jgarcia@empresa.com', contrasena_correo: 'CorreoPass123' }
    ],
    perifericos: [
      { uuid: 'abc-...', nombre: 'Mouse HP', tipo: 'Mouse', serie: 'MX1234' }
    ]
  }
                 │
  Transacción:
  ├── EquipoAsignado::create(...)  ← contrasena cifrada por mutador
  ├── EquipoCorreo::create(...)    ← contrasena cifrada por mutador
  └── EquipoPeriferico::create(...)
                 │
  Fuera de transacción:
  ├── assignDeviceInActivos(uuid_activos, ...)  ← si assign_new=true
  └── assignDeviceInActivos(per.uuid, ...)      ← para cada periférico
                 │
  response()->json({ success: true, redirect: '/admin/credenciales/15' })
                 │
  Frontend redirige al detalle del nuevo registro
```

---

## 7. Flujo de Actualización — Sincronización con AuditoriaActivos

La sincronización de periféricos en `update()` es parcial y manual. El flujo detallado:

```
Admin edita el registro en /admin/credenciales/{id}

Situación antes de guardar:
  Periféricos existentes: [Mouse HP (id=5), Teclado (id=6), Monitor (id=7)]

Admin elimina Monitor y agrega Impresora

Petición PUT /admin/credenciales/{id}
body.perifericos = [
  { id: 5, uuid: 'mouse-uuid', nombre: 'Mouse HP' },   ← existente, se mantiene
  { id: 6, uuid: 'teclado-uuid', nombre: 'Teclado' },  ← existente, se mantiene
  { uuid: 'impresora-uuid', nombre: 'Impresora' }       ← nuevo (sin id)
]

Cálculo:
  keepPerIds = [5, 6]
  removedPers = [Monitor (id=7, uuid='monitor-uuid')]
  newPers = [{ uuid: 'impresora-uuid', nombre: 'Impresora' }]

Transacción ERP:
  DELETE FROM it_equipos_perifericos WHERE id = 7
  INSERT INTO it_equipos_perifericos (uuid='impresora-uuid', ...)

Fuera de transacción:
  returnDeviceInActivos('monitor-uuid')    ← libera monitor en AuditoriaActivos
  assignDeviceInActivos('impresora-uuid')  ← asigna impresora en AuditoriaActivos
```

---

## 8. Carta Responsiva IT

La carta responsiva es un documento legal que el empleado firma para reconocer la recepción de los equipos que se le asignan.

### Proceso de Firma

```
Admin abre GET /admin/credenciales/carta-responsiva/{user}
                 │
  Vista HTML de la carta con:
  - Datos del empleado y su departamento
  - Tabla de equipos asignados (principal + secundarios)
  - Cada equipo con: nombre, modelo, serie, usuario PC
  - Correos asignados (sin contraseñas — solo el correo)
  - Periféricos asignados
  - Campos de firma del empleado y del responsable de TI
  - Fecha de emisión: {{ $fechaCarta->format('d/m/Y') }}
                 │
  Admin imprime o captura como PDF (la vista NO muestra contraseñas)
                 │
  El empleado firma la carta física o el PDF digital
                 │
  Admin sube el PDF firmado al sistema:
  POST /admin/credenciales/carta-responsiva/{user}/guardar
  body: { pdf_base64: 'data:application/pdf;base64,....' }
                 │
  Sistema guarda en:
  storage/app/private/expedientes/{empleado.id}/carta-responsiva-{fecha}.pdf
                 │
  Se crea EmpleadoDocumento:
  { nombre: 'Carta Responsiva IT — 27/04/2026', categoria: 'Sistema IT' }
```

### Detección de carta existente en el listado

En `index()`, el controlador calcula `$usersConCarta`:

```php
$usersConCarta = Empleado::whereHas('documentos', function ($q) {
    $q->where('categoria', 'Sistema IT')
      ->where('nombre', 'like', 'Carta Responsiva IT%');
})
->join('users', 'users.id', '=', 'empleados.user_id')
->pluck('users.id')
->flip()
->all();
```

La vista usa `isset($usersConCarta[$equipo->user_id])` para mostrar un indicador visual de que el usuario ya tiene carta firmada.

---

## 9. Exportación a Excel — Estructura y Seguridad

**Ruta**: `GET /admin/credenciales/exportar-excel`  
**Librería**: `PhpOffice\PhpSpreadsheet`  
**Formato**: `application/vnd.openxmlformats-officedocument.spreadsheetml.sheet`  
**Nombre del archivo**: `credenciales-equipos-IT_{YYYY-MM-DD}.xlsx`

El archivo generado contiene **todas las contraseñas en texto claro** y debe manejarse con cuidado extremo.

### Estructura del Excel

**Hoja 1: Equipos** (fondo de cabecera azul marino `#1E3A5F`)

| Usuario | Email ERP | Tipo | Nombre Equipo | Modelo | No. Serie | Usuario PC | Contraseña Equipo | Notas |
|---|---|---|---|---|---|---|---|---|
| Juan García | jgarcia@erp.com | Principal | HP EliteBook 840 | G8 | CNU123 | jgarcia | ⬛⬛⬛ (fondo amarillo) | ... |

**Hoja 2: Correos de Email** (fondo verde oscuro `#1A4731`)

| Usuario | Email ERP | Equipo | Tipo Equipo | Correo | Contraseña Correo |
|---|---|---|---|---|---|
| Juan García | jgarcia@erp.com | HP EliteBook | Principal | jgarcia@empresa.com | ⬛⬛⬛ (fondo amarillo) |

**Hoja 3: Periféricos** (fondo morado `#4C1D95`)

| Usuario | Email ERP | Equipo Principal | Periférico | Tipo | No. Serie |
|---|---|---|---|---|---|
| Juan García | jgarcia@erp.com | HP EliteBook | Mouse HP | Mouse | MX1234 |

### Seguridad del Excel

- Las columnas de contraseña se marcan con fondo amarillo suave (`#FFFFF8DC`) para visibilidad visual, pero el valor está en texto claro.
- El archivo **no tiene protección de contraseña** a nivel Excel.
- Solo roles con acceso al panel admin pueden ejecutar la ruta.
- **Recomendación**: Restringir el acceso a esta ruta solo al administrador de TI principal; agregar middleware adicional si es necesario.

---

## 10. Equipos Secundarios

Un usuario puede tener más de un equipo asignado. El equipo adicional se llama **equipo secundario** (`es_principal = false`).

### Casos de uso

- Laptop personal + equipo de escritorio en sala de juntas
- Equipo propio del empleado + equipo de un cliente que administra

### Alta de Equipo Secundario

**Ruta**: `POST /admin/credenciales/{credencial}/secundarios`

Idéntico al `store()` del equipo principal, con dos diferencias:
1. `es_principal = false` en el registro creado
2. La nota incluye automáticamente el prefijo `[Equipo Secundario / Cliente]` si no viene indicado

### Eliminación de Equipo Secundario

**Ruta**: `DELETE /admin/credenciales/{credencial}/secundarios/{secundario}`

Validación: El `$secundario` debe pertenecer al mismo `user_id` que `$credencial` y tener `es_principal = false`. Si no, responde 403.

### Visualización

Los equipos secundarios se muestran en la vista `show` del equipo principal y en la vista de la carta responsiva. En el listado `index`, se cargan en `$secundarios` agrupados por `user_id`.

---

## 11. Acceso de Solo Lectura para RH

El módulo de Recursos Humanos puede acceder al inventario de credenciales en modo de solo lectura, via las rutas `/rh/activos/*`.

El middleware `soloLectura` se detecta en los controladores con:
```php
$soloLectura = request()->routeIs('rh.activos.*');
```

Cuando `$soloLectura = true`:
- La vista no muestra botones de edición, alta o eliminación
- Los campos de contraseña se ocultan o enmascaran
- No se puede acceder a la exportación de Excel (ruta protegida solo para admin)

---

## 12. Referencia de Rutas

**Middleware**: `auth`, `sistemas_admin` (excepto rutas de RH)

### Rutas de Credenciales (Admin IT)

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `GET` | `/admin/credenciales` | `credenciales.index` | Listado paginado |
| `POST` | `/admin/credenciales` | `credenciales.store` | Alta de equipo principal |
| `GET` | `/admin/credenciales/{credencial}` | `credenciales.show` | Detalle del expediente |
| `GET` | `/admin/credenciales/{credencial}/edit` | `credenciales.edit` | Redirige a `show` |
| `PUT` | `/admin/credenciales/{credencial}` | `credenciales.update` | Actualizar |
| `DELETE` | `/admin/credenciales/{credencial}` | `credenciales.destroy` | Eliminar |
| `GET` | `/admin/credenciales/exportar-excel` | `credenciales.export-excel` | Exportar XLSX |
| `GET` | `/admin/credenciales/carta-responsiva/{user}` | `credenciales.carta-responsiva` | Vista de carta |
| `POST` | `/admin/credenciales/carta-responsiva/{user}/guardar` | `credenciales.carta-responsiva.guardar` | Guardar PDF firmado |

### Rutas de Equipos Secundarios

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `POST` | `/admin/credenciales/{credencial}/secundarios` | `credenciales.secundarios.store` | Alta de equipo secundario |
| `DELETE` | `/admin/credenciales/{credencial}/secundarios/{secundario}` | `credenciales.secundarios.destroy` | Eliminar equipo secundario |

> **Orden en routes/web.php**: Las rutas estáticas (`exportar-excel`, `carta-responsiva`) se registran **antes** del `resource('credenciales')` para evitar que el parámetro `{credencial}` las capture.

---

## 13. Guía de Mantenimiento del Módulo

---

### 🔴 CRÍTICO: Rotación de `APP_KEY` inutiliza todas las contraseñas cifradas

`Crypt::encryptString()` usa la `APP_KEY` de `.env`. Si se rota esta clave (vía `php artisan key:generate`), todos los valores cifrados en `it_equipos_asignados.contrasena_equipo` e `it_equipos_correos.contrasena_correo` quedarán corruptos.

**Antes de rotar la clave**:
1. Exportar el Excel completo desde `/admin/credenciales/exportar-excel`.
2. Guardar el Excel en un lugar seguro con acceso restringido.
3. Rotar la clave.
4. Re-ingresar manualmente todas las contraseñas.

**Solución a largo plazo**: Implementar re-cifrado al rotar la clave (descifrar con la clave vieja, cifrar con la nueva en una migración).

---

### 🔴 CRÍTICO: El Excel contiene contraseñas en texto claro

El archivo generado por `exportExcel()` expone todas las contraseñas del sistema IT. Debe:
- No enviarse por correo sin cifrado adicional
- No subirse a almacenamiento compartido no cifrado
- Eliminarse del equipo local después de su uso
- Considerarse agregar autenticación adicional (pin, 2FA) para acceder a la ruta

---

### 🔴 CRÍTICO: La sincronización con AuditoriaActivos no es atómica

La transacción de ERP y las llamadas a `assignDeviceInActivos()`/`returnDeviceInActivos()` son operaciones separadas. Si la transacción ERP tiene éxito pero la sincronización con Activos falla (BD externa caída), el estado queda inconsistente.

**Detección**: Comparar el listado de `EquipoAsignado.uuid_activos` con las asignaciones activas en AuditoriaActivos periódicamente.

---

### 🟡 IMPORTANTE: Agregar un campo de credencial

Para añadir, por ejemplo, un campo `pin_telefono` a `EquipoAsignado`:

1. Migration: `ALTER TABLE it_equipos_asignados ADD COLUMN pin_telefono VARCHAR(500) NULL;`
2. Añadir al `$fillable` del modelo.
3. Si debe cifrarse: añadir mutador `setPinTelefonoAttribute()` y accessor `getPinTelefonoDescifradoAttribute()` siguiendo el mismo patrón de `contrasena_equipo`.
4. Añadir validación en `CredencialEquipoController::store()` y `update()`.
5. Mostrar en la vista `show` y en el formulario del modal.
6. Añadir columna en la hoja Excel de `exportExcel()`.

---

### 🟡 IMPORTANTE: Cambiar el nombre de categoría de la carta responsiva

El sistema detecta si ya existe carta con `where('categoria', 'Sistema IT')->where('nombre', 'like', 'Carta Responsiva IT%')`. Si se cambia el texto en `guardarCartaResponsiva()`, la detección en `index()` dejará de funcionar para los registros anteriores.

Si se necesita renombrar, actualizar **ambas** líneas en el controlador o migrar los registros existentes en BD.

---

### 🟢 SEGURO: Cambiar el diseño de la carta responsiva

La carta responsiva es la vista `admin.credenciales.carta-responsiva`. Editar el template Blade directamente. Las variables disponibles son `$user`, `$equipoPrincipal`, `$equiposSecundarios`, `$fechaCarta`. No se requieren cambios en el controlador.

---

### 🟢 SEGURO: Agregar un nuevo tipo de periférico

Los tipos de periférico (`Mouse`, `Teclado`, `Monitor`, etc.) son texto libre en `EquipoPeriferico.tipo`. No hay una validación de enum. Se puede añadir cualquier valor desde el formulario sin cambios en el backend.

---

### Checklist de deploy para cambios en Credenciales

- [ ] ¿Se añadió un campo con contraseña? Verificar que tiene mutador de cifrado y que nunca se serializa en JSON sin el accessor descifrado.
- [ ] ¿Se modificó `exportExcel()`? Probar que las 3 hojas se generan correctamente y que las contraseñas aparecen descifradas.
- [ ] ¿Se cambió el nombre o categoría en `guardarCartaResponsiva()`? Actualizar la query de detección en `index()`.
- [ ] ¿Se añadieron rutas estáticas nuevas (como `exportar-excel`)? Verificarlas **antes** del `Route::resource()` en `web.php`.
- [ ] ¿Se modificó la validación del base64 del PDF? Verificar que sigue validando `str_starts_with($pdfContent, '%PDF')`.

---

*Documentación generada el 27 de Abril de 2026 — ERP Estrategia e Innovación*
