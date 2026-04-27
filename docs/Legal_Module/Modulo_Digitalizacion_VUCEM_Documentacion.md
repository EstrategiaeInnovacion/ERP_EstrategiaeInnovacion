# Módulo Legal — Digitalización VUCEM — Documentación Maestra
> **ERP Estrategia e Innovación** · Versión documental: Abril 2026  
> Audiencia: Equipo de comercio exterior, área legal, administradores de sistemas, desarrolladores

---

## Tabla de Contenido

1. [Visión General](#1-visión-general)
2. [Estándar VUCEM para PDFs](#2-estándar-vucem-para-pdfs)
3. [Arquitectura del Módulo](#3-arquitectura-del-módulo)
4. [Dependencias de Binarios Externos](#4-dependencias-de-binarios-externos)
5. [Modelo de Almacenamiento Temporal](#5-modelo-de-almacenamiento-temporal)
6. [Referencia de Herramientas — `DigitalizacionController`](#6-referencia-de-herramientas--digitalizacioncontroller)
7. [Modos de Operación: `vucem` vs `general`](#7-modos-de-operación-vucem-vs-general)
8. [Flujos de Procesamiento PDF](#8-flujos-de-procesamiento-pdf)
9. [Validación de Cumplimiento VUCEM](#9-validación-de-cumplimiento-vucem)
10. [División Automática de Archivos](#10-división-automática-de-archivos)
11. [Limpieza de Archivos Temporales](#11-limpieza-de-archivos-temporales)
12. [Servicios Auxiliares](#12-servicios-auxiliares)
13. [Referencia de Rutas](#13-referencia-de-rutas)
14. [Guía de Mantenimiento del Módulo](#14-guía-de-mantenimiento-del-módulo)

---

## 1. Visión General

El módulo de **Digitalización VUCEM** es un conjunto de herramientas de procesamiento PDF diseñado para preparar documentos antes de subirlos a la **Ventanilla Única de Comercio Exterior Mexicano (VUCEM)**, el portal oficial de trámites aduanales del Gobierno de México.

VUCEM impone requisitos técnicos estrictos sobre los PDFs aceptados. Este módulo automatiza la conversión, validación y optimización de documentos para cumplir con esos estándares.

### Herramientas disponibles

| Herramienta | Endpoint | Descripción |
|---|---|---|
| **Convertir a VUCEM** | `POST /digitalizacion/convertir` | Convierte PDF a 300 DPI, escala de grises, sin encriptado |
| **Validar PDF** | `POST /digitalizacion/validar` | Verifica si un PDF ya cumple con los estándares VUCEM |
| **Comprimir PDF** | `POST /digitalizacion/comprimir` | Reduce el tamaño del PDF con niveles configurables |
| **Combinar PDFs** | `POST /digitalizacion/combinar` | Une múltiples PDFs en uno solo preservando DPI |
| **Extraer Imágenes** | `POST /digitalizacion/extraer` | Extrae todas las imágenes embebidas del PDF como ZIP |

### Propósito de negocio

| Necesidad | Solución |
|---|---|
| Preparar documentos para VUCEM sin conocimiento técnico | Interfaz web que automatiza la conversión via Ghostscript |
| Verificar cumplimiento antes de subir al portal | Herramienta de validación con checklist de 5 puntos |
| Reducir tamaño de archivos grandes | Compresión configurable (screen/ebook/printer/prepress) |
| Unir expedientes en un solo documento | Merger que preserva DPI de las imágenes |
| Recuperar imágenes de PDFs escaneados | Extractor con salida en ZIP |

---

## 2. Estándar VUCEM para PDFs

VUCEM exige que los documentos cumplan los siguientes cinco criterios:

| Criterio | Requisito | Motivo |
|---|---|---|
| **Tamaño** | ≤ 3 MB | Límite de upload del portal VUCEM/MVE |
| **Versión PDF** | ≥ 1.4 | Compatibilidad con el lector interno de VUCEM |
| **Color** | Escala de grises | Reduce tamaño; VUCEM procesa documentos en B&N |
| **Resolución** | Exactamente 300 DPI | Requisito de legibilidad documental oficial |
| **Encriptado** | Sin contraseña ni cifrado | VUCEM no puede abrir documentos protegidos |

Un PDF que falla **cualquiera** de estos cinco checks es rechazado por el portal.

---

## 3. Arquitectura del Módulo

```
┌───────────────────────────────────────────────────────────────────────┐
│                    DIGITALIZACIÓN VUCEM                               │
│                                                                       │
│  Middleware: auth + verified + area.legal                             │
│  Prefijo URL: /legal/digitalizacion                                   │
│                                                                       │
│  DigitalizacionController                                             │
│  ┌─────────────────────────────────────────────────────────────────┐  │
│  │  index()        → Vista principal (SPA-like)                   │  │
│  │  convert()      → Convierte PDF a VUCEM o modo General         │  │
│  │  validatePdf()  → Valida cumplimiento de 5 criterios VUCEM     │  │
│  │  compress()     → Comprime PDF con nivel configurable          │  │
│  │  merge()        → Une múltiples PDFs en uno                    │  │
│  │  extractImages()→ Extrae imágenes como ZIP                     │  │
│  └─────────────────────────────────────────────────────────────────┘  │
│                 │                    │                                 │
│                 ▼                    ▼                                 │
│  VucemPdfConverter              VucemImageExtractor                    │
│  (app/Services/)                (app/Services/)                        │
│       │                              │                                 │
│       ▼                              ▼                                 │
│  [Ghostscript] [QPDF]          [Pdfimages binary]                     │
│  (binarios del servidor)        (binario del servidor)                 │
│                                                                        │
│  Almacenamiento temporal                                               │
│  ─────────────────────────                                             │
│  storage/app/temp/{uniqid}_input.pdf   (archivo de entrada)           │
│  storage/app/temp/{uniqid}_VUCEM.pdf   (resultado)                    │
│  storage/app/temp/{uniqid}_merged.pdf  (merge)                        │
│  (Se eliminan al finalizar cada operación)                             │
└───────────────────────────────────────────────────────────────────────┘
```

---

## 4. Dependencias de Binarios Externos

El módulo requiere los siguientes programas instalados en el servidor:

| Binario | Usado por | Propósito |
|---|---|---|
| **Ghostscript** (`gs` en Linux, `gswin64c.exe` en Windows) | `VucemPdfConverter`, `DigitalizacionController` | Conversión de color, re-renderizado a 300 DPI, validación de versión PDF, detección de grises |
| **QPDF** | `VucemPdfConverter` | División de PDF en partes, merge preservando metadatos |
| **Pdfimages** | `VucemImageExtractor` | Extracción de imágenes embebidas en PDF |

### Verificar instalación en el servidor

```bash
# Linux
which gs && gs --version
which qpdf && qpdf --version
which pdfimages && pdfimages --version

# Windows (PowerShell)
Get-Command gswin64c.exe
Get-Command qpdf.exe
Get-Command pdfimages.exe
```

### Comportamiento si falta un binario

| Binario faltante | Efecto |
|---|---|
| Ghostscript | `convert()`, `compress()`, `validatePdf()` fallarán. Los checks de grises/versión retornan `false` con mensaje "Ghostscript no encontrado" |
| QPDF | `merge()` y la división automática en partes fallarán |
| Pdfimages | `extractImages()` fallará |

### Detección de Ghostscript en Windows vs Linux

```php
// El controlador detecta automáticamente la plataforma
if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    copy($file->getRealPath(), $inputPath);  // Windows: copy() en lugar de move()
} else {
    $file->move($tempDir, $inputFileName);   // Linux: move()
}
```

---

## 5. Modelo de Almacenamiento Temporal

Todos los PDFs se procesan en el directorio temporal del servidor, **nunca en el storage permanente**:

```
storage/app/temp/
    {uniqid}_input.pdf       ← Archivo original subido por el usuario
    {uniqid}_VUCEM.pdf       ← Resultado de convert()
    {uniqid}_compressed.pdf  ← Resultado de compress()
    {uniqid}_merged.pdf      ← Resultado de merge()
    {uniqid}_input.pdf       ← Entrada para extractImages()
    imagenes_{nombre}_{uniqid}.zip ← ZIP de imágenes extraídas
```

El directorio se crea automáticamente si no existe:
```php
$tempDir = storage_path('app/temp');
if (!file_exists($tempDir)) {
    mkdir($tempDir, 0755, true);
}
```

**Los archivos temporales se eliminan al finalizar cada operación** via `cleanupFiles()`. En caso de error, los archivos se eliminan en el bloque `catch` o `finally`.

---

## 6. Referencia de Herramientas — `DigitalizacionController`

**Archivo**: `app/Http/Controllers/Legal/DigitalizacionController.php`

---

### `index(): View`

**Ruta**: `GET /legal/digitalizacion`

Retorna la vista principal (`Legal.digitalizacion.index`). Toda la lógica de herramientas opera via AJAX desde esta vista de página única.

---

### `convert(Request $request): JsonResponse`

**Ruta**: `POST /legal/digitalizacion/convertir`

La herramienta principal. Convierte un PDF para cumplir con los estándares VUCEM o el modo general.

**Parámetros de entrada**:

| Campo | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `file` | `file` (PDF) | Sí | PDF a convertir. Máx: 50 MB |
| `modo` | `string` | No | `vucem` (default) o `general`. Define el límite de tamaño |
| `splitEnabled` | `boolean` | No | `true` = dividir en partes si excede el límite |
| `numberOfParts` | `integer` | No | Número de partes para la división (2-18). Default: 2 |
| `orientation` | `string` | No | `auto`, `portrait`, `landscape`. Default: `auto` |

**Lógica interna**:

```
1. Copiar PDF subido a storage/app/temp/{uniqid}_input.pdf
2. Configurar el umbral de auto-división:
   Config::set('vucem.auto_split_threshold', $maxBytes)
   → vucem: 3 MB; general: 10 MB
3. Llamar VucemPdfConverter::convertToVucemOptimized()
4. Si el resultado está dividido (auto_divided o splitEnabled + parts):
   → buildDividedResponse()
5. Si el resultado es un único archivo:
   → buildSingleResponse()
6. Limpiar archivos temporales
```

**Respuesta JSON — Archivo único** (`split: false`):
```json
{
  "success": true,
  "split": false,
  "modo": "vucem",
  "max_size_mb": 3,
  "exceeds_limit": false,
  "file": {
    "name": "documento_VUCEM_300DPI.pdf",
    "content": "base64encodedcontent==",
    "size": 2097152,
    "size_mb": 2.0
  },
  "original_size_mb": 8.5,
  "converted_size_mb": 2.0,
  "size_change_percent": -76.47,
  "was_reduced": true,
  "compression_attempts": 3,
  "final_quality": 35,
  "total_pages": 12,
  "warnings": [],
  "messages": ["✓ Todas las imágenes (24) están a exactamente 300 DPI"],
  "exceeded_threshold": false,
  "valid": true
}
```

**Respuesta JSON — Dividido en partes** (`split: true`):
```json
{
  "success": true,
  "split": true,
  "modo": "vucem",
  "max_size_mb": 3,
  "auto_divided": true,
  "files": [
    {
      "name": "documento_parte1_VUCEM.pdf",
      "content": "base64encodedcontent==",
      "size": 2097152,
      "size_mb": 2.0,
      "part": 1,
      "pages": 6,
      "valid": true,
      "exceeds_limit": false
    }
  ],
  "total_parts": 2,
  "original_size_mb": 8.5,
  "converted_size_mb": 4.0,
  "compression_attempts": 5,
  "final_quality": 20,
  "warnings": ["Auto-dividido en 2 partes para cumplir el límite de 3 MB"],
  "messages": []
}
```

**Nombres de archivo de salida**:
- Modo `vucem`, sin división: `{nombre_original}_VUCEM_300DPI.pdf`
- Modo `vucem`, con división: `{nombre_original}_parte{N}_VUCEM.pdf`
- Modo `general`, sin división: `{nombre_original}_10MB.pdf`
- Modo `general`, con división: `{nombre_original}_parte{N}_10MB.pdf`

---

### `validatePdf(Request $request): JsonResponse`

**Ruta**: `POST /legal/digitalizacion/validar`

Analiza un PDF contra los 5 criterios VUCEM y retorna un reporte detallado.

**Parámetros de entrada**:

| Campo | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `pdf` | `file` (PDF) | Sí | PDF a validar. Máx: 50 MB |
| `modo` | `string` | No | `vucem` (default) o `general`. Afecta el límite de tamaño evaluado |

**Respuesta JSON**:
```json
{
  "success": true,
  "allOk": false,
  "fileName": "documento.pdf",
  "modo": "vucem",
  "max_size_mb": 3,
  "checks": {
    "size": {
      "label": "Tamaño < 3 MB",
      "ok": true,
      "value": "2.5 MB"
    },
    "version": {
      "label": "Versión PDF 1.4 o superior",
      "ok": true,
      "value": "1.7"
    },
    "grayscale": {
      "label": "Contenido en escala de grises",
      "ok": false,
      "value": "Color detectado en 3 de 10 página(s)"
    },
    "dpi": {
      "label": "Resolución exacta 300 DPI",
      "ok": false,
      "value": "Imágenes a 72 DPI detectadas",
      "status": "error",
      "pages": [],
      "images": []
    },
    "encryption": {
      "label": "Sin contraseña / sin encriptar",
      "ok": true,
      "value": "No encriptado"
    }
  }
}
```

**Checks realizados**:

| Check | Método interno | Herramienta usada |
|---|---|---|
| Tamaño | `filesize()` de PHP | Nativo |
| Versión PDF | `getPdfVersion()` → Ghostscript o lectura del header binario | Ghostscript / lectura directa |
| Escala de grises | `checkGrayscale()` → `gs -sDEVICE=inkcov` | Ghostscript |
| DPI exacto 300 | `checkDpi()` | Ghostscript / Pdfimages |
| Sin encriptado | `checkEncryption()` | QPDF o Ghostscript |

---

### `compress(Request $request): JsonResponse`

**Ruta**: `POST /legal/digitalizacion/comprimir`

Comprime un PDF con uno de los 4 niveles de Ghostscript.

**Parámetros de entrada**:

| Campo | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `file` | `file` (PDF) | Sí | PDF a comprimir. Máx: 100 MB |
| `compressionLevel` | `string` | Sí | `screen`, `ebook`, `printer`, `prepress` |

**Niveles de compresión de Ghostscript**:

| Nivel | Calidad | Uso recomendado | DPI resultante (aprox.) |
|---|---|---|---|
| `screen` | Baja | Solo lectura en pantalla | 72 DPI |
| `ebook` | Media | Distribución digital | 150 DPI |
| `printer` | Alta | Impresión de calidad | 300 DPI |
| `prepress` | Máxima | Imprenta profesional | 300+ DPI con colores preservados |

**Respuesta JSON**:
```json
{
  "success": true,
  "file": {
    "name": "documento_compressed.pdf",
    "content": "base64encodedcontent==",
    "size_mb": 1.2
  },
  "input_size_mb": 8.5,
  "output_size_mb": 1.2,
  "reduction_percent": 85.88,
  "level": "printer"
}
```

---

### `merge(Request $request): JsonResponse`

**Ruta**: `POST /legal/digitalizacion/combinar`

Une entre 2 y 50 PDFs en un solo documento.

**Parámetros de entrada**:

| Campo | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `files` | `array` de archivos PDF | Sí | Entre 2 y 50 PDFs. Cada uno máx: 50 MB |
| `outputName` | `string` | No | Nombre base del archivo resultante. Máx: 200 chars |

**Nombre de salida**: `{outputName}_combinado.pdf` (caracteres no alfanuméricos en `outputName` se reemplazan por `_`)

**Respuesta JSON**:
```json
{
  "success": true,
  "file": {
    "name": "expediente_combinado.pdf",
    "content": "base64encodedcontent==",
    "size_mb": 5.3
  },
  "files_merged": 4,
  "total_size_mb": 8.5,
  "output_size_mb": 5.3
}
```

> **Nota**: El merge usa `VucemPdfConverter::mergePdfsKeepDpi()` que preserva el DPI de las imágenes del documento original.

---

### `extractImages(Request $request): JsonResponse`

**Ruta**: `POST /legal/digitalizacion/extraer`

Extrae todas las imágenes embebidas en el PDF y las empaqueta en un archivo ZIP.

**Parámetros de entrada**:

| Campo | Tipo | Obligatorio | Descripción |
|---|---|---|---|
| `pdf` | `file` (PDF) | Sí | PDF del cual extraer imágenes. Máx: 100 MB |

**Respuesta JSON**:
```json
{
  "success": true,
  "file": {
    "name": "imagenes_documento.zip",
    "content": "base64encodedcontent==",
    "size_mb": 12.4
  },
  "images_count": 47
}
```

> **Nota de rendimiento**: Esta operación tiene un timeout extendido de 1200 segundos (`set_time_limit(1200)`). PDFs muy grandes (100+ MB con cientos de imágenes) pueden tardar varios minutos.

---

## 7. Modos de Operación: `vucem` vs `general`

El parámetro `modo` afecta los límites de tamaño evaluados y el umbral de auto-división:

| Parámetro | Modo `vucem` | Modo `general` |
|---|---|---|
| Límite de tamaño | 3 MB | 10 MB |
| Umbral auto-división | 3 MB | 10 MB |
| Sufijo archivo resultado | `_VUCEM_300DPI.pdf` / `_VUCEM.pdf` | `_10MB.pdf` |
| Mensaje de advertencia | "supera el límite de 3 MB para VUCEM/MVE" | "supera el límite de 10 MB para otros trámites" |

La constante en código:
```php
protected const MAX_VUCEM_BYTES   = 3  * 1024 * 1024;  // 3 MB
protected const MAX_GENERAL_BYTES = 10 * 1024 * 1024;  // 10 MB

protected function maxBytesForMode(string $modo): int
{
    return $modo === 'general' ? self::MAX_GENERAL_BYTES : self::MAX_VUCEM_BYTES;
}
```

---

## 8. Flujos de Procesamiento PDF

### Flujo: Convertir a VUCEM (modo vucem, sin división)

```
Usuario sube PDF → POST /digitalizacion/convertir
    │
    ▼
Copiar a storage/app/temp/{id}_input.pdf
    │
    ▼
Config::set('vucem.auto_split_threshold', 3MB)
    │
    ▼
VucemPdfConverter::convertToVucemOptimized(
    input, output, splitEnabled=false, parts=2, orientation='auto'
)
    │
    ├─ Ghostscript convierte color → escala de grises
    ├─ Re-renderiza a 300 DPI exactos
    ├─ Comprime iterativamente (múltiples intentos con calidades decrecientes)
    └─ Retorna result array con métricas
    │
    ▼
¿Resultado ≤ 3 MB?
    ├─ Sí → buildSingleResponse() → JSON con archivo en base64
    └─ No → Auto-dividir en partes → buildDividedResponse() → JSON con array de partes
    │
    ▼
cleanupFiles([input, output]) → eliminar temporales
    │
    ▼
Browser recibe JSON → Frontend genera descarga automática del/los archivos
```

### Flujo: Validar PDF

```
Usuario sube PDF → POST /digitalizacion/validar
    │
    ▼
Copiar a storage/app/temp/{id}_validar.pdf
    │
    ▼
Ejecutar 5 checks simultáneos:
    ├─ filesize() → ¿≤ límite?
    ├─ gs -c → ¿versión ≥ 1.4?
    ├─ gs -sDEVICE=inkcov → ¿es escala de grises?
    ├─ checkDpi() → ¿exactamente 300 DPI?
    └─ checkEncryption() → ¿sin contraseña?
    │
    ▼
Retornar JSON con resultado de cada check + allOk
    │
    ▼
@unlink($tempPath) en bloque finally
```

---

## 9. Validación de Cumplimiento VUCEM

### Detección de Escala de Grises

El controlador usa Ghostscript con el device `inkcov` que retorna valores CMYK por página:

```
Formato output: C M Y K (para cada página)
Ej: 0.00000 0.00000 0.00000 0.23432 → Escala de grises (C=M=Y=0)
    0.12345 0.00000 0.05678 0.23432 → Tiene color (C > 0.0001 o M > 0.0001 o Y > 0.0001)
```

Si Ghostscript no puede parsear el output, hay un método alternativo (`checkGrayscaleAlternative`) que renderiza la primera página a PPM y analiza si tiene color.

### Detección de Versión PDF

**Método primario**: Ghostscript via PostScript (`pdfdict /Version get`).

**Método fallback**: Lectura directa del header binario del PDF:
```php
// Los PDFs empiezan con: %PDF-1.7
if (preg_match('/%PDF-(\d+\.\d+)/', $header, $matches)) {
    return $matches[1];
}
```

### Resultado de Validación

El campo `allOk` del JSON retornado es `true` únicamente si los 5 checks son `ok = true`:
```php
$allOk = collect($checks)->every(fn($c) => $c['ok']);
```

---

## 10. División Automática de Archivos

Cuando un PDF convertido supera el límite de tamaño (3 MB en modo VUCEM), el sistema puede dividirlo automáticamente:

**Disparadores de la división**:
1. `splitEnabled = true` en el request → División manual por el usuario en N partes
2. `auto_divided = true` en el resultado del converter → División automática del servicio cuando detecta que el resultado excede el umbral

**Configuración del umbral**:
```php
Config::set('vucem.auto_split_threshold', $maxBytes);
// Esta configuración es consumida por VucemPdfConverter::convertToVucemOptimized()
```

**Límites de división**:
- Mínimo: 2 partes
- Máximo: 18 partes
- El usuario define cuántas partes desea; el sistema divide las páginas uniformemente

---

## 11. Limpieza de Archivos Temporales

Todos los métodos llaman a `cleanupFiles()` para eliminar archivos temporales al finalizar:

```php
protected function cleanupFiles(array $paths): void
{
    foreach ($paths as $path) {
        if ($path && file_exists($path)) {
            @unlink($path);
        }
    }
}
```

**Cuándo se llama**:
- En el flujo normal: al final de `buildSingleResponse()` y `buildDividedResponse()`
- En el bloque `catch`: para limpiar si ocurre una excepción
- `extractImages()` usa bloque `finally` para garantizar la limpieza del input

> **Riesgo**: Si el proceso PHP se interrumpe abruptamente (OOM kill, timeout del servidor web antes del timeout de PHP), los archivos temporales pueden quedar en disco. Se recomienda un cron job de limpieza de `storage/app/temp/` que elimine archivos más antiguos de X horas.

---

## 12. Servicios Auxiliares

### `VucemPdfConverter` — `app/Services/VucemPdfConverter.php`

Encapsula la comunicación con Ghostscript y QPDF para todas las operaciones de conversión.

**Métodos principales usados por el controlador**:

| Método | Descripción |
|---|---|
| `convertToVucemOptimized($input, $output, $splitEnabled, $parts, $orientation)` | Conversión principal: color→gris, re-DPI a 300, compresión iterativa |
| `validateVucemCompliance($path)` | Verifica si el archivo en `$path` cumple estándares VUCEM |
| `validateDpi($path)` | Valida que el DPI sea exactamente 300 |
| `compressPdf($input, $output, $level)` | Comprime usando Ghostscript con nivel `screen/ebook/printer/prepress` |
| `mergePdfsKeepDpi($inputPaths, $output)` | Une múltiples PDFs via QPDF preservando DPI |

### `VucemImageExtractor` — `app/Services/VucemImageExtractor.php`

Encapsula el uso del binario `pdfimages` para extracción de imágenes.

**Método principal**:

| Método | Descripción |
|---|---|
| `extractImagesToZip($inputPath, $outputZipPath)` | Extrae imágenes del PDF y las empaqueta en ZIP |

**Retorno**:
```php
[
    'success'      => true,
    'images_count' => 47,
    'zip_size_mb'  => 12.4,
]
```

---

## 13. Referencia de Rutas

**Middleware**: `auth`, `verified`, `area.legal`  
**Prefijo**: `/legal`  
**Nombre base**: `legal.`

| Método | URI | Nombre | Descripción |
|---|---|---|---|
| `GET` | `/legal/digitalizacion` | `legal.digitalizacion.index` | Vista principal con las 5 herramientas |
| `POST` | `/legal/digitalizacion/convertir` | `legal.digitalizacion.convert` | Convertir PDF a VUCEM/General |
| `POST` | `/legal/digitalizacion/validar` | `legal.digitalizacion.validate` | Validar cumplimiento VUCEM |
| `POST` | `/legal/digitalizacion/comprimir` | `legal.digitalizacion.compress` | Comprimir PDF |
| `POST` | `/legal/digitalizacion/combinar` | `legal.digitalizacion.merge` | Unir PDFs |
| `POST` | `/legal/digitalizacion/extraer` | `legal.digitalizacion.extract` | Extraer imágenes a ZIP |

**Todos los endpoints POST** retornan JSON con `Content-Type: application/json`.  
El cliente (JavaScript) recibe el archivo en base64 y dispara la descarga en el browser.

---

## 14. Guía de Mantenimiento del Módulo

---

### 🔴 CRÍTICO: Binarios externos no instalados

Si `Ghostscript`, `QPDF` o `Pdfimages` no están instalados en el servidor, todas las operaciones fallarán con un error 500.

**Verificar en producción**:
```bash
gs --version      # Debe retornar ej: "10.02.1"
qpdf --version    # Debe retornar ej: "qpdf version 11.9.1"
pdfimages -v      # Debe retornar ej: "pdfimages version 23.10.0"
```

**Si falta Ghostscript en Ubuntu/Debian**:
```bash
sudo apt-get install ghostscript
```

**Si falta QPDF**:
```bash
sudo apt-get install qpdf
```

**Si falta Pdfimages** (parte de poppler-utils):
```bash
sudo apt-get install poppler-utils
```

---

### 🔴 CRÍTICO: Archivos temporales huérfanos en disco

Si el proceso PHP muere durante una operación larga (ej: `extractImages()` con un PDF de 100 MB), los archivos temporales pueden quedarse en `storage/app/temp/`.

**Solución**: Agregar un cron job de limpieza:
```bash
# Crontab: eliminar archivos de más de 2 horas en storage/app/temp/
0 * * * * find /var/www/erp/storage/app/temp/ -mmin +120 -type f -delete
```

---

### 🔴 CRÍTICO: Timeout en operaciones largas

`extractImages()` establece `set_time_limit(1200)` (20 minutos), pero el timeout del servidor web (Nginx/Apache) o de PHP-FPM puede ser menor.

**Configurar en Nginx**:
```nginx
fastcgi_read_timeout 1200;
```

**Configurar en PHP-FPM** (`www.conf`):
```ini
request_terminate_timeout = 1200
```

---

### 🟡 IMPORTANTE: El límite de 3 MB de VUCEM puede cambiar

El portal VUCEM puede actualizar sus límites sin previo aviso. Si cambia el límite:

1. Actualizar la constante en `DigitalizacionController`:
   ```php
   protected const MAX_VUCEM_BYTES = 5 * 1024 * 1024; // Nuevo límite
   ```
2. Verificar si `VucemPdfConverter` tiene el umbral hardcodeado internamente.
3. Actualizar la documentación de la vista y el texto de las advertencias.

---

### 🟡 IMPORTANTE: Añadir una nueva herramienta PDF

Para añadir una herramienta nueva (ej: "Rotar páginas"):

1. Agregar método en `DigitalizacionController`:
   ```php
   public function rotatePdf(Request $request): JsonResponse { ... }
   ```
2. Registrar la ruta en `routes/web.php`:
   ```php
   Route::post('/digitalizacion/rotar', [DigitalizacionController::class, 'rotatePdf'])->name('digitalizacion.rotate');
   ```
3. Si requiere un binario nuevo, documentarlo en la sección de dependencias.
4. Usar siempre `cleanupFiles()` al finalizar para eliminar temporales.

---

### 🟡 IMPORTANTE: Seguridad — Inyección PostScript

El controlador pasa rutas de archivo a Ghostscript via `Process`. El riesgo de inyección está **mitigado** porque los archivos se renombran con `uniqid()` antes de pasarlos, pero se debe mantener esta práctica:

```php
// CORRECTO: nombre generado por el sistema, sin input del usuario
$inputFileName = $uniqueId . '_input.pdf';

// NUNCA hacer esto:
$inputFileName = $request->input('filename'); // ← Inyección potencial
```

---

### 🟢 SEGURO: Cambiar el nivel de compresión por defecto

En `compress()`, el default se toma del request con fallback a `printer`:
```php
$compressionLevel = $request->input('compressionLevel', 'printer');
```
Para cambiar el default, solo modificar el segundo parámetro de `input()`.

---

### 🟢 SEGURO: Ajustar el máximo de archivos para merge

Actualmente: `'files' => 'required|array|min:2|max:50'`. Para cambiar:
```php
'files' => 'required|array|min:2|max:20', // Reducir a 20
```

---

### Checklist de deploy para cambios en Digitalización VUCEM

- [ ] ¿Se actualiza el límite de tamaño VUCEM? Cambiar `MAX_VUCEM_BYTES` y verificar en `VucemPdfConverter`.
- [ ] ¿Se añade una nueva herramienta? Crear método, ruta, y añadir llamada a `cleanupFiles()`.
- [ ] ¿Se instala en un servidor nuevo? Verificar que Ghostscript, QPDF y Pdfimages estén instalados.
- [ ] ¿Servidor Windows? Verificar que el path de `gswin64c.exe` sea accesible desde PHP.
- [ ] ¿Se cambia el directorio temporal? Actualizar `storage_path('app/temp')` en todos los métodos.
- [ ] ¿Se añade una nueva operación larga? Configurar `set_time_limit()` y los timeouts de Nginx/PHP-FPM.
- [ ] Verificar que `storage/app/temp/` existe y tiene permisos de escritura para el proceso PHP.

---

*Documentación generada el 27 de Abril de 2026 — ERP Estrategia e Innovación*
