<?php
 
namespace App\Models\Auditoria;
 
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;
 
class ActividadAuditoria extends Model
{
    use SoftDeletes;
 
    protected $table = 'auditoria_actividades';
 
    protected $fillable = [
        'proyecto_id',
        'padre_id',
        'orden',
        'actividad',
        'responsable',
        'plazo',
        'estatus_oficial',
        'porcentaje_oficial',
        'estatus_published',
        'porcentaje_published',
        'comentarios',
        'es_proceso_principal',
    ];
 
    protected $casts = [
        'plazo' => 'date',
        'es_proceso_principal' => 'boolean',
    ];
 
    // Relación con Proyecto
    public function proyecto()
    {
        return $this->belongsTo(ProyectoAuditoria::class, 'proyecto_id');
    }
 
    // Relación con Actividad Padre (Proceso)
    public function padre()
    {
        return $this->belongsTo(ActividadAuditoria::class, 'padre_id');
    }
 
    // Relación con Actividades Hijas (Subprocesos)
    public function subprocesos()
    {
        return $this->hasMany(ActividadAuditoria::class, 'padre_id')->orderBy('orden');
    }
 

 
    // Comentarios asociados a la actividad
    public function comentariosList()
    {
        return $this->hasMany(ComentarioAuditoria::class, 'actividad_id');
    }
 
    // Propuestas de cambios asociadas a la actividad
    public function cambiosPropuestos()
    {
        return $this->hasMany(CambioPropuesto::class, 'actividad_id');
    }
 
    // Helper para verificar si la actividad tiene subprocesos
    public function tieneSubprocesos(): bool
    {
        return $this->subprocesos()->exists();
    }
}
