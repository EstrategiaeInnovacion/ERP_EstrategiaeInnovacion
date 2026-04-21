# Controladores del Módulo de Logística

Los controladores se encuentran en `app/Http/Controllers/Logistica/`.

## 1. `OperacionLogisticaController.php`
El núcleo del módulo de logística.
- **`index(Request $request)`**: Renderiza la Matriz de Seguimiento utilizando `spatie/laravel-query-builder` para manejar filtros complejos de forma dinámica, ocultando registros completados por defecto y ajustando las vistas según el rol (Admin vs Ejecutivo).
- **CRUD (`create, store, update, destroy`)**: Almacena los registros, calculando automáticamente su target de días y creando su historial de cambios en el tiempo (SGM).
- **`updateStatus()`, `recalcularStatus()`**: Métodos asíncronos para marcar operaciones como "Done" o recalcular automáticamente retrasos basándose en las fechas.
- **`consultaPublica()`, `buscarOperacionPublica()`**: Rutas públicas que permiten a clientes externos buscar por número de pedimento o factura para ver el estado de su paquete.

## 2. `PedimentoController.php` y `PedimentoImportController.php`
Gestión de Pedimentos (Documentos aduaneros).
- **`index()`**: Agrupa las operaciones por la clave del pedimento, mostrando totales pagados y pendientes. 
- **`updateEstadoPago()`, `marcarPagados()`**: Funcionalidad contable/operativa para marcar cobros.
- **`exportCSV()`**: Exporta el listado usando procesamiento en lotes (`chunk`) para evitar consumir excesiva memoria RAM.
- **`PedimentoImportController`**: Controla catálogos secundarios y la migración o subida en lote (legacy import) de pedimentos.

## 3. `ColumnaVisibleController.php`
Permite personalizar la UI del ERP.
- Guarda en la base de datos qué columnas (campos opcionales) un ejecutivo ha decidido prender o apagar en su tabla. Incluye configuraciones de qué campos aparecen antes o después de otros.

## 4. `CatalogosController.php` y Controladores Individuales
- **`CatalogosController`**: Retorna el dashboard que centraliza los catálogos.
- **`ClienteController`, `AgenteAduanalController`, `TransporteController`, `AduanaImportController`**: Manejan la adición, edición, eliminación y subida (importación por CSV/Excel) de sus respectivas entidades.

## 5. `CampoPersonalizadoController.php`
- Gestiona la característica de agregar columnas dinámicas ("Campos Personalizados") a las operaciones sin necesidad de hacer migraciones de base de datos. Se guarda el esquema general y en `storeValor()` se almacenan los valores individuales ingresados por operación.

## 6. `ReporteController.php`
- Exportaciones en CSV, Excel nativo (`exportExcelProfesional`), y resúmenes ejecutivos extraídos de las vistas en pantalla de la matriz de seguimiento. Envío automático por correo.

## 7. `LogisticaCorreoCCController.php`
- Administra un listado de correos que van copiados (CC) por defecto o bajo demanda en los reportes o notificaciones logísticas.

## 8. `EquipoController.php`
- Gestiona la asignación de roles de supervisores y configuración de equipos dentro del área de logística.
