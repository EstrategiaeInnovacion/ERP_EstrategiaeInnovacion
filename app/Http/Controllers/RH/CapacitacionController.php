<?php

namespace App\Http\Controllers\RH;

use App\Http\Controllers\Controller;
use App\Models\Capacitacion;
use App\Models\CapacitacionAdjunto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CapacitacionController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $videos = Capacitacion::where('activo', true)->orderBy('created_at', 'desc')->get();

        $filteredVideos = $videos->filter(fn ($v) => $v->isVisibleFor($user));
        $groupedVideos  = $filteredVideos->groupBy(fn ($item) => $item->categoria ?: 'General');

        return view('Recursos_Humanos.capacitacion.index', compact('groupedVideos'));
    }

    public function show($id)
    {
        $video = Capacitacion::with('adjuntos')->findOrFail($id);

        if (! $video->isVisibleFor(Auth::user())) {
            abort(403, 'No tienes permiso para ver esta capacitación.');
        }

        return view('Recursos_Humanos.capacitacion.show', compact('video'));
    }

    public function manage()
    {
        $videos   = Capacitacion::orderBy('created_at', 'desc')->get();
        $puestos  = \App\Models\Empleado::distinct()->pluck('posicion')->filter()->sort()->values();
        $usuarios = \App\Models\User::orderBy('name')->get(['id', 'name']);

        return view('Recursos_Humanos.capacitacion.manage', compact('videos', 'puestos', 'usuarios'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'titulo'               => 'required|string|max:255',
            'descripcion'          => 'nullable|string',
            'categoria'            => 'nullable|string|max:255',
            'puestos_permitidos'   => 'nullable|array',
            'puestos_permitidos.*' => 'string',
            'usuarios_permitidos'  => 'nullable|array',
            'usuarios_permitidos.*'=> 'integer',
            'youtube_url'          => 'nullable|url',
            'video'                => 'nullable|mimes:mp4,mov,ogg,qt|max:200000',
            'adjuntos.*'           => 'nullable|file|max:10240',
        ]);

        try {
            $archivoContenido = null;
            $archivoMime      = null;

            if ($request->hasFile('video')) {
                $file             = $request->file('video');
                $archivoContenido = $file->get();
                $archivoMime      = $file->getMimeType();
            }

            $capacitacion = Capacitacion::create([
                'titulo'             => $request->titulo,
                'descripcion'        => $request->descripcion,
                'categoria'          => $request->categoria,
                'puestos_permitidos' => $request->puestos_permitidos,
                'usuarios_permitidos'=> $request->usuarios_permitidos,
                'archivo_path'       => null,
                'archivo_contenido'  => $archivoContenido,
                'archivo_mime_type'  => $archivoMime,
                'youtube_url'        => $request->youtube_url,
                'subido_por'         => Auth::id(),
            ]);

            if ($request->hasFile('adjuntos')) {
                foreach ($request->file('adjuntos') as $archivo) {
                    $capacitacion->adjuntos()->create([
                        'titulo'            => $archivo->getClientOriginalName(),
                        'archivo_path'      => null,
                        'archivo_contenido' => $archivo->get(),
                        'archivo_mime_type' => $archivo->getMimeType(),
                    ]);
                }
            }

            return redirect()->route('rh.capacitacion.manage')
                ->with('success', 'Video subido correctamente.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al subir: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $video    = Capacitacion::with('adjuntos')->findOrFail($id);
        $puestos  = \App\Models\Empleado::distinct()->pluck('posicion')->filter()->sort()->values();
        $usuarios = \App\Models\User::orderBy('name')->get(['id', 'name']);

        return view('Recursos_Humanos.capacitacion.edit', compact('video', 'puestos', 'usuarios'));
    }

    public function update(Request $request, $id)
    {
        try {
            $video = Capacitacion::findOrFail($id);

            $request->validate([
                'titulo'               => 'required|string|max:255',
                'descripcion'          => 'nullable|string',
                'categoria'            => 'nullable|string',
                'puestos_permitidos'   => 'nullable|array',
                'puestos_permitidos.*' => 'string',
                'usuarios_permitidos'  => 'nullable|array',
                'usuarios_permitidos.*'=> 'integer',
                'youtube_url'          => 'nullable|url',
                'video'                => 'nullable|mimes:mp4,mov,ogg,qt|max:200000',
                'adjuntos.*'           => 'nullable|file|max:10240',
            ]);

            $video->update([
                'titulo'             => $request->titulo,
                'descripcion'        => $request->descripcion,
                'categoria'          => $request->categoria,
                'puestos_permitidos' => $request->puestos_permitidos,
                'usuarios_permitidos'=> $request->usuarios_permitidos,
                'youtube_url'        => $request->youtube_url,
            ]);

            if ($request->hasFile('video')) {
                $this->limpiarArchivoPublico($video->archivo_path);
                $file = $request->file('video');
                $video->archivo_contenido = $file->get();
                $video->archivo_mime_type = $file->getMimeType();
                $video->archivo_path      = null;
                $video->youtube_url       = null;
                $video->save();
            } elseif ($request->youtube_url) {
                $this->limpiarArchivoPublico($video->archivo_path);
                $video->archivo_contenido = null;
                $video->archivo_mime_type = null;
                $video->archivo_path      = null;
                $video->save();
            }

            if ($request->hasFile('adjuntos')) {
                foreach ($request->file('adjuntos') as $archivo) {
                    $video->adjuntos()->create([
                        'titulo'            => $archivo->getClientOriginalName(),
                        'archivo_path'      => null,
                        'archivo_contenido' => $archivo->get(),
                        'archivo_mime_type' => $archivo->getMimeType(),
                    ]);
                }
            }

            return redirect()->route('rh.capacitacion.manage')
                ->with('success', 'Capacitación actualizada.');
        } catch (\Exception $e) {
            Log::error('Error actualizando capacitación: ' . $e->getMessage());

            return back()->with('error', 'Error al actualizar: ' . $e->getMessage())->withInput();
        }
    }

    public function destroy($id)
    {
        try {
            $video = Capacitacion::withoutGlobalScope('sin_contenido')->findOrFail($id);

            foreach ($video->adjuntos as $adjunto) {
                $this->limpiarArchivoPublico($adjunto->archivo_path);
            }

            $this->limpiarArchivoPublico($video->archivo_path);
            $video->delete();

            return back()->with('success', 'Video y adjuntos eliminados.');
        } catch (\Exception $e) {
            Log::error('Error eliminando video de capacitación: ' . $e->getMessage());

            return back()->with('error', 'Error al eliminar el video: ' . $e->getMessage());
        }
    }

    public function destroyAdjunto($id)
    {
        try {
            $adjunto = CapacitacionAdjunto::withoutGlobalScope('sin_contenido')->findOrFail($id);
            $this->limpiarArchivoPublico($adjunto->archivo_path);
            $adjunto->delete();

            return back()->with('success', 'Documento eliminado.');
        } catch (\Exception $e) {
            return back()->with('error', 'Error al eliminar el documento: ' . $e->getMessage());
        }
    }

    /**
     * GET /capacitacion/ver/{id}/video
     * Sirve el video desde la BD; cae en disco para registros migrados.
     */
    public function streamVideo($id)
    {
        $video = Capacitacion::withoutGlobalScope('sin_contenido')
            ->select(['id', 'archivo_contenido', 'archivo_mime_type', 'archivo_path'])
            ->findOrFail($id);

        if (! empty($video->archivo_contenido)) {
            return $this->streamBinary(
                $video->archivo_contenido,
                $video->archivo_mime_type ?? 'video/mp4'
            );
        }

        if ($video->archivo_path && Storage::disk('public')->exists($video->archivo_path)) {
            return Storage::disk('public')->response($video->archivo_path);
        }

        abort(404, 'Video no encontrado.');
    }

    /**
     * GET /capacitacion/adjunto/{adjuntoId}/descargar
     * Descarga un adjunto desde la BD; cae en disco para registros migrados.
     */
    public function downloadAdjunto($adjuntoId)
    {
        $adjunto = CapacitacionAdjunto::withoutGlobalScope('sin_contenido')
            ->select(['id', 'titulo', 'archivo_contenido', 'archivo_mime_type', 'archivo_path'])
            ->findOrFail($adjuntoId);

        if (! empty($adjunto->archivo_contenido)) {
            $nombreDescarga = $adjunto->titulo ?: 'adjunto';
            $ext = $adjunto->archivo_mime_type ? $this->extFromMime($adjunto->archivo_mime_type) : '';
            if ($ext && ! str_ends_with($nombreDescarga, '.' . $ext)) {
                $nombreDescarga .= '.' . $ext;
            }

            return response($adjunto->archivo_contenido, 200, [
                'Content-Type'        => $adjunto->archivo_mime_type ?? 'application/octet-stream',
                'Content-Disposition' => 'attachment; filename="' . $nombreDescarga . '"',
            ]);
        }

        if ($adjunto->archivo_path && Storage::disk('public')->exists($adjunto->archivo_path)) {
            return Storage::disk('public')->download($adjunto->archivo_path, $adjunto->titulo);
        }

        abort(404, 'Archivo no encontrado.');
    }

    private function streamBinary(string $data, string $mime): \Illuminate\Http\Response
    {
        $size    = strlen($data);
        $headers = [
            'Content-Type'   => $mime,
            'Accept-Ranges'  => 'bytes',
            'Content-Length' => $size,
        ];

        if (request()->hasHeader('Range')) {
            preg_match('/bytes=(\d+)-(\d*)/', request()->header('Range'), $m);
            $start = (int) ($m[1] ?? 0);
            $end   = isset($m[2]) && $m[2] !== '' ? (int) $m[2] : $size - 1;
            $end   = min($end, $size - 1);
            $chunk = substr($data, $start, $end - $start + 1);

            return response($chunk, 206, array_merge($headers, [
                'Content-Range'  => "bytes $start-$end/$size",
                'Content-Length' => strlen($chunk),
            ]));
        }

        return response($data, 200, $headers);
    }

    private function limpiarArchivoPublico(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    private function extFromMime(string $mime): string
    {
        return match ($mime) {
            'video/mp4'                                                          => 'mp4',
            'video/quicktime'                                                    => 'mov',
            'application/pdf'                                                    => 'pdf',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/msword'                                                 => 'doc',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-excel'                                           => 'xls',
            'image/jpeg'                                                         => 'jpg',
            'image/png'                                                          => 'png',
            'image/gif'                                                          => 'gif',
            'image/webp'                                                         => 'webp',
            default                                                              => '',
        };
    }
}
