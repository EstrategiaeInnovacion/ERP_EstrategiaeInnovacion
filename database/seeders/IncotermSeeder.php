<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Logistica\Incoterm;

class IncotermSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Catálogo de Incoterms según estándares internacionales
     */
    public function run(): void
    {
        $incoterms = [
            // Grupo E - Salida
            [
                'codigo' => 'EXW',
                'nombre' => 'EXW - En Fábrica',
                'descripcion' => 'EXW - EN FABRICA (LUGAR CONVENIDO).',
                'grupo' => 'E',
                'orden' => 1,
            ],
            // Grupo F - Transporte principal no pagado
            [
                'codigo' => 'FCA',
                'nombre' => 'FCA - Franco Transportista',
                'descripcion' => 'FCA - FRANCO TRANSPORTISTA (LUGAR DESIGNADO).',
                'grupo' => 'F',
                'orden' => 2,
            ],
            [
                'codigo' => 'FAS',
                'nombre' => 'FAS - Franco al Costado del Buque',
                'descripcion' => 'FAS - FRANCO AL COSTADO DEL BUQUE (PUERTO DE CARGA CONVENIDO).',
                'grupo' => 'F',
                'orden' => 3,
            ],
            [
                'codigo' => 'FOB',
                'nombre' => 'FOB - Franco a Bordo',
                'descripcion' => 'FOB - FRANCO A BORDO (PUERTO DE CARGA CONVENIDO).',
                'grupo' => 'F',
                'orden' => 4,
            ],
            // Grupo C - Transporte principal pagado
            [
                'codigo' => 'CFR',
                'nombre' => 'CFR - Coste y Flete',
                'descripcion' => 'CFR - COSTE Y FLETE (PUERTO DE DESTINO CONVENIDO).',
                'grupo' => 'C',
                'orden' => 5,
            ],
            [
                'codigo' => 'CIF',
                'nombre' => 'CIF - Coste, Seguro y Flete',
                'descripcion' => 'CIF - COSTE, SEGURO Y FLETE (PUERTO DE DESTINO CONVENIDO).',
                'grupo' => 'C',
                'orden' => 6,
            ],
            [
                'codigo' => 'CPT',
                'nombre' => 'CPT - Transporte Pagado Hasta',
                'descripcion' => 'CPT - TRANSPORTE PAGADO HASTA (EL LUGAR DE DESTINO CONVENIDO).',
                'grupo' => 'C',
                'orden' => 7,
            ],
            [
                'codigo' => 'CIP',
                'nombre' => 'CIP - Transporte y Seguro Pagados Hasta',
                'descripcion' => 'CIP - TRANSPORTE Y SEGURO PAGADOS HASTA (LUGAR DE DESTINO CONVENIDO).',
                'grupo' => 'C',
                'orden' => 8,
            ],
            // Grupo D - Llegada
            [
                'codigo' => 'DAP',
                'nombre' => 'DAP - Entregada en Lugar',
                'descripcion' => 'DAP - ENTREGADA EN LUGAR.',
                'grupo' => 'D',
                'orden' => 9,
            ],
            [
                'codigo' => 'DPU',
                'nombre' => 'DPU - Entregada y Descargada',
                'descripcion' => 'DPU - ENTREGADA Y DESCARGADA EN EL LUGAR ACORDADO.',
                'grupo' => 'D',
                'orden' => 10,
            ],
            [
                'codigo' => 'DDP',
                'nombre' => 'DDP - Entregada Derechos Pagados',
                'descripcion' => 'DDP - ENTREGADA DERECHOS PAGADOS (LUGAR DE DESTINO CONVENIDO).',
                'grupo' => 'D',
                'orden' => 11,
            ],
        ];

        foreach ($incoterms as $incoterm) {
            Incoterm::updateOrCreate(
                ['codigo' => $incoterm['codigo']],
                [
                    'nombre' => $incoterm['nombre'],
                    'descripcion' => $incoterm['descripcion'],
                    'grupo' => $incoterm['grupo'],
                    'orden' => $incoterm['orden'],
                    'aplicable_importacion' => true,
                    'aplicable_exportacion' => true,
                    'activo' => true
                ]
            );
        }

        $this->command->info('✓ ' . count($incoterms) . ' Incoterms creados/actualizados correctamente.');
    }
}
