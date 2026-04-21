# Endpoints del Módulo de RH

Todas las rutas están protegidas bajo autenticación, correo verificado y middleware de área de acceso: `['auth', 'verified', 'area.rh']`. El prefijo global es `/rh`.

## Dashboard (`/rh`)
- `GET /`: Panel principal que direcciona al dashboard del departamento.

## Expedientes y Empleados (`/rh/expediente`)
- `GET /`: Renderiza el listado (Buscador y filtros de altas/bajas).
- `POST /`: Creación de un nuevo expediente en blanco.
- `GET /{id}/edit` y `PUT /{id}`: Modificación del registro.
- `POST /{id}/baja`: Endpoint para iniciar el proceso de desactivación del sistema del usuario y baja laboral.
- `POST /{id}/documentos`: Carga de documentos de cumplimiento (PDF, JPG).
- `DELETE /documentos/{id}`: Eliminación de documentación adjunta.

## Reloj Checador (`/rh/reloj-checador`)
- `GET /`: Renderiza el Dashboard con KPIs de puntualidad.
- `GET /equipo`: Versión reducida del dashboard, mostrando únicamente a los subordinados directos (requiere rol de coordinador/supervisor).
- `POST /import`: Inicia la importación procesada de asistencias (`ProcesarAsistenciaService`).
- `GET /progress/{key}`: (API) Consulta del estado de avance en % del archivo subido.
- `POST /asistencias`: Crea de manera masiva registros con justificaciones usando la matriz del front-end.
- `PUT /asistencias/{id}`: Justificación individual.

## Avisos de Asistencia (`/rh/avisos-asistencia`)
- `GET /`, `POST /`, `PUT /{id}`, `DELETE /{id}`: CRUD del catálogo de faltas/retardos graves.
- `PUT /{id}/status`: Resuelve un ticket de aviso entre empleado y RH.
- `POST /enviar`: Envía el correo de llamado de atención.

## Capacitación (`/rh/capacitacion`)
- `GET /`: Visor o catálogo de cursos (Lado Usuario final).
- `GET /admin`: Listado para editar cursos y asignar audiencias (Lado RH).
- `POST /`, `GET /{id}/edit`, `PUT /{id}`, `DELETE /{id}`: ABM completo de las capacitaciones.
- `POST /{id}/adjuntos`, `DELETE /adjuntos/{adjuntoId}`: Carga de material de estudio extra.

## Recordatorios (`/rh/recordatorios`)
- `GET /`: Despliega lista combinada entre Cumpleaños (Sistema Central) y Custom (Creados a mano).
- `POST /import`: Ingresa múltiples cumpleaños / fechas aniversario.
- `POST /custom`: Crea un aviso único en el calendario.
- `DELETE /custom/{id}`: Borra un aviso del calendario.

## Días Festivos (`/rh/dias-festivos`)
- `GET /`, `POST /`, `PUT /{id}`, `DELETE /{id}`: ABM clásico para establecer días de inhabilidad general.
