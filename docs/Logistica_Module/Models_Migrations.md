# Modelos y Migraciones del Módulo Logística

## 1. Modelos

Ubicados en `app/Models/Logistica/`:

- **`OperacionLogistica.php`**: El modelo principal. Contiene una gran cantidad de atributos desde fechas, incoterms, montos y referenciaciones. Implementa métodos como `calcularStatusPorDias()` que determinan de forma algorítmica si un paquete está en métrica o retrasado.
- **`HistoricoMatrizSgm.php`**: Un log (historial) por cada `OperacionLogistica`. Cada vez que cambia un estatus o fecha crítica, se crea un registro aquí para poder ver en qué punto en el tiempo sucedió el retraso.
- **`OperacionComentario.php`**: Comentarios de cronología, adicionales al historial del sistema.
- **`Pedimento.php` / `PedimentoOperacion.php`**: `Pedimento` guarda el catálogo de claves. `PedimentoOperacion` es la tabla pivote que asocia un número de pedimento de una operación con su estado contable ("pagado", "pendiente").
- **`CampoPersonalizadoMatriz.php` y `ValorCampoPersonalizado.php`**: Sistema EAV (Entity-Attribute-Value) para inyectar columnas virtuales. `CampoPersonalizadoMatriz` define el nombre y tipo del campo; `ValorCampoPersonalizado` asocia la `OperacionLogistica` con su valor.
- **`ColumnaVisibleEjecutivo.php`**: Modelo relacional para guardar qué columnas (tanto nativas como personalizadas) son visibles para cada Empleado (Ejecutivo de Cuenta).
- **Catálogos Básicos**: `Cliente.php`, `AgenteAduanal.php`, `Transporte.php`, `Aduana.php`, `Incoterm.php`.
- **`PostOperacion.php` / `PostOperacionOperacion.php`**: Seguimientos posteriores a la entrega (Facturaciones secundarias o devoluciones).
- **`LogisticaCorreoCC.php`**: Configuración de correos por defecto.

## 2. Migraciones

La base de datos principal de este módulo se estructuró a partir de una única migración consolidada, la cual contiene las tablas principales y catálogos en un solo archivo, con actualizaciones menores posteriores.

- **`2025_12_09_190634_create_logistica_tables.php`**: Contiene la definición base para:
  - Tablas de catálogos: `logistica_clientes`, `logistica_agentes_aduanales`, `logistica_transportes`.
  - Tabla principal: `operaciones_logisticas`.
  - Tabla de seguimiento en el tiempo: `historico_matriz_sgm`.
  - Tabla de campos personalizados y valores: `campos_personalizados_matriz`, `valores_campos_personalizados`.
  - Configuraciones de usuario: `columnas_visibles_ejecutivo`.
  - Pedimentos, Post Operaciones, y Correos CC.

Posteriormente se han realizado las siguientes migraciones complementarias:

- **`2026_02_04_163108_add_mostrar_despues_de_to_columnas_visibles_ejecutivo.php`**: Añade capacidad para ordenar horizontalmente las columnas dinámicas.
- **`2026_02_05_000000_make_empleado_id_nullable_in_columnas_visibles.php`**: Permite tener configuraciones de columnas globales o por defecto.
