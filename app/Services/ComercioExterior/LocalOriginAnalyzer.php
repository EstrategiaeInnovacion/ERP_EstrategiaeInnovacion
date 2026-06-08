<?php

namespace App\Services\ComercioExterior;

use App\Models\Legal\ComercioExterior\Bom;
use Illuminate\Support\Collection;

class LocalOriginAnalyzer
{
    private const TMEC_COUNTRIES = [
        'MX', 'MEX', 'MEXICO', 'MÉXICO',
        'US', 'USA', 'EEUU', 'EUA', 'ESTADOS UNIDOS', 'UNITED STATES',
        'CA', 'CAN', 'CANADA', 'CANADÁ',
    ];

    public function analyze(Bom $bom, array $calc, ?array $rule, array $overrides = []): array
    {
        $fraction = $calc['fg_fraction'] ?? '—';
        $rvc      = (float) ($calc['rvc_percentage'] ?? 0);

        if ($rule === null) {
            return $this->noRuleResult($fraction, $rvc);
        }

        if ($rule['from_apendice'] && isset($rule['requiere_cc'])) {
            $parsed = $this->paramsFromStructured($rule);
        } else {
            $parsed = $this->parseRule($rule['regla_texto']);
        }

        if (isset($overrides['vcr_threshold'])) $parsed['vcr_threshold'] = $overrides['vcr_threshold'];
        if (isset($overrides['cc_level']))      $parsed['cc_level']      = $overrides['cc_level'];
        if (isset($overrides['logic']))         $parsed['logic']         = $overrides['logic'];

        $nonOrig = $bom->items->filter(
            fn ($i) => ! in_array(strtoupper(trim((string) $i->pais_de_origen)), self::TMEC_COUNTRIES)
        );

        $ccResult = $this->checkCC($fraction, $nonOrig, $parsed['cc_level']);
        $ccOk     = $ccResult['complies'];

        $threshold = $parsed['vcr_threshold'];
        $vcrOk     = $threshold !== null ? ($rvc >= $threshold) : null;
        $vcrDetail = $threshold !== null
            ? "VCR={$rvc}% vs umbral={$threshold}% – " . ($vcrOk ? 'CUMPLE' : 'NO CUMPLE')
            : 'No se requiere VCR';

        $qualifies = match ($parsed['logic']) {
            'or'    => $ccOk || ($vcrOk ?? false),
            default => ($parsed['cc_level'] === 'none' || $ccOk)
                    && ($threshold === null || ($vcrOk ?? false)),
        };

        $escapePorTablaBC = false;
        if (! $qualifies && $ccOk && $threshold !== null && $vcrOk === false) {
            $escapePorTablaBC = app(CatalogoRelacionService::class)->estaEnTablasBC($fraction);
            if ($escapePorTablaBC) {
                $qualifies = true;
                $vcrDetail = "Condición de Escape Tabla B/C: VCR={$rvc}% (umbral {$threshold}% no alcanzado) — califica por CC";
            }
        }

        $tipoVehiculo = $rule['tipo_vehiculo_pt']
            ?? app(CatalogoRelacionService::class)->getTipoVehiculo($fraction);

        $alertasTabla = $this->checkTablasInsumos($bom->items, $tipoVehiculo, $rvc);

        $criterion   = $nonOrig->isEmpty() ? 'C' : 'B';
        $source      = ($rule['from_apendice'] ?? false) ? 'Apéndice Automotriz' : 'Reglas de Origen';
        $ruleSummary = "Hoja: {$source} | Fracción Excel: {$rule['fraccion']} | " . $rule['regla_texto'];

        $colP = ($ccOk ? 'SÍ' : 'NO') . ' – ' . $ccResult['detail'];
        $colQ = ($qualifies ? 'SÍ' : 'NO') . ' – ' . $vcrDetail;
        $colR = $qualifies ? 'SÍ CALIFICA' : 'NO CALIFICA';
        $colS = $ruleSummary;
        $colV = "Criterio {$criterion} – " . $this->criterionDescription($parsed);

        $alertaStr = empty($alertasTabla) ? '' : "\n" . implode("\n", $alertasTabla);

        $raw = implode("\n", [
            '---RESULTADO---',
            "Col P - Cambio de fracción : {$colP}",
            "Col Q - Cumple requisitos  : {$colQ}",
            "Col R - Califica originario: {$colR}",
            "Col S - Regla de origen    : {$colS}",
            "Col V - Criterio de origen : {$colV}",
            '---FIN RESULTADO---',
        ]) . $alertaStr;

        return [
            'cc_complies'      => $ccOk,
            'rvc_threshold'    => $threshold,
            'qualifies'        => $qualifies,
            'origin_criterion' => $criterion,
            'applicable_rule'  => $ruleSummary,
            'col_p'            => $colP,
            'col_q'            => $colQ,
            'col_r'            => $colR,
            'col_s'            => $colS,
            'col_v'            => $colV,
            'raw'              => $raw,
        ];
    }

