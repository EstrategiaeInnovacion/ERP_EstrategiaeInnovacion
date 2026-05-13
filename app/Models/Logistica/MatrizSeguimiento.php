<?php

namespace App\Models\Logistica;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class MatrizSeguimiento extends Model
{
    protected $table = 'matriz_seguimiento';

    protected $fillable = [
        'user_id',
        'ref_interna', 'proveedor_cliente', 'factura', 'impo_ex',
        'tipo_operacion', 'transporte', 'naviera', 'buque', 'carga_tipo',
        'no_contenedor', 'tipo_contenedor', 'aduana', 'clave',
        'pedimento', 'bl_guia', 'etd', 'eta', 'dias_libres', 'previo',
        'cita_despacho', 'arribo_planta', 'status', 'resultado',
        'target', 'comentarios',
    ];

    const CARGA_TIPOS = ['FCL', 'LCL'];

    const TIPOS_CONTENEDOR = ['20\' ST', '40\' ST', '40\' HC', '45\' HC', '20\' RF', '40\' RF', 'Open Top', 'Flat Rack'];

    protected $casts = [
        'etd'           => 'date',
        'eta'           => 'date',
        'previo'        => 'date',
        'cita_despacho' => 'date',
        'arribo_planta' => 'date',
    ];

    const TIPOS_OPERACION = ['Marítimo', 'Aéreo', 'Terrestre', 'Ferroviario'];

    const STATUSES = [
        'Pendiente', 'En Tránsito', 'En Aduana', 'Previo Programado',
        'Cita Programada', 'Despachado', 'Entregado', 'Cancelado',
    ];

    const RESULTADOS = ['En Proceso', 'Exitoso', 'Demorado', 'Cancelado'];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function historial()
    {
        return $this->hasMany(MatrizSeguimientoComentario::class, 'matriz_seguimiento_id')->orderBy('created_at', 'desc');
    }
}
