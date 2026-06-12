<?php

namespace App\Http\Controllers\Legal\ComercioExterior;

use App\Http\Controllers\Controller;
use App\Models\Legal\ComercioExterior\Bom;
use App\Models\Legal\ComercioExterior\BomItem;
use App\Services\ComercioExterior\CatalogoRelacionService;
use App\Services\ComercioExterior\OrigenCalculatorService;
use App\Services\ComercioExterior\OriginAnalysisService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class BomController extends Controller
{
    public function __construct(
        private OrigenCalculatorService $calculator,
        private OriginAnalysisService   $originService,
        private CatalogoRelacionService $catalogoRelacionService,
    ) {}

    public function index()
    {
        $boms = Bom::where('created_by', (int) auth()->user()?->id)
            ->with(['originAnalyses' => fn($q) => $q->latest('analyzed_at')->limit(1)])
            ->latest()
            ->paginate(20);

        return view('Legal.comercio-exterior.bom.index', compact('boms'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'items'            => 'nullable|array',
            'nombre'           => 'nullable|string|max:255',
            'archivo_original' => 'nullable|string|max:255',
        ]);

        $clave = 'BOM-' . date('Ymd') . '-' . strtoupper(Str::random(6));

        $bom = Bom::create([
            'clave'            => $clave,
            'nombre'           => $request->nombre,
            'archivo_original' => $request->archivo_original,
            'created_by'       => (int) auth()->user()?->id,
        ]);

        $rows = [];
        foreach ($request->input('items', []) as $item) {
            $rows[] = array_merge(
                ['bom_id' => $bom->id, 'created_at' => now(), 'updated_at' => now()],
                $this->sanitizeItem($item)
            );
        }

        BomItem::insert($rows);

        return response()->json([
            'success'  => true,
            'clave'    => $bom->clave,
            'bom_id'   => $bom->id,
            'redirect' => route('legal.ce.bom.show', $bom->id),
        ]);
    }

    public function show(Bom $bom)
    {
        abort_unless($bom->created_by === (int) auth()->user()?->id, 403);

        $bom->load('items.reglaOrigen');
        $items        = $bom->items;
        $calc         = $this->originService->calculate($bom);
        $analysis     = $bom->originAnalyses()->latest('analyzed_at')->first();
        $itemContexts = $items->mapWithKeys(fn ($item) => [
            $item->id => $this->catalogoRelacionService->resolveBomItem($item),
        ]);

        $modo = $this->originService->getModoAnalisis();

        return view('Legal.comercio-exterior.bom.show', compact('bom', 'items', 'calc', 'analysis', 'itemContexts', 'modo'));
    }

    public function updateItems(Request $request, Bom $bom)
    {
        abort_unless($bom->created_by === (int) auth()->user()?->id, 403);

        $request->validate([
            'nombre' => 'nullable|string|max:255',
            'items'  => 'required|array',
        ]);

        if ($request->has('nombre')) {
            $bom->update(['nombre' => $request->nombre]);
        }

        foreach ($request->items as $itemData) {
            if (empty($itemData['id'])) continue;
            $item = $bom->items()->find((int) $itemData['id']);
            if ($item) {
                $item->update($this->sanitizeItem($itemData));
            }
        }

        return response()->json(['success' => true]);
    }

    public function destroy(Bom $bom)
    {
        abort_unless($bom->created_by === (int) auth()->user()?->id, 403);

        $bom->delete();

        return redirect()->route('legal.ce.bom.index')->with('success', 'BOM eliminado correctamente.');
    }

    public function analizarBom(Bom $bom): JsonResponse
    {
        abort_unless($bom->created_by === (int) auth()->user()?->id, 403);

        try {
            $resultado = $this->calculator->analizarBom($bom->id);

            return response()->json([
                'success' => true,
                'summary' => $resultado['summary'],
            ]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    public function analizarItem(Bom $bom, BomItem $item): JsonResponse
    {
        abort_unless($bom->created_by === (int) auth()->user()?->id, 403);

        if ($item->bom_id !== $bom->id) {
            return response()->json(['success' => false, 'error' => 'Item no pertenece al BOM'], 403);
        }

        try {
            $resultado = $this->calculator->analizarItem($item);

            return response()->json(['success' => true, 'resultado' => $resultado]);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    private function sanitizeItem(array $item): array
    {
        $numeric = ['precio_final_usd', 'cantidad_incorporada', 'precio_unitario', 'costo_total_usd', 'costo_total_pesos'];

        foreach ($numeric as $field) {
            if (isset($item[$field])) {
                $clean        = str_replace(',', '', $item[$field]);
                $item[$field] = is_numeric($clean) ? (float) $clean : null;
            }
        }

        $allowed = [
            'numero_de_parte', 'fraccion_arancelaria_fg', 'descripcion_fg',
            'precio_final_usd', 'nivel', 'no_parte_insumo', 'descripcion_rm',
            'cantidad_incorporada', 'precio_unitario', 'unidad_de_medida',
            'costo_total_usd', 'costo_total_pesos', 'fraccion_arancelaria_rm',
            'pais_de_origen', 'nombre_proveedor', 'presenta_cambio_fraccion',
            'cumple_demas_requisitos', 'califica_originario',
            'regla_de_origen', 'criterio_de_origen',
        ];

        return array_intersect_key($item, array_flip($allowed));
    }
}
