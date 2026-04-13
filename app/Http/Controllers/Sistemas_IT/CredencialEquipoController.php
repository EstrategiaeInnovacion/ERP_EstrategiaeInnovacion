<?php

namespace App\Http\Controllers\Sistemas_IT;

use App\Http\Controllers\Controller;
use App\Models\EmpleadoDocumento;
use App\Models\Sistemas_IT\EquipoAsignado;
use App\Models\User;
use App\Services\ActivosDbService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class CredencialEquipoController extends Controller
{
    public function __construct(protected ActivosDbService $activos) {}

    public function index(Request $request)
    {
        $query = EquipoAsignado::with(['user', 'correos', 'perifericos'])
            ->where(function ($q) {
                $q->where('es_principal', true)->orWhereNull('es_principal');
            })
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nombre_equipo', 'like', "%{$search}%")
                    ->orWhere('modelo', 'like', "%{$search}%")
                    ->orWhere('numero_serie', 'like', "%{$search}%")
                    ->orWhere('nombre_usuario_pc', 'like', "%{$search}%")
                    ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"))
                    ->orWhereIn('user_id',
                        EquipoAsignado::where('es_principal', false)
                            ->where(function ($sq) use ($search) {
                                $sq->where('nombre_equipo', 'like', "%{$search}%")
                                   ->orWhere('modelo', 'like', "%{$search}%")
                                   ->orWhere('numero_serie', 'like', "%{$search}%");
                            })
                            ->pluck('user_id')
                    );
            });
        }

        $equipos = $query->paginate(15)->withQueryString();

        // Cargar equipos secundarios para los usuarios en la página actual
        $userIds = $equipos->pluck('user_id')->filter()->unique();
        $secundarios = EquipoAsignado::with(['correos', 'perifericos'])
            ->whereIn('user_id', $userIds)
            ->where('es_principal', false)
            ->orderBy('created_at')
            ->get()
            ->groupBy('user_id');

        $usuarios = User::where('status', 'approved')->orderBy('name')->get(['id', 'name', 'email']);
        $soloLectura = request()->routeIs('rh.activos.*');

        // IDs de usuarios que YA tienen carta responsiva guardada en su expediente
        $usersConCarta = \App\Models\Empleado::whereHas('documentos', function ($q) {
                $q->where('categoria', 'Sistema IT')
                  ->where('nombre', 'like', 'Carta Responsiva IT%');
            })
            ->join('users', 'users.id', '=', 'empleados.user_id')
            ->pluck('users.id')
            ->flip()
            ->all();

        return view('admin.credenciales.index', compact('equipos', 'usuarios', 'secundarios', 'soloLectura', 'usersConCarta'));
    }

    public function create()
    {
        return redirect()->route('admin.credenciales.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'assign_new' => 'sometimes|boolean',
            'uuid_activos' => 'required|string|max:255|unique:it_equipos_asignados,uuid_activos',
            'nombre_equipo' => 'required|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'numero_serie' => 'nullable|string|max:255',
            'photo_id' => 'nullable|integer',
            'nombre_usuario_pc' => 'required|string|max:255',
            'contrasena_equipo' => 'required|string',
            'notas' => 'nullable|string',
            'correos' => 'sometimes|array',
            'correos.*.correo' => 'required_with:correos.*|email|max:255',
            'correos.*.contrasena_correo' => 'nullable|string',
            'perifericos' => 'sometimes|array',
            'perifericos.*.uuid' => 'required_with:perifericos.*|string',
            'perifericos.*.nombre' => 'required_with:perifericos.*|string|max:255',
            'perifericos.*.tipo' => 'nullable|string|max:255',
            'perifericos.*.serie' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $user = User::findOrFail($request->user_id);

            $equipo = EquipoAsignado::create([
                'user_id' => $request->user_id,
                'uuid_activos' => $request->uuid_activos,
                'nombre_equipo' => $request->nombre_equipo,
                'modelo' => $request->modelo,
                'numero_serie' => $request->numero_serie,
                'photo_id' => $request->photo_id,
                'nombre_usuario_pc' => $request->nombre_usuario_pc,
                'contrasena_equipo' => $request->contrasena_equipo,
                'notas' => $request->notas,
            ]);

            // Correos
            foreach (($request->correos ?? []) as $correoData) {
                if (! empty($correoData['correo'])) {
                    $equipo->correos()->create([
                        'correo' => $correoData['correo'],
                        'contrasena_correo' => $correoData['contrasena_correo'] ?? null,
                    ]);
                }
            }

            // Periféricos
            foreach (($request->perifericos ?? []) as $per) {
                $equipo->perifericos()->create([
                    'uuid_activos' => $per['uuid'],
                    'nombre' => $per['nombre'],
                    'tipo' => $per['tipo'] ?? null,
                    'numero_serie' => $per['serie'] ?? null,
                ]);
            }

            DB::commit();

            // ── Sincronizar con AuditoriaActivos ──────────────────────────────
            $empleado = $user->empleado;
            $badge = $empleado?->id_empleado ?: null;
            $assignedTo = $empleado?->nombre ?? $user->name;

            // Equipo principal: solo cuando se seleccionó de los disponibles
            if ($request->boolean('assign_new')) {
                $this->activos->assignDeviceInActivos(
                    $request->uuid_activos,
                    $assignedTo,
                    $badge,
                    $request->notas
                );
            }

            // Periféricos: siempre se marcan asignados — siempre se eligen de disponibles
            foreach (($request->perifericos ?? []) as $per) {
                if (! empty($per['uuid'])) {
                    $this->activos->assignDeviceInActivos($per['uuid'], $assignedTo, $badge);
                }
            }
            // ─────────────────────────────────────────────────────────────────

            return response()->json([
                'success' => true,
                'message' => 'Registro creado correctamente.',
                'redirect' => route('admin.credenciales.show', $equipo),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CredencialEquipo store error: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error interno al guardar.'], 500);
        }
    }

    public function show(EquipoAsignado $credencial)
    {
        $credencial->load(['user', 'correos', 'perifericos']);
        $equiposSecundarios = EquipoAsignado::where('user_id', $credencial->user_id)
            ->where('es_principal', false)
            ->with(['correos', 'perifericos'])
            ->orderBy('created_at')
            ->get();

        // Determinar si el activo principal es una computadora.
        // Si no se puede consultar activos, se asume computadora para no romper registros existentes.
        $esComputadora = true;
        if ($this->activos->isConfigured()) {
            $deviceType = $this->activos->getDeviceTypeByUuid($credencial->uuid_activos);
            $esComputadora = ($deviceType === 'computer' || $deviceType === null);
        }

        $soloLectura = request()->routeIs('rh.activos.*');

        return view('admin.credenciales.show', compact('credencial', 'equiposSecundarios', 'esComputadora', 'soloLectura'));
    }

    public function edit(EquipoAsignado $credencial)
    {
        return redirect()->route('admin.credenciales.show', $credencial);
    }

    public function update(Request $request, EquipoAsignado $credencial)
    {
        $request->validate([
            'nombre_equipo' => 'required|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'numero_serie' => 'nullable|string|max:255',
            'nombre_usuario_pc' => 'required|string|max:255',
            'contrasena_equipo' => 'nullable|string',
            'notas' => 'nullable|string',
            'correos' => 'sometimes|array',
            'correos.*.id' => 'nullable|integer',
            'correos.*.correo' => 'required_with:correos.*|email|max:255',
            'correos.*.contrasena_correo' => 'nullable|string',
            'perifericos' => 'sometimes|array',
            'perifericos.*.id' => 'nullable|integer',
            'perifericos.*.uuid' => 'required_with:perifericos.*|string',
            'perifericos.*.nombre' => 'required_with:perifericos.*|string|max:255',
            'perifericos.*.tipo' => 'nullable|string|max:255',
            'perifericos.*.serie' => 'nullable|string|max:255',
        ]);

        // Capture changes before the transaction
        $credencial->load('perifericos');

        $incomingPers = collect($request->perifericos ?? []);
        $keepPerIds = $incomingPers->pluck('id')->filter()->toArray();
        $removedPers = $credencial->perifericos->whereNotIn('id', $keepPerIds)->values();
        $newPers = $incomingPers->filter(fn ($p) => empty($p['id']))->values();

        DB::beginTransaction();

        try {
            // Update main record fields
            $updateData = [
                'nombre_equipo' => $request->nombre_equipo,
                'modelo' => $request->modelo,
                'numero_serie' => $request->numero_serie,
                'nombre_usuario_pc' => $request->nombre_usuario_pc,
                'notas' => $request->notas,
            ];
            if ($request->filled('contrasena_equipo')) {
                $updateData['contrasena_equipo'] = $request->contrasena_equipo;
            }
            $credencial->update($updateData);

            // Sync correos: delete removed, update existing, create new
            $incomingCorreos = collect($request->correos ?? []);
            $keepCorreoIds = $incomingCorreos->pluck('id')->filter()->toArray();
            $credencial->correos()->whereNotIn('id', $keepCorreoIds)->delete();

            foreach ($incomingCorreos as $correoData) {
                if (empty($correoData['correo'])) {
                    continue;
                }
                if (! empty($correoData['id'])) {
                    $existing = $credencial->correos()->find($correoData['id']);
                    if ($existing) {
                        $upd = ['correo' => $correoData['correo']];
                        if (! empty($correoData['contrasena_correo'])) {
                            $upd['contrasena_correo'] = $correoData['contrasena_correo'];
                        }
                        $existing->update($upd);
                    }
                } else {
                    $credencial->correos()->create([
                        'correo' => $correoData['correo'],
                        'contrasena_correo' => $correoData['contrasena_correo'] ?? null,
                    ]);
                }
            }

            // Sync perifericos: delete removed, add new
            $credencial->perifericos()->whereNotIn('id', $keepPerIds)->delete();
            foreach ($newPers as $per) {
                if (empty($per['uuid'])) {
                    continue;
                }
                $credencial->perifericos()->create([
                    'uuid_activos' => $per['uuid'],
                    'nombre' => $per['nombre'],
                    'tipo' => $per['tipo'] ?? null,
                    'numero_serie' => $per['serie'] ?? null,
                ]);
            }

            DB::commit();

            // Sync AuditoriaActivos outside transaction
            $user = $credencial->user;
            $empleado = $user?->empleado;
            $badge = $empleado?->id_empleado ?: null;
            $assignedTo = $empleado?->nombre ?? $user?->name ?? '';

            foreach ($removedPers as $per) {
                if ($per->uuid_activos) {
                    $this->activos->returnDeviceInActivos($per->uuid_activos);
                }
            }
            foreach ($newPers as $per) {
                if (! empty($per['uuid'])) {
                    $this->activos->assignDeviceInActivos($per['uuid'], $assignedTo, $badge);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Registro actualizado correctamente.',
                'redirect' => route('admin.credenciales.show', $credencial),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CredencialEquipo update error: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error interno al actualizar.'], 500);
        }
    }

    public function destroy(EquipoAsignado $credencial)
    {
        // ── Liberar en AuditoriaActivos antes de eliminar localmente ──────────
        $credencial->load('perifericos');

        if ($credencial->uuid_activos) {
            $this->activos->returnDeviceInActivos($credencial->uuid_activos);
        }

        foreach ($credencial->perifericos as $per) {
            if ($per->uuid_activos) {
                $this->activos->returnDeviceInActivos($per->uuid_activos);
            }
        }
        // ─────────────────────────────────────────────────────────────────────

        $credencial->delete();

        return redirect()->route('admin.credenciales.index')
            ->with('success', 'Registro eliminado correctamente.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EQUIPOS SECUNDARIOS
    // ─────────────────────────────────────────────────────────────────────────

    public function storeSecundario(Request $request, EquipoAsignado $credencial)
    {
        $request->validate([
            'assign_new' => 'sometimes|boolean',
            'uuid_activos' => 'required|string|max:255|unique:it_equipos_asignados,uuid_activos',
            'nombre_equipo' => 'required|string|max:255',
            'modelo' => 'nullable|string|max:255',
            'numero_serie' => 'nullable|string|max:255',
            'photo_id' => 'nullable|integer',
            'nombre_usuario_pc' => 'required|string|max:255',
            'contrasena_equipo' => 'required|string',
            'notas' => 'nullable|string',
            'correos' => 'sometimes|array',
            'correos.*.correo' => 'required_with:correos.*|email|max:255',
            'correos.*.contrasena_correo' => 'nullable|string',
            'perifericos' => 'sometimes|array',
            'perifericos.*.uuid' => 'required_with:perifericos.*|string',
            'perifericos.*.nombre' => 'required_with:perifericos.*|string|max:255',
            'perifericos.*.tipo' => 'nullable|string|max:255',
            'perifericos.*.serie' => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $user = $credencial->user;

            // Anteponer etiqueta en notas si no viene ya indicado
            $notas = $request->notas ?? '';
            if (! str_starts_with($notas, '[Equipo Secundario')) {
                $notas = '[Equipo Secundario / Cliente]'.($notas ? ' '.$notas : '');
            }

            $secundario = EquipoAsignado::create([
                'user_id' => $credencial->user_id,
                'uuid_activos' => $request->uuid_activos,
                'nombre_equipo' => $request->nombre_equipo,
                'modelo' => $request->modelo,
                'numero_serie' => $request->numero_serie,
                'photo_id' => $request->photo_id,
                'nombre_usuario_pc' => $request->nombre_usuario_pc,
                'contrasena_equipo' => $request->contrasena_equipo,
                'notas' => $notas,
                'es_principal' => false,
            ]);

            foreach (($request->correos ?? []) as $correoData) {
                if (! empty($correoData['correo'])) {
                    $secundario->correos()->create([
                        'correo' => $correoData['correo'],
                        'contrasena_correo' => $correoData['contrasena_correo'] ?? null,
                    ]);
                }
            }

            foreach (($request->perifericos ?? []) as $per) {
                $secundario->perifericos()->create([
                    'uuid_activos' => $per['uuid'],
                    'nombre' => $per['nombre'],
                    'tipo' => $per['tipo'] ?? null,
                    'numero_serie' => $per['serie'] ?? null,
                ]);
            }

            DB::commit();

            // Sincronizar con AuditoriaActivos
            $empleado = $user->empleado;
            $badge = $empleado?->id_empleado ?: null;
            $assignedTo = $empleado?->nombre ?? $user->name;

            // Equipo principal: solo cuando se seleccionó de los disponibles
            if ($request->boolean('assign_new')) {
                $this->activos->assignDeviceInActivos(
                    $request->uuid_activos,
                    $assignedTo,
                    $badge,
                    $notas
                );
            }

            // Periféricos: siempre se marcan asignados
            foreach (($request->perifericos ?? []) as $per) {
                if (! empty($per['uuid'])) {
                    $this->activos->assignDeviceInActivos($per['uuid'], $assignedTo, $badge);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Equipo secundario registrado correctamente.',
                'redirect' => route('admin.credenciales.show', $credencial),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CredencialEquipo storeSecundario error: '.$e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error interno al guardar.'], 500);
        }
    }

    public function destroySecundario(EquipoAsignado $credencial, EquipoAsignado $secundario)
    {
        // Validar que pertenece al mismo usuario y es secundario
        abort_if(
            $secundario->user_id !== $credencial->user_id || $secundario->es_principal,
            403,
            'No permitido.'
        );

        $secundario->load('perifericos');

        if ($secundario->uuid_activos) {
            $this->activos->returnDeviceInActivos($secundario->uuid_activos);
        }
        foreach ($secundario->perifericos as $per) {
            if ($per->uuid_activos) {
                $this->activos->returnDeviceInActivos($per->uuid_activos);
            }
        }

        $secundario->delete();

        return back()->with('success', 'Equipo secundario eliminado correctamente.');
    }

    public function cartaResponsiva(User $user)
    {
        $user->load('empleado');

        // Equipo principal
        $equipoPrincipal = EquipoAsignado::where('user_id', $user->id)
            ->where('es_principal', true)
            ->with(['correos', 'perifericos'])
            ->first();

        // Equipos secundarios
        $equiposSecundarios = EquipoAsignado::where('user_id', $user->id)
            ->where('es_principal', false)
            ->with(['correos', 'perifericos'])
            ->orderBy('created_at')
            ->get();

        $fechaCarta = now();

        return view('admin.credenciales.carta-responsiva', compact(
            'user',
            'equipoPrincipal',
            'equiposSecundarios',
            'fechaCarta'
        ));
    }

    public function guardarCartaResponsiva(Request $request, User $user)
    {
        $request->validate(['pdf_base64' => 'required|string|max:10485760']);

        $user->load('empleado');
        $empleado = $user->empleado;
        abort_if(! $empleado, 422, 'El usuario no tiene expediente registrado.');

        $raw = preg_replace('/^data:[^;]+;base64,/', '', $request->input('pdf_base64'));
        $pdfContent = base64_decode($raw, true);

        if ($pdfContent === false || ! str_starts_with($pdfContent, '%PDF')) {
            return response()->json(['success' => false, 'message' => 'El archivo PDF no es válido.'], 422);
        }

        $filename = 'carta-responsiva-'.now()->format('Y-m-d_His').'.pdf';
        $path = "expedientes/{$empleado->id}/{$filename}";
        Storage::disk('local')->put($path, $pdfContent);

        EmpleadoDocumento::create([
            'empleado_id' => $empleado->id,
            'nombre' => 'Carta Responsiva IT — '.now()->format('d/m/Y'),
            'categoria' => 'Sistema IT',
            'ruta_archivo' => $path,
        ]);

        return response()->json(['success' => true, 'message' => 'Carta responsiva guardada en el expediente.']);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // EXPORTAR EXCEL
    // ─────────────────────────────────────────────────────────────────────────

    public function exportExcel()
    {
        // Cargar todos los equipos (principal + secundario) con sus relaciones
        $equipos = EquipoAsignado::with(['user.empleado', 'correos', 'perifericos'])
            ->orderBy('user_id')
            ->orderByRaw('ISNULL(es_principal) ASC, es_principal DESC')
            ->orderBy('created_at')
            ->get();

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setTitle('Contraseñas y Equipos IT')
            ->setCreator('ERP E&I');

        // ── Hoja 1: Equipos ──────────────────────────────────────────────────
        $ws1 = $spreadsheet->getActiveSheet()->setTitle('Equipos');

        $headers1 = ['Usuario', 'Email ERP', 'Tipo', 'Nombre Equipo', 'Modelo', 'No. Serie', 'Usuario PC', 'Contraseña Equipo', 'Notas'];
        $this->writeSheetHeader($ws1, $headers1, 'FF1E3A5F');

        $row = 2;
        foreach ($equipos as $equipo) {
            $tipo = ($equipo->es_principal === null || $equipo->es_principal) ? 'Principal' : 'Secundario';
            $ws1->setCellValue("A{$row}", $equipo->user?->name ?? '—');
            $ws1->setCellValue("B{$row}", $equipo->user?->email ?? '—');
            $ws1->setCellValue("C{$row}", $tipo);
            $ws1->setCellValue("D{$row}", $equipo->nombre_equipo);
            $ws1->setCellValue("E{$row}", $equipo->modelo ?? '');
            $ws1->setCellValue("F{$row}", $equipo->numero_serie ?? '');
            $ws1->setCellValue("G{$row}", $equipo->nombre_usuario_pc ?? '');
            $ws1->setCellValue("H{$row}", $equipo->contrasena_descifrada);
            $ws1->setCellValue("I{$row}", $equipo->notas ?? '');
            $this->styleDataRow($ws1, $row, count($headers1));
            // Proteger visualmente la contraseña con fondo amarillo suave
            $ws1->getStyle("H{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFFFF8DC');
            $row++;
        }

        foreach (range('A', 'I') as $col) {
            $ws1->getColumnDimension($col)->setAutoSize(true);
        }
        $ws1->getColumnDimension('H')->setWidth(22);
        $ws1->getColumnDimension('I')->setWidth(30);

        // ── Hoja 2: Correos ──────────────────────────────────────────────────
        $ws2 = $spreadsheet->createSheet()->setTitle('Correos de Email');

        $headers2 = ['Usuario', 'Email ERP', 'Equipo', 'Tipo Equipo', 'Correo', 'Contraseña Correo'];
        $this->writeSheetHeader($ws2, $headers2, 'FF1A4731');

        $row = 2;
        foreach ($equipos as $equipo) {
            $tipo = ($equipo->es_principal === null || $equipo->es_principal) ? 'Principal' : 'Secundario';
            foreach ($equipo->correos as $correo) {
                $ws2->setCellValue("A{$row}", $equipo->user?->name ?? '—');
                $ws2->setCellValue("B{$row}", $equipo->user?->email ?? '—');
                $ws2->setCellValue("C{$row}", $equipo->nombre_equipo);
                $ws2->setCellValue("D{$row}", $tipo);
                $ws2->setCellValue("E{$row}", $correo->correo);
                $ws2->setCellValue("F{$row}", $correo->contrasena_descifrada);
                $this->styleDataRow($ws2, $row, count($headers2));
                $ws2->getStyle("F{$row}")->getFill()
                    ->setFillType(Fill::FILL_SOLID)
                    ->getStartColor()->setARGB('FFFFF8DC');
                $row++;
            }
        }

        foreach (range('A', 'F') as $col) {
            $ws2->getColumnDimension($col)->setAutoSize(true);
        }
        $ws2->getColumnDimension('F')->setWidth(25);

        // ── Hoja 3: Periféricos ──────────────────────────────────────────────
        $ws3 = $spreadsheet->createSheet()->setTitle('Periféricos');

        $headers3 = ['Usuario', 'Email ERP', 'Equipo Principal', 'Periférico', 'Tipo', 'No. Serie'];
        $this->writeSheetHeader($ws3, $headers3, 'FF4C1D95');

        $row = 2;
        foreach ($equipos as $equipo) {
            foreach ($equipo->perifericos as $per) {
                $ws3->setCellValue("A{$row}", $equipo->user?->name ?? '—');
                $ws3->setCellValue("B{$row}", $equipo->user?->email ?? '—');
                $ws3->setCellValue("C{$row}", $equipo->nombre_equipo);
                $ws3->setCellValue("D{$row}", $per->nombre);
                $ws3->setCellValue("E{$row}", $per->tipo ?? '');
                $ws3->setCellValue("F{$row}", $per->numero_serie ?? '');
                $this->styleDataRow($ws3, $row, count($headers3));
                $row++;
            }
        }

        foreach (range('A', 'F') as $col) {
            $ws3->getColumnDimension($col)->setAutoSize(true);
        }

        // Activar hoja 1
        $spreadsheet->setActiveSheetIndex(0);

        $filename = 'credenciales-equipos-IT_' . now()->format('Y-m-d') . '.xlsx';

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(
            function () use ($writer) { $writer->save('php://output'); },
            $filename,
            [
                'Content-Type'        => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Cache-Control'       => 'max-age=0',
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ]
        );
    }

    private function writeSheetHeader($sheet, array $headers, string $bgArgb): void
    {
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue("{$col}1", $header);
            $sheet->getStyle("{$col}1")->applyFromArray([
                'font'      => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF'], 'size' => 11],
                'fill'      => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => $bgArgb]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
                'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFCCCCCC']]],
            ]);
            $sheet->getRowDimension(1)->setRowHeight(20);
            $col++;
        }
    }

    private function styleDataRow($sheet, int $row, int $cols): void
    {
        $lastCol = chr(ord('A') + $cols - 1);
        $sheet->getStyle("A{$row}:{$lastCol}{$row}")->applyFromArray([
            'borders'   => ['allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['argb' => 'FFE2E8F0']]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        if ($row % 2 === 0) {
            $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB('FFF8FAFC');
        }
    }
}
