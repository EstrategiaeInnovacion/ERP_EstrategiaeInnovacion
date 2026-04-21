# Documentación General del Módulo de Logística

El módulo de Logística del ERP Estrategia e Innovación está diseñado para realizar el seguimiento, control y reporte de las operaciones de importación y exportación de la empresa. Su principal vista es la "Matriz de Seguimiento", la cual funciona como un panel de control centralizado para los ejecutivos.

## Estructura del Módulo

Este módulo se compone principalmente de los siguientes elementos:

1. **Gestión de Operaciones (`OperacionLogisticaController`)**: CRUD de las operaciones principales, manejo de la matriz de seguimiento con filtros avanzados, cálculos de métricas y consulta pública.
2. **Control de Pedimentos (`PedimentoController`, `PedimentoImportController`)**: Agrupación, control de pagos (pagados/pendientes) y seguimiento de los pedimentos aduanales.
3. **Columnas y Vistas Dinámicas (`ColumnaVisibleController`)**: Configuración personalizada por ejecutivo sobre qué campos se muestran u ocultan en la matriz de seguimiento.
4. **Catálogos Base**: Gestión de Clientes, Agentes Aduanales, Transportes, Incoterms, etc.
5. **Reportes (`ReporteController`)**: Generación de reportes ejecutivos en formato CSV y Excel.
6. **Campos Personalizados (`CampoPersonalizadoController`)**: Habilidad para agregar dinámicamente nuevos atributos a la matriz de seguimiento sin modificar la base de datos central.

En la carpeta actual puedes encontrar documentación detallada sobre:
- [Controladores (Controllers)](Controllers.md)
- [Modelos y Migraciones](Models_Migrations.md)
- [Endpoints y Rutas](Endpoints.md)
- [Revisión de Seguridad y Limpieza de Código](Security_Code_Review.md)
