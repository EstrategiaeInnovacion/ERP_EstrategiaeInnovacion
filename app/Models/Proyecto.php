<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Proyecto extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'nombre',
        'descripcion',
        'usuario_id',
        'fecha_inicio',
        'fecha_fin',
        'recurrencia',
        'notas',
        'archivado',
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'archivado' => 'boolean',
    ];

    public function creador()
    {
        return $this->belongsTo(User::class, 'usuario_id');
    }

    public function usuarios()
    {
        return $this->belongsToMany(User::class, 'proyecto_usuarios', 'proyecto_id', 'usuario_id');
    }

    public function actividades()
    {
        return $this->hasMany(Activity::class);
    }

    public function scopeActivos($query)
    {
        return $query->where('archivado', false);
    }

    public function scopeArchivados($query)
    {
        return $query->where('archivado', true);
    }

    public function siguienteFechaJunta($desde = null)
    {
        $inicio = $desde ? Carbon::parse($desde) : Carbon::parse($this->fecha_inicio);
        $hoy = Carbon::now();

        while ($inicio->lte($hoy)) {
            switch ($this->recurrencia) {
                case 'semanal':
                    $inicio->addWeek();
                    break;
                case 'quincenal':
                    $inicio->addDays(15);
                    break;
                case 'mensual':
                    $inicio->addMonth();
                    break;
            }
        }

        return $inicio;
    }

    public function estaActivo()
    {
        $hoy = Carbon::now()->toDateString();

        return ! $this->archivado && $this->fecha_fin >= $hoy;
    }
}
