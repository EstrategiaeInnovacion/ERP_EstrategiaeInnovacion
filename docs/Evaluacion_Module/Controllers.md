# Controladores del Módulo Evaluación

La operación está centralizada en `app/Http/Controllers/EvaluacionController.php`.

## 1. `EvaluacionController.php`
- **`index()`**: Despliega la matriz de empleados a evaluar. Posee una lógica de visibilidad muy estricta. Si el usuario es Dirección o Recursos Humanos, carga toda la base de empleados. Si el usuario es supervisor o analista, solo carga su jefe directo (para evaluarlo) y a sus subordinados directos.
- **`show($id)`**: Es el orquestador del cuestionario. Mapea qué rol tiene el usuario logueado frente al usuario destino (Target). Si descubre que está evaluando a su jefe, inyecta los criterios de `Evaluacion Supervisor`. Si detecta que evalúa a su subordinado, inyecta una amalgama entre habilidades blandas de RH y el cuestionario técnico de su área (`getTechnicalArea($posicion)`).
- **`store()` / `update()`**: Guarda las calificaciones (1 al 5) enviadas desde el front-end. Calcula matemáticamente el `promedio_final` utilizando un sistema de promedios ponderados en base al `peso` de cada pregunta registrada en BD. Envuelve todo en una `DB::transaction()`.
- **`resultados($id)`**: Portal analítico disponible solo para la Dirección o RH. Recopila todas las evaluaciones de 180° que le hicieron al empleado en un semestre y le genera un resumen y calificación promediada.
- **`getVentanas()`, `saveVentana()`, `toggleVentana()`**: Métodos CRUD utilizados exclusivamente por el Administrador de RH para abrir o cerrar la temporada de evaluaciones (ej: *Temporada Enero - Junio 2026*).
