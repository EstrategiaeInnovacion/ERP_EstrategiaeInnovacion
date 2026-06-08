<?php

namespace App\Http\Controllers\Legal\ComercioExterior;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CatalogoController extends Controller
{
    public function index(Request $request)
    {
        $tab = $request->get('tab', 'seccion_b');

        // ── Sección B ────────────────────────────────────────────────────────
        $queryB = DB::table('reglas_origen');

        if ($search = $request->get('search')) {
            $queryB->where(function ($q) use ($search) {
                $q->where('fraccion_arancelaria', 'like', "%{$search}%")
                  ->orWhere('descripcion',        'like', "%{$search}%")
                  ->orWhere('regla_texto',         'like', "%{$search}%");
            });
        }

        if ($capitulo = $request->get('capitulo')) {
            $queryB->where('capitulo', (int) $capitulo);
        }

        if ($criterio = $request->get('criterio')) {
            $queryB->where('criterio', $criterio);
        }

        if ($request->get('solo_apendice')) {
            $queryB->where('requiere_apendice', 1);
        }

        $reglasOrigen = $queryB->orderBy('fraccion_arancelaria')
                               ->paginate(50, ['*'], 'page_b')
                               ->withQueryString();

        $capitulos = DB::table('reglas_origen')->select('capitulo')->distinct()->orderBy('capitulo')->pluck('capitulo');
        $criterios = DB::table('reglas_origen')->select('criterio')->whereNotNull('criterio')->distinct()->orderBy('criterio')->pluck('criterio');

        $totalReglas   = DB::table('reglas_origen')->count();
        $totalApendice = DB::table('reglas_origen')->where('requiere_apendice', 1)->count();
        $totalConVcr   = DB::table('reglas_origen')->whereNotNull('vcr_porcentaje')->count();

        // ── Apéndice A.1 ─────────────────────────────────────────────────────
        $tablaA1 = DB::table('apendice_tabla_a1')
            ->select(
                'fraccion_arancelaria',
                DB::raw('descripcion_parte as descripcion'),
                'categoria',
                DB::raw('porcentaje_min as vcr_umbral_cn_pct')
            )
            ->orderBy('fraccion_arancelaria')
            ->paginate(50, ['*'], 'page_a1')
            ->withQueryString();

        // ── Apéndice A.2 ─────────────────────────────────────────────────────
        $tablaA2 = DB::table('apendice_tabla_a2')
            ->orderBy('tipo_material')
            ->paginate(50, ['*'], 'page_a2')
            ->withQueryString();

        // ── Apéndice Tablas de Partes (B/C/D/E/F) ───────────────────────────
        $tablasBcd = DB::table('apendice_partes_catalogo')
            ->whereNotIn('tabla_codigo', ['A1', 'A2'])
            ->orderBy('tabla_codigo')
            ->orderBy('fraccion_arancelaria')
            ->paginate(50, ['*'], 'page_bcd')
            ->withQueryString();

        $totalPartesCatalogo = DB::table('apendice_partes_catalogo')->count();
        $resumenTablas       = DB::table('apendice_partes_catalogo')
            ->select('tabla_codigo', 'tabla', DB::raw('count(*) as total'))
            ->groupBy('tabla_codigo', 'tabla')
            ->orderBy('tabla_codigo')
            ->get();

        // ── Sección C ────────────────────────────────────────────────────────
        $queryC = DB::table('seccion_c_fracciones');
        if ($searchC = $request->get('search_c')) {
            $queryC->where(function ($q) use ($searchC) {
                $q->where('fraccion_tmec',    'like', "%{$searchC}%")
                  ->orWhere('fraccion_mexico', 'like', "%{$searchC}%")
                  ->orWhere('descripcion',     'like', "%{$searchC}%");
            });
        }
        $seccionC      = $queryC->orderBy('fraccion_tmec')->paginate(50, ['*'], 'page_c')->withQueryString();
        $totalSeccionC = DB::table('seccion_c_fracciones')->count();

        // ── Parámetros del sistema ────────────────────────────────────────────
        $parametros = DB::table('parametros_sistema_ce')->orderBy('clave')->get();

        return view('Legal.comercio-exterior.catalogo.index', compact(
            'tab',
            'reglasOrigen', 'capitulos', 'criterios',
            'totalReglas', 'totalApendice', 'totalConVcr',
            'tablaA1', 'tablaA2', 'tablasBcd',
            'totalPartesCatalogo', 'resumenTablas',
            'seccionC', 'totalSeccionC',
            'parametros',
        ));
    }
}
