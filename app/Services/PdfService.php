<?php

namespace App\Services;

use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Storage;
use Dompdf\Dompdf;
use Dompdf\Options;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Exception;
use App\Services\PdfTemplateService;
use Illuminate\Support\Facades\Log;

class PdfService
{
    protected ?PdfTemplateService $templateService = null;

    public function __construct(?PdfTemplateService $templateService = null)
    {
        $this->templateService = $templateService;
    }

    protected function getTemplateService(): PdfTemplateService
    {
        if (!$this->templateService) {
            $this->templateService = new PdfTemplateService();
        }
        return $this->templateService;
    }

    // Formatos disponibles
    const FORMATS = [
        'A4' => ['width' => 210, 'height' => 297, 'unit' => 'mm'],
        'A5' => ['width' => 148, 'height' => 210, 'unit' => 'mm'],
        '80mm' => ['width' => 80, 'height' => 200, 'unit' => 'mm'], // Ticket común
        '50mm' => ['width' => 50, 'height' => 150, 'unit' => 'mm'], // Ticket pequeño
        'ticket' => ['width' => 50, 'height' => 150, 'unit' => 'mm'], // Nuevo formato optimizado
    ];

    public function generateInvoicePdf($invoice, string $format = 'A4'): string
    {
        $data = $this->prepareInvoiceData($invoice);
        $data['format'] = $format;
        
        $template = $this->getTemplate('invoice', $format);
        $html = View::make($template, $data)->render();
        
        $pdf = $this->createPdfInstance($html, $format);
        
        return $pdf->output();
    }

    public function generateBoletaPdf($boleta, string $format = 'A4'): string
    {
        $data = $this->prepareBoletaData($boleta);
        $data['format'] = $format;
        
        $template = $this->getTemplate('boleta', $format);
        $html = View::make($template, $data)->render();
        
        $pdf = $this->createPdfInstance($html, $format);
        
        return $pdf->output();
    }

    public function generateCreditNotePdf($creditNote, string $format = 'A4'): string
    {
        $data = $this->prepareCreditNoteData($creditNote);
        $data['format'] = $format;
        
        $template = $this->getTemplate('credit-note', $format);
        $html = View::make($template, $data)->render();
        
        $pdf = $this->createPdfInstance($html, $format);
        
        return $pdf->output();
    }

    public function generateDebitNotePdf($debitNote, string $format = 'A4'): string
    {
        $data = $this->prepareDebitNoteData($debitNote);
        $data['format'] = $format;
        
        $template = $this->getTemplate('debit-note', $format);
        $html = View::make($template, $data)->render();
        
        $pdf = $this->createPdfInstance($html, $format);
        
        return $pdf->output();
    }

    public function generateDispatchGuidePdf($dispatchGuide, string $format = 'A4'): string
    {
        $data = $this->prepareDispatchGuideData($dispatchGuide);
        $data['format'] = $format;
        
        $template = $this->getTemplate('dispatch-guide', $format);
        $html = View::make($template, $data)->render();
        
        $pdf = $this->createPdfInstance($html, $format);
        
        return $pdf->output();
    }

    public function generateDailySummaryPdf($dailySummary, string $format = 'A4'): string
    {
        $data = $this->prepareDailySummaryData($dailySummary);
        $data['format'] = $format;
        
        $template = $this->getTemplate('daily-summary', $format);
        $html = View::make($template, $data)->render();
        
        $pdf = $this->createPdfInstance($html, $format);
        
        return $pdf->output();
    }

