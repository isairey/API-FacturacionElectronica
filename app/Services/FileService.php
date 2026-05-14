<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class FileService
{
    public function saveXml($document, string $xmlContent): string
    {
        $this->ensureDirectoryExists($document, 'xml');
        $path = $this->generatePath($document, 'xml');
        Storage::disk('public')->put($path, $xmlContent);
        return $path;
    }

    public function saveCdr($document, string $cdrContent): string
    {
        $this->ensureDirectoryExists($document, 'zip');
        $path = $this->generatePath($document, 'zip');
        Storage::disk('public')->put($path, $cdrContent);
        return $path;
    }

    public function savePdf($document, string $pdfContent, string $format = 'A4'): string
    {
        $this->ensureDirectoryExists($document, 'pdf');
        $path = $this->generatePath($document, 'pdf', $format);
        Storage::disk('public')->put($path, $pdfContent);
        return $path;
    }

    protected function generatePath($document, string $extension, string $format = 'A4'): string
    {
        $date = Carbon::parse($document->fecha_emision);
        $dateFolder = $date->format('dmY'); // Formato: 02092025
        
        $fileName = $document->numero_completo;
        
        // Obtener tipo de comprobante
        $tipoComprobante = $this->getDocumentTypeName($document);
        
        // Determinar el tipo de archivo (xml, cdr o pdf)
        $tipoArchivo = $extension === 'zip' ? 'cdr' : $extension;
        
        // Crear estructura: TIPO_COMPROBANTE/TIPO_ARCHIVO/DDMMYYYY/
        $directory = "{$tipoComprobante}/{$tipoArchivo}/{$dateFolder}";
        
        // Prefijo según tipo de archivo
        $prefix = '';
        if ($extension === 'zip') {
            $prefix = 'R-'; // CDR
        }
        
        // Para PDFs, agregar el formato al nombre del archivo si no es A4
        if ($extension === 'pdf' && $format !== 'A4') {
            $fileName .= "_{$format}";
        }
        
        return "{$directory}/{$prefix}{$fileName}.{$extension}";
    }

    protected function getDocumentTypeName($document): string
    {
        // Determinar el nombre de la carpeta según el tipo de documento
        // Verificar si es un modelo Eloquent con el atributo tipo_documento
        if (isset($document->tipo_documento)) {
            return match($document->tipo_documento) {
                '01' => 'facturas',
                '03' => 'boletas',
                '07' => 'notas-credito',
                '08' => 'notas-debito',
                '09' => 'guias-remision',
                '20' => 'percepciones',
                '21' => 'retenciones',
                default => 'otros-comprobantes'
            };
        }
        
        // Fallback basado en el nombre de la clase del modelo
        $className = class_basename($document);
        return match($className) {
            'Invoice' => 'facturas',  // Corregido: Invoice en lugar de Factura
            'Boleta' => 'boletas',
            'CreditNote' => 'notas-credito',
            'DebitNote' => 'notas-debito', 
            'DispatchGuide' => 'guias-remision',
            'Percepcion' => 'percepciones',
            'Retencion' => 'retenciones',
            'DailySummary' => 'resumenes-diarios',
            default => 'otros-comprobantes'
        };
    }

    public function getXmlPath($document): ?string
    {
        if (!$document->xml_path) {
            return null;
        }
        
        return Storage::disk('public')->exists($document->xml_path) 
            ? Storage::disk('public')->path($document->xml_path)
            : null;
    }

    public function getCdrPath($document): ?string
    {
        if (!$document->cdr_path) {
            return null;
        }
        
        return Storage::disk('public')->exists($document->cdr_path)
            ? Storage::disk('public')->path($document->cdr_path)
            : null;
    }

    public function getPdfPath($document): ?string
    {
        if (!$document->pdf_path) {
            return null;
        }
        
        return Storage::disk('public')->exists($document->pdf_path)
            ? Storage::disk('public')->path($document->pdf_path)
            : null;
    }

    public function downloadXml($document)
    {
        if (!$document->xml_path || !Storage::disk('public')->exists($document->xml_path)) {
            return null;
        }
        
        return Storage::disk('public')->download(
            $document->xml_path,
            $document->numero_completo . '.xml'
        );
    }

    public function downloadCdr($document)
    {
        if (!$document->cdr_path || !Storage::disk('public')->exists($document->cdr_path)) {
            return null;
        }
        
        return Storage::disk('public')->download(
            $document->cdr_path,
            'R-' . $document->numero_completo . '.zip'
        );
    }

    public function downloadPdf($document)
    {
        if (!$document->pdf_path || !Storage::disk('public')->exists($document->pdf_path)) {
            return null;
        }
        
        return Storage::disk('public')->download(
            $document->pdf_path,
            $document->numero_completo . '.pdf'
        );
    }

    public function createDirectoryStructure(): void
    {
        // Tipos de comprobantes
        $tiposComprobantes = [
            'facturas',
            'boletas', 
            'notas-credito',
            'notas-debito',
            'guias-remision',
            'percepciones',
            'retenciones',
            'resumenes-diarios',
            'otros-comprobantes'
        ];
        
        // Tipos de archivos
        $tiposArchivos = ['xml', 'cdr', 'pdf'];
        
        // Crear estructura de directorios base
        foreach ($tiposComprobantes as $tipoComprobante) {
            foreach ($tiposArchivos as $tipoArchivo) {
                $directory = "{$tipoComprobante}/{$tipoArchivo}";
                Storage::disk('public')->makeDirectory($directory);
            }
        }
    }

    public function ensureDirectoryExists($document, string $extension): void
    {
        $date = Carbon::parse($document->fecha_emision);
        $dateFolder = $date->format('dmY');
        
        $tipoComprobante = $this->getDocumentTypeName($document);
        $tipoArchivo = $extension === 'zip' ? 'cdr' : $extension;
        
        $directory = "{$tipoComprobante}/{$tipoArchivo}/{$dateFolder}";
        
        if (!Storage::disk('public')->exists($directory)) {
            Storage::disk('public')->makeDirectory($directory);
        }
    }
}