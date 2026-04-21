# Revisión de Seguridad y Recomendaciones de Código (Módulo IT)

Al analizar los archivos del módulo de Tecnologías de la Información, se encontraron las siguientes áreas de oportunidad y posibles riesgos:

## 1. Vulnerabilidades Encontradas

### A. Almacenamiento de Archivos en Base de Datos (Anti-patrón)
**Archivo afectado**: `TicketController.php` (Función `store` y `update`)
- **Descripción**: Las imágenes subidas en el ticket (`imagenes` y `imagenes_admin`) están siendo pasadas por `base64_encode(file_get_contents(...))` y se almacenan directamente como cadenas largas en la base de datos dentro de columnas JSON/Text.
- **Riesgo**: Denegación de servicio (DoS) a nivel de la base de datos o aplicación por consumo excesivo de memoria RAM y disco. Almacenar base64 en la BD ralentiza considerablemente los queries.
- **Solución recomendada**: Usar `Storage::disk('public')->putFile()` (o disk private) y guardar únicamente la URL/Ruta en la base de datos.

### B. Posible Cross-Site Scripting (XSS) en metadatos
**Archivo afectado**: `TicketController.php` (Función `store`)
- **Descripción**: Al procesar la subida, se utiliza `$imagen->getClientOriginalName()` directamente y se almacena en el array.
- **Riesgo**: Si un usuario sube un archivo con nombre `<script>alert(1)</script>.png` y este nombre es renderizado directamente sin limpieza (escapado) en las vistas de blade del lado del administrador.
- **Solución recomendada**: Limpiar el nombre usando funciones como `Str::slug()`, o depender directamente del escapado estricto `{{ }}` en Blade, asegurándose que jamás se imprima con `{!! !!}`.

### C. Fallos de Rendimiento en Queries N+1 y Duplicados
**Archivo afectado**: `TicketController.php` (Función `canCancel` y `destroy`)
- **Descripción**: En la función `canCancel`, se realiza una consulta de `\DB::table('tickets')->where('id', $ticketId)->first();` seguida inmediatamente por `Ticket::find($ticketId);`. Ambas consultas hacen la misma búsqueda innecesariamente, duplicando la carga del sistema.
- **Solución recomendada**: Utilizar una única consulta con Eloquent y verificar la nulidad del objeto.

## 2. Recomendaciones de Limpieza de Código (Refactoring)

### A. Repetición de Lógica en Asignaciones de Equipos
**Archivo afectado**: `CredencialEquipoController.php`
- **Descripción**: La lógica para crear los "periféricos" y "correos" se repite idénticamente en la función `store` (para el principal) y en `storeSecundario` (para secundarios).
- **Sugerencia de Refactorización**: Extraer la lógica a una función protegida `syncCorreosAndPerifericos($equipo, $requestData)` o al uso de *FormRequests* y *Actions* (patrón Action o Service) para no duplicar bloques masivos de código.

### B. Controladores Masivos (Fat Controllers)
**Archivo afectado**: `TicketController.php` (> 900 líneas de código)
- **Descripción**: El controlador de tickets tiene demasiadas responsabilidades (Validación, Almacenamiento, Lógica de Negocio de Mantenimientos, Envío de correos y Notificaciones a N8N).
- **Sugerencia de Refactorización**:
  1. Utilizar **FormRequests** (ej: `StoreTicketRequest`, `UpdateTicketRequest`) para extraer las reglas de validación (que toman hasta 50 líneas).
  2. Implementar **Events & Listeners** para notificaciones. La llamada a `$this->notifyN8nTicketCreated($ticket)` y `enviarCorreoNotificacion($ticket)` deberían ser extraídas a un listener del evento `TicketCreated`.

### C. Rutas y Middleware de Permisos
**Archivo afectado**: `routes/web.php`
- **Descripción**: Se integran manualmente métodos para los códigos QR sin control explícito de roles en el cuerpo de la función. Están delegados netamente a `sistemas_admin` middleware.
- **Sugerencia**: Agrupar lógicamente con un Request Authorization (`$request->user()->can('...')`) para garantizar que si un día se remueve un middleware por accidente, el sistema no quede expuesto.
