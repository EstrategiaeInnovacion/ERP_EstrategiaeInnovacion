# Modelos y Migraciones del Módulo Proyectos

## 1. Modelos

- **`Proyecto.php`**: El contenedor central. Soporta borrado lógico suave (`SoftDeletes`) además del flag de archivado.
    - Implementa funciones matemáticas complejas como `metricas()`: Un método que viaja por la relación hacia el modelo `Activity` (del módulo 1), y recopila cuántas actividades tienen 100% de eficacia, cuántas tienen retardo y promedia todos los resultados.
    - Contiene el calculador automático `siguienteFechaJunta()`, que mediante un switch de recurrencia (semanal, quincenal, mensual) te indica en qué fecha debe realizarse la siguiente mesa de control.

- **Relaciones Polimórficas (Pivot)**:
    - Utiliza múltiples tablas pivote M:N (Many to Many). Una persona puede pertenecer a varios proyectos, y un proyecto tener muchos integrantes. Se divide esto entre `usuarios()` y `responsablesTi()`.

## 2. Migraciones

Se sospecha la existencia de 3 migraciones clave:

- **`create_proyectos_table.php`**: La tabla pivote que alberga los campos `fecha_inicio`, `fecha_fin`, el enum `recurrencia` y los booleanos de `archivado`/`finalizado`.
- **`create_proyecto_usuarios_table.php`**: Tabla pivote simple uniendo `proyecto_id` y `usuario_id` para el equipo general.
- **`create_proyecto_responsables_ti_table.php`**: Tabla pivote aislada uniendo los mismos IDs pero separando semánticamente a los miembros de la gerencia técnica.
