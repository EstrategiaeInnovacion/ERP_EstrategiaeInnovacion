# Modelos y Migraciones del Módulo Actividades

## 1. Modelos

En `app/Models/` se encuentran tres modelos relacionados a la planificación:

- **`Activity.php`**: El pilar central. Contiene un observer complejo en su método `boot()`. Cada vez que se manda a guardar (evento `saving`), el modelo recalcula automáticamente sus campos de negocio `metrico`, `resultado_dias` y su porcentaje de eficiencia basándose en los objetos Carbon de su `fecha_inicio`, `fecha_compromiso` y `fecha_final`.
- **`ActivityHistory.php`**: Un modelo ligero utilizado a modo de log/auditoría. Documenta quién movió qué tarea y a qué hora.
- **`PlaneacionVentana.php`**: Un modelo de configuración para el sistema, utilizado para saber qué días de la semana y a qué horas un empleado tiene permitido llenar el módulo de `storeBatch()` (Planeador).

## 2. Migraciones

Ubicadas en `database/migrations/`:

- **`2026_01_XX_create_activities_table.php`** y **`create_activity_histories_table.php`**: Las tablas primigenias que contienen el ID del empleado que debe realizar la tarea y las fechas núcleo de programación.
- **`2026_02_20_100000_add_planeacion_ventanas_table.php`**: Añadió el control para que RH defina si el planeador se llena los Lunes de 9 a 11, o los Viernes, etc.
- **`2026_03_XX_add_asignado_por_to_activities.php`**: Modificación crucial que rompe el paradigma antiguo donde "siempre el usuario actual es el dueño de la actividad". Ahora soporta el concepto de "Yo te asigno esta tarea a ti", separando `user_id` (quien la hace) de `asignado_por` (quien la exige).
- **`2026_04_05_add_proyecto_id_to_activities_table.php`**: Permite anclar y rastrear una actividad contra un presupuesto y proyecto grande de la compañía.
