<?php

namespace App\Http\Controllers\Sistemas_IT;

use App\Http\Controllers\Controller;
use App\Models\Sistemas_IT\EquipoAsignado;
use App\Models\User;
use App\Services\ActivosDbService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\EmpleadoDocumento;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CredencialEquipoController extends Controller
{
    public function __construct(protected ActivosDbService $activos) {}

    public function index(Request $request)
    {
        $query = EquipoAsignado::with(['user', 'correos', 'perifericos'])
            ->orderBy('created_at', 'desc');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('nombre_equipo', 'like', "%{$search}%")
                  ->orWhere('modelo', 'like', "%{$search}%")
                  ->orWhere('numero_serie', 'like', "%{$search}%")
                  ->orWhere('nombre_usuario_pc', 'like', "%{$search}%")
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', "%{$search}%"));
            });
        }

        $equipos  = $query->paginate(15)->withQueryString();
        $usuarios = User::where('status', 'approved')->orderBy('name')->get(['id', 'name', 'email']);

        return view('admin.credenciales.index', compact('equipos', 'usuarios'));
    }

    public function create()
    {
        return redirect()->route('admin.credenciales.index');
    }

    public function store(Request $request)
    {
        $request->validate([
            'user_id'           => 'required|exists:users,id',
            'assign_new'        => 'sometimes|boolean',
            'uuid_activos'      => 'required|string|max:255',
            'nombre_equipo'     => 'required|string|max:255',
            'modelo'            => 'nullable|string|max:255',
            'numero_serie'      => 'nullable|string|max:255',
            'photo_id'          => 'nullable|integer',
            'nombre_usuario_pc' => 'required|string|max:255',
            'contrasena_equipo' => 'required|string',
            'notas'             => 'nullable|string',
            'correos'           => 'sometimes|array',
            'correos.*.correo'            => 'required_with:correos.*|email|max:255',
            'correos.*.contrasena_correo' => 'nullable|string',
            'perifericos'       => 'sometimes|array',
            'perifericos.*.uuid'   => 'required_with:perifericos.*|string',
            'perifericos.*.nombre' => 'required_with:perifericos.*|string|max:255',
            'perifericos.*.tipo'   => 'nullable|string|max:255',
            'perifericos.*.serie'  => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $user = User::findOrFail($request->user_id);

            $equipo = EquipoAsignado::create([
                'user_id'           => $request->user_id,
                'uuid_activos'      => $request->uuid_activos,
                'nombre_equipo'     => $request->nombre_equipo,
                'modelo'            => $request->modelo,
                'numero_serie'      => $request->numero_serie,
                'photo_id'          => $request->photo_id,
                'nombre_usuario_pc' => $request->nombre_usuario_pc,
                'contrasena_equipo' => $request->contrasena_equipo,
                'notas'             => $request->notas,
            ]);

            // Correos
            foreach (($request->correos ?? []) as $correoData) {
                if (!empty($correoData['correo'])) {
                    $equipo->correos()->create([
                        'correo'           => $correoData['correo'],
                        'contrasena_correo' => $correoData['contrasena_correo'] ?? null,
                    ]);
                }
            }

            // Periféricos
            foreach (($request->perifericos ?? []) as $per) {
                $equipo->perifericos()->create([
                    'uuid_activos' => $per['uuid'],
                    'nombre'       => $per['nombre'],
                    'tipo'         => $per['tipo'] ?? null,
                    'numero_serie' => $per['serie'] ?? null,
                ]);
            }

            DB::commit();

            // ── Sincronizar con AuditoriaActivos ──────────────────────────────
            $empleado   = $user->empleado;
            $badge      = $empleado?->id_empleado ?: null;
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
                'success'  => true,
                'message'  => 'Registro creado correctamente.',
                'redirect' => route('admin.credenciales.show', $equipo),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CredencialEquipo store error: ' . $e->getMessage());
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
            $deviceType   = $this->activos->getDeviceTypeByUuid($credencial->uuid_activos);
            $esComputadora = ($deviceType === 'computer' || $deviceType === null);
        }

        return view('admin.credenciales.show', compact('credencial', 'equiposSecundarios', 'esComputadora'));
    }

    public function edit(EquipoAsignado $credencial)
    {
        return redirect()->route('admin.credenciales.show', $credencial);
    }

    public function update(Request $request, EquipoAsignado $credencial)
    {
        $request->validate([
            'nombre_equipo'               => 'required|string|max:255',
            'modelo'                      => 'nullable|string|max:255',
            'numero_serie'                => 'nullable|string|max:255',
            'nombre_usuario_pc'           => 'required|string|max:255',
            'contrasena_equipo'           => 'nullable|string',
            'notas'                       => 'nullable|string',
            'correos'                     => 'sometimes|array',
            'correos.*.id'                => 'nullable|integer',
            'correos.*.correo'            => 'required_with:correos.*|email|max:255',
            'correos.*.contrasena_correo' => 'nullable|string',
            'perifericos'                 => 'sometimes|array',
            'perifericos.*.id'            => 'nullable|integer',
            'perifericos.*.uuid'          => 'required_with:perifericos.*|string',
            'perifericos.*.nombre'        => 'required_with:perifericos.*|string|max:255',
            'perifericos.*.tipo'          => 'nullable|string|max:255',
            'perifericos.*.serie'         => 'nullable|string|max:255',
        ]);

        // Capture changes before the transaction
        $credencial->load('perifericos');

        $incomingPers = collect($request->perifericos ?? []);
        $keepPerIds   = $incomingPers->pluck('id')->filter()->toArray();
        $removedPers  = $credencial->perifericos->whereNotIn('id', $keepPerIds)->values();
        $newPers      = $incomingPers->filter(fn ($p) => empty($p['id']))->values();

        DB::beginTransaction();

        try {
            // Update main record fields
            $updateData = [
                'nombre_equipo'     => $request->nombre_equipo,
                'modelo'            => $request->modelo,
                'numero_serie'      => $request->numero_serie,
                'nombre_usuario_pc' => $request->nombre_usuario_pc,
                'notas'             => $request->notas,
            ];
            if ($request->filled('contrasena_equipo')) {
                $updateData['contrasena_equipo'] = $request->contrasena_equipo;
            }
            $credencial->update($updateData);

            // Sync correos: delete removed, update existing, create new
            $incomingCorreos = collect($request->correos ?? []);
            $keepCorreoIds   = $incomingCorreos->pluck('id')->filter()->toArray();
            $credencial->correos()->whereNotIn('id', $keepCorreoIds)->delete();

            foreach ($incomingCorreos as $correoData) {
                if (empty($correoData['correo'])) continue;
                if (!empty($correoData['id'])) {
                    $existing = $credencial->correos()->find($correoData['id']);
                    if ($existing) {
                        $upd = ['correo' => $correoData['correo']];
                        if (!empty($correoData['contrasena_correo'])) {
                            $upd['contrasena_correo'] = $correoData['contrasena_correo'];
                        }
                        $existing->update($upd);
                    }
                } else {
                    $credencial->correos()->create([
                        'correo'            => $correoData['correo'],
                        'contrasena_correo' => $correoData['contrasena_correo'] ?? null,
                    ]);
                }
            }

            // Sync perifericos: delete removed, add new
            $credencial->perifericos()->whereNotIn('id', $keepPerIds)->delete();
            foreach ($newPers as $per) {
                if (empty($per['uuid'])) continue;
                $credencial->perifericos()->create([
                    'uuid_activos' => $per['uuid'],
                    'nombre'       => $per['nombre'],
                    'tipo'         => $per['tipo'] ?? null,
                    'numero_serie' => $per['serie'] ?? null,
                ]);
            }

            DB::commit();

            // Sync AuditoriaActivos outside transaction
            $user       = $credencial->user;
            $empleado   = $user?->empleado;
            $badge      = $empleado?->id_empleado ?: null;
            $assignedTo = $empleado?->nombre ?? $user?->name ?? '';

            foreach ($removedPers as $per) {
                if ($per->uuid_activos) {
                    $this->activos->returnDeviceInActivos($per->uuid_activos);
                }
            }
            foreach ($newPers as $per) {
                if (!empty($per['uuid'])) {
                    $this->activos->assignDeviceInActivos($per['uuid'], $assignedTo, $badge);
                }
            }

            return response()->json([
                'success'  => true,
                'message'  => 'Registro actualizado correctamente.',
                'redirect' => route('admin.credenciales.show', $credencial),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CredencialEquipo update error: ' . $e->getMessage());
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
            'assign_new'        => 'sometimes|boolean',
            'uuid_activos'      => 'required|string|max:255',
            'nombre_equipo'     => 'required|string|max:255',
            'modelo'            => 'nullable|string|max:255',
            'numero_serie'      => 'nullable|string|max:255',
            'photo_id'          => 'nullable|integer',
            'nombre_usuario_pc' => 'required|string|max:255',
            'contrasena_equipo' => 'required|string',
            'notas'             => 'nullable|string',
            'correos'           => 'sometimes|array',
            'correos.*.correo'             => 'required_with:correos.*|email|max:255',
            'correos.*.contrasena_correo'  => 'nullable|string',
            'perifericos'       => 'sometimes|array',
            'perifericos.*.uuid'    => 'required_with:perifericos.*|string',
            'perifericos.*.nombre'  => 'required_with:perifericos.*|string|max:255',
            'perifericos.*.tipo'    => 'nullable|string|max:255',
            'perifericos.*.serie'   => 'nullable|string|max:255',
        ]);

        DB::beginTransaction();

        try {
            $user = $credencial->user;

            // Anteponer etiqueta en notas si no viene ya indicado
            $notas = $request->notas ?? '';
            if (!str_starts_with($notas, '[Equipo Secundario')) {
                $notas = '[Equipo Secundario / Cliente]' . ($notas ? ' ' . $notas : '');
            }

            $secundario = EquipoAsignado::create([
                'user_id'           => $credencial->user_id,
                'uuid_activos'      => $request->uuid_activos,
                'nombre_equipo'     => $request->nombre_equipo,
                'modelo'            => $request->modelo,
                'numero_serie'      => $request->numero_serie,
                'photo_id'          => $request->photo_id,
                'nombre_usuario_pc' => $request->nombre_usuario_pc,
                'contrasena_equipo' => $request->contrasena_equipo,
                'notas'             => $notas,
                'es_principal'      => false,
            ]);

            foreach (($request->correos ?? []) as $correoData) {
                if (!empty($correoData['correo'])) {
                    $secundario->correos()->create([
                        'correo'            => $correoData['correo'],
                        'contrasena_correo' => $correoData['contrasena_correo'] ?? null,
                    ]);
                }
            }

            foreach (($request->perifericos ?? []) as $per) {
                $secundario->perifericos()->create([
                    'uuid_activos' => $per['uuid'],
                    'nombre'       => $per['nombre'],
                    'tipo'         => $per['tipo'] ?? null,
                    'numero_serie' => $per['serie'] ?? null,
                ]);
            }

            DB::commit();

            // Sincronizar con AuditoriaActivos
            $empleado   = $user->empleado;
            $badge      = $empleado?->id_empleado ?: null;
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
                if (!empty($per['uuid'])) {
                    $this->activos->assignDeviceInActivos($per['uuid'], $assignedTo, $badge);
                }
            }

            return response()->json([
                'success'  => true,
                'message'  => 'Equipo secundario registrado correctamente.',
                'redirect' => route('admin.credenciales.show', $credencial),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('CredencialEquipo storeSecundario error: ' . $e->getMessage());
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
        abort_if(!$empleado, 422, 'El usuario no tiene expediente registrado.');

        $raw = preg_replace('/^data:[^;]+;base64,/', '', $request->input('pdf_base64'));
        $pdfContent = base64_decode($raw, true);

        if ($pdfContent === false || !str_starts_with($pdfContent, '%PDF')) {
            return response()->json(['success' => false, 'message' => 'El archivo PDF no es válido.'], 422);
        }

        $filename = 'carta-responsiva-' . now()->format('Y-m-d_His') . '.pdf';
        $path = "expedientes/{$empleado->id}/{$filename}";
        Storage::disk('local')->put($path, $pdfContent);

        EmpleadoDocumento::updateOrCreate(
            ['empleado_id' => $empleado->id, 'nombre' => 'Carta Responsiva IT'],
            ['categoria' => 'Sistema IT', 'ruta_archivo' => $path]
        );

        return response()->json(['success' => true, 'message' => 'Carta responsiva guardada en el expediente.']);
    }
}
