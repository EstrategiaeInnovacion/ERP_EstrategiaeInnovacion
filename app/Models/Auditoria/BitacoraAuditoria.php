<?php
 
namespace App\Models\Auditoria;
 
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
 
class BitacoraAuditoria extends Model
{
    protected $table = 'auditoria_bitacora';
 
    protected $fillable = [
        'proyecto_id',
        'actividad_id',
        'user_id',
        'accion',
        'campo',
        'valor_anterior',
        'valor_nuevo',
        'comentario',
    ];
 
    // Relación con Proyecto
    public function proyecto()
    {
        return $this->belongsTo(ProyectoAuditoria::class, 'proyecto_id');
    }
 
    // Relación con Actividad (si aplica)
    public function actividad()
    {
        return $this->belongsTo(ActividadAuditoria::class, 'actividad_id');
    }
 
    // Relación con Usuario (quién realizó la acción)
    public function usuario()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
 
    // Helper para registrar una acción de forma rápida
    public static function registrar($proyectoId, $accion, $actividadId = null, $campo = null, $anterior = null, $nuevo = null, $comentario = null)
    {
        return self::create([
            'proyecto_id' => $proyectoId,
            'actividad_id' => $actividadId,
            'user_id' => auth()->id() ?? 1, // Fallback a usuario ID 1 (ej. consola o seeder)
            'accion' => $accion,
            'campo' => $campo,
            'valor_anterior' => $anterior,
            'valor_nuevo' => $nuevo,
            'comentario' => $comentario,
        ]);
    }
}
