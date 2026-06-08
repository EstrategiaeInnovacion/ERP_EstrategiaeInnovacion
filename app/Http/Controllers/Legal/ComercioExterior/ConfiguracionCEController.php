<?php

namespace App\Http\Controllers\Legal\ComercioExterior;

use App\Http\Controllers\Controller;
use App\Services\ComercioExterior\CatalogoExcelImportService;
use App\Services\ComercioExterior\OriginAnalysisService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ConfiguracionCEController extends Controller
{
    public function index(OriginAnalysisService $service)
    {
        $modo       = $service->getModoAnalisis();
        $parametros = DB::table('parametros_sistema_ce')->orderBy('clave')->get();

        // Información sobre el catálogo cargado
        $totalReglas       = DB::table('reglas_origen')->count();
        $totalAutomotrices = DB::table('reglas_automotrices')->count();
        $totalPartes       = DB::table('apendice_partes_catalogo')->count();
        $totalSeccionC     = DB::table('seccion_c_fracciones')->count();
        $ultimaImport      = DB::table('reglas_origen')->orderByDesc('updated_at')->value('updated_at');

        return view('Legal.comercio-exterior.configuracion.index', compact(
            'modo', 'parametros',
            'totalReglas', 'totalAutomotrices', 'totalPartes', 'totalSeccionC',
            'ultimaImport',
        ));
    }

    public function update(Request $request)
    {
        $request->validate([
            'modo_analisis' => 'required|in:local,ia',
        ]);

        DB::table('parametros_sistema_ce')->updateOrInsert(
            ['clave' => 'modo_analisis'],
            [
                'descripcion' => 'Motor de análisis de origen T-MEC (local o ia)',
                'valor_texto' => $request->modo_analisis,
                'activo'      => true,
                'updated_at'  => now(),
                'created_at'  => now(),
            ]
        );

        return redirect()->back()->with('success', 'Configuración guardada.');
    }

    public function uploadCatalogo(Request $request, CatalogoExcelImportService $importer)
    {
        $request->validate([
            'catalogo' => 'required|file|mimes:xlsx,xls|max:51200',
        ]);

        $path = $request->file('catalogo')->storeAs(
            'comercio-exterior',
            'catalogo_reglas_origen.xlsx',
            'local'
        );

        try {
            $result = $importer->import(Storage::disk('local')->path($path));

            $resumen = implode(', ', [
                $result['reglas_origen'] . ' reglas de origen',
                $result['reglas_automotrices'] . ' reglas automotrices',
                $result['apendice_partes'] . ' partes de apéndice',
                $result['seccion_c'] . ' fracciones Sección C',
                $result['relaciones'] . ' relaciones indexadas',
            ]);

            return redirect()->back()->with('success', "Catálogo importado exitosamente: {$resumen}.");

        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Error al importar el catálogo: ' . $e->getMessage());
        }
    }
}
