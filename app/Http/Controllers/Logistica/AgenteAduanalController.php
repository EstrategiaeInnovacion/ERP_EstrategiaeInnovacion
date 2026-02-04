<?php

namespace App\Http\Controllers\Logistica;

use App\Http\Controllers\Controller;
use App\Models\Logistica\AgenteAduanal;
use Illuminate\Http\Request;

class AgenteAduanalController extends Controller
{
    public function store(Request $request)
    {
        $request->validate(['agente_aduanal' => 'required|unique:agentes_aduanales']);
        $agente = AgenteAduanal::create($request->only('agente_aduanal'));
        return response()->json(['success' => true, 'agente' => $agente]);
    }

    public function update(Request $request, $id)
    {
        $agente = AgenteAduanal::findOrFail($id);
        $request->validate(['agente_aduanal' => 'required|unique:agentes_aduanales,agente_aduanal,'.$id]);
        $agente->update($request->only('agente_aduanal'));
        return response()->json(['success' => true, 'agente' => $agente]);
    }

    public function destroy($id)
    {
        $agente = AgenteAduanal::findOrFail($id);
        if ($agente->operaciones()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Tiene operaciones asociadas'], 400);
        }
        $agente->delete();
        return response()->json(['success' => true]);
    }
}