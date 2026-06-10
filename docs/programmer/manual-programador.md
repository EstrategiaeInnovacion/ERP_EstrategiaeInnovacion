# Manual de Programador — ERP Estrategia e Innovación

> **Versión:** 1.0  
> **Última actualización:** 04/06/2026  
> **Proyecto:** ERP Corporativo E&I  
> **Stack:** Laravel 12 · PHP 8.2 · SQLite · Tailwind CSS 3 · Alpine.js 3 · Vite 6

---

## 1. Introducción y Visión General

### 1.1 Propósito del Sistema

ERP Estrategia e Innovación es un sistema de planificación de recursos empresariales modular que centraliza la gestión de:

- **Recursos Humanos** — Expedientes, asistencias (reloj checador), capacitación, evaluaciones, días festivos, recordatorios, jerarquía organizacional.
- **Logística y Aduanas** — Clientes, matriz de seguimiento, matriz de apoyo operativo, aduanas, pedimentos, reportes.
- **Legal** — Categorías legales, programas, matriz de consultas, digitalización VUCEM.
- **Sistemas IT** — Tickets de soporte, inventario de activos, mantenimiento de equipos, credenciales, usuarios help desk, perfiles de cómputo.
- **Administración** — Clientes, perfiles de cliente.
- **Proyectos y Actividades** — Gestión de proyectos, asignación de actividades, reportes.
- **Anexo 24 / Post-Operaciones / Auditoría** — Dashboards informativos con vistas readonly de clientes.

### 1.2 Glosario

| Término | Definición |
|---|---|
| **ERP** | Enterprise Resource Planning |
| **RH** | Recursos Humanos / Capital Humano |
| **IT** | Tecnologías de la Información |
| **n8n** | Plataforma de automatización de workflows (webhooks) |
| **Activos DB** | Base de datos SQLite secundaria para auditoría de equipos IT |
| **Sanctum** | Sistema de autenticación API de Laravel (token-based) |
| **Microsoft Graph** | API de Microsoft para envío de correos corporativos |
| **Browsershot** | Herramienta de captura de pantalla (headless Chromium) |
| **VUCEM** | Ventanilla Única de Comercio Exterior (módulo Legal) |
| **MFO** | Matriz de Seguimiento (Logística) |
| **FIEL** | Firma Electrónica Avanzada (Legal) |
| **Pedimento** | Documento aduanal (Logística) |

### 1.3 Stack Tecnológico

| Componente | Tecnología |
|---|---|
| **Backend** | Laravel 12 · PHP 8.2 |
| **Base de datos** | SQLite (principal), SQLite (secundaria Activos) |
| **Frontend** | Tailwind CSS 3 · Alpine.js 3 |
| **Build** | Vite 6 + Laravel Vite Plugin |
| **Auth Web** | Session-based con middleware por área |
| **Auth API** | Laravel Sanctum (tokens) |
| **Tareas programadas** | Laravel Schedule (consola) |
| **Colas** | Database queue (jobs table) |
| **Caché** | Database cache (cache table) |
| **PDF** | Browsershot + Ghostscript + QPDF |
| **Office** | PhpOffice PhpSpreadsheet, PhpWord |
| **Correo** | Microsoft Graph API (Client Credentials OAuth2) |
| **Monitoreo** | Laravel Telescope |
| **Webhooks** | n8n (tickets, logística) |

### 1.4 Dependencias Externas (composer.json)

| Paquete | Propósito |
|---|---|
| `laravel/framework` | Framework base |
| `laravel/sanctum` | API auth |
| `laravel/telescope` | Debugging/monitoreo |
| `spatie/laravel-browsershot` | Capturas PDF desde HTML |
| `spatie/laravel-ignition` | Error pages |
| `phpoffice/phpspreadsheet` | Lectura/escritura Excel |
| `phpoffice/phpword` | Generación documentos Word |
| `barryvdh/laravel-dompdf` | Renderizado PDF alternativo |
| `setasign/fpdf` / `setasign/fpdi` | Manipulación PDF |
| `phpcfdi/credentials` | Lectura de certificados FIEL (e.firma) |
| `robrichards/xmlseclibs` | Firmas XML |
| `microsoft/microsoft-graph` | SDK Graph API |
| `guzzlehttp/guzzle` | HTTP client |

---

## 2. Arquitectura del Sistema

### 2.1 Diagrama de Capas

```
┌──────────────────────────────────────────────────────────────┐
│                     Navegador Web (Blade)                     │
│           Tailwind CSS · Alpine.js · Flatpickr · jsPDF        │
└──────────────────────┬───────────────────────────────────────┘
                       │ HTTP / Session
┌──────────────────────▼───────────────────────────────────────┐
│                    Middleware Stack                            │
│  guest → auth → verified →                                    │
│  area.rh / area.logistica / area.legal / sistemas_admin       │
│  admin / api.key (API)                                        │
└──────────────────────┬───────────────────────────────────────┘
                       │
┌──────────────────────▼───────────────────────────────────────┐
│                    Controllers (47)                           │
│  Web: Auth, RH, Logística, Legal, Sistemas_IT, Admin,        │
│       Proyectos, Actividades, Digitalización, etc.            │
│  API: AuthController, UserController (Sanctum)                │
└──────────────────────┬───────────────────────────────────────┘
                       │
┌──────────────────────▼───────────────────────────────────────┐
│                    Services (11)                              │
│  ActivosDbService, MicrosoftGraphMailService,                 │
│  PDF processing, Logística services, IT services              │
└──────────────────────┬───────────────────────────────────────┘
                       │
┌──────────────────────▼───────────────────────────────────────┐
│                    Models (50) · Eloquent ORM                 │
│  User, Empleado, Cliente, Ticket, Actividad, Projecto,       │
│  InventoryItem, ComputerProfile, Credential, etc.             │
└──────────────────────┬───────────────────────────────────────┘
                       │
┌──────────────────────▼───────────────────────────────────────┐
│                    Base de Datos SQLite                       │
│  Principal: database/database.sqlite                          │
│  Activos: (ruta configurable, BD externa)                     │
└───────────────────────────────────────────────────────────────┘
```

### 2.2 Flujo de Autenticación

1. Usuario visita `/login` → Sistema_IT/auth/login.blade.php
2. Credenciales validadas contra `users` table
3. Si `status !== 'approved'`, denegado
4. Sesión iniciada → redirección según `getPanelInfo()`:
   - Admin → `/admin`
   - RH → `/recursos-humanos`
   - Logística → `/logistica`
   - Legal → `/legal`
   - Otros → `/dashboard`
5. Middleware por área verifica posición/departamento en cada请求

### 2.3 Flujo API (Sanctum)

```
Cliente externo ──POST /api/v1/auth/login──→ Token
Cliente externo ──GET  /api/v1/users (X-API-Key)──→ Lista usuarios
Cliente externo ──GET  /api/v1/auth/me (Bearer Token)──→ Perfil
```

- Rutas públicas con `X-API-Key` via `CheckApiKey` middleware
- Rutas protegidas con `auth:sanctum`
- Tokens expire-ables (configurable)

### 2.4 Flujo de Tickets (Soporte IT)

```
Usuario ──Abre ticket──→ tickets (status: abierto)
  │
  ├── Admin IT asigna ──→ en_proceso
  ├── Admin IT cierra ──→ cerrado
  ├── Programación mantenimiento ──→ maintenance_slots
  └── Notificaciones vía n8n webhook
```

### 2.5 Flujo de Mantenimiento de Equipos

```
Admin IT ──Crea slot──→ maintenance_slots (fecha/hora/cupo)
Usuario ──Reserva slot──→ maintenance_bookings
  │
  ├── Vinculado a ticket (ticket_id)
  ├── Capacidad validada (booked_count < capacity)
  └── ComputerProfile actualizado (last_maintenance_at)
```

---

## 3. Modelo de Datos

### 3.1 Convenciones

- **Timestamps**: `created_at`, `updated_at` en todas las tablas
- **Soft Deletes**: No implementado globalmente
- **Foreign Keys**: Nombradas `{tabla}_id`, con `cascade` o `set null` en delete
- **Índices**: `index` en columnas de búsqueda frecuente, `unique` en campos lógicos (email, folio, códigos)
- **Cast de fechas**: `date` o `datetime` según necesidad
- **JSON**: Columnas `longtext` con contenido JSON (checadas, imágenes, componentes)

### 3.2 Tablas Core (Sistema)

#### `users`
| Columna | Tipo | Restricciones |
|---|---|---|
| id | integer | PK, autoincrement |
| name | string | required |
| email | string | unique |
| role | enum('user','admin') | default 'user' |
| status | enum('pending','approved','rejected') | default 'pending' |
| approved_at | datetime | nullable |
| rejected_at | datetime | nullable |
| email_verified_at | datetime | nullable |
| password | string | hashed |
| remember_token | string | nullable |

#### `empleados`
| Columna | Tipo | Restricciones |
|---|---|---|
| id | integer | PK |
| user_id | integer | FK → users, unique, cascade |
| nombre | string | |
| correo | string | index |
| area | string | |
| es_activo | boolean | |
| id_empleado | string(30) | |
| subdepartamento_id | integer | FK → subdepartamentos, set null |
| posicion | string | |
| telefono | string | |
| correo_personal | string | |
| foto_path | string | nullable |
| direccion | text | nullable |
| fecha_nacimiento | date | nullable |
| fecha_ingreso | date | nullable |
| fecha_inicio_contrato | date | nullable |
| fecha_fin_contrato | date | nullable |
| tipo_contrato | string | nullable |
| rfc | string | nullable |
| curp | string | nullable |
| nss | string | nullable |
| datos_emergencia | text | nullable |

#### `subdepartamentos`
| Columna | Tipo |
|---|---|
| id | integer PK |
| area | string |
| nombre | string |
| activo | boolean |
| Unique | (area, nombre) |

#### `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`
Tablas estándar de Laravel para sesiones, caché y jobs.

### 3.3 Tablas RH (Recursos Humanos)

#### `asistencias`
| Columna | Tipo |
|---|---|
| id | integer PK |
| empleado_no | string (index) |
| nombre | string (index) |
| fecha | date (index) |
| entrada | datetime (nullable) |
| salida | datetime (nullable) |
| checadas | longtext (JSON) |
| empleado_id | integer FK → empleados (set null) |

#### `aviso_asistencias`
| Columna | Tipo |
|---|---|
| id | integer PK |
| empleado_id | integer FK |
| enviado_por | integer FK → users |
| tipo | string |
| mensaje | text |
| periodo | string |
| cantidad_incidencias | integer |
| leido | boolean |
| leido_at | datetime (nullable) |

#### `empleado_documentos`
| Columna | Tipo |
|---|---|
| id | integer PK |
| empleado_id | integer FK |
| tipo | string |
| nombre | string |
| ruta | string |
| observaciones | text (nullable) |

#### `empleados_baja`
| Columna | Tipo |
|---|---|
| id | integer PK |
| empleado_id | integer FK |
| fecha_baja | date |
| motivo | text |
| observaciones | text (nullable) |

#### `capacitacion` / `capacitacion_empleados`
| Columna | Descripción |
|---|---|
| capacitacion.id | PK |
| capacitacion.tema, fecha, hora, instructor, lugar, estatus, observaciones | Evento de capacitación |
| capacitacion_empleados.empleado_id | FK → empleados |
| capacitacion_empleados.capacitacion_id | FK → capacitacion |
| capacitacion_empleados.asistio | boolean |

#### `evaluacion` / `evaluacion_empleados` / `evaluacion_categorias`
| Tabla | Propósito |
|---|---|
| evaluacion | Plantillas de evaluación (tipo, periodo, estatus) |
| evaluacion_empleados | Asignación empleado-evaluación (calificación, respuestas JSON) |
| evaluacion_categorias | Categorías/preguntas dentro de cada evaluación |

#### `dias_festivos`
| Columna | Tipo |
|---|---|
| id | integer PK |
| fecha | date |
| descripcion | string |
| es_reciproco | boolean |
| activo | boolean |

#### `recordatorios`
| Columna | Tipo |
|---|---|
| id | integer PK |
| titulo | string |
| descripcion | text (nullable) |
| tipo | string |
| fecha_recordatorio | datetime |
| fecha_vencimiento | datetime (nullable) |
| completado | boolean |
| empleado_id | integer FK → empleados (cascade) |
| creado_por | integer FK → users |

### 3.4 Tablas Sistemas IT

#### `tickets`
| Columna | Tipo |
|---|---|
| id | integer PK |
| folio | string (unique) |
| nombre_solicitante | string |
| correo_solicitante | string |
| nombre_programa | string (nullable) |
| descripcion_problema | text |
| imagenes | text (nullable) |
| estado | enum('abierto','en_proceso','cerrado') |
| closed_by_user | boolean |
| is_read | boolean |
| user_has_updates | boolean |
| tipo_problema | enum('software','hardware','mantenimiento') |
| prioridad | enum('baja','media','alta','critica') |
| user_id | integer FK → users |
| maintenance_slot_id | integer FK → maintenance_slots |
| computer_profile_id | integer FK → computer_profiles |
| +10 campos de equipo, fechas, notificaciones |

#### `maintenance_slots`
| Columna | Tipo |
|---|---|
| id | integer PK |
| date | date |
| start_time | time |
| end_time | time |
| capacity | integer |
| booked_count | integer (default 0) |
| is_active | boolean |
| Unique | (date, start_time, end_time) |

#### `maintenance_bookings`
| Columna | Tipo |
|---|---|
| id | integer PK |
| maintenance_slot_id | integer FK (cascade) |
| ticket_id | integer FK (cascade) |
| additional_details | text (nullable) |

#### `inventory_items`
| Columna | Tipo |
|---|---|
| id | integer PK |
| codigo_producto | string (index) |
| identificador | string |
| nombre | string |
| categoria | string |
| marca | string |
| modelo | string |
| numero_serie | string |
| estado | enum('disponible','prestado','mantenimiento','reservado','danado') |
| es_funcional | boolean |
| ubicacion | string |
| descripcion_general | text (nullable) |
| notas | text (nullable) |

