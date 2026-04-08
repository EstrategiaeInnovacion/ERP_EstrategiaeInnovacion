# Migraciones — Base de Datos Secundaria `activos`

Este directorio documenta el esquema completo de la base de datos secundaria
que gestiona el inventario de activos IT (`activos`).  
**No se ejecutan con `php artisan migrate`** — corresponden a la base de datos
del proyecto **AuditoriaActivos** y se aplican ahí.

---

## Conexión configurada en ERP

```php
// config/database.php
'activos' => [
    'driver'   => 'mysql',
    'host'     => env('ACTIVOS_DB_HOST', '127.0.0.1'),
    'port'     => env('ACTIVOS_DB_PORT', '3306'),
    'database' => env('ACTIVOS_DB_DATABASE', 'activos'),
    'username' => env('ACTIVOS_DB_USERNAME', ''),
    'password' => env('ACTIVOS_DB_PASSWORD', ''),
],
```

---

## Orden de ejecución

| # | Archivo | Tabla / Acción |
|---|---------|----------------|
| 1 | `2026_02_18_184711_create_devices_table.php` | `devices` |
| 2 | `2026_02_18_184712_create_credentials_table.php` | `credentials` |
| 3 | `2026_02_18_184716_create_assignments_table.php` | `assignments` |
| 4 | `2026_02_23_170000_add_is_admin_to_users_table.php` | `users` → add `is_admin` |
| 5 | `2026_02_23_180000_create_device_photos_table.php` | `device_photos` |
| 6 | `2026_02_23_180001_create_device_documents_table.php` | `device_documents` |
| 7 | `2026_03_19_142448_add_performance_indexes.php` | índices en `devices` y `assignments` |
| 8 | `2026_03_19_142516_create_audit_logs_table.php` | `audit_logs` |
| 9 | `2026_03_23_173400_create_employees_table.php` | `employees` |
| 10 | `2026_03_23_173538_add_employee_id_to_assignments_table.php` | `assignments` → add `employee_id` FK |

---

## Notas sobre columnas relevantes para el ERP

- **`devices.uuid`** — identificador único usado en QR codes y URLs
- **`devices.status`** — `available | assigned | maintenance | broken`
- **`devices.type`** — `computer | peripheral | printer | other`
- **`assignments.notes`** — Préstamos temporales guardan la fecha de devolución como prefijo:
  `[Préstamo temporal — Devolución: dd/mm/yyyy HH:mm] <notas del usuario>`
- **`employees`** — tabla sincronizada / usada como referencia de empleados en el módulo de activos;
  en el ERP se accede a través del modelo `App\Models\Empleado` (tabla `empleados` de la BD principal)
