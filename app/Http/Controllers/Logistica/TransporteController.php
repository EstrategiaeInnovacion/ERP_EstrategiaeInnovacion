<?php

namespace App\Http\Controllers\Logistica;

use App\Http\Controllers\Controller;
use App\Models\Logistica\Transporte;
use Illuminate\Http\Request;

class TransporteController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'transporte' => 'required|string|max:255',
            'tipo_operacion' => 'required|in:Aerea,Terrestre,Maritima,Ferrocarril'
        ]);

        $transporte = Transporte::create($request->only('transporte', 'tipo_operacion'));

        return response()->json(['success' => true, 'transporte' => $transporte]);
    }

    public function update(Request $request, $id)
    {
        $transporte = Transporte::findOrFail($id);
        $transporte->update($request->only('transporte', 'tipo_operacion')); // Permitimos editar tipo también si es necesario

        return response()->json(['success' => true, 'transporte' => $transporte]);
    }

    public function destroy($id)
    {
        $transporte = Transporte::findOrFail($id);
        if ($transporte->operaciones()->count() > 0) {
            return response()->json(['success' => false, 'message' => 'Tiene operaciones asociadas'], 400);
        }
        $transporte->delete();
        return response()->json(['success' => true]);
    }

    public function getByType(Request $request)
    {
        $transportes = Transporte::where('tipo_operacion', $request->tipo)->orderBy('transporte')->get();
        return response()->json($transportes);
    }
}