#### `computer_profiles`
| Columna | Tipo |
|---|---|
| id | integer PK |
| identifier | string |
| brand | string |
| model | string |
| disk_type | string |
| ram_capacity | string |
| battery_status | string |
| aesthetic_observations | text |
| replacement_components | longtext (JSON) |
| last_maintenance_at | datetime (nullable) |
| is_loaned | boolean |
| loaned_to_name | string (nullable) |
| loaned_to_email | string (nullable) |
| last_ticket_id | integer FK → tickets (set null) |

#### `blocked_emails`
| Columna | Tipo |
|---|---|
| id | integer PK |
| email | string (unique) |
| reason | text (nullable) |
| blocked_by | integer FK → users (set null) |

#### `help_sections`
| Columna | Tipo |
|---|---|
| id | integer PK |
| title | string |
| content | text |
| section_order | integer |
| is_active | boolean |
| images | longtext (JSON) |

#### `credenciales`
| Columna | Tipo |
|---|---|
| id | integer PK |
| titulo | string |
| username | string |
| password | string (encrypted) |
| url | string (nullable) |
| notas | text (nullable) |
| empleado_asignado | integer FK → empleados |

### 3.5 Tablas Logística

#### `logistica_clientes`
| Columna | Tipo |
|---|---|
| id | integer PK |
| cliente | string |
| tipo_operacion | string |
| tipo_servicio | string |
| direccion | text |
| contacto | string |
| email | string |
| activo | boolean |

#### `logistica_matriz_seguimiento` (MFO)
| Columna | Tipo |
|---|---|
| id | integer PK |
| cliente_id | integer FK |
| fecha_ingreso | date |
| tipo_operacion | string |
| tipo_servicio | string |
| estatus | string |
| pedimento | string |
| area_asignada | string |
| usuario_asignado | string |
| observaciones | text |

#### `logistica_matriz_apoyo`
| Columna | Tipo |
|---|---|
| id | integer PK |
| cliente_id | integer FK |
| pedimento | string |
| gasto_real | decimal |
| estatus_pago | string |
| notas | text |

### 3.6 Tablas Legal

#### `legal_categorias`
| Columna | Tipo |
|---|---|
| id | integer PK |
| nombre | string |
| descripcion | text (nullable) |
| activo | boolean |

#### `legal_programas`
| Columna | Tipo |
|---|---|
| id | integer PK |
| categoria_id | integer FK |
| nombre | string |
| descripcion | text |
| fecha_inicio | date |
| fecha_fin | date (nullable) |
| estatus | string |
| activo | boolean |

#### `legal_matriz_consulta`
| Columna | Tipo |
|---|---|
| id | integer PK |
| programa_id | integer FK |
| cliente_id | integer FK |
| consulta | text |
| respuesta | text (nullable) |
| fecha_consulta | datetime |
| fecha_respuesta | datetime (nullable) |
| estatus | string |

#### `legal_digitalizacion`
| Columna | Tipo |
|---|---|
| id | integer PK |
| pedimento | string |
| nombre_archivo | string |
| ruta_archivo | string |
| fecha_digitalizacion | datetime |
| empleado_id | integer FK |

### 3.7 Tablas Proyectos y Actividades

#### `proyectos`
| Columna | Tipo |
|---|---|
| id | integer PK |
| nombre | string |
| descripcion | text (nullable) |
| fecha_inicio | date (nullable) |
| fecha_fin | date (nullable) |
| estatus | string |
| responsable_id | integer FK → empleados |
| cliente_id | integer FK → clientes |

#### `actividades`
| Columna | Tipo |
|---|---|
| id | integer PK |
| nombre_actividad | string |
| proyecto_id | integer FK → proyectos |
| fecha_compromiso | date (nullable) |
| area | string (nullable) |
| cliente | string (nullable) |
| prioridad | string (nullable) |
| tipo_actividad | string (nullable) |
| hora_inicio | time (nullable) |
| hora_fin | time (nullable) |
| comentarios | text (nullable) |
| estatus | string (nullable) |
| asignado_a | integer FK → empleados (nullable) |
| completado_en | datetime (nullable) |
| completado_por | integer FK → users (nullable) |

### 3.8 Tablas Administración

#### `clientes`
| Columna | Tipo |
|---|---|
| id | integer PK |
| nombre | string |
| rfc | string (nullable) |
| direccion | text (nullable) |
| contacto | string (nullable) |
| email | string (nullable) |
| telefono | string (nullable) |
| activo | boolean |

#### `perfiles_cliente`
| Columna | Tipo |
|---|---|
| id | integer PK |
| cliente_id | integer FK |
| tipo_perfil | string |
| datos | longtext (JSON) |

---

## 4. Guía de Instalación y Configuración

### 4.1 Requisitos del Sistema

| Herramienta | Versión Mínima |
|---|---|
| PHP | 8.2+ |
| Composer | 2.x |
| Node.js | 20+ |
| NPM | 10+ |
| SQLite | 3.x (incluido con PHP) |
| Git | Cualquier versión moderna |
| Ghostscript | Opcional (PDF tools) |
| QPDF | Opcional (PDF tools) |
| Chromium | Opcional (Browsershot) |

### 4.2 Instalación Paso a Paso

```bash
# 1. Clonar repositorio
git clone <repo-url> erp
cd erp

# 2. Instalar dependencias PHP
composer install

# 3. Instalar dependencias frontend
npm install

# 4. Configurar entorno
copy .env.example .env
# Editar .env con datos reales

# 5. Generar APP_KEY
php artisan key:generate

# 6. Crear base de datos SQLite
# Asegurar que database/database.sqlite existe (o crearlo manualmente)
New-Item -ItemType File -Path database/database.sqlite -Force

# 7. Ejecutar migraciones
php artisan migrate

# 8. Poblar datos iniciales (si existe)
php artisan db:seed

# 9. Compilar assets
npm run build

# 10. Iniciar servidor de desarrollo
php artisan serve
npm run dev  # En otra terminal
```

### 4.3 Variables de Entorno Clave (.env)

```ini
# ── Generales ──
APP_NAME="ERP Estrategia e Innovación"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000

# ── Base de Datos ──
DB_CONNECTION=sqlite
# DB_DATABASE=/ruta/absoluta/database.sqlite (opcional)

# ── Sesión / Caché / Colas ──
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database

# ── Correo (Microsoft Graph) ──
MAIL_MAILER=microsoft-graph
MAIL_FROM_ADDRESS=soporte@ei.com
MAIL_FROM_NAME="ERP E&I"
MICROSOFT_GRAPH_CLIENT_ID=
MICROSOFT_GRAPH_TENANT_ID=
MICROSOFT_GRAPH_CLIENT_SECRET=
MICROSOFT_GRAPH_SENDER_EMAIL=

# ── Integraciones ──
N8N_WEBHOOK_URL=
N8N_LOGISTICA_WEBHOOK_URL=
API_KEY=  # Para consultas externas API
ACTIVOS_API_URL=
ACTIVOS_DB_PATH=

# ── PDF Tools ──
GHOSTSCRIPT_PATH=
QPDF_PATH=
PDFIMAGES_PATH=
IMAGEMAGICK_PATH=

# ── Soporte ──
SUPPORT_CONTACT_EMAIL=soporte@ei.com
SUPPORT_TEAMS_URL=

# ── RH ──
RH_ASISTENCIA_CC_DEFAULT=guillermo.aguilera@ei.com
RH_ASISTENCIA_CC_RRHH=karen.cruz@ei.com
RH_ASISTENCIA_USER_RRHH_EMAIL=karen.cruz@ei.com
```

### 4.4 Configuración de Base de Datos Secundaria (Activos)

El archivo `config/database.php` incluye una conexión adicional:

```php
'activos' => [
    'driver'    => 'sqlite',
    'url'       => env('ACTIVOS_DB_URL'),
    'database'  => env('ACTIVOS_DB_PATH', database_path('activos.sqlite')),
    'prefix'    => '',
],
```

### 4.5 Configuración de Microsoft Graph Mail

Registrar en `config/services.php`:

```php
'microsoft_graph' => [
    'client_id'     => env('MICROSOFT_GRAPH_CLIENT_ID'),
    'tenant_id'     => env('MICROSOFT_GRAPH_TENANT_ID'),
    'client_secret' => env('MICROSOFT_GRAPH_CLIENT_SECRET'),
    'sender_email'  => env('MICROSOFT_GRAPH_SENDER_EMAIL'),
],
```

El transport `microsoft-graph` se registra en `AppServiceProvider::boot()`:

```php
Mail::extend('microsoft-graph', function (array $config) {
    return new MicrosoftGraphTransport($config);
});
```

### 4.6 Configuración de PDF Tools

```php
// config/pdftools.php
return [
    'ghostscript'  => env('GHOSTSCRIPT_PATH', ''),
    'pdfimages'    => env('PDFIMAGES_PATH', ''),
    'qpdf'         => env('QPDF_PATH', ''),
    'imagemagick'  => env('IMAGEMAGICK_PATH', ''),
];
```

---

## 5. Estructura del Proyecto

```
ERP_EstrategiaeInnovacion/
│
├── app/
│   ├── Console/
│   │   └── Commands/           # 10 comandos Artisan
│   ├── Enums/                   # Enums PHP (estados, tipos)
│   ├── Exceptions/              # Excepciones personalizadas
│   ├── Http/
│   │   ├── Controllers/         # 47 controladores
│   │   │   ├── Admin/           # Admin dashboard
│   │   │   ├── Administracion/  # Clientes admin
│   │   │   ├── Auth/            # Login, registro
│   │   │   ├── IT/              # Tickets, mantenimiento, activos
│   │   │   ├── Legal/           # Dashboard legal, categorías, programas
│   │   │   ├── Logistica/       # Clientes, matrices, reportes
│   │   │   ├── Proyectos/       # Proyectos y actividades
│   │   │   ├── RH/              # Expedientes, asistencias, capacitación
│   │   │   └── API/             # Auth, User (Sanctum)
│   │   ├── Middleware/          # 10 middleware
│   │   └── Requests/            # Form requests
│   ├── Mail/                    # 3+ mailables
│   ├── Models/                  # ~50 modelos Eloquent
│   ├── Notifications/           # Notificaciones
│   ├── Observers/               # Eloquent observers
│   ├── Providers/               # AppServiceProvider, etc.
│   └── Services/                # 11 servicios
│
├── bootstrap/                   # Framework bootstrap
├── config/                      # Configuración (activos, rh, pdftools, support)
├── database/
│   ├── factories/               # Model factories
│   ├── migrations/              # 101 migraciones
│   └── seeders/                 # Database seeders
│
├── docs/                        # Documentación (59 archivos existentes)
│   ├── programmer/              # ← Este manual
│   ├── DIAGRAMA_BD.md           # Diagrama entidad-relación (Mermaid)
│   ├── controllers/             # Docs de controladores por módulo
│   └── ... (por módulo)
│
├── resources/
│   ├── css/
│   │   ├── app.css              # Tailwind base
│   │   ├── Recursos_Humanos/    # RH-specific styles
│   │   └── Logistica/           # Logística-specific styles
│   ├── js/
│   │   ├── app.js               # Alpine.js, Flatpickr, jsPDF
│   │   ├── Sistemas_IT/         # IT-specific JS
│   │   ├── Recursos_Humanos/    # RH-specific JS
│   │   └── Logistica/           # Logística-specific JS
│   └── views/
│       ├── layouts/             # erp.blade.php, master layouts
│       ├── components/          # x-nav-link, x-dropdown, etc.
│       ├── auth/                # (redirect to Sistemas_IT/auth/)
│       ├── Recursos_Humanos/    # 20+ vistas RH
│       ├── Logistica/           # 6 vistas Logística
│       ├── Legal/               # 4+ vistas Legal
│       ├── Sistemas_IT/         # 50+ vistas IT
│       ├── Administracion/      # 3 vistas Admin
│       ├── proyectos/           # 5+ vistas Proyectos
│       ├── activities/          # 2 vistas Actividades
│       ├── shared/              # Vistas compartidas (clientes-readonly)
│       └── vendor/              # Pagination, mail templates
│
├── routes/
│   ├── web.php                  # ~500 líneas, 9 grupos middleware
│   ├── api.php                  # 75 líneas, Sanctum + API Key
│   ├── auth.php                 # Auth routes (Laravel Breeze)
│   └── console.php              # 4 tareas programadas
│
├── storage/                     # Logs, framework, app
├── tests/                       # PHPUnit tests
├── vite.config.js               # Vite config con chunks
├── tailwind.config.js           # Tailwind config
├── composer.json
└── package.json
```

---

## 6. Referencia de Rutas

### 6.1 Rutas Públicas

| Método | URI | Controlador | Propósito |
|---|---|---|---|
| GET | `/` | WelcomeController | Página de inicio |
| GET | `/login` | AuthController@create | Formulario login |
| POST | `/login` | AuthController@store | Procesar login |
| GET | `/register` | AuthController@createRegister | Formulario registro |
| POST | `/register` | AuthController@storeRegister | Procesar registro |
| POST | `/logout` | AuthController@destroy | Cerrar sesión |
| GET | `/dashboard` | DashboardController | Dashboard genérico |

### 6.2 Rutas Generales (Autenticadas)

