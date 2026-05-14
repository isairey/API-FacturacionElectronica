<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Traits\HandlesPdfGeneration;
use App\Http\Requests\Boleta\CreateDailySummaryRequest;
use App\Http\Requests\Boleta\GetBoletasPendingRequest;
use App\Http\Requests\Boleta\IndexBoletaRequest;
use App\Http\Requests\Boleta\StoreBoletaRequest;
use App\Models\Boleta;
use App\Models\DailySummary;
use App\Services\DocumentService;
use App\Services\FileService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class BoletaController extends Controller
{
    use HandlesPdfGeneration;

    protected DocumentService $documentService;
    protected FileService $fileService;

    public function __construct(DocumentService $documentService, FileService $fileService)
    {
        $this->documentService = $documentService;
        $this->fileService = $fileService;
    }

    /**
     * Listar boletas con filtros
     */
    public function index(IndexBoletaRequest $request): JsonResponse
    {
        try {
            $query = Boleta::with(['company', 'branch', 'client']);
            $this->applyFilters($query, $request);
            
            $perPage = $request->get('per_page', 15);
            $boletas = $query->orderBy('created_at', 'desc')->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => $boletas->items(),
                'pagination' => $this->getPaginationData($boletas)
            ]);

        } catch (Exception $e) {
            return $this->errorResponse('Error al listar boletas', $e);
        }
    }

    /**
     * Crear nueva boleta
     */
    public function store(StoreBoletaRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $boleta = $this->documentService->createBoleta($validated);

            return response()->json([
                'success' => true,
                'data' => $boleta->load(['company', 'branch', 'client']),
                'message' => 'Boleta creada correctamente'
            ], 201);

        } catch (Exception $e) {
            return $this->errorResponse('Error al crear la boleta', $e);
        }
    }

    /**
     * Obtener boleta específica
     */
    public function show(string $id): JsonResponse
    {
        try {
            $boleta = Boleta::with(['company', 'branch', 'client'])->findOrFail($id);
            
            return response()->json([
                'success' => true,
                'data' => $boleta
            ]);
        } catch (Exception $e) {
            return $this->notFoundResponse('Boleta no encontrada');
        }
    }

    /**
     * Enviar boleta a SUNAT
     */
    public function sendToSunat(string $id): JsonResponse
    {
        try {
            $boleta = Boleta::with(['company', 'branch', 'client'])->findOrFail($id);
            
            if ($boleta->estado_sunat === 'ACEPTADO') {
                return response()->json([
                    'success' => false,
                    'message' => 'La boleta ya fue aceptada por SUNAT'
                ], 400);
            }

            $result = $this->documentService->sendToSunat($boleta, 'boleta');
            
            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['document']->load(['company', 'branch', 'client']),
                    'message' => 'Boleta enviada exitosamente a SUNAT'
                ]);
            }

            return $this->handleSunatError($result);

        } catch (Exception $e) {
            return $this->errorResponse('Error interno al enviar a SUNAT', $e);
        }
    }

    /**
     * Descargar XML de boleta
     */
    public function downloadXml(string $id): Response
    {
        try {
            $boleta = Boleta::findOrFail($id);
            
            if (!$this->fileService->fileExists($boleta->xml_path)) {
                return $this->notFoundResponse('XML no encontrado');
            }

            return $this->fileService->downloadFile(
                $boleta->xml_path,
                $boleta->numero_completo . '.xml',
                ['Content-Type' => 'application/xml']
            );

        } catch (Exception $e) {
            return $this->errorResponse('Error al descargar XML', $e);
        }
    }

    /**
     * Descargar CDR de boleta
     */
    public function downloadCdr(string $id): Response
    {
        try {
            $boleta = Boleta::findOrFail($id);
            
            if (!$this->fileService->fileExists($boleta->cdr_path)) {
                return $this->notFoundResponse('CDR no encontrado');
            }

            return $this->fileService->downloadFile(
                $boleta->cdr_path,
                'R-' . $boleta->numero_completo . '.zip',
                ['Content-Type' => 'application/zip']
            );

        } catch (Exception $e) {
            return $this->errorResponse('Error al descargar CDR', $e);
        }
    }

    /**
     * Descargar PDF de boleta
     */
    public function downloadPdf(string $id, Request $request): Response
    {
        try {
            $boleta = Boleta::findOrFail($id);
            return $this->downloadDocumentPdf($boleta, $request);
        } catch (Exception $e) {
            return $this->errorResponse('Error al descargar PDF', $e);
        }
    }

    /**
     * Generar PDF de boleta
     */
    public function generatePdf(string $id, Request $request): Response
    {
        try {
            $boleta = Boleta::with(['company', 'branch', 'client'])->findOrFail($id);
            return $this->generateDocumentPdf($boleta, 'boleta', $request);
        } catch (Exception $e) {
            return $this->errorResponse('Error al generar PDF', $e);
        }
    }

    /**
     * Crear resumen diario desde fecha
     */
    public function createDailySummaryFromDate(CreateDailySummaryRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $summary = $this->documentService->createSummaryFromBoletas($validated);

            return response()->json([
                'success' => true,
                'data' => $summary->load(['company', 'branch', 'boletas']),
                'message' => 'Resumen diario creado correctamente'
            ], 201);

        } catch (Exception $e) {
            return $this->errorResponse('Error al crear resumen diario', $e);
        }
    }

    /**
     * Enviar resumen a SUNAT
     */
    public function sendSummaryToSunat(string $summaryId): JsonResponse
    {
        try {
            $summary = DailySummary::with(['company', 'branch', 'boletas'])->findOrFail($summaryId);

            if ($summary->estado_sunat === 'ACEPTADO') {
                return response()->json([
                    'success' => false,
                    'message' => 'El resumen ya fue aceptado por SUNAT'
                ], 400);
            }

            $result = $this->documentService->sendDailySummaryToSunat($summary);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['document']->load(['company', 'branch', 'boletas']),
                    'ticket' => $result['ticket'],
                    'message' => 'Resumen enviado correctamente a SUNAT'
                ]);
            }

            return response()->json([
                'success' => false,
                'data' => $result['document']->load(['company', 'branch', 'boletas']),
                'message' => 'Error al enviar resumen a SUNAT',
                'error' => $result['error']
            ], 400);

        } catch (Exception $e) {
            return $this->errorResponse('Error interno al enviar resumen', $e);
        }
    }

    /**
     * Consultar estado de resumen
     */
    public function checkSummaryStatus(string $summaryId): JsonResponse
    {
        try {
            $summary = DailySummary::with(['company', 'branch', 'boletas'])->findOrFail($summaryId);
            $result = $this->documentService->checkSummaryStatus($summary);

            if ($result['success']) {
                return response()->json([
                    'success' => true,
                    'data' => $result['document']->load(['company', 'branch', 'boletas']),
                    'message' => 'Estado del resumen consultado correctamente'
                ]);
            }

            return response()->json([
                'success' => false,
                'message' => 'Error al consultar estado: ' . ($result['error'] ?? 'Error desconocido')
            ], 400);

        } catch (Exception $e) {
            return $this->errorResponse('Error al consultar estado del resumen', $e);
        }
    }

    /**
     * Obtener boletas pendientes para resumen
     */
    public function getBoletsasPendingForSummary(GetBoletasPendingRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $boletas = $this->getPendingBoletas($validated);

            return response()->json([
                'success' => true,
                'data' => $boletas,
                'total' => $boletas->count(),
                'message' => 'Boletas pendientes obtenidas correctamente'
            ]);

        } catch (Exception $e) {
            return $this->errorResponse('Error al obtener boletas pendientes', $e);
        }
    }

    /**
     * Aplicar filtros a la consulta
     */
    private function applyFilters($query, Request $request): void
    {
        $filters = [
            'company_id' => 'where',
            'branch_id' => 'where',
            'estado_sunat' => 'where',
            'fecha_desde' => 'whereDate|>=',
            'fecha_hasta' => 'whereDate|<='
        ];

        foreach ($filters as $field => $operation) {
            if ($request->has($field)) {
                $parts = explode('|', $operation);
                $method = $parts[0];
                $operator = $parts[1] ?? null;

                if ($operator) {
                    $query->$method('fecha_emision', $operator, $request->$field);
                } else {
                    $query->$method($field, $request->$field);
                }
            }
        }
    }

    /**
     * Obtener boletas pendientes
     */
    private function getPendingBoletas(array $filters)
    {
        return Boleta::with(['company', 'branch', 'client'])
            ->where('company_id', $filters['company_id'])
            ->where('branch_id', $filters['branch_id'])
            ->whereDate('fecha_emision', $filters['fecha_emision'])
            ->where('estado_sunat', 'PENDIENTE')
            ->whereNull('daily_summary_id')
            ->get();
    }

    /**
     * Manejar error de SUNAT
     */
    private function handleSunatError(array $result): JsonResponse
    {
        $error = $result['error'];
        $errorCode = 'UNKNOWN';
        $errorMessage = 'Error desconocido';

        if (is_object($error)) {
            $errorCode = method_exists($error, 'getCode') ? $error->getCode() : ($error->code ?? $errorCode);
            $errorMessage = method_exists($error, 'getMessage') ? $error->getMessage() : ($error->message ?? $errorMessage);
        }

        return response()->json([
            'success' => false,
            'data' => $result['document'],
            'message' => 'Error al enviar a SUNAT: ' . $errorMessage,
            'error_code' => $errorCode
        ], 400);
    }

    /**
     * Obtener datos de paginación
     */
    private function getPaginationData($paginator): array
    {
        return [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
        ];
    }

    /**
     * Respuesta de error estandarizada
     */
    private function errorResponse(string $message, Exception $e): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message . ': ' . $e->getMessage()
        ], 500);
    }

    /**
     * Respuesta de no encontrado
     */
    private function notFoundResponse(string $message): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $message
        ], 404);
    }
}