# Controladores del Módulo Capacitación

La operación está centralizada en `app/Http/Controllers/RH/CapacitacionController.php`.

## 1. Vistas Públicas (Para toda la empresa)
- **`index()`**: Descarga todo el catálogo de videos de la base de datos y usa el método del modelo `isVisibleFor($user)` para filtrar en la memoria (Backend) qué videos se le deben mandar a la vista agrupados por Categoría.
- **`show($id)`**: Visualizador del video. Trae el video y sus adjuntos. Realiza un re-check de seguridad invocando `isVisibleFor` de nuevo. Si el usuario adivinó o manipuló el URL del video al que no tiene acceso, dispara un `abort(403)`.

## 2. Vistas de Administración (Exclusivo RH)
- **`manage()`**: El listado CRUD. Extrae dinámicamente de la base de datos de Empleados todos los `puestos` existentes (`pluck('posicion')`) para armar la lista desplegable de permisos, así como la lista de usuarios.
- **`store()`**: Recibe el payload del formulario. Utiliza un requerimiento dinámico `required_without:youtube_url` para forzar a que envíen un archivo MP4 si no enviaron un link. Usa `Storage::disk('public')->store()` para los MP4 y los adjuntos.
- **`update($id)`**: Actualiza un registro existente. Si el administrador decide reemplazar el archivo de video por uno de YouTube, la función elimina permanentemente (`Storage::delete`) el viejo archivo MP4 del disco duro para ahorrar espacio.
- **`destroy($id)`**: Elimina toda la capacitación. Destruye recursivamente los archivos de disco duro de los adjuntos relacionados, destruye el MP4 y luego invoca el borrado del modelo.
- **`destroyAdjunto($id)`**: Endpoint utilitario que permite a RH borrar solamente un PDF que se haya subido por error a una capacitación sin tener que borrar todo el curso.