| Método | URI | Controlador | Propósito |
|---|---|---|---|
| GET | `/profile` | ProfileController | Editar perfil |
| PUT | `/profile` | ProfileController@update | Actualizar perfil |
| GET | `/digitalizacion` | DigitalizacionController | Digitalización VUCEM |
| POST | `/digitalizacion/upload` | DigitalizacionController@upload | Subir archivo |
| GET | `/activities` | ActividadController@index | Listar actividades |
| POST | `/activities/import` | ActividadController@import | Importar desde Excel |
| GET | `/proyectos` | ProyectoController@index | Listar proyectos |
| GET/POST | `/proyectos/*` | ProyectoController | CRUD proyectos |
| GET | `/ticket` | TicketController@misTickets | Tickets del usuario |
| POST | `/ticket` | TicketController@store | Crear ticket |
| GET | `/maintenance` | MaintenanceController@index | Slots de mantenimiento |
| POST | `/maintenance/book` | MaintenanceController@book | Reservar slot |
| GET | `/capacitacion` | CapacitacionController | Cursos disponibles |
| GET | `/capital-humano/evaluacion` | EvaluacionController | Evaluaciones |
| POST | `/api/notifications` | NotificationController | Notificaciones AJAX |

### 6.3 Rutas RH (Middleware: auth, area.rh)

| Método | URI | Propósito |
|---|---|---|
| GET | `/recursos-humanos` | Dashboard RH |
| GET | `/recursos-humanos/expedientes` | Lista expedientes |
| GET/POST | `/recursos-humanos/expedientes/*` | CRUD expedientes |
| GET | `/recursos-humanos/reloj-checador` | Reloj checador (asistencias) |
| GET | `/recursos-humanos/inventario-it` | Activos IT (solo lectura) |
| GET | `/recursos-humanos/evaluaciones` | Gestión evaluaciones |
| GET/POST | `/recursos-humanos/capacitacion` | Gestión capacitación |
| GET/POST | `/recursos-humanos/recordatorios` | Recordatorios |
| GET/POST | `/recursos-humanos/dias-festivos` | Días festivos |
| GET | `/recursos-humanos/jerarquia` | Organigrama |

### 6.4 Rutas Logística (Middleware: auth, area.logistica)

| Método | URI | Propósito |
|---|---|---|
| GET | `/logistica` | Dashboard Logística |
| GET | `/logistica/clientes` | Clientes logísticos |
| GET/POST | `/logistica/matriz-seguimiento` | Matriz de seguimiento MFO |
| GET/POST | `/logistica/matriz-apoyo` | Matriz de apoyo operativo |
| GET | `/logistica/reportes` | Reportes |
| POST | `/logistica/actualizar-status` | Actualizar estatus (vía comando) |

### 6.5 Rutas Legal (Middleware: auth, verified, area.legal)

| Método | URI | Propósito |
|---|---|---|
| GET | `/legal` | Dashboard Legal |
| GET/POST | `/legal/categorias` | Categorías legales |
| GET/POST | `/legal/programas` | Programas legales |
| GET/POST | `/legal/matriz-consulta` | Matriz de consultas |
| GET/POST | `/legal/digitalizacion` | Digitalización VUCEM |

### 6.6 Rutas Administración (Middleware: auth, verified, admin)

| Método | URI | Propósito |
|---|---|---|
| GET | `/administracion` | Dashboard Administración |
| GET/POST | `/administracion/clientes` | CRUD clientes |
| GET/POST | `/administracion/perfiles` | Perfiles de cliente |

### 6.7 Rutas Sistemas IT (Middleware: auth, verified, sistemas_admin)

| Método | URI | Propósito |
|---|---|---|
| GET | `/admin` | Dashboard IT Admin |
| GET | `/admin/tickets` | Tickets (todos) |
| PUT | `/admin/tickets/{id}` | Actualizar ticket |
| GET | `/admin/usuarios` | CRUD usuarios |
| GET/POST | `/admin/activos` | Inventario activos IT |
| GET/POST | `/admin/credenciales` | Credenciales |
| GET/POST | `/admin/help` | Secciones de ayuda |
| GET/POST | `/admin/mantenimiento` | Slots mantenimiento |
| GET/POST | `/admin/equipos` | Perfiles de cómputo |
| POST | `/admin/usuarios/aprobar` | Aprobar usuario |
| POST | `/admin/usuarios/rechazar` | Rechazar usuario |

### 6.8 Rutas API (Sanctum + API Key)

| Método | URI | Middleware | Propósito |
|---|---|---|---|
| GET | `/api/v1/users` | api.key | Lista usuarios activos (consulta externa) |
| POST | `/api/v1/auth/login` | — | Login público → token |
| POST | `/api/v1/auth/validate-token` | — | Validar token |
| POST | `/api/v1/auth/logout` | auth:sanctum | Revocar token |
| GET | `/api/v1/auth/me` | auth:sanctum | Perfil del token |
| POST | `/api/v1/auth/refresh` | auth:sanctum | Refrescar token |

### 6.9 Rutas Anexo 24 / Post-Operaciones / Auditoría

| Método | URI | Middleware | Propósito |
|---|---|---|---|
| GET | `/anexo24` | area.anexo24 | Dashboard (clientes readonly) |
| GET | `/postoperaciones` | area.postoperaciones | Dashboard (clientes readonly) |
| GET | `/auditoria` | area.auditoria | Dashboard (clientes readonly) |

---

## 7. Referencia de Controladores

### 7.1 AuthController
**Ruta:** `app/Http/Controllers/Auth/`
| Método | Acción |
|---|---|
| `create()` | Mostrar formulario login |
| `store(Request)` | Validar credenciales, verificar `status=approved`, iniciar sesión |
| `createRegister()` | Formulario registro |
| `storeRegister(Request)` | Crear usuario con `status=pending` |
| `destroy()` | Cerrar sesión |

### 7.2 RH Controllers

#### DashboardRHController
| Método | Acción |
|---|---|
| `index()` | Dashboard RH con cards: expedientes, reloj, evaluaciones, inventario IT, días festivos, recordatorios |

#### ExpedienteController
| Método | Acción |
|---|---|
| `index()` | Lista empleados con filtros (área, estatus) |
| `create()` | Formulario alta empleado |
| `store(Request)` | Guardar empleado + crear User asociado |
| `show(Empleado)` | Detalle expediente con documentos, % completitud |
| `edit(Empleado)` | Formulario edición |
| `update(Request, Empleado)` | Actualizar datos |
| `destroy(Empleado)` | Baja lógica (mover a empleados_baja) |
| `documentos(Empleado)` | Gestión documentos del empleado |
| `uploadDocumento(Request, Empleado)` | Subir documento |

#### AsistenciaController
| Método | Acción |
|---|---|
| `index()` | Reloj checador con tabla de asistencias |
| `importar(Request)` | Importar checadas desde archivo |
| `reporte(Request)` | Reporte de asistencias por período |

#### CapacitacionController
| Método | Acción |
|---|---|
| `index()` | Lista cursos de capacitación |
| `store(Request)` | Crear curso |
| `asignar(Request, Capacitacion)` | Asignar empleados |
| `registrarAsistencia(Request, Capacitacion)` | Marcar asistencia |

#### EvaluacionController
| Método | Acción |
|---|---|
| `index()` | Lista evaluaciones activas |
| `store(Request)` | Crear evaluación |
| `asignar(Request, Evaluacion)` | Asignar a empleados |
| `responder(Request, Evaluacion, Empleado)` | Guardar respuestas |

#### RecordatorioController
| Método | Acción |
|---|---|
| `index()` | Lista recordatorios |
| `store(Request)` | Crear recordatorio |
| `completar(Recordatorio)` | Marcar como completado |

#### DiasFestivosController
| Método | Acción |
|---|---|
| `index()` | Calendario de días festivos |
| `store(Request)` | Agregar día festivo |
| `update(Request, DiasFestivo)` | Editar |
| `destroy(DiasFestivo)` | Eliminar |

### 7.3 Logística Controllers

#### LogisticaController
| Método | Acción |
|---|---|
| `index()` | Dashboard logística |
| `clientes()` | Lista clientes logísticos |

#### MatrizSeguimientoController
| Método | Acción |
|---|---|
| `index()` | Matriz de seguimiento (MFO) con filtros |
| `store(Request)` | Agregar registro MFO |
| `update(Request, id)` | Actualizar estatus |
| `reporte(Request)` | Generar reporte |

#### MatrizApoyoController
| Método | Acción |
|---|---|
| `index()` | Matriz de apoyo operativo |
| `store(Request)` | Agregar registro |
| `update(Request, id)` | Actualizar |

### 7.4 Legal Controllers

#### LegalController
| Método | Acción |
|---|---|
| `index()` | Dashboard Legal |

#### CategoriaController
| Método | Acción |
|---|---|
| `index()` | Categorías legales (CRUD) |

#### ProgramaController
| Método | Acción |
|---|---|
| `index()` | Programas legales (CRUD) |

#### MatrizConsultaController
| Método | Acción |
|---|---|
| `index()` | Matriz de consultas |
| `store(Request)` | Nueva consulta |
| `responder(Request, id)` | Responder consulta |

#### DigitalizacionController
| Método | Acción |
|---|---|
| `index()` | Digitalización VUCEM |
| `upload(Request)` | Subir archivo digitalizado |

### 7.5 Sistemas IT Controllers

#### AdminController
| Método | Acción |
|---|---|
| `index()` | Dashboard admin IT |

#### TicketController
| Método | Acción |
|---|---|
| `misTickets()` | Tickets del usuario autenticado |
| `index()` | Todos los tickets (admin) |
| `store(Request)` | Crear ticket |
| `show(Ticket)` | Detalle ticket |
| `update(Request, Ticket)` | Actualizar estado/asignación |
| `cerrar(Ticket)` | Cerrar ticket |

#### UsuarioController
| Método | Acción |
|---|---|
| `index()` | Lista usuarios (aprobación) |
| `aprobar(User)` | Aprobar usuario pendiente |
| `rechazar(User)` | Rechazar usuario |
| `destroy(User)` | Eliminar usuario |

#### ActivoController
| Método | Acción |
|---|---|
| `index()` | Inventario de activos IT |
| `store(Request)` | Agregar activo |
| `update(Request, InventoryItem)` | Editar activo |
| `destroy(InventoryItem)` | Dar de baja |

#### CredencialController
| Método | Acción |
|---|---|
| `index()` | Gestor de credenciales |
| `store(Request)` | Guardar credencial (password encryptado) |
| `show(Credencial)` | Ver credencial (desencriptada con confirmación) |
| `update(Request, Credencial)` | Actualizar |
| `destroy(Credencial)` | Eliminar |

#### ComputerProfileController
| Método | Acción |
|---|---|
| `index()` | Perfiles de cómputo |
| `store(Request)` | Crear perfil |
| `show(ComputerProfile)` | Detalle con historial mantenimiento |
| `registrarMantenimiento(Request, ComputerProfile)` | Registrar mantenimiento |

#### MaintenanceSlotController
| Método | Acción |
|---|---|
| `index()` | Slots de mantenimiento (calendario) |
| `store(Request)` | Crear slot |
| `book(Request, MaintenanceSlot)` | Reservar slot desde ticket |
| `cancelBooking(MaintenanceBooking)` | Cancelar reserva |

#### HelpSectionController
| Método | Acción |
|---|---|
| `index()` | Secciones de ayuda |
| `store(Request)` | Crear sección |
| `update(Request, HelpSection)` | Editar |
| `reorder(Request)` | Reordenar secciones |

### 7.6 Proyectos / Actividades Controllers

#### ProyectoController
| Método | Acción |
|---|---|
| `index()` | Lista proyectos |
| `store(Request)` | Crear proyecto |
| `show(Proyecto)` | Detalle con actividades |
| `update(Request, Proyecto)` | Editar proyecto |
| `destroy(Proyecto)` | Eliminar (con verificación) |
| `reporte(Proyecto)` | Reporte PDF |

#### ActividadController
| Método | Acción |
|---|---|
| `index()` | Tablero de actividades (todas) |
| `store(Request)` | Crear actividad |
| `update(Request, Actividad)` | Editar/completar actividad |
| `import(Request)` | Importación masiva desde Excel |
| `export()` | Exportar a Excel |
| `reporte()` | Reporte PDF |

### 7.7 API Controllers

#### Api\AuthController
| Método | Acción |
|---|---|
| `login(Request)` | Validar credenciales → devolver Sanctum token |
| `logout(Request)` | Revocar token actual |
| `me(Request)` | Datos del usuario autenticado |
| `refresh(Request)` | Refrescar token |
| `validateToken(Request)` | Validar que un token sea válido |

#### Api\UserController
| Método | Acción |
|---|---|
| `index(Request)` | Lista usuarios activos (con `X-API-Key`) |

### 7.8 Admin / Dashboard Controllers

#### AdministracionController
| Método | Acción |
|---|---|
| `index()` | Dashboard administración |
| `clientes()` | CRUD clientes |
| `perfiles()` | Perfiles de cliente |

#### DashboardController
| Método | Acción |
|---|---|
| `index()` | Dashboard genérico post-login |

---

## 8. Referencia de Middleware

### 8.1 Listado Completo

| Middleware | Clase | Ruta | Propósito |
|---|---|---|---|
| `admin` | `AdminMiddleware` | `/administracion/*` | Verifica `auth()->user()->isAdmin()` |
| `sistemas_admin` | `SistemasAdminMiddleware` | `/admin/*` | Verifica role=admin AND área Sistemas / posición TI/IT |
| `area.logistica` | `AreaLogisticaMiddleware` | `/logistica/*` | Verifica área/posición contenga "logistic" |
| `area.rh` | `AreaRHMiddleware` | `/recursos-humanos/*` | Verifica posición: direccion, administracion rh, o ti. Ruta `rh.reloj.equipo` exenta. |
| `area.legal` | `AreaLegalMiddleware` | `/legal/*` | Verifica área legal/jurídico |
| `area.anexo24` | — | `/anexo24/*` | Verifica acceso Anexo 24 |
| `area.postoperaciones` | — | `/postoperaciones/*` | Verifica acceso Post-Operaciones |
| `area.auditoria` | — | `/auditoria/*` | Verifica acceso Auditoría |
| `api.key` | `CheckApiKey` | `/api/v1/*` (públicas) | Compara header `X-API-Key` con `config('app.api_key')` |
| `guest` | Laravel core | `/login`, `/register` | Redirige si ya autenticado |
| `verified` | Laravel core | `/legal/*`, `/admin/*` | Email verification |
| `auth` | Laravel core | Todas las rutas protegidas | Session authentication |