    protected function prepareInvoiceData($invoice): array
    {
        // Preparar datos del cliente - primero intentar desde la relación, luego desde campos JSON
        $clientModel = $invoice->client;
        $clientData = [];
        
        if ($clientModel) {
            // Usar datos del modelo Client relacionado
            $clientData = [
                'razon_social' => $clientModel->razon_social,
                'tipo_documento' => $clientModel->tipo_documento,
                'numero_documento' => $clientModel->numero_documento,
                'direccion' => $clientModel->direccion,
                'ubigeo' => $clientModel->ubigeo,
                'distrito' => $clientModel->distrito,
                'provincia' => $clientModel->provincia,
                'departamento' => $clientModel->departamento,
                'telefono' => $clientModel->telefono,
                'email' => $clientModel->email,
            ];
        } else {
            // Fallback: intentar desde campos JSON si no hay relación
            $clientData = $this->safeJsonDecode($invoice->client_data ?? $invoice->client_json ?? '[]');
        }
        
        // Valores por defecto para cliente
        $client = array_merge([
            'razon_social' => 'CLIENTE',
            'tipo_documento' => '1',
            'numero_documento' => 'N/A',
            'direccion' => '',
            'ubigeo' => '',
            'distrito' => '',
            'provincia' => '',
            'departamento' => '',
            'telefono' => '',
            'email' => '',
        ], $clientData);

        // Preparar detalles - manejar tanto arrays como JSON strings
        $detalles = $this->safeJsonDecode($invoice->detalles ?? $invoice->detalles_json ?? '[]');

        // Generar QR Code
        $qrData = $this->generateQRData($invoice);
        $qrBase64 = $this->generateQRCode($qrData);

        return [
            'document' => $invoice,
            'company' => $invoice->company,
            'branch' => $invoice->branch,
            'client' => $client,
            'detalles' => $detalles,
            'totales' => $this->calculateInvoiceTotals($invoice),
            'fecha_emision' => $invoice->fecha_emision ? $invoice->fecha_emision->format('d/m/Y') : date('d/m/Y'),
            'fecha_vencimiento' => $invoice->fecha_vencimiento ? $invoice->fecha_vencimiento->format('d/m/Y') : null,
            'tipo_documento_nombre' => 'FACTURA ELECTRÓNICA',
            'qr_code' => $qrBase64,
            'hash' => $invoice->hash_cdr ?? $invoice->valor_resumen ?? '',
            'total_en_letras' => $this->numeroALetras($invoice->mto_imp_venta ?? 0),
        ];
    }

    protected function prepareBoletaData($boleta): array
    {
        // Preparar datos del cliente - primero intentar desde la relación, luego desde campos JSON
        $clientModel = $boleta->client;
        $clientData = [];
        
        if ($clientModel) {
            // Usar datos del modelo Client relacionado
            $clientData = [
                'razon_social' => $clientModel->razon_social,
                'tipo_documento' => $clientModel->tipo_documento,
                'numero_documento' => $clientModel->numero_documento,
                'direccion' => $clientModel->direccion,
                'telefono' => $clientModel->telefono,
                'email' => $clientModel->email,
            ];
        } else {
            // Fallback: intentar desde campos JSON si no hay relación
            $clientData = $this->safeJsonDecode($boleta->client_data ?? $boleta->client_json ?? '[]');
        }
        
        $client = array_merge([
            'razon_social' => 'CLIENTE',
            'tipo_documento' => '1',
            'numero_documento' => 'N/A',
            'direccion' => '',
            'telefono' => '',
            'email' => '',
        ], $clientData);

        // Generar QR Code
        $qrData = $this->generateQRData($boleta);
        $qrBase64 = $this->generateQRCode($qrData);

        return [
            'document' => $boleta,
            'company' => $boleta->company,
            'branch' => $boleta->branch,
            'client' => $client,
            'detalles' => $this->safeJsonDecode($boleta->detalles ?? $boleta->detalles_json ?? '[]'),
            'totales' => $this->calculateBoletaTotals($boleta),
            'fecha_emision' => $boleta->fecha_emision->format('d/m/Y'),
            'tipo_documento_nombre' => 'BOLETA DE VENTA ELECTRÓNICA',
            'qr_code' => $qrBase64,
            'hash' => $boleta->hash_cdr ?? $boleta->valor_resumen ?? '',
            'total_en_letras' => $this->numeroALetras($boleta->mto_imp_venta ?? 0),
        ];
    }

