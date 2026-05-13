# Mailables del Sistema

Los Mailables son clases de correo transaccional en `app/Mail/` que contienen la definición del sobre (asunto, remitente) y el cuerpo (plantilla Blade).

---

## 1. `AvisoAsistenciaMailable.php`
**Propósito**: Notificar a un empleado que Recursos Humanos le levantó un aviso formal por retardos o faltas.

**Remitente dinámico**: Usa el correo del coordinador de RH que generó el aviso (`$aviso->enviadoPor->email`), con fallback al correo genérico del sistema (`config('mail.from.address')`). Esto permite que el empleado pueda responder directamente al coordinador.

**Asunto**: `Aviso Oficial de {Retardos|Faltas|Asistencia} - Recursos Humanos`

**Plantilla**: `resources/views/emails/recursos_humanos/aviso_asistencia.blade.php`

**¿Cuándo se dispara?**
- Desde `RelojChecadorImportController` (Panel de RH), cuando el coordinador da clic en "Enviar Aviso" para un empleado con incidencias.
- Se envía de forma **síncrona** (`Mail::to()->send()`). ⚠️ Ver Security Review.

**Modelo dependiente**: `AvisoAsistencia` (que contiene la relación al empleado y al usuario de RH que lo generó).

---

## 2. `NuevoTicketNotificacion.php`
**Propósito**: Confirmar al área de Sistemas IT que se levantó un nuevo ticket de soporte.

**Remitente**: Genérico del sistema (configurable en `.env`).

**Asunto**: `[{folio}] Nuevo Ticket de {Software|Hardware|Mantenimiento}`

**Plantilla**: `resources/views/emails/nuevo_ticket.blade.php`

**¿Cuándo se dispara?**
- Desde `TicketController` cuando el empleado completa y envía el formulario de creación de ticket.
- El correo se envía a la bandeja de IT (correo configurado en `.env`), no al usuario.
- ⚠️ Este Mailable **no implementa `ShouldQueue`** (no hace uso de Colas). Se despacha síncronamente con `Mail::send()`.

---

## 3. `ProyectoAsignado.php`
**Propósito**: Avisar a un colaborador que fue incorporado a un proyecto.

**Asunto**: `Te han asignado al proyecto: {Nombre del Proyecto}`

**Plantilla**: `resources/views/emails/proyectos/asignado.blade.php`

**Parámetro `$tipo`**: Puede recibir `'usuario'` (equipo operativo) o `'responsable_ti'` (soporte técnico), lo que permite que la vista cambie el mensaje según el rol dentro del proyecto.

**¿Cuándo se dispara?**
- Directamente desde `ProyectoController` en los métodos `store()`, `asignarUsuarios()` y `asignarResponsablesTi()` de forma **síncrona en bucle** (⚠️ riesgo de timeout con equipos grandes).
- También puede ser disparado de forma asíncrona a través del Job `NotificarAsignacionProyecto`.
- **El Job ya existe pero no se está usando.** El controlador sigue llamando a `Mail::to()->send()` directamente en lugar de `NotificarAsignacionProyecto::dispatch()`.
