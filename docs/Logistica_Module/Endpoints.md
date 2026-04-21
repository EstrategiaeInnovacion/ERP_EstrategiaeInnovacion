# Endpoints del Módulo de Logística

Todos los endpoints requieren autenticación y el middleware de área `area.logistica`, excepto las rutas de consulta pública. El prefijo global es `/logistica`.

## Consulta Pública (Sin autenticación)
- `GET /logistica/consulta-publica`: Vista de entrada para clientes.
- `GET /logistica/consulta-publica/buscar`: Retorna en formato JSON el estado y el historial SGM dada una factura o un pedimento.

## Dashboard y Matriz
- `GET /logistica`: Redirección y dashboard general.
- `GET /logistica/matriz-seguimiento`: El panel de la matriz de seguimiento. Acepta query params para filtros.

## Operaciones Principales (`/logistica/operaciones`)
- `GET, POST, PUT, DELETE /logistica/operaciones`: CRUD RESTful.
- `POST /logistica/operaciones/recalcular-status`: Dispara el recálculo masivo de retrasos basados en las fechas actuales.
- `PUT /logistica/operaciones/{id}/status`: Marca la operación como completada.
- `GET /logistica/operaciones/{id}/historial`: Devuelve el JSON con la bitácora SGM (fechas y observaciones).

## Configuración de Columnas (`/logistica/columnas-config`)
- `GET /ejecutivos`: Lista a los empleados que pueden tener vistas.
- `GET /ejecutivo/{empleadoId}`: Retorna configuración JSON del layout de un empleado.
- `POST /guardar`: Guarda un bloque de visibilidad.
- `POST /guardar-completa`: Guarda y reordena todas las columnas.
- `GET /global` y `POST /guardar-global`: Ajusta la configuración genérica.

## Pedimentos (`/logistica/pedimentos`)
- `GET /`: Agrupados por clave.
- `POST /`: Creación de un registro general.
- `GET /{id}`: Detalles de clave (todas las operaciones).
- `DELETE /{id}`: Eliminar.
- `PUT /{id}/estado-pago`: Marca como cobrado/pagado.
- `POST /marcar-pagados`: Actualización en lote (Bulk action).
- `GET /clave/{clave}`: Obtiene operaciones de una clave para un modal.
- `POST /actualizar-individual`: Atualiza un registro vía AJAX.
- `GET /monedas/list`: Endpoints de utilidad para selectores.

## Pedimentos Gestión / Catálogos Secundarios (`/logistica/pedimentos/gestion`)
- `GET, POST, PUT, DELETE /`: CRUD AJAX.
- `POST /importar-legacy`: Carga un CSV/Excel.
- `DELETE /limpiar-todo`: Truncar.
- `GET /categorias-list`, `GET /subcategorias-list`: Autocompletar opciones.

## Campos Personalizados (`/logistica/campos-personalizados`)
- `GET, POST, PUT, DELETE /`: ABM de la meta-data de los campos dinámicos.
- `PUT /{id}/toggle-activo`: Habilita/Deshabilita columnas globalmente.
- `POST /valor`: Guarda un input capturado de la celda de una matriz.
- `GET /activos`: Devuelve lista de inputs necesarios para pintar.
- `GET /operacion/{id}/valores`: Retorna con qué se debe rellenar el formulario.

## Catálogos CRUD Genéricos
*Siguen el estándar REST.*
- `/logistica/clientes` (Incluye `POST /importar`, `POST /asignar-ejecutivo`, `DELETE /all/delete`).
- `/logistica/agentes`.
- `/logistica/transportes` (Incluye `GET /por-tipo`).
- `/logistica/correos-cc` (Incluye `GET /api`).
- `/logistica/equipo` (Gestión de staff y supervisores).
- `/logistica/post-operaciones/globales` y `/operaciones`.

## Reportes (`/logistica/reportes`)
- `GET /`: Vista principal de reporteo.
- `GET /export`: CSV general.
- `GET /exportar-matriz`: CSV detallado basado en filtros actuales de pantalla.
- `GET /export-excel`: Generación con `PhpSpreadsheet` (Bordes, Colores).
- `GET /resumen/exportar`: Informe gerencial.
- `GET /pedimentos/exportar`: CSV de pedimentos cobrados.
- `POST /enviar-correo`: Envía automáticamente el archivo procesado a la lista CC configurada.
