# Documentación General del Módulo Legal

El módulo Legal del ERP Estrategia e Innovación permite la administración de proyectos legales (consultas, escritos), organización de archivos y la digitalización y manipulación de documentos PDF, incluyendo herramientas de compresión, combinación y extracción de imágenes específicamente calibradas para el entorno VUCEM (Ventanilla Única de Comercio Exterior Mexicano).

## Estructura del Módulo

Este módulo se compone de los siguientes elementos principales:

1. **Digitalización y Herramientas PDF (`DigitalizacionController`)**: Suite de utilidades para convertir, validar, comprimir y manipular PDFs asegurando el cumplimiento con los estándares del VUCEM (tamaño, versión, DPI, escala de grises, etc.).
2. **Matriz de Consultas (`MatrizConsultaController`)**: Gestor documental y de proyectos legales, donde se almacenan expedientes, resoluciones y se adjuntan archivos estructurados por empresa.
3. **Categorías Legales (`CategoriaLegalController`)**: Clasificador jerárquico de las consultas y escritos legales.
4. **Páginas Legales (`PaginaLegalController`)**: Gestor de contenidos estáticos o informativos relacionados al área legal.

En esta carpeta puedes encontrar documentación detallada sobre:
- [Controladores (Controllers)](Controllers.md)
- [Modelos y Migraciones](Models_Migrations.md)
- [Endpoints y Rutas](Endpoints.md)
- [Revisión de Seguridad y Limpieza de Código](Security_Code_Review.md)
