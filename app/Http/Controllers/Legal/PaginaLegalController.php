<?php

namespace App\Http\Controllers\Legal;

use App\Http\Controllers\Controller;
use App\Models\Legal\LegalPagina;
use Illuminate\Http\Request;

class PaginaLegalController extends Controller
{
    public function index()
    {
        $paginas = LegalPagina::orderBy('nombre')->get();
        return view('Legal.programas.index', compact('paginas'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre' => 'required|string|max:255',
            'url'    => 'required|url|max:2048',
        ], [
            'nombre.required' => 'El nombre del sistema es obligatorio.',
            'url.required'    => 'La URL es obligatoria.',
            'url.url'         => 'Ingresa una URL válida (debe incluir http:// o https://).',
        ]);

        LegalPagina::create($request->only('nombre', 'url'));

        return redirect()->route('legal.programas.index')
            ->with('success', 'Programa/Página "' . $request->nombre . '" agregado correctamente.');
    }

    public function update(Request $request, $id)
    {
        $pagina = LegalPagina::findOrFail($id);

        $request->validate([
            'nombre' => 'required|string|max:255',
            'url'    => 'required|url|max:2048',
        ], [
            'nombre.required' => 'El nombre del sistema es obligatorio.',
            'url.required'    => 'La URL es obligatoria.',
            'url.url'         => 'Ingresa una URL válida (debe incluir http:// o https://).',
        ]);

        $pagina->update($request->only('nombre', 'url'));

        return redirect()->route('legal.programas.index')
            ->with('success', 'Actualizado correctamente.');
    }

    public function destroy($id)
    {
        $pagina = LegalPagina::findOrFail($id);
        $nombre = $pagina->nombre;
        $pagina->delete();

        return redirect()->route('legal.programas.index')
            ->with('success', '"' . $nombre . '" eliminado correctamente.');
    }
}
