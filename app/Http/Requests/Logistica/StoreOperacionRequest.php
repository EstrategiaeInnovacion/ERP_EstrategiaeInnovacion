<?php

namespace App\Http\Requests\Logistica;

use Illuminate\Foundation\Http\FormRequest;

class StoreOperacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // 1. Campos Obligatorios (Core)
            'operacion'           => 'required|in:EXPORTACION,IMPORTACION',
            'tipo_operacion_enum' => 'required|in:Terrestre,Aerea,Maritima,Ferrocarril',
            
            // IMPORTANTE: Según tu modelo, estos son textos, no IDs
            'cliente'             => 'required|string|max:255', 
            'ejecutivo'           => 'required|string|max:255',
            'transporte'          => 'nullable|string|max:255',
            'agente_aduanal'      => 'nullable|string|max:255',

            // 2. Fechas
            'fecha_embarque'      => 'required|date',
            'fecha_etd'           => 'nullable|date',
            'fecha_zarpe'         => 'nullable|date',
            'eta'                 => 'nullable|date',

            // 3. Referencias y Datos Aduanales
            'referencia_cliente'  => 'nullable|string|max:100',
            'no_pedimento'        => 'nullable|string|max:20',
            'aduana'              => 'nullable|string|max:100',
            'tipo_incoterm'       => 'nullable|string|max:50',
            
            // 4. Configuración
            'mail_subject'        => 'nullable|string|max:500',
            
            // Si manejas arrays de mercancías o extras
            'mercancias'          => 'nullable|array',
        ];
    }

    public function attributes(): array
    {
        return [
            'tipo_operacion_enum' => 'Tipo de Operación',
            'no_pedimento'        => 'Número de Pedimento',
            'fecha_etd'           => 'Fecha ETD',
        ];
    }
}