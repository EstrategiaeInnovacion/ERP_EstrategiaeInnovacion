<?php

namespace App\Models\Logistica;

use Illuminate\Database\Eloquent\Model;
use App\Models\Empleado;

class CampoPersonalizadoMatriz extends Model
{
    protected $table = 'campos_personalizados_matriz';

    /**
     * Tipos de campos disponibles
     */
    const TIPOS = [
        'texto' => ['nombre' => 'Texto corto', 'icono' => '📝'],
        'descripcion' => ['nombre' => 'Descripción (multilínea)', 'icono' => '📄'],
        'numero' => ['nombre' => 'Número entero', 'icono' => '🔢'],
        'decimal' => ['nombre' => 'Número decimal', 'icono' => '💲'],
        'moneda' => ['nombre' => 'Moneda', 'icono' => '💰'],
        'fecha' => ['nombre' => 'Fecha', 'icono' => '📅'],
        'booleano' => ['nombre' => 'Sí/No', 'icono' => '✅'],
        'selector' => ['nombre' => 'Selector (una opción)', 'icono' => '📋'],
        'multiple' => ['nombre' => 'Opción múltiple', 'icono' => '☑️'],
        'email' => ['nombre' => 'Correo electrónico', 'icono' => '📧'],
        'telefono' => ['nombre' => 'Teléfono', 'icono' => '📞'],
        'url' => ['nombre' => 'URL/Enlace', 'icono' => '🔗'],
    ];

    protected $fillable = [
        'nombre',
        'tipo',
        'opciones',
        'configuracion',
        'requerido',
        'activo',
        'orden',
        'mostrar_despues_de',
    ];

    protected $casts = [
        'activo' => 'boolean',
        'requerido' => 'boolean',
        'orden' => 'integer',
        'opciones' => 'array',
        'configuracion' => 'array',
    ];

    /**
     * Los ejecutivos asignados a este campo personalizado
     */
    public function ejecutivos()
    {
        return $this->belongsToMany(Empleado::class, 'campo_personalizado_ejecutivo', 'campo_personalizado_id', 'empleado_id');
    }

    /**
     * Valores de este campo en las operaciones
     */
    public function valores()
    {
        return $this->hasMany(ValorCampoPersonalizado::class, 'campo_personalizado_id');
    }

    /**
     * Scope para campos activos
     */
    public function scopeActivos($query)
    {
        return $query->where('activo', true);
    }

    /**
     * Scope ordenado
     */
    public function scopeOrdenado($query)
    {
        return $query->orderBy('orden');
    }

    /**
     * Obtener tipos disponibles
     */
    public static function getTipos()
    {
        return self::TIPOS;
    }

    /**
     * Validar valor según el tipo de campo
     */
    public function validarValor($valor)
    {
        if (empty($valor) && !$this->requerido) {
            return true;
        }

        switch ($this->tipo) {
            case 'email':
                return filter_var($valor, FILTER_VALIDATE_EMAIL) !== false;
            case 'url':
                return filter_var($valor, FILTER_VALIDATE_URL) !== false;
            case 'numero':
                return is_numeric($valor) && floor($valor) == $valor;
            case 'decimal':
            case 'moneda':
                return is_numeric($valor);
            case 'telefono':
                return preg_match('/^[\d\s\-\+\(\)]+$/', $valor);
            case 'booleano':
                return in_array($valor, ['0', '1', 0, 1, true, false, 'si', 'no', 'yes', 'no'], true);
            case 'selector':
                return in_array($valor, $this->opciones ?? []);
            case 'multiple':
                $valores = is_array($valor) ? $valor : json_decode($valor, true);
                if (!is_array($valores)) return false;
                return empty(array_diff($valores, $this->opciones ?? []));
            default:
                return true;
        }
    }
}
