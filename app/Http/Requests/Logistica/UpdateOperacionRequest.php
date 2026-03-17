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
        return [
            // Datos Generales — mismos campos que envía el formulario modal
            'operacion'           => 'required|in:EXPORTACION,IMPORTACION',
            'tipo_operacion_enum' => 'required|in:Terrestre,Aerea,Maritima,Ferrocarril',
            'cliente'             => 'required|string|max:255',
            'ejecutivo'           => 'required|string|max:255',
            'transporte'          => 'nullable|string|max:255',
            'agente_aduanal'      => 'nullable|string|max:255',
            'proveedor_o_cliente' => 'nullable|string|max:255',
            'proveedor'           => 'nullable|string|max:255',

            // Fechas
            'fecha_embarque'      => 'required|date',
            'fecha_etd'           => 'nullable|date',
            'fecha_zarpe'         => 'nullable|date',
            'fecha_arribo_aduana' => 'nullable|date',
            'fecha_modulacion'    => 'nullable|date',
            'fecha_arribo_planta' => 'nullable|date',

            // Referencias y Aduanal
            'referencia_cliente'  => 'nullable|string|max:100',
            'referencia_interna'  => 'nullable|string|max:100',
            'referencia_aa'       => 'nullable|string|max:100',
            'clave'               => 'nullable|string|max:100',
            'no_factura'          => 'nullable|string|max:100',
            'no_pedimento'        => 'nullable|string|max:20',
            'guia_bl'             => 'nullable|string|max:100',
            'aduana'              => 'nullable|string|max:100',
            'tipo_incoterm'       => 'nullable|string|max:50',
            'tipo_carga'          => 'nullable|string|max:50',
            'puerto_salida'       => 'nullable|string|max:100',
            'in_charge'           => 'nullable|string|max:255',
            'tipo_previo'         => 'nullable|string|max:100',
            'pedimento_en_carpeta'=> 'nullable|boolean',

            // Métricas
            'target'              => 'nullable|integer|min:0',
            'dias_transito'       => 'nullable|integer|min:0',
            'resultado'           => 'nullable|integer|min:0',

            // Configuración
            'mail_subject'        => 'nullable|string|max:500',
            'comentarios'         => 'nullable|string|max:2000',

            // Status — campo clave para marcar como Completado
            'status_manual'       => 'nullable|string|in:,In Process,Done,Out of Metric',
        ];
    }

    public function attributes(): array
    {
        return [
            'cliente'             => 'Cliente',
            'ejecutivo'           => 'Ejecutivo',
            'tipo_operacion_enum' => 'Tipo de Operación',
            'fecha_embarque'      => 'Fecha de Embarque',
        ];
    }
}