### 8.2 AdminMiddleware

```php
public function handle(Request $request, Closure $next): mixed
{
    if (!auth()->check() || !auth()->user()->isAdmin()) {
        abort(403);
    }
    return $next($request);
}
```

### 8.3 SistemasAdminMiddleware

```php
public function handle(Request $request, Closure $next): mixed
{
    $user = auth()->user();
    $posicionNormalizada = mb_strtolower(
        preg_replace('/\s+/u', ' ', trim($user?->empleado?->posicion ?? '')), 'UTF-8'
    );
    if ($user->role !== 'admin' ||
        ($user->area !== 'Sistemas' &&
         !str_contains($posicionNormalizada, 'ti') &&
         !str_contains($posicionNormalizada, 'it'))) {
        abort(403);
    }
    return $next($request);
}
```

### 8.4 AreaLogisticaMiddleware

```php
public function handle(Request $request, Closure $next): mixed
{
    $user = auth()->user();
    $area = mb_strtolower($user->empleado?->area ?? '');
    $posicion = mb_strtolower($user->empleado?->posicion ?? '');
    if (!str_contains($area, 'logistic') && !str_contains($posicion, 'logistic')) {
        abort(403);
    }
    return $next($request);
}
```

### 8.5 AreaRHMiddleware

```php
public function handle(Request $request, Closure $next): mixed
{
    $user = auth()->user();
    $posicion = mb_strtolower(
        preg_replace('/\s+/u', ' ', trim($user?->empleado?->posicion ?? '')), 'UTF-8'
    );
    if ($request->route()->named('rh.reloj.equipo')) {
        return $next($request);
    }
    if (!in_array($posicion, ['direccion', 'administracion rh', 'ti'])) {
        abort(403);
    }
    return $next($request);
}
```

### 8.6 CheckApiKey

```php
public function handle(Request $request, Closure $next): mixed
{
    $apiKey = $request->header('X-API-Key');
    if (!$apiKey || $apiKey !== config('app.api_key')) {
        return response()->json(['error' => 'Unauthorized'], 401);
    }
    return $next($request);
}
```

### 8.7 Orden de Middleware en Kernel

```php
protected $middlewareAliases = [
    'auth'             => \Illuminate\Auth\Middleware\Authenticate::class,
    'admin'            => \App\Http\Middleware\AdminMiddleware::class,
    'sistemas_admin'   => \App\Http\Middleware\SistemasAdminMiddleware::class,
    'area.logistica'   => \App\Http\Middleware\AreaLogisticaMiddleware::class,
    'area.rh'          => \App\Http\Middleware\AreaRHMiddleware::class,
    'area.legal'       => \App\Http\Middleware\AreaLegalMiddleware::class,
    'api.key'          => \App\Http\Middleware\CheckApiKey::class,
    'verified'         => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
];
```

---

## 9. Referencia de Servicios

### 9.1 ActivosDbService

**Archivo:** `app/Services/ActivosDbService.php`
**Propósito:** Consultar y escribir en BD secundaria `AuditoriaActivos` (conexión `activos`).

| Método | Descripción |
|---|---|
| `conn()` | Retorna `DB::connection('activos')` |
| `normalizeDeviceType(string $type)` | Normaliza tipo de dispositivo |
| `syncDevice(array $data)` | Sincroniza dispositivo ERP → Activos DB |
| `getDeviceHistory(int $deviceId)` | Historial de asignaciones |
| `getEmployeeAssignments(int $employeeId)` | Equipos asignados a empleado |

**Uso:**
```php
$service = app(ActivosDbService::class);
$devices = $service->conn()->table('devices')->where('active', true)->get();
```

### 9.2 MicrosoftGraphMailService

**Archivo:** `app/Services/MicrosoftGraphMailService.php`
**Propósito:** Envío de correos vía Graph API (Client Credentials OAuth2).

| Método | Descripción |
|---|---|
| `getAccessToken()` | Obtiene token OAuth2 (cacheado) |
| `sendEmail(string $to, string $subject, string $body)` | Correo HTML simple |
| `sendEmailWithAttachment(string $to, string $subject, string $body, array $attachments)` | Correo con adjuntos |

**Flujo:**
1. Revisa cache `ms_graph_access_token`
2. Si expirado, solicita nuevo a `login.microsoftonline.com/{tenant}/oauth2/v2.0/token`
3. Envía vía `graph.microsoft.com/v1.0/users/{sender}/sendMail`

### 9.3 Otros Servicios

| Servicio | Propósito |
|---|---|
| PDF Services | Conversión/manipulación de PDFs (Ghostscript, QPDF) |
| Digitalización Services | Procesamiento documentos VUCEM |
| Logística Services | Cálculos estatus, reportes MFO |
| IT Services | Lógica tickets, notificaciones, mantenimiento |
| RH Services | Cálculo expedientes, asistencias |

---

## 10. Comandos Artisan y Tareas Programadas

### 10.1 Comandos Artisan

| Comando | Frecuencia | Propósito |
|---|---|---|
| `logistica:actualizar-status` | Cada hora | Actualiza estatus matriz seguimiento |
| `rh:generar-recordatorios` | Diario | Genera recordatorios automáticos RH |
| `proyectos:recordatorios` | Diario 08:00 | Recordatorios actividades próximas a vencer |
| `it:notificar-proximo-mantenimiento` | Diario 08:00 | Notifica mantenimiento próximo |
| `SincronizarContraseniasActivos` | Bajo demanda | Sincroniza credenciales → BD Activos |
| `SincronizarCorreosActivos` | Bajo demanda | Sincroniza correos empleados → BD Activos |

### 10.2 Schedule (routes/console.php)

```php
Schedule::command('logistica:actualizar-status')->hourly();
Schedule::command('rh:generar-recordatorios')->daily();
Schedule::command('proyectos:recordatorios')->dailyAt('08:00');
Schedule::command('it:notificar-proximo-mantenimiento')->dailyAt('08:00');
```

---

## 11. Sistema de Correos y Notificaciones

### 11.1 Mailables

| Mailable | Propósito |
|---|---|
| `TicketCreated` | Notificación admin IT de nuevo ticket |
| `TicketUpdated` | Notificación usuario de actualización |
| `MaintenanceReminder` | Recordatorio cita mantenimiento |
| `AsistenciaIncidencia` | Alerta incidencia asistencia RH |
| `ProyectoRecordatorio` | Recordatorio actividades proyecto |

### 11.2 Webhooks n8n

| Webhook | Propósito |
|---|---|
| `N8N_WEBHOOK_URL` | Notificaciones tickets (creación/actualización/cierre) |
| `N8N_LOGISTICA_WEBHOOK_URL` | Reportes logística (actualización estatus) |

**Flujo tickets → n8n:**
```
Usuario crea ticket → TicketController@store
  → Guardar BD
  → POST JSON {folio, solicitante, descripcion, prioridad} → n8n
  → n8n distribuye a Teams/Correo según reglas
```

---

## 12. Frontend

### 12.1 Layouts

| Layout | Archivo | Uso |
|---|---|---|
| `layouts.erp` | `resources/views/layouts/erp.blade.php` | RH, Logística, Proyectos, Actividades |
| `layouts.erp-navigation` | `resources/views/layouts/erp-navigation.blade.php` | Nav sticky con detección módulo |
| `layouts.master` | `resources/views/layouts/master.blade.php` | Admin, Legal, Administración, etc. |
| `layouts.app` | `resources/views/Sistemas_IT/layouts/app.blade.php` | IT interno |
| `layouts.guest` | `resources/views/Sistemas_IT/layouts/guest.blade.php` | Login, registro |

### 12.2 Componentes Blade

| Componente | Propósito |
|---|---|
| `x-nav-link` | Nav link con active route detection |
| `x-dropdown` | Dropdown usuario (perfil/logout) |
| `x-dropdown-link` | Link dentro de dropdown |
| `x-responsive-nav-link` | Link menú responsive móvil |
| `x-input-error` | Error de validación |
| `x-auth-session-status` | Mensaje estado sesión |
| `x-guest-layout` | Layout páginas públicas |

### 12.3 JavaScript

| Módulo | Archivo | Propósito |
|---|---|---|
| Alpine.js | `resources/js/app.js` | Reactividad frontend |
| Flatpickr | `resources/js/app.js` | Date/time picker (locale ES) |
| jsPDF + html2canvas | `resources/js/app.js` | PDF desde HTML (cliente) |
| Notificaciones | `Sistemas_IT/components/notifications.js` | Dropdown notificaciones tickets |
| Admin Notifications | `Sistemas_IT/components/admin-notification-center.js` | Centro notificaciones admin IT |

### 12.4 Vite Inputs y Chunks

```js
// vite.config.js - Entradas por módulo
laravel({
    input: [
        'resources/css/app.css',
        'resources/js/app.js',
        'resources/css/Sistemas_IT/inventario-index.css',
        'resources/js/Sistemas_IT/tickets-my.js',
        'resources/js/Sistemas_IT/tickets-create.js',
        'resources/css/Recursos_Humanos/index.css',
        'resources/js/Recursos_Humanos/index.js',
        'resources/css/Logistica/index.css',
        'resources/js/Logistica/index.js'
    ],
})

// Chunks manuales para vendor isolation
manualChunks(id) {
    if (id.includes('alpinejs') || id.includes('@alpinejs')) return 'vendor-alpine';
    if (id.includes('flatpickr')) return 'vendor-flatpickr';
    if (id.includes('jspdf') || id.includes('html2canvas')) return 'vendor-pdf';
    return 'vendor';
}
```

---

## 13. Seguridad y Roles

### 13.1 Sistema de Roles

| Rol | Descripción |
|---|---|
| `admin` | Acceso completo `/admin`, `/administracion`, gestión usuarios |
| `user` | Acceso según posición/departamento |

### 13.2 Estados de Usuario

| Estado | Descripción |
|---|---|
| `pending` | Recién registrado, pendiente aprobación |
| `approved` | Activo, puede iniciar sesión |
| `rejected` | Rechazado, no puede iniciar sesión |

### 13.3 Gates

```php
Gate::define('ver_rh', fn($user) => $user->isRH());
Gate::define('ver_logistica', fn($user) => $user->isLogistica());
```

### 13.4 Métodos de User para Detección de Área

| Método | Lógica |
|---|---|
| `isAdmin()` | `$this->role === 'admin'` |
| `isRH()` | role/area/departamento/puesto contenga: rh, recursos humanos, capital humano, administracion rh |
| `isRhCoordinador()` | admin+rh o posición con coordinador/direcc/gerente/jefe |
| `isLogistica()` | Busca logistica, operaciones, aduana |
| `isLegal()` | Busca legal, juridico |
| `getPanelInfo()` | Retorna `['route' => '...', 'label' => '...']` según posición |
| `getAllPanels()` | Todos los paneles disponibles |
| `scopeTi($query)` | Scope usuarios TI |

### 13.5 Protección Especial

- **Credenciales**: contraseñas encriptadas con Laravel encryption, se desencriptan solo con confirmación explícita
- **API Key**: comparación contra `config('app.api_key')` vía middleware `CheckApiKey`
- **Sanctum tokens**: autenticación stateless para API, tokens revocables por sesión

---

## 14. Integraciones Externas

### 14.1 Base de Datos Activos (Auditoría IT)

**Propósito:** BD SQLite secundaria para inventario histórico de activos IT.

**Conexión:** `config/database.php` → `'activos'` (driver sqlite)
**Ruta:** Configurable vía `ACTIVOS_DB_PATH` en `.env`

**Esquema externo:**
- `devices` — Dispositivos (código, tipo, marca, modelo, SN, estado)
- `assignments` — Asignaciones empleado ↔ dispositivo
- `employees` — Catálogo empleados sincronizado
- `device_photos` — Fotos de evidencia

**Servicio:** `ActivosDbService` → `DB::connection('activos')`

### 14.2 n8n Webhooks

| Webhook | Trigger | Payload |
|---|---|---|
| Tickets | Creación/actualización ticket | `{folio, solicitante, descripcion, prioridad, estado}` |
| Logística | Actualización estatus MFO | `{cliente, pedimento, estatus, area}` |

### 14.3 Microsoft Graph Mail

**Flujo:**
1. `MicrosoftGraphMailService::getAccessToken()` → token OAuth2
2. Cache en DB hasta expiración
3. `POST graph.microsoft.com/v1.0/users/{sender}/sendMail`
4. Adjuntos en base64

### 14.4 PDF Tools

| Herramienta | Propósito |
|---|---|
| Ghostscript | Conversión/comparsión PDF |
| QPDF | Manipulación estructural PDF |
| Browsershot | Captura HTML → PDF (headless Chromium) |
| ImageMagick | Procesamiento imágenes |

---

## 15. Observers y Providers

### 15.1 EmpleadoObserver

| Evento | Acción |
|---|---|
| `created(Empleado)` | Sincroniza con BD Activos |
| `updated(Empleado)` | Actualiza en BD Activos |
| `deleted(Empleado)` | Marca inactivo en BD Activos |

```php
// AppServiceProvider::boot()
Empleado::observe(EmpleadoObserver::class);
```

### 15.2 AppServiceProvider (boot)

1. HTTPS forzado en producción: `URL::forceScheme('https')`
2. Observer `EmpleadoObserver`
3. Transport `microsoft-graph` para Mail
4. Gates `ver_rh`, `ver_logistica`
5. View Composer `authenticated-actions` para notificaciones tickets (usuarios no-admin)

---

## 16. Estrategia de Pruebas

### 16.1 Configuración (phpunit.xml)

```xml
<env name="APP_ENV" value="testing"/>
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="CACHE_STORE" value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
<env name="MAIL_MAILER" value="array"/>
<env name="TELESCOPE_ENABLED" value="false"/>
```

### 16.2 Prioridades

