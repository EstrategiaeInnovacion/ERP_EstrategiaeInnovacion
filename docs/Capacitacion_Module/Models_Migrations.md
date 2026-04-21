# Modelos y Migraciones del Módulo Capacitación

## 1. Modelos

En `app/Models/` se encuentran dos modelos vinculados:

- **`Capacitacion.php`**: El video en cuestión. Contiene los campos JSON para permisos. Posee un método `isVisibleFor(User $user)` que contiene una lógica robusta de expresiones regulares (Regex) para evitar coincidencias erróneas en nombres de puestos (Ej. evita que el puesto "Operador de Logística" coincida mágicamente por accidente con la regla "Operador"). También contiene extractores de URL para formatear y empotrar videos de YouTube (`getYoutubeId()`).
- **`CapacitacionAdjunto.php`**: Un modelo sencillo (hijo) que almacena el nombre del PDF/Word anexado y la ruta (`archivo_path`) a la carpeta `storage/app/public/capacitacion_docs/`.

## 2. Migraciones

- **`create_capacitaciones_table.php`**: Crea el contenedor de los campos string básicos y JSON `puestos_permitidos` y `usuarios_permitidos`. 
- **`create_capacitacion_adjuntos_table.php`**: Define la foreign key hacia `capacitaciones(id)`. Es vital que esta migración contenga la cascada (`onDelete('cascade')`) a nivel base de datos para asegurar que los registros se limpien al borrar la capacitación padre.
