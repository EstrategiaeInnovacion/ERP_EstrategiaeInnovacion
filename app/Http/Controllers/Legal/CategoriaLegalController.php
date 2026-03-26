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
            'nombre'    => 'required|string|max:255',
            'parent_id' => 'nullable|exists:legal_categorias,id',
        ]);

        LegalCategoria::create([
            'nombre'    => $request->nombre,
            'parent_id' => $request->parent_id ?: null,
        ]);

        return redirect()->route('legal.categorias.index')
            ->with('success', 'Categoría "' . $request->nombre . '" creada correctamente.');
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
