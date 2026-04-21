# Controladores del Módulo IT

Los controladores se encuentran en `app/Http/Controllers/Sistemas_IT/`.

## 1. `TicketController.php`
Gestiona la creación, actualización y cancelación de tickets de soporte técnico.
- **`create($tipo)` / `store(Request $request)`**: Permiten a los usuarios levantar un ticket. Soporta subida de imágenes (guardadas en base64) e integración con horarios de mantenimiento. Notifica al administrador vía webhook (n8n) y por correo electrónico mediante Microsoft Graph API.
- **`index()` / `show(Ticket $ticket)`**: Vistas administrativas para listar y visualizar los tickets.
- **`update(Request $request, Ticket $ticket)`**: Actualiza el estado de los tickets. Permite a los administradores llenar reportes técnicos y actualizar perfiles de computadora (`ComputerProfile`) en tickets de mantenimiento.
- **`misTickets()`**: Vista del lado del usuario para revisar el progreso de sus propios tickets.
- **`destroy($ticketId)`**: Permite a un administrador eliminar o a un usuario cancelar un ticket. Libera horarios de mantenimiento reservados.

## 2. `ActivosController.php`
Controlador web para el inventario de activos.
- **Funciones Principales**: `index()`, `create()`, `store()`, `show()`, `edit()`, `update()`, `destroy()`.
- **Integraciones**: Operaciones para asignar (`assign()`) y devolver (`returnDevice()`) equipos desde la interfaz administrativa web.
- **QR**: `qrScanner()` muestra la interfaz web para escanear dispositivos.

## 3. `ActivosApiController.php`
API interna para consultas e integraciones de los activos de TI.
- **`devicesByUser(int $userId)`**: Retorna los equipos asignados a un usuario cruzando datos con el ERP.
- **`availableDevices()`**: Devuelve el equipo que no está asignado.
- **`lookupByUuid(string $uuid)`**: Retorna los datos de un activo escaneado vía código QR.
- **`assignViaQr()`, `returnViaQr()`, `markBrokenViaQr()`**: Endpoints llamados desde el escáner QR para hacer cambios de estado rápido.
- **`photo()`, `deletePhoto()`**: Proxy para servir y borrar imágenes del almacenamiento de los activos de forma segura.

## 4. `CredencialEquipoController.php`
Gestiona el expediente de los dispositivos y credenciales asignadas al usuario.
- **Funciones principales**: Permite agrupar "Equipos Asignados" que pueden ser *Principales* o *Secundarios*, ligando correos electrónicos y contraseñas.
- **Exportación**: `exportExcel()` exporta reportes de usuarios y contraseñas.
- **Carta Responsiva**: `cartaResponsiva()` / `guardarCartaResponsiva()` permite generar un documento PDF, subirlo mediante un canvas/base64 y guardarlo directamente en el expediente del empleado en el ERP.

## 5. `MaintenanceController.php`
Gestión de fechas, horarios y bloqueos de mantenimiento de equipos.
- **Usuario**: `availability()`, `slots()`, `checkAvailability()` para ver en el formulario de tickets cuándo hay espacios de TI libres.
- **Admin**: `adminIndex()`, `getWeekMaintenances()`, `getCalendarData()`.
- **Gestión de Citas y Bloqueos**: `blockSlot()` y `unblockSlot()` permiten al área de sistemas desactivar horas específicas.

## 6. `NotificationController.php`
Mini-API para la interfaz administrativa.
- **`getUnreadCount()`, `getUnreadTickets()`**: Obtiene notificaciones de nuevos tickets en tiempo real para la campanita del sistema.
- **`markAsRead()`, `markAllAsRead()`**: Funciones de interacción del UI.

## 7. `AdminController.php`
- Controlador simplificado que únicamente despacha el dashboard principal de administración de TI (`dashboard()`).
