<?php

namespace App\Http\Controllers\Logistica;

use App\Http\Controllers\Controller;
use App\Models\Logistica\LogisticaCorreoCC;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class LogisticaCorreoCCController extends Controller
{
    /**
     * Mostrar lista de correos CC
     */
    public function index()
    {
        $correos = LogisticaCorreoCC::orderBy('tipo')->orderBy('nombre')->get();
        return view('Logistica.catalogos.correos-cc.index', compact('correos'));
    }
    
    /**
     * API para obtener correos CC activos
     */
    public function api()
    {
        $correos = LogisticaCorreoCC::activos()
            ->orderBy('tipo')
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'email', 'tipo']);
            
        return response()->json($correos);
    }

    /**
     * Mostrar formulario para crear nuevo correo CC
     */
    public function create()
    {
        return view('Logistica.catalogos.correos-cc.create');
    }

    /**
     * Almacenar nuevo correo CC
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|unique:logistica_correos_cc,email',
            'tipo' => 'required|in:administrador,supervisor,notificacion',
            'descripcion' => 'nullable|string|max:500',
            'activo' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        LogisticaCorreoCC::create([
            'nombre' => $request->nombre,
            'email' => $request->email,
            'tipo' => $request->tipo,
            'descripcion' => $request->descripcion,
            'activo' => $request->has('activo')
        ]);

        return redirect()->route('logistica.correos-cc.index')
            ->with('success', 'Correo CC creado exitosamente');
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(LogisticaCorreoCC $correoCC)
    {
        return view('Logistica.catalogos.correos-cc.edit', compact('correoCC'));
    }

    /**
     * Actualizar correo CC
     */
    public function update(Request $request, LogisticaCorreoCC $correoCC)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'email' => 'required|email|unique:logistica_correos_cc,email,' . $correoCC->id,
            'tipo' => 'required|in:administrador,supervisor,notificacion',
            'descripcion' => 'nullable|string|max:500',
            'activo' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        $correoCC->update([
            'nombre' => $request->nombre,
            'email' => $request->email,
            'tipo' => $request->tipo,
            'descripcion' => $request->descripcion,
            'activo' => $request->has('activo')
        ]);

        return redirect()->route('logistica.correos-cc.index')
            ->with('success', 'Correo CC actualizado exitosamente');
    }

    /**
     * Eliminar correo CC
     */
    public function destroy(LogisticaCorreoCC $correoCC)
    {
        try {
            $correoCC->delete();
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Correo CC eliminado exitosamente'
                ]);
            }
            
            return redirect()->route('logistica.correos-cc.index')
                ->with('success', 'Correo CC eliminado exitosamente');
                
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al eliminar el correo CC'
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'Error al eliminar el correo CC');
        }
    }

    /**
     * Cambiar estado activo/inactivo
     */
    public function toggleActivo(LogisticaCorreoCC $correoCC)
    {
        try {
            $correoCC->update(['activo' => !$correoCC->activo]);
            
            $mensaje = $correoCC->activo ? 'Correo CC activado' : 'Correo CC desactivado';
            
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $mensaje,
                    'correo' => $correoCC
                ]);
            }
            
            return redirect()->route('logistica.correos-cc.index')
                ->with('success', $mensaje);
                
        } catch (\Exception $e) {
            if (request()->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Error al cambiar el estado'
                ], 500);
            }
            
            return redirect()->back()
                ->with('error', 'Error al cambiar el estado');
        }
    }

    /**
     * API: Obtener correos CC por tipo
     */
    public function apiPorTipo(Request $request)
    {
        $tipo = $request->get('tipo');
        $correos = LogisticaCorreoCC::activos();
        
        if ($tipo) {
            $correos = $correos->porTipo($tipo);
        }
        
        return response()->json($correos->get());
    }

    /**
     * API: Obtener todos los correos CC activos para emails
     */
    public function apiTodosActivos()
    {
        $correos = LogisticaCorreoCC::todosActivos();
        return response()->json($correos);
    }
}