    private function paramsFromStructured(array $rule): array
    {
        $ccLevel = match (strtolower(trim((string) ($rule['nivel_cc'] ?? '')))) {
            'capítulo', 'capitulo' => 'chapter',
            'partida'              => 'heading',
            'subpartida'           => 'subheading',
            default                => 'none',
        };

        if ($rule['requiere_cc'] === false || $rule['requiere_cc'] === null) {
            $ccLevel = 'none';
        }

        $method = match (strtolower(trim((string) ($rule['vcr_metodo'] ?? '')))) {
            'costo neto', 'cn'                                    => 'net_cost',
            'valor de transacción', 'valor de transaccion', 'vt' => 'transaction',
            default                                               => 'transaction',
        };

        $threshold = $rule['vcr_umbral_pct'] !== null ? (float) $rule['vcr_umbral_pct'] : null;
        $logic     = ($ccLevel !== 'none' && $threshold !== null) ? 'and' : 'and';

        return [
            'cc_level'      => $ccLevel,
            'vcr_threshold' => $threshold,
            'vcr_method'    => $method,
            'logic'         => $logic,
        ];
    }

    private function parseRule(string $ruleText): array
    {
        $noCC    = stripos($ruleText, 'No se requiere cambio de clasificaci') !== false;
        $ccLevel = 'none';

        if (! $noCC) {
            if (stripos($ruleText, 'otro capítulo') !== false || stripos($ruleText, 'otra capítulo') !== false) {
                $ccLevel = 'chapter';
            } elseif (stripos($ruleText, 'otra partida') !== false || stripos($ruleText, 'otro partida') !== false) {
                $ccLevel = 'heading';
            } elseif (stripos($ruleText, 'otra subpartida') !== false || stripos($ruleText, 'otro subpartida') !== false) {
                $ccLevel = 'subheading';
            }
        }

        $threshold = null;
        if (preg_match('/no\s+menor\s+a[^0-9]*(\d+(?:[.,]\d+)?)\s*por\s+ciento/iu', $ruleText, $m)) {
            $threshold = (float) str_replace(',', '.', $m[1]);
        }

        $method = stripos($ruleText, 'costo neto') !== false ? 'net_cost' : 'transaction';

        $logic = 'and';
        if ($ccLevel !== 'none' && $threshold !== null) {
            if (preg_match('/;\s*o\b/iu', $ruleText) && stripos($ruleText, 'valor de contenido regional') !== false) {
                $logic = 'or';
            }
        }

        return [
            'cc_level'      => $ccLevel,
            'vcr_threshold' => $threshold,
            'vcr_method'    => $method,
            'logic'         => $logic,
        ];
    }

