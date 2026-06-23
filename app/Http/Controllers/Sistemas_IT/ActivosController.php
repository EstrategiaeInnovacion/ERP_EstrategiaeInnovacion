<?php

namespace App\Http\Controllers\Sistemas_IT;

use App\Http\Controllers\Controller;
use App\Models\Empleado;
use App\Models\Sistemas_IT\EquipoAsignado;
use App\Models\Sistemas_IT\EquipoPeriferico;
use App\Services\ActivosDbService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Color;
use PhpOffice\PhpSpreadsheet\Style\Fill;

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

        $dispositivos    = $this->activos->getAllDevicesPaginated($search, $type, $status, 15);
        $stats           = $this->activos->getDeviceStats();
        $todasEtiquetas  = $this->activos->getAllDevicesForPrint();
        $soloLectura     = request()->routeIs('rh.inventario.*');

        return view('Sistemas_IT.admin.activos.index', compact(
            'dispositivos', 'stats', 'search', 'type', 'status', 'soloLectura', 'todasEtiquetas'
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
            'type'                => 'required|in:computer,peripheral,printer,mobiliario,phone,other',
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

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $foto) {
                if ($foto->isValid()) {
                    $filename = $uuid . '-' . uniqid() . '.' . $foto->getClientOriginalExtension();
                    $this->activos->addDevicePhoto(
                        uuid:     $uuid,
                        filePath: 'activos-fotos/' . $filename,
                        fileData: $foto->get(),
                        mimeType: $foto->getMimeType(),
                    );
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
            'type'                => 'required|in:computer,peripheral,printer,mobiliario,phone,other',
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

        if ($request->hasFile('photos')) {
            foreach ($request->file('photos') as $foto) {
                if ($foto->isValid()) {
                    $filename = $uuid . '-' . uniqid() . '.' . $foto->getClientOriginalExtension();
                    $this->activos->addDevicePhoto(
                        uuid:     $uuid,
                        filePath: 'activos-fotos/' . $filename,
                        fileData: $foto->get(),
                        mimeType: $foto->getMimeType(),
                    );
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

        $this->limpiarRegistroErp($uuid);

        return redirect()->route('admin.activos.show', $uuid)
            ->with('success', 'Dispositivo devuelto y marcado como disponible.');
    }

    // Si el dispositivo devuelto en Activos tenía registro en "Contraseñas y Equipos"
    // (como equipo principal o como periférico), lo elimina para que no quede
    // mostrándose ahí como si siguiera asignado.
    private function limpiarRegistroErp(string $uuid): void
    {
        $equipo = EquipoAsignado::where('uuid_activos', $uuid)->first();
        if ($equipo) {
            $equipo->delete();
            return;
        }

        EquipoPeriferico::where('uuid_activos', $uuid)->delete();
    }

    /**
     * DELETE /admin/activos/{uuid}
     * Elimina permanentemente un activo dañado.
     */
    public function destroy(string $uuid)
    {
        $dispositivo = $this->activos->getDeviceByUuid($uuid);

        if (! $dispositivo) {
            return redirect()->route('admin.activos.index')
                ->with('error', 'Dispositivo no encontrado.');
        }

        if ($dispositivo->status !== 'broken') {
            return back()->with('error', 'Solo se pueden eliminar dispositivos marcados como Dañado.');
        }

        $ok = $this->activos->deleteDevice($uuid);

        if (! $ok) {
            return back()->with('error', 'No se pudo eliminar el dispositivo. Intenta de nuevo.');
        }

        return redirect()->route('admin.activos.index', ['status' => 'broken'])
            ->with('success', 'Dispositivo eliminado correctamente.');
    }

    /**
     * PATCH /admin/activos/{uuid}/credenciales
     * Actualiza username, password, email y email_password en Activos,
     * y sincroniza nombre_usuario_pc y correo en el ERP.
     */
    public function updateCredencial(Request $request, string $uuid)
    {
        $request->validate([
            'username'       => 'nullable|string|max:255',
            'password'       => 'nullable|string',
            'email'          => 'nullable|email|max:255',
            'email_password' => 'nullable|string',
        ]);

        if (! $this->activos->isConfigured()) {
            return response()->json(['success' => false, 'message' => 'BD de Activos no disponible.'], 503);
        }

        // Actualizar en Activos
        $ok = $this->activos->upsertCredentialByUuid(
            $uuid,
            $request->username ?: null,
            $request->filled('password')       ? $request->password       : null,
            $request->email ?: null,
            $request->filled('email_password') ? $request->email_password : null,
        );

        if (! $ok) {
            return response()->json(['success' => false, 'message' => 'No se pudo actualizar en Activos.'], 500);
        }

        // Sincronizar con ERP (nombre_usuario_pc y correo)
        $equipoErp = DB::table('it_equipos_asignados')
            ->where('uuid_activos', $uuid)
            ->first(['id', 'nombre_usuario_pc']);

        if ($equipoErp) {
            if ($request->filled('username')) {
                DB::table('it_equipos_asignados')
                    ->where('id', $equipoErp->id)
                    ->update(['nombre_usuario_pc' => $request->username, 'updated_at' => now()]);
            }

            if ($request->filled('email')) {
                $correoExistente = DB::table('it_equipos_correos')
                    ->where('equipo_asignado_id', $equipoErp->id)
                    ->orderBy('id')
                    ->first(['id']);

                if ($correoExistente) {
                    DB::table('it_equipos_correos')
                        ->where('id', $correoExistente->id)
                        ->update(['correo' => $request->email, 'updated_at' => now()]);
                } else {
                    DB::table('it_equipos_correos')->insert([
                        'equipo_asignado_id' => $equipoErp->id,
                        'correo'             => $request->email,
                        'created_at'         => now(),
                        'updated_at'         => now(),
                    ]);
                }
            }
        }

        return response()->json(['success' => true, 'message' => 'Credenciales actualizadas correctamente.']);
    }

    /**
     * GET /admin/activos/exportar-excel
     * Genera un .xlsx con una hoja por categoría de dispositivo.
     * Incluye disponibles, asignados y en mantenimiento (excluye dañados).
     */
    public function exportExcel()
    {
        if (! $this->activos->isConfigured()) {
            return back()->with('error', 'No se pudo conectar a la base de datos de activos.');
        }

        $grupos = $this->activos->getAllDevicesForExcel();

        // Configuración de cada categoría: etiqueta, color de encabezado (ARGB)
        $categorias = [
            'computer'   => ['label' => 'Computadoras',  'color' => 'FF7C3AED'],  // violeta
            'peripheral' => ['label' => 'Periféricos',   'color' => 'FF0EA5E9'],  // azul cielo
            'printer'    => ['label' => 'Impresoras',    'color' => 'FF0284C7'],  // azul
            'mobiliario' => ['label' => 'Mobiliario',    'color' => 'FFD97706'],  // ámbar
            'phone'      => ['label' => 'Teléfonos',     'color' => 'FF059669'],  // esmeralda
            'other'      => ['label' => 'Otros',         'color' => 'FF475569'],  // slate
        ];

        $estadoLabels = [
            'available'   => 'Disponible',
            'assigned'    => 'Asignado',
            'maintenance' => 'Mantenimiento',
            'broken'      => 'Dañado',
        ];

        $headers = [
            'Nombre', 'Marca', 'Modelo', 'N° Serie',
            'Estado', 'Asignado a', 'Fecha Asignación',
            'Fecha Compra', 'Garantía hasta', 'Notas',
        ];

        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0); // Quitar hoja vacía inicial

        foreach ($categorias as $slug => $meta) {
            $items = $grupos[$slug] ?? [];

            $sheet = $spreadsheet->createSheet();
            $sheet->setTitle($meta['label']);

            // ── Fila de título de la hoja ──────────────────────────────
            $lastCol = 'J'; // columna J = 10 columnas
            $sheet->mergeCells("A1:{$lastCol}1");
            $sheet->setCellValue('A1', '📋 Inventario IT — ' . $meta['label']);
            $sheet->getStyle('A1')->applyFromArray([
                'font'      => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $meta['color']]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getRowDimension(1)->setRowHeight(30);

            // ── Fila de fecha de exportación ───────────────────────────
            $sheet->mergeCells("A2:{$lastCol}2");
            $sheet->setCellValue('A2', 'Exportado: ' . now()->format('d/m/Y H:i') . '  |  Total: ' . count($items) . ' dispositivo(s)');
            $sheet->getStyle('A2')->applyFromArray([
                'font'      => ['italic' => true, 'size' => 10, 'color' => ['argb' => 'FF64748B']],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            ]);

            // ── Encabezados de columnas (fila 3) ──────────────────────
            $col = 'A';
            foreach ($headers as $h) {
                $sheet->setCellValue("{$col}3", $h);
                $col++;
            }
            $sheet->getStyle('A3:J3')->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $meta['color']]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE2E8F0']]],
            ]);
            $sheet->getRowDimension(3)->setRowHeight(20);

            // ── Filas de datos ─────────────────────────────────────────
            $row = 4;
            foreach ($items as $d) {
                $asignado = $d->employee_name ?? $d->assigned_to ?? '—';
                $estado   = $estadoLabels[$d->status] ?? $d->status;

                $sheet->setCellValue("A{$row}", $d->name ?? '');
                $sheet->setCellValue("B{$row}", $d->brand ?? '');
                $sheet->setCellValue("C{$row}", $d->model ?? '');
                $sheet->setCellValue("D{$row}", $d->serial_number ?? '');
                $sheet->setCellValue("E{$row}", $estado);
                $sheet->setCellValue("F{$row}", $asignado);
                $sheet->setCellValue("G{$row}", $d->assigned_at ? \Carbon\Carbon::parse($d->assigned_at)->format('d/m/Y') : '');
                $sheet->setCellValue("H{$row}", $d->purchase_date ? \Carbon\Carbon::parse($d->purchase_date)->format('d/m/Y') : '');
                $sheet->setCellValue("I{$row}", $d->warranty_expiration ? \Carbon\Carbon::parse($d->warranty_expiration)->format('d/m/Y') : '');
                $sheet->setCellValue("J{$row}", $d->notes ?? '');

                // Alternar color de fila para mejor legibilidad
                $bgColor = ($row % 2 === 0) ? 'FFF8FAFC' : 'FFFFFFFF';
                $sheet->getStyle("A{$row}:J{$row}")->applyFromArray([
                    'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgColor]],
                    'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE2E8F0']]],
                    'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
                ]);

                // Colorear la celda de estado
                $statusColor = match($d->status) {
                    'available'   => 'FFD1FAE5', // verde claro
                    'assigned'    => 'FFE0F2FE', // azul claro
                    'maintenance' => 'FFFEF3C7', // ámbar claro
                    default       => 'FFFEE2E2', // rojo claro
                };
                $sheet->getStyle("E{$row}")->applyFromArray([
                    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $statusColor]],
                    'font' => ['bold' => true],
                ]);

                $row++;
            }

            // ── Auto-ajuste de ancho de columnas ──────────────────────
            foreach (range('A', 'J') as $colLetter) {
                $sheet->getColumnDimension($colLetter)->setAutoSize(true);
            }
            // Limitar columna de notas para que no se extienda demasiado
            $sheet->getColumnDimension('J')->setAutoSize(false);
            $sheet->getColumnDimension('J')->setWidth(40);

            // Fijar las primeras 3 filas (título + fecha + encabezados)
            $sheet->freezePane('A4');
        }

        // Si no hay ninguna hoja con datos, agregar al menos una vacía
        if ($spreadsheet->getSheetCount() === 0) {
            $spreadsheet->createSheet()->setTitle('Sin datos');
        }

        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'Inventario_IT_' . now()->format('Y-m-d_His') . '.xlsx';

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control'       => 'max-age=0',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }
}