    protected function prepareCreditNoteData($creditNote): array
    {
        // Preparar datos del cliente - primero intentar desde la relación, luego desde campos JSON
        $clientModel = $creditNote->client;
        $clientData = [];
        
        if ($clientModel) {
            // Usar datos del modelo Client relacionado
            $clientData = [
                'razon_social' => $clientModel->razon_social,
                'tipo_documento' => $clientModel->tipo_documento,
                'numero_documento' => $clientModel->numero_documento,
                'direccion' => $clientModel->direccion,
                'telefono' => $clientModel->telefono,
                'email' => $clientModel->email,
            ];
        } else {
            // Fallback: intentar desde campos JSON si no hay relación
            $clientData = $this->safeJsonDecode($creditNote->client_data ?? $creditNote->client_json ?? '[]');
        }
        
        $client = array_merge([
            'razon_social' => 'CLIENTE',
            'tipo_documento' => '1',
            'numero_documento' => 'N/A',
            'direccion' => '',
            'telefono' => '',
            'email' => '',
        ], $clientData);

        // Generar QR Code
        $qrData = $this->generateQRData($creditNote);
        $qrBase64 = $this->generateQRCode($qrData);

        return [
            'document' => $creditNote,
            'company' => $creditNote->company,
            'branch' => $creditNote->branch,
            'client' => $client,
            'detalles' => $this->safeJsonDecode($creditNote->detalles ?? $creditNote->detalles_json ?? '[]'),
            'totales' => $this->calculateCreditNoteTotals($creditNote),
            'fecha_emision' => $creditNote->fecha_emision->format('d/m/Y'),
            'tipo_documento_nombre' => 'NOTA DE CRÉDITO ELECTRÓNICA',
            'documento_afectado' => [
                'tipo' => $this->getTipoDocumentoName($creditNote->tipo_doc_afectado),
                'numero' => $creditNote->num_doc_afectado,
            ],
            'motivo' => [
                'codigo' => $creditNote->cod_motivo,
                'descripcion' => $creditNote->des_motivo,
            ],
            'qr_code' => $qrBase64,
            'hash' => $creditNote->hash_cdr ?? $creditNote->valor_resumen ?? '',
            'total_en_letras' => $this->numeroALetras($creditNote->mto_imp_venta ?? 0),
        ];
    }

    protected function prepareDebitNoteData($debitNote): array
    {
        // Preparar datos del cliente - primero intentar desde la relación, luego desde campos JSON
        $clientModel = $debitNote->client;
        $clientData = [];
        
        if ($clientModel) {
            // Usar datos del modelo Client relacionado
            $clientData = [
                'razon_social' => $clientModel->razon_social,
                'tipo_documento' => $clientModel->tipo_documento,
                'numero_documento' => $clientModel->numero_documento,
                'direccion' => $clientModel->direccion,
                'telefono' => $clientModel->telefono,
                'email' => $clientModel->email,
            ];
        } else {
            // Fallback: intentar desde campos JSON si no hay relación
            $clientData = $this->safeJsonDecode($debitNote->client_data ?? $debitNote->client_json ?? '[]');
        }
        
        $client = array_merge([
            'razon_social' => 'CLIENTE',
            'tipo_documento' => '1',
            'numero_documento' => 'N/A',
            'direccion' => '',
            'telefono' => '',
            'email' => '',
        ], $clientData);

        // Generar QR Code
        $qrData = $this->generateQRData($debitNote);
        $qrBase64 = $this->generateQRCode($qrData);

        return [
            'document' => $debitNote,
            'company' => $debitNote->company,
            'branch' => $debitNote->branch,
            'client' => $client,
            'detalles' => $this->safeJsonDecode($debitNote->detalles ?? $debitNote->detalles_json ?? '[]'),
            'totales' => $this->calculateDebitNoteTotals($debitNote),
            'fecha_emision' => $debitNote->fecha_emision->format('d/m/Y'),
            'tipo_documento_nombre' => 'NOTA DE DÉBITO ELECTRÓNICA',
            'documento_afectado' => [
                'tipo' => $this->getTipoDocumentoName($debitNote->tipo_doc_afectado),
                'numero' => $debitNote->num_doc_afectado,
            ],
            'motivo' => [
                'codigo' => $debitNote->cod_motivo,
                'descripcion' => $debitNote->des_motivo,
            ],
            'qr_code' => $qrBase64,
            'hash' => $debitNote->hash_cdr ?? $debitNote->valor_resumen ?? '',
            'total_en_letras' => $this->numeroALetras($debitNote->mto_imp_venta ?? 0),
        ];
    }

    protected function prepareDispatchGuideData($dispatchGuide): array
    {
        return [
            'document' => $dispatchGuide,
            'company' => $dispatchGuide->company,
            'branch' => $dispatchGuide->branch,
            'destinatario' => $dispatchGuide->destinatario,
            'detalles' => $dispatchGuide->detalles ?? json_decode($dispatchGuide->detalles_json, true),
            'fecha_emision' => $dispatchGuide->fecha_emision->format('d/m/Y'),
            'fecha_traslado' => $dispatchGuide->fecha_traslado->format('d/m/Y'),
            'tipo_documento_nombre' => 'GUÍA DE REMISIÓN ELECTRÓNICA',
            'motivo_traslado' => $dispatchGuide->getMotivoTrasladoNameAttribute(),
            'modalidad_traslado' => $dispatchGuide->getModalidadTrasladoNameAttribute(),
            'peso_total_formatted' => number_format($dispatchGuide->peso_total, 3) . ' ' . $dispatchGuide->und_peso_total,
        ];
    }

