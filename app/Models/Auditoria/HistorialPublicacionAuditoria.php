<?php
 
namespace App\Models\Auditoria;
 
use Illuminate\Database\Eloquent\Model;
use App\Models\User;
 
class HistorialPublicacionAuditoria extends Model
{
    protected $table = 'auditoria_historial_publicaciones';
 
    protected $fillable = [
        'proyecto_id',
        'user_id',
        'avance_publicado',
        'fase_publicada',
        'detalles',
    ];
 
    protected $casts = [
        'detalles' => 'array',
    ];
 
    // Relación con Proyecto
    public function proyecto()
    {
        return $this->belongsTo(ProyectoAuditoria::class, 'proyecto_id');
    }
 
    // Relación con Publicador (Usuario)
    public function publicador()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
