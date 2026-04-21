# Revisión de Seguridad y Recomendaciones de Código (Módulo Proyectos)

El `ProyectoController` está estructurado bajo buenos principios de seguridad. En todos sus métodos incluye blindaje explícito `abort(403)` para validar que si el usuario manipuló el URL, este no pase si no le pertenece el proyecto o no es coordinador de RH. 

A continuación los hallazgos técnicos.

## 1. Vulnerabilidades Críticas

### A. Bloqueo de Hilo Principal (Synchronous Mail Sending)
**Archivo afectado**: `ProyectoController.php` (Métodos `store`, `asignarUsuarios`, `asignarResponsablesTi`)
- **Descripción**: Al momento de agregar usuarios al proyecto, el código hace un bucle y envía correos electrónicos de manera síncrona.
```php
foreach ($request->usuarios as $usuarioId) {
    ...
    Mail::to($usuario->email)->send($correo);
}
```
- **Riesgo Operativo (Denegación de Servicio)**: Si Recursos Humanos decide crear un macro-proyecto e invita a 50 empleados al mismo tiempo de la casilla de select múltiple, el servidor va a intentar conectarse a Office365 / SMTP 50 veces seguidas en tiempo real. Esto congelará por completo el explorador web de Recursos Humanos durante varios minutos, arriesgando un "Gateway Timeout (504)".
- **Solución Obligatoria**: Implementar las **Colas de Laravel (Queues)**. Debería cambiarse la palabra `send` por `queue`.
Ejemplo: `Mail::to($usuario->email)->queue($correo);`

## 2. Recomendaciones de Limpieza de Código (Refactoring)

### A. Problema de Rendimiento de Consultas en Ciclo (N+1 Query Problem)
**Archivo afectado**: `ProyectoController.php` (Método `index()`)
- **Descripción**: Al enlistar las tarjetas de los proyectos, el sistema invoca una función anónima para mapear la colección entera usando conteos directos de Eloquent.
```php
$proyectosConActividades = $proyectos->map(function ($p) {
    $p->total_actividades = $p->actividades()->count();
    $p->actividades_pendientes = $p->actividades()->whereNotIn('estatus', ['Completado', 'Rechazado'])->count();
    return $p;
});
```
- **Riesgo**: Si hay 100 proyectos archivados en pantalla, el framework le va a disparar a la base de datos MySQL más de 200 miniconsultas independientes, colapsando el rendimiento visual del dashboard.
- **Solución recomendada**: Reemplazar todo el mapeo utilizando las funciones de agregación nativas de Eloquent en la consulta maestra: `->withCount('actividades')`.

### B. Funciones Huérfanas de Mapeo
**Archivo afectado**: `ProyectoController.php` (Método `listaUsuarios`)
- **Descripción**: Cuenta con un endpoint de API solitario (`listaUsuarios`) que devuelve en formato JSON todos los empleados activos de la compañía. Sin embargo, al analizar todas las demás funciones (ej. `editarActividad()`), en lugar de consumir esta API, el controlador prefiere hacer la consulta a la base de datos una y otra vez en cada controlador: `$usuarios = User::whereHas('empleado', fn ($q) => $q->where('es_activo', true))->orderBy('name')->get();`
- **Sugerencia**: Reutilizar el bloque de la API mediante VueJS/AJAX en las vistas Frontend, o bien, si todo es renderizado desde backend por Blade, retirar el endpoint JSON inutilizado.