    protected function calculateInvoiceTotals($invoice): array
    {
        $detalles = $this->safeJsonDecode($invoice->detalles ?? $invoice->detalles_json ?? '[]');
        
        $subtotal = 0;
        $igv = 0;
        $total = 0;

        if (count($detalles) > 0) {
            foreach ($detalles as $detalle) {
                if (!is_array($detalle)) continue;
                
                $cantidad = $detalle['cantidad'] ?? 0;
                $valorUnitario = $detalle['mto_valor_unitario'] ?? 0;
                $valorVenta = $detalle['mto_valor_venta'] ?? ($cantidad * $valorUnitario);
                $igvDetalle = $detalle['igv'] ?? 0;
                
                $subtotal += $valorVenta;
                $igv += $igvDetalle;
            }
        }

        $total = $subtotal + $igv;

        return [
            'subtotal' => $subtotal,
            'igv' => $igv,
            'total' => $total,
            'subtotal_formatted' => number_format($subtotal, 2),
            'igv_formatted' => number_format($igv, 2),
            'total_formatted' => number_format($total, 2),
            'moneda' => $invoice->moneda ?? 'PEN',
            'moneda_nombre' => $this->getMonedaNombre($invoice->moneda ?? 'PEN'),
        ];
    }

    protected function calculateBoletaTotals($boleta): array
    {
        return $this->calculateInvoiceTotals($boleta); // Same calculation logic
    }

    protected function calculateCreditNoteTotals($creditNote): array
    {
        return $this->calculateInvoiceTotals($creditNote); // Same calculation logic
    }

    protected function calculateDebitNoteTotals($debitNote): array
    {
        return $this->calculateInvoiceTotals($debitNote); // Same calculation logic
    }

    protected function getTipoDocumentoName($codigo): string
    {
        return match($codigo) {
            '01' => 'FACTURA',
            '03' => 'BOLETA DE VENTA',
            '07' => 'NOTA DE CRÉDITO',
            '08' => 'NOTA DE DÉBITO',
            '09' => 'GUÍA DE REMISIÓN',
            default => 'DOCUMENTO'
        };
    }

    protected function getMonedaNombre($codigo): string
    {
        return match($codigo) {
            'PEN' => 'SOLES',
            'USD' => 'DÓLARES AMERICANOS',
            'EUR' => 'EUROS',
            default => 'SOLES'
        };
    }

    public function numeroALetras($numero): string
    {
        $unidades = [
            '', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve',
            'diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 
            'dieciocho', 'diecinueve', 'veinte'
        ];

        $decenas = [
            '', '', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'
        ];

        $centenas = [
            '', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos',
            'seiscientos', 'setecientos', 'ochocientos', 'novecientos'
        ];

        // Convertir número a entero y obtener decimales
        $partes = explode('.', number_format($numero, 2, '.', ''));
        $entero = (int)$partes[0];
        $decimales = $partes[1];

        if ($entero == 0) {
            return "cero con $decimales/100";
        }

        $resultado = $this->convertirEntero($entero, $unidades, $decenas, $centenas);
        
        return trim($resultado) . " con $decimales/100";
    }

    protected function convertirEntero($numero, $unidades, $decenas, $centenas): string
    {
        if ($numero < 21) {
            return $unidades[$numero];
        }

        if ($numero < 100) {
            $dec = (int)($numero / 10);
            $uni = $numero % 10;
            
            if ($uni == 0) {
                return $decenas[$dec];
            } else {
                return $decenas[$dec] . ' y ' . $unidades[$uni];
            }
        }

        if ($numero < 1000) {
            $cen = (int)($numero / 100);
            $resto = $numero % 100;
            
            $resultado = ($numero == 100) ? 'cien' : $centenas[$cen];
            
            if ($resto > 0) {
                $resultado .= ' ' . $this->convertirEntero($resto, $unidades, $decenas, $centenas);
            }
            
            return $resultado;
        }

        // Para miles, millones, etc.
        if ($numero < 1000000) {
            $miles = (int)($numero / 1000);
            $resto = $numero % 1000;
            
            if ($miles == 1) {
                $resultado = 'mil';
            } else {
                $resultado = $this->convertirEntero($miles, $unidades, $decenas, $centenas) . ' mil';
            }
            
            if ($resto > 0) {
                $resultado .= ' ' . $this->convertirEntero($resto, $unidades, $decenas, $centenas);
            }
            
            return $resultado;
        }

        return 'número muy grande';
    }

