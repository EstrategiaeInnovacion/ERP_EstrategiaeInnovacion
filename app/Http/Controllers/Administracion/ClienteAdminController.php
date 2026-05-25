<?php

namespace App\Http\Controllers\Administracion;

use App\Http\Controllers\Controller;
use App\Models\Administracion\Cliente;
use App\Models\Administracion\PerfilCliente;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ClienteAdminController extends Controller
{
    public function index()
    {
        $clientes = Cliente::with('perfil')->orderBy('nombre')->get();
        return view('Administracion.clientes', compact('clientes'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'nombre_legal' => 'required|string|max:255',
        ], ['nombre_legal.required' => 'El Nombre Legal de la Empresa es obligatorio.']);

        $cliente = Cliente::create([
            'nombre'   => $request->nombre_legal,
            'empresa'  => $request->nombre_legal,
            'contacto' => $request->informante_nombre,
            'correo'   => null,
            'telefono' => null,
            'notas'    => null,
        ]);

        $this->guardarPerfil($cliente, $request);

        return response()->json(['success' => true, 'message' => 'Cliente creado correctamente.']);
    }

    public function update(Request $request, Cliente $cliente)
    {
        $request->validate([
            'nombre_legal' => 'required|string|max:255',
        ], ['nombre_legal.required' => 'El Nombre Legal de la Empresa es obligatorio.']);

        $cliente->update([
            'nombre'   => $request->nombre_legal,
            'empresa'  => $request->nombre_legal,
            'contacto' => $request->informante_nombre,
        ]);

        $this->guardarPerfil($cliente, $request);

        return response()->json(['success' => true, 'message' => 'Cliente actualizado.']);
    }

    public function destroy(Cliente $cliente)
    {
        $cliente->delete();
        return response()->json(['success' => true, 'message' => 'Cliente eliminado.']);
    }

    public function exportarPlantilla()
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);

        $this->crearHojaInstrucciones($spreadsheet);

        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Cuestionario');

        $this->buildFormulario($sheet);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        return response()->stream(
            function () use ($writer) { $writer->save('php://output'); },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="plantilla-perfil-cliente.xlsx"',
            ]
        );
    }

    public function importarExcel(Request $request)
    {
        $request->validate([
            'archivo' => 'required|file|mimes:xlsx,xls',
        ]);

        try {
            $file = $request->file('archivo');
            $spreadsheet = IOFactory::load($file->getPathname());
            $sheet = $spreadsheet->getSheetByName('Cuestionario') ?? $spreadsheet->getActiveSheet();
            $rows = $sheet->toArray();

            $reverseLabels = $this->reverseLabels();

            $resultados = [];
            $errores = [];

            // Buscar filas con datos en la columna A
            $data = [];
            $inSection = false;

            foreach ($rows as $idx => $row) {
                $pregunta = trim($row[0] ?? '');
                $respuesta = $row[1] ?? '';

                // Detectar secciones (están en MAYÚSCULAS y no tienen label)
                $esSeccion = !isset($reverseLabels[$pregunta]) && !empty($pregunta);

                if ($esSeccion) {
                    $inSection = true;
                    continue;
                }

                if (empty($pregunta)) {
                    continue;
                }

                // Mapear por label
                if (isset($reverseLabels[$pregunta])) {
                    $campo = $reverseLabels[$pregunta];
                    $data[$campo] = $respuesta;
                }
            }

            $nombreLegal = trim($data['nombre_legal'] ?? '');

            if (empty($nombreLegal)) {
                return response()->json(['success' => false, 'message' => 'No se encontró "Nombre Legal de la Empresa" en el archivo.'], 422);
            }

            try {
                $cliente = Cliente::updateOrCreate(
                    ['nombre' => $nombreLegal],
                    [
                        'nombre'   => $nombreLegal,
                        'empresa'  => $nombreLegal,
                        'contacto' => $data['informante_nombre'] ?? null,
                        'correo'   => null,
                        'telefono' => null,
                        'notas'    => null,
                    ]
                );

                $this->guardarPerfilDesdeExcel($cliente, $data);

                $resultados[] = "✓ {$nombreLegal}";
            } catch (\Exception $e) {
                $errores[] = "✗ {$nombreLegal}: " . $e->getMessage();
            }

            $mensaje = count($resultados) . ' cliente(s) procesado(s).';
            if ($errores) {
                $mensaje .= ' ' . count($errores) . ' error(es).';
            }

            return response()->json([
                'success'   => empty($errores),
                'message'   => $mensaje,
                'resultados' => $resultados,
                'errores'    => $errores,
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => 'Error al leer el archivo: ' . $e->getMessage()], 422);
        }
    }

    public function descargarReporteErrores(Request $request)
    {
        $errores = json_decode($request->input('errores', '[]'), true);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Errores');
        $sheet->setCellValue('A1', 'Error');
        $sheet->getStyle('A1')->getFont()->setBold(true);

        foreach ($errores as $i => $err) {
            $sheet->setCellValue('A' . ($i + 2), $err);
        }

        $sheet->getColumnDimension('A')->setAutoSize(true);

        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');

        return response()->stream(
            function () use ($writer) { $writer->save('php://output'); },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="errores-importacion.xlsx"',
            ]
        );
    }

    // ── Privados ──

    private function guardarPerfil(Cliente $cliente, Request $request): void
    {
        $booleans = $this->getBooleanFields();

        $data = ['cliente_id' => $cliente->id];

        foreach ($booleans as $b) {
            $data[$b] = $request->boolean($b);
        }

        $fields = $this->getTextFieldDefinitions();

        foreach ($fields as $f) {
            $val = $request->input($f);
            $data[$f] = ($val === '' || $val === null) ? null : $val;
        }

        PerfilCliente::updateOrCreate(['cliente_id' => $cliente->id], $data);
    }

    private function guardarPerfilDesdeExcel(Cliente $cliente, array $data): void
    {
        $booleans = $this->getBooleanFields();

        $perfil = ['cliente_id' => $cliente->id];

        foreach ($booleans as $b) {
            $raw = $data[$b] ?? 'No';

            if (is_bool($raw)) {
                $perfil[$b] = $raw;
            } elseif (is_string($raw)) {
                $trimmed = trim(mb_strtolower($raw));
                $perfil[$b] = in_array($trimmed, ['si', 'sí', 'yes', '1', 'true', 'x'], true);
            } else {
                $perfil[$b] = (bool) $raw;
            }
        }

        $fields = $this->getTextFieldDefinitions();

        foreach ($fields as $f) {
            $val = $data[$f] ?? '';
            $perfil[$f] = ($val === '' || $val === null) ? null : $val;
        }

        PerfilCliente::updateOrCreate(['cliente_id' => $cliente->id], $perfil);
    }

    private function getBooleanFields(): array
    {
        return [
            'partes_relacionadas_extranjero','registro_marca','poliza_seguro_mercancias',
            'tiene_immex','tiene_immex_servicios','es_maquiladora',
            'maquiladora_servicios','tiene_prosec','transferencias_otras_immex',
            'empresa_certificada_oea','empresa_certificada_iva_eps','tiene_ctpat','utiliza_regla_octava',
            'automotriz_deposito_fiscal','proveedor_autopartes','utiliza_almacen_fiscal',
            'utiliza_regla_2','estudio_precios_transferencia','estudio_valoracion_aduanera',
            'importa_mercancias_nom','proveedores_sub_maquila','importa_precios_estimados',
            'importa_permisos_avisos','certificados_origen_tlcan','certificados_origen_tlcue',
            'exporta_eua_canada','exporta_union_europea','emite_certificados_eua_canada',
            'emite_certificados_union_europea','recibe_info_agentes_aduanales',
            'manual_procedimientos_ce','auditado_shcp_se','importa_fuera_tlcan',
        ];
    }

    private function getTextFieldDefinitions(): array
    {
        return [
            'nombre_legal','sectores_productivos','fecha_inicio_operaciones',
            'nombre_corporativo','ciudad_estado_pais_corporativo',
            'immex_fecha','immex_servicios_fecha','maquiladora_fecha','maquiladora_servicios_fecha',
            'prosec_fecha','oea_fecha','iva_eps_modalidad','iva_eps_fecha','ctpat_fecha',
            'automotriz_fecha','nom_tipo','destino_desperdicios',
            'sistema_manufactura_erp','sistema_anexo_24',
            'ultima_auditoria_interna','ultima_auditoria_externa','principales_hallazgos',
            'auditado_shcp_se_fecha','observaciones_multas',
            'pedimentos_anuales_importacion','pedimentos_anuales_exportacion',
            'aduana_principal_importacion','aduana_principal_exportacion',
            'proveedores_extranjeros_cantidad','pais_origen_importaciones',
            'importa_fuera_tlcan_paises','clientes_extranjeros_cantidad','pais_destino_exportaciones',
            'insumos_importacion_importantes','productos_exportacion_representativos',
            'informante_nombre','informante_puesto','informante_fecha',
        ];
    }

    // ── Reverse label map (label → field) ──

    private function reverseLabels(): array
    {
        static $map = null;
        if ($map !== null) return $map;
        $map = [];
        // Generar desde getBooleanFields y getTextFieldDefinitions
        $todos = array_merge($this->getBooleanFields(), $this->getTextFieldDefinitions());
        foreach ($todos as $f) {
            $l = $this->label($f);
            $map[$l] = $f;
        }
        return $map;
    }

    // ── Labels legibles para el Excel ──

    private function label(string $field): string
    {
        $labels = [
            'nombre_legal'                      => 'Nombre Legal de la Empresa *',
            'sectores_productivos'              => 'Sectores Productivos',
            'fecha_inicio_operaciones'          => 'Fecha de Inicio de Operaciones',
            'partes_relacionadas_extranjero'    => 'Opera con partes relacionadas en el extranjero',
            'nombre_corporativo'                => 'Nombre del Corporativo',
            'ciudad_estado_pais_corporativo'    => 'Ciudad, Estado y País del Corporativo',
            'registro_marca'                    => 'Cuenta con registro de marca',
            'poliza_seguro_mercancias'          => 'Cuenta con póliza de seguro de mercancías',
            'tiene_immex'                       => 'Programa IMMEX Industrial',
            'immex_fecha'                       => '  └ Fecha IMMEX Industrial',
            'tiene_immex_servicios'             => 'Programa IMMEX de Servicios',
            'immex_servicios_fecha'             => '  └ Fecha IMMEX Servicios',
            'es_maquiladora'                    => 'Registrada como Maquiladora',
            'maquiladora_fecha'                 => '  └ Fecha Maquiladora',
            'maquiladora_servicios'             => 'Maquiladora de Servicios',
            'maquiladora_servicios_fecha'       => '  └ Fecha Maquiladora Servicios',
            'tiene_prosec'                      => 'Programa PROSEC',
            'prosec_fecha'                      => '  └ Fecha PROSEC',
            'transferencias_otras_immex'        => 'Transferencias de operación virtual',
            'empresa_certificada_oea'           => 'Empresa Certificada OEA',
            'oea_fecha'                         => '  └ Fecha OEA',
            'empresa_certificada_iva_eps'       => 'Empresa Certificada IVA/EPS',
            'iva_eps_modalidad'                 => '  └ Modalidad IVA/EPS',
            'iva_eps_fecha'                     => '  └ Fecha IVA/EPS',
            'tiene_ctpat'                       => 'Registro CT-PAT',
            'ctpat_fecha'                       => '  └ Fecha CT-PAT',
            'utiliza_regla_octava'              => 'Utiliza Regla Octava',
            'automotriz_deposito_fiscal'        => 'Autorización Automotriz (Depósito Fiscal)',
            'automotriz_fecha'                  => '  └ Fecha Automotriz',
            'proveedor_autopartes'              => 'Proveedor de la Industria Automotriz (Autopartes)',
            'utiliza_almacen_fiscal'            => 'Depósito Fiscal / Recinto Fiscalizado Estratégico',
            'utiliza_regla_2'                   => 'Regla 2° para importación de líneas de producción',
            'estudio_precios_transferencia'     => 'Estudio de Precios de Transferencia',
            'estudio_valoracion_aduanera'       => 'Estudio de Valoración Aduanera',
            'importa_mercancias_nom'            => 'Importa Mercancías Sujetas a NOM',
            'nom_tipo'                          => '  └ Tipo de NOM',
            'proveedores_sub_maquila'           => 'Proveedores de Sub Maquila / Sub Manufactura',
            'importa_precios_estimados'         => 'Importa Mercancías a Precios Estimados',
            'importa_permisos_avisos'           => 'Importa con Permisos o Avisos de Importación',
            'destino_desperdicios'              => 'Destino de los desperdicios',
            'certificados_origen_tlcan'         => 'Certificados de Origen T-MEC (Importación)',
            'certificados_origen_tlcue'         => 'Certificados de Origen TLCUEN (Importación)',
            'exporta_eua_canada'                => 'Exporta a EUA y Canadá',
            'exporta_union_europea'             => 'Exporta a la Unión Europea',
            'emite_certificados_eua_canada'     => 'Emite Certificados de Origen a EUA/Canadá',
            'emite_certificados_union_europea'  => 'Emite Certificados de Origen a la UE',
            'sistema_manufactura_erp'           => 'Nombre del sistema de Manufactura (ERP)',
            'sistema_anexo_24'                  => 'Nombre del sistema de Anexo 24',
            'recibe_info_agentes_aduanales'     => 'Recibe información electrónica de Agentes Aduanales',
            'manual_procedimientos_ce'          => 'Manual de Procedimientos de Comercio Exterior',
            'ultima_auditoria_interna'          => 'Fecha de última auditoría interna',
            'ultima_auditoria_externa'          => 'Fecha de última auditoría externa',
            'principales_hallazgos'             => 'Principales Hallazgos',
            'auditado_shcp_se'                  => 'Auditado por SHCP / SE en Comercio Exterior',
            'auditado_shcp_se_fecha'            => '  └ Fecha auditoría SHCP/SE',
            'observaciones_multas'              => 'Observaciones y multas',
            'pedimentos_anuales_importacion'    => 'Pedimentos anuales de importación',
            'pedimentos_anuales_exportacion'    => 'Pedimentos anuales de exportación',
            'aduana_principal_importacion'      => 'Aduanas principales de importación',
            'aduana_principal_exportacion'      => 'Aduanas principales de exportación',
            'proveedores_extranjeros_cantidad'  => 'Cantidad de proveedores extranjeros',
            'pais_origen_importaciones'         => 'País de origen más representativo',
            'importa_fuera_tlcan'               => 'Importa materiales fuera de T-MEC / TLCUEN',
            'importa_fuera_tlcan_paises'        => '  └ Países',
            'clientes_extranjeros_cantidad'     => 'Cantidad de clientes extranjeros',
            'pais_destino_exportaciones'        => 'País de destino más frecuente',
            'insumos_importacion_importantes'   => 'Insumos de importación más importantes',
            'productos_exportacion_representativos' => 'Productos de exportación más representativos',
            'informante_nombre'                 => 'Nombre del informante',
            'informante_puesto'                 => 'Puesto del informante',
            'informante_fecha'                  => 'Fecha de información',
        ];

        return $labels[$field] ?? $field;
    }

    private function isBooleanField(string $field): bool
    {
        return in_array($field, $this->getBooleanFields(), true);
    }

    // ── Secciones del formulario ──

    private function getSeccionesFormulario(): array
    {
        return [
            'DATOS GENERALES DE LA EMPRESA' => [
                'nombre_legal',
                'sectores_productivos',
                'fecha_inicio_operaciones',
                'partes_relacionadas_extranjero',
                'nombre_corporativo',
                'ciudad_estado_pais_corporativo',
                'registro_marca',
                'poliza_seguro_mercancias',
            ],
            'PERFIL DE LA EMPRESA' => [
                'tiene_immex', 'immex_fecha',
                'tiene_immex_servicios', 'immex_servicios_fecha',
                'es_maquiladora', 'maquiladora_fecha',
                'maquiladora_servicios', 'maquiladora_servicios_fecha',
                'tiene_prosec', 'prosec_fecha',
                'transferencias_otras_immex',
                'empresa_certificada_oea', 'oea_fecha',
                'empresa_certificada_iva_eps', 'iva_eps_modalidad', 'iva_eps_fecha',
                'tiene_ctpat', 'ctpat_fecha',
                'utiliza_regla_octava',
                'automotriz_deposito_fiscal', 'automotriz_fecha',
                'proveedor_autopartes',
                'utiliza_almacen_fiscal',
                'utiliza_regla_2',
                'estudio_precios_transferencia',
                'estudio_valoracion_aduanera',
                'importa_mercancias_nom', 'nom_tipo',
                'proveedores_sub_maquila',
                'importa_precios_estimados',
                'importa_permisos_avisos',
                'destino_desperdicios',
                'certificados_origen_tlcan',
                'certificados_origen_tlcue',
                'exporta_eua_canada',
                'exporta_union_europea',
                'emite_certificados_eua_canada',
                'emite_certificados_union_europea',
            ],
            'SISTEMAS DE INFORMACIÓN' => [
                'sistema_manufactura_erp',
                'sistema_anexo_24',
                'recibe_info_agentes_aduanales',
            ],
            'MANUALES' => [
                'manual_procedimientos_ce',
            ],
            'ANTECEDENTES' => [
                'ultima_auditoria_interna',
                'ultima_auditoria_externa',
                'principales_hallazgos',
                'auditado_shcp_se',
                'auditado_shcp_se_fecha',
                'observaciones_multas',
            ],
            'VOLUMEN DE OPERACIONES' => [
                'pedimentos_anuales_importacion',
                'pedimentos_anuales_exportacion',
                'aduana_principal_importacion',
                'aduana_principal_exportacion',
            ],
            'PROVEEDORES Y CLIENTES' => [
                'proveedores_extranjeros_cantidad',
                'pais_origen_importaciones',
                'importa_fuera_tlcan',
                'importa_fuera_tlcan_paises',
                'clientes_extranjeros_cantidad',
                'pais_destino_exportaciones',
                'insumos_importacion_importantes',
                'productos_exportacion_representativos',
            ],
            'INFORMACIÓN DEL INFORMANTE' => [
                'informante_nombre',
                'informante_puesto',
                'informante_fecha',
            ],
        ];
    }

    // ── Estilos reutilizables ──

    private function applyTitleStyle(Worksheet $sheet, int $row): void
    {
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("A{$row}", 'Cuestionario de Perfil de Comercio Exterior');
        $sheet->getStyle("A{$row}")->getFont()->setName('Calibri')->setBold(true)->setSize(16)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1E40AF');
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($row)->setRowHeight(38);
    }

    private function applySubtitleStyle(Worksheet $sheet, int $row): void
    {
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("A{$row}", 'Llena los campos en la columna B (celdas amarillas). Para Sí/No usa la lista desplegable.');
        $sheet->getStyle("A{$row}")->getFont()->setName('Calibri')->setSize(9)->setItalic(true)->getColor()->setARGB('FF64748B');
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension($row)->setRowHeight(18);
    }

    private function applyColumnHeaderStyle(Worksheet $sheet, int $row): void
    {
        $sheet->setCellValue("A{$row}", 'Pregunta');
        $sheet->setCellValue("B{$row}", 'Respuesta');
        foreach (['A', 'B'] as $c) {
            $sheet->getStyle("{$c}{$row}")->getFont()->setName('Calibri')->setBold(true)->setSize(10)->getColor()->setARGB('FFFFFFFF');
            $sheet->getStyle("{$c}{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF334155');
            $sheet->getStyle("{$c}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER)->setVertical(Alignment::VERTICAL_CENTER);
            $sheet->getStyle("{$c}{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        }
        $sheet->getRowDimension($row)->setRowHeight(24);
    }

    private function applySectionStyle(Worksheet $sheet, int $row, string $titulo): void
    {
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue("A{$row}", "  {$titulo}");
        $sheet->getStyle("A{$row}")->getFont()->setName('Calibri')->setBold(true)->setSize(10)->getColor()->setARGB('FFFFFFFF');
        $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF475569');
        $sheet->getStyle("A{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT)->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getStyle("A{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getRowDimension($row)->setRowHeight(24);
    }

    private function applyFieldStyle(Worksheet $sheet, int $row, string $label): void
    {
        $sheet->setCellValue("A{$row}", $label);

        $esObligatorio = str_contains($label, '*');
        $fontColor = $esObligatorio ? 'FF991B1B' : 'FF1E293B';
        $fontBold = $esObligatorio;

        $sheet->getStyle("A{$row}")->getFont()->setName('Calibri')->setSize(10)->setBold($fontBold)->getColor()->setARGB($fontColor);
        $sheet->getStyle("A{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("A{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setIndent(1);

        $bgColor = ($row % 2 === 0) ? 'FFF1F5F9' : 'FFFFFFFF';
        $sheet->getStyle("A{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB($bgColor);

        $sheet->getRowDimension($row)->setRowHeight(20);
    }

    private function applyAnswerStyle(Worksheet $sheet, int $row, bool $esBooleano): void
    {
        $sheet->setCellValue("B{$row}", '');
        $sheet->getStyle("B{$row}")->getFont()->setName('Calibri')->setSize(10);
        $sheet->getStyle("B{$row}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFEF9C3');
        $sheet->getStyle("B{$row}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        $sheet->getStyle("B{$row}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER)->setIndent(1);

        if ($esBooleano) {
            $validation = $sheet->getCell("B{$row}")->getDataValidation();
            $validation->setType(\PhpOffice\PhpSpreadsheet\Cell\DataValidation::TYPE_LIST);
            $validation->setFormula1('"Sí,No"');
            $validation->setAllowBlank(true);
            $validation->setShowDropDown(true);

            $rt = new RichText();
            $rt->createText('Selecciona Sí o No de la lista.');
            $sheet->getComment("B{$row}", '')->setText($rt);
        }
    }

    // ── Excel helpers ──

    private function crearHojaInstrucciones(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Instrucciones');

        // Header
        $sheet->mergeCells('A1:C1');
        $sheet->setCellValue('A1', '📋 Instrucciones');
        $sheet->getStyle('A1')->getFont()->setName('Calibri')->setBold(true)->setSize(18)->getColor()->setARGB('FF1E40AF');
        $sheet->getStyle('A1')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension('1')->setRowHeight(36);

        // Subtle underline
        $sheet->mergeCells('A2:C2');
        $sheet->setCellValue('A2', 'Guía rápida para llenar el cuestionario de Perfil de Comercio Exterior');
        $sheet->getStyle('A2')->getFont()->setName('Calibri')->setSize(11)->getColor()->setARGB('FF64748B');
        $sheet->getStyle('A2')->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        $sheet->getRowDimension('2')->setRowHeight(20);

        $pasos = [
            ['Paso', 'Acción', 'Detalle'],
            ['1', 'Ve a la hoja "Cuestionario"', 'Está en la parte inferior del archivo.'],
            ['2', 'Lee la pregunta', 'En la columna A encontrarás cada pregunta.'],
            ['3', 'Responde en la celda amarilla', 'Columna B → Escribe o selecciona tu respuesta.'],
            ['4', 'Sí / No', 'Usa la lista desplegable que aparece en la celda.'],
            ['5', 'Fechas', 'Usa formato YYYY-MM-DD (ej. 2026-05-25).'],
            ['6', 'Campo obligatorio', 'El campo "Nombre Legal de la Empresa" es obligatorio.'],
            ['7', 'Guarda el archivo', 'Guarda en tu computadora y súbelo a la plataforma.'],
        ];

        $headerStyle = $sheet->getStyle('A4:C4');
        $headerStyle->getFont()->setName('Calibri')->setBold(true)->setSize(10)->getColor()->setARGB('FFFFFFFF');
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF334155');
        $headerStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        foreach ($pasos as $i => $rowData) {
            $r = 4 + $i;
            foreach (['A', 'B', 'C'] as $j => $col) {
                $sheet->setCellValue("{$col}{$r}", $rowData[$j]);
                if ($i > 0) {
                    $sheet->getStyle("{$col}{$r}")->getFont()->setName('Calibri')->setSize(10);
                    $sheet->getStyle("{$col}{$r}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                    $sheet->getStyle("{$col}{$r}")->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
                    if ($i % 2 === 0) {
                        $sheet->getStyle("{$col}{$r}")->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF8FAFC');
                    }
                }
            }
        }

        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(35);
        $sheet->getColumnDimension('C')->setWidth(55);
    }

    private function buildFormulario(Worksheet $sheet): void
    {
        $sheet->getPageSetup()->setOrientation(\PhpOffice\PhpSpreadsheet\Worksheet\PageSetup::ORIENTATION_LANDSCAPE);
        $sheet->getPageSetup()->setFitToWidth(1);

        $row = 1;
        $this->applyTitleStyle($sheet, $row++);
        $this->applySubtitleStyle($sheet, $row++);
        $this->applyColumnHeaderStyle($sheet, $row++);

        $secciones = $this->getSeccionesFormulario();

        foreach ($secciones as $titulo => $campos) {
            $this->applySectionStyle($sheet, $row, $titulo);
            $row++;

            foreach ($campos as $campo) {
                $label = $this->label($campo);
                $esBooleano = $this->isBooleanField($campo);

                $this->applyFieldStyle($sheet, $row, $label);
                $this->applyAnswerStyle($sheet, $row, $esBooleano);

                $row++;
            }

            // Espacio post-sección
            $sheet->getRowDimension($row)->setRowHeight(6);
            $row++;
        }

        // ── Footer informativo ──
        $lastRow = $row;
        $sheet->mergeCells("A{$lastRow}:B{$lastRow}");
        $sheet->setCellValue("A{$lastRow}", 'Estrategia e Innovación — Cuestionario de Perfil de Comercio Exterior');
        $sheet->getStyle("A{$lastRow}")->getFont()->setName('Calibri')->setSize(8)->setItalic(true)->getColor()->setARGB('FF94A3B8');
        $sheet->getStyle("A{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->getColumnDimension('A')->setWidth(52);
        $sheet->getColumnDimension('B')->setWidth(32);

        // ── Congelar paneles ──
        $sheet->freezePane('A4');

        // ── Proteger ──
        $sheet->getProtection()->setSheet(true);
        $sheet->getProtection()->setSort(false);
        $sheet->getProtection()->setInsertRows(false);
        $sheet->getProtection()->setDeleteRows(false);

        // Desproteger solo columna B (editable)
        $dataEnd = $row - 2;
        $sheet->getStyle("B4:B{$dataEnd}")->getProtection()->setLocked(\PhpOffice\PhpSpreadsheet\Style\Protection::PROTECTION_UNPROTECTED);
    }
}
