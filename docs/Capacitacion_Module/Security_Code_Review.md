# Revisión de Seguridad y Recomendaciones de Código (Módulo Capacitación)

El módulo `CapacitacionController` y el modelo `Capacitacion` están extremadamente bien construidos. Representan uno de los módulos más seguros y robustos encontrados hasta ahora en la auditoría. Sin embargo, hay algunos detalles minúsculos de rendimiento que podrían optimizarse.

## 1. Puntos Fuertes (A mantener)

* **Seguridad en Permisos (Boundary Regex)**: El desarrollador tuvo la excelente idea de utilizar expresiones regulares con `\b` (word boundaries) en `Capacitacion::isVisibleFor()`. Esto evita fallos clásicos de seguridad donde el puesto "Soporte de TI" podría heredar los cursos secretos del puesto directivo "TI", debido a que las letras de una palabra caben dentro de la otra.
* **Manejo de Archivos Físicos**: Se tuvo el cuidado de usar `Storage::disk('public')->delete()` tanto al eliminar un video o documento, como al actualizarlo (por ejemplo, si suben un MP4 y luego cambian de opinión para poner el link de YouTube, el sistema no deja el MP4 flotando como archivo "basura" o huérfano, sino que lo destruye inmediatamente).
* **Bloqueo Doble**: A diferencia de otros sistemas, el controlador no se confía en que la vista ya ocultó los videos. Si un empleado copia el link directo del video y trata de entrar, el método `show()` en el controlador vuelve a re-validar los permisos en backend mandando un error 403 si no tiene acceso.

## 2. Oportunidades de Mejora y Riesgos Menores

### A. Rendimiento: Carga Excesiva en Listados de Permisos
**Archivo afectado**: `CapacitacionController.php` (`manage()` y `edit()`)
- **Descripción**: Para armar el select múltiple de "Puestos Permitidos", el controlador lanza esta consulta: `$puestos = \App\Models\Empleado::distinct()->pluck('posicion')->filter()->sort()->values();`
- **Problema**: Esto escanea absolutamente toda la tabla histórica de la compañía. Traerá nombres de puestos con errores ortográficos de ex-empleados despedidos hace años o puestos que ya no se usan.
- **Solución recomendada**: Filtrar para usar el catálogo vivo: `$puestos = Empleado::where('es_activo', true)->distinct()->pluck('posicion')...`

### B. Riesgo Base de Datos: Fallo en Cascada Oculto (Orphan Rows)
**Archivo afectado**: `CapacitacionController.php` (`destroy()`)
- **Descripción**: El controlador hace un foreach para borrar los PDFs (`adjuntos`) del disco duro y termina con un `$video->delete()`. Sin embargo, no hace explícitamente `$adjunto->delete()`.
- **Riesgo**: Se está confiando ciegamente en que la migración en MySQL tenga la directiva estricta `onDelete('cascade')`. Si por alguna razón la base de datos no tiene la llave foránea en cascada, los archivos físicos en disco duro se van a borrar (lo cual es bueno), pero las filas vacías de la base de datos de los adjuntos se van a quedar ahí eternamente, apuntando a un video que ya no existe.
- **Solución**: Para mayor certeza de software, colocar un `$video->adjuntos()->delete();` antes de la línea `$video->delete();`.
