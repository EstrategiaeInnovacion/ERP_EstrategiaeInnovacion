<?php

namespace App\Models\Logistica;

use Illuminate\Database\Eloquent\Model;
use App\Models\Empleado;

class ColumnaVisibleEjecutivo extends Model
{
    protected $table = 'columnas_visibles_ejecutivo';

    protected $fillable = [
        'empleado_id',
        'columna',
        'visible',
        'orden',
        'idioma_nombres'
    ];

    protected $casts = [
        'visible' => 'boolean',
        'orden' => 'integer'
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // COLUMNAS OPCIONALES (Nuevas - pueden mostrarse u ocultarse)
    // Incluye nombres en Español e Inglés
    // ═══════════════════════════════════════════════════════════════════════
    public static $columnasOpcionales = [
        // Campos que ya existían como opcionales
        'tipo_carga' => [
            'es' => 'Tipo de Carga',
            'en' => 'Load Type'
        ],
        'tipo_incoterm' => [
            'es' => 'Incoterm',
            'en' => 'Incoterm'
        ],
        'puerto_salida' => [
            'es' => 'Puerto de Salida',
            'en' => 'Port of Origin'
        ],
        // NUEVOS CAMPOS del Excel
        'in_charge' => [
            'es' => 'Responsable',
            'en' => 'In Charge'
        ],
        'proveedor' => [
            'es' => 'Proveedor',
            'en' => 'Supplier Name'
        ],
        'tipo_previo' => [
            'es' => 'Modalidad/Previo',
            'en' => 'Mode/Preview'
        ],
        'fecha_etd' => [
            'es' => 'Fecha ETD',
            'en' => 'Shipp Date (ETD)'
        ],
        'fecha_zarpe' => [
            'es' => 'Fecha de Zarpe',
            'en' => 'Shipp Date Zarpe'
        ],
        'pedimento_en_carpeta' => [
            'es' => 'Pedimento en Carpeta',
            'en' => 'Pedimento in Folder'
        ],
        'referencia_cliente' => [
            'es' => 'Referencia Cliente',
            'en' => 'REF'
        ],
        'mail_subject' => [
            'es' => 'Asunto de Correo',
            'en' => 'Mail Subject'
        ]
    ];

    // ═══════════════════════════════════════════════════════════════════════
    // COLUMNAS PREDETERMINADAS (Visibles por defecto, pero pueden ocultarse)
    // Incluye nombres en Español e Inglés
    // ═══════════════════════════════════════════════════════════════════════
    public static $columnasPredeterminadas = [
        'id' => [
            'es' => 'No.',
            'en' => 'No.'
        ],
        'ejecutivo' => [
            'es' => 'Ejecutivo',
            'en' => 'Executive'
        ],
        'operacion' => [
            'es' => 'Operación',
            'en' => 'Process'
        ],
        'cliente' => [
            'es' => 'Cliente',
            'en' => 'Customer'
        ],
        'proveedor_o_cliente' => [
            'es' => 'Proveedor o Cliente',
            'en' => 'Supplier/Customer'
        ],
        'fecha_embarque' => [
            'es' => 'Fecha de Embarque',
            'en' => 'Shipping Date'
        ],
        'no_factura' => [
            'es' => 'No. De Factura',
            'en' => 'Invoice Number'
        ],
        'tipo_operacion_enum' => [
            'es' => 'T. Operación',
            'en' => 'Op. Type'
        ],
        'clave' => [
            'es' => 'Clave',
            'en' => 'Key'
        ],
        'referencia_interna' => [
            'es' => 'Referencia Interna',
            'en' => 'Internal Ref.'
        ],
        'aduana' => [
            'es' => 'Aduana',
            'en' => 'Customs MX'
        ],
        'agente_aduanal' => [
            'es' => 'A.A',
            'en' => 'Customer Broker'
        ],
        'referencia_aa' => [
            'es' => 'Referencia A.A',
            'en' => 'Broker Ref.'
        ],
        'no_pedimento' => [
            'es' => 'No Pedimento',
            'en' => 'Pedimento No.'
        ],
        'transporte' => [
            'es' => 'Transporte',
            'en' => 'Freight'
        ],
        'fecha_arribo_aduana' => [
            'es' => 'Fecha de Arribo a Aduana',
            'en' => 'Arriving Date'
        ],
        'guia_bl' => [
            'es' => 'Guía/BL',
            'en' => 'TRACKING/BL'
        ],
        'status' => [
            'es' => 'Status',
            'en' => 'STATUS'
        ],
        'fecha_modulacion' => [
            'es' => 'Salida de Aduana',
            'en' => 'Customs Exit'
        ],
        'fecha_arribo_planta' => [
            'es' => 'Fecha de Arribo a Planta',
            'en' => 'ETA Plant/Origin'
        ],
        'resultado' => [
            'es' => 'Resultado',
            'en' => 'Result'
        ],
        'target' => [
            'es' => 'Target',
            'en' => 'Target'
        ],
        'dias_transito' => [
            'es' => 'Días en Tránsito',
            'en' => 'Transit Days'
        ],
        'post_operaciones' => [
            'es' => 'Post-Operaciones',
            'en' => 'Post-Operations'
        ],
        'comentarios' => [
            'es' => 'Comentarios',
            'en' => 'Comments'
        ]
    ];

    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }

