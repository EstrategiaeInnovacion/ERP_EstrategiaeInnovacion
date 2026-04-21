# Controladores del Módulo Actividades

El módulo recae completamente sobre un único controlador central: `ActivityController.php`.

## 1. `ActivityController.php` (Core)
- **`index()`**: Es el motor de renderizado principal. Lee de forma inteligente qué rol posee el usuario (`esDireccion`, `esSupervisor`) para filtrar la matriz de actividades en pantalla y calcular los KPI visuales. Resuelve rangos de fecha flexibles (semana, mes, trimestre, custom).
- **`store()`**: Permite la creación unitaria de actividades. Implementa **reglas de jerarquía**: Si un jefe asigna a un empleado, nace en `Planeado`. Si el empleado se auto-asigna algo o le asigna algo a otro empleado, nace en `Por Aprobar`.
- **`storeBatch()`**: Un "Planeador Semanal". Recibe por POST un `array` grande de un calendario web y registra masivamente todas las actividades de lunes a viernes. Está blindado para que solo se use los lunes de 9:00am a 11:00am.
- **`update()`**: La lógica principal de actualización y cierres. Bloquea que un analista pase una tarea directo a `Completado`, forzándolo al estatus `Por Validar` y que el jefe decida si de verdad la terminó.
- **`start()`, `validateCompletion()`, `approve()`, `reject()`**: Funciones de cambio rápido de estatus usadas desde la UI con botones accionables.
- **`exportExcel() / generateClientReport()`**: Métodos para generar sábanas de datos utilizando `PhpOffice\PhpSpreadsheet` y reportes en formato imprimible PDF.

---
**Nota de Planeación:** Existen también un trío de funciones (`getPlaneacionVentanas`, `savePlaneacionVentana`, `deletePlaneacionVentana`) que permiten que un usuario administrador abra ventanas de planeación de excepción (ejemplo: si se le pasó a alguien llenar el planeador el lunes, RH le puede abrir la plataforma de nuevo el martes por un par de horas).
