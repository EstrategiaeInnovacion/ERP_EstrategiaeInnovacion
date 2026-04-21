# Modelos y Migraciones del Módulo Evaluación

## 1. Modelos

En `app/Models/` se encuentran los modelos relacionados a las evaluaciones:

- **`Evaluacion.php`**: El encabezado maestro. Relaciona a quién se está evaluando (`empleado_id`) y quién lo está calificando (`evaluador_id`). Almacena el `promedio_final` y los comentarios generales que se guardaron en ese `periodo`.
- **`EvaluacionDetalle.php`**: Las filas hijas. Contiene la respuesta exacta (calificación) y observaciones a cada uno de los criterios.
- **`CriterioEvaluacion.php`**: El catálogo del examen. Contiene la pregunta/criterio, a qué área de la empresa le pertenece, y su `peso` porcentual (para ponderar preguntas más importantes que otras).
- **`EvaluacionVentana.php`**: El administrador de temporadas. Una tabla simple que guarda un nombre (Ej. "Evaluación Semestre 1 2026"), sus fechas de apertura y de cierre para inhabilitar el sistema si la fecha expiró.

## 2. Migraciones

Ubicadas en `database/migrations/`:

- **`2025_12_26_163958_create_evaluacions_table.php`**: Base inicial para guardar la cabecera, `promedio_final`, `empleado_id` y `evaluador_id`.
- **`2025_12_26_164009_create_evaluacion_detalles_table.php`**: Tabla hija que se une mediante `evaluacion_id`.
- **`2025_12_26_164030_create_criterios_evaluacion_table.php`**: Catálogo original para las preguntas pre-creadas por Recursos Humanos.
- **`2026_03_05_130000_create_evaluacion_ventanas_table.php`**: Parche evolutivo. Anteriormente las fechas estaban programadas en código duro en el Controlador (Junio y Diciembre). Se introdujo la "Ventana de Evaluación" para controlarlo desde interfaz de usuario.
- **`2026_03_05_130500_add_ventana_id_to_evaluaciones.php`**: Añadió la llave foránea al encabezado de las evaluaciones para ligarlo correctamente con la ventana activa en vez del texto plano del `periodo`.
