/**
 * ComercioExterior/bom.js
 * Maneja: modal de nuevo BOM, lectura Excel (read-excel-file), envío al servidor.
 * La URL del endpoint se inyecta desde la vista como window.BOM_STORE_URL.
 */

import readXlsxFile from 'read-excel-file';

// ── Mapeo columna-índice → campo BD ──────────────────────────────────────────
const COL_MAP = [
    'numero_de_parte',          // A (0)
    'fraccion_arancelaria_fg',  // B (1)
    'descripcion_fg',           // C (2)
    'precio_final_usd',         // D (3)
    'nivel',                    // E (4)
    'no_parte_insumo',          // F (5)
    'descripcion_rm',           // G (6)
    'cantidad_incorporada',     // H (7)
    'precio_unitario',          // I (8)
    'unidad_de_medida',         // J (9)
    'costo_total_usd',          // K (10)
    'costo_total_pesos',        // L (11)
    'fraccion_arancelaria_rm',  // M (12)
    'pais_de_origen',           // N (13)
    'nombre_proveedor',         // O (14)
    'presenta_cambio_fraccion', // P (15)
    'cumple_demas_requisitos',  // Q (16)
    'califica_originario',      // R (17)
    'regla_de_origen',          // S (18)
    'criterio_de_origen',       // T (19)
];

let parsedItems    = [];
let parsedFileName = '';

function cellValue(row, idx) {
    const val = row[idx];
    if (val === undefined || val === null) return '';
    if (val instanceof Date) return val.toLocaleDateString('es-MX');
    return String(val).trim();
}

function isRowEmpty(row) {
    return COL_MAP.every((_, i) => !cellValue(row, i));
}

function extractItems(raw) {
    // Detectar fila de inicio de datos (saltar hasta 2 filas de encabezado)
    let dataStart = 2;
    for (let r = 0; r < Math.min(raw.length, 5); r++) {
        const first = String(raw[r]?.[0] ?? '').toLowerCase();
        if (first.includes('número') || first.includes('numero') ||
            first.includes('finished') || first.includes('raw')) {
            dataStart = r + 1;
        }
    }
    const maybeHeader = String(raw[dataStart]?.[0] ?? '').toLowerCase();
    if (maybeHeader.includes('número') || maybeHeader.includes('numero') ||
        maybeHeader.includes('no.') || maybeHeader.includes('nombre')) {
        dataStart += 1;
    }

    const items = [];
    for (let r = dataStart; r < raw.length; r++) {
        const row = raw[r];
        if (isRowEmpty(row)) continue;
        const item = {};
        COL_MAP.forEach((field, idx) => { item[field] = cellValue(row, idx); });
        items.push(item);
    }
    return items;
}

async function parseXlsx(file) {
    const raw = await readXlsxFile(file);
    return extractItems(raw);
}

async function parseCsv(file) {
    const text = await file.text();
    const raw  = text.split(/\r?\n/).map(line => {
        const cols = [];
        let inQuote = false, cur = '';
        for (let i = 0; i < line.length; i++) {
            const ch = line[i];
            if (ch === '"') { inQuote = !inQuote; }
            else if (ch === ',' && !inQuote) { cols.push(cur.trim()); cur = ''; }
            else { cur += ch; }
        }
        cols.push(cur.trim());
        return cols;
    });
    return extractItems(raw);
}

async function parseExcel(file) {
    const ext = file.name.split('.').pop().toLowerCase();
    if (ext === 'csv') return parseCsv(file);
    return parseXlsx(file);
}

function setModalState(state) {
    const info      = document.getElementById('bom-file-info');
    const dropTxt   = document.getElementById('bom-drop-text');
    const btnProc   = document.getElementById('bom-btn-procesar');
    const prog      = document.getElementById('bom-progress');
    const progBar   = document.getElementById('bom-progress-bar');

    if (state === 'idle') {
        info?.classList.add('hidden');
        dropTxt?.classList.remove('hidden');
        if (btnProc) btnProc.disabled = true;
        prog?.classList.add('hidden');
        if (progBar) progBar.style.width = '0%';
    } else if (state === 'ready') {
        info?.classList.remove('hidden');
        dropTxt?.classList.add('hidden');
        if (btnProc) { btnProc.disabled = false; btnProc.textContent = 'Cargar BOM'; }
        prog?.classList.add('hidden');
    } else if (state === 'loading') {
        if (btnProc) { btnProc.disabled = true; btnProc.textContent = 'Procesando…'; }
        prog?.classList.remove('hidden');
        if (progBar) progBar.style.width = '60%';
    }
}