    /**
     * Crea una instancia de DomPDF con el HTML y formato especificado
     */
    protected function createPdfInstance(string $html, string $format): Dompdf
    {
        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $options->set('isRemoteEnabled', true);
        $options->set('isHtml5ParserEnabled', true);
        
        $pdf = new Dompdf($options);
        $pdf->loadHtml($html);
        
        $this->setPaperFormat($pdf, $format);
        $pdf->render();
        
        return $pdf;
    }

    /**
     * Configura el formato del papel según el tipo especificado
     */
    protected function setPaperFormat(Dompdf $pdf, string $format): void
    {
        if (!isset(self::FORMATS[$format])) {
            $format = 'A4'; // Fallback a A4
        }

        $formatConfig = self::FORMATS[$format];
        
        if ($format === 'A4' || $format === 'A5') {
            $pdf->setPaper($format, 'portrait');
        } else {
            // Para formatos personalizados (80mm, 50mm)
            $width = $this->mmToPt($formatConfig['width']);
            $height = $this->mmToPt($formatConfig['height']);
            $pdf->setPaper(array(0, 0, $width, $height), 'portrait');
        }
    }

    /**
     * Convierte milímetros a puntos (pts) para DomPDF
     */
    protected function mmToPt(float $mm): float
    {
        return $mm * 2.834645669; // 1mm = 2.834645669 pts
    }

    /**
     * Obtiene el template correcto según el tipo de documento y formato
     */
    protected function getTemplate(string $documentType, string $format): string
    {
        // Usar el nuevo servicio de plantillas optimizado
        return $this->getTemplateService()->getTemplatePath($documentType, $format, true);
    }

    /**
     * Obtiene los formatos disponibles
     */
    public function getAvailableFormats(): array
    {
        return $this->getTemplateService()->getAvailableFormats();
    }

    /**
     * Valida si un formato es válido
     */
    public function isValidFormat(string $format): bool
    {
        return isset(self::FORMATS[$format]);
    }

    /**
     * Prepara datos para Daily Summary
     */
    protected function prepareDailySummaryData($dailySummary): array
    {
        return [
            'document' => $dailySummary,
            'company' => $dailySummary->company,
            'branch' => $dailySummary->branch,
            'detalles' => $dailySummary->detalles ?? json_decode($dailySummary->detalles_json, true),
            'fecha_emision' => $dailySummary->fecha_emision->format('d/m/Y'),
            'fecha_referencia' => $dailySummary->fec_resumen->format('d/m/Y'),
            'tipo_documento_nombre' => 'RESUMEN DIARIO DE BOLETAS',
            'totales' => $this->calculateDailySummaryTotals($dailySummary),
        ];
    }

    /**
     * Calcula totales para Daily Summary
     */
    protected function calculateDailySummaryTotals($dailySummary): array
    {
        $detalles = $dailySummary->detalles ?? json_decode($dailySummary->detalles_json, true);
        
        $totalGravada = 0;
        $totalIgv = 0;
        $totalVenta = 0;

        if ($detalles) {
            foreach ($detalles as $detalle) {
                $totalGravada += $detalle['mto_oper_gravadas'] ?? 0;
                $totalIgv += $detalle['mto_igv'] ?? 0;
                $totalVenta += $detalle['mto_imp_venta'] ?? 0;
            }
        }

        return [
            'total_gravada' => $totalGravada,
            'total_igv' => $totalIgv,
            'total_venta' => $totalVenta,
            'total_gravada_formatted' => number_format($totalGravada, 2),
            'total_igv_formatted' => number_format($totalIgv, 2),
            'total_venta_formatted' => number_format($totalVenta, 2),
            'moneda' => $dailySummary->moneda ?? 'PEN',
            'moneda_nombre' => $this->getMonedaNombre($dailySummary->moneda ?? 'PEN'),
        ];
    }

