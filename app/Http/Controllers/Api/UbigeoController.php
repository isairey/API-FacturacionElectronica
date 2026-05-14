<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UbiRegion;
use App\Models\UbiProvincia;
use App\Models\UbiDistrito;
use App\Http\Requests\UbigeoSearchRequest;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class UbigeoController extends Controller
{
    public function getRegiones(): JsonResponse
    {
        $regiones = UbiRegion::orderBy('nombre')->get();
        
        return response()->json([
            'success' => true,
            'data' => $regiones
        ]);
    }
    
    public function getProvincias(Request $request): JsonResponse
    {
        $request->validate([
            'region_id' => 'nullable|string|size:6'
        ]);
        
        $query = UbiProvincia::with('region');
        
        if ($request->has('region_id')) {
            $query->where('region_id', $request->region_id);
        }
        
        $provincias = $query->orderBy('nombre')->get();
        
        return response()->json([
            'success' => true,
            'data' => $provincias
        ]);
    }
    
    public function getDistritos(Request $request): JsonResponse
    {
        $request->validate([
            'provincia_id' => 'nullable|string|size:6',
            'region_id' => 'nullable|string|size:6',
            'search' => 'nullable|string|min:2|max:255'
        ]);
        
        $query = UbiDistrito::with(['provincia', 'region']);
        
        if ($request->has('provincia_id')) {
            $query->where('provincia_id', $request->provincia_id);
        }
        
        if ($request->has('region_id')) {
            $query->where('region_id', $request->region_id);
        }
        
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('nombre', 'like', "%{$search}%")
                  ->orWhere('info_busqueda', 'like', "%{$search}%");
            });
        }
        
        $distritos = $query->orderBy('nombre')->get();
        
        return response()->json([
            'success' => true,
            'data' => $distritos
        ]);
    }
    
    public function searchUbigeo(UbigeoSearchRequest $request): JsonResponse
    {
        $search = $request->validated()['q'];
        
        $distritos = UbiDistrito::with(['provincia', 'region'])
            ->where(function($query) use ($search) {
                $query->where('nombre', 'like', "%{$search}%")
                      ->orWhere('info_busqueda', 'like', "%{$search}%");
            })
            ->limit(20)
            ->orderBy('nombre')
            ->get()
            ->map(function($distrito) {
                return [
                    'id' => $distrito->id,
                    'nombre' => $distrito->nombre,
                    'provincia' => $distrito->provincia->nombre,
                    'region' => $distrito->region->nombre,
                    'ubigeo_completo' => $distrito->region->nombre . ' - ' . 
                                       $distrito->provincia->nombre . ' - ' . 
                                       $distrito->nombre
                ];
            });
        
        return response()->json([
            'success' => true,
            'data' => $distritos
        ]);
    }
    
    public function getUbigeoById(string $id): JsonResponse
    {
        if (strlen($id) !== 6) {
            return response()->json([
                'success' => false,
                'message' => 'El código de ubigeo debe tener exactamente 6 dígitos'
            ], 400);
        }
        
        $distrito = UbiDistrito::with(['provincia', 'region'])->find($id);
        
        if (!$distrito) {
            return response()->json([
                'success' => false,
                'message' => 'Ubigeo no encontrado'
            ], 404);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $distrito->id,
                'nombre' => $distrito->nombre,
                'provincia_id' => $distrito->provincia_id,
                'provincia' => $distrito->provincia->nombre,
                'region_id' => $distrito->region_id,
                'region' => $distrito->region->nombre,
                'ubigeo_completo' => $distrito->region->nombre . ' - ' . 
                                   $distrito->provincia->nombre . ' - ' . 
                                   $distrito->nombre
            ]
        ]);
    }
}
