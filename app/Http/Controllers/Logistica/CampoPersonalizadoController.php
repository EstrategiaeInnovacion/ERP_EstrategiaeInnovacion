<?php

namespace App\Http\Controllers\Logistica;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Logistica\CampoPersonalizadoMatriz;
use App\Models\Logistica\ValorCampoPersonalizado;
use Illuminate\Support\Facades\DB;

class CampoPersonalizadoController extends Controller
{
    /**
     * Guarda o actualiza el valor de un campo personalizado (AJAX)
     */
    public function storeValor(Request $request)
    {
        // Validación rápida
        $request->validate([
            'operacion_id' => 'required|integer',
            'campo_id' => 'required|integer',
            'valor' => 'nullable' // Permitimos nulos para "borrar" el dato
        ]);

        try {
            // Usamos updateOrCreate para manejar inserción o actualización en una sola consulta
            $registro = ValorCampoPersonalizado::updateOrCreate(
                [
                    'operacion_logistica_id' => $request->operacion_id,
                    'campo_personalizado_id' => $request->campo_id
                ],
                [
                    'valor' => $request->valor
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Valor guardado.',
                'data' => $registro
            ]);

        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al guardar: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Obtiene los valores de una operación específica (Optimizado)
     */
    public function getValoresOperacion($id)
    {
        // Usamos pluck para obtener solo un array simple [campo_id => valor]
        // Esto es mucho más rápido y ligero para JSON que traer objetos completos
        $valores = ValorCampoPersonalizado::where('operacion_logistica_id', $id)
            ->pluck('valor', 'campo_personalizado_id');
            
        return response()->json($valores);
    }

    // --- Métodos de Configuración (Ya existentes, optimizados) ---

    public function index() {
        // Cachear esto sería ideal si rara vez cambia
        return CampoPersonalizadoMatriz::orderBy('orden')->get();
    }

    public function store(Request $request) {
        $validated = $request->validate([
            'nombre' => 'required|string|max:100',
            'tipo' => 'required|in:texto,descripcion,fecha,numero,decimal,moneda,booleano,selector,multiple,email,telefono,url',
            'opciones' => 'nullable', // JSON o array
            'requerido' => 'nullable|boolean',
            'activo' => 'nullable|boolean',
            'orden' => 'nullable|integer'
        ]);
        
        // Procesar opciones si vienen como array
        if (isset($validated['opciones']) && is_array($validated['opciones'])) {
            $validated['opciones'] = $validated['opciones'];
        }
        
        // Valores por defecto
        $validated['activo'] = $request->input('activo', true);
        $validated['requerido'] = $request->input('requerido', false);
        $validated['orden'] = $request->input('orden', 99);
        
        $campo = CampoPersonalizadoMatriz::create($validated);
        return response()->json(['success' => true, 'campo' => $campo]);
    }

    /**
     * Actualiza un campo personalizado
     */
    public function update(Request $request, $id) {
        $campo = CampoPersonalizadoMatriz::findOrFail($id);
        
        $validated = $request->validate([
            'nombre' => 'sometimes|string|max:100',
            'tipo' => 'sometimes|in:texto,descripcion,fecha,numero,decimal,moneda,booleano,selector,multiple,email,telefono,url',
            'opciones' => 'nullable',
            'requerido' => 'nullable|boolean',
            'activo' => 'nullable|boolean',
            'orden' => 'nullable|integer'
        ]);
        
        $campo->update($validated);
        return response()->json(['success' => true, 'campo' => $campo]);
    }

    /**
     * Toggle activo/inactivo de un campo
     */
    public function toggleActivo(Request $request, $id) {
        $campo = CampoPersonalizadoMatriz::findOrFail($id);
        
        $campo->activo = $request->input('activo', !$campo->activo);
        $campo->save();
        
        return response()->json(['success' => true, 'campo' => $campo]);
    }

    public function destroy($id) {
        CampoPersonalizadoMatriz::destroy($id);
        return response()->json(['success' => true]);
    }

    public function getCamposActivos() {
        return CampoPersonalizadoMatriz::activos()->ordenado()->get();
    }
}