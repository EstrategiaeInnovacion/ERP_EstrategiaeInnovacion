<?php

namespace App\Http\Controllers\Sistemas_IT;

use App\Http\Controllers\Controller;
use App\Services\ActivosDbService;
use Illuminate\Http\Request;

class ActivosController extends Controller
{
    public function __construct(protected ActivosDbService $activos) {}

    /**
     * GET /admin/activos
     * Listado paginado de todos los activos con filtros de búsqueda, tipo y estado.
     */
    public function index(Request $request)
    {
        if (! $this->activos->isConfigured()) {
            return view('Sistemas_IT.admin.activos.index', [
                'dispositivos' => null,
                'stats'        => null,
                'search'       => null,
                'type'         => null,
                'status'       => null,
                'noConexion'   => true,
            ]);
        }

        $search = $request->input('search');
        $type   = $request->input('type');
        $status = $request->input('status');

        $dispositivos = $this->activos->getAllDevicesPaginated($search, $type, $status, 15);
        $stats        = $this->activos->getDeviceStats();

        return view('Sistemas_IT.admin.activos.index', compact(
            'dispositivos', 'stats', 'search', 'type', 'status'
        ));
    }

    /**
     * GET /admin/activos/{uuid}
     * Detalle de un dispositivo: datos, fotos, asignación activa e historial.
     */
    public function show(string $uuid)
    {
        if (! $this->activos->isConfigured()) {
            return redirect()->route('admin.activos.index')
                ->with('error', 'No se pudo conectar a la base de datos de activos.');
        }

        $dispositivo = $this->activos->getDeviceByUuid($uuid);

        if (! $dispositivo) {
            abort(404, 'Dispositivo no encontrado.');
        }

        $fotos      = $this->activos->getDevicePhotos($dispositivo->id);
        $historial  = $this->activos->getAssignmentHistory($dispositivo->id);
        $documentos = $this->activos->getDeviceDocuments($dispositivo->id);

        return view('Sistemas_IT.admin.activos.show', compact(
            'dispositivo', 'fotos', 'historial', 'documentos'
        ));
    }
}
