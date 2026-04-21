# Endpoints del Módulo Proyectos

## Panel Principal
- `GET /proyectos`: Lista en estilo tarjetas todos los proyectos. Soporta el querystring `?archivado=1` para el historial.
- `POST /proyectos`: Registra el alta en la base de datos y despacha correos a los involucrados.
- `GET /proyectos/{id}`: Dashboard panorámico del proyecto.
- `PUT /proyectos/{id}`: Formulario de actualización de fechas y recurrencia.

## Ciclo de Vida del Proyecto
- `DELETE /proyectos/{id}`: Lo archiva suavemente.
- `POST /proyectos/{id}/restore`: Des-archiva el proyecto para revivirlo.
- `DELETE /proyectos/{id}/force`: Módulo destructivo. Borra en cascada pivotes, tareas y el proyecto mismo.
- `POST /proyectos/{id}/finalizar`: Fija la `fecha_fin_real` y lo cataloga como caso de éxito/fracaso completado.
- `GET /proyectos/{id}/reporte`: Carga la hoja generada por las métricas consolidadas post-mortem.

## Equipo de Trabajo (M:N)
- `POST /proyectos/{id}/usuarios`: Asignación masiva (array de checkboxes) al área operativa.
- `POST /proyectos/{id}/ti`: Asignación masiva al área de sistemas.
- `DELETE /proyectos/{id}/usuarios/{userId}`: Expulsión individual de operativos.
- `DELETE /proyectos/{id}/ti/{userId}`: Expulsión individual de sistemas.

## Integración con Módulo de Actividades
- `GET /proyectos/{id}/actividades`: El tablero Kanban del proyecto.
- `POST /proyectos/{id}/actividades`: Inserción de un "Ticket" o "Task".
- `PUT /proyectos/{id}/actividades/{actividadId}`: Actualización del avance de una tarea ligada al proyecto.
- `DELETE /proyectos/{id}/actividades/{actividadId}`: Baja de la tarea.
