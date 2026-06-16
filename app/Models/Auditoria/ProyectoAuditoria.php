<?php
 
namespace App\Models\Auditoria;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Administracion\Cliente;
use App\Models\User;
 
class ProyectoAuditoria extends Model
{
    use SoftDeletes;
 
    protected $table = 'auditoria_proyectos';
 
    protected $fillable = [
        'cliente_id',
        'cliente_nombre',
        'periodo_fiscal',
        'coordinador_id',
        'analista_id',
        'cantidad_expedientes',
        'fecha_inicio',
        'fecha_entrega_estimada',
        'estatus_general',
        'fase_actual',
        'fases_config',
        'porcentaje_general_aprobado',
        'porcentaje_general_interno',
        'porcentaje_general_publicado',
        'token_publico',
        'publico_password',
        'publico_expira_at',
        'mostrar_detalle_cliente',
        'ultima_publicacion_at',
        'ultima_publicacion_user_id',
    ];

    // Accessor para obtener el nombre del cliente de manera unificada
    public function getNombreClienteAttribute()
    {
        return $this->cliente ? $this->cliente->nombre : ($this->cliente_nombre ?? 'Cliente sin nombre');
    }
 
    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_entrega_estimada' => 'date',
        'fases_config' => 'array',
        'mostrar_detalle_cliente' => 'boolean',
        'publico_expira_at' => 'datetime',
        'ultima_publicacion_at' => 'datetime',
    ];
 
    // Relación con Cliente
    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }
 
    // Coordinador Responsable
    public function coordinador()
    {
        return $this->belongsTo(User::class, 'coordinador_id');
    }
 
    // Analista Responsable
    public function analista()
    {
        return $this->belongsTo(User::class, 'analista_id');
    }
 
    // Actividades del proyecto
    public function actividades()
    {
        return $this->hasMany(ActividadAuditoria::class, 'proyecto_id');
    }
 
    // Cambios propuestos en el proyecto
    public function cambiosPropuestos()
    {
        return $this->hasMany(CambioPropuesto::class, 'proyecto_id');
    }
 
    // Usuario que publicó por última vez
    public function publicador()
    {
        return $this->belongsTo(User::class, 'ultima_publicacion_user_id');
    }
 
    // Historial de publicaciones
    public function historialPublicaciones()
    {
        return $this->hasMany(HistorialPublicacionAuditoria::class, 'proyecto_id');
    }
 
    // Recalcular avances del proyecto (aprobado e interno)
    public function recalcularPorcentajes()
    {
        // Obtener actividades de primer nivel (procesos principales)
        $procesosPrincipales = $this->actividades()->whereNull('padre_id')->get();
 
        if ($procesosPrincipales->isEmpty()) {
            $this->update([
                'porcentaje_general_aprobado' => 0.00,
                'porcentaje_general_interno' => 0.00,
            ]);
            return;
        }
 
        $sumaAprobados = 0;
        $sumaInternos = 0;
        $totalProcesos = $procesosPrincipales->count();
 
        foreach ($procesosPrincipales as $proceso) {
            $res = $this->obtenerAvanceProceso($proceso);
            $sumaAprobados += $res['aprobado'];
            $sumaInternos += $res['interno'];
        }
 
        $this->update([
            'porcentaje_general_aprobado' => round($sumaAprobados / $totalProcesos, 2),
            'porcentaje_general_interno' => round($sumaInternos / $totalProcesos, 2),
        ]);
    }
 
    // Helper para obtener el avance de un proceso principal (calculando rollup de hijos si aplica)
    private function obtenerAvanceProceso(ActividadAuditoria $proceso)
    {
        $hijos = $this->actividades()->where('padre_id', $proceso->id)->get();
 
        if ($hijos->isEmpty()) {
            // No tiene subprocesos, se usa el valor de la misma fila
            $aprobado = $proceso->porcentaje_oficial;
            
            // Para el avance interno, buscar si tiene algún cambio propuesto pendiente
            $cambioPendiente = CambioPropuesto::where('actividad_id', $proceso->id)
                ->where('estatus_revision', 'pendiente')
                ->first();
                
            $interno = $cambioPendiente ? $cambioPendiente->porcentaje_propuesto : $proceso->porcentaje_oficial;
 
            return ['aprobado' => $aprobado, 'interno' => $interno];
        }
 
        // Tiene subprocesos, el avance del padre es el promedio de los subprocesos
        $sumHijosAprobado = 0;
        $sumHijosInterno = 0;
        $totalHijos = $hijos->count();
 
        foreach ($hijos as $hijo) {
            $aprobadoHijo = $hijo->porcentaje_oficial;
            
            $cambioPendienteHijo = CambioPropuesto::where('actividad_id', $hijo->id)
                ->where('estatus_revision', 'pendiente')
                ->first();
                
            $internoHijo = $cambioPendienteHijo ? $cambioPendienteHijo->porcentaje_propuesto : $hijo->porcentaje_oficial;
 
            $sumHijosAprobado += $aprobadoHijo;
            $sumHijosInterno += $internoHijo;
        }
 
        $promedioAprobado = $sumHijosAprobado / $totalHijos;
        $promedioInterno = $sumHijosInterno / $totalHijos;

        // Determinar el estatus del proceso padre a partir de sus hijos
        $hijosStatuses = $hijos->pluck('estatus_oficial')->unique()->toArray();
        if (count($hijosStatuses) === 1 && current($hijosStatuses) === 'cerrado') {
            $nuevoEstatus = 'cerrado';
        } elseif (count($hijosStatuses) === 1 && current($hijosStatuses) === 'pendiente') {
            $nuevoEstatus = 'pendiente';
        } else {
            $nuevoEstatus = 'en proceso';
        }
 
        // Actualizar la fila del proceso padre para que refleje el promedio oficial actual y su estatus
        $proceso->update([
            'porcentaje_oficial' => round($promedioAprobado),
            'estatus_oficial' => $nuevoEstatus,
        ]);
 
        return ['aprobado' => $promedioAprobado, 'interno' => $promedioInterno];
    }
 
    // Generar un token único y seguro para la vista de cliente
    public function generarTokenCliente()
    {
        if (empty($this->token_publico)) {
            $this->token_publico = \Illuminate\Support\Str::random(40);
            $this->save();
        }
        return $this->token_publico;
    }
}
