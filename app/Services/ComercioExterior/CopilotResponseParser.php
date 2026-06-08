<?php

namespace App\Services\ComercioExterior;

class CopilotResponseParser
{
    public function parse(string $copilotText): array
    {
        preg_match('/---RESULTADO---(.*?)---FIN RESULTADO---/s', $copilotText, $match);
        $block = $match[1] ?? $copilotText;

        return [
            'cc_complies'      => $this->extractCcComplies($block),
            'rvc_threshold'    => $this->extractThreshold($block),
            'qualifies'        => $this->extractQualifies($block),
            'origin_criterion' => $this->extractCriterion($block),
            'applicable_rule'  => $this->extractRule($block),
            'col_p'            => $this->extractLine($block, 'Col P'),
            'col_q'            => $this->extractLine($block, 'Col Q'),
            'col_r'            => $this->extractLine($block, 'Col R'),
            'col_s'            => $this->extractLine($block, 'Col S'),
            'col_v'            => $this->extractLine($block, 'Col V'),
            'raw'              => $copilotText,
        ];
    }

    private function extractCcComplies(string $text): bool
    {
        preg_match('/Col\s+P\s*[-–]\s*[^:]*:\s*(SÍ|SI|NO)/iu', $text, $m);

        return isset($m[1]) && strtoupper(trim($m[1])) !== 'NO';
    }

    private function extractThreshold(string $text): ?float
    {
        preg_match('/umbral\s*=\s*(\d+(?:[.,]\d+)?)\s*%/i', $text, $m);
        if (isset($m[1])) {
            return (float) str_replace(',', '.', $m[1]);
        }

        return null;
    }

    private function extractQualifies(string $text): bool
    {
        preg_match('/Col\s+R\s*[-–][^:]*:\s*(SÍ CALIFICA|SI CALIFICA|NO CALIFICA)/iu', $text, $m);

        return isset($m[1]) && stripos($m[1], 'NO') === false;
    }

    private function extractCriterion(string $text): ?string
    {
        preg_match('/Criterio\s+([ABCD])/i', $text, $m);

        return $m[1] ?? null;
    }

    private function extractRule(string $text): ?string
    {
        preg_match('/Col\s+S\s*[-–][^:]*:\s*(.+?)(?=\nCol\s+[PQRSV]|---FIN|$)/is', $text, $m);

        return isset($m[1]) ? trim($m[1]) : null;
    }

    private function extractLine(string $text, string $col): ?string
    {
        $pattern = '/' . preg_quote($col, '/') . '\s*[-–][^:]*:\s*(.+?)(?=\n' . preg_quote($col[0], '/') . 'ol\s+[PQRSV]|---FIN|$)/is';
        preg_match($pattern, $text, $m);

        return isset($m[1]) ? trim($m[1]) : null;
    }
}
