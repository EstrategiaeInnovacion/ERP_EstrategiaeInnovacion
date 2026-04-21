# Revisión de Seguridad y Recomendaciones de Código (Módulo Evaluación)

El `EvaluacionController` es un archivo vital para el control de talento de la empresa. En este bloque hemos identificado múltiples incidencias de usabilidad y redundancia en el código de seguridad.

## 1. Vulnerabilidades y Riesgos

### A. Fallo Funcional: Bug en el Filtrado de Autoevaluación
**Archivo afectado**: `EvaluacionController.php` (Método `index()`)
- **Descripción**: La función `index` arma la lista de "A quiénes me toca evaluar" comprobando relaciones. El código hace esto:
```php
$query->where(function ($q) use ($me) {
    $q->where('supervisor_id', $me->id) // A mis subordinados
        ->orWhere('id', $me->supervisor_id); // A mi jefe
});
```
- **El Problema**: **Se les olvidó incluirse a ellos mismos**. La empresa pide Autoevaluaciones a los empleados, pero un empleado analista (que no tiene a nadie a su cargo) cuando entre al panel, solo va a ver a su jefe, no va a ver su propia ficha para darse click y calificar su propio cuestionario de "Soft Skills". 
- **Solución**: Se requiere parchear urgentemente la línea para que quede así: `->orWhere('id', $me->id)`.

## 2. Recomendaciones de Limpieza de Código (Refactoring)

### A. Verificaciones Redundantes de Correo
**Archivo afectado**: `EvaluacionController.php` (En todo el archivo)
- **Descripción**: Múltiples veces el controlador invoca: `$me = Empleado::where('correo', $user->email)->first();`
- **Problema**: El modelo de `User` y `Empleado` ya están vinculados como una relación natural en Eloquent de "Uno a Uno" (`$user->empleado`). Realizar cruces y búsquedas de strings por `email` contra `correo` (que pueden ser propensas a errores tipográficos, diferencias de mayúsculas o espacios accidentales) es mala práctica en Laravel.
- **Sugerencia de Limpieza**: Usar siempre `$me = Auth::user()->empleado;`. Es la manera canónica y rápida a nivel base de datos.

### B. Funciones Privadas Duplicadas (DRY - Don't Repeat Yourself)
**Archivo afectado**: `EvaluacionController.php` vs `User.php`
- **Descripción**: En el controlador existen las funciones privadas `isAdminRH()` y `hasFullVisibility()`. Estas funciones hacen búsquedas idénticas de las cadenas "administracion rh", "recursos humanos", etc., repitiendo la misma lógica que existe en la función pública `$user->isRh()` dentro del modelo central `User.php`.
- **Sugerencia**: Eliminar las funciones privadas del controlador y confiar en la lógica central de seguridad del Modelo. Si RH añade una nueva palabra clave al departamento, se actualizará en `User.php` pero el evaluador se romperá si el controlador no se actualiza a la par.

### C. Bloques `if/elseif` para Selección de Criterios (Hardcoding)
**Archivo afectado**: `EvaluacionController.php` (Método `show()`)
- **Descripción**: El método contiene una lista masiva mapeando puestos (ej. `if 'anexo 24'` -> `área = Anexo 24`) para tratar de adivinar qué examen aventarle a la pantalla del empleado.
- **Sugerencia**: Mover esto a una columna en la tabla de `empleados` o de `departamentos` llamada `area_evaluacion`. El Controlador solo tendría que hacer `CriterioEvaluacion::where('area', $target->area_evaluacion)->get()`. De esta forma, si nace un nuevo departamento técnico, no es necesario mandar a un programador a inyectar código en el Controlador, simplemente se asigna desde el panel gráfico.
