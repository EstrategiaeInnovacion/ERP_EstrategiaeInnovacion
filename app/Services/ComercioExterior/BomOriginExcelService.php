<?php

namespace App\Services\ComercioExterior;

use App\Models\Legal\ComercioExterior\Bom;
use App\Models\Legal\ComercioExterior\OriginAnalysis;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Genera el reporte Excel de análisis de origen T-MEC.
 *
 * Layout (19 columnas A-S):
 *   A-E   Finished Goods   (rojo  #C00000)
 *   F-N   Raw Material     (gris  #BFBFBF)
 *   O-S   Análisis T-MEC   (azul  #BDD7EE)
 *
 * Filas:
 *   1-3   Logo / encabezado empresa
 *   4     Cabeceras de sección (FG | RM | Análisis)
 *   5     Nombres de columna
 *   6+    Datos del BOM
 *   post  Métricas VCR + Reglas + Dictamen
 */
class BomOriginExcelService
{
    private const COLS      = 19;
    private const DATA_START = 6;

    private int   $dataCount = 0;
    private int   $postRow   = 0;
    private array $rowFlags  = [];

    // ────────────────────────────────────────────────────────────────────────
    // Punto de entrada
    // ────────────────────────────────────────────────────────────────────────

    public function build(
        Bom            $bom,
        OriginAnalysis $analysis,
        array          $calc,
        ?array         $ruleDetails = null,
        mixed          $reglaOrigen = null,
    ): Spreadsheet {
        $bom->loadMissing('items');

        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Análisis de Origen T-MEC');

        $this->writeData($sheet, $bom, $analysis, $calc, $ruleDetails, $reglaOrigen);
        $this->applyStyles($sheet, $analysis, $calc);
        $this->applyMerges($sheet);
        $this->applyColumnWidths($sheet);

        $sheet->freezePane('A6');

        return $spreadsheet;
    }

    // ────────────────────────────────────────────────────────────────────────
    // Escritura de datos
    // ────────────────────────────────────────────────────────────────────────

    private function writeData(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        Bom $bom,
        OriginAnalysis $analysis,
        array $calc,
        ?array $ruleDetails,
        mixed $reglaOrigen,
    ): void {
        // ── Filas 1-3: espacio para logo ────────────────────────────────────
        // (vacías)

        // ── Fila 4: cabeceras de sección ─────────────────────────────────────
        $sheet->setCellValue('A4', 'Finished Goods');
        $sheet->setCellValue('F4', 'Raw Material');
        $sheet->setCellValue('O4', 'Análisis T-MEC');

        // ── Fila 5: nombres de columna ───────────────────────────────────────
        $headers = [
            'A5' => 'Número de Parte',
            'B5' => 'Fracción Arancelaria',
            'C5' => 'Descripción',
            'D5' => 'Precio Final (USD)',
            'E5' => 'Nivel',
            'F5' => 'No. de parte de insumo',
            'G5' => 'Descripción',
            'H5' => 'Cantidad incorporada',
            'I5' => 'Precio Unitario',
            'J5' => 'Unidad de Medida',
            'K5' => 'Costo Total USD',
            'L5' => 'Costo Total Pesos',
            'M5' => 'Fracción Arancelaria',
            'N5' => 'País de Origen',
            'O5' => "P – Presenta cambio\nde Fracción",
            'P5' => "Q – Cumple con los\ndemás requisitos",
            'Q5' => "R – Califica como\noriginario",
            'R5' => "S – Regla\nde Origen",
            'S5' => "V – Criterio\nde Origen",
        ];
        foreach ($headers as $cell => $val) {
            $sheet->setCellValue($cell, $val);
        }

        // ── Filas de datos ───────────────────────────────────────────────────
        $this->dataCount = 0;
        $this->rowFlags  = [];
        $row             = self::DATA_START;

        foreach ($bom->items as $item) {
            $p   = $this->siNo($item->presenta_cambio_fraccion);
            $q   = $this->siNo($item->cumple_demas_requisitos);
            $r   = $this->siNo($item->califica_originario);
            $art = $this->artRef($item->criterio_de_origen);
            $s   = $art ? "Art. {$art}" : ($item->regla_de_origen ?? '');
            $v   = $item->criterio_de_origen ?? '';

            $this->rowFlags[] = ['p' => $p, 'q' => $q, 'r' => $r];

            $costoUsd   = (float) ($item->costo_total_usd ?? ((float)($item->cantidad_incorporada ?? 0) * (float)($item->precio_unitario ?? 0)));
            $costoPesos = (float) ($item->costo_total_pesos ?? 0);

            $data = [
                'A' => $item->numero_de_parte,
                'B' => $item->fraccion_arancelaria_fg,
                'C' => $item->descripcion_fg,
                'D' => round((float)($item->precio_final_usd ?? 0), 4),
                'E' => $item->nivel,
                'F' => $item->no_parte_insumo,
                'G' => $item->descripcion_rm,
                'H' => round((float)($item->cantidad_incorporada ?? 0), 4),
                'I' => round((float)($item->precio_unitario ?? 0), 4),
                'J' => $item->unidad_de_medida,
                'K' => round($costoUsd, 4),
                'L' => round($costoPesos, 4),
                'M' => $item->fraccion_arancelaria_rm,
                'N' => $item->pais_de_origen,
                'O' => $p,
                'P' => $q,
                'Q' => $r,
                'R' => $s,
                'S' => $v,
            ];
            foreach ($data as $col => $val) {
                $sheet->setCellValue($col . $row, $val);
            }

            $row++;
            $this->dataCount++;
        }

        $this->postRow = self::DATA_START + $this->dataCount;
        $pdr           = $this->postRow;

        // ── Sección post-datos ───────────────────────────────────────────────
        $fraction = $calc['fg_fraction'] ?? '';
        $vcr      = $calc['rvc_percentage'] ?? $analysis->rvc_percentage ?? 0;
        $cn       = (float) ($calc['fg_price_usd']      ?? $analysis->fg_price_usd      ?? 0);
        $vmno     = (float) ($calc['non_orig_cost_usd'] ?? $analysis->non_orig_cost_usd ?? 0);
        $partNum  = $bom->items->first()?->numero_de_parte ?? '';
        $cr       = $analysis->copilot_response ?? [];

        // Fila pdr: blank (espacio)

        // Fila pdr+1: etiquetas métricas VCR
        $sheet->setCellValue('F' . ($pdr + 1), 'Costo Neto (CN)');
        $sheet->setCellValue('I' . ($pdr + 1), 'VMNO (No Originario)');
        $sheet->setCellValue('L' . ($pdr + 1), 'VCR = (CN − VMNO) / CN');

        // Fila pdr+2: valores métricas VCR
        $sheet->setCellValue('F' . ($pdr + 2), '$' . number_format($cn,   4) . ' USD');
        $sheet->setCellValue('I' . ($pdr + 2), '$' . number_format($vmno, 4) . ' USD');
        $sheet->setCellValue('L' . ($pdr + 2), $vcr . '%');

        // Fila pdr+3: blank
        // Fila pdr+4: blank

        // Fila pdr+5: título sección de reglas
        $fracDigits = preg_replace('/\D/', '', (string)$fraction);
        $chapFrac   = strlen($fracDigits) >= 4 ? substr($fracDigits, 0, 4) : $fracDigits;
        $sheet->setCellValue('A' . ($pdr + 5), "REGLAS DE ORIGEN APLICABLES — FRACCIÓN ARANCELARIA {$chapFrac}");

        // Fila pdr+6: clasificación arancelaria (sección/capítulo)
        $capDesc = $this->buildCapituloDesc($reglaOrigen);
        if ($capDesc) {
            $sheet->setCellValue('A' . ($pdr + 6), $capDesc);
        }

        // Fila pdr+7: encabezado tabla de reglas
        $sheet->setCellValue('A' . ($pdr + 7), 'Partida(s)');
        $sheet->setCellValue('B' . ($pdr + 7), 'Regla(s) aplicable(s)');

        // Fila pdr+8: datos de la regla
        $sheet->setCellValue('A' . ($pdr + 8), $fraction);
        $ruleText = $this->buildFullRuleText($ruleDetails, $analysis, $cr);
        $sheet->setCellValue('B' . ($pdr + 8), $ruleText);

        // Fila pdr+9: blank

        // ── Análisis detallado (nuevo) ────────────────────────────────────────
        // Fila pdr+10: encabezado análisis CC
        $sheet->setCellValue('A' . ($pdr + 10), 'Análisis de Cambio de Clasificación Arancelaria (CC):');
        $sheet->setCellValue('A' . ($pdr + 11), $cr['analisis_cc'] ?? ($cr['col_p'] ?? '—'));

        // Fila pdr+12: encabezado análisis VCR
        $sheet->setCellValue('A' . ($pdr + 13), 'Análisis de Valor de Contenido Regional (VCR):');
        $sheet->setCellValue('A' . ($pdr + 14), $cr['analisis_vcr'] ?? ($cr['col_q'] ?? '—'));

        // Fila pdr+15: condición de escape Art.4.5
        $sheet->setCellValue('A' . ($pdr + 16), 'Condición de Escape — Artículo 4.5 T-MEC:');
        $sheet->setCellValue('A' . ($pdr + 17), $cr['condicion_escape'] ?? 'No aplica — fracción no listada en Tablas B/C.');

        // Fila pdr+18: base legal
        $sheet->setCellValue('A' . ($pdr + 19), 'Base Legal:');
        $sheet->setCellValue('A' . ($pdr + 20), $cr['base_legal'] ?? 'T-MEC Art. 4.2; Anexo 4-B Apéndice Automotriz (si aplica).');

        // Fila pdr+21: blank
        // Fila pdr+22: título dictamen
        $sheet->setCellValue('A' . ($pdr + 22), 'Resultado del análisis de calificación de origen:');

        // Fila pdr+23: dictamen profesional
        $conclusion = ! empty($cr['dictamen_profesional'])
            ? $cr['dictamen_profesional']
            : $this->buildConclusion($partNum, $fraction, $analysis);
        $sheet->setCellValue('A' . ($pdr + 23), $conclusion);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Estilos
    // ────────────────────────────────────────────────────────────────────────

    private function applyStyles(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        OriginAnalysis $analysis,
        array $calc,
    ): void {
        $lastData = self::DATA_START + $this->dataCount - 1;
        $pdr      = $this->postRow;

        // ── Fila 4: section headers ──────────────────────────────────────────
        $this->styleRange($sheet, 'A4:E4', 'C00000', 'FFFFFF', true, 11, Alignment::HORIZONTAL_CENTER);
        $this->styleRange($sheet, 'F4:N4', 'BFBFBF', '111827', true, 11, Alignment::HORIZONTAL_CENTER);
        $this->styleRange($sheet, 'O4:S4', 'BDD7EE', '1E3A5F', true, 11, Alignment::HORIZONTAL_CENTER);
        $sheet->getRowDimension(4)->setRowHeight(22);

        // ── Fila 5: column headers ───────────────────────────────────────────
        $this->styleRange($sheet, 'A5:E5', '8B0000', 'FFFFFF', true, 9, Alignment::HORIZONTAL_CENTER, true);
        $this->styleRange($sheet, 'F5:N5', 'D9D9D9', '374151', true, 9, Alignment::HORIZONTAL_CENTER, true);
        $this->styleRange($sheet, 'O5:S5', '9DC3E6', '1E3A5F', true, 9, Alignment::HORIZONTAL_CENTER, true);
        $sheet->getRowDimension(5)->setRowHeight(42);

        // ── Filas de datos ───────────────────────────────────────────────────
        if ($this->dataCount > 0) {
            $sheet->getStyle("A6:N{$lastData}")->applyFromArray([
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
                'alignment' => ['vertical' => Alignment::VERTICAL_TOP],
                'font'      => ['size' => 9],
            ]);
            $sheet->getStyle("O6:S{$lastData}")->applyFromArray([
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'font'      => ['size' => 9],
            ]);

            // Filas pares: fondo alterno
            for ($i = 1; $i < $this->dataCount; $i += 2) {
                $r = self::DATA_START + $i;
                $sheet->getStyle("A{$r}:N{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('F9FAFB');
                $sheet->getStyle("O{$r}:S{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('DBEAFE');
            }

            // Colores por fila para P/Q/R y fuente análisis
            foreach ($this->rowFlags as $idx => $flags) {
                $r = self::DATA_START + $idx;
                foreach (['O' => 'p', 'P' => 'q', 'Q' => 'r'] as $col => $key) {
                    [$bg, $fg] = $this->siNoColor($flags[$key] ?? '');
                    if ($bg) {
                        $sheet->getStyle("{$col}{$r}")->applyFromArray([
                            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                            'font' => ['bold' => true, 'color' => ['rgb' => $fg], 'size' => 9],
                        ]);
                    }
                }
                $sheet->getStyle("R{$r}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '1E40AF'], 'size' => 9],
                ]);
                $sheet->getStyle("S{$r}")->applyFromArray([
                    'font' => ['bold' => true, 'color' => ['rgb' => '6B21A8'], 'size' => 9],
                ]);
                $sheet->getRowDimension($r)->setRowHeight(18);
            }

            // Alineaciones numéricas
            $sheet->getStyle("B6:B{$lastData}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("D6:D{$lastData}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("H6:L{$lastData}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
            $sheet->getStyle("M6:M{$lastData}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle("N6:N{$lastData}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

            // Borde general tabla
            $sheet->getStyle("A4:S{$lastData}")->applyFromArray([
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']],
                ],
            ]);
        }

        // ── Sección post-datos ───────────────────────────────────────────────
        $qualifies  = $analysis->qualifies;
        $vcrPct     = (float) ($analysis->rvc_percentage ?? 0);
        $threshold  = (float) ($analysis->rvc_threshold  ?? 0);
        $vcrColor   = $vcrPct >= $threshold ? '166534' : '991B1B';
        $vcrBgColor = $vcrPct >= $threshold ? 'F0FDF4' : 'FFF1F2';

        // Etiquetas VCR (pdr+1)
        $lblR = $pdr + 1;
        $sheet->getStyle("F{$lblR}:H{$lblR}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '374151']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F3F4F6']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
        ]);
        $sheet->getStyle("I{$lblR}:K{$lblR}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '991B1B']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFF1F2']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FECACA']]],
        ]);
        $sheet->getStyle("L{$lblR}:S{$lblR}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '1E3A5F']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'EFF6FF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'BFDBFE']]],
        ]);
        $sheet->getRowDimension($lblR)->setRowHeight(18);

        // Valores VCR (pdr+2)
        $valR = $pdr + 2;
        $sheet->getStyle("F{$valR}:H{$valR}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '111827']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
        ]);
        $sheet->getStyle("I{$valR}:K{$valR}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 12, 'color' => ['rgb' => '991B1B']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FFFFFF']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FECACA']]],
        ]);
        $sheet->getStyle("L{$valR}:S{$valR}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 16, 'color' => ['rgb' => $vcrColor]],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $vcrBgColor]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            'borders'   => ['outline' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => $vcrColor]]],
        ]);
        $sheet->getRowDimension($valR)->setRowHeight(28);

        // Título sección reglas (pdr+5)
        $roR = $pdr + 5;
        $sheet->getStyle("A{$roR}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 11, 'color' => ['rgb' => '1E3A5F']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'BDD7EE']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($roR)->setRowHeight(22);

        // Clasificación arancelaria (pdr+6)
        $clsR = $pdr + 6;
        $sheet->getStyle("A{$clsR}")->applyFromArray([
            'font'      => ['italic' => true, 'size' => 9, 'color' => ['rgb' => '374151']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F1F5F9']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($clsR)->setRowHeight(16);

        // Encabezado tabla reglas (pdr+7)
        $rhR = $pdr + 7;
        $sheet->getStyle("A{$rhR}:S{$rhR}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => 'FFFFFF']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '1F4E79']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($rhR)->setRowHeight(22);

        // Fila de regla (pdr+8)
        $rdR = $pdr + 8;
        $sheet->getStyle("A{$rdR}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 9, 'color' => ['rgb' => '1E3A5F']],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_TOP],
        ]);
        $sheet->getStyle("B{$rdR}")->applyFromArray([
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8FAFC']],
            'font'      => ['size' => 9],
            'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_TOP],
        ]);
        $sheet->getStyle("A{$rdR}:S{$rdR}")->applyFromArray([
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D1D5DB']]],
        ]);
        $sheet->getRowDimension($rdR)->setRowHeight(100);

        // ── Análisis detallado ────────────────────────────────────────────────
        $detailSections = [
            $pdr + 10 => ['Análisis de Cambio de Clasificación Arancelaria (CC):', '1D4ED8', 'EFF6FF'],
            $pdr + 13 => ['Análisis de Valor de Contenido Regional (VCR):', '065F46', 'F0FDF4'],
            $pdr + 16 => ['Condición de Escape — Artículo 4.5 T-MEC:', '92400E', 'FFFBEB'],
            $pdr + 19 => ['Base Legal:', '1E3A5F', 'F0F9FF'],
        ];
        foreach ($detailSections as $rn => [$label, $color, $bg]) {
            $sheet->getStyle("A{$rn}")->applyFromArray([
                'font'      => ['bold' => true, 'size' => 9, 'color' => ['rgb' => $color]],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bg]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['bottom' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
            ]);
            $sheet->getRowDimension($rn)->setRowHeight(18);
        }

        foreach ([$pdr + 11, $pdr + 14, $pdr + 17, $pdr + 20] as $rn) {
            $sheet->getStyle("A{$rn}")->applyFromArray([
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'FAFAFA']],
                'font'      => ['size' => 9, 'color' => ['rgb' => '374151']],
                'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_TOP],
                'borders'   => ['outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'E5E7EB']]],
            ]);
            $sheet->getRowDimension($rn)->setRowHeight(45);
        }

        // Título dictamen (pdr+22)
        $chR = $pdr + 22;
        $sheet->getStyle("A{$chR}")->applyFromArray([
            'font'      => ['bold' => true, 'size' => 10, 'color' => ['rgb' => '111827']],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($chR)->setRowHeight(18);

        // Texto del dictamen (pdr+23)
        $conR = $pdr + 23;
        $sheet->getStyle("A{$conR}")->applyFromArray([
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $qualifies ? 'F0FDF4' : 'FFF1F2']],
            'font'      => ['size' => 10, 'color' => ['rgb' => $qualifies ? '166534' : '991B1B']],
            'alignment' => ['wrapText' => true, 'vertical' => Alignment::VERTICAL_TOP],
        ]);
        $sheet->getRowDimension($conR)->setRowHeight(80);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Merges
    // ────────────────────────────────────────────────────────────────────────

    private function applyMerges(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): void
    {
        $pdr = $this->postRow;

        // Fila 4: cabeceras de sección
        $sheet->mergeCells('A4:E4');
        $sheet->mergeCells('F4:N4');
        $sheet->mergeCells('O4:S4');

        // Métricas VCR
        foreach ([1, 2] as $offset) {
            $sheet->mergeCells('F' . ($pdr + $offset) . ':H' . ($pdr + $offset));
            $sheet->mergeCells('I' . ($pdr + $offset) . ':K' . ($pdr + $offset));
            $sheet->mergeCells('L' . ($pdr + $offset) . ':S' . ($pdr + $offset));
        }

        // Sección reglas (todas las celdas A:S por fila)
        foreach ([5, 6, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23] as $offset) {
            $sheet->mergeCells('A' . ($pdr + $offset) . ':S' . ($pdr + $offset));
        }

        // Fila de regla: solo B:S (A queda sola = fracción)
        $sheet->mergeCells('B' . ($pdr + 8) . ':S' . ($pdr + 8));
    }

    // ────────────────────────────────────────────────────────────────────────
    // Anchos de columna
    // ────────────────────────────────────────────────────────────────────────

    private function applyColumnWidths(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet): void
    {
        $widths = [
            'A' => 24, 'B' => 16, 'C' => 36, 'D' => 14, 'E' => 8,
            'F' => 24, 'G' => 36, 'H' => 13, 'I' => 13, 'J' => 11,
            'K' => 15, 'L' => 15, 'M' => 16, 'N' => 11,
            'O' => 16, 'P' => 16, 'Q' => 16, 'R' => 22, 'S' => 12,
        ];
        foreach ($widths as $col => $w) {
            $sheet->getColumnDimension($col)->setWidth($w);
        }
    }

    // ────────────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────────────

    private function styleRange(
        \PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet,
        string $range,
        string $bgRgb,
        string $fontRgb,
        bool   $bold,
        int    $size,
        string $hAlign,
        bool   $wrap = false,
    ): void {
        $sheet->getStyle($range)->applyFromArray([
            'font'      => ['bold' => $bold, 'color' => ['rgb' => $fontRgb], 'size' => $size],
            'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $bgRgb]],
            'alignment' => ['horizontal' => $hAlign, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => $wrap],
        ]);
    }

    private function siNo(?string $v): string
    {
        return match ($v) { 'Sí' => 'SI', 'No' => 'NO', 'N/A' => 'N/A', default => '' };
    }

    private function siNoColor(string $v): array
    {
        return match ($v) {
            'SI'  => ['DCFCE7', '166534'],
            'NO'  => ['FEE2E2', '991B1B'],
            'N/A' => ['F3F4F6', '6B7280'],
            default => ['', ''],
        };
    }

    private function artRef(?string $criterio): string
    {
        return match ($criterio) {
            'A'    => '4.1',
            'B', 'C' => '4.2',
            'D'    => '4.5',
            default => '',
        };
    }

    private function buildCapituloDesc(mixed $reglaOrigen): string
    {
        if (! $reglaOrigen) {
            return '';
        }
        $cap  = $reglaOrigen->capitulo ?? '';
        $desc = $reglaOrigen->descripcion ?? '';
        if (! $cap && ! $desc) {
            return '';
        }
        return trim("Capítulo {$cap}   {$desc}");
    }

    private function buildFullRuleText(?array $ruleDetails, OriginAnalysis $analysis, array $cr): string
    {
        $parts = [];

        // Si el AI generó col_s con la regla aplicada, úsala como primer elemento
        if (! empty($cr['col_s'])) {
            $parts[] = $cr['col_s'];
        } elseif (! empty($analysis->applicable_rule)) {
            $parts[] = $analysis->applicable_rule;
        }

        // Datos estructurados del Apéndice si aplican
        if (! empty($ruleDetails['from_apendice']) && isset($ruleDetails['vcr_umbral_pct'])) {
            $reqCC = $ruleDetails['requiere_cc'] ? 'SÍ' : 'NO';
            $parts[] = "\n— Tipo de vehículo PT: " . ($ruleDetails['tipo_vehiculo_pt'] ?? '—');
            $parts[] = "— Requiere CC: {$reqCC} | Nivel: " . ($ruleDetails['nivel_cc'] ?? 'No aplica');
            $parts[] = "— VCR: ≥ " . $ruleDetails['vcr_umbral_pct'] . "% (" . ($ruleDetails['vcr_metodo'] ?? '—') . ")";
            if (! empty($ruleDetails['cc_excepcion_desde'])) {
                $parts[] = "— Excepción CC desde: " . $ruleDetails['cc_excepcion_desde'];
            }
            if (! empty($ruleDetails['articulo_apendice'])) {
                $parts[] = "— Artículo Apéndice: " . $ruleDetails['articulo_apendice'];
            }
        }

        return implode("\n", array_filter($parts));
    }

    private function buildConclusion(string $partNum, string $fraction, OriginAnalysis $analysis): string
    {
        $qualifies = $analysis->qualifies;
        $criterion = $analysis->origin_criterion ?? 'B';
        $vcr       = $analysis->rvc_percentage   ?? 0;
        $threshold = $analysis->rvc_threshold    ?? 0;

        if ($qualifies) {
            return "El número de parte {$partNum} objeto del presente análisis adquiere el carácter de "
                . "originario del T-MEC, derivado de que cumple con la totalidad de los requisitos determinados "
                . "en las reglas de origen específicas dispuestas para la fracción arancelaria {$fraction}, "
                . "con un VCR calculado de {$vcr}% que supera el umbral mínimo de {$threshold}% bajo el "
                . "método de costo neto, de conformidad con el Criterio {$criterion} del Artículo 4.2 del "
                . "Tratado entre México, Estados Unidos y Canadá (T-MEC).";
        }

        return "El número de parte {$partNum} objeto del presente análisis NO califica como originario del "
            . "T-MEC. VCR calculado: {$vcr}% — umbral requerido: {$threshold}% bajo el método de costo neto. "
            . "Fracción arancelaria {$fraction}, Criterio {$criterion}.";
    }
}
