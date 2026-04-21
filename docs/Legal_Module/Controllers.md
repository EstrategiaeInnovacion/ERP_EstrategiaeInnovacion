# Controladores del Módulo Legal

Los controladores se encuentran en `app/Http/Controllers/Legal/`.

## 1. `DigitalizacionController.php`
Controlador extenso dedicado al procesamiento de PDFs utilizando binarios del sistema como Ghostscript, QPDF y Pdfimages. Se apoya en las clases `VucemPdfConverter` y `VucemImageExtractor`.
- **`convert()`**: Transforma un PDF subido a las especificaciones VUCEM (300 DPI, escala de grises). Corta automáticamente el archivo en múltiples partes si excede el tamaño máximo permitido.
- **`validatePdf()`**: Ejecuta chequeos del archivo: DPI, Encriptación, Versión y Escala de Grises. Retorna un reporte en JSON.
- **`compress()`**: Reduce el tamaño de PDFs seleccionando distintos niveles de compresión (screen, ebook, printer, prepress).
- **`merge()`**: Toma un arreglo de archivos PDF y los fusiona en uno solo manteniendo la resolución.
- **`extractImages()`**: Extrae todas las imágenes incrustadas dentro de un PDF y devuelve un archivo `.zip`.

## 2. `MatrizConsultaController.php`
Gestor del inventario de proyectos y consultas legales.
- **`index()`**: Despliega la matriz, cargando categorías y permitiendo búsquedas estructuradas por tipo ("consulta" o "escritos"), empresa, y palabra clave.
- **`store() / update()`**: Guarda o actualiza los datos del proyecto. Soporta la creación automática ("al vuelo") de nuevas categorías. Sube físicamente múltiples archivos usando `Storage::disk('public')`.
- **`show()`**: Devuelve la vista detallada o un JSON si es solicitado vía API.
- **`destroy() / destroyArchivo()`**: Elimina de la base de datos y además borra los archivos físicos correspondientes del almacenamiento (Storage).
- **`downloadArchivo()`**: Proxy para descargar archivos almacenados localmente de forma segura.

## 3. `CategoriaLegalController.php`
- Controlador sencillo (CRUD) que sirve para la administración de las carpetas o jerarquías (Categorías y Subcategorías) donde se guardan los proyectos.

## 4. `PaginaLegalController.php`
- Controlador para crear y mantener páginas informativas o programas legales dentro de la intranet.

## 5. `LegalController.php`
- Controlador principal simplificado.
- **`dashboard()`**: Renderiza la vista principal o panel de bienvenida del área legal.
