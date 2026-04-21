# Modelos y Migraciones del Módulo Legal

## 1. Modelos

Los modelos se ubican en `app/Models/Legal/`:

- **`LegalProyecto.php`**: Representa el eje central documental (una "Consulta" o un "Escrito"). Guarda la relación con la empresa involucrada, el cliente final, la categoría a la que pertenece y un campo de detalles de resolución.
- **`LegalArchivo.php`**: Guarda el registro de todos los adjuntos subidos al `LegalProyecto`. Soporta el guardado físico de archivos (indicando su `ruta` y `mime_type`) o bien enlaces web (mediante la bandera `es_url`).
- **`LegalCategoria.php`**: Entidad jerárquica (`parent_id`) que permite crear árboles de categorías (Categoría principal -> Subcategoría) para agrupar los proyectos legales. Tiene un campo `tipo` para segregar entre consultas y escritos.
- **`LegalPagina.php`**: Modelo simple para guardar título, contenido e indicador de activo/inactivo para programas informativos del área legal.

## 2. Migraciones

Ubicadas en `database/migrations/`:

Las tablas base fueron creadas a finales de Marzo 2026 y han tenido refinamientos a lo largo de Abril:

- **`2026_03_26_120001_create_legal_categorias_table.php`**: Crea la tabla `legal_categorias` con llaves foráneas reflexivas (`parent_id`).
- **`2026_03_26_120002_create_legal_proyectos_table.php`**: Crea la tabla principal `legal_proyectos`.
- **`2026_03_26_120003_create_legal_archivos_table.php`**: Crea la tabla dependiente de archivos para relacionarla mediante `proyecto_id` a la tabla anterior.
- **`2026_03_26_130001_create_legal_paginas_table.php`**: Crea la tabla `legal_paginas`.
- **`2026_04_10_000001_make_empresa_resultado_nullable_in_legal_proyectos.php`**: Permite flexibilidad operativa para poder guardar un proyecto sin declarar la empresa final ni el resultado al inicio.
- **`2026_04_13_000002_add_tipo_to_legal_proyectos_table.php`**: Implementa el enum o string que define si es una `consulta` o un `escrito`.
- **`2026_04_13_000003_add_cliente_detalles_to_legal_proyectos_table.php`**: Expande la tabla de proyectos con información ampliada.
- **`2026_04_17_000001_add_tipo_to_legal_categorias_table.php`**: Traslada el concepto de `tipo` a nivel de Categoría para que las carpetas ya nazcan restringidas a consultas o escritos.
