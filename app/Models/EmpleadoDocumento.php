<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmpleadoDocumento extends Model
{
    use HasFactory;

    // Nombre de la tabla que creamos en la migración
    protected $table = 'empleado_documentos';

    protected $fillable = [
        'empleado_id',
        'nombre',           // Ej: INE, Contrato
        'categoria',        // Ej: Legal, Identificación
        'ruta_archivo',     // Path en storage
        'fecha_vencimiento' // Para las alertas
    ];

    // Casteamos la fecha para que Carbon la maneje automático (formatos, sumas, etc)
    protected $casts = [
        'fecha_vencimiento' => 'date',
    ];

    /**
     * Relación inversa: Un documento pertenece a un empleado.
     */
    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }
}