    /**
     * Genera los datos para el código QR según estándar SUNAT
     */
    protected function generateQRData($document): string
    {
        // Formato QR SUNAT: RUC|TIPO_DOC|SERIE|NUMERO|MTO_IGV|MTO_TOTAL|FECHA_EMISION|TIPO_DOC_CLIENTE|NUM_DOC_CLIENTE|
        $company = $document->company;
        
        // Obtener datos del cliente
        $client = [];
        if ($document->client) {
            $client = [
                'tipo_documento' => $document->client->tipo_documento,
                'numero_documento' => $document->client->numero_documento,
            ];
        } else {
            $client = $this->safeJsonDecode($document->client_data ?? $document->client_json ?? '[]');
        }
        
        $ruc = $company->ruc ?? '';
        $tipoDoc = $document->tipo_documento ?? '01';
        $serie = $document->serie ?? '';
        $numero = $document->correlativo ?? '';
        $mtoIgv = number_format($document->mto_igv ?? 0, 2, '.', '');
        $mtoTotal = number_format($document->mto_imp_venta ?? 0, 2, '.', '');
        $fechaEmision = $document->fecha_emision ? $document->fecha_emision->format('Y-m-d') : date('Y-m-d');
        $tipoDocCliente = $client['tipo_documento'] ?? '1';
        $numDocCliente = $client['numero_documento'] ?? '';
        
        return "{$ruc}|{$tipoDoc}|{$serie}|{$numero}|{$mtoIgv}|{$mtoTotal}|{$fechaEmision}|{$tipoDocCliente}|{$numDocCliente}|";
    }

    /**
     * Genera código QR y retorna como base64
     */
    protected function generateQRCode(string $data): string
    {
        try {
            // Usar SVG que es más compatible y funciona en todos los ambientes
            $renderer = new ImageRenderer(
                new RendererStyle(200, 10),
                new SvgImageBackEnd()
            );
            
            $writer = new Writer($renderer);
            $svgString = $writer->writeString($data);
            
            return 'data:image/svg+xml;base64,' . base64_encode($svgString);
        } catch (Exception $e) {
            // Log del error para debug
            Log::error('Error generando QR Code: ' . $e->getMessage());
            
            // Placeholder que funciona siempre
            $placeholder = '<svg width="200" height="200" xmlns="http://www.w3.org/2000/svg">
                <rect width="200" height="200" fill="#f8f9fa" stroke="#dee2e6" stroke-width="2"/>
                <text x="100" y="90" text-anchor="middle" fill="#6c757d" font-family="Arial" font-size="14" font-weight="bold">QR CODE</text>
                <text x="100" y="110" text-anchor="middle" fill="#6c757d" font-family="Arial" font-size="12">SUNAT</text>
                <text x="100" y="125" text-anchor="middle" fill="#6c757d" font-family="Arial" font-size="8">' . substr($data, 0, 20) . '...</text>
                <rect x="50" y="140" width="100" height="40" fill="none" stroke="#adb5bd" stroke-width="1" stroke-dasharray="2,2"/>
            </svg>';
            
            return 'data:image/svg+xml;base64,' . base64_encode($placeholder);
        }
    }

    /**
     * Obtiene el tipo de documento del cliente con descripción
     */
    protected function getTipoDocumentoClienteNombre($codigo): string
    {
        return match($codigo) {
            '0' => 'DOC. TRIB. NO DOM. SIN RUC',
            '1' => 'DNI',
            '4' => 'CARNET DE EXTRANJERÍA',
            '6' => 'RUC',
            '7' => 'PASAPORTE',
            '11' => 'PARTIDA DE NACIMIENTO',
            '12' => 'CEDULA DIPLOMATICA',
            default => 'OTROS'
        };
    }

    /**
     * Decodifica JSON de manera segura, manejando tanto strings como arrays
     */
    protected function safeJsonDecode($data): array
    {
        // Si ya es un array, devolverlo directamente
        if (is_array($data)) {
            return $data;
        }
        
        // Si es null o vacío, devolver array vacío
        if (empty($data)) {
            return [];
        }
        
        // Si es string, intentar decodificar
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            
            // Si la decodificación falló o no es array, devolver array vacío
            if (json_last_error() !== JSON_ERROR_NONE || !is_array($decoded)) {
                return [];
            }
            
            return $decoded;
        }
        
        // Para cualquier otro tipo, devolver array vacío
        return [];
    }
}