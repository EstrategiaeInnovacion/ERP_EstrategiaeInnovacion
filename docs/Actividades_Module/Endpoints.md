# Endpoints del Módulo Actividades

Todas estas rutas se encuentran agrupadas típicamente en `routes/web.php` bajo el middleware de autenticación `['auth', 'verified']`.
A diferencia de otros módulos, el de Actividades no usa un prefijo de URI genérico, sino que va directamente atado a `/activities`.

## Flujo Operativo (`/activities`)
- `GET /activities`: Despliega el Dashboard del empleado o supervisor con las tarjetas en vista "Kanban" y la tabla de resumen.
- `POST /activities`: Crea una tarea singular.
- `POST /activities/batch`: Recibe un payload JSON o de Formulario muy pesado conteniendo el listado de tareas planificadas para toda la semana.
- `PUT /activities/{id}`: Actualiza textualmente los datos, hora y evidencias de una actividad, dejando un rastro en el historial.
- `DELETE /activities/{id}`: Función protegida, solo disponible para directores o supervisores que crearon la tarea.

## Acciones de Estado (Transiciones)
- `POST /activities/{id}/start`: Permite al usuario marcar que arrancó el trabajo de la actividad asignada, pasando de `Planeado` a `En Proceso`.
- `POST /activities/{id}/validate`: (Solo supervisores) Cierra exitosamente una tarea que estaba esperando en el status `Por Validar`.
- `POST /activities/{id}/approve`: (Solo supervisores) Da luz verde a una asignación propuesta (Pasa de `Por Aprobar` a `Planeado`).
- `POST /activities/{id}/reject`: (Solo supervisores) Batea la propuesta o la tarea completada, solicitando que el empleado la retome por error en la ejecución.

## Reportes y Exportación
- `GET /activities/export-excel`: Compila un reporte utilizando el componente PhpSpreadsheet y fuerza la descarga al navegador de un reporte exhaustivo multi-usuario de tiempos y eficiencias.
- `GET /activities/client-report`: Descarga o muestra un documento PDF de las tareas realizadas vinculadas a un `cliente` determinado para enviarlo como justificación de horas cobradas o servicio.

## Ventanas de Planeación (`/admin/planeacion-ventanas`)
- `GET /`, `POST /`, `DELETE /{id}`: Rutas asíncronas (Devuelven JSON) utilizadas por el rol Coordinador para administrar las horas en las que los empleados pueden rellenar su planeador (ej: Lunes 09:00 - 11:00).