    private function checkCC(string $ptFraction, Collection $nonOrigItems, string $ccLevel): array
    {
        if ($ccLevel === 'none') {
            return ['complies' => true, 'detail' => 'No se requiere cambio de clasificación'];
        }

        if ($nonOrigItems->isEmpty()) {
            return ['complies' => true, 'detail' => 'Sin insumos no originarios – CC cumplida'];
        }

        $ptNorm  = $this->stripFraction($ptFraction);
        $failing = [];

        foreach ($nonOrigItems as $item) {
            $inputFraction = trim((string) ($item->fraccion_arancelaria_rm ?? ''));
            if ($inputFraction === '') continue;

            $inputNorm = $this->stripFraction($inputFraction);

            $complies = match ($ccLevel) {
                'chapter'    => substr($ptNorm, 0, 2) !== substr($inputNorm, 0, 2),
                'heading'    => substr($ptNorm, 0, 4) !== substr($inputNorm, 0, 4),
                'subheading' => substr($ptNorm, 0, 6) !== substr($inputNorm, 0, 6),
                default      => true,
            };

            if (! $complies) {
                $failing[] = $inputFraction;
            }
        }

        $levelLabel = match ($ccLevel) {
            'chapter'    => 'capítulo',
            'heading'    => 'partida',
            'subheading' => 'subpartida',
            default      => 'N/A',
        };

        if (empty($failing)) {
            return [
                'complies' => true,
                'detail'   => "Todos los insumos no originarios cambian de {$levelLabel} vs P.T.",
            ];
        }

        $listed = implode(', ', array_slice($failing, 0, 5));
        $more   = count($failing) > 5 ? ' y ' . (count($failing) - 5) . ' más' : '';

        return [
            'complies' => false,
            'detail'   => "Sin cambio de {$levelLabel}: {$listed}{$more}",
        ];
    }

    private function checkTablasInsumos(Collection $allItems, string $tipoVehiculo, float $rvcPT): array
    {
        $servicio = app(CatalogoRelacionService::class);
        $alertas  = [];

        foreach ($allItems as $item) {
            $frac = trim((string) ($item->fraccion_arancelaria_rm ?? ''));
            if ($frac === '') continue;

            $tablaInfo = $servicio->findTablaInsumo($frac, $tipoVehiculo);
            if (! $tablaInfo) continue;

            $umbral = $tablaInfo['vcr_umbral_cn'] ?? null;
            if ($umbral === null) continue;

            if ($rvcPT < $umbral) {
                $alertas[] = "⚠ Insumo {$frac} está en {$tablaInfo['tabla']} "
                           . "– requiere VCR ≥ {$umbral}% CN (VCR actual del P.T.: {$rvcPT}%)";
            }
        }

        return $alertas;
    }

    private function criterionDescription(array $parsed): string
    {
        $level = match ($parsed['cc_level']) {
            'chapter'    => 'CC a nivel capítulo',
            'heading'    => 'CC a nivel partida',
            'subheading' => 'CC a nivel subpartida',
            default      => '',
        };

        $vcr = $parsed['vcr_threshold'] !== null
            ? "VCR ≥ {$parsed['vcr_threshold']}% ({$parsed['vcr_method']})"
            : '';

        $connector = $parsed['logic'] === 'or' ? ' o ' : ' + ';

        return implode($connector, array_filter([$level, $vcr])) ?: 'Regla específica';
    }

    private function noRuleResult(string $fraction, float $rvc): array
    {
        $msg = "Fracción {$fraction} no encontrada en el catálogo de reglas";

        return [
            'cc_complies'      => null,
            'rvc_threshold'    => null,
            'qualifies'        => null,
            'origin_criterion' => null,
            'applicable_rule'  => $msg,
            'col_p'            => 'No determinado – fracción no encontrada en el catálogo',
            'col_q'            => "VCR={$rvc}% – umbral desconocido",
            'col_r'            => 'INDETERMINADO',
            'col_s'            => $msg,
            'col_v'            => 'No determinado',
            'raw'              => $msg,
        ];
    }

    private function stripFraction(string $fraction): string
    {
        return preg_replace('/[^0-9]/', '', trim($fraction));
    }
}
