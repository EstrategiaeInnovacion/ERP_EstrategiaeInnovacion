<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\Capacitacion;
use App\Models\CapacitacionAdjunto; // Asegúrate de tener este modelo creado
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CapacitacionController extends Controller
{
    // --- VISTAS PÚBLICAS (EMPLEADOS) ---

    // Vista para TODOS los empleados (Galería)
    // Vista para TODOS los empleados (Galería)
    public function index()
    {
        $user = Auth::user();
        $videos = Capacitacion::where('activo', true)
            ->orderBy('created_at', 'desc')
            ->get(); // Obtenemos todos para poder filtrar en PHP

        // Filtramos en memoria usando la lógica del modelo
        $filteredVideos = $videos->filter(function ($video) use ($user) {
            return $video->isVisibleFor($user);
        });

        // Agrupamos por categoría
        // Si la categoría es null, usamos 'General' o 'Sin Categoría'
        $groupedVideos = $filteredVideos->groupBy(function ($item) {
            return $item->categoria ?: 'General';
        });

        return view('Recursos_Humanos.capacitacion.index', compact('groupedVideos'));
    }

    // Vista para VER un video específico
    public function show($id)
    {
        $video = Capacitacion::with('adjuntos')->findOrFail($id);
        return view('Recursos_Humanos.capacitacion.show', compact('video'));
    }

    // --- ÁREA DE ADMINISTRACIÓN (SOLO RH) ---

    // Panel de gestión
    public function manage()
    {
        $videos = Capacitacion::orderBy('created_at', 'desc')->get();
        // Obtener puestos únicos de empleados
        $puestos = \App\Models\Empleado::distinct()->pluck('posicion')->filter()->sort()->values();

        return view('Recursos_Humanos.capacitacion.manage', compact('videos', 'puestos'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'categoria' => 'nullable|string|max:255', // <-- AGREGADO
            'puestos_permitidos' => 'nullable|array', // <-- SE CAMBIO A ARRAY
            'puestos_permitidos.*' => 'string', // Validar que cada elemento sea string
            'youtube_url' => 'nullable|url',
            // Video requerido solo si NO hay youtube_url
            'video' => 'required_without:youtube_url|mimes:mp4,mov,ogg,qt|max:200000',
            'adjuntos.*' => 'nullable|file|max:10240'
        ]);

        try {
            $path = null;
            if ($request->hasFile('video')) {
                $path = $request->file('video')->store('capacitacion', 'public');
            }

            // Como ahora viene en array desde el select multiple, no necesitamos explode
            $puestosArray = $request->puestos_permitidos;

            $capacitacion = Capacitacion::create([
                'titulo' => $request->titulo,
                'descripcion' => $request->descripcion,
                'categoria' => $request->categoria,
                'puestos_permitidos' => $puestosArray,
                'archivo_path' => $path,
                'youtube_url' => $request->youtube_url,
                'subido_por' => Auth::id(),
            ]);

            if ($request->hasFile('adjuntos')) {
                foreach ($request->file('adjuntos') as $archivo) {
                    $docPath = $archivo->store('capacitacion_docs', 'public');
                    $capacitacion->adjuntos()->create([
                        'titulo' => $archivo->getClientOriginalName(),
                        'archivo_path' => $docPath
                    ]);
                }
            }

            return redirect()->route('rh.capacitacion.manage')->with('success', 'Video subido correctamente.');
        }
        catch (\Exception $e) {
            return back()->with('error', 'Error al subir: ' . $e->getMessage());
        }
    }

    // --- NUEVAS FUNCIONES PARA EDITAR ---

    // Vista de edición
    public function edit($id)
    {
        $video = Capacitacion::with('adjuntos')->findOrFail($id);
        $puestos = \App\Models\Empleado::distinct()->pluck('posicion')->filter()->sort()->values();
        return view('Recursos_Humanos.capacitacion.edit', compact('video', 'puestos'));
    }

    public function update(Request $request, $id)
    {
        try {
            $video = Capacitacion::findOrFail($id);

            $request->validate([
                'titulo' => 'required|string|max:255',
                'descripcion' => 'nullable|string',
                'categoria' => 'nullable|string|max:255',
                'puestos_permitidos' => 'nullable|array',
                'puestos_permitidos.*' => 'string',
                'youtube_url' => 'nullable|url',
                'video' => 'nullable|mimes:mp4,mov,ogg,qt|max:200000',
                'adjuntos.*' => 'nullable|file|max:10240'
            ]);

            $puestosArray = $request->puestos_permitidos;

            $video->update([
                'titulo' => $request->titulo,
                'descripcion' => $request->descripcion,
                'categoria' => $request->categoria,
                'puestos_permitidos' => $puestosArray,
                'youtube_url' => $request->youtube_url,
            ]);

            if ($request->hasFile('video')) {
                if ($video->archivo_path && Storage::disk('public')->exists($video->archivo_path)) {
                    Storage::disk('public')->delete($video->archivo_path);
                }
                $video->archivo_path = $request->file('video')->store('capacitacion', 'public');
                $video->youtube_url = null;
                $video->save();
            }
            elseif ($request->youtube_url) {
                if ($video->archivo_path && Storage::disk('public')->exists($video->archivo_path)) {
                    Storage::disk('public')->delete($video->archivo_path);
                    $video->archivo_path = null;
                }
                $video->save();
            }

            if ($request->hasFile('adjuntos')) {
                foreach ($request->file('adjuntos') as $archivo) {
                    $docPath = $archivo->store('capacitacion_docs', 'public');
                    $video->adjuntos()->create([
                        'titulo' => $archivo->getClientOriginalName(),
                        'archivo_path' => $docPath
                    ]);
                }
            }

            return redirect()->route('rh.capacitacion.manage')->with('success', 'Capacitación actualizada.');
        }
        catch (\Exception $e) {
            Log::error('Error actualizando capacitación: ' . $e->getMessage());
            return back()->with('error', 'Error al actualizar: ' . $e->getMessage())->withInput();
        }
    }

    // Eliminar video completo
    public function destroy($id)
    {
        try {
            $video = Capacitacion::findOrFail($id);

            // Laravel borra los adjuntos de la BD automáticamente si configuraste cascade, 
            // pero limpiamos los archivos físicos de los adjuntos primero:
            foreach ($video->adjuntos as $adjunto) {
                if (!empty($adjunto->archivo_path) && Storage::disk('public')->exists($adjunto->archivo_path)) {
                    Storage::disk('public')->delete($adjunto->archivo_path);
                }
            }

            // Eliminar archivo de video físico
            if (!empty($video->archivo_path) && Storage::disk('public')->exists($video->archivo_path)) {
                Storage::disk('public')->delete($video->archivo_path);
            }

            $video->delete(); // Esto borra el registro y los adjuntos en cascada (si la migración está bien)
            return back()->with('success', 'Video y adjuntos eliminados.');
        }
        catch (\Exception $e) {
            \Log::error('Error eliminando video de capacitación: ' . $e->getMessage());
            return back()->with('error', 'Error al eliminar el video: ' . $e->getMessage());
        }
    }

    // Eliminar solo un documento adjunto
    public function destroyAdjunto($id)
    {
        try {
            $adjunto = CapacitacionAdjunto::findOrFail($id);

            if (!empty($adjunto->archivo_path) && Storage::disk('public')->exists($adjunto->archivo_path)) {
                Storage::disk('public')->delete($adjunto->archivo_path);
            }

            $adjunto->delete();
            return back()->with('success', 'Documento eliminado.');
        }
        catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar el documento: ' . $e->getMessage());
        }
    }
}