# Documentación del Sistema de Correos y Notificaciones

El ERP Estrategia e Innovación utiliza **tres mecanismos paralelos** para comunicarse con los empleados:

| Mecanismo | Clase Base | Descripción |
|-----------|-----------|-------------|
| **Mailables** | `Illuminate\Mail\Mailable` | Correos transaccionales completos (con plantilla HTML propia) |
| **Notifications** | `Illuminate\Notifications\Notification` | Notificaciones multi-canal que envían por correo Y además persisten en la BD |
| **Artisan Commands** | `Illuminate\Console\Command` | Tareas programadas (Cron) que disparan los correos masivos de forma automática |

## Inventario Completo

### Mailables (`app/Mail/`)
| Clase | Plantilla | Disparado Desde | Canal |
|-------|-----------|----------------|-------|
| `AvisoAsistenciaMailable` | `emails.recursos_humanos.aviso_asistencia` | `RelojChecadorImportController` | Solo Mail |
| `NuevoTicketNotificacion` | `emails.nuevo_ticket` | `TicketController` | Solo Mail |
| `ProyectoAsignado` | `emails.proyectos.asignado` | `ProyectoController` / `NotificarAsignacionProyecto` Job | Solo Mail |

### Notifications (`app/Notifications/`)
| Clase | Canal | Persistencia BD | Disparado Desde |
|-------|-------|-----------------|----------------|
| `FestivoNotification` | Mail + Database | ✅ Sí | `NotificarDiaFestivoJob` / `DiaFestivo::enviarNotificaciones()` |
| `ProximoMantenimientoNotification` | Mail + Database | ✅ Sí | `NotificarProximoMantenimiento` Command |
| `RecordatorioJuntaProyecto` | Solo Mail | ❌ No | `EnviarRecordatoriosProyectos` Command |
| `CustomResetPassword` | Solo Mail | ❌ No | ⚠️ Sin implementar (placeholder) |

### Jobs (`app/Jobs/`)
| Clase | Implementa Queue | Reintentos | Disparado Por |
|-------|-----------------|-----------|--------------|
| `NotificarAsignacionProyecto` | ✅ `ShouldQueue` | 3 reintentos | `ProyectoController` (importación) |
| `NotificarDiaFestivoJob` | ✅ `ShouldQueue` | Default | `NotificarDiasFestivos` Command |
| `ProcessAsistenciaImportJob` | ✅ `ShouldQueue` | Default | `RelojChecadorImportController` |

### Scheduler (Tareas Cron / `routes/console.php`)
| Comando Artisan | Frecuencia | Descripción |
|----------------|-----------|-------------|
| `rh:generar-recordatorios` | Diario | Genera recordatorios de días festivos próximos |
| `proyectos:recordatorios` | Diario a las 08:00 | Envía aviso de juntas del día |
| `it:notificar-proximo-mantenimiento` | Diario a las 08:00 | Notifica mantenimientos de equipo cercanos |
| `logistica:actualizar-status` | Cada hora | Actualiza automáticamente los estatus de operaciones |

En esta carpeta encontrarás:
- [Mailables (Correos)](Mailables.md)
- [Notifications (Notificaciones)](Notifications.md)
- [Jobs y Scheduler (Tareas Automáticas)](Jobs_Scheduler.md)
- [Revisión de Seguridad](Security_Code_Review.md)
