<?php

namespace App\Http\Controllers\Legal;

use App\Http\Controllers\Controller;
use App\Models\Legal\LegalArchivo;
use App\Models\Legal\LegalCategoria;
use App\Models\Legal\LegalProyecto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MatrizConsultaController extends Controller
{
    public function index(Request $request)
    {
        $query = LegalProyecto::with(['categoria.parent', 'archivos']);

        if ($request->filled('empresa')) {
            $query->where('empresa', 'like', '%'.$request->empresa.'%');
        }

        if ($request->filled('buscar')) {
            $term = '%'.$request->buscar.'%';
            $query->where(function ($q) use ($term) {
                $q->where('empresa', 'like', $term)
                    ->orWhere('consulta', 'like', $term);
            });
        }

        if ($request->filled('categoria_id')) {
            $catId = $request->categoria_id;
            // Incluir proyectos de la categoría seleccionada y sus subcategorías
            $subcatIds = LegalCategoria::where('parent_id', $catId)->pluck('id');
            $ids = $subcatIds->prepend($catId);
            $query->whereIn('categoria_id', $ids);
        }

        if ($request->filled('tipo') && $request->tipo !== 'todos') {
            $query->where('tipo', $request->tipo);
        }

        $proyectos = $query->orderBy('empresa')->orderBy('created_at', 'desc')->get();
        $categorias = LegalCategoria::with('subcategorias')->whereNull('parent_id')->orderBy('nombre')->get();
        $categoriasConsultas = $categorias->filter(fn($c) => $c->tipo === 'consulta')->values();
        $categoriasEscritos  = $categorias->filter(fn($c) => $c->tipo === 'escritos')->values();
        $empresas = LegalProyecto::where('tipo', 'consulta')->select('empresa')->distinct()->orderBy('empresa')->pluck('empresa');
        $proyectosNombres = LegalProyecto::where('tipo', 'escritos')->select('empresa')->distinct()->orderBy('empresa')->pluck('empresa');

        return view('Legal.matriz-consulta.index', compact('proyectos', 'categorias', 'categoriasConsultas', 'categoriasEscritos', 'empresas', 'proyectosNombres'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'empresa' => 'required|string|max:255',
            'tipo' => 'required|in:consulta,escritos',
            'cliente' => 'nullable|string|max:255',
            'consulta' => 'nullable|string',
            'resultado' => 'nullable|string',
            'detalles' => 'nullable|string',
            'archivos_file.*' => 'nullable|file|max:20480|mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,gif,webp',
        ]);

        // Si se seleccionó crear nueva categoría
        $categoriaId = $request->categoria_id;
        if ($request->categoria_id === '__nueva__' && $request->filled('nueva_categoria_nombre')) {
            $nuevaCategoria = LegalCategoria::create([
                'nombre' => $request->nueva_categoria_nombre,
            ]);
            $categoriaId = $nuevaCategoria->id;
        }

        // Validar que la categoría exista
        if (! $categoriaId || ! LegalCategoria::where('id', $categoriaId)->exists()) {
            return redirect()->back()->with('error', 'Debes seleccionar o crear una categoría.');
        }

        $proyecto = LegalProyecto::create([
            'empresa' => $request->empresa,
            'tipo' => $request->tipo,
            'cliente' => $request->cliente,
            'categoria_id' => $categoriaId,
            'consulta' => $request->consulta,
            'resultado' => $request->resultado,
            'detalles' => $request->detalles,
        ]);

        // Archivos subidos al sistema
        if ($request->hasFile('archivos_file')) {
            foreach ($request->file('archivos_file') as $index => $file) {
                if (! $file || ! $file->isValid()) {
                    continue;
                }

                $nombre = $request->input("archivos_nombre.{$index}") ?: $file->getClientOriginalName();
                $extension = strtolower($file->getClientOriginalExtension());
                $tipoAuto = $this->detectarTipo($extension);

                $ruta = $file->store("legal/archivos/{$proyecto->id}", 'public');

                LegalArchivo::create([
                    'proyecto_id' => $proyecto->id,
                    'nombre' => $nombre,
                    'tipo' => $tipoAuto,
                    'ruta' => $ruta,
                    'es_url' => false,
                    'mime_type' => $file->getMimeType(),
                ]);
            }
        }

        return redirect()->route('legal.matriz.index')
            ->with('success', 'Proyecto "'.$proyecto->empresa.'" agregado correctamente.');
    }

    public function update(Request $request, $id)
    {
        $proyecto = LegalProyecto::findOrFail($id);

        $request->validate([
            'empresa' => 'required|string|max:255',
            'tipo' => 'required|in:consulta,escritos',
            'categoria_id' => 'required|exists:legal_categorias,id',
            'cliente' => 'nullable|string|max:255',
            'consulta' => 'nullable|string',
            'resultado' => 'nullable|string',
            'detalles' => 'nullable|string',
        ]);

        $proyecto->update([
            'empresa' => $request->empresa,
            'tipo' => $request->tipo,
            'cliente' => $request->cliente,
            'categoria_id' => $request->categoria_id,
            'consulta' => $request->consulta,
            'resultado' => $request->resultado,
            'detalles' => $request->detalles,
        ]);

        return redirect()->route('legal.matriz.index')
            ->with('success', 'Proyecto actualizado correctamente.');
    }

    public function show($id)
    {
        $proyecto = LegalProyecto::with(['categoria.parent', 'archivos'])->findOrFail($id);

        if (request()->expectsJson()) {
            return response()->json([
                'proyecto' => [
                    'id' => $proyecto->id,
                    'empresa' => $proyecto->empresa,
                    'tipo' => $proyecto->tipo,
                    'cliente' => $proyecto->cliente,
                    'categoria_id' => $proyecto->categoria_id,
                    'categoria' => $proyecto->categoria?->nombre,
                    'consulta' => $proyecto->consulta,
                    'resultado' => $proyecto->resultado,
                    'detalles' => $proyecto->detalles,
                    'archivos' => $proyecto->archivos->map(fn ($a) => [
                        'id' => $a->id,
                        'nombre' => $a->nombre,
                        'tipo' => $a->tipo,
                        'es_url' => $a->es_url,
                        'url_publica' => $a->url_publica,
                        'ruta' => $a->ruta,
                    ]),
                ],
            ]);
        }

        return view('Legal.matriz-consulta.show', compact('proyecto'));
    }

    public function destroy($id)
    {
        $proyecto = LegalProyecto::findOrFail($id);

        // Eliminar archivos físicos del storage
        foreach ($proyecto->archivos as $archivo) {
            if (! $archivo->es_url && Storage::disk('public')->exists($archivo->ruta)) {
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

        if (! $archivo->es_url && Storage::disk('public')->exists($archivo->ruta)) {
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

        if (! Storage::disk('public')->exists($archivo->ruta)) {
            abort(404, 'Archivo no encontrado.');
        }

        // Preservar la extensión original del archivo almacenado
        $extension = pathinfo($archivo->ruta, PATHINFO_EXTENSION);
        $nombreBase = pathinfo($archivo->nombre, PATHINFO_FILENAME) ?: $archivo->nombre;
        $nombreDescarga = $extension ? "{$nombreBase}.{$extension}" : $archivo->nombre;

        return Storage::disk('public')->download($archivo->ruta, $nombreDescarga);
    }

    private function detectarTipo(string $ext): string
    {
        return match ($ext) {
            'pdf' => 'pdf',
            'doc', 'docx' => 'word',
            'xls', 'xlsx' => 'excel',
            'jpg', 'jpeg', 'png',
            'gif', 'webp' => 'imagen',
            default => 'otro',
        };
    }
}
