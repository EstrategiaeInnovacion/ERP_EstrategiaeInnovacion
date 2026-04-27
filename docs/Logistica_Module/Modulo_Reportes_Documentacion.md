# Módulo Logística — Reportes y Exportaciones — Documentación Maestra
> **ERP Estrategia e Innovación** · Versión documental: Abril 2026  
> Audiencia: Gerentes de logística, área contable, desarrolladores

---

## Tabla de Contenido

1. [Visión General](#1-visión-general)
2. [Arquitectura del Módulo](#2-arquitectura-del-módulo)
3. [Filtros del Reporte (Base Compartida)](#3-filtros-del-reporte-base-compartida)
4. [Dashboard: Estadísticas de KPIs](#4-dashboard-estadísticas-de-kpis)
5. [Exportación CSV — Básico](#5-exportación-csv--básico)
6. [Exportación CSV — Matriz de Seguimiento Filtrada](#6-exportación-csv--matriz-de-seguimiento-filtrada)
7. [Exportación Excel Profesional (PhpSpreadsheet)](#7-exportación-excel-profesional-phpspreadsheet)
8. [Exportación Resumen por Ejecutivo](#8-exportación-resumen-por-ejecutivo)
9. [Envío por Correo Electrónico](#9-envío-por-correo-electrónico)
10. [Control de Acceso por Rol](#10-control-de-acceso-por-rol)
11. [Referencia de Rutas](#11-referencia-de-rutas)
12. [Guía de Mantenimiento del Módulo](#12-guía-de-mantenimiento-del-módulo)

---

## 1. Visión General

El módulo de **Reportes** provee cinco formatos de exportación y un dashboard de KPIs sobre las operaciones logísticas. Todos comparten la misma lógica de filtrado a través del método privado `obtenerQueryBase()`, que replica los filtros de la Matriz de Seguimiento y añade filtros de período.

### Propósito de negocio

| Necesidad | Solución |
|---|---|
| Indicadores de rendimiento en tiempo real | Dashboard con 5 métricas de status |
| Exportar lista plana de operaciones | `exportCSV()` — CSV básico |
| Exportar matriz filtrada igual que en pantalla | `exportMatrizSeguimiento()` — CSV con filtros activos |
| Reporte ejecutivo con formato profesional | `exportExcelProfesional()` — PhpSpreadsheet con gráficas y colores |
| Resumen por ejecutivo | `exportResumenEjecutivo()` — CSV agrupado por ejecutivo |
| Enviar reporte a lista de correos CC | `enviarCorreo()` — Mailable con adjunto |

---

## 2. Arquitectura del Módulo

```
┌────────────────────────────────────────────────────────────────────┐
│                         REPORTES                                   │
│                                                                    │
│  Middleware: auth + area.logistica                                  │
│  ReporteController → /logistica/reportes                           │
│                                                                    │
│  ┌──────────────────────────────────────────────────────────────┐  │
│  │  [PRIVADO] obtenerQueryBase(Request)                         │  │
│  │    ↓ Filtros de Spatie + período (semanal/mensual/anual)     │  │
│  │    ↓ Role-based restriction (igual que Matriz)               │  │
│  │    → Retorna Query Builder listo para ejecutar               │  │
│  │                                                              │  │
│  │  [PÚBLICOS]                                                  │  │
│  │  index()                 → Dashboard con stats              │  │
│  │  exportCSV()             → CSV plano                         │  │
│  │  exportMatrizSeguimiento()→ CSV con filtros activos          │  │
│  │  exportExcelProfesional() → Excel (PhpSpreadsheet)           │  │
│  │  exportResumenEjecutivo() → CSV agrupado por ejecutivo       │  │
│  │  enviarCorreo()          → Email con Excel adjunto           │  │
│  └──────────────────────────────────────────────────────────────┘  │
│                                                                    │
│  Dependencias                                                      │
│  ────────────                                                       │
│  PhpOffice\PhpSpreadsheet  → exportExcelProfesional()              │
│  LogisticaCorreoCC         → Lista de destinatarios en correo      │
│  LogisticaReporteMailable  → Mailable para envío                   │
└────────────────────────────────────────────────────────────────────┘
```

---

## 3. Filtros del Reporte (Base Compartida)

El método privado `obtenerQueryBase(Request $request)` centraliza todos los filtros:

### Filtros de status y búsqueda (igual que la Matriz)

| Parámetro GET | Comportamiento |
|---|---|
| `filter[cliente]` | Busca nombre por ID en catálogo, filtra por nombre |
| `filter[ejecutivo]` | LIKE en `ejecutivo` |
| `filter[status]` | En `status_manual` OR `status_calculado`; `todos` = sin filtro |
| `filter[fecha_creacion_desde]` | `WHERE created_at >= fecha` |
| `filter[fecha_creacion_hasta]` | `WHERE created_at <= fecha` |
| `filter[search]` | LIKE en `operacion`, `no_pedimento`, `referencia_cliente` |

### Filtros de período adicionales (exclusivos del reporte)

| Parámetro GET | Comportamiento |
|---|---|
| `periodo` | `semanal` (últimos 7 días), `mensual` (mes actual), `anual` (año actual) |
| `mes` | Mes específico (1-12), combinado con `anio` |
| `anio` | Año específico; sin `mes` = todo el año |

Estos filtros se aplican **en la misma query**, no como post-proceso.

### Control de acceso por rol (idéntico a la Matriz)

```php
// ReporteController::obtenerQueryBase()
if ($esAdmin) {
    // Ve todo
} elseif ($empleadoActual) {
    $query->where('ejecutivo', 'like', "%{$empleadoActual->nombre}%");
} else {
    $query->where('id', 0); // Sin acceso
}
```

---

## 4. Dashboard: Estadísticas de KPIs

**Ruta**: `GET /logistica/reportes`

El dashboard calcula 5 métricas sobre las operaciones filtradas:

| KPI | Cálculo |
|---|---|
| `en_tiempo` | Operaciones con `status_calculado = 'In Process'` y sin retraso |
| `en_riesgo` | Próximas a vencer: en proceso pero con ≥ 80% del target cumplido |
| `con_retraso` | `status_calculado = 'Out of Metric'` |
| `completado_tiempo` | `status_manual = 'Done'` y sin retraso al momento de cerrar |
| `completado_retraso` | `status_manual = 'Done'` pero tenía retraso al cerrar |

Retorna también la lista de operaciones paginada para el período seleccionado.

---

## 5. Exportación CSV — Básico

**Ruta**: `GET /logistica/reportes/exportar-csv`

Exporta todas las operaciones del query base como CSV plano, sin formato especial:

```php
$headers = [
    'Content-Type' => 'text/csv',
    'Content-Disposition' => 'attachment; filename="logistica_reporte_' . now()->format('Ymd') . '.csv"'
];

return response()->streamDownload(function() use ($query) {
    $handle = fopen('php://output', 'w');
    
    // Encabezados
    fputcsv($handle, ['Operación', 'Cliente', 'Ejecutivo', 'Pedimento', 'Status', 'Fecha Arribo', ...]);
    
    // Datos en chunks para no agotar la memoria
    $query->chunk(200, function ($operaciones) use ($handle) {
        foreach ($operaciones as $op) {
            fputcsv($handle, [$op->operacion, $op->cliente, ...]);
        }
    });
    
    fclose($handle);
}, 'logistica_reporte.csv', $headers);
```

---

## 6. Exportación CSV — Matriz de Seguimiento Filtrada

**Ruta**: `GET /logistica/reportes/exportar-matriz`

Exporta exactamente lo que el usuario ve en pantalla (con los mismos filtros activos). Incluye:
- Todos los parámetros de filtro de la URL
- Campos personalizados: añade las columnas de campos personalizados al CSV, consultando `ValorCampoPersonalizado`

```
Operación | Cliente | Ejecutivo | ... | Factura FTA | Valor Aduana | Requiere OG
OP-001    | XYZ     | Juan P.   | ... | FAC-001      | 150,000      | Sí
OP-002    | ABC     | María L.  | ... | FAC-002      | 85,500       | No
```

---

## 7. Exportación Excel Profesional (PhpSpreadsheet)

**Ruta**: `GET /logistica/reportes/exportar-excel`

La exportación más compleja. Genera un archivo `.xlsx` con:

### Estructura del Excel generado

```
Hoja 1: "Resumen"
  ├── Encabezado con logo/nombre de la empresa
  ├── Tabla de KPIs (En Tiempo, En Riesgo, Con Retraso)
  └── Gráfica de pastel (PhpSpreadsheet Chart API)

Hoja 2: "Operaciones"
  ├── Fila de encabezados (estilo: fondo azul, texto blanco, negrita)
  ├── Datos de operaciones con formato condicional:
  │   ├── In Process   → Fondo verde claro (#E8F5E9)
  │   ├── Out of Metric→ Fondo rojo claro (#FFEBEE)
  │   └── Done         → Fondo gris claro (#F5F5F5)
  ├── Filtros automáticos en encabezados
  └── Columnas auto-ajustadas en ancho

Hoja 3: "Por Ejecutivo" (si hay datos)
  └── Tabla resumen: Ejecutivo | Total Ops | En Tiempo | Con Retraso | % Rendimiento
```

### Implementación con PhpSpreadsheet

```php
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

$spreadsheet = new Spreadsheet();

// Hoja 1: KPIs
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Resumen');
$sheet->getStyle('A1:E1')->applyFromArray([
    'font' => ['bold' => true, 'size' => 14, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'color' => ['rgb' => '1565C0']]
]);

// Añadir datos y formatear
// ...

// Respuesta como StreamedResponse
$writer = new Xlsx($spreadsheet);
return response()->streamDownload(
    fn() => $writer->save('php://output'),
    'reporte_logistica_' . now()->format('Ymd_His') . '.xlsx'
);
```

---

## 8. Exportación Resumen por Ejecutivo

**Ruta**: `GET /logistica/reportes/exportar-resumen-ejecutivo`

Genera un CSV con una fila por ejecutivo, mostrando sus métricas de rendimiento:

```
Ejecutivo       | Total | En Tiempo | En Riesgo | Retraso | % Rendimiento | Completadas
Juan Pérez      | 25    | 15        | 5         | 5       | 60.0%         | 10
María López     | 18    | 14        | 2         | 2       | 77.8%         | 8
```

Cálculo de `% Rendimiento`:
```php
$rendimiento = ($enTiempo + $completadasTiempo) / $total * 100;
```

Este reporte solo es generado por administradores (no está restringido por ejecutivo individual).

---

## 9. Envío por Correo Electrónico

**Ruta**: `POST /logistica/reportes/enviar-correo`

Genera el Excel Profesional y lo envía como adjunto a la lista de correos CC configurada en el módulo de Correos CC.

### Proceso completo

```php
public function enviarCorreo(Request $request): JsonResponse
{
    // 1. Obtener destinatarios del catálogo de CC
    $destinatarios = LogisticaCorreoCC::activos()
        ->where('tipo', 'administrador') // O 'notificacion' según config
        ->pluck('correo');
    
    if ($destinatarios->isEmpty()) {
        return response()->json(['success' => false, 'message' => 'No hay destinatarios configurados'], 422);
    }
    
    // 2. Generar el Excel
    $archivo = $this->generarArchivoExcel($request);
    
    // 3. Enviar usando Mailable
    Mail::to($destinatarios)->send(
        new LogisticaReporteMailable($archivo, $request->periodo ?? 'mensual')
    );
    
    return response()->json(['success' => true, 'message' => "Reporte enviado a {$destinatarios->count()} destinatarios"]);
}
```

### `LogisticaReporteMailable`

**Archivo**: `app/Mail/LogisticaReporteMailable.php`

El mailable adjunta el Excel generado y usa una plantilla blade para el cuerpo del correo.

---

## 10. Control de Acceso por Rol

| Usuario | Datos en el reporte |
|---|---|
| Admin | Todas las operaciones |
| Supervisor de Logística | Todas las operaciones |
| Ejecutivo normal | Solo sus operaciones |
| Sin empleado vinculado | Ninguna operación (`WHERE id = 0`) |

La lógica es **idéntica** a la del `index()` de `OperacionLogisticaController`, implementada en `obtenerQueryBase()` para evitar duplicación.

> **Nota de arquitectura**: El controlador de Reportes y el de Matriz de Seguimiento tienen lógica de filtrado duplicada. Si se añade un nuevo filtro a la Matriz, se debe añadir también a `ReporteController::obtenerQueryBase()`.

---

## 11. Referencia de Rutas

**Middleware**: `auth`, `area.logistica`  
**Prefijo**: `/logistica/reportes`  
**Nombre base**: `logistica.reportes.`

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `GET` | `/logistica/reportes` | `logistica.reportes.index` | Dashboard con KPIs |
| `GET` | `/logistica/reportes/exportar-csv` | `logistica.reportes.exportar-csv` | CSV plano |
| `GET` | `/logistica/reportes/exportar-matriz` | `logistica.reportes.exportar-matriz` | CSV con filtros activos |
| `GET` | `/logistica/reportes/exportar-excel` | `logistica.reportes.exportar-excel` | Excel con formato (PhpSpreadsheet) |
| `GET` | `/logistica/reportes/exportar-resumen-ejecutivo` | `logistica.reportes.resumen-ejecutivo` | CSV resumen por ejecutivo |
| `POST` | `/logistica/reportes/enviar-correo` | `logistica.reportes.enviar-correo` | Enviar Excel por email |

---

## 12. Guía de Mantenimiento del Módulo

---

### 🔴 CRÍTICO: `obtenerQueryBase()` y el `index()` de Matriz pueden desincronizarse

La lógica de filtrado en `ReporteController::obtenerQueryBase()` es una copia del `OperacionLogisticaController::index()`. Si se añade un filtro nuevo a uno, debe añadirse manualmente al otro.

**Síntoma**: El reporte exportado muestra más/menos operaciones de las que aparecen en la Matriz.

**Solución**: Extraer `obtenerQueryBase()` a un servicio compartido `LogisticaQueryService`:
```php
// app/Services/LogisticaQueryService.php
class LogisticaQueryService {
    public function buildQuery(Request $request, Empleado $empleadoActual, bool $esAdmin): Builder { ... }
}
// Inyectar en ambos controladores via constructor
```

---

### 🟡 IMPORTANTE: El Excel generado puede ser lento con muchas operaciones

PhpSpreadsheet carga todas las filas en memoria antes de generar el archivo. Con más de 2,000 operaciones, el tiempo de generación puede superar el timeout de PHP (default 30 segundos).

**Solución**:
```php
// Aumentar timeout para esta ruta específica
set_time_limit(120);

// O generar el Excel en background y notificar cuando esté listo
ExportarExcelJob::dispatch($filtros, $usuario)->onQueue('reportes');
```

---

### 🟡 IMPORTANTE: `enviarCorreo()` envía el correo en el mismo request HTTP

Si el envío falla (SMTP caído), el usuario recibe un 500. No hay retry ni cola.

**Recomendación**: Usar Laravel Mail con `queue()`:
```php
Mail::to($destinatarios)->queue(new LogisticaReporteMailable(...));
```

---

### 🟢 SEGURO: Añadir una nueva columna al CSV básico

En `exportCSV()`:
1. Añadir el encabezado al array de `fputcsv`
2. Añadir el campo en la fila de datos
3. Si el campo es relación, asegurarse de añadirlo al `with()` o `select()` de la query

---

### 🟢 SEGURO: Añadir una nueva hoja al Excel Profesional

```php
$sheet4 = $spreadsheet->createSheet();
$sheet4->setTitle('Mi Nueva Hoja');
// Poblar con datos...
```

---

### 🟢 SEGURO: Añadir un nuevo período de filtro

En `obtenerQueryBase()`, en el bloque `switch ($request->periodo)`:
```php
case 'trimestral':
    $query->whereBetween('created_at', [
        now()->startOfQuarter(),
        now()->endOfQuarter()
    ]);
    break;
```

---

### Checklist de deploy para cambios en Reportes

- [ ] ¿Se añade un filtro nuevo a la Matriz? Añadirlo también en `obtenerQueryBase()` de `ReporteController`.
- [ ] ¿Se añade una columna al reporte? Actualizar CSV, Excel y Resumen por Ejecutivo si aplica.
- [ ] ¿Se instala PHP en servidor nuevo? Verificar que `phpoffice/phpspreadsheet` está instalado: `composer require phpoffice/phpspreadsheet`.
- [ ] ¿Se cambia la lista de destinatarios del correo? Usar el módulo de Correos CC (no hardcodear en el controlador).
- [ ] ¿El Excel genera timeout? Aumentar `max_execution_time` en `php.ini` o mover a cola.

---

*Documentación generada el 27 de Abril de 2026 — ERP Estrategia e Innovación*
