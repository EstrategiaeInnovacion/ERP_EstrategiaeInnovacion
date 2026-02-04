<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Logistica\OperacionLogistica;

class OperacionLogisticaFactory extends Factory
{
    protected $model = OperacionLogistica::class;

    public function definition(): array
    {
        return [
            // Campos de texto y enum correctos
            'ejecutivo' => $this->faker->name(), // Ya no es ID, es texto
            'operacion' => $this->faker->bothify('OP-####'),
            'cliente' => $this->faker->company(),
            'proveedor_o_cliente' => $this->faker->company(),
            'no_factura' => $this->faker->bothify('FAC-#####'),
            'tipo_carga' => 'General',
            'tipo_incoterm' => $this->faker->randomElement(['FOB', 'CIF', 'EXW', 'DDP']),
            // Nota: En la migración definimos este enum:
            'tipo_operacion_enum' => $this->faker->randomElement(['Aerea', 'Terrestre', 'Maritima', 'Ferrocarril']),
            
            'clave' => $this->faker->bothify('??#'),
            'referencia_interna' => $this->faker->bothify('REF-INT-####'),
            'aduana' => $this->faker->city(),
            'agente_aduanal' => $this->faker->name(),
            'referencia_aa' => $this->faker->bothify('REF-AA-####'),
            'no_pedimento' => $this->faker->numerify('#######'),
            'transporte' => $this->faker->company(),
            'guia_bl' => $this->faker->bothify('BL-######'),
            'puerto_salida' => $this->faker->city(),

            // Fechas
            'fecha_embarque' => $this->faker->date(),
            'fecha_arribo_aduana' => $this->faker->date(),
            'fecha_modulacion' => $this->faker->date(),
            'fecha_arribo_planta' => $this->faker->date(),

            // Métricas y Status (Nombres corregidos)
            'resultado' => $this->faker->numberBetween(0, 10),
            'target' => 7,
            'dias_transito' => $this->faker->numberBetween(5, 30),
            'dias_transcurridos_calculados' => $this->faker->numberBetween(0, 15),
            
            // Enums de status correctos
            'status_calculado' => 'In Process',
            'status_manual' => 'In Process',
            'color_status' => 'amarillo',
            
            'comentarios' => $this->faker->sentence(),
            'fecha_ultimo_calculo' => now(),
        ];
    }
}