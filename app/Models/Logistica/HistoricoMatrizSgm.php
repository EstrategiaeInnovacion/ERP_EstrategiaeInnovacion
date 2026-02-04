<?php

namespace App\Models\Logistica;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class HistoricoMatrizSgm extends Model
{
    use HasFactory;

    protected $table = 'historico_matriz_sgm';

    protected $fillable = [
        'operacion_logistica_id',
        'fecha_arribo_aduana',
        'fecha_registro',
        'dias_transcurridos',
        'target_dias',
        'color_status',
        'operacion_status',
        'observaciones',
    ];

    protected $casts = [
        'fecha_arribo_aduana' => 'date',
        'fecha_registro' => 'date',
        'dias_transcurridos' => 'integer',
        'target_dias' => 'integer',
    ];

    public function operacionLogistica()
    {
        return $this->belongsTo(OperacionLogistica::class);
    }

    /**
     * Calcular el color de status basado en la fórmula de Excel
     * =SI(ESBLANCO(Q8),SI(ESBLANCO(T8),"SIN FECHA","VERDE"),SI(ESBLANCO(T8),SI(HOY()<=Q8+W8,"AMARILLO",SI(HOY()>Q8+W8,"ROJO","AMARILLO")),SI(T8<=Q8+W8,"VERDE","ROJO")))
     */
    public static function calcularColorStatus($fechaArriboAduana, $fechaModulacion, $targetDias, $operacionStatus)
    {
        $hoy = Carbon::now()->startOfDay();
        
        // Si no hay fecha de arribo a aduana
        if (!$fechaArriboAduana) {
            return !$fechaModulacion ? 'sin_fecha' : 'verde';
        }
        
        $fechaArribo = Carbon::parse($fechaArriboAduana)->startOfDay();
        $fechaLimite = $fechaArribo->copy()->addDays($targetDias);
        
        // Si no hay fecha de modulación
        if (!$fechaModulacion) {
            if ($hoy->lte($fechaLimite)) {
                return 'amarillo';
            } else {
                return 'rojo';
            }
        }
        
        // Si hay fecha de modulación
        $fechaMod = Carbon::parse($fechaModulacion)->startOfDay();
        
        if ($fechaMod->lte($fechaLimite)) {
            return 'verde';
        } else {
            return 'rojo';
        }
    }
}
