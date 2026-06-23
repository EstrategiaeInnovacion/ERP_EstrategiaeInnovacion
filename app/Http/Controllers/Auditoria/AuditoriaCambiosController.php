<?php
 
namespace App\Http\Controllers\Auditoria;
 
use App\Http\Controllers\Controller;
use App\Models\Auditoria\ProyectoAuditoria;
use App\Models\Auditoria\ActividadAuditoria;
use App\Models\Auditoria\CambioPropuesto;
use App\Models\Auditoria\ComentarioAuditoria;
use App\Models\Auditoria\BitacoraAuditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
 
class AuditoriaCambiosController extends Controller
{
    use \App\Http\Controllers\Auditoria\AuditoriaCoordinadorTrait;

    // Guardar cambio propuesto como Borrador o enviarlo a Revisión (Analista)
    public function store(Request $request, $proyectoId)
    {
        $user = auth()->user();
        $proyecto = ProyectoAuditoria::findOrFail($proyectoId);
 
        $request->validate([
            'actividad_id' => 'required|exists:auditoria_actividades,id',
            'porcentaje_propuesto' => 'required|integer|between:0,100',
            'comentario_propuesto' => 'nullable|string|max:2000',
            'comentario_visible_cliente' => 'nullable|boolean',
            'enviar' => 'required|boolean', // true = enviar a revisión, false = guardar borrador
        ]);
 
        $actividad = ActividadAuditoria::findOrFail($request->actividad_id);
 
        // Validar que el analista esté asignado al proyecto en general
        $esCoordinador = $this->esCoordinador($user);
        $esAsignado = $proyecto->analista_id === $user->id;

        if (!$esCoordinador && !$esAsignado) {
            return response()->json(['success' => false, 'error' => 'No tienes permisos para reportar avance en esta actividad.'], 403);
        }
 
        $porcentaje = $request->porcentaje_propuesto;
        if ($porcentaje == 100) {
            $estatus = 'cerrado';
        } elseif ($porcentaje == 0) {
            $estatus = 'pendiente';
        } else {
            $estatus = 'en proceso';
        }
 
        // Buscar si ya existe una propuesta de cambio (borrador o pendiente) para esta actividad
        $propuestaExistente = CambioPropuesto::where('actividad_id', $actividad->id)
            ->whereIn('estatus_revision', ['borrador', 'pendiente', 'ajuste_solicitado'])
            ->first();
 
        if ($propuestaExistente && $propuestaExistente->estatus_revision === 'pendiente') {
            return response()->json(['success' => false, 'error' => 'La actividad ya tiene un cambio enviado que está en revisión. No se puede modificar.'], 400);
        }
 
        $esCoordinador = $this->esCoordinador($user);
        $estatusRevision = $request->enviar ? ($esCoordinador ? 'aprobado' : 'pendiente') : 'borrador';
 
        DB::transaction(function () use ($propuestaExistente, $actividad, $proyecto, $user, $porcentaje, $estatus, $request, $estatusRevision, $esCoordinador) {
            if ($propuestaExistente) {
                // Actualizar propuesta existente
                $propuestaExistente->update([
                    'user_id' => $user->id,
                    'estatus_propuesto' => $estatus,
                    'porcentaje_propuesto' => $porcentaje,
                    'comentario_propuesto' => $request->comentario_propuesto,
                    'comentario_visible_cliente' => $request->has('comentario_visible_cliente'),
                    'estatus_revision' => $estatusRevision,
                    'motivo_rechazo' => null, // Limpiar motivo anterior si estaba en ajuste_solicitado
                    'revisado_por' => $estatusRevision === 'aprobado' ? $user->id : null,
                    'fecha_revision' => $estatusRevision === 'aprobado' ? now() : null,
                ]);
                $propuesta = $propuestaExistente;
            } else {
                // Crear nueva propuesta
                $propuesta = CambioPropuesto::create([
                    'actividad_id' => $actividad->id,
                    'proyecto_id' => $proyecto->id,
                    'padre_id' => $actividad->padre_id,
                    'user_id' => $user->id,
                    'tipo_cambio' => 'update_activity',
                    'estatus_propuesto' => $estatus,
                    'porcentaje_propuesto' => $porcentaje,
                    'comentario_propuesto' => $request->comentario_propuesto,
                    'comentario_visible_cliente' => $request->has('comentario_visible_cliente'),
                    'estatus_revision' => $estatusRevision,
                    'revisado_por' => $estatusRevision === 'aprobado' ? $user->id : null,
                    'fecha_revision' => $estatusRevision === 'aprobado' ? now() : null,
                ]);
            }
 
            if ($estatusRevision === 'aprobado') {
                $valorAnterior = $actividad->porcentaje_oficial;
                
                // Actualizar actividad oficial
                $actividad->update([
                    'estatus_oficial' => $estatus,
                    'porcentaje_oficial' => $porcentaje,
                    'comentarios' => $request->comentario_propuesto,
                ]);
 
                // Registrar el comentario aprobado para historial
                if (!empty($request->comentario_propuesto)) {
                    ComentarioAuditoria::create([
                        'actividad_id' => $actividad->id,
                        'user_id' => $user->id,
                        'comentario' => $request->comentario_propuesto,
                        'visible_cliente' => $request->has('comentario_visible_cliente'),
                    ]);
                }
 
                BitacoraAuditoria::registrar($proyecto->id, 'aprobar_cambio', $actividad->id, 'porcentaje_oficial', $valorAnterior, $porcentaje, $request->comentario_propuesto);
            } else {
                // Registrar en bitácora
                $accion = $estatusRevision === 'pendiente' ? 'proponer_cambio' : 'guardar_borrador';
                BitacoraAuditoria::registrar($proyecto->id, $accion, $actividad->id, 'porcentaje_propuesto', $actividad->porcentaje_oficial, $porcentaje, $request->comentario_propuesto);
            }
            
            // Recalcular los porcentajes del proyecto (afectando avance interno)
            $proyecto->recalcularPorcentajes();
        });
 
        $msg = $estatusRevision === 'aprobado' ? 'Cambio aprobado y aplicado correctamente.' : ($estatusRevision === 'pendiente' ? 'Cambios enviados a revisión correctamente.' : 'Borrador guardado exitosamente.');
        return response()->json(['success' => true, 'message' => $msg]);
    }
 