    /**
     * Obtener las columnas visibles para un ejecutivo específico
     * Devuelve columnas opcionales visibles + columnas predeterminadas visibles
     */
    public static function getColumnasVisiblesParaEjecutivo($empleadoId)
    {
        $configuracion = self::where('empleado_id', $empleadoId)
            ->where('visible', true)
            ->pluck('columna')
            ->toArray();
        
        return $configuracion;
    }

    /**
     * Obtener las columnas predeterminadas ocultas para un ejecutivo
     */
    public static function getColumnasPredeterminadasOcultas($empleadoId)
    {
        // Si no tiene configuración guardada, ninguna está oculta
        $tieneConfiguracion = self::where('empleado_id', $empleadoId)->exists();
        if (!$tieneConfiguracion) {
            return [];
        }
        
        $columnasOcultas = self::where('empleado_id', $empleadoId)
            ->where('visible', false)
            ->whereIn('columna', array_keys(self::$columnasPredeterminadas))
            ->pluck('columna')
            ->toArray();
        
        return $columnasOcultas;
    }

    /**
     * Verificar si una columna predeterminada está visible para un ejecutivo
     */
    public static function esColumnaVisible($empleadoId, $columna)
    {
        // Si es columna predeterminada y no tiene configuración, está visible
        if (array_key_exists($columna, self::$columnasPredeterminadas)) {
            $config = self::where('empleado_id', $empleadoId)
                ->where('columna', $columna)
                ->first();
            
            // Si no hay configuración para esta columna, está visible por defecto
            if (!$config) {
                return true;
            }
            return $config->visible;
        }
        
        // Si es columna opcional
        if (array_key_exists($columna, self::$columnasOpcionales)) {
            $config = self::where('empleado_id', $empleadoId)
                ->where('columna', $columna)
                ->first();
            return $config ? $config->visible : false;
        }
        
        return true;
    }

    /**
     * Obtener el idioma configurado para un ejecutivo
     */
    public static function getIdiomaEjecutivo($empleadoId)
    {
        $config = self::where('empleado_id', $empleadoId)->first();
        return $config ? $config->idioma_nombres : 'es';
    }

    /**
     * Guardar configuración de idioma para un ejecutivo
     */
    public static function guardarIdiomaEjecutivo($empleadoId, $idioma)
    {
        self::where('empleado_id', $empleadoId)
            ->update(['idioma_nombres' => $idioma]);
        
        // Si no tiene configuración aún, crear una entrada base
        if (self::where('empleado_id', $empleadoId)->count() === 0) {
            self::create([
                'empleado_id' => $empleadoId,
                'columna' => '_config',
                'visible' => false,
                'idioma_nombres' => $idioma
            ]);
        }
    }

    /**
     * Obtener nombre de columna en el idioma especificado
     */
    public static function getNombreColumna($columna, $idioma = 'es')
    {
        // Buscar en columnas predeterminadas
        if (isset(self::$columnasPredeterminadas[$columna])) {
            return self::$columnasPredeterminadas[$columna][$idioma] ?? self::$columnasPredeterminadas[$columna]['es'];
        }
        
        // Buscar en columnas opcionales
        if (isset(self::$columnasOpcionales[$columna])) {
            return self::$columnasOpcionales[$columna][$idioma] ?? self::$columnasOpcionales[$columna]['es'];
        }
        
        return $columna;
    }

