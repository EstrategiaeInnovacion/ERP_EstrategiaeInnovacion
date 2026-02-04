<?php

namespace App\Http\Requests\Logistica;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOperacionRequest extends FormRequest
{
    /**
     * Determina si el usuario está autorizado.
     */
    public function authorize(): bool
    {
        return true; 
    }

    /**
     * Reglas de validación.
     */
    public function rules(): array
    {
        // TRUCO SENIOR:
        // Obtenemos el ID de la operación que viene en la URL (ej: /logistica/operaciones/15)
        // Esto sirve para decirle a la BD: "Verifica que sea único, EXCEPTO para este ID (15)".
        // Verifica en tu archivo de rutas si el parámetro se llama {id} o {operacion}
        $id = $this->route('id'); 

        return [
            // Datos Generales
            'operacion'           => 'required|in:EXPORTACION,IMPORTACION',
            'tipo_operacion_enum' => 'required|in:Terrestre,Aerea,Maritima,Ferrocarril',
            'cliente_id'          => 'required|exists:clientes,id',
            'ejecutivo_id'        => 'required|exists:users,id',
            
            // Fechas
            'fecha_embarque'      => 'required|date',
            'eta'                 => 'nullable|date',
            
            // Referencias
            'referencia_cliente'  => 'nullable|string|max:100',
            
            // EJEMPLO DE CÓMO VALIDAR UNIQUE IGNORANDO EL ID ACTUAL
            // Si tuvieras que validar que el pedimento sea único:
            // 'pedimento' => 'nullable|string|max:20|unique:operacion_logisticas,pedimento,' . $id,
            'pedimento'           => 'nullable|string|max:20',
            
            // Arrays o JSON
            'mercancias'          => 'nullable|array',
            
            // Configuración
            'mail_subject'        => 'nullable|string|max:255',
        ];
    }

    public function attributes(): array
    {
        return [
            'cliente_id' => 'Cliente',
            'tipo_operacion_enum' => 'Tipo de Operación',
        ];
    }
}