    // Enviar borrador a revisión (Analista) o Auto-aprobar (Coordinador)
    public function enviarRevision(Request $request, $proyectoId)
    {
        $user = auth()->user();
        $proyecto = ProyectoAuditoria::findOrFail($proyectoId);
 
        $request->validate([
            'cambio_id' => 'required|exists:auditoria_cambios_propuestos,id'
        ]);
 
        $cambio = CambioPropuesto::where('proyecto_id', $proyecto->id)
            ->where('user_id', $user->id)
            ->findOrFail($request->cambio_id);
 
        if ($cambio->estatus_revision !== 'borrador' && $cambio->estatus_revision !== 'ajuste_solicitado') {
            return response()->json(['success' => false, 'error' => 'El cambio no está en estado borrador.'], 400);
        }
 
        $esCoordinador = $this->esCoordinador($user);
        $estatusRevision = $esCoordinador ? 'aprobado' : 'pendiente';
 
        DB::transaction(function () use ($cambio, $proyecto, $user, $estatusRevision, $esCoordinador) {
            $cambio->update([
                'estatus_revision' => $estatusRevision,
                'revisado_por' => $estatusRevision === 'aprobado' ? $user->id : null,
                'fecha_revision' => $estatusRevision === 'aprobado' ? now() : null,
            ]);
 
            if ($estatusRevision === 'aprobado') {
                $actividad = ActividadAuditoria::findOrFail($cambio->actividad_id);
                $valorAnterior = $actividad->porcentaje_oficial;
 
                $actividad->update([
                    'estatus_oficial' => $cambio->estatus_propuesto,
                    'porcentaje_oficial' => $cambio->porcentaje_propuesto,
                    'comentarios' => $cambio->comentario_propuesto,
                ]);
 
                if (!empty($cambio->comentario_propuesto)) {
                    ComentarioAuditoria::create([
                        'actividad_id' => $actividad->id,
                        'user_id' => $cambio->user_id,
                        'comentario' => $cambio->comentario_propuesto,
                        'visible_cliente' => $cambio->comentario_visible_cliente,
                    ]);
                }
 
                BitacoraAuditoria::registrar($proyecto->id, 'aprobar_cambio', $actividad->id, 'porcentaje_oficial', $valorAnterior, $cambio->porcentaje_propuesto, 'Cambio aprobado por coordinador.');
            } else {
                BitacoraAuditoria::registrar($proyecto->id, 'proponer_cambio', $cambio->actividad_id, 'estatus_revision', 'borrador', 'pendiente', 'Borrador enviado a revisión.');
            }
 
            $proyecto->recalcularPorcentajes();
        });
 
        $msg = $esCoordinador ? 'Cambio aprobado y aplicado correctamente.' : 'Cambio enviado a revisión correctamente.';
        return response()->json(['success' => true, 'message' => $msg]);
    }
 
