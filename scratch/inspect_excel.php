<?php
require 'vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

$file = 'Asistencia - 27 abril al 30 abril.xlsx';
try {
    $spreadsheet = IOFactory::load($file);
    $sheet = $spreadsheet->getActiveSheet();
    $highestRow = min(50, $sheet->getHighestDataRow());
    $highestCol = Coordinate::columnIndexFromString($sheet->getHighestDataColumn());

    echo "Sheet: " . $sheet->getTitle() . "\n";
    for ($row = 1; $row <= $highestRow; $row++) {
        $rowVals = [];
        for ($col = 1; $col <= $highestCol; $col++) {
            $val = $sheet->getCell(Coordinate::stringFromColumnIndex($col) . $row)->getValue();
            if ($val !== null && $val !== '') {
                $rowVals[] = trim((string)$val);
            }
        }
        if (!empty($rowVals)) {
            echo "Row $row: " . implode(' | ', $rowVals) . "\n";
        }
    }
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage();
}
