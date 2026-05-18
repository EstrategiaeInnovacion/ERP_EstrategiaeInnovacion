# Guía: Importación Masiva de Actividades

## ¿Qué hace?

Carga muchas tareas de una sola vez desde un archivo Excel (`.xlsx`) o CSV, en lugar de crearlas una por una.

---

## Formato del archivo

### 1. Descarga la plantilla

1. Ve a **Actividades** (`/activities`)
2. Da clic en **Importar** (botón azul)
3. Da clic en el link **"plantilla de ejemplo"** dentro del modal

### 2. Columnas que reconoce el sistema

| Columna | ¿Obligatoria? | Default | Notas |
|---|---|---|---|
| `nombre_actividad` | ✅ Sí | — | La descripción de la tarea |
| `fecha_compromiso` | ❌ No | Hoy | Formatos aceptados: `2026-05-20`, `20/05/2026`, `20-05-2026` |
| `area` | ❌ No | General | |
| `cliente` | ❌ No | — | |
| `prioridad` | ❌ No | Media | Acepta: Alta / Media / Baja / High / Low / Urgente |
| `tipo_actividad` | ❌ No | Operativo | |
| `hora_inicio` | ❌ No | — | Formato: `09:00` |
| `hora_fin` | ❌ No | — | Formato: `11:00` |
| `comentarios` | ❌ No | — | |

### 3. Nombres alternativos de columna

El sistema también reconoce estos otros nombres:

| Columna principal | También funciona con |
|---|---|
| `nombre_actividad` | `actividad`, `descripcion`, `tarea` |
| `fecha_compromiso` | `fecha`, `fechalimite`, `deadline` |
| `comentarios` | `comentario`, `notas`, `observaciones` |
| `hora_inicio` | `inicio` |
| `hora_fin` | `fin` |

**Importante:** la primera fila **debe** contener los encabezados. No importa el orden de las columnas.

---

## Paso a paso para importar

### 1. Prepara tu archivo

- Abre Excel (o tu editor de CSV)
- Fila 1 = nombres de columna (ej: `nombre_actividad`, `fecha_compromiso`, `area`)
- Fila 2 en adelante = los datos de cada tarea

### 2. Ve a la página de Actividades

```
[IP]/activities
```

### 3. Da clic en "Importar"

Botón azul ubicado junto al botón "Excel" en la barra de herramientas.

### 4. Selecciona a quién asignar las tareas

- **"Yo"** si son tus propias tareas
- O elige a otro usuario del listado

Todas las tareas del archivo se asignarán a esa misma persona.

### 5. Selecciona un proyecto (opcional)

Si todas las tareas pertenecen al mismo proyecto, elígelo aquí. Si no, déjalo en "Sin proyecto".

### 6. Elige tu archivo

Da clic en "Seleccionar archivo" y escoge tu `.xlsx`, `.xls` o `.csv`.

### 7. Da clic en "Importar"

El botón azul dentro del modal.

### 8. Espera el resultado

| Resultado | Significa |
|---|---|
| ✅ Mensaje verde | "Importación completada: X actividades creadas" — todo bien |
| ❌ Mensaje rojo | Algo falló. Revisa el mensaje de error |
| 🔄 La página se recarga sola | Las tareas ya están en el tablero |

---

## ¿Qué estatus tendrán las tareas importadas?

**"Por Aprobar"** — el mismo que si las crearas manualmente. Tu supervisor las revisará y las aprobará desde el botón ✅ en cada fila.

---

## Después de importar: editar datos faltantes

Si alguna columna quedó vacía o con datos incorrectos, el **supervisor** puede corregirla desde el botón de lápiz ✏️ en cada tarea:

| Campo editable | Sí/No |
|---|---|
| Descripción | ✅ |
| Fecha compromiso | ✅ |
| Prioridad | ✅ |
| Área | ✅ |
| Cliente | ✅ |
| Horas programadas | ✅ |
| **Responsable (reasignar)** | ✅ |
| **Proyecto** | ✅ |
| Estatus | ✅ |
| Comentarios | ✅ |

---

## CSV vs Excel

| Formato | Extensiones | Recomendado |
|---|---|---|
| Excel | `.xlsx` / `.xls` | ✅ Sí — mantiene formatos de fecha |
| CSV | `.csv` | ✅ Sí — más ligero, pero asegúrate de usar comas como delimitador |
