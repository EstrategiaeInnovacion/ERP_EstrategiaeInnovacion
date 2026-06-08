<?php

namespace App\Services\ComercioExterior;

use App\Models\Legal\ComercioExterior\Bom;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiOriginAnalyzerService
{
    private const TMEC_COUNTRIES = [
        'MX', 'US', 'CA',
        'MEX', 'USA', 'CAN',
        'MEXICO', 'ESTADOS UNIDOS', 'CANADA', 'CANADÁ',
    ];

    public function __construct(
        private CatalogoRelacionService $catalogoService,
    ) {}

    public function analyze(Bom $bom, array $calc, ?array $rule): array
    {
        $fraction = $calc['fg_fraction'] ?? '—';
        $rvc      = (float) ($calc['rvc_percentage'] ?? 0);

        if ($rule === null) {
            return $this->noRuleResult($fraction, $rvc);
        }

        $tablaBC       = $this->catalogoService->estaEnTablasBC($fraction);
        $tablaBCDetail = null;
        if ($tablaBC) {
            $parts         = $this->catalogoService->findApendicePartes($fraction);
            $tablaBCDetail = $parts->map(fn ($p) => $p->tabla_codigo . ': ' . $p->tabla)->implode('; ');
        }

        $ccPreAnalysis    = $this->preComputeCcAnalysis(
            $fraction,
            $bom->items,
            (bool) ($rule['requiere_cc'] ?? false),
            $rule['nivel_cc'] ?? null
        );
        $rmCatalogContext = $this->buildRmCatalogContext($bom->items);

        $prompt = $this->buildPrompt($bom, $calc, $rule, $tablaBC, $tablaBCDetail, $ccPreAnalysis, $rmCatalogContext);

        $groqKey   = config('services.groq.key', env('GROQ_API_KEY'));
        $groqModel = config('services.groq.model', env('GROQ_MODEL', 'llama-3.3-70b-versatile'));

        if (! $groqKey) {
            Log::error('AiOriginAnalyzer: GROQ_API_KEY no configurada');

            return $this->noRuleResult($fraction, $rvc);
        }

        try {
            $response = Http::timeout(60)
                ->withToken($groqKey)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model'           => $groqModel,
                    'messages'        => [
                        ['role' => 'system', 'content' => $this->systemPrompt()],
                        ['role' => 'user',   'content' => $prompt],
                    ],
                    'temperature'     => 0.1,
                    'max_tokens'      => 3000,
                    'response_format' => ['type' => 'json_object'],
                ]);

            if (! $response->successful()) {
                Log::error('AiOriginAnalyzer: error de API', [
                    'status' => $response->status(),
                    'body'   => $response->body(),
                ]);

                return $this->noRuleResult($fraction, $rvc);
            }

            $content = $response->json('choices.0.message.content');
            $parsed  = json_decode($content, true);

            if (! $parsed || ! isset($parsed['qualifies'])) {
                Log::error('AiOriginAnalyzer: JSON inválido', ['content' => $content]);

                return $this->noRuleResult($fraction, $rvc);
            }

            Log::info('AiOriginAnalyzer: análisis completado', [
                'bom'       => $bom->id,
                'fraccion'  => $fraction,
                'qualifies' => $parsed['qualifies'],
                'criterion' => $parsed['origin_criterion'] ?? '—',
            ]);

            return $this->normalizeResult($parsed, $rule);

        } catch (\Exception $e) {
            Log::error('AiOriginAnalyzer: excepción', ['error' => $e->getMessage()]);

            return $this->noRuleResult($fraction, $rvc);
        }
    }

    private function systemPrompt(): string
    {
        return <<<'SYSTEM'
Eres un especialista en comercio exterior con dominio experto en las Reglas de Origen del Tratado México–Estados Unidos–Canadá (T-MEC/USMCA), en especial los Artículos 4.1–4.7 y el Apéndice al Anexo 4-B (Reglas Automotrices).

PRINCIPIOS FUNDAMENTALES:
1. Analiza EXCLUSIVAMENTE con los datos proporcionados. NUNCA inventes umbrales de VCR, niveles de CC ni requisitos que no estén en los datos.
2. Si existen "DATOS ESTRUCTURADOS DEL APÉNDICE (AUTORIDAD DEFINITIVA)", son la fuente oficial directa de la base de datos. Úsalos con prioridad absoluta sobre cualquier conocimiento general.
3. El análisis CC pre-calculado (Sección III) es orientativo; verifica la lógica, pero basa el dictamen en los datos estructurados cuando estén disponibles.
4. Cita el artículo T-MEC específico que sustenta cada conclusión.

PROCESO OBLIGATORIO DE ANÁLISIS (6 PASOS, EJECUTAR TODOS):
Paso 1 – IDENTIFICACIÓN: Fuente de la regla (Apéndice Automotriz o Sección B), fracción aplicable, tipo de vehículo si es automotriz, todos los requisitos: ¿exige CC?, ¿a qué nivel (Capítulo/Partida/Subpartida)?, ¿exige VCR?, ¿método y umbral exacto?
Paso 2 – ANÁLISIS CC: Para cada insumo NO originario (país fuera de MX/US/CA): compara su fracción arancelaria con la del PT al nivel exigido. Evalúa excepciones si la regla las contempla (p.ej. "o CC desde X"). Concluye si el requisito CC se cumple globalmente.
Paso 3 – ANÁLISIS VCR: Confirma la fórmula aplicada: VCR = (CN – VMNO) / CN × 100. Usa el VCR pre-calculado proporcionado. Compara contra el umbral EXACTO de los datos estructurados del catálogo. Indica si CUMPLE o NO CUMPLE.
Paso 4 – CONDICIÓN DE ESCAPE (Art. 4.5): Si VCR no cumple pero CC sí cumple, y el PT aparece en Tablas B/C, el PT califica de todas formas. Indica si esta condición aplica o no.
Paso 5 – DICTAMEN: Determina si la mercancía califica como originaria. Criterio A = enteramente obtenida en territorio T-MEC; Criterio B = satisface requisito de cambio de fracción arancelaria y/o VCR; Criterio C = producida enteramente con materiales originarios T-MEC.
Paso 6 – BASE LEGAL: Cita los artículos T-MEC específicos: Art. 4.2 (criterios de origen), Art. 4.3 (cambio de clasificación arancelaria), Art. 4.5 (valor de contenido regional), Apéndice 4-B y artículo específico si aplica.

FORMATO DE RESPUESTA — devuelve ÚNICAMENTE JSON válido con TODOS estos campos:
{
  "qualifies": true|false,
  "origin_criterion": "A"|"B"|"C",
  "cc_complies": true|false|null,
  "rvc_threshold": número_exacto_del_catálogo|null,
  "analisis_cc": "Detalle insumo por insumo: fracción RM vs fracción PT al nivel exigido y conclusión",
  "analisis_vcr": "VCR=X% vs umbral=Y% (método: Z). Resultado: CUMPLE o NO CUMPLE",
  "condicion_escape": "Aplica o No aplica — fundamento Art. 4.5",
  "base_legal": "T-MEC Art. 4.X; Apéndice 4-B Art. Y (si aplica)",
  "col_p": "SÍ o NO — descripción del resultado CC con fracciones comparadas",
  "col_q": "SÍ o NO — VCR=X% vs umbral Y%. Método: Z",
  "col_r": "SÍ CALIFICA" o "NO CALIFICA",
  "col_s": "Fuente: [hoja] | Fracción: [fracción] | [resumen de regla aplicada]",
  "col_v": "Criterio X — [fundamento legal específico con artículo T-MEC]",
  "dictamen_profesional": "Párrafo formal en español de 3-4 oraciones con dictamen técnico completo citando artículos T-MEC",
  "justificacion": "2-3 oraciones concisas que explican el resultado final"
}
SYSTEM;
    }

    private function buildPrompt(Bom $bom, array $calc, array $rule, bool $tablaBC, ?string $tablaBCDetail, string $ccPreAnalysis, string $rmCatalogContext): string
    {
        $fraction  = $calc['fg_fraction'] ?? '—';
        $rvc       = $calc['rvc_percentage'] ?? 0;
        $cn        = $calc['fg_price_usd'] ?? 0;
        $vmno      = $calc['non_orig_cost_usd'] ?? 0;
        $fromApend = $rule['from_apendice'] ?? false;
        $source    = $fromApend ? 'Apéndice Automotriz' : 'Sección B – Reglas de Origen';
        $ruleText  = $rule['regla_texto'] ?? '—';
        $ruleFrac  = $rule['fraccion'] ?? '—';

        $fgDigits    = preg_replace('/[^0-9]/', '', (string) $fraction);
        $fgChapter   = substr($fgDigits . str_repeat('0', 2), 0, 2);
        $fgHeading   = substr($fgDigits . str_repeat('0', 4), 0, 4);
        $fgSubhead   = substr($fgDigits . str_repeat('0', 6), 0, 6);
        $fgBreakdown = "Cap. {$fgChapter} | Partida {$fgHeading} | Subpartida {$fgSubhead}";

        $tmecPaises   = self::TMEC_COUNTRIES;
        $items        = $bom->items;
        $nonOrigCount = $items->filter(fn ($i) => ! in_array(strtoupper(trim((string) $i->pais_de_origen)), $tmecPaises))->count();
        $origCount    = $items->count() - $nonOrigCount;

        $itemsLines = $items->map(function ($item, $i) use ($tmecPaises, $cn) {
            $isOrig = in_array(strtoupper(trim((string) $item->pais_de_origen)), $tmecPaises);
            $tag    = $isOrig ? 'ORIG   ' : 'NO-ORIG';
            $costo  = (float) ($item->costo_total_usd ?? 0);
            $pct    = $cn > 0 ? round($costo / (float) $cn * 100, 1) : 0;

            return sprintf(
                '  [%d] %-16s | %-20s | $%-8.4f (%5.1f%%) | %s | %s',
                $i + 1,
                $item->fraccion_arancelaria_rm ?? '—',
                $item->pais_de_origen ?? '—',
                $costo,
                $pct,
                $tag,
                $item->descripcion_rm ?? '—'
            );
        })->implode("\n");

        $tablaBCLine = $tablaBC
            ? "⚠ SÍ está en Tablas B/C: {$tablaBCDetail}. Condición de escape disponible (Art. 4.5)."
            : 'No está en Tablas B/C. Art. 4.5 no aplica.';

        $structuredBlock = '';
        if ($fromApend && isset($rule['requiere_cc'])) {
            $reqCC     = $rule['requiere_cc'] ? 'SÍ' : 'NO';
            $nivelCC   = $rule['nivel_cc'] ?? 'No aplica';
            $excepcion = $rule['cc_excepcion_desde'] ?? 'Ninguna';
            $vcrMetodo = $rule['vcr_metodo'] ?? '—';
            $vcrUmbral = isset($rule['vcr_umbral_pct']) ? $rule['vcr_umbral_pct'] . '%' : '—';
            $tipoVeh   = $rule['tipo_vehiculo_pt'] ?? '—';
            $tablaRef  = $rule['tabla_partes_ref'] ?? '—';
            $artRef    = $rule['articulo_apendice'] ?? '—';

            $structuredBlock = <<<STRUCT

=== DATOS ESTRUCTURADOS DEL APÉNDICE (AUTORIDAD DEFINITIVA) ===
Tipo de vehículo PT : {$tipoVeh}
Requiere CC         : {$reqCC}
Nivel de CC         : {$nivelCC}
Excepción CC desde  : {$excepcion}
Método VCR          : {$vcrMetodo}
Umbral VCR          : {$vcrUmbral}  ← USAR ESTE UMBRAL EXACTO
Tabla de partes ref : {$tablaRef}
Artículo apéndice   : {$artRef}
STRUCT;
        }

        return <<<PROMPT
=== I. PRODUCTO TERMINADO (PT) ===
BOM              : {$bom->clave}
No. de parte     : {$calc['part_number']}
Descripción      : {$calc['description']}
Fracción T-MEC   : {$fraction}  ({$fgBreakdown})
Precio final (CN): \${$cn} USD
VMNO             : \${$vmno} USD
VCR pre-calculado: {$rvc}%  [= (CN – VMNO) / CN × 100]
Insumos          : {$origCount} originarios T-MEC | {$nonOrigCount} NO originarios

=== II. REGLA DE ORIGEN APLICABLE (catálogo) ==={$structuredBlock}
Fuente   : {$source}
Fracción : {$ruleFrac}
Texto    : {$ruleText}

=== III. ANÁLISIS CC PRE-CALCULADO ===
{$ccPreAnalysis}

=== IV. CONTEXTO DE INSUMOS NO ORIG EN CATÁLOGO T-MEC ===
{$rmCatalogContext}

=== V. TABLAS B/C — CONDICIÓN DE ESCAPE (Art. 4.5) ===
{$tablaBCLine}

=== VI. DETALLE COMPLETO DE INSUMOS BOM ===
  #  Fracción RM      País                  Costo USD       (%CN)  Tipo    Descripción
{$itemsLines}

Ejecuta los 6 pasos y responde con JSON válido completo.
PROMPT;
    }

    private function preComputeCcAnalysis(string $fgFraction, $items, bool $requiereCC, ?string $nivelCC): string
    {
        if (! $requiereCC) {
            return 'La regla NO exige Cambio de Clasificación Arancelaria — criterio CC no aplica.';
        }

        $fgDigits = preg_replace('/[^0-9]/', '', (string) $fgFraction);
        $nivel    = strtolower(trim((string) ($nivelCC ?? '')));

        $getLevelKey = function (string $digits) use ($nivel): string {
            return match ($nivel) {
                'capítulo', 'capitulo' => substr($digits . str_repeat('0', 2), 0, 2),
                'partida'              => substr($digits . str_repeat('0', 4), 0, 4),
                'subpartida'           => substr($digits . str_repeat('0', 6), 0, 6),
                default                => '',
            };
        };

        $fgKey = $getLevelKey($fgDigits);
        $tmec  = self::TMEC_COUNTRIES;

        if ($fgKey === '') {
            return "Nivel de CC '{$nivelCC}' no reconocido — verificar manualmente en la regla.";
        }

        $lines      = [];
        $allMeet    = true;
        $anyNonOrig = false;

        foreach ($items as $i => $item) {
            $isOrig = in_array(strtoupper(trim((string) $item->pais_de_origen)), $tmec);
            if ($isOrig) {
                continue;
            }

            $anyNonOrig = true;
            $rmFrac     = (string) ($item->fraccion_arancelaria_rm ?? '');
            $rmDigits   = preg_replace('/[^0-9]/', '', $rmFrac);
            $rmKey      = $getLevelKey($rmDigits);

            if ($rmKey === '') {
                $lines[]  = sprintf('  [RM-%d] %-14s (%-12s) : INDETERMINADO — fracción sin dígitos válidos', $i + 1, $rmFrac, $item->pais_de_origen);
                $allMeet  = false;
                continue;
            }

            $ccMet = ($rmKey !== $fgKey);
            if (! $ccMet) {
                $allMeet = false;
            }

            $lines[] = sprintf(
                '  [RM-%d] %-14s (%-12s) RM-nivel=%s | PT-nivel=%s : %s',
                $i + 1,
                $rmFrac,
                $item->pais_de_origen ?? '—',
                $rmKey,
                $fgKey,
                $ccMet ? '✓ CAMBIA de ' . ucfirst($nivel) : '✗ NO CAMBIA de ' . ucfirst($nivel)
            );
        }

        if (! $anyNonOrig) {
            return 'No hay insumos NO originarios — todos los insumos son T-MEC (CC automáticamente cumplida).';
        }

        $conclusion = $allMeet
            ? '→ CONCLUSIÓN CC: CUMPLE — todos los insumos NO orig cambian de ' . ucfirst($nivel)
            : '→ CONCLUSIÓN CC: NO CUMPLE por regla directa (verificar excepciones del texto de la regla)';

        return implode("\n", $lines) . "\n  " . $conclusion;
    }

    private function buildRmCatalogContext($items): string
    {
        $tmec  = self::TMEC_COUNTRIES;
        $lines = [];

        foreach ($items as $i => $item) {
            $isOrig = in_array(strtoupper(trim((string) $item->pais_de_origen)), $tmec);
            if ($isOrig) {
                continue;
            }

            $rmFrac = trim((string) ($item->fraccion_arancelaria_rm ?? ''));
            if ($rmFrac === '' || $rmFrac === '—') {
                continue;
            }

            $resolved = $this->catalogoService->resolveFinishedGood($rmFrac);

            if ($resolved && $resolved['regla_origen']) {
                $desc    = $resolved['regla_origen']->descripcion ?? '—';
                $cap     = $resolved['regla_origen']->capitulo ?? '—';
                $lines[] = sprintf('  [RM-%d] %-14s → Cap.%-3s | %s', $i + 1, $rmFrac, $cap, $desc);
            } else {
                $lines[] = sprintf('  [RM-%d] %-14s → No encontrado en catálogo T-MEC (verificar manualmente)', $i + 1, $rmFrac);
            }
        }

        return empty($lines)
            ? '  Todos los insumos son originarios T-MEC — no hay insumos NO orig para consultar.'
            : implode("\n", $lines);
    }

    private function normalizeResult(array $ai, array $rule): array
    {
        $fromApend   = $rule['from_apendice'] ?? false;
        $source      = $fromApend ? 'Apéndice Automotriz' : 'Reglas de Origen';
        $ruleSummary = "Hoja: {$source} | Fracción Excel: {$rule['fraccion']} | " . $rule['regla_texto'];
        $colS        = ! empty($ai['col_s']) ? $ai['col_s'] : $ruleSummary;

        $raw = implode("\n", [
            '---RESULTADO (IA Groq/Llama)---',
            'Col P - Cambio de fracción    : ' . ($ai['col_p'] ?? '—'),
            'Col Q - Cumple requisitos     : ' . ($ai['col_q'] ?? '—'),
            'Col R - Califica originario   : ' . ($ai['col_r'] ?? '—'),
            "Col S - Regla de origen       : {$colS}",
            'Col V - Criterio de origen    : ' . ($ai['col_v'] ?? '—'),
            '--- Análisis CC ---',
            $ai['analisis_cc'] ?? '—',
            '--- Análisis VCR ---',
            $ai['analisis_vcr'] ?? '—',
            '--- Condición Escape Art.4.5 ---',
            $ai['condicion_escape'] ?? '—',
            '--- Base Legal ---',
            $ai['base_legal'] ?? '—',
            '--- Dictamen Profesional ---',
            $ai['dictamen_profesional'] ?? '—',
            '--- Justificación ---',
            $ai['justificacion'] ?? '—',
            '---FIN RESULTADO---',
        ]);

        return [
            'cc_complies'          => $ai['cc_complies'] ?? null,
            'rvc_threshold'        => isset($ai['rvc_threshold']) ? (float) $ai['rvc_threshold'] : null,
            'qualifies'            => (bool) ($ai['qualifies'] ?? false),
            'origin_criterion'     => $ai['origin_criterion'] ?? null,
            'applicable_rule'      => $ruleSummary,
            'col_p'                => $ai['col_p'] ?? '—',
            'col_q'                => $ai['col_q'] ?? '—',
            'col_r'                => $ai['col_r'] ?? '—',
            'col_s'                => $colS,
            'col_v'                => $ai['col_v'] ?? '—',
            'analisis_cc'          => $ai['analisis_cc'] ?? null,
            'analisis_vcr'         => $ai['analisis_vcr'] ?? null,
            'condicion_escape'     => $ai['condicion_escape'] ?? null,
            'base_legal'           => $ai['base_legal'] ?? null,
            'dictamen_profesional' => $ai['dictamen_profesional'] ?? null,
            'justificacion'        => $ai['justificacion'] ?? null,
            'raw'                  => $raw,
            'ai_powered'           => true,
        ];
    }

    private function noRuleResult(string $fraction, float $rvc): array
    {
        $msg = "Fracción {$fraction} no encontrada en el catálogo de reglas T-MEC";

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
}
