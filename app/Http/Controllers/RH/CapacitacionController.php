<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\Capacitacion;
use App\Models\CapacitacionAdjunto; // Asegúrate de tener este modelo creado
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class CapacitacionController extends Controller
{
    // --- VISTAS PÚBLICAS (EMPLEADOS) ---

    // Vista para TODOS los empleados (Galería)
    public function index()
    {
        $videos = Capacitacion::where('activo', true)
            ->orderBy('created_at', 'desc')
            ->paginate(9); // Usamos paginación para evitar carga lenta
            
        return view('Recursos_Humanos.capacitacion.index', compact('videos'));
    }

    // Vista para VER un video específico
    public function show($id)
    {
        $video = Capacitacion::with('adjuntos')->findOrFail($id);
        return view('Recursos_Humanos.capacitacion.show', compact('video'));
    }

    // --- ÁREA DE ADMINISTRACIÓN (SOLO RH) ---

    // Panel de gestión (Aquí estaba el error, esta función faltaba)
    public function manage()
    {
        $videos = Capacitacion::orderBy('created_at', 'desc')->get();
        return view('Recursos_Humanos.capacitacion.manage', compact('videos'));
    }

    // Guardar nuevo video
    public function store(Request $request)
    {
        $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'video' => 'required|mimes:mp4,mov,ogg,qt|max:200000', // 200MB
            'adjuntos.*' => 'nullable|file|max:10240' // 10MB por adjunto
        ]);

        try {
            // Guardar video
            $path = $request->file('video')->store('capacitacion', 'public');

            $capacitacion = Capacitacion::create([
                'titulo' => $request->titulo,
                'descripcion' => $request->descripcion,
                'archivo_path' => $path,
                'subido_por' => Auth::id(),
            ]);

            // Guardar adjuntos si existen
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
        } catch (\Exception $e) {
            return back()->with('error', 'Error al subir: ' . $e->getMessage());
        }
    }

    // --- NUEVAS FUNCIONES PARA EDITAR ---

    // Vista de edición
    public function edit($id)
    {
        $video = Capacitacion::with('adjuntos')->findOrFail($id);
        return view('Recursos_Humanos.capacitacion.edit', compact('video'));
    }

    // Actualizar video y documentos
    public function update(Request $request, $id)
    {
        $video = Capacitacion::findOrFail($id);

        $request->validate([
            'titulo' => 'required|string|max:255',
            'descripcion' => 'nullable|string',
            'video' => 'nullable|mimes:mp4,mov,ogg,qt|max:200000',
            'adjuntos.*' => 'nullable|file|max:10240'
        ]);

        // 1. Actualizar textos
        $video->update([
            'titulo' => $request->titulo,
            'descripcion' => $request->descripcion,
        ]);

        // 2. Reemplazar video (solo si se subió uno nuevo)
        if ($request->hasFile('video')) {
            // Borrar viejo
            if (Storage::disk('public')->exists($video->archivo_path)) {
                Storage::disk('public')->delete($video->archivo_path);
            }
            // Subir nuevo
            $video->archivo_path = $request->file('video')->store('capacitacion', 'public');
            $video->save();
        }

        // 3. Agregar nuevos adjuntos
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

    // Eliminar video completo
    public function destroy($id)
    {
        $video = Capacitacion::findOrFail($id);
        
        // Laravel borra los adjuntos de la BD automáticamente si configuraste cascade, 
        // pero limpiamos los archivos físicos de los adjuntos primero:
        foreach($video->adjuntos as $adjunto) {
            if (Storage::disk('public')->exists($adjunto->archivo_path)) {
                Storage::disk('public')->delete($adjunto->archivo_path);
            }
        }

        // Eliminar archivo de video físico
        if (Storage::disk('public')->exists($video->archivo_path)) {
            Storage::disk('public')->delete($video->archivo_path);
        }
        
        $video->delete(); // Esto borra el registro y los adjuntos en cascada (si la migración está bien)
        return back()->with('success', 'Video y adjuntos eliminados.');
    }

    // Eliminar solo un documento adjunto
    public function destroyAdjunto($id)
    {
        $adjunto = CapacitacionAdjunto::findOrFail($id);
        
        if (Storage::disk('public')->exists($adjunto->archivo_path)) {
            Storage::disk('public')->delete($adjunto->archivo_path);
        }
        
        $adjunto->delete();
        return back()->with('success', 'Documento eliminado.');
    }
}