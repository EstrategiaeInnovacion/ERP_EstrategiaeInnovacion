<?php

namespace App\Http\Controllers\Sistemas_IT;

use App\Http\Controllers\Controller;
use App\Models\Empleado;
use App\Services\ActivosDbService;
use Illuminate\Http\Request;

class ActivosController extends Controller
{
    public function __construct(protected ActivosDbService $activos) {}

    /**
     * GET /admin/activos
     * Listado paginado con filtros de búsqueda, tipo y estado.
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
                'soloLectura'  => request()->routeIs('rh.inventario.*'),
            ]);
        }

        $search = $request->input('search');
        $type   = $request->input('type');
        // Por defecto mostrar solo los disponibles; pasar ?status= (vacío) para ver todos
        $status = $request->input('status', 'available');
        if ($status === '') {
            $status = null;
        }

        $dispositivos = $this->activos->getAllDevicesPaginated($search, $type, $status, 15);
        $stats        = $this->activos->getDeviceStats();
        $soloLectura  = request()->routeIs('rh.inventario.*');

        return view('Sistemas_IT.admin.activos.index', compact(
            'dispositivos', 'stats', 'search', 'type', 'status', 'soloLectura'
        ));
    }

    /**
     * GET /admin/activos/escaner-qr
     * Página del escáner QR para asignar / devolver / prestar dispositivos.
     */
    public function qrScanner()
    {
        if (! $this->activos->isConfigured()) {
            return redirect()->route('admin.activos.index')
                ->with('error', 'No se pudo conectar a la base de datos de activos.');
        }

        $empleados = Empleado::where('es_activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'id_empleado', 'area', 'posicion']);

        return view('Sistemas_IT.admin.activos.qr-scanner', compact('empleados'));
    }

    /**
     * GET /admin/activos/crear
     */
    public function create()
    {
        if (! $this->activos->isConfigured()) {
            return redirect()->route('admin.activos.index')
                ->with('error', 'No se pudo conectar a la base de datos de activos.');
        }

        return view('Sistemas_IT.admin.activos.create');
    }

    /**
     * POST /admin/activos
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'brand'               => 'nullable|string|max:255',
            'model'               => 'nullable|string|max:255',
            'serial_number'       => 'required|string|max:255',
            'type'                => 'required|in:computer,peripheral,printer,other',
            'status'              => 'required|in:available,assigned,maintenance,broken',
            'purchase_date'       => 'nullable|date',
            'warranty_expiration' => 'nullable|date',
            'notes'               => 'nullable|string|max:2000',
            'cred_username'       => 'nullable|string|max:255',
            'cred_password'       => 'nullable|string|max:255',
            'cred_email'          => 'nullable|email|max:255',
            'cred_email_password' => 'nullable|string|max:255',
            'photos'              => 'nullable|array|max:5',
            'photos.*'            => 'image|mimes:jpg,jpeg,png,webp,gif|max:8192',
        ]);

        $uuid = $this->activos->createDevice($data);

        if (! $uuid) {
            return back()->withInput()
                ->with('error', 'No se pudo registrar el dispositivo. Intenta de nuevo.');
        }

        // Guardar fotos subidas desde el formulario de creación
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $foto) {
                if ($foto->isValid()) {
                    $filename = $uuid . '-' . uniqid() . '.' . $foto->getClientOriginalExtension();
                    $filePath = $foto->storeAs('activos-fotos', $filename, 'local');
                    $this->activos->addDevicePhoto($uuid, $filePath);
                }
            }
        }

        return redirect()->route('admin.activos.show', $uuid)
            ->with('success', 'Dispositivo registrado correctamente.');
    }

    /**
     * GET /admin/activos/{uuid}
     * Detalle: datos, fotos, asignación activa, historial, documentos y credenciales.
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
        $credencial = $this->activos->getDeviceCredential($dispositivo->id);
        $empleados  = Empleado::where('es_activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'id_empleado', 'area', 'posicion']);

        $soloLectura = request()->routeIs('rh.inventario.*');

        return view('Sistemas_IT.admin.activos.show', compact(
            'dispositivo', 'fotos', 'historial', 'documentos', 'credencial', 'empleados', 'soloLectura'
        ));
    }

    /**
     * GET /admin/activos/{uuid}/editar
     */
    public function edit(string $uuid)
    {
        if (! $this->activos->isConfigured()) {
            return redirect()->route('admin.activos.index')
                ->with('error', 'No se pudo conectar a la base de datos de activos.');
        }

        $dispositivo = $this->activos->getDeviceByUuid($uuid);

        if (! $dispositivo) {
            abort(404, 'Dispositivo no encontrado.');
        }

        $credencial = $this->activos->getDeviceCredential($dispositivo->id);
        $fotos      = $this->activos->getDevicePhotos($dispositivo->id);

        return view('Sistemas_IT.admin.activos.edit', compact('dispositivo', 'credencial', 'fotos'));
    }

    /**
     * PUT /admin/activos/{uuid}
     */
    public function update(Request $request, string $uuid)
    {
        $data = $request->validate([
            'name'                => 'required|string|max:255',
            'brand'               => 'nullable|string|max:255',
            'model'               => 'nullable|string|max:255',
            'serial_number'       => 'required|string|max:255',
            'type'                => 'required|in:computer,peripheral,printer,other',
            'status'              => 'required|in:available,assigned,maintenance,broken',
            'purchase_date'       => 'nullable|date',
            'warranty_expiration' => 'nullable|date',
            'notes'               => 'nullable|string|max:2000',
            'cred_username'       => 'nullable|string|max:255',
            'cred_password'       => 'nullable|string|max:255',
            'cred_email'          => 'nullable|email|max:255',
            'cred_email_password' => 'nullable|string|max:255',
            'photos'              => 'nullable|array|max:5',
            'photos.*'            => 'image|mimes:jpg,jpeg,png,webp,gif|max:8192',
        ]);

        $ok = $this->activos->updateDevice($uuid, $data);

        if (! $ok) {
            return back()->withInput()
                ->with('error', 'No se pudo actualizar el dispositivo. Intenta de nuevo.');
        }

        // Guardar fotos nuevas
        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $foto) {
                if ($foto->isValid()) {
                    $filename = $uuid . '-' . uniqid() . '.' . $foto->getClientOriginalExtension();
                    $filePath = $foto->storeAs('activos-fotos', $filename, 'local');
                    $this->activos->addDevicePhoto($uuid, $filePath);
                }
            }
        }

