<?php

namespace App\Services\ComercioExterior;

use App\Models\Legal\ComercioExterior\Bom;
use App\Models\Legal\ComercioExterior\OriginAnalysis;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class UsmcaCertificateService
{
    private const TEMPLATE_PATH = 'docs/Analisis_Origen/USMCA 2026 Form clear.xlsx';

    public function fill(Bom $bom, OriginAnalysis $analysis, array $coverData = []): Spreadsheet
    {
        $path = base_path(self::TEMPLATE_PATH);
        abort_unless(file_exists($path), 500, 'Plantilla USMCA no encontrada: ' . self::TEMPLATE_PATH);

        $bom->loadMissing('items');

        $spreadsheet = IOFactory::load($path);

        $this->fillCoverPage($spreadsheet->getSheetByName('Cover Page'), $coverData);

        $qualifying      = $this->buildQualifyingParts($bom->items, $analysis);
        $supplierNumbers = $coverData['supplier_part_number'] ?? [];
        $this->fillContinuationPage($spreadsheet->getSheetByName('Continuation Page'), $qualifying, $supplierNumbers);

        return $spreadsheet;
    }

    public function getQualifyingParts(Bom $bom, OriginAnalysis $analysis): array
    {
        $bom->loadMissing('items');
        return $this->buildQualifyingParts($bom->items, $analysis);
    }

    // ── Cover Page ───────────────────────────────────────────────────────────

    private function fillCoverPage(?Worksheet $ws, array $coverData = []): void
    {
        if (! $ws) return;

        // ── 1. Blanket Period ────────────────────────────────────────────────
        $this->writeCell($ws, 'I3', $this->toUsDate($coverData['blanket_from'] ?? ''), 'MM/DD/YYYY');
        $this->writeCell($ws, 'I4', $this->toUsDate($coverData['blanket_to']   ?? ''), 'MM/DD/YYYY');

        // ── 2. Certifier Type (X checkbox) ──────────────────────────────────
        $certType = $coverData['certifier_type'] ?? 'EXPORTER';
        $ws->getCell('C4')->setValue($certType === 'IMPORTER' ? 'X' : '');
        $ws->getCell('E4')->setValue($certType === 'EXPORTER' ? 'X' : '');
        $ws->getCell('G4')->setValue($certType === 'PRODUCER' ? 'X' : '');

        // ── 3. Certifier (Section 2) ─────────────────────────────────────────
        $certifierMap = [
            'B6'  => ['certifier_name',    'Certifier Name'],
            'B7'  => ['certifier_address', 'Address'],
            'B9'  => ['certifier_country', 'Country'],
            'D9'  => ['certifier_phone',   'Phone'],   // C9 es label "PHONE"
            'B10' => ['certifier_email',   'Email'],   // anchor de B10:D10
        ];
        foreach ($certifierMap as $coord => [$key, $placeholder]) {
            $this->writeCell($ws, $coord, $coverData[$key] ?? '', $placeholder);
        }

        // ── 4. Exporter (Section 3) ───────────────────────────────────────────
        $exporterMap = [
            'F6'  => ['exporter_name',    'Exporter Name'],
            'F7'  => ['exporter_address', 'Address'],
            'F9'  => ['exporter_country', 'Country'],
            'H9'  => ['exporter_phone',   'Phone'],   // anchor de H9:I9, G9 es label "PHONE"
            'F10' => ['exporter_email',   'Email'],   // anchor de F10:I10
        ];
        foreach ($exporterMap as $coord => [$key, $placeholder]) {
            $this->writeCell($ws, $coord, $coverData[$key] ?? '', $placeholder);
        }

        // ── 5. Producer (Section 4) ──────────────────────────────────────────
        $producerMap = [
            'B13' => ['producer_name',    'Producer Name'],  // anchor B13:D13
            'B14' => ['producer_address', 'Address'],        // anchor B14:D14
            'B16' => ['producer_country', 'Country'],
            'D16' => ['producer_phone',   'Phone'],          // C16 es label "PHONE"
            'B17' => ['producer_email',   'Email'],          // anchor B17:D17
            'C18' => ['producer_tax_id',  'Tax ID'],         // anchor C18:D18
        ];
        foreach ($producerMap as $coord => [$key, $placeholder]) {
            $this->writeCell($ws, $coord, $coverData[$key] ?? '', $placeholder);
        }

        // ── 6. Importer (Section 5) ──────────────────────────────────────────
        // F16 ya tiene "MEXICO" — no tocar el país
        $importerMap = [
            'F13' => ['importer_name',    'Importer Name'],  // anchor F13:I13
            'F14' => ['importer_address', 'Address'],        // anchor F14:H14
            'H16' => ['importer_phone',   'Phone'],          // anchor H16:I16; G16 es label "PHONE"
            'F17' => ['importer_email',   'Email'],          // anchor F17:I17
            'G18' => ['importer_tax_id',  'Tax ID'],         // anchor G18:I18
        ];
        foreach ($importerMap as $coord => [$key, $placeholder]) {
            $this->writeCell($ws, $coord, $coverData[$key] ?? '', $placeholder);
        }

        // ── 7. Section 12 – Certifier signature block ────────────────────────
        // Fila 52 contiene los labels; fila 53 contiene los valores (celdas merge)
        // B52:C52 = label "12e. DATE", B53:C53 = valor fecha
        // D52     = label "12f. TELEPHONE", D53:E53 = valor teléfono
        // F52     = label "12g. EMAIL",     F53:I53 = valor email
        $this->writeCell($ws, 'G48', $coverData['cert_company'] ?? '', 'Company');
        $this->writeCell($ws, 'C50', $coverData['cert_name']    ?? '', 'Name');
        $this->writeCell($ws, 'G50', $coverData['cert_title']   ?? '', 'Title');
        $this->writeCell($ws, 'B53', $this->toUsDate($coverData['cert_date'] ?? ''), 'MM/DD/YYYY');
        $this->writeCell($ws, 'D53', $coverData['cert_phone']   ?? '', 'Phone');
        $this->writeCell($ws, 'F53', $coverData['cert_email']   ?? '', 'Email');
    }

    private function writeCell(Worksheet $ws, string $coord, string $value, string $placeholder): void
    {
        $cell  = $ws->getCell($coord);
        $style = $cell->getStyle();

        if ($value !== '') {
            $cell->setValue($value);
            $style->applyFromArray([
                'font' => ['name' => 'Arial', 'size' => 9, 'italic' => false, 'color' => ['argb' => 'FF000000']],
            ]);
        } else {
            $cell->setValue('[' . $placeholder . ']');
            $style->applyFromArray([
                'font' => ['name' => 'Arial', 'size' => 9, 'italic' => true, 'color' => ['argb' => 'FF0070C0']],
            ]);
        }
    }

    // ── Continuation Page ────────────────────────────────────────────────────

    private function fillContinuationPage(?Worksheet $ws, array $parts, array $supplierNumbers = []): void
    {
        if (! $ws || empty($parts)) return;

        $total     = count($parts);
        $startRow  = 10;

        // Abrir espacio para las nuevas filas
        $ws->insertNewRowBefore($startRow, $total);

        foreach ($parts as $i => $part) {
            $row    = $startRow + $i;
            $partNo = $part['part_number'];
            $part['supplier_part_number'] = $supplierNumbers[$partNo] ?? '';
            $this->writeDataRow($ws, $row, $part);
        }

        // Actualizar Total Parts Solicited (celda adyacente a la derecha)
        foreach ($ws->getRowIterator() as $rowObj) {
            foreach ($rowObj->getCellIterator() as $cell) {
                if (is_string($cell->getValue()) && str_contains($cell->getValue(), 'Total Parts Solicited')) {
                    $colIdx   = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($cell->getColumn());
                    $nextCoord = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colIdx + 1) . $cell->getRow();
                    $ws->getCell($nextCoord)->setValue($total);
                    break 2;
                }
            }
        }
    }

    private function writeDataRow(Worksheet $ws, int $row, array $part): void
    {
        $thin = [
            'borderStyle' => Border::BORDER_THIN,
            'color'       => ['argb' => 'FF000000'],
        ];
        $borderAll = [
            'left'   => $thin,
            'right'  => $thin,
            'top'    => $thin,
            'bottom' => $thin,
        ];

        $colMap = [
            'A' => $part['part_number'],
            'B' => $part['supplier_part_number'] ?? null,
            'C' => $part['description'],
            'D' => $part['hts'],
            'E' => $part['origin_criterion'],
            'F' => $part['producer'],
            'G' => $part['method'],
            'H' => 'MX',
        ];

        foreach ($colMap as $col => $value) {
            $cell = $ws->getCell($col . $row);
            $cell->setValue($value);

            $cell->getStyle()->applyFromArray([
                'font' => [
                    'name' => 'Arial',
                    'size' => 9,
                    'bold' => false,
                ],
                'alignment' => [
                    'horizontal' => $col === 'C' ? Alignment::HORIZONTAL_LEFT : Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                    'wrapText'   => false,
                ],
                'borders' => $borderAll,
                'fill' => [
                    'fillType' => Fill::FILL_NONE,
                ],
            ]);
        }

        $ws->getRowDimension($row)->setRowHeight(15);
    }

    // ── Helpers ──────────────────────────────────────────────────────────────

    private function toUsDate(string $value): string
    {
        if ($value === '') return '';
        // Browser sends YYYY-MM-DD; convert to MM/DD/YYYY for the certificate
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
            return $m[2] . '/' . $m[3] . '/' . $m[1];
        }
        return $value; // already in another format — pass through
    }

    private function buildQualifyingParts(Collection $items, OriginAnalysis $analysis): array
    {
        $cr = $analysis->copilot_response ?? [];

        // Fallback de col_p/col_q desde OriginAnalysis si los items no tienen los valores
        $fallbackColP = isset($cr['col_p']) ? strtoupper(trim($cr['col_p'])) : ($analysis->cc_complies ? 'SI' : 'NO');
        $fallbackColQ = isset($cr['col_q'])
            ? strtoupper(trim($cr['col_q']))
            : (($analysis->rvc_percentage >= $analysis->rvc_threshold) ? 'SI' : 'NO');

        $parts = [];

        foreach ($items->groupBy('numero_de_parte') as $partNumber => $group) {
            $first = $group->first();

            $califica = strtolower(trim((string) $first->califica_originario));
            if (! in_array($califica, ['sí', 'si', 'sí'])) continue;

            $colP = $first->presenta_cambio_fraccion
                ? (strtoupper(trim($first->presenta_cambio_fraccion)) === 'SÍ' ? 'SI' : strtoupper(trim($first->presenta_cambio_fraccion)))
                : $fallbackColP;

            $colQ = $first->cumple_demas_requisitos
                ? (strtoupper(trim($first->cumple_demas_requisitos)) === 'SÍ' ? 'SI' : strtoupper(trim($first->cumple_demas_requisitos)))
                : $fallbackColQ;

            $criterion = $first->criterio_de_origen ?: ($analysis->origin_criterion ?? 'B');

            $parts[] = [
                'part_number'     => (string) $partNumber,
                'description'     => (string) ($first->descripcion_fg ?? ''),
                'hts'             => $this->normalizeHts($first->fraccion_arancelaria_fg),
                'origin_criterion'=> (string) $criterion,
                'producer'        => $colQ === 'SI' ? 'YES' : 'NO',
                'method'          => $this->methodOfQualification($colP, $colQ),
            ];
        }

        return $parts;
    }

    private function normalizeHts(mixed $value): string
    {
        if ($value === null || $value === '') return '';

        $str = (string) $value;

        // Ya tiene punto → respetar si tiene formato XXXX.XX
        if (str_contains($str, '.')) return $str;

        $digits = preg_replace('/\D/', '', $str);

        return match (strlen($digits)) {
            6 => substr($digits, 0, 4) . '.' . substr($digits, 4),         // 870894  → 8708.94
            8 => substr($digits, 0, 4) . '.' . substr($digits, 4, 2),      // 72285091 → 7228.50
            4 => substr($digits, 0, 2) . '.' . substr($digits, 2),
            default => $digits,
        };
    }

    private function methodOfQualification(string $colP, string $colQ): string
    {
        // TS = Tariff Shift (prioridad T-MEC); NC = Net Cost/VCR
        if ($colP === 'SI') return 'TS';
        if ($colQ === 'SI') return 'NC';
        return '';
    }
}