    /**
     * Obtener todas las columnas con sus nombres en el idioma especificado
     */
    public static function getTodasLasColumnasConNombres($idioma = 'es')
    {
        $resultado = [];
        
        foreach (self::$columnasPredeterminadas as $key => $nombres) {
            $resultado[$key] = $nombres[$idioma] ?? $nombres['es'];
        }
        
        foreach (self::$columnasOpcionales as $key => $nombres) {
            $resultado[$key] = $nombres[$idioma] ?? $nombres['es'];
        }
        
        return $resultado;
    }

    /**
     * Obtener solo columnas opcionales con nombres en el idioma especificado
     */
    public static function getColumnasOpcionalesConNombres($idioma = 'es')
    {
        $resultado = [];
        
        foreach (self::$columnasOpcionales as $key => $nombres) {
            $resultado[$key] = $nombres[$idioma] ?? $nombres['es'];
        }
        
        return $resultado;
    }

    /**
     * Obtener solo columnas predeterminadas con nombres en el idioma especificado
     */
    public static function getColumnasPredeterminadasConNombres($idioma = 'es')
    {
        $resultado = [];
        
        foreach (self::$columnasPredeterminadas as $key => $nombres) {
            $resultado[$key] = $nombres[$idioma] ?? $nombres['es'];
        }
        
        return $resultado;
    }

    /**
     * Guardar configuración de columnas para un ejecutivo
     * Ahora guarda tanto columnas opcionales como predeterminadas
     */
    public static function guardarConfiguracion($empleadoId, $columnasOpcionales, $idioma = null, $columnasPredeterminadasVisibles = null)
    {
        // Obtener idioma actual si no se especifica
        $idiomaActual = $idioma ?? self::getIdiomaEjecutivo($empleadoId);
        
        // Eliminar configuración anterior
        self::where('empleado_id', $empleadoId)->delete();
        
        // Guardar configuración de columnas opcionales
        foreach (self::$columnasOpcionales as $columna => $nombres) {
            self::create([
                'empleado_id' => $empleadoId,
                'columna' => $columna,
                'visible' => in_array($columna, $columnasOpcionales ?? []),
                'idioma_nombres' => $idiomaActual
            ]);
        }
        
        // Guardar configuración de columnas predeterminadas (si se especifica)
        // Si no se especifica, todas las predeterminadas están visibles por defecto
        if ($columnasPredeterminadasVisibles !== null) {
            foreach (self::$columnasPredeterminadas as $columna => $nombres) {
                self::create([
                    'empleado_id' => $empleadoId,
                    'columna' => $columna,
                    'visible' => in_array($columna, $columnasPredeterminadasVisibles),
                    'idioma_nombres' => $idiomaActual
                ]);
            }
        }
    }

    /**
     * Obtener el orden de columnas para un ejecutivo
     * Retorna un array con las columnas en su orden personalizado
     */
    public static function getOrdenColumnasEjecutivo($empleadoId)
    {
        $configuracion = self::where('empleado_id', $empleadoId)
            ->where('columna', '!=', '_config')
            ->orderBy('orden', 'asc')
            ->pluck('columna', 'orden')
            ->toArray();
        
        // Si no tiene configuración, devolver orden por defecto
        if (empty($configuracion)) {
            return array_merge(
                array_keys(self::$columnasPredeterminadas),
                array_keys(self::$columnasOpcionales)
            );
        }
        
        return array_values($configuracion);
    }

