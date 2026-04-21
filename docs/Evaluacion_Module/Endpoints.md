# Endpoints del Módulo Evaluación

Rutas registradas en el prefijo `/rh/evaluacion`. El acceso es otorgado tras la verificación de permisos y sesión activa.

## Portal Principal
- `GET /`: Despliega la matriz o listado de personas que el usuario conectado tiene el "deber" de evaluar. Permite usar parámetros en la URL (ej: `?periodo=2026 | Enero - Junio`).
- `GET /{id}`: Levanta el formato de preguntas (Cuestionario) adaptado a la relación que haya entre el usuario conectado y el empleado `{id}`.

## Transacciones
- `POST /`: Recibe el arreglo gigantesco de respuestas numéricas (radios) y caja de texto mediante formulario y lo distribuye matemáticamente en BD.
- `PUT /{id}`: Re-envía un formulario modificado si es que el contador `edit_count` del empleado aún lo permite y si la ventana sigue abierta.
- `DELETE /{id}`: Exclusivo de Recursos Humanos. Borra completamente una evaluación si se determina que se hizo de mala fe o con sesgos para que el evaluador la deba repetir.

## Consultas de Recursos Humanos
- `GET /{id}/resultados`: Endpoint analítico. Solo disponible para RH y Dirección. Realiza una sumatoria general combinando lo que opinó el jefe, el subordinado y RH sobre el mismo empleado `{id}` para arrojar un veredicto y promedio final de la temporada.

## Ventanas (Administrativo)
Prefijo interno para la carga dinámica de temporadas. Responden en formato JSON.
- `GET /ventanas/list`: Solicita la lista de temporadas disponibles.
- `POST /ventanas`: Inserta una nueva temporada y la designa como "Activa".
- `POST /ventanas/{id}/toggle`: Enciende o apaga prematuramente una temporada activa de evaluación, bloqueando la escritura en toda la empresa.
