<?php

namespace App\Http\Controllers\Legal;

use App\Http\Controllers\Controller;
use App\Models\Legal\LegalCategoria;
use Illuminate\Http\Request;

class CategoriaLegalController extends Controller
{
    public function index()
    {
        $categorias = LegalCategoria::with('subcategorias')
            ->whereNull('parent_id')
            ->orderBy('nombre')
            ->get();

        $todasCategorias = LegalCategoria::orderBy('nombre')->get();

        return view('Legal.categorias.index', compact('categorias', 'todasCategorias'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'tipo'   => 'required|in:consulta,escritos',
        ]);

        $categoria = LegalCategoria::create([
            'nombre'    => $request->nombre,
            'tipo'      => $request->tipo,
            'parent_id' => null,
        ]);

        return redirect()->route('legal.matriz.index', [
                'nueva_categoria'      => $categoria->id,
                'nueva_categoria_tipo' => $categoria->tipo,
            ])
            ->with('success', 'Categoría "' . $categoria->nombre . '" creada. Ahora puedes crear un proyecto con ella.');
    }

    public function destroy($id)
    {
        $categoria = LegalCategoria::findOrFail($id);

        // Promover subcategorías a categorías raíz antes de eliminar
        LegalCategoria::where('parent_id', $id)->update(['parent_id' => null]);

        $categoria->delete();

        return redirect()->route('legal.categorias.index')
            ->with('success', 'Categoría eliminada.');
    }
}
