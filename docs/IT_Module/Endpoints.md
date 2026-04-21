# Endpoints del Módulo IT

## Lado del Usuario (Rutas Regulares)

**Tickets (`/ticket`)**
- `GET /ticket/create/{tipo}`: Formulario de creación (`tipo`: hardware, software, mantenimiento).
- `POST /ticket`: Recibe los datos del formulario, almacena y notifica.
- `GET /ticket/mis-tickets`: Listado e historial de tickets del usuario autenticado.
- `DELETE /ticket/{id}`: Cancela o borra un ticket.
- `GET /ticket/{id}/can-cancel`: Verifica reglas de negocio para cancelar.
- `POST /ticket/{id}/acknowledge-update`: Marca notificaciones como leídas.
- `POST /ticket/acknowledge-all`: Marca todas como leídas.

**Mantenimiento (`/maintenance`)**
- `GET /maintenance/availability`: Consulta de disponibilidad de fechas.
- `GET /maintenance/slots`: Slots de hora en un día específico.
- `GET /maintenance/check-availability`: Chequeo rápido de slot ocupado.

## Lado del Administrador (Prefijo `/admin`)
*Requieren middleware `sistemas_admin`.*

**Tickets**
- `GET /admin/tickets`: Listado maestro de tickets.
- `GET /admin/tickets/{ticket}`: Vista de detalles técnicos.
- `PATCH /admin/tickets/{ticket}`: Actualización (reportes técnicos, estados).
- `POST /admin/tickets/{ticket}/change-maintenance-date`: Reagenda citas de mantenimiento.
- `GET /admin/maintenance-slots/available`: Devuelve la lista administrativa de slots.

**Activos (`/admin/activos`)**
- `GET /admin/activos`: Index de dispositivos.
- `GET /admin/activos/escaner-qr`: Lanza lector QR del navegador.
- `POST /admin/activos`: Guarda un activo manualmente.
- `POST /admin/activos/{uuid}/asignar`: Asignación a usuario.
- `POST /admin/activos/{uuid}/devolver`: Retorna a stock general.

**Activos API (`/admin/activos-api`)**
- `GET /usuario/{userId}/equipo`: Dispositivos de un empleado.
- `GET /equipos-disponibles`: Inventario libre.
- `GET /fotos/{id}`: Proxy para acceder fotos privadas de los equipos.
- `GET /dispositivo/{uuid}`: Consulta por QR ID.
- `POST /qr-asignar/{uuid}`: Asigna en base al escaneo QR.
- `POST /qr-devolver/{uuid}`: Devuelve escaneando QR.
- `POST /qr-danado/{uuid}`: Marca dañado escaneando QR.

**Mantenimientos (`/admin/maintenance`)**
- `GET /admin/maintenance`: Panel administrativo.
- `GET /admin/maintenance/computers/{computerProfile}`: Panel individual.
- `POST /admin/maintenance/block-slot`: Bloqueo manual de horarios.

**Credenciales (`/admin/credenciales`)**
- `GET /admin/credenciales`: Vista general.
- `GET /admin/credenciales/exportar-excel`: Reporte XLSX.
- `GET /admin/credenciales/carta-responsiva/{user}`: Generar responsiva PDF.
- `POST /admin/credenciales/carta-responsiva/{user}/guardar`: Guardar la carta.
- `POST /admin/credenciales/{credencial}/secundarios`: Asigna otro equipo a este usuario.

**Notificaciones (`/api/notifications`)**
- `GET /count`, `GET /unread`, `POST /mark-all-read`: Actualización de campanitas del administrador.
