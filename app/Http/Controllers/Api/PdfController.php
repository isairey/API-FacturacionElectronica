<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PdfService;
use Illuminate\Http\JsonResponse;

class PdfController extends Controller
{
    protected $pdfService;

    public function __construct(PdfService $pdfService)
    {
        $this->pdfService = $pdfService;
    }

    /**
     * Obtener formatos disponibles para PDF
     */
    public function getAvailableFormats(): JsonResponse
    {
        try {
            // Usar directamente las constantes de PdfService para evitar confusión
            $formatDetails = [];
            foreach (PdfService::FORMATS as $format => $config) {
                $formatDetails[] = [
                    'name' => $format,
                    'width' => $config['width'],
                    'height' => $config['height'],
                    'unit' => $config['unit'],
                    'description' => $this->getFormatDescription($format)
                ];
            }

            return response()->json([
                'success' => true,
                'data' => $formatDetails,
                'message' => 'Formatos disponibles obtenidos correctamente'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener formatos disponibles',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener descripción del formato
     */
    protected function getFormatDescription(string $format): string
    {
        return match($format) {
            'A4' => 'Formato estándar de oficina (210x297mm)',
            'A5' => 'Formato medio, ideal para reportes compactos (148x210mm)',
            '80mm' => 'Formato ticket térmico estándar (80x200mm)',
            '50mm' => 'Formato ticket térmico compacto (50x150mm)',
            'ticket' => 'Formato ticket optimizado (50x150mm)',
            default => 'Formato personalizado'
        };
    }
}