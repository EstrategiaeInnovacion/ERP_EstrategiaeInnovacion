<?php

namespace App\Http\Controllers\Logistica;

use App\Http\Controllers\Controller;
use App\Models\Logistica\PostOperacion;
use App\Models\Logistica\PostOperacionOperacion;
use App\Models\Logistica\OperacionLogistica;
use Illuminate\Http\Request;

class PostOperacionController extends Controller
{
    // --- PLANTILLAS GLOBALES ---

    public function indexGlobal()
    {
        $globales = PostOperacion::whereNull('operacion_logistica_id')->orderBy('nombre')->get();
        return response()->json(['success' => true, 'postOperaciones' => $globales]);
    }

    public function storeGlobal(Request $request)
    {
        $request->validate(['nombre' => 'required']);
        $po = PostOperacion::create([
            'nombre' => $request->nombre,
            'descripcion' => $request->descripcion,
            'status' => 'Plantilla',
            'operacion_logistica_id' => null
        ]);
        return response()->json(['success' => true, 'postOperacion' => $po]);
    }

    // --- ESPECÍFICAS DE UNA OPERACIÓN ---

    public function getByOperacion($operacionId)
    {
        $operacion = OperacionLogistica::findOrFail($operacionId);
        $globales = PostOperacion::where('status', 'Plantilla')->get();
        
        // Obtener asignaciones específicas (tabla pivot/relación)
        $asignaciones = PostOperacionOperacion::where('operacion_logistica_id', $operacionId)
                        ->get()->keyBy('post_operacion_id');

        $data = $globales->map(function($g) use ($asignaciones) {
            $asignacion = $asignaciones->get($g->id);
            return [
                'id_global' => $g->id,
                'nombre' => $g->nombre,
                'status' => $asignacion ? $asignacion->status : 'Pendiente',
                'fecha_completado' => $asignacion?->fecha_completado?->format('d/m/Y H:i'),
                'es_plantilla' => true
            ];
        });

        return response()->json(['success' => true, 'postOperaciones' => $data]);
    }

    public function bulkUpdate(Request $request, $operacionId)
    {
        $request->validate(['cambios' => 'required|array']);
        
        foreach ($request->cambios as $postOpId => $data) {
            $estado = $data['estado'];
            
            // Buscar si ya existe la relación
            $relacion = PostOperacionOperacion::where('post_operacion_id', $postOpId)
                        ->where('operacion_logistica_id', $operacionId)->first();

            if ($estado === 'Pendiente' && $relacion) {
                $relacion->delete(); // Si vuelve a pendiente, borramos la marca de "hecho"
            } elseif ($estado !== 'Pendiente') {
                PostOperacionOperacion::updateOrCreate(
                    ['post_operacion_id' => $postOpId, 'operacion_logistica_id' => $operacionId],
                    ['status' => $estado, 'fecha_completado' => ($estado == 'Completado' ? now() : null)]
                );
            }
        }
        return response()->json(['success' => true, 'message' => 'Actualizado']);
    }
}