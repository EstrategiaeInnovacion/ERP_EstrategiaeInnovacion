<?php

namespace App\Http\Controllers\Logistica;

use App\Http\Controllers\Controller;
use App\Models\Logistica\ColumnaVisibleEjecutivo;
use App\Models\Empleado;
use Illuminate\Http\Request;

class ColumnaVisibleController extends Controller
{
    /**
     * Obtener lista de ejecutivos de logística para el selector
     */
    public function getEjecutivos()
    {
        $ejecutivos = Empleado::where(function($query) {
            $query->where('posicion', 'like', '%LOGISTICA%')
                  ->orWhere('posicion', 'like', '%Logistica%')
                  ->orWhere('area', 'Logistica');
        })->orderBy('nombre')->get(['id', 'nombre', 'posicion']);

        return response()->json([
            'success' => true,
            'ejecutivos' => $ejecutivos
        ]);
    }

    /**
     * Obtener configuración de columnas opcionales para un ejecutivo
     */
    public function getConfiguracion($empleadoId)
    {
        $config = ColumnaVisibleEjecutivo::getConfiguracionOpcionalesEjecutivo($empleadoId);
        $columnasPredeterminadas = ColumnaVisibleEjecutivo::getColumnasPredeterminadasConNombres('es');

        return response()->json([
            'success' => true,
            'configuracion' => $config,
            'columnasPredeterminadas' => $columnasPredeterminadas
        ]);
    }

    /**
     * Guardar configuración de una columna opcional
     */
    public function guardarConfiguracion(Request $request)
    {
        $request->validate([
            'empleado_id' => 'required|exists:empleados,id',
            'columna' => 'required|string',
            'visible' => 'required|boolean',
            'mostrar_despues_de' => 'nullable|string'
        ]);

        ColumnaVisibleEjecutivo::guardarConfiguracionColumnaOpcional(
            $request->empleado_id,
            $request->columna,
            $request->visible,
            $request->mostrar_despues_de
        );

        return response()->json([
            'success' => true,
            'message' => 'Configuración guardada correctamente'
        ]);
    }

    /**
     * Guardar toda la configuración de columnas de un ejecutivo
     */
    public function guardarConfiguracionCompleta(Request $request)
    {
        $request->validate([
            'empleado_id' => 'required|exists:empleados,id',
            'columnas' => 'required|array'
        ]);

        foreach ($request->columnas as $columnaData) {
            ColumnaVisibleEjecutivo::guardarConfiguracionColumnaOpcional(
                $request->empleado_id,
                $columnaData['columna'],
                $columnaData['visible'] ?? false,
                $columnaData['mostrar_despues_de'] ?? null
            );
        }

        return response()->json([
            'success' => true,
            'message' => 'Configuración guardada correctamente'
        ]);
    }
}
