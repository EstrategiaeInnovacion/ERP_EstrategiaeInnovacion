<?php

namespace App\Services\ComercioExterior;

use App\Models\Legal\ComercioExterior\Bom;
use App\Models\Legal\ComercioExterior\OriginAnalysis;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OriginAnalysisService
{
    private const TMEC_COUNTRIES = [
        'MX', 'MEX', 'MEXICO', 'MÉXICO',
        'US', 'USA', 'EEUU', 'EUA', 'ESTADOS UNIDOS', 'UNITED STATES',
        'CA', 'CAN', 'CANADA', 'CANADÁ',
    ];

    public function calculate(Bom $bom): array
    {
        $items = $bom->items;

        $firstItem  = $items->first();
        $fgPrice    = (float) ($firstItem?->precio_final_usd ?? 0);

        $nonOrigCost = $items
            ->filter(fn ($i) => ! in_array(strtoupper(trim((string) $i->pais_de_origen)), self::TMEC_COUNTRIES))
            ->sum(fn ($i) => (float) $i->cantidad_incorporada * (float) $i->precio_unitario);

        $rvc = $fgPrice > 0
            ? round(($fgPrice - $nonOrigCost) / $fgPrice * 100, 2)
            : 0;

        return [
            'fg_price_usd'      => $fgPrice,
            'non_orig_cost_usd' => round($nonOrigCost, 6),
            'rvc_percentage'    => $rvc,
            'part_number'       => $firstItem?->numero_de_parte,
            'fg_fraction'       => $firstItem ? $this->normalizeFraction((string) $firstItem->fraccion_arancelaria_fg) : null,
            'description'       => $firstItem?->descripcion_fg,
        ];
    }

    public function analyze(Bom $bom, int $analystId): OriginAnalysis
    {
        $bom->loadMissing('items');
        $calc = $this->calculate($bom);
        $rule = app(ExcelRulesService::class)->findRule($calc['fg_fraction'] ?? '');

        $modo   = $this->getModoAnalisis();
        $parsed = $modo === 'ia'
            ? app(AiOriginAnalyzerService::class)->analyze($bom, $calc, $rule)
            : app(LocalOriginAnalyzer::class)->analyze($bom, $calc, $rule);

        Log::info('Análisis de origen T-MEC para BOM ' . $bom->id, [
            'modo'       => $modo,
            'fraccion'   => $calc['fg_fraction'],
            'qualifies'  => $parsed['qualifies'],
            'criterion'  => $parsed['origin_criterion'],
            'rvc'        => $calc['rvc_percentage'],
            'rule_found' => $rule !== null,
        ]);

        return $this->saveAnalysis($bom, $parsed, $analystId);
    }

    public function getModoAnalisis(): string
    {
        $row = DB::table('parametros_sistema_ce')->where('clave', 'modo_analisis')->first();

        return ($row?->valor_texto === 'ia') ? 'ia' : 'local';
    }

    public function saveAnalysis(Bom $bom, array $parsedData, int $analystId): OriginAnalysis
    {
        $calc = $this->calculate($bom);

        return OriginAnalysis::create([
            'bom_id'            => $bom->id,
            'part_number'       => $calc['part_number'],
            'fg_fraction'       => $calc['fg_fraction'],
            'fg_price_usd'      => $calc['fg_price_usd'],
            'non_orig_cost_usd' => $calc['non_orig_cost_usd'],
            'rvc_percentage'    => $calc['rvc_percentage'],
            'rvc_threshold'     => $parsedData['rvc_threshold']   ?? null,
            'cc_complies'       => $parsedData['cc_complies']     ?? null,
            'origin_criterion'  => $parsedData['origin_criterion'] ?? null,
            'qualifies'         => $parsedData['qualifies']        ?? null,
            'applicable_rule'   => $parsedData['applicable_rule']  ?? null,
            'copilot_response'  => $parsedData,
            'analyst_id'        => $analystId,
            'valid_until'       => Carbon::now()->addYear()->toDateString(),
        ]);
    }

    public function buildPrompt(Bom $bom): string
    {
        $calc     = $this->calculate($bom);
        $fraction = $calc['fg_fraction'] ?? '—';
        $isAuto   = $this->isAutomotive($fraction);

        $items     = $bom->items;
        $itemLines = $items->map(function ($item) {
            $pais       = strtoupper(trim((string) $item->pais_de_origen));
            $isOrig     = in_array($pais, self::TMEC_COUNTRIES) ? 'ORIGINARIO T-MEC' : 'NO ORIGINARIO';
            $costoTotal = round((float) $item->cantidad_incorporada * (float) $item->precio_unitario, 4);

            return sprintf(
                '  - Fracción: %s | Descripción: %s | Costo Total: $%.4f USD | País: %s [%s]',
                $item->fraccion_arancelaria_rm ?? 'SIN FRACCIÓN',
                $item->descripcion_rm ?? '—',
                $costoTotal,
                $item->pais_de_origen ?? 'SIN PAÍS',
                $isOrig
            );
        })->implode("\n");

        $apendiceNote = $isAuto
            ? "\n⚠ FRACCIÓN AUTOMOTRIZ: Busca primero en la hoja 'Apéndice Automotriz'."
            : '';

        $nombreBom   = $bom->nombre ?: $bom->clave;
        $partNumber  = $calc['part_number'] ?? '—';
        $description = $calc['description'] ?? '—';
        $fgPrice     = $calc['fg_price_usd'];
        $nonOrig     = $calc['non_orig_cost_usd'];
        $rvc         = $calc['rvc_percentage'];

        return <<<PROMPT
Eres un especialista en reglas de origen T-MEC/CUSMA (USMCA).

━━━━━━━━━━━━━━━━━━━━━━━━
DATOS DEL PRODUCTO TERMINADO
━━━━━━━━━━━━━━━━━━━━━━━━
BOM             : {$nombreBom}
Número de parte : {$partNumber}
Fracción P.T.   : {$fraction}
Descripción     : {$description}
Precio final    : \${$fgPrice} USD

━━━━━━━━━━━━━━━━━━━━━━━━
INSUMOS (BOM)
━━━━━━━━━━━━━━━━━━━━━━━━
{$itemLines}

━━━━━━━━━━━━━━━━━━━━━━━━
DATOS PRE-CALCULADOS
━━━━━━━━━━━━━━━━━━━━━━━━
Costo insumos NO originarios : \${$nonOrig} USD
VCR                          : {$rvc}%{$apendiceNote}

Realiza el análisis y emite el dictamen final con este formato EXACTO:

---RESULTADO---
Col P - Cambio de fracción : [SÍ/NO] – [explicación]
Col Q - Cumple requisitos  : [SÍ/NO] – VCR={$rvc}% vs umbral=[X]%
Col R - Califica originario: [SÍ CALIFICA / NO CALIFICA]
Col S - Regla de origen    : [Hoja usada] – [texto resumido de la regla]
Col V - Criterio de origen : Criterio [A/B/C/D] – [fundamento]
---FIN RESULTADO---
PROMPT;
    }

    private function normalizeFraction(string $raw): string
    {
        if (empty($raw)) return $raw;

        $digits = preg_replace('/[^0-9]/', '', $raw);
        if ($digits === '') return trim($raw);

        return match (strlen($digits)) {
            2       => $digits,
            4       => substr($digits, 0, 2) . '.' . substr($digits, 2),
            6       => substr($digits, 0, 4) . '.' . substr($digits, 4),
            8       => substr($digits, 0, 4) . '.' . substr($digits, 4, 2),
            default => strlen($digits) > 4
                        ? substr($digits, 0, 4) . '.' . substr($digits, 4)
                        : $digits,
        };
    }

    private function isAutomotive(string $fraction): bool
    {
        return str_starts_with(str_replace('.', '', $fraction), '87');
    }
}
