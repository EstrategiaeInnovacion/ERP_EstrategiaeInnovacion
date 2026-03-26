<?php

namespace App\Http\Controllers\Legal;

use App\Http\Controllers\Controller;
use App\Models\Legal\LegalCategoria;
use App\Models\Legal\LegalProyecto;
use App\Models\Legal\LegalArchivo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MatrizConsultaController extends Controller
{
    public function index(Request $request)
    {
        $query = LegalProyecto::with(['categoria.parent', 'archivos']);

        if ($request->filled('empresa')) {
            $query->where('empresa', 'like', '%' . $request->empresa . '%');
        }

        if ($request->filled('categoria_id')) {
            $catId = $request->categoria_id;
            // Incluir proyectos de la categoría seleccionada y sus subcategorías
            $subcatIds = LegalCategoria::where('parent_id', $catId)->pluck('id');
            $ids = $subcatIds->prepend($catId);
            $query->whereIn('categoria_id', $ids);
        }

        $proyectos   = $query->orderBy('empresa')->orderBy('created_at', 'desc')->get();
        $categorias  = LegalCategoria::with('subcategorias')->whereNull('parent_id')->orderBy('nombre')->get();
        $empresas    = LegalProyecto::select('empresa')->distinct()->orderBy('empresa')->pluck('empresa');

        return view('Legal.matriz-consulta.index', compact('proyectos', 'categorias', 'empresas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'empresa'      => 'required|string|max:255',
            'categoria_id' => 'required|exists:legal_categorias,id',
            'consulta'     => 'required|string',
            'resultado'    => 'required|string',
            'archivos_file.*' => 'nullable|file|max:20480|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,webp',
            'ruta_valor.*'    => 'nullable|string|max:500',
        ]);

        $proyecto = LegalProyecto::create([
            'empresa'      => $request->empresa,
            'categoria_id' => $request->categoria_id,
            'consulta'     => $request->consulta,
            'resultado'    => $request->resultado,
        ]);

        // Archivos subidos al sistema
        if ($request->hasFile('archivos_file')) {
            foreach ($request->file('archivos_file') as $index => $file) {
                if (!$file || !$file->isValid()) continue;

                $nombre    = $request->input("archivos_nombre.{$index}") ?: $file->getClientOriginalName();
                $extension = strtolower($file->getClientOriginalExtension());
                $tipoAuto  = $this->detectarTipo($extension);

                $ruta = $file->store("legal/archivos/{$proyecto->id}", 'public');

                LegalArchivo::create([
                    'proyecto_id' => $proyecto->id,
                    'nombre'      => $nombre,
                    'tipo'        => $tipoAuto,
                    'ruta'        => $ruta,
                    'es_url'      => false,
                    'mime_type'   => $file->getMimeType(),
                ]);
            }
        }

        // Rutas/URLs externas
        $rutasValor  = $request->input('ruta_valor', []);
        $rutasNombre = $request->input('ruta_nombre', []);

        foreach ($rutasValor as $i => $ruta) {
            $ruta = trim($ruta);
            if (empty($ruta)) continue;

            LegalArchivo::create([
                'proyecto_id' => $proyecto->id,
                'nombre'      => !empty($rutasNombre[$i]) ? $rutasNombre[$i] : $ruta,
                'tipo'        => 'otro',
                'ruta'        => $ruta,
                'es_url'      => true,
                'mime_type'   => null,
            ]);
        }

        return redirect()->route('legal.matriz.index')
            ->with('success', 'Proyecto "' . $proyecto->empresa . '" agregado correctamente.');
    }

    public function show($id)
    {
        $proyecto = LegalProyecto::with(['categoria.parent', 'archivos'])->findOrFail($id);

        if (request()->expectsJson()) {
            return response()->json([
                'proyecto' => [
                    'id'         => $proyecto->id,
                    'empresa'    => $proyecto->empresa,
                    'categoria'  => $proyecto->categoria?->nombre,
                    'consulta'   => $proyecto->consulta,
                    'resultado'  => $proyecto->resultado,
                    'archivos'   => $proyecto->archivos->map(fn($a) => [
                        'id'         => $a->id,
                        'nombre'     => $a->nombre,
                        'tipo'       => $a->tipo,
                        'es_url'     => $a->es_url,
                        'url_publica'=> $a->url_publica,
                        'ruta'       => $a->ruta,
                    ]),
                ]
            ]);
        }

        return view('Legal.matriz-consulta.show', compact('proyecto'));
    }

    public function destroy($id)
    {
        $proyecto = LegalProyecto::findOrFail($id);

        // Eliminar archivos físicos del storage
        foreach ($proyecto->archivos as $archivo) {
            if (!$archivo->es_url && Storage::disk('public')->exists($archivo->ruta)) {
                Storage::disk('public')->delete($archivo->ruta);
            }
        }

        $proyecto->delete();

        return redirect()->route('legal.matriz.index')
            ->with('success', 'Proyecto eliminado.');
    }

    public function destroyArchivo($id)
    {
        $archivo = LegalArchivo::findOrFail($id);

        if (!$archivo->es_url && Storage::disk('public')->exists($archivo->ruta)) {
            Storage::disk('public')->delete($archivo->ruta);
        }

        $archivo->delete();

        return response()->json(['success' => true]);
    }

    public function downloadArchivo($id)
    {
        $archivo = LegalArchivo::findOrFail($id);

        if ($archivo->es_url) {
            return redirect($archivo->ruta);
        }

        if (!Storage::disk('public')->exists($archivo->ruta)) {
            abort(404, 'Archivo no encontrado.');
        }

        return Storage::disk('public')->download($archivo->ruta, $archivo->nombre);
    }

    private function detectarTipo(string $ext): string
    {
        return match ($ext) {
            'pdf'                     => 'pdf',
            'doc', 'docx'             => 'word',
            'xls', 'xlsx'             => 'excel',
            'jpg', 'jpeg', 'png',
            'gif', 'webp'             => 'imagen',
            default                   => 'otro',
        };
    }
}