function initBom() {
    const modal       = document.getElementById('bom-modal');
    const overlay     = document.getElementById('bom-overlay');
    const btnNuevo    = document.getElementById('bom-btn-nuevo');
    const btnsCerrar  = document.querySelectorAll('.bom-modal-close');
    const dropZone    = document.getElementById('bom-drop-zone');
    const fileInput   = document.getElementById('bom-file-input');
    const btnProcesar = document.getElementById('bom-btn-procesar');
    const fileNameEl  = document.getElementById('bom-file-name');
    const fileRowsEl  = document.getElementById('bom-file-rows');

    if (!modal) return;

    function openModal() {
        parsedItems    = [];
        parsedFileName = '';
        if (fileInput) fileInput.value = '';
        setModalState('idle');
        overlay?.classList.remove('hidden');
        modal.classList.remove('hidden');
    }

    function closeModal() {
        overlay?.classList.add('hidden');
        modal.classList.add('hidden');
    }

    btnNuevo?.addEventListener('click', openModal);
    btnsCerrar.forEach(btn => btn.addEventListener('click', closeModal));
    overlay?.addEventListener('click', closeModal);

    dropZone?.addEventListener('click', () => fileInput?.click());
    dropZone?.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('bom-dragover'); });
    dropZone?.addEventListener('dragleave', () => dropZone.classList.remove('bom-dragover'));
    dropZone?.addEventListener('drop', (e) => {
        e.preventDefault();
        dropZone.classList.remove('bom-dragover');
        const file = e.dataTransfer?.files?.[0];
        if (file) handleFile(file);
    });
    fileInput?.addEventListener('change', () => {
        if (fileInput.files?.[0]) handleFile(fileInput.files[0]);
    });

    btnProcesar?.addEventListener('click', async () => {
        if (!parsedItems.length) return;
        setModalState('loading');

        const nombre    = document.getElementById('bom-nombre')?.value?.trim() || '';
        const storeUrl  = window.BOM_STORE_URL || '/legal/comercio-exterior/bom';
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content || '';

        try {
            const res  = await fetch(storeUrl, {
                method : 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept'      : 'application/json',
                },
                body: JSON.stringify({ nombre, archivo_original: parsedFileName, items: parsedItems }),
            });
            const data = await res.json();

            if (data.success) {
                document.getElementById('bom-progress-bar')?.style.setProperty('width', '100%');
                setTimeout(() => { window.location.href = data.redirect; }, 400);
            } else {
                alert('Error al guardar el BOM: ' + (data.message || 'Intenta de nuevo.'));
                setModalState('ready');
            }
        } catch (err) {
            alert('Error de conexión al servidor.');
            setModalState('ready');
        }
    });

    async function handleFile(file) {
        const ext = file.name.split('.').pop().toLowerCase();
        if (!['xlsx', 'xls', 'csv'].includes(ext)) {
            alert('Selecciona un archivo Excel (.xlsx) o CSV.');
            return;
        }
        if (ext === 'xls') {
            alert('El formato .xls (Excel 97-2003) no está soportado. Guarda el archivo como .xlsx.');
            return;
        }
        try {
            parsedFileName = file.name;
            const items    = await parseExcel(file);
            if (!items.length) {
                alert('No se encontraron datos. Verifica que el archivo tenga filas de datos a partir de la fila 3.');
                return;
            }
            parsedItems = items;
            if (fileNameEl)  fileNameEl.textContent  = file.name;
            if (fileRowsEl)  fileRowsEl.textContent  = `${items.length} filas detectadas`;
            setModalState('ready');
        } catch (err) {
            console.error(err);
            alert('Error al leer el archivo. Asegúrate de que sea un Excel válido (.xlsx).');
        }
    }
}

document.addEventListener('DOMContentLoaded', initBom);
