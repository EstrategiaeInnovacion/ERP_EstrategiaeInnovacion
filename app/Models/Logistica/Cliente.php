<?php

namespace App\Models\Logistica;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Empleado;

class Cliente extends Model
{
    use HasFactory;

    protected $table = 'clientes';

    protected $fillable = [
        'cliente',
        'ejecutivo_asignado_id',
        'correos',
        'periodicidad_reporte',
        'fecha_carga_excel',
    ];

    protected $casts = [
        'correos' => 'array',
        'fecha_carga_excel' => 'datetime',
    ];

    public function ejecutivoAsignado()
    {
        return $this->belongsTo(Empleado::class, 'ejecutivo_asignado_id');
    }

    // Método helper para obtener correos como string separados por comas
    public function getCorreosStringAttribute()
    {
        return $this->correos ? implode(', ', $this->correos) : '';
    }

    // Método para agregar un correo
    public function addCorreo($correo)
    {
        $correos = $this->correos ?? [];
        if (!in_array($correo, $correos)) {
            $correos[] = $correo;
            $this->correos = $correos;
        }
    }

    // Nota: La relación con operaciones_logisticas fue eliminada porque 
    // la tabla usa un campo de texto 'cliente' en lugar de cliente_id foreign key
}