1. **Modelos** — Validaciones, scopes, relaciones
2. **Servicios** — Lógica de negocio (ActivosDb, Graph, PDF)
3. **Controladores** — CRUD módulos core (RH, Logística, IT)
4. **Middleware** — Restricción acceso por área
5. **Comandos** — Sincronizaciones, recordatorios

### 16.3 Mocking Sugerido

- Microsoft Graph: mock `MicrosoftGraphMailService::getAccessToken()` y `sendEmail()`
- n8n: `Http::fake()` para calls webhook
- BD Activos: SQLite `:memory:` con esquema espejo
- Browsershot: mock si no hay Chromium

---

## 17. Troubleshooting

### 17.1 Base de Datos

| Problema | Causa | Solución |
|---|---|---|
| `SQLite busy` | Múltiples escritores | `PRAGMA journal_mode=WAL;` |
| `attempt to write a readonly database` | Permisos archivo | Verificar permisos `database/database.sqlite` |
| Migraciones fallan | Orden FK | `php artisan migrate:fresh` (pérdida datos) |

### 17.2 Microsoft Graph Mail

| Problema | Causa | Solución |
|---|---|---|
| Token expirado | Cache corrupto | `Cache::forget('ms_graph_access_token')` |
| 401 | Client secret vencido | Renovar en Azure AD |
| 403 | Permisos insuficientes | Verificar API permissions Azure AD |

### 17.3 PDF Tools

| Problema | Causa | Solución |
|---|---|---|
| Browsershot timeout | Chromium no encontrado | Verificar `node_modules/.bin/chromium` |
| Ghostscript error | Ruta incorrecta | Verificar `GHOSTSCRIPT_PATH` en `.env` |

### 17.4 n8n Webhooks

| Problema | Causa | Solución |
|---|---|---|
| Webhook no enviado | URL incorrecta / n8n caído | Verificar `N8N_WEBHOOK_URL` |
| Payload inválido | Cambios estructura | Revisar `storage/logs/laravel.log` |

### 17.5 Autenticación

| Problema | Causa | Solución |
|---|---|---|
| No puede login | Status `pending` o `rejected` | Admin aprueba en `/admin/usuarios` |
| Middleware bloquea | Posición no autorizada | Verificar `posicion` en `empleados` |
| Session expira | `SESSION_DRIVER=database` sin migrar | `php artisan session:table && migrate` |

---

## Apéndice A: Orden de Migraciones

**101 migraciones** en orden lógico:

1. **Core**: `users`, `password_reset_tokens`, `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs`
2. **Subdepartamentos**: `subdepartamentos`
3. **RH**: `empleados`, `asistencias`, `empleado_documentos`, `empleados_baja`, `aviso_asistencias`
4. **Capacitación**: `capacitacion`, `capacitacion_empleados`
5. **Evaluaciones**: `evaluacion`, `evaluacion_categorias`, `evaluacion_empleados`
6. **Festivos**: `dias_festivos`
7. **Recordatorios**: `recordatorios`
8. **Sistemas IT**: `blocked_emails`, `maintenance_slots`, `inventory_items`, `help_sections`, `computer_profiles`, `tickets`, `maintenance_bookings`, `credenciales`
9. **Logística**: `logistica_clientes`, `logistica_matriz_seguimiento`, `logistica_matriz_apoyo`
10. **Legal**: `legal_categorias`, `legal_programas`, `legal_matriz_consulta`, `legal_digitalizacion`
11. **Administración**: `clientes`, `perfiles_cliente`
12. **Proyectos**: `proyectos`, `actividades`

---

## Apéndice B: Variables de Entorno Rápidas

| Variable | Default | Requerida | Propósito |
|---|---|---|---|
| `APP_ENV` | `local` | Sí | Entorno |
| `APP_DEBUG` | `true` | No | Debug mode |
| `APP_KEY` | — | Sí | Cifrado |
| `DB_CONNECTION` | `sqlite` | Sí | Driver BD |
| `SESSION_DRIVER` | `database` | No | Almacén sesión |
| `QUEUE_CONNECTION` | `database` | No | Driver colas |
| `MAIL_MAILER` | `log` | No | Mail driver |
| `MICROSOFT_GRAPH_CLIENT_ID` | — | Sí (correo) | Graph client |
| `MICROSOFT_GRAPH_TENANT_ID` | — | Sí (correo) | Graph tenant |
| `MICROSOFT_GRAPH_CLIENT_SECRET` | — | Sí (correo) | Graph secret |
| `MICROSOFT_GRAPH_SENDER_EMAIL` | — | Sí (correo) | Remitente |
| `N8N_WEBHOOK_URL` | — | No | Webhook tickets |
| `N8N_LOGISTICA_WEBHOOK_URL` | — | No | Webhook logística |
| `API_KEY` | — | Sí (API) | X-API-Key |
| `ACTIVOS_DB_PATH` | — | No | Ruta BD Activos |
| `GHOSTSCRIPT_PATH` | — | No | Ruta Ghostscript |

---

## Apéndice C: Documentación Existente

El proyecto tiene **59+ archivos de documentación** en `docs/`:

| Ruta | Contenido |
|---|---|
| `docs/controllers/` | Endpoints, métodos, roles por módulo |
| `docs/models/` | Diagramas, relaciones |
| `docs/security/` | Security review |
| `docs/DIAGRAMA_BD.md` | Diagrama Mermaid ER |
| `docs/programmer/` | Este manual |
| `GUIA_IMPORTACION_ACTIVIDADES.md` | Importación masiva Excel |

---

---

## Apéndice D: Modelos Complementarios (Ficha Completa)

### D.1 Activity

| Atributo | Valor |
|---|---|
| **Tabla** | `activities` |
| **Traits** | `SoftDeletes` |
| **Fillable** | `user_id`, `asignado_por`, `deleted_by`, `area`, `cliente`, `tipo_actividad`, `nombre_actividad`, `comentarios`, `fecha_inicio`, `fecha_compromiso`, `fecha_final`, `prioridad`, `estatus`, `metrico`, `resultado_dias`, `porcentaje`, `evidencia_path`, `motivo_rechazo`, `hora_inicio_programada`, `hora_fin_programada`, `proyecto_id` |
| **Casts** | `fecha_inicio` => `datetime`, `fecha_compromiso` => `datetime`, `fecha_final` => `datetime`, `metrico` => `integer`, `hora_inicio_programada` => `datetime:H:i`, `hora_fin_programada` => `datetime:H:i` |
| **Relaciones** | `user()` (User), `asignador()` (User), `historial()` (ActivityHistory), `proyecto()` (Proyecto), `deletedByUser()` (User) |
| **Auto-cálculos** | En `saving`: calcula `metrico` (días entre fechas), `resultado_dias`, `porcentaje`, auto-asigna `estatus` (Completado / Completado con retardo / Retardo) |

### D.2 ActivityHistory

| Atributo | Valor |
|---|---|
| **Tabla** | `activity_histories` |
| **Fillable** | `activity_id`, `user_id`, `action`, `field`, `old_value`, `new_value`, `details`, `comentario` |
| **Relaciones** | `user()` (User), `activity()` (Activity) |

### D.3 Administracion\Cliente

| Atributo | Valor |
|---|---|
| **Tabla** | `admin_clientes` |
| **Fillable** | `nombre`, `contacto`, `correo`, `telefono`, `empresa`, `notas` |
| **Relaciones** | `perfil()` (PerfilCliente) |

### D.4 Administracion\PerfilCliente

| Atributo | Valor |
|---|---|
| **Tabla** | `admin_cliente_perfiles` |
| **Fillable** | 60+ campos (nombre_legal, sectores_productivos, immex, maquiladora, prosec, OEA, IVA/EPS, CTPAT, regla_octava, etc.) |
| **Casts** | 21 campos `date`, 22 campos `boolean` |
| **Relaciones** | `cliente()` (Cliente) |

### D.5 Asistencia

| Atributo | Valor |
|---|---|
| **Tabla** | `asistencias` |
| **Fillable** | `empleado_id`, `empleado_no`, `nombre`, `fecha`, `entrada`, `salida`, `checadas`, `horas_trabajadas`, `tipo_registro`, `es_retardo`, `es_justificado`, `comentarios` |
| **Casts** | `fecha` => `date`, `checadas` => `array`, `es_retardo` => `boolean`, `es_justificado` => `boolean` |
| **Relaciones** | `empleado()` (Empleado) |
| **Scopes** | `enPeriodo($inicio, $fin)`, `puntuales()`, `retardosInjustificados()`, `soloFaltas()`, `asistenciasOk()`, `laborales()`, `buscar($termino)` |

### D.6 AvisoAsistencia

| Atributo | Valor |
|---|---|
| **Tabla** | `aviso_asistencias` |
| **Fillable** | `empleado_id`, `enviado_por`, `tipo`, `mensaje`, `periodo`, `cantidad_incidencias`, `leido`, `leido_at` |
| **Casts** | `leido` => `boolean`, `leido_at` => `datetime` |
| **Relaciones** | `empleado()` (Empleado), `enviadoPor()` (User) |

### D.7 BlockedEmail

| Atributo | Valor |
|---|---|
| **Tabla** | `blocked_emails` |
| **Fillable** | `email`, `reason`, `blocked_by` |
| **Relaciones** | `admin()` (User) |

### D.8 Capacitacion

| Atributo | Valor |
|---|---|
| **Tabla** | `capacitaciones` |
| **Fillable** | `titulo`, `descripcion`, `categoria`, `puestos_permitidos`, `usuarios_permitidos`, `archivo_path`, `thumbnail_path`, `subido_por`, `activo`, `youtube_url` |
| **Casts** | `puestos_permitidos` => `array`, `usuarios_permitidos` => `array` |
| **Relaciones** | `uploader()` (User), `adjuntos()` (CapacitacionAdjunto) |
| **Métodos propio** | `isVisibleFor(User $user): bool`, `isYoutube(): bool`, `getYoutubeId(): ?string` |

### D.9 CapacitacionAdjunto

| Atributo | Valor |
|---|---|
| **Tabla** | `capacitacion_adjuntos` |
| **Fillable** | `capacitacion_id`, `titulo`, `archivo_path` |
| **Relaciones** | `capacitacion()` (Capacitacion) |

### D.10 CriterioEvaluacion

| Atributo | Valor |
|---|---|
| **Tabla** | `criterios_evaluacion` |
| **Fillable** | `area`, `criterio`, `descripcion`, `peso` |

### D.11 DiaFestivo

| Atributo | Valor |
|---|---|
| **Tabla** | `dias_festivos` |
| **Fillable** | `nombre`, `fecha`, `tipo`, `es_anual`, `descripcion`, `activo`, `notificacion_enviada`, `notificacion_enviada_at` |
| **Casts** | `fecha` => `date`, `es_anual` => `boolean`, `activo` => `boolean`, `notificacion_enviada` => `boolean`, `notificacion_enviada_at` => `datetime` |
| **Scopes** | `activos()`, `delTipo($tipo)`, `proximos($dias)`, `paraFecha($fecha)`, `sinNotificacion()` |
| **Accessors** | `getFechaFormateadaAttribute()`, `getTipoLabelAttribute()` |
| **Métodos** | `esHoy()`, `esManana()`, `esProximo($dias)`, `esParaFecha($fecha)`, `getFechaEfectiva()`, `static obtenerProximoFestivo()`, `static esDiaFestivo($fecha)`, `static esDiaInhabil($fecha)`, `crearRecordatorio($user)`, `enviarNotificaciones()` |

### D.12 EmpleadoBaja

| Atributo | Valor |
|---|---|
| **Tabla** | `empleados_baja` |
| **Fillable** | `empleado_id`, `user_id`, `nombre`, `correo`, `motivo_baja`, `fecha_baja`, `observaciones` |
| **Casts** | `fecha_baja` => `date` |
| **Relaciones** | `empleado()` (Empleado), `user()` (User) |

### D.13 EmpleadoDocumento

| Atributo | Valor |
|---|---|
| **Tabla** | `empleado_documentos` |
| **Fillable** | `empleado_id`, `nombre`, `categoria`, `ruta_archivo`, `fecha_vencimiento` |
| **Casts** | `fecha_vencimiento` => `date` |
| **Relaciones** | `empleado()` (Empleado) |

### D.14 Evaluacion

| Atributo | Valor |
|---|---|
| **Tabla** | `evaluaciones` |
| **Fillable** | `empleado_id`, `evaluador_id`, `periodo`, `ventana_id`, `tipo`, `promedio_final`, `comentarios_generales`, `edit_count`, `fecha_firma_empleado` |
| **Casts** | `fecha_firma_empleado` => `datetime` |
| **Relaciones** | `ventana()` (EvaluacionVentana), `detalles()` (EvaluacionDetalle), `empleado()` (Empleado), `evaluador()` (User) |

### D.15 EvaluacionDetalle

| Atributo | Valor |
|---|---|
| **Tabla** | `evaluacion_detalles` |
| **Fillable** | `evaluacion_id`, `criterio_id`, `calificacion`, `observaciones` |
| **Relaciones** | `criterio()` (CriterioEvaluacion) |

### D.16 EvaluacionVentana

| Atributo | Valor |
|---|---|
| **Tabla** | `evaluacion_ventanas` |
| **Fillable** | `nombre`, `fecha_apertura`, `fecha_cierre`, `activo`, `creado_por` |
| **Casts** | `fecha_apertura` => `date`, `fecha_cierre` => `date`, `activo` => `boolean` |
| **Métodos** | `static estaAbierta(): bool`, `static ventanaActual(): ?self` |

### D.17 HelpSection

| Atributo | Valor |
|---|---|
| **Tabla** | `help_sections` |
| **Fillable** | `title`, `content`, `section_order`, `is_active`, `images` |
| **Casts** | `is_active` => `boolean`, `images` => `array` |

### D.18 InventoryItem

