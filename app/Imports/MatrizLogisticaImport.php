<?php

namespace App\Imports;

use App\Models\Logistica\OperacionLogistica;
use App\Models\Logistica\CampoPersonalizadoMatriz;
use App\Models\Logistica\ValorCampoPersonalizado;
use App\Models\Logistica\ColumnaVisibleEjecutivo;
use App\Models\Empleado;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class MatrizLogisticaImport
{
    protected $empleadoId;
    protected $columnasActivadas = [];
    protected $camposPersonalizadosCreados = [];

    public function __construct($empleadoId = null)
    {
        $this->empleadoId = $empleadoId;
    }

    /**
     * Importar archivo Excel
     */
    public function import($filePath)
    {
        Log::info('Iniciando importación de Excel', ['archivo' => $filePath]);
        
        $spreadsheet = IOFactory::load($filePath);
        
        // Obtener la primera hoja (Matriz de Operación)
        $sheet = $spreadsheet->getSheet(0);
        
        // Obtener los datos como array
        $data = $sheet->toArray(null, true, true, true);
        
        if (empty($data)) {
            throw new \Exception('El archivo Excel está vacío');
        }

        // La primera fila son los encabezados
        $headers = array_shift($data);
        
        Log::info('Encabezados originales del Excel', ['headers' => $headers]);
        
        $headersSlug = array_map(function($h) {
            return Str::slug(trim($h ?? ''), '_');
        }, $headers);
        
        Log::info('Encabezados convertidos a slug', ['headers' => $headersSlug]);

        $this->processRows($headersSlug, $data, $headers);

        return $this;
    }

    /**
     * Procesar las filas del Excel
     */
    protected function processRows($headers, $rows, $headersOriginales = [])
    {
        $mapaColumnas = $this->getMapaColumnas();
        $columnasOpcionales = $this->getColumnasOpcionales();

        // Obtener campos personalizados existentes
        $camposPersonalizadosDb = CampoPersonalizadoMatriz::all();
        $camposPorSlug = [];
        foreach ($camposPersonalizadosDb as $campo) {
            $slug = Str::slug($campo->nombre, '_');
            $camposPorSlug[$slug] = $campo;
        }
        
        $operacionesImportadas = 0;

        foreach ($rows as $index => $row) {
            try {
                // Combinar headers con valores
                $rowData = [];
                foreach ($headers as $letter => $header) {
                    if (!empty($header)) {
                        $rowData[$header] = $row[$letter] ?? null;
                    }
                }
                
                if ($index === 0) {
                    Log::info('Primera fila de datos', ['rowData' => $rowData]);
                }

                // Buscar folio de manera más flexible
                $folio = null;
                $posiblesFolios = ['no_folio', 'folio', 'no_folio_', 'no_de_folio', 'numero_folio', 'num_folio'];
                
                foreach ($posiblesFolios as $key) {
                    if (!empty($rowData[$key])) {
                        $folio = $rowData[$key];
                        Log::info('Folio encontrado', ['key' => $key, 'folio' => $folio]);
                        break;
                    }
                }
                
                // Si no se encontró, buscar cualquier columna que contenga "folio"
                if (empty($folio)) {
                    foreach ($rowData as $key => $valor) {
                        if (stripos($key, 'folio') !== false && !empty($valor)) {
                            $folio = $valor;
                            Log::info('Folio encontrado por búsqueda', ['key' => $key, 'folio' => $folio]);
                            break;
                        }
                    }
                }
                
                if (empty($folio)) {
                    Log::warning('Fila sin folio, saltando', ['fila' => $index + 2, 'headers_disponibles' => array_keys($rowData)]);
                    continue;
                }
                
                $operacionesImportadas++;

                $datosPrincipales = [];
                $datosExtra = [];

                foreach ($rowData as $header => $valor) {
                    if (empty($header) || $valor === null || $valor === '') continue;

                    if (array_key_exists($header, $mapaColumnas)) {
                        $columnaDb = $mapaColumnas[$header];
                        $valorTransformado = $this->transformarValor($columnaDb, $valor);

                        if ($valorTransformado !== null && $valorTransformado !== '') {
                            $datosPrincipales[$columnaDb] = $valorTransformado;
                        }

                        if (in_array($columnaDb, $columnasOpcionales) && !empty($valorTransformado)) {
                            $this->columnasActivadas[$columnaDb] = true;
                        }
                    } else {
                        if (!empty($valor)) {
                            $datosExtra[$header] = [
                                'header_original' => $header,
                                'valor' => $valor
                            ];
                        }
                    }
                }

                if (empty($datosPrincipales['folio'])) {
                    $datosPrincipales['folio'] = $folio;
                }

                // Guardar operación
                $operacion = OperacionLogistica::updateOrCreate(
                    ['folio' => $datosPrincipales['folio']],
                    $datosPrincipales
                );

                // Procesar campos personalizados
                foreach ($datosExtra as $slugHeader => $data) {
                    $campo = $camposPorSlug[$slugHeader] ?? null;

                    if (!$campo) {
                        $campo = $this->crearCampoPersonalizado($data['header_original'], $slugHeader);
                        if ($campo) {
                            $camposPorSlug[$slugHeader] = $campo;
                        }
                    }

                    if ($campo) {
                        ValorCampoPersonalizado::updateOrCreate(
                            [
                                'operacion_logistica_id' => $operacion->id,
                                'campo_personalizado_id' => $campo->id,
                            ],
                            [
                                'valor' => $data['valor']
                            ]
                        );
                    }
                }

            } catch (\Exception $e) {
                Log::error('Error importando fila', [
                    'fila' => $index + 2,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        Log::info('Importación completada', [
            'operaciones_importadas' => $operacionesImportadas,
            'columnas_activadas' => array_keys($this->columnasActivadas)
        ]);

        // Activar columnas para el ejecutivo
        if ($this->empleadoId) {
            $this->activarColumnasParaEjecutivo();
            $this->asignarCamposPersonalizadosAEjecutivo();
        }
    }

    /**
     * Mapa de columnas Excel a BD
     */
    private function getMapaColumnas(): array
    {
        return [
            // Folio - varias formas
            'no_folio' => 'folio',
            'no_folio_' => 'folio',
            'folio' => 'folio',
            'no_de_folio' => 'folio',
            'numero_folio' => 'folio',
            'num_folio' => 'folio',
            
            // Operación
            'process' => 'operacion',
            'operacion' => 'operacion',
            
            // Cliente
            'customer' => 'cliente',
            'cliente' => 'cliente',
            
            // Agente aduanal
            'customer_broker' => 'agente_aduanal',
            'agente_aduanal' => 'agente_aduanal',
            
            // Tipo previo
            'modalidad_previo' => 'tipo_previo',
            'tipo_previo' => 'tipo_previo',
            
            // Factura
            'invoice_number' => 'no_factura',
            'no_factura' => 'no_factura',
            'factura' => 'no_factura',
            'numero_factura' => 'no_factura',
            
            // Proveedor
            'supplier_name' => 'proveedor',
            'proveedor' => 'proveedor',
            
            // Aduana
            'customs_mx' => 'aduana',
            'aduana' => 'aduana',
            
            // Responsable
            'in_charge' => 'in_charge',
            'responsable' => 'in_charge',
            'encargado' => 'in_charge',
            
            // Ejecutivo
            'ejecutivo' => 'ejecutivo',
            
            // Tipo de operación
            'freight' => 'tipo_operacion_enum',
            'tipo_operacion' => 'tipo_operacion_enum',
            
            // BL/Guía
            'trackingbl' => 'guia_bl',
            'tracking_bl' => 'guia_bl',
            'guia_bl' => 'guia_bl',
            'bl' => 'guia_bl',
            'tracking' => 'guia_bl',
            'guia' => 'guia_bl',
            
            // Fechas ETD
            'shipp_date_etd' => 'fecha_etd',
            'fecha_etd' => 'fecha_etd',
            'etd' => 'fecha_etd',
            
            // Fecha zarpe
            'shipp_date_zarpe' => 'fecha_zarpe',
            'fecha_zarpe' => 'fecha_zarpe',
            'zarpe' => 'fecha_zarpe',
            
            // Fecha arribo/ETA
            'arriving_date' => 'fecha_arribo_aduana',
            'eta' => 'fecha_arribo_aduana',
            'fecha_eta' => 'fecha_arribo_aduana',
            'fecha_arribo' => 'fecha_arribo_aduana',
            'fecha_arribo_aduana' => 'fecha_arribo_aduana',
            
            // Salida de aduana
            'salida_de_aduana' => 'fecha_modulacion',
            'fecha_modulacion' => 'fecha_modulacion',
            
            // Arribo planta
            'eta_planta_origen' => 'fecha_arribo_planta',
            'fecha_arribo_planta' => 'fecha_arribo_planta',
            
            // Status
            'status' => 'status_manual',
            'estatus' => 'status_manual',
            'estado' => 'status_manual',
            
            // Pedimento
            'pedimento' => 'no_pedimento',
            'no_pedimento' => 'no_pedimento',
            'numero_pedimento' => 'no_pedimento',
            
            // Referencias
            'ref' => 'referencia_cliente',
            'referencia_cliente' => 'referencia_cliente',
            'referencia' => 'referencia_cliente',
            
            // Mail subject
            'mail_subject' => 'mail_subject',
            
            // Pedimento en carpeta
            'pedimento_en_carpeta' => 'pedimento_en_carpeta',
            
            // Transporte
            'transporte' => 'transporte',
            
            // Referencia AA
            'referencia_aa' => 'referencia_aa',
            
            // Puerto salida
            'puerto_salida' => 'puerto_salida',
            'puerto' => 'puerto_salida',
            
            // Tipo carga
            'tipo_carga' => 'tipo_carga',
            'carga' => 'tipo_carga',
            
            // Incoterm
            'tipo_incoterm' => 'tipo_incoterm',
            'incoterm' => 'tipo_incoterm',
            
            // Comentarios
            'comentarios' => 'comentarios',
            'notas' => 'comentarios',
            
            // Target
            'target' => 'target',
            
            // Fecha embarque
            'fecha_embarque' => 'fecha_embarque',
            'embarque' => 'fecha_embarque',
        ];
    }

    private function getColumnasOpcionales(): array
    {
        return [
            'tipo_carga', 'tipo_incoterm', 'puerto_salida', 'in_charge',
            'proveedor', 'tipo_previo', 'fecha_etd', 'fecha_zarpe',
            'pedimento_en_carpeta', 'referencia_cliente', 'mail_subject',
        ];
    }

    private function transformarValor($columna, $valor)
    {
        if ($valor === null || $valor === '') return null;

        // Fechas
        if (Str::contains($columna, 'fecha') || in_array($columna, ['fecha_etd', 'fecha_zarpe', 'fecha_arribo_aduana', 'fecha_modulacion', 'fecha_arribo_planta', 'fecha_embarque'])) {
            return $this->transformarFecha($valor);
        }

        // Tipo operación
        if ($columna === 'tipo_operacion_enum') {
            $map = ['SEA' => 'Maritima', 'AIR' => 'Aerea', 'LAND' => 'Terrestre', 'RAIL' => 'Ferrocarril'];
            return $map[strtoupper($valor)] ?? $valor;
        }

        // Status
        if ($columna === 'status_manual') {
            $map = ['IN PROCESS' => 'In Process', 'DONE' => 'Done', 'OUT OF METRIC' => 'Out of Metric'];
            return $map[strtoupper($valor)] ?? 'In Process';
        }

        // Booleano
        if ($columna === 'pedimento_en_carpeta') {
            return in_array(strtoupper($valor), ['SI', 'SÍ', 'YES', '1', 'TRUE', 'X']);
        }

        return is_string($valor) ? trim($valor) : $valor;
    }

    private function transformarFecha($valor)
    {
        if (empty($valor)) return null;
        try {
            if (is_numeric($valor)) {
                return Date::excelToDateTimeObject($valor)->format('Y-m-d');
            }
            return Carbon::parse($valor)->format('Y-m-d');
        } catch (\Exception $e) {
            return null;
        }
    }

    private function crearCampoPersonalizado($nombreOriginal, $slug)
    {
        try {
            $tipo = 'texto';
            $nombreLower = strtolower($nombreOriginal);
            if (Str::contains($nombreLower, ['fecha', 'date'])) $tipo = 'fecha';
            elseif (Str::contains($nombreLower, ['monto', 'precio', 'costo'])) $tipo = 'moneda';
            elseif (Str::contains($nombreLower, ['cantidad', 'numero'])) $tipo = 'numero';

            $campo = CampoPersonalizadoMatriz::firstOrCreate(
                ['nombre' => $nombreOriginal],
                ['tipo' => $tipo, 'activo' => true, 'requerido' => false, 'orden' => CampoPersonalizadoMatriz::max('orden') + 1]
            );

            $this->camposPersonalizadosCreados[] = ['id' => $campo->id, 'nombre' => $nombreOriginal, 'tipo' => $tipo];
            return $campo;
        } catch (\Exception $e) {
            Log::error('Error creando campo', ['nombre' => $nombreOriginal, 'error' => $e->getMessage()]);
            return null;
        }
    }

    private function activarColumnasParaEjecutivo()
    {
        if (!$this->empleadoId || empty($this->columnasActivadas)) return;

        foreach (array_keys($this->columnasActivadas) as $columna) {
            ColumnaVisibleEjecutivo::updateOrCreate(
                ['empleado_id' => $this->empleadoId, 'columna' => $columna],
                ['visible' => true]
            );
        }
    }

    private function asignarCamposPersonalizadosAEjecutivo()
    {
        if (!$this->empleadoId || empty($this->camposPersonalizadosCreados)) return;

        foreach ($this->camposPersonalizadosCreados as $campoData) {
            $campo = CampoPersonalizadoMatriz::find($campoData['id']);
            if ($campo && !$campo->ejecutivos()->where('empleado_id', $this->empleadoId)->exists()) {
                $campo->ejecutivos()->attach($this->empleadoId);
            }
        }
    }

    public function getColumnasActivadas() { return array_keys($this->columnasActivadas); }
    public function getCamposPersonalizadosCreados() { return $this->camposPersonalizadosCreados; }
}
