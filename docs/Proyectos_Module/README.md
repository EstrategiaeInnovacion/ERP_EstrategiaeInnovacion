# Documentación del Módulo de Gestión de Proyectos

El módulo de Gestión de Proyectos está enfocado primordialmente para el área de Recursos Humanos. Funciona como un orquestador o contenedor padre de las "Actividades" vistas en el módulo 1. Permite agrupar tareas bajo un mismo presupuesto o meta corporativa.

## Características Principales
1. **Delegación a IT**: Los proyectos cuentan con un apartado especial para "Responsables de TI", lo que permite vincular al equipo de sistemas automáticamente en caso de requerir infraestructura para un proyecto de RH.
2. **Jerarquía Visual**: Un coordinador de área puede ver automáticamente los proyectos a los que han sido asignados sus subordinados directos.
3. **Métricas Híbridas**: El progreso del proyecto se calcula matemáticamente sumando la eficiencia y atrasos de las actividades hijas (modelo `Activity`).
4. **Notificaciones Automatizadas**: Envía correos electrónicos transaccionales cuando alguien es adherido al equipo de trabajo del proyecto.

En esta carpeta encontrarás documentación detallada sobre:
- [Controladores (Controllers)](Controllers.md)
- [Modelos y Migraciones](Models_Migrations.md)
- [Endpoints y Rutas](Endpoints.md)
- [Revisión de Seguridad y Limpieza de Código](Security_Code_Review.md)
