<?php

require 'vendor/autoload.php';

use Carbon\Carbon;

function parsePeriodo($joined)
{
    if (preg_match('/(\d{4})[\/\-](\d{1,2})[\/\-](\d{1,2})\s*[~-]\s*(?:(\d{4})[\/\-])?(\d{1,2})[\/\-](\d{1,2})/', $joined, $m)) {
        try {
            $year = (int)$m[1];
            $start = Carbon::create($year, (int)$m[2], (int)$m[3]);
            $endYear = !empty($m[4]) ? (int)$m[4] : $year;
            $endMonth = (int)$m[5];
            $endDay = (int)$m[6];
            try {
                $end = Carbon::create($endYear, $endMonth, $endDay);
            }
            catch (\Exception $ex) {
                $end = $start->copy()->endOfMonth();
            }
            return ['inicio' => $start, 'fin' => $end];
        }
        catch (\Throwable $e) {
            return "Error: " . $e->getMessage();
        }
    }
    return null;
}

$testCases = [
    "2024/04/01 ~ 2024/04/30",
    "2024-04-01 ~ 2024-04-30",
    "2024/04/01 ~ 04/30",
    "01/04/2024 ~ 30/04/2024", // Should fail
    "2024/04/31 ~ 2024/05/01", // Invalid date
];

foreach ($testCases as $case) {
    $res = parsePeriodo($case);
    echo "Case: $case\n";
    if (is_array($res)) {
        echo "  Start: " . $res['inicio']->toDateString() . "\n";
        echo "  End: " . $res['fin']->toDateString() . "\n";
    } else {
        echo "  Result: " . ($res ?? "NULL") . "\n";
    }
}