        return redirect()->route('admin.activos.show', $uuid)
            ->with('success', 'Dispositivo actualizado correctamente.');
    }

    /**
     * POST /admin/activos/{uuid}/asignar
     * Asigna el dispositivo a un empleado del ERP.
     */
    public function assign(Request $request, string $uuid)
    {
        $request->validate([
            'empleado_id' => 'required|exists:empleados,id',
            'notes'       => 'nullable|string|max:1000',
        ]);

        $empleado = Empleado::findOrFail($request->empleado_id);

        $ok = $this->activos->assignDeviceInActivos(
            uuid:       $uuid,
            assignedTo: $empleado->nombre,
            badge:      $empleado->id_empleado ?: null,
            notes:      $request->input('notes')
        );

        if (! $ok) {
            return back()->with('error', 'No se pudo registrar la asignación. Verifica que el dispositivo exista en la base de activos.');
        }

        return redirect()->route('admin.activos.show', $uuid)
            ->with('success', "Dispositivo asignado a {$empleado->nombre}.");
    }

    /**
     * POST /admin/activos/{uuid}/devolver
     * Cierra la asignación activa del dispositivo.
     */
    public function returnDevice(string $uuid)
    {
        $ok = $this->activos->returnDeviceInActivos($uuid);

        if (! $ok) {
            return back()->with('error', 'No se pudo registrar la devolución. Verifica que el dispositivo tenga una asignación activa.');
        }

        return redirect()->route('admin.activos.show', $uuid)
            ->with('success', 'Dispositivo devuelto y marcado como disponible.');
    }
}