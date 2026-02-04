<?php

namespace App\Services;

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Style\Font;
use PhpOffice\PhpWord\Style\Paragraph;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Shared\Converter;
use App\Models\Logistica\OperacionLogistica;
use Illuminate\Support\Facades\Storage;

class WordDocumentService
{
    private $phpWord;
    
    public function __construct()
    {
        $this->phpWord = new PhpWord();
        
        // Configuración por defecto del documento
        $this->phpWord->getSettings()->setThemeFontLang(new \PhpOffice\PhpWord\Style\Language('es-ES'));
        $this->phpWord->setDefaultFontName('Arial');
        $this->phpWord->setDefaultFontSize(11);
    }
    
    /**
     * Crear reporte de operación logística
     */
    public function crearReporteOperacion(OperacionLogistica $operacion)
    {
        // Crear nueva sección
        $section = $this->phpWord->addSection([
            'marginTop' => Converter::cmToTwip(2.5),
            'marginBottom' => Converter::cmToTwip(2.5),
            'marginLeft' => Converter::cmToTwip(2.0),
            'marginRight' => Converter::cmToTwip(2.0),
        ]);
        
        // Título del documento
        $section->addText(
            'REPORTE DE OPERACIÓN LOGÍSTICA',
            ['size' => 16, 'bold' => true],
            ['alignment' => 'center', 'spaceBefore' => 0, 'spaceAfter' => 240]
        );
        
        // Información básica
        $this->agregarSeccionInformacionBasica($section, $operacion);
        
        // Detalles de la operación
        $this->agregarSeccionDetalles($section, $operacion);
        
        // Post-operaciones si las hay
        if ($operacion->postOperaciones && $operacion->postOperaciones->count() > 0) {
            $this->agregarSeccionPostOperaciones($section, $operacion);
        }
        
        // Historial de cambios
        $this->agregarSeccionHistorial($section, $operacion);
        
        return $this;
    }
    
