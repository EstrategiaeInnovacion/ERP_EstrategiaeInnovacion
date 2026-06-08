<?php

namespace App\Console\Commands;

use App\Services\ComercioExterior\CatalogoExcelImportService;
use Illuminate\Console\Command;

class ImportarCatalogoCE extends Command
{
    protected $signature   = 'ce:importar-catalogo {path? : Ruta al archivo .xlsx}';
    protected $description = 'Importa el catálogo de Reglas de Origen T-MEC desde Excel';

    public function handle(CatalogoExcelImportService $importer): int
    {
        $path = $this->argument('path')
            ?? storage_path('app/private/comercio-exterior/catalogo_reglas_origen.xlsx');

        if (! file_exists($path)) {
            $this->error("Archivo no encontrado: {$path}");
            return self::FAILURE;
        }

        $this->info("Importando catálogo desde: {$path}");
        $this->info('Esto puede tardar unos segundos...');

        try {
            $result = $importer->import($path);

            $this->newLine();
            $this->table(
                ['Tabla', 'Registros importados'],
                [
                    ['Reglas de Origen',   $result['reglas_origen']],
                    ['Sección C',          $result['seccion_c']],
                    ['Reglas Automotrices',$result['reglas_automotrices']],
                    ['Apéndice – Partes',  $result['apendice_partes']],
                    ['Relaciones índice',  $result['relaciones']],
                ]
            );

            $total = array_sum(array_values($result));
            $this->info("✓ Catálogo importado exitosamente. Total de registros: {$total}");

            return self::SUCCESS;

        } catch (\Throwable $e) {
            $this->error('Error durante la importación: ' . $e->getMessage());
            $this->line($e->getTraceAsString());
            return self::FAILURE;
        }
    }
}