    // Procesar revisión de cambio (Aprobar / Rechazar / Solicitar Ajuste) - Coordinador
    public function revisar(Request $request, $proyectoId)
    {
        $user = auth()->user();
        if (!$this->esCoordinador($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }
 
        $proyecto = ProyectoAuditoria::findOrFail($proyectoId);
 
        $request->validate([
            'cambio_id' => 'required|exists:auditoria_cambios_propuestos,id',
            'accion' => 'required|in:aprobar,rechazar,ajuste',
            'motivo_rechazo' => 'required_if:accion,rechazar,ajuste|nullable|string|max:1000',
        ]);
 
        $cambio = CambioPropuesto::where('proyecto_id', $proyecto->id)->findOrFail($request->cambio_id);
 
        if ($cambio->estatus_revision !== 'pendiente') {
            return response()->json(['success' => false, 'error' => 'El cambio ya ha sido procesado previamente.'], 400);
        }
 
        DB::transaction(function () use ($cambio, $proyecto, $user, $request) {
            $accion = $request->accion;
            
            if ($accion === 'aprobar') {
                $cambio->update([
                    'estatus_revision' => 'aprobado',
                    'revisado_por' => $user->id,
                    'fecha_revision' => now(),
                ]);
 
                if ($cambio->tipo_cambio === 'update_activity') {
                    $actividad = ActividadAuditoria::findOrFail($cambio->actividad_id);
                    $valorAnterior = $actividad->porcentaje_oficial;
                    
                    // Actualizar actividad oficial
                    $actividad->update([
                        'estatus_oficial' => $cambio->estatus_propuesto,
                        'porcentaje_oficial' => $cambio->porcentaje_propuesto,
                        'comentarios' => $cambio->comentario_propuesto,
                    ]);
 
                    // Registrar el comentario aprobado para historial
                    if (!empty($cambio->comentario_propuesto)) {
                        ComentarioAuditoria::create([
                            'actividad_id' => $actividad->id,
                            'user_id' => $cambio->user_id, // El analista que lo escribió
                            'comentario' => $cambio->comentario_propuesto,
                            'visible_cliente' => $cambio->comentario_visible_cliente,
                        ]);
                    }
 
                    BitacoraAuditoria::registrar($proyecto->id, 'aprobar_cambio', $actividad->id, 'porcentaje_oficial', $valorAnterior, $cambio->porcentaje_propuesto, 'Cambio aprobado por coordinador.');
                } elseif ($cambio->tipo_cambio === 'create_subprocess' || $cambio->tipo_cambio === 'create_process') {
                    // Crear el nuevo proceso o subproceso aprobado oficialmente
                    $ultimoOrden = ActividadAuditoria::where('proyecto_id', $proyecto->id)
                        ->where('padre_id', $cambio->padre_id)
                        ->max('orden') ?? -1;

                    $actividad = ActividadAuditoria::create([
                        'proyecto_id' => $proyecto->id,
                        'padre_id' => $cambio->padre_id,
                        'orden' => $ultimoOrden + 1,
                        'actividad' => $cambio->actividad_nombre_propuesto,
                        'responsable' => $cambio->responsable_propuesto ?? 'E&I',
                        'plazo' => $proyecto->fecha_entrega_estimada,
                        'estatus_oficial' => $cambio->estatus_propuesto ?? 'pendiente',
                        'porcentaje_oficial' => $cambio->porcentaje_propuesto,
                        'es_proceso_principal' => is_null($cambio->padre_id),
                        'comentarios' => $cambio->comentario_propuesto,
                    ]);

                    // Registrar el comentario aprobado
                    if (!empty($cambio->comentario_propuesto)) {
                        ComentarioAuditoria::create([
                            'actividad_id' => $actividad->id,
                            'user_id' => $cambio->user_id,
                            'comentario' => $cambio->comentario_propuesto,
                            'visible_cliente' => $cambio->comentario_visible_cliente,
                        ]);
                    }

                    // Guardar la referencia de la actividad recién creada en la propuesta para trazabilidad
                    $cambio->update(['actividad_id' => $actividad->id]);

                    $accionBitacora = is_null($cambio->padre_id) ? 'aprobar_proceso' : 'aprobar_subproceso';
                    BitacoraAuditoria::registrar($proyecto->id, $accionBitacora, $actividad->id, 'actividad', null, $actividad->actividad, 'Nuevo proceso/subproceso aprobado.');
                }
            } elseif ($accion === 'rechazar') {
                $cambio->update([
                    'estatus_revision' => 'rechazado',
                    'motivo_rechazo' => $request->motivo_rechazo,
                    'revisado_por' => $user->id,
                    'fecha_revision' => now(),
                ]);
 
                BitacoraAuditoria::registrar($proyecto->id, 'rechazar_cambio', $cambio->actividad_id, 'estatus_revision', 'pendiente', 'rechazado', $request->motivo_rechazo);
            } elseif ($accion === 'ajuste') {
                $cambio->update([
                    'estatus_revision' => 'ajuste_solicitado',
                    'motivo_rechazo' => $request->motivo_rechazo,
                    'revisado_por' => $user->id,
                    'fecha_revision' => now(),
                ]);
 
                BitacoraAuditoria::registrar($proyecto->id, 'solicitar_ajuste', $cambio->actividad_id, 'estatus_revision', 'pendiente', 'ajuste_solicitado', $request->motivo_rechazo);
            }
 
            // Recalcular los porcentajes del proyecto
            $proyecto->recalcularPorcentajes();
        });
 
        return response()->json(['success' => true]);
    }
 
    // Aprobar todos los cambios pendientes de un proyecto (paquete) - Coordinador
    public function revisarPaquete(Request $request, $proyectoId)
    {
        $user = auth()->user();
        if (!$this->esCoordinador($user)) {
            return response()->json(['success' => false, 'error' => 'No autorizado'], 403);
        }
 
        $proyecto = ProyectoAuditoria::findOrFail($proyectoId);
 
        $cambios = CambioPropuesto::where('proyecto_id', $proyecto->id)
            ->where('estatus_revision', 'pendiente')
            ->get();
 
        if ($cambios->isEmpty()) {
            return response()->json(['success' => false, 'error' => 'No hay cambios pendientes de revisión.'], 400);
        }
 
        DB::transaction(function () use ($cambios, $proyecto, $user) {
            foreach ($cambios as $cambio) {
                $cambio->update([
                    'estatus_revision' => 'aprobado',
                    'revisado_por' => $user->id,
                    'fecha_revision' => now(),
                ]);
 
                if ($cambio->tipo_cambio === 'update_activity') {
                    $actividad = ActividadAuditoria::findOrFail($cambio->actividad_id);
                    $valorAnterior = $actividad->porcentaje_oficial;
                    
                    $actividad->update([
                        'estatus_oficial' => $cambio->estatus_propuesto,
                        'porcentaje_oficial' => $cambio->porcentaje_propuesto,
                        'comentarios' => $cambio->comentario_propuesto,
                    ]);
 
                    if (!empty($cambio->comentario_propuesto)) {
                        ComentarioAuditoria::create([
                            'actividad_id' => $actividad->id,
                            'user_id' => $cambio->user_id,
                            'comentario' => $cambio->comentario_propuesto,
                            'visible_cliente' => $cambio->comentario_visible_cliente,
                        ]);
                    }
 
                    BitacoraAuditoria::registrar($proyecto->id, 'aprobar_cambio', $actividad->id, 'porcentaje_oficial', $valorAnterior, $cambio->porcentaje_propuesto, 'Aprobación masiva.');
                } elseif ($cambio->tipo_cambio === 'create_subprocess' || $cambio->tipo_cambio === 'create_process') {
                    $ultimoOrden = ActividadAuditoria::where('proyecto_id', $proyecto->id)
                        ->where('padre_id', $cambio->padre_id)
                        ->max('orden') ?? -1;
 
                    $actividad = ActividadAuditoria::create([
                        'proyecto_id' => $proyecto->id,
                        'padre_id' => $cambio->padre_id,
                        'orden' => $ultimoOrden + 1,
                        'actividad' => $cambio->actividad_nombre_propuesto,
                        'responsable' => $cambio->responsable_propuesto ?? 'E&I',
                        'plazo' => $proyecto->fecha_entrega_estimada,
                        'estatus_oficial' => $cambio->estatus_propuesto ?? 'pendiente',
                        'porcentaje_oficial' => $cambio->porcentaje_propuesto,
                        'es_proceso_principal' => is_null($cambio->padre_id),
                        'comentarios' => $cambio->comentario_propuesto,
                    ]);
 
                    if (!empty($cambio->comentario_propuesto)) {
                        ComentarioAuditoria::create([
                            'actividad_id' => $actividad->id,
                            'user_id' => $cambio->user_id,
                            'comentario' => $cambio->comentario_propuesto,
                            'visible_cliente' => $cambio->comentario_visible_cliente,
                        ]);
                    }
 
                    $cambio->update(['actividad_id' => $actividad->id]);
 
                    $accionBitacora = is_null($cambio->padre_id) ? 'aprobar_proceso' : 'aprobar_subproceso';
                    BitacoraAuditoria::registrar($proyecto->id, $accionBitacora, $actividad->id, 'actividad', null, $actividad->actividad, 'Nuevo proceso/subproceso aprobado masivamente.');
                }
            }
 
            $proyecto->recalcularPorcentajes();
        });
 
        return response()->json(['success' => true]);
    }

    // Enviar todos los borradores a revisión (Analista / Coordinador)
    public function enviarTodosRevision(Request $request, $proyectoId)
    {
        $user = auth()->user();
        $proyecto = ProyectoAuditoria::findOrFail($proyectoId);

        $borradores = CambioPropuesto::where('proyecto_id', $proyecto->id)
            ->where('user_id', $user->id)
            ->where('estatus_revision', 'borrador')
            ->get();

        if ($borradores->isEmpty()) {
            return response()->json(['success' => false, 'error' => 'No tienes borradores pendientes.'], 400);
        }

        DB::transaction(function () use ($borradores, $proyecto, $user) {
            $esCoordinador = $this->esCoordinador($user);
            $nuevoEstado = $esCoordinador ? 'aprobado' : 'pendiente';

            foreach ($borradores as $cambio) {
                if ($nuevoEstado === 'aprobado') {
                    $cambio->update([
                        'estatus_revision' => 'aprobado',
                        'revisado_por' => $user->id,
                        'fecha_revision' => now(),
                    ]);

                    if ($cambio->tipo_cambio === 'update_activity') {
                        $actividad = ActividadAuditoria::findOrFail($cambio->actividad_id);
                        $valorAnterior = $actividad->porcentaje_oficial;

                        $actividad->update([
                            'estatus_oficial' => $cambio->estatus_propuesto,
                            'porcentaje_oficial' => $cambio->porcentaje_propuesto,
                            'comentarios' => $cambio->comentario_propuesto,
                        ]);

                        if (!empty($cambio->comentario_propuesto)) {
                            ComentarioAuditoria::create([
                                'actividad_id' => $actividad->id,
                                'user_id' => $cambio->user_id,
                                'comentario' => $cambio->comentario_propuesto,
                                'visible_cliente' => $cambio->comentario_visible_cliente,
                            ]);
                        }

                        BitacoraAuditoria::registrar($proyecto->id, 'aprobar_cambio', $actividad->id, 'porcentaje_oficial', $valorAnterior, $cambio->porcentaje_propuesto, 'Cambio auto-aprobado por coordinador.');
                    } elseif ($cambio->tipo_cambio === 'create_process' || $cambio->tipo_cambio === 'create_subprocess') {
                        $ultimoOrden = ActividadAuditoria::where('proyecto_id', $proyecto->id)
                            ->where('padre_id', $cambio->padre_id)
                            ->max('orden') ?? -1;

                        $actividad = ActividadAuditoria::create([
                            'proyecto_id' => $proyecto->id,
                            'padre_id' => $cambio->padre_id,
                            'orden' => $ultimoOrden + 1,
                            'actividad' => $cambio->actividad_nombre_propuesto,
                            'responsable' => $cambio->responsable_propuesto ?? 'E&I',
                            'plazo' => $proyecto->fecha_entrega_estimada,
                            'estatus_oficial' => $cambio->estatus_propuesto ?? 'pendiente',
                            'porcentaje_oficial' => $cambio->porcentaje_propuesto,
                            'es_proceso_principal' => is_null($cambio->padre_id),
                            'comentarios' => $cambio->comentario_propuesto,
                        ]);

                        if (!empty($cambio->comentario_propuesto)) {
                            ComentarioAuditoria::create([
                                'actividad_id' => $actividad->id,
                                'user_id' => $cambio->user_id,
                                'comentario' => $cambio->comentario_propuesto,
                                'visible_cliente' => $cambio->comentario_visible_cliente,
                            ]);
                        }

                        $cambio->update(['actividad_id' => $actividad->id]);

                        $accionBitacora = is_null($cambio->padre_id) ? 'aprobar_proceso' : 'aprobar_subproceso';
                        BitacoraAuditoria::registrar($proyecto->id, $accionBitacora, $actividad->id, 'actividad', null, $actividad->actividad, 'Nuevo proceso/subproceso auto-aprobado.');
                    }
                } else {
                    // Analista normal envía a revisión
                    $cambio->update([
                        'estatus_revision' => 'pendiente',
                    ]);

                    $accion = ($cambio->tipo_cambio === 'create_process' || $cambio->tipo_cambio === 'create_subprocess') ? 'proponer_cambio' : 'proponer_cambio';
                    BitacoraAuditoria::registrar($proyecto->id, $accion, $cambio->actividad_id, 'estatus_revision', 'borrador', 'pendiente', 'Borrador enviado a revisión.');
                }
            }

            $proyecto->recalcularPorcentajes();
        });

        $msg = auth()->user()->empleado && auth()->user()->empleado->es_coordinador ? 'Borradores auto-aprobados correctamente.' : 'Todos los borradores fueron enviados a revisión correctamente.';
        return response()->json(['success' => true, 'message' => $msg]);
    }

    // Cancelar/Eliminar un borrador o propuesta
    public function destroy(Request $request, $proyectoId, $cambioId)
    {
        $user = auth()->user();
        $proyecto = ProyectoAuditoria::findOrFail($proyectoId);

        $cambio = CambioPropuesto::where('proyecto_id', $proyecto->id)
            ->where('user_id', $user->id)
            ->findOrFail($cambioId);

        // Solo se pueden eliminar borradores, propuestas pendientes o con ajuste solicitado
        if (!in_array($cambio->estatus_revision, ['borrador', 'pendiente', 'ajuste_solicitado'])) {
            return redirect()->back()->with('error', 'No se puede eliminar un cambio que ya ha sido aprobado o rechazado.');
        }

        $nombre = $cambio->actividad_nombre_propuesto ?? ($cambio->actividad ? $cambio->actividad->actividad : 'Cambio');
        $cambio->delete();

        BitacoraAuditoria::registrar($proyecto->id, 'cancelar_cambio', $cambio->actividad_id, 'estatus_revision', $cambio->estatus_revision, 'cancelado', "Propuesta/borrador de '$nombre' cancelada.");

        $proyecto->recalcularPorcentajes();

        return redirect()->back()->with('success', 'Propuesta cancelada y eliminada correctamente.');
    }
}