| Atributo | Valor |
|---|---|
| **Tabla** | `inventory_items` |
| **Constantes** | `ESTADO_DISPONIBLE = 'disponible'`, `ESTADO_PRESTADO = 'prestado'`, `ESTADO_MANTENIMIENTO = 'mantenimiento'`, `ESTADO_RESERVADO = 'reservado'`, `ESTADO_DANADO = 'dañado'` |
| **Fillable** | `codigo_producto`, `identificador`, `nombre`, `categoria`, `marca`, `modelo`, `numero_serie`, `estado`, `es_funcional`, `ubicacion`, `descripcion_general`, `notas` |
| **Casts** | `es_funcional` => `boolean` |

### D.19 Legal\LegalArchivo

| Atributo | Valor |
|---|---|
| **Tabla** | `legal_archivos` |
| **Fillable** | `proyecto_id`, `nombre`, `tipo`, `ruta`, `es_url`, `mime_type` |
| **Casts** | `es_url` => `boolean` |
| **Relaciones** | `proyecto()` (LegalProyecto) |
| **Accessors** | `getUrlPublicaAttribute(): ?string` |

### D.20 Legal\LegalCategoria

| Atributo | Valor |
|---|---|
| **Tabla** | `legal_categorias` |
| **Fillable** | `nombre`, `parent_id`, `tipo` |
| **Relaciones** | `parent()` (LegalCategoria), `subcategorias()` (LegalCategoria), `proyectos()` (LegalProyecto) |

### D.21 Legal\LegalPagina

| Atributo | Valor |
|---|---|
| **Tabla** | `legal_paginas` |
| **Fillable** | `nombre`, `url` |

### D.22 Legal\LegalProyecto

| Atributo | Valor |
|---|---|
| **Tabla** | `legal_proyectos` |
| **Fillable** | `empresa`, `tipo`, `categoria_id`, `consulta`, `resultado`, `cliente`, `detalles` |
| **Relaciones** | `categoria()` (LegalCategoria), `archivos()` (LegalArchivo) |

### D.23 Logistica\Aduana

| Atributo | Valor |
|---|---|
| **Tabla** | `aduanas` |
| **Fillable** | `aduana`, `seccion`, `denominacion`, `patente`, `pais` |
| **Scopes** | `porCodigo($codigo)`, `porPais($pais)` |
| **Accessors** | `getNombreCompletoAttribute()` |

### D.24 Logistica\AgenteAduanal

| Atributo | Valor |
|---|---|
| **Tabla** | `agentes_aduanales` |
| **Fillable** | `agente_aduanal` |

### D.25 Logistica\Cliente

| Atributo | Valor |
|---|---|
| **Tabla** | `clientes` |
| **Fillable** | `cliente`, `ejecutivo_asignado_id`, `correos`, `periodicidad_reporte`, `fecha_carga_excel` |
| **Casts** | `correos` => `array`, `fecha_carga_excel` => `datetime` |
| **Relaciones** | `ejecutivoAsignado()` (Empleado) |
| **Accessors** | `getCorreosStringAttribute()` |

### D.26 Logistica\Incoterm

| Atributo | Valor |
|---|---|
| **Tabla** | `incoterms` |
| **Fillable** | `codigo`, `nombre`, `descripcion`, `grupo`, `aplicable_importacion`, `aplicable_exportacion`, `activo`, `orden` |
| **Casts** | `aplicable_importacion` => `boolean`, `aplicable_exportacion` => `boolean`, `activo` => `boolean` |
| **Scopes** | `activos()`, `ordenados()`, `paraImportacion()`, `paraExportacion()`, `grupo($grupo)` |

### D.27 Logistica\MatrizApoyoAgente

| Atributo | Valor |
|---|---|
| **Tabla** | `matriz_apoyo_agentes` |
| **Constantes** | `RESPONSABILIDADES = ['Gerente de operaciones', 'Ejecutivo de operaciones - Tramitador Operativo', 'Cita de Previo', 'Clasificación de mercancías', 'Cita de despacho', 'Cita de vacío']` |
| **Fillable** | `cliente`, `aduana`, `agente_aduanal`, `razon_social`, `patente`, `calificacion`, `responsabilidad`, `nombre`, `correo_electronico`, `telefono`, `comentarios` |

### D.28 Logistica\MatrizApoyoArrastre

| Atributo | Valor |
|---|---|
| **Tabla** | `matriz_apoyo_arrastres` |
| **Constantes** | `RESPONSABILIDADES = ['Cotización fletes', 'Programación de unidad', 'Finanzas']` |
| **Fillable** | `cliente`, `aduana`, `razon_social`, `calificacion`, `responsabilidad`, `nombre`, `correo_electronico`, `telefono`, `comentarios` |

### D.29 Logistica\MatrizApoyoForwarder

| Atributo | Valor |
|---|---|
| **Tabla** | `matriz_apoyo_forwarders` |
| **Constantes** | `RESPONSABILIDADES = ['Cotización fletes', 'Contacto puerto origen', 'Contacto puerto destino']` |
| **Fillable** | `cliente`, `aduana`, `razon_social`, `calificacion`, `responsabilidad`, `nombre`, `correo_electronico`, `telefono`, `comentarios` |

### D.30 Logistica\MatrizApoyoNaviera

| Atributo | Valor |
|---|---|
| **Tabla** | `matriz_apoyo_navieras` |
| **Constantes** | `RESPONSABILIDADES = ['Customer Service', 'Finanzas', 'Corte de Demoras']` |
| **Fillable** | `cliente`, `aduana`, `razon_social`, `calificacion`, `responsabilidad`, `nombre`, `correo_electronico`, `telefono`, `comentarios` |

### D.31 Logistica\MatrizSeguimiento

| Atributo | Valor |
|---|---|
| **Tabla** | `matriz_seguimiento` |
| **Constantes** | `CARGA_TIPOS = ['FCL','LCL']`, `TIPOS_CONTENEDOR = ['20\' ST','40\' ST','40\' HC','45\' HC','20\' RF','40\' RF','Open Top','Flat Rack']`, `TIPOS_OPERACION = ['Marítimo','Aéreo','Terrestre','Ferroviario']`, `STATUSES = ['Pendiente','En Tránsito','En Aduana','Previo Programado','Cita Programada','Despachado','Entregado','Cancelado']`, `RESULTADOS = ['En Proceso','Exitoso','Demorado','Cancelado']` |
| **Fillable** | `user_id`, `ref_interna`, `proveedor_cliente`, `cliente_operacion`, `factura`, `impo_ex`, `tipo_operacion`, `transporte`, `naviera`, `buque`, `carga_tipo`, `no_contenedor`, `tipo_contenedor`, `aduana`, `clave`, `pedimento`, `bl_guia`, `etd`, `eta`, `dias_libres`, `previo`, `cita_despacho`, `arribo_planta`, `status`, `resultado`, `target`, `comentarios` |
| **Casts** | `etd` => `date`, `eta` => `date`, `previo` => `date`, `cita_despacho` => `date`, `arribo_planta` => `date` |
| **Relaciones** | `user()` (User), `historial()` (MatrizSeguimientoComentario) |

### D.32 Logistica\MatrizSeguimientoComentario

| Atributo | Valor |
|---|---|
| **Tabla** | `matriz_seguimiento_comentarios` |
| **Fillable** | `matriz_seguimiento_id`, `user_id`, `comentario` |
| **Relaciones** | `user()` (User), `seguimiento()` (MatrizSeguimiento) |

### D.33 Logistica\Pedimento

| Atributo | Valor |
|---|---|
| **Tabla** | `pedimentos` |
| **Fillable** | `categoria`, `clave`, `descripcion` |
| **Scopes** | `porClave($clave)`, `porDescripcion($desc)`, `porCategoria($cat)`, `porEstadoPago($estado)`, `pendientes()`, `pagados()`, `vencidos()` |
| **Métodos** | `static getCategorias()`, `estaVencido()`, `getColorEstado()`, `getTextoEstado()` |

### D.34 Logistica\Transporte

| Atributo | Valor |
|---|---|
| **Tabla** | `transportes` |
| **Fillable** | `transporte`, `tipo_operacion` |
| **Scopes** | `porTipoOperacion($tipo)` |

### D.35 PlaneacionVentana

| Atributo | Valor |
|---|---|
| **Tabla** | `planeacion_ventanas` |
| **Constantes** | `$diasNombres = [1=>'Lunes', 2=>'Martes', 3=>'Miércoles', 4=>'Jueves', 5=>'Viernes', 6=>'Sábado', 7=>'Domingo']` |
| **Fillable** | `dia_semana`, `hora_apertura`, `hora_cierre`, `activo`, `creado_por` |
| **Casts** | `activo` => `boolean` |
| **Métodos** | `static estaAbierta(): bool`, `static ventanaActual(): ?self` |

### D.36 Proyecto

| Atributo | Valor |
|---|---|
| **Tabla** | `proyectos` |
| **Traits** | `SoftDeletes` |
| **Fillable** | `nombre`, `descripcion`, `usuario_id`, `fecha_inicio`, `fecha_fin`, `fecha_fin_real`, `recurrencia`, `notas`, `archivado`, `finalizado` |
| **Casts** | `fecha_inicio` => `date`, `fecha_fin` => `date`, `fecha_fin_real` => `date`, `archivado` => `boolean`, `finalizado` => `boolean` |
| **Relaciones** | `creador()` (User), `usuarios()` (belongsToMany User), `responsablesTi()` (belongsToMany User), `actividades()` (Activity) |
| **Scopes** | `activos()`, `archivados()` |
| **Métodos** | `siguienteFechaJunta($desde)`, `estaActivo()`, `metricas(): array` (total, completadas, en_proceso, rechazadas, a_tiempo, con_retraso, promedio_eficiencia, etc.) |

### D.37 Recordatorio

| Atributo | Valor |
|---|---|
| **Tabla** | `recordatorios` |
| **Constantes** | `TIPO_CUMPLEAÑOS = 'cumpleaños'`, `TIPO_ANIVERSARIO = 'aniversario_laboral'`, `TIPO_DOCUMENTO_VENCER = 'documento_por_vencer'`, `TIPO_DOCUMENTO_VENCIDO = 'documento_vencido'`, `TIPO_CONTRATO_VENCER = 'contrato_por_vencer'`, `TIPO_EVALUACION_PENDIENTE = 'evaluacion_pendiente'`, `TIPO_EVENTO_PERSONAL = 'evento_personal'`, `TIPOS = [...]` (array con todos), `COLORES_EVENTO = ['#EF4444'=>'Rojo', '#F97316'=>'Naranja', ...]` |
| **Fillable** | `tipo`, `titulo`, `descripcion`, `fecha_evento`, `dias_anticipacion`, `tabla_relacionada`, `registro_id`, `empleado_id`, `creado_por`, `leido`, `leido_at`, `activo`, `color_evento`, `es_manual` |
| **Casts** | `fecha_evento` => `date`, `leido_at` => `datetime`, `leido` => `boolean`, `activo` => `boolean`, `es_manual` => `boolean` |
| **Relaciones** | `empleado()` (Empleado), `creador()` (User) |
| **Scopes** | `proximos($dias)`, `noLeidos()`, `vencidos()`, `delTipo($tipo)`, `deEmpleado($id)`, `manuales()` |
| **Accessors** | `getDiasRestantesAttribute(): ?int`, `getColorAttribute(): ?string`, `getUrgenciaAttribute(): string`, `getColorUrgenciaAttribute(): array`, `getIconoTipoAttribute(): string` |
| **Métodos** | `marcarLeido()`, `static generarCumpleaños(Empleado, ?User)`, `static generarAniversario(Empleado, ?User)`, `static generarRecordatorioDocumento(EmpleadoDocumento, ?User)`, `static generarRecordatorioContrato(Empleado, ?User)` |

### D.38 Sistemas_IT\ComputerProfile

| Atributo | Valor |
|---|---|
| **Tabla** | `computer_profiles` |
| **Fillable** | `identifier`, `brand`, `model`, `disk_type`, `ram_capacity`, `battery_status`, `aesthetic_observations`, `replacement_components`, `last_maintenance_at`, `next_maintenance_at`, `maintenance_reminder_sent_at`, `is_loaned`, `loaned_to_name`, `loaned_to_email`, `last_ticket_id`, `equipo_asignado_id` |
| **Casts** | `replacement_components` => `array`, `last_maintenance_at` => `datetime`, `next_maintenance_at` => `datetime`, `maintenance_reminder_sent_at` => `date`, `is_loaned` => `boolean` |
| **Relaciones** | `ticket()` (Ticket), `equipoAsignado()` (EquipoAsignado) |

### D.39 Sistemas_IT\CredencialEquipo

| Atributo | Valor |
|---|---|
| **Tabla** | `it_credenciales_equipos` |
| **Fillable** | `user_id`, `nombre_usuario_sistema`, `contrasena`, `equipo_asignado`, `tipo_equipo`, `numero_serie`, `sistema_operativo`, `observaciones` |
| **Hidden** | `contrasena` |
| **Relaciones** | `user()` (User) |
| **Mutator** | `setContrasenaAttribute(string $value)` — encripta con `Crypt::encryptString` |
| **Accessor** | `getContrasenaDescifradaAttribute()` — desencripta con `Crypt::decryptString` |
| **Métodos** | `static tiposEquipo(): array` — `['Laptop','Desktop','Tablet','Servidor','Otro']` |

### D.40 Sistemas_IT\EquipoAsignado

| Atributo | Valor |
|---|---|
| **Tabla** | `it_equipos_asignados` |
| **Fillable** | `user_id`, `uuid_activos`, `nombre_equipo`, `modelo`, `numero_serie`, `photo_id`, `nombre_usuario_pc`, `notas`, `es_principal` |
| **Casts** | `es_principal` => `boolean` |
| **Relaciones** | `user()` (User), `correos()` (EquipoCorreo), `perifericos()` (EquipoPeriferico) |

### D.41 Sistemas_IT\EquipoCorreo

