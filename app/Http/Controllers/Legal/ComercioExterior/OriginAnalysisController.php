<?php

namespace App\Http\Controllers\Legal\ComercioExterior;

use App\Http\Controllers\Controller;
use App\Models\Legal\ComercioExterior\Bom;
use App\Models\Legal\ComercioExterior\BomItem;
use App\Services\ComercioExterior\BomOriginExcelService;
use App\Services\ComercioExterior\CatalogoRelacionService;
use App\Services\ComercioExterior\ExcelRulesService;
use App\Services\ComercioExterior\LocalOriginAnalyzer;
use App\Services\ComercioExterior\OrigenCalculatorService;
use App\Services\ComercioExterior\OriginAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class OriginAnalysisController extends Controller
{
    public function __construct(
        private OriginAnalysisService   $service,
        private ExcelRulesService       $rulesService,
        private CatalogoRelacionService $catalogoService,
        private OrigenCalculatorService $calculator,
        private LocalOriginAnalyzer     $analyzer,
    ) {}

    public function show(Bom $bom)
    {
        abort_unless($bom->created_by === (int) auth()->user()?->id, 403);

        $bom->load('items');
        $calc        = $this->service->calculate($bom);
        $analysis    = $bom->originAnalyses()->latest('analyzed_at')->first();
        $ruleDetails = $this->rulesService->findRule($calc['fg_fraction'] ?? '');
        $reglaOrigen = null;

        if ($calc['fg_fraction']) {
            $resolved    = $this->catalogoService->resolveFinishedGood($calc['fg_fraction']);
            $reglaOrigen = $resolved['regla_origen'] ?? null;
        }

        return view('Legal.comercio-exterior.origen.show', compact('bom', 'calc', 'analysis', 'ruleDetails', 'reglaOrigen'));
    }

    public function store(Bom $bom)
    {
        abort_unless($bom->created_by === (int) auth()->user()?->id, 403);

        $bom->load('items');

        try {
            $analysis = $this->service->analyze($bom, (int) auth()->user()?->id);
            $this->calculator->analizarBom($bom->id);
        } catch (\RuntimeException $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        return redirect()->back()
            ->with('success', 'Análisis completado. Vigente hasta: ' . $analysis->valid_until->format('d/m/Y'));
    }

    public function export(Bom $bom): StreamedResponse
    {
        abort_unless($bom->created_by === (int) auth()->user()?->id, 403);

        $bom->load('items');
        $analysis    = $bom->originAnalyses()->latest('analyzed_at')->firstOrFail();
        $calc        = $this->service->calculate($bom);
        $ruleDetails = $this->rulesService->findRule($calc['fg_fraction'] ?? '');
        $reglaOrigen = null;

        if ($calc['fg_fraction']) {
            $resolved    = $this->catalogoService->resolveFinishedGood($calc['fg_fraction']);
            $reglaOrigen = $resolved['regla_origen'] ?? null;
        }

        $spreadsheet = app(BomOriginExcelService::class)->build(
            $bom,
            $analysis,
            $calc,
            $ruleDetails,
            $reglaOrigen,
        );

        $writer   = new Xlsx($spreadsheet);
        $filename = 'BOM_Origen_' . ($bom->clave ?: $bom->id) . '.xlsx';

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function chat(Request $request, Bom $bom)
    {
        abort_unless($bom->created_by === (int) auth()->user()?->id, 403);

        $message = trim($request->input('message', ''));
        $history = $request->input('history', []);

        if ($message === '') {
            return response()->json(['error' => 'Mensaje vacío.'], 422);
        }

        $bom->load('items');
        $calc             = $this->service->calculate($bom);
        $originalFraction = $calc['fg_fraction'] ?? '—';
        $analysis         = $bom->originAnalyses()->latest('analyzed_at')->first();
        $ruleDetails      = $this->rulesService->findRule($originalFraction);

        $cr = $analysis?->copilot_response ?? [];

        $contextLines = [
            "=== BOM: " . ($bom->clave ?? $bom->id) . " ===",
            "Nombre: " . ($bom->nombre ?? '—'),
            "Total insumos: " . $bom->items->count(),
            "",
            "--- MÉTRICAS ---",
            "Fracción PT: {$originalFraction}",
            "Costo Neto: \${$calc['fg_price_usd']} USD",
            "VMNO: \${$calc['non_orig_cost_usd']} USD",
            "VCR: {$calc['rvc_percentage']}%",
        ];

        if ($analysis) {
            $contextLines = array_merge($contextLines, [
                "",
                "--- DICTAMEN ---",
                "Resultado: " . ($analysis->qualifies ? '✅ SÍ CALIFICA' : '❌ NO CALIFICA'),
                "Criterio: " . ($analysis->origin_criterion ?? '—'),
                "VCR: " . $analysis->rvc_percentage . "% vs umbral " . $analysis->rvc_threshold . "%",
                "CC cumple: " . ($analysis->cc_complies ? 'SÍ' : 'NO'),
                "Regla: " . ($analysis->applicable_rule ?? '—'),
            ]);
            if (! empty($cr['col_p'])) $contextLines[] = "Col P: " . $cr['col_p'];
            if (! empty($cr['col_q'])) $contextLines[] = "Col Q: " . $cr['col_q'];
            if (! empty($cr['col_r'])) $contextLines[] = "Col R: " . $cr['col_r'];
            if (! empty($cr['col_v'])) $contextLines[] = "Col V: " . $cr['col_v'];
        }

        if (! empty($ruleDetails['regla_texto'])) {
            $contextLines[] = "";
            $contextLines[] = "--- REGLA DE ORIGEN ---";
            $contextLines[] = $ruleDetails['regla_texto'];
        }

        $tmecPaises = ['MX', 'US', 'CA', 'MEX', 'USA', 'CAN', 'MEXICO', 'ESTADOS UNIDOS', 'CANADA', 'CANADÁ'];
        $contextLines[] = "";
        $contextLines[] = "--- INSUMOS BOM ---";
        foreach ($bom->items as $idx => $item) {
            $isOrig = in_array(strtoupper(trim((string) $item->pais_de_origen)), $tmecPaises);
            $tag    = $isOrig ? '[ORIG T-MEC]' : '[NO ORIG]';
            $contextLines[] = "Insumo " . ($idx + 1) . " {$tag}: Fracc RM={$item->fraccion_arancelaria_rm} | País={$item->pais_de_origen} | Costo=\${$item->costo_total_usd} | Califica={$item->califica_originario}";
        }

        $itemIds = $bom->items->mapWithKeys(fn ($i) => [$i->no_parte_insumo ?? "item-{$i->id}" => $i->id])
                              ->map(fn ($id, $parte) => "{$parte}→{$id}")->implode(', ');

        $systemPrompt = implode("\n", $contextLines) . "\n\n"
            . "Eres un experto en reglas de origen T-MEC/USMCA (Arts. 4.2–4.5). "
            . "Responde en español, sé preciso y breve. "
            . "Si hay correcciones concretas, incluye al final:\n"
            . "===CORRECCIONES===\n"
            . "{\"analisis\":{\"qualifies\":true,\"origin_criterion\":\"B\"},\"items\":[{\"id\":1,\"califica_originario\":\"Sí\"}]}\n"
            . "===FIN CORRECCIONES===\n"
            . "IDs de insumos: [{$itemIds}].";

        $groqKey   = config('services.groq.key', env('GROQ_API_KEY'));
        $groqModel = config('services.groq.model', env('GROQ_MODEL', 'llama-3.3-70b-versatile'));

        if (! $groqKey) {
            return response()->json(['error' => 'GROQ_API_KEY no configurada en el entorno.'], 500);
        }

        $messages = [['role' => 'system', 'content' => $systemPrompt]];
        foreach (array_slice($history, -6) as $h) {
            if (! empty($h['user']))      $messages[] = ['role' => 'user',      'content' => $h['user']];
            if (! empty($h['assistant'])) $messages[] = ['role' => 'assistant', 'content' => strip_tags($h['assistant'])];
        }
        $messages[] = ['role' => 'user', 'content' => $message];

        try {
            $response = Http::timeout(30)
                ->withToken($groqKey)
                ->post('https://api.groq.com/openai/v1/chat/completions', [
                    'model'       => $groqModel,
                    'messages'    => $messages,
                    'temperature' => 0.3,
                    'max_tokens'  => 900,
                ]);

            if (! $response->successful()) {
                Log::error('CE Chat Groq error', ['status' => $response->status()]);

                return response()->json(['error' => 'Error al contactar la IA. Intenta de nuevo.'], 502);
            }

            $aiText = $response->json('choices.0.message.content') ?? 'Sin respuesta.';

        } catch (\Exception $e) {
            Log::error('CE Chat exception', ['error' => $e->getMessage()]);

            return response()->json(['error' => 'Error de conexión con la IA.'], 502);
        }

        $corrections = null;
        if (preg_match('/===CORRECCIONES===\s*([\s\S]+?)\s*===FIN CORRECCIONES===/u', $aiText, $cm)) {
            $decoded = json_decode(trim($cm[1]), true);
            if (json_last_error() === JSON_ERROR_NONE) {
                $corrections = $decoded;
            }
            $aiText = trim(preg_replace('/\n*===CORRECCIONES===[\s\S]*?===FIN CORRECCIONES===/u', '', $aiText));
        }

        $analysis_result = null;
        $overrides       = [];
        $searchFraction  = $originalFraction;

        if (preg_match('/\b(\d{4}(?:\.\d{2}){1,3})\b/', $message, $m)) {
            $searchFraction = $m[1];
        }
        if (preg_match('/\b(\d{2,3}(?:[.,]\d+)?)\s*(?:%|por\s+ciento)\b/iu', $message, $m)) {
            $overrides['vcr_threshold'] = (float) str_replace(',', '.', $m[1]);
        }
        if (preg_match('/no\s+(?:se\s+)?requiere\s+cambio/iu', $message)) {
            $overrides['cc_level'] = 'none';
        } elseif (preg_match('/cambio.{1,20}cap[íi]tulo/iu', $message)) {
            $overrides['cc_level'] = 'chapter';
        } elseif (preg_match('/cambio.{1,20}subpartida/iu', $message)) {
            $overrides['cc_level'] = 'subheading';
        } elseif (preg_match('/cambio.{1,20}partida/iu', $message)) {
            $overrides['cc_level'] = 'heading';
        }

        if ($searchFraction !== $originalFraction || ! empty($overrides)) {
            $rule = $this->rulesService->findRule($searchFraction);
            if ($rule) {
                $analysis_result = $this->analyzer->analyze($bom, $calc, $rule, $overrides);
            }
        }

        return response()->json([
            'assistant'     => $aiText,
            'analysis'      => $analysis_result,
            'fraction_used' => $searchFraction,
            'corrections'   => $corrections,
        ]);
    }

    public function applyCorrections(Request $request, Bom $bom)
    {
        abort_unless($bom->created_by === (int) auth()->user()?->id, 403);

        $validated = $request->validate([
            'analisis'                         => 'nullable|array',
            'analisis.qualifies'               => 'nullable|boolean',
            'analisis.origin_criterion'        => 'nullable|string|max:5',
            'analisis.applicable_rule'         => 'nullable|string|max:2000',
            'analisis.rvc_threshold'           => 'nullable|numeric|min:0|max:100',
            'analisis.cc_complies'             => 'nullable|boolean',
            'items'                            => 'nullable|array',
            'items.*.id'                       => 'required|integer',
            'items.*.presenta_cambio_fraccion' => 'nullable|in:Sí,No,N/A',
            'items.*.cumple_demas_requisitos'  => 'nullable|in:Sí,No',
            'items.*.califica_originario'      => 'nullable|in:Sí,No',
            'items.*.criterio_de_origen'       => 'nullable|string|max:5',
            'items.*.regla_de_origen'          => 'nullable|string|max:2000',
        ]);

        if (! empty($validated['analisis'])) {
            $analysis = $bom->originAnalyses()->latest('analyzed_at')->first();
            if ($analysis) {
                $allowed = ['qualifies', 'origin_criterion', 'applicable_rule', 'rvc_threshold', 'cc_complies'];
                $updates = array_intersect_key($validated['analisis'], array_flip($allowed));
                if ($updates) {
                    $analysis->fill($updates)->save();
                }
            }
        }

        if (! empty($validated['items'])) {
            $editableFields = ['presenta_cambio_fraccion', 'cumple_demas_requisitos', 'califica_originario', 'criterio_de_origen', 'regla_de_origen'];
            foreach ($validated['items'] as $itemData) {
                $item = BomItem::where('bom_id', $bom->id)->find($itemData['id']);
                if (! $item) continue;
                $updates = array_intersect_key($itemData, array_flip($editableFields));
                if ($updates) {
                    $item->fill($updates)->save();
                }
            }
        }

        return response()->json(['success' => true]);
    }
}
