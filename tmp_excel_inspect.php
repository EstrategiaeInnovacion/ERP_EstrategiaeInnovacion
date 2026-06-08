<?php
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
$rootPath = "C:\\Users\\Sistemas\\Downloads\\Copia_Proyectos_EI\\ERP_EstrategiaeInnovacion";
require $rootPath . "\\vendor\\autoload.php";
$excelPath = $rootPath . "\\storage\\app\\private\\comercio-exterior\\catalogo_reglas_origen.xlsx";
$s = \PhpOffice\PhpSpreadsheet\IOFactory::load($excelPath);
echo "Sheet names: " . implode(", ", $s->getSheetNames()) . PHP_EOL;
$sheet = null;
foreach ($s->getSheetNames() as $name) {
    if (stripos($name, "Automotriz") !== false || stripos($name, "Aut") !== false) {
        $sheet = $s->getSheetByName($name);
        echo "Using sheet: $name" . PHP_EOL;
        break;
    }
}
if (!$sheet) { echo "Not found\n"; exit; }

function cellVal($sheet, $col, $row) {
    $colLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col);
    return $sheet->getCell($colLetter . $row)->getFormattedValue();
}

echo "Total rows: " . $sheet->getHighestRow() . PHP_EOL;
for ($c=1;$c<=10;$c++) { echo cellVal($sheet,$c,1) . " | "; }
echo PHP_EOL;
echo "=== Rows for 8708.94 ===\n";
for ($r=2;$r<=$sheet->getHighestRow();$r++) {
    $fr = cellVal($sheet,1,$r);
    if (strpos($fr,"8708.94")!==false) {
        echo "Row $r: ";
        for ($c=1;$c<=10;$c++) { $v=cellVal($sheet,$c,$r); echo substr($v,0,80)." | "; }
        echo PHP_EOL;
    }
}
// Count automotriz rows per fraction
echo "\n=== Row count per fraction (all) ===\n";
$counts = [];
for ($r=2;$r<=$sheet->getHighestRow();$r++) {
    $fr = cellVal($sheet,1,$r);
    if ($fr !== '') $counts[$fr] = ($counts[$fr] ?? 0) + 1;
}
echo "Total data rows: " . array_sum($counts) . PHP_EOL;
arsort($counts);
foreach (array_slice($counts,0,10,true) as $fr=>$cnt) {
    echo "$fr: $cnt rows\n";
}
