<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ConsultaCpeService;
use App\Services\ConsultaCpeServiceMejorado;
use App\Models\Invoice;
use App\Models\Boleta;
use App\Models\CreditNote;
use App\Models\DebitNote;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ConsultaCpeController extends Controller
{
    /**
     * Consultar estado de una factura específica
     */
    public function consultarFactura(Request $request, $id): JsonResponse
    {
        try {
            $invoice = Invoice::with('company')->findOrFail($id);
            
            $consultaService = new ConsultaCpeService($invoice->company);
            $resultado = $consultaService->consultarComprobante($invoice);

            return response()->json([
                'success' => $resultado['success'],
                'message' => $resultado['message'],
                'data' => [
                    'comprobante' => [
                        'id' => $invoice->id,
                        'tipo_documento' => $invoice->tipo_documento,
                        'serie' => $invoice->serie,
                        'correlativo' => $invoice->correlativo,
                        'fecha_emision' => $invoice->fecha_emision,
                        'monto' => $invoice->mto_imp_venta
                    ],
                    'consulta' => $resultado['data']
                ]
            ], $resultado['success'] ? 200 : 422);

        } catch (\Exception $e) {
            Log::error('Error en consulta CPE factura: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar factura',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Consultar estado de una boleta específica
     */
    public function consultarBoleta(Request $request, $id): JsonResponse
    {
        try {
            $boleta = Boleta::with('company')->findOrFail($id);
            
            $consultaService = new ConsultaCpeService($boleta->company);
            $resultado = $consultaService->consultarComprobante($boleta);

            return response()->json([
                'success' => $resultado['success'],
                'message' => $resultado['message'],
                'data' => [
                    'comprobante' => [
                        'id' => $boleta->id,
                        'tipo_documento' => $boleta->tipo_documento,
                        'serie' => $boleta->serie,
                        'correlativo' => $boleta->correlativo,
                        'fecha_emision' => $boleta->fecha_emision,
                        'monto' => $boleta->mto_imp_venta
                    ],
                    'consulta' => $resultado['data']
                ]
            ], $resultado['success'] ? 200 : 422);

        } catch (\Exception $e) {
            Log::error('Error en consulta CPE boleta: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar boleta',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Consultar estado de una nota de crédito
     */
    public function consultarNotaCredito(Request $request, $id): JsonResponse
    {
        try {
            $nota = CreditNote::with('company')->findOrFail($id);
            
            $consultaService = new ConsultaCpeService($nota->company);
            $resultado = $consultaService->consultarComprobante($nota);

            return response()->json([
                'success' => $resultado['success'],
                'message' => $resultado['message'],
                'data' => [
                    'comprobante' => [
                        'id' => $nota->id,
                        'tipo_documento' => $nota->tipo_documento,
                        'serie' => $nota->serie,
                        'correlativo' => $nota->correlativo,
                        'fecha_emision' => $nota->fecha_emision,
                        'monto' => $nota->mto_imp_venta
                    ],
                    'consulta' => $resultado['data']
                ]
            ], $resultado['success'] ? 200 : 422);

        } catch (\Exception $e) {
            Log::error('Error en consulta CPE nota crédito: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar nota de crédito',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Consultar estado de una nota de débito
     */
    public function consultarNotaDebito(Request $request, $id): JsonResponse
    {
        try {
            $nota = DebitNote::with('company')->findOrFail($id);
            
            $consultaService = new ConsultaCpeService($nota->company);
            $resultado = $consultaService->consultarComprobante($nota);

            return response()->json([
                'success' => $resultado['success'],
                'message' => $resultado['message'],
                'data' => [
                    'comprobante' => [
                        'id' => $nota->id,
                        'tipo_documento' => $nota->tipo_documento,
                        'serie' => $nota->serie,
                        'correlativo' => $nota->correlativo,
                        'fecha_emision' => $nota->fecha_emision,
                        'monto' => $nota->mto_imp_venta
                    ],
                    'consulta' => $resultado['data']
                ]
            ], $resultado['success'] ? 200 : 422);

        } catch (\Exception $e) {
            Log::error('Error en consulta CPE nota débito: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar nota de débito',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Consulta masiva de documentos por empresa
     */
    public function consultarDocumentosMasivo(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'tipo_documento' => ['required', Rule::in(['01', '03', '07', '08'])],
            'fecha_desde' => 'required|date',
            'fecha_hasta' => 'required|date|after_or_equal:fecha_desde',
            'limit' => 'nullable|integer|min:1|max:100'
        ]);

        try {
            $company = Company::findOrFail($validated['company_id']);
            $limit = $validated['limit'] ?? 50;

            // Seleccionar modelo según tipo de documento
            $documentos = $this->obtenerDocumentos(
                $validated['tipo_documento'],
                $company->id,
                $validated['fecha_desde'],
                $validated['fecha_hasta'],
                $limit
            );

            if ($documentos->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No se encontraron documentos en el rango especificado',
                    'data' => []
                ]);
            }

            $consultaService = new ConsultaCpeService($company);
            $resultado = $consultaService->consultarDocumentosMasivo($documentos->toArray());

            return response()->json([
                'success' => true,
                'message' => 'Consulta masiva completada',
                'data' => $resultado
            ]);

        } catch (\Exception $e) {
            Log::error('Error en consulta CPE masiva: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error en consulta masiva',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener documentos según tipo
     */
    private function obtenerDocumentos(string $tipoDoc, int $companyId, string $fechaDesde, string $fechaHasta, int $limit)
    {
        $query = null;

        switch ($tipoDoc) {
            case '01': // Facturas
                $query = Invoice::where('company_id', $companyId);
                break;
            case '03': // Boletas
                $query = Boleta::where('company_id', $companyId);
                break;
            case '07': // Notas de Crédito
                $query = CreditNote::where('company_id', $companyId);
                break;
            case '08': // Notas de Débito
                $query = DebitNote::where('company_id', $companyId);
                break;
            default:
                throw new \InvalidArgumentException('Tipo de documento no válido');
        }

        return $query->whereBetween('fecha_emision', [$fechaDesde, $fechaHasta])
                    ->whereIn('estado_sunat', ['ACEPTADO', 'RECHAZADO', null])
                    ->orderBy('fecha_emision', 'desc')
                    ->limit($limit)
                    ->get();
    }

    /**
     * Obtener estadísticas de consultas por empresa
     */
    public function estadisticasConsultas(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'company_id' => 'required|exists:companies,id',
            'fecha_desde' => 'nullable|date',
            'fecha_hasta' => 'nullable|date|after_or_equal:fecha_desde'
        ]);

        try {
            $company = Company::findOrFail($validated['company_id']);
            $fechaDesde = $validated['fecha_desde'] ?? now()->subMonth()->format('Y-m-d');
            $fechaHasta = $validated['fecha_hasta'] ?? now()->format('Y-m-d');

            $estadisticas = [];

            // Estadísticas por tipo de documento
            $tipos = ['01' => 'facturas', '03' => 'boletas', '07' => 'credit_notes', '08' => 'debit_notes'];
            
            foreach ($tipos as $codigo => $tabla) {
                $modelo = $this->getModeloByTipo($codigo);
                
                $total = $modelo::where('company_id', $company->id)
                    ->whereBetween('fecha_emision', [$fechaDesde, $fechaHasta])
                    ->count();
                    
                $consultados = $modelo::where('company_id', $company->id)
                    ->whereBetween('fecha_emision', [$fechaDesde, $fechaHasta])
                    ->whereNotNull('consulta_cpe_fecha')
                    ->count();

                $estadisticas[$tabla] = [
                    'total' => $total,
                    'consultados' => $consultados,
                    'pendientes' => $total - $consultados,
                    'porcentaje_consultado' => $total > 0 ? round(($consultados / $total) * 100, 2) : 0
                ];
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'periodo' => [
                        'desde' => $fechaDesde,
                        'hasta' => $fechaHasta
                    ],
                    'estadisticas' => $estadisticas
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener estadísticas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener modelo por tipo de documento
     */
    private function getModeloByTipo(string $tipo): string
    {
        return match($tipo) {
            '01' => Invoice::class,
            '03' => Boleta::class,
            '07' => CreditNote::class,
            '08' => DebitNote::class,
            default => throw new \InvalidArgumentException('Tipo no válido')
        };
    }
}