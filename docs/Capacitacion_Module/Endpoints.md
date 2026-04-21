# Endpoints del Módulo Capacitación

Rutas típicamente anidadas dentro del prefijo general de los empleados para la vista pública y agrupadas bajo middleware de RH para las transacciones.

## Vista de Empleado (Catálogo)
- `GET /capacitacion`: Vista principal tipo "Netflix" que lista todas las tarjetas de los cursos a los que la persona tiene acceso, agrupadas por su categoría.
- `GET /capacitacion/{id}`: Reproductor de video. Muestra la pantalla del reproductor (HTML5 o el Iframe de Youtube) y dibuja los enlaces de descarga directa hacia el sistema de Storage de los adjuntos.

## Vista Administrativa (RH / Gestor de Contenido)
- `GET /rh/capacitacion/manage`: Carga el "Backend" o matriz de tabla de datos de todos los videos subidos a la plataforma con el botón de añadir nuevo.
- `GET /rh/capacitacion/{id}/edit`: Carga el formulario con los datos pre-rellenados para actualizar permisos o cambiar de archivo de video.
- `POST /rh/capacitacion`: Sube un archivo pesado (Video) o adjuntos y lo inscribe en BD.
- `PUT /rh/capacitacion/{id}`: Actualiza los metadatos. Puede encargarse de destruir el MP4 viejo si el gestor cambió la capacitación a un link de YouTube.
- `DELETE /rh/capacitacion/{id}`: Borra totalmente el curso y manda la orden a Laravel Storage de limpiar el disco duro para no dejar huellas ni archivos huérfanos de videos pesados.
- `DELETE /rh/capacitacion/adjunto/{id}`: Función dedicada para borrar solo 1 adjunto en caso de que se haya equivocado de PDF.
