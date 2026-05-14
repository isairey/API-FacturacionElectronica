<?php

namespace App\Services;

class PdfTemplateService
{
    /**
     * Available PDF formats
     */
    public const FORMATS = [
        'A4' => 'A4 (210x297mm)',
        'a4' => 'A4 (210x297mm)',
        'A5' => 'A5 (148x210mm)',
        'a5' => 'A5 (148x210mm)',
        '80mm' => '80mm Ticket (80x200mm)',
        '50mm' => '50mm Ticket (50x150mm)',
        'ticket' => 'Ticket (50mm)', // Legacy support
    ];

    /**
     * Document types mapping
     */
    public const DOCUMENT_TYPES = [
        'invoice' => 'invoice',
        'boleta' => 'boleta',
        'credit-note' => 'credit-note',
        'debit-note' => 'debit-note',
        'dispatch-guide' => 'dispatch-guide',
        'daily-summary' => 'daily-summary',
        'retention' => 'retention'
    ];

    /**
     * Get optimized template path
     * 
     * @param string $documentType
     * @param string $format
     * @param bool $useOptimized
     * @return string
     */
    public function getTemplatePath(string $documentType, string $format, bool $useOptimized = true): string
    {
        // Normalize format for consistency
        $normalizedFormat = $this->normalizeFormat($format);
        
        // Check if template exists in new structure
        if ($this->templateExists($documentType, $normalizedFormat)) {
            return "pdf.{$normalizedFormat}.{$documentType}";
        }
        
        // Legacy fallback for backwards compatibility
        if ($this->templateExists($documentType, $format)) {
            return "pdf.{$format}.{$documentType}";
        }
        
        // Final fallback to A4 if available
        if ($this->templateExists($documentType, 'a4')) {
            return "pdf.a4.{$documentType}";
        }
        
        // Ultimate fallback
        return "pdf.a4.invoice";
    }

    /**
     * Check if template exists
     * 
     * @param string $documentType
     * @param string $format
     * @return bool
     */
    public function templateExists(string $documentType, string $format): bool
    {
        $templatePath = resource_path("views/pdf/{$format}/{$documentType}.blade.php");
        return file_exists($templatePath);
    }
    
    /**
     * Check if optimized template exists (legacy method)
     * 
     * @param string $documentType
     * @param string $format
     * @return bool
     */
    public function optimizedTemplateExists(string $documentType, string $format): bool
    {
        return $this->templateExists($documentType, $format);
    }

    /**
     * Normalize format names for consistency
     * 
     * @param string $format
     * @return string
     */
    public function normalizeFormat(string $format): string
    {
        $formatMap = [
            'A4' => 'a4',
            'a4' => 'a4',
            'A5' => 'a5',
            'a5' => 'a5',
            '80mm' => '80mm',
            '50mm' => '50mm',
            'ticket' => '50mm', // Legacy ticket maps to 50mm
        ];

        return $formatMap[$format] ?? 'a4';
    }

    /**
     * Get available formats
     * 
     * @return array
     */
    public function getAvailableFormats(): array
    {
        return self::FORMATS;
    }

    /**
     * Get supported document types
     * 
     * @return array
     */
    public function getSupportedDocumentTypes(): array
    {
        return self::DOCUMENT_TYPES;
    }

    /**
     * Get template variables for a document type
     * 
     * @param string $documentType
     * @return array
     */
    public function getTemplateVariables(string $documentType): array
    {
        $baseVariables = [
            'company',
            'client', 
            'document',
            'detalles',
            'fecha_emision',
            'tipo_documento_nombre',
            'leyendas',
            'qr_data',
            'hash_cdr'
        ];

        $specificVariables = [
            'credit-note' => ['documento_afectado', 'motivo'],
            'debit-note' => ['documento_afectado', 'motivo'],
            'dispatch-guide' => ['origen', 'destino', 'transportista'],
            'daily-summary' => ['boletas_incluidas', 'fecha_referencia'],
            'retention' => ['documentos_retenidos', 'tasa_retencion']
        ];

        return array_merge(
            $baseVariables, 
            $specificVariables[$documentType] ?? []
        );
    }

    /**
     * Validate template data
     * 
     * @param string $documentType
     * @param array $data
     * @return array Missing required variables
     */
    public function validateTemplateData(string $documentType, array $data): array
    {
        $requiredVars = ['company', 'client', 'document', 'detalles'];
        $missing = [];

        foreach ($requiredVars as $var) {
            if (!isset($data[$var]) || empty($data[$var])) {
                $missing[] = $var;
            }
        }

        return $missing;
    }

    /**
     * Get CSS class helpers for format
     * 
     * @param string $format
     * @return array
     */
    public function getCssHelpers(string $format): array
    {
        $normalizedFormat = $this->normalizeFormat($format);
        
        return [
            'format' => $normalizedFormat,
            'isTicket' => $normalizedFormat === 'ticket',
            'isA4' => $normalizedFormat === 'a4',
            'containerClass' => $normalizedFormat === 'ticket' ? 'ticket-container' : 'page-container',
            'fontSize' => $normalizedFormat === 'ticket' ? '7px' : '12px'
        ];
    }
}