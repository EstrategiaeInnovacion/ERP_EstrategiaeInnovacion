# Diagrama Entidad-Relación — ERP Estrategia e Innovación

```mermaid

erDiagram
    %% ========================
    %% CORE
    %% ========================
    users {
        bigint id PK
        string name
        string email UK
        enum role "user | admin"
        enum status "pending | approved | rejected"
        timestamp approved_at NULL
        timestamp rejected_at NULL
        timestamp email_verified_at NULL
        string password
        string remember_token NULL
        datetime created_at
        datetime updated_at
    }

    %% ========================
    %% MÓDULO RH
    %% ========================
    subdepartamentos {
        bigint id PK
        string area
        string nombre
        boolean activo
        datetime created_at
        datetime updated_at

    }

    empleados {
        bigint id PK
        bigint user_id FK
        string nombre
        string correo
        string area NULL
        boolean es_activo
        string id_empleado NULL
        bigint subdepartamento_id FK NULL
        string posicion NULL
        string telefono NULL
        string correo_personal NULL
        string foto_path NULL
        text direccion NULL
        datetime created_at
        datetime updated_at
    }

    asistencias {
        bigint id PK
        string empleado_no NULL
        string nombre
        date fecha
        time entrada NULL
        time salida NULL
        longtext checadas
        bigint empleado_id FK NULL
        datetime created_at
        datetime updated_at
    }

    aviso_asistencias {
        bigint id PK
        bigint empleado_id FK
        bigint enviado_por FK
        string tipo
        text mensaje NULL
        string periodo NULL
        int cantidad_incidencias
        boolean leido
        timestamp leido_at NULL
        datetime created_at
        datetime updated_at
    }

    empleado_documentos {
        bigint id PK
        bigint empleado_id FK
        string tipo
        string nombre
        string ruta
        text observaciones NULL
        datetime created_at
        datetime updated_at
    }

    empleados_baja {
        bigint id PK
        bigint empleado_id FK
        date fecha_baja
        string motivo
        text observaciones NULL
        datetime created_at
        datetime updated_at
    }

    dias_festivos {
        bigint id PK
        string nombre
        date fecha
        enum tipo "festivo | inhabil"
        boolean es_anual
        text descripcion NULL
        boolean activo
        boolean notificacion_enviada
        timestamp notificacion_enviada_at NULL
        datetime created_at
        datetime updated_at
    }

    recordatorios {
        bigint id PK
        string tipo
        string titulo
        text descripcion NULL
        date fecha_evento
        int dias_anticipacion
        string tabla_relacionada NULL
        bigint registro_id NULL
        bigint empleado_id FK NULL
        bigint creado_por FK NULL
        boolean leido
        datetime leido_at NULL
        boolean activo
        datetime created_at
        datetime updated_at
    }

    %% ========================
    %% MÓDULO CAPACITACIÓN
    %% ========================
    capacitaciones {
        bigint id PK
        string titulo
        text descripcion NULL
        string archivo_path
        string thumbnail_path NULL
        bigint subido_por FK
        boolean activo
        string youtube_url NULL
        string categoria NULL
        json puestos NULL
        json usuarios_permitidos NULL
        datetime created_at
        datetime updated_at
    }

    capacitacion_adjuntos {
        bigint id PK
        bigint capacitacion_id FK
        string nombre
        string ruta
        string tipo NULL
        datetime created_at
        datetime updated_at
    }

    %% ========================
    %% MÓDULO EVALUACIÓN
    %% ========================
    criterios_evaluacion {
        bigint id PK
        string area
        string criterio
        text descripcion NULL
        int peso
        datetime created_at
        datetime updated_at
    }

    evaluaciones {
        bigint id PK
        bigint empleado_id FK
        bigint evaluador_id FK
        string periodo
        decimal promedio_final NULL
        text comentarios_generales NULL
        int edit_count
        bigint ventana_id FK NULL
        datetime created_at
        datetime updated_at

    }

    evaluacion_detalles {
        bigint id PK
        bigint evaluacion_id FK
        bigint criterio_id FK
        decimal calificacion
        text observaciones NULL
        datetime created_at
        datetime updated_at
    }

    evaluacion_ventanas {
        bigint id PK
        string nombre
        date fecha_apertura
        date fecha_cierre
        boolean activo
        bigint creado_por NULL
        datetime created_at
        datetime updated_at
    }

    planeacion_ventanas {
        bigint id PK
        string nombre
        date fecha_inicio
        date fecha_fin
        boolean activo
        bigint creado_por NULL
        datetime created_at
        datetime updated_at
    }

    %% ========================
    %% MÓDULO ACTIVIDADES / PROYECTOS
    %% ========================
    actividades {
        bigint id PK
        bigint user_id FK
        string nombre_actividad
        string area NULL
        string tipo_actividad NULL
        string prioridad
        date fecha_inicio
        date fecha_compromiso
        date fecha_final NULL
        int metrico
        int resultado_dias NULL
        decimal porcentaje NULL
        string estatus
        text comentarios NULL
        string evidencia_path NULL
        bigint proyecto_id FK NULL
        bigint cliente_id FK NULL
        string motivo_rechazo NULL
        date planned_start_date NULL
        date planned_end_date NULL
        bigint asignado_por FK NULL
        bigint deleted_by FK NULL
        datetime created_at
        datetime updated_at
        datetime deleted_at NULL
    }

    activity_histories {
        bigint id PK
        bigint activity_id FK
        bigint user_id FK
        string action NULL
        string field NULL
        text old_value NULL
        text new_value NULL
        text details NULL
        text comentario NULL
        datetime created_at
        datetime updated_at
    }

    proyectos {
        bigint id PK
        string nombre
        text descripcion NULL
        bigint usuario_id FK
        date fecha_inicio
        date fecha_fin
        enum recurrencia "semanal | quincenal | mensual"
        text notas NULL
        boolean archivado
        boolean finalizado
        bigint responsable_ti_id FK NULL
        datetime created_at
        datetime updated_at
        datetime deleted_at NULL
    }

    proyecto_usuarios {
        bigint proyecto_id FK
        bigint usuario_id FK
    }

    proyecto_responsable_ti {
        bigint proyecto_id FK
        bigint user_id FK
    }

    %% ========================
    %% MÓDULO SISTEMAS IT
    %% ========================
    tickets {
        bigint id PK
        string folio UK
        string nombre_solicitante
        string correo_solicitante
        string nombre_programa NULL
        text descripcion_problema
        longtext imagenes NULL
        enum estado "abierto | en_proceso | cerrado"
        boolean closed_by_user
        boolean is_read
        boolean user_has_updates
        timestamp user_notified_at NULL
        timestamp user_last_read_at NULL
        text user_notification_summary NULL
        timestamp notified_at NULL
        timestamp read_at NULL
        timestamp fecha_apertura
        timestamp fecha_cierre NULL
        timestamp closed_by_user_at NULL
        text observaciones NULL
        enum tipo_problema "software | hardware | mantenimiento"
        enum prioridad "baja | media | alta | critica" NULL
        bigint user_id FK NULL
        bigint maintenance_slot_id FK NULL
        bigint computer_profile_id FK NULL
        string equipment_password NULL
        longtext imagenes_admin NULL
        datetime maintenance_scheduled_at NULL
        text maintenance_details NULL
        string equipment_identifier NULL
        string equipment_brand NULL
        string equipment_model NULL
        string disk_type NULL
        string ram_capacity NULL
        string battery_status NULL
        text aesthetic_observations NULL
        longtext replacement_components NULL
        datetime created_at
        datetime updated_at
    }

    computer_profiles {
        bigint id PK
        string identifier NULL
        string brand NULL
        string model NULL
        string disk_type NULL
        string ram_capacity NULL
        string battery_status NULL
        text aesthetic_observations NULL
        longtext replacement_components NULL
        datetime last_maintenance_at NULL
        boolean is_loaned
        string loaned_to_name NULL
        string loaned_to_email NULL
        bigint last_ticket_id FK NULL
        bigint equipo_asignado_id NULL
        date warranty_expiration NULL
        date next_maintenance_date NULL
        string maintenance_frequency NULL
        datetime created_at
        datetime updated_at
    }

    maintenance_slots {
        bigint id PK
        date date
        time start_time
        time end_time
        int capacity
        int booked_count
        boolean is_active
        datetime created_at
        datetime updated_at

    }

    maintenance_bookings {
        bigint id PK
        bigint maintenance_slot_id FK
        bigint ticket_id FK
        text additional_details NULL
        datetime created_at
        datetime updated_at
    }

    maintenance_blocked_slots {
        bigint id PK
        bigint slot_id FK
        string reason NULL
        datetime created_at
        datetime updated_at
    }

    inventory_items {
        bigint id PK
        string codigo_producto
        string identificador NULL
        string nombre
        string categoria
        string marca NULL
        string modelo NULL
        string numero_serie NULL
        enum estado "disponible | prestado | mantenimiento | reservado | danado"
        boolean es_funcional
        string ubicacion NULL
        text descripcion_general NULL
        text notas NULL
        datetime created_at
        datetime updated_at
    }

    help_sections {
        bigint id PK
        string title
        text content
        int section_order
        boolean is_active
        longtext images NULL
        datetime created_at
        datetime updated_at
    }

    blocked_emails {
        bigint id PK
        string email UK
        string reason NULL
        bigint blocked_by FK NULL
        datetime created_at
        datetime updated_at
    }

    it_equipos_asignados {
        bigint id PK
        bigint user_id FK
        string uuid_activos
        string nombre_equipo
        string modelo NULL
        string numero_serie NULL
        int photo_id NULL
        string nombre_usuario_pc
        text contrasena_equipo
        text notas NULL
        boolean es_principal
        datetime created_at
        datetime updated_at
    }

    it_equipos_correos {
        bigint id PK
        bigint equipo_asignado_id FK
        string correo
        text contrasena_correo NULL
        datetime created_at
        datetime updated_at
    }

    it_equipos_perifericos {
        bigint id PK
        bigint equipo_asignado_id FK
        string uuid_activos
        string nombre
        string tipo NULL
        string numero_serie NULL
        datetime created_at
        datetime updated_at
    }

    %% ========================
    %% MÓDULO LOGÍSTICA — CATÁLOGOS
    %% ========================
    aduanas {
        bigint id PK
        string aduana
        string seccion
        text denominacion
        string patente NULL
        string pais
        datetime created_at
        datetime updated_at
    }

    agentes_aduanales {
        bigint id PK
        string agente_aduanal
        datetime created_at
        datetime updated_at
    }

    transportes {
        bigint id PK
        string transporte
        enum tipo_operacion "Aerea | Terrestre | Maritima | Ferrocarril"
        datetime created_at
        datetime updated_at
    }

    clientes_logistica {
        bigint id PK
        string cliente
        longtext correos NULL
        string periodicidad_reporte NULL
        timestamp fecha_carga_excel NULL
        bigint ejecutivo_asignado_id FK NULL
        datetime created_at
        datetime updated_at
    }

    logistica_correos_cc {
        bigint id PK
        string nombre
        string email UK
        enum tipo "administrador | supervisor | notificacion"
        text descripcion NULL
        boolean activo
        datetime created_at
        datetime updated_at
    }

    pedimentos_catalogo {
        bigint id PK
        string categoria NULL
        string clave UK
        text descripcion
        datetime created_at
        datetime updated_at
    }

    incoterms {
        bigint id PK
        string codigo UK
        string nombre
        text descripcion NULL
        enum grupo "E | F | C | D"
        boolean aplicable_importacion
        boolean aplicable_exportacion
        boolean activo
        int orden
        datetime created_at
        datetime updated_at
    }

    %% ========================
    %% MÓDULO LOGÍSTICA — CAMPOS PERSONALIZADOS
    %% ========================
    campos_personalizados_matriz {
        bigint id PK
        string nombre
        string tipo
        json opciones NULL
        json configuracion NULL
        boolean requerido
        boolean activo
        int orden
        string mostrar_despues_de NULL
        datetime created_at
        datetime updated_at
    }

    campo_personalizado_ejecutivo {
        bigint id PK
        bigint campo_personalizado_id FK
        bigint empleado_id FK
        int orden
        datetime created_at
        datetime updated_at

    }

    columnas_visibles_ejecutivo {
        bigint id PK
        bigint empleado_id FK
        string columna
        boolean visible
        int orden
        enum idioma_nombres "es | en"
        datetime created_at
        datetime updated_at

    }

    %% ========================
    %% MÓDULO LOGÍSTICA — OPERACIONES
    %% ========================
    operaciones_logisticas {
        bigint id PK
        string folio NULL
        string ejecutivo NULL
        string operacion NULL
        string cliente NULL
        string proveedor_o_cliente NULL
        string no_factura NULL
        string tipo_carga NULL
        string tipo_incoterm NULL
        enum tipo_operacion_enum "Aerea | Terrestre | Maritima | Ferrocarril" NULL
        string clave NULL
        string referencia_interna NULL
        string aduana NULL
        string agente_aduanal NULL
        string referencia_aa NULL
        string no_pedimento NULL
        string transporte NULL
        string guia_bl NULL
        string puerto_salida NULL
        string in_charge NULL
        string proveedor NULL
        string tipo_previo NULL
        date fecha_etd NULL
        date fecha_zarpe NULL
        boolean pedimento_en_carpeta NULL
        string referencia_cliente NULL
        text mail_subject NULL
        enum status_calculado "In Process | Done | Out of Metric"
        enum status_manual "In Process | Done | Out of Metric"
        timestamp fecha_status_manual NULL
        enum color_status "verde | amarillo | rojo | sin_fecha"
        int dias_transcurridos_calculados NULL
        timestamp fecha_ultimo_calculo NULL
        text comentarios NULL
        date fecha_embarque NULL
        date fecha_arribo_aduana NULL
        date fecha_modulacion NULL
        date fecha_arribo_planta NULL
        int resultado NULL
        int target NULL
        int dias_transito NULL
        bigint post_operacion_id FK NULL
        enum post_operacion_status "In Process | Done | Out of Metric"
        datetime created_at
        datetime updated_at
    }

    post_operaciones {
        bigint id PK
        string nombre NULL
        text descripcion NULL
        bigint operacion_logistica_id FK NULL
        string no_pedimento NULL
        string post_operacion NULL
        string status
        timestamp fecha_creacion NULL
        timestamp fecha_completado NULL
        datetime created_at
        datetime updated_at
    }

    post_operacion_operacion {
        bigint id PK
        bigint post_operacion_id FK
        bigint operacion_logistica_id FK
        enum status "Pendiente | Completado | No Aplica"
        timestamp fecha_asignacion
        timestamp fecha_completado NULL
        text notas_especificas NULL
        datetime created_at
        datetime updated_at

    }

    operacion_comentarios {
        bigint id PK
        bigint operacion_logistica_id FK
        text comentario
        string status_en_momento NULL
        string tipo_accion
        string usuario_nombre NULL
        int usuario_id NULL
        longtext contexto_operacion NULL
        datetime created_at
        datetime updated_at
    }

    historico_matriz_sgm {
        bigint id PK
        bigint operacion_logistica_id FK
        date fecha_arribo_aduana NULL
        date fecha_registro
        int dias_transcurridos
        int target_dias
        enum color_status "verde | amarillo | rojo"
        enum operacion_status "In Process | Done | Out of Metric"
        text observaciones NULL
        datetime created_at
        datetime updated_at
    }

    pedimentos_operaciones {
        bigint id PK
        string no_pedimento
        string clave
        bigint operacion_logistica_id FK
        enum estado_pago "pendiente | pagado"
        date fecha_pago NULL
        decimal monto NULL
        string moneda
        text observaciones NULL
        datetime created_at
        datetime updated_at

    }

    valores_campos_personalizados {
        bigint id PK
        bigint operacion_logistica_id FK
        bigint campo_personalizado_id FK
        text valor NULL
        datetime created_at
        datetime updated_at

    }

    %% ========================
    %% MÓDULO LOGÍSTICA — MATRIZ SEGUIMIENTO
    %% ========================
    matriz_seguimiento {
        bigint id PK
        string ref_interna NULL
        string proveedor_cliente NULL
        string factura NULL
        enum impo_ex "IMPO | EX" NULL
        string tipo_operacion NULL
        string transporte NULL
        string aduana NULL
        string clave NULL
        string pedimento NULL
        string bl_guia NULL
        date etd NULL
        date eta NULL
        date previo NULL
        date cita_despacho NULL
        date arribo_planta NULL
        string status NULL
        string resultado NULL
        string target NULL
        text comentarios NULL
        bigint user_id FK NULL
        bigint cliente_operacion FK NULL
        datetime created_at
        datetime updated_at
    }

    matriz_seguimiento_comentarios {
        bigint id PK
        bigint matriz_seguimiento_id FK
        bigint user_id FK NULL
        text comentario
        datetime created_at
        datetime updated_at
    }

    %% ========================
    %% MÓDULO LOGÍSTICA — MATRIZ APOYO
    %% ========================
    matriz_apoyo_agentes {
        bigint id PK
        string agente_aduanal
        string razon_social NULL
        string patente NULL
        tinyint calificacion NULL
        string responsabilidad
        string nombre NULL
        string correo_electronico NULL
        string telefono NULL
        text comentarios NULL
        string cliente NULL
        string aduana NULL
        datetime created_at
        datetime updated_at
    }

    matriz_apoyo_forwarders {
        bigint id PK
        string cliente
        string aduana NULL
        string razon_social NULL
        tinyint calificacion NULL
        string responsabilidad
        string nombre NULL
        string correo_electronico NULL
        string telefono NULL
        text comentarios NULL
        datetime created_at
        datetime updated_at
    }

    matriz_apoyo_navieras {
        bigint id PK
        string cliente NULL
        string aduana NULL
        string razon_social NULL
        tinyint calificacion NULL
        string responsabilidad
        string nombre NULL
        string correo_electronico NULL
        string telefono NULL
        text comentarios NULL
        datetime created_at
        datetime updated_at
    }

    matriz_apoyo_arrastres {
        bigint id PK
        string cliente NULL
        string aduana NULL
        string razon_social NULL
        tinyint calificacion NULL
        string responsabilidad
        string nombre NULL
        string correo_electronico NULL
        string telefono NULL
        text comentarios NULL
        datetime created_at
        datetime updated_at
    }

    %% ========================
    %% MÓDULO LEGAL
    %% ========================
    legal_categorias {
        bigint id PK
        string nombre
        bigint parent_id FK NULL
        string tipo NULL
        datetime created_at
        datetime updated_at
    }

    legal_proyectos {
        bigint id PK
        string empresa NULL
        bigint categoria_id FK
        text consulta
        text resultado NULL
        string tipo NULL
        string cliente_nombre NULL
        string cliente_contacto NULL
        string cliente_correo NULL
        datetime created_at
        datetime updated_at
    }

    legal_archivos {
        bigint id PK
        bigint proyecto_id FK
        string nombre
        string tipo
        string ruta
        boolean es_url
        string mime_type NULL
        datetime created_at
        datetime updated_at
    }

    legal_paginas {
        bigint id PK
        string titulo
        text contenido
        string ruta UK
        boolean activo
        datetime created_at
        datetime updated_at
    }

    %% ========================
    %% MÓDULO ADMINISTRACIÓN
    %% ========================
    admin_clientes {
        bigint id PK
        string nombre
        string contacto NULL
        string correo NULL
        string telefono NULL
        string empresa NULL
        text notas NULL
        datetime created_at
        datetime updated_at
    }

    admin_cliente_perfiles {
        bigint id PK
        bigint cliente_id FK
        string nombre_legal NULL
        text sectores_productivos NULL
        date fecha_inicio_operaciones NULL
        boolean partes_relacionadas_extranjero
        string nombre_corporativo NULL
        string ciudad_estado_pais_corporativo NULL
        boolean tiene_immex
        date immex_fecha NULL
        boolean es_maquiladora
        date maquiladora_fecha NULL
        boolean maquiladora_servicios
        date maquiladora_servicios_fecha NULL
        boolean tiene_prosec
        date prosec_fecha NULL
        boolean transferencias_otras_immex
        boolean empresa_certificada_oea
        date oea_fecha NULL
        boolean empresa_certificada_iva_eps
        date iva_eps_fecha NULL
        string iva_eps_modalidad NULL
        boolean utiliza_regla_octava
        boolean automotriz_deposito_fiscal
        boolean proveedor_autopartes
        boolean utiliza_almacen_fiscal
        boolean utiliza_regla_2
        boolean estudio_precios_transferencia
        boolean estudio_valoracion_aduanera
        boolean importa_mercancias_nom
        boolean proveedores_sub_maquila
        boolean importa_precios_estimados
        boolean importa_permisos_avisos
        text destino_desperdicios NULL
        boolean certificados_origen_tlcan
        boolean certificados_origen_tlcue
        boolean exporta_eua_canada
        boolean exporta_union_europea
        boolean emite_certificados_eua_canada
        boolean emite_certificados_union_europea
        string sistema_manufactura_erp NULL
        string sistema_anexo_24 NULL
        boolean recibe_info_agentes_aduanales
        boolean manual_procedimientos_ce
        text ultima_auditoria_interna NULL
        text ultima_auditoria_externa NULL
        text principales_hallazgos NULL
        boolean auditado_shcp_se
        date auditado_shcp_se_fecha NULL
        text observaciones_multas NULL
        int pedimentos_anuales_importacion NULL
        int pedimentos_anuales_exportacion NULL
        string aduana_principal_importacion NULL
        string aduana_principal_exportacion NULL
        int proveedores_extranjeros_cantidad NULL
        string pais_origen_importaciones NULL
        boolean importa_fuera_tlcan
        string importa_fuera_tlcan_paises NULL
        int clientes_extranjeros_cantidad NULL
        string pais_destino_exportaciones NULL
        text insumos_importacion_importantes NULL
        text productos_exportacion_representativos NULL
        string informante_nombre NULL
        string informante_puesto NULL
        date informante_fecha NULL
        boolean registro_marca
        string poliza_seguro NULL
        datetime created_at
        datetime updated_at
    }

    %% ========================
    %% BD SECUNDARIA: Auditoría Activos
    %% ========================
    activos_devices {
        bigint id PK
        uuid uuid UK
        string name
        string brand
        string model
        string serial_number UK
        enum type "computer | peripheral | printer | other | mobiliario"
        enum status "available | assigned | maintenance | broken"
        date purchase_date NULL
        date warranty_expiration NULL
        text notes NULL
        datetime created_at
        datetime updated_at
    }

    activos_credentials {
        bigint id PK
        bigint device_id FK
        string username NULL
        string password NULL
        string email NULL
        string email_password NULL
        datetime created_at
        datetime updated_at
    }

    activos_assignments {
        bigint id PK
        bigint device_id FK
        bigint user_id FK NULL
        string assigned_to NULL
        bigint employee_id FK NULL
        timestamp assigned_at
        timestamp returned_at NULL
        text notes NULL
        datetime created_at
        datetime updated_at
    }

    activos_employees {
        bigint id PK
        string name
        string employee_id UK
        string department NULL
        string position NULL
        string phone NULL
        boolean is_active
        datetime created_at
        datetime updated_at
    }

    activos_device_photos {
        bigint id PK
        bigint device_id FK
        string file_path
        string caption NULL
        bigint uploaded_by FK NULL
        datetime created_at
        datetime updated_at
    }

    activos_device_documents {
        bigint id PK
        bigint device_id FK
        string file_path
        string original_name
        enum type "factura | garantia | contrato | manual | otro"
        bigint uploaded_by FK NULL
        datetime created_at
        datetime updated_at
    }

    activos_users {
        bigint id PK
        string name
        string email UK
        string password
        datetime created_at
        datetime updated_at
    }

    %% ========================
    %% RELACIONES — RH
    %% ========================
    users |o--|| empleados: "user_id"
    empleados }o--|| subdepartamentos: "subdepartamento_id"
    empleados ||--o{ asistencias: "empleado_id"
    empleados ||--o{ aviso_asistencias: "empleado_id"
    users ||--o{ aviso_asistencias: "enviado_por"
    empleados ||--o{ empleado_documentos: "empleado_id"
    empleados ||--o{ empleados_baja: "empleado_id"
    empleados ||--o{ recordatorios: "empleado_id"
    users ||--o{ recordatorios: "creado_por"

    %% ========================
    %% RELACIONES — CAPACITACIÓN
    %% ========================
    users ||--o{ capacitaciones: "subido_por"
    capacitaciones ||--o{ capacitacion_adjuntos: "capacitacion_id"

    %% ========================
    %% RELACIONES — EVALUACIÓN
    %% ========================
    empleados ||--o{ evaluaciones: "empleado_id"
    users ||--o{ evaluaciones: "evaluador_id"
    evaluaciones ||--o{ evaluacion_detalles: "evaluacion_id"
    criterios_evaluacion ||--o{ evaluacion_detalles: "criterio_id"
    evaluacion_ventanas ||--o{ evaluaciones: "ventana_id"

    %% ========================
    %% RELACIONES — ACTIVIDADES / PROYECTOS
    %% ========================
    users ||--o{ actividades: "user_id"
    actividades ||--o{ activity_histories: "activity_id"
    users ||--o{ activity_histories: "user_id"
    proyectos ||--o{ actividades: "proyecto_id"
    users ||--o{ proyectos: "usuario_id"
    proyectos ||--o{ proyecto_usuarios: "proyecto_id"
    users ||--o{ proyecto_usuarios: "usuario_id"

    %% ========================
    %% RELACIONES — SISTEMAS IT
    %% ========================
    users ||--o{ tickets: "user_id"
    maintenance_slots ||--o{ tickets: "maintenance_slot_id"
    computer_profiles ||--o{ tickets: "computer_profile_id"
    maintenance_slots ||--o{ maintenance_bookings: "maintenance_slot_id"
    tickets ||--o{ maintenance_bookings: "ticket_id"
    computer_profiles }o--|| tickets: "last_ticket_id"
    users ||--o{ blocked_emails: "blocked_by"
    maintenance_slots ||--o{ maintenance_blocked_slots: "slot_id"
    users ||--o{ it_equipos_asignados: "user_id"
    it_equipos_asignados ||--o{ it_equipos_correos: "equipo_asignado_id"
    it_equipos_asignados ||--o{ it_equipos_perifericos: "equipo_asignado_id"

    %% ========================
    %% RELACIONES — LOGÍSTICA
    %% ========================
    empleados ||--o{ clientes_logistica: "ejecutivo_asignado_id"
    post_operaciones ||--o{ operaciones_logisticas: "post_operacion_id"
    operaciones_logisticas ||--o{ post_operaciones: "operacion_logistica_id"
    post_operaciones ||--o{ post_operacion_operacion: "post_operacion_id"
    operaciones_logisticas ||--o{ post_operacion_operacion: "operacion_logistica_id"
    operaciones_logisticas ||--o{ operacion_comentarios: "operacion_logistica_id"
    operaciones_logisticas ||--o{ historico_matriz_sgm: "operacion_logistica_id"
    operaciones_logisticas ||--o{ pedimentos_operaciones: "operacion_logistica_id"
    operaciones_logisticas ||--o{ valores_campos_personalizados: "operacion_logistica_id"
    campos_personalizados_matriz ||--o{ valores_campos_personalizados: "campo_personalizado_id"
    campos_personalizados_matriz ||--o{ campo_personalizado_ejecutivo: "campo_personalizado_id"
    empleados ||--o{ campo_personalizado_ejecutivo: "empleado_id"
    empleados ||--o{ columnas_visibles_ejecutivo: "empleado_id"
    matriz_seguimiento ||--o{ matriz_seguimiento_comentarios: "matriz_seguimiento_id"
    users ||--o{ matriz_seguimiento_comentarios: "user_id"
    users ||--o{ matriz_seguimiento: "user_id"

    %% ========================
    %% RELACIONES — LEGAL
    %% ========================
    legal_categorias ||--o{ legal_categorias: "parent_id"
    legal_categorias ||--o{ legal_proyectos: "categoria_id"
    legal_proyectos ||--o{ legal_archivos: "proyecto_id"

    %% ========================
    %% RELACIONES — ADMINISTRACIÓN
    %% ========================
    admin_clientes ||--o{ admin_cliente_perfiles: "cliente_id"

    %% ========================
    %% RELACIONES — ACTIVOS (Secondary DB)
    %% ========================
    activos_devices ||--o{ activos_credentials: "device_id"
    activos_devices ||--o{ activos_assignments: "device_id"
    activos_devices ||--o{ activos_device_photos: "device_id"
    activos_devices ||--o{ activos_device_documents: "device_id"
    activos_users ||--o{ activos_assignments: "user_id"
    activos_users ||--o{ activos_device_photos: "uploaded_by"
    activos_users ||--o{ activos_device_documents: "uploaded_by"
    activos_employees ||--o{ activos_assignments: "employee_id"
```
