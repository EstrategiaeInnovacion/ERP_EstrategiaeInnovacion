<?php
 
namespace App\Models\Auditoria;
 
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
 
class CambioPropuesto extends Model
{
    protected $table = 'auditoria_cambios_propuestos';
 
    protected $fillable = [
        'actividad_id',
        'proyecto_id',
        'padre_id',
        'user_id',
        'tipo_cambio',
        'actividad_nombre_propuesto',
        'responsable_propuesto',
        'estatus_propuesto',
        'porcentaje_propuesto',
        'comentario_propuesto',
        'comentario_visible_cliente',
        'es_importante',
        'estatus_revision',
        'motivo_rechazo',
        'revisado_por',
        'fecha_revision',
    ];
 
    protected $casts = [
        'comentario_visible_cliente' => 'boolean',
        'es_importante' => 'boolean',
        'fecha_revision' => 'datetime',
    ];
 
    // Relación con Actividad (si aplica, null para nuevos subprocesos)
    public function actividad()
    {
        return $this->belongsTo(ActividadAuditoria::class, 'actividad_id');
    }
 
    // Relación con Proyecto
    public function proyecto()
    {
        return $this->belongsTo(ProyectoAuditoria::class, 'proyecto_id');
    }
 
    // Relación con Actividad Padre (si se propone un nuevo subproceso)
    public function padre()
    {
        return $this->belongsTo(ActividadAuditoria::class, 'padre_id');
    }
 
    // Quién propuso el cambio (Analista)
    public function proponente()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
 
    // Quién revisó el cambio (Coordinador)
    public function revisor()
    {
        return $this->belongsTo(User::class, 'revisado_por');
    }
}