| Atributo | Valor |
|---|---|
| **Tabla** | `it_equipos_correos` |
| **Fillable** | `equipo_asignado_id`, `correo` |
| **Relaciones** | `equipo()` (EquipoAsignado) |

### D.42 Sistemas_IT\EquipoPeriferico

| Atributo | Valor |
|---|---|
| **Tabla** | `it_equipos_perifericos` |
| **Fillable** | `equipo_asignado_id`, `uuid_activos`, `nombre`, `tipo`, `numero_serie` |
| **Relaciones** | `equipo()` (EquipoAsignado) |

### D.43 Sistemas_IT\MaintenanceBlockedSlot

| Atributo | Valor |
|---|---|
| **Tabla** | `maintenance_blocked_slots` |
| **Fillable** | `date_start`, `date_end`, `time_slot`, `reason`, `blocked_by` |
| **Casts** | `date_start` => `date`, `date_end` => `date` |
| **Relaciones** | `blockedByUser()` (User) |
| **Métodos** | `static isBlocked(string $date, ?string $timeSlot): bool`, `static getBlockedForRange(string $startDate, string $endDate): array` |

### D.44 Sistemas_IT\MaintenanceBooking

| Atributo | Valor |
|---|---|
| **Tabla** | `maintenance_bookings` |
| **Fillable** | `maintenance_slot_id`, `ticket_id`, `additional_details` |
| **Relaciones** | `slot()` (MaintenanceSlot), `ticket()` (Ticket) |

### D.45 Sistemas_IT\MaintenanceSlot

| Atributo | Valor |
|---|---|
| **Tabla** | `maintenance_slots` |
| **Fillable** | `date`, `start_time`, `end_time`, `capacity`, `booked_count`, `is_active` |
| **Casts** | `date` => `date`, `start_time` => `datetime:H:i:s`, `end_time` => `datetime:H:i:s`, `is_active` => `boolean` |
| **Relaciones** | `bookings()` (MaintenanceBooking) |
| **Accessors** | `getStartDateTimeAttribute(): Carbon`, `getEndDateTimeAttribute(): Carbon`, `getAvailableCapacityAttribute(): int` |
| **Scopes** | `active()` |

### D.46 Sistemas_IT\Ticket

| Atributo | Valor |
|---|---|
| **Tabla** | `tickets` |
| **Fillable** | `folio`, `user_id`, `nombre_solicitante`, `correo_solicitante`, `nombre_programa`, `descripcion_problema`, `imagenes`, `estado`, `fecha_apertura`, `fecha_cierre`, `observaciones`, `tipo_problema`, `prioridad`, `is_read`, `notified_at`, `read_at`, `closed_by_user`, `closed_by_user_at`, `maintenance_slot_id`, `maintenance_scheduled_at`, `maintenance_details`, `equipment_identifier`, `equipment_brand`, `equipment_model`, `equipment_password`, `disk_type`, `ram_capacity`, `battery_status`, `aesthetic_observations`, `replacement_components`, `computer_profile_id`, `imagenes_admin`, `user_has_updates`, `user_notified_at`, `user_last_read_at`, `user_notification_summary` |
| **Casts** | `imagenes` => `array`, `fecha_apertura` => `datetime`, `fecha_cierre` => `datetime`, `notified_at` => `datetime`, `read_at` => `datetime`, `is_read` => `boolean`, `user_has_updates` => `boolean`, `user_notified_at` => `datetime`, `user_last_read_at` => `datetime`, `closed_by_user` => `boolean`, `closed_by_user_at` => `datetime`, `maintenance_scheduled_at` => `datetime`, `replacement_components` => `array`, `imagenes_admin` => `array` |
| **Relaciones** | `user()` (User), `maintenanceSlot()` (MaintenanceSlot), `maintenanceBooking()` (MaintenanceBooking), `computerProfile()` (ComputerProfile) |
| **Accessors** | `getEstadoBadgeAttribute()`, `getPrioridadBadgeAttribute()` |
| **Scopes** | `byEstado($estado)`, `byTipo($tipo)` |
| **Auto-folio** | En `creating`: genera `TK + AAAA + MM + XXXX` (ej. `TK2026060001`) |

### D.47 Subdepartamento

| Atributo | Valor |
|---|---|
| **Tabla** | `subdepartamentos` |
| **Fillable** | `area`, `nombre`, `activo` |
| **Casts** | `activo` => `boolean` |
| **Relaciones** | `empleados()` (Empleado) |

### D.48 User (complemento)

| Atributo | Valor |
|---|---|
| **Traits** | `HasFactory`, `Notifiable`, `HasApiTokens`, `CanResetPassword` |
| **Implementa** | `CanResetPasswordContract` |
| **Constantes** | `STATUS_PENDING = 'pending'`, `STATUS_APPROVED = 'approved'`, `STATUS_REJECTED = 'rejected'` |
| **Hidden** | `password`, `remember_token` |
| **Casts** | `email_verified_at` => `datetime`, `password` => `hashed`, `approved_at` => `datetime`, `rejected_at` => `datetime` |
| **Relaciones completas** | `tickets()` (Ticket), `empleado()` (Empleado), `equiposAsignados()` (EquipoAsignado) |
| **Scope** | `scopeTi($query)` |
| **Métodos completos** | `normalizeString(?string): string`, `isApproved()`, `isAdmin()`, `isRh()`, `isRhCoordinador()`, `isLogistica()`, `isLegal()`, `getPanelInfo(): array`, `getAllPanels(): array`, `hasRole(string): bool` |

---

## Apéndice E: Servicios Detallados

### E.1 ActivosApiService

**Archivo:** `app/Services/ActivosApiService.php`
**Propósito:** Cliente HTTP para la API REST de AuditoríaActivos.

| Método | Descripción |
|---|---|
| `__construct()` | Lee `activos.api_url` y `activos.api_key` de config |
| `isConfigured(): bool` | Verifica URL y Key no vacíos |
| `getAssignedDevices(string $username): array` | `GET /api/v1/assigned-devices/{username}` |
| `getAvailableDevices(): array` | `GET /api/v1/devices` (filtra no asignados) |
| `assignDevice(string $uuid, string $assignedTo, ?string $employeeId, string $notes): array` | `POST /api/v1/devices/{uuid}/assign` |
| `getDevicePhoto(int $id): ?string` | `GET /api/v1/device-photos/{id}` (raw binary) |

**Helpers privados:** `client()`, `url(string $path)` — construyen HTTP request con header `X-API-Key` y timeout 15s.

### E.2 ActivosDbService (complemento)

**Archivo:** `app/Services/ActivosDbService.php`
**Propósito:** Consultas y escrituras directas en BD Activos (conexión `activos`).

| Método | Descripción |
|---|---|
| `conn()` | `DB::connection('activos')` |
| `normalizeDeviceType(?string): string` | Normaliza a `computer|peripheral|printer|mobiliario|other` |
| `getAvailableDevices(): array` | Devices con status available u orphaned |
| `getAssignedDevices(string $nombre, ?string $badge, ?string $email): array` | Búsqueda multi-estrategia |
| `assignDeviceInActivos(string $uuid, string $assignedTo, ?string $badge, ?string $notes): bool` | Transaccional: cierra asignación previa, inserta nueva, actualiza status |
| `returnDeviceInActivos(string $uuid): bool` | Cierra asignación activa, status → available |
| `markDeviceBroken(string $uuid, string $reason): bool` | Cierra asignación, status → broken |
| `addDevicePhoto(string $uuid, string $filePath, ?string $caption): bool` | Agrega foto |
| `getPhotoPath(int $photoId): ?string` | Ruta de foto |
| `deleteDevice(string $uuid): bool` | Borrado en cascada (photos, assignments, documents, credentials, device) |
| `deleteDevicePhoto(int $photoId): bool` | Elimina foto |
| `getDeviceTypeByUuid(string $uuid): ?string` | Tipo de dispositivo |
| `isConfigured(): bool` | Prueba conexión PDO |
| `getAllDevicesForPrint(): array` | Agrupado por tipo (excluye broken) |
| `getAllDevicesPaginated(?string $search, ?string $type, ?string $status, int $perPage): LengthAwarePaginator` | Paginado con filtros |
| `getDeviceStats(): array` | Conteos por status y tipo |
| `getDeviceByUuid(string $uuid): ?object` | Device con asignación activa |
| `getDevicePhotos(int $deviceId): array` | Fotos del device |
| `getAssignmentHistory(int $deviceId): array` | Historial de asignaciones |
| `getDeviceDocuments(int $deviceId): array` | Documentos |
| `createDevice(array $data): ?string` | Crea device + credenciales opcionales, regresa UUID |
| `updateDevice(string $uuid, array $data): bool` | Actualiza device + upsert/delete credenciales |
| `getDeviceCredentialByUuid(string $uuid): ?object` | Credencial por UUID |
| `getCredentialsByUuids(array $uuids): array` | Batch fetch keyed by UUID (desencripta passwords) |
| `upsertCredentialByUuid(string $uuid, ?string $username, ?string $password, ?string $email, ?string $emailPassword): bool` | Inserta o actualiza credencial |
| `getDeviceCredential(int $deviceId): ?object` | Desencripta password/email_password |

### E.3 AduanaImportService

**Archivo:** `app/Services/AduanaImportService.php`
**Propósito:** Importa aduanas desde Word (.docx), Excel (.xlsx/.xls) o CSV.

| Método | Descripción |
|---|---|
| `import(string $filePath): array` | Detecta extensión y despacha a método correspondiente |
| `getStats(): array` | Total count, por país, últimos 5 importados |

### E.4 ClienteImportService

**Archivo:** `app/Services/ClienteImportService.php`
**Propósito:** Importa clientes logísticos desde Excel con asignación de ejecutivo.

| Método | Descripción |
|---|---|
| `importFromExcel($filePath): array` | Lee hoja, crea/actualiza `Cliente`, asigna `Empleado` (área Logistica) |

### E.5 ExcelChartService

**Archivo:** `app/Services/ExcelChartService.php`
**Propósito:** Crea objetos Chart de PhpSpreadsheet (barra, línea, área, pastel/donut, dashboard).

| Método estático | Descripción |
|---|---|
| `createAdvancedBarChart(Worksheet, $dataRange, $position, $title, $options): Chart` | Gráfico de barras |
| `createLineChart(...): Chart` | Gráfico de líneas |
| `createAreaChart(...): Chart` | Gráfico de área |
| `createEnhancedPieChart(...): Chart` | Gráfico de pastel/donut |
| `createDashboard(Worksheet, array $chartConfigs): array` | Dashboard multi-gráfico |

### E.6 ExcelReportService

**Archivo:** `app/Services/ExcelReportService.php`
**Propósito:** Genera reports Excel multi-hoja con gráficos, KPIs, resúmenes ejecutivos, análisis temporal y por ejecutivo.

**Métodos públicos:**
| Método | Descripción |
|---|---|
| `__construct()` | Inicializa Spreadsheet |
| `setColumnasOrdenadas($columnas)` | Orden personalizado de columnas |
| `generateLogisticsReport($operaciones, $filtros, $estadisticas)` | Reporte completo: portada, resumen, data sheet, análisis temporal, desempeño ejecutivo |
| `save($filename)` | Guarda XLSX a disco |
| `output()` | String content del XLSX |

### E.7 MicrosoftGraphMailService (complemento)

**Archivo:** `app/Services/MicrosoftGraphMailService.php`

| Método | Descripción |
|---|---|
| `__construct()` | Lee `services.microsoft_graph.*` |
| `sendMail(string $to, string $subject, string $htmlContent, ?string $from): bool` | Envía correo HTML |
| `sendMailToMultiple(array $recipients, string $subject, string $htmlContent, ?string $from): bool` | Envía a múltiples destinatarios |
| `getAccessToken(): ?string` (private) | Obtiene token desde cache o solicita nuevo |

### E.8 PedimentoImportService

**Archivo:** `app/Services/PedimentoImportService.php`
**Propósito:** Importa pedimentos desde Excel y Word.

| Método | Descripción |
|---|---|
| `import(string $filePath): array` | Detecta extensión y despacha |
| `getStats(): array` | Conteos por tipo, últimos importados |

### E.9 ProcesarAsistenciaService

**Archivo:** `app/Services/ProcesarAsistenciaService.php`
**Propósito:** Procesa archivos Excel/CSV de asistencia (reloj checador), parsea entradas/salidas.

| Método | Descripción |
|---|---|
| `process(string $path, bool $persist, callable $onSheetProgress, array $onlyEmpleadoNos): array` | Procesa archivo completo, detecta retardos |
| `procesarFilaDeChecadas(array $rowValues, array $dayColumns, array $periodo, array $empleado): int` (protected) | Inserta/actualiza registro de asistencia |

### E.10 VucemImageExtractor

**Archivo:** `app/Services/VucemImageExtractor.php`
**Propósito:** Extrae páginas de PDF como imágenes JPEG (300 DPI) y las empaqueta en ZIP.

| Método | Descripción |
|---|---|
| `__construct()` | Auto-detecta Ghostscript |
| `extractImagesToZip(string $inputPath, string $outputZipPath): array` | Extrae JPEGs (300 DPI, calidad 25%), zipea, regresa metadatos |

### E.11 VucemPdfConverter

**Archivo:** `app/Services/VucemPdfConverter.php`
**Propósito:** Convierte PDFs a formato VUCEM (300 DPI, grayscale, PDF 1.4, max ~3MB). Algoritmo two-stage.

| Método | Descripción |
|---|---|
| `convertToVucem(string $inputPath, string $outputPath, bool $splitEnabled, int $numberOfParts, string $forceOrientation): array` | Conversión principal |
| `convertToVucemOptimized(...): array` | Algoritmo two-stage mejorado (Stage 1: optimización directa GS 70-20%, Stage 2: rasterización completa 50-8%) |
| `validateDpi(string $pdfPath): array` | Validación estricta 300x300 DPI |
| `validateVucemCompliance(string $pdfPath): array` | Verifica tamaño, versión PDF, DPI, color |
| `compressPdf(string $inputPath, string $outputPath, string $level): array` | Comprime PDF (screen|ebook|printer|prepress) |
| `mergePdfsKeepDpi(array $inputPaths, string $outputPath): array` | Fusiona PDFs manteniendo 300 DPI |
| `getToolsInfo(): array`, `getDebugInfo(): array` | Diagnóstico |

