# Controladores del Módulo RH

Los controladores se encuentran en `app/Http/Controllers/RH/`.

## 1. `ExpedienteController.php`
Encargado de la gestión de la vida del empleado dentro de la empresa.
- **`index()`, `show()`**: Listado y vista a detalle (Checklist de documentos de RH requeridos).
- **`update()`**: Modificación de perfil del empleado (Dirección, Teléfonos, Emergencias).
- **`uploadDocument()`, `deleteDocument()`, `downloadDocument()`**: Control físico de archivos PDF, Fotos, etc. vinculados al empleado.
- **`importFormatoId()`**: (Característica Especial) Sube un Excel llamado "Formato ID" y extrae (parsea) su contenido utilizando `PhpSpreadsheet` para autocompletar la base de datos de manera dinámica con la información del empleado.
- **`darDeBaja()`, `reactivar()`**: Gestor de estado del empleado. Deshabilita el acceso al sistema del usuario asociado.

## 2. `RelojChecadorImportController.php`
Gestión avanzada de tiempo y asistencia.
- **`index()`, `equipo()`**: Despliega un panel con KPIs (Faltas, Retardos, Total de Horas Trabajadas). El método `equipo()` está diseñado para que coordinadores/directores vean solo a su personal a cargo.
- **`start()`, `progress()`**: Importación asíncrona de un Excel crudo proveniente de un reloj checador biométrico (ZKTeco/Similares) usando `ProcesarAsistenciaService`.
- **`store()`, `storeManual()`, `update()`**: Control para añadir justificaciones (vacaciones, incapacidades) y manipulación masiva de registros.
- **`revertir()`, `revertirRango()`, `clear()`, `clearRango()`**: Utilidades de limpieza de base de datos para corrección de errores en la importación.
- **`enviarAviso()`**: Genera notificaciones en el dashboard del empleado y manda un correo de sanción/aviso utilizando `AvisoAsistenciaMailable`.

## 3. `CapacitacionController.php`
Plataforma de adiestramiento.
- Administra cursos en video o manuales. Permite subir múltiples `adjuntos` (PDFs/Presentaciones).
- Segrega las capacitaciones para que solo se muestren a ciertos puestos, categorías o de forma global a la empresa.

## 4. `RecordatorioController.php`
- Automatización de fechas cívicas o personales. Muestra de un vistazo los próximos cumpleaños, aniversarios de trabajo de los empleados y permite crear recordatorios manuales.

## 5. `DiaFestivoController.php`
- CRUD para configurar el calendario de la empresa e indicar al sistema de asistencias cuáles días no deben marcarse como falta por ser festividades.