    /**
     * Agregar sección de información básica
     */
    private function agregarSeccionInformacionBasica($section, OperacionLogistica $operacion)
    {
        $section->addText('INFORMACIÓN BÁSICA', ['size' => 14, 'bold' => true], ['spaceBefore' => 240]);
        
        // Crear tabla para información básica
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '006699',
            'cellMargin' => 80,
            'width' => 100 * 50, // 100% width
            'unit' => \PhpOffice\PhpWord\Style\Table::WIDTH_PERCENT
        ]);
        
        // Encabezado de la tabla
        $table->addRow();
        $table->addCell(4000, ['bgColor' => 'E6F3FF'])->addText('Campo', ['bold' => true]);
        $table->addCell(6000, ['bgColor' => 'E6F3FF'])->addText('Valor', ['bold' => true]);
        
        // Datos básicos
        $datos = [
            'Número de Operación' => $operacion->numero_operacion ?? 'N/A',
            'Cliente' => $operacion->cliente ?? 'N/A',
            'Proveedor' => $operacion->proveedor ?? 'N/A',
            'Fecha de Registro' => $operacion->created_at ? $operacion->created_at->format('d/m/Y H:i') : 'N/A',
            'Status Actual' => $operacion->status ?? 'N/A',
            'Días Transcurridos' => $operacion->dias_transcurridos ?? 'N/A',
            'Target (días)' => $operacion->target ?? 'N/A',
            'Tipo de Operación' => $operacion->tipo_operacion ?? 'N/A',
        ];
        
        foreach ($datos as $campo => $valor) {
            $table->addRow();
            $table->addCell(4000)->addText($campo);
            $table->addCell(6000)->addText($valor);
        }
    }
    
    /**
     * Agregar sección de detalles
     */
    private function agregarSeccionDetalles($section, OperacionLogistica $operacion)
    {
        $section->addText('DETALLES DE LA OPERACIÓN', ['size' => 14, 'bold' => true], ['spaceBefore' => 240]);
        
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '006699',
            'cellMargin' => 80,
            'width' => 100 * 50,
            'unit' => \PhpOffice\PhpWord\Style\Table::WIDTH_PERCENT
        ]);
        
        // Encabezado
        $table->addRow();
        $table->addCell(4000, ['bgColor' => 'E6F3FF'])->addText('Campo', ['bold' => true]);
        $table->addCell(6000, ['bgColor' => 'E6F3FF'])->addText('Valor', ['bold' => true]);
        
        // Detalles específicos
        $detalles = [
            'Pedimento' => $operacion->pedimento ?? 'N/A',
            'Contenedor' => $operacion->contenedor ?? 'N/A',
            'BL/AWB' => $operacion->bl_awb ?? 'N/A',
            'Fecha ETA' => $operacion->fecha_eta ? \Carbon\Carbon::parse($operacion->fecha_eta)->format('d/m/Y') : 'N/A',
            'Fecha de Entrada Aduana' => $operacion->fecha_entrada_aduana ? \Carbon\Carbon::parse($operacion->fecha_entrada_aduana)->format('d/m/Y') : 'N/A',
            'Naviera/Línea Aérea' => $operacion->naviera_linea_aerea ?? 'N/A',
            'Agente Aduanal' => $operacion->agente_aduanal ?? 'N/A',
        ];
        
        foreach ($detalles as $campo => $valor) {
            $table->addRow();
            $table->addCell(4000)->addText($campo);
            $table->addCell(6000)->addText($valor);
        }
    }
    
    /**
     * Agregar sección de post-operaciones
     */
    private function agregarSeccionPostOperaciones($section, OperacionLogistica $operacion)
    {
        $section->addText('POST-OPERACIONES', ['size' => 14, 'bold' => true], ['spaceBefore' => 240]);
        
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '006699',
            'cellMargin' => 80,
            'width' => 100 * 50,
            'unit' => \PhpOffice\PhpWord\Style\Table::WIDTH_PERCENT
        ]);
        
        // Encabezado
        $table->addRow();
        $table->addCell(3000, ['bgColor' => 'E6F3FF'])->addText('Post-Operación', ['bold' => true]);
        $table->addCell(3000, ['bgColor' => 'E6F3FF'])->addText('Fecha Asignada', ['bold' => true]);
        $table->addCell(4000, ['bgColor' => 'E6F3FF'])->addText('Observaciones', ['bold' => true]);
        
        // Datos de post-operaciones
        foreach ($operacion->postOperaciones as $postOp) {
            $table->addRow();
            $table->addCell(3000)->addText($postOp->nombre ?? 'N/A');
            $table->addCell(3000)->addText(
                $postOp->pivot->fecha_asignada ? 
                \Carbon\Carbon::parse($postOp->pivot->fecha_asignada)->format('d/m/Y') : 'N/A'
            );
            $table->addCell(4000)->addText($postOp->pivot->observaciones ?? 'Sin observaciones');
        }
    }
    
    /**
     * Agregar sección de historial
     */
    private function agregarSeccionHistorial($section, OperacionLogistica $operacion)
    {
        if ($operacion->historial && $operacion->historial->count() > 0) {
            $section->addText('HISTORIAL DE CAMBIOS', ['size' => 14, 'bold' => true], ['spaceBefore' => 240]);
            
            foreach ($operacion->historial->take(10) as $historial) {
                $section->addText(
                    '• ' . ($historial->created_at ? $historial->created_at->format('d/m/Y H:i') : 'Fecha N/A') . 
                    ' - ' . ($historial->descripcion ?? 'Sin descripción'),
                    ['size' => 10],
                    ['spaceBefore' => 60]
                );
            }
        }
    }
    
    /**
     * Crear reporte de múltiples operaciones
     */
    public function crearReporteMultiple($operaciones, $titulo = 'REPORTE DE OPERACIONES')
    {
        $section = $this->phpWord->addSection();
        
        // Título
        $section->addText(
            $titulo,
            ['size' => 16, 'bold' => true],
            ['alignment' => 'center', 'spaceBefore' => 0, 'spaceAfter' => 240]
        );
        
        // Resumen
        $section->addText(
            'Total de operaciones: ' . $operaciones->count(),
            ['size' => 12, 'bold' => true],
            ['spaceBefore' => 120]
        );
        
        // Tabla resumen
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => '006699',
            'cellMargin' => 80,
        ]);
        
        // Encabezado
        $table->addRow();
        $table->addCell(2000, ['bgColor' => 'E6F3FF'])->addText('Operación', ['bold' => true]);
        $table->addCell(2500, ['bgColor' => 'E6F3FF'])->addText('Cliente', ['bold' => true]);
        $table->addCell(1500, ['bgColor' => 'E6F3FF'])->addText('Status', ['bold' => true]);
        $table->addCell(1500, ['bgColor' => 'E6F3FF'])->addText('Días', ['bold' => true]);
        $table->addCell(2000, ['bgColor' => 'E6F3FF'])->addText('Fecha Registro', ['bold' => true]);
        
        // Datos
        foreach ($operaciones as $operacion) {
            $table->addRow();
            $table->addCell(2000)->addText($operacion->numero_operacion ?? 'N/A');
            $table->addCell(2500)->addText($operacion->cliente ?? 'N/A');
            $table->addCell(1500)->addText($operacion->status ?? 'N/A');
            $table->addCell(1500)->addText($operacion->dias_transcurridos ?? 'N/A');
            $table->addCell(2000)->addText(
                $operacion->created_at ? $operacion->created_at->format('d/m/Y') : 'N/A'
            );
        }
        
        return $this;
    }
    
    /**
     * Guardar documento en storage
     */
    public function guardar($nombreArchivo = null)
    {
        $nombreArchivo = $nombreArchivo ?: 'reporte_' . date('Y-m-d_H-i-s') . '.docx';
        
        // Asegurar que termine en .docx
        if (!str_ends_with($nombreArchivo, '.docx')) {
            $nombreArchivo .= '.docx';
        }
        
        $rutaCompleta = storage_path('app/public/reportes/' . $nombreArchivo);
        
        // Crear directorio si no existe
        $directorio = dirname($rutaCompleta);
        if (!file_exists($directorio)) {
            mkdir($directorio, 0755, true);
        }
        
        // Guardar archivo
        $objWriter = IOFactory::createWriter($this->phpWord, 'Word2007');
        $objWriter->save($rutaCompleta);
        
        return [
            'ruta_completa' => $rutaCompleta,
            'ruta_storage' => 'reportes/' . $nombreArchivo,
            'nombre_archivo' => $nombreArchivo,
            'url_descarga' => asset('storage/reportes/' . $nombreArchivo)
        ];
    }
    
    /**
     * Descargar documento directamente
     */
    public function descargar($nombreArchivo = null)
    {
        $nombreArchivo = $nombreArchivo ?: 'reporte_' . date('Y-m-d_H-i-s') . '.docx';
        
        // Crear en memoria
        $objWriter = IOFactory::createWriter($this->phpWord, 'Word2007');
        
        // Headers para descarga
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Content-Type: application/vnd.openxmlformats-officedocument.wordprocessingml.document');
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        header('Expires: 0');
        
        $objWriter->save('php://output');
        exit;
    }
}