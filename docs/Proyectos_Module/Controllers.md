# Controladores del Módulo Proyectos

La operación se concentra en `app/Http/Controllers/ProyectoController.php`.

## 1. Vistas Generales
- **`index()`**: Despliega el catálogo de proyectos activos o archivados (`?archivado=1`). Determina inteligentemente qué proyectos ves dependiendo de si eres creador, miembro del equipo de trabajo, o jefe de alguien que está en el proyecto.
- **`show($id)`**: Vista a detalle del proyecto. Incluye el resumen de sus integrantes y calcula mediante la función del modelo cuál es la próxima fecha agendada para reunión basada en la `recurrencia` del proyecto (semanal, quincenal, mensual).

## 2. Gestión del Proyecto (Exclusivo RH Coordinador)
- **`store() / update()`**: Permite el alta y modificación. Emite alertas de correo (`Mail::to()->send()`) utilizando la plantilla transaccional `\App\Mail\ProyectoAsignado`.
- **`asignarUsuarios()` / `asignarResponsablesTi()`**: Permiten inyectar a múltiples empleados de la empresa como colaboradores o apoyo técnico del proyecto en lotes (batch assign).
- **`quitarUsuario()` / `quitarResponsableTi()`**: Expulsa a un empleado del proyecto y de la visibilidad del mismo.

## 3. Conexión con Actividades (Tasks)
- **`actividades($proyectoId)`**: Trae todo el historial Kanban de las Actividades (Tasks) ligadas a este `proyectoId` y cuenta los KPI.
- **`guardarActividad()`, `actualizarActividad()`, `eliminarActividad()`**: Versiones espejo del controlador de Actividades original, pero puenteadas para forzar el `proyecto_id` en cada nueva inserción.

## 4. Ciclo de Vida
- **`destroy()`**: Archiva el proyecto de la vista de los operadores.
- **`restore()`**: Lo devuelve a activo.
- **`forceDelete()`**: Lo elimina completamente de la faz de la base de datos (Destrucción total). Limpia sabiamente las llaves foráneas primero con `detach()` y `delete()` antes de borrar el registro padre.
- **`finalizar()`**: Congela el proyecto marcando su `fecha_fin_real`.
- **`reporte()`**: Renderiza una hoja de sumario gerencial de cuantas tareas se lograron a tiempo contra las que se retrasaron.