    /**
     * Obtener columnas ordenadas con visibilidad para un ejecutivo
     * Incluye predeterminadas, opcionales y campos personalizados en el orden configurado
     */
    public static function getColumnasOrdenadasParaEjecutivo($empleadoId, $idioma = 'es')
    {
        $configuracion = self::where('empleado_id', $empleadoId)
            ->where('columna', '!=', '_config')
            ->orderBy('orden', 'asc')
            ->get()
            ->keyBy('columna');
        
        // Obtener campos personalizados del ejecutivo
        $camposPersonalizados = [];
        if ($empleadoId > 0) {
            $camposPersonalizados = CampoPersonalizadoMatriz::whereHas('ejecutivos', function($q) use ($empleadoId) {
                $q->where('empleado_id', $empleadoId);
            })->where('activo', true)->get();
        } else {
            // Para admin (empleadoId = 0), obtener todos los campos activos
            $camposPersonalizados = CampoPersonalizadoMatriz::where('activo', true)->get();
        }
        
        $columnasOrdenadas = [];
        
        // Si tiene configuración de orden, usar ese orden
        if ($configuracion->isNotEmpty()) {
            foreach ($configuracion as $columna => $config) {
                // Verificar si es campo personalizado
                $esCampoPersonalizado = str_starts_with($columna, 'campo_');
                $esPredeterminada = array_key_exists($columna, self::$columnasPredeterminadas);
                $esOpcional = array_key_exists($columna, self::$columnasOpcionales);
                
                $nombre = self::getNombreColumna($columna, $idioma);
                
                // Si es campo personalizado, obtener el nombre del campo
                if ($esCampoPersonalizado) {
                    $campoId = str_replace('campo_', '', $columna);
                    $campo = $camposPersonalizados->firstWhere('id', $campoId);
                    if ($campo) {
                        $nombre = $campo->nombre;
                    }
                }
                
                $columnasOrdenadas[] = [
                    'columna' => $columna,
                    'nombre' => $nombre,
                    'visible' => $config->visible,
                    'orden' => $config->orden,
                    'predeterminada' => $esPredeterminada,
                    'opcional' => $esOpcional,
                    'personalizado' => $esCampoPersonalizado
                ];
            }
            
            // Agregar campos personalizados que no estén en la configuración
            foreach ($camposPersonalizados as $campo) {
                $columnaKey = 'campo_' . $campo->id;
                if (!isset($configuracion[$columnaKey])) {
                    $columnasOrdenadas[] = [
                        'columna' => $columnaKey,
                        'nombre' => $campo->nombre,
                        'visible' => true,
                        'orden' => count($columnasOrdenadas),
                        'predeterminada' => false,
                        'opcional' => false,
                        'personalizado' => true
                    ];
                }
            }
        } else {
            // Orden por defecto: primero predeterminadas, luego opcionales, luego personalizados
            $orden = 0;
            foreach (self::$columnasPredeterminadas as $columna => $nombres) {
                $columnasOrdenadas[] = [
                    'columna' => $columna,
                    'nombre' => $nombres[$idioma] ?? $nombres['es'],
                    'visible' => true,
                    'orden' => $orden++,
                    'predeterminada' => true,
                    'opcional' => false,
                    'personalizado' => false
                ];
            }
            foreach (self::$columnasOpcionales as $columna => $nombres) {
                $columnasOrdenadas[] = [
                    'columna' => $columna,
                    'nombre' => $nombres[$idioma] ?? $nombres['es'],
                    'visible' => false,
                    'orden' => $orden++,
                    'predeterminada' => false,
                    'opcional' => true,
                    'personalizado' => false
                ];
            }
            // Agregar campos personalizados al final
            foreach ($camposPersonalizados as $campo) {
                $columnasOrdenadas[] = [
                    'columna' => 'campo_' . $campo->id,
                    'nombre' => $campo->nombre,
                    'visible' => true,
                    'orden' => $orden++,
                    'predeterminada' => false,
                    'opcional' => false,
                    'personalizado' => true
                ];
            }
        }
        
        // Ordenar por el campo orden
        usort($columnasOrdenadas, function($a, $b) {
            return $a['orden'] <=> $b['orden'];
        });
        
        return $columnasOrdenadas;
    }

    /**
     * Guardar el orden de columnas para un ejecutivo
     */
    public static function guardarOrdenColumnas($empleadoId, $ordenColumnas)
    {
        $idiomaActual = self::getIdiomaEjecutivo($empleadoId);
        
        foreach ($ordenColumnas as $index => $columna) {
            self::updateOrCreate(
                [
                    'empleado_id' => $empleadoId,
                    'columna' => $columna
                ],
                [
                    'orden' => $index,
                    'idioma_nombres' => $idiomaActual
                ]
            );
        }
    }

    /**
     * Guardar configuración completa de columnas (visibilidad y orden)
     */
    public static function guardarConfiguracionCompleta($empleadoId, $columnas, $idioma = null)
    {
        $idiomaActual = $idioma ?? self::getIdiomaEjecutivo($empleadoId);
        
        foreach ($columnas as $index => $columnaData) {
            self::updateOrCreate(
                [
                    'empleado_id' => $empleadoId,
                    'columna' => $columnaData['columna']
                ],
                [
                    'visible' => $columnaData['visible'] ?? false,
                    'orden' => $columnaData['orden'] ?? $index,
                    'idioma_nombres' => $idiomaActual
                ]
            );
        }
    }
}
