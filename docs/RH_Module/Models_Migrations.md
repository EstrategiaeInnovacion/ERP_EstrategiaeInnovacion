# Modelos y Migraciones del Módulo RH

## 1. Modelos

Los modelos se encuentran en la raíz `app/Models/` (compartidos con el Core del sistema debido a su transversalidad):

- **`Empleado.php`**: El modelo principal. Mapea la tabla `empleados`, maneja las relaciones directas con los expedientes, asistencias, usuario de sistema, vacaciones y estructura organizacional (supervisor/subordinados).
- **`EmpleadoDocumento.php`**: Guarda el registro de todos los adjuntos (INE, CURP, Formato ID) requeridos en el Expediente.
- **`EmpleadoBaja.php`**: Almacena el histórico y motivo de las bajas laborales para reportes (Turnover/Rotación).
- **`Asistencia.php`**: Registra la entrada y salida de un empleado en un día particular. Incluye banderas algorítmicas como `es_retardo`, `es_justificado` y `tipo_registro`.
- **`AvisoAsistencia.php`**: Sistema de notificaciones (Actas administrativas leves) ligado al dashboard de asistencia.
- **`Capacitacion.php` / `CapacitacionAdjunto.php`**: Almacenaje de cursos, URLs de YouTube, control de audiencia permitida y documentos suplementarios.
- **`Recordatorio.php`**: Almacena fechas importantes manuales del calendario general de RH.
- **`DiaFestivo.php`**: El catálogo de feriados anuales de la empresa.

## 2. Migraciones

La base de datos se estructuró a partir de una única migración consolidada para el inicio del proyecto y se fue adaptando con el paso de los meses.

- **`2025_12_09_190537_create_rh_tables.php`**: Crea la estructura matriz (Asistencias, Empleados).
- **`2025_12_30_164438_create_capacitacions_table.php`** y **`_adjuntos_table.php`**: Incorpora el subsistema de formación y capacitación.
- **`2026_01_05_165519_create_empleado_documentos_table.php`**: Habilita los expedientes digitales.
- **`2026_01_06_165436_create_empleados_baja_table.php`**: Añade persistencia de datos para empleados removidos de la nómina.

### Modificaciones en el Tiempo (Adición de Campos)
- `2026_01_05_171828_add_extra_info_to_empleados_table.php`: Amplía la base de datos para incluir campos extraídos por el parser del Excel ("Formato ID").
- `2026_01_08_202955_add_fiscal_data_to_empleados_table.php`: Datos de facturación de nómina.
- `2026_02_11_163327_add_es_coordinador_to_empleados_table.php`: Estructura Organizacional.
- `2026_02_17_101140...` / `2026_02_19_131153...` / `2026_04_20_120000...`: Adaptaciones a la visibilidad de las tablas de capacitaciones (YouTube URLs, Puestos Permitidos).
- `2026_03_10_112753_add_incompleto_to_asistencias_tipo_registro.php` y `2026_03_11_095338_create_aviso_asistencias_table.php`: Correcciones a la lógica de negocio de cálculo de horas de los biométricos.
- `2026_04_13_000000_create_dias_festivos_table.php`: Integración del calendario laboral final.
