# Endpoints del Módulo Legal

Todas las rutas están protegidas bajo autenticación, correo verificado y middleware de área de acceso: `['auth', 'verified', 'area.legal']`. El prefijo global para las URLs es `/legal`.

## Dashboard (`/legal`)
- `GET /`: Panel principal o vista de bienvenida (Dashboard Legal).

## Matriz de Consultas (`/legal/matriz`)
- `GET /`: Renderiza el listado maestro de proyectos, aplicando filtros GET (`empresa`, `buscar`, `categoria_id`, `tipo`).
- `POST /`: Crea un nuevo proyecto (consulta o escrito) y sube los archivos proporcionados por HTTP POST multipart.
- `GET /{id}`: Devuelve la vista de detalle del proyecto o la respuesta JSON según el header de la solicitud (`expectsJson`).
- `PUT /{id}`: Actualiza textualmente el cuerpo de un proyecto existente.
- `DELETE /{id}`: Borra físicamente del disco y de la base de datos el proyecto junto con sus archivos.
- `DELETE /archivo/{id}`: Método asíncrono para eliminar exclusivamente un documento adjunto.
- `GET /archivo/{id}/download`: Resuelve la ruta segura para forzar la descarga de un documento al navegador.

## Categorías (`/legal/categorias`)
- `GET /`: Listado administrativo.
- `POST /`: Creación rápida de nuevas ramificaciones o carpetas.
- `DELETE /{id}`: Eliminación.

## Programas / Páginas Legales (`/legal/programas`)
- `GET /`: Visor de listado.
- `POST /`: Crea nuevo texto/programa legal.
- `PUT /{id}`: Edita contenido.
- `DELETE /{id}`: Elimina contenido.

## Herramientas de Digitalización VUCEM (`/legal/digitalizacion`)
Estas rutas retornan predominantemente payloads JSON.
- `GET /`: Despliega la herramienta front-end.
- `POST /convertir`: Sube un PDF, lo rebaja a 300 DPI y lo devuelve en escala de grises. Opcionalmente fragmenta el archivo en N pedazos si el tamaño o flag lo exige.
- `POST /validar`: Realiza verificaciones técnicas (DPI, Escala Grises, Contraseña) sin alterar el PDF original, devolviendo el estado.
- `POST /comprimir`: Exprime el PDF usando uno de los cuatro perfiles (`screen, ebook, printer, prepress`).
- `POST /combinar`: Recibe en el Request un arreglo `files[]` y devuelve un solo PDF concatenado.
- `POST /extraer`: Extrae objetos `Image` incrustados en el binario del PDF y devuelve un comprimido ZIP con las imágenes.
