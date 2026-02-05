<?php

namespace App\Http\Controllers\Logistica;

use App\Http\Controllers\Controller;
use App\Models\Logistica\ColumnaVisibleEjecutivo;
use App\Models\Logistica\CampoPersonalizadoMatriz;
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
     * Obtener configuración de columnas opcionales + campos personalizados para un ejecutivo
     */
    public function getConfiguracion($empleadoId)
    {
        $config = ColumnaVisibleEjecutivo::getConfiguracionOpcionalesEjecutivo($empleadoId);
        $columnasPredeterminadas = ColumnaVisibleEjecutivo::getColumnasPredeterminadasConNombres('es');
        
        // Obtener campos personalizados con su configuración de visibilidad
        $camposPersonalizados = $this->getCamposPersonalizadosConConfig($empleadoId);

        return response()->json([
            'success' => true,
            'configuracion' => $config,
            'columnasPredeterminadas' => $columnasPredeterminadas,
            'camposPersonalizados' => $camposPersonalizados
        ]);
    }

    /**
     * Obtener campos personalizados con configuración de visibilidad para un ejecutivo
     */
    private function getCamposPersonalizadosConConfig($empleadoId)
    {
        $campos = CampoPersonalizadoMatriz::where('activo', true)->orderBy('orden')->get();
        
        // Configuración específica del ejecutivo
        $configEjecutivo = ColumnaVisibleEjecutivo::where('empleado_id', $empleadoId)
            ->where('columna', 'like', 'campo_personalizado_%')
            ->get()
            ->keyBy('columna');
        
        // Configuración global
        $configGlobal = ColumnaVisibleEjecutivo::whereNull('empleado_id')
            ->where('columna', 'like', 'campo_personalizado_%')
            ->get()
            ->keyBy('columna');
        
        $resultado = [];
        foreach ($campos as $campo) {
            $columnaKey = 'campo_personalizado_' . $campo->id;
            $configEjec = $configEjecutivo->get($columnaKey);
            $configGlob = $configGlobal->get($columnaKey);
            
            $esGlobal = $configGlob && $configGlob->visible;
            $visibleEjecutivo = $configEjec ? $configEjec->visible : false;
            
            $resultado[$columnaKey] = [
                'id' => $campo->id,
                'nombre_es' => $campo->nombre,
                'nombre_en' => $campo->nombre, // Usar mismo nombre
                'tipo' => $campo->tipo,
                'requerido' => $campo->requerido,
                'opciones' => $campo->opciones,
                'visible' => $visibleEjecutivo,
                'es_global' => $esGlobal,
                'mostrar_despues_de' => $configEjec ? $configEjec->mostrar_despues_de : 
                                       ($configGlob ? $configGlob->mostrar_despues_de : 'comentarios'),
                'es_campo_personalizado' => true
            ];
        }
        
        return $resultado;
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

    /**
     * Obtener configuración global (columnas visibles para todos)
     */
    public function getConfiguracionGlobal()
    {
        $config = ColumnaVisibleEjecutivo::getConfiguracionGlobal();
        $columnasPredeterminadas = ColumnaVisibleEjecutivo::getColumnasPredeterminadasConNombres('es');

        return response()->json([
            'success' => true,
            'configuracion' => $config,
            'columnasPredeterminadas' => $columnasPredeterminadas
        ]);
    }

    /**
     * Guardar configuración global de una columna (visible para todos)
     */
    public function guardarConfiguracionGlobal(Request $request)
    {
        $request->validate([
            'columna' => 'required|string',
            'visible' => 'required|boolean',
            'mostrar_despues_de' => 'nullable|string'
        ]);

        ColumnaVisibleEjecutivo::guardarConfiguracionColumnaGlobal(
            $request->columna,
            $request->visible,
            $request->mostrar_despues_de
        );

        return response()->json([
            'success' => true,
            'message' => 'Configuración global guardada correctamente'
        ]);
    }
}
