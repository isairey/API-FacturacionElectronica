<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Branch\StoreBranchRequest;
use App\Http\Requests\Branch\UpdateBranchRequest;
use App\Models\Branch;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Exception;

class BranchController extends Controller
{
    /**
     * Listar sucursales de una empresa
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Branch::with(['company:id,ruc,razon_social']);

            // Filtrar por empresa si se proporciona
            if ($request->has('company_id')) {
                $query->where('company_id', $request->company_id);
            }

            $branches = $query->get();

            return response()->json([
                'success' => true,
                'data' => $branches,
                'meta' => [
                    'total' => $branches->count(),
                    'companies_count' => $branches->unique('company_id')->count()
                ]
            ]);

        } catch (Exception $e) {
            Log::error("Error al listar sucursales", [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener sucursales: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nueva sucursal
     */
    public function store(StoreBranchRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            // Verificar que la empresa existe y está activa
            $company = Company::where('id', $validated['company_id'])
                             ->where('activo', true)
                             ->first();

            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'La empresa especificada no existe o está inactiva'
                ], 404);
            }

            $branch = Branch::create($validated);

            Log::info("Sucursal creada exitosamente", [
                'branch_id' => $branch->id,
                'company_id' => $branch->company_id,
                'nombre' => $branch->nombre
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sucursal creada exitosamente',
                'data' => $branch->load('company:id,ruc,razon_social')
            ], 201);

        } catch (Exception $e) {
            Log::error("Error al crear sucursal", [
                'request_data' => $validated ?? [],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear sucursal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener sucursal específica
     */
    public function show(Branch $branch): JsonResponse
    {
        try {
            $branch->load(['company:id,ruc,razon_social,nombre_comercial']);

            return response()->json([
                'success' => true,
                'data' => $branch
            ]);

        } catch (Exception $e) {
            Log::error("Error al obtener sucursal", [
                'branch_id' => $branch->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener sucursal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar sucursal
     */
    public function update(UpdateBranchRequest $request, Branch $branch): JsonResponse
    {
        try {
            $validated = $request->validated();
            
            // Verificar que la empresa nueva existe y está activa (si se está cambiando)
            if (isset($validated['company_id'])) {
                $company = Company::where('id', $validated['company_id'])
                                 ->where('activo', true)
                                 ->first();

                if (!$company) {
                    return response()->json([
                        'success' => false,
                        'message' => 'La empresa especificada no existe o está inactiva'
                    ], 404);
                }
            }

            $branch->update($validated);

            Log::info("Sucursal actualizada exitosamente", [
                'branch_id' => $branch->id,
                'company_id' => $branch->company_id,
                'changes' => $branch->getChanges()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sucursal actualizada exitosamente',
                'data' => $branch->fresh()->load('company:id,ruc,razon_social')
            ]);

        } catch (Exception $e) {
            Log::error("Error al actualizar sucursal", [
                'branch_id' => $branch->id,
                'request_data' => $validated ?? [],
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar sucursal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar sucursal (soft delete - marcar como inactiva)
     */
    public function destroy(Branch $branch): JsonResponse
    {
        try {
            // Verificar si la sucursal tiene documentos asociados
            $hasDocuments = false; // Podrías implementar estas verificaciones si es necesario
            // $hasDocuments = $branch->invoices()->count() > 0 ||
            //                $branch->dispatchGuides()->count() > 0;

            if ($hasDocuments) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar la sucursal porque tiene documentos asociados. Considere desactivarla en su lugar.'
                ], 400);
            }

            // Marcar como inactiva en lugar de eliminar
            $branch->update(['activo' => false]);

            Log::warning("Sucursal desactivada", [
                'branch_id' => $branch->id,
                'nombre' => $branch->nombre
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sucursal desactivada exitosamente'
            ]);

        } catch (Exception $e) {
            Log::error("Error al desactivar sucursal", [
                'branch_id' => $branch->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al desactivar sucursal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activar sucursal
     */
    public function activate(Branch $branch): JsonResponse
    {
        try {
            $branch->update(['activo' => true]);

            Log::info("Sucursal activada", [
                'branch_id' => $branch->id,
                'nombre' => $branch->nombre
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Sucursal activada exitosamente',
                'data' => $branch->load('company:id,ruc,razon_social')
            ]);

        } catch (Exception $e) {
            Log::error("Error al activar sucursal", [
                'branch_id' => $branch->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al activar sucursal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener sucursales de una empresa específica
     */
    public function getByCompany(Company $company): JsonResponse
    {
        try {
            $branches = $company->branches()
                              ->select([
                                  'id', 'company_id', 'nombre', 'direccion',
                                  'distrito', 'provincia', 'departamento',
                                  'telefono', 'email', 'activo',
                                  'created_at', 'updated_at'
                              ])
                              ->get();

            return response()->json([
                'success' => true,
                'data' => $branches,
                'meta' => [
                    'company_id' => $company->id,
                    'company_name' => $company->razon_social,
                    'total_branches' => $branches->count(),
                    'active_branches' => $branches->where('activo', true)->count()
                ]
            ]);

        } catch (Exception $e) {
            Log::error("Error al obtener sucursales por empresa", [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener sucursales: ' . $e->getMessage()
            ], 500);
        }
    }
}