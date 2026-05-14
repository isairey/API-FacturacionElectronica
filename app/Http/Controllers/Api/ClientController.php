<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Exception;

class ClientController extends Controller
{
    /**
     * Listar clientes
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Client::with(['company:id,ruc,razon_social']);

            // Filtrar por empresa si se proporciona
            if ($request->has('company_id')) {
                $query->where('company_id', $request->company_id);
            }

            // Filtrar por tipo de documento
            if ($request->has('tipo_documento')) {
                $query->where('tipo_documento', $request->tipo_documento);
            }

            // Búsqueda por texto
            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function($q) use ($search) {
                    $q->where('numero_documento', 'like', "%{$search}%")
                      ->orWhere('razon_social', 'like', "%{$search}%")
                      ->orWhere('nombre_comercial', 'like', "%{$search}%");
                });
            }

            $clients = $query->paginate(20);

            return response()->json([
                'success' => true,
                'data' => $clients->items(),
                'meta' => [
                    'total' => $clients->total(),
                    'per_page' => $clients->perPage(),
                    'current_page' => $clients->currentPage(),
                    'last_page' => $clients->lastPage()
                ]
            ]);

        } catch (Exception $e) {
            Log::error("Error al listar clientes", [
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener clientes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Crear nuevo cliente
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => 'nullable|integer|exists:companies,id',
                'tipo_documento' => 'required|string|in:1,4,6,7,0', // DNI, CE, RUC, PAS, SIN DOC
                'numero_documento' => 'required|string|max:20',
                'razon_social' => 'required|string|max:255',
                'nombre_comercial' => 'nullable|string|max:255',
                'direccion' => 'nullable|string|max:255',
                'ubigeo' => 'nullable|string|size:6',
                'distrito' => 'nullable|string|max:100',
                'provincia' => 'nullable|string|max:100',
                'departamento' => 'nullable|string|max:100',
                'telefono' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'activo' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar que no exista otro cliente con el mismo documento en la misma empresa
            $existingClient = Client::where('company_id', $request->company_id)
                                   ->where('tipo_documento', $request->tipo_documento)
                                   ->where('numero_documento', $request->numero_documento)
                                   ->first();

            if ($existingClient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe un cliente con el mismo tipo y número de documento en esta empresa'
                ], 400);
            }

            // Verificar que la empresa existe y está activa
            $company = Company::where('id', $request->company_id)
                             ->where('activo', true)
                             ->first();

            if (!$company) {
                return response()->json([
                    'success' => false,
                    'message' => 'La empresa especificada no existe o está inactiva'
                ], 404);
            }

            $client = Client::create($validator->validated());

            Log::info("Cliente creado exitosamente", [
                'client_id' => $client->id,
                'company_id' => $client->company_id,
                'numero_documento' => $client->numero_documento,
                'razon_social' => $client->razon_social
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cliente creado exitosamente',
                'data' => $client->load('company:id,ruc,razon_social')
            ], 201);

        } catch (Exception $e) {
            Log::error("Error al crear cliente", [
                'request_data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al crear cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener cliente específico
     */
    public function show(Client $client): JsonResponse
    {
        try {
            $client->load(['company:id,ruc,razon_social,nombre_comercial']);

            return response()->json([
                'success' => true,
                'data' => $client
            ]);

        } catch (Exception $e) {
            Log::error("Error al obtener cliente", [
                'client_id' => $client->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Actualizar cliente
     */
    public function update(Request $request, Client $client): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => 'required|integer|exists:companies,id',
                'tipo_documento' => 'required|string|in:1,4,6,7,0',
                'numero_documento' => 'required|string|max:20',
                'razon_social' => 'required|string|max:255',
                'nombre_comercial' => 'nullable|string|max:255',
                'direccion' => 'nullable|string|max:255',
                'ubigeo' => 'nullable|string|size:6',
                'distrito' => 'nullable|string|max:100',
                'provincia' => 'nullable|string|max:100',
                'departamento' => 'nullable|string|max:100',
                'telefono' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'activo' => 'boolean'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            // Verificar que no exista otro cliente con el mismo documento en la misma empresa
            $existingClient = Client::where('company_id', $request->company_id)
                                   ->where('tipo_documento', $request->tipo_documento)
                                   ->where('numero_documento', $request->numero_documento)
                                   ->where('id', '!=', $client->id)
                                   ->first();

            if ($existingClient) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya existe otro cliente con el mismo tipo y número de documento en esta empresa'
                ], 400);
            }

            $client->update($validator->validated());

            Log::info("Cliente actualizado exitosamente", [
                'client_id' => $client->id,
                'company_id' => $client->company_id,
                'changes' => $client->getChanges()
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cliente actualizado exitosamente',
                'data' => $client->fresh()->load('company:id,ruc,razon_social')
            ]);

        } catch (Exception $e) {
            Log::error("Error al actualizar cliente", [
                'client_id' => $client->id,
                'request_data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Eliminar cliente (soft delete - marcar como inactivo)
     */
    public function destroy(Client $client): JsonResponse
    {
        try {
            // Verificar si el cliente tiene documentos asociados
            $hasDocuments = false; // Podrías implementar estas verificaciones si es necesario
            // $hasDocuments = $client->invoices()->count() > 0 ||
            //                $client->boletas()->count() > 0 ||
            //                $client->dispatchGuides()->count() > 0;

            if ($hasDocuments) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se puede eliminar el cliente porque tiene documentos asociados. Considere desactivarlo en su lugar.'
                ], 400);
            }

            // Marcar como inactivo en lugar de eliminar
            $client->update(['activo' => false]);

            Log::warning("Cliente desactivado", [
                'client_id' => $client->id,
                'numero_documento' => $client->numero_documento,
                'razon_social' => $client->razon_social
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cliente desactivado exitosamente'
            ]);

        } catch (Exception $e) {
            Log::error("Error al desactivar cliente", [
                'client_id' => $client->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al desactivar cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Activar cliente
     */
    public function activate(Client $client): JsonResponse
    {
        try {
            $client->update(['activo' => true]);

            Log::info("Cliente activado", [
                'client_id' => $client->id,
                'numero_documento' => $client->numero_documento,
                'razon_social' => $client->razon_social
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cliente activado exitosamente',
                'data' => $client->load('company:id,ruc,razon_social')
            ]);

        } catch (Exception $e) {
            Log::error("Error al activar cliente", [
                'client_id' => $client->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al activar cliente: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Obtener clientes de una empresa específica
     */
    public function getByCompany(Company $company): JsonResponse
    {
        try {
            $clients = $company->clients()
                             ->select([
                                 'id', 'company_id', 'tipo_documento', 'numero_documento',
                                 'razon_social', 'nombre_comercial', 'direccion',
                                 'distrito', 'provincia', 'departamento',
                                 'telefono', 'email', 'activo',
                                 'created_at', 'updated_at'
                             ])
                             ->orderBy('razon_social')
                             ->paginate(50);

            return response()->json([
                'success' => true,
                'data' => $clients->items(),
                'meta' => [
                    'company_id' => $company->id,
                    'company_name' => $company->razon_social,
                    'total' => $clients->total(),
                    'per_page' => $clients->perPage(),
                    'current_page' => $clients->currentPage(),
                    'last_page' => $clients->lastPage()
                ]
            ]);

        } catch (Exception $e) {
            Log::error("Error al obtener clientes por empresa", [
                'company_id' => $company->id,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener clientes: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Buscar cliente por número de documento
     */
    public function searchByDocument(Request $request): JsonResponse
    {
        try {
            $validator = Validator::make($request->all(), [
                'company_id' => 'required|integer|exists:companies,id',
                'tipo_documento' => 'required|string|in:1,4,6,7,0',
                'numero_documento' => 'required|string|max:20'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Errores de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $client = Client::where('company_id', $request->company_id)
                           ->where('tipo_documento', $request->tipo_documento)
                           ->where('numero_documento', $request->numero_documento)
                           ->where('activo', true)
                           ->with('company:id,ruc,razon_social')
                           ->first();

            if (!$client) {
                return response()->json([
                    'success' => false,
                    'message' => 'Cliente no encontrado'
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $client
            ]);

        } catch (Exception $e) {
            Log::error("Error al buscar cliente por documento", [
                'request_data' => $request->all(),
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al buscar cliente: ' . $e->getMessage()
            ], 500);
        }
    }
}