---

## Apéndice F: Form Requests

### F.1 ProfileUpdateRequest

| Método | Reglas |
|---|---|
| `rules()` | `name`: required, string, max:255 |
| | `email`: required, string, lowercase, email, max:255, unique:users (ignora current) |

### F.2 Auth\LoginRequest

| Método | Reglas |
|---|---|
| `rules()` | `email`: required, string, email |
| | `password`: required, string |
| Métodos adicionales | `authenticate()` — intenta auth, lanza `ValidationException` con rate limiting (5 intentos) |
| | `ensureIsNotRateLimited()` — lockout tras 5 fallos, dispara evento `Lockout` |
| | `throttleKey(): string` — email + IP |

---

## Apéndice G: Mailables y Notificaciones

### G.1 Mailables

| Mailable | Constructor | Subject | Vista |
|---|---|---|---|
| `AvisoAsistenciaMailable` | `(AvisoAsistencia $aviso)` | `"Aviso Oficial de {tipo} - Recursos Humanos"` | `emails.recursos_humanos.aviso_asistencia` |
| `ProyectoAsignado` | `(Proyecto $proyecto, User $usuario, string $tipo = 'usuario', ?User $enviadoPor)` | `"Te han asignado al proyecto: {nombre}"` | `emails.proyectos.asignado` |

### G.2 Notificaciones (Database)

| Notificación | Constructor | Channels | Propósito |
|---|---|---|---|
| `CustomResetPassword` | — | mail | Reset password boilerplate (Laravel default) |
| `FestivoNotification` | `(DiaFestivo $diaFestivo, bool $esManana)` | database, mail | Recordatorio de día festivo/inhabil |
| `ProximoMantenimientoNotification` | `(ComputerProfile $profile, bool $esAdminFallback)` | database, mail | Alerta de mantenimiento próximo/vencido |
| `RecordatorioJuntaProyecto` | `($proyecto, $fechaJunta)` | mail | Recordatorio de junta de proyecto |

---

## Apéndice H: Blade Components

| Componente | Ruta | Propósito |
|---|---|---|
| `x-nav-link` | `Sistemas_IT/components/nav-link.blade.php` | Link de navegación con active route detection |
| `x-dropdown` | `Sistemas_IT/components/dropdown.blade.php` | Dropdown con Alpine.js (align: left/right/top, width configurable) |
| `x-dropdown-link` | `Sistemas_IT/components/dropdown-link.blade.php` | Link dentro de dropdown |
| `x-responsive-nav-link` | `Sistemas_IT/components/responsive-nav-link.blade.php` | Link menú responsive móvil |
| `x-input-error` | `Sistemas_IT/components/input-error.blade.php` | Mensaje de error de validación |
| `x-input-label` | `Sistemas_IT/components/input-label.blade.php` | Label estilizado |
| `x-text-input` | `Sistemas_IT/components/text-input.blade.php` | Input de texto estilizado |
| `x-primary-button` | `Sistemas_IT/components/primary-button.blade.php` | Botón primario |
| `x-secondary-button` | `Sistemas_IT/components/secondary-button.blade.php` | Botón secundario |
| `x-danger-button` | `Sistemas_IT/components/danger-button.blade.php` | Botón de peligro (rojo) |
| `x-modal` | `Sistemas_IT/components/modal.blade.php` | Modal Alpine.js reutilizable |
| `x-auth-session-status` | `Sistemas_IT/components/auth-session-status.blade.php` | Mensaje de estado de sesión |
| `x-application-logo` | `Sistemas_IT/components/application-logo.blade.php` | Logo de la aplicación |
| `x-nav-links` | `Sistemas_IT/components/nav-links.blade.php` | Grupo de links de navegación |
| `x-authenticated-actions` | `Sistemas_IT/components/authenticated-actions.blade.php` | Acciones post-login (notificaciones tickets) |
| `x-admin-notification-center` | `Sistemas_IT/components/admin/notification-center.blade.php` | Centro de notificaciones admin IT |
| `x-icon` | `Sistemas_IT/components/ui/icon.blade.php` | Icono SVG reutilizable |
| `x-icon-box` | `Sistemas_IT/components/ui/icon-box.blade.php` | Caja con icono |
| *Componentes layout (app)* | |
| `x-cuestionario-yn-row` | `components/cuestionario-yn-row.blade.php` | Fila sí/no para cuestionario |
| `x-perfil-field` | `components/perfil-field.blade.php` | Campo de perfil |
| `x-perfil-textarea` | `components/perfil-textarea.blade.php` | Textarea de perfil |
| `x-perfil-yn-inline` | `components/perfil-yn-inline.blade.php` | Sí/no inline |
| `x-perfil-yn` | `components/perfil-yn.blade.php` | Sí/no para perfil |

---

## Apéndice I: Vista Completa de Archivos Blade

```
resources/views/ — 137 archivos blade (totales):

welcome.blade.php
activities/ (2): index, report_print
admin/maintenance/ (1): index
admin/maintenance/computers/ (1): show
Administracion/ (3): clientes, dashboard, perfil
Anexo24/ (1): dashboard
Auditoria/ (1): dashboard
components/ (5): cuestionario-yn-row, perfil-field, perfil-textarea, perfil-yn-inline, perfil-yn
emails/ (1): nuevo_ticket
emails/proyectos/ (1): asignado
emails/recursos_humanos/ (1): aviso_asistencia
layouts/ (2): erp-navigation, erp
Legal/ (5): dashboard, categorias/index, digitalizacion/index, matriz-consulta/index, programas/index
Logistica/ (6): clientes, index, matriz-apoyo, matriz-calificaciones, matriz-seguimiento, reportes
PostOperaciones/ (1): dashboard
proyectos/ (7): actividades, index, reporte_pdf, reporte, show, partials/actividad_form, partials/edit_form
Recursos_Humanos/ (18): index, reloj_checador, capacitacion/(4), dias_festivos/(3), evaluacion/(3), expedientes/(3), jerarquia/(1), recordatorios/(3), reloj/(1)
shared/ (1): clientes-readonly
Sistemas_IT/ (61): admin/(15), archivo-problemas/(5), auth/(6), components/(15), discos-en-uso/(4), emails/(2), help/(1), inventario/(5), layouts/(4), prestamos/(4), profile/(4), tickets/(2)
vendor/ (25): mail/html(8), mail/text(8), pagination(9)
```

---

## Apéndice J: Ejemplos API Request/Response

### J.1 Login (Sanctum)

```http
POST /api/v1/auth/login
Content-Type: application/json

{
    "email": "usuario@ei.com",
    "password": "secret"
}
```

```http
HTTP/1.1 200 OK
Content-Type: application/json

{
    "token": "1|abc123token...",
    "user": {
        "id": 1,
        "name": "Usuario",
        "role": "user",
        "status": "approved"
    }
}
```

### J.2 Validar Token

```http
POST /api/v1/auth/validate-token
Content-Type: application/json

{
    "token": "1|abc123token..."
}
```

### J.3 Me (Perfil del Token)

```http
GET /api/v1/auth/me
Authorization: Bearer 1|abc123token...
```

```http
HTTP/1.1 200 OK
Content-Type: application/json

{
    "id": 1,
    "name": "Usuario",
    "email": "usuario@ei.com",
    "role": "user",
    "status": "approved",
    "empleado": {
        "id": 1,
        "nombre": "Usuario Empleado",
        "posicion": "ti"
    }
}
```

### J.4 Lista Usuarios (API Key)

```http
GET /api/v1/users
X-API-Key: tu-api-key-secreta
```

```http
HTTP/1.1 200 OK
Content-Type: application/json

{
    "data": [
        {
            "id": 1,
            "name": "Usuario Activo",
            "email": "usuario@ei.com",
            "status": "approved",
            "role": "admin"
        }
    ],
    "meta": {
        "total": 1
    }
}
```

### J.5 Consulta Externa Asignación (ActivosApiService)

```php
// Uso interno desde controlador
$api = app(ActivosApiService::class);
$devices = $api->getAssignedDevices('juan.perez');
```

---

## Apéndice K: Pruebas Existentes

### K.1 Estructura

```
tests/
├── Feature/
│   └── (test files)
├── Unit/
│   └── (test files)
└── TestCase.php
```

### K.2 Configuración (phpunit.xml)

```xml
<env name="APP_ENV" value="testing"/>
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
<env name="CACHE_STORE" value="array"/>
<env name="QUEUE_CONNECTION" value="sync"/>
<env name="MAIL_MAILER" value="array"/>
<env name="TELESCOPE_ENABLED" value="false"/>
```

### K.3 Patrón de Test Sugerido

```php
// tests/Feature/Services/ActivosDbServiceTest.php
class ActivosDbServiceTest extends TestCase
{
    public function test_normalize_device_type()
    {
        $service = app(ActivosDbService::class);
        $this->assertEquals('computer', $service->normalizeDeviceType('Laptop HP'));
        $this->assertEquals('peripheral', $service->normalizeDeviceType('Mouse inalámbrico'));
        $this->assertEquals('other', $service->normalizeDeviceType('No identificado'));
    }

    public function test_is_configured_returns_false_without_db()
    {
        config(['activos.db_path' => null]);
        $service = app(ActivosDbService::class);
        $this->assertFalse($service->isConfigured());
    }
}
```

```php
// tests/Feature/Middleware/AreaLogisticaMiddlewareTest.php
class AreaLogisticaMiddlewareTest extends TestCase
{
    public function test_logistica_user_can_access()
    {
        $user = User::factory()->create();
        $user->empleado()->create(['area' => 'Logística']);
        
        $this->actingAs($user)
            ->get(route('logistica.index'))
            ->assertOk();
    }

    public function test_non_logistica_user_is_blocked()
    {
        $user = User::factory()->create();
        
        $this->actingAs($user)
            ->get(route('logistica.index'))
            ->assertForbidden();
    }
}
```

### K.4 Prioridades de Pruebas

1. **Modelos** — Validaciones, scopes, relaciones (48 modelos)
2. **Servicios** — Lógica de negocio (ActivosDb, Graph, PDF converters)
3. **Controladores** — Flujos CRUD módulos core
4. **Middleware** — Restricción de acceso por área (8 middleware)
5. **Comandos Artisan** — Sincronizaciones, recordatorios
6. **Form Requests** — Reglas de validación

---

## Apéndice L: Constantes del Sistema

### L.1 User Status
```php
User::STATUS_PENDING   = 'pending'
User::STATUS_APPROVED  = 'approved'
User::STATUS_REJECTED  = 'rejected'
```

### L.2 InventoryItem Status
```php
InventoryItem::ESTADO_DISPONIBLE    = 'disponible'
InventoryItem::ESTADO_PRESTADO     = 'prestado'
InventoryItem::ESTADO_MANTENIMIENTO = 'mantenimiento'
InventoryItem::ESTADO_RESERVADO    = 'reservado'
InventoryItem::ESTADO_DANADO       = 'dañado'
```

### L.3 Ticket

| Campo | Valores posibles |
|---|---|
| `estado` | `abierto`, `en_proceso`, `cerrado` |
| `tipo_problema` | `software`, `hardware`, `mantenimiento` |
| `prioridad` | `baja`, `media`, `alta`, `critica` |

### L.4 MatrizSeguimiento

| Campo | Valores posibles |
|---|---|
| `carga_tipo` | `FCL`, `LCL` |
| `tipo_operacion` | `Marítimo`, `Aéreo`, `Terrestre`, `Ferroviario` |
| `status` | `Pendiente`, `En Tránsito`, `En Aduana`, `Previo Programado`, `Cita Programada`, `Despachado`, `Entregado`, `Cancelado` |
| `resultado` | `En Proceso`, `Exitoso`, `Demorado`, `Cancelado` |

### L.5 Recordatorio

| Constante | Valor |
|---|---|
| `TIPO_CUMPLEAÑOS` | `cumpleaños` |
| `TIPO_ANIVERSARIO` | `aniversario_laboral` |
| `TIPO_DOCUMENTO_VENCER` | `documento_por_vencer` |
| `TIPO_DOCUMENTO_VENCIDO` | `documento_vencido` |
| `TIPO_CONTRATO_VENCER` | `contrato_por_vencer` |
| `TIPO_EVALUACION_PENDIENTE` | `evaluacion_pendiente` |
| `TIPO_EVENTO_PERSONAL` | `evento_personal` |

### L.6 MatrizApoyo Responsabilidades

| Modelo | Valores |
|---|---|
| `MatrizApoyoAgente::RESPONSABILIDADES` | `['Gerente de operaciones', 'Ejecutivo de operaciones - Tramitador Operativo', 'Cita de Previo', 'Clasificación de mercancías', 'Cita de despacho', 'Cita de vacío']` |
| `MatrizApoyoArrastre::RESPONSABILIDADES` | `['Cotización fletes', 'Programación de unidad', 'Finanzas']` |
| `MatrizApoyoForwarder::RESPONSABILIDADES` | `['Cotización fletes', 'Contacto puerto origen', 'Contacto puerto destino']` |
| `MatrizApoyoNaviera::RESPONSABILIDADES` | `['Customer Service', 'Finanzas', 'Corte de Demoras']` |

### L.7 CredencialEquipo Tipos
```php
CredencialEquipo::tiposEquipo(): ['Laptop', 'Desktop', 'Tablet', 'Servidor', 'Otro']
```

---

*Fin del Manual de Programador — ERP Estrategia e Innovación v1.0*
COMPLEMENTO COMPLETO — 48 modelos, 11 servicios, 2 form requests, 2 mailables, 4 notificaciones, 19 componentes blade, 137 vistas, ejemplos API, constantes del sistema.
