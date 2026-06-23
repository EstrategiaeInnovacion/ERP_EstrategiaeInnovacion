<?php
 
namespace App\Models\Auditoria;
 
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
 
class ComentarioAuditoria extends Model
{
    protected $table = 'auditoria_comentarios';
 
    protected $fillable = [
        'actividad_id',
        'user_id',
        'comentario',
        'visible_cliente',
        'es_importante',
    ];
 
    protected $casts = [
        'visible_cliente' => 'boolean',
        'es_importante' => 'boolean',
    ];
 
    // Relación con Actividad
    public function actividad()
    {
        return $this->belongsTo(ActividadAuditoria::class, 'actividad_id');
    }
 
    // Relación con Autor (Usuario)
    public function autor()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
