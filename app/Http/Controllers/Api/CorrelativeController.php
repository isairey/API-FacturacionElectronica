<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Correlative;
use App\Models\Branch;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class CorrelativeController extends Controller
{
    /**
     * Tipos de documentos SUNAT válidos
     */
    private const TIPOS_DOCUMENTO = [
        '01' => 'Factura',
        '03' => 'Boleta de Venta',
        '07' => 'Nota de Crédito',
        '08' => 'Nota de Débito',
        '09' => 'Guía de Remisión',
        '20' => 'Comprobante de Retención',
        'RC' => 'Resumen de Anulaciones',
        'RA' => 'Resumen Diario'
    ];

    /**
     * Listar correlativos de una sucursal
     */
    public function index(Branch $branch): JsonResponse
    {
        try {
            $correlatives = $branch->correlatives()
                                  ->orderBy('tipo_documento')
                                  ->orderBy('serie')
                                  ->get()
                                  ->map(function ($correlative) {
                                      return [
                                          'id' => $correlative->id,
                                          'branch_id' => $correlative->branch_id,
                                          'tipo_documento' => $correlative->tipo_documento,
                                          'tipo_documento_nombre' => self::TIPOS_DOCUMENTO[$correlative->tipo_documento] ?? 'Desconocido',
                                          'serie' => $correlative->serie,
                                          'correlativo_actual' => $correlative->correlativo_actual,
                                          'numero_completo' => $correlative->numero_completo,
                                          'proximo_numero' => $correlative->serie . '-' . str_pad($correlative->correlativo_actual + 1, 6, '0', STR_PAD_LEFT),
                                          'created_at' => $correlative->created_at,
                                          'updated_at' => $correlative->updated_at
                                      ];
                                  });

            return response()->json([
                'success' => true,
                'data' => [
                    'branch' => [
                        'id' => $branch->id,
                        'codigo' => $branch->codigo,
                        'nombre' => $branch->nombre,
                        'company_id' => $branch->company_id
                    ],
                    'correlatives' => $correlatives
                ],
                'meta' => [
                    'total' => $correlatives->count(),
                    'tipos_disponibles' => self::TIPOS_DOCUMENTO
                ]
            ]);

        } catch (Exception $e) {
            Log::error("Error al listar correlativos", [
                'branch_id' => $branch->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener correlativos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nuevo correlativo para una sucursal
     */
    public function store(Request $request, Branch $branch): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'tipo_documento' => 'required|string|max:2|in:' . implode(',', array_keys(self::TIPOS_DOCUMENTO)),
                'serie' => 'required|string|max:4|regex:/^[A-Z0-9]+$/',
                'correlativo_inicial' => 'integer|min:0|max:99999999'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar que no exista la combinación sucursal + tipo + serie
            $existingCorrelative = Correlative::where('branch_id', $branch->id)
                                             ->where('tipo_documento', $request->tipo_documento)
                                             ->where('serie', $request->serie)
                                             ->first();

            if ($existingCorrelative) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe un correlativo para esta sucursal con el mismo tipo de documento y serie'
                ], 400);
            }

            $correlative = Correlative::create([
                'branch_id' => $branch->id,
                'tipo_documento' => $request->tipo_documento,
                'serie' => strtoupper($request->serie),
                'correlativo_actual' => $request->correlativo_inicial ?? 0
            ]);

            Log::info("Correlativo creado exitosamente", [
                'correlative_id' => $correlative->id,
                'branch_id' => $branch->id,
                'tipo_documento' => $correlative->tipo_documento,
                'serie' => $correlative->serie
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Correlativo creado exitosamente',
                'data' => [
                    'id' => $correlative->id,
                    'branch_id' => $correlative->branch_id,
                    'tipo_documento' => $correlative->tipo_documento,
                    'tipo_documento_nombre' => self::TIPOS_DOCUMENTO[$correlative->tipo_documento],
                    'serie' => $correlative->serie,
                    'correlativo_actual' => $correlative->correlativo_actual,
                    'numero_completo' => $correlative->numero_completo,
                    'proximo_numero' => $correlative->serie . '-' . str_pad($correlative->correlativo_actual + 1, 6, '0', STR_PAD_LEFT)
                ]
            ], 201);

        } catch (Exception $e) {
            Log::error("Error al crear correlativo", [
                'branch_id' => $branch->id,
                'request_data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear correlativo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar correlativo
     */
    public function update(Request $request, Branch $branch, Correlative $correlative): JsonResponse
    {
        try {
            // Verificar que el correlativo pertenece a la sucursal
            if ($correlative->branch_id !== $branch->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'El correlativo no pertenece a esta sucursal'
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'tipo_documento' => 'required|string|max:2|in:' . implode(',', array_keys(self::TIPOS_DOCUMENTO)),
                'serie' => 'required|string|max:4|regex:/^[A-Z0-9]+$/',
                'correlativo_actual' => 'required|integer|min:0|max:99999999'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar que no exista otra combinación igual (excluyendo el actual)
            $existingCorrelative = Correlative::where('branch_id', $branch->id)
                                             ->where('tipo_documento', $request->tipo_documento)
                                             ->where('serie', $request->serie)
                                             ->where('id', '!=', $correlative->id)
                                             ->first();

            if ($existingCorrelative) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe otro correlativo para esta sucursal con el mismo tipo de documento y serie'
                ], 400);
            }

            $correlative->update([
                'tipo_documento' => $request->tipo_documento,
                'serie' => strtoupper($request->serie),
                'correlativo_actual' => $request->correlativo_actual
            ]);

            Log::info("Correlativo actualizado exitosamente", [
                'correlative_id' => $correlative->id,
                'branch_id' => $branch->id,
                'changes' => $correlative->getChanges()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Correlativo actualizado exitosamente',
                'data' => [
                    'id' => $correlative->id,
                    'branch_id' => $correlative->branch_id,
                    'tipo_documento' => $correlative->tipo_documento,
                    'tipo_documento_nombre' => self::TIPOS_DOCUMENTO[$correlative->tipo_documento],
                    'serie' => $correlative->serie,
                    'correlativo_actual' => $correlative->correlativo_actual,
                    'numero_completo' => $correlative->numero_completo,
                    'proximo_numero' => $correlative->serie . '-' . str_pad($correlative->correlativo_actual + 1, 6, '0', STR_PAD_LEFT)
                ]
            ]);

        } catch (Exception $e) {
            Log::error("Error al actualizar correlativo", [
                'correlative_id' => $correlative->id,
                'branch_id' => $branch->id,
                'request_data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar correlativo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar correlativo
     */
    public function destroy(Branch $branch, Correlative $correlative): JsonResponse
    {
        try {
            // Verificar que el correlativo pertenece a la sucursal
            if ($correlative->branch_id !== $branch->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'El correlativo no pertenece a esta sucursal'
                ], 404);
            }

            // Verificar si hay documentos usando esta serie
            // Aquí podrías agregar validaciones adicionales si es necesario

            $correlativeInfo = [
                'id' => $correlative->id,
                'tipo_documento' => $correlative->tipo_documento,
                'serie' => $correlative->serie,
                'correlativo_actual' => $correlative->correlativo_actual
            ];

            $correlative->delete();

            Log::warning("Correlativo eliminado", [
                'branch_id' => $branch->id,
                'correlative_info' => $correlativeInfo
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Correlativo eliminado exitosamente'
            ]);

        } catch (Exception $e) {
            Log::error("Error al eliminar correlativo", [
                'correlative_id' => $correlative->id,
                'branch_id' => $branch->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar correlativo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear correlativos por lote para una sucursal
     */
    public function createBatch(Request $request, Branch $branch): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'correlativos' => 'required|array|min:1',
                'correlativos.*.tipo_documento' => 'required|string|max:2|in:' . implode(',', array_keys(self::TIPOS_DOCUMENTO)),
                'correlativos.*.serie' => 'required|string|max:4|regex:/^[A-Z0-9]+$/',
                'correlativos.*.correlativo_inicial' => 'integer|min:0|max:99999999'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $created = [];
            $errors = [];

            foreach ($request->correlativos as $index => $data) {
                try {
                    // Verificar que no exista la combinación
                    $exists = Correlative::where('branch_id', $branch->id)
                                        ->where('tipo_documento', $data['tipo_documento'])
                                        ->where('serie', strtoupper($data['serie']))
                                        ->exists();

                    if ($exists) {
                        $errors[] = [
                            'index' => $index,
                            'error' => "Ya existe correlativo para tipo {$data['tipo_documento']} serie {$data['serie']}"
                        ];
                        continue;
                    }

                    $correlative = Correlative::create([
                        'branch_id' => $branch->id,
                        'tipo_documento' => $data['tipo_documento'],
                        'serie' => strtoupper($data['serie']),
                        'correlativo_actual' => $data['correlativo_inicial'] ?? 0
                    ]);

                    $created[] = [
                        'id' => $correlative->id,
                        'tipo_documento' => $correlative->tipo_documento,
                        'tipo_documento_nombre' => self::TIPOS_DOCUMENTO[$correlative->tipo_documento],
                        'serie' => $correlative->serie,
                        'correlativo_actual' => $correlative->correlativo_actual,
                        'numero_completo' => $correlative->numero_completo
                    ];

                } catch (Exception $e) {
                    $errors[] = [
                        'index' => $index,
                        'error' => $e->getMessage()
                    ];
                }
            }

            Log::info("Correlativos creados por lote", [
                'branch_id' => $branch->id,
                'created_count' => count($created),
                'error_count' => count($errors)
            ]);

            return response()->json([
                'success' => true,
                'message' => count($created) . ' correlativos creados exitosamente',
                'data' => [
                    'created' => $created,
                    'errors' => $errors
                ],
                'meta' => [
                    'created_count' => count($created),
                    'error_count' => count($errors),
                    'total_requested' => count($request->correlativos)
                ]
            ]);

        } catch (Exception $e) {
            Log::error("Error al crear correlativos por lote", [
                'branch_id' => $branch->id,
                'request_data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear correlativos: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Incrementar correlativo (uso interno del sistema)
     */
    public function increment(Branch $branch, Correlative $correlative): JsonResponse
    {
        try {
            // Verificar que el correlativo pertenece a la sucursal
            if ($correlative->branch_id !== $branch->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'El correlativo no pertenece a esta sucursal'
                ], 404);
            }

            $oldCorrelativo = $correlative->correlativo_actual;
            $correlative->increment('correlativo_actual');

            Log::info("Correlativo incrementado", [
                'correlative_id' => $correlative->id,
                'branch_id' => $branch->id,
                'old_correlativo' => $oldCorrelativo,
                'new_correlativo' => $correlative->correlativo_actual
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Correlativo incrementado exitosamente',
                'data' => [
                    'id' => $correlative->id,
                    'serie' => $correlative->serie,
                    'correlativo_anterior' => $oldCorrelativo,
                    'correlativo_actual' => $correlative->correlativo_actual,
                    'numero_usado' => $correlative->serie . '-' . str_pad($oldCorrelativo + 1, 6, '0', STR_PAD_LEFT),
                    'proximo_numero' => $correlative->serie . '-' . str_pad($correlative->correlativo_actual + 1, 6, '0', STR_PAD_LEFT)
                ]
            ]);

        } catch (Exception $e) {
            Log::error("Error al incrementar correlativo", [
                'correlative_id' => $correlative->id,
                'branch_id' => $branch->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al incrementar correlativo: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener tipos de documentos disponibles
     */
    public function getDocumentTypes(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => collect(self::TIPOS_DOCUMENTO)->map(function ($nombre, $codigo) {
                return [
                    'codigo' => $codigo,
                    'nombre' => $nombre
                ];
            })->values()
        ]);
    }
}