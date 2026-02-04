<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Logistica\OperacionLogistica;
use App\Models\Logistica\Pedimento;
use App\Models\Logistica\PedimentoOperacion;

class VerificarPedimentosIndividuales extends Command
{
    protected $signature = 'pedimentos:verificar-individuales';
    protected $description = 'Verificar la nueva lógica de pedimentos individuales';

    public function handle()
    {
        $this->info('=== VERIFICACIÓN DE LÓGICA DE PEDIMENTOS INDIVIDUALES ===');
        $this->newLine();

        // 1. Mostrar operaciones con números de pedimento
        $this->info('1. Operaciones con números de pedimento:');
        $operaciones = OperacionLogistica::whereNotNull('no_pedimento')
            ->where('no_pedimento', '!=', '')
            ->select('id', 'clave', 'no_pedimento', 'cliente', 'ejecutivo')
            ->get();

        foreach ($operaciones as $op) {
            $this->line("   ID: {$op->id} | Clave: {$op->clave} | No. Pedimento: {$op->no_pedimento} | Cliente: {$op->cliente}");
        }

        $this->info("Total de operaciones con pedimento: " . $operaciones->count());
        $this->newLine();

        // 2. Verificar registros actuales en tabla pedimentos_operaciones (separada)
        $this->info('2. Registros actuales en tabla pedimentos_operaciones:');
        $pedimentosOperaciones = PedimentoOperacion::all();
        foreach ($pedimentosOperaciones as $ped) {
            $this->line("   ID: {$ped->id} | Clave: {$ped->clave} | No. Pedimento: {$ped->no_pedimento} | Operación ID: {$ped->operacion_logistica_id} | Estado: {$ped->estado_pago}");
        }
        $this->info("Total registros en pedimentos_operaciones: " . $pedimentosOperaciones->count());
        
        // También mostrar tabla de catálogo de pedimentos (separada)
        $this->info('3. Catálogo de claves de pedimentos (tabla separada):');
        $catalogo = Pedimento::all();
        foreach ($catalogo as $cat) {
            $this->line("   ID: {$cat->id} | Clave: {$cat->clave} | Descripción: {$cat->descripcion}");
        }
        $this->info("Total claves en catálogo: " . $catalogo->count());
        $this->newLine();

        // 4. Nueva lógica - Agrupación por clave con pedimentos individuales en tabla separada
        $this->info('4. Nueva lógica - Agrupación por clave con pedimentos individuales:');

        $clavesPedimentos = OperacionLogistica::whereNotNull('no_pedimento')
            ->where('no_pedimento', '!=', '')
            ->whereNotNull('clave')
            ->where('clave', '!=', '')
            ->select('clave')
            ->selectRaw('COUNT(*) as total_pedimentos')
            ->selectRaw('GROUP_CONCAT(DISTINCT cliente) as clientes')
            ->selectRaw('GROUP_CONCAT(DISTINCT ejecutivo) as ejecutivos')
            ->selectRaw('MIN(fecha_embarque) as primera_fecha')
            ->selectRaw('MAX(fecha_embarque) as ultima_fecha')
            ->groupBy('clave')
            ->get();

        $totalPorPagar = 0;
        $totalPagados = 0;

        foreach ($clavesPedimentos as $claveData) {
            $this->newLine();
            $this->warn("--- CLAVE: {$claveData->clave} ---");
            $this->info("Total pedimentos en esta clave: {$claveData->total_pedimentos}");
            
            // Obtener operaciones individuales de esta clave
            $operacionesIndividuales = OperacionLogistica::where('clave', $claveData->clave)
                ->whereNotNull('no_pedimento')
                ->where('no_pedimento', '!=', '')
                ->select('id', 'no_pedimento', 'cliente', 'ejecutivo')
                ->get();
            
            $pedimentosPorPagar = 0;
            $pedimentosPagados = 0;
            
            $this->info('Pedimentos individuales:');
            foreach ($operacionesIndividuales as $operacion) {
                $registroPago = PedimentoOperacion::where('no_pedimento', $operacion->no_pedimento)
                    ->where('operacion_logistica_id', $operacion->id)
                    ->first();
                
                $estado = 'pendiente'; // por defecto
                if ($registroPago) {
                    $estado = $registroPago->estado_pago;
                }
                
                if ($estado === 'pendiente') {
                    $pedimentosPorPagar++;
                } elseif ($estado === 'pagado') {
                    $pedimentosPagados++;
                }
                
                $this->line("   - No. Pedimento: {$operacion->no_pedimento} | Cliente: {$operacion->cliente} | Operación ID: {$operacion->id} | Estado: {$estado}");
            }
            
            $this->info("RESUMEN CLAVE {$claveData->clave}:");
            $this->line("   Por pagar: {$pedimentosPorPagar}");
            $this->line("   Pagados: {$pedimentosPagados}");
            $this->line("   Total: " . ($pedimentosPorPagar + $pedimentosPagados));
            
            $estadoGeneral = $pedimentosPorPagar > 0 ? 'pendiente' : ($pedimentosPagados > 0 ? 'pagado' : 'pendiente');
            $this->line("   Estado general: {$estadoGeneral}");
            
            $totalPorPagar += $pedimentosPorPagar;
            $totalPagados += $pedimentosPagados;
        }

        $this->newLine();
        $this->warn('=== ESTADÍSTICAS FINALES ===');
        $totalPedimentosIndividuales = $clavesPedimentos->sum('total_pedimentos');
        
        $this->info("Total claves: " . $clavesPedimentos->count());
        $this->info("Total pedimentos individuales: {$totalPedimentosIndividuales}");
        $this->info("Total por pagar: {$totalPorPagar}");
        $this->info("Total pagados: {$totalPagados}");

        $this->newLine();
        $this->info('=== VERIFICACIÓN COMPLETADA ===');
        
        return 0;
    }
}